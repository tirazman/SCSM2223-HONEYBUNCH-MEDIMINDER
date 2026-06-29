import { defineStore } from 'pinia'

export const useAuth = defineStore('auth', {
  // 1. State: The global reactive variables for user auth tracking
  state: () => ({
    user: null,         // Holds info like { name: 'Athierah', role: 'patient' }
    token: null,        // Holds the JWT string
    isAuthenticated: false
  }),

  // 2. Getters: Computed state selectors (like checking roles)
  getters: {
    getUserRole: (state) => state.user?.role || null
  },

  // 3. Actions: Functions to change your state or fetch APIs
  actions: {
    // Basic Mock Login function for your Interim Build
    mockLogin(email, password) {
      // In PR3, this will be an axios.post() request to your Slim 4 backend
      if (email && password) {
        this.user = {
          name: 'Test User',
          email: email,
          role: 'patient' // Change to 'caregiver' or 'admin' to test different dashboards
        }
        this.token = 'mock-jwt-token-xyz'
        this.isAuthenticated = true
        return true
      }
      return false
    },

    // Clear everything out on logout
    logout() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
    }
  }
})