<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
              <i class="bi bi-hospital-user tw-text-white tw-text-xl"></i>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-mb-1">
                Patient Admissions
              </h1>
              <p class="tw-text-slate-600">Manage and track patient admissions and procedures</p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-xl tw-p-4 tw-shadow-lg tw-min-w-[140px]">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Total</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-slate-900">{{ statistics.total_admissions }}</p>
                </div>
                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="bi bi-person-check tw-text-blue-600"></i>
                </div>
              </div>
            </div>
            <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-xl tw-p-4 tw-shadow-lg tw-min-w-[140px]">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Active</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-slate-900">{{ statistics.active_admissions }}</p>
                </div>
                <div class="tw-w-10 tw-h-10 tw-bg-cyan-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="bi bi-activity tw-text-cyan-600"></i>
                </div>
              </div>
            </div>
            <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-xl tw-p-4 tw-shadow-lg tw-min-w-[140px]">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Surgery</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-slate-900">{{ statistics.surgery_admissions }}</p>
                </div>
                <div class="tw-w-10 tw-h-10 tw-bg-amber-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="bi bi-bandaid tw-text-amber-600"></i>
                </div>
              </div>
            </div>
            <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-xl tw-p-4 tw-shadow-lg tw-min-w-[140px]">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-sm tw-font-semibold tw-text-slate-600 tw-mb-1">Nursing</p>
                  <p class="tw-text-2xl tw-font-bold tw-text-slate-900">{{ statistics.nursing_admissions }}</p>
                </div>
                <div class="tw-w-10 tw-h-10 tw-bg-green-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="bi bi-heart-pulse tw-text-green-600"></i>
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
            <div class="tw-relative">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">Search Patient</label>
              <InputText
                v-model="filters.search"
                placeholder="Patient name or phone..."
                class="tw-w-full tw-px-4 tw-py-2.5 tw-border tw-border-slate-300 tw-rounded-lg focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
                @input="debounceSearch"
              />
              <i class="bi bi-search tw-absolute tw-right-3 tw-top-10 tw-text-slate-400"></i>
            </div>
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">Admission Type</label>
              <Dropdown
                v-model="filters.type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                placeholder="All Types"
                class="tw-w-full"
                @change="applyFilters"
              />
            </div>
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">Status</label>
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                class="tw-w-full"
                @change="applyFilters"
              />
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button
              @click="resetFilters"
              icon="pi pi-refresh"
              label="Reset Filters"
              class="p-button-outlined p-button-secondary"
            />
            <Button
              @click="refreshData"
              icon="pi pi-sync"
              label="Refresh"
              class="p-button-outlined"
              :loading="loading"
            />
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="tw-mt-4 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
          <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Active filters:</span>
          <Tag v-if="filters.search" 
               :value="`Search: ${filters.search}`" 
               severity="info" 
               @click="clearSearch" />
          <Tag v-if="filters.type" 
               :value="`Type: ${getTypeLabel(filters.type)}`" 
               severity="success" 
               @click="filters.type = null; applyFilters()" />
          <Tag v-if="filters.status" 
               :value="`Status: ${getStatusLabel(filters.status)}`" 
               severity="warning" 
               @click="filters.status = null; applyFilters()" />
          <Button @click="clearAllFilters" 
                  text 
                  size="small"
                  label="Clear All" />
        </div>
      </div>

      <!-- Enhanced Data Table with Card View Toggle -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <!-- View Toggle and Table Controls -->
        <div class="tw-p-4 tw-border-b tw-border-slate-200/60 tw-bg-slate-50/50">
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <h6 class="tw-text-lg tw-font-semibold tw-text-slate-900">
                <i class="bi bi-table tw-mr-2"></i> Admissions List
              </h6>
              <div class="tw-flex tw-items-center tw-gap-2 tw-bg-white tw-rounded-lg tw-p-1 tw-border tw-border-slate-200">
                <Button
                  :class="{ 'p-button-primary': viewMode === 'table', 'p-button-outlined': viewMode !== 'table' }"
                  icon="pi pi-table"
                  @click="viewMode = 'table'"
                  size="small"
                />
                <Button
                  :class="{ 'p-button-primary': viewMode === 'cards', 'p-button-outlined': viewMode !== 'cards' }"
                  icon="pi pi-th-large"
                  @click="viewMode = 'cards'"
                  size="small"
                />
              </div>
            </div>
            <div class="tw-text-sm tw-text-slate-600">
              {{ admissions.length }} admissions
            </div>
          </div>
        </div>

        <!-- Table View -->
                <div v-if="viewMode === 'table'">
          <DataTable
            :value="admissions"
            :loading="loading"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            responsiveLayout="scroll"
            class="p-datatable-sm tw-shadow-lg"
            :sortField="sortField"
            :sortOrder="sortOrder"
            @sort="onSort($event)"
            @row-click="onRowClick"
          >
          <Column field="patient.name" header="Patient" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="bi bi-person-fill tw-text-blue-600"></i>
                </div>
                <div>
                  <strong class="tw-block tw-text-slate-900">{{ slotProps.data.patient.name }}</strong>
                  <small class="tw-text-slate-500">{{ slotProps.data.patient.phone }}</small>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Doctor" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="bi bi-person-check tw-text-green-600"></i>
                </div>
                <div>
                  <strong class="tw-block tw-text-slate-900">{{ slotProps.data.doctor?.name || 'Unassigned' }}</strong>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Companion" :sortable="false">
            <template #body="slotProps">
              <div v-if="slotProps.data.companion" class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="bi bi-person-plus tw-text-purple-600"></i>
                </div>
                <div>
                  <strong class="tw-block tw-text-slate-900">{{ slotProps.data.companion.name }}</strong>
                  <small class="tw-text-slate-500">{{ slotProps.data.companion.phone }}</small>
                </div>
              </div>
              <span v-else class="tw-text-slate-400 tw-text-sm">—</span>
            </template>
          </Column>

          <Column field="type" header="Type" :sortable="true">
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.type_label" 
                :severity="slotProps.data.type === 'surgery' ? 'warning' : 'success'"
                :icon="`pi pi-${slotProps.data.type === 'surgery' ? 'medkit' : 'heart'}`"
              />
            </template>
          </Column>

          <Column field="status" header="Status" :sortable="true">
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.status_label" 
                :severity="getStatusSeverity(slotProps.data.status)"
              />
            </template>
          </Column>

          <Column header="Admitted" :sortable="true">
            <template #body="slotProps">
              <div class="tw-text-sm tw-text-slate-600">
                {{ formatDate(slotProps.data.admitted_at) }}
              </div>
            </template>
          </Column>

          <Column header="Actions" class="tw-text-right">
            <template #body="slotProps">
              <div class="tw-flex tw-justify-end tw-gap-2">
                <Button
                  @click.stop="viewAdmissionShow(slotProps.data.id)"
                  icon="pi pi-file-pdf"
                  class="p-button-rounded p-button-info p-button-text"
                  v-tooltip.top="'View Admission'"
                />
                <Button
                  @click.stop="viewFicheNavetteItems(slotProps.data.id)"
                  icon="pi pi-list"
                  class="p-button-rounded p-button-primary p-button-text"
                  v-tooltip.top="'Fiche Navette'"
                />
                <Button
                  @click="editAdmission(slotProps.data.id)"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-warning p-button-text"
                  v-tooltip.top="'Edit'"
                />
                <Button
                  v-if="slotProps.data.can_discharge"
                  @click="dischargeAdmission(slotProps.data.id)"
                  icon="pi pi-sign-out"
                  class="p-button-rounded p-button-success p-button-text"
                  v-tooltip.top="'Discharge'"
                />
                <Button
                  @click="deleteAdmission(slotProps.data.id)"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-danger p-button-text"
                  v-tooltip.top="'Delete'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
        </div>

        <!-- Card View -->
        <div v-else class="tw-p-6">
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <div 
              v-for="admission in admissions" 
              :key="admission.id"
              class="card-hover tw-bg-white tw-border tw-border-slate-200 tw-rounded-xl tw-p-6 tw-shadow-sm tw-transition-all tw-duration-200"
            >
              <div class="tw-flex tw-items-start tw-justify-between tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="bi bi-person-fill tw-text-blue-600"></i>
                  </div>
                  <div>
                    <h6 class="tw-font-semibold tw-text-slate-900">{{ admission.patient.name }}</h6>
                    <p class="tw-text-sm tw-text-slate-500">{{ admission.patient.phone }}</p>
                  </div>
                </div>
                <Tag 
                  :value="admission.status_label" 
                  :severity="getStatusSeverity(admission.status)"
                  size="small"
                />
              </div>

              <div class="tw-space-y-3 tw-mb-4">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="bi bi-person-check tw-text-green-600"></i>
                  <span class="tw-text-sm tw-text-slate-600">{{ admission.doctor?.name || 'Unassigned' }}</span>
                </div>
                <div v-if="admission.companion" class="tw-flex tw-items-center tw-gap-2">
                  <i class="bi bi-person-plus tw-text-purple-600"></i>
                  <span class="tw-text-sm tw-text-slate-600">{{ admission.companion.name }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i :class="`bi bi-${admission.type === 'surgery' ? 'bandaid' : 'heart-pulse'} tw-text-slate-600`"></i>
                  <Tag 
                    :value="admission.type_label" 
                    :severity="admission.type === 'surgery' ? 'warning' : 'success'"
                    size="small"
                  />
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="bi bi-calendar tw-text-slate-600"></i>
                  <span class="tw-text-sm tw-text-slate-600">{{ formatDate(admission.admitted_at) }}</span>
                </div>
              </div>

              <div class="tw-flex tw-gap-2">
                <Button
                  @click="viewAdmissionShow(admission.id)"
                  icon="pi pi-file-pdf"
                  label="Details"
                  class="p-button-sm p-button-info"
                />
                <Button
                  @click="viewFicheNavetteItems(admission.id)"
                  icon="pi pi-list"
                  label="Fiche"
                  class="p-button-sm p-button-primary p-button-outlined"
                />
                <Button
                  @click="editAdmission(admission.id)"
                  icon="pi pi-pencil"
                  label="Edit"
                  class="p-button-sm p-button-warning p-button-outlined"
                />
                <Button
                  v-if="admission.can_discharge"
                  @click="dischargeAdmission(admission.id)"
                  icon="pi pi-sign-out"
                  label="Discharge"
                  class="p-button-sm p-button-success p-button-outlined"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
          <div class="tw-inline-block tw-w-12 tw-h-12 tw-border-4 tw-border-blue-200 tw-border-t-blue-600 tw-rounded-full tw-animate-spin"></div>
          <p class="tw-text-slate-600 tw-mt-4">Loading admissions...</p>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && admissions.length === 0" class="tw-text-center tw-py-12">
          <i class="bi bi-inbox tw-text-6xl tw-text-slate-300 tw-block tw-mb-4"></i>
          <h5 class="tw-text-xl tw-font-semibold tw-text-slate-600 tw-mb-2">No admissions found</h5>
          <p class="tw-text-slate-500">Start by creating a new admission using the button below</p>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <AdmissionCreateModal ref="createModal" @saved="onAdmissionSaved" />

    <!-- Edit Modal -->
    <AdmissionCreateModal ref="editModal" @saved="onAdmissionSaved" />

    <!-- Floating Action Button -->
    <button 
      @click="openCreateModal"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Admission'"
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
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { AdmissionService } from '@/services/admissionService'
import { useToastr } from '@/Components/toster'
import AdmissionCreateModal from './AdmissionCreateModal.vue'

// Composables
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const toastr = useToastr()

// Refs
const createModal = ref(null)
const editModal = ref(null)

// State Management
const loading = ref(false)
const admissions = ref([])
const viewMode = ref('table') // 'table' or 'cards'
const sortField = ref('created_at')
const sortOrder = ref(-1) // -1 for descending, 1 for ascending

// Pagination (handled by DataTable now, but keeping for API)
const pagination = ref({
  current_page: 1,
  total: 0,
  per_page: 20,
  last_page: 1,
})

const statistics = ref({
  total_admissions: 0,
  active_admissions: 0,
  surgery_admissions: 0,
  nursing_admissions: 0,
})

// Filters
const filters = reactive({
  search: '',
  type: null,
  status: null,
})

// Search debounce
let searchTimeout = null

// Options
const typeOptions = [
  { label: 'All Types', value: null },
  { label: 'Surgery', value: 'surgery' },
  { label: 'Nursing', value: 'nursing' }
]

const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Admitted', value: 'admitted' },
  { label: 'In Service', value: 'in_service' },
  { label: 'Document Pending', value: 'document_pending' },
  { label: 'Ready for Discharge', value: 'ready_for_discharge' }
]

// Computed Properties
const hasActiveFilters = computed(() => {
  return filters.search || filters.type || filters.status
})

// API Methods
const fetchAdmissions = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v)),
    }

    const response = await AdmissionService.getAdmissions(params)
    admissions.value = response.data.data
    pagination.value = response.data.meta
  } catch (error) {
    toastr.error('Failed to load admissions')
    console.error('Error fetching admissions:', error)
  } finally {
    loading.value = false
  }
}

const fetchStatistics = async () => {
  try {
    const response = await AdmissionService.getStatistics()
    statistics.value = response.data.data
  } catch (error) {
    console.error(error)
  }
}

const applyFilters = async () => {
  pagination.value.current_page = 1
  await fetchAdmissions()
}

const refreshData = async () => {
  await Promise.all([fetchAdmissions(), fetchStatistics()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

// Sorting
const onSort = (event) => {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
}

// Utility Functions
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const resetFilters = () => {
  filters.search = ''
  filters.type = null
  filters.status = null
  pagination.value.current_page = 1
  fetchAdmissions()
}

const clearSearch = () => {
  filters.search = ''
  applyFilters()
}

const clearAllFilters = () => {
  filters.search = ''
  filters.type = null
  filters.status = null
  applyFilters()
}

const getTypeLabel = (value) => {
  const option = typeOptions.find(opt => opt.value === value)
  return option ? option.label : value
}

const getStatusLabel = (value) => {
  const option = statusOptions.find(opt => opt.value === value)
  return option ? option.label : value
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'admitted': return 'info'
    case 'in_service': return 'primary'
    case 'document_pending': return 'warning'
    case 'ready_for_discharge': return 'success'
    default: return 'secondary'
  }
}

// Action Methods
const openCreateModal = () => {
  createModal.value?.openModal()
}

const onAdmissionSaved = () => {
  // Refresh the admissions list after create/update
  fetchAdmissions()
  fetchStatistics()
}

const onRowClick = (event) => {
  viewAdmissionShow(event.data.id)
}

const viewAdmissionShow = (id) => {
  // Navigate to admission show/details page
  router.push(`/admissions/${id}`)
}

const viewFicheNavetteItems = async (id) => {
  try {
    loading.value = true
    // Create or get existing fiche navette for this admission
    const response = await AdmissionService.getOrCreateFicheNavette(id)
    
    if (response.data && response.data.data && response.data.data.fiche_navette_id) {
      // Navigate to the fiche navette items list within admission context
      router.push(`/admissions/fiche-navette/${response.data.data.fiche_navette_id}/items`)
    } else {
      toastr.error('Failed to create Fiche Navette')
    }
  } catch (error) {
    console.error('Error creating Fiche Navette:', error)
    toastr.error(error.response?.data?.message || 'Failed to open fiche navette')
  } finally {
    loading.value = false
  }
}

const viewAdmissionDetails = async (id) => {
  try {
    loading.value = true
    // Create or get existing fiche navette for this admission
    const response = await AdmissionService.getOrCreateFicheNavette(id)
    
    if (response.data && response.data.data && response.data.data.fiche_navette_id) {
      // Navigate to the fiche navette items list within admission context
      router.push(`/admissions/fiche-navette/${response.data.data.fiche_navette_id}/items`)
    } else {
      toastr.error('Failed to create Fiche Navette')
    }
  } catch (error) {
    console.error('Error creating Fiche Navette:', error)
    toastr.error(error.response?.data?.message || 'Failed to open admission')
  } finally {
    loading.value = false
  }
}

const editAdmission = async (id) => {
  try {
    loading.value = true
    const response = await AdmissionService.getAdmission(id)
    const admission = response.data.data
    
    // Open edit modal with admission data
    editModal.value?.openModal(admission)
  } catch (error) {
    console.error('Error loading admission for edit:', error)
    toastr.error('Failed to load admission for editing')
  } finally {
    loading.value = false
  }
}

const dischargeAdmission = async (id) => {
  confirm.require({
    message: 'Are you sure you want to discharge this patient?',
    header: 'Confirm Discharge',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await AdmissionService.dischargeAdmission(id)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Patient discharged successfully',
          life: 3000
        })
        fetchAdmissions()
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to discharge patient',
          life: 3000
        })
      }
    }
  })
}

const deleteAdmission = async (id) => {
  confirm.require({
    message: 'Are you sure you want to delete this admission? This action cannot be undone.',
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await AdmissionService.deleteAdmission(id)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Admission deleted successfully',
          life: 3000
        })
        fetchAdmissions()
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to delete admission',
          life: 3000
        })
      }
    }
  })
}

// Legacy pagination (keeping for compatibility)
const goToPage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  fetchAdmissions()
}

const pageNumbers = computed(() => {
  const pages = []
  const maxPages = 5
  let start = Math.max(1, pagination.value.current_page - 2)
  let end = Math.min(pagination.value.last_page, start + maxPages - 1)

  if (end - start < maxPages - 1) {
    start = Math.max(1, end - maxPages + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchAdmissions(),
    fetchStatistics()
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

/* PrimeVue overrides for better integration */
:deep(.p-button) {
  border-radius: 0.5rem;
}

:deep(.p-button.p-button-outlined) {
  border-width: 1px;
}

:deep(.p-tag) {
  border-radius: 0.375rem;
}

:deep(.p-dropdown) {
  border-radius: 0.5rem;
}

:deep(.p-inputtext) {
  border-radius: 0.5rem;
}
</style>
