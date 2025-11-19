<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <!-- Toast Notifications -->
    <Toast position="top-right" />

    <!-- Header Section -->
    <div class="tw-mb-8">
      <Card class="tw-w-full tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl tw-border-0">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <Button
                icon="pi pi-arrow-left"
                severity="secondary"
                text
                class="tw-p-3 tw-rounded-full hover:tw-bg-white/20 tw-transition-all tw-duration-300"
                @click="goBack"
                v-tooltip.top="'Go Back'"
              />
              <div>
                <h1 class="tw-text-4xl tw-font-bold tw-mb-2 tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-box tw-text-5xl"></i>
                  {{ product?.name || 'Product Details' }}
                </h1>
                <p class="tw-text-blue-100 tw-text-lg">{{ product?.description || 'Loading product information...' }}</p>
              </div>
            </div>
            <div class="tw-flex tw-gap-3 tw-items-center">
              <Button
                label="Refresh"
                icon="pi pi-refresh"
                severity="secondary"
                class="tw-rounded-xl tw-px-6 tw-py-3 tw-bg-white/10 tw-border-white/20 tw-text-white hover:tw-bg-white/20 tw-transition-all tw-duration-300"
                @click="refreshData"
                :loading="loading"
                v-tooltip.top="'Refresh Data'"
              />
              <Button
                label="Export Report"
                icon="pi pi-download"
                class="tw-rounded-xl tw-px-6 tw-py-3 tw-bg-white tw-text-blue-600 tw-border-white tw-font-semibold hover:tw-bg-blue-50 tw-transition-all tw-duration-300"
                @click="exportReport"
                v-tooltip.top="'Export Report'"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-20">
      <ProgressSpinner />
    </div>

    <!-- Main Content -->
    <div v-else-if="product" class="tw-space-y-8">
      <!-- Product Overview Card -->
      <Card class="tw-shadow-lg tw-border-0">
        <template #content>
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-8">
            <!-- Product Information -->
            <div class="lg:tw-col-span-2">
              <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-6 tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-info-circle tw-text-blue-600"></i>
                Product Information
              </h2>
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                <div class="tw-space-y-4">
                  <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-600 tw-mb-1">Product Name</label>
                    <p class="tw-text-lg tw-font-medium tw-text-gray-800">{{ product.name }}</p>
                  </div>
                  <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-600 tw-mb-1">Category</label>
                    <span class="tw-inline-flex tw-items-center tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium"
                          :class="getCategoryClass(product.category)">
                      {{ product.category }}
                    </span>
                  </div>
                  <div v-if="product.description">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-600 tw-mb-1">Description</label>
                    <p class="tw-text-gray-700">{{ product.description }}</p>
                  </div>
                </div>
                <div class="tw-space-y-4">
                  <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-600 tw-mb-1">Unit</label>
                    <p class="tw-text-lg tw-font-medium tw-text-gray-800">{{ product.unit }}</p>
                  </div>
                  <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-600 tw-mb-1">Low Stock Threshold</label>
                    <p class="tw-text-lg tw-font-medium tw-text-gray-800">{{ product.low_stock_threshold }}</p>
                  </div>
                  <div v-if="product.is_clinical">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-600 tw-mb-1">Clinical Product</label>
                    <span class="tw-inline-flex tw-items-center tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium tw-bg-red-100 tw-text-red-800">
                      <i class="pi pi-heart tw-mr-2"></i>
                      Clinical
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Stock Summary -->
            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-6 tw-border tw-border-green-100">
              <h3 class="tw-text-xl tw-font-bold tw-text-green-700 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-chart-bar tw-text-green-600"></i>
                Stock Summary
              </h3>
              <div class="tw-space-y-4">
                <div class="tw-bg-white/70 tw-rounded-lg tw-p-4 tw-text-center">
                  <div class="tw-text-sm tw-text-green-600 tw-font-medium tw-mb-1">Total Quantity</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-gray-800">{{ stockSummary.total_quantity || 0 }}</div>
                  <div v-if="stockSummary.is_low_stock" class="tw-text-xs tw-text-red-600 tw-mt-1">
                    <i class="pi pi-exclamation-triangle tw-mr-1"></i>
                    Low Stock Alert
                  </div>
                </div>
                <div class="tw-bg-white/70 tw-rounded-lg tw-p-4 tw-text-center">
                  <div class="tw-text-sm tw-text-green-600 tw-font-medium tw-mb-1">Locations</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-gray-800">{{ stockSummary.locations_count || 0 }}</div>
                </div>
                <div class="tw-bg-white/70 tw-rounded-lg tw-p-4 tw-text-center">
                  <div class="tw-text-sm tw-text-green-600 tw-font-medium tw-mb-1">Available Batches</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-gray-800">{{ stockSummary.batches_count || 0 }}</div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Management Actions -->
      <Card class="tw-bg-white tw-shadow-xl tw-border tw-border-gray-100">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
            <div>
              <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-cog tw-text-blue-600"></i>
                Stock Management
              </h2>
              <p class="tw-text-gray-600 tw-mt-1">Manage inventory and stock operations</p>
            </div>
            <div class="tw-flex tw-gap-3">
              <Button
                label="Add Stock"
                icon="pi pi-plus"
                class="tw-bg-blue-600 tw-border-blue-600 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-bg-blue-700 hover:tw-shadow-xl tw-transition-all tw-duration-300"
                @click="showAddStockDialog = true"
              />
              <Button
                label="Transfer Stock"
                icon="pi pi-arrow-right-arrow-left"
                severity="info"
                outlined
                class="tw-rounded-xl tw-px-6 tw-py-3 tw-border-2 hover:tw-bg-blue-50 tw-transition-all tw-duration-300"
                @click="showTransferDialog = true"
              />
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-6">
            <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-green-600 tw-rounded-xl tw-p-4 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-green-100 tw-text-sm">Available Stock</p>
                  <p class="tw-text-2xl tw-font-bold">{{ formatNumber(stockSummary.total_quantity || 0) }}</p>
                </div>
                <i class="pi pi-check-circle tw-text-3xl tw-text-green-200"></i>
              </div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-rounded-xl tw-p-4 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-blue-100 tw-text-sm">Locations</p>
                  <p class="tw-text-2xl tw-font-bold">{{ stockSummary.locations_count || 0 }}</p>
                </div>
                <i class="pi pi-map-marker tw-text-3xl tw-text-blue-200"></i>
              </div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-orange-500 tw-to-orange-600 tw-rounded-xl tw-p-4 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-orange-100 tw-text-sm">Stock Status</p>
                  <p class="tw-text-2xl tw-font-bold">{{ getLowStockCount() === 1 ? 'Low' : 'Good' }}</p>
                </div>
                <i :class="getLowStockCount() === 1 ? 'pi pi-exclamation-triangle' : 'pi pi-check-circle'" class="tw-text-3xl tw-text-orange-200"></i>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Global Product Settings -->
      <Card class="tw-bg-white tw-shadow-xl tw-border tw-border-gray-100">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
            <div>
              <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-sliders-h tw-text-purple-600"></i>
                Product Settings
              </h2>
              <p class="tw-text-gray-600 tw-mt-1">Configure settings specific to this product</p>
            </div>
            <Button
              :label="showSettings ? 'Hide Settings' : 'Show Settings'"
              :icon="showSettings ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
              severity="secondary"
              outlined
              class="tw-rounded-xl tw-px-4 tw-py-2 tw-transition-all tw-duration-300"
              @click="showSettings = !showSettings"
            />
          </div>

          <!-- Settings Content -->
          <div v-if="showSettings" class="tw-space-y-6">
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
              <!-- Min Quantity for All Services -->
              <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-blue-100 tw-p-6 tw-rounded-xl tw-border tw-border-blue-200">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                  <div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-blue-800">Min Quantity Threshold</h3>
                    <p class="tw-text-blue-600 tw-text-sm">Global minimum for all services</p>
                  </div>
                  <div class="tw-bg-blue-500 tw-p-3 tw-rounded-full">
                    <i class="pi pi-exclamation-triangle tw-text-white"></i>
                  </div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-4">
                  <InputNumber
                    v-model="globalSettings.min_quantity_all_services.threshold"
                    :min="0"
                    class="tw-flex-1"
                    :disabled="!canEditSettings"
                  />
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <Checkbox
                      v-model="globalSettings.min_quantity_all_services.apply_to_services"
                      :disabled="!canEditSettings"
                      :binary="true"
                    />
                    <label class="tw-text-sm tw-text-blue-700">Apply to Services</label>
                  </div>
                </div>
                <p class="tw-text-xs tw-text-blue-500 tw-mt-2">Current: {{ globalSettings.min_quantity_all_services.threshold }} units</p>
              </div>

              <!-- Critical Stock Threshold -->
              <div class="tw-bg-gradient-to-br tw-from-red-50 tw-to-red-100 tw-p-6 tw-rounded-xl tw-border tw-border-red-200">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                  <div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-red-800">Critical Stock Threshold</h3>
                    <p class="tw-text-red-600 tw-text-sm">Alert when stock drops below this level</p>
                  </div>
                  <div class="tw-bg-red-500 tw-p-3 tw-rounded-full">
                    <i class="pi pi-times-circle tw-text-white"></i>
                  </div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-4">
                  <InputNumber
                    v-model="globalSettings.critical_stock_threshold.threshold"
                    :min="0"
                    class="tw-flex-1"
                    :disabled="!canEditSettings"
                  />
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <Checkbox
                      v-model="globalSettings.critical_stock_threshold.apply_to_services"
                      :disabled="!canEditSettings"
                      :binary="true"
                    />
                    <label class="tw-text-sm tw-text-red-700">Apply to Services</label>
                  </div>
                </div>
                <p class="tw-text-xs tw-text-red-500 tw-mt-2">Current: {{ globalSettings.critical_stock_threshold.threshold }} units</p>
              </div>

              <!-- Expiry Alert Days -->
              <div class="tw-bg-gradient-to-br tw-from-yellow-50 tw-to-yellow-100 tw-p-6 tw-rounded-xl tw-border tw-border-yellow-200">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                  <div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-yellow-800">Expiry Alert Days</h3>
                    <p class="tw-text-yellow-600 tw-text-sm">Days before expiry to trigger alerts</p>
                  </div>
                  <div class="tw-bg-yellow-500 tw-p-3 tw-rounded-full">
                    <i class="pi pi-clock tw-text-white"></i>
                  </div>
                </div>
                <InputNumber
                  v-model="globalSettings.expiry_alert_days.days"
                  :min="1"
                  class="tw-w-full"
                  :disabled="!canEditSettings"
                />
                <p class="tw-text-xs tw-text-yellow-500 tw-mt-2">Current: {{ globalSettings.expiry_alert_days.days }} days</p>
              </div>

              <!-- Auto Reorder Settings -->
              <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-green-100 tw-p-6 tw-rounded-xl tw-border tw-border-green-200">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                  <div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-green-800">Auto Reorder</h3>
                    <p class="tw-text-green-600 tw-text-sm">Automatic reorder settings</p>
                  </div>
                  <div class="tw-bg-green-500 tw-p-3 tw-rounded-full">
                    <i class="pi pi-refresh tw-text-white"></i>
                  </div>
                </div>
                <div class="tw-space-y-3">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <Checkbox
                      v-model="globalSettings.auto_reorder_settings.enabled"
                      :disabled="!canEditSettings"
                      :binary="true"
                    />
                    <label class="tw-text-sm tw-text-green-700">Enable Auto Reorder</label>
                  </div>
                  <div v-if="globalSettings.auto_reorder_settings.enabled" class="tw-grid tw-grid-cols-2 tw-gap-3">
                    <div>
                      <label class="tw-block tw-text-xs tw-text-green-600 tw-mb-1">Reorder Point</label>
                      <InputNumber
                        v-model="globalSettings.auto_reorder_settings.reorder_point"
                        :min="0"
                        size="small"
                        :disabled="!canEditSettings"
                      />
                    </div>
                    <div>
                      <label class="tw-block tw-text-xs tw-text-green-600 tw-mb-1">Reorder Qty</label>
                      <InputNumber
                        v-model="globalSettings.auto_reorder_settings.reorder_quantity"
                        :min="1"
                        size="small"
                        :disabled="!canEditSettings"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notification Settings -->
            <div class="tw-bg-gradient-to-br tw-from-indigo-50 tw-to-purple-50 tw-p-6 tw-rounded-xl tw-border tw-border-indigo-200">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <div>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-indigo-800">Notification Settings</h3>
                  <p class="tw-text-indigo-600 tw-text-sm">Configure alert preferences</p>
                </div>
                <div class="tw-bg-indigo-500 tw-p-3 tw-rounded-full">
                  <i class="pi pi-bell tw-text-white"></i>
                </div>
              </div>
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Checkbox
                    v-model="globalSettings.notification_settings.email_alerts"
                    :disabled="!canEditSettings"
                    :binary="true"
                  />
                  <label class="tw-text-sm tw-text-indigo-700">Email Alerts</label>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Checkbox
                    v-model="globalSettings.notification_settings.sms_alerts"
                    :disabled="!canEditSettings"
                    :binary="true"
                  />
                  <label class="tw-text-sm tw-text-indigo-700">SMS Alerts</label>
                </div>
                <div>
                  <Dropdown
                    v-model="globalSettings.notification_settings.alert_frequency"
                    :options="frequencyOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Frequency"
                    class="tw-w-full"
                    :disabled="!canEditSettings"
                  />
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div v-if="canEditSettings" class="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
              <Button
                label="Reset to Defaults"
                icon="pi pi-refresh"
                severity="secondary"
                outlined
                class="tw-rounded-xl tw-px-6 tw-py-2"
                @click="resetSettings"
              />
              <Button
                label="Save Settings"
                icon="pi pi-save"
                class="tw-bg-purple-600 tw-border-purple-600 tw-rounded-xl tw-px-6 tw-py-2 tw-font-semibold tw-shadow-lg hover:tw-bg-purple-700 tw-transition-all tw-duration-300"
                @click="saveGlobalSettings"
                :loading="savingSettings"
              />
            </div>

            <!-- Read-only notice -->
            <div v-else class="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-xl tw-p-4">
              <div class="tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-info-circle tw-text-yellow-600 tw-text-xl"></i>
                <div>
                  <p class="tw-text-yellow-800 tw-font-medium">Settings Management</p>
                  <p class="tw-text-yellow-600 tw-text-sm">You need appropriate permissions to modify global settings.</p>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Details Table -->
      <Card class="tw-shadow-lg tw-border-0">
        <template #content>
          <div class="tw-mb-6">
            <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
              <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-list tw-text-green-600"></i>
                Stock Details
              </h2>
            </div>

            <!-- Search and Filter Section -->
            <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4 tw-mb-6 tw-p-4 tw-bg-gray-50 tw-rounded-xl">
              <div class="tw-flex-1">
                <div class="tw-relative">
                  <InputText
                    v-model="locationSearch"
                    placeholder="Search locations, batch numbers..."
                    class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-text-sm tw-transition-all focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100"
                    @input="onSearchInput"
                  />
                  <i class="pi pi-search tw-absolute tw-left-4 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400 tw-text-lg"></i>
                </div>
              </div>
              <div class="tw-flex tw-gap-3">
                <Dropdown
                  v-model="locationFilter"
                  :options="locationFilterOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Filter by..."
                  class="tw-min-w-[200px]"
                />
                <Button
                  label="Clear Filters"
                  icon="pi pi-filter-slash"
                  severity="secondary"
                  outlined
                  @click="clearFilters"
                />
              </div>
            </div>

            <!-- Stock Details DataTable -->
            <DataTable
              :value="filteredStockDetails"
              :paginator="true"
              :rows="10"
              :rowsPerPageOptions="[10, 25, 50]"
              paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
              class="tw-rounded-xl tw-overflow-hidden tw-shadow-sm"
              :loading="loadingDetails"
              responsiveLayout="scroll"
            >
              <template #empty>
                <div class="tw-text-center tw-py-8">
                  <i class="pi pi-inbox tw-text-4xl tw-text-gray-400 tw-mb-4"></i>
                  <p class="tw-text-gray-500 tw-text-lg">No stock details found</p>
                </div>
              </template>

              <Column field="stockage.name" header="Location" sortable>
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-map-marker tw-text-blue-600"></i>
                    <span class="tw-font-medium">{{ slotProps.data.stockage?.name || 'N/A' }}</span>
                  </div>
                </template>
              </Column>

              <Column field="quantity" header="Quantity" sortable>
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-text-lg tw-font-bold"
                          :class="slotProps.data.quantity <= product.low_stock_threshold ? 'tw-text-red-600' : 'tw-text-green-600'">
                      {{ slotProps.data.quantity }}
                    </span>
                    <span class="tw-text-sm tw-text-gray-500">{{ product.unit }}</span>
                    <i v-if="slotProps.data.quantity <= product.low_stock_threshold"
                       class="pi pi-exclamation-triangle tw-text-red-500"
                       v-tooltip.top="'Low Stock'"></i>
                  </div>
                </template>
              </Column>

              <Column field="batch_number" header="Batch Number" sortable>
                <template #body="slotProps">
                  <span class="tw-font-mono tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">
                    {{ slotProps.data.batch_number || 'N/A' }}
                  </span>
                </template>
              </Column>

              <Column field="expiry_date" header="Expiry Date" sortable>
                <template #body="slotProps">
                  <div v-if="slotProps.data.expiry_date">
                    <span :class="getExpiryClass(slotProps.data.expiry_date)"
                          class="tw-px-2 tw-py-1 tw-rounded tw-text-sm tw-font-medium">
                      {{ formatDate(slotProps.data.expiry_date) }}
                    </span>
                  </div>
                  <span v-else class="tw-text-gray-400">N/A</span>
                </template>
              </Column>

              <Column field="unit_price" header="Unit Price" sortable>
                <template #body="slotProps">
                  <span class="tw-font-semibold tw-text-green-600">
                    {{ formatCurrency(slotProps.data.unit_price) }}
                  </span>
                </template>
              </Column>

              <Column field="total_value" header="Total Value" sortable>
                <template #body="slotProps">
                  <span class="tw-font-bold tw-text-blue-600">
                    {{ formatCurrency(slotProps.data.quantity * slotProps.data.unit_price) }}
                  </span>
                </template>
              </Column>

              <Column header="Actions" :exportable="false">
                <template #body="slotProps">
                  <div class="tw-flex tw-gap-2">
                    <Button
                      icon="pi pi-eye"
                      severity="info"
                      text
                      rounded
                      v-tooltip.top="'View Details'"
                      @click="viewStockDetail(slotProps.data)"
                    />
                    <Button
                      icon="pi pi-pencil"
                      severity="warning"
                      text
                      rounded
                      v-tooltip.top="'Edit Stock'"
                      @click="editStock(slotProps.data)"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </template>
      </Card>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="tw-text-center tw-py-20">
      <Card class="tw-max-w-md tw-mx-auto tw-shadow-lg">
        <template #content>
          <div class="tw-text-center">
            <i class="pi pi-exclamation-triangle tw-text-6xl tw-text-red-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-2">Error Loading Product</h3>
            <p class="tw-text-gray-600 tw-mb-6">{{ error }}</p>
            <Button
              label="Try Again"
              icon="pi pi-refresh"
              @click="fetchProductDetails"
              class="tw-rounded-xl"
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- Add Stock Dialog -->
    <Dialog
      :visible="showAddStockDialog"
      @update:visible="showAddStockDialog = $event"
      modal
      header="Add Stock"
      :style="{ width: '500px' }"
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="addStock" class="tw-space-y-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Location *</label>
          <Dropdown
            v-model="newStock.location_id"
            :options="locations"
            optionLabel="name"
            optionValue="id"
            placeholder="Select Location"
            class="tw-w-full"
            required
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Quantity *</label>
          <InputNumber
            v-model="newStock.quantity"
            class="tw-w-full"
            placeholder="Enter quantity"
            :min="1"
            required
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Batch Number</label>
          <InputText
            v-model="newStock.batch_number"
            class="tw-w-full"
            placeholder="Enter batch number"
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Expiry Date</label>
          <Calendar
            v-model="newStock.expiry_date"
            class="tw-w-full"
            dateFormat="dd/mm/yy"
            placeholder="Select expiry date"
          />
        </div>
        <div class="tw-flex tw-justify-end tw-space-x-3 tw-pt-4">
          <Button
            type="button"
            label="Cancel"
            severity="secondary"
            @click="showAddStockDialog = false"
          />
          <Button
            type="submit"
            label="Add Stock"
            class="tw-bg-blue-600 tw-border-blue-600"
            :loading="submitting"
          />
        </div>
      </form>
    </Dialog>

    <!-- Transfer Stock Dialog -->
    <Dialog
      :visible="showTransferDialog"
      @update:visible="showTransferDialog = $event"
      modal
      header="Transfer Stock"
      :style="{ width: '500px' }"
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="transferStockSubmit" class="tw-space-y-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">From Location *</label>
          <Dropdown
            v-model="transferData.from_location_id"
            :options="locations"
            optionLabel="name"
            optionValue="id"
            placeholder="Select source location"
            class="tw-w-full"
            required
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">To Location *</label>
          <Dropdown
            v-model="transferData.to_location_id"
            :options="availableToLocations"
            optionLabel="name"
            optionValue="id"
            placeholder="Select destination location"
            class="tw-w-full"
            required
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Quantity to Transfer *</label>
          <InputNumber
            v-model="transferData.quantity"
            class="tw-w-full"
            placeholder="Enter quantity to transfer"
            :min="1"
            required
          />
        </div>
        <div class="tw-flex tw-justify-end tw-space-x-3 tw-pt-4">
          <Button
            type="button"
            label="Cancel"
            severity="secondary"
            @click="showTransferDialog = false"
          />
          <Button
            type="submit"
            label="Transfer"
            class="tw-bg-green-600 tw-border-green-600"
            :loading="submitting"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script>
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import Calendar from 'primevue/calendar';

export default {
  name: 'PharmacyProductStockDetails',
  components: {
    Card,
    Button,
    InputText,
    InputNumber,
    Dropdown,
    DataTable,
    Column,
    ProgressSpinner,
    Toast,
    Checkbox,
    Dialog,
    Calendar
  },
  props: {
    productId: {
      type: [String, Number],
      required: true
    }
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const toast = useToast();

    return {
      route,
      router,
      toast
    };
  },
  data() {
    return {
      product: null,
      stockSummary: {},
      stockDetails: [],
      filteredStockDetails: [],
      loading: true,
      loadingDetails: false,
      error: null,
      
      // Search and filters
      locationSearch: '',
      locationFilter: '',
      locationFilterOptions: [
        { label: 'All Locations', value: '' },
        { label: 'Low Stock Only', value: 'low_stock' },
        { label: 'Expired Items', value: 'expired' },
        { label: 'Expiring Soon', value: 'expiring_soon' }
      ],
      
      searchTimeout: null,
      
      // Stock Management
      showAddStockDialog: false,
      showTransferDialog: false,
      locations: [],
      newStock: {
        location_id: null,
        quantity: null,
        batch_number: '',
        expiry_date: null
      },
      transferData: {
        from_location_id: null,
        to_location_id: null,
        quantity: null
      },
      submitting: false,
      
      // Global Settings
      showSettings: false,
      globalSettings: {
        min_quantity_all_services: {
          threshold: 10,
          apply_to_services: true
        },
        critical_stock_threshold: {
          threshold: 5,
          apply_to_services: true
        },
        expiry_alert_days: {
          days: 30
        },
        auto_reorder_settings: {
          enabled: false,
          reorder_point: 20,
          reorder_quantity: 50
        },
        notification_settings: {
          email_alerts: true,
          sms_alerts: false,
          alert_frequency: 'daily'
        }
      },
      canEditSettings: true,
      savingSettings: false,
      frequencyOptions: [
        { label: 'Real-time', value: 'realtime' },
        { label: 'Daily', value: 'daily' },
        { label: 'Weekly', value: 'weekly' },
        { label: 'Monthly', value: 'monthly' }
      ]
    };
  },
  async mounted() {
    await this.fetchProductDetails();
  },
  methods: {
    async fetchProductDetails() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/api/pharmacy/products/${this.productId}/stock-details`);
        
        if (response.data.success) {
          this.product = response.data.product;
          this.stockSummary = response.data.summary;
          this.stockDetails = response.data.stock_details || [];
          this.filteredStockDetails = [...this.stockDetails];
        } else {
          throw new Error(response.data.message || 'Failed to load product details');
        }
      } catch (error) {
        console.error('Error fetching product details:', error);
        this.error = error.response?.data?.message || error.message || 'Failed to load product details';
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: this.error,
          life: 5000
        });
      } finally {
        this.loading = false;
      }
    },
    
    async refreshData() {
      await this.fetchProductDetails();
      this.toast.add({
        severity: 'success',
        summary: 'Refreshed',
        detail: 'Product data has been refreshed',
        life: 3000
      });
    },
    
    goBack() {
      this.router.go(-1);
    },
    
    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.filterStockDetails();
      }, 300);
    },
    
    filterStockDetails() {
      let filtered = [...this.stockDetails];
      
      // Apply search filter
      if (this.locationSearch.trim()) {
        const search = this.locationSearch.toLowerCase();
        filtered = filtered.filter(item =>
          item.stockage?.name?.toLowerCase().includes(search) ||
          item.batch_number?.toLowerCase().includes(search)
        );
      }
      
      // Apply location filter
      if (this.locationFilter) {
        switch (this.locationFilter) {
          case 'low_stock':
            filtered = filtered.filter(item => item.quantity <= this.product.low_stock_threshold);
            break;
          case 'expired':
            filtered = filtered.filter(item => {
              if (!item.expiry_date) return false;
              return new Date(item.expiry_date) < new Date();
            });
            break;
          case 'expiring_soon':
            filtered = filtered.filter(item => {
              if (!item.expiry_date) return false;
              const expiryDate = new Date(item.expiry_date);
              const thirtyDaysFromNow = new Date();
              thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
              return expiryDate <= thirtyDaysFromNow && expiryDate >= new Date();
            });
            break;
        }
      }
      
      this.filteredStockDetails = filtered;
    },
    
    clearFilters() {
      this.locationSearch = '';
      this.locationFilter = '';
      this.filteredStockDetails = [...this.stockDetails];
    },
    
    getCategoryClass(category) {
      const classes = {
        'Medication': 'tw-bg-red-100 tw-text-red-800',
        'Supplies': 'tw-bg-green-100 tw-text-green-800',
        'Equipment': 'tw-bg-blue-100 tw-text-blue-800'
      };
      return classes[category] || 'tw-bg-gray-100 tw-text-gray-800';
    },
    
    getExpiryClass(expiryDate) {
      if (!expiryDate) return 'tw-bg-gray-100 tw-text-gray-800';
      
      const expiry = new Date(expiryDate);
      const now = new Date();
      const thirtyDaysFromNow = new Date();
      thirtyDaysFromNow.setDate(now.getDate() + 30);
      
      if (expiry < now) {
        return 'tw-bg-red-100 tw-text-red-800'; // Expired
      } else if (expiry <= thirtyDaysFromNow) {
        return 'tw-bg-yellow-100 tw-text-yellow-800'; // Expiring soon
      } else {
        return 'tw-bg-green-100 tw-text-green-800'; // Good
      }
    },
    
    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    
    formatCurrency(amount) {
      if (!amount) return '$0.00';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount);
    },
    
    viewStockDetail(stockItem) {
      // Navigate to detailed stock item view
      this.toast.add({
        severity: 'info',
        summary: 'Info',
        detail: 'Stock detail view functionality to be implemented',
        life: 3000
      });
    },
    
    editStock(stockItem) {
      // Open edit stock modal
      this.toast.add({
        severity: 'info',
        summary: 'Info',
        detail: 'Stock edit functionality to be implemented',
        life: 3000
      });
    },
    
    exportReport() {
      // Export functionality
      window.print();
      this.toast.add({
        severity: 'success',
        summary: 'Export',
        detail: 'Opening print dialog...',
        life: 3000
      });
    },
    
    formatNumber(num) {
      return new Intl.NumberFormat().format(num);
    },
    
    getLowStockCount() {
      const totalQuantity = this.stockSummary?.total_quantity || 0;
      const threshold = this.product?.low_stock_threshold || this.globalSettings.min_quantity_all_services.threshold;
      return totalQuantity < threshold ? 1 : 0;
    },
    
    async loadLocations() {
      try {
        const response = await axios.get('/api/pharmacy_stockages');
        if (response.data.success) {
          this.locations = response.data.data;
        }
      } catch (error) {
        console.error('Error loading locations:', error);
      }
    },
    
    async addStock() {
      this.submitting = true;
      try {
        const response = await axios.post('/api/pharmacy/inventory', {
          pharmacy_product_id: this.productId,
          pharmacy_stockage_id: this.newStock.location_id,
          quantity: this.newStock.quantity,
          batch_number: this.newStock.batch_number,
          expiry_date: this.newStock.expiry_date
        });

        if (response.data.success) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Stock added successfully',
            life: 3000
          });
          this.showAddStockDialog = false;
          this.resetNewStock();
          await this.fetchProductDetails();
        }
      } catch (error) {
        console.error('Error adding stock:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to add stock',
          life: 3000
        });
      } finally {
        this.submitting = false;
      }
    },
    
    resetNewStock() {
      this.newStock = {
        location_id: null,
        quantity: null,
        batch_number: '',
        expiry_date: null
      };
    },
    
    async transferStockSubmit() {
      this.submitting = true;
      try {
        // Transfer stock logic would go here
        this.toast.add({
          severity: 'info',
          summary: 'Transfer',
          detail: 'Stock transfer functionality will be implemented',
          life: 3000
        });
        this.showTransferDialog = false;
      } catch (error) {
        console.error('Error transferring stock:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to transfer stock',
          life: 3000
        });
      } finally {
        this.submitting = false;
      }
    },
    
    async saveGlobalSettings() {
      this.savingSettings = true;
      try {
        const response = await axios.post(`/api/pharmacy/products/${this.productId}/settings`, {
          settings: this.globalSettings
        });

        if (response.data.success) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: response.data.message || 'Product settings saved successfully',
            life: 3000
          });

          // Reload product data to reflect status changes based on new settings
          await this.fetchProductDetails();

          this.toast.add({
            severity: 'info',
            summary: 'Updated',
            detail: 'Product status has been recalculated based on new settings',
            life: 2000
          });
        }
      } catch (error) {
        console.error('Error saving product settings:', error);

        let errorMessage = 'Failed to save product settings';
        if (error.response && error.response.data) {
          if (error.response.data.message) {
            errorMessage = error.response.data.message;
          } else if (error.response.data.errors) {
            const firstError = Object.values(error.response.data.errors)[0];
            errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
          }
        }

        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: errorMessage,
          life: 5000
        });
      } finally {
        this.savingSettings = false;
      }
    },
    
    resetSettings() {
      this.globalSettings = {
        min_quantity_all_services: {
          threshold: 10,
          apply_to_services: true
        },
        critical_stock_threshold: {
          threshold: 5,
          apply_to_services: true
        },
        expiry_alert_days: {
          days: 30
        },
        auto_reorder_settings: {
          enabled: false,
          reorder_point: 20,
          reorder_quantity: 50
        },
        notification_settings: {
          email_alerts: true,
          sms_alerts: false,
          alert_frequency: 'daily'
        }
      };

      this.toast.add({
        severity: 'info',
        summary: 'Reset',
        detail: 'Settings reset to defaults',
        life: 3000
      });
    }
  },
  
  computed: {
    availableToLocations() {
      if (!this.transferData.from_location_id) {
        return this.locations;
      }
      return this.locations.filter(location => location.id !== this.transferData.from_location_id);
    }
  },
  
  async mounted() {
    await this.fetchProductDetails();
    await this.loadLocations();
  },
  
  watch: {
    locationFilter() {
      this.filterStockDetails();
    },
    'transferData.from_location_id'(newFromLocationId) {
      if (this.transferData.to_location_id === newFromLocationId) {
        this.transferData.to_location_id = null;
      }
    }
  }
};
</script>

<style scoped>
/* Custom styles if needed */
</style>