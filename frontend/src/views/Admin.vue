<template>
  <div class="container">
    <div class="dashboard-grid">
      
      <div class="card">
        <h2>Write New Patient Prescription</h2>
        <p>Assign medication logs directly to a registered patient data record.</p>
        
        <form id="prescriptionForm" @submit.prevent="handlePrescriptionSubmit">
          <div class="form-group">
            <label for="patient-select">Target Patient Profile</label>
            <select id="patient-select" v-model="patientProfile">
              <option value="">-- Choose Profile --</option>
              <option value="1">Encik Ahmad Bin Ali (ahmad58@gmail.com)</option>
              <option value="2">Puan Haida Binti Kamal (haidakamal@gmail.com)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="med-name">Medication Catalog Item</label>
            <input type="text" id="med-name" v-model="medName" placeholder="e.g., Metformin HCL">
          </div>

          <div class="form-group">
            <label for="med-form">Physical Medicine Form</label>
            <select id="med-form">
              <option value="Tablet">Tablet</option>
              <option value="Capsule">Capsule</option>
              <option value="Syrup">Syrup</option>
              <option value="Injection">Injection</option>
            </select>
          </div>

          <div class="form-group">
            <label for="med-strength">Strength Metric</label>
            <input type="text" id="med-strength" v-model="strength" placeholder="e.g., 500mg, 10ml">
          </div>

          <div class="form-group">
            <label for="dosage-qty">Dose Value (Per Interval)</label>
            <input type="text" id="dosage-qty" placeholder="e.g., 1 pill, 2 drops">
          </div>

          <div class="form-group">
            <label for="frequency">Interval Schedule Frequency (Doses Per Day)</label>
            <input type="text" id="frequency" v-model="frequency" placeholder="e.g., 2">
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div class="form-group">
              <label>Start Window</label>
              <input type="date">
            </div>
            <div class="form-group">
              <label>Conclusion Window</label>
              <input type="date">
            </div>
          </div>

          <div class="form-group">
            <label>Internal Administrator Notes</label>
            <textarea placeholder="Take post-meals..."></textarea>
          </div>

          <button type="submit" class="btn">Issue System Prescription</button>
        </form>
      </div>

      <div>
        <div class="card">
          <h2>Active Directory Summary</h2>
          <p>Quick lookup on general performance parameters across registered users.</p>
          
          <div v-if="isLoading" style="padding: 10px 0; font-style: italic;">
            Fetching directory data...
          </div>
          
          <table v-else style="font-size: 13px; width: 100%;">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Avg. Compliance</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Encik Ahmad</td>
                <td><span class="badge bg-success">94%</span></td>
              </tr>
              <tr>
                <td>Puan Haida</td>
                <td><span class="badge bg-danger">62%</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ApiService } from '../api/client.js'

// Form State
const patientProfile = ref('')
const medName = ref('')
const strength = ref('')
const frequency = ref('')

// Data State
const directory = ref([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    directory.value = await ApiService.getAdminDirectory();
  } catch (error) {
    console.error("Failed to load directory", error);
  } finally {
    isLoading.value = false;
  }
})

function handlePrescriptionSubmit() {
  if (!patientProfile.value || !medName.value || !strength.value || !frequency.value) {
    alert('🚨 Validation Alert: Please fill out all fields before committing to the database layout!')
    return
  }

  if (isNaN(frequency.value)) {
    alert('🚨 Data Integrity Error: Frequency value must be a valid number entry!')
    return
  }

  alert(`✓ Validation Passed! Prescription for "${medName.value}" is structured safely and ready for database transport.`)

  // Clear form after submitting
  patientProfile.value = ''
  medName.value = ''
  strength.value = ''
  frequency.value = ''
}
</script>