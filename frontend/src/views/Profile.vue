<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuth()

const user = computed(() => ({
  name: auth.user?.name ?? 'Guest',
  role: auth.user?.role ?? ''
}))

// Controls dropdown visibility
const isOpen = ref(false)

// Toggles between 'default' (View Profile / Logout) and 'profile' (Dashboard / Logout)
const menuView = ref('default')

// Only render this component on dashboard-type routes, never on Login/Register
const isDashboard = computed(() => {
  return !['/', '/login', '/register'].includes(route.path)
})

/**
 * Fallback role -> dashboard route map, used only if we don't have a
 * remembered route to return to (e.g. user lands on /profile directly,
 * such as a page refresh or a shared link). Adjust keys to match the
 * exact role strings your auth store returns.
 */
const ROLE_DASHBOARD_ROUTES = {
  patient: '/patient',
  caregiver: '/caregiver',
  admin: '/admin'
}

// Remembers the dashboard route the user was on right before opening
// their profile, so "Dashboard" always sends them back to where they came from
const lastDashboardRoute = ref(null)

function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function closeDropdown() {
  isOpen.value = false
}

function toggleView(view) {
  menuView.value = view
}

function goToProfile() {
  // Remember where we were so "Dashboard" can return here later
  if (route.path !== '/profile') {
    lastDashboardRoute.value = route.path
  }

  toggleView('profile')
  router.push('/profile')
  closeDropdown()
}

function goToDashboard() {
  toggleView('default')

  const fallback = ROLE_DASHBOARD_ROUTES[auth.user?.roleKey ?? auth.user?.role] ?? '/login'
  router.push(lastDashboardRoute.value ?? fallback)

  closeDropdown()
}

function handleLogout() {
  const confirmed = window.confirm('Are you sure you want to log out?')
  if (!confirmed) return

  menuView.value = 'default'
  closeDropdown()

  // Hook up to auth.logout() once the store supports it (PR3)
  auth.logout?.()
  router.push('/login')
}

// Close the dropdown when clicking outside of it
function handleClickOutside(event) {
  if (!event.target.closest('.profile-dropdown')) {
    closeDropdown()
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div
    v-if="isDashboard"
    class="profile-dropdown"
  >
    <button
      class="profile-trigger"
      type="button"
      :aria-expanded="isOpen"
      @click="toggleDropdown"
    >
      <span class="profile-avatar">
        <svg viewBox="0 0 24 24" fill="currentColor" class="avatar-icon">
          <path
            d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.5c-3.3 0-9.8 1.6-9.8 4.9v2.4h19.6v-2.4c0-3.3-6.5-4.9-9.8-4.9z"
          />
        </svg>
      </span>

      <span class="profile-text">
        <span class="profile-name">{{ user.name }}</span>
        <span class="profile-role">{{ user.role }}</span>
      </span>

      <svg
        class="profile-chevron"
        :class="{ 'is-open': isOpen }"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <path d="M6 9l6 6 6-6" />
      </svg>
    </button>

    <transition name="dropdown-fade">
      <div v-if="isOpen" class="profile-menu" role="menu">
        <template v-if="menuView === 'default'">
          <button class="profile-menu-item" role="menuitem" @click="goToProfile">
            View Profile
          </button>
          <button
            class="profile-menu-item profile-menu-item--danger"
            role="menuitem"
            @click="handleLogout"
          >
            Logout
          </button>
        </template>

        <template v-else>
          <button class="profile-menu-item" role="menuitem" @click="goToDashboard">
            Dashboard
          </button>
          <button
            class="profile-menu-item profile-menu-item--danger"
            role="menuitem"
            @click="handleLogout"
          >
            Logout
          </button>
        </template>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/*
  Modular theme tokens — override these from a parent component or your
  global stylesheet to match different sections of the app without
  touching the markup or logic above.
*/

</style>