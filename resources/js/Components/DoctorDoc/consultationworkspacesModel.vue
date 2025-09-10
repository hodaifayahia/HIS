<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../toster';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  workspace: {
    type: Object,
    default: () => ({})
  },
  doctorid: {
    type: [Number, String],
    required: true
  },
  isEdit: {
    type: Boolean,
    default: false
  }
});
// Methods
const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    is_archived: false,
    last_accessed: null
  };
  errors.value = {};
};
const emit = defineEmits(['update:modelValue', 'workspace-saved' , 'close']);

const toaster = useToastr();

// Form state
const form = ref({
  name: '',
  description: '',
  is_archived: false,
  last_accessed: null
});

const errors = ref({});
const submitting = ref(false);

// Computed properties
const showModal = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

const modalTitle = computed(() => 
  props.isEdit ? 'Edit Workspace' : 'Create New Workspace'
);

const submitButtonText = computed(() => 
  props.isEdit ? 'Update Workspace' : 'Create Workspace'
);

const isFormValid = computed(() => {
  return form.value.name.trim().length > 0;
});

// Initialize form with workspace data when editing
watch(
  () => props.workspace,
  (newWorkspace) => {
    if (newWorkspace && Object.keys(newWorkspace).length > 0) {
      form.value = {
        name: newWorkspace.name || '',
        description: newWorkspace.description || '',
        is_archived: newWorkspace.is_archived || false,
        last_accessed: newWorkspace.last_accessed || null
      };
    } else {
      resetForm();
    }
  },
  { immediate: true, deep: true }
);

// Watch for modal visibility changes
watch(showModal, (isVisible) => {
  if (isVisible) {
    // Clear errors when modal opens
    errors.value = {};
    // Focus on name input after modal is shown
    setTimeout(() => {
      const nameInput = document.getElementById('workspace-name');
      if (nameInput) nameInput.focus();
    }, 150);
  } else {
    // Reset form when modal closes
    if (!submitting.value) {
      resetForm();
    }
  }
});



const validateForm = () => {
  const newErrors = {};
  
  if (!form.value.name.trim()) {
    newErrors.name = ['Workspace name is required'];
  } else if (form.value.name.trim().length < 3) {
    newErrors.name = ['Workspace name must be at least 3 characters'];
  } else if (form.value.name.trim().length > 100) {
    newErrors.name = ['Workspace name must not exceed 100 characters'];
  }
  
  if (form.value.description && form.value.description.length > 500) {
    newErrors.description = ['Description must not exceed 500 characters'];
  }
  
  errors.value = newErrors;
  return Object.keys(newErrors).length === 0;
};

const submitForm = async () => {
  if (!validateForm()) {
    toaster.error('Please correct the form errors');
    return;
  }
    console.log('isEdit:', props.isEdit, 'workspace.id:', props.workspace?.id);


  submitting.value = true;
  
  const payload = {
    name: form.value.name.trim(),
    description: form.value.description?.trim() || null,
    doctor_id: Number(props.doctorid),
    is_archived: form.value.is_archived,
    last_accessed: form.value.last_accessed || null
  };

  try {
    let response;
  if (props.isEdit && props.workspace?.id) {
  response = await axios.put(`/api/consultationworkspaces/${props.workspace.id}`, payload);
  toaster.success('Workspace updated successfully');
} else {
  response = await axios.post('/api/consultationworkspaces', payload);
  toaster.success('Workspace created successfully');
}
    
    emit('workspace-saved', response.data);
    emit('close');
    closeModal();
  } catch (err) {
    console.error('Submission error:', err);
    
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
      toaster.error('Please correct the form errors');
    } else if (err.response?.status === 404) {
      toaster.error('Workspace not found');
    } else if (err.response?.status === 403) {
      toaster.error('You do not have permission to perform this action');
    } else {
      const errorMessage = err.response?.data?.message || 
                          err.response?.data?.error || 
                          'Failed to save workspace. Please try again.';
      toaster.error(errorMessage);
    }
  } finally {
    submitting.value = false;
  }
};

const closeModal = () => {
  if (!submitting.value) {
    showModal.value = false;
  }
};

const handleKeydown = (event) => {
  if (event.key === 'Escape' && !submitting.value) {
    closeModal();
  } else if (event.key === 'Enter' && event.ctrlKey && isFormValid.value) {
    submitForm();
  }
};

// Format date for input
const formatDateForInput = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toISOString().slice(0, 16);
};

const handleDateChange = (event) => {
  form.value.last_accessed = event.target.value || null;
};
</script>

<template>
  <!-- Modal Backdrop -->
  <div 
    v-if="showModal" 
    class="modal-backdrop fade show"
    @click="closeModal"
  ></div>

  <!-- Modal -->
  <div 
    class="modal fade"
    :class="{ show: showModal, 'd-block': showModal }"
    tabindex="-1"
    @keydown="handleKeydown"
  >
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg">
        <!-- Modal Header -->
        <div class="modal-header border-0 pb-0">
          <div class="d-flex align-items-center gap-3">
            <div class="modal-icon">
              <i class="fas fa-briefcase-medical text-primary"></i>
            </div>
            <div>
              <h4 class="modal-title mb-1">{{ modalTitle }}</h4>
              <p class="text-muted small mb-0">
                {{ isEdit ? 'Update workspace details' : 'Create a new workspace for your templates' }}
              </p>
            </div>
          </div>
          <button 
            type="button" 
            class="btn-close"
            @click="closeModal"
            :disabled="submitting"
          >
          <i class="fa fa-times rounded-pill" ></i>
        </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body pt-3">
          <form @submit.prevent="submitForm" class="workspace-form">
            <!-- Workspace Name -->
            <div class="form-group mb-4">
              <label for="workspace-name" class="form-label fw-semibold">
                <i class="fas fa-tag me-2 text-primary"></i>
                Workspace Name
                <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                class="form-control form-control-lg"
                id="workspace-name"
                v-model="form.name"
                :class="{ 'is-invalid': errors.name }"
                placeholder="Enter workspace name"
                maxlength="100"
                :disabled="submitting"
              />
              <div class="form-text d-flex justify-content-between">
                <span>Give your workspace a descriptive name</span>
                <span class="text-muted">{{ form.name.length }}/100</span>
              </div>
              <div v-if="errors.name" class="invalid-feedback">
                {{ errors.name[0] }}
              </div>
            </div>

            <!-- Description -->
            <div class="form-group mb-4">
              <label for="workspace-description" class="form-label fw-semibold">
                <i class="fas fa-align-left me-2 text-primary"></i>
                Description
              </label>
              <textarea
                class="form-control"
                id="workspace-description"
                v-model="form.description"
                :class="{ 'is-invalid': errors.description }"
                placeholder="Describe the purpose of this workspace (optional)"
                rows="4"
                maxlength="500"
                :disabled="submitting"
              ></textarea>
              <div class="form-text d-flex justify-content-between">
                <span>Optional: Add a description to help identify this workspace</span>
                <span class="text-muted">{{ (form.description || '').length }}/500</span>
              </div>
              <div v-if="errors.description" class="invalid-feedback">
                {{ errors.description[0] }}
              </div>
            </div>

            <!-- Archive Status -->
            <div class="form-group mb-4">
              <div class="card bg-light border-0">
                <div class="card-body py-3">
                  <div class=" d-flex align-items-center">
                    <input
                      type="checkbox"
                      class="form-check-input me-3 - "
                      id="workspace-archived"
                      v-model="form.is_archived"
                      :disabled="submitting"
                    />
                    <div class="flex-grow-2 ml-4">
                      <label class="form-check-label fw-semibold mb-1" for="workspace-archived">
                        <i class="fas fa-archive me-2 text-warning"></i>
                        Archive Workspace
                      </label>
                      <div class="form-text mb-0">
                        Archived workspaces are hidden from the main view but remain accessible
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Last Accessed (Optional) -->
            <!-- <div class="form-group mb-4">
              <label for="workspace-last-accessed" class="form-label fw-semibold">
                <i class="fas fa-clock me-2 text-primary"></i>
                Last Accessed (Optional)
              </label>
              <input
                type="datetime-local"
                class="form-control"
                id="workspace-last-accessed"
                :value="formatDateForInput(form.last_accessed)"
                @input="handleDateChange"
                :class="{ 'is-invalid': errors.last_accessed }"
                :disabled="submitting"
              />
              <div class="form-text">
                Set when this workspace was last accessed (leave empty for automatic tracking)
              </div>
              <div v-if="errors.last_accessed" class="invalid-feedback">
                {{ errors.last_accessed[0] }}
              </div>
            </div> -->
          </form>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer border-0 pt-0">
          <div class="d-flex justify-content-between w-100">
            <button 
              type="button" 
              class="btn btn-light px-4"
              @click="closeModal"
              :disabled="submitting"
            >
              <i class="fas fa-times me-2"></i>
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-primary px-4"
              @click="submitForm"
              :disabled="submitting || !isFormValid"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else :class="isEdit ? 'fas fa-save' : 'fas fa-plus'" class="me-2"></i>
              {{ submitButtonText }}
            </button>
          </div>
          
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
}

.modal-content {
  border-radius: 20px;
  overflow: hidden;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.modal-header {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  padding: 1.5rem 1.5rem 1rem;
}

.modal-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 123, 255, 0.2) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.modal-body {
  padding: 1rem 1.5rem;
  max-height: 70vh;
  overflow-y: auto;
}

.modal-footer {
  padding: 1rem 1.5rem 1.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.form-label {
  color: #495057;
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}

.form-control {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.8);
}

.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
  background: white;
  transform: translateY(-1px);
}

.form-control-lg {
  padding: 1rem 1.25rem;
  font-size: 1.1rem;
  font-weight: 600;
}

.form-control.is-invalid {
  border-color: #dc3545;
  background: rgba(220, 53, 69, 0.05);
}

.invalid-feedback {
  font-size: 0.875rem;
  font-weight: 500;
}

.form-text {
  font-size: 0.8rem;
  margin-top: 0.5rem;
}

.form-check-input {
  width: 3rem;
  height: 1.5rem;
  border-radius: 1rem;
  border: 2px solid #dee2e6;
}

.form-check-input:checked {
  background-color: #28a745;
  border-color: #28a745;
}

.card {
  transition: all 0.3s ease;
}

.btn {
  border-radius: 10px;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  transition: all 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  border: none;
  box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  transform: none;
  box-shadow: none;
}

.btn-light {
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  color: #6c757d;
}

.btn-light:hover:not(:disabled) {
  background: #e9ecef;
  border-color: #dee2e6;
  transform: translateY(-1px);
}

.btn-close {
  opacity: 0.6;
  transition: all 0.2s ease;
  border-radius: 50%;
}

.btn-close:hover {
  opacity: 1;
  transform: scale(1.1);
}

kbd {
  background: #495057;
  color: white;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: bold;
}

/* Animation */
.modal.show .modal-content {
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

/* Scrollbar Styling */
.modal-body::-webkit-scrollbar {
  width: 6px;
}

.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .modal-dialog {
    margin: 1rem;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .form-control-lg {
    font-size: 1rem;
  }
  
  .modal-body {
    max-height: 60vh;
  }
}
</style>