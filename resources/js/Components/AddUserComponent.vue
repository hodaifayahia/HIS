<script setup>
import { ref, computed, defineProps, watch, onMounted } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from './toster';
import { useAuthStore } from '../stores/auth';


// PrimeVue Components
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
  showModal: {
    type: Boolean,
    required: true,
  },
  userData: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'userUpdated']);
const toaster = useToastr();
const errors = ref({});
const imagePreview = ref(null);
const userRole = ref(null);
const authStore = useAuthStore();
const existingImage = ref(null);
const fileInput = ref(null);
const specializations = ref([]);
const loadingspecializations = ref(false);

const user = ref({
  id: props.userData?.id || null,
  name: props.userData?.name || '',
  email: props.userData?.email || '',
  phone: props.userData?.phone || '',
  is_active: props.userData?.is_active ?? true,
  avatar: props.userData?.avatar,
  password: '',
  role: props.userData?.role || 'receptionist',
  fichenavatte_max: props.userData?.fichenavatte_max ?? 0,
  salary: props.userData?.salary ?? 0,
  specializations: props.userData?.specialization_ids || [],
  main_specialization_id: props.userData?.main_specialization?.id || null,
});

const isEditMode = computed(() => !!props.userData?.id);
const showPassword = ref(false);
userRole.value = authStore.user?.role;

// Load specializations on component mount
onMounted(async () => {
  await loadspecializations();
});

const loadspecializations = async () => {
  loadingspecializations.value = true;
  try {
    const response = await axios.get('/api/specializations');
      specializations.value = response.data.data
  } catch (error) {
    console.error('Error loading specializations:', error);
    toaster.error('Failed to load specializations');
  } finally {
    loadingspecializations.value = false;
  }
};

watch(
  () => props.userData,
  (newValue) => {
    user.value = {
      id: newValue?.id || null,
      name: newValue?.name || '',
      email: newValue?.email || '',
      phone: newValue?.phone || '',
      is_active: newValue?.is_active || true,
      avatar: null,
      password: '',
      role: newValue?.role || 'receptionist',
      fichenavatte_max: newValue?.fichenavatte_max ?? 0,
      salary: newValue?.salary ?? 0,
      specializations: newValue?.specialization_ids || [],
      main_specialization_id: newValue?.main_specialization?.id || null,
    };
    
    // Handle existing image in edit mode
    if (newValue?.avatar) {
      existingImage.value = newValue.avatar;
      imagePreview.value = null;
    } else {
      existingImage.value = null;
    }
  },
  { immediate: true, deep: true }
);

const userSchema = computed(() =>
  yup.object({
    name: yup.string().required('Name is required'),
    email: yup.string(),
    phone: yup
      .string()
      .matches(/^[0-9]{10,15}$/, 'Phone number must be between 10 and 15 digits')
      .required('Phone number is required'),
    role: yup.string().oneOf(['admin', 'receptionist', 'doctor', 'SuperAdmin','manager' , 'purchaser'], 'Invalid role').required('Role is required'),
    is_active: yup.boolean(),
    fichenavatte_max: yup.number().integer().min(0).nullable(),
    salary: yup.number().min(0).nullable(),
    specializations: yup.array().nullable(),
    main_specialization_id: yup.number().nullable(),
    avatar: yup
      .mixed()
      .nullable()
      .test('fileSize', 'The file is too large', (value) => {
        if (value instanceof File) {
          return value.size <= 2000000; // 2 MB size limit
        }
        return true;
      }),
    password: isEditMode.value
      ? yup.string()
        .transform(value => value === '' ? undefined : value)
        .nullable()
        .min(8, 'Password must be at least 8 characters')
      : yup.string()
        .required('Password is required')
        .min(8, 'Password must be at least 8 characters'),
  })
);

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const closeModal = () => {
  errors.value = {};
  emit('close');
};

// Handle image changes
const handleImageChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    user.value.avatar = file;
    imagePreview.value = URL.createObjectURL(file);
    existingImage.value = null;
  }
};

const handleBackendErrors = (error) => {
  if (error.response?.data?.errors) {
    const fieldErrors = ['email', 'phone'];

    fieldErrors.forEach((field) => {
      if (error.response.data.errors[field]) {
        toaster.error(error.response.data.errors[field][0]);
      }
    });
  } else if (error.response?.data?.message) {
    toaster.error(error.response.data.message);
  } else {
    toaster.error('An unexpected error occurred');
  }
};

const createFormData = (values) => {
  const formData = new FormData();

  if (isEditMode.value) {
    formData.append('_method', 'PUT');
  }

  Object.keys(values).forEach((key) => {
    if (key === 'password' && isEditMode.value && !values[key]) {
      return;
    }

    if (key === 'avatar') {
      if (values[key] instanceof File) {
        formData.append('avatar', values[key]);
      }
    } else if (key === 'specializations') {
      if (Array.isArray(values[key])) {
        values[key].forEach((specializationId, index) => {
          formData.append(`specializations[${index}]`, specializationId);
        });
      }
    } else if (values[key] !== null && values[key] !== undefined && values[key] !== '') {
      formData.append(key, values[key]);
    }
  });

  return formData;
};

const submitForm = async (values) => {
  try {
    const formData = createFormData({
      ...values,
      avatar: user.value.avatar,
      specializations: user.value.specializations,
      main_specialization_id: user.value.main_specialization_id
    });

    const config = {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    };

    if (isEditMode.value) {
      await axios.post(`/api/users/${user.value.id}`, formData, config);
      toaster.success('User updated successfully');
    } else {
      await axios.post('/api/users', formData, config);
      toaster.success('User added successfully');
    }

    emit('userUpdated');
    closeModal();
  } catch (error) {
    handleBackendErrors(error);
  }
};

// Check if specializations should be shown for the selected role
const shouldShowspecializations = computed(() => {
  return ['doctor', 'admin','manager' ,'purchaser'].includes(user.value.role);
});
</script>

<template>
  <div class="modal fade overflow-auto" :class="{ show: showModal }" tabindex="-1" aria-labelledby="userModalLabel" 
       aria-hidden="true" v-if="showModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ isEditMode ? 'Edit User' : 'Add User' }}</h5>
          <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <Form v-slot="{ errors: validationErrors }" @submit="submitForm" :validation-schema="userSchema"
                :initial-values="user">
                
            <!-- Active Status -->
            <div class="mb-3 form-check">
              <Field
                type="checkbox"
                id="is_active"
                name="is_active"
                class="form-check-input"
                v-model="user.is_active"
              />
              <label class="form-check-label" for="is_active">
                Active
              </label>
            </div>
              
            <!-- Name -->
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <Field type="text" id="name" name="name" :class="{ 'is-invalid': validationErrors.name || errors.name }"
                     v-model="user.name" class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.name || (errors.name && errors.name[0]) }}
              </span>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <Field type="text" id="email" name="email"
                     :class="{ 'is-invalid': validationErrors.email || errors.email }" v-model="user.email"
                     class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.email || (errors.email && errors.email[0]) }}
              </span>
            </div>

            <!-- Avatar -->
            <div class="mb-3">
              <label for="avatar" class="form-label">Photo</label>
              <div v-if="imagePreview || existingImage" class="text-center mb-2">
                <img :src="imagePreview || existingImage" 
                     alt="Avatar preview" 
                     class="rounded preview-image mb-2"
                     style="max-width: 150px; max-height: 150px; object-fit: cover;" />
              </div>
              <input
                type="file"
                id="avatar"
                ref="fileInput"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.avatar || errors.avatar }"
                @change="handleImageChange"
                accept="image/*"
              />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.avatar || (errors.avatar && errors.avatar[0]) }}
              </span>
            </div>

            <!-- Phone -->
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <Field type="tel" id="phone" name="phone"
                     :class="{ 'is-invalid': validationErrors.phone || errors.phone }" v-model="user.phone"
                     class="form-control" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.phone || (errors.phone && errors.phone[0]) }}
              </span>
            </div>

            <!-- Role -->
            <div class="mb-3">
              <label for="role" class="form-label">Role</label>
              <Field as="select" id="role" name="role" :class="{ 'is-invalid': validationErrors.role || errors.role }"
                     v-model="user.role" class="form-control">
                <option v-if="userRole === 'SuperAdmin'" value="SuperAdmin">SuperAdmin</option>
                <option value="admin">Admin</option>
                <option value="receptionist">Receptionist</option>
                <option value="doctor">Doctor</option>
                <option value="manager">Manager</option>
                <option value="purchaser">Purchaser</option>
              </Field>
              <span class="text-sm invalid-feedback">
                {{ validationErrors.role || (errors.role && errors.role[0]) }}
              </span>
            </div>

            <!-- Main Specialization (show only for doctors) -->
            <div v-if="user.role === 'doctor'" class="mb-3">
              <label for="main_specialization_id" class="form-label">
                Main Specialization
                <small class="text-muted">(Primary specialization for doctor profile)</small>
              </label>
              <Field as="select" id="main_specialization_id" name="main_specialization_id"
                     :class="{ 'is-invalid': validationErrors.main_specialization_id || errors.main_specialization_id }"
                     v-model="user.main_specialization_id" class="form-control">
                <option value="">Select main specialization</option>
                <option v-for="spec in specializations" :key="spec.id" :value="spec.id">
                  {{ spec.name }} {{ spec.code ? `(${spec.code})` : '' }}
                </option>
              </Field>
              <span class="text-sm invalid-feedback">
                {{ validationErrors.main_specialization_id || (errors.main_specialization_id && errors.main_specialization_id[0]) }}
              </span>
            </div>

            <!-- Additional specializations (show for doctors and admins) -->
            <div v-if="shouldShowspecializations" class="mb-3">
              <label for="specializations" class="form-label">
                Additional specializations
                <small class="text-muted">(Multiple selections allowed)</small>
              </label>
              <MultiSelect
                id="specializations"
                v-model="user.specializations"
                :options="specializations"
                option-label="name"
                append-to="self"
                option-value="id"
                placeholder="Select additional specializations"
                :loading="loadingspecializations"
                :filter="true"
                :show-toggle-all="true"
                :max-selected-labels="3"
                class="w-100"
                :class="{ 'p-invalid': validationErrors.specializations || errors.specializations }"
                display="chip"
              >
                <template #option="slotProps">
                  <div class="d-flex align-items-center">
                    <div 
                      class="specialization-color me-2" 
                      :style="{ backgroundColor: slotProps.option.color }"
                    ></div>
                    <div>
                      <div class="fw-semibold">{{ slotProps.option.name }}</div>
                      <div v-if="slotProps.option.code" class="text-muted small">{{ slotProps.option.code }}</div>
                    </div>
                  </div>
                </template>
                
                <template #chip="slotProps">
                  <div class="p-multiselect-chip d-flex align-items-center">
                    <div 
                      class="specialization-color-small me-1" 
                      :style="{ backgroundColor: slotProps.value.color }"
                    ></div>
                    {{ slotProps.value.name }}
                  </div>
                </template>
              </MultiSelect>
              
              <span class="text-sm invalid-feedback">
                {{ validationErrors.specializations || (errors.specializations && errors.specializations[0]) }}
              </span>
              
              <!-- Selected Additional specializations Display -->
              <div v-if="user.specializations && user.specializations.length > 0" class="mt-2">
                <small class="text-muted">Selected Additional:</small>
                <div class="d-flex flex-wrap gap-1 mt-1">
                  <span 
                    v-for="specializationId in user.specializations" 
                    :key="specializationId"
                    class="badge bg-secondary"
                  >
                    {{ specializations.find(s => s.id === specializationId)?.name }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Max FicheNavatte -->
            <div class="mb-3">
              <label for="fichenavatte_max" class="form-label">Max FicheNavatte</label>
              <Field type="number" id="fichenavatte_max" name="fichenavatte_max"
                     :class="{ 'is-invalid': validationErrors.fichenavatte_max || errors.fichenavatte_max }"
                     v-model="user.fichenavatte_max" class="form-control" min="0" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.fichenavatte_max || (errors.fichenavatte_max && errors.fichenavatte_max[0]) }}
              </span>
            </div>

            <!-- Salary -->
            <div class="mb-3">
              <label for="salary" class="form-label">Salary</label>
              <Field type="number" id="salary" name="salary"
                     :class="{ 'is-invalid': validationErrors.salary || errors.salary }"
                     v-model="user.salary" class="form-control" min="0" step="0.01" />
              <span class="text-sm invalid-feedback">
                {{ validationErrors.salary || (errors.salary && errors.salary[0]) }}
              </span>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label for="password" class="form-label">
                {{ isEditMode ? 'Password (leave blank to keep current)' : 'Password' }}
              </label>
              <div class="input-group">
                <Field :type="showPassword ? 'text' : 'password'" id="password" name="password"
                       :class="{ 'is-invalid': validationErrors.password || errors.password }" v-model="user.password"
                       class="form-control" />
                <button type="button" class="btn btn-outline-secondary" @click="togglePasswordVisibility">
                  <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
              <span class="text-sm invalid-feedback">
                {{ validationErrors.password || (errors.password && errors.password[0]) }}
              </span>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-outline-primary">
                {{ isEditMode ? 'Update User' : 'Add User' }}
              </button>
            </div>
          </Form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal.show {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.input-group {
  display: flex;
  align-items: center;
}

.invalid-feedback {
  display: block;
  color: red;
  font-size: 0.875rem;
}

.preview-image {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 3px;
}

.specialization-color {
  width: 16px;
  height: 16px;
  border-radius: 3px;
  flex-shrink: 0;
}

.specialization-color-small {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  flex-shrink: 0;
}

.w-100 {
  width: 100%;
}

/* PrimeVue overrides for Bootstrap integration */
:deep(.p-multiselect) {
  width: 100%;
}

:deep(.p-multiselect-panel .p-multiselect-items .p-multiselect-item) {
  padding: 0.5rem;
}

:deep(.p-invalid) {
  border-color: #dc3545;
}
</style>
