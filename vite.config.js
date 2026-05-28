import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL ?? 'http://localhost:8000';

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.jsx',
                ],
                refresh: true,
            }),
            react(),
        ],
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
        server: {
            // Forward API, broadcasting, and storage requests to Laravel.
            // This means you open http://localhost:5173 and get HMR,
            // while /api/* calls hit Laravel transparently.
            proxy: {
                '/api': {
                    target: appUrl,
                    changeOrigin: true,
                    secure: false,
                },
                '/broadcasting': {
                    target: appUrl,
                    changeOrigin: true,
                    secure: false,
                },
                '/storage': {
                    target: appUrl,
                    changeOrigin: true,
                    secure: false,
                },
                '/sanctum': {
                    target: appUrl,
                    changeOrigin: true,
                    secure: false,
                },
            },
        },
    };
});
