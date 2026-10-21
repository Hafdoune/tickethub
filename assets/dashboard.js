import { computed, createApp, onMounted, onUnmounted, reactive, ref } from 'vue/dist/vue.esm-browser.prod.js';
import StatCards from './dashboard/StatCards.js';
import CategoryBars from './dashboard/CategoryBars.js';
import TicketQueue from './dashboard/TicketQueue.js';

const REFRESH_MS = 30_000;
const root = document.getElementById('dashboard');

if (root) {
    const { url, csrf } = root.dataset;

    createApp({
        components: { StatCards, CategoryBars, TicketQueue },
        setup() {
            const state = reactive({ me: 0, counts: {}, byCategory: [], tickets: [] });
            const loading = ref(true);
            const error = ref(null);
            const updatedAt = ref(null);
            const busy = reactive({});
            let timer = null;

            const unassigned = computed(() => state.tickets.filter((t) => t.assignee === null).length);
            const urgent = computed(() => state.tickets.filter((t) => t.priority === 'urgent').length);

            async function load() {
                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    Object.assign(state, await response.json());
                    updatedAt.value = new Date();
                    error.value = null;
                } catch (e) {
                    error.value = 'Impossible de charger les données.';
                } finally {
                    loading.value = false;
                }
            }

            async function assign(ticket) {
                busy[ticket.id] = true;
                try {
                    const response = await fetch(ticket.assignUrl, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-Token': csrf },
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const updated = await response.json();
                    state.tickets = state.tickets.map((t) => (t.id === updated.id ? updated : t));
                    await load();
                } catch (e) {
                    error.value = "L'assignation a échoué.";
                } finally {
                    delete busy[ticket.id];
                }
            }

            onMounted(() => {
                load();
                timer = setInterval(load, REFRESH_MS);
            });
            onUnmounted(() => clearInterval(timer));

            return { state, loading, error, updatedAt, busy, unassigned, urgent, load, assign };
        },
        template: `
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="h3 mb-0">Tableau de bord</h1>
                <div class="small text-secondary">
                    <span v-if="updatedAt">Mis à jour à {{ updatedAt.toLocaleTimeString('fr-FR') }}</span>
                    <button type="button" class="btn btn-link btn-sm" @click="load">Actualiser</button>
                </div>
            </div>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="text-center text-secondary py-5">Chargement…</div>
            <template v-else>
                <stat-cards :counts="state.counts" :unassigned="unassigned" :urgent="urgent"></stat-cards>
                <div class="row g-4">
                    <div class="col-lg-9">
                        <ticket-queue :tickets="state.tickets" :me="state.me" :busy="busy" @assign="assign"></ticket-queue>
                    </div>
                    <div class="col-lg-3">
                        <category-bars :items="state.byCategory"></category-bars>
                    </div>
                </div>
            </template>
        `,
    }).mount(root);
}
