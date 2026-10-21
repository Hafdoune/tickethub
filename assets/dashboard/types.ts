export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';

export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface DashboardTicket {
    id: number;
    title: string;
    status: TicketStatus;
    statusLabel: string;
    priority: TicketPriority;
    priorityLabel: string;
    category: string | null;
    author: string | null;
    assignee: { id: number; name: string } | null;
    createdAt: string;
    url: string;
    assignUrl: string;
}

export interface CategoryCount {
    name: string;
    total: number;
}

export interface DashboardData {
    me: number;
    counts: Record<TicketStatus, number>;
    byCategory: CategoryCount[];
    tickets: DashboardTicket[];
}
