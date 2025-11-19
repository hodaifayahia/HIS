
<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Card from 'primevue/card'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import MultiSelect from 'primevue/multiselect'
import Avatar from 'primevue/avatar'

// Composables
const toast = useToast()
const confirm = useConfirm()

// Reactive Data
const packages = ref([])
const selectedPackages = ref([])
const selectedPackage = ref(null)
const loading = ref(false)
const saving = ref(false)
const showCreateDialog = ref(false)
const showViewDialog = ref(false)
const editMode = ref(false)
const globalFilter = ref('')

// Form Data
const packageForm = reactive({
  id: null,
  name: '',
  description: ''
})

const errors = reactive({
  name: '',
  description: ''
})

// Table Columns Configuration
const columns = ref([
  { field: 'name', header: 'Name', sortable: true, style: 'min-width: 200px' },
  { field: 'description', header: 'Description', sortable: true, style: 'min-width: 300px' },
  { field: 'actions', header: 'Actions', sortable: false, style: 'width: 150px' }
])

const selectedColumns = ref([...columns.value])

// API Base URL - Update this to match your Laravel API
const API_BASE_URL = '/api/fiche-navette-custom-packages'

// Methods
const loadPackages = async () => {
  loading.value = true
  try {
    const response = await axios.get(API_BASE_URL)
    if (response.data.success) {
      packages.value = response.data.data
    } else {
      throw new Error('Failed to load packages')
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load packages',
      life: 3000
    })
    console.error('Load packages error:', error)
  } finally {
    loading.value = false
  }
}

const savePackage = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => errors[key] = '')
  
  // Basic validation
  if (!packageForm.name.trim()) {
    errors.name = 'Package name is required'
    return
  }

  saving.value = true
  try {
    let response
    
    if (editMode.value) {
      response = await axios.put(`${API_BASE_URL}/${packageForm.id}`, {
        name: packageForm.name,
        description: packageForm.description
      })
    } else {
      response = await axios.post(API_BASE_URL, {
        name: packageForm.name,
        description: packageForm.description
      })
    }

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Package ${editMode.value ? 'updated' : 'created'} successfully`,
        life: 3000
      })
      
      closeDialog()
      await loadPackages()
    } else {
      throw new Error(response.data.message || 'Operation failed')
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      // Handle validation errors
      Object.keys(error.response.data.errors).forEach(key => {
        errors[key] = error.response.data.errors[key][0]
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || `Failed to ${editMode.value ? 'update' : 'create'} package`,
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const editPackage = (pkg) => {
  editMode.value = true
  packageForm.id = pkg.id
  packageForm.name = pkg.name
  packageForm.description = pkg.description || ''
  showViewDialog.value = false
  showCreateDialog.value = true
}

const viewPackage = (pkg) => {
  selectedPackage.value = pkg
  showViewDialog.value = true
}

const confirmDelete = (pkg) => {
  confirm.require({
    message: `Are you sure you want to delete "${pkg.name}"?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: () => deletePackage(pkg.id)
  })
}

const confirmDeleteSelected = () => {
  if (!selectedPackages.value.length) return
  
  confirm.require({
    message: `Are you sure you want to delete ${selectedPackages.value.length} selected package(s)?`,
    header: 'Confirm Bulk Deletion',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: () => deleteSelectedPackages()
  })
}

const deletePackage = async (id) => {
  try {
    const response = await axios.delete(`${API_BASE_URL}/${id}`)
    
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Package deleted successfully',
        life: 3000
      })
      
      await loadPackages()
    } else {
      throw new Error(response.data.message || 'Delete failed')
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete package',
      life: 3000
    })
  }
}

const deleteSelectedPackages = async () => {
  try {
    const deletePromises = selectedPackages.value.map(pkg => 
      axios.delete(`${API_BASE_URL}/${pkg.id}`)
    )
    
    await Promise.all(deletePromises)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${selectedPackages.value.length} package(s) deleted successfully`,
      life: 3000
    })
    
    selectedPackages.value = []
    await loadPackages()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete selected packages',
      life: 3000
    })
  }
}

const closeDialog = () => {
  showCreateDialog.value = false
  editMode.value = false
  
  // Reset form
  packageForm.id = null
  packageForm.name = ''
  packageForm.description = ''
  
  // Clear errors
  Object.keys(errors).forEach(key => errors[key] = '')
}

const exportData = () => {
  // Simple CSV export
  const csvContent = [
    ['ID', 'Name', 'Description', 'Created At'].join(','),
    ...packages.value.map(pkg => [
      pkg.id,
      `"${pkg.name}"`,
      `"${pkg.description || ''}"`,
      pkg.created_at
    ].join(','))
  ].join('\n')
  
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'packages.csv'
  link.click()
  window.URL.revokeObjectURL(url)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  loadPackages()
})
</script>


<template>
  <div class="packages-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">Custom Packages</h1>
          <p class="page-subtitle">Manage your custom navigation packages</p>
        </div>
        <Button 
          @click="showCreateDialog = true"
          icon="pi pi-plus" 
          label="New Package"
          class="primary-button"
        />
      </div>
    </div>

    <!-- Main Card -->
    <Card class="main-card">
      <template #content>
        <!-- Toolbar -->
        <div class="toolbar-section">
          <div class="toolbar-start">
            <Button 
              icon="pi pi-refresh" 
              @click="loadPackages"
              class="icon-button"
              :loading="loading"
              v-tooltip.top="'Refresh'"
            />
            <Button 
              icon="pi pi-download" 
              @click="exportData"
              class="icon-button"
              label="Export"
              v-tooltip.top="'Export to CSV'"
            />
          </div>
          
          <div class="toolbar-end">
            <div class="search-container">
              <IconField iconPosition="left">
                <InputIcon class="pi pi-search" />
                <InputText 
                  v-model="globalFilter" 
                  placeholder="Search packages..."
                  class="search-input"
                />
              </IconField>
            </div>
            <MultiSelect 
              v-model="selectedColumns" 
              :options="columns" 
              optionLabel="header"
              placeholder="Columns"
              class="column-select"
              display="chip"
            />
          </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
          <DataTable 
            v-model:selection="selectedPackages"
            :value="packages" 
            :loading="loading"
            :globalFilter="globalFilter"
            :paginator="true" 
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} packages"
            class="custom-datatable"
            stripedRows
            responsiveLayout="scroll"
            dataKey="id"
            :globalFilterFields="['name', 'description']"
          >
            <template #header>
              <div class="table-header">
                <h4 class="table-title">Package Management</h4>
                <div class="header-actions">
                  <Button 
                    icon="pi pi-trash" 
                    severity="danger"
                    :disabled="!selectedPackages || !selectedPackages.length"
                    @click="confirmDeleteSelected"
                    class="danger-button"
                    :label="`Delete Selected (${selectedPackages?.length || 0})`"
                  />
                </div>
              </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width: 3rem" />
            
            <Column 
              v-for="col in selectedColumns" 
              :key="col.field"
              :field="col.field" 
              :header="col.header"
              :sortable="col.sortable"
              :style="col.style"
            >
              <template #body="{ data }" v-if="col.field === 'actions'">
                <div class="action-buttons">
                  <Button 
                    icon="pi pi-eye" 
                    @click="viewPackage(data)"
                    class="action-button view-button"
                    v-tooltip.top="'View'"
                  />
                  <Button 
                    icon="pi pi-pencil" 
                    @click="editPackage(data)"
                    class="action-button edit-button"
                    v-tooltip.top="'Edit'"
                  />
                  <Button 
                    icon="pi pi-trash" 
                    @click="confirmDelete(data)"
                    class="action-button delete-button"
                    v-tooltip.top="'Delete'"
                  />
                </div>
              </template>
              
              <template #body="{ data }" v-else-if="col.field === 'description'">
                <span v-if="data.description" class="description-text">
                  {{ data.description.length > 50 ? data.description.substring(0, 50) + '...' : data.description }}
                </span>
                <span v-else class="no-description">No description</span>
              </template>
              
              <template #body="{ data }" v-else-if="col.field === 'name'">
                <div class="name-cell">
                  <Avatar 
                    :label="data.name.charAt(0).toUpperCase()" 
                    class="package-avatar"
                    shape="circle"
                    size="small"
                  />
                  <span class="package-name">{{ data.name }}</span>
                </div>
              </template>

              <template #body="{ data }" v-else-if="col.field === 'created_at'">
                <span class="date-text">{{ formatDate(data.created_at) }}</span>
              </template>

              <template #body="{ data }" v-else-if="col.field === 'updated_at'">
                <span class="date-text">{{ formatDate(data.updated_at) }}</span>
              </template>
            </Column>

            <template #empty>
              <div class="empty-state">
                <i class="pi pi-inbox empty-icon"></i>
                <h3 class="empty-title">No packages found</h3>
                <p class="empty-text">Get started by creating your first package</p>
                <Button 
                  @click="showCreateDialog = true"
                  icon="pi pi-plus" 
                  label="Create First Package"
                  class="primary-button"
                />
              </div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <!-- Create/Edit Dialog -->
    <Dialog 
      v-model:visible="showCreateDialog" 
      :header="editMode ? 'Edit Package' : 'Create New Package'"
      :modal="true" 
      :closable="true"
      :draggable="false"
      class="custom-dialog"
      :style="{ width: '500px' }"
    >
      <form @submit.prevent="savePackage" class="form-container">
        <div class="form-group">
          <label for="name" class="form-label">
            Package Name *
          </label>
          <InputText 
            id="name"
            v-model="packageForm.name" 
            :class="{ 'p-invalid': errors.name }"
            placeholder="Enter package name"
            class="form-input"
            autofocus
          />
          <small v-if="errors.name" class="error-message">{{ errors.name }}</small>
        </div>
        
        <div class="form-group">
          <label for="description" class="form-label">
            Description
          </label>
          <Textarea 
            id="description"
            v-model="packageForm.description" 
            :class="{ 'p-invalid': errors.description }"
            placeholder="Enter package description"
            rows="4"
            :autoResize="true"
            class="form-input"
          />
          <small v-if="errors.description" class="error-message">{{ errors.description }}</small>
        </div>
      </form>

      <template #footer>
        <div class="dialog-footer">
          <Button 
            @click="closeDialog"
            label="Cancel" 
            class="secondary-button"
          />
          <Button 
            @click="savePackage"
            :label="editMode ? 'Update' : 'Create'"
            :loading="saving"
            class="primary-button"
          />
        </div>
      </template>
    </Dialog>

    <!-- View Dialog -->
    <Dialog 
      v-model:visible="showViewDialog" 
      header="Package Details"
      :modal="true" 
      :closable="true"
      class="custom-dialog view-dialog"
      :style="{ width: '600px' }"
    >
      <div v-if="selectedPackage" class="details-container">
        <div class="details-header">
          <Avatar 
            :label="selectedPackage.name.charAt(0).toUpperCase()" 
            class="details-avatar"
            size="xlarge"
          />
          <h3 class="details-title">{{ selectedPackage.name }}</h3>
        </div>
        
        <div class="details-grid">
          <div class="detail-item">
            <label class="detail-label">ID</label>
            <p class="detail-value monospace">{{ selectedPackage.id }}</p>
          </div>
          
          <div class="detail-item full-width">
            <label class="detail-label">Description</label>
            <p class="detail-value">
              {{ selectedPackage.description || 'No description provided' }}
            </p>
          </div>
          
          <div class="detail-item">
            <label class="detail-label">Created At</label>
            <p class="detail-value">{{ formatDate(selectedPackage.created_at) }}</p>
          </div>
          
          <div class="detail-item">
            <label class="detail-label">Updated At</label>
            <p class="detail-value">{{ formatDate(selectedPackage.updated_at) }}</p>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <Button 
            @click="showViewDialog = false"
            label="Close" 
            class="secondary-button"
          />
          <Button 
            @click="editPackage(selectedPackage)"
            label="Edit" 
            class="primary-button"
            icon="pi pi-pencil"
          />
        </div>
      </template>
    </Dialog>

    <!-- Toast Messages -->
    <Toast />
    
    <!-- Confirm Dialog -->
    <ConfirmDialog />
  </div>
</template>

<style scoped>
/* Container */
.packages-container {
  padding: 2rem;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.header-text {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
}

.page-subtitle {
  color: #718096;
  font-size: 1.1rem;
  margin: 0;
}

/* Main Card */
.main-card {
  border-radius: 1rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  border: none;
  overflow: hidden;
}

/* Toolbar */
.toolbar-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  gap: 2rem;
}

.toolbar-start {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.toolbar-end {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-container {
  position: relative;
}

.search-input {
  min-width: 300px;
  border-radius: 0.5rem;
  border: 2px solid #e2e8f0;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  font-size: 0.95rem;
}

.search-input:focus {
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.column-select {
  min-width: 200px;
}

/* Buttons */
.primary-button {
  background: linear-gradient(135deg, #4299e1, #3182ce);
  border: none;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  color: white;
  transition: all 0.2s ease;
}

.primary-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
}

.secondary-button {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  color: #4a5568;
  transition: all 0.2s ease;
}

.secondary-button:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
}

.icon-button {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.75rem;
  color: #4a5568;
  transition: all 0.2s ease;
}

.icon-button:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
}

.danger-button {
  background: linear-gradient(135deg, #f56565, #e53e3e);
  border: none;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  color: white;
}

.danger-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
}

/* Table */
.table-container {
  overflow: hidden;
}

.custom-datatable {
  border: none;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.table-title {
  margin: 0;
  font-weight: 600;
  color: white;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Table Cells */
.name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.package-avatar {
  background: linear-gradient(135deg, #4299e1, #3182ce);
  color: white;
}

.package-name {
  font-weight: 600;
  color: #2d3748;
}

.description-text {
  color: #4a5568;
  line-height: 1.4;
}

.no-description {
  color: #a0aec0;
  font-style: italic;
}

.date-text {
  color: #718096;
  font-size: 0.9rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-button {
  border-radius: 50%;
  width: 2.5rem;
  height: 2.5rem;
  padding: 0;
  border: none;
  transition: all 0.2s ease;
}

.view-button {
  background: #e6fffa;
  color: #38b2ac;
}

.view-button:hover {
  background: #38b2ac;
  color: white;
  transform: scale(1.1);
}

.edit-button {
  background: #fefcbf;
  color: #d69e2e;
}

.edit-button:hover {
  background: #d69e2e;
  color: white;
  transform: scale(1.1);
}

.delete-button {
  background: #fed7d7;
  color: #e53e3e;
}

.delete-button:hover {
  background: #e53e3e;
  color: white;
  transform: scale(1.1);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #718096;
}

.empty-icon {
  font-size: 4rem;
  color: #cbd5e0;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 0.5rem;
}

.empty-text {
  margin-bottom: 2rem;
  font-size: 1.1rem;
}

/* Dialog */
.custom-dialog .p-dialog {
  border-radius: 1rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.custom-dialog .p-dialog-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 1rem 1rem 0 0;
  padding: 1.5rem 2rem;
}

.custom-dialog .p-dialog-title {
  color: white;
  font-weight: 600;
  font-size: 1.2rem;
}

.custom-dialog .p-dialog-content {
  padding: 2rem;
}

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: #2d3748;
  font-size: 0.95rem;
}

.form-input {
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  transition: all 0.2s ease;
}

.form-input:focus {
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-input.p-invalid {
  border-color: #f56565;
}

.error-message {
  color: #f56565;
  font-size: 0.85rem;
  font-weight: 500;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

/* Details Dialog */
.details-container {
  min-width: 500px;
}

.details-header {
  text-align: center;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 2rem;
}

.details-avatar {
  background: linear-gradient(135deg, #4299e1, #3182ce);
  color: white;
  margin-bottom: 1rem;
}

.details-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-label {
  font-weight: 600;
  color: #718096;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  color: #2d3748;
  font-size: 1rem;
  margin: 0;
  line-height: 1.4;
}

.monospace {
  font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
  background: #f7fafc;
  padding: 0.5rem;
  border-radius: 0.25rem;
  border: 1px solid #e2e8f0;
  font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
  .packages-container {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .page-title {
    font-size: 2rem;
  }

  .toolbar-section {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .toolbar-start {
    justify-content: center;
  }

  .toolbar-end {
    flex-direction: column;
    gap: 1rem;
  }

  .search-input {
    min-width: auto;
    width: 100%;
  }

  .column-select {
    min-width: auto;
    width: 100%;
  }

  .table-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .action-buttons {
    justify-content: center;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .details-container {
    min-width: auto;
  }

  .dialog-footer {
    flex-direction: column;
    gap: 0.75rem;
  }

  .form-container {
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.75rem;
  }

  .page-subtitle {
    font-size: 1rem;
  }

  .toolbar-section {
    padding: 1rem;
  }

  .custom-dialog .p-dialog-content {
    padding: 1rem;
  }

  .dialog-footer {
    padding: 1rem;
  }

  .details-header {
    padding-bottom: 1rem;
    margin-bottom: 1rem;
  }

  .empty-state {
    padding: 2rem 1rem;
  }

  .empty-icon {
    font-size: 3rem;
  }
}

/* Table Hover Effects */
.custom-datatable :deep(.p-datatable-tbody > tr:hover) {
  background: rgba(66, 153, 225, 0.05) !important;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.custom-datatable :deep(.p-datatable-tbody > tr.p-highlight) {
  background: rgba(66, 153, 225, 0.1) !important;
}

/* Loading States */
.custom-datatable :deep(.p-datatable-loading-overlay) {
  background: rgba(255, 255, 255, 0.9) !important;
  backdrop-filter: blur(2px);
}

/* Pagination */
.custom-datatable :deep(.p-paginator) {
  background: #f8fafc;
  border: none;
  border-top: 1px solid #e2e8f0;
  padding: 1rem;
}

.custom-datatable :deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: #4299e1;
  border-color: #4299e1;
}

/* Multi-select */
.column-select :deep(.p-multiselect-label) {
  padding: 0.75rem 1rem;
}

.column-select :deep(.p-multiselect-trigger) {
  border-left: 1px solid #e2e8f0;
}

/* Scrollbar Styling */
.table-container::-webkit-scrollbar {
  height: 6px;
}

.table-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.table-container::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.table-container::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

/* Animation for buttons */
@keyframes buttonPulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}

.primary-button:active,
.danger-button:active {
  animation: buttonPulse 0.2s ease-in-out;
}

/* Tooltip customization */
.p-tooltip {
  font-size: 0.85rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  background: #2d3748;
  color: white;
}
</style>