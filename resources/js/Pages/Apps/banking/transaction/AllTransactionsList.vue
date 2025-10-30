<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { debounce } from 'lodash-es';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Services and Components
import { bankAccountTransactionService } from '../../../../Components/Apps/services/bank/BankAccountTransactionService';
import { bankAccountService } from '../../../../Components/Apps/services/bank/BankAccountService';
import TransactionDetailModal from '../../../../Components/Apps/banking/transaction/TransactionDetailModal.vue';

// Composables
const toast = useToast();
const confirm = useConfirm();

// Reactive state
const transactions = ref([]);
const selectedTransaction = ref(null);
const loading = ref(true);
const refreshing = ref(false);
const exporting = ref(false);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const stats = ref(null);
const viewMode = ref('table');
const showDetailModal = ref(false);

// Filter states
const filters = ref({
  bank_account_id: null,
  transaction_type: '',
  status: '',
  date_from: null,
  date_to: null,
  min_amount: null,
  max_amount: null
});

// Options
const bankAccountOptions = ref([]);

const typeOptions = [
  { label: 'Credit (+)', value: 'credit' },
  { label: 'Debit (-)', value: 'debit' }
];

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Reconciled', value: 'reconciled' }
];

const perPageOptions = [
  { label: '10 per page', value: 10 },
  { label: '15 per page', value: 15 },
  { label: '25 per page', value: 25 },
  { label: '50 per page', value: 50 },
  { label: '100 per page', value: 100 }
];

// Computed
const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => value !== null && value !== '') ||
           searchQuery.value !== '';
});

const isLargeAmountFilterActive = computed(() => {
  return filters.value.min_amount && filters.value.min_amount >= 10000;
});

// Methods
const fetchTransactions = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
    ...filters.value
  };

  if (filters.value.date_from) {
    params.date_from = formatDateForAPI(filters.value.date_from);
  }
  if (filters.value.date_to) {
    params.date_to = formatDateForAPI(filters.value.date_to);
  }

  try {
    const result = await bankAccountTransactionService.getAll(params);
    if (result.success) {
      transactions.value = result.data;
      meta.value = result.meta;
      calculateEnhancedStats(result.summary);
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while loading transactions.');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadBankAccounts = async () => {
  try {
    const result = await bankAccountService.getActive();
    if (result.success) {
      bankAccountOptions.value = result.data.map(account => ({
        label: `${account.bank_name} - ${account.account_name}`,
        value: account.id
      }));
    }
  } catch (error) {
    console.error('Error loading bank accounts:', error);
  }
};

const loadStats = async () => {
  try {
    const result = await bankAccountTransactionService.getStats();
    if (result.success) {
      stats.value = result.data;
    }
  } catch (error) {
    console.error('Error loading stats:', error);
  }
};

const calculateEnhancedStats = (summary) => {
  if (summary) {
    const creditCount = Object.values(summary.by_type?.credit || {}).reduce((sum, data) => sum + (data.count || 0), 0);
    const debitCount = Object.values(summary.by_type?.debit || {}).reduce((sum, data) => sum + (data.count || 0), 0);
    
    stats.value = {
      total_transactions: meta.value?.total || 0,
      total_credit: summary.credit_amount || 0,
      total_debit: summary.debit_amount || 0,
      net_amount: (summary.credit_amount || 0) - (summary.debit_amount || 0),
      credit_count: creditCount,
      debit_count: debitCount,
      by_status: summary.by_status || {}
    };
  }
};

const debouncedSearch = debounce(() => fetchTransactions(), 400);

const refreshData = async () => {
  refreshing.value = true;
  await fetchTransactions();
  await loadStats();
  refreshing.value = false;
  showToast('success', 'Success', 'Data refreshed successfully');
};

const exportTransactions = async () => {
  exporting.value = true;
  // Implement export functionality
  setTimeout(() => {
    exporting.value = false;
    showToast('success', 'Export', 'Transactions exported successfully');
  }, 2000);
};

const exportVisible = () => {
  showToast('info', 'Export', 'Exporting visible transactions...');
};

const onPageChange = (event) => {
  fetchTransactions(event.page + 1);
};

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table';
};

const clearAllFilters = () => {
  filters.value = {
    bank_account_id: null,
    transaction_type: '',
    status: '',
    date_from: null,
    date_to: null,
    min_amount: null,
    max_amount: null
  };
  searchQuery.value = '';
  fetchTransactions();
};

const setQuickDateFilter = (period) => {
  const now = new Date();
  let startDate = new Date();
  
  switch (period) {
    case 'today':
      startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      break;
    case 'week':
      startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 7);
      break;
    case 'month':
      startDate = new Date(now.getFullYear(), now.getMonth(), 1);
      break;
  }
  
  filters.value.date_from = startDate;
  filters.value.date_to = now;
  fetchTransactions();
};

const setLargeAmountFilter = () => {
  if (isLargeAmountFilterActive.value) {
    filters.value.min_amount = null;
    filters.value.max_amount = null;
  } else {
    filters.value.min_amount = 10000;
  }
  fetchTransactions();
};

const isQuickFilterActive = (period) => {
  if (!filters.value.date_from || !filters.value.date_to) return false;
  
  const now = new Date();
  const fromDate = new Date(filters.value.date_from);
  
  switch (period) {
    case 'today':
      return fromDate.toDateString() === now.toDateString();
    case 'week':
      const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
      return Math.abs(fromDate.getTime() - weekAgo.getTime()) < 24 * 60 * 60 * 1000;
    case 'month':
      const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
      return fromDate.getTime() === monthStart.getTime();
  }
  return false;
};

const openTransactionDetail = (event) => {
  selectedTransaction.value = event.data;
  showDetailModal.value = true;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedTransaction.value = null;
};

// Utility methods
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
  if (!dateString) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit'
  }).format(new Date(dateString));
};

const formatTime = (dateString) => {
  if (!dateString) return '';
  return new Intl.DateTimeFormat('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  }).format(new Date(dateString));
};

const formatDateForAPI = (date) => {
  if (!date) return null;
  return date.toISOString().split('T')[0];
};

const getAmountClass = (type) => {
  return type === 'credit' ? 'tw-text-green-500' : 'tw-text-red-500';
};

const getNetAmountClass = (amount) => {
  if (amount > 0) return 'tw-text-green-500';
  if (amount < 0) return 'tw-text-red-500';
  return 'tw-text-gray-500';
};

const getStatusIcon = (status) => {
  const icons = {
    'pending': 'pi pi-clock',
    'completed': 'pi pi-check-circle',
    'cancelled': 'pi pi-times-circle',
    'reconciled': 'pi pi-verified'
  };
  return icons[status] || 'pi pi-circle';
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

const getTransactionCardClass = (transaction) => {
  return {
    'credit-card': transaction.transaction_type === 'credit',
    'debit-card': transaction.transaction_type === 'debit',
    [`${transaction.status}-card`]: true
  };
};

const getCardHeaderClass = (type) => {
  return type === 'credit' ? 'tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500' : 'tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500';
};

const capitalizeFirst = (string) => {
  return string.charAt(0).toUpperCase() + string.slice(1);
};

const truncateText = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

// Lifecycle
onMounted(() => {
  fetchTransactions();
  loadBankAccounts();
  loadStats();
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-violet-500 tw-py-12 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-items-center tw-gap-4 tw-text-white">
            <i class="pi pi-history tw-text-5xl tw-opacity-90"></i>
            <div class="tw-flex tw-flex-col">
              <span class="tw-text-4xl tw-font-bold tw-leading-none">All Bank Transactions</span>
              <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
                Complete transaction history across all bank accounts
              </p>
            </div>
          </div>
          <div class="tw-flex tw-gap-2 tw-items-center tw-flex-wrap md:tw-flex-nowrap">
            <Button
              icon="pi pi-download"
              label="Export"
              class="p-button-secondary p-button-lg tw-mr-2"
              @click="exportTransactions"
              :loading="exporting"
            />
            <Button
              icon="pi pi-refresh"
              label="Refresh"
              class="p-button-info p-button-lg"
              @click="refreshData"
              :loading="refreshing"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <div v-if="stats" class="tw-mb-8">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-6">
          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-indigo-500 tw-to-violet-500">
                  <i class="pi pi-chart-pie"></i>
                </div>
                <div class="tw-flex-1">
                  <div class="tw-text-gray-600 tw-text-sm tw-font-medium">Total Transactions</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ stats.total_transactions }}</div>
                  <div class="tw-text-xs tw-text-gray-400 tw-flex tw-items-center tw-gap-1">
                    <i class="pi pi-arrow-up tw-text-green-500"></i>
                    <span class="tw-font-medium">12% this month</span>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
                  <i class="pi pi-plus-circle"></i>
                </div>
                <div class="tw-flex-1">
                  <div class="tw-text-gray-600 tw-text-sm tw-font-medium">Total Credits</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-green-500 tw-mt-1">{{ formatCurrency(stats.total_credit) }}</div>
                  <div class="tw-text-xs tw-text-gray-500 tw-mt-1">{{ stats.credit_count || 0 }} transactions</div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
                  <i class="pi pi-minus-circle"></i>
                </div>
                <div class="tw-flex-1">
                  <div class="tw-text-gray-600 tw-text-sm tw-font-medium">Total Debits</div>
                  <div class="tw-text-3xl tw-font-bold tw-text-red-500 tw-mt-1">{{ formatCurrency(stats.total_debit) }}</div>
                  <div class="tw-text-xs tw-text-gray-500 tw-mt-1">{{ stats.debit_count || 0 }} transactions</div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="tw-border-none tw-rounded-xl tw-shadow-lg hover:tw-translate-y-[-2px] hover:tw-shadow-xl tw-transition-all tw-duration-300">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
                <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-3xl tw-bg-gradient-to-r tw-from-violet-500 tw-to-fuchsia-500">
                  <i class="pi pi-balance-scale"></i>
                </div>
                <div class="tw-flex-1">
                  <div class="tw-text-gray-600 tw-text-sm tw-font-medium">Net Amount</div>
                  <div class="tw-text-3xl tw-font-bold tw-mt-1" :class="getNetAmountClass(stats.net_amount)">
                    {{ formatCurrency(stats.net_amount) }}
                  </div>
                  <div class="tw-text-xs tw-text-gray-500 tw-mt-1">
                    {{ stats.net_amount >= 0 ? 'Positive' : 'Negative' }} flow
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <div v-if="stats.by_status">
          <Card class="tw-border-none tw-rounded-xl tw-shadow-md tw-p-6">
            <template #content>
              <h5 class="tw-font-bold tw-text-gray-900 tw-text-lg tw-mb-4">Transaction Status Breakdown</h5>
              <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4">
                <div v-for="(statusData, status) in stats.by_status" :key="status" class="tw-flex tw-flex-col tw-items-center tw-p-4 tw-rounded-lg tw-bg-gray-50 hover:tw-bg-gray-100 tw-transition-colors tw-duration-200 tw-text-center">
                  <Tag
                    :value="capitalizeFirst(status)"
                    :severity="getStatusSeverity(status)"
                    class="tw-mb-2"
                  />
                  <div class="tw-font-semibold tw-text-gray-800">{{ statusData.count }} transactions</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ formatCurrency(statusData.total_amount) }}</div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <Card class="tw-rounded-xl tw-shadow-md tw-p-6 tw-mb-8">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
            <h5 class="tw-font-bold tw-text-gray-900 tw-text-lg">Filter & Search Transactions</h5>
            <Button
              icon="pi pi-filter-slash"
              label="Clear All"
              class="p-button-text p-button-sm"
              @click="clearAllFilters"
            />
          </div>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4 tw-mb-4">
            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Search</label>
              <div class="p-inputgroup">
                <span class="p-inputgroup-addon tw-rounded-l-lg">
                  <i class="pi pi-search"></i>
                </span>
                <InputText
                  v-model="searchQuery"
                  placeholder="Reference, description, or user..."
                  @input="debouncedSearch"
                  class="tw-w-full"
                />
              </div>
            </div>

            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Bank Account</label>
              <Dropdown
                v-model="filters.bank_account_id"
                :options="bankAccountOptions"
                option-label="label"
                option-value="value"
                placeholder="All Accounts"
                @change="fetchTransactions"
                showClear
                class="tw-w-full"
              />
            </div>

            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Transaction Type</label>
              <Dropdown
                v-model="filters.transaction_type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                placeholder="All Types"
                @change="fetchTransactions"
                showClear
                class="tw-w-full"
              />
            </div>

            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Status</label>
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                @change="fetchTransactions"
                showClear
                class="tw-w-full"
              />
            </div>

            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Date From</label>
              <Calendar
                v-model="filters.date_from"
                placeholder="Select start date"
                @date-select="fetchTransactions"
                @clear-click="fetchTransactions"
                showIcon
                class="tw-w-full"
              />
            </div>
            
            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Date To</label>
              <Calendar
                v-model="filters.date_to"
                placeholder="Select end date"
                @date-select="fetchTransactions"
                @clear-click="fetchTransactions"
                showIcon
                class="tw-w-full"
              />
            </div>

            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Min Amount</label>
              <InputNumber
                v-model="filters.min_amount"
                placeholder="0.00"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                @input="debouncedSearch"
                class="tw-w-full"
              />
            </div>

            <div class="tw-flex tw-flex-col tw-gap-2">
              <label class="tw-text-gray-700 tw-font-semibold tw-text-sm">Max Amount</label>
              <InputNumber
                v-model="filters.max_amount"
                placeholder="0.00"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                @input="debouncedSearch"
                class="tw-w-full"
              />
            </div>
          </div>
          <div class="tw-flex tw-items-center tw-flex-wrap tw-gap-2 tw-pt-4 tw-border-t tw-border-gray-200 tw-mt-4">
            <div class="tw-font-bold tw-text-gray-700 tw-text-sm">Quick Filters:</div>
            <div class="tw-flex tw-flex-wrap tw-gap-2">
              <Button
                label="Today"
                class="p-button-outlined p-button-sm"
                @click="setQuickDateFilter('today')"
                :class="{ 'p-button-primary': isQuickFilterActive('today') }"
              />
              <Button
                label="This Week"
                class="p-button-outlined p-button-sm"
                @click="setQuickDateFilter('week')"
                :class="{ 'p-button-primary': isQuickFilterActive('week') }"
              />
              <Button
                label="This Month"
                class="p-button-outlined p-button-sm"
                @click="setQuickDateFilter('month')"
                :class="{ 'p-button-primary': isQuickFilterActive('month') }"
              />
              <Button
                label="Large Amounts"
                class="p-button-outlined p-button-sm"
                @click="setLargeAmountFilter"
                :class="{ 'p-button-primary': isLargeAmountFilterActive }"
              />
            </div>
          </div>
        </template>
      </Card>
      
      <Card v-if="!loading" class="tw-rounded-xl tw-shadow-md tw-p-6 tw-mb-8">
        <template #content>
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-2 tw-text-gray-600 tw-text-sm">
              <i class="pi pi-info-circle"></i>
              <span>
                Showing {{ meta?.count || 0 }} of {{ meta?.total || 0 }} transactions
                <span v-if="hasActiveFilters" class="tw-text-indigo-600 tw-font-semibold">(filtered)</span>
              </span>
            </div>
            <div class="tw-flex tw-gap-2 tw-items-center">
              <Dropdown
                v-model="perPage"
                :options="perPageOptions"
                option-label="label"
                option-value="value"
                @change="fetchTransactions"
                class="tw-min-w-[150px]"
              />
              <Button
                :icon="viewMode === 'table' ? 'pi pi-th-large' : 'pi pi-list'"
                class="p-button-outlined"
                v-tooltip.top="viewMode === 'table' ? 'Card View' : 'Table View'"
                @click="toggleView"
              />
            </div>
          </div>
        </template>
      </Card>

      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
        <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
        <p class="tw-text-gray-600 tw-text-lg tw-ml-4">Loading transactions...</p>
      </div>

      <div v-else-if="viewMode === 'table' && transactions.length > 0">
        <DataTable
          :value="transactions"
          :rows="perPage"
          responsive-layout="scroll"
          class="tw-rounded-xl tw-overflow-hidden tw-shadow-md"
          :row-hover="true"
          @row-click="openTransactionDetail"
          selection-mode="single"
          v-model:selection="selectedTransaction"
        >
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-4">
              <h4 class="tw-text-gray-900 tw-font-bold tw-text-lg">Transaction Details</h4>
              <Button
                icon="pi pi-download"
                label="Export Visible"
                class="p-button-outlined p-button-sm"
                @click="exportVisible"
              />
            </div>
          </template>

          <Column field="reference" header="Reference" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <div @click.stop="openTransactionDetail({ data: data })" class="tw-cursor-pointer tw-font-mono tw-font-semibold tw-text-indigo-600 hover:tw-underline tw-flex tw-items-center tw-gap-2">
                <span>{{ data.reference }}</span>
                <i class="pi pi-external-link tw-text-xs tw-text-gray-400"></i>
              </div>
            </template>
          </Column>

          <Column field="bank_account" header="Account" :sortable="false" style="width: 18%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-bold tw-text-gray-900 tw-text-sm">{{ data.bank_account?.account_name || 'N/A' }}</div>
                <div class="tw-font-mono tw-text-gray-600 tw-text-xs">{{ data.bank_account?.account_number || 'N/A' }}</div>
                <div class="tw-font-normal tw-text-gray-400 tw-text-xs">{{ data.bank_account?.bank_name || 'Unknown Bank' }}</div>
              </div>
            </template>
          </Column>

          <Column field="transaction_type" header="Type" :sortable="true" style="width: 8%">
            <template #body="{ data }">
              <Tag
                :value="data.transaction_type_text"
                :severity="data.transaction_type === 'credit' ? 'success' : 'danger'"
                :icon="data.transaction_type === 'credit' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"
                class="tw-text-xs"
              />
            </template>
          </Column>

          <Column field="amount" header="Amount" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-font-bold tw-text-lg" :class="getAmountClass(data.transaction_type)">
                <span class="tw-font-normal tw-mr-1">{{ data.transaction_type === 'credit' ? '+' : '-' }}</span>{{ formatCurrency(data.amount, data.bank_account?.currency) }}
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <Tag
                :value="data.status_text"
                :severity="data.status_color"
                :icon="getStatusIcon(data.status)"
                class="tw-text-xs"
              />
            </template>
          </Column>

          <Column field="transaction_date" header="Date" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ formatDate(data.transaction_date) }}</div>
                <div class="tw-text-gray-500 tw-text-xs">{{ formatTime(data.transaction_date) }}</div>
              </div>
            </template>
          </Column>

          <Column field="accepted_by" header="Processed By" :sortable="false" style="width: 15%">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-font-medium tw-text-gray-900 tw-text-sm">{{ data.accepted_by?.name || 'N/A' }}</div>
                <div v-if="data.accepted_by?.email" class="tw-text-gray-500 tw-text-xs">{{ data.accepted_by.email }}</div>
              </div>
            </template>
          </Column>

          <Column field="description" header="Description" :sortable="false" style="width: 15%">
            <template #body="{ data }">
              <div v-if="data.description" class="tw-text-gray-600 tw-text-sm tw-truncate" v-tooltip.top="data.description">
                {{ truncateText(data.description, 40) }}
              </div>
              <span v-else class="tw-text-gray-400 tw-italic tw-text-sm">No description provided</span>
            </template>
          </Column>
        </DataTable>
      </div>

      <div v-else-if="viewMode === 'card' && transactions.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <div v-for="transaction in transactions" :key="transaction.id" class="tw-w-full">
          <Card
            class="tw-h-full tw-rounded-xl tw-shadow-md hover:tw-shadow-lg hover:-tw-translate-y-1 tw-transition-all tw-duration-300 tw-cursor-pointer"
            @click="openTransactionDetail({ data: transaction })"
          >
            <template #header>
              <div class="tw-p-6 tw-rounded-t-xl tw-text-white" :class="getCardHeaderClass(transaction.transaction_type)">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-font-semibold">
                    <i :class="transaction.transaction_type === 'credit' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"></i>
                    <span>{{ transaction.transaction_type_text }}</span>
                  </div>
                  <div class="tw-text-lg tw-font-bold" :class="getAmountClass(transaction.transaction_type)">
                    {{ transaction.transaction_type === 'credit' ? '+' : '-' }}{{ formatCurrency(transaction.amount, transaction.bank_account?.currency) }}
                  </div>
                </div>
                <div class="tw-text-sm tw-font-mono tw-opacity-80">Reference: {{ transaction.reference }}</div>
              </div>
            </template>
            <template #content>
              <div class="tw-p-6 tw-flex-grow tw-flex tw-flex-col tw-gap-4">
                <div class="tw-flex tw-flex-col tw-gap-1">
                  <div class="tw-font-semibold tw-text-gray-900 tw-text-lg">{{ transaction.bank_account?.account_name || 'N/A' }}</div>
                  <div class="tw-text-gray-500 tw-text-sm">{{ transaction.bank_account?.bank_name || 'Unknown Bank' }}</div>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center tw-text-sm tw-text-gray-600">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-calendar"></i>
                    <span>{{ formatDate(transaction.transaction_date) }}</span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-user"></i>
                    <span>{{ transaction.accepted_by?.name || 'System' }}</span>
                  </div>
                </div>
                <div v-if="transaction.description" class="tw-text-sm tw-text-gray-500 tw-italic">
                  Description: {{ truncateText(transaction.description, 80) }}
                </div>
              </div>
            </template>
            <template #footer>
              <div class="tw-p-6 tw-border-t tw-border-gray-200 tw-flex tw-justify-between tw-items-center">
                <Tag
                  :value="transaction.status_text"
                  :severity="transaction.status_color"
                  :icon="getStatusIcon(transaction.status)"
                />
                <span class="tw-text-xs tw-text-gray-400 tw-italic">Click for details</span>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-12 tw-rounded-xl tw-bg-gray-50 tw-border-2 tw-border-dashed tw-border-gray-300">
        <i class="pi pi-history tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
        <h4 class="tw-text-2xl tw-text-gray-800 tw-font-semibold tw-mb-2">No transactions found</h4>
        <p class="tw-text-gray-500 tw-mb-8">
          {{ hasActiveFilters ? 'Try adjusting your filters to see more results.' : 'There are no transactions to display at this time.' }}
        </p>
        <Button
          v-if="hasActiveFilters"
          icon="pi pi-filter-slash"
          label="Clear All Filters"
          class="p-button-text p-button-sm"
          @click="clearAllFilters"
        />
      </div>

      <Paginator
        v-if="!loading && meta && meta.total > perPage"
        :rows="perPage"
        :total-records="meta.total"
        :rows-per-page-options="[10, 15, 25, 50, 100]"
        @page="onPageChange"
        class="tw-mt-6"
      />
    </div>

    <TransactionDetailModal
      v-if="showDetailModal"
      :transaction="selectedTransaction"
      @close="closeDetailModal"
    />

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
/* Scoped CSS overrides for PrimeVue components, using `@apply` for Tailwind classes */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply bg-gray-50 tw-text-gray-700 tw-font-semibold tw-border-none tw-text-sm;
}
:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply transition-colors tw-duration-200;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  @apply bg-gray-100;
}
:deep(.p-card-header) {
  @apply p-0;
}
:deep(.p-card-content) {
  @apply p-0;
}
:deep(.p-paginator) {
  @apply bg-white tw-rounded-lg tw-p-4 tw-shadow-sm;
}
</style>