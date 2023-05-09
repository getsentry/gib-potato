// @ts-check
import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { sentryVitePlugin } from '@sentry/vite-plugin';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    // sentryVitePlugin({
    //   include: [], // we're using debug ID upload instead (see _experiments.debugIdUpload)
    //   org: process.env.SENTRY_ORG,
    //   project: process.env.SENTRY_PROJECT,
    //   authToken: process.env.SENTRY_AUTH_TOKEN,
    //   dryRun: !process.env.SENTRY_AUTH_TOKEN,
    //   setCommits: {
    //     auto: true,
    //     ignoreEmpty: true,
    //   },
    //   _experiments: {
    //     injectBuildInformation: true,
    //     debugIdUpload: {
    //       include: './webroot/assets/**',
    //     },
    //   },
    // }),
  ],
  build: {
    sourcemap: true,
    emptyOutDir: false,
    outDir: './webroot/',
    manifest: true,
    minify: 'esbuild',
    rollupOptions: {
      input: './frontend/src/main.js',
    },
  },
  envDir: './frontend/config',
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./frontend/src', import.meta.url)),
    },
  },
});
