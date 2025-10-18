<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Main Content Container -->
    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-6">
      <!-- Header Section -->
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-6 tw-mb-6">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">Service Demands</h1>
            <p class="tw-text-sm tw-text-gray-600 tw-mt-1">Manage service procurement requests</p>
          </div>
          <Button 
            @click="showCreateForm = true" 
            label="Create Demand" 
            icon="pi pi-plus"
            class="tw-bg-blue-600 hover:tw-bg-blue-700 tw-border-blue-600"
          />
        </div>
      </div>

      <!-- Search and Filters Bar -->
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4 tw-mb-6">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-4">
          <!-- Search Input -->
          <div class="tw-flex-1">
            <span class="p-input-icon-left tw-w-full">
              <i class="pi pi-search" />
              <InputText 
                v-model="filters.search" 
                placeholder="Search by code, service, or notes..."
                class="tw-w-full"
                @input="debounceSearch"
              />
            </span>
          </div>

          <!-- Filters -->
          <div class="tw-flex tw-gap-2">
            <Dropdown 
              v-model="filters.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="All Status"
              class="tw-min-w-[150px]"
              @change="filterData"
            />

            <Dropdown 
              v-model="filters.service_id"
              :options="services"
              option-label="name"
              option-value="id"
              placeholder="All Services"
              class="tw-min-w-[180px]"
              @change="filterData"
            />

            <Button 
              v-if="filters.search || filters.status || filters.service_id"
              @click="clearFilters" 
              icon="pi pi-times" 
              class="p-button-outlined"
              v-tooltip="'Clear all filters'"
            />
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-overflow-hidden">
        <DataTable 
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
          class="p-datatable-sm"
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
                class="tw-mt-4 tw-bg-blue-600 hover:tw-bg-blue-700 tw-border-blue-600"
              />
            </div>
          </template>

          <!-- Demand Code Column -->
          <Column field="demand_code" header="Code" sortable style="width: 140px">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-w-1 tw-h-8 tw-rounded-full" 
                     :class="{
                       'tw-bg-gray-400': data.status === 'draft',
                       'tw-bg-blue-500': data.status === 'sent',
                       'tw-bg-green-500': data.status === 'approved',
                       'tw-bg-red-500': data.status === 'rejected'
                     }">
                </div>
                <span class="tw-font-mono tw-font-bold tw-text-indigo-900">{{ data.demand_code || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <!-- Service Column -->
          <Column field="service.name" header="Service" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
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
                  <span class="tw-text-gray-700 tw-font-medium">{{ formatDate(data.expected_date) }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-box tw-text-gray-400 tw-text-xs"></i>
                  <span class="tw-text-gray-600">
                    <span class="tw-font-semibold tw-text-gray-900">{{ data.items?.length || 0 }}</span> items
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
          <Column header="Actions" style="width: 140px">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-1">
                <Button 
                  icon="pi pi-eye" 
                  class="p-button-text p-button-sm"
                  @click="viewDemandDetails(data)"
                  v-tooltip.top="'View Details'"
                />
                <Button 
                  icon="pi pi-pencil" 
                  class="p-button-text p-button-sm"
                  @click="editDemand(data)"
                  v-tooltip.top="'Edit'"
                  :disabled="data.status !== 'draft'"
                />
                <Button 
                  icon="pi pi-send" 
                  class="p-button-text p-button-sm"
                  @click="sendDemand(data)"
                  v-tooltip.top="'Send'"
                  :disabled="data.status !== 'draft' || !data.items?.length"
                />
               
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog 
      :visible="showCreateForm"
      @update:visible="showCreateForm = $event"
      :header="editingDemand ? 'Edit Service Demand' : 'Create Service Demand'"
      :modal="true"
      :style="{ width: '90vw', maxWidth: '600px' }"
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
    params: { id: demand.id }
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

    if (editingDemand.value) {
      result = await ServiceDemandService.update(editingDemand.value.id, demandData)
    } else {
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

const handleCancel = () => {
  showCreateForm.value = false
  editingDemand.value = null
}

const showMoreOptions = (event, data) => {
  console.log('More options for:', data)
}

const rowClass = (data) => {
  if (data.status === 'rejected') return 'tw-bg-red-50/30'
  if (data.status === 'approved') return 'tw-bg-green-50/30'
  return ''
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

const getStatusLabel = (status) => {
  const statusMap = {
    draft: 'Draft',
    sent: 'Sent',
    approved: 'Approved',
    rejected: 'Rejected'
  }
  return statusMap[status] || status
}

const getStatusSeverity = (status) => {
  const severityMap = {
    draft: 'secondary',
    sent: 'info',
    approved: 'success',
    rejected: 'danger'
  }
  return severityMap[status] || 'secondary'
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

onMounted(async () => {
  await Promise.all([
    fetchServices(),
    fetchDemands(),
    fetchStats()
  ])
})
</script>

<style scoped>
/* DataTable styling */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  border-color: #e5e7eb;
  color: #374151;
  font-weight: 600;
  font-size: 0.875rem;
  padding: 0.75rem 1rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem 1rem;
  border-color: #e5e7eb;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}

:deep(.p-paginator) {
  padding: 1rem;
  background-color: #f9fafb;
  border-color: #e5e7eb;
}
</style>