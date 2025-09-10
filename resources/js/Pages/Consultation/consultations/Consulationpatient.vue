<script setup>
import { ref, onMounted, computed, watch  } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useRouter } from 'vue-router';
import DoctorWaitlist from '../../../Components/Doctor/DoctorWaitlist.vue';
import SpeedAppointmentModel from '../../../Components/appointments/SpeedAppointmentModel.vue'; // Adjust path if necessary
import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';
import OldConsulation from '../OldConsulation/OldConsulation.vue'

const appointments = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');
const router = useRouter();
const toaster = useToastr();
const selectedStatus = ref('all'); // 'all', 'SCHEDULED', 'DONE', 'ONWORKING', 'CONFIRMED'
const countWithDoctor = ref(0);
const countWithoutDoctor = ref(0);
const NotForYou = ref(false);
const WaitlistDcotro = ref(false);
const pagination = ref({}); // Initialize pagination as an empty object
const currentFilter = ref('today'); // New ref for current filter, defaulting to 'today'
const currentPage = ref(1); // New ref for current page
const showModal = ref(false); // New ref for current page
const appointmentid = ref(null); // New ref for current page
const patientId = ref(null); // New ref for current page

// State for the AppointmentFormModal
const showAppointmentModal = ref(false);
const currentAppointmentId = ref(null);
const isEditMode = ref(false); // To determine if the modal is for editing or creating



const doctors = useAuthStoreDoctor();
const currentDoctorId = ref(null);
const specializationId = ref(null);

// Lifecycle Hook

// 1. First load doctor data independently
const loadDoctorData = async () => {
  try {
    await doctors.getDoctor();
    if (doctors.doctorData) {
      currentDoctorId.value = doctors.doctorData.id;
      specializationId.value = Number(doctors.doctorData.specialization_id) || null;
      console.log('Doctor data loaded - ID:', currentDoctorId.value);
    } else {
      throw new Error('No doctor data available');
    }
  } catch (error) {
    console.error('Failed to load doctor data:', error);
    toaster.error('Failed to load doctor profile');
    // Consider redirecting to login if essential
  }
};

// 2. Watch for changes in currentDoctorId to trigger appointments load
watch(currentDoctorId, (newId) => {
  if (newId) {
    console.log('Doctor ID changed, loading appointments:', newId);
    getAppointments(1, 'all_relevant_statuses');
  }
}, { immediate: false });

// 3. Initialize in onMounted
onMounted(async () => {
  await loadDoctorData();
  getAppointments(1, 'all_relevant_statuses');

});


// Watch for changes in filters and pagination to refetch appointments
watch(
  [selectedStatus, searchQuery, currentFilter, currentPage],
  ([newStatus, newSearch, newFilter, newPage]) => {
    console.log('Filter changed:', { newStatus, newSearch, newFilter, newPage });
    getAppointments(newPage, newStatus, newSearch, newFilter);
  },
  { immediate: false }
);

// Update handleSearch to work with the watch
const handleSearch = (event) => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    searchQuery.value = event.target.value;
    currentPage.value = 1; // Reset to first page on new search
    // No need to call getAppointments here since the watch will handle it
  }, 300);
};


// Computed property for filtered appointments (client-side filtering will be less necessary if API handles it)
const filteredAppointments = computed(() => {
    // If your API is handling filtering by status and search, this computed property might simplify.
    // For now, it will just display the appointments fetched by getAppointments.
    return appointments.value;
});
// Keep the existing getAppointments function, but ensure it logs for debugging
const getAppointments = async (page = 1, status = 'all', search = '', filter = 'today') => {
  try {
    loading.value = true;
    console.log('Fetching appointments with params:', {
      doctorId: currentDoctorId.value,
      page,
      status,
      search,
      filter,
    });

    const params = { page };

const statusMap = {
  SCHEDULED: 0,
  DONE: 4,
  ONWORKING: 5,
  CONFIRMED: 1,
};

if (status === 'all_relevant_statuses') {
  params.statuses = [0, 1, 4, 5];
  params.filter = 'all_dates';
} else if (status !== 'all') {
  if (statusMap[status] !== undefined) {
    params.statuses = [statusMap[status]];
    // If the status is 'ONWORKING' (5), always request all dates
    if (statusMap[status] === 5) {
      params.filter = 'all_dates';
    } else {
      params.filter = filter;
    }
  }
} else {
  params.filter = currentFilter.value;
} 


    if (search) {
      params.search = search;
    }

    const { data } = await axios.get(`/api/appointments/consulationappointment/${currentDoctorId.value}`, {
      params,
    });

    if (data.success) {
      appointments.value = data.data;
      pagination.value = data.meta;
      currentPage.value = data.meta.current_page;
    }
  } catch (err) {
    console.error('Error fetching appointments:', err);
    error.value = 'Failed to fetch appointments. Please try again.';
  } finally {
    loading.value = false;
  }
};

// How you would call it:

const GotoConsulatoinpage = (appointment) => {
    console.log('Navigating to consultation page for appointment:', appointment.doctor_id);

    router.push({
        name: 'admin.consultations.consulation.show',
        params: {
            appointmentId: appointment.id,
            patientId: appointment.patient_id,
            doctorId: appointment.doctor_id
        }
    });
};

const openWaitlistForYouModal = () => {
    WaitlistDcotro.value = true; // Open the Waitlist modal
    NotForYou.value = false; // Set the NotForYou state to false
};

const openWaitlistNotForYouModal = () => {
    WaitlistDcotro.value = true; // Open the Waitlist modal
    NotForYou.value = true; // Set the NotForYou state to true
};

const createConsultation = async (appointment) => {
    if (!appointment.patient_id) {
        console.error('No patient ID found:', appointment);
        toaster.error('Unable to open consultation: Patient ID is missing');
        return;
    }
    try {
        // Update appointment status to 'onWorking' (status value 5)
         axios.patch(`/api/appointment/${appointment.id}/status`, {
            status: 5
        });
      const consulation =   await axios.post('/api/consulations', {
            appointment_id: appointment.id,
            patient_id: appointment.patient_id,
            doctor_id: appointment.doctor_id,
        });        


        toaster.success('Appointment status updated to On Working.');

        router.push({
            name: 'admin.consultations.consulation.add',
            params: {
                appointmentid: appointment.id,
                specialization_id: appointment.specialization_id,
                patientId: appointment.patient_id,
                doctorId: appointment.doctor_id,
                consulationId:consulation.data.data.id
            }
        });
    } catch (err) {
        console.error('Error updating appointment status:', err);
        toaster.error('Failed to update appointment status.');
    }
};

const closeWaitlistModal = () => {
    WaitlistDcotro.value = false; // Close the Waitlist modal
};

// Functions to manage AppointmentFormModal visibility and data
const openCreateAppointmentModal = () => {
    
    isEditMode.value = false;
    showAppointmentModal.value = true;
};


const openEditAppointmentModal = (appointment) => {
    currentAppointmentId.value = appointment.id;
    // Don't overwrite currentDoctorId here, keep the logged-in doctor's ID
    // currentDoctorId.value = appointment.doctor_id; // REMOVE THIS LINE
    isEditMode.value = true;
    showAppointmentModal.value = true;
};
const goToDetailConsultation = (appointment) => {
    console.log('goToDetailConsultation called for appointment:', appointment.id);
    showModal.value = true;
    appointmentid.value = appointment.id;
    patientId.value = appointment.patient_id;
};

const closeAppointmentModal = () => {
    showAppointmentModal.value = false;
    currentAppointmentId.value = null;
    // Don't reset currentDoctorId - keep the logged-in doctor's ID
    // currentDoctorId.value = null; // REMOVE THIS LINE
    isEditMode.value = false;
    
    // Call getAppointments with the preserved doctor ID
    if (currentDoctorId.value) {
        getAppointments(1, 'all_relevant_statuses');
    }
};

// Handle page change for pagination
const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        currentPage.value = page;
    }
};

// Add debounce for search
let timeout = null;
</script>
<template>
    <div class="consultation-page">
        <div class="header-section">
            <div class="container-fluid">
                <h1 class="header-title">
                    <i class="fas fa-notes-medical me-2"></i>
                    Consultation
                </h1>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card main-card">
                    <div class="card-header">
                        <div class="d-flex flex-wrap gap-2 mb-4 justify-content-between ">
                            <div>
                                <button class="btn btn-outline-success position-relative" type="button"
                                    @click="openWaitlistForYouModal">
                                    <i class="fas fa-user-clock me-2"></i>
                                    Waitlist for You
                                    <span v-if="countWithDoctor > 0" class="badge bg-danger ms-1">{{ countWithDoctor }}</span>
                                </button>

                                <button class="btn btn-outline-warning position-relative ml-1" type="button"
                                    @click="openWaitlistNotForYouModal">
                                    <i class="fas fa-user-times me-2"></i>
                                    Waitlist for specialization
                                    <span v-if="countWithoutDoctor > 0" class="badge bg-danger ms-1">{{ countWithoutDoctor }}</span>
                                </button>
                            </div>

                            <button class="btn btn-outline-success" @click="openCreateAppointmentModal">
                                <i class="fas fa-plus-circle me-2"></i>
                                Quick Appointment
                            </button>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="btn-group status-filters">
                                <button v-for="status in ['all_relevant_statuses', 'SCHEDULED', 'CONFIRMED', 'ONWORKING', 'DONE']" :key="status"
                                    class="btn" :class="{
                                        'btn-primary': selectedStatus === status,
                                        'btn-outline-primary': selectedStatus !== status
                                    }" @click="selectedStatus = status; currentPage = 1;">
                                    <i :class="{
                                        'fas fa-list': status === 'all',
                                        'fas fa-calendar-check': status === 'SCHEDULED',
                                        'fas fa-check-circle': status === 'DONE',
                                        'fas fa-cogs': status === 'ONWORKING',
                                        'fas fa-check': status === 'CONFIRMED' // Add icon for CONFIRMED if needed
                                    }" class="me-2"></i>
                                    {{ status.charAt(0).toUpperCase() + status.slice(1).toLowerCase() }}
                                </button>
                            </div>

                            <div class="search-container">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0"
                                        placeholder="Search appointments..." @input="handleSearch">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div v-if="loading" class="loader-container">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"></span>
                            </div>
                        </div>

                        <div v-else-if="error" class="alert alert-danger m-3">
                            {{ error }}
                        </div>

                        <div v-else-if="filteredAppointments.length === 0" class="empty-state">
                            <i class="fas fa-calendar-times empty-icon"></i>
                            <p class="empty-text">No appointments found</p>
                        </div>

                        <div v-else class="table-responsive">
                            <table class="table table-hover appointment-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(appointment, index) in filteredAppointments" :key="appointment.id"
                                        class="appointment-row">
                                        <td @click="GotoConsulatoinpage(appointment)">{{ index + 1 + (currentPage - 1) * pagination.per_page }}</td>
                                        <td @click="GotoConsulatoinpage(appointment)">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle text-primary me-2"></i>
                                                <div>
                                                    <div class="fw-bold">{{ appointment.patient_first_name }} {{
                                                        appointment.patient_last_name }}
                                                    </div>
                                                    <small class="text-muted">ID: {{ appointment.patient_id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td @click="GotoConsulatoinpage(appointment)">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-md text-info me-2"></i>
                                                {{ appointment.doctor_name }}
                                            </div>
                                        </td>
                                        <td @click="GotoConsulatoinpage(appointment)">
                                            <div class="d-flex align-items-center">
                                                <i class="far fa-calendar-alt text-success me-2 textdate"></i>
                                                <div>
                                                    <div>{{ new Date(appointment.appointment_date).toLocaleDateString() }}</div>
                                                    <small class="text-muted textdate ">{{ appointment.appointment_time }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td @click="GotoConsulatoinpage(appointment)">
                                            <span class="badge" :class="`bg-${appointment.status.color}`">
                                                <i :class="appointment.status.icon" class="me-1"></i>
                                                {{ appointment.status.name }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button @click.stop.prevent="createConsultation(appointment)"
                                                    class="btn btn-info btn-sm action-button"
                                                    v-if="appointment.status.value === 5">
                                                    <i class="fas fa-play-circle me-1"></i>
                                                    Resume Consultation
                                                </button>
                                                <button @click.stop="createConsultation(appointment)"
                                                    class="btn btn-primary btn-sm action-button"
                                                    v-if="appointment.status.value === 0 || appointment.status.value === 1" >
                                                    <i class="fas fa-plus-circle me-1"></i>
                                                    Create Consultation
                                                </button>
                                                <button @click.stop="goToDetailConsultation(appointment)"
                                                    class="btn btn-primary btn-sm action-button"
                                                    v-if=" appointment.status.value === 4" >
                                                    <i class="fas fa-plus-circle me-1"></i>
                                                    view Consultation
                                                </button>


                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer" v-if="pagination.last_page > 1">
                        <nav aria-label="Page navigation example">
                            <ul class="pagination justify-content-center">
                                <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                                    <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">Previous</a>
                                </li>
                                <li class="page-item" v-for="page in pagination.last_page" :key="page"
                                    :class="{ 'active': currentPage === page }">
                                    <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                                </li>
                                <li class="page-item" :class="{ 'disabled': currentPage === pagination.last_page }">
                                    <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showModal" class="modal-overlay">
    <div class="modal-container">
<button class="modal-close" @click="showModal = false">×</button>
      <OldConsulation 
        :patient-id="patientId"
        :consultation-id="null"
        :appointment-id="appointmentid"
        :single-mode="true"
        @close="showModal = false"
      />
    </div>
  </div>
    </div>

    <DoctorWaitlist :WaitlistDcotro="WaitlistDcotro" :NotForYou="NotForYou" :specializationId="specializationId"
        :doctorId="currentDoctorId" @close="closeWaitlistModal" />

    <SpeedAppointmentModel v-if="showAppointmentModal" :doctorId="currentDoctorId" @close="closeAppointmentModal"
        @appointmentCreated=" getAppointments(1, 'all_relevant_statuses')" />
    
</template>
<style scoped>
.consultation-page {
    min-height: 100vh;
    background-color: #f8f9fa;
}
.textdate{
    font-size: 1rem;
}
.header-section {
    background: linear-gradient(135deg, #0061f2 0%, #6900f2 100%);
    padding: 2rem 1.5rem;
    margin-bottom: 2rem;
    border-radius: 0 0 0.5rem 0.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
}

.header-title {
    color: white;
    font-size: 1.75rem;
    margin: 0;
    font-weight: 600;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.modal-container {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 1200px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  padding: 20px;
}

.modal-close {
  position: absolute;
  top: 10px;
  right: 10px;
  border: none;
  background: none;
  font-size: 24px;
  cursor: pointer;
  padding: 5px 10px;
  border-radius: 4px;
  z-index: 1;
}

.modal-close:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

.main-card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
    margin-bottom: 2rem;
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(33, 40, 50, 0.125);
    padding: 1.5rem;
}

.status-filters .btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    margin-right: 0.5rem;
    font-weight: 500;
}

.search-container {
    min-width: 300px;
}

.search-container .input-group {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    border-radius: 0.5rem;
    overflow: hidden;
}

.search-container input {
    border-radius: 0 0.5rem 0.5rem 0;
    padding: 0.75rem 1rem;
}

.loader-container {
    padding: 3rem;
    text-align: center;
}

.empty-state {
    padding: 3rem;
    text-align: center;
    color: #6c757d;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-text {
    font-size: 1.1rem;
    margin: 0;
}

.appointment-table {
    margin: 0;
}

.appointment-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    padding: 1rem;
}

.appointment-row {
    transition: all 0.2s ease;
}

.appointment-row {
    transition: all 0.2s ease;
    cursor: pointer;
    /* This is important for visual feedback */
}

.appointment-row:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
}

.appointment-row:hover {
    background-color: #f8f9fa;
    cursor: pointer;
    transform: translateY(-1px);
}

.action-button {
    transition: all 0.2s ease;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
}

.action-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Pagination Styles */
.card-footer {
    background-color: white;
    border-top: 1px solid rgba(33, 40, 50, 0.125);
    padding: 1rem 1.5rem;
}

.pagination .page-item .page-link {
    color: #007bff;
    /* Bootstrap primary color */
    border: 1px solid #dee2e6;
    margin: 0 2px;
    border-radius: 0.25rem;
    transition: all 0.2s ease-in-out;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #e9ecef;
    border-color: #dee2e6;
}

.pagination .page-item .page-link:hover:not(.disabled) {
    background-color: #e9ecef;
    border-color: #007bff;
    color: #0056b3;
}


@media (max-width: 768px) {
    .header-section {
        padding: 1.5rem 1rem;
    }

    .status-filters {
        margin-bottom: 1rem;
        width: 100%;
    }

    .search-container {
        width: 100%;
    }

    .action-button {
        width: 100%;
        margin-top: 0.5rem;
    }
}
</style>