<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  date: { type: String, required: true },
  appointments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['close']);

// Format date for display
const formattedDate = computed(() => {
  if (!props.date) return '';
  const date = new Date(props.date);
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date);
});

// Format time to 24h format
const formatTime = (time) => {
  if (!time) return '';
  // Handle different time formats
  if (time.includes('T')) {
    return new Date(time).toLocaleTimeString('en-GB', { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: false 
    });
  }
  return time;
};

// Get status color
const getStatusColor = (status) => {
  const statusMap = {
    0: 'primary',    // Scheduled
    1: 'success',    // Confirmed
    2: 'danger',     // Canceled
    3: 'warning',    // Pending
    4: 'info',       // Done
    5: 'warning'     // On Working
  };
  return statusMap[status] || 'secondary';
};

// Get status name
const getStatusName = (status) => {
  const statusMap = {
    0: 'Scheduled',
    1: 'Confirmed',
    2: 'Canceled',
    3: 'Pending',
    4: 'Done',
    5: 'On Working'
  };
  return statusMap[status] || 'Unknown';
};

// Close modal
const closeModal = () => {
  emit('close');
};

// Sort appointments by time
const sortedAppointments = computed(() => {
  return [...props.appointments].sort((a, b) => {
    const timeA = a.appointment_time;
    const timeB = b.appointment_time;
    return timeA.localeCompare(timeB);
  });
});
</script>

<template>
  <!-- Modal Backdrop -->
  <div 
    v-if="show" 
    class="modal-backdrop"
    @click="closeModal"
  >
    <!-- Modal Content -->
    <div 
      class="modal-content"
      @click.stop
    >
      <!-- Modal Header -->
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-calendar-day me-2"></i>
          Appointments for {{ formattedDate }}
        </h5>
        <button 
          type="button" 
          class="btn-close"
          @click="closeModal"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading appointments...</p>
        </div>

        <!-- No Appointments -->
        <div v-else-if="sortedAppointments.length === 0" class="text-center py-4">
          <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
          <h6 class="text-muted">No appointments found</h6>
          <p class="text-muted">There are no appointments scheduled for this date.</p>
        </div>

        <!-- Appointments List -->
        <div v-else class="appointments-list">
          <div 
            v-for="appointment in sortedAppointments" 
            :key="appointment.id"
            class="appointment-card"
          >
            <div class="appointment-time">
              {{ formatTime(appointment.appointment_time) }}
            </div>
            <div class="appointment-details">
              <div class="patient-name">
                <i class="fas fa-user me-2"></i>
                {{ appointment.patient?.firstname || appointment.patient_first_name }} 
                {{ appointment.patient?.lastname || appointment.patient_last_name }}
              </div>
              <div class="patient-phone" v-if="appointment.patient?.phone || appointment.phone">
                <i class="fas fa-phone me-2"></i>
                {{ appointment.patient?.phone || appointment.phone }}
              </div>
              <div class="appointment-notes" v-if="appointment.notes">
                <i class="fas fa-sticky-note me-2"></i>
                {{ appointment.notes }}
              </div>
            </div>
            <div class="appointment-status">
              <span 
                class="badge"
                :class="`bg-${getStatusColor(appointment.status?.value || appointment.status)}`"
              >
                {{ appointment.status?.name }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <div class="text-muted">
          Total appointments: {{ sortedAppointments.length }}
        </div>
        <button 
          type="button" 
          class="btn btn-secondary"
          @click="closeModal"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
  backdrop-filter: blur(2px);
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  max-width: 1000px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: between;
  align-items: center;
  background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
  color: white;
  border-radius: 12px 12px 0 0;
}

.modal-title {
  margin: 0;
  font-weight: 600;
  flex: 1;
}

.btn-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.btn-close:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.modal-body {
  padding: 1.5rem;
  min-height: 200px;
}

.appointments-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.appointment-card {
  display: flex;
  align-items: center;
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  transition: all 0.2s ease;
}

.appointment-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.appointment-time {
  min-width: 80px;
  font-weight: 700;
  font-size: 1.1rem;
  color: #0d6efd;
  text-align: center;
  padding: 0.5rem;
  background: rgba(13, 110, 253, 0.1);
  border-radius: 6px;
  margin-right: 1rem;
}

.appointment-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.patient-name {
  font-weight: 600;
  color: #212529;
  font-size: 1rem;
}

.patient-phone {
  color: #6c757d;
  font-size: 0.9rem;
}

.appointment-notes {
  color: #6c757d;
  font-size: 0.85rem;
  font-style: italic;
}

.appointment-status {
  margin-left: 1rem;
}

.badge {
  font-size: 0.8rem;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f8f9fa;
  border-radius: 0 0 12px 12px;
}

.spinner-border {
  width: 2rem;
  height: 2rem;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
  
  .appointment-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .appointment-time {
    min-width: auto;
    margin-right: 0;
    margin-bottom: 0.5rem;
  }
  
  .appointment-status {
    margin-left: 0;
    align-self: flex-end;
  }
  
  .modal-footer {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>