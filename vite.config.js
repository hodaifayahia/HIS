import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5174,
        strictPort: true,
        hmr: { 
            host: '10.47.0.26',
        },
        watch: {
            usePolling: true
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
        sourcemap: false,  // Set true only if you need to debug production
    },
});

