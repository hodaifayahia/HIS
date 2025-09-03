<template>
  <Dialog 
    v-model:visible="visible" 
    modal 
    header="Payment Approval Request"
    class="tw-w-full tw-max-w-md"
    :closable="true"
    @hide="onHide"
  >
    <div class="tw-p-6 tw-space-y-4">
      <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
        <h4 class="tw-font-semibold tw-text-gray-700 tw-mb-2">Payment Details</h4>
        <div class="tw-grid tw-grid-cols-2 tw-gap-2 tw-text-sm">
          <div><strong>Method:</strong> {{ paymentData?.method || 'N/A' }}</div>
          <div><strong>Amount:</strong> {{ formatAmount(paymentData?.amount || 0) }} DH</div>
          <div class="tw-col-span-2"><strong>Prestation:</strong> {{ paymentData?.itemName || 'N/A' }}</div>
        </div>
      </div>

      <div class="tw-flex tw-flex-col tw-gap-2">
        <label for="approver" class="tw-font-medium tw-text-gray-700">
          Select Approver <span class="tw-text-red-500">*</span>
        </label>
        <Dropdown
          id="approver"
          v-model="selectedApprover"
          :options="approvers"
          optionLabel="name"
          optionValue="id"
          placeholder="Choose an approver..."
          :loading="loadingApprovers"
          class="tw-w-full"
          :class="{ 'p-invalid': submitted && !selectedApprover }"
        >
          <template #option="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <span>{{ slotProps.option.name }}</span>
              <small class="tw-text-gray-500">({{ slotProps.option.email }})</small>
            </div>
          </template>
        </Dropdown>
        <small v-if="submitted && !selectedApprover" class="tw-text-red-500">
          Please select an approver
        </small>
      </div>

      <div class="tw-flex tw-flex-col tw-gap-2">
        <label for="notes" class="tw-font-medium tw-text-gray-700">
          Notes <span class="tw-text-gray-400">(optional)</span>
        </label>
        <Textarea
          id="notes"
          v-model="notes"
          placeholder="Add relevant notes for the approver..."
          rows="3"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
        />
      </div>

      <div class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-3">
        <div class="tw-flex tw-items-start tw-gap-2">
          <i class="pi pi-exclamation-triangle tw-text-amber-600 tw-mt-0.5"></i>
          <div class="tw-text-sm tw-text-amber-800">
            This payment requires approval. The selected user will be notified and must approve before the payment can be processed.
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-2 tw-p-4 tw-border-t tw-border-gray-200">
        <Button
          type="button"
          label="Cancel"
          severity="secondary"
          @click="onCancel"
          :disabled="submitting"
        />
        <Button
          type="button"
          label="Request Approval"
          @click="onSubmit"
          :loading="submitting"
          :disabled="!selectedApprover"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import axios from 'axios'

const toast = useToast()

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  paymentData: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:visible', 'submitted', 'cancelled'])

const selectedApprover = ref(null)
const notes = ref('')
const approvers = ref([])
const loadingApprovers = ref(false)
const submitting = ref(false)
const submitted = ref(false)

const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const loadApprovers = async () => {
  loadingApprovers.value = true
  try {
    const response = await axios.get('/api/user-caisse-approval/approvers')
    approvers.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error loading approvers:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les approbateurs',
      life: 5000
    })
    approvers.value = []
  } finally {
    loadingApprovers.value = false
  }
}

const onSubmit = async () => {
  submitted.value = true
  
  if (!selectedApprover.value) {
    return
  }

  submitting.value = true
  
  try {
    const requestData = {
      approved_by: selectedApprover.value,
      payment_method: props.paymentData.method,
      amount: props.paymentData.amount,
      notes: notes.value || null,
      item_type: props.paymentData.itemType,
      fiche_navette_item_id: props.paymentData.fiche_navette_item_id,
      item_dependency_id: props.paymentData.item_dependency_id,
      patient_id: props.paymentData.patient_id
    }

    const response = await axios.post('/api/transaction-bank-requests', requestData)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Approval request sent successfully',
      life: 5000
    })

    emit('submitted', {
      requestId: response.data.data?.id,
      approver: approvers.value.find(a => a.id === selectedApprover.value),
      ...requestData
    })
    
    onHide()
    
  } catch (error) {
    console.error('Error submitting approval request:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to send approval request',
      life: 5000
    })
  } finally {
    submitting.value = false
  }
}

const onCancel = () => {
  emit('cancelled')
  onHide()
}

const onHide = () => {
  selectedApprover.value = null
  notes.value = ''
  submitted.value = false
  submitting.value = false
  
  emit('update:visible', false)
}

watch(() => props.visible, (newValue) => {
  if (newValue) {
    loadApprovers()
  }
})

onMounted(() => {
  if (props.visible) {
    loadApprovers()
  }
})
</script>

<style scoped>
/*
  The `p-invalid` class is provided by PrimeVue.
  We apply Tailwind's border-red-500 to it for consistent styling.
*/
.p-invalid {
  @apply tw-border-red-500;
}
</style>