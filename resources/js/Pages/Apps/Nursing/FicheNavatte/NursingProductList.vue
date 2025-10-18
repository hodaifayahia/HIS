<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <!-- Toast Notifications -->
    <Toast position="top-right" />

    <!-- Header Section -->
    <div class="tw-mb-8">
      <Card class="tw-w-full tw-bg-gradient-to-r tw-from-emerald-600 tw-to-teal-700 tw-text-white tw-shadow-xl tw-border-0">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-p-3 tw-bg-white/10 tw-rounded-full">
                <i class="pi pi-heart-fill tw-text-4xl"></i>
              </div>
              <div>
                <h1 class="tw-text-4xl tw-font-bold tw-mb-2">Nursing Products</h1>
                <p class="tw-text-emerald-100 tw-text-lg">Products available for your assigned services</p>
              </div>
            </div>
            <div class="tw-flex tw-gap-3 tw-items-center">
              <Button
                label="Refresh"
                icon="pi pi-refresh"
                severity="secondary"
                class="tw-rounded-xl tw-px-6 tw-py-3 tw-bg-white/10 tw-border-white/20 tw-text-white hover:tw-bg-white/20 tw-transition-all tw-duration-300"
                @click="refreshProducts"
                :loading="loading"
                v-tooltip.top="'Refresh Products'"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- User Services Info -->
    <div v-if="userServices.length > 0" class="tw-mb-6">
      <Card class="tw-bg-white tw-shadow-lg tw-border tw-border-blue-200">
        <template #content>
          <div class="tw-p-4">
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-users tw-text-blue-600"></i>
              Your Assigned Services
            </h3>
            <div class="tw-flex tw-flex-wrap tw-gap-2">
              <Tag 
                v-for="service in userServices" 
                :key="service.service_id"
                :value="service.service_name"
                severity="info"
                class="tw-px-3 tw-py-1 tw-rounded-full tw-font-medium"
              />
            </div>
            <p class="tw-text-sm tw-text-gray-600 tw-mt-2">
              Showing products from pharmacy and stock for these services only
            </p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Search and Filters -->
    <Card class="tw-mb-6 tw-bg-white tw-shadow-lg tw-border tw-border-gray-100">
      <template #content>
        <div class="tw-p-6">
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
            <!-- Search Input -->
            <div class="tw-relative">
              <InputText
                v-model="searchQuery"
                placeholder="Search products..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-rounded-xl tw-border-2 tw-border-gray-200 focus:tw-border-blue-500 tw-transition-all tw-duration-300"
                @input="handleSearchInput"
              />
              <i class="pi pi-search tw-absolute tw-left-4 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400 tw-text-lg"></i>
            </div>

            <!-- Category Filter -->
            <Dropdown
              v-model="selectedCategory"
              :options="categoryOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by Category"
              class="tw-w-full tw-rounded-xl tw-border-2 focus:tw-border-blue-500"
              @change="loadProducts"
            />

            <!-- Product Type Filter -->
            <Dropdown
              v-model="selectedType"
              :options="typeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by Source"
              class="tw-w-full tw-rounded-xl tw-border-2 focus:tw-border-blue-500"
              @change="loadProducts"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Statistics Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-6">
      <!-- Total Products -->
      <Card class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-blue-100 tw-border tw-border-blue-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-4">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-blue-700 tw-mb-1">Total Products</p>
              <p class="tw-text-3xl tw-font-bold tw-text-blue-900">{{ totalProducts }}</p>
            </div>
            <div class="tw-p-3 tw-bg-blue-500 tw-rounded-full tw-shadow-lg">
              <i class="pi pi-box tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Products -->
      <Card class="tw-bg-gradient-to-br tw-from-green-50 tw-to-green-100 tw-border tw-border-green-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-4">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-green-700 tw-mb-1">Stock Products</p>
              <p class="tw-text-3xl tw-font-bold tw-text-green-900">{{ stockProductsCount }}</p>
            </div>
            <div class="tw-p-3 tw-bg-green-500 tw-rounded-full tw-shadow-lg">
              <i class="pi pi-warehouse tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <!-- Pharmacy Products -->
      <Card class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-purple-100 tw-border tw-border-purple-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-4">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-purple-700 tw-mb-1">Pharmacy Products</p>
              <p class="tw-text-3xl tw-font-bold tw-text-purple-900">{{ pharmacyProductsCount }}</p>
            </div>
            <div class="tw-p-3 tw-bg-purple-500 tw-rounded-full tw-shadow-lg">
              <i class="pi pi-heart-fill tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <!-- Available Products -->
      <Card class="tw-bg-gradient-to-br tw-from-orange-50 tw-to-orange-100 tw-border tw-border-orange-200 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-4">
            <div>
              <p class="tw-text-sm tw-font-semibold tw-text-orange-700 tw-mb-1">Available Now</p>
              <p class="tw-text-3xl tw-font-bold tw-text-orange-900">{{ availableProductsCount }}</p>
            </div>
            <div class="tw-p-3 tw-bg-orange-500 tw-rounded-full tw-shadow-lg">
              <i class="pi pi-check-circle tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Products Table -->
    <Card class="tw-bg-white tw-shadow-xl tw-border tw-border-gray-100">
      <template #content>
        <div class="tw-p-6">
          <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
            <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-list tw-text-blue-600"></i>
              Products List
            </h2>
          </div>

          <DataTable
            :value="products"
            :loading="loading"
            :paginator="true"
            :rows="15"
            :totalRecords="pagination.total"
            :lazy="true"
            @page="onPageChange"
            class="tw-border tw-rounded-xl tw-overflow-hidden"
            stripedRows
            showGridlines
            responsiveLayout="scroll"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
          >
            <template #loading>
              <div class="tw-text-center tw-py-16">
                <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
                  <i class="pi pi-spin pi-spinner tw-text-4xl tw-text-blue-500"></i>
                  <p class="tw-text-gray-600 tw-text-lg tw-font-medium">Loading products...</p>
                </div>
              </div>
            </template>

            <template #empty>
              <div class="tw-text-center tw-py-16 tw-bg-gradient-to-br tw-from-gray-50 tw-to-gray-100 tw-rounded-xl">
                <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
                  <i class="pi pi-box-open tw-text-6xl tw-text-gray-300"></i>
                  <h3 class="tw-text-xl tw-font-medium tw-text-gray-700">No Products Found</h3>
                  <p class="tw-text-gray-500">No products available for your assigned services.</p>
                </div>
              </div>
            </template>

            <!-- Product Name Column -->
            <Column field="name" header="Product Name" style="min-width: 200px" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-p-2 tw-rounded-full" :class="slotProps.data.type === 'pharmacy' ? 'tw-bg-purple-100' : 'tw-bg-green-100'">
                    <i :class="slotProps.data.type === 'pharmacy' ? 'pi pi-heart-fill tw-text-purple-600' : 'pi pi-warehouse tw-text-green-600'"></i>
                  </div>
                  <div>
                    <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.code_interne }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Source Column -->
            <Column field="source" header="Source" style="min-width: 120px" sortable>
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.source" 
                  :severity="slotProps.data.type === 'pharmacy' ? 'info' : 'success'" 
                  class="tw-rounded-full tw-font-medium"
                />
              </template>
            </Column>

            <!-- Category Column -->
            <Column field="category" header="Category" style="min-width: 140px" sortable>
              <template #body="slotProps">
                <span v-if="slotProps.data.category" class="tw-bg-gray-100 tw-text-gray-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                  {{ slotProps.data.category }}
                </span>
                <span v-else class="tw-text-gray-400 tw-italic">No category</span>
              </template>
            </Column>

            <!-- Form Column -->
            <Column field="forme" header="Form" style="min-width: 120px" sortable>
              <template #body="slotProps">
                <span v-if="slotProps.data.forme" class="tw-bg-blue-100 tw-text-blue-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                  {{ slotProps.data.forme }}
                </span>
                <span v-else class="tw-text-gray-400 tw-italic">N/A</span>
              </template>
            </Column>

            <!-- Availability Column -->
            <Column field="availability" header="Availability" style="min-width: 180px" sortable>
              <template #body="slotProps">
                <div class="tw-space-y-2">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i :class="slotProps.data.total_quantity > 0 ? 'pi pi-check-circle tw-text-green-500' : 'pi pi-times-circle tw-text-red-500'"></i>
                    <span :class="slotProps.data.availability_class" class="tw-font-medium">
                      {{ slotProps.data.availability }}
                    </span>
                  </div>
                  <div class="tw-text-sm tw-text-gray-600">
                    {{ slotProps.data.inventory_display }}
                  </div>
                  <div v-if="Object.keys(slotProps.data.inventory_by_location).length > 0" class="tw-text-xs tw-text-gray-500">
                    <div v-for="(qty, location) in slotProps.data.inventory_by_location" :key="location" class="tw-truncate">
                      {{ location }}: {{ formatNumber(qty) }} {{ slotProps.data.unit }}
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Pharmacy Specific Info (for pharmacy products) -->
            <Column field="pharmacy_info" header="Pharmacy Info" style="min-width: 150px">
              <template #body="slotProps">
                <div v-if="slotProps.data.type === 'pharmacy'" class="tw-space-y-1">
                  <div v-if="slotProps.data.dci" class="tw-text-sm tw-text-gray-700">
                    <strong>DCI:</strong> {{ slotProps.data.dci }}
                  </div>
                  <div v-if="slotProps.data.dosage" class="tw-text-sm tw-text-gray-700">
                    <strong>Dosage:</strong> {{ slotProps.data.dosage }}
                  </div>
                  <div class="tw-flex tw-gap-1 tw-flex-wrap tw-mt-1">
                    <Tag v-if="slotProps.data.is_controlled_substance" value="Controlled" severity="danger" class="tw-text-xs" />
                    <Tag v-if="slotProps.data.requires_prescription" value="Prescription" severity="warn" class="tw-text-xs" />
                  </div>
                </div>
                <span v-else class="tw-text-gray-400 tw-italic tw-text-sm">Stock Product</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import Toast from 'primevue/toast'

// Reactive data
const toast = useToast()
const loading = ref(false)
const products = ref([])
const userServices = ref([])
const categories = ref([])
const searchQuery = ref('')
const selectedCategory = ref('')
const selectedType = ref('')
const searchDebounceTimeout = ref(null)

// Pagination
const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1
})

// Computed properties
const totalProducts = computed(() => products.value.length)
const stockProductsCount = computed(() => products.value.filter(p => p.type === 'stock').length)
const pharmacyProductsCount = computed(() => products.value.filter(p => p.type === 'pharmacy').length)
const availableProductsCount = computed(() => products.value.filter(p => p.total_quantity > 0).length)

const categoryOptions = computed(() => [
  { label: 'All Categories', value: '' },
  ...categories.value.map(cat => ({ label: cat, value: cat }))
])

const typeOptions = ref([
  { label: 'All Sources', value: '' },
  { label: 'Stock Products', value: 'stock' },
  { label: 'Pharmacy Products', value: 'pharmacy' }
])

// Methods
const loadProducts = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      search: searchQuery.value,
      category: selectedCategory.value,
      type: selectedType.value
    }

    // Remove empty params
    Object.keys(params).forEach(key => {
      if (!params[key]) delete params[key]
    })

    const response = await axios.get('/api/nursing/products', { params })
    
    if (response.data.success) {
      products.value = response.data.data
      pagination.value = response.data.pagination || pagination.value
    } else {
      throw new Error(response.data.message || 'Failed to load products')
    }
  } catch (error) {
    console.error('Error loading products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load products',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await axios.get('/api/nursing/products/categories')
    if (response.data.success) {
      categories.value = response.data.data
    }
  } catch (error) {
    console.error('Error loading categories:', error)
  }
}

const loadUserServices = async () => {
  try {
    const response = await axios.get('/api/nursing/user-services')
    if (response.data.success) {
      userServices.value = response.data.data
      
      if (response.data.is_admin) {
        toast.add({
          severity: 'info',
          summary: 'Admin Access',
          detail: 'You have admin access to all products',
          life: 3000
        })
      }
    }
  } catch (error) {
    console.error('Error loading user services:', error)
  }
}

const handleSearchInput = () => {
  clearTimeout(searchDebounceTimeout.value)
  searchDebounceTimeout.value = setTimeout(() => {
    loadProducts(1)
  }, 500)
}

const onPageChange = (event) => {
  loadProducts(event.page + 1)
}

const refreshProducts = () => {
  loadProducts(pagination.value.current_page)
}

const formatNumber = (num) => {
  return new Intl.NumberFormat().format(num)
}

const viewProduct = (product) => {
  // Navigate to product details or show modal
  toast.add({
    severity: 'info',
    summary: 'Product Details',
    detail: `Viewing details for ${product.name}`,
    life: 3000
  })
}

const useProduct = (product) => {
  // Handle product usage - could open a consumption modal
  toast.add({
    severity: 'success',
    summary: 'Product Usage',
    detail: `Using ${product.name}`,
    life: 3000
  })
}

// Lifecycle
onMounted(() => {
  loadUserServices()
  loadCategories()
  loadProducts()
})
</script>

<style scoped>
/* Custom styles for better UI */
.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f8fafc !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr {
  transition: all 0.2s ease;
}

/* Enhanced button styles */
.p-button {
  transition: all 0.3s ease !important;
}

.p-button:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Enhanced form inputs */
.p-inputtext:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
  border-color: #3b82f6 !important;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Tag enhancements */
.p-tag {
  border-radius: 20px !important;
  font-weight: 600 !important;
  letter-spacing: 0.025em !important;
}

/* DataTable header styling */
.p-datatable .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  border: none !important;
  padding: 1rem !important;
}

/* Enhanced card shadows */
.tw-shadow-lg {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
}

.tw-shadow-xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
}

/* Card hover effects */
.p-card:hover {
  transform: translateY(-2px);
  transition: all 0.3s ease;
}
</style>
