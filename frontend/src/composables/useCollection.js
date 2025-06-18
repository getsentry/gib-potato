import { useQuery, queryOptions } from '@tanstack/vue-query'
import api from '@/api'

export const collectionQueryOptions = () => queryOptions({
  queryKey: ['collection'],
  queryFn: async () => {
    const response = await api.get('collection')
    return response.data
  },
})

export const useCollection = () => {
  return useQuery(collectionQueryOptions())
} 