<script setup>
import { ref, watch } from 'vue';
import { useToastr } from '../toster';
import axios from 'axios';

const toaster = useToastr();

const props = defineProps({
  show: Boolean,
  patientId: {
    type: Number,
    required: true
  },
  entry: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'surgical-entry-added', 'surgical-entry-updated']);
const formatCurrentDate = () => {
  const now = new Date();
  return now.toISOString().split('T')[0];
};

const loading = ref(false);
const form = ref({
  surgery_name: '',
  date: formatCurrentDate(),
  notes: ''
});

// Watch for entry prop changes (for edit mode)
watch(() => props.entry, (newEntry) => {
  if (newEntry) {
    form.value = {
      surgery_name: newEntry.surgery_name,
      date: newEntry.date.split('T')[0],
      notes: newEntry.notes
    };
  }
}, { immediate: true });

const closeModal = () => {
  form.value = {
    surgery_name: '',
    date: formatCurrentDate(),
    notes: ''
  };
  emit('close');
};

const handleSubmit = async () => {
  try {
    loading.value = true;
    
    if (props.entry) {
      // Update existing entry
      const response = await axios.put(
        `/api/consultation/patients/${props.patientId}/surgical-history/${props.entry.id}`,
        form.value
      );
      toaster.success('Surgical entry updated successfully');
      emit('surgical-entry-updated', response.data);
    } else {
      // Create new entry
      const response = await axios.post(
        `/api/consultation/patients/${props.patientId}/surgical-history`,
        form.value
      );
      toaster.success('Surgical entry added successfully');
      emit('surgical-entry-added', response.data);
    }
    
    closeModal();
  } catch (error) {
    toaster.error(error.response?.data?.message || 
      (props.entry ? 'Failed to update surgical entry' : 'Failed to add surgical entry'));
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <teleport to="body">
    <div v-if="show" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ entry ? 'Edit' : 'Add New' }} Surgical Entry</h3>
          <button @click="closeModal" class="close-button">&times;</button>
        </div>

        <form @submit.prevent="handleSubmit" class="modal-form">
          <div class="form-group">
            <label for="surgery_name">Surgery Name</label>
            <input
              v-model="form.surgery_name"
              type="text"
              id="surgery_name"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="date">Date of Surgery</label>
            <input
              v-model="form.date"
              type="date"
              id="date"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="notes">Notes</label>
            <textarea
              v-model="form.notes"
              id="notes"
              rows="3"
              class="form-input"
            ></textarea>
          </div>

          <div class="modal-footer">
             <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              {{ loading ? (isEditing ? 'Updating...' : 'Saving...') : (isEditing ? 'Update Allergy' : 'Save Allergy') }}
            </button>
          </div>
            <!-- <button
              type="button"
              @click="closeModal"
              class="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="loading"
            >
              {{ loading ? (entry ? 'Updating...' : 'Saving...') : (entry ? 'Update Entry' : 'Save Entry') }}
            </button> -->
          </div>
        </form>
      </div>
    </div>
  </teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
 display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  padding: 2rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-height: 90vh;       /* ✅ Limits height */
  overflow-y: auto;       /* ✅ Enables scroll inside */
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  color: #6b7280;
  border-radius: 4px;
  transition: all 0.2s;
}

.close-button:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-primary:disabled {
  background-color: #93c5fd;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background-color: #e5e7eb;
}

/* Responsive design */
@media (max-width: 640px) {
  .modal-content {
    padding: 1.5rem;
    margin: 1rem;
  }

  .modal-footer {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>