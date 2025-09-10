<script setup>
import { ref, computed, watch } from 'vue'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'

const props = defineProps({
  specializations: Array,
  allDoctors: Array,
  availablePrestations: Array,
  availablePackages: Array,
  loading: Boolean,
    type: String,
  prestationAppointments: Object,
  appointmentLoading: [String, Number],
  selectedDoctor: [Number, String]
})

const emit = defineEmits(['update:hasSelectedItems', 'takeAppointment', 'cancelAppointment', 'itemsToCreate', 'appointmentRequired'])

const toast = useToast()

// Reactive data
const selectedSpecialization = ref(null)
const selectedDoctorInternal = ref(props.selectedDoctor)
const showPackages = ref(false)
const selectedPrestation = ref(null)
const selectedPackage = ref(null)
const dependencies = ref([])
const selectedDependencies = ref([])
const packagePrestations = ref([])

// Computed properties
const filteredDoctors = computed(() => {
  if (selectedSpecialization.value) {
    return props.allDoctors.filter(doctor => doctor.specialization_id === selectedSpecialization.value)
  }
  return props.allDoctors
})

const filteredPrestations = computed(() => {
  if (!props.availablePrestations.length) return []
  if (selectedSpecialization.value) {
    return props.availablePrestations.filter(p => p.specialization_id === selectedSpecialization.value)
  }
  return props.availablePrestations
})

const hasSelectedItems = computed(() => {
  return selectedPrestation.value !== null || selectedPackage.value !== null
})

const packageTotalPrice = computed(() => {
  if (selectedPackage.value && selectedPackage.value.price) {
    return selectedPackage.value.price
  }
  return 0
})

const prestationsIndividualTotal = computed(() => {
  if (packagePrestations.value && packagePrestations.value.length > 0) {
    return packagePrestations.value.reduce((total, prestation) => {
      return total + (prestation.public_price || 0)
    }, 0)
  }
  return 0
})

const otherItemsCount = computed(() => {
  let count = 0
  if (selectedPrestation.value && !selectedPrestation.value.need_an_appointment) {
    count++
  }
  if (selectedDependencies.value) {
    count += selectedDependencies.value.filter(dep => !dep.need_an_appointment).length
  }
  return count
})

const allSelectedItems = computed(() => {
  const items = [];
  if (selectedPrestation.value) {
    items.push({
      ...selectedPrestation.value,
      type: 'prestation'
    });
  }
  if (selectedPackage.value) {
    items.push({
      ...selectedPackage.value,
      type: 'package'
    });
  }
  if (selectedDependencies.value.length > 0) {
    items.push(...selectedDependencies.value.map(dep => ({
      ...dep,
      type: 'dependency'
    })));
  }
  return items;
});

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'DZD' }).format(amount || 0)
}

const getSeverity = (type) => {
  if (type === 'prestation') return 'info';
  if (type === 'package') return 'success';
  if (type === 'dependency') return 'warning';
  return 'secondary';
};

const onSpecializationChange = async () => {
  selectedDoctorInternal.value = null
  resetSelections()
}

const onPrestationSelect = async (event) => {
  const prestation = event.value
  selectedPrestation.value = prestation
  selectedPackage.value = null
  packagePrestations.value = []
  dependencies.value = []
  selectedDependencies.value = []

  if (prestation && prestation.required_prestations_info) {
    try {
      let dependencyIds = prestation.required_prestations_info
      if (typeof dependencyIds === 'string') {
        dependencyIds = JSON.parse(dependencyIds)
      }
      if (Array.isArray(dependencyIds) && dependencyIds.length > 0) {
        const deps = await fetchPrestationsByIds(dependencyIds)
        dependencies.value = deps
        selectedDependencies.value = [...deps]
      }
    } catch (error) {
      console.error('Error loading dependencies:', error)
      toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load dependencies', life: 3000 })
    }
  }
}

const onPackageSelect = async (event) => {
  const packageItem = event.value
  selectedPackage.value = packageItem
  selectedPrestation.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []

  if (packageItem && packageItem.id) {
    try {
      if (packageItem.items && packageItem.items.length > 0) {
        const prestations = packageItem.items.map(item => ({
          ...item.prestation,
          package_item_id: item.id,
          prestation_package_id: item.prestation_package_id
        }))
        packagePrestations.value = prestations
      } else {
        const result = await ficheNavetteService.getPrestationsPackage(packageItem.id)
        if (result.success) {
          packagePrestations.value = result.data
        } else {
          throw new Error(result.message)
        }
      }
    } catch (error) {
      console.error('Error loading package prestations:', error)
      toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load package prestations', life: 3000 })
    }
  }
}

const fetchPrestationsByIds = async (ids) => {
  try {
    const result = await ficheNavetteService.getPrestationsDependencies(ids)
    return result.success ? result.data : []
  } catch (error) {
    console.error('Error fetching prestations by IDs:', error)
    return []
  }
}

const resetSelections = () => {
  selectedPrestation.value = null
  selectedPackage.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []
}

const createFicheNavette = () => {
  const prestationsToCreate = []
  const appointmentsNeeded = []

  if (selectedPrestation.value) {
    if (selectedPrestation.value.need_an_appointment) {
      appointmentsNeeded.push(selectedPrestation.value)
    } else {
      prestationsToCreate.push(selectedPrestation.value)
    }
  }
  if (selectedDependencies.value) {
    selectedDependencies.value.forEach(dep => {
      if (dep.need_an_appointment) {
        appointmentsNeeded.push(dep)
      } else {
        prestationsToCreate.push(dep)
      }
    })
  }
  if (selectedPackage.value) {
    packagePrestations.value.forEach(p => {
      if (p.need_an_appointment) {
        appointmentsNeeded.push(p)
      } else {
        prestationsToCreate.push(p)
      }
    })
  }
const data = {
  selectedDoctor: selectedDoctorInternal.value,
  selectedSpecialization: selectedSpecialization.value,
  prestations: prestationsToCreate,
  type: props.type, // This will now always be set!
  packages: selectedPackage.value ? [selectedPackage.value] : []
}
  console.log('Fiche Navette Data:', data)

  if (appointmentsNeeded.length > 0) {
    emit('appointmentRequired', {
      appointmentItems: appointmentsNeeded,
      otherItems: data
    })
  } else {
    emit('itemsToCreate', data)
  }
}

// Watchers
watch(hasSelectedItems, (newVal) => {
  emit('update:hasSelectedItems', newVal)
})

watch(() => props.selectedDoctor, (newVal) => {
  selectedDoctorInternal.value = newVal
})

watch(selectedDoctorInternal, () => {
  resetSelections()
})
</script>

<template>
  <div class="prestation-tab">
    <div class="steps-row">
      <div class="step-field">
        <label>Specialization</label>
        <Dropdown
          v-model="selectedSpecialization"
          :options="specializations"
          optionLabel="name"
          optionValue="id"
          placeholder="Select specialization"
          @change="onSpecializationChange"
          class="full-width"
          :loading="loading"
        />
      </div>

      <div class="step-field">
        <label>Doctor</label>
        <Dropdown
          v-model="selectedDoctorInternal"
          :options="filteredDoctors"
          optionLabel="name"
          optionValue="id"
          placeholder="Select doctor"
          :disabled="!selectedSpecialization"
          class="full-width"
          :loading="loading"
        />
      </div>

      <div class="step-field checkbox-field">
        <div class="package-toggle">
          <Checkbox
            v-model="showPackages"
            inputId="showPackages"
            :binary="true"
            :disabled="!selectedSpecialization"
          />
          <label for="showPackages">Packages</label>
        </div>
      </div>

      <div class="step-field prestation-field">
        <label>{{ showPackages ? 'Package' : 'Prestation' }}</label>
        <Dropdown
          v-if="!showPackages"
          v-model="selectedPrestation"
          :options="filteredPrestations"
          optionLabel="name"
          placeholder="Select prestation"
          :disabled="!selectedSpecialization"
          @change="onPrestationSelect"
          class="full-width"
          :filter="true"
          filter-placeholder="Search prestations..."
          :filter-fields="['name', 'internal_code']"
        >
          <template #option="{ option }">
            <div class="dropdown-option">
              <div class="option-main">
                <span class="option-name">{{ option.name }}</span>
                <div class="option-tags">
                  <span class="option-code">{{ option.internal_code }}</span>
                  <Tag
                    v-if="option.need_an_appointment"
                    value="Appointment Required"
                    severity="danger"
                    size="small"
                    class="ml-1"
                  />
                </div>
              </div>
              <span class="option-price">{{ formatCurrency(option.public_price) }}</span>
            </div>
          </template>
        </Dropdown>

        <Dropdown
          v-else
          v-model="selectedPackage"
          :options="availablePackages"
          optionLabel="name"
          placeholder="Select package"
          :disabled="!selectedSpecialization"
          @change="onPackageSelect"
          class="full-width"
          :filter="true"
          filter-placeholder="Search packages..."
          :filter-fields="['name', 'internal_code']"
        >
          <template #option="{ option }">
            <div class="dropdown-option">
              <div class="option-main">
                <span class="option-name">{{ option.name }}</span>
                <Tag value="Package" severity="success" size="small" />
              </div>
              <span class="option-price">{{ formatCurrency(option.price) }}</span>
            </div>
          </template>
        </Dropdown>
      </div>
    </div>

    <div class="dependencies-section" v-if="selectedPrestation && dependencies.length > 0">
      <h4>
        <i class="pi pi-link"></i>
        Dependencies (Pre-selected - Uncheck what you don't want)
      </h4>
      <div class="dependencies-grid small-cards">
        <div
          v-for="dep in dependencies"
          :key="dep.id"
          class="dependency-card"
        >
          <Checkbox
            v-model="selectedDependencies"
            :inputId="`dep-${dep.id}`"
            :value="dep"
            class="small-checkbox"
          />
          <label :for="`dep-${dep.id}`" class="dependency-label">
            <div class="dep-info">
              <span class="dep-name">{{ dep.name }}</span>
              <Tag
                v-if="dep.need_an_appointment"
                value="Appointment Required"
                severity="danger"
                size="small"
                class=""
              />
              <Tag
                v-else
                value="No Appointment Needed"
                severity="success"
                size="small"
                class=""
              />
            </div>
            <span class="dep-price">{{ formatCurrency(dep.price) }}</span>
          </label>
        </div>
      </div>
    </div>

    <div class="dependencies-section" v-if="selectedPackage && packagePrestations.length > 0">
      <h4>
        <i class="pi pi-box"></i>
        Package Contents ({{ packagePrestations.length }} Prestations)
      </h4>
      <div class="package-price-info mb-3">
        <div class="price-comparison">
          <div class="package-deal-price">
            <strong>Package Price: {{ formatCurrency(packageTotalPrice) }}</strong>
            <Tag value="Special Deal" severity="success" size="small" class="ml-2" />
          </div>
          <div class="individual-prices-total" v-if="prestationsIndividualTotal > packageTotalPrice">
            <small class="text-muted">
              Individual Total: <span class="line-through">{{ formatCurrency(prestationsIndividualTotal) }}</span>
              <span class="text-success ml-2">
                Save {{ formatCurrency(prestationsIndividualTotal - packageTotalPrice) }}
              </span>
            </small>
          </div>
        </div>
      </div>
      <div class="dependencies-grid small-cards">
        <div
          v-for="prestation in packagePrestations"
          :key="prestation.id"
          class="dependency-card"
        >
          <label class="dependency-label">
            <div class="dep-info">
              <span class="dep-name">{{ prestation.name }}</span>
              <span class="dep-code">{{ prestation.internal_code }}</span>
              <Tag
                v-if="prestation.need_an_appointment"
                value="Appointment Required"
                severity="danger"
                size="small"
                class="mt-1"
              />
            </div>
            <span class="dep-price">{{ formatCurrency(prestation.public_price || prestation.price) }}</span>
          </label>
        </div>
      </div>
    </div>

    <div class="selected-summary" v-if="hasSelectedItems">
      <h4>
        <i class="pi pi-check-circle"></i>
        Selected Items
      </h4>
      <div class="summary-items-grid">
        <div v-for="item in allSelectedItems" :key="item.id" class="summary-card">
          <div class="summary-card-header">
            <span class="summary-item-name">{{ item.name }}</span>
            <Tag :value="item.type" :severity="getSeverity(item.type)" size="small" />
          </div>
          <div class="summary-card-body">
            <span class="summary-price">{{ formatCurrency(item.price || item.public_price) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="action-buttons" v-if="hasSelectedItems">
      <Button
        label="Create Fiche Navette"
        icon="pi pi-check"
        @click="createFicheNavette"
        :loading="false"
        class="create-btn"
      />
    </div>
  </div>
</template>

<style scoped>
.steps-row {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.step-field {
  display: flex;
  flex-direction: column;
  flex: 1 1 200px;
}

.checkbox-field {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  padding-bottom: 0.5rem;
}

.prestation-field {
  flex: 2 1 300px;
}

.full-width {
  width: 100%;
}

.package-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.dropdown-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.25rem 0.5rem;
}

.option-main {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.option-name {
  font-weight: 500;
  color: var(--text-color);
}

.option-tags {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.option-code {
  font-size: 0.8rem;
  color: var(--text-color-secondary);
}

.option-price {
  font-weight: 600;
  color: var(--primary-color);
  font-size: 1rem;
}

.dependencies-section {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background-color: var(--orange-50);
  border-left: 4px solid var(--orange-500);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-2);
}

.dependencies-section h4 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem 0;
  color: var(--orange-900);
  font-size: 1.1rem;
}

.dependencies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.dependency-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--surface-card);
  border-radius: var(--border-radius);
  border: 1px solid var(--orange-200);
  transition: box-shadow 0.2s;
}

.dependency-card:hover {
  box-shadow: var(--shadow-3);
}

.dependency-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex: 1;
  cursor: pointer;
  font-size: 0.875rem;
}

.dep-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.dep-name {
  font-weight: 500;
  color: var(--text-color);
}

.dep-code {
  font-size: 0.75rem;
  color: var(--text-color-secondary);
}

.dep-price {
  background: var(--orange-500);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: var(--border-radius);
  font-size: 0.8rem;
  font-weight: 500;
}

.package-price-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 1rem;
  padding: 1rem;
  border-radius: var(--border-radius);
  background-color: var(--green-100);
  border: 1px solid var(--green-200);
}

.price-comparison {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.package-deal-price {
  display: flex;
  align-items: center;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--green-800);
}

.individual-prices-total {
  font-size: 0.9rem;
  color: var(--text-color-secondary);
}

.line-through {
  text-decoration: line-through;
}

.text-success {
  color: var(--green-600);
  font-weight: 500;
}

/* Updated Selected Summary Section */
.selected-summary {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background-color: var(--surface-100);
  border-left: 4px solid var(--primary-color);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-2);
}

.selected-summary h4 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem 0;
  color: var(--primary-color-text);
  font-size: 1.1rem;
}

.summary-items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.summary-card {
  background: var(--surface-card);
  border: 1px solid var(--surface-border);
  border-radius: var(--border-radius);
  padding: 1rem;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-1);
}

.summary-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.summary-item-name {
  font-weight: 600;
  color: var(--text-color);
  font-size: 1rem;
}

.summary-card-body {
  display: flex;
  justify-content: flex-end;
  align-items: center;
}

.summary-price {
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--green-500);
}

.action-buttons {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-border);
}

.create-btn {
  padding: 0.75rem 2rem;
  font-size: 1rem;
  font-weight: 600;
}

@media (max-width: 900px) {
  .steps-row {
    flex-direction: column;
    gap: 1rem;
  }
  .step-field {
    min-width: 0;
    width: 100%;
  }
}
</style>