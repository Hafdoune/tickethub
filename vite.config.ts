import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig({
    plugins: [vue(), tailwindcss(), symfonyPlugin()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./assets', import.meta.url)),
        },
    },
    build: {
        rollupOptions: {
            input: {
                app: './assets/app.ts',
                dashboard: './assets/dashboard.ts',
            },
        },
    },
});
