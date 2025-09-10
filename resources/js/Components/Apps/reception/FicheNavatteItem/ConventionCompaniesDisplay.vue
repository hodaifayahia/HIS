<!-- filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\resources\js\Components\Apps\reception\FicheNavatteItem\ConventionCompaniesDisplay.vue -->

<template>
  <Card class="convention-organismes-card">
    <template #header>
      <div class="header-section">
        <h4>
          <i class="pi pi-building"></i>
          Convention Organismes Used
        </h4>
        <small class="organismes-count">{{ conventionOrganismes.length }} organisme{{ conventionOrganismes.length !== 1 ? 's' : '' }}</small>
      </div>
    </template>

    <template #content>
      <div v-if="conventionOrganismes.length === 0" class="no-conventions">
        <i class="pi pi-info-circle"></i>
        <p>No convention organismes found for this fiche navette</p>
      </div>

      <div v-else class="organismes-grid">
        <div
          v-for="organisme in conventionOrganismes"
          :key="`organisme-${organisme.organisme_id}`"
          class="organisme-tag-container"
        >
          <Tag
            :value="`${organisme.organisme_name} (${organisme.prestations_count})`"
            severity="info"
            class="organisme-tag"
            @click="showOrganismePrestations(organisme)"
          >
            <template #default>
              <div class="tag-content">
                <i class="pi pi-building"></i>
                <span class="organisme-name">{{ organisme.organisme_name }}</span>
                <Badge :value="organisme.prestations_count" severity="secondary" class="prestations-badge" />
              </div>
            </template>
          </Tag>
          
          <!-- Convention details for this organisme -->
          <div class="conventions-list">
            <small class="conventions-label">Conventions used:</small>
            <div class="convention-chips">
              <Chip
                v-for="convention in organisme.conventions"
                :key="`conv-${convention.id}`"
                :label="`${convention.convention_name} (${convention.prestations_count})`"
                class="convention-chip"
                @click="showConventionPrestations(convention)"
              />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Card>

  <!-- Prestations Modal -->
  <Dialog
    v-model:visible="showPrestationsModal"
    :header="modalTitle"
    modal
    class="prestations-modal"
    :style="{ width: '80vw', maxWidth: '1200px' }"
  >
    <template #header>
      <div class="modal-header">
        <i class="pi pi-list"></i>
        <div>
          <h3>{{ modalTitle }}</h3>
          <small>{{ selectedPrestations.length }} prestation{{ selectedPrestations.length !== 1 ? 's' : '' }} used by this patient</small>
        </div>
      </div>
    </template>

    <DataTable
      :value="selectedPrestations"
      :loading="loadingPrestations"
      responsiveLayout="scroll"
      :paginator="true"
      :rows="10"
      :rowsPerPageOptions="[5, 10, 20]"
      class="prestations-table"
    >
    
      <template #empty>
        <div class="empty-state">
          <i class="pi pi-search"></i>
          <p>No prestations found for this patient</p>
        </div>
      </template>
      
      <Column field="prestation.name" header="Prestation" style="min-width: 250px">
        <template #body="{ data }">
          <div class="prestation-info">
            <strong>{{ data.prestation?.name || data.custom_name || 'Unknown' }}</strong>
            <small class="prestation-code">{{ data.prestation?.internal_code || 'N/A' }}</small>
            <small v-if="data.custom_name && data.custom_name !== data.prestation?.name" class="custom-name-indicator">
              Custom name: {{ data.custom_name }}
            </small>
          </div>
        </template>
      </Column>

      <Column field="prestation.specialization" header="Specialization">
        <template #body="{ data }">
          <Tag
            v-if="data.prestation?.specialization"
            :value="data.prestation.specialization.name"
            severity="secondary"
          />
          <span v-else>-</span>
        </template>
      </Column>

      <Column field="doctor" header="Doctor">
        
        <template #body="{ data }">
          
          <div v-if="data.doctor" class="doctor-info">
            <i class="pi pi-user"></i>
            <span>{{ data.doctor.name }}</span>

          </div>
          <span v-else class="no-doctor">Not assigned</span>
        </template>
      </Column>

      <Column field="convention" header="Convention">
        <template #body="{ data }">
          <div v-if="data.convention" class="convention-info">
            <div class="convention-name">{{ data.convention.name }}</div>
            <small class="organisme-name">{{ getOrganismeName(data.convention) }}</small>
          </div>
          <span v-else>-</span>
        </template>
      </Column>

      <Column field="base_price" header="Base Price">
        <template #body="{ data }">
          <span class="price base-price">{{ formatCurrency(data.base_price) }}</span>
        </template>
      </Column>

      <Column field="final_price" header="Final Price">
        <template #body="{ data }">
          <span class="price final-price">{{ formatCurrency(data.final_price) }}</span>
          <small v-if="data.final_price !== data.base_price" class="price-difference">
            ({{ data.final_price > data.base_price ? '+' : '' }}{{ formatCurrency(data.final_price - data.base_price) }})
          </small>
        </template>
      </Column>

      <Column field="status" header="Status">
        <template #body="{ data }">
          <Tag
            :value="data.status"
            :severity="getStatusSeverity(data.status)"
          />
        </template>
      </Column>

      <Column field="prise_en_charge_date" header="Coverage Date">
        <template #body="{ data }">
          <span v-if="data.prise_en_charge_date">
            {{ formatDate(data.prise_en_charge_date) }}
          </span>
          <span v-else>-</span>
        </template>
      </Column>

      <Column field="created_at" header="Added Date">
        <template #body="{ data }">
          <span>{{ formatDate(data.created_at) }}</span>
        </template>
      </Column>

      <Column field="actions" header="Actions" style="width: 120px">
        <template #body="{ data }">
          <div class="action-buttons">
            <Button
              icon="pi pi-eye"
              class="p-button-text p-button-sm"
              v-tooltip.top="'View details'"
              @click="viewItemDetails(data)"
            />
            <Button
              icon="pi pi-pencil"
              class="p-button-text p-button-sm"
              v-tooltip.top="'Edit item'"
              @click="editItem(data)"
            />
            <Button
              icon="pi pi-history"
              class="p-button-text p-button-sm"
              v-tooltip.top="'View dependencies'"
              @click="viewDependencies(data)"
              v-if="data.dependencies && data.dependencies.length > 0"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <template #footer>
      <div class="modal-footer">
        <div class="summary-info">
          <span class="total-items">Total: {{ selectedPrestations.length }} prestations used</span>
          <span class="total-amount">
            Total Amount: {{ formatCurrency(selectedPrestations.reduce((sum, item) => sum + parseFloat(item.final_price || 0), 0)) }}
          </span>
          <span class="base-amount">
            Base Amount: {{ formatCurrency(selectedPrestations.reduce((sum, item) => sum + parseFloat(item.base_price || 0), 0)) }}
          </span>
        </div>
        <Button
          label="Close"
          icon="pi pi-times"
          @click="showPrestationsModal = false"
          class="p-button-secondary"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Chip from 'primevue/chip'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'

const props = defineProps({
  ficheNavetteId: {
    type: Number,
    required: true
  },
  items: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['view-item', 'edit-item'])

const toast = useToast()

// Reactive data
const conventionOrganismes = ref([])
const showPrestationsModal = ref(false)
const selectedPrestations = ref([])
const loadingPrestations = ref(false)
const modalTitle = ref('')

// Computed properties - only items that have conventions
const conventionItems = computed(() => {
  return props.items.filter(item => 
    item.convention_id && 
    item.convention && 
    item.convention.organisme_id
  )
})

// Process convention organismes data - ONLY for used conventions
const processConventionOrganismes = () => {
  console.log('Processing convention organismes from items:', props.items)
  
  const organismesMap = new Map()
  
  // Only process items that actually have conventions assigned
  conventionItems.value.forEach(item => {
    if (!item.convention || !item.convention.organisme_id) {
      console.log('Skipping item without proper convention:', item)
      return
    }
    
    const convention = item.convention
    const organismeId = convention.organisme_id
    const organismeName = convention.organisme_name || 'Unknown Organisme'
    
    console.log('Processing item for organisme:', organismeName, 'ID:', organismeId)
    
    // Create organisme entry if it doesn't exist
    if (!organismesMap.has(organismeId)) {
      organismesMap.set(organismeId, {
        organisme_id: organismeId,
        organisme_name: organismeName,
        prestations_count: 0,
        conventions: new Map(),
        items: []
      })
    }
    
    const organisme = organismesMap.get(organismeId)
    organisme.prestations_count++
    organisme.items.push(item)
    
    // Track conventions within this organisme - ONLY those that are actually used
    if (!organisme.conventions.has(convention.id)) {
      organisme.conventions.set(convention.id, {
        id: convention.id,
        convention_name: convention.name,
        prestations_count: 0,
        items: []
      })
    }
    
    organisme.conventions.get(convention.id).prestations_count++
    organisme.conventions.get(convention.id).items.push(item)
  })
  
  // Convert Map values to arrays
  conventionOrganismes.value = Array.from(organismesMap.values()).map(organisme => ({
    ...organisme,
    conventions: Array.from(organisme.conventions.values())
  }))
  
  console.log('Processed organismes:', conventionOrganismes.value)
}

// Methods
const showOrganismePrestations = (organisme) => {
  modalTitle.value = `Prestations used by this patient - ${organisme.organisme_name}`
  selectedPrestations.value = [...organisme.items]
  showPrestationsModal.value = true
  
  console.log('Showing prestations for organisme:', organisme.organisme_name, 'Items:', organisme.items)
}

const showConventionPrestations = (convention) => {
  modalTitle.value = `Prestations used by this patient - Convention: ${convention.convention_name}`
  selectedPrestations.value = [...convention.items]
  showPrestationsModal.value = true
  
  console.log('Showing prestations for convention:', convention.convention_name, 'Items:', convention.items)
}

const viewItemDetails = (item) => {
  console.log('Viewing item details:', item)
  emit('view-item', item)
}

const editItem = (item) => {
  console.log('Editing item:', item)
  emit('edit-item', item)
}

const viewDependencies = (item) => {
  if (item.dependencies && item.dependencies.length > 0) {
    toast.add({
      severity: 'info',
      summary: 'Dependencies',
      detail: `This prestation has ${item.dependencies.length} dependencies`,
      life: 3000
    })
  }
}

const getOrganismeName = (convention) => {
  return convention.organisme_name || 'Unknown Organisme'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

const getStatusSeverity = (status) => {
  const statusMap = {
    'pending': 'warning',
    'in_progress': 'info',
    'completed': 'success',
    'cancelled': 'danger'
  }
  return statusMap[status] || 'secondary'
}

// Watchers and lifecycle
onMounted(() => {
  console.log('Component mounted with items:', props.items)
  processConventionOrganismes()
})

// Watch for changes in items and reprocess
watch(() => props.items, (newItems) => {
  console.log('Items changed, reprocessing:', newItems)
  processConventionOrganismes()
}, { deep: true })
</script>

<style scoped>
.convention-organismes-card {
  margin-bottom: 1.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.header-section h4 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1f2937;
}

.organismes-count {
  color: #6b7280;
  font-size: 0.875rem;
}

.no-conventions {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.no-conventions i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  display: block;
}

.organismes-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1rem;
}

.organisme-tag-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.organisme-tag {
  cursor: pointer;
  transition: all 0.2s ease;
  align-self: flex-start;
}

.organisme-tag:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.tag-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.5rem;
}

.organisme-name {
  font-weight: 500;
}

.prestations-badge {
  background: white;
  color: #3b82f6;
  font-size: 0.75rem;
}

.conventions-list {
  margin-left: 1rem;
}

.conventions-label {
  color: #6b7280;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
  display: block;
}

.convention-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.convention-chip {
  cursor: pointer;
  transition: all 0.2s ease;
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #bfdbfe;
}

.convention-chip:hover {
  background: #bfdbfe;
  transform: translateY(-1px);
}

.prestations-modal :deep(.p-dialog-header) {
  background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%);
  border-bottom: 1px solid #d1d5db;
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-header i {
  font-size: 1.5rem;
  color: #3b82f6;
}

.modal-header h3 {
  margin: 0;
  color: #1f2937;
}

.modal-header small {
  color: #6b7280;
}

.prestations-table {
  margin-top: 1rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.empty-state i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  display: block;
}

.prestation-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.prestation-info strong {
  color: #1f2937;
}

.prestation-code {
  color: #6b7280;
  font-size: 0.75rem;
}

.custom-name-indicator {
  color: #8b5cf6;
  font-size: 0.75rem;
  font-style: italic;
}

.doctor-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #374151;
}

.no-doctor {
  color: #9ca3af;
  font-style: italic;
}

.convention-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.convention-name {
  font-weight: 500;
  color: #1f2937;
}

.organisme-name {
  color: #6b7280;
  font-size: 0.75rem;
}

.price {
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
}

.base-price {
  background: #f3f4f6;
  color: #374151;
}

.final-price {
  background: #10b981;
  color: white;
}

.price-difference {
  display: block;
  color: #6b7280;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.summary-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.total-items {
  color: #6b7280;
  font-size: 0.875rem;
}

.total-amount {
  color: #059669;
  font-weight: 600;
  font-size: 1rem;
}

.base-amount {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .prestations-modal {
    width: 95vw !important;
  }
  
  .organismes-grid {
    padding: 0.5rem;
  }
  
  .organisme-tag-container {
    padding: 0.75rem;
  }
  
  .convention-chips {
    flex-direction: column;
  }
  
  .modal-footer {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}
</style>