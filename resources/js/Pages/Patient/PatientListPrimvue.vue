<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { useAuthStore } from '../../stores/auth';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';

// PrimeVue imports
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import Paginator from 'primevue/paginator';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const swal = useSweetAlert();
const router = useRouter();

const props = defineProps({
    isconsultation: {
        type: Boolean,
        default: false,
    },
});

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
const currentPage = ref(0);
const totalRecords = ref(0);
const rowsPerPage = ref(10);

const emit = defineEmits(['import-complete', 'delete', 'close', 'patientsUpdate']);

const hasNavigatedToConsultation = ref(false);

const getPatient = async (page = 1) => {
    try {
        loading.value = true;
        console.log('Fetching patients from /api/patients?page=' + page);
        const response = await axios.get(`/api/patients?page=${page}`);
        console.log('Patient API Response:', response.data);
        Patient.value = response.data.data || response.data;
        pagiante.value = response.data.meta;
        totalRecords.value = response.data.meta?.total || 0;
        
        console.log('Loaded patients:', Patient.value);
        console.log('Pagination Meta:', pagiante.value);
    } catch (error) {
        console.error('Error fetching Patient:', error);
        error.value = error.response?.data?.message || error.message || 'Failed to load Patient';
        console.error('Error details:', error.response?.data);
    } finally {
        loading.value = false;
    }
};

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
            }
        }, 300);
    };
})();

watch(searchQuery, debouncedSearch);

const onSearchFocus = () => {
    // console.log('Search input focused');
};

const onSearchBlur = () => {
    // console.log('Search input blurred');
};

const performSearch = () => {
    debouncedSearch();
};

const exportUsers = async () => {
    try {
        const response = await axios.get('/api/export/Patients', {
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const downloadUrl = window.URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = 'Patients.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Failed to export Patients:', error);
        toaster.error('Failed to export patients.');
    }
};

const uploadFile = async () => {
    if (!file.value) {
        errorMessage.value = 'Please select a file.';
        return;
    }

    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
        'application/csv',
        'text/x-csv',
    ];

    console.log('File type:', file.value.type);

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
            getPatient();
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
            fileInput.value.value = '';
        }
    }
};

const handleFileChange = (event) => {
    const selectedFile = event.target.files[0];
    if (selectedFile) {
        console.log('Selected file type:', selectedFile.type);
        file.value = selectedFile;
        errorMessage.value = '';
        successMessage.value = '';
    }
};

const openModal = (patient = null) => {
    selectedPatient.value = patient ? { ...patient } : {};
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const handlePatientUpdated = () => {
    getPatient();
};

const refreshPatient = async () => {
    await getPatient();
};

const goToPatientAppointmentsPage = (patientId) => {
    router.push({ name: 'admin.patient.appointments', params: { id: patientId } });
    hasNavigatedToConsultation.value = false;
};

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
    hasNavigatedToConsultation.value = true;
};

const handlePatientRowClick = (patient) => {
    console.log('Patient row clicked:', patient);
    console.log('User role:', user.value?.role);
    console.log('Navigating to patient appointments with ID:', patient.id);
    
    const routeName = user.value?.role === 'doctor' 
        ? 'doctor.patient.appointments' 
        : 'admin.patient.appointments';
    
    console.log('Using route:', routeName);
    
    router.push({ 
        name: routeName, 
        params: { id: patient.id } 
    }).catch(err => {
        console.error('Navigation error:', err);
        toaster.error('Failed to navigate to patient page: ' + err.message);
    });
};

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
            refreshPatient();
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

const onPageChange = (event) => {
    currentPage.value = event.page;
    getPatient(event.page + 1);
};

onMounted(() => {
    console.log('PatientList component mounted');
    getPatient();
    hasNavigatedToConsultation.value = false;
})
</script>

<template>
    <div class="tw-min-h-screen tw-bg-gray-50">
        <!-- Header Section -->
        <div class="tw-bg-white tw-shadow-sm tw-border-b tw-border-gray-200">
            <div class="tw-max-w-7xl tw-mx-auto tw-px-4 tw-sm:px-6 tw-lg:px-8">
                <div class="tw-flex tw-justify-between tw-items-center tw-py-6">
                    <div>
                        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Patients</h1>
                    </div>
                    <nav class="tw-flex tw-items-center tw-space-x-2 tw-text-sm">
                        <a href="#" class="tw-text-blue-600 tw-hover:text-blue-800 tw-transition-colors">Home</a>
                        <span class="tw-text-gray-400">/</span>
                        <span class="tw-text-gray-700 tw-font-medium">Patient</span>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="tw-py-8">
            <div class="tw-max-w-7xl tw-mx-auto tw-px-4 tw-sm:px-6 tw-lg:px-8 tw-space-y-6">
                
                <!-- Search and Actions Bar -->
                <div class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-p-6">
                    <div class="tw-flex tw-flex-col tw-lg:flex-row tw-gap-4">
                        <!-- Search Box -->
                        <div class="tw-flex-1">
                            <div class="tw-relative">
                                <InputText 
                                    v-model="searchQuery"
                                    placeholder="Search patients by name, ID, phone, or CodeBash"
                                    class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-rounded-lg tw-border tw-border-gray-300 tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent"
                                    @focus="onSearchFocus"
                                    @blur="onSearchBlur"
                                />
                                <i class="pi pi-search tw-absolute tw-left-4 tw-top-3.5 tw-text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="tw-flex tw-gap-3 tw-items-center">
                            <Button 
                                v-if="!isconsultation"
                                label="Add Patient" 
                                icon="pi pi-plus"
                                class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-text-white tw-px-5 tw-py-2.5 tw-rounded-lg tw-hover:from-blue-600 tw-hover:to-blue-700 tw-transition-all tw-shadow-md"
                                @click="openModal"
                            />
                        </div>
                    </div>

                    <!-- Import/Export Section -->
                    <div v-if="!isconsultation" class="tw-mt-6 tw-pt-6 tw-border-t tw-border-gray-200">
                        <div class="tw-flex tw-flex-col tw-md:flex-row tw-justify-between tw-items-center tw-gap-4">
                            <div class="tw-flex tw-items-center tw-gap-4">
                                <input 
                                    ref="fileInput" 
                                    type="file" 
                                    accept=".csv,.xlsx" 
                                    @change="handleFileChange"
                                    class="tw-hidden"
                                    id="fileUpload"
                                >
                                <label for="fileUpload" class="tw-cursor-pointer tw-bg-gray-100 tw-text-gray-700 tw-px-4 tw-py-2.5 tw-rounded-lg tw-hover:bg-gray-200 tw-transition-colors tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-upload"></i>
                                    <span>{{ file ? file.name : 'Choose File' }}</span>
                                </label>
                                
                                <Button 
                                    label="Import Patients" 
                                    icon="pi pi-file-import"
                                    :disabled="loading || !file"
                                    class="tw-bg-green-500 tw-text-white tw-hover:bg-green-600"
                                    @click="uploadFile"
                                />
                                
                                <Button 
                                    label="Export Excel" 
                                    icon="pi pi-file-export"
                                    class="tw-bg-purple-500 tw-text-white tw-hover:bg-purple-600"
                                    @click="exportUsers"
                                />
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <Message v-if="errorMessage" severity="error" :closable="true" class="tw-mt-4">
                            {{ errorMessage }}
                        </Message>
                        <Message v-if="successMessage" severity="success" :closable="true" class="tw-mt-4">
                            {{ successMessage }}
                        </Message>
                    </div>
                </div>

                <!-- Data Table Card -->
                <div class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-overflow-hidden">
                    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-h-64">
                        <ProgressSpinner />
                    </div>
                    
                    <Message v-else-if="error" severity="error" class="tw-m-4">
                        {{ error }}
                    </Message>
                    
                    <div v-else class="tw-overflow-x-auto">
                        <table class="tw-w-full">
                            <thead class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50">
                                <tr>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">#</th>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">Parent Name</th>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">Gender</th>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">First Name</th>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">Last Name</th>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">ID Number</th>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">Date of Birth</th>
                                    <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">Phone</th>
                                    <th v-if="!isconsultation" class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-medium tw-text-gray-700 tw-uppercase tw-tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="tw-bg-white tw-divide-y tw-divide-gray-200">
                                <tr v-if="Patient.length === 0">
                                    <td :colspan="isconsultation ? 8 : 9" class="tw-text-center tw-py-12 tw-text-gray-500">
                                        <i class="pi pi-inbox tw-text-4xl tw-text-gray-300 tw-mb-3 tw-block"></i>
                                        No patients found
                                    </td>
                                </tr>
                                <tr 
                                    v-for="(patient, index) in Patient" 
                                    :key="patient.id"
                                    @click="handlePatientRowClick(patient)"
                                    class="tw-hover:bg-blue-50 tw-cursor-pointer tw-transition-colors tw-duration-150"
                                >
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ index + 1 }}</td>
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ patient.Parent }}</td>
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                                        <span :class="[
                                            'tw-inline-flex tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-rounded-full',
                                            patient.gender === 'Male' ? 'tw-bg-blue-100 tw-text-blue-800' : 'tw-bg-pink-100 tw-text-pink-800'
                                        ]">
                                            {{ patient.gender }}
                                        </span>
                                    </td>
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ patient.first_name }}</td>
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ patient.last_name }}</td>
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ patient.Idnum }}</td>
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ patient.dateOfBirth }}</td>
                                    <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">{{ patient.phone }}</td>
                                    <td v-if="!isconsultation" class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-font-medium">
                                        <div class="tw-flex tw-gap-2">
                                            <Button 
                                                icon="pi pi-pencil"
                                                class="tw-p-2 tw-bg-blue-100 tw-text-blue-600 tw-hover:bg-blue-200 tw-rounded-lg tw-transition-colors"
                                                @click.stop="openModal(patient)"
                                            />
                                            <Button 
                                                v-if="user?.role == 'admin' || user?.role == 'SuperAdmin'"
                                                icon="pi pi-trash"
                                                class="tw-p-2 tw-bg-red-100 tw-text-red-600 tw-hover:bg-red-200 tw-rounded-lg tw-transition-colors"
                                                @click.stop="deletePatient(patient.id)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="tw-p-4 tw-border-t tw-border-gray-200">
                        <Paginator 
                            :rows="rowsPerPage" 
                            :totalRecords="totalRecords"
                            :rowsPerPageOptions="[10, 20, 30, 50]"
                            @page="onPageChange"
                            class="tw-flex tw-justify-center"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Modal -->
    <PatientModel 
        :show-modal="isModalOpen" 
        :spec-data="selectedPatient" 
        @close="closeModal"
        @patientsUpdate="refreshPatient" 
    />
</template>

<style scoped>
/* Enhanced animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tw-min-h-screen {
    animation: slideDown 0.3s ease-out;
}

.tw-bg-white {
    animation: fadeInUp 0.4s ease-out;
}

/* PrimeVue Customizations */
:deep(.p-paginator) {
    @apply bg-transparent tw-border-0;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page) {
    @apply min-w-[2.5rem] tw-h-10 tw-mx-1 tw-rounded-lg tw-border tw-border-gray-300 tw-text-gray-700 hover:tw-bg-blue-50 hover:tw-border-blue-500 hover:tw-text-blue-600 tw-transition-all;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    @apply bg-blue-500 tw-text-white tw-border-blue-500 hover:tw-bg-blue-600;
}

:deep(.p-inputtext:focus) {
    @apply outline-none tw-ring-2 tw-ring-blue-500 tw-border-transparent;
}

:deep(.p-button) {
    @apply font-medium tw-transition-all tw-duration-200;
}

/* Table row hover effect */
tbody tr {
    transition: all 0.2s ease;
}

tbody tr:hover {
    transform: translateX(4px);
}

/* Loading spinner customization */
:deep(.p-progress-spinner) {
    width: 50px;
    height: 50px;
}

:deep(.p-progress-spinner-circle) {
    stroke: #3b82f6;
}

/* Custom search wrapper animation */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

.tw-relative:focus-within {
    animation: pulse 1s;
}
</style>
