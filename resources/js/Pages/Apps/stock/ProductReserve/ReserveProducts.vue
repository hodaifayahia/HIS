<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-indigo-50 tw-via-white tw-to-blue-50 tw-p-6">
    <!-- Success Toast -->
    <Toast position="top-right" />
    
    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Header Section with Reserve Info -->
    <div class="tw-mb-8">
      <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-600 tw-rounded-2xl tw-shadow-xl tw-p-8 tw-text-white">
        <div class="tw-flex tw-justify-between tw-items-start">
          <div>
            <div class="tw-flex tw-items-center tw-gap-4 tw-mb-3">
              <Button 
                icon="pi pi-arrow-left" 
                class="tw-bg-white/20 hover:tw-bg-white/30 tw-border-0 tw-text-white"
                @click="goBack"
              />
              <h1 class="tw-text-4xl tw-font-bold">
                Products for: {{ reserveInfo?.name || 'Reserve' }}
              </h1>
            </div>
            <p class="tw-text-indigo-100 tw-text-lg tw-ml-14">
              {{ reserveInfo?.description || 'Manage products for this reserve' }}
            </p>
          </div>
          <div class="tw-text-right">
            <div class="tw-bg-white/20 tw-rounded-xl tw-px-6 tw-py-3">
              <div class="tw-text-sm tw-text-indigo-100">Total Products</div>
              <div class="tw-text-3xl tw-font-bold">{{ total }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Actions -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-mb-6">
      <!-- Bulk Actions Toolbar -->
      <div v-if="selectedProducts.length > 0" class="tw-bg-indigo-50 tw-rounded-xl tw-p-4 tw-mb-4 tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-info-circle tw-text-indigo-600 tw-text-xl"></i>
          <span class="tw-font-semibold tw-text-gray-700">
            {{ selectedProducts.length }} item(s) selected
          </span>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button
            label="Bulk Confirm"
            icon="pi pi-check"
            class="tw-bg-green-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-2 hover:tw-bg-green-700"
            @click="bulkConfirm"
            :disabled="!hasPendingSelected"
          />
          <Button
            label="Bulk Cancel"
            icon="pi pi-times"
            class="tw-bg-orange-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-2 hover:tw-bg-orange-700"
            @click="openBulkCancelDialog"
            :disabled="!hasPendingSelected"
          />
          <Button
            label="Clear Selection"
            icon="pi pi-times-circle"
            class="tw-bg-gray-200 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-2 hover:tw-bg-gray-300"
            @click="selectedProducts = []"
          />
        </div>
      </div>

      <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4 tw-items-start lg:tw-items-center tw-justify-between">
        <!-- Search -->
        <div class="tw-flex-1 tw-max-w-md">
          <span class="p-input-icon-left tw-w-full">
            <i class="pi pi-search tw-text-gray-400" />
            <InputText 
              v-model="searchQuery"
              @input="onSearchInput"
              placeholder="Search products..." 
              class="tw-w-full tw-pl-10 tw-pr-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-rounded-xl focus:tw-border-indigo-500"
            />
          </span>
        </div>

        <!-- Status Filter -->
        <div class="tw-flex tw-gap-3">
          <Button
            :label="`All (${getStatusCount('')})`"
            :class="activeStatusFilter === 'all' ? 'tw-bg-indigo-600 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="setStatusFilter('all')"
          />
          <Button
            :label="`Pending (${getStatusCount('pending')})`"
            :class="activeStatusFilter === 'pending' ? 'tw-bg-yellow-500 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="setStatusFilter('pending')"
          />
          <Button
            :label="`Fulfilled (${getStatusCount('fulfilled')})`"
            :class="activeStatusFilter === 'fulfilled' ? 'tw-bg-green-500 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="setStatusFilter('fulfilled')"
          />
          <Button
            :label="`Cancelled (${getStatusCount('cancelled')})`"
            :class="activeStatusFilter === 'cancelled' ? 'tw-bg-red-500 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="setStatusFilter('cancelled')"
          />
        </div>

        <!-- Add Product Button -->
        <Button
          label="Add Product"
          icon="pi pi-plus"
          class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-xl tw-transition-all"
          @click="openAddDialog"
        />
      </div>
    </div>

    <!-- Products Table -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-overflow-hidden">
      <DataTable
        :value="filteredProducts"
        v-model:selection="selectedProducts"
        :loading="loading"
        :paginator="true"
        :rows="perPage"
        :totalRecords="total"
        :lazy="true"
        @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 25, 50, 100]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
        responsiveLayout="scroll"
        dataKey="id"
        class="tw-w-full"
      >
        <template #loading>
          <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-12">
            <i class="pi pi-spin pi-spinner tw-text-6xl tw-text-indigo-500 tw-mb-4"></i>
            <p class="tw-text-gray-500 tw-text-lg">Loading products...</p>
          </div>
        </template>

        <template #empty>
          <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
            <i class="pi pi-box tw-text-8xl tw-text-gray-300 tw-mb-4"></i>
            <p class="tw-text-gray-500 tw-text-xl tw-font-semibold tw-mb-2">No products found</p>
            <p class="tw-text-gray-400">Add your first product to this reserve</p>
          </div>
        </template>

        <Column selectionMode="multiple" headerStyle="width: 3rem" />

        <Column field="reservation_code" header="Code" style="width: 150px">
          <template #body="slotProps">
            <div class="tw-bg-indigo-100 tw-text-indigo-700 tw-px-3 tw-py-1 tw-rounded-lg tw-font-bold tw-text-center">
              {{ slotProps.data.reservation_code }}
            </div>
          </template>
        </Column>

        <Column header="Product" style="min-width: 300px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-gradient-to-br tw-from-indigo-400 tw-to-blue-500 tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-lg tw-shadow-md">
                <i class="pi pi-box"></i>
              </div>
              <div>
                <div class="tw-font-semibold tw-text-gray-800">
                  {{ slotProps.data.product?.name || slotProps.data.pharmacy_product?.name || 'N/A' }}
                </div>
                <div class="tw-text-sm tw-text-gray-500">
                  {{ slotProps.data.product ? 'Stock Product' : 'Pharmacy Product' }}
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column header="Stockage" style="min-width: 200px">
          <template #body="slotProps">
            <div class="tw-text-gray-700">
              {{ slotProps.data.stockage?.name || slotProps.data.pharmacy_stockage?.name || 'N/A' }}
            </div>
          </template>
        </Column>

        <Column field="quantity" header="Quantity" style="width: 120px">
          <template #body="slotProps">
            <div class="tw-font-bold tw-text-lg tw-text-indigo-600">
              {{ slotProps.data.quantity }}
            </div>
          </template>
        </Column>

        <Column field="status" header="Status" style="width: 150px">
          <template #body="slotProps">
            <Tag 
              :value="slotProps.data.status" 
              :severity="getStatusSeverity(slotProps.data.status)"
              :icon="getStatusIcon(slotProps.data.status)"
              class="tw-px-3 tw-py-1"
            />
          </template>
        </Column>

        <Column field="reserved_at" header="Reserved At" style="width: 200px">
          <template #body="slotProps">
            <div class="tw-text-sm tw-text-gray-600">
              {{ formatDateTime(slotProps.data.reserved_at) }}
            </div>
          </template>
        </Column>

        <Column header="Actions" style="width: 250px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button
                v-if="slotProps.data.status === 'pending'"
                icon="pi pi-check"
                class="tw-bg-green-500 tw-border-green-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-green-600"
                v-tooltip.top="'Fulfill'"
                @click="fulfillProduct(slotProps.data)"
              />
              
              <Button
                v-if="slotProps.data.status === 'pending'"
                icon="pi pi-times"
                class="tw-bg-orange-500 tw-border-orange-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-orange-600"
                v-tooltip.top="'Cancel'"
                @click="openCancelDialog(slotProps.data)"
              />
              
              <Button
                icon="pi pi-trash"
                class="tw-bg-red-500 tw-border-red-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-red-600"
                v-tooltip.top="'Delete'"
                @click="confirmDelete(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Add Product Dialog -->
    <Dialog
      v-model:visible="showAddDialog"
      modal
      header="Add Product to Reserve"
      :style="{ width: '60rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="addProduct" class="tw-p-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <!-- Product Type Selection -->
          <div class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Product Type
            </label>
            <div class="tw-flex tw-gap-4">
              <Button
                type="button"
                :label="'Stock Product'"
                :class="newProduct.product_type === 'stock' ? 'tw-bg-indigo-600 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
                class="tw-flex-1 tw-rounded-xl tw-py-3"
                @click="newProduct.product_type = 'stock'"
              />
              <Button
                type="button"
                :label="'Pharmacy Product'"
                :class="newProduct.product_type === 'pharmacy' ? 'tw-bg-indigo-600 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
                class="tw-flex-1 tw-rounded-xl tw-py-3"
                @click="newProduct.product_type = 'pharmacy'"
              />
            </div>
          </div>

          <!-- Stock Product -->
          <div v-if="newProduct.product_type === 'stock'" class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Product <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="newProduct.product_id"
              :options="products"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a product"
              :filter="true"
              class="tw-w-full"
              required
            />
          </div>

          <!-- Pharmacy Product -->
          <div v-if="newProduct.product_type === 'pharmacy'" class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Pharmacy Product <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="newProduct.pharmacy_product_id"
              :options="pharmacyProducts"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a pharmacy product"
              :filter="true"
              class="tw-w-full"
              required
            />
          </div>

          <!-- Stockage -->
          <div v-if="newProduct.product_type === 'stock'" class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Stockage <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="newProduct.stockage_id"
              :options="stockages"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a stockage"
              :filter="true"
              class="tw-w-full"
              required
            />
          </div>

          <!-- Pharmacy Stockage -->
          <div v-if="newProduct.product_type === 'pharmacy'" class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Pharmacy Stockage <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="newProduct.pharmacy_stockage_id"
              :options="pharmacyStockages"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a pharmacy stockage"
              :filter="true"
              class="tw-w-full"
              required
            />
          </div>

          <!-- Quantity -->
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Quantity <span class="tw-text-red-500">*</span>
            </label>
            <InputNumber
              v-model="newProduct.quantity"
              :min="1"
              showButtons
              class="tw-w-full"
              required
            />
          </div>

          <!-- Expires At -->
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Expires At
            </label>
            <Calendar
              v-model="newProduct.expires_at"
              showTime
              hourFormat="24"
              class="tw-w-full"
              placeholder="Optional expiry date"
            />
          </div>

          <!-- Notes -->
          <div class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Notes
            </label>
            <Textarea
              v-model="newProduct.reservation_notes"
              rows="3"
              class="tw-w-full"
              placeholder="Additional notes..."
            />
          </div>
        </div>

        <!-- Dialog Footer -->
        <div class="tw-flex tw-justify-end tw-gap-3 tw-mt-6">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="closeAddDialog"
          />
          <Button
            type="submit"
            label="Add Product"
            icon="pi pi-check"
            :loading="isSubmitting"
            class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
          />
        </div>
      </form>
    </Dialog>

    <!-- Cancel Dialog -->
    <Dialog
      v-model:visible="showCancelDialog"
      modal
      header="Cancel Product Reservation"
      :style="{ width: '40rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-p-6">
        <div class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Reason for Cancellation <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="cancelForm.reason"
            :options="cancelReasons"
            optionLabel="label"
            optionValue="value"
            placeholder="Select a reason"
            class="tw-w-full"
          />
        </div>

        <div v-if="cancelForm.reason === 'custom'" class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Custom Reason <span class="tw-text-red-500">*</span>
          </label>
          <Textarea
            v-model="cancelForm.custom_reason"
            rows="3"
            class="tw-w-full"
            placeholder="Please specify the reason..."
          />
        </div>

        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Close"
            icon="pi pi-times"
            class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="showCancelDialog = false"
          />
          <Button
            label="Confirm Cancellation"
            icon="pi pi-check"
            :loading="isSubmitting"
            class="tw-bg-red-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="cancelProduct"
          />
        </div>
      </div>
    </Dialog>

    <!-- Bulk Cancel Dialog -->
    <Dialog
      v-model:visible="showBulkCancelDialog"
      modal
      header="Bulk Cancel Reservations"
      :style="{ width: '40rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-p-6">
        <div class="tw-mb-4">
          <div class="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-lg tw-p-4">
            <p class="tw-text-sm tw-text-gray-700">
              You are about to cancel <strong>{{ selectedProducts.length }}</strong> product reservation(s).
            </p>
          </div>
        </div>

        <div class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Reason for Cancellation <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="bulkCancelForm.reason"
            :options="cancelReasons"
            optionLabel="label"
            optionValue="value"
            placeholder="Select a reason"
            class="tw-w-full"
          />
        </div>

        <div v-if="bulkCancelForm.reason === 'custom'" class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Custom Reason <span class="tw-text-red-500">*</span>
          </label>
          <Textarea
            v-model="bulkCancelForm.custom_reason"
            rows="3"
            class="tw-w-full"
            placeholder="Please specify the reason..."
          />
        </div>

        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Close"
            icon="pi pi-times"
            class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="showBulkCancelDialog = false"
          />
          <Button
            label="Confirm Bulk Cancellation"
            icon="pi pi-check"
            :loading="isSubmitting"
            class="tw-bg-red-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="bulkCancelSelected"
          />
        </div>
      </div>
    </Dialog>

    <!-- Fulfill Dialog -->
    <Dialog
      v-model:visible="showFulfillDialog"
      modal
      header="Fulfill Product Reservation"
      :style="{ width: '40rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-p-6">
        <div class="tw-mb-4">
          <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-4">
            <p class="tw-text-sm tw-text-gray-700">
              You are about to fulfill this product reservation.
            </p>
          </div>
        </div>

        <div class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Destination Service (Optional)
          </label>
          <Dropdown
            v-model="fulfillForm.destination_service_id"
            :options="services"
            optionLabel="name"
            optionValue="id"
            placeholder="Select destination service"
            :filter="true"
            class="tw-w-full"
            showClear
          />
          <small class="tw-text-gray-500 tw-mt-1 tw-block">Stock will transfer to this service on confirmation</small>
        </div>

        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="showFulfillDialog = false"
          />
          <Button
            label="Confirm Fulfillment"
            icon="pi pi-check"
            :loading="isSubmitting"
            class="tw-bg-green-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="confirmFulfillProduct"
          />
        </div>
      </div>
    </Dialog>

    <!-- Bulk Fulfill Dialog -->
    <Dialog
      v-model:visible="showBulkFulfillDialog"
      modal
      header="Bulk Fulfill Reservations"
      :style="{ width: '40rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <div class="tw-p-6">
        <div class="tw-mb-4">
          <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-4">
            <p class="tw-text-sm tw-text-gray-700">
              You are about to fulfill <strong>{{ selectedProducts.filter(p => p.status === 'pending').length }}</strong> product reservation(s).
            </p>
          </div>
        </div>

        <div class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Destination Service (Optional)
          </label>
          <Dropdown
            v-model="bulkFulfillForm.destination_service_id"
            :options="services"
            optionLabel="name"
            optionValue="id"
            placeholder="Select destination service"
            :filter="true"
            class="tw-w-full"
            showClear
          />
          <small class="tw-text-gray-500 tw-mt-1 tw-block">Stock will transfer to this service for all selected items</small>
        </div>

        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="showBulkFulfillDialog = false"
          />
          <Button
            label="Confirm Bulk Fulfillment"
            icon="pi pi-check"
            :loading="isSubmitting"
            class="tw-bg-green-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="confirmBulkFulfill"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useRouter } from 'vue-router';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

export default {
  name: 'ReserveProducts',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    InputNumber,
    Textarea,
    Dropdown,
    Calendar,
    Tag,
    Toast,
    ConfirmDialog
  },
  props: {
    reserveId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      products: [],
      pharmacyProducts: [],
      stockages: [],
      pharmacyStockages: [],
      services: [],
      reserveProducts: [],
      selectedProducts: [],
      reserveInfo: null,
      loading: false,
      showAddDialog: false,
      showCancelDialog: false,
      showBulkCancelDialog: false,
      showFulfillDialog: false,
      showBulkFulfillDialog: false,
      isSubmitting: false,
      searchQuery: '',
      searchTimeout: null,
      activeStatusFilter: 'all',
      currentPage: 1,
      perPage: 10,
      total: 0,
      newProduct: {
        product_type: 'stock',
        product_id: null,
        pharmacy_product_id: null,
        stockage_id: null,
        pharmacy_stockage_id: null,
        quantity: 1,
        expires_at: null,
        reservation_notes: '',
        source: 'manual'
      },
      cancelForm: {
        product_id: null,
        reason: '',
        custom_reason: ''
      },
      bulkCancelForm: {
        reason: '',
        custom_reason: ''
      },
      fulfillForm: {
        product_id: null,
        destination_service_id: null
      },
      bulkFulfillForm: {
        destination_service_id: null
      },
      cancelReasons: [
        { label: 'Patient cancelled', value: 'patient_cancelled' },
        { label: 'Out of stock', value: 'out_of_stock' },
        { label: 'Price issue', value: 'price_issue' },
        { label: 'Alternative chosen', value: 'alternative_chosen' },
        { label: 'Expired', value: 'expired' },
        { label: 'Duplicate', value: 'duplicate' },
        { label: 'Other', value: 'custom' }
      ]
    };
  },
  computed: {
    filteredProducts() {
      let filtered = this.reserveProducts;

      if (this.activeStatusFilter !== 'all') {
        filtered = filtered.filter(p => p.status === this.activeStatusFilter);
      }

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(product => {
          const productName = product.product?.name || product.pharmacy_product?.name || '';
          const code = product.reservation_code || '';
          return productName.toLowerCase().includes(query) || code.toLowerCase().includes(query);
        });
      }

      return filtered;
    },
    hasPendingSelected() {
      return this.selectedProducts.some(p => p.status === 'pending');
    }
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.router = useRouter();
    this.loadReserveInfo();
    this.loadReserveProducts();
    this.loadProducts();
    this.loadStockages();
    this.loadServices();
  },
  methods: {
    async loadReserveInfo() {
      try {
        const response = await axios.get(`/api/stock/reserves/${this.reserveId}`);
        this.reserveInfo = response.data;
      } catch (error) {
        console.error('Failed to load reserve info:', error);
      }
    },

    async loadReserveProducts(page = 1) {
      this.loading = true;
      try {
        const response = await axios.get('/api/product-reserves', {
          params: {
            reserve_id: this.reserveId,
            page: page,
            per_page: this.perPage
          }
        });

        this.reserveProducts = response.data.data || [];
        this.total = response.data.meta?.total || 0;
        this.currentPage = response.data.meta?.current_page || 1;
      } catch (error) {
        console.error('Failed to load products:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load products',
          life: 3000
        });
      } finally {
        this.loading = false;
      }
    },

    async loadProducts() {
      try {
        // Load stock products from /api/products
        const stockResponse = await axios.get('/api/products');
        this.products = stockResponse.data.data || stockResponse.data || [];
        
        // Load pharmacy products from /api/pharmacy/products
        const pharmacyResponse = await axios.get('/api/pharmacy/products');
        this.pharmacyProducts = pharmacyResponse.data.data || pharmacyResponse.data || [];
        
        console.log('Stock products loaded:', this.products.length);
        console.log('Pharmacy products loaded:', this.pharmacyProducts.length);
      } catch (error) {
        console.error('Failed to load products:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load products',
          life: 3000
        });
      }
    },

    async loadStockages() {
      try {
        // Load stock stockages from /api/stockages
        const stockageResponse = await axios.get('/api/stockages');
        this.stockages = stockageResponse.data.data || stockageResponse.data || [];
        
        // Load pharmacy stockages from /api/pharmacy/stockages
        const pharmacyStockageResponse = await axios.get('/api/pharmacy/stockages').catch(() => ({ data: { data: [] } }));
        this.pharmacyStockages = pharmacyStockageResponse.data.data || pharmacyStockageResponse.data || [];
        
        console.log('Stock stockages loaded:', this.stockages.length);
        console.log('Pharmacy stockages loaded:', this.pharmacyStockages.length);
      } catch (error) {
        console.error('Failed to load stockages:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load stockages',
          life: 3000
        });
      }
    },

    async loadServices() {
      try {
        const response = await axios.get('/api/services');
        this.services = response.data.data || response.data || [];
        console.log('Services loaded:', this.services.length);
      } catch (error) {
        console.error('Failed to load services:', error);
        // Don't show error toast as services are optional
      }
    },

    openAddDialog() {
      this.resetNewProduct();
      this.showAddDialog = true;
    },

    closeAddDialog() {
      this.showAddDialog = false;
      this.resetNewProduct();
    },

    resetNewProduct() {
      this.newProduct = {
        product_type: 'stock',
        product_id: null,
        pharmacy_product_id: null,
        stockage_id: null,
        pharmacy_stockage_id: null,
        quantity: 1,
        expires_at: null,
        reservation_notes: '',
        source: 'manual'
      };
    },

    async addProduct() {
      this.isSubmitting = true;
      try {
        const payload = {
          reserve_id: this.reserveId,
          quantity: this.newProduct.quantity,
          reservation_notes: this.newProduct.reservation_notes,
          source: this.newProduct.source
        };

        if (this.newProduct.product_type === 'stock') {
          payload.product_id = this.newProduct.product_id;
          payload.stockage_id = this.newProduct.stockage_id;
        } else {
          payload.pharmacy_product_id = this.newProduct.pharmacy_product_id;
          payload.pharmacy_stockage_id = this.newProduct.pharmacy_stockage_id;
        }

        if (this.newProduct.expires_at) {
          payload.expires_at = this.formatDateForAPI(this.newProduct.expires_at);
        }

        await axios.post('/api/product-reserves', payload);

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Product added successfully',
          life: 3000
        });

        this.closeAddDialog();
        this.loadReserveProducts(this.currentPage);
      } catch (error) {
        console.error('Failed to add product:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to add product',
          life: 3000
        });
      } finally {
        this.isSubmitting = false;
      }
    },

    openCancelDialog(product) {
      this.cancelForm = {
        product_id: product.id,
        reason: '',
        custom_reason: ''
      };
      this.showCancelDialog = true;
    },

    async cancelProduct() {
      if (!this.cancelForm.reason) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please select a reason',
          life: 3000
        });
        return;
      }

      if (this.cancelForm.reason === 'custom' && !this.cancelForm.custom_reason) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please specify a custom reason',
          life: 3000
        });
        return;
      }

      this.isSubmitting = true;
      try {
        const reason = this.cancelForm.reason === 'custom'
          ? this.cancelForm.custom_reason
          : this.cancelReasons.find(r => r.value === this.cancelForm.reason)?.label;

        await axios.post(`/api/product-reserves/${this.cancelForm.product_id}/cancel`, {
          cancel_reason: reason
        });

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Product reservation cancelled',
          life: 3000
        });

        this.showCancelDialog = false;
        this.loadReserveProducts(this.currentPage);
      } catch (error) {
        console.error('Failed to cancel product:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to cancel product',
          life: 3000
        });
      } finally {
        this.isSubmitting = false;
      }
    },

    fulfillProduct(product) {
      this.fulfillForm = {
        product_id: product.id,
        destination_service_id: null
      };
      this.showFulfillDialog = true;
    },

    async confirmFulfillProduct() {
      this.isSubmitting = true;
      try {
        const payload = {};
        if (this.fulfillForm.destination_service_id) {
          payload.destination_service_id = this.fulfillForm.destination_service_id;
        }

        await axios.post(`/api/product-reserves/${this.fulfillForm.product_id}/fulfill`, payload);

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Product marked as fulfilled',
          life: 3000
        });

        this.showFulfillDialog = false;
        this.loadReserveProducts(this.currentPage);
      } catch (error) {
        console.error('Failed to fulfill product:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to fulfill product',
          life: 3000
        });
      } finally {
        this.isSubmitting = false;
      }
    },

    confirmDelete(product) {
      this.confirm.require({
        message: `Delete this product reservation?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/product-reserves/${product.id}`);

            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Product deleted successfully',
              life: 3000
            });

            this.loadReserveProducts(this.currentPage);
          } catch (error) {
            console.error('Failed to delete product:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to delete product',
              life: 3000
            });
          }
        }
      });
    },

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.loadReserveProducts(1);
      }, 300);
    },

    onPage(event) {
      this.currentPage = event.page + 1;
      this.perPage = event.rows;
      this.loadReserveProducts(this.currentPage);
    },

    setStatusFilter(status) {
      this.activeStatusFilter = status;
    },

    getStatusCount(status) {
      if (!status || status === '') {
        return this.reserveProducts.length;
      }
      return this.reserveProducts.filter(p => p.status === status).length;
    },

    getStatusSeverity(status) {
      const severities = {
        pending: 'warning',
        fulfilled: 'success',
        cancelled: 'danger',
        expired: 'secondary'
      };
      return severities[status] || 'info';
    },

    getStatusIcon(status) {
      const icons = {
        pending: 'pi pi-clock',
        fulfilled: 'pi pi-check-circle',
        cancelled: 'pi pi-times-circle',
        expired: 'pi pi-exclamation-triangle'
      };
      return icons[status] || 'pi pi-info-circle';
    },

    formatDateTime(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatDateForAPI(date) {
      if (!date) return null;
      if (typeof date === 'string') return date;
      const d = new Date(date);
      return d.toISOString();
    },

    bulkConfirm() {
      const pendingProducts = this.selectedProducts.filter(p => p.status === 'pending');
      
      if (pendingProducts.length === 0) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'No pending products selected',
          life: 3000
        });
        return;
      }

      this.bulkFulfillForm = {
        destination_service_id: null
      };
      this.showBulkFulfillDialog = true;
    },

    async confirmBulkFulfill() {
      this.isSubmitting = true;
      try {
        const pendingProducts = this.selectedProducts.filter(p => p.status === 'pending');
        const ids = pendingProducts.map(p => p.id);
        
        const payload = { ids };
        if (this.bulkFulfillForm.destination_service_id) {
          payload.destination_service_id = this.bulkFulfillForm.destination_service_id;
        }

        const response = await axios.post('/api/product-reserves/bulk-fulfill', payload);

        const { success, failed } = response.data;

        if (success.length > 0) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${success.length} product(s) fulfilled successfully`,
            life: 3000
          });
        }

        if (failed.length > 0) {
          this.toast.add({
            severity: 'error',
            summary: 'Partial Failure',
            detail: `${failed.length} product(s) failed to fulfill`,
            life: 5000
          });
        }

        this.showBulkFulfillDialog = false;
        this.selectedProducts = [];
        this.loadReserveProducts(this.currentPage);
      } catch (error) {
        console.error('Failed to bulk confirm:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to confirm products',
          life: 3000
        });
      } finally {
        this.isSubmitting = false;
      }
    },

    openBulkCancelDialog() {
      const pendingProducts = this.selectedProducts.filter(p => p.status === 'pending');
      
      if (pendingProducts.length === 0) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'No pending products selected',
          life: 3000
        });
        return;
      }

      this.bulkCancelForm = {
        reason: '',
        custom_reason: ''
      };
      this.showBulkCancelDialog = true;
    },

    async bulkCancelSelected() {
      if (!this.bulkCancelForm.reason) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please select a reason',
          life: 3000
        });
        return;
      }

      if (this.bulkCancelForm.reason === 'custom' && !this.bulkCancelForm.custom_reason) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please specify a custom reason',
          life: 3000
        });
        return;
      }

      this.isSubmitting = true;
      try {
        const pendingProducts = this.selectedProducts.filter(p => p.status === 'pending');
        const ids = pendingProducts.map(p => p.id);
        
        const reason = this.bulkCancelForm.reason === 'custom'
          ? this.bulkCancelForm.custom_reason
          : this.cancelReasons.find(r => r.value === this.bulkCancelForm.reason)?.label;

        const response = await axios.post('/api/product-reserves/bulk-cancel', {
          ids,
          cancel_reason: reason
        });

        const { success, failed } = response.data;

        if (success.length > 0) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${success.length} product(s) cancelled successfully`,
            life: 3000
          });
        }

        if (failed.length > 0) {
          this.toast.add({
            severity: 'error',
            summary: 'Partial Failure',
            detail: `${failed.length} product(s) failed to cancel`,
            life: 5000
          });
        }

        this.showBulkCancelDialog = false;
        this.selectedProducts = [];
        this.loadReserveProducts(this.currentPage);
      } catch (error) {
        console.error('Failed to bulk cancel:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to cancel products',
          life: 3000
        });
      } finally {
        this.isSubmitting = false;
      }
    },

    goBack() {
      this.router.push({ name: 'stock.product-reserves' });
    }
  }
};
</script>

<style scoped>
/* Custom styles */
.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #eef2ff !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr {
  transition: all 0.2s ease;
}

.p-button {
  transition: all 0.3s ease !important;
}

.p-button:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(79, 70, 229, 0.15) !important;
}

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

.p-datatable .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  border: none !important;
  padding: 1rem !important;
}
</style>
