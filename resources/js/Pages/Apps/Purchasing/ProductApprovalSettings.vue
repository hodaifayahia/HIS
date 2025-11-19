<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">Product Approval Settings</h1>
          <p class="tw-text-gray-600 tw-mt-1">Configure which products require approval for purchase orders</p>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button 
            v-if="modifiedProducts.size > 0"
            @click="saveAllChanges" 
            :label="`Save ${modifiedProducts.size} Change${modifiedProducts.size > 1 ? 's' : ''}`"
            icon="pi pi-save" 
            :loading="saving"
            class="p-button-success"
          />
          <Button 
            @click="refreshData" 
            label="Refresh" 
            icon="pi pi-refresh" 
            :loading="loading"
            class="p-button-outlined"
          />
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-6">
      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-blue-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Total Products</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ paginationMeta.total || 0 }}</p>
          </div>
          <div class="tw-bg-blue-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-box tw-text-2xl tw-text-blue-600"></i>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-purple-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Current Page</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ products.length }}</p>
          </div>
          <div class="tw-bg-purple-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-list tw-text-2xl tw-text-purple-600"></i>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-yellow-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Require Approval</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ statistics.requiresApproval || 0 }}</p>
          </div>
          <div class="tw-bg-yellow-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-exclamation-triangle tw-text-2xl tw-text-yellow-600"></i>
          </div>
        </div>
      </div>

      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-green-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">No Approval Needed</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ statistics.noApproval || 0 }}</p>
          </div>
          <div class="tw-bg-green-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-check-circle tw-text-2xl tw-text-green-600"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs and Filters -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-mb-6">
      <!-- Tab Navigation -->
      <div class="tw-border-b tw-border-gray-200">
        <nav class="tw-flex tw-gap-4 tw-px-6" aria-label="Product Tabs">
          <button
            v-for="tab in productTabs"
            :key="tab.value"
            @click="selectTab(tab.value)"
            :class="[
              'tw-py-4 tw-px-1 tw-border-b-2 tw-font-medium tw-text-sm tw-transition-colors tw-flex tw-items-center tw-gap-2',
              activeTab === tab.value
                ? 'tw-border-blue-500 tw-text-blue-600'
                : 'tw-border-transparent tw-text-gray-500 hover:tw-text-gray-700 hover:tw-border-gray-300'
            ]"
          >
            <i :class="tab.icon"></i>
            {{ tab.label }}
            <Badge 
              v-if="tab.count !== undefined" 
              :value="tab.count" 
              :severity="activeTab === tab.value ? 'info' : 'secondary'"
            />
          </button>
        </nav>
      </div>

      <!-- Filters -->
      <div class="tw-p-4 tw-bg-gray-50">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4">
          <div class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
            <div class="p-inputgroup">
              <span class="p-inputgroup-addon"><i class="pi pi-search"></i></span>
              <InputText 
                v-model="filters.search" 
                placeholder="Search by name, code, or designation..."
                class="tw-w-full"
                @keyup.enter="applyFilters"
              />
            </div>
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Approval Status</label>
            <Dropdown 
              v-model="filters.approvalStatus" 
              :options="approvalStatusOptions" 
              optionLabel="label" 
              optionValue="value"
              placeholder="All Products"
              class="tw-w-full"
              @change="applyFilters"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Items per page</label>
            <Dropdown 
              v-model="perPage" 
              :options="perPageOptions" 
              class="tw-w-full"
              @change="changePerPage"
            />
          </div>
          <div class="tw-flex tw-items-end tw-gap-2">
            <Button 
              @click="applyFilters" 
              label="Apply" 
              icon="pi pi-filter" 
              class="p-button-primary tw-flex-1"
            />
            <Button 
              @click="resetFilters" 
              icon="pi pi-filter-slash" 
              class="p-button-outlined"
              v-tooltip.top="'Reset Filters'"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-overflow-hidden">
      <DataTable 
        :value="products" 
        :loading="loading"
        stripedRows
        :lazy="true"
        responsiveLayout="scroll"
        class="tw-w-full"
        :rowClass="rowClass"
      >
        <template #empty>
          <div class="tw-text-center tw-py-12">
            <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
            <p class="tw-text-gray-500 tw-text-lg tw-font-medium">No products found</p>
            <p class="tw-text-gray-400 tw-text-sm tw-mt-2">Try adjusting your filters or search criteria</p>
          </div>
        </template>

        <Column field="id" header="ID" style="width: 70px">
          <template #body="slotProps">
            <span class="tw-text-sm tw-text-gray-600">#{{ slotProps.data.id }}</span>
          </template>
        </Column>

        <Column header="Source" style="width: 100px">
          <template #body="slotProps">
            <Tag 
              :value="slotProps.data.source_type === 'pharmacy' ? 'Pharmacy' : 'Stock'" 
              :severity="slotProps.data.source_type === 'pharmacy' ? 'info' : 'secondary'"
              :icon="slotProps.data.source_type === 'pharmacy' ? 'pi pi-heart-fill' : 'pi pi-box'"
            />
          </template>
        </Column>

        <Column field="code" header="Product Code" style="min-width: 150px">
          <template #body="slotProps">
            <span class="tw-font-mono tw-text-sm tw-text-gray-700 tw-font-medium">
              {{ getProductCode(slotProps.data) || 'N/A' }}
            </span>
          </template>
        </Column>

        <Column field="name" header="Product Name" style="min-width: 250px">
          <template #body="slotProps">
            <div>
              <div class="tw-font-semibold tw-text-gray-900">{{ getProductName(slotProps.data) }}</div>
              <div v-if="slotProps.data.category || slotProps.data.type_medicament" class="tw-flex tw-gap-2 tw-mt-1">
                <Tag 
                  v-if="slotProps.data.category" 
                  :value="slotProps.data.category" 
                  severity="secondary"
                  class="tw-text-xs"
                />
                <Tag 
                  v-if="slotProps.data.type_medicament" 
                  :value="slotProps.data.type_medicament" 
                  severity="info"
                  class="tw-text-xs"
                />
              </div>
              <div v-if="slotProps.data.designation" class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-truncate tw-max-w-md">
                {{ slotProps.data.designation }}
              </div>
            </div>
          </template>
        </Column>

        <Column field="is_required_approval" header="Requires Approval" style="width: 220px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-3">
              <InputSwitch 
                v-model="slotProps.data.is_required_approval" 
                @change="toggleApprovalStatus(slotProps.data)"
              />
              <Tag 
                :value="slotProps.data.is_required_approval ? 'Required' : 'Optional'" 
                :severity="slotProps.data.is_required_approval ? 'warning' : 'success'"
              />
            </div>
          </template>
        </Column>

        <Column header="Actions" style="width: 120px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button 
                @click="viewProductDetails(slotProps.data)" 
                icon="pi pi-eye" 
                class="p-button-rounded p-button-text p-button-info p-button-sm"
                v-tooltip.top="'View Details'"
              />
              <Button 
                v-if="slotProps.data._modified"
                @click="saveProduct(slotProps.data)" 
                icon="pi pi-save" 
                class="p-button-rounded p-button-text p-button-success p-button-sm"
                v-tooltip.top="'Save Changes'"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <!-- Custom Pagination -->
      <div class="tw-p-4 tw-border-t tw-border-gray-200 tw-bg-gray-50">
        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
          <div class="tw-text-sm tw-text-gray-600">
            Showing 
            <span class="tw-font-semibold">{{ paginationMeta.from || 0 }}</span> 
            to 
            <span class="tw-font-semibold">{{ paginationMeta.to || 0 }}</span> 
            of 
            <span class="tw-font-semibold">{{ paginationMeta.total || 0 }}</span> 
            products
          </div>
          <Paginator
            v-model:first="first"
            :rows="perPage"
            :totalRecords="paginationMeta.total || 0"
            :rowsPerPageOptions="perPageOptions"
            @page="onPageChange"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
          />
        </div>
      </div>
    </div>

    <!-- Product Details Dialog -->
    <Dialog 
      :visible="detailsDialog"
      @update:visible="detailsDialog = $event"
      header="Product Details"
      :modal="true"
      :style="{ width: '50rem' }"
      :breakpoints="{ '960px': '75vw', '640px': '90vw' }"
    >
      <div v-if="selectedProduct" class="tw-space-y-4">
        <!-- Source Badge -->
        <div class="tw-flex tw-justify-between tw-items-start">
          <Tag 
            :value="selectedProduct.source_type === 'pharmacy' ? 'Pharmacy Product' : 'Stock Product'" 
            :severity="selectedProduct.source_type === 'pharmacy' ? 'info' : 'secondary'"
            :icon="selectedProduct.source_type === 'pharmacy' ? 'pi pi-heart-fill' : 'pi pi-box'"
            class="tw-text-base"
          />
          <Tag 
            :value="selectedProduct.is_required_approval ? 'Requires Approval' : 'No Approval Needed'" 
            :severity="selectedProduct.is_required_approval ? 'warning' : 'success'"
          />
        </div>

        <Divider />

        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Product Code</label>
            <p class="tw-text-gray-900 tw-font-mono">{{ getProductCode(selectedProduct) || 'N/A' }}</p>
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Product Name</label>
            <p class="tw-text-gray-900 tw-font-semibold">{{ getProductName(selectedProduct) }}</p>
          </div>
          <div v-if="selectedProduct.category">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Category</label>
            <Tag :value="selectedProduct.category" severity="secondary" />
          </div>
          <div v-if="selectedProduct.type_medicament">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Medication Type</label>
            <Tag :value="selectedProduct.type_medicament" severity="info" />
          </div>
          <div v-if="selectedProduct.forme">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Form</label>
            <p class="tw-text-gray-900">{{ selectedProduct.forme }}</p>
          </div>
          <div v-if="selectedProduct.dosage">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Dosage</label>
            <p class="tw-text-gray-900">{{ selectedProduct.dosage }}</p>
          </div>
        </div>
        
        <div v-if="selectedProduct.designation || selectedProduct.description">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Description</label>
          <p class="tw-text-gray-900 tw-text-sm">{{ selectedProduct.designation || selectedProduct.description }}</p>
        </div>

        <div class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4">
          <div class="tw-flex tw-items-start tw-gap-3">
            <i class="pi pi-info-circle tw-text-blue-600 tw-text-xl tw-mt-1"></i>
            <div>
              <h4 class="tw-font-semibold tw-text-gray-900 tw-mb-1">Approval Requirement</h4>
              <p class="tw-text-sm tw-text-gray-700">
                {{ selectedProduct.is_required_approval 
                  ? 'This product requires approval regardless of order amount. Any purchase order containing this product will need to be approved before confirmation.'
                  : 'This product does not require special approval. It will only need approval if the total order amount exceeds the threshold.' 
                }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button 
          @click="detailsDialog = false" 
          label="Close" 
          icon="pi pi-times" 
          class="p-button-text"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputSwitch from 'primevue/inputswitch'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Paginator from 'primevue/paginator'
import Divider from 'primevue/divider'

const toast = useToast()

// Reactive state
const loading = ref(true)
const saving = ref(false)
const products = ref([])
const selectedProduct = ref(null)
const detailsDialog = ref(false)
const modifiedProducts = ref(new Set())
const activeTab = ref('all')
const first = ref(0)
const perPage = ref(20)

// Pagination metadata from backend
const paginationMeta = ref({
  total: 0,
  per_page: 20,
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0
})

// Filters
const filters = reactive({
  approvalStatus: null,
  search: ''
})

const approvalStatusOptions = [
  { label: 'All Products', value: null },
  { label: 'Requires Approval', value: true },
  { label: 'No Approval Needed', value: false }
]

const perPageOptions = [10, 20, 50, 100]

// Product tabs
const productTabs = computed(() => [
  { 
    value: 'all', 
    label: 'All Products', 
    icon: 'pi pi-th-large',
    count: paginationMeta.value.total
  },
  { 
    value: 'pharmacy', 
    label: 'Pharmacy Products', 
    icon: 'pi pi-heart-fill',
    count: products.value.filter(p => p.source_type === 'pharmacy').length
  },
  { 
    value: 'stock', 
    label: 'Stock Products', 
    icon: 'pi pi-box',
    count: products.value.filter(p => p.source_type === 'stock').length
  }
])

// Statistics
const statistics = computed(() => {
  return {
    total: paginationMeta.value.total,
    requiresApproval: products.value.filter(p => p.is_required_approval).length,
    noApproval: products.value.filter(p => !p.is_required_approval).length
  }
})

// Helper methods for product data
const getProductCode = (product) => {
  if (!product) return 'N/A'
  return product.code || product.code_interne || product.product_code || 'N/A'
}

const getProductName = (product) => {
  if (!product) return 'Unknown Product'
  return product.name || product.product_name || product.nom_commercial || 'Unknown Product'
}

const rowClass = (data) => {
  return data._modified ? 'tw-bg-yellow-50' : ''
}

// Methods
const fetchProducts = async (page = 1) => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    
    params.append('page', page)
    params.append('per_page', perPage.value)
    
    if (filters.approvalStatus !== null) {
      params.append('is_required_approval', filters.approvalStatus)
    }
    if (filters.search) {
      params.append('search', filters.search)
    }

    // Fetch both pharmacy and stock products
    const [pharmacyResponse, stockResponse] = await Promise.all([
      axios.get(`/api/pharmacy/products?${params.toString()}`),
      axios.get(`/api/products?${params.toString()}`)
    ])

    // Process pharmacy products
    const pharmacyProducts = (pharmacyResponse.data.data || pharmacyResponse.data || []).map(p => ({
      ...p,
      source_type: 'pharmacy',
      _modified: false
    }))

    // Process stock products  
    const stockProducts = (stockResponse.data.data || stockResponse.data || []).map(p => ({
      ...p,
      source_type: 'stock',
      _modified: false
    }))

    // Combine and filter based on active tab
    let combinedProducts = []
    if (activeTab.value === 'all') {
      combinedProducts = [...pharmacyProducts, ...stockProducts]
    } else if (activeTab.value === 'pharmacy') {
      combinedProducts = pharmacyProducts
    } else if (activeTab.value === 'stock') {
      combinedProducts = stockProducts
    }

    products.value = combinedProducts

    // Update pagination metadata (use the larger of the two)
    const pharmacyMeta = pharmacyResponse.data.meta || {}
    const stockMeta = stockResponse.data.meta || {}
    
    paginationMeta.value = {
      total: (pharmacyMeta.total || 0) + (stockMeta.total || 0),
      per_page: perPage.value,
      current_page: page,
      last_page: Math.max(pharmacyMeta.last_page || 1, stockMeta.last_page || 1),
      from: ((page - 1) * perPage.value) + 1,
      to: Math.min(page * perPage.value, (pharmacyMeta.total || 0) + (stockMeta.total || 0))
    }

  } catch (err) {
    console.error('Error fetching products:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch products',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const selectTab = (tab) => {
  activeTab.value = tab
  first.value = 0
  fetchProducts(1)
}

const toggleApprovalStatus = (product) => {
  product._modified = true
  modifiedProducts.value.add(`${product.source_type}-${product.id}`)
  
  toast.add({
    severity: 'info',
    summary: 'Changed',
    detail: `${getProductName(product)} marked as ${product.is_required_approval ? 'requiring' : 'not requiring'} approval`,
    life: 2000
  })
}

const saveProduct = async (product) => {
  try {
    const endpoint = product.source_type === 'pharmacy' 
      ? `/api/pharmacy/products/${product.id}`
      : `/api/products/${product.id}`
    
    const response = await axios.put(endpoint, {
      is_required_approval: product.is_required_approval
    })

    if (response.data.success || response.data.id) {
      product._modified = false
      modifiedProducts.value.delete(`${product.source_type}-${product.id}`)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product approval setting saved',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error saving product:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save product',
      life: 3000
    })
  }
}

const saveAllChanges = async () => {
  if (modifiedProducts.value.size === 0) {
    toast.add({
      severity: 'info',
      summary: 'No Changes',
      detail: 'No products have been modified',
      life: 3000
    })
    return
  }

  try {
    saving.value = true
    
    // Group products by source type
    const pharmacyProducts = products.value
      .filter(p => p.source_type === 'pharmacy' && modifiedProducts.value.has(`pharmacy-${p.id}`))
      .map(p => ({
        id: p.id,
        is_required_approval: p.is_required_approval
      }))
    
    const stockProducts = products.value
      .filter(p => p.source_type === 'stock' && modifiedProducts.value.has(`stock-${p.id}`))
      .map(p => ({
        id: p.id,
        is_required_approval: p.is_required_approval
      }))
    
    // Save in parallel
    const promises = []
    
    if (pharmacyProducts.length > 0) {
      promises.push(
        axios.post('/api/pharmacy/products/bulk-update-approval', {
          products: pharmacyProducts
        })
      )
    }
    
    if (stockProducts.length > 0) {
      promises.push(
        axios.post('/api/products/bulk-update-approval', {
          products: stockProducts
        })
      )
    }
    
    const responses = await Promise.all(promises)
    
    // Clear modified flags
    products.value.forEach(p => {
      if (modifiedProducts.value.has(`${p.source_type}-${p.id}`)) {
        p._modified = false
      }
    })
    
    const totalSaved = pharmacyProducts.length + stockProducts.length
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Saved ${totalSaved} product(s) successfully`,
      life: 3000
    })
    
    modifiedProducts.value.clear()
  } catch (err) {
    console.error('Error saving all changes:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save all changes',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const viewProductDetails = (product) => {
  selectedProduct.value = product
  detailsDialog.value = true
}

const applyFilters = async () => {
  first.value = 0
  await fetchProducts(1)
}

const resetFilters = () => {
  filters.approvalStatus = null
  filters.search = ''
  first.value = 0
  fetchProducts(1)
}

const refreshData = () => {
  const currentPage = Math.floor(first.value / perPage.value) + 1
  fetchProducts(currentPage)
}

const changePerPage = () => {
  first.value = 0
  fetchProducts(1)
}

const onPageChange = (event) => {
  first.value = event.first
  const page = Math.floor(event.first / perPage.value) + 1
  fetchProducts(page)
}

// Lifecycle
onMounted(async () => {
  await fetchProducts(1)
})

// Watch for tab changes
watch(activeTab, () => {
  // Tab change handled in selectTab method
})
</script>
