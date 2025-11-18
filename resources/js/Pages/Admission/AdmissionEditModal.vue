<template>
  <Dialog v-model:visible="showDialog" :modal="true" :header="`Edit Admission`" :style="{ width: '100%', maxWidth: '700px' }" class="tw-rounded-xl">
    <template #header>
      <div class="tw-flex tw-items-center tw-gap-3 tw-w-full">
        <i class="bi bi-pencil-square tw-text-xl tw-text-amber-600"></i>
        <span class="tw-text-lg tw-font-bold tw-text-gray-800">Edit Admission</span>
      </div>
    </template>

    <form @submit.prevent="submitForm" class="tw-space-y-5" v-if="admission">
      <!-- Patient Info (Read-only) -->
      <div>
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="bi bi-person-badge tw-text-blue-500"></i> Patient
        </label>
        <div class="tw-p-3 tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg">
          <strong class="tw-text-gray-900">{{ admission.patient?.name }}</strong>
          <br>
          <small class="tw-text-gray-600">{{ admission.patient?.phone }}</small>
        </div>
      </div>

      <!-- Admission Type (Read-only) -->
      <div>
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="bi bi-building tw-text-amber-500"></i> Admission Type
        </label>
        <div class="tw-p-3 tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg">
          <Tag :value="admission.type_label" :severity="admission.type === 'surgery' ? 'warning' : 'success'" />
        </div>
      </div>

      <!-- Status -->
      <div>
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="bi bi-flag tw-text-purple-500"></i> Status
          <span class="tw-text-red-600">*</span>
        </label>
        <Dropdown
          v-model="form.status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          placeholder="Select Status"
          class="tw-w-full"
        />
        <small v-if="errors.status" class="tw-text-red-600 tw-block tw-mt-1">
          <i class="bi bi-exclamation-circle"></i> {{ errors.status[0] }}
        </small>
      </div>

      <!-- Documents Verified -->
      <div>
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="bi bi-file-check tw-text-green-500"></i> Documents Verified
        </label>
        <div class="tw-flex tw-items-center">
          <Checkbox v-model="form.documents_verified" :binary="true" />
          <label class="tw-ml-2 tw-text-sm tw-text-gray-700">Documents have been verified</label>
        </div>
      </div>

      <!-- Notes -->
      <div>
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="bi bi-sticky tw-text-blue-500"></i> Notes
        </label>
        <Textarea
          v-model="form.notes"
          rows="3"
          placeholder="Additional notes..."
          class="tw-w-full"
        />
      </div>
    </form>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-3">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="p-button-text p-button-secondary"
          @click="closeModal"
        />
        <Button
          label="Update Admission"
          icon="pi pi-check"
          class="p-button-primary"
          :loading="loading"
          @click="submitForm"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import axios from 'axios'

const props = defineProps({
  admissionId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['updated'])

const toast = useToast()

const showDialog = ref(false)
const loading = ref(false)
const admission = ref(null)

const form = reactive({
  status: '',
  documents_verified: false,
  notes: ''
})

const errors = ref({})

const statusOptions = [
  { label: 'Admitted', value: 'admitted' },
  { label: 'In Service', value: 'in_service' },
  { label: 'Document Pending', value: 'document_pending' },
  { label: 'Ready for Discharge', value: 'ready_for_discharge' }
]

const openModal = async (id) => {
  admission.value = null
  errors.value = {}
  showDialog.value = true
  loading.value = true

  try {
    const response = await axios.get(`/api/admissions/${id}`)
    admission.value = response.data.data

    // Populate form
    form.status = admission.value.status
    form.documents_verified = admission.value.documents_verified || false
    form.notes = admission.value.notes || ''
  } catch (error) {
    console.error('Error loading admission:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load admission details',
      life: 3000
    })
    closeModal()
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  showDialog.value = false
  admission.value = null
}

const submitForm = async () => {
  if (!admission.value) return

  loading.value = true
  errors.value = {}

  try {
    const response = await axios.put(`/api/admissions/${admission.value.id}`, form)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Admission updated successfully',
      life: 3000
    })

    emit('updated')
    closeModal()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update admission',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

// Expose methods
defineExpose({
  openModal
})
</script>

<style scoped>
.tw-rounded-xl {
  border-radius: 0.75rem;
}
</style>