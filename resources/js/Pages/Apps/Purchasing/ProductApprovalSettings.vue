<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">Product Approval Settings</h1>
          <p class="tw-text-gray-600 tw-mt-1">Configure which products require approval for purchase orders</p>
        </div>
        <Button 
          @click="saveAllChanges" 
          label="Save All Changes" 
          icon="pi pi-save" 
          :loading="saving"
          class="p-button-success"
        />
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-6 tw-mb-6">
      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-border-l-4 tw-border-blue-500">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-gray-500 tw-text-sm tw-font-medium">Total Products</p>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mt-1">{{ statistics.total || 0 }}</p>
          </div>
          <div class="tw-bg-blue-100 tw-p-3 tw-rounded-full">
            <i class="pi pi-box tw-text-2xl tw-text-blue-600"></i>
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

    <!-- Filters -->
    <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
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
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
          <InputText 
            v-model="filters.search" 
            placeholder="Search by name or code..."
            class="tw-w-full"
            @keyup.enter="applyFilters"
          />
        </div>
        <div class="tw-flex tw-items-end tw-gap-2">
          <Button 
            @click="applyFilters" 
            label="Filter" 
            icon="pi pi-filter" 
            class="p-button-primary"
          />
          <Button 
            @click="resetFilters" 
            label="Reset" 
            icon="pi pi-filter-slash" 
            class="p-button-secondary"
          />
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-overflow-hidden">
      <DataTable 
        :value="products" 
        :loading="loading"
        stripedRows
        :paginator="true"
        :rows="20"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
        class="tw-w-full"
      >
        <template #empty>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-inbox tw-text-4xl tw-text-gray-400 tw-mb-4"></i>
            <p class="tw-text-gray-500">No products found</p>
          </div>
        </template>

        <Column field="id" header="ID" sortable style="width: 80px" />

        <Column field="product_code" header="Product Code" sortable>
          <template #body="slotProps">
            <span class="tw-font-mono tw-text-sm tw-text-gray-700">{{ slotProps.data.product_code || 'N/A' }}</span>
          </template>
        </Column>

        <Column field="name" header="Product Name" sortable>
          <template #body="slotProps">
            <div>
              <div class="tw-font-semibold tw-text-gray-900">{{ slotProps.data.name || slotProps.data.product_name }}</div>
              <div v-if="slotProps.data.description" class="tw-text-sm tw-text-gray-500 tw-truncate tw-max-w-xs">
                {{ slotProps.data.description }}
              </div>
            </div>
          </template>
        </Column>

        <Column field="price" header="Price" sortable>
          <template #body="slotProps">
            <span class="tw-text-gray-700">{{ formatCurrency(slotProps.data.price) }}</span>
          </template>
        </Column>

        <Column field="is_required_approval" header="Requires Approval" sortable style="width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-3">
              <InputSwitch 
                v-model="slotProps.data.is_required_approval" 
                @change="toggleApprovalStatus(slotProps.data)"
              />
              <Tag 
                :value="slotProps.data.is_required_approval ? 'Required' : 'Not Required'" 
                :severity="slotProps.data.is_required_approval ? 'warning' : 'success'"
              />
            </div>
          </template>
        </Column>

        <Column header="Actions" style="width: 150px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button 
                @click="viewProductDetails(slotProps.data)" 
                icon="pi pi-eye" 
                class="p-button-rounded p-button-text p-button-info"
                v-tooltip.top="'View Details'"
              />
              <Button 
                v-if="slotProps.data._modified"
                @click="saveProduct(slotProps.data)" 
                icon="pi pi-save" 
                class="p-button-rounded p-button-text p-button-success"
                v-tooltip.top="'Save Changes'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Product Details Dialog -->
    <Dialog 
      :visible="detailsDialog"
      @update:visible="detailsDialog = $event"
      header="Product Details"
      :modal="true"
      class="tw-w-full tw-max-w-2xl"
    >
      <div v-if="selectedProduct" class="tw-space-y-4">
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Product Code</label>
            <p class="tw-text-gray-900 tw-font-mono">{{ selectedProduct.product_code || 'N/A' }}</p>
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Product Name</label>
            <p class="tw-text-gray-900">{{ selectedProduct.name || selectedProduct.product_name }}</p>
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Price</label>
            <p class="tw-text-gray-900 tw-font-semibold">{{ formatCurrency(selectedProduct.price) }}</p>
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Approval Status</label>
            <Tag 
              :value="selectedProduct.is_required_approval ? 'Requires Approval' : 'No Approval Needed'" 
              :severity="selectedProduct.is_required_approval ? 'warning' : 'success'"
            />
          </div>
        </div>
        
        <div v-if="selectedProduct.description">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Description</label>
          <p class="tw-text-gray-900">{{ selectedProduct.description }}</p>
        </div>

        <div class="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-lg tw-p-4">
          <div class="tw-flex tw-items-start tw-gap-3">
            <i class="pi pi-info-circle tw-text-yellow-600 tw-text-xl tw-mt-1"></i>
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
import { ref, reactive, onMounted, computed } from 'vue'
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

const toast = useToast()

// Reactive state
const loading = ref(true)
const saving = ref(false)
const products = ref([])
const selectedProduct = ref(null)
const detailsDialog = ref(false)
const modifiedProducts = ref(new Set())

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

// Statistics
const statistics = computed(() => {
  return {
    total: products.value.length,
    requiresApproval: products.value.filter(p => p.is_required_approval).length,
    noApproval: products.value.filter(p => !p.is_required_approval).length
  }
})

// Methods
const fetchProducts = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    
    if (filters.approvalStatus !== null) {
      params.append('is_required_approval', filters.approvalStatus)
    }
    if (filters.search) {
      params.append('search', filters.search)
    }

    const response = await axios.get(`/api/products?${params.toString()}`)
    products.value = response.data.data || response.data
    
    // Mark all products as unmodified initially
    products.value.forEach(product => {
      product._modified = false
    })
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

const toggleApprovalStatus = (product) => {
  product._modified = true
  modifiedProducts.value.add(product.id)
  
  toast.add({
    severity: 'info',
    summary: 'Changed',
    detail: `${product.name || product.product_name} marked as ${product.is_required_approval ? 'requiring' : 'not requiring'} approval`,
    life: 2000
  })
}

const saveProduct = async (product) => {
  try {
    const response = await axios.put(`/api/products/${product.id}`, {
      is_required_approval: product.is_required_approval
    })

    if (response.data.status === 'success' || response.data.id) {
      product._modified = false
      modifiedProducts.value.delete(product.id)
      
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
    
    const productsToSave = products.value
      .filter(p => modifiedProducts.value.has(p.id))
      .map(p => ({
        id: p.id,
        is_required_approval: p.is_required_approval
      }))
    
    const response = await axios.post('/api/products/bulk-update-approval', {
      products: productsToSave
    })
    
    if (response.data.success) {
      // Clear modified flags
      products.value.forEach(p => {
        if (modifiedProducts.value.has(p.id)) {
          p._modified = false
        }
      })
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message || `Saved ${productsToSave.length} product(s) successfully`,
        life: 3000
      })
      
      modifiedProducts.value.clear()
    }
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
  await fetchProducts()
}

const resetFilters = () => {
  filters.approvalStatus = null
  filters.search = ''
  fetchProducts()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(async () => {
  await fetchProducts()
})
</script>
