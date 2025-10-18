<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
      <div>
        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Return Notes</h1>
        <p class="tw-text-gray-600 tw-mt-1">Manage supplier returns and credit notes</p>
      </div>
      <div class="tw-flex tw-gap-3">
        <Button 
          icon="pi pi-plus" 
          label="Create Return"
          @click="openCreateDialog"
          class="tw-bg-red-600 hover:tw-bg-red-700"
        />
        <Button 
          icon="pi pi-refresh" 
          label="Refresh"
          @click="fetchRetours"
          outlined
        />
      </div>
    </div>

    <!-- Filters Section -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4 tw-mb-6">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
          <Dropdown
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Statuses"
            @change="fetchRetours"
            class="tw-w-full"
          />
        </div>
        
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Return Type</label>
          <Dropdown
            v-model="filters.return_type"
            :options="returnTypeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Types"
            @change="fetchRetours"
            class="tw-w-full"
          />
        </div>
        
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Supplier</label>
          <Dropdown
            v-model="filters.fournisseur_id"
            :options="suppliers"
            optionLabel="company_name"
            optionValue="id"
            placeholder="All Suppliers"
            @change="fetchRetours"
            class="tw-w-full"
            filter
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Return Date</label>
          <Calendar
            v-model="filters.date_from"
            placeholder="Start Date"
            @date-select="fetchRetours"
            showIcon
            class="tw-w-full"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
          <InputText
            v-model="filters.search"
            placeholder="Return Code..."
            @keyup.enter="fetchRetours"
            class="tw-w-full"
          />
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4 tw-mb-6">
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Total Returns</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.total_returns || 0 }}</p>
          </div>
          <i class="pi pi-reply tw-text-red-500 tw-text-2xl"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Pending Approval</p>
            <p class="tw-text-2xl tw-font-bold tw-text-yellow-600">{{ stats.pending_approvals || 0 }}</p>
          </div>
          <i class="pi pi-clock tw-text-yellow-500 tw-text-2xl"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Completed</p>
            <p class="tw-text-2xl tw-font-bold tw-text-green-600">{{ stats.completed || 0 }}</p>
          </div>
          <i class="pi pi-check-circle tw-text-green-500 tw-text-2xl"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Total Amount</p>
            <p class="tw-text-xl tw-font-bold tw-text-purple-600">{{ formatCurrency(stats.total_amount || 0) }}</p>
          </div>
          <i class="pi pi-dollar tw-text-purple-500 tw-text-2xl"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Credit Notes Pending</p>
            <p class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ stats.credit_notes_pending || 0 }}</p>
          </div>
          <i class="pi pi-file-edit tw-text-blue-500 tw-text-2xl"></i>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-8">
      <ProgressSpinner />
    </div>

    <!-- Error Message -->
    <Message v-if="error" severity="error" :closable="false" class="tw-mb-4">
      {{ error }}
    </Message>

    <!-- Data Table -->
    <div v-else class="tw-bg-white tw-rounded-lg tw-shadow-sm">
      <DataTable 
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
        class="tw-border-none"
        @row-click="onRowClick"
        :rowHover="true"
      >
        <template #empty>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-inbox tw-text-gray-400 tw-text-4xl tw-mb-4"></i>
            <p class="tw-text-gray-500">No return notes found</p>
          </div>
        </template>

        <!-- Table columns (same as before but with updated actions) -->
        <Column field="bon_retour_code" header="Return Code" class="tw-min-w-40">
          <template #body="slotProps">
            <span class="tw-font-mono tw-text-sm tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">
              {{ slotProps.data.bon_retour_code }}
            </span>
          </template>
        </Column>

        <Column field="return_type" header="Return Type" class="tw-min-w-32">
          <template #body="slotProps">
            <Tag 
              :value="getReturnTypeLabel(slotProps.data.return_type)" 
              :severity="getReturnTypeSeverity(slotProps.data.return_type)"
            />
          </template>
        </Column>

        <Column field="fournisseur.company_name" header="Supplier" class="tw-min-w-48">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-building tw-text-gray-400"></i>
              <span>{{ slotProps.data.fournisseur?.company_name || 'Unassigned' }}</span>
            </div>
          </template>
        </Column>

        <Column field="return_date" header="Return Date" class="tw-min-w-32">
          <template #body="slotProps">
            <span class="tw-text-sm">{{ formatDate(slotProps.data.return_date) }}</span>
          </template>
        </Column>

        <Column field="total_amount" header="Total Amount" class="tw-min-w-32">
          <template #body="slotProps">
            <span class="tw-font-semibold tw-text-green-600">
              {{ formatCurrency(slotProps.data.total_amount) }}
            </span>
          </template>
        </Column>

        <Column field="status" header="Status" class="tw-min-w-32">
          <template #body="slotProps">
            <Tag 
              :value="getStatusLabel(slotProps.data.status)" 
              :severity="getStatusSeverity(slotProps.data.status)"
            />
          </template>
        </Column>

        <Column header="Actions" class="tw-min-w-48">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button 
                icon="pi pi-eye" 
                size="small" 
                text
                @click.stop="viewRetour(slotProps.data)"
                v-tooltip="'View details'"
              />
              <Button 
                icon="pi pi-pencil" 
                size="small" 
                text
                @click.stop="editRetour(slotProps.data)"
                v-tooltip="'Edit'"
                :disabled="!slotProps.data.is_editable"
              />
              <Button 
                v-if="slotProps.data.status === 'pending'"
                icon="pi pi-check" 
                size="small" 
                text
                severity="success"
                @click.stop="approveRetour(slotProps.data)"
                v-tooltip="'Approve'"
              />
              <Button 
                icon="pi pi-file-pdf" 
                size="small" 
                text
                severity="help"
                @click.stop="generatePdf(slotProps.data)"
                v-tooltip="'Generate PDF'"
              />
              <Button 
                icon="pi pi-trash" 
                size="small" 
                text
                severity="danger"
                @click.stop="confirmDelete(slotProps.data)"
                v-tooltip="'Delete'"
                :disabled="!slotProps.data.is_editable"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- View/Edit Dialog -->
    <Dialog 
      v-model:visible="showRetourDialog"
      :header="dialogTitle"
      :modal="true"
      :maximizable="true"
      :style="{ width: '90vw', maxWidth: '1200px' }"
      :contentStyle="{ height: '80vh', overflow: 'auto' }"
      class="tw-p-0"
    >
      <!-- Render the form component for create OR when a retour is selected. When creating, selectedRetour is null. -->
      <BonRetourListItem 
        v-if="dialogMode === 'create' || selectedRetour"
        :bon-retour-id="selectedRetour ? selectedRetour.id : null"
        :mode="dialogMode"
        @saved="onRetourSaved"
        @cancelled="closeRetourDialog"
      />
    </Dialog>

    <!-- Delete Confirmation Dialog -->
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
import { ref, computed, onMounted } from 'vue'
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

// Custom Components
import BonRetourListItem from './BonRetourListItem.vue'

const toast = useToast()

// Reactive data
const loading = ref(true)
const deleting = ref(false)
const error = ref(null)

const retours = ref({ data: [], total: 0 })
const stats = ref({})
const suppliers = ref([])

// Dialog states
const showRetourDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedRetour = ref(null)
const dialogMode = ref('view') // 'view', 'edit', 'create'

// Computed
const dialogTitle = computed(() => {
  if (dialogMode.value === 'create') return 'Create Return Note'
  if (dialogMode.value === 'edit') return `Edit Return ${selectedRetour.value?.bon_retour_code}`
  return `Return Note ${selectedRetour.value?.bon_retour_code}`
})

// Filters
const filters = ref({
  status: null,
  return_type: null,
  fournisseur_id: null,
  date_from: null,
  search: ''
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
    
    // if (response.data.status === 'success') {
      suppliers.value = response.data.data
    // }
  } catch (err) {
    console.error('Error fetching suppliers:', err)
  }
}

// Dialog actions
const openCreateDialog = () => {
  selectedRetour.value = null
  dialogMode.value = 'create'
  showRetourDialog.value = true
}

const viewRetour = (retour) => {
  selectedRetour.value = retour
  dialogMode.value = 'view'
  showRetourDialog.value = true
}

const editRetour = (retour) => {
  selectedRetour.value = retour
  dialogMode.value = 'edit'
  showRetourDialog.value = true
}

const closeRetourDialog = () => {
  showRetourDialog.value = false
  selectedRetour.value = null
}

const onRetourSaved = () => {
  closeRetourDialog()
  fetchRetours()
  fetchStats()
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: dialogMode.value === 'create' ? 'Return note created successfully' : 'Return note updated successfully'
  })
}

const onRowClick = (event) => {
  viewRetour(event.data)
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

const generatePdf = async (retour) => {
  try {
    window.open(`/api/bon-retours/${retour.id}/pdf`, '_blank')
  } catch (err) {
    console.error('Error generating PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Could not generate PDF'
    })
  }
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
.p-datatable {
  border: 0;
}

.p-datatable .p-datatable-thead > tr > th {
  background-color: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.p-datatable .p-datatable-tbody > tr {
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f9fafb;
}

:deep(.p-dialog-content) {
  padding: 0 !important;
}
</style>
