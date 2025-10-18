<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

// PrimeVue components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import ProgressSpinner from 'primevue/progressspinner'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import ConfirmDialog from 'primevue/confirmdialog'
import Toolbar from 'primevue/toolbar'
import Divider from 'primevue/divider'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Menu from 'primevue/menu'
import Chip from 'primevue/chip'
import Skeleton from 'primevue/skeleton'

// Custom components
import FicheNavetteModal from '../../../../Components/Apps/Emergency/FicheNavatte/ficheNavetteModal.vue'
import FicheNavetteItemsModal from './FicheNavetteItemsList.vue'
import { ficheNavetteService } from '../../../../Components/Apps/services/Emergency/ficheNavetteService'

// Composables
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()

// Reactive data
const ficheNavettes = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const searchQuery = ref('')
const showModal = ref(false)
const showItemsModal = ref(false)
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
    { label: 'Tous les statuts', value: null },
    { label: 'En attente', value: 'pending', severity: 'warning', icon: 'pi pi-clock' },
    { label: 'En cours', value: 'in_progress', severity: 'info', icon: 'pi pi-spin pi-spinner' },
    { label: 'Terminé', value: 'completed', severity: 'success', icon: 'pi pi-check-circle' },
    { label: 'Annulé', value: 'cancelled', severity: 'danger', icon: 'pi pi-times-circle' }
]

const rowsPerPageOptions = [10, 20, 50, 100]

// Mock creators data - replace with actual API call
const creatorsOptions = ref([
    { label: 'Tous les créateurs', value: null },
    { label: 'Dr. Martin', value: 'martin' },
    { label: 'Dr. Sarah', value: 'sarah' },
    { label: 'Dr. Ahmed', value: 'ahmed' }
])

// Methods
const loadFicheNavettes = async () => {
    loading.value = true
    try {
        const params = {
            page: lazyParams.value.page,
            per_page: lazyParams.value.rows,
            search: searchQuery.value,
            status: selectedStatus.value,
            creator: selectedCreator.value,
            date_from: selectedDateRange.value?.[0],
            date_to: selectedDateRange.value?.[1]
        }
        
        const result = await ficheNavetteService.getAll(params)
        
        if (result.success) {
            ficheNavettes.value = result.data
            totalRecords.value = result.total
        } else {
            toast.add({
                severity: 'error',
                summary: 'Erreur',
                detail: result.message,
                life: 3000
            })
        }
    } finally {
        loading.value = false
    }
}

const onPage = (event) => {
    lazyParams.value.first = event.first
    lazyParams.value.rows = event.rows
    lazyParams.value.page = Math.floor(event.first / event.rows) + 1
    loadFicheNavettes()
}

const onSearch = () => {
    lazyParams.value.first = 0
    lazyParams.value.page = 1
    loadFicheNavettes()
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

const openCreateModal = () => {
    selectedFiche.value = null
    modalMode.value = 'create'
    showModal.value = true
}

const openItemsPage = (fiche) => {
    router.push(`/nursing/fiche-navette/${fiche.id}/items`)
}

// Open caisse patient payment page for this fiche.
// Default to the 'urgence' service so the caisse selection pre-selects the urgence caisse/session.
const openCaissePayment = (fiche) => {
  // Try to include the current authenticated user's id as cashier_id for immediate use
  (async () => {
    try {
      const res = await fetch('/api/loginuser')
      const json = await res.json()
      const authId = json?.data?.id ?? json?.id ?? null

      const query = {
        fiche_navette_id: fiche.id,
        patient_id: fiche.patient_id,
        caisse_service: 'urgence'
      }
      if (authId) query.cashier_id = authId

      router.push({ name: 'patients.list.caisse-patient-payment', query })
    } catch (e) {
      // Fallback if fetch fails; server will also default to Auth::id()
      router.push({
        name: 'patients.list.caisse-patient-payment',
        query: {
          fiche_navette_id: fiche.id,
          patient_id: fiche.patient_id,
          caisse_service: 'urgence'
        }
      })
    }
  })()
}

const editFiche = (fiche) => {
    selectedFiche.value = fiche
    modalMode.value = 'edit'
    showModal.value = true
}

const confirmDelete = (fiche) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la fiche navette #${fiche.id} pour ${fiche.patient_name} ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Supprimer',
        rejectLabel: 'Annuler',
        accept: () => deleteFiche(fiche)
    })
}


const onFicheSaved = (savedFiche, mode) => {
    showModal.value = false
    
    if (mode === 'create') {
        ficheNavettes.value.unshift(savedFiche)
        totalRecords.value += 1
        
        toast.add({
            severity: 'success',
            summary: 'Succès',
            detail: 'Fiche navette créée avec succès',
            life: 3000
        })
    } else if (mode === 'edit') {
        const index = ficheNavettes.value.findIndex(f => f.id === savedFiche.id)
        if (index !== -1) {
            ficheNavettes.value[index] = { ...ficheNavettes.value[index], ...savedFiche }
        }
        
        toast.add({
            severity: 'success',
            summary: 'Succès',
            detail: 'Fiche navette modifiée avec succès',
            life: 3000
        })
    }
}

// Utility functions
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const formatDateTime = (date) => {
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount || 0)
}

const getStatusData = (status) => {
    return statusOptions.find(opt => opt.value === status) || { 
        label: status, 
        severity: 'secondary',
        icon: 'pi pi-circle'
    }
}

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

// Lifecycle
onMounted(() => {
    loadFicheNavettes()
})
</script>
<template>
  <div class="tw-min-h-screen tw-bg-gray-100 tw-p-4 sm:tw-p-6 lg:tw-p-10 tw-font-sans tw-transition-colors tw-duration-300">
    <div class="tw-mb-8 lg:tw-mb-10">
      <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center tw-justify-between tw-gap-4">
        <div class="tw-flex tw-items-center tw-gap-4">
          <div class="tw-flex tw-items-center tw-justify-center tw-w-14 tw-h-14 tw-bg-blue-600 tw-text-white tw-rounded-xl tw-shadow-lg tw-flex-shrink-0">
            <i class="pi pi-file-edit tw-text-2xl"></i>
          </div>
          <div>
            <h1 class="tw-text-2xl lg:tw-text-3xl tw-font-bold tw-text-gray-900">Shuttle Forms</h1>
            <p class="tw-text-sm lg:tw-text-base tw-text-gray-500 tw-mt-1">
              Management and tracking of reception forms
            </p>
          </div>
        </div>
        <div class="tw-flex tw-gap-3 tw-flex-wrap tw-mt-4 sm:tw-mt-0">
          <Chip 
            :label="`${totalRecords} forms`" 
            class="!tw-bg-blue-100 !tw-text-blue-800 tw-font-medium" 
          />
          <Chip 
            v-if="hasActiveFilters" 
            :label="`${totalActiveFilters} filter${totalActiveFilters > 1 ? 's' : ''} active`" 
            class="!tw-bg-yellow-100 !tw-text-yellow-800 tw-font-medium"
          />
        </div>
      </div>
    </div>

    <Card class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-200">
      <template #content>
        <div class="tw-p-4 sm:tw-p-6 lg:tw-p-8">
          <div class="tw-flex tw-flex-col lg:tw-flex-row tw-items-start lg:tw-items-center tw-justify-between tw-gap-4">
            <div class="tw-flex tw-flex-col md:tw-flex-row md:tw-items-center tw-gap-4 tw-flex-1 tw-w-full">
              <span class="p-input-icon-left tw-w-full md:tw-max-w-xs">
                <i class="pi pi-search" />
                <InputText 
                  v-model="searchQuery" 
                  placeholder="Search..." 
                  class="tw-w-full tw-rounded-xl tw-py-2.5 tw-pl-10 tw-pr-4 tw-bg-gray-100 tw-border-none tw-focus:tw-ring-2 tw-focus:tw-ring-blue-500"
                  @keyup.enter="onSearch"
                />
              </span>
              <div class="tw-flex tw-items-center tw-gap-2">
                <Button 
                  :icon="showFilters ? 'pi pi-filter-slash' : 'pi pi-filter'"
                  :label="showFilters ? 'Hide filters' : 'Show filters'"
                  :severity="hasActiveFilters ? 'success' : 'secondary'"
                  class="tw-rounded-xl tw-py-2.5 tw-px-4 tw-font-semibold tw-transition-all tw-duration-300"
                  @click="toggleFilters"
                />
                <Button 
                  v-if="hasActiveFilters"
                  icon="pi pi-times"
                  class="p-button-outlined p-button-danger tw-rounded-full tw-w-10 tw-h-10 tw-p-0 tw-transition-all tw-duration-300"
                  @click="clearFilters"
                  v-tooltip.bottom="'Clear all filters'"
                />
              </div>
            </div>
            
            <div class="tw-flex tw-gap-3 tw-w-full md:tw-w-auto tw-mt-4 lg:tw-mt-0 tw-justify-end">
              <Button 
                icon="pi pi-refresh" 
                class="p-button-outlined tw-bg-white tw-text-gray-600 tw-rounded-xl tw-py-2.5 tw-px-4"
                @click="loadFicheNavettes"
                v-tooltip.bottom="'Refresh'"
                :loading="loading"
              />
            </div>
          </div>
          
          <div v-if="showFilters" class="tw-mt-6 tw-p-6 tw-bg-gray-100 tw-rounded-xl tw-border tw-border-gray-200 tw-transition-all tw-duration-300">
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-6">
              <div class="tw-flex tw-flex-col tw-gap-2">
                <label class="tw-text-sm tw-font-semibold tw-text-gray-700">Period</label>
                <Calendar 
                  v-model="selectedDateRange" 
                  selectionMode="range"
                  :manualInput="false"
                  placeholder="Select a period"
                  class="tw-w-full"
                  inputClass="!tw-rounded-lg"
                  @date-select="onSearch"
                />
              </div>
              <div class="tw-flex tw-flex-col tw-gap-2">
                <label class="tw-text-sm tw-font-semibold tw-text-gray-700">Status</label>
                <Dropdown 
                  v-model="selectedStatus" 
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="All statuses"
                  class="tw-w-full"
                  inputClass="!tw-rounded-lg"
                  @change="onSearch"
                >
                  <template #option="{ option }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i :class="option.icon" v-if="option.icon"></i>
                      <span>{{ option.label }}</span>
                    </div>
                  </template>
                </Dropdown>
              </div>
              <div class="tw-flex tw-flex-col tw-gap-2">
                <label class="tw-text-sm tw-font-semibold tw-text-gray-700">Creator</label>
                <Dropdown 
                  v-model="selectedCreator" 
                  :options="creatorsOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="All creators"
                  class="tw-w-full"
                  inputClass="!tw-rounded-lg"
                  @change="onSearch"
                />
              </div>
            </div>
          </div>
        </div>

        <Divider class="tw-my-0 tw-border-gray-200" />

        <DataTable 
          :value="ficheNavettes" 
          :loading="loading"
          :paginator="true"
          :rows="lazyParams.rows"
          :totalRecords="totalRecords"
          :lazy="true"
          :rowsPerPageOptions="rowsPerPageOptions"
          @page="onPage"
          class="p-datatable-gridlines"
          responsiveLayout="scroll"
          :rowHover="true"
          stripedRows
          dataKey="id"
          @row-click="(event) => openItemsPage(event.data)"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        >
          <template #loading>
            <div class="tw-p-4">
              <Skeleton width="100%" height="2rem" class="tw-mb-2" />
              <Skeleton width="100%" height="2rem" class="tw-mb-2" />
              <Skeleton width="100%" height="2rem" />
            </div>
          </template>
          
          <Column field="id" header="ID" sortable class="tw-w-16">
            <template #body="{ data }">
              <div class="tw-flex tw-justify-center tw-items-center">
                <Badge :value="`#${data.id}`" severity="info" class="tw-text-xs tw-font-semibold" />
              </div>
            </template>
          </Column>

          <Column field="patient_name" header="Patient" sortable class="tw-min-w-[12rem] lg:tw-min-w-[15rem]">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <Avatar 
                  :label="getInitials(data.patient_name)" 
                  class="tw-bg-blue-100 tw-text-blue-800 tw-font-semibold tw-w-10 tw-h-10 tw-flex-shrink-0"
                  shape="circle"
                />
                <div class="tw-flex tw-flex-col">
                  <span class="tw-font-medium tw-text-gray-900">{{ data.patient_name }}</span>
                  <small class="tw-text-gray-500">
                    ID: {{ data.patient_id }}
                  </small>
                </div>
              </div>
            </template>
          </Column>

          <Column field="creator_name" header="Creator" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Avatar 
                  :label="getInitials(data.creator_name)" 
                  class="tw-bg-green-100 tw-text-green-800 tw-font-semibold tw-w-8 tw-h-8 tw-text-xs tw-flex-shrink-0"
                  shape="circle"
                />
                <span class="tw-text-sm tw-text-gray-800">{{ data.creator_name }}</span>
              </div>
            </template>
          </Column>

          <Column field="fiche_date" header="Creation Date" sortable>
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col">
                <span class="tw-font-medium tw-text-gray-800">{{ formatDate(data.fiche_date) }}</span>
                <small class="tw-text-gray-500">{{ formatDateTime(data.created_at) }}</small>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag 
                :value="getStatusData(data.status).label"
                :severity="getStatusData(data.status).severity"
                :icon="getStatusData(data.status).icon"
                class="tw-font-semibold tw-px-3 tw-py-1 tw-text-sm"
              />
            </template>
          </Column>

          <Column field="is_emergency" header="Emergency Doctor" sortable>
            <template #body="{ data }">
              <div v-if="data.is_emergency && data.emergency_doctor" class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-exclamation-triangle tw-text-red-500"></i>
                <div class="tw-flex tw-flex-col">
                  <span class="tw-font-medium tw-text-gray-800 tw-text-sm">{{ data.emergency_doctor.name }}</span>
                  <small class="tw-text-gray-500 tw-text-xs">{{ data.emergency_doctor.specialization }}</small>
                </div>
              </div>
              <div v-else-if="data.is_emergency" class="tw-flex tw-items-center tw-gap-2 tw-text-orange-600">
                <i class="pi pi-exclamation-triangle"></i>
                <span class="tw-text-sm">No doctor assigned</span>
              </div>
              <span v-else class="tw-text-gray-400 tw-text-sm">-</span>
            </template>
          </Column>


          <Column field="remaining_amount" header="Remaining Amount" sortable>
            <template #body="{ data }">
              <div class="tw-text-right">
                <div class="tw-flex tw-flex-col tw-items-end">
                  <span 
                    class="tw-font-bold tw-text-base"
                    :class="{
                      'tw-text-red-600': (data.remaining_amount || 0) > 0,
                      'tw-text-green-600': (data.remaining_amount || 0) === 0
                    }"
                  >
                    {{ formatCurrency(data.remaining_amount || 0) }}
                  </span>
                  <small v-if="data.payment_status" 
                    class="tw-text-xs tw-mt-1"
                    :class="{
                      'tw-text-red-500': data.payment_status === 'pending',
                      'tw-text-yellow-600': data.payment_status === 'partial',
                      'tw-text-green-600': data.payment_status === 'paid'
                    }"
                  >
                    {{ data.payment_status === 'paid' ? 'Paid' : data.payment_status === 'partial' ? 'Partial' : 'Unpaid' }}
                  </small>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Actions" class="tw-w-32 tw-text-center">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-1 tw-items-center tw-justify-center">
                <Button 
                  icon="pi pi-list" 
                  class="p-button-rounded p-button-text p-button-sm !tw-text-blue-500 tw-hover:!tw-bg-blue-100"
                  v-tooltip.top="'View services'"
                  @click.stop="openItemsPage(data)"
                />
                <Button
                  icon="pi pi-wallet"
                  label="Payer"
                  class="p-button-success p-button-sm tw-ml-1"
                  v-tooltip.top="'Payer cette fiche'"
                  @click.stop="openCaissePayment(data)"
                />
              
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="tw-py-12 tw-text-center tw-text-gray-500">
              <div class="tw-flex tw-justify-center tw-mb-4">
                <i class="pi pi-inbox tw-text-7xl tw-text-gray-300"></i>
              </div>
              <h3 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-2">
                No shuttle forms found
              </h3>
              <p class="tw-text-base tw-max-w-sm tw-mx-auto tw-mb-6">
                {{ hasActiveFilters ? 'No results match your search criteria.' : 'Start by creating your first shuttle form.' }}
              </p>
              <div class="tw-flex tw-gap-4 tw-justify-center tw-flex-wrap">
                <Button 
                  v-if="hasActiveFilters"
                  icon="pi pi-filter-slash" 
                  label="Clear filters" 
                  class="p-button-outlined"
                  @click="clearFilters"
                />
                <Button 
                  icon="pi pi-plus" 
                  label="Create a form" 
                  class="p-button-primary tw-bg-blue-600 tw-text-white tw-font-semibold"
                  @click="openCreateModal"
                />
              </div>
            </div>
          </template>
        </DataTable>
        
      </template>
    </Card>

    <FicheNavetteModal 
      v-model="showModal"
      :fiche="selectedFiche"
      :mode="modalMode"
      @saved="onFicheSaved"
    />

    <ConfirmDialog />

  </div>
</template>
