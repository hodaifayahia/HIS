<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Button from 'primevue/button'

import Badge from 'primevue/badge'
import ProgressBar from 'primevue/progressbar'

// New child components
import PrestationSelection from '../components/PrestationSelection.vue'

// Services
import { ficheNavetteService } from '../../../../Components/Apps/services/Emergency/ficheNavetteService.js'

const emit = defineEmits(['cancel', 'created'])
const props = defineProps({
  patientId: {
    type: Number,
    required: true
  },
  ficheNavetteId: {
    type: Number,
    required: false
  }
})

const toast = useToast()

// Global Reactive State
const activeTab = ref(0)
const loading = ref(false)
const creating = ref(false)
const hasSelectedItems = ref(false)

// Simplified State - Only what's needed
const selectedDoctor = ref(null)
const selectedSpecialization = ref(null)

// Common Data (Loaded once on mount)
const allDoctors = ref([])
const specializations = ref([])
const allPrestations = ref([])

// Progress tracking
const loadingProgress = ref(0)
const loadingSteps = ref([
  { name: 'Specializations', completed: false },
  { name: 'Doctors', completed: false },
  { name: 'Prestations', completed: false }
])

// METHODS
// --- Data Fetching ---
const fetchInitialData = async () => {
  loading.value = true
  loadingProgress.value = 0
  
  try {
    const steps = [
      { fn: fetchSpecializations, name: 'Specializations' },
      { fn: fetchAllDoctors, name: 'Doctors' },
      { fn: fetchAllPrestations, name: 'Prestations' }
    ]
    
    for (let i = 0; i < steps.length; i++) {
      const step = steps[i]
      await step.fn()
      loadingSteps.value[i].completed = true
      loadingProgress.value = ((i + 1) / steps.length) * 100
    }
    
 
  } catch (error) {
    console.error('Error loading initial data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load data',
      life: 3000
    })
  } finally {
    loading.value = false
    loadingProgress.value = 100
  }
}

const fetchSpecializations = async () => {
  try {
    const result = await ficheNavetteService.getAllSpecializations()
    if (result.success) {
      specializations.value = result.data || []
    }
  } catch (error) {
    console.error('Error fetching specializations:', error)
  }
}

const fetchAllDoctors = async () => {
  try {
    const result = await ficheNavetteService.getAllDoctors()
    if (result.success) {
      allDoctors.value = result.data || []
    }
  } catch (error) {
    console.error('Error fetching all doctors:', error)
  }
}

const fetchAllPrestations = async () => {
  try {
    const prestationResult = await ficheNavetteService.getAllPrestations();

    if (prestationResult.success) {
      allPrestations.value = prestationResult.data || []
    }
  } catch (error) {
    console.error('Error fetching prestations:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load prestations',
      life: 3000
    });
  }
}



// --- Fiche Navette Creation ---
const createFicheNavette = async (data) => {
  creating.value = true
  try {
    let result
    if (props.ficheNavetteId) {
      result = await ficheNavetteService.addItemsToFiche(props.ficheNavetteId, data)
    } else {
      result = await ficheNavetteService.createFicheNavette(data)
    }
    if (result.success) {
      // Update frontend state immediately without refetching
      updateFrontendStateAfterCreation(data, result.data)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: props.ficheNavetteId ? 'Items added successfully' : 'Fiche Navette created successfully',
        life: 3000
      })
      resetSelections()
      emit('created', result.data)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error creating fiche navette:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || error.message || 'Failed to create fiche navette',
      life: 3000
    })
  } finally {
    creating.value = false
  }
}

// Update frontend state without API call
const updateFrontendStateAfterCreation = (requestData, responseData) => {
  // Update local prestations state if new prestations were created
  if (requestData.prestations && requestData.prestations.length > 0) {
    requestData.prestations.forEach(prestation => {
      // If it's a new custom prestation, add it to the list
      if (!allPrestations.value.find(p => p.id === prestation.id)) {
        allPrestations.value.push({
          ...prestation,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        })
      }
    })
  }

  // Update packages state if new packages were created
  if (requestData.packages && requestData.packages.length > 0) {
    requestData.packages.forEach(packageItem => {
      if (!availablePackages.value.find(p => p.id === packageItem.id)) {
        availablePackages.value.push({
          ...packageItem,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        })
      }
    })
  }

  // Simplified - no appointment states to clear
}

// --- Simple Handlers ---
const handleItemsCreated = (data) => {
  // Directly create the fiche navette with the selected data
  createFicheNavette(data)
}

// --- Simplified Methods (No appointments or conventions) ---

const resetSelections = () => {
  hasSelectedItems.value = false
}

// --- Tab Logic ---
const onTabChange = (event) => {
  if (hasSelectedItems.value) {
    event.preventDefault()
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please create the current selection or reset before switching tabs',
      life: 3000
    })
    return false
  }
  activeTab.value = event.index
}

// --- Computed Properties ---
const hasAnySelectedItems = computed(() => hasSelectedItems.value)
const isPrestationTab = computed(() => activeTab.value === 0)
const isCustomTab = computed(() => activeTab.value === 1)

const otherItemsCount = computed(() => {
  return 0
})



// LIFECYCLE
onMounted(() => {
  console.log('=== FicheNavetteItemCreate mounted ===')
  console.log('Props:', props)
  fetchInitialData().then(() => {
    console.log('Initial data fetch completed')
  })
})
</script>

<template>
  <div class=" tw-bg-gradient-to-br tw-from-slate-50 tw-to-blue-50">
    <div class="tw-max-w-7xl tw-mx-auto tw-space-y-1">
      
      <!-- Header Section -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-gray-200 tw-overflow-hidden">
       
        <!-- Loading Progress -->
        <div v-if="loading" class="tw-px-2 tw-py-2 tw-bg-gray-50 tw-border-b tw-border-gray-200">
          <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
            <span class="tw-text-sm tw-font-medium tw-text-gray-700">Loading data...</span>
            <span class="tw-text-sm tw-text-gray-500">{{ Math.round(loadingProgress) }}%</span>
          </div>
          <ProgressBar :value="loadingProgress" class="tw-h-2 tw-mb-3" />
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <Badge
              v-for="step in loadingSteps"
              :key="step.name"
              :value="step.name"
              :severity="step.completed ? 'success' : 'secondary'"
              class="tw-text-xs"
            />
          </div>
        </div>
      </div>



      <!-- Enhanced TabView -->
      <Card class="tw-shadow-xl tw-border-0 tw-overflow-hidden">
        <template #content>
          <div class="tw-p-6">
           

            <!-- Tab Content -->
            <div class="tw-transition-all tw-duration-300">
              <div v-show="activeTab === 0" class="tw-space-y-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
                  <i class="pi pi-list tw-text-blue-600"></i>
                  <h3 class="tw-font-semibold tw-text-gray-900">Available Prestations</h3>
                  <Badge
                    :value="`${allPrestations.length} items`"
                    severity="info"
                    class="tw-ml-auto"
                  />
                </div>
                <PrestationSelection
                  :specializations="specializations"
                  :all-doctors="allDoctors"
                  :available-prestations="allPrestations"
                  :loading="loading"
                  :selected-doctor="selectedDoctor"
                  :selected-specialization="selectedSpecialization"
                  @update:has-selected-items="hasSelectedItems = $event"
                  @update:selected-doctor="selectedDoctor = $event"
                  @update:selected-specialization="selectedSpecialization = $event"
                  @items-to-create="handleItemsCreated"
                />
              </div>

              <div v-show="activeTab === 1" class="tw-space-y-4">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
                  <i class="pi pi-plus-circle tw-text-green-600"></i>
                  <h3 class="tw-font-semibold tw-text-gray-900">Nursing Products</h3>
                </div>
                <div class="tw-text-center tw-py-12 tw-text-gray-500">
                  <i class="pi pi-heart tw-text-4xl tw-text-green-400 tw-mb-4 tw-block"></i>
                  <h4 class="tw-text-lg tw-font-medium tw-mb-2">Nursing Tab</h4>
                  <p>Nursing functionality will be implemented here</p>
                </div>
              </div>
            </div>

            <!-- Status Bar -->
            <div v-if="hasSelectedItems || creating" class="tw-mt-6 tw-p-4 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-border tw-border-blue-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-blue-600 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-check tw-text-white tw-text-sm"></i>
                  </div>
                  <div>
                    <span class="tw-font-semibold tw-text-gray-900">
                      {{ hasSelectedItems ? 'Items Selected' : 'Processing...' }}
                    </span>
                    <p class="tw-text-sm tw-text-gray-600">
                      {{ hasSelectedItems ? 'Ready to create fiche navette' : 'Please wait while we process your request' }}
                    </p>
                  </div>
                </div>
                <div v-if="creating" class="tw-flex tw-items-center tw-gap-2">
                  <ProgressBar mode="indeterminate" class="tw-w-24 tw-h-2" />
                  <span class="tw-text-sm tw-text-gray-500">Creating...</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>


  </div>
</template>

<style scoped>
/* Modern UI Enhancements */
.add-items-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Enhanced Card Animations */
:deep(.p-card) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(10px);
}

:deep(.p-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Custom Toggle Button Styling */
:deep(.p-togglebutton) {
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
}

:deep(.p-togglebutton.p-highlight) {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-color: #3b82f6;
  transform: scale(1.02);
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

:deep(.p-togglebutton:not(.p-highlight)) {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #64748b;
}

:deep(.p-togglebutton:not(.p-highlight):hover) {
  background: #f1f5f9;
  border-color: #cbd5e1;
  transform: scale(1.01);
}

/* Enhanced Button Styling */
:deep(.p-button) {
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

:deep(.p-button-text) {
  box-shadow: none;
}

:deep(.p-button-text:hover) {
  transform: scale(1.05);
  box-shadow: none;
}

/* Progress Bar Enhancements */
:deep(.p-progressbar) {
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.1);
}

:deep(.p-progressbar .p-progressbar-value) {
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  border-radius: 6px;
}

:deep(.p-progressbar[mode="indeterminate"] .p-progressbar-indeterminate-container) {
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #3b82f6);
  border-radius: 6px;
}

/* Badge Enhancements */
:deep(.p-badge) {
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Enhanced Tab Button Hover Effects */
.tw-transition-all:hover {
  transform: translateY(-1px);
}

/* Custom scrollbar for modern look */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 768px) {
  .tw-p-6 {
    padding: 1rem;
  }
  
  .tw-gap-6 {
    gap: 1rem;
  }
  
  .tw-text-2xl {
    font-size: 1.25rem;
    line-height: 1.75rem;
  }
}

/* Loading Animation */
@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.tw-animate-shimmer {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}
</style>