<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Header -->
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
      <template #content>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">
              <i class="pi pi-file-edit tw-mr-3"></i>
              Facture Proforma Management
            </h1>
            <p class="tw-text-blue-100 tw-text-lg">
              Manage purchase orders and proforma invoices
            </p>
          </div>
          <div class="tw-text-right">
            <div class="tw-text-2xl tw-font-bold">{{ stats.total_proformas || 0 }}</div>
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
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.draft_proformas || 0 }}</div>
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
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.sent_proformas || 0 }}</div>
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
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.approved_proformas || 0 }}</div>
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
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ formatCurrency(stats.total_amount || 0) }}</div>
              <div class="tw-text-sm tw-text-gray-600">Total Value</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-dollar tw-text-purple-600 tw-text-xl"></i>
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
            <span class="tw-text-xl tw-font-semibold">Orders List</span>
          </div>
          <div class="tw-flex tw-gap-3">
            <Button 
              label="Create from Service Demands" 
              icon="pi pi-plus-circle" 
              class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-border-0"
              @click="showCreateFromDemandsDialog = true"
            />
            <Button 
              label="New Order" 
              icon="pi pi-plus" 
              class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-border-0"
              @click="createNewOrder"
            />
          </div>
        </div>
      </template>

      <template #content>
        <!-- Filters -->
        <div class="tw-mb-6 tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4">
          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
            <span class="p-input-icon-left">
              <i class="pi pi-search"></i>
              <InputText 
                v-model="filters.global.value" 
                placeholder="Search orders..." 
                class="tw-w-full"
                @input="debouncedSearch"
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
              @change="loadProformas"
            />
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Supplier</label>
            <Dropdown
              v-model="supplierFilter"
              :options="suppliers"
              optionLabel="name"
              optionValue="id"
              placeholder="All Suppliers"
              class="tw-w-full"
              :filter="true"
              filterPlaceholder="Search suppliers..."
              @change="loadProformas"
            />
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Date Range</label>
            <Calendar
              v-model="dateRange"
              placeholder="Select date range"
              class="tw-w-full"
              selectionMode="range"
              showIcon
              dateFormat="yy-mm-dd"
              @date-select="loadProformas"
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
          :value="proformas" 
          :loading="loading"
          :filters="filters"
          :globalFilterFields="['proforma_code', 'service_demand.demand_code', 'fournisseur.name']"
          paginator 
          :rows="10" 
          :rowsPerPageOptions="[5, 10, 20, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} orders"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          responsiveLayout="scroll"
          stripedRows
          class="tw-mt-4"
          dataKey="id"
          v-model="selectedProformas"
          :metaKeySelection="false"
        >
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center">
              <div>
                <Checkbox v-model="selectAll" @change="onSelectAllChange" />
                <span class="tw-ml-2 tw-text-sm tw-text-gray-600">
                  {{ selectedProformas.length }} selected
                </span>
              </div>
              <div class="tw-flex tw-gap-2" v-if="selectedProformas.length > 0">
                <Button 
                  label="Bulk Action" 
                  icon="pi pi-cog" 
                  class="p-button-sm p-button-outlined"
                  @click="showBulkActionMenu"
                />
              </div>
            </div>
          </template>

          <template #empty>
            <div class="tw-text-center tw-py-12">
              <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
              <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No orders found</h3>
              <p class="tw-text-gray-500 tw-mb-6">Get started by creating your first order.</p>
              <Button 
                label="Create Order" 
                icon="pi pi-plus" 
                @click="createNewOrder"
              />
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

          <Column field="proforma_code" header="Order Code" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-hashtag tw-mr-2 tw-text-gray-400"></i>
                <span class="tw-font-mono tw-font-medium">{{ slotProps.data.proforma_code }}</span>
              </div>
            </template>
          </Column>

          <Column field="service_demand.demand_code" header="Service Demand" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-shopping-cart tw-mr-2 tw-text-blue-500"></i>
                <span>{{ slotProps.data.service_demand?.demand_code || 'Direct Order' }}</span>
              </div>
            </template>
          </Column>

          <Column field="fournisseur.name" header="Supplier" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-building tw-mr-2 tw-text-green-500"></i>
                <span>{{ slotProps.data.fournisseur?.name || 'Not Assigned' }}</span>
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

          <Column field="products" header="Products" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                <span class="tw-font-medium">{{ slotProps.data.products?.length || 0 }} items</span>
              </div>
            </template>
          </Column>

          <Column field="total_amount" header="Total Amount" sortable>
            <template #body="slotProps">
              <span class="tw-font-bold tw-text-green-600">
                {{ formatCurrency(slotProps.data.total_amount || 0) }}
              </span>
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
                  @click="viewProforma(slotProps.data)"
                />
                <Button 
                  v-if="canEditProforma(slotProps.data)"
                  icon="pi pi-pencil" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-orange-600"
                  v-tooltip.top="'Edit Order'"
                  @click="editProforma(slotProps.data)"
                />
                <Button 
                  icon="pi pi-download" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-green-600"
                  v-tooltip.top="'Download PDF'"
                  @click="downloadProforma(slotProps.data)"
                />
                <Button 
                  v-if="canDeleteProforma(slotProps.data)"
                  icon="pi pi-trash" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-red-600"
                  v-tooltip.top="'Delete Order'"
                  @click="deleteProforma(slotProps.data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Create from Service Demands Dialog -->
    <Dialog 
      :visible="showCreateFromDemandsDialog" 
      @update:visible="showCreateFromDemandsDialog = $event" 
      modal 
      header="Create Orders from Service Demands" 
      :style="{width: '80rem'}"
    >
      <div class="tw-space-y-6">
        <div class="tw-mb-4">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">Select Service Demands</h3>
          <p class="tw-text-gray-600">Choose approved service demands to create proforma invoices for suppliers.</p>
        </div>

        <!-- Service Demands List -->
        <DataTable 
          :value="availableServiceDemands" 
          :loading="loadingServiceDemands"
          v-model="selectedServiceDemands"
          dataKey="id"
          :metaKeySelection="false"
          paginator 
          :rows="10"
          stripedRows
        >
          <template #empty>
            <div class="tw-text-center tw-py-8">
              <i class="pi pi-inbox tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
              <p class="tw-text-gray-500">No approved service demands found.</p>
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

          <Column field="demand_code" header="Demand Code">
            <template #body="slotProps">
              <span class="tw-font-mono tw-font-medium">{{ slotProps.data.demand_code }}</span>
            </template>
          </Column>

          <Column field="service.name" header="Service">
            <template #body="slotProps">
              <span>{{ slotProps.data.service?.name }}</span>
            </template>
          </Column>

          <Column field="items" header="Items">
            <template #body="slotProps">
              <span>{{ slotProps.data.items?.length || 0 }} items</span>
            </template>
          </Column>

          <Column field="expected_date" header="Expected Date">
            <template #body="slotProps">
              <span>{{ formatDate(slotProps.data.expected_date) }}</span>
            </template>
          </Column>
        </DataTable>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="closeCreateFromDemandsDialog"
          />
          <Button 
            label="Create Orders" 
            icon="pi pi-plus" 
            :loading="creatingFromDemands"
            :disabled="selectedServiceDemands.length === 0"
            @click="createOrdersFromDemands"
          />
        </div>
      </template>
    </Dialog>

    <!-- Proforma Details Dialog -->
    <Dialog 
      :visible="showDetailsDialog" 
      @update:visible="showDetailsDialog = $event" 
      modal 
      :header="`Order Details: ${selectedProforma?.proforma_code}`" 
      :style="{width: '80rem'}"
    >
      <div v-if="selectedProforma" class="tw-space-y-6">
        <!-- Header Information -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <div class="tw-space-y-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Order Code</label>
              <div class="tw-text-lg tw-font-mono tw-font-bold tw-text-gray-900">{{ selectedProforma.proforma_code }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Service Demand</label>
              <div class="tw-text-lg tw-text-gray-900">{{ selectedProforma.service_demand?.demand_code || 'Direct Order' }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Status</label>
              <div>
                <Tag 
                  :value="selectedProforma.status" 
                  :severity="getStatusSeverity(selectedProforma.status)"
                  class="tw-font-medium"
                />
              </div>
            </div>
          </div>
          <div class="tw-space-y-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Supplier</label>
              <div class="tw-text-lg tw-text-gray-900">{{ selectedProforma.fournisseur?.name || 'Not Assigned' }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Total Amount</label>
              <div class="tw-text-lg tw-font-bold tw-text-green-600">{{ formatCurrency(selectedProforma.total_amount || 0) }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Created</label>
              <div class="tw-text-lg tw-text-gray-900">{{ formatDateTime(selectedProforma.created_at) }}</div>
            </div>
          </div>
        </div>

        <!-- Products -->
        <div class="tw-border-t tw-pt-6">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Products ({{ selectedProforma.products?.length || 0 }})</h3>
          
          <DataTable 
            :value="selectedProforma.products || []" 
            responsiveLayout="scroll"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8">
                <i class="pi pi-box tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                <p class="tw-text-gray-500">No products in this order.</p>
              </div>
            </template>

            <Column field="product_name" header="Product">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center">
                  <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.data.product_name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product_code }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity">
              <template #body="slotProps">
                <span class="tw-font-medium">{{ slotProps.data.quantity }}</span>
              </template>
            </Column>

            <Column field="unit_price" header="Unit Price">
              <template #body="slotProps">
                <span class="tw-font-medium">{{ formatCurrency(slotProps.data.unit_price) }}</span>
              </template>
            </Column>

            <Column field="total_price" header="Total">
              <template #body="slotProps">
                <span class="tw-font-bold tw-text-green-600">
                  {{ formatCurrency(slotProps.data.total_price) }}
                </span>
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
            v-if="canEditProforma(selectedProforma)"
            label="Edit Order" 
            icon="pi pi-pencil" 
            @click="editProformaFromDetails"
          />
          <Button 
            label="Download PDF" 
            icon="pi pi-download" 
            @click="downloadProforma(selectedProforma)"
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
import { debounce } from 'lodash-es';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Checkbox from 'primevue/checkbox';

export default {
  name: 'FactureProformaManagement',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    Dropdown,
    Calendar,
    Tag,
    Card,
    Toast,
    ConfirmDialog,
    Checkbox
  },
  data() {
    return {
      proformas: [],
      suppliers: [],
      availableServiceDemands: [],
      selectedServiceDemands: [],
      selectedProformas: [],
      selectAll: false,
      stats: {},
      loading: false,
      loadingServiceDemands: false,
      creatingFromDemands: false,

      showCreateFromDemandsDialog: false,
      showDetailsDialog: false,
      selectedProforma: null,

      filters: {
        global: { value: null, matchMode: 'contains' }
      },
      statusFilter: null,
      supplierFilter: null,
      dateRange: null,
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
    this.debouncedSearch = debounce(this.loadProformas, 300);
    this.loadInitialData();
  },
  methods: {
    async loadInitialData() {
      this.loading = true;
      try {
        await Promise.all([
          this.loadProformas(),
          this.loadSuppliers(),
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

    async loadProformas() {
      try {
        const params = {};
        if (this.statusFilter) params.status = this.statusFilter;
        if (this.supplierFilter) params.fournisseur_id = this.supplierFilter;
        if (this.filters.global.value) params.search = this.filters.global.value;
        if (this.dateRange && this.dateRange.length === 2) {
          params.date_from = this.formatDateForAPI(this.dateRange[0]);
          params.date_to = this.formatDateForAPI(this.dateRange[1]);
        }

        const response = await axios.get('/api/facture-proformas', { params });
        this.proformas = response.data.data.data || [];
      } catch (error) {
        console.error('Failed to load proformas:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load orders',
          life: 3000
        });
      }
    },

    async loadSuppliers() {
      try {
        const response = await axios.get('/api/fournisseurs');
        this.suppliers = response.data.data || [];
      } catch (error) {
        console.error('Failed to load suppliers:', error);
      }
    },

    async loadStats() {
      try {
        const response = await axios.get('/api/facture-proformas/meta/stats');
        this.stats = response.data.data || {};
      } catch (error) {
        console.error('Failed to load stats:', error);
      }
    },

    async loadServiceDemands() {
      this.loadingServiceDemands = true;
      try {
        const response = await axios.get('/api/service-demands');
        // Handle paginated response structure: {success: true, data: {data: [...], pagination_info}}
        this.availableServiceDemands = response.data.data?.data || response.data.data || [];
      } catch (error) {
        console.error('Failed to load service demands:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load service demands',
          life: 3000
        });
      } finally {
        this.loadingServiceDemands = false;
      }
    },

    async createOrdersFromDemands() {
      this.creatingFromDemands = true;
      try {
        const demandIds = this.selectedServiceDemands.map(demand => demand.id);
        const response = await axios.post('/api/facture-proformas/create-from-demands', {
          service_demand_ids: demandIds
        });

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Created ${response.data.data.length} orders successfully`,
          life: 3000
        });

        this.closeCreateFromDemandsDialog();
        this.loadProformas();
        this.loadStats();
      } catch (error) {
        console.error('Error creating orders from demands:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to create orders from service demands',
          life: 3000
        });
      } finally {
        this.creatingFromDemands = false;
      }
    },

    createNewOrder() {
      // Navigate to create order page
      this.$router.push({ name: 'stock.facture-proforma.create' });
    },

    viewProforma(proforma) {
      this.selectedProforma = proforma;
      this.showDetailsDialog = true;
    },

    editProforma(proforma) {
      // Navigate to edit order page
      this.$router.push({ 
        name: 'stock.facture-proforma.edit', 
        params: { id: proforma.id } 
      });
    },

    editProformaFromDetails() {
      this.showDetailsDialog = false;
      this.editProforma(this.selectedProforma);
    },

    async downloadProforma(proforma) {
      try {
        const response = await axios.get(`/api/facture-proformas/${proforma.id}/download`, {
          responseType: 'blob'
        });

        // Create blob link to download
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `${proforma.proforma_code}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'PDF downloaded successfully',
          life: 3000
        });
      } catch (error) {
        console.error('Error downloading PDF:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to download PDF',
          life: 3000
        });
      }
    },

    async deleteProforma(proforma) {
      this.confirm.require({
        message: `Are you sure you want to delete order "${proforma.proforma_code}"? This action cannot be undone.`,
        header: 'Delete Order',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/facture-proformas/${proforma.id}`);
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Order deleted successfully',
              life: 3000
            });
            this.loadProformas();
            this.loadStats();
          } catch (error) {
            console.error('Error deleting proforma:', error);
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

    onSelectAllChange() {
      this.selectedProformas = this.selectAll ? this.proformas.slice() : [];
    },

    showBulkActionMenu() {
      // Implementation for bulk actions
      this.toast.add({
        severity: 'info',
        summary: 'Bulk Actions',
        detail: 'Bulk actions feature coming soon',
        life: 3000
      });
    },

    clearFilters() {
      this.filters.global.value = null;
      this.statusFilter = null;
      this.supplierFilter = null;
      this.dateRange = null;
      this.loadProformas();
    },

    closeCreateFromDemandsDialog() {
      this.showCreateFromDemandsDialog = false;
      this.selectedServiceDemands = [];
    },

    openCreateFromDemandsDialog() {
      this.showCreateFromDemandsDialog = true;
      this.loadServiceDemands();
    },

    // Helper methods
    canEditProforma(proforma) {
      return proforma && ['draft'].includes(proforma.status);
    },

    canDeleteProforma(proforma) {
      return proforma && ['draft'].includes(proforma.status);
    },

    getStatusSeverity(status) {
      const severities = {
        draft: 'warning',
        sent: 'info',
        approved: 'success',
        rejected: 'danger',
        completed: 'success'
      };
      return severities[status] || 'info';
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount);
    },

    formatDate(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleDateString();
    },

    formatDateTime(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleString();
    },

    formatDateForAPI(date) {
      if (!date) return null;
      return date.toISOString().split('T')[0];
    }
  },
  watch: {
    showCreateFromDemandsDialog(newVal) {
      if (newVal) {
        this.openCreateFromDemandsDialog();
      }
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

/* Form input enhancements */
.p-inputtext:focus,
.p-dropdown:focus,
.p-calendar:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  border-color: rgb(59, 130, 246);
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-5 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>