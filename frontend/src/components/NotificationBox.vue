<template>
  <div class="notif-wrapper">
    <!-- Bell icon trigger -->
    <button class="notif-bell" @click="togglePanel" aria-label="Notifications">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
      </svg>
      <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount }}</span>
    </button>

    <!-- Dropdown panel -->
    <transition name="notif-slide">
      <div v-if="panelOpen" class="notif-panel" role="dialog" aria-label="Notification panel">

        <div class="notif-panel-header">
          <span class="notif-panel-title">Reminders</span>
          <button
            v-if="unreadCount > 0"
            class="notif-mark-all"
            @click="markAllRead"
          >Mark all read</button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="notif-state">
          <div class="notif-spinner"></div>
          <span>Checking your schedule…</span>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="notif-state notif-state--error">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>{{ error }}</span>
          <button class="notif-retry" @click="loadNotifications">Retry</button>
        </div>

        <!-- Empty -->
        <div v-else-if="notifications.length === 0" class="notif-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <span>No upcoming doses in the next hour.</span>
        </div>

        <!-- Notification list -->
        <ul v-else class="notif-list">
          <li
            v-for="notif in notifications"
            :key="notif.id"
            class="notif-item"
            :class="{ 'notif-item--unread': !notif.read }"
          >
            <div class="notif-item-icon">💊</div>
            <div class="notif-item-body">
              <p class="notif-item-title">{{ notif.drug_name }}</p>
              <p class="notif-item-sub">{{ notif.dose }} · {{ formatTime(notif.scheduled_time) }}</p>
            </div>
            <button
              v-if="!notif.read"
              class="notif-item-dismiss"
              aria-label="Dismiss"
              @click.stop="markRead(notif)"
            >✕</button>
          </li>
        </ul>

      </div>
    </transition>

    <!-- Backdrop -->
    <div v-if="panelOpen" class="notif-backdrop" @click="panelOpen = false"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Capacitor } from '@capacitor/core';
import { LocalNotifications } from '@capacitor/local-notifications';
import { ApiService } from '../api/client';

// ─── State ────────────────────────────────────────────────────────────────────

const notifications = ref([]);
const panelOpen     = ref(false);
const loading       = ref(false);
const error         = ref(null);

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);

// ─── Panel toggle ─────────────────────────────────────────────────────────────

function togglePanel() {
  panelOpen.value = !panelOpen.value;
}

// ─── Read state helpers ───────────────────────────────────────────────────────

function markRead(notif) {
  notif.read = true;
}

function markAllRead() {
  notifications.value.forEach(n => (n.read = true));
}

// ─── Time formatting ──────────────────────────────────────────────────────────

function formatTime(datetimeStr) {
  if (!datetimeStr) return '';
  // MySQL DATETIME "YYYY-MM-DD HH:MM:SS" → ISO "YYYY-MM-DDTHH:MM:SS"
  const iso = datetimeStr.replace(' ', 'T');
  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  }).format(new Date(iso));
}

// ─── Capacitor setup ──────────────────────────────────────────────────────────

async function setupCapacitorNotifications() {
  // 1. Request permissions
  const permStatus = await LocalNotifications.checkPermissions();
  if (permStatus.display !== 'granted') {
    const request = await LocalNotifications.requestPermissions();
    if (request.display !== 'granted') {
      console.warn('[Notifications] Permission denied — native alerts disabled.');
      return false;
    }
  }

  // 2. Create notification channel (required on Android 8+)
  if (Capacitor.getPlatform() === 'android') {
    await LocalNotifications.createChannel({
      id: 'medication_reminders',
      name: 'Medication Reminders',
      description: 'Alerts for upcoming medication doses',
      importance: 5,       // IMPORTANCE_HIGH — enables heads-up popup + sound
      sound: 'default',
      vibration: true,
      visibility: 1,       // VISIBILITY_PUBLIC
    });
  }

  return true;
}

async function scheduleCapacitorNotifications(dueMeds) {
  if (!dueMeds || dueMeds.length === 0) return;

  const allowed = await setupCapacitorNotifications();
  if (!allowed) return;

  const toSchedule = dueMeds.map(med => {
    // Normalise MySQL datetime string → ISO 8601 so `new Date()` is
    // consistent across browsers and mobile runtimes.
    const isoStr = med.scheduled_time.replace(' ', 'T');
    const fireAt  = new Date(isoStr);

    // Guard: skip dates that are already in the past or unparseable
    if (isNaN(fireAt.getTime()) || fireAt <= new Date()) {
      return null;
    }

    return {
      title: 'Medication Reminder',
      body: `Time to take your ${med.drug_name} — ${med.dose}`,
      id: parseInt(med.id, 10),       // Capacitor requires a JS integer
      channelId: 'medication_reminders',
      schedule: { at: fireAt },
      extra: { prescriptionId: med.prescription_id },
    };
  }).filter(Boolean);                 // remove nulls from past-date guard

  if (toSchedule.length > 0) {
    await LocalNotifications.schedule({ notifications: toSchedule });

    // Debug helper — remove before production
    const pending = await LocalNotifications.getPending();
    console.debug('[Notifications] Scheduled:', pending);
  }
}

// ─── Data loading ─────────────────────────────────────────────────────────────

async function loadNotifications() {
  loading.value = true;
  error.value   = null;

  try {
    const dueMeds = await ApiService.getDueNotifications();

    // Attach a reactive `read` flag that is local to this session
    notifications.value = (dueMeds ?? []).map(med => ({ ...med, read: false }));

    // Fire-and-forget: schedule native popups alongside the in-app list
    await scheduleCapacitorNotifications(notifications.value);

  } catch (err) {
    console.error('[Notifications] Failed to load:', err);
    error.value = 'Could not load your schedule. Check your connection.';
  } finally {
    loading.value = false;
  }
}

// ─── Bootstrap ────────────────────────────────────────────────────────────────

onMounted(loadNotifications);
</script>

<style scoped>
/* ── Wrapper & bell ─────────────────────────────────────── */
.notif-wrapper {
  position: relative;
  display: inline-flex;
}

.notif-bell {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  color: inherit;
  padding: 6px;
  border-radius: 8px;
  transition: background 0.15s;
}
.notif-bell:hover  { background: rgba(0, 0, 0, 0.06); }
.notif-bell:focus-visible { outline: 2px solid #4F8EF7; outline-offset: 2px; }

.notif-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 99px;
  background: #E5534B;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  line-height: 16px;
  text-align: center;
}

/* ── Backdrop ────────────────────────────────────────────── */
.notif-backdrop {
  position: fixed;
  inset: 0;
  z-index: 99;
}

/* ── Panel ───────────────────────────────────────────────── */
.notif-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  z-index: 100;
  width: 340px;
  max-height: 480px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #E8EAF0;
  border-radius: 14px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  display: flex;
  flex-direction: column;
}

.notif-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px 10px;
  border-bottom: 1px solid #F0F1F5;
  flex-shrink: 0;
}

.notif-panel-title {
  font-size: 14px;
  font-weight: 700;
  color: #1A1D23;
  letter-spacing: 0.01em;
}

.notif-mark-all {
  font-size: 12px;
  color: #4F8EF7;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}
.notif-mark-all:hover { text-decoration: underline; }

/* ── State views (loading / error / empty) ───────────────── */
.notif-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 36px 20px;
  color: #8B909E;
  font-size: 13px;
  text-align: center;
}

.notif-state--error { color: #C0392B; }

.notif-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid #E8EAF0;
  border-top-color: #4F8EF7;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.notif-retry {
  margin-top: 4px;
  font-size: 12px;
  color: #4F8EF7;
  background: none;
  border: 1px solid #4F8EF7;
  border-radius: 6px;
  padding: 4px 12px;
  cursor: pointer;
}

/* ── Notification list ───────────────────────────────────── */
.notif-list {
  list-style: none;
  margin: 0;
  padding: 6px 0;
}

.notif-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  transition: background 0.12s;
  cursor: default;
}
.notif-item:hover { background: #F7F8FC; }

.notif-item--unread {
  background: #F0F5FF;
}
.notif-item--unread:hover { background: #E8EFFF; }

.notif-item-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.notif-item-body {
  flex: 1;
  min-width: 0;
}

.notif-item-title {
  font-size: 13px;
  font-weight: 600;
  color: #1A1D23;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notif-item-sub {
  font-size: 12px;
  color: #8B909E;
  margin: 2px 0 0;
}

.notif-item-dismiss {
  flex-shrink: 0;
  background: none;
  border: none;
  color: #C0C4CC;
  font-size: 12px;
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 4px;
  transition: color 0.12s;
}
.notif-item-dismiss:hover { color: #E5534B; }

/* ── Panel transition ────────────────────────────────────── */
.notif-slide-enter-active,
.notif-slide-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}
.notif-slide-enter-from,
.notif-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>