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
          <!-- Gradient Header -->
          <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-700 tw-p-6 tw--m-6 tw-mb-6">
            <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-4">
              <div>
                <h1 class="tw-text-3xl tw-font-bold tw-text-white tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-file-edit tw-text-2xl"></i>
                  Edit Purchase 
                </h1>
                <div class="tw-flex tw-items-center tw-gap-4 tw-mt-3">
                  <span class="tw-text-indigo-100 tw-text-sm">Code:</span>
                  <span class="tw-bg-white/20 tw-px-3 tw-py-1 tw-rounded-full tw-text-white tw-font-semibold">
                    {{ bonCommend?.bonCommendCode || `BC-${bonCommend?.id}` || 'Loading...' }}
                  </span>
                  <Tag 
                    v-if="bonCommend" 
                    :value="getStatusLabel(bonCommend.status)" 
                    :severity="getStatusSeverity(bonCommend.status)"
                    class="tw-font-semibold"
                  />
                </div>
              </div>
              <div class="tw-flex tw-gap-2 tw-flex-wrap">
                <Button 
                  @click="goBack"
                  icon="pi pi-arrow-left"
                  label="Back"
                  class="p-button-secondary tw-shadow-lg"
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

          <!-- Quick Stats Bar -->
          <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-4 tw-px-6 tw-pb-6">
            <div class="tw-bg-orange-50 tw-rounded-lg tw-p-3 tw-border tw-border-orange-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-xs tw-text-orange-600 tw-font-medium">Supplier</p>
                  <p class="tw-text-lg tw-font-bold tw-text-orange-800 tw-truncate">
                    {{ bonCommend?.fournisseur?.company_name || 'N/A' }}
                  </p>
                </div>
                <i class="pi pi-users tw-text-orange-500 tw-text-2xl"></i>
              </div>
            </div>

            <div class="tw-bg-blue-50 tw-rounded-lg tw-p-3 tw-border tw-border-blue-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-xs tw-text-blue-600 tw-font-medium">Total Items</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-blue-800">{{ bonCommend?.items?.length || 0 }}</p>
                </div>
                <i class="pi pi-box tw-text-blue-500 tw-text-2xl"></i>
              </div>
            </div>

            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3 tw-border tw-border-green-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-xs tw-text-green-600 tw-font-medium">Ready Items</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-green-800">{{ readyItemsCount }}</p>
                </div>
                <i class="pi pi-check-circle tw-text-green-500 tw-text-2xl"></i>
              </div>
            </div>

            <div class="tw-bg-purple-50 tw-rounded-lg tw-p-3 tw-border tw-border-purple-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-xs tw-text-purple-600 tw-font-medium">Total Value</p>
                  <p class="tw-text-lg tw-font-bold tw-text-purple-800">{{ formatCurrency(totalValue) }}</p>
                </div>
                <i class="pi pi-dollar tw-text-purple-500 tw-text-2xl"></i>
              </div>
            </div>

            <div class="tw-bg-teal-50 tw-rounded-lg tw-p-3 tw-border tw-border-teal-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-xs tw-text-teal-600 tw-font-medium">Attachments</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-teal-800">{{ existingAttachments.length }}</p>
                </div>
                <i class="pi pi-paperclip tw-text-teal-500 tw-text-2xl"></i>
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
            <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">Loading bon commend data...</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <div v-else-if="bonCommend" class="tw-space-y-6">

      <!-- Alert for Sent Back Status -->
      <Card v-if="bonCommend.status === 'needs_correction'" class="tw-border-2 tw-border-orange-400 tw-shadow-xl">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-orange-50 tw-to-orange-100 tw-p-6 tw--m-6">
            <div class="tw-flex tw-items-start tw-gap-4">
              <div class="tw-bg-orange-500 tw-rounded-full tw-p-3">
                <i class="pi pi-exclamation-circle tw-text-3xl tw-text-white"></i>
              </div>
              <div class="tw-flex-1">
                <h3 class="tw-text-xl tw-font-bold tw-text-orange-900 tw-mb-2">Sent Back for Correction</h3>
                <p class="tw-text-orange-800 tw-mb-4">
                  The approver has sent this bon commend back with modifications. Please review the changes and resubmit.
                </p>

                <!-- Approver Notes -->
                <div v-if="sentBackApproval?.approval_notes" class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-sm tw-mb-4">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                    <i class="pi pi-comment tw-text-orange-600"></i>
                    <span class="tw-font-semibold tw-text-gray-900">Approver's Notes:</span>
                  </div>
                  <p class="tw-text-gray-800">{{ sentBackApproval.approval_notes }}</p>
                  <div class="tw-mt-2 tw-text-sm tw-text-gray-600">
                    <span class="tw-font-medium">From:</span> {{ sentBackApproval.approval_person?.name || 'Approver' }}
                    <span class="tw-mx-2">•</span>
                    <span class="tw-font-medium">Date:</span> {{ formatDate(sentBackApproval.rejected_at) }}
                  </div>
                </div>

                <!-- Modified Items Summary -->
                <div v-if="modifiedItems.length > 0" class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-sm">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
                    <i class="pi pi-pencil tw-text-orange-600"></i>
                    <span class="tw-font-semibold tw-text-gray-900">Quantity Changes ({{ modifiedItems.length }} items):</span>
                  </div>
                  <div class="tw-space-y-2 tw-max-h-48 tw-overflow-y-auto">
                    <div v-for="item in modifiedItems" :key="item.id" 
                         class="tw-flex tw-items-center tw-gap-3 tw-p-2 tw-bg-gray-50 tw-rounded hover:tw-bg-gray-100 tw-transition-colors">
                      <i class="pi pi-arrow-right tw-text-orange-500"></i>
                      <div class="tw-flex-1">
                        <div class="tw-font-medium tw-text-gray-900">{{ item.product?.name || item.product_name }}</div>
                        <div class="tw-text-sm tw-text-gray-600">
                          Original: <span class="tw-line-through tw-text-red-600">{{ item.original_quantity_desired }}</span>
                          <i class="pi pi-arrow-right tw-mx-2 tw-text-xs"></i>
                          New: <span class="tw-font-semibold tw-text-green-600">{{ item.quantity_desired }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="tw-mt-4 tw-flex tw-gap-3">
                  <Button 
                    @click="resubmitAfterCorrection"
                    :loading="submittingApproval"
                    icon="pi pi-send"
                    label="Accept Changes & Resubmit"
                    class="p-button-warning"
                    size="large"
                  />
                  <Button 
                    @click="viewApprovalHistory"
                    icon="pi pi-history"
                    label="View History"
                    class="p-button-secondary p-button-outlined"
                    size="large"
                  />
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Approval Status Card -->
      <Card v-if="showApprovalButtons" class="tw-border-0 tw-shadow-xl tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2 tw-text-indigo-800">
            <i class="pi pi-shield"></i>
            <span>Approval Workflow</span>
            <Tag 
              v-if="bonCommend.approval_status"
              :value="bonCommend.approval_status.replace('_', ' ').toUpperCase()" 
              :severity="getApprovalSeverity(bonCommend.approval_status)"
              class="tw-ml-auto"
            />
          </div>
        </template>
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-4">
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Amount</div>
              <div class="tw-text-2xl tw-font-bold tw-text-indigo-900">{{ formatCurrency(totalValue) }}</div>
            </div>
            <div v-if="approvalInfo.approver" class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Assigned Approver</div>
              <div class="tw-text-lg tw-font-semibold tw-text-indigo-900">{{ approvalInfo.approver.name }}</div>
              <div class="tw-text-xs tw-text-gray-500">Max: {{ formatCurrency(approvalInfo.approver.max_amount) }}</div>
            </div>
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Approval Status</div>
              <div class="tw-text-lg tw-font-semibold tw-text-indigo-900">
                {{ approvalInfo.needsApproval ? 'Required' : 'Not Required' }}
              </div>
            </div>
          </div>

          <!-- Approval Actions -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button 
              v-if="canSubmitForApproval && approvalInfo.needsApproval"
              @click="submitForApproval"
              :loading="submittingApproval"
              icon="pi pi-send"
              label="Submit for Approval"
              class="p-button-warning"
              size="large"
            />

            <Button 
              v-if="bonCommend.can_be_confirmed_now"
              @click="finalConfirmation"
              :loading="confirming"
              icon="pi pi-check-circle"
              label="Confirm Order"
              class="p-button-success"
              size="large"
            />

            <Button 
              @click="checkApprovalNeeded"
              :loading="checkingApproval"
              icon="pi pi-refresh"
              label="Refresh Status"
              class="p-button-secondary p-button-outlined"
              size="large"
            />
          </div>
        </template>
      </Card>

      <!-- Items DataTable for Better Performance -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-list tw-text-indigo-600"></i>
              <span>Purchase  Items</span>
              <Tag :value="`${bonCommend.items?.length || 0} items`" severity="info" />
            </div>
            <div class="tw-flex tw-gap-2">
              <!-- Search for items -->
              <div v-if="bonCommend.items?.length > 5" class="tw-relative">
                <InputText 
                  v-model="itemSearchQuery"
                  placeholder="Search items..."
                  class="tw-pr-8"
                />
                <i class="pi pi-search tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
              </div>
              <Button 
                @click="saveChanges"
                :loading="saving"
                icon="pi pi-save"
                label="Save All Changes"
                class="p-button-primary"
              />
            </div>
          </div>
        </template>
        <template #content>
          <!-- Empty State -->
          <div v-if="!bonCommend.items || bonCommend.items.length === 0" class="tw-text-center tw-py-12">
            <i class="pi pi-inbox tw-text-6xl tw-text-gray-300"></i>
            <p class="tw-mt-4 tw-text-gray-500 tw-text-lg">No items in this bon commend</p>
          </div>

          <!-- DataTable for Items -->
          <DataTable 
            v-else
            :value="filteredItems"
            :paginator="bonCommend.items.length > 10"
            :rows="10"
            :rowsPerPageOptions="[10, 25, 50]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
            responsiveLayout="scroll"
            class="tw-mt-4"
            :rowClass="getRowClass"
            :expandedRows="expandedRows"
            @row-expand="onRowExpand"
            @row-collapse="onRowCollapse"
          >
            <!-- Expander Column -->
            <Column :expander="true" style="width: 3rem" />

            <!-- Product Column -->
            <Column field="product.name" header="Product" :sortable="true" style="min-width: 250px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar 
                    :label="slotProps.data.product?.name?.charAt(0)" 
                    class="tw-bg-indigo-100 tw-text-indigo-700"
                    shape="square"
                  />
                  <div>
                    <div class="tw-font-semibold">
                      <span 
                        v-if="slotProps.data.product?.id"
                        @click="viewProductHistory(slotProps.data.product.id)"
                        class="tw-cursor-pointer tw-text-indigo-600 hover:tw-text-indigo-800 hover:tw-underline"
                      >
                        {{ slotProps.data.product?.name || 'Unknown Product' }}
                      </span>
                      <span v-else>{{ slotProps.data.product?.name || 'Unknown Product' }}</span>
                    </div>
                    <div class="tw-text-xs tw-text-gray-500">
                      Code: {{ slotProps.data.product?.code_interne || 'N/A' }}
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Quantity Desired Column -->
            <Column field="quantity_desired" header="Qty Desired" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.quantity_desired"
                  :min="1"
                  showButtons
                  buttonLayout="vertical"
                  incrementButtonClass="p-button-sm"
                  decrementButtonClass="p-button-sm"
                  class="tw-w-full"
                  :disabled="bonCommend.is_confirmed"
                />
              </template>
            </Column>

            <!-- Quantity Sent Column -->
            <Column field="quantity" header="Qty Sent" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.quantity"
                  :min="0"
                  showButtons
                  buttonLayout="vertical"
                  incrementButtonClass="p-button-sm"
                  decrementButtonClass="p-button-sm"
                  class="tw-w-full"
                  :disabled="bonCommend.is_confirmed"
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
                  :disabled="bonCommend.is_confirmed"
                />
              </template>
            </Column>

            <!-- Unit Price Column -->
            <Column field="price" header="Unit Price" :sortable="true" style="width: 150px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.price"
                  :min="0"
                  mode="currency"
                  currency="DZD"
                  locale="fr-FR"
                  class="tw-w-full"
                  :disabled="bonCommend.is_confirmed"
                />
              </template>
            </Column>

            <!-- Total Column -->
            <Column header="Total" :sortable="true" style="width: 150px">
              <template #body="slotProps">
                <span class="tw-font-bold tw-text-green-600">
                  {{ formatCurrency((slotProps.data.price || 0) * (slotProps.data.quantity_desired || 0)) }}
                </span>
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
                    {{ slotProps.data.quantity || 0 }}/{{ slotProps.data.quantity_desired }} 
                    ({{ getProgressPercentage(slotProps.data) }}%)
                  </span>
                </div>
              </template>
            </Column>

            <!-- Status Column -->
            <Column field="confirmed_at" header="Status" style="width: 120px">
              <template #body="slotProps">
                <Tag 
                  v-if="slotProps.data.confirmed_at"
                  value="Confirmed" 
                  severity="success"
                  icon="pi pi-check"
                />
                <Tag 
                  v-else
                  value="Pending" 
                  severity="warning"
                  icon="pi pi-clock"
                />
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" style="width: 100px" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Button 
                    @click="openCreateProductDialog(slotProps.index)"
                    icon="pi pi-plus"
                    class="p-button-text p-button-sm p-button-info"
                    v-tooltip="'Create Product'"
                    :disabled="bonCommend.is_confirmed"
                  />
                </div>
              </template>
            </Column>

            <!-- Row Expansion Template -->
            <template #expansion="slotProps">
              <div class="tw-p-4 tw-bg-gray-50">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                  <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                      Source Type
                    </label>
                    <Tag 
                      :value="slotProps.data.source_type || 'service_demand'"
                      :severity="slotProps.data.source_type === 'facture_proforma' ? 'info' : 'secondary'"
                    />
                    <span v-if="slotProps.data.source_id" class="tw-ml-2 tw-text-xs tw-text-gray-500">
                      ID: {{ slotProps.data.source_id }}
                    </span>
                  </div>
                  <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                      Confirmation Date
                    </label>
                    <span>{{ formatDate(slotProps.data.confirmed_at) || 'Not confirmed' }}</span>
                  </div>
                </div>
                <div class="tw-mt-4">
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                    Item Notes
                  </label>
                  <Textarea
                    v-model="slotProps.data.notes"
                    placeholder="Add any notes for this item..."
                    rows="3"
                    class="tw-w-full"
                    :disabled="bonCommend.is_confirmed"
                  />
                </div>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>

      <!-- Summary & Confirmation Card -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-calculator tw-text-green-600"></i>
            <span>Summary & Confirmation</span>
          </div>
        </template>
        <template #content>
          <!-- Summary Stats -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4 tw-mb-6">
            <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200">
              <div class="tw-text-sm tw-text-blue-600">Total Items</div>
              <div class="tw-text-2xl tw-font-bold tw-text-blue-900">{{ bonCommend.items?.length || 0 }}</div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-rounded-lg tw-p-4 tw-border tw-border-green-200">
              <div class="tw-text-sm tw-text-green-600">Items Ready</div>
              <div class="tw-text-2xl tw-font-bold tw-text-green-900">{{ readyItemsCount }}</div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-rounded-lg tw-p-4 tw-border tw-border-purple-200">
              <div class="tw-text-sm tw-text-purple-600">Total Quantity</div>
              <div class="tw-text-2xl tw-font-bold tw-text-purple-900">
                {{ bonCommend.items?.reduce((sum, item) => sum + (item.quantity_desired || 0), 0) || 0 }}
              </div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-rounded-lg tw-p-4 tw-text-white">
              <div class="tw-text-sm tw-text-indigo-100">Total Value</div>
              <div class="tw-text-2xl tw-font-bold">{{ formatCurrency(totalValue) }}</div>
            </div>
          </div>

          <!-- Confirmation Section -->
          <div class="tw-bg-gray-50 tw-rounded-lg tw-p-6">
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Purchase  Confirmation</h3>
                <p class="tw-text-gray-600 tw-mt-1">
                  Confirm this entire bon commend once all items are processed
                </p>
              </div>
              <div class="tw-flex tw-gap-3">
                <Button 
                  v-if="!bonCommend.is_confirmed && bonCommend.can_be_confirmed_now"
                  @click="confirmEntireBonCommend"
                  :disabled="!canConfirmBonCommend || confirming"
                  :loading="confirming"
                  icon="pi pi-check"
                  label="Confirm Entire Purchase "
                  class="p-button-success"
                  size="large"
                />
                <div v-else-if="bonCommend.is_confirmed" class="tw-flex tw-items-center tw-gap-2">
                  <Tag value="Confirmed" severity="success" icon="pi pi-check-circle" />
                  <span class="tw-text-sm tw-text-gray-600">
                    {{ formatDate(bonCommend.confirmed_at) }}
                  </span>
                </div>
                <div v-else-if="!bonCommend.can_be_confirmed_now" class="tw-flex tw-items-center tw-gap-2">
                  <Tag value="Awaiting Approval" severity="warning" icon="pi pi-clock" />
                </div>
              </div>
            </div>

            <div v-if="!bonCommend.can_be_confirmed_now && !bonCommend.is_confirmed" class="tw-mt-4">
              <Message severity="warn" :closable="false">
                <span v-if="approvalInfo.needsApproval">
                  This bon commend requires approval before it can be confirmed. Please submit for approval.
                </span>
                <span v-else>
                  Please set quantity sent for all items before confirming the bon commend.
                </span>
              </Message>
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
          <FileUpload
            mode="advanced"
            :multiple="true"
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
            :maxFileSize="10485760"
            :disabled="bonCommend.is_confirmed || saving"
            @select="handleFileSelect"
            :auto="false"
            chooseLabel="Add Files"
            :showUploadButton="false"
            :showCancelButton="false"
            class="tw-mb-4"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8">
                <i class="pi pi-cloud-upload tw-text-4xl tw-text-gray-400 tw-mb-2"></i>
                <p class="tw-text-gray-600">Drag and drop files here or click to browse.</p>
                <p class="tw-text-sm tw-text-gray-500 tw-mt-2">PDF, DOC, XLS, Images (Max 10MB)</p>
              </div>
            </template>
          </FileUpload>

          <!-- Files Grid -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
            <!-- Existing Attachments -->
            <div v-for="(attachment, index) in existingAttachments" 
                 :key="`existing-${index}`"
                 class="tw-bg-blue-50 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200 hover:tw-shadow-lg tw-transition-shadow">
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <i :class="getFileIcon(attachment.name)" class="tw-text-2xl tw-text-blue-600"></i>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">
                      {{ attachment.name || attachment.original_filename }}
                    </div>
                    <div v-if="attachment.description" class="tw-text-xs tw-text-gray-500 tw-mt-1">
                      {{ attachment.description }}
                    </div>
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
                    v-if="!bonCommend.is_confirmed"
                    icon="pi pi-times"
                    class="p-button-text p-button-danger p-button-sm"
                    @click="removeExistingAttachment(index)"
                    v-tooltip.top="'Remove'"
                  />
                </div>
              </div>
            </div>

            <!-- New Attachments -->
            <div v-for="(attachment, index) in newAttachments" 
                 :key="`new-${index}`"
                 class="tw-bg-green-50 tw-rounded-lg tw-p-4 tw-border tw-border-green-200 hover:tw-shadow-lg tw-transition-shadow">
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <i class="pi pi-file-plus tw-text-2xl tw-text-green-600"></i>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ attachment.name }}</div>
                    <div class="tw-text-xs tw-text-green-600 tw-mt-1">Ready to upload</div>
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
              placeholder="Enter internal code"
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
              placeholder="e.g. tablet, ml, piece"
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
            placeholder="Enter product description (optional)"
            rows="3"
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
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
        </div>
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
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import ProgressBar from 'primevue/progressbar'
import Message from 'primevue/message'
import FileUpload from 'primevue/fileupload'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import Checkbox from 'primevue/checkbox'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const id = route.params.id

// Reactive data
const loading = ref(true)
const saving = ref(false)
const confirming = ref(false)
const generatingPDF = ref(false)
const submittingApproval = ref(false)
const checkingApproval = ref(false)
const error = ref(null)
const bonCommend = ref(null)
const existingAttachments = ref([])
const newAttachments = ref([])
const expandedRows = ref([])
const itemSearchQuery = ref('')

const approvalInfo = ref({
  needsApproval: false,
  approver: null,
  totalAmount: 0,
  reason: null
})

// Create product dialog state
const createProductDialog = ref(false)
const creatingProduct = ref(false)
const productTargetIndex = ref(null)
const newProductForm = ref({
  name: '',
  code_interne: '',
  category: '',
  forme: '',
  is_clinical: false,
  description: ''
})

// Options
const categoryOptions = ref([
  { label: 'Medical Supplies', value: 'Medical Supplies' },
  { label: 'Equipment', value: 'Equipment' },
  { label: 'Medication', value: 'Medication' },
  { label: 'Consumables', value: 'Consumables' },
  { label: 'Laboratory', value: 'Laboratory' },
  { label: 'Others', value: 'Others' }
])

// Breadcrumb items
const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Purchase s', to: '/bon-commends' },
  { label: `Edit BC-${id}`, disabled: true }
])

// Computed properties
const filteredItems = computed(() => {
  if (!itemSearchQuery.value || !bonCommend.value?.items) return bonCommend.value?.items || []

  const query = itemSearchQuery.value.toLowerCase()
  return bonCommend.value.items.filter(item => {
    const productName = item.product?.name?.toLowerCase() || ''
    const productCode = item.product?.code_interne?.toLowerCase() || ''
    return productName.includes(query) || productCode.includes(query)
  })
})

const canConfirmBonCommend = computed(() => {
  if (!bonCommend.value?.items) return false

  return bonCommend.value.items.every(item => {
    return item.quantity > 0 && item.quantity <= item.quantity_desired
  })
})

const readyItemsCount = computed(() => {
  if (!bonCommend.value?.items) return 0

  return bonCommend.value.items.filter(item => 
    item.quantity > 0 && item.quantity <= item.quantity_desired
  ).length
})

const totalValue = computed(() => {
  if (!bonCommend.value?.items) return 0

  return bonCommend.value.items.reduce((total, item) => {
    return total + ((item.quantity_desired || 0) * (item.price || 0))
  }, 0)
})

const canSubmitForApproval = computed(() => {
  return bonCommend.value && 
         bonCommend.value.status === 'draft' &&
         bonCommend.value.approval_status !== 'pending_approval' &&
         !bonCommend.value.isApproved
})

const showApprovalButtons = computed(() => {
  return bonCommend.value && bonCommend.value.status === 'draft'
})

const modifiedItems = computed(() => {
  if (!bonCommend.value?.items) return []
  return bonCommend.value.items.filter(item => item.modified_by_approver)
})

const sentBackApproval = computed(() => {
  if (!bonCommend.value?.approvals) return null

  return bonCommend.value.approvals
    .filter(approval => approval.status === 'sent_back')
    .sort((a, b) => new Date(b.rejected_at) - new Date(a.rejected_at))[0]
})

// Methods
const fetchBonCommend = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await axios.get(`/api/bon-commends/${route.params.id}`)

    if (response.data.status === 'success') {
      bonCommend.value = response.data.data

      // Initialize quantity if not set
      if (bonCommend.value.items) {
        bonCommend.value.items.forEach(item => {
          if (item.quantity === undefined || item.quantity === null) {
            item.quantity = 0
          }
        })
      }

      // Ensure item.price is populated. Some backend responses use `unit_price`.
      if (bonCommend.value.items) {
        bonCommend.value.items.forEach(item => {
          if (item.price === undefined || item.price === null) {
            if (item.unit_price !== undefined && item.unit_price !== null) {
              item.price = item.unit_price
            } else {
              item.price = 0
            }
          }
        })
      }

      // Load existing attachments
      if (bonCommend.value.attachments) {
        if (Array.isArray(bonCommend.value.attachments)) {
          existingAttachments.value = bonCommend.value.attachments
        }
      }
    } else {
      error.value = 'Failed to load bon commend'
    }
  } catch (err) {
    console.error('Error fetching bon commend:', err)
    error.value = 'Failed to load bon commend data'
  } finally {
    loading.value = false
  }
}

// File handling methods
const handleFileSelect = (event) => {
  const files = event.files

  files.forEach(file => {
    newAttachments.value.push({
      file: file,
      name: file.name,
      description: ''
    })
  })

  toast.add({
    severity: 'success',
    summary: 'Files Selected',
    detail: `${files.length} file(s) ready to upload`,
    life: 3000
  })
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
    png: 'pi pi-image',
    gif: 'pi pi-image'
  }
  return iconMap[ext] || 'pi pi-file'
}

const removeNewAttachment = (index) => {
  newAttachments.value.splice(index, 1)
}

const removeExistingAttachment = (index) => {
  existingAttachments.value.splice(index, 1)
}

const downloadAttachment = async (attachment, index) => {
  try {
    const attachmentIndex = index !== undefined ? index : existingAttachments.value.findIndex(a => a === attachment)

    const response = await axios.get(
      `/api/bon-commends/${bonCommend.value.id}/attachments/${attachmentIndex}/download`, 
      { responseType: 'blob' }
    )

    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', attachment.name || attachment.original_filename || `attachment-${attachmentIndex}`)
    document.body.appendChild(link)
    link.click()

    link.remove()
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'File downloaded successfully',
      life: 3000
    })
  } catch (err) {
    console.error('Error downloading attachment:', err)
    toast.add({
      severity: 'error',
      summary: 'Download Error',
      detail: 'Failed to download attachment',
      life: 5000
    })
  }
}

const saveChanges = async () => {
  try {
    saving.value = true

    const formDataPayload = new FormData()

    // Add basic fields
    formDataPayload.append('fournisseur_id', bonCommend.value.fournisseur_id || '')
    formDataPayload.append('service_demand_purchasing_id', bonCommend.value.service_demand_purchasing_id || '')
    formDataPayload.append('notes', bonCommend.value.notes || '')

    // Add items (include both price and unit_price to be compatible with backend)
    bonCommend.value.items.forEach((item, index) => {
      formDataPayload.append(`items[${index}][product_id]`, item.product_id || item.product?.id)
      formDataPayload.append(`items[${index}][quantity_desired]`, item.quantity_desired)
      formDataPayload.append(`items[${index}][quantity]`, item.quantity || 0)
      // provide both field names
      formDataPayload.append(`items[${index}][price]`, item.price ?? item.unit_price ?? 0)
      formDataPayload.append(`items[${index}][unit_price]`, item.unit_price ?? item.price ?? 0)
      formDataPayload.append(`items[${index}][unit]`, item.unit || '')
      formDataPayload.append(`items[${index}][notes]`, item.notes || '')
      formDataPayload.append(`items[${index}][source_type]`, item.source_type || 'service_demand')
      formDataPayload.append(`items[${index}][source_id]`, item.source_id || '')
    })

    // Add existing attachments
    existingAttachments.value.forEach((attachment, index) => {
      formDataPayload.append(`existing_attachments[${index}][path]`, attachment.path)
      formDataPayload.append(`existing_attachments[${index}][name]`, attachment.name)
      formDataPayload.append(`existing_attachments[${index}][description]`, attachment.description || '')
    })

    // Add new attachments
    if (newAttachments.value.length > 0) {
      newAttachments.value.forEach((attachment, index) => {
        if (attachment.file instanceof File) {
          formDataPayload.append(`attachments[${index}][file]`, attachment.file)
          formDataPayload.append(`attachments[${index}][name]`, attachment.name || attachment.file.name)
          formDataPayload.append(`attachments[${index}][description]`, attachment.description || '')
        }
      })
    }

    const response = await axios.post(`/api/bon-commends/${bonCommend.value.id}?_method=PUT`, formDataPayload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Changes saved successfully',
        life: 3000
      })

      newAttachments.value = []
      await fetchBonCommend()
    }
  } catch (err) {
    console.error('Error saving changes:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save changes',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const confirmEntireBonCommend = async () => {
  try {
    if (!canConfirmBonCommend.value) {
      toast.add({
        severity: 'warn',
        summary: 'Cannot Confirm',
        detail: 'Please set quantity sent for all items first',
        life: 3000
      })
      return
    }

    confirming.value = true

    await saveChanges()

    const response = await axios.put(`/api/bon-commends/${id}/status`, { status: 'confirmed' })

    if (response.data.status === 'success') {
      bonCommend.value.is_confirmed = true
      bonCommend.value.status = 'confirmed'

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Bon commend confirmed successfully',
        life: 3000
      })

      await fetchBonCommend()
    }
  } catch (err) {
    console.error('Error confirming bon commend:', err)

    if (err.response?.status === 422 && err.response?.data?.requires_approval) {
      toast.add({
        severity: 'warn',
        summary: 'Approval Required',
        detail: err.response.data.message || 'This bon commend requires approval before confirmation',
        life: 5000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: err.response?.data?.message || 'Failed to confirm bon commend',
        life: 3000
      })
    }
  } finally {
    confirming.value = false
  }
}

const generatePDF = async () => {
  try {
    generatingPDF.value = true

    const response = await axios.get(`/api/bon-commends/${bonCommend.value.id}/download`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bon-commend-${bonCommend.value.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF generated successfully',
      life: 3000
    })
  } catch (err) {
    console.error('Error generating PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF',
      life: 3000
    })
  } finally {
    generatingPDF.value = false
  }
}

const goBack = () => {
  router.push({
    name: 'purchasing.bon-commend-list'
  })
}

const viewProductHistory = (productId) => {
  router.push({
    name: 'purchasing.product.history',
    params: { productId }
  })
}

const openCreateProductDialog = (index) => {
  productTargetIndex.value = index
  newProductForm.value = {
    name: '',
    code_interne: '',
    category: '',
    forme: '',
    is_clinical: false,
    description: ''
  }
  createProductDialog.value = true
}

const closeCreateProductDialog = () => {
  createProductDialog.value = false
  productTargetIndex.value = null
}

const createProduct = async () => {
  try {
    creatingProduct.value = true
    const payload = {
      name: newProductForm.value.name,
      code_interne: newProductForm.value.code_interne,
      category: newProductForm.value.category,
      forme: newProductForm.value.forme || 'pieces',
      is_clinical: newProductForm.value.is_clinical,
      description: newProductForm.value.description
    }
    const response = await axios.post('/api/products', payload)

    const createdProduct = response.data.data || response.data

    if (productTargetIndex.value !== null && bonCommend.value?.items) {
      const idx = productTargetIndex.value
      bonCommend.value.items[idx].product = createdProduct
      bonCommend.value.items[idx].product_id = createdProduct.id
      bonCommend.value.items[idx].unit = createdProduct.forme || bonCommend.value.items[idx].unit || 'pieces'
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
      detail: 'Failed to create product', 
      life: 5000 
    })
  } finally {
    creatingProduct.value = false
  }
}

// Utility functions
const getStatusLabel = (status) => {
  const statusMap = {
    'draft': 'Draft',
    'pending': 'Pending',
    'pending_approval': 'Pending Approval',
    'approved': 'Approved',
    'in_progress': 'In Progress',
    'delivered': 'Delivered',
    'confirmed': 'Confirmed',
    'cancelled': 'Cancelled',
    'rejected': 'Rejected',
    'needs_correction': 'Needs Correction'
  }
  return statusMap[status] || status
}

const getStatusSeverity = (status) => {
  const severityMap = {
    'draft': 'secondary',
    'pending': 'warning',
    'pending_approval': 'warning',
    'approved': 'info',
    'in_progress': 'info',
    'delivered': 'success',
    'confirmed': 'success',
    'cancelled': 'danger',
    'rejected': 'danger',
    'needs_correction': 'warning'
  }
  return severityMap[status] || 'secondary'
}

const getApprovalSeverity = (status) => {
  const severityMap = {
    'pending_approval': 'warning',
    'approved': 'success',
    'rejected': 'danger',
    'sent_back': 'warning'
  }
  return severityMap[status] || 'secondary'
}

const getProgressPercentage = (item) => {
  if (!item.quantity_desired || item.quantity_desired === 0) return 0
  return Math.round(((item.quantity || 0) / item.quantity_desired) * 100)
}

const getProgressSeverity = (percentage) => {
  if (percentage === 0) return 'danger'
  if (percentage < 50) return 'warning'
  if (percentage < 100) return 'info'
  return 'success'
}

const getRowClass = (data) => {
  if (data.confirmed_at) return 'tw-bg-green-50'
  if (data.quantity > 0) return 'tw-bg-blue-50'
  return ''
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const checkApprovalNeeded = async () => {
  if (!bonCommend.value) return

  try {
    checkingApproval.value = true
    const response = await axios.get(`/api/bon-commends/${bonCommend.value.id}/check-approval-needed`)

    if (response.data.status === 'success') {
      approvalInfo.value = response.data.data
    }
  } catch (err) {
    console.error('Error checking approval needed:', err)
  } finally {
    checkingApproval.value = false
  }
}

const submitForApproval = async () => {
  if (!bonCommend.value) return

  try {
    submittingApproval.value = true

    await saveChanges()

    const response = await axios.post(`/api/bon-commends/${bonCommend.value.id}/submit-for-approval`)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message,
        life: 3000
      })

      await fetchBonCommend()
    }
  } catch (err) {
    console.error('Error submitting for approval:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to submit for approval',
      life: 5000
    })
  } finally {
    submittingApproval.value = false
  }
}

const resubmitAfterCorrection = async () => {
  if (!bonCommend.value) return

  try {
    submittingApproval.value = true

    await saveChanges()
    await axios.put(`/api/bon-commends/${bonCommend.value.id}/status`, { status: 'draft' })

    const response = await axios.post(`/api/bon-commends/${bonCommend.value.id}/submit-for-approval`)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Resubmitted',
        detail: 'Bon commend resubmitted for approval with corrected quantities',
        life: 3000
      })

      await fetchBonCommend()
      await checkApprovalNeeded()
    }
  } catch (err) {
    console.error('Error resubmitting for approval:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to resubmit for approval',
      life: 5000
    })
  } finally {
    submittingApproval.value = false
  }
}

const viewApprovalHistory = () => {
  if (!bonCommend.value?.approvals) {
    toast.add({
      severity: 'info',
      summary: 'No History',
      detail: 'No approval history available',
      life: 3000
    })
    return
  }

  console.log('Approval History:', bonCommend.value.approvals)

  toast.add({
    severity: 'info',
    summary: 'Approval History',
    detail: `${bonCommend.value.approvals.length} approval record(s) found. Check console for details.`,
    life: 3000
  })
}

const finalConfirmation = async () => {
  if (!bonCommend.value) return

  try {
    confirming.value = true

    await saveChanges()

    const response = await axios.put(`/api/bon-commends/${bonCommend.value.id}/status`, { status: 'confirmed' })

    if (response.data.status === 'success') {
      bonCommend.value.status = 'confirmed'
      bonCommend.value.is_confirmed = true

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Purchase order confirmed successfully',
        life: 3000
      })

      await fetchBonCommend()
    }
  } catch (err) {
    console.error('Error confirming purchase order:', err)

    if (err.response?.status === 422 && err.response?.data?.requires_approval) {
      toast.add({
        severity: 'warn',
        summary: 'Approval Required',
        detail: err.response.data.message || 'This purchase order requires approval before confirmation',
        life: 5000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: err.response?.data?.message || 'Failed to confirm purchase order',
        life: 3000
      })
    }
  } finally {
    confirming.value = false
  }
}

const onRowExpand = (event) => {
  console.log('Row expanded:', event.data)
}

const onRowCollapse = (event) => {
  console.log('Row collapsed:', event.data)
}

// Lifecycle
onMounted(async () => {
  await fetchBonCommend()
  await checkApprovalNeeded()
})
</script>

<style scoped>
/* DataTable enhancements */
:deep(.p-datatable) {
  @apply border-0 tw-rounded-lg;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply bg-gray-50 tw-text-gray-700 tw-font-semibold tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply hover:tw-bg-indigo-50 tw-transition-colors;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  @apply border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
  @apply bg-indigo-100;
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
  @apply bg-gradient-to-r tw-from-indigo-600 tw-to-purple-700 tw-text-white;
}

:deep(.p-dialog-header .p-dialog-title) {
  @apply text-white tw-font-bold;
}

:deep(.p-dialog-header .p-dialog-header-icon) {
  @apply text-white hover:tw-bg-white/20;
}

/* Progress Bar */
:deep(.p-progressbar) {
  @apply bg-gray-200 tw-rounded-full tw-overflow-hidden;
}

:deep(.p-progressbar-value) {
  @apply transition-all tw-duration-300;
}

/* File Upload */
:deep(.p-fileupload) {
  @apply border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg;
}

:deep(.p-fileupload:hover) {
  @apply border-indigo-400 tw-bg-indigo-50;
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