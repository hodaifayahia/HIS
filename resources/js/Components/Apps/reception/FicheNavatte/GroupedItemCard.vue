<!-- Create: resources/js/Components/Apps/reception/FicheNavatte/GroupedItemCard.vue -->

<template>
  <Card class="grouped-item-card" :class="cardClass">
    <template #header>
      <div class="card-header">
        <div class="header-left">
          <div class="group-type-badge">
            <i :class="typeIcon"></i>
            <span>{{ typeLabel }}</span>
          </div>
          <div class="group-info">
            <h4 class="group-title">{{ groupTitle }}</h4>
            <small class="group-subtitle">{{ groupSubtitle }}</small>
          </div>
        </div>
        <div class="header-right">
          <div class="amount-display">
            <span class="amount">{{ formatCurrency(group.total_amount) }}</span>
            <small class="item-count">{{ group.prestations_count }} item{{ group.prestations_count > 1 ? 's' : '' }}</small>
          </div>
          <Button
            icon="pi pi-trash"
            class="p-button-danger p-button-text p-button-sm"
            @click.prevent="handleRemoveGroup"
            v-tooltip.top="'Remove entire group'"
          />
        </div>
      </div>
    </template>

    <template #content>
      <!-- Main Items -->
      <div class="items-section">
        <h5 class="section-title">
          <i class="pi pi-list"></i>
          Main Items
        </h5>
        <div class="items-grid">
          <div
            v-for="item in group.items"
            :key="item.id"
            class="item-card main-item"
          >
            <div class="item-header">
              <div class="item-name">
                <strong>{{ getItemDisplayName(item) }}</strong>
                <small class="item-code">{{ item.prestation?.internal_code || 'N/A' }}</small>
              </div>
              <div class="item-actions">
                <span class="item-price">{{ formatCurrency(item.final_price) }}</span>
                <Button
                  icon="pi pi-times"
                  class="p-button-danger p-button-text p-button-sm"
                  @click.prevent="handleRemoveItem(item.id)"
                  v-tooltip.top="'Remove main item'"
                />
              </div>
            </div>
            
            <div class="item-details">
              <div class="detail-row" v-if="item.doctor">
                <i class="pi pi-user"></i>
                <span>{{ item.doctor.name }}</span>
              </div>
              <div class="detail-row" v-if="item.status">
                <Tag :value="item.status" :severity="getStatusSeverity(item.status)" size="small" />
              </div>
              <div class="detail-row" v-if="item.prestation?.specialization">
                <i class="pi pi-bookmark"></i>
                <span>{{ item.prestation.specialization.name }}</span>
              </div>
            </div>

            <!-- Dependencies for this item -->
            <div v-if="item.dependencies && item.dependencies.length > 0" class="dependencies-section">
              <h6 class="dependencies-title">
                <i class="pi pi-link"></i>
                Related Items ({{ item.dependencies.length }})
              </h6>
              <div class="dependencies-list">
                <div
                  v-for="dependency in item.dependencies"
                  :key="`dep-${dependency.id}`"
                  class="dependency-item"
                >
                  <div class="dependency-info">
                    <div class="dependency-name-section">
                      <span class="dependency-name">
                        {{ getDependencyDisplayName(dependency) }}
                      </span>
                      <small class="dependency-code" v-if="getDependencyCode(dependency)">
                        Code: {{ getDependencyCode(dependency) }}
                      </small>
                      <!-- Show indicator if it's using custom name -->
                      <Tag 
                        v-if="dependency.custom_name && dependency.custom_name.trim() !== ''" 
                        value="Custom Name" 
                        severity="info" 
                        size="small" 
                        class="custom-name-tag"
                      />
                    </div>
                    <div class="dependency-meta">
                      <small class="dependency-type">{{ getDependencyType(dependency) }}</small>
                      <small v-if="dependency.dependencyPrestation?.specialization" class="dependency-specialization">
                        {{ dependency.dependencyPrestation.specialization.name }}
                      </small>
                    </div>
                  </div>
                  <div class="dependency-actions">
                    <span class="dependency-price">{{ formatCurrency(dependency.final_price) }}</span>
                    <Button
                      icon="pi pi-times"
                      class="p-button-danger p-button-text p-button-sm dependency-remove-btn"
                      @click.prevent="handleRemoveDependency(dependency.id)"
                      v-tooltip.top="'Remove this dependency'"
                      title="Remove dependency"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Convention Details if applicable -->
      <div v-if="group.type === 'convention' && group.convention" class="convention-section">
        <h5 class="section-title">
          <i class="pi pi-building"></i>
          Convention Details
        </h5>
        <div class="convention-details">
          <div class="detail-item">
            <label>Convention:</label>
            <span>{{ group.convention.contract_name }}</span>
          </div>
          <div class="detail-item">
            <label>Company:</label>
            <span>{{ group.convention.company_name }}</span>
          </div>
          <div class="detail-item" v-if="group.prise_en_charge_date">
            <label>Coverage Date:</label>
            <span>{{ formatDate(group.prise_en_charge_date) }}</span>
          </div>
          <div class="detail-item" v-if="group.family_authorization">
            <label>Family Authorization:</label>
            <span>{{ group.family_authorization }}</span>
          </div>
        </div>
      </div>

      <!-- Insured Patient Info -->
      <div v-if="group.insured_patient" class="patient-section">
        <h5 class="section-title">
          <i class="pi pi-user"></i>
          {{ group.type === 'convention' ? 'Insured Patient' : 'Patient' }}
        </h5>
        <div class="patient-info">
          <Avatar icon="pi pi-user" class="patient-avatar" />
          <div class="patient-details">
            <span class="patient-name">
              {{ group.insured_patient.first_name }} {{ group.insured_patient.last_name }}
            </span>
            <small class="patient-contact">{{ group.insured_patient.phone }}</small>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'

const props = defineProps({
  group: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['remove-group', 'remove-item', 'remove-dependency'])

// Enhanced method to get item display name
const getItemDisplayName = (item) => {
  if (item.custom_name && item.custom_name.trim() !== '') {
    return item.custom_name
  }
  if (item.prestation?.name) {
    return item.prestation.name
  }
  return 'Unknown Item'
}

// Enhanced method to handle dependency name display with better fallback
const getDependencyDisplayName = (dependency) => {
  // Debug logging
  console.log('Dependency object:', dependency)
  
  // Priority: custom_name first, then prestation name, then fallback
  if (dependency.custom_name && dependency.custom_name.trim() !== '') {
    return dependency.custom_name
  }
  
  if (dependency.dependencyPrestation?.name) {
    return dependency.dependencyPrestation.name
  }
  
  if (dependency.display_name && dependency.display_name.trim() !== '') {
    return dependency.display_name
  }
  
  // Last resort: try to get name from the prestation ID
  if (dependency.dependent_prestation_id) {
    return `Prestation ID: ${dependency.dependent_prestation_id}`
  }
  
  return 'Unknown Dependency'
}

// Get dependency code
const getDependencyCode = (dependency) => {
  if (dependency.dependencyPrestation?.internal_code) {
    return dependency.dependencyPrestation.internal_code
  }
  return null
}

// Get dependency type with better formatting
const getDependencyType = (dependency) => {
  const type = dependency.dependency_type || 'related'
  return type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')
}

// Handle remove group with prevent default
const handleRemoveGroup = (event) => {
  event.preventDefault()
  event.stopPropagation()
  emit('remove-group', props.group)
}

// Handle remove item with prevent default
const handleRemoveItem = (itemId) => {
  emit('remove-item', itemId)
}

// Handle remove dependency with prevent default
const handleRemoveDependency = (dependencyId) => {
  emit('remove-dependency', dependencyId)
}

// Keep all existing computed properties unchanged
const cardClass = computed(() => {
  return props.group.type === 'convention' ? 'convention-card' : 'regular-card'
})

const typeIcon = computed(() => {
  return props.group.type === 'convention' ? 'pi pi-building' : 'pi pi-user'
})

const typeLabel = computed(() => {
  return props.group.type === 'convention' ? 'Convention' : 'Regular'
})

const groupTitle = computed(() => {
  if (props.group.type === 'convention') {
    return props.group.convention?.contract_name || 'Convention Group'
  }
  return 'Regular Group'
})

const groupSubtitle = computed(() => {
  if (props.group.type === 'convention') {
    return props.group.convention?.company_name || ''
  }
  return 'Standard pricing'
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR')
}

const getStatusSeverity = (status) => {
  const statusMap = {
    'pending': 'warning',
    'in_progress': 'info',
    'completed': 'success',
    'cancelled': 'danger',
    'dependency': 'secondary'
  }
  return statusMap[status] || 'secondary'
}
</script>

<style scoped>
/* Keep all existing styles and add these new ones */

.dependency-name-section {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.dependency-meta {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  margin-top: 0.25rem;
}

.dependency-type {
  color: #d97706;
  font-size: 0.65rem;
  font-weight: 500;
  text-transform: uppercase;
}

.dependency-specialization {
  color: #6366f1;
  font-size: 0.65rem;
}

.dependency-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.dependency-remove-btn {
  min-width: auto !important;
  width: 1.5rem !important;
  height: 1.5rem !important;
}

.custom-name-tag {
  margin-top: 0.25rem;
  align-self: flex-start;
}

.dependency-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  align-items: flex-start;
  flex: 1;
}

.dependency-name {
  font-weight: 500;
  color: #92400e;
  font-size: 0.875rem;
  word-break: break-word;
  line-height: 1.2;
}

.dependency-code {
  color: #d97706;
  font-size: 0.75rem;
}

.dependency-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 0.75rem;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 6px;
  gap: 0.75rem;
}

.dependency-price {
  background: #f59e0b;
  color: white;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

/* Enhanced responsive design */
@media (max-width: 768px) {
  .dependency-item {
    flex-direction: column;
    gap: 0.5rem;
    align-items: stretch;
  }
  
  .dependency-actions {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

/* Keep all other existing styles */
.grouped-item-card {
  margin-bottom: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.grouped-item-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.convention-card {
  border-left: 4px solid #10b981;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.regular-card {
  border-left: 4px solid #3b82f6;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.group-type-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  font-size: 0.875rem;
  font-weight: 500;
}

.group-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.group-title {
  margin: 0;
  color: #1f2937;
  font-size: 1.125rem;
}

.group-subtitle {
  color: #6b7280;
  font-size: 0.875rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.amount-display {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: #059669;
}

.item-count {
  color: #6b7280;
  font-size: 0.75rem;
}

.items-section {
  margin-bottom: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem 0;
  color: #374151;
  font-size: 1rem;
  font-weight: 600;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.items-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.item-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
}

.main-item {
  border-left: 3px solid #3b82f6;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.item-name {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-name strong {
  color: #1f2937;
  font-size: 1rem;
}

.item-code {
  color: #6b7280;
  font-size: 0.75rem;
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.item-price {
  background: #10b981;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 600;
}

.item-details {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.dependencies-section {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f3f4f6;
}

.dependencies-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.75rem 0;
  color: #f59e0b;
  font-size: 0.875rem;
  font-weight: 600;
}

.dependencies-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.convention-section,
.patient-section {
  margin-bottom: 1rem;
}

.convention-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
  text-transform: uppercase;
}

.detail-item span {
  color: #1f2937;
  font-weight: 500;
}

.patient-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.patient-avatar {
  background: #6b7280;
}

.patient-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.patient-name {
  font-weight: 600;
  color: #1f2937;
}

.patient-contact {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-left,
  .header-right {
    justify-content: space-between;
  }
  
  .item-header {
    flex-direction: column;
    gap: 0.75rem;
    align-items: stretch;
  }
  
  .item-actions {
    justify-content: space-between;
  }
  
  .convention-details {
    grid-template-columns: 1fr;
  }
}
</style>