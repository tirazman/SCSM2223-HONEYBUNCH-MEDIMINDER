<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Profile from '../views/Profile.vue'

const route = useRoute()

// Dynamically change the title based on the active route
const pageTitle = computed(() => {
  if (route.path === '/patient') return 'MediMinder Patient Portal'
  if (route.path === '/caregiver') return 'MediMinder Caregiver Portal'
  if (route.path === '/admin') return 'MediMinder Clinic Workspace'
  return 'MediMinder Hub' // Default for Login/Register
})
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
      <!-- Conditional rendering (dashboard-only) and logout confirmation
           both live inside Profile -->
      <Profile />
    </div>
  </header>
</template>