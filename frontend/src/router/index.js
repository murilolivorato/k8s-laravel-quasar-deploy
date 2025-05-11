import { route } from 'quasar/wrappers'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from 'stores/auth'
import routes from './routes'

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default route(function (/* { store, ssrContext } */) {
  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createWebHistory(process.env.VUE_ROUTER_BASE)
  })

  Router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
    const isGuest = to.matched.some(record => record.meta.guest)

    if (authStore.token && !authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        console.error('Error fetching user:', error)
      }
    }

    if (requiresAuth && !authStore.isAuthenticated) {
      next('/login')
    } else if (isGuest && authStore.isAuthenticated) {
      next('/')
    } else {
      next()
    }
  })

  return Router
})
