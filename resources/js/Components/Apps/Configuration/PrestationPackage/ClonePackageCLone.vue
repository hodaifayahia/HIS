<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Card from 'primevue/card'

// Services
import prestationPackageService from '../../../../Components/Apps/services/Prestation/prestationPackageService.js'

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  packageData: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:visible', 'package-cloned'])

// Composables
const toast = useToast()

// State
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const form = reactive({
  name: '',
  price: 0
})

const saving = ref(false)

// Computed
const isFormValid = computed(() => {
  return form.name && form.name.trim().length > 0 && form.price >= 0
})

const originalPackageName = computed(() => {
  return props.packageData?.name || 'Unknown Package'
})

const prestationsCount = computed(() => {
  return props.packageData?.items?.length || 0
})

// Methods
const resetForm = () => {
  Object.assign(form, {
    name: '',
    price: 0
  })
}

const populateForm = () => {
  if (props.packageData) {
    Object.assign(form, {
      name: `${props.packageData.name} (Copy)`,
      price: props.packageData.price || 0
    })
  }
}

const handleSubmit = async () => {
  if (!isFormValid.value) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please fill in all required fields',
      life: 3000
    })
    return
  }

  if (!props.packageData?.id) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Package data is missing',
      life: 3000
    })
    return
  }

  saving.value = true
  
  try {
    const payload = {
      name: form.name.trim(),
      price: form.price || 0
    }

    const response = await prestationPackageService.clone(props.packageData.id, payload)

    if (response.success) {
      emit('package-cloned', response.data)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Package cloned successfully',
        life: 3000
      })
      dialogVisible.value = false
    } else {
      throw new Error(response.message || 'Failed to clone package')
    }
  } catch (error) {
    console.error('Error cloning package:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to clone package',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const handleCancel = () => {
  resetForm()
  dialogVisible.value = false
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Watchers
watch(() => props.visible, (newVal) => {
  if (newVal) {
    populateForm()
  } else {
    resetForm()
  }
})
</script>

<template>
  <Dialog 
    v-model:visible="dialogVisible"
    header="Clone Package"
    :style="{ width: '500px' }"
    :modal="true"
    class="clone-modal"
    :closable="!saving"
  >
    <div class="clone-form">
      <!-- Package Info -->
      <Card class="mb-4">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="fas fa-copy text-primary"></i>
            Cloning Package
          </div>
        </template>
        <template #content>
          <div class="source-package-info">
            <div class="info-row">
              <span class="label">Original Package:</span>
              <span class="value fw-bold">{{ originalPackageName }}</span>
            </div>
            <div class="info-row">
              <span class="label">Prestations:</span>
              <span class="value">{{ prestationsCount }} items will be copied</span>
            </div>
            <div class="info-row">
              <span class="label">Original Price:</span>
              <span class="value text-primary">{{ formatCurrency(packageData?.price) }}</span>
            </div>
          </div>
        </template>
      </Card>

      <!-- Clone Settings -->
      <Card class="mb-4">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="fas fa-edit text-primary"></i>
            New Package Details
          </div>
        </template>
        <template #content>
          <div class="form-fields">
            <div class="field">
              <label for="clone-name" class="required">Package Name</label>
              <InputText 
                id="clone-name"
                v-model="form.name"
                placeholder="Enter new package name"
                class="w-100"
                :invalid="!form.name && form.name !== ''"
              />
              <small v-if="!form.name && form.name !== ''" class="p-error">Package name is required</small>
            </div>

            <div class="field">
              <label for="clone-price" class="required">Package Price</label>
              <InputNumber 
                id="clone-price"
                v-model="form.price"
                class="w-100"
                :min="0"
                :minFractionDigits="2"
                :maxFractionDigits="2"
                placeholder="0.00"
              />
              <small class="text-muted">Set the price for the new package</small>
            </div>
          </div>
        </template>
      </Card>

      <!-- What will be copied -->
      <div class="clone-info alert alert-info">
        <div class="d-flex gap-2">
          <i class="fas fa-info-circle mt-1"></i>
          <div>
            <strong>What will be copied:</strong>
            <ul class="mb-0 mt-1">
              <li>All prestations from the original package</li>
              <li>Package description</li>
              <li>Active/inactive status</li>
            </ul>
            <small class="text-muted">Only the name and price will be different.</small>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="modal-footer">
        <Button 
          label="Cancel"
          icon="pi pi-times"
          class="p-button-text p-button-secondary"
          @click="handleCancel"
          :disabled="saving"
        />
        <Button 
          label="Clone Package"
          icon="pi pi-copy"
          class="p-button-primary"
          @click="handleSubmit"
          :loading="saving"
          :disabled="!isFormValid"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.clone-form {
  max-height: 70vh;
  overflow-y: auto;
}

.source-package-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--surface-200);
}

.info-row:last-child {
  border-bottom: none;
}

.label {
  color: var(--text-color-secondary);
  font-size: 0.9rem;
}

.value {
  color: var(--text-color);
  font-size: 0.9rem;
}

.form-fields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field label {
  font-weight: 600;
  color: var(--text-color);
}

.field label.required::after {
  content: ' *';
  color: var(--red-500);
}

.clone-info {
  background: var(--blue-50);
  border: 1px solid var(--blue-200);
  border-radius: 8px;
  padding: 1rem;
}

.clone-info ul {
  padding-left: 1.2rem;
}

.clone-info li {
  margin-bottom: 0.25rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

.p-card {
  border-radius: 12px;
}

.p-error {
  color: var(--red-500);
  font-size: 0.875rem;
}

.p-invalid {
  border-color: var(--red-500);
}

@media (max-width: 768px) {
  .modal-footer {
    flex-direction: column-reverse;
  }
  
  .modal-footer button {
    width: 100%;
  }
  
  .clone-form {
    padding: 0 0.5rem;
  }
}
</style>