<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import Chip from 'primevue/chip'
import Toolbar from 'primevue/toolbar'
import DataView from 'primevue/dataview'
import Skeleton from 'primevue/skeleton'
import ConfirmDialog from 'primevue/confirmdialog'

// Components
import PrestationsPackagesModal from '../../../../Components/Apps/Configuration/PrestationPackage/PrestationsPackagesModal.vue'
import ClonePackageCLone from '../../../../Components/Apps/Configuration/PrestationPackage/ClonePackageCLone.vue'

// Services
import prestationPackageService from '../../../../Components/Apps/services/Prestation/prestationPackageService.js'

// Composables
const toast = useToast()
const confirm = useConfirm()

// State
const packages = ref([])
const loading = ref(false)
const error = ref(null)
const searchTerm = ref('')
const showModal = ref(false)
const showCloneModal = ref(false)
const selectedPackage = ref(null)
const isEditMode = ref(false)

// Computed
const filteredPackages = computed(() => {
  if (!searchTerm.value) return packages.value
  
  const search = searchTerm.value.toLowerCase()
  return packages.value.filter(pkg => 
    pkg.name?.toLowerCase().includes(search) ||
    pkg.description?.toLowerCase().includes(search)
  )
})

const totalPackages = computed(() => packages.value.length)
const activePackages = computed(() => packages.value.filter(pkg => pkg.is_active).length)

// Methods
const loadPackages = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await prestationPackageService.getAll()
    if (response.success) {
      packages.value = response.data || []
    } else {
      throw new Error(response.message || 'Failed to load packages')
    }
  } catch (err) {
    error.value = err.message || 'Failed to load prestation packages'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.value,
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  selectedPackage.value = null
  isEditMode.value = false
  showModal.value = true
}

const openEditModal = (packageData) => {
  selectedPackage.value = { ...packageData }
  isEditMode.value = true
  showModal.value = true
}

const openCloneModal = (packageData) => {
  selectedPackage.value = { ...packageData }
  showCloneModal.value = true
}

const confirmDelete = (packageData) => {
  console.log('Delete button clicked for:', packageData); // Debugging
  confirm.require({
    message: `Are you sure you want to delete the package "${packageData.name}"? This action cannot be undone.`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Delete',
    accept: () => deletePackage(packageData.id)
  });
};

const deletePackage = async (packageId) => {
  try {
    const response = await prestationPackageService.delete(packageId)
    if (response.success) {
      // Remove from local state without API call
      packages.value = packages.value.filter(pkg => pkg.id !== packageId)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Package deleted successfully',
        life: 3000
      })
    } else {
      throw new Error(response.message || 'Failed to delete package')
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Failed to delete package',
      life: 3000
    })
  }
}

const handlePackageSaved = (packageData, mode) => {
  if (mode === 'create') {
    // Add to local state without API call
    packages.value.unshift(packageData)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Package created successfully',
      life: 3000
    })
  } else if (mode === 'edit') {
    // Update in local state without API call
    const index = packages.value.findIndex(pkg => pkg.id === packageData.id)
    if (index !== -1) {
      packages.value[index] = packageData
    }
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Package updated successfully',
      life: 3000
    })
  }
  showModal.value = false
}

const handlePackageCloned = (clonedPackage) => {
  // Add to local state without API call
  packages.value.unshift(clonedPackage)
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Package cloned successfully',
    life: 3000
  })
  showCloneModal.value = false
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const getStatusSeverity = (isActive) => {
  return isActive ? 'success' : 'danger'
}

const getStatusLabel = (isActive) => {
  return isActive ? 'Active' : 'Inactive'
}

// Lifecycle
onMounted(() => {
  loadPackages()
})
</script>

<template>
  <div class="packages-page min-vh-100 bg-light p-4">
    <div class="container-fluid">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Prestation Packages</h1>
          <p class="text-muted mb-0 small">Manage your prestation packages and their items</p>
        </div>
        <Button 
          icon="pi pi-plus" 
          label="New Package"
          class="p-button-primary"
          @click="openCreateModal"
        />
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
          <Card class="stats-card border-0 shadow-sm h-100">
            <template #content>
              <div class="d-flex align-items-center">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                  <i class="fas fa-box-open fa-lg"></i>
                </div>
                <div>
                  <div class="stats-value h4 mb-0 fw-bold">{{ totalPackages }}</div>
                  <div class="stats-label text-muted small">Total Packages</div>
                </div>
              </div>
            </template>
          </Card>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
          <Card class="stats-card border-0 shadow-sm h-100">
            <template #content>
              <div class="d-flex align-items-center">
                <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                  <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div>
                  <div class="stats-value h4 mb-0 fw-bold">{{ activePackages }}</div>
                  <div class="stats-label text-muted small">Active Packages</div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Search and Filters -->
      <Card class="shadow-sm mb-4">
        <template #content>
          <Toolbar class="border-0 p-0">
            <template #start>
              <div class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText 
                  v-model="searchTerm"
                  placeholder="Search packages..."
                  class="w-100"
                  style="min-width: 300px;"
                />
              </div>
            </template>
            <template #end>
              <Button 
                icon="pi pi-refresh" 
                class="p-button-outlined p-button-secondary"
                @click="loadPackages"
                :loading="loading"
                label="Refresh"
              />
            </template>
          </Toolbar>
        </template>
      </Card>

      <!-- Main Content -->
      <Card class="shadow-sm">
        <template #content>
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
            <p class="text-muted mt-3 mb-0">Loading packages...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>{{ error }}</div>
          </div>

          <!-- Empty State -->
          <div v-else-if="packages.length === 0" class="text-center py-5">
            <div class="empty-state">
              <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No Packages Yet</h5>
              <p class="text-muted mb-4">Create your first prestation package to get started</p>
              <Button 
                icon="pi pi-plus" 
                label="Create Package"
                class="p-button-primary"
                @click="openCreateModal"
              />
            </div>
          </div>

          <!-- Packages Grid -->
          <div v-else class="row">
            <div 
              v-for="packageItem in filteredPackages" 
              :key="packageItem.id"
              class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4"
            >
              <Card class="package-card h-100 border-0 shadow-sm">
                <template #header>
                  <div class="package-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="package-icon">
                        <i class="fas fa-box-open fa-lg text-primary"></i>
                      </div>
                      <Tag 
                        :value="getStatusLabel(packageItem.is_active)"
                        :severity="getStatusSeverity(packageItem.is_active)"
                        class="package-status"
                      />
                    </div>
                  </div>
                </template>

                <template #title>
                  <div class="px-3">
                    <h6 class="mb-1 text-truncate package-title" :title="packageItem.name">
                      {{ packageItem.name || 'Unnamed Package' }}
                    </h6>
                  </div>
                </template>

                <template #content>
                  <div class="px-3 pb-3">
                    <p class="text-muted small mb-3 package-description">
                      {{ packageItem.description || 'No description available' }}
                    </p>
                    
                    <div class="package-details">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Price:</span>
                        <span class="fw-bold text-primary">{{ formatCurrency(packageItem.price) }}</span>
                      </div>
                      
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Items:</span>
                        <Chip 
                          :label="`${packageItem.items?.length || 0} prestations`"
                          class="p-chip-sm"
                        />
                      </div>
                    </div>
                  </div>
                </template>

                <template #footer>
                  <div class="p-3 pt-0">
                    <div class="d-flex justify-content-end gap-2">
                      <Button 
                        icon="pi pi-eye"
                        class="p-button-sm p-button-outlined p-button-secondary"
                        v-tooltip.top="'View Details'"
                        @click="openEditModal(packageItem)"
                      />
                      <Button 
                        icon="pi pi-copy"
                        class="p-button-sm p-button-outlined p-button-info"
                        v-tooltip.top="'Clone Package'"
                        @click="openCloneModal(packageItem)"
                      />
                      <Button 
                        icon="pi pi-pencil"
                        class="p-button-sm p-button-outlined p-button-primary"
                        v-tooltip.top="'Edit Package'"
                        @click="openEditModal(packageItem)"
                      />
                     <Button 
  icon="pi pi-trash"
  class="p-button-sm p-button-outlined p-button-danger"
  v-tooltip.top="'Delete Package'"
  @click="confirmDelete(packageItem)"
/>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </div>

          <ConfirmDialog />


          <!-- No Search Results -->
          <div v-if="!loading && filteredPackages.length === 0 && packages.length > 0" class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No packages found</h5>
            <p class="text-muted">Try adjusting your search criteria</p>
          </div>
        </template>
      </Card>
    </div>


    <!-- Modals -->
    <PrestationsPackagesModal
      v-model:visible="showModal"
      :package-data="selectedPackage"
      :is-edit-mode="isEditMode"
      @package-saved="handlePackageSaved"
    />

    <ClonePackageCLone
      v-model:visible="showCloneModal"
      :package-data="selectedPackage"
      @package-cloned="handlePackageCloned"
    />
  </div>
</template>

<style scoped>
.packages-page {
  background: #f8f9fa;
}

.stats-card {
  border-radius: 12px;
  transition: transform 0.2s ease;
}

.stats-card:hover {
  transform: translateY(-2px);
}

.stats-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stats-value {
  font-size: 1.75rem;
}

.package-card {
  border-radius: 12px;
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid transparent;
}

.package-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
  border-color: var(--primary-color);
}

.package-header {
  background: var(--surface-50);
}

.package-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-50);
  border-radius: 8px;
}

.package-title {
  color: var(--text-color);
  font-weight: 600;
}

.package-description {
  line-height: 1.4;
  max-height: 3.2em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.package-status {
  font-size: 0.75rem;
  font-weight: 600;
}

.empty-state {
  max-width: 400px;
  margin: 0 auto;
}

.p-card {
  border-radius: 12px;
}

.p-toolbar {
  background: transparent;
}

.p-input-icon-left > .p-inputtext {
  padding-left: 2.5rem;
}

.p-chip-sm {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

@media (max-width: 768px) {
  .packages-page {
    padding: 1rem !important;
  }
  
  .row > [class*="col-"] {
    margin-bottom: 1rem;
  }
  
  .package-card .d-flex {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .package-card .d-flex > button {
    width: 100%;
  }
}
</style>