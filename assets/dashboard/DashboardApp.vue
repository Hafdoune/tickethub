<script setup lang="ts">
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
        <StatCards :counts="data.counts" :unassigned="unassigned" :urgent="urgent" />
        <div class="row g-4">
            <div class="col-lg-9">
                <TicketQueue :tickets="data.tickets" :me="data.me" :busy="busy" @assign="assign" />
            </div>
            <div class="col-lg-3">
                <CategoryBars :items="data.byCategory" />
            </div>
        </div>
    </template>
</template>
