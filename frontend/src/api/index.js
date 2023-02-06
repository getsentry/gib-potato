import axios from 'axios'

const api = {
    init() {
        axios.defaults.baseURL = '/api/'
        axios.defaults.xsrfCookieName = 'csrfToken';
        axios.defaults.xsrfHeaderName = 'X-Csrf-Token';
    },

    get(resource, config) {
        return axios.get(`${resource}`, config)
    },

    post(resource, data, config) {
        return axios.post(`${resource}`, data, config)
    },

    put(resource, data, config) {
        return axios.put(`${resource}`, data, config)
    },

    patch(resource, data, config) {
        return axios.patch(`${resource}`, data, config)
    },

    delete(resource, config) {
        return axios.delete(resource, config)
    },
};

export default api;
