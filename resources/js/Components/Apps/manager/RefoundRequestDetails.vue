<template>
  <div class="tw-bg-white tw-rounded-lg tw-p-6 tw-shadow-md">
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
      <div>
        <h3 class="tw-text-lg tw-font-semibold">Prestations for Fiche #{{ ficheIdLocal }}</h3>
        <div class="tw-text-sm tw-text-gray-500">Only prestations matching your specialization are shown</div>
      </div>
      <div>
        <Button label="Back" icon="pi pi-arrow-left" class="p-button-secondary" @click="goBack" />
      </div>
    </div>

    <div v-if="loading" class="tw-text-center tw-py-8">
      <ProgressSpinner class="tw-w-10 tw-h-10 tw-text-blue-500" />
      <div class="tw-mt-2">Loading prestations...</div>
    </div>

    <div v-else>
      <div v-if="prestations.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
        No prestations available for your specialization on this fiche.
      </div>

      <ul v-else class="tw-space-y-4">
        <li v-for="p in prestations" :key="p.id" class="tw-border tw-border-gray-200 tw-rounded-md tw-p-4">
          <div class="tw-flex tw-justify-between tw-items-start">
            <div class="tw-flex-1">
              <div class="tw-font-medium">{{ p.prestation_name ?? p.name ?? `#${p.prestation_id ?? p.id}` }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ p.specialization_name ? `Specialization: ${p.specialization_name}` : '' }}</div>
              <div v-if="p.notes" class="tw-text-xs tw-text-gray-400 tw-mt-1">{{ p.notes }}</div>
              
              <!-- Payment Status -->
              <div class="tw-flex tw-items-center tw-gap-4 tw-mt-2">
                  <div class="tw-text-sm">
                    <span class="tw-font-medium">Total:</span> {{ formatCurrency(p.final_price ?? p.price ?? p.amount ?? 0) }}
                  </div>
                  <div class="tw-text-sm">
                    <span class="tw-font-medium">Paid:</span> {{ formatCurrency(p.paid_amount ?? 0) }}
                  </div>
                  <div class="tw-text-sm" v-if="getRemainingAmount(p) > 0">
                    <span class="tw-font-medium tw-text-red-600">Remaining:</span> {{ formatCurrency(getRemainingAmount(p)) }}
                  </div>
                  <div class="tw-text-sm tw-mt-1">
                    <span class="tw-font-medium">Payment Status:</span>
                    <Tag :value="p.payment_status ?? (getPaymentStatus(p).text)" :severity="p.payment_status ? (p.payment_status === 'paid' ? 'success' : 'warning') : getPaymentStatus(p).severity" size="small" />
                  </div>
              </div>
            </div>
            
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-text-right">
                <div class="tw-font-semibold">{{ formatCurrency(p.final_price ?? p.price ?? p.amount ?? 0) }}</div>
                <Tag 
                  :value="getPaymentStatus(p).text" 
                  :severity="getPaymentStatus(p).severity"
                  size="small"
                />
              </div>
              
              <div class="tw-flex tw-flex-col tw-gap-2">
                <!-- Request Refund Authorization Button -->
                <template v-if="userCanRefund">
                  <Button
                    class="p-button-warning p-button-sm"
                    label="Request Refund"
                    icon="pi pi-undo"
                    :disabled="processingId !== null || !canRequestRefund(p) || isDone(p)"
                    @click.stop="openRefundAuthorizationDialog(p)"
                    v-tooltip.left="(!canRequestRefund(p) || isDone(p)) ? 'No payments to refund or item completed' : 'Request refund authorization'"
                  />

                  <!-- View Existing Requests Button -->
                  <Button
                    v-if="p.refund_requests_count > 0"
                    class="p-button-info p-button-sm"
                    :label="`View Requests (${p.refund_requests_count})`"
                    icon="pi pi-eye"
                    @click.stop="viewRefundRequests(p)"
                  />
                </template>
              </div>
            </div>
          </div>
          
          <!-- Existing Refund Requests Preview (only shown if user can view/request refunds) -->
          <div v-if="userCanRefund && p.recent_refund_requests && p.recent_refund_requests.length > 0" class="tw-mt-4 tw-pt-4 tw-border-t tw-border-gray-100">
            <div class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Recent Refund Requests:</div>
            <div class="tw-space-y-2">
              <div 
                v-for="request in p.recent_refund_requests.slice(0, 2)" 
                :key="request.id"
                class="tw-flex tw-justify-between tw-items-center tw-text-xs tw-p-2 tw-bg-gray-50 tw-rounded"
              >
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Tag 
                    :value="request.status_text" 
                    :severity="request.status_color" 
                    size="small"
                  />
                  <span>{{ formatCurrency(request.requested_amount) }}</span>
                  <span class="tw-text-gray-500">{{ request.reference_number }}</span>
                </div>
                <div class="tw-text-gray-500">
                  {{ formatRelativeTime(request.created_at) }}
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <!-- Refund Authorization Request Dialog -->
  <Dialog 
    v-model:visible="showRefundAuthDialog" 
    header="Request Refund Authorization" 
    :style="{ width: '600px' }" 
    :modal="true" 
    :closable="!processingRefundAuth"
  >
    <div class="tw-space-y-5" v-if="selectedItem">
      <div v-if="fetchingAuth" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-500">
        <ProgressSpinner style="width:18px;height:18px" strokeWidth="3" /> Checking authorization...
      </div>
      <!-- Prestation Information -->
      <div class="tw-p-4 tw-bg-blue-50 tw-rounded-lg">
        <h4 class="tw-font-medium tw-text-blue-800 tw-mb-2">Prestation Details</h4>
        <div class="tw-text-sm tw-space-y-1">
          <div><span class="tw-font-medium">Name:</span> {{ selectedItem.prestation_name ?? selectedItem.name ?? `#${selectedItem.prestation_id ?? selectedItem.id}` }}</div>
          <div><span class="tw-font-medium">Total Amount:</span> {{ formatCurrency(selectedItem.final_price ?? selectedItem.price ?? 0) }}</div>
          <div><span class="tw-font-medium">Paid Amount:</span> {{ formatCurrency(selectedItem.paid_amount ?? 0) }}</div>
          <div v-if="getRemainingAmount(selectedItem) > 0"><span class="tw-font-medium">Remaining:</span> {{ formatCurrency(getRemainingAmount(selectedItem)) }}</div>
        </div>
      </div>

      <!-- Request Form -->
      <div class="tw-space-y-4">
        <!-- Requested Amount -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">
            Requested Refund Amount <span class="tw-text-red-500">*</span>
          </label>
          <InputNumber 
            v-model="refundAuthForm.requested_amount" 
            :min="0.01" 
            :max="selectedItem?.paid_amount ?? selectedItem?.final_price ?? 0"
            :minFractionDigits="2"
            :maxFractionDigits="2"
            :show-buttons="true"
            class="tw-w-full"
            :class="{ 'p-invalid': formErrors.requested_amount }"
            :disabled="processingRefundAuth || fixedAmount"
            mode="currency"
            currency="DZD"
            locale="fr-DZ"
          />
          <small v-if="formErrors.requested_amount" class="p-error tw-block tw-mt-1">
            {{ formErrors.requested_amount }}
          </small>
          <small class="tw-text-gray-500 tw-block tw-mt-1">
            Maximum refundable: {{ formatCurrency(selectedItem?.paid_amount ?? 0) }}
          </small>
        </div>

        <!-- Priority -->
     
        <!-- Reason -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">
            Reason for Refund <span class="tw-text-red-500">*</span>
          </label>
          <Textarea
            v-model="refundAuthForm.reason"
            rows="4"
            class="tw-w-full"
            :class="{ 'p-invalid': formErrors.reason }"
            :disabled="processingRefundAuth"
            placeholder="Please provide a detailed reason for this refund request..."
            maxlength="1000"
          />
          <small v-if="formErrors.reason" class="p-error tw-block tw-mt-1">
            {{ formErrors.reason }}
          </small>
          <small class="tw-text-gray-500 tw-block tw-mt-1">
            {{ refundAuthForm.reason?.length || 0 }}/1000 characters
          </small>
        </div>

        <!-- Additional Notes -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">
            Additional Notes <span class="tw-text-gray-500">(Optional)</span>
          </label>
          <Textarea
            v-model="refundAuthForm.notes"
            rows="3"
            class="tw-w-full"
            :disabled="processingRefundAuth"
            placeholder="Any additional information..."
            maxlength="2000"
          />
          <small class="tw-text-gray-500 tw-block tw-mt-1">
            {{ refundAuthForm.notes?.length || 0 }}/2000 characters
          </small>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-between tw-items-center">
        <div class="tw-text-sm tw-text-gray-500">
          <i class="pi pi-info-circle tw-mr-1"></i>
          Request will be reviewed by management
        </div>
        <div class="tw-flex tw-gap-2">
          <Button 
            label="Cancel" 
            class="p-button-text" 
            @click="closeRefundAuthDialog" 
            :disabled="processingRefundAuth"
          />
          <Button 
            label="Submit Request" 
            class="p-button-primary" 
            :loading="processingRefundAuth" 
            :disabled="!isFormValid"
            @click="submitRefundAuthRequest" 
          />
        </div>
      </div>
    </template>
  </Dialog>

  <!-- View Refund Requests Dialog -->
  <Dialog 
  v-model:visible="showViewRequestsDialog" 
    header="Refund Authorization Requests" 
    :style="{ width: '800px' }" 
    :modal="true"
  >
    <div v-if="selectedItem && selectedItem.refund_requests">
      <DataTable :value="selectedItem.refund_requests" class="tw-rounded-lg">
        <Column field="reference_number" header="Reference">
          <template #body="{ data }">
            <span class="tw-font-mono tw-text-sm">{{ data.reference_number }}</span>
          </template>
        </Column>
        
        <Column field="requested_amount" header="Amount">
          <template #body="{ data }">
            {{ formatCurrency(data.requested_amount) }}
          </template>
        </Column>
        
        <Column field="status" header="Status">
          <template #body="{ data }">
            <Tag :value="data.status_text" :severity="data.status_color" />
          </template>
        </Column>
        
       
        <Column field="created_at" header="Requested">
          <template #body="{ data }">
            <div class="tw-text-sm">
              <div>{{ formatDate(data.created_at) }}</div>
              <div class="tw-text-gray-500">{{ formatRelativeTime(data.created_at) }}</div>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <template #footer>
  <Button label="Close" class="p-button-secondary" @click="showViewRequestsDialog = false" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tooltip from 'primevue/tooltip'

const props = defineProps({
  ficheId: { type: [String, Number], default: null }
})

const route = useRoute()
const router = useRouter()
const toast = useToast()

const ficheIdLocal = ref(props.ficheId ?? route.query?.fiche_navette_id ?? route.params?.fiche_navette_id ?? null)
const loading = ref(false)
const prestations = ref([])
const processingId = ref(null)
const isEditingRefund = ref(false)
const editingRefundId = ref(null)
const fixedAmount = ref(false)

const editableFicheStatuses = ['pending', 'confirmed', 'canceled']
const blockedFicheStatuses = ['working', 'done']

// Refund Authorization Dialog
const showRefundAuthDialog = ref(false)
const showViewRequestsDialog = ref(false)
const selectedItem = ref(null)
const processingRefundAuth = ref(false)
const fetchingAuth = ref(false)
const formErrors = ref({})

// Permission: whether the authenticated user can request/view refunds
const userCanRefund = ref(false)

// Refund Authorization Form
const refundAuthForm = reactive({
  requested_amount: 0,
  reason: '',
  priority: 'medium',
  notes: ''
})

// Priority options
const priorityOptions = [
  { label: 'Low Priority', value: 'low', color: 'info' },
  { label: 'Medium Priority', value: 'medium', color: 'warning' },
  { label: 'High Priority', value: 'high', color: 'danger' }
]

// Computed properties
const isFormValid = computed(() => {
  return refundAuthForm.requested_amount > 0 && 
         refundAuthForm.reason.length >= 10
})

// Helper functions
const formatCurrency = (amount) => {
  const value = Number(amount ?? 0)
  return new Intl.NumberFormat('fr-DZ', { 
    style: 'currency', 
    currency: 'DZD', 
    minimumFractionDigits: 2 
  }).format(value)
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-DZ')
}

const formatRelativeTime = (date) => {
  if (!date) return '—'
  const now = new Date()
  const past = new Date(date)
  const diffMs = now - past
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffDays > 0) {
    return `${diffDays} jour${diffDays > 1 ? 's' : ''}`
  } else if (diffHours > 0) {
    return `${diffHours}h`
  } else {
    return 'Récent'
  }
}

const getRemainingAmount = (prestation) => {
  const total = Number(prestation.final_price ?? prestation.price ?? prestation.amount ?? 0)
  const paid = Number(prestation.paid_amount ?? 0)
  return Math.max(0, total - paid)
}

const getPaymentStatus = (prestation) => {
  const remaining = getRemainingAmount(prestation)
  const paid = Number(prestation.paid_amount ?? 0)
  
  if (remaining <= 0) {
    return { text: 'Fully Paid', severity: 'success' }
  } else if (paid > 0) {
    return { text: 'Partially Paid', severity: 'warning' }
  } else {
    return { text: 'Unpaid', severity: 'danger' }
  }
}

const canRequestRefund = (prestation) => {
  // prefer explicit payment_status
  const paymentStatus = (prestation.payment_status ?? '').toString().toLowerCase();
  if (paymentStatus === 'paid' || paymentStatus === 'partial' || paymentStatus === 'partially_paid' || paymentStatus === 'partial_paid') return true;

  // fallback to paid_amount
  if (Number(prestation.paid_amount ?? 0) > 0) return true;

  return false;
}

const isDone = (prestation) => {
  const ficheStatus = (prestation.fiche_status ?? '').toString().toLowerCase();
  const itemStatus = (prestation.status ?? '').toString().toLowerCase();
  return ficheStatus === 'done' || itemStatus === 'done';
}

// API functions
const loadPrestations = async () => {
  if (!ficheIdLocal.value) return
  loading.value = true
  prestations.value = []
  
  try {
    // Load prestations with refund request information
    const [prestationsRes, refundRequestsRes] = await Promise.all([
      axios.get(`/api/reception/fiche-navette/${ficheIdLocal.value}/filtered-prestations`, { 
        headers: { Accept: 'application/json' }, 
        withCredentials: true 
      }),
      // Use apiResource index endpoint to load refund authorizations filtered by fiche_navette_id
      axios.get('/api/refund-authorizations', {
        params: { fiche_navette_id: ficheIdLocal.value },
        headers: { Accept: 'application/json' },
        withCredentials: true
      }).catch(() => ({ data: { data: [] } }))
    ])

    const prestationsData = prestationsRes?.data?.data ?? []
    const refundRequestsData = refundRequestsRes?.data?.data ?? []
    
    // Map refund requests to prestations (single request per item is expected)
    const refundRequestsByItem = {}
    refundRequestsData.forEach(request => {
      const itemId = request.fiche_navette_item_id
      if (!refundRequestsByItem[itemId]) {
        refundRequestsByItem[itemId] = []
      }
      refundRequestsByItem[itemId].push(request)
    })

    // Enhance prestations with refund request data (attach only existing requests)
    prestations.value = prestationsData.map(prestation => ({
      ...prestation,
      refund_requests: refundRequestsByItem[prestation.id] || [],
      refund_requests_count: (refundRequestsByItem[prestation.id] || []).length,
      recent_refund_requests: (refundRequestsByItem[prestation.id] || []).slice(0, 3)
    }))

  } catch (err) {
    console.error('Failed to load filtered prestations', err)
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Failed to load prestations for your specializations', 
      life: 4000 
    })
    prestations.value = []
  } finally {
    loading.value = false
  }
}

const openRefundAuthorizationDialog = (item) => {
  selectedItem.value = item

  // Reset form + flags
  isEditingRefund.value = false
  editingRefundId.value = null
  fixedAmount.value = false
  Object.assign(refundAuthForm, {
    requested_amount: Number(item.paid_amount ?? 0),
    reason: '',
    priority: 'medium',
    notes: ''
  })
  formErrors.value = {}

  // Open modal immediately to improve perceived performance, fetch existing auth in background
  showRefundAuthDialog.value = true
  fetchingAuth.value = true

  axios.get(`/api/refund-authorizations/fiche-item/${item.id}`, { headers: { Accept: 'application/json' }, withCredentials: true })
    .then(res => {
      const data = res.data?.data ?? null
      const existing = Array.isArray(data) ? (data[0] ?? null) : data

      const itemStatus = (item.status ?? '').toString().toLowerCase()
      // If item is in blocked statuses and there's no existing authorization, keep modal open but show warning and prevent submit
      if (blockedFicheStatuses.includes(itemStatus) && !existing) {
        toast.add({ severity: 'warn', summary: 'Refund requires authorization', detail: 'This prestation is in progress or completed. A refund authorization is required before proceeding.', life: 5000 })
        // Keep modal open but mark form as read-only by setting fixedAmount and form errors
        fixedAmount.value = true
        formErrors.value = { requested_amount: 'Cannot request refund: prestation in progress or completed without authorization' }
        return
      }

      if (existing) {
        const st = (existing.status ?? '').toString().toLowerCase()
        if (st === 'used' || st === 'approved') {
          // previous finalized authorization — let user create a fresh request
          isEditingRefund.value = false
          editingRefundId.value = null
          fixedAmount.value = false
          Object.assign(refundAuthForm, {
            requested_amount: Number(item.paid_amount ?? 0),
            reason: '',
            priority: 'medium',
            notes: ''
          })
          toast.add({ severity: 'info', summary: 'Previous authorization finalized', detail: 'A previous refund authorization is finalized; you may create a new request.', life: 4000 })
          return
        }

        // Non-final authorization: open edit mode and populate form
        isEditingRefund.value = true
        editingRefundId.value = existing.id
        if (blockedFicheStatuses.includes(itemStatus)) {
          fixedAmount.value = true
        }
        Object.assign(refundAuthForm, {
          requested_amount: Number(existing.requested_amount ?? item.paid_amount ?? 0),
          reason: existing.reason ?? '',
          priority: existing.priority ?? 'medium',
          notes: existing.notes ?? ''
        })
      }
    })
    .catch(err => {
      console.error('Failed to fetch refund authorization', err)
      toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to check refund authorization status', life: 4000 })
    })
    .finally(() => {
      fetchingAuth.value = false
    })

}

const closeRefundAuthDialog = () => {
  showRefundAuthDialog.value = false
  selectedItem.value = null
  formErrors.value = {}
  fetchingAuth.value = false
  // Reset form
  Object.assign(refundAuthForm, {
    requested_amount: 0,
    reason: '',
    priority: 'medium',
    notes: ''
  })
}

const validateForm = () => {
  const errors = {}
  
  if (!refundAuthForm.requested_amount || refundAuthForm.requested_amount <= 0) {
    errors.requested_amount = 'Amount is required and must be greater than 0'
  }
  
  // If amount is fixed by an existing authorization, ensure it matches requested_amount
  if (fixedAmount.value && selectedItem.value && Number(refundAuthForm.requested_amount) !== Number((selectedItem.value.refund_requests && selectedItem.value.refund_requests[0]?.requested_amount) ?? refundAuthForm.requested_amount)) {
    errors.requested_amount = 'Requested amount is fixed by authorization and cannot be changed'
  }

  if (selectedItem.value && refundAuthForm.requested_amount > Number(selectedItem.value.paid_amount ?? 0)) {
    errors.requested_amount = 'Amount cannot exceed paid amount'
  }
  
  if (!refundAuthForm.reason || refundAuthForm.reason.length < 10) {
    errors.reason = 'Reason is required and must be at least 10 characters'
  }
  
  formErrors.value = errors
  return Object.keys(errors).length === 0
}

const submitRefundAuthRequest = async () => {
  console.log('submitRefundAuthRequest called', { selectedItem: selectedItem.value, form: refundAuthForm })
  
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please correct the form errors',
      life: 3000
    })
    return
  }

  processingRefundAuth.value = true
  
  try {
    const payload = {
      fiche_navette_item_id: selectedItem.value.id,
      requested_amount: refundAuthForm.requested_amount,
      reason: refundAuthForm.reason,
      priority: refundAuthForm.priority,
      notes: refundAuthForm.notes
    }
    
    let response
    if (isEditingRefund.value && editingRefundId.value) {
      // Update existing request
      response = await axios.patch(`/api/refund-authorizations/${editingRefundId.value}`, payload, {
        headers: { Accept: 'application/json' },
        withCredentials: true
      })
    } else {
      // Create new request - REMOVED the duplicate check that was blocking approved/used cases
      // The openRefundAuthorizationDialog already handles this logic correctly
      response = await axios.post('/api/refund-authorizations', payload, {
        headers: { Accept: 'application/json' },
        withCredentials: true
      })
    }

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Request Submitted',
        detail: `Refund authorization request ${response.data.data.reference_number} submitted successfully`,
        life: 4000
      })
      
      // Reset editing state and close
      isEditingRefund.value = false
      editingRefundId.value = null
      // Update local prestations array instead of refetching
      const newRequest = response.data.data
      // find prestation in local array
      const idx = prestations.value.findIndex(p => p.id === newRequest.fiche_navette_item_id)
      if (idx !== -1) {
        const item = prestations.value[idx]
        item.refund_requests = item.refund_requests || []
        // If editing, replace existing, otherwise push
        const existingIndex = item.refund_requests.findIndex(r => r.id === newRequest.id)
        if (existingIndex !== -1) {
          item.refund_requests.splice(existingIndex, 1, newRequest)
        } else {
          item.refund_requests.unshift(newRequest)
        }
        item.refund_requests_count = (item.refund_requests || []).length
        item.recent_refund_requests = (item.refund_requests || []).slice(0, 3)
        // update prestations array to trigger reactivity
        prestations.value.splice(idx, 1, { ...item })
      }

      closeRefundAuthDialog()
    }
  } catch (error) {
    console.error('Refund authorization request error:', error)
    
    if (error.response?.data?.errors) {
      formErrors.value = error.response.data.errors
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please correct the form errors',
        life: 4000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to submit refund authorization request',
        life: 4000
      })
    }
  } finally {
    processingRefundAuth.value = false
  }
}

const viewRefundRequests = async (item) => {
  selectedItem.value = item
  showViewRequestsDialog.value = true
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  // Check if the current user is allowed to request refunds, then load prestations
  ;(async () => {
    try {
      await checkRefundPermission()
    } catch (e) {
      console.error('Failed to check refund permission', e)
    }
    await loadPrestations()
  })()
})

// Check refund permission for the authenticated user using the provided API endpoint
async function checkRefundPermission() {
  try {
      const res = await axios.get('/api/user-refund-permissions/check', { headers: { Accept: 'application/json' }, withCredentials: true })
      // Updated to work with Spatie permission system
      const payload = res?.data?.data || res?.data || null
      userCanRefund.value = !!(payload && payload.can_refund)
    } catch (err) {
      console.error('Error checking refund permission', err)
      userCanRefund.value = false
    }
}
</script>

<style scoped>
:deep(.p-invalid) {
  border-color: #ef4444 !important;
}

:deep(.p-error) {
  color: #ef4444;
  font-size: 0.875rem;
}
</style>
