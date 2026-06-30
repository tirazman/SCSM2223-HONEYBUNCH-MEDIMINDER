<template>
  <div class="container" style="max-width: 700px;">
    <div class="card">
      <h2>Adherence Summary</h2>
      <p>Generate a clinic-ready export of medication adherence for a chosen date range.</p>

      <!-- Caregiver/Admin must pick which patient they're viewing -->
      <div v-if="auth.getUserRole !== 'patient'" class="form-group">
        <label for="patient-select">Patient</label>
        <select id="patient-select" v-model="selectedPatientId" :disabled="isLoadingPatients">
          <option :value="null" disabled>{{ isLoadingPatients ? 'Loading patients…' : 'Select a patient' }}</option>
          <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>

      <!-- Date range filter -->
      <div style="display: flex; gap: 16px;">
        <div class="form-group" style="flex: 1;">
          <label for="from-date">From</label>
          <input id="from-date" v-model="fromDate" type="date" :max="toDate || undefined" />
        </div>
        <div class="form-group" style="flex: 1;">
          <label for="to-date">To</label>
          <input id="to-date" v-model="toDate" type="date" :min="fromDate || undefined" />
        </div>
      </div>

      <p v-if="!canExport" style="color: var(--status-danger); font-size: 13px; margin-top: -8px;">
        Select a patient before exporting.
      </p>

      <!-- Export buttons -->
      <div class="button-group" style="margin-top: 10px;">
        <button class="btn-success" :disabled="!!isExporting || !canExport" @click="exportAdherence('csv')">
          {{ isExporting === 'csv' ? 'Exporting…' : '⬇ Export CSV' }}
        </button>
        <button class="btn-danger" :disabled="!!isExporting || !canExport" @click="exportAdherence('pdf')">
          {{ isExporting === 'pdf' ? 'Exporting…' : '⬇ Export PDF' }}
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

const API_BASE = 'http://localhost:8000/api'

const route = useRoute()
const auth = useAuth()
const toast = useToast()

const patients = ref([])
const isLoadingPatients = ref(false)
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
    selectedPatientId.value = parseInt(route.query.patient_id, 10)
  }

  if (auth.getUserRole === 'caregiver' || auth.getUserRole === 'admin') {
    isLoadingPatients.value = true
    try {
      const endpoint = auth.getUserRole === 'caregiver' ? 'caregiver/patients' : 'admin/patients'
      const res = await axios.get(`${API_BASE}/${endpoint}`, {
        headers: { Authorization: `Bearer ${auth.token}` }
      })
      patients.value = res.data.patients ?? res.data
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to load patient list')
    } finally {
      isLoadingPatients.value = false
    }
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
      responseType: 'blob'
    })

    const blob = new Blob([response.data], {
      type: format === 'pdf' ? 'application/pdf' : 'text/csv'
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