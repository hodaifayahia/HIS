<script setup>
import { ref, computed, defineProps, watch, onMounted } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from './toster';

const props = defineProps({
  showModal: Boolean,
  specData: {
    type: Object,
    default: () => ({})
  }
});

// Emitting the actual updated/added specialization object
const emit = defineEmits(['close', 'specialization-added', 'specialization-updated']);
const toastr = useToastr();

const specialization = ref({
  id: null,
  name: '',
  description: '',
  photo_url: null, // To display existing photo or new preview
  photo_file: null, // To hold the actual new file for upload
  service_id: null,
  is_active: true
});

const services = ref([]); // Local ref for the list of services
const servicesLoading = ref(false); // Loading state for services dropdown
const servicesError = ref(null);    // Error state for services dropdown

const isEditMode = computed(() => !!specialization.value.id);

// Schema validation
const specializationSchema = yup.object({
  name: yup.string().required('Name is required'),
  description: yup.string().nullable(),
  // Validate photo_file, not photo_url. This field might not exist on initial load.
  photo_file: yup.mixed().nullable().test('fileSize', 'File must be less than 2MB', (value) => {
    if (!value || !(value instanceof File)) return true;
    return value.size <= 2000000;
  }),
  service_id: yup.string().required('Service is required'),
  is_active: yup.boolean(),
});

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    specialization.value.photo_file = file;
    // Create a temporary URL for immediate preview
    specialization.value.photo_url = URL.createObjectURL(file);
  } else {
    specialization.value.photo_file = null;
    // If no new file selected, revert to the original photo_url if in edit mode, otherwise null
    specialization.value.photo_url = isEditMode.value ? props.specData.photo_url : null;
  }
};

const removeCurrentPhoto = () => {
  specialization.value.photo_url = null; // Clear existing photo preview
  specialization.value.photo_file = null; // Clear any newly selected file
  // When this is called in edit mode, the backend should interpret null photo_url/file as a signal to remove the existing photo.
}


watch(() => props.specData, (newVal) => {
  // Deep copy the object to avoid mutating props directly
  specialization.value = {
    id: newVal?.id || null,
    name: newVal?.name || '',
    description: newVal?.description || '',
    photo_url: newVal?.photo_url || null, // For displaying current image
    photo_file: null, // This is always null initially, as no new file is selected
    service_id: newVal?.service_id || null,
    is_active: newVal?.is_active ?? true
  };
}, { immediate: true, deep: true });

// Fetch services when the modal is opened
const fetchServices = async () => {
  servicesLoading.value = true;
  servicesError.value = null;
  try {
    const response = await axios.get('/api/services', { params: { all: true } });
    services.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching services for dropdown:', error);
    servicesError.value = error.response?.data?.message || 'Failed to load services for dropdown.';
    toastr.error(servicesError.value);
  } finally {
    servicesLoading.value = false;
  }
};

watch(() => props.showModal, (newVal) => {
  if (newVal) {
    fetchServices(); // Fetch services every time the modal opens
  }
}, { immediate: true });


const closeModal = () => {
  // Reset form values on close
  specialization.value = {
    id: null,
    name: '',
    description: '',
    photo_url: null,
    photo_file: null,
    service_id: null,
    is_active: true
  };
  emit('close');
};

const createFormData = (values) => {
  const formData = new FormData();

  formData.append('name', values.name || '');
  formData.append('description', values.description || '');
  formData.append('is_active', values.is_active ? 1 : 0); // Convert boolean to 1 or 0
  formData.append('service_id', values.service_id);

  if (specialization.value.photo_file instanceof File) {
    formData.append('photo', specialization.value.photo_file);
  } else if (isEditMode.value && !specialization.value.photo_url && !specialization.value.photo_file) {
    // If in edit mode, and no new file, and no existing URL (meaning it was removed)
    formData.append('remove_photo', 'true'); // Signal to backend to remove current photo
  }
  // Important: For Laravel PUT via POST, append _method
  if (isEditMode.value) {
    formData.append('_method', 'PUT');
  }

  return formData;
};

const submitForm = async (values) => {
  try {
    const formData = createFormData({
      ...values, // values from vee-validate form (name, description, service_id)
      is_active: specialization.value.is_active, // is_active comes directly from v-model
    });

    let response = null;
    if (isEditMode.value) {
      response = await axios.post(`/api/specializations/${specialization.value.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json'
        }
      });
      toastr.success('Specialization updated successfully');
      // Emit the updated object back to the parent
      emit('specialization-updated', response.data.data);
    } else {
      response = await axios.post('/api/specializations', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json'
        }
      });
      toastr.success('Specialization created successfully');
      // Emit the newly added object back to the parent
      emit('specialization-added', response.data.data);
    }

    closeModal();
  } catch (error) {
    if (error.response?.status === 422 && error.response?.data?.errors) {
      // Display validation errors from Laravel
      Object.entries(error.response.data.errors).forEach(([field, messages]) => {
        messages.forEach(message => toastr.error(`${field}: ${message}`)); // Prepend field name
      });
    } else {
      toastr.error(error.response?.data?.message || 'An unexpected error occurred');
    }
    console.error("Form submission error:", error);
  }
};
</script>
<template>
  <div 
    v-if="showModal"
    class="modal fade show" 
    role="dialog"
    aria-modal="true"
    tabindex="-1"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ isEditMode ? 'Edit' : 'Add' }} Specialization
          </h5>
          <button 
            type="button" 
            class="btn btn-danger" 
            @click="closeModal"
            aria-label="Close"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <Form 
          @submit="submitForm" 
          :validation-schema="specializationSchema" 
          v-slot="{ errors }"
        >
          <div class="modal-body">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <Field 
                type="text"
                id="name"
                name="name"
                v-model="specialization.name"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
              />
              <div class="invalid-feedback" v-if="errors.name">
                {{ errors.name }}
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <Field 
                as="textarea"
                id="description"
                name="description"
                v-model="specialization.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
              />
              <div class="invalid-feedback" v-if="errors.description">
                {{ errors.description }}
              </div>
            </div>

            <div class="mb-3">
              <label for="photo" class="form-label">Photo</label>
              <input 
                type="file"
                id="photo"
                name="photo"
                class="form-control"
                :class="{ 'is-invalid': errors.photo }"
                @change="handleFileChange"
                accept="image/*"
              />
              <div class="invalid-feedback" v-if="errors.photo">
                {{ errors.photo }}
              </div>
            </div>

            <div class="mb-3">
              <label for="service_id" class="form-label">Service</label>
              <Field
                as="select"
                id="service_id"
                name="service_id"
                v-model="specialization.service_id"
                class="form-control"
                :class="{ 'is-invalid': errors.service_id }"
              >
                <option value="" disabled>Select a Service</option>
                <option v-for="service in services" :key="service.id" :value="service.id">
                  {{ service.name }}
                </option>
              </Field>
              <div class="invalid-feedback" v-if="errors.service_id">
                {{ errors.service_id }}
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Status</label>
              <div class="d-flex align-items-center">
                <Field
                  name="is_active"
                  type="checkbox"
                  id="is_active_switch"
                  class="switch-input"
                  :value="true"
                  v-slot="{ field }"
                >
                  <input
                    type="checkbox"
                    v-bind="field"
                    :checked="specialization.is_active"
                    @change="field.onChange($event.target.checked); specialization.is_active = $event.target.checked"
                    id="is_active_switch"
                    class="switch-input"
                  />
                </Field>
                <label for="is_active_switch" class="switch-label">
                  <span class="switch-slider"></span>
                </label>
                <div class="invalid-feedback" v-if="errors.is_active">
                  {{ errors.is_active }}
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-outline-secondary" 
              @click="closeModal"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="btn btn-outline-primary"
            >
              {{ isEditMode ? 'Update' : 'Add' }} Specialization
            </button>
          </div>
        </Form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal.show {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.invalid-feedback {
  display: block;
}

/* Switch Styles */
.switch-input {
    display: none; /* Keep this hidden */
}

.switch-label {
    position: relative;
    width: 3rem;
    height: 1.5rem;
    background: #d1d5db;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.switch-slider {
    position: absolute;
    top: 0.125rem;
    left: 0.125rem;
    width: 1.25rem;
    height: 1.25rem;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* These are the crucial CSS rules for the visual switch */
.switch-input:checked + .switch-label {
    background: linear-gradient(135deg, #10b981, #059669);
}

.switch-input:checked + .switch-label .switch-slider {
    transform: translateX(1.5rem);
}
</style>