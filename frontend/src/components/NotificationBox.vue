<template>
  <div class="notification-box">
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { LocalNotifications } from '@capacitor/local-notifications';
import { ApiService } from '../api/client';

onMounted(async () => {
  // 1. Request permissions (Member 4 - DevOps)
  let permStatus = await LocalNotifications.checkPermissions();
  if (permStatus.display !== 'granted') {
    await LocalNotifications.requestPermissions();
  }

  // 2. Fetch due medications and schedule
  const dueMeds = await ApiService.getDueNotifications(); // Call your API
  
  if (dueMeds && dueMeds.length > 0) {
    await LocalNotifications.schedule({
      notifications: dueMeds.map(med => ({
        title: "Medication Reminder",
        body: `It's time to take your ${med.drug_name}`,
        id: med.id,
        schedule: { at: new Date(med.scheduled_time) }
      }))
    });
  }
});
</script>