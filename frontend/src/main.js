import { createApp } from 'vue'
import App from './App.vue'
import * as Sentry from "@sentry/vue";
import { BrowserTracing } from "@sentry/tracing";

import './assets/main.css'

const app = createApp(App);

Sentry.init({
  app,
  dsn: "https://b3910b9bca4b4bdc878871f37f93767a@o447951.ingest.sentry.io/5429219",
  integrations: [
    new BrowserTracing({
      tracingOrigins: ["localhost"],
    }),
  ],
  // Set tracesSampleRate to 1.0 to capture 100%
  // of transactions for performance monitoring.
  // We recommend adjusting this value in production
  tracesSampleRate: 1.0,
});

app.mount('#app')
