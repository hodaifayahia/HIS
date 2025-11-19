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
      header="Add Pharmacy Product to Reserve"
      :style="{ width: '70rem', maxWidth: '95vw' }"
      :closable="true"
      class="tw-rounded-3xl tw-shadow-2xl"
      :dismissibleMask="true"
    >
      <div class="tw-p-8">
        <div class="tw-mb-6">
          <div class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-blue-50 tw-rounded-2xl tw-p-6 tw-border tw-border-indigo-100">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-2">
              <div class="tw-bg-indigo-500 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                <i class="pi pi-info-circle tw-text-white tw-text-sm"></i>
              </div>
              <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Product Selection Guide</h3>
            </div>
            <p class="tw-text-gray-600 tw-ml-11">
              First select a pharmacy product, then choose from available stockages that contain that product.
              The quantity will be validated against available stock.
            </p>
          </div>
        </div>

        <form @submit.prevent="addProduct">
            <!-- Pharmacy Product with Virtual Scroll -->
            <div class="tw-space-y-6">
              <div>
                <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-box tw-text-indigo-500"></i>
                  Pharmacy Product <span class="tw-text-red-500">*</span>
                </label>
              <AutoComplete
                v-model="selectedProductName"
                :suggestions="filteredPharmacyProducts"
                @complete="searchPharmacyProducts"
                @item-select="onProductSelect"
                field="name"
                placeholder="Search and select a pharmacy product"
                :dropdown="true"
                :virtualScrollerOptions="{ 
                  itemSize: 60,
                  lazy: true,
                  onLazyLoad: onProductLazyLoad,
                  showLoader: true,
                  loading: loadingProducts
                }"
                class="tw-w-full tw-transition-all tw-duration-300"
                :class="{ 'p-invalid': !newProduct.pharmacy_product_id && formSubmitted }"
                :minLength="0"
                forceSelection
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-4 tw-p-1 tw-rounded-lg hover:tw-bg-indigo-50 tw-transition-all tw-duration-200 tw-cursor-pointer tw-group">
                    <div class="tw-relative">
                      <div class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-blue-600 tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-shadow-lg group-hover:tw-shadow-xl group-hover:tw-scale-105 tw-transition-all tw-duration-200">
                        <i class="pi pi-box tw-text-lg"></i>
                      </div>
                      <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-500 tw-rounded-full tw-border-2 tw-border-white tw-shadow-sm"></div>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                      <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                        <div class="tw-font-bold tw-text-gray-900 tw-text-base tw-truncate">{{ slotProps.option.name }}</div>
                        <div class="tw-bg-indigo-100 tw-text-indigo-700 tw-px-2 tw-py-0.5 tw-rounded-md tw-text-xs tw-font-medium tw-shrink-0">
                          {{ slotProps.option.code || 'N/A' }}
                        </div>
                      </div>
                      <div class="tw-flex tw-items-center tw-gap-4 tw-text-sm tw-text-gray-600">
                        <span class="tw-flex tw-items-center tw-gap-1">
                          <i class="pi pi-tag tw-text-xs"></i>
                          Pharmacy Product
                        </span>
                        <span v-if="slotProps.option.category" class="tw-flex tw-items-center tw-gap-1">
                          <i class="pi pi-folder tw-text-xs"></i>
                          {{ slotProps.option.category }}
                        </span>
                      </div>
                    </div>
                    <div class="tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-200">
                      <i class="pi pi-chevron-right tw-text-indigo-400 tw-text-sm"></i>
                    </div>
                  </div>
                </template>
                <template #empty>
                  <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-8 tw-px-4">
                    <div class="tw-bg-gray-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mb-4">
                      <i class="pi pi-search tw-text-2xl tw-text-gray-400"></i>
                    </div>
                    <p class="tw-text-gray-500 tw-text-base tw-font-medium tw-mb-1">
                      {{ loadingProducts ? 'Searching products...' : 'No products found' }}
                    </p>
                    <p class="tw-text-gray-400 tw-text-sm tw-text-center">
                      {{ loadingProducts ? 'Please wait while we search' : 'Try adjusting your search terms' }}
                    </p>
                  </div>
                </template>
              </AutoComplete>
              <small v-if="!newProduct.pharmacy_product_id && formSubmitted" class="p-error tw-block tw-mt-1">
                Product is required
              </small>
              </div>
            </div>

          <!-- Pharmacy Stockage with Virtual Scroll -->
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Stockage <span class="tw-text-red-500">*</span>
            </label>
            <div class="tw-relative">
              <AutoComplete
                v-model="selectedStockageName"
                :suggestions="filteredPharmacyStockages"
                @complete="searchPharmacyStockages"
                @item-select="onStockageSelect"
                @dropdown="handleStockageDropdown"
                field="name"
                :placeholder="newProduct.pharmacy_product_id ? 'Search and select a pharmacy stockage' : 'Please select a product first'"
                :dropdown="true"
                :disabled="!newProduct.pharmacy_product_id"
                :virtualScrollerOptions="{ 
                  itemSize: 70,
                  lazy: true,
                  onLazyLoad: onStockageLazyLoad,
                  showLoader: true,
                  loading: loadingStockages
                }"
                class="tw-w-full tw-transition-all tw-duration-300"
                :class="{ 
                  'p-invalid': !newProduct.pharmacy_stockage_id && formSubmitted,
                  'p-disabled': !newProduct.pharmacy_product_id
                }"
                :minLength="0"
                forceSelection
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-4 tw-p-1 tw-rounded-lg hover:tw-bg-purple-50 tw-transition-all tw-duration-200 tw-cursor-pointer tw-group">
                    <div class="tw-relative">
                      <div class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-pink-600 tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-shadow-lg group-hover:tw-shadow-xl group-hover:tw-scale-105 tw-transition-all tw-duration-200">
                        <i class="pi pi-database tw-text-lg"></i>
                      </div>
                      <div v-if="slotProps.option.available_quantity > 0" class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-500 tw-rounded-full tw-border-2 tw-border-white tw-shadow-sm"></div>
                      <div v-else class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-red-500 tw-rounded-full tw-border-2 tw-border-white tw-shadow-sm"></div>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                      <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                        <div class="tw-font-bold tw-text-gray-900 tw-text-base tw-truncate tw-mr-3">{{ slotProps.option.name }}</div>
                        <div v-if="slotProps.option.available_quantity !== undefined && slotProps.option.available_quantity !== null" 
                             :class="slotProps.option.available_quantity > 0 
                               ? 'tw-bg-green-100 tw-text-green-700 tw-border-green-200' 
                               : 'tw-bg-red-100 tw-text-red-700 tw-border-red-200'"
                             class="tw-border tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-bold tw-shrink-0 tw-flex tw-items-center tw-gap-1">
                          <i class="pi pi-box tw-text-xs"></i>
                          {{ slotProps.option.available_quantity }} available
                        </div>
                      </div>
                      <div class="tw-flex tw-items-center tw-gap-4 tw-text-sm tw-text-gray-600 tw-mb-1">
                        <span class="tw-flex tw-items-center tw-gap-1">
                          <i class="pi pi-tag tw-text-xs"></i>
                          {{ slotProps.option.code || 'N/A' }}
                        </span>
                        <span v-if="slotProps.option.inventory_items" class="tw-flex tw-items-center tw-gap-1">
                          <i class="pi pi-list tw-text-xs"></i>
                          {{ slotProps.option.inventory_items }} batch(es)
                        </span>
                        <span v-if="slotProps.option.type" class="tw-flex tw-items-center tw-gap-1">
                          <i class="pi pi-cog tw-text-xs"></i>
                          {{ slotProps.option.type }}
                        </span>
                      </div>
                      <div v-if="slotProps.option.description" class="tw-text-xs tw-text-gray-500 tw-truncate">
                        {{ slotProps.option.description }}
                      </div>
                    </div>
                    <div class="tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-200">
                      <i class="pi pi-chevron-right tw-text-purple-400 tw-text-sm"></i>
                    </div>
                  </div>
                </template>
                <template #empty>
                  <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-8 tw-px-4">
                    <div class="tw-bg-gray-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mb-4">
                      <i class="pi pi-database tw-text-2xl tw-text-gray-400"></i>
                    </div>
                    <p class="tw-text-gray-500 tw-text-base tw-font-medium tw-mb-1">
                      {{ loadingStockages ? 'Loading stockages...' : (newProduct.pharmacy_product_id ? 'No stockages found with this product' : 'Please select a product first') }}
                    </p>
                    <p class="tw-text-gray-400 tw-text-sm tw-text-center">
                      {{ loadingStockages ? 'Please wait while we load stockages' : (newProduct.pharmacy_product_id ? 'This product is not available in any stockage' : 'Select a product to see available stockages') }}
                    </p>
                  </div>
                </template>
              </AutoComplete>
              <small v-if="!newProduct.pharmacy_product_id" class="tw-block tw-mt-1 tw-text-gray-500">
                <i class="pi pi-info-circle tw-text-xs"></i> Select a product first to see available stockages
              </small>
              <small v-else-if="!newProduct.pharmacy_stockage_id && formSubmitted" class="p-error tw-block tw-mt-1">
                Stockage is required
              </small>
              <small v-else-if="filteredPharmacyStockages.length > 0" class="tw-block tw-mt-1 tw-text-green-600">
                <i class="pi pi-check-circle tw-text-xs"></i> {{ filteredPharmacyStockages.length }} stockage(s) available 
                <span v-if="getTotalAvailableQuantity() > 0" class="tw-font-semibold">
                  (Total: {{ getTotalAvailableQuantity() }} units)
                </span>
              </small>
              <small v-else-if="newProduct.pharmacy_product_id && filteredPharmacyStockages.length === 0" class="tw-block tw-mt-1 tw-text-orange-600">
                <i class="pi pi-exclamation-triangle tw-text-xs"></i> No stockages found with this product
              </small>
            </div>
          </div>

          <!-- Quantity -->
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Quantity <span class="tw-text-red-500">*</span>
            </label>
            <InputNumber
              v-model="newProduct.quantity"
              :min="1"
              :max="getSelectedStockageQuantity()"
              showButtons
              class="tw-w-full"
              required
            />
            <small v-if="getSelectedStockageQuantity() > 0" class="tw-block tw-mt-1 tw-text-gray-600">
              <i class="pi pi-info-circle tw-text-xs"></i> Maximum available: {{ getSelectedStockageQuantity() }} units
            </small>
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
          <div>
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
        </form>
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
            label="Add Product"
            icon="pi pi-check"
            :loading="isSubmitting"
            class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
            @click="addProduct"
          />
        </div>
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
              You are about to fulfill this product reservation from Central Pharmacy.
            </p>
          </div>
        </div>

        <div class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Central Pharmacy Stockage
          </label>
          <Dropdown
            v-model="fulfillForm.pharmacy_stockage_id"
            :options="centralPharmacyStockages"
            optionLabel="name"
            optionValue="id"
            placeholder="Select Central Pharmacy stockage"
            :filter="true"
            class="tw-w-full"
          />
          <small class="tw-text-gray-500 tw-mt-1 tw-block">Select the stockage where this product will be withdrawn from</small>
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
              You are about to fulfill <strong>{{ selectedProducts.filter(p => p.status === 'pending').length }}</strong> product reservation(s) from Central Pharmacy.
            </p>
          </div>
        </div>

        <div class="tw-mb-6">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Central Pharmacy Stockage
          </label>
          <Dropdown
            v-model="bulkFulfillForm.pharmacy_stockage_id"
            :options="centralPharmacyStockages"
            optionLabel="name"
            optionValue="id"
            placeholder="Select Central Pharmacy stockage"
            :filter="true"
            class="tw-w-full"
          />
          <small class="tw-text-gray-500 tw-mt-1 tw-block">Select the stockage where these products will be withdrawn from</small>
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
import AutoComplete from 'primevue/autocomplete';
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
    AutoComplete,
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
      allPharmacyProducts: [], // Store all products (actually stock products in this component)
      filteredPharmacyProducts: [],
      stockages: [],
      pharmacyStockages: [],
      allPharmacyStockages: [], // Store all stockages (actually stock stockages in this component)
      filteredPharmacyStockages: [],
      productStockages: null, // Store stockages for currently selected product
      productStockagesProductId: null, // Track the product id for cached stockages
      services: [],
      reserveProducts: [],
      selectedProducts: [],
      reserveInfo: null,
      loading: false,
      loadingProducts: false,
      loadingStockages: false,
      showAddDialog: false,
      showCancelDialog: false,
      showBulkCancelDialog: false,
      showFulfillDialog: false,
      showBulkFulfillDialog: false,
      isSubmitting: false,
      formSubmitted: false,
      searchQuery: '',
      searchTimeout: null,
      activeStatusFilter: 'all',
      currentPage: 1,
      perPage: 10,
      total: 0,
      selectedProductName: '',
      selectedStockageName: '',
      productPage: 1,
      productHasMore: true,
      stockagePage: 1,
      stockageHasMore: true,
      productSearchQuery: '',
      stockageSearchQuery: '',
      newProduct: {
        pharmacy_product_id: null,
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
        pharmacy_stockage_id: null,
        destination_service_id: null
      },
      bulkFulfillForm: {
        pharmacy_stockage_id: null,
        destination_service_id: null
      },
      centralPharmacyStockages: [],
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

    async loadProducts(page = 1, search = '') {
      this.loadingProducts = true;
      try {
        // Load pharmacy products from /api/products with pagination
        const pharmacyResponse = await axios.get('/api/products', {
          params: {
            page: page,
            per_page: 50,
            search: search
          }
        });
        
        const pharmacyData = pharmacyResponse.data.data || pharmacyResponse.data || [];
        
        if (page === 1) {
          this.allPharmacyProducts = pharmacyData;
          this.pharmacyProducts = pharmacyData;
        } else {
          this.allPharmacyProducts = [...this.allPharmacyProducts, ...pharmacyData];
          this.pharmacyProducts = this.allPharmacyProducts;
        }
        
        // Check if there are more products to load
        const meta = pharmacyResponse.data.meta;
        if (meta) {
          this.productHasMore = meta.current_page < meta.last_page;
        } else {
          this.productHasMore = pharmacyData.length >= 50;
        }
        
        console.log('Pharmacy products loaded:', this.allPharmacyProducts.length);
      } catch (error) {
        console.error('Failed to load products:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load products',
          life: 3000
        });
      } finally {
        this.loadingProducts = false;
      }
    },

    async loadStockages(page = 1, search = '') {
      this.loadingStockages = true;
      try {
        // Load pharmacy stockages from /api/stockages
        const pharmacyStockageResponse = await axios.get('/api/stockages', {
          params: {
            page: page,
            per_page: 50,
            search: search
          }
        }).catch(() => ({ data: { data: [] } }));
        
        const pharmacyData = pharmacyStockageResponse.data.data || pharmacyStockageResponse.data || [];
        
        if (page === 1) {
          this.allPharmacyStockages = pharmacyData;
          this.pharmacyStockages = pharmacyData;
          if (this.newProduct.pharmacy_product_id &&
              this.productStockages &&
              this.productStockagesProductId === this.newProduct.pharmacy_product_id) {
            this.filteredPharmacyStockages = [...this.productStockages];
          } else if (!this.newProduct.pharmacy_product_id) {
            this.filteredPharmacyStockages = [];
          }
        } else {
          this.allPharmacyStockages = [...this.allPharmacyStockages, ...pharmacyData];
          this.pharmacyStockages = this.allPharmacyStockages;
          if (this.newProduct.pharmacy_product_id &&
              this.productStockages &&
              this.productStockagesProductId === this.newProduct.pharmacy_product_id) {
            this.filteredPharmacyStockages = [...this.productStockages];
          }
        }
        
        // Check if there are more stockages to load
        const meta = pharmacyStockageResponse.data.meta;
        if (meta) {
          this.stockageHasMore = meta.current_page < meta.last_page;
        } else {
          this.stockageHasMore = pharmacyData.length >= 50;
        }
        
        // Also load stock stockages for other purposes
        const stockageResponse = await axios.get('/api/stockages');
        this.stockages = stockageResponse.data.data || stockageResponse.data || [];
        
        // Filter for Central Pharmacy stockages from pharmacy stockages only
        this.centralPharmacyStockages = this.allPharmacyStockages.filter(stockage => {
          const name = (stockage.name || '').toLowerCase();
          const code = (stockage.code || '').toLowerCase();
          
          return name.includes('central') || 
                 name.includes('pharmacy central') ||
                 name.includes('pharmacie centrale') ||
                 name.includes('pharmacie central') ||
                 name.includes('central pharmacy') ||
                 name.includes('depot central') ||
                 name.includes('dépôt central') ||
                 name.includes('stock central') ||
                 code.includes('central');
        });
        
        console.log('Pharmacy stockages loaded:', this.allPharmacyStockages.length);
        console.log('Central Pharmacy stockages:', this.centralPharmacyStockages.length, this.centralPharmacyStockages.map(s => s.name));
      } catch (error) {
        console.error('Failed to load stockages:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load stockages',
          life: 3000
        });
      } finally {
        this.loadingStockages = false;
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
      this.formSubmitted = false;
      this.productPage = 1;
      this.stockagePage = 1;
      this.productSearchQuery = '';
      this.stockageSearchQuery = '';
      // Initialize with server data
      this.filteredPharmacyProducts = [...this.allPharmacyProducts.slice(0, 50)];
      // Stockages are shown only after a product is selected
      this.filteredPharmacyStockages = [];
      this.showAddDialog = true;
    },

    closeAddDialog() {
      this.showAddDialog = false;
      this.resetNewProduct();
      this.formSubmitted = false;
      this.selectedProductName = '';
      this.selectedStockageName = '';
    },

    resetNewProduct() {
      this.newProduct = {
        pharmacy_product_id: null,
        pharmacy_stockage_id: null,
        quantity: 1,
        expires_at: null,
        reservation_notes: '',
        source: 'manual'
      };
      this.selectedProductName = '';
      this.selectedStockageName = '';
      this.productStockages = null; // Clear cached stockages
      this.productStockagesProductId = null;
      this.filteredPharmacyStockages = [];
    },

    async searchPharmacyProducts(event) {
      const originalQuery = event.query || '';
      const query = originalQuery.trim();
      this.productSearchQuery = query;
      
      if (!query) {
        this.filteredPharmacyProducts = [...this.allPharmacyProducts.slice(0, 50)];
        return;
      }
      
      // Search directly from server to get all matching products
      this.loadingProducts = true;
      try {
        const response = await axios.get('/api/products', {
          params: {
            search: query, // Send original query (not lowercased) to backend
            per_page: 100, // Get more results for search
            page: 1,
            is_clinical: true
          }
        });
        
        const searchResults = response.data.data || response.data || [];
        this.filteredPharmacyProducts = searchResults;
        
        console.log(`Found ${searchResults.length} products matching "${query}"`);
        
        if (searchResults.length === 0) {
          console.log('No products found for query:', query);
        }
      } catch (error) {
        console.error('Failed to search products:', error);
        // Fallback to local search if API fails
        const lowerQuery = query.toLowerCase();
        this.filteredPharmacyProducts = this.allPharmacyProducts.filter(product => {
          const name = (product.name || '').toLowerCase();
          const code = (product.code || '').toLowerCase();
          return name.includes(lowerQuery) || code.includes(lowerQuery);
        });
      } finally {
        this.loadingProducts = false;
      }
    },

    async loadMoreProducts(search = '') {
      if (!this.productHasMore || this.loadingProducts) return;
      
      this.productPage++;
      await this.loadProducts(this.productPage, search || this.productSearchQuery);
      
      // Re-filter after loading more
      if (this.productSearchQuery) {
        this.filteredPharmacyProducts = this.allPharmacyProducts.filter(product => {
          const name = (product.name || '').toLowerCase();
          const code = (product.code || '').toLowerCase();
          return name.includes(this.productSearchQuery) || code.includes(this.productSearchQuery);
        });
      } else {
        this.filteredPharmacyProducts = [...this.allPharmacyProducts];
      }
    },

    onProductLazyLoad(event) {
      // Load more products when scrolling
      if (event.last >= this.filteredPharmacyProducts.length - 10) {
        this.loadMoreProducts();
      }
    },

    async onProductSelect(event) {
      if (event.value && event.value.id) {
        const productId = event.value.id;
        this.newProduct.pharmacy_product_id = productId;
        this.selectedProductName = event.value.name;
        
        // Reset stockage selection when product changes
        this.newProduct.pharmacy_stockage_id = null;
        this.selectedStockageName = '';
        
        // Clear the cached product stockages
        this.productStockages = null;
        this.productStockagesProductId = productId;
        this.filteredPharmacyStockages = [];
        
        // Load stockages that contain this product
        await this.loadStockagesForProduct(productId);
      }
    },

    async loadStockagesForProduct(productId) {
      if (!productId) {
        this.productStockages = null;
        this.productStockagesProductId = null;
        this.filteredPharmacyStockages = [];
        return;
      }

      this.loadingStockages = true;
      this.filteredPharmacyStockages = [];
      try {
        // First, try to get inventory data for this product
        const inventoryResponse = await axios.get('/api/inventory', {
          params: {
            product_id: productId,
            per_page: 1000,
            exclude_zero: false  // Include items with zero quantity
          }
        });
        
        const inventory = inventoryResponse.data.data || inventoryResponse.data || [];
        
        console.log(`Raw inventory for product ${productId}:`, inventory.length, 'items');
        
        if (inventory.length === 0) {
          this.productStockages = [];
          this.productStockagesProductId = productId;
          this.filteredPharmacyStockages = [];
          this.toast.add({
            severity: 'warn',
            summary: 'No Stockages Found',
            detail: 'This product is not available in any stockage',
            life: 4000
          });
          return;
        }
        
        // Group inventory by stockage and calculate total quantity
        const stockageMap = new Map();
        
        inventory.forEach(item => {
          const stockageId = item.stockage_id;
          const quantity = parseFloat(item.quantity) || 0;
          
          if (stockageMap.has(stockageId)) {
            const existing = stockageMap.get(stockageId);
            existing.total_quantity += quantity;
            existing.items.push(item);
          } else {
            stockageMap.set(stockageId, {
              stockage_id: stockageId,
              total_quantity: quantity,
              items: [item],
              stockage: item.stockage || null
            });
          }
        });
        
        console.log(`Grouped into ${stockageMap.size} unique stockages`);
        
        // Get unique stockage IDs that need to be fetched
        const stockageIds = Array.from(stockageMap.keys());
        const missingStockageIds = stockageIds.filter(id => {
          const stockageData = stockageMap.get(id);
          return !stockageData.stockage;
        });
        
        // Fetch missing stockage details if needed
        if (missingStockageIds.length > 0) {
          console.log(`Fetching details for ${missingStockageIds.length} stockages...`);
          try {
            const stockageResponse = await axios.get('/api/stockages', {
              params: {
                per_page: 1000
              }
            });
            const allStockages = stockageResponse.data.data || stockageResponse.data || [];
            
            // Update stockage data in the map
            allStockages.forEach(stockage => {
              if (stockageMap.has(stockage.id)) {
                const data = stockageMap.get(stockage.id);
                data.stockage = stockage;
              }
            });
          } catch (error) {
            console.error('Failed to fetch stockage details:', error);
          }
        }
        
        // Convert map to array and merge with stockage details
        const stockagesWithQuantity = [];
        
        for (const [stockageId, data] of stockageMap) {
          // Use stockage from inventory data or from loaded stockages
          let stockage = data.stockage;
          
          if (!stockage) {
            // Try to find from previously loaded stockages
            stockage = this.allPharmacyStockages.find(s => s.id === stockageId);
          }
          
          if (stockage) {
            stockagesWithQuantity.push({
              id: stockage.id,
              name: stockage.name,
              code: stockage.code,
              description: stockage.description,
              type: stockage.type,
              service_id: stockage.service_id,
              available_quantity: data.total_quantity,
              inventory_items: data.items.length
            });
          } else {
            // Create a minimal stockage object if we still don't have details
            console.warn(`Stockage ${stockageId} not found, creating minimal object`);
            stockagesWithQuantity.push({
              id: stockageId,
              name: `Stockage #${stockageId}`,
              code: `STK-${stockageId}`,
              description: 'Details unavailable',
              available_quantity: data.total_quantity,
              inventory_items: data.items.length
            });
          }
        }
        
        // Sort by quantity descending
        stockagesWithQuantity.sort((a, b) => b.available_quantity - a.available_quantity);
        
        // Cache the stockages for this product
  this.productStockages = stockagesWithQuantity;
  this.productStockagesProductId = productId;
  this.filteredPharmacyStockages = stockagesWithQuantity;
        
        console.log(`Stockages with product ${productId}:`, stockagesWithQuantity.length);
        console.log('Stockages with quantities:', stockagesWithQuantity.map(s => ({ 
          name: s.name, 
          quantity: s.available_quantity 
        })));
        
        if (stockagesWithQuantity.length === 0) {
          this.toast.add({
            severity: 'warn',
            summary: 'No Stockages Found',
            detail: 'This product is not available in any stockage',
            life: 4000
          });
        }
      } catch (error) {
        console.error('Failed to load stockages for product:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load stockages for this product',
          life: 3000
        });
        // Don't show fallback stockages - they don't have the product
        this.productStockages = [];
        this.productStockagesProductId = productId;
        this.filteredPharmacyStockages = [];
      } finally {
        this.loadingStockages = false;
      }
    },

    searchPharmacyStockages(event) {
      const query = (event.query || '').toLowerCase();
      this.stockageSearchQuery = query;

      if (!this.newProduct.pharmacy_product_id) {
        this.filteredPharmacyStockages = [];
        return;
      }

      const currentProductId = this.newProduct.pharmacy_product_id;
      const hasCachedData = this.productStockages &&
        this.productStockagesProductId === currentProductId;

      if (!hasCachedData) {
        if (!this.loadingStockages) {
          this.loadStockagesForProduct(currentProductId);
        }
        return;
      }

      const baseStockages = this.productStockages;

      if (!query) {
        this.filteredPharmacyStockages = [...baseStockages];
        return;
      }

      this.filteredPharmacyStockages = baseStockages.filter(stockage => {
        const name = (stockage.name || '').toLowerCase();
        const code = (stockage.code || '').toLowerCase();
        const description = (stockage.description || '').toLowerCase();
        return name.includes(query) || code.includes(query) || description.includes(query);
      });
    },

    async loadMoreStockages(search = '') {
      if (this.newProduct.pharmacy_product_id) {
        return;
      }

      if (!this.stockageHasMore || this.loadingStockages) return;
      
      this.stockagePage++;
      await this.loadStockages(this.stockagePage, search || this.stockageSearchQuery);
      
      // Re-filter after loading more
      if (this.stockageSearchQuery) {
        this.filteredPharmacyStockages = this.allPharmacyStockages.filter(stockage => {
          const name = (stockage.name || '').toLowerCase();
          const code = (stockage.code || '').toLowerCase();
          const description = (stockage.description || '').toLowerCase();
          return name.includes(this.stockageSearchQuery) || code.includes(this.stockageSearchQuery) || description.includes(this.stockageSearchQuery);
        });
      } else {
        this.filteredPharmacyStockages = [...this.allPharmacyStockages];
      }
      
      // Update central pharmacy stockages with all loaded data
      this.centralPharmacyStockages = this.allPharmacyStockages.filter(stockage => {
        const name = (stockage.name || '').toLowerCase();
        const code = (stockage.code || '').toLowerCase();
        
        return name.includes('central') || 
               name.includes('pharmacy central') ||
               name.includes('pharmacie centrale') ||
               name.includes('pharmacie central') ||
               name.includes('central pharmacy') ||
               name.includes('depot central') ||
               name.includes('dépôt central') ||
               name.includes('stock central') ||
               code.includes('central');
      });
    },

    onStockageLazyLoad(event) {
      // Load more stockages when scrolling only when browsing the full list
      if (this.newProduct.pharmacy_product_id) {
        return;
      }

      if (event.last >= this.filteredPharmacyStockages.length - 10) {
        this.loadMoreStockages();
      }
    },

    onStockageSelect(event) {
      if (event.value && event.value.id) {
        this.newProduct.pharmacy_stockage_id = event.value.id;
        this.selectedStockageName = event.value.name;
      }
    },

    async handleStockageDropdown() {
      if (!this.newProduct.pharmacy_product_id) {
        this.filteredPharmacyStockages = [];
        return;
      }

      const currentProductId = this.newProduct.pharmacy_product_id;

      if (this.loadingStockages) {
        return;
      }

      if (!this.productStockages || this.productStockagesProductId !== currentProductId) {
        await this.loadStockagesForProduct(currentProductId);
        return;
      }

      this.stockageSearchQuery = '';
      this.filteredPharmacyStockages = [...this.productStockages];
    },

    getTotalAvailableQuantity() {
      if (!this.filteredPharmacyStockages || this.filteredPharmacyStockages.length === 0) {
        return 0;
      }
      
      return this.filteredPharmacyStockages.reduce((total, stockage) => {
        return total + (parseFloat(stockage.available_quantity) || 0);
      }, 0);
    },

    getSelectedStockageQuantity() {
      if (!this.newProduct.pharmacy_stockage_id || !this.filteredPharmacyStockages) {
        return null;
      }
      
      const selectedStockage = this.filteredPharmacyStockages.find(
        s => s.id === this.newProduct.pharmacy_stockage_id
      );
      
      return selectedStockage ? (parseFloat(selectedStockage.available_quantity) || null) : null;
    },

    async addProduct() {
      this.formSubmitted = true;
      
      // Validate required fields
      if (!this.newProduct.pharmacy_product_id || !this.newProduct.pharmacy_stockage_id) {
        this.toast.add({
          severity: 'warn',
          summary: 'Validation Error',
          detail: 'Please fill in all required fields',
          life: 3000
        });
        return;
      }
      
      // Validate quantity against available stock
      const maxQuantity = this.getSelectedStockageQuantity();
      if (maxQuantity !== null && this.newProduct.quantity > maxQuantity) {
        this.toast.add({
          severity: 'error',
          summary: 'Quantity Exceeds Available Stock',
          detail: `Maximum available quantity is ${maxQuantity} units. Please reduce the quantity.`,
          life: 4000
        });
        return;
      }
      
      this.isSubmitting = true;
      try {
        // For stock component: use product_id and stockage_id (not pharmacy_* fields)
        const payload = {
          reserve_id: this.reserveId,
          product_id: this.newProduct.pharmacy_product_id,
          stockage_id: this.newProduct.pharmacy_stockage_id,
          quantity: this.newProduct.quantity,
          reservation_notes: this.newProduct.reservation_notes,
          source: this.newProduct.source,
          product_type: 'stock'
        };

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
        pharmacy_stockage_id: null,
        destination_service_id: null
      };
      this.showFulfillDialog = true;
    },

    async confirmFulfillProduct() {
      this.isSubmitting = true;
      try {
        const payload = {};
        if (this.fulfillForm.pharmacy_stockage_id) {
          payload.pharmacy_stockage_id = this.fulfillForm.pharmacy_stockage_id;
        }
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
        pharmacy_stockage_id: null,
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
        if (this.bulkFulfillForm.pharmacy_stockage_id) {
          payload.pharmacy_stockage_id = this.bulkFulfillForm.pharmacy_stockage_id;
        }
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
  z-index: 999 !important;
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

/* AutoComplete custom styles */
:deep(.p-autocomplete) {
  width: 100%;
  position: relative !important;
  z-index: 1 !important;
}

:deep(.p-autocomplete-input) {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

:deep(.p-autocomplete-input:focus) {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1), 0 4px 12px rgba(79, 70, 229, 0.1);
  outline: none;
  background: #ffffff;
  transform: translateY(-1px);
}

:deep(.p-autocomplete.p-invalid .p-autocomplete-input) {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

:deep(.p-autocomplete.p-disabled .p-autocomplete-input) {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  cursor: not-allowed;
  opacity: 0.7;
  transform: none;
}

:deep(.p-autocomplete-panel) {
  border-radius: 1rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.05);
  border: 1px solid #e5e7eb;
  margin-top: 0.75rem;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
  z-index: 1000 !important;
  position: fixed !important;
  max-width: 90vw !important;
}

:deep(.p-autocomplete-items) {
  padding: 0.75rem;
  max-height: 400px;
}

:deep(.p-autocomplete-item) {
  border-radius: 0.75rem;
  margin: 0.125rem 0;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid transparent;
}

:deep(.p-autocomplete-item:hover) {
  transform: translateX(2px) scale(1.01);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

:deep(.p-autocomplete-item.p-highlight) {
  background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
  color: white;
  transform: translateX(4px) scale(1.02);
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
  border: 1px solid rgba(79, 70, 229, 0.2);
}

:deep(.p-autocomplete-loader) {
  position: absolute;
  right: 3.5rem;
  top: 50%;
  transform: translateY(-50%);
}

:deep(.p-virtualscroller) {
  max-height: 400px;
}

/* Enhanced dropdown button */
:deep(.p-autocomplete .p-autocomplete-dropdown) {
  background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
  border: none;
  border-radius: 0 0.75rem 0.75rem 0;
  width: 3rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
}

:deep(.p-autocomplete .p-autocomplete-dropdown:hover) {
  background: linear-gradient(135deg, #4338ca 0%, #2563eb 100%);
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

:deep(.p-autocomplete .p-autocomplete-dropdown .pi) {
  color: white;
  font-size: 1rem;
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

:deep(.p-autocomplete-item .tw-bg-green-100) {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive design */
@media (max-width: 768px) {
  :deep(.p-autocomplete-input) {
    padding: 0.75rem;
    font-size: 0.875rem;
  }
  
  :deep(.p-autocomplete-panel) {
    margin-top: 0.5rem;
    border-radius: 0.75rem;
  }
  
  :deep(.p-autocomplete-items) {
    padding: 0.5rem;
  }
}

/* Error message style */
.p-error {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

/* Quantity badge styling */
.tw-bg-green-100 {
  background-color: #d1fae5;
}

.tw-text-green-700 {
  color: #047857;
}

.tw-rounded-full {
  border-radius: 9999px;
}

/* Animation for quantity display */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.tw-bg-green-100 {
  animation: fadeIn 0.3s ease-out;
}
</style>
