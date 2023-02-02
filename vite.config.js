import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    sourcemap: true,
    emptyOutDir: false,
    outDir: './webroot/',
    manifest: true,
    minify: 'esbuild',
    rollupOptions: {
      input: './frontend/src/main.js'
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./frontend/src', import.meta.url))
    }
  }
})
