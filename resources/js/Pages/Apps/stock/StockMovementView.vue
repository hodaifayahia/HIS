<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
      <template #content>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2">Stock Movement Details</h1>
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
          </div>
        </div>
      </template>
    </Card>

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

    <div v-else class="tw-max-w-9xl tw-mx-auto tw-px-6 tw-py-8">
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
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Status</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900 tw-capitalize">{{ getStatusDisplay(movement?.status) }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-green-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i :class="getStatusIcon(movement?.status)" class="tw-text-green-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Created</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900">{{ formatDate(movement?.created_at) }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-purple-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-calendar tw-text-purple-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Last Updated</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900">{{ formatDate(movement?.updated_at) }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-orange-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-clock tw-text-orange-600 tw-text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Approval Management Section -->
      <div v-if="canShowApprovalSection" class="tw-mb-8">
        <Card class="tw-shadow-lg tw-border-0">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-p-6 tw-rounded-t-2xl">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-bg-white tw-bg-opacity-20 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-shield tw-text-lg"></i>
                  </div>
                  <div>
                    <h3 class="tw-text-xl tw-font-bold tw-mb-1">Approval Management</h3>
                    <p class="tw-text-indigo-100 tw-text-sm">Review and approve selected products for transit</p>
                  </div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <span class="tw-px-3 tw-py-1 tw-bg-white tw-bg-opacity-20 tw-rounded-full tw-text-sm tw-font-medium">
                    {{ getSelectedItemsCount() }} items selected
                  </span>
                </div>
              </div>
            </div>
          </template>
          <template #content>
            <div class="tw-p-6">
              <div v-if="getSelectedItemsCount() === 0" class="tw-text-center tw-py-8">
                <div class="tw-w-16 tw-h-16 tw-bg-gray-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                  <i class="pi pi-info-circle tw-text-2xl tw-text-gray-400"></i>
                </div>
                <h4 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">No Items Selected</h4>
                <p class="tw-text-gray-600">Please select inventory items for products before proceeding with approval.</p>
              </div>
              
              <div v-else class="tw-space-y-4">
                <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
                  <div class="tw-flex tw-items-start tw-gap-3">
                    <i class="pi pi-info-circle tw-text-blue-600 tw-mt-1"></i>
                    <div>
                      <h4 class="tw-font-semibold tw-text-blue-800 tw-mb-1">Approval Process</h4>
                      <p class="tw-text-blue-700 tw-text-sm tw-leading-relaxed">
                        Approving will move the selected items to <strong>transit status</strong>. 
                        Items will be reserved from the source stock but not yet added to the requester's inventory.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="tw-flex tw-items-center tw-justify-between tw-pt-4">
                  <div class="tw-flex tw-items-center tw-gap-4">
                    <Button
                      @click="approveSelectedItems"
                      :loading="approvalLoading"
                      label="Approve for Transit"
                      icon="pi pi-check"
                      class="tw-bg-green-600 hover:tw-bg-green-700 tw-border-green-600 hover:tw-border-green-700"
                    />
                    <Button
                      @click="rejectSelectedItems"
                      :loading="rejectionLoading"
                      label="Reject Request"
                      icon="pi pi-times"
                      severity="danger"
                      outlined
                    />
                  </div>
                  <div class="tw-text-sm tw-text-gray-600">
                    Status: <span class="tw-font-semibold tw-capitalize">{{ movement?.status || 'Unknown' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="tw-grid tw-grid-cols-1 tw-xl:grid-cols-3 tw-gap-8">
        <div class="tw-xl:col-span-1">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-100 tw-overflow-hidden">
            <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-p-6 tw-text-white">
              <h3 class="tw-text-xl tw-font-bold tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle"></i>
                Request Details
              </h3>
            </div>

            <div class="tw-p-6 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
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

              <div class="tw-group tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-rounded-xl tw-p-3 tw-border tw-border-purple-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-purple-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-user tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-purple-800 tw-mb-1">Requested By</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ movement.requesting_user?.name }}</dd>
                    <dd class="tw-text-xs tw-text-gray-600">{{ movement.requesting_user?.email }}</dd>
                  </div>
                </div>
              </div>

              <div class="tw-group tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-rounded-xl tw-p-3 tw-border tw-border-gray-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-gray-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-info-circle tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-gray-800 tw-mb-1">Status</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium tw-capitalize">{{ movement.status }}</dd>
                  </div>
                </div>
              </div>

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

              <div v-if="movement.approval_notes" class="tw-group tw-bg-gradient-to-r tw-from-red-50 tw-to-red-100 tw-rounded-xl tw-p-3 tw-border tw-border-red-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-red-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-comment tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-red-800 tw-mb-1">Approval Notes</dt>
                    <dd class="tw-text-sm tw-text-gray-900">{{ movement.approval_notes }}</dd>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="tw-xl:col-span-2">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-100 tw-overflow-hidden">
            <div class="tw-bg-gradient-to-r tw-from-slate-600 tw-to-slate-700 tw-p-6 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between">
                <h3 class="tw-text-xl tw-font-bold tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-box"></i>
                  Requested Products
                  <span class="tw-text-sm tw-font-normal tw-text-slate-300">({{ movement.items?.length || 0 }} items)</span>
                </h3>
              </div>
            </div>

            <div class="tw-p-6">
              <div v-if="movement.items && movement.items.length > 0">
                <!-- Enhanced Header Section -->
                <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-rounded-2xl tw-p-6 tw-mb-6 tw-border tw-border-blue-100 tw-shadow-sm">
                  <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-4">
                    <div class="tw-flex tw-items-center tw-gap-4">
                      <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
                        <i class="pi pi-list tw-text-white tw-text-xl"></i>
                      </div>
                      <div>
                        <h4 class="tw-text-xl tw-font-bold tw-text-gray-900 tw-mb-1">Products List</h4>
                        <p class="tw-text-sm tw-text-gray-600">Manage and track requested inventory items</p>
                      </div>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-4">
                      <div class="tw-bg-white tw-rounded-xl tw-px-4 tw-py-2 tw-shadow-sm tw-border tw-border-gray-200">
                        <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                          <i class="pi pi-info-circle tw-text-blue-600"></i>
                          <span class="tw-font-semibold tw-text-gray-900">{{ filteredProducts.length }}</span>
                          <span class="tw-text-gray-600">of {{ movement.items.length }} products</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Enhanced DataTable -->
                <DataTable 
                  :value="paginatedProducts" 
                  :paginator="totalPages > 1"
                  :rows="pageSize"
                  :totalRecords="filteredProducts.length"
                  :lazy="true"
                  @page="onPageChange"
                  class="tw-rounded-2xl tw-overflow-hidden tw-shadow-lg tw-border tw-border-gray-200"
                  :pt="{
                    root: 'tw-bg-white',
                    header: 'tw-bg-gradient-to-r tw-from-slate-50 tw-to-slate-100 tw-border-b tw-border-gray-200 tw-p-0',
                    table: 'tw-w-full',
                    thead: 'tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100',
                    tbody: 'tw-divide-y tw-divide-gray-100',
                    row: 'tw-hover:bg-blue-50/40 tw-transition-all tw-duration-200',
                    column: 'tw-px-6 tw-py-4',
                    headerCell: 'tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-border-b tw-border-gray-200',
                    bodyCell: 'tw-px-6 tw-py-4 tw-whitespace-nowrap',
                    paginator: 'tw-bg-gray-50 tw-border-t tw-border-gray-200 tw-px-6 tw-py-4'
                  }"
                  stripedRows
                  responsiveLayout="scroll"
                >
                  <!-- Product Column -->
                  <Column field="product" header="Product" class="tw-min-w-80">
                    <template #body="{ data }">
                      <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-relative">
                          <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-md tw-ring-2 tw-ring-blue-100">
                            <i class="pi pi-box tw-text-white tw-text-lg"></i>
                          </div>
                          <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-500 tw-rounded-full tw-border-2 tw-border-white"></div>
                        </div>
                        <div class="tw-min-w-0 tw-flex-1">
                          <div class="tw-text-sm tw-font-bold tw-text-gray-900 tw-mb-1 tw-truncate">
                            {{ data.product?.name || 'Unknown Product' }}
                          </div>
                          <div class="tw-flex tw-items-center tw-gap-3 tw-text-xs tw-text-gray-500">
                            <div class="tw-flex tw-items-center tw-gap-1 tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded-md">
                              <i class="pi pi-tag tw-text-xs"></i>
                              <span class="tw-font-medium">{{ data.product?.code || 'N/A' }}</span>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1 tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded-md tw-text-blue-700">
                              <i class="pi pi-map-marker tw-text-xs"></i>
                              <span class="tw-font-medium">{{ data.product?.stockage_name || 'Main Storage' }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Quantity Column -->
                  <Column field="quantity" header="Requested Quantity" class="tw-min-w-48">
                    <template #body="{ data }">
                      <div class="tw-space-y-2">
                        <div class="tw-flex tw-items-center tw-gap-2">
                          <div class="tw-bg-gradient-to-r tw-from-emerald-500 tw-to-emerald-600 tw-text-white tw-px-3 tw-py-1 tw-rounded-lg tw-text-sm tw-font-bold tw-shadow-sm">
                            {{ getCalculatedQuantity(data) }}
                          </div>
                          <span class="tw-text-sm tw-font-medium tw-text-gray-700">{{ getProductUnit(data.product) }}</span>
                        </div>
                        <div v-if="data.quantity_by_box && data.product?.boite_de" class="tw-inline-flex tw-items-center tw-gap-1 tw-bg-blue-50 tw-text-blue-700 tw-px-2 tw-py-1 tw-rounded-md tw-text-xs tw-font-medium">
                          <i class="pi pi-box tw-text-xs"></i>
                          <span>{{ data.requested_quantity }} boxes</span>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Unit Column -->
                  <Column field="unit" header="Unit" class="tw-min-w-32">
                    <template #body="{ data }">
                      <div class="tw-space-y-2">
                        <div class="tw-inline-flex tw-items-center tw-gap-2 tw-bg-gray-100 tw-px-3 tw-py-1 tw-rounded-lg">
                          <i class="pi pi-calculator tw-text-gray-600 tw-text-xs"></i>
                          <span class="tw-text-sm tw-font-semibold tw-text-gray-800">
                            {{ data.quantity_by_box && data.product?.boite_de ? 'Boxes' : (data.product?.unit || 'units') }}
                          </span>
                        </div>
                        <div v-if="data.duration" class="tw-inline-flex tw-items-center tw-gap-1 tw-bg-purple-100 tw-text-purple-700 tw-px-2 tw-py-1 tw-rounded-md tw-text-xs tw-font-medium">
                          <i class="pi pi-clock tw-text-xs"></i>
                          <span>{{ data.duration }} {{ data.duration_unit }}</span>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Status Column -->
                  <Column field="status" header="Status" class="tw-min-w-48">
                    <template #body="{ data }">
                      <div class="tw-space-y-2">
                        <div v-if="data.approved_quantity !== null && data.approved_quantity !== undefined" class="tw-inline-flex tw-items-center tw-gap-2 tw-bg-green-50 tw-text-green-700 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-green-200">
                          <i class="pi pi-check-circle tw-text-green-600"></i>
                          <span class="tw-text-sm tw-font-semibold">Approved: {{ data.approved_quantity }}</span>
                        </div>
                        <div v-if="data.executed_quantity !== null && data.executed_quantity !== undefined" class="tw-inline-flex tw-items-center tw-gap-2 tw-bg-blue-50 tw-text-blue-700 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-blue-200">
                          <i class="pi pi-check tw-text-blue-600"></i>
                          <span class="tw-text-sm tw-font-semibold">Executed: {{ data.executed_quantity }}</span>
                        </div>
                        <div v-if="data.status === 'transit'" class="tw-inline-flex tw-items-center tw-gap-2 tw-bg-blue-50 tw-text-blue-700 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-blue-200">
                          <i class="pi pi-send tw-text-blue-600"></i>
                          <span class="tw-text-sm tw-font-semibold">In Transit</span>
                        </div>
                        <div v-if="data.approved_quantity === null && data.executed_quantity === null && data.status !== 'transit'" class="tw-inline-flex tw-items-center tw-gap-2 tw-bg-amber-50 tw-text-amber-700 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-amber-200">
                          <i class="pi pi-clock tw-text-amber-600"></i>
                          <span class="tw-text-sm tw-font-semibold">{{ getItemStatusDisplay(data.status) }}</span>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Notes Column -->
                  <Column field="notes" header="Notes" class="tw-min-w-48">
                    <template #body="{ data }">
                      <div class="tw-max-w-xs">
                        <div v-if="data.notes" class="tw-bg-gray-50 tw-p-3 tw-rounded-lg tw-border tw-border-gray-200">
                          <p class="tw-text-sm tw-text-gray-700 tw-leading-relaxed">{{ data.notes }}</p>
                        </div>
                        <div v-else class="tw-text-sm tw-text-gray-400 tw-italic tw-flex tw-items-center tw-gap-2">
                          <i class="pi pi-minus-circle tw-text-xs"></i>
                          <span>No notes</span>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <!-- Actions Column -->
                  <Column field="actions" header="Actions" class="tw-min-w-56">
                    <template #body="{ data }">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <Button
                          @click="openProductSelection(data)"
                          :label="data.selected_inventory && data.selected_inventory.length > 0 ? 'Edit Selection' : 'Select Products'"
                          :icon="data.selected_inventory && data.selected_inventory.length > 0 ? 'pi pi-pencil' : 'pi pi-plus'"
                          :severity="data.selected_inventory && data.selected_inventory.length > 0 ? 'warning' : 'primary'"
                          size="small"
                          class="tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                          :pt="{
                            root: data.selected_inventory && data.selected_inventory.length > 0 ? 'tw-bg-orange-500 hover:tw-bg-orange-600 tw-text-white tw-border-orange-500 tw-px-3 tw-py-2 tw-rounded-lg tw-font-medium' : 'tw-bg-blue-500 hover:tw-bg-blue-600 tw-text-white tw-border-blue-500 tw-px-3 tw-py-2 tw-rounded-lg tw-font-medium'
                          }"
                          v-tooltip.top="data.selected_inventory && data.selected_inventory.length > 0 ? 'Edit existing inventory selection' : 'Select specific inventory items to provide'"
                        />
                        <Button
                          v-if="data.selected_inventory && data.selected_inventory.length > 0"
                          @click="viewSelectedProducts(data)"
                          icon="pi pi-eye"
                          severity="secondary"
                          outlined
                          size="small"
                          class="tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                          v-tooltip.top="'View selected products'"
                        />
                      </div>
                    </template>
                  </Column>
                </DataTable>
              </div>

              <div v-else class="tw-text-center tw-py-16">
                <div class="tw-relative">
                  <div class="tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6 tw-shadow-lg">
                    <i class="pi pi-box tw-text-4xl tw-text-gray-400"></i>
                  </div>
                  <div class="tw-absolute tw-inset-0 tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-blue-400 tw-to-purple-500 tw-rounded-full tw-opacity-20 tw-animate-pulse"></div>
                </div>
                <h3 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-2">No products found</h3>
                <p class="tw-text-gray-600 tw-max-w-md tw-mx-auto">This request doesn't have any products associated with it.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ProductSelectionDialog
    v-model:visible="showProductSelectionDialog"
    :selected-item="selectedItemForSelection"
    :movement-id="movementId"
    @selection-saved="onSelectionSaved"
  />

  <!-- View Selections Modal -->
  <Dialog
    v-model:visible="showViewSelectionsDialog"
    modal
    :header="`Selected Inventory for ${selectedItemForView?.product?.name || 'Product'}`"
    :style="{ width: '80vw', maxWidth: '1200px' }"
    class="tw-rounded-2xl"
  >
    <div v-if="selectedItemForView?.selected_inventory?.length > 0" class="tw-space-y-4">
      <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
        <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
          <i class="pi pi-info-circle tw-text-blue-600"></i>
          <span class="tw-font-semibold tw-text-blue-800">Selection Summary</span>
        </div>
        <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-text-sm">
          <div>
            <span class="tw-text-gray-600">Total Selected Items:</span>
            <span class="tw-font-semibold tw-ml-2">{{ selectedItemForView.selected_inventory.length }}</span>
          </div>
          <div>
            <span class="tw-text-gray-600">Total Quantity:</span>
            <span class="tw-font-semibold tw-ml-2">{{ getTotalSelectedQuantity(selectedItemForView.selected_inventory) }}</span>
          </div>
        </div>
      </div>

      <div class="tw-overflow-x-auto">
        <table class="tw-w-full tw-border tw-border-gray-200 tw-rounded-lg tw-overflow-hidden">
          <thead class="tw-bg-gray-50">
            <tr>
              <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                 Inventory Item
               </th>
              
               <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                 Barcode
               </th>
               <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                 Batch/Lot
               </th>
               <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                 Serial Number
               </th>
               <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                 Expiry Date
               </th>
               <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                 Available
               </th>
               <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                 Selected Quantity
               </th>
            </tr>
          </thead>
          <tbody class="tw-divide-y tw-divide-gray-200">
            <tr
              v-for="selection in selectedItemForView.selected_inventory"
              :key="selection.id"
              class="tw-hover:bg-gray-50"
            >
              <td class="tw-px-4 tw-py-3">
                 <div class="tw-flex tw-items-center tw-gap-3">
                   <div class="tw-w-8 tw-h-8 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                     <i class="pi pi-box tw-text-blue-600 tw-text-sm"></i>
                   </div>
                   <div>
                     <div class="tw-text-sm tw-font-medium tw-text-gray-900">
                       {{ selection.inventory?.product?.name || 'Unknown Product' }}
                     </div>
                     <div class="tw-text-xs tw-text-gray-500">
                       {{ selection.inventory?.product?.code || 'No Code' }}
                     </div>
                   </div>
                 </div>
               </td>
              
               <td class="tw-px-4 tw-py-3">
                 <div class="tw-text-sm tw-text-gray-900">
                   {{ selection.inventory?.barcode || 'No Barcode' }}
                 </div>
               </td>
               <td class="tw-px-4 tw-py-3">
                 <div class="tw-text-sm tw-text-gray-900">
                   {{ selection.inventory?.batch_number || 'No Batch' }}
                 </div>
               </td>
               <td class="tw-px-4 tw-py-3">
                 <div class="tw-text-sm tw-text-gray-900">
                   {{ selection.inventory?.serial_number || 'No Serial' }}
                 </div>
               </td>
               <td class="tw-px-4 tw-py-3">
                 <div class="tw-text-sm tw-text-gray-900">
                   {{ formatDate(selection.inventory?.expiry_date) || 'No Expiry' }}
                 </div>
               </td>
               <td class="tw-px-4 tw-py-3">
                 <div class="tw-text-sm tw-text-gray-900">
                   {{ selection.inventory?.quantity || 0 }}
                 </div>
               </td>
               <td class="tw-px-4 tw-py-3">
                 <div class="tw-flex tw-items-center tw-gap-2">
                   <span class="tw-text-sm tw-font-semibold tw-text-blue-600">
                     {{ selection.quantity || 0 }}
                   </span>
                   <span class="tw-text-xs tw-text-gray-500">
                     {{ selection.inventory?.product?.unit || 'units' }}
                   </span>
                 </div>
               </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="tw-text-center tw-py-8">
      <div class="tw-w-16 tw-h-16 tw-bg-gray-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
        <i class="pi pi-inbox tw-text-2xl tw-text-gray-400"></i>
      </div>
      <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">No Selections Made</h3>
      <p class="tw-text-gray-600">No inventory items have been selected for this product yet.</p>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-2">
        <Button
          @click="showViewSelectionsDialog = false"
          label="Close"
          severity="secondary"
          outlined
        />
        <Button
          v-if="selectedItemForView?.selected_inventory?.length > 0"
          @click="editSelections"
          label="Edit Selections"
          icon="pi pi-pencil"
        />
      </div>
    </template>
  </Dialog>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProductSelectionDialog from './ProductSelectionDialog.vue'

export default {
  name: 'StockMovementView',
  components: {
    Button,
    Card,
    Dialog,
    DataTable,
    Column,
    ProductSelectionDialog
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

    // Reactive data
    const movement = ref({})
    const loading = ref(true)
    const currentPage = ref(1)
    const pageSize = ref(10)
    const showProductSelectionDialog = ref(false)
    const selectedItemForSelection = ref(null)
    const showViewSelectionsDialog = ref(false)
    const selectedItemForView = ref(null)
    const approvalLoading = ref(false)
    const rejectionLoading = ref(false)

    // Computed
    const filteredProducts = computed(() => {
      return movement.value?.items || []
    })

    const canShowApprovalSection = computed(() => {
      return movement.value?.status === 'pending' && getSelectedItemsCount() > 0
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredProducts.value.length / pageSize.value)
    })

    const paginatedProducts = computed(() => {
      const start = (currentPage.value - 1) * pageSize.value
      const end = start + pageSize.value
      return filteredProducts.value.slice(start, end)
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
      } finally {
        loading.value = false
      }
    }

    const getCalculatedQuantity = (item) => {
      if (!item) return 0
      
      const baseQuantity = item.requested_quantity || 0
      
      // If quantity_by_box is true, multiply by boite_de to get the actual unit quantity
      if (item.quantity_by_box && item.product?.boite_de) {
        return baseQuantity * item.product.boite_de
      }
      
      return baseQuantity
    }

    const getProductUnit = (product) => {
      if (!product) return 'units'
      return product.unit || product.forme || 'units'
    }

    const formatDate = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString()
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const openProductSelection = (item) => {
      selectedItemForSelection.value = item
      showProductSelectionDialog.value = true
    }

    const viewSelectedProducts = (item) => {
      selectedItemForView.value = item
      showViewSelectionsDialog.value = true
    }

    const getTotalSelectedQuantity = (selectedInventory) => {
      if (!selectedInventory || !Array.isArray(selectedInventory)) return 0
      return selectedInventory.reduce((total, selection) => {
        return total + parseFloat(selection.quantity || 0)
      }, 0)
    }

    const editSelections = () => {
      showViewSelectionsDialog.value = false
      openProductSelection(selectedItemForView.value)
    }

    const onSelectionSaved = async (data) => {
      // Refresh the movement data to show updated selections
      await loadMovement()
    }

    const onPageChange = (event) => {
      currentPage.value = event.page + 1
    }

    const getSelectedItemsCount = () => {
      if (!movement.value?.items) return 0
      return movement.value.items.filter(item => 
        item.selected_inventory && item.selected_inventory.length > 0
      ).length
    }

    const getStatusDisplay = (status) => {
      const statusMap = {
        'pending': 'Pending',
        'approved': 'Approved',
        'transit': 'In Transit',
        'fulfilled': 'Fulfilled',
        'rejected': 'Rejected',
        'draft': 'Draft'
      }
      return statusMap[status] || status || 'Unknown'
    }

    const getStatusIcon = (status) => {
      const iconMap = {
        'pending': 'pi pi-clock',
        'approved': 'pi pi-check-circle',
        'transit': 'pi pi-send',
        'fulfilled': 'pi pi-verified',
        'rejected': 'pi pi-times-circle',
        'draft': 'pi pi-file-edit'
      }
      return iconMap[status] || 'pi pi-question-circle'
    }

    const getItemStatusDisplay = (status) => {
      const statusMap = {
        'pending': 'Pending',
        'approved': 'Approved',
        'transit': 'In Transit',
        'fulfilled': 'Fulfilled',
        'rejected': 'Rejected'
      }
      return statusMap[status] || status || 'pending'
    }

    const approveSelectedItems = async () => {
      try {
        approvalLoading.value = true
        
        // Get items with selected inventory
        const itemsToApprove = movement.value.items.filter(item => 
          item.selected_inventory && item.selected_inventory.length > 0
        )

        if (itemsToApprove.length === 0) {
          console.warn('No items selected for approval')
          return
        }

        // Call API to approve and move to transit
        await axios.post(`/api/stock-movements/${props.movementId}/approve`, {
          item_ids: itemsToApprove.map(item => item.id)
        })

        // Refresh movement data
        await loadMovement()
        
        console.log('Items approved and moved to transit successfully')
      } catch (error) {
        console.error('Error approving items:', error)
      } finally {
        approvalLoading.value = false
      }
    }

    const rejectSelectedItems = async () => {
      try {
        rejectionLoading.value = true
        
        // Get items with selected inventory
        const itemsToReject = movement.value.items.filter(item => 
          item.selected_inventory && item.selected_inventory.length > 0
        )

        if (itemsToReject.length === 0) {
          console.warn('No items selected for rejection')
          return
        }

        // Call API to reject items
        await axios.post(`/api/stock-movements/${props.movementId}/reject`, {
          item_ids: itemsToReject.map(item => item.id)
        })

        // Refresh movement data
        await loadMovement()
        
        console.log('Items rejected successfully')
      } catch (error) {
        console.error('Error rejecting items:', error)
      } finally {
        rejectionLoading.value = false
      }
    }

    // Lifecycle
    onMounted(async () => {
      await loadMovement()
    })

    return {
      movement,
      loading,
      currentPage,
      pageSize,
      showProductSelectionDialog,
      selectedItemForSelection,
      showViewSelectionsDialog,
      selectedItemForView,
      approvalLoading,
      rejectionLoading,
      filteredProducts,
      totalPages,
      paginatedProducts,
      visiblePages,
      canShowApprovalSection,
      loadMovement,
      getProductUnit,
      formatDate,
      goToPage,
      openProductSelection,
      viewSelectedProducts,
      getTotalSelectedQuantity,
      editSelections,
      onSelectionSaved,
      getCalculatedQuantity,
      onPageChange,
      getSelectedItemsCount,
      getStatusDisplay,
      getStatusIcon,
      getItemStatusDisplay,
      approveSelectedItems,
      rejectSelectedItems
    }
  }
}
</script>
<style scoped>
.shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>
