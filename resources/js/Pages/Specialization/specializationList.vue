<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import specializationModel from "../../Components/specializationModel.vue";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import Tooltip from 'primevue/tooltip';

// Register directive for template
const vTooltip = Tooltip;

const specializations = ref([]);
const pagination = ref({});
const selectedSpecialization = ref({ name: '', description: '', service_id: '', photo_url: null, is_active: true });
const isModalOpen = ref(false);
const toaster = useToastr();
const searchQuery = ref('');
const isLoading = ref(false);
const selectedSpecializations = ref([]);
const loading = ref(false);
const file = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const fileInput = ref(null);
const confirm = useConfirm();

// Fetch specializations from the server
const getSpecializations = async (page = 1) => {
  try {
    const response = await axios.get(`/api/specializations?page=${page}`);
    specializations.value = response.data.data;
    pagination.value = response.data.meta;
    selectAll.value = false;
    selectAllSpecializations.value = [];
  } catch (error) {
    toaster.error('Failed to fetch specializations');
    console.error('Error fetching specializations:', error);
  }
};

const exportSpecializations = async () => {
  try {
    const response = await axios.get('/api/specializations/export', {
      responseType: 'blob',
    });
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = 'specializations.xlsx';
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error('Failed to export specializations:', error);
    toaster.error('Failed to export specializations');
  }
};

const handleFileChange = (event) => {
  file.value = event.target.files[0];
  errorMessage.value = '';
  successMessage.value = '';
};

const uploadFile = async () => {
  if (!file.value) {
    errorMessage.value = 'Please select a file.';
    return;
  }
  const allowedTypes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
  if (!allowedTypes.includes(file.value.type)) {
    errorMessage.value = 'Please upload a CSV or XLSX file.';
    return;
  }

  const formData = new FormData();
  formData.append('file', file.value);

  try {
    loading.value = true;
    const response = await axios.post('/api/specializations/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
    });

    if (response.data.success) {
      successMessage.value = response.data.message;
      refreshSpecializations();
      toaster.success('Specializations imported successfully!');
      errorMessage.value = '';
    } else {
      errorMessage.value = response.data.message;
      successMessage.value = '';
    }
  } catch (error) {
    console.error('Import error:', error);
    errorMessage.value = error.response?.data?.message || 'An error occurred during the file import.';
    successMessage.value = '';
  } finally {
    loading.value = false;
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};

const addNewSpecialization = () => {
  selectedSpecialization.value = { name: '', description: '', service_id: '', photo_url: null, is_active: true };
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const refreshSpecializations = () => {
  getSpecializations();
};

const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/specializations/search', {
          params: { query: searchQuery.value },
        });
        specializations.value = response.data.data;
        pagination.value = response.data.meta;
      } catch (error) {
        toaster.error('Failed to search specializations');
        console.error('Error searching specializations:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300);
  };
})();

watch(searchQuery, debouncedSearch);

const deleteSpecialization = (specialization) => {
  confirm.require({
    message: `Are you sure you want to delete ${specialization.name}?`,
    header: 'Confirm',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await axios.delete(`/api/specializations/${specialization.id}`);
        toaster.success('Specialization deleted successfully!');
        refreshSpecializations();
      } catch (error) {
        toaster.error('Failed to delete specialization');
        console.error('Error deleting specialization:', error);
      }
    },
  });
};

const bulkDelete = async () => {
  confirm.require({
    message: `Delete ${selectedSpecializations.value.length} selected specializations?`,
    header: 'Confirm Bulk Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await axios.delete('/api/specializations', {
          params: { ids: selectedSpecializations.value },
        });
        toaster.success('Specializations deleted successfully!');
        selectedSpecializations.value = [];
        selectAll.value = false;
        getSpecializations();
      } catch (error) {
        toaster.error('Failed to delete specializations');
        console.error('Error deleting specializations:', error);
      }
    },
  });
};

const selectAll = ref(false);

const selectAllSpecializations = () => {
  if (selectAll.value) {
    selectedSpecializations.value = specializations.value.map(specialization => specialization.id);
  } else {
    selectedSpecializations.value = [];
  }
};

const getStatusColor = (isActive) => {
  return isActive ? 'success' : 'danger';
};

const getInitials = (name) => {
  return name
    ?.split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase() || 'S';
};

const selectedStatus = ref(null);

const getRelativeTime = (date) => {
  if (!date) return '';
  const now = new Date();
  const past = new Date(date);
  const diffInSeconds = Math.floor((now - past) / 1000);

  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
  return formatDateOnly(date);
};

const formatDateOnly = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const filteredSpecializations = computed(() => {
  return specializations.value.filter(specialization => {
    const matchesSearch = !searchQuery.value || 
      specialization.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      specialization.description?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      specialization.service_name?.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesStatus = !selectedStatus.value || specialization.is_active === selectedStatus.value;
    
    return matchesSearch && matchesStatus;
  });
});

const totalRecords = computed(() => {
  return filteredSpecializations.value.length;
});

const setQuickFilter = (status) => {
  selectedStatus.value = selectedStatus.value === status ? null : status;
};

const clearSearch = () => {
  searchQuery.value = '';
};

const clearAllFilters = () => {
  searchQuery.value = '';
  selectedStatus.value = null;
};

const editSpecialization = (specialization) => {
  selectedSpecialization.value = specialization;
  isModalOpen.value = true;
};

const viewSpecialization = (specialization) => {
  selectedSpecialization.value = specialization;
  isModalOpen.value = true;
};

const handleImageError = (event) => {
  // If image fails to load, hide it to show avatar fallback
  console.warn('Image failed to load:', event.target.src);
  console.warn('Error event:', event);
  event.target.style.display = 'none';
  // Show next sibling (avatar)
  if (event.target.nextElementSibling) {
    event.target.nextElementSibling.style.display = 'block';
  }
};

const handleImageLoad = (event) => {
  // Image loaded successfully
  console.log('✓ Image loaded successfully:', event.target.src);
};

onMounted(() => {
  getSpecializations();
});
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-stethoscope tw-text-white tw-text-2xl"></i>
              </div>
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-400 tw-rounded-full tw-border-2 tw-border-white tw-animate-pulse"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
                Specialization Management
              </h1>
              <p class="tw-text-slate-600 tw-text-sm tw-mt-1 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Manage medical specializations and their status
              </p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-stethoscope tw-text-blue-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-blue-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-blue-700 tw-font-medium tw-uppercase tw-tracking-wide">Total Specializations</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">
                    {{ specializations.length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-green-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-check-circle tw-text-green-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-green-700 tw-font-medium tw-uppercase tw-tracking-wide">Active</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">
                    {{ specializations.filter(s => s.is_active).length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-amber-100 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-chart-line tw-text-amber-600 tw-text-lg"></i>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-amber-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-amber-700 tw-font-medium tw-uppercase tw-tracking-wide">Inactive</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-amber-800">
                    {{ specializations.filter(s => !s.is_active).length }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Enhanced Action Toolbar -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-flex-1">
            <!-- Search with Enhanced Design -->
            <div class="tw-relative tw-flex-1 tw-min-w-[250px] tw-max-w-[400px]">
              <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-4 tw-flex tw-items-center tw-pointer-events-none">
                <i class="pi pi-search tw-text-slate-400 tw-text-lg"></i>
              </div>
              <InputText
                v-model="searchQuery"
                placeholder="Search by name, description, service..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all tw-duration-200 tw-bg-slate-50/50 hover:tw-bg-white"
              />
              <div v-if="searchQuery" class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center">
                <Button
                  @click="clearSearch"
                  icon="pi pi-times"
                  class="p-button-text p-button-sm p-button-rounded tw-text-slate-400 hover:tw-text-slate-600"
                  v-tooltip.top="'Clear search'"
                />
              </div>
            </div>

            <!-- Quick Filter Buttons -->
            <div class="tw-flex tw-gap-2">
              <Button
                @click="setQuickFilter(true)"
                :class="selectedStatus === true ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                class="p-button-sm tw-rounded-xl"
                label="Active"
              />
              <Button
                @click="setQuickFilter(false)"
                :class="selectedStatus === false ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                class="p-button-sm tw-rounded-xl"
                label="Inactive"
              />
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button 
              @click="refreshSpecializations"
              icon="pi pi-refresh"
              class="p-button-outlined p-button-secondary p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
              v-tooltip.top="'Refresh data'"
              :loading="isLoading"
            />
            <Button 
              @click="exportSpecializations"
              icon="pi pi-download"
              label="Export"
              class="p-button-outlined p-button-info p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
            />
            <Button 
              @click="addNewSpecialization"
              icon="pi pi-plus"
              label="New Specialization"
              class="p-button-primary p-button-lg tw-rounded-xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200 tw-transform hover:tw-scale-105"
            />
          </div>
        </div>

        <!-- Import Section -->
        <div class="tw-mt-6 tw-pt-6 tw-border-t tw-border-slate-200">
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-4 tw-items-center">
            <div class="tw-relative tw-flex-1 tw-max-w-xs">
              <label for="fileUpload" class="tw-block tw-w-full tw-px-4 tw-py-2.5 tw-text-center tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border-2 tw-border-dashed tw-border-indigo-300 tw-rounded-lg tw-cursor-pointer hover:tw-bg-indigo-50 tw-transition-colors tw-duration-200">
                <i class="pi pi-upload tw-mr-2"></i>
                <span class="tw-text-sm tw-font-medium tw-text-slate-700">{{ file ? file.name : 'Choose CSV or XLSX' }}</span>
              </label>
              <input ref="fileInput" type="file" accept=".csv,.xlsx" @change="handleFileChange" id="fileUpload" class="tw-hidden" />
            </div>

            <Button
              label="Import Specializations"
              icon="pi pi-check"
              @click="uploadFile"
              :loading="loading"
              :disabled="loading || !file"
              class="p-button-success tw-rounded-lg tw-font-semibold"
              v-tooltip.top="'Upload specializations from file'"
            />

            <div v-if="successMessage" class="tw-flex-1 tw-bg-green-50 tw-text-green-800 tw-p-3 tw-rounded-lg tw-text-sm tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-check-circle"></i>{{ successMessage }}
            </div>
            <div v-if="errorMessage" class="tw-flex-1 tw-bg-red-50 tw-text-red-800 tw-p-3 tw-rounded-lg tw-text-sm tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-exclamation-circle"></i>{{ errorMessage }}
            </div>
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="searchQuery || selectedStatus !== null" class="tw-mt-4 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
          <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Active filters:</span>
          <Tag v-if="searchQuery" 
               :value="`Search: ${searchQuery}`" 
               severity="info" 
               class="tw-rounded-lg"
               @click="clearSearch" />
          <Tag v-if="selectedStatus !== null" 
               :value="`Status: ${selectedStatus ? 'Active' : 'Inactive'}`" 
               severity="info" 
               class="tw-rounded-lg"
               @click="selectedStatus = null" />
          <Button @click="clearAllFilters" 
                  label="Clear all" 
                  class="p-button-text p-button-sm tw-text-slate-500 hover:tw-text-slate-700" />
        </div>
      </div>

      <!-- Enhanced Data Table -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <DataTable 
          v-model:selection="selectedSpecializations"
          :value="filteredSpecializations"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} specializations"
          :rowsPerPageOptions="[10, 25, 50]"
          dataKey="id"
          :loading="isLoading"
          selectionMode="multiple"
          responsiveLayout="scroll"
          class="p-datatable-sm medical-table"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

    

          <!-- Name Column with Photo/Avatar -->
          <Column field="name" header="Specialization" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div v-if="data.photo_url" class="tw-flex-shrink-0">
                  <img 
                    :src="data.photo_url" 
                    :alt="`Photo for ${data.name}`"
                    class="tw-w-10 tw-h-10 tw-rounded-lg tw-object-cover tw-border tw-border-slate-200 tw-shadow-sm hover:tw-shadow-md tw-transition-shadow"
                    @error="handleImageError"
                    @load="handleImageLoad"
                    loading="lazy"
                  />
                </div>
                <Avatar
                  v-else
                  :label="getInitials(data.name)"
                  class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-font-semibold tw-shadow-md"
                  size="normal"
                  shape="circle"
                />
                <div>
                  <div class="tw-font-semibold tw-text-slate-900">{{ data.name }}</div>
                  <div class="tw-text-xs tw-text-slate-500">ID: {{ data.id }}</div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Service Column -->
          <Column field="service_name" header="Service" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-hospital tw-text-slate-400 tw-text-sm"></i>
                <span class="tw-text-slate-700">{{ data.service_name || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <!-- Description Column -->
          <Column field="description" header="Description" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-slate-900 tw-font-medium">
                {{ data.description || 'No description' }}
              </div>
            </template>
          </Column>

          <!-- Status Column -->
          <Column field="is_active" header="Status" :sortable="true">
            <template #body="{ data }">
              <Badge
                :value="data.is_active ? 'Active' : 'Inactive'"
                :severity="getStatusColor(data.is_active)"
                class="tw-font-semibold tw-rounded-lg"
              />
            </template>
          </Column>

          <!-- Created At Column -->
          <Column field="created_at" header="Created" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm">
                <div class="tw-font-semibold tw-text-slate-900">{{ formatDateOnly(data.created_at) }}</div>
                <div class="tw-text-xs tw-text-slate-500">{{ getRelativeTime(data.created_at) }}</div>
              </div>
            </template>
          </Column>

          <!-- Actions Column -->
          <Column header="Actions" headerStyle="width: 12rem">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Button
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-sm"
                  @click="editSpecialization(data)"
                  v-tooltip.top="'Edit specialization'"
                />
                <Button
                  icon="pi pi-eye"
                  class="p-button-rounded p-button-text p-button-info p-button-sm"
                  @click="viewSpecialization(data)"
                  v-tooltip.top="'View details'"
                />
                <Button
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-danger p-button-sm"
                  @click="deleteSpecialization(data)"
                  v-tooltip.top="'Delete specialization'"
                />
              </div>
            </template>
          </Column>

          <!-- Empty State -->
          <template #empty>
            <div class="tw-text-center tw-py-16 tw-px-8">
              <div class="tw-bg-gradient-to-br tw-from-slate-100 tw-to-slate-200 tw-w-24 tw-h-24 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                <i class="pi pi-stethoscope tw-text-4xl tw-text-slate-400"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">No specializations found</h3>
              <p class="tw-text-slate-500 tw-mb-6 tw-max-w-md tw-mx-auto">No specializations match your search criteria. Create a new specialization to get started.</p>
              <Button
                label="Add First Specialization"
                icon="pi pi-plus"
                @click="addNewSpecialization"
                class="p-button-success tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl"
              />
            </div>
          </template>

          <!-- Loading State -->
          <template #loading>
            <div class="tw-text-center tw-py-16 tw-px-8">
              <div class="tw-bg-gradient-to-br tw-from-indigo-100 tw-to-purple-100 tw-w-20 tw-h-20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                <ProgressSpinner
                  class="tw-w-8 tw-h-8"
                  strokeWidth="4"
                  fill="transparent"
                  animationDuration="1.5s"
                />
              </div>
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading specializations...</h3>
              <p class="tw-text-slate-500">Please wait while we fetch the specialization data</p>
            </div>
          </template>
        </DataTable>

        <!-- Bulk Actions -->
        <div v-if="selectedSpecializations.length > 0" class="tw-bg-blue-50 tw-border-t tw-border-blue-200 tw-p-4 tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-3">
            <i class="pi pi-info-circle tw-text-blue-600 tw-text-xl"></i>
            <span class="tw-text-blue-900 tw-font-medium">{{ selectedSpecializations.length }} specialization(s) selected</span>
          </div>
          <Button
            label="Delete Selected"
            icon="pi pi-trash"
            severity="danger"
            @click="bulkDelete"
            class="tw-rounded-lg tw-font-semibold"
            v-tooltip.top="'Delete selected specializations'"
          />
        </div>
      </div>
    </div>

    <!-- Add/Edit Specialization Modal -->
    <specializationModel :show-modal="isModalOpen" :spec-data="selectedSpecialization" @close="closeModal" @specialization-updated="refreshSpecializations" />

    <!-- Confirmation Dialog -->
    <ConfirmDialog />

    <!-- Toast -->
    <Toast />

    <!-- Floating Action Button -->
    <button 
      @click="addNewSpecialization"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Specialization'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>
  </div>
</template>


<style scoped>
/* Floating Action Button */
.fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 3.5rem;
  height: 3.5rem;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
  transition: all 0.3s ease;
  z-index: 1000;
}

.fab:hover {
  transform: scale(1.1);
  box-shadow: 0 15px 35px -5px rgba(59, 130, 246, 0.6);
}

/* Medical Table Styles */
.medical-table .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 2px solid #e2e8f0;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  padding: 1rem 1.5rem;
}

.medical-table .p-datatable-tbody > tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.medical-table .p-datatable-tbody > tr:hover {
  background-color: #f8fafc;
}

.medical-table .p-datatable-tbody > tr > td {
  padding: 1rem 1.5rem;
  vertical-align: middle;
  color: #4b5563;
}

/* Enhanced Pagination */
.medical-table .p-paginator {
  background: #ffffff;
  border-top: 1px solid #e5e7eb;
  padding: 1rem 1.5rem;
  justify-content: space-between;
}

.medical-table .p-paginator .p-paginator-pages .p-paginator-page {
  border-radius: 0.5rem;
  margin: 0 0.25rem;
  transition: all 0.2s;
}

.medical-table .p-paginator .p-paginator-pages .p-paginator-page:hover {
  background: #f3f4f6;
}

.medical-table .p-paginator .p-paginator-pages .p-paginator-page.p-highlight {
  background: #3b82f6;
  color: white;
}

/* Selection Styles */
.medical-table .p-datatable-tbody > tr.p-highlight {
  background: #eff6ff;
  color: #1e40af;
}

.medical-table .p-checkbox .p-checkbox-box {
  border-radius: 0.375rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .fab {
    bottom: 1rem;
    right: 1rem;
    width: 3rem;
    height: 3rem;
  }

  .medical-table .p-datatable-thead > tr > th,
  .medical-table .p-datatable-tbody > tr > td {
    padding: 0.75rem;
  }

  .medical-table .p-paginator {
    padding: 0.75rem;
    flex-direction: column;
    gap: 1rem;
  }
}

/* Loading and Empty States */
.medical-table .p-datatable-loading-overlay {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(4px);
}

/* Custom scrollbar for DataTable */
.medical-table .p-datatable-wrapper::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.medical-table .p-datatable-wrapper::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.medical-table .p-datatable-wrapper::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.medical-table .p-datatable-wrapper::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Enhanced Badge Styles */
.p-badge {
  font-weight: 600;
  border-radius: 0.5rem;
  padding: 0.25rem 0.75rem;
}

/* Enhanced Button Styles */
.p-button {
  border-radius: 0.75rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.p-button:hover {
  transform: translateY(-1px);
}

.p-button.p-button-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border: none;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
}

.p-button.p-button-primary:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
}

/* Input Styles */
.p-inputtext {
  border-radius: 0.75rem;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease;
}

.p-inputtext:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Tag Styles */
.p-tag {
  border-radius: 0.5rem;
  font-weight: 500;
}

/* Tooltip Styles */
.p-tooltip {
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Progress Spinner */
.p-progress-spinner {
  width: 3rem;
  height: 3rem;
}

/* Avatar Styles */
.p-avatar {
  border: 2px solid #e5e7eb;
}

/* Confirm Dialog */
.p-confirm-dialog {
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Toast */
.p-toast {
  border-radius: 0.75rem;
}

.p-toast .p-toast-message {
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>