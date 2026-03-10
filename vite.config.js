import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0', // Permite acesso externo ao container
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'trial-campaigns.docker.local', // HMR via domínio customizado
            port: 5173,
            protocol: 'ws',
        },
        watch: {
            usePolling: true, // Necessário para watch funcionar no Docker
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
