<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
                <i class="pi pi-file-pdf tw-text-2xl tw-text-white"></i>
              </div>
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-400 tw-rounded-full tw-border-2 tw-border-white tw-animate-pulse"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-tracking-tight">Proforma Invoices</h1>
              <p class="tw-text-slate-600 tw-mt-1">Manage and track your proforma invoice requests</p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                  <i class="pi pi-file tw-text-blue-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-blue-700 tw-font-medium tw-uppercase tw-tracking-wider">Total</p>
                  <p class="tw-text-xl tw-font-bold tw-text-blue-800">{{ totalRecords }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-green-100 tw-rounded-lg">
                  <i class="pi pi-check-circle tw-text-green-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-green-700 tw-font-medium tw-uppercase tw-tracking-wider">Active</p>
                  <p class="tw-text-xl tw-font-bold tw-text-green-800">{{ proformas.filter(p => p.status === 'approved').length }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-yellow-50 tw-to-amber-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-yellow-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-yellow-100 tw-rounded-lg">
                  <i class="pi pi-clock tw-text-yellow-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-yellow-700 tw-font-medium tw-uppercase tw-tracking-wider">Pending</p>
                  <p class="tw-text-xl tw-font-bold tw-text-yellow-800">{{ proformas.filter(p => p.status === 'draft' || p.status === 'sent').length }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-red-50 tw-to-rose-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-red-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-red-100 tw-rounded-lg">
                  <i class="pi pi-times-circle tw-text-red-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-red-700 tw-font-medium tw-uppercase tw-tracking-wider">Cancelled</p>
                  <p class="tw-text-xl tw-font-bold tw-text-red-800">{{ proformas.filter(p => p.status === 'cancelled').length }}</p>
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
            <div class="tw-relative tw-flex-1">
              <InputText
                v-model="filters.search"
                @input="debouncedSearch"
                placeholder="Search proformas by code, supplier..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-rounded-xl tw-border-2 tw-border-slate-200 hover:tw-border-indigo-300 focus:tw-border-indigo-500 focus:tw-ring-4 focus:tw-ring-indigo-100 tw-transition-all tw-duration-200"
              />
              <div class="tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400">
                <i class="pi pi-search tw-text-lg"></i>
              </div>
              <div v-if="filters.search" class="tw-absolute tw-right-4 tw-top-1/2 tw--translate-y-1/2">
                <Button
                  @click="clearSearch"
                  icon="pi pi-times"
                  class="p-button-text p-button-sm tw-text-slate-400 hover:tw-text-slate-600"
                  v-tooltip="'Clear search'"
                />
              </div>
            </div>

            <!-- Status Filter -->
            <div class="tw-relative">
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                showClear
                class="tw-w-48 tw-rounded-xl tw-border-2 tw-border-slate-200 hover:tw-border-indigo-300 focus:tw-border-indigo-500 tw-transition-all tw-duration-200"
                @change="applyFilters"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-py-1">
                    <div :class="`tw-w-3 tw-h-3 tw-rounded-full ${getStatusColor(slotProps.option.value)}`"></div>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
              </Dropdown>
            </div>

            <!-- Supplier Filter -->
            <div class="tw-relative">
              <Dropdown
                v-model="filters.fournisseur_id"
                :options="suppliers"
                optionLabel="company_name"
                optionValue="id"
                placeholder="All Suppliers"
                showClear
                filter
                class="tw-w-48 tw-rounded-xl tw-border-2 tw-border-slate-200 hover:tw-border-indigo-300 focus:tw-border-indigo-500 tw-transition-all tw-duration-200"
                @change="applyFilters"
              >
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-py-1">
                    <Avatar
                      :label="slotProps.option.company_name?.charAt(0)"
                      class="tw-bg-indigo-100 tw-text-indigo-700"
                      size="small"
                      shape="circle"
                    />
                    <span>{{ slotProps.option.company_name }}</span>
                  </div>
                </template>
              </Dropdown>
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button
              @click="switchToBonCommend"
              :disabled="buttonClicked"
              icon="pi pi-arrow-right"
              :label="buttonClicked ? 'Already Used' : 'Switch to Bon Commande'"
              class="p-button-secondary tw-shadow-lg hover:tw-scale-105 tw-transition-transform tw-rounded-xl"
              outlined
            />
            <Button
              @click="generateAllPdfs"
              :disabled="!selectedProformas.length || generatingPdf"
              :loading="generatingPdf"
              icon="pi pi-file-pdf"
              label="Export PDFs"
              class="tw-bg-gradient-to-r tw-from-emerald-500 tw-to-green-600 hover:tw-from-emerald-600 hover:tw-to-green-700 tw-text-white tw-border-none tw-shadow-lg hover:tw-scale-105 tw-transition-all tw-duration-300 tw-rounded-xl"
              :badge="selectedProformas.length ? String(selectedProformas.length) : null"
            />
            <Button
              @click="refreshData"
              icon="pi pi-refresh"
              label="Refresh"
              class="p-button-info tw-shadow-lg hover:tw-scale-105 tw-transition-transform tw-rounded-xl"
            />
            <Button
              @click="createFactureProforma"
              icon="pi pi-plus"
              label="New Proforma"
              class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-600 hover:tw-from-indigo-700 hover:tw-to-purple-700 tw-text-white tw-border-none tw-shadow-lg hover:tw-scale-105 tw-transition-all tw-duration-300 tw-rounded-xl"
            />
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="tw-mt-4 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
          <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Active filters:</span>
          <Tag v-if="filters.search"
               :value="`Search: ${filters.search}`"
               severity="info"
               class="tw-cursor-pointer hover:tw-bg-blue-100"
               @click="clearSearch" />
          <Tag v-if="filters.status"
               :value="`Status: ${getStatusLabel(filters.status)}`"
               :severity="getStatusSeverity(filters.status)"
               class="tw-cursor-pointer"
               @click="filters.status = null; applyFilters()" />
          <Tag v-if="filters.fournisseur_id"
               :value="`Supplier: ${getSupplierName(filters.fournisseur_id)}`"
               severity="success"
               class="tw-cursor-pointer"
               @click="filters.fournisseur_id = null; applyFilters()" />
          <Button @click="clearAllFilters"
                  label="Clear All"
                  class="p-button-text p-button-sm tw-text-slate-500 hover:tw-text-slate-700" />
        </div>
      </div>

      <!-- Enhanced Data Table with Card View Toggle -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <!-- View Toggle and Table Controls -->
        <div class="tw-p-4 tw-border-b tw-border-slate-200/60 tw-bg-slate-50/50">
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <span class="tw-text-sm tw-text-slate-600 tw-font-medium">View:</span>
              <div class="tw-flex tw-bg-slate-100 tw-rounded-lg tw-p-1">
                <Button
                  icon="pi pi-list"
                  :class="viewMode === 'table' ? 'p-button-primary' : 'p-button-text'"
                  @click="viewMode = 'table'"
                  size="small"
                  v-tooltip="'Table View'"
                />
                <Button
                  icon="pi pi-th-large"
                  :class="viewMode === 'cards' ? 'p-button-primary' : 'p-button-text'"
                  @click="viewMode = 'cards'"
                  size="small"
                  v-tooltip="'Card View'"
                />
              </div>
            </div>

            <div class="tw-flex tw-items-center tw-gap-4">
              <span class="tw-text-sm tw-text-slate-600">{{ selectedProformas.length }} selected</span>
              <Button
                v-if="selectedProformas.length > 0"
                @click="selectedProformas = []"
                label="Clear Selection"
                class="p-button-text p-button-sm"
              />
            </div>
          </div>
        </div>

        <!-- Table View -->
        <DataTable
          v-if="viewMode === 'table'"
          v-model="selectedProformas"
          :value="proformas"
          :loading="loading"
          dataKey="id"
          :paginator="true"
          :rows="10"
          :rowsPerPageOptions="[10, 25, 50]"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} proformas"
          responsiveLayout="scroll"
          selectionMode="multiple"
          class="medical-table"
          :rowClass="rowClass"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Code Column with Enhanced Design -->
          <Column field="factureProformaCode" header="Code" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-2 tw-h-10 tw-bg-gradient-to-b tw-from-indigo-500 tw-to-purple-500 tw-rounded-full tw-shadow-sm"></div>
                <div>
                  <span class="tw-font-bold tw-text-indigo-700 tw-font-mono tw-text-lg">
                    {{ data.factureProformaCode || `FP-${data.id}` }}
                  </span>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mt-1">
                    <Badge
                      :value="getStatusLabel(data.status)"
                      :severity="getStatusSeverity(data.status)"
                      class="tw-text-xs tw-font-semibold"
                    />
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Supplier Column with Avatar -->
          <Column field="fournisseur.company_name" header="Supplier" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-4">
                <Avatar
                  :label="data.fournisseur?.company_name?.charAt(0)"
                  class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-500 tw-text-white tw-shadow-lg"
                  size="large"
                  shape="circle"
                />
                <div>
                  <div class="tw-font-semibold tw-text-gray-900 tw-text-base">
                    {{ data.fournisseur?.company_name || 'N/A' }}
                  </div>
                  <div class="tw-text-sm tw-text-purple-600 tw-flex tw-items-center tw-gap-1" v-if="data.fournisseur?.contact_person">
                    <i class="pi pi-user"></i>
                    {{ data.fournisseur.contact_person }}
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Products Column with Progress -->
          <Column header="Products" class="tw-min-w-48">
            <template #body="{ data }">
              <div v-if="data.products?.length" class="tw-space-y-2">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Tag
                    :value="`${data.products.length} items`"
                    class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-500 tw-text-white tw-font-semibold tw-shadow-sm"
                  />
                  <div class="tw-text-xs tw-text-slate-500">
                    Qty: {{ data.products.reduce((sum, p) => sum + (p.quantity || 0), 0) }}
                  </div>
                </div>
                <ProgressBar
                  :value="getProformaProgress(data.status)"
                  :showValue="false"
                  class="tw-h-1"
                  style="height: 4px"
                />
                <div class="tw-text-xs tw-text-center tw-text-slate-500">
                  {{ getProgressLabel(data.status) }}
                </div>
              </div>
              <div v-else class="tw-text-sm tw-text-gray-400 tw-italic tw-flex tw-items-center tw-gap-1">
                <i class="pi pi-info-circle"></i>
                No products
              </div>
            </template>
          </Column>

          <!-- Created Column with Relative Time -->
          <Column field="created_at" header="Created" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                  <i class="pi pi-calendar tw-text-blue-600"></i>
                </div>
                <div>
                  <span class="tw-text-sm tw-font-semibold tw-text-gray-900">{{ formatDate(data.created_at) }}</span>
                  <div class="tw-text-xs tw-text-gray-500">{{ getRelativeTime(data.created_at) }}</div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Actions Column -->
          <Column header="Actions" frozen alignFrozen="right" class="tw-min-w-32">
            <template #body="{ data }">
              <div class="tw-flex tw-justify-center">
                <SplitButton
                  :model="getActionMenuItems(data)"
                  icon="pi pi-cog"
                  class="p-button-text tw-text-slate-600 hover:tw-bg-slate-50 tw-rounded-lg tw-transition-all tw-duration-200"
                  @click="editProformaItems(data)"
                  v-tooltip.top="'Actions'"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Card View -->
        <div v-else class="tw-p-6">
          <div v-if="proformas.length === 0 && !loading" class="tw-text-center tw-py-12">
            <div class="tw-mb-6">
              <div class="tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-inbox tw-text-4xl tw-text-gray-400"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">No Proforma Invoices Found</h3>
              <p class="tw-text-gray-500 tw-max-w-md tw-mx-auto">
                Get started by creating your first proforma invoice. Click the button below to begin.
              </p>
            </div>
            <Button
              @click="createFactureProforma"
              icon="pi pi-plus"
              label="Create Your First Proforma"
              class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-600 hover:tw-from-indigo-700 hover:tw-to-purple-700 tw-border-none tw-rounded-xl tw-font-semibold tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105"
            />
          </div>

          <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div
              v-for="proforma in paginatedProformas"
              :key="proforma.id"
              class="tw-bg-white tw-rounded-xl tw-shadow-md hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-border tw-border-slate-200/60 tw-overflow-hidden card-hover"
            >
              <!-- Card Header -->
              <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-500 tw-p-4">
                <div class="tw-flex tw-justify-between tw-items-start">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <Avatar
                      :label="proforma.fournisseur?.company_name?.charAt(0)"
                      class="tw-bg-white/20 tw-text-white tw-border-2 tw-border-white/30"
                      size="large"
                      shape="circle"
                    />
                    <div>
                      <h3 class="tw-text-white tw-font-bold tw-text-lg">
                        {{ proforma.factureProformaCode || `FP-${proforma.id}` }}
                      </h3>
                      <p class="tw-text-indigo-100 tw-text-sm">{{ proforma.fournisseur?.company_name }}</p>
                    </div>
                  </div>
                  <div class="tw-flex tw-gap-1">
                    <Checkbox
                      :modelValue="isSelected(proforma)"
                      binary
                      @update:modelValue="(value) => toggleSelection(proforma, value)"
                      class="tw-w-5 tw-h-5"
                    />
                  </div>
                </div>
              </div>

              <!-- Card Content -->
              <div class="tw-p-4 tw-space-y-4">
                <!-- Status and Date -->
                <div class="tw-flex tw-justify-between tw-items-center">
                  <Badge
                    :value="getStatusLabel(proforma.status)"
                    :severity="getStatusSeverity(proforma.status)"
                    class="tw-font-semibold"
                  />
                  <span class="tw-text-xs tw-text-slate-500">{{ getRelativeTime(proforma.created_at) }}</span>
                </div>

                <!-- Products Info -->
                <div v-if="proforma.products?.length" class="tw-space-y-2">
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-sm tw-text-slate-600">Products</span>
                    <Tag :value="`${proforma.products.length} items`" severity="info" class="tw-text-xs" />
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-sm tw-text-slate-600">Total Quantity</span>
                    <span class="tw-font-semibold">{{ proforma.products.reduce((sum, p) => sum + (p.quantity || 0), 0) }}</span>
                  </div>
                  <ProgressBar
                    :value="getProformaProgress(proforma.status)"
                    :showValue="false"
                    class="tw-h-2"
                  />
                  <div class="tw-text-xs tw-text-center tw-text-slate-500">
                    {{ getProgressLabel(proforma.status) }}
                  </div>
                </div>

                <!-- Actions -->
                <div class="tw-flex tw-gap-2 tw-pt-2 tw-border-t tw-border-slate-200">
                  <Button
                    @click="editProformaItems(proforma)"
                    icon="pi pi-pencil"
                    class="p-button-text p-button-sm tw-flex-1"
                    v-tooltip="'Edit Proforma'"
                  />
                  <Button
                    @click="generatePdf(proforma)"
                    icon="pi pi-file-pdf"
                    class="p-button-text p-button-sm"
                    v-tooltip="'Download PDF'"
                  />
                  <SplitButton
                    :model="getActionMenuItems(proforma)"
                    icon="pi pi-ellipsis-v"
                    class="p-button-text p-button-sm"
                    v-tooltip="'More Actions'"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Card View Pagination -->
          <div class="tw-mt-6 tw-flex tw-justify-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <Button
                @click="currentPage = Math.max(1, currentPage - 1)"
                :disabled="currentPage === 1"
                icon="pi pi-chevron-left"
                class="p-button-text"
              />
              <span class="tw-text-sm tw-text-slate-600">
                Page {{ currentPage }} of {{ totalPages }}
              </span>
              <Button
                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                :disabled="currentPage === totalPages"
                icon="pi pi-chevron-right"
                class="p-button-text"
              />
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
          <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-w-20 tw-h-20 tw-border-4 tw-border-indigo-200 tw-rounded-full"></div>
              <div class="tw-w-20 tw-h-20 tw-border-4 tw-border-indigo-600 tw-border-t-transparent tw-rounded-full tw-animate-spin tw-absolute tw-top-0"></div>
            </div>
            <p class="tw-mt-4 tw-text-indigo-600 tw-font-semibold tw-text-lg">Loading proforma invoices...</p>
            <p class="tw-text-gray-500 tw-mt-2">Please wait while we fetch your data</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Cancellation Dialog -->
    <Dialog
      v-model="confirmationDialog"
      modal
      header="Cancel Proforma Workflow"
      :style="{ width: '35rem' }"
      class="tw-rounded-2xl"
    >
      <div class="tw-space-y-6">
        <div class="tw-text-center">
          <div class="tw-w-20 tw-h-20 tw-bg-gradient-to-br tw-from-orange-100 tw-to-red-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
            <i class="pi pi-question-circle tw-text-4xl tw-text-orange-500"></i>
          </div>
          <h3 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-3">
            Cancel Proforma Workflow
          </h3>
          <p class="tw-text-gray-600 tw-leading-relaxed">
            Are you sure you want to cancel this proforma and mark the workflow as cancelled?
            This action cannot be undone and will permanently affect the workflow.
          </p>
        </div>

        <div v-if="selectedProforma" class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-blue-50 tw-p-6 tw-rounded-xl tw-border tw-border-gray-200">
          <h4 class="tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-info-circle tw-text-blue-500"></i>
            Proforma Details
          </h4>
          <div class="tw-space-y-3">
            <div class="tw-flex tw-justify-between tw-items-center">
              <span class="tw-font-medium tw-text-gray-600">Code:</span>
              <span class="tw-font-mono tw-font-bold tw-text-indigo-700">{{ selectedProforma.factureProformaCode || `FP-${selectedProforma.id}` }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center">
              <span class="tw-font-medium tw-text-gray-600">Supplier:</span>
              <span class="tw-font-semibold tw-text-gray-900">{{ selectedProforma.fournisseur?.company_name }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center">
              <span class="tw-font-medium tw-text-gray-600">Status:</span>
              <Badge value="Will be Cancelled" severity="danger" />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-3 tw-justify-end">
          <Button
            @click="confirmationDialog = false"
            label="Keep Proforma"
            outlined
            class="tw-rounded-xl tw-border-gray-300 hover:tw-bg-gray-50"
          />
          <Button
            @click="cancelProformaWorkflow"
            :loading="confirming"
            label="Cancel Proforma"
            severity="danger"
            class="tw-rounded-xl tw-font-semibold tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-scale-105"
          />
        </div>
      </template>
    </Dialog>

    <!-- Actions Menu -->
    <Menu ref="actionMenu" :model="actionMenuItems" :popup="true" class="tw-rounded-xl tw-shadow-xl" />

    <ConfirmDialog />
    <Toast />

    <!-- Floating Action Button -->
    <button
      @click="createFactureProforma"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Proforma'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>
  </div>
</template>

<script setup>
// ALL IMPORTS AND SCRIPT CONTENT REMAIN EXACTLY THE SAME
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import ConfirmDialog from 'primevue/confirmdialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Avatar from 'primevue/avatar'
import Checkbox from 'primevue/checkbox'
import Menu from 'primevue/menu'
import ProgressBar from 'primevue/progressbar'
import SplitButton from 'primevue/splitbutton'
import axios from 'axios'

const toast = useToast()
const confirm = useConfirm()
const router = useRouter()

const loading = ref(true)
const generatingPdf = ref(false)
const proformas = ref([])
const suppliers = ref([])
const selectedProformas = ref([])
const selectedProforma = ref(null)
const confirmationDialog = ref(false)
const confirming = ref(false)
const totalRecords = ref(0)
const buttonClicked = ref(localStorage.getItem('facture_proforma_used') === 'true')
const selectAll = ref(false)
const actionMenu = ref()
const currentActionProforma = ref(null)

// New reactive variables for enhanced UI
const viewMode = ref('table') // 'table' or 'cards'
const currentPage = ref(1)
const itemsPerPage = ref(9) // For card view pagination

const filters = reactive({
  status: null,
  fournisseur_id: null,
  search: ''
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Completed', value: 'completed' }
]

const actionMenuItems = ref([])

// Computed properties for enhanced UI
const hasActiveFilters = computed(() => {
  return filters.status || filters.fournisseur_id || filters.search
})

const paginatedProformas = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return proformas.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(proformas.value.length / itemsPerPage.value)
})

// Enhanced UI methods
const debouncedSearch = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      applyFilters()
    }, 300)
  }
})()

const clearSearch = () => {
  filters.search = ''
  applyFilters()
}

const getSupplierName = (supplierId) => {
  const supplier = suppliers.value.find(s => s.id === supplierId)
  return supplier?.company_name || 'Unknown Supplier'
}

const getRelativeTime = (date) => {
  if (!date) return 'N/A'
  const now = new Date()
  const past = new Date(date)
  const diffInSeconds = Math.floor((now - past) / 1000)

  if (diffInSeconds < 60) return 'Just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
  return formatDate(date)
}

const getActionMenuItems = (proforma) => {
  return [
    {
      label: 'Edit Proforma',
      icon: 'pi pi-pencil',
      command: () => editProformaItems(proforma)
    },
    {
      label: 'Convert to Bon Commande',
      icon: 'pi pi-arrow-right',
      command: () => convertToBonCommande(proforma),
      visible: proforma.status === 'approved'
    },
    {
      label: 'Export PDF',
      icon: 'pi pi-file-pdf',
      command: () => generatePdf(proforma)
    },
    {
      separator: true
    },
    {
      label: 'Cancel Proforma',
      icon: 'pi pi-times',
      command: () => cancelProforma(proforma),
      visible: proforma.status !== 'cancelled' && proforma.status !== 'factureprofram',
      style: 'color: #ea580c'
    },
    {
      label: 'Cancel Workflow',
      icon: 'pi pi-ban',
      command: () => showConfirmationDialog(proforma),
      visible: proforma.status !== 'cancelled' && proforma.status !== 'factureprofram',
      style: 'color: #dc2626'
    },
    {
      separator: true,
      visible: proforma.status === 'draft'
    },
    {
      label: 'Delete',
      icon: 'pi pi-trash',
      command: () => deleteProforma(proforma),
      visible: proforma.status === 'draft',
      style: 'color: #dc2626'
    }
  ]
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'tw-bg-blue-100 tw-text-blue-600',
    sent: 'tw-bg-yellow-100 tw-text-yellow-600',
    approved: 'tw-bg-green-100 tw-text-green-600',
    rejected: 'tw-bg-red-100 tw-text-red-600',
    completed: 'tw-bg-green-100 tw-text-green-600',
    cancelled: 'tw-bg-gray-100 tw-text-gray-600'
  }
  return colors[status] || 'tw-bg-gray-100 tw-text-gray-600'
}

const getProformaProgress = (status) => {
  const progressMap = {
    draft: 20,
    sent: 40,
    approved: 60,
    completed: 100,
    rejected: 0,
    cancelled: 0
  }
  return progressMap[status] || 0
}

const getProgressLabel = (status) => {
  const labels = {
    draft: 'Draft',
    sent: 'Sent',
    approved: 'Approved',
    completed: 'Completed',
    rejected: 'Rejected',
    cancelled: 'Cancelled'
  }
  return labels[status] || status
}

const clearAllFilters = () => {
  filters.status = null
  filters.fournisseur_id = null
  filters.search = ''
  applyFilters()
}

const normalizePaginator = (payload) => {
  if (!payload) return { data: [], total: 0 }
  if (payload.status && payload.data) payload = payload.data
  if (Array.isArray(payload.data)) {
    const items = payload.data
    const total = payload.total || payload.totalRecords || (payload.meta?.total) || items.length
    return { data: items, total }
  }
  if (Array.isArray(payload)) return { data: payload, total: payload.length }
  if (payload.data?.data && Array.isArray(payload.data.data)) {
    const items = payload.data.data
    const total = payload.data.total || payload.total || items.length
    return { data: items, total }
  }
  const items = payload.data || payload.items || []
  const total = payload.total || items.length
  return { data: Array.isArray(items) ? items : [], total }
}

const fetchProformas = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    if (filters.status) params.append('status', filters.status)
    if (filters.fournisseur_id) params.append('fournisseur_id', filters.fournisseur_id)
    if (filters.search) params.append('search', filters.search)

    const response = await axios.get(`/api/facture-proformas?${params.toString()}`)
    const normalized = normalizePaginator(response.data)
    proformas.value = normalized.data
    totalRecords.value = normalized.total
    
    // Update select all state after data changes
    updateSelectAllState()
  } catch (err) {
    console.error('Error fetching proformas:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load facture proformas',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    suppliers.value = response.data.status === 'success' 
      ? response.data.data 
      : (Array.isArray(response.data) ? response.data : [])
  } catch (err) {
    console.error('Error fetching suppliers:', err)
  }
}

const applyFilters = () => {
  selectedProformas.value = [] // Clear selection when applying filters
  selectAll.value = false
  fetchProformas()
}

const refreshData = async () => {
  await Promise.all([fetchProformas(), fetchSuppliers()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const editProformaItems = (proforma) => {
  router.push({ 
    name: 'purchasing.facture-proforma.edit', 
    params: { id: proforma.id } 
  })
}

const showConfirmationDialog = (proforma) => {
  selectedProforma.value = proforma
  confirmationDialog.value = true
}

const cancelProformaWorkflow = async () => {
  try {
    confirming.value = true
    const response = await axios.post(
      `/api/facture-proformas/${selectedProforma.value.id}/cancel`
    )

    if (response.data.success || response.data.status === 'success') {
      toast.add({ 
        severity: 'success', 
        summary: 'Cancelled', 
        detail: 'Proforma workflow cancelled successfully', 
        life: 3000 
      })
      await fetchProformas()
      confirmationDialog.value = false
      selectedProforma.value = null
    }
  } catch (error) {
    console.error('Error cancelling proforma workflow:', error)
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: error.response?.data?.message || 'Failed to cancel proforma workflow', 
      life: 3000 
    })
  } finally {
    confirming.value = false
  }
}

const generatePdf = async (proforma) => {
  try {
    generatingPdf.value = true
    const response = await axios.get(`/api/facture-proformas/${proforma.id}/download`, {
      responseType: 'blob'
    })

    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `facture-proforma-${proforma.factureProformaCode || `FP-${proforma.id}`}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF downloaded successfully',
      life: 3000
    })
  } catch (err) {
    console.error('Error generating PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to download PDF',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const generateAllPdfs = async () => {
  try {
    generatingPdf.value = true
    for (const proforma of selectedProformas.value) {
      await generatePdf(proforma)
    }
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Generated ${selectedProformas.value.length} PDF(s)`,
      life: 3000
    })
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDFs',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const deleteProforma = (proforma) => {
  confirm.require({
    message: `Delete ${proforma.factureProformaCode}?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/facture-proformas/${proforma.id}`)
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Deleted',
            detail: 'Proforma deleted successfully',
            life: 3000
          })
          await fetchProformas()
        }
      } catch (err) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete proforma',
          life: 3000
        })
      }
    }
  })
}

const cancelProforma = (proforma) => {
  confirm.require({
    message: `Cancel ${proforma.factureProformaCode || `FP-${proforma.id}`}? This will mark it as cancelled.`,
    header: 'Confirm Cancellation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.post(`/api/facture-proformas/${proforma.id}/cancel`)
        if (response.data.status === 'success' || response.data.success) {
          toast.add({ severity: 'success', summary: 'Cancelled', detail: 'Proforma cancelled successfully', life: 3000 })
          await fetchProformas()
        }
      } catch (err) {
        console.error('Error cancelling proforma:', err)
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to cancel proforma', life: 3000 })
      }
    }
  })
}

const switchToBonCommend = async () => {
  try {
    const serviceDemandId = localStorage.getItem('current_service_demand_id')
    if (serviceDemandId) {
      const response = await axios.post(`/api/service-demands/${serviceDemandId}/update-to-bon-commend`)
      if (response.data.success) {
        localStorage.setItem('serviceDemandStatus_' + serviceDemandId, 'boncommend')
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Updated to Bon Commend status',
          life: 3000
        })
      }
    }
    localStorage.setItem('facture_proforma_used', 'true')
    buttonClicked.value = true
    window.location.href = '/apps/purchasing/bon-commends'
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update status',
      life: 3000
    })
  }
}

const createFactureProforma = () => {
  router.push({ name: 'purchasing.facture-proforma.create' })
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
  if (!date) return ''
  return new Date(date).toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    sent: 'Sent',
    approved: 'Approved',
    rejected: 'Rejected',
    completed: 'Completed',
    cancelled: 'Cancelled'
  }
  return labels[status] || status
}

const getStatusSeverity = (status) => {
  const severities = {
    draft: 'info',
    sent: 'warning',
    approved: 'success',
    rejected: 'danger',
    completed: 'success',
    cancelled: 'danger'
  }
  return severities[status] || 'info'
}

// Row class for styling
const rowClass = (data) => {
  if (data.status === 'cancelled') return 'tw-bg-red-50/30 hover:tw-bg-red-100/50'
  if (data.status === 'approved') return 'tw-bg-green-50/30 hover:tw-bg-green-100/50'
  if (data.status === 'sent') return 'tw-bg-yellow-50/30 hover:tw-bg-yellow-100/50'
  return 'hover:tw-bg-indigo-50/30'
}

// Selection methods
const isSelected = (item) => {
  return selectedProformas.value.some(selected => selected.id === item.id)
}

const toggleSelection = (item, selected) => {
  if (selected) {
    if (!isSelected(item)) {
      selectedProformas.value.push(item)
    }
  } else {
    selectedProformas.value = selectedProformas.value.filter(selected => selected.id !== item.id)
  }
  updateSelectAllState()
}

const toggleSelectAll = (selected) => {
  if (selected) {
    selectedProformas.value = [...proformas.value]
  } else {
    selectedProformas.value = []
  }
  selectAll.value = selected
}

const updateSelectAllState = () => {
  const totalItems = proformas.value.length
  const selectedCount = selectedProformas.value.length
  selectAll.value = totalItems > 0 && selectedCount === totalItems
}

const showActionMenu = (event, proforma) => {
  currentActionProforma.value = proforma
  
  actionMenuItems.value = [
    {
      label: 'Edit Proforma',
      icon: 'pi pi-pencil',
      command: () => editProformaItems(proforma)
    },
    {
      label: 'Convert to Bon Commande',
      icon: 'pi pi-arrow-right',
      command: () => convertToBonCommande(proforma),
      visible: proforma.status === 'approved'
    },
    {
      label: 'Export PDF',
      icon: 'pi pi-file-pdf',
      command: () => generatePdf(proforma)
    },
    {
      separator: true
    },
    {
      label: 'Cancel Proforma',
      icon: 'pi pi-times',
      command: () => cancelProforma(proforma),
      visible: proforma.status !== 'cancelled' && proforma.status !== 'factureprofram',
      style: 'color: #ea580c'
    },
    {
      label: 'Cancel Workflow',
      icon: 'pi pi-ban',
      command: () => showConfirmationDialog(proforma),
      visible: proforma.status !== 'cancelled' && proforma.status !== 'factureprofram',
      style: 'color: #dc2626'
    },
    {
      separator: true,
      visible: proforma.status === 'draft'
    },
    {
      label: 'Delete',
      icon: 'pi pi-trash',
      command: () => deleteProforma(proforma),
      visible: proforma.status === 'draft',
      style: 'color: #dc2626'
    }
  ]
  
  actionMenu.value.toggle(event)
}

const convertToBonCommande = async (proforma) => {
  confirm.require({
    message: `Convert ${proforma.factureProformaCode || `FP-${proforma.id}`} to Bon Commande? This will create a purchase order from this proforma.`,
    header: 'Confirm Conversion',
    icon: 'pi pi-arrow-right',
    acceptClass: 'p-button-success',
    accept: async () => {
      try {
        const response = await axios.post(`/api/facture-proformas/${proforma.id}/convert-to-bon-commande`)
        if (response.data.status === 'success' || response.data.success) {
          toast.add({ 
            severity: 'success', 
            summary: 'Converted', 
            detail: 'Proforma converted to Bon Commande successfully', 
            life: 3000 
          })
          await fetchProformas()
        }
      } catch (err) {
        console.error('Error converting proforma:', err)
        toast.add({ 
          severity: 'error', 
          summary: 'Error', 
          detail: 'Failed to convert proforma to Bon Commande', 
          life: 3000 
        })
      }
    }
  })
}

onMounted(() => {
  Promise.all([fetchProformas(), fetchSuppliers()])
})
</script>

<style scoped>
/* Enhanced DataTable Styles */
:deep(.p-datatable) {
  border: none;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-bottom: 2px solid #e2e8f0;
  font-weight: 600;
  color: #374151;
  padding: 1rem 1.5rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f1f5f9;
  transition: all 0.2s ease-in-out;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.05);
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 1.5rem;
  vertical-align: middle;
}

/* Enhanced Paginator */
:deep(.p-paginator) {
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  padding: 1.5rem;
  border-radius: 0 0 1rem 1rem;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page) {
  border-radius: 0.5rem;
  margin: 0 0.25rem;
  transition: all 0.2s ease-in-out;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page:hover) {
  background: #eef2ff;
  color: #6366f1;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

/* Enhanced Dropdown */
:deep(.p-dropdown) {
  border-radius: 0.75rem;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease-in-out;
}

:deep(.p-dropdown:hover) {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

:deep(.p-dropdown:focus) {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

:deep(.p-dropdown.p-focus) {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

/* Enhanced Input */
:deep(.p-inputtext) {
  border-radius: 0.75rem;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease-in-out;
  padding: 0.75rem 1rem;
}

:deep(.p-inputtext:hover) {
  border-color: #6366f1;
}

:deep(.p-inputtext:focus) {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Enhanced Buttons */
:deep(.p-button) {
  border-radius: 0.75rem;
  font-weight: 600;
  transition: all 0.2s ease-in-out;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.15);
}

:deep(.p-button.p-button-text) {
  box-shadow: none;
}

:deep(.p-button.p-button-text:hover) {
  transform: scale(1.05);
}

/* Enhanced Dialog */
:deep(.p-dialog) {
  border-radius: 1rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

:deep(.p-dialog .p-dialog-header) {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-bottom: 1px solid #e2e8f0;
  border-radius: 1rem 1rem 0 0;
  padding: 1.5rem 2rem;
}

:deep(.p-dialog .p-dialog-content) {
  padding: 2rem;
}

:deep(.p-dialog .p-dialog-footer) {
  padding: 1.5rem 2rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 0 0 1rem 1rem;
}

/* Enhanced Badge */
:deep(.p-badge) {
  border-radius: 0.5rem;
  font-weight: 600;
  padding: 0.25rem 0.75rem;
}

/* Enhanced Avatar */
:deep(.p-avatar) {
  border: 3px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 4px 14px 0 rgba(0, 0, 0, 0.15);
}

/* Enhanced Menu */
:deep(.p-menu) {
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
}

:deep(.p-menu .p-menuitem-link) {
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  margin: 0.125rem 0.25rem;
  transition: all 0.2s ease-in-out;
}

:deep(.p-menu .p-menuitem-link:hover) {
  background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
  transform: translateX(2px);
}

:deep(.p-menu .p-menuitem-link:focus) {
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

:deep(.p-menu .p-menu-separator) {
  margin: 0.5rem 0;
  border-top: 1px solid #e5e7eb;
}

/* Loading Animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.loading-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem 1rem;
  }

  :deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.75rem 1rem;
    font-size: 0.75rem;
  }
}

/* Gradient text effect */
.gradient-text {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Card hover effects */
.stats-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stats-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
}

/* Button glow effect */
.btn-glow {
  position: relative;
  overflow: hidden;
}

.btn-glow::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.5s;
}

.btn-glow:hover::before {
  left: 100%;
}

/* Floating Action Button */
.fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 4rem;
  height: 4rem;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  transition: all 0.3s ease;
  z-index: 1000;
}

.fab:hover {
  transform: scale(1.1);
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
}

.fab:active {
  transform: scale(0.95);
}
</style>