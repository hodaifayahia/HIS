<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';

const confirm = useConfirm();
const toast = useToast();

const importanceOptions = ref([]);
const waitlists = ref([]);
const showAddModal = ref(false);
const currentFilter = ref({ importance: null });
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const loading = ref(false);

const props = defineProps({
  WaitlistDcotro: {
    type: Boolean,
    required: true,
  },
  doctorId: {
    type: Number,
    default: null,
  },
  specializationId: {
    type: Number,
    default: null,
  },
  NotForYou: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['close']);

const showModal = ref(props.WaitlistDcotro);

// Watch for changes in the prop
watch(
  () => props.WaitlistDcotro,
  (newVal) => {
    console.log('WaitlistDcotro prop changed:', newVal);
    showModal.value = newVal;
    console.log('showModal updated to:', showModal.value);
  }
);

const closeModal = () => {
  console.log('closeModal called');
  showModal.value = false;
  waitlists.value = [];
  console.log('Emitting close event');
  emit('close');
};

const fetchWaitlists = async (filters = {}) => {
  try {
    loading.value = true;
    const params = { ...filters, is_Daily: 1 };
    params.doctor_id = props.NotForYou ? "null" : props.doctorId;
    params.specialization_id = props.specializationId;

    const response = await axios.get('/api/waitlists', { params });
    waitlists.value = response.data.data;
  } catch (error) {
    console.error('Error fetching waitlists:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch waitlists. Please try again.',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

// Watch for changes in the WaitlistDcotro prop to fetch waitlists
watch(
  () => props.WaitlistDcotro,
  (newVal) => {
    if (newVal) {
      fetchWaitlists(currentFilter.value);
    }
  }
);

const fetchImportanceOptions = async () => {
  try {
    const response = await axios.get('/api/importance-enum');
    importanceOptions.value = response.data;
  } catch (error) {
    console.error('Error fetching importance options:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch importance options. Please try again.',
      life: 3000
    });
  }
};

const deleteWaitlist = async (id) => {
  confirm.require({
    message: 'You are about to delete this waitlist entry. This action cannot be undone!',
    header: 'Are you sure?',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'tw-bg-gray-500 tw-text-white tw-border-gray-500 hover:tw-bg-gray-600',
    acceptClass: 'tw-bg-red-500 tw-text-white tw-border-red-500 hover:tw-bg-red-600',
    accept: async () => {
      try {
        await axios.delete(`/api/waitlists/${id}`);
        toast.add({
          severity: 'success',
          summary: 'Deleted!',
          detail: 'The waitlist entry has been deleted.',
          life: 3000
        });
        fetchWaitlists(currentFilter.value);
      } catch (error) {
        console.error('Error deleting waitlist entry:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete the waitlist entry. Please try again.',
          life: 3000
        });
      }
    }
  });
};

const moveToAppointments = async (waitlist) => {
  confirm.require({
    message: 'Are you sure you want to move this waitlist entry to appointments?',
    header: 'Move to Appointments?',
    icon: 'pi pi-question-circle',
    rejectClass: 'tw-bg-gray-500 tw-text-white tw-border-gray-500 hover:tw-bg-gray-600',
    acceptClass: 'tw-bg-blue-500 tw-text-white tw-border-blue-500 hover:tw-bg-blue-600',
    accept: async () => {
      try {
        await axios.post(`/api/waitlists/${waitlist.id}/add-to-appointments`, {
          doctor_id: props.doctorId,
          waitlist_id: waitlist.id,
          patient_id: waitlist.patient_id,
          appointmentId: waitlist.appointmentId ?? null,
          notes: waitlist.notes,
        });
        fetchWaitlists();
        toast.add({
          severity: 'success',
          summary: 'Success!',
          detail: 'Entry moved to appointments successfully.',
          life: 3000
        });
      } catch (error) {
        console.error('Error moving waitlist to appointments:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to move waitlist to appointments. Please try again.',
          life: 3000
        });
      }
    }
  });
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

const updateImportance = async (waitlistId, importance) => {
  try {
    await axios.put(`/api/waitlists/${waitlistId}`, { importance });
    fetchWaitlists(currentFilter.value);
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: 'Importance updated successfully.',
      life: 3000
    });
  } catch (error) {
    console.error('Error updating importance:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update importance. Please try again.',
      life: 3000
    });
  }
};

const filterWaitlists = (importance = null) => {
  currentFilter.value = { importance };
  fetchWaitlists(currentFilter.value);
};

const clearFilters = () => {
  currentFilter.value = { importance: null };
  fetchWaitlists(currentFilter.value);
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
  // Handle the specific mapping: 0 = Normal, 1 = Urgent
  if (importance === 0) return 'Normal';
  if (importance === 1) return 'Urgent';
  
  // Fallback to options array if available
  const option = importanceOptions.value.find(opt => opt.value === importance);
  return option ? option.label : 'Unknown';
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  fetchImportanceOptions();
  fetchWaitlists();
});
</script>

<template>
  <Dialog 
    v-model:visible="showModal" 
    modal 
    :header="'Waitlist'"
    :style="{ width: '90vw', maxWidth: '1200px' }"
    class="tw-p-0"
    @hide="closeModal"
  >
    <template #header>
      <div class="tw-flex tw-items-center tw-gap-2">
        <i class="pi pi-users tw-text-blue-600"></i>
        <span class="tw-text-xl tw-font-semibold tw-text-gray-800">Waitlist</span>
      </div>
    </template>

    <div class="tw-p-6">
      <!-- Filter Section -->
      <div class="tw-mb-6 tw-flex tw-flex-wrap tw-gap-2 tw-justify-end">
        <Button
          v-for="option in importanceOptions"
          :key="option.value"
          :label="option.label"
          :icon="option.icon"
          size="small"
          :severity="currentFilter.importance === option.value ? option.color : 'secondary'"
          :outlined="currentFilter.importance !== option.value"
          @click="filterWaitlists(option.value)"
          class="tw-text-sm"
        />
        <Button
          label="Clear Filters"
          icon="pi pi-filter-slash"
          size="small"
          severity="info"
          @click="clearFilters"
          class="tw-text-sm"
        />
      </div>

      <!-- Data Table -->
      <DataTable 
        :value="waitlists" 
        :loading="loading"
        stripedRows
        showGridlines
        responsiveLayout="scroll"
        class="tw-shadow-lg tw-rounded-lg tw-overflow-hidden"
        :paginator="waitlists.length > 10"
        :rows="10"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
      >
        <template #empty>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-inbox tw-text-4xl tw-text-gray-400 tw-mb-4"></i>
            <p class="tw-text-gray-600 tw-text-lg">No waitlist entries found</p>
          </div>
        </template>

        <template #loading>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-spin pi-spinner tw-text-2xl tw-text-blue-500"></i>
            <p class="tw-text-gray-600 tw-mt-2">Loading waitlist...</p>
          </div>
        </template>

        <Column field="patient.name" header="Patient Name" sortable class="tw-min-w-48">
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                <i class="pi pi-user tw-text-blue-600"></i>
              </div>
              <div>
                <div class="tw-font-semibold tw-text-gray-800">{{ data.patient_first_name  || 'N/A' }} {{ data.patient_last_name }}</div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="patient.phone" header="Patient Phone" sortable class="tw-min-w-40">
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-phone tw-text-green-600"></i>
              <span class="tw-text-gray-700">{{ data.patient_phone || 'N/A' }}</span>
            </div>
          </template>
        </Column>

        <Column field="importance" header="Importance" sortable class="tw-min-w-32">
          <template #body="{ data }">
            <Tag 
              :value="getImportanceLabel(data.importance)" 
              :severity="getImportanceSeverity(data.importance)"
              class="tw-font-medium"
            />
          </template>
        </Column>

        <Column field="created_at" header="Created At" sortable class="tw-min-w-44">
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-calendar tw-text-gray-500"></i>
              <span class="tw-text-gray-700 tw-text-sm">{{ formatDate(data.created_at) }}</span>
            </div>
          </template>
        </Column>

        <Column header="Actions" class="tw-min-w-48">
          <template #body="{ data }">
            <div class="tw-flex tw-gap-2">
              <Button
                icon="pi pi-pencil"
                size="small"
                severity="info"
                outlined
                @click="openAddModal(data)"
                v-tooltip.top="'Edit'"
                class="tw-w-8 tw-h-8"
              />
              <Button
                icon="pi pi-calendar-plus"
                size="small"
                severity="success"
                outlined
                @click="moveToAppointments(data)"
                v-tooltip.top="'Move to Appointments'"
                class="tw-w-8 tw-h-8"
              />
              <Dropdown
                :options="importanceOptions"
                optionLabel="label"
                optionValue="value"
                :modelValue="data.importance"
                @change="updateImportance(data.id, $event.value)"
                placeholder="Importance"
                class="tw-w-32"
                size="small"
              >
                <template #value="{ value }">
                  <Tag 
                    :value="getImportanceLabel(value)" 
                    :severity="getImportanceSeverity(value)"
                    class="tw-text-xs"
                  />
                </template>
                <template #option="{ option }">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i :class="option.icon" class="tw-text-sm"></i>
                    <span>{{ option.label }}</span>
                  </div>
                </template>
              </Dropdown>
              <Button
                icon="pi pi-trash"
                size="small"
                severity="danger"
                outlined
                @click="deleteWaitlist(data.id)"
                v-tooltip.top="'Delete'"
                class="tw-w-8 tw-h-8"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-2">
        <Button
          label="Close"
          icon="pi pi-times"
          severity="secondary"
          @click="closeModal"
          class="tw-px-4 tw-py-2"
        />
      </div>
    </template>
  </Dialog>

  <!-- Confirm Dialog -->
  <ConfirmDialog />

  <!-- Add/Edit Modal -->
  <AddWaitlistModal
    v-if="showAddModal"
    :show="showAddModal"
    :editMode="isEditMode"
    :waitlist="selectedWaitlist"
    :specializationId="specializationId"
    @close="closeAddModal"
    @save="handleSave"
    @update="handleUpdate"
  />
</template>

<style scoped>
/* Custom styles for PrimeVue components with Tailwind */
:deep(.p-dialog) {
  @apply tw-rounded-xl tw-shadow-2xl;
}

:deep(.p-dialog-header) {
  @apply tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border-b tw-border-gray-200;
}

:deep(.p-dialog-content) {
  @apply tw-p-0;
}

:deep(.p-datatable) {
  @apply tw-border-0;
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

:deep(.p-button:hover) {
  @apply tw-transform tw-scale-105;
}

:deep(.p-tag) {
  @apply tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium;
}

:deep(.p-dropdown) {
  @apply tw-border tw-border-gray-300 tw-rounded-md;
}

:deep(.p-paginator) {
  @apply tw-bg-gray-50 tw-border-t tw-border-gray-200;
}
</style>