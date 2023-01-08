import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import basicSsl from '@vitejs/plugin-basic-ssl'
import sentryVitePlugin from '@sentry/vite-plugin';
import path from 'path';

if (!process.env.SENTRY_AUTH_TOKEN) {
  console.log(
    'SENTRY_AUTH_TOKEN not set. Will not upload GibPotato release artifacts to Sentry.'
  );
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    basicSsl(),
    vue(),
    sentryVitePlugin({
      include: 'dist',
      org: 'sentry',
      project: 'gibpotato-frontend',
      authToken: process.env.SENTRY_AUTH_TOKEN,
      dryRun: !process.env.SENTRY_AUTH_TOKEN,
      cleanArtifacts: true,
      setCommits: {
        auto: true,
        ignoreEmpty: true,
      },
    }),
  ],
  server: {
    https: true,
  },
  build: {
    sourcemap: true,
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
