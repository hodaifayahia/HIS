<template>
  <Dialog
    :visible="visible"
    modal
    :header="audit ? 'Edit Audit' : 'Create New Audit'"
    :style="{ width: '50rem' }"
    :closable="true"
    class="tw-rounded-2xl"
    @update:visible="$emit('close')"
  >
    <div class="tw-p-6">
      <!-- Audit Name -->
      <div class="tw-mb-6">
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="pi pi-tag tw-mr-2"></i>Audit Name <span class="tw-text-red-500">*</span>
        </label>
        <InputText
          v-model="formData.name"
          placeholder="Enter audit name"
          class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-200 tw-rounded-xl focus:tw-border-teal-500"
          :class="{ 'tw-border-red-500': errors.name }"
        />
        <small v-if="errors.name" class="tw-text-red-500">{{ errors.name }}</small>
      </div>

      <!-- Description -->
      <div class="tw-mb-6">
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="pi pi-align-left tw-mr-2"></i>Description
        </label>
        <Textarea
          v-model="formData.description"
          placeholder="Enter audit description"
          rows="4"
          class="tw-w-full tw-p-3 tw-border-2 tw-border-gray-200 tw-rounded-xl focus:tw-border-teal-500"
        />
      </div>

      <!-- Scheduled Date -->
      <div class="tw-mb-6">
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
          <i class="pi pi-calendar tw-mr-2"></i>Scheduled Date
        </label>
        <Calendar
          v-model="formData.scheduled_at"
          showTime
          showIcon
          dateFormat="yy-mm-dd"
          placeholder="Select scheduled date"
          class="tw-w-full"
          :inputClass="'tw-w-full tw-p-3 tw-border-2 tw-border-gray-200 tw-rounded-xl focus:tw-border-teal-500'"
        />
      </div>

      <!-- Global Audit Checkbox -->
      <div class="tw-mb-6">
        <div class="tw-flex tw-items-center tw-gap-3 tw-bg-gradient-to-r tw-from-teal-50 tw-to-cyan-50 tw-p-4 tw-rounded-xl tw-border-2 tw-border-teal-200">
          <Checkbox
            v-model="formData.is_global"
            :binary="true"
            inputId="is_global"
            @change="onGlobalChange"
          />
          <label for="is_global" class="tw-text-sm tw-font-semibold tw-text-gray-800 tw-cursor-pointer">
            <i class="pi pi-globe tw-mr-2 tw-text-teal-600"></i>
            Global Audit (All Products & Stockages)
          </label>
        </div>
        <small class="tw-text-gray-500 tw-mt-2 tw-block">
          When enabled, this audit will include all products across all stockages
        </small>
      </div>

      <!-- Conditional Fields (hidden when is_global is true) -->
      <div v-if="!formData.is_global" class="tw-space-y-6 tw-mb-6">
        <!-- Pharmacy or Stock Selection -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-database tw-mr-2"></i>Inventory Type <span class="tw-text-red-500">*</span>
          </label>
          <small v-if="errors.type" class="tw-text-red-500 tw-mb-2 tw-block">{{ errors.type }}</small>
          <div class="tw-flex tw-gap-4">
            <div 
              class="tw-flex-1 tw-p-4 tw-border-2 tw-rounded-xl tw-cursor-pointer tw-transition-all"
              :class="formData.is_pharmacy_wide ? 'tw-border-teal-500 tw-bg-teal-50' : 'tw-border-gray-200 hover:tw-border-gray-300'"
              @click="selectPharmacy"
            >
              <div class="tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-heart tw-text-2xl" :class="formData.is_pharmacy_wide ? 'tw-text-teal-600' : 'tw-text-gray-400'"></i>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800">Pharmacy</div>
                  <div class="tw-text-xs tw-text-gray-500">Pharmacy Products</div>
                </div>
              </div>
            </div>
            <div 
              class="tw-flex-1 tw-p-4 tw-border-2 tw-rounded-xl tw-cursor-pointer tw-transition-all"
              :class="!formData.is_pharmacy_wide && formData.is_pharmacy_wide !== null ? 'tw-border-teal-500 tw-bg-teal-50' : 'tw-border-gray-200 hover:tw-border-gray-300'"
              @click="selectStock"
            >
              <div class="tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-box tw-text-2xl" :class="!formData.is_pharmacy_wide && formData.is_pharmacy_wide !== null ? 'tw-text-teal-600' : 'tw-text-gray-400'"></i>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800">Stock</div>
                  <div class="tw-text-xs tw-text-gray-500">General Products</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Service Selection -->
        <div v-if="formData.is_pharmacy_wide !== null">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-building tw-mr-2"></i>Service <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="formData.service_id"
            :options="services"
            optionLabel="name"
            optionValue="id"
            placeholder="Select a service"
            class="tw-w-full"
            :filter="true"
            :loading="loadingServices"
            :class="{ 'tw-border-red-500': errors.service }"
            @change="onServiceChange"
          />
          <small v-if="errors.service" class="tw-text-red-500 tw-mt-1 tw-block">{{ errors.service }}</small>
        </div>

        <!-- Stockage Selection -->
        <div v-if="formData.service_id">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-warehouse tw-mr-2"></i>Stockage (Optional)
          </label>
          <Dropdown
            v-model="formData.stockage_id"
            :options="stockages"
            optionLabel="name"
            optionValue="id"
            placeholder="Select a stockage (leave empty for all stockages in service)"
            class="tw-w-full"
            :filter="true"
            :loading="loadingStockages"
            :class="{ 'tw-border-red-500': errors.stockage }"
            showClear
          />
          <small v-if="errors.stockage" class="tw-text-red-500 tw-mt-1 tw-block">{{ errors.stockage }}</small>
          <small v-else class="tw-text-gray-500 tw-mt-2 tw-block">
            Leave empty to audit all stockages in the selected service
          </small>
        </div>
      </div>

      <!-- Participants -->
      <div class="tw-mb-6">
        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
          <i class="pi pi-users tw-mr-2"></i>Participants
        </label>
        
        <div class="tw-space-y-3">
          <div
            v-for="(participant, index) in formData.participants"
            :key="index"
            class="tw-flex tw-gap-3 tw-items-end"
          >
            <!-- User Selection -->
            <div class="tw-flex-1">
              <Dropdown
                v-model="participant.user_id"
                :options="users"
                optionLabel="name"
                optionValue="id"
                placeholder="Select user"
                class="tw-w-full"
                :filter="true"
              />
            </div>

            <!-- Participant Checkbox -->
            <div class="tw-flex tw-items-center tw-gap-2 tw-bg-gray-50 tw-px-4 tw-py-3 tw-rounded-lg">
              <Checkbox
                v-model="participant.is_participant"
                :binary="true"
                inputId="is_participant"
              />
              <label for="is_participant" class="tw-text-sm tw-font-medium tw-text-gray-700">Participant</label>
            </div>

            <!-- Viewer Checkbox -->
            <div class="tw-flex tw-items-center tw-gap-2 tw-bg-gray-50 tw-px-4 tw-py-3 tw-rounded-lg">
              <Checkbox
                v-model="participant.is_able_to_see"
                :binary="true"
                inputId="is_able_to_see"
              />
              <label for="is_able_to_see" class="tw-text-sm tw-font-medium tw-text-gray-700">Viewer</label>
            </div>

            <!-- Remove Button -->
            <Button
              icon="pi pi-trash"
              class="tw-bg-red-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-3"
              @click="removeParticipant(index)"
            />
          </div>

          <!-- Add Participant Button -->
          <Button
            label="Add Participant"
            icon="pi pi-plus"
            class="tw-bg-teal-500 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3 tw-w-full"
            @click="addParticipant"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-3">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
          @click="$emit('close')"
        />
        <Button
          :label="audit ? 'Update' : 'Create'"
          icon="pi pi-check"
          class="tw-bg-teal-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
          @click="handleSubmit"
          :loading="loading"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  audit: {
    type: Object,
    default: null
  },
  users: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'save']);

const formData = reactive({
  name: '',
  description: '',
  scheduled_at: null,
  is_global: false,
  is_pharmacy_wide: null,
  service_id: null,
  stockage_id: null,
  participants: []
});

const services = ref([]);
const stockages = ref([]);
const loadingServices = ref(false);
const loadingStockages = ref(false);
const errors = ref({});
const loading = ref(false);

// Fetch services on component mount
onMounted(() => {
  fetchServices();
});

// Watch for audit changes
watch(() => props.audit, (newAudit) => {
  if (newAudit) {
    Object.assign(formData, {
      name: newAudit.name || '',
      description: newAudit.description || '',
      scheduled_at: newAudit.scheduled_at ? new Date(newAudit.scheduled_at) : null,
      is_global: newAudit.is_global || false,
      is_pharmacy_wide: newAudit.is_pharmacy_wide,
      service_id: newAudit.service_id || null,
      stockage_id: newAudit.stockage_id || null,
      participants: newAudit.participants ? newAudit.participants.map(p => ({
        user_id: p.user_id,
        is_participant: p.is_participant || false,
        is_able_to_see: p.is_able_to_see || false
      })) : []
    });
    
    // Load services if needed
    if (!formData.is_global && formData.is_pharmacy_wide !== null) {
      fetchServices();
    }
    
    // Load stockages if service is selected
    if (formData.service_id) {
      fetchStockages();
    }
  } else {
    resetForm();
  }
}, { immediate: true });

// Watch for visibility changes
watch(() => props.visible, (newVal) => {
  if (newVal && !props.audit) {
    fetchServices();
  }
});

// Watch for is_pharmacy_wide changes to fetch services
watch(() => formData.is_pharmacy_wide, (newVal) => {
  if (newVal !== null && !formData.is_global) {
    fetchServices();
  }
});

const onGlobalChange = () => {
  if (formData.is_global) {
    // Reset all conditional fields when global is selected
    formData.is_pharmacy_wide = null;
    formData.service_id = null;
    formData.stockage_id = null;
    stockages.value = [];
  }
};

const selectPharmacy = () => {
  formData.is_pharmacy_wide = true;
  formData.service_id = null;
  formData.stockage_id = null;
  stockages.value = [];
  fetchServices();
};

const selectStock = () => {
  formData.is_pharmacy_wide = false;
  formData.service_id = null;
  formData.stockage_id = null;
  stockages.value = [];
  fetchServices();
};

const fetchServices = async () => {
  loadingServices.value = true;
  try {
    console.log('Fetching services...');
    const response = await axios.get('/api/services');
    
    // The API returns { status, message, data: [...] }
    services.value = response.data.data || [];
    console.log('Services loaded:', services.value);
  } catch (error) {
    console.error('Error fetching services:', error);
    services.value = [];
  } finally {
    loadingServices.value = false;
  }
};

const onServiceChange = async () => {
  formData.stockage_id = null;
  if (formData.service_id) {
    await fetchStockages();
  } else {
    stockages.value = [];
  }
};

const fetchStockages = async () => {
  if (!formData.service_id) return;
  
  loadingStockages.value = true;
  try {
    const endpoint = formData.is_pharmacy_wide 
      ? '/api/pharmacy/stockages'
      : '/api/stockages';
    
    console.log('Fetching stockages from:', endpoint);
    const response = await axios.get(endpoint, {
      params: {
        service_id: formData.service_id,
        per_page: 1000 // Get all stockages for the service
      }
    });
    
    // Both APIs return { success: true, data: [...], meta: {...} }
    stockages.value = response.data.data || [];
    console.log('Stockages loaded:', stockages.value);
  } catch (error) {
    console.error('Error fetching stockages:', error);
    stockages.value = [];
  } finally {
    loadingStockages.value = false;
  }
};

const addParticipant = () => {
  formData.participants.push({
    user_id: null,
    is_participant: true,
    is_able_to_see: false
  });
};

const removeParticipant = (index) => {
  formData.participants.splice(index, 1);
};

const validate = () => {
  errors.value = {};

  if (!formData.name || formData.name.trim() === '') {
    errors.value.name = 'Audit name is required';
  }

  // Validate conditional fields only if not global
  if (!formData.is_global) {
    if (formData.is_pharmacy_wide === null) {
      errors.value.type = 'Please select Pharmacy or Stock';
    }
    if (!formData.service_id) {
      errors.value.service = 'Please select a service';
    }
    
    // Validate stockage selection matches the inventory type
    if (formData.stockage_id && stockages.value.length > 0) {
      const selectedStockage = stockages.value.find(s => s.id === formData.stockage_id);
      if (!selectedStockage) {
        errors.value.stockage = 'Selected stockage is not valid for this inventory type. Please select again.';
        formData.stockage_id = null; // Clear invalid selection
      }
    }
  }

  return Object.keys(errors.value).length === 0;
};

const handleSubmit = () => {
  if (!validate()) {
    console.error('Validation failed:', errors.value);
    return;
  }

  loading.value = true;

  // Format the data
  const submitData = {
    name: formData.name,
    description: formData.description,
    scheduled_at: formData.scheduled_at ? formatDate(formData.scheduled_at) : null,
    is_global: formData.is_global,
    is_pharmacy_wide: formData.is_global ? null : formData.is_pharmacy_wide,
    service_id: formData.is_global ? null : formData.service_id,
    stockage_id: formData.is_global ? null : formData.stockage_id,
    participants: formData.participants.filter(p => p.user_id)
  };

  console.log('Submitting audit data:', submitData);
  console.log('Available stockages:', stockages.value);
  console.log('Is pharmacy wide:', formData.is_pharmacy_wide);

  emit('save', submitData);
  loading.value = false;
};

const formatDate = (date) => {
  if (!date) return null;
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  const seconds = String(d.getSeconds()).padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

const resetForm = () => {
  Object.assign(formData, {
    name: '',
    description: '',
    scheduled_at: null,
    is_global: false,
    is_pharmacy_wide: null,
    service_id: null,
    stockage_id: null,
    participants: []
  });
  services.value = [];
  stockages.value = [];
  errors.value = {};
};
</script>

<style scoped>
.p-dialog {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
