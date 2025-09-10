<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Button from 'primevue/button'
import ToggleButton from 'primevue/togglebutton'

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
const selectedDoctor = ref(null) // This will be passed down to child components
const selectedDoctorSpecializationId = ref(null)
const prestationsNeedingAppointments = ref([])
const prestationAppointments = ref({})

// Common Data (Loaded once on mount)
const allDoctors = ref([])
const specializations = ref([])
const allPrestations = ref([])
const availablePackages = ref([])

// METHODS
// --- Data Fetching ---
const fetchInitialData = async () => {
  loading.value = true
  try {
    loadAppointmentData()
    await Promise.all([
      fetchSpecializations(),
      fetchAllDoctors(),
      fetchAllPrestations(),
      loadConventionCompanies()
    ])
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load data',
      life: 3000
    })
  } finally {
    loading.value = false
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
  // Logic to reset selections in child components
  // We'll use a key or a method call to trigger this
  // For now, we can reset a flag that child components can watch
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
  prestationsNeedingAppointments.value = prestations
  showAppointmentAlert.value = true
}

const handleProceedWithoutAppointments = (itemsToCreate) => {
  showAppointmentAlert.value = false
  createFicheNavette(itemsToCreate)
}

const handleProceedWithAppointments = (itemsToCreate) => {
  showAppointmentAlert.value = false
  // For now, let's just create the fiche without the appointment items
  // A more advanced flow would handle this differently.
  createFicheNavette(itemsToCreate.otherItems)

  // Start the appointment booking flow for the first item
  if (itemsToCreate.appointmentItems.length > 0) {
    currentPrestationForAppointment.value = itemsToCreate.appointmentItems[0]
    showSameDayModal.value = true
  }
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
  if (prestationAppointments.value[prestation.id]) {
    toast.add({
      severity: 'info',
      summary: 'Appointment Exists',
      detail: 'This prestation already has an appointment scheduled.',
      life: 3000
    })
    return
  }
  appointmentLoading.value = prestation.id
  selectedPrestationForAppointment.value = prestation
  const compatibleDoctor = allDoctors.value.find(doctor =>
    doctor.specialization_id === prestation.specialization_id &&
    doctor.id === selectedDoctor.value
  )
  if (compatibleDoctor) {
    onDoctorSelectedForAppointment({
      doctorId: compatibleDoctor.id,
      prestation: prestation
    })
  } else {
    appointmentLoading.value = null
    showDoctorSelectionModal.value = true
  }
}

const onDoctorSelectedForAppointment = (data) => {
  showDoctorSelectionModal.value = false
  selectedDoctor.value = data.doctorId
  currentPrestationForAppointment.value = data.prestation
  const doctor = allDoctors.value.find(d => d.id === data.doctorId)
  selectedDoctorSpecializationId.value = doctor?.specialization_id || null
  showSameDayModal.value = true
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
  currentPrestationForAppointment.value = null
  selectedPrestationForAppointment.value = null
  appointmentLoading.value = null
}

const onAddedToWaitingList = (waitListEntry) => {
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
  currentPrestationForAppointment.value = null
  selectedPrestationForAppointment.value = null
  appointmentLoading.value = null
}

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

// --- Computed Properties ---
const hasAnySelectedItems = computed(() => hasSelectedItems.value)
const isPrestationTab = computed(() => activeTab.value === 0)
const isCustomTab = computed(() => activeTab.value === 1)

// LIFECYCLE
onMounted(() => {
  fetchInitialData()
})
</script>

<template>
  <div class="add-items-container">
    <Card class="main-card">
      <template #content>
        <Card class="convention-toggle-card mb-4">
          <template #content>
            <div class="convention-toggle">
              <div class="toggle-info">
                <h5>Convention Mode</h5>
                <p>Enable to add prestations through conventions with special pricing</p>
              </div>
              
              <ToggleButton
                v-model="enableConventionMode"
                onLabel="Convention Mode"
                offLabel="Regular Mode"
                onIcon="pi pi-building"
                offIcon="pi pi-list"
                @change="onConventionModeToggle"
                class="convention-mode-toggle"
              />
            </div>
          </template>
        </Card>

        <TabView v-model:activeIndex="activeTab" @tab-change="onTabChange" class="custom-tabview">
          <TabPanel header="Prestations" :disabled="hasAnySelectedItems && isCustomTab">
        <!-- In FicheNavetteItemCreate.vue template -->
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
  @take-appointment="takeAppointmentForPrestation"
  @cancel-appointment="cancelAppointment"
  @items-to-create="createFicheNavette"
  @appointment-required="handleAppointmentRequired"
/>
          </TabPanel>

          <TabPanel header="Custom" :disabled="hasAnySelectedItems && isPrestationTab">
            <CustomPrestationSelection
              :specializations="specializations"
              :all-prestations="allPrestations"
              :all-doctors="allDoctors"
              :loading="loading"
              :prestation-appointments="prestationAppointments"
              :appointment-loading="appointmentLoading"
                :type="activeTab === 0 ? 'prestation' : 'custom'"
              @update:has-selected-items="hasSelectedItems = $event"
              @take-appointment="takeAppointmentForPrestation"
              @cancel-appointment="cancelAppointment"
              @items-to-create="createFicheNavette"
              @appointment-required="handleAppointmentRequired"
            />
          </TabPanel>
        </TabView>

        <div class="action-buttons">
        </div>
      </template>
    </Card>

    <ConventionModal
      v-model:visible="showConventionModal"
      :ficheNavetteId="props.ficheNavetteId"
      @convention-items-added="onConventionItemsAdded"
    />
    <SameDayAppointmentModal
      v-model:visible="showSameDayModal"
      :doctor-id="selectedDoctor"
      :patient-id="props.patientId"
      :prestation-id="currentPrestationForAppointment?.id"
      :doctor-specialization-id="selectedDoctorSpecializationId"
      @appointment-booked="onSameDayAppointmentBooked"
      @added-to-waitlist="onAddedToWaitingList"
    />
    <AppointmentRequiredAlert
      :visible="showAppointmentAlert"
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
/* All the parent styles from the original file should go here */
</style>