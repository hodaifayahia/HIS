<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
      <div>
        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Goods Receipts</h1>
        <p class="tw-text-gray-600 tw-mt-1">Manage incoming order receptions</p>
      </div>
      <div class="tw-flex tw-gap-3">
        <Button 
          icon="pi pi-plus" 
          label="Create Receipt"
          @click="navigateToCreate"
          class="tw-bg-blue-600 hover:tw-bg-blue-700"
        />
        <Button 
          icon="pi pi-refresh" 
          label="Refresh"
          @click="fetchReceptions"
          outlined
        />
      </div>
    </div>

    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4 tw-mb-6">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
          <Dropdown
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Statuses"
            @change="fetchReceptions"
            class="tw-w-full"
          />
        </div>
        
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Supplier</label>
          <Dropdown
            v-model="filters.supplier_id"
            :options="suppliers"
            optionLabel="company_name"
            optionValue="id"
            placeholder="All Suppliers"
            @change="fetchReceptions"
            class="tw-w-full"
            filter
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Receipt Date</label>
          <Calendar
            v-model="filters.date_from"
            placeholder="Start Date"
            @date-select="fetchReceptions"
            showIcon
            class="tw-w-full"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
          <InputText
            v-model="filters.search"
            placeholder="Receipt Code..."
            @keyup.enter="fetchReceptions"
            class="tw-w-full"
          />
        </div>
      </div>
    </div>

    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4 tw-mb-6">
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Total Receipts</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.total || 0 }}</p>
          </div>
          <i class="pi pi-box tw-text-blue-500 tw-text-2xl"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Pending</p>
            <p class="tw-text-2xl tw-font-bold tw-text-yellow-600">{{ stats.pending || 0 }}</p>
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
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Surplus Items</p>
            <p class="tw-text-2xl tw-font-bold tw-text-purple-600">{{ stats.surplus_items || 0 }}</p>
          </div>
          <i class="pi pi-plus-circle tw-text-purple-500 tw-text-2xl"></i>
        </div>
      </div>

      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">With Returns</p>
            <p class="tw-text-2xl tw-font-bold tw-text-red-600">{{ stats.with_returns || 0 }}</p>
          </div>
          <i class="pi pi-reply tw-text-red-500 tw-text-2xl"></i>
        </div>
      </div>
    </div>

    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-8">
      <ProgressSpinner />
    </div>

    <Message v-if="error" severity="error" :closable="false" class="tw-mb-4">
      {{ error }}
    </Message>

    <div v-else class="tw-bg-white tw-rounded-lg tw-shadow-sm">
      <DataTable 
        :value="receptions.data" 
        :loading="loading"
        paginator 
        :rows="15"
        :totalRecords="receptions.total"
        :lazy="true"
        @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} receptions"
        responsiveLayout="scroll"
        class="tw-border-none"
        @row-click="onRowClick"
        :rowHover="true"
      >
        <template #empty>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-inbox tw-text-gray-400 tw-text-4xl tw-mb-4"></i>
            <p class="tw-text-gray-500">No goods receipts found</p>
          </div>
        </template>

        <Column field="bonReceptionCode" header="Receipt Code" class="tw-min-w-40">
          <template #body="slotProps">
            <span class="tw-font-mono tw-text-sm tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">
              {{ slotProps.data.bonReceptionCode }}
            </span>
          </template>
        </Column>

        <Column field="bonCommend.bonCommendCode" header="Purchase Order" class="tw-min-w-40">
          <template #body="slotProps">
            <span class="tw-font-mono tw-text-sm tw-text-blue-600">
              {{ slotProps.data.bon_commend?.bonCommendCode || 'N/A' }}
            </span>
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

        <Column field="date_reception" header="Receipt Date" class="tw-min-w-32">
          <template #body="slotProps">
            <span class="tw-text-sm">{{ formatDate(slotProps.data.date_reception) }}</span>
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

        <Column header="Items" class="tw-min-w-24">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <div class="tw-text-center">
                <span class="tw-text-sm tw-font-medium">{{ slotProps.data.items?.length || 0 }}</span>
                <p class="tw-text-xs tw-text-gray-500">items</p>
              </div>
              <div v-if="getSurplusCount(slotProps.data) > 0">
                <Tag 
                  :value="`+${getSurplusCount(slotProps.data)} surplus`"
                  severity="warning"
                  class="tw-text-xs"
                />
              </div>
            </div>
          </template>
        </Column>

        <Column header="Return Status" class="tw-min-w-32">
          <template #body="slotProps">
            <div v-if="slotProps.data.bon_retour_id" class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-reply tw-text-orange-500"></i>
              <span class="tw-text-sm">Return Created</span>
            </div>
            <span v-else class="tw-text-gray-400 tw-text-sm">-</span>
          </template>
        </Column>

        <Column header="Actions" class="tw-min-w-56">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button 
                icon="pi pi-eye" 
                size="small" 
                text
                @click.stop="viewReception(slotProps.data)"
                v-tooltip="'View details'"
              />
              <Button 
                icon="pi pi-pencil" 
                size="small" 
                text
                @click.stop="editReception(slotProps.data.id)"
                v-tooltip="'Edit'"
                :disabled="slotProps.data.status !== 'pending'"
              />
              <Button 
                v-if="slotProps.data.status === 'pending'"
                icon="pi pi-check-circle" 
                size="small" 
                text
                severity="success"
                @click.stop="openConfirmDialog(slotProps.data)"
                v-tooltip="'Confirm Receipt'"
              />
              <Button 
                v-if="slotProps.data.bon_retour_id"
                icon="pi pi-reply" 
                size="small" 
                text
                severity="warning"
                @click.stop="viewReturn(slotProps.data)"
                v-tooltip="'View Return'"
              />
              <Button 
                v-if="!slotProps.data.bon_commend_id"
                icon="pi pi-file-plus" 
                size="small" 
                text
                severity="info"
                @click.stop="createPurchaseOrderFromReceipt(slotProps.data)"
                v-tooltip="'Create Purchase Order'"
              />
              <Button 
                icon="pi pi-trash" 
                size="small" 
                text
                severity="danger"
                @click.stop="confirmDelete(slotProps.data)"
                v-tooltip="'Delete'"
                :disabled="slotProps.data.status !== 'pending'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- View/Edit Dialog -->
    <Dialog 
      v-model:visible="showReceptionDialog"
      :header="dialogTitle"
      :modal="true"
      :maximizable="true"
      :style="{ width: '90vw', maxWidth: '1200px' }"
      :contentStyle="{ height: '80vh', overflow: 'auto' }"
      class="tw-p-0"
    >
      <BonReceptionDetails
        v-if="selectedReception"
        :bon-reception-id="selectedReception.id"
        :mode="dialogMode"
        @saved="onReceptionSaved"
        @cancelled="closeReceptionDialog"
      />
    </Dialog>

    <!-- Confirm Reception Dialog with Surplus Handling -->
    <BonReceptionConfirmDialog
      v-model="showConfirmDialog"
      :bon-reception-id="selectedReception?.id"
      @confirmed="onReceptionConfirmed"
    />

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
          Are you sure you want to delete the receipt 
          <strong>{{ selectedReception?.bonReceptionCode }}</strong>?
        </span>
      </div>
      
      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-2">
          <Button label="Cancel" text @click="showDeleteDialog = false" />
          <Button 
            label="Delete" 
            severity="danger" 
            @click="deleteReception"
            :loading="deleting"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
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
import BonReceptionDetails from './BonReceptionItem.vue'
import BonReceptionConfirmDialog from './BonReceptionConfirmDialog.vue'

const router = useRouter()
const toast = useToast()

// Reactive data
const loading = ref(true)
const deleting = ref(false)
const error = ref(null)

const receptions = ref({ data: [], total: 0 })
const stats = ref({})
const suppliers = ref([])

const showReceptionDialog = ref(false)
const showDeleteDialog = ref(false)
const showConfirmDialog = ref(false)
const selectedReception = ref(null)
const dialogMode = ref('view')

// Computed
const dialogTitle = computed(() => {
  if (dialogMode.value === 'create') return 'Create Reception'
  if (dialogMode.value === 'edit') return `Edit Reception ${selectedReception.value?.bonReceptionCode}`
  return `Reception ${selectedReception.value?.bonReceptionCode}`
})

// Form data
const filters = ref({
  status: 'all',
  supplier_id: null,
  date_from: null,
  search: ''
})

// Options
const statusOptions = ref([
  { label: 'All Statuses', value: 'all' },
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Canceled', value: 'canceled' },
  { label: 'Rejected', value: 'rejected' }
])

// Methods
const fetchReceptions = async (page = 1) => {
  try {
    loading.value = true
    error.value = null
    
    const params = {
      page,
      status: filters.value.status,
      fournisseur_id: filters.value.supplier_id,
      date_from: filters.value.date_from,
      search: filters.value.search,
    }
    
    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === '' || params[key] === 'all') {
        delete params[key]
      }
    })
    
    const response = await axios.get('/api/bon-receptions', { params })
    
    if (response.data.status === 'success') {
      receptions.value = response.data.data
      
      // Calculate additional stats
      if (receptions.value.data) {
        stats.value.with_returns = receptions.value.data.filter(r => r.bon_retour_id).length
      }
    } else {
      throw new Error(response.data.message || 'Failed to fetch receptions')
    }
  } catch (err) {
    console.error('Error fetching receptions:', err)
    error.value = err.message || 'Failed to fetch receptions'
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/bon-receptions/meta/stats')
    
    if (response.data.status === 'success') {
      stats.value = { ...stats.value, ...response.data.data }
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
  router.push('/purchasing/bon-receptions/create')
}

const viewReception = (reception) => {
  selectedReception.value = reception
  dialogMode.value = 'view'
  showReceptionDialog.value = true
}

const editReception = (id) => {
  router.push(`/purchasing/bon-receptions/${id}`)
}

const openConfirmDialog = (reception) => {
  selectedReception.value = reception
  showConfirmDialog.value = true
}

const onReceptionConfirmed = (data) => {
  showConfirmDialog.value = false
  
  if (data.bonRetour) {
    toast.add({
      severity: 'success',
      summary: 'Reception Confirmed',
      detail: `Reception confirmed and return note ${data.bonRetour.bon_retour_code} created for surplus items`,
      life: 5000
    })
  } else {
    toast.add({
      severity: 'success',
      summary: 'Reception Confirmed',
      detail: 'Reception confirmed successfully'
    })
  }
  
  fetchReceptions()
  fetchStats()
}

const viewReturn = (reception) => {
  if (reception.bon_retour_id) {
    router.push(`/purchasing/bon-retours/${reception.bon_retour_id}`)
  }
}

const closeReceptionDialog = () => {
  showReceptionDialog.value = false
  selectedReception.value = null
}

const onReceptionSaved = () => {
  closeReceptionDialog()
  fetchReceptions()
  fetchStats()
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Reception saved successfully'
  })
}

const onRowClick = (event) => {
  viewReception(event.data)
}

const confirmDelete = (reception) => {
  selectedReception.value = reception
  showDeleteDialog.value = true
}

const deleteReception = async () => {
  try {
    deleting.value = true
    
    const response = await axios.delete(`/api/bon-receptions/${selectedReception.value.id}`)
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Receipt deleted successfully'
      })
      
      showDeleteDialog.value = false
      selectedReception.value = null
      await Promise.all([fetchReceptions(), fetchStats()])
    } else {
      throw new Error(response.data.message || 'Failed to delete reception')
    }
  } catch (err) {
    console.error('Error deleting reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || err.message || 'Could not delete reception'
    })
  } finally {
    deleting.value = false
  }
}

const createPurchaseOrderFromReceipt = async (receipt) => {
  if (!receipt || !receipt.items || receipt.items.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Items',
      detail: 'This receipt has no items to create a purchase order from'
    })
    return
  }

  if (!receipt.fournisseur_id) {
    toast.add({
      severity: 'warn',
      summary: 'Supplier Required',
      detail: 'This receipt must have a supplier to create a purchase order'
    })
    return
  }

  try {
    loading.value = true

    const bonCommendData = {
      fournisseur_id: receipt.fournisseur_id,
      order_date: new Date().toISOString().split('T')[0],
      status: 'confirmed',
      approval_status: 'approved',
      notes: `Created from receipt ${receipt.bonReceptionCode}. ${receipt.observation || ''}`,
      items: receipt.items.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity_received,
        price: item.unit_price || 0,
        unit: item.unit || 'piece',
        notes: item.notes || ''
      }))
    }

    const response = await axios.post('/api/bon-commends', bonCommendData)

    if (response.data.status === 'success') {
      const newBonCommend = response.data.data

      // Update the receipt to link it to the new bon commend
      await axios.put(`/api/bon-receptions/${receipt.id}`, {
        bon_commend_id: newBonCommend.id
      })

      toast.add({
        severity: 'success',
        summary: 'Purchase Order Created',
        detail: `Purchase order ${newBonCommend.bonCommendCode} created and linked successfully`
      })

      await fetchReceptions()
    } else {
      throw new Error(response.data.message || 'Failed to create purchase order')
    }
  } catch (err) {
    console.error('Error creating purchase order:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || err.message || 'Could not create purchase order'
    })
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  fetchReceptions(event.page + 1)
}

// Utility functions
const getSurplusCount = (reception) => {
  if (!reception.items) return 0
  return reception.items.filter(item => item.quantity_surplus > 0).length
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'warn',
    completed: 'success',
    canceled: 'secondary',
    rejected: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    completed: 'Completed',
    canceled: 'Canceled',
    rejected: 'Rejected'
  }
  return labels[status] || status
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

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchReceptions(),
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
