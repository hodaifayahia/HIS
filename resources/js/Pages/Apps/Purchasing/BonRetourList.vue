<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-p-3 tw-bg-gradient-to-br tw-from-red-500 tw-to-red-600 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-reply tw-text-white tw-text-2xl"></i>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-gray-900 tw-to-gray-700 tw-bg-clip-text tw-text-transparent">
                Return Notes
              </h1>
              <p class="tw-text-slate-600 tw-mt-1 tw-font-medium">Manage supplier returns and credit notes</p>
            </div>
          </div>

          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-3 tw-w-full lg:tw-w-auto">
            <Button
              icon="pi pi-refresh"
              label="Refresh"
              @click="refreshData"
              outlined
              class="tw-border-slate-300 hover:tw-bg-slate-50"
            />
            <Button
              icon="pi pi-plus"
              label="Create Return"
              @click="navigateToCreate"
              class="tw-bg-gradient-to-r tw-from-red-500 tw-to-red-600 hover:tw-from-red-600 hover:tw-to-red-700 tw-border-none tw-shadow-lg"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Enhanced Action Toolbar -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Filters Section -->
          <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4 tw-w-full xl:tw-w-auto">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-filter tw-text-slate-500"></i>
              <span class="tw-font-semibold tw-text-slate-700">Filters</span>
            </div>

            <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-4 tw-w-full">
              <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Status</label>
                <Dropdown
                  v-model="filters.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="All Statuses"
                  @change="applyFilters"
                  class="tw-w-full"
                />
              </div>

              <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Return Type</label>
                <Dropdown
                  v-model="filters.return_type"
                  :options="returnTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="All Types"
                  @change="applyFilters"
                  class="tw-w-full"
                />
              </div>

              <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Supplier</label>
                <Dropdown
                  v-model="filters.fournisseur_id"
                  :options="suppliers"
                  optionLabel="company_name"
                  optionValue="id"
                  placeholder="All Suppliers"
                  @change="applyFilters"
                  class="tw-w-full"
                  filter
                />
              </div>

              <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Return Date</label>
                <Calendar
                  v-model="filters.date_from"
                  placeholder="Start Date"
                  @date-select="applyFilters"
                  showIcon
                  class="tw-w-full"
                />
              </div>

              <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Search</label>
                <InputText
                  v-model="filters.search"
                  placeholder="Return Code..."
                  @keyup.enter="applyFilters"
                  class="tw-w-full"
                />
              </div>
            </div>
          </div>

          <!-- View Toggle and Actions -->
          <div class="tw-flex tw-items-center tw-gap-3 tw-w-full xl:tw-w-auto tw-justify-center xl:tw-justify-end">
            <div class="tw-flex tw-items-center tw-bg-slate-100 tw-rounded-lg tw-p-1">
              <Button
                icon="pi pi-list"
                :class="viewMode === 'table' ? 'tw-bg-white tw-shadow-sm' : 'tw-bg-transparent'"
                text
                @click="viewMode = 'table'"
                v-tooltip.top="'Table View'"
              />
              <Button
                icon="pi pi-th-large"
                :class="viewMode === 'cards' ? 'tw-bg-white tw-shadow-sm' : 'tw-bg-transparent'"
                text
                @click="viewMode = 'cards'"
                v-tooltip.top="'Card View'"
              />
            </div>

            <Button
              icon="pi pi-filter-slash"
              label="Clear Filters"
              @click="clearAllFilters"
              text
              class="tw-text-slate-600 hover:tw-bg-slate-100"
              :disabled="!hasActiveFilters"
            />
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-6 tw-mb-8">
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-backdrop-blur-sm card-hover status-indicator">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Total Returns</p>
              <p class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
                {{ stats.total_returns || 0 }}
              </p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-br tw-from-red-500 tw-to-red-600 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-reply tw-text-white tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-backdrop-blur-sm card-hover status-indicator">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Pending Approval</p>
              <p class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-yellow-600 tw-to-yellow-500 tw-bg-clip-text tw-text-transparent">
                {{ stats.pending_approvals || 0 }}
              </p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-br tw-from-yellow-500 tw-to-yellow-600 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-clock tw-text-white tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-backdrop-blur-sm card-hover">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Completed</p>
              <p class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-green-600 tw-to-green-500 tw-bg-clip-text tw-text-transparent">
                {{ stats.completed || 0 }}
              </p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-br tw-from-green-500 tw-to-green-600 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-check-circle tw-text-white tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-backdrop-blur-sm card-hover">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Total Amount</p>
              <p class="tw-text-2xl tw-font-bold tw-bg-gradient-to-r tw-from-purple-600 tw-to-purple-500 tw-bg-clip-text tw-text-transparent">
                {{ formatCurrency(stats.total_amount || 0) }}
              </p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-br tw-from-purple-500 tw-to-purple-600 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-dollar tw-text-white tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-backdrop-blur-sm card-hover">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Credit Notes Pending</p>
              <p class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-blue-600 tw-to-blue-500 tw-bg-clip-text tw-text-transparent">
                {{ stats.credit_notes_pending || 0 }}
              </p>
            </div>
            <div class="tw-p-3 tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-600 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-file-edit tw-text-white tw-text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Data Table with Card View Toggle -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <!-- Table View -->
        <DataTable
          v-if="viewMode === 'table'"
          :value="retours.data"
          :loading="loading"
          paginator
          :rows="15"
          :totalRecords="retours.total"
          :lazy="true"
          @page="onPage"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} returns"
          responsiveLayout="scroll"
          class="medical-table tw-border-none"
        >
          <template #loading>
            <div class="tw-flex tw-justify-center tw-items-center tw-py-12">
              <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
                <div class="tw-animate-spin tw-rounded-full tw-h-8 tw-w-8 tw-border-b-2 tw-border-red-500"></div>
                <p class="tw-text-slate-600">Loading returns...</p>
              </div>
            </div>
          </template>

          <template #empty>
            <div class="tw-text-center tw-py-12">
              <div class="tw-p-4 tw-bg-slate-100 tw-rounded-full tw-inline-block tw-mb-4">
                <i class="pi pi-inbox tw-text-slate-400 tw-text-3xl"></i>
              </div>
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-700 tw-mb-2">No return notes found</h3>
              <p class="tw-text-slate-500">Try adjusting your filters or create a new return note.</p>
            </div>
          </template>

          <Column field="bon_retour_code" header="Return Code" class="tw-min-w-40">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-p-2 tw-bg-red-100 tw-rounded-lg">
                  <i class="pi pi-reply tw-text-red-600 tw-text-sm"></i>
                </div>
                <span class="tw-font-mono tw-text-sm tw-font-semibold tw-text-slate-700">
                  {{ slotProps.data.bon_retour_code }}
                </span>
              </div>
            </template>
          </Column>

          <Column field="return_type" header="Return Type" class="tw-min-w-32">
            <template #body="slotProps">
              <Tag
                :value="getReturnTypeLabel(slotProps.data.return_type)"
                :severity="getReturnTypeSeverity(slotProps.data.return_type)"
                class="tw-font-semibold"
              />
            </template>
          </Column>

          <Column field="fournisseur.company_name" header="Supplier" class="tw-min-w-48">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-slate-100 tw-rounded-lg">
                  <i class="pi pi-building tw-text-slate-600"></i>
                </div>
                <div>
                  <p class="tw-font-semibold tw-text-slate-700">{{ slotProps.data.fournisseur?.company_name || 'Unassigned' }}</p>
                  <p class="tw-text-xs tw-text-slate-500">{{ getRelativeTime(slotProps.data.created_at) }}</p>
                </div>
              </div>
            </template>
          </Column>

          <Column field="return_date" header="Return Date" class="tw-min-w-32">
            <template #body="slotProps">
              <div class="tw-flex tw-flex-col">
                <span class="tw-font-semibold tw-text-slate-700">{{ formatDate(slotProps.data.return_date) }}</span>
                <span class="tw-text-xs tw-text-slate-500">{{ formatTimeOnly(slotProps.data.return_date) }}</span>
              </div>
            </template>
          </Column>

          <Column field="total_amount" header="Total Amount" class="tw-min-w-32">
            <template #body="slotProps">
              <span class="tw-font-bold tw-text-lg tw-text-green-600">
                {{ formatCurrency(slotProps.data.total_amount) }}
              </span>
            </template>
          </Column>

          <Column field="status" header="Status" class="tw-min-w-32">
            <template #body="slotProps">
              <Tag
                :value="getStatusLabel(slotProps.data.status)"
                :severity="getStatusSeverity(slotProps.data.status)"
                class="tw-font-semibold status-indicator"
              />
            </template>
          </Column>

          <Column field="credit_note_received" header="Credit Note" class="tw-min-w-32">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i
                  :class="slotProps.data.credit_note_received ? 'pi pi-check-circle tw-text-green-500' : 'pi pi-times-circle tw-text-slate-400'"
                  class="tw-text-lg"
                ></i>
                <div v-if="slotProps.data.credit_note_number" class="tw-flex tw-flex-col">
                  <span class="tw-text-xs tw-font-mono tw-font-semibold tw-text-slate-700">
                    {{ slotProps.data.credit_note_number }}
                  </span>
                  <span class="tw-text-xs tw-text-slate-500">Credit Note</span>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Items" class="tw-min-w-24">
            <template #body="slotProps">
              <div class="tw-text-center">
                <div class="tw-inline-flex tw-items-center tw-gap-1 tw-bg-slate-100 tw-px-3 tw-py-1 tw-rounded-full">
                  <i class="pi pi-box tw-text-slate-600 tw-text-sm"></i>
                  <span class="tw-text-sm tw-font-semibold tw-text-slate-700">{{ slotProps.data.items?.length || 0 }}</span>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Actions" class="tw-min-w-56">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-eye"
                  size="small"
                  text
                  @click="viewRetour(slotProps.data.id)"
                  v-tooltip.top="'View details'"
                  class="hover:tw-bg-slate-100"
                />
                <Button
                  icon="pi pi-pencil"
                  size="small"
                  text
                  @click="editRetour(slotProps.data.id)"
                  v-tooltip.top="'Edit'"
                  :disabled="!slotProps.data.is_editable"
                  class="hover:tw-bg-blue-50"
                />
                <Button
                  v-if="slotProps.data.status === 'pending'"
                  icon="pi pi-check"
                  size="small"
                  text
                  severity="success"
                  @click="approveRetour(slotProps.data)"
                  v-tooltip.top="'Approve'"
                  class="hover:tw-bg-green-50"
                />
                <Button
                  v-if="slotProps.data.status === 'approved'"
                  icon="pi pi-check-square"
                  size="small"
                  text
                  severity="info"
                  @click="completeRetour(slotProps.data)"
                  v-tooltip.top="'Mark as Complete'"
                  class="hover:tw-bg-blue-50"
                />
                <SplitButton
                  icon="pi pi-file-pdf"
                  size="small"
                  :model="getPdfMenuItems(slotProps.data)"
                  @click="openPdfTemplateDialog(slotProps.data)"
                  class="hover:tw-bg-purple-50"
                />
                <Button
                  icon="pi pi-trash"
                  size="small"
                  text
                  severity="danger"
                  @click="confirmDelete(slotProps.data)"
                  v-tooltip.top="'Delete'"
                  :disabled="!slotProps.data.is_editable"
                  class="hover:tw-bg-red-50"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Card View -->
        <div v-else class="tw-p-6">
          <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
            <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
              <div class="tw-animate-spin tw-rounded-full tw-h-8 tw-w-8 tw-border-b-2 tw-border-red-500"></div>
              <p class="tw-text-slate-600">Loading returns...</p>
            </div>
          </div>

          <div v-else-if="!retours.data || retours.data.length === 0" class="tw-text-center tw-py-12">
            <div class="tw-p-4 tw-bg-slate-100 tw-rounded-full tw-inline-block tw-mb-4">
              <i class="pi pi-inbox tw-text-slate-400 tw-text-3xl"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-slate-700 tw-mb-2">No return notes found</h3>
            <p class="tw-text-slate-500">Try adjusting your filters or create a new return note.</p>
          </div>

          <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div
              v-for="retour in retours.data"
              :key="retour.id"
              class="tw-bg-white tw-border tw-border-slate-200 tw-rounded-xl tw-p-6 tw-shadow-sm card-hover tw-transition-all tw-duration-200"
            >
              <div class="tw-flex tw-items-start tw-justify-between tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-p-2 tw-bg-red-100 tw-rounded-lg">
                    <i class="pi pi-reply tw-text-red-600"></i>
                  </div>
                  <div>
                    <h3 class="tw-font-semibold tw-text-slate-800 tw-text-sm">{{ retour.bon_retour_code }}</h3>
                    <p class="tw-text-xs tw-text-slate-500">{{ getRelativeTime(retour.created_at) }}</p>
                  </div>
                </div>
                <Tag
                  :value="getStatusLabel(retour.status)"
                  :severity="getStatusSeverity(retour.status)"
                  class="tw-text-xs status-indicator"
                />
              </div>

              <div class="tw-space-y-3 tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-building tw-text-slate-400 tw-text-sm"></i>
                  <span class="tw-text-sm tw-text-slate-600">{{ retour.fournisseur?.company_name || 'Unassigned' }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-calendar tw-text-slate-400 tw-text-sm"></i>
                  <span class="tw-text-sm tw-text-slate-600">{{ formatDate(retour.return_date) }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-tag tw-text-slate-400 tw-text-sm"></i>
                  <Tag :value="getReturnTypeLabel(retour.return_type)" :severity="getReturnTypeSeverity(retour.return_type)" class="tw-text-xs" />
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-dollar tw-text-green-500 tw-text-sm"></i>
                  <span class="tw-font-semibold tw-text-green-600">{{ formatCurrency(retour.total_amount) }}</span>
                </div>
              </div>

              <div class="tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-1">
                  <i class="pi pi-box tw-text-slate-400 tw-text-sm"></i>
                  <span class="tw-text-xs tw-text-slate-500">{{ retour.items?.length || 0 }} items</span>
                </div>
                <div class="tw-flex tw-gap-1">
                  <Button icon="pi pi-eye" size="small" text @click="viewRetour(retour.id)" v-tooltip.top="'View'" />
                  <Button icon="pi pi-pencil" size="small" text @click="editRetour(retour.id)" :disabled="!retour.is_editable" v-tooltip.top="'Edit'" />
                  <Button icon="pi pi-file-pdf" size="small" text @click="openPdfTemplateDialog(retour)" v-tooltip.top="'PDF'" />
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination for Card View -->
          <div class="tw-flex tw-justify-center tw-mt-6">
            <div class="tw-flex tw-items-center tw-gap-2">
              <Button
                icon="pi pi-chevron-left"
                @click="onPage({ page: currentPage - 1 })"
                :disabled="currentPage === 1"
                text
              />
              <span class="tw-text-sm tw-text-slate-600">
                Page {{ currentPage }} of {{ Math.ceil(retours.total / 15) }}
              </span>
              <Button
                icon="pi pi-chevron-right"
                @click="onPage({ page: currentPage + 1 })"
                :disabled="currentPage >= Math.ceil(retours.total / 15)"
                text
              />
            </div>
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
          <div class="tw-p-2 tw-bg-purple-100 tw-rounded-lg">
            <i class="pi pi-file-pdf tw-text-purple-600"></i>
          </div>
          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-slate-800">Generate PDF</h3>
            <p class="tw-text-sm tw-text-slate-600">Choose template and action for {{ selectedPdfBonRetour?.bon_retour_code }}</p>
          </div>
        </div>
      </template>

      <div class="tw-space-y-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">PDF Template</label>
          <div class="tw-grid tw-grid-cols-1 tw-gap-3">
            <div
              v-for="template in pdfTemplates"
              :key="template.value"
              class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-border tw-rounded-lg hover:tw-bg-slate-50 tw-cursor-pointer tw-transition-colors"
              :class="selectedTemplate === template.value ? 'tw-border-purple-300 tw-bg-purple-50' : 'tw-border-slate-200'"
              @click="selectedTemplate = template.value"
            >
              <RadioButton
                :modelValue="selectedTemplate"
                :value="template.value"
                @update:modelValue="selectedTemplate = $event"
              />
              <div class="tw-flex-1">
                <div class="tw-font-medium tw-text-slate-800">{{ template.label }}</div>
                <div class="tw-text-sm tw-text-slate-600">{{ template.description }}</div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Action</label>
          <div class="tw-grid tw-grid-cols-1 tw-gap-3">
            <div
              v-for="action in pdfActionOptions"
              :key="action.value"
              class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-border tw-rounded-lg hover:tw-bg-slate-50 tw-cursor-pointer tw-transition-colors"
              :class="pdfAction === action.value ? 'tw-border-blue-300 tw-bg-blue-50' : 'tw-border-slate-200'"
              @click="pdfAction = action.value"
            >
              <RadioButton
                :modelValue="pdfAction"
                :value="action.value"
                @update:modelValue="pdfAction = $event"
              />
              <div class="tw-flex tw-items-center tw-gap-2 tw-flex-1">
                <i :class="action.icon" class="tw-text-slate-600"></i>
                <div>
                  <div class="tw-font-medium tw-text-slate-800">{{ action.label }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="pdfAction === 'save'">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Save Path</label>
          <InputText
            v-model="savePath"
            placeholder="/downloads/returns/"
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-2">
          <Button label="Cancel" text @click="pdfTemplateDialog = false" />
          <Button
            label="Generate PDF"
            icon="pi pi-file-pdf"
            @click="generatePdf"
            :loading="generatingPdf"
            class="tw-bg-gradient-to-r tw-from-purple-500 tw-to-purple-600 hover:tw-from-purple-600 hover:tw-to-purple-700"
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
      <template #header>
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-p-2 tw-bg-green-100 tw-rounded-lg">
            <i class="pi pi-check-circle tw-text-green-600"></i>
          </div>
          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-slate-800">Approve Return</h3>
            <p class="tw-text-sm tw-text-slate-600">Approve return note {{ selectedRetour?.bon_retour_code }}</p>
          </div>
        </div>
      </template>

      <div class="tw-space-y-4">
        <div class="tw-p-4 tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg">
          <div class="tw-flex tw-items-start tw-gap-3">
            <i class="pi pi-info-circle tw-text-amber-600 tw-mt-0.5"></i>
            <div>
              <h4 class="tw-font-medium tw-text-amber-800">Approval Required</h4>
              <p class="tw-text-sm tw-text-amber-700 tw-mt-1">
                This return exceeds the approval threshold of {{ formatCurrency(approvalThreshold) }}.
                Please provide approval notes.
              </p>
            </div>
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Approval Notes</label>
          <Textarea
            v-model="approvalNotes"
            placeholder="Enter approval justification..."
            rows="3"
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-2">
          <Button label="Cancel" text @click="approvalDialog = false" />
          <Button
            label="Approve Return"
            icon="pi pi-check"
            @click="confirmApproval"
            :loading="requestingApproval"
            class="tw-bg-gradient-to-r tw-from-green-500 tw-to-green-600 hover:tw-from-green-600 hover:tw-to-green-700"
          />
        </div>
      </template>
    </Dialog>

    <!-- ConfirmDialog component -->
    <ConfirmDialog />

    <!-- Toast component -->
    <Toast />

    <!-- Floating Action Button -->
    <button
      @click="navigateToCreate"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Return'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>

    <Dialog
      :visible="showDeleteDialog"
      @update:visible="showDeleteDialog = $event"
      modal
      appendTo="self"
      header="Confirm Deletion"
      class="tw-w-full tw-max-w-md"
    >
      <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
        <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-2xl"></i>
        <span>
          Are you sure you want to delete the return note 
          <strong>{{ selectedRetour?.bon_retour_code }}</strong>?
        </span>
      </div>
      
      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-2">
          <Button label="Cancel" text @click="showDeleteDialog = false" />
          <Button 
            label="Delete" 
            severity="danger" 
            @click="deleteRetour"
            :loading="deleting"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
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
import SplitButton from 'primevue/splitbutton'
import RadioButton from 'primevue/radiobutton'
import Textarea from 'primevue/textarea'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// Reactive data
const loading = ref(true)
const deleting = ref(false)
const error = ref(null)
const generatingPdf = ref(false)
const requestingApproval = ref(false)

const retours = ref({ data: [], total: 0 })
const stats = ref({})
const suppliers = ref([])

const showDeleteDialog = ref(false)
const selectedRetour = ref(null)

// View Mode
const viewMode = ref('table') // 'table' or 'cards'
const currentPage = ref(1)

// PDF State
const pdfTemplateDialog = ref(false)
const selectedPdfBonRetour = ref(null)
const selectedTemplate = ref('default')
const pdfAction = ref('download') // 'download', 'preview', 'save'
const savePath = ref('/downloads/returns/')

// Approval State
const approvalDialog = ref(false)
const approvalNotes = ref('')
const approvalThreshold = ref(10000)

// Filters
const filters = reactive({
  status: null,
  return_type: null,
  fournisseur_id: null,
  date_from: null,
  search: ''
})

// Search debounce
let searchTimeout = null

// Computed Properties
const hasActiveFilters = computed(() => {
  return filters.status || filters.return_type || filters.fournisseur_id || filters.date_from || filters.search
})

// Options
const statusOptions = ref([
  { label: 'All Statuses', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
])

const returnTypeOptions = ref([
  { label: 'All Types', value: null },
  { label: 'Defective', value: 'defective' },
  { label: 'Expired', value: 'expired' },
  { label: 'Wrong Delivery', value: 'wrong_delivery' },
  { label: 'Overstock', value: 'overstock' },
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Other', value: 'other' }
])

const pdfTemplates = ref([
  {
    label: 'Standard Return',
    value: 'default',
    description: 'Standard return note with supplier details'
  },
  {
    label: 'Detailed Return',
    value: 'detailed',
    description: 'Detailed return with item specifications'
  },
  {
    label: 'Credit Note Ready',
    value: 'credit_note',
    description: 'Optimized for credit note generation'
  }
])

const pdfActionOptions = ref([
  { label: 'Download', value: 'download', icon: 'pi pi-download' },
  { label: 'Open in new tab', value: 'preview', icon: 'pi pi-external-link' },
  { label: 'Save to server', value: 'save', icon: 'pi pi-server' }
])

// Get PDF menu items for split button
const getPdfMenuItems = (bonRetour) => {
  return [
    {
      label: 'Standard PDF',
      icon: 'pi pi-file',
      command: () => {
        selectedPdfBonRetour.value = bonRetour
        selectedTemplate.value = 'default'
        pdfAction.value = 'download'
        generatePdf()
      }
    },
    {
      label: 'Detailed PDF',
      icon: 'pi pi-file-text',
      command: () => {
        selectedPdfBonRetour.value = bonRetour
        selectedTemplate.value = 'detailed'
        pdfAction.value = 'download'
        generatePdf()
      }
    },
    {
      separator: true
    },
    {
      label: 'Choose Template...',
      icon: 'pi pi-cog',
      command: () => openPdfTemplateDialog(bonRetour)
    }
  ]
}

// Methods
const fetchRetours = async (page = 1) => {
  try {
    loading.value = true
    error.value = null
    
    const params = { page, ...filters.value }
    
    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === '') {
        delete params[key]
      }
    })
    
    const response = await axios.get('/api/bon-retours', { params })
    
    if (response.data.status === 'success') {
      retours.value = response.data.data
    } else {
      throw new Error(response.data.message || 'Failed to fetch returns')
    }
  } catch (err) {
    console.error('Error fetching returns:', err)
    error.value = err.message || 'Failed to fetch returns'
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/bon-retours/statistics')
    
    if (response.data.status === 'success') {
      stats.value = response.data.data
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
  router.push('/purchasing/bon-retours/create')
}

const viewRetour = (id) => {
  router.push(`/purchasing/bon-retours/${id}`)
}

const editRetour = (id) => {
  router.push(`/purchasing/bon-retours/${id}/edit`)
}

const confirmDelete = (retour) => {
  selectedRetour.value = retour
  showDeleteDialog.value = true
}

const deleteRetour = async () => {
  try {
    deleting.value = true
    
    const response = await axios.delete(`/api/bon-retours/${selectedRetour.value.id}`)
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Return note deleted successfully'
      })
      
      showDeleteDialog.value = false
      selectedRetour.value = null
      await Promise.all([fetchRetours(), fetchStats()])
    } else {
      throw new Error(response.data.message || 'Failed to delete return')
    }
  } catch (err) {
    console.error('Error deleting return:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || err.message || 'Could not delete return'
    })
  } finally {
    deleting.value = false
  }
}

const approveRetour = async (retour) => {
  try {
    const response = await axios.post(`/api/bon-retours/${retour.id}/approve`)
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Return approved successfully'
      })
      await fetchRetours()
    }
  } catch (err) {
    console.error('Error approving return:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Could not approve return'
    })
  }
}

const completeRetour = async (retour) => {
  try {
    const response = await axios.post(`/api/bon-retours/${retour.id}/complete`)
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Return marked as completed'
      })
      await fetchRetours()
    }
  } catch (err) {
    console.error('Error completing return:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Could not complete return'
    })
  }
}

const applyFilters = async () => {
  currentPage.value = 1
  await fetchRetours()
}

const refreshData = async () => {
  await Promise.all([fetchRetours(), fetchStats()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const clearAllFilters = () => {
  filters.status = null
  filters.return_type = null
  filters.fournisseur_id = null
  filters.date_from = null
  filters.search = ''
  applyFilters()
}

const openPdfTemplateDialog = (bonRetour) => {
  selectedPdfBonRetour.value = bonRetour
  selectedTemplate.value = 'default'
  pdfAction.value = 'download'
  pdfTemplateDialog.value = true
}

const generatePdf = async () => {
  if (!selectedPdfBonRetour.value) return

  generatingPdf.value = true

  try {
    if (pdfAction.value === 'download') {
      // Download PDF
      const response = await axios.get(
        `/api/bon-retours/${selectedPdfBonRetour.value.id}/pdf?template=${selectedTemplate.value}`,
        { responseType: 'blob' }
      )

      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)

      const link = document.createElement('a')
      link.href = url
      link.download = `return-${selectedPdfBonRetour.value.bon_retour_code}-${selectedTemplate.value}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)

      setTimeout(() => window.URL.revokeObjectURL(url), 100)

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'PDF downloaded successfully',
        life: 3000
      })
    } else if (pdfAction.value === 'preview') {
      // Preview in new tab
      window.open(`/api/bon-retours/${selectedPdfBonRetour.value.id}/pdf?template=${selectedTemplate.value}`, '_blank')
    } else if (pdfAction.value === 'save') {
      // Save to server
      const response = await axios.post(
        `/api/bon-retours/${selectedPdfBonRetour.value.id}/pdf/save?template=${selectedTemplate.value}`,
        { path: savePath.value }
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

const requestApproval = (retour) => {
  selectedRetour.value = retour
  approvalNotes.value = ''
  approvalDialog.value = true
}

const confirmApproval = async () => {
  if (!selectedRetour.value) return

  requestingApproval.value = true

  try {
    const response = await axios.post(`/api/bon-retours/${selectedRetour.value.id}/approve`, {
      notes: approvalNotes.value
    })

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Return approved successfully',
        life: 3000
      })

      approvalDialog.value = false
      await Promise.all([fetchRetours(), fetchStats()])
    }
  } catch (err) {
    console.error('Error approving return:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Could not approve return',
      life: 3000
    })
  } finally {
    requestingApproval.value = false
  }
}

// Enhanced utility functions
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
const onPage = (event) => {
  fetchRetours(event.page + 1)
}

// Utility functions
const getStatusSeverity = (status) => {
  const severities = {
    draft: 'secondary',
    pending: 'warning',
    approved: 'info',
    completed: 'success',
    cancelled: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    pending: 'Pending',
    approved: 'Approved',
    completed: 'Completed',
    cancelled: 'Cancelled'
  }
  return labels[status] || status
}

const getReturnTypeSeverity = (type) => {
  const severities = {
    defective: 'danger',
    expired: 'warning',
    wrong_delivery: 'info',
    overstock: 'secondary',
    quality_issue: 'danger',
    other: 'secondary'
  }
  return severities[type] || 'info'
}

const getReturnTypeLabel = (type) => {
  const labels = {
    defective: 'Defective',
    expired: 'Expired',
    wrong_delivery: 'Wrong Delivery',
    overstock: 'Overstock',
    quality_issue: 'Quality Issue',
    other: 'Other'
  }
  return labels[type] || type
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
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchRetours(),
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
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: white;
  border: none;
  box-shadow: 0 4px 14px 0 rgba(220, 38, 38, 0.39);
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
  box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5);
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
