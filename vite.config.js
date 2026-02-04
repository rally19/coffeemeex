import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [`resources/views/**/*`],
        }),
        tailwindcss(),
    ],
    server: { 
        cors: true, //changes are below
        // host: '0.0.0.0',
        // port: 5173,
        // strictPort: true,
        // hmr: {
        //     host: '192.168.1.11',
        // }, 
        // "npx concurrently -c \"#93c5fd,#c4b5fd,#fdba74\" \"php artisan serve --host=0.0.0.0 --port=8000\" \"php artisan queue:listen --tries=1\" \"npm run dev -- --host\" --names='server,queue,vite'"
        // "npx concurrently -c \"#93c5fd,#c4b5fd,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"npm run dev\" --names='server,queue,vite'"
    },
});