import { useQuery } from '@tanstack/vue-query'
import api from '@/api'

export const useLeaderboard = (filters) => {
  return useQuery({
    queryKey: ['leaderboard', filters],
    queryFn: async () => {
      const response = await api.get('leaderboard', {
        params: filters.value
      })
      return response.data
    },
  })
} 