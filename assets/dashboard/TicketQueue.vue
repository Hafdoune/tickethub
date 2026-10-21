<script setup lang="ts">
import { computed, ref } from 'vue';
import { UserPlus } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { priorityTone, statusTone } from './tones';
import type { DashboardTicket, TicketPriority } from './types';

type Filter = 'all' | 'unassigned' | 'mine' | 'urgent';
type Sort = 'priority' | 'age';

const props = defineProps<{
    tickets: DashboardTicket[];
    me: number;
    busy: Record<number, boolean>;
}>();

const emit = defineEmits<{
    assign: [ticket: DashboardTicket];
}>();

const PRIORITY_WEIGHT: Record<TicketPriority, number> = { urgent: 4, high: 3, medium: 2, low: 1 };

const filters: { key: Filter; label: string }[] = [
    { key: 'all', label: 'Tous' },
    { key: 'unassigned', label: 'Non assignés' },
    { key: 'mine', label: 'Mes tickets' },
    { key: 'urgent', label: 'Urgents' },
];

const filter = ref<Filter>('unassigned');
const search = ref('');
const sortBy = ref<Sort>('priority');

function matchesFilter(ticket: DashboardTicket): boolean {
    switch (filter.value) {
        case 'unassigned':
            return ticket.assignee === null;
        case 'mine':
            return ticket.assignee?.id === props.me;
        case 'urgent':
            return ticket.priority === 'urgent';
        default:
            return true;
    }
}

function compare(a: DashboardTicket, b: DashboardTicket): number {
    const byAge = a.createdAt.localeCompare(b.createdAt);

    return sortBy.value === 'priority' ? PRIORITY_WEIGHT[b.priority] - PRIORITY_WEIGHT[a.priority] || byAge : byAge;
}

const visible = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.tickets
        .filter(matchesFilter)
        .filter((t) => !q || t.title.toLowerCase().includes(q) || (t.author ?? '').toLowerCase().includes(q))
        .sort(compare);
});

function age(iso: string): string {
    const hours = Math.floor((Date.now() - new Date(iso).getTime()) / 3_600_000);
    if (hours < 1) return "moins d'1 h";
    if (hours < 24) return `${hours} h`;

    return `${Math.floor(hours / 24)} j`;
}
</script>

<template>
    <Card class="gap-0 py-0">
        <CardHeader class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
            <Tabs v-model="filter">
                <TabsList>
                    <TabsTrigger v-for="f in filters" :key="f.key" :value="f.key">{{ f.label }}</TabsTrigger>
                </TabsList>
            </Tabs>
            <div class="ml-auto flex flex-wrap gap-2">
                <Input v-model="search" type="search" placeholder="Sujet ou client" class="h-9 w-56" />
                <Select v-model="sortBy">
                    <SelectTrigger class="w-44">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="priority">Tri : priorité</SelectItem>
                        <SelectItem value="age">Tri : ancienneté</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </CardHeader>
        <CardContent class="px-0">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="pl-4">#</TableHead>
                        <TableHead>Sujet</TableHead>
                        <TableHead>Client</TableHead>
                        <TableHead>Priorité</TableHead>
                        <TableHead>Statut</TableHead>
                        <TableHead>Attente</TableHead>
                        <TableHead class="pr-4 text-right"><span class="sr-only">Action</span></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="t in visible" :key="t.id" :data-ticket="t.id">
                        <TableCell class="pl-4 text-muted-foreground">{{ t.id }}</TableCell>
                        <TableCell class="whitespace-normal">
                            <a :href="t.url" class="font-medium hover:underline">{{ t.title }}</a>
                            <div class="text-xs text-muted-foreground">{{ t.category }}</div>
                        </TableCell>
                        <TableCell>{{ t.author }}</TableCell>
                        <TableCell><Badge variant="outline" :class="priorityTone[t.priority]">{{ t.priorityLabel }}</Badge></TableCell>
                        <TableCell><Badge variant="outline" :class="statusTone[t.status]">{{ t.statusLabel }}</Badge></TableCell>
                        <TableCell class="text-muted-foreground">{{ age(t.createdAt) }}</TableCell>
                        <TableCell class="pr-4 text-right">
                            <span v-if="t.assignee" class="text-sm text-muted-foreground">{{ t.assignee.id === me ? 'Moi' : t.assignee.name }}</span>
                            <Button v-else size="sm" variant="outline" :disabled="busy[t.id]" @click="emit('assign', t)">
                                <UserPlus />
                                {{ busy[t.id] ? '…' : "Me l'assigner" }}
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="!visible.length">
                        <TableCell colspan="7" class="py-10 text-center text-muted-foreground">Rien à traiter ici.</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </CardContent>
    </Card>
</template>
