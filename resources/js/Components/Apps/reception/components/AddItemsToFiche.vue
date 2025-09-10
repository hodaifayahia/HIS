<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Button from 'primevue/button'

// Import the new components
import PrestationSelection from './PrestationSelection.vue'
import CustomPrestationSelection from './CustomPrestationSelection.vue'
import ConventionModal from './ConventionManagement.vue'
import SameDayAppointmentModal from './SameDayAppointmentModal.vue'
import AppointmentRequiredAlert from './AppointmentRequiredAlert.vue'
import DoctorSelectionModal from './DoctorSelectionModal.vue'
import ReasonModel from '../../../appointments/ReasonModel.vue'

// Import services
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'
import { appointmentService } from '../../services/Appointment/appointmentService.js'

const emit = defineEmits(['cancel', 'created'])
const props = defineProps({
  patientId: { type: Number, required: true },
  ficheNavetteId: { type: Number, required: false },
})

const toast = useToast()

// Reactive data (centralized state)
const activeTab = ref(0)
const loading = ref(false)
const creating = ref(false)
const hasSelectedItems = ref(false)

// Convention Mode state
const showConventionModal = ref(false)
const enableConventionMode = ref(false)

// Same Day Appointment state
const showSameDayModal = ref(false)
const selectedPrestationForAppointment = ref(null)
const selectedDoctorSpecializationId = ref(null)
const showDoctorSelectionModal = ref(false)

// Common data
const selectedSpecialization = ref(null)
const selectedDoctor = ref(null)
const specializations = ref([])
const allDoctors = ref([])
const allPrestations = ref([])
const availablePackages = ref([])

// Prestation tab data
const showPackages = ref(false)
const selectedPrestation = ref(null)
const selectedPackage = ref(null)
const dependencies = ref([])
const selectedDependencies = ref([])
const packagePrestations = ref([])
const prestationAppointments = ref({})

// Custom tab data
const customSelectedPrestations = ref([])
const searchTerm = ref('')
const selectedSpecializationsFilter = ref([])
const selectedCustomNameOption = ref(null)
const customPrestationName = ref('')

// Appointment alert state
const showAppointmentAlert = ref(false)
const prestationsNeedingAppointments = ref([])

// Appointment cancellation state
const showCancelReasonModal = ref(false)
const prestationToCancel = ref(null)

// --- COMPUTED PROPERTIES ---
const nameToUse = computed(() => {
  if (selectedCustomNameOption.value === 'other') {
    return customPrestationName.value.trim() || null
  }
  return selectedCustomNameOption.value
})

const canSwitchTabs = computed(() => !hasSelectedItems.value)

// --- METHODS (kept in parent for central state management) ---
const onTabChange = (event) => {
  if (!canSwitchTabs.value) {
    toast.add({ severity: 'warn', summary: 'Warning', detail: 'Please create or reset selection before switching tabs', life: 3000 })
    return false
  }
  activeTab.value = event.index
  resetSelections()
}

const updateHasSelectedItems = () => {
  if (activeTab.value === 0) {
    hasSelectedItems.value = selectedPrestation.value !== null || selectedPackage.value !== null
  } else {
    hasSelectedItems.value = customSelectedPrestations.value.length > 0
  }
}

const resetSelections = () => {
  selectedPrestation.value = null
  selectedPackage.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []
  customSelectedPrestations.value = []
  searchTerm.value = ''
  selectedSpecializationsFilter.value = []
  selectedCustomNameOption.value = null
  customPrestationName.value = ''
  updateHasSelectedItems()
}

// Main logic for creating the fiche navette
const createFicheNavette = async () => {
  creating.value = true
  try {
    const prestationsToCheck = activeTab.value === 0
      ? [selectedPrestation.value, ...selectedDependencies.value].filter(Boolean)
      : customSelectedPrestations.value
    
    prestationsNeedingAppointments.value = prestationsToCheck.filter(p => p.need_an_appointment)

    if (prestationsNeedingAppointments.value.length > 0) {
      showAppointmentAlert.value = true
    } else {
      await proceedWithFicheNavetteCreation()
    }
  } catch (error) {
    console.error('Error creating fiche navette:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to create Fiche Navette', life: 5000 })
  } finally {
    creating.value = false
  }
}

const proceedWithFicheNavetteCreation = async () => {
  // ... (Keep the full logic here)
}

// Appointment handling methods
const handleProceedWithAppointments = (data) => {
  showAppointmentAlert.value = false
  if (data.prestationsNeedingAppointments.length > 0) {
    selectedPrestationForAppointment.value = data.prestationsNeedingAppointments[0]
    showDoctorSelectionModal.value = true
  }
}

const onDoctorSelectedForAppointment = (data) => {
  showDoctorSelectionModal.value = false
  selectedDoctor.value = data.doctorId
  selectedPrestationForAppointment.value = data.prestation
  const doctor = allDoctors.value.find(d => d.id === data.doctorId)
  selectedDoctorSpecializationId.value = doctor?.specialization_id || null
  showSameDayModal.value = true
}

const onSameDayAppointmentBooked = (appointment) => {
  if (selectedPrestationForAppointment.value) {
    prestationAppointments.value[selectedPrestationForAppointment.value.id] = {
      id: appointment.id,
      datetime: appointment.appointment_datetime,
      doctor_name: appointment.doctor_name,
      status: 'confirmed'
    }
    saveAppointmentData()
  }
  showSameDayModal.value = false
}

// ... (Other appointment methods like `onAddedToWaitingList`, `cancelAppointment`, etc. would also be here)

// Data fetching and lifecycle hooks
onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      fetchSpecializations(),
      fetchAllDoctors(),
      fetchAllPrestations(),
      // loadConventionCompanies()
    ])
  } finally {
    loading.value = false
  }
})

// --- TEMPLATE ---
</script>

<template>
  <div class="add-items-container">
    <Card class="main-card">
      <template #content>
        <TabView v-model:activeIndex="activeTab" @tab-change="onTabChange">
          <TabPanel header="Prestations" :disabled="!canSwitchTabs && activeTab !== 0">
            <PrestationSelection
              :specializations="specializations"
              :doctors="allDoctors"
              :availablePrestations="availablePrestations"
              :availablePackages="availablePackages"
              :loading="loading"
              v-model:selectedSpecialization="selectedSpecialization"
              v-model:selectedDoctor="selectedDoctor"
              v-model:selectedPrestation="selectedPrestation"
              v-model:selectedPackage="selectedPackage"
              v-model:dependencies="dependencies"
              v-model:selectedDependencies="selectedDependencies"
              v-model:packagePrestations="packagePrestations"
              v-model:prestationAppointments="prestationAppointments"
              @update:hasSelectedItems="updateHasSelectedItems"
              @take-appointment="takeAppointmentForPrestation"
              @cancel-appointment="cancelAppointment"
            />
          </TabPanel>
          <TabPanel header="Custom" :disabled="!canSwitchTabs && activeTab !== 1">
            <CustomPrestationSelection
              :allPrestations="allPrestations"
              :specializations="specializations"
              :allDoctors="allDoctors"
              :loading="loading"
              v-model:customSelectedPrestations="customSelectedPrestations"
              v-model:searchTerm="searchTerm"
              v-model:selectedSpecializationsFilter="selectedSpecializationsFilter"
              v-model:selectedCustomNameOption="selectedCustomNameOption"
              v-model:customPrestationName="customPrestationName"
              v-model:prestationAppointments="prestationAppointments"
              @update:hasSelectedItems="updateHasSelectedItems"
              @take-appointment="takeAppointmentForPrestation"
              @cancel-appointment="cancelAppointment"
            />
          </TabPanel>
        </TabView>

        <div class="action-buttons">
          <Button
            :label="props.ficheNavetteId ? 'Add Items' : 'Create Fiche Navette'"
            icon="pi pi-check"
            @click="createFicheNavette"
            :loading="creating"
            :disabled="!hasSelectedItems"
          />
        </div>
      </template>
    </Card>

    <SameDayAppointmentModal
      v-model:visible="showSameDayModal"
      :doctor-id="selectedDoctor"
      :patient-id="props.patientId"
      :prestation-id="selectedPrestationForAppointment?.id"
      :doctor-specialization-id="selectedDoctorSpecializationId"
      @appointment-booked="onSameDayAppointmentBooked"
      @added-to-waitlist="onAddedToWaitingList"
      @update:visible="showSameDayModal = $event"
    />
    <AppointmentRequiredAlert
      :visible="showAppointmentAlert"
      :prestations-needing-appointments="prestationsNeedingAppointments"
      :other-items-count="otherItemsCount"
      :selected-doctor="selectedDoctor"
      @update:visible="showAppointmentAlert = $event"
      @proceed-with-appointments="handleProceedWithAppointments"
      @proceed-without-appointments="handleProceedWithoutAppointments"
      @cancel="handleCancelAppointmentProcess"
    />
    <DoctorSelectionModal
      v-model:visible="showDoctorSelectionModal"
      :prestation="selectedPrestationForAppointment"
      :doctors="allDoctors"
      :specializations="specializations"
      :loading="loading"
      @doctor-selected="onDoctorSelectedForAppointment"
      @cancel="showDoctorSelectionModal = false"
    />
    <ReasonModel
      :show="showCancelReasonModal"
      @submit="onReasonSubmitted"
      @close="showCancelReasonModal = false"
    />
  </div>
</template>