<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import ProgressSpinner from 'primevue/progressspinner'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Menu from 'primevue/menu'
import Dialog from 'primevue/dialog'
import Chip from 'primevue/chip'

import { ficheNavetteService } from '../../../Components/Apps/services/Reception/ficheNavetteService'

// Composables
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const route = useRoute()

// Use route param (or fallback to query) and keep it reactive
const caisseSessionId = ref(route.params?.caisse_session_id ?? route.query?.caisse_session_id ?? null)
const caisse_id = ref(route.params?.caisse_id ?? route.query?.caisse_id ?? null)

// Reactive data
const ficheNavettes = ref([])
const sessionInfo = ref(null) // session info to display in header
const loading = ref(false)
const totalRecords = ref(0)
const searchQuery = ref('')
const showModal = ref(false)
const selectedFiche = ref(null)
const modalMode = ref('create')
const showFilters = ref(false)
const selectedDateRange = ref(null)
const selectedStatus = ref(null)
const selectedCreator = ref(null)

// Pagination
const lazyParams = ref({
  first: 0,
  rows: 20,
  page: 1
})

// Computed properties
const hasActiveFilters = computed(() => {
  return selectedDateRange.value || selectedStatus.value || selectedCreator.value || searchQuery.value
})

const totalActiveFilters = computed(() => {
  let count = 0
  if (selectedDateRange.value) count++
  if (selectedStatus.value) count++
  if (selectedCreator.value) count++
  if (searchQuery.value) count++
  return count
})

// Static data
const statusOptions = [
  { label: 'All Statuses', value: null },
  { label: 'Pending', value: 'pending', severity: 'warning', icon: 'pi pi-clock' },
  { label: 'In Progress', value: 'in_progress', severity: 'info', icon: 'pi pi-spin pi-spinner' },
  { label: 'Completed', value: 'completed', severity: 'success', icon: 'pi pi-check-circle' },
  { label: 'Cancelled', value: 'cancelled', severity: 'danger', icon: 'pi pi-times-circle' }
]

const rowsPerPageOptions = [10, 20, 50, 100]

// Mock creators data - replace with actual API call
const creatorsOptions = ref([
  { label: 'All Creators', value: null },
  { label: 'Dr. Martin', value: 'martin' },
  { label: 'Dr. Sarah', value: 'sarah' },
  { label: 'Dr. Ahmed', value: 'ahmed' }
])

// --- OPTIMIZED CODE START ---
// Debounce function to limit API calls
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

const debouncedSearch = debounce(() => {
  lazyParams.value.first = 0;
  lazyParams.value.page = 1;
  loadFicheNavettes();
}, 500);

const loadFicheNavettes = async () => {
  loading.value = true;
  try {
    const params = {
      page: lazyParams.value.page,
      per_page: lazyParams.value.rows,
      search: searchQuery.value,
      status: selectedStatus.value,
      creator: selectedCreator.value,
      date_from: selectedDateRange.value?.[0],
      date_to: selectedDateRange.value?.[1],
      caisse_session_id: caisseSessionId.value
    };

    const result = await ficheNavetteService.getAll(params);

    if (!result.success) {
      toast.add({ severity: 'error', summary: 'Error', detail: result.message, life: 3000 });
      return;
    }

    const raw = Array.isArray(result.data.data) ? result.data.data : (Array.isArray(result.data) ? result.data : []);
    const total = result.data.meta?.total ?? result.data.pagination?.total ?? raw.length;

    ficheNavettes.value = raw.map(f => {
      const fiche = { ...f };
      const p = fiche.patient ?? {};
      let pname = p.name ?? fiche.patient_name ?? 'Unknown';
      
      fiche.patient = { id: p.id ?? fiche.patient_id ?? null, name: pname };
      fiche.services = fiche.services ?? (Array.isArray(fiche.items) ? (fiche.items.map(it => (it.prestation?.service ? { id: it.prestation.service.id, name: it.prestation.service.name } : null)).filter(Boolean)) : []);
      fiche.items_count = fiche.items_count ?? (Array.isArray(fiche.items) ? fiche.items.length : (fiche.services.length || 0));
      fiche.total_amount = Number(fiche.total_amount ?? fiche.total ?? 0);
      fiche.paid_amount = Number(fiche.paid_amount ?? fiche.payed_amount ?? 0);
      fiche.remaining_amount = Number(fiche.remaining_amount ?? Math.max(0, fiche.total_amount - fiche.paid_amount));
      return fiche;
    });

    totalRecords.value = Number(total || ficheNavettes.value.length || 0);
  } catch (err) {
    console.error('loadFicheNavettes error', err);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load fiches', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const onSearch = () => {
  debouncedSearch();
};
// --- OPTIMIZED CODE END ---

const onPage = (event) => {
  lazyParams.value.first = event.first
  lazyParams.value.rows = event.rows
  lazyParams.value.page = Math.floor(event.first / event.rows) + 1
  loadFicheNavettes()
}


// load session info using the reactive caisseSessionId
const loadSessionInfo = async (id = null) => {
  const sid = id ?? caisseSessionId.value
  if (!sid) {
    sessionInfo.value = null
    return
  }
  try {
    const res = await axios.get(`/api/caisse-sessions/${sid}`, { headers: { Accept: 'application/json' }, withCredentials: true })
    const payload = res?.data?.data ?? res?.data ?? null
    sessionInfo.value = payload
  } catch (e) {
    console.error('Failed to load session info', e)
    sessionInfo.value = null
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedDateRange.value = null
  selectedStatus.value = null
  selectedCreator.value = null
  onSearch()
}

const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const editFiche = (fiche) => {
  selectedFiche.value = fiche
  modalMode.value = 'edit'
  showModal.value = true
}

const confirmDelete = (fiche) => {
  confirm.require({
    message: `Are you sure you want to delete slip #${fiche.id} for ${fiche.patient.name}?`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Delete',
    rejectLabel: 'Cancel',
    accept: () => deleteFiche(fiche)
  })
}

const deleteFiche = async (fiche) => {
  try {
    const result = await ficheNavetteService.delete(fiche.id)

    if (result.success) {
      const index = ficheNavettes.value.findIndex(f => f.id === fiche.id)
      if (index !== -1) {
        ficheNavettes.value.splice(index, 1)
        totalRecords.value -= 1
      }

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reception slip deleted successfully',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: result.message,
        life: 3000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error deleting reception slip',
      life: 3000
    })
  }
}

const onFicheSaved = (savedFiche, mode) => {
  showModal.value = false

  if (mode === 'create') {
    ficheNavettes.value.unshift(savedFiche)
    totalRecords.value += 1

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Reception slip created successfully',
      life: 3000
    })
  } else if (mode === 'edit') {
    const index = ficheNavettes.value.findIndex(f => f.id === savedFiche.id)
    if (index !== -1) {
      ficheNavettes.value[index] = { ...ficheNavettes.value[index], ...savedFiche }
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Reception slip updated successfully',
      life: 3000
    })
  }
}

const handlePay = (fiche) => {
  console.log(fiche);
  
  router.push({ name: 'patients.list.caisse-patient-payment',
  query: {
    fiche_id: fiche.id,
    caisse_session_id: caisseSessionId.value,
    fiche_navette_id: fiche.id,
    patient_id: fiche.patient.id,
    cashier_id: sessionInfo.value?.user?.id
  } })
};

// Add reactive state for modal
const showPassModal = ref(false)
const createdPassing = ref(null)
const showDetailsModal = ref(false)

// replace passerToCaisse implementation
const passerToCaisse = () => {
  // open modal and pass current session/caisse context
  showPassModal.value = true
};

const openDetails = (fiche) => {
  // Navigate to manager refund details route. The route requires a param `id` (route path: refund-requests/:id).
  // Provide the fiche id as route param and keep the prestation-only query flag.
  router.push({
    name: 'manger.refund-requests.details',
    params: { id: fiche.id },
    query: { fiche_navette_id: fiche.id, show_only_prestations: '1' }
  })
}

const formatDateTime = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleString()
  } catch {
    return String(date)
  }
}

  const getInitials = (name) => {
    if (!name || typeof name !== 'string') return ''
    const parts = name.trim().split(/\s+/).filter(Boolean)
    if (parts.length === 0) return ''
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }

// FORMATTER: currency for amounts in this view
const formatCurrency = (amount) => {
  const value = Number(amount ?? 0)
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(value)
}

// DATE helper used in fiche date column
const formatDate = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString()
  } catch {
    return String(date)
  }
}

// Map status string to label/severity/icon (used by template)
const getStatusData = (status) => {
  const map = {
    pending: { label: 'Pending', severity: 'warning', icon: 'pi pi-clock' },
    in_progress: { label: 'In Progress', severity: 'info', icon: 'pi pi-spin pi-spinner' },
    completed: { label: 'Completed', severity: 'success', icon: 'pi pi-check-circle' },
    cancelled: { label: 'Cancelled', severity: 'danger', icon: 'pi pi-times-circle' },
    default: { label: status ?? 'Unknown', severity: 'secondary', icon: null }
  }
  return map[status] ?? map.default
}

// Open create modal (template button expects this)
const openCreateModal = () => {
  modalMode.value = 'create'
  selectedFiche.value = null
  showModal.value = true
}

// call checkPendingTransfers before loading any patient/session data
onMounted(async () => {
  caisseSessionId.value = route.params?.caisse_session_id ?? route.query?.caisse_session_id ?? caisseSessionId.value
  // Parallelize API calls for faster initial load
  await Promise.all([
    loadSessionInfo(caisseSessionId.value),
    loadFicheNavettes()
  ]);
})

// when notified that a passing was created here, save id (if provided) and redirect
const onPassingCreated = (passing) => {
  createdPassing.value = passing
  toast.add({
    severity: 'success',
    summary: 'Passing Created',
    detail: 'Caisse passing created successfully.',
    life: 3000
  })

  // persist pending transfer id if server provided it
  if (passing && (passing.id || passing.transfer_id)) {
    localStorage.setItem('pending_caisse_transfer_id', String(passing.id ?? passing.transfer_id))
  }

  router.replace({
    name: 'patients.list',
    query: {
      caisse_id: passing.caisse_id ?? passing.caisseId ?? '',
      to_user_id: passing.to_user_id ?? passing.toUserId ?? '',
      session_caisse_id: passing.caisse_session_id ?? passing.session_caisse_id ?? ''
    }
  })

  showPassModal.value = false
  loadFicheNavettes()
}
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen tw-font-sans">
    <div class="tw-bg-gradient-to-r tw-from-cyan-500 tw-to-blue-500 tw-py-12 tw-rounded-b-2xl tw-mb-8">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
          <div class="tw-flex tw-items-center tw-gap-6 tw-text-white tw-text-center md:tw-text-left">
            <div class="tw-w-16 tw-h-16 tw-rounded-xl tw-bg-white/20 tw-flex tw-items-center tw-justify-center tw-text-3xl tw-shadow-md">
              <i class="pi pi-file-edit"></i>
            </div>
            <div>
              <h1 class="tw-text-4xl tw-font-bold tw-m-0">Patient Records </h1>
              <p class="tw-text-lg tw-font-normal tw-text-white/80 tw-m-0 tw-mt-1">Manage patient records and track services</p>
            </div>
          </div>
         
        </div>
      </div>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-pb-8">
      <Card v-if="sessionInfo" class="tw-p-4 tw-rounded-2xl tw-shadow-md tw-mb-6">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center tw-flex-wrap tw-gap-4">
            <div>
              <div class="tw-text-sm tw-text-gray-500">Session (Opening)</div>
              <div class="tw-text-2xl tw-font-bold">{{ formatCurrency(sessionInfo.opening_amount) }}</div>
              <div class="tw-text-sm tw-text-gray-600 tw-mt-1">Caisse: {{ sessionInfo.caisse?.name ?? sessionInfo.caisse_name ?? '—' }}</div>
            </div>
            <div class="tw-text-right">
              <div class="tw-text-sm tw-text-gray-500">Opened by</div>
              <div class="tw-font-semibold">{{ sessionInfo.opened_by?.name ?? sessionInfo.user?.name ?? sessionInfo.open_by ?? '—' }}</div>
              <div class="tw-text-sm tw-text-gray-500 tw-mt-2">Opened at: {{ formatDateTime(sessionInfo.ouverture_at ?? sessionInfo.opened_at ?? sessionInfo.created_at) }}</div>
              <Tag :value="sessionInfo.status ?? 'unknown'" class="tw-mt-2" :severity="sessionInfo.status === 'open' ? 'success' : 'info'" />
            </div>
          </div>
        </template>
      </Card>
      <Card class="tw-shadow-xl tw-rounded-2xl tw-border-none tw-overflow-hidden">
        <template #content>
          <div class="tw-p-6 tw-bg-gray-50 tw-border-b tw-border-gray-200">
            <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
              <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-4 tw-items-center tw-w-full md:tw-w-auto">
                <div class="p-inputgroup tw-w-full md:tw-w-auto">
                  <span class="p-inputgroup-addon tw-rounded-l-lg">
                    <i class="pi pi-search" />
                  </span>
                  <InputText
                    v-model="searchQuery"
                    placeholder="Search..."
                    class="tw-w-full md:tw-w-96"
                    @keyup.enter="onSearch"
                  />
                </div>
                <div class="tw-flex tw-gap-2">
                  <Button
                    :icon="showFilters ? 'pi pi-filter-slash' : 'pi pi-filter'"
                    :label="showFilters ? 'Hide' : 'Filters'"
                    class="p-button-outlined"
                    :severity="hasActiveFilters ? 'success' : 'secondary'"
                    @click="toggleFilters"
                    :badge="hasActiveFilters ? totalActiveFilters.toString() : null"
                  />
                  <Button
                    v-if="hasActiveFilters"
                    icon="pi pi-times"
                    class="p-button-outlined p-button-danger"
                    @click="clearFilters"
                    v-tooltip.bottom="'Clear all filters'"
                  />
                </div>
              </div>
              <div class="tw-flex tw-gap-3 tw-items-center">
                <Dropdown
                  v-model="lazyParams.rows"
                  :options="rowsPerPageOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="20 per page"
                  class="tw-min-w-[150px]"
                  @change="onSearch"
                />
                <Button
                  icon="pi pi-refresh"
                  class="p-button-outlined"
                  @click="loadFicheNavettes"
                  v-tooltip.bottom="'Refresh'"
                  :loading="loading"
                />
              </div>
            </div>

            <Transition name="filters">
              <div v-if="showFilters" class="tw-mt-4 tw-p-4 tw-bg-gray-100 tw-rounded-xl tw-border tw-border-gray-200">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
                  <div class="tw-flex tw-flex-col tw-gap-2">
                    <label class="tw-font-semibold tw-text-gray-700 tw-text-sm">Period</label>
                    <Calendar
                      v-model="selectedDateRange"
                      selectionMode="range"
                      placeholder="Select a period"
                      class="tw-w-full"
                      @date-select="onSearch"
                    />
                  </div>
                  <div class="tw-flex tw-flex-col tw-gap-2">
                    <label class="tw-font-semibold tw-text-gray-700 tw-text-sm">Status</label>
                    <Dropdown
                      v-model="selectedStatus"
                      :options="statusOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="All Statuses"
                      class="tw-w-full"
                      @change="onSearch"
                    />
                  </div>
                  <div class="tw-flex tw-flex-col tw-gap-2">
                    <label class="tw-font-semibold tw-text-gray-700 tw-text-sm">Creator</label>
                    <Dropdown
                      v-model="selectedCreator"
                      :options="creatorsOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="All Creators"
                      class="tw-w-full"
                      @change="onSearch"
                    />
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <DataTable
            :value="ficheNavettes"
            :lazy="true"
            :paginator="true"
            :rows="lazyParams.rows"
            :totalRecords="totalRecords"
            @page="onPage"
            class="tw-mt-4 tw-rounded-xl tw-shadow-sm tw-overflow-hidden"
            responsiveLayout="scroll"
            rowHover
            stripedRows
            dataKey="id"
          >
            <Column field="id" header="ID" sortable class="tw-w-[5%]">
              <template #body="{ data }">
                <Badge :value="`#${data.id}`" severity="info" class="tw-text-xs" />
              </template>
            </Column>

            <Column field="patient.name" header="Patient" sortable class="tw-w-[15%]">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-10 tw-h-10 tw-rounded-full tw-bg-blue-500 tw-text-white tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm" @click.stop="openDetails(data)">
                      {{ getInitials(data.patient.name) }}
                    </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900">{{ data.patient.name }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Services" class="tw-w-[20%]">
              <template #body="{ data }">
                <div class="tw-flex tw-flex-wrap tw-gap-2">
                  <Tag v-for="service in data.services" :key="service.id" :value="service.name" severity="info" class="tw-text-xs" />
                </div>
              </template>
            </Column>

            <Column field="total_amount" header="Total Amount" sortable class="tw-w-[15%]">
              <template #body="{ data }">
                <div class="tw-flex tw-flex-col">
                  <div class="tw-text-lg tw-font-bold">{{ formatCurrency(data.total_amount) }}</div>
                  <div v-if="data.remaining_amount > 0" class="tw-text-sm tw-text-red-500">
                    Remaining: {{ formatCurrency(data.remaining_amount) }}
                  </div>
                </div>
              </template>
            </Column>

            <Column field="status" header="Status" sortable class="tw-w-[15%]">
              <template #body="{ data }">
                <Tag
                  :value="getStatusData(data.status).label"
                  :severity="getStatusData(data.status).severity"
                  :icon="getStatusData(data.status).icon"
                  class="tw-font-semibold"
                />
              </template>
            </Column>

            <Column field="fiche_date" header="Creation Date" sortable class="tw-w-[15%]">
              <template #body="{ data }">
                <div class="tw-flex tw-flex-col">
                  <span class="tw-font-semibold tw-text-gray-900">{{ formatDate(data.fiche_date) }}</span>
                  <span class="tw-text-sm tw-text-gray-500">{{ formatDateTime(data.created_at) }}</span>
                </div>
              </template>
            </Column>

            <Column header="Actions" class="tw-w-[15%]">
              <template #body="{ data }">
                <Button
                  icon="pi pi-eye"
                  label="Details"
                  class="p-button-info p-button-sm tw-shadow-sm"
                  @click="openDetails(data)"
                />
              </template>
            </Column>

            <template #empty>
              <div class="tw-text-center tw-py-16 tw-text-gray-500">
                <i class="pi pi-list tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                <h4 class="tw-text-2xl tw-font-semibold">No Patient Records Found</h4>
                <p class="tw-mt-2">
                    {{ hasActiveFilters ? 'No results match your search criteria.' : 'Start by creating your first patient record.' }}
                </p>
                <Button v-if="!hasActiveFilters" icon="pi pi-plus" label="Create a Record" class="p-button-primary tw-mt-4" @click="openCreateModal" />
              </div>
            </template>

            <template #loading>
              <div class="tw-text-center tw-py-16 tw-text-gray-500">
                <ProgressSpinner strokeWidth="4" class="tw-w-12 tw-h-12 tw-text-blue-500" />
                <p class="tw-mt-4">Loading patient records...</p>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>
    </div>
    <Toast />
    <ConfirmDialog />
    <PassCaisseModal
      v-if="showPassModal"
      :caisse-session-id="caisseSessionId"
      :caisse-id="sessionInfo?.caisse_id ?? sessionInfo?.caisse?.id"
      @close="showPassModal = false"
      @created="onPassingCreated"
    />

    <Dialog v-model:visible="showDetailsModal" modal :style="{ width: '700px' }" header="Fiche Details">
      <template v-if="selectedFiche">
        <div class="tw-p-4">
          <h3 class="tw-text-lg tw-font-semibold">Patient: {{ selectedFiche.patient?.name ?? '—' }}</h3>
          <div class="tw-mt-3">
            <p><strong>Total:</strong> {{ formatCurrency(selectedFiche.total_amount) }}</p>
            <p><strong>Paid:</strong> {{ formatCurrency(selectedFiche.paid_amount) }}</p>
            <p><strong>Remaining:</strong> {{ formatCurrency(selectedFiche.remaining_amount) }}</p>
            <p><strong>Status:</strong> {{ getStatusData(selectedFiche.status).label }}</p>
            <p class="tw-mt-2"><strong>Services:</strong></p>
            <ul class="tw-list-disc tw-pl-5">
              <li v-for="s in selectedFiche.services" :key="s.id">{{ s.name }}</li>
            </ul>
          </div>
        </div>
      </template>
      <template v-else>
        <div class="tw-p-4">No details available.</div>
      </template>
      <div class="tw-flex tw-justify-end tw-gap-2 tw-p-4 tw-border-t">
        <Button label="Close" icon="pi pi-times" class="p-button-secondary" @click="closeDetails" />
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
/* Filters Panel Animation */
.filters-enter-active,
.filters-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.filters-enter-from,
.filters-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-10px);
}
.filters-enter-to,
.filters-leave-from {
  max-height: 200px;
  opacity: 1;
  transform: translateY(0);
}

/* PrimeVue Overrides */
:deep(.p-card-header) {
    @apply p-0;
}
:deep(.p-card-content) {
    @apply p-0;
}
:deep(.p-datatable-thead tr th) {
    @apply bg-gray-100 tw-text-gray-700 tw-font-semibold tw-text-sm;
}
:deep(.p-datatable-tbody tr td) {
    @apply text-gray-800;
}
:deep(.p-datatable-tbody tr:hover) {
    @apply bg-blue-50;
}
:deep(.p-datatable .p-paginator-bottom) {
    @apply p-4 tw-border-t-0;
}
</style>