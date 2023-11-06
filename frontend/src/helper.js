const helper = {
    getRangeFilter() {
        return localStorage.getItem('filter.range') ?? 'all'
    },
    getOrderFilter() {
        return localStorage.getItem('filter.order') ?? 'all'
    },
};

export default helper;
