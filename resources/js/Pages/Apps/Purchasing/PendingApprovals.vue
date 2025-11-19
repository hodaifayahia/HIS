<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">Pending Approvals</h1>
          <p class="tw-text-gray-600 tw-mt-1">Review and process bon commend approval requests</p>
        </div>
        <Button 
          @click="fetchPendingApprovals" 
          label="Refresh" 
          icon="pi pi-refresh" 
          class="p-button-secondary"
        />
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-6">
      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-blue-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Total Requests</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ statistics.total || 0 }}</p>
          </div>
          <div class="tw-bg-blue-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-list tw-text-2xl tw-text-blue-600"></i>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-yellow-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Pending</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ statistics.pending || 0 }}</p>
          </div>
          <div class="tw-bg-yellow-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-clock tw-text-2xl tw-text-yellow-600"></i>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-green-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Approved</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ statistics.approved || 0 }}</p>
          </div>
          <div class="tw-bg-green-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-check-circle tw-text-2xl tw-text-green-600"></i>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-red-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Rejected</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ statistics.rejected || 0 }}</p>
          </div>
          <div class="tw-bg-red-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-times-circle tw-text-2xl tw-text-red-600"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Approvals List -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-overflow-hidden">
      <div class="tw-p-6 tw-border-b tw-border-gray-200">
        <h2 class="tw-text-xl tw-font-semibold tw-text-gray-800">Pending Approval Requests</h2>
      </div>

      <DataTable 
        :value="pendingApprovals" 
        :loading="loading"
        stripedRows
        class="tw-w-full"
      >
        <template #empty>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-inbox tw-text-4xl tw-text-gray-400 tw-mb-4"></i>
            <p class="tw-text-gray-500">No pending approvals</p>
          </div>
        </template>

        <Column field="bon_commend.code" header="Bon Commend" sortable>
          <template #body="slotProps">
            <div>
              <div class="tw-font-semibold tw-text-gray-900">{{ slotProps.data.bon_commend.code }}</div>
              <div class="tw-text-sm tw-text-gray-500">
                {{ slotProps.data.bon_commend.supplier?.name || 'N/A' }}
              </div>
            </div>
          </template>
        </Column>

        <Column field="amount" header="Amount" sortable>
          <template #body="slotProps">
            <div class="tw-font-semibold tw-text-blue-600 tw-text-lg">
              {{ formatCurrency(slotProps.data.amount) }}
            </div>
          </template>
        </Column>

        <Column field="requester.name" header="Requested By" sortable>
          <template #body="slotProps">
            <div>
              <div class="tw-font-medium tw-text-gray-900">{{ slotProps.data.requester.name }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.requester.email }}</div>
            </div>
          </template>
        </Column>

        <Column field="notes" header="Notes">
          <template #body="slotProps">
            <span class="tw-text-gray-700 tw-text-sm">{{ slotProps.data.notes || 'No notes' }}</span>
          </template>
        </Column>

        <Column field="created_at" header="Requested At" sortable>
          <template #body="slotProps">
            <span class="tw-text-gray-600 tw-text-sm">{{ formatDate(slotProps.data.created_at) }}</span>
          </template>
        </Column>

        <Column header="Actions" style="width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button 
                @click="viewDetails(slotProps.data)" 
                icon="pi pi-eye" 
                class="p-button-rounded p-button-text p-button-info"
                v-tooltip.top="'View Details'"
              />
              <Button 
                @click="approveRequest(slotProps.data)" 
                icon="pi pi-check" 
                class="p-button-rounded p-button-text p-button-success"
                v-tooltip.top="'Approve'"
              />
              <Button 
                @click="rejectRequest(slotProps.data)" 
                icon="pi pi-times" 
                class="p-button-rounded p-button-text p-button-danger"
                v-tooltip.top="'Reject'"
              />
              <Button
                @click="openSendBack(slotProps.data)"
                icon="pi pi-reply"
                class="p-button-rounded p-button-text"
                v-tooltip.top="'Send Back for Correction'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Details Dialog -->
    <Dialog 
      v-model:visible="detailsDialog" 
      header="Approval Request Details"
      :modal="true"
      class="tw-w-full tw-max-w-4xl"
    >
      <div v-if="selectedApproval" class="tw-space-y-6">
        <!-- Bon Commend Info -->
        <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-3">Bon Commend Information</h3>
          <div class="tw-grid tw-grid-cols-2 tw-gap-4">
            <div>
              <p class="tw-text-sm tw-text-gray-600">Code:</p>
              <p class="tw-font-semibold tw-text-gray-900">{{ selectedApproval.bon_commend.code }}</p>
            </div>
            <div>
              <p class="tw-text-sm tw-text-gray-600">Supplier:</p>
              <p class="tw-font-semibold tw-text-gray-900">{{ selectedApproval.bon_commend.supplier?.name || 'N/A' }}</p>
            </div>
            <div>
              <p class="tw-text-sm tw-text-gray-600">Total Amount:</p>
              <p class="tw-font-semibold tw-text-blue-600 tw-text-xl">{{ formatCurrency(selectedApproval.bon_commend.total_amount) }}</p>
            </div>
            <div>
              <p class="tw-text-sm tw-text-gray-600">Status:</p>
              <Tag :value="selectedApproval.bon_commend.status" severity="info" />
            </div>
          </div>
        </div>

        <!-- Request Info -->
        <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-3">Request Information</h3>
          <div class="tw-grid tw-grid-cols-2 tw-gap-4">
            <div>
              <p class="tw-text-sm tw-text-gray-600">Requested By:</p>
              <p class="tw-font-semibold tw-text-gray-900">{{ selectedApproval.requester.name }}</p>
              <p class="tw-text-sm tw-text-gray-500">{{ selectedApproval.requester.email }}</p>
            </div>
            <div>
              <p class="tw-text-sm tw-text-gray-600">Requested At:</p>
              <p class="tw-font-semibold tw-text-gray-900">{{ formatDate(selectedApproval.created_at) }}</p>
            </div>
          </div>
          <div v-if="selectedApproval.notes" class="tw-mt-4">
            <p class="tw-text-sm tw-text-gray-600 tw-mb-1">Notes:</p>
            <p class="tw-text-gray-900 tw-bg-white tw-p-3 tw-rounded tw-border tw-border-gray-200">{{ selectedApproval.notes }}</p>
          </div>
        </div>

        <!-- Items List -->
        <div v-if="selectedApproval.bon_commend.items && selectedApproval.bon_commend.items.length > 0" class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-p-4">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-3">Items</h3>
          <div class="tw-overflow-x-auto">
            <table class="tw-w-full tw-text-sm">
              <thead class="tw-bg-gray-50">
                <tr>
                  <th class="tw-p-2 tw-text-left">Product</th>
                  <th class="tw-p-2 tw-text-right">Quantity</th>
                  <th class="tw-p-2 tw-text-right">Price</th>
                  <th class="tw-p-2 tw-text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedApproval.bon_commend.items" :key="item.id" class="tw-border-t">
                  <td class="tw-p-2">{{ item.product_name }}</td>
                  <td class="tw-p-2 tw-text-right">{{ item.quantity_desired ?? item.quantity }}</td>
                  <td class="tw-p-2 tw-text-right">{{ formatCurrency(item.price) }}</td>
                  <td class="tw-p-2 tw-text-right tw-font-semibold">{{ formatCurrency(item.price * (item.quantity_desired ?? item.quantity)) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <template #footer>
        <Button 
          @click="detailsDialog = false" 
          label="Close" 
          icon="pi pi-times" 
          class="p-button-text"
        />
        <Button 
          @click="rejectRequest(selectedApproval)" 
          label="Reject" 
          icon="pi pi-times-circle" 
          class="p-button-danger"
        />
        <Button 
          @click="approveRequest(selectedApproval)" 
          label="Approve" 
          icon="pi pi-check-circle" 
          class="p-button-success"
        />
      </template>
    </Dialog>

    <!-- Send Back Dialog -->
    <Dialog
      v-model:visible="sendBackDialog"
      header="Send Back for Correction"
      :modal="true"
      class="tw-w-full tw-max-w-3xl"
    >
      <div v-if="selectedApproval" class="tw-space-y-4">
        <p class="tw-text-sm tw-text-gray-600">Edit the quantities for the items you want the requester to correct, then send back the request.</p>

        <div class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-p-4">
          <div class="tw-overflow-auto">
            <table class="tw-w-full tw-text-sm">
              <thead>
                <tr class="tw-bg-gray-50">
                  <th class="tw-p-2 tw-text-left">Product</th>
                  <th class="tw-p-2 tw-text-right">Price</th>
                  <th class="tw-p-2 tw-text-right">Original Qty</th>
                  <th class="tw-p-2 tw-text-right">New Qty</th>
                  <th class="tw-p-2 tw-text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in sendBackItems" :key="item.item_id">
                  <td class="tw-p-2">{{ item.product_name }}</td>
                  <td class="tw-p-2 tw-text-right">{{ formatCurrency(item.price) }}</td>
                  <td class="tw-p-2 tw-text-right">{{ item.original_quantity }}</td>
                  <td class="tw-p-2">
                    <input type="number" class="tw-border tw-px-2 tw-py-1 tw-rounded tw-w-20" v-model.number="sendBackItems[idx].quantity_desired" min="0" />
                  </td>
                  <td class="tw-p-2 tw-text-right tw-font-semibold">{{ formatCurrency(item.price * sendBackItems[idx].quantity_desired) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes (optional)</label>
          <Textarea v-model="sendBackNotes" rows="4" class="tw-w-full" />
        </div>
      </div>

      <template #footer>
        <Button @click="sendBackDialog = false" label="Cancel" class="p-button-text" icon="pi pi-times" />
        <Button @click="submitSendBack" :loading="processing" label="Send Back" class="p-button-warning" icon="pi pi-reply" />
      </template>
    </Dialog>

    <!-- Approval Notes Dialog -->
    <Dialog 
      v-model:visible="notesDialog" 
      :header="notesDialogTitle"
      :modal="true"
      class="tw-w-full tw-max-w-md"
    >
      <div class="tw-py-4">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          {{ notesDialogAction === 'approve' ? 'Approval' : 'Rejection' }} Notes (Optional)
        </label>
        <Textarea 
          v-model="approvalNotes" 
          rows="5"
          placeholder="Add notes about your decision..."
          class="tw-w-full"
        />
      </div>

      <template #footer>
        <Button 
          @click="notesDialog = false" 
          label="Cancel" 
          icon="pi pi-times" 
          class="p-button-text"
        />
        <Button 
          @click="processApproval" 
          :label="notesDialogAction === 'approve' ? 'Approve' : 'Reject'" 
          :icon="notesDialogAction === 'approve' ? 'pi pi-check' : 'pi pi-times'"
          :loading="processing"
          :class="notesDialogAction === 'approve' ? 'p-button-success' : 'p-button-danger'"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'

const toast = useToast()

// Reactive state
const loading = ref(true)
const processing = ref(false)
const pendingApprovals = ref([])
const selectedApproval = ref(null)
const detailsDialog = ref(false)
const notesDialog = ref(false)
const notesDialogAction = ref('approve') // 'approve' or 'reject'
const approvalNotes = ref('')
const statistics = reactive({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0
})

const notesDialogTitle = computed(() => {
  return notesDialogAction.value === 'approve' 
    ? 'Approve Bon Commend' 
    : 'Reject Bon Commend'
})

// Send back dialog state
const sendBackDialog = ref(false)
const sendBackItems = ref([])
const sendBackNotes = ref('')

const openSendBack = (approval) => {
  selectedApproval.value = approval
  // Deep clone items so we can edit quantities locally
  sendBackItems.value = (approval.bon_commend.items || []).map(i => ({
    item_id: i.id,
    product_name: i.product?.name || i.product_name || 'Unknown',
    price: i.price || 0,
    original_quantity: i.quantity_desired ?? i.quantity,
    quantity_desired: i.quantity_desired ?? i.quantity
  }))
  sendBackNotes.value = ''
  detailsDialog.value = false
  sendBackDialog.value = true
}

const submitSendBack = async () => {
  try {
    processing.value = true
    const modified_items = sendBackItems.value.map(i => ({ item_id: i.item_id, quantity_desired: i.quantity_desired }))

    const response = await axios.post(`/api/bon-commend-approvals/${selectedApproval.value.id}/send-back`, {
      modified_items,
      notes: sendBackNotes.value
    })

    if (response.data.status === 'success') {
      toast.add({ severity: 'success', summary: 'Sent Back', detail: response.data.message, life: 3000 })
      sendBackDialog.value = false
      await Promise.all([fetchPendingApprovals(), fetchStatistics()])
    }
  } catch (err) {
    console.error('Error sending back approval:', err)
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Failed to send back approval', life: 3000 })
  } finally {
    processing.value = false
  }
}

// Methods
const fetchPendingApprovals = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/bon-commend-approvals/my-pending')
    pendingApprovals.value = response.data.data || response.data
  } catch (err) {
    console.error('Error fetching pending approvals:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch pending approvals',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchStatistics = async () => {
  try {
    const response = await axios.get('/api/bon-commend-approvals/statistics')
    Object.assign(statistics, response.data.data)
  } catch (err) {
    console.error('Error fetching statistics:', err)
  }
}

const viewDetails = (approval) => {
  selectedApproval.value = approval
  detailsDialog.value = true
}

const approveRequest = (approval) => {
  selectedApproval.value = approval
  notesDialogAction.value = 'approve'
  approvalNotes.value = ''
  detailsDialog.value = false
  notesDialog.value = true
}

const rejectRequest = (approval) => {
  selectedApproval.value = approval
  notesDialogAction.value = 'reject'
  approvalNotes.value = ''
  detailsDialog.value = false
  notesDialog.value = true
}

const processApproval = async () => {
  try {
    processing.value = true
    
    const endpoint = notesDialogAction.value === 'approve'
      ? `/api/bon-commend-approvals/${selectedApproval.value.id}/approve`
      : `/api/bon-commend-approvals/${selectedApproval.value.id}/reject`
    
    const response = await axios.post(endpoint, {
      approval_notes: approvalNotes.value
    })

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message,
        life: 3000
      })
      
      notesDialog.value = false
      await Promise.all([
        fetchPendingApprovals(),
        fetchStatistics()
      ])
    }
  } catch (err) {
    console.error('Error processing approval:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to process approval',
      life: 3000
    })
  } finally {
    processing.value = false
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchPendingApprovals(),
    fetchStatistics()
  ])
})
</script>
