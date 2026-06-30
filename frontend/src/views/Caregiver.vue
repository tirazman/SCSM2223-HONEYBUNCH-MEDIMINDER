<template>
  <div class="container">
    <div class="card">
      <h2>My Supervised Patients</h2>
      <p>Real-time updates on scheduled medicine ingestion across your linked accounts.</p>

      <div v-if="isLoading" style="text-align: center; padding: 20px; color: var(--text-muted);">
        Loading supervised patients...
      </div>

      <div
        v-else
        v-for="patient in patients"
        :key="patient.id"
        class="card"
        :style="`background-color: #fffdf9; border-left: 5px solid ${patient.borderColor};`"
      >
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
          <div>
            <h3 style="color: var(--dark-honey);">Patient Name: {{ patient.name }}</h3>
            <p style="margin-top: 5px; margin-bottom: 0;">
              DOB: {{ patient.dob }} | Linked Account: {{ patient.email }}
            </p>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 14px; font-weight: bold;">30-Day Rate:</div>
            <strong class="badge" :class="patient.rateClass" style="font-size: 16px;">
              {{ patient.rate }}
            </strong>
          </div>
        </div>

        <h4 style="margin-top: 15px; color: var(--text-muted);">Today's Status Track:</h4>
        
        <MedicationTable 
          :medication-list="patient.meds" 
          @inspect-medication="handleMedicationInspection"
        />

        <div
          v-if="patient.alert"
          style="background-color: #fef2f2; padding: 10px; border-radius: 6px; margin-top: 10px; border: 1px solid #fca5a5;"
        >
          <p style="color: var(--status-danger); margin: 0; font-weight: bold;">
            ⚠️ {{ patient.alert }}
          </p>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ApiService } from '../api/client.js'
// Import child table layout module
import MedicationTable from '../components/MedicationTable.vue'

const patients = ref([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    patients.value = await ApiService.getCaregiverDashboardData();
  } catch (error) {
    console.error("Failed to load caregiver data", error);
  } finally {
    isLoading.value = false;
  }
})

// capturing emitted data packages from sub-components
function handleMedicationInspection(medication) {
  console.log(`Inspecting log indices for medication entry: ${medication.name}`)
}
</script>