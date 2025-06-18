import { ref, computed, watchEffect } from 'vue'
import helper from '@/helper'

// Create global filter state
const rangeFilter = ref(helper.getRangeFilter())
const orderFilter = ref(helper.getOrderFilter())

export const useFilters = () => {
  // Create computed property for filters object
  const filters = computed(() => ({
    range: rangeFilter.value,
    order: orderFilter.value
  }))

  // Save to localStorage when filters change
  watchEffect(() => {
    localStorage.setItem('filter.range', rangeFilter.value)
    localStorage.setItem('filter.order', orderFilter.value)
  })

  const setRangeFilter = (range) => {
    rangeFilter.value = range
  }

  const setOrderFilter = (order) => {
    orderFilter.value = order
  }

  return {
    filters,
    rangeFilter,
    orderFilter,
    setRangeFilter,
    setOrderFilter
  }
} 