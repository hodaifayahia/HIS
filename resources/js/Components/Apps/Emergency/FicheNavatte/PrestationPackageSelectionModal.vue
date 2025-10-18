<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import Chip from 'primevue/chip'
import RadioButton from 'primevue/radiobutton'

// Services
import { ficheNavetteService } from '../../../../Components/Apps/services/Recption/ficheNavetteService'

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  ficheId: {
    type: [String, Number],
    required: true
  },
  prestations: {
    type: Array,
    default: () => []
  },
  packages: {
    type: Array,
    default: () => []
  },
  doctors: {
    type: Array,
    default: () => []
  },
  existingItems: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['update:visible', 'item-added'])

// Composables
const toast = useToast()

// State
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const form = reactive({
  type: '', // 'prestation' or 'package'
  prestation_id: null,
  package_id: null,
  doctor_id: null,
  custom_name: '',
  use_predefined_name: true
})

const saving = ref(false)

// Computed
const typeOptions = [
  { label: 'Individual Prestation', value: 'prestation' },
  { label: 'Package', value: 'package' }
]

const availablePrestations = computed(() => {
  return props.prestations.filter(prestation => 
    !props.existingItems.some(item => 
      item.prestation_id === prestation.id && !item.package_id
    )
  )
})

const availablePackages = computed(() => {
  return props.packages.filter(pkg => pkg.is_active)
})

const selectedPrestation = computed(() => {
  if (!form.prestation_id) return null
  return props.prestations.find(p => p.id === form.prestation_id)
})

const selectedPackage = computed(() => {
  if (!form.package_id) return null
  return props.packages.find(p => p.id === form.package_id)
})

const selectedDoctor = computed(() => {
  if (!form.doctor_id) return null
  return props.doctors.find(d => d.id === form.doctor_id)
})

const isFormValid = computed(() => {
  if (!form.type || !form.doctor_id) return false
  
  if (form.type === 'prestation') {
    return !!form.prestation_id
  }
  
  if (form.type === 'package') {
    return !!form.package_id && (form.use_predefined_name || form.custom_name.trim())
  }
  
  return false
})

const requiredPrestations = computed(() => {
  if (!selectedPrestation.value?.required_prestations_info) return []
  
  const requiredIds = selectedPrestation.value.required_prestations_info
    .filter(id => id !== "!" && !isNaN(parseInt(id)))
    .map(id => parseInt(id))
  
  return props.prestations.filter(p => requiredIds.includes(p.id))
})

// Methods
const resetForm = () => {
  Object.assign(form, {
    type: '',
    prestation_id: null,
    package_id: null,
    doctor_id: null,
    custom_name: '',
    use_predefined_name: true
  })
}

const createItem = async () => {
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
    if (form.type === 'prestation') {
      await createPrestationItem()
    } else if (form.type === 'package') {
      await createPackageItem()
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to add item',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const createPrestationItem = async () => {
  const prestationData = {
    prestation_id: form.prestation_id,
    doctor_id: form.doctor_id,
    status: 'pending',
    base_price: selectedPrestation.value.public_price,
    final_price: selectedPrestation.value.public_price,
    patient_share: selectedPrestation.value.public_price
  }

  // Mock response - replace with actual service call
  const result = { success: true, data: prestationData }
  
  if (result.success) {
    emit('item-added', result.data)
    resetForm()
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Prestation added successfully',
      life: 3000
    })
  } else {
    throw new Error(result.message || 'Failed to add prestation')
  }
}

const createPackageItem = async () => {
  const packageData = {
    package_id: form.package_id,
    doctor_id: form.doctor_id,
    custom_name: form.use_predefined_name ? null : form.custom_name,
    status: 'pending'
  }

  // Mock response - replace with actual service call
  const result = { success: true, data: packageData }
  
  if (result.success) {
    emit('item-added', result.data)
    resetForm()
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Package added successfully',
      life: 3000
    })
  } else {
    throw new Error(result.message || 'Failed to add package')
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

// Watchers
watch(() => props.visible, (newVal) => {
  if (newVal) {
    resetForm()
  }
})

watch(() => form.type, () => {
  form.prestation_id = null
  form.package_id = null
})
</script>

<template>
  <Dialog 
    v-model:visible="dialogVisible"
    header="Add Prestation or Package"
    :style="{ width: '800px', maxHeight: '90vh' }"
    :modal="true"
    class="prestation-form-modal"
  >
    <div class="form-container">
      <!-- Step 1: Type Selection -->
      <Card class="mb-4">
        <template #title>
          <div class="step-header">
            <i class="pi pi-th-large text-primary"></i>
            <span>Step 1: Select Type</span>
          </div>
        </template>
        <template #content>
          <div class="type-selection">
            <div 
              v-for="option in typeOptions"
              :key="option.value"
              class="type-option"
              :class="{ 'selected': form.type === option.value }"
              @click="form.type = option.value"
            >
              <RadioButton 
                :id="option.value"
                v-model="form.type" 
                :value="option.value"
              />
              <label :for="option.value" class="type-label">
                <div class="type-info">
                  <i :class="option.value === 'prestation' ? 'pi pi-medical' : 'pi pi-box'"></i>
                  <div>
                    <div class="type-name">{{ option.label }}</div>
                    <div class="type-description">
                      {{ option.value === 'prestation' ? 'Add individual medical service' : 'Add package with multiple services' }}
                    </div>
                  </div>
                </div>
              </label>
            </div>
          </div>
        </template>
      </Card>

      <!-- Step 2: Doctor Selection -->
      <Card class="mb-4" v-if="form.type">
        <template #title>
          <div class="step-header">
            <i class="pi pi-user-md text-primary"></i>
            <span>Step 2: Select Doctor</span>
          </div>
        </template>
        <template #content>
          <div class="field">
            <label for="doctor" class="field-label required">Attending Doctor</label>
            <Dropdown 
              id="doctor"
              v-model="form.doctor_id"
              :options="doctors"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose attending doctor"
              class="w-100"
              :class="{ 'p-invalid': !form.doctor_id }"
            >
              <template #option="{ option }">
                <div class="doctor-option">
                  <div class="doctor-avatar">
                    <i class="pi pi-user"></i>
                  </div>
                  <div class="doctor-info">
                    <div class="doctor-name">{{ option.name }}</div>
                    <small class="doctor-specialization">{{ option.specialization }}</small>
                  </div>
                </div>
              </template>
            </Dropdown>
          </div>
        </template>
      </Card>

      <!-- Step 3: Prestation Selection -->
      <Card class="mb-4" v-if="form.type === 'prestation' && form.doctor_id">
        <template #title>
          <div class="step-header">
            <i class="pi pi-medical text-primary"></i>
            <span>Step 3: Select Prestation</span>
          </div>
        </template>
        <template #content>
          <div class="field">
            <label for="prestation" class="field-label required">Medical Service</label>
            <Dropdown 
              id="prestation"
              v-model="form.prestation_id"
              :options="availablePrestations"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose medical service"
              class="w-100"
              filter
              :class="{ 'p-invalid': !form.prestation_id }"
            >
              <template #option="{ option }">
                <div class="prestation-option">
                  <div class="prestation-header">
                    <span class="prestation-name">{{ option.name }}</span>
                    <Chip 
                      :label="formatCurrency(option.public_price)"
                      severity="success"
                      class="price-chip"
                    />
                  </div>
                  <small class="prestation-code">Code: {{ option.internal_code }}</small>
                </div>
              </template>
            </Dropdown>
          </div>

          <!-- Selected Prestation Preview -->
          <div v-if="selectedPrestation" class="preview-section">
            <Divider />
            <h6 class="preview-title">Selected Prestation</h6>
            <Card class="prestation-preview">
              <template #content>
                <div class="preview-content">
                  <div class="preview-header">
                    <h6 class="prestation-title">{{ selectedPrestation.name }}</h6>
                    <Tag 
                      :label="formatCurrency(selectedPrestation.public_price)"
                      severity="success"
                      class="price-tag"
                    />
                  </div>
                  <p class="prestation-code">Code: {{ selectedPrestation.internal_code }}</p>
                  <p v-if="selectedPrestation.description" class="prestation-description">
                    {{ selectedPrestation.description }}
                  </p>
                </div>
              </template>
            </Card>

            <!-- Dependencies -->
            <div v-if="requiredPrestations.length > 0" class="dependencies-section">
              <h6 class="dependencies-title">
                <i class="pi pi-sitemap mr-2"></i>
                Required Dependencies ({{ requiredPrestations.length }})
              </h6>
              <div class="dependencies-grid">
                <div 
                  v-for="dep in requiredPrestations"
                  :key="dep.id"
                  class="dependency-item"
                >
                  <div class="dependency-content">
                    <div class="dependency-header">
                      <span class="dependency-name">{{ dep.name }}</span>
                      <Chip label="Required" severity="secondary" class="required-chip" />
                    </div>
                    <small class="dependency-code">{{ dep.internal_code }}</small>
                    <div class="dependency-price">{{ formatCurrency(dep.public_price) }}</div>
                  </div>
                </div>
              </div>
              <div class="dependencies-note">
                <i class="pi pi-info-circle mr-1"></i>
                <small>These services will be automatically added as dependencies</small>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Step 3: Package Selection -->
      <Card class="mb-4" v-if="form.type === 'package' && form.doctor_id">
        <template #title>
          <div class="step-header">
            <i class="pi pi-box text-primary"></i>
            <span>Step 3: Select Package</span>
          </div>
        </template>
        <template #content>
          <div class="field">
            <label for="package" class="field-label required">Service Package</label>
            <Dropdown 
              id="package"
              v-model="form.package_id"
              :options="availablePackages"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose service package"
              class="w-100"
              filter
              :class="{ 'p-invalid': !form.package_id }"
            >
              <template #option="{ option }">
                <div class="package-option">
                  <div class="package-header">
                    <span class="package-name">{{ option.name }}</span>
                    <Chip 
                      :label="formatCurrency(option.price)"
                      severity="info"
                      class="price-chip"
                    />
                  </div>
                  <small class="package-description">{{ option.description }}</small>
                  <div class="package-items-count">
                    <Chip 
                      :label="`${option.items?.length || 0} services included`"
                      severity="secondary"
                      class="items-chip"
                    />
                  </div>
                </div>
              </template>
            </Dropdown>
          </div>

          <!-- Package Name Options -->
          <div v-if="selectedPackage" class="package-naming">
            <Divider />
            <h6 class="naming-title">Package Display Name</h6>
            <div class="naming-options">
              <div class="naming-option">
                <RadioButton 
                  id="predefined-name" 
                  v-model="form.use_predefined_name" 
                  :value="true"
                />
                <label for="predefined-name" class="naming-label">
                  Use standard name: <strong>"{{ selectedPackage.name }}"</strong>
                </label>
              </div>
              <div class="naming-option">
                <RadioButton 
                  id="custom-name" 
                  v-model="form.use_predefined_name" 
                  :value="false"
                />
                <label for="custom-name" class="naming-label">
                  Use custom name
                </label>
              </div>
            </div>
            
            <div v-if="!form.use_predefined_name" class="field mt-3">
              <InputText 
                v-model="form.custom_name"
                placeholder="Enter custom package name"
                class="w-100"
                :class="{ 'p-invalid': !form.use_predefined_name && !form.custom_name.trim() }"
              />
            </div>

            <!-- Package Preview -->
            <div class="preview-section">
              <h6 class="preview-title">Package Details</h6>
              <Card class="package-preview">
                <template #content>
                  <div class="preview-content">
                    <div class="preview-header">
                      <h6 class="package-title">{{ selectedPackage.name }}</h6>
                      <Tag 
                        :label="formatCurrency(selectedPackage.price)"
                        severity="info"
                        class="price-tag"
                      />
                    </div>
                    <p class="package-description">{{ selectedPackage.description }}</p>
                    <div class="package-services">
                      <h6 class="services-title">Included Services ({{ selectedPackage.items?.length || 0 }})</h6>
                      <div class="services-list">
                        <Chip 
                          v-for="item in selectedPackage.items"
                          :key="item.id"
                          :label="item.prestation?.name"
                          class="service-chip"
                        />
                      </div>
                    </div>
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
          @click="dialogVisible = false"
          :disabled="saving"
        />
        <Button 
          :label="`Add ${form.type === 'package' ? 'Package' : 'Prestation'}`"
          icon="pi pi-plus"
          class="p-button-primary"
          @click="createItem"
          :loading="saving"
          :disabled="!isFormValid"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.prestation-form-modal {
  font-family: 'Inter', sans-serif;
}

.form-container {
  max-height: 70vh;
  overflow-y: auto;
  padding: 0 0.5rem;
}

.step-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  color: var(--text-color);
}

.type-selection {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.type-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid var(--surface-200);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.type-option:hover {
  border-color: var(--primary-300);
  background: var(--primary-50);
}

.type-option.selected {
  border-color: var(--primary-color);
  background: var(--primary-50);
}

.type-label {
  cursor: pointer;
  flex: 1;
}

.type-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.type-info i {
  font-size: 1.5rem;
  color: var(--primary-color);
}

.type-name {
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.25rem;
}

.type-description {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
}

.field {
  margin-bottom: 1rem;
}

.field-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-color);
}

.field-label.required::after {
  content: ' *';
  color: var(--red-500);
}

.doctor-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 0;
}

.doctor-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--primary-100);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
}

.doctor-name {
  font-weight: 600;
  color: var(--text-color);
}

.doctor-specialization {
  color: var(--text-color-secondary);
}

.prestation-option,
.package-option {
  padding: 0.75rem 0;
}

.prestation-header,
.package-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.prestation-name,
.package-name {
  font-weight: 600;
  color: var(--text-color);
}

.prestation-code,
.package-description {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
}

.package-items-count {
  margin-top: 0.5rem;
}

.price-chip {
  font-weight: 600;
}

.preview-section {
  margin-top: 1.5rem;
}

.preview-title {
  margin-bottom: 1rem;
  color: var(--text-color);
}

.preview-content {
  padding: 0;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.prestation-title,
.package-title {
  margin: 0;
  color: var(--text-color);
}

.prestation-description,
.package-description {
  margin: 0.5rem 0;
  color: var(--text-color-secondary);
  line-height: 1.5;
}

.dependencies-section {
  margin-top: 1.5rem;
}

.dependencies-title {
  margin-bottom: 1rem;
  color: var(--text-color);
  display: flex;
  align-items: center;
}

.dependencies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.dependency-item {
  border: 1px solid var(--surface-200);
  border-radius: 8px;
  padding: 1rem;
  background: var(--surface-50);
}

.dependency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.dependency-name {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--text-color);
}

.dependency-code {
  color: var(--text-color-secondary);
  font-size: 0.75rem;
}

.dependency-price {
  font-weight: 600;
  color: var(--primary-color);
  margin-top: 0.5rem;
}

.dependencies-note {
  color: var(--text-color-secondary);
  font-style: italic;
  display: flex;
  align-items: center;
}

.package-naming {
  margin-top: 1rem;
}

.naming-title {
  margin-bottom: 1rem;
  color: var(--text-color);
}

.naming-options {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1rem;
}

.naming-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.naming-label {
  cursor: pointer;
  color: var(--text-color);
}

.package-services {
  margin-top: 1rem;
}

.services-title {
  margin-bottom: 0.75rem;
  color: var(--text-color);
}

.services-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.service-chip {
  font-size: 0.75rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

@media (max-width: 768px) {
  .form-container {
    padding: 0;
  }
  
  .type-selection {
    gap: 0.75rem;
  }
  
  .type-option {
    padding: 0.75rem;
  }
  
  .dependencies-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column-reverse;
  }
  
  .modal-footer button {
    width: 100%;
  }
}

.p-invalid {
  border-color: var(--red-500);
}
</style>