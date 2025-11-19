<template>
  <Dialog
    :visible="showModal"
    modal
    :header="title || 'Add New Stockage'"
    :style="{ width: '50rem' }"
    :maximizable="true"
    @update:visible="closeModal"
  >
    <form @submit.prevent="submitForm" class="tw-p-4">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
        <div class="tw-col-span-full">
          <Card class="tw-mb-4">
            <template #content>
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Basic Information
              </h4>

              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Name *</label>
                  <InputText
                    v-model="formData.name"
                    required
                    class="tw-w-full"
                    placeholder="Enter stockage name"
                  />
                </div>

                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Location *</label>
                  <InputText
                    v-model="formData.location"
                    required
                    class="tw-w-full"
                    placeholder="Enter location"
                  />
                </div>

                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Location Code</label>
                  <InputText
                    v-model="formData.location_code"
                    class="tw-w-full"
                    placeholder="Enter location code"
                  />
                </div>

                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Warehouse Type *</label>
                  <Dropdown
                    v-model="formData.warehouse_type"
                    :options="warehouseTypeOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select warehouse type"
                    class="tw-w-full"
                    required
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <div class="tw-col-span-full">
          <Card class="tw-mb-4">
            <template #content>
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-cog tw-text-green-500"></i>
                Stockage Details
              </h4>

              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Capacity</label>
                  <InputNumber
                    v-model="formData.capacity"
                    class="tw-w-full"
                    placeholder="Enter capacity"
                    :min="0"
                  />
                </div>

                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status *</label>
                  <Dropdown
                    v-model="formData.status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select status"
                    class="tw-w-full"
                    required
                  />
                </div>

                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Type</label>
                  <Dropdown
                    v-model="formData.type"
                    :options="typeOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select type"
                    class="tw-w-full"
                  />
                </div>

                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Security Level</label>
                  <Dropdown
                    v-model="formData.security_level"
                    :options="securityLevelOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select security level"
                    class="tw-w-full"
                  />
                </div>

                <div class="tw-col-span-full">
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Service *</label>
                  <Dropdown
                    v-model="formData.service_id"
                    :options="services"
                    option-label="name"
                    option-value="id"
                    placeholder="Select a service"
                    class="tw-w-full"
                    :disabled="!!preSelectedServiceId"
                    required
                  />
                  <small v-if="preSelectedServiceId" class="tw-text-gray-500 tw-mt-1 tw-block">
                    Service is pre-selected for this stockage
                  </small>
                </div>

                <div class="tw-col-span-full">
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Description</label>
                  <Textarea
                    v-model="formData.description"
                    rows="3"
                    class="tw-w-full"
                    placeholder="Enter description"
                  />
                </div>

                <div class="tw-flex tw-items-center tw-gap-2">
                  <Checkbox
                    v-model="formData.temperature_controlled"
                    inputId="temperature_controlled"
                    :binary="true"
                    class="tw-cursor-pointer"
                  />
                  <label for="temperature_controlled" class="tw-text-sm tw-font-medium tw-text-gray-700 tw-cursor-pointer">
                    Temperature Controlled
                  </label>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div class="tw-flex tw-justify-end tw-gap-3 tw-mt-6 tw-pt-4 tw-border-t tw-border-gray-200">
        <Button
          type="button"
          label="Cancel"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="closeModal"
        />
        <Button
          type="submit"
          :label="isSubmitting ? 'Creating...' : 'Create Stockage'"
          :icon="isSubmitting ? 'pi pi-spin pi-spinner' : 'pi pi-plus'"
          class="p-button-primary"
          :disabled="isSubmitting"
          :loading="isSubmitting"
        />
      </div>
    </form>
  </Dialog>
</template>

<script>
import axios from 'axios';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Card from 'primevue/card';

export default {
  name: 'AddStockageModal',
  components: {
    Dialog,
    Button,
    InputText,
    Dropdown,
    Textarea,
    InputNumber,
    Checkbox,
    Card
  },
  props: {
    showModal: {
      type: Boolean,
      default: false
    },
    preSelectedServiceId: {
      type: [Number, String],
      default: null
    },
    title: {
      type: String,
      default: 'Add New Stockage'
    }
  },
  emits: ['close', 'success'],
  data() {
    return {
      isSubmitting: false,
      services: [],
      formData: {
        name: '',
        description: '',
        location: '',
        type: '',
        capacity: null,
        status: 'active',
        service_id: null,
        temperature_controlled: false,
        security_level: 'medium',
        location_code: '',
        warehouse_type: ''
      },
      // Dropdown options
      typeOptions: [
        { label: 'Warehouse', value: 'warehouse' },
        { label: 'Pharmacy', value: 'pharmacy' },
        { label: 'Laboratory', value: 'laboratory' },
        { label: 'Emergency', value: 'emergency' },
        { label: 'Storage Room', value: 'storage_room' },
        { label: 'Cold Room', value: 'cold_room' }
      ],
      warehouseTypeOptions: [
        { label: 'Central Pharmacy (PC)', value: 'Central Pharmacy (PC)' },
        { label: 'Service Pharmacy (PS)', value: 'Service Pharmacy (PS)' }
      ],
      statusOptions: [
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
        { label: 'Maintenance', value: 'maintenance' },
        { label: 'Under Construction', value: 'under_construction' }
      ],
      securityLevelOptions: [
        { label: 'Low', value: 'low' },
        { label: 'Medium', value: 'medium' },
        { label: 'High', value: 'high' },
        { label: 'Restricted', value: 'restricted' }
      ]
    }
  },
  watch: {
    showModal(newVal) {
      if (newVal) {
        this.resetForm();
        this.fetchServices();
        if (this.preSelectedServiceId) {
          this.formData.service_id = this.preSelectedServiceId;
        }
      }
    }
  },
  methods: {
    async fetchServices() {
      try {
        const response = await axios.get('/api/services');
        const resData = response.data;
        if (Array.isArray(resData)) {
          this.services = resData;
        } else if (resData && Array.isArray(resData.data)) {
          this.services = resData.data;
        }
      } catch (error) {
      }
    },

    resetForm() {
      this.formData = {
        name: '',
        description: '',
        location: '',
        type: '',
        capacity: null,
        status: 'active',
        service_id: this.preSelectedServiceId || null,
        temperature_controlled: false,
        security_level: 'medium',
        location_code: '',
        warehouse_type: ''
      };
    },

    closeModal() {
      this.$emit('close');
    },

    async submitForm() {
      this.isSubmitting = true;

      try {
        const response = await axios.post('/api/stockages', this.formData);
        if (response.data.success) {
          this.$emit('success', response.data.data);
          this.closeModal();
        }
      } catch (error) {
        if (error.response && error.response.data.errors) {
          // Handle validation errors
        }
        // You might want to emit an error event here
      } finally {
        this.isSubmitting = false;
      }
    }
  }
}
</script>

<style scoped>
/* Minimal checkbox styling to ensure visibility */
.p-checkbox {
  display: inline-flex;
  align-items: center;
}

.p-checkbox .p-checkbox-box {
  border: 1px solid #d1d5db;
  background: #ffffff;
  transition: all 0.2s ease;
}

.p-checkbox .p-checkbox-box:hover {
  border-color: #3b82f6;
}

.p-checkbox.p-checkbox-checked .p-checkbox-box {
  background: #3b82f6;
  border-color: #3b82f6;
}

.p-checkbox .p-checkbox-icon {
  color: #ffffff;
}

/* Label styling */
label {
  user-select: none;
  cursor: pointer;
}
</style>