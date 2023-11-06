const FILTER_RANGE = [
    'all',
    'year',
    'quarter',
    'month',
    'week',
]

const FILTER_ORDER = [
    'sent',
    'received',
]

const helper = {
    getRangeFilter() {
        const rangeFilter = localStorage.getItem('filter.range')

        if (FILTER_RANGE.includes(rangeFilter)) {
            return rangeFilter
        }

        return 'all'
    },
    getOrderFilter() {
        const orderFilter = localStorage.getItem('filter.order')

        if (FILTER_ORDER.includes(orderFilter)) {
            return orderFilter
        }

        return 'received'
    },
};

export default helper;
