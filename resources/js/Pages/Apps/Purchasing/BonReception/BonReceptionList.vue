<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-box tw-text-white tw-text-2xl"></i>
              </div>
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-bg-green-500 tw-w-4 tw-h-4 tw-rounded-full tw-border-2 tw-border-white tw-shadow-sm"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mb-1">Goods Receipts</h1>
              <p class="tw-text-slate-600 tw-text-sm">Manage incoming order receptions and track deliveries</p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-blue-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-box tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">Total Receipts</p>
                  <p class="tw-text-xl tw-font-bold tw-text-blue-900">{{ stats.total || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-amber-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-clock tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-amber-700 tw-uppercase tw-tracking-wide">Pending</p>
                  <p class="tw-text-xl tw-font-bold tw-text-amber-900">{{ stats.pending || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-green-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-check-circle tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-green-700 tw-uppercase tw-tracking-wide">Completed</p>
                  <p class="tw-text-xl tw-font-bold tw-text-green-900">{{ stats.completed || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-violet-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-purple-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-purple-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-plus-circle tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-purple-700 tw-uppercase tw-tracking-wide">Surplus Items</p>
                  <p class="tw-text-xl tw-font-bold tw-text-purple-900">{{ stats.surplus_items || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-red-50 tw-to-rose-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-red-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-red-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-reply tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-red-700 tw-uppercase tw-tracking-wide">With Returns</p>
                  <p class="tw-text-xl tw-font-bold tw-text-red-900">{{ stats.with_returns || 0 }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Enhanced Action Toolbar -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-flex-1">
            <!-- Search with Enhanced Design -->
            <div class="tw-relative tw-flex-1 tw-min-w-[250px] tw-max-w-[400px]">
              <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-3 tw-flex tw-items-center tw-pointer-events-none">
                <i class="pi pi-search tw-text-slate-400 tw-text-sm"></i>
              </div>
              <InputText
                v-model="filters.search"
                placeholder="Search receipts..."
                @keyup.enter="applyFilters"
                class="tw-pl-10 tw-pr-4 tw-py-3 tw-border tw-border-slate-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-blue-500 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md"
              />
            </div>

            <!-- Filter Dropdowns -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                @change="applyFilters"
                class="tw-min-w-[140px] tw-border tw-border-slate-300 tw-rounded-lg tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                showClear
              />

              <Dropdown
                v-model="filters.supplier_id"
                :options="suppliers"
                optionLabel="company_name"
                optionValue="id"
                placeholder="All Suppliers"
                @change="applyFilters"
                class="tw-min-w-[160px] tw-border tw-border-slate-300 tw-rounded-lg tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                filter
                showClear
              />

              <Calendar
                v-model="filters.date_from"
                placeholder="Receipt Date"
                @date-select="applyFilters"
                showIcon
                class="tw-min-w-[140px] tw-border tw-border-slate-300 tw-rounded-lg tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                showClear
              />
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button
              icon="pi pi-refresh"
              label="Refresh"
              @click="refreshData"
              outlined
              class="tw-border tw-border-slate-300 tw-rounded-lg hover:tw-bg-slate-50 tw-transition-all tw-duration-200"
            />
            <Button
              icon="pi pi-plus"
              label="Create Receipt"
              @click="navigateToCreate"
              class="enhanced-primary-btn tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            />
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="tw-mt-4 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
          <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Active filters:</span>
          <Tag v-if="filters.search"
               :value="`Search: ${filters.search}`"
               severity="info"
               class="tw-cursor-pointer"
               @click="clearSearch" />
          <Tag v-if="filters.status && filters.status !== 'all'"
               :value="`Status: ${getStatusLabel(filters.status)}`"
               severity="info"
               class="tw-cursor-pointer"
               @click="filters.status = 'all'; applyFilters()" />
          <Tag v-if="filters.supplier_id"
               :value="`Supplier: ${getSupplierName(filters.supplier_id)}`"
               severity="info"
               class="tw-cursor-pointer"
               @click="filters.supplier_id = null; applyFilters()" />
          <Tag v-if="filters.date_from"
               :value="`Date: ${formatDate(filters.date_from)}`"
               severity="info"
               class="tw-cursor-pointer"
               @click="filters.date_from = null; applyFilters()" />
          <Button @click="clearAllFilters"
                  text
                  size="small"
                  class="tw-text-slate-500 hover:tw-text-slate-700">
            Clear all
          </Button>
        </div>
      </div>

      <!-- Enhanced Data Table with Card View Toggle -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <!-- View Toggle and Table Controls -->
        <div class="tw-p-4 tw-border-b tw-border-slate-200/60 tw-bg-slate-50/50">
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <span class="tw-text-sm tw-font-medium tw-text-slate-700">View:</span>
              <div class="tw-flex tw-bg-white tw-rounded-lg tw-border tw-border-slate-200 tw-p-1">
                <Button
                  :outlined="viewMode !== 'table'"
                  size="small"
                  @click="viewMode = 'table'"
                  class="tw-rounded-md"
                >
                  <i class="pi pi-table tw-mr-2"></i>
                  Table
                </Button>
                <Button
                  :outlined="viewMode !== 'cards'"
                  size="small"
                  @click="viewMode = 'cards'"
                  class="tw-rounded-md"
                >
                  <i class="pi pi-th-large tw-mr-2"></i>
                  Cards
                </Button>
              </div>
            </div>

            <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-slate-600">
              <i class="pi pi-info-circle"></i>
              <span>{{ receptions.total || 0 }} total receipts</span>
            </div>
          </div>
        </div>

        <!-- Table View -->
        <DataTable
          v-if="viewMode === 'table'"
          :value="receptions.data"
          :paginator="true"
          :rows="15"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} receipts"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          dataKey="id"
          :loading="loading"
          selectionMode="multiple"
          responsiveLayout="scroll"
          class="medical-table tw-border-none"
          :class="{ 'tw-hidden': loading }"
          @row-click="onRowClick"
          :rowHover="true"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Receipt Code with Enhanced Design -->
          <Column field="bonReceptionCode" header="Receipt Code" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-bg-blue-100 tw-text-blue-800 tw-px-2 tw-py-1 tw-rounded-md tw-text-xs tw-font-mono tw-font-medium">
                  {{ slotProps.data.bonReceptionCode }}
                </div>
              </div>
            </template>
          </Column>

          <!-- Purchase Order with Enhanced Design -->
          <Column field="bon_commend.bonCommendCode" header="Purchase Order" :sortable="true">
            <template #body="slotProps">
              <div v-if="slotProps.data.bon_commend" class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-file tw-text-blue-500"></i>
                <span class="tw-font-mono tw-text-sm tw-text-blue-600 tw-font-medium">
                  {{ slotProps.data.bon_commend.bonCommendCode }}
                </span>
              </div>
              <span v-else class="tw-text-slate-400 tw-text-sm tw-italic">No Order</span>
            </template>
          </Column>

          <!-- Supplier with Avatar -->
          <Column field="fournisseur.company_name" header="Supplier" :sortable="true">
            <template #body="slotProps">
              <div v-if="slotProps.data.fournisseur" class="tw-flex tw-items-center tw-gap-3">
                <Avatar
                  :label="getInitials(slotProps.data.fournisseur.company_name)"
                  class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-text-xs tw-font-bold"
                  size="normal"
                  shape="circle"
                />
                <div>
                  <span class="tw-font-medium tw-text-slate-900">{{ slotProps.data.fournisseur.company_name }}</span>
                  <p class="tw-text-xs tw-text-slate-500">{{ slotProps.data.fournisseur.contact_person || 'No contact' }}</p>
                </div>
              </div>
              <span v-else class="tw-text-slate-400 tw-text-sm tw-italic">Unassigned</span>
            </template>
          </Column>

          <!-- Items Count with Progress -->
          <Column header="Items" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-text-center">
                  <span class="tw-text-lg tw-font-bold tw-text-slate-900">{{ slotProps.data.items?.length || 0 }}</span>
                  <p class="tw-text-xs tw-text-slate-500">items</p>
                </div>
                <div v-if="getSurplusCount(slotProps.data) > 0" class="tw-flex tw-flex-col tw-items-center">
                  <Tag
                    :value="`+${getSurplusCount(slotProps.data)}`"
                    severity="warning"
                    class="tw-text-xs tw-mb-1"
                  />
                  <span class="tw-text-xs tw-text-amber-600">surplus</span>
                </div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Status with Progress -->
          <Column field="status" header="Status" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Tag
                  :value="getStatusLabel(slotProps.data.status)"
                  :severity="getStatusSeverity(slotProps.data.status)"
                  class="tw-font-medium"
                />
                <div v-if="slotProps.data.status === 'pending'" class="tw-w-2 tw-h-2 tw-bg-amber-400 tw-rounded-full tw-animate-pulse"></div>
                <div v-if="slotProps.data.status === 'completed'" class="tw-w-2 tw-h-2 tw-bg-green-400 tw-rounded-full"></div>
              </div>
            </template>
          </Column>

          <!-- Date with Relative Time -->
          <Column field="date_reception" header="Receipt Date" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-flex-col">
                <span class="tw-text-sm tw-font-medium tw-text-slate-900">{{ formatDate(slotProps.data.date_reception) }}</span>
                <span class="tw-text-xs tw-text-slate-500">{{ getRelativeTime(slotProps.data.date_reception) }}</span>
              </div>
            </template>
          </Column>

          <!-- Return Status -->
          <Column header="Return Status" :sortable="true">
            <template #body="slotProps">
              <div v-if="slotProps.data.bon_retour_id" class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-reply tw-text-orange-500"></i>
                <span class="tw-text-sm tw-text-orange-700 tw-font-medium">Return Created</span>
              </div>
              <span v-else class="tw-text-slate-400 tw-text-sm">-</span>
            </template>
          </Column>

          <!-- Enhanced Actions -->
          <Column header="Actions" :exportable="false" class="tw-w-64">
            <template #body="slotProps">
              <!-- All Action Buttons Visible -->
              <div class="tw-flex tw-items-center tw-gap-1 tw-flex-wrap">
                <!-- View - Always visible -->
                <Button
                  icon="pi pi-eye"
                  size="small"
                  text
                  severity="info"
                  @click.stop="viewReception(slotProps.data)"
                  v-tooltip.top="'View details'"
                  class="hover:tw-bg-blue-50 tw-rounded-lg"
                />

                <!-- Edit - Only for pending -->
                <Button
                  v-if="slotProps.data.status === 'pending'"
                  icon="pi pi-pencil"
                  size="small"
                  text
                  severity="warning"
                  @click.stop="editReception(slotProps.data)"
                  v-tooltip.top="'Edit reception'"
                  class="hover:tw-bg-amber-50 tw-rounded-lg"
                />

                <!-- Confirm Receipt - Only for pending -->
                <Button
                  v-if="slotProps.data.status === 'pending'"
                  icon="pi pi-check-circle"
                  size="small"
                  text
                  severity="success"
                  @click.stop="confirmReception(slotProps.data)"
                  v-tooltip.top="'Confirm receipt'"
                  class="hover:tw-bg-green-50 tw-rounded-lg"
                />

                <!-- Create Purchase Order - Only for pending and no order -->
                <Button
                  v-if="slotProps.data.status === 'pending' && !slotProps.data.purchase_order_id"
                  icon="pi pi-plus"
                  size="small"
                  text
                  severity="info"
                  @click.stop="createPurchaseOrderFromReceipt(slotProps.data)"
                  v-tooltip.top="'Create purchase order'"
                  class="hover:tw-bg-blue-50 tw-rounded-lg"
                />

                <!-- Delete - Only for pending -->
                <Button
                  v-if="slotProps.data.status === 'pending'"
                  icon="pi pi-trash"
                  size="small"
                  text
                  severity="danger"
                  @click.stop="confirmDelete(slotProps.data)"
                  v-tooltip.top="'Delete reception'"
                  class="hover:tw-bg-red-50 tw-rounded-lg"
                />

                <!-- Download PDF - For completed/canceled/rejected -->
                <Button
                  v-if="slotProps.data.status !== 'pending'"
                  icon="pi pi-download"
                  size="small"
                  text
                  severity="secondary"
                  @click.stop="downloadPDF(slotProps.data)"
                  v-tooltip.top="'Download PDF'"
                  class="hover:tw-bg-slate-50 tw-rounded-lg"
                />

                <!-- Print - For completed/canceled/rejected -->
                <Button
                  v-if="slotProps.data.status !== 'pending'"
                  icon="pi pi-print"
                  size="small"
                  text
                  severity="secondary"
                  @click.stop="printReception(slotProps.data)"
                  v-tooltip.top="'Print reception'"
                  class="hover:tw-bg-slate-50 tw-rounded-lg"
                />

                <!-- View Returns - Only if has returns -->
                <Button
                  v-if="slotProps.data.has_returns"
                  icon="pi pi-reply"
                  size="small"
                  text
                  severity="info"
                  @click.stop="viewReturns(slotProps.data)"
                  v-tooltip.top="'View returns'"
                  class="hover:tw-bg-orange-50 tw-rounded-lg"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Card View -->
        <div v-else class="tw-p-6">
          <div v-if="receptions.data.length === 0 && !loading" class="tw-text-center tw-py-12">
            <div class="tw-bg-slate-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
              <i class="pi pi-inbox tw-text-slate-400 tw-text-2xl"></i>
            </div>
            <h3 class="tw-text-lg tw-font-medium tw-text-slate-900 tw-mb-2">No receipts found</h3>
            <p class="tw-text-slate-500">Try adjusting your filters or create a new receipt.</p>
          </div>

          <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <Card
              v-for="reception in receptions.data"
              :key="reception.id"
              class="card-hover tw-cursor-pointer tw-transition-all tw-duration-200"
              @click="viewReception(reception)"
            >
              <template #content>
                <div class="tw-space-y-4">
                  <!-- Header -->
                  <div class="tw-flex tw-items-start tw-justify-between">
                    <div>
                      <h4 class="tw-font-mono tw-text-sm tw-font-bold tw-text-slate-900 tw-mb-1">
                        {{ reception.bonReceptionCode }}
                      </h4>
                      <p class="tw-text-xs tw-text-slate-500">{{ formatDate(reception.date_reception) }}</p>
                    </div>
                    <Tag
                      :value="getStatusLabel(reception.status)"
                      :severity="getStatusSeverity(reception.status)"
                      size="small"
                    />
                  </div>

                  <!-- Supplier -->
                  <div v-if="reception.fournisseur" class="tw-flex tw-items-center tw-gap-3">
                    <Avatar
                      :label="getInitials(reception.fournisseur.company_name)"
                      class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-text-white"
                      size="small"
                      shape="circle"
                    />
                    <div>
                      <p class="tw-text-sm tw-font-medium tw-text-slate-900">{{ reception.fournisseur.company_name }}</p>
                      <p class="tw-text-xs tw-text-slate-500">{{ reception.fournisseur.contact_person || 'No contact' }}</p>
                    </div>
                  </div>

                  <!-- Purchase Order -->
                  <div v-if="reception.bon_commend" class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-file tw-text-blue-500 tw-text-sm"></i>
                    <span class="tw-text-sm tw-text-blue-600">{{ reception.bon_commend.bonCommendCode }}</span>
                  </div>

                  <!-- Items Count -->
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-box tw-text-slate-400 tw-text-sm"></i>
                      <span class="tw-text-sm tw-text-slate-700">{{ reception.items?.length || 0 }} items</span>
                    </div>
                    <div v-if="getSurplusCount(reception) > 0" class="tw-flex tw-items-center tw-gap-1">
                      <Tag value="Surplus" severity="warning" size="small" />
                    </div>
                  </div>

                  <!-- Return Status -->
                  <div v-if="reception.bon_retour_id" class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-reply tw-text-orange-500 tw-text-sm"></i>
                    <span class="tw-text-xs tw-text-orange-700">Return Created</span>
                  </div>
                </div>
              </template>

              <template #footer>
                <div class="tw-flex tw-gap-2 tw-justify-end">
                  <Button
                    icon="pi pi-eye"
                    size="small"
                    text
                    severity="info"
                    @click.stop="viewReception(reception)"
                  />
                  <Button
                    v-if="reception.status === 'pending'"
                    icon="pi pi-check-circle"
                    size="small"
                    text
                    severity="success"
                    @click.stop="openConfirmDialog(reception)"
                  />
                </div>
              </template>
            </Card>
          </div>

          <!-- Card View Pagination -->
          <div class="tw-mt-6 tw-flex tw-justify-center">
            <Paginator
              :first="currentPage * itemsPerPage - itemsPerPage"
              :rows="itemsPerPage"
              :totalRecords="receptions.total"
              @page="onPage"
              :rowsPerPageOptions="[12, 24, 36]"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
          <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
            <ProgressSpinner class="tw-w-8 tw-h-8" />
            <p class="tw-text-slate-600">Loading receipts...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- View/Edit Dialog -->
    <Dialog
      :visible="showReceptionDialog"
      @update:visible="showReceptionDialog = $event"
      :header="dialogTitle"
      :modal="true"
      :maximizable="true"
      :style="{ width: '90vw', maxWidth: '1200px' }"
      :contentStyle="{ height: '80vh', overflow: 'auto' }"
      class="enhanced-dialog tw-p-0"
    >
      <BonReceptionDetails
        v-if="selectedReception"
        :bon-reception-id="selectedReception.id"
        :mode="dialogMode"
        @saved="onReceptionSaved"
        @cancelled="closeReceptionDialog"
      />
    </Dialog>

    <!-- Confirm Reception Dialog with Surplus Handling -->
    <BonReceptionConfirmDialog
      v-model="showConfirmDialog"
      :bon-reception-id="selectedReception?.id"
      @confirmed="onReceptionConfirmed"
    />

    <!-- Delete Confirmation Dialog -->
    <Dialog
      :visible="showDeleteDialog"
      @update:visible="showDeleteDialog = $event"
      modal
      appendTo="self"
      header="Confirm Deletion"
      class="enhanced-dialog tw-w-full tw-max-w-md"
    >
      <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
        <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-2xl"></i>
        <span>
          Are you sure you want to delete the receipt
          <strong>{{ selectedReception?.bonReceptionCode }}</strong>?
        </span>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-2">
          <Button label="Cancel" text @click="showDeleteDialog = false" />
          <Button
            label="Delete"
            severity="danger"
            @click="deleteReception"
            :loading="deleting"
          />
        </div>
      </template>
    </Dialog>

    <!-- Toast component -->
    <Toast />

    <!-- Floating Action Button -->
    <button
      @click="navigateToCreate"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Receipt'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import ProgressSpinner from 'primevue/progressspinner'
import Message from 'primevue/message'
import Card from 'primevue/card'
import Avatar from 'primevue/avatar'
import Paginator from 'primevue/paginator'
import Toast from 'primevue/toast'

// Custom Components
import BonReceptionDetails from '../BonReception/BonReceptionItem.vue'
import BonReceptionConfirmDialog from '../../../../Components/Apps/Purchasing/BonReception/BonReceptionConfirmDialog.vue'

const router = useRouter()
const toast = useToast()

// Reactive data
const loading = ref(true)
const deleting = ref(false)
const error = ref(null)

const receptions = ref({ data: [], total: 0 })
const stats = ref({})
const suppliers = ref([])

const showReceptionDialog = ref(false)
const showDeleteDialog = ref(false)
const showConfirmDialog = ref(false)
const selectedReception = ref(null)
const dialogMode = ref('view')

// View Mode
const viewMode = ref('table') // 'table' or 'cards'
const currentPage = ref(1)
const itemsPerPage = ref(15)

// Computed
const dialogTitle = computed(() => {
  if (dialogMode.value === 'create') return 'Create Reception'
  if (dialogMode.value === 'edit') return `Edit Reception ${selectedReception.value?.bonReceptionCode}`
  return `Reception ${selectedReception.value?.bonReceptionCode}`
})

const hasActiveFilters = computed(() => {
  return filters.value.status && filters.value.status !== 'all' ||
         filters.value.supplier_id ||
         filters.value.date_from ||
         filters.value.search
})

// Form data
const filters = ref({
  status: 'all',
  supplier_id: null,
  date_from: null,
  search: ''
})

// Options
const statusOptions = ref([
  { label: 'All Statuses', value: 'all' },
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Canceled', value: 'canceled' },
  { label: 'Rejected', value: 'rejected' }
])

// Methods
const fetchReceptions = async (page = 1) => {
  try {
    loading.value = true
    error.value = null
    
    const params = {
      page,
      status: filters.value.status,
      fournisseur_id: filters.value.supplier_id,
      date_from: filters.value.date_from,
      search: filters.value.search,
    }
    
    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === '' || params[key] === 'all') {
        delete params[key]
      }
    })
    
    const response = await axios.get('/api/bon-receptions', { params })
    
    if (response.data.status === 'success') {
      receptions.value = response.data.data
      
      // Calculate additional stats
      if (receptions.value.data) {
        stats.value.with_returns = receptions.value.data.filter(r => r.bon_retour_id).length
      }
    } else {
      throw new Error(response.data.message || 'Failed to fetch receptions')
    }
  } catch (err) {
    console.error('Error fetching receptions:', err)
    error.value = err.message || 'Failed to fetch receptions'
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/bon-receptions/meta/stats')
    
    if (response.data.status === 'success') {
      stats.value = { ...stats.value, ...response.data.data }
    }
  } catch (err) {
    console.error('Error fetching stats:', err)
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    
    if (response.data.status === 'success') {
      suppliers.value = response.data.data
    }
  } catch (err) {
    console.error('Error fetching suppliers:', err)
  }
}

const navigateToCreate = () => {
  router.push('/purchasing/bon-receptions/create')
}

const viewReception = (reception) => {
  selectedReception.value = reception
  dialogMode.value = 'view'
  showReceptionDialog.value = true
  console.log('Dialog should open now. showReceptionDialog:', showReceptionDialog.value)
}

const editReception = (id) => {
  router.push(`/purchasing/bon-receptions/${id}`)
}

const openConfirmDialog = (reception) => {
  selectedReception.value = reception
  showConfirmDialog.value = true
}

const onReceptionConfirmed = (data) => {
  showConfirmDialog.value = false
  
  if (data.bonRetour) {
    toast.add({
      severity: 'success',
      summary: 'Reception Confirmed',
      detail: `Reception confirmed and return note ${data.bonRetour.bon_retour_code} created for surplus items`,
      life: 5000
    })
  } else {
    toast.add({
      severity: 'success',
      summary: 'Reception Confirmed',
      detail: 'Reception confirmed successfully'
    })
  }
  
  fetchReceptions()
  fetchStats()
}

const viewReturn = (reception) => {
  if (reception.bon_retour_id) {
    router.push(`/purchasing/bon-retours/${reception.bon_retour_id}`)
  }
}

const closeReceptionDialog = () => {
  showReceptionDialog.value = false
  selectedReception.value = null
}

const onReceptionSaved = () => {
  closeReceptionDialog()
  fetchReceptions()
  fetchStats()
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Reception saved successfully'
  })
}

const onRowClick = (event) => {
  viewReception(event.data)
}

const confirmDelete = (reception) => {
  selectedReception.value = reception
  showDeleteDialog.value = true
}

const deleteReception = async () => {
  try {
    deleting.value = true
    
    const response = await axios.delete(`/api/bon-receptions/${selectedReception.value.id}`)
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Receipt deleted successfully'
      })
      
      showDeleteDialog.value = false
      selectedReception.value = null
      await Promise.all([fetchReceptions(), fetchStats()])
    } else {
      throw new Error(response.data.message || 'Failed to delete reception')
    }
  } catch (err) {
    console.error('Error deleting reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || err.message || 'Could not delete reception'
    })
  } finally {
    deleting.value = false
  }
}

const createPurchaseOrderFromReceipt = async (receipt) => {
  if (!receipt || !receipt.items || receipt.items.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Items',
      detail: 'This receipt has no items to create a purchase order from'
    })
    return
  }

  if (!receipt.fournisseur_id) {
    toast.add({
      severity: 'warn',
      summary: 'Supplier Required',
      detail: 'This receipt must have a supplier to create a purchase order'
    })
    return
  }

  try {
    loading.value = true

    const bonCommendData = {
      fournisseur_id: receipt.fournisseur_id,
      order_date: new Date().toISOString().split('T')[0],
      status: 'confirmed',
      approval_status: 'approved',
      notes: `Created from receipt ${receipt.bonReceptionCode}. ${receipt.observation || ''}`,
      items: receipt.items.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity_received,
        price: item.unit_price || 0,
        unit: item.unit || 'piece',
        notes: item.notes || ''
      }))
    }

    const response = await axios.post('/api/bon-commends', bonCommendData)

    if (response.data.status === 'success') {
      const newBonCommend = response.data.data

      // Update the receipt to link it to the new bon commend
      await axios.put(`/api/bon-receptions/${receipt.id}`, {
        bon_commend_id: newBonCommend.id
      })

      toast.add({
        severity: 'success',
        summary: 'Purchase Order Created',
        detail: `Purchase order ${newBonCommend.bonCommendCode} created and linked successfully`
      })

      await fetchReceptions()
    } else {
      throw new Error(response.data.message || 'Failed to create purchase order')
    }
  } catch (err) {
    console.error('Error creating purchase order:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || err.message || 'Could not create purchase order'
    })
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  fetchReceptions(event.page + 1)
}

// New Enhanced Methods
const applyFilters = async () => {
  currentPage.value = 1
  await fetchReceptions(1)
}

const refreshData = async () => {
  await Promise.all([fetchReceptions(), fetchStats(), fetchSuppliers()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const clearSearch = () => {
  filters.value.search = ''
  applyFilters()
}

const clearAllFilters = () => {
  filters.value.status = 'all'
  filters.value.supplier_id = null
  filters.value.date_from = null
  filters.value.search = ''
  applyFilters()
}

const getSupplierName = (supplierId) => {
  const supplier = suppliers.value.find(s => s.id === supplierId)
  return supplier ? supplier.company_name : 'Unknown'
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().slice(0, 2)
}

const getRelativeTime = (date) => {
  if (!date) return ''
  const now = new Date()
  const past = new Date(date)
  const diffInSeconds = Math.floor((now - past) / 1000)

  if (diffInSeconds < 60) return 'Just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
  return formatDateOnly(date)
}

// Utility functions
const getSurplusCount = (reception) => {
  if (!reception.items) return 0
  return reception.items.filter(item => item.quantity_surplus > 0).length
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'warn',
    completed: 'success',
    canceled: 'secondary',
    rejected: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    completed: 'Completed',
    canceled: 'Canceled',
    rejected: 'Rejected'
  }
  return labels[status] || status
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

const formatDateOnly = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Download and Print Functions
const downloadPDF = async (reception) => {
  try {
    const response = await axios.get(`/api/bon-receptions/${reception.id}/download`, {
      responseType: 'blob'
    })

    // Create a blob link to the file
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${reception.bonReceptionCode || 'bon-reception'}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF downloaded successfully',
      life: 2000
    })
  } catch (err) {
    console.error('Error downloading PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to download PDF'
    })
  }
}

const printReception = async (reception) => {
  try {
    // Open PDF in new window for printing
    const url = `/api/bon-receptions/${reception.id}/download`
    const printWindow = window.open(url, '_blank')
    
    if (!printWindow) {
      throw new Error('Pop-up blocked. Please allow pop-ups for this site.')
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF opened in new tab. Use browser print (Ctrl+P)',
      life: 3000
    })
  } catch (err) {
    console.error('Error printing reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Failed to print reception'
    })
  }
}

// Confirm Reception Function
const confirmReception = async (reception) => {
  if (!confirm(`Are you sure you want to confirm reception ${reception.bonReceptionCode}?`)) {
    return
  }

  try {
    loading.value = true
    const response = await axios.put(`/api/bon-receptions/${reception.id}/confirm`)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reception confirmed successfully'
      })
      await fetchReceptions()
    } else {
      throw new Error(response.data.message || 'Failed to confirm reception')
    }
  } catch (err) {
    console.error('Error confirming reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || err.message || 'Could not confirm reception'
    })
  } finally {
    loading.value = false
  }
}

// View Returns Function
const viewReturns = (reception) => {
  // Navigate to returns page or open returns dialog
  // This would typically navigate to a returns management page
  toast.add({
    severity: 'info',
    summary: 'View Returns',
    detail: `Viewing returns for reception ${reception.bonReceptionCode}`,
    life: 3000
  })
  // TODO: Implement navigation to returns page or open returns dialog
}

// Action Menu Generator
const getActionMenu = (reception) => {
  const menuItems = []
  console.log('Status Check:', reception.status, 'Type:', typeof reception.status);
  
  if (reception.status === 'pending') {
    // For pending receptions: Edit, Confirm, Create PO (if no order), Delete
    menuItems.push({
      label: 'Edit',
      icon: 'pi pi-pencil',
      command: () => editReception(reception)
    })

    menuItems.push({
      label: 'Confirm Receipt',
      icon: 'pi pi-check',
      command: () => confirmReception(reception)
    })

    // Only show Create Purchase Order if no order exists
    if (!reception.purchase_order_id) {
      menuItems.push({
        label: 'Create Purchase Order',
        icon: 'pi pi-plus',
        command: () => createPurchaseOrderFromReceipt(reception)
      })
    }

    menuItems.push({
      label: 'Delete',
      icon: 'pi pi-trash',
      command: () => deleteReception(reception),
      class: 'text-red-600'
    })
  } else {
    // For ALL non-pending receptions: Always show Download PDF and Print
    menuItems.push({
      label: 'Download PDF',
      icon: 'pi pi-download',
      command: () => downloadPDF(reception)
    })

    menuItems.push({
      label: 'Print',
      icon: 'pi pi-print',
      command: () => printReception(reception)
    })

    // Only show View Return if there are returns
    if (reception.has_returns) {
      menuItems.push({
        label: 'View Returns',
        icon: 'pi pi-reply',
        command: () => viewReturns(reception)
      })
    }
  }

  console.log('Menu Items:', menuItems.length, menuItems);
  return menuItems
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchReceptions(),
    fetchStats(),
    fetchSuppliers()
  ])
})
</script>

<style scoped>
/* Enhanced Medical Table Styles */
:deep(.medical-table .p-datatable-header) {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

:deep(.medical-table .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  color: #374151;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 0.75rem;
}

:deep(.medical-table .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s ease;
}

:deep(.medical-table .p-datatable-tbody > tr:hover) {
  background-color: rgba(59, 130, 246, 0.05);
}

:deep(.medical-table .p-datatable-tbody > tr.p-highlight) {
  background-color: #eff6ff;
}

:deep(.p-button-sm) {
  font-size: 0.875rem;
  padding: 0.375rem 0.625rem;
}

:deep(.p-tag) {
  font-weight: 600;
}

:deep(.p-radiobutton .p-radiobutton-box) {
  border: 1px solid #cbd5e1;
}

:deep(.p-radiobutton.p-radiobutton-checked .p-radiobutton-box) {
  border: 1px solid #3b82f6;
  background-color: #3b82f6;
}

:deep(.p-splitbutton .p-splitbutton-defaultbutton) {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

:deep(.p-splitbutton .p-splitbutton-menubutton) {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  padding: 0 0.5rem;
}

/* Enhanced Animations and Effects */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-10px); }
  to { opacity: 1; transform: translateX(0); }
}

.tw-animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.tw-animate-slide-in {
  animation: slideIn 0.2s ease-out;
}

/* Custom scrollbar */
:deep(.p-datatable-wrapper::-webkit-scrollbar) {
  width: 6px;
  height: 6px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-track) {
  background: #f1f5f9;
  border-radius: 3px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb) {
  background: #cbd5e1;
  border-radius: 3px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb:hover) {
  background: #94a3b8;
}

/* Enhanced card hover effects */
.card-hover {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Loading skeleton */
.skeleton {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Floating Action Button */
.fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.39);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  transition: all 0.3s ease;
  z-index: 1000;
}

.fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

.fab:active {
  transform: scale(0.95);
}

/* Enhanced status indicators */
.status-indicator {
  position: relative;
  display: inline-block;
}

.status-indicator::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: inherit;
  background: inherit;
  opacity: 0.2;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 0.2; }
  50% { transform: scale(1.05); opacity: 0.3; }
  100% { transform: scale(1); opacity: 0.2; }
}

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
  box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
}

:deep(.enhanced-checkbox.p-checkbox-checked .p-checkbox-box) {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border-color: #6366f1;
}

:deep(.enhanced-radio .p-radiobutton-box) {
  border: 1px solid #d1d5db;
  border-radius: 9999px;
  transition: all 0.2s ease;
}

:deep(.enhanced-radio .p-radiobutton-box:hover) {
  border-color: #a78bfa;
  box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
}

:deep(.enhanced-radio.p-radiobutton-checked .p-radiobutton-box) {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border-color: #6366f1;
}

/* Enhanced Button Styles */
:deep(.enhanced-primary-btn) {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border: 0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.1), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
  transition: all 0.2s ease;
}

:deep(.enhanced-primary-btn:hover) {
  background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
  box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.1), 0 4px 6px -2px rgba(99, 102, 241, 0.05);
  transform: translateY(-1px);
}

:deep(.enhanced-primary-btn:active) {
  transform: translateY(0);
  box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.1), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
}

:deep(.enhanced-success-btn) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: 0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06);
  transition: all 0.2s ease;
}

:deep(.enhanced-success-btn:hover) {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1), 0 4px 6px -2px rgba(16, 185, 129, 0.05);
  transform: translateY(-1px);
}

:deep(.enhanced-success-btn:active) {
  transform: translateY(0);
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06);
}

:deep(.enhanced-cancel-btn) {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  border: 0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(107, 114, 128, 0.1), 0 2px 4px -1px rgba(107, 114, 128, 0.06);
  transition: all 0.2s ease;
}

:deep(.enhanced-cancel-btn:hover) {
  background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
  box-shadow: 0 10px 15px -3px rgba(107, 114, 128, 0.1), 0 4px 6px -2px rgba(107, 114, 128, 0.05);
  transform: translateY(-1px);
}

/* Dialog Animations */
@keyframes dialogSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

:deep(.enhanced-dialog) {
  animation: dialogSlideIn 0.3s ease-out;
}

/* Form Field Focus Animations */
@keyframes inputFocus {
  0% {
    box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.1);
  }
  100% {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  }
}

:deep(.enhanced-input:focus, .enhanced-dropdown:focus, .enhanced-textarea:focus) {
  animation: inputFocus 0.2s ease-out;
}

/* Enhanced Radio Button Cards */
:deep(.enhanced-radio-card) {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  cursor: pointer;
}

:deep(.enhanced-radio-card:hover) {
  border-color: #a78bfa;
  box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
}

:deep(.enhanced-radio-card.p-radiobutton-checked) {
  border-color: #6366f1;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.05) 100%);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Enhanced Progress Bar Global */
:deep(.p-progressbar) {
  border: 0;
  border-radius: 9999px;
  background: linear-gradient(90deg, #f3f4f6 0%, #e5e7eb 100%);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

:deep(.p-progressbar-value) {
  background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
}

/* Enhanced File Upload */
:deep(.p-fileupload) {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

:deep(.p-fileupload:hover) {
  border-color: #a78bfa;
  box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
}

/* Enhanced Animations & Transitions */
@keyframes slideInFromTop {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInFromBottom {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInFromLeft {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInFromRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
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
    opacity: 0.5;
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
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  }
  100% {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
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
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
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
  animation: fadeInUp 0.6s ease-out;
}

/* Card Animations */
:deep(.p-card) {
  animation: scaleIn 0.4s ease-out;
}

:deep(.p-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Button Micro-interactions */
:deep(.p-button) {
  position: relative;
  overflow: hidden;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-button::before) {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  transition: width 0.6s, height 0.6s;
  transform: translate(-50%, -50%);
}

:deep(.p-button:active::before) {
  width: 300px;
  height: 300px;
}

:deep(.p-button.p-button-primary) {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border: 0;
}

:deep(.p-button.p-button-primary:hover) {
  background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
  transform: translateY(-1px);
  box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.1), 0 4px 6px -2px rgba(99, 102, 241, 0.05);
}

:deep(.p-button.p-button-success) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: 0;
}

:deep(.p-button.p-button-success:hover) {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  transform: translateY(-1px);
  box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1), 0 4px 6px -2px rgba(16, 185, 129, 0.05);
}

:deep(.p-button.p-button-warning) {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  border: 0;
}

:deep(.p-button.p-button-warning:hover) {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  transform: translateY(-1px);
  box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.1), 0 4px 6px -2px rgba(245, 158, 11, 0.05);
}

/* Input Field Animations */
:deep(.p-inputtext, .p-inputnumber-input, .p-textarea) {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-inputtext:focus, .p-inputnumber-input:focus, .p-textarea:focus) {
  transform: scale(1.01);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.p-inputtext:hover, .p-inputnumber-input:hover, .p-textarea:hover) {
  border-color: #a78bfa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Tag Animations */
:deep(.p-tag) {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-tag:hover) {
  transform: scale(1.05);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Progress Bar Animations */
:deep(.p-progressbar-value) {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
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
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
  animation: fadeInUp 0.4s ease-out;
}

:deep(.p-datatable-tbody > tr:nth-child(odd)) {
  animation-delay: 0.1s;
}

:deep(.p-datatable-tbody > tr:nth-child(even)) {
  animation-delay: 0.2s;
}

/* Icon Animations */
@keyframes iconBounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
  60% {
    transform: translateY(-5px);
  }
}

.icon-bounce {
  animation: iconBounce 2s infinite;
}

/* Pulse Animation for Important Elements */
@keyframes gentlePulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

.pulse-gentle {
  animation: gentlePulse 2s infinite;
}

/* Floating Animation */
.float-animation {
  animation: float 3s ease-in-out infinite;
}

/* Gradient Animation */
.gradient-animate {
  background-size: 200% 200%;
  animation: gradientShift 3s ease infinite;
}

/* Enhanced Focus States */
:deep(.focus-ring:focus) {
  outline: 0;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Micro-interactions for Form Elements */
:deep(.form-group) {
  position: relative;
  margin-bottom: 1rem;
}

:deep(.form-group label) {
  position: absolute;
  top: 0.5rem;
  left: 0.75rem;
  color: #6b7280;
  transition: all 0.2s ease;
  pointer-events: none;
}

:deep(.form-group input:focus + label, .form-group input:not(:placeholder-shown) + label) {
  top: -0.5rem;
  left: 0.5rem;
  font-size: 0.75rem;
  color: #6366f1;
  background: white;
  padding: 0 0.25rem;
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
  border-radius: 0.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Enhanced Scrollbar */
:deep(.custom-scrollbar) {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f8fafc;
}

:deep(.custom-scrollbar::-webkit-scrollbar) {
  width: 6px;
  height: 6px;
}

:deep(.custom-scrollbar::-webkit-scrollbar-track) {
  background: #f3f4f6;
  border-radius: 0.5rem;
}

:deep(.custom-scrollbar::-webkit-scrollbar-thumb) {
  background: #cbd5e1;
  border-radius: 0.5rem;
  transition: background-color 0.2s ease;
}

:deep(.custom-scrollbar::-webkit-scrollbar-thumb:hover) {
  background: #94a3b8;
}

.tw-card-enter {
  animation: scaleIn 0.4s ease-out;
}

/* Enhanced Button Hover Effects */
:deep(.p-button:hover) {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

:deep(.p-button.p-button-primary:hover) {
  background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
}

:deep(.p-button.p-button-success:hover) {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

/* Loading State Enhancements */
:deep(.p-datatable .p-datatable-loading-overlay) {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(4px);
}

/* Enhanced Empty State */
:deep(.p-datatable .p-datatable-emptymessage) {
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-radius: 0.5rem;
  margin: 1rem;
}

:deep(.p-datatable .p-datatable-emptymessage td) {
  border: 0;
  padding: 2rem;
}
</style>
