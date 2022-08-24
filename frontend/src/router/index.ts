import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/Home.vue'
import LoginView from '../views/Login.vue'
import Error from '../views/errors/UnexpectedError.vue'
import NotFound from '../views/errors/NotFound.vue'
import { useAccountStore } from '../stores/account'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView
    },
    {
      path: '/error',
      name: 'error',
      component: Error
    },
    {
      path: '/not_found',
      name: 'notFound',
      component: NotFound
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'notFound',
      component: NotFound
    },
  ]
})

router.beforeEach(async (to, from) => {
  const store = useAccountStore()
  const account = await store.account.execute()
  if (
    // make sure the user is authenticated
    !account &&
    // // ❗️ Avoid an infinite redirect
    to.name !== 'login'
  ) {
    // redirect the user to the login page
    return { name: 'login' }
  }

  if (
    account &&
    to.name === 'login'
  ) {
    // redirect the user to the home page
    return { name: 'home' }
  }
})

export default router
