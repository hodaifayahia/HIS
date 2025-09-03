<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { debounce } from 'lodash-es';

// PrimeVue Components (only keeping what you specified)
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import ProgressSpinner from 'primevue/progressspinner';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Menu from 'primevue/menu';
import Card from 'primevue/card';

// Services and Components
import { caisseSessionService } from '../../../../Components/Apps/services/Coffre/caisseSessionService';
import { caisseService } from '../../../../Components/Apps/services/Coffre/CaisseService';
import CaisseSessionModal from '../../../../Components/Apps/coffre/caisse/CaisseSessionModal.vue';
import CaisseSessionCloseModal from '../../../../Components/Apps/coffre/caisse/CaisseSessionCloseModal.vue';
import CaisseSessionViewModal from '../../../../Components/Apps/coffre/caisse/CaisseSessionViewModal.vue';

// Props (route passes caisse_id as 'id' prop)
const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
});

// Composables
const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

// Get caisse ID from props or route params
const caisseId = ref(props.id ? parseInt(props.id) : (route.params.caisse_id ? parseInt(route.params.caisse_id) : null));
const caisseInfo = ref(null);
const currentSession = ref(null);

// Reactive state
const sessions = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const stats = ref(null);
const showModal = ref(false);
const showCloseModal = ref(false);
const selectedSession = ref(null);
const selectedViewSession = ref(null);
const showViewModal = ref(false);

const viewMode = ref('card');
const menu = ref();
const activeSessionForMenu = ref(null);
const dateRange = ref(null);

const sortKey = ref(null);
const sortOrder = ref(1);

// Filter states
const filters = ref({
  caisse_id: caisseId.value,
  user_id: '',
  status: '',
  date_from: '',
  date_to: ''
});

// Options
const statusOptions = [
  { label: 'Open', value: 'open' },
  { label: 'Closed', value: 'closed' },
];

const perPageOptions = [
  { label: '10 per page', value: 10 },
  { label: '15 per page', value: 15 },
  { label: '25 per page', value: 25 },
  { label: '50 per page', value: 50 }
];

// Computed properties
const hasActiveSession = computed(() => {
  return sessions.value.some(session => session.status === 'open');
});

const todaySessionsCount = computed(() => {
  const today = new Date().toDateString();
  return sessions.value.filter(session =>
    new Date(session.ouverture_at).toDateString() === today
  ).length;
});

const averageDuration = computed(() => {
  const closedSessions = sessions.value.filter(s => s.status === 'closed' && s.duration);
  if (closedSessions.length === 0) return '0h 0m';
  return '2h 30m'; // Placeholder calculation
});

const totalVariance = computed(() => {
  return sessions.value
    .filter(s => s.cash_difference !== null)
    .reduce((total, s) => total + (s.cash_difference || 0), 0);
});

const sortedSessions = computed(() => {
  if (!sortKey.value) {
    return sessions.value;
  }
  return [...sessions.value].sort((a, b) => {
    const valueA = a[sortKey.value];
    const valueB = b[sortKey.value];
    if (valueA < valueB) return -1 * sortOrder.value;
    if (valueA > valueB) return 1 * sortOrder.value;
    return 0;
  });
});

const onSort = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value * -1;
  } else {
    sortKey.value = key;
    sortOrder.value = 1;
  }
};

const cardMenuItems = computed(() => [
  {
    label: activeSessionForMenu.value?.status === 'open' ? 'Close Session' : 'View Details',
    icon: activeSessionForMenu.value?.status === 'open' ? 'pi pi-stop' : 'pi pi-eye',
    command: () => activeSessionForMenu.value?.status === 'open' ? openCloseModal(activeSessionForMenu.value) : viewSession(activeSessionForMenu.value)
  },
  { separator: true, visible: activeSessionForMenu.value?.status === 'closed' },
  {
    label: 'Delete',
    icon: 'pi pi-trash',
    command: () => confirmDeleteSession(activeSessionForMenu.value),
    visible: activeSessionForMenu.value?.status === 'closed'
  }
].filter(item => item.visible !== false));

// Methods
const loadCaisseInfo = async () => {
  try {
    const result = await caisseService.getById(caisseId.value);
    if (result.success) {
      caisseInfo.value = result.data;
    } else {
      showToast('error', 'Error', 'Failed to load cash register information');
      goBack();
    }
  } catch (error) {
    console.error('Error loading caisse info:', error);
    showToast('error', 'Error', 'Failed to load cash register information');
    goBack();
  }
};

const fetchSessions = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
    caisse_id: caisseId.value,
    ...filters.value
  };

  try {
    const result = await caisseSessionService.getAll(params);
    if (result.success) {
      sessions.value = result.data || [];
      meta.value = result.meta;
      currentSession.value = sessions.value.find(s => s.status === 'open') || null;
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while loading sessions.');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'coffre.caisse' });
};

const debouncedSearch = debounce(() => fetchSessions(), 400);

const onPageChange = (event) => {
  fetchSessions(event.page + 1);
};

const onDateRangeChange = () => {
  if (dateRange.value && dateRange.value.length === 2) {
    filters.value.date_from = dateRange.value[0].toISOString().split('T')[0];
    filters.value.date_to = dateRange.value[1].toISOString().split('T')[0];
  } else {
    filters.value.date_from = '';
    filters.value.date_to = '';
  }
  fetchSessions();
};

const toggleView = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table';
};

const openCreateModal = () => {
  selectedSession.value = null;
  showModal.value = true;
};

const openCloseModal = (session) => {
  selectedSession.value = { ...session };
  showCloseModal.value = true;
};

const confirmDeleteSession = (session) => {
  confirm.require({
    message: `Are you sure you want to delete this session? This action is irreversible.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteSession(session);
    }
  });
};

const deleteSession = async (session) => {
  try {
    const result = await caisseSessionService.delete(session.id);
    if (result.success) {
      showToast('success', 'Success', 'Session deleted successfully');
      fetchSessions();
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred during deletion.');
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedSession.value = null;
};

const closeCloseModal = () => {
  showCloseModal.value = false;
  selectedSession.value = null;
};

const handleSessionSaved = (message) => {
  closeModal();
  showToast('success', 'Success', message);
  fetchSessions();
};

const handleSessionClosed = (message) => {
  closeCloseModal();
  showToast('success', 'Success', message);
  fetchSessions();
};

const toggleCardMenu = (event, session) => {
  activeSessionForMenu.value = session;
};

const viewSession = async (session) => {
  try {
    if (session?.id) {
      const res = await caisseSessionService.getById(session.id);
      if (res && res.success) {
        selectedViewSession.value = res.data;
      } else {
        selectedViewSession.value = session;
      }
    } else {
      selectedViewSession.value = session;
    }
    showViewModal.value = true;
  } catch (error) {
    console.error('Failed to load session details:', error);
    selectedViewSession.value = session;
    showViewModal.value = true;
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedViewSession.value = null;
};

// Utility methods for PrimeVue and custom styling
const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};

const getStatusIcon = (status) => {
  const iconMap = {
    open: 'pi-play-circle',
    closed: 'pi-check-circle',
    suspended: 'pi-exclamation-circle'
  };
  return `pi ${iconMap[status] || 'pi-circle'}`;
};

const getStatusColor = (status) => {
  const colorMap = {
    open: 'tw-text-emerald-500',
    closed: 'tw-text-blue-500',
    suspended: 'tw-text-amber-500'
  };
  return colorMap[status] || 'tw-text-gray-500';
};

const getTagSeverity = (status) => {
  const severityMap = {
    open: 'tw-bg-emerald-500',
    closed: 'tw-bg-blue-500',
    suspended: 'tw-bg-amber-500'
  };
  return severityMap[status] || 'tw-bg-gray-500';
};

const getVarianceClass = (variance) => {
  if (variance > 0) return 'tw-text-emerald-500';
  if (variance < 0) return 'tw-text-red-500';
  return 'tw-text-gray-500';
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
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

// Watch for prop changes
watch(() => props.id, (newId) => {
  if (newId) {
    caisseId.value = parseInt(newId);
    filters.value.caisse_id = caisseId.value;
    loadCaisseInfo();
    fetchSessions();
  }
});

// Lifecycle
onMounted(() => {
  if (caisseId.value) {
    loadCaisseInfo();
    fetchSessions();
  } else {
    showToast('error', 'Error', 'No caisse ID provided');
    goBack();
  }
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <header class="tw-bg-gradient-to-br tw-from-amber-500 tw-to-amber-700 tw-p-10 tw-rounded-b-[20px]">
      <div class="tw-container tw-mx-auto tw-px-4">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-flex-col tw-items-center md:tw-items-start">
            <h1 class="tw-flex tw-items-center tw-gap-4 tw-text-white tw-text-4xl tw-font-bold tw-m-0">
              <i class="pi pi-clock tw-text-5xl tw-opacity-90"></i>
              <div class="tw-flex tw-flex-col">
                <span v-if="caisseInfo">{{ caisseInfo.name }} <small class="tw-text-white tw-opacity-70 tw-text-base">/ {{ caisseInfo.location || '—'
                    }}</small></span>
                <div v-if="currentSession" class="tw-mt-1 tw-text-sm">
                  <span class="tw-text-white tw-opacity-70">Status:</span>
                  <strong class="tw-ml-1" :class="getStatusColor(currentSession.status)">{{ currentSession.status }}</strong>
                </div>
              </div>
            </h1>
            <div class="tw-text-white tw-opacity-90 tw-mt-2 tw-text-sm" v-if="currentSession">
              Opened by: <strong>{{ currentSession.opened_by?.name ?? currentSession.user?.name ?? currentSession.open_by ?? '—' }}</strong>
              <span v-if="currentSession.closed_by"> — Closed by: <strong>{{ currentSession.closed_by?.name ?? currentSession.closed_by ?? '—' }}</strong></span>
            </div>
          </div>
          <div class="tw-flex tw-gap-4 tw-items-center">
            <Button icon="pi pi-arrow-left" label="Back to Cash Registers" class="p-button-secondary p-button-lg" @click="goBack" />
            <Button icon="pi pi-plus" label="Open New Session" class="p-button-light p-button-lg" @click="openCreateModal" :disabled="hasActiveSession" />
          </div>
        </div>
      </div>
    </header>

    <main class="tw-container tw-mx-auto tw-px-4 tw-py-8">
      <Card class="tw-mb-8 tw-border-none tw-rounded-[15px] tw-shadow-lg" v-if="caisseInfo">
        <template #content>
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-8 tw-items-start">
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-gap-6">
              <div class="tw-w-20 tw-h-20 tw-bg-gradient-to-br tw-from-amber-500 tw-to-amber-700 tw-rounded-[15px] tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-flex-shrink-0">
                <i class="pi pi-desktop"></i>
              </div>
              <div class="tw-text-center sm:tw-text-left">
                <h3 class="tw-text-2xl tw-font-semibold tw-text-gray-800 tw-m-0">{{ caisseInfo.name }}</h3>
                <p class="tw-text-gray-500 tw-mt-2 tw-m-0">{{ caisseInfo.location || 'No location specified' }}</p>
                <div class="tw-mt-4">
                  <Tag :value="caisseInfo.is_active ? 'Active' : 'Inactive'"
                    :severity="caisseInfo.is_active ? 'success' : 'danger'"
                    :icon="caisseInfo.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'" />
                </div>
              </div>
            </div>
            <div v-if="currentSession" class="tw-p-6 tw-bg-gray-50 tw-rounded-xl">
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4">Current Session</h4>
              <div class="tw-flex tw-flex-col tw-gap-2">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-sm tw-text-gray-500 tw-font-medium">Opened:</span>
                  <span class="tw-text-base tw-font-semibold tw-text-gray-800">{{ formatDateTime(currentSession.ouverture_at) }}</span>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-sm tw-text-gray-500 tw-font-medium">User:</span>
                  <span class="tw-text-base tw-font-semibold tw-text-gray-800">{{ currentSession.user?.name }}</span>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-sm tw-text-gray-500 tw-font-medium">Opening Amount:</span>
                  <span class="tw-text-base tw-font-semibold tw-text-gray-800">{{ formatCurrency(currentSession.opening_amount) }}</span>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-sm tw-text-gray-500 tw-font-medium">Duration:</span>
                  <span class="tw-text-base tw-font-semibold tw-text-gray-800">{{ currentSession.duration }}</span>
                </div>
              </div>
              <div class="tw-mt-4 tw-flex tw-gap-3 tw-justify-end">
                <Button icon="pi pi-stop" label="Close Session" class="p-button-danger p-button-sm" @click="openCloseModal(currentSession)" />
              </div>
            </div>
            <div v-else class="tw-col-span-1 lg:tw-col-span-2 tw-flex tw-flex-col tw-items-center tw-justify-center tw-min-h-[150px]">
              <i class="pi pi-info-circle tw-text-4xl tw-text-gray-500 tw-mb-4"></i>
              <p class="tw-text-gray-500 tw-mb-4">No active session for this cash register</p>
              <Button icon="pi pi-play" label="Start New Session" class="p-button-success p-button-sm" @click="openCreateModal" />
            </div>
          </div>
        </template>
      </Card>

      <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-border-none tw-rounded-[15px] tw-shadow-lg">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-[50px] tw-h-[50px] tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-700">
                <i class="pi pi-chart-bar"></i>
              </div>
              <div>
                <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Sessions</div>
                <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ meta?.total || 0 }}</div>
              </div>
            </div>
          </template>
        </Card>
        <Card class="tw-border-none tw-rounded-[15px] tw-shadow-lg">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-[50px] tw-h-[50px] tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-br tw-from-emerald-500 tw-to-emerald-700">
                <i class="pi pi-calendar"></i>
              </div>
              <div>
                <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Today's Sessions</div>
                <div class="tw-text-2xl tw-font-bold tw-text-emerald-500">{{ todaySessionsCount }}</div>
              </div>
            </div>
          </template>
        </Card>
        <Card class="tw-border-none tw-rounded-[15px] tw-shadow-lg">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-[50px] tw-h-[50px] tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-br tw-from-violet-500 tw-to-violet-700">
                <i class="pi pi-clock"></i>
              </div>
              <div>
                <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Avg Duration</div>
                <div class="tw-text-2xl tw-font-bold tw-text-violet-500">{{ averageDuration }}</div>
              </div>
            </div>
          </template>
        </Card>
        <Card class="tw-border-none tw-rounded-[15px] tw-shadow-lg">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-[50px] tw-h-[50px] tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-br tw-from-amber-500 tw-to-amber-700">
                <i class="pi pi-exclamation-triangle"></i>
              </div>
              <div>
                <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Variance</div>
                <div class="tw-text-2xl tw-font-bold" :class="totalVariance >= 0 ? 'tw-text-green-500' : 'tw-text-red-500'">
                  {{ formatCurrency(totalVariance) }}
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <Card class="tw-mb-8 tw-border-none tw-rounded-[15px] tw-shadow-lg">
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6 tw-items-center">
            <div class="tw-col-span-1 lg:tw-col-span-2">
              <div class="p-inputgroup">
                <span class="p-inputgroup-addon tw-rounded-l-xl">
                  <i class="pi pi-search"></i>
                </span>
                <InputText v-model="searchQuery" placeholder="Search by notes, user..." @input="debouncedSearch"
                  appendTo="self" class="tw-w-full" />
              </div>
            </div>
            <div class="tw-flex tw-flex-wrap tw-gap-4 tw-items-center tw-justify-between">
              <Dropdown v-model="filters.status" :options="statusOptions" option-label="label" option-value="value"
                appendTo="self" placeholder="All Status" class="tw-min-w-[150px] tw-flex-1" @change="fetchSessions" showClear />
              <Calendar v-model="dateRange" selection-mode="range" :manual-input="false" date-format="yy-mm-dd"
                placeholder="Select Date Range" class="tw-min-w-[150px] tw-flex-1" @date-select="onDateRangeChange" showIcon />
              <Dropdown v-model="perPage" :options="perPageOptions" option-label="label" option-value="value"
                @change="() => fetchSessions()" class="tw-flex-1" />
              <Button :icon="viewMode === 'table' ? 'pi pi-th-large' : 'pi pi-list'" class="p-button-outlined"
                v-tooltip.top="viewMode === 'table' ? 'Card View' : 'Table View'" @click="toggleView" />
              <Button icon="pi pi-refresh" class="p-button-outlined" @click="() => fetchSessions()" :loading="loading" v-tooltip.top="'Refresh'" />
            </div>
          </div>
        </template>
      </Card>

      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
        <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
      </div>

      <div v-else-if="viewMode === 'table' && sessions.length > 0">
        <div class="tw-bg-white tw-rounded-xl tw-overflow-hidden tw-shadow-lg">
          <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
            <thead class="tw-bg-gray-50">
              <tr>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Opened By</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Closed By</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Status</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Opening</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Closing</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Variance</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Opened</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Duration</th>
                <th scope="col" class="tw-px-6 tw-py-3 tw-text-right tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="tw-bg-white tw-divide-y tw-divide-gray-200">
              <tr v-for="session in sortedSessions" :key="session.id" class="hover:tw-bg-amber-50 tw-transition-colors tw-duration-200">
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-text-sm tw-text-gray-900">{{ session.opened_by?.name ?? session.open_by ?? '—' }}</div>
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-text-sm tw-text-gray-900">{{ session.closed_by?.name ?? session.closed_by ?? '—' }}</div>
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <Tag :value="session.status_text" :severity="session.status_color" :icon="getStatusIcon(session.status)" />
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-font-semibold tw-text-sm tw-text-emerald-500">{{ formatCurrency(session.opening_amount) }}</div>
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div v-if="session.closing_amount" class="tw-font-semibold tw-text-sm tw-text-blue-500">{{ formatCurrency(session.closing_amount) }}</div>
                  <span v-else class="tw-text-gray-400">—</span>
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div v-if="session.cash_difference !== null" class="tw-font-semibold tw-text-sm" :class="getVarianceClass(session.cash_difference)">
                    {{ formatCurrency(session.cash_difference) }}
                  </div>
                  <span v-else class="tw-text-gray-400">—</span>
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-text-sm tw-text-gray-500">{{ formatDateTime(session.ouverture_at) }}</div>
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-text-sm tw-text-gray-500">{{ session.duration || 'Ongoing' }}</div>
                </td>
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-right tw-text-sm tw-font-medium">
                  <div class="tw-flex tw-justify-end tw-gap-2">
                    <Button icon="pi pi-eye" class="p-button-sm p-button-text" v-tooltip.top="'View session details'" @click="viewSession(session)" />
                    <Button icon="pi pi-stop" class="p-button-sm p-button-danger" v-if="session.status === 'open'" v-tooltip.top="'Close session'" @click="openCloseModal(session)" />
                    <Button icon="pi pi-trash" class="p-button-sm p-button-danger" v-if="session.status === 'closed'" v-tooltip.top="'Delete session'" @click="confirmDeleteSession(session)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else-if="viewMode === 'card' && sessions.length > 0"
        class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <div v-for="session in sessions" :key="session.id" class="tw-w-full">
          <Card class="tw-h-full tw-border-none tw-rounded-[15px] tw-shadow-lg hover:tw-translate-y-[-5px] hover:tw-shadow-xl tw-transition-all tw-ease-in-out tw-duration-300"
            :class="[session.status === 'suspended' ? 'tw-opacity-80' : '']">
            <template #header>
              <div class="tw-p-6 tw-rounded-t-[15px] tw-text-white"
                :class="{ 'tw-bg-gradient-to-br tw-from-emerald-500 tw-to-emerald-700': session.status === 'open', 'tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-700': session.status === 'closed', 'tw-bg-gradient-to-br tw-from-amber-500 tw-to-amber-700': session.status === 'suspended' }">
                <div class="tw-flex tw-justify-between tw-items-start">
                  <div class="tw-w-[50px] tw-h-[50px] tw-bg-white/20 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-xl">
                    <i :class="getStatusIcon(session.status)"></i>
                  </div>
                  <div class="tw-flex-1 tw-text-center tw-mx-4">
                    <div class="tw-font-semibold tw-text-lg tw-mb-2">{{ session.user?.name }}</div>
                    <Tag :value="session.status_text" :severity="session.status_color" />
                  </div>
                </div>
              </div>
            </template>

            <template #content>
              <div class="tw-p-6">
                <div class="tw-bg-gray-100 tw-rounded-lg tw-p-4 tw-mb-6">
                  <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
                    <span class="tw-text-sm tw-text-gray-500 tw-font-medium">Opening:</span>
                    <span class="tw-font-semibold tw-text-base tw-text-emerald-500">{{ formatCurrency(session.opening_amount) }}</span>
                  </div>
                  <div v-if="session.closing_amount" class="tw-flex tw-justify-between tw-items-center tw-mb-2">
                    <span class="tw-text-sm tw-text-gray-500 tw-font-medium">Closing:</span>
                    <span class="tw-font-semibold tw-text-base tw-text-blue-500">{{ formatCurrency(session.closing_amount) }}</span>
                  </div>
                  <div v-if="session.cash_difference !== null" class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-sm tw-text-gray-500 tw-font-medium">Variance:</span>
                    <span class="tw-font-semibold tw-text-base" :class="getVarianceClass(session.cash_difference)">
                      {{ formatCurrency(session.cash_difference) }}
                    </span>
                  </div>
                </div>
                <div class="tw-flex tw-flex-col tw-gap-3">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                    <i class="pi pi-calendar tw-text-amber-500 tw-w-4 tw-flex-shrink-0"></i>
                    <span class="tw-font-medium tw-text-gray-700 tw-min-w-[60px]">Opened:</span>
                    <span class="tw-text-gray-500 tw-flex-1">{{ formatDateTime(session.ouverture_at) }}</span>
                  </div>
                  <div v-if="session.cloture_at" class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                    <i class="pi pi-check tw-text-amber-500 tw-w-4 tw-flex-shrink-0"></i>
                    <span class="tw-font-medium tw-text-gray-700 tw-min-w-[60px]">Closed:</span>
                    <span class="tw-text-gray-500 tw-flex-1">{{ formatDateTime(session.cloture_at) }}</span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                    <i class="pi pi-clock tw-text-amber-500 tw-w-4 tw-flex-shrink-0"></i>
                    <span class="tw-font-medium tw-text-gray-700 tw-min-w-[60px]">Duration:</span>
                    <span class="tw-text-gray-500 tw-flex-1">{{ session.duration || 'Ongoing' }}</span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm" v-if="session.coffre">
                    <i class="pi pi-lock tw-text-amber-500 tw-w-4 tw-flex-shrink-0"></i>
                    <span class="tw-font-medium tw-text-gray-700 tw-min-w-[60px]">Safe:</span>
                    <span class="tw-text-gray-500 tw-flex-1">{{ session.coffre.name }}</span>
                  </div>
                </div>
              </div>
            </template>

            <template #footer>
              <div class="tw-flex tw-gap-3 tw-p-6 tw-border-t tw-border-gray-200">
                <Button v-if="session.status === 'open'" icon="pi pi-stop" label="Close Session" class="p-button-success p-button-outlined p-button-sm tw-flex-1" @click="openCloseModal(session)" />
                <Button v-else icon="pi pi-eye" label="View Details" class="p-button-info p-button-outlined p-button-sm tw-flex-1" @click="viewSession(session)" />
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div v-else-if="!loading" class="tw-flex tw-justify-center tw-items-center tw-min-h-[40vh]">
        <div class="tw-text-center tw-p-12 tw-rounded-[15px] tw-bg-gray-50 tw-border-2 tw-border-dashed tw-border-gray-300">
          <i class="pi pi-clock tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
          <h4 class="tw-text-2xl tw-text-gray-800 tw-font-semibold tw-mb-2">No sessions found for this cash register</h4>
          <p class="tw-text-gray-500 tw-mb-8">Start by opening your first session to see it here.</p>
          <Button icon="pi pi-plus" label="Open New Session" @click="openCreateModal" />
        </div>
      </div>

      <Paginator v-if="!loading && meta && meta.total > perPage" :rows="perPage" :total-records="meta.total" :rows-per-page-options="[10, 15, 25, 50]" @page="onPageChange"
        template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        current-page-report-template="Showing {first} to {last} of {totalRecords} entries" class="tw-mt-8" />
    </main>

    <CaisseSessionModal v-if="showModal" :session="selectedSession" :caisse-id="caisseId" :is-editing="isEditing" @close="closeModal" @saved="handleSessionSaved" />
    <CaisseSessionCloseModal v-if="showCloseModal" :session="selectedSession" @close="closeCloseModal" @saved="handleSessionClosed" />
    <CaisseSessionViewModal v-if="showViewModal" :session="selectedViewSession" @close="closeViewModal" />

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
/* Scoped CSS overrides for PrimeVue components, now cleaner with Tailwind classes */
/* Table styling */
.datatable-header th {
  @apply tw-bg-gray-50 tw-text-gray-700 tw-font-semibold tw-border-none tw-text-sm;
}
.datatable-row:hover {
  @apply tw-bg-amber-50;
}

/* Card styling */
:deep(.p-card-header) {
  @apply tw-p-0;
}
:deep(.p-card-content) {
  @apply tw-p-0;
}

/* Custom classes for conditional styling */
.variance-positive {
  @apply tw-text-emerald-500;
}
.variance-negative {
  @apply tw-text-red-500;
}
.variance-neutral {
  @apply tw-text-gray-500;
}
</style>