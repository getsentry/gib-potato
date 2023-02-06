import { createStore } from 'vuex'
import api from '@/api'

const store = createStore({
    state () {
        return {
            user: null,
            users: [],
            filter: {
                range: 'all',
                order: 'received',
            },
        }
    },
    getters: {
        user: state => state.user,
        users: state => state.users,
        filter: state => state.filter,
        range: state => state.filter.range,
        order: state => state.filter.order,
    },
    actions: {
        async getUsers({ commit, getters }) {
            try {
                const response = await api.get('users', {
                    params: {
                        ...getters.filter,
                    }
                })
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
        setRangeFilter({ commit }, range) {
            commit('SET_RANGE_FILTER', range)
        },
        setOrderFilter({ commit }, order) {
            commit('SET_ORDER_FILTER', order)
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
        SET_RANGE_FILTER(state, range) {
            state.filter.range = range
        },
        SET_ORDER_FILTER(state, order) {
            state.filter.order = order
        },
    },
})

export default store
