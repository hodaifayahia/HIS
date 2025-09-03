<template>
  <div class="tw-p-6 tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-container tw-mx-auto">
      <PageHeader
        title="Approval Dashboard"
        subtitle="Review and act on pending payment approval requests.."
        current-breadcrumb="Approvals"
      >
        <template #actions>
          <Button
            icon="pi pi-refresh"
            label="Refresh"
            @click="loadPendingApprovals"
            :loading="loading"
            size="small"
          />
        </template>
      </PageHeader>
      
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-6">
        <div class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4 tw-shadow-sm">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-blue-600 tw-text-sm tw-font-medium">Pending Requests</p>
              <p class="tw-text-2xl tw-font-bold tw-text-blue-900">{{ pendingRequests.length }}</p>
            </div>
            <i class="pi pi-clock tw-text-blue-400 tw-text-2xl"></i>
          </div>
        </div>
        
        <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-4 tw-shadow-sm">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-green-600 tw-text-sm tw-font-medium">Total Amount</p>
              <p class="tw-text-2xl tw-font-bold tw-text-green-900">{{ formatAmount(totalAmount) }} DH</p>
            </div>
            <i class="pi pi-money-bill tw-text-green-400 tw-text-2xl"></i>
          </div>
        </div>

        <div class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-4 tw-shadow-sm">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-amber-600 tw-text-sm tw-font-medium">Oldest Request</p>
              <p class="tw-text-sm tw-font-medium tw-text-amber-900">{{ oldestRequestTime }}</p>
            </div>
            <i class="pi pi-calendar tw-text-amber-400 tw-text-2xl"></i>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200">
        <div class="tw-p-6 tw-border-b tw-border-gray-200">
          <h2 class="tw-text-lg tw-font-semibold tw-text-gray-800">Pending Approval Requests</h2>
        </div>

        <div v-if="loading" class="tw-p-8 tw-text-center">
          <ProgressSpinner />
          <p class="tw-mt-2 tw-text-gray-600">Loading requests...</p>
        </div>

        <div v-else-if="pendingRequests.length === 0" class="tw-p-8 tw-text-center tw-text-gray-500">
          <i class="pi pi-inbox tw-text-4xl tw-text-gray-300 tw-mb-3"></i>
          <p>No pending approval requests</p>
        </div>

        <div v-else class="tw-divide-y tw-divide-gray-200">
          <div 
            v-for="request in pendingRequests" 
            :key="request.id"
            class="tw-p-6 hover:tw-bg-gray-50 tw-transition-colors"
          >
            <div class="tw-flex tw-items-start tw-justify-between">
              <div class="tw-flex-1">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
                  <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-user tw-text-blue-600"></i>
                  </div>
                  <div>
                    <h3 class="tw-font-medium tw-text-gray-900">{{ request.requester?.name || 'Unknown User' }}</h3>
                    <p class="tw-text-sm tw-text-gray-500">{{ formatDate(request.requested_at) }}</p>
                  </div>
                </div>

                <div class="tw-bg-gray-50 tw-rounded-lg tw-p-4 tw-mb-4 tw-border tw-border-gray-200">
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-3 tw-text-sm">
                    <div class="tw-flex tw-flex-col">
                      <span class="tw-font-medium tw-text-gray-700">Method:</span>
                      <span class="tw-text-gray-900 tw-mt-1 tw-font-semibold">{{ request.payment_method }}</span>
                    </div>
                    <div class="tw-flex tw-flex-col">
                      <span class="tw-font-medium tw-text-gray-700">Amount:</span>
                      <span class="tw-text-gray-900 tw-mt-1 tw-font-semibold">{{ formatAmount(request.amount) }} DH</span>
                    </div>
                    <div class="tw-flex tw-flex-col">
                      <span class="tw-font-medium tw-text-gray-700">Type:</span>
                      <span class="tw-text-gray-900 tw-mt-1 tw-font-semibold">{{ formatItemType(request.item_type) }}</span>
                    </div>
                    <div class="tw-flex tw-flex-col">
                      <span class="tw-font-medium tw-text-gray-700">Item:</span>
                      <span class="tw-text-gray-900 tw-mt-1 tw-font-semibold">{{ getItemName(request) }}</span>
                    </div>
                  </div>
                </div>

                <div v-if="request.notes">
                  <p class="tw-text-sm tw-text-gray-700">
                    <span class="tw-font-medium">Notes:</span>
                    <span class="tw-text-gray-600 tw-italic tw-ml-1">{{ request.notes }}</span>
                  </p>
                </div>
              </div>

              <div class="tw-flex tw-gap-2 tw-ml-4 tw-self-center">
                <Button
                  icon="pi pi-check"
                  label="Approve"
                  severity="success"
                  size="small"
                  @click="approveRequest(request)"
                  :loading="processingId === request.id"
                />
                <Button
                  icon="pi pi-times"
                  label="Reject"
                  severity="danger"
                  size="small"
                  outlined
                  @click="openRejectDialog(request)"
                  :disabled="processingId === request.id"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog 
      v-model:visible="rejectDialogVisible" 
      modal 
      header="Reject Payment Request"
      :style="{ width: '400px' }"
    >
      <div class="tw-space-y-4">
        <p class="tw-text-gray-700">Are you sure you want to reject this payment request?</p>
        
        <div class="tw-flex tw-flex-col tw-gap-2">
          <label for="rejectNotes" class="tw-font-medium tw-text-gray-700">
            Rejection Reason <span class="tw-text-red-500">*</span>
          </label>
          <Textarea
            id="rejectNotes"
            v-model="rejectNotes"
            placeholder="Please provide a reason for rejection..."
            rows="3"
            class="tw-w-full tw-border-gray-300 tw-rounded-md tw-shadow-sm"
            :class="{ 'p-invalid': !rejectNotes.trim() }"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-2">
          <Button
            label="Cancel"
            severity="secondary"
            @click="rejectDialogVisible = false"
            :disabled="processingReject"
          />
          <Button
            label="Reject"
            severity="danger"
            @click="confirmReject"
            :loading="processingReject"
            :disabled="!rejectNotes.trim()"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import ProgressSpinner from 'primevue/progressspinner'
import axios from 'axios'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import PageHeader from '../Components/Apps/Configuration/RemiseMangement/PaymentMethod/PageHeader.vue';

const toast = useToast()

// Reactive data
const pendingRequests = ref([])
const loading = ref(false)
const processingId = ref(null)
const rejectDialogVisible = ref(false)
const selectedRequest = ref(null)
const rejectNotes = ref('')
const processingReject = ref(false)

// Computed
const totalAmount = computed(() => {
  return pendingRequests.value.reduce((sum, request) => sum + (request.amount || 0), 0)
})

const oldestRequestTime = computed(() => {
  if (pendingRequests.value.length === 0) return '--'
  
  const oldest = pendingRequests.value.reduce((oldest, request) => {
    return new Date(request.requested_at) < new Date(oldest.requested_at) ? request : oldest
  })
  
  const now = new Date()
  const requestTime = new Date(oldest.requested_at)
  const diffInHours = Math.floor((now - requestTime) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'Less than 1 hour'
  if (diffInHours < 24) return `${diffInHours} hours ago`
  return `${Math.floor(diffInHours / 24)} days ago`
})

// Methods
const loadPendingApprovals = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/transaction-bank-requests?for_approval')
    pendingRequests.value = response.data?.data ?? response.data ?? []
  } catch (error) {
    console.error('Error loading pending approvals:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pending approvals',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const approveRequest = async (request) => {
  processingId.value = request.id
  
  try {
    await axios.patch(`/api/transaction-bank-requests/${request.id}/status`, {
      status: 'approved',
      notes: 'Approved via dashboard'
    })
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Payment request approved successfully',
      life: 5000
    })
    
    pendingRequests.value = pendingRequests.value.filter(r => r.id !== request.id)
    
  } catch (error) {
    console.error('Error approving request:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to approve request',
      life: 5000
    })
  } finally {
    processingId.value = null
  }
}

const openRejectDialog = (request) => {
  selectedRequest.value = request
  rejectNotes.value = ''
  rejectDialogVisible.value = true
}

const confirmReject = async () => {
  if (!rejectNotes.value.trim()) return
  
  processingReject.value = true
  
  try {
    await axios.patch(`/api/transaction-bank-requests/${selectedRequest.value.id}/status`, {
      status: 'rejected',
      notes: rejectNotes.value
    })
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Payment request rejected',
      life: 5000
    })
    
    pendingRequests.value = pendingRequests.value.filter(r => r.id !== selectedRequest.value.id)
    rejectDialogVisible.value = false
    
  } catch (error) {
    console.error('Error rejecting request:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to reject request',
      life: 5000
    })
  } finally {
    processingReject.value = false
  }
}

const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatItemType = (itemType) => {
  switch (itemType) {
    case 'fiche_navette_item':
      return 'Fiche Navette'
    case 'item_dependency':
      return 'Dependency'
    default:
      return itemType
  }
}

const getItemName = (request) => {
  if (request.fiche_navette_item) {
    return request.fiche_navette_item.prestation?.nom || 'Prestation'
  }
  return 'Item'
}

// Lifecycle
onMounted(() => {
  loadPendingApprovals()
})
</script>

<style scoped>
.tw-approval-dashboard {
  @apply tw-p-6 tw-max-w-7xl tw-mx-auto;
}

.p-invalid {
  @apply tw-border-red-500;
}
</style>