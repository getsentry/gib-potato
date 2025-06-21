import { useQueryClient } from '@tanstack/vue-query'
import { userQueryOptions, usersQueryOptions } from './useUser'
import { productsQueryOptions } from './useShop'
import { collectionQueryOptions } from './useCollection'
import { quickWinsQueryOptions } from './useQuickWins'

export const usePrefetch = () => {
  const queryClient = useQueryClient()

  // Prefetch a single query
  const prefetchUser = () => {
    return queryClient.prefetchQuery(userQueryOptions())
  }

  // Prefetch multiple queries at once
  const prefetchShopData = () => {
    return Promise.all([
      queryClient.prefetchQuery(userQueryOptions()),
      queryClient.prefetchQuery(usersQueryOptions()),
      queryClient.prefetchQuery(productsQueryOptions()),
    ])
  }

  // Prefetch all app data
  const prefetchAllData = () => {
    return Promise.all([
      queryClient.prefetchQuery(userQueryOptions()),
      queryClient.prefetchQuery(usersQueryOptions()),
      queryClient.prefetchQuery(productsQueryOptions()),
      queryClient.prefetchQuery(collectionQueryOptions()),
      queryClient.prefetchQuery(quickWinsQueryOptions()),
    ])
  }

  return {
    prefetchUser,
    prefetchShopData,
    prefetchAllData,
  }
} 