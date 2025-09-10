<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import PatientSearch from '../../../../Pages/Appointments/PatientSearch.vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Calendar from 'primevue/calendar'
import Dropdown from 'primevue/dropdown'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'


import ficheNavetteService from '../../../../Components/Apps/services/Reception/ficheNavetteService';

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  fiche: {
    type: Object,
    default: null
  },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  }
})
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}
const formatDate = (date) => {
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).format(new Date(date))
}
// Emits
const emit = defineEmits(['update:visible', 'saved'])

// Composables
const toast = useToast()

// Reactive data
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const saving = ref(false)
const errors = reactive({})

// Form data - Fix the patient structure
const formData = reactive({
  patient: null,
  fiche_date: new Date(),
  status: 'pending'
})

// Static data
const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

// Computed
const modalTitle = computed(() => {
  return props.mode === 'create' ? 'New Shuttle Form' : 'Modify Shuttle Form'
})

const creatorName = computed(() => {
  // Replace with actual current user data
  return 'Dr. Martin (You)'
})

const isFormValid = computed(() => {
  return formData.patient && formData.fiche_date && formData.status
})

// Methods
const resetForm = () => {
  formData.patient = null
  formData.fiche_date = new Date()
  formData.status = 'pending'
  Object.keys(errors).forEach(key => delete errors[key])
}

const populateForm = () => {
  if (props.fiche && props.mode === 'edit') {
    formData.patient = {
      id: props.fiche.patient_id,
      name: props.fiche.patient_name
    }
    formData.fiche_date = new Date(props.fiche.fiche_date)
    formData.status = props.fiche.status
  }
}

const onPatientSelected = (patient) => {
  console.log('Patient selected:', patient); // Debug log
  formData.patient = patient;
  if (errors.patient_id) {
    delete errors.patient_id;
  }
}

// Fix the handleSubmit method
const handleSubmit = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Please correct the errors in the form',
      life: 3000
    })
    return
  }

  saving.value = true
  
  try {
    // Fix: Ensure patient.id exists
    if (!formData.patient || !formData.patient.id) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Please select a valid patient',
        life: 3000
      })
      saving.value = false
      return
    }

    const payload = {
      patient_id: formData.patient.id,
      fiche_date: formData.fiche_date.toISOString().split('T')[0],
      status: formData.status,
    }

    console.log('Payload being sent:', payload); // Debug log

    const result = props.mode === 'create' 
      ? await ficheNavetteService.createFicheNavette(payload)
      : await ficheNavetteService.update(props.fiche.id, payload)

    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: props.mode === 'create' ? 'Fiche navette created successfully' : 'Fiche navette updated successfully',
        life: 3000
      })
      emit('saved', result.data, props.mode)
      closeModal()
    } else {
      if (result.errors) {
        Object.assign(errors, result.errors)
      }
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: result.message,
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error in handleSubmit:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'An unexpected error occurred',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

// Fix the validation method
const validateForm = () => {
  Object.keys(errors).forEach(key => delete errors[key])
  
  if (!formData.patient || !formData.patient.id) {
    errors.patient_id = 'Please select a patient'
  }
  
  if (!formData.fiche_date) {
    errors.fiche_date = 'Please select a date'
  }
  
  if (!formData.status) {
    errors.status = 'Please select a status'
  }
  
  return Object.keys(errors).length === 0
}

// Lifecycle
onMounted(() => {
  if (props.visible && props.mode === 'edit') {
    populateForm()
  }
})
</script>


<template>
  <Dialog 
    v-model:visible="dialogVisible"
    :header="modalTitle"
    :style="{ width: '600px' }"
    :modal="true"
    :closable="true"
    :draggable="false"
    class="fiche-modal"
  >
    <template #header>
      <div class="modal-header">
        <div class="modal-title">
          <i class="pi pi-file-edit mr-2"></i>
          {{ modalTitle }}
        </div>
      </div>
    </template>

    <form @submit.prevent="handleSubmit" class="fiche-form">
      <div class="form-section">
        <h4 class="section-title">
          <i class="pi pi-user mr-2"></i>
          Patient Information
        </h4>
        
        <div class="field">
          <label for="patient" class="required">Patient *</label>
          <PatientSearch 
            id="patient"
            :modelValue="formData.patient ? `${formData.patient.first_name} ${formData.patient.last_name}` : ''"
            placeholder="Search and select a patient..."
            class="w-full"
            @patientSelected="onPatientSelected"
            :class="{ 'p-invalid': errors.patient_id }"
          />
          <small v-if="errors.patient_id" class="p-error">
            {{ errors.patient_id }}
          </small>
          <!-- Debug display -->
          <small v-if="formData.patient" class="p-text-secondary">
            Selected: {{ formData.patient.first_name }} {{ formData.patient.last_name }} (ID: {{ formData.patient.id }})
          </small>
        </div>
      </div>

      <div class="form-section">
        <h4 class="section-title">
          <i class="pi pi-calendar mr-2"></i>
          Form Details
        </h4>
        
        <div class="form-grid">
          <div class="field">
            <label for="fiche-date" class="required">Form Date *</label>
            <Calendar 
              id="fiche-date"
              v-model="formData.fiche_date"
              dateFormat="dd/mm/yy"
              showIcon
              showButtonBar
              class="w-full"
              :class="{ 'p-invalid': errors.fiche_date }"
              placeholder="Select a date"
            />
            <small v-if="errors.fiche_date" class="p-error">
              {{ errors.fiche_date }}
            </small>
          </div>

          <div class="field">
            <label for="status">Status</label>
            <Dropdown 
              id="status"
              v-model="formData.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select a status"
              class="w-full"
              :class="{ 'p-invalid': errors.status }"
            />
            <small v-if="errors.status" class="p-error">
              {{ errors.status }}
            </small>
          </div>
        </div>

        <!-- <div class="field">
          <label for="creator">Creator</label>
          <InputText 
            id="creator"
            v-model="creatorName"
            disabled
            class="w-full creator-input"
          />
          <small class="help-text">
            <i class="pi pi-info-circle mr-1"></i>
            The creator is automatically set
          </small>
        </div> -->
      </div>

      <div v-if="mode === 'edit' && fiche" class="form-section">
        <h4 class="section-title">
          <i class="pi pi-list mr-2"></i>
          Overview
        </h4>
        
        <div class="preview-grid">
          <div class="preview-item">
            <span class="preview-label">ID:</span>
            <Tag :value="`#${fiche.id}`" severity="info" />
          </div>
          
          <div class="preview-item">
            <span class="preview-label">Services:</span>
            <Badge 
              :value="fiche.items_count || 0" 
              :severity="fiche.items_count > 0 ? 'success' : 'warning'"
            />
          </div>
          
          <div class="preview-item">
            <span class="preview-label">Total Amount:</span>
            <strong class="amount-text">{{ formatCurrency(fiche.total_amount) }}</strong>
          </div>
          
          <div class="preview-item">
            <span class="preview-label">Created On:</span>
            <span>{{ formatDate(fiche.created_at) }}</span>
          </div>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="modal-footer">
        <Button 
          label="Cancel" 
          icon="pi pi-times" 
          class="p-button-text"
          @click="closeModal"
          :disabled="saving"
        />
        <Button 
          :label="mode === 'create' ? 'Create' : 'Modify'"
          :icon="mode === 'create' ? 'pi pi-plus' : 'pi pi-check'"
          class="p-button-primary"
          @click="handleSubmit"
          :loading="saving"
          :disabled="!isFormValid"
        />
      </div>
    </template>
  </Dialog>
</template>


<style scoped>
.fiche-modal :deep(.p-dialog-header) {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
  color: white;
  border-radius: 6px 6px 0 0;
}

.modal-header {
  display: flex;
  align-items: center;
  width: 100%;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
}

.fiche-form {
  padding: 1.5rem 0;
}

.form-section {
  margin-bottom: 2rem;
}

.form-section:last-child {
  margin-bottom: 0;
}

.section-title {
  color: var(--primary-color);
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 1.5rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--surface-200);
  display: flex;
  align-items: center;
}

.field {
  margin-bottom: 1.5rem;
}

.field:last-child {
  margin-bottom: 0;
}

.field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-color);
}

.field label.required::after {
  content: ' *';
  color: var(--red-500);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.creator-input {
  background: var(--surface-100) !important;
  font-style: italic;
}

.help-text {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 6px;
  border: 1px solid var(--surface-200);
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.preview-label {
  font-weight: 600;
  color: var(--text-color-secondary);
}

.amount-text {
  color: var(--primary-color);
  font-size: 1.1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

@media (max-width: 768px) {
  .fiche-modal :deep(.p-dialog) {
    width: 95vw !important;
    margin: 1rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .preview-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column-reverse;
  }
  
  .modal-footer button {
    width: 100%;
  }
}
</style>