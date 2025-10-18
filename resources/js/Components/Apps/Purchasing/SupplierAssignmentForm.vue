<template>
  <form @submit.prevent="handleSubmit" class="tw-space-y-6">
    <!-- Item Information Display -->
    <div v-if="item" class="tw-bg-gray-50 tw-p-4 tw-rounded-md tw-border">
      <h4 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Assigning Supplier for:</h4>
      <div class="tw-space-y-1 tw-text-sm tw-text-gray-600">
        <div><strong>Product:</strong> {{ item.product?.name || 'Unknown Product' }}</div>
        <div><strong>Total Quantity:</strong> {{ item.quantity }}</div>
        <div><strong>Remaining:</strong> {{ getRemainingQuantity() }}</div>
        <div v-if="item.unit_price"><strong>Estimated Unit Price:</strong> {{ formatCurrency(item.unit_price) }}</div>
      </div>
    </div>

    <!-- Supplier Selection -->
    <div>
      <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
        Supplier <span class="tw-text-red-500">*</span>
      </label>
      <Dropdown 
        v-model="formData.fournisseur_id"
        :options="suppliers"
        option-label="company_name"
        option-value="id"
        placeholder="Select a supplier"
        class="tw-w-full"
        :class="{
          'p-invalid': errors.fournisseur_id
        }"
        filter
      >
        <template #option="slotProps">
          <div class="tw-flex tw-flex-col tw-gap-1">
            <span class="tw-font-medium">{{ slotProps.option.company_name }}</span>
            <small class="tw-text-gray-500">
              Contact: {{ slotProps.option.contact_person }} | 
              {{ slotProps.option.email }} | 
              {{ slotProps.option.phone }}
            </small>
          </div>
        </template>
      </Dropdown>
      <small v-if="errors.fournisseur_id" class="p-error">{{ errors.fournisseur_id[0] }}</small>
    </div>

    <!-- Assigned Quantity and Unit Price -->
    <div class="tw-grid tw-grid-cols-2 tw-gap-4">
      <div>
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Assigned Quantity <span class="tw-text-red-500">*</span>
        </label>
        <InputNumber 
          v-model="formData.assigned_quantity"
          placeholder="Enter quantity to assign"
          :min="1"
          :max="getRemainingQuantity()"
          class="tw-w-full"
          :class="{
            'p-invalid': errors.assigned_quantity
          }"
        />
        <small v-if="errors.assigned_quantity" class="p-error">{{ errors.assigned_quantity[0] }}</small>
        <small class="tw-text-gray-500 tw-text-xs tw-mt-1">
          Maximum: {{ getRemainingQuantity() }}
        </small>
      </div>

      <div>
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Unit Price
        </label>
        <InputNumber 
          v-model="formData.unit_price"
          placeholder="Enter unit price"
          :min="0"
          :fraction-digits="2"
          currency="USD"
          locale="en-US"
          class="tw-w-full"
          :class="{
            'p-invalid': errors.unit_price
          }"
        />
        <small v-if="errors.unit_price" class="p-error">{{ errors.unit_price[0] }}</small>
      </div>
    </div>

    <!-- Unit -->
    <div>
      <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
        Unit
      </label>
      <InputText 
        v-model="formData.unit"
        placeholder="e.g., pieces, boxes, bottles"
        class="tw-w-full"
        :class="{
          'p-invalid': errors.unit
        }"
      />
      <small v-if="errors.unit" class="p-error">{{ errors.unit[0] }}</small>
    </div>

    <!-- Notes -->
    <div>
      <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
        Assignment Notes
      </label>
      <Textarea 
        v-model="formData.notes"
        placeholder="Enter any specific requirements, delivery conditions, or notes for this assignment..."
        :rows="3"
        class="tw-w-full"
        :class="{
          'p-invalid': errors.notes
        }"
      />
      <small v-if="errors.notes" class="p-error">{{ errors.notes[0] }}</small>
    </div>

    <!-- Total Calculation Display -->
    <div v-if="formData.assigned_quantity && formData.unit_price" class="tw-bg-blue-50 tw-p-3 tw-rounded-md tw-border tw-border-blue-200">
      <div class="tw-flex tw-justify-between tw-items-center">
        <span class="tw-text-sm tw-font-medium tw-text-blue-700">Total Amount:</span>
        <span class="tw-text-lg tw-font-bold tw-text-blue-900">
          {{ formatCurrency(formData.assigned_quantity * formData.unit_price) }}
        </span>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
      <Button 
        type="button"
        @click="$emit('cancel')"
        label="Cancel"
        class="p-button-secondary"
        :disabled="loading"
      />
      <Button 
        type="submit"
        label="Assign Supplier"
        :loading="loading"
        class="p-button-success"
        icon="pi pi-check"
      />
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch, defineProps, defineEmits, computed } from 'vue'

// PrimeVue Components
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

// Props
const props = defineProps({
  item: {
    type: Object,
    default: null
  },
  suppliers: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['save', 'cancel'])

// Form state
const formData = reactive({
  fournisseur_id: null,
  assigned_quantity: 1,
  unit_price: null,
  unit: '',
  notes: ''
})

const errors = ref({})

// Computed
const getRemainingQuantity = () => {
  if (!props.item) return 0
  
  const totalAssigned = props.item.fournisseur_assignments?.reduce(
    (sum, assignment) => sum + assignment.assigned_quantity, 
    0
  ) || 0
  
  return Math.max(0, props.item.quantity - totalAssigned)
}

// Methods
const handleSubmit = () => {
  // Clear previous errors
  errors.value = {}
  
  // Basic validation
  const validationErrors = {}
  
  if (!formData.fournisseur_id) {
    validationErrors.fournisseur_id = ['Supplier is required']
  }
  
  if (!formData.assigned_quantity || formData.assigned_quantity < 1) {
    validationErrors.assigned_quantity = ['Assigned quantity must be at least 1']
  }
  
  if (formData.assigned_quantity > getRemainingQuantity()) {
    validationErrors.assigned_quantity = [`Assigned quantity cannot exceed remaining quantity (${getRemainingQuantity()})`]
  }
  
  if (formData.unit_price !== null && formData.unit_price < 0) {
    validationErrors.unit_price = ['Unit price cannot be negative']
  }
  
  if (Object.keys(validationErrors).length > 0) {
    errors.value = validationErrors
    return
  }

  // Prepare data for submission
  const submitData = {
    fournisseur_id: formData.fournisseur_id,
    assigned_quantity: formData.assigned_quantity,
    unit_price: formData.unit_price || null,
    unit: formData.unit || 'unit',
    notes: formData.notes || null
  }

  emit('save', submitData)
}

const resetForm = () => {
  formData.fournisseur_id = null
  formData.assigned_quantity = 1
  formData.unit_price = null
  formData.unit = ''
  formData.notes = ''
  errors.value = {}
}

const formatCurrency = (amount) => {
  if (!amount) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

// Watch for item changes
watch(() => props.item, (newItem) => {
  if (newItem) {
    // Set default values when item changes
    formData.assigned_quantity = getRemainingQuantity()
    formData.unit_price = newItem.unit_price || null
    formData.unit = newItem.product?.unit || 'unit'
    
    // Reset other fields
    formData.fournisseur_id = null
    formData.notes = ''
  } else {
    resetForm()
  }
}, { immediate: true })

// Watch for form visibility changes to reset form when dialog closes
watch(() => props.loading, (loading) => {
  if (!loading && !props.item) {
    setTimeout(() => {
      resetForm()
    }, 100)
  }
})
</script>

<style scoped>
/* Form styling */
:deep(.p-dropdown) {
  width: 100%;
}

:deep(.p-inputnumber) {
  width: 100%;
}

:deep(.p-inputnumber-input) {
  width: 100%;
}

:deep(.p-inputtext) {
  width: 100%;
}

:deep(.p-inputtextarea) {
  width: 100%;
  resize: vertical;
}

:deep(.p-invalid) {
  border-color: #dc2626;
}

.p-error {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}
</style>