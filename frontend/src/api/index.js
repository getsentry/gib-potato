import axios from 'axios'

const api = {
    init() {
        axios.defaults.baseURL = '/api/'
        axios.defaults.xsrfCookieName = 'csrfToken';
        axios.defaults.xsrfHeaderName = 'X-Csrf-Token';
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