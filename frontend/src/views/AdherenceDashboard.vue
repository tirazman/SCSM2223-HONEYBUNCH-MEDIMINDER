<template>
  <div class="container" style="max-width: 700px; margin: 20px auto; font-family: system-ui, -apple-system, sans-serif;">
    <div class="card" style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
      <h2 style="margin: 0 0 20px 0; color: #1f2937;">Adherence Summary</h2>

      <!-- Caregiver/Admin must pick which patient they're viewing -->
      <div v-if="auth.getUserRole !== 'patient'" style="margin-bottom: 20px;">
        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Patient</label>
        <select v-model="selectedPatientId" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
          <option :value="null" disabled>Select a patient</option>
          <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>

      <!-- Optional date range -->
      <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <div style="flex: 1;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">From</label>
          <input v-model="fromDate" type="date" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
        <div style="flex: 1;">
          <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">To</label>
          <input v-model="toDate" type="date" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
        </div>
      </div>

      <!-- Export buttons -->
      <div style="display: flex; gap: 10px;">
        <button
          @click="exportAdherence('csv')"
          :disabled="isExporting || !canExport"
          style="flex: 1; padding: 10px; font-weight: 600; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;"
        >
          {{ isExporting === 'csv' ? 'Exporting…' : 'Export CSV' }}
        </button>
        <button
          @click="exportAdherence('pdf')"
          :disabled="isExporting || !canExport"
          style="flex: 1; padding: 10px; font-weight: 600; background-color: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;"
        >
          {{ isExporting === 'pdf' ? 'Exporting…' : 'Export PDF' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuth } from '../stores/auth'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'

const route = useRoute()
const auth = useAuth()
const toast = useToast()
const API_BASE = 'http://localhost:8000/api'

const patients = ref([])
const selectedPatientId = ref(null)
const fromDate = ref('')
const toDate = ref('')
const isExporting = ref(false) // false | 'csv' | 'pdf'

// A patient exports their own data automatically; caregiver/admin must pick someone first
const canExport = computed(() => {
  return auth.getUserRole === 'patient' || selectedPatientId.value !== null
})

onMounted(async () => {
    if (route.query.patient_id) {
    selectedPatientId.value = parseInt(route.query.patient_id)
  }
    if (auth.getUserRole === 'caregiver') {
        const res = await axios.get(`${API_BASE}/caregiver/patients`, {
        headers: { Authorization: `Bearer ${auth.token}` },
        })
        patients.value = res.data.patients ?? res.data
    } else if (auth.getUserRole === 'admin') {
        const res = await axios.get(`${API_BASE}/admin/patients`, {
        headers: { Authorization: `Bearer ${auth.token}` },
        })
        patients.value = res.data.patients ?? res.data
    }
})

async function exportAdherence(format) {
  isExporting.value = format

  try {
    const params = {}
    if (auth.getUserRole !== 'patient') {
      params.patient_id = selectedPatientId.value
    }
    if (fromDate.value) params.from_date = fromDate.value
    if (toDate.value) params.to_date = toDate.value

    const response = await axios.get(`${API_BASE}/dose-logs/export/${format}`, {
      params,
      headers: { Authorization: `Bearer ${auth.token}` },
      responseType: 'blob',
    })

    const blob = new Blob([response.data], {
      type: format === 'pdf' ? 'application/pdf' : 'text/csv',
    })

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `adherence_summary_${new Date().toISOString().slice(0, 10)}.${format}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.success(`${format.toUpperCase()} export downloaded`)
  } catch (error) {
    if (error.response?.data instanceof Blob) {
      const text = await error.response.data.text()
      try {
        const parsed = JSON.parse(text)
        toast.error(parsed.error || 'Export failed')
      } catch {
        toast.error('Export failed')
      }
    } else {
      toast.error('Export failed')
    }
  } finally {
    isExporting.value = false
  }
}
</script>