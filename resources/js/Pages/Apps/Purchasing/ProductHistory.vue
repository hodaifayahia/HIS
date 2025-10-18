<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-mb-6">
      <div class="tw-flex tw-items-center tw-gap-4 tw-mb-4">
        <Button 
          icon="pi pi-arrow-left" 
          text
          @click="goBack"
          class="tw-text-gray-600"
        />
        <div>
          <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Product Purchase History</h1>
          <p class="tw-text-gray-600 tw-mt-1">Complete purchase history and supplier information</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
      <ProgressSpinner />
    </div>

    <!-- Product Information -->
    <div v-else-if="productData" class="tw-space-y-6">
      <!-- Product Details Card -->
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-6">
        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900 tw-mb-4">{{ productData.name }}</h2>
        
        <!-- Statistics Cards -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4 tw-mb-6">
          <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-text-center">
            <i class="pi pi-shopping-cart tw-text-2xl tw-text-blue-600 tw-mb-2 tw-block"></i>
            <p class="tw-text-sm tw-text-blue-600 tw-font-medium">Total Purchases</p>
            <p class="tw-text-xl tw-font-bold tw-text-blue-800">{{ statistics.totalPurchases || 0 }}</p>
          </div>
          <div class="tw-bg-green-50 tw-p-4 tw-rounded-lg tw-text-center">
            <i class="pi pi-box tw-text-2xl tw-text-green-600 tw-mb-2 tw-block"></i>
            <p class="tw-text-sm tw-text-green-600 tw-font-medium">Total Quantity</p>
            <p class="tw-text-xl tw-font-bold tw-text-green-800">{{ statistics.totalQuantity || 0 }}</p>
          </div>
          <div class="tw-bg-orange-50 tw-p-4 tw-rounded-lg tw-text-center">
            <i class="pi pi-users tw-text-2xl tw-text-orange-600 tw-mb-2 tw-block"></i>
            <p class="tw-text-sm tw-text-orange-600 tw-font-medium">Suppliers</p>
            <p class="tw-text-xl tw-font-bold tw-text-orange-800">{{ statistics.suppliersCount || 0 }}</p>
          </div>
          <div class="tw-bg-purple-50 tw-p-4 tw-rounded-lg tw-text-center">
            <i class="pi pi-dollar tw-text-2xl tw-text-purple-600 tw-mb-2 tw-block"></i>
            <p class="tw-text-sm tw-text-purple-600 tw-font-medium">Total Value</p>
            <p class="tw-text-sm tw-font-bold tw-text-purple-800">{{ formatCurrency(statistics.totalValue || 0) }}</p>
          </div>
        </div>
      </div>

      <!-- Purchase History Table -->
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-6">
        <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Purchase History</h3>
        
        <div v-if="purchaseHistory.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
          <i class="pi pi-inbox tw-text-4xl tw-mb-2 tw-block"></i>
          <p>No purchases found for this product</p>
        </div>
        
        <div v-else class="tw-overflow-x-auto">
          <table class="tw-w-full tw-table-auto">
            <thead>
              <tr class="tw-border-b tw-border-gray-200">
                <th class="tw-text-left tw-py-3 tw-px-4 tw-font-semibold tw-text-gray-700">Date</th>
                <th class="tw-text-left tw-py-3 tw-px-4 tw-font-semibold tw-text-gray-700">Supplier</th>
                <th class="tw-text-left tw-py-3 tw-px-4 tw-font-semibold tw-text-gray-700">Quantity</th>
                <th class="tw-text-left tw-py-3 tw-px-4 tw-font-semibold tw-text-gray-700">Unit Price</th>
                <th class="tw-text-left tw-py-3 tw-px-4 tw-font-semibold tw-text-gray-700">Total</th>
                <th class="tw-text-left tw-py-3 tw-px-4 tw-font-semibold tw-text-gray-700">Location</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="purchase in purchaseHistory" 
                :key="purchase.id"
                class="tw-border-b tw-border-gray-100 tw-hover:tw-bg-gray-50"
              >
                <td class="tw-py-3 tw-px-4">
                  <div class="tw-text-sm">
                    <p class="tw-font-medium tw-text-gray-900">{{ formatDate(purchase.date_reception) }}</p>
                    <p class="tw-text-gray-500">Reception #{{ purchase.id }}</p>
                  </div>
                </td>
                <td class="tw-py-3 tw-px-4">
                  <p class="tw-text-sm tw-font-medium tw-text-gray-900">{{ purchase.supplier_name || 'Unknown' }}</p>
                </td>
                <td class="tw-py-3 tw-px-4">
                  <span class="tw-text-sm tw-font-medium tw-text-gray-900">
                    {{ purchase.quantity_received }}
                  </span>
                </td>
                <td class="tw-py-3 tw-px-4">
                  <span v-if="purchase.purchase_price" class="tw-text-sm tw-font-medium tw-text-gray-900">
                    {{ formatCurrency(purchase.purchase_price) }}
                  </span>
                  <span v-else class="tw-text-sm tw-text-gray-500">N/A</span>
                </td>
                <td class="tw-py-3 tw-px-4">
                  <span v-if="purchase.total_price" class="tw-text-sm tw-font-bold tw-text-green-600">
                    {{ formatCurrency(purchase.total_price) }}
                  </span>
                  <span v-else class="tw-text-sm tw-text-gray-500">N/A</span>
                </td>
                <td class="tw-py-3 tw-px-4">
                  <span v-if="purchase.current_location" class="tw-text-xs tw-bg-blue-100 tw-text-blue-800 tw-px-2 tw-py-1 tw-rounded">
                    {{ purchase.current_location }}
                  </span>
                  <span v-else class="tw-text-sm tw-text-gray-500">Unknown</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="tw-text-center tw-py-12">
      <i class="pi pi-exclamation-triangle tw-text-4xl tw-text-red-500 tw-mb-4 tw-block"></i>
      <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">Error Loading Product History</h3>
      <p class="tw-text-gray-600">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'

const router = useRouter()
const toast = useToast()

// Props
const props = defineProps({
  productId: {
    type: Number,
    required: true
  }
})

// Reactive data
const loading = ref(true)
const error = ref(null)
const productData = ref(null)
const purchaseHistory = ref([])
const suppliers = ref([])
const statistics = ref({})

// Methods
const fetchProductHistory = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await axios.get(`/api/products/${props.productId}/history`)
    
    if (response.data.success) {
      const data = response.data.data
      productData.value = data.product
      purchaseHistory.value = data.purchaseHistory || []
      suppliers.value = data.suppliers || []
      statistics.value = data.statistics || {}
    } else {
      throw new Error(response.data.message || 'Failed to fetch product history')
    }
  } catch (err) {
    console.error('Error fetching product history:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load product history'
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load product history',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('en-US', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch (err) {
    return 'Invalid Date'
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  fetchProductHistory()
})
</script>