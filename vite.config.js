import { fileURLToPath, URL } from 'node:url';

import { defineConfig, loadEnv } from "vite";
import vue from '@vitejs/plugin-vue';
import { sentryVitePlugin } from '@sentry/vite-plugin';
import { codecovVitePlugin } from "@codecov/vite-plugin";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  // Set the third parameter to '' to load all env regardless of the `VITE_` prefix.
  const env = loadEnv(mode, process.cwd(), "")

  return {
    plugins: [
      vue(),
      sentryVitePlugin({
        org: "sentry",
        project: "gibpotato-frontend",
        include: './webroot/assets/**',
        authToken: env.SENTRY_AUTH_TOKEN,
        dryRun: env.SENTRY_AUTH_TOKEN,
        release: env.RELEASE,
        telemetry: false
      }),
      codecovVitePlugin({
        enableBundleAnalysis: process.env.CODECOV_TOKEN !== undefined,
        bundleName: "gib-potato",
        uploadToken: process.env.CODECOV_TOKEN,
      }),
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
    server: {
      cors: true,
    },
    envDir: './frontend/config',
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./frontend/src', import.meta.url)),
      },
    },
  }
});
