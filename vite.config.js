import { fileURLToPath, URL } from 'node:url';

import { defineConfig, loadEnv } from "vite";
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { sentryVitePlugin } from '@sentry/vite-plugin';

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  // Set the third parameter to '' to load all env regardless of the `VITE_` prefix.
  const env = loadEnv(mode, process.cwd(), "")

  return {
    plugins: [
      vue(),
      tailwindcss(),
      sentryVitePlugin({
        org: "sentry",
        project: "gibpotato-frontend",
        authToken: env.SENTRY_AUTH_TOKEN,
        disable: !env.SENTRY_AUTH_TOKEN,
        release: {
          name: env.RELEASE,
        },
        sourcemaps: {
          assets: './webroot/assets/**',
        },
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
  }
});
