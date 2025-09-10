<template>
  <Dialog
    :visible="visible"
    :header="`Edit Payment Methods for ${userForm.name}`"
    :modal="true"
    class="custom-dialog"
    :style="{ width: '50vw' }"
    @update:visible="$emit('hide-dialog')"
  >
    <div class="form-grid">
      <!-- Show user info (read-only) -->
      <div class="user-info">
        <p><strong>User:</strong> {{ userForm.name }}</p>
        <p><strong>Email:</strong> {{ userForm.email }}</p>
      </div>
      
      <!-- Only editable fields -->
      <div class="form-field">
        <label for="userStatus">Status</label>
        <Dropdown
          id="userStatus"
          v-model="userForm.status"
          :options="userStatusOptions"
          placeholder="Select a Status"
          :class="{ 'p-invalid': submitted && !userForm.status }"
        />
        <small class="p-error" v-if="submitted && !userForm.status">Status is required.</small>
      </div>
      
      <div class="form-field">
        <label for="allowedMethods">Allowed Payment Methods</label>
        <MultiSelect
          id="allowedMethods"
          v-model="userForm.allowedMethods"
          :options="paymentOptionsDropdown"
          optionLabel="name"
          optionValue="key"
          placeholder="Select Allowed Methods"
          display="chip"
        />
      </div>
    </div>

    <template #footer>
      <button class="dialog-button secondary" @click="$emit('hide-dialog')">
        <i class="fas fa-times"></i> Cancel
      </button>
      <button class="dialog-button primary" @click="$emit('save-user')">
        <i class="fas fa-check"></i> Update
      </button>
    </template>
  </Dialog>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true,
  },
  editMode: {
    type: Boolean,
    required: true,
  },
  userForm: {
    type: Object,
    required: true,
  },
  submitted: {
    type: Boolean,
    required: true,
  },
  userStatusOptions: {
    type: Array,
    required: true,
  },
  paymentOptionsDropdown: {
    type: Array,
    required: true,
  },
  isValidEmail: {
    type: Function,
    required: true,
  },
});

const emit = defineEmits(['hide-dialog', 'save-user']);
</script>

<style scoped>
/* Dialog Styling */
.custom-dialog.p-dialog {
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
}

.custom-dialog .p-dialog-header {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
  padding: 1.5rem 2rem;
  border-top-left-radius: 1rem;
  border-top-right-radius: 1rem;
}

.custom-dialog .p-dialog-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}

.custom-dialog .p-dialog-content {
  padding: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

.form-field label {
  font-weight: 600;
  display: block;
  margin-bottom: 0.5rem;
  color: #475569;
}

.form-field .p-inputtext,
.form-field .p-dropdown,
.form-field .p-multiselect {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.9rem;
  transition: all 0.2s;
  background-color: #ffffff;
}

.form-field .p-inputtext:focus,
.form-field .p-dropdown.p-focus,
.form-field .p-multiselect.p-focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-field .p-invalid {
  border-color: #ef4444;
}

.form-field .p-error {
  color: #dc2626;
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: block;
}

.custom-dialog .p-dialog-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;
}

.dialog-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.dialog-button.primary {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: #ffffff;
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
}

.dialog-button.primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
}

.dialog-button.secondary {
  background-color: #f1f5f9;
  color: #64748b;
  border: 1px solid #cbd5e1;
}

.dialog-button.secondary:hover {
  background-color: #e2e8f0;
  color: #475569;
}

/* Responsive */
@media (max-width: 960px) {
  .custom-dialog.p-dialog {
    width: 95vw !important;
  }

  .form-grid {
    gap: 1rem;
  }

  .custom-dialog .p-dialog-footer {
    flex-direction: column;
    gap: 0.75rem;
  }
  .dialog-button {
    width: 100%;
  }
}
</style>