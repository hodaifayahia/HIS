<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50 tw-p-4 lg:tw-p-6">
    <!-- Enhanced Header with Breadcrumb -->
    <div class="tw-mb-6">
      <Breadcrumb :model="breadcrumbItems" class="tw-mb-4">
        <template #item="{ item }">
          <span class="tw-text-gray-600 hover:tw-text-emerald-600 tw-transition-colors">{{ item.label }}</span>
        </template>
      </Breadcrumb>

      <Card class="tw-border-0 tw-shadow-2xl tw-overflow-hidden enhanced-card">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-emerald-600 tw-via-teal-600 tw-to-cyan-700 tw-p-6 tw--m-6">
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
                    <Tag 
                      v-else-if="isCancelled" 
                      value="Cancelled" 
                      severity="danger" 
                      icon="pi pi-ban"
                      class="tw-font-semibold"
                    />
                    <Tag 
                      v-else
                      value="Draft" 
                      severity="warning" 
                      icon="pi pi-pencil"
                      class="tw-font-semibold"
                    />
                  </h1>
                  <p class="tw-text-emerald-100 tw-mt-1">
                    <i class="pi pi-building tw-mr-2"></i>
                    {{ factureProforma?.fournisseur?.company_name || 'No Supplier' }}
                  </p>
                  <div v-if="isReadOnly" class="tw-mt-2 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-eye tw-text-emerald-200"></i>
                    <span class="tw-text-sm tw-text-emerald-200 tw-font-medium">
                      Read-only mode - {{ isConfirmed ? 'Confirmed' : 'Cancelled' }} proforma cannot be modified
                    </span>
                  </div>
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
                  v-if="!isReadOnly"
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

          <!-- Enhanced Quick Stats Bar -->
          <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50 tw-to-indigo-50 tw-p-6 tw-rounded-b-2xl">
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-6">
              <div class="tw-bg-white tw-rounded-2xl tw-p-4 tw-border tw-border-blue-100 hover:tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-sm tw-font-semibold tw-text-blue-600 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-box tw-text-blue-500"></i>
                      Products
                    </p>
                    <p class="tw-text-3xl tw-font-bold tw-text-blue-800 tw-mt-1">
                      {{ factureProforma?.products?.length || 0 }}
                    </p>
                  </div>
                  <div class="tw-p-3 tw-bg-blue-100 tw-rounded-xl">
                    <i class="pi pi-box tw-text-2xl tw-text-blue-600"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-white tw-rounded-2xl tw-p-4 tw-border tw-border-green-100 hover:tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-sm tw-font-semibold tw-text-green-600 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-chart-bar tw-text-green-500"></i>
                      Total Qty
                    </p>
                    <p class="tw-text-3xl tw-font-bold tw-text-green-800 tw-mt-1">{{ totalQuantity }}</p>
                  </div>
                  <div class="tw-p-3 tw-bg-green-100 tw-rounded-xl">
                    <i class="pi pi-chart-bar tw-text-2xl tw-text-green-600"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-white tw-rounded-2xl tw-p-4 tw-border tw-border-purple-100 hover:tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-sm tw-font-semibold tw-text-purple-600 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-send tw-text-purple-500"></i>
                      Sent Qty
                    </p>
                    <p class="tw-text-3xl tw-font-bold tw-text-purple-800 tw-mt-1">{{ sentQuantity }}</p>
                  </div>
                  <div class="tw-p-3 tw-bg-purple-100 tw-rounded-xl">
                    <i class="pi pi-send tw-text-2xl tw-text-purple-600"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-white tw-rounded-2xl tw-p-4 tw-border tw-border-orange-100 hover:tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-sm tw-font-semibold tw-text-orange-600 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-clock tw-text-orange-500"></i>
                      Progress
                    </p>
                    <p class="tw-text-3xl tw-font-bold tw-text-orange-800 tw-mt-1">{{ overallProgress }}%</p>
                  </div>
                  <div class="tw-p-3 tw-bg-orange-100 tw-rounded-xl">
                    <CircularProgress :value="overallProgress" :strokeWidth="4" :size="40" />
                  </div>
                </div>
              </div>

              <div class="tw-bg-white tw-rounded-2xl tw-p-4 tw-border tw-border-emerald-100 hover:tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-sm tw-font-semibold tw-text-emerald-600 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-dollar tw-text-emerald-500"></i>
                      Total Value
                    </p>
                    <p class="tw-text-2xl tw-font-bold tw-text-emerald-800 tw-mt-1">{{ formatCurrency(totalValue) }}</p>
                  </div>
                  <div class="tw-p-3 tw-bg-emerald-100 tw-rounded-xl">
                    <i class="pi pi-dollar tw-text-2xl tw-text-emerald-600"></i>
                  </div>
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

    <!-- Status Messages -->
    <div v-if="factureProforma && isReadOnly" class="tw-mb-6">
      <Card class="tw-border-0 tw-shadow-xl" :class="isConfirmed ? 'tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-border-green-200' : 'tw-bg-gradient-to-r tw-from-red-50 tw-to-orange-50 tw-border-red-200'">
        <template #content>
          <div class="tw-flex tw-items-start tw-gap-4">
            <div :class="isConfirmed ? 'tw-bg-green-500' : 'tw-bg-red-500'" class="tw-p-3 tw-rounded-xl tw-text-white">
              <i :class="isConfirmed ? 'pi pi-check-circle' : 'pi pi-times-circle'" class="tw-text-2xl"></i>
            </div>
            <div class="tw-flex-1">
              <h3 :class="isConfirmed ? 'tw-text-green-800' : 'tw-text-red-800'" class="tw-text-xl tw-font-bold">
                Proforma {{ isConfirmed ? 'Confirmed' : 'Cancelled' }}
              </h3>
              <p :class="isConfirmed ? 'tw-text-green-700' : 'tw-text-red-700'" class="tw-mt-1">
                {{ isConfirmed 
                  ? 'This proforma has been confirmed and can no longer be modified. You can create a Bon Commande from this proforma.' 
                  : 'This proforma has been cancelled and can no longer be modified.' 
                }}
              </p>
              <div v-if="isConfirmed" class="tw-mt-3 tw-flex tw-gap-2">
                <Button 
                  @click="createBonCommend"
                  :loading="creatingBonCommend" 
                  icon="pi pi-arrow-right" 
                  label="Create Bon Commande"
                  class="p-button-success"
                  size="small"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <div v-else-if="factureProforma" class="tw-space-y-6 tw-relative">
      <!-- Read-only overlay -->
      <div 
        v-if="isReadOnly" 
        class="tw-absolute tw-inset-0 tw-bg-black/5 tw-backdrop-blur-[1px] tw-z-10 tw-rounded-lg tw-pointer-events-none"
      ></div>
      <div 
        v-if="isReadOnly" 
        class="tw-absolute tw-top-4 tw-right-4 tw-z-20 tw-bg-white tw-rounded-lg tw-shadow-lg tw-border tw-p-3"
      >
        <div class="tw-flex tw-items-center tw-gap-2">
          <i :class="isConfirmed ? 'pi pi-lock tw-text-green-600' : 'pi pi-ban tw-text-red-600'" class="tw-text-lg"></i>
          <span class="tw-text-sm tw-font-medium tw-text-gray-700">
            {{ isConfirmed ? 'Confirmed' : 'Cancelled' }} - Read Only
          </span>
        </div>
      </div>

      <!-- Products DataTable Card -->
      <Card class="tw-border-0 tw-shadow-xl enhanced-card">
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
                v-if="!isReadOnly"
                @click="showBulkAddModal = true"
                icon="pi pi-plus-circle"
                label="Bulk Add"
                class="p-button-info"
              />
              <Button 
                v-if="!isReadOnly"
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
            :editMode="!isReadOnly ? 'cell' : null"
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
                  :options="tableProducts"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select product"
                  filter
                  :loading="loadingTableDropdownProducts"
                  :virtualScroller="true"
                  :scrollHeight="320"
                  class="tw-w-full product-dropdown"
                  @change="onProductChange(slotProps.data, slotProps.index)"
                  @filter="filterProductsInTable"
                  @show="() => handleTableDropdownShow(slotProps.data)"
                  @scroll-to-end="loadMoreTableDropdownProducts"
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
            <Column v-if="!isReadOnly" header="Actions" style="width: 100px" :exportable="false">
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
        v-if="!isReadOnly" 
        class="tw-border-0 tw-shadow-xl tw-bg-gradient-to-r tw-from-red-50 tw-to-orange-50 enhanced-card"
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
      <Card class="tw-border-0 tw-shadow-xl enhanced-card">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-paperclip tw-text-teal-600"></i>
            <span>Attachments</span>
            <Tag :value="`${existingAttachments.length + newAttachments.length} files`" severity="info" />
          </div>
        </template>
        <template #content>
          <!-- File Upload -->
          <div v-if="!isReadOnly" class="tw-mb-4">
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
      :style="{ width: '90vw', maxWidth: '1000px' }"
      :maximizable="true"
      @show="fetchProducts(1, bulkSearchQuery)"
      class="enhanced-dialog"
    >
      <div class="tw-space-y-6">
        <!-- Search Bar with Type Indicator -->
        <div class="tw-flex tw-gap-4 tw-items-center">
          <div class="tw-flex-1 tw-relative">
            <InputText 
              v-model="bulkSearchQuery"
              placeholder="Search products..."
              class="tw-w-full tw-pl-10 tw-pr-4"
              @keyup.enter="fetchProducts(1, bulkSearchQuery)"
            />
            <i class="pi pi-search tw-absolute tw-left-3 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-bg-blue-50 tw-rounded-lg tw-border tw-border-blue-200">
            <i class="pi tw-text-blue-600" :class="isPharmacyOrder ? 'pi-pills' : 'pi-box'"></i>
            <span class="tw-text-sm tw-font-medium tw-text-blue-700">
              {{ isPharmacyOrder ? 'Pharmacy Products' : 'Stock Products' }}
            </span>
          </div>
          <Button 
            @click="selectAllFiltered"
            icon="pi pi-check-square"
            label="Select All"
            class="p-button-secondary"
          />
        </div>

        <!-- Products Grid with Infinite Scroll -->
        <div class="tw-max-h-96 tw-overflow-y-auto tw-border tw-border-gray-200 tw-rounded-lg">
          <!-- Products Grid -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-3 tw-p-4">
            <div 
              v-for="product in availableProducts" 
              :key="product.id"
              @click="selectedProducts.some(p => p.id === product.id) ? selectedProducts = selectedProducts.filter(p => p.id !== product.id) : selectedProducts.push(product)"
              :class="[
                'tw-cursor-pointer tw-p-3 tw-rounded-lg tw-border-2 tw-transition-all tw-duration-200',
                selectedProducts.some(p => p.id === product.id)
                  ? 'tw-border-emerald-500 tw-bg-emerald-50 tw-shadow-md'
                  : 'tw-border-gray-200 tw-bg-white hover:tw-border-emerald-300'
              ]"
            >
              <div class="tw-flex tw-items-start tw-gap-2">
                <Checkbox 
                  :modelValue="selectedProducts.some(p => p.id === product.id)"
                  @update:modelValue="selectedProducts.some(p => p.id === product.id) ? selectedProducts = selectedProducts.filter(p => p.id !== product.id) : selectedProducts.push(product)"
                  :binary="true"
                />
                <div class="tw-flex-1 tw-min-w-0">
                  <div class="tw-font-semibold tw-text-gray-900 tw-truncate">{{ product.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500 tw-truncate">{{ product.category || 'Uncategorized' }}</div>
                  <div class="tw-flex tw-gap-2 tw-mt-1 tw-flex-wrap">
                    <Tag v-if="product.forme" :value="product.forme" severity="info" :style="{ fontSize: '0.7rem' }" />
                    <Tag v-if="product.code_interne" :value="`Code: ${product.code_interne}`" severity="secondary" :style="{ fontSize: '0.7rem' }" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading More Indicator -->
          <div v-if="loadingMoreProducts" class="tw-col-span-full tw-py-4 tw-text-center">
            <ProgressSpinner strokeWidth="4" :style="{ width: '2rem', height: '2rem' }" class="tw-mx-auto" />
          </div>

          <!-- Load More Button -->
          <div v-if="hasMoreProducts && !loadingMoreProducts" class="tw-p-4 tw-text-center">
            <Button 
              @click="loadMoreProducts"
              :loading="loadingMoreProducts"
              icon="pi pi-arrow-down"
              label="Load More Products"
              class="w-full p-button-outlined"
            />
          </div>

          <!-- No Results -->
          <div v-if="!loadingProducts && availableProducts.length === 0" class="tw-p-8 tw-text-center">
            <i class="pi pi-inbox tw-text-3xl tw-text-gray-300 tw-mb-3 tw-block"></i>
            <p class="tw-text-gray-500">No products found</p>
          </div>
        </div>

        <!-- Product Count -->
        <div class="tw-flex tw-justify-between tw-items-center tw-text-sm tw-text-gray-600">
          <span>Showing {{ availableProducts.length }} of {{ products.length }} available products</span>
          <span class="tw-font-semibold tw-text-emerald-600">{{ selectedProducts.length }} selected</span>
        </div>

        <!-- Default Settings -->
        <div v-if="selectedProducts.length > 0" class="tw-bg-gradient-to-r tw-from-emerald-50 tw-to-teal-50 tw-p-4 tw-rounded-lg tw-border tw-border-emerald-200">
          <h4 class="tw-font-semibold tw-mb-4 tw-text-emerald-900">Default Settings for Selected Products</h4>
          <div class="tw-grid tw-grid-cols-3 tw-gap-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-block tw-mb-2">Default Quantity</label>
              <InputNumber
                v-model="bulkDefaults.quantity"
                :min="1"
                showButtons
                class="tw-w-full"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-block tw-mb-2">Default Price</label>
              <InputNumber
                v-model="bulkDefaults.unit_price"
                mode="currency"
                currency="DZD"
                locale="fr-DZ"
                :min="0"
                class="tw-w-full"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-block tw-mb-2">Quantity Sent</label>
              <InputNumber
                v-model="bulkDefaults.quantity_sended"
                :min="0"
                showButtons
                class="tw-w-full"
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-between tw-items-center">
          <span class="tw-text-sm tw-font-medium tw-text-gray-600">
            {{ selectedProducts.length }} product(s) selected
          </span>
          <div class="tw-flex tw-gap-2">
            <Button 
              label="Cancel" 
              icon="pi pi-times"
              class="p-button-secondary"
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
      :visible="createProductDialog"
      @update:visible="createProductDialog = $event"
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
import Checkbox from 'primevue/checkbox'

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
const tableProducts = ref([])
const loadingProducts = ref(false)
const loadingMoreProducts = ref(false)
const createProductDialog = ref(false)
const creatingProduct = ref(false)
const currentItemIndex = ref(null)
const productSearchQuery = ref('')
const showBulkAddModal = ref(false)
const bulkSearchQuery = ref('')
const selectedProducts = ref([])
const productsPage = ref(1)
const productsPerPage = ref(15)
const hasMoreProducts = ref(true)
const isPharmacyOrder = ref(false)
const bulkAddScrollRef = ref(null)
const isLoadingMore = ref(false)

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

const isCancelled = computed(() => {
  return factureProforma.value?.is_cancelled || factureProforma.value?.status === 'cancelled'
})

const isReadOnly = computed(() => {
  return isConfirmed.value || isCancelled.value
})

const canConfirmProforma = computed(() => {
  if (!factureProforma.value?.products?.length) return false
  if (isConfirmed.value) return false
  
  // Validate each product has valid quantity_sended
  return factureProforma.value.products.every(p => {
    const qty = p.quantity || 0
    const sent = p.quantity_sended || 0
    return sent >= 0 && sent <= qty
  })
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
      
      // Check if this is a pharmacy order
      const serviceDemand = response.data.data.service_demand_purchasing
      isPharmacyOrder.value = serviceDemand?.is_pharmacy_order || false

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
        
        // Ensure quantity_sended is valid
        const qty = product.quantity || 0
        if (product.quantity_sended < 0) product.quantity_sended = 0
        if (product.quantity_sended > qty) product.quantity_sended = qty
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

const fetchProducts = async (page = 1, search = '') => {
  try {
    if (page === 1) {
      loadingProducts.value = true
      products.value = []
    } else {
      loadingMoreProducts.value = true
    }

    // Determine which endpoint to use based on pharmacy order flag
    const endpoint = isPharmacyOrder.value 
      ? '/api/pharmacy/products/paginated' 
      : '/api/products-paginated'

    const response = await axios.get(endpoint, {
      params: {
        page: page,
        per_page: productsPerPage.value,
        search: search
      }
    })

    const newProducts = response.data.data || response.data.products || []
    
    if (page === 1) {
      products.value = newProducts
    } else {
      products.value.push(...newProducts)
    }

    // Check if there are more products to load
    hasMoreProducts.value = response.data.pagination?.has_more || 
                             (newProducts.length === productsPerPage.value)
    productsPage.value = page

  } catch (err) {
    console.error('Error fetching products:', err)
    if (page === 1) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load products',
        life: 3000
      })
    }
  } finally {
    loadingProducts.value = false
    loadingMoreProducts.value = false
  }
}

const loadMoreProducts = async () => {
  if (!loadingMoreProducts.value && hasMoreProducts.value) {
    await fetchProducts(productsPage.value + 1, bulkSearchQuery.value)
  }
}

// Table Dropdown Infinite Scroll - for editing products in table
const tableDropdownPage = ref(1)
const tableDropdownSearch = ref('')
const tableDropdownHasMore = ref(true)
const loadingTableDropdownProducts = ref(false)
const tableDropdownPageSize = 15

const loadTableDropdownProducts = async (page = 1) => {
  if (loadingTableDropdownProducts.value) return
  if (page > 1 && !tableDropdownHasMore.value) return

  if (page === 1) {
    tableDropdownHasMore.value = true
    tableDropdownPage.value = 1
  }

  try {
    loadingTableDropdownProducts.value = true

    // Determine which endpoint to use based on pharmacy order flag
    const endpoint = isPharmacyOrder.value 
      ? '/api/pharmacy/products/paginated' 
      : '/api/products-paginated'

    const response = await axios.get(endpoint, {
      params: {
        page,
        per_page: tableDropdownPageSize,
        search: tableDropdownSearch.value
      }
    })

    const newProducts = response.data.data || response.data.products || []
    const hasMore = response.data.pagination?.has_more ?? (newProducts.length === tableDropdownPageSize)

    if (page === 1) {
      tableProducts.value = newProducts
    } else if (newProducts.length) {
      const existingIds = new Set(tableProducts.value.map(product => product.id))
      newProducts.forEach(product => {
        if (!existingIds.has(product.id)) {
          tableProducts.value.push(product)
        }
      })
    }

    tableDropdownHasMore.value = hasMore
    if (newProducts.length) {
      tableDropdownPage.value = page
    }
  } catch (err) {
    console.error('Error loading table dropdown products:', err)
    if (page === 1) {
      tableProducts.value = []
    }
  } finally {
    loadingTableDropdownProducts.value = false
  }
}

const loadMoreTableDropdownProducts = async () => {
  if (loadingTableDropdownProducts.value || !tableDropdownHasMore.value) return
  await loadTableDropdownProducts(tableDropdownPage.value + 1)
}

const filterProductsInTable = async (event) => {
  const nextFilter = typeof event === 'string'
    ? event
    : event?.value ?? event?.filter ?? ''
  tableDropdownSearch.value = nextFilter
  await loadTableDropdownProducts(1)
}

const ensureProductInDropdownOptions = (item) => {
  const currentId = item?.product_id || item?.product?.id
  if (!currentId) return

  const exists = tableProducts.value.some(product => product.id === currentId)
  if (exists) return

  if (item?.product) {
    tableProducts.value.unshift({
      id: currentId,
      name: item.product.name,
      category: item.product.category || item.product.medication_category || 'Uncategorized',
      unit: item.product.unit || item.product.forme || 'pieces'
    })
  }
}

const handleTableDropdownShow = async (item) => {
  await loadTableDropdownProducts(1)
  ensureProductInDropdownOptions(item)
}

const findProductOptionById = (id) => {
  if (!id) return null
  return tableProducts.value.find(product => product.id === id) ||
         products.value.find(product => product.id === id) ||
         null
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
  const product = findProductOptionById(item.product_id)
  if (product) {
    item.unit = product.unit || product.forme || 'pieces'
  }
}

const onCellEditComplete = (event) => {
  let { data, newValue, field } = event
  data[field] = newValue
  
  if (field === 'quantity') {
    // Ensure quantity_sended doesn't exceed new quantity
    if (data.quantity_sended > newValue) {
      data.quantity_sended = newValue
    }
    calculateItemTotal(data)
  } else if (field === 'unit_price') {
    calculateItemTotal(data)
  } else if (field === 'quantity_sended') {
    // Ensure quantity_sended is not negative and doesn't exceed quantity
    if (data.quantity_sended < 0) {
      data.quantity_sended = 0
    } else if (data.quantity_sended > data.quantity) {
      data.quantity_sended = data.quantity
    }
  }
}

const calculateItemTotal = (item) => {
  item.total_price = (item.quantity || 0) * (item.unit_price || 0)
}

const getProductName = (product) => {
  if (product.product?.name) return product.product.name
  if (product.product_id) {
    const found = findProductOptionById(product.product_id)
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
    const qty = bulkDefaults.value.quantity
    const sent = bulkDefaults.value.quantity_sended
    
    factureProforma.value.products.push({
      product_id: product.id,
      quantity: qty,
      quantity_sended: Math.min(sent, qty), // Ensure sent doesn't exceed quantity
      unit: product.forme || 'pieces',
      unit_price: bulkDefaults.value.unit_price,
      total_price: qty * bulkDefaults.value.unit_price,
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
  router.push({name: 'purchasing.facture-proforma-list'})
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  Promise.all([fetchFactureProforma(), fetchProducts(1)])
})

// Watch for bulk search query changes
watch(bulkSearchQuery, async (newSearch) => {
  productsPage.value = 1
  await fetchProducts(1, newSearch)
})
</script>

<style scoped>
/* DataTable enhancements */
:deep(.p-datatable) {
  border: 0;
  border-radius: 0.5rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  color: #374151;
  font-weight: 600;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s ease-in-out;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f0fdf4;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
  background-color: #dcfce7;
}

/* Card styling */
:deep(.p-card) {
  border-radius: 0.75rem;
}

:deep(.p-card-title) {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

/* Dialog */
:deep(.p-dialog-header) {
  background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
  color: white;
}

:deep(.p-dialog-header .p-dialog-title) {
  color: white;
  font-weight: 700;
}

/* FileUpload */
:deep(.p-fileupload) {
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s ease-in-out;
}

:deep(.p-fileupload:hover) {
  border-color: #10b981;
  background-color: #f0fdf4;
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

/* Read-only mode enhancements */
:deep(.read-only-overlay) {
  position: relative;
}

:deep(.read-only-overlay::before) {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(1px);
  z-index: 5;
  pointer-events: none;
  border-radius: 0.5rem;
}

/* Enhanced card shadows */
:deep(.enhanced-card) {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  transition: all 0.3s ease-in-out;
}

:deep(.enhanced-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Status badge enhancements */
:deep(.status-badge) {
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Progress bar enhancements */
:deep(.progress-enhanced .p-progressbar) {
  height: 8px;
  border-radius: 4px;
}

:deep(.progress-enhanced .p-progressbar .p-progressbar-value) {
  border-radius: 4px;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}
</style>