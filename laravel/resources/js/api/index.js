import axios from 'axios'

const api = {
    init() {
        axios.defaults.baseURL = '/api'
        axios.defaults.withCredentials = true;
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['Accept'] = 'application/json';
        
        // Set CSRF token from meta tag
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        }
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
