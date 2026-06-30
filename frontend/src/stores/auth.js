import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE = 'http://localhost:8000/api'

export const useAuth = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    isAuthenticated: false
  }),

  getters: {
    getUserRole: (state) => state.user?.role || null
  },

  actions: {
    async login(email, password) {
      try {
        const response = await axios.post(`${API_BASE}/auth/login`, {
          email,
          password
        })

        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true

        return true
      } catch (error) {
        console.error('Login failed:', error.response?.data?.error || error.message)
        return false
      }
    },

    logout() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
    }
  }
})