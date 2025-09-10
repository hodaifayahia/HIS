<script setup>
import { defineProps, defineEmits, ref, computed } from 'vue'; // Import 'computed'
import OldConsulation from '../OldConsulation/OldConsulation.vue';

const props = defineProps({
  consultation: Object,
  index: Number,
  isworkspace: {
    type: Boolean,
    default: false // Default to false, so it behaves as original when not in workspace
  }
});

const emit = defineEmits(['delete']);
const showModal = ref(false);

const handleDelete = () => {
  if (props.isworkspace) {
    // Emit both IDs for workspace deletion
    emit('delete', {
      consultation_id: props.consultation.id,
      consultation_workspace_id: props.consultation.consultation_workspace_id
    });
  } else {
    emit('delete', props.consultation.id, props.consultation.patient_name);
  }
};
const goToDetailConsultation = () => {
  showModal.value = true;
};

// Helper to determine status badge color
const getStatusBadgeClass = (status) => {
  if (!status) return 'bg-secondary'; // Default color for undefined/null status

  switch (status.toLowerCase()) {
    case 'completed':
      return 'bg-success';
    case 'pending':
      return 'bg-warning';
    case 'cancelled':
      return 'bg-danger';
    default:
      return 'bg-secondary';
  }
};

// --- New Computed Properties for Time Formatting ---

const formattedStartTime = computed(() => {
  if (!props.consultation.created_at) return ''; // Handle cases where data might be missing
  try {
    const date = new Date(props.consultation.created_at);
    // Use toLocaleTimeString for a user-friendly time format (e.g., "10:30 AM" or "10:30")
    // The options object ensures 2-digit hour and minute, no seconds
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
  } catch (e) {
    console.error("Error parsing created_at:", e);
    return 'Not yet';
  }
});

const formattedEndTime = computed(() => {
  if (!props.consultation.consultation_end_at) return ''; // Handle cases where data might be missing
  try {
    const date = new Date(props.consultation.consultation_end_at);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
  } catch (e) {
    console.error("Error parsing consultation_end_at:", e);
    return 'Not yet';
  }
});
</script>

<template>
  <tr @click="goToDetailConsultation" class="consultation-row">
    <td class="ps-4">{{ index + 1 }}</td>
    <td>
      <div class="d-flex align-items-center">
        <i class="fas fa-file-alt me-2 text-primary"></i>
        <strong>{{ consultation.codebash }}</strong>
      </div>
    </td>
    <td>
      <div class="d-flex align-items-center">
        <i class="fas fa-user me-2 text-primary"></i>
        <strong>{{ consultation.patient_name }}</strong>
      </div>
    </td>
    <td v-if="!isworkspace">
      <div v-if="consultation.doctor_name" class="d-flex align-items-center">
        <i class="fas fa-user-md me-1 text-secondary"></i>
        {{ consultation.doctor_name }}
      </div>
      <span v-else class="text-muted fst-italic">No Doctor</span>
    </td>
    <td>{{ consultation.date }}</td>
    <td>{{ formattedStartTime }} -> {{ formattedEndTime }}</td>
    <td>
      <div class="btn-group d-flex justify-content-center">
        <button 
          class="btn btn-sm btn-outline-danger action-btn" 
          @click.stop="handleDelete" 
          :title="isworkspace ? 'Remove from Workspace' : 'Delete Consultation'"
        >
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>
  </tr>

  <div v-if="showModal" class="modal-overlay">
    <div class="modal-container">
      <button class="modal-close" @click="showModal = false">×</button>
      <OldConsulation 
        :patient-id="consultation.patient_id"
        :consultation-id="consultation.id"
        :single-mode="true"
        @close="showModal = false"
      />
    </div>
  </div>
</template>

<style scoped>
/* Your existing styles here */
.consultation-row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.consultation-row:hover {
  background-color: rgba(0, 123, 255, 0.05) !important;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border-radius: 0.25rem;
  transition: all 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.modal-container {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 1200px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  padding: 20px;
}

.modal-close {
  position: absolute;
  top: 10px;
  right: 10px;
  border: none;
  background: none;
  font-size: 24px;
  cursor: pointer;
  padding: 5px 10px;
  border-radius: 4px;
  z-index: 1;
}

.modal-close:hover {
  background-color: rgba(0, 0, 0, 0.1);
}
</style>