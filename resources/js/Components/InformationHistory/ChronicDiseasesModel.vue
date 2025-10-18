<template>
  <teleport to="body">
    <div v-if="show" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Edit Chronic Disease' : 'Add New Chronic Disease' }}</h3>
          <button @click="closeModal" class="close-button">&times;</button>
        </div>
        
        <form @submit.prevent="handleSubmit" class="modal-form">
          <div class="form-group">
            <label for="name">Disease Name</label>
            <input
              v-model="form.name"
              type="text"
              id="name"
              required
              class="form-input"
            />
          </div>
          
          <div class="form-group">
            <label for="description">Description</label>
            <textarea
              v-model="form.description"
              id="description"
              rows="3"
              class="form-input"
            ></textarea>
          </div>
          
          <div class="form-group">
            <label for="diagnosis_date">Diagnosis Date</label>
            <input
              v-model="form.diagnosis_date"
              type="date"
              id="diagnosis_date"
              class="form-input"
            />
          </div>
          
          <div class="modal-footer">
            <button
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
              {{ loading ? (isEditing ? 'Updating...' : 'Saving...') : (isEditing ? 'Update Disease' : 'Save Disease') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </teleport>
</template>

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
  diseaseToEdit: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'chronic-disease-added', 'chronic-disease-updated']);

const loading = ref(false);
const isEditing = ref(false);
const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    diagnosis_date: formatCurrentDate(),
  };
};

const formatCurrentDate = () => {
  const now = new Date();
  return now.toISOString().split('T')[0];
};

const form = ref({
  name: '',
  description: '',
  diagnosis_date: formatCurrentDate(),
});


// Watch for changes in diseaseToEdit prop
watch(() => props.diseaseToEdit, (newVal) => {
  if (newVal) {
    isEditing.value = true;
    form.value = {
      name: newVal.name,
      description: newVal.description || '',
      diagnosis_date: newVal.diagnosis_date.split('T')[0], // Ensure proper date format
    };
  } else {
    isEditing.value = false;
    resetForm();
  }
}, { immediate: true });


const closeModal = () => {
  resetForm();
  emit('close');
};

const handleSubmit = async () => {
  try {
    loading.value = true;
    
    if (isEditing.value) {
      // Update existing disease
      const response = await axios.put(
        `/api/consultation/patients/${props.patientId}/chronic-diseases/${props.diseaseToEdit.id}`,
        form.value
      );
      toaster.success('Chronic disease updated successfully');
      emit('chronic-disease-updated', response.data);
    } else {
      // Create new disease
      const response = await axios.post(
        `/api/consultation/patients/${props.patientId}/chronic-diseases`,
        form.value
      );
      toaster.success('Chronic disease added successfully');
      emit('chronic-disease-added', response.data);
    }
    
    closeModal();
  } catch (error) {
    toaster.error(error.response?.data?.message || 
      (isEditing.value ? 'Failed to update chronic disease' : 'Failed to add chronic disease'));
  } finally {
    loading.value = false;
  }
};
</script>

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
  max-height: 100vh;
  overflow-y: auto;
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