<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { debounce } from 'lodash-es';

// PrimeVue Components (as per your request)
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import ProgressSpinner from 'primevue/progressspinner';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// Services and Components
import { coffreTransactionService } from '../../../../Components/Apps/services/Coffre/CoffreTransactionService';
import CoffreTransactionModal from '../../../../Components/Apps/coffre/Transactions/CoffreTransactionModal.vue';

// Composables
const route = useRoute();
const router = useRouter();

// PrimeVue composables
const toast = useToast();
const confirm = useConfirm();

const coffreId = ref(null);

const setCoffreIdFromRoute = () => {
  const paramId = route.params.coffre_id ?? route.params.coffreId ?? null;
  const queryId = route.query.coffre_id ?? route.query.coffreId ?? null;
  const id = paramId ?? queryId ?? null;
  coffreId.value = id !== null ? parseInt(id, 10) : null;
};

setCoffreIdFromRoute();

watch(route, () => {
  setCoffreIdFromRoute();
  fetchTransactions(1);
}, { deep: true });

const selectedCoffreName = ref('');

setCoffreIdFromRoute();

watch(route, () => {
  setCoffreIdFromRoute();
  fetchTransactions(1);
  loadDropdownData();
});

// Reactive state
const transactions = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const summary = ref(null);
const showModal = ref(false);
const selectedTransaction = ref(null);
const isEditing = ref(false);
const viewMode = ref('table');
const menu = ref();
const activeTransactionForMenu = ref(null);
const dateRange = ref(null);

// Filter states
const filters = ref({
  transaction_type: '',
  coffre_id: coffreId.value || '',
  user_id: '',
  date_from: '',
  date_to: ''
});

// Options
const transactionTypeOptions = ref([]);
const coffreOptions = ref([]);
const userOptions = ref([]);

const perPageOptions = [
  { label: '10 per page', value: 10 },
  { label: '15 per page', value: 15 },
  { label: '25 per page', value: 25 },
  { label: '50 per page', value: 50 }
];

const cardMenuItems = computed(() => [
  { label: 'View', icon: 'pi pi-eye', command: () => viewTransaction(activeTransactionForMenu.value) },
  { label: 'Edit', icon: 'pi pi-pencil', command: () => editTransaction(activeTransactionForMenu.value) },
  { separator: true },
  { label: 'Delete', icon: 'pi pi-trash', command: () => confirmDeleteTransaction(activeTransactionForMenu.value) }
]);

// Methods
const fetchTransactions = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
    ...filters.value
  };

  if (coffreId.value) {
    params.coffre_id = coffreId.value;
  }

  try {
    const result = await coffreTransactionService.getAll(params);
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

const loadDropdownData = async () => {
  const typesResult = await coffreTransactionService.getTransactionTypes();
  if (typesResult.success) {
    transactionTypeOptions.value = typesResult.data;
  }

  const coffresResult = await coffreTransactionService.getCoffres();
  if (coffresResult.success) {
    coffreOptions.value = coffresResult.data;
    if (coffreId.value) {
      const selectedCoffre = coffreOptions.value.find(c => c.id === coffreId.value);
      selectedCoffreName.value = selectedCoffre ? selectedCoffre.name : 'Unknown Safe';
    }
  }
};

const debouncedSearch = debounce(() => fetchTransactions(), 400);

const onPageChange = (event) => {
  fetchTransactions(event.page + 1);
};

const onDateRangeChange = () => {
  if (dateRange.value && dateRange.value.length === 2) {
    filters.value.date_from = dateRange.value[0].toISOString().split('T');
    filters.value.date_to = dateRange.value[1].toISOString().split('T');
  } else {
    filters.value.date_from = '';
    filters.value.date_to = '';
  }
  fetchTransactions();
};

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table';
};

const openCreateModal = () => {
  selectedTransaction.value = null;
  isEditing.value = false;
  showModal.value = true;
};

const editTransaction = (transaction) => {
  selectedTransaction.value = { ...transaction };
  isEditing.value = true;
  showModal.value = true;
};

const viewTransaction = (transaction) => {
  showToast('info', 'Information', `Viewing details for transaction #${transaction.id}`);
};

const confirmDeleteTransaction = (transaction) => {
  confirm.require({
    message: `Are you sure you want to delete this transaction? This action is irreversible.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteTransaction(transaction);
    }
  });
};

const deleteTransaction = async (transaction) => {
  try {
    const result = await coffreTransactionService.delete(transaction.id);
    if (result.success) {
      showToast('success', 'Success', 'Transaction deleted successfully');
      fetchTransactions();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during deletion.');
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedTransaction.value = null;
  isEditing.value = false;
};

const handleTransactionSaved = (message) => {
  closeModal();
  showToast('success', 'Success', message);
  fetchTransactions();
};

const toggleMenu = (event, transaction) => {
  activeTransactionForMenu.value = transaction;
  menu.value.toggle(event);
};

// Utility methods
const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(amount || 0);
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'Not specified';
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'short',
    timeStyle: 'short'
  }).format(new Date(dateString));
};

const getTransactionTypeSeverity = (type) => {
  const severityMap = {
    deposit: 'success',
    withdrawal: 'danger',
    transfer_in: 'info',
    transfer_out: 'warning',
    adjustment: 'secondary'
  };
  return severityMap[type] || 'secondary';
};

const getTransactionTypeIcon = (type) => {
  const iconMap = {
    deposit: 'pi pi-arrow-up',
    withdrawal: 'pi pi-arrow-down',
    transfer_in: 'pi pi-arrow-right',
    transfer_out: 'pi pi-arrow-left',
    adjustment: 'pi pi-cog'
  };
  return iconMap[type] || 'pi pi-circle';
};

const getTransactionHeaderClass = (type) => {
  const classMap = {
    deposit: 'tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500',
    withdrawal: 'tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500',
    transfer_in: 'tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500',
    transfer_out: 'tw-bg-gradient-to-r tw-from-amber-500 tw-to-orange-500',
    adjustment: 'tw-bg-gradient-to-r tw-from-gray-500 tw-to-slate-500'
  };
  return classMap[type] || '';
};

const getAmountClass = (type, amount) => {
  if (type === 'deposit' || type === 'transfer_in') return 'tw-text-green-500';
  if (type === 'withdrawal' || type === 'transfer_out') return 'tw-text-red-500';
  return amount >= 0 ? 'tw-text-green-500' : 'tw-text-red-500';
};

const getFormattedAmount = (type, amount) => {
  const sign = (type === 'deposit' || type === 'transfer_in') ? '+' : '';
  return `${sign}${formatCurrency(amount)}`;
};

// Lifecycle
onMounted(() => {
  fetchTransactions();
  loadDropdownData();
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-flex-col tw-items-center md:tw-items-start">
            <h1 class="tw-text-white tw-text-4xl tw-font-bold tw-flex tw-items-center tw-gap-4">
              <i class="pi pi-dollar tw-text-5xl tw-opacity-90"></i>
              <div class="tw-flex tw-flex-col">
                <span class="tw-block tw-leading-none">{{ coffreId ? `Transactions - ${selectedCoffreName}` : 'Transaction Management' }}</span>
                <p class="tw-text-white/80 tw-mt-2 tw-text-lg tw-font-normal tw-leading-tight">
                  {{ coffreId ? `View transactions for ${selectedCoffreName}` : 'Manage all safe transactions and monitor cash flow' }}
                </p>
              </div>
            </h1>
          </div>
          <div class="tw-flex tw-gap-2 tw-items-center tw-flex-wrap md:tw-flex-nowrap">
            <Button icon="pi pi-plus" label="New Transaction" class="p-button-light p-button-lg" @click="openCreateModal" />
            <Button v-if="coffreId" icon="pi pi-arrow-left" label="Back to Safes" class="p-button-outlined p-button-lg" @click="$router.push({ name: 'coffre.list' })" />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <div v-if="summary" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-6 tw-mb-8">
        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
                <i class="pi pi-arrow-up"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Deposits</div>
                <div class="tw-text-2xl tw-font-bold tw-text-green-500">{{ formatCurrency(summary.total_deposits) }}</div>
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
                <div class="tw-text-gray-600 tw-text-sm">Total Withdrawals</div>
                <div class="tw-text-2xl tw-font-bold tw-text-red-500">{{ formatCurrency(summary.total_withdrawals) }}</div>
              </div>
            </div>
          </template>
        </Card>
        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500">
                <i class="pi pi-chart-line"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Net Amount</div>
                <div class="tw-text-2xl tw-font-bold" :class="summary.net_amount >= 0 ? 'tw-text-green-500' : 'tw-text-red-500'">
                  {{ formatCurrency(summary.net_amount) }}
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <Card class="tw-mb-8 tw-border-none tw-rounded-xl tw-shadow-md">
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6 tw-items-center">
            <div class="tw-col-span-1 md:tw-col-span-2">
              <div class="p-inputgroup">
                <span class="p-inputgroup-addon tw-rounded-l-lg">
                  <i class="pi pi-search"></i>
                </span>
                <InputText
                  v-model="searchQuery"
                  placeholder="Search by description, safe name, user..."
                  @input="debouncedSearch"
                  class="tw-w-full"
                />
              </div>
            </div>
            <div class="tw-col-span-1 tw-flex tw-flex-wrap tw-gap-4 tw-items-center tw-justify-end">
              <Dropdown
                v-model="filters.transaction_type"
                :options="transactionTypeOptions"
                option-label="label"
                option-value="value"
                placeholder="All Types"
                class="tw-min-w-[150px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
              <Dropdown
                v-model="filters.coffre_id"
                :options="coffreOptions"
                option-label="name"
                option-value="id"
                placeholder="All Safes"
                class="tw-min-w-[150px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
              <Calendar
                v-model="dateRange"
                selection-mode="range"
                :manual-input="false"
                date-format="yy-mm-dd"
                placeholder="Select Date Range"
                class="tw-min-w-[150px] tw-flex-1"
                @date-select="onDateRangeChange"
                showIcon
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
        </template>
      </Card>

      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
        <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
      </div>

      <div v-else-if="viewMode === 'table' && transactions.length > 0">
        <DataTable
          :value="transactions"
          :rows="perPage"
          responsive-layout="scroll"
          class="tw-rounded-xl tw-overflow-hidden tw-shadow-md"
          :row-hover="true"
        >
          <Column field="transaction_type" header="Type" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <Tag
                :value="data.transaction_type_display"
                :severity="getTransactionTypeSeverity(data.transaction_type)"
                :icon="getTransactionTypeIcon(data.transaction_type)"
              />
            </template>
          </Column>
          <Column field="coffre.name" header="Safe" :sortable="true" style="width: 15%">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-9 tw-h-9 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-gray-600 tw-text-white tw-text-sm">
                  <i class="pi pi-lock"></i>
                </div>
                <div>
                  <div class="tw-font-bold tw-text-gray-800">{{ data.coffre?.name }}</div>
                  <div class="tw-text-gray-500 tw-text-xs">{{ data.coffre?.location || 'No location' }}</div>
                </div>
              </div>
            </template>
          </Column>
          <Column field="amount" header="Amount" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-font-bold" :class="getAmountClass(data.transaction_type, data.amount)">
                {{ getFormattedAmount(data.transaction_type, data.amount) }}
              </div>
            </template>
          </Column>
          <Column field="description" header="Description" style="width: 25%">
            <template #body="{ data }">
              <span class="tw-text-gray-600 tw-text-sm">{{ data.description || 'No description' }}</span>
            </template>
          </Column>
          <Column field="user.name" header="User" :sortable="true" style="width: 15%">
            <template #body="{ data }">
              <div v-if="data.user" class="tw-flex tw-items-center tw-gap-2">
                <Avatar :label="data.user.name.charAt(0).toUpperCase()" class="tw-flex-shrink-0" size="normal" shape="circle" />
                <span class="tw-font-medium tw-text-gray-800 tw-text-sm">{{ data.user.name }}</span>
              </div>
              <Tag v-else value="Unknown" severity="warning" />
            </template>
          </Column>
          <Column field="created_at" header="Date" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <span class="tw-text-gray-500 tw-text-xs">{{ formatDateTime(data.created_at) }}</span>
            </template>
          </Column>
          <Column header="Actions" :exportable="false" style="width: 9%">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-1">
                <Button icon="pi pi-eye" class="p-button-rounded p-button-info p-button-text" v-tooltip.top="'View details'" @click="viewTransaction(data)" />
                <Button icon="pi pi-pencil" class="p-button-rounded p-button-warning p-button-text" v-tooltip.top="'Edit'" @click="editTransaction(data)" />
                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-text" v-tooltip.top="'Delete'" @click="confirmDeleteTransaction(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <div v-else-if="viewMode === 'card' && transactions.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <div v-for="transaction in transactions" :key="transaction.id" class="tw-w-full">
          <Card class="tw-h-full tw-rounded-xl tw-shadow-md hover:tw-shadow-lg hover:-tw-translate-y-1 tw-transition-all tw-duration-300">
            <template #header>
              <div class="tw-p-6 tw-rounded-t-xl tw-text-white" :class="getTransactionHeaderClass(transaction.transaction_type)">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                  <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-white/20">
                    <i :class="getTransactionTypeIcon(transaction.transaction_type)"></i>
                  </div>
                  <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-plain p-button-rounded !tw-text-white" @click="(event) => toggleMenu(event, transaction)" />
                  <Menu ref="menu" :model="cardMenuItems" :popup="true" />
                </div>
                <div class="tw-flex tw-flex-col">
                  <span class="tw-text-sm tw-font-semibold tw-opacity-80">{{ transaction.transaction_type_display }}</span>
                  <span class="tw-text-2xl tw-font-bold">{{ getFormattedAmount(transaction.transaction_type, transaction.amount) }}</span>
                </div>
              </div>
            </template>
            <template #content>
              <div class="tw-p-6 tw-flex-grow tw-flex tw-flex-col tw-gap-3">
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-lock tw-text-gray-500 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">Safe:</span>
                  <span class="tw-text-gray-500">{{ transaction.coffre?.name || 'Unknown' }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-user tw-text-gray-500 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">User:</span>
                  <span class="tw-text-gray-500">{{ transaction.user?.name || 'Unknown' }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-calendar tw-text-gray-500 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">Date:</span>
                  <span class="tw-text-gray-500">{{ formatDateTime(transaction.created_at) }}</span>
                </div>
                <div v-if="transaction.description" class="tw-flex tw-items-start tw-gap-2 tw-text-sm tw-mt-2">
                  <i class="pi pi-file-edit tw-text-gray-500 tw-w-4 tw-flex-shrink-0 tw-mt-1"></i>
                  <span class="tw-font-medium tw-text-gray-700">Description:</span>
                  <p class="tw-text-gray-500 tw-italic">{{ transaction.description }}</p>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-12 tw-rounded-xl tw-bg-gray-50 tw-border-2 tw-border-dashed tw-border-gray-300">
        <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
        <h4 class="tw-text-2xl tw-text-gray-800 tw-font-semibold tw-mb-2">No transactions found</h4>
        <p class="tw-text-gray-500 tw-mb-8">Start by creating your first transaction to see it here.</p>
        <Button icon="pi pi-plus" label="Create New Transaction" @click="openCreateModal" />
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

    <CoffreTransactionModal v-if="showModal" :transaction="selectedTransaction" :coffreId="coffreId" :is-editing="isEditing" @close="closeModal" @saved="handleTransactionSaved" />
    <Toast />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
/*
 * Minimal CSS is needed when using Tailwind.
 * We only use it for deep styling of PrimeVue components that can't be reached by classes.
 */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply tw-bg-gray-50 tw-text-gray-700 tw-font-semibold tw-border-none tw-text-sm;
}
:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply tw-transition-colors tw-duration-200;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  @apply tw-bg-blue-50;
}
:deep(.p-card-header) {
  @apply tw-p-0;
}
:deep(.p-card-content) {
  @apply tw-p-0;
}
</style>