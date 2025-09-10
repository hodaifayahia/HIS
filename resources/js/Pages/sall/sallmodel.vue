<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { useToastr } from './toster'

const props = defineProps({
  showModal: {
    type: Boolean,
    default: false
  },
  sallData: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'sallUpdate'])

const toaster = useToastr()
const loading = ref(false)
const errors = ref({})

const form = ref({
  name: '',
  number: ''
})

const isEditing = computed(() => {
  return props.sallData && props.sallData.id
})

const modalTitle = computed(() => {
  return isEditing.value ? 'Edit Sall' : 'Add New Sall'
})

// Watch for prop changes to populate form
watch(() => props.sallData, (newData) => {
  if (newData && Object.keys(newData).length > 0) {
    form.value = {
      name: newData.name || '',
      number: newData.number || ''
    }
  } else {
    resetForm()
  }
}, { immediate: true, deep: true })

const resetForm = () => {
  form.value = {
    name: '',
    number: ''
  }
  errors.value = {}
}

const closeModal = () => {
  resetForm()
  emit('close')
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.name.trim()) {
    errors.value.name = 'Name is required'
  }
  
  if (!form.value.number.trim()) {
    errors.value.number = 'Number is required'
  }
  
  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) {
    toaster.error('Please fix the form errors')
    return
  }

  try {
    loading.value = true
    
    const formData = {
      name: form.value.name.trim(),
      number: form.value.number.trim()
    }

    let response
    if (isEditing.value) {
      response = await axios.put(`/api/salls/${props.sallData.id}`, formData)
      toaster.success('Sall updated successfully')
    } else {
      response = await axios.post('/api/salls', formData)
      toaster.success('Sall created successfully')
    }

    emit('sallUpdate')
    closeModal()
    
  } catch (error) {
    console.error('Error saving sall:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else if (error.response?.data?.message) {
      toaster.error(error.response.data.message)
    } else {
      toaster.error('An error occurred while saving the sall')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ modalTitle }}</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        
        <form @submit.prevent="submitForm">
          <div class="modal-body">
            <!-- Name Field -->
            <div class="mb-3">
              <label for="sallName" class="form-label">Name <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control" 
                :class="{ 'is-invalid': errors.name }"
                id="sallName"
                v-model="form.name"
                placeholder="Enter sall name"
              >
              <div v-if="errors.name" class="invalid-feedback">
                {{ errors.name }}
              </div>
            </div>

            <!-- Number Field -->
            <div class="mb-3">
              <label for="sallNumber" class="form-label">Number <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control" 
                :class="{ 'is-invalid': errors.number }"
                id="sallNumber"
                v-model="form.number"
                placeholder="Enter sall number"
              >
              <div v-if="errors.number" class="invalid-feedback">
                {{ errors.number }}
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal" :disabled="loading">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              {{ isEditing ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>