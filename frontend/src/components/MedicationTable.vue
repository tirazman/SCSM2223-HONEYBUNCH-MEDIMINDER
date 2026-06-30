<template>
  <table style="font-size: 14px;">
    <thead>
      <tr>
        <th>Medication Name</th>
        <th>Dose Time</th>
        <th>Actual Check-In</th>
        <th>Status Badge</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="med in medicationList" :key="med.name">
        <td>{{ med.name }}</td>
        <td>{{ med.doseTime }}</td>
        <td>{{ med.checkIn }}</td>
        <td>
          <strong 
            class="badge" 
            :class="med.badgeClass"
            style="cursor: pointer;"
            @click="handleBadgeClick(med)"
          >
            {{ med.status }}
          </strong>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
// 1. Declare Props to receive the medication list stream
defineProps({
  medicationList: {
    type: Array,
    required: true
  }
})

// 2. Declare Emits to handle dynamic user interactions back to parent view
const emit = defineEmits(['inspect-medication'])

function handleBadgeClick(med) {
  emit('inspect-medication', med)
}
</script>