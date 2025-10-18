<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">Purchase Order Approvals</h1>
          <p class="tw-text-gray-600 tw-mt-1">Review and manage purchase orders requiring your approval</p>
        </div>
        <Button 
          @click="refreshApprovals"
          :loading="loading"
          icon="pi pi-refresh"
          label="Refresh"
          class="p-button-secondary"
        />
      </div>
    </div>

    <!-- Statistics -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-6">
      <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-yellow-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <div class="tw-text-sm tw-font-medium tw-text-gray-600">Pending</div>
            <div class="tw-text-2xl tw-font-bold tw-text-yellow-600">{{ stats.pending || 0 }}</div>
          </div>
          <i class="pi pi-clock tw-text-3xl tw-text-yellow-500"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-green-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <div class="tw-text-sm tw-font-medium tw-text-gray-600">Approved Today</div>
            <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ stats.approved_today || 0 }}</div>
          </div>
          <i class="pi pi-check-circle tw-text-3xl tw-text-green-500"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-red-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <div class="tw-text-sm tw-font-medium tw-text-gray-600">Rejected</div>
            <div class="tw-text-2xl tw-font-bold tw-text-red-600">{{ stats.rejected || 0 }}</div>
          </div>
          <i class="pi pi-times-circle tw-text-3xl tw-text-red-500"></i>
        </div>
      </div>
      
      <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-blue-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <div class="tw-text-sm tw-font-medium tw-text-gray-600">Total This Month</div>
            <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ stats.total_month || 0 }}</div>
          </div>
          <i class="pi pi-chart-bar tw-text-3xl tw-text-blue-500"></i>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
        <div>
          <label class="tw-text-sm tw-font-medium tw-text-gray-700">Status:</label>
          <Dropdown 
            v-model="filters.status" 
            :options="statusOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="All Status"
            class="tw-w-full tw-mt-1"
          />
        </div>
        
        <div>
          <label class="tw-text-sm tw-font-medium tw-text-gray-700">Amount Range:</label>
          <Dropdown 
            v-model="filters.amountRange" 
            :options="amountRangeOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="All Amounts"
            class="tw-w-full tw-mt-1"
          />
        </div>
        
        <div>
          <label class="tw-text-sm tw-font-medium tw-text-gray-700">Search:</label>
          <InputText 
            v-model="filters.search" 
            placeholder="Search by code or supplier..."
            class="tw-w-full tw-mt-1"
          />
        </div>
      
        <div class="tw-flex tw-items-end tw-gap-2">
          <Button 
            @click="applyFilters" 
            icon="pi pi-filter" 
            label="Apply"
            class="p-button-primary"
          />
          <Button 
            @click="resetFilters" 
            icon="pi pi-filter-slash" 
            label="Reset"
            class="p-button-secondary"
          />
        </div>
      </div>
    </div>

    <!-- Approvals List -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-overflow-hidden">
      <DataTable 
        :value="approvals" 
        :loading="loading"
        stripedRows
        class="tw-w-full"
        :paginator="true"
        :rows="15"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 15, 25, 50]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
      >
        <Column field="bonCommend.bonCommendCode" header="Order Code" sortable style="width: 120px">
          <template #body="slotProps">
            <div class="tw-font-mono tw-text-sm">
              {{ slotProps.data.bonCommend.bonCommendCode || `BC-${slotProps.data.bonCommend.id}` }}
            </div>
          </template>
        </Column>

        <Column header="Supplier" sortable>
          <template #body="slotProps">
            <div>
              <div class="tw-font-medium">{{ slotProps.data.bonCommend.fournisseur?.company_name || 'No Supplier' }}</div>
              <div class="tw-text-xs tw-text-gray-500">
                {{ slotProps.data.bonCommend.items?.length || 0 }} items
              </div>
            </div>
          </template>
        </Column>

        <Column field="total_amount" header="Total Amount" sortable style="width: 130px">
          <template #body="slotProps">
            <div class="tw-font-semibold tw-text-right">
              {{ formatCurrency(slotProps.data.total_amount) }}
            </div>
          </template>
        </Column>

        <Column field="requester.name" header="Requested By" sortable style="width: 120px">
          <template #body="slotProps">
            <div class="tw-text-sm">
              {{ slotProps.data.requester?.name || 'Unknown' }}
            </div>
          </template>
        </Column>

        <Column field="requested_at" header="Request Date" sortable style="width: 120px">
          <template #body="slotProps">
            <div class="tw-text-sm">
              {{ formatDate(slotProps.data.requested_at) }}
            </div>
          </template>
        </Column>

        <Column field="status" header="Status" sortable style="width: 100px">
          <template #body="slotProps">
            <Tag 
              :value="getStatusLabel(slotProps.data.status).label" 
              :severity="getStatusLabel(slotProps.data.status).severity"
            />
          </template>
        </Column>

        <Column header="Actions" style="width: 180px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button 
                @click="viewApproval(slotProps.data)"
                icon="pi pi-eye"
                size="small"
                class="p-button-info p-button-text"
                v-tooltip.top="'View Details'"
              />
              <Button 
                v-if="slotProps.data.status === 'pending'"
                @click="openApproveDialog(slotProps.data)"
                icon="pi pi-check"
                size="small"
                class="p-button-success p-button-text"
                v-tooltip.top="'Approve'"
              />
              <Button 
                v-if="slotProps.data.status === 'pending'"
                @click="openRejectDialog(slotProps.data)"
                icon="pi pi-times"
                size="small"
                class="p-button-danger p-button-text"
                v-tooltip.top="'Reject'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Approve Dialog -->
    <Dialog 
      v-model:visible="approveDialogVisible" 
      header="Approve Purchase Order"
      :modal="true"
      class="tw-w-full tw-max-w-4xl"
      :maximizable="true"
    >
      <div v-if="selectedApproval" class="tw-space-y-6">
        <!-- Order Summary -->
        <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
            <div>
              <div class="tw-text-sm tw-font-medium tw-text-gray-600">Order Code</div>
              <div class="tw-font-semibold">
                {{ selectedApproval.bonCommend.bonCommendCode || `BC-${selectedApproval.bonCommend.id}` }}
              </div>
            </div>
            <div>
              <div class="tw-text-sm tw-font-medium tw-text-gray-600">Supplier</div>
              <div class="tw-font-semibold">
                {{ selectedApproval.bonCommend.fournisseur?.company_name || 'No Supplier' }}
              </div>
            </div>
            <div>
              <div class="tw-text-sm tw-font-medium tw-text-gray-600">Total Amount</div>
              <div class="tw-font-semibold tw-text-lg tw-text-blue-600">
                {{ formatCurrency(selectedApproval.total_amount) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Items List with Quantity Modification -->
        <div>
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Order Items</h4>
          <div class="tw-space-y-3">
            <div 
              v-for="(item, index) in modifiableItems" 
              :key="item.id"
              class="tw-flex tw-items-center tw-gap-4 tw-p-4 tw-border tw-rounded-lg tw-bg-white"
            >
              <div class="tw-flex-1">
                <div class="tw-font-medium">{{ item.product?.name || 'Unknown Product' }}</div>
                <div class="tw-text-sm tw-text-gray-600">
                  Unit: {{ item.unit }} | Price: {{ formatCurrency(item.price) }}
                </div>
              </div>
              
              <div class="tw-text-center">
                <div class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Original Qty</div>
                <div class="tw-font-semibold">{{ item.original_quantity || item.quantity_desired }}</div>
              </div>
              
              <div class="tw-text-center">
                <div class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Approved Qty</div>
                <InputNumber
                  v-model="item.approved_quantity"
                  :min="0"
                  :max="item.quantity_desired * 2"
                  class="tw-w-20"
                />
              </div>
              
              <div class="tw-text-center">
                <div class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Subtotal</div>
                <div class="tw-font-semibold">
                  {{ formatCurrency((item.approved_quantity || item.quantity_desired) * item.price) }}
                </div>
              </div>
            </div>
          </div>
          
          <div class="tw-mt-4 tw-p-3 tw-bg-blue-50 tw-rounded-lg">
            <div class="tw-flex tw-justify-between tw-items-center">
              <span class="tw-font-medium tw-text-blue-900">Approved Total:</span>
              <span class="tw-text-xl tw-font-bold tw-text-blue-900">{{ formatCurrency(approvedTotal) }}</span>
            </div>
          </div>
        </div>

        <!-- Approval Notes -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Approval Notes (Optional)
          </label>
          <Textarea 
            v-model="approvalForm.notes"
            placeholder="Add any comments or conditions for this approval..."
            rows="3"
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button 
          @click="approveDialogVisible = false" 
          label="Cancel" 
          icon="pi pi-times" 
          class="p-button-text"
        />
        <Button 
          @click="processApproval('approved')" 
          :loading="processing"
          label="Approve Order" 
          icon="pi pi-check" 
          class="p-button-success"
        />
      </template>
    </Dialog>

    <!-- Reject Dialog -->
    <Dialog 
      v-model:visible="rejectDialogVisible" 
      header="Reject Purchase Order"
      :modal="true"
      class="tw-w-full tw-max-w-2xl"
    >
      <div v-if="selectedApproval" class="tw-space-y-4">
        <div class="tw-bg-red-50 tw-p-4 tw-rounded-lg tw-border tw-border-red-200">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <i class="pi pi-exclamation-triangle tw-text-red-600"></i>
            <span class="tw-font-medium tw-text-red-800">
              You are about to reject this purchase order
            </span>
          </div>
          <div class="tw-text-sm tw-text-red-700">
            Order: {{ selectedApproval.bonCommend.bonCommendCode || `BC-${selectedApproval.bonCommend.id}` }} 
            ({{ formatCurrency(selectedApproval.total_amount) }})
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Reason for Rejection <span class="tw-text-red-500">*</span>
          </label>
          <Textarea 
            v-model="approvalForm.notes"
            placeholder="Please provide a reason for rejecting this purchase order..."
            rows="4"
            class="tw-w-full"
            :class="{'p-invalid': !approvalForm.notes && showValidationErrors}"
          />
          <small v-if="!approvalForm.notes && showValidationErrors" class="p-error">
            Rejection reason is required
          </small>
        </div>
      </div>

      <template #footer>
        <Button 
          @click="rejectDialogVisible = false" 
          label="Cancel" 
          icon="pi pi-times" 
          class="p-button-text"
        />
        <Button 
          @click="processApproval('rejected')" 
          :loading="processing"
          label="Reject Order" 
          icon="pi pi-times-circle" 
          class="p-button-danger"
        />
      </template>
    </Dialog>

    <!-- View Details Dialog -->
    <Dialog 
      v-model:visible="viewDialogVisible" 
      header="Purchase Order Details"
      :modal="true"
      class="tw-w-full tw-max-w-5xl"
      :maximizable="true"
    >
      <div v-if="selectedApproval" class="tw-space-y-6">
        <!-- Order Information -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
            <h4 class="tw-font-semibold tw-text-gray-900 tw-mb-3">Order Information</h4>
            <dl class="tw-space-y-2">
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Order Code</dt>
                <dd class="tw-font-medium">{{ selectedApproval.bonCommend.bonCommendCode || `BC-${selectedApproval.bonCommend.id}` }}</dd>
              </div>
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Department</dt>
                <dd>{{ selectedApproval.bonCommend.department || 'Not specified' }}</dd>
              </div>
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Priority</dt>
                <dd>
                  <Tag :value="selectedApproval.bonCommend.priority" :severity="getPrioritySeverity(selectedApproval.bonCommend.priority)" />
                </dd>
              </div>
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Order Date</dt>
                <dd>{{ formatDate(selectedApproval.bonCommend.order_date) }}</dd>
              </div>
            </dl>
          </div>
          
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
            <h4 class="tw-font-semibold tw-text-gray-900 tw-mb-3">Approval Information</h4>
            <dl class="tw-space-y-2">
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Requested By</dt>
                <dd class="tw-font-medium">{{ selectedApproval.requester?.name || 'Unknown' }}</dd>
              </div>
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Request Date</dt>
                <dd>{{ formatDate(selectedApproval.requested_at) }}</dd>
              </div>
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Total Amount</dt>
                <dd class="tw-text-lg tw-font-bold tw-text-blue-600">{{ formatCurrency(selectedApproval.total_amount) }}</dd>
              </div>
              <div>
                <dt class="tw-text-sm tw-text-gray-600">Status</dt>
                <dd>
                  <Tag 
                    :value="getStatusLabel(selectedApproval.status).label" 
                    :severity="getStatusLabel(selectedApproval.status).severity"
                  />
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Items List (Read-only) -->
        <div>
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Order Items</h4>
          <div class="tw-overflow-x-auto">
            <table class="tw-w-full tw-table-auto tw-border-collapse">
              <thead>
                <tr class="tw-bg-gray-100">
                  <th class="tw-text-left tw-p-3 tw-border">Product</th>
                  <th class="tw-text-center tw-p-3 tw-border">Quantity</th>
                  <th class="tw-text-center tw-p-3 tw-border">Unit</th>
                  <th class="tw-text-right tw-p-3 tw-border">Price</th>
                  <th class="tw-text-right tw-p-3 tw-border">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedApproval.bonCommend.items" :key="item.id">
                  <td class="tw-p-3 tw-border">
                    <div class="tw-font-medium">{{ item.product?.name || 'Unknown Product' }}</div>
                    <div class="tw-text-sm tw-text-gray-600">{{ item.product?.code_interne || '' }}</div>
                  </td>
                  <td class="tw-text-center tw-p-3 tw-border">{{ item.quantity_desired }}</td>
                  <td class="tw-text-center tw-p-3 tw-border">{{ item.unit }}</td>
                  <td class="tw-text-right tw-p-3 tw-border">{{ formatCurrency(item.price) }}</td>
                  <td class="tw-text-right tw-p-3 tw-border tw-font-semibold">
                    {{ formatCurrency(item.quantity_desired * item.price) }}
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="tw-bg-blue-50 tw-font-bold">
                  <td colspan="4" class="tw-text-right tw-p-3 tw-border">Total:</td>
                  <td class="tw-text-right tw-p-3 tw-border tw-text-blue-600">
                    {{ formatCurrency(selectedApproval.total_amount) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedApproval.bonCommend.notes" class="tw-bg-yellow-50 tw-p-4 tw-rounded-lg">
          <h4 class="tw-font-medium tw-text-gray-900 tw-mb-2">Order Notes</h4>
          <p class="tw-text-gray-700">{{ selectedApproval.bonCommend.notes }}</p>
        </div>
      </div>

      <template #footer>
        <Button 
          @click="viewDialogVisible = false" 
          label="Close" 
          icon="pi pi-times" 
          class="p-button-secondary"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'

const router = useRouter()
const toast = useToast()

// Reactive state
const loading = ref(true)
const processing = ref(false)
const approvals = ref([])
const stats = ref({})
const selectedApproval = ref(null)
const modifiableItems = ref([])

// Dialog visibility
const approveDialogVisible = ref(false)
const rejectDialogVisible = ref(false)
const viewDialogVisible = ref(false)
const showValidationErrors = ref(false)

// Filters
const filters = reactive({
  status: null,
  amountRange: null,
  search: ''
})

// Form data
const approvalForm = reactive({
  notes: ''
})

// Options
const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
]

const amountRangeOptions = [
  { label: 'All Amounts', value: null },
  { label: 'Under 10,000 DZD', value: 'under_10k' },
  { label: '10,000 - 50,000 DZD', value: '10k_50k' },
  { label: '50,000 - 100,000 DZD', value: '50k_100k' },
  { label: 'Over 100,000 DZD', value: 'over_100k' }
]

// Computed
const approvedTotal = computed(() => {
  return modifiableItems.value.reduce((total, item) => {
    return total + ((item.approved_quantity || item.quantity_desired) * item.price)
  }, 0)
})

// Methods
const fetchApprovals = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    
    if (filters.status) params.append('status', filters.status)
    if (filters.search) params.append('search', filters.search)
    // Add amount range filtering logic here if needed

    const response = await axios.get(`/api/bon-commend-approvals/my-approvals?${params.toString()}`)
    approvals.value = response.data.data || []
  } catch (err) {
    console.error('Error fetching approvals:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch approvals',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchStatistics = async () => {
  try {
    const response = await axios.get('/api/bon-commend-approvals/statistics')
    if (response.data.status === 'success') {
      stats.value = response.data.data
    }
  } catch (err) {
    console.error('Error fetching statistics:', err)
  }
}

const refreshApprovals = async () => {
  await Promise.all([
    fetchApprovals(),
    fetchStatistics()
  ])
}

const applyFilters = async () => {
  await fetchApprovals()
}

const resetFilters = () => {
  filters.status = null
  filters.amountRange = null
  filters.search = ''
  fetchApprovals()
}

const viewApproval = (approval) => {
  selectedApproval.value = approval
  viewDialogVisible.value = true
}

const openApproveDialog = (approval) => {
  selectedApproval.value = approval
  
  // Prepare modifiable items with approved quantities
  modifiableItems.value = approval.bonCommend.items.map(item => ({
    ...item,
    approved_quantity: item.quantity_desired,
    original_quantity: item.quantity_desired
  }))
  
  approvalForm.notes = ''
  showValidationErrors.value = false
  approveDialogVisible.value = true
}

const openRejectDialog = (approval) => {
  selectedApproval.value = approval
  approvalForm.notes = ''
  showValidationErrors.value = false
  rejectDialogVisible.value = true
}

const processApproval = async (action) => {
  if (action === 'rejected' && !approvalForm.notes) {
    showValidationErrors.value = true
    return
  }

  try {
    processing.value = true
    
    const endpoint = `/api/bon-commend-approvals/${selectedApproval.value.id}/${action === 'approved' ? 'approve' : 'reject'}`
    const data = {
      approval_notes: approvalForm.notes
    }

    // Add modified items for approval
    if (action === 'approved' && modifiableItems.value.length > 0) {
      const modifiedItems = modifiableItems.value
        .filter(item => item.approved_quantity !== item.original_quantity)
        .map(item => ({
          item_id: item.id,
          quantity_desired: item.approved_quantity
        }))
      
      if (modifiedItems.length > 0) {
        data.modified_items = modifiedItems
      }
    }

    const response = await axios.post(endpoint, data)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message,
        life: 3000
      })

      // Close dialogs and refresh
      approveDialogVisible.value = false
      rejectDialogVisible.value = false
      await refreshApprovals()
    }
  } catch (err) {
    console.error(`Error ${action === 'approved' ? 'approving' : 'rejecting'} order:`, err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || `Failed to ${action === 'approved' ? 'approve' : 'reject'} order`,
      life: 3000
    })
  } finally {
    processing.value = false
  }
}

// Utility functions
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusLabel = (status) => {
  const statusMap = {
    'pending': { label: 'Pending', severity: 'warning' },
    'approved': { label: 'Approved', severity: 'success' },
    'rejected': { label: 'Rejected', severity: 'danger' }
  }
  return statusMap[status] || { label: status, severity: 'secondary' }
}

const getPrioritySeverity = (priority) => {
  const priorityMap = {
    'low': 'secondary',
    'normal': 'info',
    'high': 'warning',
    'urgent': 'danger'
  }
  return priorityMap[priority] || 'info'
}

// Lifecycle
onMounted(async () => {
  await refreshApprovals()
})
</script>

<style scoped>
.p-datatable .p-datatable-thead > tr > th {
  background-color: #f8f9fa;
}
</style>