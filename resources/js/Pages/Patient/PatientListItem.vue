<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { useAuthStore } from '../../stores/auth';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import ProgressSpinner from 'primevue/progressspinner';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import TooltipDirective from 'primevue/tooltip';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

// Register directive for template
const vTooltip = TooltipDirective;

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const swal = useSweetAlert();
const router = useRouter();
const confirm = useConfirm();
const toaster = useToastr();
const toast = useToast();


const props = defineProps({
   
    isconsultation: {
        type: Boolean,
        default: false,
    },
});

const Patient = ref([])
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('');
const file = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const fileInput = ref(null);

const isModalOpen = ref(false);
const selectedPatient = ref([]);
const pagiante = ref([]);

// Filters state
const filters = reactive({
  search: '',
  gender: null,
  status: null
});

// Filter options
const genderOptions = ref([
  { label: 'All Genders', value: null },
  { label: 'Male', value: 'Male' },
  { label: 'Female', value: 'Female' }
]);

const statusOptions = ref([
  { label: 'All Status', value: null },
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]);

const emit = defineEmits(['import-complete', 'delete', 'close', 'patientsUpdate']);

// --- NEW: State to track if consultation page has been navigated to ---
const hasNavigatedToConsultation = ref(false);

// Function to fetch patient data from the API
const getPatient = async (page = 1) => {
    try {
        loading.value = true;
        const params = new URLSearchParams();
        params.append('page', page);

        if (filters.search) params.append('search', filters.search);
        if (filters.gender) params.append('gender', filters.gender);
        if (filters.status !== null) params.append('is_active', filters.status ? 1 : 0);

        const response = await axios.get(`/api/patients?${params.toString()}`);
        Patient.value = response.data.data || response.data;
        pagiante.value = response.data.meta;

        console.log('Pagination Meta:', pagiante.value);
    } catch (error) {
        console.error('Error fetching Patient:', error);
        error.value = error.response?.data?.message || 'Failed to load Patient';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load patients',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

// Apply filters
const applyFilters = async () => {
  filters.search = searchQuery.value;
  await getPatient();
};

// Debounced search function to prevent excessive API calls
const debouncedSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            try {
                filters.value.search = searchQuery.value;
                await getPatient();
            } catch (error) {
                console.error('Error searching patients:', error);
            }
        }, 300);
    };
})();

// Watch for search query changes and trigger debounced search
watch(searchQuery, debouncedSearch);

// Refresh data
const refreshData = async () => {
    await getPatient();
    toast.add({
        severity: 'success',
        summary: 'Refreshed',
        detail: 'Data refreshed successfully',
        life: 2000
    });
};

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

// Removed PatientPortal functions as we're now using a separate page

const handlePatientUpdated = () => {
    getPatient();
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
const handlePatientRowClick = (event) => {
    const patient = event.data;
    
    if (!patient || !patient.id) {
        console.error('Patient data missing or no ID found:', patient);
        return;
    }
    
    if (user.value.role === 'doctor') {
        // For doctors, navigate to consultation with patient ID
        router.push({
            name: 'admin.consultations.consulation.show',
            params: {
                patientId: patient.id,
                doctorId: user.value.id
            }
        });
    } else {
        // Navigate to the new patient portal page
        router.push({ 
            name: 'admin.patient.portal', 
            params: { id: patient.id } 
        });
    }
};


// Function to delete a patient
const deletePatient = async (id) => {
    confirm.require({
        message: 'Are you sure you want to delete this patient?',
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`/api/patients/${id}`);
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Patient deleted successfully!',
                    life: 3000
                });
                await getPatient();
            } catch (error) {
                console.error('Deletion error:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete patient',
                    life: 3000
                });
            }
        }
    });
};

// Fetch patients on component mount
onMounted(() => {
    getPatient();
    // --- NEW: Reset state on component mount ---
    hasNavigatedToConsultation.value = false;
})
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50 tw-to-indigo-50">
    <Toast />
    <ConfirmDialog />

    <!-- Enhanced Medical-themed Header -->
    <div class="tw-bg-white tw-border-b tw-border-slate-200 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm tw-bg-white/95">
      <div class="tw-px-4 lg:tw-px-8 xl:tw-px-12 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-blue-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-users tw-text-white tw-text-2xl"></i>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mb-1">Patient Management</h1>
              <p class="tw-text-slate-600 tw-text-base">Comprehensive patient information and management system</p>
            </div>
          </div>

          <!-- Enhanced Quick Stats -->
          <div class="tw-flex tw-gap-6">
            <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-px-6 tw-py-4 tw-rounded-xl tw-border tw-border-blue-200 tw-shadow-sm">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-blue-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-users tw-text-white"></i>
                </div>
                <div>
                  <div class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">Total Patients</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">{{ Patient.length }}</div>
                </div>
              </div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-px-6 tw-py-4 tw-rounded-xl tw-border tw-border-green-200 tw-shadow-sm">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-green-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-check-circle tw-text-white"></i>
                </div>
                <div>
                  <div class="tw-text-xs tw-font-medium tw-text-green-700 tw-uppercase tw-tracking-wide">Active</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">{{ Patient.filter(p => p.is_active).length }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
                            <div class="tw-px-4 lg:tw-px-8 xl:tw-px-12 tw-py-8">
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 lg:tw-p-8 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-wrap tw-gap-4 tw-flex-1">
            <div class="tw-relative tw-flex-1 tw-min-w-[280px] tw-max-w-[320px]">
              <IconField iconPosition="left" class="tw-w-full">
                <InputIcon class="pi pi-search tw-text-slate-400" />
                <InputText
                  v-model="searchQuery"
                  placeholder="Search by name, ID, or phone..."
                  class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-indigo-500 focus:tw-outline-none tw-text-base"
                />
              </IconField>
            </div>

            <Dropdown
              v-model="filters.gender"
              :options="genderOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Genders"
              class="tw-min-w-[160px] tw-rounded-xl"
              @change="applyFilters"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-user tw-text-indigo-500"></i>
                  {{ slotProps.options?.find(o => o.value === slotProps.value)?.label }}
                </div>
                <div v-else class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-filter tw-text-slate-400"></i>
                  <span class="tw-text-slate-500">All Genders</span>
                </div>
              </template>
            </Dropdown>

            <Dropdown
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Status"
              class="tw-min-w-[160px] tw-rounded-xl"
              @change="applyFilters"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value !== null" class="tw-flex tw-items-center tw-gap-3">
                  <span class="tw-w-3 tw-h-3 tw-rounded-full tw-shadow-sm"
                        :class="slotProps.value ? 'tw-bg-green-500' : 'tw-bg-yellow-500'"></span>
                  {{ slotProps.options?.find(o => o.value === slotProps.value)?.label }}
                </div>
                <div v-else class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-circle tw-text-slate-400"></i>
                  <span class="tw-text-slate-500">All Status</span>
                </div>
              </template>
            </Dropdown>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button
              @click="refreshPatient"
              icon="pi pi-refresh"
              class="p-button-outlined p-button-secondary tw-rounded-xl tw-px-6 tw-py-3"
              v-tooltip.top="'Refresh data'"
            />
            <Button
              @click="exportUsers"
              icon="pi pi-download"
              label="Export Data"
              class="p-button-outlined p-button-info tw-rounded-xl tw-px-6 tw-py-3"
              v-tooltip.top="'Export Data'"
            />
            <Button
              v-if="!isconsultation"
              @click="openModal"
              icon="pi pi-plus"
              label="Add Patient"
              class="p-button-primary tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
              v-tooltip.top="'Add Patient'"
            />
          </div>
        </div>
      </div>

      <!-- Enhanced Main Data Table -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <DataTable
          :value="Patient"
          :loading="loading"
          dataKey="id"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} patients"
          :rowsPerPageOptions="[10, 25, 50]"
          responsiveLayout="scroll"
          scrollHeight="600px"
          class="patient-table tw-rounded-2xl"
          @row-click="handlePatientRowClick"
        >
          <!-- Selection Column -->
          <Column field="index" header="#" headerStyle="width: 3rem">
            <template #body="{ index }">
              {{ index + 1 }}
            </template>
          </Column>

          <!-- Avatar -->
          <Column field="avatar" header="Photo" headerStyle="width: 6rem">
            <template #body="{ data }">
              <Avatar
                v-if="data.avatar && !data.avatar.includes('default')"
                :image="data.avatar"
                class="tw-mr-2"
                size="large"
                shape="circle"
              />
              <Avatar
                v-else
                :label="data.first_name?.charAt(0)?.toUpperCase()"
                class="tw-bg-indigo-100 tw-text-indigo-600"
                size="large"
                shape="circle"
              />
            </template>
          </Column>

          <!-- Parent Name -->
          <Column field="Parent" header="Parent Name" :sortable="true">
            <template #body="{ data }">
              <div class="tw-font-semibold tw-text-slate-900">
                {{ data.Parent || 'N/A' }}
              </div>
            </template>
          </Column>

          <!-- Gender -->
          <Column field="gender" header="Gender" :sortable="true">
            <template #body="{ data }">
              <Badge
                :value="data.gender || 'Not Specified'"
                :severity="data.gender === 'Male' ? 'info' : data.gender === 'Female' ? 'success' : 'warning'"
                rounded
              />
            </template>
          </Column>

          <!-- First Name -->
          <Column field="first_name" header="First Name" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-slate-700">
                {{ data.first_name }}
              </div>
            </template>
          </Column>

          <!-- Last Name -->
          <Column field="last_name" header="Last Name" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-slate-700">
                {{ data.last_name }}
              </div>
            </template>
          </Column>

          <!-- ID Number -->
          <Column field="Idnum" header="ID Number" :sortable="true">
            <template #body="{ data }">
              <div class="tw-font-mono tw-text-sm tw-text-slate-600">
                {{ data.Idnum || 'N/A' }}
              </div>
            </template>
          </Column>

          <!-- Date of Birth -->
          <Column field="dateOfBirth" header="Date of Birth" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm tw-text-slate-600">
                {{ data.dateOfBirth ? new Date(data.dateOfBirth).toLocaleDateString() : 'N/A' }}
              </div>
            </template>
          </Column>

          <!-- Phone -->
          <Column field="phone" header="Phone">
            <template #body="{ data }">
              <div class="tw-text-sm tw-text-slate-600">
                <i class="pi pi-phone tw-mr-1 tw-text-xs"></i>
                {{ data.phone || 'N/A' }}
              </div>
            </template>
          </Column>

          <!-- Actions -->
          <Column v-if="!isconsultation" header="Actions" headerStyle="width: 12rem">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-1">
                <Button
                  @click.stop="openModal(data)"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-primary p-button-sm"
                  v-tooltip.top="'Edit Patient'"
                />
                <Button
                  v-if="user.role === 'admin' || user.role === 'SuperAdmin'"
                  @click.stop="deletePatient(data.id)"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-danger p-button-sm"
                  v-tooltip.top="'Delete Patient'"
                />
              </div>
            </template>
          </Column>

          <!-- Enhanced Empty State -->
          <template #empty>
            <div class="tw-text-center tw-py-16 tw-px-8">
              <div class="tw-bg-gradient-to-br tw-from-slate-100 tw-to-slate-200 tw-w-24 tw-h-24 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                <i class="pi pi-users tw-text-4xl tw-text-slate-400"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">No patients found</h3>
              <p class="tw-text-slate-500 tw-mb-6 tw-max-w-md tw-mx-auto">Get started by adding your first patient to the system. Patient records help manage healthcare workflows efficiently.</p>
              <Button
                v-if="!isconsultation"
                label="Add Your First Patient"
                icon="pi pi-plus"
                class="p-button-primary tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
                @click="openModal"
                v-tooltip.top="'Add Your First Patient'"
              />
            </div>
          </template>

          <!-- Enhanced Loading State -->
          <template #loading>
            <div class="tw-text-center tw-py-16 tw-px-8">
              <div class="tw-bg-gradient-to-br tw-from-indigo-100 tw-to-blue-100 tw-w-20 tw-h-20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                <ProgressSpinner
                  class="tw-w-8 tw-h-8"
                  strokeWidth="4"
                  fill="transparent"
                  animationDuration="1.5s"
                />
              </div>
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading patients...</h3>
              <p class="tw-text-slate-500">Please wait while we fetch the latest information</p>
            </div>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Patient Modal -->
    <PatientModel
      :show-modal="isModalOpen"
      :spec-data="selectedPatient"
      @close="closeModal"
      @patientsUpdate="refreshPatient"
    />
  </div>
</template>

<style scoped>
/* Enhanced Medical DataTable styling */
:deep(.patient-table .p-datatable-header) {
  background: linear-gradient(135deg, rgb(249, 250, 251) 0%, rgb(241, 245, 249) 100%);
  border-bottom: 2px solid rgb(226, 232, 240);
  padding: 0.75rem 1rem;
}

:deep(.patient-table .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, rgb(249, 250, 251) 0%, rgb(241, 245, 249) 100%);
  color: rgb(51, 65, 85);
  font-weight: 700;
  font-size: 0.8rem;
  border-bottom: 2px solid rgb(226, 232, 240);
  padding: 0.75rem 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

:deep(.patient-table .p-datatable-tbody > tr) {
  border-bottom: 1px solid rgb(241, 245, 249);
  transition: all 0.3s ease;
  cursor: pointer;
}

:deep(.patient-table .p-datatable-tbody > tr:hover) {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.05) 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

:deep(.patient-table .p-datatable-tbody > tr > td) {
  border: none;
  padding: 0.75rem 0.75rem;
  color: rgb(51, 65, 85);
  vertical-align: middle;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.875rem;
}

/* Enhanced DataTable wrapper for better width handling */
:deep(.patient-table) {
  width: 100%;
  overflow-x: auto;
}

:deep(.patient-table .p-datatable-wrapper) {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

:deep(.patient-table .p-datatable-table) {
  min-width: 1000px;
  width: 100%;
}

/* Enhanced custom styles */
:deep(.p-card) {
  border-radius: 1rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

:deep(.p-card .p-card-content) {
  padding: 2rem;
}

:deep(.p-button) {
  border-radius: 0.75rem;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  transition: all 0.2s ease;
  white-space: nowrap;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

:deep(.p-inputtext) {
  border-radius: 0.75rem;
  border-color: rgb(226, 232, 240);
  padding: 0.75rem 1rem;
  font-size: 1rem;
  transition: all 0.2s ease;
}

:deep(.p-inputtext:focus) {
  border-color: rgb(99, 102, 241);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  transform: translateY(-1px);
}

:deep(.p-dropdown) {
  border-radius: 0.75rem;
  transition: all 0.2s ease;
}

:deep(.p-dropdown:focus) {
  border-color: rgb(99, 102, 241);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  transform: translateY(-1px);
}

:deep(.p-tag) {
  font-weight: 600;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  white-space: nowrap;
}

:deep(.p-chip) {
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  white-space: nowrap;
}

:deep(.p-badge) {
  border-radius: 0.5rem;
  font-weight: 700;
  padding: 0.375rem 0.625rem;
  white-space: nowrap;
}

/* Enhanced pagination styles */
:deep(.p-paginator) {
  border-top: 2px solid rgb(226, 232, 240);
  background: linear-gradient(135deg, rgb(249, 250, 251) 0%, rgb(241, 245, 249) 100%);
  padding: 1.5rem 2rem;
  flex-wrap: wrap;
  justify-content: center;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page) {
  border-radius: 0.5rem;
  margin: 0 0.25rem;
  transition: all 0.2s ease;
  min-width: 2.5rem;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page:hover) {
  background-color: rgba(99, 102, 241, 0.1);
  transform: translateY(-1px);
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: linear-gradient(135deg, rgb(99, 102, 241) 0%, rgb(139, 92, 246) 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

/* Enhanced empty state */
:deep(.p-datatable .p-datatable-tbody > tr > td .tw-text-center) {
  padding: 4rem 2rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td .tw-text-center .pi-users) {
  font-size: 4rem;
  color: rgb(203, 213, 225);
  margin-bottom: 1rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td .tw-text-center p) {
  font-size: 1.125rem;
  color: rgb(148, 163, 184);
  margin-bottom: 1.5rem;
}

/* Loading state enhancement */
:deep(.p-datatable .p-datatable-loading-overlay) {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(4px);
}

/* Responsive enhancements - Better large screen support */
@media (min-width: 1440px) {
  .tw-px-4 {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }

  :deep(.patient-table .p-datatable-tbody > tr > td) {
    padding: 0.875rem 0.75rem;
    font-size: 0.9rem;
  }

  :deep(.patient-table .p-datatable-thead > tr > th) {
    padding: 0.875rem 0.75rem;
    font-size: 0.85rem;
  }
}

@media (max-width: 1280px) {
  .tw-px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  :deep(.patient-table .p-datatable-tbody > tr > td) {
    padding: 0.625rem 0.5rem;
    font-size: 0.8rem;
  }

  :deep(.patient-table .p-datatable-thead > tr > th) {
    padding: 0.625rem 0.5rem;
    font-size: 0.75rem;
  }
}

@media (max-width: 1024px) {
  .tw-px-4 {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }

  .tw-py-8 {
    padding-top: 2rem;
    padding-bottom: 2rem;
  }

  :deep(.patient-table .p-datatable-tbody > tr > td) {
    padding: 1rem 1rem;
    font-size: 0.9rem;
  }

  :deep(.patient-table .p-datatable-thead > tr > th) {
    padding: 1rem 1rem;
    font-size: 0.8rem;
  }
}

@media (max-width: 768px) {
  .tw-px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .tw-py-8 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
  }

  :deep(.patient-table .p-datatable-tbody > tr > td) {
    padding: 0.75rem 0.75rem;
    font-size: 0.85rem;
  }

  :deep(.patient-table .p-datatable-thead > tr > th) {
    padding: 0.75rem 0.75rem;
    font-size: 0.75rem;
  }

  /* Stack filters vertically on mobile */
  .tw-flex-wrap {
    flex-direction: column;
    align-items: stretch;
  }

  .tw-min-w-\[160px\] {
    min-width: 100%;
  }
}

/* Smooth transitions for all interactive elements */
* {
  transition: all 0.2s ease;
}

/* Enhanced avatar styling */
:deep(.p-avatar) {
  border: 2px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Tooltip enhancements */
:deep(.p-tooltip) {
  border-radius: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Better scrollbar styling for overflow content */
:deep(.patient-table::-webkit-scrollbar) {
  height: 8px;
}

:deep(.patient-table::-webkit-scrollbar-track) {
  background: rgb(241, 245, 249);
  border-radius: 4px;
}

:deep(.patient-table::-webkit-scrollbar-thumb) {
  background: rgb(203, 213, 225);
  border-radius: 4px;
}

:deep(.patient-table::-webkit-scrollbar-thumb:hover) {
  background: rgb(148, 163, 184);
}
</style>