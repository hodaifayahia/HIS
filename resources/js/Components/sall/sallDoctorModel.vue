<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import { useToastr } from './toster'

const props = defineProps({
  showModal: {
    type: Boolean,
    default: false
  },
  assignmentData: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'assignmentUpdate'])

const toaster = useToastr()
const loading = ref(false)
const errors = ref({})
const doctors = ref([])
const salls = ref([])

const form = ref({
  doctor_id: '',
  sall_id: ''
})

const isEditing = computed(() => {
  return props.assignmentData && props.assignmentData.id
})

const modalTitle = computed(() => {
  return isEditing.value ? 'Edit Doctor-Sall Assignment' : 'Add New Doctor-Sall Assignment'
})

// Watch for prop changes to populate form
watch(() => props.assignmentData, (newData) => {
  if (newData && Object.keys(newData).length > 0) {
    form.value = {
      doctor_id: newData.doctor_id || '',
      sall_id: newData.sall_id || ''
    }
  } else {
    resetForm()
  }
}, { immediate: true, deep: true })

// Fetch doctors and salls when modal opens
watch(() => props.showModal, async (isOpen) => {
  if (isOpen) {
    await Promise.all([
      fetchDoctors(),
      fetchSalls()
    ])
  }
})

const fetchDoctors = async () => {
  try {
    const response = await axios.get('/api/doctors')
    doctors.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching doctors:', error)
    toaster.error('Failed to load doctors')
  }
}

const fetchSalls = async () => {
  try {
    const response = await axios.get('/api/salls')
    salls.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching salls:', error)
    toaster.error('Failed to load salls')
  }
}

const resetForm = () => {
  form.value = {
    doctor_id: '',
    sall_id: ''
  }
  errors.value = {}
}

const closeModal = () => {
  resetForm()
  emit('close')
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.doctor_id) {
    errors.value.doctor_id = 'Doctor is required'
  }
  
  if (!form.value.sall_id) {
    errors.value.sall_id = 'Sall is required'
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
      doctor_id: form.value.doctor_id,
      sall_id: form.value.sall_id
    }

    let response
    if (isEditing.value) {
      response = await axios.put(`/api/doctor-salls/${props.assignmentData.id}`, formData)
      toaster.success('Doctor-Sall assignment updated successfully')
    } else {
      response = await axios.post('/api/doctor-salls', formData)
      toaster.success('Doctor-Sall assignment created successfully')
    }

    emit('assignmentUpdate')
    closeModal()
    
  } catch (error) {
    console.error('Error saving doctor-sall assignment:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else if (error.response?.data?.message) {
      toaster.error(error.response.data.message)
    } else {
      toaster.error('An error occurred while saving the assignment')
    }
  } finally {
    loading.value = false
  }
}

const getSelectedDoctorName = computed(() => {
  const doctor = doctors.value.find(d => d.id == form.value.doctor_id)
  return doctor ? doctor.name : ''
})

const getSelectedSallName = computed(() => {
  const sall = salls.value.find(s => s.id == form.value.sall_id)
  return sall ? sall.name : ''
})
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
            <!-- Doctor Selection -->
            <div class="mb-3">
              <label for="doctorSelect" class="form-label">Doctor <span class="text-danger">*</span></label>
              <select 
                class="form-select" 
                :class="{ 'is-invalid': errors.doctor_id }"
                id="doctorSelect"
                v-model="form.doctor_id"
              >
                <option value="">Select a doctor...</option>
                <option 
                  v-for="doctor in doctors" 
                  :key="doctor.id" 
                  :value="doctor.id"
                >
                  {{ doctor.name }} - {{ doctor.specialization || 'No specialization' }}
                </option>
              </select>
              <div v-if="errors.doctor_id" class="invalid-feedback">
                {{ errors.doctor_id }}
              </div>
            </div>

            <!-- Sall Selection -->
            <div class="mb-3">
              <label for="sallSelect" class="form-label">Sall <span class="text-danger">*</span></label>
              <select 
                class="form-select" 
                :class="{ 'is-invalid': errors.sall_id }"
                id="sallSelect"
                v-model="form.sall_id"
              >
                <option value="">Select a sall...</option>
                <option 
                  v-for="sall in salls" 
                  :key="sall.id" 
                  :value="sall.id"
                >
                  {{ sall.name }} - {{ sall.number }}
                </option>
              </select>
              <div v-if="errors.sall_id" class="invalid-feedback">
                {{ errors.sall_id }}
              </div>
            </div>

            <!-- Assignment Preview -->
            <div v-if="form.doctor_id && form.sall_id" class="alert alert-info">
              <h6 class="alert-heading">Assignment Preview:</h6>
              <p class="mb-0">
                <strong>Doctor:</strong> {{ getSelectedDoctorName }}<br>
                <strong>Sall:</strong> {{ getSelectedSallName }}
              </p>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal" :disabled="loading">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              {{ isEditing ? 'Update Assignment' : 'Create Assignment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.alert-heading {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}
</style>