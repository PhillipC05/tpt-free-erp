import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./resources/js/test/setup.ts'],
        include: ['resources/js/test/**/*.test.ts'],
        coverage: {
            provider: 'v8',
            include: ['resources/js/**/*.{ts,vue}'],
            exclude: [
                'resources/js/test/**',
                'resources/js/env.d.ts',
                'resources/js/router/**',
                'resources/js/main.ts',
            ],
            reporter: ['text', 'html'],
        },
    },
});
