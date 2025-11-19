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
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-400 tw-rounded-full tw-border-2 tw-border-white tw-animate-pulse"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
                Consignment Dashboard
              </h1>
              <p class="tw-text-slate-600 tw-text-sm tw-mt-1 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Manage consignment receipts, consumption tracking, and invoicing
              </p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-amber-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-clock tw-text-amber-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-amber-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-amber-700 tw-font-medium tw-uppercase tw-tracking-wide">Pending</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-amber-800">
                    {{ stats.pending }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-check-circle tw-text-blue-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-blue-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-blue-700 tw-font-medium tw-uppercase tw-tracking-wide">Active</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">
                    {{ stats.active }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-green-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-dollar tw-text-green-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-green-700 tw-font-medium tw-uppercase tw-tracking-wide">Invoiced</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">
                    {{ stats.invoiced }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-gray-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-slate-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-slate-100 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-chart-line tw-text-slate-600 tw-text-lg"></i>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-slate-700 tw-font-medium tw-uppercase tw-tracking-wide">Total Value</div>
                  <div class="tw-text-lg tw-font-bold tw-text-slate-800">
                    {{ formatCurrency(stats.totalValue) }}
                  </div>
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
              <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-4 tw-flex tw-items-center tw-pointer-events-none">
                <i class="pi pi-search tw-text-slate-400 tw-text-lg"></i>
              </div>
              <InputText
                v-model="filters.search"
                placeholder="Search by consignment code, supplier..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all tw-duration-200 tw-bg-slate-50/50 hover:tw-bg-white"
                @input="debouncedSearch"
              />
              <div v-if="filters.search" class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center">
                <Button
                  @click="clearSearch"
                  icon="pi pi-times"
                  class="p-button-text p-button-sm tw-text-slate-400 hover:tw-text-slate-600"
                  v-tooltip.top="'Clear search'"
                />
              </div>
            </div>

            <!-- Filter Dropdowns -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Dropdown
                v-model="filters.fournisseur_id"
                :options="suppliers"
                optionLabel="nom_fournisseur"
                optionValue="id"
                placeholder="All Suppliers"
                class="tw-min-w-[200px] tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20"
                filter
                filterPlaceholder="Search supplier..."
                @change="applyFilters"
              />

              <Dropdown
                v-model="filters.has_uninvoiced"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Status"
                class="tw-min-w-[160px] tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20"
                @change="applyFilters"
              />

              <!-- Quick Filter Buttons -->
              <div class="tw-flex tw-gap-2">
                <Button
                  @click="setQuickFilter(null)"
                  :class="{ 'p-button-primary': !filters.has_uninvoiced, 'p-button-outlined': filters.has_uninvoiced }"
                  label="All"
                  class="tw-rounded-xl"
                />
                <Button
                  @click="setQuickFilter(true)"
                  :class="{ 'p-button-primary': filters.has_uninvoiced === true, 'p-button-outlined': filters.has_uninvoiced !== true }"
                  label="Uninvoiced"
                  class="tw-rounded-xl"
                />
                <Button
                  @click="setQuickFilter(false)"
                  :class="{ 'p-button-primary': filters.has_uninvoiced === false, 'p-button-outlined': filters.has_uninvoiced !== false }"
                  label="Invoiced"
                  class="tw-rounded-xl"
                />
              </div>
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button
              @click="refreshData"
              icon="pi pi-refresh"
              class="p-button-outlined p-button-secondary p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
              v-tooltip.top="'Refresh data'"
              :loading="loading"
            />
            <Button
              @click="showCreateDialog = true"
              icon="pi pi-plus"
              label="New Consignment"
              class="p-button-primary p-button-lg tw-rounded-xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200 tw-transform hover:tw-scale-105"
            />
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="tw-mt-4 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
          <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Active filters:</span>
          <Tag v-if="filters.search"
               :value="`Search: ${filters.search}`"
               severity="info"
               class="tw-rounded-lg"
               @click="clearSearch" />
          <Tag v-if="filters.fournisseur_id"
               :value="`Supplier: ${getSupplierName(filters.fournisseur_id)}`"
               severity="info"
               class="tw-rounded-lg"
               @click="filters.fournisseur_id = null; applyFilters()" />
          <Tag v-if="filters.has_uninvoiced !== null"
               :value="`Status: ${filters.has_uninvoiced ? 'Uninvoiced' : 'Invoiced'}`"
               severity="info"
               class="tw-rounded-lg"
               @click="filters.has_uninvoiced = null; applyFilters()" />
          <Button @click="clearAllFilters"
                  label="Clear all"
                  class="p-button-text p-button-sm tw-text-slate-500 hover:tw-text-slate-700" />
        </div>
      </div>

      <!-- Enhanced Data Table with Card View Toggle -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <!-- View Toggle and Table Controls -->
        <div class="tw-p-4 tw-border-b tw-border-slate-200/60 tw-bg-slate-50/50">
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900">Consignments List</h3>
              <div class="tw-flex tw-items-center tw-gap-2">
                <Button
                  @click="viewMode = 'table'"
                  :class="{ 'p-button-primary': viewMode === 'table', 'p-button-outlined': viewMode !== 'table' }"
                  icon="pi pi-table"
                  v-tooltip.top="'Table view'"
                />
                <Button
                  @click="viewMode = 'cards'"
                  :class="{ 'p-button-primary': viewMode === 'cards', 'p-button-outlined': viewMode !== 'cards' }"
                  icon="pi pi-th-large"
                  v-tooltip.top="'Card view'"
                />
              </div>
            </div>
            <div class="tw-text-sm tw-text-slate-600">
              {{ selectedConsignments.length }} of {{ consignments.length }} selected
            </div>
          </div>
        </div>

        <!-- Table View -->
        <DataTable
          v-if="viewMode === 'table'"
          v-model:selection="selectedConsignments"
          :value="consignments"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} consignments"
          :rowsPerPageOptions="[10, 25, 50]"
          dataKey="id"
          :loading="loading"
          selectionMode="multiple"
          responsiveLayout="scroll"
          class="p-datatable-sm consignment-table"
          :class="{ 'tw-hidden': loading }"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Consignment Code with Enhanced Design -->
          <Column field="consignment_code" header="Code" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-gradient-to-br tw-from-blue-100 tw-to-indigo-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-box tw-text-blue-600 tw-text-sm"></i>
                  </div>
                  <div v-if="data.total_uninvoiced > 0" class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-amber-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-font-bold tw-text-slate-900">{{ data.consignment_code }}</div>
                  <div class="tw-text-xs tw-text-slate-500">{{ formatDate(data.reception_date) }}</div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Supplier with Avatar -->
          <Column field="fournisseur.nom_fournisseur" header="Supplier" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-gradient-to-br tw-from-slate-400 tw-to-slate-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-sm tw-shadow-sm">
                  {{ getInitials(data.fournisseur?.nom_fournisseur) }}
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-slate-900">{{ data.fournisseur?.nom_fournisseur || 'N/A' }}</div>
                  <div class="tw-text-xs tw-text-slate-500">{{ data.fournisseur?.company_name || '' }}</div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Quantities with Progress -->
          <Column header="Quantities" :sortable="true">
            <template #body="{ data }">
              <div class="tw-space-y-2">
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-slate-600">Received:</span>
                  <span class="tw-font-semibold tw-text-slate-900">{{ data.total_received }}</span>
                </div>
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-orange-600">Consumed:</span>
                  <span class="tw-font-semibold tw-text-orange-700">{{ data.total_consumed }}</span>
                </div>
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-red-600">Uninvoiced:</span>
                  <span class="tw-font-semibold tw-text-red-700">{{ data.total_uninvoiced }}</span>
                </div>
                <div v-if="data.total_received > 0" class="tw-w-full tw-bg-slate-200 tw-rounded-full tw-h-1.5">
                  <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-h-1.5 tw-rounded-full"
                       :style="{ width: getPercentage(data.total_consumed, data.total_received) + '%' }"></div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Progress -->
          <Column header="Progress" :sortable="true">
            <template #body="{ data }">
              <div class="tw-space-y-3">
                <!-- Consumption Progress -->
                <div>
                  <div class="tw-flex tw-justify-between tw-text-xs tw-mb-1">
                    <span class="tw-text-slate-600">Consumed</span>
                    <span class="tw-font-medium tw-text-slate-900">{{ getPercentage(data.total_consumed, data.total_received) }}%</span>
                  </div>
                  <ProgressBar
                    :value="getPercentage(data.total_consumed, data.total_received)"
                    :showValue="false"
                    style="height: 6px"
                    class="tw-rounded-full"
                  />
                </div>
                <!-- Invoice Progress -->
                <div>
                  <div class="tw-flex tw-justify-between tw-text-xs tw-mb-1">
                    <span class="tw-text-slate-600">Invoiced</span>
                    <span class="tw-font-medium tw-text-slate-900">{{ getPercentage(data.total_consumed - data.total_uninvoiced, data.total_consumed) }}%</span>
                  </div>
                  <ProgressBar
                    :value="getPercentage(data.total_consumed - data.total_uninvoiced, data.total_consumed)"
                    :showValue="false"
                    severity="success"
                    style="height: 6px"
                    class="tw-rounded-full"
                  />
                </div>
              </div>
            </template>
          </Column>

          <!-- Status with Badge -->
          <Column header="Status" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-2">
                <Tag
                  :value="getStatusLabel(data)"
                  :severity="getStatusSeverity(data)"
                  class="tw-text-xs tw-font-semibold tw-rounded-lg tw-px-3 tw-py-1"
                />
                <div v-if="data.total_uninvoiced > 0" class="tw-text-xs tw-text-amber-600">
                  {{ data.total_uninvoiced }} items pending invoice
                </div>
              </div>
            </template>
          </Column>

          <!-- Date with Relative Time -->
          <Column field="reception_date" header="Received" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm">
                <div class="tw-font-semibold tw-text-slate-900">{{ formatDateOnly(data.reception_date) }}</div>
                <div class="tw-text-xs tw-text-slate-500">{{ formatTimeOnly(data.reception_date) }}</div>
                <div class="tw-text-xs tw-text-slate-400 tw-mt-1">{{ getRelativeTime(data.reception_date) }}</div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Actions -->
          <Column header="Actions" :exportable="false">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-1">
                <Button
                  @click="viewConsignment(data)"
                  icon="pi pi-eye"
                  size="small"
                  outlined
                  class="p-button-info"
                  v-tooltip.top="'View Details'"
                />

                <Button
                  v-if="data.total_uninvoiced > 0"
                  @click="createInvoice(data)"
                  icon="pi pi-file-invoice"
                  size="small"
                  severity="warning"
                  v-tooltip.top="'Create Invoice'"
                />

                <Button
                  @click="refreshConsignment(data)"
                  icon="pi pi-refresh"
                  size="small"
                  outlined
                  class="p-button-secondary"
                  v-tooltip.top="'Refresh'"
                  :loading="refreshingIds.includes(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Card View -->
        <div v-else class="tw-p-6">
          <div v-if="consignments.length === 0 && !loading" class="tw-text-center tw-py-12">
            <div class="tw-mx-auto tw-w-24 tw-h-24 tw-bg-slate-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mb-4">
              <i class="pi pi-box tw-text-4xl tw-text-slate-400"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">No consignments found</h3>
            <p class="tw-text-slate-500 tw-mb-4">Get started by creating your first consignment</p>
            <Button @click="showCreateDialog = true" icon="pi pi-plus" label="Create Consignment" class="p-button-primary" />
          </div>

          <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div v-for="consignment in paginatedConsignments"
                 :key="consignment.id"
                 class="tw-bg-white tw-border tw-border-slate-200 tw-rounded-xl tw-p-6 tw-shadow-sm hover:tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-transform hover:tw-scale-105">

              <!-- Card Header -->
              <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-relative">
                    <div class="tw-bg-gradient-to-br tw-from-blue-100 tw-to-indigo-100 tw-p-2 tw-rounded-lg">
                      <i class="pi pi-box tw-text-blue-600 tw-text-lg"></i>
                    </div>
                    <div v-if="consignment.total_uninvoiced > 0" class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-amber-400 tw-rounded-full tw-border tw-border-white"></div>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-slate-900">{{ consignment.consignment_code }}</div>
                    <div class="tw-text-xs tw-text-slate-500">{{ formatDate(consignment.reception_date) }}</div>
                  </div>
                </div>
                <Tag :value="getStatusLabel(consignment)"
                     :severity="getStatusSeverity(consignment)"
                     class="tw-rounded-lg" />
              </div>

              <!-- Supplier Info -->
              <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
                <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-slate-400 tw-to-slate-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-xs">
                  {{ getInitials(consignment.fournisseur?.nom_fournisseur) }}
                </div>
                <div>
                  <div class="tw-font-medium tw-text-slate-900">{{ consignment.fournisseur?.nom_fournisseur || 'N/A' }}</div>
                  <div class="tw-text-xs tw-text-slate-500">{{ consignment.fournisseur?.company_name || '' }}</div>
                </div>
              </div>

              <!-- Quantities & Progress -->
              <div class="tw-space-y-4 tw-mb-4">
                <div class="tw-grid tw-grid-cols-3 tw-gap-2 tw-text-sm">
                  <div class="tw-text-center">
                    <div class="tw-text-slate-500">Received</div>
                    <div class="tw-font-bold tw-text-slate-900">{{ consignment.total_received }}</div>
                  </div>
                  <div class="tw-text-center">
                    <div class="tw-text-orange-500">Consumed</div>
                    <div class="tw-font-bold tw-text-orange-700">{{ consignment.total_consumed }}</div>
                  </div>
                  <div class="tw-text-center">
                    <div class="tw-text-red-500">Uninvoiced</div>
                    <div class="tw-font-bold tw-text-red-700">{{ consignment.total_uninvoiced }}</div>
                  </div>
                </div>

                <!-- Progress Bars -->
                <div v-if="consignment.total_received > 0" class="tw-space-y-2">
                  <div>
                    <div class="tw-flex tw-justify-between tw-text-xs tw-mb-1">
                      <span>Consumption</span>
                      <span>{{ getPercentage(consignment.total_consumed, consignment.total_received) }}%</span>
                    </div>
                    <div class="tw-w-full tw-bg-slate-200 tw-rounded-full tw-h-2">
                      <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-h-2 tw-rounded-full"
                           :style="{ width: getPercentage(consignment.total_consumed, consignment.total_received) + '%' }"></div>
                    </div>
                  </div>
                  <div v-if="consignment.total_consumed > 0">
                    <div class="tw-flex tw-justify-between tw-text-xs tw-mb-1">
                      <span>Invoicing</span>
                      <span>{{ getPercentage(consignment.total_consumed - consignment.total_uninvoiced, consignment.total_consumed) }}%</span>
                    </div>
                    <div class="tw-w-full tw-bg-slate-200 tw-rounded-full tw-h-2">
                      <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-h-2 tw-rounded-full"
                           :style="{ width: getPercentage(consignment.total_consumed - consignment.total_uninvoiced, consignment.total_consumed) + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card Actions -->
              <div class="tw-flex tw-flex-wrap tw-gap-2">
                <Button @click="viewConsignment(consignment)"
                        icon="pi pi-eye"
                        label="View"
                        class="p-button-outlined p-button-info p-button-sm" />
                <Button v-if="consignment.total_uninvoiced > 0"
                        @click="createInvoice(consignment)"
                        icon="pi pi-file-invoice"
                        label="Invoice"
                        class="p-button-warning p-button-sm" />
              </div>
            </div>
          </div>

          <!-- Card View Pagination -->
          <div class="tw-mt-6 tw-flex tw-justify-center">
            <div class="tw-flex tw-items-center tw-gap-2">
              <Button @click="currentPage = Math.max(1, currentPage - 1)"
                      :disabled="currentPage === 1"
                      icon="pi pi-chevron-left"
                      class="p-button-outlined p-button-secondary" />
              <span class="tw-text-sm tw-text-slate-600 tw-mx-4">
                Page {{ currentPage }} of {{ totalPages }}
              </span>
              <Button @click="currentPage = Math.min(totalPages, currentPage + 1)"
                      :disabled="currentPage === totalPages"
                      icon="pi pi-chevron-right"
                      class="p-button-outlined p-button-secondary" />
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
          <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
            <div class="tw-animate-spin tw-rounded-full tw-h-8 tw-w-8 tw-border-b-2 tw-border-blue-600"></div>
            <p class="tw-text-slate-600">Loading consignments...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Real-time notification toast -->
    <Toast position="top-right" />

    <!-- Create Consignment Dialog -->
    <ConsignmentCreateDialog
        v-model:visible="showCreateDialog"
        @created="onConsignmentCreated"
    />

    <!-- View Consignment Dialog -->
    <ConsignmentViewDialog
        v-model:visible="showViewDialog"
        :consignment-id="selectedConsignmentId"
        @invoice-created="onInvoiceCreated"
    />

    <!-- Floating Action Button -->
    <button
      @click="showCreateDialog = true"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Consignment'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import ProgressBar from 'primevue/progressbar';
import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import ConsignmentCreateDialog from './ConsignmentCreateDialog.vue';
import ConsignmentViewDialog from './ConsignmentViewDialog.vue';
import consignmentService from '@/services/Purchasing/consignmentService';
import supplierService from '@/services/Purchasing/supplierService';

const toast = useToast();
const consignments = ref([]);
const suppliers = ref([]);
const loading = ref(false);
const showCreateDialog = ref(false);
const showViewDialog = ref(false);
const selectedConsignmentId = ref(null);
const selectedConsignments = ref([]);
const echoChannel = ref(null);
const refreshingIds = ref([]);

// View Mode
const viewMode = ref('table') // 'table' or 'cards'
const currentPage = ref(1)
const itemsPerPage = ref(12)

// Filters
const filters = reactive({
    fournisseur_id: null,
    date_from: null,
    date_to: null,
    has_uninvoiced: null,
    search: ''
});

// Stats
const stats = reactive({
    pending: 0,
    active: 0,
    invoiced: 0,
    totalValue: 0
});

// Search debounce
let searchTimeout = null

// Computed Properties
const hasActiveFilters = computed(() => {
    return filters.fournisseur_id || filters.has_uninvoiced !== null || filters.search
})

const paginatedConsignments = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return consignments.value.slice(start, end)
})

const totalPages = computed(() => {
    return Math.ceil(consignments.value.length / itemsPerPage.value)
})

const statusOptions = [
    { label: 'All Status', value: null },
    { label: 'Has Uninvoiced', value: true },
    { label: 'Fully Invoiced', value: false }
]

// API Methods
const loadConsignments = async () => {
    loading.value = true;
    try {
        const response = await consignmentService.getAll(filters);
        consignments.value = response.data;
        calculateStats();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load consignments',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const loadSuppliers = async () => {
    try {
        const response = await supplierService.getAll();
        suppliers.value = response.data;
    } catch (error) {
        console.error('Failed to load suppliers:', error);
    }
};

const calculateStats = () => {
    const cons = consignments.value;
    stats.pending = cons.filter(c => c.total_uninvoiced > 0).length;
    stats.active = cons.filter(c => c.total_consumed > 0 && c.total_uninvoiced > 0).length;
    stats.invoiced = cons.filter(c => c.total_uninvoiced === 0 && c.total_consumed > 0).length;
    stats.totalValue = cons.reduce((sum, c) => sum + (c.total_received * (c.unit_price || 0)), 0);
};

const applyFilters = async () => {
    currentPage.value = 1;
    await loadConsignments();
};

const refreshData = async () => {
    await Promise.all([loadConsignments(), loadSuppliers()]);
    toast.add({
        severity: 'success',
        summary: 'Refreshed',
        detail: 'Data refreshed successfully',
        life: 2000
    });
};

const viewConsignment = (consignment) => {
    selectedConsignmentId.value = consignment.id;
    showViewDialog.value = true;
};

const createInvoice = (consignment) => {
    selectedConsignmentId.value = consignment.id;
    showViewDialog.value = true;
    // The dialog will handle invoice creation
};

const refreshConsignment = async (consignment) => {
    refreshingIds.value.push(consignment.id);
    try {
        const response = await consignmentService.getById(consignment.id);
        const index = consignments.value.findIndex(c => c.id === consignment.id);
        if (index !== -1) {
            consignments.value[index] = response.data;
        }
        calculateStats();
        toast.add({
            severity: 'success',
            summary: 'Refreshed',
            detail: `${consignment.consignment_code} refreshed`,
            life: 2000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to refresh consignment',
            life: 3000
        });
    } finally {
        refreshingIds.value = refreshingIds.value.filter(id => id !== consignment.id);
    }
};

const onConsignmentCreated = (newConsignment) => {
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Consignment ${newConsignment.consignment_code} created`,
        life: 3000
    });
    loadConsignments();
    showCreateDialog.value = false;
};

const onInvoiceCreated = () => {
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Invoice created successfully',
        life: 3000
    });
    loadConsignments();
};

// Enhanced Methods
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

const clearSearch = () => {
    filters.search = '';
    applyFilters();
};

const setQuickFilter = (status) => {
    filters.has_uninvoiced = status;
    applyFilters();
};

const clearAllFilters = () => {
    filters.fournisseur_id = null;
    filters.has_uninvoiced = null;
    filters.search = '';
    applyFilters();
};

const getSupplierName = (supplierId) => {
    const supplier = suppliers.value.find(s => s.id === supplierId);
    return supplier ? supplier.nom_fournisseur : 'Unknown';
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().slice(0, 2);
};

const getRelativeTime = (date) => {
    if (!date) return '';
    const now = new Date();
    const past = new Date(date);
    const diffInSeconds = Math.floor((now - past) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
    return formatDateOnly(date);
};

// Utility Functions
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateOnly = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatTimeOnly = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount || 0);
};

const getPercentage = (value, total) => {
    if (!total || total === 0) return 0;
    return Math.round((value / total) * 100);
};

const getStatusLabel = (consignment) => {
    if (consignment.total_uninvoiced === 0 && consignment.total_consumed > 0) {
        return 'Fully Invoiced';
    } else if (consignment.total_uninvoiced > 0) {
        return 'Has Uninvoiced';
    } else if (consignment.total_consumed === 0) {
        return 'Not Started';
    }
    return 'Active';
};

const getStatusSeverity = (consignment) => {
    if (consignment.total_uninvoiced === 0 && consignment.total_consumed > 0) {
        return 'success';
    } else if (consignment.total_uninvoiced > 0) {
        return 'warning';
    } else if (consignment.total_consumed === 0) {
        return 'info';
    }
    return 'primary';
};

// WebSocket real-time updates
const setupWebSocket = () => {
    if (!window.Echo) {
        console.warn('WebSocket (Echo) not available. Real-time updates disabled.');
        return;
    }

    // Listen to all supplier channels if no filter, or specific supplier
    const supplierId = filters.fournisseur_id;

    if (supplierId) {
        echoChannel.value = window.Echo.private(`consignments.${supplierId}`);

        echoChannel.value
            .listen('.consignment.received', (event) => {
                toast.add({
                    severity: 'info',
                    summary: 'New Consignment Received',
                    detail: `${event.consignment_code} - ${event.total_items} items`,
                    life: 5000
                });
                loadConsignments();
            })
            .listen('.consignment.consumption', (event) => {
                toast.add({
                    severity: 'warning',
                    summary: 'Item Consumed',
                    detail: `${event.product_name} - Qty: ${event.quantity_consumed}`,
                    life: 4000
                });
                // Update the specific consignment in the list
                updateConsignmentInList(event.consignment_id);
            });
    }
};

const updateConsignmentInList = async (consignmentId) => {
    try {
        const response = await consignmentService.getById(consignmentId);
        const index = consignments.value.findIndex(c => c.id === consignmentId);
        if (index !== -1) {
            consignments.value[index] = response.data;
            calculateStats();
        }
    } catch (error) {
        console.error('Failed to update consignment:', error);
    }
};

const teardownWebSocket = () => {
    if (echoChannel.value) {
        echoChannel.value.stopListening('.consignment.received');
        echoChannel.value.stopListening('.consignment.consumption');
        window.Echo.leave(echoChannel.value.name);
    }
};

onMounted(() => {
    loadConsignments();
    loadSuppliers();
    setupWebSocket();
});

onUnmounted(() => {
    teardownWebSocket();
});
</script>

<style scoped>
/* Enhanced Medical Table Styles */
:deep(.consignment-table .p-datatable-header) {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

:deep(.consignment-table .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  color: #374151;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 0.75rem;
}

:deep(.consignment-table .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s ease;
}

:deep(.consignment-table .p-datatable-tbody > tr:hover) {
  background-color: rgba(59, 130, 246, 0.05);
}

:deep(.consignment-table .p-datatable-tbody > tr.p-highlight) {
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

/* Progress bar customizations */
:deep(.p-progressbar .p-progressbar-value) {
  border-radius: 9999px;
}

:deep(.p-progressbar) {
  border-radius: 9999px;
  height: 8px;
}

/* Enhanced dropdown styling */
:deep(.p-dropdown) {
  border-radius: 0.75rem;
}

:deep(.p-dropdown:not(.p-disabled):hover) {
  border-color: #3b82f6;
}

:deep(.p-dropdown:not(.p-disabled).p-focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

/* Tag customizations */
:deep(.p-tag) {
  border-radius: 0.5rem;
  font-weight: 600;
}

/* Button enhancements */
:deep(.p-button) {
  border-radius: 0.75rem;
  transition: all 0.2s ease;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

:deep(.p-button.p-button-primary) {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: none;
}

:deep(.p-button.p-button-primary:hover) {
  background: linear-gradient(135deg, #2563eb, #1e40af);
}

/* Input field enhancements */
:deep(.p-inputtext) {
  border-radius: 0.75rem;
  transition: all 0.2s ease;
}

:deep(.p-inputtext:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

/* Calendar enhancements */
:deep(.p-calendar) {
  border-radius: 0.75rem;
}

:deep(.p-calendar input) {
  border-radius: 0.75rem;
}

/* Toast positioning */
:deep(.p-toast) {
  z-index: 9999;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .tw-px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .tw-py-6 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
  }

  .fab {
    bottom: 1rem;
    right: 1rem;
    width: 3rem;
    height: 3rem;
  }
}
</style>
