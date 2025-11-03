<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <!-- Floating Action Buttons -->
    <div class="tw-fixed tw-bottom-6 tw-right-6 tw-z-50 tw-flex tw-flex-col tw-gap-3">
      <Button
        v-if="isEditable"
        @click="openSuggestionsDialog"
        class="tw-w-14 tw-h-14 tw-rounded-full tw-shadow-2xl hover:tw-shadow-3xl tw-transition-all tw-duration-300 tw-bg-gradient-to-r tw-from-purple-500 tw-to-purple-600 hover:tw-from-purple-600 hover:tw-to-purple-700"
        v-tooltip.top="'Smart Suggestions'"
      >
        <i class="pi pi-lightbulb tw-text-white tw-text-xl"></i>
      </Button>
      <Button
        v-if="isEditable"
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
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2">{{ pageTitle }}</h1>
            <p class="tw-text-blue-100 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-hashtag"></i>
              {{ demand?.demand_code || 'New Demand' }}
              <span v-if="mode !== 'create'" class="tw-ml-4 tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-uppercase tw-font-semibold"
                :class="{
                  'tw-bg-yellow-500/20 tw-text-yellow-200': demand?.status === 'draft',
                  'tw-bg-blue-500/20 tw-text-blue-200': demand?.status === 'sent',
                  'tw-bg-green-500/20 tw-text-green-200': demand?.status === 'approved',
                  'tw-bg-red-500/20 tw-text-red-200': demand?.status === 'rejected'
                }">
                {{ demand?.status }}
              </span>
              <span v-if="isServicePreSelected" class="tw-ml-4 tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-uppercase tw-font-semibold tw-bg-green-500/20 tw-text-green-200 tw-flex tw-items-center tw-gap-1">
                <i class="pi pi-cog"></i>
                {{ preSelectedServiceName }}
              </span>
            </p>
          </div>
          <div class="tw-flex tw-space-x-3">
            <Button
              @click="goBackToManagement"
              icon="pi pi-arrow-left"
              severity="secondary"
              outlined
              class="tw-bg-white tw-rounded-xl tw-text-blue-600 hover:tw-bg-blue-50 tw-px-4 tw-py-2 tw-text-nowrap"
              v-tooltip.top="'Back to Demands'"
            />

            <Button
              v-if="isViewMode && canEditDemand"
              @click="switchToEditMode"
              icon="pi pi-pencil"
              class="tw-bg-yellow-600 tw-rounded-xl hover:tw-bg-yellow-700 tw-px-6 tw-py-2 tw-text-nowrap"
            >
              Edit
            </Button>

            <Button
              v-if="isEditable && (demand?.items?.length > 0 || basicInfoCompleted)"
              @click="saveDraft"
              :loading="saving"
              icon="pi pi-save"
              severity="secondary"
              class="tw-bg-gray-600 tw-rounded-xl hover:tw-bg-gray-700 tw-px-6 tw-py-2 tw-text-nowrap"
            >
              Save Draft
            </Button>

            <Button
              v-if="isEditable && demand?.items?.length > 0"
              @click="sendDemand"
              :loading="sending"
              icon="pi pi-send"
              class="tw-bg-green-600 tw-rounded-xl hover:tw-bg-green-700 tw-px-6 tw-py-2 tw-text-nowrap"
            >
              Send Demand
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
            <i class="pi pi-shopping-cart tw-text-white tw-text-5xl"></i>
          </div>
          <div class="tw-absolute tw-inset-0 tw-w-32 tw-h-32 tw-border-4 tw-border-white/30 tw-border-t-white tw-rounded-full tw-mx-auto tw-animate-spin"></div>
        </div>
        <h3 class="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">Setting up your demand</h3>
        <p class="tw-text-gray-600">Please wait while we prepare everything...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="tw-max-w-9xl tw-mx-auto tw-px-6 tw-py-8">
      <!-- Basic Information Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-mb-8">
        <template #header>
          <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-text-white tw-p-6 tw-rounded-t-xl">
            <h3 class="tw-text-xl tw-font-semibold tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-info-circle"></i>
              Basic Information
            </h3>
          </div>
        </template>
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <!-- Service Selection -->
            <div class="tw-space-y-3">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700">
                Service <span class="tw-text-red-500">*</span>
              </label>
              <div v-if="isServicePreSelected" class="tw-relative">
                <div class="tw-w-full tw-px-4 tw-py-3 tw-border-2 tw-border-gray-300 tw-rounded-xl tw-bg-gray-100 tw-text-gray-600 tw-cursor-not-allowed tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-lock tw-text-gray-500"></i>
                  <span class="tw-font-medium">{{ preSelectedServiceName }}</span>
                  <span class="tw-ml-auto tw-text-xs tw-bg-gray-200 tw-px-2 tw-py-1 tw-rounded-full tw-uppercase tw-font-semibold">
                    Pre-selected
                  </span>
                </div>
                <div class="tw-absolute tw-inset-0 tw-bg-gray-200/20 tw-rounded-xl tw-pointer-events-none"></div>
              </div>
              <Dropdown
                v-else
                v-model="demand.service_id"
                :options="services"
                option-label="name"
                option-value="id"
                placeholder="Select a service..."
                class="tw-w-full tw-rounded-xl"
                :disabled="!isEditable"
                :loading="loadingServices"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3 tw-py-2">
                    <i class="pi pi-hospital tw-text-blue-500"></i>
                    <span>{{ slotProps.option.name }}</span>
                  </div>
                </template>
              </Dropdown>
            </div>

            <!-- Expected Date -->
            <div class="tw-space-y-3">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700">
                Expected Date <span class="tw-text-red-500">*</span>
              </label>
              <Calendar
                v-model="demand.expected_date"
                placeholder="Select expected date..."
                class="tw-w-full tw-rounded-xl"
                :disabled="!isEditable"
                :minDate="minDate"
                dateFormat="yy-mm-dd"
                showIcon
              />
            </div>

            <!-- Notes -->
            <div class="tw-space-y-3 md:tw-col-span-2">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700">
                Notes
              </label>
              <Textarea
                v-model="demand.notes"
                placeholder="Add any additional notes or requirements..."
                class="tw-w-full tw-rounded-xl"
                :disabled="!isEditable"
                rows="3"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Quick Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Total Items</p>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-900">{{ demand?.items?.length || 0 }}</p>
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
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Critical Stock</p>
              <p class="tw-text-3xl tw-font-bold tw-text-red-600">{{ suggestionCounts.critical_low }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-red-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-times-circle tw-text-red-600 tw-text-xl"></i>
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
      </div>

      <!-- Products Section -->
      <div class="tw-grid tw-grid-cols-1 tw-gap-6 tw-mb-8">
        <!-- Product Management -->
        <Card class="tw-shadow-xl tw-border-0">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-green-600 tw-text-white tw-p-6 tw-rounded-t-xl">
              <h3 class="tw-text-xl tw-font-semibold tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-box"></i>
                Products & Items  
                <span class="tw-text-sm tw-bg-white/20 tw-px-2 tw-py-1 tw-rounded-full">
                  {{ demand?.items?.length || 0 }} items
                </span>
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-space-y-4">
              <Button
                label="Add Products"
                icon="pi pi-plus"
                @click="openAddProductDialog"
                class="tw-w-full tw-bg-blue-600 hover:tw-bg-blue-700"
                :disabled="!demand?.id || demand?.status !== 'draft'"
              />
              <Button
                label="View Suggestions"
                icon="pi pi-lightbulb"
                @click="openSuggestionsDialog"
                class="tw-w-full tw-bg-purple-600 hover:tw-bg-purple-700"
                :disabled="!demand?.id"
              />
              <Button
                label="Clear All Items"
                icon="pi pi-trash"
                severity="danger"
                @click="clearAllItems"
                class="tw-w-full"
                :disabled="!demand?.items?.length || demand?.status !== 'draft'"
              />
            </div>
          </template>
        </Card>
      </div>

      <!-- Items List -->
      <Card class="tw-shadow-xl tw-border-0">
        <template #header>
          <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-green-600 tw-text-white tw-p-6 tw-rounded-t-xl">
            <div class="tw-flex tw-justify-between tw-items-center">
              <h3 class="tw-text-xl tw-font-semibold tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-list"></i>
                Demand Items ({{ demand?.items?.length || 0 }})
              </h3>
              <Button
                v-if="demand?.status === 'draft'"
                icon="pi pi-plus"
                class="tw-bg-white tw-text-green-600 hover:tw-bg-green-50"
                @click="openAddProductDialog"
                v-tooltip.left="'Add Item'"
              />
            </div>
          </div>
        </template>
        <template #content>
          <div v-if="!demand?.items?.length" class="tw-text-center tw-py-12">
            <div class="tw-w-24 tw-h-24 tw-bg-gray-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
              <i class="pi pi-box tw-text-gray-400 tw-text-4xl"></i>
            </div>
            <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No items added yet</h3>
            <p class="tw-text-gray-500 tw-mb-6">Start by adding products to your demand</p>
            <Button
              label="Add First Item"
              icon="pi pi-plus"
              @click="openAddProductDialog"
              class="tw-bg-blue-600 hover:tw-bg-blue-700"
              :disabled="!demand?.id || demand?.status !== 'draft'"
            />
          </div>

          <DataTable
            v-else
            :value="demand.items"
            responsiveLayout="scroll"
            class="tw-w-full"
          >
            <Column field="product.name" header="Product" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-box tw-text-blue-600"></i>
                  </div>
                  <div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <div class="tw-font-medium tw-text-gray-900">{{ getProductName(slotProps.data) }}</div>
                      <span v-if="slotProps.data.product_source" class="tw-text-xs tw-px-2 tw-py-1 tw-rounded-full tw-font-semibold"
                        :class="{
                          'tw-bg-blue-100 tw-text-blue-700': slotProps.data.product_source === 'stock',
                          'tw-bg-green-100 tw-text-green-700': slotProps.data.product_source === 'pharmacy'
                        }">
                        {{ slotProps.data.product_source === 'stock' ? 'Stock' : 'Pharmacy' }}
                      </span>
                    </div>
                    <div class="tw-text-sm tw-text-gray-500">{{ getProductCode(slotProps.data) || 'N/A' }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity" sortable>
              <template #body="slotProps">
                <div class="tw-font-medium tw-text-gray-900">
                  {{ slotProps.data.quantity || 0 }}
                  <span class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.unit_of_measure || slotProps.data.product?.unit || 'units' }}</span>
                </div>
              </template>
            </Column>

            <Column field="notes" header="Notes">
              <template #body="slotProps">
                <div class="tw-text-sm tw-text-gray-600 tw-max-w-48 tw-truncate">
                  {{ slotProps.data.notes || 'No notes' }}
                </div>
              </template>
            </Column>

            <Column header="Actions" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Button
                    v-if="demand?.status === 'draft'"
                    icon="pi pi-pencil"
                    class="p-button-rounded p-button-text p-button-sm tw-text-blue-600"
                    v-tooltip.top="'Edit Item'"
                    @click="editItem(slotProps.data)"
                  />
                  <Button
                    v-if="demand?.status === 'draft'"
                    icon="pi pi-trash"
                    class="p-button-rounded p-button-text p-button-sm tw-text-red-600"
                    v-tooltip.top="'Remove Item'"
                    @click="removeItem(slotProps.data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Add Product Dialog -->
    <Dialog
      :visible="showAddProductDialog"
      @update:visible="showAddProductDialog = $event"
      modal
      :header="'Add Product to Demand'"
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
              <p class="tw-text-sm tw-text-blue-700">Choose a product for your service demand</p>
            </div>
          </div>
        </div>

        <!-- Smart Suggestions Alert Section -->
        <div v-if="suggestionCounts.total > 0" class="tw-space-y-4">
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-4 tw-border tw-border-blue-200 tw-shadow-lg">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
              <div class="tw-w-12 tw-h-12 tw-bg-blue-600 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-animate-pulse">
                <i class="pi pi-bell tw-text-white tw-text-lg"></i>
              </div>
              <div>
                <h4 class="tw-font-semibold tw-text-blue-800 tw-text-lg">🚨 Suggestion Alerts</h4>
                <p class="tw-text-sm tw-text-blue-700">Consider these critical items for your demand</p>
              </div>
              <div class="tw-ml-auto">
                <span class="tw-text-xs tw-bg-blue-100 tw-text-blue-700 tw-px-3 tw-py-1 tw-rounded-full tw-font-medium">{{ suggestionCounts.total }} items</span>
              </div>
            </div>

            <div class="tw-flex tw-items-center tw-gap-2">
              <Button
                size="small"
                class="tw-bg-purple-600 hover:tw-bg-purple-700 tw-text-white tw-rounded-lg tw-text-sm tw-px-4 tw-py-2"
                @click="openSuggestionsDialog"
              >
                <i class="pi pi-list tw-mr-2"></i>
                View All Suggestions
              </Button>
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
                  v-model="newItem.quantity"
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
              <div v-if="newItem.quantity_by_box && newItem.quantity && selectedProduct && selectedProduct.boite_de != null" class="tw-mt-2 tw-text-sm tw-text-blue-600 tw-font-semibold tw-bg-blue-50 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-blue-200">
                <i class="pi pi-calculator tw-mr-2"></i>
                Total: {{ newItem.quantity * selectedProduct.boite_de }} {{ getProductUnit(selectedProduct) }} ({{ newItem.quantity }} boxes × {{ selectedProduct.boite_de }} {{ getProductUnit(selectedProduct) }}/box)
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

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes</label>
            <Textarea
              v-model="newItem.notes"
              rows="3"
              placeholder="Additional notes for this item..."
              class="tw-w-full"
            />
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="tw-flex tw-justify-end tw-space-x-3 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            @click="closeAddProductDialog"
            severity="secondary"
            outlined
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold"
          >
            Cancel
          </Button>
          <Button
            @click="addProductToDemand"
            :loading="addingItem"
            icon="pi pi-plus"
            class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 hover:tw-from-blue-600 hover:tw-to-blue-700 tw-text-white tw-rounded-xl tw-px-20 tw-py-3 tw-font-semibold tw-shadow-lg tw-transition-all tw-duration-300 tw-text-nowrap"
          >
            Add Product
          </Button>
        </div>
      </div>
    </Dialog>

    <!-- Suggestions Dialog -->
    <Dialog :visible="showSuggestionsDialog" @update:visible="showSuggestionsDialog = $event" modal header="Smart Suggestions" :style="{width: '80rem'}">
      <div v-if="loadingSuggestions" class="tw-text-center tw-py-8">
        <div class="tw-w-16 tw-h-16 tw-bg-gradient-to-r tw-from-purple-500 tw-to-purple-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
          <i class="pi pi-spin pi-spinner tw-text-white tw-text-2xl"></i>
        </div>
        <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">Loading Suggestions</h3>
        <p class="tw-text-gray-600">Analyzing stock levels and finding recommendations...</p>
      </div>
      
      <div v-else-if="suggestionCounts.total === 0" class="tw-text-center tw-py-12">
        <div class="tw-w-24 tw-h-24 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
          <i class="pi pi-check tw-text-green-600 tw-text-4xl"></i>
        </div>
        <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">All Stock Levels Look Good!</h3>
        <p class="tw-text-gray-500 tw-mb-6">No critical stock issues found at this time.</p>
        <Button
          label="Refresh Suggestions"
          icon="pi pi-refresh"
          @click="loadSuggestions"
          class="tw-bg-purple-600 hover:tw-bg-purple-700"
        />
      </div>
      
      <TabView v-else>
        <TabPanel header="Critical Low Stock" :disabled="!suggestions.critical_low?.length">
          <template #header>
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-exclamation-triangle tw-text-red-600"></i>
              <span>Critical Low ({{ suggestionCounts.critical_low }})</span>
            </div>
          </template>
          <DataTable :value="suggestions.critical_low" responsiveLayout="scroll" class="tw-mt-4">
            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-bg-red-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-box tw-text-red-600"></i>
                  </div>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.data.product?.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.forme || 'N/A' }}</div>
                  </div>
                </div>
              </template>
            </Column>
            <Column field="current_stock" header="Current Stock">
              <template #body="slotProps">
                <Tag severity="danger" :value="slotProps.data.current_stock || 0" />
              </template>
            </Column>
            <Column field="suggested_quantity" header="Suggested Quantity">
              <template #body="slotProps">
                <Tag severity="success" :value="slotProps.data.suggested_quantity || 0" />
              </template>
            </Column>
            <Column header="Actions">
              <template #body="slotProps">
                <Button
                  icon="pi pi-plus"
                  class="p-button-sm"
                  @click="addSuggestedItem(slotProps.data)"
                  v-tooltip.top="'Add to Demand'"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel header="Low Stock" :disabled="!suggestions.low_stock?.length">
          <template #header>
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-exclamation-triangle tw-text-orange-600"></i>
              <span>Low Stock ({{ suggestionCounts.low_stock }})</span>
            </div>
          </template>
          <DataTable :value="suggestions.low_stock" responsiveLayout="scroll" class="tw-mt-4">
            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-bg-orange-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-box tw-text-orange-600"></i>
                  </div>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.data.product?.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.forme || 'N/A' }}</div>
                  </div>
                </div>
              </template>
            </Column>
            <Column field="current_stock" header="Current Stock">
              <template #body="slotProps">
                <Tag severity="warning" :value="slotProps.data.current_stock || 0" />
              </template>
            </Column>
            <Column field="suggested_quantity" header="Suggested Quantity">
              <template #body="slotProps">
                <Tag severity="success" :value="slotProps.data.suggested_quantity || 0" />
              </template>
            </Column>
            <Column header="Actions">
              <template #body="slotProps">
                <Button
                  icon="pi pi-plus"
                  class="p-button-sm"
                  @click="addSuggestedItem(slotProps.data)"
                  v-tooltip.top="'Add to Demand'"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel header="Expiring Soon" :disabled="!suggestions.expiring_soon?.length">
          <template #header>
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-clock tw-text-yellow-600"></i>
              <span>Expiring Soon ({{ suggestionCounts.expiring_soon }})</span>
            </div>
          </template>
          <DataTable :value="suggestions.expiring_soon" responsiveLayout="scroll" class="tw-mt-4">
            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-bg-yellow-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-box tw-text-yellow-600"></i>
                  </div>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.data.product?.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.forme || 'N/A' }}</div>
                  </div>
                </div>
              </template>
            </Column>
            <Column field="expiry_date" header="Expiry Date">
              <template #body="slotProps">
                <Tag severity="warning" :value="formatDate(slotProps.data.expiry_date)" />
              </template>
            </Column>
            <Column field="suggested_quantity" header="Suggested Quantity">
              <template #body="slotProps">
                <Tag severity="success" :value="slotProps.data.suggested_quantity || 0" />
              </template>
            </Column>
            <Column header="Actions">
              <template #body="slotProps">
                <Button
                  icon="pi pi-plus"
                  class="p-button-sm"
                  @click="addSuggestedItem(slotProps.data)"
                  v-tooltip.top="'Add to Demand'"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>
      </TabView>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Close"
            icon="pi pi-times"
            class="p-button-text"
            @click="closeSuggestionsDialog"
          />
          <Button
            label="Add All Critical"
            icon="pi pi-plus"
            severity="danger"
            @click="addAllCritical"
            :disabled="!suggestions.critical_low?.length"
          />
        </div>
      </template>
    </Dialog>

    <!-- Toast -->
    <Toast position="top-right" />
    <ConfirmDialog />
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
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

export default {
  name: 'ServiceDemandCreate',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    InputNumber,
    InputSwitch,
    Dropdown,
    Calendar,
    Textarea,
    Tag,
    Card,
    TabView,
    TabPanel,
    Toast,
    ConfirmDialog
  },
  props: {
    demandId: {
      type: [String, Number],
      default: null
    },
    mode: {
      type: String,
      default: 'create', // 'create', 'edit', 'view'
      validator: (value) => ['create', 'edit', 'view'].includes(value)
    }
  },
  data() {
    return {
      demand: {
        service_id: null,
        expected_date: null,
        notes: '',
        status: 'draft',
        items: []
      },
      services: [],
      availableProducts: [],
      selectedProduct: null,
      suggestions: {
        critical_low: [],
        low_stock: [],
        expiring_soon: [],
        expired: [],
        controlled_substances: []
      },
      suggestionCounts: {
        critical_low: 0,
        low_stock: 0,
        expiring_soon: 0,
        expired: 0,
        controlled_substances: 0,
        total: 0
      },
      currentUser: null,
      loading: false,
      loadingServices: false,
      loadingProducts: false,
      saving: false,
      sending: false,
      loadingSuggestions: false,

      showAddProductDialog: false,
      showSuggestionsDialog: false,

      newItem: {
        product_id: null,
        quantity: 1,
        quantity_by_box: false,
        notes: ''
      },

      minDate: new Date()
    };
  },
  
  computed: {
    isCreateMode() {
      return this.mode === 'create';
    },
    
    isEditMode() {
      return this.mode === 'edit';
    },
    
    isViewMode() {
      return this.mode === 'view';
    },
    
    isEditable() {
      return (this.isCreateMode || this.isEditMode) && this.demand?.status === 'draft';
    },
    
    pageTitle() {
      if (this.isViewMode) return 'View Service Demand';
      if (this.isEditMode) return 'Edit Service Demand';
      return 'Create Service Demand';
    },

    quantityModeText() {
      if (!this.newItem.quantity_by_box) {
        return 'Individual units mode';
      }
      
      const product = this.selectedProduct;
      if (!product) {
        return 'Box mode (product not selected)';
      }
      
      if (product.boite_de !== null && product.boite_de !== undefined) {
        return `1 box = ${product.boite_de} ${this.getProductUnit(product)}`;
      }
      
      return '1 box = units (not specified)';
    },

    isServicePreSelected() {
      return !!this.$route.query.service_id;
    },

    preSelectedServiceName() {
      if (!this.isServicePreSelected || !this.services.length) return '';
      const service = this.services.find(s => s.id == this.demand.service_id);
      return service ? service.name : '';
    },

    basicInfoCompleted() {
      return this.demand?.service_id && this.demand?.expected_date;
    }
  },
  async mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    
    // Check for service_id in query parameters
    if (this.$route.query.service_id) {
      this.demand.service_id = parseInt(this.$route.query.service_id);
    }
    
    this.loading = true;
    try {
      await this.loadInitialData();
      if (this.demandId) {
        await this.loadDemand();
      }
      // Load suggestions after everything else is ready
      setTimeout(async () => {
        await this.loadSuggestions();
      }, 1000);
    } finally {
      this.loading = false;
    }
  },
  watch: {
    'newItem.product_id': {
      handler(newId) {
        if (!newId) {
          this.selectedProduct = null;
          return;
        }
        
        const productIdStr = String(newId);
        const product = this.availableProducts.find(p => String(p.id) === productIdStr);
        this.selectedProduct = product || null;
      },
      immediate: true
    }
  },
  methods: {
    async loadInitialData() {
      await Promise.all([
        this.loadCurrentUser(),
        this.loadServices(),
        this.loadProducts()
      ]);
    },

    async loadCurrentUser() {
      try {
        const response = await axios.get('/api/loginuser');
        this.currentUser = response.data.data;
      } catch (error) {
        console.error('Failed to load current user:', error);
      }
    },

    async loadServices() {
      this.loadingServices = true;
      try {
        const response = await axios.get('/api/services');
        const allServices = response.data.data || [];
        
        // Filter services based on user's specialization
        if (this.currentUser && this.currentUser.specialization_id) {
          const specializationResponse = await axios.get(`/api/specializations`);
          const specializations = specializationResponse.data.data || [];
          
          const userSpecialization = specializations.find(spec => spec.id === this.currentUser.specialization_id);
          
          if (userSpecialization && userSpecialization.service_id) {
            this.services = allServices.filter(service => service.id === userSpecialization.service_id);
            
            // Auto-select the service if there's only one
            if (this.services.length === 1) {
              this.demand.service_id = this.services[0].id;
            }
          } else {
            this.services = allServices;
          }
        } else {
          this.services = allServices;
        }
      } catch (error) {
        console.error('Failed to load services:', error);
        this.services = [];
      } finally {
        this.loadingServices = false;
      }
    },

    async loadProducts() {
      this.loadingProducts = true;
      try {
        // OPTIMIZED: Load with pagination limit (backend caps at 100)
        const response = await axios.get('/api/pharmacy/products', { 
          params: { per_page: 10 } 
        });
        this.availableProducts = response.data.data || [];
      } catch (error) {
        console.error('Failed to load products:', error);
      } finally {
        this.loadingProducts = false;
      }
    },

    async loadDemand() {
      try {
        if (!this.demandId || this.demandId === 'null' || this.demandId === 'undefined') {
          console.warn('Invalid demandId, initializing empty demand instead');
          await this.initializeEmptyDemand();
          return;
        }
        
        const response = await axios.get(`/api/service-demands/${this.demandId}`);
        console.log('Loaded demand:', this.demandId);
        this.demand = response.data.data;
      } catch (error) {
        console.error('Error loading demand:', error);
        
        if (error.response?.status === 404) {
          // If demand not found, initialize empty demand
          console.warn('Demand not found, initializing empty demand');
          await this.initializeEmptyDemand();
        } else {
          this.toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load demand details',
            life: 3000
          });
        }
      }
    },

    async initializeEmptyDemand() {
      // Initialize with empty demand structure
      this.demand = {
        service_id: null,
        expected_date: null,
        notes: '',
        status: 'draft',
        items: []
      };
    },

    async loadSuggestions() {
      this.loadingSuggestions = true;
      try {
        const response = await axios.get('/api/service-demands/suggestions');
        this.suggestions = response.data.data;
        this.suggestionCounts = response.data.data.counts || {};
        this.suggestionCounts.total = Object.values(this.suggestionCounts).reduce((a, b) => a + b, 0);
        
        console.log('Suggestions loaded:', this.suggestions);
        console.log('Suggestion counts:', this.suggestionCounts);
      } catch (error) {
        console.error('Failed to load suggestions:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load suggestions',
          life: 3000
        });
        // Set default empty values on error
        this.suggestions = {
          critical_low: [],
          low_stock: [],
          expiring_soon: [],
          expired: [],
          controlled_substances: []
        };
        this.suggestionCounts = {
          critical_low: 0,
          low_stock: 0,
          expiring_soon: 0,
          expired: 0,
          controlled_substances: 0,
          total: 0
        };
      } finally {
        this.loadingSuggestions = false;
      }
    },

    async saveDraft() {
      if (!this.demand.service_id) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please select a service first',
          life: 3000
        });
        return;
      }

      if (!this.demand.expected_date) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please select an expected date',
          life: 3000
        });
        return;
      }

      this.saving = true;
      try {
        if (this.demand.id) {
          // Update existing demand
          await axios.put(`/api/service-demands/${this.demand.id}`, {
            service_id: this.demand.service_id,
            expected_date: this.demand.expected_date,
            notes: this.demand.notes
          });
        } else {
          // Create new demand
          const response = await axios.post('/api/service-demands', {
            service_id: this.demand.service_id,
            expected_date: this.demand.expected_date,
            notes: this.demand.notes
          });
          
          // Update the demand with the returned data
          this.demand = response.data.data;
          
          // Update the route to edit mode with the new ID
          this.$router.replace({
            path: `/stock/service-demands/edit/${this.demand.id}`,
            query: this.$route.query
          });
        }
        
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Demand saved successfully',
          life: 3000
        });
      } catch (error) {
        console.error('Error saving demand:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to save demand',
          life: 3000
        });
      } finally {
        this.saving = false;
      }
    },

    async sendDemand() {
      if (!this.demand.items?.length) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please add at least one item before sending the demand',
          life: 3000
        });
        return;
      }

      this.confirm.require({
        message: `Are you sure you want to send demand "${this.demand.demand_code}"? Once sent, you cannot edit it anymore.`,
        header: 'Send Demand',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          this.sending = true;
          try {
            await axios.post(`/api/service-demands/${this.demand.id}/send`);
            this.demand.status = 'sent';
            
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Demand sent successfully',
              life: 3000
            });

            // Redirect to management page
            setTimeout(() => {
              this.$router.push('/stock/service-demands');
            }, 1000);
          } catch (error) {
            console.error('Error sending demand:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: error.response?.data?.message || 'Failed to send demand',
              life: 3000
            });
          } finally {
            this.sending = false;
          }
        }
      });
    },

    async addProductToDemand() {
      this.addingItem = true;
      try {
        const response = await axios.post(`/api/service-demands/${this.demand.id}/items`, this.newItem);
        this.demand.items.push(response.data.data);
        
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Item added successfully',
          life: 3000
        });
        
        this.closeAddProductDialog();
      } catch (error) {
        console.error('Error adding item:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to add item',
          life: 3000
        });
      } finally {
        this.addingItem = false;
      }
    },

    async removeItem(item) {
      this.confirm.require({
        message: `Are you sure you want to remove "${item.product?.name}" from this demand?`,
        header: 'Remove Item',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/service-demands/${this.demand.id}/items/${item.id}`);
            this.demand.items = this.demand.items.filter(i => i.id !== item.id);
            
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Item removed successfully',
              life: 3000
            });
          } catch (error) {
            console.error('Error removing item:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to remove item',
              life: 3000
            });
          }
        }
      });
    },

    editItem(item) {
      // Set the item data to edit form
      this.newItem = {
        id: item.id,
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        notes: item.notes
      };
      this.showAddProductDialog = true;
    },

    async clearAllItems() {
      this.confirm.require({
        message: 'Are you sure you want to remove all items from this demand?',
        header: 'Clear All Items',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            for (const item of this.demand.items) {
              await axios.delete(`/api/service-demands/${this.demand.id}/items/${item.id}`);
            }
            this.demand.items = [];
            
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'All items cleared successfully',
              life: 3000
            });
          } catch (error) {
            console.error('Error clearing items:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to clear all items',
              life: 3000
            });
          }
        }
      });
    },

    addSuggestedItem(suggestion) {
      // Add suggested item to the demand
      this.newItem = {
        product_id: suggestion.product_id,
        quantity: suggestion.suggested_quantity,
        unit_price: suggestion.suggested_price || null,
        notes: `Suggested due to ${suggestion.reason || 'stock level'}`
      };
      this.addProductToDemand();
    },

    async addAllCritical() {
      if (!this.suggestions.critical_low?.length) return;
      
      try {
        for (const suggestion of this.suggestions.critical_low) {
          await axios.post(`/api/service-demands/${this.demand.id}/items`, {
            product_id: suggestion.product_id,
            quantity: suggestion.suggested_quantity,
            unit_price: suggestion.suggested_price || null,
            notes: `Critical stock - automatically added`
          });
        }
        
        // Reload demand to get updated items
        await this.loadDemand();
        
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Added ${this.suggestions.critical_low.length} critical items`,
          life: 3000
        });
        
        this.closeSuggestionsDialog();
      } catch (error) {
        console.error('Error adding critical items:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to add some critical items',
          life: 3000
        });
      }
    },

    openAddProductDialog() {
      if (!this.demand.id) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please save the demand first',
          life: 3000
        });
        return;
      }
      this.resetNewItem();
      this.showAddProductDialog = true;
    },

    closeAddProductDialog() {
      this.showAddProductDialog = false;
      this.resetNewItem();
    },

    openSuggestionsDialog() {
      this.showSuggestionsDialog = true;
      // If suggestions haven't been loaded yet, load them first
      if (this.suggestionCounts.total === 0 && !this.loadingSuggestions) {
        this.loadSuggestions();
      }
    },

    closeSuggestionsDialog() {
      this.showSuggestionsDialog = false;
    },

    // Helper methods for box/unit functionality
    getProductUnit(product) {
      if (!product) return 'units';
      return product.unit || product.forme || 'units';
    },

    getProductName(item) {
      // Use product data from the resource (which includes both stock and pharmacy products)
      if (item.product && item.product.name) {
        return item.product.name;
      }
      // Fallback for older data structure
      if (item.product?.name) {
        return item.product.name;
      }
      if (item.pharmacyProduct?.name) {
        return item.pharmacyProduct.name;
      }
      return 'N/A';
    },

    getProductCode(item) {
      // Return the appropriate code based on product source
      if (item.product) {
        // Stock product
        return item.product.code_interne || item.product.sku || 'N/A';
      }
      if (item.pharmacyProduct) {
        // Pharmacy product
        return item.pharmacyProduct.sku || 'N/A';
      }
      return 'N/A';
    },

    getSelectedProductUnit() {
      if (!this.newItem.product_id) return 'units';
      const product = this.selectedProduct;
      if (!product) return 'units';

      if (this.newItem.quantity_by_box && product.boite_de != null) {
        return 'boxes';
      }
      return product.unit || product.forme || 'units';
    },

    getProductType(product) {
      // Priority: Check for type-related fields first
      if (product.type && product.type !== '') {
        return product.type;
      }
      if (product.product_type && product.product_type !== '') {
        return product.product_type;
      }
      if (product.type_name && product.type_name !== '') {
        return product.type_name;
      }

      // Then check category-related fields
      if (product.category_name && product.category_name !== '') {
        return product.category_name;
      }
      if (product.category && product.category !== '') {
        return product.category;
      }
      if (product.product_category && product.product_category !== '') {
        return product.product_category;
      }

      // Fallback to default
      return 'General';
    },

    getProductStock(productId) {
      const product = this.availableProducts.find(p => p.id === productId);
      if (product) {
        if (product.current_stock !== undefined && product.current_stock !== null) {
          return product.current_stock;
        }
        if (product.stock !== undefined && product.stock !== null) {
          return product.stock;
        }
        if (product.quantity !== undefined && product.quantity !== null) {
          return product.quantity;
        }
        if (product.available_quantity !== undefined && product.available_quantity !== null) {
          return product.available_quantity;
        }
        if (product.stock_quantity !== undefined && product.stock_quantity !== null) {
          return product.stock_quantity;
        }
      }
      return 0;
    },

    onQuantityModeChange() {
      // Reset quantity when switching modes
      this.newItem.quantity = 1;
    },

    resetNewItem() {
      this.newItem = {
        product_id: null,
        quantity: 1,
        quantity_by_box: false,
        notes: ''
      };
    },

    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString();
    },

    // Navigation helpers
    goBackToManagement() {
      // Check if service is pre-selected (came from stock)
      if (this.isServicePreSelected) {
        this.$router.push(`/stock/services/${this.demand.service_id}/orders`);
      } else {
        this.$router.push('/stock/service-demands');
      }
    },

    switchToEditMode() {
      if (this.mode !== 'edit' && this.demandId) {
        this.$router.push({
          path: `/stock/service-demands/edit/${this.demandId}`,
          query: { mode: 'edit' }
        });
      }
    },

    canEditDemand(demand) {
      return demand && demand.status === 'draft';
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
.p-textarea:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  border-color: rgb(59, 130, 246);
}

@media (max-width: 768px) {
  .tw-grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>