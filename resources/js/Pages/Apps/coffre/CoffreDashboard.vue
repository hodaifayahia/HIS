<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import Chart from 'primevue/chart'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

// Services
import { coffreService } from '../../../Components/Apps/services/Coffre/CoffreService'
import { coffreTransactionService } from '../../../Components/Apps/services/Coffre/CoffreTransactionService'

// Composables
const toast = useToast()
const authStore = useAuthStore()
const router = useRouter()

// Reactive state
const loading = ref(true)
const stats = ref({
  totalCoffres: 0,
  activeCoffres: 0,
  totalBalance: 0,
  todayTransactions: 0,
  totalTransactions: 0
})

const coffres = ref([])
const recentTransactions = ref([])
const chartData = ref({})
const chartOptions = ref({})

// Dashboard sections
const showCoffresDialog = ref(false)
const showTransactionsDialog = ref(false)
const selectedCoffre = ref(null)

// Filters
const dateRange = ref([new Date(), new Date()])
const selectedService = ref(null)
const services = ref([])
const transactionTypes = ref([])

// Computed properties
const user = computed(() => authStore.user)
const hasPermission = (permission) => {
  // Add permission checking logic here
  return true // For now, allow all
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-DZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Methods
const loadDashboardData = async () => {
  loading.value = true
  try {
    // Load all data in parallel
    const [
      coffresResult,
      transactionsResult,
      transactionTypesResult,
      servicesResult
    ] = await Promise.allSettled([
      coffreService.getAll(),
      coffreTransactionService.getRecentTransactions(10),
      coffreTransactionService.getTransactionTypes(),
      coffreService.getServices()
    ])

    // Process coffres
    if (coffresResult.status === 'fulfilled' && coffresResult.value.success) {
      coffres.value = coffresResult.value.data || []
      stats.value.totalCoffres = coffres.value.length
      stats.value.activeCoffres = coffres.value.filter(c => c.current_balance !== null).length
      stats.value.totalBalance = coffres.value.reduce((sum, c) => sum + parseFloat(c.current_balance || 0), 0)
    }

    // Process recent transactions
    if (transactionsResult.status === 'fulfilled' && transactionsResult.value.success) {
      recentTransactions.value = transactionsResult.value.data || []
      stats.value.todayTransactions = recentTransactions.value.length
      stats.value.totalTransactions = recentTransactions.value.length
    }

    // Process transaction types
    if (transactionTypesResult.status === 'fulfilled' && transactionTypesResult.value.success) {
      transactionTypes.value = transactionTypesResult.value.data || []
    }

    // Process services
    if (servicesResult.status === 'fulfilled' && servicesResult.value.success) {
      services.value = servicesResult.value.data || []
    }

    // Generate chart data
    generateChartData()

  } catch (error) {
    console.error('Error loading dashboard data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load dashboard data',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const generateChartData = () => {
  // Balance trend chart data
  const balanceData = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
      label: 'Daily Balance Changes',
      data: [15000, 18000, 16000, 22000, 25000, 28000, 30000],
      backgroundColor: 'rgba(59, 130, 246, 0.2)',
      borderColor: 'rgba(59, 130, 246, 1)',
      borderWidth: 2,
      fill: true
    }]
  }

  chartData.value = balanceData
  chartOptions.value = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top'
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return formatCurrency(value)
          }
        }
      }
    }
  }
}

const openCoffresDialog = () => {
  showCoffresDialog.value = true
}

const openTransactionsDialog = () => {
  showTransactionsDialog.value = true
}

const viewCoffreDetails = async (coffre) => {
  try {
    await router.push({
      name: 'coffre.transactions',
      params: { coffre_id: coffre.id }
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to navigate to coffre details',
      life: 3000
    })
  }
}

const createNewTransaction = () => {
  router.push({ name: 'coffre.transactions.create' })
}

const getStatusColor = (status) => {
  const colors = {
    'active': 'success',
    'inactive': 'danger',
    'pending': 'warning'
  }
  return colors[status] || 'secondary'
}

const getStatusText = (status) => {
  const texts = {
    'active': 'Active',
    'inactive': 'Inactive',
    'pending': 'Pending'
  }
  return texts[status] || status
}

const getTransactionTypeLabel = (type) => {
  const typeLabels = {
    'deposit': 'Deposit',
    'withdrawal': 'Withdrawal',
    'transfer': 'Transfer',
    'adjustment': 'Adjustment'
  }
  return typeLabels[type] || type
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
})
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-to-blue-50 tw-p-6">
    <div class="tw-max-w-7xl tw-mx-auto tw-space-y-6">

      <!-- Header -->
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Coffre Dashboard</h1>
          <p class="tw-text-gray-600 tw-mt-1">Manage cash registers and financial operations</p>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button
            icon="pi pi-refresh"
            label="Refresh"
            @click="loadDashboardData"
            :loading="loading"
            class="tw-rounded-lg"
          />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
        <!-- Total Coffres -->
        <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-text-white">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-blue-100 tw-text-sm tw-font-medium">Total Coffres</p>
                <p class="tw-text-2xl tw-font-bold">{{ stats.totalCoffres }}</p>
              </div>
              <div class="tw-w-12 tw-h-12 tw-bg-blue-400 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <i class="pi pi-safe tw-text-xl"></i>
              </div>
            </div>
          </template>
        </Card>

        <!-- Active Coffres -->
        <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-r tw-from-green-500 tw-to-green-600 tw-text-white">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-green-100 tw-text-sm tw-font-medium">Active Coffres</p>
                <p class="tw-text-2xl tw-font-bold">{{ stats.activeCoffres }}</p>
              </div>
              <div class="tw-w-12 tw-h-12 tw-bg-green-400 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <i class="pi pi-check-circle tw-text-xl"></i>
              </div>
            </div>
          </template>
        </Card>

        <!-- Total Balance -->
        <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-r tw-from-purple-500 tw-to-purple-600 tw-text-white">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-purple-100 tw-text-sm tw-font-medium">Total Balance</p>
                <p class="tw-text-2xl tw-font-bold">{{ formatCurrency(stats.totalBalance) }}</p>
              </div>
              <div class="tw-w-12 tw-h-12 tw-bg-purple-400 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <i class="pi pi-dollar tw-text-xl"></i>
              </div>
            </div>
          </template>
        </Card>

        <!-- Today's Transactions -->
        <Card class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-r tw-from-orange-500 tw-to-orange-600 tw-text-white">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-orange-100 tw-text-sm tw-font-medium">Today's Transactions</p>
                <p class="tw-text-2xl tw-font-bold">{{ stats.todayTransactions }}</p>
              </div>
              <div class="tw-w-12 tw-h-12 tw-bg-orange-400 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <i class="pi pi-list tw-text-xl"></i>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Main Content Grid -->
      <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6">

        <!-- Left Column - Charts and Quick Actions -->
        <div class="lg:tw-col-span-2 tw-space-y-6">

          <!-- Balance Chart -->
          <Card class="tw-shadow-lg tw-border-0">
            <template #title>
              <div class="tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-chart-line tw-text-blue-600"></i>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Balance Trend</h3>
              </div>
            </template>
            <template #content>
              <div class="tw-h-80">
                <Chart type="line" :data="chartData" :options="chartOptions" />
              </div>
            </template>
          </Card>

          <!-- Recent Transactions -->
          <Card class="tw-shadow-lg tw-border-0">
            <template #title>
              <div class="tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-list tw-text-green-600"></i>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Recent Transactions</h3>
                </div>
                <Button
                  label="View All"
                  size="small"
                  text
                  @click="$router.push('/coffre/transactions')"
                  class="tw-text-blue-600"
                />
              </div>
            </template>
            <template #content>
              <DataTable
                :value="recentTransactions.slice(0, 5)"
                :loading="loading"
                class="p-datatable-sm"
                stripedRows
              >
                <template #empty>
                  <div class="tw-text-center tw-py-8">
                    <i class="pi pi-inbox tw-text-4xl tw-text-gray-300"></i>
                    <p class="tw-text-gray-500 tw-mt-2">No recent transactions</p>
                  </div>
                </template>

                <Column field="reference" header="Reference" style="min-width: 120px;">
                  <template #body="{ data }">
                    <span class="tw-font-medium">{{ data.reference || `TXN-${data.id}` }}</span>
                  </template>
                </Column>

                <Column field="amount" header="Amount" style="min-width: 100px;">
                  <template #body="{ data }">
                    <span :class="data.transaction_type === 'deposit' ? 'tw-text-green-600' : 'tw-text-red-600'">
                      {{ data.transaction_type === 'deposit' ? '+' : '-' }}{{ formatCurrency(data.amount) }}
                    </span>
                  </template>
                </Column>

                <Column field="created_at" header="Date" style="min-width: 100px;">
                  <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                  </template>
                </Column>

                <Column field="transaction_type" header="Status" style="min-width: 80px;">
                  <template #body="{ data }">
                    <Tag :value="getTransactionTypeLabel(data.transaction_type)" :severity="getStatusColor(data.transaction_type)" />
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </div>

        <!-- Right Column - Coffres and Actions -->
        <div class="tw-space-y-6">

          <!-- Active Coffres -->
          <Card class="tw-shadow-lg tw-border-0">
            <template #title>
              <div class="tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <i class="pi pi-safe tw-text-blue-600"></i>
                  <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Active Coffres</h3>
                </div>
                <Button
                  icon="pi pi-plus"
                  size="small"
                  @click="openCoffresDialog"
                  class="tw-rounded-lg"
                />
              </div>
            </template>
            <template #content>
              <div class="tw-space-y-3">
                <div
                  v-for="coffre in coffres.filter(c => c.current_balance !== null).slice(0, 3)"
                  :key="coffre.id"
                  class="tw-flex tw-items-center tw-justify-between tw-p-3 tw-bg-gray-50 tw-rounded-lg"
                >
                  <div>
                    <p class="tw-font-medium tw-text-gray-900">{{ coffre.name }}</p>
                    <p class="tw-text-sm tw-text-gray-500">{{ formatCurrency(coffre.current_balance) }}</p>
                  </div>
                  <Button
                    label="View"
                    size="small"
                    @click.stop="viewCoffreDetails(coffre)"
                    class="tw-rounded-lg"
                  />
                </div>

                <div v-if="coffres.filter(c => c.current_balance !== null).length === 0" class="tw-text-center tw-py-8">
                  <i class="pi pi-safe tw-text-4xl tw-text-gray-300"></i>
                  <p class="tw-text-gray-500 tw-mt-2">No active coffres</p>
                </div>
              </div>
            </template>
          </Card>

          <!-- Quick Actions -->
          <Card class="tw-shadow-lg tw-border-0">
            <template #title>
              <div class="tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-bolt tw-text-purple-600"></i>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Quick Actions</h3>
              </div>
            </template>
            <template #content>
              <div class="tw-space-y-3">
                <Button
                  label="New Transaction"
                  icon="pi pi-plus"
                  class="tw-w-full tw-justify-start"
                  @click="createNewTransaction"
                />
                <Button
                  label="Transfer Funds"
                  icon="pi pi-arrow-right-arrow-left"
                  class="tw-w-full tw-justify-start"
                  severity="secondary"
                  @click="$router.push('/coffre/transfers')"
                />
                <Button
                  label="Generate Report"
                  icon="pi pi-file-pdf"
                  class="tw-w-full tw-justify-start"
                  severity="secondary"
                  @click="$router.push('/coffre/reports')"
                />
                <Button
                  label="Manage Coffres"
                  icon="pi pi-cog"
                  class="tw-w-full tw-justify-start"
                  severity="secondary"
                  @click="openCoffresDialog"
                />
              </div>
            </template>
          </Card>

          <!-- Transaction Types Summary -->
          <Card class="tw-shadow-lg tw-border-0">
            <template #title>
              <div class="tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-chart-pie tw-text-orange-600"></i>
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Transaction Types</h3>
              </div>
            </template>
            <template #content>
              <div class="tw-space-y-3">
                <div
                  v-for="type in transactionTypes.slice(0, 4)"
                  :key="type.id"
                  class="tw-flex tw-items-center tw-justify-between tw-p-3 tw-bg-gray-50 tw-rounded-lg"
                >
                  <div>
                    <p class="tw-font-medium tw-text-gray-900">{{ type.name }}</p>
                    <p class="tw-text-sm tw-text-gray-500">{{ type.description || 'Transaction type' }}</p>
                  </div>
                  <Tag :value="type.count || 0" severity="info" />
                </div>

                <div v-if="transactionTypes.length === 0" class="tw-text-center tw-py-8">
                  <i class="pi pi-chart-pie tw-text-4xl tw-text-gray-300"></i>
                  <p class="tw-text-gray-500 tw-mt-2">No transaction types</p>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
        <ProgressBar mode="indeterminate" style="width: 200px" />
      </div>
    </div>

    <!-- Coffres Dialog -->
    <Dialog
      v-model:visible="showCoffresDialog"
      modal
      header="Manage Coffres"
      :style="{ width: '900px' }"
      class="p-fluid"
    >
      <DataTable
        :value="coffres"
        :loading="loading"
        class="p-datatable-sm"
        stripedRows
      >
        <Column field="name" header="Name" sortable />
        <Column field="location" header="Location" sortable />
        <Column field="current_balance" header="Balance" sortable>
          <template #body="{ data }">
            {{ formatCurrency(data.current_balance) }}
          </template>
        </Column>
        <Column field="user.name" header="Responsible" sortable />
        <Column header="Actions" style="min-width: 150px;">
          <template #body="{ data }">
            <Button
              label="View"
              size="small"
              @click="viewCoffreDetails(data)"
              class="tw-mr-2"
            />
            <Button
              icon="pi pi-pencil"
              size="small"
              severity="secondary"
              @click="$router.push(`/coffre/coffres/${data.id}/edit`)"
              class="tw-mr-2"
            />
            <Button
              icon="pi pi-eye"
              size="small"
              severity="info"
              @click="$router.push(`/coffre/transactions/${data.id}`)"
            />
          </template>
        </Column>
      </DataTable>
    </Dialog>

    <!-- Transactions Dialog -->
    <Dialog
      v-model:visible="showTransactionsDialog"
      modal
      header="Recent Transactions"
      :style="{ width: '1000px' }"
      class="p-fluid"
    >
      <DataTable
        :value="recentTransactions"
        :loading="loading"
        class="p-datatable-sm"
        stripedRows
      >
        <Column field="reference" header="Reference" sortable />
        <Column field="coffre.name" header="Coffre" sortable />
        <Column field="amount" header="Amount" sortable>
          <template #body="{ data }">
            {{ formatCurrency(data.amount) }}
          </template>
        </Column>
        <Column field="transaction_type" header="Type" sortable>
          <template #body="{ data }">
            <Tag :value="getTransactionTypeLabel(data.transaction_type)" :severity="getStatusColor(data.transaction_type)" />
          </template>
        </Column>
        <Column field="created_at" header="Date" sortable>
          <template #body="{ data }">
            {{ formatDate(data.created_at) }}
          </template>
        </Column>
        <Column header="Actions" style="min-width: 100px;">
          <template #body="{ data }">
            <Button
              icon="pi pi-eye"
              size="small"
              @click="$router.push(`/coffre/transactions/${data.id}`)"
            />
          </template>
        </Column>
      </DataTable>
    </Dialog>
  </div>
</template>

<style scoped>
/* Enhanced styling for the dashboard */
:deep(.p-card) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

:deep(.p-button) {
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.2s ease;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  font-weight: 600;
  border-bottom: 2px solid #e2e8f0;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8fafc;
}

:deep(.p-tag) {
  font-weight: 600;
  border-radius: 6px;
}

:deep(.p-progressbar) {
  border-radius: 6px;
}

:deep(.p-dialog .p-dialog-header) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px 12px 0 0;
}

:deep(.p-dialog .p-dialog-content) {
  padding: 1.5rem;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .tw-grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .tw-text-3xl {
    font-size: 1.875rem;
  }
}
</style>
