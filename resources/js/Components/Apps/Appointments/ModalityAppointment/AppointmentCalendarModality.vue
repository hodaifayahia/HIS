<script setup>
import { ref, onMounted, watch, computed } from 'vue'; // Add computed import
import axios from 'axios';
import TimeSlotSelectorModality from './TimeSlotSelectorModality.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import SimpleCalendarModality from './SimpleCalendarModality.vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  modalityId: {
    type: Number,
    required: true,
  },
  durationDays: {
    type: Number,
    default: 1,
  },
});

const authStore = useAuthStore();

const isAbleToForce = ref(false);

const checkForcePermission = async () => {
  try {
    const response = await axios.get('/api/modality-user-permissions/ability', {
      params: {
        modality_id: props.modalityId,
        user_id: authStore.user.data.id,
      },
    });
    isAbleToForce.value = response.data.data.is_able_to_force;
  } catch (error) {
    console.error('Error checking force permission:', error);
  }
};

const emit = defineEmits(['timeSelected', 'dateSelected']);
const selectedDate = ref('');
const availableSlots = ref([]);
const loading = ref(false);
const error = ref(null);
const nextAvailableDate = ref(null);
const showTimeSlots = ref(false);
const isForcingAppointment = ref(false);

const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  return `${selectedDate.value.getFullYear()}-${(selectedDate.value.getMonth() + 1).toString().padStart(2, '0')}-${selectedDate.value.getDate().toString().padStart(2, '0')}`;
});

const checkDateAvailability = async (date) => {
  if (!date) return;
  loading.value = true;
  error.value = null;
  showTimeSlots.value = false;

  try {
    const response = await axios.get('/api/modality-appointments/check-availability', {
      params: {
        modality_id: props.modalityId,
        date: date,
        duration_days: props.durationDays,
      }
    });

    if (response.data.success && response.data.data.available_slots) {
      availableSlots.value = response.data.data.available_slots;
      showTimeSlots.value = true;
      emit('dateSelected', date);
    } else {
      availableSlots.value = [];
      showTimeSlots.value = false;
      error.value = 'No available slots for this date';
    }
  } catch (err) {
    error.value = 'Failed to check availability for this date';
    availableSlots.value = [];
    showTimeSlots.value = false;
  } finally {
    loading.value = false;
  }
};

const handleTimeSelected = (time) => {
  emit('timeSelected', time);
};

const resetDateSelection = () => {
  selectedDate.value = null;
  availableSlots.value = [];
  showTimeSlots.value = false;
  nextAvailableDate.value = null;
  isForcingAppointment.value = false;
  error.value = null;
};

const forceAppointment = () => {
  isForcingAppointment.value = true;
};

const isDateDisabled = (date) => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return date < today;
};

watch(selectedDate, (newDate) => {
  if (newDate) {
    checkDateAvailability(formattedDate.value);
  }
});

watch(() => props.durationDays, () => {
  checkDateAvailability(selectedDate.value);
});

onMounted(() => {
  checkForcePermission();
});
</script>

<template>
  <div>
    <div class="mb-3">
      <label for="datepicker" class="form-label">Select Date</label>
      <Datepicker
        v-model="selectedDate"
        id="datepicker"
        :enable-time-picker="false"
        :disabled-dates="isDateDisabled"
        :format="(date) => `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`"
        :min-date="new Date()"
        style="display: block; width: 100%;"
      />
    </div>

    <div v-if="loading" class="text-center py-4">
      <span class="spinner-border spinner-border-sm me-2"></span>
      Loading available slots...
    </div>

    <div v-else-if="error && !isForcingAppointment" class="text-danger py-4">
      {{ error }}
    </div>

    <!-- Show time slots when available -->
    <div v-if="selectedDate && showTimeSlots && availableSlots.length > 0" class="card mb-3 shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Available Time Slots for {{ formattedDate }}</h6>
        <TimeSlotSelectorModality
          :date="formattedDate"
          :modalityId="props.modalityId"
          @timeSelected="handleTimeSelected"
          class="mt-3"
        />
        <button @click="resetDateSelection" class="btn btn-outline-secondary btn-sm mt-3">
          Reset Selection
        </button>
      </div>
    </div>

    <!-- Show force appointment option when no slots available -->
    <div v-else-if="selectedDate && !showTimeSlots && !loading" class="mt-2 text-center">
      <div v-if="isAbleToForce || authStore.user.data.role === 'admin' || authStore.user.data.role === 'SuperAdmin'">
        <div class="alert alert-warning mb-3">
          No available slots for {{ formattedDate }}
        </div>
        <button @click="forceAppointment" class="btn btn-warning mt-2 mb-2">
          <i class="fas fa-exclamation-triangle me-2"></i>
          Force Appointment
        </button>
        <div v-if="isForcingAppointment">
          <SimpleCalendarModality
            :date="formattedDate"
            :modalityId="props.modalityId"
            @timeSelected="handleTimeSelected"
            @dateSelected="emit('dateSelected', $event)"
          />
        </div>
      </div>
      <div v-else class="alert alert-info text-center">
        <i class="fas fa-info-circle me-2"></i>
        No appointments available on this date.
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 0.2rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.alert {
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}

.alert-warning {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #856404;
}

.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
}
</style>