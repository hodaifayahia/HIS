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
  


    <div class="tw-container tw-mx-auto tw-p-6">
      <div class="tw-mb-6">
        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-mb-2">
          Coffre Management
        </h1>
        <p class="tw-text-gray-600">
          Manage coffre transactions and approve bank transfers
        </p>
      </div>

      <TabView>
        <!-- Transactions Tab -->
        <TabPanel header="Transactions">
          <Card class="tw-shadow-lg">
            <template #title>
              <div class="tw-flex tw-justify-between tw-items-center">
                <span>Coffre Transactions</span>
                <Button
                  label="New Transaction"
                  icon="pi pi-plus"
                  class="p-button-primary"
                  @click="openCreateModal"
                />
              </div>
            </template>
            
            <template #content>
              <!-- Filters -->
              <div class="tw-mb-4 tw-flex tw-gap-4 tw-flex-wrap">
                <div class="tw-flex-1 tw-min-w-[200px]">
                  <span class="p-input-icon-left tw-w-full">
                    <i class="pi pi-search" />
                    <InputText
                      v-model="filters.global.value"
                      placeholder="Search transactions..."
                      class="tw-w-full"
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
                    class="tw-w-full"
                  />
                </div>
              </div>

              <!-- Transactions Table -->
              <DataTable
                :value="transactions"
                :loading="loading"
                v-model:filters="filters"
                :globalFilterFields="['description', 'coffre.name', 'user.name']"
                paginator
                :rows="15"
                dataKey="id"
                emptyMessage="No transactions found."
                class="p-datatable-sm"
              >
                <Column field="transaction_type" header="Type" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i :class="[getTypeIcon(data.transaction_type), getTypeColor(data.transaction_type)]"></i>
                      <span class="tw-capitalize">{{ data.transaction_type.replace('_', ' ') }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="amount" header="Amount" sortable>
                  <template #body="{ data }">
                    <span class="tw-font-semibold tw-text-green-600">
                      {{ formatAmount(data.amount) }}
                    </span>
                  </template>
                </Column>

                <Column field="status" header="Status" sortable>
                  <template #body="{ data }">
                    <TransactionStatusBadge :status="data.status" :transaction="data" />
                  </template>
                </Column>

                <Column field="coffre.name" header="Coffre">
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-lock tw-text-gray-500"></i>
                      <span>{{ data.coffre?.name || 'Unknown' }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="description" header="Description">
                  <template #body="{ data }">
                    <span class="tw-truncate tw-max-w-xs tw-block" :title="data.description">
                      {{ data.description || 'No description' }}
                    </span>
                  </template>
                </Column>

                <Column field="user.name" header="User">
                  <template #body="{ data }">
                    <span>{{ data.user?.name || 'Unknown' }}</span>
                  </template>
                </Column>

                <Column field="created_at" header="Date" sortable>
                  <template #body="{ data }">
                    <span class="tw-text-sm tw-text-gray-600">
                      {{ formatDate(data.created_at) }}
                    </span>
                  </template>
                </Column>

                <Column header="Actions" :exportable="false">
                  <template #body="{ data }">
                      <div class="tw-flex tw-gap-2">
                        <Button
                          icon="pi pi-pencil"
                          class="p-button-sm"
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
            </template>
          </Card>
        </TabPanel>

        <!-- Approvals Tab -->
        <TabPanel header="Pending Approvals">
          <PendingApprovalsList />
        </TabPanel>
      </TabView>

      <!-- Transaction Modal -->
      <CoffreTransactionModal
        v-if="showModal"
        :transaction="editingTransaction"
        :is-editing="isEditing"
        @close="closeModal"
        @saved="onTransactionSaved"
      />
    </div>

</template>

<style scoped>
/* Component-specific styles */
:deep(.p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  font-weight: 600;
}

:deep(.p-button-sm) {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

:deep(.p-tabview-panels) {
  padding: 0;
}
</style>
