<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-to-slate-100 tw-p-4 lg:tw-p-6">
    <!-- Enhanced Header with Breadcrumb -->
    <div class="tw-mb-6">
      <Breadcrumb :model="breadcrumbItems" class="tw-mb-4">
        <template #item="{ item }">
          <span class="tw-text-gray-600 hover:tw-text-emerald-600 tw-transition-colors">{{ item.label }}</span>
        </template>
      </Breadcrumb>

      <Card class="tw-border-0 tw-shadow-2xl tw-overflow-hidden">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-emerald-600 tw-to-teal-700 tw-p-6 tw--m-6">
            <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-start lg:tw-justify-between tw-gap-4">
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-backdrop-blur-sm">
                  <i class="pi pi-file-edit tw-text-3xl tw-text-white"></i>
                </div>
                <div>
                  <h1 class="tw-text-3xl tw-font-bold tw-text-white tw-flex tw-items-center tw-gap-3">
                    {{ factureProforma?.factureProformaCode || `FP-${factureProforma?.id}` || 'Loading...' }}
                    <Tag 
                      v-if="isConfirmed" 
                      value="Confirmed" 
                      severity="success" 
                      icon="pi pi-lock"
                      class="tw-font-semibold"
                    />
                  </h1>
                  <p class="tw-text-emerald-100 tw-mt-1">
                    <i class="pi pi-building tw-mr-2"></i>
                    {{ factureProforma?.fournisseur?.company_name || 'No Supplier' }}
                  </p>
                </div>
              </div>

              <div class="tw-flex tw-flex-wrap tw-gap-2">
                <Button 
                  @click="goBack" 
                  icon="pi pi-arrow-left" 
                  label="Back"
                  class="p-button-secondary tw-shadow-lg"
                  size="large"
                />
                <Button 
                  v-if="!isConfirmed"
                  @click="saveChanges" 
                  :loading="saving" 
                  icon="pi pi-save" 
                  label="Save"
                  class="p-button-success tw-shadow-lg"
                  size="large"
                />
                <Button 
                  v-if="isConfirmed && !creatingBonCommend"
                  @click="createBonCommend"
                  :loading="creatingBonCommend" 
                  icon="pi pi-arrow-right" 
                  label="Create Bon Commande"
                  class="p-button-info tw-shadow-lg"
                  size="large"
                />
                <Button 
                  v-if="!isConfirmed"
                  @click="generatePDF" 
                  :loading="generatingPDF" 
                  icon="pi pi-file-pdf"
                  label="PDF"
                  class="p-button-help tw-shadow-lg"
                  size="large"
                />
              </div>
            </div>
          </div>

          <!-- Quick Stats Bar -->
          <div class="tw-bg-white tw-p-6">
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-4">
              <div class="tw-bg-blue-50 tw-rounded-lg tw-p-3 tw-border tw-border-blue-200">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-blue-600 tw-font-medium">Products</p>
                    <p class="tw-text-2xl tw-font-bold tw-text-blue-800">
                      {{ factureProforma?.products?.length || 0 }}
                    </p>
                  </div>
                  <i class="pi pi-box tw-text-blue-500 tw-text-2xl"></i>
                </div>
              </div>

              <div class="tw-bg-green-50 tw-rounded-lg tw-p-3 tw-border tw-border-green-200">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-green-600 tw-font-medium">Total Qty</p>
                    <p class="tw-text-2xl tw-font-bold tw-text-green-800">{{ totalQuantity }}</p>
                  </div>
                  <i class="pi pi-chart-bar tw-text-green-500 tw-text-2xl"></i>
                </div>
              </div>

              <div class="tw-bg-purple-50 tw-rounded-lg tw-p-3 tw-border tw-border-purple-200">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-purple-600 tw-font-medium">Sent</p>
                    <p class="tw-text-2xl tw-font-bold tw-text-purple-800">{{ sentQuantity }}</p>
                  </div>
                  <i class="pi pi-send tw-text-purple-500 tw-text-2xl"></i>
                </div>
              </div>

              <div class="tw-bg-orange-50 tw-rounded-lg tw-p-3 tw-border tw-border-orange-200">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-orange-600 tw-font-medium">Progress</p>
                    <p class="tw-text-2xl tw-font-bold tw-text-orange-800">{{ overallProgress }}%</p>
                  </div>
                  <CircularProgress :value="overallProgress" :strokeWidth="4" :size="40" />
                </div>
              </div>

              <div class="tw-bg-emerald-50 tw-rounded-lg tw-p-3 tw-border tw-border-emerald-200">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-emerald-600 tw-font-medium">Total Value</p>
                    <p class="tw-text-lg tw-font-bold tw-text-emerald-800">{{ formatCurrency(totalValue) }}</p>
                  </div>
                  <i class="pi pi-dollar tw-text-emerald-500 tw-text-2xl"></i>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
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
            <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">Loading facture proforma...</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <div v-else-if="factureProforma" class="tw-space-y-6">

      <!-- Products DataTable Card -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-list tw-text-emerald-600"></i>
              <span>Product Items</span>
              <Tag :value="`${factureProforma?.products?.length || 0} items`" severity="info" />
            </div>
            <div class="tw-flex tw-gap-2">
              <!-- Search for items -->
              <div v-if="factureProforma?.products?.length > 5" class="tw-relative">
                <InputText 
                  v-model="productSearchQuery"
                  placeholder="Search products..."
                  class="tw-pr-8"
                />
                <i class="pi pi-search tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
              </div>
              <Button 
                v-if="!isConfirmed"
                @click="showBulkAddModal = true"
                icon="pi pi-plus-circle"
                label="Bulk Add"
                class="p-button-info"
              />
              <Button 
                v-if="!isConfirmed"
                @click="addItem"
                icon="pi pi-plus"
                label="Add Product"
                class="p-button-success"
              />
            </div>
          </div>
        </template>
        <template #content>
          <!-- Empty State -->
          <div v-if="!factureProforma?.products?.length" class="tw-text-center tw-py-12">
            <i class="pi pi-inbox tw-text-6xl tw-text-gray-300"></i>
            <p class="tw-mt-4 tw-text-gray-500 tw-text-lg">No products added yet</p>
            <Button 
              @click="addItem"
              icon="pi pi-plus"
              label="Add First Product"
              class="p-button-outlined tw-mt-4"
            />
          </div>

          <!-- DataTable for Products -->
          <DataTable 
            v-else
            :value="filteredProducts"
            :paginator="factureProforma.products.length > 10"
            :rows="10"
            :rowsPerPageOptions="[10, 25, 50]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
            responsiveLayout="scroll"
            class="tw-mt-4"
            :rowClass="getRowClass"
            :editMode="!isConfirmed ? 'cell' : null"
            @cell-edit-complete="onCellEditComplete"
          >
            <!-- Index Column -->
            <Column header="#" style="width: 50px">
              <template #body="slotProps">
                <div class="tw-w-8 tw-h-8 tw-bg-emerald-600 tw-text-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm">
                  {{ slotProps.index + 1 }}
                </div>
              </template>
            </Column>

            <!-- Product Column -->
            <Column field="product_id" header="Product" style="min-width: 250px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar 
                    :label="getProductName(slotProps.data)?.charAt(0)" 
                    class="tw-bg-emerald-100 tw-text-emerald-700"
                    shape="square"
                  />
                  <div>
                    <div class="tw-font-semibold">
                      {{ getProductName(slotProps.data) || 'Select Product' }}
                    </div>
                    <div class="tw-text-xs tw-text-gray-500">
                      Unit: {{ slotProps.data.unit || 'pieces' }}
                    </div>
                  </div>
                </div>
              </template>
              <template #editor="slotProps" v-if="!isConfirmed">
                <Dropdown
                  v-model="slotProps.data.product_id"
                  :options="products"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select product"
                  filter
                  class="tw-w-full product-dropdown"
                  @change="onProductChange(slotProps.data, slotProps.index)"
                >
                  <template #option="slotProps">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <span class="tw-font-medium">{{ slotProps.option.name }}</span>
                      <Tag :value="slotProps.option.category" severity="info" class="tw-text-xs" />
                    </div>
                  </template>
                </Dropdown>
              </template>
            </Column>

            <!-- Ordered Quantity Column -->
            <Column field="quantity" header="Ordered Qty" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <Tag :value="slotProps.data.quantity" severity="info" />
              </template>
              <template #editor="slotProps" v-if="!isConfirmed">
                <InputNumber
                  v-model="slotProps.data.quantity"
                  :min="1"
                  showButtons
                  buttonLayout="vertical"
                  class="tw-w-full"
                  @input="calculateItemTotal(slotProps.data)"
                />
              </template>
            </Column>

            <!-- Sent Quantity Column -->
            <Column field="quantity_sended" header="Sent Qty" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.quantity_sended || 0" 
                  :severity="slotProps.data.quantity_sended >= slotProps.data.quantity ? 'success' : 'warning'"
                />
              </template>
              <template #editor="slotProps" v-if="!isConfirmed">
                <InputNumber
                  v-model="slotProps.data.quantity_sended"
                  :min="0"
                  :max="slotProps.data.quantity"
                  showButtons
                  buttonLayout="vertical"
                  class="tw-w-full"
                />
              </template>
            </Column>

            <!-- Progress Column -->
            <Column header="Progress" style="width: 150px">
              <template #body="slotProps">
                <div class="tw-space-y-1">
                  <ProgressBar 
                    :value="getProgressPercentage(slotProps.data)"
                    :showValue="false"
                    class="tw-h-2"
                    :severity="getProgressSeverity(getProgressPercentage(slotProps.data))"
                  />
                  <span class="tw-text-xs tw-text-gray-500">
                    {{ getProgressPercentage(slotProps.data) }}% complete
                  </span>
                </div>
              </template>
            </Column>

            <!-- Unit Price Column -->
            <Column field="unit_price" header="Unit Price" :sortable="true" style="width: 150px">
              <template #body="slotProps">
                <span class="tw-font-medium">{{ formatCurrency(slotProps.data.unit_price) }}</span>
              </template>
              <template #editor="slotProps" v-if="!isConfirmed">
                <InputNumber
                  v-model="slotProps.data.unit_price"
                  mode="currency"
                  currency="DZD"
                  locale="fr-DZ"
                  :min="0"
                  class="tw-w-full"
                  @input="calculateItemTotal(slotProps.data)"
                />
              </template>
            </Column>

            <!-- Total Column -->
            <Column header="Total" :sortable="true" style="width: 150px">
              <template #body="slotProps">
                <span class="tw-font-bold tw-text-green-600">
                  {{ formatCurrency(slotProps.data.total_price || 0) }}
                </span>
              </template>
            </Column>

            <!-- Status Column -->
            <Column header="Status" style="width: 120px">
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.confirmation_status || 'pending'"
                  :severity="getConfirmationSeverity(slotProps.data.confirmation_status)"
                  :icon="slotProps.data.confirmation_status === 'confirmed' ? 'pi pi-check' : 'pi pi-clock'"
                />
              </template>
            </Column>

            <!-- Actions Column -->
            <Column v-if="!isConfirmed" header="Actions" style="width: 100px" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Button 
                    @click="openCreateProductDialog(slotProps.index)"
                    icon="pi pi-plus"
                    class="p-button-text p-button-sm p-button-info"
                    v-tooltip="'Create Product'"
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
          </DataTable>

          <!-- Summary Footer -->
          <div v-if="factureProforma?.products?.length > 0" class="tw-mt-6 tw-p-4 tw-bg-gradient-to-r tw-from-emerald-50 tw-to-teal-50 tw-rounded-lg">
            <div class="tw-flex tw-justify-between tw-items-center">
              <div class="tw-text-gray-600">
                <span class="tw-font-medium">Total Items:</span> {{ factureProforma.products.length }}
                <span class="tw-mx-4">|</span>
                <span class="tw-font-medium">Total Quantity:</span> {{ totalQuantity }}
              </div>
              <div class="tw-text-xl tw-font-bold tw-text-emerald-600">
                Total Value: {{ formatCurrency(totalValue) }}
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Cancel Section -->
      <Card 
        v-if="!isConfirmed" 
        class="tw-border-0 tw-shadow-xl tw-bg-gradient-to-r tw-from-red-50 tw-to-orange-50"
      >
        <template #content>
          <div class="tw-flex tw-flex-col md:tw-flex-row md:tw-items-center md:tw-justify-between tw-gap-6">
            <div class="tw-flex tw-items-start tw-gap-4">
              <div class="tw-bg-gradient-to-br tw-from-red-500 tw-to-orange-500 tw-p-3 tw-rounded-xl tw-text-white">
                <i class="pi pi-times-circle tw-text-2xl"></i>
              </div>
              <div>
                <h3 class="tw-text-xl tw-font-bold tw-text-gray-900">Cancel Proforma?</h3>
                <p class="tw-text-gray-700 tw-mt-1">
                  Cancel this proforma and mark it as cancelled. This action cannot be undone.
                </p>
              </div>
            </div>
                      <div class="tw-flex tw-justify-center tw-gap-4">
            <Button
              @click="confirmEntireProforma"
              :loading="confirming"
              :disabled="!canConfirmProforma"
              icon="pi pi-check"
              label="Confirm Proforma"
              class="p-button-success"
              size="large"
            />
            <Button
              @click="cancelEntireProforma"
              :loading="cancelling"
              icon="pi pi-times"
              label="Cancel Proforma"
              class="p-button-danger"
              size="large"
            />
          </div>
          </div>
        </template>
      </Card>

      <!-- Attachments Card -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-paperclip tw-text-teal-600"></i>
            <span>Attachments</span>
            <Tag :value="`${existingAttachments.length + newAttachments.length} files`" severity="info" />
          </div>
        </template>
        <template #content>
          <!-- File Upload -->
          <div v-if="!isConfirmed" class="tw-mb-4">
            <FileUpload
              mode="advanced"
              :multiple="true"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
              :maxFileSize="10485760"
              chooseLabel="Select Files"
              @select="handleFileSelect"
              :auto="false"
              :showUploadButton="false"
              :showCancelButton="false"
            >
              <template #empty>
                <div class="tw-text-center tw-py-8">
                  <i class="pi pi-cloud-upload tw-text-4xl tw-text-gray-400 tw-mb-2"></i>
                  <p class="tw-text-gray-600">Drag and drop files here or click to browse</p>
                  <p class="tw-text-sm tw-text-gray-500 tw-mt-2">PDF, DOC, Images (Max 10MB)</p>
                </div>
              </template>
            </FileUpload>
          </div>

          <!-- Files Grid -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
            <!-- Existing Attachments -->
            <div 
              v-for="(attachment, index) in existingAttachments" 
              :key="`existing-${index}`"
              class="tw-bg-blue-50 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200 hover:tw-shadow-lg tw-transition-shadow"
            >
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <i :class="getFileIcon(attachment.name)" class="tw-text-2xl tw-text-blue-600"></i>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ attachment.name }}</div>
                    <div class="tw-text-xs tw-text-gray-500">Uploaded</div>
                  </div>
                </div>
                <div class="tw-flex tw-gap-1">
                  <Button
                    icon="pi pi-download"
                    class="p-button-text p-button-sm"
                    @click="downloadAttachment(attachment, index)"
                    v-tooltip.top="'Download'"
                  />
                  <Button
                    v-if="!isConfirmed"
                    icon="pi pi-times"
                    class="p-button-text p-button-danger p-button-sm"
                    @click="removeExistingAttachment(index)"
                    v-tooltip.top="'Remove'"
                  />
                </div>
              </div>
            </div>

            <!-- New Attachments -->
            <div 
              v-for="(attachment, index) in newAttachments" 
              :key="`new-${index}`"
              class="tw-bg-green-50 tw-rounded-lg tw-p-4 tw-border tw-border-green-200 hover:tw-shadow-lg tw-transition-shadow"
            >
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-file-plus tw-text-2xl tw-text-green-600"></i>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ attachment.name }}</div>
                    <div class="tw-text-xs tw-text-green-600">Ready to upload</div>
                  </div>
                </div>
                <Button
                  icon="pi pi-times"
                  class="p-button-text p-button-danger p-button-sm"
                  @click="removeNewAttachment(index)"
                  v-tooltip.top="'Remove'"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="tw-flex tw-justify-center tw-items-center tw-h-64">
      <Card class="tw-w-full tw-max-w-md tw-border-0 tw-shadow-xl">
        <template #content>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-exclamation-triangle tw-text-6xl tw-text-red-500 tw-mb-4"></i>
            <p class="tw-text-gray-600 tw-text-lg">{{ error }}</p>
            <Button 
              @click="goBack" 
              label="Go Back" 
              icon="pi pi-arrow-left"
              class="tw-mt-4 p-button-secondary" 
              size="large"
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- Bulk Add Products Modal -->
    <Dialog 
      v-model:visible="showBulkAddModal" 
      header="Bulk Add Products" 
      :modal="true"
      :style="{ width: '90vw', maxWidth: '900px' }"
      :maximizable="true"
    >
      <div class="tw-space-y-4">
        <!-- Search -->
        <div class="tw-flex tw-gap-4">
          <div class="tw-flex-1 tw-relative">
            <InputText 
              v-model="bulkSearchQuery"
              placeholder="Search products..."
              class="tw-w-full tw-pl-10"
            />
            <i class="pi pi-search tw-absolute tw-left-3 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
          </div>
          <Button 
            @click="selectAllFiltered"
            icon="pi pi-check-square"
            label="Select All"
            class="p-button-secondary"
          />
        </div>

        <!-- Products DataTable -->
        <DataTable 
          v-model:selection="selectedProducts"
          :value="availableProducts"
          :paginator="true"
          :rows="10"
          selectionMode="multiple"
          dataKey="id"
          responsiveLayout="scroll"
        >
          <Column selectionMode="multiple" style="width: 3rem"></Column>

          <Column field="name" header="Product Name" :sortable="true">
            <template #body="slotProps">
              <div class="tw-font-semibold">{{ slotProps.data.name }}</div>
              <div class="tw-text-xs tw-text-gray-500">{{ slotProps.data.category }}</div>
            </template>
          </Column>

          <Column field="forme" header="Unit" style="width: 100px">
            <template #body="slotProps">
              <Tag :value="slotProps.data.forme || 'pieces'" />
            </template>
          </Column>
        </DataTable>

        <!-- Default Settings -->
        <div v-if="selectedProducts.length > 0" class="tw-bg-emerald-50 tw-p-4 tw-rounded-lg">
          <h4 class="tw-font-semibold tw-mb-3">Default Settings for Selected Products</h4>
          <div class="tw-grid tw-grid-cols-3 tw-gap-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Default Quantity</label>
              <InputNumber
                v-model="bulkDefaults.quantity"
                :min="1"
                showButtons
                class="tw-w-full tw-mt-1"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Default Price</label>
              <InputNumber
                v-model="bulkDefaults.unit_price"
                mode="currency"
                currency="DZD"
                locale="fr-DZ"
                :min="0"
                class="tw-w-full tw-mt-1"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Quantity Sent</label>
              <InputNumber
                v-model="bulkDefaults.quantity_sended"
                :min="0"
                showButtons
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
          </span>
          <div class="tw-flex tw-gap-2">
            <Button 
              label="Cancel" 
              icon="pi pi-times"
              class="p-button-text"
              @click="showBulkAddModal = false"
            />
            <Button 
              label="Add Selected Products" 
              icon="pi pi-plus"
              @click="addBulkProducts"
              :disabled="selectedProducts.length === 0"
              class="p-button-success"
            />
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Create Product Dialog -->
    <Dialog 
      v-model:visible="createProductDialog"
      modal 
      header="Create New Product"
      :style="{ width: '600px' }"
    >
      <div class="tw-space-y-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Product Name <span class="tw-text-red-500">*</span>
            </label>
            <InputText
              v-model="newProductForm.name"
              placeholder="Enter product name"
              class="tw-w-full"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Internal Code
            </label>
            <InputNumber
              v-model="newProductForm.code_interne"
              placeholder="Enter code"
              class="tw-w-full"
              :useGrouping="false"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Category <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="newProductForm.category"
              :options="categoryOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select category"
              class="tw-w-full category-dropdown"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Unit/Form
            </label>
            <InputText
              v-model="newProductForm.forme"
              placeholder="e.g., pieces, kg, box"
              class="tw-w-full"
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Description
          </label>
          <Textarea
            v-model="newProductForm.description"
            placeholder="Optional description"
            rows="3"
            class="tw-w-full"
            autoResize
          />
        </div>
      </div>

      <template #footer>
        <Button 
          label="Cancel" 
          icon="pi pi-times"
          class="p-button-text"
          @click="closeCreateProductDialog"
        />
        <Button 
          label="Create & Select" 
          icon="pi pi-check"
          :loading="creatingProduct"
          :disabled="!newProductForm.name || !newProductForm.category"
          @click="createProduct"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import ProgressBar from 'primevue/progressbar'
import Dialog from 'primevue/dialog'
import FileUpload from 'primevue/fileupload'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import Message from 'primevue/message'

// Custom circular progress (you can replace with actual component)
const CircularProgress = {
  props: ['value', 'strokeWidth', 'size'],
  template: `
    <div class="tw-relative" :style="{ width: size + 'px', height: size + 'px' }">
      <svg :width="size" :height="size" class="tw-transform tw--rotate-90">
        <circle
          :cx="size/2"
          :cy="size/2"
          :r="(size - strokeWidth) / 2"
          :stroke-width="strokeWidth"
          fill="none"
          stroke="#e5e7eb"
        />
        <circle
          :cx="size/2"
          :cy="size/2"
          :r="(size - strokeWidth) / 2"
          :stroke-width="strokeWidth"
          fill="none"
          stroke="#f97316"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="circumference - (value / 100 * circumference)"
          class="tw-transition-all tw-duration-300"
        />
      </svg>
      <div class="tw-absolute tw-inset-0 tw-flex tw-items-center tw-justify-center">
        <span class="tw-text-xs tw-font-bold">{{ value }}%</span>
      </div>
    </div>
  `,
  computed: {
    circumference() {
      return 2 * Math.PI * ((this.size - this.strokeWidth) / 2)
    }
  }
}

const route = useRoute()
const router = useRouter()
const toast = useToast()

// State
const loading = ref(true)
const saving = ref(false)
const confirming = ref(false)
const cancelling = ref(false)
const creatingBonCommend = ref(false)
const generatingPDF = ref(false)
const error = ref(null)
const factureProforma = ref(null)
const existingAttachments = ref([])
const newAttachments = ref([])
const products = ref([])
const loadingProducts = ref(false)
const createProductDialog = ref(false)
const creatingProduct = ref(false)
const currentItemIndex = ref(null)
const productSearchQuery = ref('')
const showBulkAddModal = ref(false)
const bulkSearchQuery = ref('')
const selectedProducts = ref([])

const newProductForm = ref({
  name: '',
  code_interne: null,
  category: '',
  forme: '',
  description: ''
})

const bulkDefaults = ref({
  quantity: 1,
  quantity_sended: 0,
  unit_price: 0
})

const categoryOptions = [
  { label: 'Medical Supplies', value: 'Medical Supplies' },
  { label: 'Equipment', value: 'Equipment' },
  { label: 'Medication', value: 'Medication' },
  { label: 'Consumables', value: 'Consumables' },
  { label: 'Laboratory', value: 'Laboratory' },
  { label: 'Others', value: 'Others' }
]

// Breadcrumb
const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Facture Proformas', to: '/apps/purchasing/facture-proformas-list' },
  { label: `Edit FP-${route.params.id}`, disabled: true }
])

// Computed
const isConfirmed = computed(() => {
  return factureProforma.value?.is_confirmed || factureProforma.value?.status === 'confirmed'
})

const canConfirmProforma = computed(() => {
  if (!factureProforma.value?.products?.length) return false
  if (isConfirmed.value) return false
  return factureProforma.value.products.every(p => 
    p.quantity_sended >= 0 && p.quantity_sended <= p.quantity
  )
})

const totalQuantity = computed(() => {
  return factureProforma.value?.products?.reduce((sum, p) => sum + (p.quantity || 0), 0) || 0
})

const sentQuantity = computed(() => {
  return factureProforma.value?.products?.reduce((sum, p) => sum + (p.quantity_sended || 0), 0) || 0
})

const overallProgress = computed(() => {
  if (!totalQuantity.value) return 0
  return Math.round((sentQuantity.value / totalQuantity.value) * 100)
})

const totalValue = computed(() => {
  return factureProforma.value?.products?.reduce((sum, p) => sum + (p.total_price || 0), 0) || 0
})

const filteredProducts = computed(() => {
  if (!productSearchQuery.value || !factureProforma.value?.products) return factureProforma.value?.products || []

  const query = productSearchQuery.value.toLowerCase()
  return factureProforma.value.products.filter(product => {
    const name = getProductName(product)?.toLowerCase() || ''
    return name.includes(query)
  })
})

const availableProducts = computed(() => {
  if (!bulkSearchQuery.value) return products.value

  const query = bulkSearchQuery.value.toLowerCase()
  return products.value.filter(product => 
    product.name?.toLowerCase().includes(query) || 
    product.category?.toLowerCase().includes(query)
  )
})

// Methods
const fetchFactureProforma = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/facture-proformas/${route.params.id}`)

    if (response.data.status === 'success') {
      factureProforma.value = response.data.data

      factureProforma.value.products?.forEach(product => {
        if (!product.quantity_sended) product.quantity_sended = 0
        if (!product.product_id && product.product?.id) {
          product.product_id = product.product.id
        }
        if (!product.unit_price && product.price) {
          product.unit_price = product.price
        }
        if (!product.total_price) {
          product.total_price = (product.quantity || 0) * (product.unit_price || 0)
        }
      })

      existingAttachments.value = factureProforma.value.attachments || []
    } else {
      error.value = 'Failed to load facture proforma'
    }
  } catch (err) {
    console.error('Error fetching facture proforma:', err)
    error.value = 'Failed to load facture proforma data'
  } finally {
    loading.value = false
  }
}

const fetchProducts = async () => {
  try {
    loadingProducts.value = true
    const response = await axios.get('/api/products')
    products.value = response.data.data || response.data || []
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

const addItem = () => {
  if (!factureProforma.value.products) {
    factureProforma.value.products = []
  }
  factureProforma.value.products.push({
    product_id: null,
    quantity: 1,
    quantity_sended: 0,
    unit: 'pieces',
    unit_price: 0,
    total_price: 0,
    confirmation_status: 'pending'
  })
}

const removeItem = (index) => {
  factureProforma.value.products.splice(index, 1)
  toast.add({
    severity: 'success',
    summary: 'Removed',
    detail: 'Product removed successfully',
    life: 2000
  })
}

const onProductChange = (item, index) => {
  const product = products.value.find(p => p.id === item.product_id)
  if (product) {
    item.unit = product.unit || product.forme || 'pieces'
  }
}

const onCellEditComplete = (event) => {
  let { data, newValue, field } = event
  data[field] = newValue
  if (field === 'quantity' || field === 'unit_price') {
    calculateItemTotal(data)
  }
}

const calculateItemTotal = (item) => {
  item.total_price = (item.quantity || 0) * (item.unit_price || 0)
}

const getProductName = (product) => {
  if (product.product?.name) return product.product.name
  if (product.product_id) {
    const found = products.value.find(p => p.id === product.product_id)
    return found?.name
  }
  return null
}

const getRowClass = (data) => {
  if (data.confirmation_status === 'confirmed') return 'tw-bg-green-50'
  if (data.quantity_sended >= data.quantity) return 'tw-bg-emerald-50'
  return ''
}

const getConfirmationSeverity = (status) => {
  return status === 'confirmed' ? 'success' : 'warning'
}

const getProgressPercentage = (product) => {
  if (!product.quantity) return 0
  return Math.round(((product.quantity_sended || 0) / product.quantity) * 100)
}

const getProgressSeverity = (percentage) => {
  if (percentage === 0) return 'danger'
  if (percentage < 50) return 'warning'
  if (percentage < 100) return 'info'
  return 'success'
}

const getFileIcon = (filename) => {
  if (!filename) return 'pi pi-file'
  const ext = filename.split('.').pop().toLowerCase()
  const iconMap = {
    pdf: 'pi pi-file-pdf',
    doc: 'pi pi-file-word',
    docx: 'pi pi-file-word',
    xls: 'pi pi-file-excel',
    xlsx: 'pi pi-file-excel',
    jpg: 'pi pi-image',
    jpeg: 'pi pi-image',
    png: 'pi pi-image'
  }
  return iconMap[ext] || 'pi pi-file'
}

// Bulk Add
const selectAllFiltered = () => {
  selectedProducts.value = [...availableProducts.value]
}

const addBulkProducts = () => {
  if (!factureProforma.value.products) {
    factureProforma.value.products = []
  }

  selectedProducts.value.forEach(product => {
    factureProforma.value.products.push({
      product_id: product.id,
      quantity: bulkDefaults.value.quantity,
      quantity_sended: bulkDefaults.value.quantity_sended,
      unit: product.forme || 'pieces',
      unit_price: bulkDefaults.value.unit_price,
      total_price: bulkDefaults.value.quantity * bulkDefaults.value.unit_price,
      confirmation_status: 'pending'
    })
  })

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${selectedProducts.value.length} products added`,
    life: 3000
  })

  showBulkAddModal.value = false
  selectedProducts.value = []
}

// File Management
const handleFileSelect = (event) => {
  event.files.forEach(file => {
    newAttachments.value.push({
      file: file,
      name: file.name
    })
  })
  toast.add({
    severity: 'success',
    summary: 'Files Selected',
    detail: `${event.files.length} file(s) ready to upload`,
    life: 2000
  })
}

const removeNewAttachment = (index) => {
  newAttachments.value.splice(index, 1)
}

const removeExistingAttachment = (index) => {
  existingAttachments.value.splice(index, 1)
}

const downloadAttachment = async (attachment, index) => {
  try {
    const response = await axios.get(
      `/api/facture-proformas/${factureProforma.value.id}/attachments/${index}/download`,
      { responseType: 'blob' }
    )

    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = attachment.name || `attachment-${index}`
    link.click()
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Downloaded',
      detail: 'File downloaded successfully',
      life: 2000
    })
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to download attachment',
      life: 3000
    })
  }
}

// Product Creation
const openCreateProductDialog = (index) => {
  currentItemIndex.value = index
  createProductDialog.value = true
  newProductForm.value = {
    name: '',
    code_interne: null,
    category: '',
    forme: '',
    description: ''
  }
}

const closeCreateProductDialog = () => {
  createProductDialog.value = false
  currentItemIndex.value = null
}

const createProduct = async () => {
  try {
    creatingProduct.value = true
    const response = await axios.post('/api/products', newProductForm.value)

    if (response.data.success || response.data.status === 'success') {
      const created = response.data.data
      products.value.push(created)

      if (currentItemIndex.value !== null) {
        factureProforma.value.products[currentItemIndex.value].product_id = created.id
        factureProforma.value.products[currentItemIndex.value].unit = created.forme || 'pieces'
      }

      toast.add({
        severity: 'success',
        summary: 'Created',
        detail: 'Product created successfully',
        life: 3000
      })
      closeCreateProductDialog()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to create product',
      life: 3000
    })
  } finally {
    creatingProduct.value = false
  }
}

// Save & Confirm
const saveChanges = async () => {
  try {
    saving.value = true
    const formData = new FormData()

    formData.append('fournisseur_id', factureProforma.value.fournisseur_id)
    formData.append('service_demand_purchasing_id', factureProforma.value.service_demand_purchasing_id || '')

    factureProforma.value.products.forEach((product, index) => {
      formData.append(`products[${index}][product_id]`, product.product_id)
      formData.append(`products[${index}][quantity]`, product.quantity)
      formData.append(`products[${index}][quantity_sended]`, product.quantity_sended || 0)
      formData.append(`products[${index}][unit_price]`, product.unit_price || 0)
      formData.append(`products[${index}][total_price]`, product.total_price || 0)
    })

    existingAttachments.value.forEach((attachment, index) => {
      formData.append(`existing_attachments[${index}][path]`, attachment.path)
      formData.append(`existing_attachments[${index}][name]`, attachment.name)
    })

    newAttachments.value.forEach((attachment, index) => {
      formData.append(`attachments[${index}][file]`, attachment.file)
      formData.append(`attachments[${index}][name]`, attachment.name)
    })

    const response = await axios.post(
      `/api/facture-proformas/${factureProforma.value.id}?_method=PUT`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    if (response.data.status === 'success') {
      newAttachments.value = []
      await fetchFactureProforma()
      toast.add({
        severity: 'success',
        summary: 'Saved',
        detail: 'Changes saved successfully',
        life: 3000
      })
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save changes',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const confirmEntireProforma = async () => {
  try {
    confirming.value = true
    await saveChanges()
    await axios.post(`/api/facture-proformas/${factureProforma.value.id}/confirm`)

    toast.add({
      severity: 'success',
      summary: 'Confirmed',
      detail: 'Proforma confirmed successfully',
      life: 3000
    })

    await fetchFactureProforma()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to confirm proforma',
      life: 3000
    })
  } finally {
    confirming.value = false
  }
}

const cancelEntireProforma = async () => {
  try {
    cancelling.value = true
    const response = await axios.post(`/api/facture-proformas/${factureProforma.value.id}/cancel`)

    if (response.data.status === 'success' || response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Cancelled',
        detail: 'Proforma cancelled successfully',
        life: 3000
      })
      await fetchFactureProforma()
    }
  } catch (err) {
    console.error('Error cancelling proforma:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to cancel proforma',
      life: 3000
    })
  } finally {
    cancelling.value = false
  }
}

const createBonCommend = async () => {
  try {
    creatingBonCommend.value = true
    const response = await axios.post('/api/bon-commends/create-from-facture-proforma', {
      factureproforma_id: factureProforma.value.id
    })

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Created',
        detail: 'Bon commande created successfully',
        life: 3000
      })
      router.push({ name: 'purchasing.bon-commend.edit', params: { id: response.data.data.id } })
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to create bon commande',
      life: 3000
    })
  } finally {
    creatingBonCommend.value = false
  }
}

const generatePDF = () => {
  toast.add({
    severity: 'info',
    summary: 'PDF Generation',
    detail: 'Feature coming soon',
    life: 2000
  })
}

const goBack = () => {
  router.push('/apps/purchasing/facture-proformas-list')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  Promise.all([fetchFactureProforma(), fetchProducts()])
})
</script>

<style scoped>
/* DataTable enhancements */
:deep(.p-datatable) {
  @apply tw-border-0 tw-rounded-lg;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply tw-bg-gray-50 tw-text-gray-700 tw-font-semibold tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply hover:tw-bg-emerald-50 tw-transition-colors;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
  @apply tw-bg-emerald-100;
}

/* Card styling */
:deep(.p-card) {
  @apply tw-rounded-xl;
}

:deep(.p-card-title) {
  @apply tw-text-xl tw-font-bold tw-text-gray-800;
}

/* Dialog */
:deep(.p-dialog-header) {
  @apply tw-bg-gradient-to-r tw-from-emerald-600 tw-to-teal-700 tw-text-white;
}

:deep(.p-dialog-header .p-dialog-title) {
  @apply tw-text-white tw-font-bold;
}

/* FileUpload */
:deep(.p-fileupload) {
  @apply tw-border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg hover:tw-border-emerald-400 hover:tw-bg-emerald-50 tw-transition-all;
}

/* Animations */
@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.tw-card-enter {
  animation: slideIn 0.3s ease-out;
}

/* Indigo-themed Category Dropdown */
:deep(.category-dropdown) {
  @apply tw-border-indigo-300 tw-rounded-md;
}

:deep(.category-dropdown .p-dropdown-label) {
  @apply tw-text-gray-700;
}

:deep(.category-dropdown:not(.p-disabled).p-focus) {
  @apply tw-border-indigo-500 tw-ring-2 tw-ring-indigo-200;
}

:deep(.category-dropdown .p-dropdown-trigger) {
  @apply tw-text-indigo-600;
}

:deep(.category-dropdown .p-dropdown-panel) {
  @apply tw-border-indigo-200 tw-shadow-lg;
}

:deep(.category-dropdown .p-dropdown-item) {
  @apply tw-text-gray-700 hover:tw-bg-indigo-50 hover:tw-text-indigo-900;
}

:deep(.category-dropdown .p-dropdown-item.p-highlight) {
  @apply tw-bg-indigo-100 tw-text-indigo-900;
}
</style>