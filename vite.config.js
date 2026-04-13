import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite-plus';
import vue from '@vitejs/plugin-vue';
import { sentryVitePlugin } from '@sentry/vite-plugin';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    sentryVitePlugin({
      org: 'sentry',
      project: 'gibpotato-frontend',
      include: './webroot/assets/**',
      authToken: process.env.SENTRY_AUTH_TOKEN,
      dryRun: !process.env.SENTRY_AUTH_TOKEN,
      release: process.env.RELEASE,
      telemetry: false,
    }),
  ],
  build: {
    sourcemap: true,
    emptyOutDir: false,
    outDir: './webroot/',
    manifest: true,
    rolldownOptions: {
      input: './frontend/src/main.js',
    },
  },
  server: {
    cors: true,
  },
  envDir: './frontend/config',
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./frontend/src', import.meta.url)),
    },
  },
});
