<template>
    <div class="container">
    <div class="dashboard-grid">

      <!-- LEFT: Daily Schedule -->
      <div>
        <div class="card">
          <h2>My Schedule (Today)</h2>
          <p>Be sure to mark your medication as soon as you consume it.</p>

          <div v-if="isLoading" style="text-align: center; margin-top: 50px;">
              <p>Loading patient data...</p>
          </div>

          <div
            v-for="med in medications"
            :key="med.id"
            class="list-item"
          >
            <div>
              <h3>{{ med.name }} (Form: {{ med.form }})</h3>
              <p><strong>Dose:</strong> {{ med.dose }} | ⏰ {{ med.time }}</p>
              <p style="font-size: 13px; color: #6b7280;">Notes: {{ med.notes }}</p>
            </div>

            <div>
              <template v-if="med.status === null">
                <button
                  class="btn-success"
                  style="width: auto; padding: 8px 15px; margin-right: 5px;"
                  @click="logDose(med.id, 'taken')"
                >Mark Taken</button>
                <button
                  class="btn-danger"
                  style="width: auto; padding: 8px 15px;"
                  @click="logDose(med.id, 'skipped')"
                >Skip</button>
              </template>
              <template v-else>
                <span
                  class="badge"
                  :class="med.status === 'taken' ? 'bg-success' : 'bg-danger'"
                >
                  {{ med.status === 'taken' ? '✓ Taken Checked' : '✗ Skipped Dosing' }}
                </span>
              </template>
            </div>

          </div>
        </div>
      </div>

      <!-- RIGHT: Stats & Caregiver -->
      <div>
        <div class="card" style="text-align: center;">
          <h2
          
          >7-Day Performance</h2>
          <div style="font-size: 48px; font-weight: bold; color: var(--status-success); margin: 15px 0;">
            86.5%
          </div>
          <p>Great progress! You missed only 2 doses this past week.</p>
        </div>

        <div class="card">
          <h3>Linked Caregiver</h3>
          <div style="margin-top: 10px;">
            <p style="margin-bottom: 4px;"><strong>Name:</strong> Auni Sofiya</p>
            <p style="margin-bottom: 4px;"><strong>Email:</strong> aunisofi@gmail.com</p>
            <span class="badge bg-success">Connected Status</span>
          </div>
        </div>
      </div>

    </div>
  </div>

</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ApiService } from '../api/client.js'

const medications = ref([])
const isLoading = ref(true)
  
onMounted(async () => {
  medications.value = await ApiService.getPatientDashboardData(1);
  isLoading.value = false;
})

// Function to log dose status (taken or skipped)
function logDose(id, statusStr) {
  const med = medications.value.find(m => m.id === id)
  if (med) med.status = statusStr
}
</script>
