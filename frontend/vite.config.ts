import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import sentryVitePlugin from '@sentry/vite-plugin';

if (!process.env.SENTRY_AUTH_TOKEN) {
  console.log(
    'SENTRY_AUTH_TOKEN not set. Will not upload GibPotato release artifacts to Sentry.'
  );
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    sentryVitePlugin({
      include: 'dist',
      org: 'sentry',
      project: 'gibpotato-frontend',
      authToken: process.env.SENTRY_AUTH_TOKEN,
      dryRun: !process.env.SENTRY_AUTH_TOKEN,
      cleanArtifacts: true,
      stripCommonPrefix: true,
      rewrite: true,
      setCommits: {
        auto: true,
        ignoreEmpty: true,
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  envDir: 'config',
  build: {
    sourcemap: true,
  },
});
