<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { useAuthStore } from '../../stores/auth'; // Import your Pinia store
const authStore = useAuthStore(); // Use the Pinia store
const swal = useSweetAlert();
import { useRouter } from 'vue-router';


const props = defineProps({
   
    isconsultation: {
        type: Boolean,
        default: false,
    },
});

const router = useRouter();
const Patient = ref([])
const loading = ref(false)
const error = ref(null)
const toaster = useToastr();
const searchQuery = ref('');
const file = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const fileInput = ref(null);

const isModalOpen = ref(false);
const selectedPatient = ref([]);
const pagiante = ref([]);

const emit = defineEmits(['import-complete', 'delete', 'close', 'patientsUpdate']);

// --- NEW: State to track if consultation page has been navigated to ---
const hasNavigatedToConsultation = ref(false);

// Function to fetch patient data from the API
const getPatient = async (page = 1) => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/patients?page=${page}`); // Pass the page parameter
        Patient.value = response.data.data || response.data; // Adjust based on your API response structure
        pagiante.value = response.data.meta; // Ensure this matches the meta data from the backend

        console.log('Pagination Meta:', pagiante.value); // Debugging: Check the meta data
    } catch (error) {
        console.error('Error fetching Patient:', error);
        error.value = error.response?.data?.message || 'Failed to load Patient';
    } finally {
        loading.value = false;
    }
};

// Debounced search function to prevent excessive API calls
const debouncedSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            try {
                const response = await axios.get('/api/patients/search', {
                    params: { query: searchQuery.value },
                });
                Patient.value = response.data.data;
            } catch (error) {
                toaster.error('Failed to search users');
                console.error('Error searching users:', error);
            } finally {
                // No specific final actions needed for search currently
            }
        }, 300); // 300ms delay
    };
})();

// Watch for search query changes and trigger debounced search
watch(searchQuery, debouncedSearch);

// Placeholder functions for search input focus/blur, if they perform any UI actions
const onSearchFocus = () => {
    // console.log('Search input focused');
};

const onSearchBlur = () => {
    // console.log('Search input blurred');
};

// Placeholder function for performSearch, assuming debouncedSearch handles the actual search
const performSearch = () => {
    debouncedSearch(); // Trigger the debounced search immediately on button click
};

// Function to export patient data to an Excel file
const exportUsers = async () => {
    try {
        const response = await axios.get('/api/export/Patients', {
            responseType: 'blob', // Ensure the response is treated as a binary file
        });

        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const downloadUrl = window.URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = 'Patients.xlsx'; // The name of the file
        document.body.appendChild(link);
        link.click();
        link.remove(); // Clean up
    } catch (error) {
        console.error('Failed to export Patients:', error);
        toaster.error('Failed to export patients.');
    }
};

// Function to upload an Excel/CSV file for patient import
const uploadFile = async () => {
    if (!file.value) {
        errorMessage.value = 'Please select a file.';
        return;
    }

    // Add file type validation
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
        'application/vnd.ms-excel', // xls
        'text/csv', // csv
        'application/csv', // some systems use this mime type for CSV
        'text/x-csv', // another possible CSV mime type
    ];

    console.log('File type:', file.value.type); // Debug line

    if (!allowedTypes.includes(file.value.type)) {
        errorMessage.value = 'Please upload a CSV or Excel file (xlsx, csv)';
        return;
    }

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        loading.value = true;
        const response = await axios.post('/api/import/Patients', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
        });

        if (response.data.success) {
            successMessage.value = response.data.message;
            errorMessage.value = '';
            emit('import-complete');
            getPatient(); // Refresh patient list after successful import
            toaster.success('Patients imported successfully!');
        } else {
            errorMessage.value = response.data.message;
            successMessage.value = '';
            toaster.error(response.data.message);
        }
    } catch (error) {
        console.error('Import error:', error);
        errorMessage.value = error.response?.data?.message || 'An error occurred during the file import.';
        successMessage.value = '';
        toaster.error(errorMessage.value);
    } finally {
        loading.value = false;
        if (fileInput.value) {
            fileInput.value.value = ''; // Clear file input
        }
    }
};

// Handler for file input change
const handleFileChange = (event) => {
    const selectedFile = event.target.files[0];
    if (selectedFile) {
        console.log('Selected file type:', selectedFile.type); // Debug line
        file.value = selectedFile;
        errorMessage.value = '';
        successMessage.value = '';
    }
};

// Function to open the patient modal (for add/edit)
const openModal = (patient = null) => {
    selectedPatient.value = patient ? { ...patient } : {};
    isModalOpen.value = true;
};

// Function to close the patient modal
const closeModal = () => {
    isModalOpen.value = false;
};

// Function to refresh the patient list
const refreshPatient = async () => {
    await getPatient();
};

// Function to navigate to the patient's appointments page
const goToPatientAppointmentsPage = (patientId) => {
    router.push({ name: 'admin.patient.appointments', params: { id: patientId } });
    // Reset the state when navigating away to a different view
    hasNavigatedToConsultation.value = false;
};

// Function to navigate to the consultation page
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
    // --- NEW: Set state to true after navigating to consultation ---
    hasNavigatedToConsultation.value = true;
};

// New function to handle patient row clicks conditionally based on role
const handlePatientRowClick = (patient) => {
   

    if (authStore.user.role === 'doctor') {
        if (!hasNavigatedToConsultation.value) {
            // First click for a doctor: Go to consultation
            GotoConsulatoinpage({
                id: null, // Placeholder for a new consultation or to be fetched
                patient_id: patient.id,
                doctor_id: null // Placeholder for the logged-in doctor's ID
            });
        } else {
            // Subsequent click for a doctor: Go to patient's appointments
            goToPatientAppointmentsPage(patient.id);
            // After navigating to appointments, reset for future clicks on THIS component instance
            hasNavigatedToConsultation.value = false;
        }
    } else {
        // For all other roles: Always navigate to the patient's appointments page.
        goToPatientAppointmentsPage(patient.id);
    }
};


// Function to delete a patient
const deletePatient = async (id) => {
    try {
        const result = await swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            await axios.delete(`/api/patients/${id}`);
            toaster.success('Patient deleted successfully');
            refreshPatient(); // Refresh the list after deletion
            swal.fire(
                'Deleted!',
                'Patient has been deleted.',
                'success'
            );
            closeModal();
        }
    } catch (error) {
        console.error('Deletion error:', error);
        if (error.response?.data?.message) {
            swal.fire(
                'Error!',
                error.response.data.message,
                'error'
            );
        } else {
            swal.fire(
                'Error!',
                'An unexpected error occurred during deletion.',
                'error'
            );
        }
    }
};

// Fetch patients on component mount
onMounted(() => {
    getPatient();
    // --- NEW: Reset state on component mount ---
    hasNavigatedToConsultation.value = false;
})
</script>

<template>
    <div class="appointment-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row">

                    <div class="col-lg-12">
                        <div class="search-wrapper mb-2">
                            <input type="text" class="form-control premium-search" v-model="searchQuery"
                                placeholder="Search patients by name, ID, or phone ,or CodeBash" @focus="onSearchFocus"
                                @blur="onSearchBlur" />
                            <button class="search-button" @click="performSearch">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button v-if="!isconsultation"
                                    class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 mb-4 py-2"
                                    title="Add Patient" @click="openModal">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add Patient</span>
                                </button>

                            </div>
                            
                            <div class="d-flex flex-column align-items-end">
                                <div v-if="!isconsultation" class="d-flex flex-column align-items-center">

                                    <div class="custom-file mb-3 " style="width: 200px; margin-left: 160px;">
                                        <label for="fileUpload" class="btn btn-primary w-100 premium-file-button">
                                            <i class="fas fa-file-upload mr-2"></i> Choose File
                                        </label>
                                        <input ref="fileInput" type="file" accept=".csv,.xlsx" @change="handleFileChange"
                                            class="custom-file-input d-none" id="fileUpload">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center ml-5 pl-5 ">
                                        <button @click="uploadFile" :disabled="loading || !file"
                                            class="btn btn-success mr-2 ml-5">
                                            Import Users
                                        </button>
                                        <button @click="exportUsers" class="btn btn-primary">
                                            Export File
                                        </button>
                                    </div>
                                    <div v-if="errorMessage" class="alert alert-danger mt-2" role="alert">
                                        {{ errorMessage }}
                                    </div>
                                    <div v-if="successMessage" class="alert alert-success mt-2" role="alert">
                                        {{ successMessage }}
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div v-if="error" class="alert alert-danger" role="alert">
                                  {{ error }}
                                </div>

                                <table v-else class="table table-hover ">
                                    <thead class="table-primary">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Parent Name</th>
                                            <th scope="col">Gender</th>
                                            <th scope="col">First Name</th>
                                            <th scope="col">Last Name</th>
                                            <th scope="col">Identification Number</th>
                                            <th scope="col">Date of Birth</th>
                                            <th scope="col">Phone</th>
                                            <th v-if="!isconsultation" scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <tr v-if="Patient.length === 0">
                                            <td :colspan="isconsultation ? 8 : 9" class="text-center">No Patient found</td>
                                        </tr>
                                        <tr v-for="(patient, index) in Patient" :key="patient.id"
                                            @click="handlePatientRowClick(patient)" style="cursor: pointer;">
                                            <td>{{ index + 1 }}</td>
                                            <td>{{ patient.Parent }}</td>
                                            <td>{{ patient.gender }}</td>
                                            <td>{{ patient.first_name }}</td>
                                            <td>{{ patient.last_name }}</td>
                                            <td>{{ patient.Idnum }}</td>
                                            <td>{{ patient.dateOfBirth }}</td>
                                            <td>{{ patient.phone }}</td>
                                            <td v-if="!isconsultation">
                                                <button @click.stop="openModal(patient)"
                                                    class="btn btn-sm btn-outline-primary me-2">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button v-if="role == 'admin' || role == 'SuperAdmin'"
                                                    @click.stop="deletePatient(patient.id)"
                                                    class="btn btn-sm btn-outline-danger ml-1">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="m-4">
                                <Bootstrap5Pagination :data="pagiante" :limit="5"
                                    @pagination-change-page="(page) => getPatient(page)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <PatientModel :show-modal="isModalOpen" :spec-data="selectedPatient" @close="closeModal"
            @patientsUpdate="refreshPatient" />
    </div>
</template>

<style scoped>
/* ... Your existing styles ... */

.search-wrapper {
    display: flex;
    align-items: center;
    border: 2px solid #007BFF;
    /* Blue border for a premium feel */
    border-radius: 50px;
    /* Rounded corners for a modern look */
    overflow: hidden;
    /* Ensures the border-radius applies to children */
    transition: all 0.3s ease;
    /* Smooth transition for focus/blur effects */
}

.premium-search {
    border: none;
    /* Remove default border */
    border-radius: 50px 0 0 50px;
    /* Round only left corners */
    flex-grow: 1;
    /* Expand to fill available space */
    padding: 10px 15px;
    /* Adequate padding for text */
    font-size: 16px;
    /* Clear, readable text size */
    outline: none;
    /* Remove the outline on focus */
}

.premium-search:focus {
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    /* Focus effect */
}

.search-button {
    border: none;
    background: #007BFF;
    /* Blue background for the search button */
    color: white;
    padding: 10px 20px;
    border-radius: 0 50px 50px 0;
    /* Round only right corners */
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
    /* Smooth transition for hover effect */
}

.search-button:hover {
    background: #0056b3;
    /* Darker blue on hover */
}

.search-button i {
    margin-right: 5px;
    /* Space between icon and text */
}

/* Optional: Animation for focus */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
    }

    70% {
        box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
    }
}

.search-wrapper:focus-within {
    animation: pulse 1s;
}
</style>