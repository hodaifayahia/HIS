<script setup>
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
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
import { bankService } from '../../../../Components/Apps/services/bank/bankService';
import BankModal from '../../../../Components/Apps/banking/bank/BanqueModal.vue';

// Composables
const toast = useToast();
const confirm = useConfirm();

// Reactive state
const banks = ref([]);
const loading = ref(true);
const seeding = ref(false);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const stats = ref(null);
const showModal = ref(false);
const selectedBank = ref(null);
const isEditing = ref(false);
const viewMode = ref('card');
const menu = ref();
const activeBankForMenu = ref(null);

// Filter states
const filters = ref({
  currency: '',
  is_active: ''
});

// Options
const currencyOptions = [
  { label: 'DZD', value: 'DZD' },
  { label: 'EUR', value: 'EUR' },
  { label: 'USD', value: 'USD' },
  { label: 'GBP', value: 'GBP' },
  { label: 'CHF', value: 'CHF' },
  { label: 'JPY', value: 'JPY' }
];

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

const cardMenuItems = computed(() => [
  {
    label: activeBankForMenu.value?.is_active ? 'Deactivate' : 'Activate',
    icon: activeBankForMenu.value?.is_active ? 'pi pi-pause' : 'pi pi-play',
    command: () => toggleStatus(activeBankForMenu.value)
  },
  { label: 'View', icon: 'pi pi-eye', command: () => viewBank(activeBankForMenu.value) },
  { label: 'Edit', icon: 'pi pi-pencil', command: () => editBank(activeBankForMenu.value) },
  { separator: true },
  { label: 'Delete', icon: 'pi pi-trash', command: () => confirmDeleteBank(activeBankForMenu.value) }
]);

// Methods
const fetchBanks = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
    ...filters.value
  };

  try {
    const result = await bankService.getAll(params);
    if (result.success) {
      banks.value = result.data;
      meta.value = result.meta;
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while loading banks.');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadStats = async () => {
  const statsResult = await bankService.getStats();
  if (statsResult.success) {
    stats.value = statsResult.data;
  }
};

const debouncedSearch = debounce(() => fetchBanks(), 400);

const onPageChange = (event) => {
  fetchBanks(event.page + 1);
};

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table';
};

const openCreateModal = () => {
  selectedBank.value = null;
  isEditing.value = false;
  showModal.value = true;
};

const editBank = (bank) => {
  selectedBank.value = { ...bank };
  isEditing.value = true;
  showModal.value = true;
};

const viewBank = (bank) => {
  showToast('info', 'Information', `Viewing details for ${bank.name}`);
};

const toggleStatus = async (bank) => {
  try {
    const result = await bankService.toggleStatus(bank.id);
    if (result.success) {
      showToast('success', 'Success', `Bank ${result.data.is_active ? 'activated' : 'deactivated'} successfully`);
      fetchBanks();
      loadStats();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while updating status.');
  }
};

const confirmDeleteBank = (bank) => {
  confirm.require({
    message: `Are you sure you want to delete "${bank.name}"? This action is irreversible.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteBank(bank);
    }
  });
};

const deleteBank = async (bank) => {
  try {
    const result = await bankService.delete(bank.id);
    if (result.success) {
      showToast('success', 'Success', 'Bank deleted successfully');
      fetchBanks();
      loadStats();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during deletion.');
  }
};

const seedDefaultBanks = async () => {
  seeding.value = true;
  try {
    const result = await bankService.seedDefault();
    if (result.success) {
      showToast('success', 'Success', 'Default banks seeded successfully');
      fetchBanks();
      loadStats();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during seeding.');
  } finally {
    seeding.value = false;
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedBank.value = null;
  isEditing.value = false;
};

const handleBankSaved = (message) => {
  closeModal();
  showToast('success', 'Success', message);
  fetchBanks();
  loadStats();
};

const toggleMenu = (event, bank) => {
  activeBankForMenu.value = bank;
  menu.value.toggle(event);
};

// Utility methods
const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};

const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium'
  }).format(new Date(dateString));
};

// Lifecycle
onMounted(() => {
  fetchBanks();
  loadStats();
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-violet-500 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-flex-col tw-items-center md:tw-items-start">
            <h1 class="tw-text-white tw-text-4xl tw-font-bold tw-flex tw-items-center tw-gap-4">
              <i class="pi pi-building tw-text-5xl tw-opacity-90"></i>
              <div class="tw-flex tw-flex-col">
                <span class="tw-block tw-leading-none">Bank Management</span>
                <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal tw-leading-tight">
                  Manage financial institutions and their information
                </p>
              </div>
            </h1>
          </div>
          <div class="tw-flex tw-gap-2 tw-items-center tw-flex-wrap md:tw-flex-nowrap">
            <Button icon="pi pi-download" label="Seed Default Banks" class="p-button-secondary p-button-lg tw-mr-2" @click="seedDefaultBanks" :loading="seeding" />
            <Button icon="pi pi-plus" label="New Bank" class="p-button-light p-button-lg" @click="openCreateModal" />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <div v-if="stats" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-indigo-500 tw-to-violet-500">
                <i class="pi pi-building"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Banks</div>
                <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.total_banks }}</div>
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
                <div class="tw-text-gray-600 tw-text-sm">Active Banks</div>
                <div class="tw-text-2xl tw-font-bold tw-text-green-500">{{ stats.active_banks }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
                <i class="pi pi-times-circle"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Inactive Banks</div>
                <div class="tw-text-2xl tw-font-bold tw-text-red-500">{{ stats.inactive_banks }}</div>
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
                  placeholder="Search by bank name or code..."
                  @input="debouncedSearch"
                  class="tw-w-full"
                />
              </div>
            </div>
            <div class="tw-col-span-1 tw-flex tw-flex-wrap tw-gap-4 tw-items-center tw-justify-end">
              <Dropdown
                v-model="filters.currency"
                :options="currencyOptions"
                option-label="label"
                option-value="value"
                placeholder="All Currencies"
                class="tw-min-w-[150px] tw-flex-1"
                @change="fetchBanks"
                showClear
              />
              <Dropdown
                v-model="filters.is_active"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                class="tw-min-w-[150px] tw-flex-1"
                @change="fetchBanks"
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
              @change="fetchBanks"
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
              @click="fetchBanks"
              :loading="loading"
              v-tooltip.top="'Refresh'"
            />
          </div>
        </template>
      </Card>

      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
        <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
      </div>

      <div v-else-if="viewMode === 'table' && banks.length > 0">
        <DataTable
          :value="banks"
          :rows="perPage"
          responsive-layout="scroll"
          class="tw-rounded-xl tw-overflow-hidden tw-shadow-md"
          :row-hover="true"
        >
          <Column field="name" header="Bank Name" :sortable="true" style="width: 25%">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-gradient-to-r tw-from-indigo-500 tw-to-violet-500 tw-text-white">
                  <i class="pi pi-building"></i>
                </div>
                <div>
                  <div class="tw-font-bold tw-text-gray-900">{{ data.name }}</div>
                  <div class="tw-text-gray-500 tw-text-sm">{{ data.code || 'No code' }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="swift_code" header="SWIFT Code" :sortable="true" style="width: 15%">
            <template #body="{ data }">
              <span class="tw-font-semibold tw-text-gray-900">{{ data.swift_code || '-' }}</span>
            </template>
          </Column>

          <Column field="supported_currencies" header="Currencies" style="width: 20%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-wrap tw-gap-1">
                <Tag
                  v-for="currency in (data.supported_currencies || [])"
                  :key="currency"
                  :value="currency"
                  severity="info"
                  class="tw-text-xs"
                />
                <span v-if="!data.supported_currencies?.length" class="tw-text-gray-400 tw-italic">No currencies</span>
              </div>
            </template>
          </Column>

          <Column field="is_active" header="Status" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <Tag
                :value="data.is_active ? 'Active' : 'Inactive'"
                :severity="data.is_active ? 'success' : 'danger'"
                :icon="data.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'"
              />
            </template>
          </Column>

          <Column field="created_at" header="Created" :sortable="true" style="width: 15%">
            <template #body="{ data }">
              <span class="tw-text-gray-500 tw-text-sm">{{ formatDate(data.created_at) }}</span>
            </template>
          </Column>

          <Column header="Actions" :exportable="false" style="width: 13%">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-1">
                <Button
                  :icon="data.is_active ? 'pi pi-pause' : 'pi pi-play'"
                  :class="data.is_active ? 'p-button-warning p-button-text' : 'p-button-success p-button-text'"
                  class="p-button-rounded p-button-sm"
                  v-tooltip.top="data.is_active ? 'Deactivate' : 'Activate'"
                  @click="toggleStatus(data)"
                />
                <Button icon="pi pi-eye" class="p-button-rounded p-button-info p-button-text" v-tooltip.top="'View details'" @click="viewBank(data)" />
                <Button icon="pi pi-pencil" class="p-button-rounded p-button-warning p-button-text" v-tooltip.top="'Edit'" @click="editBank(data)" />
                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-text" v-tooltip.top="'Delete'" @click="confirmDeleteBank(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <div v-else-if="viewMode === 'card' && banks.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <div v-for="bank in banks" :key="bank.id" class="tw-w-full">
          <Card class="tw-h-full tw-rounded-xl tw-shadow-md hover:tw-shadow-lg hover:-tw-translate-y-1 tw-transition-all tw-duration-300" :class="{ 'tw-opacity-60': !bank.is_active }">
            <template #header>
              <div class="tw-p-6 tw-rounded-t-xl tw-bg-gradient-to-br tw-from-indigo-500 tw-to-violet-500 tw-text-white">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-white/20 tw-text-2xl">
                    <i class="pi pi-building"></i>
                  </div>
                  <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-plain p-button-rounded !tw-text-white" @click="(event) => toggleMenu(event, bank)" />
                  <Menu ref="menu" :model="cardMenuItems" :popup="true" />
                </div>
                <div class="tw-mt-4 tw-flex tw-items-end tw-justify-between">
                  <div>
                    <div class="tw-font-bold tw-text-lg tw-mb-1">{{ bank.name }}</div>
                    <Tag :value="bank.is_active ? 'Active' : 'Inactive'" :severity="bank.is_active ? 'success' : 'danger'" />
                  </div>
                  <div class="tw-text-sm tw-opacity-80">{{ bank.code || '-' }}</div>
                </div>
              </div>
            </template>
            <template #content>
              <div class="tw-p-6 tw-flex-grow tw-flex tw-flex-col tw-gap-3">
                <div v-if="bank.swift_code" class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-send tw-text-indigo-500 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">SWIFT:</span>
                  <span class="tw-text-gray-500">{{ bank.swift_code }}</span>
                </div>
                <div v-if="bank.supported_currencies?.length" class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                  <i class="pi pi-dollar tw-text-indigo-500 tw-w-4 tw-flex-shrink-0"></i>
                  <span class="tw-font-medium tw-text-gray-700">Currencies:</span>
                  <div class="tw-flex tw-flex-wrap tw-gap-1">
                    <Tag v-for="currency in bank.supported_currencies" :key="currency" :value="currency" severity="info" class="tw-text-xs" />
                  </div>
                </div>
                <div v-if="bank.address" class="tw-flex tw-items-start tw-gap-2 tw-text-sm">
                  <i class="pi pi-map-marker tw-text-indigo-500 tw-w-4 tw-flex-shrink-0 tw-mt-1"></i>
                  <span class="tw-font-medium tw-text-gray-700">Address:</span>
                  <p class="tw-text-gray-500 tw-leading-snug">{{ bank.address }}</p>
                </div>
              </div>
            </template>
            <template #footer>
              <div class="tw-p-6 tw-border-t tw-border-gray-200">
                <div class="tw-flex tw-gap-2">
                  <Button
                    icon="pi pi-pencil"
                    label="Edit"
                    class="p-button-warning p-button-outlined p-button-sm tw-flex-1"
                    @click="editBank(bank)"
                  />
                  <Button
                    icon="pi pi-trash"
                    label="Delete"
                    class="p-button-danger p-button-outlined p-button-sm tw-flex-1"
                    @click="confirmDeleteBank(bank)"
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-12 tw-rounded-xl tw-bg-gray-50 tw-border-2 tw-border-dashed tw-border-gray-300">
        <i class="pi pi-building tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
        <h4 class="tw-text-2xl tw-text-gray-800 tw-font-semibold tw-mb-2">No banks found</h4>
        <p class="tw-text-gray-500 tw-mb-8">Start by adding your first bank to see it here.</p>
        <Button icon="pi pi-plus" label="Add Bank" @click="openCreateModal" />
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

    <BankModal
      v-if="showModal"
      :bank="selectedBank"
      :is-editing="isEditing"
      @close="closeModal"
      @saved="handleBankSaved"
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
</style>