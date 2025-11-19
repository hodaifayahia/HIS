<script setup>
// KEEPING ALL EXISTING SCRIPT EXACTLY AS IS - NO CHANGES TO FUNCTIONALITY
import { ref, reactive, onMounted, computed, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import ProgressSpinner from 'primevue/progressspinner'
import ProgressBar from 'primevue/progressbar'
import Tag from 'primevue/tag'
import Rating from 'primevue/rating'
import Badge from 'primevue/badge'
import Divider from 'primevue/divider'
import Calendar from 'primevue/calendar'
// Add new UI components
import Card from 'primevue/card'
import Avatar from 'primevue/avatar'
import Chip from 'primevue/chip'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Skeleton from 'primevue/skeleton'
import Timeline from 'primevue/timeline'
import SpeedDial from 'primevue/speeddial'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// ALL EXISTING COMPOSABLES AND STATE - UNCHANGED
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

// ALL EXISTING REACTIVE STATE - UNCHANGED
const loading = ref(true)
const error = ref(null)
const serviceDemand = ref(null)
const suppliers = ref([])
const supplierDialog = ref(false)
const selectedProduct = ref(null)
const submittingAssignment = ref(false)
const savingData = ref(false)
const loadingSupplierData = ref(false)

// Workflow status tracking
const serviceDemandStatus = ref(null)

// Supplier pricing and rating data
const supplierPricingData = ref({})
const supplierRatings = ref({})

// Pricing history modal state
const historyDialog = ref(false)
const loadingHistory = ref(false)
const pricingHistoryData = ref([])
const selectedHistoryProduct = ref(null)
const selectedHistorySupplier = ref(null)

// History filters
const historyFilters = reactive({
  startDate: null,
  endDate: null,
  orderType: null
})

const orderTypeOptions = [
  { label: 'All Types', value: null },
  { label: 'Proforma', value: 'proforma' },
  { label: 'Receipt', value: 'receipt' },
  { label: 'Purchase Order', value: 'purchase_order' }
]

// Assignment form - UNCHANGED
const assignmentForm = reactive({
  fournisseur_ids: [],
  quantity: 0,
  unit: '',
  notes: ''
})

const formErrors = ref({})

// Product Selection Dialog State - NEW
const showProductSelectionDialog = ref(false)
const productSelectionTab = ref(0) // 0 = Stock, 1 = Pharmacy
const availableStockProducts = ref([])
const availablePharmacyProducts = ref([])
const selectedStockProduct = ref(null)
const selectedPharmacyProduct = ref(null)
const productSelectionQuantity = ref(1)
const productSelectionUnit = ref('unit') // 'unit' or 'box'
const loadingProducts = ref(false)
const selectedItemForProductSelection = ref(null) // Track which item is being edited

// Pagination state for infinite scroll
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

// Service demand pharmacy order flag
const serviceDemandIsPharmacyOrder = ref(false)

// NEW UI ENHANCEMENTS (additions only)
const activeTab = ref(0)
const expandedRows = ref([])
const speedDialItems = ref([
  {
    label: 'Save Proforma',
    icon: 'pi pi-save',
    command: () => saveProforma(),
    visible: () => showProformaButton.value
  },
  {
    label: 'Save Bon Commend',
    icon: 'pi pi-file-export',
    command: () => saveBonCommend(),
    visible: () => showBonCommendButton.value
  },
  {
    label: 'Export PDF',
    icon: 'pi pi-file-pdf',
    command: () => exportPDF()
  },
  {
    label: 'Print',
    icon: 'pi pi-print',
    command: () => window.print()
  },
  {
    label: 'Refresh',
    icon: 'pi pi-refresh',
    command: () => refreshData()
  }
])

// ALL EXISTING COMPUTED PROPERTIES - UNCHANGED
const hasAssignments = computed(() => {
  return serviceDemand.value?.items?.some(item => item.fournisseur_assignments?.length > 0)
})

const totalAssignments = computed(() => {
  if (!serviceDemand.value?.items) return 0
  return serviceDemand.value.items.reduce((sum, item) => 
    sum + (item.fournisseur_assignments?.length || 0), 0)
})

const unassignedItems = computed(() => {
  if (!serviceDemand.value?.items) return 0
  return serviceDemand.value.items.filter(item => 
    !item.fournisseur_assignments || item.fournisseur_assignments.length === 0).length
})

const assignmentProgress = computed(() => {
  if (!serviceDemand.value?.items?.length) return 0
  const assigned = serviceDemand.value.items.filter(item => 
    item.fournisseur_assignments?.length > 0).length
  return Math.round((assigned / serviceDemand.value.items.length) * 100)
})

const enhancedSuppliers = computed(() => {
  return suppliers.value.map(supplier => {
    const pricingData = supplierPricingData.value[supplier.id] || {}
    const rating = supplierRatings.value[supplier.id] || { rating: 0, total_orders: 0 }

    return {
      ...supplier,
      lastPrice: pricingData.last_price || null,
      averagePrice: pricingData.average_price || null,
      minPrice: pricingData.min_price || null,
      maxPrice: pricingData.max_price || null,
      totalOrders: pricingData.total_orders || 0,
      priceTrend: pricingData.price_trend || 'stable',
      reliabilityScore: pricingData.reliability_score || 0,
      rating: rating.rating || 0,
      ratingTotalOrders: rating.total_orders || 0,
      onTimeDelivery: rating.on_time_delivery || 0,
      qualityScore: rating.quality_score || 0,
      performanceTier: rating.performance_tier || 'average'
    }
  }).sort((a, b) => {
    // Sort by: 1) Has pricing data, 2) Average price (lowest first), 3) Rating (highest first)
    if (a.averagePrice && !b.averagePrice) return -1
    if (!a.averagePrice && b.averagePrice) return 1
    if (a.averagePrice && b.averagePrice) {
      return a.averagePrice - b.averagePrice
    }
    return b.rating - a.rating
  })
})

// Workflow status computed properties - UNCHANGED
const showProformaButton = computed(() => {
  if (!serviceDemand.value) return false
  const currentStatus = serviceDemandStatus.value || serviceDemand.value.status
  return ['sent', 'approved'].includes(currentStatus) && currentStatus !== 'factureprofram'
})

const showBonCommendButton = computed(() => {
  if (!serviceDemand.value) return false
  const currentStatus = serviceDemandStatus.value || serviceDemand.value.status
  return ['factureprofram', 'approved','sent'].includes(currentStatus) && currentStatus !== 'boncommend'
})

// Check localStorage for saved workflow states - UNCHANGED
const getWorkflowStatusFromStorage = () => {
  const storageKey = `serviceDemandStatus_${route.params.id}`
  return localStorage.getItem(storageKey)
}

const setWorkflowStatusInStorage = (status) => {
  const storageKey = `serviceDemandStatus_${route.params.id}`
  localStorage.setItem(storageKey, status)
  serviceDemandStatus.value = status
}

// ALL EXISTING METHODS - KEEPING EXACTLY AS IS
const fetchServiceDemand = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await axios.get(`/api/service-demands/${route.params.id}`)

    if (response.data.success) {
      serviceDemand.value = response.data.data
      serviceDemandIsPharmacyOrder.value = response.data.data.is_pharmacy_order || (route.query.is_pharmacy_order === 'true')

      
      // Initialize workflow status from localStorage or current status
      const storedStatus = getWorkflowStatusFromStorage()
      if (storedStatus && ['factureprofram', 'boncommend'].includes(storedStatus)) {
        serviceDemandStatus.value = storedStatus
      } else {
        serviceDemandStatus.value = response.data.data.status
      }
    } else {
      throw new Error(response.data.message || 'Failed to fetch service demand')
    }
  } catch (err) {
    console.error('Error fetching service demand:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load service demand'
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/service-demands/meta/fournisseurs')
    if (response.data.success) {
      suppliers.value = response.data.data

      // Fetch real supplier ratings from backend
      await fetchSupplierRatings()
    }
  } catch (err) {
    console.error('Error fetching suppliers:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load suppliers',
      life: 3000
    })
  }
}

const fetchSupplierPricingData = async (productId, isPharmacy = false) => {
  // Skip if no product ID
  if (!productId) {
    console.warn('No product ID provided for supplier pricing data')
    return {}
  }

  // Skip pricing data for pharmacy products (they don't have supplier pricing)
  if (isPharmacy) {
    console.log('Pharmacy products do not have supplier pricing data')
    return {}
  }

  try {
    // Only fetch pricing data for stock products
    const endpoint = `/api/products/${productId}/supplier-pricing`
    
    const response = await axios.get(endpoint)
    if (response.data.success) {
      // Store pricing data by supplier ID for easy lookup
      const pricingBySupplier = {}
      response.data.data.forEach(supplierData => {
        pricingBySupplier[supplierData.supplier_id] = supplierData
      })
      return pricingBySupplier
    }
    return {}
  } catch (err) {
    console.error('Error fetching supplier pricing data:', err)

    // Show specific error message from backend (but only if it's a real error, not 404)
    if (err.response?.status !== 404) {
      const errorMessage = err.response?.data?.message || err.message || 'Failed to load supplier pricing data'
      toast.add({
        severity: 'error',
        summary: 'Error Loading Pricing Data',
        detail: errorMessage,
        life: 5000
      })
    }
    return {}
  }
}

const fetchSupplierRatings = async () => {
  try {
    const response = await axios.get('/api/service-demands/meta/supplier-ratings')
    if (response.data.success) {
      // Store ratings by supplier ID
      supplierRatings.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching supplier ratings:', error)

    // Show specific error message from backend
    const errorMessage = error.response?.data?.message || error.message || 'Failed to load supplier ratings'
    toast.add({
      severity: 'error',
      summary: 'Error Loading Supplier Ratings',
      detail: errorMessage,
      life: 5000
    })
  }
}

const showSupplierDialog = async (product) => {
  selectedProduct.value = product
  assignmentForm.fournisseur_ids = []
  assignmentForm.quantity = product.quantity || 1 // Initialize with product quantity
  assignmentForm.unit = product.quantity_by_box ? 'Box' : 'Unit' // Get unit from product
  assignmentForm.notes = ''
  formErrors.value = {}

  // Determine product type and fetch appropriate pricing data
  loadingSupplierData.value = true
  try {
    const isPharmacy = !!product.pharmacy_product_id
    const productId = isPharmacy ? product.pharmacy_product_id : product.product_id
    
    if (!productId) {
      console.warn('No product ID found for pricing data')
      supplierPricingData.value = {}
    } else {
      const pricingData = await fetchSupplierPricingData(productId, isPharmacy)
      supplierPricingData.value = pricingData
    }
  } catch (error) {
    console.error('Error loading supplier pricing data:', error)
    supplierPricingData.value = {}
  } finally {
    loadingSupplierData.value = false
  }

  supplierDialog.value = true
}

const saveAssignment = async () => {
  try {
    formErrors.value = {}

    // Validation
    if (!assignmentForm.fournisseur_ids || assignmentForm.fournisseur_ids.length === 0) {
      formErrors.value.fournisseur_ids = 'At least one supplier is required'
      return
    }

    if (!assignmentForm.quantity || assignmentForm.quantity <= 0) {
      formErrors.value.quantity = 'Quantity must be greater than 0'
      return
    }

    submittingAssignment.value = true

    // Create assignments for each selected supplier
    const assignments = assignmentForm.fournisseur_ids.map(fournisseurId => ({
      item_id: selectedProduct.value.id, // Include item_id in each assignment
      fournisseur_id: fournisseurId,
      assigned_quantity: assignmentForm.quantity, // User-specified quantity
      unit_price: null, // No price for proforma demand
      unit: assignmentForm.unit,
      notes: assignmentForm.notes
    }))

    // Send bulk assignment request
    const response = await axios.post(
      `/api/service-demands/${route.params.id}/bulk-assign-fournisseurs`,
      {
        assignments: assignments
      }
    )

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `${assignmentForm.fournisseur_ids.length} supplier(s) assigned successfully`,
        life: 3000
      })

      supplierDialog.value = false
      await fetchServiceDemand()
    } else {
      throw new Error(response.data.message || 'Assignment failed')
    }
  } catch (err) {
    console.error('Error assigning suppliers:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to assign suppliers',
      life: 3000
    })
  } finally {
    submittingAssignment.value = false
  }
}

const removeAssignment = async (itemId, assignmentId) => {
  confirm.require({
    message: 'Are you sure you want to remove this supplier assignment?',
    header: 'Confirm Removal',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-text',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(
          `/api/service-demands/${route.params.id}/items/${itemId}/assignments/${assignmentId}`
        )

        if (response.data.success) {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Assignment removed successfully',
            life: 3000
          })

          await fetchServiceDemand()
        } else {
          throw new Error(response.data.message || 'Failed to remove assignment')
        }
      } catch (err) {
        console.error('Error removing assignment:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to remove assignment',
          life: 3000
        })
      }
    }
  })
}
// ... continuing from Part 1

const editAssignment = (product, assignment) => {
  selectedProduct.value = product
  assignmentForm.fournisseur_ids = [assignment.fournisseur_id]
  assignmentForm.quantity = assignment.assigned_quantity || product.quantity || 1
  assignmentForm.unit = assignment.unit || (product.quantity_by_box ? 'Box' : 'Unit')
  assignmentForm.notes = assignment.notes || ''
  formErrors.value = {}
  supplierDialog.value = true
}

const saveProforma = async () => {
  try {
    savingData.value = true

    // Check if there are any supplier assignments
    const hasAnyAssignments = serviceDemand.value?.items?.some(item => item.fournisseur_assignments?.length > 0)
    if (!hasAnyAssignments) {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'No supplier assignments found to save',
        life: 3000
      })
      return
    }

    let totalFactureProformas = 0

    // Process each product with assignments
    for (const product of serviceDemand.value.items) {
      const assignments = product.fournisseur_assignments || []
      if (assignments.length === 0) continue

      // Group assignments by supplier
      const supplierGroups = {}
      assignments.forEach(assignment => {
        const supplierId = assignment.fournisseur.id
        if (!supplierGroups[supplierId]) {
          supplierGroups[supplierId] = {
            supplier: assignment.fournisseur,
            products: []
          }
        }

        // Get product ID from either stock product or pharmacy product
        const productId = product.product?.id || product.pharmacy_product?.id || product.product_id || product.pharmacy_product_id
        
        if (!productId) {
          console.warn('Warning: Could not find product ID for item:', product)
          return
        }

        supplierGroups[supplierId].products.push({
          product_id: productId,
          quantity: assignment.assigned_quantity,
          price: 0,
          unit: assignment.unit || 'Unit'
        })
      })

      // Create facture proforma for each supplier
      for (const [supplierId, group] of Object.entries(supplierGroups)) {
        try {
          const factureProformaData = {
            fournisseur_id: parseInt(supplierId),
            service_demand_purchasing_id: serviceDemand.value.id,
            products: group.products,
            order_date: new Date().toISOString().split('T')[0]
          }

          const proformaResponse = await axios.post('/api/facture-proformas', factureProformaData)

          if (proformaResponse.data.status !== 'success') {
            throw new Error('Failed to create facture proforma')
          }

          totalFactureProformas++

          // Add workflow note
          await addWorkflowNote('Generate Facture Proforma')

        } catch (supplierError) {
          console.error(`Error saving proforma for supplier ${group.supplier.company_name}:`, supplierError)
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to save proforma for ${group.supplier.company_name}`,
            life: 3000
          })
        }
      }
    }

    if (totalFactureProformas > 0) {
      // Update service demand status to 'factureprofram'
      try {
        await axios.post(`/api/service-demands/${serviceDemand.value.id}/update-to-facture-proforma`)

        // Update local status and localStorage
        serviceDemand.value.status = 'factureprofram'
        setWorkflowStatusInStorage('factureprofram')

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Saved ${totalFactureProformas} facture proforma(s) successfully and updated status`,
          life: 3000
        })
      } catch (statusError) {
        console.error('Error updating status to facture proforma:', statusError)
        toast.add({
          severity: 'warn',
          summary: 'Warning', 
          detail: `Proformas saved but failed to update status`,
          life: 3000
        })
      }

      // Navigate to facture proforma list
      await navigateToFactureProformaList()
    }

  } catch (err) {
    console.error('Error saving facture proforma:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save facture proforma data',
      life: 3000
    })
  } finally {
    savingData.value = false
  }
}

const saveBonCommend = async () => {
  try {
    savingData.value = true

    // Check if there are any supplier assignments
    const hasAnyAssignments = serviceDemand.value?.items?.some(item => item.fournisseur_assignments?.length > 0)
    if (!hasAnyAssignments) {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'No supplier assignments found to save',
        life: 3000
      })
      return
    }

    let totalBonCommends = 0

    // Process each product with assignments
    for (const product of serviceDemand.value.items) {
      const assignments = product.fournisseur_assignments || []
      if (assignments.length === 0) continue

      // Group assignments by supplier
      const supplierGroups = {}
      assignments.forEach(assignment => {
        const supplierId = assignment.fournisseur.id
        if (!supplierGroups[supplierId]) {
          supplierGroups[supplierId] = {
            supplier: assignment.fournisseur,
            products: []
          }
        }

        // Get product ID from either stock product or pharmacy product
        const productId = product.product?.id || product.pharmacy_product?.id || product.product_id || product.pharmacy_product_id
        
        if (!productId) {
          console.warn('Warning: Could not find product ID for item:', product)
          return
        }

        supplierGroups[supplierId].products.push({
          product_id: productId,
          quantity: assignment.assigned_quantity,
          price: 0,
          unit: assignment.unit || 'Unit'
        })
      })

      // For each supplier, create facture proforma first, then bon commend
      for (const [supplierId, group] of Object.entries(supplierGroups)) {
        try {
          // Step 1: Create facture proforma for this supplier
          const factureProformaData = {
            fournisseur_id: parseInt(supplierId),
            service_demand_purchasing_id: serviceDemand.value.id,
            products: group.products,
            order_date: new Date().toISOString().split('T')[0]
          }

          const proformaResponse = await axios.post('/api/facture-proformas', factureProformaData)

          if (proformaResponse.data.status !== 'success') {
            throw new Error('Failed to create facture proforma')
          }

          const factureProforma = proformaResponse.data.data

          // Step 2: Create bon commend items linked to the facture proforma
          const bonCommendData = {
            fournisseur_id: parseInt(supplierId),
            service_demand_purchasing_id: serviceDemand.value.id,
            order_date: new Date().toISOString().split('T')[0],
            items: group.products.map(prod => ({
              factureproforma_id: factureProforma.id,
              product_id: prod.product_id,
              quantity: prod.quantity,
              quantity_desired: prod.quantity,
              price: prod.price,
              unit: prod.unit,
              status: 'pending'
            }))
          }

          const bonCommendResponse = await axios.post('/api/bon-commends', bonCommendData)

          if (bonCommendResponse.data.status === 'success') {
            totalBonCommends++
          }

          // Add workflow note
          await addWorkflowNote('Generate Facture Proforma->Generate Boncommend')

        } catch (supplierError) {
          console.error(`Error saving boncommend for supplier ${group.supplier.company_name}:`, supplierError)
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to save boncommend for ${group.supplier.company_name}`,
            life: 3000
          })
        }
      }
    }

    if (totalBonCommends > 0) {
      // Update service demand status to 'boncommend'
      try {
        await axios.post(`/api/service-demands/${serviceDemand.value.id}/update-to-bon-commend`)

        // Update local status and localStorage
        serviceDemand.value.status = 'boncommend'
        setWorkflowStatusInStorage('boncommend')

        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Saved ${totalBonCommends} bon commend(s) successfully and updated status`,
          life: 3000
        })
      } catch (statusError) {
        console.error('Error updating status to bon commend:', statusError)
        toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: `Bon commends saved but failed to update status`,
          life: 3000
        })
      }

      // Navigate to bon commend list
      await navigateToBonCommendList()
    }

  } catch (err) {
    console.error('Error saving bon commend:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save bon commend data',
      life: 3000
    })
  } finally {
    savingData.value = false
  }
}

// Navigation functions
const navigateToFactureProformaList = async () => {
  // Navigate to facture proforma list page
  window.location.href = '/purchasing/facture-proforma-list'
}

const navigateToBonCommendList = async () => {
  // Navigate to bon commend list page
  window.location.href = '/purchasing/bon-commend-list'
}

// Workflow tracking function
const addWorkflowNote = async (noteText) => {
  try {
    // Add workflow tracking note to service demand
    await axios.post(`/api/service-demands/${route.params.id}/add-note`, {
      note: noteText,
      created_by: 'system'
    })
  } catch (error) {
    console.error('Error adding workflow note:', error)
  }
}

const refreshData = async () => {
  await fetchServiceDemand()
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

// Product Selection Methods - NEW
const openProductSelectionDialog = async (item = null) => {
  selectedItemForProductSelection.value = item
  
  selectedStockProduct.value = null
  selectedPharmacyProduct.value = null
  productSelectionQuantity.value = 1
  
  // Initialize pagination states
  stockProductsPagination.page = 1
  stockProductsPagination.totalPages = 1
  stockProductsPagination.loading = false
  stockProductsPagination.hasMore = true
  stockProductsPagination.searchQuery = ''
  
  pharmacyProductsPagination.page = 1
  pharmacyProductsPagination.totalPages = 1
  pharmacyProductsPagination.loading = false
  pharmacyProductsPagination.hasMore = true
  pharmacyProductsPagination.searchQuery = ''
  
  // Clear existing products
  availableStockProducts.value = []
  availablePharmacyProducts.value = []
  
  loadingProducts.value = true
  
  try {
    // Determine if we should load pharmacy or stock products
    // If is_pharmacy_order is true, load pharmacy products, otherwise stock products
    const isPharmacyOrder = item?.is_pharmacy_order ?? serviceDemandIsPharmacyOrder.value
    
    if (isPharmacyOrder) {
      await loadPharmacyProducts()
    } else {
      await loadStockProducts()
    }
  } catch (error) {
    console.error('Error loading products:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load products',
      life: 3000
    })
  } finally {
    loadingProducts.value = false
  }
  
  showProductSelectionDialog.value = true
  
  // Attach scroll listeners after a short delay to ensure DOM is ready
  nextTick(() => {
    setTimeout(() => {
      attachScrollListeners()
    }, 500)
  })
}

const closeProductSelectionDialog = () => {
  showProductSelectionDialog.value = false
  selectedStockProduct.value = null
  selectedPharmacyProduct.value = null
  productSelectionQuantity.value = 1
  productSelectionUnit.value = 'unit'
  selectedItemForProductSelection.value = null
  
  // Clean up scroll listeners when closing dialog
  removeScrollListeners()
}

// Paginated loading functions for infinite scroll
const loadStockProducts = async (searchQuery = '', append = false) => {
  // Prevent duplicate loading
  if (stockProductsPagination.loading) {
    console.log('📦 Stock products already loading, skipping...')
    return
  }
  
  stockProductsPagination.loading = true
  
  console.log(`📦 Loading stock products - Page: ${stockProductsPagination.page}, Append: ${append}, Search: "${searchQuery}"`)
  
  try {
    const params = {
      type: 'stock',
      page: stockProductsPagination.page,
      per_page: 50,
      search: searchQuery || stockProductsPagination.searchQuery || ''
    }
    
    const response = await axios.get('/api/service-demands/meta/products', { params })
    
    if (response.data.success) {
      const newProducts = response.data.data || []
      console.log(`✅ Loaded ${newProducts.length} stock products. Total now: ${append ? availableStockProducts.value.length + newProducts.length : newProducts.length}`)
      
      if (append) {
        // Prevent duplicate entries by checking IDs
        const existingIds = new Set(availableStockProducts.value.map(p => p.id))
        const uniqueNewProducts = newProducts.filter(p => !existingIds.has(p.id))
        availableStockProducts.value = [...availableStockProducts.value, ...uniqueNewProducts]
      } else {
        availableStockProducts.value = newProducts
      }
      
      // Update pagination info
      stockProductsPagination.totalPages = response.data.last_page || 1
      stockProductsPagination.hasMore = stockProductsPagination.page < stockProductsPagination.totalPages
      console.log(`📊 Pagination - Current: ${stockProductsPagination.page}, Total: ${stockProductsPagination.totalPages}, HasMore: ${stockProductsPagination.hasMore}`)
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
    stockProductsPagination.loading = false
  }
}

const loadPharmacyProducts = async (searchQuery = '', append = false) => {
  // Prevent duplicate loading
  if (pharmacyProductsPagination.loading) {
    console.log('💊 Pharmacy products already loading, skipping...')
    return
  }
  
  pharmacyProductsPagination.loading = true
  
  console.log(`💊 Loading pharmacy products - Page: ${pharmacyProductsPagination.page}, Append: ${append}, Search: "${searchQuery}"`)
  
  try {
    const params = {
      page: pharmacyProductsPagination.page,
      per_page: 50,
      search: searchQuery || pharmacyProductsPagination.searchQuery || ''
    }

    const response = await axios.get('/api/pharmacy/products', { params })

    if (response.data.success) {
      const newProducts = response.data.data || []
      console.log(`✅ Loaded ${newProducts.length} pharmacy products. Total now: ${append ? availablePharmacyProducts.value.length + newProducts.length : newProducts.length}`)
      
      if (append) {
        // Prevent duplicate entries by checking IDs
        const existingIds = new Set(availablePharmacyProducts.value.map(p => p.id))
        const uniqueNewProducts = newProducts.filter(p => !existingIds.has(p.id))
        availablePharmacyProducts.value = [...availablePharmacyProducts.value, ...uniqueNewProducts]
      } else {
        availablePharmacyProducts.value = newProducts
      }
      
      // Update pagination info
      pharmacyProductsPagination.totalPages = response.data.last_page || 1
      pharmacyProductsPagination.hasMore = pharmacyProductsPagination.page < pharmacyProductsPagination.totalPages
      console.log(`📊 Pagination - Current: ${pharmacyProductsPagination.page}, Total: ${pharmacyProductsPagination.totalPages}, HasMore: ${pharmacyProductsPagination.hasMore}`)
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
    pharmacyProductsPagination.loading = false
  }
}

// Refs for dropdowns
const pharmacyDropdownRef = ref(null)
const stockDropdownRef = ref(null)
const attachedScrollHandlers = new Map() // Track which panels have listeners attached
let scrollDebounceTimer = null

// Debounced scroll handlers for infinite scroll in dropdowns
const onStockProductsScroll = async (event) => {
  // When user scrolls to the bottom of the list
  if (event && event.target) {
    const scrollTop = event.target.scrollTop
    const scrollHeight = event.target.scrollHeight
    const clientHeight = event.target.clientHeight
    
    // Calculate scroll percentage
    const scrollPercentage = ((scrollTop + clientHeight) / scrollHeight) * 100
    
    // If scrolled to bottom (within 50px or 85%), load more with debounce
    if (scrollHeight - scrollTop - clientHeight < 50 || scrollPercentage > 85) {
      if (stockProductsPagination.hasMore && !stockProductsPagination.loading) {
        // Clear existing timer
        if (scrollDebounceTimer) {
          clearTimeout(scrollDebounceTimer)
        }
        
        // Debounce to prevent multiple rapid calls
        scrollDebounceTimer = setTimeout(async () => {
          console.log('📦 Stock products: Loading next page', stockProductsPagination.page + 1, `at ${scrollPercentage.toFixed(1)}%`)
          stockProductsPagination.page++
          await loadStockProducts(stockProductsPagination.searchQuery, true)
        }, 300)
      }
    }
  }
}

const onPharmacyProductsScroll = async (event) => {
  // When user scrolls to the bottom of the list
  if (event && event.target) {
    const scrollTop = event.target.scrollTop
    const scrollHeight = event.target.scrollHeight
    const clientHeight = event.target.clientHeight
    
    // Calculate scroll percentage
    const scrollPercentage = ((scrollTop + clientHeight) / scrollHeight) * 100
    
    // If scrolled to bottom (within 50px or 85%), load more with debounce
    if (scrollHeight - scrollTop - clientHeight < 50 || scrollPercentage > 85) {
      if (pharmacyProductsPagination.hasMore && !pharmacyProductsPagination.loading) {
        // Clear existing timer
        if (scrollDebounceTimer) {
          clearTimeout(scrollDebounceTimer)
        }
        
        // Debounce to prevent multiple rapid calls
        scrollDebounceTimer = setTimeout(async () => {
          console.log('💊 Pharmacy products: Loading next page', pharmacyProductsPagination.page + 1, `at ${scrollPercentage.toFixed(1)}%`)
          pharmacyProductsPagination.page++
          await loadPharmacyProducts(pharmacyProductsPagination.searchQuery, true)
        }, 300)
      }
    }
  }
}

// Attach scroll listeners to dropdown panels
const attachScrollListeners = async () => {
  console.log('🔗 Attaching scroll listeners...')
  
  // Remove any existing listeners first
  removeScrollListeners()
  
  // Use nextTick and setTimeout to ensure DOM is ready
  await nextTick()
  
  // Try multiple times with increasing delays to catch the DOM when it's ready
  const attemptAttach = (delay = 200, maxAttempts = 5, currentAttempt = 1) => {
    setTimeout(() => {
      // Try multiple selector patterns for PrimeVue dropdown panels
      const selectors = [
        '.p-dropdown-panel .p-dropdown-items-wrapper',
        '.p-dropdown-panel .p-virtualscroller',
        '.p-dropdown-items-wrapper',
        '[role="listbox"]'
      ]
      
      let foundPanel = false
      
      for (const selector of selectors) {
        const panels = document.querySelectorAll(selector)
        
        panels.forEach(panel => {
          // Check if this is a visible panel and not already tracked
          const isVisible = panel.offsetParent !== null
          
          if (isVisible && !attachedScrollHandlers.has(panel)) {
            console.log(`✅ Found visible dropdown panel (${selector}) on attempt ${currentAttempt}`)
            foundPanel = true
            
            // Determine which type based on current state
            const isPharmacyOrder = selectedItemForProductSelection.value?.is_pharmacy_order ?? serviceDemandIsPharmacyOrder.value
            
            // Create debounced scroll handler
            let scrollTimer = null
            const scrollHandler = (e) => {
              // Clear previous timer
              if (scrollTimer) clearTimeout(scrollTimer)
              
              // Debounce scroll event
              scrollTimer = setTimeout(async () => {
                const scrollTop = e.target.scrollTop
                const scrollHeight = e.target.scrollHeight
                const clientHeight = e.target.clientHeight
                
                const scrollPercentage = ((scrollTop + clientHeight) / scrollHeight) * 100
                
                // If scrolled to bottom (within 50px or 85%), load more
                if (scrollHeight - scrollTop - clientHeight < 50 || scrollPercentage > 85) {
                  if (isPharmacyOrder) {
                    if (pharmacyProductsPagination.hasMore && !pharmacyProductsPagination.loading) {
                      console.log('� Pharmacy: Triggering load at', scrollPercentage.toFixed(1) + '%')
                      pharmacyProductsPagination.page++
                      await loadPharmacyProducts(pharmacyProductsPagination.searchQuery, true)
                    }
                  } else {
                    if (stockProductsPagination.hasMore && !stockProductsPagination.loading) {
                      console.log('📦 Stock: Triggering load at', scrollPercentage.toFixed(1) + '%')
                      stockProductsPagination.page++
                      await loadStockProducts(stockProductsPagination.searchQuery, true)
                    }
                  }
                }
              }, 200) // 200ms debounce
            }
            
            // Attach the listener with passive option for better performance
            panel.addEventListener('scroll', scrollHandler, { passive: true })
            attachedScrollHandlers.set(panel, scrollHandler)
            console.log('🎯 Scroll listener attached successfully')
          }
        })
        
        if (foundPanel) break
      }
      
      // Retry if panel not found and haven't exceeded max attempts
      if (!foundPanel && currentAttempt < maxAttempts) {
        console.log(`🔄 Panel not found, retrying (attempt ${currentAttempt + 1}/${maxAttempts})...`)
        attemptAttach(delay + 100, maxAttempts, currentAttempt + 1)
      } else if (!foundPanel) {
        console.warn('⚠️ Could not find dropdown panel after maximum attempts')
      }
    }, delay)
  }
  
  attemptAttach(200)
}

// Remove scroll listeners
const removeScrollListeners = () => {
  console.log('Removing scroll listeners...')
  attachedScrollHandlers.forEach((handler, panel) => {
    panel.removeEventListener('scroll', handler)
  })
  attachedScrollHandlers.clear()
}

// Lazy load handlers for infinite scroll - these are called by virtualScroller
const onStockProductsLazyLoad = async (event) => {
  // event.first = starting index, event.last = ending index
  // Only load more if we're near the end and have more pages
  if (event && event.last >= availableStockProducts.value.length - 10) {
    if (stockProductsPagination.hasMore && !stockProductsPagination.loading) {
      stockProductsPagination.page++
      await loadStockProducts(stockProductsPagination.searchQuery, true)
    }
  }
}

const onPharmacyProductsLazyLoad = async (event) => {
  // event.first = starting index, event.last = ending index
  // Only load more if we're near the end and have more pages
  if (event && event.last >= availablePharmacyProducts.value.length - 10) {
    if (pharmacyProductsPagination.hasMore && !pharmacyProductsPagination.loading) {
      pharmacyProductsPagination.page++
      await loadPharmacyProducts(pharmacyProductsPagination.searchQuery, true)
    }
  }
}

// Search handlers with debouncing - Note: Dropdown filter event uses 'value' property
let stockSearchDebounce = null
let pharmacySearchDebounce = null

const onStockProductsSearch = async (event) => {
  const searchQuery = event?.value || ''
  console.log('🔍 Stock search query:', searchQuery)
  
  stockProductsPagination.searchQuery = searchQuery
  stockProductsPagination.page = 1
  stockProductsPagination.hasMore = true
  
  // Clear existing debounce timer
  if (stockSearchDebounce) {
    clearTimeout(stockSearchDebounce)
  }
  
  // Debounce search to prevent excessive API calls
  stockSearchDebounce = setTimeout(async () => {
    console.log('🔍 Executing stock search for:', searchQuery)
    await loadStockProducts(searchQuery, false)
  }, 500)
}

const onPharmacyProductsSearch = async (event) => {
  const searchQuery = event?.value || ''
  console.log('🔍 Pharmacy search query:', searchQuery)
  
  pharmacyProductsPagination.searchQuery = searchQuery
  pharmacyProductsPagination.page = 1
  pharmacyProductsPagination.hasMore = true
  
  // Clear existing debounce timer
  if (pharmacySearchDebounce) {
    clearTimeout(pharmacySearchDebounce)
  }
  
  // Debounce search to prevent excessive API calls
  pharmacySearchDebounce = setTimeout(async () => {
    console.log('🔍 Executing pharmacy search for:', searchQuery)
    await loadPharmacyProducts(searchQuery, false)
  }, 500)
}

const addSelectedProductToOrder = async () => {
  // Determine which product is selected based on the service demand type
  const isPharmacyOrder = selectedItemForProductSelection?.is_pharmacy_order ?? serviceDemandIsPharmacyOrder.value
  const selectedProduct = isPharmacyOrder ? selectedPharmacyProduct.value : selectedStockProduct.value

  if (!selectedProduct || productSelectionQuantity.value < 1) {
    toast.add({
      severity: 'warning',
      summary: 'Warning',
      detail: 'Please select a product and enter a quantity',
      life: 3000
    })
    return
  }

  // Add product to the service demand items
  try {
    const payload = {
      product_id: isPharmacyOrder ? null : selectedProduct.id,
      pharmacy_product_id: isPharmacyOrder ? selectedProduct.id : null,
      quantity: productSelectionQuantity.value,
      unit: productSelectionUnit.value,
      quantity_by_box: productSelectionUnit.value === 'box',
      notes: `Added from product selection (${productSelectionUnit.value})`
    }

    // Make API call to add item to service demand
    const response = await axios.post(
      `/api/service-demands/${route.params.id}/items`,
      payload
    )

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Product "${selectedProduct.name}" added to order`,
        life: 3000
      })
      closeProductSelectionDialog()
      await fetchServiceDemand()
    }
  } catch (error) {
    console.error('Error adding product:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to add product to order',
      life: 3000
    })
  }
}

// ALL EXISTING UTILITY FUNCTIONS - UNCHANGED
// Helper function to get product name from either product or pharmacy_product
// Handles both nested objects (item.pharmacy_product/item.product) and direct product objects
const getProductName = (item) => {
  if (!item) return 'No Product'
  
  // Check for nested pharmacy_product object
  if (item.pharmacy_product) {
    return item.pharmacy_product.name || item.pharmacy_product.generic_name || item.pharmacy_product.brand_name || 'Unnamed Pharmacy Product'
  }
  
  // Check for nested product object
  if (item.product) {
    return item.product.name || item.product.designation || 'Unnamed Product'
  }
  
  // Handle direct pharmacy product (has pharmacy_product_id)
  if (item.pharmacy_product_id && (item.name || item.generic_name || item.brand_name)) {
    return item.name || item.generic_name || item.brand_name || 'Unnamed Pharmacy Product'
  }
  
  // Handle direct stock product (has product_id or code_interne)
  if ((item.product_id || item.code_interne) && (item.name || item.designation)) {
    return item.name || item.designation || 'Unnamed Product'
  }
  
  // Fallback: check for any name-like property
  if (item.name) return item.name
  if (item.designation) return item.designation
  if (item.generic_name) return item.generic_name
  if (item.brand_name) return item.brand_name
  
  return 'No Product'
}

// Helper function to get product code from either product or pharmacy_product
// Handles both nested objects and direct product objects
const getProductCode = (item) => {
  if (!item) return 'N/A'
  
  // Check for nested pharmacy_product object
  if (item.pharmacy_product) {
    return item.pharmacy_product.sku || item.pharmacy_product.barcode || 'N/A'
  }
  
  // Check for nested product object
  if (item.product) {
    return item.product.code_interne || item.product.code || 'N/A'
  }
  
  // Handle direct pharmacy product (has pharmacy_product_id or sku/barcode)
  if (item.pharmacy_product_id || item.sku || item.barcode) {
    return item.sku || item.barcode || 'N/A'
  }
  
  // Handle direct stock product (has product_id or code_interne/code)
  if (item.product_id || item.code_interne || item.code) {
    return item.code_interne || item.code || 'N/A'
  }
  
  // Fallback: check for any code-like property
  if (item.sku) return item.sku
  if (item.barcode) return item.barcode
  if (item.code_interne) return item.code_interne
  if (item.code) return item.code
  
  return 'N/A'
}

const getAssignmentStatus = (product) => {
  const assignmentCount = product.fournisseur_assignments?.length || 0
  if (assignmentCount === 0) return 'Not Assigned'
  if (assignmentCount === 1) return '1 Supplier'
  return `${assignmentCount} Suppliers`
}

const getAssignmentStatusSeverity = (product) => {
  const assignmentCount = product.fournisseur_assignments?.length || 0
  if (assignmentCount === 0) return 'danger'
  if (assignmentCount === 1) return 'info'
  return 'success'
}

const getStatusSeverity = (status) => {
  const severityMap = {
    'draft': 'secondary',
    'sent': 'info',
    'approved': 'success',
    'rejected': 'danger',
    'completed': 'success',
    'factureprofram': 'warning',
    'boncommend': 'success'
  }
  return severityMap[status] || 'secondary'
}

const getStatusIcon = (status) => {
  const iconMap = {
    'draft': 'pi pi-pencil',
    'sent': 'pi pi-send',
    'approved': 'pi pi-check-circle',
    'rejected': 'pi pi-times-circle',
    'completed': 'pi pi-verified',
    'factureprofram': 'pi pi-file',
    'boncommend': 'pi pi-truck'
  }
  return iconMap[status] || 'pi pi-circle'
}

// Function to get row class for product type color coding
const getProductRowClass = (data) => {
  if (data.pharmacy_product_id) {
    return 'pharmacy-product-row'
  } else {
    return 'stock-product-row'
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-CM', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

// Utility function to check if supplier has best price
const isBestPriceSupplier = (supplier) => {
  const suppliersWithPricing = enhancedSuppliers.value.filter(s => s.averagePrice)
  if (suppliersWithPricing.length === 0) return false

  const bestPrice = Math.min(...suppliersWithPricing.map(s => s.averagePrice))
  return supplier.averagePrice === bestPrice
}

// Computed properties for pricing history - UNCHANGED
const filteredHistoryData = computed(() => {
  let filtered = [...(pricingHistoryData.value || [])]

  // Apply date filters
  if (historyFilters.startDate) {
    filtered = filtered.filter(item => {
      const itemDate = new Date(item.order_date || item.created_at)
      return itemDate >= historyFilters.startDate
    })
  }

  if (historyFilters.endDate) {
    filtered = filtered.filter(item => {
      const itemDate = new Date(item.order_date || item.created_at)
      return itemDate <= historyFilters.endDate
    })
  }

  // Apply order type filter
  if (historyFilters.orderType) {
    filtered = filtered.filter(item => item.order_type === historyFilters.orderType)
  }

  return filtered
})

const historyStats = computed(() => {
  const data = filteredHistoryData.value
  if (!data.length) {
    return {
      totalOrders: 0,
      averagePrice: 0,
      minPrice: 0,
      maxPrice: 0,
      totalQuantity: 0
    }
  }

  const prices = data.map(item => parseFloat(item.price) || 0)
  const quantities = data.map(item => parseInt(item.quantity) || 0)

  return {
    totalOrders: data.length,
    averagePrice: prices.reduce((sum, price) => sum + price, 0) / prices.length,
    minPrice: Math.min(...prices),
    maxPrice: Math.max(...prices),
    totalQuantity: quantities.reduce((sum, qty) => sum + qty, 0)
  }
})

// TIMELINE COMPUTED PROPERTY AND HELPERS
const timelineEvents = computed(() => {
  if (!serviceDemand.value) return []

  const events = []

  // 1. Service Demand Creation
  events.push({
    date: serviceDemand.value.created_at,
    title: 'Service Demand Created',
    content: `Demand ${serviceDemand.value.demand_code} was created for ${serviceDemand.value.service?.name || 'Unknown Service'}`,
    icon: 'pi pi-file',
    color: '#10b981',
    type: 'creation',
    user: 'System'
  })

  // 2. Service Demand General Notes (if any)
  if (serviceDemand.value.notes) {
    // Parse workflow notes (timestamped)
    const workflowNotes = parseWorkflowNotes(serviceDemand.value.notes)
    workflowNotes.forEach(note => {
      events.push({
        date: note.timestamp,
        title: 'Workflow Note Added',
        content: note.content,
        icon: 'pi pi-comment',
        color: '#3b82f6',
        type: 'workflow',
        user: 'System'
      })
    })

    // If there are non-timestamped notes, add as general notes
    const generalNotes = extractGeneralNotes(serviceDemand.value.notes)
    if (generalNotes) {
      events.push({
        date: serviceDemand.value.created_at,
        title: 'General Notes Added',
        content: generalNotes,
        icon: 'pi pi-sticky-note',
        color: '#8b5cf6',
        type: 'general',
        user: 'System'
      })
    }
  }

  // 3. Item-level notes and assignments
  serviceDemand.value.items?.forEach(item => {
    // Item creation/update notes
    if (item.notes) {
      events.push({
        date: item.created_at,
        title: `Product Notes: ${item.product?.name}`,
        content: item.notes,
        icon: 'pi pi-box',
        color: '#f59e0b',
        type: 'item',
        user: 'System',
        metadata: [
          { label: 'Product', value: item.product?.name || 'Unknown' },
          { label: 'Quantity', value: `${item.quantity} ${item.quantity_by_box ? 'Boxes' : 'Units'}` }
        ]
      })
    }

    // Supplier assignments
    item.fournisseur_assignments?.forEach(assignment => {
      events.push({
        date: assignment.created_at,
        title: `Supplier Assigned: ${assignment.fournisseur?.company_name}`,
        content: assignment.notes || `Assigned ${assignment.assigned_quantity} ${assignment.unit || 'units'} at ${formatCurrency(assignment.unit_price || 0)} each`,
        icon: 'pi pi-user-plus',
        color: '#06b6d4',
        type: 'assignment',
        user: assignment.assignedBy?.name || 'Unknown',
        metadata: [
          { label: 'Supplier', value: assignment.fournisseur?.company_name || 'Unknown' },
          { label: 'Quantity', value: assignment.assigned_quantity },
          { label: 'Unit Price', value: formatCurrency(assignment.unit_price || 0) },
          { label: 'Status', value: assignment.status }
        ]
      })
    })
  })

  // 4. Status changes
  if (serviceDemand.value.status !== 'draft') {
    events.push({
      date: serviceDemand.value.updated_at,
      title: `Status Changed to ${serviceDemand.value.status}`,
      content: `Service demand status was updated to ${serviceDemand.value.status}`,
      icon: 'pi pi-refresh',
      color: '#ef4444',
      type: 'status',
      user: 'System'
    })
  }

  // Sort events by date (newest first for timeline display)
  return events.sort((a, b) => new Date(b.date) - new Date(a.date))
})

// Helper function to parse workflow notes with timestamps
const parseWorkflowNotes = (notes) => {
  if (!notes) return []

  const workflowNotes = []
  const lines = notes.split('\n')

  lines.forEach(line => {
    // Look for timestamped notes (format: YYYY-MM-DD HH:MM:SS - note content)
    const timestampMatch = line.match(/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) - (.+)$/)
    if (timestampMatch) {
      workflowNotes.push({
        timestamp: timestampMatch[1],
        content: timestampMatch[2]
      })
    }
  })

  return workflowNotes
}

// Helper function to extract general notes (non-timestamped)
const extractGeneralNotes = (notes) => {
  if (!notes) return null

  const lines = notes.split('\n')
  const generalLines = lines.filter(line => !line.match(/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) - (.+)$/))

  return generalLines.length > 0 ? generalLines.join('\n') : null
}

// Helper function for timeline type severity
const getTypeSeverity = (type) => {
  const severityMap = {
    'creation': 'success',
    'workflow': 'info',
    'general': 'secondary',
    'item': 'warning',
    'assignment': 'info',
    'status': 'danger'
  }
  return severityMap[type] || 'secondary'
}

// ALL EXISTING PRICING HISTORY FUNCTIONS - UNCHANGED
const viewSupplierHistory = async (supplier) => {
  // Determine product type and get correct product ID
  const isPharmacy = !!selectedProduct.value?.pharmacy_product_id
  const productId = isPharmacy ? selectedProduct.value?.pharmacy_product_id : selectedProduct.value?.product_id

  if (!productId) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'No product selected for history view',
      life: 3000
    })
    return
  }

  // Both pharmacy and stock products can now show history
  selectedHistorySupplier.value = supplier
  selectedHistoryProduct.value = selectedProduct.value
  historyDialog.value = true

  await fetchSupplierHistory(productId, supplier?.id, isPharmacy)
}

const fetchSupplierHistory = async (productId, supplierId, isPharmacy = false) => {
  try {
    loadingHistory.value = true
    
    // Choose the appropriate endpoint based on product type
    let endpoint
    if (isPharmacy) {
      // Pharmacy products have their own pricing history endpoint (movement history)
      endpoint = `/api/pharmacy-products/${productId}/pricing-history`
    } else {
      // Stock products use supplier pricing history
      endpoint = `/api/products/${productId}/supplier/${supplierId}/history`
    }
    
    const response = await axios.get(endpoint)

    if (response.data.success) {
      // For pharmacy products, format the movement history data
      if (isPharmacy && response.data.data) {
        const pharmacyData = response.data.data
        // Transform pharmacy movement history to match the expected format
        pricingHistoryData.value = pharmacyData.movement_history?.map(item => ({
          id: item.movement_id,
          order_date: item.created_at,
          movement_type: item.movement_type,
          status: item.status,
          providing_service: item.providing_service,
          requesting_service: item.requesting_service,
          requested_quantity: item.requested_quantity,
          approved_quantity: item.approved_quantity,
          executed_quantity: item.executed_quantity,
          provided_quantity: item.provided_quantity,
          received_quantity: item.received_quantity,
          notes: item.notes,
          // Add current pricing info
          current_unit_cost: pharmacyData.current_pricing?.unit_cost,
          current_selling_price: pharmacyData.current_pricing?.selling_price,
          current_markup: pharmacyData.current_pricing?.markup_percentage,
        })) || []
      } else {
        // Stock product history
        pricingHistoryData.value = response.data.data || []
      }
    } else {
      throw new Error(response.data.message || 'Failed to fetch history')
    }
  } catch (error) {
    console.error('Error fetching history:', error)
    
    // Show warning if no history found
    if (error.response?.status === 404) {
      toast.add({
        severity: 'info',
        summary: 'No History',
        detail: `No ${isPharmacy ? 'movement' : 'pricing'} history found for this product`,
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load history',
        life: 3000
      })
    }
    pricingHistoryData.value = []
  } finally {
    loadingHistory.value = false
  }
}

const applyHistoryFilters = () => {
  // Filters are applied automatically via computed property
  toast.add({
    severity: 'info',
    summary: 'Filters Applied',
    detail: `Showing ${filteredHistoryData.value.length} records`,
    life: 2000
  })
}

const clearHistoryFilters = () => {
  historyFilters.startDate = null
  historyFilters.endDate = null
  historyFilters.orderType = null

  toast.add({
    severity: 'info',
    summary: 'Filters Cleared',
    detail: 'All filters have been reset',
    life: 2000
  })
}

const exportHistory = () => {
  if (!filteredHistoryData.value.length) {
    toast.add({
      severity: 'warn',
      summary: 'No Data',
      detail: 'No data available to export',
      life: 3000
    })
    return
  }

  try {
    // Create CSV content
    const headers = ['Date', 'Type', 'Unit Price', 'Quantity', 'Total Amount', 'Reference', 'Notes']
    const csvContent = [
      headers.join(','),
      ...filteredHistoryData.value.map(item => [
        formatDate(item.order_date || item.created_at),
        item.order_type || 'Receipt',
        item.price || 0,
        item.quantity || 0,
        (parseFloat(item.price || 0) * parseInt(item.quantity || 0)).toFixed(2),
        `"${item.document_reference || item.bon_reception_code || 'N/A'}"`,
        `"${item.notes || item.observations || ''}"`
      ].join(','))
    ].join('\n')

    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    const productName = getProductName(selectedHistoryProduct.value)
    const supplierName = selectedHistorySupplier.value?.company_name || 'unknown'
    link.setAttribute('download', `pricing_history_${productName}_${supplierName}_${new Date().toISOString().split('T')[0]}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    toast.add({
      severity: 'success',
      summary: 'Export Complete',
      detail: 'Pricing history exported successfully',
      life: 3000
    })
  } catch (error) {
    console.error('Export error:', error)
    toast.add({
      severity: 'error',
      summary: 'Export Failed',
      detail: 'Failed to export pricing history',
      life: 3000
    })
  }
}

// New UI helper function
const exportPDF = () => {
  toast.add({
    severity: 'info',
    summary: 'Export PDF',
    detail: 'PDF export feature coming soon',
    life: 3000
  })
}

// Lifecycle - UNCHANGED
onMounted(async () => {
  await Promise.all([
    fetchServiceDemand(),
    fetchSuppliers()
  ])
})

// Watch for dialog visibility to attach scroll listeners
watch(showProductSelectionDialog, async (newVal) => {
  if (newVal) {
    // Dialog opened - attach scroll listeners after a delay
    await nextTick()
    setTimeout(() => {
      attachScrollListeners()
    }, 500)
  } else {
    // Dialog closed - remove scroll listeners
    removeScrollListeners()
  }
})
</script>

<template>
  <div class="service-demand-detail tw-min-h-screen tw-bg-gray-50">
    <!-- Enhanced Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-min-h-screen">
      <div class="tw-text-center">
        <div class="tw-relative tw-inline-flex">
          <div class="tw-w-24 tw-h-24 tw-bg-gray-200 tw-rounded-full tw-animate-ping tw-absolute tw-opacity-75"></div>
          <div class="tw-w-24 tw-h-24 tw-bg-gray-300 tw-rounded-full tw-animate-pulse tw-flex tw-items-center tw-justify-center">
            <i class="pi pi-shopping-cart tw-text-gray-700 tw-text-3xl"></i>
          </div>
        </div>
        <p class="tw-mt-4 tw-text-lg tw-font-medium tw-text-gray-700">Loading Service Demand...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else-if="serviceDemand" class="tw-container tw-mx-auto tw-px-4 tw-py-6">
      <!-- Modern Header Section -->
      <div class="tw-bg-gray-800 tw-rounded-2xl tw-shadow-2xl tw-p-8 tw-mb-8 tw-text-white tw-relative tw-overflow-hidden">
        <!-- Background Pattern -->
        <div class="tw-absolute tw-inset-0 tw-opacity-10">
          <div class="tw-absolute tw-transform tw-rotate-45 tw--right-20 tw--top-20 tw-w-80 tw-h-80 tw-bg-white tw-rounded-full"></div>
          <div class="tw-absolute tw-transform tw-rotate-45 tw--left-20 tw--bottom-20 tw-w-80 tw-h-80 tw-bg-white tw-rounded-full"></div>
        </div>

        <!-- Header Content -->
        <div class="tw-relative tw-z-10">
          <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-mb-6">
            <div>
              <h1 class="tw-text-4xl tw-font-bold tw-mb-2 tw-flex tw-items-center">
                <i class="pi pi-shopping-cart tw-mr-4"></i>
                {{ serviceDemand.demand_code }}
              </h1>
              <p class="tw-text-xl tw-text-white/90">Service Demand Management System</p>
            </div>

            <!-- Quick Stats -->
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4 tw-mt-4 lg:tw-mt-0">
              <div class="tw-bg-white/20 tw-backdrop-blur tw-rounded-xl tw-p-4 tw-text-center">
                <div class="tw-text-3xl tw-font-bold">{{ serviceDemand.items?.length || 0 }}</div>
                <div class="tw-text-sm tw-text-white/80">Products</div>
              </div>
              <div class="tw-bg-white/20 tw-backdrop-blur tw-rounded-xl tw-p-4 tw-text-center">
                <div class="tw-text-3xl tw-font-bold">{{ totalAssignments }}</div>
                <div class="tw-text-sm tw-text-white/80">Assignments</div>
              </div>
              <div class="tw-bg-white/20 tw-backdrop-blur tw-rounded-xl tw-p-4 tw-text-center">
                <div class="tw-text-3xl tw-font-bold">{{ unassignedItems }}</div>
                <div class="tw-text-sm tw-text-white/80">Unassigned</div>
              </div>
              <div class="tw-bg-white/20 tw-backdrop-blur tw-rounded-xl tw-p-4 tw-text-center">
                <div class="tw-text-3xl tw-font-bold">{{ assignmentProgress }}%</div>
                <div class="tw-text-sm tw-text-white/80">Progress</div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button 
              v-if="showProformaButton"
              @click="saveProforma"
              :disabled="!hasAssignments || savingData"
              :loading="savingData"
              icon="pi pi-save"
              label="Save Proforma"
              class="p-button-lg tw-bg-white/20 tw-backdrop-blur tw-border-2 tw-border-white/30 hover:tw-bg-white/30 tw-transition-all"
            />
            <Button 
              v-if="showBonCommendButton"
              @click="saveBonCommend"
              :disabled="!hasAssignments || savingData"
              :loading="savingData"
              icon="pi pi-file-export"
              label="Save Bon Commend"
              class="p-button-lg tw-bg-yellow-400/90 tw-text-gray-800 hover:tw-bg-yellow-300 tw-transition-all"
            />
            <Button 
              @click="refreshData"
              icon="pi pi-refresh"
              label="Refresh"
              class="p-button-lg p-button-outlined tw-text-white tw-border-white/50 hover:tw-bg-white/20"
            />
            <Button 
              @click="exportPDF"
              icon="pi pi-file-pdf"
              label="Export PDF"
              class="p-button-lg p-button-outlined tw-text-white tw-border-white/50 hover:tw-bg-white/20"
            />
          </div>
        </div>
      </div>

      <!-- Service Info Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300 tw-transform hover:tw--translate-y-1">
          <template #content>
            <div class="tw-bg-gray-700 tw-rounded-xl tw-p-6 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <i class="pi pi-building tw-text-4xl tw-opacity-80"></i>
                <Tag :value="serviceDemand.status" :severity="getStatusSeverity(serviceDemand.status)" />
              </div>
              <div class="tw-text-xs tw-uppercase tw-tracking-wider tw-opacity-80">Service</div>
              <div class="tw-text-xl tw-font-bold tw-mt-1">{{ serviceDemand.service?.name || 'No Service' }}</div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300 tw-transform hover:tw--translate-y-1">
          <template #content>
            <div class="tw-bg-gray-700 tw-rounded-xl tw-p-6 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <i class="pi pi-calendar tw-text-4xl tw-opacity-80"></i>
                <i class="pi pi-clock tw-text-xl tw-opacity-60"></i>
              </div>
              <div class="tw-text-xs tw-uppercase tw-tracking-wider tw-opacity-80">Expected Date</div>
              <div class="tw-text-xl tw-font-bold tw-mt-1">{{ formatDate(serviceDemand.expected_date) }}</div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300 tw-transform hover:tw--translate-y-1">
          <template #content>
            <div class="tw-bg-gray-700 tw-rounded-xl tw-p-6 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <i class="pi pi-box tw-text-4xl tw-opacity-80"></i>
                <Badge :value="serviceDemand.items?.length || 0" severity="danger" class="tw-scale-125" />
              </div>
              <div class="tw-text-xs tw-uppercase tw-tracking-wider tw-opacity-80">Total Items</div>
              <div class="tw-text-xl tw-font-bold tw-mt-1">{{ serviceDemand.items?.length || 0 }} Products</div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-0 tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300 tw-transform hover:tw--translate-y-1">
          <template #content>
            <div class="tw-bg-gray-700 tw-rounded-xl tw-p-6 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <i class="pi pi-chart-line tw-text-4xl tw-opacity-80"></i>
                <ProgressBar :value="assignmentProgress" :showValue="false" class="tw-w-20" />
              </div>
              <div class="tw-text-xs tw-uppercase tw-tracking-wider tw-opacity-80">Progress</div>
              <div class="tw-text-xl tw-font-bold tw-mt-1">{{ assignmentProgress }}% Complete</div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Notes Section (if exists) -->
      <Card v-if="serviceDemand.notes" class="tw-border-0 tw-shadow-xl tw-mb-8">
        <template #title>
          <div class="tw-flex tw-items-center tw-text-gray-800">
            <i class="pi pi-comment tw-mr-3 tw-text-purple-600"></i>
            <span>Notes & Comments</span>
          </div>
        </template>
        <template #content>
          <div class="tw-bg-gray-50 tw-rounded-xl tw-p-6">
            <p class="tw-text-gray-700 tw-leading-relaxed">{{ serviceDemand.notes }}</p>
          </div>
        </template>
      </Card>

      <!-- Tabbed Content Area -->
      <TabView class="tw-shadow-2xl tw-rounded-xl tw-bg-white">
        <TabPanel>
          <template #header>
            <i class="pi pi-list tw-mr-2"></i>
            Products & Assignments
            <Badge :value="serviceDemand.items?.length || 0" class="tw-ml-2" severity="info" />
          </template>

          <!-- Add Product Button Section -->
          <div class="tw-flex tw-items-center tw-justify-between tw-mb-6 tw-p-4 tw-bg-gray-50 tw-rounded-xl tw-border tw-border-gray-200">
            <div>
              <h3 class="tw-text-lg tw-font-bold tw-text-gray-900">📦 Products in Order</h3>
              <p class="tw-text-sm tw-text-gray-600 tw-mt-1">{{ serviceDemand.items?.length || 0 }} product(s) added</p>
            </div>
            <Button
              @click="openProductSelectionDialog(null)"
              icon="pi pi-plus"
              label="Add Product"
              class="tw-bg-green-600 tw-text-white tw-border-0 tw-rounded-xl tw-px-4 tw-py-2"
              severity="success"
            />
          </div>

          <!-- Enhanced Products Table -->
          <DataTable 
            :value="serviceDemand.items"
            v-model:expandedRows="expandedRows"
            responsiveLayout="scroll"
            :paginator="true"
            :rows="10"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            dataKey="id"
            class="tw-border-0 enhanced-products-table"
            :rowClass="getProductRowClass"
          >
            <!-- Expander Column -->
            <Column :expander="true" style="width: 3rem" />

            <!-- Product Information -->
            <Column field="product.name" header="Product" :sortable="true" class="tw-min-w-[250px]">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar 
                    :label="getProductName(data).charAt(0)" 
                    :class="data.pharmacy_product ? 'tw-bg-purple-500 tw-text-white' : 'tw-bg-indigo-500 tw-text-white'"
                    shape="circle"
                    size="large"
                  />
                  <div>
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-lg">{{ getProductName(data) }}</div>
                    <div class="tw-text-sm tw-text-gray-500">
                      <i :class="data.pharmacy_product ? 'pi pi-pills' : 'pi pi-tag'" class="tw-mr-1 tw-text-xs"></i>
                      {{ data.pharmacy_product ? 'SKU' : 'Code' }}: {{ getProductCode(data) }}
                      <span v-if="data.pharmacy_product" class="tw-ml-2 tw-px-2 tw-py-0.5 tw-bg-purple-100 tw-text-purple-700 tw-rounded-full tw-text-xs tw-font-medium">
                        Pharmacy
                      </span>
                      <span v-else class="tw-ml-2 tw-px-2 tw-py-0.5 tw-bg-indigo-100 tw-text-indigo-700 tw-rounded-full tw-text-xs tw-font-medium">
                        Stock
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Quantity & Unit -->
            <Column header="Quantity" class="tw-min-w-[150px]">
              <template #body="{ data }">
                <div class="tw-text-center">
                  <div class="tw-inline-flex tw-items-center tw-rounded-full tw-px-4 tw-py-2">
                    <span class="tw-text-2xl tw-font-bold tw-mr-2">{{ data.quantity }}</span>
                    <span class="tw-text-sm tw-font-medium">{{ data.quantity_by_box ? 'Boxes' : 'Units' }}</span>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Assignment Status -->
            <Column header="Status" class="tw-min-w-[180px]">
              <template #body="{ data }">
                <div class="tw-text-center">
                  <Tag 
                    :value="getAssignmentStatus(data)"
                    :severity="getAssignmentStatusSeverity(data)"
                    :icon="data.fournisseur_assignments?.length > 0 ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'"
                    class="tw-px-4 tw-py-2"
                  />
                  <div class="tw-mt-2">
                    <span class="tw-text-sm tw-text-gray-600">
                      {{ data.fournisseur_assignments?.length || 0 }} supplier(s)
                    </span>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Assigned Suppliers Preview -->
            <Column header="Assigned Suppliers" class="tw-min-w-[300px]">
              <template #body="{ data }">
                <div class="tw-flex tw-flex-wrap tw-gap-2">
                  <Chip 
                    v-for="assignment in data.fournisseur_assignments?.slice(0, 3)" 
                    :key="assignment.id"
                    :label="assignment.fournisseur?.company_name"
                    class="tw-bg-gray-100 tw-text-gray-800"
                  />
                  <Chip 
                    v-if="data.fournisseur_assignments?.length > 3"
                    :label="`+${data.fournisseur_assignments.length - 3} more`"
                    class="tw-bg-gray-200 tw-text-gray-600"
                  />
                  <span v-if="!data.fournisseur_assignments?.length" class="tw-text-gray-400 tw-italic">
                    No suppliers assigned
                  </span>
                </div>
              </template>
            </Column>

            <!-- Actions -->
            <Column header="Actions" class="tw-min-w-[200px]">
              <template #body="{ data }">
                <div class="tw-flex tw-gap-2 tw-justify-center">
                  <Button
                    @click="showSupplierDialog(data)"
                    icon="pi pi-plus"
                    label="Assign"
                    class="p-button-rounded p-button-success"
                    v-tooltip.top="'Assign suppliers to this product'"
                  />
                </div>
              </template>
            </Column>

            <!-- Expansion Template -->
            <template #expansion="{ data }">
              <div class="tw-p-6 tw-bg-gray-50">
                <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center">
                  <i class="pi pi-users tw-mr-2 tw-text-gray-600"></i>
                  Supplier Assignments Details
                </h4>
                <div v-if="data.fournisseur_assignments?.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
                  <div 
                    v-for="assignment in data.fournisseur_assignments" 
                    :key="assignment.id"
                    class="tw-bg-white tw-rounded-xl tw-p-4 tw-shadow-lg hover:tw-shadow-xl tw-transition-all"
                  >
                    <div class="tw-flex tw-items-start tw-justify-between">
                      <div class="tw-flex-1">
                        <div class="tw-flex tw-items-center tw-mb-2">
                          <Avatar 
                            :label="assignment.fournisseur?.company_name?.charAt(0)" 
                            class="tw-bg-gray-400 tw-text-white tw-mr-3"
                          />
                          <div>
                            <div class="tw-font-semibold tw-text-gray-900">{{ assignment.fournisseur?.company_name }}</div>
                            <div class="tw-text-xs tw-text-gray-500">{{ assignment.fournisseur?.contact_person }}</div>
                          </div>
                        </div>
                        <div class="tw-space-y-1 tw-text-sm">
                          <div class="tw-flex tw-justify-between">
                            <span class="tw-text-gray-600">Quantity:</span>
                            <span class="tw-font-medium">{{ assignment.assigned_quantity }} {{ assignment.unit || '' }}</span>
                          </div>
                          <div v-if="assignment.notes" class="tw-mt-2">
                            <span class="tw-text-gray-600">Notes:</span>
                            <p class="tw-text-xs tw-text-gray-700 tw-mt-1">{{ assignment.notes }}</p>
                          </div>
                        </div>
                      </div>
                      <div class="tw-flex tw-flex-col tw-gap-1 tw-ml-2">
                        <Button
                          @click="editAssignment(data, assignment)"
                          icon="pi pi-pencil"
                          class="p-button-rounded p-button-text p-button-sm tw-text-blue-600"
                          v-tooltip.top="'Edit assignment'"
                        />
                        <Button
                          @click="removeAssignment(data.id, assignment.id)"
                          icon="pi pi-trash"
                          class="p-button-rounded p-button-text p-button-sm tw-text-red-600"
                          v-tooltip.top="'Remove assignment'"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
                  <i class="pi pi-inbox tw-text-5xl tw-text-gray-300 tw-mb-3"></i>
                  <p>No suppliers have been assigned to this product yet.</p>
                  <Button
                    @click="showSupplierDialog(data)"
                    label="Assign First Supplier"
                    icon="pi pi-plus"
                    class="p-button-success tw-mt-4"
                  />
                </div>
              </div>
            </template>
          </DataTable>
        </TabPanel>

        <!-- Timeline Tab (Optional Enhancement) -->
        <TabPanel>
          <template #header>
            <i class="pi pi-history tw-mr-2"></i>
            Activity Timeline
          </template>
          <div class="tw-p-6">
            <Timeline :value="timelineEvents" align="alternate" class="tw-mt-6">
              <template #marker="slotProps">
                <span class="tw-flex tw-w-10 tw-h-10 tw-items-center tw-justify-center tw-text-white tw-rounded-full tw-shadow-lg"
                      :style="{ backgroundColor: slotProps.item.color }">
                  <i :class="`pi ${slotProps.item.icon}`"></i>
                </span>
              </template>
              <template #content="slotProps">
                <Card class="tw-shadow-lg tw-border-0">
                  <template #content>
                    <div class="tw-flex tw-items-start tw-justify-between tw-mb-2">
                      <h4 class="tw-font-semibold tw-text-gray-900 tw-flex tw-items-center tw-gap-2">
                        <i :class="`pi ${slotProps.item.icon} tw-text-sm`" :style="{ color: slotProps.item.color }"></i>
                        {{ slotProps.item.title }}
                      </h4>
                      <Badge :value="slotProps.item.type" :severity="getTypeSeverity(slotProps.item.type)" class="tw-text-xs" />
                    </div>
                    <p class="tw-text-sm tw-text-gray-600 tw-mb-3">{{ formatDate(slotProps.item.date) }}</p>
                    <div v-if="slotProps.item.user" class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
                      <Avatar :label="slotProps.item.user.charAt(0)" class="tw-bg-gray-500 tw-text-white" size="small" />
                      <span class="tw-text-xs tw-text-gray-500">{{ slotProps.item.user }}</span>
                    </div>
                    <div class="tw-bg-gray-50 tw-rounded-lg tw-p-3">
                      <p class="tw-text-sm tw-text-gray-700 tw-whitespace-pre-wrap">{{ slotProps.item.content }}</p>
                    </div>
                    <div v-if="slotProps.item.metadata" class="tw-mt-3 tw-pt-3 tw-border-t tw-border-gray-200">
                      <div class="tw-flex tw-flex-wrap tw-gap-2">
                        <Badge v-for="meta in slotProps.item.metadata" :key="meta.key" 
                               :value="`${meta.label}: ${meta.value}`" 
                               severity="info" class="tw-text-xs" />
                      </div>
                    </div>
                  </template>
                </Card>
              </template>
            </Timeline>
          </div>
        </TabPanel>
      </TabView>

      <!-- Enhanced Supplier Assignment Dialog -->
<Dialog 
  :visible="supplierDialog" 
  @update:visible="supplierDialog = $event"
  modal 
  :header="null"
  :style="{ width: '90vw', maxWidth: '1200px' }"
  :breakpoints="{'960px': '95vw', '640px': '100vw'}"
  :closable="!loadingSupplierData"
  class="supplier-assignment-dialog"
>
  <!-- Custom Header with Gradient -->
  <template #header>
    <div class="tw-bg-gray-800 tw--m-8 tw-p-6 tw-rounded-t-xl">
      <div class="tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-4">
          <div class="tw-bg-white/20 tw-backdrop-blur tw-p-3 tw-rounded-xl">
            <i class="pi pi-users tw-text-2xl tw-text-white"></i>
          </div>
          <div>
            <h2 class="tw-text-2xl tw-font-bold tw-text-white">Assign Suppliers</h2>
            <div class="tw-text-white/90 tw-mt-1">
              <i :class="selectedProduct?.pharmacy_product ? 'pi pi-pills' : 'pi pi-box'" class="tw-mr-2"></i>
              <span class="tw-font-medium">{{ selectedProduct ? getProductName(selectedProduct) : 'Product' }}</span>
              <span v-if="selectedProduct?.pharmacy_product" class="tw-ml-2 tw-px-2 tw-py-0.5 tw-bg-purple-300 tw-text-purple-900 tw-rounded-full tw-text-xs tw-font-semibold">
                Pharmacy
              </span>
              <span v-else class="tw-ml-2 tw-px-2 tw-py-0.5 tw-bg-indigo-300 tw-text-indigo-900 tw-rounded-full tw-text-xs tw-font-semibold">
                Stock
              </span>
              <span v-if="selectedProduct?.quantity" class="tw-ml-3">
                <span class="tw-bg-white/20 tw-px-2 tw-py-1 tw-rounded-full tw-text-sm">
                  {{ selectedProduct.quantity }} {{ selectedProduct.quantity_by_box ? 'Boxes' : 'Units' }} required
                </span>
              </span>
            </div>
          </div>
        </div>
        <Button 
          v-if="!loadingSupplierData"
          icon="pi pi-times" 
          @click="supplierDialog = false"
          class="p-button-rounded p-button-text tw-text-white hover:tw-bg-white/20"
        />
      </div>
    </div>
  </template>

  <!-- Loading State -->
  <div v-if="loadingSupplierData" class="tw-py-16">
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center">
      <div class="tw-relative">
        <div class="tw-w-20 tw-h-20 tw-bg-gradient-to-tr tw-from-purple-400 tw-to-pink-400 tw-rounded-full tw-animate-ping tw-absolute tw-opacity-75"></div>
        <div class="tw-w-20 tw-h-20 tw-bg-gradient-to-tr tw-from-purple-500 tw-to-pink-500 tw-rounded-full tw-animate-pulse tw-flex tw-items-center tw-justify-center">
          <i class="pi pi-sync tw-text-white tw-text-2xl tw-animate-spin"></i>
        </div>
      </div>
      <p class="tw-mt-6 tw-text-gray-600 tw-font-medium">Loading supplier pricing data...</p>
      <p class="tw-text-sm tw-text-gray-500 tw-mt-2">Fetching latest prices and ratings</p>
    </div>
  </div>

  <!-- Main Content -->
  <div v-else class="tw-space-y-6 tw-p-2">
    <!-- Product Summary Card -->
    <Card class="tw-border-0 tw-shadow-lg tw-bg-gray-50">
      <template #content>
        <div class="tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-4">
            <Avatar 
              :label="selectedProduct ? getProductName(selectedProduct).charAt(0) : 'P'" 
              :class="selectedProduct?.pharmacy_product ? 'tw-bg-gradient-to-r tw-from-purple-500 tw-to-pink-500 tw-text-white' : 'tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-text-white'"
              size="xlarge"
              shape="circle"
            />
            <div>
              <p class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wider">
                <i :class="selectedProduct?.pharmacy_product ? 'pi pi-pills' : 'pi pi-box'" class="tw-mr-1"></i>
                {{ selectedProduct?.pharmacy_product ? 'Pharmacy Product Details' : 'Stock Product Details' }}
              </p>
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-900">{{ selectedProduct ? getProductName(selectedProduct) : 'Product' }}</h3>
              <div class="tw-flex tw-items-center tw-gap-4 tw-mt-2">
                <span class="tw-text-sm tw-text-gray-600">
                  <i class="pi pi-tag tw-mr-1"></i>
                  {{ selectedProduct?.pharmacy_product ? 'SKU' : 'Code' }}: {{ selectedProduct ? getProductCode(selectedProduct) : 'N/A' }}
                </span>
                <Badge :value="`${selectedProduct?.quantity || 0} ${selectedProduct?.quantity_by_box ? 'Boxes' : 'Units'}`" severity="info" />
              </div>
            </div>
          </div>
          <div class="tw-text-right">
            <p class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wider">Currently Assigned</p>
            <p class="tw-text-3xl tw-font-bold tw-text-purple-600">
              {{ selectedProduct?.fournisseur_assignments?.length || 0 }}
            </p>
            <p class="tw-text-sm tw-text-gray-600">Suppliers</p>
          </div>
        </div>
      </template>
    </Card>

   <!-- Supplier Selection Section -->
<div class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-p-6">
  <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
    <label class="tw-flex tw-items-center tw-gap-2 tw-text-lg tw-font-semibold tw-text-gray-800">
      <i class="pi pi-users tw-text-purple-600"></i>
      Select Suppliers
      <span class="tw-text-red-500">*</span>
    </label>
    <span class="tw-text-sm tw-text-gray-500 tw-bg-gray-100 tw-px-3 tw-py-1 tw-rounded-full">
      <i class="pi pi-sort-amount-down tw-mr-1"></i>
      Sorted by best price & rating
    </span>
  </div>

  <MultiSelect
    v-model="assignmentForm.fournisseur_ids"
    :options="enhancedSuppliers"
    optionLabel="company_name"
    optionValue="id"
    placeholder="Choose one or more suppliers"
    class="tw-w-full custom-multiselect-compact"
    filter
    :class="{ 'p-invalid': formErrors.fournisseur_ids }"
    :maxSelectedLabels="3"
    :showToggleAll="true"
    display="chip"
    :panelStyle="{ maxHeight: '400px' }"
  >
    <template #option="{ option }">
      <div class="tw-py-2 tw-px-3 tw-border-b tw-border-gray-100 hover:tw-bg-purple-50 tw-transition-colors tw-cursor-pointer">
        <div class="tw-flex tw-items-center tw-justify-between tw-gap-2">
          <!-- Left: Compact Info with Avatar -->
          <div class="tw-flex tw-items-center tw-gap-2 tw-flex-1">
            <!-- Small Avatar -->
            <div class="tw-w-7 tw-h-7 tw-bg-gradient-to-r tw-from-purple-500 tw-to-indigo-500 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-text-xs tw-font-bold tw-flex-shrink-0">
              {{ option.company_name?.charAt(0) }}
            </div>
            
            <!-- Company Info -->
            <div class="tw-flex-1 tw-min-w-0">
              <div class="tw-flex tw-items-center tw-gap-1">
                <span class="tw-font-semibold tw-text-sm tw-text-gray-900 tw-truncate">
                  {{ option.company_name }}
                </span>
                <Tag v-if="isBestPriceSupplier(option)" value="BEST" severity="success" class="!tw-text-xs !tw-py-0 !tw-px-1" />
              </div>
              <div class="tw-text-xs tw-text-gray-500 tw-truncate">
                <i class="pi pi-user tw-text-xs"></i> {{ option.contact_person || 'No contact' }}
              </div>
            </div>
          </div>

          <!-- Right: All Metrics in Compact Row -->
          <div class="tw-flex tw-items-center tw-gap-3 tw-text-xs">
            <!-- Rating -->
            <div class="tw-flex tw-items-center tw-gap-0.5">
              <i class="pi pi-star-fill tw-text-yellow-500 tw-text-xs"></i>
              <span class="tw-font-semibold">{{ option.rating || '0' }}</span>
            </div>
            
            <!-- Divider -->
            <span class="tw-text-gray-300">|</span>
            
            <!-- Last Price -->
            <div class="tw-text-green-700">
              <span class="tw-font-semibold">{{ option.lastPrice ? formatCurrency(option.lastPrice) : 'N/A' }}</span>
              <span class="tw-text-gray-500 tw-text-xs"> last</span>
            </div>
            
            <!-- Avg Price -->
            <div class="tw-text-blue-700">
              <span class="tw-font-semibold">{{ option.averagePrice ? formatCurrency(option.averagePrice) : 'N/A' }}</span>
              <span class="tw-text-gray-500 tw-text-xs"> avg</span>
            </div>
            
            <!-- Total Orders -->
            <div class="tw-text-purple-700">
              <span class="tw-font-semibold">{{ option.totalOrders || 0 }}</span>
              <span class="tw-text-gray-500 tw-text-xs"> orders</span>
            </div>
            
            <!-- History Button -->
            <Button
              @click.stop="viewSupplierHistory(option)"
              icon="pi pi-history"
              class="p-button-rounded p-button-text p-button-sm !tw-p-1 !tw-w-6 !tw-h-6"
              v-tooltip.left="'View history'"
            />
          </div>
        </div>
      </div>
    </template>

    <template #chip="slotProps">
      <div class="tw-flex tw-items-center tw-gap-1 tw-bg-purple-100 tw-px-2 tw-py-0.5 tw-rounded-full">
        <div class="tw-w-4 tw-h-4 tw-bg-purple-500 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-text-xs">
          {{ suppliers.find(s => s.id === slotProps.value)?.company_name?.charAt(0) }}
        </div>
        <span class="tw-text-purple-800 tw-text-xs tw-font-medium">
          {{ suppliers.find(s => s.id === slotProps.value)?.company_name }}
        </span>
      </div>
    </template>
  </MultiSelect>
  
  <small v-if="formErrors.fournisseur_ids" class="tw-text-red-500 tw-text-sm tw-mt-1 tw-block">
    <i class="pi pi-exclamation-circle tw-mr-1"></i>
    {{ formErrors.fournisseur_ids }}
  </small>
</div>

    <!-- Selected Suppliers Summary -->
    <transition name="slide-down">
      <Card v-if="assignmentForm.fournisseur_ids.length > 0" class="tw-border-0 tw-shadow-lg tw-bg-gray-50">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-check-circle tw-text-green-600"></i>
            <span>Selected Suppliers ({{ assignmentForm.fournisseur_ids.length }})</span>
          </div>
        </template>
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
            <div 
              v-for="supplierId in assignmentForm.fournisseur_ids" 
              :key="supplierId"
              class="tw-bg-white tw-rounded-xl tw-p-4 tw-shadow-md hover:tw-shadow-lg tw-transition-all"
            >
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar 
                    :label="enhancedSuppliers.find(s => s.id === supplierId)?.company_name?.charAt(0)"
                    class="tw-bg-gradient-to-r tw-from-purple-500 tw-to-pink-500 tw-text-white"
                  />
                  <div>
                    <p class="tw-font-semibold tw-text-gray-900">
                      {{ enhancedSuppliers.find(s => s.id === supplierId)?.company_name }}
                    </p>
                    <Rating 
                      :modelValue="enhancedSuppliers.find(s => s.id === supplierId)?.rating" 
                      :readonly="true" 
                      :stars="5" 
                      class="tw-text-xs tw-mt-1"
                    />
                  </div>
                </div>
                <Button 
                  @click="assignmentForm.fournisseur_ids = assignmentForm.fournisseur_ids.filter(id => id !== supplierId)"
                  icon="pi pi-times"
                  class="p-button-rounded p-button-text p-button-sm tw-text-red-500"
                  v-tooltip="'Remove'"
                />
              </div>
              <div class="tw-mt-3 tw-pt-3 tw-border-t tw-grid tw-grid-cols-2 tw-gap-2 tw-text-sm">
                <div>
                  <span class="tw-text-gray-500">Last:</span>
                  <p class="tw-font-bold tw-text-green-600">
                    {{ enhancedSuppliers.find(s => s.id === supplierId)?.lastPrice ? formatCurrency(enhancedSuppliers.find(s => s.id === supplierId).lastPrice) : 'N/A' }}
                  </p>
                </div>
                <div>
                  <span class="tw-text-gray-500">Avg:</span>
                  <p class="tw-font-bold tw-text-blue-600">
                    {{ enhancedSuppliers.find(s => s.id === supplierId)?.averagePrice ? formatCurrency(enhancedSuppliers.find(s => s.id === supplierId).averagePrice) : 'N/A' }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </transition>

    <!-- Quantity and Unit Section -->
    <div class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-p-6">
      <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
        <i class="pi pi-calculator tw-text-purple-600 tw-text-lg"></i>
        <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Assignment Details</h3>
      </div>

      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Quantity to Request <span class="tw-text-red-500">*</span>
          </label>
          <div class="tw-relative">
            <InputNumber
              v-model="assignmentForm.quantity"
              :min="1"
              :max="selectedProduct?.quantity || 999999"
              placeholder="Enter quantity"
              class="tw-w-full"
              showButtons
              buttonLayout="horizontal"
              :inputStyle="{ textAlign: 'center', fontSize: '1.125rem', fontWeight: '600' }"
            />
            <div class="tw-absolute tw--bottom-6 tw-left-0 tw-right-0">
              <ProgressBar 
                :value="(assignmentForm.quantity / (selectedProduct?.quantity || 1)) * 100" 
                :showValue="false"
                class="tw-h-1"
              />
            </div>
          </div>
          <div class="tw-flex tw-items-center tw-justify-between tw-mt-8">
            <small v-if="formErrors.quantity" class="tw-text-red-500">
              <i class="pi pi-exclamation-circle tw-mr-1"></i>
              {{ formErrors.quantity }}
            </small>
            <small v-else class="tw-text-gray-500">
              Maximum: {{ selectedProduct?.quantity || 0 }} {{ selectedProduct?.quantity_by_box ? 'Boxes' : 'Units' }}
            </small>
            <Badge 
              :value="`${Math.round((assignmentForm.quantity / (selectedProduct?.quantity || 1)) * 100)}%`"
              :severity="assignmentForm.quantity > selectedProduct?.quantity ? 'danger' : 'info'"
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Unit Type
          </label>
          <div class="tw-relative">
            <InputText
              v-model="assignmentForm.unit"
              placeholder="e.g., piece, box, kg"
              class="tw-w-full tw-pl-10"
            />
            <i class="pi pi-box tw-absolute tw-left-3 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
          </div>
          <small class="tw-text-gray-500 tw-mt-1 tw-block">
            Specify the unit of measurement for this assignment
          </small>
        </div>
      </div>
    </div>

    <!-- Notes Section -->
    <div class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-p-6">
      <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <label class="tw-flex tw-items-center tw-gap-2 tw-text-lg tw-font-semibold tw-text-gray-800">
          <i class="pi pi-comment tw-text-purple-600"></i>
          Additional Notes
        </label>
        <span class="tw-text-sm tw-text-gray-500">
          {{ assignmentForm.notes?.length || 0 }}/500 characters
        </span>
      </div>
      <Textarea
        v-model="assignmentForm.notes"
        rows="4"
        placeholder="Add any special instructions or requirements for the selected suppliers..."
        class="tw-w-full"
        :maxlength="500"
      />
    </div>
  </div>

  <!-- Footer Actions -->
  <template #footer>
    <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4 tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-p-6 tw--m-6 tw-rounded-b-xl">
      <div class="tw-text-sm tw-text-gray-600">
        <i class="pi pi-info-circle tw-mr-1 tw-text-blue-500"></i>
        Each selected supplier will receive the specified quantity
      </div>
      <div class="tw-flex tw-gap-3">
        <Button 
          @click="supplierDialog = false"
          label="Cancel"
          icon="pi pi-times"
          class="p-button-outlined"
          :disabled="submittingAssignment"
        />
        <Button 
          @click="saveAssignment"
          :loading="submittingAssignment"
          :disabled="!assignmentForm.fournisseur_ids?.length || !assignmentForm.quantity || assignmentForm.quantity <= 0"
          label="Assign Suppliers"
          icon="pi pi-check"
          class="p-button-primary tw-bg-purple-600 tw-border-0"
        />
      </div>
    </div>
  </template>
</Dialog>
    <!-- Enhanced Pricing History Dialog -->
<Dialog 
  :visible="historyDialog" 
  @update:visible="historyDialog = $event"
  modal 
  :header="null"
  :style="{ width: '95vw', maxWidth: '1400px' }"
  :breakpoints="{'960px': '98vw', '640px': '100vw'}"
  :closable="!loadingHistory"
  class="pricing-history-dialog"
>
  <!-- Custom Header with Gradient -->
  <template #header>
    <div :class="selectedProduct?.pharmacy_product_id 
      ? 'tw-bg-gray-800'
      : 'tw-bg-gray-800'" 
      class="tw--m-8 tw-p-6 tw-rounded-t-xl">
      <div class="tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-4">
          <div :class="selectedProduct?.pharmacy_product_id 
            ? 'tw-bg-purple-500/20'
            : 'tw-bg-blue-500/20'" 
            class="tw-backdrop-blur tw-p-3 tw-rounded-xl">
            <i class="pi pi-chart-line tw-text-2xl tw-text-white"></i>
          </div>
          <div>
            <h2 class="tw-text-2xl tw-font-bold tw-text-white">Pricing History Analytics</h2>
            <div class="tw-text-white/90 tw-mt-1 tw-flex tw-items-center tw-gap-3">
              <span class="tw-bg-white/20 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-box tw-mr-1"></i>
                {{ selectedHistoryProduct ? getProductName(selectedHistoryProduct) : 'Product' }}
                <Badge 
                  :value="selectedProduct?.pharmacy_product_id ? 'PHARMACY' : 'STOCK'" 
                  :severity="selectedProduct?.pharmacy_product_id ? 'danger' : 'info'"
                  class="tw-ml-2 tw-text-xs"
                />
              </span>
              <i class="pi pi-arrow-right tw-text-white/60"></i>
              <span class="tw-bg-white/20 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm">
                <i class="pi pi-building tw-mr-1"></i>
                {{ selectedHistorySupplier?.company_name || 'Supplier' }}
              </span>
            </div>
          </div>
        </div>
        <Button 
          v-if="!loadingHistory"
          icon="pi pi-times" 
          @click="historyDialog = false"
          class="p-button-rounded p-button-text tw-text-white hover:tw-bg-white/20"
        />
      </div>
    </div>
  </template>

  <!-- Loading State -->
  <div v-if="loadingHistory" class="tw-py-16">
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center">
      <div class="tw-relative">
        <div class="tw-w-20 tw-h-20 tw-bg-gradient-to-tr tw-from-blue-400 tw-to-purple-400 tw-rounded-full tw-animate-ping tw-absolute tw-opacity-75"></div>
        <div class="tw-w-20 tw-h-20 tw-bg-gradient-to-tr tw-from-blue-500 tw-to-purple-500 tw-rounded-full tw-animate-pulse tw-flex tw-items-center tw-justify-center">
          <i class="pi pi-chart-line tw-text-white tw-text-2xl"></i>
        </div>
      </div>
      <p class="tw-mt-6 tw-text-gray-600 tw-font-medium">Loading pricing history...</p>
      <p class="tw-text-sm tw-text-gray-500 tw-mt-2">Analyzing historical data patterns</p>
    </div>
  </div>

  <!-- Main Content -->
  <div v-else class="tw-space-y-6 tw-p-2">
    <!-- Supplier Info Card -->
    <Card class="tw-border-0 tw-shadow-lg tw-bg-gray-50">
      <template #content>
        <div class="tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-4">
            <Avatar 
              :label="selectedHistorySupplier?.company_name?.charAt(0)"
              class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-purple-500 tw-text-white"
              size="xlarge"
              shape="circle"
            />
            <div>
              <p class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wider">Supplier</p>
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-900">{{ selectedHistorySupplier?.company_name }}</h3>
              <p class="tw-text-gray-600 tw-mt-1">
                <i class="pi pi-user tw-mr-1 tw-text-sm"></i>
                {{ selectedHistorySupplier?.contact_person || 'No contact specified' }}
              </p>
            </div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-6">
            <div class="tw-text-center">
              <Rating :modelValue="selectedHistorySupplier?.rating || 0" :readonly="true" :stars="5" />
              <p class="tw-text-xs tw-text-gray-500 tw-mt-1">Overall Rating</p>
            </div>
            <div class="tw-text-center">
              <p class="tw-text-2xl tw-font-bold tw-text-purple-600">{{ selectedHistorySupplier?.totalOrders || 0 }}</p>
              <p class="tw-text-xs tw-text-gray-500">Total Orders</p>
            </div>
            <div class="tw-text-center">
              <p class="tw-text-2xl tw-font-bold tw-text-green-600">{{ selectedHistorySupplier?.reliabilityScore || 0 }}%</p>
              <p class="tw-text-xs tw-text-gray-500">Reliability</p>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Advanced Filters Section -->
    <Card class="tw-border-0 tw-shadow-lg">
      <template #title>
        <div class="tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-filter tw-text-indigo-600"></i>
          <span>Advanced Filters</span>
        </div>
      </template>
      <template #content>
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-calendar tw-mr-1 tw-text-gray-500"></i>
              From Date
            </label>
            <Calendar 
              v-model="historyFilters.startDate" 
              placeholder="Start date"
              class="tw-w-full"
              dateFormat="yy-mm-dd"
              showIcon
              showButtonBar
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-calendar tw-mr-1 tw-text-gray-500"></i>
              To Date
            </label>
            <Calendar 
              v-model="historyFilters.endDate" 
              placeholder="End date"
              class="tw-w-full"
              dateFormat="yy-mm-dd"
              showIcon
              showButtonBar
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-tag tw-mr-1 tw-text-gray-500"></i>
              Order Type
            </label>
            <Dropdown
              v-model="historyFilters.orderType"
              :options="orderTypeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All types"
              class="tw-w-full"
              showClear
            />
          </div>
          <div class="tw-flex tw-items-end tw-gap-2">
            <Button 
              @click="applyHistoryFilters"
              label="Apply"
              icon="pi pi-check"
              class="tw-bg-indigo-600 tw-border-0"
            />
            <Button 
              @click="clearHistoryFilters"
              icon="pi pi-times"
              class="p-button-outlined"
              v-tooltip="'Clear filters'"
            />
            <Button 
              @click="exportHistory"
              icon="pi pi-download"
              class="p-button-success"
              v-tooltip="'Export to CSV'"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Statistics Dashboard -->
    <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-5 tw-gap-4">
      <Card class="tw-border-0 tw-shadow-lg tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-600">
        <template #content>
          <div class="tw-text-white tw-text-center">
            <i class="pi pi-shopping-cart tw-text-3xl tw-opacity-80 tw-mb-2"></i>
            <div class="tw-text-3xl tw-font-bold">{{ historyStats.totalOrders }}</div>
            <div class="tw-text-sm tw-opacity-90 tw-mt-1">Total Orders</div>
          </div>
        </template>
      </Card>

      <Card class="tw-border-0 tw-shadow-lg tw-bg-gradient-to-br tw-from-green-500 tw-to-green-600">
        <template #content>
          <div class="tw-text-white tw-text-center">
            <i class="pi pi-dollar tw-text-3xl tw-opacity-80 tw-mb-2"></i>
            <div class="tw-text-2xl tw-font-bold">{{ formatCurrency(historyStats.averagePrice) }}</div>
            <div class="tw-text-sm tw-opacity-90 tw-mt-1">Avg Price</div>
          </div>
        </template>
      </Card>

      <Card class="tw-border-0 tw-shadow-lg tw-bg-gradient-to-br tw-from-purple-500 tw-to-purple-600">
        <template #content>
          <div class="tw-text-white tw-text-center">
            <i class="pi pi-arrow-down tw-text-3xl tw-opacity-80 tw-mb-2"></i>
            <div class="tw-text-2xl tw-font-bold">{{ formatCurrency(historyStats.minPrice) }}</div>
            <div class="tw-text-sm tw-opacity-90 tw-mt-1">Min Price</div>
          </div>
        </template>
      </Card>

      <Card class="tw-border-0 tw-shadow-lg tw-bg-gradient-to-br tw-from-red-500 tw-to-red-600">
        <template #content>
          <div class="tw-text-white tw-text-center">
            <i class="pi pi-arrow-up tw-text-3xl tw-opacity-80 tw-mb-2"></i>
            <div class="tw-text-2xl tw-font-bold">{{ formatCurrency(historyStats.maxPrice) }}</div>
            <div class="tw-text-sm tw-opacity-90 tw-mt-1">Max Price</div>
          </div>
        </template>
      </Card>

      <Card class="tw-border-0 tw-shadow-lg tw-bg-gradient-to-br tw-from-orange-500 tw-to-orange-600">
        <template #content>
          <div class="tw-text-white tw-text-center">
            <i class="pi pi-box tw-text-3xl tw-opacity-80 tw-mb-2"></i>
            <div class="tw-text-3xl tw-font-bold">{{ historyStats.totalQuantity }}</div>
            <div class="tw-text-sm tw-opacity-90 tw-mt-1">Total Qty</div>
          </div>
        </template>
      </Card>
    </div>

    <!-- History Table with Enhanced Styling -->
    <Card class="tw-border-0 tw-shadow-xl">
      <template #title>
        <div class="tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-table tw-text-indigo-600"></i>
            <span>Transaction History</span>
          </div>
          <Badge :value="`${filteredHistoryData?.length || 0} records`" severity="info" />
        </div>
      </template>
      <template #content>
        <DataTable 
          :value="filteredHistoryData"
          class="custom-history-table"
          :paginator="true"
          :rows="10"
          :sortOrder="-1"
          sortField="order_date"
          responsiveLayout="scroll"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} records"
          :rowsPerPageOptions="[10, 25, 50, 100]"
          dataKey="id"
          :rowClass="rowClass"
          stripedRows
        >
          <template #empty>
            <div class="tw-text-center tw-py-12">
              <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
              <p class="tw-text-xl tw-font-medium tw-text-gray-700">No History Found</p>
              <p class="tw-text-gray-500 tw-mt-2">No pricing history available for the selected filters</p>
            </div>
          </template>

          <Column field="order_date" header="Date" :sortable="true" class="tw-min-w-[140px]">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-calendar tw-text-gray-400"></i>
                <span class="tw-font-medium">{{ formatDate(data.order_date || data.created_at) }}</span>
              </div>
            </template>
          </Column>

          <Column field="order_type" header="Type" :sortable="true" class="tw-min-w-[120px]">
            <template #body="{ data }">
              <Tag 
                :value="data.movement_type || data.order_type || 'Receipt'" 
                :severity="(data.movement_type || data.order_type) === 'transfer' ? 'warning' : (data.order_type === 'proforma' ? 'info' : (data.order_type === 'purchase_order' ? 'warning' : 'success'))"
                :icon="data.movement_type ? 'pi pi-arrows-h' : (data.order_type === 'proforma' ? 'pi pi-file' : 'pi pi-check')"
              />
            </template>
          </Column>

          <!-- Show pricing for stock products -->
          <Column v-if="!selectedProduct?.pharmacy_product_id" field="price" header="Unit Price" :sortable="true" class="tw-min-w-[140px]">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-justify-between">
                <span class="tw-font-bold tw-text-green-700 tw-text-lg">
                  {{ formatCurrency(data.price) }}
                </span>
                <i 
                  v-if="data.price < historyStats.averagePrice" 
                  class="pi pi-arrow-down tw-text-green-500"
                  v-tooltip="'Below average'"
                ></i>
                <i 
                  v-else-if="data.price > historyStats.averagePrice" 
                  class="pi pi-arrow-up tw-text-red-500"
                  v-tooltip="'Above average'"
                ></i>
              </div>
            </template>
          </Column>

          <!-- Show pharmacy product current pricing info -->
          <Column v-if="selectedProduct?.pharmacy_product_id" header="Current Pricing" class="tw-min-w-[200px]">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-text-sm">
                  <span class="tw-text-gray-600">Cost:</span>
                  <span class="tw-font-bold tw-text-green-700 tw-ml-2">{{ formatCurrency(data.current_unit_cost) }}</span>
                </div>
                <div class="tw-text-sm">
                  <span class="tw-text-gray-600">Price:</span>
                  <span class="tw-font-bold tw-text-blue-700 tw-ml-2">{{ formatCurrency(data.current_selling_price) }}</span>
                </div>
              </div>
            </template>
          </Column>

          <Column field="quantity" header="Quantity" :sortable="true" class="tw-min-w-[100px]">
            <template #body="{ data }">
              <div class="tw-text-center">
                <Badge :value="data.executed_quantity || data.quantity || 0" severity="info" class="tw-px-3 tw-py-1" />
              </div>
            </template>
          </Column>

          <!-- Show services for pharmacy products -->
          <Column v-if="selectedProduct?.pharmacy_product_id" header="Services" class="tw-min-w-[180px]">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1 tw-text-sm">
                <div v-if="data.providing_service" class="tw-flex tw-items-center tw-gap-1">
                  <i class="pi pi-arrow-circle-right tw-text-green-600 tw-text-xs"></i>
                  <span class="tw-text-gray-600">{{ data.providing_service }}</span>
                </div>
                <div v-if="data.requesting_service" class="tw-flex tw-items-center tw-gap-1">
                  <i class="pi pi-arrow-circle-left tw-text-blue-600 tw-text-xs"></i>
                  <span class="tw-text-gray-600">{{ data.requesting_service }}</span>
                </div>
              </div>
            </template>
          </Column>

          <!-- Show total amount for stock products -->
          <Column v-if="!selectedProduct?.pharmacy_product_id" field="total_amount" header="Total Amount" :sortable="true" class="tw-min-w-[150px]">
            <template #body="{ data }">
              <div class="tw-font-bold tw-text-blue-700 tw-text-lg tw-text-center">
                {{ formatCurrency((parseFloat(data.price) || 0) * (parseInt(data.quantity) || 0)) }}
              </div>
            </template>
          </Column>

          <!-- Status column for pharmacy products -->
          <Column v-if="selectedProduct?.pharmacy_product_id" field="status" header="Status" class="tw-min-w-[120px]">
            <template #body="{ data }">
              <Tag 
                :value="data.status" 
                :severity="data.status === 'completed' ? 'success' : (data.status === 'pending' ? 'warning' : 'info')"
              />
            </template>
          </Column>

          <Column field="document_reference" header="Reference" class="tw-min-w-[180px]">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-file-o tw-text-gray-400"></i>
                <span class="tw-font-mono tw-text-sm tw-text-gray-700">
                  {{ data.document_reference || data.bon_reception_code || 'N/A' }}
                </span>
              </div>
            </template>
          </Column>

          <Column field="notes" header="Notes" class="tw-min-w-[200px]">
            <template #body="{ data }">
              <div v-if="data.notes || data.observations" class="tw-text-sm tw-text-gray-600">
                <Chip :label="(data.notes || data.observations).substring(0, 50) + (data.notes?.length > 50 ? '...' : '')" 
                      class="tw-bg-gray-100"
                      v-tooltip="data.notes || data.observations" />
              </div>
              <span v-else class="tw-text-gray-400 tw-italic">No notes</span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>

  <!-- Footer Actions -->
  <template #footer>
    <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4 tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-p-6 tw--m-6 tw-rounded-b-xl">
      <div class="tw-flex tw-items-center tw-gap-4 tw-text-sm tw-text-gray-600">
        <span>
          <i class="pi pi-info-circle tw-mr-1 tw-text-blue-500"></i>
          Showing {{ filteredHistoryData?.length || 0 }} of {{ pricingHistoryData?.length || 0 }} records
        </span>
        <Divider layout="vertical" />
        <span>
          <i class="pi pi-chart-line tw-mr-1 tw-text-green-500"></i>
          Price Trend: 
          <Tag 
            :value="filteredHistoryData?.length > 1 ? 'Stable' : 'N/A'" 
            severity="info" 
            class="tw-ml-1"
          />
        </span>
      </div>
      <Button 
        @click="historyDialog = false"
        label="Close"
        icon="pi pi-times"
        class="p-button-outlined"
      />
    </div>
  </template>
</Dialog>

<!-- Product Selection Dialog - NEW -->
<Dialog
  v-model:visible="showProductSelectionDialog"
  modal
  :header="'Add Product to Order'"
  :style="{ width: '75rem' }"
  :closable="true"
  class="tw-rounded-2xl tw-shadow-2xl"
>
  <div class="tw-space-y-8 tw-p-4">
    <!-- Conditional Content - Show appropriate form based on is_pharmacy_order -->
    <!-- Pharmacy Products Section - Show when is_pharmacy_order is true -->
    <div v-if="!selectedItemForProductSelection ? serviceDemandIsPharmacyOrder : selectedItemForProductSelection.is_pharmacy_order">
      <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
        <div class="tw-w-12 tw-h-12 tw-bg-purple-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
          <i class="pi pi-pills tw-text-white tw-text-xl"></i>
        </div>
        <div>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-font-bold tw-text-xl tw-text-gray-800">Pharmacy Products</div>
            <Badge value="PHARMACY" severity="danger" class="tw-bg-purple-600 tw-text-white tw-font-bold tw-border-0" />
          </div>
          <div class="tw-text-sm tw-text-gray-500 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-info-circle tw-text-purple-400"></i>
            {{ availablePharmacyProducts.length }} products loaded{{ pharmacyProductsPagination.hasMore ? ' (more available)' : '' }}
          </div>
        </div>
      </div>

      <div class="tw-space-y-6">
        <!-- Selection Form Card -->
        <Card class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
          <template #content>
            <div class="tw-space-y-6">
              <!-- Product Selection Dropdown -->
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                  <i class="pi pi-search tw-mr-2 tw-text-purple-600"></i>
                  Select Pharmacy Product <span class="tw-text-red-500">*</span>
                </label>
                <Dropdown
                  ref="pharmacyDropdownRef"
                  v-model="selectedPharmacyProduct"
                  :options="availablePharmacyProducts"
                  :optionLabel="(option) => option.name || option.generic_name || option.brand_name || 'Unnamed Product'"
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
                      <div class="tw-font-medium tw-text-gray-900">{{ option.name || option.generic_name || option.brand_name || 'Unnamed Product' }}</div>
                      <div class="tw-text-xs tw-text-gray-500 tw-mt-1">SKU: {{ option.sku || 'N/A' }}</div>
                    </div>
                  </template>
                  <template #value="slotProps">
                    <div v-if="slotProps.value" class="tw-text-sm">
                      <span class="tw-font-medium">{{ slotProps.value.name || slotProps.value.generic_name || slotProps.value.brand_name || 'Unnamed Product' }}</span>
                    </div>
                  </template>
                  <template v-if="pharmacyProductsPagination.hasMore" #footer>
                    <div class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200">
                      <span v-if="pharmacyProductsPagination.loading" class="tw-text-sm tw-text-gray-600">
                        <i class="pi pi-spin pi-spinner tw-mr-2"></i>Loading more products...
                      </span>
                      <span v-else class="tw-text-sm tw-text-gray-500">Scroll to load more</span>
                    </div>
                  </template>
                </Dropdown>
              </div>

              <!-- Quantity and Unit Row -->
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <!-- Quantity Input -->
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                    <i class="pi pi-hashtag tw-mr-2 tw-text-purple-600"></i>
                    Quantity <span class="tw-text-red-500">*</span>
                  </label>
                  <InputNumber
                    v-model="productSelectionQuantity"
                    :min="1"
                    :max="999"
                    class="tw-w-full tw-border-2 tw-border-purple-200 focus:tw-border-purple-400"
                    showButtons
                    buttonLayout="horizontal"
                    incrementButtonIcon="pi pi-plus"
                    decrementButtonIcon="pi pi-minus"
                  />
                </div>

                <!-- Unit Selection -->
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                    <i class="pi pi-tag tw-mr-2 tw-text-purple-600"></i>
                    Unit Type <span class="tw-text-red-500">*</span>
                  </label>
                  <Dropdown
                    v-model="productSelectionUnit"
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

        <!-- Product Info Card -->
        <Card v-if="selectedPharmacyProduct" class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
          <template #content>
            <div class="tw-relative tw-z-10">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-pills tw-text-purple-600"></i>
                  Product Details
                </h4>
                <Badge value="PHARMACY PRODUCT" severity="danger" class="tw-bg-purple-600 tw-text-white tw-font-bold tw-border-0" />
              </div>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">SKU:</span>
                  <Tag :value="selectedPharmacyProduct.sku" severity="danger" class="tw-bg-purple-100 tw-text-purple-800 tw-border-purple-200" />
                </div>
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Generic Name:</span>
                  <span class="tw-font-semibold tw-text-gray-800">{{ selectedPharmacyProduct.generic_name || 'N/A' }}</span>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Unit:</span>
                  <Badge :value="selectedPharmacyProduct.unit_of_measure || 'pieces'" class="tw-bg-pink-100 tw-text-pink-800 tw-border-pink-200" />
                </div>
                <div v-if="selectedPharmacyProduct.brand_name" class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Brand:</span>
                  <span class="tw-font-semibold tw-text-gray-800">{{ selectedPharmacyProduct.brand_name }}</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Stock Products Section - Show when is_pharmacy_order is false -->
    <div v-else>
      <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
        <div class="tw-w-12 tw-h-12 tw-bg-indigo-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
          <i class="pi pi-box tw-text-white tw-text-xl"></i>
        </div>
        <div>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-font-bold tw-text-xl tw-text-gray-800">Stock Products</div>
            <Badge value="STOCK" severity="info" class="tw-bg-indigo-600 tw-text-white tw-font-bold tw-border-0" />
          </div>
          <div class="tw-text-sm tw-text-gray-500 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-info-circle tw-text-indigo-400"></i>
            {{ availableStockProducts.length }} products loaded{{ stockProductsPagination.hasMore ? ' (more available)' : '' }}
          </div>
        </div>
      </div>

      <div class="tw-space-y-6">
        <!-- Selection Form Card -->
        <Card class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
          <template #content>
            <div class="tw-space-y-6">
              <!-- Product Selection Dropdown -->
              <div>
                <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                  <i class="pi pi-search tw-mr-2 tw-text-indigo-600"></i>
                  Select Stock Product <span class="tw-text-red-500">*</span>
                </label>
                <Dropdown
                  ref="stockDropdownRef"
                  v-model="selectedStockProduct"
                  :options="availableStockProducts"
                  :optionLabel="(option) => option.name || option.designation || 'Unnamed Product'"
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
                      <div class="tw-font-medium tw-text-gray-900">{{ option.name || option.designation || 'Unnamed Product' }}</div>
                      <div class="tw-text-xs tw-text-gray-500 tw-mt-1">Code: {{ option.code_interne || option.code || 'N/A' }}</div>
                    </div>
                  </template>
                  <template #value="slotProps">
                    <div v-if="slotProps.value" class="tw-text-sm">
                      <span class="tw-font-medium">{{ slotProps.value.name || slotProps.value.designation || 'Unnamed Product' }}</span>
                    </div>
                  </template>
                  <template v-if="stockProductsPagination.hasMore" #footer>
                    <div class="tw-p-3 tw-text-center tw-border-t tw-border-gray-200">
                      <span v-if="stockProductsPagination.loading" class="tw-text-sm tw-text-gray-600">
                        <i class="pi pi-spin pi-spinner tw-mr-2"></i>Loading more products...
                      </span>
                      <span v-else class="tw-text-sm tw-text-gray-500">Scroll to load more</span>
                    </div>
                  </template>
                </Dropdown>
              </div>

              <!-- Quantity and Unit Row -->
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <!-- Quantity Input -->
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                    <i class="pi pi-hashtag tw-mr-2 tw-text-indigo-600"></i>
                    Quantity <span class="tw-text-red-500">*</span>
                  </label>
                  <InputNumber
                    v-model="productSelectionQuantity"
                    :min="1"
                    :max="999"
                    class="tw-w-full tw-border-2 tw-border-indigo-200 focus:tw-border-indigo-400"
                    showButtons
                    buttonLayout="horizontal"
                    incrementButtonIcon="pi pi-plus"
                    decrementButtonIcon="pi pi-minus"
                  />
                </div>

                <!-- Unit Selection -->
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">
                    <i class="pi pi-tag tw-mr-2 tw-text-indigo-600"></i>
                    Unit Type <span class="tw-text-red-500">*</span>
                  </label>
                  <Dropdown
                    v-model="productSelectionUnit"
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

        <!-- Product Info Card -->
        <Card v-if="selectedStockProduct" class="tw-bg-gray-50 tw-border-0 tw-shadow-lg">
          <template #content>
            <div class="tw-relative tw-z-10">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-box tw-text-indigo-600"></i>
                  Product Details
                </h4>
                <Badge value="STOCK PRODUCT" severity="info" class="tw-bg-indigo-600 tw-text-white tw-font-bold tw-border-0" />
              </div>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Product Code:</span>
                  <Tag :value="selectedStockProduct.code_interne || selectedStockProduct.code" severity="info" class="tw-bg-indigo-100 tw-text-indigo-800 tw-border-indigo-200" />
                </div>
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Category:</span>
                  <span class="tw-font-semibold tw-text-gray-800">{{ selectedStockProduct.category || 'N/A' }}</span>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Unit:</span>
                  <Badge :value="selectedStockProduct.unit || 'pieces'" class="tw-bg-blue-100 tw-text-blue-800 tw-border-blue-200" />
                </div>
                <div v-if="selectedStockProduct.designation" class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white/60 tw-rounded-lg tw-backdrop-blur-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Designation:</span>
                  <span class="tw-font-semibold tw-text-gray-800">{{ selectedStockProduct.designation }}</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>

  <!-- Dialog Footer -->
  <template #footer>
    <div class="tw-flex tw-justify-end tw-gap-4 tw-p-4 tw-bg-gray-50 tw-rounded-b-2xl">
      <Button
        label="Cancel"
        icon="pi pi-times"
        class="tw-bg-white tw-text-gray-700 tw-border-2 tw-border-gray-300 hover:tw-bg-gray-50 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-transition-all tw-duration-200"
        @click="closeProductSelectionDialog"
      />
      <Button
        :label="(!selectedItemForProductSelection ? serviceDemandIsPharmacyOrder : selectedItemForProductSelection.is_pharmacy_order) ? 'Add Pharmacy Product' : 'Add Stock Product'"
        icon="pi pi-check"
        :class="(!selectedItemForProductSelection ? serviceDemandIsPharmacyOrder : selectedItemForProductSelection.is_pharmacy_order) 
          ? 'tw-bg-purple-600 hover:tw-bg-purple-700 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200'
          : 'tw-bg-indigo-600 hover:tw-bg-indigo-700 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200'"
        @click="addSelectedProductToOrder"
        :loading="loadingProducts"
      />
    </div>
  </template>
</Dialog>

    </div>

    <!-- Error State - Enhanced -->
    <div v-else-if="error" class="tw-flex tw-items-center tw-justify-center tw-min-h-screen">
      <Card class="tw-max-w-md tw-border-0 tw-shadow-2xl">
        <template #content>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-exclamation-triangle tw-text-6xl tw-text-red-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-semibold tw-text-gray-800 tw-mb-2">Error Loading Service Demand</h3>
            <p class="tw-text-gray-600 tw-mb-6">{{ error }}</p>
            <Button 
              @click="fetchServiceDemand" 
              label="Try Again" 
              icon="pi pi-refresh"
              class="p-button-danger"
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- SpeedDial for Quick Actions -->
    <SpeedDial 
      :model="speedDialItems.filter(item => !item.visible || item.visible())" 
      :radius="80" 
      type="semi-circle" 
      direction="up"
      :style="{ position: 'fixed', bottom: '30px', right: '30px', zIndex: 1000 }"
      buttonClass="p-button-lg tw-bg-gray-600"
      :tooltipOptions="{ position: 'left' }"
    />

    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Toast Notifications -->
    <Toast position="top-right" />
  </div>
</template>

<style scoped>
/* Enhanced styles with gradients and modern design */
.service-demand-detail {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
/* Make the dropdown more compact */
.custom-multiselect-compact .p-multiselect-panel {
  max-width: 650px !important;
  font-size: 0.875rem !important;
}

.custom-multiselect-compact .p-multiselect-items-wrapper {
  max-height: 350px !important;
}

.custom-multiselect-compact .p-multiselect-item {
  padding: 0 !important;
}

/* Compact chips */
.custom-multiselect-compact .p-multiselect-token {
  padding: 0.125rem 0.375rem !important;
  margin-right: 0.25rem !important;
}

.custom-multiselect-compact .p-multiselect-token-icon {
  margin-left: 0.25rem !important;
  font-size: 0.75rem !important;
}

/* Enhanced DataTable styling */
:deep(.p-datatable) {
  border: none;
  border-radius: 1rem;
  overflow: hidden;
}
/* Animation for slide down */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from {
  transform: translateY(-20px);
  opacity: 0;
}
.slide-down-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

/* Custom MultiSelect Styling */
.custom-multiselect :deep(.p-multiselect-panel) {
  max-height: 400px;
}

.custom-multiselect :deep(.p-multiselect-items) {
  padding: 0;
}

/* Custom Table Styling */
.custom-history-table :deep(.p-datatable-thead > tr > th) {
  background: #6b7280;
  color: white;
  font-weight: 600;
  border: none;
}

.custom-history-table :deep(.p-datatable-tbody > tr:hover) {
  background: rgba(0, 0, 0, 0.05);
  cursor: pointer;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: #6b7280;
  color: white;
  font-weight: 600;
  padding: 1.25rem;
  border: none;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: all 0.3s ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: rgba(0, 0, 0, 0.05);
  transform: translateX(4px);
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1.25rem;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Enhanced Dialog styling */
:deep(.p-dialog .p-dialog-header) {
  background: #6b7280;
  color: white;
  padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-content) {
  background: #ffffff;
  padding: 2rem;
}

/* Enhanced Card styling */
:deep(.p-card) {
  border-radius: 1rem;
  border: none;
}

:deep(.p-card-title) {
  font-size: 1.25rem;
  font-weight: 600;
}

/* Enhanced TabView styling */
:deep(.p-tabview) {
  border-radius: 1rem;
  overflow: hidden;
}

:deep(.p-tabview-nav) {
  background: #f9fafb;
  border: none;
}

:deep(.p-tabview-nav-link) {
  transition: all 0.3s ease;
}

:deep(.p-tabview-nav-link:not(.p-disabled):not(.p-highlight):hover) {
  background: rgba(0, 0, 0, 0.05);
}

:deep(.p-tabview-nav-link.p-highlight) {
  background: #6b7280;
  color: white;
}

/* Enhanced Button styling */
:deep(.p-button) {
  transition: all 0.3s ease;
  font-weight: 600;
}

:deep(.p-button:not(:disabled):hover) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px -5px rgba(0,0,0,0.2);
}

/* Enhanced SpeedDial styling */
:deep(.p-speeddial-button) {
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
}

/* Enhanced Avatar styling */
:deep(.p-avatar) {
  font-weight: 600;
}

/* Enhanced Tag styling */
:deep(.p-tag) {
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 2rem;
}

/* Animation classes */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.tw-animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  :deep(.p-datatable .p-datatable-thead > tr > th),
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem;
  }

  :deep(.p-dialog) {
    width: 95vw !important;
    margin: 1rem;
  }
}

/* Print styles */
@media print {
  .p-button,
  .p-speeddial,
  .tw-bg-gradient-to-r {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}
</style>