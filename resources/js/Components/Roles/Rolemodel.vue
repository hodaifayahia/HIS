<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster'; // Adjust path as needed

const props = defineProps({
  showModal: {
    type: Boolean,
    default: false,
  },
  roleData: {
    type: Object,
    default: () => ({ name: '' }),
  },
});

const emit = defineEmits(['close', 'roleUpdated']);

const toaster = useToastr();
const roleForm = ref({
  id: null,
  name: '',
});
const isSubmitting = ref(false);
const formErrors = ref({});

// Watch for changes in roleData to populate the form
watch(() => props.roleData, (newVal) => {
  if (newVal) {
    roleForm.value = { ...newVal };
    formErrors.value = {}; // Clear errors when data changes for a new role or edit
  }
}, { immediate: true });

const submitForm = async () => {
  isSubmitting.value = true;
  formErrors.value = {}; // Clear previous errors

  try {
    if (roleForm.value.id) {
      // Update existing role
      await axios.put(`/api/roles/${roleForm.value.id}`, roleForm.value);
      toaster.success('Role updated successfully!');
    } else {
      // Create new role
      await axios.post('/api/roles', roleForm.value);
      toaster.success('Role created successfully!');
    }
    emit('roleUpdated'); // Notify parent to refresh list
    closeModal();
  } catch (error) {
    console.error('Error saving role:', error);
    if (error.response?.data?.errors) {
      formErrors.value = error.response.data.errors;
      toaster.error('Please correct the form errors.');
    } else {
      toaster.error(error.response?.data?.message || 'Failed to save role.');
    }
  } finally {
    isSubmitting.value = false;
  }
};

const closeModal = () => {
  emit('close');
  // Reset form when closing, ensuring id is null for future 'add' operations
  roleForm.value = { id: null, name: '' };
  formErrors.value = {};
};
</script>

<template>
  <div v-if="showModal" class="modal fade show d-block" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true" @click.self="closeModal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title d-flex align-items-center gap-2" id="roleModalLabel">
            <i :class="roleForm.id ? 'fas fa-edit' : 'fas fa-plus-circle'"></i>
            <span>{{ roleForm.id ? 'Edit Role' : 'Add New Role' }}</span>
          </h5>
          <button type="button" class="btn btn-primary rounded-pill" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="roleName" class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control form-control-lg" :class="{ 'is-invalid': formErrors.name }"
                id="roleName"
                v-model="roleForm.name"
                placeholder="e.g., Administrator, Editor"
                required
                autofocus />
              <div v-if="formErrors.name" class="invalid-feedback d-block"> {{ formErrors.name[0] }}
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
          <button type="submit" class="btn btn-primary" @click="submitForm" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ roleForm.id ? 'Update Role' : 'Create Role' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showModal" class="modal-backdrop fade show"></div>
</template>

<style scoped>
/* Custom modal styles for better appearance */
.modal {
  background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
}

.modal-dialog {
  max-width: 500px; /* Adjust modal max-width for better proportions */
}

.modal-content {
  border-radius: 0.6rem; /* Slightly larger border-radius */
  overflow: hidden; /* Ensures header background extends to corners */
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Add subtle shadow */
}

.modal-header {
  border-bottom: none;
  padding: 1.25rem 1.5rem; /* Increased padding */
}

.modal-title {
  font-weight: 600;
  font-size: 1.3rem; /* Slightly larger title font size */
}

.btn-close-white {
  filter: brightness(0) invert(1); /* Makes the close icon white */
  font-size: 1.1rem; /* Slightly larger close button */
}

.modal-body {
  padding: 1.5rem;
}

.form-label {
  color: #343a40; /* Darker label color */
}

.form-control:focus {
  border-color: #80bdff; /* Default Bootstrap focus blue */
  box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); /* Default Bootstrap focus shadow */
}

.invalid-feedback {
  font-size: 0.875em; /* Standard smaller font for feedback */
  margin-top: 0.25rem; /* Add a little margin top */
}

.modal-footer {
  border-top: 1px solid #e9ecef; /* Add a subtle border top */
  padding: 1rem 1.5rem;
  background-color: #f8f9fa; /* Light background for footer */
}

.modal-footer .btn {
  padding: 0.6rem 1.2rem; /* Slightly larger buttons */
  font-size: 0.95rem;
}
</style>