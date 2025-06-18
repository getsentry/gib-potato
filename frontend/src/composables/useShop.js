import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from '@/api'

export const useProducts = () => {
  return useQuery({
    queryKey: ['products'],
    queryFn: async () => {
      const response = await api.get('shop/products')
      return response.data
    },
  })
}

export const usePurchase = () => {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: async (purchaseData) => {
      const response = await api.post('shop/purchase', purchaseData)
      return response.data
    },
    onSuccess: () => {
      // Invalidate related queries to refetch updated data
      queryClient.invalidateQueries({ queryKey: ['user'] })
      queryClient.invalidateQueries({ queryKey: ['products'] })
      queryClient.invalidateQueries({ queryKey: ['collection'] })
    },
  })
} 