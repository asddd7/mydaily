import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/style.css',
                'resources/css/app.css',
                'resources/css/tasks.css',
                'resources/css/money.css',
                'resources/css/notes.css',
                'resources/css/database-structure.css',
                'resources/css/files.css',
                'resources/css/auth.css',
                'resources/css/clock.css',
                'resources/js/page-loader.js',
                'resources/js/push-notifications.js',
                'resources/js/pwa-install.js',
                'resources/js/money-receipt-ocr.js',
                'resources/js/money-transaction-items.js',
                'resources/js/file-manager.js',
            ],
            refresh: true,
        }),
    ],
});
