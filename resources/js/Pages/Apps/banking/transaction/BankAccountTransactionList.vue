<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useRoute, useRouter } from 'vue-router';
import { debounce } from 'lodash-es';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';

// Services
import { bankAccountTransactionService } from '../../../../Components/Apps/services/bank/BankAccountTransactionService';
import { bankAccountService } from '../../../../Components/Apps/services/bank/BankAccountService';

// Composables
const toast = useToast();
const route = useRoute();
const router = useRouter();

// Reactive state
const transactions = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const summary = ref(null);

// New reactive state for account filtering
const selectedBankAccountId = ref(null);
const accountInfo = ref(null);

// Filter states
const filters = ref({
  bank_account_id: null,
  transaction_type: '',
  status: ''
});

// Options
const bankAccountOptions = ref([]);

const typeOptions = [
  { label: 'Credit', value: 'credit' },
  { label: 'Debit', value: 'debit' }
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
  { label: '50 per page', value: 50 }
];

// Computed properties for dynamic page title
const pageTitle = computed(() => {
  if (selectedBankAccountId.value && accountInfo.value) {
    return `Transactions - ${accountInfo.value.account_name}`;
  }
  return 'Bank Account Transactions';
});

const pageSubtitle = computed(() => {
  if (selectedBankAccountId.value && accountInfo.value) {
    return `View transactions for ${accountInfo.value.bank_name}`;
  }
  return 'View all bank account transactions';
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

  if (selectedBankAccountId.value) {
    params.bank_account_id = selectedBankAccountId.value;
  }

  try {
    const result = await bankAccountTransactionService.getAll(params);
    if (result.success) {
      transactions.value = result.data;
      meta.value = result.meta;
      summary.value = result.summary;
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

// Load specific bank account info when filtering
const loadAccountInfo = async (accountId) => {
  try {
    const result = await bankAccountService.getById(accountId);
    if (result.success) {
      accountInfo.value = result.data;
    }
  } catch (error) {
    console.error('Error loading account info:', error);
  }
};

// Navigation methods
const navigateBack = () => {
  router.push({ name: 'bank.accounts' });
};

// Initialize from route parameters
const initializeFromRoute = () => {
  if (route.query.bank_account_id) {
    selectedBankAccountId.value = parseInt(route.query.bank_account_id);
    
    if (route.query.account_name && route.query.bank_name) {
      accountInfo.value = {
        account_name: route.query.account_name,
        bank_name: route.query.bank_name,
        masked_account_number: route.query.account_number || 'N/A'
      };
    }
    
    loadAccountInfo(selectedBankAccountId.value);
  } else {
    selectedBankAccountId.value = null;
    accountInfo.value = null;
  }
};

const debouncedSearch = debounce(() => fetchTransactions(), 400);

const onPageChange = (event) => {
  fetchTransactions(event.page + 1);
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

const getAmountClass = (type) => {
  return type === 'credit' ? 'tw-text-green-500' : 'tw-text-red-500';
};

const getBalanceClass = (balance) => {
  if (balance > 0) return 'tw-text-green-600';
  if (balance < 0) return 'tw-text-red-600';
  return 'tw-text-gray-600';
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

const truncateText = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

// Watch for route changes
watch(() => route.query, () => {
  initializeFromRoute();
  fetchTransactions();
}, { immediate: false });

// Lifecycle
onMounted(() => {
  initializeFromRoute();
  fetchTransactions();
  if (!selectedBankAccountId.value) {
    loadBankAccounts();
  }
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-items-center tw-gap-4 tw-text-white">
            <i class="pi pi-list tw-text-5xl tw-opacity-90"></i>
            <div class="tw-flex tw-flex-col">
              <span class="tw-text-4xl tw-font-bold tw-leading-none">{{ pageTitle }}</span>
              <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
                {{ pageSubtitle }}
              </p>
            </div>
          </div>
          <div class="tw-flex tw-gap-2 tw-items-center tw-flex-wrap md:tw-flex-nowrap">
            <Button
              v-if="selectedBankAccountId"
              icon="pi pi-arrow-left"
              label="Back to Accounts"
              class="p-button-secondary p-button-lg"
              @click="navigateBack"
            />
          </div>
        </div>
      </div>
    </div>

    <div v-if="selectedBankAccountId && accountInfo" class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-4">
      <Card class="tw-rounded-xl tw-shadow-md tw-bg-gradient-to-r tw-from-gray-100 tw-to-gray-200 tw-p-6">
        <template #content>
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400">
                <i class="pi pi-credit-card"></i>
              </div>
              <div class="tw-text-center md:tw-text-left">
                <h5 class="tw-text-xl tw-font-semibold tw-text-gray-900 tw-m-0">{{ accountInfo.account_name }}</h5>
                <p class="tw-text-gray-600 tw-mt-1 tw-m-0">{{ accountInfo.bank_name }} • {{ accountInfo.masked_account_number }}</p>
                <div class="tw-mt-2 tw-text-sm tw-text-gray-700 tw-font-semibold">
                  Current Balance:
                  <span :class="getBalanceClass(accountInfo.current_balance)">
                    {{ formatCurrency(accountInfo.current_balance, accountInfo.currency) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="tw-flex tw-gap-2 tw-items-center">
              <Tag v-if="accountInfo.currency" :value="accountInfo.currency" severity="info" class="tw-text-xs" />
              <Tag 
                v-if="accountInfo.is_active !== undefined"
                :value="accountInfo.is_active ? 'Active' : 'Inactive'"
                :severity="accountInfo.is_active ? 'success' : 'danger'"
                class="tw-text-xs"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-4">
      <div v-if="summary" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400">
                <i class="pi pi-list"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Transactions</div>
                <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ summary.pending_count + summary.completed_count + summary.reconciled_count }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
                <i class="pi pi-arrow-up"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Credits</div>
                <div class="tw-text-2xl tw-font-bold tw-text-green-500">{{ formatCurrency(summary.credit_amount) }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
                <i class="pi pi-arrow-down"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Debits</div>
                <div class="tw-text-2xl tw-font-bold tw-text-red-500">{{ formatCurrency(summary.debit_amount) }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500">
                <i class="pi pi-check-circle"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Completed</div>
                <div class="tw-text-2xl tw-font-bold tw-text-blue-500">{{ summary.completed_count }}</div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <Card class="tw-mb-8 tw-border-none tw-rounded-xl tw-shadow-md">
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6 tw-items-center">
            <div class="tw-col-span-1 lg:tw-col-span-2">
              <div class="p-inputgroup">
                <span class="p-inputgroup-addon tw-rounded-l-lg">
                  <i class="pi pi-search"></i>
                </span>
                <InputText
                  v-model="searchQuery"
                  placeholder="Search by reference or description..."
                  @input="debouncedSearch"
                  class="tw-w-full"
                />
              </div>
            </div>
            <div class="tw-col-span-1 tw-flex tw-flex-wrap tw-gap-4 tw-items-center tw-justify-end">
              <Dropdown
                v-if="!selectedBankAccountId"
                v-model="filters.bank_account_id"
                :options="bankAccountOptions"
                option-label="label"
                option-value="value"
                placeholder="All Accounts"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
              <Dropdown
                v-model="filters.transaction_type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                placeholder="All Types"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
            </div>
          </div>
          <div class="tw-flex tw-justify-end tw-mt-4 tw-gap-2">
            <Dropdown
              v-model="perPage"
              :options="perPageOptions"
              option-label="label"
              option-value="value"
              @change="fetchTransactions"
              class="tw-min-w-[150px]"
            />
            <Button
              icon="pi pi-refresh"
              class="p-button-outlined"
              @click="fetchTransactions"
              :loading="loading"
              v-tooltip.top="'Refresh'"
            />
          </div>
        </template>
      </Card>

      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
        <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
      </div>

      <div v-else-if="transactions.length > 0">
        <DataTable
          :value="transactions"
          :rows="perPage"
          responsive-layout="scroll"
          class="tw-rounded-xl tw-overflow-hidden tw-shadow-md"
          :row-hover="true"
        >
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-4">
              <h3 class="tw-text-gray-900 tw-font-bold tw-text-xl">{{ selectedBankAccountId ? 'Account Transactions' : 'Transaction History' }}</h3>
            </div>
          </template>

          <Column field="reference" header="Reference" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-font-mono tw-font-semibold tw-text-indigo-600">{{ data.reference }}</div>
            </template>
          </Column>

          <Column v-if="!selectedBankAccountId" field="bank_account" header="Account" :sortable="false" style="width: 20%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-bold tw-text-gray-900 tw-text-sm">{{ data.bank_account?.account_name || 'N/A' }}</div>
                <div class="tw-font-mono tw-text-gray-600 tw-text-xs">{{ data.bank_account?.account_number || 'N/A' }}</div>
                <div class="tw-font-normal tw-text-gray-400 tw-text-xs">{{ data.bank_account?.bank_name || 'Unknown Bank' }}</div>
              </div>
            </template>
          </Column>

          <Column field="transaction_type" header="Type" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <Tag
                :value="data.transaction_type_text"
                :severity="data.transaction_type === 'credit' ? 'success' : 'danger'"
                :icon="data.transaction_type === 'credit' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"
              />
            </template>
          </Column>

          <Column field="amount" header="Amount" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-font-bold tw-text-lg" :class="getAmountClass(data.transaction_type)">
                <span class="tw-font-normal">{{ data.transaction_type === 'credit' ? '+' : '-' }}</span>{{ formatCurrency(data.amount, data.bank_account?.currency) }}
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <Tag
                :value="data.status_text"
                :severity="data.status_color"
                :icon="getStatusIcon(data.status)"
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

          <Column field="accepted_by" header="Accepted By" :sortable="false" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-medium tw-text-gray-900 tw-text-sm">{{ data.accepted_by?.name || 'N/A' }}</div>
                <div v-if="data.accepted_by?.email" class="tw-text-gray-500 tw-text-xs">{{ data.accepted_by.email }}</div>
              </div>
            </template>
          </Column>

          <Column field="description" header="Description" :sortable="false" :style="selectedBankAccountId ? 'width: 20%' : 'width: 12%'">
            <template #body="{ data }">
              <div v-if="data.description" class="tw-text-gray-600 tw-text-sm tw-truncate" v-tooltip.top="data.description">
                {{ truncateText(data.description, 30) }}
              </div>
              <span v-else class="tw-text-gray-400 tw-italic tw-text-sm">No description</span>
            </template>
          </Column>

        </DataTable>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-12 tw-rounded-xl tw-bg-gray-50 tw-border-2 tw-border-dashed tw-border-gray-300">
        <i class="pi pi-list tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
        <h4 class="tw-text-2xl tw-text-gray-800 tw-font-semibold tw-mb-2">No transactions found</h4>
        <p class="tw-text-gray-500 tw-mb-8">
          {{ selectedBankAccountId ? 'This account has no transactions yet.' : 'There are no transactions to display.' }}
        </p>
        <Button 
          v-if="selectedBankAccountId" 
          icon="pi pi-arrow-left" 
          label="Back to Accounts" 
          class="p-button-secondary"
          @click="navigateBack" 
        />
      </div>

      <Paginator
        v-if="!loading && meta && meta.total > perPage"
        :rows="perPage"
        :total-records="meta.total"
        :rows-per-page-options="[10, 15, 25, 50]"
        @page="onPageChange"
        class="tw-mt-6"
      />
    </div>

    <Toast />
  </div>
</template>

<style scoped>
/* Scoped CSS overrides for PrimeVue components, using `@apply` for Tailwind classes */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply tw-bg-gray-50 tw-text-gray-700 tw-font-semibold tw-border-none tw-text-sm;
}
:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply tw-transition-colors tw-duration-200;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  @apply tw-bg-gray-100;
}
:deep(.p-card-header) {
  @apply tw-p-0;
}
:deep(.p-card-content) {
  @apply tw-p-0;
}
:deep(.p-paginator) {
  @apply tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm;
}
</style>