<template>
    <div class="container" style="max-width: 500px; margin: 20px auto;">
      
      <div v-if="currentView === 'role-view'" class="card">
        <h2>Join MediMinder</h2>
        <p style="margin-bottom: 20px;">How will you be using the application?</p>
        
        <div class="button-group">
          <button class="btn-patient" @click="showForm('patient-view')">I am a Patient</button>
          <button class="btn-caregiver" @click="showForm('caregiver-view')">I am a Caregiver</button>
          <button class="btn-admin" @click="showForm('admin-view')">I am a Clinic Admin</button>
        </div>
        <p style="margin-top: 20px;">Already have an account? <router-link to="/login">Login here</router-link></p>
      </div>

      <div v-if="currentView === 'patient-view'" class="card">
        <h2 style="color: #3b82f6;">Patient Registration</h2>
        
        <form @submit.prevent="handleRegister('Patient')">
          <div class="form-group">
            <label>Full Name</label>
            <input v-model="fullName" type="text" placeholder="Enter your full name">
          </div>
          
          <div class="form-group">
            <label>Email Address</label>
            <input v-model="email" type="email" placeholder="Enter your email">
          </div>
          
          <div class="form-group">
            <label>Password</label>
            <input v-model="password" type="password" placeholder="Create a password">
          </div>

          <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" id="link-caregiver" v-model="linkCaregiver">
            <label for="link-caregiver" style="margin: 0;">Link a Caregiver</label>
          </div>

          <div v-if="linkCaregiver" class="form-group">
            <label>Caregiver Email Address</label>
            <input v-model="caregiverEmail" type="email" placeholder="Enter your caregiver's email">
          </div>

          <button type="submit" class="btn-patient">Create Patient Account</button>
        </form>
        
        <p style="margin-top: 15px;"><a href="#" @click.prevent="showForm('role-view')">&larr; Back to roles</a></p>
      </div>

      <div v-if="currentView === 'caregiver-view'" class="card">
        <h2 style="color: #f59e0b;">Caregiver Registration</h2>
        
        <form @submit.prevent="handleRegister('Caregiver')">
          <div class="form-group">
            <label>Full Name</label>
            <input v-model="fullName" type="text" placeholder="Enter your full name">
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input v-model="email" type="email" placeholder="Enter your email">
          </div>
          <div class="form-group">
            <label>Password</label>
            <input v-model="password" type="password" placeholder="Create a password">
          </div>
          <button type="submit" class="btn-caregiver">Create Caregiver Account</button>
        </form>
        
        <p style="margin-top: 15px;"><a href="#" @click.prevent="showForm('role-view')">&larr; Back to roles</a></p>
      </div>

      <div v-if="currentView === 'admin-view'" class="card">
        <h2 style="color: #1f2937;">Admin Registration</h2>
        
        <form @submit.prevent="handleRegister('Admin')">
          <div class="form-group">
            <label>Admin Full Name</label>
            <input v-model="fullName" type="text" placeholder="Enter your full name">
          </div>
          <div class="form-group">
            <label>Clinic/Facility Name</label>
            <input v-model="clinicName" type="text" placeholder="e.g., KPJ Medical Center">
          </div>
          <div class="form-group">
            <label>Work Email</label>
            <input v-model="email" type="email" placeholder="Enter your work email">
          </div>
          <div class="form-group">
            <label>Password</label>
            <input v-model="password" type="password" placeholder="Create a password">
          </div>
          <button type="submit" class="btn-admin">Create Admin Account</button>
        </form>
        
        <p style="margin-top: 15px;"><a href="#" @click.prevent="showForm('role-view')">&larr; Back to roles</a></p>
      </div>
    </div>
 
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// View State Controller
const currentView = ref('role-view')

// Form Data Refs
const fullName = ref('')
const email = ref('')
const password = ref('')
const clinicName = ref('')
const linkCaregiver = ref(false)
const caregiverEmail = ref('')

// Navigate between forms and clear inputs
function showForm(viewId) {
  currentView.value = viewId
  
  // Reset form states upon view change
  fullName.value = ''
  email.value = ''
  password.value = ''
  clinicName.value = ''
  linkCaregiver.value = false
  caregiverEmail.value = ''
}

// Unified Registration Handler
function handleRegister(role) {
  // Base Validation
  if (!fullName.value || !email.value || !password.value) {
    alert('🚨 Security Validation Error: All standard fields are strictly required before submitting records!')
    return
  }

  // Admin Specific Validation
  if (role === 'Admin' && !clinicName.value) {
    alert('🚨 Validation Error: Clinic/Facility name is required for Admin registration.')
    return
  }

  // Patient + Linked Caregiver Validation
  if (role === 'Patient' && linkCaregiver.value && !caregiverEmail.value) {
    alert('🚨 Validation Error: Please provide the caregiver email address to establish the link.')
    return
  }

  alert(`✓ Success! Data validated safely for ${role} user: ${fullName.value}`)
  router.push('/login')
}
</script>