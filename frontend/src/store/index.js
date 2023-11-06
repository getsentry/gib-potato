import { createStore } from 'vuex'
import api from '@/api'
import helper from '@/helper'

const store = createStore({
    state () {
        return {
            leaderboard: [],
            user: null,
            users: [],
            products: [],
            collection: [],
            filter: {
                range: helper.getRangeFilter(),
                order: helper.getOrderFilter(),
            },
        }
    },
    getters: {
        leaderboard: state => state.leaderboard,
        user: state => state.user,
        users: state => state.users,
        products: state => state.products,
        collection: state => state.collection,
        filter: state => state.filter,
        range: state => state.filter.range,
        order: state => state.filter.order,
    },
    actions: {
        async getLeaderboard({ commit, getters }) {
            try {
                const response = await api.get('leaderboard', {
                    params: {
                        ...getters.filter,
                    }
                })
                commit('SET_LEADERBOARD', response.data)
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
        async getUsers({ commit }) {
            try {
                const response = await api.get('users')
                commit('SET_USERS', response.data)
            } catch (error) {
                console.log(error)
            }
        },
        async getProducts({ commit }) {
            try {
                const response = await api.get('shop/products')
                commit('SET_PRODUCTS', response.data)
            } catch (error) {
                console.log(error)
            }
        },
        async getCollection({ commit }) {
            try {
                const response = await api.get('collection')
                commit('SET_COLLECTION', response.data)
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
        SET_LEADERBOARD(state, leaderboard) {
            state.leaderboard = leaderboard
        },
        SET_USER(state, user) {
            state.user = user
        },
        SET_USERS(state, users) {
            state.users = users
        },
        SET_PRODUCTS(state, products) {
            state.products = products
        },
        SET_COLLECTION(state, collection) {
            state.collection = collection
        },
        TOGGLE_SENT_NOTIFICATIONS(state) {
            state.user.notifications.sent = !state.user.notifications.sent
        },
        TOGGLE_RECEIVED_NOTIFICATIONS(state) {
            state.user.notifications.received = !state.user.notifications.received
        },
        SET_RANGE_FILTER(state, range) {
            state.filter.range = range
            localStorage.setItem('filter.range', state.filter.range)
        },
        SET_ORDER_FILTER(state, order) {
            state.filter.order = order
            localStorage.setItem('filter.order', state.filter.order)
        },
    },
})

export default store
