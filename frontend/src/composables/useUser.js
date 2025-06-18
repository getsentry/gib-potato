import { useQuery, useMutation, useQueryClient, queryOptions } from '@tanstack/vue-query'
import api from '@/api'

// Define query options for reusability
export const userQueryOptions = () => queryOptions({
  queryKey: ['user'],
  queryFn: async () => {
    const response = await api.get('user')
    return response.data
  },
})

export const usersQueryOptions = () => queryOptions({
  queryKey: ['users'],
  queryFn: async () => {
    const response = await api.get('users')
    return response.data
  },
})

export const userProfileQueryOptions = () => queryOptions({
  queryKey: ['userProfile'],
  queryFn: async () => {
    const response = await api.get('user/profile')
    return response.data
  },
})

// Composables that use the query options
export const useUser = () => {
  return useQuery(userQueryOptions())
}

export const useUsers = () => {
  return useQuery(usersQueryOptions())
}

export const useUserProfile = () => {
  return useQuery(userProfileQueryOptions())
}

export const useUpdateUser = () => {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: async (userData) => {
      const response = await api.patch('user', userData)
      return response.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] })
    },
  })
} 