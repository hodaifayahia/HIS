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

    <!-- Main Content -->
    <div class="tw-space-y-8" v-if="!loading && product">

      <!-- Alerts Section -->
      <div v-if="totalAlertsCount > 0" class="tw-space-y-4">
        <Card class="tw-bg-gradient-to-r tw-from-red-50 tw-to-orange-50 tw-border tw-border-red-200 tw-shadow-xl">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
              <div class="tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-exclamation-triangle tw-text-red-600 tw-text-2xl"></i>
                <h2 class="tw-text-xl tw-font-bold tw-text-red-800">Active Alerts ({{ totalAlertsCount }})</h2>
              </div>
              <Button
                label="View All"
                icon="pi pi-eye"
                severity="danger"
                outlined
                class="tw-rounded-xl tw-px-4 tw-py-2"
                @click="showAllAlerts = !showAllAlerts"
              />
            </div>

            <div v-if="showAllAlerts" class="tw-space-y-4">
              <!-- Low Stock Alerts -->
              <div v-if="lowStockAlerts.length > 0" class="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-xl tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
                  <i class="pi pi-exclamation-triangle tw-text-yellow-600"></i>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-yellow-800">Low Stock Alert</h3>
                  <Tag :value="`${lowStockAlerts.length} alert(s)`" severity="warn" class="tw-ml-auto" />
                </div>
                <div v-for="alert in lowStockAlerts" :key="alert.type" class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-sm tw-mb-3">
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <div>
                      <div class="tw-font-medium tw-text-gray-800">Total Product Quantity</div>
                      <div class="tw-text-sm tw-text-yellow-600 tw-mt-1">{{ alert.message }}</div>
                    </div>
                    <div class="tw-text-right">
                      <div class="tw-text-2xl tw-font-bold tw-text-yellow-600">{{ formatNumber(alert.quantity) }}</div>
                      <div class="tw-text-sm tw-text-gray-500">Current Stock</div>
                    </div>
                  </div>
                  <div class="tw-mt-3 tw-flex tw-items-center tw-gap-2">
                    <div class="tw-text-sm tw-text-gray-600">Threshold:</div>
                    <Tag :value="`${formatNumber(alert.threshold)} units`" severity="warn" />
                  </div>
                </div>
              </div>

              <!-- Critical Stock Alerts -->
              <div v-if="criticalStockAlerts.length > 0" class="tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-xl tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
                  <i class="pi pi-times-circle tw-text-red-600"></i>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-red-800">Critical Stock Alert</h3>
                  <Tag :value="`${criticalStockAlerts.length} alert(s)`" severity="danger" class="tw-ml-auto" />
                </div>
                <div v-for="alert in criticalStockAlerts" :key="alert.type" class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-sm tw-mb-3">
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <div>
                      <div class="tw-font-medium tw-text-gray-800">Total Product Quantity</div>
                      <div class="tw-text-sm tw-text-red-600 tw-mt-1">{{ alert.message }}</div>
                    </div>
                    <div class="tw-text-right">
                      <div class="tw-text-2xl tw-font-bold tw-text-red-600">{{ formatNumber(alert.quantity) }}</div>
                      <div class="tw-text-sm tw-text-gray-500">Current Stock</div>
                    </div>
                  </div>
                  <div class="tw-mt-3 tw-flex tw-items-center tw-gap-2">
                    <div class="tw-text-sm tw-text-gray-600">Critical Threshold:</div>
                    <Tag :value="`${formatNumber(alert.threshold)} units`" severity="danger" />
                  </div>
                </div>
              </div>

              <!-- Expiring Alerts -->
              <div v-if="expiringAlerts.length > 0" class="tw-bg-orange-50 tw-border tw-border-orange-200 tw-rounded-xl tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
                  <i class="pi pi-clock tw-text-orange-600"></i>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-orange-800">Expiring Soon Alert</h3>
                  <Tag :value="`${expiringAlerts.length} item(s)`" severity="warning" class="tw-ml-auto" />
                </div>
                <p class="tw-text-orange-700 tw-mb-3">Items expiring within {{ globalSettings.expiry_alert_days.days }} days</p>
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-3">
                  <div v-for="alert in expiringAlerts.slice(0, 6)" :key="alert.location" class="tw-bg-white tw-p-3 tw-rounded-lg tw-shadow-sm">
                    <div class="tw-font-medium tw-text-gray-800">{{ alert.location }}</div>
                    <div class="tw-text-sm tw-text-orange-600">Expires: {{ formatDate(alert.expiry_date) }}</div>
                  </div>
                </div>
                <div v-if="expiringAlerts.length > 6" class="tw-text-center tw-mt-3">
                  <span class="tw-text-sm tw-text-orange-600">... and {{ expiringAlerts.length - 6 }} more items</span>
                </div>
              </div>

              <!-- Expired Alerts -->
              <div v-if="expiredAlerts.length > 0" class="tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-xl tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
                  <i class="pi pi-ban tw-text-red-600"></i>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-red-800">Expired Items Alert</h3>
                  <Tag :value="`${expiredAlerts.length} item(s)`" severity="danger" class="tw-ml-auto" />
                </div>
                <p class="tw-text-red-700 tw-mb-3">Items that have already expired</p>
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-3">
                  <div v-for="alert in expiredAlerts.slice(0, 6)" :key="alert.location" class="tw-bg-white tw-p-3 tw-rounded-lg tw-shadow-sm">
                    <div class="tw-font-medium tw-text-gray-800">{{ alert.location }}</div>
                    <div class="tw-text-sm tw-text-red-600">Expired: {{ formatDate(alert.expiry_date) }}</div>
                  </div>
                </div>
                <div v-if="expiredAlerts.length > 6" class="tw-text-center tw-mt-3">
                  <span class="tw-text-sm tw-text-red-600">... and {{ expiredAlerts.length - 6 }} more items</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Product Overview Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
        <!-- Total Quantity -->
        <Card class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-blue-100 tw-border tw-border-blue-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-font-semibold tw-text-blue-700 tw-mb-1">Total Quantity</p>
                <p class="tw-text-3xl tw-font-bold tw-text-blue-900">{{ formatNumber(productStats?.total_quantity || 0) }}</p>
                <p class="tw-text-xs tw-text-blue-600 tw-mt-1">Units in stock</p>
              </div>
              <div class="tw-p-4 tw-bg-blue-500 tw-rounded-full tw-shadow-lg">
                <i class="pi pi-box tw-text-white tw-text-2xl"></i>
              </div>
            </div>
            <div class="tw-mt-4">
              <div class="tw-flex tw-items-center tw-text-sm" v-if="productStats?.is_low_stock">
                <i class="pi pi-exclamation-triangle tw-text-red-500 tw-mr-2"></i>
                <span class="tw-text-red-600 tw-font-medium">Low Stock Alert</span>
              </div>
              <div class="tw-flex tw-items-center tw-text-sm" v-else>
                <i class="pi pi-check-circle tw-text-green-500 tw-mr-2"></i>
                <span class="tw-text-green-600 tw-font-medium">Stock Level Good</span>
              </div>
            </div>
          </template>
        </Card>

        <!-- Locations Count -->
        <Card class="tw-bg-gradient-to-br tw-from-green-50 tw-to-green-100 tw-border tw-border-green-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-font-semibold tw-text-green-700 tw-mb-1">Storage Locations</p>
                <p class="tw-text-3xl tw-font-bold tw-text-green-900">{{ productStats?.locations_count || 0 }}</p>
                <p class="tw-text-xs tw-text-green-600 tw-mt-1">Active storage areas</p>
              </div>
              <div class="tw-p-4 tw-bg-green-500 tw-rounded-full tw-shadow-lg">
                <i class="pi pi-map-marker tw-text-white tw-text-2xl"></i>
              </div>
            </div>
          </template>
        </Card>

        <!-- Low Stock Threshold -->
        <Card class="tw-bg-gradient-to-br tw-from-yellow-50 tw-to-yellow-100 tw-border tw-border-yellow-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-font-semibold tw-text-yellow-700 tw-mb-1">Low Stock Threshold</p>
                <p class="tw-text-3xl tw-font-bold tw-text-yellow-900">{{ productStats?.low_stock_threshold || globalSettings.min_quantity_all_services.threshold }}</p>
                <p class="tw-text-xs tw-text-yellow-600 tw-mt-1">Alert when total quantity below this level</p>
              </div>
              <div class="tw-p-4 tw-bg-yellow-500 tw-rounded-full tw-shadow-lg">
                <i class="pi pi-exclamation-circle tw-text-white tw-text-2xl"></i>
              </div>
            </div>
            <div class="tw-mt-4">
              <div class="tw-flex tw-items-center tw-text-sm">
                <i class="pi pi-info-circle tw-text-blue-500 tw-mr-2"></i>
                <span class="tw-text-blue-600 tw-font-medium">{{ lowStockAlerts.length === 1 ? 'Total quantity below threshold' : 'Total quantity above threshold' }}</span>
              </div>
            </div>
          </template>
        </Card>

        <!-- Stock Status -->
        <Card class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-purple-100 tw-border tw-border-purple-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-font-semibold tw-text-purple-700 tw-mb-1">Stock Status</p>
                <p class="tw-text-2xl tw-font-bold" :class="totalAlertsCount > 0 ? 'tw-text-red-600' : 'tw-text-green-600'">
                  {{ totalAlertsCount > 0 ? 'Needs Attention' : 'All Good' }}
                </p>
                <p class="tw-text-xs tw-text-purple-600 tw-mt-1">{{ totalAlertsCount }} active alert(s)</p>
              </div>
              <div class="tw-p-4 tw-rounded-full tw-shadow-lg" :class="totalAlertsCount > 0 ? 'tw-bg-red-500' : 'tw-bg-green-500'">
                <i :class="totalAlertsCount > 0 ? 'pi pi-exclamation-triangle tw-text-white' : 'pi pi-check-circle tw-text-white'" class="tw-text-2xl"></i>
              </div>
            </div>
            <div class="tw-mt-4">
              <div class="tw-flex tw-items-center tw-text-sm" v-if="totalAlertsCount > 0">
                <i class="pi pi-exclamation-triangle tw-text-red-500 tw-mr-2"></i>
                <span class="tw-text-red-600 tw-font-medium">Action required</span>
              </div>
              <div class="tw-flex tw-items-center tw-text-sm" v-else>
                <i class="pi pi-check-circle tw-text-green-500 tw-mr-2"></i>
                <span class="tw-text-green-600 tw-font-medium">No issues detected</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

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
                  <p class="tw-text-2xl tw-font-bold">{{ formatNumber(productStats?.total_quantity || 0) }}</p>
                </div>
                <i class="pi pi-check-circle tw-text-3xl tw-text-green-200"></i>
              </div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-rounded-xl tw-p-4 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-blue-100 tw-text-sm">Locations</p>
                  <p class="tw-text-2xl tw-font-bold">{{ productStats?.locations_count || 0 }}</p>
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
      <Card class="tw-bg-white tw-shadow-xl tw-border tw-border-gray-100">
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
                  class="tw-w-48 tw-rounded-xl tw-border-2 focus:tw-border-blue-500"
                />
                <Button
                  label="Clear Filters"
                  icon="pi pi-times"
                  severity="secondary"
                  outlined
                  class="tw-rounded-xl tw-px-4 tw-py-3 tw-border-2 hover:tw-bg-gray-50 tw-transition-all tw-duration-300"
                  @click="clearFilters"
                />
              </div>
            </div>
          </div>

          <!-- Stock Locations Table -->
          <div v-if="filteredLocations.length > 0">
            <DataTable
              :value="filteredLocations"
              :paginator="true"
              :rows="10"
              :loading="loading"
              class="tw-border tw-rounded-xl tw-overflow-hidden"
              stripedRows
              showGridlines
              responsiveLayout="scroll"
              paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
              :rowsPerPageOptions="[5, 10, 25, 50]"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords} locations"
            >
              <template #loading>
                <div class="tw-text-center tw-py-16">
                  <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
                    <i class="pi pi-spin pi-spinner tw-text-4xl tw-text-blue-500"></i>
                    <p class="tw-text-gray-500 tw-text-lg">Loading stock locations...</p>
                  </div>
                </div>
              </template>

              <Column field="location" header="Location" style="min-width: 180px" sortable>
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <div class="tw-w-10 tw-h-10 tw-rounded-full tw-bg-gradient-to-br tw-from-blue-400 tw-to-purple-500 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold">
                      {{ slotProps.data.location.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.location }}</div>
                      <div class="tw-text-sm tw-text-gray-500">Storage Area</div>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="quantity" header="Quantity" style="min-width: 120px" sortable>
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <div class="tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-semibold" :class="getQuantityBadgeClass(slotProps.data.quantity)">
                      {{ formatNumber(slotProps.data.quantity) }}
                    </div>
                    <span class="tw-text-gray-500 tw-text-sm">{{ slotProps.data.unit || 'units' }}</span>
                  </div>
                </template>
              </Column>

              <Column field="batch_number" header="Batch Number" style="min-width: 140px" sortable>
                <template #body="slotProps">
                  <span v-if="slotProps.data.batch_number" class="tw-bg-gray-100 tw-text-gray-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                    {{ slotProps.data.batch_number }}
                  </span>
                  <span v-else class="tw-text-gray-400 tw-italic">N/A</span>
                </template>
              </Column>

              <Column field="expiry_date" header="Expiry Date" style="min-width: 130px" sortable>
                <template #body="slotProps">
                  <div v-if="slotProps.data.expiry_date" class="tw-flex tw-items-center tw-gap-2">
                    <i :class="getExpiryIconClass(slotProps.data.expiry_date)" class="tw-text-lg"></i>
                    <span :class="getExpiryColor(slotProps.data.expiry_date)">
                      {{ formatDate(slotProps.data.expiry_date) }}
                    </span>
                  </div>
                  <span v-else class="tw-text-gray-400 tw-italic">No expiry</span>
                </template>
              </Column>

              <Column field="unit" header="Unit" style="min-width: 100px" sortable>
                <template #body="slotProps">
                  <Tag :value="slotProps.data.unit || 'Unit'" severity="info" class="tw-rounded-full tw-font-medium" />
                </template>
              </Column>

              <Column header="Actions" style="min-width: 180px">
                <template #body="slotProps">
                  <div class="tw-flex tw-gap-2">
                    <Button
                      icon="pi pi-eye"
                      severity="info"
                      outlined
                      class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-blue-50 tw-transition-all tw-duration-200"
                      @click="viewLocationDetails(slotProps.data)"
                      v-tooltip.top="'View Details'"
                    />
                    <Button
                      icon="pi pi-pencil"
                      severity="warning"
                      outlined
                      class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-yellow-50 tw-transition-all tw-duration-200"
                      @click="editStock(slotProps.data)"
                      v-tooltip.top="'Edit Stock'"
                    />
                    <Button
                      icon="pi pi-arrow-right-arrow-left"
                      severity="help"
                      outlined
                      class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-purple-50 tw-transition-all tw-duration-200"
                      @click="transferStock(slotProps.data)"
                      v-tooltip.top="'Transfer Stock'"
                    />
                    <Button
                      icon="pi pi-trash"
                      severity="danger"
                      outlined
                      class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-red-50 tw-transition-all tw-duration-200"
                      @click="deleteStock(slotProps.data)"
                      v-tooltip.top="'Delete Stock'"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>

          <!-- No Stock Message -->
          <div v-else class="tw-text-center tw-py-16 tw-bg-gradient-to-br tw-from-gray-50 tw-to-gray-100 tw-rounded-xl">
            <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
              <i class="pi pi-box-open tw-text-6xl tw-text-gray-300"></i>
              <h3 class="tw-text-xl tw-font-medium tw-text-gray-700">No Stock Found</h3>
              <p class="tw-text-gray-500 tw-mb-6">This product has no stock in any location.</p>
              <Button
                label="Add First Stock"
                icon="pi pi-plus"
                class="tw-bg-blue-600 tw-border-blue-600 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-bg-blue-700 tw-transition-all tw-duration-300"
                @click="showAddStockDialog = true"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Movement History -->
      <Card class="tw-bg-white tw-shadow-xl tw-border tw-border-gray-100">
        <template #content>
          <div class="tw-mb-6">
            <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-history tw-text-orange-600"></i>
              Stock Movement History
            </h2>
            <p class="tw-text-gray-600 tw-mt-1">Track all stock movements and changes</p>
          </div>

          <DataTable
            :value="stockMovements"
            :paginator="true"
            :rows="5"
            class="tw-border tw-rounded-xl tw-overflow-hidden"
            stripedRows
            showGridlines
            responsiveLayout="scroll"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} movements"
          >
            <Column field="date" header="Date" style="min-width: 140px" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-calendar tw-text-blue-500"></i>
                  <span class="tw-font-medium">{{ formatDate(slotProps.data.date) }}</span>
                </div>
              </template>
            </Column>
            <Column field="type" header="Type" style="min-width: 120px" sortable>
              <template #body="slotProps">
                <Tag :value="slotProps.data.type" :severity="getMovementSeverity(slotProps.data.type)" class="tw-font-medium tw-rounded-full tw-px-3 tw-py-1" />
              </template>
            </Column>
            <Column field="quantity" header="Quantity" style="min-width: 120px" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i :class="slotProps.data.type === 'IN' ? 'pi pi-plus-circle tw-text-green-500' : 'pi pi-minus-circle tw-text-red-500'"></i>
                  <span :class="slotProps.data.type === 'IN' ? 'tw-text-green-600 tw-font-semibold' : 'tw-text-red-600 tw-font-semibold'">
                    {{ slotProps.data.type === 'IN' ? '+' : '-' }}{{ formatNumber(slotProps.data.quantity) }}
                  </span>
                </div>
              </template>
            </Column>
            <Column field="location" header="Location" style="min-width: 140px" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-map-marker tw-text-gray-400"></i>
                  <span>{{ slotProps.data.location }}</span>
                </div>
              </template>
            </Column>
            <Column field="reason" header="Reason" style="min-width: 180px" sortable>
              <template #body="slotProps">
                <span class="tw-bg-gray-100 tw-text-gray-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm">
                  {{ slotProps.data.reason }}
                </span>
              </template>
            </Column>
            <Column field="user" header="User" style="min-width: 120px" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-user tw-text-blue-500"></i>
                  <span class="tw-font-medium">{{ slotProps.data.user }}</span>
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Analytics Charts Placeholder -->
      <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-8">
        <!-- Stock Level Chart -->
        <Card class="tw-bg-gradient-to-br tw-from-indigo-50 tw-to-purple-50 tw-shadow-xl tw-border tw-border-indigo-100">
          <template #content>
            <div class="tw-p-6">
              <h3 class="tw-text-xl tw-font-bold tw-text-indigo-700 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-chart-line tw-text-indigo-600"></i>
                Stock Level Trend
              </h3>
              <div class="tw-h-64 tw-flex tw-items-center tw-justify-center tw-bg-white tw-rounded-xl tw-shadow-inner">
                <div class="tw-text-center">
                  <i class="pi pi-chart-line tw-text-6xl tw-text-indigo-300 tw-mb-4"></i>
                  <p class="tw-text-indigo-600 tw-font-medium tw-text-lg">Stock Trend Chart</p>
                  <p class="tw-text-indigo-400 tw-text-sm">Chart integration available</p>
                  <Button
                    label="View Full Analytics"
                    icon="pi pi-external-link"
                    severity="info"
                    outlined
                    class="tw-mt-4 tw-rounded-xl"
                  />
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Location Distribution -->
        <Card class="tw-bg-gradient-to-br tw-from-green-50 tw-to-teal-50 tw-shadow-xl tw-border tw-border-green-100">
          <template #content>
            <div class="tw-p-6">
              <h3 class="tw-text-xl tw-font-bold tw-text-green-700 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-chart-pie tw-text-green-600"></i>
                Stock Distribution
              </h3>
              <div class="tw-space-y-4">
                <div v-for="location in productStats?.locations || []" :key="location.location" class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                  <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                    <span class="tw-font-medium tw-text-gray-800">{{ location.location }}</span>
                    <span class="tw-text-sm tw-text-gray-500">{{ formatNumber(location.quantity) }} units</span>
                  </div>
                  <div class="tw-w-full tw-bg-gray-200 tw-rounded-full tw-h-2">
                    <div
                      class="tw-bg-gradient-to-r tw-from-green-400 tw-to-green-600 tw-h-2 tw-rounded-full tw-transition-all tw-duration-500"
                      :style="{ width: getDistributionWidth(location.quantity) }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-20">
      <Card class="tw-bg-white tw-shadow-xl tw-border tw-border-gray-100">
        <template #content>
          <div class="tw-text-center tw-py-16">
            <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
              <i class="pi pi-spin pi-spinner tw-text-4xl tw-text-blue-500"></i>
              <p class="tw-text-gray-600 tw-text-lg tw-font-medium">Loading stock information...</p>
              <p class="tw-text-gray-500 tw-text-sm">Please wait while we fetch the data</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Error State -->
    <div v-else class="tw-text-center tw-py-20">
      <Card class="tw-bg-white tw-shadow-xl tw-border tw-border-gray-100">
        <template #content>
          <div class="tw-text-center tw-py-16">
            <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
              <i class="pi pi-exclamation-triangle tw-text-6xl tw-text-red-400"></i>
              <h3 class="tw-text-xl tw-font-medium tw-text-gray-700 tw-mb-2">Error Loading Data</h3>
              <p class="tw-text-gray-500 tw-mb-6">{{ error }}</p>
              <Button
                label="Try Again"
                icon="pi pi-refresh"
                class="tw-bg-blue-600 tw-border-blue-600 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-bg-blue-700 tw-transition-all tw-duration-300"
                @click="loadData"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Add Stock Dialog -->
    <Dialog
      v-model:visible="showAddStockDialog"
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
            min="1"
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
      v-model:visible="showTransferDialog"
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
            min="1"
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
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Toast from 'primevue/toast';
import Checkbox from 'primevue/checkbox';

export default {
  name: 'ProductStockDetails',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    InputNumber,
    Dropdown,
    Calendar,
    Tag,
    Card,
    Toast,
    Checkbox
  },
  data() {
    return {
      productId: this.$route.params.id,
      product: null,
      productStats: null,
      stockMovements: [],
      locations: [],
      loading: false,
      error: null,
      submitting: false,
      locationSearch: '',
      locationFilter: '',
      showAddStockDialog: false,
      showTransferDialog: false,
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
      locationFilterOptions: [
        { label: 'All Locations', value: '' },
        { label: 'With Expiry', value: 'with_expiry' },
        { label: 'Without Expiry', value: 'without_expiry' },
        { label: 'Low Stock', value: 'low_stock' }
      ],
      searchTimeout: null,
      // Global Settings
      showSettings: false,
      showAllAlerts: false,
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
      canEditSettings: true, // TODO: Implement proper permission check
      savingSettings: false,
      frequencyOptions: [
        { label: 'Real-time', value: 'realtime' },
        { label: 'Daily', value: 'daily' },
        { label: 'Weekly', value: 'weekly' },
        { label: 'Monthly', value: 'monthly' }
      ]
    };
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.loadData();
  },
  computed: {
    filteredLocations() {
      let locations = this.productStats?.locations || [];

      // Search filter
      if (this.locationSearch.trim()) {
        const search = this.locationSearch.toLowerCase();
        locations = locations.filter(loc =>
          loc.location.toLowerCase().includes(search) ||
          (loc.batch_number && loc.batch_number.toLowerCase().includes(search))
        );
      }

      // Additional filters
      if (this.locationFilter) {
        switch (this.locationFilter) {
          case 'with_expiry':
            locations = locations.filter(loc => loc.expiry_date);
            break;
          case 'without_expiry':
            locations = locations.filter(loc => !loc.expiry_date);
            break;
          case 'low_stock':
            const threshold = this.globalSettings.min_quantity_all_services.threshold;
            locations = locations.filter(loc => loc.quantity < threshold);
            break;
        }
      }

      return locations;
    },

    // Alerts based on global settings
    lowStockAlerts() {
      const totalQuantity = this.productStats?.total_quantity || 0;
      const threshold = this.globalSettings.min_quantity_all_services.threshold;
      
      if (totalQuantity < threshold) {
        return [{
          type: 'total_quantity',
          message: `Total quantity (${totalQuantity}) is below minimum threshold (${threshold})`,
          severity: 'warning',
          quantity: totalQuantity,
          threshold: threshold
        }];
      }
      return [];
    },

    criticalStockAlerts() {
      const totalQuantity = this.productStats?.total_quantity || 0;
      const threshold = this.globalSettings.critical_stock_threshold.threshold;
      
      if (totalQuantity <= threshold) {
        return [{
          type: 'total_quantity',
          message: `Total quantity (${totalQuantity}) is at or below critical threshold (${threshold})`,
          severity: 'danger',
          quantity: totalQuantity,
          threshold: threshold
        }];
      }
      return [];
    },

    expiringAlerts() {
      if (!this.productStats?.locations) return [];
      const alertDays = this.globalSettings.expiry_alert_days.days;
      const now = new Date();
      return this.productStats.locations.filter(loc => {
        if (!loc.expiry_date) return false;
        const expiry = new Date(loc.expiry_date);
        const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
        return diffDays >= 0 && diffDays <= alertDays;
      });
    },

    expiredAlerts() {
      if (!this.productStats?.locations) return [];
      const now = new Date();
      return this.productStats.locations.filter(loc => {
        if (!loc.expiry_date) return false;
        const expiry = new Date(loc.expiry_date);
        return expiry < now;
      });
    },

    totalAlertsCount() {
      return this.lowStockAlerts.length + this.criticalStockAlerts.length + this.expiringAlerts.length + this.expiredAlerts.length;
    },

    // Filter 'to' locations to exclude the selected 'from' location
    availableToLocations() {
      if (!this.transferData.from_location_id) {
        return this.locations;
      }
      return this.locations.filter(location => location.id !== this.transferData.from_location_id);
    }
  },
  watch: {
    // Clear 'to' location when 'from' location changes to prevent same location selection
    'transferData.from_location_id'(newFromLocationId, oldFromLocationId) {
      // If the currently selected 'to' location is the same as the new 'from' location, clear it
      if (this.transferData.to_location_id === newFromLocationId) {
        this.transferData.to_location_id = null;
      }
    }
  },
  methods: {
    async loadData() {
      this.loading = true;
      this.error = null;

      try {
        // Load product details
        const response = await axios.get(`/api/stock/products/${this.productId}/details`);
        if (response.data.success) {
          this.product = response.data.product;
          this.productStats = response.data.stats;
          
          // Use settings from backend if available, otherwise use defaults
          if (response.data.stats.global_settings) {
            this.globalSettings = { ...this.globalSettings, ...response.data.stats.global_settings };
          }
        }

        // Load locations for dropdowns
        await this.loadLocations();

        // Load stock movements (mock data for now)
        this.loadStockMovements();

        // Load global settings
        await this.loadGlobalSettings();

      } catch (error) {
        console.error('Error loading data:', error);
        this.error = 'Failed to load product stock details';
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load product stock details',
          life: 3000
        });
      } finally {
        this.loading = false;
      }
    },

    async loadLocations() {
      try {
        const response = await axios.get('/api/stockages');
        if (response.data.success) {
          this.locations = response.data.data;
        }
      } catch (error) {
        console.error('Error loading locations:', error);
      }
    },

    loadStockMovements() {
      // Mock stock movements data
      this.stockMovements = [
        {
          date: new Date(),
          type: 'IN',
          quantity: 50,
          location: 'Main Warehouse',
          reason: 'Initial stock',
          user: 'Admin'
        },
        {
          date: new Date(Date.now() - 86400000),
          type: 'OUT',
          quantity: 10,
          location: 'Pharmacy A',
          reason: 'Patient prescription',
          user: 'Dr. Smith'
        }
      ];
    },

    async loadGlobalSettings() {
      try {
        const response = await axios.get(`/api/stock/products/${this.productId}/settings`);
        if (response.data.success && response.data.settings) {
          // Only merge if we don't already have settings from details
          if (Object.keys(this.globalSettings).every(key => 
            !this.globalSettings[key] || 
            (typeof this.globalSettings[key] === 'object' && Object.keys(this.globalSettings[key]).length === 0)
          )) {
            this.globalSettings = { ...this.globalSettings, ...response.data.settings };
          }
        }
      } catch (error) {
        console.error('Error loading product settings:', error);
        // Keep default settings if loading fails
      }
    },

    goBack() {
      this.$router.go(-1);
    },

    refreshData() {
      this.loadData();
    },

    exportReport() {
      // Simple export - open print dialog
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

    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString();
    },

    getStatusColor(status) {
      const colors = {
        'In Stock': 'tw-text-green-600',
        'Low Stock': 'tw-text-yellow-600',
        'Critical Stock': 'tw-text-orange-600',
        'Out of Stock': 'tw-text-red-600',
        'Expired': 'tw-text-red-700',
        'Expiring Soon': 'tw-text-yellow-700'
      };
      return colors[status] || 'tw-text-gray-600';
    },

    getStatusBg(status) {
      const colors = {
        'In Stock': 'tw-bg-green-100',
        'Low Stock': 'tw-bg-yellow-100',
        'Critical Stock': 'tw-bg-orange-100',
        'Out of Stock': 'tw-bg-red-100',
        'Expired': 'tw-bg-red-200',
        'Expiring Soon': 'tw-bg-yellow-200'
      };
      return colors[status] || 'tw-bg-gray-100';
    },

    getStatusIcon(status) {
      const icons = {
        'In Stock': 'pi pi-check-circle tw-text-green-600',
        'Low Stock': 'pi pi-exclamation-triangle tw-text-yellow-600',
        'Critical Stock': 'pi pi-exclamation-triangle tw-text-orange-600',
        'Out of Stock': 'pi pi-times-circle tw-text-red-600',
        'Expired': 'pi pi-ban tw-text-red-700',
        'Expiring Soon': 'pi pi-clock tw-text-yellow-700'
      };
      return icons[status] || 'pi pi-question-circle tw-text-gray-600';
    },

    getExpiryColor(expiryDate) {
      if (!expiryDate) return 'tw-text-gray-500';
      const now = new Date();
      const expiry = new Date(expiryDate);
      const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
      const alertDays = this.productStats?.expiry_alert_days || this.globalSettings.expiry_alert_days.days;

      if (diffDays < 0) return 'tw-text-red-600 tw-font-semibold';
      if (diffDays <= alertDays) return 'tw-text-yellow-600 tw-font-semibold';
      return 'tw-text-green-600';
    },

    getMovementSeverity(type) {
      return type === 'IN' ? 'success' : 'danger';
    },

    async addStock() {
      this.submitting = true;
      try {
        const response = await axios.post('/api/inventories', {
          product_id: this.productId,
          stockage_id: this.newStock.location_id,
          quantity: this.newStock.quantity,
          total_units: this.newStock.quantity, // Assuming total_units equals quantity for new stock
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
          this.loadData();
        }
      } catch (error) {
        console.error('Error adding stock:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to add stock',
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

    editStock(stock) {
      // Edit stock functionality
      this.toast.add({
        severity: 'info',
        summary: 'Edit',
        detail: 'Edit stock functionality will be implemented',
        life: 3000
      });
    },

    transferStock(stock) {
      // Transfer stock functionality
      this.transferData.from_location_id = this.locations.find(l => l.name === stock.location)?.id;
      this.showTransferDialog = true;
    },

    deleteStock(stock) {
      // Delete stock functionality - placeholder for now
      this.toast.add({
        severity: 'info',
        summary: 'Delete',
        detail: 'Delete stock functionality will be implemented',
        life: 3000
      });
    },

    getLowStockCount() {
      const totalQuantity = this.productStats?.total_quantity || 0;
      const threshold = this.productStats?.low_stock_threshold || this.globalSettings.min_quantity_all_services.threshold;
      return totalQuantity < threshold ? 1 : 0; // Return 1 if total is below threshold, 0 otherwise
    },

    onSearchInput() {
      // Debounce search
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        // Search is already reactive through computed property
      }, 300);
    },

    clearFilters() {
      this.locationSearch = '';
      this.locationFilter = '';
    },

    getQuantityBadgeClass(quantity) {
      const lowThreshold = this.productStats?.low_stock_threshold || this.globalSettings.min_quantity_all_services.threshold;
      const criticalThreshold = this.productStats?.critical_stock_threshold || this.globalSettings.critical_stock_threshold.threshold;

      if (quantity === 0) return 'tw-bg-red-100 tw-text-red-800';
      if (quantity <= criticalThreshold) return 'tw-bg-red-100 tw-text-red-800';
      if (quantity < lowThreshold) return 'tw-bg-yellow-100 tw-text-yellow-800';
      return 'tw-bg-green-100 tw-text-green-800';
    },

    getExpiryIconClass(expiryDate) {
      if (!expiryDate) return 'pi pi-infinity tw-text-gray-400';
      const now = new Date();
      const expiry = new Date(expiryDate);
      const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
      const alertDays = this.productStats?.expiry_alert_days || this.globalSettings.expiry_alert_days.days;

      if (diffDays < 0) return 'pi pi-exclamation-triangle tw-text-red-500';
      if (diffDays <= alertDays) return 'pi pi-clock tw-text-yellow-500';
      return 'pi pi-check-circle tw-text-green-500';
    },

    getDistributionWidth(quantity) {
      const total = this.productStats?.total_quantity || 1;
      const percentage = (quantity / total) * 100;
      return `${Math.min(percentage, 100)}%`;
    },

    viewLocationDetails(location) {
      // View location details functionality
      this.toast.add({
        severity: 'info',
        summary: 'View Details',
        detail: `Viewing details for ${location.location}`,
        life: 3000
      });
    },

    // Global Settings Methods
    async saveGlobalSettings() {
      this.savingSettings = true;
      try {
        const response = await axios.post(`/api/stock/products/${this.productId}/settings`, {
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
          await this.loadData();

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
            // Show first validation error
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
      // Reset to default values
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
  }
};
</script>

<style scoped>
/* Custom styles */
.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f8fafc !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr {
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr.p-highlight {
  background-color: #fef2f2 !important;
  border-left: 4px solid #ef4444 !important;
}

/* Enhanced button styles */
.p-button {
  transition: all 0.3s ease !important;
}

.p-button:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Dialog animations */
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

/* Enhanced form inputs */
.p-inputtext:focus,
.p-textarea:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
  border-color: #3b82f6 !important;
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

.loading-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-w-80 {
    width: 100% !important;
  }

  .tw-flex {
    flex-direction: column;
    gap: 1rem;
  }

  .tw-justify-between {
    justify-content: flex-start !important;
  }

  .tw-grid-cols-1.md\:tw-grid-cols-2 {
    grid-template-columns: 1fr;
  }
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Tag enhancements */
.p-tag {
  border-radius: 20px !important;
  font-weight: 600 !important;
  letter-spacing: 0.025em !important;
}

/* DataTable header styling */
.p-datatable .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  border: none !important;
  padding: 1rem !important;
}

/* Enhanced card shadows */
.tw-shadow-lg {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
}

.tw-shadow-xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
}

/* Card hover effects */
.p-card:hover {
  transform: translateY(-2px);
  transition: all 0.3s ease;
}

/* Gradient text effects */
.gradient-text {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Custom progress bars */
.progress-bar {
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  transition: width 0.5s ease;
}
</style>
