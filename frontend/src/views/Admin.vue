<template>
  <div class="container" style="max-width: 1200px; margin: 30px auto; font-family: system-ui, -apple-system, sans-serif;">
    
    <header style="background: #ffffff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #3b82f6;">
      <div>
        <h1 style="margin: 0; font-size: 24px; color: #1f2937; font-weight: 700;">MediMinder Clinic Portal</h1>
      </div>
      
      <button 
        @click="showForm = !showForm" 
        style="padding: 8px 16px; font-size: 13px; font-weight: 600; background-color: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; max-width: 200px; white-space: nowrap; transition: background 0.2s;"
        onmouseover="this.style.backgroundColor='#2563eb'"
        onmouseout="this.style.backgroundColor='#3b82f6'"
      >
        {{ showForm ? '✕ Close Form' : '➕ New Prescription' }}
      </button>
    </header>

    <div v-if="showForm" class="card" style="margin-bottom: 30px; background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.06); border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 8px 0; font-size: 18px; color: #111827;">Write New Patient Prescription</h2>
      <p style="margin: 0 0 20px 0; font-size: 14px; color: #6b7280;">Compile validated medication records directly into the patient logging stream.</p>
      
      <div 
        v-if="interactionWarning" 
        style="background-color: #fffbeb; border: 1px solid #fcd34d; padding: 15px; border-radius: 6px; margin-bottom: 20px;"
      >
        <p style="color: #b45309; margin: 0; font-weight: bold; font-size: 14px;">
          ⚠️ Drug Interaction Warning: {{ interactionWarning }}
        </p>
      </div>

      <form @submit.prevent="handlePrescriptionSubmit">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Target Patient Profile</label>
            <select v-model="patientProfile" @change="checkDrugInteractions" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background-color: #f9fafb;">
              <option value="">-- Select Patient Profile Record --</option>
              <option value="Encik Ahmad Bin Ali">Encik Ahmad Bin Ali (ahmad58@gmail.com)</option>
              <option value="Puan Haida Binti Kamal">Puan Haida Binti Kamal (haidakamal@gmail.com)</option>
            </select>
          </div>

          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Medication Catalog Item</label>
            <input type="text" v-model="medName" @input="checkDrugInteractions" placeholder="e.g., Metformin HCL" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background-color: #f9fafb;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Physical Medicine Form</label>
            <select v-model="medForm" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background-color: #f9fafb;">
              <option value="Tablet">Tablet</option>
              <option value="Capsule">Capsule</option>
              <option value="Syrup">Syrup</option>
              <option value="Injection">Injection</option>
            </select>
          </div>

          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Strength Metric</label>
            <input type="text" v-model="strength" placeholder="e.g., 500mg" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background-color: #f9fafb;">
          </div>

          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Schedule Frequency (Doses/Day)</label>
            <input type="text" v-model="frequency" placeholder="e.g., 2" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background-color: #f9fafb;">
          </div>
        </div>

        <button type="submit" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s;"
          onmouseover="this.style.backgroundColor='#059669'"
          onmouseout="this.style.backgroundColor='#10b981'">
          Commit & Append Database Record
        </button>
      </form>
    </div>

    <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 25px; align-items: start;">
      <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 6px 0; font-size: 18px; color: #111827;">System Prescription Registry</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
          <thead>
            <tr style="background-color: #f9fafb; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600;">
              <th style="padding: 12px 16px;">Patient Identifier Name</th>
              <th style="padding: 12px 16px;">Medication Catalog</th>
              <th style="padding: 12px 16px;">Form Factor</th>
              <th style="padding: 12px 16px;">Metric Strength</th>
              <th style="padding: 12px 16px; text-align: center;">Frequency Interval</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="sortedPrescriptions.length === 0">
              <td colspan="5" style="padding: 30px; text-align: center; color: #9ca3af; font-style: italic; background-color: #fdfdfd;">
                No structural records found in the current system layer context.
              </td>
            </tr>
            <tr v-for="(presc, index) in sortedPrescriptions" :key="index" style="border-bottom: 1px solid #f3f4f6; color: #374151;"
                onmouseover="this.style.backgroundColor='#f9fafb'"
                onmouseout="this.style.backgroundColor='transparent'">
              <td style="padding: 14px 16px; font-weight: 600; color: #111827;">
                {{ shouldDisplayName(index) ? presc.patient : '' }}
              </td>
              <td style="padding: 14px 16px;">{{ presc.name }}</td>
              <td style="padding: 14px 16px; color: #6b7280;">{{ presc.form }}</td>
              <td style="padding: 14px 16px; font-variant-numeric: tabular-nums;">{{ presc.strength }}</td>
              <td style="padding: 14px 16px; text-align: center; font-weight: bold; color: #2563eb;">{{ presc.frequency }}x Daily</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 6px 0; font-size: 16px; color: #111827;">System Health Metrics</h2>
        <div v-if="isLoading" style="padding: 15px 0; font-size: 13px; font-style: italic; color: #9ca3af; text-align: center;">
          Syncing directory state modules...
        </div>
        <table v-else style="font-size: 13px; width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600; text-align: left;">
              <th style="padding-bottom: 8px;">Target Profile</th>
              <th style="padding-bottom: 8px; text-align: right;">Compliance Index</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid #f3f4f6;">
              <td style="padding: 12px 0; color: #374151; font-weight: 500;">Encik Ahmad</td>
              <td style="padding: 12px 0; text-align: right;">
                <strong style="color: #065f46; background: #d1fae5; padding: 4px 10px; border-radius: 12px; font-size: 12px;">94%</strong>
              </td>
            </tr>
            <tr>
              <td style="padding: 12px 0; color: #374151; font-weight: 500;">Puan Haida</td>
              <td style="padding: 12px 0; text-align: right;">
                <strong style="color: #991b1b; background: #fee2e2; padding: 4px 10px; border-radius: 12px; font-size: 12px;">62%</strong>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
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

// 🚨 NEW: Real-time alert state manager string
const interactionWarning = ref('')

const prescriptions = ref([
  { patient: 'Encik Ahmad Bin Ali', name: 'Metformin HCL', form: 'Capsule', strength: '500mg', frequency: '2' },
  { patient: 'Puan Haida Binti Kamal', name: 'Insulin Glargine', form: 'Injection', strength: '10ml', frequency: '1' }
])
const isLoading = ref(true)

// 🚨 NEW: Static drug high-risk evaluation matching mapping index
const dangerousPairs = {
  'Encik Ahmad Bin Ali': { conflictingDrug: 'Contrast Dye', message: 'Metformin combined with Contrast Dye can lead to acute renal failure risks!' },
  'Puan Haida Binti Kamal': { conflictingDrug: 'Aspirin', message: 'Insulin paired with high doses of Aspirin might dangerously amplify hypoglycemic events.' }
}

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false
  }, 500)
})

const sortedPrescriptions = computed(() => {
  return [...prescriptions.value].sort((a, b) => a.patient.localeCompare(b.patient))
})

function shouldDisplayName(index) {
  if (index === 0) return true
  return sortedPrescriptions.value[index].patient !== sortedPrescriptions.value[index - 1].patient
}

// 🚨 NEW: Real-time sub-string text pattern lookup algorithm
function checkDrugInteractions() {
  interactionWarning.value = ''
  if (!patientProfile.value || !medName.value) return

  const record = dangerousPairs[patientProfile.value]
  if (record) {
    const typedDrug = medName.value.toLowerCase().trim()
    const forbiddenDrug = record.conflictingDrug.toLowerCase()
    
    if (typedDrug.includes(forbiddenDrug) && typedDrug.length > 0) {
      interactionWarning.value = record.message
    }
  }
}

function handlePrescriptionSubmit() {
  const trimmedMedName = medName.value.trim();
  const trimmedStrength = strength.value.trim();
  const trimmedFrequency = frequency.value.trim();

  if (!patientProfile.value || !trimmedMedName || !trimmedStrength || !trimmedFrequency) {
    alert('🚨 Form Validation Error: All fields must be filled out before submitting records!');
    return
  }

  const numericFrequency = Number(trimmedFrequency);
  if (isNaN(numericFrequency) || numericFrequency <= 0 || !Number.isInteger(numericFrequency)) {
    alert('🚨 Data Format Error: Daily frequency must be a valid positive whole number entry!');
    return
  }

  // 🚨 NEW: Absolute hard-stop intercept boundary if warning banner is up
  if (interactionWarning.value) {
    alert(`🚨 Clinical Hold: Cannot commit prescription. ${interactionWarning.value}`)
    return
  }

  prescriptions.value.push({
    patient: patientProfile.value,
    name: trimmedMedName,
    form: medForm.value,
    strength: trimmedStrength,
    frequency: trimmedFrequency
  })

  alert(`✓ Success! Prescription for "${trimmedMedName}" is structured safely and appended to the tracking table.`)

  patientProfile.value = ''
  medName.value = ''
  medForm.value = 'Tablet'
  strength.value = ''
  frequency.value = ''
  interactionWarning.value = ''
  showForm.value = false
}
</script>