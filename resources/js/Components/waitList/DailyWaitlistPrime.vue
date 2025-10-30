<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';
import { useSweetAlert } from '../../Components/useSweetAlert';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';
import AppointmentFormWaitlist from '../../Components/appointments/appointmentFormWaitlist.vue';
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
const showAppointmentModal = ref(false);
const selectedWaitlistForAppointment = ref(null);

const fetchWaitlists = async (filters = {}) => {
  loading.value = true;
  try {
    const params = { ...filters, is_Daily: 1 };
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
    // Convert object to array if needed
    if (Array.isArray(response.data)) {
      importanceOptions.value = response.data;
    } else {
      // Convert enum object to array format
      importanceOptions.value = Object.entries(response.data).map(([key, value]) => ({
        label: value.label || key,
        value: parseInt(value.value || key),
        color: value.color || 'info',
        icon: value.icon || 'pi pi-info-circle'
      }));
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch importance options', life: 3000 });
    // Fallback to default options
    importanceOptions.value = [
      { label: 'Normal', value: 0, color: 'info', icon: 'pi pi-info-circle' },
      { label: 'Urgent', value: 1, color: 'danger', icon: 'pi pi-exclamation-triangle' }
    ];
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
  // For daily waitlist, auto-schedule immediately
  selectedWaitlistForAppointment.value = waitlist;
  showAppointmentModal.value = true;
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

const handleAppointmentSaved = () => {
  showAppointmentModal.value = false;
  selectedWaitlistForAppointment.value = null;
  fetchWaitlists(currentFilter.value);
};

const closeAppointmentModal = () => {
  showAppointmentModal.value = false;
  selectedWaitlistForAppointment.value = null;
};

const filterWaitlists = (importance = null, doctor_id = null) => {
  currentFilter.value = { importance, doctor_id };
  fetchWaitlists(currentFilter.value);
};

const clearFilters = () => {
  currentFilter.value = { importance: null, doctor_id: null };
  fetchWaitlists(currentFilter.value);
};

const getImportanceSeverity = (importance) => {
  // Backend enum: 0 = Urgent (danger), 1 = Normal (primary/info)
  if (importance === 0) return 'danger';
  if (importance === 1) return 'info';
  
  // Fallback to options array if available and is an array
  if (Array.isArray(importanceOptions.value)) {
    const option = importanceOptions.value.find(opt => opt.value === importance);
    if (option) return option.color;
  }
  
  return 'info';
};


const getImportanceLabel = (importance) => {
  // Backend enum: 0 = Urgent, 1 = Normal
  if (importance === 0) return 'Urgent';
  if (importance === 1) return 'Normal';
  
  // Fallback to options array if available and is an array
  if (Array.isArray(importanceOptions.value)) {
    const option = importanceOptions.value.find(opt => opt.value === importance);
    if (option) return option.label;
  }
  
  return 'Unknown';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString();
};

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
          <h1 class="tw-text-3xl tw-font-bold tw-text-gray-800">Daily Waitlist</h1>
        </div>
        <nav class="tw-text-sm tw-text-gray-600">
          <span>Home</span>
          <i class="pi pi-angle-right tw-mx-2"></i>
          <span class="tw-text-blue-600">Waitlist</span>
        </nav>
      </div>
    </div>

    <!-- Main Content Card -->
    <Card class="tw-shadow-lg">
      <!-- Toolbar -->
      <template #header>
        <Toolbar class="tw-border-0 tw-bg-white tw-p-6">
          <template #start>
            <div class="tw-flex tw-flex-wrap tw-gap-2">
              <!-- Doctor Filter Buttons -->
              <Button 
                v-for="doctor in doctors" 
                :key="doctor.id"
                :label="doctor.name"
                :outlined="currentFilter.doctor_id !== doctor.id"
                :severity="currentFilter.doctor_id === doctor.id ? 'primary' : 'secondary'"
                size="small"
                @click="filterWaitlists(null, doctor.id)"
                class="tw-rounded-full"
              />
              <Button 
                label="All Doctors"
                :outlined="currentFilter.doctor_id !== null"
                :severity="currentFilter.doctor_id === null ? 'primary' : 'secondary'"
                size="small"
                @click="filterWaitlists(null, null)"
                class="tw-rounded-full"
              />
              <Button 
                label="No Doctor Assigned"
                :outlined="currentFilter.doctor_id !== 'null'"
                :severity="currentFilter.doctor_id === 'null' ? 'primary' : 'secondary'"
                size="small"
                @click="filterWaitlists(null, 'null')"
                class="tw-rounded-full"
              />
            </div>
          </template>
          
          <template #end>
            <div class="tw-flex tw-gap-2 tw-flex-wrap">
              <!-- Importance Filter Buttons -->
              <Button 
                v-for="option in importanceOptions" 
                :key="option.value"
                :label="option.label"
                :icon="option.icon"
                :outlined="currentFilter.importance !== option.value"
                :severity="currentFilter.importance === option.value ? option.color : 'secondary'"
                size="small"
                @click="filterWaitlists(option.value, currentFilter.doctor_id)"
                class="tw-rounded-full"
              />
              <Button 
                label="Clear Filters"
                icon="pi pi-filter-slash"
                severity="secondary"
                outlined
                size="small"
                @click="clearFilters"
                class="tw-rounded-full"
              />
              <Button 
                label="Add to Waitlist"
                icon="pi pi-plus"
                severity="primary"
                @click="openAddModal()"
                class="tw-rounded-full"
              />
            </div>
          </template>
        </Toolbar>
      </template>

      <!-- Data Table -->
      <template #content>
        <DataTable 
          :value="waitlists" 
          :loading="loading"
          stripedRows
          showGridlines
          responsiveLayout="scroll"
          :paginator="true"
          :rows="10"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          currentPageReportTemplate="{first} to {last} of {totalRecords}"
          class="tw-rounded-lg"
        >
          <Column field="doctor.user.name" header="Doctor Name" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-user-md tw-text-blue-500"></i>
                <span class="tw-font-medium">{{ data.doctor_name || 'Not Assigned' }}</span>
              </div>
            </template>
          </Column>
          
          <Column field="specialization.name" header="Specialization" sortable>
            <template #body="{ data }">
              <Chip :label="data.specialization_name" class="tw-bg-blue-100 tw-text-blue-800" />
            </template>
          </Column>
          
          <Column field="patient.firstname" header="Patient Name" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-user tw-text-green-500"></i>
                <span class="tw-font-medium">{{ data?.patient_first_name }} {{ data?.patient_last_name }}</span>
              </div>
            </template>
          </Column>
          
          <Column field="patient.phone" header="Patient Phone" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-phone tw-text-gray-500"></i>
                <span>{{ data?.patient_phone }}</span>
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

    <!-- Appointment Form Modal -->
    <AppointmentFormWaitlist
      v-if="selectedWaitlistForAppointment"
      :showModal="showAppointmentModal"
      :waitlist="selectedWaitlistForAppointment"
      :editMode="!!selectedWaitlistForAppointment.appointmentId"
      @close="closeAppointmentModal"
      @appointmentUpdated="handleAppointmentSaved"
    />
  </div>
</template>

<style scoped>
/* Custom PrimeVue overrides */
:deep(.p-datatable) {
  @apply rounded-lg tw-overflow-hidden;
}

:deep(.p-datatable-header) {
  @apply bg-gray-50 tw-border-b tw-border-gray-200;
}

:deep(.p-datatable-thead > tr > th) {
  @apply bg-gray-100 tw-text-gray-700 tw-font-semibold tw-border-b tw-border-gray-200;
}

:deep(.p-datatable-tbody > tr:hover) {
  @apply bg-blue-50;
}

:deep(.p-button) {
  @apply transition-all tw-duration-200;
}

:deep(.p-card) {
  @apply border-0;
}

:deep(.p-toolbar) {
  @apply border-0 tw-bg-transparent;
}
</style>