import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    root: './src',
    base: './',
    build: {
        outDir: '../build',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                main: path.resolve(__dirname, 'src/index.html')
            }
        }
    },
    server: {
        port: 5173,
        strictPort: true
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src'),
            '@core': path.resolve(__dirname, './src/core'),
            '@components': path.resolve(__dirname, './src/components'),
            '@languages': path.resolve(__dirname, './src/languages'),
            '@utils': path.resolve(__dirname, './src/utils')
        }
    }
});
