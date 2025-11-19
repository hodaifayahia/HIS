<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50 tw-p-4 lg:tw-p-6">
    <!-- Enhanced Header with Breadcrumb -->
    <div class="tw-mb-6">
      <Breadcrumb :model="breadcrumbItems" class="tw-mb-4">
        <template #item="{ item }">
          <span class="tw-text-gray-600 hover:tw-text-indigo-600 tw-transition-colors">{{ item.label }}</span>
        </template>
      </Breadcrumb>

      <Card class="tw-border-0 tw-shadow-2xl tw-overflow-hidden hover:tw-shadow-3xl tw-transition-all tw-duration-500 hover:tw-scale-[1.02] tw-transform-gpu">
        <template #content>
          <!-- Enhanced Gradient Header with Animation -->
          <div class="tw-bg-gradient-to-br tw-from-indigo-600 tw-via-purple-600 tw-to-pink-600 tw-p-8 tw--m-8 tw-mb-8 tw-relative tw-overflow-hidden">
            <!-- Animated Background Elements -->
            <div class="tw-absolute tw-inset-0 tw-opacity-20">
              <div class="tw-absolute tw-top-0 tw-left-0 tw-w-32 tw-h-32 tw-bg-white tw-rounded-full tw-blur-3xl tw-animate-pulse"></div>
              <div class="tw-absolute tw-bottom-0 tw-right-0 tw-w-24 tw-h-24 tw-bg-blue-300 tw-rounded-full tw-blur-2xl tw-animate-pulse tw-animation-delay-1000"></div>
              <div class="tw-absolute tw-top-1/2 tw-left-1/3 tw-w-16 tw-h-16 tw-bg-purple-300 tw-rounded-full tw-blur-xl tw-animate-pulse tw-animation-delay-2000"></div>
            </div>

            <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6 tw-relative tw-z-10">
              <div class="tw-flex-1">
                <div class="tw-flex tw-items-center tw-gap-4 tw-mb-4">
                  <div class="tw-p-3 tw-bg-white/20 tw-rounded-xl tw-backdrop-blur-sm tw-border tw-border-white/30 hover:tw-bg-white/30 tw-transition-all tw-duration-300">
                    <i class="pi pi-shopping-cart tw-text-3xl tw-text-white"></i>
                  </div>
                  <div>
                    <h1 class="tw-text-4xl tw-font-bold tw-text-white tw-mb-2 tw-drop-shadow-lg">
                      Purchase Order Management
                    </h1>
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <span class="tw-text-indigo-100 tw-text-lg tw-font-medium">Order:</span>
                      <span class="tw-bg-white/20 tw-px-4 tw-py-2 tw-rounded-full tw-text-white tw-font-bold tw-text-lg tw-backdrop-blur-sm tw-border tw-border-white/30">
                        {{ bonCommend?.bonCommendCode || `BC-${bonCommend?.id}` || 'Loading...' }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Enhanced Status Display -->
                <div class="tw-flex tw-items-center tw-gap-4 tw-mb-4">
                  <span class="tw-text-indigo-100 tw-text-sm tw-font-medium">Status:</span>
                  <Tag
                    v-if="bonCommend"
                    :value="getStatusLabel(bonCommend.status)"
                    :severity="getStatusSeverity(bonCommend.status)"
                    class="tw-font-bold tw-text-sm tw-px-3 tw-py-2 tw-shadow-lg"
                    icon="pi pi-circle-fill"
                  />
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-white/80">
                    <i class="pi pi-calendar tw-text-sm"></i>
                    <span class="tw-text-sm">{{ formatDate(bonCommend?.created_at) }}</span>
                  </div>
                </div>

                <!-- Quick Metrics Row -->
                <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4 tw-mt-6">
                  <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20 hover:tw-bg-white/20 tw-transition-all tw-duration-300">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                      <i class="pi pi-box tw-text-blue-200 tw-text-sm"></i>
                      <span class="tw-text-blue-100 tw-text-xs tw-font-medium">Items</span>
                    </div>
                    <div class="tw-text-2xl tw-font-bold tw-text-white">{{ bonCommend?.items?.length || 0 }}</div>
                  </div>
                  <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20 hover:tw-bg-white/20 tw-transition-all tw-duration-300">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                      <i class="pi pi-check-circle tw-text-green-200 tw-text-sm"></i>
                      <span class="tw-text-green-100 tw-text-xs tw-font-medium">Ready</span>
                    </div>
                    <div class="tw-text-2xl tw-font-bold tw-text-white">{{ readyItemsCount }}</div>
                  </div>
                  <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20 hover:tw-bg-white/20 tw-transition-all tw-duration-300">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                      <i class="pi pi-dollar tw-text-yellow-200 tw-text-sm"></i>
                      <span class="tw-text-yellow-100 tw-text-xs tw-font-medium">Value</span>
                    </div>
                    <div class="tw-text-xl tw-font-bold tw-text-white">{{ formatCurrency(totalValue) }}</div>
                  </div>
                  <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20 hover:tw-bg-white/20 tw-transition-all tw-duration-300">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                      <i class="pi pi-percentage tw-text-purple-200 tw-text-sm"></i>
                      <span class="tw-text-purple-100 tw-text-xs tw-font-medium">Progress</span>
                    </div>
                    <div class="tw-text-2xl tw-font-bold tw-text-white">{{ Math.round((readyItemsCount / (bonCommend?.items?.length || 1)) * 100) }}%</div>
                  </div>
                </div>
              </div>

              <!-- Enhanced Action Buttons -->
              <div class="tw-flex tw-flex-col tw-gap-3">
                <div class="tw-flex tw-gap-3 tw-flex-wrap">
                  <Button
                    @click="goBack"
                    icon="pi pi-arrow-left"
                    label="Back to List"
                    class="p-button-secondary tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300 hover:tw-scale-105 tw-bg-white/10 tw-border-white/30 tw-text-white hover:tw-bg-white/20"
                    size="large"
                  />
                  <Button
                    @click="generatePDF"
                    :loading="generatingPDF"
                    icon="pi pi-file-pdf"
                    label="Export PDF"
                    class="p-button-help tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300 hover:tw-scale-105 tw-bg-red-500/80 hover:tw-bg-red-500"
                    size="large"
                  />
                </div>

                <!-- Progress Bar -->
                <div class="tw-bg-white/10 tw-rounded-full tw-p-3 tw-backdrop-blur-sm tw-border tw-border-white/20">
                  <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                    <span class="tw-text-white tw-text-sm tw-font-medium">Order Progress</span>
                    <span class="tw-text-white tw-text-sm tw-font-bold">{{ Math.round((readyItemsCount / (bonCommend?.items?.length || 1)) * 100) }}%</span>
                  </div>
                  <ProgressBar
                    :value="Math.round((readyItemsCount / (bonCommend?.items?.length || 1)) * 100)"
                    :showValue="false"
                    class="tw-h-2 tw-bg-white/20"
                    style="border-radius: 4px;"
                  />
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
            <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">Loading bon commend data...</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <div v-else-if="bonCommend" class="tw-space-y-6">

      <!-- Alert for Sent Back Status -->
      <Card v-if="bonCommend.status === 'needs_correction'" class="tw-border-2 tw-border-orange-400 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
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

      <!-- Confirmed State Alert -->
      <Card v-if="bonCommend.is_confirmed" class="tw-border-2 tw-border-green-400 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-100 tw-p-6 tw--m-6">
            <div class="tw-flex tw-items-start tw-gap-4">
              <div class="tw-bg-green-500 tw-rounded-full tw-p-3">
                <i class="pi pi-check-circle tw-text-3xl tw-text-white"></i>
              </div>
              <div class="tw-flex-1">
                <h3 class="tw-text-xl tw-font-bold tw-text-green-900 tw-mb-2">Bon Commend Confirmed</h3>
                <p class="tw-text-green-800 tw-mb-4">
                  This bon commend has been confirmed and is now read-only. No further edits can be made.
                </p>
                <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-sm">
                  <div class="tw-text-sm tw-text-gray-600">
                    <span class="tw-font-medium">Confirmed on:</span> {{ formatDate(bonCommend.confirmed_at) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Approval Status Card -->
      <Card v-if="showApprovalButtons && !bonCommend.is_confirmed" class="tw-border-0 tw-shadow-xl tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
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
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #title>
          <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-justify-between lg:tw-items-center tw-gap-4">
            <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center tw-gap-2">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="fas fa-list tw-text-indigo-600 tw-text-lg"></i>
                <span class="tw-font-semibold tw-text-gray-800">Purchase Items</span>
                <Tag :value="`${bonCommend.items?.length || 0} items`" severity="info" class="tw-text-xs" />
              </div>

              <!-- Search for items - Responsive -->
              <div v-if="bonCommend.items?.length > 10" class="tw-relative tw-w-full sm:tw-w-64">
                <InputText
                  v-model="itemSearchQuery"
                  placeholder="Search products..."
                  class="tw-pr-8 tw-w-full tw-text-sm"
                />
                <i class="fas fa-search tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
              </div>
            </div>

            <!-- Action Buttons - Responsive Grid -->
            <div class="tw-flex tw-flex-wrap tw-gap-2 tw-justify-center sm:tw-justify-end">
              <!-- Primary Actions - Icon Only on Mobile -->
              <div class="tw-flex tw-gap-1">
                <Button
                  v-if="!bonCommend.is_confirmed"
                  @click="openProductSelectorDialog(null)"
                  icon="fas fa-plus"
                  class="p-button-info p-button-sm p-button-rounded p-button-outlined tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                  v-tooltip.top="'Create Product'"
                />
              </div>

              <!-- Secondary Actions - Icon Only on Mobile -->
              <div class="tw-flex tw-gap-1">
                <Button
                  v-if="!bonCommend.is_confirmed"
                  @click="showBulkEditDialog = true"
                  icon="fas fa-edit"
                  class="p-button-secondary p-button-sm p-button-rounded p-button-outlined tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                  v-tooltip.top="'Bulk Edit'"
                />
                <Button
                  @click="exportToCSV"
                  icon="fas fa-download"
                  class="p-button-help p-button-sm p-button-rounded p-button-outlined tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                  v-tooltip.top="'Export CSV'"
                />
              </div>

              <!-- Save Action - Always Visible with Icon -->
              <Button
                v-if="!bonCommend.is_confirmed"
                @click="saveChanges"
                :loading="saving"
                icon="fas fa-save"
                label="Save Changes"
                class="p-button-primary p-button-sm tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200 tw-min-w-[120px]"
                v-tooltip.top="'Save All Changes'"
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
            :paginator="bonCommend.items.length > 75"
            :rows="50"
            :rowsPerPageOptions="[25, 50, 75, 100, 150, 200]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
            responsiveLayout="scroll"
            class="tw-mt-4 large-datatable"
            :rowClass="getRowClass"
            :expandedRows="expandedRows"
            @row-expand="onRowExpand"
            @row-collapse="onRowCollapse"
            size="large"
            :lazy="bonCommend.items.length > 500"
            :loading="tableLoading"
            scrollHeight="1200px"
            scrollable
          >
            <!-- Expander Column -->
            <Column :expander="true" style="width: 3rem" />

            <!-- Product Column -->
            <Column field="product.name" header="Product" :sortable="true" style="min-width: 280px; max-width: 320px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2 tw-py-2">
                  <Avatar 
                    :label="slotProps.data.product?.name?.charAt(0)" 
                    class="tw-bg-indigo-100 tw-text-indigo-700 tw-font-bold"
                    shape="square"
                    size="large"
                  />
                  <div class="tw-min-w-0 tw-flex-1">
                    <div class="tw-font-semibold tw-text-sm tw-truncate tw-leading-tight">{{ slotProps.data.product?.name || 'Unknown Product' }}</div>
                    <div class="tw-text-xs tw-text-gray-500 tw-truncate">Code: {{ slotProps.data.product?.code_interne || 'N/A' }}</div>
                    <div class="tw-flex tw-flex-wrap tw-gap-1 tw-mt-1">
                      <Tag 
                        v-if="slotProps.data.product?.is_required_approval" 
                        value="Requires Approval" 
                        severity="danger" 
                        icon="fas fa-lock"
                        class="tw-text-xs"
                      />
                      <Tag 
                        v-if="slotProps.data.product?.is_request_approval" 
                        value="Requests Approval" 
                        severity="warning" 
                        icon="fas fa-exclamation-circle"
                        class="tw-text-xs"
                      />
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Quantity Desired Column -->
            <Column field="quantity_desired" header="Qty Desired" :sortable="true" style="width: 110px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.quantity_desired"
                  :min="1"
                  class="tw-w-full"
                  inputClass="tw-text-center tw-text-sm tw-h-10 tw-font-semibold"
                  :disabled="bonCommend.is_confirmed"
                />
              </template>
            </Column>

            <!-- Quantity Sent Column -->
            <Column field="quantity" header="Qty Sent" :sortable="true" style="width: 110px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.quantity"
                  :min="0"
                  class="tw-w-full"
                  inputClass="tw-text-center tw-text-sm tw-h-10 tw-font-semibold"
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
                  class="tw-w-full tw-text-sm tw-h-10"
                  inputClass="tw-text-center tw-font-semibold"
                  :disabled="bonCommend.is_confirmed"
                />
              </template>
            </Column>

            <!-- Unit Price Column -->
            <Column field="price" header="Unit Price" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <InputNumber
                  v-model="slotProps.data.price"
                  :min="0"
                  mode="currency"
                  currency="DZD"
                  locale="fr-FR"
                  class="tw-w-full"
                  inputClass="tw-text-center tw-text-sm tw-h-10 tw-font-semibold"
                  :disabled="bonCommend.is_confirmed"
                />
              </template>
            </Column>

            <!-- Total Column -->
            <Column header="Total" :sortable="true" style="width: 130px">
              <template #body="slotProps">
                <span class="tw-font-bold tw-text-green-600 tw-text-sm tw-block tw-text-right tw-pr-2">
                  {{ formatCurrency((slotProps.data.price || 0) * (slotProps.data.quantity_desired || 0)) }}
                </span>
              </template>
            </Column>

            <!-- Progress Column -->
            <Column header="Progress" style="width: 140px">
              <template #body="slotProps">
                <div class="tw-space-y-1">
                  <ProgressBar 
                    :value="getProgressPercentage(slotProps.data)"
                    :showValue="false"
                    class="tw-h-2"
                    :severity="getProgressSeverity(getProgressPercentage(slotProps.data))"
                  />
                  <span class="tw-text-xs tw-text-gray-600 tw-block tw-text-center tw-font-semibold">
                    {{ slotProps.data.quantity || 0 }}/{{ slotProps.data.quantity_desired }}
                  </span>
                </div>
              </template>
            </Column>

            <!-- Status Column -->
            <Column field="confirmed_at" header="Status" style="width: 85px">
              <template #body="slotProps">
                <Tag 
                  v-if="slotProps.data.confirmed_at"
                  value="Confirmed" 
                  severity="success"
                  icon="fas fa-check"
                  class="tw-text-[9px] tw-px-1 tw-py-0.5"
                />
                <Tag 
                  v-else
                  value="Pending" 
                  severity="warning"
                  icon="fas fa-clock"
                  class="tw-text-[9px] tw-px-1 tw-py-0.5"
                />
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
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="fas fa-calculator tw-text-green-600"></i>
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
          <div v-if="!bonCommend.is_confirmed" class="tw-bg-gray-50 tw-rounded-lg tw-p-6">
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Purchase Order Confirmation</h3>
                <p class="tw-text-gray-600 tw-mt-1">
                  Confirm this entire bon commend once all items are processed
                </p>
              </div>
              <div class="tw-flex tw-gap-3">
                <Button 
                  v-if="canConfirmBonCommend && (!approvalInfo.needsApproval || bonCommend.isApproved)"
                  @click="confirmEntireBonCommend"
                  :disabled="confirming"
                  :loading="confirming"
                  icon="fas fa-check"
                  label="Confirm Entire Purchase Order"
                  class="p-button-success"
                  size="large"
                />
                <div v-else class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
                  <Tag 
                    v-if="!canConfirmBonCommend"
                    value="Incomplete Items" 
                    severity="info" 
                    icon="fas fa-list-check" 
                  />
                  <Tag 
                    v-else-if="approvalInfo.needsApproval && !bonCommend.isApproved"
                    value="Awaiting Approval" 
                    severity="warning" 
                    icon="fas fa-clock" 
                  />
                </div>
              </div>
            </div>

            <div v-if="!bonCommend.can_be_confirmed_now" class="tw-mt-4 tw-space-y-3">
              <Message v-if="!canConfirmBonCommend" severity="info" :closable="false">
                <i class="pi pi-info-circle tw-mr-2"></i>
                <strong>Action Required:</strong> Please set quantity sent for all items ({{ readyItemsCount }}/{{ bonCommend.items?.length || 0 }} ready)
              </Message>
              <Message v-if="approvalInfo.needsApproval && !bonCommend.isApproved" severity="warn" :closable="false">
                <i class="pi pi-exclamation-triangle tw-mr-2"></i>
                <strong>Pending Approval:</strong> This purchase order requires approval before confirmation. 
                <span v-if="approvalInfo.approver">
                  Assigned to: <strong>{{ approvalInfo.approver.name }}</strong>
                </span>
              </Message>
            </div>
          </div>
        </template>
      </Card>

      <!-- Attachments Card -->
      <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-shadow tw-duration-300">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="fas fa-paperclip tw-text-teal-600"></i>
            <span>Attachments</span>
            <Tag :value="`${existingAttachments.length + newAttachments.length} files`" severity="info" />
          </div>
        </template>
        <template #content>
          <!-- File Upload -->
          <FileUpload
            v-if="!bonCommend.is_confirmed"
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
                <i class="fas fa-cloud-upload tw-text-4xl tw-text-gray-400 tw-mb-2"></i>
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
                    icon="fas fa-download"
                    class="p-button-text p-button-sm"
                    @click="downloadAttachment(attachment, index)"
                    v-tooltip.top="'Download'"
                  />
                  <Button
                    v-if="!bonCommend.is_confirmed"
                    icon="fas fa-times"
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
                  <i class="fas fa-file-plus tw-text-2xl tw-text-green-600"></i>
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ attachment.name }}</div>
                    <div class="tw-text-xs tw-text-green-600 tw-mt-1">Ready to upload</div>
                  </div>
                </div>
                <Button
                  icon="fas fa-times"
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

    <!-- Product Selector Dialog -->
    <Dialog
      :visible="showProductSelectorDialog"
      :header="isPharmacyOrder ? 'Select Pharmacy Product' : 'Select Stock Product'"
      :modal="true"
      :style="{ width: '900px', maxHeight: '90vh' }"
      class="enhanced-dialog"
      @hide="showProductSelectorDialog = false"
    >
      <div class="tw-space-y-4">
        <!-- Info Section -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
          <div class="tw-flex tw-items-start tw-gap-3">
            <div class="tw-bg-blue-500 tw-rounded-full tw-p-2">
              <i class="pi pi-search tw-text-white tw-text-lg"></i>
            </div>
            <div>
              <h3 class="tw-text-lg tw-font-semibold tw-text-blue-900">
                {{ isPharmacyOrder ? 'Pharmacy Products' : 'Stock Products' }}
              </h3>
              <p class="tw-text-sm tw-text-blue-700">
                {{ isPharmacyOrder 
                  ? 'Select a product from the pharmacy inventory' 
                  : 'Select a product from the stock inventory' 
                }}
              </p>
            </div>
          </div>
        </div>

        <!-- Product Selector Component -->
        <ProductSelectorWithInfiniteScroll
          ref="productSelectorRef"
          v-model="selectedProductsFromSelector"
          :scroll-height="'500px'"
          :per-page="20"
          :show-source-filter="false"
          :show-stock="true"
          :show-select-all="false"
          :selectable="true"
          :default-tab="isPharmacyOrder ? 'pharmacy' : 'stock'"
          :default-quantity="defaultQuantity"
          :default-price="defaultPrice"
          :default-unit="defaultUnit"
          @selection-change="handleProductSelection"
          @bulk-add="handleBulkAdd"
          @defaults-change="handleDefaultsChange"
        />
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-between tw-items-center tw-pt-4 tw-border-t tw-border-gray-200">
          <Button
            label="Create New Product"
            icon="pi pi-plus"
            class="p-button-outlined enhanced-primary-btn"
            @click="showProductSelectorDialog = false; openCreateProductDialog(productTargetIndex)"
          />
          <div class="tw-flex tw-gap-3">
            <Button
              label="Cancel"
              icon="pi pi-times"
              class="p-button-text enhanced-cancel-btn"
              @click="showProductSelectorDialog = false; productTargetIndex = null; selectedProductsFromSelector = []"
            />
            <Button
              label="Apply Selection"
              icon="pi pi-check"
              :disabled="selectedProductsFromSelector.length === 0"
              class="enhanced-success-btn"
              @click="applyProductSelection"
            />
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Create Product Dialog -->
    <Dialog
      :visible="createProductDialog"
      header="Create New Product"
      :modal="true"
      :style="{ width: '600px' }"
      class="enhanced-dialog"
      @hide="createProductDialog = false"
    >
      <div class="tw-space-y-6">
        <!-- Header Section -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-blue-500 tw-rounded-full tw-p-2">
                <i class="pi pi-plus tw-text-white tw-text-lg"></i>
              </div>
              <div>
                <h3 class="tw-text-lg tw-font-semibold tw-text-blue-900">Create New Product</h3>
                <p class="tw-text-sm tw-text-blue-700">Add a new product to the system and select it for this item</p>
              </div>
            </div>
            <Tag 
              :value="newProductForm.is_clinical ? 'Pharmacy Product' : 'Stock Product'" 
              :severity="newProductForm.is_clinical ? 'info' : 'success'"
              class="tw-font-semibold"
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <!-- Product Name -->
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Product Name <span class="tw-text-red-500">*</span>
            </label>
            <InputText
              v-model="newProductForm.name"
              placeholder="Enter product name"
              class="tw-w-full enhanced-input"
            />
          </div>

          <!-- Internal Code -->
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Internal Code
            </label>
            <InputNumber
              v-model="newProductForm.code_interne"
              placeholder="Enter internal code"
              class="tw-w-full enhanced-input"
              :useGrouping="false"
            />
          </div>

          <!-- Category -->
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Category <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="newProductForm.category"
              :options="categoryOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select category"
              class="tw-w-full enhanced-dropdown"
            />
          </div>

          <!-- Form/Unit -->
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Form/Unit
            </label>
            <InputText
              v-model="newProductForm.forme"
              placeholder="e.g. tablet, ml, piece"
              class="tw-w-full enhanced-input"
            />
          </div>
        </div>

        <!-- Product Type Selection -->
        <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg tw-border tw-border-gray-200">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-3">
            Product Type <span class="tw-text-red-500">*</span>
          </label>
          <div class="tw-grid tw-grid-cols-2 tw-gap-3">
            <div 
              @click="newProductForm.is_clinical = false"
              :class="[
                'tw-p-4 tw-rounded-lg tw-border-2 tw-cursor-pointer tw-transition-all tw-duration-200',
                !newProductForm.is_clinical 
                  ? 'tw-border-green-500 tw-bg-green-50' 
                  : 'tw-border-gray-300 tw-bg-white hover:tw-border-green-300'
              ]"
            >
              <div class="tw-flex tw-items-center tw-gap-3">
                <div :class="[
                  'tw-w-5 tw-h-5 tw-rounded-full tw-border-2 tw-flex tw-items-center tw-justify-center',
                  !newProductForm.is_clinical ? 'tw-border-green-500 tw-bg-green-500' : 'tw-border-gray-300'
                ]">
                  <i v-if="!newProductForm.is_clinical" class="fas fa-check tw-text-white tw-text-xs"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-900">Stock Product</div>
                  <div class="tw-text-xs tw-text-gray-600">General inventory items</div>
                </div>
              </div>
            </div>

            <div 
              @click="newProductForm.is_clinical = true"
              :class="[
                'tw-p-4 tw-rounded-lg tw-border-2 tw-cursor-pointer tw-transition-all tw-duration-200',
                newProductForm.is_clinical 
                  ? 'tw-border-blue-500 tw-bg-blue-50' 
                  : 'tw-border-gray-300 tw-bg-white hover:tw-border-blue-300'
              ]"
            >
              <div class="tw-flex tw-items-center tw-gap-3">
                <div :class="[
                  'tw-w-5 tw-h-5 tw-rounded-full tw-border-2 tw-flex tw-items-center tw-justify-center',
                  newProductForm.is_clinical ? 'tw-border-blue-500 tw-bg-blue-500' : 'tw-border-gray-300'
                ]">
                  <i v-if="newProductForm.is_clinical" class="fas fa-check tw-text-white tw-text-xs"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-900">Pharmacy Product</div>
                  <div class="tw-text-xs tw-text-gray-600">Clinical/medical items</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="tw-space-y-2">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Description
          </label>
          <Textarea
            v-model="newProductForm.description"
            placeholder="Enter product description (optional)"
            rows="3"
            class="tw-w-full enhanced-textarea"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-between tw-items-center tw-pt-4 tw-border-t tw-border-gray-200">
          <div class="tw-text-sm tw-text-gray-600">
            <i class="pi pi-info-circle tw-mr-2"></i>
            Product will be added to {{ newProductForm.is_clinical ? 'Pharmacy' : 'Stock' }} inventory
          </div>
          <div class="tw-flex tw-gap-3">
            <Button
              label="Cancel"
              icon="pi pi-times"
              class="p-button-text enhanced-cancel-btn"
              @click="closeCreateProductDialog"
            />
            <Button
              :label="`Create ${newProductForm.is_clinical ? 'Pharmacy' : 'Stock'} Product`"
              icon="pi pi-check"
              :loading="creatingProduct"
              :disabled="!newProductForm.name || !newProductForm.category"
              class="enhanced-primary-btn"
              @click="createProduct"
            />
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Bulk Edit Dialog -->
    <Dialog
      :visible="showBulkEditDialog"
      header="Bulk Edit Items"
      :modal="true"
      :style="{ width: '500px' }"
      class="enhanced-dialog"
      @hide="showBulkEditDialog = false"
    >
      <div class="tw-space-y-6">
        <!-- Info Section -->
        <div class="tw-bg-gradient-to-r tw-from-amber-50 tw-to-orange-50 tw-p-4 tw-rounded-lg tw-border tw-border-amber-200">
          <div class="tw-flex tw-items-start tw-gap-3">
            <div class="tw-bg-amber-500 tw-rounded-full tw-p-2 tw-mt-0.5">
              <i class="pi pi-info-circle tw-text-white tw-text-sm"></i>
            </div>
            <div>
              <h4 class="tw-text-sm tw-font-semibold tw-text-amber-900">Bulk Edit Mode</h4>
              <p class="tw-text-sm tw-text-amber-800 tw-mt-1">
                Apply changes to multiple items at once. Leave fields empty to skip updating them.
              </p>
            </div>
          </div>
        </div>

        <div class="tw-space-y-6">
          <!-- Unit Field -->
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Unit
            </label>
            <InputText
              v-model="bulkEditData.unit"
              placeholder="e.g. pieces, boxes, ml"
              class="tw-w-full enhanced-input"
            />
          </div>

          <!-- Unit Price Field -->
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Unit Price
            </label>
            <InputNumber
              v-model="bulkEditData.price"
              :min="0"
              mode="currency"
              currency="DZD"
              locale="fr-FR"
              class="tw-w-full enhanced-input"
            />
          </div>

          <!-- Apply To Section -->
          <div class="tw-space-y-3">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Apply to
            </label>
            <div class="tw-space-y-3">
              <div class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-blue-50 tw-rounded-lg tw-border tw-border-blue-200">
                <RadioButton
                  v-model="bulkEditData.applyToAll"
                  inputId="all"
                  name="applyTo"
                  :value="true"
                  class="enhanced-radio"
                />
                <div>
                  <label for="all" class="tw-text-sm tw-font-medium tw-text-blue-900 tw-cursor-pointer">
                    All items ({{ bonCommend.items?.length || 0 }})
                  </label>
                  <p class="tw-text-xs tw-text-blue-700 tw-mt-1">Apply changes to every item in the list</p>
                </div>
              </div>
              <div class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gray-50 tw-rounded-lg tw-border tw-border-gray-200">
                <RadioButton
                  v-model="bulkEditData.applyToAll"
                  inputId="selected"
                  name="applyTo"
                  :value="false"
                  class="enhanced-radio"
                />
                <div>
                  <label for="selected" class="tw-text-sm tw-font-medium tw-text-gray-900 tw-cursor-pointer">
                    Only selected items ({{ bulkEditData.selectedItems.length }})
                  </label>
                  <p class="tw-text-xs tw-text-gray-600 tw-mt-1">Apply changes to selected items only</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text enhanced-cancel-btn"
            @click="showBulkEditDialog = false"
          />
          <Button
            label="Apply Changes"
            icon="pi pi-check"
            class="enhanced-success-btn"
            :disabled="!bulkEditData.unit && !bulkEditData.price"
            @click="applyBulkEdit"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch ,reactive } from 'vue'
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
import RadioButton from 'primevue/radiobutton'
import ProductSelectorWithInfiniteScroll from '@/Components/Apps/Purchasing/Shared/ProductSelectorWithInfiniteScroll.vue'

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
const tableLoading = ref(false)
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
const showBulkEditDialog = ref(false)
const showProductSelectorDialog = ref(false)
const productSelectorRef = ref(null)
const selectedProductsFromSelector = ref([])
const bulkEditData = ref({
  unit: '',
  price: null,
  applyToAll: false,
  selectedItems: []
})
const newProductForm = ref({
  name: '',
  code_interne: '',
  category: '',
  forme: '',
  is_clinical: false,
  description: ''
})

// Default values for product selection
const defaultQuantity = ref(1)
const defaultPrice = ref(0)
const defaultUnit = ref('unit')

// Computed to check if this is a pharmacy order
const isPharmacyOrder = computed(() => {
  return bonCommend.value?.service_demand_purchasing?.is_pharmacy_order || false
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

const openProductSelectorDialog = (index) => {
  productTargetIndex.value = index
  showProductSelectorDialog.value = true
  selectedProductsFromSelector.value = []
}

const openCreateProductDialog = (index) => {
  productTargetIndex.value = index
  newProductForm.value = {
    name: '',
    code_interne: '',
    category: '',
    forme: '',
    is_clinical: isPharmacyOrder.value,
    description: ''
  }
  createProductDialog.value = true
}

const closeCreateProductDialog = () => {
  createProductDialog.value = false
  productTargetIndex.value = null
}

const handleProductSelection = (selectedProducts) => {
  // This is now just for monitoring selection changes
  // Don't close dialog here - let user manually apply or cancel
  selectedProductsFromSelector.value = selectedProducts || []
}

const handleDefaultsChange = (defaults) => {
  // Update default values when user changes them in the selector
  defaultQuantity.value = defaults.quantity
  defaultPrice.value = defaults.price
  defaultUnit.value = defaults.unit
}

const applyProductSelection = () => {
  const selectedProducts = selectedProductsFromSelector.value
  
  if (!selectedProducts || selectedProducts.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Selection',
      detail: 'Please select at least one product',
      life: 3000
    })
    return
  }

  if (!bonCommend.value?.items) {
    bonCommend.value.items = []
  }

  // Add all selected products as new items
  selectedProducts.forEach(selectedProduct => {
    const newItem = {
      product: selectedProduct,
      product_id: selectedProduct.id,
      quantity_desired: defaultQuantity.value || 1,
      quantity: defaultQuantity.value || 1,
      unit: defaultUnit.value || selectedProduct.purchaseUnit || selectedProduct.forme || selectedProduct.unit || 'unit',
      price: defaultPrice.value || selectedProduct.price || selectedProduct.unit_price || 0,
      unit_price: defaultPrice.value || selectedProduct.price || selectedProduct.unit_price || 0,
      notes: '',
      source_type: 'service_demand',
      source_id: bonCommend.value.service_demand_purchasing_id || null,
      confirmed_at: null,
      modified_by_approver: false
    }
    
    bonCommend.value.items.push(newItem)
  })

  // Trigger reactivity by creating a new array reference
  bonCommend.value.items = [...bonCommend.value.items]
  
  toast.add({
    severity: 'success',
    summary: 'Products Added',
    detail: `${selectedProducts.length} product(s) added to bon commend`,
    life: 3000
  })

  // Close dialog and reset after successful selection
  showProductSelectorDialog.value = false
  productTargetIndex.value = null
  selectedProductsFromSelector.value = []
}

const handleBulkAdd = (selectedProducts) => {
  if (!selectedProducts || selectedProducts.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Selection',
      detail: 'Please select at least one product',
      life: 3000
    })
    return
  }

  if (!bonCommend.value?.items) {
    bonCommend.value.items = []
  }

  // Add all selected products as new items
  selectedProducts.forEach(selectedProduct => {
    const newItem = {
      product: selectedProduct,
      product_id: selectedProduct.id,
      quantity_desired: defaultQuantity.value || 1,
      quantity: defaultQuantity.value || 1,
      unit: defaultUnit.value || selectedProduct.purchaseUnit || selectedProduct.forme || selectedProduct.unit || 'unit',
      price: defaultPrice.value || selectedProduct.price || selectedProduct.unit_price || 0,
      unit_price: defaultPrice.value || selectedProduct.price || selectedProduct.unit_price || 0,
      notes: '',
      source_type: 'service_demand',
      source_id: bonCommend.value.service_demand_purchasing_id || null,
      confirmed_at: null,
      modified_by_approver: false
    }
    
    bonCommend.value.items.push(newItem)
  })

  // Trigger reactivity by creating a new array reference
  bonCommend.value.items = [...bonCommend.value.items]
  
  toast.add({
    severity: 'success',
    summary: 'Bulk Add Complete',
    detail: `${selectedProducts.length} product(s) added to bon commend`,
    life: 3000
  })

  // Clear selection after bulk add
  selectedProductsFromSelector.value = []
}

const createProduct = async () => {
  try {
    creatingProduct.value = true
    
    // Determine the API endpoint based on is_clinical
    const apiEndpoint = newProductForm.value.is_clinical 
      ? '/api/pharmacy/products'
      : '/api/products'
    
    const payload = {
      name: newProductForm.value.name,
      code_interne: newProductForm.value.code_interne,
      category: newProductForm.value.category,
      forme: newProductForm.value.forme || 'pieces',
      is_clinical: newProductForm.value.is_clinical,
      description: newProductForm.value.description
    }
    
    const response = await axios.post(apiEndpoint, payload)
    const createdProduct = response.data.data || response.data

    if (productTargetIndex.value !== null && bonCommend.value?.items) {
      const idx = productTargetIndex.value
      bonCommend.value.items[idx].product = createdProduct
      bonCommend.value.items[idx].product_id = createdProduct.id
      bonCommend.value.items[idx].unit = createdProduct.forme || bonCommend.value.items[idx].unit || 'pieces'
    }

    const productType = newProductForm.value.is_clinical ? 'Pharmacy' : 'Stock'
    toast.add({ 
      severity: 'success', 
      summary: 'Success', 
      detail: `${productType} product created and selected successfully`, 
      life: 3000 
    })

    closeCreateProductDialog()
  } catch (err) {
    console.error('Error creating product:', err)
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: err.response?.data?.message || 'Failed to create product', 
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

// Bulk operations
const applyBulkEdit = () => {
  if (!bonCommend.value?.items) return

  const itemsToUpdate = bulkEditData.value.applyToAll 
    ? bonCommend.value.items 
    : bulkEditData.value.selectedItems

  itemsToUpdate.forEach(item => {
    if (bulkEditData.value.unit) {
      item.unit = bulkEditData.value.unit
    }
    if (bulkEditData.value.price !== null && bulkEditData.value.price !== undefined) {
      item.price = bulkEditData.value.price
    }
  })

  toast.add({
    severity: 'success',
    summary: 'Bulk Edit Applied',
    detail: `Updated ${itemsToUpdate.length} items`,
    life: 3000
  })

  showBulkEditDialog.value = false
  bulkEditData.value = {
    unit: '',
    price: null,
    applyToAll: false,
    selectedItems: []
  }
}

const exportToCSV = () => {
  if (!bonCommend.value?.items) return

  const headers = ['Product Name', 'Code', 'Qty Desired', 'Qty Sent', 'Unit', 'Unit Price', 'Total', 'Progress %', 'Status']
  const csvContent = [
    headers.join(','),
    ...bonCommend.value.items.map(item => [
      `"${item.product?.name || 'Unknown'}"`,
      `"${item.product?.code_interne || 'N/A'}"`,
      item.quantity_desired || 0,
      item.quantity || 0,
      `"${item.unit || ''}"`,
      item.price || 0,
      ((item.price || 0) * (item.quantity_desired || 0)).toFixed(2),
      getProgressPercentage(item),
      item.confirmed_at ? 'Confirmed' : 'Pending'
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `bon-commend-${bonCommend.value.id}-items.csv`
  link.click()

  toast.add({
    severity: 'success',
    summary: 'Export Complete',
    detail: 'CSV file downloaded successfully',
    life: 3000
  })
}

// Lifecycle
onMounted(async () => {
  await fetchBonCommend()
  await checkApprovalNeeded()
})
</script>

<style scoped>
/* Enhanced DataTable Styling - Large Version */
:deep(.p-datatable) {
  border: 0;
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  background: white;
  font-size: 14px;
}

:deep(.large-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #1f2937;
  font-weight: bold;
  border-bottom: 2px solid #c7d2fe;
  padding-top: 1rem;
  padding-bottom: 1rem;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: relative;
}

:deep(.large-datatable .p-datatable-thead > tr > th:hover) {
  background: linear-gradient(135deg, #eef2ff 0%, #dbeafe 100%);
}

:deep(.large-datatable .p-datatable-tbody > tr) {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid #f3f4f6;
  height: 4.5rem;
}

:deep(.large-datatable .p-datatable-tbody > tr:hover) {
  background: linear-gradient(135deg, rgba(238, 242, 255, 0.5) 0%, rgba(219, 234, 254, 0.5) 100%);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  transform: scale(1.001);
}

:deep(.large-datatable .p-datatable-tbody > tr.p-highlight) {
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border-left: 4px solid #4ade80;
}

:deep(.large-datatable .p-datatable-tbody > tr > td) {
  border: 1px solid #f3f4f6;
  padding-top: 1rem;
  padding-bottom: 1rem;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
  vertical-align: middle;
  font-size: 14px;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #1f2937;
  font-weight: bold;
  border-bottom: 2px solid #c7d2fe;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: relative;
}

/* Enhanced Row Classes */
:deep(.p-datatable .p-datatable-tbody > tr.tw-bg-green-50) {
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border-left: 4px solid #4ade80;
  animation: rowHighlight 0.3s ease-out;
}

:deep(.p-datatable .p-datatable-tbody > tr.tw-bg-blue-50) {
  background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
  border-left: 4px solid #3b82f6;
}

/* High Density Table Optimizations */
:deep(.high-density-table .p-datatable-tbody > tr > td) {
  padding-top: 0.375rem;
  padding-bottom: 0.375rem;
  padding-left: 0.375rem;
  padding-right: 0.375rem;
}

:deep(.high-density-table .p-datatable-thead > tr > th) {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  padding-left: 0.375rem;
  padding-right: 0.375rem;
  font-size: 10px;
}

:deep(.high-density-table .p-inputnumber-input) {
  font-size: 0.75rem;
  line-height: 1.25;
  height: 1.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

:deep(.high-density-table .p-inputnumber-input:focus) {
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  border-color: #6366f1;
}

:deep(.high-density-table .p-inputtext) {
  font-size: 0.75rem;
  line-height: 1.25;
  height: 1.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

:deep(.high-density-table .p-inputtext:focus) {
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  border-color: #6366f1;
}

:deep(.high-density-table .p-button-xs) {
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

:deep(.high-density-table .p-button-xs:hover) {
  transform: scale(1.1);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

:deep(.high-density-table .p-button-xs .p-button-icon) {
  font-size: 0.75rem;
}

/* Enhanced Progress Bars */
:deep(.high-density-table .p-progressbar) {
  height: 0.5rem;
  border-radius: 9999px;
  overflow: hidden;
  background: linear-gradient(90deg, #f3f4f6 0%, #e5e7eb 100%);
}

:deep(.high-density-table .p-progressbar .p-progressbar-value) {
  transition: all 0.5s ease-out;
  background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
}

/* Enhanced Tags */
:deep(.high-density-table .p-tag) {
  font-size: 9px;
  padding-left: 0.375rem;
  padding-right: 0.375rem;
  padding-top: 0.125rem;
  padding-bottom: 0.125rem;
  line-height: 1.25;
  border-radius: 9999px;
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Avatar Enhancements */
:deep(.p-avatar) {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 2px solid white;
}

:deep(.p-avatar.tw-bg-indigo-100) {
  border-color: #c7d2fe;
}

/* Scrollable Table Container */
:deep(.p-datatable-wrapper) {
  max-height: 1000px;
  overflow: auto;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f8fafc;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar) {
  width: 6px;
  height: 6px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-track) {
  background: #f3f4f6;
  border-radius: 0.5rem;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb) {
  background: #cbd5e1;
  border-radius: 0.5rem;
  transition: background-color 0.2s ease;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb:hover) {
  background: #94a3b8;
}

/* Enhanced Card Styling */
:deep(.p-card) {
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.p-card-title) {
  font-size: 1.25rem;
  line-height: 1.75rem;
  font-weight: bold;
  color: #1f2937;
}

/* Enhanced Dialog Styling */
:deep(.enhanced-dialog .p-dialog-content) {
  padding: 1.5rem;
}

:deep(.enhanced-dialog .p-dialog-header) {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
  border-bottom: 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

:deep(.enhanced-dialog .p-dialog-header .p-dialog-title) {
  color: white;
  font-weight: bold;
  font-size: 1.25rem;
  line-height: 1.75rem;
}

:deep(.enhanced-dialog .p-dialog-header .p-dialog-header-icon) {
  color: white;
  border-radius: 9999px;
  transition: all 0.2s ease;
}

:deep(.enhanced-dialog .p-dialog-header .p-dialog-header-icon:hover) {
  background-color: rgba(255, 255, 255, 0.2);
}

:deep(.enhanced-dialog .p-dialog-footer) {
  background: #f9fafb;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
  border-top: 1px solid #e5e7eb;
}

/* Enhanced Form Elements */
:deep(.enhanced-input) {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

:deep(.enhanced-input:focus) {
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 1px 3px rgba(0, 0, 0, 0.1);
  border-color: #6366f1;
}

:deep(.enhanced-input:hover) {
  border-color: #a78bfa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.enhanced-dropdown) {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

:deep(.enhanced-dropdown:focus) {
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 1px 3px rgba(0, 0, 0, 0.1);
  border-color: #6366f1;
}

:deep(.enhanced-dropdown:hover) {
  border-color: #a78bfa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.enhanced-textarea) {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  resize: none;
}

:deep(.enhanced-textarea:focus) {
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 1px 3px rgba(0, 0, 0, 0.1);
  border-color: #6366f1;
}

:deep(.enhanced-textarea:hover) {
  border-color: #a78bfa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.enhanced-checkbox .p-checkbox-box) {
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

:deep(.enhanced-checkbox .p-checkbox-box:hover) {
  border-color: #a78bfa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.enhanced-checkbox.p-checkbox-checked .p-checkbox-box) {
  background: #4f46e5;
  border-color: #4f46e5;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.enhanced-radio .p-radiobutton-box) {
  border: 1px solid #d1d5db;
  border-radius: 9999px;
  transition: all 0.2s ease;
}

:deep(.enhanced-radio .p-radiobutton-box:hover) {
  border-color: #a78bfa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.enhanced-radio.p-radiobutton-checked .p-radiobutton-box) {
  background: #4f46e5;
  border-color: #4f46e5;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Enhanced Button Styles */
:deep(.enhanced-primary-btn) {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  border: 0;
  border-radius: 0.5rem;
  font-weight: 600;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

:deep(.enhanced-primary-btn:hover) {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  transform: scale(1.05);
}

:deep(.enhanced-primary-btn:active) {
  transform: scale(0.95);
}

:deep(.enhanced-success-btn) {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border: 0;
  border-radius: 0.5rem;
  font-weight: 600;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

:deep(.enhanced-success-btn:hover) {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  transform: scale(1.05);
}

:deep(.enhanced-success-btn:active) {
  transform: scale(0.95);
}

:deep(.enhanced-cancel-btn) {
  color: #374151;
  border-radius: 0.5rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

:deep(.enhanced-cancel-btn:hover) {
  background: #f3f4f6;
  transform: scale(1.05);
}

/* Dialog Animations */
@keyframes dialogSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

:deep(.enhanced-dialog) {
  animation: dialogSlideIn 0.3s ease-out;
}

/* Form Field Focus Animations */
@keyframes inputFocus {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
  100% {
    transform: scale(1);
  }
}

:deep(.enhanced-input:focus, .enhanced-dropdown:focus, .enhanced-textarea:focus) {
  animation: inputFocus 0.2s ease-out;
}

/* Enhanced Radio Button Cards */
:deep(.enhanced-radio-card) {
  transition: all 0.2s ease;
  cursor: pointer;
}

:deep(.enhanced-radio-card:hover) {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transform: scale(1.02);
}

:deep(.enhanced-radio-card.p-radiobutton-checked) {
  box-shadow: 0 0 0 2px #6366f1;
  background-color: rgba(238, 242, 255, 0.5);
}

/* Enhanced Progress Bar Global */
:deep(.p-progressbar) {
  background: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

:deep(.p-progressbar-value) {
  transition: all 0.5s ease-out;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

/* Enhanced File Upload */
:deep(.p-fileupload) {
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  transition: all 0.2s ease;
}

:deep(.p-fileupload:hover) {
  border-color: #a78bfa;
  background-color: rgba(238, 242, 255, 0.5);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Enhanced Animations & Transitions */
@keyframes slideInFromTop {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInFromBottom {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInFromLeft {
  from {
    opacity: 0;
    transform: translateX(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInFromRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.3);
  }
  50% {
    opacity: 1;
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

@keyframes shimmer {
  0% {
    background-position: -200px 0;
  }
  100% {
    background-position: calc(200px + 100%) 0;
  }
}

@keyframes rowHighlight {
  0% {
    background-color: rgba(34, 197, 94, 0.1);
    transform: scale(1);
  }
  50% {
    background-color: rgba(34, 197, 94, 0.2);
    transform: scale(1.005);
  }
  100% {
    background-color: rgba(240, 253, 244, 1);
    transform: scale(1);
  }
}

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

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-6px);
  }
}

@keyframes gradientShift {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

/* Page Load Animations */
.tw-page-enter {
  animation: slideInFromTop 0.6s ease-out;
}

/* Card Animations */
:deep(.p-card) {
  animation: slideInFromBottom 0.4s ease-out;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
}

/* Button Micro-interactions */
:deep(.p-button) {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

:deep(.p-button::before) {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.3s ease, height 0.3s ease;
}

:deep(.p-button:active::before) {
  width: 300px;
  height: 300px;
}

:deep(.p-button.p-button-primary) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

:deep(.p-button.p-button-primary:hover) {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

:deep(.p-button.p-button-success) {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
}

:deep(.p-button.p-button-success:hover) {
  background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(72, 187, 120, 0.6);
}

:deep(.p-button.p-button-warning) {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  box-shadow: 0 4px 15px rgba(237, 137, 54, 0.4);
}

:deep(.p-button.p-button-warning:hover) {
  background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(237, 137, 54, 0.6);
}

/* Input Field Animations */
:deep(.p-inputtext, .p-inputnumber-input, .p-textarea) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

:deep(.p-inputtext:focus, .p-inputnumber-input:focus, .p-textarea:focus) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
}

:deep(.p-inputtext:hover, .p-inputnumber-input:hover, .p-textarea:hover) {
  transform: translateY(-0.5px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Tag Animations */
:deep(.p-tag) {
  animation: scaleIn 0.3s ease-out;
  transition: all 0.2s ease;
}

:deep(.p-tag:hover) {
  transform: scale(1.05);
}

/* Progress Bar Animations */
:deep(.p-progressbar-value) {
  transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

:deep(.p-progressbar-value::after) {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  animation: shimmer 2s infinite;
}

/* Card Hover Effects */
:deep(.hover-lift) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.hover-lift:hover) {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
}

/* Staggered Animation Classes */
.stagger-1 { animation-delay: 0.1s; }
.stagger-2 { animation-delay: 0.2s; }
.stagger-3 { animation-delay: 0.3s; }
.stagger-4 { animation-delay: 0.4s; }
.stagger-5 { animation-delay: 0.5s; }

/* Loading Animation */
:deep(.loading-spinner) {
  animation: spin 1s linear infinite;
}

/* Enhanced Table Row Animations */
:deep(.p-datatable-tbody > tr) {
  animation: slideInFromLeft 0.3s ease-out;
}

:deep(.p-datatable-tbody > tr:nth-child(odd)) {
  animation-delay: 0.05s;
}

:deep(.p-datatable-tbody > tr:nth-child(even)) {
  animation-delay: 0.1s;
}

/* Icon Animations */
@keyframes iconBounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-6px);
  }
  60% {
    transform: translateY(-3px);
  }
}

.icon-bounce {
  animation: iconBounce 2s infinite;
}

/* Pulse Animation for Important Elements */
@keyframes gentlePulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.9;
    transform: scale(1.02);
  }
}

.pulse-gentle {
  animation: gentlePulse 3s ease-in-out infinite;
}

/* Floating Animation */
.float-animation {
  animation: float 3s ease-in-out infinite;
}

/* Gradient Animation */
.gradient-animate {
  background-size: 200% 200%;
  animation: gradientShift 4s ease infinite;
}

/* Enhanced Focus States */
:deep(.focus-ring:focus) {
  outline: none;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5);
  border-color: #6366f1;
}

/* Micro-interactions for Form Elements */
:deep(.form-group) {
  position: relative;
  margin-bottom: 1rem;
}

:deep(.form-group label) {
  transition: all 0.2s ease;
  cursor: pointer;
}

:deep(.form-group input:focus + label, .form-group input:not(:placeholder-shown) + label) {
  color: #6366f1;
  font-weight: 600;
  transform: translateY(-2px);
}

/* Enhanced Checkbox and Radio Animations */
:deep(.p-checkbox .p-checkbox-box, .p-radiobutton .p-radiobutton-box) {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-checkbox.p-checkbox-checked .p-checkbox-box, .p-radiobutton.p-radiobutton-checked .p-radiobutton-box) {
  animation: bounceIn 0.4s ease-out;
}

/* Tooltip Enhancements */
:deep(.p-tooltip) {
  animation: scaleIn 0.2s ease-out;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
}

/* Enhanced Scrollbar */
:deep(.custom-scrollbar) {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f8fafc;
}

:deep(.custom-scrollbar::-webkit-scrollbar) {
  width: 8px;
  height: 8px;
}

:deep(.custom-scrollbar::-webkit-scrollbar-track) {
  background: #f1f5f9;
  border-radius: 4px;
}

:deep(.custom-scrollbar::-webkit-scrollbar-thumb) {
  background: #cbd5e1;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

:deep(.custom-scrollbar::-webkit-scrollbar-thumb:hover) {
  background: #94a3b8;
}

.tw-card-enter {
  animation: fadeInUp 0.4s ease-out;
}

/* Enhanced Button Hover Effects */
:deep(.p-button:hover) {
  transform: scale(1.05);
  transition: transform 0.2s ease;
}

:deep(.p-button.p-button-primary:hover) {
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

:deep(.p-button.p-button-success:hover) {
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
}

/* Loading State Enhancements */
:deep(.p-datatable .p-datatable-loading-overlay) {
  background-color: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(4px);
}

/* Enhanced Empty State */
:deep(.p-datatable .p-datatable-emptymessage) {
  color: #6b7280;
  padding-top: 3rem;
  padding-bottom: 3rem;
}

:deep(.p-datatable .p-datatable-emptymessage td) {
  border: 0;
}
</style>