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

const emit = defineEmits(['update:hasSelectedItems', 'takeAppointment', 'cancelAppointment', 'itemsToCreate', 'appointmentRequired', 'update:prestationAppointments'])

const toast = useToast()

// Reactive data
const selectedSpecialization = ref(null)
const selectedDoctorInternal = ref(null)
const showPackages = ref(false)
const selectedPrestation = ref(null)
const selectedPackage = ref(null)
const dependencies = ref([])
const selectedDependencies = ref([])
const packagePrestations = ref([])
const isLoadingDependencies = ref(false)
const isLoadingPackage = ref(false)
const isCreatingFiche = ref(false)

// Initialize selectedDoctorInternal with props value
watch(() => props.selectedDoctor, (newVal) => {
  console.log('PrestationSelection: selectedDoctor prop changed:', newVal)
  selectedDoctorInternal.value = newVal
  console.log('PrestationSelection: selectedDoctorInternal set to:', selectedDoctorInternal.value)
}, { immediate: true })

// Also watch for changes in selectedDoctorInternal to update parent if needed
watch(selectedDoctorInternal, (newVal) => {
  console.log('PrestationSelection: selectedDoctorInternal changed to:', newVal)

  // If a doctor is selected but no specialization is set, try to set it from the doctor
  if (newVal && !selectedSpecialization.value) {
    const selectedDoctorObj = props.allDoctors.find(doctor => doctor.id === newVal)
    if (selectedDoctorObj && selectedDoctorObj.specialization_id) {
      console.log('PrestationSelection: Setting specialization from selected doctor:', selectedDoctorObj.specialization_id)
      selectedSpecialization.value = selectedDoctorObj.specialization_id
    }
  }

  // Optional: emit to parent if you want to sync back
  // emit('update:selectedDoctor', newVal)
})
const filteredDoctors = computed(() => {
  if (selectedSpecialization.value) {
    const filtered = props.allDoctors.filter(doctor => doctor.specialization_id === selectedSpecialization.value)

    // Always include the currently selected doctor if it's not already in the filtered list
    if (selectedDoctorInternal.value) {
      const selectedDoctor = props.allDoctors.find(doctor => doctor.id === selectedDoctorInternal.value)
      if (selectedDoctor && !filtered.find(doctor => doctor.id === selectedDoctor.id)) {
        filtered.unshift(selectedDoctor)
      }
    }

    return filtered
  }

  // If no specialization is selected, return all doctors
  return props.allDoctors
})

const filteredPrestations = computed(() => {
  if (!props.availablePrestations.length) return []
  // Removed specialization filtering to show all prestations regardless of selected specialization
  // This ensures all relevant items are displayed when any specialization is selected
  return props.availablePrestations
})

const hasSelectedItems = computed(() => {
  return selectedPrestation.value !== null || selectedPackage.value !== null
})

// Robust price resolver: accepts numbers, numeric strings, or objects with common price keys
const resolvePrice = (value) => {
  if (value === null || value === undefined) return 0
  if (typeof value === 'number' && isFinite(value)) return value
  if (typeof value === 'string' && value.trim() !== '' && !isNaN(Number(value))) return Number(value)
  if (typeof value === 'object') {
    if (value.price_with_vat_and_consumables_variant !== undefined) {
      const pd = value.price_with_vat_and_consumables_variant
      if (pd === null || pd === undefined) return 0
      if (typeof pd === 'number' && isFinite(pd)) return pd
      if (typeof pd === 'string' && pd.trim() !== '' && !isNaN(Number(pd))) return Number(pd)
      if (typeof pd === 'object') {
        return Number(pd.ttc_with_consumables_vat ?? pd.ttc ?? pd.public_price ?? pd.price ?? 0) || 0
      }
    }
    return Number(value.ttc_with_consumables_vat ?? value.ttc ?? value.public_price ?? value.price ?? value.final_price ?? 0) || 0
  }
  return 0
}

const packageTotalPrice = computed(() => {
  if (!selectedPackage.value) return 0
  return resolvePrice(selectedPackage.value.price ?? selectedPackage.value.public_price ?? selectedPackage.value.price_with_vat_and_consumables_variant ?? selectedPackage.value)
})

const prestationsIndividualTotal = computed(() => {
  if (packagePrestations.value && packagePrestations.value.length > 0) {
    return packagePrestations.value.reduce((total, prestation) => {
      return total + resolvePrice(prestation.public_price ?? prestation.price ?? prestation.price_with_vat_and_consumables_variant ?? prestation)
    }, 0)
  }
  return 0
})

const savingsPercent = computed(() => {
  const indiv = prestationsIndividualTotal.value
  const pkg = packageTotalPrice.value
  if (!indiv || indiv <= 0) return 0
  return Math.round(((indiv - pkg) / indiv) * 100)
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
  const num = resolvePrice(amount)
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'DZD' }).format(num || 0)
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
    isLoadingDependencies.value = true
    try {
      let dependencyIds = prestation.required_prestations_info
      if (typeof dependencyIds === 'string') {
        dependencyIds = JSON.parse(dependencyIds)
      }
      if (Array.isArray(dependencyIds) && dependencyIds.length > 0) {
        const deps = await fetchPrestationsByIds(dependencyIds)
        dependencies.value = deps
        selectedDependencies.value = [...deps]
        
        if (deps.length > 0) {
          toast.add({ 
            severity: 'success', 
            summary: 'Dependencies Loaded', 
            detail: `${deps.length} required dependencies found and pre-selected`, 
            life: 3000 
          })
        }
      }
    } catch (error) {
      console.error('Error loading dependencies:', error)
      toast.add({ 
        severity: 'error', 
        summary: 'Loading Error', 
        detail: 'Failed to load service dependencies. Please try again.', 
        life: 5000 
      })
    } finally {
      isLoadingDependencies.value = false
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
    isLoadingPackage.value = true
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
      
      if (packagePrestations.value.length > 0) {
        toast.add({ 
          severity: 'success', 
          summary: 'Package Loaded', 
          detail: `Package contains ${packagePrestations.value.length} services`, 
          life: 3000 
        })
      }
    } catch (error) {
      console.error('Error loading package prestations:', error)
      toast.add({ 
        severity: 'error', 
        summary: 'Loading Error', 
        detail: 'Failed to load package contents. Please try again.', 
        life: 5000 
      })
      // Reset package selection on error
      selectedPackage.value = null
    } finally {
      isLoadingPackage.value = false
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

const createFicheNavette = async () => {
  isCreatingFiche.value = true
  
  try {
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
    // Handle packages - when a package is selected, we only send the package data
    // NOT the individual prestations to avoid duplication
    if (selectedPackage.value) {
      // Check if any prestations in the package need appointments
      const packageNeedsAppointments = packagePrestations.value.some(p => p.need_an_appointment)
      
      if (packageNeedsAppointments) {
        // If package contains prestations needing appointments, add them to appointmentsNeeded
        packagePrestations.value.forEach(p => {
          if (p.need_an_appointment) {
            appointmentsNeeded.push(p)
          }
        })
      }
      // Note: We don't add package prestations to prestationsToCreate to avoid duplication
    }
    
    const data = {
      selectedDoctor: selectedDoctorInternal.value,
      selectedSpecialization: selectedSpecialization.value,
      prestations: prestationsToCreate,
      type: props.type,
      packages: selectedPackage.value ? [selectedPackage.value] : []
    }
    
    console.log('Fiche Navette Data:', data)

    // Show success feedback
    toast.add({ 
      severity: 'info', 
      summary: 'Processing', 
      detail: 'Creating fiche navette...', 
      life: 2000 
    })

    if (appointmentsNeeded.length > 0) {
      emit('appointmentRequired', {
        appointmentItems: appointmentsNeeded,
        otherItems: data
      })
      
      toast.add({ 
        severity: 'warn', 
        summary: 'Appointments Required', 
        detail: `${appointmentsNeeded.length} services require appointments to be scheduled`, 
        life: 4000 
      })
    } else {
      emit('itemsToCreate', data)
      
      toast.add({ 
        severity: 'success', 
        summary: 'Ready to Process', 
        detail: 'All selected services can be processed immediately', 
        life: 3000 
      })
    }
  } catch (error) {
    console.error('Error creating fiche navette:', error)
    toast.add({ 
      severity: 'error', 
      summary: 'Processing Error', 
      detail: 'Failed to process your selection. Please try again.', 
      life: 5000 
    })
  } finally {
    isCreatingFiche.value = false
  }
}

// Watchers
watch(hasSelectedItems, (newVal) => {
  emit('update:hasSelectedItems', newVal)
})

watch(selectedDoctorInternal, () => {
  resetSelections()
})
</script>

<template>
  <div class="tw-w-full tw-space-y-6">
    <!-- Enhanced Selection Form with Progress Indicator -->
    <div class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-border tw-border-gray-200 tw-p-6 tw-mb-6">
      <!-- Progress Steps -->
      <div class="tw-mb-6">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
          <div class="tw-flex tw-items-center tw-space-x-4">
            <div class="tw-flex tw-items-center tw-space-x-2">
              <div class="tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-sm tw-font-semibold"
                   :class="selectedSpecialization ? 'tw-bg-blue-500 tw-text-white' : 'tw-bg-gray-200 tw-text-gray-500'">
                1
              </div>
              <span class="tw-text-sm tw-font-medium" :class="selectedSpecialization ? 'tw-text-blue-600' : 'tw-text-gray-500'">
                Specialization
              </span>
            </div>
            <div class="tw-flex-1 tw-h-1 tw-rounded" :class="selectedDoctorInternal ? 'tw-bg-blue-500' : 'tw-bg-gray-200'"></div>
            <div class="tw-flex tw-items-center tw-space-x-2">
              <div class="tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-sm tw-font-semibold"
                   :class="selectedDoctorInternal ? 'tw-bg-blue-500 tw-text-white' : 'tw-bg-gray-200 tw-text-gray-500'">
                2
              </div>
              <span class="tw-text-sm tw-font-medium" :class="selectedDoctorInternal ? 'tw-text-blue-600' : 'tw-text-gray-500'">
                Doctor
              </span>
            </div>
            <div class="tw-flex-1 tw-h-1 tw-rounded" :class="hasSelectedItems ? 'tw-bg-blue-500' : 'tw-bg-gray-200'"></div>
            <div class="tw-flex tw-items-center tw-space-x-2">
              <div class="tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-sm tw-font-semibold"
                   :class="hasSelectedItems ? 'tw-bg-blue-500 tw-text-white' : 'tw-bg-gray-200 tw-text-gray-500'">
                3
              </div>
              <span class="tw-text-sm tw-font-medium" :class="hasSelectedItems ? 'tw-text-blue-600' : 'tw-text-gray-500'">
                Services
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Fields -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-items-end">
        <!-- Specialization Field -->
        <div class="tw-flex tw-flex-col tw-group">
          <label class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-space-x-1">
            <i class="pi pi-bookmark tw-text-blue-500"></i>
            <span>Specialization</span>
            <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="selectedSpecialization"
            :options="specializations"
            optionLabel="name"
            optionValue="id"
            placeholder="Choose specialization"
            @change="onSpecializationChange"
            class="tw-w-full tw-transition-all tw-duration-200 group-hover:tw-shadow-md"
            :loading="loading"
            :pt="{
              root: 'tw-border-2 tw-border-gray-300 focus:tw-border-blue-500 tw-rounded-lg',
              input: 'tw-px-4 tw-py-3'
            }"
          />
        </div>

        <!-- Doctor Field -->
        <div class="tw-flex tw-flex-col tw-group">
          <label class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-space-x-1">
            <i class="pi pi-user-md tw-text-green-500"></i>
            <span>Doctor</span>
            <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="selectedDoctorInternal"
            :options="filteredDoctors"
            optionLabel="name"
            optionValue="id"
            placeholder="Choose doctor"
            :disabled="!selectedSpecialization"
            class="tw-w-full tw-transition-all tw-duration-200 group-hover:tw-shadow-md"
            :loading="loading"
            :key="`doctor-${selectedDoctorInternal}`"
            :pt="{
              root: 'tw-border-2 tw-border-gray-300 focus:tw-border-blue-500 tw-rounded-lg',
              input: 'tw-px-4 tw-py-3'
            }"
          >
            <template #option="{ option }">
              <div class="tw-flex tw-items-center tw-space-x-3 tw-p-2">
                <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-gradient-to-r tw-from-green-400 tw-to-green-600 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-semibold tw-text-sm">
                  {{ option.name.charAt(0) }}
                </div>
                <div class="tw-flex tw-flex-col">
                  <span class="tw-font-medium tw-text-gray-900">{{ option.name }}</span>
                  <span class="tw-text-xs tw-text-gray-500">{{ option.specialization?.name || 'General' }}</span>
                </div>
              </div>
            </template>
          </Dropdown>
        </div>

        <!-- Enhanced Service Type Toggle -->
        <div class="tw-flex tw-flex-col tw-group">
          <label class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-space-x-1">
            <i class="pi pi-cog tw-text-purple-500"></i>
            <span>Service Type</span>
          </label>
          <div class="tw-relative tw-bg-gray-100 tw-rounded-lg tw-p-1 tw-flex tw-space-x-1 tw-transition-all tw-duration-200 group-hover:tw-shadow-md">
            <button
              @click="showPackages = false"
              :disabled="!selectedSpecialization"
              class="tw-flex-1 tw-py-2 tw-px-4 tw-rounded-md tw-text-sm tw-font-medium tw-transition-all tw-duration-200 tw-flex tw-items-center tw-justify-center tw-space-x-2"
              :class="!showPackages 
                ? 'tw-bg-white tw-text-blue-600 tw-shadow-sm' 
                : 'tw-text-gray-600 hover:tw-text-blue-600'"
            >
              <i class="pi pi-list"></i>
              <span>Individual</span>
            </button>
            <button
              @click="showPackages = true"
              :disabled="!selectedSpecialization"
              class="tw-flex-1 tw-py-2 tw-px-4 tw-rounded-md tw-text-sm tw-font-medium tw-transition-all tw-duration-200 tw-flex tw-items-center tw-justify-center tw-space-x-2"
              :class="showPackages 
                ? 'tw-bg-white tw-text-green-600 tw-shadow-sm' 
                : 'tw-text-gray-600 hover:tw-text-green-600'"
            >
              <i class="pi pi-box"></i>
              <span>Package</span>
            </button>
          </div>
        </div>

        <!-- Enhanced Prestation/Package Field -->
        <div class="tw-flex tw-flex-col tw-group">
          <label class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-space-x-1">
            <i :class="showPackages ? 'pi pi-box tw-text-green-500' : 'pi pi-list tw-text-blue-500'"></i>
            <span>{{ showPackages ? 'Package' : 'Service' }}</span>
            <span class="tw-text-red-500">*</span>
          </label>
          
          <!-- Individual Services -->
          <Dropdown
            v-if="!showPackages"
            v-model="selectedPrestation"
            :options="filteredPrestations"
            optionLabel="name"
            placeholder="Search & select service"
            :disabled="!selectedSpecialization"
            @change="onPrestationSelect"
            class="tw-w-full tw-transition-all tw-duration-200 group-hover:tw-shadow-md"
            :filter="true"
            filter-placeholder="Type to search services..."
            :filter-fields="['name', 'internal_code']"
            :pt="{
              root: 'tw-border-2 tw-border-gray-300 focus:tw-border-blue-500 tw-rounded-lg',
              input: 'tw-px-4 tw-py-3'
            }"
          >
            <template #option="{ option }">
              <div class="tw-flex tw-items-center tw-justify-between tw-w-full tw-p-3 tw-rounded-md hover:tw-bg-blue-50 tw-transition-colors">
                <div class="tw-flex tw-flex-col tw-flex-1">
                  <span class="tw-font-semibold tw-text-gray-900">{{ option.name }}</span>
                  <div class="tw-flex tw-items-center tw-space-x-2 tw-mt-1">
                    <span class="tw-text-xs tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded tw-text-gray-600">{{ option.internal_code }}</span>
                    <Tag
                      v-if="option.need_an_appointment"
                      value="📅 Appointment"
                      severity="danger"
                      size="small"
                    />
                  </div>
                </div>
                <div class="tw-text-right">
                  <span class="tw-text-lg tw-font-bold tw-text-blue-600">{{ formatCurrency(option.patient_price ?? option.price_with_vat_and_consumables_variant ?? option.price ?? option.public_price ?? option) }}</span>
                </div>
              </div>
            </template>
          </Dropdown>

          <!-- Package Services -->
          <Dropdown
            v-else
            v-model="selectedPackage"
            :options="availablePackages"
            optionLabel="name"
            placeholder="Search & select package"
            :disabled="!selectedSpecialization"
            @change="onPackageSelect"
            class="tw-w-full tw-transition-all tw-duration-200 group-hover:tw-shadow-md"
            :filter="true"
            filter-placeholder="Type to search packages..."
            :filter-fields="['name', 'internal_code']"
            :pt="{
              root: 'tw-border-2 tw-border-gray-300 focus:tw-border-green-500 tw-rounded-lg',
              input: 'tw-px-4 tw-py-3'
            }"
          >
            <template #option="{ option }">
              <div class="tw-flex tw-items-center tw-justify-between tw-w-full tw-p-3 tw-rounded-md hover:tw-bg-green-50 tw-transition-colors">
                <div class="tw-flex tw-flex-col tw-flex-1">
                  <span class="tw-font-semibold tw-text-gray-900">{{ option.name }}</span>
                  <div class="tw-mt-1">
                    <Tag value="📦 Package Deal" severity="success" size="small" />
                  </div>
                </div>
                <div class="tw-text-right">
                  <span class="tw-text-lg tw-font-bold tw-text-green-600">{{ formatCurrency(option.price ?? option.public_price ?? option.price_with_vat_and_consumables_variant ?? option) }}</span>
                </div>
              </div>
            </template>
          </Dropdown>
        </div>
      </div>
    </div>

    <!-- Enhanced Dependencies Section -->
    <transition name="tw-slide-down" appear>
      <div v-if="selectedPrestation && dependencies.length > 0" 
           class="tw-bg-gradient-to-r tw-from-orange-50 tw-to-amber-50 tw-border-l-4 tw-border-orange-500 tw-rounded-xl tw-shadow-lg tw-p-6 tw-transform tw-transition-all tw-duration-300">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
          <h4 class="tw-flex tw-items-center tw-space-x-3 tw-text-xl tw-font-bold tw-text-orange-900">
            <div class="tw-w-10 tw-h-10 tw-bg-orange-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-link tw-text-white tw-text-sm"></i>
            </div>
            <div>
              <span>Required Dependencies</span>
              <p class="tw-text-sm tw-font-normal tw-text-orange-700 tw-mt-1">These services are automatically selected. Uncheck items you don't need.</p>
            </div>
          </h4>
          <div class="tw-bg-white tw-px-4 tw-py-2 tw-rounded-full tw-shadow-md">
            <span class="tw-text-sm tw-font-semibold tw-text-orange-600">
              {{ selectedDependencies.length }} of {{ dependencies.length }} selected
            </span>
          </div>
        </div>
        
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
          <div
            v-for="(dep, index) in dependencies"
            :key="dep.id"
            class="tw-group tw-bg-white tw-rounded-xl tw-border-2 tw-border-orange-200 tw-shadow-sm hover:tw-shadow-lg hover:tw-border-orange-400 tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105"
            :style="{ 'animation-delay': `${index * 100}ms` }"
          >
            <div class="tw-p-4">
              <div class="tw-flex tw-items-start tw-space-x-3">
                <Checkbox
                  v-model="selectedDependencies"
                  :inputId="`dep-${dep.id}`"
                  :value="dep"
                  class="tw-mt-1"
                />
                <label :for="`dep-${dep.id}`" class="tw-flex-1 tw-cursor-pointer">
                  <div class="tw-flex tw-flex-col tw-space-y-2">
                    <h5 class="tw-font-semibold tw-text-gray-900 tw-group-hover:tw-text-orange-700 tw-transition-colors">{{ dep.name }}</h5>
                    
                    <div class="tw-flex tw-flex-wrap tw-gap-2">
                      <Tag
                        v-if="dep.need_an_appointment"
                        value="📅 Appointment"
                        severity="danger"
                        size="small"
                        class="tw-animate-pulse"
                      />
                      <Tag
                        v-else
                        value="⚡ Instant"
                        severity="success"
                        size="small"
                      />
                      <Tag
                        value="Required"
                        severity="warning"
                        size="small"
                      />
                    </div>
                    
                    <div class="tw-flex tw-items-center tw-justify-between tw-pt-2 tw-border-t tw-border-orange-100">
                      <span class="tw-text-xs tw-text-gray-500">Code: {{ dep.internal_code }}</span>
                      <span class="tw-text-lg tw-font-bold tw-text-orange-600 tw-bg-orange-100 tw-px-3 tw-py-1 tw-rounded-full">
                        {{ formatCurrency(dep.price) }}
                      </span>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Enhanced Package Contents Section -->
    <transition name="tw-slide-down" appear>
      <div v-if="selectedPackage && packagePrestations.length > 0" 
           class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-border-l-4 tw-border-green-500 tw-rounded-xl tw-shadow-lg tw-p-6 tw-transform tw-transition-all tw-duration-300">
        
        <!-- Package Header -->
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
          <h4 class="tw-flex tw-items-center tw-space-x-3 tw-text-xl tw-font-bold tw-text-green-900">
            <div class="tw-w-10 tw-h-10 tw-bg-green-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-white tw-text-sm"></i>
            </div>
            <div>
              <span>Package Contents</span>
              <p class="tw-text-sm tw-font-normal tw-text-green-700 tw-mt-1">{{ packagePrestations.length }} services included in this package</p>
            </div>
          </h4>
        </div>

        <!-- Enhanced Package Price Info -->
        <div class="tw-bg-white tw-rounded-xl tw-shadow-md tw-p-6 tw-mb-6 tw-border tw-border-green-200">
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-start md:tw-items-center tw-justify-between tw-space-y-4 md:tw-space-y-0">
            <div class="tw-flex tw-flex-col tw-space-y-2">
              <div class="tw-flex tw-items-center tw-space-x-3">
                <span class="tw-text-3xl tw-font-bold tw-text-green-600">{{ formatCurrency(packageTotalPrice) }}</span>
                <Tag value="💰 Package Deal" severity="success" />
              </div>
                <div v-if="prestationsIndividualTotal > packageTotalPrice" class="tw-flex tw-items-center tw-space-x-2">
                <span class="tw-text-gray-600">Individual total:</span>
                <span class="tw-text-lg tw-line-through tw-text-gray-500">{{ formatCurrency(prestationsIndividualTotal) }}</span>
                  <div class="tw-bg-green-100 tw-px-3 tw-py-1 tw-rounded-full tw-flex tw-items-center tw-space-x-1">
                  <i class="pi pi-check-circle tw-text-green-600 tw-text-sm"></i>
                  <span class="tw-text-green-700 tw-font-semibold tw-text-sm">
                    You save {{ formatCurrency(prestationsIndividualTotal - packageTotalPrice) }}
                  </span>
                </div>
              </div>
            </div>
            
            <div class="tw-flex tw-flex-col tw-items-end tw-space-y-2">
              <div class="tw-text-right">
                <div class="tw-text-sm tw-text-gray-600">Savings</div>
                <div class="tw-text-xl tw-font-bold tw-text-green-600">
                  {{ savingsPercent }}% OFF
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Package Prestations Grid -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
          <div
            v-for="(prestation, index) in packagePrestations"
            :key="prestation.id"
            class="tw-group tw-bg-white tw-rounded-xl tw-border-2 tw-border-green-200 tw-shadow-sm hover:tw-shadow-lg hover:tw-border-green-400 tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105"
            :style="{ 'animation-delay': `${index * 100}ms` }"
          >
            <div class="tw-p-4">
              <div class="tw-flex tw-flex-col tw-space-y-3">
                <div class="tw-flex tw-items-start tw-justify-between">
                  <h5 class="tw-font-semibold tw-text-gray-900 tw-group-hover:tw-text-green-700 tw-transition-colors tw-flex-1 tw-pr-2">
                    {{ prestation.name }}
                  </h5>
                  <div class="tw-text-right">
                    <div class="tw-text-lg tw-font-bold tw-text-green-600">{{ formatCurrency(prestation.public_price || prestation.price) }}</div>
                    <div class="tw-text-xs tw-text-gray-500">Individual price</div>
                  </div>
                </div>
                
                <div class="tw-flex tw-flex-wrap tw-gap-2">
                  <Tag
                    v-if="prestation.need_an_appointment"
                    value="📅 Appointment"
                    severity="danger"
                    size="small"
                    class="tw-animate-pulse"
                  />
                  <Tag
                    v-else
                    value="⚡ Instant"
                    severity="success"
                    size="small"
                  />
                  <Tag
                    value="Included"
                    severity="info"
                    size="small"
                  />
                </div>
                
                <div class="tw-pt-2 tw-border-t tw-border-green-100">
                  <span class="tw-text-xs tw-text-gray-500 tw-bg-green-50 tw-px-2 tw-py-1 tw-rounded">
                    Code: {{ prestation.internal_code }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Enhanced Selected Items Summary -->
    <transition name="tw-slide-up" appear>
      <div v-if="hasSelectedItems" 
           class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border-l-4 tw-border-blue-500 tw-rounded-xl tw-shadow-lg tw-p-6">
        
        <!-- Summary Header -->
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
          <h4 class="tw-flex tw-items-center tw-space-x-3 tw-text-xl tw-font-bold tw-text-blue-900">
            <div class="tw-w-10 tw-h-10 tw-bg-blue-500 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-animate-pulse">
              <i class="pi pi-check-circle tw-text-white tw-text-sm"></i>
            </div>
            <div>
              <span>Selection Summary</span>
              <p class="tw-text-sm tw-font-normal tw-text-blue-700 tw-mt-1">Review your selected services before proceeding</p>
            </div>
          </h4>
          
          <div class="tw-bg-white tw-rounded-xl tw-shadow-md tw-p-4 tw-min-w-[200px]">
            <div class="tw-text-center">
              <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ allSelectedItems.length }}</div>
              <div class="tw-text-sm tw-text-gray-600">{{ allSelectedItems.length === 1 ? 'Service' : 'Services' }} Selected</div>
            </div>
          </div>
        </div>

        <!-- Selected Items Grid -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4 tw-mb-6">
          <div
            v-for="(item, index) in allSelectedItems"
            :key="item.id"
            class="tw-group tw-bg-white tw-rounded-xl tw-border-2 tw-border-blue-200 tw-shadow-sm hover:tw-shadow-lg hover:tw-border-blue-400 tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105"
            :style="{ 'animation-delay': `${index * 100}ms` }"
          >
            <div class="tw-p-4">
              <div class="tw-flex tw-flex-col tw-space-y-3">
                <div class="tw-flex tw-items-start tw-justify-between">
                  <h5 class="tw-font-semibold tw-text-gray-900 tw-group-hover:tw-text-blue-700 tw-transition-colors tw-flex-1 tw-pr-2">
                    {{ item.name }}
                  </h5>
                  <Tag 
                    :value="item.type === 'prestation' ? '🔹 Service' : item.type === 'package' ? '📦 Package' : '🔗 Dependency'" 
                    :severity="getSeverity(item.type)" 
                    size="small" 
                  />
                </div>
                
                <div class="tw-flex tw-flex-wrap tw-gap-2">
                  <Tag
                    v-if="item.need_an_appointment"
                    value="📅 Needs Appointment"
                    severity="danger"
                    size="small"
                    class="tw-animate-pulse"
                  />
                  <Tag
                    v-else
                    value="⚡ Ready Now"
                    severity="success"
                    size="small"
                  />
                </div>
                
                <div class="tw-flex tw-items-center tw-justify-between tw-pt-2 tw-border-t tw-border-blue-100">
                  <span class="tw-text-xs tw-text-gray-500 tw-bg-blue-50 tw-px-2 tw-py-1 tw-rounded">
                    {{ item.internal_code || 'N/A' }}
                  </span>
                  <span class="tw-text-xl tw-font-bold tw-text-blue-600 tw-bg-blue-100 tw-px-3 tw-py-1 tw-rounded-full">
                    {{ formatCurrency(item.price || item.public_price) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Action Section -->
        <div class="tw-bg-white tw-rounded-xl tw-shadow-md tw-p-6 tw-border tw-border-blue-200">
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-justify-between tw-space-y-4 sm:tw-space-y-0 tw-space-x-0 sm:tw-space-x-6">
            <!-- Quick Stats -->
            <div class="tw-flex tw-space-x-6">
              <div class="tw-text-center">
                <div class="tw-text-lg tw-font-bold tw-text-gray-700">{{ allSelectedItems.filter(item => item.need_an_appointment).length }}</div>
                <div class="tw-text-xs tw-text-gray-500">Need Appointment</div>
              </div>
              <div class="tw-text-center">
                <div class="tw-text-lg tw-font-bold tw-text-gray-700">{{ otherItemsCount }}</div>
                <div class="tw-text-xs tw-text-gray-500">Ready Now</div>
              </div>
              <div class="tw-text-center">
                <div class="tw-text-lg tw-font-bold tw-text-blue-600">
                  {{ formatCurrency(allSelectedItems.reduce((total, item) => total + (item.price || item.public_price || 0), 0)) }}
                </div>
                <div class="tw-text-xs tw-text-gray-500">Total Estimate</div>
              </div>
            </div>

            <!-- Action Button -->
            <Button
              :label="isCreatingFiche ? 'Processing...' : 'Create Fiche Navette'"
              :icon="isCreatingFiche ? 'pi pi-spinner pi-spin' : 'pi pi-arrow-right'"
              @click="createFicheNavette"
              :loading="isCreatingFiche"
              :disabled="isCreatingFiche || isLoadingDependencies || isLoadingPackage"
              class="tw-px-8 tw-py-4 tw-text-lg tw-font-semibold tw-rounded-xl tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 hover:tw-from-blue-700 hover:tw-to-indigo-700 tw-text-white tw-border-0 tw-shadow-lg hover:tw-shadow-xl tw-transform tw-transition-all tw-duration-200 hover:tw-scale-105 disabled:tw-opacity-60 disabled:tw-cursor-not-allowed disabled:tw-transform-none"
              size="large"
            />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* Enhanced PrimeVue component customization with animations */
:deep(.p-dropdown) {
  width: 100%;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-dropdown:hover) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

:deep(.p-dropdown-panel) {
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
  backdrop-filter: blur(8px);
}

:deep(.p-dropdown-item) {
  transition: all 0.2s ease;
  border-radius: 8px;
  margin: 4px 8px;
}

:deep(.p-dropdown-item:hover) {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  transform: translateX(4px);
}

:deep(.p-checkbox) {
  margin-right: 0.5rem;
  transition: all 0.2s ease;
}

:deep(.p-checkbox:hover .p-checkbox-box) {
  transform: scale(1.1);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-tag) {
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}

:deep(.p-tag:hover) {
  transform: scale(1.05);
}

:deep(.p-button) {
  border-radius: 12px;
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

:deep(.p-button:hover) {
  transform: translateY(-2px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.p-button:active) {
  transform: translateY(0px);
}

:deep(.p-button::before) {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
  transition: left 0.5s;
}

:deep(.p-button:hover::before) {
  left: 100%;
}

/* Enhanced Animations */
.tw-slide-down-enter-active,
.tw-slide-down-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.tw-slide-down-enter-from {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.tw-slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.tw-slide-up-enter-active,
.tw-slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.tw-slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.tw-slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

/* Card Animations */
@keyframes tw-fade-in-up {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tw-group {
  animation: tw-fade-in-up 0.6s ease-out backwards;
}

/* Pulse animation for important elements */
@keyframes tw-pulse-glow {
  0%, 100% {
    box-shadow: 0 0 5px rgba(59, 130, 246, 0.3);
  }
  50% {
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.6), 0 0 30px rgba(59, 130, 246, 0.4);
  }
}

.tw-animate-pulse {
  animation: tw-pulse-glow 2s ease-in-out infinite;
}

/* Enhanced scrollbar styling */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #cbd5e1 0%, #94a3b8 100%);
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #94a3b8 0%, #64748b 100%);
}

::-webkit-scrollbar-corner {
  background: #f1f5f9;
}

/* Loading states */
.tw-loading-shimmer {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e0e0e0 50%,
    #f0f0f0 75%
  );
  background-size: 200% 100%;
  animation: tw-shimmer 1.5s infinite;
}

@keyframes tw-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Focus states for accessibility */
:deep(.p-dropdown:focus),
:deep(.p-checkbox:focus),
:deep(.p-button:focus) {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Responsive enhancements */
@media (max-width: 768px) {
  .tw-group {
    transform: none !important;
  }
  
  .group:tw-hover {
    transform: none !important;
  }
}

/* Dark mode support (if enabled) */
@media (prefers-color-scheme: dark) {
  :deep(.p-dropdown-panel) {
    background: rgba(31, 41, 55, 0.95);
    border-color: #4b5563;
  }
  
  ::-webkit-scrollbar-track {
    background: linear-gradient(180deg, #374151 0%, #1f2937 100%);
  }
  
  ::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #6b7280 0%, #4b5563 100%);
    border-color: #374151;
  }
}
</style>