<!-- pages/Reception/FicheNavette/FicheNavetteDetails.vue -->
<script setup >
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'

// Components
import FicheNavetteHeader from '../../../../Components/Apps/reception/FicheNavatteItem/FicheNavetteHeader.vue'
import FicheNavetteInfo from '../../../../Components/Apps/reception/FicheNavatteItem/FicheNavetteInfo.vue'
import FicheNavetteItemsSection from '../../../../Components/Apps/reception/FicheNavatteItem/FicheNavetteItemsSection.vue'
import LoadingSpinner from '../../../../Components/Common/LoadingSpinner.vue'
import Dialog from 'primevue/dialog'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
// Services
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService'
import prestationService from '../../../../Components/Apps/services/Prestation/prestationService'
import prestationPackageService from '../../../../Components/Apps/services/Prestation/prestationPackageService'

// Composables
const route = useRoute()
const router = useRouter()
const toast = useToast()

// State
const fiche = ref(null)
const items = ref([])
const prestations = ref([])
const packages = ref([])
const doctors = ref([])
const loading = ref(false)
const showCreateForm = ref(false)
const conventionCompanies = ref([])
const loadingConventions = ref(false)
const showConventionDetailsModal = ref(false)
const showAllConventionsModal = ref(false)
const selectedConventionOrganisme = ref(null)

// Color fallback palette
const fallbackColors = [
  { bg: '#3B82F6', light: '#DBEAFE' },
  { bg: '#10B981', light: '#D1FAE5' },
  { bg: '#8B5CF6', light: '#EDE9FE' },
  { bg: '#F59E0B', light: '#FEF3C7' },
  { bg: '#EF4444', light: '#FEE2E2' },
  { bg: '#06B6D4', light: '#CFFAFE' },
  { bg: '#84CC16', light: '#ECFCCB' },
  { bg: '#EC4899', light: '#FCE7F3' }
]

// Computed
const ficheId = computed(() => route.params.id)

const groupedItems = computed(() => {
  const groups = {}
  
  items.value.forEach(item => {
    let key
    
    // For package items
    if (item.package_id) {
      key = `package_${item.package_id}_${item.id}` // Use item.id to ensure unique grouping
    } 
    // For individual prestations
    else if (item.prestation_id) {
      key = `prestation_${item.prestation_id}_${item.id}` // Use item.id to ensure unique grouping
    }
    // Fallback
    else {
      key = `item_${item.id}`
    }
    
    if (!groups[key]) {
      groups[key] = {
        type: item.package_id ? 'package' : 'prestation',
        id: item.package_id || item.prestation_id || item.id,
        name: item.package_id ? item.package?.name : item.prestation?.name,
        doctor_id: item.doctor_id,
        doctor_name: item.doctor?.name,
        items: [],
        total_price: 0,
        parent_item_id: item.id // Add this to track the parent item
      }
    }
    
    groups[key].items.push(item)
    groups[key].total_price += parseFloat(item.final_price || 0)
  })
  
  return Object.values(groups)
})

const totalAmount = computed(() => {
  return items.value.reduce((total, item) => total + parseFloat(item.final_price || 0), 0)
})

const itemsCount = computed(() => items.value.length)

// Methods
const loadFiche = async () => {
  loading.value = true
  try {
    const [ficheResult, itemsResult] = await Promise.all([
      ficheNavetteService.getById(ficheId.value),
      ficheNavetteService.getFicheNavetteItems(ficheId.value)
    ])

    if (ficheResult.success) {
      fiche.value = ficheResult.data
    } else {
      throw new Error(ficheResult.message || 'Failed to load fiche')
    }

    if (itemsResult.success) {
      items.value = itemsResult.data || []
    } else {
      console.warn('Failed to load items:', itemsResult.message)
      items.value = []
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to load fiche navette',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadSupportData = async () => {
  try {
    const [prestationsResult, packagesResult] = await Promise.all([
      prestationService.getAll(),
      prestationPackageService.getAll()
    ])

    if (prestationsResult.success) {
      prestations.value = prestationsResult.data
    }

    if (packagesResult.success) {
      packages.value = packagesResult.data
    }

    // Mock doctors data
    doctors.value = [
      { id: 1, name: 'Dr. Martin', specialization: 'Cardiology' },
      { id: 2, name: 'Dr. Sarah', specialization: 'Neurology' },
      { id: 3, name: 'Dr. Ahmed', specialization: 'Radiology' }
    ]
  } catch (error) {
    console.error('Error loading support data:', error)
  }
}

const loadConventionCompanies = async () => {
  if (!fiche.value?.patient_id) return
  
  try {
    loadingConventions.value = true
    const result = await ficheNavetteService.getPatientConventions(
      fiche.value.patient_id, 
      fiche.value.id
    )
    
    if (result.success) {
      conventionCompanies.value = result.data || []
      console.log('Loaded convention companies:', conventionCompanies.value) // Debug
    }
  } catch (error) {
    console.error('Error loading convention companies:', error)
  } finally {
    loadingConventions.value = false
  }
}

const handleItemsAdded = async () => {
  showCreateForm.value = false
  await loadFiche()
  
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Items added successfully',
    life: 3000
  })
}
const handleItemRemoved = (itemId) => {
  // Remove the item from the items array by its id
  items.value = items.value.filter(item => item.id !== itemId)
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Item removed successfully',
    life: 3000
  })
}
const handleRemiseApplied = async () => {
  await loadFiche()
  
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Discount applied successfully',
    life: 3000
  })
}

const toggleCreateForm = () => {
  showCreateForm.value = !showCreateForm.value
}

const goBack = () => {
  router.push('/reception/fiche-navette')
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadFiche(),
    loadSupportData()
  ])
  
  loadConventionCompanies()
})

// Watch
watch(() => fiche.value?.id, (newId) => {
  if (newId) {
    loadConventionCompanies()
  }
})

// Event Handlers
const showConventionDetails = (organisme) => {
  console.log('Received organisme:', organisme) // Debug
  selectedConventionOrganisme.value = organisme
  showConventionDetailsModal.value = true
}

const showAllConventions = () => {
  showAllConventionsModal.value = true
}

// When a company is clicked
const handleShowConventionDetails = (company) => {
  selectedConventionOrganisme.value = company // company already has color from FicheNavetteInfo
  showConventionDetailsModal.value = true
}

// Add file handling methods for the modal
const getFileIcon = (mimeTypeOrName) => {
  if (!mimeTypeOrName) return 'pi pi-file'
  const type = mimeTypeOrName.toLowerCase()
  if (type.includes('pdf')) return 'pi pi-file-pdf'
  if (type.includes('word') || type.includes('doc')) return 'pi pi-file-word'
  if (type.includes('excel') || type.includes('xls')) return 'pi pi-file-excel'
  if (type.includes('image')) return 'pi pi-image'
  return 'pi pi-file'
}


const formatFileSize = (bytes) => {
  if (!bytes) return 'Unknown size'
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const viewFile = (file) => {
  if (file && file.id) {
    window.open(`/api/fiche-navette/files/${file.id}/view`, '_blank')
  }
}

const downloadFile = (file) => {
  if (file && file.id) {
    const link = document.createElement('a')
    link.href = `/api/fiche-navette/files/${file.id}/download`
    link.download = file.original_name || 'download'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}
</script>

<template>
  <div class="fiche-navette-details">
    <!-- Header -->
    <FicheNavetteHeader
      :fiche-id="ficheId"
      :fiche="fiche"
      :show-create-form="showCreateForm"
      @go-back="goBack"
      @toggle-create-form="toggleCreateForm"
    />

    <!-- Loading State -->
    <LoadingSpinner v-if="loading" message="Loading fiche navette..." />

    <!-- Main Content -->
    <div v-else-if="fiche" class="main-content">
      <!-- Fiche Information -->
      <FicheNavetteInfo
        :fiche="fiche"
        :total-amount="totalAmount"
        :items-count="itemsCount"
        :groups-count="groupedItems.length"
        :convention-companies="conventionCompanies"
        @show-convention-details="handleShowConventionDetails"
        @show-all-conventions="showAllConventions"
      />

      <!-- Items Section -->
      <FicheNavetteItemsSection
        :fiche="fiche"
        :items="items"
        :grouped-items="groupedItems"
        :prestations="prestations"
        :packages="packages"
        :doctors="doctors"
        :show-create-form="showCreateForm"
        :total-amount="totalAmount"
        :items-count="itemsCount"
        @items-added="handleItemsAdded"
        @remove-group="handleRemoveGroup"
        @item-removed="handleItemRemoved"
        @remise-applied="handleRemiseApplied"
        @toggle-create-form="toggleCreateForm"
      />
    </div>

    <Dialog
      v-model:visible="showConventionDetailsModal"
      :header="selectedConventionOrganisme?.organisme_name || selectedConventionOrganisme?.company_name || 'Organisme Details'"
      modal
      class="convention-details-modal"
      :style="{ width: '70vw', maxWidth: '800px' }"
    >
      <div v-if="selectedConventionOrganisme" class="organisme-details-content">
        <!-- Modal header with company color -->
        <div 
          class="modal-header" 
          :style="{ 
            backgroundColor: selectedConventionOrganisme.organism_color || '#3B82F6', 
            color: 'white', 
            padding: '1rem',
            marginBottom: '1rem',
            borderRadius: '8px'
          }"
        >
          <h3 style="margin: 0;">{{ selectedConventionOrganisme.organisme_name || selectedConventionOrganisme.company_name }}</h3>
        </div>
        
        <!-- Modal body showing prestations -->
        <div class="modal-body" style="padding: 1rem;">
          <div v-for="convention in selectedConventionOrganisme.conventions" :key="convention.id" class="convention-section" style="margin-bottom: 2rem;">
            <h5 style="color: var(--text-color); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--surface-200);">
              {{ convention.convention_name }}
            </h5>
            
            <!-- Show prestations (MAIN FOCUS) -->
            <div v-if="convention.prestations && convention.prestations.length" class="prestations-modal-section">
              <h6 style="color: var(--text-color-secondary); margin-bottom: 1rem;">
                <i class="pi pi-list" :style="{ color: selectedConventionOrganisme.organism_color || '#3B82F6' }"></i>
                Prestations Used (DGSN - {{ convention.prestations.length }} prestation{{ convention.prestations.length > 1 ? 's' : '' }})
              </h6>
              <div class="prestations-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <Card
                  v-for="prestation in convention.prestations"
                  :key="prestation.id"
                  class="prestation-card"
                  :style="{
                    borderLeft: `4px solid ${selectedConventionOrganisme.organism_color || '#3B82F6'}`
                  }"
                >
                  <template #content>
                    <div class="prestation-info">
                      <div class="prestation-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong 
                          class="prestation-title"
                          :style="{ color: selectedConventionOrganisme.organism_color || '#3B82F6', fontSize: '1rem' }"
                        >
                          {{ prestation.name }}
                        </strong>
                        <Tag
                          value="DGSN"
                          severity="info"
                          size="small"
                          :style="{
                            backgroundColor: selectedConventionOrganisme.organism_color ? selectedConventionOrganisme.organism_color + '22' : '#DBEAFE',
                            color: selectedConventionOrganisme.organism_color || '#3B82F6'
                          }"
                        />
                      </div>
                      <div v-if="prestation.specialization" class="prestation-specialization" style="margin-top: 0.5rem;">
                        <small :style="{ color: 'var(--text-color-secondary)' }">
                          Spécialisation: {{ prestation.specialization }}
                        </small>
                      </div>
                      <div v-if="prestation.internal_code" class="prestation-code" style="margin-top: 0.25rem;">
                        <small :style="{ color: 'var(--text-color-secondary)' }">
                          Code: {{ prestation.internal_code }}
                        </small>
                      </div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>

            <!-- Show files if any (secondary) -->
            <div v-if="convention.uploaded_files && convention.uploaded_files.length" class="uploaded-files-section" style="margin-top: 2rem;">
              <h6 style="color: var(--text-color-secondary); margin-bottom: 1rem;">
                <i class="pi pi-folder" :style="{ color: selectedConventionOrganisme.organism_color || '#3B82F6' }"></i>
                Documents ({{ convention.uploaded_files.length }})
              </h6>
              <div class="files-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <Card
                  v-for="file in convention.uploaded_files"
                  :key="file.id"
                  class="file-card"
                  :style="{
                    borderLeft: `4px solid ${selectedConventionOrganisme.organism_color || '#3B82F6'}`
                  }"
                >
                  <template #content>
                    <div class="file-info" style="display: flex; align-items: center; gap: 1rem;">
                      <div class="file-icon">
                        <i 
                          :class="getFileIcon(file.mime_type || file.original_name)"
                          :style="{ color: selectedConventionOrganisme.organism_color || '#3B82F6', fontSize: '1.5rem' }"
                        ></i>
                      </div>
                      <div class="file-details" style="flex: 1;">
                        <span class="file-name" style="font-weight: 500; color: var(--text-color);">{{ file.original_name }}</span>
                        <small class="file-size" style="display: block; color: var(--text-color-secondary); margin-top: 0.25rem;">{{ formatFileSize(file.size) }}</small>
                      </div>
                      <div class="file-actions" style="display: flex; gap: 0.5rem;">
                        <Button
                          icon="pi pi-eye"
                          severity="info"
                          size="small"
                          @click="viewFile(file)"
                          v-tooltip="'View'"
                          :style="{ 
                            backgroundColor: selectedConventionOrganisme.organism_color || '#3B82F6',
                            borderColor: selectedConventionOrganisme.organism_color || '#3B82F6'
                          }"
                        />
                        <Button
                          icon="pi pi-download"
                          severity="success"
                          size="small"
                          @click="downloadFile(file)"
                          v-tooltip="'Download'"
                        />
                      </div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <Dialog
      v-model:visible="showAllConventionsModal"
      header="All Available Conventions"
      modal
      class="all-conventions-modal"
      :style="{ width: '80vw', maxWidth: '1000px' }"
    >
      <div class="all-conventions-content">
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.fiche-navette-details {
  min-height: 100vh;
  background: var(--surface-ground);
  padding: 1.5rem;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .fiche-navette-details {
    padding: 1rem;
  }
}

/* Add these styles for the modal */
.convention-details-modal .p-dialog-content {
  padding: 0;
}

.organisme-details-content {
  min-height: 400px;
}

.convention-section {
  padding: 1rem;
  border-bottom: 1px solid var(--surface-200);
}

.convention-section:last-child {
  border-bottom: none;
}

.prestations-modal-section {
  margin-bottom: 1rem;
}

.prestation-card {
  transition: all 0.3s ease;
  border-radius: 8px;
  overflow: hidden;
}

.prestation-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.prestation-card .p-card-body {
  padding: 1rem;
}

.file-card {
  transition: all 0.3s ease;
  border-radius: 8px;
  overflow: hidden;
}

.file-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.file-card .p-card-body {
  padding: 1rem;
}

.modal-header {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Responsive design for modal */
@media (max-width: 768px) {
  .prestations-grid {
    grid-template-columns: 1fr !important;
  }
  
  .files-grid {
    grid-template-columns: 1fr !important;
  }
  
  .file-info {
    flex-direction: column !important;
    text-align: center;
    gap: 0.5rem !important;
  }
  
  .file-actions {
    justify-content: center !important;
  }
}
</style>
