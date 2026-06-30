import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE = 'https://scsm2223-honeybunch-mediminder.onrender.com'

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
        async register(name, email, password, dob, role) {
      try {
        const response = await axios.post(`${API_BASE}/api/auth/register`, {
          name, email, password, dob, role
        })
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        return { success: true }
      } catch (error) {
        const message = error.response?.data?.error
          || Object.values(error.response?.data?.errors || {}).join(', ')
          || 'Registration failed'
        return { success: false, error: message }
      }
    },

    async linkCaregiver(caregiverEmail) {
      try {
        await axios.post(
          `${API_BASE}/api/patient-caregiver`,
          { caregiver_email: caregiverEmail },
          { headers: { Authorization: `Bearer ${this.token}` } }
        )
        return { success: true }
      } catch (error) {
        const message = error.response?.data?.error || 'Linking failed'
        return { success: false, error: message }
      }
    },
    async login(email, password, role) {
      try {
        const response = await axios.post(`${API_BASE}/auth/login`, {
          email,
          password,
          role
        })

        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true

        return { success: true }
      } catch (error) {
        const message = error.response?.data?.error || 'Login failed'
        return { success: false, error: message }
      }
    },

    /**
     * Updates the logged-in user's profile (name, email, dob, and
     * optionally password). Only send the fields that actually changed —
     * the backend only touches keys present in the payload.
     *
     * @param {{ name?: string, email?: string, dob?: string|null, password?: string }} payload
     */
    async updateProfile(payload) {
      try {
        const response = await axios.put(
          `${API_BASE}/auth/profile`,
          payload,
          {
            headers: {
              Authorization: `Bearer ${this.token}`
            }
          }
        )

        // Backend returns the fresh user row (without password_hash).
        this.user = response.data.user

        return { success: true }
      } catch (error) {
        const message =
          error.response?.data?.error ||
          error.response?.data?.errors ||
          'Could not update profile'
        return { success: false, error: message }
      }
    },

    logout() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
    }
  }
})