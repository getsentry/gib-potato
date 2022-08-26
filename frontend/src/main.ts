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

/* add each imported icon to the library */
library.add(faSlack)

const app = createApp(App).use(createPinia())

app.use(createPinia())
app.use(router)

Sentry.init({
  app,
  dsn: params.sentry.dsn,
});

app
    .component('font-awesome-icon', FontAwesomeIcon)
    .mount('#app')
