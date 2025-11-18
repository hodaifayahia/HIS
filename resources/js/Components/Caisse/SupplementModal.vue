<script setup>
import { ref, reactive, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  prestationName: {
    type: String,
    default: 'Prestation'
  },
  basePrice: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update:visible', 'supplements-added'])

const supplements = ref([])
const newSupplement = reactive({
  name: '',
  amount: null,
  reason: ''
})

const errors = reactive({
  name: '',
  amount: '',
  reason: ''
})

const totalSupplementsAmount = computed(() => {
  return supplements.value.reduce((sum, supp) => sum + (supp.amount || 0), 0)
})

const totalWithSupplements = computed(() => {
  return props.basePrice + totalSupplementsAmount.value
})

const validateSupplement = () => {
  errors.name = ''
  errors.amount = ''
  errors.reason = ''

  let isValid = true

  if (!newSupplement.name || newSupplement.name.trim() === '') {
    errors.name = 'Name is required'
    isValid = false
  }

  if (!newSupplement.amount || newSupplement.amount <= 0) {
    errors.amount = 'Amount must be greater than 0'
    isValid = false
  }

  return isValid
}

const addSupplement = () => {
  if (!validateSupplement()) {
    return
  }

  supplements.value.push({
    name: newSupplement.name,
    amount: newSupplement.amount,
    reason: newSupplement.reason
  })

  // Reset form
  newSupplement.name = ''
  newSupplement.amount = null
  newSupplement.reason = ''
}

const removeSupplement = (index) => {
  supplements.value.splice(index, 1)
}

const confirmSupplements = () => {
  if (supplements.value.length === 0) {
    return
  }

  emit('supplements-added', supplements.value)
  closeModal()
}

const closeModal = () => {
  // Reset data
  supplements.value = []
  newSupplement.name = ''
  newSupplement.amount = null
  newSupplement.reason = ''
  errors.name = ''
  errors.amount = ''
  errors.reason = ''
  
  emit('update:visible', false)
}
</script>

<template>
  <Dialog 
    :visible="visible" 
    @update:visible="emit('update:visible', $event)"
    modal 
    :style="{ width: '650px' }" 
    :closable="true"
    @hide="closeModal"
  >
    <template #header>
      <div class="tw-flex tw-items-center tw-gap-3">
        <i class="pi pi-plus-circle tw-text-2xl tw-text-violet-600"></i>
        <div>
          <h3 class="tw-text-xl tw-font-bold tw-text-slate-800 tw-m-0">Add Supplements</h3>
          <p class="tw-text-sm tw-text-slate-500 tw-m-0">{{ prestationName }}</p>
        </div>
      </div>
    </template>

    <div class="tw-space-y-4">
      <!-- Price Summary -->
      <div class="tw-bg-violet-50 tw-rounded-lg tw-p-4 tw-border tw-border-violet-200">
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
          <span class="tw-text-slate-600">Base Price:</span>
          <span class="tw-font-semibold tw-text-slate-800">{{ basePrice.toFixed(2) }} DA</span>
        </div>
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
          <span class="tw-text-slate-600">Supplements:</span>
          <span class="tw-font-semibold tw-text-violet-600">+{{ totalSupplementsAmount.toFixed(2) }} DA</span>
        </div>
        <div class="tw-border-t tw-border-violet-300 tw-pt-2 tw-flex tw-justify-between tw-items-center">
          <span class="tw-font-bold tw-text-slate-700">Total:</span>
          <span class="tw-font-bold tw-text-lg tw-text-violet-700">{{ totalWithSupplements.toFixed(2) }} DA</span>
        </div>
      </div>

      <!-- Add New Supplement Form -->
      <div class="tw-bg-slate-50 tw-rounded-lg tw-p-4 tw-border tw-border-slate-200">
        <h4 class="tw-font-semibold tw-text-slate-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-plus tw-text-sm"></i>
          Add New Supplement
        </h4>
        
        <div class="tw-space-y-3">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-1">
              Name <span class="tw-text-red-500">*</span>
            </label>
            <InputText 
              v-model="newSupplement.name" 
              placeholder="e.g., Nursing fee, Equipment use..."
              class="tw-w-full"
              :class="{ 'p-invalid': errors.name }"
            />
            <small v-if="errors.name" class="p-error tw-block tw-mt-1">{{ errors.name }}</small>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-1">
              Amount (DA) <span class="tw-text-red-500">*</span>
            </label>
            <InputNumber 
              v-model="newSupplement.amount" 
              mode="decimal"
              :minFractionDigits="2"
              :maxFractionDigits="2"
              placeholder="0.00"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.amount }"
            />
            <small v-if="errors.amount" class="p-error tw-block tw-mt-1">{{ errors.amount }}</small>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-1">
              Reason
            </label>
            <Textarea 
              v-model="newSupplement.reason" 
              rows="2"
              placeholder="Optional: Explain why this supplement is needed..."
              class="tw-w-full"
            />
          </div>

          <Button 
            label="Add to List" 
            icon="pi pi-plus" 
            @click="addSupplement"
            class="p-button-success tw-w-full"
          />
        </div>
      </div>

      <!-- Supplements List -->
      <div v-if="supplements.length > 0">
        <h4 class="tw-font-semibold tw-text-slate-800 tw-mb-2 tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-list tw-text-sm"></i>
          Supplements List ({{ supplements.length }})
        </h4>
        <DataTable :value="supplements" class="tw-text-sm">
          <Column field="name" header="Name" style="width: 35%"></Column>
          <Column field="amount" header="Amount" style="width: 25%">
            <template #body="{ data }">
              <span class="tw-font-semibold tw-text-violet-600">{{ data.amount.toFixed(2) }} DA</span>
            </template>
          </Column>
          <Column field="reason" header="Reason" style="width: 30%">
            <template #body="{ data }">
              <span class="tw-text-slate-600 tw-text-xs">{{ data.reason || '-' }}</span>
            </template>
          </Column>
          <Column header="Actions" style="width: 10%">
            <template #body="{ index }">
              <Button 
                icon="pi pi-trash" 
                class="p-button-danger p-button-text p-button-sm" 
                @click="removeSupplement(index)"
                v-tooltip.top="'Remove'"
              />
            </template>
          </Column>
        </DataTable>
      </div>

      <div v-else class="tw-text-center tw-py-6 tw-text-slate-500 tw-bg-slate-50 tw-rounded-lg tw-border tw-border-dashed tw-border-slate-300">
        <i class="pi pi-inbox tw-text-3xl tw-mb-2 tw-block"></i>
        <p class="tw-text-sm">No supplements added yet</p>
      </div>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-2">
        <Button 
          label="Cancel" 
          icon="pi pi-times" 
          @click="closeModal"
          class="p-button-secondary"
        />
        <Button 
          label="Confirm & Apply" 
          icon="pi pi-check" 
          @click="confirmSupplements"
          :disabled="supplements.length === 0"
          class="p-button-success"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
:deep(.p-dialog-header) {
  background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
  color: white;
}

:deep(.p-dialog-header .p-dialog-title) {
  color: white;
}

:deep(.p-dialog-header .p-dialog-header-icon) {
  color: white;
}
</style>
