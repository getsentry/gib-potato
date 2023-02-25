import { createApp } from 'vue'

import * as Sentry from '@sentry/vue'
import { BrowserTracing } from "@sentry/tracing";

import App from './App.vue'
import router from './router'
import store from './store'
import api from './api'

import './assets/main.css'

(async () => {
    const app = createApp(App)

    Sentry.init({
        app,
        dsn: import.meta.env.VITE_APP_SENTRY_DSN,
        environment: import.meta.env.VITE_APP_ENVIRONMENT,
        release: import.meta.env.VITE_APP_VERSION,
        integrations: [
            new BrowserTracing({
                routingInstrumentation: Sentry.vueRouterInstrumentation(router),
                tracePropagationTargets: ["localhost", "gibpotato.app", /^\//],
            }),
            // new Sentry.Replay(),
        ],
        tracesSampleRate: 1.0,
        // replaysSessionSampleRate: 0.0,
        // replaysOnErrorSampleRate: 1.0,
    })

    api.init()

    await store.dispatch('getLeaderboard')
    await store.dispatch('getUser')
    await store.dispatch('getUsers')
    await store.dispatch('getProducts')

    app
        .use(router)
        .use(store)
    
    app.mount('#app')
})()
