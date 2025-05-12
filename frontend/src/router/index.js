import { route } from 'quasar/wrappers'
import { createRouter, createWebHistory } from 'vue-router'
import routes from './routes'
import { api } from 'src/boot/axios'
import { Notify } from 'quasar'

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default route(function (/* { store } */) {
  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createWebHistory(process.env.VUE_APP_BASE_URL)
  })

  // Cache for auth check
  let authCheckCache = {
    promise: null,
    timestamp: 0,
    result: null
  }
  const CACHE_DURATION = 5000 // 5 seconds

  Router.beforeEach(async (to, from, next) => {
    console.log('Navigation:', { from: from.path, to: to.path })

    // Prevent infinite redirects
    if (to.path === from.path) {
      return next()
    }

    const publicPages = ['/auth/login', '/auth/register']
    const authRequired = !publicPages.includes(to.path)
    const token = localStorage.getItem('token')

    // If trying to access login page while authenticated
    if (to.path === '/auth/login' && token) {
      return next('/admin/dashboard')
    }

    // If trying to access protected page without token
    if (authRequired && !token) {
      return next('/auth/login')
    }

    // If no auth required, proceed
    if (!authRequired) {
      return next()
    }

    // Check if we have a valid cached result
    const now = Date.now()
    if (authCheckCache.result && (now - authCheckCache.timestamp) < CACHE_DURATION) {
      return next()
    }

    // If we have an ongoing auth check, wait for it
    if (authCheckCache.promise) {
      try {
        await authCheckCache.promise
        return next()
      } catch {
        console.log('Cached auth check failed, performing new check')
      }
    }

    try {
      // Set the token in the Authorization header
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`

      // Create a new auth check promise
      authCheckCache.promise = api.get('/api/check-auth')
        .then(response => {
          console.log('Auth check response:', response.data)
          authCheckCache.result = response.data
          authCheckCache.timestamp = Date.now()
          return response
        })
        .catch(error => {
          console.error('Auth check error:', error)
          authCheckCache.result = null
          throw error
        })
        .finally(() => {
          authCheckCache.promise = null
        })

      const response = await authCheckCache.promise
      
      if (!response.data.authenticated) {
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
        authCheckCache.result = null
        
        Notify.create({
          type: 'negative',
          message: 'Session expired. Please login again.',
          position: 'top'
        })
        
        return next('/auth/login')
      }

      // Check for admin routes
      if (to.path.startsWith('/admin')) {
        const userRoles = response.data.user.roles || []
        const isAdmin = userRoles.some(role => role.slug === 'admin')
        
        console.log('Admin check:', {
          path: to.path,
          userRoles: userRoles,
          isAdmin: isAdmin,
          roleSlugs: userRoles.map(role => role.slug)
        })

        if (!isAdmin) {
          console.log('Access denied: User is not an admin')
          Notify.create({
            type: 'negative',
            message: 'Access denied. Admin privileges required.',
            position: 'top'
          })
          return next('/auth/login')
        }
      }

      // All checks passed
      console.log('Navigation allowed to:', to.path)
      return next()
    } catch (error) {
      console.error('Auth check error:', error)
      
      localStorage.removeItem('token')
      delete api.defaults.headers.common['Authorization']
      authCheckCache.result = null
      
      Notify.create({
        type: 'negative',
        message: 'Authentication error. Please login again.',
        position: 'top'
      })
      
      return next('/auth/login')
    }
  })

  return Router
})
