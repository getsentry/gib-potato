import { createApp, markRaw } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';
import params from '../config/parameters';

import './assets/main.css';

import * as Sentry from '@sentry/vue';
import { BrowserTracing } from "@sentry/tracing";

const app = createApp(App);

Sentry.init({
  app,
  dsn: params.sentry.dsn,
  environment: params.sentry.environment,
  release: params.sentry.version,
  integrations: [
    new BrowserTracing({
      tracePropagationTargets: [
        "localhost",
        "gipotato.eu.ngrok.io",
        "gipotato.app",
      ],
    }),
    // new Sentry.Replay({
    //   maskAllText: false,
    //   blockAllMedia: false,
    // }),
  ],
  tracesSampleRate: 1.0,
  replaysSessionSampleRate: 1.0,
  replaysOnErrorSampleRate: 1.0,
});

const pinia = createPinia();
app.use(pinia);
app.use(router);

pinia.use(({ store }) => {
  store.router = markRaw(router);
});

app.mount('#app');
