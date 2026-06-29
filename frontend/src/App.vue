<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useAuth } from './stores/auth'

const auth = useAuth();
const router = useRouter();
const route = useRoute();

// Dynamically change the title based on the active route
const pageTitle = computed(() => {
  if (route.path === '/patient') return 'MediMinder Patient Portal'
  if (route.path === '/caregiver') return 'MediMinder Caregiver Hub'
  if (route.path === '/admin') return 'MediMinder Clinic Workspace'
  return 'MediMinder Caregiver Hub' // Default for Login/Register
})

// Only show the logout button if we are NOT on the login or register screens
const showLogout = computed(() => {
  return route.path !== '/' && route.path !== '/register' && route.path !== '/login'
})

function handleLogout() {
  // For PR2: Just redirect to the login screen. 
  // For PR3: Member 2 will clear the Pinia auth store here.
  router.push('/login')
}
</script>

<template>
  <header class="navbar">
    <div class="brand-section">
      <span class="logo">🍯</span>
      
      <div class="text-group">
        <h1>{{ pageTitle }}</h1>
        <p class="tagline">Right pill, right time - for patients, caregivers, and clinics</p>
      </div>
    </div>
    
    <div class="action-section">
      <button v-if="showLogout" class="btn-logout" @click="handleLogout">Logout</button>
    </div>
  </header>

  <router-view></router-view>
</template>