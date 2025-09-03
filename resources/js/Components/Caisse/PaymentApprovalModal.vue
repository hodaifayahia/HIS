<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    modal
    :header="'Demande d\'Approbation de Paiement'"
    :style="{ width: '500px' }"
    :closable="!processing"
    :pt="{
      root: { class: 'approval-modal' },
      header: { class: 'approval-modal-header' },
      content: { class: 'approval-modal-content' }
    }"
  >
    <div class="tw-space-y-4">
      <!-- Payment Details -->
      <div class="tw-bg-gray-50 tw-p-4 tw-rounded-md">
        <h4 class="tw-font-semibold tw-mb-2">Détails du Paiement</h4>
        <div class="tw-grid tw-grid-cols-2 tw-gap-2 tw-text-sm">
          <div>
            <span class="tw-font-medium">Montant:</span>
            <span class="tw-ml-2">{{ formatCurrency(paymentData.amount) }}</span>
          </div>
          <div>
            <span class="tw-font-medium">Méthode:</span>
            <span class="tw-ml-2 tw-capitalize">{{ paymentData.method }}</span>
          </div>
          <div class="tw-col-span-2">
            <span class="tw-font-medium">Prestation:</span>
            <span class="tw-ml-2">{{ paymentData.itemName }}</span>
          </div>
        </div>
      </div>

      <!-- Approver Selection -->
      <div>
        <label for="approver" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Sélectionner l'Approbateur *
        </label>
        <Dropdown
          id="approver"
          v-model="selectedApprover"
          :options="approvers"
          option-label="name"
          option-value="id"
          :placeholder="'Choisir un approbateur...'"

          class="tw-w-full"
          :disabled="processing || loadingApprovers"
          :loading="loadingApprovers"
        />
        <small v-if="errors.approved_by" class="tw-text-red-500">{{ errors.approved_by }}</small>
      </div>

      <!-- Notes -->
      <div>
        <label for="notes" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Notes (optionnel)
        </label>
        <Textarea
          id="notes"
          v-model="notes"
          :rows="3"
          class="tw-w-full"
          :placeholder="'Ajouter des notes pour l\'approbateur...'"
          :disabled="processing"
        />
        <small v-if="errors.notes" class="tw-text-red-500">{{ errors.notes }}</small>
      </div>

      <!-- Warning Message -->
      <div class="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-md tw-p-3">
        <div class="tw-flex">
          <i class="pi pi-exclamation-triangle tw-text-yellow-600 tw-mr-2 tw-mt-0.5"></i>
          <div class="tw-text-sm tw-text-yellow-800">
            <p class="tw-font-medium">Attention:</p>
            <p>Ce paiement nécessite une approbation. Vous devrez attendre la validation de l'approbateur avant que le paiement soit traité.</p>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-2">
        <Button
          :label="'Annuler'"
          icon="pi pi-times"
          @click="close"
          class="p-button-secondary"
          :disabled="processing"
        />
        <Button
          :label="'Envoyer la Demande'"
          icon="pi pi-send"
          @click="submitRequest"
          class="p-button-primary"
          :loading="processing"
          :disabled="!selectedApprover || processing"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useCurrencyFormatter } from '@/composables/useCurrencyFormatter'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  paymentData: {
    type: Object,
    required: true,
    default: () => ({
      amount: 0,
      method: 'cash',
      itemName: '',
      fiche_navette_item_id: null,
      item_dependency_id: null
    })
  }
})

const emit = defineEmits(['update:visible', 'request-sent', 'close'])

const toast = useToast()
const { formatCurrency } = useCurrencyFormatter()

// State
const selectedApprover = ref(null)
const notes = ref('')
const approvers = ref([])
const processing = ref(false)
const loadingApprovers = ref(false)
const errors = ref({})

// Load approvers when modal opens
watch(() => props.visible, (newVisible) => {
  if (newVisible) {
    loadApprovers()
    resetForm()
  }
})

const loadApprovers = async () => {
  loadingApprovers.value = true
  try {
    const response = await axios.get('/api/user-caisse-approval/approvers')
    approvers.value = response.data || []
  } catch (error) {
    console.error('Failed to load approvers:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger la liste des approbateurs',
      life: 4000
    })
  } finally {
    loadingApprovers.value = false
  }
}

const resetForm = () => {
  selectedApprover.value = null
  notes.value = ''
  errors.value = {}
}

const submitRequest = async () => {
  console.log('props',props.paymentData)
  errors.value = {}
  
  if (!selectedApprover.value) {
    errors.value.approved_by = 'Veuillez sélectionner un approbateur'
    return
  }

  processing.value = true

  try {
    const requestData = {
      fiche_navette_item_id: props.paymentData.fiche_navette_item_id,
      item_dependency_id: props.paymentData.item_dependency_id || null,
      amount: props.paymentData.amount,
      payment_method: props.paymentData.method,
      approved_by: selectedApprover.value,
      notes: notes.value
    }

    const response = await axios.post('/api/transaction-bank-requests', requestData)

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: response.data.message,
        life: 4000
      })

      emit('request-sent', response.data.request)
      close()
    } else {
      throw new Error(response.data.message || 'Erreur lors de l\'envoi de la demande')
    }
  } catch (error) {
    console.error('Failed to submit approval request:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: error.response?.data?.message || 'Erreur lors de l\'envoi de la demande',
        life: 4000
      })
    }
  } finally {
    processing.value = false
  }
}

const close = () => {
  emit('update:visible', false)
  emit('close')
}

onMounted(() => {
  if (props.visible) {
    loadApprovers()
  }
})
</script>

<style scoped>
.approval-modal :deep(.p-dialog-header) {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.approval-modal :deep(.p-dialog-content) {
  padding: 1.5rem;
}
</style>
