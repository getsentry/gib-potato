import { createApp } from 'vue'

import App from './App.vue'
import router from './router'
import store from './store'
import api from './api'

import './assets/main.css'

(async () => {
    api.init()

    await store.dispatch('getUsers')
    await store.dispatch('getUser')

    const app = createApp(App)

    app
        .use(router)
        .use(store)
    
    app.mount('#app')
})()
