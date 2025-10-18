<template>
  <div class="tw-bg-gray-50 tw-min-h-screen">
    <!-- Header Section -->
    <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-py-6">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
          <!-- Title & Stats -->
          <div class="tw-text-center md:tw-text-left">
            <h1 class="tw-text-3xl tw-font-bold tw-text-white tw-mb-2">
              Service Demands Management
            </h1>
            <div class="tw-flex tw-flex-wrap tw-gap-4 tw-text-sm tw-text-blue-100" v-if="stats">
              <span><i class="fas fa-list tw-mr-1"></i>{{ stats.total_demands || 0 }} Total</span>
              <span><i class="fas fa-edit tw-mr-1"></i>{{ stats.draft_demands || 0 }} Drafts</span>
              <span><i class="fas fa-paper-plane tw-mr-1"></i>{{ stats.sent_demands || 0 }} Sent</span>
              <span><i class="fas fa-check-circle tw-mr-1"></i>{{ stats.approved_demands || 0 }} Approved</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="tw-flex tw-gap-3">
            <Button 
              @click="showCreateForm = true" 
              icon="pi pi-plus" 
              class="p-button-success"
              label="New Service Demand"
            />
            <Button 
              @click="refreshData" 
              icon="pi pi-refresh" 
              class="p-button-secondary"
              :loading="loading"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-6">
      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
            <!-- Search -->
            <div class="tw-flex tw-items-center tw-gap-2">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-min-w-fit">Search:</label>
              <InputText 
                v-model="filters.search" 
                placeholder="Search demands..."
                class="tw-flex-1"
                @input="debounceSearch"
              />
            </div>

            <!-- Status Filter -->
            <div class="tw-flex tw-items-center tw-gap-2">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-min-w-fit">Status:</label>
              <Dropdown 
                v-model="filters.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                class="tw-flex-1"
                @change="filterData"
              />
            </div>

            <!-- Service Filter -->
            <div class="tw-flex tw-items-center tw-gap-2">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-min-w-fit">Service:</label>
              <Dropdown 
                v-model="filters.service_id"
                :options="services"
                option-label="name"
                option-value="id"
                placeholder="All Services"
                class="tw-flex-1"
                @change="filterData"
              />
            </div>

            <!-- Clear Filters -->
            <div class="tw-flex tw-justify-end">
              <Button 
                @click="clearFilters" 
                icon="pi pi-filter-slash" 
                class="p-button-text"
                label="Clear Filters"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Data Table Section -->
    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-pb-8">
      <Card class="tw-shadow-lg">
        <template #content>
          <DataTable 
            :value="demands" 
            :loading="loading"
            :paginator="true"
            :rows="15"
            :totalRecords="totalRecords"
            lazy
            @page="onPageChange"
            @sort="onSort"
            sortMode="multiple"
            class="p-datatable-sm"
            :rowHover="true"
            responsiveLayout="scroll"
          >
            <!-- Demand Code Column -->
            <Column field="demand_code" header="Demand Code" sortable style="width: 130px">
              <template #body="{ data }">
                <Badge :value="data.demand_code || 'N/A'" severity="info" />
              </template>
            </Column>

            <!-- Service Column -->
            <Column field="service.name" header="Service" sortable style="width: 180px">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="fas fa-building tw-text-blue-500"></i>
                  <span class="tw-font-medium">{{ data.service?.name || 'N/A' }}</span>
                </div>
              </template>
            </Column>

            <!-- Expected Date Column -->
            <Column field="expected_date" header="Expected Date" sortable style="width: 150px">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="fas fa-calendar tw-text-green-500"></i>
                  <span>{{ formatDate(data.expected_date) }}</span>
                </div>
              </template>
            </Column>

            <!-- Items Count Column -->
            <Column header="Items" style="width: 100px">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="fas fa-box tw-text-orange-500"></i>
                  <Badge :value="data.items?.length || 0" severity="warning" />
                </div>
              </template>
            </Column>

            <!-- Status Column -->
            <Column field="status" header="Status" sortable style="width: 120px">
              <template #body="{ data }">
                <Tag 
                  :value="getStatusLabel(data.status)" 
                  :severity="getStatusSeverity(data.status)"
                  :icon="getStatusIcon(data.status)"
                />
              </template>
            </Column>

            <!-- Notes Column -->
            <Column field="notes" header="Notes" style="width: 200px">
              <template #body="{ data }">
                <span 
                  v-if="data.notes" 
                  class="tw-text-sm tw-text-gray-600 tw-truncate tw-block"
                  :title="data.notes"
                >
                  {{ truncateText(data.notes, 50) }}
                </span>
                <span v-else class="tw-text-gray-400 tw-italic">No notes</span>
              </template>
            </Column>

            <!-- Created Date Column -->
            <Column field="created_at" header="Created" sortable style="width: 150px">
              <template #body="{ data }">
                <div class="tw-text-sm tw-text-gray-600">
                  {{ formatDateTime(data.created_at) }}
                </div>
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" style="width: 150px">
              <template #body="{ data }">
                <div class="tw-flex tw-gap-2">
                  <Button 
                    icon="pi pi-eye" 
                    class="p-button-sm p-button-info"
                    @click="viewDemandDetails(data)"
                    v-tooltip="'View Details'"
                  />
                  <Button 
                    icon="pi pi-pencil" 
                    class="p-button-sm p-button-warning"
                    @click="editDemand(data)"
                    v-tooltip="'Edit'"
                    :disabled="data.status !== 'draft'"
                  />
                  <Button 
                    icon="pi pi-send" 
                    class="p-button-sm p-button-success"
                    @click="sendDemand(data)"
                    v-tooltip="'Send'"
                    :disabled="data.status !== 'draft' || !data.items?.length"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog 
      :visible="showCreateForm"
      @update:visible="showCreateForm = $event"
      :header="editingDemand ? 'Edit Service Demand' : 'Create Service Demand'"
      :modal="true"
      :style="{ width: '600px' }"
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
    <Toast />
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

// Custom Components
import ServiceDemandForm from '@/Components/Apps/Purchasing/ServiceDemandForm.vue'

// Services
import ServiceDemandService from '@/Components/Apps/services/Purchasing/ServiceDemandService'

// Composables
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State
const demands = ref([])
const services = ref([])
const stats = ref(null)
const loading = ref(false)
const formLoading = ref(false)
const showCreateForm = ref(false)
const editingDemand = ref(null)
const totalRecords = ref(0)
const searchTimeout = ref(null)

// Filters
const filters = reactive({
  search: '',
  status: null,
  service_id: null,
  page: 1,
  sortField: null,
  sortOrder: null
})

// Status options for dropdown
const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
]

// Methods
const fetchDemands = async () => {
  loading.value = true
  try {
    const params = {
      search: filters.search || undefined,
      status: filters.status || undefined,
      service_id: filters.service_id || undefined,
      page: filters.page
    }

    // Add sorting if present
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

// Event Handlers
const viewDemandDetails = (demand) => {
  // Navigate to the product details page for this service demand
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
    message: `Are you sure you want to send "${demand.demand_code}"? This action cannot be undone.`,
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
            detail: result.message || 'Service demand sent successfully',
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
        detail: result.message || 'Service demand saved successfully',
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

// Filtering and Pagination
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

// Utility Functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(dateString))
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
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

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchServices(),
    fetchDemands(),
    fetchStats()
  ])
})
</script>

<style scoped>
/* Custom styles for the data table */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem 0.5rem;
  vertical-align: middle;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #f8fafc;
  color: #374151;
  font-weight: 600;
  border-bottom: 2px solid #e5e7eb;
}

:deep(.p-button-sm) {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.tw-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>