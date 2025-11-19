<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import Card from 'primevue/card'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'
import Skeleton from 'primevue/skeleton'
import Tooltip from 'primevue/tooltip'
import Divider from 'primevue/divider'

// ADD APPOINTMENT MODAL IMPORTS
import AppointmentRequiredAlert from '../FicheNavatteItem/AppointmentRequiredAlert.vue'
import SameDayAppointmentModal from '../FicheNavatteItem/SameDayAppointmentModal.vue'
import DoctorSelectionModal from '../FicheNavatteItem/DoctorSelectionModal.vue'

import { ficheNavetteService } from '../../../../Components/Apps/services/Emergency/ficheNavetteService.js'

const props = defineProps({
  specializations: Array,
  allPrestations: Array,
  allDoctors: Array,
  loading: Boolean,
  prestationAppointments: Object,
  appointmentLoading: [String, Number],
  // ADD PATIENT AND FICHE NAVETTE PROPS FOR APPOINTMENTS
  patientId: {
    type: Number,
    required: true
  },
  ficheNavetteId: {
    type: Number,
    required: false
  }
})

const emit = defineEmits(['update:hasSelectedItems', 'takeAppointment', 'cancelAppointment', 'itemsToCreate', 'appointmentRequired', 'update:prestationAppointments'])

const toast = useToast()

// Reactive data
const searchTerm = ref('')
const selectedSpecializationsFilter = ref([])
const customSelectedPrestations = ref([])
const doctorsBySpecialization = ref([])
const selectedCustomNameOption = ref(null)
const customPrestationName = ref('')

// ADD APPOINTMENT MODAL REACTIVE DATA
const showAppointmentRequiredAlert = ref(false)
const showSameDayAppointmentModal = ref(false)
const showDoctorSelectionModal = ref(false)
const appointmentRequiredPrestations = ref([])
const sameDayAppointmentData = ref({})
const selectedPrestationForAppointment = ref(null)
const selectedDoctor = ref(null)
const selectedDoctorSpecializationId = ref(null)

// Custom name options
const customNameOptions = ref([])

// Loading state for custom packages
const customPackagesLoading = ref(false)

// Computed properties
const filteredCustomPrestations = computed(() => {
  if (!props.allPrestations || props.allPrestations.length === 0) return []

  let filtered = [...props.allPrestations]

  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(p =>
      (p.name && p.name.toLowerCase().includes(search)) ||
      (p.internal_code && p.internal_code.toLowerCase().includes(search))
    )
  }

  // Removed specialization filtering to show all prestations regardless of selected specialization
  // This ensures all relevant items are displayed when any specialization is selected

  return filtered
})

const shouldShowCustomNameInput = computed(() => {
  if (customSelectedPrestations.value.length < 2) {
    return false;
  }
  const specializationsInSelection = new Set(customSelectedPrestations.value.map(p => p.specialization_id));
  return specializationsInSelection.size > 1;
})

const nameToUse = computed(() => {
  if (selectedCustomNameOption.value === 'other') {
    return customPrestationName.value.trim() ? customPrestationName.value.trim() : null
  }
  return selectedCustomNameOption.value
})

const hasSelectedItems = computed(() => customSelectedPrestations.value.length > 0)

const customPrestationsNeedingAppointments = computed(() => {
  return customSelectedPrestations.value.filter(p => p.need_an_appointment === true)
})

const customPrestationsNotNeedingAppointments = computed(() => {
  return customSelectedPrestations.value.filter(p => p.need_an_appointment !== true)
})

// Robust price resolver used by custom and standard selection components
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

const customTotalCost = computed(() => {
  return customSelectedPrestations.value.reduce((total, prestation) => {
    return total + resolvePrice(prestation.public_price ?? prestation.price ?? prestation.price_with_vat_and_consumables_variant ?? prestation)
  }, 0)
})

const hasActiveAppointment = computed(() => {
  if (!props.prestationAppointments) return false
  
  const now = new Date()
  return Object.values(props.prestationAppointments).some(appointment => {
    if (appointment.status === 'canceled') return false
    const appointmentDate = new Date(appointment.datetime || appointment.date)
    return appointmentDate > now
  })
})

// Methods
const onSpecializationFilterChange = async () => {
  doctorsBySpecialization.value = []
  if (selectedSpecializationsFilter.value.length > 0) {
    try {
      const doctorPromises = selectedSpecializationsFilter.value.map(specId =>
        ficheNavetteService.getDoctorsBySpecialization(specId)
      )
      const results = await Promise.all(doctorPromises)
      const allDoctors = results.flatMap(result => result.success ? result.data : [])
      const uniqueDoctors = allDoctors.filter((doctor, index, self) =>
        index === self.findIndex(d => d.id === doctor.id)
      )
      doctorsBySpecialization.value = uniqueDoctors
    } catch (error) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load doctors', life: 3000 })
    }
  }
}

const fetchCustomPackages = async () => {
  customPackagesLoading.value = true
  try {
    const result = await ficheNavetteService.getCustomPackages()
    if (result.success) {
      // Transform API data to match dropdown format
      const packages = result.data.map(pkg => ({
        name: pkg.name,
        icon: 'pi-tag', // Default icon for packages
        description: pkg.description
      }))

      // Add "Other" option
      packages.push({
        label: 'Other',
        value: 'other',
        icon: 'pi-ellipsis-h'
      })

      customNameOptions.value = packages
    } else {
      // Fallback to default options if API fails
      customNameOptions.value = [
        { label: 'Laboratory', value: 'Laboratory', icon: 'pi-flask' },
        { label: 'Consultation', value: 'Consultation', icon: 'pi-user-edit' },
        { label: 'Radiology', value: 'Radiology', icon: 'pi-image' },
        { label: 'Cardiology', value: 'Cardiology', icon: 'pi-heart' },
        { label: 'Neurology', value: 'Neurology', icon: 'pi-sitemap' },
        { label: 'Dermatology', value: 'Dermatology', icon: 'pi-sun' },
        { label: 'Pharmacy', value: 'Pharmacy', icon: 'pi-shopping-bag' },
        { label: 'Emergency', value: 'Emergency', icon: 'pi-exclamation-triangle' },
        { label: 'Surgery', value: 'Surgery', icon: 'pi-cog' },
        { label: 'Physiotherapy', value: 'Physiotherapy', icon: 'pi-replay' },
        { label: 'Other', value: 'other', icon: 'pi-ellipsis-h' }
      ]
      toast.add({ severity: 'warn', summary: 'Warning', detail: 'Failed to load custom packages, using defaults', life: 3000 })
    }
  } catch (error) {
    console.error('Error fetching custom packages:', error)
    // Fallback to default options
    customNameOptions.value = [
      { label: 'Laboratory', value: 'Laboratory', icon: 'pi-flask' },
      { label: 'Consultation', value: 'Consultation', icon: 'pi-user-edit' },
      { label: 'Radiology', value: 'Radiology', icon: 'pi-image' },
      { label: 'Cardiology', value: 'Cardiology', icon: 'pi-heart' },
      { label: 'Neurology', value: 'Neurology', icon: 'pi-sitemap' },
      { label: 'Dermatology', value: 'Dermatology', icon: 'pi-sun' },
      { label: 'Pharmacy', value: 'Pharmacy', icon: 'pi-shopping-bag' },
      { label: 'Emergency', value: 'Emergency', icon: 'pi-exclamation-triangle' },
      { label: 'Surgery', value: 'Surgery', icon: 'pi-cog' },
      { label: 'Physiotherapy', value: 'Physiotherapy', icon: 'pi-replay' },
      { label: 'Other', value: 'other', icon: 'pi-ellipsis-h' }
    ]
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load custom packages', life: 3000 })
  } finally {
    customPackagesLoading.value = false
  }
}

const formatAppointmentDateTime = (dateTime) => {
  if (!dateTime) {
    return 'No date available'
  }

  const date = new Date(dateTime)

  if (isNaN(date.getTime())) {
    console.warn('Invalid date value:', dateTime)
    return 'Invalid date'
  }

  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  }).format(date)
}

const formatCurrency = (amount) => {
  const num = resolvePrice(amount)
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num || 0)
}

const getSpecializationName = (specializationId) => {
  const spec = props.specializations.find(s => s.id === specializationId)
  return spec ? spec.name : 'Unknown'
}

const getDoctorsForPrestation = (prestation) => {
  if (!props.allDoctors || props.allDoctors.length === 0) return []
  return props.allDoctors.filter(d => d.specialization_id === prestation.specialization_id)
}

const takeAppointment = (prestation) => {
  console.log('=== CustomPrestationSelection takeAppointment called ===')
  console.log('Prestation:', prestation)
  console.log('Patient ID:', props.patientId)
  console.log('Fiche Navette ID:', props.ficheNavetteId)

  // Check if there are any active appointments
  if (hasActiveAppointment.value) {
    toast.add({
      severity: 'warn',
      summary: 'Active Appointment',
      detail: 'You cannot take a new appointment while you have an active appointment scheduled.',
      life: 3000
    })
    return
  }

  // Check if appointment already exists
  if (props.prestationAppointments && props.prestationAppointments[prestation.id]) {
    toast.add({
      severity: 'info',
      summary: 'Appointment Exists',
      detail: 'This prestation already has an appointment scheduled.',
      life: 3000
    })
    return
  }

  // Set the current prestation for appointment
  selectedPrestationForAppointment.value = prestation

  // ALWAYS open doctor selection modal first (as requested)
  console.log('Opening doctor selection modal for prestation:', prestation.name)
  showDoctorSelectionModal.value = true
}

const cancelAppointment = (prestation) => {
  emit('cancelAppointment', prestation)
}

const createFicheNavette = () => {
  const data = {
    type: 'custom',
    customPrestations: customSelectedPrestations.value.map(p => ({
      ...p,
      display_name: nameToUse.value || p.name,
      type: nameToUse.value ? 'custom' : 'predefined',
      selected_doctor_id: p.selected_doctor_id
    }))
  }

  const appointmentsNeeded = customPrestationsNeedingAppointments.value
  const otherItems = customPrestationsNotNeedingAppointments.value

  if (appointmentsNeeded.length > 0) {
    emit('appointmentRequired', {
      appointmentItems: appointmentsNeeded,
      otherItems: otherItems
    })
  } else {
    emit('itemsToCreate', data)
  }
}

const removeFromCustomSelected = (prestation) => {
  customSelectedPrestations.value = customSelectedPrestations.value.filter(p => p.id !== prestation.id)
}

// ADD APPOINTMENT HANDLING METHODS
const onDoctorSelectedForAppointment = (data) => {
  console.log('=== onDoctorSelectedForAppointment called ===')
  const doctorId = typeof data === 'object' ? data.doctorId : data
  console.log('Selected doctor ID:', doctorId)

  selectedDoctor.value = doctorId
  const doctor = props.allDoctors.find(d => d.id === doctorId)
  selectedDoctorSpecializationId.value = doctor?.specialization_id || null

  console.log('Doctor selected:', doctor)
  console.log('Current prestation for appointment:', selectedPrestationForAppointment.value)

  // If we have a prestation from the data object, use it
  if (typeof data === 'object' && data.prestation) {
    selectedPrestationForAppointment.value = data.prestation
    console.log('Set prestation from data:', data.prestation)
  }

  showDoctorSelectionModal.value = false
  showSameDayAppointmentModal.value = true

  console.log('Doctor selection - Opening SameDayAppointmentModal')
  console.log('Final state - Doctor:', selectedDoctor.value, 'Prestation:', selectedPrestationForAppointment.value?.id)
}

const onSameDayAppointmentBooked = (appointment) => {
  console.log('=== onSameDayAppointmentBooked called ===')
  console.log('Appointment data:', appointment)

  if (selectedPrestationForAppointment.value) {
    // Update the prestation appointments (this would normally be handled by parent)
    const updatedAppointments = { ...props.prestationAppointments }
    updatedAppointments[selectedPrestationForAppointment.value.id] = {
      id: appointment.id,
      datetime: appointment.appointment_datetime,
      doctor_name: appointment.doctor_name,
      status: 'confirmed'
    }

    // Emit to parent to update the appointments
    emit('update:prestationAppointments', updatedAppointments)
  }

  toast.add({ severity: 'success', summary: 'Appointment Booked!', life: 5000 })

  showSameDayAppointmentModal.value = false
  showDoctorSelectionModal.value = false
  selectedPrestationForAppointment.value = null

  toast.add({
    severity: 'info',
    summary: 'Appointment Booked',
    detail: 'You can now proceed to create the fiche navette with all selected items.',
    life: 5000
  })
}

const onDoctorSelectionCancelled = () => {
  showDoctorSelectionModal.value = false
  selectedPrestationForAppointment.value = null
}

// Watchers
watch(hasSelectedItems, (newVal) => {
  emit('update:hasSelectedItems', newVal)
})

watch(selectedCustomNameOption, (val) => {
  if (val !== 'other') {
    customPrestationName.value = ''
  }
})

// Lifecycle
onMounted(() => {
  fetchCustomPackages()
})
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-100 tw-p-4 lg:tw-p-8">
    <div class="tw-max-w-7xl tw-mx-auto">
      
    


      <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-8">
        
        <!-- Left Column - Main Content -->
        <div class="lg:tw-col-span-2 tw-space-y-6">
          
          <!-- Search and Filters Card -->
          <Card class="tw-shadow-sm tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-search tw-text-blue-600"></i>
                </div>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Search & Filter</h3>
              </div>
              <div class="tw-space-y-4">
                
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                  <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search Prestations</label>
                    <span class="p-input-icon-left tw-w-full">
                      <InputText
                        v-model="searchTerm"
                        placeholder="Search by name or code..."
                        class="tw-w-full tw-h-12 tw-rounded-lg"
                      />
                    </span>
                  </div>
                  <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Filter by Specialization</label>
                    <MultiSelect
                      v-model="selectedSpecializationsFilter"
                      :options="specializations"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select specializations"
                      @change="onSpecializationFilterChange"
                      class="tw-w-full tw-h-12"
                      :loading="loading"
                    />
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Custom Grouping Name Card -->
          <Card v-if="shouldShowCustomNameInput" class="tw-shadow-sm tw-border-0 tw-bg-gradient-to-r tw-from-orange-50 tw-to-amber-50">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
                <div class="tw-w-10 tw-h-10 tw-bg-orange-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-tag tw-text-orange-600"></i>
                </div>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Custom Group Name</h3>
              </div>
              
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Choose a category</label>
                  <Dropdown
                    v-model="selectedCustomNameOption"
                    :options="customNameOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select category"
                    class="tw-w-full tw-h-12"
                  >
                    <template #value="slotProps">
                      <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                        <i :class="['pi', customNameOptions.find(opt => opt.value === slotProps.value)?.icon]"></i>
                        <span>{{ customNameOptions.find(opt => opt.value === slotProps.value)?.label }}</span>
                      </div>
                      <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i :class="['pi', slotProps.option.icon]"></i>
                        <span>{{ slotProps.option.label }}</span>
                      </div>
                    </template>
                  </Dropdown>
                </div>
                <div v-if="selectedCustomNameOption === 'other'">
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Custom name</label>
                  <InputText
                    v-model="customPrestationName"
                    placeholder="Enter custom name..."
                    class="tw-w-full tw-h-12 tw-rounded-lg"
                  />
                </div>
              </div>
            </template>
          </Card>

          <!-- Prestations Table -->
          <Card class="tw-shadow-sm tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
            <template #content>
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-bg-green-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-list tw-text-green-600"></i>
                  </div>
                  <div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Available Prestations</h3>
                    <p class="tw-text-sm tw-text-gray-500">{{ filteredCustomPrestations.length }} prestations found</p>
                  </div>
                </div>
                <Button
                  v-if="customSelectedPrestations.length > 0"
                  icon="pi pi-times"
                  label="Clear Selection"
                  severity="secondary"
                  text
                  @click="customSelectedPrestations = []"
                  class="tw-rounded-lg"
                />
              </div>

              <DataTable
                :value="filteredCustomPrestations"
                v-model:selection="customSelectedPrestations"
                dataKey="id"
                :loading="loading"
                :paginator="true"
                :rows="8"
                :rowsPerPageOptions="[5, 8, 15, 25]"
                responsiveLayout="scroll"
                class="tw-rounded-lg tw-overflow-hidden"
                stripedRows
              >
                <template #empty>
                  <div class="tw-text-center tw-py-12">
                    <i class="pi pi-search tw-text-4xl tw-text-gray-300 tw-mb-4"></i>
                    <p class="tw-text-gray-500 tw-text-lg">No prestations found</p>
                    <p class="tw-text-gray-400 tw-text-sm">Try adjusting your search or filters</p>
                  </div>
                </template>

                <Column selectionMode="multiple" headerStyle="width: 3rem">
                  <template #body="{ data }">
                    <div class="tw-flex tw-justify-center">
                      <Checkbox 
                        v-if="!data.need_an_appointment" 
                        v-model="customSelectedPrestations" 
                        :value="data" 
                        :binary="false"
                      />
                      <i 
                        v-else 
                        class="pi pi-calendar-times tw-text-xl tw-text-amber-500" 
                        v-tooltip.top="'Appointment required'"
                      ></i>
                    </div>
                  </template>
                </Column>

                <Column field="name" header="Prestation" sortable style="min-width: 200px;">
                  <template #body="{ data }">
                    <div>
                      <div class="tw-font-medium tw-text-gray-900">{{ data.name }}</div>
                      <div class="tw-text-sm tw-text-gray-500">{{ data.internal_code }}</div>
                    </div>
                  </template>
                </Column>

                <Column field="specialization.name" header="Specialization" sortable style="min-width: 100px;">
                  <template #body="{ data }">
                    <Tag 
                      v-if="data.specialization" 
                      :value="data.specialization.name" 
                      severity="info" 
                      class="tw-rounded-full"
                    />
                    <span v-else class="tw-text-gray-400">-</span>
                  </template>
                </Column>

                <Column field="public_price" header="Price" sortable style="min-width: 100px;">
                  <template #body="{ data }">
                    <div class="tw-font-semibold tw-text-emerald-600">
                      {{ formatCurrency(data.public_price || data.price) }}
                    </div>
                  </template>
                </Column>

                <Column header="Action" style="min-width: 50px;">
                  <template #body="{ data }">
                    <div v-if="data.need_an_appointment" class="tw-space-y-2">
                      <div v-if="!prestationAppointments[data.id]">
                        <Button
                          icon="pi pi-calendar-plus"
                          label="Book"
                          severity="warning"
                          size="small"
                          @click="takeAppointment(data)"
                          :loading="appointmentLoading === data.id"
                          :disabled="hasActiveAppointment"
                          class="tw-w-full tw-rounded-lg"
                          v-tooltip.top="hasActiveAppointment ? 'Cannot book while another appointment is active' : ''"
                        />
                      </div>
                      <div v-else class="tw-bg-emerald-50 tw-border tw-border-emerald-200 tw-rounded-lg tw-p-3">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                          <Tag value="Booked" severity="success" icon="pi pi-calendar-check" class="tw-rounded-full"/>
                          <Button
                            icon="pi pi-times"
                            severity="danger"
                            size="small"
                            text
                            @click="cancelAppointment(data)"
                            v-tooltip.top="'Cancel appointment'"
                          />
                        </div>
                        <div class="tw-text-xs tw-text-gray-600">
                          <div>{{ formatAppointmentDateTime(prestationAppointments[data.id].datetime) }}</div>
                          <div v-if="prestationAppointments[data.id].doctor_name" class="tw-font-medium">
                            Dr. {{ prestationAppointments[data.id].doctor_name }}
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-else>
                      <Dropdown
                        v-model="data.selected_doctor_id"
                        :options="getDoctorsForPrestation(data)"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select doctor"
                        class="tw-w-full"
                        :disabled="!customSelectedPrestations.some(p => p.id === data.id)"
                        size="small"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </div>

        <!-- Right Sidebar -->
        <div class="lg:tw-col-span-1 tw-space-y-6">
          
          <!-- Selected Prestations -->
          <Card v-if="hasSelectedItems" class="tw-shadow-sm tw-border-0 tw-bg-white tw-sticky tw-top-4">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-shopping-cart tw-text-blue-600"></i>
                </div>
                <div>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Selected Items</h3>
                  <p class="tw-text-sm tw-text-gray-500">{{ customSelectedPrestations.length }} prestations</p>
                </div>
              </div>

              <!-- Summary Stats -->
              <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-4 tw-mb-6">
                <div class="tw-grid tw-grid-cols-2 tw-gap-4">
                  <div class="tw-text-center">
                    <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ customSelectedPrestations.length }}</div>
                    <div class="tw-text-xs tw-text-gray-600 tw-uppercase tw-tracking-wide">Total Items</div>
                  </div>
                  <div class="tw-text-center">
                    <div class="tw-text-2xl tw-font-bold tw-text-emerald-600">{{ formatCurrency(customTotalCost) }}</div>
                    <div class="tw-text-xs tw-text-gray-600 tw-uppercase tw-tracking-wide">Total Amount</div>
                  </div>
                </div>
                <div v-if="customPrestationsNeedingAppointments.length > 0" class="tw-mt-3 tw-pt-3 tw-border-t tw-border-blue-200">
                  <div class="tw-text-center">
                    <div class="tw-text-lg tw-font-semibold tw-text-amber-600">{{ customPrestationsNeedingAppointments.length }}</div>
                    <div class="tw-text-xs tw-text-gray-600 tw-uppercase tw-tracking-wide">Need Appointments</div>
                  </div>
                </div>
              </div>

              <!-- Selected Items List -->
              <div class="tw-space-y-3 tw-max-h-96 tw-overflow-y-auto">
                <div
                  v-for="prestation in customSelectedPrestations"
                  :key="prestation.id"
                  class="tw-group tw-bg-gray-50 tw-rounded-lg tw-p-3 tw-border tw-border-gray-200 hover:tw-bg-gray-100 tw-transition-colors"
                  :class="{ 'tw-border-l-4 tw-border-amber-400': prestation.need_an_appointment }"
                >
                  <div class="tw-flex tw-justify-between tw-items-start">
                    <div class="tw-flex-1">
                      <div class="tw-font-medium tw-text-gray-900 tw-text-sm">{{ prestation.name }}</div>
                      <div class="tw-text-xs tw-text-gray-500 tw-mb-2">{{ prestation.internal_code }}</div>
                      <div class="tw-flex tw-gap-1 tw-mb-2">
                        <Tag v-if="prestation.need_an_appointment" value="Appointment" severity="warning" class="tw-text-xs tw-rounded-full"/>
                        <Tag v-if="prestation.specialization" :value="prestation.specialization.name" severity="info" class="tw-text-xs tw-rounded-full"/>
                      </div>
                      <div class="tw-font-semibold tw-text-emerald-600 tw-text-sm">
                        {{ formatCurrency(prestation.public_price) }}
                      </div>
                    </div>
                    <Button 
                      icon="pi pi-times" 
                      text
                      severity="danger"
                      size="small"
                      class="tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity"
                      @click="removeFromCustomSelected(prestation)" 
                    />
                  </div>
                </div>
              </div>

              <Divider />

              <!-- Action Button -->
              <Button
                label="Create Fiche Navette"
                icon="pi pi-check"
                @click="createFicheNavette"
                class="tw-w-full tw-h-12 tw-rounded-lg tw-font-semibold tw-text-base"
                size="large"
              />
            </template>
          </Card>

          <!-- Available Doctors -->
          <Card v-if="doctorsBySpecialization.length > 0" class="tw-shadow-sm tw-border-0 tw-bg-white">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
                <div class="tw-w-10 tw-h-10 tw-bg-purple-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-users tw-text-purple-600"></i>
                </div>
                <div>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Available Doctors</h3>
                  <p class="tw-text-sm tw-text-gray-500">{{ doctorsBySpecialization.length }} doctors</p>
                </div>
              </div>

              <div class="tw-space-y-3 tw-max-h-80 tw-overflow-y-auto">
                <div
                  v-for="doctor in doctorsBySpecialization"
                  :key="doctor.id"
                  class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gray-50 tw-rounded-lg tw-border tw-border-gray-200"
                >
                  <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                    <i class="pi pi-user tw-text-blue-600"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-text-sm">{{ doctor.name }}</div>
                    <div class="tw-text-xs tw-text-gray-500">{{ getSpecializationName(doctor.specialization_id) }}</div>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Empty State for Selection -->
          <Card v-if="!hasSelectedItems" class="tw-shadow-sm tw-border-0 tw-bg-white">
            <template #content>
              <div class="tw-text-center tw-py-8">
                <div class="tw-w-16 tw-h-16 tw-bg-gray-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                  <i class="pi pi-shopping-cart tw-text-2xl tw-text-gray-400"></i>
                </div>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">No Items Selected</h3>
                <p class="tw-text-gray-500 tw-text-sm">Select prestations from the table to get started</p>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </div>

  <!-- ADD APPOINTMENT MODALS -->
  <DoctorSelectionModal
    v-model:visible="showDoctorSelectionModal"
    :prestation="selectedPrestationForAppointment"
    :doctors="allDoctors"
    :specializations="specializations"
    :loading="loading"
    @doctor-selected="onDoctorSelectedForAppointment"
    @cancel="onDoctorSelectionCancelled"
  />

  <SameDayAppointmentModal
    v-model:visible="showSameDayAppointmentModal"
    :doctor-id="selectedDoctor"
    :patient-id="patientId"
    :prestation-id="selectedPrestationForAppointment?.id"
    :doctor-specialization-id="selectedDoctorSpecializationId"
    @appointment-booked="onSameDayAppointmentBooked"
  />
</template>

<style scoped>

/* Enhanced styling */
:deep(.p-card) {
  @apply tw-border-0 tw-shadow-sm;
}

:deep(.p-card-body) {
  @apply tw-p-0;
}

:deep(.p-card-content) {
  @apply tw-p-6;
}

:deep(.p-inputtext) {
  @apply tw-border-gray-200 tw-rounded-lg;
}

:deep(.p-inputtext:focus) {
  @apply tw-border-blue-500 tw-ring-2 tw-ring-blue-200 tw-ring-opacity-50;
}

:deep(.p-multiselect), :deep(.p-dropdown) {
  @apply tw-border-gray-200 tw-rounded-lg;
}

:deep(.p-multiselect:focus), :deep(.p-dropdown:focus) {
  @apply tw-border-blue-500 tw-ring-2 tw-ring-blue-200 tw-ring-opacity-50;
}

:deep(.p-button) {
  @apply tw-rounded-lg tw-font-medium;
}

:deep(.p-datatable) {
  @apply tw-border-0 tw-shadow-none;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply tw-bg-gray-50 tw-border-gray-200 tw-text-gray-700 tw-font-semibold tw-text-sm;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply tw-border-gray-100;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  @apply tw-bg-blue-50;
}

:deep(.p-tag) {
  @apply tw-font-medium tw-text-xs;
}

:deep(.p-paginator) {
  @apply tw-border-0 tw-bg-white;
}

:deep(.p-checkbox .p-checkbox-box) {
  @apply tw-border-gray-300 tw-rounded tw-w-4 tw-h-4;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
  @apply tw-bg-blue-500 tw-border-blue-500;
}

:deep(.p-divider) {
  @apply tw-my-4;
}

:deep(.p-divider.p-divider-horizontal:before) {
  @apply tw-border-gray-200;
}

:deep(.p-tooltip .p-tooltip-text) {
  @apply tw-bg-gray-800 tw-text-white tw-rounded-lg tw-px-3 tw-py-2 tw-text-sm tw-shadow-lg;
}

:deep(.p-skeleton) {
  @apply tw-bg-gray-200;
}

/* Custom scrollbar styling */
.tw-overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.tw-overflow-y-auto::-webkit-scrollbar-track {
  @apply tw-bg-gray-100 tw-rounded-full;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb {
  @apply tw-bg-gray-300 tw-rounded-full;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb:hover {
  @apply tw-bg-gray-400;
}

/* Animation classes */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-in {
  animation: slideIn 0.3s ease-out;
}

/* Enhanced hover effects */
.group:hover .tw-opacity-0 {
  @apply tw-opacity-100;
}

/* Blue-25 custom class since it's not in standard Tailwind */
.tw-bg-blue-50 {
  background-color: #f8fafc;
}
</style>