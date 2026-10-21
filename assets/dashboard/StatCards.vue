<script setup lang="ts">
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { TicketStatus } from './types';

defineProps<{
    counts: Record<TicketStatus, number>;
    unassigned: number;
    urgent: number;
}>();

const statuses: { key: TicketStatus; label: string; color: string }[] = [
    { key: 'open', label: 'Ouverts', color: 'text-blue-600' },
    { key: 'in_progress', label: 'En cours', color: 'text-amber-600' },
    { key: 'resolved', label: 'Résolus', color: 'text-emerald-600' },
    { key: 'closed', label: 'Fermés', color: 'text-muted-foreground' },
];
</script>

<template>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <Card v-for="status in statuses" :key="status.key" class="gap-1 py-4">
            <CardHeader class="px-4">
                <CardDescription>{{ status.label }}</CardDescription>
                <CardTitle class="text-3xl tabular-nums" :class="status.color">{{ counts[status.key] }}</CardTitle>
            </CardHeader>
        </Card>
        <Card class="gap-1 py-4">
            <CardHeader class="px-4">
                <CardDescription>Non assignés</CardDescription>
                <CardTitle class="text-3xl tabular-nums">{{ unassigned }}</CardTitle>
            </CardHeader>
        </Card>
        <Card class="gap-1 border-red-200 py-4">
            <CardHeader class="px-4">
                <CardDescription>Urgents actifs</CardDescription>
                <CardTitle class="text-3xl text-red-600 tabular-nums">{{ urgent }}</CardTitle>
            </CardHeader>
        </Card>
    </div>
</template>
