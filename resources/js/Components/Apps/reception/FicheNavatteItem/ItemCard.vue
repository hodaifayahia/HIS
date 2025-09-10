<!-- components/Reception/FicheNavette/ItemCard.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Chip from 'primevue/chip'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dropdown from 'primevue/dropdown'

// Import RemiseModal component
import RemiseModal from './RemiseModal.vue'

// Import the service
import { ficheNavetteService } from '../../../Apps/services/Reception/ficheNavetteService'

// Types
interface ItemGroup {
  id: string
  type: 'package' | 'prestation'
  name: string
  doctor_name?: string
  total_price: number
  items: ItemDetail[]
}

interface ItemDetail {
  id: string
  prestation_id?: string
  package_id?: string
  prestation?: Prestation
  package?: Package
  status: string
  base_price: number
  final_price: number
  patient_share: number
  convention_id?: string
  convention_name?: string
  dependencies?: Dependency[]
  isDependency?: boolean
  originalDependency?: Dependency
}

interface Prestation {
  id: string
  name: string
  internal_code: string
  public_price: number
  is_package: boolean
  specialization_name?: string
}

interface Package {
  id: string
  name: string
  internal_code: string
  public_price: number
}

interface Doctor {
  id: string
  name: string
  specialization: string
}

interface Dependency {
  id: string
  dependency_type: string
  notes?: string
  dependencyPrestation?: Prestation
  dependency_prestation?: Prestation
  status?: string
  parentItem?: ItemDetail
}

// Props
const props = defineProps<{
  group: ItemGroup
  prestations: Prestation[]
  packages: Package[]
  doctors: Doctor[]
}>()

// Emits
const emit = defineEmits<{
  'remove-item': [itemId: string]
  'item-updated': [data: { itemId?: string; status?: string; refresh?: boolean }]
  'dependency-removed': [dependencyId: string]
  'apply-remise': [groupId: string]
}>()

// Composables
const toast = useToast()
const confirm = useConfirm()

// State
const showDetailsModal = ref(false)
const showRemiseModal = ref(false)

// Computed properties
const cardTitle = computed(() => {
  if (props.group.type === 'package') {
    return props.group.name || 'Package'
  }
  return props.group.name || 'Prestation'
})

const cardSubtitle = computed(() => {
  if (props.group.doctor_name) {
    return `Dr. ${props.group.doctor_name}`
  }
  return 'No doctor assigned'
})

// Check if any items have convention_id
const hasConventionItems = computed(() => {
  return props.group.items?.some(item => 
    item.convention_id && item.convention_id !== null
  ) || false
})

// Get convention information from items
const conventionInfo = computed(() => {
  const conventionItems = props.group.items?.filter(item => 
    item.convention_id && item.convention_id !== null
  ) || []
  
  if (conventionItems.length === 0) {
    return null
  }
  
  // Get unique conventions
  const conventions = conventionItems.reduce((acc, item) => {
    const key = item.convention_id
    if (!acc[key]) {
      acc[key] = {
        id: item.convention_id,
        name: item.convention_name || `Convention ${item.convention_id}`,
        count: 0
      }
    }
    acc[key].count++
    return acc
  }, {})
  
  return Object.values(conventions)
})

// Card styling based on convention and type
const cardStyleClass = computed(() => {
  if (!hasConventionItems.value) {
    return 'item-card default-card'
  }
  
  if (props.group.type === 'package') {
    return 'item-card convention-package-card'
  }
  
  return 'item-card convention-prestation-card'
})

const cardIconStyle = computed(() => {
  if (!hasConventionItems.value) {
    return {}
  }
  
  if (props.group.type === 'package') {
    return {
      background: 'var(--orange-500, #fd7e14)',
      color: 'white'
    }
  }
  
  return {
    background: 'var(--green-500, #28a745)',
    color: 'white'
  }
})

const conventionChips = computed(() => {
  if (!conventionInfo.value || conventionInfo.value.length === 0) {
    return []
  }
  
  return conventionInfo.value.map(conv => ({
    label: conv.count > 1 ? `${conv.name} (${conv.count})` : conv.name,
    severity: props.group.type === 'package' ? 'warning' : 'success',
    id: conv.id
  }))
})

// Get all dependencies from all items in the group
const allDependencies = computed(() => {
  const dependencies = []
  if (props.group.items) {
    props.group.items.forEach(item => {
      if (item.dependencies && Array.isArray(item.dependencies)) {
        item.dependencies.forEach(dep => {
          dependencies.push({
            ...dep,
            parentItem: item
          })
        })
      }
    })
  }
  return dependencies
})

// Get package dependencies (dependencies that have is_package = true)
const packageDependencies = computed(() => {
  return allDependencies.value.filter(dep => {
    const prestation = dep.dependencyPrestation || dep.dependency_prestation
    return prestation && prestation.is_package === true
  })
})

// Get regular dependencies (dependencies that don't have is_package or is_package = false)
const regularDependencies = computed(() => {
  return allDependencies.value.filter(dep => {
    const prestation = dep.dependencyPrestation || dep.dependency_prestation
    return !prestation || prestation.is_package !== true
  })
})

// Combined items for main display (original items + package dependencies)
const mainDisplayItems = computed(() => {
  const items = [...(props.group.items || [])]
  
  // Add package dependencies as main items
  packageDependencies.value.forEach(dep => {
    const prestation = dep.dependencyPrestation || dep.dependency_prestation
    if (prestation) {
      items.push({
        id: `dep_${dep.id || Math.random()}`,
        prestation: prestation,
        status: dep.status || 'required',
        base_price: prestation.public_price || 0,
        final_price: prestation.public_price || 0,
        patient_share: prestation.public_price || 0,
        dependencies: [],
        isDependency: true,
        originalDependency: dep
      })
    }
  })
  
  return items
})

// Status options for dropdown
const statusOptions = [
  { label: 'Pending', value: 'pending', severity: 'warning' },
  { label: 'In Progress', value: 'in_progress', severity: 'info' },
  { label: 'Completed', value: 'completed', severity: 'success' },
  { label: 'Cancelled', value: 'cancelled', severity: 'danger' },
  { label: 'Required', value: 'required', severity: 'secondary' }
]

// Methods
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
  }).format(amount || 0)
}

const getStatusData = (status: string) => {
  return statusOptions.find(option => option.value === status) || statusOptions[0]
}

const getItemTypeIcon = (item: ItemDetail) => {
  if (item.isDependency && item.prestation?.is_package) return 'pi pi-box'
  if (item.prestation_id) return 'pi pi-medical'
  if (item.package_id) return 'pi pi-box'
  return 'pi pi-circle'
}

const getItemTypeBadge = (item: ItemDetail) => {
  if (item.isDependency && item.prestation?.is_package) return { label: 'Package', severity: 'warning' }
  if (item.prestation_id) return { label: 'Prestation', severity: 'success' }
  if (item.package_id) return { label: 'Package', severity: 'info' }
  return { label: 'Unknown', severity: 'secondary' }
}

const getConventionBadge = (item: ItemDetail) => {
  if (!item.convention_id) return null
  
  return {
    label: item.convention_name || `Convention ${item.convention_id}`,
    severity: 'success'
  }
}

const updateItemStatus = async (item: ItemDetail, newStatus: string) => {
  console.log('Updating item status:', item.id, newStatus)
  emit('item-updated', { itemId: item.id, status: newStatus })
}

const openDetails = () => {
  showDetailsModal.value = true
}

const openRemiseModal = () => {
  showRemiseModal.value = true
}

const removeItem = (itemId: string) => {
  emit('remove-item', itemId)
}

const removeDependency = async (dependency: Dependency) => {
  try {
    const result = await ficheNavetteService.removeDependency(dependency.id)
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Dependency removed successfully',
        life: 3000
      })
      
      // Emit event to refresh the parent component
      emit('dependency-removed', dependency.id)
      emit('item-updated', { refresh: true })
    } else {
      throw new Error(result.message || 'Failed to remove dependency')
    }
  } catch (error) {
    console.error('Error removing dependency:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to remove dependency',
      life: 3000
    })
  }
}

const confirmRemoveDependency = (dependency: Dependency) => {
  confirm.require({
    message: `Are you sure you want to remove the dependency "${dependency.dependencyPrestation?.name || dependency.dependency_prestation?.name || 'Unknown'}"?`,
    header: 'Remove Dependency',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => removeDependency(dependency),
    reject: () => {
      // User cancelled
    }
  })
}

const handleApplyRemise = (data: any) => {
  emit('apply-remise', props.group.id)
  
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Remise applied successfully',
    life: 3000
  })
  
  // Refresh the component data
  emit('item-updated', { refresh: true })
}
</script>

<template>
  <div :class="cardStyleClass">
    <!-- Header -->
    <div class="card-header">
      <div class="header-left">
        <div class="card-icon" :style="cardIconStyle">
          <i :class="group.type === 'package' ? 'pi pi-box' : 'pi pi-medical'"></i>
        </div>
        <div class="header-info">
          <h6 class="card-title">{{ cardTitle }}</h6>
          <small class="card-subtitle">{{ cardSubtitle }}</small>
        </div>
      </div>
      <div class="header-actions">
        <!-- Convention chips -->
        <div v-if="conventionChips.length > 0" class="convention-chips">
          <Chip
            v-for="chip in conventionChips"
            :key="chip.id"
            :label="chip.label"
            :severity="chip.severity"
            class="convention-chip"
          />
        </div>
        
        <Chip
          :label="group.type === 'package' ? 'Package' : 'Prestation'"
          :severity="group.type === 'package' ? 'info' : 'success'"
          class="type-chip"
        />
      </div>
    </div>

    <!-- Content -->
    <div class="card-content">
      <!-- Convention info if available -->
      <div v-if="hasConventionItems" class="convention-info">
        <div class="info-item">
          <span class="info-label">Conventions:</span>
          <div class="convention-list">
            <Chip
              v-for="conv in conventionInfo"
              :key="conv.id"
              :label="conv.count > 1 ? `${conv.name} (${conv.count} items)` : conv.name"
              :severity="group.type === 'package' ? 'warning' : 'success'"
              class="convention-detail"
            />
          </div>
        </div>
      </div>

      <!-- Summary Info -->
      <div class="summary-info">
        <div class="info-item">
          <span class="info-label">Items:</span>
          <Chip
            :label="`${mainDisplayItems.length} item${mainDisplayItems.length !== 1 ? 's' : ''}`"
            severity="secondary"
          />
        </div>
        <div class="info-item">
          <span class="info-label">Total:</span>
          <strong class="total-price">{{ formatCurrency(group.total_price) }}</strong>
        </div>
      </div>

      <!-- Dependencies Summary -->
      <div v-if="regularDependencies.length > 0" class="dependencies-summary">
        <div class="info-item">
          <span class="info-label">Dependencies:</span>
          <Chip
            :label="`${regularDependencies.length} dependency${regularDependencies.length !== 1 ? 'ies' : 'y'}`"
            severity="warning"
          />
        </div>
        <div class="dependencies-preview">
          <div
            v-for="(dependency, index) in regularDependencies.slice(0, 3)"
            :key="index"
            class="dependency-chip"
          >
            <Chip
              :label="dependency.dependencyPrestation?.name || dependency.dependency_prestation?.name || 'Unknown'"
              severity="secondary"
              class="dependency-item"
            />
          </div>
          <Chip
            v-if="regularDependencies.length > 3"
            :label="`+${regularDependencies.length - 3} more`"
            severity="info"
            class="more-deps"
          />
        </div>
      </div>
      
      <!-- Package Dependencies Info -->
      <div v-if="packageDependencies.length > 0" class="package-dependencies-summary">
        <div class="info-item">
          <span class="info-label">Package Dependencies:</span>
          <Chip
            :label="`${packageDependencies.length} package${packageDependencies.length !== 1 ? 's' : ''}`"
            severity="info"
          />
        </div>
      </div>
    </div>

    <!-- Footer - Fixed at bottom -->
    <div class="card-footer">
      <Button
        icon="pi pi-percentage"
        label="Remise"
        class="p-button-outlined p-button-warning p-button-sm"
        @click="openRemiseModal"
      />
      <Button
        icon="pi pi-eye"
        label="Details"
        class="p-button-outlined p-button-secondary p-button-sm"
        @click="openDetails"
      />
      <Button
        icon="pi pi-trash"
        label="Remove"
        class="p-button-outlined p-button-danger p-button-sm"
        @click="removeItem(group.items?.[0]?.id)"
      />
    </div>

    <!-- Details Modal -->
    <Dialog
      v-model:visible="showDetailsModal"
      :header="`${cardTitle} - Details`"
      :style="{ width: '1100px', maxHeight: '90vh' }"
      :modal="true"
      class="details-modal"
    >
      <div class="details-content">
        <!-- Group Info -->
        <Card class="group-info mb-4">
          <template #content>
            <div class="group-details">
              <div class="detail-item">
                <span class="detail-label">Type:</span>
                <Chip
                  :label="group.type === 'package' ? 'Package' : 'Individual Prestation'"
                  :severity="group.type === 'package' ? 'info' : 'success'"
                />
              </div>
              
              <div v-if="hasConventionItems" class="detail-item">
                <span class="detail-label">Conventions:</span>
                <div class="convention-badges">
                  <Chip
                    v-for="conv in conventionInfo"
                    :key="conv.id"
                    :label="`${conv.name} (${conv.count} items)`"
                    :severity="group.type === 'package' ? 'warning' : 'success'"
                  />
                </div>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Doctor:</span>
                <span>{{ group.doctor_name || 'Not assigned' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Total Price:</span>
                <strong class="total-amount">{{ formatCurrency(group.total_price) }}</strong>
              </div>
              <div class="detail-item">
                <span class="detail-label">Regular Dependencies:</span>
                <Chip
                  :label="`${regularDependencies.length} dependencies`"
                  severity="warning"
                />
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Package Dependencies:</span>
                <Chip
                  :label="`${packageDependencies.length} packages`"
                  severity="info"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- Main Items Table (including package dependencies) -->
        <Card class="mb-4">
          <template #title>
            <div class="table-title">
              <i class="pi pi-list"></i>
              Main Items ({{ mainDisplayItems.length }})
            </div>
          </template>
          <template #content>
            <DataTable
              v-if="mainDisplayItems && mainDisplayItems.length > 0"
              :value="mainDisplayItems"
              class="items-table"
              responsiveLayout="scroll"
              :rowHover="true"
            >
              <Column field="name" header="Name">
                <template #body="{ data }">
                  <div class="item-name-cell">
                    <i :class="getItemTypeIcon(data)" class="item-icon"></i>
                    <div class="item-details">
                      <div class="item-name">{{ data.prestation?.name || data.package?.name || 'Unknown' }}</div>
                      <small class="item-code">{{ data.prestation?.internal_code || data.package?.internal_code || 'N/A' }}</small>
                      
                      <!-- Show convention badge -->
                      <div v-if="getConventionBadge(data)" class="convention-badge">
                        <Chip 
                          :label="getConventionBadge(data).label" 
                          :severity="getConventionBadge(data).severity" 
                          size="small"
                        />
                      </div>
                      
                      <!-- Show if it's a dependency -->
                      <div v-if="data.isDependency" class="dependency-badge">
                        <Chip 
                          :label="getItemTypeBadge(data).label" 
                          :severity="getItemTypeBadge(data).severity" 
                          size="small"
                        />
                      </div>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="status" header="Status">
                <template #body="{ data }">
                  <Tag
                    :value="getStatusData(data.status).label"
                    :severity="getStatusData(data.status).severity"
                  />
                </template>
              </Column>

              <Column field="base_price" header="Base Price">
                <template #body="{ data }">
                  {{ formatCurrency(data.base_price) }}
                </template>
              </Column>

              <Column field="final_price" header="Final Price">
                <template #body="{ data }">
                  <strong>{{ formatCurrency(data.final_price) }}</strong>
                </template>
              </Column>

              <Column field="patient_share" header="Patient Share">
                <template #body="{ data }">
                  {{ formatCurrency(data.patient_share) }}
                </template>
              </Column>

              <Column header="Dependencies">
                <template #body="{ data }">
                  <div v-if="data.isDependency" class="dependency-info">
                    <!-- <Chip label="From dependency" severity="warning" size="small" /> -->
                  </div>
                  <div v-else-if="data.dependencies && data.dependencies.length > 0">
                    <Chip
                      :label="`${data.dependencies.length} deps`"
                      severity="warning"
                    />
                  </div>
                  <span v-else class="text-muted">No dependencies</span>
                </template>
              </Column>

              <Column header="Actions">
                <template #body="{ data }">
                  <Button
                    icon="pi pi-trash"
                    class="p-button-rounded p-button-text p-button-sm p-button-danger"
                    @click="removeItem(data.id)"
                    v-tooltip.top="'Remove item'"
                  />
                </template>
              </Column>
            </DataTable>
            <div v-else class="no-items">
              <p>No items found in this group.</p>
            </div>
          </template>
        </Card>

        <!-- Regular Dependencies Table -->
        <Card v-if="regularDependencies.length > 0" class="mb-4">
          <template #title>
            <div class="table-title">
              <i class="pi pi-sitemap"></i>
              Regular Dependencies ({{ regularDependencies.length }})
            </div>
          </template>
          <template #content>
            <DataTable
              :value="regularDependencies"
              class="dependencies-table"
              responsiveLayout="scroll"
              :rowHover="true"
            >
              <Column field="dependency_name" header="Dependency Name">
                <template #body="{ data }">
                  <div class="dependency-name-cell">
                    <i class="pi pi-arrow-right dependency-arrow"></i>
                    <div class="dependency-details">
                      <div class="dependency-name">
                        {{ data.dependencyPrestation?.name || data.dependency_prestation?.name || 'Unknown' }}
                      </div>
                      <small class="dependency-code">
                        {{ data.dependencyPrestation?.internal_code || data.dependency_prestation?.internal_code || 'N/A' }}
                      </small>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="dependency_type" header="Type">
                <template #body="{ data }">
                  <Tag
                    :value="data.dependency_type || 'standard'"
                    :severity="data.dependency_type === 'required' ? 'danger' : 'info'"
                  />
                </template>
              </Column>

              <Column field="specialization" header="Specialization">
                <template #body="{ data }">
                  <Chip
                    :label="data.dependencyPrestation?.specialization_name || data.dependency_prestation?.specialization_name || 'N/A'"
                    severity="secondary"
                  />
                </template>
              </Column>

              <Column field="price" header="Price">
                <template #body="{ data }">
                  {{ formatCurrency(data.dependencyPrestation?.public_price || data.dependency_prestation?.public_price || 0) }}
                </template>
              </Column>

              <Column field="notes" header="Notes">
                <template #body="{ data }">
                  <span class="notes-text">{{ data.notes || 'No notes' }}</span>
                </template>
              </Column>

              <Column header="Parent Item">
                <template #body="{ data }">
                  <small class="parent-item">
                    {{ data.parentItem?.prestation?.name || data.parentItem?.package?.name || 'Unknown' }}
                  </small>
                </template>
              </Column>

              <!-- Add Actions Column for Delete Button -->
              <Column header="Actions" style="width: 120px">
                <template #body="{ data }">
                  <div class="dependency-actions">
                    <Button
                      icon="pi pi-trash"
                      class="p-button-rounded p-button-text p-button-sm p-button-danger"
                      @click="confirmRemoveDependency(data)"
                      v-tooltip.top="'Remove dependency'"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <template #footer>
        <Button
          label="Close"
          icon="pi pi-times"
          class="p-button-text"
          @click="showDetailsModal = false"
        />
      </template>
    </Dialog>

    <!-- Remise Modal -->
    <RemiseModal
      v-model:visible="showRemiseModal"
      :group="group"
      :prestations="prestations"
      :doctors="doctors"
      @apply-remise="handleApplyRemise"
    />
  </div>
</template>

<style scoped>
/* Base Card Styles - Using Flexbox Layout */
.item-card {
  margin-bottom: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  border: 2px solid transparent;
  border-radius: 8px;
  background: white;
  overflow: hidden;
  
  /* Flexbox for consistent layout */
  display: flex;
  flex-direction: column;
  min-height: 280px; /* Ensures consistent card height */
}

.item-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}

/* Default card (no convention) */
.default-card {
  border-color: #e9ecef;
}

/* Convention-based card styles */
.convention-prestation-card {
  border-color: var(--green-500, #28a745);
  background: linear-gradient(135deg, rgba(40, 167, 69, 0.02) 0%, rgba(40, 167, 69, 0.05) 100%);
}

.convention-prestation-card:hover {
  border-color: var(--green-600, #218838);
  box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
}

.convention-package-card {
  border-color: var(--orange-500, #fd7e14);
  background: linear-gradient(135deg, rgba(253, 126, 20, 0.02) 0%, rgba(253, 126, 20, 0.05) 100%);
}

.dependency-actions {
  display: flex;
  justify-content: center;
  align-items: center;
}

.dependency-actions .p-button {
  transition: all 0.2s ease;
}

.dependency-actions .p-button:hover {
  transform: scale(1.1);
}

/* Add some spacing for the actions column */
.dependencies-table :deep(.p-datatable-tbody > tr > td:last-child) {
  text-align: center;
}

.convention-package-card:hover {
  border-color: var(--orange-600, #e86503);
  box-shadow: 0 4px 8px rgba(253, 126, 20, 0.2);
}

/* Header Styles */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e9ecef;
  flex-shrink: 0; /* Prevents header from shrinking */
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-icon {
  background: var(--primary-color, #007bff);
  color: white;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.header-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
  color: #2c3e50;
}

.card-subtitle {
  color: #6c757d;
  font-size: 0.85rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  flex-shrink: 0;
}

.convention-chips {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.convention-chip {
  font-weight: 500;
  font-size: 0.8rem;
}

.type-chip {
  white-space: nowrap;
}

/* Content Styles - Flexible content area */
.card-content {
  padding: 1rem;
  flex: 1; /* Takes available space, pushing footer to bottom */
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.convention-info {
  padding-bottom: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.convention-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.convention-detail {
  font-size: 0.85rem;
}

.convention-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.summary-info {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.info-label {
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
}

.total-price {
  color: #28a745;
  font-size: 1.1rem;
  font-weight: 600;
}

.dependencies-summary {
  border-top: 1px solid #e9ecef;
  padding-top: 1rem;
}

.package-dependencies-summary {
  border-top: 1px solid #e9ecef;
  padding-top: 1rem;
}

.dependencies-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

/* Footer Styles - Fixed at bottom */
.card-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
  flex-shrink: 0; /* Prevents footer from shrinking */
  margin-top: auto; /* Pushes footer to bottom when content is short */
}

/* Modal Styles */
.details-modal {
  max-height: 90vh;
}

.details-content {
  max-height: 70vh;
  overflow-y: auto;
}

.group-info .group-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
}

.total-amount {
  color: #28a745;
  font-size: 1.2rem;
}

/* Table Styles */
.table-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.item-name-cell,
.dependency-name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.item-icon {
  color: #007bff;
  font-size: 1.1rem;
}

.dependency-arrow {
  color: #007bff;
  font-size: 0.9rem;
}

.item-details,
.dependency-details {
  display: flex;
  flex-direction: column;
}

.item-name,
.dependency-name {
  font-weight: 500;
  color: #2c3e50;
}

.item-code,
.dependency-code {
  color: #6c757d;
  font-size: 0.8rem;
}

.convention-badge,
.dependency-badge {
  margin-top: 0.25rem;
}

.dependency-info {
  font-style: italic;
  color: #f39c12;
}

.notes-text {
  color: #495057;
  font-style: italic;
}

.parent-item {
  color: #6c757d;
  font-size: 0.8rem;
}

.no-items {
  text-align: center;
  padding: 2rem;
  color: #6c757d;
}

.text-muted {
  color: #6c757d;
  font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
  .item-card {
    min-height: auto; /* Allow flexible height on mobile */
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .header-actions {
    justify-content: flex-start;
  }

  .summary-info {
    flex-direction: column;
    gap: 0.75rem;
  }

  .card-footer {
    flex-direction: column;
    gap: 0.5rem;
  }

  .card-footer .p-button {
    width: 100%;
  }

  .group-details {
    grid-template-columns: 1fr;
  }

  .convention-chips {
    order: -1;
  }
}

@media (max-width: 480px) {
  .card-content {
    padding: 0.75rem;
  }
  
  .card-header {
    padding: 0.75rem;
  }
  
  .card-footer {
    padding: 0.75rem;
  }
}

/* Utility Classes */
.mb-4 {
  margin-bottom: 1.5rem;
}
</style>
