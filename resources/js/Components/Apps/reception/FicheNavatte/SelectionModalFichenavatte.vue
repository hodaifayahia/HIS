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

// Services
import { ficheNavetteService } from '../../../../services/ficheNavetteService'

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
    patient_share: selectedPrestation.value.public_price,
    dependency_prestation_ids: requiredPrestations.value.map(p => p.id)
  }

  const result = await ficheNavetteService.addPrestation(props.ficheId, prestationData)
  
  if (result.success) {
    emit('item-added', result.data)
    resetForm()
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

  const result = await ficheNavetteService.addPackage(props.ficheId, packageData)
  
  if (result.success) {
    emit('item-added', result.data)
    resetForm()
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
    class="selection-modal"
  >
    <div class="selection-form">
      <!-- Type Selection -->
      <Card class="mb-4">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="pi pi-th-large text-primary"></i>
            Select Type
          </div>
        </template>
        <template #content>
          <div class="field">
            <label for="type" class="required">Type</label>
            <Dropdown 
              id="type"
              v-model="form.type"
              :options="typeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Choose type"
              class="w-100"
            />
          </div>
        </template>
      </Card>

      <!-- Doctor Selection -->
      <Card class="mb-4" v-if="form.type">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="pi pi-user-md text-primary"></i>
            Select Doctor
          </div>
        </template>
        <template #content>
          <div class="field">
            <label for="doctor" class="required">Doctor</label>
            <Dropdown 
              id="doctor"
              v-model="form.doctor_id"
              :options="doctors"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose doctor"
              class="w-100"
            >
              <template #option="{ option }">
                <div class="doctor-option">
                  <div class="doctor-name">{{ option.name }}</div>
                  <small class="doctor-specialization">{{ option.specialization }}</small>
                </div>
              </template>
            </Dropdown>
          </div>
        </template>
      </Card>

      <!-- Prestation Selection -->
      <Card class="mb-4" v-if="form.type === 'prestation'">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="pi pi-medical text-primary"></i>
            Select Prestation
          </div>
        </template>
        <template #content>
          <div class="field">
            <label for="prestation" class="required">Prestation</label>
            <Dropdown 
              id="prestation"
              v-model="form.prestation_id"
              :options="availablePrestations"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose prestation"
              class="w-100"
              filter
            >
              <template #option="{ option }">
                <div class="prestation-option">
                  <div class="prestation-header">
                    <span class="prestation-name">{{ option.name }}</span>
                    <Chip 
                      :label="formatCurrency(option.public_price)"
                      class="price-chip"
                    />
                  </div>
                  <small class="prestation-code">{{ option.internal_code }}</small>
                </div>
              </template>
            </Dropdown>
          </div>

          <!-- Selected Prestation Preview -->
          <div v-if="selectedPrestation" class="selected-item-preview">
            <Divider />
            <h6>Selected Prestation</h6>
            <Card class="prestation-preview">
              <template #content>
                <div class="preview-content">
                  <div class="preview-header">
                    <h6 class="mb-1">{{ selectedPrestation.name }}</h6>
                    <Tag 
                      :label="formatCurrency(selectedPrestation.public_price)"
                      severity="success"
                    />
                  </div>
                  <p class="preview-code">{{ selectedPrestation.internal_code }}</p>
                  <p v-if="selectedPrestation.description" class="preview-description">
                    {{ selectedPrestation.description }}
                  </p>
                </div>
              </template>
            </Card>

            <!-- Required Dependencies -->
            <div v-if="requiredPrestations.length > 0" class="dependencies-section">
              <h6 class="dependencies-title">
                <i class="pi pi-sitemap mr-2"></i>
                Required Dependencies ({{ requiredPrestations.length }})
              </h6>
              <div class="dependencies-grid">
                <Card 
                  v-for="dep in requiredPrestations"
                  :key="dep.id"
                  class="dependency-card"
                >
                  <template #content>
                    <div class="dependency-content">
                      <div class="dependency-header">
                        <span class="dependency-name">{{ dep.name }}</span>
                        <Chip label="Required" severity="secondary" class="required-chip" />
                      </div>
                      <small class="dependency-code">{{ dep.internal_code }}</small>
                      <div class="dependency-price">{{ formatCurrency(dep.public_price) }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <small class="dependencies-note">
                <i class="pi pi-info-circle mr-1"></i>
                These prestations will be automatically added as required
              </small>
            </div>
          </div>
        </template>
      </Card>

      <!-- Package Selection -->
      <Card class="mb-4" v-if="form.type === 'package'">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="pi pi-box text-primary"></i>
            Select Package
          </div>
        </template>
        <template #content>
          <div class="field">
            <label for="package" class="required">Package</label>
            <Dropdown 
              id="package"
              v-model="form.package_id"
              :options="availablePackages"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose package"
              class="w-100"
              filter
            >
              <template #option="{ option }">
                <div class="package-option">
                  <div class="package-header">
                    <span class="package-name">{{ option.name }}</span>
                    <Chip 
                      :label="formatCurrency(option.price)"
                      class="price-chip"
                    />
                  </div>
                  <small class="package-description">{{ option.description }}</small>
                  <div class="package-items">
                    <Chip 
                      :label="`${option.items?.length || 0} prestations`"
                      severity="info"
                      class="items-chip"
                    />
                  </div>
                </div>
              </template>
            </Dropdown>
          </div>

          <!-- Package Name Options -->
          <div v-if="selectedPackage" class="package-name-section">
            <Divider />
            <h6>Package Name</h6>
            <div class="name-options">
              <div class="name-option">
                <input 
                  type="radio" 
                  id="predefined-name" 
                  v-model="form.use_predefined_name" 
                  :value="true"
                />
                <label for="predefined-name">Use predefined name: "{{ selectedPackage.name }}"</label>
              </div>
              <div class="name-option">
                <input 
                  type="radio" 
                  id="custom-name" 
                  v-model="form.use_predefined_name" 
                  :value="false"
                />
                <label for="custom-name">Use custom name</label>
              </div>
            </div>
            
            <div v-if="!form.use_predefined_name" class="field mt-3">
              <InputText 
                v-model="form.custom_name"
                placeholder="Enter custom package name"
                class="w-100"
              />
            </div>

            <!-- Selected Package Preview -->
            <div class="selected-item-preview">
              <h6>Selected Package</h6>
              <Card class="package-preview">
                <template #content>
                  <div class="preview-content">
                    <div class="preview-header">
                      <h6 class="mb-1">{{ selectedPackage.name }}</h6>
                      <Tag 
                        :label="formatCurrency(selectedPackage.price)"
                        severity="success"
                      />
                    </div>
                    <p class="preview-description">{{ selectedPackage.description }}</p>
                    <div class="package-prestations">
                      <h6>Included Prestations ({{ selectedPackage.items?.length || 0 }})</h6>
                      <div class="prestations-list">
                        <Chip 
                          v-for="item in selectedPackage.items"
                          :key="item.id"
                          :label="item.prestation?.name"
                          class="prestation-chip"
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
          label="Create"
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
.selection-form {
  max-height: 70vh;
  overflow-y: auto;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.field label {
  font-weight: 600;
  color: var(--text-color);
}

.field label.required::after {
  content: ' *';
  color: var(--red-500);
}

.doctor-option,
.prestation-option,
.package-option {
  padding: 0.5rem 0;
}

.doctor-name,
.prestation-name,
.package-name {
  font-weight: 600;
  color: var(--text-color);
}

.doctor-specialization,
.prestation-code,
.package-description {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
}

.prestation-header,
.package-header {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.price-chip {
  font-weight: 600;
}

.package-items {
  margin-top: 0.5rem;
}

.selected-item-preview {
  margin-top: 1rem;
}

.preview-content {
  padding: 0;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.preview-code,
.preview-description {
  margin: 0.25rem 0;
  color: var(--text-color-secondary);
  font-size: 0.875rem;
}

.dependencies-section {
  margin-top: 1.5rem;
}

.dependencies-title {
  color: var(--text-color);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.dependencies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.dependency-card {
  border: 1px solid var(--surface-200);
  background: var(--surface-50);
}

.dependency-content {
  padding: 0;
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

.name-options {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin: 1rem 0;
}

.name-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.name-option input[type="radio"] {
  margin-right: 0.5rem;
}

.package-prestations {
  margin-top: 1rem;
}

.prestations-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.prestation-chip {
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
  .selection-form {
    padding: 0 0.5rem;
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
</style>