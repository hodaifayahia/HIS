<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Button from 'primevue/button'
import ToggleButton from 'primevue/togglebutton'
import Badge from 'primevue/badge'
import ProgressBar from 'primevue/progressbar'

// New child components
import PrestationSelection from '../components/PrestationSelection.vue'
import CustomPrestationSelection from '../components/CustomPrestationSelection.vue'

// Modals
import ConventionModal from './ConventionManagement.vue'
import SameDayAppointmentModal from './SameDayAppointmentModal.vue'
import AppointmentRequiredAlert from './AppointmentRequiredAlert.vue'
import DoctorSelectionModal from './DoctorSelectionModal.vue'
import ReasonModel from '../../../appointments/ReasonModel.vue'

// Services
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'
import { appointmentService } from '../../services/Appointment/appointmentService.js'

const emit = defineEmits(['cancel', 'created'])
const props = defineProps({
  patientId: {
    type: Number,
    required: true
  },
  ficheNavetteId: {
    type: Number,
    required: false
  }
})

const toast = useToast()

// Global Reactive State
const activeTab = ref(0)
const loading = ref(false)
const creating = ref(false)
const hasSelectedItems = ref(false)

// Convention Mode State
const showConventionModal = ref(false)
const enableConventionMode = ref(false)
const conventionOrganismes = ref([])
const loadingConventions = ref(false)

// Appointment State
const showSameDayModal = ref(false)
const showDoctorSelectionModal = ref(false)
const showAppointmentAlert = ref(false)
const showCancelReasonModal = ref(false)

const currentPrestationForAppointment = ref(null)
const selectedPrestationForAppointment = ref(null)
const appointmentLoading = ref(null)
const prestationToCancel = ref(null)
const selectedDoctor = ref(null)
const selectedDoctorSpecializationId = ref(null)
const prestationsNeedingAppointments = ref([])
const prestationAppointments = ref({})
const fuckuifwork = ref({})

// Common Data (Loaded once on mount)
const allDoctors = ref([])
const specializations = ref([])
const allPrestations = ref([])
const availablePackages = ref([])

// Progress tracking
const loadingProgress = ref(0)
const loadingSteps = ref([
  { name: 'Specializations', completed: false },
  { name: 'Doctors', completed: false },
  { name: 'Prestations', completed: false },
  { name: 'Conventions', completed: false }
])

// METHODS
// --- Data Fetching ---
const fetchInitialData = async () => {
  loading.value = true
  loadingProgress.value = 0
  
  try {
    loadAppointmentData()
    
    const steps = [
      { fn: fetchSpecializations, name: 'Specializations' },
      { fn: fetchAllDoctors, name: 'Doctors' },
      { fn: fetchAllPrestations, name: 'Prestations' },
      { fn: loadConventionCompanies, name: 'Conventions' }
    ]
    
    for (let i = 0; i < steps.length; i++) {
      const step = steps[i]
      await step.fn()
      loadingSteps.value[i].completed = true
      loadingProgress.value = ((i + 1) / steps.length) * 100
    }
    
    console.log('Initial data loaded successfully')
    console.log('Doctors loaded:', allDoctors.value.length)
    console.log('Specializations loaded:', specializations.value.length)
  } catch (error) {
    console.error('Error loading initial data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load data',
      life: 3000
    })
  } finally {
    loading.value = false
    loadingProgress.value = 100
  }
}

const fetchSpecializations = async () => {
  try {
    const result = await ficheNavetteService.getAllSpecializations()
    if (result.success) {
      specializations.value = result.data || []
    }
  } catch (error) {
    console.error('Error fetching specializations:', error)
  }
}

const fetchAllDoctors = async () => {
  try {
    const result = await ficheNavetteService.getAllDoctors()
    if (result.success) {
      allDoctors.value = result.data || []
    }
  } catch (error) {
    console.error('Error fetching all doctors:', error)
  }
}

const fetchAllPrestations = async () => {
  try {
    const prestationResult = await ficheNavetteService.getAllPrestations();
    const packageResult = await ficheNavetteService.getAllPackages();

    if (prestationResult.success) {
      allPrestations.value = prestationResult.data || [];
    }
    if (packageResult.success) {
      availablePackages.value = packageResult.data || [];
    }
  } catch (error) {
    console.error('Error fetching all prestations and packages:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load prestations and packages',
      life: 3000
    });
  }
}

const loadConventionCompanies = async () => {
  if (!props.patientId) return
  try {
    loadingConventions.value = true
    const result = await ficheNavetteService.getPatientConventions(props.patientId, props.ficheNavetteId)
    if (result.success) {
      conventionOrganismes.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading convention organismes:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load convention organismes',
      life: 3000
    })
  } finally {
    loadingConventions.value = false
  }
}

// --- Fiche Navette Creation ---
const createFicheNavette = async (data) => {
  creating.value = true
  try {
    let result
    if (props.ficheNavetteId) {
      result = await ficheNavetteService.addItemsToFiche(props.ficheNavetteId, data)
    } else {
      result = await ficheNavetteService.createFicheNavette(data)
    }
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: props.ficheNavetteId ? 'Items added successfully' : 'Fiche Navette created successfully',
        life: 3000
      })
      resetSelections()
      emit('created', result.data)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error creating fiche navette:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || error.message || 'Failed to create fiche navette',
      life: 3000
    })
  } finally {
    creating.value = false
  }
}

const resetSelections = () => {
  hasSelectedItems.value = false
}

// --- Tab & UI Logic ---
const onTabChange = (event) => {
  if (hasSelectedItems.value) {
    event.preventDefault()
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please create the current selection or reset before switching tabs',
      life: 3000
    })
    return false
  }
  activeTab.value = event.index
  cleanupExpiredAppointments()
}

// --- Appointment & Convention Handlers ---
const handleAppointmentRequired = (prestations) => {
  console.log('=== handleAppointmentRequired called ===')
  console.log('Prestations requiring appointments:', prestations)
  prestationsNeedingAppointments.value = prestations
  fuckuifwork.value = prestations

  if (prestations && prestations.length > 0 && allDoctors.value.length > 0) {
    const firstPrestation = prestations[0]
    console.log('Looking for compatible doctor for prestation:', firstPrestation)
    
    const compatibleDoctor = allDoctors.value.find(doctor => 
      doctor.specialization_id === firstPrestation.specialization_id
    )
    
    if (compatibleDoctor) {
      selectedDoctor.value = compatibleDoctor.id
      selectedDoctorSpecializationId.value = compatibleDoctor.specialization_id
      console.log('Pre-selected compatible doctor for alert:', compatibleDoctor)
    } else {
      console.log('No compatible doctor found for prestation specialization:', firstPrestation.specialization_id)
      selectedDoctor.value = null
      selectedDoctorSpecializationId.value = null
    }
  }
  
  showAppointmentAlert.value = true
}

const handleProceedWithoutAppointments = (itemsToCreate) => {
  console.log('=== handleProceedWithoutAppointments called ===')
  console.log('Items to create:', itemsToCreate)
  showAppointmentAlert.value = false
  createFicheNavette(itemsToCreate)
}

const handleProceedWithAppointments = (appointmentData) => {
  console.log('=== handleProceedWithAppointments called ===')
  console.log('Appointment data received:', appointmentData)
  
  showAppointmentAlert.value = false
  fuckuifwork.value = appointmentData
  
  if (appointmentData.appointmentItems && appointmentData.appointmentItems.length > 0) {
    currentPrestationForAppointment.value = appointmentData.appointmentItems[0]
    console.log('Set currentPrestationForAppointment:', currentPrestationForAppointment.value)
  }
  
  if (appointmentData.otherItems && appointmentData.otherItems.selectedDoctor) {
    selectedDoctor.value = appointmentData.otherItems.selectedDoctor
    const doctor = allDoctors.value.find(d => d.id === appointmentData.otherItems.selectedDoctor)
    selectedDoctorSpecializationId.value = doctor?.specialization_id || null
    console.log('Set selectedDoctor to:', selectedDoctor.value)
    console.log('Doctor details:', doctor)
  }
  
  prestationsNeedingAppointments.value = appointmentData.appointmentItems
  console.log('Prestations needing appointments:', prestationsNeedingAppointments.value)
  
  console.log('Opening SameDayAppointmentModal...')
  console.log('Modal props will be:')
  console.log('- doctor-id:', selectedDoctor.value)
  console.log('- patient-id:', props.patientId)
  console.log('- prestation-id:', currentPrestationForAppointment.value?.id)
  console.log('- doctor-specialization-id:', selectedDoctorSpecializationId.value)
  
  showSameDayModal.value = true
}

// Pass-through functions for modals
const onConventionModeToggle = () => {
  if (enableConventionMode.value) {
    if (!props.ficheNavetteId) {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'Please create a Fiche Navette first before using Convention Mode',
        life: 3000
      })
      enableConventionMode.value = false
      return
    }
    showConventionModal.value = true
  }
}

const onConventionItemsAdded = (data) => {
  enableConventionMode.value = false
  showConventionModal.value = false
  toast.add({
    severity: 'success',
    summary: 'Convention Items Added',
    detail: 'Convention prestations have been added successfully',
    life: 3000
  })
  emit('created', data)
}

const takeAppointmentForPrestation = (prestation) => {
  console.log('=== takeAppointmentForPrestation called ===',prestation);
  
  if (prestationAppointments.value[prestation.id]) {
    toast.add({
      severity: 'info',
      summary: 'Appointment Exists',
      detail: 'This prestation already has an appointment scheduled.',
      life: 3000
    })
    return
  }
  
  console.log('=== takeAppointmentForPrestation called ===')
  console.log('Prestation:', prestation)
  
  // Set the current prestation for appointment
  currentPrestationForAppointment.value = prestation
  appointmentLoading.value = prestation.id
  selectedPrestationForAppointment.value = prestation
  
  // Find a compatible doctor for this prestation
  const compatibleDoctor = allDoctors.value.find(doctor =>
    doctor.specialization_id === prestation.specialization_id &&
    (!selectedDoctor.value || doctor.id === selectedDoctor.value)
  )
  
  if (compatibleDoctor) {
    console.log('Found compatible doctor:', compatibleDoctor)
    onDoctorSelectedForAppointment({
      doctorId: compatibleDoctor.id,
      prestation: prestation
    })
  } else {
    console.log('No compatible doctor found, opening doctor selection modal')
    appointmentLoading.value = null
    showDoctorSelectionModal.value = true
  }
}

const onDoctorSelectedForAppointment = (data) => {
  console.log('=== onDoctorSelectedForAppointment called ===')
  const doctorId = typeof data === 'object' ? data.doctorId : data
  console.log('Selected doctor ID:', doctorId)
  
  selectedDoctor.value = doctorId
  const doctor = allDoctors.value.find(d => d.id === doctorId)
  selectedDoctorSpecializationId.value = doctor?.specialization_id || null
  
  console.log('Doctor selected:', doctor)
  console.log('Current prestation for appointment:', currentPrestationForAppointment.value)
  
  // If we have a prestation from the data object, use it
  if (typeof data === 'object' && data.prestation) {
    currentPrestationForAppointment.value = data.prestation
    console.log('Set prestation from data:', data.prestation)
  }
  
  // Ensure we have a prestation for the appointment
  if (!currentPrestationForAppointment.value && selectedPrestationForAppointment.value) {
    currentPrestationForAppointment.value = selectedPrestationForAppointment.value
    console.log('Set prestation from selectedPrestationForAppointment:', selectedPrestationForAppointment.value)
  }
  
  showDoctorSelectionModal.value = false
  showSameDayModal.value = true
  
  console.log('Doctor selection - Opening SameDayAppointmentModal')
  console.log('Final state - Doctor:', selectedDoctor.value, 'Prestation:', currentPrestationForAppointment.value?.id)
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
  
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }).format(date)
}

const onDoctorSelectionCancelled = () => {
  showDoctorSelectionModal.value = false
  selectedPrestationForAppointment.value = null
  appointmentLoading.value = null
}

const onSameDayAppointmentBooked = (appointment) => {
  console.log('=== onSameDayAppointmentBooked called ===')
  console.log('Appointment data:', appointment)
  
  if (currentPrestationForAppointment.value) {
    prestationAppointments.value[currentPrestationForAppointment.value.id] = {
      id: appointment.id,
      datetime: appointment.appointment_datetime,
      doctor_name: appointment.doctor_name,
      status: 'confirmed'
    }
    saveAppointmentData()
  }
  
  toast.add({ severity: 'success', summary: 'Appointment Booked!', life: 5000 })
  
  showSameDayModal.value = false
  showDoctorSelectionModal.value = false
  showAppointmentAlert.value = false
  showCancelReasonModal.value = false
  
  currentPrestationForAppointment.value = null
  selectedPrestationForAppointment.value = null
  appointmentLoading.value = null
  
  toast.add({
    severity: 'info', 
    summary: 'Appointment Booked', 
    detail: 'You can now proceed to create the fiche navette with all selected items.',
    life: 5000
  })
}

const onAddedToWaitingList = (waitListEntry) => {
  console.log('=== onAddedToWaitingList called ===')
  console.log('Waitlist entry:', waitListEntry)
  
  if (currentPrestationForAppointment.value) {
    prestationAppointments.value[currentPrestationForAppointment.value.id] = {
      id: waitListEntry.id,
      type: 'waitlist',
      date: waitListEntry.date,
      doctor_name: waitListEntry.doctor_name,
      status: 'waiting'
    }
    saveAppointmentData()
  }
  
  toast.add({ severity: 'info', summary: 'Added to Waiting List', life: 5000 })
  
  showSameDayModal.value = false
  showDoctorSelectionModal.value = false
  showAppointmentAlert.value = false
  showCancelReasonModal.value = false
  
  currentPrestationForAppointment.value = null
  selectedPrestationForAppointment.value = null
  appointmentLoading.value = null
  
  toast.add({
    severity: 'info', 
    summary: 'Added to Waiting List', 
    detail: 'You can now proceed to create the fiche navette with all selected items.',
    life: 5000
  })
}

// Computed properties for safe prop access
const safeDoctorId = computed(() => {

  // Then try to get from appointment data
  if (fuckuifwork.value?.otherItems?.selectedDoctor) {
    return fuckuifwork.value.otherItems.selectedDoctor
  }
  
  return null
})

const safeSpecializationId = computed(() => {

  // Then try to get from appointment data
  if (fuckuifwork.value?.otherItems?.selectedSpecialization) {
    return fuckuifwork.value.otherItems.selectedSpecialization
  }
  
  // Try to get from selected doctor info
  if (selectedDoctor.value && allDoctors.value.length > 0) {
    const doctor = allDoctors.value.find(d => d.id === selectedDoctor.value)
    return doctor?.specialization_id || null
  }
  
  return null
})

const safePrestationId = computed(() => {
  // First try to get from the current prestation for appointment
  if (currentPrestationForAppointment.value?.id) {
    return currentPrestationForAppointment.value.id
  }
  
  // Then try to get from the appointment data
  if (fuckuifwork.value?.appointmentItems?.length > 0) {
    return fuckuifwork.value.appointmentItems[0].id
  }
  
  // Finally try from prestations needing appointments
  if (prestationsNeedingAppointments.value?.length > 0) {
    return prestationsNeedingAppointments.value[0].id
  }
  
  return null
})

const cancelAppointment = (prestation) => {
  prestationToCancel.value = prestation
  showCancelReasonModal.value = true
}

const onReasonSubmitted = async (reason) => {
  if (!prestationToCancel.value) return
  try {
    const appointment = prestationAppointments.value[prestationToCancel.value.id]
    const result = await appointmentService.cancelAppointment({
      appointment_id: appointment.id,
      reason: reason,
    })
    if (result.success) {
      delete prestationAppointments.value[prestationToCancel.value.id]
      saveAppointmentData()
      toast.add({ severity: 'success', summary: 'Appointment Cancelled', life: 5000 })
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Cancellation Failed', detail: 'Failed to cancel appointment', life: 3000 })
  } finally {
    showCancelReasonModal.value = false
    prestationToCancel.value = null
  }
}

// --- Persistence Helpers ---
const saveAppointmentData = () => {
  const appointmentData = {
    prestationAppointments: prestationAppointments.value,
    timestamp: Date.now()
  }
  localStorage.setItem(`fiche_appointments_${props.patientId}`, JSON.stringify(appointmentData))
}

const loadAppointmentData = () => {
  try {
    const stored = localStorage.getItem(`fiche_appointments_${props.patientId}`)
    if (stored) {
      const data = JSON.parse(stored)
      const isExpired = Date.now() - data.timestamp > 24 * 60 * 60 * 1000
      if (!isExpired && data.prestationAppointments) {
        prestationAppointments.value = data.prestationAppointments
        cleanupExpiredAppointments()
      }
    }
  } catch (error) {
    console.error('Error loading appointment data:', error)
  }
}

const cleanupExpiredAppointments = () => {
  const validAppointments = {}
  Object.keys(prestationAppointments.value).forEach(prestationId => {
    const appointment = prestationAppointments.value[prestationId]
    if (isAppointmentValid(appointment)) {
      validAppointments[prestationId] = appointment
    }
  })
  prestationAppointments.value = validAppointments
  saveAppointmentData()
}

const isAppointmentValid = (appointment) => {
  if (appointment.status === 'cancelled') return false
  const appointmentDate = new Date(appointment.datetime || appointment.date)
  const now = new Date()
  return appointmentDate > now
}

const debugAppointmentState = () => {
  console.log('=== Debug Appointment State ===')
  console.log('selectedDoctor:', selectedDoctor.value)
  console.log('currentPrestationForAppointment:', currentPrestationForAppointment.value)
  console.log('selectedDoctorSpecializationId:', selectedDoctorSpecializationId.value)
  console.log('prestationsNeedingAppointments:', prestationsNeedingAppointments.value)
  console.log('showSameDayModal:', showSameDayModal.value)
  console.log('================================')
}

// Watch for same day modal visibility changes
watch(showSameDayModal, (newValue) => {
  console.log('SameDayModal visibility changed to:', newValue)
  if (newValue) {
    debugAppointmentState()
  }
})

// --- Computed Properties ---
const hasAnySelectedItems = computed(() => hasSelectedItems.value)
const isPrestationTab = computed(() => activeTab.value === 0)
const isCustomTab = computed(() => activeTab.value === 1)

const otherItemsCount = computed(() => {
  return 0
})

const tabStats = computed(() => {
  return [
    {
      label: 'Prestations',
      icon: 'pi pi-list',
      count: allPrestations.value.length,
      color: '#3b82f6'
    },
    {
      label: 'Custom',
      icon: 'pi pi-plus-circle',
      count: availablePackages.value.length,
      color: '#10b981'
    }
  ]
})

// LIFECYCLE
onMounted(() => {
  console.log('=== FicheNavetteItemCreate mounted ===')
  console.log('Props:', props)
  fetchInitialData().then(() => {
    console.log('Initial data fetch completed')
    debugAppointmentState()
  })
})
</script>

<template>
  <div class=" tw-bg-gradient-to-br tw-from-slate-50 tw-to-blue-50">
    <div class="tw-max-w-7xl tw-mx-auto tw-space-y-1">
      
      <!-- Header Section -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-gray-200 tw-overflow-hidden">
       
        <!-- Loading Progress -->
        <div v-if="loading" class="tw-px-2 tw-py-2 tw-bg-gray-50 tw-border-b tw-border-gray-200">
          <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
            <span class="tw-text-sm tw-font-medium tw-text-gray-700">Loading data...</span>
            <span class="tw-text-sm tw-text-gray-500">{{ Math.round(loadingProgress) }}%</span>
          </div>
          <ProgressBar :value="loadingProgress" class="tw-h-2 tw-mb-3" />
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <Badge
              v-for="step in loadingSteps"
              :key="step.name"
              :value="step.name"
              :severity="step.completed ? 'success' : 'secondary'"
              class="tw-text-xs"
            />
          </div>
        </div>
      </div>

      <!-- Convention Mode Toggle -->
      <Card class="tw-shadow-lg tw-border-0 tw-overflow-hidden">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-amber-50 tw-to-orange-50 tw-p-1 tw-rounded-xl tw-border tw-border-amber-200">
            <div class="tw-flex tw-items-center tw-justify-between">
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-w-12 tw-h-12 tw-bg-amber-500 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
                  <i class="pi pi-building tw-text-white tw-text-xl"></i>
                </div>
                <div>
                  <h3 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-1">Convention Mode</h3>
                  <p class="tw-text-gray-600 tw-text-sm">Enable special pricing through patient conventions</p>
                </div>
              </div>
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-text-right tw-mr-2">
                  <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Status</div>
                  <Badge
                    :value="enableConventionMode ? 'Active' : 'Regular'"
                    :severity="enableConventionMode ? 'success' : 'secondary'"
                    class="tw-font-semibold"
                  />
                </div>
                <ToggleButton
                  v-model="enableConventionMode"
                  onLabel="Convention"
                  offLabel="Regular"
                  onIcon="pi pi-building"
                  offIcon="pi pi-list"
                  @change="onConventionModeToggle"
                  class="tw-w-32 tw-shadow-lg"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Enhanced TabView -->
      <Card class="tw-shadow-xl tw-border-0 tw-overflow-hidden">
        <template #content>
          <div class="tw-p-6">
            <!-- Custom Tab Headers -->
            <div class="tw-flex tw-gap-2 tw-bg-gray-100 tw-rounded-lg tw-p-0.5 tw-mb-4">
              <button
                v-for="(tab, index) in tabStats"
                :key="index"
                @click="activeTab = index"
                :class="[
                  'tw-flex tw-items-center tw-gap-2 tw-px-4 tw-py-2 tw-rounded-md tw-flex-1 tw-transition-all tw-duration-300 tw-font-medium tw-text-sm',
                  activeTab === index
                    ? 'tw-bg-white tw-text-gray-900 tw-shadow-sm '
                    : 'tw-text-gray-600 hover:tw-text-gray-900  hover:tw-bg-gray-50',
                  hasAnySelectedItems && activeTab !== index && 'tw-opacity-50 tw-cursor-not-allowed'
                ]"
                :disabled="hasAnySelectedItems && activeTab !== index"
              >
                <i :class="tab.icon" :style="{ color: activeTab === index ? tab.color : '' }"></i>
                <span>{{ tab.label }}</span>
                <Badge
                  :value="tab.count"
                  :style="{ backgroundColor: activeTab === index ? tab.color : '#6b7280' }"
                  class="tw-text-white tw-min-w-[20px] tw-h-5 tw-text-xs"
                />
              </button>
            </div>

            <!-- Tab Content -->
            <div class="tw-transition-all tw-duration-300">
              <div v-show="activeTab === 0" class="tw-space-y-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
                  <i class="pi pi-list tw-text-blue-600"></i>
                  <h3 class="tw-font-semibold tw-text-gray-900">Available Prestations</h3>
                  <Badge
                    :value="`${allPrestations.length} items`"
                    severity="info"
                    class="tw-ml-auto"
                  />
                </div>
                <PrestationSelection
                  :specializations="specializations"
                  :all-doctors="allDoctors"
                  :available-prestations="allPrestations"
                  :available-packages="availablePackages"
                  :loading="loading"
                  :prestation-appointments="prestationAppointments"
                  :appointment-loading="appointmentLoading"
                  :selected-doctor="selectedDoctor"
                  :type="activeTab === 0 ? 'prestation' : 'custom'" 
                  @update:has-selected-items="hasSelectedItems = $event"
                  @update:prestation-appointments="prestationAppointments = $event; saveAppointmentData()"
                  @take-appointment="takeAppointmentForPrestation"
                  @cancel-appointment="cancelAppointment"
                  @items-to-create="createFicheNavette"
                  @appointment-required="handleAppointmentRequired"
                />
              </div>

              <div v-show="activeTab === 1" class="tw-space-y-4 ">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
                  <i class="pi pi-plus-circle tw-text-green-600"></i>
                  <h3 class="tw-font-semibold tw-text-gray-900">Custom Services</h3>
                  <Badge
                    :value="`${availablePackages.length} packages`"
                    severity="success"
                    class="tw-ml-auto"
                  />
                </div>
                <CustomPrestationSelection
                  :specializations="specializations"
                  :all-prestations="allPrestations"
                  :all-doctors="allDoctors"
                  :loading="loading"
                  :prestation-appointments="prestationAppointments"
                  :appointment-loading="appointmentLoading"
                  :patient-id="props.patientId"
                  :fiche-navette-id="props.ficheNavetteId"
                  :type="activeTab === 0 ? 'prestation' : 'custom'"
                  @update:has-selected-items="hasSelectedItems = $event"
                  @update:prestation-appointments="prestationAppointments = $event; saveAppointmentData()"
                  @take-appointment="takeAppointmentForPrestation"
                  @cancel-appointment="cancelAppointment"
                  @items-to-create="createFicheNavette"
                  @appointment-required="handleAppointmentRequired"
                />
              </div>
            </div>

            <!-- Status Bar -->
            <div v-if="hasSelectedItems || creating" class="tw-mt-6 tw-p-4 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-border tw-border-blue-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-blue-600 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-check tw-text-white tw-text-sm"></i>
                  </div>
                  <div>
                    <span class="tw-font-semibold tw-text-gray-900">
                      {{ hasSelectedItems ? 'Items Selected' : 'Processing...' }}
                    </span>
                    <p class="tw-text-sm tw-text-gray-600">
                      {{ hasSelectedItems ? 'Ready to create fiche navette' : 'Please wait while we process your request' }}
                    </p>
                  </div>
                </div>
                <div v-if="creating" class="tw-flex tw-items-center tw-gap-2">
                  <ProgressBar mode="indeterminate" class="tw-w-24 tw-h-2" />
                  <span class="tw-text-sm tw-text-gray-500">Creating...</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- All Modals -->
    <ConventionModal
      v-model:visible="showConventionModal"
      :ficheNavetteId="props.ficheNavetteId"
      @convention-items-added="onConventionItemsAdded"
    />
    <SameDayAppointmentModal
      v-model:visible="showSameDayModal"
      :doctor-id="safeDoctorId"
      :patient-id="props.patientId"
      :fuckuifwork="fuckuifwork"
      :prestation-id="safePrestationId"
      :doctor-specialization-id="safeSpecializationId"
      @appointment-booked="onSameDayAppointmentBooked"
      @added-to-waitlist="onAddedToWaitingList"
    />
    
    <AppointmentRequiredAlert
      v-model:visible="showAppointmentAlert"
      :prestations-needing-appointments="prestationsNeedingAppointments"
      :other-items-count="otherItemsCount"
      :selected-doctor="selectedDoctor"
      @proceed-with-appointments="handleProceedWithAppointments"
      @proceed-without-appointments="handleProceedWithoutAppointments"
      @cancel="showAppointmentAlert = false"
    />
    
    <DoctorSelectionModal
      v-model:visible="showDoctorSelectionModal"
      :prestation="selectedPrestationForAppointment"
      :doctors="allDoctors"
      :specializations="specializations"
      :loading="loading"
      @doctor-selected="onDoctorSelectedForAppointment"
      @cancel="onDoctorSelectionCancelled"
    />
    
    <ReasonModel
      :show="showCancelReasonModal"
      @submit="onReasonSubmitted"
      @close="showCancelReasonModal = false; prestationToCancel = null"
    />
  </div>
</template>

<style scoped>
/* Modern UI Enhancements */
.add-items-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Enhanced Card Animations */
:deep(.p-card) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(10px);
}

:deep(.p-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Custom Toggle Button Styling */
:deep(.p-togglebutton) {
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
}

:deep(.p-togglebutton.p-highlight) {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-color: #3b82f6;
  transform: scale(1.02);
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

:deep(.p-togglebutton:not(.p-highlight)) {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #64748b;
}

:deep(.p-togglebutton:not(.p-highlight):hover) {
  background: #f1f5f9;
  border-color: #cbd5e1;
  transform: scale(1.01);
}

/* Enhanced Button Styling */
:deep(.p-button) {
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

:deep(.p-button-text) {
  box-shadow: none;
}

:deep(.p-button-text:hover) {
  transform: scale(1.05);
  box-shadow: none;
}

/* Progress Bar Enhancements */
:deep(.p-progressbar) {
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.1);
}

:deep(.p-progressbar .p-progressbar-value) {
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  border-radius: 6px;
}

:deep(.p-progressbar[mode="indeterminate"] .p-progressbar-indeterminate-container) {
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #3b82f6);
  border-radius: 6px;
}

/* Badge Enhancements */
:deep(.p-badge) {
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Enhanced Tab Button Hover Effects */
.tw-transition-all:hover {
  transform: translateY(-1px);
}

/* Custom scrollbar for modern look */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 768px) {
  .tw-p-6 {
    @apply tw-p-4;
  }
  
  .tw-gap-6 {
    @apply tw-gap-4;
  }
  
  .tw-text-2xl {
    @apply tw-text-xl;
  }
}

/* Loading Animation */
@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.tw-animate-shimmer {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}
</style>