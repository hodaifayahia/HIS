<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-to-slate-100 tw-p-4 lg:tw-p-6">
    <!-- Enhanced Header with Breadcrumb -->
    <div class="tw-mb-6">
      <Breadcrumb :model="breadcrumbItems" class="tw-mb-4">
        <template #item="{ item }">
          <span class="tw-text-gray-600 hover:tw-text-indigo-600 tw-transition-colors">{{ item.label }}</span>
        </template>
      </Breadcrumb>

      <Card class="tw-border-0 tw-shadow-2xl tw-overflow-hidden">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-700 tw-p-6 tw--m-6">
            <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-4">
              <div>
                <h1 class="tw-text-3xl tw-font-bold tw-text-white tw-flex tw-items-center tw-gap-3">
                  <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-backdrop-blur-sm tw-animate-pulse-slow">
                    <i class="pi pi-file-edit tw-text-2xl"></i>
                  </div>
                  Edit Bon Entree
                </h1>
                <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-3 tw-mt-3">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-text-indigo-100 tw-text-sm">Code:</span>
                    <span class="tw-bg-white/20 tw-px-4 tw-py-2 tw-rounded-full tw-text-white tw-font-bold tw-backdrop-blur-sm">
                      <i class="pi pi-tag tw-mr-2"></i>
                      {{ bonEntreeData?.bon_entree_code || 'Loading...' }}
                    </span>
                  </div>
                  <Tag 
                    v-if="bonEntreeData" 
                    :value="getStatusLabel(bonEntreeData.status)" 
                    :severity="getStatusSeverity(bonEntreeData.status)"
                    class="tw-font-semibold tw-px-4 tw-py-2"
                  />
                  <Tag 
                    v-if="bonEntreeData?.bon_reception" 
                    :value="`Receipt: ${bonEntreeData.bon_reception.bon_reception_code}`" 
                    severity="info"
                    icon="pi pi-link"
                  />
                </div>
              </div>
              <div class="tw-flex tw-gap-3">
                <Button 
                  @click="printBonEntree"
                  icon="pi pi-print"
                  label="Print"
                  class="p-button-help tw-shadow-lg"
                  size="large"
                />
                <Button 
                  @click="router.back()"
                  icon="pi pi-arrow-left"
                  label="Back"
                  class="p-button-secondary tw-shadow-lg hover:tw-scale-105 tw-transition-transform"
                  size="large"
                />
              </div>
            </div>

            <!-- Quick Stats Bar -->
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-3 tw-mt-6">
              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200">Total Items</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ form.items.length }}</p>
                  </div>
                  <i class="pi pi-box tw-text-indigo-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200">Total Quantity</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ calculateTotalQuantity() }}</p>
                  </div>
                  <i class="pi pi-hashtag tw-text-indigo-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200">Total Amount</p>
                    <p class="tw-text-lg tw-font-bold tw-text-white">{{ formatCurrency(calculateTotalAmount()) }}</p>
                  </div>
                  <i class="pi pi-dollar tw-text-indigo-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200">Service</p>
                    <p class="tw-text-sm tw-font-bold tw-text-white tw-truncate">{{ getServiceName() || 'Not Set' }}</p>
                  </div>
                  <i class="pi pi-building tw-text-indigo-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200">Attachments</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ bonEntreeData?.attachments?.length || 0 }}</p>
                  </div>
                  <i class="pi pi-paperclip tw-text-indigo-300 tw-text-xl"></i>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Progress Indicator -->
    <div class="tw-mb-6">
      <Steps :model="stepsItems" :readonly="true" :activeIndex="activeStep" />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-h-64">
      <Card class="tw-w-full tw-max-w-md tw-border-0 tw-shadow-2xl">
        <template #content>
          <div class="tw-text-center tw-py-8">
            <ProgressSpinner 
              strokeWidth="4"
              animationDuration="1s"
              class="tw-w-16 tw-h-16"
            />
            <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">Loading bon entree data...</p>
            <ProgressBar :value="loadingProgress" :showValue="false" class="tw-mt-4" />
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Form -->
    <form v-else-if="bonEntreeData" @submit.prevent="submitForm" class="tw-space-y-6">

      <!-- Tabbed Content -->
      <TabView class="tw-shadow-xl tw-rounded-xl">
        <!-- Basic Information Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-info-circle tw-mr-2"></i>
            Basic Information
          </template>

          <div class="tw-space-y-6">
            <!-- Status and Service -->
            <Card class="tw-border tw-shadow-sm">
              <template #content>
                <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6">
                  <!-- Bon Reception Code -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-receipt tw-text-gray-500"></i>
                      Bon Reception Code
                    </label>
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <InputText
                        :value="bonEntreeData.bon_reception?.bon_reception_code || 'N/A'"
                        disabled
                        class="tw-flex-1 tw-bg-gray-50"
                      />
                      <Button 
                        v-if="bonEntreeData.bon_reception"
                        @click="viewBonReception"
                        icon="pi pi-external-link"
                        class="p-button-text p-button-sm"
                        v-tooltip="'View Bon Reception'"
                      />
                    </div>
                  </div>

                  <!-- Service Selection -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-building tw-text-gray-500"></i>
                      Service <span class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      v-model="form.service_id"
                      :options="services"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select a Service"
                      class="tw-w-full"
                      :loading="loadingServices"
                      :disabled="bonEntreeData.status === 'transferred'"
                      filter
                      showClear
                      :virtualScrollerOptions="{ itemSize: 40 }"
                    >
                      <template #option="slotProps">
                        <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-indigo-50 tw-p-2 tw-rounded">
                          <Avatar 
                            icon="pi pi-building"
                            class="tw-bg-indigo-100 tw-text-indigo-700"
                            shape="circle"
                            size="small"
                          />
                          <div>
                            <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                            <div class="tw-text-xs tw-text-gray-500">ID: {{ slotProps.option.service_id }}</div>
                          </div>
                        </div>
                      </template>
                    </Dropdown>
                    <small v-if="errors.service_id" class="tw-text-red-500 tw-text-xs">
                      <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.service_id[0] }}
                    </small>
                  </div>

                  <!-- Status Management -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-flag tw-text-gray-500"></i>
                      Status Management
                    </label>
                    <div class="tw-space-y-2">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <Tag 
                          :value="getStatusLabel(bonEntreeData.status)" 
                          :severity="getStatusSeverity(bonEntreeData.status)"
                          class="tw-text-lg tw-px-4 tw-py-2 tw-flex-1 tw-justify-center"
                        />
                      </div>
                      <div class="tw-flex tw-gap-2">
                        <Button 
                          v-if="bonEntreeData.status === 'draft'"
                          @click="validateBonEntree"
                          icon="pi pi-check-circle"
                          label="Validate"
                          class="p-button-success tw-flex-1"
                          size="small"
                        />
                        <Button 
                          v-if="bonEntreeData.status === 'validated'"
                          @click="transferToStock"
                          icon="pi pi-send"
                          label="Transfer to Stock"
                          class="p-button-warning tw-flex-1"
                          size="small"
                        />
                        <Button 
                          v-if="bonEntreeData.status === 'draft'"
                          @click="cancelBonEntree"
                          icon="pi pi-times"
                          label="Cancel"
                          severity="danger"
                          class="tw-flex-1"
                          size="small"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Notes Section -->
                <Divider />
                <div class="tw-space-y-2">
                  <label class="tw-flex tw-items-center tw-justify-between">
                    <span class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-comment tw-text-gray-500"></i>
                      Notes
                    </span>
                    <span class="tw-text-xs tw-text-gray-500">
                      {{ form.notes.length }}/500 characters
                    </span>
                  </label>
                  <Textarea
                    v-model="form.notes"
                    rows="3"
                    :maxlength="500"
                    placeholder="Add any additional notes..."
                    class="tw-w-full"
                    :disabled="bonEntreeData.status === 'transferred'"
                    autoResize
                  />
                  <small v-if="errors.notes" class="tw-text-red-500 tw-text-xs">
                    <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.notes[0] }}
                  </small>
                </div>
              </template>
            </Card>

            <!-- Supplier Information Card -->
            <Card v-if="bonEntreeData.fournisseur" class="tw-border tw-shadow-sm tw-bg-gradient-to-br tw-from-indigo-50 tw-to-purple-50">
              <template #title>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-indigo-800">
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
                        :label="bonEntreeData.fournisseur.company_name?.charAt(0)" 
                        class="tw-bg-indigo-100 tw-text-indigo-700"
                        shape="circle"
                      />
                      <div class="tw-text-lg tw-font-semibold tw-text-gray-800">
                        {{ bonEntreeData.fournisseur.company_name || 'N/A' }}
                      </div>
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Contact</label>
                    <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mt-2">
                      <i class="pi pi-user tw-mr-2 tw-text-gray-400"></i>
                      {{ bonEntreeData.fournisseur.contact_person || 'N/A' }}
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Phone</label>
                    <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mt-2">
                      <i class="pi pi-phone tw-mr-2 tw-text-gray-400"></i>
                      {{ bonEntreeData.fournisseur.phone || 'N/A' }}
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Email</label>
                    <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mt-2 tw-truncate">
                      <i class="pi pi-envelope tw-mr-2 tw-text-gray-400"></i>
                      {{ bonEntreeData.fournisseur.email || 'N/A' }}
                    </div>
                  </div>
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

          <div class="tw-space-y-4">
            <!-- Products Toolbar -->
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
              <div class="tw-flex tw-items-center tw-gap-4">
                <!-- Search for existing items -->
                <div v-if="form.items.length > 5" class="tw-relative">
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
                    icon="pi pi-table"
                    :class="viewMode === 'table' ? 'p-button-primary' : 'p-button-text'"
                    @click="viewMode = 'table'"
                    size="small"
                    v-tooltip="'Table View'"
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
                  v-if="bonEntreeData.status !== 'transferred'"
                  @click="importFromExcel"
                  icon="pi pi-file-excel"
                  label="Import Excel"
                  class="p-button-help"
                  size="small"
                />
                <Button 
                  v-if="bonEntreeData.status !== 'transferred'"
                  @click="showProductModal = true"
                  icon="pi pi-plus"
                  label="Add Products"
                  class="p-button-success"
                />
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="form.items.length === 0" class="tw-bg-gray-50 tw-rounded-xl tw-border-2 tw-border-dashed tw-border-gray-300 tw-p-12">
              <div class="tw-text-center">
                <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-animate-bounce"></i>
                <p class="tw-mt-4 tw-text-gray-500 tw-text-lg">No products added yet</p>
                <p class="tw-text-gray-400 tw-mt-2">Start by adding products to this bon entree</p>
                <Button 
                  v-if="bonEntreeData.status !== 'transferred'"
                  @click="showProductModal = true"
                  icon="pi pi-plus"
                  label="Add First Product"
                  class="p-button-outlined tw-mt-4"
                />
              </div>
            </div>

            <!-- Table View -->
            <DataTable 
              v-else-if="viewMode === 'table'"
              :value="filteredItems"
              :paginator="form.items.length > 10"
              :rows="10"
              :rowsPerPageOptions="[10, 25, 50, 100]"
              paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
              responsiveLayout="scroll"
              class="tw-mt-4"
              :editMode="bonEntreeData.status !== 'transferred' ? 'cell' : null"
              @cell-edit-complete="onCellEditComplete"
              :rowClass="getRowClass"
              showGridlines
              :resizableColumns="true"
              columnResizeMode="expand"
            >
              <!-- Selection Column -->
              <Column v-if="bonEntreeData.status !== 'transferred'" selectionMode="multiple" style="width: 3rem"></Column>

              <!-- Index Column -->
              <Column header="#" style="width: 50px" :exportable="false">
                <template #body="slotProps">
                  <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm tw-shadow">
                    {{ slotProps.index + 1 }}
                  </div>
                </template>
              </Column>

              <!-- Product Column -->
              <Column field="product_id" header="Product" :sortable="true" style="min-width: 250px">
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <Avatar 
                      :label="getProductById(slotProps.data.product_id)?.name?.charAt(0)" 
                      class="tw-bg-purple-100 tw-text-purple-700"
                      shape="square"
                    />
                    <div>
                      <div class="tw-font-semibold">{{ getProductById(slotProps.data.product_id)?.name || 'Select Product' }}</div>
                      <div class="tw-text-xs tw-text-gray-500">
                        Code: {{ getProductById(slotProps.data.product_id)?.product_code }}
                        <Tag 
                          v-if="getProductById(slotProps.data.product_id)?.category"
                          :value="getProductById(slotProps.data.product_id)?.category" 
                          severity="info"
                          class="tw-ml-2"
                        />
                      </div>
                    </div>
                  </div>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <Dropdown
                    v-model="slotProps.data.product_id"
                    :options="products"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Product"
                    class="tw-w-full"
                    filter
                    :virtualScrollerOptions="{ itemSize: 50 }"
                  >
                    <template #option="optionProps">
                      <div class="tw-py-2 hover:tw-bg-indigo-50 tw-px-2 tw-rounded">
                        <div class="tw-font-medium">{{ optionProps.option.name }}</div>
                        <div class="tw-text-xs tw-text-gray-500">Code: {{ optionProps.option.product_code }}</div>
                      </div>
                    </template>
                  </Dropdown>
                </template>
              </Column>

              <!-- Quantity Column -->
              <Column field="quantity" header="Quantity" :sortable="true" style="width: 120px">
                <template #body="slotProps">
                  <Tag :value="slotProps.data.quantity" severity="info" class="tw-font-bold" />
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <InputNumber
                    v-model="slotProps.data.quantity"
                    :min="1"
                    :max="9999"
                    showButtons
                    buttonLayout="vertical"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Purchase Price Column -->
              <Column field="purchase_price" header="Purchase Price" :sortable="true" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-font-medium">{{ formatCurrency(slotProps.data.purchase_price) }}</span>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <InputNumber
                    v-model="slotProps.data.purchase_price"
                    :min="0"
                    mode="currency"
                    currency="DZD"
                    locale="fr-FR"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Batch Number Column -->
              <Column field="batch_number" header="Batch #" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-text-sm tw-font-mono">{{ slotProps.data.batch_number || '-' }}</span>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
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
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <Calendar
                    v-model="slotProps.data.expiry_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Select date"
                    class="tw-w-full"
                    showIcon
                  />
                </template>
              </Column>

              <!-- TVA Column -->
              <Column field="tva" header="TVA %" :sortable="true" style="width: 100px">
                <template #body="slotProps">
                  <span>{{ slotProps.data.tva || 0 }}%</span>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <InputNumber
                    v-model="slotProps.data.tva"
                    :min="0"
                    :max="100"
                    suffix="%"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Total Column -->
              <Column header="Total" :sortable="true" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-font-bold tw-text-green-600 tw-text-lg">
                    {{ formatCurrency(calculateItemTotal(slotProps.data)) }}
                  </span>
                </template>
              </Column>

              <!-- Actions Column -->
              <Column v-if="bonEntreeData.status !== 'transferred'" header="Actions" style="width: 120px" :exportable="false">
                <template #body="slotProps">
                  <div class="tw-flex tw-gap-1">
                    <Button 
                      @click="editItemDetails(slotProps.data)"
                      icon="pi pi-pencil"
                      class="p-button-text p-button-sm p-button-info"
                      v-tooltip="'Edit Details'"
                    />
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

              <!-- Footer Template -->
              <template #footer>
                <div class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-p-4 tw-rounded-lg">
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <div class="tw-text-gray-600">
                      <span class="tw-font-medium">Total Items:</span> {{ form.items.length }}
                      <span class="tw-mx-4">|</span>
                      <span class="tw-font-medium">Total Quantity:</span> {{ calculateTotalQuantity() }}
                    </div>
                    <div class="tw-text-2xl tw-font-bold tw-text-indigo-600">
                      Grand Total: {{ formatCurrency(calculateTotalAmount()) }}
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
                <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-p-3">
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-white tw-font-bold">Item #{{ index + 1 }}</span>
                    <div class="tw-flex tw-gap-1">
                      <Button 
                        @click="editItemDetails(item)"
                        icon="pi pi-pencil"
                        class="p-button-text p-button-sm tw-text-white"
                        v-tooltip="'Edit'"
                      />
                      <Button 
                        @click="removeItem(index)"
                        icon="pi pi-trash"
                        class="p-button-text p-button-sm tw-text-white"
                        v-tooltip="'Remove'"
                      />
                    </div>
                  </div>
                </div>
                <div class="tw-p-4 tw-space-y-3">
                  <div>
                    <label class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wider">Product</label>
                    <div class="tw-font-semibold tw-text-gray-800">
                      {{ getProductById(item.product_id)?.name || 'Unknown' }}
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
                    <span class="tw-text-lg tw-font-bold tw-text-green-600">
                      {{ formatCurrency(calculateItemTotal(item)) }}
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
            <Badge :value="bonEntreeData?.attachments?.length || 0" class="tw-ml-2" severity="warning" />
          </template>

          <Card class="tw-border-0">
            <template #content>
              <AttachmentUploader
                v-model="bonEntreeData.attachments"
                model-type="bon_entree"
                :model-id="bonEntreeData.id"
                :disabled="bonEntreeData.status === 'transferred'"
                @uploaded="onAttachmentsUpdated"
                @deleted="onAttachmentsUpdated"
              />
            </template>
          </Card>
        </TabPanel>

        <!-- History Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-history tw-mr-2"></i>
            History
          </template>

          <Timeline :value="historyEvents" align="alternate" class="tw-mt-6">
            <template #marker="slotProps">
              <span class="tw-flex tw-w-10 tw-h-10 tw-items-center tw-justify-center tw-text-white tw-rounded-full tw-z-10"
                    :style="{ backgroundColor: getEventColor(slotProps.item.type) }">
                <i :class="slotProps.item.icon"></i>
              </span>
            </template>
            <template #content="slotProps">
              <Card class="tw-shadow-md">
                <template #content>
                  <div class="tw-text-sm tw-text-gray-500 tw-mb-1">
                    {{ formatDateTime(slotProps.item.date) }}
                  </div>
                  <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.item.title }}</div>
                  <div class="tw-text-sm tw-text-gray-600 tw-mt-1">{{ slotProps.item.description }}</div>
                  <div v-if="slotProps.item.user" class="tw-text-xs tw-text-gray-500 tw-mt-2">
                    <i class="pi pi-user tw-mr-1"></i>{{ slotProps.item.user }}
                  </div>
                </template>
              </Card>
            </template>
          </Timeline>
        </TabPanel>
      </TabView>

      <!-- Summary Card -->
      <Card class="tw-border-0 tw-shadow-xl tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50">
        <template #content>
          <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-5 tw-gap-4">
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Subtotal</div>
              <div class="tw-text-xl tw-font-bold tw-text-gray-800">{{ formatCurrency(calculateSubtotal()) }}</div>
            </div>
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total TVA</div>
              <div class="tw-text-xl tw-font-bold tw-text-blue-600">{{ formatCurrency(calculateTotalTVA()) }}</div>
            </div>
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Items Count</div>
              <div class="tw-text-xl tw-font-bold tw-text-purple-600">{{ form.items.length }}</div>
            </div>
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Quantity</div>
              <div class="tw-text-xl tw-font-bold tw-text-indigo-600">{{ calculateTotalQuantity() }}</div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-rounded-lg tw-p-4 tw-shadow-sm tw-text-white">
              <div class="tw-text-sm tw-text-green-100 tw-mb-1">Total Amount</div>
              <div class="tw-text-2xl tw-font-bold">{{ formatCurrency(calculateTotalAmount()) }}</div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Form Actions -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #content>
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4 tw-text-sm tw-text-gray-500">
              <div>
                <i class="pi pi-calendar tw-mr-1"></i>
                Created: {{ formatDateTime(bonEntreeData.created_at) }}
              </div>
              <div>
                <i class="pi pi-refresh tw-mr-1"></i>
                Updated: {{ formatDateTime(bonEntreeData.updated_at) }}
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
                v-if="bonEntreeData.status !== 'transferred'"
                type="button"
                @click="saveAsDraft"
                :loading="savingDraft"
                label="Save as Draft"
                icon="pi pi-save"
                class="p-button-info"
                size="large"
              />
              <Button 
                v-if="bonEntreeData.status !== 'transferred'"
                type="submit"
                :loading="saving"
                label="Update Bon Entree"
                icon="pi pi-check"
                class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-700"
                size="large"
              />
            </div>
          </div>
        </template>
      </Card>
    </form>

    <!-- Product Selection Modal -->
    <Dialog 
      v-model:visible="showProductModal" 
      header="Add Products" 
      :modal="true"
      :style="{ width: '90vw', maxWidth: '1400px' }"
      :maximizable="true"
    >
      <div class="tw-space-y-4">
        <!-- Search and Filters Bar -->
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4">
          <div class="tw-flex-1 tw-relative">
            <InputText 
              v-model="productSearchQuery"
              placeholder="Search products by name, code, or category..."
              class="tw-w-full tw-pl-10"
              @input="filterProducts"
            />
            <i class="pi pi-search tw-absolute tw-left-3 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
          </div>
          <Dropdown
            v-model="selectedCategory"
            :options="categoryOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Categories"
            class="tw-w-48"
            showClear
            @change="filterProducts"
          />
          <Button 
            @click="clearProductSelection"
            icon="pi pi-refresh"
            label="Clear"
            class="p-button-secondary"
          />
        </div>

        <!-- Products DataTable -->
        <DataTable 
          v-model:selection="selectedProducts"
          :value="filteredProducts"
          :paginator="true"
          :rows="10"
          :rowsPerPageOptions="[10, 25, 50, 100]"
          selectionMode="multiple"
          :metaKeySelection="false"
          dataKey="id"
          :loading="loadingProducts"
          :virtualScrollerOptions="{ itemSize: 50 }"
          responsiveLayout="scroll"
          class="tw-border tw-rounded-lg"
        >
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center">
              <span class="tw-text-lg tw-font-semibold">Available Products</span>
              <div class="tw-flex tw-gap-2">
                <Tag :value="`${filteredProducts.length} products`" severity="info" />
                <Tag :value="`${selectedProducts.length} selected`" severity="success" />
              </div>
            </div>
          </template>

          <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

          <Column field="product_code" header="Code" :sortable="true" style="width: 120px">
            <template #body="slotProps">
              <span class="tw-font-mono tw-text-sm">{{ slotProps.data.product_code }}</span>
            </template>
          </Column>

          <Column field="name" header="Product Name" :sortable="true">
            <template #body="slotProps">
              <div>
                <div class="tw-font-semibold">{{ slotProps.data.name }}</div>
                <div class="tw-text-xs tw-text-gray-500">{{ slotProps.data.description }}</div>
              </div>
            </template>
          </Column>

          <Column field="category" header="Category" :sortable="true" style="width: 150px">
            <template #body="slotProps">
              <Tag :value="slotProps.data.category || 'Uncategorized'" />
            </template>
          </Column>

          <Column field="stock_quantity" header="Current Stock" :sortable="true" style="width: 120px">
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.stock_quantity || 0" 
                :severity="getStockSeverity(slotProps.data.stock_quantity)"
              />
            </template>
          </Column>

          <Column field="purchase_price" header="Price" :sortable="true" style="width: 150px">
            <template #body="slotProps">
              <span class="tw-font-medium">{{ formatCurrency(slotProps.data.purchase_price) }}</span>
            </template>
          </Column>
        </DataTable>

        <!-- Quick Settings for Selected Products -->
        <div v-if="selectedProducts.length > 0" class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-p-4 tw-rounded-lg tw-border tw-border-indigo-200">
          <h4 class="tw-font-semibold tw-mb-3 tw-text-indigo-800">
            <i class="pi pi-cog tw-mr-2"></i>
            Quick Settings for Selected Products
          </h4>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Default Quantity</label>
              <InputNumber
                v-model="defaultItemSettings.quantity"
                :min="1"
                showButtons
                class="tw-w-full tw-mt-1"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Default TVA %</label>
              <InputNumber
                v-model="defaultItemSettings.tva"
                :min="0"
                :max="100"
                suffix="%"
                class="tw-w-full tw-mt-1"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Storage Location</label>
              <InputText
                v-model="defaultItemSettings.storage_name"
                placeholder="Storage location"
                class="tw-w-full tw-mt-1"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Batch Number</label>
              <InputText
                v-model="defaultItemSettings.batch_number"
                placeholder="Batch number"
                class="tw-w-full tw-mt-1"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Price Multiplier</label>
              <InputNumber
                v-model="defaultItemSettings.priceMultiplier"
                :min="0"
                :max="10"
                :step="0.1"
                prefix="x"
                class="tw-w-full tw-mt-1"
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-between tw-items-center">
          <span class="tw-text-sm tw-text-gray-500">
            {{ selectedProducts.length }} product(s) selected
            <span v-if="selectedProducts.length > 0" class="tw-ml-2">
              | Estimated Total: {{ formatCurrency(calculateEstimatedTotal()) }}
            </span>
          </span>
          <div class="tw-flex tw-gap-2">
            <Button 
              label="Cancel" 
              icon="pi pi-times" 
              @click="showProductModal = false" 
              class="p-button-text"
            />
            <Button 
              label="Add Selected Products" 
              icon="pi pi-plus" 
              @click="addSelectedProducts" 
              :disabled="selectedProducts.length === 0"
              class="p-button-success"
            />
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
              locale="fr-FR"
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
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Serial Number</label>
            <InputText
              v-model="editingItem.serial_number"
              placeholder="Serial number"
              class="tw-w-full"
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
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
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">TVA (%)</label>
            <InputNumber
              v-model="editingItem.tva"
              :min="0"
              :max="100"
              suffix="%"
              class="tw-w-full"
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

        <!-- Live Total Display -->
        <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-p-4 tw-rounded-lg tw-border tw-border-green-200">
          <div class="tw-flex tw-justify-between tw-items-center">
            <span class="tw-text-gray-700 tw-font-medium">Item Total (with TVA):</span>
            <span class="tw-text-2xl tw-font-bold tw-text-green-600">
              {{ formatCurrency(calculateItemTotal(editingItem)) }}
            </span>
          </div>
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
import { useRouter, useRoute } from 'vue-router'
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
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import Divider from 'primevue/divider'
import Steps from 'primevue/steps'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Badge from 'primevue/badge'
import ProgressBar from 'primevue/progressbar'
import Timeline from 'primevue/timeline'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

// Custom Components
import AttachmentUploader from '@/Components/AttachmentUploader.vue'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

// Props from route
const bonEntreeId = route.params.id

// State
const loading = ref(true)
const saving = ref(false)
const savingDraft = ref(false)
const loadingProducts = ref(false)
const loadingServices = ref(false)
const loadingProgress = ref(0)
const activeStep = ref(0)

const bonEntreeData = ref(null)
const products = ref([])
const services = ref([])
const errors = ref({})

const form = reactive({
  service_id: null,
  notes: '',
  items: []
})

// View and search
const viewMode = ref('table')
const itemSearchQuery = ref('')
const productSearchQuery = ref('')
const selectedCategory = ref(null)
const selectedProducts = ref([])
const filteredProducts = ref([])

// Modals
const showProductModal = ref(false)
const showItemDetailsModal = ref(false)
const editingItem = ref(null)

// Default settings
const defaultItemSettings = reactive({
  quantity: 1,
  tva: 0,
  storage_name: '',
  batch_number: '',
  priceMultiplier: 1.0
})

// Options
const categoryOptions = [
  { label: 'All Categories', value: null },
  { label: 'Medical Supplies', value: 'Medical Supplies' },
  { label: 'Equipment', value: 'Equipment' },
  { label: 'Medication', value: 'Medication' },
  { label: 'Consumables', value: 'Consumables' },
  { label: 'Laboratory', value: 'Laboratory' },
  { label: 'Others', value: 'Others' }
]

// Steps
const stepsItems = [
  { label: 'Basic Info', icon: 'pi pi-info-circle' },
  { label: 'Products', icon: 'pi pi-box' },
  { label: 'Attachments', icon: 'pi pi-paperclip' },
  { label: 'Review', icon: 'pi pi-check' }
]

// History events (mock data - replace with API call)
const historyEvents = computed(() => [
  {
    type: 'created',
    title: 'Bon Entree Created',
    description: 'Initial creation from Bon Reception',
    date: bonEntreeData.value?.created_at,
    user: 'System',
    icon: 'pi pi-plus'
  },
  {
    type: 'updated',
    title: 'Last Updated',
    description: 'Form data updated',
    date: bonEntreeData.value?.updated_at,
    user: 'Current User',
    icon: 'pi pi-pencil'
  }
])

// Breadcrumb
const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Bon Entrees', to: '/bon-entrees' },
  { label: bonEntreeData.value?.bon_entree_code || 'Loading...', disabled: true }
])

// Computed
const filteredItems = computed(() => {
  if (!itemSearchQuery.value) return form.items

  const query = itemSearchQuery.value.toLowerCase()
  return form.items.filter(item => {
    const product = getProductById(item.product_id)
    return product?.name?.toLowerCase().includes(query) || 
           item.batch_number?.toLowerCase().includes(query)
  })
})

// Methods continue in next part...
// ... continuing methods
const fetchBonEntree = async () => {
  try {
    loading.value = true
    loadingProgress.value = 30

    const response = await axios.get(`/api/bon-entrees/${bonEntreeId}`)
    loadingProgress.value = 60

    if (response.data.status === 'success') {
      bonEntreeData.value = response.data.data

      // Populate form data
      form.service_id = bonEntreeData.value.service_id
      form.notes = bonEntreeData.value.notes || ''
      form.items = bonEntreeData.value.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        product_name: item.product?.name,
        quantity: item.quantity,
        purchase_price: item.purchase_price || 0,
        sell_price: item.sell_price || 0,
        tva: item.tva || 0,
        batch_number: item.batch_number || '',
        serial_number: item.serial_number || '',
        expiry_date: item.expiry_date ? new Date(item.expiry_date) : null,
        storage_name: item.storage_name || '',
        remarks: item.remarks || ''
      }))

      loadingProgress.value = 100
      activeStep.value = form.items.length > 0 ? 1 : 0
    }
  } catch (err) {
    console.error('Error fetching bon entree:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load bon entree data',
      life: 3000
    })
    router.back()
  } finally {
    loading.value = false
  }
}

const fetchProducts = async () => {
  try {
    loadingProducts.value = true
    const response = await axios.get('/api/products')
    products.value = response.data.data || response.data
    filteredProducts.value = products.value
  } catch (err) {
    console.error('Error fetching products:', err)
  } finally {
    loadingProducts.value = false
  }
}

const fetchServices = async () => {
  try {
    loadingServices.value = true
    const response = await axios.get('/api/services')
    services.value = response.data.data || response.data
  } catch (err) {
    console.error('Error fetching services:', err)
  } finally {
    loadingServices.value = false
  }
}

const filterProducts = () => {
  let filtered = products.value

  if (productSearchQuery.value) {
    const query = productSearchQuery.value.toLowerCase()
    filtered = filtered.filter(product => 
      product.name?.toLowerCase().includes(query) || 
      product.product_code?.toLowerCase().includes(query) ||
      product.category?.toLowerCase().includes(query)
    )
  }

  if (selectedCategory.value) {
    filtered = filtered.filter(product => product.category === selectedCategory.value)
  }

  filteredProducts.value = filtered
}

const clearProductSelection = () => {
  selectedProducts.value = []
  productSearchQuery.value = ''
  selectedCategory.value = null
  filterProducts()
}

const addSelectedProducts = () => {
  selectedProducts.value.forEach(product => {
    form.items.push({
      product_id: product.id,
      product_name: product.name,
      quantity: defaultItemSettings.quantity,
      purchase_price: (product.purchase_price || 0) * defaultItemSettings.priceMultiplier,
      sell_price: product.sell_price || 0,
      tva: defaultItemSettings.tva,
      batch_number: defaultItemSettings.batch_number,
      serial_number: '',
      expiry_date: null,
      storage_name: defaultItemSettings.storage_name,
      remarks: ''
    })
  })

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${selectedProducts.value.length} product(s) added`,
    life: 3000
  })

  showProductModal.value = false
  clearProductSelection()
}

const editItemDetails = (item) => {
  editingItem.value = { ...item }
  showItemDetailsModal.value = true
}

const saveItemDetails = () => {
  const index = form.items.findIndex(item => 
    (item.id === editingItem.value.id) || 
    (item.product_id === editingItem.value.product_id && !item.id && !editingItem.value.id)
  )

  if (index !== -1) {
    form.items[index] = { ...editingItem.value }
  }

  showItemDetailsModal.value = false
  editingItem.value = null
}

const duplicateItem = (item) => {
  const newItem = { ...item }
  delete newItem.id
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
      toast.add({
        severity: 'success',
        summary: 'Removed',
        detail: 'Product removed successfully',
        life: 2000
      })
    }
  })
}

const onCellEditComplete = (event) => {
  let { data, newValue, field } = event
  data[field] = newValue
}

const importFromExcel = () => {
  toast.add({
    severity: 'info',
    summary: 'Coming Soon',
    detail: 'Excel import feature will be available soon',
    life: 3000
  })
}

// Status management
const validateBonEntree = () => {
  confirm.require({
    message: 'Are you sure you want to validate this bon entree? This action cannot be undone.',
    header: 'Validate Bon Entree',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: async () => {
      try {
        const response = await axios.post(`/api/bon-entrees/${bonEntreeId}/validate`)

        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon entree validated successfully',
            life: 3000
          })
          fetchBonEntree()
        }
      } catch (err) {
        console.error('Error validating bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to validate bon entree',
          life: 3000
        })
      }
    }
  })
}

const transferToStock = () => {
  confirm.require({
    message: 'Are you sure you want to transfer this bon entree to stock? This action cannot be undone.',
    header: 'Transfer to Stock',
    icon: 'pi pi-send',
    acceptClass: 'p-button-warning',
    accept: async () => {
      try {
        const response = await axios.post(`/api/bon-entrees/${bonEntreeId}/transfer-to-stock`)

        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon entree transferred to stock successfully',
            life: 3000
          })
          fetchBonEntree()
        }
      } catch (err) {
        console.error('Error transferring bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to transfer bon entree to stock',
          life: 3000
        })
      }
    }
  })
}

const cancelBonEntree = () => {
  confirm.require({
    message: 'Are you sure you want to cancel this bon entree?',
    header: 'Cancel Bon Entree',
    icon: 'pi pi-times-circle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.post(`/api/bon-entrees/${bonEntreeId}/cancel`)

        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon entree cancelled successfully',
            life: 3000
          })
          fetchBonEntree()
        }
      } catch (err) {
        console.error('Error cancelling bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to cancel bon entree',
          life: 3000
        })
      }
    }
  })
}

const saveAsDraft = async () => {
  try {
    savingDraft.value = true
    await submitForm(true)
  } finally {
    savingDraft.value = false
  }
}

const submitForm = async (asDraft = false) => {
  try {
    saving.value = true
    errors.value = {}

    const payload = {
      service_id: form.service_id,
      notes: form.notes,
      status: asDraft ? 'draft' : bonEntreeData.value.status,
      items: form.items.map(item => ({
        ...item,
        expiry_date: item.expiry_date ? formatDateForAPI(item.expiry_date) : null
      }))
    }

    const response = await axios.put(`/api/bon-entrees/${bonEntreeId}`, payload)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: asDraft ? 'Bon entree saved as draft' : 'Bon entree updated successfully',
        life: 3000
      })

      fetchBonEntree()
    }
  } catch (err) {
    console.error('Error updating bon entree:', err)

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
        detail: err.response?.data?.message || 'Failed to update bon entree',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

// Calculation methods
const calculateItemTotal = (item) => {
  const subtotal = (item.quantity || 0) * (item.purchase_price || 0)
  const tvaAmount = subtotal * ((item.tva || 0) / 100)
  return subtotal + tvaAmount
}

const calculateSubtotal = () => {
  return form.items.reduce((total, item) => {
    return total + ((item.quantity || 0) * (item.purchase_price || 0))
  }, 0)
}

const calculateTotalTVA = () => {
  return form.items.reduce((total, item) => {
    const itemSubtotal = (item.quantity || 0) * (item.purchase_price || 0)
    return total + (itemSubtotal * ((item.tva || 0) / 100))
  }, 0)
}

const calculateTotalQuantity = () => {
  return form.items.reduce((total, item) => total + (item.quantity || 0), 0)
}

const calculateTotalAmount = () => {
  return calculateSubtotal() + calculateTotalTVA()
}

const calculateEstimatedTotal = () => {
  return selectedProducts.value.reduce((total, product) => {
    const price = (product.purchase_price || 0) * defaultItemSettings.priceMultiplier
    const subtotal = defaultItemSettings.quantity * price
    const tva = subtotal * (defaultItemSettings.tva / 100)
    return total + subtotal + tva
  }, 0)
}

// Utility functions
const getProductById = (id) => {
  return products.value.find(p => p.id === id)
}

const getServiceName = () => {
  const service = services.value.find(s => s.id === form.service_id)
  return service?.name
}

const getStatusLabel = (status) => {
  const statusMap = {
    draft: 'Draft',
    validated: 'Validated',
    transferred: 'Transferred',
    cancelled: 'Cancelled'
  }
  return statusMap[status] || status
}

const getStatusSeverity = (status) => {
  const severityMap = {
    draft: 'info',
    validated: 'success',
    transferred: 'warning',
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

const getEventColor = (type) => {
  const colors = {
    created: '#10b981',
    updated: '#3b82f6',
    validated: '#8b5cf6',
    transferred: '#f59e0b',
    cancelled: '#ef4444'
  }
  return colors[type] || '#6b7280'
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return 'DZD 0.00'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('fr-FR')
}

const formatDateTime = (date) => {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleString('fr-FR')
}

const formatDateForAPI = (date) => {
  if (!date) return null
  return date instanceof Date ? date.toISOString().split('T')[0] : date
}

const onAttachmentsUpdated = () => {
  toast.add({
    severity: 'info',
    summary: 'Attachments Updated',
    detail: 'Document attachments have been updated',
    life: 2000
  })
}

const viewBonReception = () => {
  if (bonEntreeData.value?.bon_reception?.id) {
    router.push(`/bon-receptions/${bonEntreeData.value.bon_reception.id}`)
  }
}

const printBonEntree = () => {
  toast.add({
    severity: 'info',
    summary: 'Print',
    detail: 'Print feature will be available soon',
    life: 3000
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchBonEntree(),
    fetchProducts(),
    fetchServices()
  ])
})

// Watchers
watch(productSearchQuery, () => filterProducts())
watch(selectedCategory, () => filterProducts())
watch(() => form.items.length, () => {
  if (form.items.length > 0) activeStep.value = 1
})
</script>

<style scoped>
/* DataTable enhancements */
:deep(.p-datatable) {
  @apply tw-border-0 tw-rounded-lg;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-text-gray-700 tw-font-semibold tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply hover:tw-bg-indigo-50 tw-transition-colors;
}

/* Card styling */
:deep(.p-card) {
  @apply tw-rounded-xl;
}

:deep(.p-card-title) {
  @apply tw-text-xl tw-font-bold tw-text-gray-800;
}

/* TabView */
:deep(.p-tabview-nav) {
  @apply tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100;
}

:deep(.p-tabview-nav-link) {
  @apply hover:tw-bg-indigo-50;
}

:deep(.p-tabview-nav-link.p-highlight) {
  @apply tw-bg-white tw-border-indigo-600;
}

/* Dialog */
:deep(.p-dialog-header) {
  @apply tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-700 tw-text-white;
}

:deep(.p-dialog-header .p-dialog-title) {
  @apply tw-text-white tw-font-bold;
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
  @apply tw-transform tw-scale-105 tw-transition-transform;
}

:deep(.p-card:hover) {
  @apply tw-shadow-2xl tw-transition-shadow;
}
</style>