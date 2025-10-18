<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToastr } from '../../../toster'

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    bedData: {
        type: Object,
        default: () => ({})
    },
    rooms: {
        type: Array,
        default: () => []
    },
    patients: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'bed-added', 'bed-updated'])

const toaster = useToastr()
const loading = ref(false)
const loadingServices = ref(false)
const loadingPatients = ref(false)
const form = ref({
    room_id: '',
    bed_identifier: '',
    status: 'free',
    current_patient_id: null
})
const errors = ref({})
const services = ref([])
const selectedService = ref('')
const patientSearchQuery = ref('')
const filteredPatients = ref([])
const showPatientDropdown = ref(false)

const isEditing = ref(false)

// Computed property for filtered rooms based on selected service
const filteredRooms = computed(() => {
    let list = props.rooms

    // ➊-a  remove waiting rooms
    list = list.filter(
        r => r.room_type?.room_type?.toLowerCase() !== 'waitingroom'
    )

    // ➊-b  optional service filter
    if (selectedService.value) {
        list = list.filter(r => r.service?.id == selectedService.value)
    }

    return list
})
// Computed property for patient search results
const searchedPatients = computed(() => {
    if (!patientSearchQuery.value) {
        return props.patients.slice(0, 10) // Show first 10 patients by default
    }
    return props.patients.filter(patient =>
        patient.first_name.toLowerCase().includes(patientSearchQuery.value.toLowerCase()) ||
        (patient.phone && patient.phone.includes(patientSearchQuery.value)) ||
        (patient.email && patient.email.toLowerCase().includes(patientSearchQuery.value.toLowerCase()))
    ).slice(0, 10) // Limit to 10 results
})
watch(() => props.showModal, async (open) => {
    if (!open) return

    resetForm()
    await fetchServices()                 // wait until services arrive

    if (props.bedData && props.bedData.id) {
        isEditing.value = true
        form.value = {
            room_id:             props.bedData.room?.id || '',
            bed_identifier:      props.bedData.bed_identifier || '',
            status:              props.bedData.status || 'free',
            current_patient_id:  props.bedData.current_patient?.id || null
        }

        // ➋  make sure the service selector is set AFTER the data exists
        selectedService.value = props.bedData.room?.service?.id || ''

        /* ➌  wait one tick so <select> has its options, then force v-model to
               notice the value (sometimes Safari / Firefox need that)          */
        await nextTick()
    } else {
        isEditing.value = false
    }
})
// Watch for service selection changes
watch(selectedService, (newService) => {
    // Clear room selection when service changes
    if (form.value.room_id) {
        const currentRoom = props.rooms.find(room => room.id == form.value.room_id)
        if (currentRoom && currentRoom.service?.id != newService) {
            form.value.room_id = ''
        }
    }
})

// Clear patient when status is not occupied
watch(() => form.value.status, (newStatus) => {
    if (newStatus !== 'occupied') {
        form.value.current_patient_id = null
        patientSearchQuery.value = ''
    }
})

const resetForm = () => {
    form.value = {
        room_id: '',
        bed_identifier: '',
        status: 'free',
        current_patient_id: null
    }
    errors.value = {}
    selectedService.value = ''
    patientSearchQuery.value = ''
    showPatientDropdown.value = false
}

const fetchServices = async () => {
    loadingServices.value = true
    try {
        const response = await axios.get('/api/services')
        services.value = response.data.data || response.data
    } catch (error) {
        console.error('Error fetching services:', error)
        toaster.error('Failed to load services')
    } finally {
        loadingServices.value = false
    }
}

const selectPatient = (patient) => {
    form.value.current_patient_id = patient.id
    patientSearchQuery.value = patient.name
    showPatientDropdown.value = false
}

const clearPatientSelection = () => {
    form.value.current_patient_id = null
    patientSearchQuery.value = ''
}

const closeModal = () => {
    emit('close')
}

const saveBed = async () => {
    loading.value = true
    errors.value = {}

    try {
        const url = isEditing.value ? `/api/beds/${props.bedData.id}` : '/api/beds'
        const method = isEditing.value ? 'PUT' : 'POST'

        const response = await axios({
            method,
            url,
            data: form.value
        })

        const bedData = response.data.data || response.data

        if (isEditing.value) {
            emit('bed-updated', bedData)
            toaster.success('Bed updated successfully!')
        } else {
            emit('bed-added', bedData)
            toaster.success('Bed created successfully!')
        }

        closeModal()
    } catch (error) {
        console.error('Error saving bed:', error)
        
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else {
            const message = error.response?.data?.message || 'Failed to save bed'
            toaster.error(message)
        }
    } finally {
        loading.value = false
    }
}

// Get selected patient name for display
const getSelectedPatientName = () => {
    if (form.value.current_patient_id) {
        const patient = props.patients.find(p => p.id == form.value.current_patient_id)
        return patient ? patient.name : ''
    }
    return ''
}

// Initialize patient search query if editing with existing patient
watch(() => props.bedData, (newBedData) => {
    if (newBedData && newBedData.current_patient) {
        patientSearchQuery.value = newBedData.current_patient.name
    }
}, { immediate: true })
</script>

<template>
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">
                    {{ isEditing ? 'Edit Bed' : 'Add New Bed' }}
                </h3>
                <button @click="closeModal" class="close-button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveBed" class="modal-form">
                <!-- Service Filter -->
                <div class="form-group">
                    <label for="service_filter" class="form-label">Filter by Service</label>
                    <select 
                        id="service_filter" 
                        v-model="selectedService" 
                        class="form-control"
                        :disabled="loadingServices"
                    >
                        <option value="">All Services</option>
                        <option v-for="service in services" :key="service.id" :value="service.id">
                            {{ service.name }}
                        </option>
                    </select>
                    <small class="form-help">Select a service to filter available rooms</small>
                </div>

                <!-- Room Selection -->
          <div class="form-group">
      <label class="form-label" for="room_id">Room *</label>

      <select
          id="room_id"
          v-model="form.room_id"
          class="form-control"
          :class="{ error: errors.room_id }"
          required
      >
          <option value="">Select a room</option>

          <!-- ➊-c  the list now never contains Waiting-Rooms             -->
          <!-- ➋   show the human-readable name instead of room_type code -->
          <option
              v-for="room in filteredRooms"
              :key="room.id"
              :value="room.id"
          >
              Room {{ room.room_number }} – {{ room.room_type.name }}
          </option>
      </select>

      <span v-if="errors.room_id" class="error-message">
        {{ errors.room_id[0] }}
      </span>

      <small
          v-if="selectedService && filteredRooms.length === 0"
          class="form-help text-warning"
      >
          No rooms available for the selected service
      </small>
  </div>

                <!-- Bed Identifier -->
                <div class="form-group">
                    <label for="bed_identifier" class="form-label">Bed Identifier *</label>
                    <input 
                        id="bed_identifier"
                        type="text" 
                        v-model="form.bed_identifier" 
                        class="form-control"
                        :class="{ 'error': errors.bed_identifier }"
                        placeholder="e.g., A, B, 1, 2"
                        required
                    />
                    <span v-if="errors.bed_identifier" class="error-message">{{ errors.bed_identifier[0] }}</span>
                </div>

                <!-- Status Selection -->
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select 
                        id="status" 
                        v-model="form.status"
                        class="form-control"
                        :class="{ 'error': errors.status }"
                    >
                        <option value="free">Free</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                    </select>
                    <span v-if="errors.status" class="error-message">{{ errors.status[0] }}</span>
                </div>

                <!-- Patient Search and Selection (only when status is occupied) -->
                <div class="form-group" v-if="form.status === 'occupied'">
                    <label for="patient_search" class="form-label">Assign Patient (Optional)</label>
                    <div class="patient-search-container">
                        <div class="search-input-wrapper">
                            <input 
                                id="patient_search"
                                type="text" 
                                v-model="patientSearchQuery"
                                @focus="showPatientDropdown = true"
                                @input="showPatientDropdown = true"
                                class="form-control"
                                :class="{ 'error': errors.current_patient_id }"
                                placeholder="Search patient by name, phone, or email..."
                            />
                            <button 
                                v-if="form.current_patient_id" 
                                type="button"
                                @click="clearPatientSelection"
                                class="clear-patient-btn"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Patient Dropdown -->
                        <div v-if="showPatientDropdown && searchedPatients.length > 0" class="patient-dropdown">
                            <div class="dropdown-header">
                                <span>{{ searchedPatients.length }} patient(s) found</span>
                                <button type="button" @click="showPatientDropdown = false" class="dropdown-close">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="patient-list">
                                <div 
                                    v-for="patient in searchedPatients" 
                                    :key="patient.id"
                                    @click="selectPatient(patient)"
                                    class="patient-item"
                                    :class="{ 'selected': form.current_patient_id == patient.id }"
                                >
                                    <div class="patient-info">
                                        <div class="patient-name">{{ patient.first_name }} {{ patient.last_name }}</div>
                                        <div class="patient-details">
                                            <span v-if="patient.phone" class="patient-phone">{{ patient.phone }}</span>
                                            <span v-if="patient.email" class="patient-email">{{ patient.email }}</span>
                                        </div>
                                    </div>
                                    <i v-if="form.current_patient_id == patient.id" class="fas fa-check patient-check"></i>
                                </div>
                            </div>
                        </div>

                        <!-- No patients found -->
                        <div v-else-if="showPatientDropdown && patientSearchQuery && searchedPatients.length === 0" class="no-patients">
                            <i class="fas fa-search"></i>
                            <span>No patients found matching "{{ patientSearchQuery }}"</span>
                        </div>
                    </div>
                    <span v-if="errors.current_patient_id" class="error-message">{{ errors.current_patient_id[0] }}</span>
                    <small class="form-help">You can leave this empty and assign a patient later</small>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" @click="closeModal" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                        {{ loading ? 'Saving...' : (isEditing ? 'Update Bed' : 'Create Bed') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}

.modal-content {
    background: #ffffff;
    border-radius: 1rem;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid #e2e8f0;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.close-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: none;
    border: none;
    border-radius: 0.375rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.close-button:hover {
    background-color: #f1f5f9;
    color: #374151;
}

.modal-form {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
    background-color: #ffffff;
}

.form-control:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-control.error {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #64748b;
}

.form-help.text-warning {
    color: #d97706;
}

.error-message {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #ef4444;
    font-weight: 500;
}

/* Patient Search Styles */
.patient-search-container {
    position: relative;
}

.search-input-wrapper {
    position: relative;
}

.clear-patient-btn {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
}

.clear-patient-btn:hover {
    color: #ef4444;
    background-color: #fee2e2;
}

.patient-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 0.5rem 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    z-index: 1001;
    max-height: 300px;
    overflow-y: auto;
}

.dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.dropdown-close {
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
}

.dropdown-close:hover {
    color: #374151;
    background-color: #e5e7eb;
}

.patient-list {
    max-height: 250px;
    overflow-y: auto;
}

.patient-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 1px solid #f1f5f9;
}

.patient-item:hover {
    background-color: #f8fafc;
}

.patient-item.selected {
    background-color: #ecfdf5;
    border-left: 3px solid #10b981;
}

.patient-info {
    flex: 1;
}

.patient-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.patient-details {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: #64748b;
}

.patient-phone::before {
    content: "📞 ";
}

.patient-email::before {
    content: "✉️ ";
}

.patient-check {
    color: #10b981;
    font-size: 0.875rem;
}

.no-patients {
    padding: 2rem 1rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
}

.no-patients i {
    margin-right: 0.5rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
}

.btn-secondary {
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #cbd5e1;
}

.btn-secondary:hover {
    background-color: #e2e8f0;
    color: #475569;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 1rem;
    }

    .modal-header {
        padding: 1rem 1.5rem;
    }

    .modal-form {
        padding: 1.5rem;
    }

    .form-actions {
        flex-direction: column;
        gap: 0.75rem;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .patient-details {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>
