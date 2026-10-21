<script setup lang="ts">
import { RefreshCw, TriangleAlert } from '@lucide/vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import CategoryBars from './CategoryBars.vue';
import StatCards from './StatCards.vue';
import TicketQueue from './TicketQueue.vue';
import { useDashboard } from './useDashboard';

const props = defineProps<{
    dataUrl: string;
    csrfToken: string;
}>();

const { data, loading, error, updatedAt, busy, unassigned, urgent, load, assign } = useDashboard(props.dataUrl, props.csrfToken);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Tableau de bord</h1>
                <p class="text-sm text-muted-foreground">File d'attente du support, actualisée toutes les 30 secondes.</p>
            </div>
            <div class="flex items-center gap-3 text-sm text-muted-foreground">
                <span v-if="updatedAt">Mis à jour à {{ updatedAt.toLocaleTimeString('fr-FR') }}</span>
                <Button variant="outline" size="sm" @click="load">
                    <RefreshCw />
                    Actualiser
                </Button>
            </div>
        </div>

        <Alert v-if="error" variant="destructive">
            <TriangleAlert />
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <div v-if="loading" class="py-16 text-center text-muted-foreground">Chargement…</div>

        <template v-else>
            <StatCards :counts="data.counts" :unassigned="unassigned" :urgent="urgent" />
            <div class="grid gap-6 lg:grid-cols-4">
                <div class="lg:col-span-3">
                    <TicketQueue :tickets="data.tickets" :me="data.me" :busy="busy" @assign="assign" />
                </div>
                <CategoryBars :items="data.byCategory" />
            </div>
        </template>
    </div>
</template>
