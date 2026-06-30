import { ref } from 'vue'

// 🌐 Centralized prescription state accessible by Admin and Patient simultaneously
export const globalPrescriptions = ref([
  { patient: 'Encik Ahmad Bin Ali', name: 'Metformin HCL', form: 'Capsule', strength: '500mg', frequency: '2' },
  { patient: 'Puan Haida Binti Kamal', name: 'Insulin Glargine', form: 'Injection', strength: '10ml', frequency: '1' }
])

// Global method to insert new prescription sets dynamically
export function addPrescription(payload) {
  globalPrescriptions.value.push(payload)
}