import { createApp } from 'vue'

import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'

import * as Sentry from '@sentry/vue'

import App from './App.vue'
import router from './router'
import store from './store'
import api from './api'

import '../css/vue-app.css'

// Initialize the Vue app
(async () => {
    const app = createApp(App)

    // Get data attributes from body element
    const dataSet = document.querySelector('body').dataset

    // Initialize Sentry if DSN is provided
    if (dataSet.sentryFrontendDsn) {
        Sentry.init({
            app,
            dsn: dataSet.sentryFrontendDsn,
            environment: dataSet.sentryEnvironment || 'production',
            release: dataSet.sentryRelease || undefined,
            tracesSampleRate: 1.0,
            profilesSampleRate: 1.0,
            replaysSessionSampleRate: 1.0,
            replaysOnErrorSampleRate: 1.0,
            enableLogs: true,
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
                        background: "#f4f4f5", // zinc-100
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
                Sentry.consoleLoggingIntegration({ levels: ['error'] }),
            ],
        })

        // Set user context
        if (dataSet.username) {
            Sentry.setUser({
                username: dataSet.username,
            });
        }
    }

    // Initialize API client
    api.init()

    // Load initial data
    try {
        await Promise.all([
            store.dispatch('getLeaderboard'),
            store.dispatch('getUser'),
            store.dispatch('getUsers'),
            store.dispatch('getProducts'),
            store.dispatch('getCollection'),
            store.dispatch('getQuickWins'),
        ])
    } catch (error) {
        console.error('Failed to load initial data:', error)
    }

    // Register global components
    app.component('v-select', vSelect)

    // Use plugins
    app.use(router)
    app.use(store)
    
    // Mount the app
    app.mount('#app')
})()