import {defineConfig} from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    build: {
        manifest: 'manifest.json',
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
        },
    },
    server: {
        origin: 'http://localhost:5173',
        cors: true,
    },
    plugins: [
      tailwindcss(),
    ],
});