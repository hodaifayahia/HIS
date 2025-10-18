<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <!-- Toast Notifications -->
    <Toast />

    <!-- Success Notification -->
    <div v-if="submitSuccess" class="tw-fixed tw-top-4 tw-right-4 tw-bg-green-500 tw-text-white tw-px-6 tw-py-3 tw-rounded-xl tw-shadow-xl tw-z-50 tw-flex tw-items-center tw-gap-2 tw-animate-fade-in">
      <i class="fas fa-check-circle"></i>
      <span>{{ submitSuccess }}</span>
      <button @click="submitSuccess = false" class="tw-ml-2 tw-text-white hover:tw-text-green-200">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Error Notification -->
    <div v-if="submitError" class="tw-fixed tw-top-4 tw-right-4 tw-bg-red-500 tw-text-white tw-px-6 tw-py-3 tw-rounded-xl tw-shadow-xl tw-z-50 tw-flex tw-items-center tw-gap-2 tw-animate-fade-in">
      <i class="fas fa-exclamation-triangle"></i>
      <span>{{ submitError }}</span>
      <button @click="submitError = null" class="tw-ml-2 tw-text-white hover:tw-text-red-200">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Header -->
    <div class="tw-mb-8">
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <Button
                @click="$router.go(-1)"
                icon="pi pi-arrow-left"
                label="Back"
                class="p-button-secondary tw-rounded-lg tw-shadow-md"
              />
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-p-3 tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-shadow-lg">
                  <i class="pi pi-box tw-text-white tw-text-2xl"></i>
                </div>
                <div>
                  <h1 class="tw-m-0 tw-text-gray-800 tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 tw-bg-clip-text tw-text-transparent">
                    Service Stock - {{ service?.name || 'Loading...' }}
                  </h1>
                  <p class="tw-m-0 tw-text-gray-600 tw-text-sm tw-mt-1">All stock across {{ stockagesCount }} stockages</p>
                </div>
              </div>
            </div>
            <div class="tw-flex tw-gap-4 tw-items-center">
              <div class="tw-relative">
                <InputText
                  v-model="searchQuery"
                  placeholder="Search products..."
                  class="tw-w-80 tw-pl-10 tw-pr-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-focus:border-blue-500 tw-transition-all tw-duration-300"
                  @input="onSearchInput"
                />
                <i class="pi pi-search tw-absolute tw-left-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400"></i>
              </div>
              <Button
                label="Add Product"
                icon="pi pi-plus"
                class="p-button-primary tw-px-6 tw-py-3 tw-rounded-xl tw-shadow-lg tw-hover:shadow-xl tw-transition-all tw-duration-300"
                @click="$refs.addProductModal.openModal()"
              />
              <Button
                label="Export All"
                icon="pi pi-download"
                class="p-button-success tw-px-6 tw-py-3 tw-rounded-xl tw-shadow-lg tw-hover:shadow-xl tw-transition-all tw-duration-300"
                @click="exportStock"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Stock Summary Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
      <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-br tw-from-blue-50 tw-to-blue-100 tw-hover:shadow-xl tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-blue-600 tw-m-0 tw-font-medium">Total Products</p>
              <p class="tw-text-3xl tw-font-bold tw-text-blue-800 tw-m-0">{{ totalProducts }}</p>
            </div>
            <div class="tw-bg-blue-500 tw-p-4 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-box tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-br tw-from-green-50 tw-to-green-100 tw-hover:shadow-xl tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-green-600 tw-m-0 tw-font-medium">Total Quantity</p>
              <p class="tw-text-3xl tw-font-bold tw-text-green-800 tw-m-0">{{ totalQuantity }}</p>
            </div>
            <div class="tw-bg-green-500 tw-p-4 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-chart-line tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-br tw-from-purple-50 tw-to-purple-100 tw-hover:shadow-xl tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-purple-600 tw-m-0 tw-font-medium">Stockages</p>
              <p class="tw-text-3xl tw-font-bold tw-text-purple-800 tw-m-0">{{ stockagesCount }}</p>
            </div>
            <div class="tw-bg-purple-500 tw-p-4 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-warehouse tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-br tw-from-red-50 tw-to-red-100 tw-hover:shadow-xl tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-red-600 tw-m-0 tw-font-medium">Alerts</p>
              <p class="tw-text-3xl tw-font-bold tw-text-red-800 tw-m-0">{{ alertCount }}</p>
            </div>
            <div class="tw-bg-red-500 tw-p-4 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-exclamation-triangle tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card class="tw-mb-6 tw-shadow-lg tw-border-0 tw-bg-white/90 tw-backdrop-blur-sm">
      <template #content>
        <div class="tw-flex tw-gap-6 tw-items-center tw-flex-wrap">
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-filter tw-text-blue-500"></i>
            <label class="tw-text-sm tw-font-medium tw-text-gray-700">Stockage:</label>
            <Dropdown
              v-model="stockageFilter"
              :options="serviceStockages"
              option-label="name"
              option-value="id"
              placeholder="All Stockages"
              class="tw-w-48 tw-rounded-lg"
              @change="applyFilters"
            />
          </div>

          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-tag tw-text-green-500"></i>
            <label class="tw-text-sm tw-font-medium tw-text-gray-700">Category:</label>
            <Dropdown
              v-model="categoryFilter"
              :options="categories"
              option-label="name"
              option-value="id"
              placeholder="All Categories"
              class="tw-w-48 tw-rounded-lg"
              @change="applyFilters"
            />
          </div>

          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-chart-bar tw-text-purple-500"></i>
            <label class="tw-text-sm tw-font-medium tw-text-gray-700">Stock Status:</label>
            <Dropdown
              v-model="stockStatusFilter"
              :options="stockStatusOptions"
              option-label="label"
              option-value="value"
              placeholder="All Status"
              class="tw-w-48 tw-rounded-lg"
              @change="applyFilters"
            />
          </div>

          <Button
            label="Clear Filters"
            icon="pi pi-times"
            class="p-button-secondary tw-rounded-lg"
            @click="clearFilters"
          />
        </div>
      </template>
    </Card>

    <!-- Products DataTable -->
    <Card class="tw-shadow-xl tw-border-0 tw-bg-white/90 tw-backdrop-blur-sm">
      <template #content>
        <DataTable
          :value="filteredProducts"
          :loading="loading"
          :paginator="true"
          :rows="perPage"
          :totalRecords="filteredProducts.length"
          :lazy="false"
          class="p-datatable-sm tw-rounded-xl"
          stripedRows
          showGridlines
          responsiveLayout="scroll"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[10, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
        >
          <template #loading>
            <div class="tw-flex tw-justify-center tw-items-center tw-py-12 tw-bg-gray-50">
              <ProgressSpinner class="tw-mr-3" />
              <span class="tw-text-lg tw-text-gray-600 tw-font-medium">Loading products...</span>
            </div>
          </template>

          <template #empty>
            <div class="tw-text-center tw-py-20 tw-bg-gray-50 tw-rounded-lg">
              <i class="pi pi-box tw-text-6xl tw-mb-4 tw-text-gray-400"></i>
              <p class="tw-text-xl tw-m-0 tw-text-gray-500 tw-font-medium">No products found</p>
              <p class="tw-text-sm tw-text-gray-400 tw-mt-2">Try adjusting your filters or add a new product.</p>
            </div>
          </template>

          <Column field="product.name" header="Product Name" style="min-width: 200px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                  <i class="pi pi-box tw-text-blue-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.product?.name || 'N/A' }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.code || '' }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="stockage.name" header="Stockage" style="min-width: 150px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-warehouse tw-text-purple-500"></i>
                <span class="tw-text-gray-700">{{ slotProps.data.stockage?.name || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <Column field="quantity" header="Quantity" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <span :class="getQuantityClass(slotProps.data.total_units || slotProps.data.quantity, slotProps.data)">
                  {{ formatQuantity(slotProps.data.total_units || slotProps.data.quantity || 0) }}
                </span>
                <span class="tw-text-sm tw-text-gray-500">{{ slotProps.data.unit || 'units' }}</span>
                <span v-if="slotProps.data.product?.boite_de && slotProps.data.quantity && slotProps.data.total_units && slotProps.data.quantity !== slotProps.data.total_units" class="tw-text-xs tw-text-blue-600 tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded">
                  {{ formatQuantity(Math.round(parseQuantity(slotProps.data.total_units) / parseQuantity(slotProps.data.product.boite_de))) }} boxes
                </span>
              </div>
            </template>
          </Column>

          <!-- Min Level column removed -->

          <Column field="batch_number" header="Batch" style="min-width: 120px">
            <template #body="slotProps">
              <span class="tw-text-sm tw-font-mono tw-text-gray-600">{{ slotProps.data.batch_number || 'N/A' }}</span>
            </template>
          </Column>

          <Column field="serial_number" header="Serial Number" style="min-width: 120px">
            <template #body="slotProps">
              <span class="tw-text-sm tw-font-mono tw-text-gray-600">{{ slotProps.data.serial_number || 'N/A' }}</span>
            </template>
          </Column>

          <Column field="purchase_price" header="Purchase Price" style="min-width: 120px">
            <template #body="slotProps">
              <span v-if="slotProps.data.purchase_price" class="tw-text-sm tw-font-semibold tw-text-green-600">
                ${{ slotProps.data.purchase_price }}
              </span>
              <span v-else class="tw-text-gray-500 tw-text-sm tw-italic">N/A</span>
            </template>
          </Column>

          <Column field="barcode" header="Barcode" style="min-width: 150px">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <span class="tw-text-sm tw-font-mono tw-text-gray-600">{{ slotProps.data.barcode || 'N/A' }}</span>
                <Button
                  v-if="slotProps.data.barcode"
                  icon="pi pi-copy"
                  class="p-button-text p-button-sm"
                  @click="copyBarcode(slotProps.data.barcode)"
                  v-tooltip.top="'Copy Barcode'"
                />
              </div>
            </template>
          </Column>

          <Column field="expiry_date" header="Expiry Date" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <span v-if="slotProps.data.expiry_date" :class="getExpiryClass(slotProps.data.expiry_date)">
                {{ formatDate(slotProps.data.expiry_date) }}
              </span>
              <span v-else class="tw-text-gray-500 tw-italic">N/A</span>
            </template>
          </Column>

          <Column field="location" header="Location" style="min-width: 150px">
            <template #body="slotProps">
              <span class="tw-text-sm tw-text-gray-700">{{ slotProps.data.location || 'N/A' }}</span>
            </template>
          </Column>

          <Column field="last_updated" header="Last Updated" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <span class="tw-text-sm tw-text-gray-600">{{ formatDate(slotProps.data.updated_at) }}</span>
            </template>
          </Column>

          <Column header="Actions" style="min-width: 150px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-eye"
                  class="p-button-info p-button-sm tw-rounded-lg"
                  @click="viewStockageStock(slotProps.data.stockage)"
                  v-tooltip.top="'View Stockage Stock'"
                />
                <Button
                  icon="pi pi-external-link"
                  class="p-button-secondary p-button-sm tw-rounded-lg"
                  @click="viewProductDetails(slotProps.data.product)"
                  v-tooltip.top="'View Product'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Stockages Overview -->
    <Card class="tw-mt-8 tw-shadow-xl tw-border-0 tw-bg-white/90 tw-backdrop-blur-sm">
      <template #header>
        <div class="tw-flex tw-justify-between tw-items-center tw-p-6 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-t-xl">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
              <i class="pi pi-list tw-text-blue-600"></i>
            </div>
            <h3 class="tw-m-0 tw-text-gray-800 tw-text-xl tw-font-bold">Stockages Overview</h3>
          </div>
          <Button
            label="View All Stockages"
            icon="pi pi-list"
            class="p-button-outline tw-rounded-lg tw-border-blue-500 tw-text-blue-600 hover:tw-bg-blue-50"
            @click="$router.push({ name: 'stock.services.show', params: { id: route.params.id } })"
          />
        </div>
      </template>
      <template #content>
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6 tw-p-6">
          <Card v-for="stockage in serviceStockages" :key="stockage.id" class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-br tw-from-white tw-to-gray-50 hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105">
            <template #content>
              <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
                <div>
                  <h4 class="tw-m-0 tw-text-gray-800 tw-font-bold tw-text-lg">{{ stockage.name }}</h4>
                  <p class="tw-m-0 tw-text-sm tw-text-gray-500 tw-flex tw-items-center tw-gap-1">
                    <i class="pi pi-map-marker tw-text-gray-400"></i>
                    {{ stockage.location }}
                  </p>
                </div>
                <Tag :value="stockage.status" :severity="getStatusSeverity(stockage.status)" class="tw-font-medium" />
              </div>

              <div class="tw-flex tw-justify-between tw-items-center tw-mb-3 tw-p-3 tw-bg-blue-50 tw-rounded-lg">
                <span class="tw-text-sm tw-text-blue-700 tw-font-medium tw-flex tw-items-center tw-gap-1">
                  <i class="pi pi-box tw-text-blue-500"></i>
                  Products:
                </span>
                <span class="tw-font-bold tw-text-blue-800">{{ getStockageProductCount(stockage.id) }}</span>
              </div>

              <div class="tw-flex tw-justify-between tw-items-center tw-mb-4 tw-p-3 tw-bg-green-50 tw-rounded-lg">
                <span class="tw-text-sm tw-text-green-700 tw-font-medium tw-flex tw-items-center tw-gap-1">
                  <i class="pi pi-chart-line tw-text-green-500"></i>
                  Total Quantity:
                </span>
                <span class="tw-font-bold tw-text-green-800">{{ getStockageTotalQuantity(stockage.id) }}</span>
              </div>

              <Button
                label="View Stock"
                icon="pi pi-box"
                class="p-button-success tw-w-full tw-rounded-lg tw-py-3 tw-font-medium tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-300"
                @click="viewStockageStock(stockage)"
              />
            </template>
          </Card>
        </div>
      </template>
    </Card>

    <!-- Add Product Modal -->
    <AddProductToStockModal
      ref="addProductModal"
      :available-stockages="serviceStockages"
      :service-abv="service?.service_abv"
      @success="onAddProductSuccess"
      @error="onAddProductError"
    />

    <!-- Add Location Modal -->
    <!-- This modal is now handled by the AddProductToStockModal component -->
  </div>
</template>

<script>
import axios from 'axios';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';

// Custom Components
import AddProductToStockModal from '@/Components/Apps/stock/AddProductToStockModal.vue';

export default {
  name: 'ServiceStock',
  components: {
    Card,
    Button,
    InputText,
    Dropdown,
    DataTable,
    Column,
    Tag,
    ProgressSpinner,
    Toast,
    AddProductToStockModal
  },
  props: {
    serviceId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      searchQuery: '',
      stockageFilter: '',
      categoryFilter: '',
      stockStatusFilter: '',
      submitSuccess: false,
      submitError: null,
      searchTimeout: null,

      // Data
      service: null,
      products: [],
      filteredProducts: [],
      serviceStockages: [],
      categories: [],

      // Loading states
      loading: false,

      // Loading states for component
      // loadingProducts: false, // Now handled by AddProductToStockModal component
      // loadingLocations: false, // Now handled by AddProductToStockModal component
      // isSubmitting: false, // Now handled by AddProductToStockModal component

      // Modals
      // showAddProductModal: false, // Now handled by AddProductToStockModal component

      // Pagination
      currentPage: 1,
      lastPage: 1,
      perPage: 10,
      total: 0,

      // Options
      stockStatusOptions: [
        { label: 'All', value: '' },
        { label: 'In Stock', value: 'in_stock' },
        { label: 'Low Stock', value: 'low_stock' },
        { label: 'Out of Stock', value: 'out_of_stock' }
      ],
      // toolTypes: [], // Now handled by AddProductToStockModal component
      // blocks: [], // Now handled by AddProductToStockModal component
    }
  },
  mounted() {
    this.fetchService();
    this.fetchServiceStockages();
    this.fetchProducts();
    this.fetchCategories();
    // fetchAvailableProducts() - Now handled by AddProductToStockModal component
    // fetchToolTypes() - Now handled by AddProductToStockModal component
    // fetchBlocks() - Now handled by AddProductToStockModal component
  },
  computed: {
    totalProducts() {
      return this.filteredProducts.length;
    },
    totalQuantity() {
      return this.filteredProducts.reduce((sum, item) => {
        // Parse quantity safely, handling strings with commas or other formatting
        let quantity = 0;

        if (item.total_units !== null && item.total_units !== undefined) {
          // Remove commas and parse as float
          const totalUnitsStr = String(item.total_units).replace(/,/g, '');
          quantity = parseFloat(totalUnitsStr) || 0;
        } else if (item.quantity !== null && item.quantity !== undefined) {
          // Remove commas and parse as float
          const quantityStr = String(item.quantity).replace(/,/g, '');
          quantity = parseFloat(quantityStr) || 0;
        }

        // Ensure we have a valid number
        if (isNaN(quantity)) {
          quantity = 0;
        }

        return sum + quantity;
      }, 0);
    },
    stockagesCount() {
      return this.serviceStockages.length;
    },
    alertCount() {
      let count = 0;
      // Count low stock items
      count += this.filteredProducts.filter(item => {
        const quantity = this.parseQuantity(item.total_units || item.quantity || 0);
        const minLevel = 0; // legacy field removed
        return quantity <= minLevel;
      }).length;
      // Count expiring items
      const now = new Date();
      const thirtyDaysFromNow = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));
      count += this.filteredProducts.filter(item => {
        if (!item.expiry_date) return false;
        const expiryDate = new Date(item.expiry_date);
        return expiryDate <= thirtyDaysFromNow;
      }).length;
      return count;
    }
    // locationCodePreview computed property removed - now handled by AddProductToStockModal component
  },
  methods: {
    async fetchService() {
      try {
        const response = await axios.get(`/api/services/${this.serviceId}`);
        if (response.data.success) {
          this.service = response.data.data;
          // Also fetch service abbreviation if not included
          if (!this.service.service_abv) {
            this.service.service_abv = 'PHAR'; // Default fallback
          }
        }
      } catch (error) {
        this.submitError = 'Failed to load service details';
        setTimeout(() => {
          this.submitError = null;
        }, 5000);
      }
    },

    async fetchServiceStockages() {
      try {
        const response = await axios.get(`/api/stockages?service_id=${this.serviceId}`);
        if (response.data.success) {
          this.serviceStockages = response.data.data;
        }
      } catch (error) {
        console.error('Failed to load service stockages');
      }
    },

    async fetchProducts() {
      this.loading = true;
      try {
        const params = {
          per_page: 10000, // Fetch all
          service_id: this.serviceId
        };

        if (this.searchQuery.trim()) {
          params.search = this.searchQuery;
        }

        const response = await axios.get('/api/inventory/service-stock', { params });
        if (response.data.success) {
          this.products = response.data.data;
          this.filterProducts();
        }
      } catch (error) {
        this.submitError = 'Failed to load products';
        setTimeout(() => {
          this.submitError = null;
        }, 5000);
      } finally {
        this.loading = false;
      }
    },

    async fetchCategories() {
      try {
        const response = await axios.get('/api/categories');
        if (response.data.success) {
          this.categories = response.data.data;
        }
      } catch (error) {
        console.error('Failed to load categories');
      }
    },

    // fetchStockageLocations and calculateLocationItemCounts are now handled by AddProductToStockModal component

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchProducts();
      }, 300);
    },

    applyFilters() {
      this.filterProducts();
    },

    clearFilters() {
      this.stockageFilter = '';
      this.categoryFilter = '';
      this.stockStatusFilter = '';
      this.filterProducts();
    },

    filterProducts() {
      let filtered = [...this.products];

      // Filter by stockage
      if (this.stockageFilter) {
        filtered = filtered.filter(item => item.stockage_id == this.stockageFilter);
      }

      // Filter by category
      if (this.categoryFilter) {
        filtered = filtered.filter(item => item.product?.category_id == this.categoryFilter);
      }

      // Filter by stock status
      if (this.stockStatusFilter) {
        switch (this.stockStatusFilter) {
          case 'in_stock':
            filtered = filtered.filter(item => {
              const quantity = this.parseQuantity(item.total_units || item.quantity || 0);
              return quantity > 0;
            });
            break;
          case 'low_stock':
            filtered = filtered.filter(item => {
              const quantity = this.parseQuantity(item.total_units || item.quantity || 0);
              const minLevel = 0; // legacy field removed
              return quantity <= minLevel && quantity > 0;
            });
            break;
          case 'out_of_stock':
            filtered = filtered.filter(item => {
              const quantity = this.parseQuantity(item.total_units || item.quantity || 0);
              return quantity <= 0;
            });
            break;
        }
      }

      this.filteredProducts = filtered;
    },

    viewStockageStock(stockage) {
      this.$router.push({ name: 'stock.stockages.stock', params: { id: stockage.id } });
    },

    viewProductDetails(product) {
      // TODO: Navigate to product details page
      console.log('View product details:', product);
    },

    exportStock() {
      // TODO: Implement export functionality
      this.submitSuccess = 'Export feature coming soon!';
      setTimeout(() => {
        this.submitSuccess = false;
      }, 3000);
    },

    getStockageProductCount(stockageId) {
      return this.filteredProducts.filter(item => item.stockage_id === stockageId).length;
    },

    getStockageTotalQuantity(stockageId) {
      return this.filteredProducts
        .filter(item => item.stockage_id === stockageId)
        .reduce((sum, item) => {
          // Parse quantity safely, handling strings with commas or other formatting
          let quantity = 0;

          if (item.total_units !== null && item.total_units !== undefined) {
            // Remove commas and parse as float
            const totalUnitsStr = String(item.total_units).replace(/,/g, '');
            quantity = parseFloat(totalUnitsStr) || 0;
          } else if (item.quantity !== null && item.quantity !== undefined) {
            // Remove commas and parse as float
            const quantityStr = String(item.quantity).replace(/,/g, '');
            quantity = parseFloat(quantityStr) || 0;
          }

          // Ensure we have a valid number
          if (isNaN(quantity)) {
            quantity = 0;
          }

          return sum + quantity;
        }, 0);
    },

    // Helper methods
    getQuantityClass(quantity, productGroup) {
      // Parse quantity safely
      let qty = 0;
      if (quantity !== null && quantity !== undefined) {
        const qtyStr = String(quantity).replace(/,/g, '');
        qty = parseFloat(qtyStr) || 0;
      }

      // Determine threshold from product settings if available
      let min = 0;
      try {
        const settings = this.getProductSettings ? this.getProductSettings(productGroup) : null;
        min = settings?.lowStockThreshold ?? 0;
      } catch (e) {
        min = 0;
      }

      if (qty <= 0) return 'tw-text-red-600 tw-font-semibold';
      if (min && qty <= min) return 'tw-text-yellow-600 tw-font-semibold';
      return 'tw-text-green-600 tw-font-semibold';
    },

    parseQuantity(value) {
      if (value === null || value === undefined) return 0;

      // Remove commas and parse as float
      const str = String(value).replace(/,/g, '');
      const parsed = parseFloat(str);

      return isNaN(parsed) ? 0 : parsed;
    },

    formatQuantity(value) {
      const num = this.parseQuantity(value);
      return num.toLocaleString();
    },

    getExpiryClass(expiryDate) {
      const now = new Date();
      const expiry = new Date(expiryDate);
      const thirtyDaysFromNow = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));

      if (expiry <= now) return 'tw-text-red-600 tw-font-semibold';
      if (expiry <= thirtyDaysFromNow) return 'tw-text-yellow-600 tw-font-semibold';
      return 'tw-text-gray-600';
    },

    getStatusSeverity(status) {
      const severities = {
        'active': 'success',
        'inactive': 'secondary',
        'maintenance': 'warning',
        'under_construction': 'info'
      };
      return severities[status] || 'info';
    },

    getCategorySeverity(category) {
      const severities = {
        'medication': 'danger',
        'equipment': 'info',
        'consumable': 'success',
        'supplies': 'warning'
      };
      return severities[category] || 'secondary';
    },

    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString();
    },

    copyBarcode(barcode) {
      navigator.clipboard.writeText(barcode).then(() => {
        this.submitSuccess = 'Barcode copied to clipboard!';
        setTimeout(() => {
          this.submitSuccess = false;
        }, 2000);
      }).catch(() => {
        this.submitError = 'Failed to copy barcode';
        setTimeout(() => {
          this.submitError = null;
        }, 2000);
      });
    },

    // Component event handlers
    onAddProductSuccess(message) {
      this.submitSuccess = message;
      this.fetchProducts(); // Refresh the products list
      setTimeout(() => {
        this.submitSuccess = false;
      }, 3000);
    },

    onAddProductError(message) {
      this.submitError = message;
      setTimeout(() => {
        this.submitError = null;
      }, 5000);
    },

    // All modal-related methods are now handled by AddProductToStockModal component
  }
}
</script>

<style scoped>
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

.tw-animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

.tw-animate-slide-in {
  animation: slide-in 0.4s ease-out;
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

/* Enhanced Input styling */
:deep(.p-inputtext) {
  border-radius: 0.5rem;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease;
  background: #ffffff;
}

:deep(.p-inputtext:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

/* Custom scrollbar for modal */
.tw-overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.tw-overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Gradient text effect */
.tw-gradient-text {
  background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Card hover effects */
.p-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

/* Custom focus styles */
.tw-border-gray-300:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.tw-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
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
