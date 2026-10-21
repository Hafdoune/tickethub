export default {
    props: {
        counts: { type: Object, required: true },
        unassigned: { type: Number, required: true },
        urgent: { type: Number, required: true },
    },
    setup() {
        const cards = [
            { key: 'open', label: 'Ouverts', class: 'text-primary' },
            { key: 'in_progress', label: 'En cours', class: 'text-warning' },
            { key: 'resolved', label: 'Résolus', class: 'text-success' },
            { key: 'closed', label: 'Fermés', class: 'text-secondary' },
        ];

        return { cards };
    },
    template: `
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-2" v-for="card in cards" :key="card.key">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-secondary">{{ card.label }}</div>
                        <div class="display-6" :class="card.class">{{ counts[card.key] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card stat-card shadow-sm h-100 border-warning-subtle">
                    <div class="card-body">
                        <div class="small text-secondary">Non assignés</div>
                        <div class="display-6">{{ unassigned }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card stat-card shadow-sm h-100 border-danger-subtle">
                    <div class="card-body">
                        <div class="small text-secondary">Urgents actifs</div>
                        <div class="display-6 text-danger">{{ urgent }}</div>
                    </div>
                </div>
            </div>
        </div>
    `,
};
