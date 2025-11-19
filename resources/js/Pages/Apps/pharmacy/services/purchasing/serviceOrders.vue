<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Header -->
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
      <template #content>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div class="tw-flex tw-items-center tw-gap-4">
            <Button
              @click="goBack"
              icon="pi pi-arrow-left"
              label="Back to Services"
              class="p-button-secondary tw-rounded-lg tw-shadow-md"
            />
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-mb-2">
                <i class="pi pi-shopping-cart tw-mr-3"></i>
                {{ serviceName }} - Service Orders
              </h1>
              <p class="tw-text-blue-100 tw-text-lg">
                Manage purchasing demands for {{ serviceName }}
              </p>
            </div>
          </div>
          <div class="tw-text-right">
            <div class="tw-text-2xl tw-font-bold">{{ stats.total_demands || 0 }}</div>
            <div class="tw-text-blue-100">Total Orders</div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Stats Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.draft_demands || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Draft Orders</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-yellow-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-file-edit tw-text-yellow-600 tw-text-xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.sent_demands || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Sent Orders</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-send tw-text-blue-600 tw-text-xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.approved_demands || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Approved Orders</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-check-circle tw-text-green-600 tw-text-xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.total_items || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Total Items</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-purple-600 tw-text-xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <Card class="tw-shadow-xl">
      <template #title>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div class="tw-flex tw-items-center">
            <i class="pi pi-list tw-mr-3 tw-text-blue-600"></i>
            <span class="tw-text-xl tw-font-semibold">Service Orders</span>
          </div>
          <Button 
            label="Create New Order" 
            icon="pi pi-plus" 
            class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-border-0"
            @click="createNewOrder"
          />
        </div>
      </template>

      <template #content>
        <!-- Filters -->
        <div class="tw-mb-6 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
            <span class="p-input-icon-left">
              <i class="pi pi-search"></i>
              <InputText 
                v-model="filters.global.value" 
                placeholder="Search orders..." 
                class="tw-w-full"
                @input="onSearchChange"
              />
            </span>
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
            <Dropdown
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Status"
              class="tw-w-full"
              @change="loadDemands"
            />
          </div>

          <div class="tw-flex tw-flex-col tw-justify-end">
            <Button 
              label="Clear Filters" 
              icon="pi pi-times" 
              class="p-button-outlined"
              @click="clearFilters"
            />
          </div>
        </div>

        <!-- Data Table -->
        <DataTable 
          :value="demands" 
          :loading="loading"
          :filters="filters"
          :globalFilterFields="['demand_code', 'notes']"
          paginator 
          :rows="10" 
          :rowsPerPageOptions="[5, 10, 20, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} orders"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          responsiveLayout="scroll"
          stripedRows
          class="tw-mt-4"
        >
          <template #empty>
            <div class="tw-text-center tw-py-12">
              <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
              <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No orders found</h3>
              <p class="tw-text-gray-500 tw-mb-6">Get started by creating your first order for this service.</p>
              <Button 
                label="Create Order" 
                icon="pi pi-plus" 
                @click="createNewOrder"
              />
            </div>
          </template>

          <Column field="demand_code" header="Order Code" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-hashtag tw-mr-2 tw-text-gray-400"></i>
                <span class="tw-font-mono tw-font-medium">{{ slotProps.data.demand_code }}</span>
              </div>
            </template>
          </Column>

          <Column field="expected_date" header="Expected Date" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-calendar tw-mr-2 tw-text-green-500"></i>
                <span>{{ formatDate(slotProps.data.expected_date) }}</span>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" sortable>
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.status" 
                :severity="getStatusSeverity(slotProps.data.status)"
                class="tw-font-medium"
              />
            </template>
          </Column>

          <Column field="items" header="Items" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                <span class="tw-font-medium">{{ slotProps.data.items?.length || 0 }} items</span>
              </div>
            </template>
          </Column>

          <Column field="created_at" header="Created" sortable>
            <template #body="slotProps">
              <div class="tw-text-sm tw-text-gray-600">
                {{ formatDateTime(slotProps.data.created_at) }}
              </div>
            </template>
          </Column>

          <Column header="Actions" :exportable="false">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button 
                  icon="pi pi-eye" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-blue-600"
                  v-tooltip.top="'View Details'"
                  @click="viewDemand(slotProps.data)"
                />
                <Button 
                  v-if="canEditDemand(slotProps.data)"
                  icon="pi pi-pencil" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-orange-600"
                  v-tooltip.top="'Edit Draft'"
                  @click="editDemand(slotProps.data)"
                />
                <Button 
                  v-if="canSendDemand(slotProps.data)"
                  icon="pi pi-send" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-green-600"
                  v-tooltip.top="'Send Order'"
                  @click="sendDemand(slotProps.data)"
                />
                <Button 
                  v-if="canDeleteDemand(slotProps.data)"
                  icon="pi pi-trash" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-red-600"
                  v-tooltip.top="'Delete Draft'"
                  @click="deleteDemand(slotProps.data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Demand Details Dialog -->
    <Dialog :visible="showDetailsDialog" @update:visible="showDetailsDialog = $event" modal :header="`Order Details: ${selectedDemand?.demand_code}`" :style="{width: '70rem'}">
      <div v-if="selectedDemand" class="tw-space-y-6">
        <!-- Basic Information -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <div class="tw-space-y-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Order Code</label>
              <div class="tw-text-lg tw-font-mono tw-font-bold tw-text-gray-900">{{ selectedDemand.demand_code }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Service</label>
              <div class="tw-text-lg tw-text-gray-900">{{ selectedDemand.service?.name }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Status</label>
              <div>
                <Tag 
                  :value="selectedDemand.status" 
                  :severity="getStatusSeverity(selectedDemand.status)"
                  class="tw-font-medium"
                />
              </div>
            </div>
          </div>
          <div class="tw-space-y-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Expected Date</label>
              <div class="tw-text-lg tw-text-gray-900">{{ formatDate(selectedDemand.expected_date) }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Created</label>
              <div class="tw-text-lg tw-text-gray-900">{{ formatDateTime(selectedDemand.created_at) }}</div>
            </div>
            <div v-if="selectedDemand.notes">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Notes</label>
              <div class="tw-text-gray-900">{{ selectedDemand.notes }}</div>
            </div>
          </div>
        </div>

        <!-- Items -->
        <div class="tw-border-t tw-pt-6">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Items ({{ selectedDemand.items?.length || 0 }})</h3>
          
          <DataTable 
            :value="selectedDemand.items || []" 
            responsiveLayout="scroll"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8">
                <i class="pi pi-box tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                <p class="tw-text-gray-500">No items in this order.</p>
              </div>
            </template>

            <Column field="pharmacy_product.name" header="Product">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center">
                  <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.data.pharmacy_product?.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.pharmacy_product?.product_code }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <span class="tw-font-medium">{{ slotProps.data.quantity }}</span>
                  <span v-if="slotProps.data.quantity_by_box" class="tw-text-xs tw-bg-blue-100 tw-text-blue-800 tw-px-2 tw-py-1 tw-rounded-full">
                    By Box
                  </span>
                  <span v-else class="tw-text-xs tw-bg-gray-100 tw-text-gray-800 tw-px-2 tw-py-1 tw-rounded-full">
                    By Unit
                  </span>
                </div>
              </template>
            </Column>

            <Column field="unit_price" header="Unit Price">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-medium">${{ slotProps.data.unit_price }}</span>
                <span v-else class="tw-text-gray-400">Not set</span>
              </template>
            </Column>

            <Column field="total_price" header="Total">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-bold tw-text-green-600">
                  ${{ (slotProps.data.quantity * slotProps.data.unit_price).toFixed(2) }}
                </span>
                <span v-else class="tw-text-gray-400">-</span>
              </template>
            </Column>

            <Column field="status" header="Status">
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.status" 
                  :severity="getStatusSeverity(slotProps.data.status)"
                  class="tw-font-medium"
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Close" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="showDetailsDialog = false"
          />
          <Button 
            v-if="canEditDemand(selectedDemand)"
            label="Edit Order" 
            icon="pi pi-pencil" 
            @click="editDemandFromDetails"
          />
          <Button 
            v-if="canSendDemand(selectedDemand)"
            label="Send Order" 
            icon="pi pi-send" 
            @click="sendDemand(selectedDemand)"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

export default {
  name: 'PharmacyServiceOrders',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    Dropdown,
    Tag,
    Card,
    Toast,
    ConfirmDialog
  },
  props: {
    serviceId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      serviceName: '',
      demands: [],
      stats: {},
      loading: false,

      showDetailsDialog: false,
      selectedDemand: null,

      filters: {
        global: { value: null, matchMode: 'contains' }
      },
      statusFilter: null,
      statusOptions: [
        { label: 'All Status', value: null },
        { label: 'Draft', value: 'draft' },
        { label: 'Sent', value: 'sent' },
        { label: 'Approved', value: 'approved' },
        { label: 'Rejected', value: 'rejected' },
        { label: 'Completed', value: 'completed' }
      ]
    };
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.loadInitialData();
  },
  methods: {
    async loadInitialData() {
      this.loading = true;
      try {
        await Promise.all([
          this.loadServiceInfo(),
          this.loadDemands(),
          this.loadStats()
        ]);
      } catch (error) {
        console.error('Failed to load initial data:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load initial data',
          life: 3000
        });
      } finally {
        this.loading = false;
      }
    },

    async loadServiceInfo() {
      try {
        const response = await axios.get(`/api/services/${this.serviceId}`);
        this.serviceName = response.data.data?.name || 'Unknown Service';
      } catch (error) {
        console.error('Failed to load service info:', error);
        this.serviceName = 'Service';
      }
    },

    async loadDemands() {
      try {
        const params = {
          service_id: this.serviceId
        };
        if (this.statusFilter) params.status = this.statusFilter;
        if (this.filters.global.value) params.search = this.filters.global.value;

        const response = await axios.get('/api/pharmacy/service-demands', { params });
        this.demands = response.data.data.data || [];
      } catch (error) {
        console.error('Failed to load demands:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load orders',
          life: 3000
        });
      }
    },

    async loadStats() {
      try {
        const response = await axios.get('/api/pharmacy/service-demands/meta/stats', {
          params: { service_id: this.serviceId }
        });
        this.stats = response.data.data || {};
      } catch (error) {
        console.error('Failed to load stats:', error);
      }
    },

    onSearchChange() {
      // Debounce search
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.loadDemands();
      }, 300);
    },

    clearFilters() {
      this.filters.global.value = null;
      this.statusFilter = null;
      this.loadDemands();
    },

    createNewOrder() {
      // Navigate to create page with pre-selected service
      this.$router.push({
        path: '/pharmacy/service-demands/create',
        query: { service_id: this.serviceId }
      });
    },

    viewDemand(demand) {
      this.selectedDemand = demand;
      this.showDetailsDialog = true;
    },

    editDemand(demand) {
      // Navigate to edit page
      this.$router.push({
        path: `/pharmacy/service-demands/edit/${demand.id}`
      });
    },

    editDemandFromDetails() {
      this.showDetailsDialog = false;
      this.editDemand(this.selectedDemand);
    },

    async sendDemand(demand) {
      this.confirm.require({
        message: `Are you sure you want to send order "${demand.demand_code}"? Once sent, you cannot edit it anymore.`,
        header: 'Send Order',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.post(`/api/pharmacy/service-demands/${demand.id}/send`);
            
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Order sent successfully',
              life: 3000
            });
            
            this.loadDemands();
            this.loadStats();
            
          } catch (error) {
            console.error('Error sending demand:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: error.response?.data?.message || 'Failed to send order',
              life: 3000
            });
          }
        }
      });
    },

    async deleteDemand(demand) {
      this.confirm.require({
        message: `Are you sure you want to delete order "${demand.demand_code}"? This action cannot be undone.`,
        header: 'Delete Order',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/pharmacy/service-demands/${demand.id}`);
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Order deleted successfully',
              life: 3000
            });
            this.loadDemands();
            this.loadStats();
          } catch (error) {
            console.error('Error deleting demand:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to delete order',
              life: 3000
            });
          }
        }
      });
    },

    goBack() {
      this.$router.push({ name: 'pharmacy.services' });
    },

    getStatusSeverity(status) {
      const severities = {
        draft: 'warning',
        sent: 'info',
        approved: 'success',
        rejected: 'danger',
        completed: 'success',
        pending: 'warning'
      };
      return severities[status] || 'info';
    },

    canEditDemand(demand) {
      return demand && demand.status === 'draft';
    },

    canSendDemand(demand) {
      return demand && demand.status === 'draft';
    },

    canDeleteDemand(demand) {
      return demand && demand.status === 'draft';
    },

    formatDate(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleDateString();
    },

    formatDateTime(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleString();
    }
  }
};
</script>

<style scoped>
.tw-shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: rgba(59, 130, 246, 0.05);
}

.p-button {
  transition: all 0.2s ease-in-out;
}

.p-button:hover {
  transform: translateY(-1px);
}

.p-tag {
  font-weight: 500;
  text-transform: capitalize;
}

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

.p-inputtext:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  border-color: rgb(59, 130, 246);
}

@media (max-width: 768px) {
  .tw-grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .tw-grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>
