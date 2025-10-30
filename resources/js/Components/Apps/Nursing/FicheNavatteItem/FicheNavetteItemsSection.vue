<!-- components/Reception/FicheNavette/FicheNavetteItemsSection.vue -->
<script setup >
import Card from 'primevue/card'
import Button from 'primevue/button'
import Chip from 'primevue/chip'
import Badge from 'primevue/badge'
import ConfirmDialog from 'primevue/confirmdialog'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { ref, Transition } from 'vue'
import axios from 'axios'

// Components
import FicheNavetteItemCreate from './FicheNavetteItemCreate.vue'
import NursingProductSelection from '../components/NursingProductSelection.vue'
import PrestationItemCard from '../FicheNavatte/PrestationItemCard.vue'
import EmptyState from '../../../Common/EmptyState.vue'
import { ficheNavetteService } from '../../../Apps/services/Emergency/ficheNavetteService'


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
const toast = useToast()

// Reactive data for nursing functionality
const showNursingSelection = ref(false)
const selectedNursingItems = ref([])
const hasSelectedNursingItems = ref(false)
const creatingNursingItems = ref(false)
const nursingProductSelectionRef = ref(null)

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

// Nursing functionality methods
const toggleNursingSelection = () => {
  showNursingSelection.value = !showNursingSelection.value
  if (!showNursingSelection.value) {
    // Clear selections when closing
    selectedNursingItems.value = []
    hasSelectedNursingItems.value = false
    
    // Clear selections in the child component
    if (nursingProductSelectionRef.value) {
      nursingProductSelectionRef.value.clearSelection()
    }
  }
}

const handleNursingItemsCreation = async () => {
  if (selectedNursingItems.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select at least one nursing item',
      life: 3000
    })
    return
  }

  creatingNursingItems.value = true
  const itemCount = selectedNursingItems.value.length
  
  try {
    // Prepare data for patient consumption creation
    const consumptionData = selectedNursingItems.value.map(item => ({
      product_id: item.id,
      fiche_id: props.fiche.id,
      patient_id: props.fiche.patient_id,
      quantity: item.quantity,
      administered_at: new Date().toISOString(),
      notes: `${item.source === 'pharmacy' ? 'Pharmacy' : 'Stock'} product: ${item.name}`
    }))

    // Send to backend
    const response = await axios.post('/api/patient-consumptions', {
      consumptions: consumptionData
    })

    // Check if response has data (successful creation)
    if (response.data && (response.data.data || response.status === 200 || response.status === 201)) {
      // Clear selections in both parent and child components
      selectedNursingItems.value = []
      hasSelectedNursingItems.value = false
      
      // Clear selections in the child component
      if (nursingProductSelectionRef.value) {
        nursingProductSelectionRef.value.clearSelection()
      }
      
      // Show success toast
      toast.add({
        severity: 'success',
        summary: 'Nursing Items Added Successfully',
        detail: `${itemCount} nursing item${itemCount > 1 ? 's' : ''} have been added to patient consumption records`,
        life: 4000
      })
      
      // Emit to refresh the parent data
      emit('items-added')
    } else {
      throw new Error(response.data?.message || 'Failed to create nursing items')
    }
  } catch (error) {
    console.error('Error creating nursing items:', error)
    
    // Show error toast
    toast.add({
      severity: 'error',
      summary: 'Error Adding Nursing Items',
      detail: error.response?.data?.message || error.message || 'Failed to add nursing items. Please try again.',
      life: 5000
    })
  } finally {
    creatingNursingItems.value = false
  }
}
</script>
<template>
  <div class="tw-flex tw-flex-col tw-gap-6 tw-mb-8">
    <Card v-if="showCreateForm" class="tw-border-2 tw-border-dashed tw-border-blue-400 tw-bg-blue-50 tw-rounded-2xl tw-overflow-hidden tw-shadow-md">
      <template #header>
        <div class="tw-bg-blue-100 tw-p-6">
          <div class="tw-flex tw-items-center tw-justify-between tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-bg-blue-600 tw-text-white tw-rounded-lg tw-shadow-md tw-flex-shrink-0">
                <i class="pi pi-plus tw-text-xl"></i>
              </div>
              <div>
                <h4 class="tw-text-xl tw-font-bold tw-text-blue-800 tw-mb-0.5">Add New Items</h4>
                <p class="tw-text-sm tw-text-blue-600">Select medical procedures and nursing items</p>
              </div>
            </div>
            <Button
              icon="pi pi-times"
              class="p-button-text p-button-rounded tw-text-blue-600 hover:tw-bg-blue-200"
              @click="$emit('toggle-create-form')"
              v-tooltip.left="'Close'"
            />
          </div>
        </div>
      </template>
      <template #content>
        <div class="tw-p-0">
          <!-- Enhanced Tab Navigation with Modern Design -->
          <TabView class="enhanced-tab-view">
            <!-- Medical Procedures Tab - For adding medical prestations and packages -->
            <TabPanel>
              <template #header>
                <!-- Custom Tab Header with Icon and Badge -->
                <div class="tw-flex tw-items-center tw-gap-3 tw-py-2 tw-px-4">
                  <div class="tw-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-bg-red-100 tw-rounded-lg tw-transition-all tw-duration-300">
                    <i class="pi pi-heart tw-text-red-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-flex tw-flex-col">
                    <span class="tw-font-semibold tw-text-gray-900 tw-text-base">Medical Procedures</span>
                    <span class="tw-text-xs tw-text-gray-500 tw-mt-0.5">Prestations & Packages</span>
                  </div>
                </div>
              </template>
              
              <!-- Medical Procedures Content Area -->
              <div class="tw-p-8 tw-bg-gradient-to-br tw-from-red-50 tw-to-pink-50 tw-min-h-96">
                <!-- Section Header with Description -->
                

                <!-- Medical Procedures Component Container -->
                <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-border tw-border-gray-200 tw-overflow-hidden">
                  <FicheNavetteItemCreate
                    :patient-id="fiche.patient_id"
                    :fiche-navette-id="fiche.id"
                    @created="$emit('items-added')"
                  />
                </div>
              </div>
            </TabPanel>
            
            <!-- Nursing Tab - For adding nursing products and consumables -->
            <TabPanel>
              <template #header>
                <!-- Custom Tab Header with Icon and Badge -->
                <div class="tw-flex tw-items-center tw-gap-3 tw-py-2 tw-px-4">
                  <div class="tw-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-bg-green-100 tw-rounded-lg tw-transition-all tw-duration-300">
                    <i class="pi pi-plus-circle tw-text-green-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-flex tw-flex-col">
                    <span class="tw-font-semibold tw-text-gray-900 tw-text-base">Nursing Care</span>
                    <span class="tw-text-xs tw-text-gray-500 tw-mt-0.5">Products & Medications</span>
                  </div>
                  <!-- Selection Badge - Shows when items are selected -->
                  <Badge 
                    v-if="selectedNursingItems.length > 0"
                    :value="selectedNursingItems.length"
                    severity="success"
                    class="tw-ml-2"
                  />
                </div>
              </template>
              
              <!-- Nursing Content Area -->
              <div class="tw-p-8 tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-min-h-96">
                

                <!-- Nursing Product Selection Container -->
                <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-border tw-border-gray-200 tw-overflow-hidden tw-mb-6">
                  <NursingProductSelection 
                    ref="nursingProductSelectionRef"
                    :patient-id="fiche.patient_id"
                    :fiche-navette-id="fiche.id"
                    @update:hasSelectedItems="hasSelectedNursingItems = $event"
                    @update:selectedNursingItems="selectedNursingItems = $event"
                  />
                </div>
                
                <!-- Enhanced Action Panel - Shows when items are selected -->
                <Transition
                  enter-active-class="tw-transition-all tw-duration-500 tw-ease-out"
                  enter-from-class="tw-opacity-0 tw-transform tw-scale-95 tw-translate-y-4"
                  enter-to-class="tw-opacity-100 tw-transform tw-scale-100 tw-translate-y-0"
                  leave-active-class="tw-transition-all tw-duration-300 tw-ease-in"
                  leave-from-class="tw-opacity-100 tw-transform tw-scale-100 tw-translate-y-0"
                  leave-to-class="tw-opacity-0 tw-transform tw-scale-95 tw-translate-y-4"
                >
                  <div v-if="hasSelectedNursingItems" class="nursing-action-panel">
                    <!-- Background with Gradient and Pattern -->
                    <div class="tw-bg-gradient-to-r tw-from-green-500 tw-via-emerald-500 tw-to-teal-500 tw-rounded-2xl tw-p-6 tw-shadow-xl tw-border tw-border-green-200 tw-relative tw-overflow-hidden">
                      <!-- Decorative Pattern -->
                      <div class="tw-absolute tw-inset-0 tw-bg-white tw-bg-opacity-10" style="background-image: radial-gradient(circle at 25% 25%, white 2px, transparent 2px); background-size: 24px 24px;"></div>
                      
                      <!-- Content Container -->
                      <div class="tw-relative tw-flex tw-flex-col sm:tw-flex-row tw-items-start sm:tw-items-center tw-gap-6">
                        <!-- Status Information -->
                        <div class="tw-flex tw-items-center tw-gap-4 tw-flex-1">
                          <!-- Animated Success Icon -->
                          <div class="tw-relative">
                            <div class="tw-w-14 tw-h-14 tw-bg-white tw-bg-opacity-20 tw-backdrop-blur-sm tw-rounded-full tw-flex tw-items-center tw-justify-center tw-border-2 tw-border-white tw-border-opacity-30">
                              <i class="pi pi-check-circle tw-text-white tw-text-2xl"></i>
                            </div>
                            <!-- Pulse Animation Ring -->
                            <div class="tw-absolute tw-inset-0 tw-rounded-full tw-border-2 tw-border-white tw-border-opacity-50 tw-animate-ping"></div>
                          </div>
                          
                          <!-- Selection Details -->
                          <div class="tw-text-white">
                            <h4 class="tw-text-lg tw-font-bold tw-mb-1">
                              {{ selectedNursingItems.length }} Product{{ selectedNursingItems.length !== 1 ? 's' : '' }} Selected
                            </h4>
                            <p class="tw-text-green-100 tw-text-sm tw-font-medium">
                              Total Quantity: {{ selectedNursingItems.reduce((total, item) => total + (item.quantity || 0), 0) }} units
                            </p>
                            <div class="tw-flex tw-flex-wrap tw-gap-2 tw-mt-2">
                              <Chip 
                                v-for="item in selectedNursingItems.slice(0, 3)" 
                                :key="item.id"
                                :label="`${item.name} (${item.quantity})`"
                                class="tw-bg-white tw-bg-opacity-20 tw-text-white tw-text-xs tw-border-0"
                              />
                              <Chip 
                                v-if="selectedNursingItems.length > 3"
                                :label="`+${selectedNursingItems.length - 3} more`"
                                class="tw-bg-white tw-bg-opacity-20 tw-text-white tw-text-xs tw-border-0"
                              />
                            </div>
                          </div>
                        </div>

                        <!-- Action Button -->
                        <div class="tw-flex tw-flex-col tw-items-center tw-gap-3">
                          <Button
                            label="Add to Patient Care"
                            icon="pi pi-plus"
                            @click="handleNursingItemsCreation"
                            :loading="creatingNursingItems"
                            class="nursing-add-button tw-bg-white tw-text-green-600 tw-border-0 tw-px-6 tw-py-3 tw-font-semibold tw-rounded-xl tw-shadow-lg hover:tw-bg-gray-50 tw-transition-all tw-duration-300"
                            :pt="{
                              root: { class: 'hover:scale-105 transform transition-all duration-300' },
                              label: { class: 'font-semibold' }
                            }"
                          />
                          <!-- Loading State Indicator -->
                          <div v-if="creatingNursingItems" class="tw-text-white tw-text-xs tw-flex tw-items-center tw-gap-2">
                            <i class="pi pi-spin pi-spinner"></i>
                            Processing nursing items...
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </Transition>
              </div>
            </TabPanel>
          </TabView>
        </div>
      </template>
    </Card>

    <Card class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-200">
      <template #header>
        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-start sm:tw-items-center tw-justify-between tw-p-6 tw-bg-gray-50 tw-border-b tw-border-gray-200">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-bg-blue-100 tw-text-blue-600 tw-rounded-full tw-flex-shrink-0">
              <i class="pi pi-list tw-text-xl"></i>
            </div>
            <div>
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-900 tw-mb-0.5">Items & Services</h3>
              <p class="tw-text-sm tw-text-gray-500">Manage prestations and packages for this fiche</p>
            </div>
          </div>
          <div class="tw-flex tw-gap-2 tw-mt-4 sm:tw-mt-0 tw-flex-wrap">
            <Chip 
              :label="`${groupedItems.length} item${groupedItems.length !== 1 ? 's' : ''}`"
              severity="info"
              class="!tw-bg-blue-100 !tw-text-blue-800 tw-font-medium"
            />
          </div>
        </div>
      </template>
      
      <template #content>
        <div class="tw-p-6">
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

          <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <PrestationItemCard
              v-for="group in groupedItems"
              :key="`${group.type}_${group.id}`"
              :group="group"
              :patient-id="fiche.patient_id"
              :prestations="prestations"
              :packages="packages"
              :doctors="doctors"
              :fiche-navette-id="fiche.id"
              @remove-item="confirmRemoveItem"
              @item-updated="$emit('items-added')"
              @remove-group="confirmRemoveGroup"
              @dependency-removed="$emit('items-added')"
              @apply-remise="$emit('remise-applied')"
            />
          </div>
        </div>

        <div v-if="itemsCount > 0" class="tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-justify-between tw-p-6 tw-bg-gray-100 tw-border-t tw-border-gray-200">
          <div class="tw-flex-1">
            <Button
              icon="pi pi-plus"
              label="Add Items"
              class="p-button-outlined tw-w-full sm:tw-w-auto"
              @click="$emit('toggle-create-form')"
            />
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1">Medical procedures and nursing products</p>
          </div>
        </div>
      </template>
    </Card>

    <ConfirmDialog />
  </div>
</template>

<style scoped>
/* Enhanced Tab Navigation Styling */
:deep(.enhanced-tab-view) {
  background: transparent;
  border: none;
}

:deep(.enhanced-tab-view .p-tabview-nav) {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border: none;
  border-radius: 1.5rem 1.5rem 0 0;
  padding: 0.75rem;
  margin: 0;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

:deep(.enhanced-tab-view .p-tabview-nav-link) {
  border: none;
  border-radius: 1rem;
  margin: 0 0.5rem;
  padding: 0;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  background: transparent;
  box-shadow: none;
}

:deep(.enhanced-tab-view .p-tabview-nav-link:hover) {
  background: rgba(255, 255, 255, 0.9);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

:deep(.enhanced-tab-view .p-tabview-nav-link:focus) {
  box-shadow: 0 0 0 3px #3b82f6, 0 8px 25px rgba(0, 0, 0, 0.15);
  outline: none;
}

:deep(.enhanced-tab-view .p-highlight .p-tabview-nav-link) {
  background: white;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  border: 2px solid #e5e7eb;
  transform: translateY(-3px);
}

/* Medical Procedures Tab Icon Animation */
:deep(.enhanced-tab-view .p-highlight .p-tabview-nav-link:has(i.pi-heart)) {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-color: #fca5a5;
}

:deep(.enhanced-tab-view .p-highlight .p-tabview-nav-link:has(i.pi-heart) .tw-bg-red-100) {
  background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%) !important;
  box-shadow: 0 4px 16px rgba(220, 38, 38, 0.3);
}

/* Nursing Tab Icon Animation */
:deep(.enhanced-tab-view .p-highlight .p-tabview-nav-link:has(i.pi-plus-circle)) {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-color: #86efac;
}

:deep(.enhanced-tab-view .p-highlight .p-tabview-nav-link:has(i.pi-plus-circle) .tw-bg-green-100) {
  background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important;
  box-shadow: 0 4px 16px rgba(22, 163, 74, 0.3);
}

:deep(.enhanced-tab-view .p-tabview-panels) {
  background: transparent;
  border: none;
  padding: 0;
}

/* Nursing Action Panel Enhancements */
.nursing-action-panel {
  animation: slideInScale 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideInScale {
  0% {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
  }
  50% {
    opacity: 0.7;
    transform: scale(0.95) translateY(10px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Button Enhancement for Nursing Actions */
:deep(.nursing-add-button) {
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.nursing-add-button::before) {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.6s ease;
}

:deep(.nursing-add-button:hover::before) {
  left: 100%;
}

:deep(.nursing-add-button:active) {
  transform: scale(0.98);
}

/* Icon Pulse Animation for Status Indicators */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.tw-animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Tab Content Area Animations */
.tw-bg-gradient-to-br {
  position: relative;
  overflow: hidden;
}

.tw-bg-gradient-to-br::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
  pointer-events: none;
}

/* Card Shadow Enhancements */
.tw-shadow-sm {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 1px 4px rgba(0, 0, 0, 0.04);
}

.tw-shadow-lg {
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1), 0 4px 16px rgba(0, 0, 0, 0.06);
}

.tw-shadow-xl {
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.12), 0 8px 32px rgba(0, 0, 0, 0.08);
}

/* Responsive Enhancements */
@media (max-width: 1024px) {
  :deep(.enhanced-tab-view .p-tabview-nav-link) {
    margin: 0 0.25rem;
  }
  
  .nursing-action-panel .tw-flex-col {
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  :deep(.enhanced-tab-view .p-tabview-nav) {
    padding: 0.5rem;
    border-radius: 1rem 1rem 0 0;
  }
  
  :deep(.enhanced-tab-view .p-tabview-nav-link) {
    margin: 0 0.125rem;
  }
  
  .nursing-action-panel .tw-text-lg {
    font-size: 1rem;
  }
  
  .nursing-action-panel .tw-text-2xl {
    font-size: 1.25rem;
  }
}

/* Loading State Enhancements */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.pi-spinner {
  animation: spin 1s linear infinite;
}

/* Hover Effects for Interactive Elements */
.tw-transition-all:hover {
  transition-duration: 0.3s;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Focus States for Accessibility */
:deep(*:focus) {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

:deep(.p-button:focus) {
  box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #3b82f6;
}
</style>