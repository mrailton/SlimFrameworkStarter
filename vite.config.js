import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

function slimVitePlugin() {
    return {
        name: 'slim-vite-plugin',
        configureServer(server) {
            server.httpServer?.once('listening', () => {
                const address = server.httpServer?.address();
                const host = typeof address === 'object' && address ? (address.address === '::' ? 'localhost' : address.address) : 'localhost';
                const port = typeof address === 'object' && address ? address.port : 5173;
                const protocol = server.config.server.https ? 'https' : 'http';
                const url = `${protocol}://${host}:${port}`;
                fs.writeFileSync(path.resolve(process.cwd(), 'public/hot'), url);
            });

            const cleanHot = () => {
                const hotPath = path.resolve(process.cwd(), 'public/hot');
                if (fs.existsSync(hotPath)) {
                    fs.unlinkSync(hotPath);
                }
            };

            process.on('exit', cleanHot);
            process.on('SIGINT', () => { cleanHot(); process.exit(); });
            process.on('SIGTERM', () => { cleanHot(); process.exit(); });
            process.on('SIGHUP', () => { cleanHot(); process.exit(); });
        },
    };
}

export default defineConfig({
    publicDir: false,
    plugins: [
        tailwindcss(),
        slimVitePlugin(),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
        },
    },
    server: {
        origin: 'http://localhost:5173',
        strictPort: true,
        cors: true,
    },
});
