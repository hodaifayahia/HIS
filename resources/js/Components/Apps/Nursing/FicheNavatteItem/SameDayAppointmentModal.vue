<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import addWaitlistModel from '../../../waitList/addWaitlistModel.vue'
import appointmentForm from '../../../appointments/AppointmentModal.vue'
import { useToastr } from '../../../toster'
import { appointmentService } from '../../services/Appointment/appointmentService'

const toastr = useToastr()

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  doctorId: {
    type: Number,
    required: true
  },
  fuckuifwork: {
    type: Object,
    required: false,
    default: () => ({})
  },
  patientId: {
    type: Number,
    required: true
  },
  prestationId: {
    type: Number,
    required: false
  },
  doctorSpecializationId: {
    type: Number,
    required: false
  }
})

const emit = defineEmits(['update:visible', 'appointment-booked', 'added-to-waitlist'])

// Reactive data
const loading = ref(false)
const bookingAppointment = ref(false)
const availabilityData = ref({})
const selectedSlot = ref(null)
const showWaitlistModal = ref(false)
const showAppointmentFormModal = ref(false)
const isMounted = ref(false)

// Computed
const modalTitle = computed(() => {
  if (availabilityData.value.is_available && availabilityData.value.available_slots && availabilityData.value.available_slots.length > 0) {
    return 'Same-Day Appointment Options'
  }
  return 'Appointment Options'
})

// Extract values from fuckuifwork prop (fallback data)
const extractedDoctorId = computed(() => {
  // Priority 1: Use direct doctorId prop if it exists and is valid
  if (props.doctorId && props.doctorId !== null && props.doctorId !== undefined) {
    console.log('Using direct doctorId:', props.doctorId)
    return props.doctorId
  }

  // Priority 2: Extract from fuckuifwork fallback object
  const fallbackDoctorId = props.fuckuifwork?.appointmentItems?.otherItems?.selectedDoctor ||
                          props.fuckuifwork?.otherItems?.selectedDoctor ||
                          props.fuckuifwork?.selectedDoctor

  if (fallbackDoctorId && fallbackDoctorId !== null && fallbackDoctorId !== undefined) {
    console.log('Using fallback doctorId from fuckuifwork:', fallbackDoctorId)
    return fallbackDoctorId
  }

  console.log('No valid doctorId found')
  return null
})

const extractedPrestationId = computed(() => {
  // Priority 1: Use direct prestationId prop if it exists and is valid
  if (props.prestationId && props.prestationId !== null && props.prestationId !== undefined) {
    console.log('Using direct prestationId:', props.prestationId)
    return props.prestationId
  }

  // Priority 2: Extract from fuckuifwork fallback object
  const fallbackPrestationId = props.fuckuifwork?.appointmentItems?.appointmentItems?.[0]?.id ||
                               props.fuckuifwork?.appointmentItems?.otherItems?.prestationId ||
                               props.fuckuifwork?.prestationId

  if (fallbackPrestationId && fallbackPrestationId !== null && fallbackPrestationId !== undefined) {
    console.log('Using fallback prestationId from fuckuifwork:', fallbackPrestationId)
    return fallbackPrestationId
  }

  console.log('No valid prestationId found')
  return null
})

const extractedDoctorSpecializationId = computed(() => {
  // Priority 1: Use direct doctorSpecializationId prop if it exists and is valid
  if (props.doctorSpecializationId && props.doctorSpecializationId !== null && props.doctorSpecializationId !== undefined) {
    console.log('Using direct doctorSpecializationId:', props.doctorSpecializationId)
    return props.doctorSpecializationId
  }

  // Priority 2: Extract from fuckuifwork fallback object
  const fallbackSpecializationId = props.fuckuifwork?.appointmentItems?.otherItems?.selectedSpecialization ||
                                   props.fuckuifwork?.otherItems?.selectedSpecialization ||
                                   props.fuckuifwork?.selectedSpecialization

  if (fallbackSpecializationId && fallbackSpecializationId !== null && fallbackSpecializationId !== undefined) {
    console.log('Using fallback doctorSpecializationId from fuckuifwork:', fallbackSpecializationId)
    return fallbackSpecializationId
  }

  console.log('No valid doctorSpecializationId found')
  return null
})

const extractedPatientId = computed(() => {
  console.log('=== extractedPatientId computed ===')
  
  // Priority 1: Use direct patientId prop if it exists and is valid
  if (props.patientId && props.patientId !== null && props.patientId !== undefined) {
    console.log('Using direct patientId:', props.patientId)
    return props.patientId
  }

  // Priority 2: Extract from fuckuifwork fallback object
  const fallbackPatientId = props.fuckuifwork?.otherItems?.patientId ||
                            props.fuckuifwork?.patientId

  if (fallbackPatientId && fallbackPatientId !== null && fallbackPatientId !== undefined) {
    console.log('Using fallback patientId from fuckuifwork:', fallbackPatientId)
    return fallbackPatientId
  }

  console.log('No valid patientId found')
  return null
})

// Methods
const checkAvailability = async () => {
  if (!isMounted.value) return

  console.log('=== checkAvailability called ===')
  console.log('Extracted values:', {
    doctorId: extractedDoctorId.value,
    patientId: extractedPatientId.value,
    prestationId: extractedPrestationId.value,
    doctorSpecializationId: extractedDoctorSpecializationId.value
  })

  // Validate required parameters with improved error handling
  if (!extractedDoctorId.value) {
    console.error('Doctor ID is required but not found - checking fallback sources')
    console.log('Direct doctorId:', props.doctorId)
    console.log('Fallback fuckuifwork:', props.fuckuifwork)

    // Try to trigger a re-computation by accessing the computed properties
    const doctorFromFallback = extractedDoctorId.value

    if (!doctorFromFallback) {
      toastr.error('Doctor information is missing. Please select a doctor first.')
      return
    }
  }

  if (!extractedPatientId.value) {
    console.error('Patient ID is required but not found')
    toastr.error('Patient information is missing.')
    return
  }

  // Log which source we're using for each parameter
  console.log('=== Parameter Sources ===')
  console.log('Doctor ID source:', props.doctorId ? 'direct prop' : 'fallback (fuckuifwork)')
  console.log('Prestation ID source:', props.prestationId ? 'direct prop' : 'fallback (fuckuifwork)')
  console.log('Specialization ID source:', props.doctorSpecializationId ? 'direct prop' : 'fallback (fuckuifwork)')

  loading.value = true
  try {
    console.log('Calling appointmentService.checkSameDayAvailability...')
    const result = await appointmentService.checkSameDayAvailability({
      doctor_id: extractedDoctorId.value,
      prestation_id: extractedPrestationId.value,
      date: new Date().toISOString().split('T')[0] // today's date
    })

    console.log('Availability check result:', result)

    if (result.success) {
      if (isMounted.value) {
        availabilityData.value = result.data
      }
      console.log('Availability data set:', availabilityData.value)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error checking availability:', error)
    toastr.error(error.message || 'Failed to check availability')

    // Set default data so modal doesn't break
    if (isMounted.value) {
      availabilityData.value = {
        is_available: false,
        current_date: new Date().toISOString().split('T')[0],
        next_available_date: null,
        period: null,
        available_slots: []
      }
    }
  } finally {
    if (isMounted.value) {
      loading.value = false
    }
  }
}

const selectSlot = (slot) => {
  if (!isMounted.value) return
  
  // Create a proper slot object from the string
  const slotTime = typeof slot === 'string' ? slot : slot.time || slot
  const today = availabilityData.value.current_date || availabilityData.value.today || new Date().toISOString().split('T')[0]

  selectedSlot.value = {
    time: slotTime,
    datetime: `${today} ${slotTime}`
  }
  console.log('Selected slot:', selectedSlot.value)
}

const bookSameDayAppointment = async () => {
  if (!isMounted.value) return

  if (!selectedSlot.value) {
    toastr.error('Please select a time slot')
    return
  }

  console.log('=== Booking same-day appointment ===')
  console.log('Selected slot:', selectedSlot.value)
  console.log('Booking params:', {
    doctor_id: extractedDoctorId.value,
    patient_id: extractedPatientId.value,
    prestation_id: extractedPrestationId.value,
    appointment_time: selectedSlot.value.datetime
  })

  // Validate that we have all required parameters
  if (!extractedDoctorId.value) {
    toastr.error('Doctor information is missing. Cannot book appointment.')
    return
  }

  if (!extractedPatientId.value) {
    toastr.error('Patient information is missing. Cannot book appointment.')
    return
  }

  // Log parameter sources for debugging
  console.log('=== Booking Parameter Sources ===')
  console.log('Doctor ID:', extractedDoctorId.value, 'from:', props.doctorId ? 'direct prop' : 'fallback')
  console.log('Prestation ID:', extractedPrestationId.value, 'from:', props.prestationId ? 'direct prop' : 'fallback')

  bookingAppointment.value = true
  try {
    const result = await appointmentService.bookSameDayAppointment({
      doctor_id: extractedDoctorId.value,
      patient_id: extractedPatientId.value,
      prestation_id: extractedPrestationId.value,
      appointment_time: selectedSlot.value.datetime
    })

    console.log('Booking result:', result)

    if (result.success) {
      toastr.success(result.message || 'Same-day appointment booked successfully!')
      emit('appointment-booked', result.data)
      emit('update:visible', false)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error booking appointment:', error)
    toastr.error(error.message || 'Failed to book appointment')
  } finally {
    if (isMounted.value) {
      bookingAppointment.value = false
    }
  }
}

const showWaitListModal = () => {
  if (!isMounted.value) return
  console.log('Opening waitlist modal')
  showWaitlistModal.value = true
}

const closeWaitlistModal = () => {
  if (!isMounted.value) return
  showWaitlistModal.value = false
}

const onWaitlistSaved = (waitlistData) => {
  if (!isMounted.value) return
  console.log('Waitlist saved:', waitlistData)
  toastr.success('Added to waiting list successfully!')
  emit('added-to-waitlist', waitlistData)
  showWaitlistModal.value = false
  emit('update:visible', false)
}

const showAppointmentForm = () => {
  if (!isMounted.value) return
  console.log('Opening appointment form modal')
  showAppointmentFormModal.value = true
}

const closeAppointmentForm = () => {
  if (!isMounted.value) return
  showAppointmentFormModal.value = false
}

const onAppointmentSaved = (appointmentData) => {
  if (!isMounted.value) return
  console.log('Appointment saved:', appointmentData)
  toastr.success('Appointment booked successfully!')
  emit('appointment-booked', appointmentData)
  showAppointmentFormModal.value = false
  emit('update:visible', false)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateTimeString) => {
  return new Date(dateTimeString).toLocaleString('fr-FR', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Watchers
watch(() => props.visible, (newVisible) => {
  if (!isMounted.value) return
  console.log('=== Modal visibility changed ===', newVisible)
  if (newVisible) {
    selectedSlot.value = null
    showWaitlistModal.value = false
    showAppointmentFormModal.value = false
    availabilityData.value = {}
    
    // Call checkAvailability immediately if all required props are available
    if (extractedDoctorId.value && extractedPatientId.value) {
      console.log('All props available, calling checkAvailability immediately')
      checkAvailability()
    } else {
      console.log('Missing required props, waiting for prop changes')
      console.log('Current values:', {
        doctorId: extractedDoctorId.value,
        patientId: extractedPatientId.value,
        fuckuifwork: props.fuckuifwork
      })
    }
  }
})

// Watch for prop changes
watch(() => [extractedDoctorId.value, extractedPatientId.value], ([newDoctorId, newPatientId]) => {
  if (!isMounted.value) return
  console.log('=== Props changed ===', { doctorId: newDoctorId, patientId: newPatientId })
  if (props.visible && newDoctorId && newPatientId) {
    console.log('Modal is visible and props are set, calling checkAvailability')
    checkAvailability()
  } else if (props.visible) {
    console.log('Modal is visible but missing required props:', {
      doctorId: newDoctorId,
      patientId: newPatientId
    })
  }
})

// Watch for changes in fuckuifwork prop
watch(() => props.fuckuifwork, (newFuckuifwork, oldFuckuifwork) => {
  if (!isMounted.value) return
  console.log('=== fuckuifwork prop changed ===')
  console.log('Old fuckuifwork:', oldFuckuifwork)
  console.log('New fuckuifwork:', newFuckuifwork)
  console.log('New extractedDoctorId:', extractedDoctorId.value)
  
  if (props.visible && extractedDoctorId.value && extractedPatientId.value) {
    console.log('fuckuifwork changed and modal is visible with valid props, calling checkAvailability')
    checkAvailability()
  } else {
    console.log('fuckuifwork changed but conditions not met:', {
      visible: props.visible,
      doctorId: extractedDoctorId.value,
      patientId: extractedPatientId.value
    })
  }
}, { deep: true, immediate: false })

onMounted(() => {
  console.log('=== SameDayAppointmentModal mounted ===')
  console.log('Initial props:', props)
  isMounted.value = true
  
  if (props.visible && extractedDoctorId.value && extractedPatientId.value) {
    console.log('Modal is visible on mount, calling checkAvailability')
    checkAvailability()
  } else {
    console.log('Modal not visible or missing required props on mount')
  }
})

onUnmounted(() => {
  isMounted.value = false
})
</script>

<template>
  <Dialog
    :visible="visible"
    :header="modalTitle"
    modal
    class="same-day-appointment-modal"
    :style="{ width: '700px' }"
    @update:visible="$emit('update:visible', $event)"
  >

    <div class="same-day-content">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
        </div>
        <p>Checking availability...</p>
      </div>
     
      
      <!-- Main Content -->
      <div v-if="!loading && Object.keys(availabilityData).length > 0">
        <!-- Scenario 1: Doctor allows same-day appointments -->
        <div v-if="availabilityData.is_available" class="same-day-allowed">
          <div class="scenario-header">
            <i class="pi pi-calendar-plus text-green-500"></i>
            <h4>Same-Day Appointments Available</h4>
          </div>
          
          <!-- Available slots today -->
          <div v-if="availabilityData.available_slots && availabilityData.available_slots.length > 0" class="available-slots">
            <h5>Available Times Today ({{ formatDate(availabilityData.current_date || availabilityData.today) }})</h5>
            <div class="slots-grid">
              <Button
                v-for="slot in availabilityData.available_slots"
                :key="slot"
                :label="slot"
                class="text-dark"
                :class="['slot-button', { 'selected': selectedSlot?.time === slot }]"
                @click="selectSlot(slot)"
                :disabled="bookingAppointment"
              />
            </div>
            
            <div v-if="selectedSlot" class="selected-slot-info">
              <i class="pi pi-check-circle text-green-500"></i>
              <span>Selected: {{ formatDateTime(selectedSlot.datetime) }}</span>
            </div>
            
            <div class="slot-actions">
              <Button
                label="Book This Appointment"
                icon="pi pi-check"
                @click="bookSameDayAppointment"
                :loading="bookingAppointment"
                :disabled="!selectedSlot"
                class="p-button-success"
              />
            </div>
            
            <!-- Alternative Options -->
            <div class="alternatives-section">
              <h6>Or choose an alternative:</h6>
              <div class="alternatives">
                <Button
                  label="Join Today's Waiting List"
                  icon="pi pi-clock"
                  @click="showWaitListModal"
                  class="p-button-warning"
                  :disabled="bookingAppointment"
                />
                <Button
                  label="Book Future Appointment"
                  icon="pi pi-calendar"
                  @click="showAppointmentForm"
                  class="p-button-outlined"
                  :disabled="bookingAppointment"
                />
              </div>
            </div>
          </div>
          
          <!-- No slots available today -->
          <div v-else class="no-slots-today">
            <div class="no-slots-message">
              <i class="pi pi-calendar-times text-orange-500"></i>
              <h5>No Available Slots Today</h5>
              <p>All time slots for today are booked.</p>
            </div>
            
            <div class="alternatives">
              <Button
                label="Join Today's Waiting List"
                icon="pi pi-clock"
                @click="showWaitListModal"
                class="p-button-warning"
              />
              <Button
                label="Book Future Appointment"
                icon="pi pi-calendar"
                @click="showAppointmentForm"
                class="p-button-outlined"
              />
            </div>
          </div>
        </div>
        
        <!-- Scenario 2: Doctor doesn't allow same-day appointments -->
        <div v-else class="same-day-not-allowed">
          <div class="scenario-header">
            <i class="pi pi-calendar-minus text-orange-500"></i>
            <h4>Same-Day Appointments Not Available</h4>
          </div>
          
          <div class="not-allowed-message">
            <p>This doctor does not accept same-day appointments.</p>
          </div>
          
          <div class="alternatives">
            <!-- Option 1: Waiting List -->
            <div class="waiting-list-option">
              <h5>Option 1: Join Daily Waiting List</h5>
              <p>Get added to today's waiting list in case of cancellations.</p>
              <Button
                label="Join Waiting List"
                icon="pi pi-clock"
                @click="showWaitListModal"
                class="p-button-warning"
              />
            </div>
            
            <!-- Option 2: Next Available Appointment -->
            <div class="next-appointment-option">
              <h5>Option 2: Book Next Available Appointment</h5>
              <div v-if="availabilityData.next_available_date" class="next-date-info">
                <i class="pi pi-calendar"></i>
                <span>Next available: {{ formatDate(availabilityData.next_available_date) }}</span>
                <span v-if="availabilityData.period" class="period-info">({{ availabilityData.period }})</span>
              </div>
              <Button
                label="Book Future Appointment"
                icon="pi pi-calendar"
                @click="showAppointmentForm"
                class="p-button-primary"
              />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Error State -->
      <div v-else-if="!loading" class="error-state">
        <div class="error-message">
          <i class="pi pi-exclamation-triangle text-orange-500"></i>
          <h5>Unable to Check Availability</h5>
          <p>Please try again or use the alternative options below.</p>
        </div>
        
        <div class="alternatives">
          <Button
            label="Join Waiting List"
            icon="pi pi-clock"
            @click="showWaitListModal"
            class="p-button-warning"
          />
          <Button
            label="Book Future Appointment"
            icon="pi pi-calendar"
            @click="showAppointmentForm"
            class="p-button-primary"
          />
        </div>
      </div>
    </div>
  </Dialog>
  
  <!-- Add Waitlist Modal -->
  <teleport to="body">
    <div style="position:fixed; inset:0; z-index:2147483000; pointer-events: none;">
      <div style="pointer-events: auto;">
        <addWaitlistModel
          :show="showWaitlistModal"
          :editMode="false"
          :specializationId="extractedDoctorSpecializationId"
          :isDaily="1"
          :doctorId="extractedDoctorId"
          :patientId="extractedPatientId"
          @close="closeWaitlistModal"
          @save="onWaitlistSaved"
        />
      </div>
    </div>
  </teleport>
  
  <!-- Appointment Form Modal -->
  <teleport to="body">
    <div v-if="showAppointmentFormModal" style="position:fixed; inset:0; z-index:2147483001; pointer-events: none;">
      <div style="pointer-events: auto;">
        <appointmentForm
          :show="showAppointmentFormModal"
          :patientId="extractedPatientId"
          :doctorId="extractedDoctorId"
          :prestationId="extractedPrestationId"
          :NextAppointment="true"
          @close="closeAppointmentForm"
          @appointment-saved="onAppointmentSaved"
        />
      </div>
    </div>
  </teleport>
</template>

<style scoped>
/* Add debug info styling */
.debug-info {
  font-family: monospace;
  font-size: 0.8rem;
}

.debug-info h6 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.debug-info p {
  margin: 0.25rem 0;
  color: #666;
}

/* Error state styling */
.error-state {
  text-align: center;
  padding: 2rem;
}

.error-message {
  margin-bottom: 2rem;
}

.error-message h5 {
  color: #f59e0b;
  margin: 0.5rem 0;
}

/* Rest of existing styles... */
.same-day-content {
  padding: 1rem;
}

.loading-state {
  text-align: center;
  padding: 2rem;
}

.loading-spinner {
  margin-bottom: 1rem;
}

.scenario-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.scenario-header h4 {
  margin: 0;
  color: var(--text-color);
}

.available-slots h5,
.no-slots-message h5,
.waiting-list-option h5,
.next-appointment-option h5 {
  margin: 0 0 1rem 0;
  color: var(--text-color);
}

.slots-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.slot-button {
  padding: 0.75rem;
  border: 1px solid var(--surface-300);
  background: white;
  border-radius: 6px;
  transition: all 0.2s;
}

.slot-button:hover {
  border-color: var(--primary-color);
  background: var(--primary-50);
}

.slot-button.selected {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.selected-slot-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: var(--green-50);
  border-radius: 6px;
  margin-bottom: 1rem;
}

.slot-actions,
.alternatives {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.alternatives-section {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--surface-200);
}

.alternatives-section h6 {
  margin: 0 0 1rem 0;
  color: var(--text-color-secondary);
  font-weight: 500;
}

.no-slots-message {
  text-align: center;
  padding: 1.5rem;
  background: var(--orange-50);
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.not-allowed-message {
  background: var(--orange-50);
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
}

.waiting-list-option,
.next-appointment-option {
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 6px;
  margin-bottom: 1rem;
}

.next-date-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: var(--blue-50);
  border-radius: 4px;
}

.period-info {
  font-weight: 500;
  color: var(--blue-600);
}

.text-green-500 {
  color: #10b981;
}

.text-orange-500 {
  color: #f59e0b;
}

@media (max-width: 768px) {
  .same-day-appointment-modal {
    width: 95vw !important;
  }
  
  .slots-grid {
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  }
  
  .slot-actions,
  .alternatives {
    flex-direction: column;
  }
}
</style>