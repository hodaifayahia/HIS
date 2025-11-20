<template>
  <!-- PrimeVue Dialog Modal with Professional Medical Theme -->
  <Dialog 
    :visible="showModal" 
    @update:visible="closeModal"
    :modal="true" 
    :closable="true" 
    :close-on-escape="true"
    class="tw-rounded-2xl tw-overflow-hidden"
    :style="{ width: '100%', maxWidth: '700px' }"
    :breakpoints="{ '960px': '75vw', '640px': '90vw' }"
  >
    <!-- Enhanced Header with Gradient Background - Matching Admission List Page -->
    <template #header>
      <div class="tw-bg-gradient-to-r tw-from-white tw-via-indigo-50/50 tw-to-purple-50/30 tw-border-b tw-border-slate-200/60 tw-shadow-lg tw-backdrop-blur-sm tw--m-6 tw-mb-0 tw-w-screen tw--ml-6">
        <div class="tw-px-6 tw-py-6 tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
              <i :class="isEditMode ? 'bi bi-pencil-square' : 'bi bi-person-plus-fill'" class="tw-text-white tw-text-xl"></i>
            </div>
            <div>
              <h2 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-1">
                {{ isEditMode ? 'Edit User' : 'Add New User' }}
              </h2>
              <p class="tw-text-slate-600 tw-text-sm">
                {{ isEditMode ? 'Update user account details and permissions' : 'Create a new user account with role and access permissions' }}
              </p>
            </div>
          </div>
          <Button
            icon="pi pi-times"
            @click="closeModal"
            severity="secondary"
            text
            rounded
            class="tw-w-10 tw-h-10 tw-flex tw-items-center tw-justify-center hover:tw-bg-gray-200/40 tw-transition-colors"
            v-tooltip="'Close (ESC)'"
          />
        </div>
      </div>
    </template>

    <!-- Form Content with Professional Styling -->
    <Form v-slot="{ errors: validationErrors }" :key="`user-form-${user.id}`" @submit="submitForm" :validation-schema="userSchema" :initial-values="user" class="tw-bg-gradient-to-br tw-from-white tw-via-slate-50/30 tw-to-blue-50/20 tw-p-6">
      
      <!-- Active Status Badge Section -->
      <div class="tw-mb-6">
        <div class="tw-flex tw-items-center tw-gap-3 tw-p-4 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border tw-border-blue-200 tw-rounded-lg hover:tw-bg-gradient-to-r hover:tw-from-blue-100 hover:tw-to-indigo-100 tw-transition-all">
          <Checkbox 
            v-model="user.is_active" 
            name="is_active" 
            binary
            input-id="is_active"
            class="tw-cursor-pointer"
          />
          <label for="is_active" class="tw-cursor-pointer tw-flex-grow-1">
            <span class="tw-font-semibold tw-text-blue-900">Active User Account</span>
            <span class="tw-text-gray-600 tw-text-sm tw-block tw-mt-1">{{ user.is_active ? '✓ User account is active and can access the system' : '✗ User account is inactive' }}</span>
          </label>
        </div>
      </div>

      <!-- Personal Information Section -->
      <div class="tw-mb-6 tw-pb-6 tw-border-b tw-border-slate-200">
        <h3 class="tw-flex tw-items-center tw-gap-2 tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-4">
          <i class="pi pi-user tw-text-indigo-600"></i>Personal Information
        </h3>
        
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <!-- Name Field -->
          <div class="tw-space-y-2">
            <label for="name" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-user tw-text-indigo-500"></i>Full Name
              <span class="tw-text-red-600">*</span>
            </label>
            <InputGroup>
              <InputGroupAddon>
                <i class="pi pi-user tw-text-indigo-400"></i>
              </InputGroupAddon>
              <Field 
                name="name" 
                v-slot="{ field }"
              >
                <input 
                  v-bind="field"
                  type="text" 
                  id="name" 
                  class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-slate-300 tw-rounded-lg tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-indigo-500 focus:tw-border-transparent"
                  placeholder="Enter full name"
                  :class="{ 'tw-border-red-500 tw-bg-red-50': validationErrors.name }"
                />
              </Field>
            </InputGroup>
            <small v-if="validationErrors.name" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.name }}
            </small>
          </div>

          <!-- Email Field -->
          <div class="tw-space-y-2">
            <label for="email" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-envelope tw-text-green-500"></i>Email Address
              <span class="tw-text-red-600">*</span>
            </label>
            <InputGroup>
              <InputGroupAddon>
                <i class="pi pi-envelope tw-text-green-400"></i>
              </InputGroupAddon>
              <Field 
                name="email" 
                v-slot="{ field }"
              >
                <input 
                  v-bind="field"
                  type="email" 
                  id="email" 
                  class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-slate-300 tw-rounded-lg tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-green-500 focus:tw-border-transparent"
                  placeholder="user@hospital.com"
                  :class="{ 'tw-border-red-500 tw-bg-red-50': validationErrors.email }"
                />
              </Field>
            </InputGroup>
            <small v-if="validationErrors.email" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.email }}
            </small>
          </div>

          <!-- Phone Field -->
          <div class="tw-space-y-2">
            <label for="phone" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-phone tw-text-purple-500"></i>Phone Number
              <span class="tw-text-red-600">*</span>
            </label>
            <InputGroup>
              <InputGroupAddon>
                <i class="pi pi-phone tw-text-purple-400"></i>
              </InputGroupAddon>
              <Field 
                name="phone" 
                v-slot="{ field }"
              >
                <input 
                  v-bind="field"
                  type="tel" 
                  id="phone" 
                  class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-slate-300 tw-rounded-lg tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-purple-500 focus:tw-border-transparent"
                  placeholder="+213 (555) 000-0000"
                  :class="{ 'tw-border-red-500 tw-bg-red-50': validationErrors.phone }"
                />
              </Field>
            </InputGroup>
            <small v-if="validationErrors.phone" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.phone }}
            </small>
          </div>

          <!-- Avatar Upload -->
          <div class="tw-space-y-2">
            <label for="avatar" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-image tw-text-pink-500"></i>Profile Photo
            </label>
            <div v-if="imagePreview || user.avatar" class="tw-flex tw-justify-center tw-mb-2">
              <img 
                :src="imagePreview || user.avatar" 
                alt="Avatar preview" 
                class="tw-rounded-full tw-w-16 tw-h-16 tw-object-cover tw-border-4 tw-border-blue-200 tw-shadow-md"
              />
            </div>
            <div class="tw-upload-zone  tw-border-2 tw-border-dashed tw-border-blue-300 tw-rounded-lg tw-bg-blue-50 tw-text-center tw-cursor-pointer hover:tw-bg-blue-100 tw-transition-all">
              <input
                type="file"
                id="avatar"
                ref="fileInput"
                class="tw-hidden"
                @change="handleImageChange"
                accept="image/*"
              />
              <label for="avatar" class="tw-cursor-pointer tw-block">
                <i class="pi pi-cloud-upload tw-text-blue-600 tw-text-xl tw-mb-1 tw-block"></i>
                <span class="tw-text-gray-700 tw-font-medium tw-text-xs">Click to upload</span>
              </label>
            </div>
            <small v-if="validationErrors.avatar" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.avatar }}
            </small>
          </div>
        </div>
      </div>

      <!-- Role & Access Section -->
      <div class="tw-mb-6 tw-pb-6 tw-border-b tw-border-slate-200">
        <h3 class="tw-flex tw-items-center tw-gap-2 tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-4">
          <i class="pi pi-shield tw-text-amber-600"></i>Role & Access
        </h3>
        
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <!-- Role Selection -->
          <div class="tw-space-y-2">
            <label for="role" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-shield tw-text-amber-500"></i>User Role
              <span class="tw-text-red-600">*</span>
            </label>
            <Field
              name="role"
              v-slot="{ field }"
            >
              <Dropdown
                :model-value="field.value"
                @update:model-value="field.value = $event"
                id="role"
                :options="getRoleOptions()"
                option-label="label"
                option-value="value"
                class="tw-w-full"
                :class="{ 'tw-border-red-500': validationErrors.role, 'dropdown-sm': true }"
                placeholder="Select role"
              />
            </Field>
            <small v-if="validationErrors.role" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.role }}
            </small>
          </div>

          <!-- Max Fiche Navatte -->
          <div class="tw-space-y-2  tw-pt-1">
            <label for="fichenavatte_max" class="tw-flex tw-items-center tw-gap-2 tw tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-bookmark tw-text-orange-500 "></i>Max Fiche Navatte
            </label>
            <InputGroup>
              <InputGroupAddon>
                <i class="pi pi-bookmark tw-text-orange-400"></i>
              </InputGroupAddon>
              <Field 
                name="fichenavatte_max" 
                v-slot="{ field }"
              >
                <input 
                  v-bind="field"
                  type="number" 
                  id="fichenavatte_max" 
                  class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-slate-300 tw-rounded-lg tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-orange-500 focus:tw-border-transparent"
                  placeholder="0"
                  min="0"
                  :class="{ 'tw-border-red-500 tw-bg-red-50': validationErrors.fichenavatte_max }"
                />
              </Field>
            </InputGroup>
            <small v-if="validationErrors.fichenavatte_max" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.fichenavatte_max }}
            </small>
          </div>
        </div>

        <!-- Main Specialization (for doctors) -->
        <Transition name="fade">
          <div v-if="user.role === 'doctor'" class="tw-mt-4 tw-space-y-2">
            <label for="main_specialization_id" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-star-fill tw-text-blue-500"></i>Main Specialization
            </label>
            <Field
              name="main_specialization_id"
              v-slot="{ field }"
            >
              <Dropdown
                :model-value="field.value"
                @update:model-value="field.value = $event"
                id="main_specialization_id"
                :options="specializations"
                option-label="name"
                option-value="id"
                class="tw-w-full tw-p-inputfield-sm"
                placeholder="Select main specialization"
                :class="{ 'tw-border-red-500': validationErrors.main_specialization_id }"
              />
            </Field>
            <small class="tw-text-gray-500 tw-text-xs">Primary specialization for doctor profile</small>
            <small v-if="validationErrors.main_specialization_id" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.main_specialization_id }}
            </small>
          </div>
        </Transition>

        <!-- Additional Specializations (for doctors/admins) -->
        <Transition name="fade">
          <div v-if="shouldShowspecializations" class="tw-mt-4 tw-space-y-2">
            <label for="specializations" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-list tw-text-indigo-500"></i>Additional Specializations
            </label>
            <Field
              name="specializations"
              v-slot="{ field }"
            >
              <MultiSelect
                :model-value="field.value"
                @update:model-value="field.value = $event"
                id="specializations"
                :options="specializations"
                option-label="name"
                option-value="id"
                placeholder="Select additional specializations"
                :loading="loadingspecializations"
                :filter="true"
                :show-toggle-all="true"
                :max-selected-labels="3"
                class="tw-w-full tw-p-inputfield-sm"
                :class="{ 'tw-border-red-500': validationErrors.specializations }"
                display="chip"
              />
            </Field>
            <small class="tw-text-gray-500 tw-text-xs">You can select multiple specializations</small>
            <small v-if="validationErrors.specializations" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-exclamation-circle"></i>{{ validationErrors.specializations }}
            </small>
          </div>
        </Transition>
      </div>

      <!-- Compensation Section -->
      <div class="tw-mb-6">
        <h3 class="tw-flex tw-items-center tw-gap-2 tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-4">
          <i class="pi pi-dollar tw-text-green-600"></i>Compensation
        </h3>
        
        <!-- Salary -->
        <div class="tw-space-y-2">
          <label for="salary" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
            <i class="pi pi-dollar tw-text-green-500"></i>Monthly Salary
          </label>
          <InputGroup>
            <InputGroupAddon>
              <i class="pi pi-dollar tw-text-green-400"></i>
            </InputGroupAddon>
            <Field 
              name="salary" 
              v-slot="{ field }"
            >
              <input 
                v-bind="field"
                type="number" 
                id="salary" 
                class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-slate-300 tw-rounded-lg tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-green-500 focus:tw-border-transparent"
                placeholder="0.00"
                min="0"
                step="0.01"
                :class="{ 'tw-border-red-500 tw-bg-red-50': validationErrors.salary }"
              />
            </Field>
          </InputGroup>
          <small v-if="validationErrors.salary" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
            <i class="pi pi-exclamation-circle"></i>{{ validationErrors.salary }}
          </small>
        </div>
      </div>

      <!-- Security Section -->
      <div class="tw-mb-6">
        <h3 class="tw-flex tw-items-center tw-gap-2 tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-4">
          <i class="pi pi-lock tw-text-red-600"></i>Security
        </h3>

        <!-- Password Field -->
        <div class="tw-space-y-2">
          <label for="password" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
            <i class="pi pi-lock tw-text-red-500"></i>Password
            <span class="tw-text-red-600">*</span>
          </label>
          <InputGroup>
            <InputGroupAddon>
              <i class="pi pi-lock tw-text-red-400"></i>
            </InputGroupAddon>
            <Field 
              name="password"
              v-slot="{ field }"
            >
              <input 
                v-bind="field"
                :type="showPassword ? 'text' : 'password'"
                id="password"
                class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-slate-300 tw-rounded-lg tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-red-500 focus:tw-border-transparent"
                :placeholder="isEditMode ? 'Leave blank to keep current password' : 'Enter password (min 8 characters)'"
                :class="{ 'tw-border-red-500 tw-bg-red-50': validationErrors.password }"
              />
            </Field>
            <Button
              type="button"
              :icon="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"
              severity="secondary"
              text
              @click="togglePasswordVisibility"
              class="tw-p-button-sm"
            />
          </InputGroup>
          <small class="tw-text-gray-500 tw-text-xs tw-flex tw-items-center tw-gap-1">
            <i class="pi pi-info-circle"></i>
            {{ isEditMode ? 'Leave blank to keep current password' : 'Minimum 8 characters required' }}
          </small>
          <small v-if="validationErrors.password" class="tw-text-red-600 tw-text-xs tw-flex tw-items-center tw-gap-1">
            <i class="pi pi-exclamation-circle"></i>{{ validationErrors.password }}
          </small>
        </div>
      </div>
    </Form>

    <!-- Dialog Footer with Professional Styling -->
    <template #footer>
      <div class="tw-bg-gradient-to-r tw-from-slate-50 tw-via-indigo-50/30 tw-to-purple-50/20 tw-border-t tw-border-slate-200/60 tw-px-6 tw-py-4">
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            severity="secondary"
            @click="closeModal"
            class="tw-px-6 tw-py-2.5 tw-bg-white tw-border tw-border-slate-300 tw-text-slate-700 hover:tw-bg-slate-50 tw-rounded-lg tw-font-medium tw-transition-colors"
            outlined
          />
          <Button
            :label="isEditMode ? 'Update User' : 'Add User'"
            :icon="isEditMode ? 'bi bi-check-circle-fill' : 'bi bi-person-plus-fill'"
            severity="success"
            @click="submitForm"
            :loading="isSubmitting"
            class="tw-px-6 tw-py-2.5 tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 hover:tw-from-indigo-600 hover:tw-to-purple-700 tw-text-white tw-rounded-lg tw-font-medium tw-transition-all tw-shadow-lg hover:tw-shadow-xl"
          />
        </div>
      </div>
    </template>
  </Dialog>

  <!-- Toast Notifications -->
  <Toast position="top-right" />
</template>

<script setup>
import { ref, computed, defineProps, watch, onMounted } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from './toster';
import { useAuthStore } from '../stores/auth';

// PrimeVue Components
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';
import Toast from 'primevue/toast';

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
const showPassword = ref(false);
const isSubmitting = ref(false);

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

const shouldShowspecializations = computed(() => {
  return ['doctor', 'admin', 'manager', 'purchaser'].includes(user.value.role);
});

userRole.value = authStore.user?.role;

// Load specializations on component mount
onMounted(async () => {
  await loadspecializations();
});

const loadspecializations = async () => {
  loadingspecializations.value = true;
  try {
    const response = await axios.get('/api/specializations');
    specializations.value = response.data.data;
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
      is_active: newValue?.is_active ?? true,
      avatar: newValue?.avatar,
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
    email: yup
      .string()
      .email('Invalid email format')
      .required('Email is required'),
    phone: yup
      .string()
      .matches(/^[0-9]{10,15}$/, 'Phone number must be between 10 and 15 digits')
      .required('Phone number is required'),
    role: yup
      .string()
      .oneOf(['admin', 'receptionist', 'doctor', 'SuperAdmin', 'manager', 'purchaser'], 'Invalid role')
      .required('Role is required'),
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
      ? yup
          .string()
          .transform((value) => (value === '' ? undefined : value))
          .nullable()
          .min(8, 'Password must be at least 8 characters')
      : yup
          .string()
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
  isSubmitting.value = true;
  try {
    const formData = createFormData(values);
    const url = isEditMode.value ? `/api/users/${user.value.id}` : '/api/users';
    const method = isEditMode.value ? 'post' : 'post';

    const response = await axios({
      method,
      url,
      data: formData,
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    toaster.success(isEditMode.value ? 'User updated successfully' : 'User created successfully');
    emit('userUpdated', response.data.data);
    closeModal();
  } catch (error) {
    handleBackendErrors(error);
  } finally {
    isSubmitting.value = false;
  }
};

const getRoleOptions = () => {
  const baseRoles = [
    { label: 'Receptionist', value: 'receptionist' },
    { label: 'Doctor', value: 'doctor' },
    { label: 'Admin', value: 'admin' },
    { label: 'Manager', value: 'manager' },
    { label: 'Purchaser', value: 'purchaser' },
  ];

  if (userRole.value === 'SuperAdmin') {
    baseRoles.push({ label: 'Super Admin', value: 'SuperAdmin' });
  }

  return baseRoles;
};
</script>

<style scoped>
/* Dialog Customizations */
:deep(.p-dialog) {
  border-radius: 1rem;
  overflow: hidden;
}

:deep(.p-dialog-header) {
  padding: 0;
  border: none;
  background: transparent;
}

:deep(.p-dialog-content) {
  padding: 0;
  background: transparent;
}

:deep(.p-dialog-footer) {
  padding: 0;
  border: none;
  background: transparent;
}

:deep(.p-dialog-mask .p-dialog) {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Input Group Styling */
:deep(.p-inputgroup) {
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

:deep(.p-inputgroup input) {
  border: none;
  padding: 0.5rem 0.75rem;
  font-size: 0.95rem;
}

:deep(.p-inputgroup input:focus) {
  outline: none;
  box-shadow: inset 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-inputgroupaddon) {
  background-color: #f8fafc;
  border: none;
  padding: 0.5rem 0.75rem;
  color: #64748b;
}

/* Dropdown Styling */
:deep(.p-dropdown) {
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: white;
}

:deep(.p-dropdown.dropdown-sm) {
  font-size: 0.875rem;
  padding: 0.375rem 0.5rem;
  height: 36px;
}

:deep(.p-dropdown.dropdown-sm .p-dropdown-label) {
  padding: 0;
}

:deep(.p-dropdown:focus) {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

:deep(.p-dropdown:hover) {
  border-color: #cbd5e1;
}

:deep(.p-dropdown-panel) {
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.p-dropdown-item) {
  padding: 0.75rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

:deep(.p-dropdown-item:hover) {
  background-color: #f0f9ff;
  color: #0369a1;
}

:deep(.p-dropdown-item.p-highlight) {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: white;
}

/* Checkbox Styling */
:deep(.p-checkbox .p-checkbox-box) {
  border: 2px solid #cbd5e1;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

:deep(.p-checkbox .p-checkbox-box:hover) {
  border-color: #4f46e5;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border-color: #4f46e5;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight:after) {
  color: white;
}

/* MultiSelect Styling */
:deep(.p-multiselect) {
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.5rem;
  background: white;
}

:deep(.p-multiselect.p-inputfield-sm) {
  font-size: 0.875rem;
  padding: 0.375rem 0.5rem;
}

:deep(.p-multiselect:focus) {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

:deep(.p-multiselect-panel) {
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

:deep(.p-multiselect-label-container) {
  padding: 0.25rem 0;
}

:deep(.p-multiselect-token) {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: white;
  border-radius: 0.375rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

/* Button Styling */
:deep(.p-button) {
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

:deep(.p-button-success) {
  background: linear-gradient(135deg, #10b981, #059669);
  border: none;
}

:deep(.p-button-success:hover) {
  background: linear-gradient(135deg, #059669, #047857);
}

:deep(.p-button-secondary) {
  background: white;
  color: #4b5563;
  border: 1px solid #cbd5e1;
}

:deep(.p-button-secondary:hover) {
  background: #f8fafc;
  border-color: #94a3b8;
}

/* Form Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Upload Zone */
.tw-upload-zone {
  position: relative;
  transition: all 0.3s ease;
}

.tw-upload-zone:hover {
  border-color: #4f46e5;
  background-color: #eff6ff;
}

.tw-upload-zone input.tw-hidden {
  display: none;
}

/* Responsive */
@media (max-width: 768px) {
  :deep(.p-dialog) {
    max-width: 95vw !important;
  }

  .tw-grid-cols-2 {
    grid-template-columns: 1fr;
  }
}

/* Placeholder styling */
:deep(input::placeholder) {
  color: #cbd5e1;
}

:deep(textarea::placeholder) {
  color: #cbd5e1;
}

/* Focus visible for accessibility */
:deep(.p-button:focus-visible) {
  outline: 2px solid #4f46e5;
  outline-offset: 2px;
}

:deep(.p-dropdown:focus-visible) {
  outline: 2px solid #4f46e5;
  outline-offset: 2px;
}
</style>


