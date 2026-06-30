<template>
  <div class="container" style="max-width: 1200px; margin: 30px auto; font-family: system-ui, -apple-system, sans-serif;">
    <header style="background: #ffffff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #3b82f6;">
      <div>
        <h1 style="margin: 0; font-size: 24px; color: #1f2937; font-weight: 700;">MediMinder Clinic Portal</h1>
      </div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <router-link to="/adherence" class="btn-export-report">
          📊 Adherence Report
        </router-link>
        <button @click="showForm = !showForm" style="padding: 8px 16px; font-size: 13px; font-weight: 600; background-color: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">
          {{ showForm ? '✕ Close Form' : '➕ New Prescription' }}
        </button>
      </div>
    </header>

    <div v-if="showForm" class="card" style="margin-bottom: 30px; background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 8px 0; font-size: 18px; color: #111827;">Write New Patient Prescription</h2>
      <div v-if="interactionWarning" style="background-color: #fffbeb; border: 1px solid #fcd34d; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <p style="color: #b45309; margin: 0; font-weight: bold; font-size: 14px;">⚠️ Drug Interaction Warning: {{ interactionWarning }}</p>
      </div>

      <form @submit.prevent="handlePrescriptionSubmit">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Target Patient Profile</label>
            <select v-model="patientProfile" @change="checkDrugInteractions" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
              <option value="">-- Select Patient Profile Record --</option>
              <option value="Encik Ahmad Bin Ali">Encik Ahmad Bin Ali (ahmad58@gmail.com)</option>
              <option value="Puan Haida Binti Kamal">Puan Haida Binti Kamal (haidakamal@gmail.com)</option>
            </select>
          </div>
          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Medication Catalog Item</label>
            <input type="text" v-model="medName" @input="checkDrugInteractions" placeholder="e.g., Metformin HCL" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Physical Medicine Form</label>
            <select v-model="medForm" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
              <option value="Tablet">Tablet</option>
              <option value="Capsule">Capsule</option>
              <option value="Syrup">Syrup</option>
              <option value="Injection">Injection</option>
            </select>
          </div>
          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Strength Metric</label>
            <input type="text" v-model="strength" placeholder="e.g., 500mg" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Schedule Frequency</label>
            <input type="text" v-model="frequency" placeholder="e.g., 2" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
        </div>
        <button type="submit" style="width: 100%; padding: 12px; font-weight: 600; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">Commit & Append Database Record</button>
      </form>
    </div>

    <div style="background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 15px 0; font-size: 18px; color: #111827;">System Prescription Registry</h2>
      <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
        <thead>
          <tr style="background-color: #f9fafb; border-bottom: 2px solid #e5e7eb;">
            <th style="padding: 12px 16px;">Patient Name</th>
            <th style="padding: 12px 16px;">Medication</th>
            <th style="padding: 12px 16px;">Form</th>
            <th style="padding: 12px 16px;">Strength</th>
            <th style="padding: 12px 16px; text-align: center;">Frequency</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(presc, index) in sortedPrescriptions" :key="index" style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 14px 16px; font-weight: 600;">{{ presc.patient }}</td>
            <td style="padding: 14px 16px;">{{ presc.name }}</td>
            <td style="padding: 14px 16px; color: #6b7280;">{{ presc.form }}</td>
            <td style="padding: 14px 16px;">{{ presc.strength }}</td>
            <td style="padding: 14px 16px; text-align: center; font-weight: bold; color: #2563eb;">{{ presc.frequency }}x Daily</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const showForm = ref(false)
const patientProfile = ref('')
const medName = ref('')
const medForm = ref('Tablet')
const strength = ref('')
const frequency = ref('')
const interactionWarning = ref('')

const prescriptions = ref([])

// 🔌 Get data dynamically from Browser Memory LocalStorage
const loadPrescriptions = () => {
  const saved = localStorage.getItem('global_prescriptions')
  if (saved) {
    prescriptions.value = JSON.parse(saved)
  } else {
    // Default fallback values
    prescriptions.value = [
      { patient: 'Encik Ahmad Bin Ali', name: 'Metformin HCL', form: 'Capsule', strength: '500mg', frequency: '2' },
      { patient: 'Puan Haida Binti Kamal', name: 'Insulin Glargine', form: 'Injection', strength: '10ml', frequency: '1' }
    ]
    localStorage.setItem('global_prescriptions', JSON.stringify(prescriptions.value))
  }
}

onMounted(() => {
  loadPrescriptions()
})

const sortedPrescriptions = computed(() => {
  return [...prescriptions.value].sort((a, b) => a.patient.localeCompare(b.patient))
})

function checkDrugInteractions() {
  interactionWarning.value = ''
  if (patientProfile.value === 'Encik Ahmad Bin Ali' && medName.value.toLowerCase().includes('contrast dye')) {
    interactionWarning.value = 'Metformin combined with Contrast Dye can lead to acute renal failure risks!'
  }
}

function handlePrescriptionSubmit() {
  if (!patientProfile.value || !medName.value.trim() || !strength.value.trim() || !frequency.value.trim()) {
    alert('🚨 All fields must be filled out!'); return
  }
  if (interactionWarning.value) {
    alert('🚨 Clinical Hold: Interaction risk detected!'); return
  }

  prescriptions.value.push({
    patient: patientProfile.value,
    name: medName.value.trim(),
    form: medForm.value,
    strength: strength.value.trim(),
    frequency: frequency.value.trim()
  })

  // 💾 Save straight into continuous LocalStorage state
  localStorage.setItem('global_prescriptions', JSON.stringify(prescriptions.value))

  alert('✓ Success! Added to registry.')
  patientProfile.value = ''; medName.value = ''; strength.value = ''; frequency.value = ''; showForm.value = false
}
</script>