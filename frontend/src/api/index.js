import axios from 'axios'
import Cookies from 'js-cookie'

const api = {
    init() {
        axios.defaults.baseURL = '/api/'
        axios.defaults.headers.common['Content-Type'] = 'application/json'
        axios.defaults.headers.common['Accept'] = 'application/json'
        axios.defaults.headers.common['X-CSRF-Token'] = Cookies.get('csrfToken')
    },

    get(resource, id = '', options = {}) {
        return axios.get(`${resource}/${id}`, options)
    },

    post(resource, params) {
        return axios.post(`${resource}`, params)
    },

    put(resource, params) {
        return axios.put(`${resource}`, params)
    },

    patch(resource, params) {
        return axios.patch(`${resource}`, params)
    },

    delete(resource, params) {
        return axios.delete(resource, params)
    },
};

export default api;