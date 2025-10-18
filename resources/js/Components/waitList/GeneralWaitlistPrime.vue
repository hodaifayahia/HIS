<script setup>
import { ref, onMounted , computed } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';
import { useSweetAlert } from '../../Components/useSweetAlert';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Toolbar from 'primevue/toolbar';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';

const swal = useSweetAlert();
const confirm = useConfirm();
const toast = useToast();
const router = useRouter();
const route = useRoute();

const importanceOptions = ref([]);
const waitlists = ref([]);
const showAddModal = ref(false);
const currentFilter = ref({ importance: null, doctor_id: null });
const isDaily = route.query.isDaily;
const specializationId = parseInt(route.query.specialization_id);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const doctors = ref([]);
const loading = ref(false);
const searchTerm = ref('');
const selectedDoctor = ref(null);
const selectedImportance = ref(null);

const fetchWaitlists = async (filters = {}) => {
  loading.value = true;
  try {
    const params = { ...filters, is_Daily: 0 };
    const response = await axios.get(`/api/waitlists`, { params });
    waitlists.value = response.data.data;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch waitlists', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const fetchImportanceOptions = async () => {
  try {
    const response = await axios.get('/api/importance-enum');
    importanceOptions.value = response.data;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch importance options', life: 3000 });
  }
};

const fetchDoctors = async () => {
  try {
    if (specializationId) {
      const response = await axios.get(`/api/doctors/specializations/${specializationId}`);
      doctors.value = response.data.data;
    } else {
      doctors.value = [];
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch doctors', life: 3000 });
  }
};

const deleteWaitlist = async (id) => {
  confirm.require({
    message: 'Are you sure you want to delete this waitlist entry? This action cannot be undone.',
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await axios.delete(`/api/waitlists/${id}`);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Waitlist entry deleted successfully', life: 3000 });
        fetchWaitlists(currentFilter.value);
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete waitlist entry', life: 3000 });
      }
    }
  });
};

const moveToAppointments = async (waitlist) => {
  try {
    await axios.post(`/api/waitlists/${waitlist.id}/add-to-appointments`, {
      doctor_id: waitlist.doctor_id,
      waitlist_id: waitlist.id,
      patient_id: waitlist.patient_id,
      notes: waitlist.notes,
    });
    toast.add({ severity: 'success', summary: 'Success', detail: 'Patient moved to appointments successfully', life: 3000 });
    fetchWaitlists(currentFilter.value);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to move patient to appointments', life: 3000 });
  }
};

const updateImportance = async (waitlist, importance) => {
  try {
    await axios.patch(`/api/waitlists/${waitlist.id}/importance`, { importance });
    toast.add({ severity: 'success', summary: 'Success', detail: 'Importance updated successfully', life: 3000 });
    fetchWaitlists(currentFilter.value);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update importance', life: 3000 });
  }
};

const openAddModal = (waitlist = null) => {
  isEditMode.value = !!waitlist;
  selectedWaitlist.value = waitlist;
  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
  isEditMode.value = false;
  selectedWaitlist.value = null;
};

const handleSave = () => {
  fetchWaitlists(currentFilter.value);
};

const handleUpdate = () => {
  fetchWaitlists(currentFilter.value);
};

const applyFilters = () => {
  const filters = {};
  if (selectedDoctor.value) filters.doctor_id = selectedDoctor.value.id;
  if (selectedImportance.value) filters.importance = selectedImportance.value.value;
  if (searchTerm.value) filters.search = searchTerm.value;
  
  currentFilter.value = filters;
  fetchWaitlists(filters);
};

const clearFilters = () => {
  selectedDoctor.value = null;
  selectedImportance.value = null;
  searchTerm.value = '';
  currentFilter.value = {};
  fetchWaitlists();
};

const getImportanceSeverity = (importance) => {
  // Handle the specific mapping: 0 = Normal (info), 1 = Urgent (danger)
  if (importance === 0) return 'info';
  if (importance === 1) return 'danger';
  
  // Fallback to options array if available
  const option = importanceOptions.value.find(opt => opt.value === importance);
  if (!option) return 'info';
  
  switch (option.color) {
    case 'danger': return 'danger';
    case 'warning': return 'warning';
    case 'success': return 'success';
    case 'primary': return 'info';
    default: return 'info';
  }
};

const getImportanceLabel = (importance) => {
  const option = importanceOptions.value.find(opt => opt.value === importance);
  return option ? option.label : 'Unknown';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString();
};

const filteredWaitlists = computed(() => {
  let filtered = waitlists.value;
  
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase();
    filtered = filtered.filter(waitlist => 
      waitlist.patient?.firstname?.toLowerCase().includes(search) ||
      waitlist.patient?.lastname?.toLowerCase().includes(search) ||
      waitlist.patient?.phone?.includes(search) ||
      waitlist.doctor?.user?.name?.toLowerCase().includes(search)
    );
  }
  
  return filtered;
});

onMounted(() => {
  fetchImportanceOptions();
  fetchWaitlists();
  fetchDoctors();
});
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gray-50 tw-p-6">
    <Toast />
    <ConfirmDialog />
    
    <!-- Header Section -->
    <div class="tw-mb-6">
      <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <div class="tw-flex tw-items-center tw-gap-4">
          <Button 
            icon="pi pi-arrow-left" 
            severity="secondary" 
            outlined 
            @click="router.go(-1)"
            class="tw-rounded-full"
          />
          <h1 class="tw-text-3xl tw-font-bold tw-text-gray-800">General Waitlist</h1>
        </div>
        <nav class="tw-text-sm tw-text-gray-600">
          <span>Home</span>
          <i class="pi pi-angle-right tw-mx-2"></i>
          <span class="tw-text-blue-600">Waitlist</span>
        </nav>
      </div>
    </div>

    <!-- Filters Card -->
    <Card class="tw-shadow-lg tw-mb-6">
      <template #content>
        <div class="tw-p-4">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-filter tw-text-blue-500"></i>
            Filters & Search
          </h3>
          
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4 tw-mb-4">
            <!-- Search Input -->
            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Search</label>
              <InputText 
                v-model="searchTerm"
                placeholder="Search patients, doctors, phone..."
                class="tw-w-full"
              />
            </div>
            
            <!-- Doctor Filter -->
            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Doctor</label>
              <Dropdown 
                v-model="selectedDoctor"
                :options="doctors"
                optionLabel="name"
                placeholder="Select Doctor"
                class="tw-w-full"
                showClear
              />
            </div>
            
            <!-- Importance Filter -->
            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Importance</label>
              <Dropdown 
                v-model="selectedImportance"
                :options="importanceOptions"
                optionLabel="label"
                placeholder="Select Importance"
                class="tw-w-full"
                showClear
              />
            </div>
            
            <!-- Action Buttons -->
            <div class="tw-flex tw-flex-col tw-gap-2 tw-justify-end">
              <div class="tw-flex tw-gap-2">
                <Button 
                  label="Apply"
                  icon="pi pi-check"
                  severity="primary"
                  @click="applyFilters"
                  class="tw-flex-1"
                />
                <Button 
                  label="Clear"
                  icon="pi pi-times"
                  severity="secondary"
                  outlined
                  @click="clearFilters"
                  class="tw-flex-1"
                />
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Main Content Card -->
    <Card class="tw-shadow-lg">
      <!-- Toolbar -->
      <template #header>
        <Toolbar class="tw-border-0 tw-bg-white tw-p-6">
          <template #start>
            <div class="tw-flex tw-items-center tw-gap-4">
              <h2 class="tw-text-xl tw-font-semibold tw-text-gray-800">Waitlist Entries</h2>
              <Badge :value="filteredWaitlists.length" severity="info" class="tw-rounded-full" />
            </div>
          </template>
          
          <template #end>
            <Button 
              label="Add to Waitlist"
              icon="pi pi-plus"
              severity="primary"
              @click="openAddModal()"
              class="tw-rounded-full"
            />
          </template>
        </Toolbar>
      </template>

      <!-- Data Table -->
      <template #content>
        <DataTable 
          :value="filteredWaitlists" 
          :loading="loading"
          stripedRows
          showGridlines
          responsiveLayout="scroll"
          :paginator="true"
          :rows="15"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          currentPageReportTemplate="{first} to {last} of {totalRecords}"
          class="tw-rounded-lg"
          sortField="created_at"
          :sortOrder="-1"
        >
          <Column field="doctor.user.name" header="Doctor Name" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-user-md tw-text-blue-500"></i>
                <span class="tw-font-medium">{{ data.doctor?.user?.name || 'Not Assigned' }}</span>
              </div>
            </template>
          </Column>
          
          <Column field="specialization.name" header="Specialization" sortable>
            <template #body="{ data }">
              <Chip :label="data.specialization?.name" class="tw-bg-blue-100 tw-text-blue-800" />
            </template>
          </Column>
          
          <Column field="patient.firstname" header="Patient Name" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-user tw-text-green-500"></i>
                <div class="tw-flex tw-flex-col">
                  <span class="tw-font-medium">{{ data?.patient_first_name }} {{ data?.patient_last_name }}</span>
                  <span class="tw-text-xs tw-text-gray-500">ID: {{ data.patient?.id }}</span>
                </div>
              </div>
            </template>
          </Column>
          
          <Column field="patient.phone" header="Contact" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-phone tw-text-gray-500"></i>
                  <span>{{ data?.patient_phone }}</span>
                </div>
              
              </div>
            </template>
          </Column>
          
          <Column field="importance" header="Importance" sortable>
            <template #body="{ data }">
              <Badge 
                :value="getImportanceLabel(data.importance)"
                :severity="getImportanceSeverity(data.importance)"
                class="tw-rounded-full"
              />
            </template>
          </Column>
          
          <Column field="notes" header="Notes">
            <template #body="{ data }">
              <div class="tw-max-w-xs tw-truncate" v-if="data.notes">
                <span class="tw-text-sm tw-text-gray-600" :title="data.notes">{{ data.notes }}</span>
              </div>
              <span class="tw-text-gray-400 tw-text-sm" v-else>No notes</span>
            </template>
          </Column>
          
          <Column field="created_at" header="Created At" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-calendar tw-text-gray-500"></i>
                <span class="tw-text-sm">{{ formatDate(data.created_at) }}</span>
              </div>
            </template>
          </Column>
          
          <Column header="Actions" class="tw-text-center">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-2 tw-justify-center">
                <Button 
                  icon="pi pi-pencil"
                  severity="info"
                  outlined
                  size="small"
                  @click="openAddModal(data)"
                  v-tooltip="'Edit'"
                  class="tw-rounded-full"
                />
                <Button 
                  icon="pi pi-calendar-plus"
                  severity="success"
                  outlined
                  size="small"
                  @click="moveToAppointments(data)"
                  v-tooltip="'Move to Appointments'"
                  class="tw-rounded-full"
                />
                <Button 
                  icon="pi pi-trash"
                  severity="danger"
                  outlined
                  size="small"
                  @click="deleteWaitlist(data.id)"
                  v-tooltip="'Delete'"
                  class="tw-rounded-full"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Add/Edit Modal -->
    <AddWaitlistModal 
      :show="showAddModal" 
      :editMode="isEditMode" 
      :waitlist="selectedWaitlist"
      :specializationId="specializationId" 
      :isDaily="isDaily" 
      @close="closeAddModal" 
      @save="handleSave"
      @update="handleUpdate" 
    />
  </div>
</template>

<style scoped>
/* Custom PrimeVue overrides */
:deep(.p-datatable) {
  @apply tw-rounded-lg tw-overflow-hidden;
}

:deep(.p-datatable-header) {
  @apply tw-bg-gray-50 tw-border-b tw-border-gray-200;
}

:deep(.p-datatable-thead > tr > th) {
  @apply tw-bg-gray-100 tw-text-gray-700 tw-font-semibold tw-border-b tw-border-gray-200;
}

:deep(.p-datatable-tbody > tr:hover) {
  @apply tw-bg-blue-50;
}

:deep(.p-button) {
  @apply tw-transition-all tw-duration-200;
}

:deep(.p-card) {
  @apply tw-border-0;
}

:deep(.p-toolbar) {
  @apply tw-border-0 tw-bg-transparent;
}

:deep(.p-dropdown) {
  @apply tw-w-full;
}

:deep(.p-inputtext) {
  @apply tw-w-full;
}
</style>