import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost'
        }
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: true,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            'pizzip': 'pizzip/dist/pizzip.js',
        },
        dedupe: ['pizzip'],
    },
    optimizeDeps: {
        include: ['pizzip'],
    },
    build: {
        target: 'esnext',
        sourcemap: false,
    },
});
