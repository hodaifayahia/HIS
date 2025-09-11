<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { debounce } from 'lodash-es';
import { useRouter } from 'vue-router';

// PrimeVue Components (only keeping what you specified)
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

// Services and Components
import { caisseService } from '../../../../Components/Apps/services/Coffre/CaisseService';
import CaisseModal from '../../../../Components/Apps/coffre/caisse/CaisseModal.vue';

// Composables
const toast = useToast();
const confirm = useConfirm();
const router = useRouter();

// Reactive state
const caisses = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const stats = ref(null);
const showModal = ref(false);
const selectedCaisse = ref(null);
const isEditing = ref(false);
const viewMode = ref('card');
const menu = ref();
const activeCaisseForMenu = ref(null);

const sortKey = ref('');
const sortOrder = ref(1);

// Filter states
const filters = ref({
  service_id: '',
  is_active: ''
});

// Options
const serviceOptions = ref([]);
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

// Computed properties
const sortedCaisses = computed(() => {
  if (!sortKey.value) {
    return caisses.value;
  }
  return [...caisses.value].sort((a, b) => {
    let valueA, valueB;
    if (sortKey.value.includes('.')) {
      const keys = sortKey.value.split('.');
      valueA = keys.reduce((o, k) => o?.[k], a);
      valueB = keys.reduce((o, k) => o?.[k], b);
    } else {
      valueA = a[sortKey.value];
      valueB = b[sortKey.value];
    }

    if (valueA < valueB) return -1 * sortOrder.value;
    if (valueA > valueB) return 1 * sortOrder.value;
    return 0;
  });
});

const cardMenuItems = computed(() => [
  {
    label: 'View Sessions',
    icon: 'pi pi-clock',
    command: () => openOperation(activeCaisseForMenu.value)
  },
  {
    label: 'Edit',
    icon: 'pi pi-pencil',
    command: () => editCaisse(activeCaisseForMenu.value)
  },
  {
    label: activeCaisseForMenu.value?.is_active ? 'Deactivate' : 'Activate',
    icon: activeCaisseForMenu.value?.is_active ? 'pi pi-pause' : 'pi pi-play',
    command: () => toggleStatus(activeCaisseForMenu.value)
  },
  { separator: true },
  {
    label: 'Delete',
    icon: 'pi pi-trash',
    command: () => confirmDeleteCaisse(activeCaisseForMenu.value)
  }
]);

// Methods
const onSort = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value * -1;
  } else {
    sortKey.value = key;
    sortOrder.value = 1;
  }
};

const fetchCaisses = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
    ...filters.value
  };

  try {
    const result = await caisseService.getAll(params);
    if (result.success) {
      caisses.value = result.data;
      meta.value = result.meta;
      if (result.stats) {
        stats.value = result.stats;
      }
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while loading cash registers.');
  } finally {
    loading.value = false;
  }
};

const loadDropdownData = async () => {
  const servicesResult = await caisseService.getServices();
  if (servicesResult.success) {
    serviceOptions.value = servicesResult.data;
  }
  const statsResult = await caisseService.getStats();
  if (statsResult.success) {
    stats.value = statsResult.data;
  }
};

const debouncedSearch = debounce(() => fetchCaisses(), 400);

const onPageChange = (event) => {
  fetchCaisses(event.page + 1);
};

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table';
};

const openCreateModal = () => {
  selectedCaisse.value = null;
  isEditing.value = false;
  showModal.value = true;
};

const editCaisse = (caisse) => {
  selectedCaisse.value = { ...caisse };
  isEditing.value = true;
  showModal.value = true;
};

const toggleStatus = async (caisse) => {
  try {
    const result = await caisseService.toggleStatus(caisse.id);
    if (result.success) {
      showToast('success', 'Success', `Cash register ${result.data.is_active ? 'activated' : 'deactivated'}`);
      fetchCaisses();
      loadDropdownData();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while updating status.');
  }
};

const confirmDeleteCaisse = (caisse) => {
  confirm.require({
    message: `Are you sure you want to delete "${caisse.name}"? This action is irreversible.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => deleteCaisse(caisse)
  });
};

const deleteCaisse = async (caisse) => {
  try {
    const result = await caisseService.delete(caisse.id);
    if (result.success) {
      showToast('success', 'Success', 'Cash register deleted successfully');
      fetchCaisses();
      loadDropdownData();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during deletion.');
  }
};

const closeModal = () => {
  showModal.value = false;
};

const handleCaisseSaved = (message) => {
  closeModal();
  showToast('success', 'Success', message);
  fetchCaisses();
  loadDropdownData();
};

const toggleMenu = (event, caisse) => {
  activeCaisseForMenu.value = caisse;
  menu.value.toggle(event);
};

const openOperation = (caisse) => {
  router.push({ name: 'coffre.caisse.operation', params: { caisse_id: caisse.id } });
};

const viewTransactions = (caisse) => {
  router.push({ name: 'coffre.caisse.financial-transactions', params: { caisse_id: caisse.id } });
};

const openCaisseDetails = (caisse) => {
  viewTransactions(caisse);
};

const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};

const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';
  return new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium' }).format(new Date(dateString));
};

onMounted(() => {
  fetchCaisses();
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
              <i class="pi pi-desktop tw-mr-4 tw-text-5xl"></i>
              <span>
                Cash Register Management
                <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
                  Oversee all cash registers and their operational status.
                </p>
              </span>
            </h1>
          </div>
          <div class="tw-flex tw-gap-2">
            <Button
              icon="pi pi-plus"
              label="New Register"
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
            <div class="tw-text-slate-500 tw-text-sm">Total Registers</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats?.total || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
            <i class="pi pi-check-circle tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Active</div>
            <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ stats?.active || 0 }}</div>
          </div>
        </div>
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-p-6 tw-flex tw-items-center tw-gap-4">
          <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
            <i class="pi pi-times-circle tw-text-2xl"></i>
          </div>
          <div>
            <div class="tw-text-slate-500 tw-text-sm">Inactive</div>
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
            <Dropdown v-model="filters.service_id" :options="serviceOptions" optionLabel="name" optionValue="id" placeholder="Filter by Service" @change="fetchCaisses" showClear class="tw-w-full" />
            <Dropdown v-model="filters.is_active" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Filter by Status" @change="fetchCaisses" showClear class="tw-w-full" />
          </div>
          <div class="md:tw-col-span-3 tw-flex tw-justify-end tw-gap-2">
            <Button :icon="viewMode === 'table' ? 'pi pi-th-large' : 'pi pi-list'" class="p-button-outlined" v-tooltip.top="viewMode === 'table' ? 'Card View' : 'Table View'" @click="toggleView" />
            <Button icon="pi pi-refresh" class="p-button-outlined" @click="fetchCaisses" :loading="loading" v-tooltip.top="'Refresh'" />
          </div>
        </div>
      </div>
      
      <div v-if="loading" class="tw-text-center tw-py-10">
        <ProgressSpinner />
      </div>

      <div v-else-if="viewMode === 'table' && caisses.length > 0" class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-overflow-hidden">
        <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
          <thead class="tw-bg-gray-50">
            <tr>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase tw-cursor-pointer" @click="onSort('name')">
                Cash Register
                <i :class="{'pi-sort-alpha-down': sortKey === 'name' && sortOrder === 1, 'pi-sort-alpha-up': sortKey === 'name' && sortOrder === -1, 'pi-sort': sortKey !== 'name'}" class="pi tw-ml-2"></i>
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase tw-cursor-pointer" @click="onSort('service.name')">
                Service
                <i :class="{'pi-sort-alpha-down': sortKey === 'service.name' && sortOrder === 1, 'pi-sort-alpha-up': sortKey === 'service.name' && sortOrder === -1, 'pi-sort': sortKey !== 'service.name'}" class="pi tw-ml-2"></i>
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase tw-cursor-pointer" @click="onSort('is_active')">
                Status
                <i :class="{'pi-sort-numeric-down': sortKey === 'is_active' && sortOrder === 1, 'pi-sort-numeric-up': sortKey === 'is_active' && sortOrder === -1, 'pi-sort': sortKey !== 'is_active'}" class="pi tw-ml-2"></i>
              </th>
              <th scope="col" class="tw-px-6 tw-py-3 tw-text-right tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="tw-bg-white tw-divide-y tw-divide-gray-200">
            <tr v-for="caisse in sortedCaisses" :key="caisse.id" class="hover:tw-bg-violet-50 tw-transition-colors tw-duration-200">
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-gradient-to-r tw-from-violet-500 tw-to-indigo-500 tw-text-white">
                    <i class="pi pi-desktop"></i>
                  </div>
                  <div>
                    <div class="tw-font-bold tw-text-slate-800 tw-cursor-pointer hover:tw-text-violet-600 tw-transition-colors" @click="openCaisseDetails(caisse)">
                      {{ caisse.name }}
                    </div>
                    <div class="tw-text-slate-500 tw-text-sm tw-cursor-pointer hover:tw-text-violet-600 tw-transition-colors" @click="openCaisseDetails(caisse)">
                      {{ caisse.location || 'No location' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <Tag :value="caisse.service?.name || 'N/A'" severity="info" />
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                <Tag :value="caisse.is_active ? 'Active' : 'Inactive'" :severity="caisse.is_active ? 'success' : 'danger'" />
              </td>
              <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-right tw-text-sm tw-font-medium">
                <div class="tw-flex tw-justify-end tw-gap-2">
                  <Button icon="pi pi-clock" class="p-button-rounded p-button-info p-button-outlined" v-tooltip.top="'View Sessions'" @click="openOperation(caisse)" />
                  <Button icon="pi pi-credit-card" class="p-button-rounded p-button-secondary p-button-outlined" v-tooltip.top="'Transactions'" @click="viewTransactions(caisse)" />
                  <Button icon="pi pi-pencil" class="p-button-rounded p-button-warning p-button-outlined" v-tooltip.top="'Edit'" @click="editCaisse(caisse)" />
                  <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-outlined" v-tooltip.top="'Delete'" @click="confirmDeleteCaisse(caisse)" />
                  <Button 
                  :icon="caisse.is_active ? 'pi pi-pause' : 'pi pi-play'" 
                  :class="caisse.is_active ? 'p-button-warning' : 'p-button-success'"
                  class="p-button-rounded p-button-sm" 
                  v-tooltip.top="caisse.is_active ? 'Deactivate' : 'Activate'" 
                  @click="toggleStatus(caisse)" 
                />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="viewMode === 'card' && caisses.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <div v-for="caisse in caisses" :key="caisse.id" class="tw-bg-white tw-rounded-2xl tw-shadow-md hover:tw-shadow-xl hover:-tw-translate-y-1 tw-transition-all tw-duration-300 tw-flex tw-flex-col tw-cursor-pointer" @click="openCaisseDetails(caisse)">
          <div class="tw-p-5 tw-rounded-t-2xl tw-flex tw-justify-between tw-items-start" :class="caisse.is_active ? 'tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500' : 'tw-bg-gradient-to-r tw-from-slate-500 tw-to-slate-600'">
            <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-bg-white/20 tw-text-white">
                <i class="pi pi-desktop tw-text-2xl"></i>
            </div>
            <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-plain p-button-rounded !tw-text-white" @click.stop="(event) => toggleMenu(event, caisse)" />
            <Menu ref="menu" :model="cardMenuItems" :popup="true" />
          </div>
          <div class="tw-p-5 tw-flex-grow tw-flex tw-flex-col">
            <h3 class="tw-font-bold tw-text-slate-800 tw-text-lg">{{ caisse.name }}</h3>
            <p class="tw-text-slate-500 tw-text-sm">{{ caisse.location || 'No Location' }}</p>
            <div class="tw-flex tw-gap-2 tw-mt-2">
              <Tag :value="caisse.is_active ? 'Active' : 'Inactive'" :severity="caisse.is_active ? 'success' : 'danger'" />
              <Tag :value="caisse.service?.name || 'N/A'" severity="info" />
            </div>
            <div class="tw-mt-auto tw-pt-4">
                <Button icon="pi pi-clock" label="View Sessions" class="p-button-info p-button-outlined !tw-w-full" @click.stop="openOperation(caisse)" />
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-8 tw-border-2 tw-border-dashed tw-border-slate-300 tw-rounded-2xl tw-bg-white tw-mt-4">
        <i class="pi pi-inbox tw-text-6xl tw-text-slate-400"></i>
        <h4 class="tw-text-slate-700 tw-mt-4 tw-mb-2 tw-font-bold tw-text-2xl">No Cash Registers Found</h4>
        <p class="tw-text-slate-500 tw-mb-6">Get started by creating a new cash register.</p>
        <Button icon="pi pi-plus" label="Create New Register" @click="openCreateModal" />
      </div>

      <Paginator
        v-if="!loading && meta && meta.total > perPage"
        :rows="perPage"
        :total-records="meta.total"
        @page="onPageChange"
        class="tw-mt-6"
      />
    </div>

    <CaisseModal v-if="showModal" :caisse="selectedCaisse" :is-editing="isEditing" @close="closeModal" @saved="handleCaisseSaved" />
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