import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig({
    plugins: [vue(), symfonyPlugin()],
    build: {
        rollupOptions: {
            input: {
                app: './assets/app.ts',
                dashboard: './assets/dashboard.ts',
            },
        },
    },
});
