import { createApp } from 'vue'

import vSelect from 'vue-select'

import * as Sentry from '@sentry/vue'

import App from './App.vue'
import router from './router'
import store from './store'
import api from './api'

import './assets/main.css'

(async () => {
    const app = createApp(App)

    let dataSet = document.querySelector('body').dataset

    Sentry.init({
        app,
        dsn: dataSet.sentryFrontendDsn,
        environment: dataSet.sentryEnvironment,
        release: dataSet.sentryRelease,
        integrations: [
            new Sentry.BrowserTracing({
                routingInstrumentation: Sentry.vueRouterInstrumentation(router),
                tracePropagationTargets: ["localhost", "gibpotato.app", /^\//],
                _experiments: {
                    enableInteractions: true,
                    // If you want automatic route transactions in react or similar
                    onStartRouteTransaction: Sentry.onProfilingStartRouteTransaction,
                },
            }),
            new Sentry.BrowserProfilingIntegration(),
            new Sentry.Replay(),
        ],
        tracesSampleRate: 1.0,
        profilesSampleRate: 1,
        replaysSessionSampleRate: 0.0,
        replaysOnErrorSampleRate: 1.0,
    })

    api.init()

    await store.dispatch('getLeaderboard')
    await store.dispatch('getUser')
    await store.dispatch('getUsers')
    await store.dispatch('getProducts')
    await store.dispatch('getCollection')

    app
        .use(router)
        .use(store)
    
    app.component('v-select', vSelect)

    app.mount('#app')
})()
