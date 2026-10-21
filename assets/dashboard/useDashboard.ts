import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { assignToMe, fetchDashboard } from './api';
import type { DashboardData, DashboardTicket } from './types';

export function useDashboard(dataUrl: string, csrfToken: string, refreshMs = 30_000) {
    const data = reactive<DashboardData>({
        me: 0,
        counts: { open: 0, in_progress: 0, resolved: 0, closed: 0 },
        byCategory: [],
        tickets: [],
    });
    const loading = ref(true);
    const error = ref<string | null>(null);
    const updatedAt = ref<Date | null>(null);
    const busy = reactive<Record<number, boolean>>({});

    const unassigned = computed(() => data.tickets.filter((t) => t.assignee === null).length);
    const urgent = computed(() => data.tickets.filter((t) => t.priority === 'urgent').length);

    async function load(): Promise<void> {
        try {
            Object.assign(data, await fetchDashboard(dataUrl));
            updatedAt.value = new Date();
            error.value = null;
        } catch {
            error.value = 'Impossible de charger les données.';
        } finally {
            loading.value = false;
        }
    }

    async function assign(ticket: DashboardTicket): Promise<void> {
        busy[ticket.id] = true;
        try {
            const updated = await assignToMe(ticket, csrfToken);
            data.tickets = data.tickets.map((t) => (t.id === updated.id ? updated : t));
            await load();
        } catch {
            error.value = "L'assignation a échoué.";
        } finally {
            delete busy[ticket.id];
        }
    }

    let timer: ReturnType<typeof setInterval> | undefined;
    onMounted(() => {
        void load();
        timer = setInterval(load, refreshMs);
    });
    onUnmounted(() => clearInterval(timer));

    return { data, loading, error, updatedAt, busy, unassigned, urgent, load, assign };
}
