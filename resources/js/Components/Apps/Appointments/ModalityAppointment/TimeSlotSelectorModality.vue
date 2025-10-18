<script setup>
import axios from 'axios';
import { ref, watch, onMounted, computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  modalityId: {
    type: Number,
    required: true,
  },
  date: {
    type: String,
    required: true,
  },
  deletedslots: {
    type: Array,
    default: () => []
  },
  range: {
    type: Number,
    default: 0
  },
  forceAppointment: {
    type: Boolean,
    default: false
  },
  days: {
    type: Number,
    default: 0
  },
  slotType: {
    type: String,
    default: 'minutes'
  },
  durationDays: {
    type: Number,
    default: 1
  },
  userRole: {
    type: String,
    default: ''
  },
  userId: {
    type: Number,
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'timeSelected', 'availabilityChecked', 'forceRequested']);

const slots = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedTime = ref(props.modelValue);
const canForce = ref(false);
const checkingForcePermissions = ref(false);
const hasNoSlots = ref(false);

// Computed property to check if user can force appointments
const userCanForce = computed(() => {
  // Check if user is admin or superadmin
  const isAdmin = ['admin', 'superadmin', 'SuperAdmin'].includes(props.userRole?.toLowerCase());
  
  // Check if user has specific force permissions
  const hasForcePermission = canForce.value;
  
  return isAdmin || hasForcePermission;
});

// Check if user can force appointments for this modality
const checkForcePermissions = async () => {
  if (!props.userId || !props.modalityId) {
    canForce.value = false;
    return;
  }

  checkingForcePermissions.value = true;
  try {
    const response = await axios.get('/api/modality-appointments/force-ability', {
      params: {
        modality_id: props.modalityId,
        user_id: props.userId
      }
    });

    if (response.data?.success) {
      canForce.value = response.data.data?.is_able_to_force || false;
    } else {
      canForce.value = false;
    }
  } catch (error) {
    console.error('Error checking force permissions:', error);
    canForce.value = false;
  } finally {
    checkingForcePermissions.value = false;
  }
};

const fetchTimeSlots = async () => {
  loading.value = true;
  error.value = null;
  hasNoSlots.value = false;

  try {
    // If we have deleted slots (from canceled appointments), use those
    if (props.deletedslots && props.deletedslots.length > 0) {
      slots.value = props.deletedslots.map(time => ({
        time: typeof time === 'string' ? time : time.time || time,
        available: true,
        isDeleted: true
      }));
      loading.value = false;
      hasNoSlots.value = false;
      return;
    }

    console.log('Fetching time slots for date:', props.date, 'modalityId:', props.modalityId);
    
    const response = await axios.get('/api/modality-appointments/checkModalityAvailability', {
      params: {
        date: props.date,
        modality_id: props.modalityId,
        include_slots: true,
        duration_days: props.slotType === 'days' ? props.durationDays : null,
      }
    });

    console.log('Response from checkModalityAvailability:', response.data);

    emit('availabilityChecked', {
      hasSlots: !!response.data.data?.available_slots?.length,
      nextAvailableDate: response.data.data?.next_available_date
    });

    if (response.data.data?.available_slots && Array.isArray(response.data.data.available_slots) && response.data.data.available_slots.length > 0) {
      slots.value = response.data.data.available_slots.map(time => ({
        time: typeof time === 'string' ? time : time.time || time,
        available: true,
        isDeleted: false
      }));
      hasNoSlots.value = false;
      console.log('Available slots:', slots.value);
    } else {
      // No slots available - check if user can force
      slots.value = [];
      hasNoSlots.value = true;
      await checkForcePermissions();
    }
  } catch (err) {
    console.error('Error fetching time slots:', err);
    error.value = err.message || 'An error occurred while fetching time slots.';
    slots.value = [];
    hasNoSlots.value = true;
    // Check force permissions even on error
    await checkForcePermissions();
  } finally {
    loading.value = false;
  }
};

const selectTimeSlot = (time) => {
  selectedTime.value = time;
  emit('update:modelValue', time);
  emit('timeSelected', time);
};

const handleForceAppointment = () => {
  emit('forceRequested', {
    modalityId: props.modalityId,
    date: props.date,
    durationDays: props.durationDays
  });
};

// Computed property to show appropriate message
const noSlotsMessage = computed(() => {
  if (checkingForcePermissions.value) {
    return 'Checking permissions...';
  }
  
  if (hasNoSlots.value && userCanForce.value) {
    return 'No time slots available for this date. You can force an appointment using the button below.';
  }
  
  if (hasNoSlots.value && !userCanForce.value) {
    return 'No time slots available for this date. Please select another date or contact an administrator.';
  }
  
  return 'No time slots available for this date.';
});

onMounted(() => {
  fetchTimeSlots();
});

watch([() => props.date, () => props.range, () => props.modalityId, () => props.forceAppointment, () => props.days, () => props.deletedslots], fetchTimeSlots);
watch(() => props.modelValue, (newValue) => {
  selectedTime.value = newValue;
});
</script>

<template>
  <div class="time-slots-container">
    <label class="text-muted mb-2 block">
      {{ slotType === 'days' ? 'Select Start Time' : date }}
    </label>
    
    <div v-if="slotType === 'days'" class="alert alert-info mb-3">
      <i class="fas fa-info-circle me-2"></i>
      This will book {{ durationDays }} consecutive day(s) starting from the selected time.
    </div>
    
    <div v-if="loading" class="text-center py-4">
      <span class="spinner-border spinner-border-sm me-2"></span>
      Loading available slots...
    </div>
    
    <div v-else-if="error" class="py-4">
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ error }}
      </div>
      
      <!-- Show force button if user has permissions and there's an error -->
      <div v-if="userCanForce && !checkingForcePermissions" class="mt-3">
        <button 
          @click="handleForceAppointment"
          class="btn btn-warning btn-sm"
          type="button"
        >
          <i class="fas fa-bolt me-2"></i>
          Force Appointment
        </button>
      </div>
    </div>
    
    <div v-else-if="hasNoSlots" class="py-4">
      <div class="alert alert-warning">
        <i class="fas fa-info-circle me-2"></i>
        {{ noSlotsMessage }}
      </div>
      
      <!-- Show force button if user has permissions -->
      <div v-if="userCanForce && !checkingForcePermissions" class="mt-3">
        <button 
          @click="handleForceAppointment"
          class="btn btn-warning btn-sm"
          type="button"
        >
          <i class="fas fa-bolt me-2"></i>
          Force Appointment
        </button>
        <small class="d-block text-muted mt-1">
          This will create an appointment outside normal scheduling rules.
        </small>
      </div>
      
      <!-- Show permission checking state -->
      <div v-else-if="checkingForcePermissions" class="mt-3">
        <span class="spinner-border spinner-border-sm me-2"></span>
        <small class="text-muted">Checking permissions...</small>
      </div>
    </div>
    
    <div v-else-if="slots.length > 0" class="grid grid-cols-4 gap-2">
      <button
        v-for="slot in slots"
        :key="slot.time"
        type="button"
        class="btn m-2"
        :class="{
          'btn-primary': selectedTime === slot.time,
          'btn-outline-secondary': selectedTime !== slot.time,
          'btn-outline-danger': slot.isDeleted
        }"
        @click="selectTimeSlot(slot.time)"
      >
        {{ slot.time }}
        <small v-if="slotType === 'days'" class="d-block text-xs">
          Start time
        </small>
        <small v-if="slot.isDeleted" class="d-block text-xs text-danger">
          From canceled
        </small>
      </button>
    </div>
    
    <div class="mt-4">
      <p v-if="selectedTime" class="text-success">
        <strong>Selected {{ slotType === 'days' ? 'start' : '' }} time:</strong> {{ selectedTime }}
        <span v-if="slotType === 'days'">
          <br><small>Duration: {{ durationDays }} day(s)</small>
        </span>
      </p>
      <p v-else-if="!hasNoSlots && !loading && !error" class="text-muted">
        Please select an available {{ slotType === 'days' ? 'start' : '' }} time slot
      </p>
    </div>
  </div>
</template>

<!-- Styles remain the same -->

<style scoped>
.time-slots-container {
  margin-top: 1rem;
}

.grid {
  display: grid;
}

.grid-cols-4 {
  grid-template-columns: repeat(4, 1fr);
}

.gap-2 {
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem;
  border: 1px solid #ccc;
  background: white;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
  color: white;
}

.btn-outline-secondary {
  border-color: #6c757d;
  color: #6c757d;
}

.btn-outline-secondary:hover {
  background-color: #6c757d;
  color: white;
}

.btn-outline-danger {
  border-color: #dc3545;
  color: #dc3545;
}

.btn-outline-danger:hover {
  background-color: #dc3545;
  color: white;
}

.btn-warning {
  background-color: #ffc107;
  border-color: #ffc107;
  color: #212529;
}

.btn-warning:hover {
  background-color: #e0a800;
  border-color: #d39e00;
}

.alert {
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}

.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
}

.alert-warning {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #856404;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
}

.text-xs {
  font-size: 0.75rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.text-success {
  color: #28a745;
}

.text-muted {
  color: #6c757d;
}

.text-danger {
  color: #dc3545;
}
</style>