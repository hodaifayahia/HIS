<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-green-500 tw-to-emerald-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-box tw-text-white tw-text-2xl"></i>
              </div>
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-bg-green-500 tw-w-4 tw-h-4 tw-rounded-full tw-border-2 tw-border-white tw-shadow-sm"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mb-1">Stock Entries</h1>
              <p class="tw-text-slate-600 tw-text-sm">Manage stock entries from received products</p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-blue-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-file tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">Total Entries</p>
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
                  <p class="tw-text-xs tw-font-medium tw-text-amber-700 tw-uppercase tw-tracking-wide">Draft</p>
                  <p class="tw-text-xl tw-font-bold tw-text-amber-900">{{ stats.draft || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-green-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-check-circle tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-green-700 tw-uppercase tw-tracking-wide">Validated</p>
                  <p class="tw-text-xl tw-font-bold tw-text-green-900">{{ stats.validated || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-violet-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-purple-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-purple-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-send tw-text-white tw-text-sm"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-font-medium tw-text-purple-700 tw-uppercase tw-tracking-wide">Transferred</p>
                  <p class="tw-text-xl tw-font-bold tw-text-purple-900">{{ stats.transferred || 0 }}</p>
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
                placeholder="Search entries..."
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

              <Calendar
                v-model="filters.dateRange"
                selectionMode="range"
                placeholder="Date Range"
                @date-select="applyFilters"
                showIcon
                class="tw-min-w-[160px] tw-border tw-border-slate-300 tw-rounded-lg tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
                showClear
              />

              <InputText
                v-model="filters.supplier"
                placeholder="Supplier..."
                @input="applyFilters"
                class="tw-min-w-[140px] tw-border tw-border-slate-300 tw-rounded-lg tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
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
              :disabled="!selectedDraftEntries.length || validatingIds.length > 0"
              @click="validateSelectedEntries"
              icon="pi pi-check-circle"
              :label="`Validate (${selectedDraftEntries.length})`"
              class="enhanced-success-btn tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            />
            <router-link to="/purchasing/bon-entrees/create">
              <Button
                icon="pi pi-plus"
                label="Create Entry"
                class="enhanced-primary-btn tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
              />
            </router-link>
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
          <Tag v-if="filters.status"
               :value="`Status: ${getStatusLabel(filters.status)}`"
               severity="info"
               class="tw-cursor-pointer"
               @click="filters.status = null; applyFilters()" />
          <Tag v-if="filters.supplier"
               :value="`Supplier: ${filters.supplier}`"
               severity="info"
               class="tw-cursor-pointer"
               @click="filters.supplier = ''; applyFilters()" />
          <Tag v-if="filters.dateRange"
               :value="`Date: ${formatDateRange(filters.dateRange)}`"
               severity="info"
               class="tw-cursor-pointer"
               @click="filters.dateRange = null; applyFilters()" />
          <Button @click="clearAllFilters"
                  text
                  size="small"
                  class="tw-text-slate-500 hover:tw-text-slate-700">
            Clear all
          </Button>
        </div>
      </div>

      <!-- Enhanced Data Table -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <!-- Table Controls -->
        <div class="tw-p-4 tw-border-b tw-border-slate-200/60 tw-bg-slate-50/50">
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <span class="tw-text-sm tw-font-medium tw-text-slate-700">Showing {{ bonEntrees.length }} of {{ totalRecords }} entries</span>
            </div>
            <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-slate-600">
              <i class="pi pi-info-circle"></i>
              <span>{{ totalRecords || 0 }} total entries</span>
            </div>
          </div>
        </div>

        <DataTable
          v-model="selectedItems"
          :value="bonEntrees"
          :loading="loading"
          :paginator="true"
          :rows="20"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          @sort="onSort"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[10, 20, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
          responsiveLayout="scroll"
          :scrollable="true"
          scrollHeight="600px"
          dataKey="id"
          class="medical-table tw-border-none"
          :class="{ 'tw-hidden': loading }"
        >
          <template #loading>
            <div class="tw-flex tw-items-center tw-justify-center tw-py-12">
              <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
                <ProgressSpinner class="tw-w-8 tw-h-8" />
                <p class="tw-text-slate-600">Loading stock entries...</p>
              </div>
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Enhanced Code Column -->
          <Column field="bon_entree_code" header="Entry Code" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-bg-green-100 tw-text-green-800 tw-px-2 tw-py-1 tw-rounded-md tw-text-xs tw-font-mono tw-font-medium">
                  {{ slotProps.data.bon_entree_code }}
                </div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Status Column -->
          <Column field="status" header="Status" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Tag
                  :value="getStatusLabel(slotProps.data.status)"
                  :severity="getStatusSeverity(slotProps.data.status)"
                  class="tw-font-medium"
                />
                <div v-if="slotProps.data.status.toLowerCase() === 'draft'" class="tw-w-2 tw-h-2 tw-bg-amber-400 tw-rounded-full tw-animate-pulse"></div>
                <div v-if="slotProps.data.status.toLowerCase() === 'validated'" class="tw-w-2 tw-h-2 tw-bg-green-400 tw-rounded-full"></div>
                <div v-if="slotProps.data.status.toLowerCase() === 'transferred'" class="tw-w-2 tw-h-2 tw-bg-purple-400 tw-rounded-full"></div>
              </div>
            </template>
          </Column>

          <!-- Bon Reception Column -->
          <Column field="bon_reception.bon_reception_code" header="Bon Reception" :sortable="true">
            <template #body="slotProps">
              <div v-if="slotProps.data.bon_reception" class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-file tw-text-blue-500"></i>
                <span class="tw-font-mono tw-text-sm tw-text-blue-600 tw-font-medium">
                  {{ slotProps.data.bon_reception.bon_reception_code }}
                </span>
              </div>
              <span v-else class="tw-text-slate-400 tw-text-sm tw-italic">Direct Entry</span>
            </template>
          </Column>

          <!-- Enhanced Supplier Column -->
          <Column field="fournisseur.company_name" header="Supplier" :sortable="true">
            <template #body="slotProps">
              <div v-if="slotProps.data.fournisseur" class="tw-flex tw-items-center tw-gap-3">
                <Avatar
                  :label="getInitials(slotProps.data.fournisseur.company_name)"
                  class="tw-bg-gradient-to-br tw-from-green-500 tw-to-emerald-600 tw-text-white tw-text-xs tw-font-bold"
                  size="normal"
                  shape="circle"
                />
                <div>
                  <span class="tw-font-medium tw-text-slate-900">{{ slotProps.data.fournisseur.company_name }}</span>
                  <p class="tw-text-xs tw-text-slate-500">{{ slotProps.data.fournisseur.contact_person || 'No contact' }}</p>
                </div>
              </div>
              <span v-else class="tw-text-slate-400 tw-text-sm tw-italic">No Supplier</span>
            </template>
          </Column>

          <!-- Enhanced Amount Column -->
          <Column field="total_amount" header="Total Amount" :sortable="true">
            <template #body="slotProps">
              <div class="tw-text-right">
                <span class="tw-text-lg tw-font-bold tw-text-slate-900">{{ formatCurrency(slotProps.data.total_amount) }}</span>
              </div>
            </template>
          </Column>

          <!-- Items Count Column -->
          <Column field="items_count" header="Items" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Badge :value="slotProps.data.items_count || 0" severity="info" />
                <span class="tw-text-sm tw-text-slate-600">items</span>
              </div>
            </template>
          </Column>

          <!-- Created By Column -->
          <Column field="creator.name" header="Created By">
            <template #body="slotProps">
              <div v-if="slotProps.data.creator" class="tw-flex tw-items-center tw-gap-2">
                <Avatar
                  :label="getInitials(slotProps.data.creator.name)"
                  class="tw-bg-gradient-to-br tw-from-slate-500 tw-to-gray-600 tw-text-white tw-text-xs tw-font-bold"
                  size="small"
                  shape="circle"
                />
                <span class="tw-text-sm tw-text-slate-700">{{ slotProps.data.creator.name }}</span>
              </div>
              <span v-else class="tw-text-slate-400 tw-text-sm">System</span>
            </template>
          </Column>

          <!-- Enhanced Date Column -->
          <Column field="created_at" header="Created Date" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-flex-col">
                <span class="tw-text-sm tw-font-medium tw-text-slate-900">{{ formatDate(slotProps.data.created_at) }}</span>
                <span class="tw-text-xs tw-text-slate-500">{{ formatTime(slotProps.data.created_at) }}</span>
              </div>
            </template>
          </Column>

          <!-- Enhanced Actions Column -->
          <Column header="Actions" :exportable="false" class="tw-w-80">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-1 tw-flex-wrap">
                <!-- View Details - Always visible -->
                <Button
                  @click="viewDetails(slotProps.data)"
                  icon="pi pi-eye"
                  size="small"
                  text
                  v-tooltip.top="'View details'"
                />

                <!-- Print Tickets - Only for validated or transferred -->
                <Button
                  v-if="slotProps.data.status.toLowerCase() === 'validated' || slotProps.data.status.toLowerCase() === 'transferred'"
                  @click="printTickets(slotProps.data)"
                  icon="pi pi-print"
                  size="small"
                  text
                  v-tooltip.top="'Print tickets'"
                />

                <!-- Edit - Only for draft -->
                <Button
                  v-if="slotProps.data.status.toLowerCase() === 'draft'"
                  @click="editBonEntree(slotProps.data)"
                  icon="pi pi-pencil"
                  size="small"
                  text
                  v-tooltip.top="'Edit entry'"
                />

                <!-- Validate - Only for draft -->
                <Button
                  v-if="slotProps.data.status.toLowerCase() === 'draft'"
                  @click="validateBonEntree(slotProps.data)"
                  icon="pi pi-check"
                  :loading="validatingIds.includes(slotProps.data.id)"
                  :disabled="validatingIds.includes(slotProps.data.id)"
                  size="small"
                  text
                  v-tooltip.top="'Validate entry'"
                />

                <!-- Transfer - Only for validated -->
                <Button
                  v-if="slotProps.data.status.toLowerCase() === 'validated'"
                  @click="transferToStock(slotProps.data)"
                  icon="pi pi-send"
                  :loading="transferringIds.includes(slotProps.data.id)"
                  :disabled="transferringIds.includes(slotProps.data.id)"
                  size="small"
                  text
                  v-tooltip.top="'Transfer to stock'"
                />

                <!-- Status Badge for Transferred -->
                <Tag
                  v-if="slotProps.data.status.toLowerCase() === 'transferred'"
                  value="Completed"
                  severity="success"
                  size="small"
                  class="tw-text-xs"
                />

                <!-- Delete - Only for draft -->
                <Button
                  v-if="slotProps.data.status.toLowerCase() === 'draft'"
                  @click="deleteBonEntree(slotProps.data)"
                  icon="pi pi-trash"
                  size="small"
                  text
                  v-tooltip.top="'Delete entry'"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Loading State -->
        <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
          <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
            <ProgressSpinner class="tw-w-8 tw-h-8" />
            <p class="tw-text-slate-600">Loading stock entries...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Dialog -->
    <Dialog
      :visible="showDetailsDialog"
      @update:visible="showDetailsDialog = $event"
      :header="`Stock Entry Details - ${selectedBonEntree?.bon_entree_code}`"
      :modal="true"
      :maximizable="true"
      :style="{ width: '90vw', maxWidth: '1200px' }"
      :contentStyle="{ height: '80vh', overflow: 'auto' }"
      class="enhanced-dialog tw-p-0"
    >
      <div v-if="selectedBonEntree" class="tw-space-y-6 tw-p-6">
        <!-- Basic Information -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-info-circle tw-text-blue-500"></i>
              Basic Information
            </h3>
            <div class="tw-space-y-3 tw-bg-slate-50 tw-p-4 tw-rounded-lg">
              <div class="tw-flex tw-justify-between">
                <strong>Code:</strong>
                <span class="tw-font-mono tw-text-blue-600">{{ selectedBonEntree.bon_entree_code }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <strong>Status:</strong>
                <Tag
                  :value="getStatusLabel(selectedBonEntree.status)"
                  :severity="getStatusSeverity(selectedBonEntree.status)"
                />
              </div>
              <div class="tw-flex tw-justify-between">
                <strong>Total Amount:</strong>
                <span class="tw-font-bold tw-text-green-600">{{ formatCurrency(selectedBonEntree.total_amount) }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <strong>Created:</strong>
                <span>{{ formatDateTime(selectedBonEntree.created_at) }}</span>
              </div>
            </div>
          </div>

          <div v-if="selectedBonEntree.fournisseur">
            <h3 class="tw-text-lg tw-font-semibold tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-building tw-text-green-500"></i>
              Supplier Information
            </h3>
            <div class="tw-space-y-3 tw-bg-slate-50 tw-p-4 tw-rounded-lg">
              <div class="tw-flex tw-justify-between">
                <strong>Company:</strong>
                <span>{{ selectedBonEntree.fournisseur.company_name }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <strong>Contact:</strong>
                <span>{{ selectedBonEntree.fournisseur.contact_person }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <strong>Phone:</strong>
                <span>{{ selectedBonEntree.fournisseur.phone }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <strong>Email:</strong>
                <span>{{ selectedBonEntree.fournisseur.email }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Bon Reception Reference -->
        <div v-if="selectedBonEntree.bon_reception" class="tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
          <h3 class="tw-text-lg tw-font-semibold tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-link tw-text-blue-500"></i>
            Linked Bon Reception
          </h3>
          <div class="tw-flex tw-items-center tw-gap-3">
            <i class="pi pi-file tw-text-blue-500"></i>
            <span class="tw-font-mono tw-text-blue-600 tw-font-medium">{{ selectedBonEntree.bon_reception.bon_reception_code }}</span>
            <span class="tw-text-sm tw-text-slate-600">from {{ formatDate(selectedBonEntree.bon_reception.date_reception) }}</span>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedBonEntree.notes" class="tw-bg-amber-50 tw-p-4 tw-rounded-lg tw-border tw-border-amber-200">
          <h3 class="tw-text-lg tw-font-semibold tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-sticky-note tw-text-amber-500"></i>
            Notes
          </h3>
          <div class="tw-text-slate-700">{{ selectedBonEntree.notes }}</div>
        </div>

        <!-- Items -->
        <div v-if="selectedBonEntree.items && selectedBonEntree.items.length > 0">
          <h3 class="tw-text-lg tw-font-semibold tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-list tw-text-green-500"></i>
            Items ({{ selectedBonEntree.items.length }})
          </h3>
          <DataTable :value="selectedBonEntree.items" class="p-datatable-sm tw-border tw-border-slate-200 tw-rounded-lg">
            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div>
                  <div class="tw-font-medium tw-text-slate-900">{{ slotProps.data.product?.name }}</div>
                  <div class="tw-text-sm tw-text-slate-500">{{ slotProps.data.product?.product_code }}</div>
                </div>
              </template>
            </Column>
            <Column field="quantity" header="Quantity" class="tw-text-center">
              <template #body="slotProps">
                <Badge :value="slotProps.data.quantity" severity="info" />
              </template>
            </Column>
            <Column field="purchase_price" header="Purchase Price" class="tw-text-right">
              <template #body="slotProps">
                {{ formatCurrency(slotProps.data.purchase_price) }}
              </template>
            </Column>
            <Column field="total_amount" header="Total" class="tw-text-right">
              <template #body="slotProps">
                <strong class="tw-text-green-600">{{ formatCurrency(slotProps.data.total_amount) }}</strong>
              </template>
            </Column>
            <Column field="batch_number" header="Batch">
              <template #body="slotProps">
                <span class="tw-font-mono tw-text-sm">{{ slotProps.data.batch_number || 'N/A' }}</span>
              </template>
            </Column>
            <Column field="expiry_date" header="Expiry">
              <template #body="slotProps">
                <span :class="isExpiringSoon(slotProps.data.expiry_date) ? 'tw-text-red-600 tw-font-medium' : 'tw-text-slate-700'">
                  {{ slotProps.data.expiry_date ? formatDate(slotProps.data.expiry_date) : 'N/A' }}
                </span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-2">
          <Button
            @click="showDetailsDialog = false"
            label="Close"
            class="p-button-secondary"
          />
        </div>
      </template>
    </Dialog>

    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Validation Modal with Service and Storage Selection -->
    <Dialog
      :visible="showValidationModal"
      :header="`Validate Bon Entree - ${validationModalData.bonEntree?.bon_entree_code || ''}`"
      :modal="true"
      :closable="true"
      :style="{ width: '90vw', maxWidth: '650px' }"
      class="validation-modal"
      @update:visible="val => (showValidationModal = val)"
      @hide="closeValidationModal"
    >
      <div class="tw-space-y-6">
        <!-- Order Type Indicator -->
        <div v-if="validationModalData.isPharmacyOrder !== undefined" class="tw-bg-gradient-to-r tw-p-4 tw-rounded-lg tw-border tw-flex tw-items-center tw-gap-3"
          :class="validationModalData.isPharmacyOrder 
            ? 'tw-from-green-50 tw-to-emerald-50 tw-border-green-200' 
            : 'tw-from-blue-50 tw-to-indigo-50 tw-border-blue-200'">
          <i :class="validationModalData.isPharmacyOrder ? 'pi pi-heart-fill tw-text-green-600' : 'pi pi-box tw-text-blue-600'" class="tw-text-2xl"></i>
          <div>
            <p class="tw-font-bold tw-text-lg" :class="validationModalData.isPharmacyOrder ? 'tw-text-green-800' : 'tw-text-blue-800'">
              {{ validationModalData.isPharmacyOrder ? 'Pharmacy Order' : 'Stock Order' }}
            </p>
            <p class="tw-text-sm" :class="validationModalData.isPharmacyOrder ? 'tw-text-green-700' : 'tw-text-blue-700'">
              This will be added to {{ validationModalData.isPharmacyOrder ? 'pharmacy inventory' : 'stock inventory' }}
            </p>
          </div>
        </div>

        <!-- Service Selection Section -->
        <div class="tw-space-y-3">
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-building tw-text-blue-600 tw-text-lg"></i>
            <label class="tw-font-semibold tw-text-gray-800">Select Service</label>
            <span v-if="validationModalData.loadingServices" class="tw-text-xs tw-text-gray-500">(Loading...)</span>
          </div>

          <!-- Quick dropdown for services (searchable) -->
          <div class="tw-mb-3">
            <Dropdown
              v-model="validationModalData.selectedService"
              :options="validationModalData.serviceOptions"
              optionLabel="name"
              placeholder="Select a service"
              @change="() => selectService(validationModalData.selectedService)"
              class="tw-w-full"
            />
          </div>


        </div>

        <!-- Storage Selection Section -->
        <div v-if="validationModalData.selectedService" class="tw-space-y-3">
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-home tw-text-indigo-600 tw-text-lg"></i>
            <label class="tw-font-semibold tw-text-gray-800">Select Storage Location</label>
            <span v-if="validationModalData.loadingStorages" class="tw-text-xs tw-text-gray-500">(Loading...)</span>
          </div>
          
          <div v-if="validationModalData.storageOptions.length > 0" class="tw-grid tw-grid-cols-1 tw-gap-3">
            <div
              v-for="storage in validationModalData.storageOptions"
              :key="storage.id"
              class="tw-p-4 tw-border-2 tw-rounded-lg tw-cursor-pointer tw-transition-all tw-duration-200"
              :class="validationModalData.selectedStorage?.id === storage.id 
                ? 'tw-border-indigo-600 tw-bg-indigo-50' 
                : 'tw-border-gray-200 tw-bg-white hover:tw-border-indigo-300 hover:tw-bg-indigo-50/30'"
              @click="validationModalData.selectedStorage = storage"
            >
              <div class="tw-flex tw-items-start tw-gap-3">
                <div
                  class="tw-w-6 tw-h-6 tw-border-2 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mt-0.5 tw-flex-shrink-0"
                  :class="validationModalData.selectedStorage?.id === storage.id 
                    ? 'tw-border-indigo-600 tw-bg-indigo-600' 
                    : 'tw-border-gray-300'"
                >
                  <i v-if="validationModalData.selectedStorage?.id === storage.id" class="pi pi-check tw-text-white tw-text-xs"></i>
                </div>
                <div class="tw-flex-1 tw-min-w-0">
                  <p class="tw-font-semibold tw-text-gray-800">{{ storage.name }}</p>
                  <p class="tw-text-sm tw-text-gray-600 tw-mt-1">{{ storage.description || storage.location || 'No description' }}</p>
                  <div v-if="storage.capacity" class="tw-text-xs tw-text-gray-500 tw-mt-2">
                    Capacity: <span class="tw-font-medium">{{ storage.capacity }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-4">
            <div class="tw-flex tw-items-start tw-gap-3">
              <i class="pi pi-info-circle tw-text-amber-600 tw-text-xl tw-flex-shrink-0 tw-mt-0.5"></i>
              <div>
                <p class="tw-font-medium tw-text-amber-900">No storages configured</p>
                <p class="tw-text-sm tw-text-amber-800 tw-mt-1">This service has no storage locations configured. The bon entree will be validated without storage assignment.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Service Group Pricing Section -->
        <div v-if="validationModalData.selectedService" class="tw-space-y-3">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-money-bill tw-text-green-600 tw-text-lg"></i>
              <label class="tw-font-semibold tw-text-gray-800">Service Group Pricing</label>
            </div>
            <Button
              v-if="!validationModalData.showPricingSection"
              @click="loadServiceGroupPricing"
              :loading="validationModalData.loadingPricing"
              label="Configure Pricing"
              icon="pi pi-cog"
              class="p-button-sm p-button-text"
              size="small"
            />
          </div>

          <!-- Pricing Configuration Panel -->
          <div v-if="validationModalData.showPricingSection" class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4 tw-space-y-4">
            <!-- Service Groups Header -->
            <div class="tw-flex tw-items-center tw-justify-between tw-pb-3 tw-border-b tw-border-blue-200">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-users tw-text-blue-600"></i>
                <span class="tw-font-semibold tw-text-blue-900">Available Service Groups</span>
              </div>
              <Badge :value="validationModalData.serviceGroups?.length || 0" severity="info" />
            </div>

            <!-- Products Table with Service Group Pricing -->
            <div class="tw-space-y-3 tw-max-h-96 tw-overflow-y-auto">
              <div
                v-for="item in validationModalData.bonEntree?.items"
                :key="item.id"
                class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm tw-border tw-border-gray-200"
              >
                <!-- Product Header -->
                <div class="tw-flex tw-items-start tw-justify-between tw-mb-3 tw-pb-3 tw-border-b tw-border-gray-200">
                  <div class="tw-flex-1">
                    <h4 class="tw-font-semibold tw-text-gray-900">
                      {{ getProductNameFromItem(item) }}
                    </h4>
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                      Qty: {{ item.quantity }} | Purchase Price: {{ formatCurrency(item.purchase_price) }}
                    </p>
                  </div>
                  <Tag :value="validationModalData.isPharmacyOrder ? 'Pharmacy' : 'Stock'" severity="info" />
                </div>

                <!-- Service Group Pricing Grid -->
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-3">
                  <div
                    v-for="group in validationModalData.serviceGroups"
                    :key="group.id"
                    class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-gray-50 tw-p-3 tw-rounded-lg tw-border tw-border-gray-200"
                  >
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                      <div class="tw-w-2 tw-h-2 tw-rounded-full tw-bg-blue-500"></div>
                      <span class="tw-text-sm tw-font-medium tw-text-gray-700">{{ group.name }}</span>
                    </div>
                    
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                      <InputText
                        v-model="validationModalData.groupPricing[`${item.id}_${group.id}`]"
                        :placeholder="`Price for ${group.name}`"
                        type="number"
                        step="0.01"
                        min="0"
                        class="tw-w-full tw-text-sm"
                        @input="calculateMargin(item, group)"
                      />
                      <i class="pi pi-dollar tw-text-green-600 tw-text-xs"></i>
                    </div>

                    <!-- Pricing Information Display -->
                    <div class="tw-bg-white tw-rounded tw-p-2 tw-space-y-1 tw-text-xs tw-border tw-border-gray-100">
                      <div class="tw-flex tw-justify-between tw-items-center">
                        <span class="tw-text-gray-500">Latest Purchase:</span>
                        <span class="tw-font-semibold tw-text-blue-700">{{ formatCurrency(item.purchase_price) }}</span>
                      </div>
                      <div v-if="validationModalData.productPricingInfo[getProductKey(item)]" class="tw-space-y-1">
                        <div class="tw-flex tw-justify-between tw-items-center">
                          <span class="tw-text-gray-500">Average:</span>
                          <span class="tw-font-medium tw-text-gray-700">{{ formatCurrency(validationModalData.productPricingInfo[getProductKey(item)]?.average_price) }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-items-center">
                          <span class="tw-text-gray-500">Current Price:</span>
                          <span class="tw-font-medium tw-text-gray-700">{{ formatCurrency(validationModalData.productPricingInfo[getProductKey(item)]?.current_price) }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-items-center">
                          <span class="tw-text-gray-500">Last Price:</span>
                          <span class="tw-font-medium tw-text-gray-700">{{ formatCurrency(validationModalData.productPricingInfo[getProductKey(item)]?.last_price) }}</span>
                        </div>
                      </div>
                      <div v-else class="tw-text-center tw-text-gray-400 tw-py-1">
                        <i class="pi pi-spin pi-spinner tw-text-xs"></i> Loading...
                      </div>
                    </div>

                    <!-- Margin Display -->
                    <div v-if="validationModalData.groupPricing[`${item.id}_${group.id}`]" class="tw-mt-2 tw-text-xs">
                      <span class="tw-text-gray-600">Margin: </span>
                      <span :class="getMarginClass(item, group)" class="tw-font-semibold">
                        {{ calculateMarginPercentage(item, group) }}%
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Quick Actions -->
                <div class="tw-flex tw-gap-2 tw-mt-3 tw-pt-3 tw-border-t tw-border-gray-200">
                  <Button
                    @click="applyPurchasePriceToAll(item)"
                    label="Use Purchase Price"
                    icon="pi pi-copy"
                    class="p-button-text p-button-sm tw-text-xs"
                    size="small"
                  />
                  <Button
                    @click="applyMarginToAll(item, 20)"
                    label="+20% Margin"
                    icon="pi pi-percentage"
                    class="p-button-text p-button-sm tw-text-xs"
                    size="small"
                  />
                  <Button
                    @click="clearProductPricing(item)"
                    label="Clear"
                    icon="pi pi-times"
                    class="p-button-text p-button-danger p-button-sm tw-text-xs"
                    size="small"
                  />
                </div>
              </div>
            </div>

            <!-- Pricing Summary -->
            <div class="tw-bg-white tw-rounded-lg tw-p-3 tw-border tw-border-blue-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <span class="tw-text-sm tw-text-gray-700">Total Pricing Entries:</span>
                <Badge :value="Object.keys(validationModalData.groupPricing).filter(k => validationModalData.groupPricing[k]).length" severity="success" />
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Section -->
        <div class="tw-bg-gradient-to-r tw-from-slate-50 tw-to-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-space-y-2">
          <div class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-list tw-text-indigo-600"></i>
            Validation Summary
          </div>
          <div class="tw-grid tw-grid-cols-2 tw-gap-3 tw-text-sm tw-mt-3">
            <div class="tw-bg-white tw-p-2 tw-rounded">
              <p class="tw-text-gray-600">Items</p>
              <p class="tw-text-lg tw-font-bold tw-text-indigo-600">{{ validationModalData.bonEntree?.items?.length || 0 }}</p>
            </div>
            <div class="tw-bg-white tw-p-2 tw-rounded">
              <p class="tw-text-gray-600">Code</p>
              <p class="tw-font-medium tw-text-gray-800 tw-truncate">{{ validationModalData.bonEntree?.bon_entree_code }}</p>
            </div>
            <div class="tw-bg-white tw-p-2 tw-rounded">
              <p class="tw-text-gray-600">Service</p>
              <p class="tw-font-medium tw-text-gray-800">{{ validationModalData.selectedService?.name || 'Not selected' }}</p>
            </div>
            <div class="tw-bg-white tw-p-2 tw-rounded">
              <p class="tw-text-gray-600">Storage</p>
              <p class="tw-font-medium tw-text-gray-800">{{ validationModalData.selectedStorage?.name || 'Not selected' }}</p>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-3 tw-justify-end">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="closeValidationModal" 
          />
          <Button
            label="Validate"
            icon="pi pi-check-circle"
            class="p-button-success"
            :disabled="!validationModalData.selectedService"
            :loading="validatingIds.includes(validationModalData.bonEntree?.id)"
            @click="confirmValidation"
          />
        </div>
      </template>
    </Dialog>

    <!-- Toast component -->
    <Toast />

    <!-- Floating Action Button -->
    <router-link to="/purchasing/bon-entrees/create">
      <button class="fab tw-shadow-xl" v-tooltip.top="'Create New Stock Entry'">
        <i class="pi pi-plus tw-text-xl"></i>
      </button>
    </router-link>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import ConfirmDialog from 'primevue/confirmdialog'
import ProgressSpinner from 'primevue/progressspinner'
import Avatar from 'primevue/avatar'
import Toast from 'primevue/toast'
import axios from 'axios'
import serviceGroupPricingService from '@/services/Inventory/serviceGroupPricingService'
import productPricingService from '@/services/Inventory/productPricingService'

// Composables
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// Reactive state
const loading = ref(false)
const validatingIds = ref([])
const transferringIds = ref([])
const bonEntrees = ref([])
const selectedItems = ref([])
const totalRecords = ref(0)
const showDetailsDialog = ref(false)
const selectedBonEntree = ref(null)
const stats = ref({})

// Validation modal state
const showValidationModal = ref(false)
const validationModalData = reactive({
  bonEntree: null,
  selectedService: null,
  selectedStorage: null,
  serviceOptions: [],
  storageOptions: [],
  serviceInfo: null,
  isPharmacyOrder: false,
  loadingServices: false,
  loadingStorages: false,
  // Service Group Pricing
  showPricingSection: false,
  loadingPricing: false,
  serviceGroups: [],
  groupPricing: {}, // Format: { "itemId_groupId": price }
  productPricingInfo: {} // Format: { "productId_isPharmacy": { average_price, current_price, last_price } }
})

const lazyParams = ref({
  first: 0,
  rows: 20,
  sortField: 'created_at',
  sortOrder: -1
})

const filters = reactive({
  status: null,
  dateRange: null,
  supplier: '',
  search: ''
})

// Constants
const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Validated', value: 'validated' },
  { label: 'Transferred', value: 'transferred' },
  { label: 'Cancelled', value: 'cancelled' }
]

// Computed properties
const selectedDraftEntries = computed(() => {
  if (!selectedItems.value || !Array.isArray(selectedItems.value)) return []
  return selectedItems.value.filter(item => item && item.status && item.status.toLowerCase() === 'draft')
})

const hasActiveFilters = computed(() => {
  return filters.status ||
         filters.supplier ||
         filters.dateRange ||
         filters.search
})

// Methods
const fetchBonEntrees = async () => {
  try {
    loading.value = true
    
    const params = {
      page: Math.floor(lazyParams.value.first / lazyParams.value.rows) + 1,
      per_page: lazyParams.value.rows,
      sort_by: lazyParams.value.sortField || 'created_at',
      sort_direction: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
      with: 'bon_reception,fournisseur,creator',
      ...filters
    }

    // Clean up empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null || params[key] === undefined) {
        delete params[key]
      }
    })

    const response = await axios.get('/api/purchasing/bon-entrees', { params })
    
    if (response.data.status === 'success') {
      bonEntrees.value = response.data.data.data || response.data.data
      totalRecords.value = response.data.data.total || response.data.data.length
    }
  } catch (err) {
    console.error('Error fetching bon entrees:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load bon entrees',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/purchasing/bon-entrees/meta/stats')
    
    if (response.data.status === 'success') {
      stats.value = response.data.data || {}
    }
  } catch (err) {
    console.error('Error fetching stats:', err)
  }
}

const refreshData = async () => {
  await Promise.all([fetchBonEntrees(), fetchStats()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const onPage = (event) => {
  lazyParams.value = event
  fetchBonEntrees()
}

const onSort = (event) => {
  lazyParams.value = event
  fetchBonEntrees()
}

const applyFilters = () => {
  lazyParams.value.first = 0 // Reset to first page
  fetchBonEntrees()
}

const viewDetails = async (bonEntree) => {
  try {
    const response = await axios.get(`/api/purchasing/bon-entrees/${bonEntree.id}`)
    if (response.data.status === 'success') {
      selectedBonEntree.value = response.data.data
      showDetailsDialog.value = true
    }
  } catch (err) {
    console.error('Error fetching bon entree details:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load bon entree details',
      life: 3000
    })
  }
}

const editBonEntree = (bonEntree) => {
  router.push(`/purchasing/bon-entrees/${bonEntree.id}/edit`)
}

const printTickets = async (bonEntree) => {
  try {
    const response = await axios.get(`/api/purchasing/bon-entrees/${bonEntree.id}/generate-tickets`)
    
    const newWindow = window.open('', '_blank')
    if (newWindow) {
      newWindow.document.write(response.data)
      newWindow.document.close()
      setTimeout(() => {
        newWindow.print()
      }, 250)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Tickets generated and print dialog opened',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Popup Blocked',
        detail: 'Please allow popups to print tickets',
        life: 5000
      })
    }
  } catch (err) {
    console.error('Error generating tickets:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to generate tickets',
      life: 3000
    })
  }
}

const validateBonEntree = (bonEntree) => {
  // Open validation modal instead of direct confirmation
  validationModalData.bonEntree = bonEntree
  showValidationModal.value = true
  loadServicesForValidation(bonEntree)
}

const loadServicesForValidation = async (bonEntree) => {
  try {
    validationModalData.loadingServices = true
    validationModalData.serviceOptions = []
    validationModalData.selectedService = null
    validationModalData.storageOptions = []
    validationModalData.selectedStorage = null
    
    // Fetch services with appropriate storages (pharmacy or stock) based on order type
    try {
      const response = await axios.get(`/api/purchasing/bon-entrees/${bonEntree.id}/services-with-storages`)
      if (response.data.status === 'success') {
        validationModalData.serviceOptions = response.data.data.services || []
        validationModalData.isPharmacyOrder = response.data.data.is_pharmacy_order || false
        
        // If bon entree has a service_id, pre-select it
        if (bonEntree.service_id && validationModalData.serviceOptions.length > 0) {
          const preSelectedService = validationModalData.serviceOptions.find(s => s.id === bonEntree.service_id)
          if (preSelectedService) {
            validationModalData.selectedService = preSelectedService
            // Storages are already loaded in the service object from backend
            validationModalData.storageOptions = preSelectedService.storages || []
            // Auto-select if only one storage
            if (validationModalData.storageOptions.length === 1) {
              validationModalData.selectedStorage = validationModalData.storageOptions[0]
            }
          }
        } else if (validationModalData.serviceOptions.length === 1) {
          // Auto-select the only service
          const onlyService = validationModalData.serviceOptions[0]
          validationModalData.selectedService = onlyService
          validationModalData.storageOptions = onlyService.storages || []
          // Auto-select if only one storage
          if (validationModalData.storageOptions.length === 1) {
            validationModalData.selectedStorage = validationModalData.storageOptions[0]
          }
        }
      }
    } catch (err) {
      console.warn('Could not load services:', err)
      validationModalData.serviceOptions = []
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load services and storages',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error loading services:', err)
    validationModalData.serviceOptions = []
  } finally {
    validationModalData.loadingServices = false
  }
}

const selectService = (service) => {
  // unify selection from dropdown or card
  validationModalData.selectedService = service || null
  if (service) {
    // Storages are already loaded in the service object from the backend
    validationModalData.storageOptions = service.storages || []
    validationModalData.selectedStorage = null
    
    // Auto-select if only one storage
    if (validationModalData.storageOptions.length === 1) {
      validationModalData.selectedStorage = validationModalData.storageOptions[0]
    }
  } else {
    validationModalData.storageOptions = []
    validationModalData.selectedStorage = null
  }
}

const loadStoragesForSelectedService = async (service) => {
  try {
    if (!service || !service.id) {
      validationModalData.storageOptions = []
      validationModalData.selectedStorage = null
      return
    }
    
    validationModalData.loadingStorages = true
    validationModalData.storageOptions = []
    validationModalData.selectedStorage = null
    
    try {
      const response = await axios.get(`/api/services/${service.id}/storages`)
      if (response.data.status === 'success') {
        validationModalData.storageOptions = response.data.data || []
        validationModalData.serviceInfo = service

        // If api returned empty but service object already contains storages, use that
        if ((!validationModalData.storageOptions || validationModalData.storageOptions.length === 0) && service.storages && service.storages.length > 0) {
          validationModalData.storageOptions = service.storages
        }

        // Auto-select if only one storage available
        if (validationModalData.storageOptions && validationModalData.storageOptions.length === 1) {
          validationModalData.selectedStorage = validationModalData.storageOptions[0]
        }
      }
    } catch (err) {
      console.warn('Could not load storages for service:', err)
      // If service object already contains storages, use them as fallback
      if (service.storages && service.storages.length > 0) {
        validationModalData.storageOptions = service.storages
        if (validationModalData.storageOptions.length === 1) {
          validationModalData.selectedStorage = validationModalData.storageOptions[0]
        }
      } else {
        validationModalData.storageOptions = []
      }
    }
  } catch (err) {
    console.error('Error loading storages:', err)
    validationModalData.storageOptions = []
  } finally {
    validationModalData.loadingStorages = false
  }
}

const loadStoragesForService = async (bonEntree) => {
  try {
    validationModalData.loadingStorages = true
    
    // For now, we'll load generic storages - in production, fetch from service
    // Check if service_id exists on bon entree or fetch it
    let serviceId = bonEntree.service_id
    
    if (!serviceId && bonEntree.bon_reception?.bonCommend?.serviceDemand?.service?.id) {
      serviceId = bonEntree.bon_reception.bonCommend.serviceDemand.service.id
    }
    
    if (serviceId) {
      try {
        const response = await axios.get(`/api/services/${serviceId}/storages`)
        if (response.data.status === 'success') {
          validationModalData.storageOptions = response.data.data || []
          validationModalData.serviceInfo = response.data.service || null
          
          // Auto-select if only one storage available
          if (validationModalData.storageOptions.length === 1) {
            validationModalData.selectedStorage = validationModalData.storageOptions[0]
          }
        }
      } catch (err) {
        console.warn('Could not load storages, will validate without storage selection:', err)
        validationModalData.storageOptions = []
      }
    } else {
      validationModalData.storageOptions = []
    }
  } catch (err) {
    console.error('Error loading storages:', err)
    validationModalData.storageOptions = []
  } finally {
    validationModalData.loadingStorages = false
  }
}

// Service Group Pricing Functions
const loadServiceGroupPricing = async () => {
  try {
    validationModalData.loadingPricing = true
    validationModalData.showPricingSection = true
    
    // Load service groups
    const groupsData = await serviceGroupPricingService.getServiceGroups()
    validationModalData.serviceGroups = groupsData || []
    
    // Load existing pricing and product pricing info for each product
    for (const item of validationModalData.bonEntree.items) {
      const productId = item.product_id || item.pharmacy_product_id
      if (productId) {
        // Load product pricing information (average, current, last)
        try {
          const pricingInfo = await productPricingService.getProductPricingInfo(
            productId,
            validationModalData.isPharmacyOrder
          )
          const productKey = `${productId}_${validationModalData.isPharmacyOrder}`
          validationModalData.productPricingInfo[productKey] = pricingInfo
        } catch (err) {
          console.warn('Could not load pricing info for product:', productId, err)
        }

        // Load service group pricing
        try {
          const pricingData = await serviceGroupPricingService.getProductPricingByGroups(
            productId, 
            validationModalData.isPharmacyOrder
          )
          
          // Pre-fill existing pricing
          if (pricingData && Array.isArray(pricingData)) {
            pricingData.forEach(pricing => {
              const key = `${item.id}_${pricing.service_group_id}`
              validationModalData.groupPricing[key] = pricing.price
            })
          }
        } catch (err) {
          console.warn('Could not load service group pricing for product:', productId, err)
        }
      }
    }
  } catch (err) {
    console.error('Error loading service group pricing:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load service group pricing',
      life: 3000
    })
  } finally {
    validationModalData.loadingPricing = false
  }
}

const getProductKey = (item) => {
  const productId = item.product_id || item.pharmacy_product_id
  return `${productId}_${validationModalData.isPharmacyOrder}`
}

const getProductNameFromItem = (item) => {
  if (item.product) {
    return item.product.product_name || item.product.name || 'Unknown Product'
  }
  if (item.pharmacy_product) {
    return item.pharmacy_product.name || item.pharmacy_product.commercial_name || 'Unknown Product'
  }
  return 'Unknown Product'
}

const calculateMarginPercentage = (item, group) => {
  const key = `${item.id}_${group.id}`
  const sellingPrice = parseFloat(validationModalData.groupPricing[key]) || 0
  const purchasePrice = parseFloat(item.purchase_price) || 0
  
  if (purchasePrice === 0) return '0.00'
  
  const margin = ((sellingPrice - purchasePrice) / purchasePrice) * 100
  return margin.toFixed(2)
}

const getMarginClass = (item, group) => {
  const margin = parseFloat(calculateMarginPercentage(item, group))
  if (margin < 0) return 'tw-text-red-600'
  if (margin < 10) return 'tw-text-orange-600'
  if (margin < 20) return 'tw-text-blue-600'
  return 'tw-text-green-600'
}

const applyPurchasePriceToAll = (item) => {
  validationModalData.serviceGroups.forEach(group => {
    const key = `${item.id}_${group.id}`
    validationModalData.groupPricing[key] = item.purchase_price
  })
  
  toast.add({
    severity: 'info',
    summary: 'Applied',
    detail: 'Purchase price applied to all service groups',
    life: 2000
  })
}

const applyMarginToAll = (item, marginPercent) => {
  const purchasePrice = parseFloat(item.purchase_price) || 0
  const sellingPrice = purchasePrice * (1 + marginPercent / 100)
  
  validationModalData.serviceGroups.forEach(group => {
    const key = `${item.id}_${group.id}`
    validationModalData.groupPricing[key] = sellingPrice.toFixed(2)
  })
  
  toast.add({
    severity: 'info',
    summary: 'Applied',
    detail: `${marginPercent}% margin applied to all service groups`,
    life: 2000
  })
}

const clearProductPricing = (item) => {
  validationModalData.serviceGroups.forEach(group => {
    const key = `${item.id}_${group.id}`
    delete validationModalData.groupPricing[key]
  })
  
  toast.add({
    severity: 'info',
    summary: 'Cleared',
    detail: 'Pricing cleared for all service groups',
    life: 2000
  })
}

const confirmValidation = async () => {
  if (!validationModalData.bonEntree) return
  
  // Validate selections
  if (!validationModalData.selectedService) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select a service',
      life: 3000
    })
    return
  }
  
  if (!validationModalData.selectedStorage) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select a storage location',
      life: 3000
    })
    return
  }
  
  try {
    validatingIds.value.push(validationModalData.bonEntree.id)
    
    // Prepare pricing data for submission
    const pricingData = []
    if (validationModalData.showPricingSection && Object.keys(validationModalData.groupPricing).length > 0) {
      Object.keys(validationModalData.groupPricing).forEach(key => {
        const price = validationModalData.groupPricing[key]
        if (price && parseFloat(price) > 0) {
          const [itemId, groupId] = key.split('_')
          const item = validationModalData.bonEntree.items.find(i => i.id === parseInt(itemId))
          if (item) {
            pricingData.push({
              product_id: validationModalData.isPharmacyOrder ? null : (item.product_id || null),
              pharmacy_product_id: validationModalData.isPharmacyOrder ? (item.pharmacy_product_id || null) : null,
              service_group_id: parseInt(groupId),
              price: parseFloat(price),
              is_pharmacy: validationModalData.isPharmacyOrder
            })
          }
        }
      })
    }
    
    const response = await axios.patch(`/api/purchasing/bon-entrees/${validationModalData.bonEntree.id}/validate`, {
      service_id: validationModalData.selectedService.id,
      storage_id: validationModalData.selectedStorage.id,
      service_group_pricing: pricingData
    })
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Validation Successful',
        detail: `Bon entree "${validationModalData.bonEntree.bon_entree_code}" has been validated and is ready for stock transfer`,
        life: 4000
      })
      showValidationModal.value = false
      closeValidationModal()
      fetchBonEntrees()
    }
  } catch (err) {
    console.error('Error validating bon entree:', err)
    toast.add({
      severity: 'error',
      summary: 'Validation Failed',
      detail: err.response?.data?.message || 'Failed to validate bon entree',
      life: 3000
    })
  } finally {
    validatingIds.value = validatingIds.value.filter(id => id !== validationModalData.bonEntree.id)
  }
}

const closeValidationModal = () => {
  showValidationModal.value = false
  validationModalData.bonEntree = null
  validationModalData.selectedService = null
  validationModalData.selectedStorage = null
  validationModalData.serviceOptions = []
  validationModalData.storageOptions = []
  validationModalData.serviceInfo = null
  validationModalData.showPricingSection = false
  validationModalData.serviceGroups = []
  validationModalData.groupPricing = {}
  validationModalData.productPricingInfo = {}
}

const validateSelectedEntries = () => {
  const draftEntries = selectedDraftEntries.value
  if (!draftEntries.length) {
    toast.add({
      severity: 'warn',
      summary: 'No Entries Selected',
      detail: 'Please select draft entries to validate',
      life: 3000
    })
    return
  }

  confirm.require({
    message: `Are you sure you want to validate ${draftEntries.length} selected draft entries? They will be prepared for stock transfer.`,
    header: 'Validate Selected Entries',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: async () => {
      let successCount = 0
      let errorCount = 0

      for (const entry of draftEntries) {
        try {
          validatingIds.value.push(entry.id)
          const response = await axios.patch(`/api/purchasing/bon-entrees/${entry.id}/validate`)
          
          if (response.data.status === 'success') {
            successCount++
          } else {
            errorCount++
          }
        } catch (err) {
          console.error(`Error validating ${entry.bon_entree_code}:`, err)
          errorCount++
        } finally {
          validatingIds.value = validatingIds.value.filter(id => id !== entry.id)
        }
      }

      // Show summary toast
      if (successCount > 0) {
        toast.add({
          severity: 'success',
          summary: 'Bulk Validation Complete',
          detail: `${successCount} entries validated successfully${errorCount > 0 ? `, ${errorCount} failed` : ''}`,
          life: 4000
        })
      }

      if (errorCount > 0 && successCount === 0) {
        toast.add({
          severity: 'error',
          summary: 'Bulk Validation Failed',
          detail: `${errorCount} entries failed to validate`,
          life: 4000
        })
      }

      // Clear selection and refresh data
      selectedItems.value = []
      fetchBonEntrees()
    }
  })
}

const transferToStock = (bonEntree) => {
  confirm.require({
    message: `Are you sure you want to transfer "${bonEntree.bon_entree_code}" to service stock? This will add all items to the service inventory and cannot be undone.`,
    header: 'Transfer to Stock',
    icon: 'pi pi-send',
    acceptClass: 'p-button-primary',
    accept: async () => {
      try {
        transferringIds.value.push(bonEntree.id)
        
        const response = await axios.patch(`/api/purchasing/bon-entrees/${bonEntree.id}/transfer`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Transfer Successful',
            detail: `Items from "${bonEntree.bon_entree_code}" have been added to service stock inventory`,
            life: 4000
          })
          fetchBonEntrees()
        }
      } catch (err) {
        console.error('Error transferring bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Transfer Failed',
          detail: err.response?.data?.message || 'Failed to transfer bon entree to stock',
          life: 3000
        })
      } finally {
        transferringIds.value = transferringIds.value.filter(id => id !== bonEntree.id)
      }
    }
  })
}

const deleteBonEntree = (bonEntree) => {
  confirm.require({
    message: `Are you sure you want to delete bon entree ${bonEntree.bon_entree_code}?`,
    header: 'Delete Bon Entree',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/purchasing/bon-entrees/${bonEntree.id}`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon entree deleted successfully',
            life: 3000
          })
          fetchBonEntrees()
        }
      } catch (err) {
        console.error('Error deleting bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to delete bon entree',
          life: 3000
        })
      }
    }
  })
}

// Utility functions
const clearSearch = () => {
  filters.search = ''
  applyFilters()
}

const clearAllFilters = () => {
  filters.status = null
  filters.supplier = ''
  filters.dateRange = null
  filters.search = ''
  applyFilters()
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().slice(0, 2)
}

const formatDateRange = (dateRange) => {
  if (!dateRange || !Array.isArray(dateRange)) return ''
  const [start, end] = dateRange
  if (!start || !end) return ''
  return `${formatDate(start)} - ${formatDate(end)}`
}

const isExpiringSoon = (expiryDate) => {
  if (!expiryDate) return false
  const today = new Date()
  const expiry = new Date(expiryDate)
  const diffTime = expiry - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays <= 30 && diffDays >= 0 // Expiring within 30 days
}

const getStatusLabel = (status) => {
  const statusMap = {
    draft: 'Draft',
    validated: 'Validated',
    transferred: 'Transferred',
    cancelled: 'Cancelled'
  }
  return statusMap[status?.toLowerCase()] || status
}

const getStatusSeverity = (status) => {
  const severityMap = {
    draft: 'info',
    validated: 'success',
    transferred: 'warning',
    cancelled: 'danger'
  }
  return severityMap[status?.toLowerCase()] || 'info'
}

const formatCurrency = (amount) => {
  if (!amount) return 'DZD 0.00'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchBonEntrees(),
    fetchStats()
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
  background-color: rgba(34, 197, 94, 0.05);
}

:deep(.medical-table .p-datatable-tbody > tr.p-highlight) {
  background-color: #f0fdf4;
}

:deep(.p-button-sm) {
  font-size: 0.875rem;
  padding: 0.375rem 0.625rem;
}

:deep(.p-tag) {
  font-weight: 600;
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

/* Floating Action Button */
.fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.39);
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
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
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

/* Enhanced DataTable Styling */
:deep(.p-datatable) {
  border: 0;
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  background: white;
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
  border-left: 4px solid #10b981;
  animation: rowHighlight 0.3s ease-out;
}

:deep(.p-datatable .p-datatable-tbody > tr.tw-bg-blue-50) {
  background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
  border-left: 4px solid #3b82f6;
}

/* Avatar Enhancements */
:deep(.p-avatar) {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 2px solid white;
}

:deep(.p-avatar.tw-bg-green-100) {
  border-color: #dcfce7;
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
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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

/* Enhanced Button Styles */
:deep(.enhanced-primary-btn) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: 0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06);
  transition: all 0.2s ease;
}

:deep(.enhanced-primary-btn:hover) {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1), 0 4px 6px -2px rgba(16, 185, 129, 0.05);
  transform: translateY(-1px);
}

:deep(.enhanced-primary-btn:active) {
  transform: translateY(0);
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06);
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

/* Animations */
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

:deep(.p-button.p-button-success) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: 0;
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
</style>
