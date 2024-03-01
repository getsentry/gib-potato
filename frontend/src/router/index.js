import { createRouter, createWebHistory } from 'vue-router'
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
      component: () => import('@/views/Shop.vue')
    },
    {
      path: '/collection',
      name: 'collection',
      component: () => import('@/views/Collection.vue')
    },
    {
      path: '/quickwins',
      name: 'quickwins',
      component: () => import('@/views/Quickwins.vue')
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

export default router
