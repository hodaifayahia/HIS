<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import ViewAppointmentsModal from './ViewAppointmentsModalModality.vue';

const props = defineProps({
  modalityId: { type: Number, required: true },
});

const emit = defineEmits(['timeSelected', 'dateSelected']);

// State
const selectedDate = ref(new Date());
const selectedTime = ref('09:00');
const showViewModal = ref(false);
const appointments = ref([]);
const loading = ref(false);

// Format the selected date as yyyy-MM-dd for API
const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  const date = selectedDate.value;
  return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
});

// Custom date formatter for display
const formatDate = (date) => {
  if (!date) return '';
  return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
};

// Handle time input
const handleTimeChange = (event) => {
  selectedTime.value = event.target.value;
  emit('timeSelected', selectedTime.value);
};

// Handle date change
const handleDateChange = () => {
  emit('dateSelected', formattedDate.value);
};

// Fetch appointments for the selected date
const fetchAppointments = async () => {
  if (!selectedDate.value || !props.modalityId) return;

  try {
    loading.value = true;
    const response = await axios.get(`/api/modality-appointments/${props.modalityId}/appointments`, {
      params: {
        date: formattedDate.value,
      },
    });

    appointments.value = response.data.data || [];
  } catch (error) {
    console.error('Error fetching appointments:', error);
    appointments.value = [];
  } finally {
    loading.value = false;
  }
};

// Show appointments modal
const viewAppointments = () => {
  fetchAppointments();
  showViewModal.value = true;
};

// Watch for date changes
watch(selectedDate, () => {
  handleDateChange();
}, { immediate: true });
</script>

<template>
  <div class="appointment-selection-container">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="selection-card">
          <label for="datepicker" class="form-label">Choose a Date</label>
          <Datepicker
            v-model="selectedDate"
            id="datepicker"
            :enable-time-picker="false"
            :format-locale="{ code: 'en-GB', monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] }"
            :auto-apply="true"
            :format="formatDate"
            :min-date="new Date()"
            class="custom-datepicker"
          >
            <template #input="{ value }">
              <input type="text" :value="value" class="form-control datepicker-input" readonly placeholder="Select a date..." />
              <i class="fas fa-calendar-alt datepicker-icon"></i>
            </template>
          </Datepicker>
        </div>
      </div>

      <div class="col-md-6">
        <div class="selection-card">
          <label for="time-input" class="form-label">Select a Time</label>
          <div class="time-input-group">
            <input
              id="time-input"
              type="time"
              v-model="selectedTime"
              @change="handleTimeChange"
              class="form-control time-input"
              step="300"
            />
            <i class="fas fa-clock time-icon"></i>
          </div>
        </div>
      </div>
    </div>

    <hr class="my-5" />

    <div class="card current-selection-summary shadow-lg">
      <div class="card-body text-center">
        <h5 class="card-title mb-3">Your Selected Appointment</h5>
        <div class="row align-items-center">
          <div class="col-md-5 mb-3 mb-md-0">
            <p class="mb-0 selected-detail">
              <i class="fas fa-calendar-check me-2 detail-icon"></i>
              <strong>Date:</strong> {{ formatDate(selectedDate) }}
            </p>
          </div>
          <div class="col-md-5 mb-3 mb-md-0">
            <p class="mb-0 selected-detail">
              <i class="fas fa-clock me-2 detail-icon"></i>
              <strong>Time:</strong> {{ selectedTime }}
            </p>
          </div>
          <div class="col-md-2">
            <button
              @click="viewAppointments"
              class="btn btn-primary btn-view-appointments w-100"
              :disabled="loading"
            >
              <template v-if="loading">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Loading...
              </template>
              <template v-else>
                <div class="w-full">
                  <i class="fas fa-eye me-2"></i>
                  View Appointments
                </div>
              </template>
            </button>
          </div>
        </div>
      </div>
    </div>

    <ViewAppointmentsModal
      :show="showViewModal"
      :date="formattedDate"
      :appointments="appointments"
      :loading="loading"
      @close="showViewModal = false"
    />
  </div>
</template>

<style scoped>
/* Add styles similar to SimpleCalendar.vue */
</style>