<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50 tw-to-indigo-100">
    <!-- Enhanced Header with Glass Effect -->
    <div class="tw-relative tw-overflow-hidden tw-mb-8">
      <!-- Background Pattern -->
      <div class="tw-absolute tw-inset-0 tw-opacity-5">
        <div class="tw-absolute tw-top-0 tw-left-0 tw-w-72 tw-h-72 tw-bg-purple-500 tw-rounded-full tw-mix-blend-multiply tw-filter tw-blur-xl tw-opacity-70 tw-animate-pulse"></div>
        <div class="tw-absolute tw-top-0 tw-right-0 tw-w-72 tw-h-72 tw-bg-yellow-500 tw-rounded-full tw-mix-blend-multiply tw-filter tw-blur-xl tw-opacity-70 tw-animate-pulse tw-animation-delay-2000"></div>
        <div class="tw-absolute tw--bottom-8 tw-left-20 tw-w-72 tw-h-72 tw-bg-pink-500 tw-rounded-full tw-mix-blend-multiply tw-filter tw-blur-xl tw-opacity-70 tw-animate-pulse tw-animation-delay-4000"></div>
      </div>

      <Card class="tw-border-0 tw-shadow-2xl tw-backdrop-blur tw-bg-white/80 tw-mx-4 lg:tw-mx-6 tw-mt-6">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-via-purple-600 tw-to-pink-600 tw-p-8 tw--m-6 tw-relative tw-overflow-hidden">
            <!-- Decorative Elements -->
            <div class="tw-absolute tw-top-0 tw-right-0 tw-w-64 tw-h-64 tw-bg-white/10 tw-rounded-full tw--translate-y-32 tw-translate-x-32"></div>
            <div class="tw-absolute tw-bottom-0 tw-left-0 tw-w-48 tw-h-48 tw-bg-white/5 tw-rounded-full tw-translate-y-24 tw--translate-x-24"></div>
            
            <div class="tw-relative tw-z-10">
              <div class="tw-flex tw-flex-col lg:tw-flex-row tw-items-start lg:tw-items-center tw-justify-between tw-gap-6">
                <div class="tw-flex tw-items-center tw-gap-6">
                  <div class="tw-relative">
                    <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-br tw-from-yellow-400 tw-to-orange-500 tw-rounded-2xl tw-blur tw-opacity-60"></div>
                    <div class="tw-relative tw-bg-white/20 tw-p-4 tw-rounded-2xl tw-backdrop-blur-sm tw-border tw-border-white/20">
                      <i class="pi pi-dollar tw-text-4xl tw-text-white tw-drop-shadow-lg"></i>
                    </div>
                  </div>
                  <div>
                    <h1 class="tw-text-4xl tw-font-bold tw-text-white tw-mb-2 tw-drop-shadow-lg">Pricing Manager</h1>
                    <p class="tw-text-white/90 tw-text-lg tw-font-medium">Manage product pricing across service groups with advanced analytics</p>
                    <div class="tw-flex tw-items-center tw-gap-4 tw-mt-3">
                      <div class="tw-flex tw-items-center tw-gap-2 tw-text-white/80">
                        <i class="pi pi-chart-line tw-text-sm"></i>
                        <span class="tw-text-sm">Real-time pricing</span>
                      </div>
                      <div class="tw-flex tw-items-center tw-gap-2 tw-text-white/80">
                        <i class="pi pi-shield tw-text-sm"></i>
                        <span class="tw-text-sm">Audit trail</span>
                      </div>
                      <div class="tw-flex tw-items-center tw-gap-2 tw-text-white/80">
                        <i class="pi pi-bolt tw-text-sm"></i>
                        <span class="tw-text-sm">Bulk operations</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="tw-flex tw-flex-wrap tw-gap-3">
                  <Button 
                    @click="showImportDialog = true"
                    icon="pi pi-upload"
                    label="Import CSV"
                    class="p-button-outlined tw-bg-white/10 tw-border-white/30 tw-text-white hover:tw-bg-white/20 tw-backdrop-blur-sm tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105"
                    :disabled="!selectedServiceGroup"
                  />
                  <Button 
                    @click="exportPricing"
                    icon="pi pi-download"
                    label="Export CSV"
                    class="p-button-outlined tw-bg-white/10 tw-border-white/30 tw-text-white hover:tw-bg-white/20 tw-backdrop-blur-sm tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105"
                    :loading="exporting"
                    :disabled="!selectedServiceGroup"
                  />
                  <Button 
                    @click="generateReport"
                    icon="pi pi-file-pdf"
                    label="Generate Report"
                    class="p-button-outlined tw-bg-white/10 tw-border-white/30 tw-text-white hover:tw-bg-white/20 tw-backdrop-blur-sm tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105"
                    :loading="generatingReport"
                    :disabled="!selectedServiceGroup"
                  />
                  <Button 
                    @click="showAddPricingDialog = true"
                    icon="pi pi-plus"
                    label="Add New"
                    class="tw-bg-gradient-to-r tw-from-yellow-400 tw-to-orange-500 tw-border-0 tw-text-gray-800 tw-font-semibold tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105 hover:tw-shadow-xl"
                    :disabled="!selectedServiceGroup"
                  />
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Enhanced Filters with Glass Effect -->
    <div class="tw-px-4 lg:tw-px-6 tw-mb-8">
      <Card class="tw-border-0 tw-shadow-xl tw-backdrop-blur tw-bg-white/70 tw-overflow-hidden">
        <template #content>
          <div class="tw-p-6">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
              <div class="tw-w-1 tw-h-8 tw-bg-gradient-to-b tw-from-indigo-500 tw-to-purple-600 tw-rounded-full"></div>
              <h2 class="tw-text-xl tw-font-bold tw-text-gray-800">Filter & Search</h2>
              <div class="tw-flex-1 tw-h-px tw-bg-gradient-to-r tw-from-gray-200 tw-to-transparent tw-ml-4"></div>
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
              <!-- Service Group Filter -->
              <div class="tw-group">
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-building tw-text-indigo-500"></i>
                  Service Group
                </label>
                <div class="tw-relative">
                  <Dropdown 
                    v-model="selectedServiceGroup"
                    :options="serviceGroups"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Service Group"
                    class="tw-w-full tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
                    @change="loadPricing"
                  />
                  <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-indigo-500/10 tw-to-purple-500/10 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
                </div>
              </div>

              <!-- Product Type Filter -->
              <div class="tw-group">
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-tags tw-text-purple-500"></i>
                  Product Type
                </label>
                <div class="tw-relative">
                  <Dropdown 
                    v-model="isPharmacy"
                    :options="productTypes"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Type"
                    class="tw-w-full tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
                    @change="loadPricing"
                  />
                  <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-purple-500/10 tw-to-pink-500/10 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
                </div>
              </div>

              <!-- Search Filter -->
              <div class="tw-group">
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-search tw-text-blue-500"></i>
                  Search Products
                </label>
                <div class="tw-relative">
                  <InputText 
                    v-model="searchQuery"
                    placeholder="Search by product name..."
                    class="tw-w-full tw-pl-12 tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
                    @input="filterPricing"
                  />
                  <i class="pi pi-search tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400 tw-pointer-events-none"></i>
                  <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-blue-500/10 tw-to-indigo-500/10 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
                </div>
              </div>

              <!-- Quick Actions -->
              <div class="tw-group">
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-bolt tw-text-orange-500"></i>
                  Quick Actions
                </label>
                <div class="tw-flex tw-gap-2">
                  <Button 
                    @click="loadPricing"
                    icon="pi pi-refresh"
                    class="tw-flex-1 p-button-outlined tw-transition-all tw-duration-300 hover:tw-scale-105"
                    :disabled="!selectedServiceGroup"
                    v-tooltip.top="'Refresh Data'"
                  />
                  <Button 
                    @click="clearFilters"
                    icon="pi pi-filter-slash"
                    class="tw-flex-1 p-button-outlined p-button-secondary tw-transition-all tw-duration-300 hover:tw-scale-105"
                    v-tooltip.top="'Clear Filters'"
                  />
                </div>
              </div>
            </div>

            <!-- Filter Summary -->
            <div v-if="selectedServiceGroup || isPharmacy !== null || searchQuery" class="tw-mt-6 tw-pt-4 tw-border-t tw-border-gray-200">
              <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                <span class="tw-text-sm tw-font-medium tw-text-gray-600">Active Filters:</span>
                <Tag v-if="selectedServiceGroup" :value="getServiceGroupName(selectedServiceGroup)" severity="info" class="tw-animate-pulse" />
                <Tag v-if="isPharmacy !== null" :value="isPharmacy ? 'Pharmacy Products' : 'Stock Products'" severity="success" class="tw-animate-pulse" />
                <Tag v-if="searchQuery" :value="`Search: ${searchQuery}`" severity="warning" class="tw-animate-pulse" />
                <Button 
                  @click="clearAllFilters"
                  icon="pi pi-times"
                  label="Clear All"
                  class="p-button-text p-button-sm tw-text-gray-500"
                  v-if="selectedServiceGroup || isPharmacy !== null || searchQuery"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Enhanced Data Table with Modern Design -->
    <div class="tw-px-4 lg:tw-px-6 tw-mb-8">
      <Card class="tw-border-0 tw-shadow-xl tw-backdrop-blur tw-bg-white/70 tw-overflow-hidden">
        <template #content>
          <div class="tw-p-6">
            <!-- Table Header -->
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-start sm:tw-items-center tw-justify-between tw-mb-6 tw-gap-4">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-1 tw-h-8 tw-bg-gradient-to-b tw-from-green-500 tw-to-blue-600 tw-rounded-full"></div>
                <h2 class="tw-text-xl tw-font-bold tw-text-gray-800">Pricing Data</h2>
                <div v-if="filteredPricing.length > 0" class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-semibold">
                  {{ filteredPricing.length }} Products
                </div>
              </div>
              
              <!-- Table Controls -->
              <div class="tw-flex tw-items-center tw-gap-3">
                <Button 
                  icon="pi pi-refresh"
                  class="p-button-outlined p-button-sm"
                  @click="loadPricing"
                  :loading="loading"
                  v-tooltip.top="'Refresh Data'"
                />
                <Button 
                  icon="pi pi-download"
                  class="p-button-outlined p-button-sm"
                  @click="exportPricing"
                  :loading="exporting"
                  v-tooltip.top="'Export to CSV'"
                  :disabled="!selectedServiceGroup"
                />
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="!selectedServiceGroup" class="tw-text-center tw-py-16">
              <div class="tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-w-20 tw-h-20 tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-building tw-text-3xl tw-text-gray-400"></i>
              </div>
              <h3 class="tw-text-lg tw-font-semibold tw-text-gray-600 tw-mb-2">Select a Service Group</h3>
              <p class="tw-text-gray-500">Choose a service group from the filters above to view pricing data</p>
            </div>

            <!-- Loading State -->
            <div v-else-if="loading" class="tw-text-center tw-py-16">
              <div class="tw-inline-flex tw-items-center tw-gap-3 tw-text-gray-600">
                <i class="pi pi-spin pi-spinner tw-text-2xl"></i>
                <span class="tw-text-lg tw-font-medium">Loading pricing data...</span>
              </div>
            </div>

            <!-- Data Table -->
            <div v-else class="tw-overflow-hidden tw-rounded-xl tw-border tw-border-gray-200">
              <DataTable 
                :value="filteredPricing"
                :paginator="true"
                :rows="20"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                class="tw-text-sm"
                stripedRows
                responsiveLayout="scroll"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="{first} to {last} of {totalRecords} entries"
              >
                <!-- Product Name Column -->
                <Column field="product_name" header="Product Name" :sortable="true" class="tw-min-w-48">
                  <template #body="slotProps">
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <div class="tw-w-10 tw-h-10 tw-bg-gradient-to-br tw-from-blue-400 tw-to-indigo-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="pi pi-box tw-text-white tw-text-sm"></i>
                      </div>
                      <div>
                        <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.product_name }}</div>
                        <div class="tw-text-xs tw-text-gray-500">{{ isPharmacy ? 'Pharmacy' : 'Stock' }} Product</div>
                      </div>
                    </div>
                  </template>
                </Column>
                
                <!-- Selling Price Column -->
                <Column field="selling_price" header="Selling Price" :sortable="true">
                  <template #body="slotProps">
                    <div class="tw-text-center">
                      <div class="tw-font-bold tw-text-emerald-600 tw-text-lg">{{ formatCurrency(slotProps.data.selling_price) }}</div>
                      <div class="tw-text-xs tw-text-gray-500">Base Price</div>
                    </div>
                  </template>
                </Column>
                
                <!-- Purchase Price Column -->
                <Column field="purchase_price" header="Purchase Price" :sortable="true">
                  <template #body="slotProps">
                    <div class="tw-text-center">
                      <div class="tw-font-semibold tw-text-blue-600">{{ formatCurrency(slotProps.data.purchase_price) }}</div>
                      <div class="tw-text-xs tw-text-gray-500">Cost</div>
                    </div>
                  </template>
                </Column>
                
                <!-- VAT Rate Column -->
                <Column field="vat_rate" header="VAT Rate" :sortable="true">
                  <template #body="slotProps">
                    <div class="tw-text-center">
                      <Tag 
                        :value="`${slotProps.data.vat_rate}%`" 
                        severity="info" 
                        class="tw-font-semibold"
                      />
                    </div>
                  </template>
                </Column>
                
                <!-- Price with VAT Column -->
                <Column field="selling_price_with_vat" header="Final Price" :sortable="true">
                  <template #body="slotProps">
                    <div class="tw-text-center">
                      <div class="tw-font-bold tw-text-purple-600 tw-text-lg">{{ formatCurrency(slotProps.data.selling_price_with_vat) }}</div>
                      <div class="tw-text-xs tw-text-gray-500">Inc. VAT</div>
                    </div>
                  </template>
                </Column>
                
                <!-- Profit Margin Column -->
                <Column field="profit_margin" header="Margin" :sortable="true">
                  <template #body="slotProps">
                    <div class="tw-text-center">
                      <Tag 
                        :value="`${slotProps.data.profit_margin || 'N/A'}%`" 
                        :severity="getMarginSeverity(slotProps.data.profit_margin)"
                        class="tw-font-semibold"
                      />
                      <div class="tw-text-xs tw-text-gray-500 tw-mt-1">Profit</div>
                    </div>
                  </template>
                </Column>
                
                <!-- Effective Date Column -->
                <Column field="effective_from" header="Effective From" :sortable="true">
                  <template #body="slotProps">
                    <div class="tw-text-center">
                      <div class="tw-text-sm tw-text-gray-700 tw-font-medium">{{ formatDate(slotProps.data.effective_from) }}</div>
                      <div class="tw-text-xs tw-text-gray-500">Start Date</div>
                    </div>
                  </template>
                </Column>
                
                <!-- Updated By Column -->
                <Column field="updated_by_name" header="Updated By">
                  <template #body="slotProps">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-gray-400 tw-to-gray-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                        <i class="pi pi-user tw-text-white tw-text-xs"></i>
                      </div>
                      <div>
                        <div class="tw-text-sm tw-text-gray-700 tw-font-medium">{{ slotProps.data.updated_by_name || 'System' }}</div>
                      </div>
                    </div>
                  </template>
                </Column>
                
                <!-- Actions Column -->
                <Column header="Actions" :frozen="true" alignFrozen="right" class="tw-w-32">
                  <template #body="slotProps">
                    <div class="tw-flex tw-justify-center tw-gap-1">
                      <Button 
                        icon="pi pi-history"
                        class="p-button-rounded p-button-text p-button-info tw-transition-all tw-duration-300 hover:tw-scale-110"
                        size="small"
                        v-tooltip.top="'View History'"
                        @click="viewHistory(slotProps.data)"
                      />
                      <Button 
                        icon="pi pi-pencil"
                        class="p-button-rounded p-button-text p-button-warning tw-transition-all tw-duration-300 hover:tw-scale-110"
                        size="small"
                        v-tooltip.top="'Edit Pricing'"
                        @click="editPricing(slotProps.data)"
                      />
                      <Button 
                        icon="pi pi-trash"
                        class="p-button-rounded p-button-text p-button-danger tw-transition-all tw-duration-300 hover:tw-scale-110"
                        size="small"
                        v-tooltip.top="'Delete Pricing'"
                        @click="confirmDelete(slotProps.data)"
                      />
                    </div>
                  </template>
                </Column>

                <!-- Empty State Template -->
                <template #empty>
                  <div class="tw-text-center tw-py-12">
                    <div class="tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-w-16 tw-h-16 tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                      <i class="pi pi-search tw-text-2xl tw-text-gray-400"></i>
                    </div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-gray-600 tw-mb-2">No pricing data found</h3>
                    <p class="tw-text-gray-500 tw-mb-4">No products match your current filters</p>
                    <Button 
                      @click="showAddPricingDialog = true"
                      icon="pi pi-plus"
                      label="Add First Pricing"
                      class="p-button-outlined"
                      :disabled="!selectedServiceGroup"
                    />
                  </div>
                </template>
              </DataTable>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Enhanced Add/Edit Pricing Dialog -->
    <Dialog 
      v-model:visible="showAddPricingDialog"
      :modal="true"
      :style="{ width: '700px' }"
      class="tw-rounded-2xl"
    >
      <template #header>
        <div class="tw-flex tw-items-center tw-gap-4">
          <div class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-p-3 tw-rounded-xl">
            <i class="pi pi-dollar tw-text-white tw-text-xl"></i>
          </div>
          <div>
            <h3 class="tw-text-xl tw-font-bold tw-text-gray-800">
              {{ editingPricing ? 'Edit Pricing' : 'Add New Pricing' }}
            </h3>
            <p class="tw-text-gray-600 tw-text-sm tw-mt-1">
              {{ editingPricing ? 'Update pricing information' : 'Set pricing for a new product' }}
            </p>
          </div>
        </div>
      </template>

      <div class="tw-space-y-6 tw-pt-4">
        <!-- Product Selection (only for new pricing) -->
        <div v-if="!editingPricing" class="tw-group">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-box tw-text-indigo-500"></i>
            Product Selection *
          </label>
          <div class="tw-relative">
            <Dropdown 
              v-model="pricingForm.product_id"
              :options="availableProducts"
              :optionLabel="product => product.product_name || product.name"
              optionValue="id"
              placeholder="Choose a product to set pricing"
              class="tw-w-full tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
              filter
              filterPlaceholder="Search products..."
            />
            <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-indigo-500/5 tw-to-purple-500/5 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
          </div>
        </div>

        <!-- Pricing Information Grid -->
        <div class="tw-bg-gradient-to-br tw-from-gray-50 tw-to-gray-100 tw-rounded-xl tw-p-6">
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-money-bill tw-text-green-500"></i>
            Pricing Information
          </h4>
          
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <!-- Selling Price -->
            <div class="tw-group">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-tag tw-text-emerald-500"></i>
                Selling Price *
              </label>
              <div class="tw-relative">
                <InputNumber 
                  v-model="pricingForm.selling_price"
                  mode="currency"
                  currency="DZD"
                  locale="fr-DZ"
                  class="tw-w-full tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
                  placeholder="0.00"
                />
                <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-emerald-500/5 tw-to-green-500/5 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
              </div>
            </div>
            
            <!-- Purchase Price -->
            <div class="tw-group">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-shopping-cart tw-text-blue-500"></i>
                Purchase Price
              </label>
              <div class="tw-relative">
                <InputNumber 
                  v-model="pricingForm.purchase_price"
                  mode="currency"
                  currency="DZD"
                  locale="fr-DZ"
                  class="tw-w-full tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
                  placeholder="0.00"
                />
                <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-blue-500/5 tw-to-indigo-500/5 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
              </div>
            </div>
          </div>

          <!-- VAT Rate -->
          <div class="tw-mt-6 tw-group">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-percentage tw-text-purple-500"></i>
              VAT Rate (%)
            </label>
            <div class="tw-relative tw-max-w-xs">
              <InputNumber 
                v-model="pricingForm.vat_rate"
                :min="0"
                :max="100"
                suffix="%"
                class="tw-w-full tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
                placeholder="0"
              />
              <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-purple-500/5 tw-to-pink-500/5 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
            </div>
          </div>
        </div>

        <!-- Profit Calculation Display -->
        <div v-if="pricingForm.selling_price && pricingForm.purchase_price" class="tw-bg-gradient-to-r tw-from-emerald-50 tw-to-green-50 tw-border tw-border-emerald-200 tw-rounded-xl tw-p-4">
          <h5 class="tw-text-sm tw-font-semibold tw-text-emerald-800 tw-mb-2 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-calculator tw-text-emerald-600"></i>
            Profit Analysis
          </h5>
          <div class="tw-grid tw-grid-cols-3 tw-gap-4 tw-text-sm">
            <div>
              <span class="tw-text-gray-600">Profit Margin:</span>
              <span class="tw-font-bold tw-text-emerald-700 tw-ml-2">
                {{ calculateProfitMargin() }}%
              </span>
            </div>
            <div>
              <span class="tw-text-gray-600">Final Price (+ VAT):</span>
              <span class="tw-font-bold tw-text-purple-700 tw-ml-2">
                {{ formatCurrency(calculateFinalPrice()) }}
              </span>
            </div>
            <div>
              <span class="tw-text-gray-600">Profit Amount:</span>
              <span class="tw-font-bold tw-text-blue-700 tw-ml-2">
                {{ formatCurrency(pricingForm.selling_price - pricingForm.purchase_price) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Notes Section -->
        <div class="tw-group">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-comment tw-text-orange-500"></i>
            Notes & Reason for Change
          </label>
          <div class="tw-relative">
            <Textarea 
              v-model="pricingForm.notes"
              rows="3"
              placeholder="Enter reason for price change or additional notes..."
              class="tw-w-full tw-transition-all tw-duration-300 group-hover:tw-shadow-md"
            />
            <div class="tw-absolute tw-inset-0 tw-rounded-lg tw-bg-gradient-to-r tw-from-orange-500/5 tw-to-red-500/5 tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-pointer-events-none"></div>
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            @click="closeAddPricingDialog" 
            class="p-button-text tw-transition-all tw-duration-300 hover:tw-scale-105" 
          />
          <Button 
            :label="editingPricing ? 'Update Pricing' : 'Save Pricing'" 
            icon="pi pi-check" 
            @click="savePricing" 
            :loading="saving"
            class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-border-0 tw-transition-all tw-duration-300 hover:tw-scale-105 hover:tw-shadow-lg"
            :disabled="!pricingForm.product_id || !pricingForm.selling_price"
          />
        </div>
      </template>
    </Dialog>

    <!-- Enhanced Price History Dialog -->
    <Dialog 
      v-model:visible="showHistoryDialog"
      :modal="true"
      :style="{ width: '900px' }"
      class="tw-rounded-2xl"
    >
      <template #header>
        <div class="tw-flex tw-items-center tw-gap-4">
          <div class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl">
            <i class="pi pi-history tw-text-white tw-text-xl"></i>
          </div>
          <div>
            <h3 class="tw-text-xl tw-font-bold tw-text-gray-800">Price History</h3>
            <p class="tw-text-gray-600 tw-text-sm tw-mt-1">
              Track all pricing changes over time
            </p>
          </div>
        </div>
      </template>

      <div class="tw-pt-4">
        <!-- History Summary Cards -->
        <div v-if="priceHistory.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-6">
          <div class="tw-bg-gradient-to-br tw-from-emerald-50 tw-to-green-50 tw-border tw-border-emerald-200 tw-rounded-xl tw-p-4">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-emerald-500 tw-p-2 tw-rounded-lg">
                <i class="pi pi-arrow-up tw-text-white"></i>
              </div>
              <div>
                <p class="tw-text-emerald-800 tw-font-semibold">Current Price</p>
                <p class="tw-text-emerald-600 tw-text-sm">
                  {{ formatCurrency(priceHistory[0]?.selling_price || 0) }}
                </p>
              </div>
            </div>
          </div>
          
          <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-border tw-border-blue-200 tw-rounded-xl tw-p-4">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-blue-500 tw-p-2 tw-rounded-lg">
                <i class="pi pi-calendar tw-text-white"></i>
              </div>
              <div>
                <p class="tw-text-blue-800 tw-font-semibold">Last Updated</p>
                <p class="tw-text-blue-600 tw-text-sm">
                  {{ formatDate(priceHistory[0]?.effective_from) }}
                </p>
              </div>
            </div>
          </div>
          
          <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-pink-50 tw-border tw-border-purple-200 tw-rounded-xl tw-p-4">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-purple-500 tw-p-2 tw-rounded-lg">
                <i class="pi pi-chart-line tw-text-white"></i>
              </div>
              <div>
                <p class="tw-text-purple-800 tw-font-semibold">Total Changes</p>
                <p class="tw-text-purple-600 tw-text-sm">
                  {{ priceHistory.length }} {{ priceHistory.length === 1 ? 'update' : 'updates' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- History Timeline Table -->
        <div class="tw-bg-white tw-rounded-xl tw-border tw-shadow-sm tw-overflow-hidden">
          <DataTable 
            :value="priceHistory" 
            :loading="loadingHistory"
            class="tw-border-0"
            emptyMessage="No pricing history available"
          >
            <template #empty>
              <div class="tw-text-center tw-py-12">
                <div class="tw-bg-gray-100 tw-rounded-full tw-w-16 tw-h-16 tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                  <i class="pi pi-clock tw-text-gray-400 tw-text-2xl"></i>
                </div>
                <p class="tw-text-gray-500 tw-text-lg tw-font-medium">No Price History</p>
                <p class="tw-text-gray-400 tw-text-sm tw-mt-1">No pricing changes have been recorded yet</p>
              </div>
            </template>

            <Column header="Period" class="tw-min-w-48">
              <template #body="{ data, index }">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-text-xs tw-font-bold">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <p class="tw-font-semibold tw-text-gray-800">{{ formatDate(data.effective_from) }}</p>
                    <p class="tw-text-gray-500 tw-text-xs">
                      {{ data.effective_to ? `Until ${formatDate(data.effective_to)}` : 'Current' }}
                    </p>
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Pricing Details" class="tw-min-w-80">
              <template #body="{ data }">
                <div class="tw-space-y-2">
                  <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <span class="tw-text-xs tw-bg-emerald-100 tw-text-emerald-800 tw-px-2 tw-py-1 tw-rounded-full">Selling</span>
                      <span class="tw-font-bold tw-text-emerald-700">{{ formatCurrency(data.selling_price) }}</span>
                    </div>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <span class="tw-text-xs tw-bg-blue-100 tw-text-blue-800 tw-px-2 tw-py-1 tw-rounded-full">Purchase</span>
                      <span class="tw-font-bold tw-text-blue-700">{{ formatCurrency(data.purchase_price) }}</span>
                    </div>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <span class="tw-text-xs tw-bg-purple-100 tw-text-purple-800 tw-px-2 tw-py-1 tw-rounded-full">VAT</span>
                      <span class="tw-font-semibold tw-text-purple-700">{{ data.vat_rate }}%</span>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <span class="tw-text-xs tw-bg-orange-100 tw-text-orange-800 tw-px-2 tw-py-1 tw-rounded-full">Margin</span>
                      <Tag 
                        :value="`${data.profit_margin || 'N/A'}%`"
                        :severity="getMarginSeverity(data.profit_margin)"
                        class="tw-text-xs"
                      />
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="notes" header="Notes & Reason" class="tw-min-w-64">
              <template #body="{ data }">
                <div v-if="data.notes" class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-3">
                  <div class="tw-flex tw-items-start tw-gap-2">
                    <i class="pi pi-info-circle tw-text-amber-600 tw-text-sm tw-mt-0.5"></i>
                    <p class="tw-text-amber-800 tw-text-sm tw-leading-relaxed">{{ data.notes }}</p>
                  </div>
                </div>
                <span v-else class="tw-text-gray-400 tw-text-sm tw-italic">No notes provided</span>
              </template>
            </Column>

            <Column field="updated_by_name" header="Updated By" class="tw-min-w-40">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-500 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-text-xs tw-font-bold">
                    {{ getUserInitials(data.updated_by_name) }}
                  </div>
                  <span class="tw-font-medium tw-text-gray-700">{{ data.updated_by_name }}</span>
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-pt-4">
          <Button 
            label="Close" 
            icon="pi pi-times" 
            @click="showHistoryDialog = false" 
            class="p-button-text tw-transition-all tw-duration-300 hover:tw-scale-105" 
          />
        </div>
      </template>
    </Dialog>

    <!-- Enhanced CSV Import Dialog -->
    <Dialog 
      v-model:visible="showImportDialog"
      :modal="true"
      :style="{ width: '700px' }"
      class="tw-rounded-2xl"
    >
      <template #header>
        <div class="tw-flex tw-items-center tw-gap-4">
          <div class="tw-bg-gradient-to-br tw-from-emerald-500 tw-to-green-600 tw-p-3 tw-rounded-xl">
            <i class="pi pi-upload tw-text-white tw-text-xl"></i>
          </div>
          <div>
            <h3 class="tw-text-xl tw-font-bold tw-text-gray-800">Import Pricing from CSV</h3>
            <p class="tw-text-gray-600 tw-text-sm tw-mt-1">
              Bulk upload pricing data from a CSV file
            </p>
          </div>
        </div>
      </template>

      <div class="tw-space-y-6 tw-pt-4">
        <!-- CSV Format Requirements -->
        <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-border-2 tw-border-dashed tw-border-blue-200 tw-rounded-xl tw-p-6">
          <div class="tw-flex tw-items-start tw-gap-4">
            <div class="tw-bg-blue-500 tw-p-2 tw-rounded-lg tw-flex-shrink-0">
              <i class="pi pi-info-circle tw-text-white tw-text-lg"></i>
            </div>
            <div class="tw-flex-1">
              <h4 class="tw-font-bold tw-text-blue-900 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-file-excel"></i>
                CSV Format Requirements
              </h4>
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <h5 class="tw-font-semibold tw-text-blue-800 tw-mb-2">Required Columns:</h5>
                  <ul class="tw-text-sm tw-text-blue-700 tw-space-y-1">
                    <li class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-check tw-text-emerald-600"></i>
                      <code class="tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded">product_id</code>
                    </li>
                    <li class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-check tw-text-emerald-600"></i>
                      <code class="tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded">selling_price</code>
                    </li>
                    <li class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-check tw-text-emerald-600"></i>
                      <code class="tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded">purchase_price</code>
                    </li>
                    <li class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-check tw-text-emerald-600"></i>
                      <code class="tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded">vat_rate</code>
                    </li>
                    <li class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-minus tw-text-gray-400"></i>
                      <code class="tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">notes</code> (optional)
                    </li>
                  </ul>
                </div>
                <div>
                  <h5 class="tw-font-semibold tw-text-blue-800 tw-mb-2">Format Rules:</h5>
                  <ul class="tw-text-sm tw-text-blue-700 tw-space-y-1">
                    <li>• Use comma (,) as separator</li>
                    <li>• First row must be headers</li>
                    <li>• Product ID must exist in database</li>
                    <li>• Prices as decimal numbers</li>
                    <li>• VAT rate as percentage (0-100)</li>
                  </ul>
                </div>
              </div>
              <div class="tw-mt-4 tw-flex tw-gap-3">
                <Button 
                  label="Download Template"
                  icon="pi pi-download"
                  class="p-button-sm p-button-outlined tw-bg-white tw-border-blue-300 tw-text-blue-700 hover:tw-bg-blue-50 tw-transition-all tw-duration-300"
                  @click="downloadTemplate"
                />
                <Button 
                  label="View Sample Data"
                  icon="pi pi-eye"
                  class="p-button-sm p-button-text tw-text-blue-600"
                />
              </div>
            </div>
          </div>
        </div>
        
        <!-- File Upload Area -->
        <div class="tw-group">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-cloud-upload tw-text-emerald-500"></i>
            Upload CSV File
          </label>
          <div class="tw-relative">
            <div class="tw-border-2 tw-border-dashed tw-border-gray-300 group-hover:tw-border-emerald-400 tw-rounded-xl tw-p-8 tw-text-center tw-transition-all tw-duration-300 tw-bg-gray-50 group-hover:tw-bg-emerald-50">
              <div class="tw-mb-4">
                <div class="tw-bg-gradient-to-r tw-from-emerald-500 tw-to-green-500 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-3">
                  <i class="pi pi-cloud-upload tw-text-white tw-text-2xl"></i>
                </div>
                <p class="tw-text-gray-600 tw-font-medium">Drag and drop your CSV file here</p>
                <p class="tw-text-gray-400 tw-text-sm tw-mt-1">or click to browse</p>
              </div>
              
              <FileUpload 
                mode="basic"
                accept=".csv"
                :maxFileSize="1000000"
                @select="onFileSelect"
                :auto="false"
                chooseLabel="Select CSV File"
                class="tw-inline-block"
                :class="{ 'tw-opacity-50': importPreview.length > 0 }"
              />
              
              <div class="tw-mt-3 tw-text-xs tw-text-gray-500">
                Maximum file size: 1MB • Supported format: .csv
              </div>
            </div>
          </div>
        </div>
        
        <!-- Import Preview -->
        <div v-if="importPreview.length > 0" class="tw-bg-gradient-to-br tw-from-gray-50 tw-to-gray-100 tw-rounded-xl tw-p-6">
          <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-emerald-500 tw-p-2 tw-rounded-lg">
                <i class="pi pi-eye tw-text-white"></i>
              </div>
              <div>
                <h4 class="tw-font-bold tw-text-gray-800">Import Preview</h4>
                <p class="tw-text-gray-600 tw-text-sm">
                  {{ importPreview.length }} {{ importPreview.length === 1 ? 'record' : 'records' }} ready to import
                </p>
              </div>
            </div>
            <Button 
              icon="pi pi-times" 
              class="p-button-text p-button-sm tw-text-gray-500 hover:tw-text-red-500"
              @click="clearImportPreview"
              v-tooltip="'Clear preview'"
            />
          </div>
          
          <div class="tw-bg-white tw-rounded-lg tw-border tw-shadow-sm tw-overflow-hidden">
            <DataTable 
              :value="importPreview.slice(0, 5)" 
              class="tw-text-sm tw-border-0"
              :scrollable="true"
              scrollHeight="300px"
            >
              <Column field="product_id" header="Product ID" class="tw-min-w-32">
                <template #body="{ data }">
                  <span class="tw-font-mono tw-text-blue-600">{{ data.product_id }}</span>
                </template>
              </Column>
              <Column field="selling_price" header="Selling Price" class="tw-min-w-32">
                <template #body="{ data }">
                  <span class="tw-font-semibold tw-text-emerald-600">{{ formatCurrency(data.selling_price) }}</span>
                </template>
              </Column>
              <Column field="purchase_price" header="Purchase Price" class="tw-min-w-32">
                <template #body="{ data }">
                  <span class="tw-font-semibold tw-text-blue-600">{{ formatCurrency(data.purchase_price) }}</span>
                </template>
              </Column>
              <Column field="vat_rate" header="VAT %" class="tw-min-w-20">
                <template #body="{ data }">
                  <span class="tw-font-semibold tw-text-purple-600">{{ data.vat_rate }}%</span>
                </template>
              </Column>
              <Column field="notes" header="Notes" class="tw-min-w-48">
                <template #body="{ data }">
                  <span class="tw-text-gray-600 tw-text-xs">{{ data.notes || '-' }}</span>
                </template>
              </Column>
            </DataTable>
          </div>
          
          <div v-if="importPreview.length > 5" class="tw-mt-3 tw-text-center">
            <p class="tw-text-gray-500 tw-text-sm">
              Showing first 5 of {{ importPreview.length }} records
            </p>
          </div>
        </div>

        <!-- Import Statistics -->
        <div v-if="importPreview.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
          <div class="tw-bg-emerald-50 tw-border tw-border-emerald-200 tw-rounded-lg tw-p-4 tw-text-center">
            <div class="tw-text-emerald-600 tw-text-2xl tw-font-bold">{{ importPreview.length }}</div>
            <div class="tw-text-emerald-800 tw-text-sm tw-font-medium">Total Records</div>
          </div>
          <div class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4 tw-text-center">
            <div class="tw-text-blue-600 tw-text-2xl tw-font-bold">{{ getValidRecords() }}</div>
            <div class="tw-text-blue-800 tw-text-sm tw-font-medium">Valid Records</div>
          </div>
          <div class="tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-lg tw-p-4 tw-text-center">
            <div class="tw-text-red-600 tw-text-2xl tw-font-bold">{{ getInvalidRecords() }}</div>
            <div class="tw-text-red-800 tw-text-sm tw-font-medium">Invalid Records</div>
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            @click="showImportDialog = false" 
            class="p-button-text tw-transition-all tw-duration-300 hover:tw-scale-105" 
          />
          <Button 
            label="Import Pricing"
            icon="pi pi-upload"
            @click="importPricing"
            :loading="importing"
            :disabled="importPreview.length === 0 || getValidRecords() === 0"
            class="tw-bg-gradient-to-r tw-from-emerald-500 tw-to-green-600 tw-border-0 tw-transition-all tw-duration-300 hover:tw-scale-105 hover:tw-shadow-lg"
          />
        </div>
      </template>
    </Dialog>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import FileUpload from 'primevue/fileupload'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

// Services
import productPricingService from '@/services/Inventory/productPricingService'

const toast = useToast()
const confirm = useConfirm()

// State
const loading = ref(false)
const saving = ref(false)
const exporting = ref(false)
const importing = ref(false)
const generatingReport = ref(false)
const loadingHistory = ref(false)

const serviceGroups = ref([])
const selectedServiceGroup = ref(null)
const isPharmacy = ref(false)
const searchQuery = ref('')

const pricing = ref([])
const filteredPricing = ref([])
const availableProducts = ref([])

const showAddPricingDialog = ref(false)
const showHistoryDialog = ref(false)
const showImportDialog = ref(false)

const editingPricing = ref(null)
const priceHistory = ref([])
const importPreview = ref([])
const importFile = ref(null)

const productTypes = [
  { label: 'Stock Products', value: false },
  { label: 'Pharmacy Products', value: true }
]

const pricingForm = reactive({
  product_id: null,
  selling_price: 0,
  purchase_price: 0,
  vat_rate: 0,
  notes: ''
})

// Load service groups on mount
onMounted(async () => {
  await loadServiceGroups()
  await loadAvailableProducts()
})

const loadServiceGroups = async () => {
  try {
    const response = await axios.get('/api/configuration/service-groups')
    serviceGroups.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load service groups:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load service groups',
      life: 3000
    })
  }
}

const loadAvailableProducts = async () => {
  try {
    const endpoint = isPharmacy.value 
      ? '/api/pharmacy/products'
      : '/api/purchasing/products'
    const response = await axios.get(endpoint)
    availableProducts.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load products:', error)
  }
}

const loadPricing = async () => {
  if (!selectedServiceGroup.value) {
    pricing.value = []
    filteredPricing.value = []
    return
  }

  loading.value = true
  try {
    const response = await productPricingService.getServiceGroupPricingList(
      selectedServiceGroup.value,
      isPharmacy.value
    )
    
    if (response.success) {
      pricing.value = response.data
      filteredPricing.value = response.data
    }
  } catch (error) {
    console.error('Failed to load pricing:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pricing data',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const filterPricing = () => {
  if (!searchQuery.value) {
    filteredPricing.value = pricing.value
    return
  }

  const query = searchQuery.value.toLowerCase()
  filteredPricing.value = pricing.value.filter(item =>
    item.product_name?.toLowerCase().includes(query)
  )
}

const editPricing = (item) => {
  editingPricing.value = item
  pricingForm.product_id = item.product_id || item.pharmacy_product_id
  pricingForm.selling_price = item.selling_price
  pricingForm.purchase_price = item.purchase_price
  pricingForm.vat_rate = item.vat_rate
  pricingForm.notes = ''
  showAddPricingDialog.value = true
}

const savePricing = async () => {
  if (!pricingForm.product_id || !pricingForm.selling_price) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Product and selling price are required',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    await productPricingService.updateServiceGroupPricing(
      selectedServiceGroup.value,
      pricingForm.product_id,
      {
        is_pharmacy: isPharmacy.value,
        selling_price: pricingForm.selling_price,
        purchase_price: pricingForm.purchase_price,
        vat_rate: pricingForm.vat_rate,
        notes: pricingForm.notes
      }
    )

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Pricing saved successfully',
      life: 3000
    })

    closeAddPricingDialog()
    await loadPricing()
  } catch (error) {
    console.error('Failed to save pricing:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save pricing',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const viewHistory = async (item) => {
  loadingHistory.value = true
  showHistoryDialog.value = true
  
  try {
    const productId = item.product_id || item.pharmacy_product_id
    const response = await productPricingService.getPriceHistory(
      selectedServiceGroup.value,
      productId,
      isPharmacy.value,
      20
    )
    
    if (response.success) {
      priceHistory.value = response.data
    }
  } catch (error) {
    console.error('Failed to load price history:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load price history',
      life: 3000
    })
  } finally {
    loadingHistory.value = false
  }
}

const confirmDelete = (item) => {
  confirm.require({
    message: `Delete pricing for ${item.product_name}?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    accept: () => deletePricing(item),
  })
}

const deletePricing = async (item) => {
  try {
    await productPricingService.deletePricing(item.id)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Pricing deleted successfully',
      life: 3000
    })
    
    await loadPricing()
  } catch (error) {
    console.error('Failed to delete pricing:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete pricing',
      life: 3000
    })
  }
}

const exportPricing = async () => {
  if (!selectedServiceGroup.value) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select a service group first',
      life: 3000
    })
    return
  }

  exporting.value = true
  try {
    const response = await axios.get(
      `/api/purchasing/service-groups/${selectedServiceGroup.value}/pricing/export`,
      {
        params: { is_pharmacy: isPharmacy.value },
        responseType: 'blob'
      }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `pricing_export_${Date.now()}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Pricing exported successfully',
      life: 3000
    })
  } catch (error) {
    console.error('Export failed:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to export pricing',
      life: 3000
    })
  } finally {
    exporting.value = false
  }
}

const downloadTemplate = () => {
  const csv = 'product_id,selling_price,purchase_price,vat_rate,notes\n1,1000.00,800.00,19.00,Example pricing\n'
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', 'pricing_template.csv')
  document.body.appendChild(link)
  link.click()
  link.remove()
}

const onFileSelect = (event) => {
  importFile.value = event.files[0]
  const reader = new FileReader()
  
  reader.onload = (e) => {
    const text = e.target.result
    const lines = text.split('\n').filter(line => line.trim())
    const headers = lines[0].split(',')
    
    importPreview.value = lines.slice(1, 6).map(line => {
      const values = line.split(',')
      return {
        product_id: values[0],
        selling_price: values[1],
        purchase_price: values[2],
        vat_rate: values[3],
        notes: values[4] || ''
      }
    })
  }
  
  reader.readAsText(importFile.value)
}

const importPricing = async () => {
  if (!selectedServiceGroup.value || !importFile.value) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select service group and CSV file',
      life: 3000
    })
    return
  }

  importing.value = true
  try {
    const formData = new FormData()
    formData.append('file', importFile.value)
    formData.append('is_pharmacy', isPharmacy.value)

    const response = await axios.post(
      `/api/purchasing/service-groups/${selectedServiceGroup.value}/pricing/import`,
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' }
      }
    )

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Imported ${response.data.imported_count} pricing records`,
      life: 5000
    })

    showImportDialog.value = false
    importPreview.value = []
    importFile.value = null
    await loadPricing()
  } catch (error) {
    console.error('Import failed:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to import pricing',
      life: 3000
    })
  } finally {
    importing.value = false
  }
}

const generateReport = async () => {
  if (!selectedServiceGroup.value) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select a service group first',
      life: 3000
    })
    return
  }

  generatingReport.value = true
  try {
    const response = await axios.get(
      `/api/purchasing/service-groups/${selectedServiceGroup.value}/pricing/report`,
      {
        params: { is_pharmacy: isPharmacy.value },
        responseType: 'blob'
      }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `pricing_report_${Date.now()}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Report generated successfully',
      life: 3000
    })
  } catch (error) {
    console.error('Report generation failed:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate report',
      life: 3000
    })
  } finally {
    generatingReport.value = false
  }
}

const closeAddPricingDialog = () => {
  showAddPricingDialog.value = false
  editingPricing.value = null
  pricingForm.product_id = null
  pricingForm.selling_price = 0
  pricingForm.purchase_price = 0
  pricingForm.vat_rate = 0
  pricingForm.notes = ''
}

const formatCurrency = (value) => {
  if (!value) return 'N/A'
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD'
  }).format(value)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR')
}

const getMarginSeverity = (margin) => {
  if (!margin) return 'secondary'
  if (margin > 30) return 'success'
  if (margin > 15) return 'info'
  if (margin > 5) return 'warning'
  return 'danger'
}

const getServiceGroupName = (groupId) => {
  const group = serviceGroups.value.find(g => g.id === groupId)
  return group ? group.name : 'Unknown'
}

const clearFilters = () => {
  searchQuery.value = ''
  filterPricing()
}

const clearAllFilters = () => {
  selectedServiceGroup.value = null
  isPharmacy.value = null
  searchQuery.value = ''
  pricing.value = []
  filteredPricing.value = []
}

// Additional utility methods for enhanced dialogs
const calculateProfitMargin = () => {
  if (!pricingForm.selling_price || !pricingForm.purchase_price) return 0
  return ((pricingForm.selling_price - pricingForm.purchase_price) / pricingForm.purchase_price * 100).toFixed(2)
}

const calculateFinalPrice = () => {
  if (!pricingForm.selling_price) return 0
  const vatAmount = pricingForm.selling_price * (pricingForm.vat_rate || 0) / 100
  return pricingForm.selling_price + vatAmount
}

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const getUserInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(word => word.charAt(0).toUpperCase()).join('').slice(0, 2)
}

const getPriceChange = (currentPrice, previousPrice, type) => {
  if (!previousPrice || !currentPrice) return ''
  const change = currentPrice - previousPrice
  const percentage = (change / previousPrice * 100).toFixed(1)
  const isIncrease = change > 0
  return `${isIncrease ? '+' : ''}${percentage}%`
}

const getHistoryRowClass = (data) => {
  return data.effective_to ? 'tw-opacity-75' : ''
}

const clearImportPreview = () => {
  importPreview.value = []
}

const getValidRecords = () => {
  return importPreview.value.filter(record => 
    record.product_id && 
    record.selling_price && 
    !isNaN(record.selling_price) && 
    record.selling_price > 0
  ).length
}

const getInvalidRecords = () => {
  return importPreview.value.length - getValidRecords()
}
</script>

<style scoped>
/* Enhanced DataTable Styling */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
  color: white;
  font-weight: 600;
  border: none;
  padding: 1rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: all 0.2s ease;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem;
  border: none;
}

/* Enhanced Button Animations */
:deep(.p-button) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

:deep(.p-button:not(.p-button-text):hover) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

:deep(.p-button:active) {
  transform: translateY(0px);
}

/* Enhanced Dialog Animations */
:deep(.p-dialog) {
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(10px);
}

:deep(.p-dialog-header) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
  padding: 1.5rem;
}

:deep(.p-dialog-content) {
  padding: 0;
}

/* Enhanced Input Styling */
:deep(.p-inputtext):focus,
:deep(.p-dropdown):focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Enhanced Dropdown Styling */
:deep(.p-dropdown-panel) {
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid #e2e8f0;
}

/* Custom Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.tw-animate-fadeInUp {
  animation: fadeInUp 0.5s ease-out;
}

.tw-animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Enhanced Card Hover Effects */
:deep(.p-card) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid transparent;
}

:deep(.p-card:hover) {
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  border-color: rgba(99, 102, 241, 0.2);
}

/* Loading State Animations */
:deep(.p-datatable .p-datatable-loading-overlay) {
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.95) 0%, rgba(241, 245, 249, 0.95) 100%);
  backdrop-filter: blur(8px);
}

/* Toast Enhancements */
:deep(.p-toast .p-toast-message) {
  border-radius: 0.75rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* File Upload Enhancements */
:deep(.p-fileupload-basic .p-button) {
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
}

/* Enhanced Progress Bar */
:deep(.p-progressbar) {
  border-radius: 0.5rem;
  overflow: hidden;
  background: #e2e8f0;
}

:deep(.p-progressbar .p-progressbar-value) {
  background: linear-gradient(90deg, #10b981, #06d6a0);
}
</style>
