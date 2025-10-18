<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import PatientSearch from '../../../../Pages/Appointments/PatientSearch.vue'
import { ficheNavetteService } from '../../../../Components/Apps/services/Emergency/ficheNavetteService'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  fiche: { type: Object, default: null },
  mode: { type: String, default: 'create' }
})

const emit = defineEmits(['update:modelValue', 'saved'])

// Modal state
const saving = ref(false)
const errors = reactive({})

const formData = reactive({
  patient: null,
  companion: null,
  is_emergency: true,
  fiche_date: new Date().toISOString().split('T')[0],
  status: 'pending'
})

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const modalTitle = computed(() => 
  props.mode === 'create' ? 'New Shuttle Form' : 'Edit Shuttle Form'
)

const closeModal = () => {
  emit('update:modelValue', false)
  resetForm()
}

const resetForm = () => {
  formData.patient = null
  formData.companion = null
  formData.is_emergency = true
  formData.fiche_date = new Date().toISOString().split('T')[0]
  formData.status = 'pending'
  Object.keys(errors).forEach(key => delete errors[key])
}

const handleSubmit = async () => {
  if (!validateForm()) {
    alert('Please correct the form errors')
    return
  }

  saving.value = true
  try {
    const payload = {
      patient_id: formData.patient.id,
      companion_id: formData.companion?.id || null,
      fiche_date: formData.fiche_date,
      is_emergency: formData.is_emergency,
      status: formData.status
    }

    const result = props.mode === 'create' 
      ? await ficheNavetteService.createFicheNavette(payload)
      : await ficheNavetteService.update(props.fiche.id, payload)

    if (result.success) {
      alert(props.mode === 'create' ? 'Form created successfully' : 'Form updated successfully')
      emit('saved', result.data, props.mode)
      closeModal()
    } else {
      alert('Error: ' + result.message)
    }
  } catch (error) {
    alert('An unexpected error occurred')
  } finally {
    saving.value = false
  }
}

const validateForm = () => {
  Object.keys(errors).forEach(key => delete errors[key])
  if (!formData.patient?.id) errors.patient_id = 'Please select a patient'
  if (!formData.fiche_date) errors.fiche_date = 'Please select a date'
  if (!formData.status) errors.status = 'Please select a status'
  return Object.keys(errors).length === 0
}

watch(() => props.modelValue, (val) => {
  if (val && props.mode === 'edit') {
    formData.patient = { id: props.fiche.patient_id, name: props.fiche.patient_name }
    formData.companion = props.fiche.companion_id ? { id: props.fiche.companion_id, name: props.fiche.companion_name } : null
    formData.is_emergency = props.fiche.is_emergency
    formData.fiche_date = props.fiche.fiche_date
    formData.status = props.fiche.status
  }
})
</script>

<template>
  <div v-if="modelValue" class="tw-fixed tw-inset-0 tw-z-50 tw-flex tw-items-center tw-justify-center tw-bg-black/50">
    <div class="tw-bg-white tw-rounded-lg tw-w-full tw-max-w-2xl tw-max-h-[90vh] tw-overflow-y-auto tw-shadow-xl">
      
      <!-- Header -->
      <div class="tw-bg-blue-600 tw-text-white tw-px-6 tw-py-4 tw-rounded-t-lg">
        <h2 class="tw-text-xl tw-font-semibold">{{ modalTitle }}</h2>
      </div>

      <!-- Body -->
      <div class="tw-p-6 tw-space-y-6">
        <!-- Patient Section -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Patient *</label>
          <PatientSearch 
            :modelValue="formData.patient?.name || ''"
            @patientSelected="(p) => formData.patient = p"
            class="tw-w-full"
          />
          <p v-if="errors.patient_id" class="tw-text-red-500 tw-text-sm tw-mt-1">{{ errors.patient_id }}</p>
        </div>

        <!-- Companion Section -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Companion</label>
          <PatientSearch 
            :modelValue="formData.companion?.name || ''"
            @patientSelected="(c) => formData.companion = c"
            class="tw-w-full"
          />
        </div>

        <!-- Emergency Checkbox -->
        <div class="tw-flex tw-items-center tw-space-x-3 tw-p-4 tw-bg-red-50 tw-rounded-lg tw-border tw-border-red-200">
          <input 
            type="checkbox" 
            id="is_emergency"
            v-model="formData.is_emergency"
            class="tw-w-5 tw-h-5 tw-text-red-600 tw-bg-white tw-border-red-300 tw-rounded focus:tw-ring-red-500 focus:tw-ring-2"
          />
          <label for="is_emergency" class="tw-text-sm tw-font-medium tw-text-red-800 tw-cursor-pointer">
            🚨 Emergency Case
          </label>
          <p v-if="errors.is_emergency" class="tw-text-red-500 tw-text-sm">{{ errors.is_emergency }}</p>
        </div>

      
      </div>

      <!-- Footer -->
      <div class="tw-px-6 tw-py-4 tw-bg-gray-50 tw-flex tw-justify-end tw-gap-3 tw-rounded-b-lg">
        <button @click="closeModal" class="tw-px-4 tw-py-2 tw-border tw-rounded-md hover:tw-bg-gray-100">
          Cancel
        </button>
        <button @click="handleSubmit" :disabled="saving"
          class="tw-px-4 tw-py-2 tw-bg-blue-600 tw-text-white tw-rounded-md hover:tw-bg-blue-700 disabled:tw-opacity-50">
          {{ saving ? 'Saving...' : (mode === 'create' ? 'Create' : 'Edit') }}
        </button>
      </div>
    </div>
  </div>
</template>
