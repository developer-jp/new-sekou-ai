import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/LoginPage.vue')
  },
  {
    path: '/',
    component: () => import('../layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('../pages/HomePage.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router

