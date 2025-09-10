<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import MultiSelect from 'primevue/multiselect'
import Card from 'primevue/card'
import Chip from 'primevue/chip'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import ProgressSpinner from 'primevue/progressspinner'
import Checkbox from 'primevue/checkbox'

// Services
import prestationPackageService from '../../../../Components/Apps/services/Prestation/prestationPackageService.js'
import prestationService from '../../../../Components/Apps/services/Prestation/prestationService.js'
import specializationService from '../../../../Components/Apps/services/specialization/specializationService.js'

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  packageData: {
    type: Object,
    default: null
  },
  isEditMode: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:visible', 'package-saved'])

// Composables
const toast = useToast()

// State
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const form = reactive({
  name: '',
  description: '',
  price: 0,
  is_active: true, // <-- default to true
  prestations: []
})

const availablePrestations = ref([])
const availableSpecializations = ref([])
const selectedSpecializations = ref([])
const loading = ref(false)
const saving = ref(false)
const prestationsLoading = ref(false)
const specializationsLoading = ref(false)

// Computed
const modalTitle = computed(() => {
  return props.isEditMode ? 'Edit Package' : 'Create New Package'
})

const submitButtonLabel = computed(() => {
  return props.isEditMode ? 'Update Package' : 'Create Package'
})

const submitButtonIcon = computed(() => {
  return props.isEditMode ? 'pi pi-check' : 'pi pi-plus'
})

const filteredPrestations = computed(() => {
  if (selectedSpecializations.value.length === 0) {
    return availablePrestations.value
  }
  
  return availablePrestations.value.filter(prestation => {
    return selectedSpecializations.value.includes(prestation.specialization_id)
  })
})

const selectedPrestationsDetails = computed(() => {
  return form.prestations.map(prestationId => {
    return availablePrestations.value.find(p => p.id === prestationId)
  }).filter(Boolean)
})

const totalEstimatedPrice = computed(() => {
  return selectedPrestationsDetails.value.reduce((total, prestation) => {
    return total + (prestation.public_price || 0)
  }, 0)
})

const isFormValid = computed(() => {
  return form.name && form.name.trim().length > 0 && form.prestations.length > 0
})

// Methods
const loadSpecializations = async () => {
  specializationsLoading.value = true
  try {
    const response = await specializationService.getAll()
    if (response.success) {
      availableSpecializations.value = response.data || []
    } else {
      throw new Error(response.message || 'Failed to load specializations')
    }
  } catch (error) {
    console.error('Error loading specializations:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load specializations',
      life: 3000
    })
  } finally {
    specializationsLoading.value = false
  }
}

const loadPrestations = async () => {
  prestationsLoading.value = true
  try {
    const response = await prestationService.getAll()
    if (response.success) {
      availablePrestations.value = response.data || []
    } else {
      throw new Error(response.message || 'Failed to load prestations')
    }
  } catch (error) {
    console.error('Error loading prestations:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load prestations',
      life: 3000
    })
  } finally {
    prestationsLoading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, {
    name: '',
    description: '',
    price: 0,
    is_active: true, // <-- default to true
    prestations: []
  })
  selectedSpecializations.value = []
}

const populateForm = () => {
  if (props.packageData) {
    Object.assign(form, {
      name: props.packageData.name || '',
      description: props.packageData.description || '',
      price: props.packageData.price || 0,
      is_active: props.packageData.is_active ?? true,
      prestations: props.packageData.items?.map(item => item.prestation_id) || []
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

  saving.value = true
  
  try {
    const payload = {
      name: form.name.trim(),
      description: form.description?.trim() || null,
      price: form.price || 0,
      is_active: form.is_active,
      prestations: form.prestations
    }

    let response
    let mode = 'create'

    if (props.isEditMode && props.packageData) {
      response = await prestationPackageService.update(props.packageData.id, payload)
      mode = 'edit'
    } else {
      response = await prestationPackageService.create(payload)
      mode = 'create'
    }

    if (response.success) {
      // Emit the saved package data with populated items
      const savedPackage = {
        ...response.data,
        items: form.prestations.map(prestationId => {
          const prestation = availablePrestations.value.find(p => p.id === prestationId)
          return {
            prestation_id: prestationId,
            prestation: prestation
          }
        })
      }
      
      emit('package-saved', savedPackage, mode)
    } else {
      throw new Error(response.message || `Failed to ${mode} package`)
    }
  } catch (error) {
    console.error('Error saving package:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to save package',
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

const removePrestationFromSelection = (prestationId) => {
  form.prestations = form.prestations.filter(id => id !== prestationId)
}

const useEstimatedPrice = () => {
  form.price = totalEstimatedPrice.value
}

// Watchers
watch(() => props.visible, (newVal) => {
  if (newVal) {
    if (props.isEditMode) {
      populateForm()
    } else {
      resetForm()
    }
  }
})

// Watch for specialization filter changes and clear invalid prestation selections
watch(selectedSpecializations, () => {
  if (selectedSpecializations.value.length > 0) {
    // Remove prestations that don't match the selected specializations
    form.prestations = form.prestations.filter(prestationId => {
      const prestation = availablePrestations.value.find(p => p.id === prestationId)
      return prestation && selectedSpecializations.value.includes(prestation.specialization_id)
    })
  }
})

// Lifecycle
onMounted(() => {
  loadPrestations()
  loadSpecializations()
})
</script>

<template>
  <Dialog 
    v-model:visible="dialogVisible"
    :header="modalTitle"
    :style="{ width: '900px', maxHeight: '90vh' }"
    :modal="true"
    class="package-modal"
    :closable="!saving"
  >
    <div class="package-form">
      <!-- Basic Information -->
      <Card class="mb-4">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="fas fa-info-circle text-primary"></i>
            Basic Information
          </div>
        </template>
        <template #content>
          <div class="form-grid">
            <div class="field">
              <label for="package-name" class="required">Package Name</label>
              <InputText 
                id="package-name"
                v-model="form.name"
                placeholder="Enter package name"
                class="w-100"
                :invalid="!form.name && form.name !== ''"
              />
              <small v-if="!form.name && form.name !== ''" class="p-error">Package name is required</small>
            </div>

            <div class="field">
              <label for="package-price">Package Price</label>
              <InputNumber 
                id="package-price"
                v-model="form.price"
                class="w-100"
                :min="0"
              />
            </div>

            <div class="field full-width">
              <label for="package-description">Description</label>
              <Textarea 
                id="package-description"
                v-model="form.description"
                rows="3"
                placeholder="Enter package description (optional)"
                class="w-100"
              />
            </div>

            <div class="field">
              <div class="d-flex align-items-center gap-2">
                <Checkbox 
                  id="package-active"
                  v-model="form.is_active"
                  :binary="true"
                />
                <label for="package-active">Active Package</label>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Prestations Selection -->
      <Card class="mb-4">
        <template #title>
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="fas fa-list text-primary"></i>
              Prestations
              <Tag 
                v-if="form.prestations.length > 0"
                :value="`${form.prestations.length} selected`"
                severity="info"
                class="ml-2"
              />
            </div>
            <Button 
              v-if="totalEstimatedPrice > 0 && totalEstimatedPrice !== form.price"
              label="Use Estimated Price"
              icon="pi pi-calculator"
              class="p-button-sm p-button-outlined"
              @click="useEstimatedPrice"
            />
          </div>
        </template>
        <template #content>
          <div class="prestations-section">
            <!-- Filters Row -->
            <div class="filters-row mb-3">
              <div class="field">
                <label for="specializations-filter">Filter by Specialization</label>
                <MultiSelect 
                  id="specializations-filter"
                  v-model="selectedSpecializations"
                  :options="availableSpecializations"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Filter prestations by specialization"
                  class="w-100"
                  filter
                  :loading="specializationsLoading"
                >
                  <template #option="{ option }">
                    <div class="d-flex align-items-center">
                      <div>
                        <div class="fw-bold">{{ option.name }}</div>
                        <small class="text-muted">{{ option.description || 'No description' }}</small>
                      </div>
                    </div>
                  </template>
                </MultiSelect>
                <small class="text-muted">Optional: Filter prestations by specialization to narrow down options</small>
              </div>
            </div>

            <div class="field mb-3">
              <label for="prestations-select" class="required">Select Prestations</label>
              <MultiSelect 
                id="prestations-select"
                v-model="form.prestations"
                :options="filteredPrestations"
                optionLabel="name"
                optionValue="id"
                placeholder="Choose prestations for this package"
                class="w-100"
                filter
                :loading="prestationsLoading"
                :invalid="form.prestations.length === 0"
              >
                <template #option="{ option }">
                  <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                      <div class="fw-bold">{{ option.name }}</div>
                      <small class="text-muted">{{ option.internal_code }}</small>
                      <small v-if="option.specialization" class="text-muted d-block">
                        <i class="fas fa-stethoscope me-1"></i>{{ option.specialization?.name }}
                      </small>
                    </div>
                    <div class="text-primary fw-bold">
                      {{ formatCurrency(option.public_price) }}
                    </div>
                  </div>
                </template>
              </MultiSelect>
              <small v-if="form.prestations.length === 0" class="p-error">At least one prestation is required</small>
              <small v-if="selectedSpecializations.length > 0" class="text-info d-block mt-1">
                <i class="fas fa-filter me-1"></i>Showing prestations filtered by {{ selectedSpecializations.length }} specialization(s)
              </small>
            </div>

            <!-- Selected Prestations Preview -->
            <div v-if="selectedPrestationsDetails.length > 0" class="selected-prestations">
              <Divider />
              <h6 class="mb-3">Selected Prestations ({{ selectedPrestationsDetails.length }})</h6>
              
              <div class="prestations-grid">
                <Card 
                  v-for="prestation in selectedPrestationsDetails"
                  :key="prestation.id"
                  class="prestation-card"
                >
                  <template #content>
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="flex-grow-1">
                        <h6 class="mb-1">{{ prestation.name }}</h6>
                        <small class="text-muted d-block mb-1">{{ prestation.internal_code }}</small>
                        <small v-if="prestation.specialization" class="text-muted d-block mb-2">
                          <i class="fas fa-stethoscope me-1"></i>{{ prestation.specialization?.name }}
                        </small>
                        <div class="text-primary fw-bold">
                          {{ formatCurrency(prestation.public_price) }}
                        </div>
                      </div>
                      <Button 
                        icon="pi pi-times"
                        class="p-button-sm p-button-text p-button-danger"
                        @click="removePrestationFromSelection(prestation.id)"
                        v-tooltip.top="'Remove from package'"
                      />
                    </div>
                  </template>
                </Card>
              </div>

              <!-- Price Summary -->
              <Card class="mt-3 price-summary">
                <template #content>
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="text-muted small">Estimated Price (sum of prestations)</div>
                      <div class="h5 mb-0 text-success">{{ formatCurrency(totalEstimatedPrice) }}</div>
                    </div>
                    <div class="text-end">
                      <div class="text-muted small">Package Price</div>
                      <div class="h5 mb-0 text-primary">{{ formatCurrency(form.price) }}</div>
                    </div>
                  </div>
                  <div v-if="totalEstimatedPrice !== form.price" class="mt-2">
                    <small class="text-muted">
                      <i class="fas fa-info-circle me-1"></i>
                      Package price differs from estimated price by 
                      <span :class="form.price > totalEstimatedPrice ? 'text-success' : 'text-danger'">
                        {{ formatCurrency(Math.abs(form.price - totalEstimatedPrice)) }}
                      </span>
                    </small>
                  </div>
                </template>
              </Card>
            </div>
          </div>
        </template>
      </Card>
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
          :label="submitButtonLabel"
          :icon="submitButtonIcon"
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
.package-form {
  max-height: 70vh;
  overflow-y: auto;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  align-items: start;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field.full-width {
  grid-column: 1 / -1;
}

.field label {
  font-weight: 600;
  color: var(--text-color);
}

.field label.required::after {
  content: ' *';
  color: var(--red-500);
}

.prestations-section {
  width: 100%;
}

.filters-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 8px;
  border: 1px solid var(--surface-200);
}

.prestations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.prestation-card {
  border: 1px solid var(--surface-200);
  border-radius: 8px;
  background: var(--surface-50);
}

.prestation-card:hover {
  border-color: var(--primary-color);
  background: var(--primary-50);
}

.price-summary {
  background: var(--surface-50);
  border: 2px solid var(--primary-200);
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

.p-multiselect {
  min-height: 3rem;
}

.text-info {
  color: var(--blue-500);
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .prestations-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column-reverse;
  }
  
  .modal-footer button {
    width: 100%;
  }
  
  .package-form {
    padding: 0 0.5rem;
  }
  
  .filters-row {
    padding: 0.75rem;
  }
}

.p-error {
  color: var(--red-500);
  font-size: 0.875rem;
}

.p-invalid {
  border-color: var(--red-500);
}
</style>