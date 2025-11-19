<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-shopping-cart tw-text-white tw-text-2xl"></i>
              </div>
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-400 tw-rounded-full tw-border-2 tw-border-white tw-animate-pulse"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
                Purchase Orders
              </h1>
              <p class="tw-text-slate-600 tw-text-sm tw-mt-1 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Manage medical supplies and equipment procurement
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
                    {{ bonCommends.filter(b => b.status === 'draft').length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-send tw-text-blue-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-blue-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-blue-700 tw-font-medium tw-uppercase tw-tracking-wide">Sent</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">
                    {{ bonCommends.filter(b => b.status === 'sent').length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-green-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-check-circle tw-text-green-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-green-700 tw-font-medium tw-uppercase tw-tracking-wide">Confirmed</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">
                    {{ bonCommends.filter(b => b.status === 'confirmed').length }}
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
                  <div class="tw-text-xs tw-text-slate-700 tw-font-medium tw-uppercase tw-tracking-wide">Total</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-slate-800">
                    {{ bonCommends.length }}
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
                placeholder="Search by order code, supplier..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all tw-duration-200 tw-bg-slate-50/50 hover:tw-bg-white"
                @input="debouncedSearch"
              />
              <div v-if="filters.search" class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center">
                <Button
                  @click="clearSearch"
                  icon="pi pi-times"
                  class="p-button-text p-button-sm p-button-rounded tw-text-slate-400 hover:tw-text-slate-600"
                  v-tooltip.top="'Clear search'"
                />
              </div>
            </div>

            <!-- Filter Dropdowns -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Status"
                class="tw-min-w-[160px] tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20"
                @change="applyFilters"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-w-3 tw-h-3 tw-rounded-full" 
                          :class="getStatusColor(slotProps.value)"></span>
                    <span class="tw-font-medium">{{ getStatusLabel({ status: slotProps.value }) }}</span>
                  </div>
                  <span v-else class="tw-text-slate-500">All Status</span>
                </template>
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3 tw-py-2">
                    <span class="tw-w-3 tw-h-3 tw-rounded-full" 
                          :class="getStatusColor(slotProps.option.value)"></span>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
              </Dropdown>

              <Dropdown
                v-model="filters.fournisseur_id"
                :options="suppliers"
                optionLabel="company_name"
                optionValue="id"
                placeholder="All Suppliers"
                class="tw-min-w-[200px] tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20"
                filter
                filterPlaceholder="Search supplier..."
                @change="applyFilters"
              >
                <template #option="slotProps">
                  <div class="tw-py-2 tw-px-1">
                    <div class="tw-font-semibold tw-text-slate-900">{{ slotProps.option.company_name }}</div>
                    <div class="tw-text-xs tw-text-slate-500 tw-flex tw-items-center tw-gap-1">
                      <i class="pi pi-user"></i>
                      {{ slotProps.option.contact_person }}
                    </div>
                  </div>
                </template>
              </Dropdown>

              <!-- Quick Filter Buttons -->
              <div class="tw-flex tw-gap-2">
                <Button
                  @click="setQuickFilter('draft')"
                  :class="filters.status === 'draft' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  class="p-button-sm tw-rounded-xl"
                  label="Draft"
                />
                <Button
                  @click="setQuickFilter('sent')"
                  :class="filters.status === 'sent' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  class="p-button-sm tw-rounded-xl"
                  label="Sent"
                />
                <Button
                  @click="setQuickFilter('confirmed')"
                  :class="filters.status === 'confirmed' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  class="p-button-sm tw-rounded-xl"
                  label="Confirmed"
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
              @click="openBatchPdfDialog"
              :disabled="!selectedBonCommends.length || generatingPdf"
              icon="pi pi-file-pdf"
              :label="selectedBonCommends.length ? `Generate ${selectedBonCommends.length} PDF(s)` : 'Generate PDFs'"
              class="p-button-outlined p-button-info p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
            />
            <Button 
              @click="createBonCommend"
              icon="pi pi-plus"
              label="New Order"
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
          <Tag v-if="filters.status" 
               :value="`Status: ${getStatusLabel({ status: filters.status })}`" 
               severity="info" 
               class="tw-rounded-lg"
               @click="filters.status = null; applyFilters()" />
          <Tag v-if="filters.fournisseur_id" 
               :value="`Supplier: ${getSupplierName(filters.fournisseur_id)}`" 
               severity="info" 
               class="tw-rounded-lg"
               @click="filters.fournisseur_id = null; applyFilters()" />
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
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900">Orders List</h3>
              <div class="tw-flex tw-items-center tw-gap-2">
                <Button 
                  @click="viewMode = 'table'"
                  :class="viewMode === 'table' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  icon="pi pi-table"
                  class="p-button-sm tw-rounded-lg"
                  v-tooltip.top="'Table view'"
                />
                <Button 
                  @click="viewMode = 'cards'"
                  :class="viewMode === 'cards' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  icon="pi pi-th-large"
                  class="p-button-sm tw-rounded-lg"
                  v-tooltip.top="'Card view'"
                />
              </div>
            </div>
            <div class="tw-text-sm tw-text-slate-600">
              {{ selectedBonCommends.length }} of {{ bonCommends.length }} selected
            </div>
          </div>
        </div>

        <!-- Table View -->
        <DataTable 
          v-if="viewMode === 'table'"
          v-model:selection="selectedBonCommends"
          :value="bonCommends"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} orders"
          :rowsPerPageOptions="[10, 25, 50]"
          dataKey="id"
          :loading="loading"
          selectionMode="multiple"
          responsiveLayout="scroll"
          class="p-datatable-sm medical-table"
          :class="{ 'tw-hidden': loading }"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Order Code with Enhanced Design -->
          <Column field="bonCommendCode" header="Order Code" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-px-3 tw-py-2 tw-rounded-lg tw-font-mono tw-text-sm tw-font-bold tw-shadow-sm">
                    {{ data.bonCommendCode || `BC-${data.id}` }}
                  </div>
                  <div v-if="requiresApproval(calculateTotal(data))" 
                       class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-amber-400 tw-rounded-full tw-border tw-border-white tw-animate-pulse"
                       v-tooltip.top="'Requires approval'">
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Supplier with Avatar -->
          <Column field="fournisseur.company_name" header="Supplier" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-gradient-to-br tw-from-slate-400 tw-to-slate-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-sm tw-shadow-sm">
                  {{ getInitials(data.fournisseur?.company_name) }}
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-slate-900">
                    {{ data.fournisseur?.company_name || 'Not specified' }}
                  </div>
                  <div class="tw-text-xs tw-text-slate-500 tw-flex tw-items-center tw-gap-1">
                    <i class="pi pi-user"></i>
                    {{ data.fournisseur?.contact_person || 'No contact' }}
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Items Count with Progress -->
          <Column header="Items" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-2">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <span class="tw-bg-gradient-to-r tw-from-blue-100 tw-to-indigo-100 tw-text-blue-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-semibold tw-shadow-sm">
                    {{ data.items?.length || 0 }} items
                  </span>
                </div>
                <div v-if="data.items?.length > 0" class="tw-w-full tw-bg-slate-200 tw-rounded-full tw-h-1.5">
                  <div class="tw-bg-gradient-to-r tw-from-green-400 tw-to-green-600 tw-h-1.5 tw-rounded-full tw-transition-all tw-duration-300"
                       :style="{ width: `${Math.min((data.items.filter(item => item.quantity_sended > 0).length / data.items.length) * 100, 100)}%` }">
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Total Amount with Currency Formatting -->
          <Column header="Total Amount" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-right">
                <div class="tw-font-bold tw-text-slate-900 tw-text-lg">
                  {{ formatCurrency(calculateTotal(data)) }}
                </div>
                <div v-if="requiresApproval(calculateTotal(data))" 
                     class="tw-text-xs tw-text-amber-600 tw-mt-1 tw-flex tw-items-center tw-justify-end tw-gap-1">
                  <i class="pi pi-exclamation-triangle"></i>
                  Requires approval
                </div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Status with Progress -->
          <Column field="status" header="Status" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-2">
                <Tag 
                  :value="getStatusLabel(data)" 
                  :severity="getStatusSeverity(data.status)"
                  class="tw-text-xs tw-font-semibold tw-rounded-lg tw-px-3 tw-py-1"
                />
                <div v-if="data.status === 'confirmed' && data.items?.length > 0" class="tw-text-xs tw-text-slate-500">
                  {{ data.items.filter(item => item.quantity_sended > 0).length }}/{{ data.items.length }} fulfilled
                </div>
              </div>
            </template>
          </Column>

          <!-- Date with Relative Time -->
          <Column field="created_at" header="Created" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm">
                <div class="tw-font-semibold tw-text-slate-900">{{ formatDateOnly(data.created_at) }}</div>
                <div class="tw-text-xs tw-text-slate-500">{{ formatTimeOnly(data.created_at) }}</div>
                <div class="tw-text-xs tw-text-slate-400 tw-mt-1">{{ getRelativeTime(data.created_at) }}</div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Actions -->
          <Column header="Actions" :exportable="false">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-1">
                <Button
                  @click="editBonCommendItems(data)"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-sm tw-text-blue-600 hover:tw-bg-blue-50 tw-transition-all tw-duration-200"
                  v-tooltip.top="'Edit Order'"
                />
                
                <Button
                  v-if="requiresApproval(calculateTotal(data)) && data.status === 'draft' && !data.approval_status"
                  @click="requestApproval(data)"
                  icon="pi pi-send"
                  class="p-button-rounded p-button-text p-button-sm tw-text-amber-600 hover:tw-bg-amber-50 tw-transition-all tw-duration-200"
                  v-tooltip.top="'Request Approval'"
                />
                
                <Button
                  @click="createReception(data)"
                  :disabled="data.status !== 'confirmed'"
                  icon="pi pi-box"
                  class="p-button-rounded p-button-text p-button-sm tw-text-green-600 hover:tw-bg-green-50 disabled:tw-opacity-40 tw-transition-all tw-duration-200"
                  v-tooltip.top="'Create Reception'"
                />
                
                <SplitButton
                  icon="pi pi-download"
                  :model="getPdfMenuItems(data)"
                  class="p-button-sm p-button-outlined tw-text-slate-600 hover:tw-bg-slate-50 tw-transition-all tw-duration-200"
                  v-tooltip.top="'PDF Options'"
                />
                
                <Button
                  @click="deleteBonCommend(data)"
                  :disabled="data.status !== 'draft'"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-sm tw-text-red-600 hover:tw-bg-red-50 disabled:tw-opacity-40 tw-transition-all tw-duration-200"
                  v-tooltip.top="'Delete'"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Card View -->
        <div v-else class="tw-p-6">
          <div v-if="bonCommends.length === 0 && !loading" class="tw-text-center tw-py-12">
            <div class="tw-mx-auto tw-w-24 tw-h-24 tw-bg-slate-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mb-4">
              <i class="pi pi-shopping-cart tw-text-4xl tw-text-slate-400"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">No purchase orders found</h3>
            <p class="tw-text-slate-500 tw-mb-4">Get started by creating your first purchase order</p>
            <Button @click="createBonCommend" icon="pi pi-plus" label="Create Order" class="p-button-primary" />
          </div>
          
          <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div v-for="bonCommend in paginatedBonCommends" 
                 :key="bonCommend.id"
                 class="tw-bg-white tw-border tw-border-slate-200 tw-rounded-xl tw-p-6 tw-shadow-sm hover:tw-shadow-lg tw-transition-all tw-duration-300 hover:tw-transform hover:tw-scale-105">
              
              <!-- Card Header -->
              <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <input type="checkbox" 
                         v-model="selectedBonCommends" 
                         :value="bonCommend"
                         class="tw-w-4 tw-h-4 tw-text-blue-600 tw-bg-slate-100 tw-border-slate-300 tw-rounded focus:tw-ring-blue-500" />
                  <div>
                    <div class="tw-font-mono tw-font-bold tw-text-blue-600 tw-text-lg">
                      {{ bonCommend.bonCommendCode || `BC-${bonCommend.id}` }}
                    </div>
                    <div class="tw-text-xs tw-text-slate-500">{{ getRelativeTime(bonCommend.created_at) }}</div>
                  </div>
                </div>
                <Tag :value="getStatusLabel(bonCommend)" 
                     :severity="getStatusSeverity(bonCommend.status)"
                     class="tw-rounded-lg" />
              </div>

              <!-- Supplier Info -->
              <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
                <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-slate-400 tw-to-slate-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-xs">
                  {{ getInitials(bonCommend.fournisseur?.company_name) }}
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-slate-900">{{ bonCommend.fournisseur?.company_name || 'Not specified' }}</div>
                  <div class="tw-text-xs tw-text-slate-500">{{ bonCommend.fournisseur?.contact_person || 'No contact' }}</div>
                </div>
              </div>

              <!-- Items & Amount -->
              <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-mb-4">
                <div>
                  <div class="tw-text-xs tw-text-slate-500 tw-uppercase tw-tracking-wide">Items</div>
                  <div class="tw-font-semibold tw-text-slate-900">{{ bonCommend.items?.length || 0 }}</div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-slate-500 tw-uppercase tw-tracking-wide">Total</div>
                  <div class="tw-font-bold tw-text-green-600">{{ formatCurrency(calculateTotal(bonCommend)) }}</div>
                </div>
              </div>

              <!-- Progress Bar -->
              <div v-if="bonCommend.items?.length > 0" class="tw-mb-4">
                <div class="tw-flex tw-justify-between tw-text-xs tw-text-slate-500 tw-mb-1">
                  <span>Fulfillment</span>
                  <span>{{ bonCommend.items.filter(item => item.quantity_sended > 0).length }}/{{ bonCommend.items.length }}</span>
                </div>
                <div class="tw-w-full tw-bg-slate-200 tw-rounded-full tw-h-2">
                  <div class="tw-bg-gradient-to-r tw-from-green-400 tw-to-green-600 tw-h-2 tw-rounded-full tw-transition-all tw-duration-300"
                       :style="{ width: `${Math.min((bonCommend.items.filter(item => item.quantity_sended > 0).length / bonCommend.items.length) * 100, 100)}%` }">
                  </div>
                </div>
              </div>

              <!-- Approval Warning -->
              <div v-if="requiresApproval(calculateTotal(bonCommend))" 
                   class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-3 tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-amber-700">
                  <i class="pi pi-exclamation-triangle"></i>
                  <span class="tw-text-sm tw-font-medium">Requires approval</span>
                </div>
              </div>

              <!-- Card Actions -->
              <div class="tw-flex tw-flex-wrap tw-gap-2">
                <Button @click="editBonCommendItems(bonCommend)" 
                        icon="pi pi-pencil" 
                        class="p-button-outlined p-button-primary p-button-sm tw-rounded-lg" 
                        label="Edit" />
                <Button @click="createReception(bonCommend)" 
                        :disabled="bonCommend.status !== 'confirmed'"
                        icon="pi pi-box" 
                        class="p-button-outlined p-button-success p-button-sm tw-rounded-lg" 
                        label="Receive" />
                <SplitButton icon="pi pi-download" 
                            :model="getPdfMenuItems(bonCommend)" 
                            class="p-button-outlined p-button-info p-button-sm tw-rounded-lg" />
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
            <p class="tw-text-slate-600">Loading purchase orders...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- PDF Template Selection Dialog -->
    <Dialog 
      v-model:visible="pdfTemplateDialog"
      :modal="true"
      class="tw-w-full tw-max-w-lg"
    >
      <template #header>
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-bg-slate-100 tw-p-2 tw-rounded-lg">
            <i class="pi pi-file-pdf tw-text-slate-600 tw-text-lg"></i>
          </div>
          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900">Generate PDF Documents</h3>
            <p class="tw-text-sm tw-text-slate-500">
              {{ pdfMode === 'single' ? 'Select template for order' : `Generate ${selectedBonCommends.length} PDFs` }}
            </p>
          </div>
        </div>
      </template>

      <div class="tw-space-y-4">
        <!-- Template Selection Cards -->
        <div class="tw-space-y-3">
          <!-- Standard Template -->
          <div 
            @click="selectedTemplate = 'default'"
            class="tw-border tw-rounded-lg tw-p-4 tw-cursor-pointer tw-transition-all"
            :class="selectedTemplate === 'default' 
              ? 'tw-border-blue-500 tw-bg-blue-50 tw-ring-2 tw-ring-blue-200' 
              : 'tw-border-slate-200 hover:tw-border-slate-300 hover:tw-bg-slate-50'"
          >
            <div class="tw-flex tw-items-start tw-gap-3">
              <div class="tw-mt-1">
                <RadioButton 
                  v-model="selectedTemplate" 
                  inputId="default-template" 
                  value="default" 
                />
              </div>
              <div class="tw-flex-1">
                <label for="default-template" class="tw-cursor-pointer">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                    <div class="tw-p-1.5 tw-bg-blue-100 tw-rounded">
                      <i class="pi pi-file tw-text-blue-600"></i>
                    </div>
                    <span class="tw-font-semibold tw-text-slate-900">Standard Template</span>
                    <Tag value="Default" severity="info" class="tw-text-xs" />
                  </div>
                  <p class="tw-text-sm tw-text-slate-600">
                    Hospital's standard purchase order format with complete details and official branding
                  </p>
                  <div class="tw-mt-2 tw-flex tw-gap-2">
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Header/Footer
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Logo
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Signatures
                    </span>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- PCH Template -->
          <div 
            @click="selectedTemplate = 'pch'"
            class="tw-border tw-rounded-lg tw-p-4 tw-cursor-pointer tw-transition-all"
            :class="selectedTemplate === 'pch' 
              ? 'tw-border-green-500 tw-bg-green-50 tw-ring-2 tw-ring-green-200' 
              : 'tw-border-slate-200 hover:tw-border-slate-300 hover:tw-bg-slate-50'"
          >
            <div class="tw-flex tw-items-start tw-gap-3">
              <div class="tw-mt-1">
                <RadioButton 
                  v-model="selectedTemplate" 
                  inputId="pch-template" 
                  value="pch" 
                />
              </div>
              <div class="tw-flex-1">
                <label for="pch-template" class="tw-cursor-pointer">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                    <div class="tw-p-1.5 tw-bg-green-100 tw-rounded">
                      <i class="pi pi-building tw-text-green-600"></i>
                    </div>
                    <span class="tw-font-semibold tw-text-slate-900">PCH Template</span>
                    <Tag value="Pharmacy" severity="success" class="tw-text-xs" />
                  </div>
                  <p class="tw-text-sm tw-text-slate-600">
                    Central Pharmacy (PCH) specific format for pharmaceutical and medical supply orders
                  </p>
                  <div class="tw-mt-2 tw-flex tw-gap-2">
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> PCH Header
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Drug Codes
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Batch Info
                    </span>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Details (for single PDF) -->
        <div v-if="selectedPdfBonCommend && pdfMode === 'single'" 
             class="tw-bg-slate-50 tw-rounded-lg tw-p-4 tw-border tw-border-slate-200">
          <h4 class="tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-3">Order Details</h4>
          <div class="tw-grid tw-grid-cols-2 tw-gap-3 tw-text-sm">
            <div>
              <span class="tw-text-slate-500">Order Code:</span>
              <span class="tw-font-mono tw-font-medium tw-text-slate-900 tw-ml-2">
                {{ selectedPdfBonCommend.bonCommendCode }}
              </span>
            </div>
            <div>
              <span class="tw-text-slate-500">Supplier:</span>
              <span class="tw-font-medium tw-text-slate-900 tw-ml-2">
                {{ selectedPdfBonCommend.fournisseur?.company_name || 'N/A' }}
              </span>
            </div>
            <div>
              <span class="tw-text-slate-500">Items:</span>
              <span class="tw-font-medium tw-text-slate-900 tw-ml-2">
                {{ selectedPdfBonCommend.items?.length || 0 }}
              </span>
            </div>
            <div>
              <span class="tw-text-slate-500">Total:</span>
              <span class="tw-font-medium tw-text-blue-600 tw-ml-2">
                {{ formatCurrency(calculateTotal(selectedPdfBonCommend)) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Batch Summary (for multiple PDFs) -->
        <div v-if="pdfMode === 'batch'" 
             class="tw-bg-blue-50 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200">
          <div class="tw-flex tw-items-start tw-gap-2">
            <i class="pi pi-info-circle tw-text-blue-600 tw-mt-0.5"></i>
            <div class="tw-text-sm">
              <p class="tw-font-medium tw-text-blue-900 tw-mb-1">
                Generating {{ selectedBonCommends.length }} PDF documents
              </p>
              <p class="tw-text-blue-700">
                All selected orders will be generated using the {{ selectedTemplate === 'pch' ? 'PCH' : 'Standard' }} template.
              </p>
            </div>
          </div>
        </div>

        <!-- PDF Options -->
        <div class="tw-space-y-3 tw-pt-3 tw-border-t tw-border-slate-200">
          <div class="tw-flex tw-items-center tw-justify-between">
            <label for="open-pdf" class="tw-text-sm tw-font-medium tw-text-slate-700 tw-cursor-pointer">
              PDF Action
            </label>
            <Dropdown 
              v-model="pdfAction" 
              :options="pdfActionOptions"
              optionLabel="label"
              optionValue="value"
              class="tw-w-48"
            />
          </div>
          
          <div v-if="pdfAction === 'save'" class="tw-flex tw-items-center tw-justify-between">
            <label class="tw-text-sm tw-font-medium tw-text-slate-700">
              Save Location
            </label>
            <InputText 
              v-model="savePath" 
              placeholder="/downloads/orders/"
              class="tw-w-48"
              disabled
            />
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-2 tw-justify-end">
          <Button 
            @click="pdfTemplateDialog = false" 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text p-button-secondary"
          />
          <Button 
            @click="previewPdf" 
            label="Preview" 
            icon="pi pi-eye" 
            class="p-button-outlined p-button-info"
            v-if="pdfMode === 'single'"
          />
          <Button 
            @click="generatePdf" 
            :label="pdfMode === 'batch' ? `Generate ${selectedBonCommends.length} PDFs` : 'Generate PDF'" 
            icon="pi pi-download" 
            :loading="generatingPdf"
            class="p-button-primary"
          />
        </div>
      </template>
    </Dialog>

    <!-- Approval Dialog -->
    <Dialog 
      v-model:visible="approvalDialog"
      :modal="true"
      class="tw-w-full tw-max-w-lg"
    >
      <!-- [Previous approval dialog content remains the same] -->
    </Dialog>

    <!-- ConfirmDialog component -->
    <ConfirmDialog />
    
    <!-- Toast component -->
    <Toast />

    <!-- Floating Action Button -->
    <button 
      @click="createBonCommend"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Order'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>
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
import SplitButton from 'primevue/splitbutton'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import ConfirmDialog from 'primevue/confirmdialog'
import RadioButton from 'primevue/radiobutton'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import axios from 'axios'

// Composables
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State Management
const loading = ref(false)
const generatingPdf = ref(false)
const bonCommends = ref([])
const suppliers = ref([])
const selectedBonCommends = ref([])
const selectedBonCommend = ref(null)

// View Mode
const viewMode = ref('table') // 'table' or 'cards'
const currentPage = ref(1)
const itemsPerPage = ref(12)

// PDF State
const pdfTemplateDialog = ref(false)
const selectedPdfBonCommend = ref(null)
const selectedTemplate = ref('default')
const pdfMode = ref('single') // 'single' or 'batch'
const pdfAction = ref('download') // 'download', 'preview', 'save'
const savePath = ref('/downloads/orders/')

// Approval State
const approvalDialog = ref(false)
const approvalNotes = ref('')
const requestingApproval = ref(false)
const approvalThreshold = ref(10000)

// Filters
const filters = reactive({
  status: null,
  fournisseur_id: null,
  search: ''
})

// Search debounce
let searchTimeout = null

// Computed Properties
const hasActiveFilters = computed(() => {
  return filters.status || filters.fournisseur_id || filters.search
})

const paginatedBonCommends = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return bonCommends.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(bonCommends.value.length / itemsPerPage.value)
})

// Options
const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const pdfActionOptions = [
  { label: 'Download', value: 'download', icon: 'pi pi-download' },
  { label: 'Open in new tab', value: 'preview', icon: 'pi pi-external-link' },
  { label: 'Save to server', value: 'save', icon: 'pi pi-server' }
]

// Get PDF menu items for split button
const getPdfMenuItems = (bonCommend) => {
  return [
    {
      label: 'Standard PDF',
      icon: 'pi pi-file',
      command: () => {
        selectedPdfBonCommend.value = bonCommend
        selectedTemplate.value = 'default'
        pdfMode.value = 'single'
        generatePdf()
      }
    },
    {
      label: 'PCH PDF',
      icon: 'pi pi-building',
      command: () => {
        selectedPdfBonCommend.value = bonCommend
        selectedTemplate.value = 'pch'
        pdfMode.value = 'single'
        generatePdf()
      }
    },
    {
      separator: true
    },
    {
      label: 'Choose Template...',
      icon: 'pi pi-cog',
      command: () => {
        selectedPdfBonCommend.value = bonCommend
        pdfMode.value = 'single'
        pdfTemplateDialog.value = true
      }
    }
  ]
}

// API Methods
const fetchBonCommends = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    
    if (filters.status) params.append('status', filters.status)
    if (filters.fournisseur_id) params.append('fournisseur_id', filters.fournisseur_id)
    if (filters.search) params.append('search', filters.search)

    const response = await axios.get(`/api/bon-commends?${params.toString()}`)
    
    if (response.data.status === 'success') {
      bonCommends.value = response.data.data.data || response.data.data
    }
  } catch (err) {
    console.error('Error fetching bon commends:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase orders',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    if (response.data.status === 'success') {
      suppliers.value = response.data.data
    } else if (response.data && Array.isArray(response.data)) {
      suppliers.value = response.data
    }
  } catch (err) {
    console.error('Error fetching suppliers:', err)
  }
}

const applyFilters = async () => {
  await fetchBonCommends()
}

const refreshData = async () => {
  await Promise.all([fetchBonCommends(), fetchSuppliers()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

// Navigate to edit page
const editBonCommendItems = (bonCommend) => {
  router.push({ 
    name: 'purchasing.bon-commend.edit', 
    params: { id: bonCommend.id } 
  })
}

// Open batch PDF dialog
const openBatchPdfDialog = () => {
  if (!selectedBonCommends.value.length) return
  
  pdfMode.value = 'batch'
  selectedTemplate.value = 'default'
  pdfTemplateDialog.value = true
}

// Generate PDF with selected template
const generatePdf = async () => {
  generatingPdf.value = true
  
  try {
    if (pdfMode.value === 'batch') {
      // Generate multiple PDFs
      let successCount = 0
      let errorCount = 0
      
      for (const bonCommend of selectedBonCommends.value) {
        try {
          await downloadSinglePdf(bonCommend, selectedTemplate.value)
          successCount++
        } catch (err) {
          errorCount++
          console.error(`Error generating PDF for order ${bonCommend.bonCommendCode}:`, err)
        }
      }
      
      if (successCount > 0) {
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Generated ${successCount} PDF(s) successfully${errorCount > 0 ? ` (${errorCount} failed)` : ''}`,
          life: 3000
        })
      }
      
      selectedBonCommends.value = []
    } else {
      // Generate single PDF
      await downloadSinglePdf(selectedPdfBonCommend.value, selectedTemplate.value)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `PDF generated with ${selectedTemplate.value === 'pch' ? 'PCH' : 'standard'} template`,
        life: 3000
      })
    }
    
    pdfTemplateDialog.value = false
  } catch (err) {
    console.error('Error generating PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

// Download single PDF
const downloadSinglePdf = async (bonCommend, template) => {
  try {
    const response = await axios.get(
      `/api/bon-commends/${bonCommend.id}/pdf?template=${template}`,
      {
        responseType: 'blob',
        timeout: 30000,
        headers: {
          'Accept': 'application/pdf'
        }
      }
    )
    
    // Check if the response is actually a PDF or an error message
    if (response.data.type === 'application/json') {
      const text = await response.data.text()
      const jsonData = JSON.parse(text)
      throw new Error(jsonData.message || 'Failed to generate PDF')
    }
    
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    
    if (pdfAction.value === 'preview') {
      // Open in new tab
      const newWindow = window.open()
      newWindow.location.href = url
    } else if (pdfAction.value === 'save') {
      // Save to server (call different endpoint)
      await savePdfToServer(bonCommend, template)
    } else {
      // Download
      const link = document.createElement('a')
      link.href = url
      link.download = `order-${bonCommend.bonCommendCode}-${template}.pdf`
      link.style.display = 'none'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Revoke URL immediately after download starts
      setTimeout(() => {
        window.URL.revokeObjectURL(url)
      }, 100)
    }
  } catch (err) {
    console.error('Error downloading PDF:', err)
    throw new Error(err.message || 'Failed to download PDF')
  }
}

// Save PDF to server
const savePdfToServer = async (bonCommend, template) => {
  const response = await axios.post(
    `/api/bon-commends/${bonCommend.id}/pdf/save?template=${template}`
  )
  
  if (response.data.status === 'success') {
    toast.add({
      severity: 'info',
      summary: 'Saved',
      detail: `PDF saved to: ${response.data.path}`,
      life: 4000
    })
  }
}

// Preview PDF
const previewPdf = async () => {
  if (!selectedPdfBonCommend.value) return
  
  try {
    generatingPdf.value = true
    
    const response = await axios.get(
      `/api/bon-commends/${selectedPdfBonCommend.value.id}/pdf/preview?template=${selectedTemplate.value}`,
      {
        responseType: 'blob',
        timeout: 30000,
        headers: {
          'Accept': 'application/pdf'
        }
      }
    )
    
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    
    const newWindow = window.open()
    newWindow.location.href = url
    
    setTimeout(() => {
      window.URL.revokeObjectURL(url)
    }, 100)
  } catch (err) {
    console.error('Error previewing PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to preview PDF',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const createReception = (bonCommend) => {
  router.push({
    name: 'purchasing.bon-receptions.create',
    query: { bon_commend_id: bonCommend.id }
  })
}

const deleteBonCommend = (bonCommend) => {
  confirm.require({
    message: `Delete order ${bonCommend.bonCommendCode}? This action cannot be undone.`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/bon-commends/${bonCommend.id}`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Order deleted successfully',
            life: 3000
          })
          await fetchBonCommends()
        }
      } catch (err) {
        console.error('Error deleting bon commend:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete order',
          life: 3000
        })
      }
    }
  })
}

const createBonCommend = () => {
  router.push({ name: 'purchasing.bon-commend.create' })
}

// Approval Methods
const requiresApproval = (totalAmount) => {
  return totalAmount > approvalThreshold.value
}

const requestApproval = (bonCommend) => {
  selectedBonCommend.value = bonCommend
  approvalNotes.value = ''
  approvalDialog.value = true
}

// New Enhanced Methods
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const clearSearch = () => {
  filters.search = ''
  applyFilters()
}

const setQuickFilter = (status) => {
  filters.status = filters.status === status ? null : status
  applyFilters()
}

const clearAllFilters = () => {
  filters.status = null
  filters.fournisseur_id = null
  filters.search = ''
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

// Utility Functions
const calculateTotal = (bonCommend) => {
  if (!bonCommend.items || !Array.isArray(bonCommend.items)) {
    return 0
  }
  
  return bonCommend.items.reduce((total, item) => {
    const quantity = parseFloat(item.quantity_desired) || 0
    const price = parseFloat(item.price) || 0
    return total + (quantity * price)
  }, 0)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const formatDateOnly = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTimeOnly = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusLabel = (data) => {
  const status = data.status || 'draft'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'draft': return 'warning'
    case 'sent': return 'info'
    case 'confirmed': return 'success'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

const getStatusColor = (status) => {
  switch (status) {
    case 'draft': return 'tw-bg-amber-400'
    case 'sent': return 'tw-bg-blue-400'
    case 'confirmed': return 'tw-bg-green-400'
    case 'cancelled': return 'tw-bg-red-400'
    default: return 'tw-bg-slate-400'
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchBonCommends(),
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
</style>
