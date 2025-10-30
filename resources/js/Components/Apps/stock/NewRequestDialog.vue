<template>
  <Dialog :visible="visible" @update:visible="$emit('update:visible', $event)" modal header="Create New Stock Request" :style="{width: '60rem'}">
    <div class="tw-space-y-6">
      <div class="tw-grid tw-grid-cols-2 tw-gap-6">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Your Service (Requesting From) *</label>
          <Dropdown
            v-model="formData.requesting_service_id"
            :options="userServices"
            optionLabel="name"
            optionValue="id"
            placeholder="Select your service"
            class="tw-w-full"
            :loading="loadingServices"
            :class="{ 'p-invalid': validationErrors.requesting_service_id }"
            :disabled="userServices.length === 1"
          />
          <small v-if="validationErrors.requesting_service_id" class="p-error">
            {{ validationErrors.requesting_service_id }}
          </small>
          <small class="tw-text-gray-500 tw-text-xs tw-mt-1 tw-block">
            {{ userServices.length === 1 ? 'Your assigned service' : 'Select which service you are requesting from' }}
          </small>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Service to Request From *</label>
          <Dropdown
            v-model="formData.providing_service_id"
            :options="services"
            optionLabel="name"
            optionValue="id"
            placeholder="Choose service to request from"
            class="tw-w-full"
            :loading="loadingServices"
            :class="{ 'p-invalid': validationErrors.providing_service_id }"
          />
          <small v-if="validationErrors.providing_service_id" class="p-error">
            {{ validationErrors.providing_service_id }}
          </small>
          <small class="tw-text-gray-500 tw-text-xs tw-mt-1 tw-block">
            Service that will provide the products
          </small>
        </div>
      </div>

      <div class="tw-grid tw-grid-cols-2 tw-gap-6">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Expected Delivery Date</label>
          <Calendar
            v-model="formData.expected_delivery_date"
            dateFormat="yy-mm-dd"
            class="tw-w-full"
            :minDate="minDate"
            placeholder="Optional"
          />
        </div>
      </div>

      <div>
        <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Request Reason *</label>
        <Textarea
          v-model="formData.request_reason"
          rows="3"
          class="tw-w-full"
          placeholder="Please explain why you need these products..."
          :class="{ 'p-invalid': validationErrors.request_reason }"
        />
        <small v-if="validationErrors.request_reason" class="p-error">
          {{ validationErrors.request_reason }}
        </small>
      </div>

      <div class="tw-flex tw-justify-end tw-space-x-3">
        <Button
          type="button"
          label="Cancel"
          icon="pi pi-times"
          class="p-button-text"
          @click="handleClose"
        />
        <Button
          type="button"
          label="Create Draft"
          icon="pi pi-save"
          class="tw-bg-blue-600"
          :loading="creatingDraft"
          @click="handleCreateDraft"
          :disabled="!isFormValid"
        />
      </div>
    </div>
  </Dialog>
</template>

<script>
import { computed, reactive, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

export default {
  name: 'NewRequestDialog',
  components: {
    Dialog,
    Dropdown,
    Calendar,
    Textarea,
    Button
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    services: {
      type: Array,
      default: () => []
    },
    userServices: {
      type: Array,
      default: () => []
    },
    loadingServices: {
      type: Boolean,
      default: false
    },
    creatingDraft: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:visible', 'create-draft', 'close'],
  setup(props, { emit }) {
    const formData = reactive({
      requesting_service_id: null,
      providing_service_id: null,
      request_reason: '',
      expected_delivery_date: null
    });

    const validationErrors = reactive({
      requesting_service_id: '',
      providing_service_id: '',
      request_reason: '',
      expected_delivery_date: ''
    });

    // Auto-select requesting service if user has only one
    watch(() => props.userServices, (newServices) => {
      if (newServices.length === 1 && !formData.requesting_service_id) {
        formData.requesting_service_id = newServices[0].id;
      }
    }, { immediate: true });

    const minDate = computed(() => new Date());
    
    const isFormValid = computed(() => {
      return formData.requesting_service_id && formData.providing_service_id && formData.request_reason.trim();
    });

    const validateForm = () => {
      // Clear previous errors
      Object.keys(validationErrors).forEach(key => {
        validationErrors[key] = '';
      });

      let isValid = true;

      // Validate requesting_service_id
      if (!formData.requesting_service_id) {
        validationErrors.requesting_service_id = 'Please select your service';
        isValid = false;
      }

      // Validate providing_service_id
      if (!formData.providing_service_id) {
        validationErrors.providing_service_id = 'Please select a service to request from';
        isValid = false;
      }

      // Validate that requesting and providing services are different
      if (formData.requesting_service_id && formData.providing_service_id && 
          formData.requesting_service_id === formData.providing_service_id) {
        validationErrors.providing_service_id = 'Cannot request from your own service';
        isValid = false;
      }

      // Validate request_reason
      if (!formData.request_reason || !formData.request_reason.trim()) {
        validationErrors.request_reason = 'Please provide a reason for this request';
        isValid = false;
      } else if (formData.request_reason.trim().length < 10) {
        validationErrors.request_reason = 'Request reason must be at least 10 characters';
        isValid = false;
      }

      return isValid;
    };

    const handleCreateDraft = () => {
      
      if (validateForm() && isFormValid.value) {
        emit('create-draft', { ...formData });
      }
    };

    const handleClose = () => {
      emit('close');
      resetForm();
    };

    const resetForm = () => {
      Object.assign(formData, {
        requesting_service_id: props.userServices.length === 1 ? props.userServices[0].id : null,
        providing_service_id: null,
        request_reason: '',
        expected_delivery_date: null
      });
      
      // Clear validation errors
      Object.keys(validationErrors).forEach(key => {
        validationErrors[key] = '';
      });
    };

    // Watch for dialog close to reset form
    watch(() => props.visible, (newVal) => {
      if (!newVal) {
        resetForm();
      }
    });

    return {
      formData,
      validationErrors,
      minDate,
      isFormValid,
      validateForm,
      handleCreateDraft,
      handleClose
    };
  }
};
</script>

<style scoped>
/* Form input enhancements */
.p-inputtext:focus,
.p-textarea:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  border-color: rgb(59, 130, 246);
}

/* Dialog animations */
.p-dialog {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>