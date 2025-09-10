<!-- pages/Reception/FicheNavette/FicheNavetteList.vue -->
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
import FicheNavetteModal from '../../../../Components/Apps/reception/FicheNavatte/ficheNavetteModal.vue'
import FicheNavetteItemsModal from './FicheNavetteItemsList.vue'
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService'

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
  router.push(`/reception/fiche-navette/${fiche.id}/items`)
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
        summary: 'Succès',
        detail: 'Fiche navette supprimée avec succès',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: result.message,
        life: 3000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors de la suppression',
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
  <div class="fiche-navette-container">
    <!-- Enhanced Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <div class="title-group">
            <div class="title-icon">
              <i class="pi pi-file-edit"></i>
            </div>
            <div class="title-content">
              <h1 class="page-title">Fiches Navette</h1>
              <p class="page-subtitle">Gestion des fiches de réception et suivi des services</p>
              <div class="stats-chips">
                <Chip :label="`${totalRecords} fiches`" severity="info" />
                <Chip v-if="hasActiveFilters" :label="`${totalActiveFilters} filtre${totalActiveFilters > 1 ? 's' : ''} actif${totalActiveFilters > 1 ? 's' : ''}`" severity="warning" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Card -->
    <Card class="main-card">
      <template #content>
        <!-- Enhanced Toolbar -->
        <div class="toolbar-container">
          <div class="toolbar-main">
            <div class="toolbar-left">
              <!-- Search Section -->
              <div class="search-section">
                <span class="p-input-icon-left search-wrapper">
                  <i class="pi pi-search" />
                  <InputText 
                    v-model="searchQuery" 
                    placeholder="Rechercher par patient, ID, créateur..." 
                    class="search-input"
                    @keyup.enter="onSearch"
                  />
                </span>
                <Button 
                  icon="pi pi-search" 
                  class="p-button-outlined search-btn"
                  @click="onSearch"
                  v-tooltip.bottom="'Rechercher'"
                />
              </div>

              <!-- Filter Toggle -->
              <div class="filter-section">
                <Button 
                  :icon="showFilters ? 'pi pi-filter-slash' : 'pi pi-filter'"
                  :label="showFilters ? 'Masquer' : 'Filtres'"
                  :class="['filter-toggle', { 'p-button-success': hasActiveFilters }]"
                  :severity="hasActiveFilters ? 'success' : 'secondary'"
                  @click="toggleFilters"
                  :badge="hasActiveFilters ? totalActiveFilters.toString() : null"
                />
                <Button 
                  v-if="hasActiveFilters"
                  icon="pi pi-times"
                  class="p-button-outlined p-button-sm clear-filters"
                  @click="clearFilters"
                  v-tooltip.bottom="'Effacer tous les filtres'"
                />
              </div>
            </div>
            
            <div class="toolbar-right">
              <Button 
                icon="pi pi-refresh" 
                class="p-button-outlined refresh-btn"
                @click="loadFicheNavettes"
                v-tooltip.bottom="'Actualiser'"
                :loading="loading"
              />
              <Button 
                icon="pi pi-plus" 
                label="Nouvelle Fiche" 
                class="p-button-primary create-btn"
                @click="openCreateModal"
              />
            </div>
          </div>

          <!-- Advanced Filters Panel -->
          <Transition name="filters">
            <div v-if="showFilters" class="filters-panel">
              <div class="filters-grid">
                <div class="filter-item">
                  <label class="filter-label">Période</label>
                  <Calendar 
                    v-model="selectedDateRange" 
                    selectionMode="range"
                    :manualInput="false"
                    placeholder="Sélectionner une période"
                    class="date-filter"
                    @date-select="onSearch"
                  />
                </div>
                
                <div class="filter-item">
                  <label class="filter-label">Statut</label>
                  <Dropdown 
                    v-model="selectedStatus" 
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Tous les statuts"
                    class="status-filter"
                    @change="onSearch"
                  >
                    <template #option="{ option }">
                      <div class="status-option">
                        <i :class="option.icon" v-if="option.icon"></i>
                        <span>{{ option.label }}</span>
                      </div>
                    </template>
                  </Dropdown>
                </div>
                
                <div class="filter-item">
                  <label class="filter-label">Créateur</label>
                  <Dropdown 
                    v-model="selectedCreator" 
                    :options="creatorsOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Tous les créateurs"
                    class="creator-filter"
                    @change="onSearch"
                  />
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <Divider />

        <!-- Enhanced Data Table -->
        <DataTable 
          :value="ficheNavettes" 
          :loading="loading"
          :paginator="true"
          :rows="lazyParams.rows"
          :totalRecords="totalRecords"
          :lazy="true"
          :rowsPerPageOptions="rowsPerPageOptions"
          @page="onPage"
          class="fiche-table"
          responsiveLayout="scroll"
          :rowHover="true"
          stripedRows
          dataKey="id"
          @row-click="(event) => openItemsPage(event.data)"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} entrées"
        >
          <!-- ID Column with enhanced styling -->
          <Column field="id" header="ID" sortable class="id-column">
            <template #body="{ data }">
              <div class="id-cell">
                <Badge :value="`#${data.id}`" severity="info" size="large" />
              </div>
            </template>
          </Column>

          <!-- Enhanced Patient Column -->
          <Column field="patient_name" header="Patient" sortable class="patient-column">
            <template #body="{ data }">
              <div class="patient-info">
                <Avatar 
                  :label="getInitials(data.patient_name)" 
                  class="patient-avatar"
                  size="large"
                  shape="circle"
                />
                <div class="patient-details">
                  <div class="patient-name">{{ data.patient_name }}</div>
                  <small class="patient-id">
                    <i class="pi pi-id-card mr-1"></i>
                    ID: {{ data.patient_id }}
                  </small>
                </div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Creator Column -->
          <Column field="creator_name" header="Créateur" sortable>
            <template #body="{ data }">
              <div class="creator-info">
                <Avatar 
                  :label="getInitials(data.creator_name)" 
                  class="creator-avatar"
                  size="normal"
                  shape="circle"
                  :style="{ background: 'var(--green-500)', color: 'white' }"
                />
                <div class="creator-details">
                  <div class="creator-name">{{ data.creator_name }}</div>
                  <small class="creator-role">Réceptionniste</small>
                </div>
              </div>
            </template>
          </Column>

          <!-- Enhanced Date Column -->
          <Column field="fiche_date" header="Date de création" sortable>
            <template #body="{ data }">
              <div class="date-info">
                <div class="date-main">
                  <i class="pi pi-calendar mr-2"></i>
                  {{ formatDate(data.fiche_date) }}
                </div>
                <small class="date-time">{{ formatDateTime(data.created_at) }}</small>
              </div>
            </template>
          </Column>

          <!-- Enhanced Status Column -->
          <Column field="status" header="Statut" sortable>
            <template #body="{ data }">
              <div class="status-cell">
                <Tag 
                  :value="getStatusData(data.status).label"
                  :severity="getStatusData(data.status).severity"
                  :icon="getStatusData(data.status).icon"
                  class="status-tag"
                />
              </div>
            </template>
          </Column>

          <!-- Services Count Column -->
          <Column field="items_count" header="Services" sortable>
            <template #body="{ data }">
              <div class="services-info">
                <Badge 
                  :value="data.items_count || 0" 
                  :severity="(data.items_count || 0) > 0 ? 'success' : 'warning'"
                  size="large"
                />
                <small class="services-label">service{{ (data.items_count || 0) !== 1 ? 's' : '' }}</small>
              </div>
            </template>
          </Column>

          <!-- Enhanced Amount Column -->
          <Column field="total_amount" header="Montant Total" sortable>
            <template #body="{ data }">
              <div class="amount-display">
                <div class="amount-value">{{ formatCurrency(data.total_amount) }}</div>
                <small class="amount-label">Total DZD</small>
              </div>
            </template>
          </Column>

          <!-- Enhanced Actions Column -->
          <Column header="Actions" class="actions-column">
            <template #body="{ data }">
              <div class="action-buttons" @click.stop>
                <Button 
                  icon="pi pi-list" 
                  class="p-button-rounded p-button-text p-button-sm p-button-info action-btn"
                  v-tooltip.top="'Voir les services'"
                  @click="openItemsPage(data)"
                />
                <Button 
                  icon="pi pi-pencil" 
                  class="p-button-rounded p-button-text p-button-sm action-btn"
                  v-tooltip.top="'Modifier'"
                  @click="editFiche(data)"
                />
                <Button 
                  icon="pi pi-trash" 
                  class="p-button-rounded p-button-text p-button-sm p-button-danger action-btn"
                  v-tooltip.top="'Supprimer'"
                  @click="confirmDelete(data)"
                />
              </div>
            </template>
          </Column>

          <!-- Enhanced Empty State -->
          <template #empty>
            <div class="empty-state">
              <div class="empty-content">
                <div class="empty-icon">
                  <i class="pi pi-file-o"></i>
                </div>
                <h3 class="empty-title">Aucune fiche navette trouvée</h3>
                <p class="empty-description">
                  {{ hasActiveFilters ? 'Aucun résultat ne correspond à vos critères de recherche' : 'Commencez par créer votre première fiche navette' }}
                </p>
                <div class="empty-actions">
                  <Button 
                    v-if="hasActiveFilters"
                    icon="pi pi-filter-slash" 
                    label="Effacer les filtres" 
                    class="p-button-outlined"
                    @click="clearFilters"
                  />
                  <Button 
                    icon="pi pi-plus" 
                    label="Créer une fiche" 
                    class="p-button-primary"
                    @click="openCreateModal"
                  />
                </div>
              </div>
            </div>
          </template>

          <!-- Enhanced Loading State -->
          <template #loading>
            <div class="loading-state">
              <div class="loading-content">
                <ProgressSpinner strokeWidth="3" size="3rem" />
                <p class="loading-text">Chargement des fiches navette...</p>
              </div>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <!-- Modals -->
    <FicheNavetteModal 
      v-model:visible="showModal"
      :fiche="selectedFiche"
      :mode="modalMode"
      @saved="onFicheSaved"
    />

    <ConfirmDialog />
  </div>
</template>

<style scoped>
.fiche-navette-container {
  padding: 1.5rem;
  min-height: 100vh;
  background: linear-gradient(135deg, var(--surface-50) 0%, var(--surface-100) 100%);
}

/* Enhanced Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.title-group {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.title-icon {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-600) 100%);
  color: white;
  width: 4rem;
  height: 4rem;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  box-shadow: 0 8px 20px rgba(var(--primary-color-rgb), 0.3);
}

.title-content {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 0.5rem 0;
  line-height: 1.2;
}

.page-subtitle {
  color: var(--text-color-secondary);
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  line-height: 1.4;
}

.stats-chips {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Enhanced Main Card */
.main-card {
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border-radius: 16px;
  border: 1px solid var(--surface-200);
  overflow: hidden;
}

/* Enhanced Toolbar */
.toolbar-container {
  padding: 1.5rem;
  background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-50) 100%);
  border-bottom: 1px solid var(--surface-200);
}

.toolbar-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.search-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-wrapper {
  position: relative;
}

.search-input {
  width: 400px;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border-radius: 12px;
  border: 2px solid var(--surface-300);
  transition: all 0.3s ease;
}

.search-input:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb), 0.2);
}

.search-btn {
  border-radius: 12px;
  padding: 0.75rem 1rem;
}

.filter-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-toggle {
  border-radius: 12px;
  transition: all 0.3s ease;
}

.clear-filters {
  border-radius: 50%;
  width: 2.5rem;
  height: 2.5rem;
  padding: 0;
}

.toolbar-right {
  display: flex;
  gap: 0.75rem;
}

.refresh-btn,
.create-btn {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
}

/* Filters Panel */
.filters-panel {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: var(--surface-50);
  border-radius: 12px;
  border: 1px solid var(--surface-200);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.9rem;
}

.date-filter,
.status-filter,
.creator-filter {
  width: 100%;
}

.status-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Filters Animation */
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

/* Enhanced Table */
.fiche-table {
  margin-top: 1rem;
}

.fiche-table :deep(.p-datatable-thead tr th) {
  background: var(--surface-100);
  border-bottom: 2px solid var(--primary-200);
  color: var(--text-color);
  font-weight: 600;
  padding: 1rem;
}

.fiche-table :deep(.p-datatable-tbody tr) {
  cursor: pointer;
  transition: all 0.2s ease;
  border-bottom: 1px solid var(--surface-200);
}

.fiche-table :deep(.p-datatable-tbody tr:hover) {
  background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-25) 100%) !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.fiche-table :deep(.p-datatable-tbody tr td) {
  padding: 1rem;
  border: none;
}

/* Column Styles */
.id-column {
  width: 100px;
}

.patient-column {
  min-width: 250px;
}

.actions-column {
  width: 150px;
}

.id-cell {
  display: flex;
  justify-content: center;
}

.patient-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.patient-avatar {
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
  color: white;
  font-weight: 600;
}

.patient-details {
  flex: 1;
}

.patient-name {
  font-weight: 600;
  color: var(--text-color);
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.patient-id {
  color: var(--text-color-secondary);
  display: flex;
  align-items: center;
}

.creator-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.creator-avatar {
  background: linear-gradient(135deg, var(--green-500) 0%, var(--green-600) 100%);
  color: white;
  font-weight: 600;
}

.creator-details {
  flex: 1;
}

.creator-name {
  font-weight: 500;
  color: var(--text-color);
  margin-bottom: 0.25rem;
}

.creator-role {
  color: var(--text-color-secondary);
  font-size: 0.8rem;
}

.date-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date-main {
  display: flex;
  align-items: center;
  color: var(--text-color);
  font-weight: 500;
}

.date-time {
  color: var(--text-color-secondary);
  font-size: 0.8rem;
}

.status-cell {
  display: flex;
  justify-content: center;
}

.status-tag {
  font-weight: 600;
  padding: 0.5rem 1rem;
}

.services-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.services-label {
  color: var(--text-color-secondary);
  font-size: 0.8rem;
}

.amount-display {
  text-align: center;
}

.amount-value {
  color: var(--primary-color);
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.amount-label {
  color: var(--text-color-secondary);
  font-size: 0.8rem;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.action-btn {
  transition: all 0.2s ease;
}

.action-btn:hover {
  transform: scale(1.1);
}

/* Enhanced Empty State */
.empty-state {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-content {
  max-width: 400px;
  margin: 0 auto;
}

.empty-icon {
  font-size: 5rem;
  color: var(--text-color-secondary);
  margin-bottom: 1.5rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0 0 1rem 0;
}

.empty-description {
  color: var(--text-color-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

.empty-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* Enhanced Loading State */
.loading-state {
  padding: 3rem 2rem;
  text-align: center;
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.loading-text {
  color: var(--text-color-secondary);
  font-size: 1.1rem;
  margin: 0;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .search-input {
    width: 300px;
  }
  
  .filters-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .fiche-navette-container {
    padding: 1rem;
  }
  
  .title-group {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .toolbar-main {
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar-left {
    flex-direction: column;
    gap: 1rem;
  }
  
  .search-section {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .search-input {
    width: 100%;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .empty-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.75rem;
  }
  
  .title-icon {
    width: 3rem;
    height: 3rem;
    font-size: 1.25rem;
  }
}
</style>
