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

      <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; font-size: 0.85rem; color: #666; text-align: left; line-height: 1.6;">
        <p style="margin: 0 0 6px 0; font-weight: bold; color: #444;">Demo logins:</p>
        
        <div style="margin-bottom: 4px;">
          <strong style="color: #d35400;">Patient:</strong> 
          <span style="font-family: monospace; background: #f9f9f9; padding: 2px 4px; border-radius: 3px;">patient@mediminder.test</span> / <span>password</span>
        </div>
        
        <div style="margin-bottom: 4px;">
          <strong style="color: #d35400;">Caregiver:</strong> 
          <span style="font-family: monospace; background: #f9f9f9; padding: 2px 4px; border-radius: 3px;">caregiver@mediminder.test</span> / <span>password</span>
        </div>
        
        <div>
          <strong style="color: #d35400;">Clinic Admin:</strong> 
          <span style="font-family: monospace; background: #f9f9f9; padding: 2px 4px; border-radius: 3px;">admin@mediminder.test</span> / <span>password</span>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const password = ref('')
const role = ref('patient')

// 1. Define the strictly allowed credentials for each role
const validDemoAccounts = {
  'patient': {
    email: 'patient@mediminder.test',
    password: 'password'
  },
  'caregiver': {
    email: 'caregiver@mediminder.test',
    password: 'password'
  },
  'admin': {
    email: 'admin@mediminder.test',
    password: 'password'
  }
}

function handleLogin() {
  // Check for empty fields first
  if (!email.value || !password.value) {
    alert('🚨 Security Entry Alert: Email and password fields cannot be empty!')
    return
  }

  // 2. Get the expected credentials based on the selected role
  const expectedAccount = validDemoAccounts[role.value]

  // 3. Verify the user input matches the expected demo account
  if (email.value === expectedAccount.email && password.value === expectedAccount.password) {
    alert(`✓ Validation Passed! Authenticating user session safely as role: [${role.value}]`)
    router.push(`/${role.value}`)
  } else {
    // 4. Reject invalid login attempts
    alert('🚨 Invalid credentials for the selected role! Please use the demo logins provided, or register a new account first.')
    password.value = '' // Clear the password field after a failed attempt
  }
}
</script>