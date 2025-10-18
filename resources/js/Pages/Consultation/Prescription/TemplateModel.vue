  
<script setup>
import { ref, computed, watch, nextTick, defineProps, defineEmits } from 'vue';
import axios from 'axios';

// Props with proper validation
const props = defineProps({
  showTemplateModal: {
    type: Boolean,
    default: false
  },
  prescriptionId: {
    type: [Number, String],
    required: true,
    validator: (value) => {
      return value && (typeof value === 'number' || !isNaN(Number(value)));
    }
  },
  doctorId: {
    type: [Number, String],
    default: 1,
    validator: (value) => {
      return value && (typeof value === 'number' || !isNaN(Number(value)));
    }
  }
});

// Emits
const emit = defineEmits(['saved', 'cancel']);

// Template refs
const nameInput = ref(null);

// Reactive data
const template = ref({
  name: '',
  description: '',
  prescription_id: Number(props.prescriptionId),
  doctor_id: Number(props.doctorId)
});

const isLoading = ref(false);
const errors = ref({});
const successMessage = ref('');
const errorMessage = ref('');

// Computed properties
const isFormValid = computed(() => {
  return template.value.name.trim().length > 0 && Object.keys(errors.value).length === 0;
});

// Watch for modal visibility to focus input and reset form
watch(() => props.showTemplateModal, async (newVal) => {
  if (newVal) {
    resetForm();
    await nextTick();
    if (nameInput.value) {
      nameInput.value.focus();
    }
  }
});

// Watch for prescription ID changes
watch(() => props.prescriptionId, (newVal) => {
  template.value.prescription_id = Number(newVal);
});

// Validation functions
const validateForm = () => {
  errors.value = {};
  
  // Validate template name
  if (!template.value.name.trim()) {
    errors.value.name = 'Template name is required';
  } else if (template.value.name.trim().length < 3) {
    errors.value.name = 'Template name must be at least 3 characters long';
  } else if (template.value.name.trim().length > 100) {
    errors.value.name = 'Template name must not exceed 100 characters';
  }
  
  // Validate description
  if (template.value.description.length > 500) {
    errors.value.description = 'Description must not exceed 500 characters';
  }
  
  return Object.keys(errors.value).length === 0;
};

// Watchers for real-time validation
watch(() => template.value.name, () => {
  if (errors.value.name) {
    validateForm();
  }
});

watch(() => template.value.description, () => {
  if (errors.value.description) {
    validateForm();
  }
});

// Methods
const clearMessages = () => {
  successMessage.value = '';
  errorMessage.value = '';
};
const saveTemplate = async () => {
  clearMessages();
  
  if (!validateForm()) {
    errorMessage.value = 'Please fix the errors below';
    return;
  }
  
  isLoading.value = true;
  
  try {
    // Prepare payload with proper data types and string conversion
    const payload = {
      name: template.value.name.trim(),
      description: template.value.description.trim(),
      prescription_id: Number(props.prescriptionId),
      doctor_id: Number(props.doctorId)
    };
    
    const response = await axios.post('/api/prescription/prescription-templates', payload, {
      timeout: 10000,
      validateStatus: (status) => status < 500
    });
    
    if (response.status === 201 || response.status === 200) {
      successMessage.value = 'Template saved successfully!';
      emit('saved', response.data);
      
      setTimeout(() => {
        handleCancel();
      }, 1500);
      
    } else {
      throw new Error(`Server responded with status ${response.status}`);
    }
    
  } catch (error) {
    console.error('Error saving template:', error);
    
    if (error.response) {
      const status = error.response.status;
      const data = error.response.data;
      
      if (status === 400) {
        errorMessage.value = data.message || 'Invalid data provided. Please check your input.';
      } else if (status === 401) {
        errorMessage.value = 'You are not authorized to perform this action.';
      } else if (status === 422) {
        errorMessage.value = data.message || 'Validation failed. Please check your input.';
        if (data.errors) {
          errors.value = data.errors;
        }
      } else {
        errorMessage.value = 'Server error occurred. Please try again later.';
      }
    } else if (error.request) {
      errorMessage.value = 'Network error. Please check your connection and try again.';
    } else {
      errorMessage.value = 'An unexpected error occurred. Please try again.';
    }
  } finally {
    isLoading.value = false;
  }
};

const handleCancel = () => {
  clearMessages();
  emit('cancel');
};

const resetForm = () => {
  template.value = {
    name: '',
    description: '',
    prescription_id: props.prescriptionId,
    doctor_id: Number(props.doctorId)
  };
  errors.value = {};
  clearMessages();
};

// Handle escape key to close modal
const handleKeydown = (event) => {
  if (event.key === 'Escape' && props.showTemplateModal && !isLoading.value) {
    handleCancel();
  }
};

// Add/remove event listeners
watch(() => props.showTemplateModal, (newVal) => {
  if (newVal) {
    document.addEventListener('keydown', handleKeydown);
    document.body.style.overflow = 'hidden'; // Prevent background scroll
  } else {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = ''; // Restore scroll
  }
});

// Cleanup on unmount
import { onUnmounted } from 'vue';
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});

// Expose methods if needed by parent component
defineExpose({
  resetForm,
  validateForm
});
</script>

<template>
    <!-- Modal Backdrop and Container -->
    <div 
      v-if="showTemplateModal" 
      class="modal-backdrop"
      @click.self="handleCancel"
    >
      <div class="modal-container" @click.stop>
        <div class="card premium-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                  <i class="fas fa-file-medical me-2"></i>
                  Save as Template
                </h5>

                {{ prescriptionId }}
            </div>
          </div>
          
          <div class="card-body">
            <!-- Success/Error Messages -->
            <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle me-2"></i>
              {{ successMessage }}
              <button type="button" class="btn-close" @click="successMessage = ''"></button>
            </div>
            
            <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-triangle me-2"></i>
              {{ errorMessage }}
              <button type="button" class="btn-close" @click="errorMessage = ''"></button>
            </div>
  
            <form @submit.prevent="saveTemplate" novalidate>
              <div class="mb-3">
                <label for="templateName" class="form-label required">
                  Template Name
                </label>
                <input 
                  type="text" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.name }"
                  id="templateName" 
                  v-model.trim="template.name"
                  placeholder="Enter template name"
                  maxlength="100"
                  required
                  ref="nameInput"
                >
                <div v-if="errors.name" class="invalid-feedback">
                  {{ errors.name }}
                </div>
              </div>
  
              <div class="mb-3">
                <label for="templateDescription" class="form-label">
                  Description
                </label>
                <textarea 
                  class="form-control"
                  :class="{ 'is-invalid': errors.description }"
                  id="templateDescription" 
                  v-model.trim="template.description"
                  placeholder="Enter template description (optional)"
                  rows="3"
                  maxlength="500"
                ></textarea>
                <div class="form-text">
                  {{ template.description.length }}/500 characters
                </div>
                <div v-if="errors.description" class="invalid-feedback">
                  {{ errors.description }}
                </div>
              </div>
  
              <div class="modal-footer">
                <button 
                  type="button" 
                  class="premium-btn premium-btn-secondary" 
                  @click="handleCancel"
                  :disabled="isLoading"
                >
                  <i class="fas fa-times me-1"></i>
                  Cancel
                </button>
                <button 
                  type="submit" 
                  class="premium-btn premium-btn-primary"
                  :disabled="isLoading || !isFormValid"
                >
                  <i class="fas fa-spinner fa-spin me-1" v-if="isLoading"></i>
                  <i class="fas fa-save me-1" v-else></i>
                  <span v-if="isLoading">Saving...</span>
                  <span v-else>Save Template</span>
                </button>
              </div>
            </form>
          </div>
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
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 20px;
    animation: fadeIn 0.2s ease-out;
  }
  
  .modal-container {
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideIn 0.3s ease-out;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  
  @keyframes slideIn {
    from { 
      opacity: 0;
      transform: translateY(-20px) scale(0.95);
    }
    to { 
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }
  
  .premium-card {
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border: none;
    overflow: hidden;
    background: white;
  }
  
  .premium-card .card-header {
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
    padding: 20px;
  }
  
  .premium-card .card-header h5 {
    color: white;
    font-weight: 600;
  }
  
  .btn-close-custom {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }
  
  .btn-close-custom:hover:not(:disabled) {
    background-color: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
  }
  
  .btn-close-custom:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .card-body {
    padding: 30px;
  }
  
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
  }
  
  .form-label.required::after {
    content: " *";
    color: #dc3545;
  }
  
  .form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 12px 16px;
    transition: all 0.3s ease;
  }
  
  .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  }
  
  .form-control.is-invalid {
    border-color: #dc3545;
  }
  
  .form-control.is-invalid:focus {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
  }
  
  .premium-btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    min-width: 120px;
    justify-content: center;
  }
  
  .premium-btn-primary {
    background: linear-gradient(135deg, #3650ce 0%, #545eec 100%);
    color: white;
  }
  
  .premium-btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #3650ce 0%, #545eec 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
  }
  
  .premium-btn-secondary {
    background-color: #f8f9fa;
    border: 2px solid #e9ecef;
    color: #6c757d;
  }
  
  .premium-btn-secondary:hover:not(:disabled) {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #495057;
    transform: translateY(-1px);
  }
  
  .premium-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
  }
  
  .alert {
    border-radius: 8px;
    border: none;
    padding: 16px;
    margin-bottom: 24px;
  }
  
  .alert-success {
    background-color: #d1edff;
    color: #0c5460;
  }
  
  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
  }
  
  .btn-close {
    padding: 0.5rem;
  }
  
  .invalid-feedback {
    display: block;
    font-size: 0.875rem;
    color: #dc3545;
    margin-top: 0.25rem;
  }
  
  .form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
  }
  
  @media (max-width: 768px) {
    .modal-backdrop {
      padding: 10px;
    }
    
    .card-body {
      padding: 20px;
    }
    
    .modal-footer {
      flex-direction: column;
    }
    
    .premium-btn {
      width: 100%;
    }
  }
  </style>