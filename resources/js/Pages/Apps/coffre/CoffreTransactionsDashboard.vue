<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

// Components
import PendingApprovalsList from '../../../Components/Apps/coffre/Approvals/PendingApprovalsList.vue';
import CoffreTransactionModal from '../../../Components/Apps/coffre/Transactions/CoffreTransactionModal.vue';
import TransactionStatusBadge from '../../../Components/Apps/coffre/Transactions/TransactionStatusBadge.vue';

// Services
import { coffreTransactionService } from '../../../Components/Apps/services/Coffre/CoffreTransactionService';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';

// State
const transactions = ref([]);
const loading = ref(false);
const showModal = ref(false);
const editingTransaction = ref(null);
const isEditing = ref(false);
const selectedCoffre = ref(null);

const filters = ref({
  global: { value: null, matchMode: 'contains' },
  status: { value: null, matchMode: 'equals' }
});

const statusOptions = ref([
  { label: 'All', value: null },
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Rejected', value: 'rejected' }
]);

// Methods
const loadTransactions = async () => {
  loading.value = true;
  try {
    const result = await coffreTransactionService.getAll();
    if (result.success) {
      transactions.value = result.data || [];
    }
  } catch (error) {
    console.error('Error loading transactions:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  editingTransaction.value = null;
  isEditing.value = false;
  showModal.value = true;
};

const openEditModal = (transaction) => {
  editingTransaction.value = transaction;
  isEditing.value = true;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingTransaction.value = null;
  isEditing.value = false;
};

const onTransactionSaved = (message) => {
  closeModal();
  loadTransactions();
  // You could show a toast notification here
  alert(message);
};

const deleteTransaction = async (transaction) => {
  if (!confirm('Are you sure you want to delete this transaction?')) {
    return;
  }

  try {
    const result = await coffreTransactionService.delete(transaction.id);
    if (result.success) {
      await loadTransactions();
      alert('Transaction deleted successfully');
    } else {
      alert(result.message || 'Failed to delete transaction');
    }
  } catch (error) {
    console.error('Error deleting transaction:', error);
    alert('An error occurred while deleting the transaction');
  }
};

const formatAmount = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount) + ' DZD';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getTypeIcon = (type) => {
  const icons = {
    'deposit': 'pi pi-plus-circle',
    'withdrawal': 'pi pi-minus-circle',
    'transfer_in': 'pi pi-arrow-down',
    'transfer_out': 'pi pi-arrow-up',
    'adjustment': 'pi pi-wrench'
  };
  return icons[type] || 'pi pi-circle';
};

const getTypeColor = (type) => {
  const colors = {
    'deposit': 'tw-text-green-600',
    'withdrawal': 'tw-text-red-600',
    'transfer_in': 'tw-text-blue-600',
    'transfer_out': 'tw-text-orange-600',
    'adjustment': 'tw-text-purple-600'
  };
  return colors[type] || 'tw-text-gray-600';
};

// Lifecycle
onMounted(() => {
  loadTransactions();
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen tw-p-6 md:tw-p-8">
    <div class="tw-max-w-7xl tw-mx-auto">
      <div class="tw-mb-6 md:tw-mb-8">
        <h1 class="tw-text-3xl lg:tw-text-4xl tw-font-bold tw-text-gray-900 tw-mb-2">
          Coffre Management
        </h1>
        <p class="tw-text-gray-600 tw-text-lg">
          Manage coffre transactions and approve transfers.
        </p>
      </div>

      <TabView class="tw-bg-white tw-shadow-xl tw-rounded-2xl">
        <TabPanel header="Transactions">
          <Card class="tw-border-0">
            <template #title>
              <div class="tw-flex tw-justify-between tw-items-center tw-p-6 tw-border-b tw-border-gray-200">
                <span class="tw-text-xl tw-font-semibold tw-text-gray-800">Coffre Transactions</span>
                <Button
                  label="New Transaction"
                  icon="pi pi-plus"
                  class="p-button-primary tw-rounded-lg"
                  @click="openCreateModal"
                />
              </div>
            </template>
            
            <template #content>
              <div class="tw-p-6">
                <div class="tw-mb-6 tw-flex tw-gap-4 tw-flex-wrap">
                  <div class="tw-flex-1 tw-min-w-[200px]">
                    <span class="p-input-icon-left tw-w-full">
                      <i class="pi pi-search" />
                      <InputText
                        v-model="filters.global.value"
                        placeholder="Search transactions..."
                        class="tw-w-full tw-rounded-lg"
                      />
                    </span>
                  </div>
                  <div class="tw-min-w-[150px]">
                    <Dropdown
                      v-model="filters.status.value"
                      :options="statusOptions"
                      option-label="label"
                      option-value="value"
                      placeholder="Filter by status"
                      class="tw-w-full tw-rounded-lg"
                    />
                  </div>
                </div>

                <DataTable
                  :value="transactions"
                  :loading="loading"
                  v-model:filters="filters"
                  :globalFilterFields="['description', 'coffre.name', 'user.name']"
                  paginator
                  :rows="15"
                  dataKey="id"
                  emptyMessage="No transactions found."
                  class="tw-transactions-table"
                >
                  <Column field="transaction_type" header="Type" sortable style="min-width: 150px;">
                    <template #body="{ data }">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i :class="[getTypeIcon(data.transaction_type), getTypeColor(data.transaction_type)]"></i>
                        <span class="tw-capitalize tw-font-medium">{{ data.transaction_type.replace('_', ' ') }}</span>
                      </div>
                    </template>
                  </Column>

                  <Column field="amount" header="Amount" sortable style="min-width: 120px;">
                    <template #body="{ data }">
                      <span class="tw-font-bold" :class="data.transaction_type === 'deposit' ? 'tw-text-green-600' : 'tw-text-red-600'">
                        {{ formatAmount(data.amount) }}
                      </span>
                    </template>
                  </Column>

                  <Column field="status" header="Status" sortable style="min-width: 120px;">
                    <template #body="{ data }">
                      <TransactionStatusBadge :status="data.status" :transaction="data" />
                    </template>
                  </Column>

                  <Column field="coffre.name" header="Coffre" style="min-width: 150px;">
                    <template #body="{ data }">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i class="pi pi-lock tw-text-gray-500"></i>
                        <span>{{ data.coffre?.name || 'Unknown' }}</span>
                      </div>
                    </template>
                  </Column>

                  <Column field="description" header="Description" style="min-width: 250px;">
                    <template #body="{ data }">
                      <span class="tw-truncate tw-max-w-xs tw-block tw-text-gray-700" :title="data.description">
                        {{ data.description || 'No description' }}
                      </span>
                    </template>
                  </Column>

                  <Column field="user.name" header="User" style="min-width: 150px;">
                    <template #body="{ data }">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i class="pi pi-user tw-text-gray-500"></i>
                        <span>{{ data.user?.name || 'Unknown' }}</span>
                      </div>
                    </template>
                  </Column>

                  <Column field="created_at" header="Date" sortable style="min-width: 150px;">
                    <template #body="{ data }">
                      <span class="tw-text-sm tw-text-gray-600">
                        {{ formatDate(data.created_at) }}
                      </span>
                    </template>
                  </Column>

                  <Column header="Actions" :exportable="false" style="min-width: 120px;">
                    <template #body="{ data }">
                      <div class="tw-flex tw-gap-2">
                        <Button
                          icon="pi pi-pencil"
                          class="p-button-sm p-button-secondary"
                          @click="openEditModal(data)"
                          :disabled="data.status === 'pending'"
                        />
                        <Button
                          icon="pi pi-trash"
                          class="p-button-sm p-button-danger"
                          @click="deleteTransaction(data)"
                          :disabled="data.status === 'pending'"
                        />
                      </div>
                    </template>
                  </Column>
                </DataTable>
              </div>
            </template>
          </Card>
        </TabPanel>

        <TabPanel header="Pending Approvals">
          <PendingApprovalsList />
        </TabPanel>
      </TabView>

      <CoffreTransactionModal
        v-if="showModal"
        :transaction="editingTransaction"
        :is-editing="isEditing"
        @close="closeModal"
        @saved="onTransactionSaved"
      />
    </div>
  </div>
</template>

<style scoped>
/*
 * Custom styles to enhance PrimeVue components using Tailwind.
 * All classes are prefixed with 'tw-' to avoid conflicts.
 */

/* PrimeVue Tabs */
:deep(.p-tabview-nav) {
  @apply bg-gray-100 tw-rounded-t-2xl tw-p-4;
}

:deep(.p-tabview-nav-link) {
  @apply text-lg tw-font-semibold tw-text-gray-700 tw-px-6 tw-py-3 tw-transition-colors tw-duration-200 tw-rounded-lg;
}

:deep(.p-tabview-nav-link:hover) {
  @apply bg-gray-200 tw-text-gray-900;
}

:deep(.p-tabview-nav-link.p-highlight) {
  @apply bg-white tw-text-blue-600 tw-shadow-md;
}

/* PrimeVue Card */
:deep(.p-card) {
  @apply rounded-2xl tw-shadow-xl;
}

:deep(.p-card-body) {
  @apply p-0;
}

/* PrimeVue DataTable */
:deep(.p-datatable-header) {
  @apply p-0;
}

:deep(.p-datatable-thead > tr > th) {
  @apply bg-gray-100 tw-text-gray-600 tw-font-semibold tw-text-sm tw-uppercase tw-p-4;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply transition-colors tw-duration-200;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  @apply bg-gray-50;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  @apply p-4 tw-border-b tw-border-gray-200;
}

/* PrimeVue Buttons */
.p-button-primary {
  @apply bg-blue-600 tw-border-blue-600 tw-text-white hover:tw-bg-blue-700 focus:tw-ring-blue-500;
}

.p-button-secondary {
  @apply bg-gray-200 tw-border-gray-200 tw-text-gray-800 hover:tw-bg-gray-300 focus:tw-ring-gray-300;
}

.p-button-danger {
  @apply bg-red-600 tw-border-red-600 tw-text-white hover:tw-bg-red-700 focus:tw-ring-red-500;
}

/* PrimeVue Inputs */
:deep(.p-inputtext) {
  @apply border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500 tw-shadow-sm;
}

:deep(.p-dropdown) {
  @apply border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500 tw-shadow-sm;
}
</style>