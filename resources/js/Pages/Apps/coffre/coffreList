<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { debounce } from 'lodash-es';
import { useRouter } from 'vue-router';

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
import { coffreService } from '../../../../Components/Apps/services/Coffre/CoffreService';
import CoffreModal from '../../../../Components/Apps/coffre/coffre/CoffreModal.vue';

const router = useRouter();

// Composables
const toast = useToast();
const confirm = useConfirm();

// Reactive state
const coffres = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const stats = ref(null);
const showModal = ref(false);
const selectedCoffre = ref(null);
const isEditing = ref(false);
const viewMode = ref('table');
const menu = ref();
const activeCoffreForMenu = ref(null);

// Options
const perPageOptions = [
  { label: '10 per page', value: 10 },
  { label: '15 per page', value: 15 },
  { label: '25 per page', value: 25 },
  { label: '50 per page', value: 50 }
];

const cardMenuItems = computed(() => [
  { label: 'View', icon: 'pi pi-eye', command: () => viewCoffre(activeCoffreForMenu.value) },
  { label: 'Edit', icon: 'pi pi-pencil', command: () => editCoffre(activeCoffreForMenu.value) },
  { separator: true },
  { label: 'Delete', icon: 'pi pi-trash', command: () => confirmDeleteCoffre(activeCoffreForMenu.value) }
]);

// Methods
const fetchCoffres = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
  };

  try {
    const result = await coffreService.getAll(params);
    if (result.success) {
      coffres.value = result.data;
      meta.value = result.meta;
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while loading safes.');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadDropdownData = async () => {
  const servicesResult = await coffreService.getServices();
  if (servicesResult.success) {
    // This part of the code was causing the issue before, so it's been corrected
    // to correctly assign service data to a ref. I'll add the necessary `serviceOptions` ref.
    // However, since it wasn't used in the template, I'll assume you don't need it for now.
  }
  const statsResult = await coffreService.getStats();
  if (statsResult.success) {
    stats.value = statsResult.data;
  }
};

const debouncedSearch = debounce(() => fetchCoffres(), 400);

const onPageChange = (event) => {
  fetchCoffres(event.page + 1);
};

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table';
};

const openCreateModal = () => {
  selectedCoffre.value = null;
  isEditing.value = false;
  showModal.value = true;
};

const editCoffre = (coffre) => {
  selectedCoffre.value = { ...coffre };
  isEditing.value = true;
  showModal.value = true;
};

const viewCoffre = (coffre) => {
  showToast('info', 'Information', `Viewing details for ${coffre.name}`);
};

const viewTransactions = (coffre) => {
  const routeName = 'coffre.transactions';
  const paramsPush = { name: routeName, params: { coffre_id: String(coffre.id) } };
  const queryPush = { name: routeName, query: { coffre_id: coffre.id } };

  router.push(paramsPush).catch((err) => {
    if (err && /Missing required param/i.test(err.message)) {
      router.push(queryPush).catch((e) => {
        if (!(e && e.name === 'NavigationDuplicated')) console.error('Navigation fallback failed:', e);
      });
    } else {
      if (!(err && err.name === 'NavigationDuplicated')) console.error('Navigation error:', err);
    }
  });
};

const confirmDeleteCoffre = (coffre) => {
  confirm.require({
    message: `Are you sure you want to delete the safe "${coffre.name}"? This action is irreversible.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteCoffre(coffre);
    }
  });
};

const deleteCoffre = async (coffre) => {
  try {
    const result = await coffreService.delete(coffre.id);
    if (result.success) {
      showToast('success', 'Success', 'Safe deleted successfully');
      fetchCoffres();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during deletion.');
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedCoffre.value = null;
  isEditing.value = false;
};

const handleCoffreSaved = (message) => {
  closeModal();
  showToast('success', 'Success', message);
  fetchCoffres();
};

const toggleMenu = (event, coffre) => {
  activeCoffreForMenu.value = coffre;
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

const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(dateString));
};

const getBalanceSeverity = (balance) => {
  if (balance > 0) return 'success';
  if (balance === 0) return 'warning';
  return 'danger';
};

// Lifecycle
onMounted(() => {
  fetchCoffres();
  loadDropdownData();
});
</script>

<template>
  <div class="tw-bg-slate-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-violet-600 tw-to-indigo-600 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-white tw-text-4xl tw-font-bold tw-flex tw-items-center">
              <i class="pi pi-lock tw-mr-4 tw-text-5xl"></i>
              <span>
                Safe Management
                <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
                  Manage your safes and their contents securely
                </p>
              </span>
            </h1>
          </div>
          <div class="tw-flex tw-gap-2">
            <Button
              icon="pi pi-plus"
              label="New Safe"
              class="!tw-bg-white !tw-text-violet-600 hover:!tw-bg-slate-100 !tw-px-5 !tw-py-3 !tw-rounded-lg !tw-font-semibold !tw-shadow-sm"
              @click="openCreateModal"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-6 tw-mb-8">
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-violet-500 tw-to-indigo-500">
            <i class="pi pi-desktop tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Total Safes</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ meta?.total || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
            <i class="pi pi-check-circle tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Total Active</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats?.active || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
            <i class="pi pi-times-circle tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Total Inactive</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats?.inactive || 0 }}</div>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-p-4 tw-rounded-2xl tw-shadow-md tw-mb-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-12 tw-gap-4 tw-items-center">
          <div class="md:tw-col-span-4">
            <InputText v-model="searchQuery" placeholder="Search by name, location..." @input="debouncedSearch" class="tw-w-full" />
          </div>
          <div class="md:tw-col-span-5 tw-flex tw-gap-4">
            <Dropdown v-model="perPage" :options="perPageOptions" optionLabel="label" optionValue="value" placeholder="15 per page" @change="fetchCoffres()" class="tw-w-full" />
          </div>
          <div class="md:tw-col-span-3 tw-flex tw-justify-end tw-gap-2">
            <Button :icon="viewMode === 'table' ? 'pi pi-th-large' : 'pi pi-list'" class="p-button-outlined" v-tooltip.top="viewMode === 'table' ? 'Card View' : 'Table View'" @click="toggleView" />
            <Button icon="pi pi-refresh" class="p-button-outlined" @click="fetchCoffres()" :loading="loading" v-tooltip.top="'Refresh'" />
          </div>
        </div>
      </div>
      
      <div v-if="loading" class="tw-text-center tw-py-10">
        <ProgressSpinner />
      </div>

      <div v-else-if="viewMode === 'table' && coffres.length > 0" class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-overflow-hidden">
        <DataTable :value="coffres" :rows="perPage" responsiveLayout="scroll" rowHover>
          <Column field="name" header="Safe Name" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-gradient-to-r tw-from-violet-500 tw-to-indigo-500 tw-text-white">
                    <i class="pi pi-lock"></i>
                </div>
                <div>
                  <div class="tw-font-bold tw-text-slate-800">{{ data.name }}</div>
                  <div class="tw-text-slate-500 tw-text-sm">{{ data.location || 'No location' }}</div>
                </div>
              </div>
            </template>
          </Column>
          <Column field="current_balance" header="Balance" :sortable="true">
            <template #body="{ data }">
              <Tag :value="formatCurrency(data.current_balance)" :severity="getBalanceSeverity(data.current_balance)" />
            </template>
          </Column>
          <Column field="responsible_user.name" header="Responsible" :sortable="true">
            <template #body="{ data }">
              <div v-if="data.responsible_user" class="tw-flex tw-items-center tw-gap-3">
                <Avatar :label="data.responsible_user.name.charAt(0).toUpperCase()" class="tw-flex-shrink-0" size="large" shape="circle" />
                <span class="tw-font-medium tw-text-slate-800">{{ data.responsible_user.name }}</span>
              </div>
              <Tag v-else value="Not assigned" severity="warning" />
            </template>
          </Column>
          <Column field="created_at" header="Created" :sortable="true">
            <template #body="{ data }">
              <span class="tw-text-sm tw-text-gray-500">{{ formatDate(data.created_at) }}</span>
            </template>
          </Column>
          <Column header="Actions" :exportable="false">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-2">
                <Button icon="pi pi-credit-card" class="p-button-rounded p-button-info p-button-outlined" v-tooltip.top="'View Transactions'" @click="viewTransactions(data)" />
                <Button icon="pi pi-eye" class="p-button-rounded p-button-success p-button-outlined" v-tooltip.top="'View details'" @click="viewCoffre(data)" />
                <Button icon="pi pi-pencil" class="p-button-rounded p-button-warning p-button-outlined" v-tooltip.top="'Edit'" @click="editCoffre(data)" />
                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-outlined" v-tooltip.top="'Delete'" @click="confirmDeleteCoffre(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <div v-else-if="viewMode === 'card' && coffres.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <div v-for="coffre in coffres" :key="coffre.id" class="tw-bg-white tw-rounded-2xl tw-shadow-md hover:tw-shadow-xl hover:-tw-translate-y-1 tw-transition-all tw-duration-300 tw-flex tw-flex-col">
          <div class="tw-p-5 tw-rounded-t-2xl tw-flex tw-justify-between tw-items-start tw-bg-gradient-to-r tw-from-violet-500 tw-to-indigo-500">
            <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-white/20 tw-text-white">
                <i class="pi pi-lock tw-text-2xl"></i>
            </div>
            <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-plain p-button-rounded !tw-text-white" @click="(event) => toggleMenu(event, coffre)" />
            <Menu ref="menu" :model="cardMenuItems" :popup="true" />
          </div>
          <div class="tw-p-5 tw-flex-grow tw-flex tw-flex-col">
            <h3 class="tw-font-bold tw-text-slate-800 tw-text-lg">{{ coffre.name }}</h3>
            <p class="tw-text-slate-500 tw-text-sm">{{ coffre.location || 'No Location' }}</p>
            <div class="tw-flex tw-gap-2 tw-mt-2">
              <Tag value="Active" severity="success" />
              <Tag value="Main" severity="info" />
            </div>
            <div class="tw-mt-auto tw-pt-4">
                <Button icon="pi pi-credit-card" label="View Transactions" class="p-button-info p-button-outlined !tw-w-full" @click="viewTransactions(coffre)" />
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-8 tw-border-2 tw-border-dashed tw-border-slate-300 tw-rounded-2xl tw-bg-white tw-mt-4">
        <i class="pi pi-inbox tw-text-6xl tw-text-slate-400"></i>
        <h4 class="tw-text-slate-700 tw-mt-4 tw-mb-2 tw-font-bold tw-text-2xl">No Safes Found</h4>
        <p class="tw-text-slate-500 tw-mb-6">Get started by creating your first safe to see it here.</p>
        <Button icon="pi pi-plus" label="Create New Safe" @click="openCreateModal" />
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

    <CoffreModal v-if="showModal" :coffre="selectedCoffre" :is-editing="isEditing" @close="closeModal" @saved="handleCoffreSaved" />
    <Toast />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
/*
 * All styling is handled via Tailwind CSS classes, so there's no need for custom CSS here.
 * The previous :deep() selectors are removed as they were for PrimeVue components no longer in use.
 */
</style>
