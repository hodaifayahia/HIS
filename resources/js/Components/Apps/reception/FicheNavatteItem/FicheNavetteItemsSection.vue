<!-- components/Reception/FicheNavette/FicheNavetteItemsSection.vue -->
<script setup lang="ts">
import Card from 'primevue/card'
import Button from 'primevue/button'
import Chip from 'primevue/chip'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

// Components
import FicheNavetteItemCreate from './FicheNavetteItemCreate.vue'
import PrestationItemCard from '../FicheNavatte/PrestationItemCard.vue'
import EmptyState from '../../../Common/EmptyState.vue'
import { ficheNavetteService } from '../../../Apps/services/Reception/ficheNavetteService'


const props = defineProps({
  fiche: {
    type: Object,
    required: true
  },
  items: {
    type: Array,
    required: true
  },
  groupedItems: {
    type: Array,
    required: true
  },
  prestations: {
    type: Array,
    required: true
  },
  packages: {
    type: Array,
    required: true
  },
  doctors: {
    type: Array,
    required: true
  },
  showCreateForm: {
    type: Boolean,
    required: true
  },
  totalAmount: {
    type: Number,
    required: true
  },
  itemsCount: {
    type: Number,
    required: true
  }
})

const emit = defineEmits([
  'items-added',
  'item-removed',
  'remise-applied',
  'toggle-create-form'
])

const confirm = useConfirm()

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}
const confirmRemoveGroup = (items) => {
  confirm.require({
    message: 'Are you sure you want to remove this group and all its prestations and dependencies? This action cannot be undone.',
    header: 'Remove Group',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => handleRemoveGroup(items)
  })
}
const handleRemoveGroup = async (items) => {
  // items is an array of all prestations in the group
  for (const item of items) {
    await ficheNavetteService.removeFicheNavetteItem(props.fiche.id, item.id)
    // Optionally: remove dependencies if not handled by backend
  }
  emit('items-added')
}
const handleRemoveItem = async (item) => {
  await ficheNavetteService.removeFicheNavetteItem(props.fiche.id, item)
  emit('item-removed', item)
}
const confirmRemoveItem = (itemId) => {
  confirm.require({
    message: 'Are you sure you want to remove this item? This action cannot be undone.',
    header: 'Remove Item',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => handleRemoveItem(itemId)
  })
}
</script>

<template>
  <div class="items-section">
    <!-- Add Items Form -->
    <Card v-if="showCreateForm" class="create-form-card">
      <template #header>
        <div class="create-form-header">
          <div class="header-content">
            <div class="header-left">
              <div class="header-icon">
                <i class="pi pi-plus"></i>
              </div>
              <div class="header-text">
                <h4 class="header-title">Add New Items</h4>
                <p class="header-subtitle">Select prestations or packages to add to this fiche</p>
              </div>
            </div>
            <Button
              icon="pi pi-times"
              class="p-button-text p-button-rounded"
              @click="$emit('toggle-create-form')"
              v-tooltip.left="'Close'"
            />
          </div>
        </div>
      </template>
      <template #content>
        <FicheNavetteItemCreate
          :patient-id="fiche.patient_id"
          :fiche-navette-id="fiche.id"
          @created="$emit('items-added')"
        />
      </template>
    </Card>

    <!-- Items Display -->
    <Card class="items-display-card">
      <template #header>
        <div class="section-header">
          <div class="header-left">
            <div class="header-icon-section">
              <i class="pi pi-list header-main-icon"></i>
            </div>
            <div class="header-text-section">
              <h3 class="section-title">Items & Services</h3>
              <p class="section-subtitle">Manage prestations and packages for this fiche</p>
            </div>
          </div>
          <div class="header-right">
            <div class="summary-chips">
              <Chip 
                :label="`${groupedItems.length} item${groupedItems.length !== 1 ? 's' : ''}`"
                severity="info"
                class="summary-chip"
              />
             
              <Chip 
                :label="formatCurrency(totalAmount)"
                severity="success"
                class="summary-chip total-chip"
              />
            </div>
          </div>
        </div>
      </template>
      
      <template #content>
        <!-- Empty State -->
        <EmptyState
          v-if="itemsCount === 0"
          icon="pi pi-inbox"
          title="No Items Added Yet"
          description="Start by adding prestations or packages to this fiche navette"
          :actions="[
            {
              label: 'Add First Item',
              icon: 'pi pi-plus',
              class: 'p-button-primary p-button-lg',
              action: () => $emit('toggle-create-form')
            }
          ]"
        />

        <!-- Items Grid -->
        <div v-else class="items-grid">
          <PrestationItemCard
            v-for="group in groupedItems"
            :key="`${group.type}_${group.id}`"
            :group="group"
            :patient-id="fiche.patient_id"
            :prestations="prestations"
            :packages="packages"
            :doctors="doctors"
            @remove-item="confirmRemoveItem"
            @item-updated="$emit('items-added')"
            @remove-group="confirmRemoveGroup"
            @dependency-removed="$emit('items-added')"
            @apply-remise="$emit('remise-applied')"
          />
        </div>

        <!-- Quick Actions Bar (when items exist) -->
        <div v-if="itemsCount > 0" class="quick-actions-bar">
          <div class="actions-left">
            <Button
              icon="pi pi-plus"
              label="Add More Items"
              class="p-button-outlined"
              @click="$emit('toggle-create-form')"
            />
          </div>
          <div class="actions-right">
            <div class="total-summary">
              <span class="total-label">Total:</span>
              <span class="total-amount">{{ formatCurrency(totalAmount) }}</span>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <ConfirmDialog />
  </div>
</template>

<style scoped>
.items-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Create Form Card */
.create-form-card {
  border: 2px dashed var(--primary-300);
  background: linear-gradient(135deg, var(--primary-25) 0%, var(--primary-50) 100%);
  border-radius: 16px;
  overflow: hidden;
}

.create-form-header {
  background: linear-gradient(135deg, var(--primary-100) 0%, var(--primary-50) 100%);
  padding: 1.5rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon {
  background: var(--primary-color);
  color: white;
  width: 3rem;
  height: 3rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.3);
}

.header-text {
  flex: 1;
}

.header-title {
  margin: 0 0 0.25rem 0;
  color: var(--primary-color);
  font-size: 1.25rem;
  font-weight: 600;
}

.header-subtitle {
  margin: 0;
  color: var(--text-color-secondary);
  font-size: 0.9rem;
}

/* Items Display Card */
.items-display-card {
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  background: linear-gradient(135deg, var(--surface-50) 0%, white 100%);
  border-bottom: 1px solid var(--surface-200);
}

.header-icon-section {
  margin-right: 1rem;
}

.header-main-icon {
  color: var(--primary-color);
  font-size: 2rem;
}

.header-text-section {
  flex: 1;
}

.section-title {
  margin: 0 0 0.25rem 0;
  color: var(--text-color);
  font-size: 1.5rem;
  font-weight: 700;
}

.section-subtitle {
  margin: 0;
  color: var(--text-color-secondary);
  font-size: 1rem;
}

.header-right {
  display: flex;
  align-items: center;
}

.summary-chips {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.summary-chip {
  font-weight: 600;
}

.total-chip {
  font-size: 1rem;
  padding: 0.5rem 1rem;
}

/* Items Grid */
.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  padding: 2rem;
}

/* Quick Actions Bar */
.quick-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  background: var(--surface-50);
  border-top: 1px solid var(--surface-200);
  margin-top: auto;
}

.actions-left {
  display: flex;
  gap: 0.75rem;
}

.actions-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.total-summary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: var(--primary-50);
  border: 1px solid var(--primary-200);
  border-radius: 12px;
}

.total-label {
  font-weight: 600;
  color: var(--text-color-secondary);
}

.total-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  }
}

@media (max-width: 768px) {
  .section-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
    padding: 1.5rem;
  }
  
  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .summary-chips {
    justify-content: center;
  }
  
  .items-grid {
    grid-template-columns: 1fr;
    padding: 1.5rem;
  }
  
  .quick-actions-bar {
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
  }
  
  .actions-left,
  .actions-right {
    width: 100%;
    justify-content: center;
  }
  
  .create-form-header {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}

@media (max-width: 480px) {
  .section-title {
    font-size: 1.25rem;
  }
  
  .header-main-icon {
    font-size: 1.5rem;
  }
  
  .summary-chips {
    flex-direction: column;
    align-items: stretch;
  }
  
  .summary-chip {
    text-align: center;
  }
}
</style>
