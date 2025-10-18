<script setup>
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Button from 'primevue/button';
import Message from 'primevue/message';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  selectedAppointments: {
    type: Array,
    default: () => []
  },
  doctors: {
    type: Array,
    default: () => []
  },
  transferDate: {
    type: Date,
    default: null
  },
  transferDoctor: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'update:visible',
  'update:transferDate', 
  'update:transferDoctor',
  'confirm-transfer',
  'cancel-transfer'
]);

const localVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

const localTransferDate = computed({
  get: () => props.transferDate,
  set: (value) => emit('update:transferDate', value)
});

const localTransferDoctor = computed({
  get: () => props.transferDoctor,
  set: (value) => emit('update:transferDoctor', value)
});

const confirmTransfer = () => {
  emit('confirm-transfer');
};

const cancelTransfer = () => {
  emit('cancel-transfer');
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
  <Dialog 
    v-model:visible="localVisible" 
    modal 
    header="Transfer Appointments" 
    :style="{ width: '80vw', maxWidth: '1200px' }"
    :closable="true"
    class="transfer-modal">
    
    <div class="tw-space-y-6">
      <!-- Transfer Details Alert -->
      <Message 
        severity="info" 
        :closable="false"
        class="tw-mb-4">
        You are about to transfer {{ selectedAppointments.length }} appointment(s). Please select the new doctor and date.
      </Message>

      <!-- Transfer Options -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4 tw-mb-6">
        <!-- Doctor Selection -->
        <div class="tw-space-y-2">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
            Select New Doctor
          </label>
          <Dropdown 
            v-model="localTransferDoctor"
            :options="doctors" 
            optionLabel="name" 
            placeholder="Choose a doctor"
            class="tw-w-full"
            :loading="loading">
            <template #option="{ option }">
              <div class="tw-flex tw-items-center tw-space-x-2">
                <div 
                  class="tw-w-3 tw-h-3 tw-rounded-full" 
                  :style="{ backgroundColor: option.color }"></div>
                <span>{{ option.name }}</span>
              </div>
            </template>
            <template #value="{ value }">
              <div v-if="value" class="tw-flex tw-items-center tw-space-x-2">
                <div 
                  class="tw-w-3 tw-h-3 tw-rounded-full" 
                  :style="{ backgroundColor: value.color }"></div>
                <span>{{ value.name }}</span>
              </div>
              <span v-else class="tw-text-gray-500">Choose a doctor</span>
            </template>
          </Dropdown>
        </div>

        <!-- Date Selection -->
        <div class="tw-space-y-2">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
            Select New Date
          </label>
          <Calendar 
            v-model="localTransferDate"
            dateFormat="yy-mm-dd"
            placeholder="Select date"
            class="tw-w-full"
            :minDate="new Date()"
            showIcon />
        </div>
      </div>

      <!-- Selected Appointments Table -->
      <div class="tw-space-y-4">
        <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">
          Appointments to Transfer ({{ selectedAppointments.length }})
        </h3>
        
        <DataTable 
          :value="selectedAppointments" 
          class="tw-w-full tw-shadow-sm tw-rounded-lg tw-overflow-hidden"
          tableClass="tw-min-w-full tw-bg-white"
          :scrollable="true"
          scrollHeight="300px">
          
          <!-- Patient Name Column -->
          <Column 
            field="patient.name" 
            header="Patient Name" 
            headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
            bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
            <template #body="{ data }">
              <div class="tw-font-medium tw-text-gray-900">
                {{ data.patient?.name || 'N/A' }}
              </div>
            </template>
          </Column>
          
          <!-- Current Date Column -->
          <Column 
            field="appointment_date" 
            header="Current Date" 
            headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
            bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
            <template #body="{ data }">
              {{ formatDate(data.appointment_date) }}
            </template>
          </Column>
          
          <!-- Current Time Column -->
          <Column 
            field="appointment_time" 
            header="Time" 
            headerClass="tw-bg-gray-50 tw-font-semibold tw-text-gray-700 tw-px-4 tw-py-3 tw-text-left"
            bodyClass="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">
            <template #body="{ data }">
              {{ formatTime(data.appointment_time) }}
            </template>
          </Column>
          
          <!-- Current Doctor Column -->
          <Column 
            field="doctor.name" 
            header="Current Doctor" 
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
    </div>

    <!-- Modal Footer -->
    <template #footer>
      <div class="tw-flex tw-justify-end tw-space-x-3">
        <Button 
          @click="cancelTransfer" 
          severity="secondary"
          class="tw-px-4 tw-py-2">
          Cancel
        </Button>
        <Button 
          @click="confirmTransfer" 
          :disabled="!transferDoctor || !transferDate || loading"
          :loading="loading"
          severity="primary"
          class="tw-px-4 tw-py-2">
          Transfer Appointments
        </Button>
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.transfer-modal :deep(.p-dialog-header) {
  @apply tw-bg-blue-50 tw-border-b tw-border-blue-200;
}

.transfer-modal :deep(.p-dialog-title) {
  @apply tw-text-blue-900 tw-font-semibold;
}

.transfer-modal :deep(.p-dialog-content) {
  @apply tw-p-6;
}

.transfer-modal :deep(.p-dialog-footer) {
  @apply tw-bg-gray-50 tw-border-t tw-border-gray-200 tw-px-6 tw-py-4;
}

/* Dropdown and Calendar styling */
.transfer-modal :deep(.p-dropdown) {
  @apply tw-border tw-border-gray-300 tw-rounded-md;
}

.transfer-modal :deep(.p-calendar) {
  @apply tw-border tw-border-gray-300 tw-rounded-md;
}

.transfer-modal :deep(.p-inputtext) {
  @apply tw-border tw-border-gray-300 tw-rounded-md tw-px-3 tw-py-2;
}

.transfer-modal :deep(.p-inputtext:focus) {
  @apply tw-border-blue-500 tw-ring-1 tw-ring-blue-500;
}
</style>