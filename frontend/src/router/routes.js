const routes = [
  {
    path: '/',
    redirect: '/auth/login'
  },
  {
    path: '/auth',
    component: () => import('layouts/AuthLayout.vue'),
    children: [
      {
        path: 'login',
        component: () => import('pages/LoginPage.vue')
      },
      {
        path: 'register',
        component: () => import('pages/RegisterPage.vue')
      }
    ]
  },
  {
    path: '/admin',
    component: () => import('layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard'
      },
      {
        path: 'dashboard',
        component: () => import('pages/admin/DashboardPage.vue')
      },
      {
        path: 'users',
        component: () => import('pages/admin/UsersPage.vue')
      },
      {
        path: 'roles',
        component: () => import('pages/admin/RolesPage.vue')
      }
    ]
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('../pages/ErrorNotFound.vue')
  }
]

export default routes
