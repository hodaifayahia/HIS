<template>
  <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-100 tw-p-6 tw-rounded-xl tw-shadow-lg">
    <!-- Form Header -->
    <div class="tw-flex tw-items-center tw-gap-4 tw-mb-6 tw-p-4 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-rounded-xl tw--mx-2 tw--mt-2">
      <div class="tw-bg-white/20 tw-rounded-full tw-p-3">
        <i class="pi pi-shopping-bag tw-text-xl"></i>
      </div>
      <div>
        <h3 class="tw-text-xl tw-font-bold">{{ product ? 'Modifier Produit' : 'Ajouter Produit' }}</h3>
        <p class="tw-text-blue-200 tw-text-sm">Configurez les détails du produit pour cette demande</p>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="tw-space-y-8">
      <!-- Product Selection -->
      <div class="tw-bg-white tw-rounded-xl tw-p-6 tw-shadow-md tw-border tw-border-blue-100">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
          <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-rounded-full tw-p-2">
            <i class="pi pi-box tw-text-white tw-text-sm"></i>
          </div>
          <label class="tw-text-lg tw-font-semibold tw-text-gray-800">
            Sélection du Produit <span class="tw-text-red-500">*</span>
          </label>
        </div>
        <Dropdown 
          v-model="formData.product_id"
          :options="products"
          option-label="name"
          option-value="id"
          placeholder="Choisissez un produit..."
          class="tw-w-full tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
          :class="{
            'p-invalid': errors.product_id
          }"
          filter
          :disabled="!!product"
        >
          <template #option="slotProps">
            <div class="tw-flex tw-flex-col tw-gap-2 tw-p-3 tw-border-b tw-border-gray-100 last:tw-border-b-0 hover:tw-bg-blue-50 tw-transition-colors">
              <div class="tw-flex tw-items-center tw-justify-between">
                <span class="tw-font-semibold tw-text-gray-900">{{ slotProps.option.name }}</span>
                <Tag :value="slotProps.option.product_code" severity="info" class="tw-text-xs"/>
              </div>
              <small class="tw-text-gray-600">{{ slotProps.option.designation }}</small>
            </div>
          </template>
        </Dropdown>
        <small v-if="errors.product_id" class="tw-flex tw-items-center tw-gap-2 tw-mt-2 tw-text-red-600">
          <i class="pi pi-exclamation-triangle tw-text-xs"></i>
          {{ errors.product_id[0] }}
        </small>
      </div>

      <!-- Quantity & Pricing -->
      <div class="tw-bg-white tw-rounded-xl tw-p-6 tw-shadow-md tw-border tw-border-blue-100">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
          <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-rounded-full tw-p-2">
            <i class="pi pi-calculator tw-text-white tw-text-sm"></i>
          </div>
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Quantité et Prix</h4>
        </div>
        
        <div class="tw-w-full">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
            <i class="pi pi-sort-numeric-up tw-text-green-500 tw-mr-2"></i>
            Quantité <span class="tw-text-red-500">*</span>
          </label>
          <InputNumber 
            v-model="formData.quantity"
            placeholder="Entrez la quantité..."
            :min="1"
            :max="99999"
            class="tw-w-full tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
            :class="{
              'p-invalid': errors.quantity
            }"
            showButtons
            buttonLayout="horizontal"
          />
          <small v-if="errors.quantity" class="tw-flex tw-items-center tw-gap-2 tw-mt-2 tw-text-red-600">
            <i class="pi pi-exclamation-triangle tw-text-xs"></i>
            {{ errors.quantity[0] }}
          </small>
        </div>
      </div>

      <!-- Options & Settings -->
      <div class="tw-bg-white tw-rounded-xl tw-p-6 tw-shadow-md tw-border tw-border-blue-100">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
          <div class="tw-bg-gradient-to-r tw-from-purple-500 tw-to-pink-600 tw-rounded-full tw-p-2">
            <i class="pi pi-cog tw-text-white tw-text-sm"></i>
          </div>
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Options</h4>
        </div>

        <!-- Quantity by Box Checkbox -->
        <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-pink-50 tw-border tw-border-purple-200 tw-rounded-lg tw-p-4 tw-mb-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <Checkbox 
              v-model="formData.quantity_by_box"
              binary
              class="tw-transform tw-scale-125"
            />
            <div class="tw-flex-1">
              <label class="tw-text-sm tw-font-semibold tw-text-gray-800 tw-block">
                <i class="pi pi-box tw-text-purple-600 tw-mr-2"></i>
                Quantité par Boîte/Paquet
              </label>
              <p class="tw-text-xs tw-text-gray-600 tw-mt-1">
                Cochez si la quantité est spécifiée en boîtes ou paquets
              </p>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
            <i class="pi pi-file-edit tw-text-purple-600 tw-mr-2"></i>
            Notes
          </label>
            <Textarea 
              v-model="formData.notes"
              placeholder="Entrez les exigences spécifiques ou notes..."
              :rows="4"
              class="tw-w-full tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200 tw-rounded-lg"
              :class="{
                'p-invalid': errors.notes
              }"
            />
            <small v-if="errors.notes" class="tw-flex tw-items-center tw-gap-2 tw-mt-2 tw-text-red-600">
              <i class="pi pi-exclamation-triangle tw-text-xs"></i>
              {{ errors.notes[0] }}
            </small>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-end tw-gap-4 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button 
            type="button"
            @click="$emit('cancel')"
            label="Annuler"
            class="tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-700 tw-border-gray-300 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200 tw-px-6 tw-py-3"
            :disabled="loading"
            icon="pi pi-times"
          />
          <Button 
            type="submit"
            :label="product ? 'Mettre à jour' : 'Ajouter Produit'"
            :loading="loading"
            class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 hover:tw-from-green-600 hover:tw-to-emerald-700 tw-border-0 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200 tw-px-8 tw-py-3"
            icon="pi pi-save"
          />
        </div>
      </form>
    </div>
</template>

<script setup>
import { ref, reactive, watch, defineProps, defineEmits } from 'vue'

// PrimeVue Components
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'

// Props
const props = defineProps({
  product: {
    type: Object,
    default: null
  },
  products: {
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
  product_id: null,
  quantity: 1,
  quantity_by_box: false,
  notes: ''
})

const errors = ref({})

// Methods
const handleSubmit = () => {
  // Clear previous errors
  errors.value = {}
  
  // Basic validation
  const validationErrors = {}
  
  if (!formData.product_id) {
    validationErrors.product_id = ['Product is required']
  }
  
  if (!formData.quantity || formData.quantity < 1) {
    validationErrors.quantity = ['Quantity must be at least 1']
  }
  
  if (Object.keys(validationErrors).length > 0) {
    errors.value = validationErrors
    return
  }

  // Prepare data for submission
  const submitData = {
    product_id: formData.product_id,
    quantity: formData.quantity,
    quantity_by_box: formData.quantity_by_box,
    notes: formData.notes || null
  }

  emit('save', submitData)
}

const resetForm = () => {
  formData.product_id = null
  formData.quantity = 1
  formData.quantity_by_box = false
  formData.notes = ''
  errors.value = {}
}

// Watch for product changes (editing mode)
watch(() => props.product, (newProduct) => {
  if (newProduct) {
    formData.product_id = newProduct.product_id
    formData.quantity = newProduct.quantity
    formData.quantity_by_box = newProduct.quantity_by_box || false
    formData.notes = newProduct.notes || ''
  } else {
    resetForm()
  }
}, { immediate: true })

// Watch for form visibility changes to reset form when dialog closes
watch(() => props.loading, (loading) => {
  if (!loading && !props.product) {
    // Form finished loading and not in edit mode, reset form after a short delay
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

:deep(.p-inputtextarea) {
  width: 100%;
  resize: vertical;
}

:deep(.p-invalid) {
  border-color: #dc2626;
}

:deep(.p-checkbox) {
  margin-right: 0.5rem;
}

.p-error {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}
</style>