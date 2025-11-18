<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-to-slate-100 tw-p-4 lg:tw-p-6">
    <!-- Enhanced Header with Breadcrumb -->
    <div class="tw-mb-6">
      <Breadcrumb :model="breadcrumbItems" class="tw-mb-4">
        <template #item="{ item }">
          <span class="tw-text-gray-600 hover:tw-text-teal-600 tw-transition-colors">{{ item.label }}</span>
        </template>
      </Breadcrumb>

      <Card class="tw-border-0 tw-shadow-2xl tw-overflow-hidden">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700 tw-p-6 tw--m-6">
            <div class="tw-flex tw-items-center tw-justify-between">
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-backdrop-blur-sm tw-animate-pulse-slow">
                  <i class="pi pi-database tw-text-3xl tw-text-white"></i>
                </div>
                <div>
                  <h1 class="tw-text-3xl tw-font-bold tw-text-white">Create Stock Entry</h1>
                  <p class="tw-text-teal-100 tw-mt-1">Create a new stock entry from received products</p>
                </div>
              </div>
              <Button 
                @click="router.back()"
                icon="pi pi-arrow-left"
                label="Back"
                class="p-button-secondary tw-shadow-lg hover:tw-scale-105 tw-transition-transform"
                size="large"
              />
            </div>

            <!-- Quick Stats Bar -->
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-3 tw-mt-6">
              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-teal-200">Total Products</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ form.items.length }}</p>
                  </div>
                  <i class="pi pi-box tw-text-teal-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-teal-200">Total Quantity</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ calculateTotalQuantity() }}</p>
                  </div>
                  <i class="pi pi-hashtag tw-text-teal-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-teal-200">Total Value</p>
                    <p class="tw-text-lg tw-font-bold tw-text-white">{{ formatCurrency(calculateTotalValue()) }}</p>
                  </div>
                  <i class="pi pi-dollar tw-text-teal-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-teal-200">Expiring Soon</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ countExpiringSoon() }}</p>
                  </div>
                  <i class="pi pi-calendar-times tw-text-orange-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-teal-200">Progress</p>
                    <ProgressBar :value="formCompletionPercentage" :showValue="false" class="tw-w-16" />
                  </div>
                  <span class="tw-text-white tw-font-bold">{{ formCompletionPercentage }}%</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Progress Steps -->
    <div class="tw-mb-6">
      <Steps :model="stepsItems" :readonly="false" :activeIndex="activeStep" />
    </div>

    <!-- Main Form -->
    <form @submit.prevent="handleSubmit">
      <!-- Tabbed Content -->
      <TabView v-model:activeIndex="activeTab" class="tw-shadow-xl tw-rounded-xl tw-mb-6">
        <!-- Basic Information Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-info-circle tw-mr-2"></i>
            Basic Information
            <Badge v-if="isBasicInfoComplete" value="✓" severity="success" class="tw-ml-2" />
          </template>

          <div class="tw-space-y-6">
            <!-- Selection Cards -->
            <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6">
              <!-- Goods Receipt Card -->
              <Card class="tw-border tw-shadow-sm hover:tw-shadow-lg tw-transition-shadow">
                <template #title>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-lg">
                    <i class="pi pi-receipt tw-text-teal-600"></i>
                    <span>Goods Receipt</span>
                  </div>
                </template>
                <template #content>
                  <Dropdown
                    v-model="form.bon_reception_id"
                    :options="goodsReceptions"
                    optionLabel="bon_reception_code"
                    optionValue="id"
                    placeholder="Select a Goods Receipt"
                    class="tw-w-full"
                    :loading="loadingGoodsReceptions"
                    @change="onGoodsReceptionChange"
                    filter
                    showClear
                    :virtualScrollerOptions="{ itemSize: 50 }"
                  >
                    <template #option="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-teal-50 tw-p-2 tw-rounded">
                        <Avatar 
                          icon="pi pi-file"
                          class="tw-bg-teal-100 tw-text-teal-700"
                          shape="circle"
                        />
                        <div class="tw-flex-1">
                          <div class="tw-font-semibold">{{ slotProps.option.bon_reception_code }}</div>
                          <div class="tw-text-xs tw-text-gray-500">
                            {{ slotProps.option.fournisseur?.company_name }} - 
                            {{ formatDate(slotProps.option.created_at) }}
                          </div>
                          <div class="tw-text-xs tw-text-gray-400">
                            Items: {{ slotProps.option.items?.length || 0 }}
                          </div>
                        </div>
                        <Tag 
                          :value="slotProps.option.status" 
                          :severity="getStatusSeverity(slotProps.option.status)"
                        />
                      </div>
                    </template>
                  </Dropdown>
                  <small v-if="errors.bon_reception_id" class="tw-text-red-500 tw-text-xs tw-mt-1">
                    <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.bon_reception_id[0] }}
                  </small>

                  <!-- Quick Actions -->
                  <div v-if="form.bon_reception_id" class="tw-mt-3 tw-flex tw-gap-2">
                    <Button 
                      @click="viewGoodsReceipt"
                      icon="pi pi-eye"
                      label="View Receipt"
                      class="p-button-text p-button-sm tw-text-teal-600"
                    />
                    <Button 
                      @click="loadReceiptItems"
                      icon="pi pi-download"
                      label="Load Items"
                      class="p-button-text p-button-sm tw-text-blue-600"
                    />
                  </div>
                </template>
              </Card>

              <!-- Service Selection Card -->
              <Card class="tw-border tw-shadow-sm hover:tw-shadow-lg tw-transition-shadow">
                <template #title>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-lg">
                    <i class="pi pi-building tw-text-cyan-600"></i>
                    <span>Service</span>
                  </div>
                </template>
                <template #content>
                  <Dropdown
                    v-model="form.service_id"
                    :options="services"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select a Service"
                    class="tw-w-full"
                    :loading="loadingServices"
                    filter
                    showClear
                    @change="onServiceChange"
                  >
                    <template #option="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-cyan-50 tw-p-2 tw-rounded">
                        <Avatar 
                          icon="pi pi-building"
                          class="tw-bg-cyan-100 tw-text-cyan-700"
                          shape="circle"
                        />
                        <div>
                          <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                          <div class="tw-text-xs tw-text-gray-500">
                            ID: {{ slotProps.option.service_id }}
                          </div>
                        </div>
                      </div>
                    </template>
                  </Dropdown>
                  <small v-if="errors.service_id" class="tw-text-red-500 tw-text-xs tw-mt-1">
                    <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.service_id[0] }}
                  </small>

                  <!-- Service Stats -->
                  <div v-if="form.service_id" class="tw-mt-3 tw-bg-cyan-50 tw-rounded-lg tw-p-3">
                    <div class="tw-text-xs tw-text-gray-600 tw-mb-1">Current Stock Status</div>
                    <div class="tw-flex tw-gap-4">
                      <div>
                        <span class="tw-text-xs tw-text-gray-500">Total Items:</span>
                        <span class="tw-font-bold tw-ml-1">{{ getServiceStockCount() }}</span>
                      </div>
                      <div>
                        <span class="tw-text-xs tw-text-gray-500">Low Stock:</span>
                        <span class="tw-font-bold tw-ml-1 tw-text-orange-600">{{ getServiceLowStockCount() }}</span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>

            <!-- Supplier Information Card -->
            <Card v-if="selectedGoodsReception" class="tw-border tw-shadow-sm tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50">
              <template #title>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-blue-800">
                  <i class="pi pi-users"></i>
                  <span>Supplier Information</span>
                </div>
              </template>
              <template #content>
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Company</label>
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mt-2">
                      <Avatar 
                        :label="selectedGoodsReception.fournisseur?.company_name?.charAt(0)" 
                        class="tw-bg-blue-100 tw-text-blue-700"
                      />
                      <div class="tw-font-semibold">
                        {{ selectedGoodsReception.fournisseur?.company_name || 'N/A' }}
                      </div>
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Contact</label>
                    <div class="tw-mt-2">
                      <i class="pi pi-user tw-mr-2 tw-text-gray-400"></i>
                      {{ selectedGoodsReception.fournisseur?.contact_person || 'N/A' }}
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Phone</label>
                    <div class="tw-mt-2">
                      <i class="pi pi-phone tw-mr-2 tw-text-gray-400"></i>
                      {{ selectedGoodsReception.fournisseur?.phone || 'N/A' }}
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Email</label>
                    <div class="tw-mt-2 tw-truncate">
                      <i class="pi pi-envelope tw-mr-2 tw-text-gray-400"></i>
                      {{ selectedGoodsReception.fournisseur?.email || 'N/A' }}
                    </div>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Notes Section -->
            <Card class="tw-border tw-shadow-sm">
              <template #title>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-comment tw-text-gray-600"></i>
                  <span>Notes</span>
                </div>
              </template>
              <template #content>
                <div class="tw-space-y-2">
                  <div class="tw-flex tw-justify-between">
                    <label class="tw-text-sm tw-font-medium tw-text-gray-700">Additional Notes</label>
                    <span class="tw-text-xs tw-text-gray-500">{{ form.notes.length }}/500 characters</span>
                  </div>
                  <Textarea
                    v-model="form.notes"
                    rows="3"
                    :maxlength="500"
                    placeholder="Add any additional notes about this stock entry..."
                    class="tw-w-full"
                    autoResize
                  />
                  <small v-if="errors.notes" class="tw-text-red-500 tw-text-xs">
                    <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.notes[0] }}
                  </small>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>

        <!-- Products Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-box tw-mr-2"></i>
            Products
            <Badge :value="form.items.length" class="tw-ml-2" severity="info" />
          </template>

          <div class="tw-space-y-6">
            <!-- Products Toolbar -->
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
              <div class="tw-flex tw-items-center tw-gap-4">
                <!-- Search -->
                <div v-if="form.items.length > 3" class="tw-relative">
                  <InputText 
                    v-model="itemSearchQuery"
                    placeholder="Search products..."
                    class="tw-pr-8 tw-min-w-[250px]"
                  />
                  <i class="pi pi-search tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
                </div>

                <!-- View Toggle -->
                <div class="tw-flex tw-bg-gray-100 tw-rounded-lg tw-p-1">
                  <Button 
                    icon="pi pi-list"
                    :class="viewMode === 'list' ? 'p-button-primary' : 'p-button-text'"
                    @click="viewMode = 'list'"
                    size="small"
                    v-tooltip="'List View'"
                  />
                  <Button 
                    icon="pi pi-th-large"
                    :class="viewMode === 'grid' ? 'p-button-primary' : 'p-button-text'"
                    @click="viewMode = 'grid'"
                    size="small"
                    v-tooltip="'Grid View'"
                  />
                </div>
              </div>

              <div class="tw-flex tw-gap-2">
                <Button 
                  @click="showBulkAddDialog"
                  icon="pi pi-plus-circle"
                  label="Bulk Add"
                  class="p-button-help"
                  size="small"
                />
                <Button 
                  @click="showProductModal = true"
                  icon="pi pi-plus"
                  label="Add Product"
                  class="p-button-success"
                />
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="form.items.length === 0" class="tw-bg-gray-50 tw-rounded-xl tw-border-2 tw-border-dashed tw-border-gray-300 tw-p-12">
              <div class="tw-text-center">
                <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-animate-bounce"></i>
                <p class="tw-mt-4 tw-text-gray-500 tw-text-lg">No products added yet</p>
                <p class="tw-text-gray-400 tw-mt-2">Start by adding products to this stock entry</p>
                <Button 
                  @click="showProductModal = true"
                  icon="pi pi-plus"
                  label="Add First Product"
                  class="p-button-outlined tw-mt-4"
                />
              </div>
            </div>

            <!-- List View -->
            <DataTable 
              v-else-if="viewMode === 'list'"
              :value="filteredItems"
              :paginator="form.items.length > 5"
              :rows="5"
              :rowsPerPageOptions="[5, 10, 25, 50]"
              responsiveLayout="scroll"
              class="tw-shadow-md"
              editMode="cell"
              @cell-edit-complete="onCellEditComplete"
              :rowClass="getRowClass"
              showGridlines
            >
              <!-- Index Column -->
              <Column header="#" style="width: 50px">
                <template #body="slotProps">
                  <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-teal-500 tw-to-cyan-600 tw-text-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm">
                    {{ slotProps.index + 1 }}
                  </div>
                </template>
              </Column>

              <!-- Product Column -->
              <Column field="product_id" header="Product" :sortable="true" style="min-width: 250px">
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <Avatar 
                      :label="(slotProps.data.product_name || getProductName(slotProps.data.product_id) || 'P')?.charAt(0)" 
                      class="tw-bg-teal-100 tw-text-teal-700"
                      shape="square"
                    />
                    <div class="tw-flex-1">
                      <div class="tw-font-semibold">
                        {{ slotProps.data.product_name || getProductName(slotProps.data.product_id) || 'Select Product' }}
                      </div>
                      <div class="tw-text-xs tw-text-gray-500">
                        <span v-if="slotProps.data.pharmacy_product_id" class="tw-inline-block tw-bg-amber-100 tw-text-amber-800 tw-px-2 tw-py-0.5 tw-rounded tw-mr-2">
                          <i class="pi pi-capsule tw-text-xs tw-mr-1"></i>Pharmacy
                        </span>
                        Code: {{ getProductCode(slotProps.data.product_id, slotProps.data.pharmacy_product_id) }}
                      </div>
                    </div>
                    <Button
                      v-if="slotProps.data.product_id"
                      icon="pi pi-history"
                      class="p-button-text p-button-sm"
                      @click="viewProductHistory(slotProps.data.product_id)"
                      v-tooltip="'View History'"
                    />
                  </div>
                </template>
                <template #editor="slotProps">
                  <Dropdown
                    v-model="slotProps.data.product_id"
                    :options="filteredProducts"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Product"
                    class="tw-w-full"
                    filter
                    :virtualScrollerOptions="{ itemSize: 50 }"
                  />
                </template>
              </Column>

              <!-- Quantity Column -->
              <Column field="quantity" header="Quantity" :sortable="true" style="width: 120px">
                <template #body="slotProps">
                  <Tag :value="slotProps.data.quantity" severity="info" class="tw-font-bold" />
                </template>
                <template #editor="slotProps">
                  <InputNumber
                    v-model="slotProps.data.quantity"
                    :min="1"
                    :max="9999"
                    showButtons
                    buttonLayout="horizontal"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Purchase Price Column -->
              <Column field="purchase_price" header="Purchase Price" :sortable="true" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-font-medium">{{ formatCurrency(slotProps.data.purchase_price) }}</span>
                </template>
                <template #editor="slotProps">
                  <InputNumber
                    v-model="slotProps.data.purchase_price"
                    :min="0"
                    mode="currency"
                    currency="DZD"
                    locale="en-US"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Batch Number Column -->
              <Column field="batch_number" header="Batch #" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-font-mono tw-text-sm">{{ slotProps.data.batch_number || '-' }}</span>
                </template>
                <template #editor="slotProps">
                  <InputText
                    v-model="slotProps.data.batch_number"
                    placeholder="Batch number"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Expiry Date Column -->
              <Column field="expiry_date" header="Expiry" :sortable="true" style="width: 150px">
                <template #body="slotProps">
                  <Tag 
                    v-if="slotProps.data.expiry_date"
                    :value="formatDate(slotProps.data.expiry_date)" 
                    :severity="getExpirySeverity(slotProps.data.expiry_date)"
                    :icon="getExpiryIcon(slotProps.data.expiry_date)"
                  />
                  <span v-else class="tw-text-gray-400">-</span>
                </template>
                <template #editor="slotProps">
                  <Calendar
                    v-model="slotProps.data.expiry_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Select date"
                    class="tw-w-full"
                    showIcon
                  />
                </template>
              </Column>

              <!-- VAT Column -->
              <Column field="tva" header="VAT %" style="width: 100px">
                <template #body="slotProps">
                  <span>{{ slotProps.data.tva || 0 }}%</span>
                </template>
                <template #editor="slotProps">
                  <InputNumber
                    v-model="slotProps.data.tva"
                    :min="0"
                    :max="100"
                    suffix="%"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Storage Column -->
              <Column field="storage_name" header="Storage" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-text-sm">{{ slotProps.data.storage_name || '-' }}</span>
                </template>
                <template #editor="slotProps">
                  <InputText
                    v-model="slotProps.data.storage_name"
                    placeholder="Storage location"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Actions Column -->
              <Column header="Actions" style="width: 120px" :exportable="false">
                <template #body="slotProps">
                  <div class="tw-flex tw-gap-1">
                    <Button 
                      @click="duplicateItem(slotProps.data)"
                      icon="pi pi-copy"
                      class="p-button-text p-button-sm p-button-success"
                      v-tooltip="'Duplicate'"
                    />
                    <Button 
                      @click="removeItem(slotProps.index)"
                      icon="pi pi-trash"
                      class="p-button-text p-button-sm p-button-danger"
                      v-tooltip="'Remove'"
                    />
                  </div>
                </template>
              </Column>

              <!-- Footer -->
              <template #footer>
                <div class="tw-bg-gradient-to-r tw-from-teal-50 tw-to-cyan-50 tw-p-4 tw-rounded-lg">
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <div class="tw-text-gray-600">
                      <span class="tw-font-medium">Total Items:</span> {{ form.items.length }}
                      <span class="tw-mx-4">|</span>
                      <span class="tw-font-medium">Total Quantity:</span> {{ calculateTotalQuantity() }}
                    </div>
                    <div class="tw-text-xl tw-font-bold tw-text-teal-600">
                      Total Value: {{ formatCurrency(calculateTotalValue()) }}
                    </div>
                  </div>
                </div>
              </template>
            </DataTable>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
              <div 
                v-for="(item, index) in filteredItems" 
                :key="index"
                class="tw-bg-white tw-rounded-xl tw-shadow-md hover:tw-shadow-xl tw-transition-shadow tw-overflow-hidden"
              >
                <div class="tw-bg-gradient-to-r tw-from-teal-500 tw-to-cyan-600 tw-p-3">
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-white tw-font-bold">Item #{{ index + 1 }}</span>
                    <div class="tw-flex tw-gap-1">
                      <Button 
                        @click="editItemDetails(item)"
                        icon="pi pi-pencil"
                        class="p-button-text p-button-sm tw-text-white"
                      />
                      <Button 
                        @click="removeItem(index)"
                        icon="pi pi-trash"
                        class="p-button-text p-button-sm tw-text-white"
                      />
                    </div>
                  </div>
                </div>
                <div class="tw-p-4 tw-space-y-3">
                  <div>
                    <label class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wider">Product</label>
                    <div class="tw-font-semibold tw-text-gray-800">
                      {{ getProductName(item.product_id) || 'Not Selected' }}
                    </div>
                  </div>
                  <div class="tw-grid tw-grid-cols-2 tw-gap-3">
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Quantity</label>
                      <Tag :value="item.quantity" severity="info" class="tw-mt-1" />
                    </div>
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Price</label>
                      <div class="tw-font-medium tw-mt-1">{{ formatCurrency(item.purchase_price) }}</div>
                    </div>
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Batch</label>
                      <div class="tw-font-mono tw-text-sm tw-mt-1">{{ item.batch_number || '-' }}</div>
                    </div>
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Expiry</label>
                      <Tag 
                        v-if="item.expiry_date"
                        :value="formatDate(item.expiry_date)"
                        :severity="getExpirySeverity(item.expiry_date)"
                        class="tw-mt-1"
                      />
                      <span v-else class="tw-text-gray-400">-</span>
                    </div>
                  </div>
                  <Divider />
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-sm tw-text-gray-500">Total</span>
                    <span class="tw-text-lg tw-font-bold tw-text-teal-600">
                      {{ formatCurrency(item.quantity * item.purchase_price) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- Attachments Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-paperclip tw-mr-2"></i>
            Attachments
            <Badge :value="form.attachments?.length || 0" class="tw-ml-2" severity="warning" />
          </template>

          <Card class="tw-border-0">
            <template #content>
              <AttachmentUploader
                v-model="form.attachments"
                model-type="bon_entree"
                :model-id="'temp'"
                :disabled="saving"
                @uploaded="onAttachmentsUploaded"
                @error="onAttachmentError"
              />
            </template>
          </Card>
        </TabPanel>

        <!-- Review Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-check-circle tw-mr-2"></i>
            Review & Submit
          </template>

          <div class="tw-space-y-6">
            <!-- Summary Cards -->
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
              <Card class="tw-border tw-shadow-sm tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50">
                <template #content>
                  <div class="tw-text-center">
                    <i class="pi pi-check-circle tw-text-3xl tw-text-green-600 tw-mb-2"></i>
                    <div class="tw-text-sm tw-text-gray-600">Ready Items</div>
                    <div class="tw-text-2xl tw-font-bold tw-text-green-800">{{ getReadyItemsCount() }}</div>
                  </div>
                </template>
              </Card>

              <Card class="tw-border tw-shadow-sm tw-bg-gradient-to-br tw-from-yellow-50 tw-to-amber-50">
                <template #content>
                  <div class="tw-text-center">
                    <i class="pi pi-exclamation-triangle tw-text-3xl tw-text-yellow-600 tw-mb-2"></i>
                    <div class="tw-text-sm tw-text-gray-600">Incomplete</div>
                    <div class="tw-text-2xl tw-font-bold tw-text-yellow-800">{{ getIncompleteItemsCount() }}</div>
                  </div>
                </template>
              </Card>

              <Card class="tw-border tw-shadow-sm tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50">
                <template #content>
                  <div class="tw-text-center">
                    <i class="pi pi-dollar tw-text-3xl tw-text-blue-600 tw-mb-2"></i>
                    <div class="tw-text-sm tw-text-gray-600">Total Value</div>
                    <div class="tw-text-xl tw-font-bold tw-text-blue-800">{{ formatCurrency(calculateTotalValue()) }}</div>
                  </div>
                </template>
              </Card>

              <Card class="tw-border tw-shadow-sm tw-bg-gradient-to-br tw-from-purple-50 tw-to-pink-50">
                <template #content>
                  <div class="tw-text-center">
                    <i class="pi pi-percentage tw-text-3xl tw-text-purple-600 tw-mb-2"></i>
                    <div class="tw-text-sm tw-text-gray-600">Completion</div>
                    <div class="tw-text-2xl tw-font-bold tw-text-purple-800">{{ formCompletionPercentage }}%</div>
                  </div>
                </template>
              </Card>
            </div>

            <!-- Review Checklist -->
            <Card class="tw-border tw-shadow-sm">
              <template #title>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-list-check tw-text-gray-600"></i>
                  <span>Review Checklist</span>
                </div>
              </template>
              <template #content>
                <div class="tw-space-y-3">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <i :class="form.bon_reception_id || form.items.length > 0 ? 'pi pi-check-circle tw-text-green-600' : 'pi pi-circle tw-text-gray-400'" class="tw-text-xl"></i>
                    <span :class="form.bon_reception_id || form.items.length > 0 ? 'tw-text-gray-800' : 'tw-text-gray-400'">
                      Goods receipt selected or products added
                    </span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <i :class="form.service_id ? 'pi pi-check-circle tw-text-green-600' : 'pi pi-circle tw-text-gray-400'" class="tw-text-xl"></i>
                    <span :class="form.service_id ? 'tw-text-gray-800' : 'tw-text-gray-400'">
                      Service selected (required for validation)
                    </span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <i :class="form.items.length > 0 ? 'pi pi-check-circle tw-text-green-600' : 'pi pi-circle tw-text-gray-400'" class="tw-text-xl"></i>
                    <span :class="form.items.length > 0 ? 'tw-text-gray-800' : 'tw-text-gray-400'">
                      At least one product added
                    </span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <i :class="allItemsComplete() ? 'pi pi-check-circle tw-text-green-600' : 'pi pi-circle tw-text-gray-400'" class="tw-text-xl"></i>
                    <span :class="allItemsComplete() ? 'tw-text-gray-800' : 'tw-text-gray-400'">
                      All products have required information
                    </span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>
      </TabView>

      <!-- Actions Card -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #content>
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4 tw-text-sm tw-text-gray-500">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle"></i>
                <span>Complete all required fields before submitting</span>
              </div>
            </div>
            <div class="tw-flex tw-gap-3">
              <Button 
                type="button"
                @click="router.back()"
                label="Cancel"
                icon="pi pi-times"
                class="p-button-secondary"
                size="large"
              />
              <Button 
                type="button"
                @click="submitForm(false)"
                :loading="saving"
                :disabled="form.items.length === 0"
                label="Save as Draft"
                icon="pi pi-save"
                class="p-button-info"
                size="large"
              />
              <Button 
                type="button"
                @click="submitForm(true)"
                :loading="saving"
                :disabled="!canValidate"
                label="Save & Validate"
                icon="pi pi-check-circle"
                class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700"
                size="large"
              />
            </div>
          </div>
        </template>
      </Card>
    </form>

    <!-- Product Selection Modal with Infinite Scroll -->
    <Dialog 
      v-model:visible="showProductModal" 
      header="Add Products" 
      :modal="true"
      :style="{ width: '90vw', maxWidth: '1400px' }"
      :maximizable="true"
    >
      <!-- Pricing Information Panel -->
      <div v-if="loadingPricingInfo || currentProductPricingInfo" class="tw-mb-4">
        <div v-if="loadingPricingInfo" class="tw-flex tw-justify-center tw-py-8">
          <i class="pi pi-spin pi-spinner tw-text-3xl tw-text-teal-600"></i>
        </div>
        <ProductPricingInfoPanel 
          v-else
          :pricing-info="currentProductPricingInfo" 
        />
      </div>
      
      <ProductSelectorWithInfiniteScroll
        v-model="selectedProductsToAdd"
        :scroll-height="'60vh'"
        :per-page="20"
        :show-source-filter="true"
        :show-stock="true"
        :show-select-all="true"
        :selectable="true"
        :default-quantity="defaultItemSettings.quantity"
        :default-price="defaultItemSettings.purchasingPrice"
        :default-unit="defaultItemSettings.unit"
        @selection-change="handleProductSelectionChange"
        @defaults-change="handleDefaultsChange"
        ref="productSelectorRef"
      />

      <template #footer>
        <div class="tw-space-y-3">
          <!-- Selected Products List -->
          <div v-if="selectedProductsToAdd.length > 0" class="tw-bg-teal-50 tw-rounded-lg tw-p-4 tw-border tw-border-teal-200">
            <div class="tw-text-sm tw-font-semibold tw-text-teal-800 tw-mb-2">
              <i class="pi pi-check-circle tw-mr-2"></i>
              Selected Products ({{ selectedProductsToAdd.length }})
            </div>
            <div class="tw-flex tw-flex-wrap tw-gap-2">
              <Tag 
                v-for="(product, index) in selectedProductsToAdd" 
                :key="index"
                :value="`${product.name}${product.quantity ? ' x' + product.quantity : ''}`"
                severity="success"
                icon="pi pi-check"
                class="tw-text-xs"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="tw-flex tw-justify-between tw-items-center">
            <span class="tw-text-sm tw-text-gray-500">
              <i :class="selectedProductsToAdd.length > 0 ? 'pi pi-check tw-text-green-600' : 'pi pi-info-circle tw-text-gray-400'" class="tw-mr-1"></i>
              {{ selectedProductsToAdd.length > 0 ? `Ready to add ${selectedProductsToAdd.length} product(s)` : 'Select products to add' }}
            </span>
            <div class="tw-flex tw-gap-2">
              <Button 
                label="Cancel" 
                icon="pi pi-times" 
                @click="showProductModal = false" 
                class="p-button-text"
              />
              <Button 
                label="Add Selected" 
                icon="pi pi-plus" 
                @click="addSelectedProducts" 
                :disabled="selectedProductsToAdd.length === 0"
                class="p-button-success"
              />
            </div>
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Item Details Edit Modal -->
    <Dialog 
      v-model:visible="showItemDetailsModal" 
      :header="`Edit Product: ${editingItem?.product_name || ''}`"
      :modal="true"
      :style="{ width: '700px' }"
    >
      <div v-if="editingItem" class="tw-space-y-4">
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Quantity</label>
            <InputNumber
              v-model="editingItem.quantity"
              :min="1"
              showButtons
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Purchase Price</label>
            <InputNumber
              v-model="editingItem.purchase_price"
              :min="0"
              mode="currency"
              currency="DZD"
              locale="en-US"
              class="tw-w-full"
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Batch Number</label>
            <InputText
              v-model="editingItem.batch_number"
              placeholder="Batch number"
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Expiry Date</label>
            <Calendar
              v-model="editingItem.expiry_date"
              dateFormat="yy-mm-dd"
              placeholder="Select date"
              class="tw-w-full"
              showIcon
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Storage Location</label>
          <InputText
            v-model="editingItem.storage_name"
            placeholder="Storage location"
            class="tw-w-full"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Remarks</label>
          <Textarea
            v-model="editingItem.remarks"
            placeholder="Additional remarks..."
            class="tw-w-full"
            rows="3"
            autoResize
          />
        </div>
      </div>

      <template #footer>
        <Button 
          label="Cancel" 
          icon="pi pi-times" 
          @click="showItemDetailsModal = false" 
          class="p-button-text"
        />
        <Button 
          label="Save Changes" 
          icon="pi pi-check" 
          @click="saveItemDetails" 
          class="p-button-primary"
        />
      </template>
    </Dialog>

    <!-- Confirm Dialog -->
    <ConfirmDialog></ConfirmDialog>

    <!-- Toast -->
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Calendar from 'primevue/calendar'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Badge from 'primevue/badge'
import Steps from 'primevue/steps'
import ProgressBar from 'primevue/progressbar'
import Divider from 'primevue/divider'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

// Custom Components
import AttachmentUploader from '@/Components/AttachmentUploader.vue'
import ProductSelectorWithInfiniteScroll from '@/Components/Apps/Purchasing/Shared/ProductSelectorWithInfiniteScroll.vue'
import ProductPricingInfoPanel from '@/Components/Inventory/ProductPricingInfoPanel.vue'

// Services
import productPricingService from '@/services/Inventory/productPricingService'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State
const saving = ref(false)
const loadingGoodsReceptions = ref(false)
const loadingProducts = ref(false)
const loadingServices = ref(false)
const activeStep = ref(0)
const activeTab = ref(0)
const viewMode = ref('list')
const itemSearchQuery = ref('')

// Data
const goodsReceptions = ref([])
const products = ref([])
const services = ref([])
const selectedGoodsReception = ref(null)
const selectedProductsToAdd = ref([])
const currentProductPricingInfo = ref(null)
const loadingPricingInfo = ref(false)
const errors = ref({})

// Modals
const showProductModal = ref(false)
const showItemDetailsModal = ref(false)
const editingItem = ref(null)
const productSelectorRef = ref(null)

// Default settings
const defaultItemSettings = reactive({
  quantity: 1,
  purchasingPrice: 0,
  unit: 'unit'
})

// Form
const form = reactive({
  bon_reception_id: null,
  service_id: null,
  notes: '',
  attachments: [],
  items: []
})

// Steps
const stepsItems = [
  { label: 'Basic Info', icon: 'pi pi-info-circle' },
  { label: 'Products', icon: 'pi pi-box' },
  { label: 'Attachments', icon: 'pi pi-paperclip' },
  { label: 'Review', icon: 'pi pi-check' }
]

// Breadcrumb
const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Stock Management', to: '/stock' },
  { label: 'Create Stock Entry', disabled: true }
])

// Computed
const filteredProducts = computed(() => {
  const list = Array.isArray(products.value) ? products.value : []
  const selectedIds = new Set((form.items || []).map(i => i.product_id).filter(Boolean))

  if (!form.service_id) {
    return list
  }

  return list.filter(p => {
    const matchesService = (p?.service?.name === form.service_id) || (p?.service_id === form.service_id)
    return matchesService || selectedIds.has(p.id)
  })
})

const filteredItems = computed(() => {
  if (!itemSearchQuery.value) return form.items

  const query = itemSearchQuery.value.toLowerCase()
  return form.items.filter(item => {
    const productName = getProductName(item.product_id)?.toLowerCase() || ''
    return productName.includes(query) || item.batch_number?.toLowerCase().includes(query)
  })
})

const canValidate = computed(() => {
  return form.items.length > 0 && form.service_id && allItemsComplete()
})

const isBasicInfoComplete = computed(() => {
  return (form.bon_reception_id || form.items.length > 0) && form.service_id
})

const formCompletionPercentage = computed(() => {
  let points = 0
  const totalPoints = 4

  if (form.bon_reception_id || form.items.length > 0) points++
  if (form.service_id) points++
  if (form.items.length > 0) points++
  if (allItemsComplete()) points++

  return Math.round((points / totalPoints) * 100)
})

// Methods
const fetchGoodsReceptions = async () => {
  try {
    loadingGoodsReceptions.value = true
    const response = await axios.get('/api/bon-receptions')

    if (response?.data?.data?.data) goodsReceptions.value = response.data.data.data
    else if (response?.data?.data) goodsReceptions.value = response.data.data
    else if (response?.data) goodsReceptions.value = response.data
    else goodsReceptions.value = []

  } catch (err) {
    console.error('Error fetching goods receptions:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load goods receptions',
      life: 3000
    })
  } finally {
    loadingGoodsReceptions.value = false
  }
}

const fetchProducts = async () => {
  try {
    loadingProducts.value = true
    
    // Fetch both regular and pharmacy products
    const [regularResponse, pharmacyResponse] = await Promise.all([
      axios.get('/api/products'),
      axios.get('/api/pharmacy-products')
    ])

    // Extract regular products
    let regularProducts = []
    if (regularResponse?.data?.data?.data) regularProducts = regularResponse.data.data.data
    else if (regularResponse?.data?.data) regularProducts = regularResponse.data.data
    else if (regularResponse?.data) regularProducts = regularResponse.data
    else regularProducts = []

    // Extract pharmacy products
    let pharmacyProducts = []
    if (pharmacyResponse?.data?.data?.data) pharmacyProducts = pharmacyResponse.data.data.data
    else if (pharmacyResponse?.data?.data) pharmacyProducts = pharmacyResponse.data.data
    else if (pharmacyResponse?.data) pharmacyProducts = pharmacyResponse.data
    else pharmacyProducts = []

    // Combine both product types
    products.value = [...regularProducts, ...pharmacyProducts]
  } catch (err) {
    console.error('Error fetching products:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load products',
      life: 3000
    })
  } finally {
    loadingProducts.value = false
  }
}

const fetchServices = async () => {
  try {
    loadingServices.value = true
    const response = await axios.get('/api/services')

    if (response.data.status === 'success') {
      services.value = response.data.data
    }
  } catch (err) {
    console.error('Error fetching services:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load services',
      life: 3000
    })
  } finally {
    loadingServices.value = false
  }
}

const onGoodsReceptionChange = () => {
  if (form.bon_reception_id) {
    selectedGoodsReception.value = goodsReceptions.value.find(br => br.id === form.bon_reception_id)
    loadReceiptItems()
  } else {
    selectedGoodsReception.value = null
    form.items = []
  }
}

const loadReceiptItems = () => {
  if (selectedGoodsReception.value?.items) {
    form.items = selectedGoodsReception.value.items.map(item => ({
      product_id: item.product_id || item.product?.id,
      quantity: item.quantity_received || item.quantity || 1,
      purchase_price: item.unit_price || item.purchase_price || 0,
      tva: item.tva || 0,
      batch_number: item.batch_number || '',
      serial_number: item.serial_number || '',
      expiry_date: item.expiry_date || null,
      storage_name: item.storage_name || '',
      remarks: item.remarks || ''
    }))
    activeStep.value = 1
    activeTab.value = 1
  }
}

const onServiceChange = () => {
  // Additional logic when service changes
}

const addSelectedProducts = () => {
  if (selectedProductsToAdd.value.length === 0) return

  selectedProductsToAdd.value.forEach(product => {
    // Determine if it's a pharmacy product or regular product
    const isPharmacyProduct = product.sku || product.is_clinical !== undefined
    
    const newItem = {
      quantity: product.quantity || defaultItemSettings.quantity || 1,
      tva: 0,
      batch_number: '',
      serial_number: '',
      expiry_date: null,
      storage_name: '',
      remarks: '',
      product_name: product.name,
      purchase_price: product.purchase_price || product.unit_cost || product.selling_price || defaultItemSettings.purchasingPrice || 0,
      unit: product.unit || defaultItemSettings.unit || 'unit',
      sub_items: []
    }

    // Add appropriate product ID based on type
    if (isPharmacyProduct) {
      newItem.pharmacy_product_id = product.id
      newItem.product_id = null
    } else {
      newItem.product_id = product.id
      newItem.pharmacy_product_id = null
    }

    form.items.push(newItem)
  })

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${selectedProductsToAdd.value.length} product(s) added`,
    life: 3000
  })

  showProductModal.value = false
  selectedProductsToAdd.value = []
  productSelectorRef.value?.clearSelection?.()
}

// Handle product selection changes from the ProductSelectorWithInfiniteScroll
const handleProductSelectionChange = async (selected) => {
  selectedProductsToAdd.value = selected.map(product => ({
    id: product.id,
    name: product.name,
    sku: product.sku,
    product_code: product.product_code,
    is_clinical: product.is_clinical,
    quantity: defaultItemSettings.quantity || 1,
    purchase_price: defaultItemSettings.purchasingPrice || 0,
    unit: defaultItemSettings.unit || 'unit'
  }))
  
  // Load pricing info for first selected product
  if (selected.length > 0) {
    await loadProductPricingInfo(selected[0])
  } else {
    currentProductPricingInfo.value = null
  }
}

// Load product pricing information
const loadProductPricingInfo = async (product) => {
  if (!product || !product.id) {
    currentProductPricingInfo.value = null
    return
  }
  
  loadingPricingInfo.value = true
  try {
    const isPharmacy = product.sku || product.is_clinical !== undefined
    const response = await productPricingService.getProductPricingInfo(product.id, isPharmacy)
    
    if (response.success) {
      currentProductPricingInfo.value = response.data
    } else {
      currentProductPricingInfo.value = null
    }
  } catch (error) {
    console.error('Failed to load pricing info:', error)
    currentProductPricingInfo.value = null
  } finally {
    loadingPricingInfo.value = false
  }
}

// Handle defaults changes from the ProductSelectorWithInfiniteScroll
const handleDefaultsChange = (defaults) => {
  defaultItemSettings.quantity = defaults.quantity || 1
  defaultItemSettings.purchasingPrice = defaults.price || 0
  defaultItemSettings.unit = defaults.unit || 'unit'
}

const duplicateItem = (item) => {
  const newItem = { ...item }
  form.items.push(newItem)

  toast.add({
    severity: 'success',
    summary: 'Duplicated',
    detail: 'Product duplicated successfully',
    life: 2000
  })
}

const removeItem = (index) => {
  confirm.require({
    message: 'Are you sure you want to remove this product?',
    header: 'Confirm Removal',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      form.items.splice(index, 1)
    }
  })
}

const editItemDetails = (item) => {
  editingItem.value = { ...item }
  showItemDetailsModal.value = true
}

const saveItemDetails = () => {
  const index = form.items.findIndex(item => 
    (item.product_id === editingItem.value.product_id)
  )

  if (index !== -1) {
    form.items[index] = { ...editingItem.value }
  }

  showItemDetailsModal.value = false
  editingItem.value = null
}

const onCellEditComplete = (event) => {
  let { data, newValue, field } = event
  data[field] = newValue
}

const showBulkAddDialog = () => {
  toast.add({
    severity: 'info',
    summary: 'Coming Soon',
    detail: 'Bulk add feature will be available soon',
    life: 3000
  })
}

const submitForm = async (shouldValidate = false) => {
  try {
    saving.value = true
    errors.value = {}

    if (!form.bon_reception_id && form.items.length === 0) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please select a goods receipt or add at least one product',
        life: 3000
      })
      return
    }

    if (shouldValidate) {
      if (!form.service_id) {
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: 'Service is required when validating stock entry',
          life: 3000
        })
        return
      }

      for (let i = 0; i < form.items.length; i++) {
        const item = form.items[i]
        const hasProduct = item.product_id || item.pharmacy_product_id
        if (!hasProduct || !item.quantity || item.quantity <= 0) {
          toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: `Item ${i + 1}: Product and quantity are required for validation`,
            life: 3000
          })
          return
        }
      }
    }

    const payload = {
      bon_reception_id: form.bon_reception_id,
      service_id: form.service_id,
      notes: form.notes,
      items: form.items.map(item => ({
        product_id: item.product_id || null,
        pharmacy_product_id: item.pharmacy_product_id || null,
        quantity: item.quantity,
        purchase_price: item.purchase_price,
        tva: item.tva,
        batch_number: item.batch_number,
        serial_number: item.serial_number,
        expiry_date: item.expiry_date ? formatDateForAPI(item.expiry_date) : null,
        storage_name: item.storage_name,
        remarks: item.remarks
      }))
    }

    const response = await axios.post('/api/purchasing/bon-entrees', payload)

    if (response.data.status === 'success') {
      const bonEntreeId = response.data.data.id

      if (shouldValidate) {
        try {
          const validateResponse = await axios.patch(`/api/purchasing/bon-entrees/${bonEntreeId}/validate`)

          if (validateResponse.data.status === 'success') {
            toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Stock entry created and validated successfully',
              life: 4000
            })
          }
        } catch (validateErr) {
          console.error('Error validating bon entree:', validateErr)
          toast.add({
            severity: 'warn',
            summary: 'Partial Success',
            detail: 'Stock entry created but validation failed',
            life: 4000
          })
        }
      } else {
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Stock entry created successfully as draft',
          life: 3000
        })
      }

  // navigate to the bon entrees index route (named route includes .index)
  router.push({ name: 'purchasing.bon-entrees.index' })
    }
  } catch (err) {
    console.error('Error creating stock entry:', err)

    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please check the form for errors',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: err.response?.data?.message || 'Failed to create stock entry',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

// Utility functions
const getProductName = (productId, pharmacyProductId = null) => {
  if (pharmacyProductId) {
    const product = products.value.find(p => p.id === pharmacyProductId && (p.sku || p.is_clinical !== undefined))
    return product?.name
  }
  const product = products.value.find(p => p.id === productId)
  return product?.name
}

const getProductCode = (productId, pharmacyProductId = null) => {
  if (pharmacyProductId) {
    const product = products.value.find(p => p.id === pharmacyProductId && (p.sku || p.is_clinical !== undefined))
    return product?.sku || product?.product_code || 'N/A'
  }
  const product = products.value.find(p => p.id === productId)
  return product?.product_code || 'N/A'
}

const getStatusSeverity = (status) => {
  const severityMap = {
    pending: 'warning',
    completed: 'success',
    cancelled: 'danger'
  }
  return severityMap[status] || 'info'
}

const getStockSeverity = (quantity) => {
  if (quantity <= 0) return 'danger'
  if (quantity <= 10) return 'warning'
  return 'success'
}

const getExpirySeverity = (date) => {
  if (!date) return 'info'
  const expiryDate = new Date(date)
  const today = new Date()
  const daysUntilExpiry = Math.floor((expiryDate - today) / (1000 * 60 * 60 * 24))

  if (daysUntilExpiry < 0) return 'danger'
  if (daysUntilExpiry < 30) return 'warning'
  return 'success'
}

const getExpiryIcon = (date) => {
  const severity = getExpirySeverity(date)
  if (severity === 'danger') return 'pi pi-times-circle'
  if (severity === 'warning') return 'pi pi-exclamation-triangle'
  return 'pi pi-check-circle'
}

const getRowClass = (data) => {
  const expirySeverity = getExpirySeverity(data.expiry_date)
  if (expirySeverity === 'danger') return 'tw-bg-red-50'
  if (expirySeverity === 'warning') return 'tw-bg-yellow-50'
  return ''
}

const calculateTotalQuantity = () => {
  return form.items.reduce((total, item) => total + (item.quantity || 0), 0)
}

const calculateTotalValue = () => {
  return form.items.reduce((total, item) => 
    total + ((item.quantity || 0) * (item.purchase_price || 0)), 0)
}

const countExpiringSoon = () => {
  return form.items.filter(item => 
    getExpirySeverity(item.expiry_date) === 'warning'
  ).length
}

const getServiceStockCount = () => {
  // Mock value - replace with actual API call
  return 125
}

const getServiceLowStockCount = () => {
  // Mock value - replace with actual API call  
  return 8
}

const getReadyItemsCount = () => {
  return form.items.filter(item => 
    item.product_id && item.quantity > 0
  ).length
}

const getIncompleteItemsCount = () => {
  return form.items.filter(item => 
    !item.product_id || !item.quantity
  ).length
}

const allItemsComplete = () => {
  return form.items.every(item => 
    item.product_id && item.quantity > 0
  )
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateForAPI = (date) => {
  if (!date) return null
  return date instanceof Date ? date.toISOString().split('T')[0] : date
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount || 0)
}

const viewProductHistory = (productId) => {
  router.push(`/purchasing/products/${productId}/history`)
}

const viewGoodsReceipt = () => {
  if (form.bon_reception_id) {
    router.push(`/bon-receptions/${form.bon_reception_id}`)
  }
}

const onAttachmentsUploaded = (files) => {
  console.log('Attachments uploaded:', files)
}

const onAttachmentError = (error) => {
  console.error('Attachment error:', error)
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchGoodsReceptions(),
    fetchProducts(),
    fetchServices()
  ])

  const queryBonReceptionId = router.currentRoute.value.query.bon_reception_id
  if (queryBonReceptionId) {
    form.bon_reception_id = parseInt(queryBonReceptionId)
    onGoodsReceptionChange()
  }
})

// Watchers
watch(() => form.items.length, () => {
  if (form.items.length > 0) {
    activeStep.value = Math.max(1, activeStep.value)
  }
})
</script>

<style scoped>
@reference "../../../../../css/app.css";

/* DataTable enhancements */
:deep(.p-datatable) {
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
}

:deep(.p-datatable .p-datatable-tbody > tr) {
}

/* Card styling */
:deep(.p-card) {
}

:deep(.p-card-title) {
}

/* TabView */
:deep(.p-tabview-nav) {
}

:deep(.p-tabview-nav-link) {
}

:deep(.p-tabview-nav-link.p-highlight) {
}

/* Dialog */
:deep(.p-dialog-header) {
}

:deep(.p-dialog-header .p-dialog-title) {
}

/* Steps */
:deep(.p-steps .p-steps-item.p-highlight .p-steps-number) {
}

/* Animations */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

.tw-animate-pulse-slow {
  animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.tw-animate-bounce {
  animation: bounce 1s infinite;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(-25%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}

/* Hover effects */
:deep(.p-button:not(:disabled):hover) {
}

:deep(.p-card:hover) {
}
</style>