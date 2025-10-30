<!-- DoctorList.vue - Fixed Version -->
<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import DoctorListItem from './DoctorListItem.vue';
import DoctorModel from '../../Components/DoctorModel.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Toolbar from 'primevue/toolbar';
import Card from 'primevue/card';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';

const confirm = useConfirm();
const toaster = useToastr();

// State management
const doctors = ref([]);
const selectedDoctor = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  specialization: 0,
  number_of_patients_per_day: '',
  patients_based_on_time: '',
  time_slot: '',
  frequency: '',
  start_time: '',
  include_time: false,
  end_time: '',
  days: [],
  number_patients: false,
});
const isModalOpen = ref(false);
const searchQuery = ref('');
const isLoading = ref(false);
const selectedDoctors = ref([]);
const pagination = ref({});
const userRole = ref('');

// Fetch doctors - follow the same pattern used in PrimeWaitlistModal
const getDoctors = async (page = 1, filters = {}) => {
  isLoading.value = true;
  try {
    const params = { page, query: searchQuery.value, ...filters };
    const response = await axios.get('/api/doctors', { params });

    // if (response.data && response.data.data) {
      doctors.value = response.data.data;
      pagination.value = response.data.meta || pagination.value || {};
    // } else {
    //   doctors.value = Array.isArray(response.data) ? response.data : [];
    //   pagination.value = {};
    // }

    console.log('Doctors loaded:', doctors.value);
  } catch (error) {
    toaster.error('Failed to fetch doctors');
    console.error('Error fetching doctors:', error);
    doctors.value = [];
    pagination.value = {};
  } finally {
    isLoading.value = false;
  }
};

// Open modal for adding a new doctor
const openModal = () => {
  selectedDoctor.value = {
    name: '',
    email: '',
    phone: '',
    password: '',
    specialization: 0,
    number_of_patients_per_day: '',
    patients_based_on_time: '',
    appointmentBookingWindow: '',
    time_slot: '',
    frequency: '',
    include_time: false,
    start_time: '',
    end_time: '',
    days: [],
    number_patients: false,
  };
  isModalOpen.value = true;
};

// Close modal
const closeModal = () => {
  isModalOpen.value = false;
};

// Refresh doctors list
const refreshUsers = () => {
  getDoctors();
  closeModal();
};

// Fetch user role
const initializeDoctorId = async () => {
  try {
    const user = await axios.get('/api/role');
    if (user.data.role === 'admin' || user.data.role === 'SuperAdmin') {
      userRole.value = user.data.role;
    }
  } catch (err) {
    console.error('Error fetching user role:', err);
  }
};

// Debounced search
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/doctors/search', {
          params: { query: searchQuery.value },
        });

        // Handle search response
        if (response.data.data) {
          doctors.value = response.data.data;
        } else {
          doctors.value = Array.isArray(response.data) ? response.data : [];
        }

      } catch (error) {
        toaster.error('Failed to search doctors');
        console.error('Error searching doctors:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300);
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);

// Bulk delete
const bulkDelete = () => {
  confirm.require({
    message: `Are you sure you want to delete ${selectedDoctors.value.length} selected doctors?`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const doctorIds = selectedDoctors.value.map(doctor => doctor.id);
        await axios.delete('/api/doctors', {
          params: { ids: doctorIds },
        });
        toaster.success('Doctors deleted successfully!');
        selectedDoctors.value = [];
        getDoctors();
      } catch (error) {
        toaster.error('Failed to delete doctors');
        console.error('Error deleting doctors:', error);
      }
    }
  });
};

// Export doctors data
const exportDoctors = () => {
  toaster.info('Export functionality coming soon!');
};

// Get specialization severity for tags
const getSpecializationSeverity = (specialization) => {
  const severityMap = {
    'Cardiology': 'danger',
    'Neurology': 'warning',
    'Pediatrics': 'success',
    'General': 'info',
    'Orthopedics': 'secondary',
  };
  return severityMap[specialization] || 'info';
};

// Get frequency icon
const getFrequencyIcon = (frequency) => {
  const iconMap = {
    'Daily': 'pi-calendar',
    'Weekly': 'pi-calendar-plus',
    'Monthly': 'pi-calendar-times',
  };
  return iconMap[frequency] || 'pi-calendar';
};

onMounted(() => {
  getDoctors();
  initializeDoctorId();
});
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-white tw-to-indigo-50">
    <ConfirmDialog />

    <!-- Header Section -->
    <div class="tw-bg-white tw-shadow-sm tw-border-b tw-border-gray-200">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-py-6">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-users tw-text-indigo-600"></i>
                Doctor Management
              </h1>
              <p class="tw-mt-2 tw-text-sm tw-text-gray-600">
                Manage your healthcare professionals and their schedules
              </p>
            </div>
            <div class="tw-flex tw-items-center tw-gap-2">
              <Tag :value="`${doctors.length} Doctors`" severity="info" />
              <Tag v-if="selectedDoctors.length > 0" :value="`${selectedDoctors.length} Selected`" severity="warning" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <!-- Toolbar -->
      <Card class="tw-mb-6 tw-shadow-lg tw-border-0">
        <template #content>
          <Toolbar class="tw-border-0 tw-p-0">
            <template #start>
              <div class="tw-flex tw-flex-wrap tw-gap-2">
                <Button 
                  label="New Doctor" 
                  icon="pi pi-plus" 
                  class="p-button-success"
                  @click="openModal" 
                />
                <Button 
                  v-if="selectedDoctors.length > 0"
                  label="Delete Selected" 
                  icon="pi pi-trash"
                  class="p-button-danger"
                  :badge="`${selectedDoctors.length}`"
                  @click="bulkDelete"
                />
                <Button 
                  label="Export" 
                  icon="pi pi-download" 
                  class="p-button-outlined"
                  @click="exportDoctors" 
                />
              </div>
            </template>

            <template #end>
              <div class="tw-flex tw-flex-wrap tw-gap-3 tw-items-center">
                <!-- Search -->
                <IconField iconPosition="left" class="tw-w-full md:tw-w-auto">
                  <InputIcon>
                    <i class="pi pi-search" />
                  </InputIcon>
                  <InputText 
                    v-model="searchQuery" 
                    placeholder="Search doctors..." 
                    class="tw-w-full md:tw-w-80"
                  />
                </IconField>
              </div>
            </template>
          </Toolbar>
        </template>
      </Card>

      <!-- Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4 tw-mb-6">
        <Card class="tw-shadow-md tw-border-0">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Doctors</p>
                <p class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ doctors.length }}</p>
              </div>
              <Avatar icon="pi pi-users" class="tw-bg-blue-100 tw-text-blue-600" size="large" />
            </div>
          </template>
        </Card>

        <Card class="tw-shadow-md tw-border-0">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-text-gray-600 tw-mb-1">Active Today</p>
                <p class="tw-text-2xl tw-font-bold tw-text-green-600">
                  {{ doctors.filter(d => d.is_active).length }}
                </p>
              </div>
              <Avatar icon="pi pi-check-circle" class="tw-bg-green-100 tw-text-green-600" size="large" />
            </div>
          </template>
        </Card>

        <Card class="tw-shadow-md tw-border-0">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-text-gray-600 tw-mb-1">On Leave</p>
                <p class="tw-text-2xl tw-font-bold tw-text-yellow-600">
                  {{ doctors.filter(d => !d.is_active).length }}
                </p>
              </div>
              <Avatar icon="pi pi-calendar-times" class="tw-bg-yellow-100 tw-text-yellow-600" size="large" />
            </div>
          </template>
        </Card>

        <Card class="tw-shadow-md tw-border-0">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-text-gray-600 tw-mb-1">Avg. Patients/Day</p>
                <p class="tw-text-2xl tw-font-bold tw-text-purple-600">
                  {{ doctors.length > 0 ? Math.round(doctors.reduce((sum, d) => sum + (d.total_patients_per_day || 0), 0) / doctors.length) : 0 }}
                </p>
              </div>
              <Avatar icon="pi pi-heart" class="tw-bg-purple-100 tw-text-purple-600" size="large" />
            </div>
          </template>
        </Card>
      </div>
      <!-- Doctors Table -->
      <Card class="tw-shadow-lg tw-border-0">
        <template #content>
          <!-- DEBUG: doctors count and sample (remove in production) -->
          

          <DataTable 
            :value="doctors" 
            :selection="selectedDoctors"
            @update:selection="val => selectedDoctors.value = val"
            :loading="isLoading"
            dataKey="id"
            :paginator="true"
            responsiveLayout="scroll"
            class="tw-w-full"
            :rows="10"
            showGridlines
            stripedRows
          >
            <template #empty>
              <div class="tw-text-center tw-py-12">
                <i class="pi pi-inbox tw-text-6xl tw-text-gray-300"></i>
                <p class="tw-mt-4 tw-text-gray-500">No doctors found</p>
                <Button 
                  label="Add First Doctor" 
                  icon="pi pi-plus" 
                  class="p-button-primary tw-mt-4"
                  @click="openModal" 
                />
              </div>
            </template>

            <template #loading>
              <div class="tw-text-center tw-py-12">
                <ProgressSpinner />
                <p class="tw-mt-4 tw-text-gray-500">Loading doctors...</p>
              </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width: 3rem" />

            <Column field="index" header="#" headerStyle="width: 3rem">
              <template #body="slotProps">
                {{ slotProps.index + 1 }}
              </template>
            </Column>

            <Column field="avatar" header="Photo" headerStyle="width: 5rem">
              <template #body="slotProps">
                <Avatar 
                  v-if="slotProps.data.avatar && slotProps.data.avatar !== '/storage/default.png'"
                  :image="slotProps.data.avatar" 
                  class="tw-mr-2" 
                  size="large"
                  shape="circle"
                />
                <Avatar 
                  v-else
                  :label="slotProps.data.name?.charAt(0)?.toUpperCase()" 
                  class="tw-mr-2 tw-bg-indigo-100 tw-text-indigo-600" 
                  size="large"
                  shape="circle"
                />
              </template>
            </Column>

            <Column field="name" header="Name" sortable>
              <template #body="slotProps">
                <div class="tw-font-semibold tw-text-gray-900">
                  {{ slotProps.data.name }}
                  <Badge v-if="!slotProps.data.is_active" value="Inactive" severity="warning" class="tw-ml-2" />
                </div>
              </template>
            </Column>

            <Column field="email" header="Email" sortable>
              <template #body="slotProps">
                <div class="tw-text-sm tw-text-gray-600">
                  <i class="pi pi-envelope tw-mr-1 tw-text-xs"></i>
                  {{ slotProps.data.email }}
                </div>
              </template>
            </Column>

            <Column field="phone" header="Phone">
              <template #body="slotProps">
                <div class="tw-text-sm tw-text-gray-600">
                  <i class="pi pi-phone tw-mr-1 tw-text-xs"></i>
                  {{ slotProps.data.phone || 'N/A' }}
                </div>
              </template>
            </Column>

            <Column field="specialization" header="Specialization" sortable>
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.specialization || 'General'" 
                  :severity="getSpecializationSeverity(slotProps.data.specialization)"
                  rounded
                />
              </template>
            </Column>

            <Column field="frequency" header="Schedule">
              <template #body="slotProps">
                <Chip 
                  :label="slotProps.data.frequency || 'Not Set'"
                  :icon="`pi ${getFrequencyIcon(slotProps.data.frequency)}`"
                  class="tw-bg-blue-50 tw-text-blue-700"
                />
              </template>
            </Column>

            <Column field="time_slots" header="Time Slots">
              <template #body="slotProps">
                <Badge 
                  :value="`${slotProps.data.time_slots || 0} min`"
                  severity="info"
                />
              </template>
            </Column>

            <Column header="Actions" headerStyle="width: 10rem">
              <template #body="slotProps">
                <DoctorListItem
                  :doctor="slotProps.data"
                  :index="slotProps.index"
                  @doctorUpdated="refreshUsers"
                  @toggle-selection="(doctor) => {
                    const index = selectedDoctors.value.findIndex(d => d.id === doctor.id);
                    if (index > -1) {
                      selectedDoctors.value.splice(index, 1);
                    } else {
                      selectedDoctors.value.push(doctor);
                    }
                  }"
                  :selectAll="false"
                  :is-selected="selectedDoctors.value.some(d => d.id === slotProps.data.id)"
                />
              </template>
            </Column>
          </DataTable>

      
          <!-- Pagination -->
          <div class="tw-mt-6 tw-flex tw-justify-center" v-if="pagination && pagination.last_page > 1">
            <Bootstrap5Pagination 
              :data="pagination" 
              @pagination-change-page="getDoctors" 
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- Doctor Modal -->
    <DoctorModel
      :show-modal="isModalOpen"
      :doctor-data="selectedDoctor"
      @close="closeModal"
      @doctorUpdated="refreshUsers"
    />
  </div>
</template>

<style scoped>
/* Custom DataTable styling */
:deep(.p-datatable) {
  @apply border-0;
}

:deep(.p-datatable .p-datatable-header) {
  @apply bg-gray-50 tw-border-b tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply bg-gray-50 tw-text-gray-700 tw-font-semibold tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply hover:tw-bg-blue-50 tw-transition-colors tw-cursor-pointer;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  @apply border-gray-100;
}

/* Card styling */
:deep(.p-card) {
  @apply rounded-xl;
}

:deep(.p-card-content) {
  @apply p-4;
}

/* Button styling */
:deep(.p-button) {
  @apply rounded-lg tw-font-medium;
}

/* Input styling */
:deep(.p-inputtext) {
  @apply rounded-lg;
}

/* Pagination styling */
:deep(.pagination) {
  @apply flex tw-gap-1;
}

:deep(.page-link) {
  @apply px-3 tw-py-2 tw-rounded-lg tw-border tw-border-gray-300 hover:tw-bg-blue-50 tw-transition-colors;
}

:deep(.page-item.active .page-link) {
  @apply bg-blue-600 tw-text-white tw-border-blue-600;
}

/* Temporary visibility overrides for PrimeVue DataTable rows (debug only) */
:deep(.p-datatable .p-datatable-tbody > tr) {
  display: table-row !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  display: table-cell !important;
}
</style>
