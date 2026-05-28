import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

// Standalone Vite config used only for Vercel deployment.
// Does NOT use laravel-vite-plugin — outputs a self-contained dist/ folder.
export default defineConfig({
    root: '.',
    plugins: [react()],
    resolve: {
        alias: {
            '@': path.resolve('./resources/js'),
        },
    },
    build: {
        outDir:    'dist',
        emptyOutDir: true,
    },
});
