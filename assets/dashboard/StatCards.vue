<script setup lang="ts">
import type { TicketStatus } from './types';

defineProps<{
    counts: Record<TicketStatus, number>;
    unassigned: number;
    urgent: number;
}>();

const cards: { key: TicketStatus; label: string; color: string }[] = [
    { key: 'open', label: 'Ouverts', color: 'text-primary' },
    { key: 'in_progress', label: 'En cours', color: 'text-warning' },
    { key: 'resolved', label: 'Résolus', color: 'text-success' },
    { key: 'closed', label: 'Fermés', color: 'text-secondary' },
];
</script>

<template>
    <div class="row g-3 mb-4">
        <div v-for="card in cards" :key="card.key" class="col-6 col-lg-2">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-secondary">{{ card.label }}</div>
                    <div class="display-6" :class="card.color">{{ counts[card.key] }}</div>
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
</template>
