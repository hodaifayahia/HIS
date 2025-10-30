<script setup>
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useRouter } from 'vue-router';
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
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import Avatar from 'primevue/avatar';

// Services and Components
import { bankAccountService } from '../../../../Components/Apps/services/bank/BankAccountService';
import BankAccountModal from '../../../../Components/Apps/banking/account/BankAccountModal.vue';
// import BalanceUpdateModal from './BalanceUpdateModal.vue';

// Composables
const toast = useToast();
const confirm = useConfirm();
const router = useRouter();

// Reactive state
const bankAccounts = ref([]);
const selectedAccounts = ref([]);
const loading = ref(true);
const syncing = ref(false);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const stats = ref(null);
const showModal = ref(false);
const showBalanceModal = ref(false);
const selectedBankAccount = ref(null);
const isEditing = ref(false);
const viewMode = ref('card');
const rowMenu = ref();
const cardMenu = ref();
const bulkMenu = ref();
const activeAccountForMenu = ref(null);

// Filter states
const filters = ref({
  bank_id: null,
  currency: '',
  is_active: ''
});

// Options
const bankOptions = ref([]);
const currencyOptions = ref([]);

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
];

const perPageOptions = [
  { label: '10 per page', value: 10 },
  { label: '15 per page', value: 15 },
  { label: '25 per page', value: 25 },
  { label: '50 per page', value: 50 }
];

// Menu items
const rowMenuItems = computed(() => [
  {
    label: activeAccountForMenu.value?.is_active ? 'Deactivate' : 'Activate',
    icon: activeAccountForMenu.value?.is_active ? 'pi pi-pause' : 'pi pi-play',
    command: () => toggleStatus(activeAccountForMenu.value)
  },
  { label: 'Edit', icon: 'pi pi-pencil', command: () => editBankAccount(activeAccountForMenu.value) },
  { separator: true },
  { label: 'Delete', icon: 'pi pi-trash', command: () => confirmDeleteBankAccount(activeAccountForMenu.value) }
]);

const cardMenuItems = computed(() => rowMenuItems.value);

const bulkMenuItems = [
  { label: 'Activate Selected', icon: 'pi pi-check', command: () => bulkToggleStatus(true) },
  { label: 'Deactivate Selected', icon: 'pi pi-pause', command: () => bulkToggleStatus(false) },
  { separator: true },
  { label: 'Delete Selected', icon: 'pi pi-trash', command: () => confirmBulkDelete() }
];

// Methods
const fetchBankAccounts = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
    ...filters.value
  };

  try {
    const result = await bankAccountService.getAll(params);
    if (result.success) {
      bankAccounts.value = result.data;
      meta.value = result.meta;
      if (result.summary) {
        stats.value = result.summary;
      }
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while loading bank accounts.');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadDropdownData = async () => {
  // Load banks
  const banksResult = await bankAccountService.getBanks();
  if (banksResult.success) {
    bankOptions.value = banksResult.data;
  }

  // Load currencies
  const currenciesResult = await bankAccountService.getCurrencies();
  if (currenciesResult.success) {
    currencyOptions.value = currenciesResult.data.map(currency => ({
      label: currency,
      value: currency
    }));
  }

  // Load stats
  const statsResult = await bankAccountService.getStats();
  if (statsResult.success) {
    stats.value = statsResult.data;
  }
};

const debouncedSearch = debounce(() => fetchBankAccounts(), 400);

const onPageChange = (event) => {
  fetchBankAccounts(event.page + 1);
};

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table';
};

const openCreateModal = () => {
  selectedBankAccount.value = null;
  isEditing.value = false;
  showModal.value = true;
};

const editBankAccount = (account) => {
  selectedBankAccount.value = { ...account };
  isEditing.value = true;
  showModal.value = true;
};

const viewBankAccount = (account) => {
  showToast('info', 'Account Information', `${account.bank?.name || 'Unknown Bank'} - ${account.account_name}`);
};

const navigateToTransactions = (account) => {
  if (!account || !account.id) return;
  
  router.push({
    name: 'bank.transactions',
    query: {
      bank_account_id: account.id,
      account_name: account.account_name,
      bank_name: account.bank?.name,
      account_number: account.masked_account_number,
      currency: account.currency
    }
  });
};

const openBalanceModal = (account) => {
  navigateToTransactions(account);
};

const toggleStatus = async (account) => {
  try {
    const result = await bankAccountService.toggleStatus(account.id);
    if (result.success) {
      showToast('success', 'Success', `Account ${result.data.is_active ? 'activated' : 'deactivated'} successfully`);
      fetchBankAccounts();
      loadDropdownData();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while updating status.');
  }
};

const confirmDeleteBankAccount = (account) => {
  confirm.require({
    message: `Are you sure you want to delete "${account.account_name}" from ${account.bank?.name || 'Unknown Bank'}? This action is irreversible.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteBankAccount(account);
    }
  });
};

const deleteBankAccount = async (account) => {
  try {
    const result = await bankAccountService.delete(account.id);
    if (result.success) {
      showToast('success', 'Success', 'Bank account deleted successfully');
      fetchBankAccounts();
      loadDropdownData();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during deletion.');
  }
};

const syncBalances = async () => {
  syncing.value = true;
  try {
    const result = await bankAccountService.syncBalances();
    if (result.success) {
      showToast('success', 'Success', result.message);
      fetchBankAccounts();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during balance sync.');
  } finally {
    syncing.value = false;
  }
};


const closeModal = () => {
  showModal.value = false;
  selectedBankAccount.value = null;
  isEditing.value = false;
};

const closeBalanceModal = () => {
  showBalanceModal.value = false;
  selectedBankAccount.value = null;
};

const handleBankAccountSaved = (message) => {
  closeModal();
  showToast('success', 'Success', message);
  fetchBankAccounts();
  loadDropdownData();
};

const handleBalanceUpdated = (message) => {
  closeBalanceModal();
  showToast('success', 'Success', message);
  fetchBankAccounts();
  loadDropdownData();
};

const toggleRowMenu = (event, account) => {
  activeAccountForMenu.value = account;
  rowMenu.value.toggle(event);
};

const toggleCardMenu = (event, account) => {
  activeAccountForMenu.value = account;
  cardMenu.value.toggle(event);
};

const toggleBulkMenu = (event) => {
  bulkMenu.value.toggle(event);
};

const bulkToggleStatus = async (status) => {
  showToast('info', 'Bulk Action', 'Bulk status update feature coming soon');
};

const confirmBulkDelete = () => {
  confirm.require({
    message: `Are you sure you want to delete ${selectedAccounts.value.length} selected bank accounts? This action is irreversible.`,
    header: 'Bulk Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Delete All',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      showToast('info', 'Bulk Action', 'Bulk delete feature coming soon');
    }
  });
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
  if (!dateString) return 'Not specified';
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium'
  }).format(new Date(dateString));
};

const getBalanceClass = (balance) => {
  if (balance > 0) return 'tw-text-green-500';
  if (balance < 0) return 'tw-text-red-500';
  return 'tw-text-gray-500';
};

const getCurrencyHeaderClass = (currency) => {
  const currencyClasses = {
    'DZD': 'tw-bg-gradient-to-r tw-from-emerald-500 tw-to-green-500',
    'EUR': 'tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500',
    'USD': 'tw-bg-gradient-to-r tw-from-gray-700 tw-to-slate-600',
    'GBP': 'tw-bg-gradient-to-r tw-from-red-600 tw-to-rose-500'
  };
  return currencyClasses[currency] || 'tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400';
};

const getBankColor = (bankCode) => {
  const colors = {
    'BA': '#1e40af',
    'BNPD': '#059669',
    'SGA': '#dc2626',
    'CPA': '#7c2d12',
    'BEA': '#4338ca',
    'CNEP': '#059669',
    'BDL': '#b91c1c',
    'TBA': '#0ea5e9',
    'ABBA': '#16a34a',
    'BAS': '#ca8a04'
  };
  return colors[bankCode] || '#6b7280';
};

// Lifecycle
onMounted(() => {
  fetchBankAccounts();
  loadDropdownData();
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-flex-col tw-items-center md:tw-items-start">
            <h1 class="tw-text-white tw-text-4xl tw-font-bold tw-flex tw-items-center tw-gap-4">
              <i class="pi pi-credit-card tw-text-5xl tw-opacity-90"></i>
              <div class="tw-flex tw-flex-col">
                <span class="tw-block tw-leading-none">Bank Account Management</span>
                <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal tw-leading-tight">
                  Manage your bank accounts and monitor balances
                </p>
              </div>
            </h1>
          </div>
          <div class="tw-flex tw-gap-2 tw-items-center tw-flex-wrap md:tw-flex-nowrap">
          
            <Button
              icon="pi pi-sync"
              label="Sync Balances"
              class="p-button-info p-button-lg tw-mr-2"
              @click="syncBalances"
              :loading="syncing"
            />
            <Button
              icon="pi pi-plus"
              label="New Bank Account"
              class="p-button-light p-button-lg"
              @click="openCreateModal"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <div v-if="stats" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400">
                <i class="pi pi-credit-card"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Accounts</div>
                <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.total_accounts }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
                <i class="pi pi-check-circle"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Active</div>
                <div class="tw-text-2xl tw-font-bold tw-text-green-500">{{ stats.active_accounts }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-violet-500 tw-to-purple-500">
                <i class="pi pi-wallet"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Balance</div>
                <div class="tw-text-2xl tw-font-bold tw-text-violet-500">{{ formatCurrency(stats.total_balance) }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-amber-500 tw-to-orange-500">
                <i class="pi pi-globe"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Currencies</div>
                <div class="tw-text-2xl tw-font-bold tw-text-amber-500">{{ Object.keys(stats.by_currency || {}).length }}</div>
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
                  placeholder="Search by account name, number, bank name..."
                  @input="debouncedSearch"
                  class="tw-w-full"
                />
              </div>
            </div>
            <div class="tw-col-span-1 tw-flex tw-flex-wrap tw-gap-4 tw-items-center tw-justify-end">
              <Dropdown
                v-model="filters.bank_id"
                :options="bankOptions"
                option-label="label"
                option-value="value"
                placeholder="All Banks"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchBankAccounts"
                showClear
              />
              <Dropdown
                v-model="filters.currency"
                :options="currencyOptions"
                option-label="label"
                option-value="value"
                placeholder="All Currencies"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchBankAccounts"
                showClear
              />
              <Dropdown
                v-model="filters.is_active"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchBankAccounts"
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
              @change="fetchBankAccounts"
              class="tw-min-w-[150px]"
            />
            <Button
              :icon="viewMode === 'table' ? 'pi pi-th-large' : 'pi pi-list'"
              class="p-button-outlined"
              v-tooltip.top="viewMode === 'table' ? 'Card View' : 'Table View'"
              @click="toggleView"
            />
            <Button
              icon="pi pi-refresh"
              class="p-button-outlined"
              @click="fetchBankAccounts"
              :loading="loading"
              v-tooltip.top="'Refresh'"
            />
          </div>
        </template>
      </Card>

      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
        <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
      </div>

      <div v-else-if="viewMode === 'table' && bankAccounts.length > 0">
        <DataTable
          :value="bankAccounts"
          :rows="perPage"
          responsive-layout="scroll"
          class="tw-rounded-xl tw-overflow-hidden tw-shadow-md"
          :row-hover="true"
          selection-mode="multiple"
          @row-click="onRowClick"
          v-model:selection="selectedAccounts"
        >
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-4">
              <h3 class="tw-text-gray-900 tw-font-bold tw-text-xl">Bank Accounts</h3>
              <div v-if="selectedAccounts.length > 0" class="tw-relative">
                <Button
                  icon="pi pi-check-square"
                  :label="`Actions (${selectedAccounts.length})`"
                  class="p-button-success p-button-sm"
                  @click="toggleBulkMenu"
                />
                <Menu ref="bulkMenu" :model="bulkMenuItems" :popup="true" />
              </div>
            </div>
          </template>

          <Column selection-mode="multiple" style="width: 3rem"></Column>

          <Column field="bank.name" header="Bank" :sortable="true" style="width: 20%">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-xl" :style="{ backgroundColor: getBankColor(data.bank?.code) }">
                  <i class="pi pi-building"></i>
                </div>
                <div>
                  <div class="tw-font-bold tw-text-gray-900">{{ data.bank?.name || 'Unknown Bank' }}</div>
                  <div class="tw-text-gray-500 tw-text-sm">{{ data.bank?.code || 'No code' }}</div>
                </div>
              </div>
            </template>
          </Column>

         <Column field="account_name" header="Account Details" :sortable="true" style="width: 25%">
        <template #body="{ data }">
          <div class="tw-flex tw-flex-col tw-gap-1 tw-cursor-pointer hover:tw-text-blue-600 tw-transition-colors" @click.stop="navigateToTransactions(data)">
            <div class="tw-font-bold tw-text-gray-900 tw-flex tw-items-center tw-gap-2">
              {{ data.account_name }}
              <i class="pi pi-external-link tw-text-xs tw-opacity-60"></i>
            </div>
            <div class="tw-font-mono tw-text-gray-600 tw-text-sm">{{ data.masked_account_number }}</div>
            <div v-if="data.iban" class="tw-font-mono tw-text-gray-400 tw-text-xs">{{ data.formatted_iban }}</div>
            <div class="tw-text-xs tw-text-blue-600 tw-opacity-70">Click to view transactions</div>
          </div>
        </template>
      </Column>

          <Column field="currency" header="Currency" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <Tag :value="data.currency" severity="info" class="tw-text-xs" />
            </template>
          </Column>

          <Column field="current_balance" header="Balance" :sortable="true" style="width: 15%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-bold" :class="getBalanceClass(data.current_balance)">
                  {{ formatCurrency(data.current_balance, data.currency) }}
                </div>
              
              </div>
            </template>
          </Column>

          <Column field="is_active" header="Status" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <Tag
                :value="data.is_active ? 'Active' : 'Inactive'"
                :severity="data.is_active ? 'success' : 'danger'"
                :icon="data.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'"
              />
            </template>
          </Column>

          <Column field="created_at" header="Created" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <span class="tw-text-gray-500 tw-text-sm">{{ formatDate(data.created_at) }}</span>
            </template>
          </Column>

          <Column header="Actions" :exportable="false" style="width: 8%">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-1">
                <Button
                  :icon="data.is_active ? 'pi pi-pause' : 'pi pi-play'"
                  :class="data.is_active ? 'p-button-warning p-button-text' : 'p-button-success p-button-text'"
                  class="p-button-rounded p-button-sm"
                  v-tooltip.top="data.is_active ? 'Deactivate' : 'Activate'"
                  @click="toggleStatus(data)"
                />
                <Button icon="pi pi-wallet" class="p-button-rounded p-button-info p-button-text" v-tooltip.top="'Update Balance'" @click="openBalanceModal(data)" />
                <Button icon="pi pi-ellipsis-v" class="p-button-rounded p-button-text p-button-sm" v-tooltip.top="'More actions'" @click="(event) => toggleRowMenu(event, data)" />
              </div>
              <Menu ref="rowMenu" :model="rowMenuItems" :popup="true" />
            </template>
          </Column>
        </DataTable>
      </div>

    <div v-else-if="viewMode === 'card' && bankAccounts.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
    <div v-for="account in bankAccounts" :key="account.id" class="tw-w-full">
      <Card 
        class="tw-h-full tw-rounded-xl tw-shadow-md hover:tw-shadow-lg hover:-tw-translate-y-1 tw-transition-all tw-duration-300 tw-cursor-pointer" 
        :class="{ 'tw-opacity-60': !account.is_active }"
        @click="navigateToTransactions(account)"
      >
        <template #header>
          <div class="tw-p-6 tw-rounded-t-xl tw-text-white tw-relative" :class="getCurrencyHeaderClass(account.currency)">
            <!-- Add a visual indicator -->
            <div class="tw-absolute tw-top-2 tw-right-2">
              <i class="pi pi-external-link tw-text-white/60"></i>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center">
              <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-white/20 tw-text-2xl" :style="{ backgroundColor: getBankColor(account.bank?.code) }">
                <i class="pi pi-building"></i>
              </div>
              <Button 
                icon="pi pi-ellipsis-v" 
                class="p-button-text p-button-plain p-button-rounded !tw-text-white" 
                @click.stop="(event) => toggleCardMenu(event, account)" 
              />
              <Menu ref="cardMenu" :model="cardMenuItems" :popup="true" />
            </div>
            <div class="tw-mt-4 tw-flex tw-items-end tw-justify-between">
              <div>
                <div class="tw-font-bold tw-text-lg tw-mb-1">{{ account.account_name }}</div>
                <Tag :value="account.is_active ? 'Active' : 'Inactive'" :severity="account.is_active ? 'success' : 'danger'" />
              </div>
              <div class="tw-text-sm tw-opacity-80">{{ account.bank?.code || '-' }}</div>
            </div>
            <!-- Add click hint -->
            <div class="tw-mt-2 tw-text-xs tw-text-white/70">
              Click to view transactions
            </div>
          </div>
        </template>
            <template #content>
              <div class="tw-p-6 tw-flex-grow tw-flex tw-flex-col tw-gap-3">
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-tag tw-text-blue-700 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">Account Number:</span>
                  <span class="tw-text-gray-500 tw-font-mono">{{ account.masked_account_number }}</span>
                </div>
                <div v-if="account.iban" class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-wallet tw-text-blue-700 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">IBAN:</span>
                  <span class="tw-text-gray-500 tw-font-mono">{{ account.formatted_iban }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-dollar tw-text-blue-700 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">Balance:</span>
                  <span class="tw-font-bold" :class="getBalanceClass(account.current_balance)">
                    {{ formatCurrency(account.current_balance, account.currency) }}
                  </span>
                </div>
              </div>
            </template>
             <template #footer>
          <div class="tw-p-6 tw-border-t tw-border-gray-200">
            <div class="tw-flex tw-gap-2 tw-flex-wrap">
              <Button 
                icon="pi pi-list" 
                label="View Transactions" 
                class="p-button-info p-button-outlined p-button-sm tw-flex-1" 
                @click.stop="navigateToTransactions(account)" 
              />
              <Button 
                icon="pi pi-pencil" 
                label="Edit" 
                class="p-button-warning p-button-outlined p-button-sm tw-flex-1" 
                @click.stop="editBankAccount(account)" 
              />
            </div>
          </div>
        </template>
          </Card>
        </div>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-12 tw-rounded-xl tw-bg-gray-50 tw-border-2 tw-border-dashed tw-border-gray-300">
        <i class="pi pi-credit-card tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
        <h4 class="tw-text-2xl tw-text-gray-800 tw-font-semibold tw-mb-2">No bank accounts found</h4>
        <p class="tw-text-gray-500 tw-mb-8">Start by adding your first bank account to see it here.</p>
        <Button icon="pi pi-plus" label="Add Bank Account" @click="openCreateModal" />
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

    <BankAccountModal v-if="showModal" :bank-account="selectedBankAccount" :is-editing="isEditing" @close="closeModal" @saved="handleBankAccountSaved" />
    <BalanceUpdateModal v-if="showBalanceModal" :bank-account="selectedBankAccount" @close="closeBalanceModal" @updated="handleBalanceUpdated" />
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
:deep(.p-inputgroup .p-inputgroup-addon) {
  @apply rounded-r-none;
}
</style>