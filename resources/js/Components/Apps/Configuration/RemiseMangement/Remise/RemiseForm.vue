<template>
  <div class="remise-form">
    <form @submit.prevent="handleSubmit" class="form-container">
      <div class="form-header">
        <h3 class="form-title">
          <i class="pi pi-tag mr-2"></i>
          {{ mode === 'add' ? 'Create New Remise' : 'Edit Remise' }}
        </h3>
        <p class="form-subtitle">Fill in the details below to {{ mode === 'add' ? 'create' : 'update' }} the remise.</p>
      </div>

      <Card class="form-card">
        <template #title>
          <div class="card-title">
            <i class="pi pi-info-circle mr-2"></i>
            Basic Information
          </div>
        </template>
        <template #content>
          <div class="p-fluid formgrid grid">
            <div class="field col-12 md:col-6">
              <label for="name" class="field-label required">Name</label>
              <InputText
                id="name"
                v-model="form.name"
                :class="{ 'p-invalid': errors.name }"
                placeholder="Enter remise name (e.g., Summer Sale)"
                class="input-field"
              />
              <small v-if="errors.name" class="p-error">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                {{ errors.name }}
              </small>
            </div>

            <div class="field col-12 md:col-6">
              <label for="code" class="field-label required">Code</label>
              <InputText
                id="code"
                v-model="form.code"
                :class="{ 'p-invalid': errors.code }"
                placeholder="Enter unique code (e.g., SUMMER2024)"
                class="input-field"
                style="text-transform: uppercase;"
              />
              <small v-if="errors.code" class="p-error">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                {{ errors.code }}
              </small>
            </div>

            <div class="field col-12">
              <label for="description" class="field-label">Description</label>
              <Textarea
                id="description"
                v-model="form.description"
                rows="3"
                placeholder="Enter a detailed description of the remise"
                class="input-field"
                :autoResize="true"
              />
              <small class="field-hint">Optional: Provide additional details about this remise.</small>
            </div>
          </div>
        </template>
      </Card>

      <Card class="form-card">
        <template #title>
          <div class="card-title">
            <i class="pi pi-percentage mr-2"></i>
            Discount Configuration
          </div>
        </template>
        <template #content>
          <div class="p-fluid formgrid grid">
            <div class="field col-12 md:col-6">
              <label for="type" class="field-label required">Discount Type</label>
              <Dropdown
                id="type"
                v-model="form.type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': errors.type }"
                placeholder="Select discount type"
                class="input-field"
                @change="onTypeChange"
              >
                <template #option="slotProps">
                  <div class="flex align-items-center">
                    <i :class="slotProps.option.icon" class="mr-2"></i>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
              </Dropdown>
              <small v-if="errors.type" class="p-error">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                {{ errors.type }}
              </small>
            </div>

            <div class="field col-12 md:col-6">
              <label class="field-label">Status</label>
              <div class="status-toggle">
                <InputSwitch
                  v-model="form.is_active"
                  :class="form.is_active ? 'switch-active' : 'switch-inactive'"
                />
                <span class="status-label" :class="form.is_active ? 'text-green-600' : 'text-red-600'">
                  <i :class="form.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'" class="mr-1"></i>
                  {{ form.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <small class="field-hint">Toggle to activate or deactivate this remise.</small>
            </div>

            <div class="field col-12 md:col-6" v-if="form.type === 'fixed'">
              <label for="amount" class="field-label required">
                <i class="pi pi-money-bill mr-1"></i>
                Fixed Amount
              </label>
              <InputNumber
                id="amount"
                v-model="form.amount"
                mode="currency"
                currency="DZD"
                locale="fr-DZ"
                :class="{ 'p-invalid': errors.amount }"
                placeholder="0.00"
                class="input-field"
                :min="0"
                :step="0.01"
              />
              <small v-if="errors.amount" class="p-error">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                {{ errors.amount }}
              </small>
              <small v-else class="field-hint">Enter the fixed discount amount in Algerian Dinars (DZD).</small>
            </div>

            <div class="field col-12 md:col-6" v-if="form.type === 'percentage'">
              <label for="percentage" class="field-label required">
                <i class="pi pi-percentage mr-1"></i>
                Percentage
              </label>
              <InputNumber
                id="percentage"
                v-model="form.percentage"
                suffix="%"
                :min="0"
                :max="100"
                :class="{ 'p-invalid': errors.percentage }"
                placeholder="0"
                class="input-field"
                :step="1"
              />
              <small v-if="errors.percentage" class="p-error">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                {{ errors.percentage }}
              </small>
              <small v-else class="field-hint">Enter percentage between 0 and 100.</small>
            </div>
          </div>
        </template>
      </Card>

      <Card class="form-card">
        <template #title>
          <div class="card-title">
            <i class="pi pi-users mr-2"></i>
            Assignment
          </div>
        </template>
        <template #content>
          <div class="p-fluid formgrid grid">
            <div class="field col-12 md:col-6">
              <label for="users" class="field-label">
                <i class="pi pi-user mr-1"></i>
                Assigned Users
              </label>
              <MultiSelect
                id="users"
                v-model="form.user_ids"
                :options="users"
                optionLabel="label"
                optionValue="value"
                placeholder="Select users to assign this remise"
                :maxSelectedLabels="2"
                class="input-field multiselect-field"
                :filter="true"
                filterPlaceholder="Search users..."
                :showToggleAll="true"
                :loading="!users.length"
              >
                <template #option="slotProps">
                  <div class="flex align-items-center">
                    <i class="pi pi-user mr-2 text-primary"></i>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
                <template #selectedItems="slotProps">
                  <div v-if="slotProps.value.length" class="inline-flex align-items-center py-1 px-2 m-1 text-sm bg-primary text-primary-contrast border-round">
                    <i class="pi pi-user mr-1"></i>
                    <span>{{ slotProps.value.length }} user(s) selected</span>
                  </div>
                  <span v-else>Select users to assign this remise</span>
                </template>
              </MultiSelect>
              <small class="field-hint">Choose which users can use this remise.</small>
            </div>

            <div class="field col-12 md:col-6">
              <label for="prestations" class="field-label">
                <i class="pi pi-cog mr-1"></i>
                Applicable Prestations
              </label>
              <MultiSelect
                id="prestations"
                v-model="form.prestation_ids"
                :options="prestations"
                optionLabel="label"
                optionValue="value"
                placeholder="Select applicable prestations"
                :maxSelectedLabels="2"
                class="input-field multiselect-field"
                :filter="true"
                filterPlaceholder="Search prestations..."
                :showToggleAll="true"
                :loading="!prestations.length"
              >
                <template #option="slotProps">
                  <div class="flex align-items-center">
                    <i class="pi pi-cog mr-2 text-primary"></i>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
                <template #selectedItems="slotProps">
                  <div v-if="slotProps.value.length" class="inline-flex align-items-center py-1 px-2 m-1 text-sm bg-primary text-primary-contrast border-round">
                    <i class="pi pi-cog mr-1"></i>
                    <span>{{ slotProps.value.length }} prestation(s) selected</span>
                  </div>
                  <span v-else>Select applicable prestations</span>
                </template>
              </MultiSelect>
              <small class="field-hint">Select which prestations this remise applies to.</small>
            </div>
          </div>
        </template>
      </Card>

      <div class="form-actions">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="p-button-outlined p-button-secondary action-button"
          @click="$emit('cancel')"
          type="button"
          :disabled="loading"
        />
        <Button
          :label="mode === 'add' ? 'Create Remise' : 'Update Remise'"
          :icon="mode === 'add' ? 'pi pi-plus' : 'pi pi-check'"
          type="submit"
          :loading="loading"
          class=""
          :disabled="!isFormValid || loading"
        />
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, watch, computed, defineProps, defineEmits } from 'vue'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  remise: {
    type: Object,
    default: () => ({})
  },
  mode: {
    type: String,
    default: 'add'
  },
  users: {
    type: Array,
    default: () => []
  },
  prestations: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['save', 'cancel'])

const form = ref({
  name: '',
  description: '',
  code: '',
  type: 'fixed',
  amount: null,
  percentage: null,
  is_active: true,
  user_ids: [],
  prestation_ids: []
})

const errors = ref({})

const typeOptions = [
  { label: 'Fixed Amount', value: 'fixed', icon: 'pi pi-euro' },
  { label: 'Percentage', value: 'percentage', icon: 'pi pi-percentage' }
]

// Computed property to check if form is valid
const isFormValid = computed(() => {
  // Clear all errors before re-evaluating validity
  errors.value = {};

  let valid = true;

  if (!form.value.name?.trim()) {
    errors.value.name = 'Name is required.';
    valid = false;
  }

  if (!form.value.code?.trim()) {
    errors.value.code = 'Code is required.';
    valid = false;
  }

  if (!form.value.type) {
    errors.value.type = 'Discount type is required.';
    valid = false;
  }

  if (form.value.type === 'fixed') {
    if (form.value.amount === null || form.value.amount === '' || form.value.amount <= 0) {
      errors.value.amount = 'Fixed amount must be greater than 0.';
      valid = false;
    }
  } else if (form.value.type === 'percentage') {
    if (form.value.percentage === null || form.value.percentage === '' || form.value.percentage < 0 || form.value.percentage > 100) {
      errors.value.percentage = 'Percentage must be between 0 and 100.';
      valid = false;
    }
  }

  return valid;
})

const onTypeChange = () => {
  // Clear the other field when type changes
  if (form.value.type === 'fixed') {
    form.value.percentage = null
    // Clear percentage error if present
    delete errors.value.percentage
  } else {
    form.value.amount = null
    // Clear amount error if present
    delete errors.value.amount
  }
  // Clear any general errors related to both fields
  delete errors.value.amount
  delete errors.value.percentage
}

const handleSubmit = () => {
  // isFormValid computed property will update errors.value
  if (isFormValid.value) {
    // Prepare data for submission
    const formData = {
      ...form.value,
      name: form.value.name?.trim(),
      code: form.value.code?.trim().toUpperCase(),
      description: form.value.description?.trim()
    }
    
    // Remove the unused field based on type
    if (formData.type === 'fixed') {
      delete formData.percentage
    } else {
      delete formData.amount
    }
    
    emit('save', formData)
  }
}

// Watch for prop changes to initialize the form
watch(() => props.remise, (newRemise) => {
  if (newRemise && Object.keys(newRemise).length > 0) {
    form.value = {
      ...newRemise,
      // Ensure user_ids and prestation_ids are arrays of IDs for MultiSelect
      user_ids: newRemise.users ? newRemise.users.map(user => user.id) : [],
      prestation_ids: newRemise.prestations ? newRemise.prestations.map(prestation => prestation.id) : []
    }
    
    // Ensure proper initialization of amount/percentage based on type
    if (newRemise.type === 'percentage' && newRemise.percentage !== undefined) {
      form.value.amount = null
    } else if (newRemise.type === 'fixed' && newRemise.amount !== undefined) {
      form.value.percentage = null
    }
  } else {
    // Reset form for 'add' mode if no remise prop is provided
    form.value = {
      name: '',
      description: '',
      code: '',
      type: 'fixed',
      amount: null,
      percentage: null,
      is_active: true,
      user_ids: [],
      prestation_ids: []
    }
  }
  // Clear errors when remise prop changes
  errors.value = {};
}, { immediate: true })

// Auto-convert code to uppercase
watch(() => form.value.code, (newCode) => {
  if (newCode) {
    form.value.code = newCode.toUpperCase()
  }
})
</script>

<style scoped>
/* Base Form Layout */
.remise-form {
  max-width: 800px;
  margin: 0 auto;
  padding: 1.5rem; /* Increased padding */
  font-family: 'Inter', sans-serif; /* Consistent modern font */
  color: #334155; /* Default text color */
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 2rem; /* Increased gap between sections */
}

/* Form Header */
.form-header {
  text-align: center;
  margin-bottom: 1rem;
  background-color: var(--surface-card); /* Added background */
  padding: 1.5rem; /* Added padding */
  border-radius: var(--border-radius); /* Rounded corners */
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* Soft shadow */
}

.form-title {
  color: var(--primary-color);
  font-size: 1.8rem; /* Larger title */
  font-weight: 700; /* Bolder font */
  margin: 0 0 0.75rem 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.form-subtitle {
  color: var(--text-color-secondary);
  margin: 0;
  font-size: 1rem; /* Slightly larger subtitle */
  line-height: 1.5;
}

/* Card Styling for Sections */
.form-card {
  border: 1px solid var(--surface-border);
  border-radius: 12px; /* More rounded corners */
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08); /* Stronger, modern shadow */
  transition: all 0.3s ease-in-out;
  overflow: hidden; /* Ensures content respects border-radius */
}

.form-card:hover {
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12); /* Deeper shadow on hover */
  transform: translateY(-2px);
}

.card-title {
  color: var(--primary-color);
  font-weight: 700; /* Bolder title */
  font-size: 1.25rem; /* Larger card title */
  display: flex;
  align-items: center;
  padding-bottom: 0.75rem; /* Spacing below title */
  border-bottom: 1px solid var(--surface-border); /* Separator */
  margin-bottom: 1.5rem; /* Space below separator */
  padding-left: 1.5rem; /* Align title with content */
  padding-right: 1.5rem;
  padding-top: 1.5rem;
}

/* PrimeVue Card Content Override */
:deep(.p-card .p-card-body) {
    padding: 0 !important; /* Reset default to control with inner classes */
}
:deep(.p-card .p-card-content) {
    padding: 1.5rem !important; /* Apply custom padding to content */
}


/* Field Specific Styling */
.field-label {
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.6rem; /* More space below label */
  display: inline-flex;
  align-items: center;
  font-size: 0.95rem; /* Slightly larger label */
}

.field-label.required::after {
  content: ' *';
  color: var(--red-500);
  font-weight: bold;
  margin-left: 0.25rem; /* Space the asterisk */
}

.input-field {
  transition: all 0.2s ease-in-out; /* Smoother transition */
  border-color: var(--surface-border); /* Default border color */
  border-radius: var(--border-radius); /* Consistent border-radius */
  padding: 0.85rem 1rem; /* More padding */
  font-size: 1rem;
}

.input-field:focus {
  border-color: var(--primary-color); /* Highlight border on focus */
  box-shadow: 0 0 0 0.2rem var(--primary-color-lighter); /* Consistent focus ring */
}

.field-hint {
  color: var(--text-color-secondary);
  font-size: 0.85rem; /* Slightly larger hint text */
  margin-top: 0.4rem; /* More space above hint */
  display: block;
}

/* Status Toggle */
.status-toggle {
  display: flex;
  align-items: center;
  gap: 1rem; /* Increased gap */
  padding: 0.75rem 0; /* More vertical padding */
}

.status-label {
  font-weight: 600; /* Bolder status label */
  transition: color 0.3s ease;
  display: flex;
  align-items: center;
}

/* Override PrimeVue InputSwitch colors */
:deep(.p-inputswitch.switch-active .p-inputswitch-slider) {
  background-color: var(--green-500) !important;
}

:deep(.p-inputswitch.switch-inactive .p-inputswitch-slider) {
  background-color: var(--red-500) !important;
}

/* MultiSelect Specifics */
.multiselect-field {
  min-height: 44px; /* Slightly taller for better touch targets */
}

/* Custom MultiSelect selected items display */
:deep(.p-multiselect .p-multiselect-label.p-placeholder) {
    color: var(--text-color-secondary); /* Placeholder color */
}

:deep(.p-multiselect-panel .p-multiselect-items .p-multiselect-item) {
  padding: 0.8rem 1.25rem; /* More padding for options */
}

:deep(.p-multiselect-panel .p-multiselect-items .p-multiselect-item:hover) {
  background-color: var(--primary-50); /* Hover background for options */
  color: var(--primary-color); /* Hover text color */
}

:deep(.p-multiselect-token) {
    background-color: var(--primary-color);
    color: var(--primary-color-text);
    border-radius: var(--border-radius);
    font-size: 0.85rem;
    padding: 0.4rem 0.6rem;
}

:deep(.p-multiselect-token-icon) {
    font-size: 0.7rem;
    margin-left: 0.5rem;
}


/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1.25rem; /* Increased gap */
  padding: 1.5rem 0; /* More vertical padding */
  border-top: 1px solid var(--surface-border);
  margin-top: 1.5rem; /* More margin */
}

.action-button {
  padding: 0.85rem 1.75rem; /* Larger buttons */
  font-weight: 600;
  border-radius: var(--border-radius); /* Consistent border-radius */
  transition: all 0.2s ease-in-out; /* Smoother transition */
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* Soft button shadow */
}

/* Primary Button (Create/Update) */
.p-button-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark-color) 100%);
    color: var(--primary-color-text);
    border: none; /* No border for gradient buttons */
}
.p-button-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark-color) 0%, var(--primary-dark-color-hover) 100%);
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* Secondary Button (Cancel) */
.p-button-secondary {
    background: var(--surface-50);
    color: var(--text-color);
    border: 1px solid var(--surface-border);
}
.p-button-secondary:hover {
    background: var(--surface-100);
    border-color: var(--surface-d);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.action-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .form-actions {
    flex-direction: column-reverse; /* Stack buttons on small screens */
    gap: 0.75rem;
  }

  .action-button {
    width: 100%;
  }

  .form-title {
    font-size: 1.5rem; /* Adjusted for smaller screens */
  }

  .form-header, .form-card, .p-card .p-card-content {
    padding: 1rem; /* Adjusted padding for smaller screens */
  }

  .card-title {
    font-size: 1.1rem;
    padding-left: 1rem;
    padding-right: 1rem;
    padding-top: 1rem;
  }
}

/* Error states */
.p-invalid {
  border-color: var(--red-500) !important;
  box-shadow: 0 0 0 0.15rem rgba(239, 68, 68, 0.2) !important; /* Thinner error ring */
}

.p-error {
  color: var(--red-500);
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
}

/* Loading state for buttons */
.p-button[aria-expanded="true"] {
  pointer-events: none;
}

/* Animation for form cards */
.form-card {
  animation: slideInUp 0.4s ease-out; /* Slightly longer animation */
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>