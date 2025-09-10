<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { RoomStatus } from '../../../../models/Room.js' // Ensure this path is correct

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  room_type_id: {
    type: Number,
    default: null
  },
  service_id: {
    type: Number,
    default: null
  },
  room: {
    type: Object,
    default: null
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'submit'])

// Reactive data
const isSubmitting = ref(false)
const errors = ref({})
const clearErrors = () => {
  errors.value = {}
}
const formData = ref({
  name: '',
  room_number: '',
  location: '',
  number_of_people: 1,
  status: RoomStatus.AVAILABLE,
})

// Methods
const resetForm = () => {
  formData.value = {
    name: '',
    room_number: '',
    location: '',
    number_of_people: 1,
    status: RoomStatus.AVAILABLE,
  }
  clearErrors()
}

// Watch for prop changes
watch(() => props.room, (newRoom) => {
  if (newRoom) {
    formData.value = { ...newRoom }
    // Ensure number_of_people is a number
    if (typeof formData.value.number_of_people !== 'number') {
      formData.value.number_of_people = parseInt(formData.value.number_of_people) || 1;
    }
    formData.value.room_type_id = props.room_type_id
    formData.value.service_id = props.service_id
  } else {
    resetForm()
  }
}, { immediate: true })

watch(() => props.show, (newShow) => {
  if (newShow) {
    clearErrors()
  }
})

const handleClose = () => {
  clearErrors()
  emit('close')
}

const handleSubmit = async () => {
  console.log(props.room_type_id, props.service_id);

  isSubmitting.value = true

  try {
    const dataToSend = { ...formData.value };
    dataToSend.room_type_id = props.room_type_id;
    dataToSend.service_id = props.service_id;

    let response
    if (props.isEdit) {
      // For PUT requests, send as application/json
      response = await axios.put(`/api/rooms/${formData.value.id}`, dataToSend)
    } else {
      // For POST requests, send as application/json
      response = await axios.post('/api/rooms', dataToSend)
    }

    emit('submit', response.data.data)
    handleClose()
  } catch (error) {
    console.error('Error submitting room:', error)

    if (error.response && error.response.status === 422) {
      // Server-side validation errors
      errors.value = error.response.data.errors
    } else {
      errors.value.general = error.response?.data?.message || 'An error occurred while saving the room.'
    }
  } finally {
    isSubmitting.value = false
  }
}

// Computed property for form validity, now relying on validateForm result (for UI enablement)
const isFormValid = computed(() => {
  // This computed property can be used to disable the submit button
  // It's a simpler check, actual detailed validation happens in validateForm
  return formData.value.name.trim() &&
           formData.value.room_number.trim() &&
           formData.value.location.trim() &&
           formData.value.number_of_people > 0 &&
           props.room_type_id !== null && // Using props.room_type_id directly
           props.service_id !== null; // Using props.service_id directly
});
</script>

<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-3">
        <div class="modal-header border-0 pb-0">
          <h2 class="modal-title h4 fw-bold">{{ isEdit ? 'Edit Room' : 'Add New Room' }}</h2>
          <button type="button" class="btn-close rounded-pill bg-danger border-0" @click="handleClose">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <form @submit.prevent="handleSubmit">
          <div class="modal-body px-4">
            <div v-if="errors.general" class="alert alert-danger">
              {{ errors.general }}
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-semibold">Room Name</label>
                <input
                  v-model="formData.name"
                  type="text"
                  class="form-control form-control-lg border-2 rounded-3"
                  :class="{ 'is-invalid': errors.name }"
                  required
                >
                <div v-if="errors.name" class="invalid-feedback">
                  {{ errors.name }}
                </div>
              </div>

              <div class="col-6">
                <label class="form-label fw-semibold">Room Number</label>
                <input
                  v-model="formData.room_number"
                  type="text"
                  class="form-control form-control-lg border-2 rounded-3"
                  :class="{ 'is-invalid': errors.room_number }"
                  required
                >
                <div v-if="errors.room_number" class="invalid-feedback">
                  {{ errors.room_number }}
                </div>
              </div>

              <div class="col-6">
                <label class="form-label fw-semibold">Location</label>
                <input
                  v-model="formData.location"
                  type="text"
                  class="form-control form-control-lg border-2 rounded-3"
                  :class="{ 'is-invalid': errors.location }"
                  required
                >
                <div v-if="errors.location" class="invalid-feedback">
                  {{ errors.location }}
                </div>
              </div>

              <div class="col-6">
                <label class="form-label fw-semibold">Number of People</label>
                <input
                  v-model.number="formData.number_of_people"
                  type="number"
                  min="1"
                  class="form-control form-control-lg border-2 rounded-3"
                  :class="{ 'is-invalid': errors.number_of_people }"
                  required
                >
                <div v-if="errors.number_of_people" class="invalid-feedback">
                  {{ errors.number_of_people }}
                </div>
              </div>

              <div class="col-6">
                <label class="form-label fw-semibold">Status</label>
                <select
                  v-model="formData.status"
                  class="form-select form-select-lg border-2 rounded-3"
                  :class="{ 'is-invalid': errors.status }"
                >
                  <option :value="RoomStatus.AVAILABLE">Available</option>
                  <option :value="RoomStatus.OCCUPIED">Occupied</option>
                  <option :value="RoomStatus.MAINTENANCE">Maintenance</option>
                  <option :value="RoomStatus.RESERVED">Reserved</option>
                </select>
                <div v-if="errors.status" class="invalid-feedback">
                  {{ errors.status }}
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 pt-0">
            <button
              type="button"
              @click="handleClose"
              class="btn btn-secondary btn-lg px-4 rounded-3"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary btn-lg px-4 rounded-3"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ isEdit ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal {
  animation: fadeIn 0.15s ease-out;
}

.modal-dialog {
  animation: slideIn 0.15s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from {
    transform: translateY(-50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>