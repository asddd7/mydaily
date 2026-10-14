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
                'resources/js/page-loader.js',
                'resources/js/money-receipt-ocr.js',
            ],
            refresh: true,
        }),
    ],
});
