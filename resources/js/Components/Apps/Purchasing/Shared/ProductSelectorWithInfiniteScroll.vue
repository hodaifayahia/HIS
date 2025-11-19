<template>
  <div class="product-selector-infinite-scroll">
    <!-- Search and Filter Controls -->
    <div class="tw-flex tw-flex-wrap tw-gap-3 tw-mb-4">
      <div class="tw-flex-1 tw-min-w-[200px]">
        <InputText
          v-model="searchQuery"
          placeholder="Search products..."
          class="tw-w-full"
          @input="handleSearch"
        >
          <template #prefix>
            <i class="pi pi-search tw-mr-2"></i>
          </template>
        </InputText>
      </div>

      <Dropdown
        v-model="selectedCategory"
        :options="categoryOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="All Categories"
        class="tw-w-48"
        showClear
        @change="handleCategoryChange"
      />

      <Button
        v-if="showSelectAll"
        @click="selectAllVisible"
        icon="pi pi-check-square"
        label="Select All"
        class="p-button-secondary"
      />
    </div>

    <!-- Tabs for Stock and Pharmacy Products -->
    <TabView v-model:activeIndex="activeTabIndex" @tab-change="handleTabChange" class="tw-mb-4">
      <TabPanel>
        <template #header>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-box"></i>
            <span>Stock Products</span>
            <Badge v-if="stockProducts.length > 0" :value="stockProducts.length" severity="success" />
          </div>
        </template>

    <!-- Products DataTable with Infinite Scroll (Stock) -->
    <div class="tw-relative">
      <DataTable
        ref="stockDataTableRef"
        :value="stockProducts"
        v-model:selection="selectedStockProducts"
        :loading="loadingStock"
        :paginator="false"
        selectionMode="multiple"
        dataKey="id"
        responsiveLayout="scroll"
        :metaKeySelection="false"
        :scrollHeight="scrollHeight"
        scrollDirection="vertical"
        class="tw-text-sm"
      >
        <template #empty>
          <div class="tw-text-center tw-py-8 tw-text-gray-500">
            <i class="pi pi-inbox tw-text-3xl tw-mb-2"></i>
            <p>No stock products found</p>
          </div>
        </template>

        <Column v-if="selectable" selectionMode="multiple" style="width: 3rem"></Column>

        <Column field="name" header="Product Name" :sortable="true">
          <template #body="slotProps">
            <div>
              <div class="tw-font-semibold tw-text-sm">{{ slotProps.data.name }}</div>
              <div v-if="slotProps.data.description" class="tw-text-xs tw-text-gray-500">
                {{ slotProps.data.description }}
              </div>
              <div class="tw-mt-1">
                <Tag
                  value="Stock"
                  severity="success"
                  style="font-size: 0.7rem"
                />
              </div>
            </div>
          </template>
        </Column>

        <Column field="category" header="Category" :sortable="true" style="width: 120px">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.category || 'Uncategorized'"
              style="font-size: 0.75rem"
            />
          </template>
        </Column>

        <Column field="unit" header="Unit" style="width: 100px">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.unit || slotProps.data.forme || 'pieces'"
              severity="info"
              style="font-size: 0.75rem"
            />
          </template>
        </Column>

        <Column v-if="showStock" field="stock" header="Stock" style="width: 100px">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.stock || 'N/A'"
              :severity="slotProps.data.stock > 0 ? 'success' : 'danger'"
              style="font-size: 0.75rem"
            />
          </template>
        </Column>
      </DataTable>

      <!-- Loading indicator at bottom for stock -->
      <div
        v-if="loadingStock && stockProducts.length > 0"
        class="tw-flex tw-justify-center tw-items-center tw-py-4 tw-bg-blue-50"
      >
        <i class="pi pi-spin pi-spinner tw-text-blue-600 tw-mr-2"></i>
        <span class="tw-text-sm tw-text-blue-600">Loading more stock products...</span>
      </div>

      <!-- End of list indicator for stock -->
      <div
        v-if="!stockHasMore && stockProducts.length > 0"
        class="tw-flex tw-justify-center tw-items-center tw-py-4 tw-bg-gray-50"
      >
        <span class="tw-text-sm tw-text-gray-600">
          <i class="pi pi-check tw-mr-2"></i>All {{ stockProducts.length }} stock products loaded
        </span>
      </div>
    </div>
      </TabPanel>

      <TabPanel>
        <template #header>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-heart"></i>
            <span>Pharmacy Products</span>
            <Badge v-if="pharmacyProducts.length > 0" :value="pharmacyProducts.length" severity="info" />
          </div>
        </template>

        <!-- Products DataTable with Infinite Scroll (Pharmacy) -->
        <div class="tw-relative">
          <DataTable
            ref="pharmacyDataTableRef"
            :value="pharmacyProducts"
            v-model:selection="selectedPharmacyProducts"
            :loading="loadingPharmacy"
            :paginator="false"
            selectionMode="multiple"
            dataKey="id"
            responsiveLayout="scroll"
            :metaKeySelection="false"
            :scrollHeight="scrollHeight"
            scrollDirection="vertical"
            class="tw-text-sm"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8 tw-text-gray-500">
                <i class="pi pi-inbox tw-text-3xl tw-mb-2"></i>
                <p>No pharmacy products found</p>
              </div>
            </template>

            <Column v-if="selectable" selectionMode="multiple" style="width: 3rem"></Column>

            <Column field="name" header="Product Name" :sortable="true">
              <template #body="slotProps">
                <div>
                  <div class="tw-font-semibold tw-text-sm">{{ slotProps.data.name }}</div>
                  <div v-if="slotProps.data.description" class="tw-text-xs tw-text-gray-500">
                    {{ slotProps.data.description }}
                  </div>
                  <div class="tw-mt-1">
                    <Tag
                      value="Pharmacy"
                      severity="info"
                      style="font-size: 0.7rem"
                    />
                  </div>
                </div>
              </template>
            </Column>

            <Column field="category" header="Category" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.category || 'Uncategorized'"
                  severity="secondary"
                  style="font-size: 0.75rem"
                />
              </template>
            </Column>

            <Column field="unit" header="Unit" style="width: 100px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.unit || slotProps.data.forme || 'pieces'"
                  severity="info"
                  style="font-size: 0.75rem"
                />
              </template>
            </Column>

            <Column v-if="showStock" field="stock" header="Stock" style="width: 100px">
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.stock || 'N/A'"
                  :severity="slotProps.data.stock > 0 ? 'success' : 'danger'"
                  style="font-size: 0.75rem"
                />
              </template>
            </Column>
          </DataTable>

          <!-- Loading indicator at bottom for pharmacy -->
          <div
            v-if="loadingPharmacy && pharmacyProducts.length > 0"
            class="tw-flex tw-justify-center tw-items-center tw-py-4 tw-bg-blue-50"
          >
            <i class="pi pi-spin pi-spinner tw-text-blue-600 tw-mr-2"></i>
            <span class="tw-text-sm tw-text-blue-600">Loading more pharmacy products...</span>
          </div>

          <!-- End of list indicator for pharmacy -->
          <div
            v-if="!pharmacyHasMore && pharmacyProducts.length > 0"
            class="tw-flex tw-justify-center tw-items-center tw-py-4 tw-bg-gray-50"
          >
            <span class="tw-text-sm tw-text-gray-600">
              <i class="pi pi-check tw-mr-2"></i>All {{ pharmacyProducts.length }} pharmacy products loaded
            </span>
          </div>
        </div>
      </TabPanel>
    </TabView>

    <!-- Default Settings Controls -->
    <div class="tw-mt-6 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border tw-border-blue-200 tw-rounded-xl tw-p-4 tw-shadow-sm">
      <div class="tw-flex tw-items-center tw-gap-1 tw-mb-3">
        <i class="pi pi-cog tw-text-blue-600 tw-text-sm"></i>
        <span class="tw-text-sm tw-font-semibold tw-text-blue-800">Default Settings</span>
      </div>

      <div class="tw-grid tw-grid-cols-3 tw-gap-3">
        <!-- Quantity Input -->
        <div class="tw-flex tw-flex-col tw-gap-1">
          <label class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">
            Quantity
          </label>
          <InputNumber
            v-model="internalDefaultQuantity"
            :min="1"
            :max="9999"
            :inputClass="'tw-w-full tw-text-center tw-font-medium'"
            class="tw-h-9"
            :inputStyle="{
              border: '1px solid #e2e8f0',
              borderRadius: '6px',
              backgroundColor: 'white',
              boxShadow: '0 1px 2px rgba(0, 0, 0, 0.05)'
            }"
            @input="updateDefaults"
          />
        </div>

        <!-- Price Input -->
        <div class="tw-flex tw-flex-col tw-gap-1">
          <label class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">
            Purchasing Price
          </label>
          <InputNumber
            v-model="internalDefaultPrice"
            :min="0"
            :max="999999"
            :inputClass="'tw-w-full tw-text-center tw-font-medium'"
            class="tw-h-9"
            mode="decimal"
            :minFractionDigits="2"
            :maxFractionDigits="2"
            :inputStyle="{
              border: '1px solid #e2e8f0',
              borderRadius: '6px',
              backgroundColor: 'white',
              boxShadow: '0 1px 2px rgba(0, 0, 0, 0.05)'
            }"
            @input="updateDefaults"
          />
        </div>

        <!-- Unit Dropdown -->
        <div class="tw-flex tw-flex-col tw-gap-1">
          <label class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">
            Unit
          </label>
          <Dropdown
            v-model="internalDefaultUnit"
            :options="unitOptions"
            optionLabel="label"
            optionValue="value"
            :inputClass="'tw-w-full tw-font-medium'"
            class="tw-h-9"
            :style="{
              border: '1px solid #e2e8f0',
              borderRadius: '6px',
              backgroundColor: 'white',
              boxShadow: '0 1px 2px rgba(0, 0, 0, 0.05)'
            }"
            @change="updateDefaults"
          />
        </div>
      </div>

      <!-- Helper Text -->
      <div class="tw-mt-2 tw-text-xs tw-text-blue-600 tw-flex tw-items-center tw-gap-1">
        <i class="pi pi-info-circle tw-text-xs"></i>
        <span>These values will be applied to newly selected products</span>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick ,reactive} from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Badge from 'primevue/badge'

const toast = useToast()

// Props
const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  scrollHeight: {
    type: String,
    default: '400px'
  },
  perPage: {
    type: Number,
    default: 20
  },
  showSourceFilter: {
    type: Boolean,
    default: true
  },
  showStock: {
    type: Boolean,
    default: true
  },
  showSelectAll: {
    type: Boolean,
    default: true
  },
  selectable: {
    type: Boolean,
    default: true
  },
  defaultTab: {
    type: String,
    default: 'stock', // 'stock' or 'pharmacy'
    validator: (value) => ['stock', 'pharmacy'].includes(value)
  },
  categoryOptions: {
    type: Array,
    default: () => [
      { label: 'Medical Supplies', value: 'Medical Supplies' },
      { label: 'Equipment', value: 'Equipment' },
      { label: 'Medication', value: 'Medication' },
      { label: 'Consumables', value: 'Consumables' },
      { label: 'Laboratory', value: 'Laboratory' },
      { label: 'Others', value: 'Others' }
    ]
  },
  defaultQuantity: {
    type: Number,
    default: 1
  },
  defaultPrice: {
    type: Number,
    default: 0
  },
  defaultUnit: {
    type: String,
    default: 'unit'
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'selection-change', 'defaults-change'])

// Core state
const activeTabIndex = ref(props.defaultTab === 'pharmacy' ? 1 : 0)
const searchQuery = ref('')
const selectedCategory = ref(null)
const stockDataTableRef = ref(null)
const pharmacyDataTableRef = ref(null)
const stockScrollElement = ref(null)
const pharmacyScrollElement = ref(null)
let searchDebounceTimer = null

// Default settings state
const internalDefaultQuantity = ref(props.defaultQuantity)
const internalDefaultPrice = ref(props.defaultPrice)
const internalDefaultUnit = ref(props.defaultUnit)

const unitOptions = [
  { label: 'Unit', value: 'unit' },
  { label: 'Box', value: 'box' }
]

// Watch props to sync with internal state
watch(() => props.defaultQuantity, (newVal) => {
  internalDefaultQuantity.value = newVal
})

watch(() => props.defaultPrice, (newVal) => {
  internalDefaultPrice.value = newVal
})

watch(() => props.defaultUnit, (newVal) => {
  internalDefaultUnit.value = newVal
})

// Product collections
const stockProducts = ref([])
const pharmacyProducts = ref([])

// Selection management
const selectedStockProducts = ref([])
const selectedPharmacyProducts = ref([])

// Pagination + loading state
const stockPage = ref(1)
const pharmacyPage = ref(1)
const stockHasMore = ref(true)
const pharmacyHasMore = ref(true)
const loadingStock = ref(false)
const loadingPharmacy = ref(false)

// v-model bridge
const internalSelectedProducts = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
    emit('selection-change', value)
  }
})

// Keep combined selection in sync with tab selections
watch([selectedStockProducts, selectedPharmacyProducts], () => {
  // Apply defaults to each selected product before emitting
  // Always use the default settings from the modal, not product properties
  const processedStock = selectedStockProducts.value.map(product => ({
    ...product,
    quantity: internalDefaultQuantity.value,
    price: internalDefaultPrice.value,
    purchaseUnit: internalDefaultUnit.value
  }))

  const processedPharmacy = selectedPharmacyProducts.value.map(product => ({
    ...product,
    quantity: internalDefaultQuantity.value,
    price: internalDefaultPrice.value,
    purchaseUnit: internalDefaultUnit.value
  }))

  const combined = [...processedStock, ...processedPharmacy]
  
  // Debug logging
  console.log('🔧 ProductSelector - Applying defaults:', {
    quantity: internalDefaultQuantity.value,
    price: internalDefaultPrice.value,
    unit: internalDefaultUnit.value,
    productsCount: combined.length
  })
  
  internalSelectedProducts.value = combined
}, { deep: true })

const attachScrollListeners = async () => {
  await nextTick()
  detachScrollListeners()

  const stockWrapper = stockDataTableRef.value?.$el?.querySelector('.p-datatable-wrapper')
  if (stockWrapper) {
    stockScrollElement.value = stockWrapper
    stockWrapper.addEventListener('scroll', handleStockScroll)
  }

  const pharmacyWrapper = pharmacyDataTableRef.value?.$el?.querySelector('.p-datatable-wrapper')
  if (pharmacyWrapper) {
    pharmacyScrollElement.value = pharmacyWrapper
    pharmacyWrapper.addEventListener('scroll', handlePharmacyScroll)
  }
}

const detachScrollListeners = () => {
  if (stockScrollElement.value) {
    stockScrollElement.value.removeEventListener('scroll', handleStockScroll)
    stockScrollElement.value = null
  }

  if (pharmacyScrollElement.value) {
    pharmacyScrollElement.value.removeEventListener('scroll', handlePharmacyScroll)
    pharmacyScrollElement.value = null
  }
}

const fetchProducts = async (reset = true) => {
  if (reset) {
    stockPage.value = 1
    pharmacyPage.value = 1
    stockHasMore.value = true
    pharmacyHasMore.value = true
    stockProducts.value = []
    pharmacyProducts.value = []
  }

  await Promise.all([
    fetchStockProducts(reset),
    fetchPharmacyProducts(reset)
  ])

  await attachScrollListeners()
}

const fetchStockProducts = async (reset = true) => {
  if (loadingStock.value) return
  if (!reset && !stockHasMore.value) return

  try {
    loadingStock.value = true
    const page = stockPage.value

    const params = {
      page,
      per_page: props.perPage,
      search: searchQuery.value,
      category: selectedCategory.value
    }

    const response = await axios.get('/api/products', { params })
    const mappedProducts = (response.data?.data ?? []).map(product => ({
      ...product,
      source: 'stock',
      is_clinical: false
    }))

    if (reset) {
      stockProducts.value = mappedProducts
    } else {
      const existingIds = new Set(stockProducts.value.map(item => item.id))
      const unique = mappedProducts.filter(item => !existingIds.has(item.id))
      stockProducts.value = [...stockProducts.value, ...unique]
    }

    const pagination = response.data?.pagination
    if (pagination) {
      stockHasMore.value = pagination.current_page < pagination.last_page
      stockPage.value = stockHasMore.value ? pagination.current_page + 1 : pagination.current_page
    } else {
      const hasMore = mappedProducts.length === props.perPage
      stockHasMore.value = hasMore
      stockPage.value = hasMore ? page + 1 : page
    }
  } catch (error) {
    console.error('Error loading stock products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load stock products',
      life: 3000
    })
  } finally {
    loadingStock.value = false
  }
}

const fetchPharmacyProducts = async (reset = true) => {
  if (loadingPharmacy.value) return
  if (!reset && !pharmacyHasMore.value) return

  try {
    loadingPharmacy.value = true
    const page = pharmacyPage.value

    const params = {
      page,
      per_page: props.perPage,
      search: searchQuery.value,
      category: selectedCategory.value
    }

    const response = await axios.get('/api/pharmacy/products', { params })
    const mappedProducts = (response.data?.data ?? []).map(product => ({
      ...product,
      source: 'pharmacy',
      is_clinical: true
    }))

    if (reset) {
      pharmacyProducts.value = mappedProducts
    } else {
      const existingIds = new Set(pharmacyProducts.value.map(item => item.id))
      const unique = mappedProducts.filter(item => !existingIds.has(item.id))
      pharmacyProducts.value = [...pharmacyProducts.value, ...unique]
    }

    const pagination = response.data?.pagination
    if (pagination) {
      pharmacyHasMore.value = pagination.current_page < pagination.last_page
      pharmacyPage.value = pharmacyHasMore.value ? pagination.current_page + 1 : pagination.current_page
    } else {
      const hasMore = mappedProducts.length === props.perPage
      pharmacyHasMore.value = hasMore
      pharmacyPage.value = hasMore ? page + 1 : page
    }
  } catch (error) {
    console.error('Error loading pharmacy products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pharmacy products',
      life: 3000
    })
  } finally {
    loadingPharmacy.value = false
  }
}

const shouldLoadMore = (element) => {
  if (!element) return false
  if (element.scrollHeight <= element.clientHeight) return false

  const remaining = element.scrollHeight - element.scrollTop - element.clientHeight
  if (remaining < 120) return true

  const scrolledPercentage = ((element.scrollTop + element.clientHeight) / element.scrollHeight) * 100
  return scrolledPercentage >= 90
}

const handleStockScroll = (event) => {
  if (shouldLoadMore(event.target) && stockHasMore.value && !loadingStock.value) {
    fetchStockProducts(false)
  }
}

const handlePharmacyScroll = (event) => {
  if (shouldLoadMore(event.target) && pharmacyHasMore.value && !loadingPharmacy.value) {
    fetchPharmacyProducts(false)
  }
}

const handleSearch = () => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  searchDebounceTimer = setTimeout(() => {
    fetchProducts(true)
  }, 500)
}

const handleCategoryChange = () => {
  fetchProducts(true)
}

const handleTabChange = async () => {
  await attachScrollListeners()
}

const updateDefaults = () => {
  // Emit change event when defaults are updated
  emit('defaults-change', {
    quantity: internalDefaultQuantity.value,
    price: internalDefaultPrice.value,
    unit: internalDefaultUnit.value
  })
}

const selectAllVisible = () => {
  if (activeTabIndex.value === 0) {
    selectedStockProducts.value = [...stockProducts.value]
  } else {
    selectedPharmacyProducts.value = [...pharmacyProducts.value]
  }
}

const refresh = () => {
  fetchProducts(true)
}

const clearSelection = () => {
  selectedStockProducts.value = []
  selectedPharmacyProducts.value = []
}

const getSelectedProducts = () => internalSelectedProducts.value

onMounted(async () => {
  await fetchProducts(true)
})

watch(activeTabIndex, async () => {
  await attachScrollListeners()
})

onBeforeUnmount(() => {
  detachScrollListeners()
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }
})

defineExpose({
  refresh,
  clearSelection,
  getSelectedProducts,
  fetchProducts
})
</script>

<style scoped>
@reference "../../../../../css/app.css";

/* DataTable enhancements */
:deep(.p-datatable) {
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
}

:deep(.p-datatable .p-datatable-tbody > tr) {
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
}

/* Scrollbar styling */
:deep(.p-datatable-wrapper) {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 #f7fafc;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar) {
  width: 8px;
  height: 8px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-track) {
  background: #f7fafc;
  border-radius: 4px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb) {
  background: #cbd5e0;
  border-radius: 4px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb:hover) {
  background: #a0aec0;
}
</style>
