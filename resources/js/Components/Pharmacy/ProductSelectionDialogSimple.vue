<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Select Pharmacy Products"
    :modal="true"
    class="tw-w-[90vw] md:tw-w-[900px]"
    :maximizable="true"
  >
    <!-- Search and Filter Controls -->
    <div class="tw-flex tw-flex-wrap tw-gap-3 tw-mb-4">
      <div class="tw-flex-1 tw-min-w-[250px]">
        <InputText
          v-model="search"
          placeholder="Search products by name or code..."
          class="tw-w-full"
          @input="handleSearch"
        />
      </div>

      <Button
        @click="refreshProducts"
        icon="pi pi-refresh"
        class="p-button-outlined p-button-secondary"
        v-tooltip.top="'Refresh'"
      />
    </div>

    <!-- Products DataTable with Infinite Scroll -->
    <div class="tw-relative tw-border tw-border-slate-200 tw-rounded-lg tw-overflow-hidden">
      <DataTable
        ref="dataTableRef"
        :value="products"
        v-model:selection="selectedProducts"
        :loading="loading"
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
          <div class="tw-text-center tw-py-12 tw-text-gray-500">
            <i class="pi pi-inbox tw-text-5xl tw-mb-3"></i>
            <p class="tw-font-medium">No pharmacy products found</p>
            <p class="tw-text-xs tw-text-gray-400 tw-mt-2">Try adjusting your search</p>
          </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem"></Column>

        <Column field="name" header="Product Name" :sortable="false">
          <template #body="slotProps">
            <div>
              <div class="tw-font-semibold tw-text-sm tw-text-slate-900">
                {{ slotProps.data.name || slotProps.data.designation }}
              </div>
              <div v-if="slotProps.data.code" class="tw-text-xs tw-text-gray-500 tw-mt-1">
                Code: <span class="tw-font-mono tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">{{ slotProps.data.code }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="unit_of_measure" header="Unit" style="width: 120px">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.unit_of_measure || 'Box'"
              severity="info"
              style="font-size: 0.75rem"
            />
          </template>
        </Column>

        <Column field="quantity_by_box" header="Qty/Box" style="width: 100px">
          <template #body="slotProps">
            <span class="tw-font-medium tw-text-slate-900">
              {{ slotProps.data.quantity_by_box || '-' }}
            </span>
          </template>
        </Column>

        <Column header="Status" style="width: 100px">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.is_active ? 'Active' : 'Inactive'"
              :severity="slotProps.data.is_active ? 'success' : 'danger'"
              style="font-size: 0.75rem"
            />
          </template>
        </Column>
      </DataTable>

      <!-- Loading indicator at bottom -->
      <div
        v-if="loading && products.length > 0"
        class="tw-flex tw-justify-center tw-items-center tw-py-4 tw-bg-blue-50 tw-border-t tw-border-blue-200"
      >
        <i class="pi pi-spin pi-spinner tw-text-blue-600 tw-mr-2"></i>
        <span class="tw-text-sm tw-text-blue-600">Loading more products...</span>
      </div>

      <!-- End of list indicator -->
      <div
        v-if="!hasMore && products.length > 0"
        class="tw-flex tw-justify-center tw-items-center tw-py-4 tw-bg-green-50 tw-border-t tw-border-green-200"
      >
        <span class="tw-text-sm tw-text-green-700">
          <i class="pi pi-check tw-mr-2"></i>All {{ products.length }} pharmacy products loaded
        </span>
      </div>
    </div>

    <!-- Default Quantity and Unit Settings -->
    <div class="tw-mt-6 tw-p-4 tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg">
      <div class="tw-mb-3">
        <p class="tw-text-sm tw-font-semibold tw-text-blue-900 tw-mb-3">
          <i class="pi pi-info-circle tw-mr-2"></i>
          Default Settings for Selected Products
        </p>
      </div>

      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
        <!-- Default Quantity -->
        <div>
          <label class="tw-block tw-text-xs tw-font-medium tw-text-slate-700 tw-mb-2">
            <i class="pi pi-hashtag tw-mr-1"></i>
            Default Quantity
          </label>
          <InputNumber
            v-model="defaultQuantity"
            :min="1"
            :step="1"
            class="tw-w-full"
            placeholder="Enter quantity"
          />
        </div>

        <!-- Default Unit -->
        <div>
          <label class="tw-block tw-text-xs tw-font-medium tw-text-slate-700 tw-mb-2">
            <i class="pi pi-tag tw-mr-1"></i>
            Default Unit
          </label>
          <Dropdown
            v-model="defaultUnit"
            :options="unitOptions"
            optionLabel="label"
            optionValue="value"
            class="tw-w-full"
            placeholder="Select unit"
          />
        </div>
      </div>

      <p class="tw-text-xs tw-text-blue-700 tw-mt-3">
        <i class="pi pi-info-circle tw-mr-1"></i>
        These settings will apply to all {{ selectedProducts.length }} selected product(s)
      </p>
    </div>

    <!-- Selected count -->
    <div class="tw-mt-4 tw-text-sm tw-text-slate-600">
      <i class="pi pi-check-circle tw-mr-2 tw-text-green-600"></i>
      <span class="tw-font-medium">{{ selectedProducts.length }} product(s) selected</span>
    </div>

    <!-- Footer Actions -->
    <template #footer>
      <Button
        label="Cancel"
        icon="pi pi-times"
        class="p-button-text"
        @click="cancel"
      />
      <Button
        label="Add Selected"
        icon="pi pi-check"
        class="p-button-success"
        :disabled="selectedProducts.length === 0"
        @click="confirmSelection"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'

const toast = useToast()

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  scrollHeight: {
    type: String,
    default: '500px'
  },
  perPage: {
    type: Number,
    default: 25
  }
})

// Emits
const emit = defineEmits(['update:visible', 'products-selected'])

// State
const dataTableRef = ref(null)
const products = ref([])
const selectedProducts = ref([])
const loading = ref(false)
const search = ref('')
const currentPage = ref(1)
const hasMore = ref(true)
const defaultQuantity = ref(1)
const defaultUnit = ref('box')
const unitOptions = ref([
  { label: 'Box', value: 'box' },
  { label: 'Unit', value: 'unit' },
])
let searchDebounceTimer = null
let scrollListener = null

// Load pharmacy products with infinite scroll
const loadPharmacyProducts = async (reset = true) => {
  if (loading.value && !reset) return
  if (!reset && !hasMore.value) return

  try {
    loading.value = true
    const page = reset ? 1 : currentPage.value

    const params = {
      page,
      per_page: props.perPage,
      search: search.value,
    }

    const response = await axios.get('/api/pharmacy/products', { params })
    const newProducts = response.data.data || []

    if (reset) {
      products.value = newProducts
      currentPage.value = 1
    } else {
      // Avoid duplicates
      const existingIds = new Set(products.value.map(p => p.id))
      const uniqueProducts = newProducts.filter(p => !existingIds.has(p.id))
      products.value = [...products.value, ...uniqueProducts]
    }

    // Check if there are more pages
    const pagination = response.data.meta?.pagination || response.data.pagination
    if (pagination) {
      hasMore.value = pagination.current_page < pagination.last_page
      currentPage.value = pagination.current_page + 1
    } else {
      // Fallback: if we got fewer items than requested, we're at the end
      hasMore.value = newProducts.length === props.perPage
      currentPage.value = page + 1
    }

    // Attach scroll listener after products are loaded
    await nextTick()
    attachScrollListener()
  } catch (error) {
    console.error('Error loading pharmacy products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pharmacy products',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Attach scroll listener to DataTable wrapper
const attachScrollListener = async () => {
  await nextTick()
  detachScrollListener()

  const wrapper = dataTableRef.value?.$el?.querySelector('.p-datatable-wrapper')
  if (wrapper) {
    scrollListener = wrapper
    wrapper.addEventListener('scroll', handleTableScroll)
  }
}

// Detach scroll listener
const detachScrollListener = () => {
  if (scrollListener) {
    scrollListener.removeEventListener('scroll', handleTableScroll)
    scrollListener = null
  }
}

// Handle table scroll for infinite scroll
const handleTableScroll = (event) => {
  const element = event.target
  if (!element) return

  // Calculate how far scrolled
  const scrollTop = element.scrollTop
  const scrollHeight = element.scrollHeight
  const clientHeight = element.clientHeight

  // Trigger load when near bottom (within 200px)
  if (scrollHeight - scrollTop - clientHeight < 200) {
    if (!loading.value && hasMore.value) {
      loadPharmacyProducts(false)
    }
  }
}

// Handle search with debounce
const handleSearch = () => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  searchDebounceTimer = setTimeout(() => {
    currentPage.value = 1
    hasMore.value = true
    selectedProducts.value = [] // Clear selection on search
    loadPharmacyProducts(true)
  }, 500)
}

// Refresh products
const refreshProducts = () => {
  currentPage.value = 1
  hasMore.value = true
  selectedProducts.value = []
  search.value = ''
  loadPharmacyProducts(true)
}

// Confirm selection and emit
const confirmSelection = () => {
  if (selectedProducts.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select at least one product',
      life: 3000
    })
    return
  }

  if (!defaultQuantity.value || defaultQuantity.value < 1) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please enter a valid default quantity (minimum 1)',
      life: 3000
    })
    return
  }

  if (!defaultUnit.value) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select a default unit',
      life: 3000
    })
    return
  }

  const selected = selectedProducts.value.map(product => ({
    id: product.id,
    name: product.name || product.designation,
    code: product.code || product.code_interne,
    unit: defaultUnit.value,
    default_unit: defaultUnit.value,
    quantity: defaultQuantity.value,
    default_quantity: defaultQuantity.value,
    quantity_by_box: product.quantity_by_box || 1,
    product_id: product.id,
  }))

  emit('products-selected', selected)
  cancel()
}

// Cancel and close dialog
const cancel = () => {
  emit('update:visible', false)
  // Reset state when closing
  setTimeout(() => {
    products.value = []
    selectedProducts.value = []
    search.value = ''
    currentPage.value = 1
    hasMore.value = true
    defaultQuantity.value = 1
    defaultUnit.value = 'box'
  }, 300)
}

// Lifecycle
onMounted(() => {
  if (props.visible) {
    loadPharmacyProducts(true)
  }
})

onBeforeUnmount(() => {
  detachScrollListener()
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }
})
</script>

<style scoped>
/* DataTable scrollbar styling */
:deep(.p-datatable-wrapper) {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 #f7fafc;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar) {
  width: 8px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-track) {
  background: #f7fafc;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb) {
  background: #cbd5e0;
  border-radius: 4px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb:hover) {
  background: #a0aec0;
}

:deep(.p-datatable) {
  font-size: 0.875rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  padding: 0.75rem;
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
  font-weight: 600;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #f1f5f9;
}
</style>
