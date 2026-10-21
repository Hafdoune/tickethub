import { computed } from 'vue/dist/vue.esm-browser.prod.js';

export default {
    props: {
        items: { type: Array, required: true },
    },
    setup(props) {
        const max = computed(() => Math.max(1, ...props.items.map((item) => item.total)));

        return { max };
    },
    template: `
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Tickets actifs par catégorie</h2>
                <div v-for="item in items" :key="item.name" class="mb-2">
                    <div class="d-flex justify-content-between small">
                        <span>{{ item.name }}</span>
                        <span class="text-secondary">{{ item.total }}</span>
                    </div>
                    <div class="progress" style="height: 6px">
                        <div class="progress-bar bg-dark" :style="{ width: (item.total / max * 100) + '%' }"></div>
                    </div>
                </div>
                <p v-if="!items.length" class="text-secondary small mb-0">Aucun ticket actif.</p>
            </div>
        </div>
    `,
};
