import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import params from '../config/parameters'

import './assets/main.css'

/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

/* add some free styles */
import { faSlack } from '@fortawesome/free-brands-svg-icons'

import * as Sentry from "@sentry/vue";
import { BrowserTracing } from "@sentry/tracing";

/* add each imported icon to the library */
library.add(faSlack)

const app = createApp(App);

Sentry.init({
  app,
  dsn: params.sentry.dsn,
  // integrations: [
  //   new BrowserTracing({
  //     routingInstrumentation: Sentry.vueRouterInstrumentation(router),
  //     tracingOrigins: ["localhost", params.app.host, params.api.host, /^\//],
  //   }),
  // ],
  // // Set tracesSampleRate to 1.0 to capture 100%
  // // of transactions for performance monitoring.
  // // We recommend adjusting this value in production
  // tracesSampleRate: 1.0,
});

app.use(createPinia())
app.use(router)

app
    .component('font-awesome-icon', FontAwesomeIcon)
    .mount('#app')
