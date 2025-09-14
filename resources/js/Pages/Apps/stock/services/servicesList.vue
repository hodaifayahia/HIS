<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-100 tw-p-6">
    <!-- Toast Notifications -->
    <Toast />

    <!-- Success Notification -->
    <div v-if="submitSuccess" class="tw-fixed tw-top-4 tw-right-4 tw-bg-green-500 tw-text-white tw-px-6 tw-py-3 tw-rounded-lg tw-shadow-lg tw-z-50 tw-flex tw-items-center tw-gap-2 tw-animate-fade-in">
      <i class="fas fa-check-circle"></i>
      <span>Operation completed successfully!</span>
      <button @click="submitSuccess = false" class="tw-ml-2 tw-text-white hover:tw-text-green-200">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Error Notification -->
    <div v-if="submitError" class="tw-fixed tw-top-4 tw-right-4 tw-bg-red-500 tw-text-white tw-px-6 tw-py-3 tw-rounded-lg tw-shadow-lg tw-z-50 tw-flex tw-items-center tw-gap-2 tw-animate-fade-in">
      <i class="fas fa-exclamation-triangle"></i>
      <span>{{ submitError }}</span>
      <button @click="submitError = null" class="tw-ml-2 tw-text-white hover:tw-text-red-200">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Header Section -->
    <div class="tw-mb-8">
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <Button
                v-if="selectedService"
                @click="backToServices"
                icon="pi pi-arrow-left"
                label="Back to Services"
                class="p-button-secondary tw-rounded-lg tw-shadow-md"
              />
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-p-3 tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-rounded-xl tw-shadow-lg">
                  <i class="pi pi-cog tw-text-white tw-text-2xl"></i>
                </div>
                <div>
                  <h1 class="tw-m-0 tw-text-gray-800 tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-green-600 tw-to-emerald-600 tw-bg-clip-text tw-text-transparent">
                    {{ selectedService ? `${selectedService.name} - Stockages` : 'Services Management' }}
                  </h1>
                  <p class="tw-m-0 tw-text-gray-600 tw-text-sm tw-mt-1">
                    {{ selectedService ? `Manage stockages for ${selectedService.name}` : 'Manage your services and their stockages' }}
                  </p>
                </div>
              </div>
            </div>
            <div class="tw-flex tw-gap-4 tw-items-center">
              <div class="tw-relative">
                <InputText
                  v-model="searchQuery"
                  :placeholder="selectedService ? 'Search stockages...' : 'Search services...'"
                  class="tw-w-80 tw-pl-10 tw-pr-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-focus:border-green-500 tw-transition-all tw-duration-300"
                  @input="onSearchInput"
                />
                <i class="pi pi-search tw-absolute tw-left-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400"></i>
              </div>
              <Button
                @click="$router.push({ name: 'stock.stockages' })"
                icon="pi pi-warehouse"
                label="View All Stockages"
                class="p-button-primary tw-px-6 tw-py-3 tw-rounded-xl tw-shadow-lg tw-hover:shadow-xl tw-transition-all tw-duration-300"
              />
              <Button
                v-if="selectedService"
                @click="openAddStockageModal"
                icon="pi pi-plus"
                label="Add Stockage"
                class="p-button-success tw-px-6 tw-py-3 tw-rounded-xl tw-shadow-lg tw-hover:shadow-xl tw-transition-all tw-duration-300"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Services List View -->
    <Card v-if="!selectedService" class="tw-shadow-xl tw-border-0 tw-bg-white/90 tw-backdrop-blur-sm">
      <template #content>
        <DataTable
          :value="filteredServices"
          :loading="loading"
          :paginator="true"
          :rows="10"
          :totalRecords="services.length"
          class="p-datatable-sm tw-rounded-xl"
          stripedRows
          showGridlines
          responsiveLayout="scroll"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[5, 10, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} services"
        >
          <template #loading>
            <div class="tw-flex tw-justify-center tw-items-center tw-py-12">
              <ProgressSpinner class="tw-mr-3" />
              <span class="tw-text-gray-600 tw-font-medium">Loading services...</span>
            </div>
          </template>

          <template #empty>
            <div class="tw-text-center tw-py-16">
              <i class="pi pi-cog tw-text-6xl tw-mb-4 tw-text-gray-300"></i>
              <p class="tw-text-xl tw-m-0 tw-text-gray-500 tw-font-medium">No services found</p>
              <p class="tw-text-sm tw-text-gray-400 tw-mt-2">Try adjusting your search or add a new service</p>
            </div>
          </template>

          <Column field="id" header="ID" style="width: 80px" sortable class="tw-font-semibold">
            <template #body="slotProps">
              <span class="tw-bg-gray-100 tw-text-gray-800 tw-px-2 tw-py-1 tw-rounded-lg tw-text-sm tw-font-mono">
                {{ slotProps.data.id }}
              </span>
            </template>
          </Column>
          <Column field="name" header="Name" style="min-width: 200px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-green-100 tw-rounded-lg">
                  <i class="pi pi-cog tw-text-green-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.name }}</div>
                  <div v-if="slotProps.data.description" class="tw-text-xs tw-text-gray-500 tw-mt-1">
                    {{ slotProps.data.description }}
                  </div>
                </div>
              </div>
            </template>
          </Column>
          <Column field="description" header="Description" style="min-width: 250px">
            <template #body="slotProps">
              <div class="tw-text-gray-700">
                {{ slotProps.data.description || 'No description available' }}
              </div>
            </template>
          </Column>
          <Column header="Stockages Count" style="min-width: 150px">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-justify-center">
                <div class="stockage-count-badge" :class="getBadgeClass(slotProps.data.stockages_count || 0)">
                  <i class="pi pi-warehouse tw-mr-2"></i>
                  <span class="count-value">{{ slotProps.data.stockages_count || 0 }}</span>
                </div>
              </div>
            </template>
          </Column>
          <Column header="Actions" style="min-width: 200px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-eye"
                  class="p-button-info p-button-sm tw-rounded-lg"
                  @click="viewServiceStockages(slotProps.data)"
                  v-tooltip.top="'View Stockages'"
                />
                <Button
                  icon="pi pi-box"
                  class="p-button-success p-button-sm tw-rounded-lg"
                  @click="viewServiceStock(slotProps.data)"
                  v-tooltip.top="'View All Stock'"
                />
                <Button
                  icon="pi pi-cog"
                  class="p-button-primary p-button-sm tw-rounded-lg"
                  @click="openStockManagement(slotProps.data)"
                  v-tooltip.top="'Stock Management'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Service Stockages View -->
    <Card v-else class="tw-shadow-xl tw-border-0 tw-bg-white/90 tw-backdrop-blur-sm">
      <template #content>
        <DataTable
          :value="filteredServiceStockages"
          :loading="loadingStockages"
          :paginator="true"
          :rows="10"
          :totalRecords="serviceStockages.length"
          class="p-datatable-sm tw-rounded-xl"
          stripedRows
          showGridlines
          responsiveLayout="scroll"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[5, 10, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} stockages"
        >
          <template #loading>
            <div class="tw-flex tw-justify-center tw-items-center tw-py-12">
              <ProgressSpinner class="tw-mr-3" />
              <span class="tw-text-gray-600 tw-font-medium">Loading stockages...</span>
            </div>
          </template>

          <template #empty>
            <div class="tw-text-center tw-py-16">
              <i class="pi pi-warehouse tw-text-6xl tw-mb-4 tw-text-gray-300"></i>
              <p class="tw-text-xl tw-m-0 tw-text-gray-500 tw-font-medium">No stockages found for this service</p>
              <p class="tw-text-sm tw-text-gray-400 tw-mt-2">Add a new stockage to get started</p>
            </div>
          </template>

          <Column field="id" header="ID" style="width: 80px" sortable class="tw-font-semibold">
            <template #body="slotProps">
              <span class="tw-bg-gray-100 tw-text-gray-800 tw-px-2 tw-py-1 tw-rounded-lg tw-text-sm tw-font-mono">
                {{ slotProps.data.id }}
              </span>
            </template>
          </Column>
          <Column field="name" header="Name" style="min-width: 200px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                  <i class="pi pi-box tw-text-blue-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.name }}</div>
                  <div v-if="slotProps.data.temperature_controlled" class="tw-flex tw-items-center tw-gap-1 tw-mt-1">
                    <i class="pi pi-snowflake tw-text-blue-500 tw-text-sm"></i>
                    <span class="tw-text-xs tw-text-blue-600 tw-font-medium">Temperature Controlled</span>
                  </div>
                </div>
              </div>
            </template>
          </Column>
          <Column field="location" header="Location" style="min-width: 150px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-map-marker tw-text-gray-400"></i>
                <span class="tw-text-gray-700">{{ slotProps.data.location }}</span>
              </div>
            </template>
          </Column>
          <Column field="type" header="Type" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <Tag :value="getTypeLabel(slotProps.data.type)" :severity="getTypeSeverity(slotProps.data.type)" class="tw-font-medium" />
            </template>
          </Column>
          <Column field="capacity" header="Capacity" style="min-width: 100px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-chart-bar tw-text-gray-400"></i>
                <span v-if="slotProps.data.capacity" class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.capacity }}</span>
                <span v-else class="tw-text-gray-500 tw-italic">Not set</span>
              </div>
            </template>
          </Column>
          <Column field="status" header="Status" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <Tag :value="getStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" class="tw-font-medium" />
            </template>
          </Column>
          <Column header="Actions" style="min-width: 200px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-eye"
                  class="p-button-info p-button-sm tw-rounded-lg"
                  @click="$router.push({ name: 'stock.stockages.detail', params: { id: slotProps.data.id } })"
                  v-tooltip.top="'View Details'"
                />
                <Button
                  icon="pi pi-box"
                  class="p-button-success p-button-sm tw-rounded-lg"
                  @click="viewStock(slotProps.data)"
                  v-tooltip.top="'View Stock'"
                />
                <Button
                  icon="pi pi-pencil"
                  class="p-button-warning p-button-sm tw-rounded-lg"
                  @click="$router.push({ name: 'stock.stockages.detail', params: { id: slotProps.data.id } })"
                  v-tooltip.top="'Edit'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Add Stockage Modal -->
    <AddStockageModal
      :show-modal="showAddStockageModal"
      :pre-selected-service-id="selectedService ? selectedService.id : null"
      :title="selectedService ? `Add Stockage to ${selectedService.name}` : 'Add New Stockage'"
      @close="closeAddStockageModal"
      @success="onStockageCreated"
    />
  </div>
</template>

<script>
import axios from 'axios';
import AddStockageModal from '../../../../Components/Apps/stock/AddStockageModal.vue';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';

export default {
  name: 'ServicesList',
  components: {
    AddStockageModal,
    Card,
    Button,
    InputText,
    DataTable,
    Column,
    Tag,
    Badge,
    ProgressSpinner,
    Toast
  },
  data() {
    return {
      searchQuery: '',
      selectedService: null,
      submitSuccess: false,
      submitError: null,
      services: [],
      serviceStockages: [],
      filteredServices: [],
      filteredServiceStockages: [],
      loading: false,
      loadingStockages: false,
      error: null,
      stockageError: null,
      searchTimeout: null,
      showAddStockageModal: false
    }
  },
  mounted() {
    this.fetchServices();
    // Initialize ToastService if not already initialized
    if (!this.$toast) {
      this.$toast = ToastService;
    }
  },
  methods: {
    async fetchServices() {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/api/services');
        const resData = response.data;
        if (resData.status === 'success' && Array.isArray(resData.data)) {
          this.services = resData.data;
        } else if (Array.isArray(resData)) {
          this.services = resData;
        }
        this.filterServices();
      } catch (error) {
        this.error = 'Failed to load services';
      } finally {
        this.loading = false;
      }
    },

    async fetchServiceStockages() {
      if (!this.selectedService) return;

      this.loadingStockages = true;
      this.stockageError = null;
      try {
        const response = await axios.get(`/api/stockages?service_id=${this.selectedService.id}`);
        if (response.data.success) {
          this.serviceStockages = response.data.data;
          this.filterServiceStockages();
        }
      } catch (error) {
        this.stockageError = 'Failed to load stockages';
      } finally {
        this.loadingStockages = false;
      }
    },

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        if (this.selectedService) {
          this.filterServiceStockages();
        } else {
          this.filterServices();
        }
      }, 300);
    },

    filterServices() {
      let filtered = [...this.services];

      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(service =>
          service.name.toLowerCase().includes(query) ||
          (service.description && service.description.toLowerCase().includes(query))
        );
      }

      this.filteredServices = filtered;
    },

    filterServiceStockages() {
      let filtered = [...this.serviceStockages];

      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(stockage =>
          stockage.name.toLowerCase().includes(query) ||
          stockage.description?.toLowerCase().includes(query) ||
          stockage.location.toLowerCase().includes(query)
        );
      }

      this.filteredServiceStockages = filtered;
    },

    viewServiceStockages(service) {
      this.selectedService = service;
      this.fetchServiceStockages();
    },

    viewServiceStock(service) {
      this.$router.push({ name: 'stock.services.stock', params: { id: service.id } });
    },

    openStockManagement(service) {
      this.$router.push({ name: 'stock.services.management', params: { id: service.id } });
    },

    backToServices() {
      this.selectedService = null;
      this.serviceStockages = [];
      this.filteredServiceStockages = [];
      this.searchQuery = '';
      this.stockageError = null;
    },

    openAddStockageModal() {
      this.showAddStockageModal = true;
    },

    closeAddStockageModal() {
      this.showAddStockageModal = false;
    },

    onStockageCreated(createdStockage) {
      // Refresh the service stockages to show the new one
      this.fetchServiceStockages();
      this.submitSuccess = true;
      setTimeout(() => {
        this.submitSuccess = false;
      }, 3000);
    },

    viewStock(stockage) {
      this.$router.push({ name: 'stock.stockages.stock', params: { id: stockage.id } });
    },

    getBadgeClass(count) {
      if (count === 0) return 'count-zero';
      if (count <= 2) return 'count-low';
      if (count <= 5) return 'count-medium';
      return 'count-high';
    },

    getTypeClass(type) {
      const classes = {
        'warehouse': 'tw-bg-blue-100 tw-text-blue-800',
        'pharmacy': 'tw-bg-green-100 tw-text-green-800',
        'laboratory': 'tw-bg-purple-100 tw-text-purple-800',
        'emergency': 'tw-bg-red-100 tw-text-red-800',
        'storage_room': 'tw-bg-yellow-100 tw-text-yellow-800',
        'cold_room': 'tw-bg-cyan-100 tw-text-cyan-800'
      };
      return classes[type] || 'tw-bg-gray-100 tw-text-gray-800';
    },

    getTypeLabel(type) {
      const labels = {
        'warehouse': 'Warehouse',
        'pharmacy': 'Pharmacy',
        'laboratory': 'Laboratory',
        'emergency': 'Emergency',
        'storage_room': 'Storage Room',
        'cold_room': 'Cold Room'
      };
      return labels[type] || type;
    },

    getStatusClass(status) {
      const classes = {
        'active': 'tw-text-green-500',
        'inactive': 'tw-text-gray-500',
        'maintenance': 'tw-text-yellow-500',
        'under_construction': 'tw-text-orange-500'
      };
      return classes[status] || 'tw-text-gray-500';
    },

    getStatusRowClass(status) {
      const classes = {
        'active': '',
        'inactive': 'tw-bg-gray-50',
        'maintenance': 'tw-bg-yellow-50',
        'under_construction': 'tw-bg-orange-50'
      };
      return classes[status] || '';
    },

    getStatusLabel(status) {
      const labels = {
        'active': 'Active',
        'inactive': 'Inactive',
        'maintenance': 'Maintenance',
        'under_construction': 'Under Construction'
      };
      return labels[status] || status;
    },

    // PrimeVue helper methods
    getTypeSeverity(type) {
      const severities = {
        'warehouse': 'info',
        'pharmacy': 'success',
        'laboratory': 'warning',
        'emergency': 'danger',
        'storage_room': 'secondary',
        'cold_room': 'info'
      };
      return severities[type] || 'info';
    },

    getStatusSeverity(status) {
      const severities = {
        'active': 'success',
        'inactive': 'secondary',
        'maintenance': 'warning',
        'under_construction': 'info'
      };
      return severities[status] || 'info';
    }
  }
}
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

.tw-animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

/* Enhanced DataTable styling */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  color: #374151;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  padding: 1rem 1.5rem;
  border-bottom: 2px solid #cbd5e1;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  background-color: #ffffff;
  transition: all 0.2s ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8fafc;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  color: #4b5563;
}

/* Enhanced Card styling */
:deep(.p-card) {
  border-radius: 1rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Enhanced Button styling */
:deep(.p-button) {
  border-radius: 0.5rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

:deep(.p-button.p-button-sm) {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

/* Enhanced Tag and Badge styling */
:deep(.p-tag), :deep(.p-badge) {
  font-weight: 600;
  padding: 0.375rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
}

/* Custom Stockage Count Badge */
.stockage-count-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 2rem;
  font-weight: 600;
  font-size: 0.875rem;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  border: 2px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stockage-count-badge::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.6s ease;
}

.stockage-count-badge:hover::before {
  left: 100%;
}

.stockage-count-badge:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
}

.stockage-count-badge .pi {
  font-size: 1rem;
  opacity: 0.9;
}

.stockage-count-badge .count-value {
  font-weight: 700;
  font-size: 1rem;
  margin-left: 0.25rem;
}

/* Alternative badge styles for different counts */
.stockage-count-badge.count-zero {
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
  color: #64748b;
  box-shadow: 0 4px 15px rgba(226, 232, 240, 0.4);
}

.stockage-count-badge.count-low {
  background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
  color: #c53030;
  box-shadow: 0 4px 15px rgba(254, 215, 215, 0.4);
}

.stockage-count-badge.count-medium {
  background: linear-gradient(135deg, #fef5e7 0%, #fdeaa7 100%);
  color: #d69e2e;
  box-shadow: 0 4px 15px rgba(254, 245, 231, 0.4);
}

.stockage-count-badge.count-high {
  background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
  color: #276749;
  box-shadow: 0 4px 15px rgba(198, 246, 213, 0.4);
}

/* Enhanced Input styling */
:deep(.p-inputtext) {
  border-radius: 0.5rem;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease;
  background: #ffffff;
}

:deep(.p-inputtext:focus) {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

/* Enhanced Paginator styling */
:deep(.p-paginator) {
  border-radius: 0.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #e5e7eb;
  margin-top: 1rem;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page) {
  border-radius: 0.375rem;
  margin: 0 0.25rem;
  transition: all 0.3s ease;
  font-weight: 500;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page:hover) {
  background: #f1f5f9;
  transform: scale(1.05);
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Loading and Empty States */
.loading-state, .empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 0.75rem;
  margin: 1rem 0;
}

.loading-state .pi, .empty-state .pi {
  font-size: 4rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1.5rem;
}

.loading-state p, .empty-state p {
  color: #6b7280;
  font-size: 1.125rem;
  font-weight: 500;
  margin: 0;
}

/* Header styling */
.tw-flex.tw-justify-between.tw-items-center.tw-mb-5 .tw-flex.tw-justify-between.tw-items-center {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  padding: 2rem;
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.tw-text-gray-800.tw-text-3xl.tw-font-semibold {
  background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 800;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-p-6 {
    padding: 1rem;
  }

  .tw-flex.tw-justify-between.tw-items-center {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .tw-flex.tw-gap-4.tw-items-center {
    flex-direction: column;
    gap: 1rem;
  }

  .tw-w-80 {
    width: 100% !important;
  }

  :deep(.p-card .p-card-content) {
    padding: 1rem;
  }

  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem 0.5rem;
    font-size: 0.875rem;
  }

  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 1rem 0.5rem;
    font-size: 0.75rem;
  }

  .tw-text-gray-800.tw-text-3xl.tw-font-semibold {
    font-size: 1.5rem !important;
  }

  :deep(.p-button) {
    width: 100%;
    justify-content: center;
  }

  :deep(.p-inputtext) {
    width: 100%;
  }
}

@media (max-width: 640px) {
  :deep(.p-datatable .p-datatable-tbody > tr > td),
  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.5rem 0.25rem;
    font-size: 0.75rem;
  }

  :deep(.p-button.p-button-sm) {
    padding: 0.5rem;
    font-size: 0.75rem;
  }

  :deep(.p-tag), :deep(.p-badge) {
    font-size: 0.625rem;
    padding: 0.25rem 0.5rem;
  }

  .stockage-count-badge {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
  }

  .stockage-count-badge .pi {
    font-size: 0.875rem;
  }

  .stockage-count-badge .count-value {
    font-size: 0.875rem;
  }
}
</style>
