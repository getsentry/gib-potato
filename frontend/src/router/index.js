import { createRouter, createWebHistory } from 'vue-router'
import { queryClient } from '@/queryClient'
import { 
  userQueryOptions, 
  productsQueryOptions, 
  collectionQueryOptions,
  quickWinsQueryOptions 
} from '@/composables'
import Home from '@/views/Home.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: Home
    },
    {
      path: '/shop',
      name: 'shop',
      component: () => import('@/views/Shop.vue'),
      // Prefetch shop data before entering the route
      beforeEnter: async () => {
        await queryClient.prefetchQuery(productsQueryOptions())
      }
    },
    {
      path: '/collection',
      name: 'collection',
      component: () => import('@/views/Collection.vue'),
      // Prefetch collection data before entering the route
      beforeEnter: async () => {
        await queryClient.prefetchQuery(collectionQueryOptions())
      }
    },
    {
      path: '/quick-wins',
      name: 'quick-wins',
      component: () => import('@/views/QuickWins.vue'),
      // Prefetch quick wins data before entering the route
      beforeEnter: async () => {
        await queryClient.prefetchQuery(quickWinsQueryOptions())
      }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/views/Profile.vue')
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/Settings.vue')
    }
  ]
})

// Prefetch critical user data on every route change
router.beforeEach(async () => {
  // Only prefetch if not already cached
  const userData = queryClient.getQueryData(userQueryOptions().queryKey)
  if (!userData) {
    await queryClient.prefetchQuery(userQueryOptions())
  }
})

export default router
