<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import axios from 'axios'

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  showBothTypes: {
    type: Boolean,
    default: true
  },
  defaultTab: {
    type: String,
    default: 'all', // 'all', 'stock', 'pharmacy'
    validator: (value) => ['all', 'stock', 'pharmacy'].includes(value)
  }
})

const emit = defineEmits(['update:visible', 'product-selected'])

// State
const activeTab = ref(0) // 0 = All, 1 = Stock, 2 = Pharmacy
const selectedProduct = ref(null)
const productQuantity = ref(1)
const productUnit = ref('unit')
const loadingProducts = ref(false)

// Combined products list for "All" tab
const allProducts = ref([])
const stockProducts = ref([])
const pharmacyProducts = ref([])

// Pagination states
const allProductsPagination = reactive({
  page: 1,
  totalPages: 1,
  loading: false,
  hasMore: true,
  searchQuery: ''
})

const stockProductsPagination = reactive({
  page: 1,
  totalPages: 1,
  loading: false,
  hasMore: true,
  searchQuery: ''
})

const pharmacyProductsPagination = reactive({
  page: 1,
  totalPages: 1,
  loading: false,
  hasMore: true,
  searchQuery: ''
})

// Refs for dropdowns
const allDropdownRef = ref(null)
const stockDropdownRef = ref(null)
const pharmacyDropdownRef = ref(null)

// Search debounce timers
let allSearchDebounce = null
let stockSearchDebounce = null
let pharmacySearchDebounce = null

// Computed
const isDialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const selectedProductDetails = computed(() => {
  if (!selectedProduct.value) return null
  
  const isPharmacy = selectedProduct.value.type === 'pharmacy'
  
  return {
    type: selectedProduct.value.type,
    name: isPharmacy 
      ? (selectedProduct.value.name || selectedProduct.value.generic_name || selectedProduct.value.brand_name)
      : (selectedProduct.value.name || selectedProduct.value.designation),
    code: isPharmacy 
      ? selectedProduct.value.sku 
      : (selectedProduct.value.code_interne || selectedProduct.value.code),
    unit: isPharmacy 
      ? (selectedProduct.value.unit_of_measure || 'pieces')
      : (selectedProduct.value.unit || 'pieces'),
    isPharmacy
  }
})

// Methods
const resetDialog = () => {
  selectedProduct.value = null
  productQuantity.value = 1
  productUnit.value = 'unit'
  
  // Reset pagination
  allProductsPagination.page = 1
  allProductsPagination.loading = false
  allProductsPagination.hasMore = true
  allProductsPagination.searchQuery = ''
  
  stockProductsPagination.page = 1
  stockProductsPagination.loading = false
  stockProductsPagination.hasMore = true
  stockProductsPagination.searchQuery = ''
  
  pharmacyProductsPagination.page = 1
  pharmacyProductsPagination.loading = false
  pharmacyProductsPagination.hasMore = true
  pharmacyProductsPagination.searchQuery = ''
  
  // Clear products
  allProducts.value = []
  stockProducts.value = []
  pharmacyProducts.value = []
}

const closeDialog = () => {
  resetDialog()
  isDialogVisible.value = false
}

// Load all products (combined stock + pharmacy)
const loadAllProducts = async (searchQuery = '', append = false) => {
  if (allProductsPagination.loading) return
  
  allProductsPagination.loading = true
  console.log(`🔍 Loading all products - Page: ${allProductsPagination.page}, Append: ${append}, Search: "${searchQuery}"`)
  
  try {
    // Load both stock and pharmacy products in parallel
    const [stockResponse, pharmacyResponse] = await Promise.all([
      axios.get('/api/products', {
        params: {
          page: allProductsPagination.page,
          per_page: 20,
          search: searchQuery
        }
      }),
      axios.get('/api/pharmacy/products', {
        params: {
          page: allProductsPagination.page,
          per_page: 20,
          search: searchQuery
        }
      })
    ])
    
    // Tag products with their type
    const stockProductsData = (stockResponse.data.data || []).map(p => ({ ...p, type: 'stock' }))
    const pharmacyProductsData = (pharmacyResponse.data.data || []).map(p => ({ ...p, type: 'pharmacy' }))
    
    // Combine and sort by name
    const combinedProducts = [...stockProductsData, ...pharmacyProductsData].sort((a, b) => {
      const nameA = (a.name || a.generic_name || a.designation || '').toLowerCase()
      const nameB = (b.name || b.generic_name || b.designation || '').toLowerCase()
      return nameA.localeCompare(nameB)
    })
    
    if (append) {
      allProducts.value = [...allProducts.value, ...combinedProducts]
    } else {
      allProducts.value = combinedProducts
    }
    
    // Check if more pages available (use the max of both)
    const stockHasMore = stockResponse.data.current_page < stockResponse.data.last_page
    const pharmacyHasMore = pharmacyResponse.data.current_page < pharmacyResponse.data.last_page
    allProductsPagination.hasMore = stockHasMore || pharmacyHasMore
    
    allProductsPagination.totalPages = Math.max(
      stockResponse.data.last_page || 1,
      pharmacyResponse.data.last_page || 1
    )
    
    console.log(`✅ Loaded ${combinedProducts.length} products (${stockProductsData.length} stock + ${pharmacyProductsData.length} pharmacy)`)
  } catch (error) {
    console.error('❌ Error loading all products:', error)
  } finally {
    allProductsPagination.loading = false
  }
}

// Load stock products
const loadStockProducts = async (searchQuery = '', append = false) => {
  if (stockProductsPagination.loading) return
  
  stockProductsPagination.loading = true
  console.log(`📦 Loading stock products - Page: ${stockProductsPagination.page}, Append: ${append}, Search: "${searchQuery}"`)
  
  try {
    const response = await axios.get('/api/products', {
      params: {
        page: stockProductsPagination.page,
        per_page: 20,
        search: searchQuery
      }
    })
    
    const productsData = (response.data.data || []).map(p => ({ ...p, type: 'stock' }))
    
    if (append) {
      stockProducts.value = [...stockProducts.value, ...productsData]
    } else {
      stockProducts.value = productsData
    }
    
    stockProductsPagination.hasMore = response.data.current_page < response.data.last_page
    stockProductsPagination.totalPages = response.data.last_page || 1
    
    console.log(`✅ Loaded ${productsData.length} stock products`)
  } catch (error) {
    console.error('❌ Error loading stock products:', error)
  } finally {
    stockProductsPagination.loading = false
  }
}

// Load pharmacy products
const loadPharmacyProducts = async (searchQuery = '', append = false) => {
  if (pharmacyProductsPagination.loading) return
  
  pharmacyProductsPagination.loading = true
  console.log(`💊 Loading pharmacy products - Page: ${pharmacyProductsPagination.page}, Append: ${append}, Search: "${searchQuery}"`)
  
  try {
    const response = await axios.get('/api/pharmacy/products', {
      params: {
        page: pharmacyProductsPagination.page,
        per_page: 20,
        search: searchQuery
      }
    })
    
    const productsData = (response.data.data || []).map(p => ({ ...p, type: 'pharmacy' }))
    
    if (append) {
      pharmacyProducts.value = [...pharmacyProducts.value, ...productsData]
    } else {
      pharmacyProducts.value = productsData
    }
    
    pharmacyProductsPagination.hasMore = response.data.current_page < response.data.last_page
    pharmacyProductsPagination.totalPages = response.data.last_page || 1
    
    console.log(`✅ Loaded ${productsData.length} pharmacy products`)
  } catch (error) {
    console.error('❌ Error loading pharmacy products:', error)
  } finally {
    pharmacyProductsPagination.loading = false
  }
}

// Search handlers
const onAllProductsSearch = async (event) => {
  const searchQuery = event.value || ''
  
  clearTimeout(allSearchDebounce)
  allSearchDebounce = setTimeout(async () => {
    allProductsPagination.page = 1
    allProductsPagination.searchQuery = searchQuery
    await loadAllProducts(searchQuery, false)
  }, 300)
}

const onStockProductsSearch = async (event) => {
  const searchQuery = event.value || ''
  
  clearTimeout(stockSearchDebounce)
  stockSearchDebounce = setTimeout(async () => {
    stockProductsPagination.page = 1
    stockProductsPagination.searchQuery = searchQuery
    await loadStockProducts(searchQuery, false)
  }, 300)
}

const onPharmacyProductsSearch = async (event) => {
  const searchQuery = event.value || ''
  
  clearTimeout(pharmacySearchDebounce)
  pharmacySearchDebounce = setTimeout(async () => {
    pharmacyProductsPagination.page = 1
    pharmacyProductsPagination.searchQuery = searchQuery
    await loadPharmacyProducts(searchQuery, false)
  }, 300)
}

// Scroll handlers for infinite scroll
const attachScrollListeners = async () => {
  await nextTick()
  
  // Multiple attempts to attach listeners as DOM might not be ready
  const attemptAttach = (attempt = 0) => {
    if (attempt > 10) return // Max 10 attempts
    
    setTimeout(() => {
      let attached = false
      
      // All products dropdown
      const allPanels = document.querySelectorAll('.p-dropdown-panel')
      allPanels.forEach(panel => {
        const itemsWrapper = panel.querySelector('.p-dropdown-items-wrapper')
        if (itemsWrapper && !itemsWrapper.dataset.scrollAttached) {
          // Determine which dropdown this belongs to based on active tab
          if (activeTab.value === 0) {
            itemsWrapper.addEventListener('scroll', onAllProductsScroll, { passive: true })
            itemsWrapper.dataset.scrollAttached = 'all'
            attached = true
            console.log('✅ Attached scroll listener to All Products dropdown')
          } else if (activeTab.value === 1) {
            itemsWrapper.addEventListener('scroll', onStockProductsScroll, { passive: true })
            itemsWrapper.dataset.scrollAttached = 'stock'
            attached = true
            console.log('✅ Attached scroll listener to Stock Products dropdown')
          } else if (activeTab.value === 2) {
            itemsWrapper.addEventListener('scroll', onPharmacyProductsScroll, { passive: true })
            itemsWrapper.dataset.scrollAttached = 'pharmacy'
            attached = true
            console.log('✅ Attached scroll listener to Pharmacy Products dropdown')
          }
        }
      })
      
      // If not attached, try again
      if (!attached && attempt < 10) {
        attemptAttach(attempt + 1)
      }
    }, 50 + (attempt * 50)) // Increasing delay
  }
  
  attemptAttach()
}

const removeScrollListeners = () => {
  // Remove all scroll listeners
  document.querySelectorAll('.p-dropdown-items-wrapper[data-scroll-attached]').forEach(wrapper => {
    const type = wrapper.dataset.scrollAttached
    if (type === 'all') {
      wrapper.removeEventListener('scroll', onAllProductsScroll)
    } else if (type === 'stock') {
      wrapper.removeEventListener('scroll', onStockProductsScroll)
    } else if (type === 'pharmacy') {
      wrapper.removeEventListener('scroll', onPharmacyProductsScroll)
    }
    delete wrapper.dataset.scrollAttached
    console.log(`🔓 Removed scroll listener for ${type}`)
  })
}

const onAllProductsScroll = async (event) => {
  if (!event?.target) return
  
  const { scrollTop, scrollHeight, clientHeight } = event.target
  const scrollBottom = scrollHeight - scrollTop - clientHeight
  
  console.log(`📜 All Products Scroll: ${Math.round(scrollTop)}/${Math.round(scrollHeight)}, Bottom: ${Math.round(scrollBottom)}px`)
  
  // Trigger when within 50px of bottom
  if (scrollBottom < 50 && allProductsPagination.hasMore && !allProductsPagination.loading) {
    console.log('🔄 Loading more All Products...')
    allProductsPagination.page++
    await loadAllProducts(allProductsPagination.searchQuery, true)
  }
}

const onStockProductsScroll = async (event) => {
  if (!event?.target) return
  
  const { scrollTop, scrollHeight, clientHeight } = event.target
  const scrollBottom = scrollHeight - scrollTop - clientHeight
  
  console.log(`📦 Stock Products Scroll: ${Math.round(scrollTop)}/${Math.round(scrollHeight)}, Bottom: ${Math.round(scrollBottom)}px`)
  
  // Trigger when within 50px of bottom
  if (scrollBottom < 50 && stockProductsPagination.hasMore && !stockProductsPagination.loading) {
    console.log('🔄 Loading more Stock Products...')
    stockProductsPagination.page++
    await loadStockProducts(stockProductsPagination.searchQuery, true)
  }
}

const onPharmacyProductsScroll = async (event) => {
  if (!event?.target) return
  
  const { scrollTop, scrollHeight, clientHeight } = event.target
  const scrollBottom = scrollHeight - scrollTop - clientHeight
  
  console.log(`💊 Pharmacy Products Scroll: ${Math.round(scrollTop)}/${Math.round(scrollHeight)}, Bottom: ${Math.round(scrollBottom)}px`)
  
  // Trigger when within 50px of bottom
  if (scrollBottom < 50 && pharmacyProductsPagination.hasMore && !pharmacyProductsPagination.loading) {
    console.log('🔄 Loading more Pharmacy Products...')
    pharmacyProductsPagination.page++
    await loadPharmacyProducts(pharmacyProductsPagination.searchQuery, true)
  }
}

const handleProductSelection = () => {
  if (!selectedProduct.value) return
  
  emit('product-selected', {
    product: selectedProduct.value,
    quantity: productQuantity.value,
    unit: productUnit.value,
    type: selectedProduct.value.type
  })
  
  closeDialog()
}

const getProductDisplayName = (product) => {
  if (!product) return 'Unnamed Product'
  
  if (product.type === 'pharmacy') {
    return product.name || product.generic_name || product.brand_name || 'Unnamed Product'
  } else {
    return product.name || product.designation || 'Unnamed Product'
  }
}

const getProductCode = (product) => {
  if (!product) return 'N/A'
  
  if (product.type === 'pharmacy') {
    return product.sku || 'N/A'
  } else {
    return product.code_interne || product.code || 'N/A'
  }
}

// Watch for dialog visibility
watch(() => props.visible, async (newVal) => {
  if (newVal) {
    resetDialog()
    loadingProducts.value = true
    
    try {
      // Load initial data based on default tab
      if (props.showBothTypes) {
        await Promise.all([
          loadAllProducts(),
          loadStockProducts(),
          loadPharmacyProducts()
        ])
      } else if (props.defaultTab === 'stock') {
        await loadStockProducts()
      } else if (props.defaultTab === 'pharmacy') {
        await loadPharmacyProducts()
      }
    } finally {
      loadingProducts.value = false
    }
  }
})
</script>

<template>
  <Dialog
    v-model:visible="isDialogVisible"
    modal
    header="Select Product"
    :style="{ width: '75rem' }"
    :closable="true"
    class="tw-rounded-2xl tw-shadow-2xl"
    @hide="resetDialog"
  >
    <div class="tw-space-y-6 tw-p-4">
      <!-- Tab View for All/Stock/Pharmacy -->
      <TabView v-if="showBothTypes" v-model:activeIndex="activeTab" class="tw-mb-6">
        <!-- All Products Tab -->
        <TabPanel>
          <template #header>
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-search tw-text-lg"></i>
              <span class="tw-font-semibold">All Products</span>
              <Badge :value="allProducts.length" severity="info" />
            </div>
          </template>
          
          <div class="tw-space-y-6">
            <Card class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
              <template #content>
                <div class="tw-space-y-6">
                  <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                      <i class="pi pi-search tw-mr-2 tw-text-blue-600"></i>
                      Search All Products <span class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      ref="allDropdownRef"
                      v-model="selectedProduct"
                      :options="allProducts"
                      :optionLabel="getProductDisplayName"
                      :optionValue="null"
                      placeholder="Search all products (stock & pharmacy)..."
                      filter
                      :disabled="loadingProducts"
                      @filter="onAllProductsSearch"
                      @show="attachScrollListeners"
                      @hide="removeScrollListeners"
                      class="tw-w-full tw-border-2 tw-border-blue-200 focus:tw-border-blue-400"
                    >
                      <template #option="{ option }">
                        <div class="tw-p-2 tw-flex tw-items-center tw-gap-3">
                          <Badge 
                            :value="option.type === 'pharmacy' ? 'PHARMACY' : 'STOCK'" 
                            :severity="option.type === 'pharmacy' ? 'danger' : 'info'"
                            class="tw-text-xs"
                          />
                          <div class="tw-flex-1">
                            <div class="tw-font-medium tw-text-gray-900">{{ getProductDisplayName(option) }}</div>
                            <div class="tw-text-xs tw-text-gray-500 tw-mt-1">Code: {{ getProductCode(option) }}</div>
                          </div>
                        </div>
                      </template>
                      <template #value="slotProps">
                        <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                          <Badge 
                            :value="slotProps.value.type === 'pharmacy' ? 'PHARMACY' : 'STOCK'" 
                            :severity="slotProps.value.type === 'pharmacy' ? 'danger' : 'info'"
                            class="tw-text-xs"
                          />
                          <span class="tw-font-medium">{{ getProductDisplayName(slotProps.value) }}</span>
                        </div>
                      </template>
                      <template #footer>
                        <div v-if="allProductsPagination.hasMore || allProductsPagination.loading" class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200 tw-bg-blue-50">
                          <span v-if="allProductsPagination.loading" class="tw-text-sm tw-text-blue-700 tw-font-medium">
                            <i class="pi pi-spin pi-spinner tw-mr-2"></i>Loading more products...
                          </span>
                          <span v-else class="tw-text-sm tw-text-blue-600 tw-font-medium">
                            <i class="pi pi-angle-double-down tw-mr-2"></i>Scroll down to load more ({{ allProducts.length }} loaded)
                          </span>
                        </div>
                        <div v-else class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200 tw-bg-gray-50">
                          <span class="tw-text-sm tw-text-gray-500">
                            <i class="pi pi-check tw-mr-2"></i>All products loaded ({{ allProducts.length }} total)
                          </span>
                        </div>
                      </template>
                    </Dropdown>
                  </div>
                  
                  <!-- Quantity and Unit -->
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                    <div>
                      <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                        <i class="pi pi-hashtag tw-mr-2 tw-text-blue-600"></i>
                        Quantity <span class="tw-text-red-500">*</span>
                      </label>
                      <InputNumber
                        v-model="productQuantity"
                        :min="1"
                        :max="999"
                        class="tw-w-full tw-border-2 tw-border-blue-200 focus:tw-border-blue-400"
                        showButtons
                        buttonLayout="horizontal"
                        incrementButtonIcon="pi pi-plus"
                        decrementButtonIcon="pi pi-minus"
                      />
                    </div>
                    
                    <div>
                      <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                        <i class="pi pi-tag tw-mr-2 tw-text-blue-600"></i>
                        Unit Type <span class="tw-text-red-500">*</span>
                      </label>
                      <Dropdown
                        v-model="productUnit"
                        :options="[
                          { label: 'Unit', value: 'unit', icon: 'pi pi-minus' },
                          { label: 'Box', value: 'box', icon: 'pi pi-box' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select unit type..."
                        class="tw-w-full tw-border-2 tw-border-blue-200 focus:tw-border-blue-400"
                      >
                        <template #option="{ option }">
                          <div class="tw-flex tw-items-center tw-gap-3">
                            <i :class="option.icon" class="tw-text-lg tw-text-blue-600"></i>
                            <span class="tw-font-medium">{{ option.label }}</span>
                          </div>
                        </template>
                        <template #value="slotProps">
                          <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                            <i :class="slotProps.value === 'unit' ? 'pi pi-minus' : 'pi pi-box'" class="tw-text-lg tw-text-blue-600"></i>
                            <span class="tw-font-medium">{{ slotProps.value === 'unit' ? 'Unit' : 'Box' }}</span>
                          </div>
                        </template>
                      </Dropdown>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
            
            <!-- Product Details Card -->
            <Card v-if="selectedProductDetails" class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
              <template #content>
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                  <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-2">
                    <i :class="selectedProductDetails.isPharmacy ? 'pi pi-pills tw-text-purple-600' : 'pi pi-box tw-text-indigo-600'"></i>
                    Product Details
                  </h4>
                  <Badge 
                    :value="selectedProductDetails.isPharmacy ? 'PHARMACY PRODUCT' : 'STOCK PRODUCT'" 
                    :severity="selectedProductDetails.isPharmacy ? 'danger' : 'info'"
                    :class="selectedProductDetails.isPharmacy ? 'tw-bg-purple-600 tw-text-white tw-font-bold tw-border-0' : 'tw-bg-indigo-600 tw-text-white tw-font-bold tw-border-0'"
                  />
                </div>
                <div class="tw-space-y-3">
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Name:</span>
                    <span class="tw-font-semibold tw-text-gray-800">{{ selectedProductDetails.name }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Code:</span>
                    <Tag :value="selectedProductDetails.code" :severity="selectedProductDetails.isPharmacy ? 'danger' : 'info'" />
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Unit:</span>
                    <Badge :value="selectedProductDetails.unit" />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>
        
        <!-- Stock Products Tab -->
        <TabPanel>
          <template #header>
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-box tw-text-lg"></i>
              <span class="tw-font-semibold">Stock Products</span>
              <Badge :value="stockProducts.length" severity="info" />
            </div>
          </template>
          
          <div class="tw-space-y-6">
            <Card class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
              <template #content>
                <div class="tw-space-y-6">
                  <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                      <i class="pi pi-search tw-mr-2 tw-text-indigo-600"></i>
                      Select Stock Product <span class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      ref="stockDropdownRef"
                      v-model="selectedProduct"
                      :options="stockProducts"
                      :optionLabel="getProductDisplayName"
                      :optionValue="null"
                      placeholder="Search and select a stock product..."
                      filter
                      filterBy="name,code_interne,designation,forme"
                      :disabled="loadingProducts"
                      @filter="onStockProductsSearch"
                      @show="attachScrollListeners"
                      @hide="removeScrollListeners"
                      class="tw-w-full tw-border-2 tw-border-indigo-200 focus:tw-border-indigo-400"
                    >
                      <template #option="{ option }">
                        <div class="tw-p-2">
                          <div class="tw-font-medium tw-text-gray-900">{{ getProductDisplayName(option) }}</div>
                          <div class="tw-text-xs tw-text-gray-500 tw-mt-1">Code: {{ getProductCode(option) }}</div>
                        </div>
                      </template>
                      <template #value="slotProps">
                        <div v-if="slotProps.value" class="tw-text-sm">
                          <span class="tw-font-medium">{{ getProductDisplayName(slotProps.value) }}</span>
                        </div>
                      </template>
                      <template #footer>
                        <div v-if="stockProductsPagination.hasMore || stockProductsPagination.loading" class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200 tw-bg-indigo-50">
                          <span v-if="stockProductsPagination.loading" class="tw-text-sm tw-text-indigo-700 tw-font-medium">
                            <i class="pi pi-spin pi-spinner tw-mr-2"></i>Loading more products...
                          </span>
                          <span v-else class="tw-text-sm tw-text-indigo-600 tw-font-medium">
                            <i class="pi pi-angle-double-down tw-mr-2"></i>Scroll down to load more ({{ stockProducts.length }} loaded)
                          </span>
                        </div>
                        <div v-else class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200 tw-bg-gray-50">
                          <span class="tw-text-sm tw-text-gray-500">
                            <i class="pi pi-check tw-mr-2"></i>All products loaded ({{ stockProducts.length }} total)
                          </span>
                        </div>
                      </template>
                    </Dropdown>
                  </div>
                  
                  <!-- Quantity and Unit -->
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                    <div>
                      <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                        <i class="pi pi-hashtag tw-mr-2 tw-text-indigo-600"></i>
                        Quantity <span class="tw-text-red-500">*</span>
                      </label>
                      <InputNumber
                        v-model="productQuantity"
                        :min="1"
                        :max="999"
                        class="tw-w-full tw-border-2 tw-border-indigo-200 focus:tw-border-indigo-400"
                        showButtons
                        buttonLayout="horizontal"
                        incrementButtonIcon="pi pi-plus"
                        decrementButtonIcon="pi pi-minus"
                      />
                    </div>
                    
                    <div>
                      <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                        <i class="pi pi-tag tw-mr-2 tw-text-indigo-600"></i>
                        Unit Type <span class="tw-text-red-500">*</span>
                      </label>
                      <Dropdown
                        v-model="productUnit"
                        :options="[
                          { label: 'Unit', value: 'unit', icon: 'pi pi-minus' },
                          { label: 'Box', value: 'box', icon: 'pi pi-box' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select unit type..."
                        class="tw-w-full tw-border-2 tw-border-indigo-200 focus:tw-border-indigo-400"
                      >
                        <template #option="{ option }">
                          <div class="tw-flex tw-items-center tw-gap-3">
                            <i :class="option.icon" class="tw-text-lg tw-text-indigo-600"></i>
                            <span class="tw-font-medium">{{ option.label }}</span>
                          </div>
                        </template>
                        <template #value="slotProps">
                          <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                            <i :class="slotProps.value === 'unit' ? 'pi pi-minus' : 'pi pi-box'" class="tw-text-lg tw-text-indigo-600"></i>
                            <span class="tw-font-medium">{{ slotProps.value === 'unit' ? 'Unit' : 'Box' }}</span>
                          </div>
                        </template>
                      </Dropdown>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
            
            <!-- Product Details Card -->
            <Card v-if="selectedProduct && selectedProduct.type === 'stock'" class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
              <template #content>
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                  <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-box tw-text-indigo-600"></i>
                    Product Details
                  </h4>
                  <Badge value="STOCK PRODUCT" severity="info" class="tw-bg-indigo-600 tw-text-white tw-font-bold tw-border-0" />
                </div>
                <div class="tw-space-y-3">
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Product Code:</span>
                    <Tag :value="selectedProduct.code_interne || selectedProduct.code" severity="info" />
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Category:</span>
                    <span class="tw-font-semibold tw-text-gray-800">{{ selectedProduct.category || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Unit:</span>
                    <Badge :value="selectedProduct.unit || 'pieces'" />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>
        
        <!-- Pharmacy Products Tab -->
        <TabPanel>
          <template #header>
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-pills tw-text-lg"></i>
              <span class="tw-font-semibold">Pharmacy Products</span>
              <Badge :value="pharmacyProducts.length" severity="danger" />
            </div>
          </template>
          
          <div class="tw-space-y-6">
            <Card class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
              <template #content>
                <div class="tw-space-y-6">
                  <div>
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                      <i class="pi pi-search tw-mr-2 tw-text-purple-600"></i>
                      Select Pharmacy Product <span class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      ref="pharmacyDropdownRef"
                      v-model="selectedProduct"
                      :options="pharmacyProducts"
                      :optionLabel="getProductDisplayName"
                      :optionValue="null"
                      placeholder="Search and select a pharmacy product..."
                      filter
                      filterBy="name,sku,generic_name,brand_name"
                      :disabled="loadingProducts"
                      @filter="onPharmacyProductsSearch"
                      @show="attachScrollListeners"
                      @hide="removeScrollListeners"
                      class="tw-w-full tw-border-2 tw-border-purple-200 focus:tw-border-purple-400"
                    >
                      <template #option="{ option }">
                        <div class="tw-p-2">
                          <div class="tw-font-medium tw-text-gray-900">{{ getProductDisplayName(option) }}</div>
                          <div class="tw-text-xs tw-text-gray-500 tw-mt-1">SKU: {{ getProductCode(option) }}</div>
                        </div>
                      </template>
                      <template #value="slotProps">
                        <div v-if="slotProps.value" class="tw-text-sm">
                          <span class="tw-font-medium">{{ getProductDisplayName(slotProps.value) }}</span>
                        </div>
                      </template>
                      <template #footer>
                        <div v-if="pharmacyProductsPagination.hasMore || pharmacyProductsPagination.loading" class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200 tw-bg-purple-50">
                          <span v-if="pharmacyProductsPagination.loading" class="tw-text-sm tw-text-purple-700 tw-font-medium">
                            <i class="pi pi-spin pi-spinner tw-mr-2"></i>Loading more products...
                          </span>
                          <span v-else class="tw-text-sm tw-text-purple-600 tw-font-medium">
                            <i class="pi pi-angle-double-down tw-mr-2"></i>Scroll down to load more ({{ pharmacyProducts.length }} loaded)
                          </span>
                        </div>
                        <div v-else class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200 tw-bg-gray-50">
                          <span class="tw-text-sm tw-text-gray-500">
                            <i class="pi pi-check tw-mr-2"></i>All products loaded ({{ pharmacyProducts.length }} total)
                          </span>
                        </div>
                      </template>
                    </Dropdown>
                  </div>
                  
                  <!-- Quantity and Unit -->
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                    <div>
                      <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                        <i class="pi pi-hashtag tw-mr-2 tw-text-purple-600"></i>
                        Quantity <span class="tw-text-red-500">*</span>
                      </label>
                      <InputNumber
                        v-model="productQuantity"
                        :min="1"
                        :max="999"
                        class="tw-w-full tw-border-2 tw-border-purple-200 focus:tw-border-purple-400"
                        showButtons
                        buttonLayout="horizontal"
                        incrementButtonIcon="pi pi-plus"
                        decrementButtonIcon="pi pi-minus"
                      />
                    </div>
                    
                    <div>
                      <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                        <i class="pi pi-tag tw-mr-2 tw-text-purple-600"></i>
                        Unit Type <span class="tw-text-red-500">*</span>
                      </label>
                      <Dropdown
                        v-model="productUnit"
                        :options="[
                          { label: 'Unit', value: 'unit', icon: 'pi pi-minus' },
                          { label: 'Box', value: 'box', icon: 'pi pi-box' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select unit type..."
                        class="tw-w-full tw-border-2 tw-border-purple-200 focus:tw-border-purple-400"
                      >
                        <template #option="{ option }">
                          <div class="tw-flex tw-items-center tw-gap-3">
                            <i :class="option.icon" class="tw-text-lg tw-text-purple-600"></i>
                            <span class="tw-font-medium">{{ option.label }}</span>
                          </div>
                        </template>
                        <template #value="slotProps">
                          <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                            <i :class="slotProps.value === 'unit' ? 'pi pi-minus' : 'pi pi-box'" class="tw-text-lg tw-text-purple-600"></i>
                            <span class="tw-font-medium">{{ slotProps.value === 'unit' ? 'Unit' : 'Box' }}</span>
                          </div>
                        </template>
                      </Dropdown>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
            
            <!-- Product Details Card -->
            <Card v-if="selectedProduct && selectedProduct.type === 'pharmacy'" class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
              <template #content>
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                  <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-pills tw-text-purple-600"></i>
                    Product Details
                  </h4>
                  <Badge value="PHARMACY PRODUCT" severity="danger" class="tw-bg-purple-600 tw-text-white tw-font-bold tw-border-0" />
                </div>
                <div class="tw-space-y-3">
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">SKU:</span>
                    <Tag :value="selectedProduct.sku" severity="danger" />
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Generic Name:</span>
                    <span class="tw-font-semibold tw-text-gray-800">{{ selectedProduct.generic_name || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Unit:</span>
                    <Badge :value="selectedProduct.unit_of_measure || 'pieces'" />
                  </div>
                  <div v-if="selectedProduct.brand_name" class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg">
                    <span class="tw-text-gray-600 tw-font-medium">Brand:</span>
                    <span class="tw-font-semibold tw-text-gray-800">{{ selectedProduct.brand_name }}</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>
      </TabView>
    </div>
    
    <!-- Dialog Footer -->
    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-4 tw-p-4 tw-bg-gray-50 tw-rounded-b-2xl">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="tw-bg-white tw-text-gray-700 tw-border-2 tw-border-gray-300 hover:tw-bg-gray-50 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-transition-all tw-duration-200"
          @click="closeDialog"
        />
        <Button
          label="Add Product"
          icon="pi pi-check"
          class="tw-bg-blue-600 hover:tw-bg-blue-700 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
          :disabled="!selectedProduct"
          @click="handleProductSelection"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
:deep(.p-tabview-nav-link) {
  transition: all 0.2s ease;
}

:deep(.p-tabview-nav-link:not(.p-disabled):not(.p-highlight):hover) {
  background-color: rgba(59, 130, 246, 0.1);
}

:deep(.p-tabview-nav-link.p-highlight) {
  background-color: rgba(59, 130, 246, 0.1);
  border-color: #3b82f6;
}
</style>
