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
            <div class="tw-flex tw-items-center tw-justify-between">
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-backdrop-blur-sm tw-animate-pulse-slow">
                  <i class="pi pi-inbox-in tw-text-3xl tw-text-white"></i>
                </div>
                <div>
                  <h1 class="tw-text-3xl tw-font-bold tw-text-white">Create Goods Receipt</h1>
                  <p class="tw-text-emerald-100 tw-mt-1">Record incoming goods and verify deliveries</p>
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
          </div>
        </template>
      </Card>
    </div>

    <!-- Progress Indicator -->
    <div class="tw-mb-6">
      <Steps :model="stepsItems" :readonly="false" :activeIndex="activeStep" />
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
            <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">Loading form data...</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Error State -->
    <Message v-if="error" severity="error" :closable="false" class="tw-mb-4 tw-shadow-xl">
      <i class="pi pi-exclamation-triangle tw-mr-2"></i>
      {{ error }}
    </Message>

    <!-- Main Form -->
    <form v-if="!loading" @submit.prevent="createReception" class="tw-space-y-6">

      <!-- Basic Information Card -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-bg-emerald-100 tw-p-2 tw-rounded-lg">
              <i class="pi pi-info-circle tw-text-emerald-600"></i>
            </div>
            <span>Basic Information</span>
            <Tag v-if="isBasicInfoComplete" value="Complete" severity="success" />
          </div>
        </template>
        <template #content>
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6">
            <!-- Purchase Order Selection -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-file tw-text-gray-500"></i>
                Purchase Order
              </label>
              <Dropdown
                v-model="createForm.bon_commend_id"
                :options="availablePurchaseOrders"
                optionLabel="bonCommendCode"
                optionValue="id"
                placeholder="Select a purchase order (optional)"
                class="tw-w-full"
                filter
                showClear
                :loading="loadingPurchaseOrders"
                :virtualScrollerOptions="{ itemSize: 50 }"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-emerald-50 tw-p-2 tw-rounded">
                    <Avatar 
                      icon="pi pi-file"
                      class="tw-bg-emerald-100 tw-text-emerald-700"
                      shape="circle"
                    />
                    <div class="tw-flex-1">
                      <div class="tw-font-medium">{{ slotProps.option.bonCommendCode }}</div>
                      <div class="tw-text-xs tw-text-gray-500">
                        {{ slotProps.option.fournisseur?.company_name }}
                      </div>
                    </div>
                    <Tag :value="formatDate(slotProps.option.created_at)" severity="info" />
                  </div>
                </template>
              </Dropdown>
            </div>

            <!-- Supplier Selection -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-building tw-text-gray-500"></i>
                Supplier <span v-if="!selectedPurchaseOrder" class="tw-text-red-500">*</span>
              </label>
              <Dropdown
                v-model="createForm.fournisseur_id"
                :options="suppliers"
                optionLabel="company_name"
                optionValue="id"
                placeholder="Select supplier"
                class="tw-w-full"
                filter
                showClear
               :disabled="!!selectedPurchaseOrder"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3">
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
              <small v-if="!createForm.fournisseur_id && !selectedPurchaseOrder && submitted" class="p-error">
                <i class="pi pi-exclamation-circle tw-mr-1"></i>Supplier is required
              </small>
            </div>

            <!-- Received By -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-user tw-text-gray-500"></i>
                Received By <span class="tw-text-red-500">*</span>
              </label>
              <Dropdown
                v-model="createForm.received_by"
                :options="users"
                optionLabel="name"
                optionValue="id"
                placeholder="Select who received the goods"
                class="tw-w-full"
                filter
                :invalid="!createForm.received_by && submitted"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <Avatar 
                      :label="slotProps.option.name?.charAt(0)" 
                      class="tw-bg-blue-100 tw-text-blue-700"
                      shape="circle"
                    />
                    <div>
                      <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                      <div class="tw-text-xs tw-text-gray-500">{{ slotProps.option.email }}</div>
                    </div>
                  </div>
                </template>
              </Dropdown>
              <small v-if="!createForm.received_by && submitted" class="p-error">
                <i class="pi pi-exclamation-circle tw-mr-1"></i>Received By is required
              </small>
            </div>
          </div>

          <Divider />

          <!-- Dates and Numbers -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-calendar tw-text-gray-500"></i>
                Receipt Date <span class="tw-text-red-500">*</span>
              </label>
              <Calendar
                v-model="createForm.date_reception"
                placeholder="Select date"
                showIcon
                class="tw-w-full"
                :invalid="!createForm.date_reception && submitted"
                :showButtonBar="true"
              />
              <small v-if="!createForm.date_reception && submitted" class="p-error">
                <i class="pi pi-exclamation-circle tw-mr-1"></i>Receipt Date is required
              </small>
            </div>

            <div class="tw-space-y-2">
              <label class="tw-text-sm tw-font-semibold tw-text-gray-700">
                Delivery Note Number
              </label>
              <InputText
                v-model="createForm.bon_livraison_numero"
                placeholder="Enter delivery note number"
                class="tw-w-full"
              />
            </div>

            <div class="tw-space-y-2">
              <label class="tw-text-sm tw-font-semibold tw-text-gray-700">
                Delivery Date
              </label>
              <Calendar
                v-model="createForm.bon_livraison_date"
                placeholder="Select delivery date"
                showIcon
                class="tw-w-full"
              />
            </div>

            <div class="tw-space-y-2">
              <label class="tw-text-sm tw-font-semibold tw-text-gray-700">
                Invoice Number
              </label>
              <InputText
                v-model="createForm.facture_numero"
                placeholder="Enter invoice number"
                class="tw-w-full"
              />
            </div>

            <div class="tw-space-y-2">
              <label class="tw-text-sm tw-font-semibold tw-text-gray-700">
                Invoice Date
              </label>
              <Calendar
                v-model="createForm.facture_date"
                placeholder="Select invoice date"
                showIcon
                class="tw-w-full"
              />
            </div>

            <div class="tw-space-y-2">
              <label class="tw-text-sm tw-font-semibold tw-text-gray-700">
                Number of Packages
              </label>
              <InputNumber
                v-model="createForm.nombre_colis"
                placeholder="Enter number"
                class="tw-w-full"
                :min="0"
                showButtons
              />
            </div>
          </div>

          <Divider />

          <!-- Observations -->
          <div class="tw-space-y-2">
            <label class="tw-flex tw-items-center tw-justify-between">
              <span class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-comment tw-text-gray-500"></i>
                Observations
              </span>
              <span class="tw-text-xs tw-text-gray-500">
                {{ createForm.observation.length }}/500 characters
              </span>
            </label>
            <Textarea
              v-model="createForm.observation"
              placeholder="Enter any observations or notes"
              rows="3"
              :maxlength="500"
              class="tw-w-full"
              autoResize
            />
          </div>
        </template>
      </Card>

      <!-- Purchase Order Preview (if selected) -->
      <Card v-if="selectedPurchaseOrder" class="tw-border-0 tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-eye tw-text-blue-600"></i>
            <span>Purchase Order Details</span>
            <Tag :value="selectedPurchaseOrder.bonCommendCode" severity="info" />
          </div>
        </template>
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-lg tw-p-4">
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-4">
              <div>
                <span class="tw-text-xs tw-text-gray-600">Order Code:</span>
                <p class="tw-font-semibold">{{ selectedPurchaseOrder.bonCommendCode }}</p>
              </div>
              <div>
                <span class="tw-text-xs tw-text-gray-600">Supplier:</span>
                <p class="tw-font-semibold">{{ selectedPurchaseOrder.fournisseur?.company_name }}</p>
              </div>
              <div>
                <span class="tw-text-xs tw-text-gray-600">Status:</span>
                <Tag 
                  :value="selectedPurchaseOrder.status" 
                  :severity="getPurchaseOrderStatusSeverity(selectedPurchaseOrder.status)"
                />
              </div>
            </div>

            <div v-if="selectedPurchaseOrder.items?.length">
              <span class="tw-text-xs tw-text-gray-600 tw-font-semibold">
                Items ({{ selectedPurchaseOrder.items.length }}):
              </span>
              <div class="tw-mt-2 tw-space-y-2 tw-max-h-32 tw-overflow-y-auto">
                <div 
                  v-for="item in selectedPurchaseOrder.items" 
                  :key="item.id"
                  class="tw-flex tw-justify-between tw-items-center tw-text-sm tw-bg-white tw-p-2 tw-rounded"
                >
                  <span class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-box tw-text-blue-500"></i>
                    <span 
                      v-if="item.product?.id" 
                      @click="viewProductHistory(item.product.id)"
                      class="tw-cursor-pointer tw-text-blue-600 hover:tw-underline"
                    >
                      {{ item.product.name }}
                    </span>
                    <span v-else>{{ item.product?.name }}</span>
                  </span>
                  <Tag :value="`Qty: ${item.quantity_desired}`" severity="info" />
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Items Reception Section with DataTable -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-list tw-text-emerald-600"></i>
              <span>Items Reception</span>
              <Tag v-if="totalItemsCount > 0" :value="`${totalItemsCount} items`" severity="success" />
            </div>
            <div class="tw-flex tw-gap-2">
              <Button 
                v-if="!createForm.bon_commend_id && manualItems.length > 0"
                @click="createBonCommendFromReception"
                :loading="creatingBonCommend"
                label="Create Purchase Order"
                icon="pi pi-file-plus"
                class="p-button-info"
                size="small"
              />
              <Button 
                @click="addManualItem"
                icon="pi pi-plus"
                label="Add Item"
                class="p-button-success"
                size="small"
              />
              <Button 
                v-if="selectedPurchaseOrder"
                @click="addUnexpectedItem"
                icon="pi pi-exclamation-circle"
                label="Add Unexpected"
                class="p-button-warning"
                size="small"
              />
            </div>
          </div>
        </template>
        <template #content>
          <!-- Manual Items (no purchase order) -->
          <div v-if="!selectedPurchaseOrder">
            <DataTable 
              :value="manualItems"
              responsiveLayout="scroll"
              class="tw-mt-4"
              :rowHover="true"
              editMode="cell"
              @cell-edit-complete="onCellEditComplete"
              :paginator="manualItems.length > 5"
              :rows="5"
              showGridlines
            >
              <!-- Index Column -->
              <Column header="#" style="width: 50px">
                <template #body="slotProps">
                  <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-emerald-500 tw-to-teal-600 tw-text-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm">
                    {{ slotProps.index + 1 }}
                  </div>
                </template>
              </Column>

              <!-- Product Column -->
              <Column field="product_id" header="Product" style="min-width: 250px">
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <Dropdown
                      v-model="slotProps.data.product_id"
                      :options="availableProducts"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select product"
                      class="tw-flex-1"
                      filter
                      :invalid="!slotProps.data.product_id && submitted"
                    />
                    <Button
                      v-if="slotProps.data.product_id"
                      icon="pi pi-history"
                      class="p-button-text p-button-sm"
                      @click="viewProductHistory(slotProps.data.product_id)"
                      v-tooltip="'View History'"
                    />
                    <Button
                      icon="pi pi-plus"
                      class="p-button-success p-button-sm"
                      @click="openCreateProductDialog('manual', slotProps.index)"
                      v-tooltip="'Create Product'"
                    />
                  </div>
                </template>
              </Column>

              <!-- Quantity Column -->
              <Column field="quantity_received" header="Quantity" style="width: 120px">
                <template #body="slotProps">
                  <Tag :value="slotProps.data.quantity_received" severity="info" />
                </template>
                <template #editor="slotProps">
                  <InputNumber
                    v-model="slotProps.data.quantity_received"
                    :min="1"
                    showButtons
                    buttonLayout="vertical"
                    class="tw-w-full"
                  />
                </template>
              </Column>

              <!-- Unit Price Column -->
              <Column field="unit_price" header="Unit Price" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-font-medium">{{ formatCurrency(slotProps.data.unit_price) }}</span>
                </template>
                <template #editor="slotProps">
                  <InputNumber
                    v-model="slotProps.data.unit_price"
                    mode="currency"
                    currency="DZD"
                    locale="fr-DZ"
                    :min="0"
                    class="tw-w-full"
                  />
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
              <Column header="Actions" style="width: 80px" :exportable="false">
                <template #body="slotProps">
                  <Button 
                    icon="pi pi-trash"
                    class="p-button-danger p-button-text p-button-sm"
                    @click="removeManualItem(slotProps.index)"
                    v-tooltip="'Remove item'"
                  />
                </template>
              </Column>
            </DataTable>
          </div>

          <!-- Ordered Items (with purchase order) -->
          <div v-if="selectedPurchaseOrder" class="tw-space-y-6">
            <!-- Ordered Items Table -->
            <div>
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-box tw-text-blue-600"></i>
                Ordered Items
                <Tag :value="`${orderedItems.length} items`" severity="info" />
              </h4>

              <DataTable 
                :value="orderedItems"
                responsiveLayout="scroll"
                :rowHover="true"
                showGridlines
                class="tw-shadow-sm"
              >
                <!-- Product Column -->
                <Column field="product.name" header="Product" :sortable="true">
                  <template #body="slotProps">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Avatar 
                        :label="slotProps.data.product?.name?.charAt(0)" 
                        class="tw-bg-blue-100 tw-text-blue-700"
                        shape="square"
                      />
                      <div>
                        <div class="tw-font-semibold">
                          <span 
                            v-if="slotProps.data.product?.id"
                            @click="viewProductHistory(slotProps.data.product.id)"
                            class="tw-cursor-pointer tw-text-blue-600 hover:tw-underline"
                          >
                            {{ slotProps.data.product.name }}
                          </span>
                          <span v-else>{{ slotProps.data.product?.name }}</span>
                        </div>
                        <div class="tw-text-xs tw-text-gray-500">
                          Code: {{ slotProps.data.product?.code_interne }}
                        </div>
                      </div>
                    </div>
                  </template>
                </Column>

                <!-- Ordered Quantity Column -->
                <Column field="quantity_desired" header="Ordered" style="width: 100px">
                  <template #body="slotProps">
                    <Tag :value="slotProps.data.quantity_desired" severity="info" />
                  </template>
                </Column>

                <!-- Received Quantity Column -->
                <Column field="quantity_received" header="Received" style="width: 120px">
                  <template #body="slotProps">
                    <InputNumber
                      v-model="slotProps.data.quantity_received"
                      :min="0"
                      showButtons
                      buttonLayout="horizontal"
                      class="tw-w-full"
                      @input="calculateSurplusShortage(slotProps.data)"
                    />
                  </template>
                </Column>

                <!-- Variance Column -->
                <Column header="Variance" style="width: 150px">
                  <template #body="slotProps">
                    <div class="tw-flex tw-gap-2">
                      <div v-if="slotProps.data.surplus > 0" class="tw-bg-green-100 tw-px-2 tw-py-1 tw-rounded">
                        <span class="tw-text-green-700 tw-font-bold">+{{ slotProps.data.surplus }}</span>
                      </div>
                      <div v-if="slotProps.data.shortage > 0" class="tw-bg-red-100 tw-px-2 tw-py-1 tw-rounded">
                        <span class="tw-text-red-700 tw-font-bold">-{{ slotProps.data.shortage }}</span>
                      </div>
                      <div v-if="slotProps.data.surplus === 0 && slotProps.data.shortage === 0" class="tw-text-gray-500">
                        <i class="pi pi-check tw-text-green-500"></i> Match
                      </div>
                    </div>
                  </template>
                </Column>

                <!-- Notes Column -->
                <Column field="notes" header="Notes">
                  <template #body="slotProps">
                    <InputText
                      v-model="slotProps.data.notes"
                      placeholder="Add notes..."
                      class="tw-w-full"
                    />
                  </template>
                </Column>
              </DataTable>
            </div>

            <!-- Unexpected Items Table -->
            <div v-if="unexpectedItems.length > 0" class="tw-mt-6">
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-exclamation-circle tw-text-orange-600"></i>
                Unexpected Items (Surplus)
                <Tag :value="`${unexpectedItems.length} items`" severity="warning" />
              </h4>

              <DataTable 
                :value="unexpectedItems"
                responsiveLayout="scroll"
                :rowHover="true"
                showGridlines
                class="tw-shadow-sm"
              >
                <!-- Similar columns structure -->
                <Column field="product_id" header="Product" style="min-width: 250px">
                  <template #body="slotProps">
                    <Dropdown
                      v-model="slotProps.data.product_id"
                      :options="availableProducts"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select product"
                      class="tw-w-full"
                      filter
                    />
                  </template>
                </Column>

                <Column field="quantity_received" header="Quantity" style="width: 120px">
                  <template #body="slotProps">
                    <InputNumber
                      v-model="slotProps.data.quantity_received"
                      :min="1"
                      showButtons
                      class="tw-w-full"
                    />
                  </template>
                </Column>
                    <!-- Received Quantity Column -->
                <Column field="quantity_received" header="Received" style="width: 120px">
                  <template #body="slotProps">
                    <InputNumber
                      v-model="slotProps.data.quantity_received"
                      :min="0"
                      showButtons
                      buttonLayout="horizontal"
                      class="tw-w-full"
                      @input="calculateSurplusShortage(slotProps.data)"
                    />
                  </template>
                </Column>

                <Column field="notes" header="Notes">
                  <template #body="slotProps">
                    <InputText
                      v-model="slotProps.data.notes"
                      placeholder="Add notes..."
                      class="tw-w-full"
                    />
                  </template>
                </Column>

                <Column header="Actions" style="width: 80px">
                  <template #body="slotProps">
                    <Button 
                      icon="pi pi-trash"
                      class="p-button-danger p-button-text p-button-sm"
                      @click="removeUnexpectedItem(slotProps.index)"
                    />
                  </template>
                </Column>
              </DataTable>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="totalItemsCount === 0" class="tw-text-center tw-py-12 tw-bg-gray-50 tw-rounded-lg tw-border-2 tw-border-dashed tw-border-gray-300">
            <i class="pi pi-inbox tw-text-gray-400 tw-text-6xl tw-mb-3"></i>
            <p class="tw-text-gray-500 tw-font-medium tw-text-lg">No items added yet</p>
            <p class="tw-text-gray-400 tw-text-sm tw-mt-2">Click "Add Item" to start adding products</p>
          </div>
        </template>
      </Card>

      <!-- Attachments Card -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-paperclip tw-text-purple-600"></i>
            <span>Attachments</span>
            <Tag :value="`${selectedAttachments.length} files`" severity="info" />
          </div>
        </template>
        <template #content>
          <FileUpload
            mode="advanced"
            :multiple="true"
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
            :maxFileSize="10485760"
            :disabled="creating"
            :showUploadButton="false"
            :showCancelButton="false"
            chooseLabel="Select Files"
            @select="handleFileSelect"
            :fileLimit="10"
          >
            <template #empty>
              <div class="tw-text-center tw-py-12">
                <i class="pi pi-cloud-upload tw-text-5xl tw-text-gray-400 tw-animate-pulse"></i>
                <p class="tw-text-lg tw-text-gray-600 tw-mt-3">Drag & drop or click to browse files</p>
                <div class="tw-mt-4 tw-flex tw-justify-center tw-gap-2">
                  <Tag value="PDF" />
                  <Tag value="Images" />
                  <Tag value="Documents" />
                  <Tag value="Max 10MB" severity="warning" />
                </div>
              </div>
            </template>
          </FileUpload>

          <div v-if="selectedAttachments.length > 0" class="tw-mt-6">
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
              <div 
                v-for="(file, index) in selectedAttachments" 
                :key="index"
                class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-indigo-50 tw-rounded-lg tw-p-4 tw-border tw-border-purple-200 hover:tw-shadow-lg tw-transition-all"
              >
                <div class="tw-flex tw-items-start tw-justify-between">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <div class="tw-bg-white tw-p-2 tw-rounded-lg tw-shadow-sm">
                      <i :class="getFileIcon(file.name)" class="tw-text-2xl tw-text-purple-600"></i>
                    </div>
                    <div class="tw-flex-1">
                      <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ file.name }}</div>
                      <div class="tw-text-xs tw-text-purple-600">{{ formatFileSize(file.size) }}</div>
                    </div>
                  </div>
                  <Button 
                    icon="pi pi-times" 
                    size="small" 
                    text
                    severity="danger"
                    @click="removeAttachment(index)"
                  />
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Actions Footer -->
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
                @click="goBack"
                label="Cancel"
                icon="pi pi-times"
                class="p-button-secondary"
                size="large"
              />
              <Button 
                type="submit"
                :loading="creating"
                :disabled="!isFormValid"
                label="Create Receipt"
                icon="pi pi-check"
                size="large"
                class="tw-bg-gradient-to-r tw-from-emerald-600 tw-to-teal-700"
              />
            </div>
          </div>
        </template>
      </Card>
    </form>

    <!-- Create Product Dialog -->
    <Dialog 
      v-model:visible="createProductDialog" 
      modal 
      header="Create New Product" 
      :style="{ width: '700px' }"
      :dismissableMask="false"
      :closeOnEscape="false"
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
              :invalid="!newProductForm.name && newProductForm.touched"
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
              :invalid="!newProductForm.category && newProductForm.touched"
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import FileUpload from 'primevue/fileupload'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import Divider from 'primevue/divider'
import Checkbox from 'primevue/checkbox'
import Steps from 'primevue/steps'
import ProgressBar from 'primevue/progressbar'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

// State
const loading = ref(true)
const creating = ref(false)
const creatingBonCommend = ref(false)
const loadingPurchaseOrders = ref(false)
const creatingProduct = ref(false)
const error = ref(null)
const submitted = ref(false)
const activeStep = ref(0)

// Data
const availablePurchaseOrders = ref([])
const users = ref([])
const suppliers = ref([])
const availableProducts = ref([])
const selectedAttachments = ref([])

const createForm = ref({
  bon_commend_id: null,
  fournisseur_id: null,
  received_by: null,
  date_reception: new Date(),
  bon_livraison_numero: '',
  bon_livraison_date: null,
  facture_numero: '',
  facture_date: null,
  nombre_colis: null,
  observation: ''
})

// Items data
const orderedItems = ref([])
const unexpectedItems = ref([])
const manualItems = ref([])

// Product creation
const createProductDialog = ref(false)
const productTarget = ref(null)
const newProductForm = ref({
  name: '',
  code_interne: '',
  category: 'Medical Supplies',
  description: '',
  forme: 'pieces',
  is_clinical: false,
  touched: false
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

const unitOptions = [ 'ANAPATHE', 'ANTISEPTIQUE', 'CATHETERISME', 'CHIMIQUE', 'CHIRURGIE GÉNÉRALE',
        'CONSOMMABLE', 'FIBROSCOPIE', 'FROID', 'INSTRUMENT', 'LABORATOIRE',
        'LIGATURE', 'MÉDICAMENT', 'OSTÉO-SYNTHÈSE', 'PSYCHOTROPE 1', 'PSYCHOTROPE 2',
        'RADIOLOGIE', 'SOLUTÉ GRAND VOLUME', 'STÉRILISATION', 'STUPÉFIANT']

// Steps
const stepsItems = [
  { label: 'Basic Info', icon: 'pi pi-info-circle' },
  { label: 'Items', icon: 'pi pi-list' },
  { label: 'Attachments', icon: 'pi pi-paperclip' },
  { label: 'Review', icon: 'pi pi-check' }
]

// Breadcrumb
const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Goods Receipts', to: '/purchasing/bon-receptions' },
  { label: 'Create New', disabled: true }
])

// Computed
const selectedPurchaseOrder = computed(() => {
  if (!createForm.value.bon_commend_id) return null
  return availablePurchaseOrders.value.find(po => po.id === createForm.value.bon_commend_id)
})

const isBasicInfoComplete = computed(() => {
  return createForm.value.received_by && 
         createForm.value.date_reception && 
         (createForm.value.fournisseur_id || selectedPurchaseOrder.value)
})

const totalItemsCount = computed(() => {
  return manualItems.value.length + orderedItems.value.length + unexpectedItems.value.length
})

const isFormValid = computed(() => {
  const hasRequiredFields = createForm.value.received_by && createForm.value.date_reception
  const hasSupplier = selectedPurchaseOrder.value || createForm.value.fournisseur_id
  const hasItems = totalItemsCount.value > 0

  return hasRequiredFields && hasSupplier && hasItems
})

const formProgress = computed(() => {
  let progress = 0
  if (createForm.value.received_by) progress += 25
  if (createForm.value.date_reception) progress += 25
  if (createForm.value.fournisseur_id || selectedPurchaseOrder.value) progress += 25
  if (totalItemsCount.value > 0) progress += 25
  return progress
})

// Methods
const fetchAvailablePurchaseOrders = async () => {
  try {
    loadingPurchaseOrders.value = true
    const response = await axios.get('/api/bon-receptions/meta/bon-commends')

    if (response.data.status === 'success') {
      availablePurchaseOrders.value = response.data.data
    }
  } catch (err) {
    console.error('Error fetching purchase orders:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Could not retrieve purchase orders',
      life: 3000
    })
  } finally {
    loadingPurchaseOrders.value = false
  }
}

const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    users.value = response.data.data || response.data
  } catch (err) {
    console.error('Error fetching users:', err)
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    suppliers.value = response.data.data || response.data
  } catch (err) {
    console.error('Error fetching suppliers:', err)
  }
}

const fetchAvailableProducts = async () => {
  try {
    const response = await axios.get('/api/products')
    availableProducts.value = response.data.data?.data || response.data.data || response.data
  } catch (err) {
    console.error('Error fetching products:', err)
  }
}

const loadPurchaseOrderItems = async () => {
  if (!selectedPurchaseOrder.value) {
    orderedItems.value = []
    return
  }

  try {
    const response = await axios.get(`/api/bon-commends/${selectedPurchaseOrder.value.id}`)

    if (response.data.status === 'success') {
      const bonCommend = response.data.data

      orderedItems.value = bonCommend.items.map(item => ({
        ...item,
        quantity_received: item.quantity_desired || 0,
        unit_price: item.price || 0,
        surplus: 0,
        shortage: 0,
        notes: ''
      }))
    }
  } catch (err) {
    console.error('Error loading purchase order items:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Could not load purchase order items',
      life: 3000
    })
  }
}

const calculateSurplusShortage = (item) => {
  const ordered = item.quantity_desired || 0
  const received = item.quantity_received || 0

  if (received > ordered) {
    item.surplus = received - ordered
    item.shortage = 0
  } else if (received < ordered) {
    item.surplus = 0
    item.shortage = ordered - received
  } else {
    item.surplus = 0
    item.shortage = 0
  }
}

const addUnexpectedItem = () => {
  unexpectedItems.value.push({
    product_id: null,
    quantity_received: 1,
    notes: ''
  })
}

const removeUnexpectedItem = (index) => {
  unexpectedItems.value.splice(index, 1)
}

const addManualItem = () => {
  manualItems.value.push({
    product_id: null,
    quantity_received: 1,
    unit_price: 0,
    notes: ''
  })
}

const removeManualItem = (index) => {
  confirm.require({
    message: 'Are you sure you want to remove this item?',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      manualItems.value.splice(index, 1)
    }
  })
}

const onCellEditComplete = (event) => {
  let { data, newValue, field } = event
  data[field] = newValue
}

// Product creation methods
const openCreateProductDialog = (type, index) => {
  productTarget.value = { type, index }
  Object.assign(newProductForm.value, {
    name: '',
    code_interne: '',
    category: 'Medical Supplies',
    description: '',
    forme: 'pieces',
    is_clinical: false,
    touched: false
  })
  createProductDialog.value = true
}

const closeCreateProductDialog = () => {
  createProductDialog.value = false
  productTarget.value = null
}

const createProduct = async () => {
  try {
    creatingProduct.value = true
    newProductForm.value.touched = true

    if (!newProductForm.value.name || !newProductForm.value.category) {
      return
    }

    const response = await axios.post('/api/products', {
      name: newProductForm.value.name,
      code_interne: newProductForm.value.code_interne,
      category: newProductForm.value.category,
      description: newProductForm.value.description,
      forme: newProductForm.value.forme,
      is_clinical: newProductForm.value.is_clinical
    })

    const newProduct = response.data.data || response.data
    availableProducts.value.push(newProduct)

    if (productTarget.value) {
      const { type, index } = productTarget.value

      if (type === 'manual') {
        manualItems.value[index].product_id = newProduct.id
      } else if (type === 'unexpected') {
        unexpectedItems.value[index].product_id = newProduct.id
      }
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Product created and selected successfully',
      life: 3000
    })

    closeCreateProductDialog()
  } catch (err) {
    console.error('Error creating product:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Could not create product',
      life: 3000
    })
  } finally {
    creatingProduct.value = false
  }
}

const createReception = async () => {
  submitted.value = true

  if (!isFormValid.value) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please fill in all required fields',
      life: 3000
    })
    return
  }

  try {
    creating.value = true

    let items = []

    if (selectedPurchaseOrder.value) {
      items = [
        ...orderedItems.value.map(item => ({
          product_id: item.product_id,
          quantity_received: item.quantity_received,
          unit_price: item.price || 0,
          notes: item.notes
        })),
        ...unexpectedItems.value.map(item => ({
          product_id: item.product_id,
          quantity_received: item.quantity_received,
          unit_price: 0,
          notes: item.notes
        }))
      ]
    } else {
      items = manualItems.value.map(item => ({
        product_id: item.product_id,
        quantity_received: item.quantity_received,
        unit_price: item.unit_price || 0,
        notes: item.notes
      }))
    }

    const formData = new FormData()

    // Defensive: if a purchase order is selected but fournisseur_id is not set
    // (e.g. shape mismatch or timing), try to populate it from the PO before sending.
    if (selectedPurchaseOrder.value && !createForm.value.fournisseur_id) {
      const po = selectedPurchaseOrder.value
      if (typeof po.fournisseur_id !== 'undefined' && po.fournisseur_id !== null) {
        createForm.value.fournisseur_id = Number(po.fournisseur_id)
      } else if (po.fournisseur && typeof po.fournisseur.id !== 'undefined' && po.fournisseur.id !== null) {
        createForm.value.fournisseur_id = Number(po.fournisseur.id)
      }
    }

    // Append createForm values. Be defensive when handling dates (Calendar may return Date or string)
    Object.keys(createForm.value).forEach(key => {
      const value = createForm.value[key]
      if (value === null || typeof value === 'undefined') return

      if (key === 'date_reception' || key === 'bon_livraison_date' || key === 'facture_date') {
        try {
          // Normalize to a Date instance then format to YYYY-MM-DD
          const dateObj = value instanceof Date ? value : new Date(value)
          if (!isNaN(dateObj.getTime())) {
            formData.append(key, dateObj.toISOString().split('T')[0])
          }
        } catch (e) {
          // if parsing fails, skip the date field but log for debugging
          console.warn(`Skipping invalid date for ${key}:`, value)
        }
      } else {
        formData.append(key, value)
      }
    })

    items.forEach((item, index) => {
      Object.keys(item).forEach(key => {
        formData.append(`items[${index}][${key}]`, item[key])
      })
    })

    selectedAttachments.value.forEach((file, i) => {
      formData.append(`attachments[${i}][file]`, file)
      formData.append(`attachments[${i}][name]`, file.name)
    })

    // Let the browser set the Content-Type (including multipart boundary).
    // Setting Content-Type manually can break the multipart boundary and cause
    // Laravel to not receive uploaded files (validation.uploaded).
    const response = await axios.post('/api/bon-receptions', formData)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Receipt created successfully',
        life: 3000
      })

      router.push('/purchasing/bon-receptions')
    } else {
      throw new Error(response.data.message || 'Failed to create reception')
    }
  } catch (err) {
    console.error('Error creating reception:', err)

    // If validation errors from Laravel (422), show detailed messages
    if (err.response && err.response.status === 422 && err.response.data && err.response.data.errors) {
      const errors = err.response.data.errors
      // Flatten to a single message
      const messages = Object.keys(errors).map(k => `${k}: ${errors[k].join(', ')}`)
      const message = messages.join(' — ')

      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: message || err.response.data.message || 'Validation failed',
        life: 8000
      })

      // also set the page-level error so the Message component can show it if present
      error.value = err.response.data.message || 'Validation failed. See details.'
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: err.response?.data?.message || err.message || 'Could not create reception',
        life: 3000
      })
    }
  } finally {
    creating.value = false
  }
}

const createBonCommendFromReception = async () => {
  if (manualItems.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Items',
      detail: 'Please add at least one item before creating a purchase order',
      life: 3000
    })
    return
  }

  if (!createForm.value.fournisseur_id) {
    toast.add({
      severity: 'warn',
      summary: 'Supplier Required',
      detail: 'Please select a supplier before creating a purchase order',
      life: 3000
    })
    return
  }

  confirm.require({
    message: 'Create a purchase order from these items?',
    header: 'Create Purchase Order',
    icon: 'pi pi-question-circle',
    accept: async () => {
      try {
        creatingBonCommend.value = true

        const bonCommendData = {
          fournisseur_id: createForm.value.fournisseur_id,
          order_date: new Date().toISOString().split('T')[0],
          status: 'confirmed',
          approval_status: 'approved',
          notes: `Created from reception. ${createForm.value.observation || ''}`,
          items: manualItems.value.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity_received,
            price: item.unit_price || 0,
            unit: 'piece',
            notes: item.notes || ''
          }))
        }

        const response = await axios.post('/api/bon-commends', bonCommendData)

        if (response.data.status === 'success') {
          const newBonCommend = response.data.data

          toast.add({
            severity: 'success',
            summary: 'Purchase Order Created',
            detail: `Purchase order ${newBonCommend.bonCommendCode} created successfully`,
            life: 3000
          })

          createForm.value.bon_commend_id = newBonCommend.id
          await fetchAvailablePurchaseOrders()
        } else {
          throw new Error(response.data.message || 'Failed to create purchase order')
        }
      } catch (err) {
        console.error('Error creating purchase order:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || err.message || 'Could not create purchase order',
          life: 3000
        })
      } finally {
        creatingBonCommend.value = false
      }
    }
  })
}

const goBack = () => {
  router.push('/purchasing/bon-receptions')
}

const viewProductHistory = (productId) => {
  router.push({
    name: 'purchasing.product.history',
    params: { productId }
  })
}

// File Management
const handleFileSelect = (event) => { 
  event.files.forEach(f => selectedAttachments.value.push(f))
}

const removeAttachment = (index) => {
  selectedAttachments.value.splice(index, 1)
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

// Utility functions
const getPurchaseOrderStatusSeverity = (status) => {
  const severities = {
    draft: 'secondary',
    sent: 'info',
    confirmed: 'success',
    rejected: 'danger'
  }
  return severities[status] || 'info'
}

const formatDate = (date) => {
  if (!date) return 'N/A'

  try {
    return new Date(date).toLocaleDateString('en-US', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch (err) {
    return 'Invalid Date'
  }
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return 'N/A'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

// Watchers
watch(() => createForm.value.bon_commend_id, async (newId) => {
  if (newId) {
    // selectedPurchaseOrder may provide fournisseur_id directly or a nested fournisseur object
    const po = selectedPurchaseOrder.value
    let fournisseurId = null

    if (po) {
      if (typeof po.fournisseur_id !== 'undefined' && po.fournisseur_id !== null) {
        fournisseurId = Number(po.fournisseur_id)
      } else if (po.fournisseur && typeof po.fournisseur.id !== 'undefined' && po.fournisseur.id !== null) {
        fournisseurId = Number(po.fournisseur.id)
      }
    }

    createForm.value.fournisseur_id = fournisseurId

    await loadPurchaseOrderItems()
    // Clear manual items when a PO is selected
    manualItems.value = []
    activeStep.value = 1
  } else {
    createForm.value.fournisseur_id = null
    orderedItems.value = []
    unexpectedItems.value = []
  }
})

watch(() => createForm.value.received_by, () => {
  if (createForm.value.received_by && createForm.value.date_reception) {
    activeStep.value = 1
  }
})

watch(() => totalItemsCount.value, () => {
  if (totalItemsCount.value > 0) {
    activeStep.value = 2
  }
})

// Lifecycle
onMounted(async () => {
  try {
    loading.value = true
    await Promise.all([
      fetchAvailablePurchaseOrders(),
      fetchUsers(),
      fetchSuppliers(),
      fetchAvailableProducts()
    ])

    if (route.query.bon_commend_id) {
      createForm.value.bon_commend_id = parseInt(route.query.bon_commend_id)
    }
  } catch (err) {
    error.value = 'Failed to load form data'
  } finally {
    loading.value = false
  }
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
  @apply hover:tw-bg-emerald-50 tw-transition-colors;
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
  @apply bg-gradient-to-r tw-from-emerald-600 tw-to-teal-700 tw-text-white;
}

:deep(.p-dialog-header .p-dialog-title) {
  @apply text-white tw-font-bold;
}

/* FileUpload */
:deep(.p-fileupload) {
  @apply border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg hover:tw-border-emerald-400 hover:tw-bg-emerald-50 tw-transition-all;
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

/* Hover effects */
:deep(.p-button:not(:disabled):hover) {
  @apply transform tw-scale-105 tw-transition-transform;
}

:deep(.p-card:hover) {
  @apply shadow-2xl tw-transition-shadow;
}
</style>