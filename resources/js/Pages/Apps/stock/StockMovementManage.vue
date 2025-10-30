<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <!-- Floating Action Buttons -->
    <div class="tw-fixed tw-bottom-6 tw-right-6 tw-z-50 tw-flex tw-flex-col tw-gap-3">
      <Button
        v-if="movement?.status === 'draft' && suggestionCounts.total > 0"
        @click="openSuggestionsDialog"
        class="tw-w-14 tw-h-14 tw-rounded-full tw-shadow-2xl hover:tw-shadow-3xl tw-transition-all tw-duration-300 tw-bg-gradient-to-r tw-from-purple-500 tw-to-purple-600 hover:tw-from-purple-600 hover:tw-to-purple-700"
        v-tooltip.top="'Smart Suggestions'"
      >
        <i class="pi pi-lightbulb tw-text-white tw-text-xl"></i>
      </Button>
      
      <Button
        v-if="movement?.status === 'draft'"
        @click="openAddProductDialog"
        class="tw-w-14 tw-h-14 tw-rounded-full tw-shadow-2xl hover:tw-shadow-3xl tw-transition-all tw-duration-300 tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 hover:tw-from-blue-600 hover:tw-to-blue-700 tw-text-nowrap"
        v-tooltip.top="'Add Product'"
      >
        <i class="pi pi-plus tw-text-white tw-text-xl"></i>
      </Button>
    </div>

    <!-- Header -->
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
      <template #content>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2">Stock Movement Management</h1>
            <p class="tw-text-blue-100 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-hashtag"></i>
              {{ movement?.movement_number || 'Loading...' }}
            </p>
          </div>
          <div class="tw-flex tw-space-x-3">
            <Button
              @click="$router.go(-1)"
              icon="pi pi-arrow-left"
              severity="secondary"
              outlined
              class="tw-bg-white tw-rounded-xl tw-text-blue-600 hover:tw-bg-blue-50 tw-px-4 tw-py-2 tw-text-nowrap"
              v-tooltip.top="'Back to Requests'"
            />

            <Button
              v-if="movement?.status === 'draft'"
              @click="sendRequest"
              :loading="sending"
              icon="pi pi-send"
              class="tw-bg-green-600 tw-rounded-xl hover:tw-bg-green-600 tw-px-20 tw-py-2 tw-text-nowrap"
            >
              Send for Approval
            </Button>
          </div>
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-min-h-96">
      <div class="tw-text-center">
        <div class="tw-relative">
          <div class="tw-w-32 tw-h-32 tw-bg-gradient-to-r tw-from-blue-500 tw-to-purple-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6 tw-shadow-2xl">
            <i class="pi pi-clipboard tw-text-white tw-text-5xl"></i>
          </div>
          <div class="tw-absolute tw-inset-0 tw-w-32 tw-h-32 tw-border-4 tw-border-white/30 tw-border-t-white tw-rounded-full tw-mx-auto tw-animate-spin"></div>
        </div>
        <h3 class="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">Loading Request Details</h3>
        <p class="tw-text-gray-600">Please wait while we fetch your data...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="tw-max-w-9xl tw-mx-auto tw-px-6 tw-py-8">
      <!-- Quick Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Total Products</p>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-900">{{ movement?.items?.length || 0 }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-blue-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Low Stock Alerts</p>
              <p class="tw-text-3xl tw-font-bold tw-text-orange-600">{{ suggestionCounts.low_stock }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-orange-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-exclamation-triangle tw-text-orange-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Expiring Soon</p>
              <p class="tw-text-3xl tw-font-bold tw-text-yellow-600">{{ suggestionCounts.expiring_soon }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-yellow-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-clock tw-text-yellow-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Critical Stock</p>
              <p class="tw-text-3xl tw-font-bold tw-text-red-600">{{ suggestionCounts.critical_low }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-red-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-exclamation-circle tw-text-red-600 tw-text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="tw-grid tw-grid-cols-1 tw-xl:grid-cols-3 tw-gap-8">
        <!-- Request Details Card -->
        <div class="tw-xl:col-span-1">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-100 tw-overflow-hidden">
            <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-p-6 tw-text-white">
              <h3 class="tw-text-xl tw-font-bold tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle"></i>
                Request Details
              </h3>
            </div>

            <div class="tw-p-6 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <!-- Requesting Service -->
              <div class="tw-group tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-rounded-xl tw-p-3 tw-border tw-border-blue-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-blue-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-building tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-blue-800 tw-mb-1">Requesting Service</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ movement.requesting_service?.name }}</dd>
                  </div>
                </div>
              </div>

              <!-- Providing Service -->
              <div class="tw-group tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-rounded-xl tw-p-3 tw-border tw-border-green-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-green-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-building tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-green-800 tw-mb-1">Providing Service</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ movement.providing_service?.name }}</dd>
                  </div>
                </div>
              </div>

              <!-- Requested By -->
              <div class="tw-group tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-rounded-xl tw-p-3 tw-border tw-border-purple-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-purple-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-user tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-purple-800 tw-mb-1">Requested By</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ movement.requesting_user?.name }}</dd>
                  </div>
                </div>
              </div>

              <!-- Reason -->
              <div v-if="movement.request_reason" class="tw-group tw-bg-gradient-to-r tw-from-orange-50 tw-to-orange-100 tw-rounded-xl tw-p-3 tw-border tw-border-orange-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-orange-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-question-circle tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-orange-800 tw-mb-1">Reason</dt>
                    <dd class="tw-text-sm tw-text-gray-900">{{ movement.request_reason }}</dd>
                  </div>
                </div>
              </div>

              <!-- Expected Delivery -->
              <div v-if="movement.expected_delivery_date" class="tw-group tw-bg-gradient-to-r tw-from-indigo-50 tw-to-indigo-100 tw-rounded-xl tw-p-3 tw-border tw-border-indigo-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-indigo-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-calendar tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-indigo-800 tw-mb-1">Expected Delivery</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ formatDate(movement.expected_delivery_date) }}</dd>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Products Management Card -->
        <div class="tw-xl:col-span-2">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-100 tw-overflow-hidden">
            <div class="tw-bg-gradient-to-r tw-from-slate-600 tw-to-slate-700 tw-p-6 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between">
                <h3 class="tw-text-xl tw-font-bold tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-box"></i>
                  Requested Products
                  <span class="tw-text-sm tw-font-normal tw-text-slate-300">({{ movement.items?.length || 0 }} items)</span>
                </h3>

                <div class="tw-flex tw-gap-2">
                  <Button
                    icon="pi pi-refresh"
                    severity="secondary"
                    text
                    class="tw-p-2 hover:tw-bg-white/10 tw-transition-all tw-duration-300"
                    @click="loadMovement"
                    v-tooltip="'Refresh Data'"
                    :loading="loading"
                  />
                  <Button
                    v-if="movement?.status === 'draft'"
                    @click="openAddProductDialog"
                    icon="pi pi-plus"
                    class="tw-bg-white tw-text-slate-700 tw-rounded-xl tw-px-20 tw-py-2 tw-font-semibold tw-shadow-lg hover:tw-bg-slate-50 tw-transition-all tw-duration-300 tw-text-nowrap"
                  >
                    Add Product
                  </Button>
                </div>
              </div>
            </div>

            <div class="tw-p-6">
              <!-- Products Management Header -->
              <div v-if="movement.items && movement.items.length > 0" class="tw-mb-6">
                <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-4 tw-mb-4">
                  <!-- Search and Filter -->
                  <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-3 tw-flex-1">
                    <div class="tw-relative tw-flex-1">
                      <i class="pi pi-search tw-absolute tw-left-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400"></i>
                      <InputText
                        v-model="searchQuery"
                        placeholder="Search products..."
                        class="tw-pl-10 tw-pr-4 tw-py-2 tw-w-full tw-rounded-xl tw-border-2 tw-border-gray-300 hover:tw-border-blue-400 focus:tw-border-blue-500 tw-transition-all tw-duration-300"
                        @input="handleSearch"
                      />
                    </div>
                    <Dropdown
                      v-model="selectedCategory"
                      :options="categoryOptions"
                      option-label="label"
                      option-value="value"
                      placeholder="Filter by category"
                      class="tw-w-full sm:tw-w-48 tw-rounded-xl tw-border-2"
                      @change="handleFilter"
                      show-clear
                    />
                  </div>

                  <!-- View Controls -->
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <Button
                      icon="pi pi-table"
                      severity="secondary"
                      text
                      class="tw-p-2 tw-bg-blue-50 tw-text-blue-600 tw-border tw-border-blue-200"
                      v-tooltip="'Table view'"
                      disabled
                    />
                  </div>
                </div>

                <!-- Results Summary -->
                <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600 tw-mb-4">
                  <span>
                    Showing {{ (currentPage - 1) * pageSize + 1 }} to {{ Math.min(currentPage * pageSize, filteredProducts.length) }} of {{ filteredProducts.length }} products
                    <span v-if="searchQuery || selectedCategory" class="tw-text-blue-600">
                      (filtered from {{ movement.items.length }} total)
                    </span>
                  </span>
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-text-gray-500">Show:</span>
                    <Dropdown
                      v-model="pageSize"
                      :options="pageSizeOptions"
                      option-label="label"
                      option-value="value"
                      placeholder="10"
                      class="tw-w-20 tw-rounded-lg"
                      @change="handlePageSizeChange"
                    />
                  </div>
                </div>
              </div>

              <!-- Enhanced Products DataTable -->
              <div v-if="movement.items && movement.items.length > 0" class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-gray-100 tw-overflow-hidden">
                <!-- Modern Header -->
                <div class="tw-bg-gradient-to-r tw-from-emerald-600 tw-via-teal-600 tw-to-cyan-600 tw-px-8 tw-py-6">
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <div class="tw-flex tw-items-center tw-gap-4">
                      <div class="tw-w-12 tw-h-12 tw-bg-white/20 tw-backdrop-blur-sm tw-rounded-xl tw-flex tw-items-center tw-justify-center">
                        <i class="pi pi-shopping-cart tw-text-white tw-text-xl"></i>
                      </div>
                      <div>
                        <h4 class="tw-text-xl tw-font-bold tw-text-white tw-mb-1">Products Management</h4>
                        <p class="tw-text-emerald-100 tw-text-sm">Add, edit and manage product requests</p>
                      </div>
                    </div>
                    <div class="tw-bg-white/20 tw-backdrop-blur-sm tw-rounded-xl tw-px-4 tw-py-2">
                      <div class="tw-flex tw-items-center tw-gap-2 tw-text-white">
                        <i class="pi pi-info-circle tw-text-lg"></i>
                        <span class="tw-font-semibold">{{ filteredProducts.length }}</span>
                        <span class="tw-text-emerald-100">of {{ movement.items.length }} products</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Enhanced DataTable -->
                <DataTable
                  :value="paginatedProducts"
                  :rows="pageSize"
                  :paginator="false"
                  class="tw-border-0"
                  :rowHover="true"
                  stripedRows
                  responsiveLayout="scroll"
                >
                  <!-- Product Column -->
                  <Column field="product" header="Product" class="tw-min-w-80">
                    <template #body="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-4 tw-py-2">
                        <div class="tw-relative">
                          <div class="tw-w-14 tw-h-14 tw-bg-gradient-to-br tw-from-emerald-500 tw-via-teal-500 tw-to-cyan-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
                            <i class="pi pi-box tw-text-white tw-text-lg"></i>
                          </div>
                          <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-5 tw-h-5 tw-bg-orange-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                            <i class="pi pi-pencil tw-text-white tw-text-xs"></i>
                          </div>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                          <div class="tw-text-base tw-font-bold tw-text-gray-900 tw-mb-1 tw-truncate">
                            {{ slotProps.data.product?.name }}
                          </div>
                          <div class="tw-flex tw-items-center tw-gap-3 tw-text-sm tw-text-gray-500">
                            <div class="tw-flex tw-items-center tw-gap-1 tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded-lg">
                              <i class="pi pi-tag tw-text-xs"></i>
                              <span class="tw-font-medium">{{ slotProps.data.product?.code }}</span>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1 tw-bg-emerald-100 tw-text-emerald-700 tw-px-2 tw-py-1 tw-rounded-lg">
                              <i class="pi pi-map-marker tw-text-xs"></i>
                              <span class="tw-font-medium">{{ slotProps.data.product?.stockage_name || 'Main Storage' }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Quantity Column -->
                  <Column field="quantity" header="Quantity" class="tw-min-w-48">
                    <template #body="slotProps">
                      <div class="tw-py-2">
                        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border tw-border-blue-200 tw-rounded-xl tw-p-4">
                          <div v-if="movement?.status === 'draft'" class="tw-relative">
                            <InputNumber
                              v-model="slotProps.data.requested_quantity"
                              :min="0.01"
                              :step="0.01"
                              @input="updateItemQuantity(slotProps.data)"
                              class="tw-w-full"
                              inputClass="tw-text-center tw-font-bold tw-text-lg tw-text-blue-700 tw-bg-white tw-rounded-lg tw-border-2 tw-border-blue-300 hover:tw-border-blue-400 focus:tw-border-blue-500 tw-transition-all tw-duration-200"
                              :disabled="updatingItemId === slotProps.data.id"
                              :showButtons="false"
                            />
                            <div v-if="updatingItemId === slotProps.data.id" class="tw-absolute tw-inset-0 tw-bg-white/80 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                              <div class="tw-w-4 tw-h-4 tw-border-2 tw-border-blue-500 tw-border-t-transparent tw-rounded-full tw-animate-spin"></div>
                            </div>
                          </div>
                          <div v-else class="tw-text-2xl tw-font-bold tw-text-blue-700 tw-text-center">
                            {{ slotProps.data.requested_quantity }}
                          </div>
                          <div v-if="slotProps.data.quantity_by_box && slotProps.data.product?.boite_de" class="tw-text-xs tw-text-blue-600 tw-font-medium tw-mt-2 tw-text-center">
                            = {{ slotProps.data.requested_quantity * slotProps.data.product.boite_de }} {{ getProductUnit(slotProps.data.product) }}
                          </div>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Unit Column -->
                  <Column field="unit" header="Unit" class="tw-min-w-40">
                    <template #body="slotProps">
                      <div class="tw-py-2 tw-space-y-2">
                        <div class="tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-px-3 tw-py-2">
                          <div class="tw-text-sm tw-font-semibold tw-text-gray-900">
                            {{ slotProps.data.quantity_by_box && slotProps.data.product?.boite_de ? 'Boxes' : (slotProps.data.product?.unit || 'units') }}
                          </div>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Stock Info Column -->
                  <Column field="stock" header="Stock Information" class="tw-min-w-64">
                    <template #body="slotProps">
                      <div class="tw-py-2 tw-space-y-2">
                        <div class="tw-flex tw-items-center tw-gap-2 tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-px-3 tw-py-2">
                          <i class="pi pi-chart-line tw-text-blue-600 tw-text-lg"></i>
                          <div>
                            <div class="tw-text-xs tw-text-blue-700 tw-font-medium">Available Stock</div>
                            <div class="tw-text-lg tw-font-bold tw-text-blue-800">
                              {{ getProductStock(slotProps.data.product_id) }}
                            </div>
                            <div class="tw-text-xs tw-text-blue-600">{{ slotProps.data.product?.unit || 'units' }}</div>
                          </div>
                        </div>
                        <div v-if="slotProps.data.product?.expiry_date" class="tw-flex tw-items-center tw-gap-2 tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-px-3 tw-py-2">
                          <i class="pi pi-calendar tw-text-green-600 tw-text-lg"></i>
                          <div>
                            <div class="tw-text-xs tw-text-green-700 tw-font-medium">Expiry Date</div>
                            <div class="tw-text-sm tw-font-bold tw-text-green-800">
                              {{ formatDate(slotProps.data.product.expiry_date) }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Actions Column -->
                  <Column field="actions" header="Actions" class="tw-min-w-40">
                    <template #body="slotProps">
                      <div class="tw-py-2 tw-flex tw-items-center tw-gap-2">
                        <Button
                          v-if="movement?.status === 'draft'"
                          @click="removeItem(slotProps.data)"
                          icon="pi pi-trash"
                          severity="danger"
                          outlined
                          size="small"
                          class="tw-rounded-lg tw-transition-all tw-duration-200"
                          v-tooltip="'Remove product'"
                        />
                        <div v-if="slotProps.data.notes" class="tw-relative">
                          <Button
                            icon="pi pi-exclamation-triangle"
                            severity="warning"
                            text
                            size="small"
                            class="tw-rounded-lg"
                            v-tooltip="slotProps.data.notes"
                          />
                        </div>
                      </div>
                    </template>
                  </Column>
                </DataTable>

                <!-- Enhanced Pagination -->
                <div v-if="totalPages > 1" class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-px-8 tw-py-6 tw-border-t tw-border-gray-200">
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600">
                      <i class="pi pi-info-circle tw-text-emerald-500"></i>
                      <span class="tw-font-medium">
                        Showing {{ (currentPage - 1) * pageSize + 1 }} to {{ Math.min(currentPage * pageSize, filteredProducts.length) }} of {{ filteredProducts.length }} products
                      </span>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <Button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        icon="pi pi-chevron-left"
                        severity="secondary"
                        outlined
                        size="small"
                        class="tw-rounded-lg"
                      />
                      <div class="tw-flex tw-items-center tw-gap-1">
                        <Button
                          v-for="page in visiblePages"
                          :key="page"
                          @click="goToPage(page)"
                          :severity="page === currentPage ? 'success' : 'secondary'"
                          :outlined="page !== currentPage"
                          class="tw-w-10 tw-h-10 tw-rounded-lg tw-font-semibold tw-transition-all tw-duration-200"
                          size="small"
                        >
                          {{ page }}
                        </Button>
                      </div>
                      <Button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        icon="pi pi-chevron-right"
                        severity="secondary"
                        outlined
                        size="small"
                        class="tw-rounded-lg"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-else class="tw-text-center tw-py-16">
                <div class="tw-relative">
                  <div class="tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6 tw-shadow-lg">
                    <i class="pi pi-box tw-text-4xl tw-text-gray-400"></i>
                  </div>
                  <div class="tw-absolute tw-inset-0 tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-blue-400 tw-to-purple-500 tw-rounded-full tw-opacity-20 tw-animate-pulse"></div>
                </div>
                <h3 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-2">No products added yet</h3>
                <p class="tw-text-gray-600 tw-mb-8 tw-max-w-md tw-mx-auto">Get started by adding products to your stock request. You can add them manually or use our smart suggestions feature.</p>
                <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-3 tw-justify-center">
                  <Button
                    v-if="movement?.status === 'draft'"
                    @click="openAddProductDialog"
                    icon="pi pi-plus"
                    class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 hover:tw-from-blue-600 hover:tw-to-blue-700 tw-text-white tw-rounded-xl tw-px-20 tw-py-3 tw-font-semibold tw-shadow-lg tw-transition-all tw-duration-300 tw-text-nowrap"
                  >
                    Add First Product
                  </Button>
                  <Button
                    v-if="movement?.status === 'draft' && suggestionCounts.total > 0"
                    @click="openSuggestionsDialog"
                    icon="pi pi-lightbulb"
                    severity="help"
                    class="tw-rounded-xl tw-px-20 tw-py-3 tw-font-semibold tw-shadow-lg tw-transition-all tw-duration-300 tw-text-nowrap"
                    v-tooltip.top="'Get smart suggestions'"
                  >
                    Smart Suggestions
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Product Dialog -->
    <Dialog
      v-model:visible="showAddProductDialog"
      modal
      :header="'Add Product to Request'"
      :style="{width: '700px'}"
      class="tw-rounded-2xl tw-shadow-2xl"
    >
      <div class="tw-space-y-6">
        <!-- Header Info -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-rounded-xl tw-p-4 tw-border tw-border-blue-200">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-w-12 tw-h-12 tw-bg-blue-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-info-circle tw-text-white tw-text-lg"></i>
            </div>
            <div>
              <h4 class="tw-font-semibold tw-text-blue-800 tw-text-lg">Product Selection</h4>
              <p class="tw-text-sm tw-text-blue-700">Choose a product from {{ movement?.providing_service?.name }}'s inventory</p>
            </div>
          </div>
        </div>

        <!-- Smart Suggestions Alert Section -->
        <div v-if="suggestions.length > 0" class="tw-space-y-4">
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-4 tw-border tw-border-blue-200 tw-shadow-lg">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
              <div class="tw-w-12 tw-h-12 tw-bg-blue-600 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-animate-pulse">
                <i class="pi pi-bell tw-text-white tw-text-lg"></i>
              </div>
              <div>
                <h4 class="tw-font-semibold tw-text-blue-800 tw-text-lg">🚨 Suggestion Alerts</h4>
                <p class="tw-text-sm tw-text-blue-700">Click on products to auto-add with suggested quantities</p>
              </div>
              <div class="tw-ml-auto">
                <span class="tw-text-xs tw-bg-blue-100 tw-text-blue-700 tw-px-3 tw-py-1 tw-rounded-full tw-font-medium">Click to add</span>
              </div>
            </div>

            <div class="tw-space-y-4">
              <div
                v-for="suggestion in suggestions.slice(0, 4)"
                :key="suggestion.type"
                class="tw-bg-white tw-rounded-lg tw-p-4 tw-border tw-border-gray-200 hover:tw-border-blue-300 hover:tw-shadow-md tw-transition-all tw-duration-300"
              >
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <div :class="['tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center', getSuggestionIconClass(suggestion.color)]">
                      <i :class="suggestion.icon" class="tw-text-white tw-text-lg"></i>
                    </div>
                    <div>
                      <h5 class="tw-font-semibold tw-text-gray-900 tw-text-base">{{ suggestion.title }}</h5>
                      <p class="tw-text-sm tw-text-gray-600">{{ suggestion.products.length }} products need attention</p>
                    </div>
                  </div>
                  <Button
                    size="small"
                    :class="getSuggestionButtonClass(suggestion.color)"
                    class="tw-rounded-lg tw-text-sm tw-px-4 tw-py-2"
                    @click="addSuggestedProducts(suggestion.products)"
                  >
                    <i class="pi pi-plus-circle tw-mr-2"></i>
                    Add All
                  </Button>
                </div>
                
                <!-- Individual Product Cards -->
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-2">
                  <div
                    v-for="product in suggestion.products.slice(0, 4)"
                    :key="product.id"
                    :class="[
                      'tw-border tw-rounded-lg tw-p-3 tw-cursor-pointer tw-transition-all tw-duration-200 hover:tw-shadow-md',
                      suggestion.type === 'critical_low' ? 'tw-bg-red-50 tw-border-red-200 hover:tw-bg-red-100 tw-border-l-4 tw-border-l-red-500' :
                      suggestion.type === 'low_stock' ? 'tw-bg-orange-50 tw-border-orange-200 hover:tw-bg-orange-100 tw-border-l-4 tw-border-l-orange-500' :
                      suggestion.type === 'expiring_soon' ? 'tw-bg-yellow-50 tw-border-yellow-200 hover:tw-bg-yellow-100 tw-border-l-4 tw-border-l-yellow-500' :
                      'tw-bg-gray-50 tw-border-gray-200 hover:tw-bg-gray-100 tw-border-l-4 tw-border-l-gray-500'
                    ]"
                    @click="openQuantityDialog(product, suggestion.type)"
                  >
                    <div class="tw-flex tw-items-center tw-justify-between">
                      <div class="tw-flex-1">
                        <div class="tw-text-sm tw-font-semibold tw-text-gray-800 tw-mb-1">{{ product.name }}</div>
                        <div class="tw-text-xs tw-text-gray-600 tw-mb-1">
                          <span v-if="suggestion.type === 'critical_low' || suggestion.type === 'low_stock'">
                            Current: {{ product.current_stock || 0 }} {{ getProductUnit(product) }}
                          </span>
                          <span v-else-if="suggestion.type === 'expiring_soon'">
                            {{ getExpiryText(product, 'expiring_soon') }}
                          </span>
                          <span v-else>
                            {{ getExpiryText(product, 'expired') }}
                          </span>
                        </div>
                        <div v-if="product.order_quantity" class="tw-text-xs tw-font-medium tw-text-blue-600">
                          💡 Suggested: {{ product.order_quantity }} {{ getProductUnit(product) }}
                        </div>
                      </div>
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i class="pi pi-plus-circle tw-text-blue-600 tw-text-lg"></i>
                      </div>
                    </div>
                  </div>
                  <div v-if="suggestion.products.length > 4" class="tw-flex tw-items-center tw-justify-center tw-text-xs tw-text-gray-500 tw-bg-gray-50 tw-rounded tw-p-3 tw-border tw-border-dashed tw-border-gray-300">
                    +{{ suggestion.products.length - 4 }} more items
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Selection Form -->
        <div class="tw-space-y-6">
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
              Select Product <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="newItem.product_id"
              :options="availableProducts"
              option-label="name"
              option-value="id"
              placeholder="Search and select a product..."
              class="tw-w-full tw-rounded-xl tw-border-2"
              :loading="loadingProducts"
              :filter="true"
              filter-placeholder="Type to search products..."
            >
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-justify-between tw-w-full tw-py-4 tw-px-2">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                      <i class="pi pi-box tw-text-blue-600"></i>
                    </div>
                    <div>
                      <div class="tw-font-semibold tw-text-gray-900">{{ slotProps.option.name }}</div>
                      <div class="tw-text-sm tw-text-gray-500 tw-flex tw-items-center tw-gap-2">
                        <span>{{ slotProps.option.code }}</span>
                        <span>•</span>
                        <span>{{ getProductType(slotProps.option) }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="tw-text-right">
                    <div class="tw-text-sm tw-font-medium tw-text-gray-900">
                      {{ loadingProducts ? '...' : (getProductStock(slotProps.option.id) || 'N/A') }} {{ getProductUnit(slotProps.option) }}
                    </div>
                    <div class="tw-text-xs tw-text-gray-500">
                      {{ loadingProducts ? 'Loading...' : 'Available' }}
                    </div>
                  </div>
                </div>
              </template>
            </Dropdown>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                Quantity <span class="tw-text-red-500">*</span>
              </label>
              <div class="tw-relative">
                <InputNumber
                  v-model="newItem.requested_quantity"
                  :min="0"
                  :step="1"
                  :useGrouping="false"
                  placeholder="Enter quantity"
                  class="tw-w-full tw-h-12 tw-rounded-xl tw-border-2 tw-border-gray-300 hover:tw-border-blue-400 focus:tw-border-blue-500 tw-transition-all tw-duration-300 tw-shadow-sm hover:tw-shadow-md"
                  inputClass="tw-text-center tw-font-semibold tw-text-lg tw-text-gray-900 tw-bg-white tw-rounded-xl tw-border-0 focus:tw-ring-0 tw-h-full tw-pr-16"
                  :showButtons="true"
                  buttonLayout="horizontal"
                  incrementButtonClass="tw-bg-gray-100 hover:tw-bg-gray-200 tw-border-0 tw-rounded-r-none"
                  decrementButtonClass="tw-bg-gray-100 hover:tw-bg-gray-200 tw-border-0 tw-rounded-l-none"
                  :allowEmpty="false"
                />
                <div class="tw-absolute tw-right-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-pointer-events-none">
                  <span class="tw-text-xs tw-text-gray-400 tw-uppercase tw-tracking-wide tw-font-semibold tw-bg-white tw-px-2 tw-rounded-full tw-shadow-sm">
                    {{ getSelectedProductUnit() || 'units' }}
                  </span>
                </div>
              </div>

              <!-- Total calculation display for box mode -->
              <div v-if="newItem.quantity_by_box && newItem.requested_quantity && selectedProduct && selectedProduct.boite_de != null" class="tw-mt-2 tw-text-sm tw-text-blue-600 tw-font-semibold tw-bg-blue-50 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-blue-200">
                <i class="pi pi-calculator tw-mr-2"></i>
                Total: {{ newItem.requested_quantity * selectedProduct.boite_de }} {{ getProductUnit(selectedProduct) }} ({{ newItem.requested_quantity }} boxes × {{ selectedProduct.boite_de }} {{ getProductUnit(selectedProduct) }}/box)
              </div>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                Unit
              </label>
              <div class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-rounded-xl tw-px-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-h-12 tw-flex tw-items-center">
                <span class="tw-text-gray-700 tw-font-semibold tw-text-sm">
                  {{ selectedProduct ? getProductUnit(selectedProduct) : 'units' }}
                </span>
              </div>
            </div>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
              Quantity Mode
            </label>
            <div class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gray-50 tw-rounded-xl tw-border-2 tw-border-gray-200">
              <InputSwitch
                v-model="newItem.quantity_by_box"
                @change="onQuantityModeChange"
                class="tw-scale-110"
              />
              <div class="tw-flex tw-flex-col">
                <span class="tw-text-sm tw-font-semibold tw-text-gray-800">
                  {{ newItem.quantity_by_box ? 'By Box' : 'By Units' }}
                </span>
                <span class="tw-text-xs tw-text-gray-600">
                  {{ quantityModeText }}
                </span>
              </div>
            </div>
          </div>


        </div>

        <!-- Action Buttons -->
        <div class="tw-flex tw-justify-end tw-space-x-3 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            @click="showAddProductDialog = false"
            severity="secondary"
            outlined
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold"
          >
            Cancel
          </Button>
          <Button
            @click="addProduct"
            :loading="addingProduct"
            icon="pi pi-plus"
            class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 hover:tw-from-blue-600 hover:tw-to-blue-700 tw-text-white tw-rounded-xl tw-px-20 tw-py-3 tw-font-semibold tw-shadow-lg tw-transition-all tw-duration-300 tw-text-nowrap"
          >
            Add Product
          </Button>
        </div>
      </div>
    </Dialog>

    <!-- Quantity Selection Dialog -->
    <Dialog 
      v-model:visible="showQuantityDialogState" 
      modal 
      :header="quantityDialogProduct ? `Add ${quantityDialogProduct.name}` : 'Add Product'" 
      :style="{ width: '500px' }"
      class="tw-rounded-xl tw-shadow-2xl"
    >
      <div v-if="quantityDialogProduct" class="tw-space-y-4">
        <!-- Product Info -->
        <div class="tw-bg-gray-50 tw-rounded-lg tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-blue-600 tw-text-lg"></i>
            </div>
            <div class="tw-flex-1">
              <h4 class="tw-font-semibold tw-text-gray-900 tw-mb-1">{{ quantityDialogProduct.name }}</h4>
              <div class="tw-text-sm tw-text-gray-600">
                <span v-if="quantityDialogType === 'critical_low' || quantityDialogType === 'low_stock'">
                  Current Stock: {{ quantityDialogProduct.current_stock || 0 }} {{ getProductUnit(quantityDialogProduct) }}
                </span>
                <span v-else-if="quantityDialogType === 'expiring_soon'">
                  {{ getExpiryText(quantityDialogProduct, 'expiring_soon') }}
                </span>
                <span v-else>
                  {{ getExpiryText(quantityDialogProduct, 'expired') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Quantity Input -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
            Quantity to Add <span class="tw-text-red-500">*</span>
          </label>
          <div class="tw-relative">
            <InputNumber
              v-model="quantityDialogQuantity"
              :min="1"
              :step="1"
              :useGrouping="false"
              placeholder="Enter quantity"
              class="tw-w-full tw-h-12 tw-rounded-xl tw-border-2 tw-border-gray-300 hover:tw-border-blue-400 focus:tw-border-blue-500 tw-transition-all tw-duration-300 tw-shadow-sm hover:tw-shadow-md"
              inputClass="tw-text-center tw-font-semibold tw-text-lg tw-text-gray-900 tw-bg-white tw-rounded-xl tw-border-0 focus:tw-ring-0 tw-h-full tw-pr-16"
              :showButtons="true"
              buttonLayout="horizontal"
              incrementButtonClass="tw-bg-gray-100 hover:tw-bg-gray-200 tw-border-0 tw-rounded-r-none"
              decrementButtonClass="tw-bg-gray-100 hover:tw-bg-gray-200 tw-border-0 tw-rounded-l-none"
              :allowEmpty="false"
            />
            <div class="tw-absolute tw-right-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-pointer-events-none">
              <span class="tw-text-xs tw-text-gray-400 tw-uppercase tw-tracking-wide tw-font-semibold tw-bg-white tw-px-2 tw-rounded-full tw-shadow-sm">
                {{ getProductUnit(quantityDialogProduct) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Suggested Quantity Info -->
        <div v-if="quantityDialogProduct.order_quantity" class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-3">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <i class="pi pi-lightbulb tw-text-blue-600"></i>
            <span class="tw-text-sm tw-font-medium tw-text-blue-800">Smart Suggestion</span>
          </div>
          <p class="tw-text-sm tw-text-blue-700">
            Based on usage patterns, we suggest ordering <strong>{{ quantityDialogProduct.order_quantity }} {{ getProductUnit(quantityDialogProduct) }}</strong>
          </p>
          <Button 
            label="Use Suggested Quantity" 
            size="small" 
            class="p-button-outlined p-button-info tw-mt-2"
            @click="quantityDialogQuantity = quantityDialogProduct.order_quantity"
          />
        </div>

        <!-- Action Buttons -->
        <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4">
          <Button 
            label="Cancel" 
            class="p-button-outlined p-button-secondary"
            @click="closeQuantityDialog"
          />
          <Button 
            label="Add Product" 
            icon="pi pi-plus"
            class="p-button-primary"
            :disabled="!quantityDialogQuantity || quantityDialogQuantity <= 0"
            @click="addProductWithQuantity"
          />
        </div>
      </div>
    </Dialog>

    <!-- Smart Suggestions Dialog -->
    <Dialog
      v-model:visible="showSuggestionsDialog"
      modal
      header="Smart Product Suggestions"
      :style="{width: '700px'}"
      class="tw-rounded-2xl"
    >
      <div class="tw-space-y-6">
        <!-- Info Section -->
        <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-rounded-xl tw-p-4 tw-border tw-border-purple-200">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-w-10 tw-h-10 tw-bg-purple-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-lightbulb tw-text-white"></i>
            </div>
            <div>
              <h4 class="tw-font-semibold tw-text-purple-800">Smart Suggestions</h4>
              <p class="tw-text-sm tw-text-purple-700">Select categories of products to add to your request based on inventory alerts</p>
            </div>
          </div>
        </div>

        <!-- Suggestions Categories -->
        <div class="tw-space-y-4">
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <!-- Critical Low Stock -->
            <div
              v-if="suggestionCounts.critical_low > 0"
              class="tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-xl tw-p-4 hover:tw-bg-red-100 tw-transition-all tw-duration-300"
            >
              <div class="tw-flex tw-items-start tw-gap-3">
                <Checkbox
                  v-model="selectedSuggestionCategories"
                  :value="'critical_low'"
                  class="tw-mt-1"
                />
                <div class="tw-flex-1">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                    <div class="tw-w-8 tw-h-8 tw-bg-red-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                      <i class="pi pi-exclamation-circle tw-text-white tw-text-sm"></i>
                    </div>
                    <h5 class="tw-font-semibold tw-text-red-800">Critical Low Stock</h5>
                  </div>
                  <p class="tw-text-sm tw-text-red-700 tw-mb-2">{{ suggestionCounts.critical_low }} products with critical low stock</p>
                  <p class="tw-text-xs tw-text-red-600">Stock ≤ critical threshold</p>
                </div>
              </div>
            </div>

            <!-- Low Stock -->
            <div
              v-if="suggestionCounts.low_stock > 0"
              class="tw-bg-orange-50 tw-border tw-border-orange-200 tw-rounded-xl tw-p-4 hover:tw-bg-orange-100 tw-transition-all tw-duration-300"
            >
              <div class="tw-flex tw-items-start tw-gap-3">
                <Checkbox
                  v-model="selectedSuggestionCategories"
                  :value="'low_stock'"
                  class="tw-mt-1"
                />
                <div class="tw-flex-1">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                    <div class="tw-w-8 tw-h-8 tw-bg-orange-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                      <i class="pi pi-exclamation-triangle tw-text-white tw-text-sm"></i>
                    </div>
                    <h5 class="tw-font-semibold tw-text-orange-800">Low Stock Alert</h5>
                  </div>
                  <p class="tw-text-sm tw-text-orange-700 tw-mb-2">{{ suggestionCounts.low_stock }} products with low stock</p>
                  <p class="tw-text-xs tw-text-orange-600">Stock ≤ low threshold</p>
                </div>
              </div>
            </div>

            <!-- Expiring Soon -->
            <div
              v-if="suggestionCounts.expiring_soon > 0"
              class="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-xl tw-p-4 hover:tw-bg-yellow-100 tw-transition-all tw-duration-300"
            >
              <div class="tw-flex tw-items-start tw-gap-3">
                <Checkbox
                  v-model="selectedSuggestionCategories"
                  :value="'expiring_soon'"
                  class="tw-mt-1"
                />
                <div class="tw-flex-1">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                    <div class="tw-w-8 tw-h-8 tw-bg-yellow-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                      <i class="pi pi-clock tw-text-white tw-text-sm"></i>
                    </div>
                    <h5 class="tw-font-semibold tw-text-yellow-800">Expiring Soon</h5>
                  </div>
                  <p class="tw-text-sm tw-text-yellow-700 tw-mb-2">{{ suggestionCounts.expiring_soon }} products expiring within 30 days</p>
                  <p class="tw-text-xs tw-text-yellow-600">Expiry ≤ 30 days</p>
                </div>
              </div>
            </div>

            <!-- Expired -->
            <div
              v-if="suggestionCounts.expired > 0"
              class="tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-xl tw-p-4 hover:tw-bg-gray-100 tw-transition-all tw-duration-300"
            >
              <div class="tw-flex tw-items-start tw-gap-3">
                <Checkbox
                  v-model="selectedSuggestionCategories"
                  :value="'expired'"
                  class="tw-mt-1"
                />
                <div class="tw-flex-1">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                    <div class="tw-w-8 tw-h-8 tw-bg-gray-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                      <i class="pi pi-times-circle tw-text-white tw-text-sm"></i>
                    </div>
                    <h5 class="tw-font-semibold tw-text-gray-800">Expired Products</h5>
                  </div>
                  <p class="tw-text-sm tw-text-gray-700 tw-mb-2">{{ suggestionCounts.expired }} expired products</p>
                  <p class="tw-text-xs tw-text-gray-600">Already expired</p>
                </div>
              </div>
            </div>
          </div>

          <!-- No Suggestions Message -->
          <div v-if="suggestionCounts.total === 0" class="tw-text-center tw-py-8">
            <div class="tw-w-16 tw-h-16 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
              <i class="pi pi-check-circle tw-text-green-600 tw-text-2xl"></i>
            </div>
            <h4 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">All Good!</h4>
            <p class="tw-text-gray-600">No products require attention at this time.</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="tw-flex tw-justify-end tw-space-x-3 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            @click="showSuggestionsDialog = false"
            severity="secondary"
            outlined
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold"
          >
            Cancel
          </Button>
          <Button
            @click="addSelectedSuggestions"
            :disabled="selectedSuggestionCategories.length === 0"
            icon="pi pi-plus"
            class="tw-bg-purple-600 tw-text-white tw-rounded-xl tw-px-20 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-bg-purple-700 tw-transition-all tw-duration-300 tw-text-nowrap"
          >
            Add Selected ({{ getSelectedCount() }})
          </Button>
        </div>
      </div>
    </Dialog>

    <!-- Toast -->
    <Toast />
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Card from 'primevue/card'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'
import InputSwitch from 'primevue/inputswitch'
import Checkbox from 'primevue/checkbox'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

export default {
  name: 'StockMovementManage',
  components: {
    Button,
    Dialog,
    InputNumber,
    InputText,
    Textarea,
    Dropdown,
    Card,
    Toast,
    InputSwitch,
    Checkbox,
    DataTable,
    Column
  },
  directives: {
    tooltip: Tooltip
  },
  props: {
    movementId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const route = useRoute()
    const router = useRouter()
    const toast = useToast()

    // Reactive data
    const movement = ref({})
    const availableProducts = ref([])
    const selectedProduct = ref(null)
    const suggestions = ref([])
    const suggestionCounts = ref({
      critical_low: 0,
      low_stock: 0,
      expiring_soon: 0,
      expired: 0,
      total: 0
    })
    const showAddProductDialog = ref(false)
    const showSuggestionsDialog = ref(false)
    const addingProduct = ref(false)
    const sending = ref(false)
    const loading = ref(true)
    const loadingProducts = ref(false)
    const updatingQuantity = ref(false)
    const updatingItemId = ref(null)
    const selectedSuggestionCategories = ref([])

    // Quantity Dialog State
    const showQuantityDialogState = ref(false)
    const quantityDialogProduct = ref(null)
    const quantityDialogType = ref('')
    const quantityDialogQuantity = ref(1)

    // New reactive data for list management
    const searchQuery = ref('')
    const selectedCategory = ref(null)
    const currentPage = ref(1)
    const pageSize = ref(10)
    const pageSizeOptions = ref([
      { label: '5', value: 5 },
      { label: '10', value: 10 },
      { label: '20', value: 20 },
      { label: '50', value: 50 }
    ])



    const newItem = ref({
      product_id: null,
      requested_quantity: null,
      quantity_by_box: false,
      notes: ''
    })

    // Add watcher for debugging
    watch(() => newItem.value.product_id, (newId, oldId) => {
      // console.log('Product ID changed from', oldId, 'to', newId, 'Type:', typeof newId)
    })

    // Computed
    const canEdit = computed(() => movement.value?.status === 'draft')

    // New computed properties for list management
    const categoryOptions = computed(() => {
      if (!movement.value?.items) return []
      const categories = new Set()
      movement.value.items.forEach(item => {
        if (item.product?.category_name) {
          categories.add(item.product.category_name)
        }
      })
      return Array.from(categories).map(cat => ({ label: cat, value: cat }))
    })

    const filteredProducts = computed(() => {
      if (!movement.value?.items) return []

      let filtered = movement.value.items

      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(item =>
          item.product?.name?.toLowerCase().includes(query) ||
          item.product?.code?.toLowerCase().includes(query) ||
          item.product?.category_name?.toLowerCase().includes(query)
        )
      }

      // Apply category filter
      if (selectedCategory.value) {
        filtered = filtered.filter(item =>
          item.product?.category_name === selectedCategory.value
        )
      }

      return filtered
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredProducts.value.length / pageSize.value)
    })

    const paginatedProducts = computed(() => {
      const start = (currentPage.value - 1) * pageSize.value
      const end = start + pageSize.value
      return filteredProducts.value.slice(start, end)
    })
 
    // Watcher to update selectedProduct when product_id or availableProducts change
    watch([() => newItem.value.product_id, availableProducts], () => {
      if (!newItem.value.product_id) {
        selectedProduct.value = null
        return
      }
      
      const productId = newItem.value.product_id
      
      // Try to find the product, converting both to strings for comparison
      const productIdStr = String(productId)
      const product = availableProducts.value.find(p => String(p.id) === productIdStr)
      
      selectedProduct.value = product || null
    }, { immediate: true })

    const quantityModeText = computed(() => {
      // console.log('Computing quantityModeText')
      // console.log('newItem.quantity_by_box:', newItem.value.quantity_by_box)
      // console.log('selectedProduct:', selectedProduct.value)
      
      if (!newItem.value.quantity_by_box) {
        // console.log('Returning: Enter individual units')
        return 'Enter individual units'
      }
      
      const product = selectedProduct.value
      if (!product) {
        // console.log('No product selected, returning: 1 box = units (not specified)')
        return '1 box = units (not specified)'
      }
      
      // console.log('Product boite_de:', product.boite_de, 'Type:', typeof product.boite_de)
      if (product.boite_de !== null && product.boite_de !== undefined) {
        const unit = getProductUnit(product)
        const result = `1 box = ${product.boite_de} ${unit}`
        // console.log('Returning:', result)
        return result
      }
      
      // console.log('boite_de is null, returning: 1 box = units (not specified)')
      return '1 box = units (not specified)'
    })

    const visiblePages = computed(() => {
      const total = totalPages.value
      const current = currentPage.value
      const maxVisible = 5

      if (total <= maxVisible) {
        return Array.from({ length: total }, (_, i) => i + 1)
      }

      const start = Math.max(1, current - Math.floor(maxVisible / 2))
      const end = Math.min(total, start + maxVisible - 1)

      return Array.from({ length: end - start + 1 }, (_, i) => start + i)
    })

    // Methods
    const loadMovement = async () => {
      try {
        loading.value = true
        const response = await axios.get(`/api/stock-movements/${props.movementId}`)
        movement.value = response.data.data
      } catch (error) {
        console.error('Error loading movement:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load movement details',
          life: 3000
        })
      } finally {
        loading.value = false
      }
    }

    const loadAvailableProducts = async () => {
      try {
        loadingProducts.value = true
        // Load available products from the providing service
        const productsResponse = await axios.get(`/api/products?service_id=${movement.value?.providing_service_id}&per_page=1000`)
        availableProducts.value = productsResponse.data.data || []
        
        // console.log('Loaded products:', availableProducts.value.length)
        availableProducts.value.forEach(product => {
          // console.log(`Product ${product.id} (${product.name}): boite_de = ${product.boite_de}, type: ${typeof product.boite_de}`)
        })
        
        // Load suggestions (low stock and expiring products) to get stock information
        const suggestionsResponse = await axios.get(`/api/stock-movements/suggestions?providing_service_id=${movement.value?.providing_service_id}`)
        const suggestionsData = suggestionsResponse.data.data || {}

        // Merge stock information from suggestions into available products
        if (suggestionsData.critical_low && Array.isArray(suggestionsData.critical_low)) {
          suggestionsData.critical_low.forEach(product => {
            const existingProduct = availableProducts.value.find(p => p.id === product.id)
            if (existingProduct) {
              // Update existing product with stock information
              if (product.total_quantity !== undefined) {
                existingProduct.total_quantity = product.total_quantity
              }
              if (product.total_stock !== undefined) {
                existingProduct.total_stock = product.total_stock
              }
              if (product.actual_stock !== undefined) {
                existingProduct.actual_stock = product.actual_stock
              }
              if (product.available_quantity !== undefined) {
                existingProduct.available_quantity = product.available_quantity
              }
              existingProduct.current_stock = product.current_stock || product.stock || product.quantity || 0
            } else {
              // Add product to availableProducts if not already there
              availableProducts.value.push({
                ...product,
                current_stock: product.current_stock || product.stock || product.quantity || 0
              })
            }
          })
        }

        if (suggestionsData.low_stock && Array.isArray(suggestionsData.low_stock)) {
          suggestionsData.low_stock.forEach(product => {
            const existingProduct = availableProducts.value.find(p => p.id === product.id)
            if (existingProduct) {
              // Update existing product with stock information
              if (product.total_quantity !== undefined) {
                existingProduct.total_quantity = product.total_quantity
              }
              if (product.total_stock !== undefined) {
                existingProduct.total_stock = product.total_stock
              }
              if (product.actual_stock !== undefined) {
                existingProduct.actual_stock = product.actual_stock
              }
              if (product.available_quantity !== undefined) {
                existingProduct.available_quantity = product.available_quantity
              }
              existingProduct.current_stock = product.current_stock || product.stock || product.quantity || 0
            } else {
              // Add product to availableProducts if not already there
              availableProducts.value.push({
                ...product,
                current_stock: product.current_stock || product.stock || product.quantity || 0
              })
            }
          })
        }

        if (suggestionsData.expiring_soon && Array.isArray(suggestionsData.expiring_soon)) {
          suggestionsData.expiring_soon.forEach(product => {
            const existingProduct = availableProducts.value.find(p => p.id === product.id)
            if (existingProduct) {
              // Update existing product with stock information
              if (product.total_quantity !== undefined) {
                existingProduct.total_quantity = product.total_quantity
              }
              if (product.total_stock !== undefined) {
                existingProduct.total_stock = product.total_stock
              }
              if (product.actual_stock !== undefined) {
                existingProduct.actual_stock = product.actual_stock
              }
              if (product.available_quantity !== undefined) {
                existingProduct.available_quantity = product.available_quantity
              }
              existingProduct.current_stock = product.current_stock || product.stock || product.quantity || 0
            } else {
              // Add product to availableProducts if not already there
              availableProducts.value.push({
                ...product,
                current_stock: product.current_stock || product.stock || product.quantity || 0
              })
            }
          })
        }

        if (suggestionsData.expired && Array.isArray(suggestionsData.expired)) {
          suggestionsData.expired.forEach(product => {
            const existingProduct = availableProducts.value.find(p => p.id === product.id)
            if (existingProduct) {
              // Update existing product with stock information
              if (product.total_quantity !== undefined) {
                existingProduct.total_quantity = product.total_quantity
              }
              if (product.total_stock !== undefined) {
                existingProduct.total_stock = product.total_stock
              }
              if (product.actual_stock !== undefined) {
                existingProduct.actual_stock = product.actual_stock
              }
              if (product.available_quantity !== undefined) {
                existingProduct.available_quantity = product.available_quantity
              }
              existingProduct.current_stock = product.current_stock || product.stock || product.quantity || 0
            } else {
              // Add product to availableProducts if not already there
              availableProducts.value.push({
                ...product,
                current_stock: product.current_stock || product.stock || product.quantity || 0
              })
            }
          })
        }

        // If some products still don't have stock information, try to load it from the details endpoint
        const productsWithoutStock = availableProducts.value.filter(p => p.current_stock === undefined)
        if (productsWithoutStock.length > 0) {
          try {
            // Try to get stock information for products that don't have it
            const stockPromises = productsWithoutStock.map(async (product) => {
              try {
                const detailsResponse = await axios.get(`/api/products/${product.id}/details`)
                const detailsData = detailsResponse.data

                // Return object with stock information
                return {
                  id: product.id,
                  total_quantity: detailsData?.stats?.total_quantity || 0,
                  current_stock: detailsData?.stats?.total_quantity || 0
                }
              } catch (error) {
                console.warn(`Could not load stock for product ${product.id}:`, error)
                return { id: product.id, total_quantity: 0, current_stock: 0 }
              }
            })

            const stockResults = await Promise.all(stockPromises)
            stockResults.forEach(result => {
              const product = availableProducts.value.find(p => p.id === result.id)
              if (product) {
                // Set total quantity fields if available
                if (result.total_quantity !== null && result.total_quantity !== undefined) {
                  product.total_quantity = result.total_quantity
                }
                // Always set current_stock as fallback
                product.current_stock = result.current_stock
              }
            })
          } catch (error) {
            console.warn('Could not load additional stock information:', error)
          }
        }

        // Update suggestion counts
        suggestionCounts.value = {
          critical_low: suggestionsData.counts?.critical_low || 0,
          low_stock: suggestionsData.counts?.low_stock || 0,
          expiring_soon: suggestionsData.counts?.expiring_soon || 0,
          expired: suggestionsData.counts?.expired || 0,
          total: (suggestionsData.counts?.critical_low || 0) +
                 (suggestionsData.counts?.low_stock || 0) +
                 (suggestionsData.counts?.expiring_soon || 0) +
                 (suggestionsData.counts?.expired || 0)
        }

        // Process suggestions for the dialog
        suggestions.value = []
        if (suggestionsData.critical_low && suggestionsData.critical_low.length > 0) {
          suggestions.value.push({
            type: 'critical_low',
            title: 'Critical Low Stock',
            message: `Found ${suggestionsData.critical_low.length} products with critical low stock`,
            icon: 'pi pi-exclamation-circle',
            color: 'red',
            products: suggestionsData.critical_low
          })
        }

        if (suggestionsData.low_stock && suggestionsData.low_stock.length > 0) {
          suggestions.value.push({
            type: 'low_stock',
            title: 'Low Stock Alert',
            message: `Found ${suggestionsData.low_stock.length} products with low stock`,
            icon: 'pi pi-exclamation-triangle',
            color: 'orange',
            products: suggestionsData.low_stock
          })
        }

        if (suggestionsData.expiring_soon && suggestionsData.expiring_soon.length > 0) {
          suggestions.value.push({
            type: 'expiring_soon',
            title: 'Expiring Soon',
            message: `Found ${suggestionsData.expiring_soon.length} products expiring within 30 days`,
            icon: 'pi pi-clock',
            color: 'yellow',
            products: suggestionsData.expiring_soon
          })
        }

        if (suggestionsData.expired && suggestionsData.expired.length > 0) {
          suggestions.value.push({
            type: 'expired',
            title: 'Expired Products',
            message: `Found ${suggestionsData.expired.length} expired products`,
            icon: 'pi pi-times-circle',
            color: 'red',
            products: suggestionsData.expired
          })
        }
      } catch (error) {
        console.error('Error loading products and suggestions:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load products and suggestions',
          life: 3000
        })
      } finally {
        loadingProducts.value = false
      }
    }

    const addProduct = async () => {
      if (!newItem.value.product_id || !newItem.value.requested_quantity) {
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: 'Please select a product and enter quantity',
          life: 3000
        })
        return
      }

      try {
        addingProduct.value = true

        // Make API call to add the product
        const response = await axios.post(`/api/stock-movements/${props.movementId}/items`, {
          product_id: newItem.value.product_id,
          requested_quantity: newItem.value.requested_quantity,
          quantity_by_box: newItem.value.quantity_by_box,
          notes: newItem.value.notes
        })

        // Add the new item to the frontend immediately
        if (movement.value && response.data && response.data.data) {
          const newItemData = response.data.data

          // Initialize items array if it doesn't exist
          if (!movement.value.items) {
            movement.value.items = []
          }

          // Add the new item to the items array
          movement.value.items.unshift(newItemData) // Add to beginning for immediate visibility

          // Update the movement stats if needed
          if (movement.value.items_count !== undefined) {
            movement.value.items_count = movement.value.items.length
          }
        }

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Product added to request',
          life: 3000
        })

        // Reset form
        newItem.value = {
          product_id: null,
          requested_quantity: null,
          quantity_by_box: false,
          notes: ''
        }
        showAddProductDialog.value = false

        // No need to reload movement - item is already added to frontend
      } catch (error) {
        console.error('Error adding product:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to add product',
          life: 3000
        })
      } finally {
        addingProduct.value = false
      }
    }

    const updateItemQuantity = async (item) => {
      try {
        updatingQuantity.value = false
        updatingItemId.value = null
        // Update the frontend immediately for better UX
        if (movement.value?.items) {
          const itemIndex = movement.value.items.findIndex(i => i.id === item.id)
          if (itemIndex !== -1) {
            // Update the item in the local array with the new values
            movement.value.items[itemIndex] = {
              ...movement.value.items[itemIndex],
              requested_quantity: item.requested_quantity,
              quantity_by_box: item.quantity_by_box,
              notes: item.notes
            }
          }
        }

        // Make API call to update backend (but don't fetch updated info)
        await axios.put(`/api/stock-movements/${props.movementId}/items/${item.id}`, {
          product_id: item.product_id,
          requested_quantity: item.requested_quantity,
          quantity_by_box: item.quantity_by_box,
          notes: item.notes
        })

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Quantity updated',
          life: 2000
        })
      } catch (error) {
        console.error('Error updating quantity:', error)

        // If API call fails, we could rollback the frontend changes here
        // For now, just show error message
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to update quantity',
          life: 3000
        })
      } finally {
        updatingQuantity.value = false
        updatingItemId.value = null
      }
    }

    const removeItem = async (item) => {
      if (!confirm('Are you sure you want to remove this product from the request?')) {
        return
      }

      try {
        await axios.delete(`/api/stock-movements/${props.movementId}/items/${item.id}`)

        // Remove the item from the frontend immediately
        if (movement.value?.items) {
          const itemIndex = movement.value.items.findIndex(i => i.id === item.id)
          if (itemIndex !== -1) {
            movement.value.items.splice(itemIndex, 1)

            // Update the movement stats if needed
            if (movement.value.items_count !== undefined) {
              movement.value.items_count = movement.value.items.length
            }
          }
        }

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Product removed from request',
          life: 3000
        })

        // No need to reload movement - item is already removed from frontend
      } catch (error) {
        console.error('Error removing product:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to remove product',
          life: 3000
        })
      }
    }

    const sendRequest = async () => {
      if (!movement.value?.items || movement.value.items.length === 0) {
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: 'Please add at least one product before sending',
          life: 3000
        })
        return
      }

      try {
        sending.value = true
        await axios.post(`/api/stock-movements/${props.movementId}/send`)

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Request sent for approval',
          life: 3000
        })

        // Update the movement status in frontend immediately
        if (movement.value) {
          movement.value.status = 'pending' // Assuming the API changes status to pending
        }

        // Explicitly ensure add product dialog stays closed
        showAddProductDialog.value = false
        showSuggestionsDialog.value = false

        // No need to reload movement - status is already updated in frontend
      } catch (error) {
        console.error('Error sending request:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to send request',
          life: 3000
        })
      } finally {
        sending.value = false
      }
    }

    const openAddProductDialog = async () => {
      // Only allow opening dialog for draft movements
      if (movement.value?.status !== 'draft') {
        toast.add({
          severity: 'warn',
          summary: 'Not Allowed',
          detail: 'Cannot add products to non-draft movements',
          life: 3000
        })
        return
      }
      
      showAddProductDialog.value = true
      // Load available products if not already loaded or if providing service changed
      if (availableProducts.value.length === 0 || !movement.value?.providing_service_id) {
        await loadAvailableProducts()
      }
    }

    const openSuggestionsDialog = async () => {
      // Only allow opening dialog for draft movements
      if (movement.value?.status !== 'draft') {
        toast.add({
          severity: 'warn',
          summary: 'Not Allowed',
          detail: 'Cannot add suggestions to non-draft movements',
          life: 3000
        })
        return
      }
      
      showSuggestionsDialog.value = true
      // Reload suggestions when opening the dialog
      await loadAvailableProducts()
    }

    const addSelectedSuggestions = async () => {
      if (selectedSuggestionCategories.value.length === 0) {
        toast.add({
          severity: 'warn',
          summary: 'No Selection',
          detail: 'Please select at least one category',
          life: 3000
        })
        return
      }

      try {
        const allProducts = []
        selectedSuggestionCategories.value.forEach(category => {
          const suggestion = suggestions.value.find(s => s.type === category)
          if (suggestion) {
            allProducts.push(...suggestion.products)
          }
        })

        await addSuggestedProducts(allProducts)
        showSuggestionsDialog.value = false
        selectedSuggestionCategories.value = []
      } catch (error) {
        console.error('Error adding selected suggestions:', error)
      }
    }

    const getSelectedCount = () => {
      return selectedSuggestionCategories.value.length
    }

    const getProductStock = (productId) => {
      const product = availableProducts.value.find(p => p.id === productId)
      if (product) {
        // Priority: Check for total quantity fields first (these represent actual total stock)
        if (product.total_quantity !== undefined && product.total_quantity !== null) {
          return product.total_quantity
        }
        if (product.total_stock !== undefined && product.total_stock !== null) {
          return product.total_stock
        }
        if (product.actual_stock !== undefined && product.actual_stock !== null) {
          return product.actual_stock
        }
        if (product.available_quantity !== undefined && product.available_quantity !== null) {
          return product.available_quantity
        }

        // Then check current_stock from suggestions API
        if (product.current_stock !== undefined && product.current_stock !== null) {
          return product.current_stock
        }

        // Fallback to other possible stock properties (these might be box counts)
        return product.stock || product.quantity || product.available_stock || 0
      }
      return 0
    }

    const getProductType = (product) => {
      // Priority: Check for type-related fields first
      if (product.type && product.type !== '') {
        return product.type
      }
      if (product.product_type && product.product_type !== '') {
        return product.product_type
      }
      if (product.type_name && product.type_name !== '') {
        return product.type_name
      }

      // Then check category-related fields
      if (product.category_name && product.category_name !== '') {
        return product.category_name
      }
      if (product.category && product.category !== '') {
        return product.category
      }
      if (product.product_category && product.product_category !== '') {
        return product.product_category
      }

      // Fallback to default
      return 'General'
    }

    const getSuggestionIconClass = (color) => {
      const classes = {
        'orange': 'tw-bg-orange-500',
        'red': 'tw-bg-red-500',
        'blue': 'tw-bg-blue-500'
      }
      return classes[color] || 'tw-bg-gray-500'
    }

    const getSuggestionButtonClass = (color) => {
      const classes = {
        'red': 'p-button-outlined p-button-danger',
        'orange': 'p-button-outlined p-button-warning',
        'yellow': 'p-button-outlined p-button-help',
        'blue': 'p-button-outlined p-button-info'
      }
      return classes[color] || 'p-button-outlined'
    }

    const getExpiryIconClass = (type) => {
      const icons = {
        'critical_low': 'pi pi-exclamation-circle tw-text-red-500',
        'low_stock': 'pi pi-exclamation-triangle tw-text-orange-500',
        'expiring_soon': 'pi pi-clock tw-text-yellow-500',
        'expired': 'pi pi-times-circle tw-text-gray-500'
      }
      return icons[type] || 'pi pi-calendar tw-text-gray-500'
    }

    const getExpiryTextClass = (type) => {
      const classes = {
        'critical_low': 'tw-text-red-700',
        'low_stock': 'tw-text-orange-700',
        'expiring_soon': 'tw-text-yellow-700',
        'expired': 'tw-text-gray-700'
      }
      return classes[type] || 'tw-text-gray-700'
    }

    const getExpiryBadgeClass = (type) => {
      const classes = {
        'critical_low': 'tw-bg-red-100 tw-text-red-800',
        'low_stock': 'tw-bg-orange-100 tw-text-orange-800',
        'expiring_soon': 'tw-bg-yellow-100 tw-text-yellow-800',
        'expired': 'tw-bg-gray-100 tw-text-gray-800'
      }
      return classes[type] || 'tw-bg-gray-100 tw-text-gray-800'
    }

    const getExpiryText = (product, type) => {
      if (!product.expiry_date) return 'No expiry'

      if (type === 'expired') {
        const daysExpired = product.days_expired || 0
        return `${daysExpired} days ago`
      } else if (type === 'expiring_soon') {
        const daysLeft = product.days_until_expiry || 0
        return `${daysLeft} days left`
      } else {
        const daysLeft = product.days_until_expiry || 0
        return daysLeft > 0 ? `${daysLeft} days left` : 'Expired'
      }
    }

    const addSingleProduct = async (product) => {
      try {
        const response = await axios.post(`/api/stock-movements/${props.movementId}/items`, {
          product_id: product.id,
          requested_quantity: 1, // Default quantity, can be adjusted later
          quantity_by_box: false, // Default to units mode
          notes: getSuggestionNote(product)
        })

        // Add the new item to the frontend immediately
        if (movement.value && response.data && response.data.data) {
          const newItemData = response.data.data

          // Initialize items array if it doesn't exist
          if (!movement.value.items) {
            movement.value.items = []
          }

          // Add the new item to the items array
          movement.value.items.unshift(newItemData) // Add to beginning for immediate visibility

          // Update the movement stats if needed
          if (movement.value.items_count !== undefined) {
            movement.value.items_count = movement.value.items.length
          }
        }

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Added ${product.name} to request`,
          life: 3000
        })

        // No need to reload movement - item is already added to frontend
      } catch (error) {
        console.error('Error adding product:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: `Failed to add ${product.name}`,
          life: 3000
        })
      }
    }

    const addSuggestedProducts = async (products) => {
      try {
        const results = []
        const newItems = []

        for (const product of products) {
          try {
            const response = await axios.post(`/api/stock-movements/${props.movementId}/items`, {
              product_id: product.id,
              requested_quantity: 1, // Default quantity, can be adjusted later
              quantity_by_box: false, // Default to units mode
              notes: getSuggestionNote(product)
            })

            // Store the new item data for frontend update
            if (response.data && response.data.data) {
              newItems.push(response.data.data)
            }

            results.push({ product: product.name, success: true })
          } catch (error) {
            results.push({ product: product.name, success: false, error: error.message })
          }
        }

        // Add all new items to the frontend immediately
        if (movement.value && newItems.length > 0) {
          // Initialize items array if it doesn't exist
          if (!movement.value.items) {
            movement.value.items = []
          }

          // Add all new items to the beginning of the items array
          movement.value.items.unshift(...newItems)

          // Update the movement stats if needed
          if (movement.value.items_count !== undefined) {
            movement.value.items_count = movement.value.items.length
          }
        }

        const successCount = results.filter(r => r.success).length
        const failCount = results.filter(r => !r.success).length

        if (successCount > 0) {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Added ${successCount} suggested product${successCount > 1 ? 's' : ''} to request`,
            life: 3000
          })
        }

        if (failCount > 0) {
          toast.add({
            severity: 'warn',
            summary: 'Partial Success',
            detail: `${failCount} product${failCount > 1 ? 's' : ''} could not be added`,
            life: 3000
          })
        }

        // No need to reload movement - items are already added to frontend
      } catch (error) {
        console.error('Error adding suggested products:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to add suggested products',
          life: 3000
        })
      }
    }

    const getSuggestionNote = (product) => {
      if (product.expiry_date) {
        const daysUntilExpiry = Math.ceil((new Date(product.expiry_date) - new Date()) / (1000 * 60 * 60 * 24))
        if (daysUntilExpiry < 0) {
          return `Added from expired products (expired ${Math.abs(daysUntilExpiry)} days ago)`
        } else if (daysUntilExpiry <= 30) {
          return `Added from expiring soon (${daysUntilExpiry} days left)`
        }
      }
      return `Added from low stock alert (current: ${product.current_stock})`
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        'draft': 'tw-bg-gray-100 tw-text-gray-800',
        'pending': 'tw-bg-yellow-100 tw-text-yellow-800',
        'approved': 'tw-bg-green-100 tw-text-green-800',
        'rejected': 'tw-bg-red-100 tw-text-red-800',
        'executed': 'tw-bg-blue-100 tw-text-blue-800'
      }
      return classes[status] || 'tw-bg-gray-100 tw-text-gray-800'
    }

    const getStatusIcon = (status) => {
      const icons = {
        'draft': 'pi pi-pencil',
        'pending': 'pi pi-clock',
        'approved': 'pi pi-check-circle',
        'rejected': 'pi pi-times-circle',
        'executed': 'pi pi-check'
      }
      return icons[status] || 'pi pi-question-circle'
    }

    const onQuantityModeChange = () => {
      // Reset quantity when switching modes
    
    }

    const getSelectedProductUnit = () => {
      if (!newItem.value.product_id) return 'units'
      const product = selectedProduct.value
      if (!product) return 'units'

      if (newItem.value.quantity_by_box && product.boite_de != null) {
        return 'boxes'
      }
      return product.unit || product.forme || 'units'
    }

    const getProductUnit = (product) => {
      if (!product) return 'units'
      return product.unit || product.forme || 'units'
    }

    const formatDate = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString()
    }

    // New methods for list management
    const handleSearch = () => {
      currentPage.value = 1 // Reset to first page when searching
    }

    const handleFilter = () => {
      currentPage.value = 1 // Reset to first page when filtering
    }

    const handlePageSizeChange = () => {
      currentPage.value = 1 // Reset to first page when changing page size
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    // Quantity Dialog Methods
    const showQuantityDialog = (product, type) => {
      quantityDialogProduct.value = product
      quantityDialogType.value = type
      // Set default quantity from order_quantity if available, otherwise 1
      quantityDialogQuantity.value = product.order_quantity || 1
      showQuantityDialogState.value = true
    }

    const closeQuantityDialog = () => {
      showQuantityDialogState.value = false
      quantityDialogProduct.value = null
      quantityDialogType.value = ''
      quantityDialogQuantity.value = 1
    }

    const addProductWithQuantity = async () => {
      if (!quantityDialogProduct.value || !quantityDialogQuantity.value) return

      try {
        const response = await axios.post(`/api/stock-movements/${props.movementId}/items`, {
          product_id: quantityDialogProduct.value.id,
          requested_quantity: quantityDialogQuantity.value,
          quantity_by_box: false, // Default to units mode
          notes: getSuggestionNote(quantityDialogProduct.value)
        })

        // Add the new item to the frontend immediately
        if (movement.value && response.data && response.data.data) {
          const newItemData = response.data.data

          // Initialize items array if it doesn't exist
          if (!movement.value.items) {
            movement.value.items = []
          }

          // Add the new item to the items array
          movement.value.items.unshift(newItemData) // Add to beginning for immediate visibility

          // Update the movement stats if needed
          if (movement.value.items_count !== undefined) {
            movement.value.items_count = movement.value.items.length
          }
        }

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Added ${quantityDialogProduct.value.name} (${quantityDialogQuantity.value} ${getProductUnit(quantityDialogProduct.value)}) to request`,
          life: 3000
        })

        closeQuantityDialog()
      } catch (error) {
        console.error('Error adding product:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: `Failed to add ${quantityDialogProduct.value.name}`,
          life: 3000
        })
      }
    }

    // Lifecycle
    onMounted(async () => {
      await loadMovement()
      if (movement.value?.providing_service_id) {
        await loadAvailableProducts()
      }
    })

    return {
      movement,
      availableProducts,
      selectedProduct,
      suggestions,
      suggestionCounts,
      showAddProductDialog,
      showSuggestionsDialog,
      addingProduct,
      sending,
      loading,
      updatingQuantity,
      newItem,
      selectedSuggestionCategories,
      loadingProducts,
      canEdit,
      updatingItemId,
      // New reactive data
      searchQuery,
      selectedCategory,
      currentPage,
      pageSize,
      pageSizeOptions,
      // New computed properties
      categoryOptions,
      filteredProducts,
      totalPages,
      paginatedProducts,
      visiblePages,
      quantityModeText,
      // Existing methods
      loadMovement,
      loadAvailableProducts,
      openAddProductDialog,
      openSuggestionsDialog,
      addSelectedSuggestions,
      getSelectedCount,
      addProduct,
      updateItemQuantity,
      removeItem,
      sendRequest,
      getProductStock,
      getProductType,
      getSuggestionIconClass,
      getSuggestionButtonClass,
      getExpiryIconClass,
      getExpiryTextClass,
      getExpiryBadgeClass,
      getExpiryText,
      addSuggestedProducts,
      getSuggestionNote,
      getStatusBadgeClass,
      getStatusIcon,
      getSelectedProductUnit,
      getProductUnit,
      formatDate,
      onQuantityModeChange,
      // New methods
      handleSearch,
      handleFilter,
      handlePageSizeChange,
      goToPage,
      // Quantity Dialog
      showQuantityDialogState,
      quantityDialogProduct,
      quantityDialogType,
      quantityDialogQuantity,
      openQuantityDialog: showQuantityDialog,
      closeQuantityDialog,
      addProductWithQuantity
    }
  }
}
</script>

<style scoped>
/* Custom animations and transitions */
.tw-shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.p-button {
  transition: all 0.3s ease-in-out;
}

.p-button:hover {
  transform: translateY(-1px);
}

.p-card {
  border-radius: 1rem;
  border: none;
}

/* Dialog styling */
.p-dialog {
  border-radius: 1rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.p-dialog .p-dialog-header {
  border-radius: 1rem 1rem 0 0;
  padding: 1.5rem;
}

.p-dialog .p-dialog-content {
  padding: 2rem;
}

/* Input field enhancements */
.p-inputtext:focus,
.p-textarea:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  border-color: rgb(59, 130, 246);
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

.tw-animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-1.tw-lg\\:grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .tw-text-4xl {
    font-size: 2rem;
  }

  .tw-p-8 {
    padding: 1.5rem;
  }
}

/* Custom scrollbar for dialog content */
.p-dialog .p-dialog-content::-webkit-scrollbar {
  width: 6px;
}

.p-dialog .p-dialog-content::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.p-dialog .p-dialog-content::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.p-dialog .p-dialog-content::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Custom InputNumber styling */
:deep(.p-inputnumber) {
  width: 100%;
}

:deep(.p-inputnumber-input) {
  text-align: center;
  font-weight: 600;
  font-size: 1.125rem;
  color: #111827;
  background: white;
  border: none;
  border-radius: 0.75rem;
  padding: 0.5rem 1rem;
  transition: all 0.3s ease;
}

:deep(.p-inputnumber-input:focus) {
  outline: none;
  box-shadow: none;
}

:deep(.p-inputnumber-buttons) {
  display: none;
}

:deep(.p-inputnumber-button) {
  display: none;
}

/* Search input styling */
:deep(.p-inputtext) {
  border-radius: 0.75rem;
  border: 2px solid #d1d5db;
  transition: all 0.3s ease;
}

:deep(.p-inputtext:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-inputtext:hover) {
  border-color: #93c5fd;
}

/* Dropdown styling */
:deep(.p-dropdown) {
  border-radius: 0.75rem;
  border: 2px solid #d1d5db;
  transition: all 0.3s ease;
}

:deep(.p-dropdown:hover) {
  border-color: #93c5fd;
}

:deep(.p-dropdown:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Pagination button styling */
:deep(.p-button-text) {
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

:deep(.p-button-text:hover) {
  background-color: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

/* Compact view grid adjustments */
@media (max-width: 768px) {
  .tw-grid-cols-1.md\\:tw-grid-cols-2.xl\\:tw-grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

@media (min-width: 768px) and (max-width: 1279px) {
  .tw-grid-cols-1.md\\:tw-grid-cols-2.xl\\:tw-grid-cols-3 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1280px) {
  .tw-grid-cols-1.md\\:tw-grid-cols-2.xl\\:tw-grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>
