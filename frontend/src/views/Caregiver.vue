<template>
  <div class="container">
    <div class="card">
      <h2>My Supervised Patients</h2>
      <p>Real-time updates on scheduled medicine ingestion across your linked accounts.</p>

      <div v-if="isLoading" style="text-align: center; padding: 20px; color: var(--text-muted);">
        Loading supervised patients...
      </div>

      <div v-else-if="loadError" style="background-color: #fef2f2; padding: 15px; border-radius: 6px; border: 1px solid #fca5a5;">
        <p style="color: var(--status-danger); margin: 0;">{{ loadError }}</p>
      </div>

      <div v-else-if="patients.length === 0" style="text-align: center; padding: 30px; color: var(--text-muted); font-style: italic;">
        No patients are linked to your account yet.
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
              DOB: {{ patient.dob || 'N/A' }} | Linked Account: {{ patient.email }}
            </p>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 14px; font-weight: bold;">7-Day Rate:</div>
            <strong class="badge" :class="patient.rateClass" style="font-size: 16px;">
              {{ patient.rate }}
            </strong>
            <div style="margin-top: 8px;">
              <router-link :to="`/adherence?patient_id=${patient.id}`" class="btn-export-report">
                Export Report
              </router-link>
            </div>
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
import axios from 'axios'
import { useAuth } from '../stores/auth'
import MedicationTable from '../components/MedicationTable.vue'

const API_BASE = 'http://localhost:8000/api'

const auth = useAuth()
const patients = ref([])
const isLoading = ref(true)
const loadError = ref('')

function authHeaders() {
  return { Authorization: `Bearer ${auth.token}` }
}

// Maps a raw dose_logs row from the API into what MedicationTable expects.
function mapDoseLog(log) {
  const badgeClass =
    log.status === 'taken' ? 'bg-success' :
    log.status === 'skipped' ? 'bg-danger' :
    'bg-pending'

  return {
    name: `${log.drug_name} ${log.dose ?? ''}`.trim(),
    doseTime: log.scheduled_at,
    checkIn: log.taken_at || '--',
    status: log.status,
    badgeClass
  }
}

async function loadPatients() {
  isLoading.value = true
  loadError.value = ''

  try {
    const { data } = await axios.get(`${API_BASE}/caregiver/patients`, {
      headers: authHeaders()
    })

    const linkedPatients = data.patients ?? []

    // For each linked patient, fetch today's dose logs + their 7-day adherence rate.
    const today = new Date().toISOString().slice(0, 10)

    const enriched = await Promise.all(
      linkedPatients.map(async (patient) => {
        let meds = []
        let alert = null
        let rate = null

        try {
          const [logsRes, adherenceRes] = await Promise.all([
            axios.get(`${API_BASE}/dose-logs`, {
              params: { patient_id: patient.id, from_date: today, to_date: today },
              headers: authHeaders()
            }),
            axios.get(`${API_BASE}/dose-logs/adherence`, {
              params: { patient_id: patient.id },
              headers: authHeaders()
            })
          ])

          const logs = logsRes.data.dose_logs ?? []
          meds = logs.map(mapDoseLog)

          const missed = logs.find((l) => l.status === 'skipped')
          alert = missed
            ? `Missed dose: ${missed.drug_name} was scheduled for ${missed.scheduled_at} today.`
            : null

          const stats = adherenceRes.data.adherence ?? {}
          if (typeof stats.adherence_rate === 'number') {
            rate = Math.round(stats.adherence_rate)
          }
        } catch (err) {
          // If logs fail to load for one patient, still show the patient card —
          // just without medication detail, rather than failing the whole list.
          console.error(`Failed to load dose logs for patient ${patient.id}`, err)
        }

        const isGood = rate === null ? true : rate >= 80

        return {
          id: patient.id,
          name: patient.name,
          email: patient.email,
          dob: patient.dob,
          rate: rate === null ? 'N/A' : `${rate}% Adherent`,
          rateClass: isGood ? 'bg-success' : 'bg-danger',
          borderColor: isGood ? 'var(--primary-honey)' : 'var(--status-danger)',
          alert,
          meds
        }
      })
    )

    patients.value = enriched
  } catch (error) {
    loadError.value = error.response?.data?.error || 'Failed to load supervised patients.'
    console.error('Failed to load caregiver data', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(loadPatients)

function handleMedicationInspection(medication) {
  console.log(`Inspecting log indices for medication entry: ${medication.name}`)
}
</script>