import { createApp, markRaw } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';
import params from '../config/parameters';

import './assets/main.css';

/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core';

/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

/* add some free styles */
import { faSlack } from '@fortawesome/free-brands-svg-icons';

import * as Sentry from '@sentry/vue';

/* add each imported icon to the library */
library.add(faSlack);

const app = createApp(App);

Sentry.init({
  app,
  dsn: params.sentry.dsn,
});

const pinia = createPinia();
app.use(pinia);
app.use(router);

pinia.use(({ store }) => {
  store.router = markRaw(router);
});

app.component('FontAwesomeIcon', FontAwesomeIcon).mount('#app');
