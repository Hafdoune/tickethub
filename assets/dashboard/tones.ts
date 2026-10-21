import type { TicketPriority, TicketStatus } from './types';

export const statusTone: Record<TicketStatus, string> = {
    open: 'border-blue-200 bg-blue-50 text-blue-700',
    in_progress: 'border-amber-200 bg-amber-50 text-amber-700',
    resolved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    closed: 'border-border bg-muted text-muted-foreground',
};

export const priorityTone: Record<TicketPriority, string> = {
    low: 'border-border bg-background text-muted-foreground',
    medium: 'border-sky-200 bg-sky-50 text-sky-700',
    high: 'border-orange-200 bg-orange-50 text-orange-700',
    urgent: 'border-red-200 bg-red-50 text-red-700',
};
