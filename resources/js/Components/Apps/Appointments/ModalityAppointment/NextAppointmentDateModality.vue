<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import axios from 'axios';
import { formatDateHelper } from '@/Components/helper.js';
import TimeSlotSelectorModality from './TimeSlotSelectorModality.vue';
import SimpleCalendarModality from './SimpleCalendarModality.vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  modalityId: { type: Number, required: true },
  initialDays: { type: Number, default: 0 },
});

const emit = defineEmits(['update:days', 'update:range', 'dateSelected', 'timeSelected']);

const days = ref(props.initialDays);
const nextAppointmentDate = ref('');
const period = ref('');
const selectedAppointment = ref(null);
const range = ref(0);
const isForcingAppointment = ref(false);
const modality = ref(null);
const maxSlotDuration = ref(0);
const showTimeSlots = ref(false);

const authStore = useAuthStore();
const isAbleToForce = ref(false);

// Computed property to check if user can force appointments
const userCanForce = computed(() => {
  // Check if user is admin or superadmin
  const isAdmin = ['admin', 'superadmin', 'SuperAdmin'].includes(authStore.user?.role?.toLowerCase());
  
  // Check if user has specific force permissions
  const hasForcePermission = isAbleToForce.value;
  
  return isAdmin || hasForcePermission;
});

// Computed property for max days allowed
const maxDaysAllowed = computed(() => {
  return modality.value?.slot_type === 'days' ? modality.value?.time_slot_duration || 1 : null;
});

const fetchModalityInfo = async () => {
  try {
    const response = await axios.get(`/api/modalities/${props.modalityId}`);
    modality.value = response.data.data;
    maxSlotDuration.value = modality.value.time_slot_duration || 0;
  } catch (error) {
    console.error('Error fetching modality info:', error);
  }
};

const checkForcePermission = async () => {
  try {
    const response = await axios.get('/api/modality-appointments/force-ability', {
      params: {
        modality_id: props.modalityId,
        user_id: authStore.user?.data?.id || authStore.user?.id,
      },
    });
    
    if (response.data?.success) {
      isAbleToForce.value = response.data.data?.is_able_to_force || false;
    }
  } catch (error) {
    console.error('Error checking force permission:', error);
    isAbleToForce.value = false;
  }
};

const checkAvailability = async () => {
  if (!days.value) {
    nextAppointmentDate.value = '';
    period.value = '';
    selectedAppointment.value = null;
    showTimeSlots.value = false;
    emit('dateSelected', null);
    return;
  }

  // For days slot type, validate max duration
  if (modality.value?.slot_type === 'days' && days.value > maxSlotDuration.value) {
    days.value = maxSlotDuration.value;
    return;
  }

  try {
    const response = await axios.get('/api/modality-appointments/checkModalityAvailability', {
      params: {
        days: days.value,
        modality_id: props.modalityId,
        range: range.value,
        include_slots: true, // Always include slots to show time selection
        duration_days: modality.value?.slot_type === 'days' ? days.value : null,
      },
    });

    console.log('Availability check response:', response.data);

    if (response.data.success && response.data.data.next_available_date) {
      nextAppointmentDate.value = response.data.data.next_available_date;
      period.value = response.data.data.period || '';
      showTimeSlots.value = true; // Show time slots for both types
      emit('dateSelected', nextAppointmentDate.value);
      
      console.log('Next appointment date found:', nextAppointmentDate.value);
    } else {
      nextAppointmentDate.value = '';
      period.value = '';
      showTimeSlots.value = false;
      emit('dateSelected', null);
    }
  } catch (err) {
    console.error('Error checking availability:', err);
    nextAppointmentDate.value = '';
    period.value = '';
    showTimeSlots.value = false;
  }
};

const forceAppointment = () => {
  isForcingAppointment.value = true;
};

const handleDateSelected = (date) => {
  emit('dateSelected', date);
};

const handleTimeSelected = (time) => {
  console.log('Time selected in NextAppointmentDateModality:', time);
  emit('timeSelected', time);
};

watch(() => days.value, (newValue) => {
  emit('update:days', newValue);
  showTimeSlots.value = false; // Reset time slots when days change
  checkAvailability();
});

watch(() => range.value, (newValue) => {
  emit('update:range', newValue);
  showTimeSlots.value = false; // Reset time slots when range changes
  checkAvailability();
});

onMounted(() => {
  fetchModalityInfo();
  checkForcePermission();
  if (days.value > 0) {
    checkAvailability();
  }
});
</script>

<template>
  <div class="w-100" style="width: 200px;">
    <label for="days" class="text-muted">
      {{ modality?.slot_type === 'days' ? 'Duration (Days)' : 'Days' }}
    </label>
    <input 
      type="number" 
      v-model="days" 
      class="form-control" 
      id="days" 
      :placeholder="modality?.slot_type === 'days' ? `Max ${maxDaysAllowed} days` : 'Enter number of days'"
      :max="maxDaysAllowed" 
      :min="1"
    />
    <small v-if="modality?.slot_type === 'days'" class="text-muted">
      Maximum duration: {{ maxDaysAllowed }} day(s)
    </small>

    <div class="form-group mb-4" v-if="modality?.slot_type === 'minutes'">
      <label for="range" class="text-muted">Range (Optional)</label>
      <input
        class="form-control"
        type="number"
        id="range"
        placeholder="Enter the range"
        v-model="range"
      />
    </div>

    <div v-if="nextAppointmentDate" class="mt-2 text-info">
      <p><strong>Next appointment will be on:</strong> {{ formatDateHelper(nextAppointmentDate) }}</p>
      <p v-if="modality?.slot_type === 'days'"><strong>Duration:</strong> {{ days }} day(s)</p>
      <p v-if="period"><strong>Time from now:</strong> {{ period }}</p>
    </div>

    <div v-else-if="!nextAppointmentDate && days > 0" class="mt-2 text-center">
      <div v-if="userCanForce" class="mt-2">
        <div class="alert alert-warning mb-3">
          No available slots for the requested duration
        </div>
        <button @click="forceAppointment" class="btn btn-outline-secondary mt-2 mb-2">
          <i class="fas fa-exclamation-triangle me-2"></i>
          Force Appointment
        </button>
        <div v-if="isForcingAppointment">
          <SimpleCalendarModality
            :days="days"
            :modalityId="props.modalityId"
            @timeSelected="handleTimeSelected"
            @dateSelected="handleDateSelected"
          />
        </div>
      </div>
      <div v-else class="alert alert-info text-center w-50 m-auto" style="background-color: #5bc0de; color: white;">
        <p class="text-white" style="font-weight: bold; font-size: 1.1em;">No slots available at the moment.</p>
      </div>
    </div>
  </div>

  <!-- Show TimeSlotSelectorModality for both minutes and days slot types -->
  <div v-if="nextAppointmentDate && showTimeSlots" class="mt-4">
    <h6 class="text-muted mb-3">
      {{ modality?.slot_type === 'days' ? 'Select Start Time' : 'Available Time Slots' }}
    </h6>
    <TimeSlotSelectorModality
      :date="nextAppointmentDate"
      :range="range"
      :modality-id="props.modalityId"
      :slot-type="modality?.slot_type"
      :duration-days="days"
      :user-id="authStore.user?.data?.id || authStore.user?.id"
      :user-role="authStore.user?.role"
      @time-selected="handleTimeSelected"
    />
  </div>
</template>

<style scoped>
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