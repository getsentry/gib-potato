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
        tracesSampleRate: 1.0,
        profilesSampleRate: 1,
        replaysSessionSampleRate: 1.0,
        replaysOnErrorSampleRate: 1.0,
        integrations: [
            Sentry.browserTracingIntegration({
                enableInp: true,
                router: router,
                routeLabel: 'path',
                _experiments: {
                    enableInteractions: true,
                },
            }),
            Sentry.browserProfilingIntegration(),
            Sentry.replayIntegration({
                maskAllText: false,
                blockAllMedia: false,
            }),
            Sentry.feedbackIntegration({
                buttonLabel: 'Gib Feedback',
                submitButtonLabel: 'Send Feedback',
                formTitle: 'Gib Feedback 🥔',
                messagePlaceholder: 'What\'s not working? 😢',
                showEmail: false,
                showBranding: false,
                themeLight: {
                    foreground: '#18181b', // zinc-900
                    background: "#f4f4f5", //zinc-100
                    accentForeground: '#18181b', // zinc-900
                    accentBackground: '#fde68a', // amber-200
                    boxShadow: 'none',
                },
                themeDark: {
                    foreground: '#fafafa', // zinc-50
                    background: "#27272a", // zinc-800
                    accentForeground: '#18181b', // zinc-900
                    accentBackground: '#fde68a', // amber-200
                    boxShadow: 'none',
                },
            }),
        ],
    })

    Sentry.setUser({
        username: document.querySelector('body').dataset.username,
    });

    api.init()

    await Promise.all([
        store.dispatch('getLeaderboard'),
        store.dispatch('getUser'),
        store.dispatch('getUsers'),
        store.dispatch('getProducts'),
        store.dispatch('getCollection'),
        store.dispatch('getQuickWins'),
    ])

    app
        .use(router)
        .use(store)
    
    app.component('v-select', vSelect)

    app.mount('#app')
})()
