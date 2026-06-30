<template>
  <div class="container" style="max-width: 450px; margin-top: 50px;">
    <div class="card" style="text-align: center;">
      <h2>Secure Login</h2>

      <form @submit.prevent="handleLogin" style="text-align: left; margin-top: 20px;">
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email Address</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="Enter your email" 
            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
          />
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Password</label>
          <input 
            v-model="password" 
            type="password" 
            placeholder="Enter your password" 
            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
          />
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Login As</label>
          <select v-model="role" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="patient">Patient</option>
            <option value="caregiver">Caregiver</option>
            <option value="admin">Clinic Admin</option>
          </select>
        </div>

        <button type="submit" class="btn-success" style="width: 100%; padding: 10px; font-weight: bold; margin-bottom: 15px;">
          Secure Login
        </button>
      </form>

      <p style="margin-top: 15px; font-size: 0.95rem; color: #555; text-align: left;">
        New to MediMinder? 
        <router-link to="/register" style="color: #673ab7; font-weight: bold; text-decoration: underline; cursor: pointer;">
          Register here
        </router-link>
      </p>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'

const router = useRouter()
const auth = useAuth()
const email = ref('')
const password = ref('')
const role = ref('patient')

async function handleLogin() {
  if (!email.value || !password.value) {
    alert('🚨 Email and password fields cannot be empty!')
    return
  }

  const success = await auth.login(email.value, password.value)

  if (success) {
    router.push(`/${auth.getUserRole}`)
  } else {
    alert('🚨 Invalid email or password.')
    password.value = ''
  }
}
</script>