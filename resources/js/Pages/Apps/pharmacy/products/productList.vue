<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-white tw-to-indigo-50 tw-p-6">
    <!-- Success Toast -->
    <Toast position="top-right" />

    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Header Section -->
    <div class="tw-mb-8">
      <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-rounded-2xl tw-shadow-xl tw-p-8 tw-text-white">
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2 tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-box tw-text-5xl"></i>
              Product Management
            </h1>
            <p class="tw-text-blue-100 tw-text-lg">Manage your medical products and inventory efficiently</p>
          </div>
          <div class="tw-flex tw-gap-4 tw-items-center">
            <Button
              icon="pi pi-refresh"
              severity="secondary"
              class="tw-rounded-lg tw-p-3 hover:tw-bg-gray-100 tw-transition-all tw-duration-300"
              @click="refreshProducts"
              v-tooltip.top="'Refresh Products'"
              :loading="loading"
            />
            <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-xl tw-p-4 tw-text-center">
              <div class="tw-text-2xl tw-font-bold">{{ total }}</div>
              <div class="tw-text-sm tw-text-blue-100">Total Products</div>
            </div>
            <Button
              label="Add New Product"
              icon="pi pi-plus"
              class="tw-bg-white tw-text-blue-600 tw-border-white tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-bg-blue-50 tw-transition-all tw-duration-300"
              @click="openAddProductModal"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Actions Section -->
    <div v-if="selectedProducts.length > 0" class="tw-bg-blue-50 tw-rounded-2xl tw-shadow-lg tw-p-4 tw-mb-6 tw-border tw-border-blue-200">
      <div class="tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-check-circle tw-text-blue-600 tw-text-xl"></i>
          <span class="tw-text-blue-800 tw-font-medium">{{ selectedProducts.length }} product(s) selected</span>
        </div>
        <div class="tw-flex tw-gap-2">
          <Button
            label="Select All"
            icon="pi pi-check-square"
            severity="info"
            outlined
            class="tw-rounded-lg tw-px-4 tw-py-2"
            @click="selectAllProducts"
          />
          <Button
            label="Clear Selection"
            icon="pi pi-times"
            severity="secondary"
            outlined
            class="tw-rounded-lg tw-px-4 tw-py-2"
            @click="selectedProducts = []"
          />
          <Button
            label="Bulk Delete"
            icon="pi pi-trash"
            severity="danger"
            class="tw-rounded-lg tw-px-4 tw-py-2"
            @click="confirmBulkDelete"
          />
        </div>
      </div>
    </div>
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-mb-6">
      <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-6 tw-items-start lg:tw-items-center tw-justify-between">
        <div class="tw-flex-1 tw-max-w-md">
          <div class="tw-relative">
            <InputText
              v-model="searchQuery"
              placeholder="Search products by name, description, or category..."
              class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-text-sm tw-transition-all focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100"
              @input="onSearchInput"
            />
            <i class="pi pi-search tw-absolute tw-left-4 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400 tw-text-lg"></i>
          </div>
        </div>
        <div class="tw-flex tw-flex-wrap tw-gap-3">
          <Button
            :label="`Clinical Products (${total})`"
            icon="pi pi-pills"
            :class="[
              'tw-rounded-xl tw-px-6 tw-py-3 tw-font-medium tw-transition-all tw-duration-300',
              'tw-bg-red-600 tw-text-white tw-shadow-lg'
            ]"
            disabled
          />
        </div>
        
        <!-- Alert Filters Section -->
        <div class="tw-flex tw-flex-wrap tw-gap-3 tw-items-center tw-p-4 tw-bg-gradient-to-r tw-from-orange-50 tw-to-red-50 tw-rounded-xl tw-border tw-border-orange-200">
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-bell tw-text-orange-600 tw-text-lg"></i>
            <span class="tw-text-sm tw-font-semibold tw-text-gray-700">Filter by Alerts:</span>
          </div>
          
          <Button
            :label="`Low Stock (${lowStockAlertCount})`"
            icon="pi pi-exclamation-triangle"
            severity="warning"
            :outlined="!alertFilters.includes('low_stock')"
            :class="[
              'tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-transition-all tw-duration-300',
              alertFilters.includes('low_stock')
                ? 'tw-bg-orange-500 tw-text-white tw-shadow-md'
                : 'tw-bg-orange-50 tw-text-orange-700 hover:tw-bg-orange-100'
            ]"
            @click="toggleAlertFilter('low_stock')"
          />
          <Button
            :label="`Critical (${criticalAlertCount})`"
            icon="pi pi-times-circle"
            severity="danger"
            :outlined="!alertFilters.includes('critical_stock')"
            :class="[
              'tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-transition-all tw-duration-300',
              alertFilters.includes('critical_stock')
                ? 'tw-bg-red-500 tw-text-white tw-shadow-md'
                : 'tw-bg-red-50 tw-text-red-700 hover:tw-bg-red-100'
            ]"
            @click="toggleAlertFilter('critical_stock')"
          />
          <Button
            :label="`Expiring (${expiringAlertCount})`"
            icon="pi pi-clock"
            severity="warning"
            :outlined="!alertFilters.includes('expiring')"
            :class="[
              'tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-transition-all tw-duration-300',
              alertFilters.includes('expiring')
                ? 'tw-bg-yellow-500 tw-text-white tw-shadow-md'
                : 'tw-bg-yellow-50 tw-text-yellow-700 hover:tw-bg-yellow-100'
            ]"
            @click="toggleAlertFilter('expiring')"
          />
          <Button
            :label="`Expired (${expiredAlertCount})`"
            icon="pi pi-ban"
            severity="danger"
            :outlined="!alertFilters.includes('expired')"
            :class="[
              'tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-transition-all tw-duration-300',
              alertFilters.includes('expired')
                ? 'tw-bg-red-600 tw-text-white tw-shadow-md'
                : 'tw-bg-red-50 tw-text-red-800 hover:tw-bg-red-100'
            ]"
            @click="toggleAlertFilter('expired')"
          />
          
          <div class="tw-ml-auto">
            <Button
              v-if="alertFilters.length > 0"
              label="Clear All Filters"
              icon="pi pi-times"
              severity="secondary"
              outlined
              class="tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-transition-all tw-duration-300 hover:tw-bg-gray-100"
              @click="clearAlertFilters"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-overflow-hidden">
      <DataTable
        :value="filteredProducts"
        :loading="loading"
        :paginator="true"
        :rows="perPage"
        :totalRecords="total"
        :lazy="true"
        @page="onPage"
        v-model:selection="selectedProducts"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25, 50]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
        class="p-datatable-sm tw-rounded-2xl"
        stripedRows
        showGridlines
        responsiveLayout="scroll"
      >
        <template #loading>
          <div class="tw-text-center tw-py-16">
            <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
              <i class="pi pi-spin pi-spinner tw-text-4xl tw-text-blue-500"></i>
              <p class="tw-text-gray-500 tw-text-lg">Loading products...</p>
            </div>
          </div>
        </template>

        <template #empty>
          <div class="tw-text-center tw-py-16">
            <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
              <i class="pi pi-box-open tw-text-6xl tw-text-gray-300"></i>
              <p class="tw-text-gray-500 tw-text-xl tw-font-medium">No products found</p>
              <p class="tw-text-gray-400">Try adjusting your search or filters</p>
            </div>
          </div>
        </template>

        <Column selectionMode="multiple" style="width: 50px" :exportable="false"></Column>

        <Column field="id" header="ID" style="width: 80px" class="tw-font-medium">
          <template #body="slotProps">
            <span class="tw-bg-gray-100 tw-text-gray-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
              {{ slotProps.data.id }}
            </span>
          </template>
        </Column>

        <Column field="name" header="Product Name" style="min-width: 250px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-w-10 tw-h-10 tw-rounded-full tw-flex tw-items-center tw-justify-center" :class="getCategoryColor(slotProps.data.category)">
                <i :class="getCategoryIcon(slotProps.data.category)" class="tw-text-white tw-text-sm"></i>
              </div>
              <div>
                <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.name }}</div>
                <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.description }}</div>
              </div>
            </div>
          </template>
        </Column>

        <Column header="Quantity" style="min-width: 120px" class="tw-text-center">
          <template #body="slotProps">
            <div class="tw-flex tw-flex-col tw-items-center">
              <span class="tw-font-bold tw-text-lg">{{ formatNumber(slotProps.data.total_quantity || 0) }}</span>
              <span class="tw-text-xs tw-text-gray-500">Low: {{ slotProps.data.global_settings?.min_quantity_all_services?.threshold || 10 }}</span>
            </div>
          </template>
        </Column>

        <Column header="Category" style="min-width: 150px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i :class="[
                'pi',
                getCategoryIcon(slotProps.data.category),
                getCategorySeverity(slotProps.data.category) === 'danger' ? 'tw-text-red-500' :
                getCategorySeverity(slotProps.data.category) === 'success' ? 'tw-text-green-500' :
                getCategorySeverity(slotProps.data.category) === 'info' ? 'tw-text-blue-500' : 'tw-text-yellow-500'
              ]" aria-hidden="true"></i>
              <span :class="[
                'tw-font-medium',
                getCategorySeverity(slotProps.data.category) === 'danger' ? 'tw-text-red-600' :
                getCategorySeverity(slotProps.data.category) === 'success' ? 'tw-text-green-600' :
                getCategorySeverity(slotProps.data.category) === 'info' ? 'tw-text-blue-600' : 'tw-text-yellow-600'
              ]">
                {{ slotProps.data.category }}
              </span>
            </div>
          </template>
        </Column>

        <Column header="Is Clinical" style="min-width: 120px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2 tw-justify-center">
              <i :class="[
                'pi',
                slotProps.data.is_clinical ? 'pi-heart-fill tw-text-red-500' : 'pi-heart tw-text-gray-400',
                'tw-text-lg'
              ]" :title="slotProps.data.is_clinical ? 'Clinical Product' : 'Non-Clinical Product'"></i>
              <span :class="[
                'tw-font-medium',
                slotProps.data.is_clinical ? 'tw-text-red-600' : 'tw-text-gray-500'
              ]">
                {{ slotProps.data.is_clinical ? 'Yes' : 'No' }}
              </span>
            </div>
          </template>
        </Column>

        <Column header="Status" style="min-width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-flex-wrap tw-gap-1">
              <Tag
                v-for="alert in slotProps.data.alerts || []"
                :key="alert.type"
                :value="alert.message"
                :severity="alert.severity"
                :icon="alert.icon"
                class="tw-font-semibold tw-rounded-full tw-px-2 tw-py-1 tw-text-xs"
                style="margin: 0 2px 2px 0;"
              />
              <div v-if="!slotProps.data.alerts || slotProps.data.alerts.length === 0" class="tw-text-gray-500 tw-text-sm">
                No alerts
              </div>
            </div>
          </template>
        </Column>

        <Column header="Actions" style="min-width: 250px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button
                icon="pi pi-eye"
                severity="info"
                outlined
                class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-blue-50 tw-transition-all tw-duration-200"
                @click="viewProduct(slotProps.data)"
                v-tooltip.top="'View Details'"
              />
              <Button
                icon="pi pi-chart-bar"
                severity="success"
                outlined
                class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-green-50 tw-transition-all tw-duration-200"
                @click="viewStockDetails(slotProps.data)"
                v-tooltip.top="'Stock Details'"
              />
              <Button
                icon="pi pi-pencil"
                severity="warning"
                outlined
                class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-yellow-50 tw-transition-all tw-duration-200"
                @click="editProduct(slotProps.data)"
                v-tooltip.top="'Edit Product'"
              />
              <Button
                icon="pi pi-copy"
                severity="help"
                outlined
                class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-purple-50 tw-transition-all tw-duration-200"
                @click="duplicateProduct(slotProps.data)"
                v-tooltip.top="'Duplicate Product'"
              />
              <Button
                icon="pi pi-trash"
                severity="danger"
                outlined
                class="tw-rounded-lg tw-p-2 tw-border-2 hover:tw-bg-red-50 tw-transition-all tw-duration-200"
                @click="confirmDelete(slotProps.data)"
                v-tooltip.top="'Delete Product'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Product Details Dialog -->
    <Dialog
      v-model:visible="selectedProduct"
      modal
      :header="selectedProduct ? selectedProduct.name : 'Product Details'"
      :style="{ width: '75rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <div v-if="selectedProduct" class="tw-p-6">
        <!-- Product Header with Quick Actions -->
        <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-rounded-2xl tw-p-6 tw-mb-6 tw-text-white">
          <div class="tw-flex tw-justify-between tw-items-start">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-bg-white/20 tw-backdrop-blur-sm">
                <i :class="getCategoryIcon(selectedProduct.category)" class="tw-text-3xl"></i>
              </div>
              <div>
                <h2 class="tw-text-2xl tw-font-bold tw-mb-1">{{ selectedProduct.name }}</h2>
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Tag :value="selectedProduct.category" :severity="getCategorySeverity(selectedProduct.category)" class="tw-rounded-full tw-px-3 tw-py-1" />
                  <div v-if="selectedProduct.is_clinical" class="tw-flex tw-items-center tw-gap-1 tw-bg-red-500/20 tw-rounded-full tw-px-3 tw-py-1">
                    <i class="pi pi-heart tw-text-sm"></i>
                    <span class="tw-text-sm tw-font-medium">Medication</span>
                  </div>
                  <!-- Multiple Status Alerts -->
                  <div class="tw-flex tw-flex-wrap tw-gap-1">
                    <Tag
                      v-for="alert in selectedProduct.alerts || []"
                      :key="alert.type"
                      :value="alert.type"
                      :severity="alert.severity"
                      :icon="alert.icon"
                      class="tw-rounded-full tw-px-2 tw-py-1 tw-text-xs"
                    />
                  </div>
                </div>
              </div>
            </div>
            <div class="tw-flex tw-gap-2">
              <Button
                icon="pi pi-pencil"
                severity="warning"
                class="tw-bg-yellow-500 tw-border-yellow-500 tw-rounded-xl tw-p-3 hover:tw-bg-yellow-600 tw-transition-all tw-duration-300"
                @click="editProduct(selectedProduct)"
                v-tooltip.top="'Edit Product'"
              />
              <Button
                icon="pi pi-trash"
                severity="danger"
                class="tw-bg-red-500 tw-border-red-500 tw-rounded-xl tw-p-3 hover:tw-bg-red-600 tw-transition-all tw-duration-300"
                @click="confirmDelete(selectedProduct)"
                v-tooltip.top="'Delete Product'"
              />
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6">
          <!-- Left Column - Basic Info & Description -->
          <div class="lg:tw-col-span-2 tw-space-y-6">
            <!-- Description Card -->
            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-6 tw-border tw-border-green-100">
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-file tw-text-green-600"></i>
                Description
              </h3>
              <p class="tw-text-gray-700 tw-leading-relaxed tw-text-lg">{{ selectedProduct.description }}</p>
            </div>

            <!-- Basic Information Card -->
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6 tw-border tw-border-blue-100">
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-600"></i>
                Basic Information
              </h3>
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div class="tw-bg-white/60 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200">
                  <div class="tw-text-sm tw-text-blue-600 tw-font-medium tw-mb-1">Product ID</div>
                  <div class="tw-text-lg tw-font-bold tw-text-gray-800">#{{ selectedProduct.id }}</div>
                </div>
                <div class="tw-bg-white/60 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200">
                  <div class="tw-text-sm tw-text-blue-600 tw-font-medium tw-mb-1">Category</div>
                  <div class="tw-text-lg tw-font-bold tw-text-gray-800">{{ selectedProduct.category }}</div>
                </div>
                <div class="tw-bg-white/60 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200">
                  <div class="tw-text-sm tw-text-blue-600 tw-font-medium tw-mb-1">Forme</div>
                  <div class="tw-text-lg tw-font-bold tw-text-gray-800">{{ selectedProduct.forme || 'Not specified' }}</div>
                </div>
                <div class="tw-bg-white/60 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200">
                  <div class="tw-text-sm tw-text-blue-600 tw-font-medium tw-mb-1">Boîte de</div>
                  <div class="tw-text-lg tw-font-bold tw-text-gray-800">{{ selectedProduct.boite_de || 'Not specified' }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Status & Quick Stats -->
          <div class="tw-space-y-6">
            <!-- Status Overview Card -->
            <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-pink-50 tw-rounded-xl tw-p-6 tw-border tw-border-purple-100">
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-chart-line tw-text-purple-600"></i>
                Status Overview
              </h3>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-gray-600">Status Alerts</span>
                  <div class="tw-flex tw-flex-wrap tw-gap-1 tw-justify-end">
                    <Tag
                      v-for="alert in selectedProduct.alerts || []"
                      :key="alert.type"
                      :value="alert.type"
                      :severity="alert.severity"
                      :icon="alert.icon"
                      class="tw-rounded-full tw-px-2 tw-py-1 tw-text-xs"
                    />
                    <div v-if="!selectedProduct.alerts || selectedProduct.alerts.length === 0" class="tw-text-xs tw-text-gray-500">
                      No alerts
                    </div>
                  </div>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-gray-600">Type</span>
                  <div class="tw-flex tw-items-center tw-gap-1">
                    <i :class="selectedProduct.is_clinical ? 'pi pi-heart tw-text-red-500' : 'pi pi-box tw-text-green-500'"></i>
                    <span class="tw-font-medium">{{ selectedProduct.is_clinical ? 'Clinical' : 'Supply' }}</span>
                  </div>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-gray-600">Category</span>
                  <div class="tw-flex tw-items-center tw-gap-1">
                    <i :class="`${getCategoryIcon(selectedProduct.category)} ${getCategoryColor(selectedProduct.category)} tw-text-white tw-rounded-full tw-w-5 tw-h-5 tw-flex tw-items-center tw-justify-center tw-text-xs`"></i>
                    <span class="tw-font-medium">{{ selectedProduct.category }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="tw-bg-gradient-to-br tw-from-orange-50 tw-to-yellow-50 tw-rounded-xl tw-p-6 tw-border tw-border-orange-100">
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-bolt tw-text-orange-600"></i>
                Quick Actions
              </h3>
              <div class="tw-space-y-3">
                <Button
                  label="Edit Product"
                  icon="pi pi-pencil"
                  class="tw-w-full tw-bg-orange-500 tw-border-orange-500 tw-rounded-lg hover:tw-bg-orange-600 tw-transition-all tw-duration-300"
                  @click="editProduct(selectedProduct)"
                />
                <Button
                  label="Duplicate Product"
                  icon="pi pi-copy"
                  severity="info"
                  outlined
                  class="tw-w-full tw-rounded-lg tw-border-2 hover:tw-bg-blue-50 tw-transition-all tw-duration-300"
                  @click="duplicateProduct(selectedProduct)"
                />
                <Button
                  label="View History"
                  icon="pi pi-history"
                  severity="secondary"
                  outlined
                  class="tw-w-full tw-rounded-lg tw-border-2 hover:tw-bg-gray-50 tw-transition-all tw-duration-300"
                  @click="viewProductHistory(selectedProduct)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Stock Information Section -->
        <div v-if="productStats" class="tw-mt-6 tw-bg-gradient-to-br tw-from-indigo-50 tw-to-purple-50 tw-rounded-xl tw-p-6 tw-border tw-border-indigo-100">
          <h3 class="tw-text-xl tw-font-bold tw-text-indigo-700 tw-mb-6 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-chart-bar tw-text-indigo-600"></i>
            Stock Information
          </h3>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-6">
            <div class="tw-bg-white/70 tw-rounded-lg tw-p-4 tw-border tw-border-indigo-200 tw-text-center">
              <div class="tw-text-sm tw-text-indigo-600 tw-font-medium tw-mb-2">Total Quantity</div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-800">{{ productStats.total_quantity }}</div>
              <div v-if="productStats.is_low_stock" class="tw-text-xs tw-text-red-600 tw-mt-1">Low Stock Alert</div>
            </div>
            <div class="tw-bg-white/70 tw-rounded-lg tw-p-4 tw-border tw-border-indigo-200 tw-text-center">
              <div class="tw-text-sm tw-text-indigo-600 tw-font-medium tw-mb-2">Locations Count</div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-800">{{ productStats.locations_count }}</div>
            </div>
            <div class="tw-bg-white/70 tw-rounded-lg tw-p-4 tw-border tw-border-indigo-200 tw-text-center">
              <div class="tw-text-sm tw-text-indigo-600 tw-font-medium tw-mb-2">Low Stock Threshold</div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-800">{{ productStats.low_stock_threshold }}</div>
            </div>
          </div>
          
          <!-- Locations Table -->
          <div v-if="productStats.locations.length > 0" class="tw-mt-6">
            <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4">Stock Locations</h4>
            <div class="tw-bg-white tw-rounded-lg tw-overflow-hidden tw-shadow-sm">
              <table class="tw-w-full">
                <thead class="tw-bg-indigo-100">
                  <tr>
                    <th class="tw-px-4 tw-py-3 tw-text-left tw-text-sm tw-font-medium tw-text-indigo-700">Location</th>
                    <th class="tw-px-4 tw-py-3 tw-text-left tw-text-sm tw-font-medium tw-text-indigo-700">Quantity</th>
                    <th class="tw-px-4 tw-py-3 tw-text-left tw-text-sm tw-font-medium tw-text-indigo-700">Batch Number</th>
                    <th class="tw-px-4 tw-py-3 tw-text-left tw-text-sm tw-font-medium tw-text-indigo-700">Expiry Date</th>
                    <th class="tw-px-4 tw-py-3 tw-text-left tw-text-sm tw-font-medium tw-text-indigo-700">Unit</th>
                  </tr>
                </thead>
                <tbody class="tw-divide-y tw-divide-gray-200">
                  <tr v-for="location in productStats.locations" :key="location.location" class="hover:tw-bg-gray-50">
                    <td class="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">{{ location.location }}</td>
                    <td class="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">{{ location.quantity }}</td>
                    <td class="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">{{ location.batch_number || 'N/A' }}</td>
                    <td class="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">{{ location.expiry_date ? new Date(location.expiry_date).toLocaleDateString() : 'N/A' }}</td>
                    <td class="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-900">{{ location.unit || 'N/A' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
            <i class="pi pi-info-circle tw-text-3xl tw-mb-2"></i>
            <p>No stock locations found for this product.</p>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-8 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            label="Print Details"
            icon="pi pi-print"
            severity="info"
            outlined
            class="tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium"
            @click="printProductDetails"
          />
          <Button
            label="Close"
            severity="secondary"
            class="tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium"
            @click="closeModal"
          />
          <Button
            label="Edit Product"
            icon="pi pi-pencil"
            class="tw-bg-blue-600 tw-border-blue-600 tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium tw-shadow-lg hover:tw-bg-blue-700 tw-transition-all tw-duration-300"
            @click="editProduct(selectedProduct)"
          />
        </div>
      </div>
    </Dialog>

    <!-- Add Product Dialog -->
    <Dialog
      v-model:visible="showAddProductModal"
      modal
      header="Add New Product"
      :style="{ width: '70rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="addProduct" class="tw-p-6">
        <div class="tw-space-y-6">
          <!-- Basic Information -->
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6">
            <h4 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-info-circle tw-text-blue-600"></i>
              Basic Information
            </h4>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                  Product Name {{ newProduct.category === 'Medication' ? '(Auto-filled from Nom Commercial)' : '*' }}
                </label>
                <InputText
                  v-model="newProduct.name"
                  :readonly="newProduct.category === 'Medication'"
                  :class="[
                    'tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100',
                    newProduct.category === 'Medication' ? 'tw-bg-gray-100 tw-cursor-not-allowed' : ''
                  ]"
                  :required="newProduct.category !== 'Medication'"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Category *</label>
                <Dropdown
                  v-model="newProduct.category"
                  :options="categoryOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select Category"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-blue-500"
                  @change="handleCategoryChange"
                  required
                />
              </div>
              <div class="tw-col-span-full">
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Description *</label>
                <Textarea
                  v-model="newProduct.description"
                  rows="3"
                  class="tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Additional Details -->
          <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-6">
            <h4 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-cog tw-text-green-600"></i>
              Additional Details
            </h4>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Forme</label>
                <Dropdown
                  v-model="newProduct.forme"
                  :options="formeOptions"
                  placeholder="Select Form"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-green-500"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Boîte de</label>
                <InputNumber
                  v-model="newProduct.boite_de"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-green-500"
                  placeholder="Quantity per box/unit"
                  min="1"
                />
              </div>
              <div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Checkbox v-model="newProduct.is_clinical" inputId="is_clinical" :binary="true" />
                  <label for="is_clinical" class="tw-text-sm tw-font-semibold tw-text-gray-700">Is Clinical</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Medication Specific Fields -->
          <div v-if="newProduct.category === 'Medication'" class="tw-bg-gradient-to-r tw-from-red-50 tw-to-pink-50 tw-rounded-xl tw-p-6">
            <h4 class="tw-text-xl tw-font-bold tw-text-red-700 tw-mb-4 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-heart tw-text-red-600"></i>
              Medication Details
            </h4>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Code PCH</label>
                <InputText
                  v-model="newProduct.code_pch"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Designation *</label>
                <InputText
                  v-model="newProduct.designation"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                  required
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Nom Commercial</label>
                <InputText
                  v-model="newProduct.nom_commercial"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                  @input="syncProductName"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Type de Médicament *</label>
                <Dropdown
                  v-model="newProduct.type_medicament"
                  :options="medicationTypeOptions"
                  placeholder="Select Type"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                  required
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-8 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            label="Cancel"
            severity="secondary"
            class="tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium"
            @click="closeAddProductModal"
            :disabled="isSubmitting"
          />
          <Button
            type="submit"
            label="Add Product"
            class="tw-bg-blue-600 tw-border-blue-600 tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium tw-shadow-lg hover:tw-bg-blue-700 tw-transition-all tw-duration-300"
            :loading="isSubmitting"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </Dialog>

    <!-- Edit Product Dialog -->
    <Dialog
      v-model:visible="showEditProductModal"
      modal
      header="Edit Product"
      :style="{ width: '70rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="updateProduct" class="tw-p-6">
        <div class="tw-space-y-6">
          <!-- Basic Information -->
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6">
            <h4 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-info-circle tw-text-blue-600"></i>
              Basic Information
            </h4>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                  Product Name {{ editingProduct?.category === 'Medication' ? '(Auto-filled from Nom Commercial)' : '*' }}
                </label>
                <InputText
                  v-model="editingProduct.name"
                  :readonly="editingProduct?.category === 'Medication'"
                  :class="[
                    'tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100',
                    editingProduct?.category === 'Medication' ? 'tw-bg-gray-100 tw-cursor-not-allowed' : ''
                  ]"
                  :required="editingProduct?.category !== 'Medication'"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Category *</label>
                <Dropdown
                  v-model="editingProduct.category"
                  :options="categoryOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select Category"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-blue-500"
                  @change="handleEditCategoryChange"
                  required
                />
              </div>
              <div class="tw-col-span-full">
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Description *</label>
                <Textarea
                  v-model="editingProduct.description"
                  rows="3"
                  class="tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Additional Details -->
          <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-6">
            <h4 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-cog tw-text-green-600"></i>
              Additional Details
            </h4>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Forme</label>
                <Dropdown
                  v-model="editingProduct.forme"
                  :options="formeOptions"
                  placeholder="Select Form"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-green-500"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Boîte de</label>
                <InputNumber
                  v-model="editingProduct.boite_de"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-green-500"
                  placeholder="Quantity per box/unit"
                  min="1"
                />
              </div>
              <div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Checkbox v-model="editingProduct.is_clinical" inputId="edit_is_clinical" :binary="true" />
                  <label for="edit_is_clinical" class="tw-text-sm tw-font-semibold tw-text-gray-700">Is Clinical</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Medication Specific Fields -->
          <div v-if="editingProduct?.category === 'Medication'" class="tw-bg-gradient-to-r tw-from-red-50 tw-to-pink-50 tw-rounded-xl tw-p-6">
            <h4 class="tw-text-xl tw-font-bold tw-text-red-700 tw-mb-4 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-heart tw-text-red-600"></i>
              Medication Details
            </h4>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Code PCH</label>
                <InputText
                  v-model="editingProduct.code_pch"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Designation *</label>
                <InputText
                  v-model="editingProduct.designation"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                  required
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Nom Commercial</label>
                <InputText
                  v-model="editingProduct.nom_commercial"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                  @input="syncEditProductName"
                />
              </div>
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Type de Médicament *</label>
                <Dropdown
                  v-model="editingProduct.type_medicament"
                  :options="medicationTypeOptions"
                  placeholder="Select Type"
                  class="tw-w-full tw-rounded-lg tw-border-2 focus:tw-border-red-500"
                  required
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-8 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            label="Cancel"
            severity="secondary"
            class="tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium"
            @click="closeEditProductModal"
            :disabled="isSubmitting"
          />
          <Button
            type="submit"
            label="Update Product"
            class="tw-bg-blue-600 tw-border-blue-600 tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium tw-shadow-lg hover:tw-bg-blue-700 tw-transition-all tw-duration-300"
            :loading="isSubmitting"
            :disabled="isSubmitting"
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
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Checkbox from 'primevue/checkbox';

export default {
  name: 'ProductList',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    Textarea,
    Dropdown,
    InputNumber,
    Tag,
    Toast,
    ConfirmDialog,
    Checkbox
  },
  data() {
    return {
      searchQuery: '',
      selectedProduct: null,
      productStats: null,
      selectedProducts: [],
      showAddProductModal: false,
      showEditProductModal: false,
      alertFilters: [], // Array of selected alert types: 'low_stock', 'critical_stock', 'expiring', 'expired'
      editingProduct: null,
      isSubmitting: false,
      products: [],
      filteredProducts: [],
      loading: false,
      error: null,
      searchTimeout: null,
      // Pagination
      currentPage: 1,
      lastPage: 1,
      perPage: 10,
      total: 0,
      // Alert counts from API
      alertCounts: {
        low_stock: 0,
        critical_stock: 0,
        expiring: 0,
        expired: 0
      },
      newProduct: {
        name: '',
        description: '',
        category: '',
        is_clinical: false,
        // Medication specific fields
        code_pch: '',
        designation: '',
        type_medicament: '',
        forme: '',
        boite_de: null,
        nom_commercial: ''
      },
      categoryOptions: [
        { label: 'Medical Supplies', value: 'Medical Supplies' },
        { label: 'Equipment', value: 'Equipment' },
        { label: 'Medication', value: 'Medication' },
        { label: 'Others', value: 'Others' }
      ],
      medicationTypeOptions: [
        'ANAPATHE', 'ANTISEPTIQUE', 'CATHETERISME', 'CHIMIQUE', 'CHIRURGIE GÉNÉRALE',
        'CONSOMMABLE', 'FIBROSCOPIE', 'FROID', 'INSTRUMENT', 'LABORATOIRE',
        'LIGATURE', 'MÉDICAMENT', 'OSTÉO-SYNTHÈSE', 'PSYCHOTROPE 1', 'PSYCHOTROPE 2',
        'RADIOLOGIE', 'SOLUTÉ GRAND VOLUME', 'STÉRILISATION', 'STUPÉFIANT'
      ],
      formeOptions: [
        'AMPOULE', 'COMPRIME', 'FLACON', 'GELULE', 'LITRE', 'SACHET', 'TUBE', 'UNITE'
      ]
    }
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.fetchProducts();
  },
  computed: {
    lowStockAlertCount() {
      return this.alertCounts.low_stock || 0;
    },
    criticalAlertCount() {
      return this.alertCounts.critical_stock || 0;
    },
    expiringAlertCount() {
      return this.alertCounts.expiring || 0;
    },
    expiredAlertCount() {
      return this.alertCounts.expired || 0;
    }
  },
  methods: {
    async fetchProducts(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const params = {
          page: page,
          per_page: this.perPage
        };

        // Add search if present
        if (this.searchQuery.trim()) {
          params.search = this.searchQuery;
        }

        // Add alert filters if present
        if (this.alertFilters.length > 0) {
          params.alert_filters = this.alertFilters;
        }
        params.is_clinical =true;

        const response = await axios.get('/api/pharmacy/products', { params });
        if (response.data.success) {
          this.products = response.data.data;
          this.currentPage = response.data.meta.current_page;
          this.lastPage = response.data.meta.last_page;
          this.total = response.data.meta.total;
          // Store alert counts from API
          if (response.data.alert_counts) {
            this.alertCounts = response.data.alert_counts;
          }
          this.filterProducts();
        }
      } catch (error) {
        this.error = 'Failed to load products';
        console.error('Error fetching products:', error);
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

    onSearchInput() {
      // Debounce search to avoid too many API calls
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page when searching
        this.fetchProducts(1);
      }, 300);
    },

    onPage(event) {
      this.currentPage = event.page + 1;
      this.fetchProducts(this.currentPage);
    },

    toggleAlertFilter(alertType) {
      const index = this.alertFilters.indexOf(alertType);
      if (index > -1) {
        this.alertFilters.splice(index, 1);
      } else {
        this.alertFilters.push(alertType);
      }
      // Reset to first page when applying filters
      this.currentPage = 1;
      this.fetchProducts(1);
    },
    clearAlertFilters() {
      this.alertFilters = [];
      // Reset to first page when clearing filters
      this.currentPage = 1;
      this.fetchProducts(1);
    },
    filterProducts() {
      // Since backend now filters for is_clinical = true, we don't need tab-based filtering
      let filtered = [...this.products];

      // Note: Alert filtering is now handled server-side

      // Filter by search query
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(product =>
          product.name.toLowerCase().includes(query) ||
          product.description.toLowerCase().includes(query) ||
          product.category.toLowerCase().includes(query)
        );
      }

      this.filteredProducts = filtered;
    },
    viewProduct(product) {
      // Fetch detailed product information including stock stats
      this.fetchProductDetails(product.id);
    },
    viewStockDetails(product) {
      // Navigate to the full stock details page
      this.$router.push({ name: 'pharmacy.products.details', params: { id: product.id } });
    },
    async fetchProductDetails(productId) {
      try {
        const response = await axios.get(`/api/pharmacy/products/${productId}/details`);
        if (response.data.success) {
          this.selectedProduct = response.data.product;
          this.productStats = response.data.stats;
        }
      } catch (error) {
        console.error('Error fetching product details:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load product details',
          life: 3000
        });
      }
    },
    async refreshProducts() {
      await this.fetchProducts(this.currentPage);
      this.toast.add({
        severity: 'success',
        summary: 'Refreshed',
        detail: 'Product list has been refreshed',
        life: 2000
      });
    },
    editProduct(product) {
      this.editingProduct = { ...product };
      // Sync product name for medication products
      if (this.editingProduct.category === 'Medication') {
        this.syncEditProductName();
      }
      this.showEditProductModal = true;
    },
    confirmDelete(product) {
      this.confirm.require({
        message: `Are you sure you want to delete "${product.name}"? This action cannot be undone.`,
        header: 'Delete Product',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () => {
          this.deleteProduct(product);
        }
      });
    },
    async deleteProduct(product) {
      try {
        const response = await axios.delete(`/api/pharmacy/products/${product.id}`);
        if (response.data.success) {
          // Refresh the current page to get updated data
          await this.fetchProducts(this.currentPage);
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Product deleted successfully',
            life: 3000
          });
        }
      } catch (error) {
        console.error('Error deleting product:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete product',
          life: 3000
        });
      }
    },
    closeModal() {
      this.selectedProduct = null;
      this.productStats = null;
    },
    openAddProductModal() {
      this.showAddProductModal = true;
      this.resetNewProduct();
    },
    closeAddProductModal() {
      this.showAddProductModal = false;
      this.resetNewProduct();
    },
    closeEditProductModal() {
      this.showEditProductModal = false;
      this.editingProduct = null;
    },
    resetNewProduct() {
      this.newProduct = {
        name: '',
        description: '',
        category: '',
        is_clinical: false,
        code_pch: '',
        designation: '',
        type_medicament: '',
        forme: '',
        boite_de: null,
        nom_commercial: ''
      };
    },
    handleCategoryChange() {
      // Reset medication fields when category changes
      if (this.newProduct.category !== 'Medication') {
        this.newProduct.code_pch = '';
        this.newProduct.designation = '';
        this.newProduct.type_medicament = '';
        this.newProduct.forme = '';
        this.newProduct.boite_de = null;
        this.newProduct.nom_commercial = '';
        // Clear product name if it was auto-filled
        if (this.newProduct.name) {
          this.newProduct.name = '';
        }
      }
      // Note: is_clinical is no longer automatically set based on category
    },
    syncProductName() {
      // For medication products, sync product name with nom commercial
      if (this.newProduct.category === 'Medication') {
        this.newProduct.name = this.newProduct.nom_commercial;
      }
    },
    async addProduct() {
      this.isSubmitting = true;

      try {
        const response = await axios.post('/api/pharmacy/products', this.newProduct);
        if (response.data.success) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Product added successfully',
            life: 3000
          });
          // Refresh the current page to get updated data
          await this.fetchProducts(this.currentPage);
          this.closeAddProductModal();
        }
      } catch (error) {
        console.error('Error adding product:', error);
        if (error.response && error.response.data.errors) {
          const errors = Object.values(error.response.data.errors).flat().join(', ');
          this.toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: errors,
            life: 5000
          });
        } else {
          this.toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to add product',
            life: 3000
          });
        }
      } finally {
        this.isSubmitting = false;
      }
    },
    handleEditCategoryChange() {
      // Reset medication fields when category changes
      if (this.editingProduct.category !== 'Medication') {
        this.editingProduct.code_pch = '';
        this.editingProduct.designation = '';
        this.editingProduct.type_medicament = '';
        this.editingProduct.forme = '';
        this.editingProduct.boite_de = null;
        this.editingProduct.nom_commercial = '';
        // Clear product name if it was auto-filled
        if (this.editingProduct.name) {
          this.editingProduct.name = '';
        }
      } else {
        // If switching to medication, sync name with nom commercial
        this.syncEditProductName();
      }
      // Note: is_clinical is no longer automatically set based on category
    },
    syncEditProductName() {
      // For medication products, sync product name with nom commercial
      if (this.editingProduct.category === 'Medication') {
        this.editingProduct.name = this.editingProduct.nom_commercial;
      }
    },
    async updateProduct() {
      this.isSubmitting = true;

      try {
        const response = await axios.put(`/api/pharmacy/products/${this.editingProduct.id}`, this.editingProduct);
        if (response.data.success) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Product updated successfully',
            life: 3000
          });
          // Refresh the current page to get updated data
          await this.fetchProducts(this.currentPage);
          this.closeEditProductModal();
        }
      } catch (error) {
        console.error('Error updating product:', error);
        if (error.response && error.response.data.errors) {
          const errors = Object.values(error.response.data.errors).flat().join(', ');
          this.toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: errors,
            life: 5000
          });
        } else {
          this.toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update product',
            life: 3000
          });
        }
      } finally {
        this.isSubmitting = false;
      }
    },

    getCategorySeverity(category) {
      const severities = {
        'Medical Supplies': 'success',
        'Equipment': 'info',
        'Medication': 'danger',
        'Others': 'warning'
      };
      return severities[category] || 'secondary';
    },
    getStatusSeverity(status) {
      const severities = {
        'In Stock': 'success',
        'Low Stock': 'warning',
        'Out of Stock': 'danger'
      };
      return severities[status] || 'secondary';
    },
    getCategoryIcon(category) {
      const icons = {
        'Medical Supplies': 'pi pi-box',
        'Equipment': 'pi pi-wrench',
        'Medication': 'pi pi-heart',
        'Others': 'pi pi-ellipsis-h'
      };
      return icons[category] || 'pi pi-box';
    },
    getCategoryColor(category) {
      const colors = {
        'Medical Supplies': 'tw-bg-green-500',
        'Equipment': 'tw-bg-blue-500',
        'Medication': 'tw-bg-red-500',
        'Others': 'tw-bg-yellow-500'
      };
      return colors[category] || 'tw-bg-gray-500';
    },
    duplicateProduct(product) {
      // Create a copy of the product for duplication
      const duplicatedProduct = { ...product };
      duplicatedProduct.name = `${product.name} (Copy)`;
      delete duplicatedProduct.id; // Remove ID so it creates a new record

      // Open add modal with duplicated data
      this.newProduct = duplicatedProduct;
      this.showAddProductModal = true;

      this.toast.add({
        severity: 'info',
        summary: 'Product Duplicated',
        detail: 'You can now modify the duplicated product details',
        life: 3000
      });
    },
    selectAllProducts() {
      this.selectedProducts = [...this.filteredProducts];
    },
    confirmBulkDelete() {
      this.confirm.require({
        message: `Are you sure you want to delete ${this.selectedProducts.length} selected product(s)? This action cannot be undone.`,
        header: 'Bulk Delete Products',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () => {
          this.bulkDeleteProducts();
        }
      });
    },
    async bulkDeleteProducts() {
      try {
        const ids = this.selectedProducts.map(p => p.id);
        // Assuming there's a bulk delete endpoint
        const response = await axios.delete('/api/pharmacy/products/bulk-delete', { data: { ids } });
        if (response.data.success) {
          await this.fetchProducts(this.currentPage);
          this.selectedProducts = [];
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${ids.length} products deleted successfully`,
            life: 3000
          });
        }
      } catch (error) {
        console.error('Error bulk deleting products:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete selected products',
          life: 3000
        });
      }
    },
    formatNumber(num) {
      return new Intl.NumberFormat().format(num);
    },
  }
}
</script>

<style scoped>
/* Custom styles for PrimeVue components */
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
</style>
