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
                <i class="pi pi-chart-bar tw-text-white tw-text-2xl"></i>
              </div>
              <div>
                <h1 class="tw-m-0 tw-text-gray-800 tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 tw-bg-clip-text tw-text-transparent">
                  Stock Management - {{ service?.name || 'Loading...' }}
                </h1>
                <p class="tw-m-0 tw-text-gray-600 tw-text-sm tw-mt-1">Comprehensive stock overview and management</p>
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
                label="Export Report"
                icon="pi pi-download"
                class="p-button-success tw-rounded-lg tw-shadow-md"
                @click="exportStockReport"
              />
              <Button
                label="Settings"
                icon="pi pi-cog"
                class="p-button-primary tw-rounded-lg tw-shadow-md tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-border-0"
                @click="openSettingsModal"
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
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm hover:tw-shadow-2xl tw-transition-all tw-duration-300">
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
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm hover:tw-shadow-2xl tw-transition-all tw-duration-300">
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

      <!-- Stockages Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm hover:tw-shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-purple-500 tw-to-violet-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-warehouse tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Stockages</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-m-0">{{ stockagesCount }}</p>
              <p class="tw-text-sm tw-text-gray-600 tw-m-0 tw-mt-1">Storage locations</p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-r tw-from-purple-100 tw-to-violet-100 tw-rounded-xl">
              <i class="pi pi-warehouse tw-text-purple-600 tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <!-- Alerts Card -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm hover:tw-shadow-2xl tw-transition-all tw-duration-300">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-red-500 tw-to-pink-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-exclamation-triangle tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Alerts</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-m-0">{{ alertCount }}</p>
              <p class="tw-text-sm tw-text-gray-600 tw-m-0 tw-mt-1">Need attention</p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-r tw-from-red-100 tw-to-pink-100 tw-rounded-xl">
              <i class="pi pi-exclamation-triangle tw-text-red-600 tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters and Alerts Section -->
    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6 tw-mb-8">
      <!-- Filters -->
      <Card class="lg:tw-col-span-2 tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
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
                <i class="pi pi-warehouse tw-text-blue-500"></i>
                Stockage
              </label>
              <Dropdown
                v-model="stockageFilter"
                :options="serviceStockages"
                option-label="name"
                option-value="id"
                placeholder="All Stockages"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                @change="applyFilters"
                show-clear
              />
            </div>

            <div class="tw-flex-1 tw-min-w-48">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-tag tw-text-green-500"></i>
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
                <i class="pi pi-chart-bar tw-text-orange-500"></i>
                Status
              </label>
              <Dropdown
                v-model="statusFilter"
                :options="statusOptions"
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

      <!-- Quick Alerts -->
      <Card class="tw-shadow-xl tw-border-0 tw-bg-white/80 tw-backdrop-blur-sm">
        <template #header>
          <div class="tw-p-4 tw-bg-gradient-to-r tw-from-red-500 tw-to-pink-600 tw-text-white tw-rounded-t-lg">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-bell tw-text-xl"></i>
              <h3 class="tw-m-0 tw-font-semibold">Quick Alerts</h3>
            </div>
          </div>
        </template>
        <template #content>
          <div class="tw-space-y-4">
            <div v-if="lowStockAlerts.length > 0" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gradient-to-r tw-from-yellow-50 tw-to-orange-50 tw-rounded-lg tw-border tw-border-yellow-100">
              <div class="tw-p-2 tw-bg-yellow-100 tw-rounded-lg">
                <i class="pi pi-exclamation-triangle tw-text-yellow-600"></i>
              </div>
              <div>
                <span class="tw-text-sm tw-font-semibold tw-text-yellow-800">{{ lowStockAlerts.length }}</span>
                <p class="tw-text-xs tw-text-yellow-700 tw-m-0">Low Stock</p>
              </div>
            </div>
            <div v-if="criticalStockAlerts.length > 0" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gradient-to-r tw-from-red-50 tw-to-pink-50 tw-rounded-lg tw-border tw-border-red-100">
              <div class="tw-p-2 tw-bg-red-100 tw-rounded-lg">
                <i class="pi pi-times-circle tw-text-red-600"></i>
              </div>
              <div>
                <span class="tw-text-sm tw-font-semibold tw-text-red-800">{{ criticalStockAlerts.length }}</span>
                <p class="tw-text-xs tw-text-red-700 tw-m-0">Critical Stock</p>
              </div>
            </div>
            <div v-if="reorderAlerts.length > 0" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gradient-to-r tw-from-orange-50 tw-to-red-50 tw-rounded-lg tw-border tw-border-orange-100">
              <div class="tw-p-2 tw-bg-orange-100 tw-rounded-lg">
                <i class="pi pi-shopping-cart tw-text-orange-600"></i>
              </div>
              <div>
                <span class="tw-text-sm tw-font-semibold tw-text-orange-800">{{ reorderAlerts.length }}</span>
                <p class="tw-text-xs tw-text-orange-700 tw-m-0">Reorder Needed</p>
              </div>
            </div>
            <div v-if="overstockAlerts.length > 0" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-lg tw-border tw-border-blue-100">
              <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                <i class="pi pi-arrow-up tw-text-blue-600"></i>
              </div>
              <div>
                <span class="tw-text-sm tw-font-semibold tw-text-blue-800">{{ overstockAlerts.length }}</span>
                <p class="tw-text-xs tw-text-blue-700 tw-m-0">Overstock</p>
              </div>
            </div>
            <div v-if="expiringAlerts.length > 0" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gradient-to-r tw-from-pink-50 tw-to-purple-50 tw-rounded-lg tw-border tw-border-pink-100">
              <div class="tw-p-2 tw-bg-pink-100 tw-rounded-lg">
                <i class="pi pi-calendar-times tw-text-pink-600"></i>
              </div>
              <div>
                <span class="tw-text-sm tw-font-semibold tw-text-pink-800">{{ expiringAlerts.length }}</span>
                <p class="tw-text-xs tw-text-pink-700 tw-m-0">Expiring Soon</p>
              </div>
            </div>
            <div v-if="outOfStockAlerts.length > 0" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gradient-to-r tw-from-gray-50 tw-to-slate-50 tw-rounded-lg tw-border tw-border-gray-100">
              <div class="tw-p-2 tw-bg-gray-100 tw-rounded-lg">
                <i class="pi pi-times-circle tw-text-gray-600"></i>
              </div>
              <div>
                <span class="tw-text-sm tw-font-semibold tw-text-gray-800">{{ outOfStockAlerts.length }}</span>
                <p class="tw-text-xs tw-text-gray-700 tw-m-0">Out of Stock</p>
              </div>
            </div>
            <div v-if="alertCount === 0" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-lg tw-border tw-border-green-100">
              <div class="tw-p-2 tw-bg-green-100 tw-rounded-lg">
                <i class="pi pi-check-circle tw-text-green-600"></i>
              </div>
              <div>
                <span class="tw-text-sm tw-font-semibold tw-text-green-800">All Good!</span>
                <p class="tw-text-xs tw-text-green-700 tw-m-0">No alerts</p>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

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
          :value="filteredGroupedProducts"
          :loading="loading"
          :paginator="true"
          :rows="perPage"
          :totalRecords="filteredGroupedProducts.length"
          :lazy="false"
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
              <p class="tw-text-gray-600 tw-text-lg tw-font-medium">No products found in this service</p>
              <p class="tw-text-gray-500 tw-text-sm">Try adjusting your filters or add products to stockages.</p>
            </div>
          </template>

          <Column field="productName" header="Product Name" style="min-width: 200px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-gradient-to-r tw-from-blue-100 tw-to-indigo-100 tw-rounded-lg">
                  <i class="pi pi-box tw-text-blue-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.productName }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.forme }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="category.name" header="Category" style="min-width: 150px" sortable>
            <template #body="slotProps">
              <span class="tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">
                {{ slotProps.data.category?.name || 'N/A' }}
              </span>
            </template>
          </Column>

          <Column header="Total Quantity" style="min-width: 150px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <span :class="getQuantityClass(slotProps.data.totalQuantity, slotProps.data)">
                  {{ Number(slotProps.data.totalQuantity) }}
                </span>
                <span class="tw-text-sm tw-text-gray-500">{{ slotProps.data.unit }}</span>
                <span v-if="slotProps.data.boite_de && slotProps.data.totalQuantity" class="tw-text-xs tw-text-blue-600 tw-bg-blue-50 tw-px-2 tw-py-1 tw-rounded">
                  {{ slotProps.data.totalBoxes }} boxes
                </span>
              </div>
            </template>
          </Column>

          <!-- Min Level column removed -->

          <Column header="Stockages" style="min-width: 150px">
            <template #body="slotProps">
              <div class="tw-flex tw-flex-wrap tw-gap-1">
                <Tag
                  v-for="stockage in Array.from(slotProps.data.stockages)"
                  :key="stockage"
                  :value="stockage"
                  severity="info"
                  class="tw-text-xs tw-font-medium tw-px-2 tw-py-1"
                />
              </div>
            </template>
          </Column>

          <Column header="Latest Expiry" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <span v-if="slotProps.data.latestExpiry" :class="getExpiryClass(slotProps.data.latestExpiry)">
                {{ formatDate(slotProps.data.latestExpiry) }}
              </span>
              <span v-else class="tw-text-gray-500">N/A</span>
            </template>
          </Column>

          <Column header="Status" style="min-width: 120px">
            <template #body="slotProps">
              <Tag :value="getProductGroupStatus(slotProps.data)" :severity="getProductGroupStatusSeverity(slotProps.data)" class="tw-font-semibold" />
            </template>
          </Column>

          <Column header="Actions" style="min-width: 150px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-eye"
                  class="p-button-info p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                  @click="viewProductDetails(slotProps.data)"
                  v-tooltip.top="'View Batch Details'"
                />
                <Button
                  icon="pi pi-cog"
                  class="p-button-warning p-button-sm tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                  @click="editProductSettings(slotProps.data)"
                  v-tooltip.top="'Edit Settings'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Settings Modal -->
    <Dialog
      v-model:visible="showSettingsModal"
      modal
      header="Stock Management Settings"
      :style="{ width: '60rem' }"
      class="settings-modal"
    >
      <div class="tw-space-y-6">
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-5 tw-border tw-border-blue-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-blue-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-bell tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Alert Settings</h3>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-exclamation-triangle tw-text-yellow-500 tw-mr-2"></i>
                Low Stock Alert Threshold (%)
              </label>
              <InputNumber
                v-model="settings.lowStockThreshold"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="1"
                max="100"
                suffix="%"
                placeholder="e.g., 20"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Alert when stock falls below this percentage of minimum level
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-calendar-times tw-text-red-500 tw-mr-2"></i>
                Expiry Alert Days
              </label>
              <InputNumber
                v-model="settings.expiryAlertDays"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="1"
                max="365"
                suffix=" days"
                placeholder="e.g., 30"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Alert when products expire within this many days
              </small>
            </div>
          </div>
        </div>

        <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-5 tw-border tw-border-green-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-green-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-chart-line tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Display Settings</h3>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-sort-amount-up tw-text-green-500 tw-mr-2"></i>
                Default Sort By
              </label>
              <Dropdown
                v-model="settings.defaultSortBy"
                :options="sortOptions"
                option-label="label"
                option-value="value"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                placeholder="Select default sort"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-list tw-text-green-500 tw-mr-2"></i>
                Items Per Page
              </label>
              <Dropdown
                v-model="settings.itemsPerPage"
                :options="pageSizeOptions"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                placeholder="Select page size"
              />
            </div>
          </div>
        </div>

        <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-violet-50 tw-rounded-xl tw-p-5 tw-border tw-border-purple-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-purple-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-envelope tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Notification Settings</h3>
          </div>

          <div class="tw-space-y-4">
            <div class="tw-flex tw-items-center tw-gap-3">
              <input
                type="checkbox"
                id="emailAlerts"
                v-model="settings.emailAlerts"
                class="tw-w-4 tw-h-4 tw-text-purple-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-purple-500"
              />
              <label for="emailAlerts" class="tw-text-sm tw-text-gray-700">
                <i class="pi pi-envelope tw-text-purple-500 tw-mr-2"></i>
                Email notifications for alerts
              </label>
            </div>

            <div class="tw-flex tw-items-center tw-gap-3">
              <input
                type="checkbox"
                id="smsAlerts"
                v-model="settings.smsAlerts"
                class="tw-w-4 tw-h-4 tw-text-purple-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-purple-500"
              />
              <label for="smsAlerts" class="tw-text-sm tw-text-gray-700">
                <i class="pi pi-mobile tw-text-purple-500 tw-mr-2"></i>
                SMS notifications for critical alerts
              </label>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-secondary tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            @click="closeSettingsModal"
          />
          <Button
            label="Save Settings"
            icon="pi pi-save"
            class="p-button-primary tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            :loading="savingSettings"
            @click="saveSettings"
          />
        </div>
      </template>
    </Dialog>

    <!-- Product Details Modal -->
    <Dialog
      v-model:visible="showDetailsModal"
      modal
      :header="`Product Details - ${selectedProductGroup?.productName || ''}`"
      :style="{ width: '70rem' }"
      class="product-details-modal"
    >
      <div v-if="selectedProductGroup" class="tw-space-y-6">
        <!-- Product Summary -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-5 tw-border tw-border-blue-100">
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
            <div class="tw-text-center">
              <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ selectedProductGroup.totalQuantity }}</div>
              <div class="tw-text-sm tw-text-gray-600">Total Units</div>
            </div>
            <div class="tw-text-center">
              <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ selectedProductGroup.totalBoxes }}</div>
              <div class="tw-text-sm tw-text-gray-600">Total Boxes</div>
            </div>
            <div class="tw-text-center">
              <div class="tw-text-2xl tw-font-bold tw-text-purple-600">{{ selectedProductGroup.items.length }}</div>
              <div class="tw-text-sm tw-text-gray-600">Stock Entries</div>
            </div>
            <div class="tw-text-center">
              <div class="tw-text-2xl tw-font-bold tw-text-orange-600">{{ selectedProductGroup.batches.length }}</div>
              <div class="tw-text-sm tw-text-gray-600">Batches</div>
            </div>
          </div>
        </div>

        <!-- Batch Details Table -->
        <div class="tw-bg-white tw-rounded-xl tw-border tw-border-gray-200 tw-overflow-hidden tw-shadow-lg">
          <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-px-4 tw-py-3 tw-text-white">
            <h3 class="tw-text-lg tw-font-semibold tw-m-0">Batch Details</h3>
          </div>

          <div class="tw-p-4">
            <DataTable
              :value="selectedProductGroup.batches"
              class="p-datatable-sm tw-rounded-xl tw-overflow-hidden"
              striped-rows
              show-gridlines
              responsive-layout="scroll"
            >
              <template #empty>
                <div class="tw-text-center tw-py-8 tw-text-gray-500">
                  <i class="pi pi-info-circle tw-text-3xl tw-mb-2 tw-opacity-50"></i>
                  <p class="tw-m-0">No batch information available</p>
                </div>
              </template>

              <Column field="batch_number" header="Batch Number" style="min-width: 120px" />
              <Column field="quantity" header="Quantity" style="min-width: 100px" />
              <Column field="stockage" header="Stockage" style="min-width: 120px" />
              <Column field="location" header="Location" style="min-width: 120px" />
              <Column field="expiry_date" header="Expiry Date" style="min-width: 120px">
                <template #body="slotProps">
                  <span v-if="slotProps.data.expiry_date" :class="getExpiryClass(slotProps.data.expiry_date)">
                    {{ formatDate(slotProps.data.expiry_date) }}
                  </span>
                  <span v-else class="tw-text-gray-500">N/A</span>
                </template>
              </Column>
            </DataTable>
          </div>
        </div>

        <!-- Stock Entries Table -->
        <div class="tw-bg-white tw-rounded-xl tw-border tw-border-gray-200 tw-overflow-hidden tw-shadow-lg">
          <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-px-4 tw-py-3 tw-text-white">
            <h3 class="tw-text-lg tw-font-semibold tw-m-0">Stock Entries</h3>
          </div>

          <div class="tw-p-4">
            <DataTable
              :value="selectedProductGroup.items"
              class="p-datatable-sm tw-rounded-xl tw-overflow-hidden"
              striped-rows
              show-gridlines
              responsive-layout="scroll"
            >
              <Column field="stockage.name" header="Stockage" style="min-width: 120px" />
              <Column field="quantity" header="Quantity" style="min-width: 100px" />
              <Column field="total_units" header="Total Units" style="min-width: 100px" />
              <Column field="batch_number" header="Batch" style="min-width: 120px" />
              <Column field="location" header="Location" style="min-width: 120px" />
              <Column field="expiry_date" header="Expiry Date" style="min-width: 120px">
                <template #body="slotProps">
                  <span v-if="slotProps.data.expiry_date" :class="getExpiryClass(slotProps.data.expiry_date)">
                    {{ formatDate(slotProps.data.expiry_date) }}
                  </span>
                  <span v-else class="tw-text-gray-500">N/A</span>
                </template>
              </Column>
              <Column field="updated_at" header="Last Updated" style="min-width: 120px">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.updated_at) }}
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end">
          <Button
            label="Close"
            icon="pi pi-times"
            class="p-button-secondary tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            @click="closeDetailsModal"
          />
        </div>
      </template>
    </Dialog>

    <!-- Product Settings Modal -->
    <Dialog
      v-model:visible="showProductSettingsModal"
      modal
      :header="`Product Settings - ${selectedProductGroup?.productName || ''}`"
      :style="{ width: '70rem' }"
      class="product-settings-modal"
    >
      <div v-if="selectedProductSettings" class="tw-space-y-6">
        <!-- Alert Settings -->
        <div class="tw-bg-gradient-to-r tw-from-red-50 tw-to-pink-50 tw-rounded-xl tw-p-5 tw-border tw-border-red-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-red-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-bell tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Alert Settings</h3>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-exclamation-triangle tw-text-yellow-500 tw-mr-2"></i>
                Low Stock Threshold
              </label>
              <InputNumber
                v-model="selectedProductSettings.lowStockThreshold"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="0"
                :suffix="` ${selectedProductGroup?.unit || 'units'}`"
                placeholder="e.g., 10"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Alert when stock falls below this level
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-times-circle tw-text-red-500 tw-mr-2"></i>
                Critical Stock Threshold
              </label>
              <InputNumber
                v-model="selectedProductSettings.criticalStockThreshold"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="0"
                :suffix="` ${selectedProductGroup?.unit || 'units'}`"
                placeholder="e.g., 5"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Critical alert threshold
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-arrow-up tw-text-blue-500 tw-mr-2"></i>
                Maximum Stock Level
              </label>
              <InputNumber
                v-model="selectedProductSettings.maxStockLevel"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="0"
                :suffix="` ${selectedProductGroup?.unit || 'units'}`"
                placeholder="e.g., 100"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Maximum stock level to maintain
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-shopping-cart tw-text-green-500 tw-mr-2"></i>
                Reorder Point
              </label>
              <InputNumber
                v-model="selectedProductSettings.reorderPoint"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="0"
                :suffix="` ${selectedProductGroup?.unit || 'units'}`"
                placeholder="e.g., 15"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Point at which to reorder
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-calendar-times tw-text-orange-500 tw-mr-2"></i>
                Expiry Alert Days
              </label>
              <InputNumber
                v-model="selectedProductSettings.expiryAlertDays"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="1"
                max="365"
                suffix=" days"
                placeholder="e.g., 30"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Alert before expiry
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-clock tw-text-purple-500 tw-mr-2"></i>
                Min Shelf Life
              </label>
              <InputNumber
                v-model="selectedProductSettings.minShelfLifeDays"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                min="1"
                suffix=" days"
                placeholder="e.g., 90"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Minimum shelf life required
              </small>
            </div>
          </div>
        </div>

        <!-- Notification Settings -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-5 tw-border tw-border-blue-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-blue-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-envelope tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Notification Settings</h3>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-send tw-text-blue-500 tw-mr-2"></i>
                Alert Frequency
              </label>
              <Dropdown
                v-model="selectedProductSettings.alertFrequency"
                :options="alertFrequencyOptions"
                option-label="label"
                option-value="value"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                placeholder="Select frequency"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                How often to send alerts
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-truck tw-text-green-500 tw-mr-2"></i>
                Preferred Supplier
              </label>
              <InputText
                v-model="selectedProductSettings.preferredSupplier"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                placeholder="Supplier name"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Default supplier for reordering
              </small>
            </div>
          </div>

          <div class="tw-space-y-4 tw-mt-4">
            <div class="tw-flex tw-items-center tw-gap-3">
              <input
                type="checkbox"
                id="productEmailAlerts"
                v-model="selectedProductSettings.emailAlerts"
                class="tw-w-4 tw-h-4 tw-text-blue-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-blue-500"
              />
              <label for="productEmailAlerts" class="tw-text-sm tw-text-gray-700">
                <i class="pi pi-envelope tw-text-blue-500 tw-mr-2"></i>
                Email notifications for this product
              </label>
            </div>

            <div class="tw-flex tw-items-center tw-gap-3">
              <input
                type="checkbox"
                id="productSmsAlerts"
                v-model="selectedProductSettings.smsAlerts"
                class="tw-w-4 tw-h-4 tw-text-blue-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-blue-500"
              />
              <label for="productSmsAlerts" class="tw-text-sm tw-text-gray-700">
                <i class="pi pi-mobile tw-text-blue-500 tw-mr-2"></i>
                SMS notifications for critical alerts
              </label>
            </div>

            <div class="tw-flex tw-items-center tw-gap-3">
              <input
                type="checkbox"
                id="autoReorder"
                v-model="selectedProductSettings.autoReorder"
                class="tw-w-4 tw-h-4 tw-text-blue-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-blue-500"
              />
              <label for="autoReorder" class="tw-text-sm tw-text-gray-700">
                <i class="pi pi-refresh tw-text-blue-500 tw-mr-2"></i>
                Enable automatic reordering
              </label>
            </div>
          </div>
        </div>

        <!-- Inventory Settings -->
        <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-5 tw-border tw-border-green-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-green-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-cog tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Inventory Settings</h3>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-tags tw-text-green-500 tw-mr-2"></i>
                Custom Product Name
              </label>
              <InputText
                v-model="selectedProductSettings.customName"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                :placeholder="selectedProductGroup?.productName || 'Custom name'"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Display name override
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-palette tw-text-purple-500 tw-mr-2"></i>
                Color Code
              </label>
              <Dropdown
                v-model="selectedProductSettings.colorCode"
                :options="colorOptions"
                option-label="label"
                option-value="value"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                placeholder="Select color"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Status color coding
              </small>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-star tw-text-yellow-500 tw-mr-2"></i>
                Priority Level
              </label>
              <Dropdown
                v-model="selectedProductSettings.priority"
                :options="priorityOptions"
                option-label="label"
                option-value="value"
                class="tw-w-full tw-rounded-lg tw-shadow-sm"
                placeholder="Select priority"
              />
              <small class="tw-text-gray-500 tw-block tw-mt-1">
                Alert priority level
              </small>
            </div>
          </div>

          <div class="tw-space-y-4 tw-mt-4">
            <div class="tw-flex tw-items-center tw-gap-3">
              <input
                type="checkbox"
                id="batchTracking"
                v-model="selectedProductSettings.batchTracking"
                class="tw-w-4 tw-h-4 tw-text-green-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-green-500"
              />
              <label for="batchTracking" class="tw-text-sm tw-text-gray-700">
                <i class="pi pi-list tw-text-green-500 tw-mr-2"></i>
                Enable batch tracking
              </label>
            </div>

            <div class="tw-flex tw-items-center tw-gap-3">
              <input
                type="checkbox"
                id="locationTracking"
                v-model="selectedProductSettings.locationTracking"
                class="tw-w-4 tw-h-4 tw-text-green-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-green-500"
              />
              <label for="locationTracking" class="tw-text-sm tw-text-gray-700">
                <i class="pi pi-map-marker tw-text-green-500 tw-mr-2"></i>
                Enable location tracking
              </label>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-secondary tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            @click="closeProductSettingsModal"
          />
          <Button
            label="Save Product Settings"
            icon="pi pi-save"
            class="p-button-primary tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            :loading="savingSettings"
            @click="saveProductSettings"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';

export default {
  name: 'ServiceStockManagement',
  components: {
    Card,
    Button,
    InputText,
    InputNumber,
    Dropdown,
    DataTable,
    Column,
    Dialog,
    Tag,
    ProgressSpinner,
    Toast
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
      statusFilter: '',
      submitSuccess: false,
      submitError: null,
      searchTimeout: null,

      // Data
      service: null,
      products: [],
      serviceStockages: [],
      categories: [],

      // Loading states
      loading: false,

      // Modals
      showSettingsModal: false,
      showDetailsModal: false,
      showProductSettingsModal: false,
      selectedProductGroup: null,
      selectedProductSettings: null,
      savingSettings: false,

      // Settings
      settings: {
        lowStockThreshold: 20,
        expiryAlertDays: 30,
        defaultSortBy: 'name',
        itemsPerPage: 10,
        emailAlerts: true,
        smsAlerts: false
      },

      // Options
      statusOptions: [
        { label: 'All', value: '' },
        { label: 'In Stock', value: 'in_stock' },
        { label: 'Low Stock', value: 'low_stock' },
        { label: 'Out of Stock', value: 'out_of_stock' },
        { label: 'Expiring Soon', value: 'expiring' }
      ],
      sortOptions: [
        { label: 'Product Name', value: 'name' },
        { label: 'Quantity', value: 'quantity' },
        { label: 'Expiry Date', value: 'expiry_date' },
        { label: 'Last Updated', value: 'updated_at' }
      ],
      pageSizeOptions: [10, 25, 50, 100],
      alertFrequencyOptions: [
        { label: 'Immediate', value: 'immediate' },
        { label: 'Daily Summary', value: 'daily' },
        { label: 'Weekly Summary', value: 'weekly' }
      ],
      colorOptions: [
        { label: 'Default', value: 'default' },
        { label: 'Red', value: 'red' },
        { label: 'Orange', value: 'orange' },
        { label: 'Yellow', value: 'yellow' },
        { label: 'Green', value: 'green' },
        { label: 'Blue', value: 'blue' },
        { label: 'Purple', value: 'purple' }
      ],
      priorityOptions: [
        { label: 'Low', value: 'low' },
        { label: 'Normal', value: 'normal' },
        { label: 'High', value: 'high' },
        { label: 'Critical', value: 'critical' }
      ]
  ,
  // Cache for product-specific settings loaded from backend
  productSettingsCache: {},
  // Track which products are currently being loaded to prevent duplicate requests
  loadingProductSettings: {}
    }
  },
  mounted() {
    this.fetchService();
    this.fetchServiceStockages();
    this.fetchProducts();
    this.fetchCategories();
    this.loadSettings();
  },
  computed: {
    // Group products by name, forme, and boite_de
    groupedProducts() {
      const groups = {};
      
      this.products.forEach(item => {
        const product = item.product || {};
        const key = `${product.name || 'N/A'}_${product.forme || 'N/A'}_${product.boite_de || 'N/A'}`;
        
        if (!groups[key]) {
          groups[key] = {
            productName: product.name || 'N/A',
            forme: product.forme || 'N/A',
            boite_de: product.boite_de || 'N/A',
            category: product.category || {},
            unit: product.unit || 'units',
            items: [],
            totalQuantity: 0,
            totalBoxes: 0,
            // minStockLevel removed; use product-specific settings instead
            latestExpiry: null,
            stockages: new Set(),
            batches: []
          };
        }
        
        const quantity = Number(item.total_units || item.quantity || 0);
        const boxes = product.boite_de ? Math.floor(quantity / product.boite_de) : 0;
        
        groups[key].items.push(item);
        groups[key].totalQuantity += quantity;
        groups[key].totalBoxes += boxes;
  // removed use of item.min_stock_level per request
        
        if (item.stockage) {
          groups[key].stockages.add(item.stockage.name);
        }
        
        // Track batches and expiry dates
        if (item.batch_number) {
          groups[key].batches.push({
            batch_number: item.batch_number,
            quantity: quantity,
            expiry_date: item.expiry_date,
            location: item.location,
            stockage: item.stockage?.name
          });
        }
        
        // Find latest expiry date
        if (item.expiry_date) {
          const expiryDate = new Date(item.expiry_date);
          if (!groups[key].latestExpiry || expiryDate > groups[key].latestExpiry) {
            groups[key].latestExpiry = expiryDate;
          }
        }
      });
      
      return Object.values(groups);
    },
    
    filteredGroupedProducts() {
      let filtered = [...this.groupedProducts];

      // Filter by category
      if (this.categoryFilter) {
        filtered = filtered.filter(group => group.category?.id == this.categoryFilter);
      }

      // Filter by status
      if (this.statusFilter) {
        switch (this.statusFilter) {
          case 'in_stock':
            filtered = filtered.filter(group => group.totalQuantity > 0);
            break;
          case 'low_stock':
            filtered = filtered.filter(group => {
              const settings = this.getProductSettings(group);
              const lowThreshold = (settings && typeof settings.lowStockThreshold !== 'undefined')
                ? settings.lowStockThreshold
                : (settings?.lowStockThreshold ?? 0);
              return group.totalQuantity <= lowThreshold && group.totalQuantity > 0;
            });
            break;
          case 'out_of_stock':
            filtered = filtered.filter(group => group.totalQuantity <= 0);
            break;
          case 'expiring':
            filtered = filtered.filter(group => {
              const now = new Date();
              const settings = this.getProductSettings ? this.getProductSettings(group) : null;
              const alertDays = settings?.expiryAlertDays ?? this.settings.expiryAlertDays ?? 30;
              const alertDate = new Date(now.getTime() + (alertDays * 24 * 60 * 60 * 1000));
              return group.latestExpiry && group.latestExpiry <= alertDate;
            });
            break;
        }
      }

      // Filter by search query
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(group => 
          group.productName.toLowerCase().includes(query) ||
          group.forme.toLowerCase().includes(query) ||
          group.category?.name?.toLowerCase().includes(query)
        );
      }

      return filtered;
    },

    totalProducts() {
      return this.filteredGroupedProducts.length;
    },
    totalQuantity() {
      return this.filteredGroupedProducts.reduce((sum, group) => sum + group.totalQuantity, 0);
    },
    stockagesCount() {
      return this.serviceStockages.length;
    },
    alertCount() {
      return this.lowStockAlerts.length + this.expiringAlerts.length + this.outOfStockAlerts.length + 
             this.criticalStockAlerts.length + this.reorderAlerts.length + this.overstockAlerts.length;
    },
    perPage() {
      return this.settings.itemsPerPage || 10;
    },
    lowStockAlerts() {
      return this.filteredGroupedProducts.filter(group => {
        const settings = this.getProductSettings(group);
        const lowThreshold = (settings && typeof settings.lowStockThreshold !== 'undefined')
          ? settings.lowStockThreshold
          : (settings?.lowStockThreshold ?? 0);
        return group.totalQuantity <= lowThreshold && group.totalQuantity > 0;
      });
    },
    expiringAlerts() {
      const now = new Date();
      return this.filteredGroupedProducts.filter(group => {
        if (!group.latestExpiry) return false;
        const settings = this.getProductSettings ? this.getProductSettings(group) : null;
        const alertDays = settings?.expiryAlertDays ?? this.settings.expiryAlertDays ?? 30;
        const alertDate = new Date(now.getTime() + (alertDays * 24 * 60 * 60 * 1000));
        return group.latestExpiry && group.latestExpiry <= alertDate;
      });
    },
    outOfStockAlerts() {
      return this.filteredGroupedProducts.filter(group => group.totalQuantity <= 0);
    },
    criticalStockAlerts() {
      return this.filteredGroupedProducts.filter(group => {
        const settings = this.getProductSettings(group);
        return group.totalQuantity <= (settings?.criticalStockThreshold || 0) && group.totalQuantity > 0;
      });
    },
    reorderAlerts() {
      return this.filteredGroupedProducts.filter(group => {
        const settings = this.getProductSettings(group);
        return group.totalQuantity <= (settings?.reorderPoint || 0) && group.totalQuantity > 0;
      });
    },
    overstockAlerts() {
      return this.filteredGroupedProducts.filter(group => {
        const settings = this.getProductSettings(group);
        return settings?.maxStockLevel && group.totalQuantity > settings.maxStockLevel;
      });
    }
  },
  methods: {
    async fetchService() {
      try {
        const response = await axios.get(`/api/services/${this.serviceId}`);
        if (response.data.success) {
          this.service = response.data.data;
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
        const response = await axios.get(`/api/pharmacy/stockages?service_id=${this.serviceId}`);
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
          per_page: 10000, // Fetch all for management view
          service_id: this.serviceId
        };

        if (this.searchQuery.trim()) {
          params.search = this.searchQuery;
        }

        const response = await axios.get('/api/pharmacy/inventory/service-stock', { params });
        if (response.data.success) {
          this.products = response.data.data;
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

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        // Search is now handled in computed property
        this.$forceUpdate();
      }, 300);
    },

    applyFilters() {
      // Filters are now handled in computed property
      this.$forceUpdate();
    },

    clearFilters() {
      this.stockageFilter = '';
      this.categoryFilter = '';
      this.statusFilter = '';
      this.$forceUpdate();
    },

    // Helper methods
    getQuantityClass(quantity, minLevel, productGroup) {
      const qty = Number(quantity || 0);
      const settings = this.getProductSettings(productGroup);
      
      if (qty <= 0) return 'tw-text-red-600 tw-font-semibold';
      
      // Check critical stock first
      if (settings?.criticalStockThreshold && qty <= settings.criticalStockThreshold) {
        return 'tw-text-red-700 tw-font-bold';
      }
      
      // Check low stock
      const lowThreshold = settings?.lowStockThreshold || minLevel || 0;
      if (qty <= lowThreshold) return 'tw-text-yellow-600 tw-font-semibold';
      
      // Check overstock
      if (settings?.maxStockLevel && qty > settings.maxStockLevel) {
        return 'tw-text-blue-600 tw-font-semibold';
      }
      
      return 'tw-text-green-600 tw-font-semibold';
    },

    getExpiryClass(expiryDate) {
      const now = new Date();
      const expiry = new Date(expiryDate);
      const thirtyDaysFromNow = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));

      if (expiry <= now) return 'tw-text-red-600 tw-font-semibold';
      if (expiry <= thirtyDaysFromNow) return 'tw-text-yellow-600 tw-font-semibold';
      return 'tw-text-gray-600';
    },

    getProductStatus(product) {
      const quantity = Number(product.total_units || product.quantity || 0);
  // Determine min level from product-specific settings if available
  const settings = this.getProductSettings ? this.getProductSettings(product) : null;
  const minLevel = settings?.lowStockThreshold ?? 0;

  if (quantity <= 0) return 'Out of Stock';
  if (minLevel && quantity <= minLevel) return 'Low Stock';

      if (product.expiry_date) {
  const expiry = new Date(product.expiry_date);
  const now = new Date();
  const settings = this.getProductSettings ? this.getProductSettings(product) : null;
  const alertDays = settings?.expiryAlertDays ?? this.settings.expiryAlertDays ?? 30;
  const alertDate = new Date(now.getTime() + (alertDays * 24 * 60 * 60 * 1000));

  if (expiry <= now) return 'Expired';
  if (expiry <= alertDate) return 'Expiring Soon';
      }

      return 'In Stock';
    },

    getProductGroupStatus(productGroup) {
      const quantity = productGroup.totalQuantity;
      const settings = this.getProductSettings(productGroup);
      
      if (quantity <= 0) return 'Out of Stock';
      
      // Check critical stock first
      if (settings?.criticalStockThreshold && quantity <= settings.criticalStockThreshold) {
        return 'Critical Stock';
      }
      
      // Check low stock
  const lowThreshold = settings?.lowStockThreshold ?? 0;
      if (quantity <= lowThreshold) return 'Low Stock';
      
      // Check overstock
      if (settings?.maxStockLevel && quantity > settings.maxStockLevel) {
        return 'Overstock';
      }

      if (productGroup.latestExpiry) {
        const expiry = productGroup.latestExpiry;
        const now = new Date();
        const alertDate = new Date(now.getTime() + ((settings?.expiryAlertDays || this.settings.expiryAlertDays || 30) * 24 * 60 * 60 * 1000));

        if (expiry <= now) return 'Expired';
        if (expiry <= alertDate) return 'Expiring Soon';
      }

      return 'In Stock';
    },

    getProductGroupStatusSeverity(productGroup) {
      const status = this.getProductGroupStatus(productGroup);
      const severities = {
        'In Stock': 'success',
        'Low Stock': 'warning',
        'Critical Stock': 'danger',
        'Out of Stock': 'danger',
        'Expired': 'danger',
        'Expiring Soon': 'warning',
        'Overstock': 'info'
      };
      return severities[status] || 'info';
    },

    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString();
    },

    viewProductDetails(productGroup) {
      this.selectedProductGroup = productGroup;
      this.showDetailsModal = true;
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedProductGroup = null;
    },

    async editProductSettings(productGroup) {
      this.selectedProductGroup = productGroup;
      // Load settings before showing modal
      await this.loadProductSettings(productGroup);
      this.showProductSettingsModal = true;
    },

    loadProductSettings(productGroup) {
      // Load product-specific settings from backend API using web routes
      const productName = productGroup.productName;
      const productForme = productGroup.forme !== 'N/A' ? productGroup.forme : 'N/A';
      const key = `${productName}::${productForme}`;

      // Return cached if available
      if (this.productSettingsCache[key]) {
        // if modal open for this product, set selected settings
        if (this.selectedProductGroup && this.selectedProductGroup.productName === productName && this.selectedProductGroup.forme === productGroup.forme) {
          this.selectedProductSettings = this.productSettingsCache[key];
        }
        return Promise.resolve(this.productSettingsCache[key]);
      }

      // If already loading, return the existing promise
      if (this.loadingProductSettings[key]) {
        return this.loadingProductSettings[key];
      }

      // Create and store the loading promise
      const loadingPromise = axios.get(`/api/pharmacy/product-settings/${this.serviceId}/${encodeURIComponent(productName)}/${encodeURIComponent(productForme)}`)
        .then(response => {
          const payload = response.data || {};
          if (payload.success && payload.data) {
            const mapped = this.mapRemoteSettings(payload.data);
            this.productSettingsCache[key] = mapped;
            if (this.selectedProductGroup && this.selectedProductGroup.productName === productName && this.selectedProductGroup.forme === productGroup.forme) {
              this.selectedProductSettings = mapped;
            }
            return mapped;
          }

          // Not found: cache and return default
          const defaults = this.getDefaultProductSettings(productGroup);
          this.productSettingsCache[key] = defaults;
          if (this.selectedProductGroup && this.selectedProductGroup.productName === productName && this.selectedProductGroup.forme === productGroup.forme) {
            this.selectedProductSettings = defaults;
          }
          return defaults;
        })
        .catch(error => {
          console.error('Failed to load product settings:', error);
          const defaults = this.getDefaultProductSettings(productGroup);
          this.productSettingsCache[key] = defaults;
          if (this.selectedProductGroup && this.selectedProductGroup.productName === productName && this.selectedProductGroup.forme === productGroup.forme) {
            this.selectedProductSettings = defaults;
          }
          return defaults;
        })
        .finally(() => {
          // Clean up the loading promise
          delete this.loadingProductSettings[key];
        });

      this.loadingProductSettings[key] = loadingPromise;
      return loadingPromise;
    },

    // Map server response fields (snake_case) to frontend camelCase keys
    mapRemoteSettings(raw) {
      if (!raw) return null;
      return {
        lowStockThreshold: raw.low_stock_threshold ?? raw.lowStockThreshold ?? null,
        criticalStockThreshold: raw.critical_stock_threshold ?? raw.criticalStockThreshold ?? null,
        maxStockLevel: raw.max_stock_level ?? raw.maxStockLevel ?? null,
        reorderPoint: raw.reorder_point ?? raw.reorderPoint ?? null,
        expiryAlertDays: raw.expiry_alert_days ?? raw.expiryAlertDays ?? (this.settings.expiryAlertDays || 30),
        minShelfLifeDays: raw.min_shelf_life_days ?? raw.minShelfLifeDays ?? null,
        emailAlerts: raw.email_alerts ?? raw.emailAlerts ?? false,
        smsAlerts: raw.sms_alerts ?? raw.smsAlerts ?? false,
        alertFrequency: raw.alert_frequency ?? raw.alertFrequency ?? null,
        preferredSupplier: raw.preferred_supplier ?? raw.preferredSupplier ?? null,
        batchTracking: raw.batch_tracking ?? raw.batchTracking ?? true,
        locationTracking: raw.location_tracking ?? raw.locationTracking ?? true,
        autoReorder: raw.auto_reorder ?? raw.autoReorder ?? false,
        customName: raw.custom_name ?? raw.customName ?? null,
        colorCode: raw.color_code ?? raw.colorCode ?? 'default',
        priority: raw.priority ?? raw.priority ?? 'normal'
      };
    },

    getDefaultProductSettings(productGroup) {
      // If the product group had a legacy minStockLevel it was removed; use sensible defaults
      const base = 10;
      return {
        // Alert Settings
        lowStockThreshold: base,
        criticalStockThreshold: Math.floor(base * 0.5),
        maxStockLevel: base * 3,
        reorderPoint: base,
        expiryAlertDays: 30,
        minShelfLifeDays: 90,
        
        // Notification Settings
        emailAlerts: productGroup.email_alerts,
        smsAlerts: productGroup.sms_alerts,
        alertFrequency: productGroup.alert_frequency || 'immediate', // immediate, daily, weekly
        preferredSupplier: productGroup.preferred_supplier || '',
        
        // Inventory Settings
        batchTracking: productGroup.batch_tracking,
        locationTracking: productGroup.location_tracking,
        autoReorder: productGroup.auto_reorder,
        
        // Display Settings
        customName: productGroup.productName,
        colorCode: productGroup.color_code || 'default',
        priority: productGroup.priority || 'normal'
      };
    },

    async saveProductSettings() {
      this.savingSettings = true;
      try {
        // Helper to coerce numeric values to integers or null
        const toIntOrNull = (v) => {
          if (v === null || v === undefined || v === '') return null;
          const n = parseInt(v, 10);
          return isNaN(n) ? null : n;
        };

        // Prepare settings data for the backend
        const settingsData = {
          service_id: this.serviceId,
          product_name: this.selectedProductGroup.productName,
          product_forme: this.selectedProductGroup.forme !== 'N/A' ? this.selectedProductGroup.forme : null,
          
          // Alert Settings
          low_stock_threshold: toIntOrNull(this.selectedProductSettings.lowStockThreshold),
          critical_stock_threshold: toIntOrNull(this.selectedProductSettings.criticalStockThreshold),
          max_stock_level: toIntOrNull(this.selectedProductSettings.maxStockLevel),
          reorder_point: toIntOrNull(this.selectedProductSettings.reorderPoint),
          expiry_alert_days: toIntOrNull(this.selectedProductSettings.expiryAlertDays),
          min_shelf_life_days: toIntOrNull(this.selectedProductSettings.minShelfLifeDays),
          
          // Notification Settings
          email_alerts: this.selectedProductSettings.emailAlerts,
          sms_alerts: this.selectedProductSettings.smsAlerts,
          alert_frequency: this.selectedProductSettings.alertFrequency,
          preferred_supplier: this.selectedProductSettings.preferredSupplier,
          
          // Inventory Settings
          batch_tracking: this.selectedProductSettings.batchTracking,
          location_tracking: this.selectedProductSettings.locationTracking,
          auto_reorder: this.selectedProductSettings.autoReorder,
          
          // Display Settings
          custom_name: this.selectedProductSettings.customName,
          color_code: this.selectedProductSettings.colorCode,
          priority: this.selectedProductSettings.priority
        };

        // Save product settings using web routes
        const response = await axios.post('/api/pharmacy/product-settings', settingsData);

        if (response.data.success) {
          this.$toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Product settings saved successfully',
            life: 3000
          });
          this.closeProductSettingsModal();

          // Refresh data to apply new settings
          this.fetchProducts();
        } else {
          throw new Error(response.data.message || 'Failed to save settings');
        }
      } catch (error) {
        console.error('Failed to save product settings:', error);
        this.$toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to save product settings. Please try again.',
          life: 5000
        });
      } finally {
        this.savingSettings = false;
      }
    },

    closeProductSettingsModal() {
      this.showProductSettingsModal = false;
      this.selectedProductGroup = null;
      this.selectedProductSettings = null;
    },

    exportStockReport() {
      // TODO: Implement export functionality
      this.$toast.add({
        severity: 'info',
        summary: 'Coming Soon',
        detail: 'Export feature is coming soon!',
        life: 3000
      });
    },

    openSettingsModal() {
      this.showSettingsModal = true;
    },

    closeSettingsModal() {
      this.showSettingsModal = false;
    },

    async saveSettings() {
      this.savingSettings = true;
      try {
        // TODO: Save settings to backend API
        // For now, keep localStorage but add API call later
        localStorage.setItem(`stock_settings_${this.serviceId}`, JSON.stringify(this.settings));

        this.$toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Settings saved successfully',
          life: 3000
        });
        this.closeSettingsModal();
      } catch (error) {
        console.error('Failed to save settings:', error);
        this.$toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to save settings. Please try again.',
          life: 5000
        });
      } finally {
        this.savingSettings = false;
      }
    },

    loadSettings() {
      try {
        const saved = localStorage.getItem(`stock_settings_${this.serviceId}`);
        if (saved) {
          this.settings = { ...this.settings, ...JSON.parse(saved) };
        }
      } catch (error) {
        console.error('Failed to load settings:', error);
        // Use default settings if loading fails
      }
    },

    getProductSettings(productGroup) {
      if (!productGroup) return this.getDefaultProductSettings({});
      const productName = productGroup.productName;
      const productForme = productGroup.forme !== 'N/A' ? productGroup.forme : 'N/A';
      const key = `${productName}::${productForme}`;

      if (this.productSettingsCache[key]) return this.productSettingsCache[key];

      // Return defaults immediately - settings will be loaded on-demand when modal is opened
      return this.getDefaultProductSettings(productGroup);
    },
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

.tw-animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

/* Enhanced styling similar to other pages */
.p-card {
  border-radius: 0.75rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.p-datatable .p-datatable-tbody > tr:hover {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(147, 197, 253, 0.05) 100%) !important;
  transform: scale(1.01);
  transition: all 0.2s ease;
}

/* Custom modal styling */
.settings-modal .p-dialog-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px 12px 0 0;
  padding: 1.5rem;
}

.settings-modal .p-dialog-header .p-dialog-title {
  font-size: 1.25rem;
  font-weight: 600;
}

.settings-modal .p-dialog-content {
  padding: 0;
  border-radius: 0 0 12px 12px;
}

.settings-modal .p-dialog-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0 0 12px 12px;
}

/* Product Details Modal */
.product-details-modal .p-dialog-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px 12px 0 0;
  padding: 1.5rem;
}

.product-details-modal .p-dialog-header .p-dialog-title {
  font-size: 1.25rem;
  font-weight: 600;
}

.product-details-modal .p-dialog-content {
  padding: 0;
  border-radius: 0 0 12px 12px;
}

.product-details-modal .p-dialog-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0 0 12px 12px;
}

/* Product Settings Modal */
.product-settings-modal .p-dialog-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px 12px 0 0;
  padding: 1.5rem;
}

.product-settings-modal .p-dialog-header .p-dialog-title {
  font-size: 1.25rem;
  font-weight: 600;
}

.product-settings-modal .p-dialog-content {
  padding: 0;
  border-radius: 0 0 12px 12px;
}

.product-settings-modal .p-dialog-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0 0 12px 12px;
}

/* Section styling */
.section-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.section-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.875rem;
}

/* Form field styling */
.p-inputtext,
.p-inputnumber-input,
.p-dropdown,
.p-calendar {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

.p-inputtext:focus,
.p-inputnumber-input:focus,
.p-dropdown:focus,
.p-calendar:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button styling */
.p-button {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.p-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Tag styling */
.p-tag {
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Checkbox styling */
input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  border-radius: 4px;
  border: 2px solid #d1d5db;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

input[type="checkbox"]:checked {
  background: #10b981;
  border-color: #10b981;
}

input[type="checkbox"]:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

/* Responsive design */
@media (max-width: 768px) {
  .settings-modal,
  .product-details-modal,
  .product-settings-modal {
    width: 95vw !important;
    max-width: none !important;
  }

  .settings-modal .p-dialog-header,
  .product-details-modal .p-dialog-header,
  .product-settings-modal .p-dialog-header {
    padding: 1rem;
  }

  .settings-modal .p-dialog-content,
  .product-details-modal .p-dialog-content,
  .product-settings-modal .p-dialog-content {
    padding: 1rem;
  }

  .settings-modal .p-dialog-footer,
  .product-details-modal .p-dialog-footer,
  .product-settings-modal .p-dialog-footer {
    padding: 1rem;
  }
}
</style>
