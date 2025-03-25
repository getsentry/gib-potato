import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from '@/api'
import helper from '@/helper'

// Query keys
export const queryKeys = {
  leaderboard: 'leaderboard',
  user: 'user',
  users: 'users',
  products: 'products',
  collection: 'collection',
  quickWins: 'quickWins',
}

// Queries
export const useLeaderboard = (filter) => {
  return useQuery({
    queryKey: [queryKeys.leaderboard, filter],
    queryFn: async () => {
      const response = await api.get('leaderboard', { params: filter })
      return response.data
    }
  })
}

export const useUser = () => {
  return useQuery({
    queryKey: [queryKeys.user],
    queryFn: async () => {
      const response = await api.get('user')
      return response.data
    }
  })
}

export const useUsers = () => {
  return useQuery({
    queryKey: [queryKeys.users],
    queryFn: async () => {
      const response = await api.get('users')
      return response.data
    }
  })
}

export const useProducts = () => {
  return useQuery({
    queryKey: [queryKeys.products],
    queryFn: async () => {
      const response = await api.get('shop/products')
      return response.data
    }
  })
}

export const useCollection = () => {
  return useQuery({
    queryKey: [queryKeys.collection],
    queryFn: async () => {
      const response = await api.get('collection')
      return response.data
    }
  })
}

export const useQuickWins = () => {
  return useQuery({
    queryKey: [queryKeys.quickWins],
    queryFn: async () => {
      const response = await api.get('quick-wins')
      return response.data
    }
  })
}

// Mutations
export const useUpdateUserNotifications = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (user) => {
      const response = await api.patch('user', user)
      return response.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [queryKeys.user] })
    }
  })
}

// Filter state management
const defaultFilter = {
  range: helper.getRangeFilter(),
  order: helper.getOrderFilter()
}

export const useFilter = () => {
  const setRangeFilter = (range) => {
    defaultFilter.range = range
    localStorage.setItem('filter.range', range)
  }

  const setOrderFilter = (order) => {
    defaultFilter.order = order
    localStorage.setItem('filter.order', order)
  }

  return {
    filter: defaultFilter,
    setRangeFilter,
    setOrderFilter
  }
} 