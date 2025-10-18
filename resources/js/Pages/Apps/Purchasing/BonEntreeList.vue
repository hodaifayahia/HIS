<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
      <div>
        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Stock Entries</h1>
        <p class="tw-text-gray-600 tw-mt-1">Manage stock entries from received products</p>
      </div>
      <div class="tw-flex tw-gap-3">
        <Button 
          @click="validateSelectedEntries"
          :disabled="!selectedDraftEntries.length || validatingIds.length > 0"
          icon="pi pi-check-circle"
          :label="`Validate Selected (${selectedDraftEntries.length})`"
          class="tw-bg-green-600 hover:tw-bg-green-700"
        />
        <Button 
          @click="refreshData"
          icon="pi pi-refresh"
          label="Refresh"
          outlined
        />
        <router-link to="/purchasing/bon-entrees/create">
          <Button 
            icon="pi pi-plus"
            label="Create Stock Entry"
            class="tw-bg-blue-600 hover:tw-bg-blue-700"
          />
        </router-link>
      </div>
    </div>

    <!-- Filters -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4 tw-mb-6">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Status</label>
          <Dropdown
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Statuses"
            class="tw-w-full"
            showClear
            @change="applyFilters"
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Date Range</label>
          <Calendar
            v-model="filters.dateRange"
            selectionMode="range"
            :manualInput="false"
            placeholder="Select date range"
            class="tw-w-full"
            @date-select="applyFilters"
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Supplier</label>
          <InputText
            v-model="filters.supplier"
            placeholder="Search by supplier..."
            class="tw-w-full"
            @input="applyFilters"
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Search</label>
          <InputText
            v-model="filters.search"
            placeholder="Search by code..."
            class="tw-w-full"
            @input="applyFilters"
          />
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm">
      <DataTable
        v-model="selectedItems"
        :value="bonEntrees"
        :loading="loading"
        :paginator="true"
        :rows="20"
        :totalRecords="totalRecords"
        :lazy="true"
        @page="onPage"
        @sort="onSort"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 20, 50]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        responsiveLayout="scroll"
        :scrollable="true"
        scrollHeight="500px"
        dataKey="id"
        class="p-datatable-sm"
      >
        <template #loading>
          Loading bon entrees data. Please wait...
        </template>

        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
        
        <Column field="bon_entree_code" header="Code" sortable>
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <span class="tw-font-medium tw-text-blue-600">
                {{ slotProps.data.bon_entree_code }}
              </span>
              <Button 
                @click="viewDetails(slotProps.data)"
                icon="pi pi-eye"
                class="p-button-text p-button-sm"
                v-tooltip="'View Details'"
              />
            </div>
          </template>
        </Column>

        <Column field="status" header="Status" sortable>
          <template #body="slotProps">
            <Tag 
              :value="getStatusLabel(slotProps.data.status)" 
              :severity="getStatusSeverity(slotProps.data.status)"
            />
          </template>
        </Column>

        <Column field="bon_reception.bon_reception_code" header="Bon Reception">
          <template #body="slotProps">
            <span v-if="slotProps.data.bon_reception">
              {{ slotProps.data.bon_reception.bon_reception_code }}
            </span>
            <span v-else class="tw-text-gray-400">N/A</span>
          </template>
        </Column>

        <Column field="fournisseur.company_name" header="Supplier">
          <template #body="slotProps">
            <div v-if="slotProps.data.fournisseur">
              <div class="tw-font-medium">{{ slotProps.data.fournisseur.company_name }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.fournisseur.contact_person }}</div>
            </div>
            <span v-else class="tw-text-gray-400">N/A</span>
          </template>
        </Column>

        <Column field="total_amount" header="Total Amount" sortable>
          <template #body="slotProps">
            <span class="tw-font-semibold">
              {{ formatCurrency(slotProps.data.total_amount) }}
            </span>
          </template>
        </Column>

        <Column field="items_count" header="Items" sortable>
          <template #body="slotProps">
            <Badge :value="slotProps.data.items_count || 0" />
          </template>
        </Column>

        <Column field="creator.name" header="Created By">
          <template #body="slotProps">
            <div v-if="slotProps.data.creator">
              <div class="tw-text-sm">{{ slotProps.data.creator.name }}</div>
            </div>
            <span v-else class="tw-text-gray-400">N/A</span>
          </template>
        </Column>

        <Column field="created_at" header="Created Date" sortable>
          <template #body="slotProps">
            <div class="tw-text-sm">
              <div>{{ formatDate(slotProps.data.created_at) }}</div>
              <div class="tw-text-gray-500">{{ formatTime(slotProps.data.created_at) }}</div>
            </div>
          </template>
        </Column>

        <Column header="Actions" style="min-width: 250px;">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-1 tw-flex-wrap tw-items-center">
              <!-- View Button -->
              <Button 
                @click="viewDetails(slotProps.data)"
                icon="pi pi-eye"
                class="p-button-info p-button-sm"
                v-tooltip="'View Details'"
              />
              
              <!-- Edit Button - Only for draft status -->
              <Button 
                v-if="slotProps.data.status.toLowerCase() === 'draft'"
                @click="editBonEntree(slotProps.data)"
                icon="pi pi-pencil"
                class="p-button-warning p-button-sm"
                v-tooltip="'Edit'"
              />
              
              <!-- Validate Button - Only for draft status -->
              <Button 
                v-if="slotProps.data.status.toLowerCase() === 'draft'"
                @click="validateBonEntree(slotProps.data)"
                icon="pi pi-check"
                :loading="validatingIds.includes(slotProps.data.id)"
                :disabled="validatingIds.includes(slotProps.data.id)"
                label="Validate"
                class="p-button-success p-button-sm"
                v-tooltip="'Validate & Prepare for Stock Transfer'"
              />
              
              <!-- Transfer to Stock Button - Only for validated status -->
              <Button 
                v-if="slotProps.data.status.toLowerCase() === 'validated'"
                @click="transferToStock(slotProps.data)"
                icon="pi pi-send"
                :loading="transferringIds.includes(slotProps.data.id)"
                :disabled="transferringIds.includes(slotProps.data.id)"
                label="Transfer"
                class="p-button-primary p-button-sm"
                v-tooltip="'Transfer Items to Service Stock'"
              />
              
              <!-- Status Badge for Transferred Items -->
              <Tag 
                v-if="slotProps.data.status.toLowerCase() === 'transferred'"
                value="Completed"
                severity="success"
                class="tw-text-xs"
              />
              
              <!-- Delete Button - Only for draft status -->
              <Button 
                v-if="slotProps.data.status.toLowerCase() === 'draft'"
                @click="deleteBonEntree(slotProps.data)"
                icon="pi pi-trash"
                class="p-button-danger p-button-sm"
                v-tooltip="'Delete'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Details Dialog -->
    <Dialog 
      :visible="showDetailsDialog"
      @update:visible="showDetailsDialog = $event" 
      :header="`Bon Entree Details - ${selectedBonEntree?.bon_entree_code}`"
      :style="{ width: '80vw' }"
      :maximizable="true"
      :modal="true"
    >
      <div v-if="selectedBonEntree" class="tw-space-y-6">
        <!-- Basic Information -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-mb-3">Basic Information</h3>
            <div class="tw-space-y-2">
              <div><strong>Code:</strong> {{ selectedBonEntree.bon_entree_code }}</div>
              <div><strong>Status:</strong> 
                <Tag 
                  :value="getStatusLabel(selectedBonEntree.status)" 
                  :severity="getStatusSeverity(selectedBonEntree.status)"
                />
              </div>
              <div><strong>Total Amount:</strong> {{ formatCurrency(selectedBonEntree.total_amount) }}</div>
              <div><strong>Created:</strong> {{ formatDateTime(selectedBonEntree.created_at) }}</div>
            </div>
          </div>
          
          <div v-if="selectedBonEntree.fournisseur">
            <h3 class="tw-text-lg tw-font-semibold tw-mb-3">Supplier Information</h3>
            <div class="tw-space-y-2">
              <div><strong>Company:</strong> {{ selectedBonEntree.fournisseur.company_name }}</div>
              <div><strong>Contact:</strong> {{ selectedBonEntree.fournisseur.contact_person }}</div>
              <div><strong>Phone:</strong> {{ selectedBonEntree.fournisseur.phone }}</div>
              <div><strong>Email:</strong> {{ selectedBonEntree.fournisseur.email }}</div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedBonEntree.notes">
          <h3 class="tw-text-lg tw-font-semibold tw-mb-3">Notes</h3>
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
            {{ selectedBonEntree.notes }}
          </div>
        </div>

        <!-- Items -->
        <div v-if="selectedBonEntree.items && selectedBonEntree.items.length > 0">
          <h3 class="tw-text-lg tw-font-semibold tw-mb-3">Items</h3>
          <DataTable :value="selectedBonEntree.items" class="p-datatable-sm">
            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div>
                  <div class="tw-font-medium">{{ slotProps.data.product?.name }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.product_code }}</div>
                </div>
              </template>
            </Column>
            <Column field="quantity" header="Quantity"></Column>
            <Column field="purchase_price" header="Purchase Price">
              <template #body="slotProps">
                {{ formatCurrency(slotProps.data.purchase_price) }}
              </template>
            </Column>
            <Column field="total_amount" header="Total">
              <template #body="slotProps">
                <strong>{{ formatCurrency(slotProps.data.total_amount) }}</strong>
              </template>
            </Column>
            <Column field="batch_number" header="Batch"></Column>
            <Column field="expiry_date" header="Expiry">
              <template #body="slotProps">
                {{ slotProps.data.expiry_date ? formatDate(slotProps.data.expiry_date) : 'N/A' }}
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <template #footer>
        <Button 
          @click="showDetailsDialog = false"
          label="Close"
          class="p-button-secondary"
        />
      </template>
    </Dialog>

    <!-- Confirm Dialog -->
    <ConfirmDialog />
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
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Calendar from 'primevue/calendar'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import ConfirmDialog from 'primevue/confirmdialog'
import axios from 'axios'

// Composables
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// Reactive state
const loading = ref(false)
const validatingIds = ref([])
const transferringIds = ref([])
const bonEntrees = ref([])
const selectedItems = ref([])
const totalRecords = ref(0)
const showDetailsDialog = ref(false)
const selectedBonEntree = ref(null)

const lazyParams = ref({
  first: 0,
  rows: 20,
  sortField: 'created_at',
  sortOrder: -1
})

const filters = reactive({
  status: null,
  dateRange: null,
  supplier: '',
  search: ''
})

// Constants
const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Validated', value: 'validated' },
  { label: 'Transferred', value: 'transferred' },
  { label: 'Cancelled', value: 'cancelled' }
]

// Computed properties
const selectedDraftEntries = computed(() => {
  if (!selectedItems.value || !Array.isArray(selectedItems.value)) return []
  return selectedItems.value.filter(item => item && item.status && item.status.toLowerCase() === 'draft')
})

// Methods
const fetchBonEntrees = async () => {
  try {
    loading.value = true
    
    const params = {
      page: Math.floor(lazyParams.value.first / lazyParams.value.rows) + 1,
      per_page: lazyParams.value.rows,
      sort_by: lazyParams.value.sortField || 'created_at',
      sort_direction: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
      ...filters
    }

    // Clean up empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null || params[key] === undefined) {
        delete params[key]
      }
    })

    const response = await axios.get('/api/bon-entrees', { params })
    
    if (response.data.status === 'success') {
      bonEntrees.value = response.data.data.data || response.data.data
      totalRecords.value = response.data.data.total || response.data.data.length
    }
  } catch (err) {
    console.error('Error fetching bon entrees:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load bon entrees',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const refreshData = () => {
  fetchBonEntrees()
}

const onPage = (event) => {
  lazyParams.value = event
  fetchBonEntrees()
}

const onSort = (event) => {
  lazyParams.value = event
  fetchBonEntrees()
}

const applyFilters = () => {
  lazyParams.value.first = 0 // Reset to first page
  fetchBonEntrees()
}

const viewDetails = async (bonEntree) => {
  try {
    const response = await axios.get(`/api/bon-entrees/${bonEntree.id}`)
    if (response.data.status === 'success') {
      selectedBonEntree.value = response.data.data
      showDetailsDialog.value = true
    }
  } catch (err) {
    console.error('Error fetching bon entree details:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load bon entree details',
      life: 3000
    })
  }
}

const editBonEntree = (bonEntree) => {
  router.push(`/purchasing/bon-entrees/${bonEntree.id}/edit`)
}

const validateBonEntree = (bonEntree) => {
  confirm.require({
    message: `Are you sure you want to validate "${bonEntree.bon_entree_code}"? This will change its status to validated and prepare it for stock transfer.`,
    header: 'Validate Bon Entree',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: async () => {
      try {
        validatingIds.value.push(bonEntree.id)
        
        const response = await axios.patch(`/api/bon-entrees/${bonEntree.id}/validate`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Validation Successful',
            detail: `Bon entree "${bonEntree.bon_entree_code}" has been validated and is ready for stock transfer`,
            life: 4000
          })
          fetchBonEntrees()
        }
      } catch (err) {
        console.error('Error validating bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Validation Failed',
          detail: err.response?.data?.message || 'Failed to validate bon entree',
          life: 3000
        })
      } finally {
        validatingIds.value = validatingIds.value.filter(id => id !== bonEntree.id)
      }
    }
  })
}

const validateSelectedEntries = () => {
  const draftEntries = selectedDraftEntries.value
  if (!draftEntries.length) {
    toast.add({
      severity: 'warn',
      summary: 'No Entries Selected',
      detail: 'Please select draft entries to validate',
      life: 3000
    })
    return
  }

  confirm.require({
    message: `Are you sure you want to validate ${draftEntries.length} selected draft entries? They will be prepared for stock transfer.`,
    header: 'Validate Selected Entries',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: async () => {
      let successCount = 0
      let errorCount = 0

      for (const entry of draftEntries) {
        try {
          validatingIds.value.push(entry.id)
          const response = await axios.patch(`/api/bon-entrees/${entry.id}/validate`)
          
          if (response.data.status === 'success') {
            successCount++
          } else {
            errorCount++
          }
        } catch (err) {
          console.error(`Error validating ${entry.bon_entree_code}:`, err)
          errorCount++
        } finally {
          validatingIds.value = validatingIds.value.filter(id => id !== entry.id)
        }
      }

      // Show summary toast
      if (successCount > 0) {
        toast.add({
          severity: 'success',
          summary: 'Bulk Validation Complete',
          detail: `${successCount} entries validated successfully${errorCount > 0 ? `, ${errorCount} failed` : ''}`,
          life: 4000
        })
      }

      if (errorCount > 0 && successCount === 0) {
        toast.add({
          severity: 'error',
          summary: 'Bulk Validation Failed',
          detail: `${errorCount} entries failed to validate`,
          life: 4000
        })
      }

      // Clear selection and refresh data
      selectedItems.value = []
      fetchBonEntrees()
    }
  })
}

const transferToStock = (bonEntree) => {
  confirm.require({
    message: `Are you sure you want to transfer "${bonEntree.bon_entree_code}" to service stock? This will add all items to the service inventory and cannot be undone.`,
    header: 'Transfer to Stock',
    icon: 'pi pi-send',
    acceptClass: 'p-button-primary',
    accept: async () => {
      try {
        transferringIds.value.push(bonEntree.id)
        
        const response = await axios.patch(`/api/bon-entrees/${bonEntree.id}/transfer`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Transfer Successful',
            detail: `Items from "${bonEntree.bon_entree_code}" have been added to service stock inventory`,
            life: 4000
          })
          fetchBonEntrees()
        }
      } catch (err) {
        console.error('Error transferring bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Transfer Failed',
          detail: err.response?.data?.message || 'Failed to transfer bon entree to stock',
          life: 3000
        })
      } finally {
        transferringIds.value = transferringIds.value.filter(id => id !== bonEntree.id)
      }
    }
  })
}

const deleteBonEntree = (bonEntree) => {
  confirm.require({
    message: `Are you sure you want to delete bon entree ${bonEntree.bon_entree_code}?`,
    header: 'Delete Bon Entree',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/bon-entrees/${bonEntree.id}`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon entree deleted successfully',
            life: 3000
          })
          fetchBonEntrees()
        }
      } catch (err) {
        console.error('Error deleting bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to delete bon entree',
          life: 3000
        })
      }
    }
  })
}

// Utility functions
const getStatusLabel = (status) => {
  const statusMap = {
    draft: 'Draft',
    validated: 'Validated',
    transferred: 'Transferred',
    cancelled: 'Cancelled'
  }
  return statusMap[status?.toLowerCase()] || status
}

const getStatusSeverity = (status) => {
  const severityMap = {
    draft: 'info',
    validated: 'success',
    transferred: 'warning',
    cancelled: 'danger'
  }
  return severityMap[status?.toLowerCase()] || 'info'
}

const formatCurrency = (amount) => {
  if (!amount) return 'DZD 0.00'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount)
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
  if (!date) return 'N/A'
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
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
  fetchBonEntrees()
})
</script>

<style scoped>
:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  padding: 0.75rem;
  background-color: #f8f9fa;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}

:deep(.p-dialog) {
  border-radius: 8px;
}

:deep(.p-tag) {
  border-radius: 4px;
}
</style>
