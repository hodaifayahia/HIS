<template>
  <form @submit.prevent="handleSubmit" class="tw-space-y-6">
    <!-- Service Selection -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4">
      <div>
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Service <span class="tw-text-red-500">*</span>
        </label>
        <Dropdown 
          v-model="formData.service_id"
          :options="services"
          option-label="name"
          option-value="id"
          placeholder="Select a service"
          class="tw-w-full"
          :class="{
            'p-invalid': errors.service_id
          }"
        />
        <small v-if="errors.service_id" class="p-error">{{ errors.service_id[0] }}</small>
      </div>
    </div>

    <!-- Expected Date -->
    <div>
      <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
        Expected Date
      </label>
      <Calendar 
        v-model="formData.expected_date"
        placeholder="Select expected date"
        class="tw-w-full"
        :min-date="new Date()"
        dateFormat="yy-mm-dd"
        :class="{
          'p-invalid': errors.expected_date
        }"
      />
      <small v-if="errors.expected_date" class="p-error">{{ errors.expected_date[0] }}</small>
    </div>

    <!-- Notes -->
    <div>
      <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
        Notes
      </label>
      <Textarea 
        v-model="formData.notes"
        placeholder="Enter any additional notes or requirements..."
        :rows="4"
        class="tw-w-full"
        :class="{
          'p-invalid': errors.notes
        }"
      />
      <small v-if="errors.notes" class="p-error">{{ errors.notes[0] }}</small>
    </div>

    <!-- Form Actions -->
    <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
      <Button 
        type="button"
        @click="$emit('cancel')"
        label="Cancel"
        class="p-button-secondary"
        :disabled="loading"
      />
      <Button 
        type="submit"
        :label="demand ? 'Update' : 'Create'"
        :loading="loading"
        class="p-button-success"
        icon="pi pi-save"
      />
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch, defineProps, defineEmits } from 'vue'

// PrimeVue Components
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

// Props
const props = defineProps({
  demand: {
    type: Object,
    default: null
  },
  services: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['save', 'cancel'])

// Form state
const formData = reactive({
  service_id: null,
  expected_date: null,
  notes: ''
})

const errors = ref({})

// Methods
const handleSubmit = () => {
  // Clear previous errors
  errors.value = {}
  
  // Basic validation
  const validationErrors = {}
  
  if (!formData.service_id) {
    validationErrors.service_id = ['Service is required']
  }
  
  if (Object.keys(validationErrors).length > 0) {
    errors.value = validationErrors
    return
  }

  // Prepare data for submission
  const submitData = {
    service_id: formData.service_id,
    expected_date: formData.expected_date ? formatDateForAPI(formData.expected_date) : null,
    notes: formData.notes || null
  }

  emit('save', submitData)
}

const formatDateForAPI = (date) => {
  if (!date) return null
  
  // If it's already a string in the correct format, return as is
  if (typeof date === 'string') return date
  
  // If it's a Date object, format it
  if (date instanceof Date) {
    return date.toISOString().split('T')[0]
  }
  
  return null
}

const resetForm = () => {
  formData.service_id = null
  formData.expected_date = null
  formData.notes = ''
  errors.value = {}
}

// Watch for demand changes (editing mode)
watch(() => props.demand, (newDemand) => {
  if (newDemand) {
    formData.service_id = newDemand.service_id
    formData.expected_date = newDemand.expected_date ? new Date(newDemand.expected_date) : null
    formData.notes = newDemand.notes || ''
  } else {
    resetForm()
  }
}, { immediate: true })

// Watch for form visibility changes to reset form when dialog closes
watch(() => props.loading, (loading) => {
  if (!loading && !props.demand) {
    // Form finished loading and not in edit mode, reset form
    setTimeout(() => {
      resetForm()
    }, 100)
  }
})
</script>

<style scoped>
/* Form styling */
:deep(.p-dropdown) {
  width: 100%;
}

:deep(.p-calendar) {
  width: 100%;
}

:deep(.p-inputtextarea) {
  width: 100%;
  resize: vertical;
}

:deep(.p-invalid) {
  border-color: #dc2626;
}

.p-error {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}
</style>