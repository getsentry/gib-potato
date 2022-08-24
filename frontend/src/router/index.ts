import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/Home.vue'
import LoginView from '../views/Login.vue'
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
