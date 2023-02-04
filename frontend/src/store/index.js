import { createStore } from 'vuex'
import api from '@/api'

const store = createStore({
    state () {
        return {
            user: null,
            users: [],
        }
    },
    getters: {
        user: state => state.user,
        users: state => state.users,
    },
    actions: {
        async getUsers({ commit }) {
            try {
                const response = await api.get('users')
                commit('SET_USERS', response.data)
            } catch (error) {
                console.log(error)
            }
        },
        async getUser({ commit }) {
            try {
                const response = await api.get('user')
                commit('SET_USER', response.data)
            } catch (error) {
                console.log(error)
            }
        },
        async toggleSentNotifications({ commit, getters }) {
            commit('TOGGLE_SENT_NOTIFICATIONS')
            try {
                const response = await api.patch('user', getters.user)
            } catch (error) {
                console.log(error)
            }
        },
        async toggleReceivedNotifications({ commit, getters }) {
            commit('TOGGLE_RECEIVED_NOTIFICATIONS')
            try {
                const response = await api.patch('user', getters.user)
            } catch (error) {
                console.log(error)
            }
        },
    },
    mutations: {
        SET_USERS(state, users) {
            state.users = users
        },
        SET_USER(state, user) {
            state.user = user
        },
        TOGGLE_SENT_NOTIFICATIONS(state) {
            state.user.notifications.sent = !state.user.notifications.sent
        },
        TOGGLE_RECEIVED_NOTIFICATIONS(state) {
            state.user.notifications.received = !state.user.notifications.received
        },
    },
})

export default store