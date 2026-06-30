import { createRouter, createWebHistory } from 'vue-router'

import Register from '../views/Register.vue'
import Login from '../views/Login.vue'
import Admin from '../views/Admin.vue'      
import Patient from '../views/Patient.vue'   
import Caregiver from '../views/Caregiver.vue'


const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/register', component: Register },
    { path: '/login', component: Login },
    { path: '/patient', component: Patient },
    { path: '/caregiver', component: Caregiver },
    { path: '/admin', component: Admin },
    {
      path: '/adherence',
      name: 'adherence',
      component: () => import('../views/AdherenceDashboard.vue'),
      meta: { requiresAuth: true } 
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true }
    }
]})

export default router