import { defineConfig } from 'vite'
import laravel, { refreshPaths } from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5173, // يمكنك تغييره إذا كنت تريد بورت آخر
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', ],
            refresh: true,
            refresh: [
                ...refreshPaths,
                'app/Filament/**',
                'app/Forms/Components/**',
                'app/Livewire/**',
                'app/Infolists/Components/**',
                'app/Providers/Filament/**',
                'app/Tables/Columns/**',
            ],
        }),
        react(),
    ],
})
