<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-to-slate-100 tw-p-4 lg:tw-p-6">
    <!-- Enhanced Header with Breadcrumb and Stats -->
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
                  <i class="pi pi-file-plus tw-text-3xl tw-text-white"></i>
                </div>
                <div>
                  <h1 class="tw-text-3xl tw-font-bold tw-text-white">Create Facture Proforma</h1>
                  <p class="tw-text-teal-100 tw-mt-1">Generate a new proforma invoice for suppliers</p>
                </div>
              </div>
              <Button 
                icon="pi pi-arrow-left" 
                label="Back"
                @click="goBack"
                class="p-button-secondary tw-shadow-lg hover:tw-scale-105 tw-transition-transform"
                size="large"
              />
            </div>

            <!-- Real-time Stats -->
            <div v-if="formData.products.length > 0" class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-3 tw-mt-6">
              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-text-xs tw-text-teal-200">Products</div>
                <div class="tw-text-xl tw-font-bold tw-text-white">{{ formData.products.length }}</div>
              </div>
              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-text-xs tw-text-teal-200">Total Items</div>
                <div class="tw-text-xl tw-font-bold tw-text-white">{{ totalQuantity }}</div>
              </div>
              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-text-xs tw-text-teal-200">Attachments</div>
                <div class="tw-text-xl tw-font-bold tw-text-white">{{ formData.attachments.length }}</div>
              </div>
              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-text-xs tw-text-teal-200">Total Value</div>
                <div class="tw-text-xl tw-font-bold tw-text-white">{{ formatCurrency(totalAmount) }}</div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Progress Indicator -->
    <div class="tw-mb-6">
      <Steps :model="stepsItems" :readonly="false" :activeIndex="activeStep" />
    </div>

    <!-- Main Form -->
    <form @submit.prevent="handleSubmit" class="tw-space-y-6">

      <!-- Basic Information Card -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-bg-teal-100 tw-p-2 tw-rounded-lg">
              <i class="pi pi-info-circle tw-text-teal-600"></i>
            </div>
            <span>Basic Information</span>
            <Tag v-if="formData.date && formData.fournisseur_id" value="Complete" severity="success" />
          </div>
        </template>
        <template #content>
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6">
            <!-- Date with Animation -->
            <div class="tw-space-y-2 tw-group">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-calendar tw-text-gray-500 group-hover:tw-text-teal-600 tw-transition-colors"></i>
                Date <span class="tw-text-red-500">*</span>
              </label>
              <Calendar
                v-model="formData.date"
                dateFormat="dd/mm/yy"
                :showIcon="true"
                placeholder="Select date"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.date }"
                :showButtonBar="true"
              />
              <small v-if="errors.date" class="p-error tw-flex tw-items-center tw-gap-1">
                <i class="pi pi-exclamation-circle"></i>{{ errors.date }}
              </small>
            </div>

            <!-- Supplier with Search -->
            <div class="lg:tw-col-span-2 tw-space-y-2 tw-group">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-users tw-text-gray-500 group-hover:tw-text-teal-600 tw-transition-colors"></i>
                Supplier <span class="tw-text-red-500">*</span>
              </label>
              <Dropdown
                v-model="formData.fournisseur_id"
                :options="suppliers"
                optionLabel="company_name"
                optionValue="id"
                placeholder="Search and select supplier"
                filter
                showClear
                :loading="loadingSuppliers"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.fournisseur_id }"
                :virtualScrollerOptions="{ itemSize: 40 }"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-teal-50 tw-p-2 tw-rounded">
                    <Avatar 
                      :label="slotProps.option.company_name?.charAt(0)" 
                      class="tw-bg-teal-100 tw-text-teal-700"
                      shape="circle"
                    />
                    <div>
                      <div class="tw-font-medium">{{ slotProps.option.company_name }}</div>
                      <div class="tw-text-xs tw-text-gray-500">{{ slotProps.option.email }}</div>
                    </div>
                  </div>
                </template>
              </Dropdown>
              <small v-if="errors.fournisseur_id" class="p-error tw-flex tw-items-center tw-gap-1">
                <i class="pi pi-exclamation-circle"></i>{{ errors.fournisseur_id }}
              </small>
            </div>
          </div>

          <Divider />

          <!-- Notes with Character Counter -->
          <div class="tw-space-y-2">
            <label class="tw-flex tw-items-center tw-justify-between">
              <span class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-comment tw-text-gray-500"></i>
                Notes
              </span>
              <span class="tw-text-xs tw-text-gray-500">
                {{ formData.notes.length }}/500 characters
              </span>
            </label>
            <Textarea
              v-model="formData.notes"
              rows="3"
              :maxlength="500"
              placeholder="Additional notes or special instructions..."
              class="tw-w-full"
              autoResize
            />
          </div>
        </template>
      </Card>

      <!-- Products Section with Advanced DataTable -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow">
        <template #title>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-2">
              <div class="tw-bg-cyan-100 tw-p-2 tw-rounded-lg">
                <i class="pi pi-shopping-cart tw-text-cyan-600"></i>
              </div>
              <span>Products</span>
              <Tag :value="`${formData.products.length} items`" severity="info" />
              <Tag v-if="totalAmount > 0" :value="formatCurrency(totalAmount)" severity="success" />
            </div>
            <div class="tw-flex tw-gap-2">
              <!-- Quick Actions -->
              <Button 
                v-if="formData.products.length > 0"
                type="button"
                icon="pi pi-copy" 
                label="Duplicate Last"
                @click="duplicateLastItem"
                class="p-button-secondary"
                size="small"
              />
              <Button 
                v-if="formData.products.length > 0"
                type="button"
                icon="pi pi-trash" 
                label="Clear All"
                @click="clearAllItems"
                class="p-button-danger"
                severity="danger"
                size="small"
              />
              <Button 
                type="button"
                icon="pi pi-plus-circle" 
                label="Bulk Add"
                @click="showBulkAddModal = true"
                class="p-button-info"
                size="small"
              />
              <Button 
                type="button"
                icon="pi pi-plus" 
                label="Add Item"
                @click="showItemModal = true"
                class="p-button-success"
                size="small"
              />
            </div>
          </div>
        </template>
        <template #content>
          <!-- Empty State with Animation -->
          <div v-if="formData.products.length === 0" class="tw-text-center tw-py-12">
            <div class="tw-animate-bounce">
              <i class="pi pi-inbox tw-text-6xl tw-text-gray-300"></i>
            </div>
            <p class="tw-mt-4 tw-text-gray-500 tw-text-lg">No products added yet</p>
            <div class="tw-mt-4 tw-flex tw-justify-center tw-gap-3">
              <Button 
                type="button"
                icon="pi pi-plus"
                label="Add Product"
                @click="showItemModal = true"
                class="p-button-outlined"
              />
              <Button 
                type="button"
                icon="pi pi-plus-circle"
                label="Bulk Add"
                @click="showBulkAddModal = true"
                class="p-button-outlined"
              />
            </div>
          </div>

          <!-- Advanced DataTable -->
          <DataTable 
            v-else
            :value="formData.products"
            responsiveLayout="scroll"
            class="tw-mt-4"
            :rowHover="true"
            :rowClass="getRowClass"
            editMode="cell"
            @cell-edit-complete="onCellEditComplete"
            :paginator="formData.products.length > 5"
            :rows="5"
            :rowsPerPageOptions="[5, 10, 20]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
            :globalFilterFields="['product_name', 'unit', 'notes']"
            v-model:filters="filters"
            filterDisplay="row"
            :reorderableColumns="true"
            :resizableColumns="true"
            columnResizeMode="expand"
            showGridlines
          >
            <!-- Selection Column -->
            <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

            <!-- Index Column -->
            <Column header="#" style="width: 50px" :exportable="false">
              <template #body="slotProps">
                <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-teal-500 tw-to-cyan-600 tw-text-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm tw-shadow">
                  {{ slotProps.index + 1 }}
                </div>
              </template>
            </Column>

            <!-- Product Column with Avatar -->
            <Column field="product_id" header="Product" :sortable="true" style="min-width: 250px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar 
                    :label="getProductById(slotProps.data.product_id)?.name?.charAt(0)" 
                    class="tw-bg-cyan-100 tw-text-cyan-700"
                    shape="square"
                  />
                  <div>
                    <div class="tw-font-semibold">
                      {{ getProductById(slotProps.data.product_id)?.name || 'Select Product' }}
                    </div>
                    <div class="tw-text-xs tw-text-gray-500">
                      {{ getProductById(slotProps.data.product_id)?.category }}
                    </div>
                  </div>
                  <Button
                    v-if="slotProps.data.product_id"
                    icon="pi pi-info-circle"
                    class="p-button-text p-button-sm"
                    @click="showProductInfo(slotProps.data.product_id)"
                    v-tooltip="'Product Info'"
                  />
                </div>
              </template>
              <template #editor="slotProps">
                <Dropdown
                  v-model="slotProps.data.product_id"
                  :options="products"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select product"
                  filter
                  class="tw-w-full"
                  @change="onProductChange(slotProps.data, slotProps.index)"
                />
              </template>
            </Column>

            <!-- Quantity Column with Animation -->
            <Column field="quantity" header="Quantity" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.quantity" 
                  :severity="slotProps.data.quantity > 100 ? 'warning' : 'info'"
                  class="tw-font-bold"
                />
              </template>
              <template #editor="slotProps">
                <InputNumber
                  v-model="slotProps.data.quantity"
                  :min="1"
                  :max="9999"
                  showButtons
                  buttonLayout="vertical"
                  class="tw-w-full"
                  @input="calculateItemTotal(slotProps.data)"
                />
              </template>
            </Column>

            <!-- Unit Column -->
            <Column field="unit" header="Unit" style="width: 100px">
              <template #body="slotProps">
                <Tag :value="slotProps.data.unit || 'pieces'" severity="secondary" />
              </template>
              <template #editor="slotProps">
                <Dropdown
                  v-model="slotProps.data.unit"
                  :options="unitOptions"
                  placeholder="Unit"
                  class="tw-w-full"
                />
              </template>
            </Column>

            <!-- Item Column (shows product name and unit info) -->
            <Column header="Item" style="min-width: 200px">
              <template #body="slotProps">
                <div>
                  <div class="tw-font-semibold">{{ getProductById(slotProps.data.product_id)?.name || 'Item' }}</div>
                  <div class="tw-text-xs tw-text-gray-500">Unit: {{ slotProps.data.unit || 'pieces' }}</div>
                </div>
              </template>
            </Column>

            <!-- Notes Column -->
            <Column field="notes" header="Notes" style="min-width: 200px">
              <template #body="slotProps">
                <span class="tw-text-sm tw-text-gray-600 tw-italic">
                  {{ slotProps.data.notes || 'Click to add notes...' }}
                </span>
              </template>
              <template #editor="slotProps">
                <InputText
                  v-model="slotProps.data.notes"
                  placeholder="Item notes..."
                  class="tw-w-full"
                />
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" style="width: 120px" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-1">
                  <Button 
                    type="button"
                    icon="pi pi-copy"
                    class="p-button-text p-button-sm p-button-info"
                    @click="duplicateItem(slotProps.index)"
                    v-tooltip="'Duplicate'"
                  />
                  <Button 
                    type="button"
                    icon="pi pi-pencil"
                    class="p-button-text p-button-sm p-button-warning"
                    @click="editItem(slotProps.index)"
                    v-tooltip="'Edit'"
                  />
                  <Button 
                    type="button"
                    icon="pi pi-trash"
                    class="p-button-text p-button-sm p-button-danger"
                    @click="removeItem(slotProps.index)"
                    v-tooltip="'Remove'"
                  />
                </div>
              </template>
            </Column>

            <!-- Footer Template -->
            <template #footer>
              <div class="tw-bg-gradient-to-r tw-from-teal-50 tw-to-cyan-50 tw-p-4 tw-rounded-lg">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <div class="tw-text-gray-600">
                    <span class="tw-font-medium">Total Products:</span> {{ formData.products.length }}
                    <span class="tw-mx-4">|</span>
                    <span class="tw-font-medium">Total Quantity:</span> {{ totalQuantity }}
                  </div>
                  <div class="tw-text-2xl tw-font-bold tw-text-teal-600">
                    Grand Total: {{ formatCurrency(totalAmount) }}
                  </div>
                </div>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>

      <!-- Attachments Card with Drag & Drop -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-bg-purple-100 tw-p-2 tw-rounded-lg">
              <i class="pi pi-paperclip tw-text-purple-600"></i>
            </div>
            <span>Attachments</span>
            <Tag :value="`${formData.attachments.length} files`" severity="info" />
            <Tag v-if="totalFileSize > 0" :value="formatFileSize(totalFileSize)" severity="warning" />
          </div>
        </template>
        <template #content>
          <FileUpload
            mode="advanced"
            :multiple="true"
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
            :maxFileSize="10485760"
            :disabled="saving"
            @select="handleFileSelect"
            @remove="handleFileRemove"
            :auto="false"
            chooseLabel="Select Files"
            :showUploadButton="false"
            :showCancelButton="false"
            class="tw-mb-4"
            :fileLimit="10"
          >
            <template #empty>
              <div class="tw-text-center tw-py-12">
                <i class="pi pi-cloud-upload tw-text-5xl tw-text-gray-400 tw-mb-3 tw-animate-pulse"></i>
                <p class="tw-text-lg tw-text-gray-600">Drag and drop files here</p>
                <p class="tw-text-sm tw-text-gray-500 tw-mt-2">or click to browse</p>
                <div class="tw-mt-4 tw-flex tw-justify-center tw-gap-2">
                  <Tag value="PDF" />
                  <Tag value="DOC" />
                  <Tag value="XLS" />
                  <Tag value="Images" />
                  <Tag value="Max 10MB" severity="warning" />
                </div>
              </div>
            </template>
          </FileUpload>

          <!-- Files Grid with Icons -->
          <div v-if="formData.attachments.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
            <div 
              v-for="(attachment, index) in formData.attachments" 
              :key="index"
              class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-purple-100 tw-rounded-lg tw-p-4 tw-border tw-border-purple-200 hover:tw-shadow-lg tw-transition-all hover:tw-scale-105"
            >
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-bg-white tw-p-2 tw-rounded-lg tw-shadow-sm">
                    <i :class="getFileIcon(attachment.name)" class="tw-text-2xl tw-text-purple-600"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ attachment.name }}</div>
                    <div class="tw-text-xs tw-text-purple-600">{{ formatFileSize(attachment.file?.size || 0) }}</div>
                  </div>
                </div>
                <Button
                  icon="pi pi-times"
                  class="p-button-text p-button-danger p-button-sm"
                  @click="removeAttachment(index)"
                  v-tooltip="'Remove'"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Actions Footer with Multiple Options -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #content>
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-500">
              <i class="pi pi-info-circle"></i>
              <span>Complete all required fields before submitting</span>
              <ProgressBar :value="formProgress" :showValue="false" class="tw-w-32" />
              <span class="tw-font-medium">{{ formProgress }}%</span>
            </div>
            <div class="tw-flex tw-gap-3">
              <Button 
                type="button"
                label="Cancel" 
                icon="pi pi-times"
                severity="secondary"
                @click="confirmCancel"
                :disabled="saving"
                size="large"
              />
              <Button 
                type="button"
                label="Save as Draft" 
                icon="pi pi-save"
                severity="info"
                @click="handleSubmit(true)"
                :loading="savingDraft"
                :disabled="!isFormValid"
                size="large"
              />
              <Button 
                type="button"
                label="Preview" 
                icon="pi pi-eye"
                severity="warning"
                @click="showPreview"
                :disabled="!isFormValid"
                size="large"
              />
              <Button 
                type="submit"
                label="Create Proforma" 
                icon="pi pi-check"
                :loading="saving"
                :disabled="!isFormValid"
                size="large"
                class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700"
              />
              <Button
                type="button"
                label="Create & Cancel"
                icon="pi pi-times"
                :loading="saving"
                :disabled="!isFormValid"
                size="large"
                class="tw-button-danger"
                @click="createAsCancelled"
              />
            </div>
          </div>
        </template>
      </Card>
    </form>

    <!-- Add/Edit Item Modal -->
    <Dialog 
      v-model:visible="showItemModal" 
      :header="editingItemIndex !== null ? 'Edit Product' : 'Add Product'" 
      :modal="true"
      :style="{ width: '700px' }"
      :dismissableMask="false"
      :closeOnEscape="false"
    >
      <div class="tw-space-y-4">
        <!-- Product Selection with Image -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Product <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="currentItem.product_id"
            :options="products"
            optionLabel="name"
            optionValue="id"
            placeholder="Search and select product"
            filter
            :loading="loadingProducts"
            class="tw-w-full"
            @change="onProductChangeModal"
            :virtualScrollerOptions="{ itemSize: 50 }"
          >
            <template #option="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-teal-50 tw-p-2 tw-rounded">
                <Avatar 
                  :label="slotProps.option.name?.charAt(0)" 
                  class="tw-bg-cyan-100 tw-text-cyan-700"
                  size="large"
                />
                <div class="tw-flex-1">
                  <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ slotProps.option.category }} - {{ slotProps.option.description }}</div>
                </div>
                <Tag :value="slotProps.option.unit || 'pieces'" severity="info" />
              </div>
            </template>
          </Dropdown>
          <div class="tw-mt-2 tw-flex tw-gap-2">
            <Button 
              type="button"
              label="Create New Product" 
              icon="pi pi-plus"
              text 
              size="small"
              @click="openCreateProductDialog"
              class="tw-text-teal-600"
            />
            <Button 
              v-if="currentItem.product_id"
              type="button"
              label="View History" 
              icon="pi pi-history"
              text 
              size="small"
              @click="viewProductHistory(currentItem.product_id)"
              class="tw-text-blue-600"
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-3 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Quantity <span class="tw-text-red-500">*</span>
            </label>
            <InputNumber
              v-model="currentItem.quantity"
              :min="1"
              :max="9999"
              showButtons
              class="tw-w-full"
              :class="{ 'p-invalid': currentItem.quantity < 1 }"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Unit</label>
            <Dropdown
              v-model="currentItem.unit"
              :options="unitOptions"
              placeholder="Select unit"
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Unit Price (DZD)</label>
            <InputNumber
              v-model="currentItem.unit_price"
              mode="currency"
              currency="DZD"
              locale="fr-DZ"
              :min="0"
              class="tw-w-full"
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes</label>
          <Textarea
            v-model="currentItem.notes"
            rows="3"
            placeholder="Special instructions for this item..."
            class="tw-w-full"
            autoResize
            :maxlength="200"
          />
          <small class="tw-text-gray-500">{{ currentItem.notes?.length || 0 }}/200 characters</small>
        </div>

        <!-- Live Total Display -->
        <div class="tw-bg-gradient-to-r tw-from-teal-50 tw-to-cyan-50 tw-p-4 tw-rounded-lg tw-border tw-border-teal-200">
          <div class="tw-flex tw-justify-between tw-items-center">
            <span class="tw-text-gray-700 tw-font-medium">Item Total:</span>
            <span class="tw-text-2xl tw-font-bold tw-text-teal-600">
              {{ formatCurrency((currentItem.quantity || 0) * (currentItem.unit_price || 0)) }}
            </span>
          </div>
        </div>
      </div>

      <template #footer>
        <Button 
          label="Cancel" 
          icon="pi pi-times"
          class="p-button-text"
          @click="closeItemModal"
        />
        <Button 
          label="Save & Add Another" 
          icon="pi pi-plus"
          class="p-button-secondary"
          @click="saveAndAddAnother"
          :disabled="!currentItem.product_id || !currentItem.quantity"
        />
        <Button 
          label="Save Product" 
          icon="pi pi-check"
          @click="saveItem"
          :disabled="!currentItem.product_id || !currentItem.quantity"
        />
      </template>
    </Dialog>

    <!-- Bulk Add Modal -->
    <Dialog 
      v-model:visible="showBulkAddModal" 
      header="Bulk Add Products" 
      :modal="true"
      :style="{ width: '90vw', maxWidth: '1000px' }"
      :maximizable="true"
    >
      <div class="tw-space-y-4">
        <!-- Search and Filter -->
        <div class="tw-flex tw-gap-4">
          <div class="tw-flex-1 tw-relative">
            <InputText 
              v-model="productSearchQuery"
              placeholder="Search products by name, category, or description..."
              class="tw-w-full tw-pl-10"
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
          />
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
          :value="filteredProducts"
          :paginator="true"
          :rows="10"
          selectionMode="multiple"
          dataKey="id"
          responsiveLayout="scroll"
          :metaKeySelection="false"
        >
          <Column selectionMode="multiple" style="width: 3rem"></Column>

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

          <Column field="unit" header="Unit" style="width: 100px">
            <template #body="slotProps">
              <Tag :value="slotProps.data.unit || slotProps.data.forme || 'pieces'" severity="info" />
            </template>
          </Column>

          <Column field="stock" header="Stock" style="width: 100px">
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.stock || 'N/A'" 
                :severity="slotProps.data.stock > 0 ? 'success' : 'danger'"
              />
            </template>
          </Column>
        </DataTable>

        <!-- Default Settings -->
        <div v-if="selectedProducts.length > 0" class="tw-bg-gradient-to-r tw-from-teal-50 tw-to-cyan-50 tw-p-4 tw-rounded-lg tw-border tw-border-teal-200">
          <h4 class="tw-font-semibold tw-mb-3">Default Settings for Selected Products</h4>
          <div class="tw-grid tw-grid-cols-4 tw-gap-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Default Quantity</label>
              <InputNumber
                v-model="bulkDefaults.quantity"
                :min="1"
                showButtons
                class="tw-w-full tw-mt-1"
              />
            </div>
             
      
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div class="tw-text-sm tw-text-gray-500">
            <i class="pi pi-info-circle tw-mr-1"></i>
            {{ selectedProducts.length }} product(s) selected
            <span v-if="selectedProducts.length > 0" class="tw-ml-2">
              | Estimated Total: {{ formatCurrency(selectedProducts.length * bulkDefaults.quantity * bulkDefaults.unit_price) }}
            </span>
          </div>
          <div class="tw-flex tw-gap-2">
            <Button 
              label="Cancel" 
              icon="pi pi-times"
              class="p-button-text"
              @click="closeBulkAddModal"
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
      header="Create New Product" 
      :modal="true"
      :style="{ width: '700px' }"
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
              :class="{ 'p-invalid': !newProductForm.name && newProductForm.touched }"
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
              class="tw-w-full"
              :class="{ 'p-invalid': !newProductForm.category && newProductForm.touched }"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Form/Unit
            </label>
            <Dropdown
              v-model="newProductForm.forme"
              :options="unitOptions"
              placeholder="Select unit"
              class="tw-w-full"
              editable
            />
          </div>
        </div>

        <div>
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
            <Checkbox 
              v-model="newProductForm.is_clinical"
              inputId="is_clinical"
              :binary="true"
            />
            <label for="is_clinical" class="tw-text-sm tw-font-medium tw-text-gray-700 tw-cursor-pointer">
              Clinical Product
            </label>
          </div>

          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Description
          </label>
          <Textarea
            v-model="newProductForm.description"
            placeholder="Enter product description (optional)"
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

    <!-- Confirm Dialog -->
    <ConfirmDialog></ConfirmDialog>

    <!-- Toast -->
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import FileUpload from 'primevue/fileupload'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import Divider from 'primevue/divider'
import Checkbox from 'primevue/checkbox'
import Steps from 'primevue/steps'
import ProgressBar from 'primevue/progressbar'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import { FilterMatchMode } from 'primevue/api'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State
const saving = ref(false)
const savingDraft = ref(false)
const creatingProduct = ref(false)
const loadingSuppliers = ref(false)
const loadingProducts = ref(false)
const activeStep = ref(0)

// Modal states
const showItemModal = ref(false)
const showBulkAddModal = ref(false)
const createProductDialog = ref(false)
const editingItemIndex = ref(null)

// Data
const suppliers = ref([])
const products = ref([])
const selectedProducts = ref([])
const productSearchQuery = ref('')
const selectedCategory = ref(null)

const formData = reactive({
  date: new Date(),
  fournisseur_id: null,
  notes: '',
  attachments: [],
  products: []
})

const currentItem = reactive({
  product_id: null,
  quantity: 1,
  unit: 'pieces',
  unit_price: 0,
  total_price: 0,
  notes: ''
})

const bulkDefaults = reactive({
  quantity: 1,
  unit: 'pieces',
  unit_price: 0,
  discount: 0
})

const newProductForm = reactive({
  name: '',
  code_interne: null,
  category: '',
  forme: '',
  is_clinical: false,
  description: '',
  touched: false
})

const errors = reactive({})

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

// Options
const categoryOptions = [
  { label: 'Medical Supplies', value: 'Medical Supplies' },
  { label: 'Equipment', value: 'Equipment' },
  { label: 'Medication', value: 'Medication' },
  { label: 'Consumables', value: 'Consumables' },
  { label: 'Laboratory', value: 'Laboratory' },
  { label: 'Others', value: 'Others' }
]

const unitOptions = [
 'ANAPATHE', 'ANTISEPTIQUE', 'CATHETERISME', 'CHIMIQUE', 'CHIRURGIE GÉNÉRALE',
        'CONSOMMABLE', 'FIBROSCOPIE', 'FROID', 'INSTRUMENT', 'LABORATOIRE',
        'LIGATURE', 'MÉDICAMENT', 'OSTÉO-SYNTHÈSE', 'PSYCHOTROPE 1', 'PSYCHOTROPE 2',
        'RADIOLOGIE', 'SOLUTÉ GRAND VOLUME', 'STÉRILISATION', 'STUPÉFIANT'
]

// Steps
const stepsItems = [
  { label: 'Basic Info', icon: 'pi pi-info-circle' },
  { label: 'Products', icon: 'pi pi-shopping-cart' },
  { label: 'Attachments', icon: 'pi pi-paperclip' },
  { label: 'Review', icon: 'pi pi-check' }
]

// Breadcrumb
const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Facture Proformas', to: '/purchasing/facture-proforma-list' },
  { label: 'Create New', disabled: true }
])

// Computed
const isFormValid = computed(() => {
  return formData.date && 
         formData.fournisseur_id && 
         formData.products.length > 0 &&
         formData.products.every(item => item.product_id && item.quantity)
})

const totalAmount = computed(() => {
  return formData.products.reduce((total, item) => {
    return total + (item.total_price || 0)
  }, 0)
})

const totalQuantity = computed(() => {
  return formData.products.reduce((total, item) => total + (item.quantity || 0), 0)
})

const totalFileSize = computed(() => {
  return formData.attachments.reduce((total, attachment) => {
    return total + (attachment.file?.size || 0)
  }, 0)
})

const formProgress = computed(() => {
  let progress = 0
  if (formData.date) progress += 25
  if (formData.fournisseur_id) progress += 25
  if (formData.products.length > 0) progress += 25
  if (formData.attachments.length > 0) progress += 25
  return progress
})

const filteredProducts = computed(() => {
  let filtered = products.value

  if (productSearchQuery.value) {
    const query = productSearchQuery.value.toLowerCase()
    filtered = filtered.filter(product => 
      product.name?.toLowerCase().includes(query) || 
      product.description?.toLowerCase().includes(query) ||
      product.category?.toLowerCase().includes(query)
    )
  }

  if (selectedCategory.value) {
    filtered = filtered.filter(product => product.category === selectedCategory.value)
  }

  return filtered
})

// Methods
const fetchSuppliers = async () => {
  try {
    loadingSuppliers.value = true
    const response = await axios.get('/api/fournisseurs')
    suppliers.value = response.data.data || response.data || []
  } catch (err) {
    console.error('Error fetching suppliers:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load suppliers',
      life: 3000
    })
  } finally {
    loadingSuppliers.value = false
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

const getProductById = (id) => {
  return products.value.find(p => p.id === id)
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

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const getRowClass = (data) => {
  if (data.total_price > 100000) return 'tw-bg-yellow-50'
  if (data.quantity > 100) return 'tw-bg-blue-50'
  return ''
}

// Item Management
const editItem = (index) => {
  editingItemIndex.value = index
  Object.assign(currentItem, formData.products[index])
  showItemModal.value = true
}

const duplicateItem = (index) => {
  const item = { ...formData.products[index] }
  formData.products.splice(index + 1, 0, item)
  toast.add({
    severity: 'success',
    summary: 'Duplicated',
    detail: 'Product duplicated successfully',
    life: 2000
  })
}

const duplicateLastItem = () => {
  if (formData.products.length > 0) {
    const lastItem = { ...formData.products[formData.products.length - 1] }
    formData.products.push(lastItem)
    toast.add({
      severity: 'success',
      summary: 'Duplicated',
      detail: 'Last product duplicated',
      life: 2000
    })
  }
}

const clearAllItems = () => {
  confirm.require({
    message: 'Are you sure you want to remove all products?',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      formData.products = []
      toast.add({
        severity: 'success',
        summary: 'Cleared',
        detail: 'All products removed',
        life: 2000
      })
    }
  })
}

const saveItem = () => {
  const itemData = {
    ...currentItem,
    total_price: (currentItem.quantity || 0) * (currentItem.unit_price || 0)
  }

  if (editingItemIndex.value !== null) {
    formData.products[editingItemIndex.value] = itemData
  } else {
    formData.products.push(itemData)
  }

  closeItemModal()

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: editingItemIndex.value !== null ? 'Product updated' : 'Product added',
    life: 2000
  })
}

const saveAndAddAnother = () => {
  saveItem()
  Object.assign(currentItem, {
    product_id: null,
    quantity: 1,
    unit: 'pieces',
    unit_price: 0,
    total_price: 0,
    notes: ''
  })
  showItemModal.value = true
}

const closeItemModal = () => {
  showItemModal.value = false
  editingItemIndex.value = null
  Object.assign(currentItem, {
    product_id: null,
    quantity: 1,
    unit: 'pieces',
    unit_price: 0,
    total_price: 0,
    notes: ''
  })
}

const removeItem = (index) => {
  confirm.require({
    message: 'Are you sure you want to remove this product?',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      formData.products.splice(index, 1)
      toast.add({
        severity: 'success',
        summary: 'Removed',
        detail: 'Product removed successfully',
        life: 2000
      })
    }
  })
}

const onProductChange = (item, index) => {
  const product = products.value.find(p => p.id === item.product_id)
  if (product) {
    item.unit = product.unit || product.forme || 'pieces'
  }
  calculateItemTotal(item)
}

const onProductChangeModal = () => {
  const product = products.value.find(p => p.id === currentItem.product_id)
  if (product) {
    currentItem.unit = product.unit || product.forme || 'pieces'
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

const showProductInfo = (productId) => {
  const product = getProductById(productId)
  if (product) {
    toast.add({
      severity: 'info',
      summary: product.name,
      detail: `Category: ${product.category}
Unit: ${product.unit || 'pieces'}
${product.description || 'No description'}`,
      life: 5000
    })
  }
}

// Bulk Add
const selectAllFiltered = () => {
  selectedProducts.value = [...filteredProducts.value]
}

const addBulkProducts = () => {
  selectedProducts.value.forEach(product => {
    const price = bulkDefaults.unit_price * (1 - bulkDefaults.discount / 100)
    formData.products.push({
      product_id: product.id,
      quantity: bulkDefaults.quantity,
      unit: bulkDefaults.unit || product.unit || product.forme || 'pieces',
      unit_price: price,
      total_price: bulkDefaults.quantity * price,
      notes: ''
    })
  })

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${selectedProducts.value.length} products added`,
    life: 3000
  })

  closeBulkAddModal()
}

const closeBulkAddModal = () => {
  showBulkAddModal.value = false
  selectedProducts.value = []
  productSearchQuery.value = ''
  selectedCategory.value = null
}

// File Management
const handleFileSelect = (event) => {
  const files = event.files
  files.forEach(file => {
    formData.attachments.push({
      file: file,
      name: file.name,
      description: ''
    })
  })
  toast.add({
    severity: 'success',
    summary: 'Files Selected',
    detail: `${files.length} file(s) ready to upload`,
    life: 2000
  })
}

const handleFileRemove = (event) => {
  // Handle file removal if needed
}

const removeAttachment = (index) => {
  formData.attachments.splice(index, 1)
}

// Product Creation
const openCreateProductDialog = () => {
  createProductDialog.value = true
  Object.assign(newProductForm, {
    name: '',
    code_interne: null,
    category: '',
    forme: '',
    is_clinical: false,
    description: '',
    touched: false
  })
}

const closeCreateProductDialog = () => {
  createProductDialog.value = false
}

const createProduct = async () => {
  try {
    creatingProduct.value = true
    newProductForm.touched = true

    if (!newProductForm.name || !newProductForm.category) {
      return
    }

    const response = await axios.post('/api/products', {
      name: newProductForm.name,
      code_interne: newProductForm.code_interne,
      category: newProductForm.category,
      forme: newProductForm.forme || 'pieces',
      is_clinical: newProductForm.is_clinical,
      description: newProductForm.description
    })

    const createdProduct = response.data.data || response.data
    products.value.push(createdProduct)

    if (showItemModal.value) {
      currentItem.product_id = createdProduct.id
      currentItem.unit = createdProduct.forme || 'pieces'
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Product created successfully',
      life: 3000
    })

    closeCreateProductDialog()
  } catch (err) {
    console.error('Error creating product:', err)
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

// Form Validation
const validateForm = () => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!formData.date) errors.date = 'Date is required'
  if (!formData.fournisseur_id) errors.fournisseur_id = 'Supplier is required'
  if (formData.products.length === 0) errors.products = 'At least one product is required'

  formData.products.forEach((item, index) => {
    if (!item.product_id) errors[`products.${index}.product_id`] = 'Product is required'
    if (!item.quantity || item.quantity <= 0) errors[`products.${index}.quantity`] = 'Valid quantity is required'
  })

  return Object.keys(errors).length === 0
}

// Preview
const showPreview = () => {
  toast.add({
    severity: 'info',
    summary: 'Preview',
    detail: 'Preview feature coming soon',
    life: 3000
  })
}

// Submit
const handleSubmit = async (isDraft = false) => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please correct the errors in the form',
      life: 3000
    })
    return
  }

  try {
    if (isDraft) {
      savingDraft.value = true
    } else {
      saving.value = true
    }

    const formDataPayload = new FormData()

    formDataPayload.append('date', formData.date.toISOString().split('T')[0])
    formDataPayload.append('fournisseur_id', formData.fournisseur_id)
    formDataPayload.append('notes', formData.notes || '')
    formDataPayload.append('status', isDraft ? 'draft' : 'pending')

    formData.products.forEach((item, index) => {
      formDataPayload.append(`products[${index}][product_id]`, item.product_id)
      formDataPayload.append(`products[${index}][quantity]`, item.quantity)
      formDataPayload.append(`products[${index}][unit_price]`, item.unit_price || 0)
      formDataPayload.append(`products[${index}][total_price]`, item.total_price || 0)
      formDataPayload.append(`products[${index}][notes]`, item.notes || '')
    })

    formData.attachments.forEach((attachment, index) => {
      if (attachment.file instanceof File) {
        formDataPayload.append(`attachments[${index}][file]`, attachment.file)
        formDataPayload.append(`attachments[${index}][name]`, attachment.name)
        formDataPayload.append(`attachments[${index}][description]`, attachment.description || '')
      }
    })

    const response = await axios.post('/api/facture-proformas', formDataPayload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: isDraft ? 'Draft saved successfully' : 'Facture Proforma created successfully',
      life: 3000
    })

    router.push({ name: 'purchasing.facture-proforma-list' })
  } catch (err) {
    console.error('Error:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to create facture proforma',
      life: 3000
    })
  } finally {
    saving.value = false
    savingDraft.value = false
  }
}

const createAsCancelled = async () => {
  // reuse the same payload logic but send status=cancelled
  if (!validateForm()) {
    toast.add({ severity: 'warn', summary: 'Validation Error', detail: 'Please correct the errors in the form', life: 3000 })
    return
  }

  try {
    saving.value = true
    const formDataPayload = new FormData()

    formDataPayload.append('date', formData.date.toISOString().split('T')[0])
    formDataPayload.append('fournisseur_id', formData.fournisseur_id)
    formDataPayload.append('notes', formData.notes || '')
    formDataPayload.append('status', 'cancelled')

    formData.products.forEach((item, index) => {
      formDataPayload.append(`products[${index}][product_id]`, item.product_id)
      formDataPayload.append(`products[${index}][quantity]`, item.quantity)
      formDataPayload.append(`products[${index}][unit_price]`, item.unit_price || 0)
      formDataPayload.append(`products[${index}][total_price]`, item.total_price || 0)
      formDataPayload.append(`products[${index}][notes]`, item.notes || '')
    })

    formData.attachments.forEach((attachment, index) => {
      if (attachment.file instanceof File) {
        formDataPayload.append(`attachments[${index}][file]`, attachment.file)
        formDataPayload.append(`attachments[${index}][name]`, attachment.name)
        formDataPayload.append(`attachments[${index}][description]`, attachment.description || '')
      }
    })

    const response = await axios.post('/api/facture-proformas', formDataPayload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.add({ severity: 'success', summary: 'Cancelled', detail: 'Facture Proforma created as cancelled', life: 3000 })
    router.push({ name: 'purchasing.facture-proforma-list' })
  } catch (err) {
    console.error('Error creating cancelled proforma:', err)
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Failed to create cancelled facture proforma', life: 3000 })
  } finally {
    saving.value = false
  }
}

const confirmCancel = () => {
  if (formData.products.length > 0 || formData.attachments.length > 0) {
    confirm.require({
      message: 'You have unsaved changes. Are you sure you want to leave?',
      header: 'Confirmation',
      icon: 'pi pi-exclamation-triangle',
      accept: () => {
        goBack()
      }
    })
  } else {
    goBack()
  }
}

const goBack = () => {
  router.push({
    name: 'purchasing.facture-proforma-list'
  })
}

const viewProductHistory = (productId) => {
  router.push({
    name: 'purchasing.product.history',
    params: { id: productId }
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Watchers
watch(() => formData.date, () => { if (formData.date && formData.fournisseur_id) activeStep.value = 1 })
watch(() => formData.products.length, () => { if (formData.products.length > 0) activeStep.value = 2 })
watch(() => formData.attachments.length, () => { if (formData.attachments.length > 0) activeStep.value = 3 })

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchSuppliers(),
    fetchProducts()
  ])
})
</script>

<style scoped>
/* DataTable enhancements */
:deep(.p-datatable) {
  @apply border-0 tw-rounded-lg;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-text-gray-700 tw-font-semibold tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply hover:tw-bg-cyan-50 tw-transition-colors;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
  @apply bg-cyan-100;
}

/* Card styling */
:deep(.p-card) {
  @apply rounded-xl;
}

:deep(.p-card-title) {
  @apply text-xl tw-font-bold tw-text-gray-800;
}

/* Dialog */
:deep(.p-dialog-header) {
  @apply bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700 tw-text-white;
}

:deep(.p-dialog-header .p-dialog-title) {
  @apply text-white tw-font-bold;
}

/* Calendar */
:deep(.p-calendar) {
  @apply w-full;
}

:deep(.p-calendar:hover) {
  @apply shadow-md tw-transition-shadow;
}

/* FileUpload */
:deep(.p-fileupload) {
  @apply border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg hover:tw-border-teal-400 hover:tw-bg-teal-50 tw-transition-all;
}

/* Steps */
:deep(.p-steps .p-steps-item) {
  @apply flex-1;
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

.tw-card-enter {
  animation: slideIn 0.3s ease-out;
}

/* Hover effects */
:deep(.p-button:not(:disabled):hover) {
  @apply transform tw-scale-105 tw-transition-transform;
}

:deep(.p-card:hover) {
  @apply shadow-2xl tw-transition-shadow;
}
</style>