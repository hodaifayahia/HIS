<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import ViewAppointmentsModal from '../../Components/appointments/ViewAppointmentsModal.vue';

const props = defineProps({
  doctorId: { type: Number, required: true },
  days: { type: Number, default: null },
  date: { type: String, default: null } // Accept the date prop
});

const emit = defineEmits(['timeSelected', 'dateSelected']);

// State
const selectedDate = ref(new Date());
const selectedTime = ref('09:00');
const showViewModal = ref(false);
const appointments = ref([]);
const loading = ref(false);

// Watch for changes in the date prop and update the internal state
watch(() => props.date, (newDate) => {
  if (newDate) {
    selectedDate.value = new Date(newDate);
  }
}, { immediate: true });

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

// Handle time input - emit to parent
const handleTimeChange = (event) => {
  selectedTime.value = event.target.value;
  emit('timeSelected', selectedTime.value);
};

// Handle date change - emit to parent
const handleDateChange = () => {
  emit('dateSelected', formattedDate.value);
};

// Watch for date changes
watch(selectedDate, () => {
  handleDateChange();
}, { immediate: true });

// Fetch appointments for the selected date
const fetchAppointments = async () => {
  if (!selectedDate.value || !props.doctorId) return;
  
  loading.value = true;
  try {
    const response = await axios.get(`/api/appointments/${props.doctorId}`, {
      params: {
        doctor_id: props.doctorId,
        date: formattedDate.value
      }
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

const isDateDisabled = (date) => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return date < today;
};
</script>

<template>
  <div class="appointment-selection-container">
    <div class="row g-4">
      <!-- Date Selection Card -->
      <div class="col-md-6">
        <div class="selection-card">
          <h6 class="form-label mb-3">
            <i class="fas fa-calendar-alt me-2"></i>
            Select Date
          </h6>
          <Datepicker 
            v-model="selectedDate"
            :enable-time-picker="false" 
            :disabled-dates="isDateDisabled"
            :format="formatDate"
            :min-date="new Date()"
            :clearable="true"
            :close-on-select="true"
            :auto-apply="true"
            placeholder="Select appointment date"
            class="custom-datepicker"
            input-class-name="form-control"
          />
        </div>
      </div>

      <!-- Time Selection Card -->
      <div class="col-md-6">
        <div class="selection-card">
          <h6 class="form-label mb-3">
            <i class="fas fa-clock me-2"></i>
            Select Time
          </h6>
          <div class="time-input-group">
            <input 
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

    <!-- Current Selection Summary -->
    <div v-if="selectedDate && selectedTime" class="current-selection-summary mt-4">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title">
            <i class="fas fa-check-circle text-success me-2"></i>
            Force Appointment Selection
          </h6>
          <div class="row">
            <div class="col-md-6">
              <div class="selected-detail">
                <i class="fas fa-calendar detail-icon"></i>
                <strong>Date:</strong> {{ formatDate(selectedDate) }}
              </div>
            </div>
            <div class="col-md-6">
              <div class="selected-detail">
                <i class="fas fa-clock detail-icon"></i>
                <strong>Time:</strong> {{ selectedTime }}
              </div>
            </div>
          </div>
          <div class="mt-3">
            <button 
              type="button"
              @click="viewAppointments" 
              class="btn btn-info btn-sm"
              :disabled="loading"
            >
              <i class="fas fa-eye me-2"></i>
              {{ loading ? 'Loading...' : 'View Existing Appointments' }}
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
/* Your existing styles */
.appointment-selection-container {
  max-width: 900px;
  margin: 2rem auto;
  padding: 1.5rem;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.selection-card {
  background: linear-gradient(145deg, #f0f2f5 0%, #e6e9ee 100%);
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: 1px solid #e0e4e8;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.form-label {
  margin-bottom: 0.75rem;
  font-weight: 600;
  color: #495057;
}

.form-control {
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background-color: #ffffff;
}

.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  outline: none;
}

.time-input-group {
  position: relative;
}

.time-input {
  padding-right: 45px;
}

.time-icon {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  pointer-events: none;
}

.current-selection-summary {
  background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
  border-radius: 12px;
  padding: 0;
  border: 2px solid #28a745;
}

.current-selection-summary .card {
  background: transparent;
  border: none;
}

.current-selection-summary .card-title {
  color: #28a745;
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.selected-detail {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.detail-icon {
  margin-right: 8px;
  color: #28a745;
  width: 16px;
}

.btn-info {
  background-color: #17a2b8;
  border-color: #17a2b8;
  color: white;
  font-weight: 500;
  border-radius: 6px;
  padding: 8px 16px;
  transition: all 0.2s ease;
}

.btn-info:hover {
  background-color: #138496;
  border-color: #117a8b;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .appointment-selection-container {
    margin: 1rem;
    padding: 1rem;
  }
}
</style>