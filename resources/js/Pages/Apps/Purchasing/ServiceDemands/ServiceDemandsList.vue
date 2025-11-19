<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-list tw-text-2xl tw-text-white"></i>
              </div>
              <div class="tw-absolute -tw-bottom-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-500 tw-rounded-full tw-border-2 tw-border-white"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mb-1">Service Demands</h1>
              <p class="tw-text-sm tw-text-slate-600 tw-font-medium tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-check-circle tw-text-green-500"></i>
                Manage service procurement requests
              </p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-amber-100 tw-rounded-lg">
                  <i class="pi pi-clock tw-text-amber-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-amber-700 tw-font-medium tw-uppercase tw-tracking-wide tw-mb-0.5">Draft</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-amber-900">{{ stats?.draft || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                  <i class="pi pi-send tw-text-blue-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-blue-700 tw-font-medium tw-uppercase tw-tracking-wide tw-mb-0.5">Sent</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-blue-900">{{ stats?.sent || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-green-100 tw-rounded-lg">
                  <i class="pi pi-check tw-text-green-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-green-700 tw-font-medium tw-uppercase tw-tracking-wide tw-mb-0.5">Approved</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-green-900">{{ stats?.approved || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-gray-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-slate-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-slate-100 tw-rounded-lg">
                  <i class="pi pi-chart-bar tw-text-slate-600 tw-text-lg"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-slate-700 tw-font-medium tw-uppercase tw-tracking-wide tw-mb-0.5">Total</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-slate-900">{{ totalRecords || 0 }}</p>
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
              <i class="pi pi-search tw-absolute tw-left-4 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-slate-400 tw-z-10"></i>
              <InputText
                v-model="filters.search"
                placeholder="Search by code, service, or notes..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-rounded-xl tw-border-2 tw-border-slate-200 focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-200 tw-transition-all tw-duration-200"
                @input="debounceSearch"
              />
              <div v-if="filters.search" 
                   @click="clearSearch"
                   class="tw-absolute tw-right-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-cursor-pointer tw-text-slate-400 hover:tw-text-slate-600 tw-transition-colors">
                <i class="pi pi-times"></i>
              </div>
            </div>

            <!-- Filter Dropdowns -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                class="tw-min-w-[150px]"
                @change="filterData"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-circle-fill tw-text-xs" :class="getStatusColor(slotProps.value)"></i>
                    <span>{{ getStatusLabel({ status: slotProps.value }) }}</span>
                  </div>
                  <span v-else>All Status</span>
                </template>
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i v-if="slotProps.option.value" class="pi pi-circle-fill tw-text-xs" :class="getStatusColor(slotProps.option.value)"></i>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
              </Dropdown>

              <Dropdown
                v-model="filters.service_id"
                :options="services"
                option-label="name"
                option-value="id"
                placeholder="All Services"
                class="tw-min-w-[180px]"
                filter
                @change="filterData"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-cog tw-text-blue-500"></i>
                    <span>{{ getServiceName(slotProps.value) }}</span>
                  </div>
                  <span v-else>All Services</span>
                </template>
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-cog tw-text-blue-500"></i>
                    <span>{{ slotProps.option.name }}</span>
                  </div>
                </template>
              </Dropdown>
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button 
              @click="refreshData"
              icon="pi pi-refresh"
              v-tooltip.top="'Refresh data'"
              class="p-button-outlined p-button-secondary p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
              :loading="loading"
            />
            <Button 
              @click="createNewDemand"
              icon="pi pi-filter-slash"
              label="Clear Filters"
              class="p-button-outlined p-button-info p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
              v-if="hasActiveFilters"
              @click.stop="clearFilters"
            />
            <Button 
              @click="createNewDemand"
              label="Create Demand"
              icon="pi pi-plus"
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
               @click="filters.status = null; filterData()" />
          <Tag v-if="filters.service_id" 
               :value="`Service: ${getServiceName(filters.service_id)}`" 
               severity="info" 
               class="tw-rounded-lg"
               @click="filters.service_id = null; filterData()" />
          <Button @click="clearFilters" 
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
              <span class="tw-text-sm tw-font-medium tw-text-slate-600">
                Showing {{ demands.length }} of {{ totalRecords }} demands
              </span>
            </div>

            <div class="tw-flex tw-items-center tw-gap-2">
              <Button
                :class="['p-button-text p-button-sm', { 'tw-bg-blue-100 tw-text-blue-700': viewMode === 'table' }]"
                @click="viewMode = 'table'"
                icon="pi pi-table"
                v-tooltip="'Table view'"
              />
              <Button
                :class="['p-button-text p-button-sm', { 'tw-bg-blue-100 tw-text-blue-700': viewMode === 'cards' }]"
                @click="viewMode = 'cards'"
                icon="pi pi-th-large"
                v-tooltip="'Card view'"
              />
            </div>
          </div>
        </div>

        <!-- Table View -->
        <DataTable
          v-if="viewMode === 'table'"
          :value="demands"
          :loading="loading"
          :paginator="true"
          :rows="10"
          :totalRecords="totalRecords"
          lazy
          @page="onPageChange"
          @sort="onSort"
          sortMode="multiple"
          :rowHover="true"
          responsiveLayout="scroll"
          class="p-datatable-sm medical-table"
          :rowClass="rowClass"
        >
          <!-- Loading Template -->
          <template #loading>
            <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
              <div class="tw-relative">
                <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-200 tw-rounded-full"></div>
                <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-600 tw-border-t-transparent tw-rounded-full tw-animate-spin tw-absolute tw-top-0"></div>
              </div>
              <p class="tw-mt-4 tw-text-indigo-600 tw-font-medium">Loading demands...</p>
            </div>
          </template>

          <!-- Empty Template -->
          <template #empty>
            <div class="tw-text-center tw-py-16">
              <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-20 tw-h-20 tw-bg-gray-100 tw-rounded-full tw-mb-4">
                <i class="pi pi-inbox tw-text-3xl tw-text-gray-500"></i>
              </div>
              <p class="tw-text-xl tw-font-semibold tw-text-gray-800">No Service Demands Found</p>
              <p class="tw-text-gray-500 tw-mt-2">Create your first demand or adjust filters</p>
              <Button
                @click="showCreateForm = true"
                label="Create First Demand"
                icon="pi pi-plus"
                class="tw-mt-4 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 hover:tw-from-blue-700 hover:tw-to-indigo-700 tw-border-none tw-shadow-lg"
              />
            </div>
          </template>

          <!-- Demand Code Column -->
          <Column field="demand_code" header="Code" sortable style="width: 140px">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-w-1 tw-h-8 tw-rounded-full"
                     :class="data.is_pharmacy_order ? 'tw-bg-blue-500' : 'tw-bg-green-500'">
                </div>
                <span class="tw-font-mono tw-font-bold tw-text-indigo-900">{{ data.demand_code || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <!-- Service Column -->
          <Column field="service.name" header="Service" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <i :class="[
                     data.is_pharmacy_order ? 'pi pi-pills' : 'pi pi-box',
                     'tw-text-lg',
                     data.is_pharmacy_order ? 'tw-text-blue-500' : 'tw-text-green-500'
                   ]"
                   v-tooltip="data.is_pharmacy_order ? 'Pharmacy Order' : 'Stock Order'">
                </i>
                <Avatar
                  :label="data.service?.name?.charAt(0)"
                  class="tw-bg-gray-500 tw-text-white"
                  size="small"
                />
                <span class="tw-font-medium tw-text-gray-800">{{ data.service?.name || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <!-- Date & Items Column -->
          <Column header="Details" style="width: 200px">
            <template #body="{ data }">
              <div class="tw-space-y-1">
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-calendar tw-text-gray-400 tw-text-xs"></i>
                  <span class="tw-text-gray-700 tw-font-medium">{{ formatDate(data.created_at) }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-box tw-text-gray-400 tw-text-xs"></i>
                  <span class="tw-text-gray-600">
                    <span class="tw-font-semibold tw-text-gray-900">{{ data.items_count || 0 }}</span> items
                  </span>
                </div>
              </div>
            </template>
          </Column>

          <!-- Status Column -->
          <Column field="status" header="Status" sortable style="width: 120px">
            <template #body="{ data }">
              <Tag
                :value="getStatusLabel(data.status)"
                :severity="getStatusSeverity(data.status)"
                class="tw-px-3 tw-py-1 tw-font-semibold"
              />
            </template>
          </Column>

          <!-- Notes Column -->
          <Column field="notes" header="Notes">
            <template #body="{ data }">
              <div v-if="data.notes" class="tw-bg-gray-50 tw-text-gray-900 tw-px-2 tw-py-1 tw-rounded tw-text-sm">
                {{ truncateText(data.notes, 60) }}
              </div>
              <span v-else class="tw-text-sm tw-text-gray-400 tw-italic">No notes</span>
            </template>
          </Column>

          <!-- Actions Column -->
          <Column header="Actions" style="width: 80px">
            <template #body="{ data }">
              <Button
                icon="pi pi-ellipsis-v"
                class="p-button-text p-button-sm"
                @click="showActionsMenu($event, data)"
                v-tooltip.top="'Actions'"
              />
            </template>
          </Column>
        </DataTable>

        <!-- Card View -->
        <div v-else class="tw-p-6">
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div
              v-for="demand in paginatedDemands"
              :key="demand.id"
              class="tw-bg-white tw-rounded-xl tw-shadow-md tw-border tw-border-slate-200/60 tw-p-6 hover:tw-shadow-lg tw-transition-all tw-duration-200"
              :class="rowClass(demand)"
            >
              <div class="tw-flex tw-items-start tw-justify-between tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-1 tw-h-12 tw-rounded-full"
                       :class="demand.is_pharmacy_order ? 'tw-bg-blue-500' : 'tw-bg-green-500'">
                  </div>
                  <div>
                    <h3 class="tw-font-bold tw-text-lg tw-text-gray-900">{{ demand.demand_code || 'N/A' }}</h3>
                    <p class="tw-text-sm tw-text-gray-600">{{ demand.service?.name || 'N/A' }}</p>
                  </div>
                </div>
                <Tag
                  :value="getStatusLabel(demand.status)"
                  :severity="getStatusSeverity(demand.status)"
                  class="tw-text-xs"
                />
              </div>

              <div class="tw-space-y-3 tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-calendar tw-text-gray-400"></i>
                  <span class="tw-text-gray-700">{{ formatDate(demand.created_at) }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-box tw-text-gray-400"></i>
                  <span class="tw-text-gray-700">{{ demand.items_count || 0 }} items</span>
                </div>
                <div v-if="demand.notes" class="tw-bg-gray-50 tw-text-gray-900 tw-px-3 tw-py-2 tw-rounded tw-text-sm">
                  {{ truncateText(demand.notes, 80) }}
                </div>
              </div>

              <div class="tw-flex tw-items-center tw-justify-between tw-pt-4 tw-border-t tw-border-gray-100">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i :class="[
                       demand.is_pharmacy_order ? 'pi pi-pills' : 'pi pi-box',
                       demand.is_pharmacy_order ? 'tw-text-blue-500' : 'tw-text-green-500'
                     ]"
                     class="tw-text-lg">
                  </i>
                  <span class="tw-text-xs tw-font-medium tw-text-gray-600">
                    {{ demand.is_pharmacy_order ? 'Pharmacy' : 'Stock' }}
                  </span>
                </div>

                <div class="tw-flex tw-gap-1">
                  <Button
                    icon="pi pi-ellipsis-v"
                    class="p-button-text p-button-sm"
                    @click="showActionsMenu($event, demand)"
                    v-tooltip.top="'Actions'"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Card View Pagination -->
          <div class="tw-flex tw-justify-center tw-items-center tw-gap-4 tw-mt-8">
            <Button
              icon="pi pi-chevron-left"
              class="p-button-text"
              :disabled="currentPage === 1"
              @click="currentPage--"
            />
            <span class="tw-text-sm tw-text-gray-600">
              Page {{ currentPage }} of {{ totalPages }}
            </span>
            <Button
              icon="pi pi-chevron-right"
              class="p-button-text"
              :disabled="currentPage === totalPages"
              @click="currentPage++"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
          <div class="tw-relative">
            <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-200 tw-rounded-full"></div>
            <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-600 tw-border-t-transparent tw-rounded-full tw-animate-spin tw-absolute tw-top-0"></div>
          </div>
          <p class="tw-mt-4 tw-text-indigo-600 tw-font-medium tw-ml-4">Loading demands...</p>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
        <!-- Create/Edit Dialog -->
    <Dialog
      :visible="showCreateForm"
      @update:visible="showCreateForm = $event"
      :header="editingDemand ? 'Edit Service Demand' : 'Create Service Demand'"
      :modal="true"
      :style="{ width: '90vw', maxWidth: '600px' }"
      class="tw-rounded-2xl"
    >
      <ServiceDemandForm
        :demand="editingDemand"
        :services="services"
        @save="handleSave"
        @cancel="handleCancel"
        :loading="formLoading"
      />
    </Dialog>

    <!-- Toast for notifications -->
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Actions Menu -->
    <Menu ref="menu" :model="currentMenuItems" :popup="true" :visible="menuVisible" @hide="menuVisible = false" />

    <!-- Floating Action Button -->
    <button
      @click="createNewDemand"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Demand'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>

    <!-- Toast for notifications -->
    <Toast position="top-right" />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Avatar from 'primevue/avatar'
import Menu from 'primevue/menu'

// Custom Components
import ServiceDemandForm from '@/Components/Apps/Purchasing/ServiceDemandForm.vue'

// Services
import ServiceDemandService from '@/Components/Apps/services/Purchasing/ServiceDemandService'

// Composables
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State - ALL UNCHANGED
const demands = ref([])
const services = ref([])
const stats = ref(null)
const loading = ref(false)
const formLoading = ref(false)
const showCreateForm = ref(false)
const editingDemand = ref(null)
const totalRecords = ref(0)
const searchTimeout = ref(null)

// New reactive variables for enhanced UI
const viewMode = ref('table') // 'table' or 'cards'
const currentPage = ref(1)
const itemsPerPage = ref(9) // For card view pagination
const menu = ref()
const menuVisible = ref(false)
const currentMenuItems = ref([])

// Filters - ALL UNCHANGED
const filters = reactive({
  search: '',
  status: null,
  service_id: null,
  page: 1,
  sortField: null,
  sortOrder: null
})

// Status options - ALL UNCHANGED
const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
]

// Computed properties for enhanced UI
const hasActiveFilters = computed(() => {
  return filters.status || filters.service_id || filters.search
})

const paginatedDemands = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return demands.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(demands.value.length / itemsPerPage.value)
})

// ALL METHODS UNCHANGED (keeping exact same functionality)
const fetchDemands = async () => {
  loading.value = true
  try {
    const params = {
      search: filters.search || undefined,
      status: filters.status || undefined,
      service_id: filters.service_id || undefined,
      page: filters.page
    }

    if (filters.sortField) {
      params.sortField = filters.sortField
      params.sortOrder = filters.sortOrder
    }

    const result = await ServiceDemandService.getAll(params)

    if (result.success) {
      demands.value = result.data.data || []
      totalRecords.value = result.data.total || 0
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: result.message || 'Failed to fetch service demands',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error fetching demands:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch service demands',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchServices = async () => {
  try {
    const result = await ServiceDemandService.getServices()
    if (result.success) {
      services.value = result.data || []
    }
  } catch (error) {
    console.error('Error fetching services:', error)
  }
}

const fetchStats = async () => {
  try {
    const result = await ServiceDemandService.getStats()
    if (result.success) {
      stats.value = result.data
    }
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const refreshData = async () => {
  await Promise.all([
    fetchDemands(),
    fetchStats()
  ])
}

const viewDemandDetails = (demand) => {
  router.push({ 
    name: 'purchasing.service-demands.detail', 
    params: { id: demand.id },
    query: { is_pharmacy_order: demand.is_pharmacy_order }
  })
}

const editDemand = (demand) => {
  editingDemand.value = { ...demand }
  showCreateForm.value = true
}

const sendDemand = (demand) => {
  confirm.require({
    message: `Send demand "${demand.demand_code}" for approval?`,
    header: 'Confirm Send',
    icon: 'pi pi-send',
    acceptClass: 'p-button-success',
    accept: async () => {
      try {
        const result = await ServiceDemandService.send(demand.id)

        if (result.success) {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Service demand sent successfully',
            life: 3000
          })
          await refreshData()
        } else {
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: result.message || 'Failed to send service demand',
            life: 3000
          })
        }
      } catch (error) {
        console.error('Error sending demand:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to send service demand',
          life: 3000
        })
      }
    }
  })
}

const handleSave = async (demandData) => {
  formLoading.value = true
  try {
    let result
    demandData.status = 'sent' // Ensure status is sent on save
    if (editingDemand.value) {
      result = await ServiceDemandService.update(editingDemand.value.id, demandData)
    } else {
       demandData.status = 'sent' // Ensure status is sent on save
      result = await ServiceDemandService.store(demandData)
    }

    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Service demand saved successfully',
        life: 3000
      })

      showCreateForm.value = false
      editingDemand.value = null
      await refreshData()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: result.message || 'Failed to save service demand',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error saving demand:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save service demand',
      life: 3000
    })
  } finally {
    formLoading.value = false
  }
}

const createNewDemand = () => {
  editingDemand.value = null
  showCreateForm.value = true
}

const handleCancel = () => {
  showCreateForm.value = false
  editingDemand.value = null
}

const showActionsMenu = (event, demand) => {
  const menuItems = [
    {
      label: 'View Details',
      icon: 'pi pi-eye',
      command: () => viewDemandDetails(demand)
    },
    {
      label: 'Edit',
      icon: 'pi pi-pencil',
      command: () => editDemand(demand),
      disabled: demand.status !== 'draft'
    },
    {
      label: 'Send',
      icon: 'pi pi-send',
      command: () => sendDemand(demand),
      disabled: demand.status !== 'draft' || !demand.items?.length
    }
  ]
  
  currentMenuItems.value = menuItems
  menuVisible.value = true
  menu.value.show(event)
}

const showMoreOptions = (event, data) => {
  console.log('More options for:', data)
}

const rowClass = (data) => {
  let classes = []
  
  // Base color by type
  if (data.is_pharmacy_order) {
    classes.push('tw-bg-blue-50/20')
  } else {
    classes.push('tw-bg-green-50/20')
  }
  
  // Overlay status colors
  if (data.status === 'rejected') classes.push('tw-bg-red-50/40')
  if (data.status === 'approved') classes.push('tw-bg-green-50/40')
  
  return classes.join(' ')
}

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }

  searchTimeout.value = setTimeout(() => {
    filters.page = 1
    fetchDemands()
  }, 500)
}

const clearSearch = () => {
  filters.search = ''
  fetchDemands()
}

const filterData = () => {
  filters.page = 1
  fetchDemands()
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
  filters.service_id = null
  filters.page = 1
  fetchDemands()
}

const onPageChange = (event) => {
  filters.page = event.page + 1
  fetchDemands()
}

const onSort = (event) => {
  filters.sortField = event.sortField
  filters.sortOrder = event.sortOrder === 1 ? 'asc' : 'desc'
  fetchDemands()
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(dateString))
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const getStatusLabel = (data) => {
  const status = data.status || 'draft'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'draft': return 'warning'
    case 'sent': return 'info'
    case 'approved': return 'success'
    case 'rejected': return 'danger'
    default: return 'secondary'
  }
}

const getStatusColor = (status) => {
  switch (status) {
    case 'draft': return 'tw-text-amber-500'
    case 'sent': return 'tw-text-blue-500'
    case 'approved': return 'tw-text-green-500'
    case 'rejected': return 'tw-text-red-500'
    default: return 'tw-text-slate-500'
  }
}

const getStatusIcon = (status) => {
  const iconMap = {
    draft: 'pi pi-pencil',
    sent: 'pi pi-send',
    approved: 'pi pi-check',
    rejected: 'pi pi-times'
  }
  return iconMap[status] || 'pi pi-circle'
}

const getServiceName = (serviceId) => {
  const service = services.value.find(s => s.id === serviceId)
  return service?.name || 'Unknown Service'
}

onMounted(async () => {
  await Promise.all([
    fetchServices(),
    fetchDemands(),
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

/* Enhanced DataTable Styles */
:deep(.p-datatable) {
  border: none;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f8fafc;
  color: #374151;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 0.75rem;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: rgba(59, 130, 246, 0.05);
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
  background-color: #eff6ff;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 0.75rem;
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
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

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

/* Enhanced card hover effects */
.card-hover {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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