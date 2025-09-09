<script setup>
import { defineProps, defineEmits, ref, onMounted, onBeforeUnmount, watch, capitalize, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { useRouter } from 'vue-router';
import appointmentWaitlist from '../../Components/appointments/appointmentWaitlist.vue';
import AppointmentModal from '../../Components/appointments/AppointmentModal.vue';
import { useSweetAlert } from '../../Components/useSweetAlert';
import ReasonModel from '../../Components/appointments/ReasonModel.vue';

const swal = useSweetAlert();
const props = defineProps({
    appointments: {
        type: Array,
        required: true
    },
    error: {
        type: String,
        default: null
    },
    totalPages: {
        type: Number,
        required: true, // This makes the prop required
    },
    userRole: {
        type: String,
        required: true
    },
    doctorId: {
        type: String,
        required: false
    },
});

const emit = defineEmits(['getAppointments', "updateAppointment", 'updateStatus']);
const toastr = useToastr();
const router = useRouter();

const statuses = ref([]);
const error = ref(null);
const dropdownStates = ref({});
const searchQuery = ref("");
const isLoading = ref(false);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const localAppointments = ref(props.appointments);
const localPagination = ref(props.pagination);
const selectedDate = ref();
const previousDate = ref(null); // Store the previous date

const showAddModal = ref(false); // Add this line
const ShowReasonModel = ref(false);
const selectedAppointment = ref(null);
const isModalVisible = ref(false);
const appointmentId = ref(null);
const specialization_id = ref(null);
const ticketDataConfirmation = ref([]);


// Computed property to determine which appointments to display
const displayAppointments = computed(() => {
    return localAppointments.value.length > 0 ? localAppointments.value : props.appointments;
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};


const formatTime = (time) => {
    if (!time) return "00:00";
    try {
        if (time.includes('T')) {
            const [, timePart] = time.split('T');
            if (timePart.length === 6) return timePart;
            const [hours, minutes] = timePart.split(':');
            return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
        }
        const [hours, minutes] = time.split(':');
        return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
    } catch (error) {
        console.error("Error formatting time:", error);
        return "00:00";
    }
};const formatDateTime = (input) => {
    if (!input) return '';

    try {
        const date = new Date(input);

        const formattedDate = date.toLocaleDateString('fr', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        const formattedTime = date.toLocaleTimeString('fr', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });

        return `${formattedDate} ${formattedTime}`;
    } catch (error) {
        console.error("Error formatting datetime:", error);
        return '';
    }
};


const getPatientFullName = (patient) => {
    // Validate input
    if (!patient || typeof patient !== 'object') {
        return 'N/A';
    }

    // Extract and sanitize properties
    const {  patient_last_name = '', patient_first_name = '' } = patient;

    // Construct full name
    const fullName = [ patient_first_name , patient_last_name]
        .filter(Boolean) // Remove empty strings
        .join(' ')       // Join with spaces

    // Capitalize the result (assuming capitalize is defined elsewhere)
    return fullName ? capitalize(fullName) : 'N/A';
};
const toggleDropdown = (appointmentId) => {
    // Close all other dropdowns first
    Object.keys(dropdownStates.value).forEach(id => {
        if (id !== appointmentId.toString()) {
            dropdownStates.value[id] = false;
        }
    });
    
    dropdownStates.value = {
        ...dropdownStates.value,
        [appointmentId]: !dropdownStates.value[appointmentId]
    };
};

const togglePrintDropdown = (appointmentId) => {
    // Close all other print dropdowns first
    Object.keys(printDropdownStates.value).forEach(id => {
        if (id !== appointmentId.toString()) {
            printDropdownStates.value[id] = false;
        }
    });
    
    printDropdownStates.value = {
        ...printDropdownStates.value,
        [appointmentId]: !printDropdownStates.value[appointmentId]
    };
};

// Close dropdowns when clicking outside
const closeAllDropdowns = () => {
    dropdownStates.value = {};
    printDropdownStates.value = {};
};

// Add click outside event listener
onMounted(() => {
    getAppointmentsStatus();
    document.addEventListener('click', closeAllDropdowns);
});

// Clean up event listener
onBeforeUnmount(() => {
    document.removeEventListener('click', closeAllDropdowns);
});

const getStatusText = (status) => {
    return status?.name || 'Unknown';
};
const OpenReasoneModel = () => {
    ShowReasonModel.value = true;
}
const updateAppointmentStatus = async (appointmentId, newStatus, reason = null) => {
    try {
        const payload = { status: newStatus };

        // Only require reason when status is 2
        if (newStatus === 2) {
            if (!reason) {
                // Open reason modal if no reason provided
                selectedAppointment.value = { id: appointmentId };
                OpenReasoneModel();
                return;
            }
            payload.reason = reason;
        }

        await axios.patch(`/api/appointment/${appointmentId}/status`, payload);
        dropdownStates.value[appointmentId] = false;
        emit('updateStatus');
        emit('update-appointment');
        toastr.success('Appointment status updated successfully');
    } catch (err) {
        console.error('Error updating status:', err);
    }
};

const SubmitReasonValue = (reason) => {
    ShowReasonModel.value = false;
    if (selectedAppointment.value) {
        // Reuse updateAppointmentStatus with status 2 and provided reason
        updateAppointmentStatus(selectedAppointment.value.id, 2, reason);
    }
    selectedAppointment.value = null;
};

watch(() => props.appointments, (newVal) => {
    localAppointments.value = newVal;
}, { deep: true });

watch(() => props.pagination, (newVal) => {
    localPagination.value = newVal;
}, { deep: true });

const getAppointmentsStatus = async () => {
    try {
        const response = await axios.get(`/api/appointmentStatus/${props.doctorId}`);
        statuses.value = response.data;
    } catch (err) {
        error.value = 'Failed to load appointment statuses';
        console.error('Error:', err);
    }
};

const goToEditAppointmentPage = (appointment) => {
    router.push({
        name: 'admin.appointments.edit',
        params: { id: props.doctorId, appointmentId: appointment.id , specialization_id: appointment.specialization_id  }
    });
};
const openModal = (appointment) => {
    isModalVisible.value = true;
    appointmentId.value = appointment.id;
    specialization_id.value = appointment.specialization_id;
    emit('updateStatus');
    emit('update-appointment');
};

const closeModal = () => {
    isModalVisible.value = false;
    emit('updateStatus');
    emit('update-appointment');
};

const getStatusOption = (statusName) => {
    return statuses.value.find(option => option.name === statusName) ||
        { name: 'UNKNOWN', color: 'secondary', textColor: 'white', bgColor: 'bg-secondary' };
};


const deleteAppointment = async (appointmentid) => {
    try {
        // Show SweetAlert confirmation dialog using the configured swal instance
        const result = await swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        // If user confirms, proceed with deletion
        if (result.isConfirmed) {
            await axios.delete(`/api/appointments/${appointmentid}`);
            emit('updateAppointment');
            // Show success message
            swal.fire(
                'Deleted!',
                'appointment has been deleted.',
                'success'
            );

            // Emit event to notify parent component
            emit('doctorDeleted');
        }
    } catch (error) {
        // Handle error
        if (error.response?.data?.message) {
            swal.fire(
                'Error!',
                error.response.data.message,
                'error'
            );
        } else {
            swal.fire(
                'Error!',
                'Failed to delete Doctor.',
                'error'
            );
        }
    }
};

const openAddModal = () => {
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
    ShowReasonModel.value = false;
    showAddModal.value = false;
    isEditMode.value = false;
    selectedWaitlist.value = null;
};

const handleSave = (newWaitlist) => {
    console.log('New Waitlist:', newWaitlist);
    closeAddModal();
};

const handleUpdate = (updatedWaitlist) => {
    console.log('Updated Waitlist:', updatedWaitlist);
    closeAddModal();
};

const AddToWaitList = async (appointment) => {
    isEditMode.value = appointment.add_to_waitlist;
    selectedWaitlist.value = {
        ...appointment,
        patient_id: appointment.patient_id,
        doctor_id: props.doctorId, // Assuming doctorId is passed as a prop
        specialization_id: appointment.specialization_id, // Assuming this is available in the appointment object
        add_to_waitlist: appointment.add_to_waitlist, // Pass the add_to_waitlist flag
    };
    openAddModal();
};
const PrintTicket = async (appointment) => {
    try {
        console.log('Printing ticket for appointment:', appointment);
        // Prepare the ticket data
        const ticketData = {

            patient_name: getPatientFullName(appointment),
            patient_first_name: appointment.patient_first_name,
            patient_last_name: appointment.patient_last_name,
            doctor_id: null,
            
            // patient_phone: appointment.patient_phone,
            parent_name: appointment.Parent,
            doctor_name: appointment.doctor_name || 'N/A',

            appointment_date: formatDate(appointment.appointment_date),
            appointment_time: formatTime(appointment.appointment_time),
            description: appointment.description || 'N/A'
        };

        // Send POST request with the ticket data
        const response = await axios.post('/api/appointments/print-ticket', ticketData, {
            responseType: 'blob'
        });
        
        // Create PDF URL
        const pdfUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        
        // Open in new tab
        const printWindow = window.open(pdfUrl);
        
        // Automatically trigger print dialog
        printWindow.onload = function() {
            printWindow.print();
        };
    } catch (error) {
        console.error('Error printing ticket:', error);
        toastr.error('Failed to print ticket');
    }
}
const PrintConfirmationTicket = async (appointment) => {
    try {
        // Prepare the ticket data
        const ticketDataConfirmation = {

            patient_name: getPatientFullName(appointment),
            patient_first_name: appointment.patient_first_name,
            patient_last_name: appointment.patient_last_name,
            // patient_phone: appointment.patient_phone,
            parent_name: appointment.Parent,
            doctor_name: appointment.doctor_name || 'N/A',
            specialization_id :appointment.specialization_id,

            appointment_date: formatDate(appointment.appointment_date),
            appointment_time: formatTime(appointment.appointment_time),
            description: appointment.description || 'N/A'
        };

        // Send POST request with the ticket data
        const response = await axios.post('/api/appointments/Confirmation/print-confirmation-ticket', ticketDataConfirmation, {
            responseType: 'blob'
        });
        
        // Create PDF URL
        const pdfUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        
        // Open in new tab
        const printWindow = window.open(pdfUrl);
        
        // Automatically trigger print dialog
        printWindow.onload = function() {
            printWindow.print();
        };
    } catch (error) {
        console.error('Error printing ticket:', error);
        toastr.error('Failed to print ticket');
    }
}
const applyDateFilter = async () => {
    if (selectedDate.value) {
        isLoading.value = true;
        try {
            // Emit the selected date to the parent component for filtering
            emit('filterByDate', selectedDate.value);
        } catch (err) {
            error.value = 'Failed to filter appointments by date.';
        } finally {
            isLoading.value = false;
        }
    } else {
        // If no date is selected, emit null to clear the filter
        emit('filterByDate', null);
    }
};
const resetToPreviousDate = () => {
    selectedDate.value = null; // Reset to the previous date
    applyDateFilter(); // Reapply the filter with the previous date
};
// Watch for changes in selectedDate
watch(selectedDate, (newDate, oldDate) => {
    if (newDate !== oldDate) {
        previousDate.value = oldDate; // Store the previous date
    }
});
// Optimized debounced search with cleanup
const debouncedSearch = (() => {
    let timeoutId;
    return (immediate = false) => {
        clearTimeout(timeoutId);
        
        if (immediate) {
            performSearch();
            return;
        }
        
        timeoutId = setTimeout(performSearch, 300);
    };
})();

const performSearch = async () => {
    try {
        isLoading.value = true;
        
        // If search query is empty, fetch appointments with current status
        if (!searchQuery.value?.trim()) {
            emit('update-appointment');
            return;
        }
        
        const response = await axios.get(`/api/appointments/search`, {
            params: {
                query: searchQuery.value.trim(),
                doctor_id: props.doctorId,
            },
        });
        
        if (response.data) {
            localAppointments.value = response.data.data;
            localPagination.value = response.data.meta;
            emit('getAppointments', response.data);
        }
    } catch (error) {
        console.error('Error searching appointments:', error);
        toastr.error('Failed to search appointments');
        emit('getAppointments');
    } finally {
        isLoading.value = false;
    }
};

// Improved watch for search query
watch(searchQuery, (newValue) => {
    if (!newValue || newValue.trim() === '') {
        debouncedSearch(true); // Immediate search for empty query
    } else {
        debouncedSearch();
    }
});
const printDropdownStates = ref({});
</script>
<template>
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-overflow-hidden">
        <!-- Error Alert -->
        <div v-if="error" class="tw-bg-red-50 tw-border-l-4 tw-border-red-400 tw-p-4 tw-mb-4">
            <div class="tw-flex">
                <div class="tw-flex-shrink-0">
                    <i class="fas fa-exclamation-circle tw-text-red-400"></i>
                </div>
                <div class="tw-ml-3">
                    <p class="tw-text-sm tw-text-red-700">{{ error }}</p>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="tw-p-4 tw-bg-gray-50 tw-border-b tw-border-gray-200">
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4">
                <!-- Date Filter -->
                <div class="tw-flex-1 tw-min-w-0">
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Filter by Date</label>
                    <div class="tw-flex">
                        <input 
                            type="date" 
                            v-model="selectedDate" 
                            class="tw-flex-1 tw-min-w-0 tw-block tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-l-md focus:tw-ring-blue-500 focus:tw-border-blue-500 sm:tw-text-sm"
                        />
                        <button 
                            @click="applyDateFilter" 
                            :disabled="isLoading"
                            class="tw-inline-flex tw-items-center tw-px-3 tw-py-2 tw-border tw-border-l-0 tw-border-gray-300 tw-bg-gray-50 tw-text-gray-500 hover:tw-bg-gray-100 tw-disabled:opacity-50 sm:tw-text-sm"
                        >
                            <i class="fas fa-filter"></i>
                        </button>
                        <button 
                            @click="resetToPreviousDate"
                            class="tw-inline-flex tw-items-center tw-px-3 tw-py-2 tw-border tw-border-l-0 tw-border-gray-300 tw-rounded-r-md tw-bg-gray-50 tw-text-gray-500 hover:tw-bg-gray-100 sm:tw-text-sm"
                        >
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="tw-flex-1 tw-min-w-0">
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Search Appointments</label>
                    <div class="tw-flex">
                        <input 
                            type="text" 
                            v-model="searchQuery"
                            placeholder="Search by patient name or date of birth"
                            class="tw-flex-1 tw-min-w-0 tw-block tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-l-md focus:tw-ring-blue-500 focus:tw-border-blue-500 sm:tw-text-sm"
                        />
                        <button 
                            type="button" 
                            :disabled="isLoading"
                            class="tw-inline-flex tw-items-center tw-px-3 tw-py-2 tw-border tw-border-l-0 tw-border-gray-300 tw-rounded-r-md tw-bg-gray-50 tw-text-gray-500 hover:tw-bg-gray-100 tw-disabled:opacity-50 sm:tw-text-sm"
                        >
                            <i class="fas" :class="{ 'fa-search': !isLoading, 'fa-spinner fa-spin': isLoading }"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="tw-p-8 tw-text-center">
            <div class="tw-inline-flex tw-items-center">
                <div class="tw-animate-spin tw-rounded-full tw-h-6 tw-w-6 tw-border-b-2 tw-border-blue-600 tw-mr-3"></div>
                <span class="tw-text-gray-600">Loading appointments...</span>
            </div>
        </div>

        <!-- Table Container -->
        <div v-else class="tw-overflow-x-auto">
            <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                <thead class="tw-bg-gray-50">
                    <tr>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">#</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Patient</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Parent</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Phone</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Birth Date</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Appointment Date</th>
                        <th v-if="userRole === 'admin' || userRole === 'SuperAdmin'" scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Consultation</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Time</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Description</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Status</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Reason</th>
                        <th v-if="userRole === 'admin' || userRole === 'SuperAdmin'" scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Created By</th>
                        <th v-if="userRole === 'admin' || userRole === 'SuperAdmin'" scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Created At</th>
                        <th v-if="userRole === 'admin' || userRole === 'SuperAdmin'" scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Canceled By</th>
                        <th v-if="userRole === 'admin' || userRole === 'SuperAdmin'" scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Updated By</th>
                        <th v-if="userRole === 'admin' || userRole === 'SuperAdmin'" scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Updated At</th>
                        <th scope="col" class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="tw-bg-white tw-divide-y tw-divide-gray-200">
                    <!-- Empty State -->
                    <tr v-if="displayAppointments.length === 0">
                        <td :colspan="userRole === 'admin' || userRole === 'SuperAdmin' ? 17 : 11" class="tw-px-6 tw-py-12 tw-text-center">
                            <div class="tw-flex tw-flex-col tw-items-center tw-justify-center">
                                <i class="fas fa-calendar-times tw-text-gray-400 tw-text-4xl tw-mb-4"></i>
                                <h3 class="tw-text-lg tw-font-medium tw-text-gray-900 tw-mb-2">No appointments found</h3>
                                <p class="tw-text-sm tw-text-gray-500">Try adjusting your search criteria or create a new appointment.</p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Appointment Rows -->
                    <tr v-for="(appointment, index) in displayAppointments" 
                        :key="appointment.id" 
                        class="hover:tw-bg-gray-50 tw-transition-colors tw-duration-200">
                        
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ index + 1 }}</td>
                        
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap">
                            <div class="tw-text-sm tw-font-medium tw-text-gray-900">{{ getPatientFullName(appointment) }}</div>
                        </td>
                        
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ appointment.Parent || '__' }}</td>
                        
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ appointment.phone || '__' }}</td>
                        
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ formatDate(appointment.patient_Date_Of_Birth) }}</td>
                        
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900 tw-font-medium">{{ formatDate(appointment.appointment_date) }}</td>
                        
                        <td v-if="userRole === 'admin' || userRole === 'SuperAdmin'" class="tw-px-3 tw-py-4 tw-whitespace-nowrap">
                            <button class="tw-inline-flex tw-items-center tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium tw-bg-blue-100 tw-text-blue-800 hover:tw-bg-blue-200 tw-transition-colors tw-duration-200">
                                <i class="fas fa-stethoscope tw-mr-1"></i>
                                Consultation
                            </button>
                        </td>
                        
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900 tw-font-mono">{{ formatTime(appointment.appointment_time) }}</td>
                        
                        <td class="tw-px-3 tw-py-4">
                            <div class="tw-text-sm tw-text-gray-500 tw-max-w-xs tw-truncate">{{ appointment.description || "No description" }}</div>
                        </td>
                        
                        <!-- Status Dropdown -->
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap">
                            <div class="tw-relative tw-inline-block tw-text-left">
                                <button 
                                    @click="toggleDropdown(appointment.id)"
                                    class="tw-inline-flex tw-items-center tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium tw-transition-colors tw-duration-200"
                                    :class="`tw-bg-${appointment.status?.color}-100 tw-text-${appointment.status?.color}-800 hover:tw-bg-${appointment.status?.color}-200`"
                                >
                                    <i :class="appointment.status?.icon" class="tw-mr-1"></i>
                                    {{ getStatusText(appointment.status) }}
                                    <i class="fas fa-chevron-down tw-ml-1"></i>
                                </button>

                                <!-- Dropdown Menu -->
                                <div v-if="dropdownStates[appointment.id]" 
                                     class="tw-origin-top-right tw-absolute tw-right-0 tw-mt-2 tw-w-48 tw-rounded-md tw-shadow-lg tw-bg-white tw-ring-1 tw-ring-black tw-ring-opacity-5 tw-z-50">
                                    <div class="tw-py-1">
                                        <template v-for="status in statuses" :key="status.name">
                                            <template v-if="props.userRole === 'doctor' && status.value === 4">
                                                <button 
                                                    @click="updateAppointmentStatus(appointment.id, status.value)"
                                                    class="tw-flex tw-items-center tw-w-full tw-px-4 tw-py-2 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 tw-transition-colors tw-duration-200"
                                                >
                                                    <i :class="status.icon" class="tw-mr-2"></i>
                                                    {{ status.name }}
                                                </button>
                                            </template>
                                            <template v-else-if="props.userRole !== 'doctor'">
                                                <button 
                                                    @click="updateAppointmentStatus(appointment.id, status.value)"
                                                    class="tw-flex tw-items-center tw-w-full tw-px-4 tw-py-2 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 tw-transition-colors tw-duration-200"
                                                >
                                                    <i :class="status.icon" class="tw-mr-2"></i>
                                                    {{ status.name }}
                                                </button>
                                            </template>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="tw-px-3 tw-py-4">
                            <div class="tw-text-sm tw-text-gray-500 tw-max-w-xs tw-truncate">{{ appointment.reason || "__" }}</div>
                        </td>
                        
                        <!-- Admin/SuperAdmin only columns -->
                        <td v-if="userRole === 'admin' || userRole === 'SuperAdmin'" class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ appointment.created_by || "__" }}</td>
                        <td v-if="userRole === 'admin' || userRole === 'SuperAdmin'" class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ formatDateTime(appointment.created_at) || "__" }}</td>
                        <td v-if="userRole === 'admin' || userRole === 'SuperAdmin'" class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ appointment.canceled_by || "__" }}</td>
                        <td v-if="userRole === 'admin' || userRole === 'SuperAdmin'" class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ appointment.updated_by || "__" }}</td>
                        <td v-if="userRole === 'admin' || userRole === 'SuperAdmin'" class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">{{ formatDateTime(appointment.updated_at) || "__" }}</td>

                        <!-- Actions -->
                        <td class="tw-px-3 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-font-medium">
                            <div class="tw-flex tw-items-center tw-space-x-2">
                                <!-- Print Dropdown -->
                                <div class="tw-relative tw-inline-block tw-text-left">
                                    <button 
                                        @click="togglePrintDropdown(appointment.id)"
                                        class="tw-inline-flex tw-items-center tw-p-2 tw-text-gray-400 hover:tw-text-blue-600 tw-rounded-md hover:tw-bg-blue-50 tw-transition-colors tw-duration-200"
                                    >
                                        <i class="fas fa-print"></i>
                                    </button>
                                    
                                    <div v-if="printDropdownStates[appointment.id]" 
                                         class="tw-origin-top-right tw-absolute tw-right-0 tw-mt-2 tw-w-48 tw-rounded-md tw-shadow-lg tw-bg-white tw-ring-1 tw-ring-black tw-ring-opacity-5 tw-z-50">
                                        <div class="tw-py-1">
                                            <button 
                                                @click="PrintTicket(appointment)"
                                                class="tw-flex tw-items-center tw-w-full tw-px-4 tw-py-2 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 tw-transition-colors tw-duration-200"
                                            >
                                                <i class="fas fa-ticket-alt tw-mr-2"></i> Ticket
                                            </button>
                                            <button 
                                                @click="PrintConfirmationTicket(appointment)"
                                                class="tw-flex tw-items-center tw-w-full tw-px-4 tw-py-2 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 tw-transition-colors tw-duration-200"
                                            >
                                                <i class="fas fa-file-alt tw-mr-2"></i> Confirmation
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit -->
                                <button 
                                    @click="goToEditAppointmentPage(appointment)"
                                    class="tw-p-2 tw-text-gray-400 hover:tw-text-blue-600 tw-rounded-md hover:tw-bg-blue-50 tw-transition-colors tw-duration-200"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Calendar -->
                                <button 
                                    @click="openModal(appointment)" 
                                    class="tw-p-2 tw-text-gray-400 hover:tw-text-green-600 tw-rounded-md hover:tw-bg-green-50 tw-transition-colors tw-duration-200"
                                >
                                    <i class="fas fa-calendar-plus"></i>
                                </button>

                                <!-- Delete (Admin/SuperAdmin only) -->
                                <button 
                                    v-if="props.userRole === 'admin' || userRole === 'SuperAdmin'" 
                                    @click="deleteAppointment(appointment.id)"
                                    class="tw-p-2 tw-text-gray-400 hover:tw-text-red-600 tw-rounded-md hover:tw-bg-red-50 tw-transition-colors tw-duration-200"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </button>

                                <!-- Waitlist -->
                                <button 
                                    @click="AddToWaitList(appointment)" 
                                    class="tw-p-2 tw-text-gray-400 hover:tw-text-yellow-600 tw-rounded-md hover:tw-bg-yellow-50 tw-transition-colors tw-duration-200"
                                >
                                    <i class="fas fa-clock"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modals -->
        <ReasonModel :show="ShowReasonModel" @close="closeAddModal" @submit="SubmitReasonValue" />
        <appointmentWaitlist 
            :show="showAddModal" 
            :editMode="isEditMode" 
            :waitlist="selectedWaitlist"
            :add_to_waitlist="selectedWaitlist?.add_to_waitlist ?? false" 
            @close="closeAddModal" 
            @save="handleSave"
            @update="handleUpdate" 
        />
        <AppointmentModal 
            v-if="isModalVisible" 
            :specialization_id="specialization_id" 
            :doctorId="props.doctorId" 
            :appointmentId="appointmentId"
            :editMode="true" 
            @close="closeModal" 
        />
    </div>
</template>

<style scoped>
/* Status button color utilities for dynamic classes */
.bg-primary-100 { background-color: #dbeafe; }
.text-primary-800 { color: #1e40af; }
.hover\:bg-primary-200:hover { background-color: #bfdbfe; }

.bg-success-100 { background-color: #dcfce7; }
.text-success-800 { color: #166534; }
.hover\:bg-success-200:hover { background-color: #bbf7d0; }

.bg-danger-100 { background-color: #fecaca; }
.text-danger-800 { color: #991b1b; }
.hover\:bg-danger-200:hover { background-color: #fca5a5; }

.bg-warning-100 { background-color: #fef3c7; }
.text-warning-800 { color: #92400e; }
.hover\:bg-warning-200:hover { background-color: #fde68a; }

.bg-info-100 { background-color: #cffafe; }
.text-info-800 { color: #155e75; }
.hover\:bg-info-200:hover { background-color: #a5f3fc; }

.bg-secondary-100 { background-color: #f3f4f6; }
.text-secondary-800 { color: #1f2937; }
.hover\:bg-secondary-200:hover { background-color: #e5e7eb; }

/* Loading animations */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Smooth transitions */
.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}

/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f3f4f6;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Ensure table stays responsive but readable */
table {
  min-width: 1200px;
}

/* Focus states for accessibility */
button:focus,
input:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Hover effects for better interaction feedback */
tr:hover {
  background-color: #f9fafb;
}

/* Status indicator styles */
.status-indicator {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 0.5rem;
}

/* Action button hover states */
.action-btn {
  transition: all 0.2s ease-in-out;
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Dropdown animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>