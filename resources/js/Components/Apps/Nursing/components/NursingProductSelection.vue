<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Chip from 'primevue/chip'
import axios from 'axios'

const emit = defineEmits(['update:hasSelectedItems', 'update:selectedNursingItems'])
const props = defineProps({
  patientId: { type: Number, required: true },
  ficheNavetteId: { type: Number, required: false }
})

const toast = useToast()

// Reactive data
const loading = ref(false)
const products = ref([])
const selectedProducts = ref([])
const searchTerm = ref('')
const searchDebounceTimeout = ref(null)

// Computed - FIXED SEARCH
const filteredProducts = computed(() => {
  if (!searchTerm.value || searchTerm.value.trim() === '') {
    return products.value
  }
  
  const search = searchTerm.value.toLowerCase().trim()
  
  return products.value.filter(product => {
    // Safe string checking helper
    const matchesField = (field) => {
      return field && typeof field === 'string' && field.toLowerCase().includes(search)
    }
    
    // Check all searchable fields safely
    return matchesField(product.name) ||
           matchesField(product.code_interne) ||
           matchesField(product.category) ||
           matchesField(product.forme) ||
           matchesField(product.description) ||
           matchesField(product.notes) ||
           matchesField(product.dosage) ||
           matchesField(product.indication) ||
           matchesField(product.inventory_display)
  })
})

const hasSelectedItems = computed(() => {
  return selectedProducts.value.length > 0 && 
         selectedProducts.value.some(item => item.quantity > 0)
})

const getTotalSelectedItems = computed(() => {
  return selectedProducts.value.reduce((total, item) => total + (item.quantity || 0), 0)
})

// Watch for changes and emit to parent
watch(hasSelectedItems, (newValue) => {
  emit('update:hasSelectedItems', newValue)
})

watch(selectedProducts, (newValue) => {
  emit('update:selectedNursingItems', newValue.filter(item => item.quantity > 0))
}, { deep: true })

// Methods
const fetchProducts = async () => {
  loading.value = true
  try {
    // Fetch from both pharmacy products and general products
    const [pharmacyResponse, stockResponse] = await Promise.all([
      axios.get('/api/pharmacy/products'),
      axios.get('/api/products')
    ])

    const pharmacyProducts = pharmacyResponse.data.data || pharmacyResponse.data || []
    const stockProducts = stockResponse.data.data || stockResponse.data || []

    // Combine and deduplicate products
    const allProducts = [...pharmacyProducts, ...stockProducts]
    const uniqueProducts = allProducts.reduce((acc, product) => {
      const existingIndex = acc.findIndex(p => p.id === product.id)
      if (existingIndex === -1) {
        acc.push({
          ...product,
          source: pharmacyProducts.includes(product) ? 'pharmacy' : 'stock',
          quantity: 0,
          selected: false
        })
      }
      return acc
    }, [])

    products.value = uniqueProducts
  } catch (error) {
    console.error('Error fetching products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load products',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const addToSelection = (product) => {
  const existingIndex = selectedProducts.value.findIndex(item => item.id === product.id)
  
  if (existingIndex === -1) {
    selectedProducts.value.push({
      ...product,
      quantity: 1,
      selected: true
    })
    
    toast.add({
      severity: 'success',
      summary: 'Product Added',
      detail: `${product.name} added to selection`,
      life: 2000
    })
  } else {
    selectedProducts.value[existingIndex].quantity += 1
    
    toast.add({
      severity: 'info',
      summary: 'Quantity Updated',
      detail: `${product.name} quantity: ${selectedProducts.value[existingIndex].quantity}`,
      life: 2000
    })
  }
}

const removeFromSelection = (productId) => {
  selectedProducts.value = selectedProducts.value.filter(item => item.id !== productId)
}

const updateQuantity = (productId, quantity) => {
  const index = selectedProducts.value.findIndex(item => item.id === productId)
  if (index !== -1) {
    if (quantity <= 0) {
      removeFromSelection(productId)
    } else {
      selectedProducts.value[index].quantity = quantity
    }
  }
}

const clearSelection = () => {
  selectedProducts.value = []
}

const handleSearchInput = (event) => {
  // Clear existing timeout
  if (searchDebounceTimeout.value) {
    clearTimeout(searchDebounceTimeout.value)
  }
  
  // Set new timeout for debounced search
  searchDebounceTimeout.value = setTimeout(() => {
    console.log(`Searching for: ${event.target.value}`)
  }, 300)
}

// Expose methods for parent component access
defineExpose({
  clearSelection,
  selectedProducts,
  hasSelectedItems
})

// Lifecycle
onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="tw-space-y-8 tw-p-6">
    <!-- Enhanced Header Section with Statistics -->
    <div class="tw-bg-gradient-to-r tw-from-emerald-50 tw-to-teal-50 tw-rounded-2xl tw-p-6 tw-border tw-border-emerald-200">
      <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-4">
        <!-- Title and Description -->
        <div class="tw-flex tw-items-start tw-gap-4">
          <div class="tw-flex tw-items-center tw-justify-center tw-w-14 tw-h-14 tw-bg-gradient-to-br tw-from-emerald-500 tw-to-teal-600 tw-rounded-xl tw-shadow-lg tw-flex-shrink-0">
            <i class="pi pi-shopping-cart tw-text-white tw-text-xl"></i>
          </div>
          <div>
            <h3 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-1">Product Selection</h3>
            <p class="tw-text-gray-600 tw-text-sm tw-leading-relaxed tw-max-w-md">
              Browse and select medications, medical supplies, and consumables from our comprehensive inventory system
            </p>
          </div>
        </div>
        
        <!-- Statistics and Actions -->
        <div class="tw-flex tw-items-center tw-gap-4">
          <!-- Selection Statistics -->
          <div v-if="selectedProducts.length > 0" class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-bg-white tw-rounded-lg tw-px-4 tw-py-2 tw-shadow-sm tw-border tw-border-emerald-200">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Badge 
                  :value="selectedProducts.length"
                  severity="success"
                  class="tw-bg-emerald-100 tw-text-emerald-800"
                />
                <span class="tw-text-sm tw-font-medium tw-text-gray-700">Products Selected</span>
              </div>
            </div>
            
            <div class="tw-bg-white tw-rounded-lg tw-px-4 tw-py-2 tw-shadow-sm tw-border tw-border-emerald-200">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Badge 
                  :value="getTotalSelectedItems"
                  severity="info"
                  class="tw-bg-blue-100 tw-text-blue-800"
                />
                <span class="tw-text-sm tw-font-medium tw-text-gray-700">Total Quantity</span>
              </div>
            </div>
          </div>
          
          <!-- Clear All Action -->
          <Button 
            v-if="selectedProducts.length > 0"
            @click="clearSelection"
            label="Clear All"
            icon="pi pi-trash"
            severity="secondary"
            class="tw-bg-red-50 tw-text-red-600 tw-border-red-200 hover:tw-bg-red-100 tw-font-medium tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-300"
          />
        </div>
      </div>
    </div>

    <!-- Enhanced Search and Filter Section -->
    <Card class="">
      <template #content>
        <div class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-slate-50 tw-p-6 tw-rounded-xl">
          <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4 tw-items-start lg:tw-items-center">
            <!-- Search Input with Enhanced Styling -->
            <div class="tw-flex-1 tw-w-full lg:tw-w-auto">
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                <i class="pi pi-search tw-mr-2 tw-text-gray-500"></i>
                Search Products
              </label>
              <span class="p-input-icon-left tw-w-full tw-relative">
                <i class="pi pi-search tw-text-gray-400" />
                <InputText 
                  v-model="searchTerm"
                  @input="handleSearchInput"
                  placeholder="Search by name, internal code, category, form, description, or indication..."
                  class="tw-w-full tw-pl-10 tw-pr-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-rounded-xl tw-bg-white tw-shadow-sm focus:tw-border-emerald-400 focus:tw-ring-2 focus:tw-ring-emerald-100 tw-transition-all tw-duration-300"
                />
                <!-- Search Results Counter -->
                <div v-if="searchTerm" class="tw-absolute tw-right-3 tw-top-1/2 tw-transform tw--translate-y-1/2">
                  <Badge 
                    :value="`${filteredProducts.length} found`"
                    severity="info"
                    class="tw-text-xs tw-bg-blue-100 tw-text-blue-700"
                  />
                </div>
              </span>
              <!-- Search Help Note -->
              <div class="tw-mt-2 tw-flex tw-flex-wrap tw-gap-1 tw-text-xs tw-text-gray-500">
                <span class="tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded tw-font-medium">Searchable fields:</span>
                <span class="tw-bg-emerald-100 tw-text-emerald-700 tw-px-2 tw-py-1 tw-rounded">Name</span>
                <span class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-1 tw-rounded">Internal Code</span>
                <span class="tw-bg-purple-100 tw-text-purple-700 tw-px-2 tw-py-1 tw-rounded">Category</span>
                <span class="tw-bg-orange-100 tw-text-orange-700 tw-px-2 tw-py-1 tw-rounded">Form</span>
                <span class="tw-bg-pink-100 tw-text-pink-700 tw-px-2 tw-py-1 tw-rounded">Description</span>
                <span class="tw-bg-indigo-100 tw-text-indigo-700 tw-px-2 tw-py-1 tw-rounded">Indication</span>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="tw-flex tw-gap-3 tw-w-full lg:tw-w-auto tw-mt-4 lg:tw-mt-6">
              <!-- Refresh Button -->
              <Button 
                @click="fetchProducts"
                :loading="loading"
                icon="pi pi-refresh"
                label="Refresh Inventory"
                severity="secondary"
                class="tw-flex-1 lg:tw-flex-none tw-bg-white tw-border-2 tw-border-gray-300 tw-text-gray-700 hover:tw-bg-gray-50 tw-px-4 tw-py-3 tw-rounded-xl tw-font-medium tw-shadow-sm tw-transition-all tw-duration-300"
              />
              
              <!-- Quick Stats -->
              <div class="tw-hidden lg:tw-flex tw-items-center tw-gap-2 tw-bg-white tw-px-4 tw-py-3 tw-rounded-xl tw-border-2 tw-border-gray-200 tw-shadow-sm">
                <i class="pi pi-database tw-text-gray-500"></i>
                <span class="tw-text-sm tw-font-medium tw-text-gray-700">
                  {{ products.length }} Available
                </span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Enhanced Selected Products Summary with Animation -->
    <Transition
      enter-active-class="tw-transition-all tw-duration-500 tw-ease-out"
      enter-from-class="tw-opacity-0 tw-transform tw-scale-95 tw--translate-y-4"
      enter-to-class="tw-opacity-100 tw-transform tw-scale-100 tw-translate-y-0"
      leave-active-class="tw-transition-all tw-duration-300 tw-ease-in"
      leave-from-class="tw-opacity-100"
      leave-to-class="tw-opacity-0 tw-transform tw-scale-95"
    >
      <div v-if="selectedProducts.length > 0" class="tw-mb-8">
        <Card class="tw-shadow-xl tw-border-0 tw-overflow-hidden tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50">
          <template #title>
            <!-- Enhanced Title with Animation -->
            <div class="tw-flex tw-items-center tw-justify-between tw-p-6 tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-m--6 tw-mb-0">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <i class="pi pi-shopping-cart tw-text-2xl"></i>
                  <!-- Animated Badge -->
                  <div class="tw-absolute tw--top-2 tw--right-2 tw-bg-yellow-400 tw-text-yellow-900 tw-rounded-full tw-w-6 tw-h-6 tw-flex tw-items-center tw-justify-center tw-text-xs tw-font-bold tw-animate-bounce">
                    {{ selectedProducts.length }}
                  </div>
                </div>
                <div>
                  <h3 class="tw-text-xl tw-font-bold tw-mb-1">Selected Products Cart</h3>
                  <p class="tw-text-blue-100 tw-text-sm">Review and adjust quantities before adding to patient care</p>
                </div>
              </div>
              
              <!-- Cart Summary Stats -->
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-text-center">
                  <div class="tw-text-2xl tw-font-bold">{{ selectedProducts.length }}</div>
                  <div class="tw-text-xs tw-text-blue-100">Products</div>
                </div>
                <div class="tw-text-center">
                  <div class="tw-text-2xl tw-font-bold">{{ getTotalSelectedItems }}</div>
                  <div class="tw-text-xs tw-text-blue-100">Total Units</div>
                </div>
              </div>
            </div>
          </template>
          
          <template #content>
            <div class="tw-p-6 tw-space-y-4">
              <!-- Product Cards Grid -->
              <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-4">
                <div 
                  v-for="(item, index) in selectedProducts" 
                  :key="item.id"
                  class="product-card tw-bg-white tw-rounded-xl tw-p-4 tw-shadow-sm tw-border-2 tw-border-gray-100 hover:tw-border-blue-300 tw-transition-all tw-duration-300 hover:tw-shadow-md"
                  :style="{ 'animation-delay': `${index * 0.1}s` }"
                >
                  <div class="tw-flex tw-items-start tw-gap-4">
                    <!-- Product Icon and Source -->
                    <div class="tw-flex-shrink-0">
                      <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-lg tw-shadow-lg"
                           :class="item.source === 'pharmacy' ? 'tw-bg-gradient-to-br tw-from-green-500 tw-to-emerald-600' : 'tw-bg-gradient-to-br tw-from-orange-500 tw-to-amber-600'">
                        {{ item.name.charAt(0).toUpperCase() }}
                      </div>
                    </div>
                    
                    <!-- Product Details --> 
                    <div class="tw-flex-1 tw-min-w-0">
                      <h5 class="tw-font-semibold tw-text-gray-900 tw-truncate tw-mb-1">{{ item.name }}</h5>
                      <div class="tw-space-y-1">
                        <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-500">
                          <i class="pi pi-tag tw-text-xs"></i>
                          <span>{{ item.code_interne || 'No Code' }}</span>
                        </div>
                        <div class="tw-flex tw-items-center tw-gap-2">
                          <Badge 
                            :value="item.source"
                            :severity="item.source === 'pharmacy' ? 'success' : 'warning'"
                            class="tw-text-xs"
                          />
                          <Badge 
                            v-if="item.category"
                            :value="item.category"
                            severity="info"
                            class="tw-text-xs tw-bg-blue-100 tw-text-blue-700"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Quantity Controls -->
                  <div class="tw-flex tw-items-center tw-justify-between tw-mt-4 tw-pt-4 tw-border-t tw-border-gray-100">
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <label class="tw-text-sm tw-font-semibold tw-text-gray-700">Quantity:</label>
                      <InputNumber 
                        :modelValue="item.quantity"
                        @update:modelValue="(value) => updateQuantity(item.id, value)"
                        :min="1"
                        :max="1000"
                        showButtons
                        buttonLayout="horizontal"
                        class="quantity-input tw-w-32"
                        :pt="{
                          input: { class: 'tw-text-center tw-font-semibold' },
                          incrementButton: { class: 'tw-bg-blue-500 hover:tw-bg-blue-600 tw-border-blue-500' },
                          decrementButton: { class: 'tw-bg-blue-500 hover:tw-bg-blue-600 tw-border-blue-500' }
                        }"
                      />
                    </div>
                    
                    <!-- Remove Button -->
                    <Button 
                      @click="removeFromSelection(item.id)"
                      icon="pi pi-trash"
                      severity="danger"
                      size="small"
                      class="tw-bg-red-50 tw-text-red-600 tw-border-red-200 hover:tw-bg-red-100 tw-rounded-lg tw-transition-all tw-duration-300"
                      v-tooltip.top="'Remove from selection'"
                    />
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </Transition>

    <!-- Enhanced Products Inventory Table -->
    <Card class="tw-shadow-xl tw-border-0 tw-overflow-hidden">
      <template #title>
        <!-- Modern Table Header -->
        <div class="tw-bg-gradient-to-r tw-from-emerald-500 tw-to-teal-600 tw-text-white tw-p-6 tw-m--6 tw-mb-0">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-w-10 tw-h-10 tw-bg-white tw-bg-opacity-20 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <i class="pi pi-database tw-text-xl"></i>
              </div>
              <div>
                <h3 class="tw-text-xl tw-font-bold tw-mb-1">Inventory Browser</h3>
                <p class="tw-text-emerald-100 tw-text-sm">Browse {{ filteredProducts.length }} available products from pharmacy and general stock</p>
              </div>
            </div>
            
            <!-- Inventory Stats -->
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-text-center">
                <div class="tw-text-lg tw-font-bold">{{ products.filter(p => p.source === 'pharmacy').length }}</div>
                <div class="tw-text-xs tw-text-emerald-100">Pharmacy</div>
              </div>
              <div class="tw-text-center">
                <div class="tw-text-lg tw-font-bold">{{ products.filter(p => p.source === 'stock').length }}</div>
                <div class="tw-text-xs tw-text-emerald-100">Stock</div>
              </div>
            </div>
          </div>
        </div>
      </template>
      
      <template #content>
        <div class="tw-p-0">
          <!-- Enhanced DataTable with Custom Styling -->
          <DataTable 
            :value="filteredProducts"
            :loading="loading"
            paginator
            :rows="12"
            :rowsPerPageOptions="[12, 24, 48]"
            scrollable
            scrollHeight="500px"
            class="enhanced-products-table"
            :pt="{
              root: { class: 'tw-border-0' },
              header: { class: 'tw-bg-gray-50 tw-border-b-2 tw-border-emerald-200' },
              tbody: { class: 'tw-divide-y tw-divide-gray-100' }
            }"
          >
            <!-- Custom Empty State -->
            <template #empty>
              <div class="tw-text-center tw-py-16">
                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-20 tw-h-20 tw-bg-gray-100 tw-rounded-full tw-mb-4">
                  <i class="pi pi-inbox tw-text-4xl tw-text-gray-400"></i>
                </div>
                <h4 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">No Products Found</h4>
                <p class="tw-text-gray-500 tw-max-w-sm tw-mx-auto">
                  {{ searchTerm ? 'Try adjusting your search terms or clear the search to see all products.' : 'No products are currently available in the inventory.' }}
                </p>
                <Button 
                  v-if="searchTerm"
                  @click="searchTerm = ''"
                  label="Clear Search"
                  icon="pi pi-times"
                  severity="secondary"
                  class="tw-mt-4"
                />
              </div>
            </template>
            
            <!-- Enhanced Loading State -->
            <template #loading>
              <div class="tw-text-center tw-py-16">
                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-20 tw-h-20 tw-bg-blue-100 tw-rounded-full tw-mb-4">
                  <i class="pi pi-spin pi-spinner tw-text-3xl tw-text-blue-500"></i>
                </div>
                <h4 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">Loading Products</h4>
                <p class="tw-text-gray-500">Fetching the latest inventory data...</p>
              </div>
            </template>

            <!-- Product Name Column with Enhanced Display -->
            <Column field="name" header="Product Information" sortable class="tw-min-w-80">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-3 tw-py-2">
                  <!-- Product Avatar -->
                  <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-lg tw-shadow-md tw-flex-shrink-0"
                       :class="data.source === 'pharmacy' ? 'tw-bg-gradient-to-br tw-from-green-500 tw-to-emerald-600' : 'tw-bg-gradient-to-br tw-from-orange-500 tw-to-amber-600'">
                    {{ data.name.charAt(0).toUpperCase() }}
                  </div>
                  
                  <!-- Product Details -->
                  <div class="tw-flex-1 tw-min-w-0">
                    <h5 class="tw-font-semibold tw-text-gray-900 tw-truncate tw-mb-1">{{ data.name }}</h5>
                    <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-500 tw-mb-1">
                      <i class="pi pi-tag tw-text-xs"></i>
                      <span>{{ data.code_interne || 'No Internal Code' }}</span>
                    </div>
                    <!-- Inventory Information -->
                    <div v-if="data.inventory_display" class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-blue-600 tw-bg-blue-50 tw-px-2 tw-py-1 tw-rounded tw-font-medium">
                      <i class="pi pi-database tw-text-xs"></i>
                      <span>{{ data.inventory_display }}</span>
                    </div>
                    <!-- Location Breakdown -->
                    <div v-if="data.inventory_by_location && data.inventory_by_location.length > 0" class="tw-mt-1">
                      <div v-for="location in data.inventory_by_location.slice(0, 2)" :key="location.location" 
                           class="tw-text-xs tw-text-gray-600 tw-flex tw-items-center tw-gap-1">
                        <i class="pi pi-map-marker tw-text-xs tw-text-gray-400"></i>
                        <span class="tw-font-medium">{{ location.location }}:</span>
                        <span>{{ location.display }}</span>
                      </div>
                      <div v-if="data.inventory_by_location.length > 2" class="tw-text-xs tw-text-gray-500 tw-italic">
                        +{{ data.inventory_by_location.length - 2 }} more locations
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Category Column with Enhanced Badges -->
            <Column field="category" header="Category" sortable class="tw-min-w-32">
              <template #body="{ data }">
                <Badge 
                  :value="data.category || 'Uncategorized'" 
                  :severity="data.category ? 'info' : 'secondary'"
                  class="tw-px-3 tw-py-1 tw-rounded-full tw-font-medium tw-text-xs"
                  :class="data.category ? 'tw-bg-blue-100 tw-text-blue-800' : 'tw-bg-gray-100 tw-text-gray-600'"
                />
              </template>
            </Column>

            <!-- Source Column with Enhanced Display -->
            <Column field="source" header="Source" sortable class="tw-min-w-28">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i :class="data.source === 'pharmacy' ? 'pi pi-plus tw-text-green-600' : 'pi pi-box tw-text-orange-600'"></i>
                  <Badge 
                    :value="data.source === 'pharmacy' ? 'Pharmacy' : 'General Stock'"
                    :severity="data.source === 'pharmacy' ? 'success' : 'warning'"
                    class="tw-px-3 tw-py-1 tw-rounded-full tw-font-medium tw-text-xs"
                  />
                </div>
              </template>
            </Column>

            <!-- Form Column -->
            <Column field="forme" header="Form" sortable class="tw-min-w-24">
              <template #body="{ data }">
                <span class="tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded tw-text-center tw-block">
                  {{ data.forme || 'N/A' }}
                </span>
              </template>
            </Column>

            <!-- Availability Column -->
            <Column header="Availability" class="tw-min-w-32">
              <template #body="{ data }">
                <div class="tw-text-center">
                  <!-- Main Quantity Display -->
                  <div v-if="data.inventory_display" class="tw-text-sm tw-font-bold tw-text-blue-700 tw-mb-1">
                    {{ data.inventory_display }}
                  </div>
                  <div v-else class="tw-text-sm tw-text-gray-400 tw-mb-1">
                    No Stock
                  </div>
                  
                  <!-- Box/Unit Indicator -->
                  <div v-if="data.has_box_units || data.has_package_units" class="tw-text-xs tw-bg-emerald-100 tw-text-emerald-700 tw-px-2 tw-py-1 tw-rounded-full tw-font-medium">
                    <i class="pi pi-box tw-mr-1"></i>
                    {{ data.boite_de || data.units_per_package }} per box
                  </div>
                  <div v-else class="tw-text-xs tw-bg-gray-100 tw-text-gray-600 tw-px-2 tw-py-1 tw-rounded-full tw-font-medium">
                    Unit only
                  </div>
                </div>
              </template>
            </Column>

            <!-- Enhanced Actions Column -->
            <Column header="Actions" class="tw-min-w-36">
              <template #body="{ data }">
                <div class="tw-flex tw-gap-2 tw-justify-center">
                  <Button 
                    @click="addToSelection(data)"
                    :disabled="selectedProducts.some(item => item.id === data.id)"
                    size="small"
                    class="add-product-btn tw-transition-all tw-duration-300"
                    :class="selectedProducts.some(item => item.id === data.id) 
                      ? 'tw-bg-gray-100 tw-text-gray-400 tw-cursor-not-allowed' 
                      : 'tw-bg-emerald-500 hover:tw-bg-emerald-600 tw-text-white hover:tw-shadow-lg hover:tw-scale-105'"
                  >
                    <template #icon>
                      <i :class="selectedProducts.some(item => item.id === data.id) ? 'pi pi-check' : 'pi pi-plus'"></i>
                    </template>
                    <span class="tw-ml-2 tw-font-medium">
                      {{ selectedProducts.some(item => item.id === data.id) ? 'Added' : 'Add' }}
                    </span>
                  </Button>
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<style scoped>
/* Enhanced DataTable Styling */
:deep(.enhanced-products-table) {
  font-size: 0.9rem;
  border-radius: 0;
}

:deep(.enhanced-products-table .p-datatable-header) {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border: none;
  padding: 1rem 1.5rem;
  font-weight: 600;
  color: #374151;
}

:deep(.enhanced-products-table .p-datatable-tbody > tr) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid #f1f5f9;
}

:deep(.enhanced-products-table .p-datatable-tbody > tr:hover) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

:deep(.enhanced-products-table .p-datatable-tbody > tr > td) {
  padding: 1rem 1.5rem;
  border: none;
  vertical-align: middle;
}

/* Product Card Animation */
.product-card {
  animation: slideInUp 0.5s ease-out forwards;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Enhanced Input Number Styling */
:deep(.quantity-input .p-inputnumber-input) {
  text-align: center;
  font-weight: 600;
  border-radius: 0.5rem;
  border: 2px solid #e5e7eb;
  transition: all 0.3s ease;
}

:deep(.quantity-input .p-inputnumber-input:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.quantity-input .p-inputnumber-button) {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  border: 2px solid #3b82f6;
  transition: all 0.3s ease;
}

:deep(.quantity-input .p-inputnumber-button:hover) {
  background: #2563eb;
  transform: scale(1.05);
}

/* Add Product Button Animation */
.add-product-btn {
  position: relative;
  overflow: hidden;
}

.add-product-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s ease;
}

.add-product-btn:hover::before {
  left: 100%;
}

/* Enhanced Badge Styling */
:deep(.p-badge) {
  font-weight: 600;
  letter-spacing: 0.025em;
}

/* Custom Scrollbar */
:deep(.p-datatable-scrollable-body)::-webkit-scrollbar {
  width: 8px;
}

:deep(.p-datatable-scrollable-body)::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

:deep(.p-datatable-scrollable-body)::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

:deep(.p-datatable-scrollable-body)::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 768px) {
  :deep(.enhanced-products-table .p-datatable-tbody > tr > td) {
    padding: 0.75rem 1rem;
  }
  
  .product-card {
    margin-bottom: 1rem;
  }
}
</style>
