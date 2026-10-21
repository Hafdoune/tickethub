<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import type { CategoryCount } from './types';

const props = defineProps<{ items: CategoryCount[] }>();

const max = computed(() => Math.max(1, ...props.items.map((item) => item.total)));
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Tickets actifs par catégorie</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div v-for="item in items" :key="item.name" class="space-y-1.5">
                <div class="flex justify-between text-sm">
                    <span>{{ item.name }}</span>
                    <span class="text-muted-foreground tabular-nums">{{ item.total }}</span>
                </div>
                <Progress :model-value="(item.total / max) * 100" class="h-1.5" />
            </div>
            <p v-if="!items.length" class="text-sm text-muted-foreground">Aucun ticket actif.</p>
        </CardContent>
    </Card>
</template>
