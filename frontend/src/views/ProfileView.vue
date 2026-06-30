<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useAuth } from '../stores/auth'

const auth = useAuth()

// Local editable copy of the profile fields, seeded from the auth store.
// Editing happens against this object so nothing is written back to the
// store until the user explicitly saves.
const form = reactive({
  id: auth.user?.id ?? '',
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  role: auth.user?.role ?? '',
  dob: auth.user?.dob ?? ''
})

// The backend never returns the password (correctly — it shouldn't), so
// changing it is handled as a separate, explicit action with its own
// blank fields rather than something pre-filled in the main form.
const passwordForm = reactive({
  newPassword: '',
  confirmPassword: ''
})

const passwordError = computed(() => {
  if (!passwordForm.newPassword && !passwordForm.confirmPassword) return ''
  if (passwordForm.newPassword.length < 8) return 'Password must be at least 8 characters.'
  if (passwordForm.newPassword !== passwordForm.confirmPassword) return 'Passwords do not match.'
  return ''
})

// Keep the form in sync if the auth store populates after this component
// has already mounted (e.g. profile fetched async on load).
watch(
  () => auth.user,
  (next) => {
    if (!next) return
    form.id = next.id ?? form.id
    form.name = next.name ?? form.name
    form.email = next.email ?? form.email
    form.role = next.role ?? form.role
    form.dob = next.dob ?? form.dob
  }
)

const isPatient = computed(
  () => (form.role ?? '').toLowerCase() === 'patient'
)

const isPasswordVisible = ref(false)
function togglePasswordVisibility() {
  isPasswordVisible.value = !isPasswordVisible.value
}

const isEditing = ref(false)
const isSaving = ref(false)
const saveError = ref('')
const saveSuccess = ref(false)

function enterEditMode() {
  isEditing.value = true
  saveSuccess.value = false
  saveError.value = ''
}

function cancelEdit() {
  // Revert any unsaved changes back to the store's last known values
  form.id = auth.user?.id ?? ''
  form.name = auth.user?.name ?? ''
  form.email = auth.user?.email ?? ''
  form.role = auth.user?.role ?? ''
  form.dob = auth.user?.dob ?? ''

  passwordForm.newPassword = ''
  passwordForm.confirmPassword = ''

  isEditing.value = false
  isPasswordVisible.value = false
  saveError.value = ''
}

async function handleSave() {
  saveError.value = ''
  saveSuccess.value = false

  if (passwordError.value) {
    saveError.value = passwordError.value
    return
  }

  isSaving.value = true

  try {
    const payload = { ...form }
    // Only include the password if the user actually typed one in.
    if (passwordForm.newPassword) {
      payload.password = passwordForm.newPassword
    }
    // id and role are never editable from this page — don't send them.
    delete payload.id
    delete payload.role

    const result = await auth.updateProfile(payload)

    if (!result?.success) {
      saveError.value =
        typeof result?.error === 'string'
          ? result.error
          : 'Could not save changes. Please check your input and try again.'
      return
    }

    // Re-sync local form state from the store's fresh user object.
    form.name = auth.user?.name ?? form.name
    form.email = auth.user?.email ?? form.email
    form.dob = auth.user?.dob ?? form.dob

    passwordForm.newPassword = ''
    passwordForm.confirmPassword = ''
    saveSuccess.value = true
    isEditing.value = false
    isPasswordVisible.value = false
  } catch (err) {
    saveError.value = err?.message ?? 'Something went wrong while saving. Please try again.'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="profile-page">
    <div class="profile-page__header">
      <h1 class="profile-page__title">My Profile</h1>
      <p class="profile-page__subtitle">View and manage your account details</p>
    </div>

    <form class="profile-card" @submit.prevent="handleSave">
      <div class="profile-card__row">
        <label class="profile-field" for="profile-id">
          <span class="profile-field__label">ID</span>
          <input
            id="profile-id"
            class="profile-field__input"
            type="text"
            :value="form.id"
            disabled
            readonly
          />
        </label>

        <label class="profile-field" for="profile-role">
          <span class="profile-field__label">Role</span>
          <input
            id="profile-role"
            class="profile-field__input"
            type="text"
            :value="form.role"
            disabled
            readonly
          />
        </label>
      </div>

      <label class="profile-field" for="profile-name">
        <span class="profile-field__label">Name</span>
        <input
          id="profile-name"
          class="profile-field__input"
          type="text"
          v-model="form.name"
          :disabled="!isEditing"
          autocomplete="name"
        />
      </label>

      <label class="profile-field" for="profile-email">
        <span class="profile-field__label">Email</span>
        <input
          id="profile-email"
          class="profile-field__input"
          type="email"
          v-model="form.email"
          :disabled="!isEditing"
          autocomplete="email"
        />
      </label>

      <div class="profile-field">
        <span class="profile-field__label">Password</span>
        <div class="password-block">
          <div class="profile-field__password-wrap">
            <input
              id="profile-password-new"
              class="profile-field__input profile-field__input--password"
              :type="isPasswordVisible ? 'text' : 'password'"
              v-model="passwordForm.newPassword"
              :disabled="!isEditing"
              autocomplete="new-password"
              placeholder="New password"
            />
            <button
              type="button"
              class="profile-field__toggle"
              :aria-pressed="isPasswordVisible"
              :aria-label="isPasswordVisible ? 'Hide password' : 'Show password'"
              @click="togglePasswordVisibility"
            >
              <svg
                v-if="isPasswordVisible"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="profile-field__toggle-icon"
              >
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <svg
                v-else
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="profile-field__toggle-icon"
              >
                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.4 0-10-7-10-7a18.6 18.6 0 0 1 4.22-5.06M9.9 4.24A10.4 10.4 0 0 1 12 4c6.4 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                <line x1="1" y1="1" x2="23" y2="23" />
              </svg>
            </button>
          </div>

          <input
            id="profile-password-confirm"
            class="profile-field__input"
            :type="isPasswordVisible ? 'text' : 'password'"
            v-model="passwordForm.confirmPassword"
            :disabled="!isEditing"
            autocomplete="new-password"
            placeholder="Confirm new password"
          />

          <p v-if="isEditing" class="profile-field__hint">
            Leave blank to keep your current password.
          </p>
          <p v-if="isEditing && passwordError" class="profile-field__error">
            {{ passwordError }}
          </p>
        </div>
      </div>

      <transition name="field-fade">
        <label v-if="isPatient" class="profile-field" for="profile-dob">
          <span class="profile-field__label">Date of Birth</span>
          <input
            id="profile-dob"
            class="profile-field__input"
            type="date"
            v-model="form.dob"
            :disabled="!isEditing"
          />
        </label>
      </transition>

      <p v-if="saveError" class="profile-card__message profile-card__message--error">
        {{ saveError }}
      </p>
      <p v-if="saveSuccess" class="profile-card__message profile-card__message--success">
        Profile updated.
      </p>

      <div class="profile-card__actions">
        <template v-if="!isEditing">
          <button type="button" class="profile-btn profile-btn--primary" @click="enterEditMode">
            Edit Profile
          </button>
        </template>
        <template v-else>
          <button type="button" class="profile-btn profile-btn--ghost" @click="cancelEdit">
            Cancel
          </button>
          <button type="submit" class="profile-btn profile-btn--primary" :disabled="isSaving || !!passwordError">
            {{ isSaving ? 'Saving…' : 'Save changes' }}
          </button>
        </template>
      </div>
    </form>
  </div>
</template>

<style scoped>
.profile-page {
  --profile-bg: #f6f8fa;
  --profile-card-bg: #ffffff;
  --profile-border: #e2e8f0;
  --profile-text: #1a2330;
  --profile-text-muted: #64748b;
  --profile-accent: #0f6e6a;
  --profile-accent-hover: #0b5552;
  --profile-accent-soft: #e6f2f1;
  --profile-danger: #c0392b;
  --profile-danger-soft: #fdecea;
  --profile-success: #1d7a4c;
  --profile-success-soft: #e8f6ee;
  --profile-radius: 10px;

  min-height: 100%;
  background: var(--profile-bg);
  padding: 2.5rem 1.5rem 4rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  color: var(--profile-text);
}

.profile-page__header {
  width: 100%;
  max-width: 540px;
  margin-bottom: 1.5rem;
}

.profile-page__title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0 0 0.25rem;
  letter-spacing: -0.01em;
}

.profile-page__subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: var(--profile-text-muted);
}

.profile-card {
  width: 100%;
  max-width: 540px;
  background: var(--profile-card-bg);
  border: 1px solid var(--profile-border);
  border-radius: var(--profile-radius);
  padding: 1.75rem;
  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.profile-card__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.profile-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.profile-field__label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--profile-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.profile-field__input {
  font: inherit;
  font-size: 0.95rem;
  color: var(--profile-text);
  background: var(--profile-card-bg);
  border: 1px solid var(--profile-border);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.profile-field__input:focus-visible {
  outline: none;
  border-color: var(--profile-accent);
  box-shadow: 0 0 0 3px var(--profile-accent-soft);
}

.profile-field__input:disabled {
  background: #f8fafc;
  color: var(--profile-text-muted);
  cursor: default;
}

.profile-field__password-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.profile-field__input--password {
  width: 100%;
  padding-right: 2.5rem;
}

.profile-field__toggle {
  position: absolute;
  right: 0.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  background: transparent;
  color: var(--profile-text-muted);
  border-radius: 6px;
  cursor: pointer;
}

.profile-field__toggle:hover {
  color: var(--profile-accent);
  background: var(--profile-accent-soft);
}

.profile-field__toggle:focus-visible {
  outline: 2px solid var(--profile-accent);
  outline-offset: 2px;
}

.profile-field__toggle-icon {
  width: 1.1rem;
  height: 1.1rem;
}

.password-block {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.profile-field__hint {
  margin: 0;
  font-size: 0.78rem;
  color: var(--profile-text-muted);
}

.profile-field__error {
  margin: 0;
  font-size: 0.78rem;
  color: var(--profile-danger);
}

.profile-card__message {
  margin: 0;
  font-size: 0.85rem;
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
}

.profile-card__message--error {
  color: var(--profile-danger);
  background: var(--profile-danger-soft);
}

.profile-card__message--success {
  color: var(--profile-success);
  background: var(--profile-success-soft);
}

.profile-card__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
  margin-top: 0.25rem;
}

.profile-btn {
  font: inherit;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  padding: 0.55rem 1.1rem;
  cursor: pointer;
  border: 1px solid transparent;
  transition: background-color 0.15s ease, border-color 0.15s ease, opacity 0.15s ease;
}

.profile-btn--primary {
  background: var(--profile-accent);
  color: #ffffff;
}

.profile-btn--primary:hover {
  background: var(--profile-accent-hover);
}

.profile-btn--primary:disabled {
  opacity: 0.65;
  cursor: default;
}

.profile-btn--ghost {
  background: transparent;
  border-color: var(--profile-border);
  color: var(--profile-text);
}

.profile-btn--ghost:hover {
  background: #f8fafc;
}

.field-fade-enter-active,
.field-fade-leave-active {
  transition: opacity 0.18s ease, max-height 0.18s ease;
}

.field-fade-enter-from,
.field-fade-leave-to {
  opacity: 0;
}

@media (max-width: 480px) {
  .profile-card__row {
    grid-template-columns: 1fr;
  }
}
</style>