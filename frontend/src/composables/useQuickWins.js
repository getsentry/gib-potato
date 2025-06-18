import { useQuery } from '@tanstack/vue-query'
import api from '@/api'

export const useQuickWins = () => {
  return useQuery({
    queryKey: ['quickWins'],
    queryFn: async () => {
      const response = await api.get('quick-wins')
      return response.data
    },
  })
} 