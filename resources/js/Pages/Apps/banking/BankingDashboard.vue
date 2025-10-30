<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Chart from 'chart.js/auto';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ProgressSpinner from 'primevue/progressspinner';
// Services and Components
import { dashboardService } from '../../../Components/Apps/services/bank/DashboardService';
import TransactionDetailModal from '../../../Components/Apps/banking/transaction/TransactionDetailModal.vue';


// Composables
const router = useRouter();
const toast = useToast();

// Reactive state
const loading = ref(false);
const exporting = ref(false);
const dateRange = ref([new Date(new Date().setDate(new Date().getDate() - 30)), new Date()]);
const balanceChartPeriod = ref('30d');
const transactionViewType = ref('amount');
const showTransactionDetail = ref(false);
const selectedTransaction = ref(null);

// Charts
const balanceChart = ref(null);
const transactionChart = ref(null);
const accountDistributionChart = ref(null);
let balanceChartInstance = null;
let transactionChartInstance = null;
let distributionChartInstance = null;

// Dashboard data
const dashboardData = ref({
  totalBalance: 0,
  balanceChange: 0,
  activeAccounts: 0,
  totalAccounts: 0,
  monthlyTransactions: 0,
  transactionTrend: 0,
  activeBanks: 0,
  totalBanks: 0,
  totalTransactions: 0,
  balanceByCurrency: [],
  accountsByBank: [],
  recentTransactions: [],
  accountSummaries: [],
  transactionsByStatus: [],
  balanceHistory: [],
  transactionTrends: []
});

const systemHealth = ref({
  status: 'Healthy',
  activeConnections: 0,
  lastSync: new Date(),
  errorRate: 0
});

// Options
const periodOptions = [
  { label: '7 Days', value: '7d' },
  { label: '30 Days', value: '30d' },
  { label: '90 Days', value: '90d' },
  { label: '1 Year', value: '1y' }
];

const chartColors = [
  '#6366f1', '#8b5cf6', '#ec4899', '#ef4444', '#f59e0b',
  '#10b981', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899'
];

// Methods
const loadDashboardData = async () => {
  loading.value = true;
  try {
    const params = {
      date_from: formatDateForAPI(dateRange.value[0]),
      date_to: formatDateForAPI(dateRange.value[1])
    };

    const result = await dashboardService.getDashboardData(params);
    if (result.success) {
      dashboardData.value = result.data;
      updateCharts();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'Failed to load dashboard data');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadSystemHealth = async () => {
  try {
    const result = await dashboardService.getSystemHealth();
    if (result.success) {
      systemHealth.value = result.data;
    }
  } catch (error) {
    console.error('Failed to load system health:', error);
  }
};

const refreshDashboard = async () => {
  await Promise.all([
    loadDashboardData(),
    loadSystemHealth()
  ]);
  showToast('success', 'Refreshed', 'Dashboard data updated successfully');
};

const exportDashboard = async () => {
  exporting.value = true;
  try {
    const result = await dashboardService.exportDashboard({
      date_from: formatDateForAPI(dateRange.value[0]),
      date_to: formatDateForAPI(dateRange.value[1])
    });
    
    if (result.success) {
      showToast('success', 'Export', 'Dashboard report exported successfully');
      // Handle file download
    }
  } catch (error) {
    showToast('error', 'Export Failed', 'Could not export dashboard report');
  } finally {
    exporting.value = false;
  }
};

// Chart Methods
const initializeCharts = () => {
  // Balance Chart
  if (balanceChart.value) {
    const ctx = balanceChart.value.getContext('2d');
    balanceChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: [],
        datasets: [{
          label: 'Balance',
          data: [],
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99, 102, 241, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: false,
            ticks: {
              callback: (value) => formatCurrency(value)
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
  }

  // Transaction Chart
  if (transactionChart.value) {
    const ctx = transactionChart.value.getContext('2d');
    transactionChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [],
        datasets: [
          {
            label: 'Credits',
            data: [],
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderColor: '#10b981',
            borderWidth: 1
          },
          {
            label: 'Debits',
            data: [],
            backgroundColor: 'rgba(239, 68, 68, 0.8)',
            borderColor: '#ef4444',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (value) => transactionViewType.value === 'amount' ? formatCurrency(value) : value
            }
          }
        }
      }
    });
  }

  // Account Distribution Chart
  if (accountDistributionChart.value) {
    const ctx = accountDistributionChart.value.getContext('2d');
    distributionChartInstance = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: [],
        datasets: [{
          data: [],
          backgroundColor: chartColors,
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
  }
};

const updateCharts = () => {
  if (balanceChartInstance && dashboardData.value.balanceHistory) {
    balanceChartInstance.data.labels = dashboardData.value.balanceHistory.map(item =>
      formatDate(item.date)
    );
    balanceChartInstance.data.datasets[0].data = dashboardData.value.balanceHistory.map(item =>
      item.balance
    );
    balanceChartInstance.update();
  }

  if (transactionChartInstance && dashboardData.value.transactionTrends) {
    transactionChartInstance.data.labels = dashboardData.value.transactionTrends.map(item =>
      formatDate(item.date)
    );
    transactionChartInstance.data.datasets[0].data = dashboardData.value.transactionTrends.map(item =>
      transactionViewType.value === 'amount' ? item.credits : item.credit_count
    );
    transactionChartInstance.data.datasets[1].data = dashboardData.value.transactionTrends.map(item =>
      transactionViewType.value === 'amount' ? item.debits : item.debit_count
    );
    transactionChartInstance.update();
  }

  if (distributionChartInstance && dashboardData.value.accountsByBank) {
    distributionChartInstance.data.labels = dashboardData.value.accountsByBank.map(bank => bank.name);
    distributionChartInstance.data.datasets[0].data = dashboardData.value.accountsByBank.map(bank => bank.accounts);
    distributionChartInstance.update();
  }
};

const updateBalanceChart = () => {
  loadDashboardData();
};

const toggleTransactionView = () => {
  transactionViewType.value = transactionViewType.value === 'amount' ? 'count' : 'amount';
  updateCharts();
};

// Navigation Methods
const navigateToAccounts = () => {
  router.push({ name: 'bank.accounts' });
};

const navigateToTransactions = () => {
  router.push({ name: 'AllTransactionsList' });
};

const navigateToBanks = () => {
  router.push({ name: 'BankList' });
};

const navigateToAccountTransactions = (account) => {
  router.push({
    name: 'BankAccountTransactionList',
    query: {
      bank_account_id: account.id,
      account_name: account.account_name,
      bank_name: account.bank_name
    }
  });
};

const viewTransaction = (transaction) => {
  selectedTransaction.value = transaction;
  showTransactionDetail.value = true;
};

const closeTransactionDetail = () => {
  showTransactionDetail.value = false;
  selectedTransaction.value = null;
};

// Utility Methods
const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};

const formatCurrency = (amount, currency = 'DZD') => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2
  }).format(amount || 0);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit'
  }).format(new Date(dateString));
};

const formatRelativeDate = (dateString) => {
  if (!dateString) return 'Never';
  const date = new Date(dateString);
  const now = new Date();
  const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));
  
  if (diffInHours < 1) return 'Just now';
  if (diffInHours < 24) return `${diffInHours}h ago`;
  if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`;
  return date.toLocaleDateString();
};

const formatDateForAPI = (date) => {
  if (!date) return null;
  return date.toISOString().split('T')[0];
};

const getTransactionTrendClass = () => {
  return dashboardData.value.transactionTrend >= 0 ? 'tw-text-green-500' : 'tw-text-red-500';
};

const getTransactionTrendIcon = () => {
  return dashboardData.value.transactionTrend >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down';
};

const getTransactionIconClass = (type) => {
  return type === 'credit' ? 'tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500' : 'tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500';
};

const getAmountClass = (type) => {
  return type === 'credit' ? 'tw-text-green-500' : 'tw-text-red-500';
};

const getBalanceClass = (balance) => {
  if (balance > 0) return 'tw-text-green-600';
  if (balance < 0) return 'tw-text-red-600';
  return 'tw-text-gray-600';
};

const getStatusSeverity = (status) => {
  const severities = {
    'pending': 'warning',
    'completed': 'success',
    'cancelled': 'danger',
    'reconciled': 'info'
  };
  return severities[status] || 'secondary';
};

const getHealthIndicatorClass = () => {
  switch (systemHealth.value.status?.toLowerCase()) {
    case 'healthy': return 'tw-text-green-500';
    case 'warning': return 'tw-text-amber-500';
    case 'error': return 'tw-text-red-500';
    default: return 'tw-text-gray-500';
  }
};

const getChartColor = (index) => {
  return chartColors[index % chartColors.length];
};

const capitalizeFirst = (string) => {
  return string.charAt(0).toUpperCase() + string.slice(1);
};

// Lifecycle
onMounted(async () => {
  await refreshDashboard();
  setTimeout(() => {
    initializeCharts();
    updateCharts();
  }, 100);
  
  const refreshInterval = setInterval(refreshDashboard, 5 * 60 * 1000);
  
  onUnmounted(() => {
    clearInterval(refreshInterval);
    if (balanceChartInstance) balanceChartInstance.destroy();
    if (transactionChartInstance) transactionChartInstance.destroy();
    if (distributionChartInstance) distributionChartInstance.destroy();
  });
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-violet-700 tw-py-12 tw-rounded-b-[30px]">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-flex-col tw-items-center md:tw-items-start">
            <h1 class="tw-text-white tw-text-4xl tw-font-bold tw-flex tw-items-center tw-gap-4">
              <i class="pi pi-chart-pie tw-text-5xl tw-opacity-90"></i>
              <span class="tw-block tw-leading-none">Banking Dashboard</span>
            </h1>
            <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
              Comprehensive overview of your banking operations
            </p>
          </div>
          <div class="tw-flex tw-gap-4 tw-items-center tw-flex-wrap md:tw-flex-nowrap">
            <Calendar
              v-model="dateRange"
              selection-mode="range"
              :manual-input="false"
              placeholder="Select date range"
              @date-select="refreshDashboard"
              showIcon
              class="tw-w-full md:tw-min-w-[250px]"
            />
            <Button
              icon="pi pi-refresh"
              label="Refresh"
              class="p-button-outlined"
              @click="refreshDashboard"
              :loading="loading"
            />
            <Button
              icon="pi pi-download"
              label="Export Report"
              class="p-button-info"
              @click="exportDashboard"
              :loading="exporting"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <div class="tw-mb-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-700">
                  <i class="pi pi-wallet"></i>
                </div>
                <div>
                  <div class="tw-text-gray-600 tw-text-sm">Total Balance</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-blue-600">
                    {{ formatCurrency(dashboardData.totalBalance) }}
                  </div>
                  <div v-if="dashboardData.balanceChange" class="tw-text-xs tw-flex tw-items-center tw-gap-1" :class="dashboardData.balanceChange > 0 ? 'tw-text-green-500' : 'tw-text-red-500'">
                    <i :class="dashboardData.balanceChange > 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"></i>
                    <span>{{ dashboardData.balanceChange > 0 ? '+' : '' }}{{ formatCurrency(dashboardData.balanceChange) }} this month</span>
                  </div>
                </div>
              </div>
            </template>
          </Card>
          
          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
                  <i class="pi pi-credit-card"></i>
                </div>
                <div>
                  <div class="tw-text-gray-600 tw-text-sm">Active Accounts</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-green-600">
                    {{ dashboardData.activeAccounts }}
                  </div>
                  <div class="tw-text-xs tw-text-gray-500">
                    {{ dashboardData.totalAccounts }} total accounts
                  </div>
                </div>
              </div>
            </template>
          </Card>
          
          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-violet-500 tw-to-purple-500">
                  <i class="pi pi-arrow-right-arrow-left"></i>
                </div>
                <div>
                  <div class="tw-text-gray-600 tw-text-sm">Monthly Transactions</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-purple-600">
                    {{ dashboardData.monthlyTransactions }}
                  </div>
                  <div class="tw-text-xs tw-flex tw-items-center tw-gap-1" :class="getTransactionTrendClass()">
                    <i :class="getTransactionTrendIcon()"></i>
                    <span>{{ dashboardData.transactionTrend }}% vs last month</span>
                  </div>
                </div>
              </div>
            </template>
          </Card>
          
          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-amber-500 tw-to-orange-500">
                  <i class="pi pi-building"></i>
                </div>
                <div>
                  <div class="tw-text-gray-600 tw-text-sm">Connected Banks</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-orange-600">
                    {{ dashboardData.activeBanks }}
                  </div>
                  <div class="tw-text-xs tw-text-gray-500">
                    {{ dashboardData.totalBanks }} total banks
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-12 tw-gap-6">
        <Card class="tw-col-span-full lg:tw-col-span-8 tw-rounded-xl tw-shadow-md">
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-6">
              <h3 class="tw-font-bold tw-text-gray-900 tw-text-xl">Balance Overview</h3>
              <Dropdown
                v-model="balanceChartPeriod"
                :options="periodOptions"
                option-label="label"
                option-value="value"
                @change="updateBalanceChart"
                class="tw-min-w-[100px]"
              />
            </div>
          </template>
          <template #content>
            <div class="tw-p-6">
              <div class="tw-relative tw-h-[300px] tw-mb-4">
                <canvas ref="balanceChart"></canvas>
              </div>
              <div class="tw-flex tw-justify-around tw-pt-4 tw-border-t tw-border-gray-200">
                <div v-for="currency in dashboardData.balanceByCurrency" :key="currency.code" class="tw-text-center">
                  <div class="tw-font-semibold tw-text-gray-600 tw-text-sm">{{ currency.code }}</div>
                  <div class="tw-font-bold tw-text-gray-900 tw-text-lg">{{ formatCurrency(currency.amount, currency.code) }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ currency.accounts }} accounts</div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-col-span-full lg:tw-col-span-8 tw-rounded-xl tw-shadow-md">
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-6">
              <h3 class="tw-font-bold tw-text-gray-900 tw-text-xl">Transaction Trends</h3>
              <Button
                :label="transactionViewType === 'amount' ? 'Amount' : 'Count'"
                :icon="transactionViewType === 'amount' ? 'pi pi-dollar' : 'pi pi-list'"
                class="p-button-outlined p-button-sm"
                @click="toggleTransactionView"
              />
            </div>
          </template>
          <template #content>
            <div class="tw-p-6">
              <div class="tw-relative tw-h-[300px]">
                <canvas ref="transactionChart"></canvas>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-col-span-full lg:tw-col-span-4 tw-rounded-xl tw-shadow-md">
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-6">
              <h3 class="tw-font-bold tw-text-gray-900 tw-text-xl">Account Distribution</h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-6">
              <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-6 tw-items-center">
                <div class="tw-relative tw-h-[200px] tw-w-full lg:tw-w-1/2">
                  <canvas ref="accountDistributionChart"></canvas>
                </div>
                <div class="tw-flex-1 tw-flex tw-flex-col tw-gap-2">
                  <div v-for="(bank, index) in dashboardData.accountsByBank" :key="bank.name" class="tw-flex tw-items-center tw-gap-3">
                    <div class="tw-w-3 tw-h-3 tw-rounded-sm tw-flex-shrink-0" :style="{ backgroundColor: getChartColor(index) }"></div>
                    <div>
                      <div class="tw-font-medium tw-text-gray-900">{{ bank.name }}</div>
                      <div class="tw-text-xs tw-text-gray-500">{{ bank.accounts }} accounts</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-col-span-full lg:tw-col-span-6 tw-rounded-xl tw-shadow-md">
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-6">
              <h3 class="tw-font-bold tw-text-gray-900 tw-text-xl">Recent Transactions</h3>
              <Button label="View All" icon="pi pi-external-link" class="p-button-text p-button-sm" @click="navigateToTransactions" />
            </div>
          </template>
          <template #content>
            <div class="tw-p-6 tw-max-h-[400px] tw-overflow-y-auto tw-flex tw-flex-col tw-gap-3">
              <div v-for="transaction in dashboardData.recentTransactions" :key="transaction.id" class="tw-flex tw-items-center tw-gap-4 tw-p-3 tw-rounded-lg tw-bg-gray-50 hover:tw-bg-gray-100 tw-transition-colors tw-duration-200 tw-cursor-pointer" @click="viewTransaction(transaction)">
                <div class="tw-w-10 tw-h-10 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-text-lg" :class="getTransactionIconClass(transaction.transaction_type)">
                  <i :class="transaction.transaction_type === 'credit' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"></i>
                </div>
                <div class="tw-flex-1 tw-flex tw-flex-col tw-gap-1">
                  <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ transaction.bank_account?.account_name }}</div>
                  <div class="tw-text-gray-500 tw-text-xs">{{ transaction.bank_account?.bank_name }} • {{ formatRelativeDate(transaction.transaction_date) }}</div>
                </div>
                <div class="tw-font-bold tw-text-lg" :class="getAmountClass(transaction.transaction_type)">
                  <span class="tw-font-normal tw-mr-1">{{ transaction.transaction_type === 'credit' ? '+' : '-' }}</span>{{ formatCurrency(transaction.amount, transaction.bank_account?.currency) }}
                </div>
                <Tag :value="transaction.status_text" :severity="getStatusSeverity(transaction.status)" class="tw-text-xs" />
              </div>
            </div>
          </template>
        </Card>

        <div class="tw-col-span-full lg:tw-col-span-6 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <Card class="tw-rounded-xl tw-shadow-md">
            <template #header>
              <div class="tw-flex tw-justify-between tw-items-center tw-p-6">
                <h3 class="tw-font-bold tw-text-gray-900 tw-text-xl">Quick Actions</h3>
              </div>
            </template>
            <template #content>
              <div class="tw-p-6 tw-grid tw-grid-cols-1 tw-gap-3">
                <button class="tw-w-full tw-flex tw-items-center tw-gap-4 tw-p-4 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-bg-white hover:tw-border-indigo-500 tw-transition-colors tw-duration-200 tw-text-left" @click="navigateToAccounts">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-lg tw-bg-indigo-500">
                    <i class="pi pi-credit-card"></i>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-gray-900">Manage Accounts</div>
                    <div class="tw-text-gray-600 tw-text-sm">View and manage bank accounts</div>
                  </div>
                </button>
                <button class="tw-w-full tw-flex tw-items-center tw-gap-4 tw-p-4 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-bg-white hover:tw-border-indigo-500 tw-transition-colors tw-duration-200 tw-text-left" @click="navigateToTransactions">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-lg tw-bg-violet-500">
                    <i class="pi pi-list"></i>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-gray-900">View Transactions</div>
                    <div class="tw-text-gray-600 tw-text-sm">Browse all transactions</div>
                  </div>
                </button>
                <button class="tw-w-full tw-flex tw-items-center tw-gap-4 tw-p-4 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-bg-white hover:tw-border-indigo-500 tw-transition-colors tw-duration-200 tw-text-left" @click="navigateToBanks">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-lg tw-bg-fuchsia-500">
                    <i class="pi pi-building"></i>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-gray-900">Manage Banks</div>
                    <div class="tw-text-gray-600 tw-text-sm">Configure bank settings</div>
                  </div>
                </button>
                <button class="tw-w-full tw-flex tw-items-center tw-gap-4 tw-p-4 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-bg-white hover:tw-border-indigo-500 tw-transition-colors tw-duration-200 tw-text-left" @click="exportDashboard">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-lg tw-bg-green-500">
                    <i class="pi pi-download"></i>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-gray-900">Export Data</div>
                    <div class="tw-text-gray-600 tw-text-sm">Download reports</div>
                  </div>
                </button>
              </div>
            </template>
          </Card>
          <Card class="tw-rounded-xl tw-shadow-md">
            <template #header>
              <div class="tw-flex tw-justify-between tw-items-center tw-p-6">
                <h3 class="tw-font-bold tw-text-gray-900 tw-text-xl">System Health</h3>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-circle-fill" :class="getHealthIndicatorClass()"></i>
                  <span class="tw-font-medium tw-text-sm tw-text-gray-700">{{ systemHealth.status }}</span>
                </div>
              </div>
            </template>
            <template #content>
              <div class="tw-p-6 tw-flex tw-flex-col tw-gap-4">
                <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-border-gray-200 tw-pb-2">
                  <div class="tw-text-gray-600 tw-text-sm">Active Connections</div>
                  <div class="tw-font-bold tw-text-gray-900">{{ systemHealth.activeConnections }}</div>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-border-gray-200 tw-pb-2">
                  <div class="tw-text-gray-600 tw-text-sm">Last Sync</div>
                  <div class="tw-font-bold tw-text-gray-900">{{ formatRelativeDate(systemHealth.lastSync) }}</div>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center">
                  <div class="tw-text-gray-600 tw-text-sm">Error Rate</div>
                  <div class="tw-font-bold" :class="systemHealth.errorRate > 5 ? 'tw-text-red-500' : 'tw-text-green-500'">
                    {{ systemHealth.errorRate }}%
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>

    <TransactionDetailModal
      v-if="showTransactionDetail"
      :transaction="selectedTransaction"
      @close="closeTransactionDetail"
    />

    <Toast />
  </div>
</template>

<style scoped>
/* Scoped CSS overrides for PrimeVue components, using `@apply` for Tailwind classes */
:deep(.p-card-header) {
  @apply p-0;
}
:deep(.p-card-content) {
  @apply p-0;
}
:deep(.p-calendar) {
  @apply w-full;
}
</style>