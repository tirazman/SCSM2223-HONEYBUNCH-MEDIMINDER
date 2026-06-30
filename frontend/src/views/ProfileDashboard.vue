<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

/**
 * Sample reactive user data for demonstration.
 * Swap this out for a prop, a fetched API response, or your auth store's
 * user object once this is wired into the real app.
 */
const user = reactive({
  id: 1,
  name: 'Pn. Aisyah Binti Ahmad',
  email: 'aisyah.ahmad@example.com',
  password: 'SuperSecret123!',
  role: 'Patient', // 'Patient' | 'Caregiver' | 'Admin'
  dob: '1990-04-12'
})

// Controls whether the password field shows plain text or dots
const showPassword = ref(false)

const passwordFieldType = computed(() => (showPassword.value ? 'text' : 'password'))

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value
}

// Same role -> route convention used by ProfileDropdown.vue, kept in sync
const ROLE_DASHBOARD_ROUTES = {
  Patient: '/patient',
  Caregiver: '/caregiver',
  Admin: '/admin'
}

function goBackToDashboard() {
  router.push(ROLE_DASHBOARD_ROUTES[user.role] ?? '/login')
}

const roleBadgeClass = computed(() => {
  switch (user.role) {
    case 'Admin':
      return 'badge-admin'
    case 'Caregiver':
      return 'badge-caregiver'
    default:
      return 'badge-patient'
  }
})
</script>

<template>
  <div class="profile-page">
    <div class="profile-card">
      <header class="profile-header">
        <div class="profile-avatar">
          <svg viewBox="0 0 24 24" fill="currentColor" class="avatar-icon">
            <path
              d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.5c-3.3 0-9.8 1.6-9.8 4.9v2.4h19.6v-2.4c0-3.3-6.5-4.9-9.8-4.9z"
            />
          </svg>
        </div>

        <div class="profile-heading-text">
          <h1>{{ user.name }}</h1>
          <span class="role-badge" :class="roleBadgeClass">{{ user.role }}</span>
        </div>
      </header>

      <dl class="profile-fields">
        <div class="field-row">
          <dt>User ID</dt>
          <dd>{{ user.id }}</dd>
        </div>

        <div class="field-row">
          <dt>Full Name</dt>
          <dd>{{ user.name }}</dd>
        </div>

        <div class="field-row">
          <dt>Email</dt>
          <dd>{{ user.email }}</dd>
        </div>

        <div class="field-row">
          <dt>Password</dt>
          <dd class="password-row">
            <input
              :type="passwordFieldType"
              :value="user.password"
              class="password-input"
              readonly
            />
            <button
              type="button"
              class="password-toggle"
              :aria-label="showPassword ? 'Hide password' : 'Show password'"
              @click="togglePasswordVisibility"
            >
              <!-- Eye (visible) -->
              <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <!-- Eye-off (hidden) -->
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.9 17.9A10.7 10.7 0 0 1 12 20c-7 0-11-8-11-8a18.9 18.9 0 0 1 5.1-5.9M9.9 4.2A10.4 10.4 0 0 1 12 4c7 0 11 8 11 8a18.7 18.7 0 0 1-2.4 3.5" />
                <path d="M14.1 14.1a3 3 0 1 1-4.2-4.2" />
                <path d="M1 1l22 22" />
              </svg>
            </button>
          </dd>
        </div>

        <!-- Date of Birth only renders for patients -->
        <div v-if="user.role === 'Patient'" class="field-row">
          <dt>Date of Birth</dt>
          <dd>{{ user.dob }}</dd>
        </div>
      </dl>

      <button type="button" class="back-button" @click="goBackToDashboard">
        ← Back to Dashboard
      </button>
    </div>
  </div>
</template>

<style scoped>
.profile-page {
  display: flex;
  justify-content: center;
  padding: 2.5rem 1.5rem;
  background-color: #f3f6f9;
  min-height: 100vh;
}

.profile-card {
  width: 100%;
  max-width: 28rem;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
  padding: 1.75rem;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-bottom: 1.25rem;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.profile-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3.25rem;
  height: 3.25rem;
  border-radius: 50%;
  background-color: #042238;
  flex-shrink: 0;
}

.avatar-icon {
  width: 1.9rem;
  height: 1.9rem;
  color: #ffffff;
}

.profile-heading-text h1 {
  margin: 0 0 0.35rem;
  font-size: 1.15rem;
  font-weight: 600;
  color: #0f172a;
}

.role-badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.badge-patient {
  background-color: #e0f2fe;
  color: #0369a1;
}

.badge-caregiver {
  background-color: #fef3c7;
  color: #b45309;
}

.badge-admin {
  background-color: #ede9fe;
  color: #6d28d9;
}

.profile-fields {
  margin: 0 0 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field-row {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.field-row dt {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.field-row dd {
  margin: 0;
  font-size: 0.9rem;
  color: #0f172a;
}

.password-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.password-input {
  flex: 1;
  border: 1px solid #e2e8f0;
  border-radius: 0.4rem;
  padding: 0.45rem 0.6rem;
  font-size: 0.9rem;
  background-color: #f8fafc;
  color: #0f172a;
}

.password-input:focus {
  outline: none;
  border-color: #042238;
}

.password-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.1rem;
  height: 2.1rem;
  flex-shrink: 0;
  background: none;
  border: 1px solid #e2e8f0;
  border-radius: 0.4rem;
  color: #64748b;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.password-toggle svg {
  width: 1.1rem;
  height: 1.1rem;
}

.password-toggle:hover {
  background-color: #f1f5f9;
  color: #042238;
}

.back-button {
  width: 100%;
  padding: 0.65rem;
  background-color: #042238;
  color: #ffffff;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.back-button:hover {
  background-color: #0b3654;
}
</style>