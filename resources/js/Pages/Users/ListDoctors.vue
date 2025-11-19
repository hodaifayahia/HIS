<!-- DoctorList.vue - Enhanced with BonCommendList Design -->
<script setup>
import { ref, onMounted, watch, computed, reactive } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
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
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import TooltipDirective from 'primevue/tooltip';
import { useRouter } from 'vue-router';

const router = useRouter();
const confirm = useConfirm();
const toaster = useToastr();
const toast = useToast();

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

// Filters state
const filters = reactive({
  search: '',
  specialization: null,
  status: null
});

// Filter options
const specializationOptions = ref([
  { label: 'All Specializations', value: null },
  { label: 'Cardiology', value: 'Cardiology' },
  { label: 'Neurology', value: 'Neurology' },
  { label: 'Pediatrics', value: 'Pediatrics' },
  { label: 'General', value: 'General' },
  { label: 'Orthopedics', value: 'Orthopedics' },
  { label: 'Dermatology', value: 'Dermatology' }
]);

const statusOptions = ref([
  { label: 'All Status', value: null },
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]);

// Fetch doctors with proper error handling
const fetchDoctors = async () => {
  try {
    isLoading.value = true;
    const params = new URLSearchParams();
    
    if (filters.search) params.append('search', filters.search);
    if (filters.specialization) params.append('specialization', filters.specialization);
    if (filters.status !== null) params.append('is_active', filters.status ? 1 : 0);

    const response = await axios.get(`/api/doctors?${params.toString()}`);

    if (response.data && response.data.data) {
      doctors.value = response.data.data || [];
      pagination.value = response.data.meta || {};
    } else if (Array.isArray(response.data)) {
      doctors.value = response.data;
      pagination.value = {};
    } else {
      doctors.value = [];
      pagination.value = {};
    }
  } catch (error) {
    console.error('Error fetching doctors:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load doctors',
      life: 3000
    });
    doctors.value = [];
  } finally {
    isLoading.value = false;
  }
};

// Apply filters
const applyFilters = async () => {
  await fetchDoctors();
};

// Refresh data
const refreshData = async () => {
  await fetchDoctors();
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  });
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
  fetchDoctors();
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
        filters.search = searchQuery.value;
        await fetchDoctors();
      } catch (error) {
        console.error('Error searching doctors:', error);
      }
    }, 300);
  };
})();

// Watch for search query changes
watch(searchQuery, debouncedSearch);

// Bulk delete
const bulkDelete = () => {
  if (!selectedDoctors.value.length) return;
  
  confirm.require({
    message: `Are you sure you want to delete ${selectedDoctors.value.length} selected doctor(s)?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const doctorIds = selectedDoctors.value.map(d => d.id);
        await axios.delete('/api/doctors', {
          data: { ids: doctorIds }
        });
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Doctors deleted successfully!',
          life: 3000
        });
        selectedDoctors.value = [];
        await fetchDoctors();
      } catch (error) {
        console.error('Error deleting doctors:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete doctors',
          life: 3000
        });
      }
    }
  });
};

// Export doctors data
const exportDoctors = () => {
  toast.add({
    severity: 'info',
    summary: 'Coming Soon',
    detail: 'Export functionality will be available soon!',
    life: 3000
  });
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

// Navigate to doctor schedule
const viewDoctorSchedule = (doctor) => {
  if (doctor && doctor.id) {
    router.push({
      name: 'admin.doctors.schedule',
      params: { id: doctor.id }
    });
  }
};

onMounted(() => {
  fetchDoctors();
  initializeDoctorId();
});
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
              <h1 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mb-1">Doctor Management</h1>
              <p class="tw-text-slate-600 tw-text-base">Comprehensive healthcare professional management system</p>
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
                  <div class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">Total Doctors</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">{{ doctors.length }}</div>
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
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">{{ doctors.filter(d => d.is_active).length }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-4 lg:tw-px-8 xl:tw-px-12 tw-py-8">
      <!-- Enhanced Action Toolbar -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 lg:tw-p-8 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-wrap tw-gap-4 tw-flex-1">
            <div class="tw-relative tw-flex-1 tw-min-w-[280px] tw-max-w-[320px]">
              <IconField iconPosition="left" class="tw-w-full">
                <InputIcon class="pi pi-search tw-text-slate-400" />
                <InputText
                  v-model="searchQuery"
                  placeholder="Search by name, email, or specialization..."
                  class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-indigo-500 focus:tw-outline-none tw-text-base"
                />
              </IconField>
            </div>
            
            <Dropdown
              v-model="filters.specialization"
              :options="specializationOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Specializations"
              class="tw-min-w-[200px] tw-rounded-xl"
              @change="applyFilters"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-stethoscope tw-text-indigo-500"></i>
                  {{ slotProps.options?.find(o => o.value === slotProps.value)?.label }}
                </div>
                <div v-else class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-filter tw-text-slate-400"></i>
                  <span class="tw-text-slate-500">All Specializations</span>
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
              @click="refreshData"
              icon="pi pi-refresh"
              class="p-button-outlined p-button-secondary tw-rounded-xl tw-px-6 tw-py-3"
              v-tooltip.top="'Refresh data'"
            />
            <Button 
              @click="exportDoctors"
              icon="pi pi-download"
              label="Export Data"
              class="p-button-outlined p-button-info tw-rounded-xl tw-px-6 tw-py-3"
              v-tooltip.top="'Export Data'"
            />
            <Button 
              @click="openModal"
              icon="pi pi-plus"
              label="Add Doctor"
              class="p-button-primary tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
              v-tooltip.top="'Add Doctor'"
            />
          </div>
        </div>
      </div>

      <!-- Enhanced Main Data Table -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <DataTable 
          v-model:selection="selectedDoctors"
          :value="doctors"
          :loading="isLoading"
          dataKey="id"
          selectionMode="multiple"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} doctors"
          :rowsPerPageOptions="[10, 25, 50]"
          responsiveLayout="scroll"
          scrollHeight="600px"
          class="doctor-table tw-rounded-2xl"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Index -->
          <Column field="index" header="#" headerStyle="width: 3rem">
            <template #body="{ index }">
              {{ index + 1 }}
            </template>
          </Column>

          <!-- Photo -->
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
                :label="data.name?.charAt(0)?.toUpperCase()" 
                class="tw-bg-indigo-100 tw-text-indigo-600" 
                size="large"
                shape="circle"
              />
            </template>
          </Column>

          <!-- Name -->
          <Column field="name" header="Name" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-justify-between tw-gap-2">
                <div class="tw-font-semibold tw-text-slate-900 tw-cursor-pointer hover:tw-text-indigo-600" @click="viewDoctorSchedule(data)">
                  {{ data.name }}
                  <Badge v-if="!data.is_active" value="Inactive" severity="warning" class="tw-ml-2" />
                </div>
                <Button 
                  icon="pi pi-arrow-right" 
                  class="p-button-rounded p-button-text p-button-sm"
                  @click="viewDoctorSchedule(data)"
                  v-tooltip.left="'View Schedule'"
                />
              </div>
            </template>
          </Column>

          <!-- Email -->
          <Column field="email" header="Email" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm tw-text-slate-600">
                <i class="pi pi-envelope tw-mr-1 tw-text-xs"></i>
                {{ data.email }}
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

          <!-- Specialization -->
          <Column field="specialization" header="Specialization" :sortable="true">
            <template #body="{ data }">
              <Tag 
                :value="data.specialization || 'General'" 
                :severity="getSpecializationSeverity(data.specialization)"
                rounded
              />
            </template>
          </Column>

          <!-- Schedule -->
          <Column field="frequency" header="Schedule">
            <template #body="{ data }">
              <Chip 
                :label="data.frequency || 'Not Set'"
                :icon="`pi ${getFrequencyIcon(data.frequency)}`"
                class="tw-bg-blue-50 tw-text-blue-700"
              />
            </template>
          </Column>

          <!-- Time Slots -->
          <Column field="time_slots" header="Time Slots">
            <template #body="{ data }">
              <Badge 
                :value="`${data.time_slots || 0} min`"
                severity="info"
              />
            </template>
          </Column>

          <!-- Actions -->
          <Column header="Actions" headerStyle="width: 12rem">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-1">
                <DoctorListItem
                  :doctor="data"
                  @doctorUpdated="refreshUsers"
                />
              </div>
            </template>
          </Column>

          <!-- Enhanced Empty State -->
          <template #empty>
            <div class="tw-text-center tw-py-16 tw-px-8">
              <div class="tw-bg-gradient-to-br tw-from-slate-100 tw-to-slate-200 tw-w-24 tw-h-24 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                <i class="pi pi-inbox tw-text-4xl tw-text-slate-400"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">No doctors found</h3>
              <p class="tw-text-slate-500 tw-mb-6 tw-max-w-md tw-mx-auto">Get started by adding your first healthcare professional to the system. They'll be able to manage patient appointments and schedules.</p>
              <Button 
                label="Add Your First Doctor" 
                icon="pi pi-plus" 
                class="p-button-primary tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
                @click="openModal"
                v-tooltip.top="'Add Your First Doctor'"
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
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading doctors...</h3>
              <p class="tw-text-slate-500">Please wait while we fetch the latest information</p>
            </div>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Enhanced Delete Selected Button (Bulk Action) -->
    <div v-if="selectedDoctors.length > 0" class="tw-fixed tw-bottom-8 tw-right-8 tw-bg-white tw-rounded-2xl tw-shadow-2xl tw-p-6 tw-border tw-border-red-200 tw-animate-bounce bulk-action-btn tw-backdrop-blur-sm">
      <div class="tw-flex tw-items-center tw-gap-3">
        <div class="tw-bg-red-100 tw-p-2 tw-rounded-lg">
          <i class="pi pi-trash tw-text-red-600"></i>
        </div>
        <div>
          <div class="tw-text-sm tw-font-semibold tw-text-slate-900">{{ selectedDoctors.length }} selected</div>
          <div class="tw-text-xs tw-text-slate-500">Click to delete</div>
        </div>
        <Button 
          icon="pi pi-trash"
          class="p-button-danger tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold"
          rounded
          @click="bulkDelete"
          v-tooltip.left="`Delete ${selectedDoctors.length} selected doctors`"
        />
      </div>
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
/* Enhanced Medical DataTable styling */
:deep(.doctor-table .p-datatable-header) {
  background: linear-gradient(135deg, rgb(249, 250, 251) 0%, rgb(241, 245, 249) 100%);
  border-bottom: 2px solid rgb(226, 232, 240);
  padding: 0.75rem 1rem;
}

:deep(.doctor-table .p-datatable-thead > tr > th) {
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

:deep(.doctor-table .p-datatable-tbody > tr) {
  border-bottom: 1px solid rgb(241, 245, 249);
  transition: all 0.3s ease;
  cursor: pointer;
}

:deep(.doctor-table .p-datatable-tbody > tr:hover) {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.05) 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

:deep(.doctor-table .p-datatable-tbody > tr.p-highlight) {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.08) 100%);
  border-left: 4px solid rgb(99, 102, 241);
}

:deep(.doctor-table .p-datatable-tbody > tr > td) {
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
:deep(.doctor-table) {
  width: 100%;
  overflow-x: auto;
}

:deep(.doctor-table .p-datatable-wrapper) {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

:deep(.doctor-table .p-datatable-table) {
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

/* Enhanced bulk action button */
:deep(.bulk-action-btn) {
  animation: gentle-bounce 2s infinite;
  max-width: 300px;
}

@keyframes gentle-bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-4px);
  }
  60% {
    transform: translateY(-2px);
  }
}

/* Enhanced empty state */
:deep(.p-datatable .p-datatable-tbody > tr > td .tw-text-center) {
  padding: 4rem 2rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td .tw-text-center .pi-inbox) {
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

  :deep(.doctor-table .p-datatable-tbody > tr > td) {
    padding: 0.875rem 0.75rem;
    font-size: 0.9rem;
  }

  :deep(.doctor-table .p-datatable-thead > tr > th) {
    padding: 0.875rem 0.75rem;
    font-size: 0.85rem;
  }
}

@media (max-width: 1280px) {
  .tw-px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  :deep(.doctor-table .p-datatable-tbody > tr > td) {
    padding: 0.625rem 0.5rem;
    font-size: 0.8rem;
  }

  :deep(.doctor-table .p-datatable-thead > tr > th) {
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
  
  :deep(.doctor-table .p-datatable-tbody > tr > td) {
    padding: 1rem 1rem;
    font-size: 0.9rem;
  }
  
  :deep(.doctor-table .p-datatable-thead > tr > th) {
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
  
  :deep(.doctor-table .p-datatable-tbody > tr > td) {
    padding: 0.75rem 0.75rem;
    font-size: 0.85rem;
  }
  
  :deep(.doctor-table .p-datatable-thead > tr > th) {
    padding: 0.75rem 0.75rem;
    font-size: 0.75rem;
  }
  
  /* Stack filters vertically on mobile */
  .tw-flex-wrap {
    flex-direction: column;
    align-items: stretch;
  }
  
  .tw-min-w-\[200px\] {
    min-width: 100%;
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
:deep(.doctor-table::-webkit-scrollbar) {
  height: 8px;
}

:deep(.doctor-table::-webkit-scrollbar-track) {
  background: rgb(241, 245, 249);
  border-radius: 4px;
}

:deep(.doctor-table::-webkit-scrollbar-thumb) {
  background: rgb(203, 213, 225);
  border-radius: 4px;
}

:deep(.doctor-table::-webkit-scrollbar-thumb:hover) {
  background: rgb(148, 163, 184);
}
</style>
