<script setup>
import { defineProps, defineEmits } from 'vue';

// PrimeVue Components
import Dialog from 'primevue/dialog';
// import Dropdown from 'primevue/dropdown'; // REMOVE THIS
import MultiSelect from 'primevue/multiselect'; // ENSURE THIS IS IMPORTED

const props = defineProps({
  visible: {
    type: Boolean,
    required: true,
  },
  // CHANGED: paymentMethodToAssign -> paymentMethodsToAssign (now an Array)
  paymentMethodsToAssign: {
    type: Array, // Now an array of keys
    default: () => [], // Default to an empty array
  },
  selectedUsersForAssignment: {
    type: Array, // This array will contain full user objects (id, name, email, etc.)
    required: true,
  },
  assignSubmitted: {
    type: Boolean,
    required: true,
  },
  paymentOptionsDropdown: {
    type: Array,
    required: true,
  },
  users: {
    type: Array, // This prop receives ALL users from the parent component
    required: true,
  },
});

const emit = defineEmits([
  'hide-dialog',
  'perform-bulk-assignment',
  'select-all-active-users',
  'remove-user-chip',
  // CHANGED: update:payment-method -> update:payment-methods
  'update:payment-methods',
  'update:selected-users',
]);
</script>
<template>
  <Dialog
    :visible="visible"
    header="Assign Payment Methods to Users"
    :modal="true"
    class="custom-dialog"
    :style="{ width: '60vw' }"
    @update:visible="$emit('hide-dialog')"
  >
    <div class="form-grid">
      <div class="form-field">
        <label for="assignMethods">Select Payment Methods to Assign</label>
        <MultiSelect
          id="assignMethods"
          :modelValue="paymentMethodsToAssign"
          @update:modelValue="(value) => $emit('update:payment-methods', value)"
          :options="paymentOptionsDropdown"
          optionLabel="name"
          optionValue="key"
          placeholder="Choose method(s)"
          display="chip"
          class="p-fluid"
          :class="{ 'p-invalid': assignSubmitted && paymentMethodsToAssign.length === 0 }"
        />
        <small class="p-error" v-if="assignSubmitted && paymentMethodsToAssign.length === 0"
          >At least one payment method is required.</small
        >
      </div>

      <div class="form-field overflow-hidden">
        <label for="usersToAssign">Select Users</label>
        <MultiSelect
          id="usersToAssign"
          :modelValue="selectedUsersForAssignment"
          @update:modelValue="(value) => $emit('update:selected-users', value)"
          :options="users"
          optionLabel="name"
          placeholder="Select users to assign"
          display="chip"
          class="p-fluid assign-users-multiselect"
          :class="{ 'p-invalid': assignSubmitted && selectedUsersForAssignment.length === 0 }"
          :filter="true"
          filterPlaceholder="Search users..."
          :filterFields="['name', 'email']"
        >
          <template #header>
            <div class="p-multiselect-filter-container-custom">
              <button @click="$emit('select-all-active-users')" class="select-active-button">
                <i class="fas fa-user-check button-icon"></i>
                Select All Active Users
              </button>
            </div>
          </template>
          <template #option="slotProps">
            <div class="flex align-items-center">
              <div>{{ slotProps.option.name }} ({{ slotProps.option.email }})</div>
            </div>
          </template>
          <template #chip="slotProps">
            <div class="p-multiselect-token">
              <span class="user-chip">{{ slotProps.value.name }}</span>
              <span
                class="p-multiselect-token-icon pi pi-times-circle"
                @click="$emit('remove-user-chip', slotProps.value)"
              ></span>
            </div>
          </template>
        </MultiSelect>
        <small class="p-error" v-if="assignSubmitted && selectedUsersForAssignment.length === 0"
          >At least one user must be selected.</small
        >
      </div>
    </div>

    <template #footer>
      <button class="dialog-button secondary" @click="$emit('hide-dialog')">
        <i class="fas fa-times"></i> Cancel
      </button>
      <button class="dialog-button primary" @click="$emit('perform-bulk-assignment')">
        <i class="fas fa-plus-circle"></i> Assign Selected
      </button>
    </template>
  </Dialog>
</template>



<style scoped>
/* No changes needed in styles for this functionality, as PrimeVue's MultiSelect
   already has the necessary styling classes that your existing CSS leverages.
   The .p-multiselect-token and .p-multiselect-token-icon styles will apply correctly. */
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

/* MultiSelect specific styling for bulk assignment dialog (FIXED/IMPROVED) */

.assign-users-multiselect.p-multiselect .p-multiselect-label {
  min-height: 2.8rem; /* Ensure consistent height even when empty */
  display: flex;
  align-items: center; /* Vertically center content */
  padding: 0.25rem 0.5rem; /* Reduced padding here, actual chips will have padding */
  line-height: normal; /* Important for vertical alignment */
}

/* This is the container for the chips */
.assign-users-multiselect.p-multiselect .p-multiselect-token-container {
  max-height: 120px; /* Limit the height of the chip display area */
  overflow-y: auto; /* Add scrollbar if chips exceed max-height */
  padding: 0.25rem 0.5rem; /* Padding inside the scrollable area */
  width: 100%; /* Ensure it takes full width */
  display: flex; /* Enable flexbox for chips */
  flex-wrap: wrap; /* Enable wrapping of chips */
  gap: 0.5rem 0.5rem; /* Vertical and horizontal gap between chips */
  align-items: flex-start; /* Align chips to the top */
  margin: 0; /* Remove any default margins */
}

/* Styling for individual chips */
.assign-users-multiselect.p-multiselect .p-multiselect-token {
  /* PrimeVue adds .p-multiselect-token to each chip wrapper */
  display: inline-flex; /* Use flex to align text and close icon */
  align-items: center; /* Vertically center icon and text */
  background-color: #e0f2fe; /* Your desired chip background (light blue) */
  border: 1px solid #93c5fd; /* Light blue border */
  border-radius: 0.3rem;
  padding: 0.25rem 0.5rem; /* Padding inside each chip */
  font-size: 0.85rem;
  color: #1e40af; /* Your desired chip text color (dark blue) */
  white-space: nowrap; /* Prevent chip text from wrapping */
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); /* Subtle shadow for definition */
}

.assign-users-multiselect.p-multiselect .p-multiselect-token .user-chip {
  /* Your specific text span inside the token */
  font-weight: 500;
}

.assign-users-multiselect.p-multiselect .p-multiselect-token .p-multiselect-token-icon {
  /* The close icon on each chip */
  margin-left: 0.5rem; /* Space between text and icon */
  font-size: 0.75rem; /* Adjust icon size if needed */
  color: #1e40af; /* Match icon color to text color */
  cursor: pointer;
  transition: color 0.2s;
}

.assign-users-multiselect.p-multiselect .p-multiselect-token .p-multiselect-token-icon:hover {
  color: #dc2626; /* Red on hover for delete */
}

/* NEW: Custom template for #chip slot */
.p-multiselect-token {
  /* This targets the wrapper created by PrimeVue */
  /* We'll override some of PrimeVue's default styling on this */
  margin: 0 !important; /* Remove default margin that could interfere with gaps */
}

/* Styling for the new "Select All Active Users" button */
.p-multiselect-filter-container-custom {
  display: flex;
  flex-direction: column; /* Stack filter and button vertically */
  gap: 0.75rem; /* Space between filter and button */
  padding: 0.5rem 0.75rem 0; /* Adjust padding for the header slot content */
  border-bottom: 1px solid #e2e8f0; /* Add a separator */
  padding-bottom: 0.75rem;
  margin-bottom: 0.75rem;
}

.select-active-button {
  display: inline-flex;
  align-items: center;
  justify-content: center; /* Center content in button */
  width: 100%; /* Take full width of its container */
  padding: 0.6rem 1rem;
  background-color: #e0f2fe; /* light blue */
  color: #1e40af; /* dark blue */
  font-weight: 600;
  border-radius: 0.5rem;
  border: 1px solid #93c5fd; /* blue-300 */
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.select-active-button:hover {
  background-color: #bfdbfe; /* slightly darker blue */
  border-color: #60a5fa; /* blue-400 */
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
1