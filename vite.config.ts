import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
      emptyOutDir: false,
      outDir: './webroot/',
      manifest: true,
      minify: 'esbuild',
      rollupOptions: {
          input: './frontend/src/main.ts'
      },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './frontend/src'),
    },
  },
  envDir: './frontend/config',
});
