<script setup>
import { ref, computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const props = defineProps({
  appointments: {
    type: Array,
    default: () => []
  },
  filteredAppointments: {
    type: Array,
    default: () => []
  },
  selectedAppointments: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  role: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:selectedAppointments', 'transfer-appointments', 'export-pdf']);

const selectedAppointmentsLocal = computed({
  get: () => props.selectedAppointments,
  set: (value) => emit('update:selectedAppointments', value)
});

const transferAppointments = () => {
  emit('transfer-appointments');
};

const exportPDF = () => {
  emit('export-pdf');
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatTime = (timeString) => {
  if (!timeString) return '';
  return timeString.substring(0, 5); // Extract HH:MM from HH:MM:SS
};
</script>

<template>
  <div class="appointments-table-container">
    <!-- Action Buttons -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
      <div class="tw-flex tw-gap-2">
        <Button 
          @click="transferAppointments" 
          :disabled="selectedAppointments.length === 0"
          class="tw-bg-blue-500 hover:tw-bg-blue-600 tw-text-white tw-px-4 tw-py-2 tw-rounded tw-transition-colors"
          severity="info">
          Transfer Selected ({{ selectedAppointments.length }})
        </Button>
        <Button 
          @click="exportPDF" 
          class="tw-bg-green-500 hover:tw-bg-green-600 tw-text-white tw-px-4 tw-py-2 tw-rounded tw-transition-colors"
          severity="success">
          Export PDF
        </Button>
      </div>
    </div>

    <!-- DataTable -->
    <DataTable 
      v-model:selection="selectedAppointmentsLocal"
      :value="filteredAppointments" 
      :loading="loading"
      selectionMode="multiple" 
      dataKey="id"
      :paginator="true" 
      :rows="10"
      :rowsPerPageOptions="[5, 10, 20, 50]"
      paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
      currentPageReportTemplate="{first} to {last} of {totalRecords}"
      class="tw-w-full tw-shadow-lg tw-rounded-lg tw-overflow-hidden"
      tableClass="tw-min-w-full tw-bg-white"
      :scrollable="true"
      scrollHeight="600px">
      
      <!-- Selection Column -->
      <Column selectionMode="multiple" headerStyle="width: 3rem" class="tw-text-center"></Column>
      
      <!-- Index Column -->
      <Column 
        field="index" 
        header="#" 
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ index }">
          {{ index + 1 }}
        </template>
      </Column>
      
      <!-- Patient Name Column -->
      <Column 
        field="patient.name" 
        header="Patient Name" 
        :sortable="true"
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ data }">
          <div class="tw-font-medium tw-text-gray-900">
            {{ data.patient?.name || 'N/A' }}
          </div>
        </template>
      </Column>
      
      <!-- Phone Column -->
      <Column 
        field="patient.phone" 
        header="Phone" 
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ data }">
          {{ data.patient?.phone || 'N/A' }}
        </template>
      </Column>
      
      <!-- Date of Birth Column -->
      <Column 
        field="patient.date_of_birth" 
        header="Date of Birth" 
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ data }">
          {{ formatDate(data.patient?.date_of_birth) }}
        </template>
      </Column>
      
      <!-- Appointment Date Column -->
      <Column 
        field="appointment_date" 
        header="Appointment Date" 
        :sortable="true"
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ data }">
          {{ formatDate(data.appointment_date) }}
        </template>
      </Column>
      
      <!-- Appointment Time Column -->
      <Column 
        field="appointment_time" 
        header="Time" 
        :sortable="true"
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ data }">
          {{ formatTime(data.appointment_time) }}
        </template>
      </Column>
      
      <!-- Status Column -->
      <Column 
        field="status" 
        header="Status" 
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ data }">
          <span :class="`status-${data.status.color.toLowerCase()} tw-px-2 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium`">
            {{ data.status.name }}
          </span>
        </template>
      </Column>
      
      <!-- Doctor Name Column (only for non-doctor roles) -->
      <Column 
        v-if="role !== 'doctor'"
        field="doctor.name" 
        header="Doctor" 
        :sortable="true"
        headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
        bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
        <template #body="{ data }">
          <div class="tw-font-medium tw-text-gray-900">
            {{ data.doctor?.name || 'N/A' }}
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<style scoped>
@reference "../../../../resources/css/app.css";

.appointments-table-container {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Status badge styles */
.status-primary {
  @apply tw-bg-blue-100 tw-text-blue-800 tw-border tw-border-blue-200;
}

.status-success {
  @apply tw-bg-green-100 tw-text-green-800 tw-border tw-border-green-200;
}

.status-danger {
  @apply tw-bg-red-100 tw-text-red-800 tw-border tw-border-red-200;
}

.status-warning {
  @apply tw-bg-yellow-100 tw-text-yellow-800 tw-border tw-border-yellow-200;
}

.status-info {
  @apply tw-bg-cyan-100 tw-text-cyan-800 tw-border tw-border-cyan-200;
}

/* PrimeVue severity mappings */
.severity-primary {
  @apply tw-bg-blue-100 tw-text-blue-800 tw-border tw-border-blue-200;
}

.severity-secondary {
  @apply tw-bg-gray-100 tw-text-gray-800 tw-border tw-border-gray-200;
}

.severity-success {
  @apply tw-bg-green-100 tw-text-green-800 tw-border tw-border-green-200;
}

.severity-info {
  @apply tw-bg-cyan-100 tw-text-cyan-800 tw-border tw-border-cyan-200;
}

.severity-warning {
  @apply tw-bg-yellow-100 tw-text-yellow-800 tw-border tw-border-yellow-200;
}

.severity-danger {
  @apply tw-bg-red-100 tw-text-red-800 tw-border tw-border-red-200;
}

.severity-contrast {
  @apply tw-bg-gray-900 tw-text-white tw-border tw-border-gray-700;
}
</style>