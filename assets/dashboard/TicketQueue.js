import { computed, ref } from 'vue/dist/vue.esm-browser.prod.js';

const PRIORITY_WEIGHT = { urgent: 4, high: 3, medium: 2, low: 1 };

const FILTERS = [
    { key: 'all', label: 'Tous' },
    { key: 'unassigned', label: 'Non assignés' },
    { key: 'mine', label: 'Mes tickets' },
    { key: 'urgent', label: 'Urgents' },
];

export default {
    props: {
        tickets: { type: Array, required: true },
        me: { type: Number, required: true },
        busy: { type: Object, required: true },
    },
    emits: ['assign'],
    setup(props) {
        const filter = ref('unassigned');
        const search = ref('');
        const sortBy = ref('priority');

        const visible = computed(() => {
            const q = search.value.trim().toLowerCase();

            return props.tickets
                .filter((t) => {
                    if (filter.value === 'unassigned') return t.assignee === null;
                    if (filter.value === 'mine') return t.assignee?.id === props.me;
                    if (filter.value === 'urgent') return t.priority === 'urgent';
                    return true;
                })
                .filter((t) => !q || t.title.toLowerCase().includes(q) || t.author.toLowerCase().includes(q))
                .sort((a, b) => sortBy.value === 'priority'
                    ? PRIORITY_WEIGHT[b.priority] - PRIORITY_WEIGHT[a.priority] || a.createdAt.localeCompare(b.createdAt)
                    : a.createdAt.localeCompare(b.createdAt));
        });

        const age = (iso) => {
            const hours = Math.floor((Date.now() - new Date(iso).getTime()) / 36e5);
            if (hours < 1) return "moins d'1 h";
            if (hours < 24) return `${hours} h`;
            return `${Math.floor(hours / 24)} j`;
        };

        return { FILTERS, filter, search, sortBy, visible, age };
    },
    template: `
        <div class="card shadow-sm">
            <div class="card-body border-bottom">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button v-for="f in FILTERS" :key="f.key" type="button" class="btn"
                                :class="filter === f.key ? 'btn-dark' : 'btn-outline-secondary'"
                                @click="filter = f.key">{{ f.label }}</button>
                    </div>
                    <input v-model="search" type="search" class="form-control form-control-sm ms-auto" style="max-width: 240px"
                           placeholder="Sujet ou client">
                    <select v-model="sortBy" class="form-select form-select-sm" style="max-width: 170px">
                        <option value="priority">Tri : priorité</option>
                        <option value="age">Tri : ancienneté</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>#</th><th>Sujet</th><th>Client</th><th>Priorité</th><th>Statut</th><th>Attente</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in visible" :key="t.id">
                            <td class="text-secondary">{{ t.id }}</td>
                            <td>
                                <a :href="t.url" class="text-decoration-none fw-medium">{{ t.title }}</a>
                                <div class="small text-secondary">{{ t.category }}</div>
                            </td>
                            <td>{{ t.author }}</td>
                            <td><span class="badge" :class="'badge-priority-' + t.priority">{{ t.priorityLabel }}</span></td>
                            <td><span class="badge" :class="'badge-status-' + t.status">{{ t.statusLabel }}</span></td>
                            <td class="text-nowrap small">{{ age(t.createdAt) }}</td>
                            <td class="text-end text-nowrap">
                                <span v-if="t.assignee" class="small text-secondary">{{ t.assignee.id === me ? 'Moi' : t.assignee.name }}</span>
                                <button v-else type="button" class="btn btn-sm btn-outline-dark" :disabled="busy[t.id]"
                                        @click="$emit('assign', t)">{{ busy[t.id] ? '…' : "Me l'assigner" }}</button>
                            </td>
                        </tr>
                        <tr v-if="!visible.length">
                            <td colspan="7" class="text-center text-secondary py-4">Rien à traiter ici.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `,
};
