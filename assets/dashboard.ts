import { createApp } from 'vue';
import DashboardApp from './dashboard/DashboardApp.vue';

const root = document.getElementById('dashboard');

if (root) {
    const { url = '', csrf = '' } = root.dataset;

    createApp(DashboardApp, { dataUrl: url, csrfToken: csrf }).mount(root);
}
