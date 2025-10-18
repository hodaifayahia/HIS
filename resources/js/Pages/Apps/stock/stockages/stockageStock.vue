<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <!-- Toast Notifications -->
    <Toast />

    <!-- Success Notification -->
    <div v-if="submitSuccess" class="tw-fixed tw-top-4 tw-right-4 tw-bg-green-500 tw-text-white tw-px-6 tw-py-3 tw-rounded-lg tw-shadow-lg tw-z-50 tw-flex tw-items-center tw-gap-2 tw-animate-fade-in">
      <i class="fas fa-check-circle"></i>
      <span>{{ submitSuccess }}</span>
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

    <!-- Header -->
    <div class="tw-mb-8">
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-p-3 tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-box tw-text-white tw-text-2xl"></i>
              </div>
              <div>
                <h1 class="tw-m-0 tw-text-gray-800 tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 tw-bg-clip-text tw-text-transparent">
                  Stock Management - {{ stockage?.name || 'Loading...' }}
                </h1>
                <p class="tw-m-0 tw-text-gray-600 tw-text-sm tw-mt-1">{{ stockage?.location || '' }}</p>
              </div>
            </div>
            <div class="tw-flex tw-gap-4 tw-items-center">
              <div class="p-input-icon-left tw-w-80">
                <i class="pi pi-search tw-text-gray-400" />
                <InputText
                  v-model="searchQuery"
                  placeholder="Search products..."
                  class="tw-rounded-lg tw-shadow-sm tw-border-gray-200"
                  @input="onSearchInput"
                />
              </div>
              <Button
                label="Add Product"
                icon="pi pi-plus"
                class="p-button-primary tw-rounded-lg tw-shadow-md tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-border-0"
                @click="$refs.addProductModal.openModal()"
              />
              <Button
                label="Export"
                icon="pi pi-download"
                class="p-button-success tw-rounded-lg tw-shadow-md"
                @click="exportStock"
              />
              <Button
                @click="$router.go(-1)"
                icon="pi pi-arrow-left"
                label="Back"
                class="p-button-secondary tw-rounded-lg tw-shadow-md"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Stock Summary Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
      <!-- Total Products Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm tw-hover:shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-box tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Total Products</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-m-0">{{ totalProducts }}</p>
              <p class="tw-text-sm tw-text-gray-600 tw-m-0 tw-mt-1">Active products</p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-r tw-from-blue-100 tw-to-indigo-100 tw-rounded-xl">
              <i class="pi pi-box tw-text-blue-600 tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <!-- Total Quantity Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm tw-hover:shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-chart-line tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Total Quantity</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-m-0">{{ totalQuantity }}</p>
              <p class="tw-text-sm tw-text-gray-600 tw-m-0 tw-mt-1">Units in stock</p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-r tw-from-green-100 tw-to-emerald-100 tw-rounded-xl">
              <i class="pi pi-chart-line tw-text-green-600 tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <!-- Low Stock Items Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm tw-hover:shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-yellow-500 tw-to-orange-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-exclamation-triangle tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Low Stock Items</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-m-0">{{ lowStockCount }}</p>
              <p class="tw-text-sm tw-text-gray-600 tw-m-0 tw-mt-1">Need attention</p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-r tw-from-yellow-100 tw-to-orange-100 tw-rounded-xl">
              <i class="pi pi-exclamation-triangle tw-text-yellow-600 tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <!-- Expiring Soon Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm tw-hover:shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-red-500 tw-to-pink-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-calendar-times tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Expiring Soon</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-m-0">{{ expiringSoonCount }}</p>
              <p class="tw-text-sm tw-text-gray-600 tw-m-0 tw-mt-1">Within 30 days</p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-r tw-from-red-100 tw-to-pink-100 tw-rounded-xl">
              <i class="pi pi-calendar-times tw-text-red-600 tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm tw-mb-8">
      <template #header>
        <div class="tw-p-4 tw-bg-gradient-to-r tw-from-purple-500 tw-to-pink-600 tw-text-white tw-rounded-t-lg">
          <div class="tw-flex tw-items-center tw-gap-3">
            <i class="pi pi-filter tw-text-xl"></i>
            <h3 class="tw-m-0 tw-font-semibold">Filters & Search</h3>
          </div>
        </div>
      </template>
      <template #content>
        <div class="tw-flex tw-flex-wrap tw-gap-6 tw-items-end">
          <div class="tw-flex-1 tw-min-w-48">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-tag tw-text-blue-500"></i>
              Category
            </label>
            <Dropdown
              v-model="categoryFilter"
              :options="categories"
              option-label="name"
              option-value="id"
              placeholder="All Categories"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              @change="applyFilters"
              show-clear
            />
          </div>

          <div class="tw-flex-1 tw-min-w-48">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-chart-bar tw-text-green-500"></i>
              Stock Status
            </label>
            <Dropdown
              v-model="stockStatusFilter"
              :options="stockStatusOptions"
              option-label="label"
              option-value="value"
              placeholder="All Status"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              @change="applyFilters"
              show-clear
            />
          </div>

          <div class="tw-flex tw-gap-3">
            <Button
              label="Clear Filters"
              icon="pi pi-times"
              class="p-button-secondary tw-rounded-lg tw-shadow-md"
              @click="clearFilters"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Products DataTable -->
    <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
      <template #header>
        <div class="tw-p-4 tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-rounded-t-lg">
          <div class="tw-flex tw-items-center tw-gap-3">
            <i class="pi pi-list tw-text-xl"></i>
            <h3 class="tw-m-0 tw-font-semibold">Products Inventory</h3>
          </div>
        </div>
      </template>
      <template #content>
        <DataTable
          :value="filteredProducts"
          :loading="loading"
          :paginator="true"
          :rows="perPage"
          :totalRecords="total"
          :lazy="true"
          @page="onPage"
          class="p-datatable-sm tw-rounded-xl tw-overflow-hidden tw-shadow-lg"
          striped-rows
          show-gridlines
          responsive-layout="scroll"
          paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rows-per-page-options="[10, 25, 50]"
          current-page-report-template="Showing {first} to {last} of {totalRecords} products"
        >
          <template #loading>
            <div class="tw-flex tw-justify-center tw-items-center tw-py-16 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl">
              <ProgressSpinner class="tw-mb-4" />
              <p class="tw-text-gray-600 tw-font-medium">Loading products...</p>
            </div>
          </template>

          <template #empty>
            <div class="tw-text-center tw-py-16 tw-bg-gradient-to-r tw-from-gray-50 tw-to-blue-50 tw-rounded-xl tw-border tw-border-gray-100">
              <i class="pi pi-inbox tw-text-5xl tw-text-gray-400 tw-mb-4"></i>
              <p class="tw-text-gray-600 tw-text-lg tw-font-medium">No products found in this stockage</p>
              <p class="tw-text-gray-500 tw-text-sm">Try adjusting your filters or add a new product.</p>
              <Button
                label="Add First Product"
                icon="pi pi-plus"
                class="p-button-primary tw-rounded-lg tw-shadow-md tw-mt-4"
                @click="$refs.addProductModal.openModal()"
              />
            </div>
          </template>

          <Column field="product.name" header="Product Name" style="min-width: 200px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-gradient-to-r tw-from-blue-100 tw-to-indigo-100 tw-rounded-lg">
                  <i class="pi pi-box tw-text-blue-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.product?.name || 'N/A' }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.code || '' }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="quantity" header="Quantity" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <span :class="getQuantityClass(slotProps.data.total_units || slotProps.data.quantity, slotProps.data)">
                  {{ formatQuantity(getItemTotalUnitsDisplay(slotProps.data)) }}
                </span>
                <span class="tw-text-sm tw-text-gray-500">{{ slotProps.data.unit || 'units' }}</span>
                <span v-if="slotProps.data.product?.boite_de && (slotProps.data.total_units || slotProps.data.quantity)" class="tw-text-xs tw-text-blue-600 tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded">
                  {{ formatQuantity(Math.floor(parseQuantity(getItemTotalUnits(slotProps.data)) / parseQuantity(slotProps.data.product.boite_de))) }} boxes
                </span>
              </div>
            </template>
          </Column>

          <!-- Min Level column removed -->

          <Column field="batch_number" header="Batch" style="min-width: 120px">
            <template #body="slotProps">
              <span class="tw-text-sm tw-font-mono tw-text-gray-700 tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">
                {{ slotProps.data.batch_number || 'N/A' }}
              </span>
            </template>
          </Column>

          <Column field="serial_number" header="Serial Number" style="min-width: 120px">
            <template #body="slotProps">
              <span class="tw-text-sm tw-font-mono tw-text-gray-700 tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">
                {{ slotProps.data.serial_number || 'N/A' }}
              </span>
            </template>
          </Column>

          <Column field="purchase_price" header="Purchase Price" style="min-width: 120px">
            <template #body="slotProps">
              <span v-if="slotProps.data.purchase_price" class="tw-text-sm tw-font-semibold tw-text-green-600">
                ${{ slotProps.data.purchase_price }}
              </span>
              <span v-else class="tw-text-gray-500 tw-text-sm">N/A</span>
            </template>
          </Column>

          <Column field="barcode" header="Barcode" style="min-width: 150px">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <span class="tw-text-sm tw-font-mono tw-text-indigo-600 tw-bg-indigo-50 tw-px-2 tw-py-1 tw-rounded">
                  {{ slotProps.data.barcode || 'N/A' }}
                </span>
                <Button
                  v-if="slotProps.data.barcode"
                  icon="pi pi-copy"
                  class="p-button-text p-button-sm tw-rounded-lg"
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
              <span v-else class="tw-text-gray-500">N/A</span>
            </template>
          </Column>

          <Column field="location" header="Location" style="min-width: 150px">
            <template #body="slotProps">
              <span class="tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-blue-50 tw-px-2 tw-py-1 tw-rounded">
                {{ slotProps.data.location || 'N/A' }}
              </span>
            </template>
          </Column>

          <Column field="last_updated" header="Last Updated" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <span class="tw-text-sm tw-text-gray-600">{{ formatDate(slotProps.data.updated_at) }}</span>
            </template>
          </Column>

          <Column header="Actions" style="min-width: 200px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-plus"
                  class="p-button-success p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                  @click="openAddStockModal(slotProps.data)"
                  v-tooltip.top="'Add Stock'"
                />
                <Button
                  icon="pi pi-minus"
                  class="p-button-warning p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                  @click="openRemoveStockModal(slotProps.data)"
                  v-tooltip.top="'Remove Stock'"
                />
                <Button
                  icon="pi pi-arrow-right"
                  class="p-button-info p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                  @click="openTransferModal(slotProps.data)"
                  v-tooltip.top="'Transfer'"
                />
                <Button
                  icon="pi pi-pencil"
                  class="p-button-secondary p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                  @click="openEditModal(slotProps.data)"
                  v-tooltip.top="'Edit'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Add Product Modal Component -->
    <AddProductToStockModal
      ref="addProductModal"
      :pre-selected-stockage-id="parseInt(route.params.id)"
      :available-stockages="availableStockages"
      :service-abv="stockage?.service?.service_abv"
      @success="onAddProductSuccess"
      @error="onAddProductError"
    />

    <!-- Add Stock Modal -->
    <Dialog
      v-model:visible="showAddStockModal"
      modal
      header="Add Stock"
      :style="{ width: '500px' }"
      :closable="true"
      :dismissible-mask="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-p-2">
        <form @submit.prevent="addStock" class="tw-space-y-6">
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-plus-circle tw-text-green-500"></i>
              Additional Quantity *
            </label>
            <InputNumber
              v-model="stockAdjustment.quantity"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              min="1"
              placeholder="Enter quantity to add"
              required
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-comment tw-text-blue-500"></i>
              Notes
            </label>
            <Textarea
              v-model="stockAdjustment.notes"
              rows="3"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              placeholder="Optional notes about this stock addition"
            />
          </div>
        </form>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-4 tw-p-4 tw-bg-gray-50 tw-rounded-b-2xl">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text p-button-secondary tw-rounded-lg tw-shadow-sm"
            @click="closeAddStockModal"
          />
          <Button
            type="submit"
            label="Add Stock"
            icon="pi pi-check"
            class="p-button-success tw-rounded-lg tw-shadow-md tw-px-6"
            :disabled="isSubmitting"
            :loading="isSubmitting"
            @click="addStock"
          />
        </div>
      </template>
    </Dialog>

    <!-- Remove Stock Modal -->
    <Dialog
      v-model:visible="showRemoveStockModal"
      modal
      header="Remove Stock"
      :style="{ width: '500px' }"
      :closable="true"
      :dismissible-mask="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-p-2">
        <form @submit.prevent="removeStock" class="tw-space-y-6">
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-minus-circle tw-text-red-500"></i>
              Quantity to Remove *
            </label>
            <InputNumber
              v-model="stockAdjustment.quantity"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              :max="selectedInventory?.quantity || 0"
              min="1"
              placeholder="Enter quantity to remove"
              required
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-question-circle tw-text-orange-500"></i>
              Reason *
            </label>
            <Dropdown
              v-model="stockAdjustment.reason"
              :options="removalReasons"
              option-label="label"
              option-value="value"
              placeholder="Select Reason"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              required
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-comment tw-text-blue-500"></i>
              Notes
            </label>
            <Textarea
              v-model="stockAdjustment.notes"
              rows="3"
              class="tw-w-full tw-rounded-lg tw-shadow-sm"
              placeholder="Optional notes about this stock removal"
            />
          </div>
        </form>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-4 tw-p-4 tw-bg-gray-50 tw-rounded-b-2xl">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text p-button-secondary tw-rounded-lg tw-shadow-sm"
            @click="closeRemoveStockModal"
          />
          <Button
            type="submit"
            label="Remove Stock"
            icon="pi pi-check"
            class="p-button-danger tw-rounded-lg tw-shadow-md tw-px-6"
            :disabled="isSubmitting"
            :loading="isSubmitting"
            @click="removeStock"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';
import { useRoute } from 'vue-router';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';

// Custom Components
import AddProductToStockModal from '@/Components/Apps/stock/AddProductToStockModal.vue';

export default {
  name: 'StockageStock',
  components: {
    Card,
    Button,
    InputText,
    InputNumber,
    Dropdown,
    DataTable,
    Column,
    Dialog,
    Textarea,
    ProgressSpinner,
    Toast,
    AddProductToStockModal
  },
  data() {
    return {
      route: useRoute(),
      searchQuery: '',
      categoryFilter: '',
      stockStatusFilter: '',
      selectedInventory: null,
      submitSuccess: false,
      submitError: null,
      isSubmitting: false,
      searchTimeout: null,

      // Modals
      // showAddProductModal: false, // Now handled by AddProductToStockModal component
      showAddStockModal: false,
      showRemoveStockModal: false,
      showTransferModal: false,
      stockage: null,
      products: [],
      filteredProducts: [],
      availableStockages: [],
      categories: [],

      // Loading states
      loading: false,
      loadingStockages: false,

      // Pagination
      currentPage: 1,
      lastPage: 1,
      perPage: 10,
      total: 0,

      // Forms
      stockAdjustment: {
        quantity: null,
        reason: '',
        notes: ''
      },

      transferData: {
        quantity: null,
        destination_stockage_id: null,
        notes: ''
      },

      // Options
      stockStatusOptions: [
        { label: 'All', value: '' },
        { label: 'In Stock', value: 'in_stock' },
        { label: 'Low Stock', value: 'low_stock' },
        { label: 'Out of Stock', value: 'out_of_stock' }
      ],

      removalReasons: [
        { label: 'Used', value: 'used' },
        { label: 'Expired', value: 'expired' },
        { label: 'Damaged', value: 'damaged' },
        { label: 'Lost', value: 'lost' },
        { label: 'Other', value: 'other' }
      ],
    }
  },
  mounted() {
    this.fetchStockage();
    this.fetchProducts();
    this.fetchAvailableStockages();
    this.fetchCategories();
  },
  computed: {
    totalProducts() {
      return this.products.length;
    },
    totalQuantity() {
      return this.products.reduce((sum, item) => {
        // Use total units when available, otherwise derive from quantity and boite_de
        const units = this.parseQuantity(this.getItemTotalUnits(item) || 0);
        return sum + units;
      }, 0);
    },
    lowStockCount() {
      return this.products.filter(item => {
        const quantity = this.parseQuantity(this.getItemTotalUnits(item) || 0);
        // min_stock_level removed; treat missing min level as 0 (no low stock) or rely on product settings elsewhere
        const minLevel = 0;
        return quantity <= minLevel && quantity > 0;
      }).length;
    },
    expiringSoonCount() {
      const now = new Date();
      const thirtyDaysFromNow = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));
      return this.products.filter(item => {
        if (!item.expiry_date) return false;
        const expiryDate = new Date(item.expiry_date);
        return expiryDate <= thirtyDaysFromNow;
      }).length;
    }
  },
  methods: {
    async fetchStockage() {
      try {
        const response = await axios.get(`/api/stockages/${this.route.params.id}`);
        if (response.data.success) {
          this.stockage = response.data.data;
        }
      } catch (error) {
        this.submitError = 'Failed to load stockage details';
        setTimeout(() => {
          this.submitError = null;
        }, 5000);
      }
    },

    async fetchProducts(page = 1) {
      this.loading = true;
      try {
        const params = {
          page: page,
          per_page: this.perPage,
          stockage_id: this.route.params.id
        };

        if (this.searchQuery.trim()) {
          params.search = this.searchQuery;
        }

        const response = await axios.get('/api/inventory', { params });
        if (response.data.success) {
          this.products = response.data.data;
          this.currentPage = response.data.meta.current_page;
          this.lastPage = response.data.meta.last_page;
          this.total = response.data.meta.total;
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

    async fetchAvailableStockages() {
      this.loadingStockages = true;
      try {
        const response = await axios.get('/api/stockages');
        if (response.data.success) {
          this.availableStockages = response.data.data.filter(s => s.id !== parseInt(this.route.params.id));
        }
      } catch (error) {
        console.error('Failed to load stockages');
      } finally {
        this.loadingStockages = false;
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

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1;
        this.fetchProducts(1);
      }, 300);
    },

    onPage(event) {
      this.currentPage = event.page + 1;
      this.fetchProducts(this.currentPage);
    },

    applyFilters() {
      this.filterProducts();
    },

    clearFilters() {
      this.categoryFilter = '';
      this.stockStatusFilter = '';
      this.filterProducts();
    },

    filterProducts() {
      let filtered = [...this.products];

      // Filter by category
      if (this.categoryFilter) {
        filtered = filtered.filter(item => item.product?.category_id == this.categoryFilter);
      }

      // Filter by stock status
      if (this.stockStatusFilter) {
        switch (this.stockStatusFilter) {
          case 'in_stock':
            filtered = filtered.filter(item => {
              const quantity = this.parseQuantity(this.getItemTotalUnits(item) || 0);
              return quantity > 0;
            });
            break;
          case 'low_stock':
            filtered = filtered.filter(item => {
              const quantity = this.parseQuantity(this.getItemTotalUnits(item) || 0);
              const minLevel = 0; // legacy field removed
              return quantity <= minLevel && quantity > 0;
            });
            break;
          case 'out_of_stock':
            filtered = filtered.filter(item => {
              const quantity = this.parseQuantity(this.getItemTotalUnits(item) || 0);
              return quantity <= 0;
            });
            break;
        }
      }

      this.filteredProducts = filtered;
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

    openAddStockModal(inventory) {
      this.selectedInventory = inventory;
      this.showAddStockModal = true;
      this.resetStockAdjustment();
    },

    closeAddStockModal() {
      this.showAddStockModal = false;
      this.selectedInventory = null;
      this.resetStockAdjustment();
    },

    openRemoveStockModal(inventory) {
      this.selectedInventory = inventory;
      this.showRemoveStockModal = true;
      this.resetStockAdjustment();
    },

    closeRemoveStockModal() {
      this.showRemoveStockModal = false;
      this.selectedInventory = null;
      this.resetStockAdjustment();
    },

    openTransferModal(inventory) {
      this.selectedInventory = inventory;
      this.showTransferModal = true;
      this.resetTransferData();
    },

    closeTransferModal() {
      this.showTransferModal = false;
      this.selectedInventory = null;
      this.resetTransferData();
    },

    openEditModal(inventory) {
      this.selectedInventory = inventory;
      // TODO: Implement edit modal
    },





    resetStockAdjustment() {
      this.stockAdjustment = {
        quantity: null,
        reason: '',
        notes: ''
      };
    },

    resetTransferData() {
      this.transferData = {
        quantity: null,
        destination_stockage_id: null,
        notes: ''
      };
    },



    async addStock() {
      this.isSubmitting = true;
      this.submitError = null;

      try {
        const response = await axios.post(`/api/inventory/${this.selectedInventory.id}/adjust`, {
          quantity: this.stockAdjustment.quantity,
          type: 'add',
          notes: this.stockAdjustment.notes
        });

        if (response.data.success) {
          this.submitSuccess = 'Stock added successfully!';
          this.fetchProducts();
          this.closeAddStockModal();
          setTimeout(() => {
            this.submitSuccess = false;
          }, 3000);
        }
      } catch (error) {
        this.submitError = 'Failed to add stock';
      } finally {
        this.isSubmitting = false;
      }
    },

    async removeStock() {
      this.isSubmitting = true;
      this.submitError = null;

      try {
        const response = await axios.post(`/api/inventory/${this.selectedInventory.id}/adjust`, {
          quantity: -this.stockAdjustment.quantity,
          type: 'remove',
          reason: this.stockAdjustment.reason,
          notes: this.stockAdjustment.notes
        });

        if (response.data.success) {
          this.submitSuccess = 'Stock removed successfully!';
          this.fetchProducts();
          this.closeRemoveStockModal();
          setTimeout(() => {
            this.submitSuccess = false;
          }, 3000);
        }
      } catch (error) {
        this.submitError = 'Failed to remove stock';
      } finally {
        this.isSubmitting = false;
      }
    },



    exportStock() {
      // TODO: Implement export functionality
      this.submitSuccess = 'Export feature coming soon!';
      setTimeout(() => {
        this.submitSuccess = false;
      }, 3000);
    },

    // Helper methods
    getQuantityClass(quantity, productGroup) {
      // Parse quantity safely
      let qty = 0;
      if (quantity !== null && quantity !== undefined) {
        const qtyStr = String(quantity).replace(/,/g, '');
        qty = parseFloat(qtyStr) || 0;
      }

      // Determine thresholds from product settings if available
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

    // Return total units for an inventory item. Prefer explicit total_units when present.
    getItemTotalUnits(item) {
      if (!item) return 0;
      if (item.total_units !== null && item.total_units !== undefined) {
        return item.total_units;
      }

      // If product has boite_de (units per box) and quantity represents boxes
      const qty = this.parseQuantity(item.quantity || 0);
      const boite = item.product?.boite_de ? Number(item.product.boite_de) : null;
      if (boite) {
        return qty * boite;
      }

      return qty;
    },

    // Friendly display value: prefers total_units, otherwise derived units
    getItemTotalUnitsDisplay(item) {
      return this.getItemTotalUnits(item) || 0;
    },

    getExpiryClass(expiryDate) {
      const now = new Date();
      const expiry = new Date(expiryDate);
      const thirtyDaysFromNow = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));

      if (expiry <= now) return 'tw-text-red-600 tw-font-semibold';
      if (expiry <= thirtyDaysFromNow) return 'tw-text-yellow-600 tw-font-semibold';
      return 'tw-text-gray-600';
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
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 197, 253, 0.1)) !important;
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

.p-button.p-button-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
  border: none !important;
}

.p-button.p-button-success:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
}

.p-button.p-button-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
  border: none !important;
}

.p-button.p-button-danger:hover {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
}

/* Input customizations */
.p-inputtext,
.p-dropdown,
.p-inputnumber,
.p-textarea {
  border-radius: 8px !important;
  border: 2px solid #e5e7eb !important;
  transition: all 0.3s ease !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.p-inputtext:focus,
.p-dropdown:focus,
.p-inputnumber:focus,
.p-textarea:focus {
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

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-1.md\\:tw-grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
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

@media (max-width: 640px) {
  .tw-grid-cols-1.md\\:tw-grid-cols-4 {
    grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
  }
}
</style>
