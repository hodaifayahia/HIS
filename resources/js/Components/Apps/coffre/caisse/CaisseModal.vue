<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="pi pi-desktop"></i>
          {{ isEditing ? 'Edit Cash Register' : 'New Cash Register' }}
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="saveCaisse">
          <!-- Name Field -->
          <div class="form-group mb-3">
            <label for="name" class="form-label">
              <i class="pi pi-tag"></i>
              Cash Register Name *
            </label>
            <InputText
              v-model="form.name"
              id="name"
              placeholder="Enter cash register name"
              class="w-full"
              :class="{ 'p-invalid': errors.name }"
              :disabled="saving"
            />
            <small v-if="errors.name" class="p-error">
              {{ errors.name[0] }}
            </small>
          </div>

          <!-- Service Selection -->
          <div class="form-group mb-3">
            <label for="service_id" class="form-label">
              <i class="pi pi-building"></i>
              Service *
            </label>
            <Dropdown
              v-model="form.service_id"
              :options="services"
              option-label="name"
              option-value="id"
               appendTo="self"
              placeholder="Select service"
              class="w-full"
              :class="{ 'p-invalid': errors.service_id }"
              :disabled="saving"
              :loading="loadingServices"
            />
            <small v-if="errors.service_id" class="p-error">
              {{ errors.service_id[0] }}
            </small>
          </div>

          <!-- Location Field -->
          <div class="form-group mb-3">
            <label for="location" class="form-label">
              <i class="pi pi-map-marker"></i>
              Location
            </label>
            <InputText
              v-model="form.location"
              id="location"
              placeholder="Enter location (optional)"
              class="w-full"
              :class="{ 'p-invalid': errors.location }"
              :disabled="saving"
            />
            <small v-if="errors.location" class="p-error">
              {{ errors.location[0] }}
            </small>
            <small class="form-text">
              Specify where this cash register is physically located
            </small>
          </div>

          <!-- Status Toggle -->
          <div class="form-group mb-4">
            <div class="status-group">
              <div class="status-info">
                <label class="form-label">
                  <i class="pi pi-power-off"></i>
                  Status
                </label>
                <small class="form-text">
                  {{ form.is_active ? 'Cash register is active and ready to use' : 'Cash register is inactive and cannot be used' }}
                </small>
              </div>
              <div class="status-toggle">
                <InputSwitch
                  v-model="form.is_active"
                  :disabled="saving"
                />
                <span class="status-label" :class="form.is_active ? 'text-green' : 'text-gray'">
                  {{ form.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>

         

          <!-- Action Buttons -->
          <div class="button-group">
            <Button
              type="button"
              label="Cancel"
              icon="pi pi-times"
              class="p-button-secondary flex-fill"
              @click="$emit('close')"
              :disabled="saving"
            />
            <Button
              type="submit"
              :label="saving ? 'Saving...' : (isEditing ? 'Update Cash Register' : 'Create Cash Register')"
              :icon="saving ? 'pi pi-spin pi-spinner' : (isEditing ? 'pi pi-check' : 'pi pi-plus')"
              class="p-button-primary flex-fill"
              :disabled="saving || !isFormValid"
            />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import axios from 'axios';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import {caisseService } from '../../services/Coffre/CaisseService';

// Props
const props = defineProps({
  caisse: {
    type: Object,
    default: null
  },
  isEditing: {
    type: Boolean,
    default: false
  }
});

// Emits
const emit = defineEmits(['close', 'saved']);

// Reactive state
const services = ref([]);
const loadingServices = ref(false);
const saving = ref(false);
const errors = ref({});

const form = reactive({
  name: '',
  location: '',
  is_active: true,
  service_id: ''
});

// Computed
const isFormValid = computed(() => {
  return form.name.trim() !== '' && form.service_id !== '';
});

const selectedServiceName = computed(() => {
  if (!form.service_id) return '';
  const service = services.value.find(s => s.id === form.service_id);
  return service ? service.name : '';
});

// Methods
const loadServices = async () => {
  loadingServices.value = true;
  try {
    const res = await axios.get('/api/services');
    services.value = res.data.data ?? res.data ?? [];
  } catch (error) {
    console.error('Error loading services:', error);
  } finally {
    loadingServices.value = false;
  }
};

const resetForm = () => {
  form.name = '';
  form.location = '';
  form.is_active = true;
  form.service_id = '';
  errors.value = {};
};

const populateForm = () => {
  if (props.caisse && props.isEditing) {
    form.name = props.caisse.name || '';
    form.location = props.caisse.location || '';
    form.is_active = props.caisse.is_active ?? true;
    form.service_id = props.caisse.service_id || '';
  }
};

const saveCaisse = async () => {
  if (!isFormValid.value) return;
  
  saving.value = true;
  errors.value = {};

  const data = {
    name: form.name.trim(),
    location: form.location.trim() || null,
    is_active: form.is_active,
    service_id: form.service_id
  };

  try {
    const result = props.isEditing
      ? await caisseService.update(props.caisse.id, data)
      : await caisseService.create(data);

    if (result.success) {
    emit('saved', result.data ?? { message: result.message });
     emit('close');
    } else {
      if (result.errors) {
        errors.value = result.errors;
      } else {
        // Show general error message
        console.error('Save failed:', result.message);
        alert(result.message);
      }
    }
  } catch (error) {
    console.error('Error saving caisse:', error);
    alert('An unexpected error occurred. Please try again.');
  } finally {
    saving.value = false;
  }
};

// Watchers
watch(() => props.caisse, () => {
  if (props.isEditing) {
    populateForm();
  } else {
    resetForm();
  }
}, { immediate: true });

// Lifecycle
onMounted(() => {
  loadServices();
  if (props.isEditing) {
    populateForm();
  }
});
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 15px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
  border-radius: 15px 15px 0 0;
}

.modal-title {
  margin: 0;
  font-weight: 600;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.2rem;
  padding: 0.5rem;
  border-radius: 50%;
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  cursor: pointer;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: rotate(90deg);
}

.modal-body {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.form-text {
  color: #6b7280;
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: block;
}

.status-group {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f9fafb;
}

.status-info {
  flex: 1;
}

.status-toggle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
}

.status-label {
  font-weight: 600;
  font-size: 0.9rem;
  transition: color 0.2s;
}

.text-green {
  color: #10b981;
}

.text-gray {
  color: #6b7280;
}

/* Preview Section */
.preview-section {
  border-top: 1px solid #e5e7eb;
  padding-top: 1.5rem;
}

.preview-title {
  color: #374151;
  font-weight: 600;
  margin-bottom: 1rem;
  font-size: 0.95rem;
}

.preview-card {
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.preview-inactive {
  opacity: 0.7;
}

.preview-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-active {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.preview-inactive-header {
  background: linear-gradient(135deg, #6b7280, #4b5563);
  color: white;
}

.preview-icon {
  width: 35px;
  height: 35px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.preview-content {
  padding: 1rem 1.5rem;
  background: white;
}

.preview-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 1.1rem;
  margin-bottom: 0.75rem;
}

.preview-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.preview-detail {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #6b7280;
}

.preview-detail i {
  width: 14px;
  flex-shrink: 0;
  color: #8b5cf6;
}

/* Button Group */
.button-group {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.flex-fill {
  flex: 1;
}

.p-error {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.w-full {
  width: 100%;
}

/* PrimeVue Component Overrides */
:deep(.p-inputtext),
:deep(.p-dropdown) {
  border-radius: 10px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

:deep(.p-inputtext:hover),
:deep(.p-dropdown:not(.p-disabled):hover) {
  border-color: #8b5cf6;
}

:deep(.p-inputtext:focus),
:deep(.p-dropdown:not(.p-disabled).p-focus) {
  border-color: #8b5cf6;
  box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
}

:deep(.p-invalid) {
  border-color: #ef4444;
}

:deep(.p-invalid:focus) {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
}

:deep(.p-button) {
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.2s ease;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
}

:deep(.p-button:disabled) {
  transform: none;
}

:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
  background: #10b981;
}

:deep(.p-inputswitch:not(.p-disabled):hover .p-inputswitch-slider) {
  background: #6b7280;
}

:deep(.p-inputswitch.p-inputswitch-checked:not(.p-disabled):hover .p-inputswitch-slider) {
  background: #059669;
}

:deep(.p-dropdown-panel) {
  border-radius: 10px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

:deep(.p-dropdown-item) {
  border-radius: 6px;
  margin: 2px 8px;
  transition: all 0.2s ease;
}

:deep(.p-dropdown-item:hover) {
  background-color: #f3f4f6;
  transform: translateX(2px);
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .modal-header {
    padding: 1rem 1.5rem;
  }
  
  .status-group {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .status-toggle {
    justify-content: space-between;
  }
  
  .button-group {
    flex-direction: column;
  }
  
  .preview-header {
    flex-direction: column;
    gap: 0.75rem;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .modal-title {
    font-size: 1.1rem;
  }
  
  .preview-details {
    font-size: 0.8rem;
  }
}
</style>
