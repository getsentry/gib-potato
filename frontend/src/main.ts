import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/main.css'

/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

/* add some free styles */
import { faSlack } from '@fortawesome/free-brands-svg-icons'

/* add each imported icon to the library */
library.add(faSlack)

const app = createApp(App).use(createPinia())

app.use(createPinia())
app.use(router)

app
    .component('font-awesome-icon', FontAwesomeIcon)
    .mount('#app')
