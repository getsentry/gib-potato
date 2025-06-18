import { useQuery, queryOptions } from '@tanstack/vue-query'
import api from '@/api'

export const quickWinsQueryOptions = () => queryOptions({
  queryKey: ['quickWins'],
  queryFn: async () => {
    const response = await api.get('quick-wins')
    return response.data
  },
})

export const useQuickWins = () => {
  return useQuery(quickWinsQueryOptions())
} 