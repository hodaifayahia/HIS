<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'

import Toast from 'primevue/toast'

const router = useRouter()
const loading = ref(false)
const sessions = ref([])
// This controls whether to show "My Caisse Sessions" section
const showSessions = ref(true)

const formatCurrency = (v) => {
  if (v === null || v === undefined) return '—'
  return new Intl.NumberFormat('fr-DZ', { style: 'currency', currency: 'DZD', minimumFractionDigits: 2 }).format(Number(v))
}
const formatDateTime = (d) => d ? new Date(d).toLocaleString() : '—'
const statusSeverity = (st) => {
  if (!st) return 'secondary'
  return st === 'open' ? 'success' : st === 'closed' ? 'info' : 'warning'
}

const loadSessions = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/caisse-sessions-authUser', {
      headers: { Accept: 'application/json' },
      withCredentials: true
    })
    
    const data = res?.data?.data ?? {}
    console.log('Response data:', data)
    
    // Get active sessions and mark them as non-transfers
    const activeSessions = (data.sessions || []).map(session => ({
      ...session,
      is_transfer: false
    }))
    
    // Format transfers to match the expected structure
    const formattedTransfers = (data.transfers || []).map(transfer => ({
      id: transfer.id,
      caisse_id: transfer.caisse_id,
      user_id: transfer.to_user_id,
      caisse_session_id: transfer.caisse_session_id,
      status: 'transferred',
      opening_amount: transfer.amount_sended,
      ouverture_at: transfer.created_at,
      transfer_id: transfer.id,
      caisse: transfer.caisse,
      user: transfer.toUser,
      from_user: transfer.fromUser,
      is_transfer: true,
      transfer_data: transfer
    }))
    
    // Group by caisse_id to identify duplicates and prefer active sessions
    const sessionMap = new Map()
    
    // First add all active sessions
    activeSessions.forEach(session => {
      sessionMap.set(session.caisse_id, session)
    })
    
    // Then add transfers only if there's no active session for that caisse
    formattedTransfers.forEach(transfer => {
      if (!sessionMap.has(transfer.caisse_id)) {
        sessionMap.set(transfer.caisse_id, transfer)
      }
    })
    
    // Convert Map back to array
    sessions.value = Array.from(sessionMap.values())
  } catch (e) {
    console.error('Failed to load sessions', e)
    sessions.value = []
  } finally {
    loading.value = false
  }
}

const goToPatientList = (session) => {
  // Add null checks and fallbacks
  console.log(session);
  
  let sessionId;
  if (session.is_transfer) {
    // For transfers, use caisse_session_id or fallback to transfer_id
    sessionId = (session.caisse_session_id || session.transfer_id || session.id || '').toString();
  } else {
    // For active sessions, use id
    sessionId = (session.id || '').toString();
  }

  // Ensure we have a valid session ID
  if (!sessionId) {
    console.error('Invalid session ID');
    return;
  }

  const caisseId = (session.caisse_id || '').toString();
  if (!caisseId) {
    console.error('Invalid caisse ID');
    return;
  }

  router.push({
    name: 'patients.with-session',
    params: {
      id: session.id,
      caisse_session_id: sessionId,
      caisse_id: caisseId
    }
  })
}

onMounted(async () => {
  await loadSessions()
})
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen tw-font-sans">
    <div class="tw-max-w-4xl tw-mx-auto tw-p-6 lg:tw-p-8">
      
      <!-- My Caisse Sessions section - only shown when showSessions is true -->
      <div v-if="showSessions">
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
          <h2 class="tw-text-3xl tw-font-bold tw-text-gray-800">My Caisse Sessions</h2>
          <Button
            icon="pi pi-refresh"
            :loading="loading"
            class="p-button-text"
            @click="loadSessions"
            v-tooltip.top="'Refresh sessions'"
          />
        </div>

        <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
          <ProgressSpinner class="tw-w-16 tw-h-16" strokeWidth="4" animationDuration=".5s" />
        </div>

        <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
          <Card
            v-for="s in sessions"
            :key="s.id"
            :class="[
              'tw-rounded-xl tw-shadow-lg tw-border tw-transition-all tw-duration-300 hover:tw-shadow-xl hover:tw-scale-[1.02] tw-cursor-pointer',
              s.is_transfer ? 'tw-border-blue-200 tw-bg-blue-50/30' : 'tw-border-gray-200'
            ]"
            @click="goToPatientList(s)"
          >
            <template #header>
              <div class="tw-p-5 tw-bg-gray-50 tw-rounded-t-xl tw-flex tw-justify-between tw-items-start">
                <div>
                  <div class="tw-text-sm tw-text-gray-500 tw-font-medium">
                    {{ s.is_transfer ? 'Transferred Caisse' : 'Caisse' }}
                  </div>
                  <div class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mt-1">
                    {{ s.caisse?.name ?? '—' }}
                  </div>
                  <div v-if="s.is_transfer" class="tw-text-sm tw-text-blue-600 tw-mt-1">
                    Transferred from {{ s.from_user?.name ?? '—' }}
                  </div>
                </div>
                <div class="tw-text-right">
                  <div class="tw-text-xs tw-text-gray-400">{{ formatDateTime(s.ouverture_at) }}</div>
                  <Tag 
                    :value="s.is_transfer ? 'Transferred' : (s.status ?? 'unknown')" 
                    :severity="s.is_transfer ? 'info' : statusSeverity(s.status)" 
                    class="tw-mt-2" 
                  />
                </div>
              </div>
            </template>

            <template #content>
              <div class="tw-p-5 tw-flex-1">
                <div class="tw-text-sm tw-text-gray-500">Opening amount</div>
                <div class="tw-text-xl tw-font-bold tw-text-green-600 tw-mt-1">{{ formatCurrency(s.opening_amount) }}</div>
              </div>
            </template>

            <template #footer>
              <div class="tw-flex tw-justify-end tw-p-5 tw-border-t tw-border-gray-200">
                <Button label="View Details" icon="pi pi-eye" class="p-button-text p-button-sm" @click.stop="goToPatientList(s)" />
              </div>
            </template>
          </Card>

          <div v-if="sessions.length === 0" class="tw-col-span-full tw-text-center tw-py-16">
            <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
            <p class="tw-text-xl tw-font-semibold tw-text-gray-700">No active sessions found.</p>
            <p class="tw-text-gray-500 tw-mt-2">Check back later or start a new session from the main dashboard.</p>
          </div>
        </div>
      </div>

      <!-- Message when sessions are hidden -->
      <div v-else class="tw-text-center tw-py-16">
        <i class="pi pi-lock tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
        <p class="tw-text-xl tw-font-semibold tw-text-gray-700">Sessions Access Restricted</p>
        <p class="tw-text-gray-500 tw-mt-2">You cannot access sessions due to transfer status.</p>
      </div>
    </div>

    <Toast />
  </div>
</template>

<style scoped>

/*
 * Minimal CSS is needed when using Tailwind.
 * The following section contains overrides for PrimeVue components
 * that are styled using the `@apply` directive.
 */
/* :deep(.p-card-header) {
    @apply tw-p-0;
}
:deep(.p-card-content) {
    @apply tw-p-0;
}
:deep(.p-button.p-button-sm) {
  @apply tw-px-3 tw-py-1;
}
:deep(.p-dialog .p-dialog-header) {
  @apply tw-p-4 tw-md:p-6 tw-bg-gray-100 tw-text-gray-800 tw-rounded-t-xl tw-border-b tw-border-gray-200;
}
:deep(.p-dialog .p-dialog-content) {
  @apply tw-p-4 tw-md:p-6;
}
:deep(.p-dialog .p-dialog-footer) {
  @apply tw-p-4 tw-md:p-6 tw-bg-gray-100 tw-rounded-b-xl tw-border-t tw-border-gray-200 tw-flex tw-justify-end tw-gap-2;
} */
</style>