<template>
  <div class="tw-bg-slate-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-green-600 tw-to-emerald-600 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-white tw-text-4xl tw-font-bold tw-flex tw-items-center">
              <i class="pi pi-money-bill tw-mr-4 tw-text-5xl"></i>
              <span>
                Financial Transactions
                <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
                  All financial transactions for this cash register
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
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <!-- Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
            <i class="pi pi-list tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Total Transactions</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats.total || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500">
            <i class="pi pi-credit-card tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Payments</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats.payments || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
            <i class="pi pi-refresh tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Refunds</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats.refunds || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-yellow-500 tw-to-orange-500">
            <i class="pi pi-dollar tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Total Amount</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ formatCurrency(stats.total_amount || 0) }}</div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="tw-bg-white tw-p-4 tw-rounded-2xl tw-shadow-md tw-mb-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-12 tw-gap-4 tw-items-center">
          <div class="md:tw-col-span-4">
            <InputText
              v-model="searchQuery"
              placeholder="Search transactions..."
              @input="debouncedSearch"
              class="tw-w-full"
            />
          </div>
          <div class="md:tw-col-span-5 tw-flex tw-gap-4">
            <Dropdown
              v-model="filters.transaction_type"
              :options="transactionTypeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by Type"
              @change="fetchTransactions"
              showClear
              class="tw-w-full"
            />
            <Dropdown
              v-model="filters.payment_method"
              :options="paymentMethodOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by Payment Method"
              @change="fetchTransactions"
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
              @click="fetchTransactions"
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
      <div v-else-if="viewMode === 'table' && transactions.length > 0" class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-overflow-hidden">
        <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
          <thead class="tw-bg-gray-50">
            <tr>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Transaction Details
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Patient & Prestation
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Amount & Method
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Cashier
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">
                Date
              </th>
            </tr>
          </thead>
          <tbody class="tw-bg-white tw-divide-y tw-divide-gray-200">
            <tr v-for="transaction in transactions" :key="transaction.id" class="hover:tw-bg-green-50 tw-transition-colors tw-duration-200">
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center" :class="getTransactionIconClass(transaction.transaction_type)">
                    <i :class="getTransactionIcon(transaction.transaction_type)"></i>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-slate-800">{{ transaction.reference || `Transaction #${transaction.id}` }}</div>
                    <div class="tw-text-slate-500 tw-text-sm">{{ getTransactionTypeLabel(transaction.transaction_type) }}</div>
                  </div>
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-text-sm">
                  <div class="tw-font-medium tw-text-slate-800">{{ transaction.patient?.name || 'N/A' }}</div>
                  <div class="tw-text-slate-500">{{ transaction.ficheNavetteItem?.prestation?.name || 'N/A' }}</div>
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-text-sm">
                  <div class="tw-font-bold tw-text-green-600">{{ formatCurrency(transaction.amount) }}</div>
                  <div class="tw-text-slate-500">{{ getPaymentMethodLabel(transaction.payment_method) }}</div>
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-text-sm tw-text-slate-600">
                  {{ transaction.cashier?.name || 'N/A' }}
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-text-sm tw-text-slate-600">
                  {{ formatDate(transaction.created_at) }}
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Card View -->
      <div v-else-if="viewMode === 'card' && transactions.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <div
          v-for="transaction in transactions"
          :key="transaction.id"
          class="tw-bg-white tw-rounded-2xl tw-shadow-md hover:tw-shadow-xl hover:-tw-translate-y-1 tw-transition-all tw-duration-300 tw-flex tw-flex-col"
        >
          <div class="tw-p-5 tw-rounded-t-2xl tw-flex tw-justify-between tw-items-start" :class="getTransactionBgClass(transaction.transaction_type)">
            <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-white/20 tw-text-white">
              <i :class="getTransactionIcon(transaction.transaction_type)"></i>
            </div>
            <Tag :value="getTransactionTypeLabel(transaction.transaction_type)" :severity="getTransactionSeverity(transaction.transaction_type)" />
          </div>
          <div class="tw-p-5 tw-flex-grow tw-flex tw-flex-col">
            <h3 class="tw-font-bold tw-text-slate-800 tw-text-lg">{{ transaction.reference || `Transaction #${transaction.id}` }}</h3>
            <div class="tw-text-sm tw-text-slate-600 tw-mb-2">
              <div>Patient: {{ transaction.patient?.name || 'N/A' }}</div>
              <div>Prestation: {{ transaction.ficheNavetteItem?.prestation?.name || 'N/A' }}</div>
            </div>
            <div class="tw-text-2xl tw-font-bold tw-text-green-600 tw-mb-3">
              {{ formatCurrency(transaction.amount) }}
            </div>
            <div class="tw-text-xs tw-text-slate-500 tw-mb-2">
              {{ getPaymentMethodLabel(transaction.payment_method) }}
            </div>
            <div class="tw-text-xs tw-text-slate-500">
              {{ formatDate(transaction.created_at) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading" class="tw-text-center tw-p-8 tw-border-2 tw-border-dashed tw-border-slate-300 tw-rounded-2xl tw-bg-white tw-mt-4">
        <i class="pi pi-money-bill tw-text-6xl tw-text-slate-400"></i>
        <h4 class="tw-text-slate-700 tw-mt-4 tw-mb-2 tw-font-bold tw-text-2xl">No Transactions Found</h4>
        <p class="tw-text-slate-500 tw-mb-6">No financial transactions found for this cash register.</p>
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { debounce } from 'lodash-es'
import { useRouter } from 'vue-router'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import Paginator from 'primevue/paginator'
import Toast from 'primevue/toast'

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
const router = useRouter()
const route = useRoute()

// Get sessionId from route query if not provided as prop
const sessionId = computed(() => {
  return props.sessionId || route.query.session_id
})

// Reactive state
const transactions = ref([])
const loading = ref(true)
const searchQuery = ref('')
const perPage = ref(15)
const meta = ref(null)
const stats = ref({
  total: 0,
  deposits: 0,
  withdrawals: 0,
  total_amount: 0
})
const viewMode = ref('table')

// Filters
const filters = ref({
  caisse_session_id: sessionId.value,
  transaction_type: '',
  status: ''
})

// Options
const transactionTypeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Deposit', value: 'deposit' },
  { label: 'Withdrawal', value: 'withdrawal' },
  { label: 'Transfer In', value: 'transfer_in' },
  { label: 'Transfer Out', value: 'transfer_out' },
  { label: 'Adjustment', value: 'adjustment' }
]

const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Rejected', value: 'rejected' }
]

// Methods
const fetchTransactions = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: searchQuery.value,
      ...filters.value
    }

    // Add session_id filter if provided
    if (sessionId.value) {
      params.caisse_session_id = sessionId.value
      console.log('Filtering by session ID:', sessionId.value)
    }

    console.log('Fetching coffre transactions with params:', params)

    const response = await axios.get('/api/coffre-transactions', { params })

    console.log('API Response:', response.data)

    if (response.data && response.data.data) {
      transactions.value = response.data.data || []
      meta.value = response.data.meta || null

      // Calculate stats
      if (response.data.summary) {
        stats.value = response.data.summary
      } else {
        stats.value = {
          total: transactions.value.length,
          deposits: transactions.value.filter(t => t.transaction_type === 'deposit').length,
          withdrawals: transactions.value.filter(t => t.transaction_type === 'withdrawal').length,
          total_amount: transactions.value.reduce((sum, t) => sum + parseFloat(t.amount || 0), 0)
        }
      }
    } else {
      console.error('Unexpected response format:', response.data)
      showToast('error', 'Error', 'Unexpected response format from server')
      transactions.value = []
      meta.value = null
    }
  } catch (error) {
    console.error('Error fetching transactions:', error)
    if (error.response) {
      console.error('Response error:', error.response.data)
      showToast('error', 'Error', error.response.data.message || 'Failed to load transactions')
    } else {
      showToast('error', 'Error', 'Network error - Failed to load transactions')
    }
    transactions.value = []
    meta.value = null
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => fetchTransactions(), 400)

const onPageChange = (event) => {
  fetchTransactions(event.page + 1)
}

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table'
}

const goBack = () => {
  router.push({ name: 'coffre.caisse' })
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

const getTransactionTypeLabel = (type) => {
  const labels = {
    deposit: 'Deposit',
    withdrawal: 'Withdrawal',
    transfer_in: 'Transfer In',
    transfer_out: 'Transfer Out',
    adjustment: 'Adjustment'
  }
  return labels[type] || type
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    completed: 'Completed',
    rejected: 'Rejected'
  }
  return labels[status] || status
}

const getTransactionIcon = (type) => {
  const icons = {
    deposit: 'pi pi-plus-circle',
    withdrawal: 'pi pi-minus-circle',
    transfer_in: 'pi pi-arrow-down',
    transfer_out: 'pi pi-arrow-up',
    adjustment: 'pi pi-cog'
  }
  return icons[type] || 'pi pi-money-bill'
}

const getTransactionIconClass = (type) => {
  const classes = {
    deposit: 'tw-bg-green-500 tw-text-white',
    withdrawal: 'tw-bg-red-500 tw-text-white',
    transfer_in: 'tw-bg-blue-500 tw-text-white',
    transfer_out: 'tw-bg-orange-500 tw-text-white',
    adjustment: 'tw-bg-yellow-500 tw-text-white'
  }
  return classes[type] || 'tw-bg-gray-500 tw-text-white'
}

const getTransactionBgClass = (type) => {
  const classes = {
    deposit: 'tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500',
    withdrawal: 'tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500',
    transfer_in: 'tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500',
    transfer_out: 'tw-bg-gradient-to-r tw-from-orange-500 tw-to-amber-500',
    adjustment: 'tw-bg-gradient-to-r tw-from-yellow-500 tw-to-orange-500'
  }
  return classes[type] || 'tw-bg-gradient-to-r tw-from-gray-500 tw-to-slate-600'
}

const getTransactionSeverity = (type) => {
  const severities = {
    deposit: 'success',
    withdrawal: 'danger',
    transfer_in: 'info',
    transfer_out: 'warning',
    adjustment: 'secondary'
  }
  return severities[type] || 'secondary'
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'warning',
    completed: 'success',
    rejected: 'danger'
  }
  return severities[status] || 'secondary'
}

// Lifecycle
onMounted(() => {
  fetchTransactions()
})
</script>

<style scoped>
/* Custom styles for the financial transactions list */
</style>
