import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/login.js',
                'resources/js/datatable-general-config.js',
                'resources/js/register.js',
                'resources/js/updateprofile.js',
                'resources/js/delete-confirmation-modal.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],


});
