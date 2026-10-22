import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    envDir: '../backend',
    build: {
        emptyOutDir: true,
    },
    plugins: [
        laravel({
            input: ['css/app.css', 'js/app.js'],
            publicDirectory: '../backend/public',
            refresh: ['views/**/*.blade.php', '../backend/routes/**/*.php', '../backend/app/**/*.php'],
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
