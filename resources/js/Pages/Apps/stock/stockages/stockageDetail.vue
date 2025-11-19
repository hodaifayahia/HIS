<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <!-- Header -->
    <div class="tw-mb-8">
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-p-3 tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-info-circle tw-text-white tw-text-2xl"></i>
              </div>
              <div>
                <h1 class="tw-m-0 tw-text-gray-800 tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 tw-bg-clip-text tw-text-transparent">
                  Stockage Details
                </h1>
                <p class="tw-m-0 tw-text-gray-600 tw-text-sm tw-mt-1">{{ stockage.name }} - {{ stockage.location_code }}</p>
              </div>
            </div>
            <div class="tw-flex tw-gap-4 tw-items-center">
              <Button
                icon="pi pi-arrow-left"
                label="Back to List"
                class="p-button-secondary tw-rounded-lg tw-shadow-md"
                @click="goBack"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Stockage Information -->
    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6 tw-mb-8">
      <!-- Basic Information Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm hover:tw-shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-info-circle tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Basic Information</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-space-y-4">
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Name:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ stockage.name }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Location Code:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ stockage.location_code }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Warehouse Type:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ stockage.warehouse_type }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Service:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ stockage.service?.name || 'N/A' }}</span>
            </div>
          </div>
        </template>
      </Card>

      <!-- Statistics Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm hover:tw-shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-chart-bar tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Statistics</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-space-y-4">
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Total Locations:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ totalRecords }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Active Tools:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ tools.filter(t => t.status === 'active').length }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Tool Types:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ toolTypes.length }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-gray-50 tw-rounded-lg">
              <span class="tw-font-medium tw-text-gray-700">Last Updated:</span>
              <span class="tw-text-gray-900 tw-font-semibold">{{ formatDate(stockage.updated_at) }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Location Management Section -->
    <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm hover:tw-shadow-2xl tw-transition-all tw-duration-300">
      <template #header>
        <div class="tw-p-4 tw-bg-gradient-to-r tw-from-purple-500 tw-to-pink-600 tw-text-white tw-rounded-t-lg">
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-map-marker tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Location Management</h3>
            </div>
            <Button
              icon="pi pi-plus"
              label="Add Location"
              class="p-button-primary tw-rounded-lg tw-shadow-md tw-bg-white/20 tw-border-white/30 tw-text-white hover:tw-bg-white/30"
              @click="showAddToolModal = true"
            />
          </div>
        </div>
      </template>
      <template #content>
        <!-- Filters -->
        <div class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-blue-50 tw-p-6 tw-rounded-xl tw-mb-6 tw-border tw-border-gray-100">
          <div class="tw-flex tw-flex-wrap tw-gap-6">
            <div class="tw-flex-1 tw-min-w-48">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-filter tw-text-blue-500"></i>
                Tool Type
              </label>
              <Dropdown
                v-model="filters.tool_type"
                :options="toolTypes"
                option-label="label"
                option-value="value"
                placeholder="All Types"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                @change="fetchTools"
                show-clear
              />
            </div>
            <div class="tw-flex-1 tw-min-w-48">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-search tw-text-green-500"></i>
                Search
              </label>
              <InputText
                v-model="filters.search"
                placeholder="Search by number, block, or level..."
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                @input="debounceSearch"
              />
            </div>
          </div>
        </div>

        <!-- Tools List -->
        <div v-if="loading" class="tw-text-center tw-py-16 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-border tw-border-blue-100">
          <ProgressSpinner class="tw-mb-4" />
          <p class="tw-text-gray-600 tw-font-medium">Loading locations...</p>
        </div>

        <div v-else-if="tools.length === 0" class="tw-text-center tw-py-16 tw-bg-gradient-to-r tw-from-gray-50 tw-to-blue-50 tw-rounded-xl tw-border tw-border-gray-100">
          <i class="pi pi-inbox tw-text-5xl tw-text-gray-400 tw-mb-4"></i>
          <p class="tw-text-gray-600 tw-text-lg tw-font-medium">No locations found.</p>
          <p class="tw-text-gray-500 tw-text-sm">Try adjusting your filters or add a new location.</p>
        </div>

        <div v-else>
          <DataTable
            :value="tools"
            class="p-datatable-sm tw-rounded-xl tw-overflow-hidden tw-shadow-lg tw-border tw-border-gray-200"
            striped-rows
            show-gridlines
            responsive-layout="scroll"
            :paginator="false"
          >
            <Column field="tool_type" header="Tool Type" style="width: 150px">
              <template #body="slotProps">
                <Tag
                  :value="getToolTypeLabel(slotProps.data.tool_type)"
                  :severity="getToolTypeSeverity(slotProps.data.tool_type)"
                  class="tw-font-semibold tw-px-3 tw-py-2 tw-rounded-lg"
                />
              </template>
            </Column>
            <Column field="tool_number" header="Number" style="width: 100px">
              <template #body="slotProps">
                <span class="tw-font-mono tw-font-bold tw-text-lg tw-text-gray-800 tw-bg-gray-100 tw-px-3 tw-py-2 tw-rounded-lg">
                  {{ slotProps.data.tool_number }}
                </span>
              </template>
            </Column>
            <Column field="block" header="Block" style="width: 100px">
              <template #body="slotProps">
                <span v-if="slotProps.data.block" class="tw-font-mono tw-font-bold tw-text-lg tw-text-blue-600 tw-bg-blue-50 tw-px-3 tw-py-2 tw-rounded-lg">
                  {{ slotProps.data.block }}
                </span>
                <span v-else class="tw-text-gray-400 tw-font-medium">-</span>
              </template>
            </Column>
            <Column field="location_code" header="Location Code" style="width: 180px">
              <template #body="slotProps">
                <span class="tw-font-mono tw-font-bold tw-text-sm tw-text-indigo-600 tw-bg-indigo-50 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-indigo-200">
                  {{ slotProps.data.location_code }}
                </span>
              </template>
            </Column>
            <Column header="Actions" style="width: 150px">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Button
                    icon="pi pi-pencil"
                    class="p-button-text p-button-warning p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                    @click="editTool(slotProps.data)"
                    v-tooltip.top="'Edit Location'"
                  />
                  <Button
                    icon="pi pi-trash"
                    class="p-button-text p-button-danger p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                    @click="deleteTool(slotProps.data)"
                    v-tooltip.top="'Delete Location'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="tw-flex tw-justify-center tw-mt-8">
            <Paginator
              :first="(currentPage - 1) * 15"
              :rows="15"
              :total-records="totalRecords"
              :rows-per-page-options="[15, 30, 50]"
              class="tw-rounded-xl tw-shadow-lg tw-border tw-border-gray-200"
              @page="onPageChange"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Add/Edit Tool Dialog -->
    <Dialog
      v-model:visible="showToolDialog"
      :header="showEditToolModal ? 'Edit Location' : 'Add New Location'"
      modal
      :closable="true"
      :style="{ width: '500px' }"
      :dismissible-mask="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-p-2">
        <form @submit.prevent="saveTool" class="tw-space-y-6">
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-cog tw-text-blue-500"></i>
              Tool Type *
            </label>
            <Dropdown
              v-model="toolForm.tool_type"
              :options="toolTypes"
              option-label="label"
              option-value="value"
              placeholder="Select Type"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              :class="{ 'p-invalid': errors.tool_type }"
              @change="onToolTypeChange"
              required
            />
            <small v-if="errors.tool_type" class="p-error tw-mt-1 tw-block">{{ errors.tool_type }}</small>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-hashtag tw-text-green-500"></i>
              Tool Number *
            </label>
            <InputNumber
              v-model="toolForm.tool_number"
              :min="1"
              placeholder="Enter tool number"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              :class="{ 'p-invalid': errors.tool_number }"
              required
            />
            <small v-if="errors.tool_number" class="p-error tw-mt-1 tw-block">{{ errors.tool_number }}</small>
          </div>

          <div v-if="toolForm.tool_type === 'RY'">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-th-large tw-text-purple-500"></i>
              Block *
            </label>
            <Dropdown
              v-model="toolForm.block"
              :options="blocks"
              option-label="label"
              option-value="value"
              placeholder="Select Block"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              :class="{ 'p-invalid': errors.block }"
              required
            />
            <small v-if="errors.block" class="p-error tw-mt-1 tw-block">{{ errors.block }}</small>
          </div>

          <div v-if="toolForm.tool_type === 'RY'">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-sort-numeric-up tw-text-orange-500"></i>
              Shelf Level *
            </label>
            <InputNumber
              v-model="toolForm.shelf_level"
              :min="1"
              placeholder="Enter shelf level"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              :class="{ 'p-invalid': errors.shelf_level }"
              required
            />
            <small v-if="errors.shelf_level" class="p-error tw-mt-1 tw-block">{{ errors.shelf_level }}</small>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-map-marker tw-text-indigo-500"></i>
              Location Code
            </label>
            <InputText
              :value="locationCodePreview"
              placeholder="Auto-generated"
              class="tw-w-full tw-rounded-lg tw-shadow-sm tw-bg-gray-50"
              readonly
              :class="{ 'p-invalid': errors.location_code }"
            />
            <small class="tw-text-gray-500 tw-text-xs tw-mt-1 tw-block">This code is auto-generated based on the tool details</small>
            <small v-if="errors.location_code" class="p-error tw-mt-1 tw-block">{{ errors.location_code }}</small>
          </div>
        </form>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-4 tw-p-4 tw-bg-gray-50 tw-rounded-b-2xl">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text p-button-secondary tw-rounded-lg tw-shadow-sm"
            @click="closeModals"
          />
          <Button
            :label="showEditToolModal ? 'Update Location' : 'Create Location'"
            icon="pi pi-check"
            class="p-button-primary tw-rounded-lg tw-shadow-md tw-px-6"
            :loading="saving"
            @click="saveTool"
          />
        </div>
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog
      v-model:visible="showDeleteModal"
      header="Delete Location"
      modal
      :closable="true"
      :style="{ width: '450px' }"
      :dismissible-mask="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-text-center tw-p-4">
        <div class="tw-mb-6">
          <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-16 tw-h-16 tw-bg-red-100 tw-rounded-full tw-mb-4">
            <i class="pi pi-exclamation-triangle tw-text-3xl tw-text-red-500"></i>
          </div>
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-2">Confirm Deletion</h3>
          <p class="tw-text-gray-600 tw-mb-4">
            Are you sure you want to delete this location? This action cannot be undone.
          </p>
        </div>
        <div class="tw-bg-gradient-to-r tw-from-red-50 tw-to-pink-50 tw-p-4 tw-rounded-xl tw-border tw-border-red-100 tw-mb-4">
          <p class="tw-text-sm tw-text-gray-700 tw-mb-2">
            <strong class="tw-text-red-600">Location Details:</strong>
          </p>
          <div class="tw-space-y-1 tw-text-left">
            <p class="tw-text-sm tw-text-gray-600">
              <strong>Type:</strong> {{ getToolTypeLabel(toolToDelete?.tool_type) }}
            </p>
            <p class="tw-text-sm tw-text-gray-600">
              <strong>Number:</strong> {{ toolToDelete?.tool_number }}
            </p>
            <p class="tw-text-sm tw-text-gray-600">
              <strong>Block:</strong> {{ toolToDelete?.block || 'N/A' }}
            </p>
            <p class="tw-text-sm tw-text-gray-600">
              <strong>Shelf Level:</strong> {{ toolToDelete?.shelf_level || 'N/A' }}
            </p>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-4 tw-p-4 tw-bg-gray-50 tw-rounded-b-2xl">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text p-button-secondary tw-rounded-lg tw-shadow-sm"
            @click="closeModals"
          />
          <Button
            label="Delete Location"
            icon="pi pi-trash"
            class="p-button-danger tw-rounded-lg tw-shadow-md tw-px-6"
            :loading="deleting"
            @click="confirmDelete"
          />
        </div>
      </template>
    </Dialog>

    <!-- Toast for notifications -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

// Props
const props = defineProps({
  stockageId: {
    type: [String, Number],
    required: true
  }
});

// Composables
const router = useRouter();
const toast = useToast();

// Reactive data
const stockage = ref({});
const tools = ref([]);
const toolTypes = ref([]);
const blocks = ref([]);
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const showAddToolModal = ref(false);
const showEditToolModal = ref(false);
const showDeleteModal = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const totalRecords = ref(0);
const filters = ref({
  tool_type: '',
  search: ''
});
const toolForm = ref({
  tool_type: '',
  tool_number: '',
  block: '',
  shelf_level: ''
});
const errors = ref({});
const editingTool = ref(null);
const toolToDelete = ref(null);
const searchTimeout = ref(null);

// Computed
const showToolDialog = computed(() => showAddToolModal.value || showEditToolModal.value);

const locationCodePreview = computed(() => {
  if (!toolForm.value.tool_type || !toolForm.value.tool_number) {
    return '';
  }

  const serviceAbv = stockage.value?.service?.service_abv || '';
  const stockageLocationCode = stockage.value?.location_code || '';

  let code = serviceAbv + stockageLocationCode + '-' + toolForm.value.tool_type + toolForm.value.tool_number;

  if (toolForm.value.tool_type === 'RY' && toolForm.value.block && toolForm.value.shelf_level) {
    code += '-' + toolForm.value.block + toolForm.value.shelf_level;
  }

  return code;
});

// Methods
const fetchStockage = async () => {
  try {
    const response = await axios.get(`/api/stockages/${props.stockageId}`);
    stockage.value = response.data.data;
  } catch (error) {
    showError('Failed to load stockage details');
  }
};

const fetchToolTypes = async () => {
  try {
    const response = await axios.get('/api/stockage-tools/types');
    toolTypes.value = response.data.data;
  } catch (error) {
    showError('Failed to load tool types');
  }
};

const fetchBlocks = async () => {
  try {
    const response = await axios.get('/api/stockage-tools/blocks');
    blocks.value = response.data.data;
  } catch (error) {
    showError('Failed to load blocks');
  }
};

const fetchTools = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      ...filters.value
    };

    const response = await axios.get(`/api/stockages/${props.stockageId}/tools`, { params });

    if (response.data && response.data.success && response.data.data) {
      tools.value = response.data.data.data || [];
      totalPages.value = response.data.data.last_page || 1;
      totalRecords.value = response.data.data.total || 0;
      currentPage.value = response.data.data.current_page || 1;

      // If current page is greater than total pages, reset to last page
      if (currentPage.value > totalPages.value && totalPages.value > 0) {
        currentPage.value = totalPages.value;
        // Re-fetch with correct page
        await fetchTools();
        return;
      }
    } else {
      tools.value = [];
      totalPages.value = 1;
      totalRecords.value = 0;
      currentPage.value = 1;
    }
  } catch (error) {
    console.error('Error fetching tools:', error);
    showError('Failed to load locations');
    tools.value = [];
    totalPages.value = 1;
    totalRecords.value = 0;
    currentPage.value = 1;
  } finally {
    loading.value = false;
  }
};

const getToolTypeLabel = (type) => {
  const toolType = toolTypes.value.find(t => t.value === type);
  return toolType ? toolType.label : type;
};

const getToolTypeSeverity = (type) => {
  const severityMap = {
    'RY': 'info',
    'AR': 'success',
    'CF': 'warning',
    'FR': 'danger',
    'CS': 'secondary',
    'CH': 'primary',
    'PL': 'help'
  };
  return severityMap[type] || 'info';
};

const onToolTypeChange = () => {
  if (toolForm.value.tool_type !== 'RY') {
    toolForm.value.block = '';
    toolForm.value.shelf_level = '';
  }
  errors.value = {};
};

const editTool = (tool) => {
  editingTool.value = tool;
  toolForm.value = {
    tool_type: tool.tool_type,
    tool_number: tool.tool_number,
    block: tool.block || '',
    shelf_level: tool.shelf_level || ''
  };
  errors.value = {};
  showEditToolModal.value = true;
};

const saveTool = async () => {
  saving.value = true;
  errors.value = {};

  try {
    const url = showEditToolModal.value
      ? `/api/stockages/${props.stockageId}/tools/${editingTool.value.id}`
      : `/api/stockages/${props.stockageId}/tools`;

    const method = showEditToolModal.value ? 'put' : 'post';

    const response = await axios[method](url, toolForm.value);

    if (response.data.success) {
      showSuccess(response.data.message);
      closeModals();
      // Reset to first page after operation
      currentPage.value = 1;
      await fetchTools();
    } else {
      showError(response.data.message);
    }
  } catch (error) {
    if (error.response && error.response.data && error.response.data.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response && error.response.data && error.response.data.message) {
      showError(error.response.data.message);
    } else {
      showError('Failed to save location');
    }
  } finally {
    saving.value = false;
  }
};

const deleteTool = (tool) => {
  toolToDelete.value = tool;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  deleting.value = true;
  try {
    const response = await axios.delete(`/api/stockages/${props.stockageId}/tools/${toolToDelete.value.id}`);

    if (response.data.success) {
      showSuccess(response.data.message);
      closeModals();
      // Reset to first page after deletion
      currentPage.value = 1;
      await fetchTools();
    } else {
      showError(response.data.message);
    }
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      showError(error.response.data.message);
    } else {
      showError('Failed to delete location');
    }
  } finally {
    deleting.value = false;
  }
};

const closeModals = () => {
  showAddToolModal.value = false;
  showEditToolModal.value = false;
  showDeleteModal.value = false;
  editingTool.value = null;
  toolToDelete.value = null;
  toolForm.value = {
    tool_type: '',
    tool_number: '',
    block: '',
    shelf_level: ''
  };
  errors.value = {};
  // Clear any pending search timeout
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
    searchTimeout.value = null;
  }
};

const onPageChange = (event) => {
  currentPage.value = event.page + 1;
  fetchTools();
};

const debounceSearch = () => {
  clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => {
    currentPage.value = 1; // Reset to first page when searching
    fetchTools();
  }, 500);
};

const showSuccess = (message) => {
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: message,
    life: 3000
  });
};

const showError = (message) => {
  toast.add({
    severity: 'error',
    summary: 'Error',
    detail: message,
    life: 5000
  });
};

const goBack = () => {
  router.go(-1);
};

const getStatusSeverity = (status) => {
  const severityMap = {
    'active': 'success',
    'inactive': 'danger',
    'maintenance': 'warning'
  };
  return severityMap[status] || 'info';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Lifecycle
onMounted(() => {
  fetchStockage();
  fetchToolTypes();
  fetchBlocks();
  fetchTools();
});
</script>

<style scoped>
/* Custom animations */
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slide-in {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 5px rgba(59, 130, 246, 0.5);
  }
  50% {
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.8);
  }
}

.tw-animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

.tw-animate-slide-in {
  animation: slide-in 0.4s ease-out;
}

.tw-animate-pulse-glow {
  animation: pulse-glow 2s infinite;
}

/* PrimeVue component customizations */
.p-card {
  border: none !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
  transition: all 0.3s ease !important;
}

.p-card:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
}

.p-card-content {
  padding: 1.5rem !important;
}

.p-datatable {
  border-radius: 12px !important;
  overflow: hidden !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

.p-datatable .p-datatable-tbody > tr {
  transition: all 0.2s ease !important;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1)) !important;
  transform: scale(1.01) !important;
}

.p-dialog {
  border-radius: 16px !important;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2) !important;
}

.p-dialog .p-dialog-header {
  padding: 1.5rem 2rem !important;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
  border-radius: 16px 16px 0 0 !important;
  border-bottom: none !important;
}

.p-dialog .p-dialog-content {
  padding: 2rem !important;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
}

.p-dialog .p-dialog-footer {
  padding: 1.5rem 2rem !important;
  background: #f8fafc !important;
  border-radius: 0 0 16px 16px !important;
  border-top: 1px solid #e2e8f0 !important;
}

/* Form field enhancements */
.p-field {
  margin-bottom: 1.5rem !important;
}

.p-field label {
  display: block !important;
  margin-bottom: 0.75rem !important;
  font-weight: 600 !important;
  color: #374151 !important;
  font-size: 0.875rem !important;
}

/* Tag customizations */
.p-tag {
  font-weight: 600 !important;
  padding: 0.5rem 1rem !important;
  border-radius: 8px !important;
  font-size: 0.875rem !important;
}

/* Button customizations */
.p-button {
  border-radius: 8px !important;
  font-weight: 600 !important;
  transition: all 0.3s ease !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

.p-button:hover {
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.p-button.p-button-sm {
  padding: 0.5rem 1rem !important;
  font-size: 0.875rem !important;
}

.p-button.p-button-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  border: none !important;
}

.p-button.p-button-primary:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%) !important;
}

/* Input customizations */
.p-inputtext,
.p-dropdown,
.p-inputnumber {
  border-radius: 8px !important;
  border: 2px solid #e5e7eb !important;
  transition: all 0.3s ease !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.p-inputtext:focus,
.p-dropdown:focus,
.p-inputnumber:focus {
  border-color: #667eea !important;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
}

.p-invalid {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

/* Paginator customization */
.p-paginator {
  border-radius: 12px !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
  background: white !important;
}

/* Progress spinner customization */
.p-progress-spinner {
  color: #667eea !important;
}

/* Toast customization */
.p-toast {
  border-radius: 12px !important;
}

.p-toast .p-toast-message {
  border-radius: 8px !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-1.md\\:tw-grid-cols-2.lg\\:tw-grid-cols-4 {
    grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
  }

  .p-dialog {
    width: 95vw !important;
    max-width: none !important;
  }

  .tw-flex-wrap {
    flex-direction: column !important;
  }

  .tw-gap-4 {
    gap: 1rem !important;
  }
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}
</style>
