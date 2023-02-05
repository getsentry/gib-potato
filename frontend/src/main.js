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
        integrations: [
            new BrowserTracing({
                routingInstrumentation: Sentry.vueRouterInstrumentation(router),
                tracePropagationTargets: ["localhost", "gitpoato.app", /^\//],
            }),
            // new Sentry.Replay(),
        ],
        tracesSampleRate: 1.0,
        // replaysSessionSampleRate: 0.0,
        // replaysOnErrorSampleRate: 1.0,
    })

    api.init()

    await store.dispatch('getUsers')
    await store.dispatch('getUser')

    app
        .use(router)
        .use(store)
    
    app.mount('#app')
})()
