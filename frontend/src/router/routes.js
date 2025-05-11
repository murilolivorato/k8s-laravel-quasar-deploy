const routes = [
  {
    path: '/',
    component: () => import('../layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', component: () => import('../pages/IndexPage.vue') },
    ]
  },
  {
    path: '/',
    component: () => import('../layouts/AuthLayout.vue'),
    children: [
      {
        path: 'login',
        component: () => import('../pages/LoginPage.vue'),
        meta: { guest: true }
      },
      {
        path: 'register',
        component: () => import('../pages/RegisterPage.vue'),
        meta: { guest: true }
      }
    ]
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('../pages/ErrorNotFound.vue')
  }
]

export default routes
