<template>
  <div class="container" style="max-width: 1200px; margin: 30px auto; font-family: system-ui, -apple-system, sans-serif;">
    
    <header style="background: #ffffff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; border-left: 4px solid #10b981;">
      <h1 style="margin: 0; font-size: 22px; color: #1f2937;">Patient Dashboard - {{ loggedInPatient }}</h1>
      <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">Your active medical prescription schedule synchronized from database layers.</p>
    </header>

    <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 25px; align-items: start;">

      <div>
        <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
          <h2 style="margin: 0 0 6px 0; font-size: 18px; color: #111827;">My Schedule (Today)</h2>
          <p style="margin: 0 0 20px 0; font-size: 14px; color: #6b7280;">Be sure to mark your medication as soon as you consume it.</p>

          <div v-if="isLoading" style="text-align: center; padding: 50px 0; color: #9ca3af; font-style: italic;">
            <p>Loading patient directory data modules...</p>
          </div>

          <div v-else-if="myPrescriptions.length === 0" style="padding: 40px; text-align: center; color: #9ca3af; font-style: italic;">
            No prescriptions listed under your account profile context.
          </div>

          <div
            v-else
            v-for="(med, idx) in myPrescriptions"
            :key="idx"
            style="padding: 20px 15px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; transition: background 0.2s;"
            onmouseover="this.style.backgroundColor='#f9fafb'"
            onmouseout="this.style.backgroundColor='transparent'"
          >
            <div>
              <h3 style="margin: 0; font-size: 16px; color: #111827; font-weight: 600;">
                {{ med.name }} (Form: {{ med.form || 'Tablet' }})
              </h3>
              <p style="margin: 5px 0 0 0; font-size: 14px; color: #374151;">
                <strong>Dose / Strength:</strong> {{ med.strength || med.dose || 'N/A' }} | ⏰ {{ med.frequency }}x Daily
              </p>
              <p style="margin: 5px 0 0 0; font-size: 13px; color: #6b7280;">
                Notes: Enforced structural adherence rules active.
              </p>
            </div>

            <div>
              <div v-if="!med.status" style="display: flex; gap: 8px;">
                <button
                  style="padding: 8px 16px; font-size: 13px; font-weight: 600; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s;"
                  onmouseover="this.style.backgroundColor='#059669'"
                  onmouseout="this.style.backgroundColor='#10b981'"
                  @click="logDose(idx, 'taken')"
                >
                  Mark Taken
                </button>
                <button
                  style="padding: 8px 16px; font-size: 13px; font-weight: 600; background-color: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s;"
                  onmouseover="this.style.backgroundColor='#dc2626'"
                  onmouseout="this.style.backgroundColor='#ef4444'"
                  @click="logDose(idx, 'skipped')"
                >
                  Skip
                </button>
              </div>
              
              <div v-else>
                <div 
                  v-if="med.status === 'taken'"
                  style="display: inline-block; padding: 6px 12px; font-size: 12px; font-weight: 700; color: #065f46; background: #d1fae5; border-radius: 12px;"
                >
                  ✓ Taken Checked
                </div>
                <div 
                  v-else
                  style="display: inline-block; padding: 6px 12px; font-size: 12px; font-weight: 700; color: #991b1b; background: #fee2e2; border-radius: 12px;"
                >
                  ✗ Skipped Dosing
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
        
        <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; text-align: center;">
          <h2 style="margin: 0 0 6px 0; font-size: 16px; color: #374151;">7-Day Performance</h2>
          <div style="font-size: 48px; font-weight: bold; color: #10b981; margin: 15px 0;">
            86.5%
          </div>
          <p style="margin: 0; font-size: 13px; color: #6b7280;">Great progress! You missed only 2 doses this past week.</p>
        </div>

        <div style="background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
          <h3 style="margin: 0 0 12px 0; font-size: 16px; color: #374151;">Linked Caregiver</h3>
          <div style="font-size: 14px; color: #4b5563;">
            <p style="margin: 0 0 6px 0;"><strong>Name:</strong> Auni Sofiya</p>
            <p style="margin: 0 0 12px 0;"><strong>Email:</strong> aunisofi@gmail.com</p>
            <div style="display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: bold; color: #065f46; background: #d1fae5; border-radius: 6px;">
              Connected Status
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const loggedInPatient = ref('Encik Ahmad Bin Ali')
const prescriptions = ref([])
const isLoading = ref(true)

// Central local storage data alignment method
const loadPrescriptions = () => {
  const saved = localStorage.getItem('global_prescriptions')
  if (saved) {
    prescriptions.value = JSON.parse(saved)
  } else {
    // Structural database baseline array row data mapping sets
    prescriptions.value = [
      { patient: 'Encik Ahmad Bin Ali', name: 'Metformin HCL', form: 'Capsule', strength: '500mg', frequency: '2', status: null },
      { patient: 'Puan Haida Binti Kamal', name: 'Insulin Glargine', form: 'Injection', strength: '10ml', frequency: '1', status: null }
    ]
    localStorage.setItem('global_prescriptions', JSON.stringify(prescriptions.value))
  }
}

onMounted(() => {
  // Simulate database delay logic to trigger layout loading spinners cleanly
  setTimeout(() => {
    loadPrescriptions()
    isLoading.value = false
  }, 500)
})

// Computes and filters down exclusively to the active logged-in identifier name
const myPrescriptions = computed(() => {
  return prescriptions.value.filter(p => p.patient === loggedInPatient.value)
})

// Dynamic action callback modifier handling structural changes
function logDose(index, statusStr) {
  // Finds corresponding row references inside filtered array metrics mapping allocations
  const targetMed = myPrescriptions.value[index]
  if (targetMed) {
    targetMed.status = statusStr
    // Save state update cleanly back into continuous localStorage browser context
    localStorage.setItem('global_prescriptions', JSON.stringify(prescriptions.value))
  }
}
</script>