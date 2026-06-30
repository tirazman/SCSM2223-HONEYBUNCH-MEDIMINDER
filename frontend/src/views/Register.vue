<template>
  <div class="container" style="max-width: 500px; margin: 20px auto; font-family: system-ui, -apple-system, sans-serif;">
    
    <div v-if="currentView === 'role-view'" class="card" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 10px 0; color: #1f2937;">Join MediMinder</h2>
      <p style="margin: 0 0 20px 0; color: #6b7280; font-size: 14px;">How will you be using the application?</p>
      
      <div class="button-group" style="display: flex; flex-direction: column; gap: 10px;">
        <button @click="showForm('patient-view')" style="padding: 12px; font-weight: 600; background-color: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">I am a Patient</button>
        <button @click="showForm('caregiver-view')" style="padding: 12px; font-weight: 600; background-color: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer;">I am a Caregiver</button>
        <button @click="showForm('admin-view')" style="padding: 12px; font-weight: 600; background-color: #1f2937; color: white; border: none; border-radius: 6px; cursor: pointer;">I am a Clinic Admin</button>
      </div>
      <p style="margin-top: 20px; font-size: 14px; color: #4b5563;">Already have an account? <router-link to="/login" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Login here</router-link></p>
    </div>

    <div v-if="currentView === 'patient-view'" class="card" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 20px 0; color: #3b82f6;">Patient Registration</h2>
      
      <form @submit.prevent="handleRegister('Patient')">
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Full Name</label>
          <input v-model="fullName" type="text" placeholder="Enter your full name" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email Address</label>
          <input v-model="email" type="email" placeholder="Enter your email" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Password</label>
          <input v-model="password" type="password" placeholder="Create a password" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Date of Birth</label>
          <input v-model="dob" type="date" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>

        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
          <input type="checkbox" id="link-caregiver" v-model="linkCaregiver">
          <label for="link-caregiver" style="margin: 0; font-size: 14px; font-weight: 600; color: #374151;">Link a Caregiver</label>
        </div>

        <div v-if="linkCaregiver" style="margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Caregiver Email Address</label>
          <input v-model="caregiverEmail" type="email" placeholder="Enter your caregiver's email" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>

        <button type="submit" style="width: 100%; padding: 12px; font-weight: 600; background-color: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">Create Patient Account</button>
      </form>
      
      <p style="margin-top: 15px; font-size: 14px;"><a href="#" @click.prevent="showForm('role-view')" style="color: #4b5563; text-decoration: none;">&larr; Back to roles</a></p>
    </div>

    <div v-if="currentView === 'caregiver-view'" class="card" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 20px 0; color: #f59e0b;">Caregiver Registration</h2>
      
      <form @submit.prevent="handleRegister('Caregiver')">
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Full Name</label>
          <input v-model="fullName" type="text" placeholder="Enter your full name" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email Address</label>
          <input v-model="email" type="email" placeholder="Enter your email" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Password</label>
          <input v-model="password" type="password" placeholder="Create a password" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <button type="submit" style="width: 100%; padding: 12px; font-weight: 600; background-color: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer;">Create Caregiver Account</button>
      </form>
      
      <p style="margin-top: 15px; font-size: 14px;"><a href="#" @click.prevent="showForm('role-view')" style="color: #4b5563; text-decoration: none;">&larr; Back to roles</a></p>
    </div>

    <div v-if="currentView === 'admin-view'" class="card" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 20px 0; color: #1f2937;">Admin Registration</h2>
      
      <form @submit.prevent="handleRegister('Admin')">
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Admin Full Name</label>
          <input v-model="fullName" type="text" placeholder="Enter your full name" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Clinic/Facility Name</label>
          <input v-model="clinicName" type="text" placeholder="e.g., KPJ Medical Center" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 15px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Work Email</label>
          <input v-model="email" type="email" placeholder="Enter your work email" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Password</label>
          <input v-model="password" type="password" placeholder="Create a password" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <button type="submit" style="width: 100%; padding: 12px; font-weight: 600; background-color: #1f2937; color: white; border: none; border-radius: 6px; cursor: pointer;">Create Admin Account</button>
      </form>
      
      <p style="margin-top: 15px; font-size: 14px;"><a href="#" @click.prevent="showForm('role-view')" style="color: #4b5563; text-decoration: none;">&larr; Back to roles</a></p>
    </div>

    <div v-if="currentView === 'confirm-view'" class="card" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; border-top: 4px solid #10b981;">
      <h2 style="margin: 0 0 5px 0; color: #10b981;">✓ Registration Confirmed</h2>
      <p style="margin: 0 0 20px 0; color: #6b7280; font-size: 14px;">Account successfully created.</p>

      <div style="background-color: #f3f4f6; padding: 15px; border-radius: 6px; text-align: left; font-size: 14px; line-height: 1.6; margin-bottom: 20px; color: #374151;">
        <div style="margin-bottom: 8px;"><strong>Assigned System Role:</strong> {{ registeredRole }}</div>
        <div style="margin-bottom: 8px;"><strong>Registered Name:</strong> {{ confirmedData.name }}</div>
        <div style="margin-bottom: 8px;"><strong>System Identity Email:</strong> {{ confirmedData.email }}</div>
        
        <div v-if="registeredRole === 'Admin' && confirmedData.clinic">
          <strong>Clinic Association:</strong> {{ confirmedData.clinic }}
        </div>
        <div v-if="registeredRole === 'Patient' && confirmedData.linkedEmail">
          <strong>Linked Caregiver Email:</strong> {{ confirmedData.linkedEmail }}
        </div>
      </div>

      <button @click="goToLogin" style="width: 100%; padding: 12px; font-weight: bold; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
        Proceed to Secure Login Dashboard
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuth } from '../stores/auth'

const router = useRouter()
const auth = useAuth()

const API_BASE = 'http://localhost:8000/api'

// View State Controller
const currentView = ref('role-view')

// Form Data Refs
const fullName = ref('')
const email = ref('')
const password = ref('')
const dob = ref('')
const clinicName = ref('')
const linkCaregiver = ref(false)
const caregiverEmail = ref('')

// Summary Retention Refs
const registeredRole = ref('')
const confirmedData = ref({
  name: '',
  email: '',
  clinic: '',
  linkedEmail: ''
})

// Navigate between forms and clear inputs
function showForm(viewId) {
  currentView.value = viewId

  fullName.value = ''
  email.value = ''
  password.value = ''
  dob.value = ''
  clinicName.value = ''
  linkCaregiver.value = false
  caregiverEmail.value = ''
}

// Unified Registration Handler - calls the real Slim backend
async function handleRegister(role) {
  // Base Validation
  if (!fullName.value || !email.value || !password.value) {
    alert('🚨 All standard fields are required before submitting!')
    return
  }

  // Admin Specific Validation
  if (role === 'Admin' && !clinicName.value) {
    alert('🚨 Clinic/Facility name is required for Admin registration.')
    return
  }

  // Patient + Linked Caregiver Validation
  if (role === 'Patient' && linkCaregiver.value && !caregiverEmail.value) {
    alert('🚨 Please provide the caregiver email address to establish the link.')
    return
  }

  // Backend expects lowercase role values: patient, caregiver, admin
  const backendRole = role.toLowerCase()

  try {
    const response = await axios.post(`${API_BASE}/auth/register`, {
      name: fullName.value,
      email: email.value,
      password: password.value,
      role: backendRole,
      dob: dob.value
    })

    const { token, user } = response.data

    let linkedEmailForDisplay = ''
    if (role === 'Patient' && linkCaregiver.value) {
      try {
        await axios.post(
          `${API_BASE}/patient-caregiver`,
          { caregiver_email: caregiverEmail.value },
          { headers: { Authorization: `Bearer ${token}` } }
        )
        linkedEmailForDisplay = caregiverEmail.value
      } catch (linkError) {
        console.error('Caregiver linking failed:', linkError.response?.data?.error || linkError.message)
        alert('Account created, but linking the caregiver failed: ' + (linkError.response?.data?.error || 'unknown error') + '. You can link them later.')
      }
    }

    // Log the new user in automatically so they land on their dashboard ready to go
    auth.token = token
    auth.user = user
    auth.isAuthenticated = true

    registeredRole.value = role
    confirmedData.value = {
      name: fullName.value,
      email: email.value,
      clinic: clinicName.value,
      linkedEmail: linkedEmailForDisplay
    }

    currentView.value = 'confirm-view'
  } catch (error) {
    const errMsg = error.response?.data?.error
      || Object.values(error.response?.data?.errors || {}).join(', ')
      || error.message
    alert(`🚨 Registration failed: ${errMsg}`)
  }
}

// Securely route back to the Login Dashboard route endpoint
function goToLogin() {
  router.push('/login')
}
</script>