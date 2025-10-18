<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import TimeSlotSelectorModality from './TimeSlotSelectorModality.vue';
import { formatDateHelper } from '@/Components/helper.js';
import Dialog from 'primevue/dialog';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  modalityId: {
    type: Number,
    required: true,
  },
  isEmpty: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['dateSelected', 'timeSelected']);

const authStore = useAuthStore();

const availableAppointments = ref({
  canceled_appointments: [],
  normal_appointments: null,
});

const selectedAppointment = ref(null);
const selectedValue = ref('');
const availableTimes = ref([]);
const selectedType = ref('');
const timeSlotKey = ref(0);
const loading = ref(false);
const error = ref(null);
const showForceModal = ref(false);
const forceDate = ref('');
const forceTime = ref('');

// Fetch available appointments for the selected modality
const fetchAvailableAppointments = async () => {
  if (!props.modalityId) {
    console.warn('No modality ID provided');
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get(`/api/modality-appointments/${props.modalityId}/available`);

    if (response.data.success) {
      const canceledAppointments = response.data.data.canceled_appointments || [];
      const normalAppointments = response.data.data.normal_appointments || null;

      // Filter out canceled appointments that have been rebooked
      const filteredCanceledAppointments = canceledAppointments.filter(
        (appointment) => appointment.status !== 'booked' // Ensure only available canceled appointments are shown
      );

      availableAppointments.value = {
        canceled_appointments: filteredCanceledAppointments,
        normal_appointments: normalAppointments,
      };
    } else {
      error.value = response.data.message || 'Failed to fetch available appointments';
    }
  } catch (err) {
    console.error('Error fetching available appointments:', err);
    error.value = 'Failed to fetch available appointments. Please try again.';
  } finally {
    loading.value = false;
  }
};
// Update selectAppointment function
const selectAppointment = async () => {
  if (!selectedValue.value) return;

  const [type, date] = selectedValue.value.split('|');
  selectedType.value = type; // Set the type ('canceled' or 'normal')

  if (type === 'canceled') {
    selectedAppointment.value = availableAppointments.value.canceled_appointments.find(
      app => formatDateHelper(app.date) === date
    );
    availableTimes.value = selectedAppointment.value?.available_times || [];
  } else {
    selectedAppointment.value = availableAppointments.value.normal_appointments;
    availableTimes.value = selectedAppointment.value?.available_times || [];
  }

  if (selectedAppointment.value) {
    timeSlotKey.value++; // Force TimeSlotSelectorModality to remount
    emit('dateSelected', selectedAppointment.value.date, type); // Pass the type
  }
};

// Update handleTimeSelected function
const handleTimeSelected = (time) => {
  emit('timeSelected', time, selectedType.value); // Pass the type
};

// Force appointment booking
const submitForceAppointment = async () => {
  if (!forceDate.value || !forceTime.value) return;
  // Emit or call API to force book
  emit('forceAppointment', { date: forceDate.value, time: forceTime.value });
  showForceModal.value = false;
};

// Watch for changes in selectedValue
watch(selectedValue, () => {
  if (selectedValue.value) {
    selectAppointment();
  }
});

// Watch for changes in modalityId
watch(() => props.modalityId, (newModalityId) => {
  if (newModalityId) {
    // Reset selected values
    selectedAppointment.value = null;
    selectedValue.value = '';
    availableTimes.value = [];
    timeSlotKey.value++;

    // Fetch new appointments
    fetchAvailableAppointments();
  }
});

// Fetch appointments when the component is mounted
onMounted(() => {
  if (props.modalityId) {
    fetchAvailableAppointments();
  }
});
</script>

<template>
  <div class="form-group mb-4">
    <label class="text-muted mb-2">Available Appointment Dates</label>
    
    <!-- Loading state -->
    <div v-if="loading" class="text-center py-4">
      <span class="spinner-border spinner-border-sm me-2"></span>
      Loading available appointments...
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="text-danger py-4">
      {{ error }}
    </div>
    
    <!-- No appointments available -->
    <div
      v-else-if="availableAppointments.canceled_appointments.length === 0 && !availableAppointments.normal_appointments"
      class="text-muted p-3 border rounded"
    >
      No available appointments found.
      <div v-if="authStore.user.role === 'admin' || authStore.user.role === 'SuperAdmin'" class="mt-3">
        <button class="btn btn-warning" @click="showForceModal = true">
          <i class="fas fa-exclamation-triangle me-2"></i>
          Force Appointment
        </button>
      </div>
    </div>

    <!-- Available appointments selector -->
    <div v-else>
      <select
        v-model="selectedValue"
        class="form-select form-control w-full mb-3"
      >
        <option disabled value="">Select an appointment</option>
        <optgroup v-if="availableAppointments.canceled_appointments.length > 0" label="Canceled Appointments">
          <option
            v-for="appointment in availableAppointments.canceled_appointments"
            :key="`canceled-${appointment.date}`"
            :value="'canceled|' + formatDateHelper(appointment.date)"
          >
            {{ formatDateHelper(appointment.date) }} ({{ appointment.available_times.length }} slots available)
          </option>
        </optgroup>
        <optgroup v-if="availableAppointments.normal_appointments" label="Next Available">
          <option
            :key="`normal-${availableAppointments.normal_appointments.date}`"
            :value="'normal|' + formatDateHelper(availableAppointments.normal_appointments.date)"
          >
            {{ formatDateHelper(availableAppointments.normal_appointments.date) }}
            <span v-if="availableAppointments.normal_appointments.available_times">
              ({{ availableAppointments.normal_appointments.available_times.length }} slots)
            </span>
          </option>
        </optgroup>
      </select>
    </div>

    <!-- Time slot selector -->
    <TimeSlotSelectorModality
      v-if="selectedAppointment && availableTimes.length > 0"
      :key="timeSlotKey"
      :deletedslots="selectedType === 'canceled' ? availableTimes : []"
      :date="selectedAppointment.date"
      :modalityId="props.modalityId"
      @timeSelected="handleTimeSelected"
      class="mt-4"
    />
    
    <!-- No time slots message -->
    <div v-else-if="selectedAppointment && availableTimes.length === 0" class="text-muted mt-4">
      No time slots available for the selected date.
    </div>

    <!-- Force Appointment Modal -->
    <Dialog v-model:visible="showForceModal" modal header="Force Appointment" :style="{ width: '40vw' }">
      <div>
        <label>Select Date</label>
        <input type="date" v-model="forceDate" class="form-control mb-2" />
        <label>Select Time</label>
        <input type="time" v-model="forceTime" class="form-control mb-2" />
        <button class="btn btn-success" @click="submitForceAppointment">Force Book</button>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.form-group {
  margin-bottom: 1.5rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>