<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Stepper from 'primevue/stepper'
import StepperPanel from 'primevue/stepperpanel'
import Card from 'primevue/card'
import MultiSelect from 'primevue/multiselect'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Calendar from 'primevue/calendar'
import FileUpload from 'primevue/fileupload'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Message from 'primevue/message'

// Import PatientSearch component
import PatientSearch from '../../../../Pages/Appointments/PatientSearch.vue'

// ADD THESE IMPORTS
import AppointmentRequiredAlert from './AppointmentRequiredAlert.vue'
import SameDayAppointmentModal from './SameDayAppointmentModal.vue'

// Services
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'

// Props & Emits
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  ficheNavetteId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits([
  'update:visible',
  'convention-items-added'
])

// Composables
const toast = useToast()

// State
const activeStep = ref(0)
const loading = ref(false)
const creating = ref(false)

// Combined Step 1 state (Date + Family Auth + Patient)
const priseEnChargeDate = ref(null)
const familyAuthOptions = ref([])
const selectedFamilyAuth = ref(null)
const familyAuthLoading = ref(false)
const adherentPatientSearch = ref('')
const selectedAdherentPatient = ref(null)

// Convention selection (Step 2)
const organismes = ref([])
const conventions = ref([])
const filteredConventions = ref([])
const conventionPrestations = ref([])

// New: Specialization and Doctor selection (after convention selection)
const specializations = ref([])
const allDoctors = ref([])
const selectedSpecialization = ref(null)
const selectedDoctor = ref(null)
const specializationLoading = ref(false)
const doctorLoading = ref(false)

// Workflow state
const currentOrganisme = ref(null)
const currentConvention = ref(null)
const currentPrestations = ref([])
const completedConventions = ref([])

// File upload
const uploadedFiles = ref([])

// ADD THESE MISSING REACTIVE VARIABLES FOR APPOINTMENTS
const showAppointmentAlert = ref(false)
const showSameDayModal = ref(false)
const prestationsNeedingAppointments = ref([])
const currentPrestationForAppointment = ref(null)
const pendingConventionData = ref(null)

// Computed properties
const visibleModal = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const requiresAdherentPatient = computed(() => {
  if (!selectedFamilyAuth.value) {
    return false
  }
  const hasNonAdherent = selectedFamilyAuth.value !== 'adherent'
  return hasNonAdherent
})

const canProceedToConventions = computed(() => {
  const dateOk = priseEnChargeDate.value
  const familyAuthOk = selectedFamilyAuth.value !== null
  const patientOk = !requiresAdherentPatient.value || selectedAdherentPatient.value

  return dateOk && familyAuthOk && patientOk
})

const filteredDoctors = computed(() => {
  if (!selectedSpecialization.value || !allDoctors.value.length) {
    return []
  }
  return allDoctors.value.filter(doctor =>
    doctor.specialization_id === selectedSpecialization.value
  )
})

// Check for existing convention (check if any selected convention already exists)
const existingConventionIndex = computed(() => {
  if (!currentConvention.value) {
    return -1
  }

  // Check if the current convention is already in the completed list
  return completedConventions.value.findIndex(conv => conv.convention.id === currentConvention.value);
})

const isAddingToExisting = computed(() => {
  return existingConventionIndex.value !== -1
})

// Get already selected prestation IDs for the current convention to prevent duplicates in the UI
const alreadySelectedPrestationIds = computed(() => {
  if (existingConventionIndex.value === -1) {
    return [];
  }
  return completedConventions.value[existingConventionIndex.value].prestations.map(p => p.prestation_id);
})

// Filter out prestations that are already selected for the current convention
const filteredConventionPrestations = computed(() => {
  if (!conventionPrestations.value.length || !selectedSpecialization.value) {
    return [];
  }

  const selectedPrestationIds = new Set(alreadySelectedPrestationIds.value);
  
  return conventionPrestations.value.filter(prestation => {
    const prestationId = prestation.prestation_id || prestation.id;
    const isForSpecialization = prestation.specialization_id === selectedSpecialization.value ||
                                  prestation.service_specialization_id === selectedSpecialization.value;
    const isAlreadySelected = selectedPrestationIds.has(prestationId);

    return isForSpecialization && !isAlreadySelected;
  });
})

const canAddConvention = computed(() => {
  return currentOrganisme.value &&
           currentConvention.value &&
           currentPrestations.value.length > 0 &&
           selectedSpecialization.value &&
           selectedDoctor.value
})

const canCreatePrescription = computed(() => {
  return completedConventions.value.length > 0
})

// Methods
const formatDateForApi = (date) => {
  if (!date) return null
  return new Date(date).toISOString().split('T')[0]
}
const fetchFamilyAuthorization = async () => {
  console.log('fetchFamilyAuthorization called with date:', priseEnChargeDate.value) // Debug
  
  if (!priseEnChargeDate.value) {
    console.log('No date selected, clearing options') // Debug
    familyAuthOptions.value = []
    selectedFamilyAuth.value = null
    return
  }

  try {
    familyAuthLoading.value = true
    const formattedDate = formatDateForApi(priseEnChargeDate.value)
    console.log('Formatted date for API:', formattedDate) // Debug
    
    const response = await ficheNavetteService.getFamilyAuthorization(formattedDate)
    console.log('API response:', response) // Debug
    
    if (response.success) {
      familyAuthOptions.value = response.data || []
      console.log('Family auth options set:', familyAuthOptions.value) // Debug
    } else {
      console.error('API returned error:', response.message)
      familyAuthOptions.value = []
    }
  } catch (error) {
    console.error('Error fetching family authorization:', error)
    familyAuthOptions.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load family authorization options',
      life: 3000
    })
  } finally {
    familyAuthLoading.value = false
  }
}

const onDateChanged = async () => {
  selectedFamilyAuth.value = null
  selectedAdherentPatient.value = null
  await fetchFamilyAuthorization()
}

const onFamilyAuthChanged = () => {
  if (!requiresAdherentPatient.value) {
    selectedAdherentPatient.value = null
  }
}

const onAdherentPatientSelected = (patient) => {
  selectedAdherentPatient.value = patient
}

const loadOrganismes = async () => {
  try {
    const result = await ficheNavetteService.getCompanies()
    if (result.success) {
      organismes.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading organismes:', error)
  }
}

const loadConventions = async () => {
  try {
    const result = await ficheNavetteService.getConventions()
    if (result.success) {
      conventions.value = Array.isArray(result.data) ? result.data : []
    }
  } catch (error) {
    console.error('Error loading conventions:', error)
  }
}

const loadSpecializations = async () => {
  try {
    specializationLoading.value = true
    const result = await ficheNavetteService.getAllSpecializations()
    if (result.success) {
      specializations.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading specializations:', error)
  } finally {
    specializationLoading.value = false
  }
}

const loadAllDoctors = async () => {
  try {
    doctorLoading.value = true
    const result = await ficheNavetteService.getAllDoctors()
    if (result.success) {
      allDoctors.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading doctors:', error)
  } finally {
    doctorLoading.value = false
  }
}

const onOrganismeChange = async () => {
  currentConvention.value = null
  currentPrestations.value = []
  conventionPrestations.value = []
  selectedSpecialization.value = null
  selectedDoctor.value = null

  if (!currentOrganisme.value) {
    filteredConventions.value = []
    return
  }

  try {
    const result = await ficheNavetteService.getConventionsByOrganismes([currentOrganisme.value])
    if (result.success) {
      filteredConventions.value = Array.isArray(result.data) ? result.data : []
    }
  } catch (error) {
    console.error('Error filtering conventions:', error)
    filteredConventions.value = []
  }
}

const onConventionChange = async () => {
  currentPrestations.value = []
  selectedSpecialization.value = null
  selectedDoctor.value = null

  if (!currentConvention.value) {
    conventionPrestations.value = []
    return
  }

  if (!priseEnChargeDate.value) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Please select Prise en Charge Date first',
      life: 3000
    })
    return
  }

  try {
    loading.value = true
    
    const conventionIds = [currentConvention.value]
    
    const result = await ficheNavetteService.getPrestationsWithConventionPricing(
      conventionIds,
      formatDateForApi(priseEnChargeDate.value)
    )

    if (result.success && result.data) {
      conventionPrestations.value = Array.isArray(result.data) ? result.data : []
    } else {
      conventionPrestations.value = []
    }
  } catch (error) {
    console.error('Error loading prestations:', error)
    conventionPrestations.value = []
  } finally {
    loading.value = false
  }
}

const onSpecializationChange = () => {
  selectedDoctor.value = null
  currentPrestations.value = []
}


// --- MODIFIED addConvention METHOD ---
const addConvention = () => {
  if (!canAddConvention.value) return

  // Always proceed with convention creation without checking appointments
  // Appointment handling will be done at the final step
  proceedWithConventionCreation()
}

// Add new methods for handling appointments
const handleProceedWithAppointments = (data) => {
  showAppointmentAlert.value = false
  
  // Prepare the first prestation with proper doctor/specialization info
  if (data.prestationsNeedingAppointments.length > 0) {
    const prestation = data.prestationsNeedingAppointments[0]
    
    // Find the convention that contains this prestation to get doctor info
    let doctorId = selectedDoctor.value
    let specializationId = selectedSpecialization.value
    
    // Look through completed conventions to find the right doctor for this prestation
    for (const convention of completedConventions.value) {
      const found = convention.prestations.find(p => 
        (p.prestation_id || p.id) === (prestation.prestation_id || prestation.id)
      )
      if (found) {
        doctorId = convention.doctor.id
        specializationId = convention.specialization.id
        break
      }
    }
    
    // Enhance the prestation object with doctor info
    currentPrestationForAppointment.value = {
      ...prestation,
      doctorId: doctorId,
      specializationId: specializationId
    }
    
    console.log('Setting up appointment modal with:', {
      doctorId,
      specializationId,
      patientId: selectedAdherentPatient.value?.id,
      prestationId: prestation.prestation_id || prestation.id
    })
    
    showSameDayModal.value = true
  }
}

const handleProceedWithoutAppointments = () => {
  showAppointmentAlert.value = false
  
  // Remove prestations that need appointments from completed conventions
  completedConventions.value.forEach(convention => {
    convention.prestations = convention.prestations.filter(p => !p.need_an_appointment)
    convention.totalPrestations = convention.prestations.length
  })
  
  // Remove conventions that have no prestations left
  completedConventions.value = completedConventions.value.filter(conv => conv.prestations.length > 0)
  
  // Clear appointment-related state
  prestationsNeedingAppointments.value = []
  
  // Proceed with prescription creation
  proceedWithPrescriptionCreation()
}

const handleCancelAppointmentProcess = () => {
  showAppointmentAlert.value = false
  pendingConventionData.value = null
  prestationsNeedingAppointments.value = []
}

const onSameDayAppointmentBooked = (appointment) => {
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Appointment booked successfully!',
    life: 3000
  })
  
  // Close the same day modal
  showSameDayModal.value = false
  
  // Remove the prestation that was booked from the pending list
  removeBookedPrestationFromPending(currentPrestationForAppointment.value)
  
  // Continue with next prestation or remaining items
  continueAppointmentProcess()
}

const onAddedToWaitingList = (waitListEntry) => {
  toast.add({
    severity: 'info',
    summary: 'Added to Waiting List',
    detail: 'You have been added to the waiting list',
    life: 3000
  })
  
  // Close the same day modal
  showSameDayModal.value = false
  
  // Remove the prestation that was added to waitlist from pending list
  removeBookedPrestationFromPending(currentPrestationForAppointment.value)
  
  // Continue with next prestation or remaining items
  continueAppointmentProcess()
}

const removeBookedPrestationFromPending = (prestation) => {
  console.log('=== removeBookedPrestationFromPending called ===')
  console.log('Removing prestation:', prestation)
  
  if (!prestation) return
  
  // Remove from prestations needing appointments
  const prestationId = prestation.prestation_id || prestation.id
  prestationsNeedingAppointments.value = prestationsNeedingAppointments.value.filter(
    p => (p.prestation_id || p.id) !== prestationId
  )
  
  console.log('Updated prestationsNeedingAppointments:', prestationsNeedingAppointments.value.length)
  
  // Also remove from completed conventions if the prestation requires appointment
  // This prevents it from being included in the final prescription if no appointment was booked
  // Note: Only remove if you want to exclude prestations without appointments
  // Comment out the below code if you want to keep all prestations regardless of appointment status
  
  /*
  completedConventions.value.forEach(convention => {
    const originalLength = convention.prestations.length
    convention.prestations = convention.prestations.filter(p => 
      (p.prestation_id || p.id) !== prestationId
    )
    if (convention.prestations.length !== originalLength) {
      convention.totalPrestations = convention.prestations.length
      console.log(`Removed prestation from convention ${convention.convention.name}`)
    }
  })
  
  // Remove conventions that have no prestations left
  completedConventions.value = completedConventions.value.filter(conv => conv.prestations.length > 0)
  */
}

const continueAppointmentProcess = () => {
  console.log('=== continueAppointmentProcess called ===')
  console.log('Remaining prestations needing appointments:', prestationsNeedingAppointments.value.length)
  
  // Check if there are more prestations needing appointments
  if (prestationsNeedingAppointments.value.length > 0) {
    const prestation = prestationsNeedingAppointments.value[0]
    
    // Find the convention that contains this prestation to get doctor info
    let doctorId = selectedDoctor.value
    let specializationId = selectedSpecialization.value
    
    // Look through completed conventions to find the right doctor for this prestation
    for (const convention of completedConventions.value) {
      const found = convention.prestations.find(p => 
        (p.prestation_id || p.id) === (prestation.prestation_id || prestation.id)
      )
      if (found) {
        doctorId = convention.doctor.id
        specializationId = convention.specialization.id
        break
      }
    }
    
    // Enhance the prestation object with doctor info
    currentPrestationForAppointment.value = {
      ...prestation,
      doctorId: doctorId,
      specializationId: specializationId
    }
    
    console.log('Setting up next appointment modal with:', currentPrestationForAppointment.value)
    showSameDayModal.value = true
  } else {
    // No more prestations need appointments - proceed with prescription creation
    console.log('No more appointments needed, proceeding with prescription creation')
    prestationsNeedingAppointments.value = []
    currentPrestationForAppointment.value = null
    
    // Proceed with prescription creation
    proceedWithPrescriptionCreation()
  }
}

// Extract the actual convention creation logic
const proceedWithConventionCreation = () => {
  if (!pendingConventionData.value) {
    // Direct call - use current data
    const selectedSpecializationObj = specializations.value.find(s => s.id === selectedSpecialization.value)
    const selectedDoctorObj = allDoctors.value.find(d => d.id === selectedDoctor.value)
    const selectedConventionObj = filteredConventions.value.find(c => c.id === currentConvention.value)
    const selectedOrganismeObj = organismes.value.find(o => o.id === currentOrganisme.value)
    
    createConventionWithData(selectedOrganismeObj, selectedConventionObj, selectedSpecializationObj, selectedDoctorObj, currentPrestations.value)
  } else {
    // Called after appointment processing - use pending data
    createConventionWithData(
      pendingConventionData.value.selectedOrganismeObj,
      pendingConventionData.value.selectedConventionObj,
      pendingConventionData.value.selectedSpecializationObj,
      pendingConventionData.value.selectedDoctorObj,
      currentPrestations.value
    )
  }
}

const createConventionWithData = (organismeObj, conventionObj, specializationObj, doctorObj, prestations) => {
  const existingIndex = completedConventions.value.findIndex(conv => conv.convention.id === conventionObj.id);
  
  if (existingIndex !== -1) {
    const existingConvention = completedConventions.value[existingIndex];
    const existingPrestationIds = new Set(existingConvention.prestations.map(p => p.prestation_id));
    
    const newUniquePrestations = prestations.filter(
      p => !existingPrestationIds.has(p.prestation_id || p.id)
    );

    if (newUniquePrestations.length > 0) {
      existingConvention.prestations.push(...newUniquePrestations);
      existingConvention.totalPrestations = existingConvention.prestations.length;
      toast.add({
        severity: 'success',
        summary: 'Prestations Added',
        detail: `Added ${newUniquePrestations.length} new prestation(s) to existing convention.`,
        life: 3000
      });
    } else {
      toast.add({
        severity: 'info',
        summary: 'No new prestations added',
        detail: 'All selected prestations are already in this convention.',
        life: 3000
      });
    }
  } else {
    const newConventionGroup = {
      id: Date.now(),
      priseEnChargeDate: priseEnChargeDate.value,
      selectedFamilyAuth: selectedFamilyAuth.value,
      selectedAdherentPatient: selectedAdherentPatient.value,
      organisme: organismeObj,
      convention: conventionObj,
      prestations: [...prestations],
      specialization: specializationObj,
      doctor: doctorObj,
      addedAt: new Date(),
      totalPrestations: prestations.length
    }
    completedConventions.value.push(newConventionGroup)
    toast.add({
      severity: 'success',
      summary: 'Convention Added',
      detail: `Added new convention with ${newConventionGroup.totalPrestations} prestations.`,
      life: 3000
    })
  }

  // Reset fields for Step 2 only
  resetStep2Fields();
}

// Computed for other items count (for the alert modal)
const otherItemsCount = computed(() => {
  if (!pendingConventionData.value) return 0
  return pendingConventionData.value.prestationsWithoutAppointments.length
})

// REMOVE the old blocking logic from the original addConvention method
// The old method had:
// if (hasNeedAppointment) {
//   toast.add({
//     severity: 'error',
//     summary: 'Cannot Add',
//     detail: 'One or more selected prestations require an appointment...',
//   });
//   return;
// }

// Add these missing methods that are referenced but not defined:

const nextStep = () => {
  if (activeStep.value < 2) {
    activeStep.value++
  }
}

const prevStep = () => {
  if (activeStep.value > 0) {
    activeStep.value--
  }
}

const onDoctorChange = () => {
  // Add any logic needed when doctor changes
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return 'N/A'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const onFileSelect = (event) => {
  const files = event.files
  files.forEach(file => {
    uploadedFiles.value.push({
      id: Date.now() + Math.random(),
      name: file.name,
      size: file.size,
      file: file
    })
  })
}

const removeFile = (fileId) => {
  uploadedFiles.value = uploadedFiles.value.filter(file => file.id !== fileId)
}

const removeConvention = (conventionId) => {
  completedConventions.value = completedConventions.value.filter(conv => conv.id !== conventionId)
}

const removePrestationFromConvention = (conventionId, prestationId) => {
  const convention = completedConventions.value.find(conv => conv.id === conventionId)
  if (convention) {
    convention.prestations = convention.prestations.filter(p => p.prestation_id !== prestationId)
    convention.totalPrestations = convention.prestations.length
    
    // Remove entire convention if no prestations left
    if (convention.prestations.length === 0) {
      removeConvention(conventionId)
    }
  }
}

const resetStep2Fields = () => {
  currentOrganisme.value = null
  currentConvention.value = null
  currentPrestations.value = []
  selectedSpecialization.value = null
  selectedDoctor.value = null
  conventionPrestations.value = []
  filteredConventions.value = []
}

const resetAll = () => {
  activeStep.value = 0
  priseEnChargeDate.value = null
  selectedFamilyAuth.value = null
  selectedAdherentPatient.value = null
  completedConventions.value = []
  uploadedFiles.value = []
  resetStep2Fields()
  
  // Reset appointment-related state
  showAppointmentAlert.value = false
  showSameDayModal.value = false
  prestationsNeedingAppointments.value = []
  currentPrestationForAppointment.value = null
  pendingConventionData.value = null
}

const createConventionPrescription = async () => {
  console.log('=== createConventionPrescription called ===')
  console.log('Completed conventions:', completedConventions.value)
  
  // Check if any prestations in completed conventions require appointments
  const allPrestationsNeedingAppointments = []
  
  completedConventions.value.forEach(convention => {
    const prestationsWithAppointments = convention.prestations.filter(
      p => p.need_an_appointment === true
    )
    console.log(`Convention ${convention.convention.name} has ${prestationsWithAppointments.length} prestations needing appointments`)
    allPrestationsNeedingAppointments.push(...prestationsWithAppointments)
  })
  
  console.log('Total prestations needing appointments:', allPrestationsNeedingAppointments.length)
  
  // If there are prestations needing appointments, show the alert
  if (allPrestationsNeedingAppointments.length > 0) {
    prestationsNeedingAppointments.value = allPrestationsNeedingAppointments
    showAppointmentAlert.value = true
    return
  }
  
  // If no appointments needed, proceed with creation
  await proceedWithPrescriptionCreation()
}

const proceedWithPrescriptionCreation = async () => {
  console.log('=== proceedWithPrescriptionCreation called ===')
  console.log('Creating prescription with completed conventions:', completedConventions.value)
  
  if (completedConventions.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'No conventions to create prescription with',
      life: 3000
    })
    return
  }
  
  creating.value = true
  try {
    const formData = new FormData()

    // Append conventions as JSON string
    const conventionsData = completedConventions.value.map(conv => ({
      convention_id: conv.convention.id,
      doctor_id: conv.doctor.id,
      prestations: conv.prestations.map(p => ({
        prestation_id: p.prestation_id || p.id,
        doctor_id: conv.doctor.id,
        convention_price: p.convention_price || p.price
      }))
    }))
    
    console.log('Conventions data to send:', conventionsData)
    formData.append('conventions', JSON.stringify(conventionsData))

    // Append other fields
    formData.append('prise_en_charge_date', formatDateForApi(priseEnChargeDate.value))
    formData.append('familyAuth', selectedFamilyAuth.value)
    if (selectedAdherentPatient.value?.id) {
      formData.append('adherentPatient_id', selectedAdherentPatient.value.id)
    }

    // Append files
    uploadedFiles.value.forEach((file, index) => {
      formData.append(`uploadedFiles[${index}]`, file.file)
    })

    console.log('Sending prescription creation request...')
    const response = await ficheNavetteService.createConventionPrescription(props.ficheNavetteId, formData)
    console.log('Prescription creation response:', response)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Convention prescription created successfully!',
        life: 3000
      })
      
      // Emit success and close modal
      emit('convention-items-added')
      emit('update:visible', false)
      resetAll()
    } else {
      throw new Error(response.message || 'Failed to create prescription')
    }
  } catch (error) {
    console.error('Error creating convention prescription:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to create convention prescription',
      life: 3000
    })
  } finally {
    creating.value = false
  }
}

// Initialize data when component mounts
onMounted(async () => {
  await Promise.all([
    loadOrganismes(),
    loadConventions(),
    loadSpecializations(),
    loadAllDoctors()
  ])
})

// FIXED: Add the correct watch for date changes
watch(priseEnChargeDate, async (newDate) => {
  console.log('Date changed:', newDate) // Debug log
  await onDateChanged()
}, { immediate: false })

// Watch for family auth changes
watch(selectedFamilyAuth, () => {
  onFamilyAuthChanged()
})
</script>

<template>
  <Dialog
    v-model:visible="visibleModal"
    modal
    header="Convention Prescription"
    :style="{ width: '90vw', maxWidth: '1400px' }"
    :maximizable="true"
    :closable="true"
    class="convention-modal"
    @hide="resetAll"
  >
    <div class="modal-content">
      <Stepper v-model:activeStep="activeStep" class="custom-stepper">
        <StepperPanel header="Setup & Authorization">
          <template #content>
            <div class="step-content">
              <Card class="step-card">
                <template #header>
                  <div class="step-header">
                    <i class="pi pi-cog text-primary"></i>
                    <h4>Step 1: Setup Date, Authorization & Patient</h4>
                  </div>
                </template>

                <template #content>
                  <div class="combined-setup">
                    <p class="section-description">
                      Choose the date, select the family authorization type, and if needed, the adherent patient.
                    </p>

                    <div class="setup-grid">
                      <div class="setup-field">
                        <label>Prise en Charge Date *</label>
                        <Calendar
                          v-model="priseEnChargeDate"
                          dateFormat="dd/mm/yy"
                          :showIcon="true"
                          placeholder="Select date"
                          class="date-input"
                        />
                      </div>

                      <div class="setup-field">
                        <label>Family Authorization *</label>
                        <Dropdown
                          v-model="selectedFamilyAuth"
                          :options="familyAuthOptions"
                          optionLabel="label"
                          optionValue="value"
                          placeholder="Select authorization"
                          :loading="familyAuthLoading"
                          :disabled="!priseEnChargeDate"
                          class="auth-multiselect"
                          :class="{ 'p-invalid': !selectedFamilyAuth && familyAuthOptions.length }"
                          showClear
                        >
                          <template #empty>
                            <span v-if="!priseEnChargeDate">Select a date first</span>
                            <span v-else-if="familyAuthLoading">Loading options...</span>
                            <span v-else>No authorization options available</span>
                          </template>
                        </Dropdown>
                        <small v-if="!priseEnChargeDate" class="field-help text-muted">
                          Select a date first to see available authorizations
                        </small>
                      </div>

                      <div v-if="requiresAdherentPatient" class="setup-field">
                        <label>Adherent Patient *</label>
                        <PatientSearch
                          v-model="adherentPatientSearch"
                          @patientSelected="onAdherentPatientSelected"
                          placeholder="Search and select adherent patient..."
                          class="patient-search"
                        />
                      </div>
                    </div>

                    <div class="step-progress">
                      <Divider />

                      <div class="progress-indicators">
                        <div class="progress-item" :class="{ 'completed': priseEnChargeDate }">
                          <i class="pi pi-calendar"></i>
                          <span>Date Selected</span>
                        </div>
                        <div class="progress-item" :class="{ 'completed': selectedFamilyAuth }">
                          <i class="pi pi-users"></i>
                          <span>Authorization Set</span>
                        </div>
                        <div
                          v-if="requiresAdherentPatient"
                          class="progress-item"
                          :class="{ 'completed': selectedAdherentPatient }"
                        >
                          <i class="pi pi-user"></i>
                          <span>Patient Selected</span>
                        </div>
                      </div>

                      <div class="step-navigation">
                        <Button
                          label="Proceed to Conventions"
                          icon="pi pi-arrow-right"
                          @click="nextStep"
                          :disabled="!canProceedToConventions"
                          class="proceed-btn"
                        />
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </template>
        </StepperPanel>

        <StepperPanel header="Add Conventions">
          <template #content>
            <div class="step-content">
              <Card class="step-card">
                <template #header>
                  <div class="step-header">
                    <i class="pi pi-building text-primary"></i>
                    <h4>Step 2: Select Conventions, Specialization & Doctor</h4>
                  </div>
                </template>

                <template #content>
                  <div class="convention-selection">
                    <div v-if="!canProceedToConventions" class="prerequisites-warning">
                      <Message severity="warn" :closable="false">
                        <i class="pi pi-exclamation-triangle"></i>
                        Please complete all setup requirements in Step 1 before selecting conventions
                      </Message>
                    </div>

                    <div v-else>
                      <p class="step-description">
                        Select conventions and prestations for <strong>{{ formatDate(priseEnChargeDate) }}</strong>.
                        You must select specialization and doctor to filter prestations properly.
                      </p>

                      <div class="selection-grid">
                        <div class="field-group">
                          <label>Companies</label>
                          <Dropdown
                            v-model="currentOrganisme"
                            :options="organismes"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select company..."
                            @change="onOrganismeChange"
                            class="field-input"
                            showClear
                            filter
                            filterPlaceholder="Search companies..."
                          />
                        </div>

                        <div class="field-group">
                          <label>Conventions</label>
                          <Dropdown
                            v-model="currentConvention"
                            :options="filteredConventions"
                            optionLabel="contract_name"
                            optionValue="id"
                            placeholder="Select convention..."
                            :disabled="!currentOrganisme"
                            @change="onConventionChange"
                            class="field-input"
                            showClear
                            filter
                            filterPlaceholder="Search conventions..."
                          />
                        </div>

                        <div class="field-group">
                          <label>Specialization *</label>
                          <Dropdown
                            v-model="selectedSpecialization"
                            :options="specializations"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select specialization"
                            :disabled="currentConvention === null"
                            @change="onSpecializationChange"
                            class="field-input"
                            :loading="specializationLoading"
                          />
                          <small v-if="currentConvention === null" class="field-help">
                            Select conventions first
                          </small>
                        </div>

                        <div class="field-group">
                          <label>Doctor *</label>
                          <Dropdown
                            v-model="selectedDoctor"
                            :options="filteredDoctors"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select doctor"
                            :disabled="!selectedSpecialization"
                            @change="onDoctorChange"
                            class="field-input"
                            :loading="doctorLoading"
                          >
                            <template #option="{ option }">
                              <div class="doctor-option">
                                <span class="doctor-name">{{ option.name }}</span>
                                <small class="doctor-specialization">{{ option.specialization_name }}</small>
                              </div>
                            </template>
                          </Dropdown>
                          <small v-if="!selectedSpecialization" class="field-help">
                            Select specialization first
                          </small>
                          <small v-else-if="filteredDoctors.length === 0" class="field-help text-orange-600">
                            No doctors found for selected specialization
                          </small>
                          <small v-else class="field-help text-green-600">
                            {{ filteredDoctors.length }} doctor(s) available
                          </small>
                        </div>

                        <div class="field-group full-width">
                          <label>Prestations ({{ filteredConventionPrestations.length }} available)</label>
                          <MultiSelect
                            v-model="currentPrestations"
                            :options="filteredConventionPrestations"
                            optionLabel="prestation_name"
                            dataKey="prestation_id"
                            placeholder="Select prestations..."
                            :disabled="!selectedDoctor"
                            :loading="loading"
                            class="field-input"
                            :filter="true"
                            filterPlaceholder="Search prestations..."
                            display="chip"
                            :showToggleAll="true"
                          >
                            <template #option="{ option }">
                              <div class="prestation-option">
                                <div class="prestation-info">
                                  <div class="prestation-header">
                                    <strong class="prestation-title">{{ option.prestation_name || option.name }}</strong>
                                    <Tag
                                      v-if="option.need_an_appointment"
                                      value="Appointment Required"
                                      severity="warning"
                                      size="small"
                                      class="ml-2"
                                    />
                                    <Tag
                                      v-else
                                      value="No Appointment Needed"
                                      severity="success"
                                      size="small"
                                      class="ml-2"
                                    />
                                    <Tag
                                      v-if="option.specialization_name && option.specialization_name !== 'Unknown'"
                                      :value="option.specialization_name"
                                      severity="info"
                                      size="small"
                                      class="ml-2"
                                    />
                                  </div>
                                  <small class="prestation-code">Code: {{ option.prestation_code || option.internal_code }}</small>
                                  <small class="prestation-service">Service: {{ option.service_name }}</small>
                                </div>
                                <div class="prestation-pricing">
                                  <span class="prestation-price">{{ formatCurrency(option.convention_price || option.price) }}</span>
                                  <small v-if="option.base_price && option.convention_price !== option.base_price" class="original-price">
                                    Original: {{ formatCurrency(option.base_price) }}
                                  </small>
                                </div>
                              </div>
                            </template>
                            
                            <template #value="{ value, placeholder }">
                              <template v-if="!value || value.length === 0">
                                <span class="multiselect-placeholder">{{ placeholder }}</span>
                              </template>
                              <template v-else>
                                <div class="multiselect-chips">
                                  <span v-for="prestation in value" :key="prestation.prestation_id" class="prestation-chip">
                                    {{ prestation.prestation_name || prestation.name }}
                                  </span>
                                </div>
                              </template>
                            </template>
                            
                            <template #empty>
                              <div class="empty-state">
                                <span v-if="!selectedDoctor">
                                  <i class="pi pi-info-circle"></i>
                                  Select doctor first to see prestations
                                </span>
                                <span v-else-if="loading">
                                  <i class="pi pi-spin pi-spinner"></i>
                                  Loading prestations...
                                </span>
                                <span v-else-if="!selectedSpecialization">
                                  <i class="pi pi-info-circle"></i>
                                  Select specialization first
                                </span>
                                <span v-else-if="conventionPrestations.length === 0">
                                  <i class="pi pi-exclamation-triangle"></i>
                                  No prestations available for this convention
                                </span>
                                <span v-else>
                                  <i class="pi pi-exclamation-triangle"></i>
                                  No prestations found for selected specialization
                                </span>
                              </div>
                            </template>
                          </MultiSelect>
                          
                          <div class="field-help-section">
                            <small v-if="!selectedDoctor" class="field-help">
                              <i class="pi pi-arrow-up"></i>
                              Select doctor to see filtered prestations
                            </small>
                            <small v-else-if="loading" class="field-help text-blue-600">
                              <i class="pi pi-spin pi-spinner"></i>
                              Loading prestations...
                            </small>
                            <small v-else-if="conventionPrestations.length === 0" class="field-help text-orange-600">
                              <i class="pi pi-exclamation-triangle"></i>
                              No prestations found for selected convention
                            </small>
                            <small v-else-if="filteredConventionPrestations.length === 0" class="field-help text-orange-600">
                              <i class="pi pi-exclamation-triangle"></i>
                              No prestations found for selected specialization "{{ specializations.find(s => s.id === selectedSpecialization)?.name }}"
                            </small>
                            <small v-else class="field-help text-green-600">
                              <i class="pi pi-check-circle"></i>
                              {{ filteredConventionPrestations.length }} prestations available for "{{ specializations.find(s => s.id === selectedSpecialization)?.name }}"
                            </small>
                          </div>
                        </div>

                        <div class="field-group add-button-group">
                          <Button
                            label="Add Convention"
                            icon="pi pi-plus"
                            @click="addConvention"
                            :disabled="!canAddConvention"
                            class="add-convention-btn"
                          />
                          <small v-if="!canAddConvention" class="field-help text-orange-600">
                            Complete all fields to add convention
                          </small>
                        </div>
                      </div>

                      <div v-if="completedConventions.length > 0" class="completed-conventions">
                        <h5>Added Conventions</h5>
                        <div class="convention-list">
                          <div
                            v-for="(convention, conventionIndex) in completedConventions"
                            :key="convention.id"
                            class="convention-item expanded"
                          >
                            <div class="convention-header">
                              <div class="convention-info">
                                <div class="convention-title">
                                  <strong>{{ convention.organisme.name }}</strong>
                                  <small class="convention-subtitle">{{ convention.convention.contract_name }}</small>
                                </div>
                                <div class="convention-details">
                                  <Tag :value="`${convention.totalPrestations} prestations`" severity="success" />
                                  <Tag :value="convention.specialization.name" severity="warning" />
                                  <Tag :value="`Dr. ${convention.doctor.name}`" severity="help" />
                                  <Tag :value="convention.selectedFamilyAuth" severity="info" />
                                  <Tag :value="formatDate(convention.priseEnChargeDate)" severity="secondary" />
                                </div>
                              </div>
                              <Button
                                icon="pi pi-times"
                                class="p-button-text p-button-danger p-button-sm"
                                @click="removeConvention(convention.id)"
                                title="Remove entire convention"
                              />
                            </div>

                            <div class="prestations-list">
                              <h6>Prestations:</h6>
                              <div class="prestations-grid">
                                <div
                                  v-for="prestation in convention.prestations"
                                  :key="prestation.prestation_id"
                                  class="prestation-item"
                                >
                                  <div class="prestation-info">
                                    <div class="prestation-header">
                                      <strong class="prestation-title">{{ prestation.name }}</strong>
                                      <Tag
                                        v-if="prestation.need_an_appointment"
                                        value="Appointment Required"
                                        severity="danger"
                                        size="small"
                                        class="ml-2"
                                      />
                                      <Tag
                                        v-if="prestation.specialization && prestation.specialization !== 'Unknown'"
                                        :value="prestation.specialization"
                                        severity="info"
                                        size="small"
                                        class="ml-2"
                                      />
                                    </div>
                                    <small class="prestation-code">Code: {{ prestation.prestation_code || prestation.internal_code }}</small>
                                  </div>
                                  <div class="prestation-actions">
                                    <span class="prestation-price">{{ formatCurrency(prestation.convention_price || prestation.price) }}</span>
                                    <Button
                                      icon="pi pi-times"
                                      class="p-button-text p-button-danger p-button-sm"
                                      @click="removePrestationFromConvention(convention.id, prestation.prestation_id)"
                                      title="Remove this prestation"
                                    />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="step-navigation">
                        <Button
                          label="Back to Setup"
                          icon="pi pi-arrow-left"
                          @click="prevStep"
                          outlined
                        />
                        <Button
                          label="Done, Proceed to Files"
                          icon="pi pi-arrow-right"
                          @click="nextStep"
                          :disabled="completedConventions.length === 0"
                          class="proceed-btn"
                        />
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </template>
        </StepperPanel>

        <StepperPanel header="Files & Create">
          <template #content>
            <div class="step-content">
              <Card class="step-card">
                <template #header>
                  <div class="step-header">
                    <i class="pi pi-file text-primary"></i>
                    <h4>Step 3: Upload Files & Create Prescription</h4>
                  </div>
                </template>

                <template #content>
                  <div class="final-step">
                    <div class="file-upload-section">
                      <h5>Upload Documents (Optional)</h5>
                      <FileUpload
                        mode="basic"
                        :multiple="true"
                        accept=".pdf,.doc,.docx"
                        @select="onFileSelect"
                        chooseLabel="Choose Files"
                        class="file-upload"
                      />
                      
                      <div v-if="uploadedFiles.length > 0" class="uploaded-files">
                        <div
                          v-for="file in uploadedFiles"
                          :key="file.id"
                          class="uploaded-file-item"
                        >
                          <div class="file-info">
                            <i class="pi pi-file text-primary"></i>
                            <span class="file-name">{{ file.name }}</span>
                            <small class="file-size">({{ formatFileSize(file.size) }})</small>
                          </div>
                          <Button
                            icon="pi pi-times"
                            class="p-button-text p-button-sm p-button-danger"
                            @click="removeFile(file.id)"
                          />
                        </div>
                      </div>
                    </div>

                    <Divider />

                    <div class="prescription-summary">
                      <h5>Prescription Summary</h5>
                      <div class="summary-grid">
                        <div class="summary-section">
                          <h6>Summary of All Added Conventions</h6>
                          <div class="summary-item">
                            <strong>Total Conventions Added:</strong> {{ completedConventions.length }}
                          </div>
                          <div class="summary-item">
                            <strong>Total Prestations:</strong> {{ completedConventions.reduce((sum, conv) => sum + conv.totalPrestations, 0) }}
                          </div>
                          <div class="summary-item">
                            <strong>Files to Upload:</strong> {{ uploadedFiles.length }}
                          </div>
                        </div>

                        <div class="summary-section">
                          <h6>Details of Each Convention</h6>
                          <div class="summary-list">
                            <div v-for="conv in completedConventions" :key="conv.id" class="summary-sub-item">
                                <strong>{{ conv.organisme.name }} - {{ conv.convention.contract_name }}</strong>
                                <br/>
                                <small>Date: {{ formatDate(conv.priseEnChargeDate) }} | Doctor: {{ conv.doctor.name }} | Prestations: {{ conv.totalPrestations }}</small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="final-actions">
                      <Button
                        label="Back to Conventions"
                        icon="pi pi-arrow-left"
                        @click="prevStep"
                        outlined
                      />

                      <Button
                        label="Create Convention Prescription"
                        icon="pi pi-save"
                        @click="createConventionPrescription"
                        :loading="creating"
                        :disabled="!canCreatePrescription"
                        class="create-prescription-btn"
                        severity="success"
                        size="large"
                      />
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </template>
        </StepperPanel>
      </Stepper>
    </div>

    <!-- Appointment Required Alert -->
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
    
    <!-- Same Day Appointment Modal -->
   <SameDayAppointmentModal
  v-if="showSameDayModal"
  :visible="showSameDayModal"
  :doctor-id="currentPrestationForAppointment?.doctorId || selectedDoctor"
  :patient-id="selectedAdherentPatient?.id || selectedAdherentPatient?.patient_id"
  :prestation-id="currentPrestationForAppointment?.prestation_id || currentPrestationForAppointment?.id"
  :doctor-specialization-id="currentPrestationForAppointment?.specializationId || selectedSpecialization"
  @update:visible="showSameDayModal = $event"
  @appointment-booked="onSameDayAppointmentBooked"
  @added-to-waitlist="onAddedToWaitingList"
/>
  </Dialog>
</template>

<style scoped>
/* General Reset and Base Styles */
.convention-modal {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: #f8fafc;
  border-radius: 12px;
  overflow: hidden;
}

.modal-content {
  padding: 1.5rem;
}

/* Stepper Styling */
.custom-stepper {
  margin-bottom: 1rem;
}

.custom-stepper .p-stepper-nav {
  background: #ffffff;
  border-radius: 8px;
  padding: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.custom-stepper .p-stepper-panel {
  background: transparent;
}

/* Step Card */
.step-card {
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  background: #ffffff;
  overflow: hidden;
}

.step-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
}

.step-header h4 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
}

.step-header .pi {
  font-size: 1.5rem;
  color: #3b82f6;
}

/* Step Content */
.step-content {
  padding: 1.5rem;
}

.section-description {
  color: #64748b;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

/* Setup Grid (Step 1) */
.setup-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.setup-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.setup-field label {
  font-weight: 500;
  color: #1e293b;
  font-size: 0.95rem;
}

.date-input,
.auth-multiselect,
.patient-search {
  width: 100%;
}

.field-help {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.25rem;
}

/* Progress Indicators */
.step-progress {
  margin-top: 2rem;
}

.progress-indicators {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.progress-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #94a3b8;
  font-size: 0.9rem;
}

.progress-item.completed {
  color: #22c55e;
}

.progress-item .pi {
  font-size: 1rem;
}

/* Step Navigation */
.step-navigation {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.proceed-btn {
  background: #3b82f6;
  border: none;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  transition: background 0.2s;
}

.proceed-btn:hover {
  background: #2563eb;
}

/* Convention Selection (Step 2) */
.convention-selection {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.prerequisites-warning {
  margin-bottom: 1rem;
}

.selection-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-group label {
  font-weight: 500;
  color: #1e293b;
  font-size: 0.95rem;
}

.field-input {
  width: 100%;
}

.add-button-group {
  align-self: flex-end;
}

.add-convention-btn {
  background: #22c55e;
  border: none;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  transition: background 0.2s;
}

.add-convention-btn:hover {
  background: #16a34a;
}

/* Completed Conventions */
.completed-conventions {
  margin-top: 2rem;
}

.convention-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.convention-item {
  background: #ffffff;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.convention-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.convention-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.convention-title {
  font-weight: 600;
  color: #1e293b;
}

.convention-subtitle {
  font-size: 0.9rem;
  color: #64748b;
}

.convention-details {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.prestations-list {
  margin-top: 1rem;
}

.prestations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.prestation-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 6px;
}

.prestation-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.prestation-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.prestation-title {
  font-weight: 500;
  color: #1e293b;
}

.prestation-code {
  font-size: 0.8rem;
  color: #64748b;
}

.prestation-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.prestation-price {
  font-weight: 500;
  color: #22c55e;
}

/* File Upload (Step 3) */
.file-upload-section {
  margin-bottom: 2rem;
}

.uploaded-files {
  margin-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.uploaded-file-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 6px;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.file-name {
  font-weight: 500;
  color: #1e293b;
}

.file-size {
  font-size: 0.8rem;
  color: #64748b;
}

/* Prescription Summary */
.prescription-summary {
  margin-bottom: 2rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.summary-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-item,
.summary-sub-item {
  font-size: 0.9rem;
  color: #1e293b;
}

.summary-sub-item small {
  color: #64748b;
}

/* Final Actions */
.final-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.create-prescription-btn {
  padding: 0.75rem 2rem;
  font-size: 1.1rem;
  font-weight: 600;
}

/* PrimeVue Component Overrides */
:deep(.p-dialog-header) {
  background: #3b82f6;
  color: #ffffff;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
  padding: 1rem 1.5rem;
}

:deep(.p-dialog-header-icon) {
  color: #ffffff;
}

:deep(.p-inputtext),
:deep(.p-dropdown),
:deep(.p-multiselect) {
  border-radius: 6px;
  border: 1px solid #d1d5db;
  transition: border-color 0.2s, box-shadow 0.2s;
}

:deep(.p-inputtext:focus),
:deep(.p-dropdown:focus),
:deep(.p-multiselect:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

:deep(.p-button) {
  border-radius: 6px;
}

:deep(.p-button-outlined) {
  border-color: #d1d5db;
  color: #1e293b;
}

:deep(.p-button-outlined:hover) {
  background: #f1f5f9;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .convention-modal {
    width: 95vw;
  }

  .setup-grid,
  .selection-grid,
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .step-navigation,
  .final-actions {
    flex-direction: column;
  }

  .proceed-btn,
  .create-prescription-btn {
    width: 100%;
  }
}
</style>