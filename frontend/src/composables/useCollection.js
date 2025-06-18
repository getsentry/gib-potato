import { useQuery } from '@tanstack/vue-query'
import api from '@/api'

export const useCollection = () => {
  return useQuery({
    queryKey: ['collection'],
    queryFn: async () => {
      const response = await api.get('collection')
      return response.data
    },
  })
} 