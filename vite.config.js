import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/torneo/main.jsx'],
            refresh: true,
        }),
        react(),
    ],
    // Esto asegura que en producción los archivos se busquen en la carpeta pública
    base: './', 
});