<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { debounce } from 'lodash-es'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import Paginator from 'primevue/paginator'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

// Props
const props = defineProps({
  caisseId: {
    type: [String, Number],
    required: true
  },
  sessionId: {
    type: [String, Number],
    default: null
  }
})

// Composables
const toast = useToast()
const confirm = useConfirm()
const router = useRouter()
const route = useRoute()

// Reactive state
const transfers = ref([])
const loading = ref(true)
const searchQuery = ref('')
const perPage = ref(15)
const meta = ref(null)
const stats = ref({
  total: 0,
  completed: 0,
  pending: 0,
  rejected: 0
})
const viewMode = ref('table')

// Get sessionId from route query if not provided as prop
const sessionId = computed(() => {
  return props.sessionId || route.query.session_id
})

// Filters
const filters = ref({
  caisse_id: props.caisseId,
  status: '',
  user_id: ''
})

// Options
const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Expired', value: 'expired' }
]

const userOptions = ref([])

// Methods
const fetchTransfers = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: searchQuery.value,
      caisse_id: props.caisseId,
      ...filters.value
    }

    // Add session_id filter if provided
    if (sessionId.value) {
      params.caisse_session_id = sessionId.value
      console.log('Filtering by session ID:', sessionId.value)
    }

    console.log('Fetching transfers with params:', params)

    const response = await axios.get('/api/caisse-transfers', { params })

    console.log('API Response:', response.data)

    // Handle the response from CaisseTransferCollection
    if (response.data && response.data.data) {
      transfers.value = response.data.data || []
      meta.value = response.data.meta || null

      // Use summary from API if available, otherwise calculate
      if (response.data.summary) {
        stats.value = {
          total: response.data.summary.total_count || transfers.value.length,
          completed: response.data.summary.accepted_count || transfers.value.filter(t => t.status === 'accepted').length,
          pending: response.data.summary.pending_count || transfers.value.filter(t => t.status === 'pending').length,
          rejected: response.data.summary.rejected_count || transfers.value.filter(t => t.status === 'rejected').length
        }
      } else {
        // Calculate stats locally
        stats.value = {
          total: transfers.value.length,
          completed: transfers.value.filter(t => t.status === 'accepted').length,
          pending: transfers.value.filter(t => t.status === 'pending').length,
          rejected: transfers.value.filter(t => t.status === 'rejected').length
        }
      }
    } else {
      console.error('Unexpected response format:', response.data)
      showToast('error', 'Error', 'Unexpected response format from server')
      transfers.value = []
      meta.value = null
    }
  } catch (error) {
    console.error('Error fetching transfers:', error)
    if (error.response) {
      console.error('Response error:', error.response.data)
      showToast('error', 'Error', error.response.data.message || 'Failed to load transfers')
    } else {
      showToast('error', 'Error', 'Network error - Failed to load transfers')
    }
    transfers.value = []
    meta.value = null
  } finally {
    loading.value = false
  }
}

const loadUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    if (response.data) {
      userOptions.value = response.data
    }
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const debouncedSearch = debounce(() => fetchTransfers(), 400)

const onPageChange = (event) => {
  fetchTransfers(event.page + 1)
}

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table'
}

const goBack = () => {
  router.push({ name: 'coffre.caisse' })
}

const createNewTransfer = () => {
  // Navigate to create transfer page or open modal
  router.push({ name: 'coffre.caisse.transfer.create', params: { caisse_id: props.caisseId } })
}

const viewTransfer = (transfer) => {
  // Navigate to transfer details page or open modal
  router.push({ name: 'coffre.caisse.transfer.view', params: { caisse_id: props.caisseId, transfer_id: transfer.id } })
}

const approveTransfer = async (transfer) => {
  try {
    const response = await axios.post(`/api/caisse-transfers/${transfer.id}/accept`)
    if (response.data.success) {
      showToast('success', 'Success', 'Transfer approved successfully')
      fetchTransfers()
    } else {
      showToast('error', 'Error', response.data.message || 'Failed to approve transfer')
    }
  } catch (error) {
    console.error('Error approving transfer:', error)
    showToast('error', 'Error', 'Failed to approve transfer')
  }
}

const rejectTransfer = async (transfer) => {
  try {
    const response = await axios.post(`/api/caisse-transfers/${transfer.id}/reject`)
    if (response.data.success) {
      showToast('success', 'Success', 'Transfer rejected successfully')
      fetchTransfers()
    } else {
      showToast('error', 'Error', response.data.message || 'Failed to reject transfer')
    }
  } catch (error) {
    console.error('Error rejecting transfer:', error)
    showToast('error', 'Error', 'Failed to reject transfer')
  }
}

const confirmDeleteTransfer = (transfer) => {
  confirm.require({
    message: `Are you sure you want to delete this transfer? This action is irreversible.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => deleteTransfer(transfer)
  })
}

const deleteTransfer = async (transfer) => {
  try {
    const response = await axios.delete(`/api/caisse-transfers/${transfer.id}`)
    if (response.data.success) {
      showToast('success', 'Success', 'Transfer deleted successfully')
      fetchTransfers()
    } else {
      showToast('error', 'Error', response.data.message || 'Failed to delete transfer')
    }
  } catch (error) {
    console.error('Error deleting transfer:', error)
    showToast('error', 'Error', 'Failed to delete transfer')
  }
}

// Utility methods
const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount || 0)
}

const formatDate = (dateString) => {
  if (!dateString) return 'Not specified'
  return new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateString))
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    completed: 'Completed',
    rejected: 'Rejected',
    expired: 'Expired'
  }
  return labels[status] || status
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'warning',
    completed: 'success',
    rejected: 'danger',
    expired: 'secondary'
  }
  return severities[status] || 'secondary'
}

const getStatusBgClass = (status) => {
  const classes = {
    pending: 'tw-bg-gradient-to-r tw-from-yellow-500 tw-to-orange-500',
    completed: 'tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500',
    rejected: 'tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500',
    expired: 'tw-bg-gradient-to-r tw-from-slate-500 tw-to-slate-600'
  }
  return classes[status] || 'tw-bg-gradient-to-r tw-from-slate-500 tw-to-slate-600'
}

// Lifecycle
onMounted(() => {
  fetchTransfers()
  loadUsers()
})
</script>

<template>
  <div class="tw-bg-slate-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-white tw-text-4xl tw-font-bold tw-flex tw-items-center">
              <i class="pi pi-arrow-right-arrow-left tw-mr-4 tw-text-5xl"></i>
              <span>
                Caisse Transfers
                <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
                  All transfer transactions for this cash register
                </p>
              </span>
            </h1>
          </div>
          <div class="tw-flex tw-gap-2">
            <Button
              icon="pi pi-arrow-left"
              label="Back to Caisses"
              class="p-button-secondary p-button-lg"
              @click="goBack"
            />
            <Button
              icon="pi pi-plus"
              label="New Transfer"
              class="!tw-bg-white !tw-text-blue-600 hover:!tw-bg-slate-100 !tw-px-5 !tw-py-3 !tw-rounded-lg !tw-font-semibold !tw-shadow-sm"
              @click="createNewTransfer"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <!-- Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500">
            <i class="pi pi-list tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Total Transfers</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats.total || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
            <i class="pi pi-check-circle tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Completed</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats.completed || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-yellow-500 tw-to-orange-500">
            <i class="pi pi-clock tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Pending</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats.pending || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
            <i class="pi pi-times-circle tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Rejected</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats.rejected || 0 }}</div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="tw-bg-white tw-p-4 tw-rounded-2xl tw-shadow-md tw-mb-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-12 tw-gap-4 tw-items-center">
          <div class="md:tw-col-span-4">
            <InputText
              v-model="searchQuery"
              placeholder="Search transfers..."
              @input="debouncedSearch"
              class="tw-w-full"
            />
          </div>
          <div class="md:tw-col-span-5 tw-flex tw-gap-4">
            <Dropdown
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by Status"
              @change="fetchTransfers"
              showClear
              class="tw-w-full"
            />
            <Dropdown
              v-model="filters.user_id"
              :options="userOptions"
              optionLabel="name"
              optionValue="id"
              placeholder="Filter by User"
              @change="fetchTransfers"
              showClear
              class="tw-w-full"
            />
          </div>
          <div class="md:tw-col-span-3 tw-flex tw-justify-end tw-gap-2">
            <Button
              :icon="viewMode === 'table' ? 'pi pi-th-large' : 'pi pi-list'"
              class="p-button-outlined"
              v-tooltip.top="viewMode === 'table' ? 'Card View' : 'Table View'"
              @click="toggleView"
            />
            <Button
              icon="pi pi-refresh"
              class="p-button-outlined"
              @click="fetchTransfers"
              :loading="loading"
              v-tooltip.top="'Refresh'"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="tw-text-center tw-py-10">
        <ProgressSpinner />
      </div>

      <!-- Table View -->
      <div v-else-if="viewMode === 'table' && transfers.length > 0" class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-overflow-hidden">
        <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
          <thead class="tw-bg-gray-50">
            <tr>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Transfer Details
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                From/To Users
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Amount
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Status
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Date
              </th>
            
            </tr>
          </thead>
          <tbody class="tw-bg-white tw-divide-y tw-divide-gray-200">
            <tr v-for="transfer in transfers" :key="transfer.id" class="hover:tw-bg-blue-50 tw-transition-colors tw-duration-200">
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500 tw-text-white">
                    <i class="pi pi-arrow-right-arrow-left"></i>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-slate-800">{{ transfer.reference || `Transfer #${transfer.id}` }}</div>
                    <div class="tw-text-slate-500 tw-text-sm">{{ transfer.description || 'No description' }}</div>
                  </div>
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-text-sm">
                  <div class="tw-font-medium tw-text-slate-800">From: {{ transfer.from_user?.name || 'N/A' }}</div>
                  <div class="tw-text-slate-500">To: {{ transfer.to_user?.name || 'N/A' }}</div>
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-flex tw-flex-col tw-gap-1">
                <div>
                    amount sended : 
                    <span class="tw-font-bold tw-text-sm tw-text-green-600">
                      {{ formatCurrency(transfer.amount_sended) }}
                    </span>
                </div>
                <div>
                    amount received :
                    <span class="tw-font-bold tw-text-sm tw-text-green-600">
                    {{ formatCurrency(transfer.amount_received) }}
                    </span>
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <Tag
                  :value="getStatusLabel(transfer.status)"
                  :severity="getStatusSeverity(transfer.status)"
                />
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-text-sm tw-text-slate-600">
                  {{ formatDate(transfer.created_at) }}
                </div>
              </td>
              
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Card View -->
      <div v-else-if="viewMode === 'card' && transfers.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <div
          v-for="transfer in transfers"
          :key="transfer.id"
          class="tw-bg-white tw-rounded-2xl tw-shadow-md hover:tw-shadow-xl hover:-tw-translate-y-1 tw-transition-all tw-duration-300 tw-flex tw-flex-col"
        >
          <div class="tw-p-5 tw-rounded-t-2xl tw-flex tw-justify-between tw-items-start" :class="getStatusBgClass(transfer.status)">
            <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-white/20 tw-text-white">
              <i class="pi pi-arrow-right-arrow-left tw-text-2xl"></i>
            </div>
            <Tag :value="getStatusLabel(transfer.status)" :severity="getStatusSeverity(transfer.status)" />
          </div>
          <div class="tw-p-5 tw-flex-grow tw-flex tw-flex-col">
            <h3 class="tw-font-bold tw-text-slate-800 tw-text-lg">{{ transfer.reference || `Transfer #${transfer.id}` }}</h3>
            <div class="tw-text-sm tw-text-slate-600 tw-mb-2">
              <div>From: {{ transfer.fromUser?.name || 'N/A' }}</div>
              <div>To: {{ transfer.toUser?.name || 'N/A' }}</div>
            </div>
            <div class="tw-text-2xl tw-font-bold tw-text-green-600 tw-mb-3">
              {{ formatCurrency(transfer.amount) }}
            </div>
            <div class="tw-text-xs tw-text-slate-500 tw-mb-4">
              {{ formatDate(transfer.created_at) }}
            </div>
            <div class="tw-mt-auto">
              <Button
                icon="pi pi-eye"
                label="View Details"
                class="p-button-info p-button-outlined !tw-w-full"
                @click="viewTransfer(transfer)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading" class="tw-text-center tw-p-8 tw-border-2 tw-border-dashed tw-border-slate-300 tw-rounded-2xl tw-bg-white tw-mt-4">
        <i class="pi pi-arrow-right-arrow-left tw-text-6xl tw-text-slate-400"></i>
        <h4 class="tw-text-slate-700 tw-mt-4 tw-mb-2 tw-font-bold tw-text-2xl">No Transfers Found</h4>
        <p class="tw-text-slate-500 tw-mb-6">No transfer transactions found for this cash register.</p>
        <Button icon="pi pi-plus" label="Create New Transfer" @click="createNewTransfer" />
      </div>

      <!-- Pagination -->
      <Paginator
        v-if="!loading && meta && meta.total > perPage"
        :rows="perPage"
        :total-records="meta.total"
        @page="onPageChange"
        class="tw-mt-6"
      />
    </div>

    <!-- Modals -->
    <Toast />
    <ConfirmDialog />
  </div>
</template>



<style scoped>
/* Custom styles for the transfers list */
</style>
