<script setup>
import { ref, onMounted, computed } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Badge from 'primevue/badge'
import Chip from 'primevue/chip'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Calendar from 'primevue/calendar'

import axios from 'axios'

// Composables
const confirm = useConfirm()
const toast = useToast()

// State
const reserves = ref([])
const pharmacyProducts = ref([])
const pharmacyStockages = ref([])
const loading = ref(false)
const showDialog = ref(false)
const dialogMode = ref('create')
const selectedReserve = ref(null)
const searchQuery = ref('')
const totalRecords = ref(0)
const statusFilter = ref(null)

// Pagination
const lazyParams = ref({
  first: 0,
  rows: 20,
  page: 1
})

// Form State
const form = ref({
  pharmacy_product_id: null,
  pharmacy_stockage_id: null,
  quantity: 1,
  reservation_notes: '',
  expires_at: null,
  source: 'manual'
})

// Cancel Dialog
const showCancelDialog = ref(false)
const cancelForm = ref({
  reserve_id: null,
  reason: '',
  custom_reason: ''
})

const defaultCancelReasons = [
  { label: 'Patient cancelled appointment', value: 'patient_cancelled' },
  { label: 'Product out of stock', value: 'out_of_stock' },
  { label: 'Price issue', value: 'price_issue' },
  { label: 'Alternative product chosen', value: 'alternative_chosen' },
  { label: 'Reservation expired', value: 'expired' },
  { label: 'Duplicate reservation', value: 'duplicate' },
  { label: 'Administrative error', value: 'admin_error' },
  { label: 'Other (Custom)', value: 'custom' }
]

const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Pending', value: 'pending' },
  { label: 'Fulfilled', value: 'fulfilled' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Expired', value: 'expired' }
]

// Computed
const filteredReserves = computed(() => {
  let filtered = reserves.value

  // Apply status filter
  if (statusFilter.value) {
    filtered = filtered.filter(r => r.status === statusFilter.value)
  }

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(reserve => {
      const pharmacyProductName = reserve.pharmacy_product?.name?.toLowerCase() || ''
      const reserverName = reserve.reserver?.name?.toLowerCase() || ''
      const code = reserve.reservation_code?.toLowerCase() || ''
      
      return pharmacyProductName.includes(query) || 
             reserverName.includes(query) ||
             code.includes(query)
    })
  }

  return filtered
})

// Status severity mapping
const getStatusSeverity = (status) => {
  const map = {
    pending: 'warning',
    fulfilled: 'success',
    cancelled: 'danger',
    expired: 'secondary'
  }
  return map[status] || 'info'
}

// Get status counts
const getStatusCount = (status) => {
  return reserves.value.filter(r => r.status === status).length
}

// Load reserves
const loadReserves = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/product-reserves', {
      params: {
        page: lazyParams.value.page,
        per_page: lazyParams.value.rows
      }
    })
    
    console.log('Reserves loaded:', response.data)
    reserves.value = response.data.data || []
    totalRecords.value = response.data.meta?.total || reserves.value.length
    
  } catch (error) {
    console.error('Failed to load reserves:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product reserves',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Load pharmacy products
const loadPharmacyProducts = async () => {
  try {
    // OPTIMIZED: Load with pagination (backend caps at 100)
    const response = await axios.get('/api/pharmacy/products', { 
      params: { per_page: 10 } 
    })
    pharmacyProducts.value = response.data.data || []
    
    console.log('Pharmacy products loaded:', pharmacyProducts.value.length)
  } catch (error) {
    console.error('Failed to load pharmacy products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pharmacy products',
      life: 3000
    })
  }
}

// Load pharmacy stockages
const loadPharmacyStockages = async () => {
  try {
    const response = await axios.get('/api/pharmacy-stockages')
    pharmacyStockages.value = response.data.data || []
    
    console.log('Pharmacy stockages loaded:', pharmacyStockages.value.length)
  } catch (error) {
    console.error('Failed to load pharmacy stockages:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pharmacy stockages',
      life: 3000
    })
  }
}

// Open create dialog
const openCreateDialog = () => {
  dialogMode.value = 'create'
  selectedReserve.value = null
  resetForm()
  showDialog.value = true
}

// Open edit dialog
const openEditDialog = (reserve) => {
  dialogMode.value = 'edit'
  selectedReserve.value = reserve
  form.value = {
    pharmacy_product_id: reserve.pharmacy_product_id,
    pharmacy_stockage_id: reserve.pharmacy_stockage_id,
    quantity: reserve.quantity,
    expires_at: reserve.expires_at ? new Date(reserve.expires_at) : null,
    reservation_notes: reserve.reservation_notes || '',
    source: reserve.source || 'manual'
  }
  showDialog.value = true
}

// Open view dialog
const openViewDialog = (reserve) => {
  dialogMode.value = 'view'
  selectedReserve.value = reserve
  form.value = {
    pharmacy_product_id: reserve.pharmacy_product_id,
    pharmacy_stockage_id: reserve.pharmacy_stockage_id,
    quantity: reserve.quantity,
    expires_at: reserve.expires_at ? new Date(reserve.expires_at) : null,
    reservation_notes: reserve.reservation_notes || '',
    source: reserve.source || 'manual'
  }
  showDialog.value = true
}

// Reset form
const resetForm = () => {
  form.value = {
    pharmacy_product_id: null,
    pharmacy_stockage_id: null,
    quantity: 1,
    expires_at: null,
    reservation_notes: '',
    source: 'manual'
  }
}

// Save reserve
const saveReserve = async () => {
  if (!form.value.pharmacy_product_id) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please select a pharmacy product',
      life: 3000
    })
    return
  }

  if (!form.value.quantity || form.value.quantity < 1) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Quantity must be at least 1',
      life: 3000
    })
    return
  }

  loading.value = true
  try {
    if (dialogMode.value === 'create') {
      await axios.post('/api/product-reserves', form.value)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Pharmacy product reserved successfully',
        life: 3000
      })
    } else {
      await axios.put(`/api/product-reserves/${selectedReserve.value.id}`, form.value)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reservation updated successfully',
        life: 3000
      })
    }
    
    showDialog.value = false
    loadReserves()
    resetForm()
    
  } catch (error) {
    console.error('Failed to save reserve:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save reservation',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Open cancel dialog
const openCancelDialog = (reserve) => {
  cancelForm.value = {
    reserve_id: reserve.id,
    reason: '',
    custom_reason: ''
  }
  showCancelDialog.value = true
}

// Cancel reservation
const cancelReservation = async () => {
  if (!cancelForm.value.reason) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please select a cancellation reason',
      life: 3000
    })
    return
  }

  if (cancelForm.value.reason === 'custom' && !cancelForm.value.custom_reason) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please provide a custom reason',
      life: 3000
    })
    return
  }

  loading.value = true
  try {
    const cancelReason = cancelForm.value.reason === 'custom' 
      ? cancelForm.value.custom_reason 
      : defaultCancelReasons.find(r => r.value === cancelForm.value.reason)?.label

    await axios.post(`/api/product-reserves/${cancelForm.value.reserve_id}/cancel`, {
      cancel_reason: cancelReason
    })
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Reservation cancelled successfully',
      life: 3000
    })
    
    showCancelDialog.value = false
    loadReserves()
    
  } catch (error) {
    console.error('Failed to cancel reservation:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to cancel reservation',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Fulfill reservation
const fulfillReservation = (reserve) => {
  confirm.require({
    message: `Mark reservation ${reserve.reservation_code} as fulfilled?`,
    header: 'Confirm Fulfillment',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: async () => {
      loading.value = true
      try {
        await axios.post(`/api/product-reserves/${reserve.id}/fulfill`)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Reservation fulfilled successfully',
          life: 3000
        })
        loadReserves()
      } catch (error) {
        console.error('Failed to fulfill reservation:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to fulfill reservation',
          life: 3000
        })
      } finally {
        loading.value = false
      }
    }
  })
}

// Delete reservation
const deleteReservation = (reserve) => {
  confirm.require({
    message: `Are you sure you want to delete reservation ${reserve.reservation_code}?`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      loading.value = true
      try {
        await axios.delete(`/api/product-reserves/${reserve.id}`)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Reservation deleted successfully',
          life: 3000
        })
        loadReserves()
      } catch (error) {
        console.error('Failed to delete reservation:', error)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to delete reservation',
          life: 3000
        })
      } finally {
        loading.value = false
      }
    }
  })
}

// Pagination
const onPage = (event) => {
  lazyParams.value.first = event.first
  lazyParams.value.rows = event.rows
  lazyParams.value.page = Math.floor(event.first / event.rows) + 1
  loadReserves()
}

// Format date
const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  loadReserves()
  loadPharmacyProducts()
  loadPharmacyStockages()
})
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-gray-50 tw-to-gray-100 tw-p-4 sm:tw-p-6 lg:tw-p-8">
    <!-- Header Section -->
    <div class="tw-mb-8">
      <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-4">
        <div class="tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-16 tw-h-16 tw-bg-gradient-to-br tw-from-purple-500 tw-to-pink-600 tw-rounded-2xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
            <i class="pi pi-shopping-bag tw-text-3xl tw-text-white"></i>
          </div>
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Pharmacy Reservations</h1>
            <p class="tw-text-gray-600 tw-mt-1">Manage pharmacy product reservations</p>
          </div>
        </div>
        
        <div class="tw-flex tw-gap-3 tw-flex-wrap">
          <Chip :label="`${totalRecords} Total`" class="!tw-bg-purple-100 !tw-text-purple-800 tw-font-semibold" />
          <Chip 
            :label="`${reserves.filter(r => r.status === 'pending').length} Pending`" 
            class="!tw-bg-yellow-100 !tw-text-yellow-800 tw-font-semibold" 
          />
        </div>
      </div>
    </div>

    <!-- Main Card -->
    <Card class="tw-shadow-2xl tw-rounded-3xl tw-border-0 tw-overflow-hidden">
      <template #content>
        <!-- Toolbar -->
        <div class="tw-p-6 tw-bg-gradient-to-r tw-from-gray-50 tw-to-white tw-border-b tw-border-gray-200">
          <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4 tw-items-start lg:tw-items-center tw-justify-between">
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-3 tw-flex-1 tw-w-full">
              <span class="p-input-icon-left tw-flex-1">
                <i class="pi pi-search tw-text-gray-400" />
                <InputText 
                  v-model="searchQuery" 
                  placeholder="Search by code, product, or reserver..." 
                  class="tw-w-full tw-rounded-xl !tw-pl-10 !tw-py-3 tw-border-gray-300 focus:tw-ring-2 focus:tw-ring-purple-500"
                />
              </span>
              
              <Dropdown
                v-model="statusFilter"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Filter by Status"
                class="tw-w-full sm:tw-w-48 tw-rounded-xl"
              />
            </div>
            
            <div class="tw-flex tw-gap-3">
              <Button 
                icon="pi pi-refresh" 
                label="Refresh"
                class="p-button-outlined tw-rounded-xl"
                @click="loadReserves"
                :loading="loading"
              />
              <Button 
                icon="pi pi-plus" 
                label="New Reservation"
                class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-pink-600 tw-border-0 tw-rounded-xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all"
                @click="openCreateDialog"
              />
            </div>
          </div>
        </div>

        <!-- Data Table -->
        <DataTable 
          :value="filteredReserves" 
          :loading="loading"
          :paginator="true"
          :rows="lazyParams.rows"
          :totalRecords="totalRecords"
          :lazy="false"
          @page="onPage"
          class="tw-mt-0"
          responsiveLayout="scroll"
          :rowHover="true"
          stripedRows
          dataKey="id"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[10, 20, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} reservations"
        >
          <template #loading>
            <div class="tw-p-4">
              <Skeleton height="3rem" class="tw-mb-2" />
              <Skeleton height="3rem" class="tw-mb-2" />
              <Skeleton height="3rem" />
            </div>
          </template>

          <Column field="reservation_code" header="Code" sortable class="tw-w-32">
            <template #body="{ data }">
              <Badge :value="data.reservation_code" severity="info" class="tw-text-xs tw-font-mono" />
            </template>
          </Column>

          <Column field="pharmacy_product" header="Pharmacy Product" sortable class="tw-min-w-[250px]">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <span class="tw-font-semibold tw-text-gray-900">
                  {{ data.pharmacy_product?.name || 'N/A' }}
                </span>
                <span class="tw-text-xs tw-text-purple-600">
                  <i class="pi pi-shopping-bag tw-mr-1"></i>Pharmacy Product
                </span>
              </div>
            </template>
          </Column>

          <Column field="quantity" header="Quantity" sortable class="tw-w-28">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
                <i class="pi pi-box tw-text-purple-500"></i>
                <span class="tw-font-bold tw-text-gray-900">{{ data.quantity }}</span>
              </div>
            </template>
          </Column>

          <Column field="reserver" header="Reserved By" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-gradient-to-br tw-from-purple-400 tw-to-pink-500 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-sm">
                  {{ data.reserver?.name?.charAt(0) || 'U' }}
                </div>
                <span class="tw-text-gray-900">{{ data.reserver?.name || 'Unknown' }}</span>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" sortable class="tw-w-36">
            <template #body="{ data }">
              <Tag 
                :value="data.status" 
                :severity="getStatusSeverity(data.status)"
                class="tw-font-semibold tw-uppercase tw-text-xs"
              />
            </template>
          </Column>

          <Column field="reserved_at" header="Reserved At" sortable class="tw-w-48">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <span class="tw-text-sm tw-text-gray-900">{{ formatDate(data.reserved_at) }}</span>
                <span v-if="data.expires_at" class="tw-text-xs tw-text-orange-600">
                  <i class="pi pi-clock tw-mr-1"></i>Expires: {{ formatDate(data.expires_at) }}
                </span>
              </div>
            </template>
          </Column>

          <Column header="Actions" class="tw-w-48">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-1 tw-items-center tw-justify-center">
                <Button 
                  icon="pi pi-eye" 
                  class="p-button-rounded p-button-text p-button-sm !tw-text-blue-600 hover:!tw-bg-blue-50"
                  v-tooltip.top="'View'"
                  @click="openViewDialog(data)"
                />
                <Button 
                  v-if="data.status === 'pending'"
                  icon="pi pi-pencil" 
                  class="p-button-rounded p-button-text p-button-sm !tw-text-purple-600 hover:!tw-bg-purple-50"
                  v-tooltip.top="'Edit'"
                  @click="openEditDialog(data)"
                />
                <Button 
                  v-if="data.status === 'pending'"
                  icon="pi pi-check" 
                  class="p-button-rounded p-button-text p-button-sm !tw-text-green-600 hover:!tw-bg-green-50"
                  v-tooltip.top="'Fulfill'"
                  @click="fulfillReservation(data)"
                />
                <Button 
                  v-if="data.status !== 'fulfilled'"
                  icon="pi pi-times" 
                  class="p-button-rounded p-button-text p-button-sm !tw-text-orange-600 hover:!tw-bg-orange-50"
                  v-tooltip.top="'Cancel'"
                  @click="openCancelDialog(data)"
                />
                <Button 
                  icon="pi pi-trash" 
                  class="p-button-rounded p-button-text p-button-sm !tw-text-red-600 hover:!tw-bg-red-50"
                  v-tooltip.top="'Delete'"
                  @click="deleteReservation(data)"
                />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="tw-py-16 tw-text-center">
              <div class="tw-flex tw-justify-center tw-mb-6">
                <div class="tw-w-24 tw-h-24 tw-bg-gray-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-shopping-bag tw-text-6xl tw-text-gray-300"></i>
                </div>
              </div>
              <h3 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-2">No Pharmacy Reservations Found</h3>
              <p class="tw-text-gray-600 tw-mb-6">Start by creating your first pharmacy product reservation</p>
              <Button 
                icon="pi pi-plus" 
                label="Create Reservation" 
                class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-pink-600 tw-border-0 tw-rounded-xl"
                @click="openCreateDialog"
              />
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <!-- Create/Edit Dialog -->
    <Dialog 
      v-model:visible="showDialog" 
      :header="dialogMode === 'create' ? 'New Pharmacy Reservation' : dialogMode === 'edit' ? 'Edit Pharmacy Reservation' : 'View Pharmacy Reservation'"
      :modal="true"
      :closable="true"
      class="tw-w-full tw-max-w-2xl"
    >
      <div class="tw-space-y-6 tw-p-2">
        <!-- Pharmacy Product Selection -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-shopping-bag tw-mr-2 tw-text-purple-600"></i>Pharmacy Product *
          </label>
          <Dropdown 
            v-model="form.pharmacy_product_id"
            :options="pharmacyProducts"
            optionLabel="name"
            optionValue="id"
            placeholder="Select a pharmacy product"
            filter
            :disabled="dialogMode === 'view'"
            class="tw-w-full"
          >
            <template #option="{ option }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-purple-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-shopping-bag tw-text-purple-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold">{{ option.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500">Stock: {{ option.current_stock || 0 }}</div>
                </div>
              </div>
            </template>
          </Dropdown>
        </div>

        <!-- Pharmacy Storage Selection -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-home tw-mr-2 tw-text-green-600"></i>Pharmacy Storage Location
          </label>
          <Dropdown 
            v-model="form.pharmacy_stockage_id"
            :options="pharmacyStockages"
            optionLabel="name"
            optionValue="id"
            placeholder="Select a pharmacy storage location (optional)"
            filter
            :disabled="dialogMode === 'view'"
            class="tw-w-full"
          >
            <template #option="{ option }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-green-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-home tw-text-green-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold">{{ option.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ option.location || 'No specific location' }}</div>
                </div>
              </div>
            </template>
          </Dropdown>
        </div>

        <!-- Quantity -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-hashtag tw-mr-2 tw-text-green-600"></i>Quantity *
          </label>
          <InputNumber 
            v-model="form.quantity"
            :min="1"
            :disabled="dialogMode === 'view'"
            class="tw-w-full"
            showButtons
            buttonLayout="horizontal"
            incrementButtonIcon="pi pi-plus"
            decrementButtonIcon="pi pi-minus"
          />
        </div>

        <!-- Expiration Date -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-calendar tw-mr-2 tw-text-red-600"></i>Reservation Expiration Date
          </label>
          <Calendar 
            v-model="form.expires_at"
            :showTime="true"
            :minDate="new Date()"
            dateFormat="yy-mm-dd"
            :disabled="dialogMode === 'view'"
            placeholder="Select expiration date (optional)"
            class="tw-w-full"
          />
          <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-italic">
            <i class="pi pi-info-circle tw-mr-1"></i>
            If set, reservation will auto-cancel after this date/time
          </p>
        </div>

        <!-- Notes -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-comment tw-mr-2 tw-text-blue-600"></i>Reservation Notes
          </label>
          <Textarea 
            v-model="form.reservation_notes"
            rows="4"
            :disabled="dialogMode === 'view'"
            placeholder="Add any notes about this reservation..."
            class="tw-w-full tw-rounded-xl"
          />
        </div>

        <!-- View Mode Additional Info -->
        <div v-if="dialogMode === 'view' && selectedReserve" class="tw-space-y-4 tw-bg-gray-50 tw-p-4 tw-rounded-xl">
          <div class="tw-flex tw-justify-between">
            <span class="tw-text-gray-600">Reservation Code:</span>
            <Badge :value="selectedReserve.reservation_code" severity="info" />
          </div>
          <div class="tw-flex tw-justify-between">
            <span class="tw-text-gray-600">Status:</span>
            <Tag :value="selectedReserve.status" :severity="getStatusSeverity(selectedReserve.status)" />
          </div>
          <div class="tw-flex tw-justify-between">
            <span class="tw-text-gray-600">Reserved By:</span>
            <span class="tw-font-semibold">{{ selectedReserve.reserver?.name || 'N/A' }}</span>
          </div>
          <div class="tw-flex tw-justify-between">
            <span class="tw-text-gray-600">Reserved At:</span>
            <span class="tw-font-semibold">{{ formatDate(selectedReserve.reserved_at) }}</span>
          </div>
          <div v-if="selectedReserve.cancel_reason" class="tw-flex tw-justify-between">
            <span class="tw-text-gray-600">Cancel Reason:</span>
            <span class="tw-font-semibold tw-text-red-600">{{ selectedReserve.cancel_reason }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-3 tw-justify-end">
          <Button 
            label="Cancel" 
            icon="pi pi-times"
            class="p-button-text tw-rounded-xl"
            @click="showDialog = false"
          />
          <Button 
            v-if="dialogMode !== 'view'"
            :label="dialogMode === 'create' ? 'Create Reservation' : 'Update Reservation'"
            icon="pi pi-check"
            class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-pink-600 tw-border-0 tw-rounded-xl"
            @click="saveReserve"
            :loading="loading"
          />
        </div>
      </template>
    </Dialog>

    <!-- Cancel Dialog -->
    <Dialog 
      v-model:visible="showCancelDialog" 
      header="Cancel Reservation"
      :modal="true"
      :closable="true"
      class="tw-w-full tw-max-w-lg"
    >
      <div class="tw-space-y-6 tw-p-2">
        <div class="tw-bg-orange-50 tw-border tw-border-orange-200 tw-rounded-xl tw-p-4">
          <div class="tw-flex tw-gap-3">
            <i class="pi pi-exclamation-triangle tw-text-2xl tw-text-orange-600"></i>
            <div>
              <h4 class="tw-font-semibold tw-text-orange-900 tw-mb-1">Cancel Reservation</h4>
              <p class="tw-text-sm tw-text-orange-700">Please provide a reason for cancelling this pharmacy reservation.</p>
            </div>
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Cancellation Reason *
          </label>
          <Dropdown 
            v-model="cancelForm.reason"
            :options="defaultCancelReasons"
            optionLabel="label"
            optionValue="value"
            placeholder="Select a reason"
            class="tw-w-full"
          />
        </div>

        <div v-if="cancelForm.reason === 'custom'">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            Custom Reason *
          </label>
          <Textarea 
            v-model="cancelForm.custom_reason"
            rows="3"
            placeholder="Provide detailed reason for cancellation..."
            class="tw-w-full tw-rounded-xl"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-3 tw-justify-end">
          <Button 
            label="Back" 
            icon="pi pi-arrow-left"
            class="p-button-text tw-rounded-xl"
            @click="showCancelDialog = false"
          />
          <Button 
            label="Cancel Reservation"
            icon="pi pi-times-circle"
            class="p-button-danger tw-rounded-xl"
            @click="cancelReservation"
            :loading="loading"
          />
        </div>
      </template>
    </Dialog>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
@reference "../../../../../../resources/css/app.css";

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply tw-bg-gradient-to-r tw-from-gray-100 tw-to-gray-50 tw-text-gray-800 tw-font-bold tw-text-sm tw-border-b-2 tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply tw-transition-all tw-duration-200;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  @apply tw-bg-purple-50;
}

:deep(.p-paginator) {
  @apply tw-bg-gradient-to-r tw-from-gray-50 tw-to-white tw-border-t tw-border-gray-200 tw-py-4;
}

:deep(.p-dialog .p-dialog-header) {
  @apply tw-bg-gradient-to-r tw-from-purple-600 tw-to-pink-600 tw-text-white tw-rounded-t-2xl;
}

:deep(.p-dialog .p-dialog-content) {
  @apply tw-rounded-b-2xl;
}
</style>
