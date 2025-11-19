<!-- components/Reception/FicheNavette/ItemCard.vue -->
<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Chip from 'primevue/chip'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dropdown from 'primevue/dropdown'

// Import RemiseModal component
import RemiseModal from '../FicheNavatteItem/RemiseFichenavetteModal.vue'

// Import the service
import { ficheNavetteService } from '../../../Apps/services/Emergency/ficheNavetteService'

// Props
const props = defineProps({
  group: {
    type: Object,
    required: true,
    default: () => ({
      items: [],
      type: 'prestation'
    })
  },
  prestations: {
    type: Array,
    required: true,
    default: () => []
  },
  packages: {
    type: Array,
    required: true,
    default: () => []
  },
  patientId: {
    type: String,
    required: true
  },
  ficheNavetteId: {
    type: String,
    required: true
  },
  doctors: {
    type: Array,
    required: true,
    default: () => []
  }
})

// Emits
const emit = defineEmits([
  'remove-item',
  'item-updated', 
  'dependency-removed',
  'apply-remise'
])

// Composables
const toast = useToast()
const confirm = useConfirm()

// State
const showDetailsModal = ref(false)
const showRemiseModal = ref(false)
const showPaymentTypeDialog = ref(false)
const editingItem = ref(null)
const selectedPaymentType = ref(null)

// Payment type options match backend validation
const paymentTypeOptions = [
  { label: 'Pré-paiement', value: 'Pré-paiement' },
  { label: 'Post-paiement', value: 'Post-paiement' },
  { label: 'Versement', value: 'Versement' }
]

// Safe access to group items
const groupItems = computed(() => {
  return props.group?.items || []
})

// Computed properties
const cardTitle = computed(() => {
  if (props.group?.type === 'package') {
    return props.group.name || 'Package'
  }
  return props.group?.name || 'Prestation'
})

const cardSubtitle = computed(() => {
  const doctors = []
  
  // Main group doctor
  if (props.group?.doctor_name) {
    doctors.push(props.group.doctor_name)
  }
  
  // Collect doctors from all items in the group
  groupItems.value.forEach(item => {
    // Item doctor
    if (item.doctor_name && !doctors.includes(item.doctor_name)) {
      doctors.push(item.doctor_name)
    }
    
    // Package reception doctors (Emergency module - FIXED)
    if (item.packageReceptionRecords && Array.isArray(item.packageReceptionRecords)) {
      item.packageReceptionRecords.forEach(record => {
        if (record.doctor?.name && !doctors.includes(record.doctor.name)) {
          doctors.push(record.doctor.name)
        }
      })
    }
    
    // Package prestations doctors
    if (item.package?.prestations) {
      item.package.prestations.forEach(prestation => {
        if (prestation.doctor?.name && !doctors.includes(prestation.doctor.name)) {
          doctors.push(prestation.doctor.name)
        }
      })
    }
    
    // Dependencies doctors
    if (item.dependencies) {
      item.dependencies.forEach(dependency => {
        if (dependency.dependencyPrestation?.doctor?.name && !doctors.includes(dependency.dependencyPrestation.doctor.name)) {
          doctors.push(dependency.dependencyPrestation.doctor.name)
        }
      })
    }
  })
  
  if (doctors.length > 0) {
    return `Dr. ${doctors.join(', Dr. ')}`
  }
  return 'No doctor assigned'
})

// Check if any items have convention_id
const hasConventionItems = computed(() => {
  return groupItems.value.some(item => 
    item.convention_id && item.convention_id !== null
  )
})

// Get convention information from items  
const conventionInfo = computed(() => {
  const conventionItems = groupItems.value.filter(item => 
    item.convention_id && item.convention_id !== null
  )
  
  if (conventionItems.length === 0) {
    return []
  }
  
  // Get unique conventions
  const conventions = conventionItems.reduce((acc, item) => {
    const key = item.convention_id
    if (!acc[key]) {
      acc[key] = {
        id: item.convention_id,
        name: item.convention_name || `Convention ${item.convention_id}`,
        count: 0
      }
    }
    acc[key].count++
    return acc
  }, {})
  
  return Object.values(conventions)
})

// Add missing conventionChips computed property
const conventionChips = computed(() => {
  const conventionItems = groupItems.value.filter(item => 
    item.convention_id && item.convention
  )
  
  if (conventionItems.length === 0) {
    return []
  }
  
  // Create unique chips based on convention info
  const uniqueConventions = conventionItems.reduce((acc, item) => {
    const key = item.convention_id
    if (!acc[key]) {
      acc[key] = {
        id: item.convention_id,
        label: item.convention?.company_name || item.convention_name || 'Convention',
        severity: 'info',
        organism_color: item.convention?.organism_color || null
      }
    }
    return acc
  }, {})
  
  return Object.values(uniqueConventions)
})

// FIXED: Update regularDependencies to group by parent_item_id
const regularDependencies = computed(() => {
  const allDependencies = []
  
  groupItems.value.forEach(item => {
    if (item.dependencies && Array.isArray(item.dependencies)) {
      item.dependencies.forEach(dep => {
        // Only include dependencies that belong to THIS specific parent item
        if (dep.parent_item_id === item.id && dep.dependency_type !== 'package') {
          allDependencies.push({
            ...dep,
            parentItem: item
          })
        }
      })
    }
  })
  
  return allDependencies
})

// FIXED: Update packageDependencies to group by parent_item_id
const packageDependencies = computed(() => {
  const packageDeps = []
  
  groupItems.value.forEach(item => {
    if (item.dependencies && Array.isArray(item.dependencies)) {
      item.dependencies.forEach(dep => {
        // Only include package dependencies that belong to THIS specific parent item
        if (dep.parent_item_id === item.id && dep.dependency_type === 'package') {
          packageDeps.push({
            ...dep,
            parentItem: item
          })
        }
      })
    }
  })
  
  return packageDeps
})

// Add this computed property to get the organism color
const organismColor = computed(() => {
  // Check if any item in the group has a convention with organism_color
  const itemWithConvention = groupItems.value.find(item => 
  item.convention_id && item.convention?.organism_color
)


  
  return itemWithConvention?.convention?.organism_color || null
})

// Card styling based on convention and type
const cardStyleClass = computed(() => {
  let baseClass = 'item-card'
  
  // If we have an organism color, use custom styling
  if (organismColor.value) {
    return `${baseClass} organism-convention-card`
  }
  
  // Fallback to existing logic
  if (!hasConventionItems.value) {
    return `${baseClass} default-card`
  }
  
  if (props.group?.type === 'package') {
    return `${baseClass} convention-package-card`
  }
  
  return `${baseClass} convention-prestation-card`
})

// Update the cardIconStyle computed property
const cardIconStyle = computed(() => {
  // If we have an organism color, use it
  if (organismColor.value) {
    return {
      background: organismColor.value,
      color: 'white'
    }
  }
  
  // Default styling based on convention status and type
  if (!hasConventionItems.value) {
    return {}
  }
  
  if (props.group?.type === 'package') {
    return {
      background: 'var(--orange-500, #fd7e14)',
      color: 'white'
    }
  }
  
  return {
    background: 'var(--green-500, #28a745)',
    color: 'white'
  }
})

// Check if any items have a specific status
const hasSpecificStatusItems = computed(() => {
  return groupItems.value.some(item => 
    item.status && item.status !== 'completed'
  )
})

// Get all unique statuses from items
const allStatuses = computed(() => {
  const statuses = groupItems.value.map(item => item.status).filter(Boolean)
  return [...new Set(statuses)]
})

// Get status color based on severity
const statusColor = computed(() => {
  if (!hasSpecificStatusItems.value) {
    return null
  }
  
  // If all items are in progress, show blue
  if (groupItems.value.every(item => item.status === 'in_progress')) {
    return 'var(--blue-500, #007bff)'
  }
  
  // If all items are completed, show green
  if (groupItems.value.every(item => item.status === 'completed')) {
    return 'var(--green-500, #28a745)'
  }
  
  // If there are pending items, show orange
  if (groupItems.value.some(item => item.status === 'pending')) {
    return 'var(--orange-500, #fd7e14)'
  }
  
  // If there are cancelled items, show red
  if (groupItems.value.some(item => item.status === 'cancelled')) {
    return 'var(--red-500, #dc3545)'
  }
  
  return null
})

// Computed property for header styling
const headerStyle = computed(() => {
  if (organismColor.value) {
    return {
      background: `linear-gradient(135deg, ${organismColor.value}22 0%, ${organismColor.value}11 100%)`,
      borderBottom: `2px solid ${organismColor.value}33`
    }
  }
  return {}
})

// FIXED: Update mainDisplayItems to only show items for this specific group
const mainDisplayItems = computed(() => {
  // Only show the main items for this group (not dependencies from other groups)
  const items = [...groupItems.value]
  
  // Add package dependencies as main items ONLY if they belong to this group's parent item
  packageDependencies.value.forEach(dep => {
    // Check if this dependency belongs to any item in the current group
    const belongsToThisGroup = groupItems.value.some(item => item.id === dep.parent_item_id)
    
    if (belongsToThisGroup) {
      const prestation = dep.dependencyPrestation || dep.dependency_prestation
      if (prestation) {
        items.push({
          id: `dep_${dep.id || Math.random()}`,
          prestation: prestation,
          status: dep.status || 'required',
          // Prefer the combined TTC (with consumables VAT) when available
          base_price: (prestation.price_with_vat_and_consumables_variant && prestation.price_with_vat_and_consumables_variant.ttc_with_consumables_vat) || prestation.public_price || 0,
          final_price: (prestation.price_with_vat_and_consumables_variant && prestation.price_with_vat_and_consumables_variant.ttc_with_consumables_vat) || prestation.public_price || 0,
          patient_share: (prestation.price_with_vat_and_consumables_variant && prestation.price_with_vat_and_consumables_variant.ttc_with_consumables_vat) || prestation.public_price || 0,
          dependencies: [],
          isDependency: true,
          originalDependency: dep,
          parent_item_id: dep.parent_item_id // Track which parent this belongs to
        })
      }
    }
  })
  
  return items
})

// Status options for dropdown
const statusOptions = [
  { label: 'Pending', value: 'pending', severity: 'warning' },
  { label: 'In Progress', value: 'in_progress', severity: 'info' },
  { label: 'Completed', value: 'completed', severity: 'success' },
  { label: 'Cancelled', value: 'cancelled', severity: 'danger' },
  { label: 'Required', value: 'required', severity: 'secondary' }
]

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
  }).format(amount || 0)
}

const getStatusData = (status) => {
  return statusOptions.find(option => option.value === status) || statusOptions[0]
}

const getItemTypeIcon = (item) => {
  if (item.isDependency && item.prestation?.is_package) return 'pi pi-box'
  if (item.prestation_id) return 'pi pi-medical'
  if (item.package_id) return 'pi pi-box'
  return 'pi pi-circle'
}

const getItemTypeBadge = (item) => {
  if (item.isDependency && item.prestation?.is_package) return { label: 'Package', severity: 'warning' }
  if (item.prestation_id) return { label: 'Prestation', severity: 'success' }
  if (item.package_id) return { label: 'Package', severity: 'info' }
  return { label: 'Unknown', severity: 'secondary' }
}

// Human-friendly payment label formatter
const paymentLabel = (raw) => {
  const status = String(raw || 'unpaid').toLowerCase()
  if (status === 'unpaid') return 'Not Paid'
  if (status === 'partial') return 'Partial'
  if (status === 'paid') return 'Paid'
  // fallback: capitalize
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getConventionBadge = (item) => {
  if (!item.convention_id) return null
  
  return {
    label: item.convention_name || `Convention ${item.convention_id}`,
    severity: 'success'
  }
}

const updateItemStatus = async (item, newStatus) => {
  console.log('Updating item status:', item.id, newStatus)
  emit('item-updated', { itemId: item.id, status: newStatus })
}

const openDetails = () => {
  showDetailsModal.value = true
}

const openRemiseModal = () => {
  showRemiseModal.value = true
}

  const removeItemGroup = (itemsGroup) => {
    emit('remove-group', itemsGroup)
  }

  const removeItem = (itemId) => {
    emit('remove-item', itemId)
  }
const removeDependency = async (dependency) => {
  try {
    const result = await ficheNavetteService.removeDependency(dependency.id)
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Dependency removed successfully',
        life: 3000
      })
      
      // Emit event to refresh the parent component
      emit('dependency-removed', dependency.id)
      emit('item-updated', { refresh: true })
    } else {
      throw new Error(result.message || 'Failed to remove dependency')
    }
  } catch (error) {
    console.error('Error removing dependency:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to remove dependency',
      life: 3000
    })
  }
}

const openPaymentTypeDialog = (item) => {
  editingItem.value = item
  selectedPaymentType.value = item.default_payment_type || null
  showPaymentTypeDialog.value = true
}

const cancelPaymentTypeChange = () => {
  editingItem.value = null
  selectedPaymentType.value = null
  showPaymentTypeDialog.value = false
}

const savePaymentTypeChange = async () => {
  if (!editingItem.value) return
  try {
    let res

    // If editingItem is a dependency (has parent_item_id) call dependency endpoint
    if (editingItem.value.parent_item_id && editingItem.value.id) {
      // dependency ids are numeric in DB
      res = await ficheNavetteService.updateDependency(editingItem.value.id, {
        default_payment_type: selectedPaymentType.value
      })
    } else {
      // Otherwise update the fiche navette item
      res = await ficheNavetteService.updateFicheNavetteItem(props.ficheNavetteId, editingItem.value.id, {
        default_payment_type: selectedPaymentType.value
      })
    }

    if (res.success) {
      toast.add({ severity: 'success', summary: 'Saved', detail: 'Payment type updated', life: 3000 })
      // Notify parent to refresh items
      emit('item-updated', { refresh: true })
      cancelPaymentTypeChange()
    } else {
      throw new Error(res.message || 'Update failed')
    }
  } catch (error) {
    console.error('Error updating payment type:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: error.message || 'Failed to update payment type', life: 5000 })
  }
}

const confirmRemoveDependency = (dependency) => {
  confirm.require({
    message: `Are you sure you want to remove the dependency "${dependency.dependencyPrestation?.name || dependency.dependency_prestation?.name || 'Unknown'}"?`,
    header: 'Remove Dependency',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => removeDependency(dependency),
    reject: () => {
      // User cancelled
    }
  })
}

const handleApplyRemise = async (data) => {
  try {
    // Call the remise API endpoint
    const response = await axios.post('/api/remise/apply', data)
    
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Remise applied successfully',
        life: 3000
      })
      
      // Refresh the component data to show updated prices
      emit('item-updated', { refresh: true })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.data.message || 'Failed to apply remise',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error applying remise:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to apply remise',
      life: 3000
    })
  }
}

// Package replacement state
const showPackageReplacementDialog = ref(false)
const packageReplacementInfo = ref(null)

// Check if there's an existing package containing all prestations (current + new)
const checkExistingPackageContainingAllPrestations = (newPrestationIds) => {
  // Get current non-convention prestations
  const currentPrestationIds = getCurrentNonConventionPrestations()
  
  // Combine current and new prestation IDs
  const allPrestationIds = [...currentPrestationIds, ...newPrestationIds]
  
  // Find a package that contains all these prestations
  return props.packages.find(pkg => {
    // Get prestation IDs from this package
    const packagePrestationIds = getPackagePrestationIds(pkg)
    
    // Check if this package contains all the prestations we need
    return allPrestationIds.every(id => packagePrestationIds.includes(id))
  })
}

// Get current prestations excluding convention items and dependencies
const getCurrentNonConventionPrestations = () => {
  return groupItems.value
    .filter(item => 
      // Exclude convention items
      !item.convention_id && 
      // Exclude dependencies
      !item.isDependency
    )
    .map(item => item.prestation_id)
    .filter(Boolean)
}

// Get prestation IDs from a package, excluding convention items and dependencies
const getPackagePrestationIds = (pkg) => {
  if (!pkg.prestations) return []
  
  return pkg.prestations
    .filter(prestation => 
      // Exclude convention items
      !prestation.convention_id
    )
    .map(prestation => prestation.id)
    .filter(Boolean)
}

// Remove all prestations from an existing package
const removeAllPrestationsFromPackage = async (packageId) => {
  try {
    // Get all non-convention, non-dependency items from this package
    const itemsToRemove = groupItems.value.filter(item => 
      !item.convention_id && 
      !item.isDependency
    )
    
    // Remove each item
    for (const item of itemsToRemove) {
      await ficheNavetteService.removeFicheNavetteItem(props.ficheNavetteId, item.id)
    }
    
    return true
  } catch (error) {
    console.error('Error removing prestations from package:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to remove prestations from package',
      life: 3000
    })
    return false
  }
}

// Create a new package with the specified prestations
const createNewPackage = async (packageId, prestationIds) => {
  try {
    // Create the new package
    const response = await ficheNavetteService.createFicheNavetteItem({
      fiche_navette_id: props.ficheNavetteId,
      patient_id: props.patientId,
      package_id: packageId,
      prestation_ids: prestationIds
    })
    
    if (response.success) {
      return response.data
    } else {
      throw new Error(response.message || 'Failed to create package')
    }
  } catch (error) {
    console.error('Error creating new package:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to create new package',
      life: 3000
    })
    return null
  }
}

// Handle package replacement
const handlePackageReplacement = async (existingPackage, newPrestationIds) => {
  // Remove all prestations from the existing package
  const removed = await removeAllPrestationsFromPackage(existingPackage.id)
  if (!removed) return false
  
  // Get current non-convention prestations
  const currentPrestationIds = getCurrentNonConventionPrestations()
  
  // Combine current and new prestation IDs
  const allPrestationIds = [...currentPrestationIds, ...newPrestationIds]
  
  // Create a new package with all prestations
  const newPackage = await createNewPackage(existingPackage.id, allPrestationIds)
  if (!newPackage) return false
  
  // Set package replacement info for the dialog
  packageReplacementInfo.value = {
    oldPackage: existingPackage,
    newPackage: newPackage,
    includedPrestations: [
      ...props.prestations.filter(p => currentPrestationIds.includes(p.id)),
      ...props.prestations.filter(p => newPrestationIds.includes(p.id))
    ]
  }
  
  // Show the dialog
  showPackageReplacementDialog.value = true
  
  // Emit event to refresh the parent component
  emit('item-updated', { refresh: true })
  
  return true
}

// Close package replacement dialog
const closePackageReplacementDialog = () => {
  showPackageReplacementDialog.value = false
  packageReplacementInfo.value = null
}

// Public method to handle new items addition
const handleNewItemsAddition = (newPrestationIds) => {
  // Check if there's an existing package containing all prestations
  const existingPackage = checkExistingPackageContainingAllPrestations(newPrestationIds)
  
  if (existingPackage) {
    // Handle package replacement
    return handlePackageReplacement(existingPackage, newPrestationIds)
  }
  
  return false
}

// Expose the method to parent components
defineExpose({
  handleNewItemsAddition
})

// FIXED: Update the packagePrestations computed property
const packagePrestations = computed(() => {
  console.log('=== Computing packagePrestations ===')
  console.log('Group type:', props.group?.type)
  console.log('Group items:', props.group?.items)
  
  if (props.group.type !== 'package') {
    console.log('Not a package, returning empty array')
    return []
  }
  
  // Get prestations from the first item's package data
  const firstItem = props.group.items[0]
  console.log('First item:', firstItem)
  
  if (firstItem?.package?.prestations) {
    console.log('Found package prestations:', firstItem.package.prestations)
    return firstItem.package.prestations
  }
  
  console.log('No prestations found, returning empty array')
  return []
})

// FIXED: Update individualTotal to use the correct prestations
const individualTotal = computed(() => {
  const total = packagePrestations.value.reduce((total, prestation) => {
    return total + parseFloat(prestation.public_price || 0)
  }, 0)
  console.log('Individual total calculated:', total)
  return total
})

// NEW: compute a correct total for the group including main items + their dependencies (non-package)
const totalPrice = computed(() => {
  // If this is a package, use the package's total price directly
  if (props.group?.type === 'package') {
    // First try to get the declared package price from the group
    const declaredPackagePrice = parseFloat(props.group?.total_price ?? 0) || 0
    if (declaredPackagePrice > 0) {
      return declaredPackagePrice
    }
    
    // If no declared price, try to get it from the first item's package data
    const firstItem = groupItems.value[0]
    if (firstItem?.package?.total_price) {
      return parseFloat(firstItem.package.total_price) || 0
    }
    
    // If no package total price, try to get it from final_price of the main item
    if (firstItem?.final_price) {
      return parseFloat(firstItem.final_price) || 0
    }
    
    // Fallback: sum individual prestations in the package
    const packageSum = packagePrestations.value.reduce((sum, p) => {
      const v = parseFloat(p.public_price ?? p.final_price ?? 0) || 0
      return sum + v
    }, 0)
    
    if (packageSum > 0) return packageSum
  }

  // For non-package groups, sum main items + their dependencies
  // Sum main display items (these include package dependencies already added to mainDisplayItems)
  const mainSum = mainDisplayItems.value.reduce((sum, item) => {
    const v = parseFloat(item.final_price ?? item.base_price ?? item.prestation?.public_price ?? item.package?.total_price ?? 0) || 0
    return sum + v
  }, 0)

  // Sum regular (non-package) dependencies that belong to items in this group
  const dependencySum = regularDependencies.value.reduce((sum, dep) => {
    // price may be on dependency.dependencyPrestation.public_price or dep.price or dependency_prestation.public_price
    const price = parseFloat(
      dep.dependencyPrestation?.public_price
      ?? dep.dependencyPrestation?.final_price
      ?? dep.dependency_prestation?.public_price
      ?? dep.price
      ?? 0
    ) || 0
    return sum + price
  }, 0)

  return mainSum + dependencySum
})

// Get payment status information
const paymentStatusInfo = computed(() => {
  // Check if any item in the group has payment_status
  // Consider items with explicit payment_status or infer 'unpaid' when missing
  if (!groupItems.value || groupItems.value.length === 0) return null

  // Collect statuses, defaulting missing statuses to 'unpaid'
  const paymentStatuses = groupItems.value.map(item => item.payment_status ? String(item.payment_status).toLowerCase() : 'unpaid')
  const uniqueStatuses = [...new Set(paymentStatuses.filter(Boolean))]

  // Prioritize most severe status
  if (uniqueStatuses.includes('unpaid')) {
    return { status: 'unpaid', label: 'Not Paid', severity: 'danger', color: '#dc3545' }
  } else if (uniqueStatuses.includes('partial')) {
    return { status: 'partial', label: 'Partial', severity: 'warning', color: '#fd7e14' }
  } else if (uniqueStatuses.includes('paid')) {
    return { status: 'paid', label: 'Paid', severity: 'success', color: '#28a745' }
  }

  return null
})

// Doctor tags for display
const doctorTags = computed(() => {
  const doctors = []
  
  // Main group doctor
  if (props.group?.doctor_name) {
    doctors.push({
      id: props.group.doctor_id || 'unknown',
      name: props.group.doctor_name,
      source: 'group'
    })
  }
  
  // Collect doctors from all items in the group
  groupItems.value.forEach(item => {
    // Item doctor
    if (item.doctor_name && !doctors.some(d => d.id === item.doctor_id)) {
      doctors.push({
        id: item.doctor_id || 'unknown',
        name: item.doctor_name,
        source: 'item'
      })
    }
    
    // Package reception doctors (Emergency module - FIXED)
    if (item.packageReceptionRecords && Array.isArray(item.packageReceptionRecords)) {
      item.packageReceptionRecords.forEach(record => {
        if (record.doctor && !doctors.some(d => d.id === record.doctor.id)) {
          doctors.push({
            id: record.doctor.id,
            name: record.doctor.name,
            source: 'package_reception'
          })
        }
      })
    }
    
    // Package prestations doctors
    if (item.package?.prestations) {
      item.package.prestations.forEach(prestation => {
        if (prestation.doctor?.name && !doctors.some(d => d.id === prestation.doctor.id)) {
          doctors.push({
            id: prestation.doctor.id,
            name: prestation.doctor.name,
            source: 'package'
          })
        }
      })
    }
    
    // Dependencies doctors
    if (item.dependencies) {
      item.dependencies.forEach(dependency => {
        if (dependency.dependencyPrestation?.doctor?.name && !doctors.some(d => d.id === dependency.dependencyPrestation.doctor.id)) {
          doctors.push({
            id: dependency.dependencyPrestation.doctor.id,
            name: dependency.dependencyPrestation.doctor.name,
            source: 'dependency'
          })
        }
      })
    }
  })
  
  return doctors
})

// NEW: Computed property to get doctors from package reception records (Emergency module)
const packageDoctors = computed(() => {
  if (props.group?.type !== 'package') {
    return []
  }
  
  // Get all doctors from packageReceptionRecords of items in this group
  const doctors = []
  
  groupItems.value.forEach(item => {
    if (item.packageReceptionRecords && Array.isArray(item.packageReceptionRecords)) {
      item.packageReceptionRecords.forEach(record => {
        // Avoid duplicates by checking if doctor already exists
        if (record.doctor && !doctors.some(d => d.id === record.doctor.id)) {
          doctors.push({
            id: record.doctor.id,
            name: record.doctor.name,
            prestation_id: record.prestation_id,
            source: 'package_reception'
          })
        }
      })
    }
  })
  
  return doctors
})

// NEW: Function to get detailed package reception records for display
const getPackageReceptionDetails = () => {
  const details = []
  
  groupItems.value.forEach(item => {
    if (item.packageReceptionRecords && Array.isArray(item.packageReceptionRecords)) {
      item.packageReceptionRecords.forEach(record => {
        details.push({
          id: record.id,
          prestation_id: record.prestation_id,
          prestation_name: record.prestation?.name || 'Unknown Prestation',
          prestation_code: record.prestation?.internal_code || '',
          doctor_id: record.doctor?.id,
          doctor_name: record.doctor?.name || 'No Doctor Assigned',
          status: item.status || 'assigned'
        })
      })
    }
  })
  
  return details
}
</script>

<template>
  <div :class="cardStyleClass" :style="organismColor ? { borderColor: '#ffffff' } : {}">
    <!-- Header with organism color styling -->
    <div class="card-header" :style="headerStyle">
      <div class="header-left">
        <div class="" :style="cardIconStyle">
          <i :class="group?.type === 'package' ? 'pi pi-box' : 'pi pi-medical'"></i>
        </div>
        <div class="header-info">
          <h6 class="card-title">{{ cardTitle }}</h6>
          <small class="card-subtitle">{{ cardSubtitle }}</small>
        </div>
      </div>
      
      <!-- Header Actions with Payment Status -->
      <div class="header-actions">
        <!-- Payment Status Chip -->
        <Chip
          v-if="paymentStatusInfo"
          :label="paymentStatusInfo.label"
          :severity="paymentStatusInfo.severity"
          class="payment-status-chip"
          :style="{ backgroundColor: paymentStatusInfo.color, color: 'white', borderColor: paymentStatusInfo.color }"
          size="small"
        />
        
        <!-- Convention Chips -->
        <div v-if="conventionChips.length > 0" class="convention-chips">
          <Chip
            v-for="chip in conventionChips.slice(0, 2)"
            :key="chip.id"
            :label="chip.label"
            severity="info"
            class="convention-chip"
            size="small"
          />
          <Chip
            v-if="conventionChips.length > 2"
            :label="`+${conventionChips.length - 2}`"
            severity="secondary"
            size="small"
          />
        </div>
        
        <!-- Doctor Tags -->
        <div v-if="doctorTags.length > 0" class="doctor-chips">
          <Chip
            v-for="doctor in doctorTags.slice(0, 2)"
            :key="doctor.id"
            :label="`Dr. ${doctor.name}`"
            severity="secondary"
            class="doctor-chip"
            size="small"
            icon="pi pi-user-md"
          />
          <Chip
            v-if="doctorTags.length > 2"
            :label="`+${doctorTags.length - 2} more`"
            severity="secondary"
            size="small"
          />
        </div>

        <!-- Package Reception Doctors (Emergency module) -->
        <div v-if="packageDoctors.length > 0" class="package-doctor-chips">
          <Chip
            v-for="doctor in packageDoctors.slice(0, 2)"
            :key="`pkg-doc-${doctor.id}`"
            :label="`${doctor.name}`"
            severity="info"
            class="package-doctor-chip"
            size="small"
            icon="pi pi-box"
            title="Package Reception Doctor"
          />
          <Chip
            v-if="packageDoctors.length > 2"
            :label="`+${packageDoctors.length - 2} pkg`"
            severity="info"
            size="small"
          />
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="card-content">
      <!-- Convention info if available -->
      <div v-if="hasConventionItems" class="convention-info">
        <div class="info-item">
          <span class="info-label">Conventions:</span>
          <div class="convention-list">
            <Chip
              v-for="conv in conventionInfo"
              :key="conv.id"
              :label="conv.count > 1 ? `${conv.name} (${conv.count} items)` : conv.name"
              :severity="group?.type === 'package' ? 'warning' : 'success'"
              class="convention-detail"
            />
          </div>
        </div>
      </div>

      <!-- Summary Info -->
      <div class="summary-info">
        <div class="info-item">
          <span class="info-label">Items:</span>
          <Chip
            :label="`${mainDisplayItems.length} item${mainDisplayItems.length !== 1 ? 's' : ''}`"
            severity="secondary"
          />
        </div>
        
        <!-- Payment Status -->
        <div v-if="paymentStatusInfo" class="info-item">
          <span class="info-label">Payment:</span>
          <Chip
            :label="paymentStatusInfo.label"
            :severity="paymentStatusInfo.severity"
            class="payment-status-chip"
            :style="{ backgroundColor: paymentStatusInfo.color, color: 'white', borderColor: paymentStatusInfo.color }"
          />
        </div>
        
        <div class="info-item">
          <span class="info-label">Total:</span>
          <strong class="total-price" v-if="paymentStatusInfo && paymentStatusInfo.status === 'unpaid'">{{ paymentStatusInfo.label }}</strong>
          <strong class="total-price" v-else>{{ formatCurrency(totalPrice) }}</strong>
        </div>
      </div>

      <!-- Dependencies Summary -->
      <div v-if="regularDependencies && regularDependencies.length > 0" class="dependencies-summary">
        <div class="info-item">
          <span class="info-label">Dependencies:</span>
          <Chip
            :label="`${regularDependencies.length} dependency${regularDependencies.length !== 1 ? 'ies' : 'y'}`"
            severity="warning"
          />
        </div>
        <div class="dependencies-preview">
          <div
            v-for="(dependency, index) in regularDependencies.slice(0, 3)"
            :key="index"
            class="dependency-chip"
          >
            <Chip
              :label="dependency.dependencyPrestation?.name || dependency.dependency_prestation?.name || 'Unknown'"
              severity="secondary"
              class="dependency-item"
            />
          </div>
          <Chip
            v-if="regularDependencies.length > 3"
            :label="`+${regularDependencies.length - 3} more`"
            severity="info"
            class="more-deps"
          />
        </div>
      </div>
      
      <!-- Package Dependencies Info -->
      <div v-if="packageDependencies && packageDependencies.length > 0" class="package-dependencies-summary">
        <div class="info-item">
          <span class="info-label">Package Dependencies:</span>
          <Chip
            :label="`${packageDependencies.length} package${packageDependencies.length !== 1 ? 's' : ''}`"
            severity="info"
          />
        </div>
      </div>

      <!-- FIXED: Package Content Section -->
      <div v-if="group?.type === 'package'" class="package-content">
        <!-- Package Info -->
        <div class="package-info">
          <div class="package-details">
            <div class="detail-row">
              <span class="detail-label">Package Price:</span>
              <span class="detail-value package-price">{{ formatCurrency(totalPrice) }}</span>
            </div>
            <div v-if="group.doctor_name" class="detail-row">
              <span class="detail-label">Assigned Doctor:</span>
              <span class="detail-value">Dr. {{ group.doctor_name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer - Fixed at bottom -->
    <div class="card-footer">
      <Button
        v-if="!paymentStatusInfo || paymentStatusInfo.status !== 'paid'"
        icon="pi pi-percentage"
        label="Remise"
        class="p-button-outlined p-button-warning p-button-sm"
        @click="openRemiseModal"
      />
      <Button
        icon="pi pi-eye"
        label="Details"
        class="p-button-outlined p-button-secondary p-button-sm"
        @click="openDetails"
      />
      <Button
        v-if="!paymentStatusInfo || (paymentStatusInfo.status !== 'paid' && paymentStatusInfo.status !== 'partial')"
        icon="pi pi-trash"
        label="Remove"
        class="p-button-outlined p-button-danger p-button-sm"
        @click="removeItemGroup(groupItems)"
      />
    </div>

    <!-- Details Modal -->
    <Dialog
      v-model:visible="showDetailsModal"
      :header="`${cardTitle} - Details`"
      :style="{ width: '1200px', maxHeight: '90vh' }"
      :modal="true"
      class="details-modal"
    >
      <div class="details-content">
        <!-- Group Info -->
        <Card class="group-info mb-4">
          <template #content>
            <div class="group-details">
              <div class="detail-item">
                <span class="detail-label">Type:</span>
                <Chip
                  :label="group.type === 'package' ? 'Package' : 'Individual Prestation'"
                  :severity="group.type === 'package' ? 'info' : 'success'"
                />
              </div>
              
              <div v-if="hasConventionItems" class="detail-item">
                <span class="detail-label">Conventions:</span>
                <div class="convention-badges">
                  <Chip
                    v-for="conv in conventionInfo"
                    :key="conv.id"
                    :label="`${conv.name} (${conv.count} items)`"
                    :severity="group.type === 'package' ? 'warning' : 'success'"
                  />
                </div>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Doctor:</span>
                <span>{{ group.doctor_name || 'Not assigned' }}</span>
              </div>
              
              <!-- Payment Status in Details -->
              <div v-if="paymentStatusInfo" class="detail-item">
                <span class="detail-label">Payment Status:</span>
                <Chip
                  :label="paymentStatusInfo.label"
                  :severity="paymentStatusInfo.severity"
                  class="payment-status-chip"
                  :style="{ backgroundColor: paymentStatusInfo.color, color: 'white', borderColor: paymentStatusInfo.color }"
                />
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Total Price:</span>
                <strong class="total-amount">{{ formatCurrency(totalPrice) }}</strong>
              </div>
              
              <!-- Package-specific info -->
              <div v-if="group.type === 'package'" class="detail-item">
                <span class="detail-label">Package Price:</span>
                <span class="detail-value package-price">{{ formatCurrency(totalPrice) }}</span>
              </div>
              
              <!-- Package Savings Info -->
              <div v-if="group.type === 'package' && showSavings" class="detail-item">
                <span class="detail-label">Savings:</span>
                <div class="savings-chips">
                  <Chip
                    :label="`Individual Total: ${formatCurrency(individualTotal)}`"
                    severity="warning"
                    class="mr-2"
                  />
                  <Chip
                    :label="`You Save: ${formatCurrency(individualTotal - group.total_price)}`"
                    severity="success"
                  />
                </div>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Regular Dependencies:</span>
                <Chip
                  :label="`${regularDependencies.length} dependencies`"
                  severity="warning"
                />
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Package Dependencies:</span>
                <Chip
                  :label="`${packageDependencies.length} packages`"
                  severity="info"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- ADDED: Package Prestations Table (for packages only) -->
        <Card v-if="group.type === 'package' && packagePrestations.length > 0" class="mb-4">
          <template #title>
            <div class="table-title">
              <i class="pi pi-box"></i>
              Package Prestations ({{ packagePrestations.length }})
            </div>
          </template>
          <template #content>
            <DataTable
              :value="packagePrestations"
              class="package-prestations-table"
              responsiveLayout="scroll"
              :rowHover="true"
              :rows="10"
              :paginator="packagePrestations.length > 10"
            >
              <Column field="name" header="Prestation Name" :sortable="true">
                <template #body="{ data }">
                  <div class="prestation-name-cell">
                    <i class="pi pi-medical prestation-icon"></i>
                    <div class="prestation-details">
                      <div class="prestation-name">{{ data.name }}</div>
                      <small class="prestation-code">{{ data.internal_code || 'N/A' }}</small>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="service_name" header="Service" :sortable="true">
                <template #body="{ data }">
                  <Chip
                    :label="data.service_name || 'N/A'"
                    severity="secondary"
                    size="small"
                  />
                </template>
              </Column>

              <Column field="specialization_name" header="Specialization" :sortable="true">
                <template #body="{ data }">
                  <Chip
                    :label="data.specialization_name || 'N/A'"
                    severity="info"
                    size="small"
                  />
                </template>
              </Column>

              <Column field="public_price" header="Individual Price" :sortable="true">
                <template #body="{ data }">
                  <div class="price-cell">
                    <span class="individual-price">{{ formatCurrency(data.public_price) }}</span>
                  </div>
                </template>
              </Column>

              <!-- <Column field="need_an_appointment" header="Appointment" :sortable="true">
                <template #body="{ data }">
                  <Tag
                    :value="data.need_an_appointment ? 'Required' : 'Not Required'"
                    :severity="data.need_an_appointment ? 'warning' : 'success'"
                    size="small"
                  />
                </template>
              </Column> -->
             <Column field="status" header="Appointment" :sortable="true">
                <template #body="{ data }">
                  <Tag
                    :value="data.status "
                    :severity="data.status"
                    size="small"
                  />
                </template>
              </Column>

<!-- 
              <Column header="Actions" style="width: 120px">
                <template #body="{ data }">
                  <div class="prestation-actions">
                    <Button
                      icon="pi pi-info-circle"
                      class="p-button-rounded p-button-text p-button-sm p-button-info"
                      @click="viewPrestationDetails(data)"
                      v-tooltip.top="'View prestation details'"
                    />
                  </div>
                </template>
              </Column> -->
            </DataTable>

            <!-- Package Summary -->
            <div class="package-summary mt-3">
              <div class="summary-row">
                <span class="summary-label">Individual Prestations Total:</span>
                <span class="summary-value individual-total">{{ formatCurrency(individualTotal) }}</span>
              </div>
              <div class="summary-row package-price-row">
                <span class="summary-label">Package Price:</span>
                <span class="summary-value package-price">{{ formatCurrency(totalPrice) }}</span>
              </div>
              <div v-if="showSavings" class="summary-row savings-row">
                <span class="summary-label">Your Savings:</span>
                <span class="summary-value savings-amount">{{ formatCurrency(individualTotal - totalPrice) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <!-- Main Items Table (including package dependencies) -->
        <Card class="mb-4">
          <template #title>
            <div class="table-title">
              <i class="pi pi-list"></i>
              Main Items ({{ mainDisplayItems.length }})
            </div>
          </template>
          <template #content>
            <DataTable
              v-if="mainDisplayItems && mainDisplayItems.length > 0"
              :value="mainDisplayItems"
              class="items-table"
              responsiveLayout="scroll"
              :rowHover="true"
            >
              <Column field="name" header="Name">
                <template #body="{ data }">
                  <div class="item-name-cell">
                    <i :class="getItemTypeIcon(data)" class="item-icon"></i>
                    <div class="item-details">
                      <div class="item-name">{{ data.prestation?.name || data.package?.name || 'Unknown' }}</div>
                      <small class="item-code">{{ data.prestation?.internal_code || data.package?.internal_code || 'N/A' }}</small>
                      
                      <!-- Show convention badge -->
                      <div v-if="getConventionBadge(data)" class="convention-badge">
                        <Chip 
                          :label="getConventionBadge(data).label" 
                          :severity="getConventionBadge(data).severity" 
                          size="small"
                        />
                      </div>
                      
                      <!-- Show if it's a dependency -->
                      <div v-if="data.isDependency" class="dependency-badge">
                        <Chip 
                          :label="getItemTypeBadge(data).label" 
                          :severity="getItemTypeBadge(data).severity" 
                          size="small"
                        />
                      </div>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="status" header="Status">
                <template #body="{ data }">
                  <Tag
                    :value="getStatusData(data.status).label"
                    :severity="getStatusData(data.status).severity"
                  />
                </template>
              </Column>

              <!-- Payment Status Column -->
              <Column field="payment_status" header="Payment Status">
                <template #body="{ data }">
                  <Chip
                    :label="paymentLabel(data.payment_status)"
                    :severity="(data.payment_status || 'unpaid') === 'paid' ? 'success' : (data.payment_status || 'unpaid') === 'partial' ? 'warning' : 'danger'"
                    size="small"
                    :style="{
                      backgroundColor: (data.payment_status || 'unpaid') === 'paid' ? '#28a745' : 
                                     (data.payment_status || 'unpaid') === 'partial' ? '#fd7e14' : '#dc3545',
                      color: 'white',
                      borderColor: (data.payment_status || 'unpaid') === 'paid' ? '#28a745' : 
                                  (data.payment_status || 'unpaid') === 'partial' ? '#fd7e14' : '#dc3545'
                    }"
                  />
                </template>
              </Column>

              <Column field="final_price" header="Final Price">
                <template #body="{ data }">
                  <strong>{{ formatCurrency(data.final_price ?? data.base_price ?? 0) }}</strong>
                </template>
              </Column>

              <Column header="Dependencies">
                <template #body="{ data }">
                  <div v-if="data.isDependency" class="dependency-info">
                    <!-- <Chip label="From dependency" severity="warning" size="small" /> -->
                  </div>
                  <div v-else-if="data.dependencies && data.dependencies.length > 0">
                    <Chip
                      :label="`${data.dependencies.length} deps`"
                      severity="warning"
                    />
                  </div>
                  <span v-else class="text-muted">No dependencies</span>
                </template>
              </Column>

              <Column header="Actions">
                <template #body="{ data }">
                  <div class="item-actions">
                    <Button
                      icon="pi pi-pencil"
                      class="p-button-rounded p-button-text p-button-sm p-button-info"
                      @click="openPaymentTypeDialog(data)"
                      v-tooltip.top="'Edit payment type'"
                    />
                    <Button
                      icon="pi pi-trash"
                      class="p-button-rounded p-button-text p-button-sm p-button-danger"
                      @click="removeItem(data.id)"
                      v-tooltip.top="'Remove item'"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
            <div v-else class="no-items">
              <p>No items found in this group.</p>
            </div>
          </template>
        </Card>

        <!-- Package Reception Doctors Table (Emergency Module) -->
        <Card v-if="packageDoctors.length > 0" class="mb-4">
          <template #title>
            <div class="table-title">
              <i class="pi pi-user-md"></i>
              Package Doctor Assignments ({{ packageDoctors.length }})
            </div>
          </template>
          <template #content>
            <div class="package-reception-info mb-3">
              <p class="info-text">
                <i class="pi pi-info-circle"></i>
                This package has multiple prestations assigned to different doctors. Each prestation's assigned doctor is shown below.
              </p>
            </div>
            
            <DataTable
              :value="getPackageReceptionDetails()"
              class="package-reception-table"
              responsiveLayout="scroll"
              :rowHover="true"
              :paginator="getPackageReceptionDetails().length > 10"
              :rows="10"
            >
              <Column field="prestation_name" header="Prestation" :sortable="true">
                <template #body="{ data }">
                  <div class="prestation-name-cell">
                    <i class="pi pi-medical prestation-icon"></i>
                    <div class="prestation-details">
                      <div class="prestation-name">{{ data.prestation_name }}</div>
                      <small class="prestation-code">{{ data.prestation_code || 'N/A' }}</small>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="doctor_name" header="Assigned Doctor" :sortable="true">
                <template #body="{ data }">
                  <div class="doctor-cell">
                    <i class="pi pi-user-md doctor-icon"></i>
                    <span class="doctor-name">Dr. {{ data.doctor_name }}</span>
                  </div>
                </template>
              </Column>

              <Column field="status" header="Status" :sortable="true">
                <template #body="{ data }">
                  <Tag
                    :value="data.status || 'Assigned'"
                    severity="info"
                    size="small"
                  />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Regular Dependencies Table -->
        <Card v-if="regularDependencies.length > 0" class="mb-4">
          <template #title>
            <div class="table-title">
              <i class="pi pi-sitemap"></i>
              Regular Dependencies ({{ regularDependencies.length }})
            </div>
          </template>
          <template #content>
            <DataTable
              :value="regularDependencies"
              class="dependencies-table"
              responsiveLayout="scroll"
              :rowHover="true"
            >
              <Column field="dependency_name" header="Dependency Name">
                <template #body="{ data }">
                  <div class="dependency-name-cell">
                    <i class="pi pi-arrow-right dependency-arrow"></i>
                    <div class="dependency-details">
                      <div class="dependency-name">
                        {{ data.dependencyPrestation?.name || data.dependency_prestation?.name || 'Unknown' }}
                      </div>
                      <small class="dependency-code">
                        {{ data.dependencyPrestation?.internal_code || data.dependency_prestation?.internal_code || 'N/A' }}
                      </small>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="dependency_type" header="Type">
                <template #body="{ data }">
                  <Tag
                    :value="data.dependency_type || 'standard'"
                    :severity="data.dependency_type === 'required' ? 'danger' : 'info'"
                  />
                </template>
              </Column>

              <Column field="specialization" header="Specialization">
                <template #body="{ data }">
                  <Chip
                    :label="data.dependencyPrestation?.specialization_name || data.dependency_prestation?.specialization_name || 'N/A'"
                    severity="secondary"
                  />
                </template>
              </Column>

              <Column field="price" header="Price">
                <template #body="{ data }">
                  {{ formatCurrency(data.dependencyPrestation?.public_price || data.dependency_prestation?.public_price || 0) }}
                </template>
              </Column>

              <Column field="notes" header="Notes">
                <template #body="{ data }">
                  <span class="notes-text">{{ data.notes || 'No notes' }}</span>
                </template>
              </Column>
                <Column field="status" header="Appointment" :sortable="true">
                <template #body="{ data }">
                  <Tag
                    :value="data.status "
                    :severity="data.status"
                    size="small"
                  />
                </template>
              </Column>

              <Column header="Parent Item">
                <template #body="{ data }">
                  <small class="parent-item">
                    {{ data.parentItem?.prestation?.name || data.parentItem?.package?.name || 'Unknown' }}
                  </small>
                </template>
              </Column>

              <!-- Add Actions Column for Delete Button -->
              <Column header="Actions" style="width: 120px">
                <template #body="{ data }">
                      <div class="dependency-actions">
                        <Button
                          icon="pi pi-pencil"
                          class="p-button-rounded p-button-text p-button-sm p-button-info"
                          @click="openPaymentTypeDialog(data)"
                          v-tooltip.top="'Edit payment type'"
                        />
                        <Button
                          icon="pi pi-trash"
                          class="p-button-rounded p-button-text p-button-sm p-button-danger"
                          @click="confirmRemoveDependency(data)"
                          v-tooltip.top="'Remove dependency'"
                        />
                      </div>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <template #footer>
        <Button
          label="Close"
          icon="pi pi-times"
          class="p-button-text"
          @click="showDetailsModal = false"
        />
      </template>
    </Dialog>

    <!-- Remise Modal -->
    <RemiseModal
      v-model:visible="showRemiseModal"
      :patientId="props.patientId"
      :group="group"
      :ficheNavetteId="props.ficheNavetteId"
      :prestations="prestations"
      :doctors="doctors"
      @apply-remise="handleApplyRemise"
    />

    <!-- Payment Type Edit Dialog -->
    <Dialog
      v-model:visible="showPaymentTypeDialog"
      header="Edit Payment Type"
      :modal="true"
      :style="{ width: '420px' }"
      :closable="false"
    >
      <div class="p-fluid">
        <div class="p-field">
          <label for="paymentType">Payment Type</label>
          <Dropdown
            inputId="paymentType"
            :options="paymentTypeOptions"
            optionLabel="label"
            optionValue="value"
            v-model="selectedPaymentType"
            placeholder="Select a payment type"
          />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" class="p-button-text" @click="cancelPaymentTypeChange" />
        <Button label="Save" icon="pi pi-check" @click="savePaymentTypeChange" />
      </template>
    </Dialog>

    <!-- Package Replacement Information Dialog -->
    <Dialog
      v-model:visible="showPackageReplacementDialog"
      header="Package Replacement Information"
      :modal="true"
      :style="{ width: '550px' }"
      :closable="true"
      @hide="closePackageReplacementDialog"
    >
      <div v-if="packageReplacementInfo" class="package-replacement-dialog">
        <div class="info-section">
          <i class="pi pi-info-circle info-icon"></i>
          <p class="info-text">
            We've detected that the selected prestations can be combined into a package.
            The following changes have been made:
          </p>
        </div>

        <div class="replacement-details">
          <div class="old-package">
            <h4>Original Package:</h4>
            <p>{{ packageReplacementInfo.oldPackage.name }}</p>
            <small>{{ packageReplacementInfo.oldPackage.internal_code || 'No code' }}</small>
          </div>

          <div class="new-package">
            <h4>New Package:</h4>
            <p>{{ packageReplacementInfo.newPackage.package?.name || packageReplacementInfo.oldPackage.name }}</p>
            <small>{{ packageReplacementInfo.newPackage.package?.internal_code || packageReplacementInfo.oldPackage.internal_code || 'No code' }}</small>
          </div>

          <div class="included-prestations">
            <h4>Included Prestations:</h4>
            <ul>
              <li v-for="prestation in packageReplacementInfo.includedPrestations" :key="prestation.id">
                {{ prestation.name }}
                <small>({{ prestation.internal_code || 'No code' }})</small>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="OK" icon="pi pi-check" @click="closePackageReplacementDialog" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
@reference "../../../../../../resources/css/app.css";

/* Package Replacement Dialog Styles */
.package-replacement-dialog {
  padding: 1rem;
}

.info-section {
  display: flex;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  background-color: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
}

.info-icon {
  font-size: 1.5rem;
  color: #3b82f6;
  margin-right: 1rem;
}

.info-text {
  margin: 0;
  line-height: 1.5;
}

.replacement-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.old-package, .new-package, .included-prestations {
  padding: 1rem;
  border-radius: 6px;
  background-color: #f8f9fa;
}

.old-package h4, .new-package h4, .included-prestations h4 {
  margin-top: 0;
  margin-bottom: 0.5rem;
  color: #495057;
}

.included-prestations ul {
  margin: 0.5rem 0 0 0;
  padding-left: 1.5rem;
}

.included-prestations li {
  margin-bottom: 0.5rem;
}

.included-prestations small {
  color: #6c757d;
  margin-left: 0.5rem;
}

/* Base Card Styles - Using Flexbox Layout */
.item-card {
  margin-bottom: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  border: 2px solid transparent;
  border-radius: 8px;
  background: white;
  overflow: hidden;
  
  /* Flexbox for consistent layout */
  display: flex;
  flex-direction: column;
  min-height: 280px; /* Ensures consistent card height */
}

.item-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}

/* Default card (no convention) */
.default-card {
  border-color: #e9ecef;
}

/* Convention-based card styles */
.convention-prestation-card {
  border-color: var(--green-500, #28a745);
  background: linear-gradient(135deg, rgba(40, 167, 69, 0.02) 0%, rgba(40, 167, 69, 0.05) 100%);
}

.convention-prestation-card:hover {
  border-color: var(--green-600, #218838);
  box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
}

.convention-package-card {
  border-color: var(--orange-500, #fd7e14);
  background: linear-gradient(135deg, rgba(253, 126, 20, 0.02) 0%, rgba(253, 126, 20, 0.05) 100%);
}

.dependency-actions {
  display: flex;
  justify-content: center;
  align-items: center;
}

.dependency-actions .p-button {
  transition: all 0.2s ease;
}

.dependency-actions .p-button:hover {
  transform: scale(1.1);
}

/* Add some spacing for the actions column */
.dependencies-table :deep(.p-datatable-tbody > tr > td:last-child) {
  text-align: center;
}

.convention-package-card:hover {
  border-color: var(--orange-600, #e86503);
  box-shadow: 0 4px 8px rgba(253, 126, 20, 0.2);
}

/* Header Styles */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e9ecef;
  flex-shrink: 0; /* Prevents header from shrinking */
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-icon {
  background: var(--primary-color, #007bff);
  color: white;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.header-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
  color: #2c3e50;
}

.card-subtitle {
  color: #6c757d;
  font-size: 0.85rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  flex-shrink: 0;
}

.convention-chips {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.convention-chip {
  font-size: 0.75rem;
}

.doctor-chips {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.doctor-chip {
  font-size: 0.75rem;
  background-color: var(--blue-100) !important;
  color: var(--blue-700) !important;
  border-color: var(--blue-200) !important;
}

.type-chip {
  font-weight: 500;
  font-size: 0.8rem;
  white-space: nowrap;
}

/* Content Styles - Flexible content area */
.card-content {
  padding: 1rem;
  flex: 1; /* Takes available space, pushing footer to bottom */
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.convention-info {
  padding-bottom: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.convention-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.convention-detail {
  font-size: 0.85rem;
}

.convention-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.summary-info {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.info-label {
  font-weight: 500;
  color: #495057;
  white-space: nowrap;
}

.total-price {
  color: #28a745;
  font-size: 1.1rem;
  font-weight: 600;
}

.dependencies-summary {
  border-top: 1px solid #e9ecef;
  padding-top: 1rem;
}

.package-dependencies-summary {
  border-top: 1px solid #e9ecef;
  padding-top: 1rem;
}

.dependencies-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

/* Footer Styles - Fixed at bottom */
.card-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
  flex-shrink: 0; /* Prevents footer from shrinking */
  margin-top: auto; /* Pushes footer to bottom when content is short */
}

/* Modal Styles */
.details-modal {
  max-height: 90vh;
}

.details-content {
  max-height: 70vh;
  overflow-y: auto;
}

.group-info .group-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
}

.total-amount {
  color: #28a745;
  font-size: 1.2rem;
}

/* Table Styles */
.table-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.item-name-cell,
.dependency-name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.item-icon {
  color: #007bff;
  font-size: 1.1rem;
}

.dependency-arrow {
  color: #007bff;
  font-size: 0.9rem;
}

.item-details,
.dependency-details {
  display: flex;
  flex-direction: column;
}

.item-name,
.dependency-name {
  font-weight: 500;
  color: #2c3e50;
}

.item-code,
.dependency-code {
  color: #6c757d;
  font-size: 0.8rem;
}

.convention-badge,
.dependency-badge {
  margin-top: 0.25rem;
}

.dependency-info {
  font-style: italic;
  color: #f39c12;
}

.notes-text {
  color: #495057;
  font-style: italic;
}

.parent-item {
  color: #6c757d;
  font-size: 0.8rem;
}

.no-items {
  text-align: center;
  padding: 2rem;
  color: #6c757d;
}

.text-muted {
  color: #6c757d;
  font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
  .item-card {
    min-height: auto; /* Allow flexible height on mobile */
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .header-actions {
    justify-content: flex-start;
  }

  .summary-info {
    flex-direction: column;
    gap: 0.75rem;
  }

  .card-footer {
    flex-direction: column;
    gap: 0.5rem;
  }

  .card-footer .p-button {
    width: 100%;
  }

  .group-details {
    grid-template-columns: 1fr;
  }

  .convention-chips {
    order: -1;
  }
}

@media (max-width: 480px) {
  .card-content {
    padding: 0.75rem;
  }
  
  .card-header {
    padding: 0.75rem;
  }
  
  .card-footer {
    padding: 0.75rem;
  }
}

/* Utility Classes */
.mb-4 {
  margin-bottom: 1.5rem;
}

/* Add these new styles to your existing CSS */

/* Organism-based convention card styles */
.organism-convention-card {
  transition: all 0.3s ease;
  border: 2px solid;
  border-radius: 8px;
  background: white;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 280px;
}

.organism-convention-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  filter: brightness(1.02);
}

/* Enhanced header styling for organism colors */
.organism-convention-card .card-header {
  position: relative;
}

.organism-convention-card .card-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--organism-color, transparent);
}

/* Convention chip styling with organism colors */
.organism-convention-card .convention-chip {
  border: 1px solid;
  font-weight: 600;
  transition: all 0.2s ease;
}

.organism-convention-card .convention-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  filter: brightness(1.1);
}

.organism-convention-card .type-chip {
  border: 1px solid;
  font-weight: 600;
  transition: all 0.2s ease;
}

.organism-convention-card .type-chip:hover {
  filter: brightness(1.1);
}

/* Enhanced card icon for organism colors */
.organism-convention-card .card-icon {
  position: relative;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

.organism-convention-card .card-icon:hover {
  transform: scale(1.1);
  filter: brightness(1.1);
}

/* Add these new styles for better package prestations display */
.package-prestations {
  margin-top: 1.5rem;
}

.prestations-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem 0;
  color: var(--text-color);
  font-size: 1.1rem;
  font-weight: 600;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--surface-200);
}

.prestations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.prestation-card {
  padding: 1rem;
  background: white;
  border: 1px solid var(--surface-200);
  border-radius: 8px;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.prestation-card:hover {
  border-color: var(--primary-300);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.prestation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.prestation-name {
  font-weight: 600;
  color: var(--text-color);
  flex: 1;
  min-width: 0;
  word-break: break-word;
}

.prestation-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
}

.prestation-code,
.prestation-service,
.prestation-specialization {
  font-size: 0.85rem;
  color: var(--text-color-secondary);
}

.prestation-price {
  text-align: right;
}

.individual-price {
  background: var(--blue-100);
  color: var(--blue-700);
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  display: inline-block;
}

.package-savings {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--green-50);
  border: 1px solid var(--green-200);
  border-radius: 8px;
  margin-top: 1rem;
}

.savings-info,
.savings-amount {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.savings-label {
  font-size: 0.85rem;
  color: var(--text-color-secondary);
  font-weight: 500;
}

.individual-total {
  text-decoration: line-through;
  color: var(--text-color-secondary);
  font-size: 1rem;
}

.savings-value {
  color: var(--green-600);
  font-weight: 700;
  font-size: 1.1rem;
}

.no-prestations {
  margin-top: 1.5rem;
  padding: 2rem;
  text-align: center;
  background: var(--orange-50);
  border: 1px solid var(--orange-200);
  border-radius: 8px;
}

.no-prestations-message h5 {
  margin: 0.5rem 0;
  color: var(--orange-600);
}

.no-prestations-message p {
  margin: 0;
  color: var(--text-color-secondary);
}

.debug-info h6 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.debug-info p {
  margin: 0.25rem 0;
  color: #666;
  word-break: break-all;
}

/* Payment Status Styles */
.payment-status-chip {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.payment-status-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Payment status specific colors */
.payment-status-unpaid {
  background-color: #dc3545 !important;
  color: white !important;
  border-color: #dc3545 !important;
}

.payment-status-partial {
  background-color: #fd7e14 !important;
  color: white !important;
  border-color: #fd7e14 !important;
}

.payment-status-paid {
  background-color: #28a745 !important;
  color: white !important;
  border-color: #28a745 !important;
}

/* Package Reception Doctors Table Styles */
.package-reception-info {
  padding: 0.75rem 1rem;
  background-color: #e7f3ff;
  border-left: 4px solid #007bff;
  border-radius: 6px;
}

.package-reception-info .info-text {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #2c3e50;
  font-size: 0.9rem;
}

.package-reception-info .pi-info-circle {
  color: #007bff;
  font-size: 1.1rem;
}

.package-reception-table .prestation-name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.package-reception-table .prestation-icon {
  color: #28a745;
  font-size: 1.1rem;
}

.package-reception-table .prestation-details {
  display: flex;
  flex-direction: column;
}

.package-reception-table .prestation-name {
  font-weight: 500;
  color: #2c3e50;
}

.package-reception-table .prestation-code {
  color: #6c757d;
  font-size: 0.8rem;
}

.package-reception-table .doctor-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.package-reception-table .doctor-icon {
  color: #007bff;
  font-size: 1rem;
}

.package-reception-table .doctor-name {
  font-weight: 500;
  color: #2c3e50;
}

/* Package Doctor Chips Styles */
.package-doctor-chips {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.package-doctor-chip {
  font-size: 0.75rem;
  background-color: #e3f2fd !important;
  color: #1976d2 !important;
  border-color: #90caf9 !important;
  font-weight: 500;
}

.package-doctor-chip .pi-box {
  font-size: 0.65rem;
}
</style>
