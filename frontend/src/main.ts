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
  integrations: [
    new BrowserTracing({
      tracingOrigins: ["localhost", "gipotato.eu.ngrok.io", /^\//],
    }),
  ],
  tracesSampleRate: 1.0,
});

const pinia = createPinia();
app.use(pinia);
app.use(router);

pinia.use(({ store }) => {
  store.router = markRaw(router);
});

app.mount('#app');
