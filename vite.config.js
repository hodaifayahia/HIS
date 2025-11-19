import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5174,
        strictPort: true,
        watch: {
            usePolling: true, // Enable polling for Docker volumes
            interval: 100,    // Poll every 100ms
        },
        cors: {
            origin: ['http://10.47.0.26:9000', 'http://10.47.0.26:8080', 'http://localhost:8080', 'http://localhost:9000', 'http://127.0.0.1:8080', 'http://127.0.0.1:9000'],
            credentials: true
        },
        hmr: { 
            host: '10.47.0.26',
            port: 5174,
            protocol: 'ws',
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
            '@': '/resources/js',
            'pizzip': 'pizzip/dist/pizzip.js',
        },
        dedupe: ['pizzip'],
    },
    optimizeDeps: {
        include: ['pizzip'],
    },
    build: {
        target: 'esnext',
        sourcemap: true, // Enable source maps for debugging
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'axios'],
                    utils: ['lodash']
                }
            }
        }
    },
});

