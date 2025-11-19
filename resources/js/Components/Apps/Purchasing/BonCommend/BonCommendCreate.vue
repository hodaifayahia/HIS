<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50 tw-p-4 lg:tw-p-6">
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
            <div class="tw-flex tw-items-center tw-justify-between">
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-backdrop-blur-sm">
                  <i class="pi pi-plus-circle tw-text-3xl tw-text-white"></i>
                </div>
                <div>
                  <h1 class="tw-text-3xl tw-font-bold tw-text-white">Create Purchase Order</h1>
                  <p class="tw-text-blue-100 tw-mt-1">Create a new bon commend for procurement</p>
                </div>
              </div>
              <Button 
                icon="pi pi-arrow-left" 
                label="Back"
                @click="goBack"
                class="p-button-secondary tw-shadow-lg"
                size="large"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Form -->
    <form @submit.prevent="handleSubmit" class="tw-space-y-6">

      <!-- Basic Information Card -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-info-circle tw-text-blue-600"></i>
            <span>Basic Information</span>
          </div>
        </template>
        <template #content>
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6">
            <!-- Order Date -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-calendar tw-text-gray-500"></i>
                Order Date <span class="tw-text-red-500">*</span>
              </label>
              <Calendar
                v-model="formData.order_date"
                dateFormat="dd/mm/yy"
                :showIcon="true"
                placeholder="Select order date"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.order_date }"
              />
              <small v-if="errors.order_date" class="p-error">
                <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.order_date }}
              </small>
            </div>

            <!-- Expected Delivery Date -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-truck tw-text-gray-500"></i>
                Expected Delivery Date
              </label>
              <Calendar
                v-model="formData.expected_delivery_date"
                dateFormat="dd/mm/yy"
                :showIcon="true"
                :minDate="formData.order_date"
                placeholder="Select delivery date"
                class="tw-w-full"
              />
            </div>

            <!-- Priority -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-flag tw-text-gray-500"></i>
                Priority
              </label>
              <Dropdown
                v-model="formData.priority"
                :options="priorityOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select priority"
                class="tw-w-full"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <Tag 
                      :value="slotProps.option.label" 
                      :severity="getPrioritySeverity(slotProps.option.value)"
                    />
                  </div>
                </template>
                <template #value="slotProps">
                  <Tag 
                    v-if="slotProps.value"
                    :value="getPriorityLabel(slotProps.value)" 
                    :severity="getPrioritySeverity(slotProps.value)"
                  />
                </template>
              </Dropdown>
            </div>
          </div>

          <Divider />

          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6">
            <!-- Supplier Selection -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-users tw-text-gray-500"></i>
                Supplier <span class="tw-text-red-500">*</span>
              </label>
              <Dropdown
                v-model="formData.fournisseur_id"
                :options="suppliers"
                optionLabel="company_name"
                optionValue="id"
                placeholder="Select supplier"
                filter
                showClear
                :loading="loadingSuppliers"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.fournisseur_id }"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <Avatar 
                      :label="slotProps.option.company_name?.charAt(0)" 
                      class="tw-bg-blue-100 tw-text-blue-700"
                      shape="circle"
                    />
                    <div>
                      <div class="tw-font-medium">{{ slotProps.option.company_name }}</div>
                      <div class="tw-text-xs tw-text-gray-500">{{ slotProps.option.email }}</div>
                    </div>
                  </div>
                </template>
              </Dropdown>
              <small v-if="errors.fournisseur_id" class="p-error">
                <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.fournisseur_id }}
              </small>
            </div>

            <!-- Department -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-building tw-text-gray-500"></i>
                Department
              </label>
              <InputText
                v-model="formData.department"
                placeholder="Requesting department"
                class="tw-w-full"
              />
            </div>
          </div>

          <!-- Total Price (Optional) -->
          <div class="tw-mt-6">
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-dollar tw-text-gray-500"></i>
                Total Price (DZD) 
                <Tag value="Optional" severity="info" size="small" />
              </label>
              <InputNumber
                v-model="formData.price"
                mode="currency"
                currency="DZD"
                locale="fr-DZ"
                placeholder="Enter total price (optional)"
                class="tw-w-full"
                :min="0"
                :maxFractionDigits="2"
              />
              <small class="tw-text-gray-500 tw-flex tw-items-center tw-gap-1">
                <i class="pi pi-info-circle tw-text-xs"></i>
                If specified, this will be used for approval calculations. Otherwise, calculated from items.
              </small>
            </div>
          </div>

          <!-- Notes -->
          <div class="tw-mt-6 tw-space-y-2">
            <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
              <i class="pi pi-comment tw-text-gray-500"></i>
              Notes
            </label>
            <Textarea
              v-model="formData.notes"
              rows="3"
              placeholder="Additional notes or special instructions..."
              class="tw-w-full"
              autoResize
            />
          </div>
        </template>
      </Card>

      <!-- Approval Information Card (Enhanced) -->
      <Card v-if="formData.items.length > 0" class="tw-border-0 tw-shadow-xl tw-bg-gradient-to-r tw-from-yellow-50 tw-to-orange-50 hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2 tw-text-orange-800">
            <i class="pi pi-shield"></i>
            <span>Approval Requirements</span>
          </div>
        </template>
        <template #content>
          <!-- Summary Stats -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4 tw-mb-6">
            <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200 hover:tw-shadow-lg tw-transition-shadow">
              <div class="tw-text-sm tw-text-blue-600">Total Amount</div>
              <div class="tw-text-2xl tw-font-bold tw-text-blue-900">{{ formatCurrency(totalAmount) }}</div>
            </div>

            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-rounded-lg tw-p-4 tw-border tw-border-green-200 hover:tw-shadow-lg tw-transition-shadow">
              <div class="tw-text-sm tw-text-green-600">Items Count</div>
              <div class="tw-text-2xl tw-font-bold tw-text-green-900">{{ formData.items.length }}</div>
            </div>

            <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-rounded-lg tw-p-4 tw-border tw-border-purple-200 hover:tw-shadow-lg tw-transition-shadow">
              <div class="tw-text-sm tw-text-purple-600">Total Quantity</div>
              <div class="tw-text-2xl tw-font-bold tw-text-purple-900">
                {{ totalQuantity }}
              </div>
            </div>

            <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-rounded-lg tw-p-4 tw-text-white hover:tw-shadow-lg tw-transition-shadow">
              <div class="tw-text-sm tw-text-indigo-100">Status</div>
              <div class="tw-text-lg tw-font-bold">
                {{ approvalInfo.needsApproval ? 'Approval Required' : 'Ready to Submit' }}
              </div>
            </div>
          </div>

          <!-- Approval Thresholds -->
          <div v-if="approvalThresholds.length > 0">
            <h4 class="tw-text-md tw-font-semibold tw-text-gray-900 tw-mb-3">Approval Thresholds</h4>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
              <div 
                v-for="threshold in approvalThresholds" 
                :key="threshold.id"
                :class="[
                  'tw-p-3 tw-rounded-lg tw-text-center tw-transition-all tw-cursor-pointer',
                  approvalInfo.approver?.id === threshold.id 
                    ? 'tw-bg-orange-500 tw-text-white tw-shadow-lg tw-scale-105' 
                    : 'tw-bg-white tw-border tw-border-gray-200 hover:tw-border-orange-300 hover:tw-shadow-md'
                ]"
              >
                <i class="pi pi-user tw-text-2xl tw-mb-2"></i>
                <div class="tw-font-semibold">{{ threshold.name }}</div>
                <div class="tw-text-xs tw-opacity-90">{{ threshold.title }}</div>
                <div class="tw-text-sm tw-font-bold tw-mt-2">
                  Up to {{ formatCurrency(threshold.max_amount) }}
                </div>
                <div v-if="approvalInfo.approver?.id === threshold.id" class="tw-text-xs tw-mt-2">
                  <i class="pi pi-check tw-mr-1"></i>Will review
                </div>
              </div>
            </div>
          </div>

          <!-- Products Requiring Approval Alert -->
          <div v-if="hasProductsRequiringApproval" class="tw-mt-6">
            <Message severity="warn" :closable="false">
              <div class="tw-flex tw-items-start tw-gap-3">
                <i class="pi pi-info-circle tw-text-2xl"></i>
                <div>
                  <div class="tw-font-semibold tw-mb-2">Products Requiring Approval</div>
                  <div class="tw-text-sm">The following products require approval regardless of amount:</div>
                  <ul class="tw-list-disc tw-list-inside tw-mt-2 tw-text-sm">
                    <li v-for="productName in productsRequiringApprovalList" :key="productName">
                      {{ productName }}
                    </li>
                  </ul>
                </div>
              </div>
            </Message>
          </div>
        </template>
      </Card>

      <!-- Items Section with DataTable -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #title>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-shopping-cart tw-text-purple-600"></i>
              <span>Order Items</span>
              <Tag :value="`${formData.items.length} items`" severity="info" />
            </div>
            <div class="tw-flex tw-gap-2">
              <Button 
                type="button"
                icon="pi pi-plus" 
                label="Add Item"
                @click="showItemModal = true"
                class="p-button-success"
              />
              <Button 
                v-if="formData.items.length > 0"
                type="button"
                icon="pi pi-plus-circle" 
                label="Bulk Add"
                @click="showBulkAddModal = true"
                class="p-button-info"
              />
            </div>
          </div>
        </template>
        <template #content>
          <!-- Empty State -->
          <div v-if="formData.items.length === 0" class="tw-text-center tw-py-12">
            <i class="pi pi-shopping-cart tw-text-6xl tw-text-gray-300"></i>
            <p class="tw-mt-4 tw-text-gray-500 tw-text-lg">No items added yet</p>
            <Button 
              type="button"
              icon="pi pi-plus"
              label="Add First Item"
              @click="showItemModal = true"
              class="p-button-outlined tw-mt-4"
            />
          </div>

          <!-- DataTable for Items -->
          <DataTable 
            v-else
            :value="formData.items"
            responsiveLayout="scroll"
            class="tw-mt-4"
            :rowHover="true"
          >
            <!-- Product Column -->
            <Column field="product_id" header="Product" style="min-width: 250px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar 
                    :label="getProductById(slotProps.data.product_id)?.name?.charAt(0)" 
                    class="tw-bg-purple-100 tw-text-purple-700"
                    shape="square"
                  />
                  <div>
                    <div class="tw-font-semibold">
                      {{ getProductById(slotProps.data.product_id)?.name || 'Select Product' }}
                    </div>
                    <div class="tw-text-xs tw-text-gray-500">
                      {{ getProductById(slotProps.data.product_id)?.description }}
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Quantity Column -->
            <Column field="quantity" header="Quantity" style="width: 120px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.quantity"
                  :min="1"
                  showButtons
                  buttonLayout="vertical"
                  incrementButtonClass="p-button-sm"
                  decrementButtonClass="p-button-sm"
                  class="tw-w-full"
                  @input="checkApprovalRequirements"
                />
              </template>
            </Column>

            <!-- Unit Column -->
            <Column field="unit" header="Unit" style="width: 100px">
              <template #body="slotProps">
                <InputText
                  v-model="slotProps.data.unit"
                  placeholder="Unit"
                  class="tw-w-full"
                />
              </template>
            </Column>

            <!-- Price Column -->
            <Column field="price" header="Unit Price" style="width: 150px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.price"
                  mode="currency"
                  currency="DZD"
                  locale="fr-DZ"
                  :min="0"
                  class="tw-w-full"
                  @input="checkApprovalRequirements"
                />
              </template>
            </Column>

            <!-- Total Column -->
            <Column header="Total" style="width: 150px">
              <template #body="slotProps">
                <span class="tw-font-bold tw-text-green-600">
                  {{ formatCurrency((slotProps.data.price || 0) * (slotProps.data.quantity || 0)) }}
                </span>
              </template>
            </Column>

            <!-- Notes Column -->
            <Column field="notes" header="Notes" style="min-width: 200px">
              <template #body="slotProps">
                <InputText
                  v-model="slotProps.data.notes"
                  placeholder="Item notes..."
                  class="tw-w-full"
                />
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" style="width: 100px" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Button 
                    type="button"
                    icon="pi pi-pencil"
                    class="p-button-text p-button-sm p-button-info"
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
          </DataTable>

          <!-- Summary Footer -->
                    <!-- Summary Footer -->
          <div class="tw-mt-6 tw-p-4 tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-rounded-lg tw-border tw-border-gray-200 hover:tw-shadow-lg tw-transition-shadow">
            <div class="tw-flex tw-justify-between tw-items-center">
              <div class="tw-text-gray-600">
                <span class="tw-font-medium">Total Items:</span> {{ formData.items.length }}
                <span class="tw-mx-4">|</span>
                <span class="tw-font-medium">Total Quantity:</span> {{ totalQuantity }}
              </div>
              <div class="tw-text-xl tw-font-bold tw-text-green-600">
                Total: {{ formatCurrency(totalAmount) }}
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Attachments Card -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-paperclip tw-text-teal-600"></i>
            <span>Attachments</span>
            <Tag :value="`${formData.attachments.length} files`" severity="info" />
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
            :auto="false"
            chooseLabel="Select Files"
            :showUploadButton="false"
            :showCancelButton="false"
            class="tw-mb-4"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8">
                <i class="pi pi-cloud-upload tw-text-4xl tw-text-gray-400 tw-mb-2"></i>
                <p class="tw-text-gray-600">Drag and drop files here or click to browse</p>
                <p class="tw-text-sm tw-text-gray-500 tw-mt-2">PDF, DOC, XLS, Images (Max 10MB)</p>
              </div>
            </template>
          </FileUpload>

          <!-- Files Grid -->
          <div v-if="formData.attachments.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
            <div 
              v-for="(attachment, index) in formData.attachments" 
              :key="index"
              class="tw-bg-teal-50 tw-rounded-lg tw-p-4 tw-border tw-border-teal-200 hover:tw-shadow-lg tw-transition-shadow"
            >
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <i :class="getFileIcon(attachment.name)" class="tw-text-2xl tw-text-teal-600"></i>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ attachment.name }}</div>
                    <div class="tw-text-xs tw-text-gray-500">Ready to upload</div>
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

      <!-- Actions Footer -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #content>
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-text-sm tw-text-gray-500">
              <i class="pi pi-info-circle tw-mr-1"></i>
              Complete all required fields before submitting
            </div>
            <div class="tw-flex tw-gap-3">
              <Button 
                type="button"
                label="Cancel" 
                icon="pi pi-times"
                severity="secondary"
                @click="goBack"
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
                type="submit"
                label="Create Purchase Order" 
                icon="pi pi-check"
                :loading="saving"
                :disabled="!isFormValid"
                size="large"
              />
            </div>
          </div>
        </template>
      </Card>
    </form>

    <!-- Add/Edit Item Modal -->
    <Dialog 
      v-model:visible="showItemModal" 
      :header="editingItemIndex !== null ? 'Edit Item' : 'Add Item'" 
      :modal="true"
      :style="{ width: '600px' }"
    >
      <div class="tw-space-y-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Product <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="currentItem.product_id"
            :options="products"
            optionLabel="name"
            optionValue="id"
            placeholder="Select product"
            filter
            :loading="loadingProducts"
            class="tw-w-full"
            @change="onProductChangeModal"
          >
            <template #option="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <Avatar 
                  :label="slotProps.option.name?.charAt(0)" 
                  class="tw-bg-purple-100 tw-text-purple-700"
                  size="small"
                />
                <div>
                  <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ slotProps.option.description }}</div>
                </div>
              </div>
            </template>
          </Dropdown>
          <div class="tw-mt-2">
            <Button 
              type="button"
              label="Create New Product" 
              icon="pi pi-plus"
              text 
              size="small"
              @click="openCreateProductDialog"
              class="tw-text-blue-600"
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Quantity <span class="tw-text-red-500">*</span>
            </label>
            <InputNumber
              v-model="currentItem.quantity"
              :min="1"
              showButtons
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Unit</label>
            <InputText
              v-model="currentItem.unit"
              placeholder="e.g., pieces, kg"
              class="tw-w-full"
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Unit Price (DZD)</label>
          <InputNumber
            v-model="currentItem.price"
            mode="currency"
            currency="DZD"
            locale="fr-DZ"
            :min="0"
            class="tw-w-full"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes</label>
          <Textarea
            v-model="currentItem.notes"
            rows="3"
            placeholder="Special instructions for this item..."
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
          @click="closeItemModal"
        />
        <Button 
          label="Save Item" 
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
      :style="{ width: '90vw', maxWidth: '900px' }"
      :maximizable="true"
    >
      <div class="tw-space-y-4">
        <!-- Search -->
        <div class="tw-flex tw-gap-4">
          <div class="tw-flex-1 tw-relative">
            <InputText 
              v-model="productSearchQuery"
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

        <!-- Products Selection Grid -->
        <DataTable 
          v-model:selection="selectedProducts"
          :value="filteredProducts"
          :paginator="true"
          :rows="10"
          selectionMode="multiple"
          :metaKeySelection="false"
          dataKey="id"
          responsiveLayout="scroll"
        >
          <Column selectionMode="multiple" style="width: 3rem"></Column>

          <Column field="name" header="Product Name" :sortable="true">
            <template #body="slotProps">
              <div class="tw-font-semibold">{{ slotProps.data.name }}</div>
              <div class="tw-text-xs tw-text-gray-500">{{ slotProps.data.description }}</div>
            </template>
          </Column>

          <Column field="category" header="Category" :sortable="true" style="width: 150px">
            <template #body="slotProps">
              <Tag :value="slotProps.data.category || 'Uncategorized'" />
            </template>
          </Column>
        </DataTable>

        <!-- Default Settings -->
        <div v-if="selectedProducts.length > 0" class="tw-bg-blue-50 tw-p-4 tw-rounded-lg">
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
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Default Unit</label>
              <InputText
                v-model="bulkDefaults.unit"
                placeholder="pieces"
                class="tw-w-full tw-mt-1"
              />
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Default Price</label>
              <InputNumber
                v-model="bulkDefaults.price"
                mode="currency"
                currency="DZD"
                locale="fr-DZ"
                :min="0"
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
      header="Create New Product" 
      :modal="true"
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
              class="tw-w-full"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Form/Unit
            </label>
            <InputText
              v-model="newProductForm.forme"
              placeholder="e.g., tablet, ml"
              class="tw-w-full"
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
            placeholder="Enter product description"
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
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
import Message from 'primevue/message'
import Checkbox from 'primevue/checkbox'

const router = useRouter()
const toast = useToast()

// State
const saving = ref(false)
const savingDraft = ref(false)
const creatingProduct = ref(false)
const loadingSuppliers = ref(false)
const loadingProducts = ref(false)

// Modal states
const showItemModal = ref(false)
const showBulkAddModal = ref(false)
const createProductDialog = ref(false)
const editingItemIndex = ref(null)

// Data
const suppliers = ref([])
const products = ref([])
const approvalThresholds = ref([])
const selectedProducts = ref([])
const productSearchQuery = ref('')

const formData = reactive({
  order_date: new Date(),
  expected_delivery_date: null,
  fournisseur_id: null,
  department: '',
  priority: 'normal',
  notes: '',
  price: null,
  items: [],
  attachments: []
})

const currentItem = reactive({
  product_id: null,
  quantity: 1,
  unit: 'pieces',
  price: 0,
  notes: ''
})

const bulkDefaults = reactive({
  quantity: 1,
  unit: 'pieces',
  price: 0
})

const newProductForm = reactive({
  name: '',
  code_interne: null,
  category: '',
  forme: '',
  is_clinical: false,
  description: ''
})

const approvalInfo = ref({
  needsApproval: false,
  approver: null,
  totalAmount: 0,
  reason: null
})

const errors = reactive({})

// Options
const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Normal', value: 'normal' },
  { label: 'High', value: 'high' },
  { label: 'Urgent', value: 'urgent' }
]

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
  { label: 'Purchase Orders', to: '/purchasing/bon-commends' },
  { label: 'Create New', disabled: true }
])

// Computed
const isFormValid = computed(() => {
  return formData.order_date && 
         formData.fournisseur_id && 
         formData.items.length > 0 &&
         formData.items.every(item => item.product_id && item.quantity)
})

const totalAmount = computed(() => {
  return formData.items.reduce((total, item) => {
    return total + ((item.quantity || 0) * (item.price || 0))
  }, 0)
})

const totalQuantity = computed(() => {
  return formData.items.reduce((total, item) => total + (item.quantity || 0), 0)
})

const hasProductsRequiringApproval = computed(() => {
  return formData.items.some(item => {
    const product = products.value.find(p => p.id === item.product_id)
    return product && (product.is_request_approval || product.is_required_approval)
  })
})

const productsRequiringApprovalList = computed(() => {
  return formData.items
    .filter(item => {
      const product = products.value.find(p => p.id === item.product_id)
      return product && (product.is_request_approval || product.is_required_approval)
    })
    .map(item => {
      const product = products.value.find(p => p.id === item.product_id)
      return product?.name
    })
    .filter(Boolean)
})

const filteredProducts = computed(() => {
  if (!productSearchQuery.value) return products.value

  const query = productSearchQuery.value.toLowerCase()
  return products.value.filter(product => 
    product.name?.toLowerCase().includes(query) || 
    product.description?.toLowerCase().includes(query) ||
    product.category?.toLowerCase().includes(query)
  )
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

const fetchApprovalThresholds = async () => {
  try {
    const response = await axios.get('/api/bon-commends/approval-thresholds')
    approvalThresholds.value = response.data.data || []
  } catch (err) {
    console.error('Error fetching approval thresholds:', err)
  }
}

const checkApprovalRequirements = () => {
  const total = totalAmount.value
  const hasProductApproval = hasProductsRequiringApproval.value

  approvalInfo.value = {
    needsApproval: false,
    approver: null,
    totalAmount: total,
    reason: null
  }

  if (hasProductApproval) {
    approvalInfo.value.needsApproval = true
    approvalInfo.value.reason = 'Contains products that require approval'

    const approver = findApproverForAmount(total)
    if (approver) {
      approvalInfo.value.approver = approver
    }
    return
  }

  const approver = findApproverForAmount(total)
  if (approver) {
    approvalInfo.value.needsApproval = true
    approvalInfo.value.approver = approver
    approvalInfo.value.reason = 'Amount exceeds threshold'
  }
}

const findApproverForAmount = (amount) => {
  if (!approvalThresholds.value?.length) return null

  const sorted = [...approvalThresholds.value].sort((a, b) => a.max_amount - b.max_amount)
  const appropriateApprover = sorted.find(approver => approver.max_amount >= amount)

  return appropriateApprover || sorted[sorted.length - 1]
}

const getProductById = (id) => {
  return products.value.find(p => p.id === id)
}

const getPrioritySeverity = (priority) => {
  const map = {
    low: 'secondary',
    normal: 'info',
    high: 'warning',
    urgent: 'danger'
  }
  return map[priority] || 'secondary'
}

const getPriorityLabel = (priority) => {
  const map = {
    low: 'Low',
    normal: 'Normal',
    high: 'High',
    urgent: 'Urgent'
  }
  return map[priority] || priority
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

// Item Management
const editItem = (index) => {
  editingItemIndex.value = index
  Object.assign(currentItem, formData.items[index])
  showItemModal.value = true
}

const saveItem = () => {
  if (editingItemIndex.value !== null) {
    formData.items[editingItemIndex.value] = { ...currentItem }
  } else {
    formData.items.push({ ...currentItem })
  }

  checkApprovalRequirements()
  closeItemModal()

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: editingItemIndex.value !== null ? 'Item updated' : 'Item added',
    life: 2000
  })
}

const closeItemModal = () => {
  showItemModal.value = false
  editingItemIndex.value = null
  Object.assign(currentItem, {
    product_id: null,
    quantity: 1,
    unit: 'pieces',
    price: 0,
    notes: ''
  })
}

const removeItem = (index) => {
  formData.items.splice(index, 1)
  checkApprovalRequirements()
  toast.add({
    severity: 'success',
    summary: 'Removed',
    detail: 'Item removed successfully',
    life: 2000
  })
}

const onProductChangeModal = () => {
  const product = products.value.find(p => p.id === currentItem.product_id)
  if (product) {
    currentItem.unit = product.unit || 'pieces'
  }
}

// Bulk Add
const selectAllFiltered = () => {
  selectedProducts.value = [...filteredProducts.value]
}

const addBulkProducts = () => {
  selectedProducts.value.forEach(product => {
    // Use default values from product, fallback to bulkDefaults
    const defaultQuantity = product.default_quantity || bulkDefaults.quantity || 1
    const defaultUnit = product.default_unit || bulkDefaults.unit || product.unit || 'pieces'
    const defaultPrice = product.default_price || bulkDefaults.price || 0
    
    formData.items.push({
      product_id: product.id,
      quantity: defaultQuantity,
      unit: defaultUnit,
      price: defaultPrice,
      notes: ''
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
  checkApprovalRequirements()
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
    description: ''
  })
}

const closeCreateProductDialog = () => {
  createProductDialog.value = false
}

const createProduct = async () => {
  try {
    creatingProduct.value = true

    const response = await axios.post('/api/products', newProductForm)
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

  if (!formData.order_date) errors.order_date = 'Order date is required'
  if (!formData.fournisseur_id) errors.fournisseur_id = 'Supplier is required'
  if (formData.items.length === 0) errors.items = 'At least one item is required'

  formData.items.forEach((item, index) => {
    if (!item.product_id) errors[`items.${index}.product_id`] = 'Product is required'
    if (!item.quantity || item.quantity <= 0) errors[`items.${index}.quantity`] = 'Valid quantity is required'
  })

  return Object.keys(errors).length === 0
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

    formDataPayload.append('order_date', formData.order_date.toISOString().split('T')[0])
    if (formData.expected_delivery_date) {
      formDataPayload.append('expected_delivery_date', formData.expected_delivery_date.toISOString().split('T')[0])
    }
    formDataPayload.append('fournisseur_id', formData.fournisseur_id)
    formDataPayload.append('department', formData.department || '')
    formDataPayload.append('priority', formData.priority)
    formDataPayload.append('notes', formData.notes || '')
    formDataPayload.append('status', isDraft ? 'draft' : 'pending')

    formData.items.forEach((item, index) => {
      formDataPayload.append(`items[${index}][product_id]`, item.product_id)
      formDataPayload.append(`items[${index}][quantity]`, item.quantity)
      formDataPayload.append(`items[${index}][unit]`, item.unit || 'pieces')
      formDataPayload.append(`items[${index}][price]`, item.price || 0)
      formDataPayload.append(`items[${index}][notes]`, item.notes || '')
    })

    formData.attachments.forEach((attachment, index) => {
      if (attachment.file instanceof File) {
        formDataPayload.append(`attachments[${index}][file]`, attachment.file)
        formDataPayload.append(`attachments[${index}][name]`, attachment.name)
        formDataPayload.append(`attachments[${index}][description]`, attachment.description || '')
      }
    })

    const response = await axios.post('/api/bon-commends', formDataPayload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: isDraft ? 'Draft saved successfully' : 'Purchase Order created successfully',
      life: 3000
    })

    router.push('/purchasing/bon-commends')
  } catch (err) {
    console.error('Error:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to create purchase order',
      life: 3000
    })
  } finally {
    saving.value = false
    savingDraft.value = false
  }
}

const goBack = () => {
  router.push('/purchasing/bon-commends')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Watchers
watch([totalAmount, hasProductsRequiringApproval], () => {
  checkApprovalRequirements()
})

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchApprovalThresholds(),
    fetchSuppliers(),
    fetchProducts()
  ])
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

/* Dialog */
:deep(.p-dialog-header) {
}

:deep(.p-dialog-header .p-dialog-title) {
}

/* Calendar */
:deep(.p-calendar) {
}

/* FileUpload */
:deep(.p-fileupload) {
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
</style>