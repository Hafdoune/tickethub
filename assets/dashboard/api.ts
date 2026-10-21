import type { DashboardData, DashboardTicket } from './types';

async function request<T>(url: string, init: RequestInit = {}): Promise<T> {
    const response = await fetch(url, {
        ...init,
        headers: { Accept: 'application/json', ...init.headers },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status} on ${url}`);
    }

    return response.json() as Promise<T>;
}

export function fetchDashboard(url: string): Promise<DashboardData> {
    return request<DashboardData>(url);
}

export function assignToMe(ticket: DashboardTicket, csrfToken: string): Promise<DashboardTicket> {
    return request<DashboardTicket>(ticket.assignUrl, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
    });
}
