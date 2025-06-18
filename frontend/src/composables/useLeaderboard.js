import { useQuery, queryOptions } from '@tanstack/vue-query'
import api from '@/api'

export const leaderboardQueryOptions = (filters) => queryOptions({
  queryKey: ['leaderboard', filters],
  queryFn: async () => {
    const response = await api.get('leaderboard', {
      params: filters.value
    })
    return response.data
  },
})

export const useLeaderboard = (filters) => {
  return useQuery(leaderboardQueryOptions(filters))
} 