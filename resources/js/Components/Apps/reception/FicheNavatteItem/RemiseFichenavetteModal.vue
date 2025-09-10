<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import InputNumber from 'primevue/inputnumber'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'

// Services
import { remiseService } from '../../../Apps/services/Remise/RemiseService'
import { userService } from '../../../Apps/services/User/userService'
import RemiseNotificationService from '../../../Apps/services/Remise/RemiseNotificationService'

// Props
const props = defineProps({
  visible: Boolean,
  group: Object,
  prestations: Array,
  patientId: String,
  doctors: Array,
})

// Emits
const emit = defineEmits(['update:visible', 'apply-remise'])

// State
const selectedRemise = ref(null)
const availableRemises = ref([])
const users = ref([])
const isCustomDiscount = ref(false)
const loading = ref(false)
const toast = useToast()

// Modified state for per-prestation contributions
const selectedUser = ref(null)
const selectedDoctor = ref(null)
const prestationContributions = ref([]) // Array of { prestationId, userId, doctorId, userAmount, doctorAmount }
const notifyLoading = ref(false)
const activeTab = ref(0)

// Add salary tracking for selected users
const userSalaries = ref(new Map()) // Map to store user ID -> salary info

// Watch for changes in the custom discount checkbox
watch(isCustomDiscount, (enabled) => {
  if (enabled) {
    activeTab.value = 1
    selectedRemise.value = null
    initializePrestationContributions()
  } else {
    activeTab.value = 0
  }
})

// Update total contribution when amounts change
const updateContributionTotal = (index) => {
  const contribution = prestationContributions.value[index]
  contribution.totalContribution = Number(contribution.userAmount || 0) + Number(contribution.doctorAmount || 0)
}

// Computed properties for validation and totals
const totalUserContributions = computed(() => {
  return prestationContributions.value.reduce((sum, contrib) => sum + Number(contrib.userAmount || 0), 0)
})

const totalDoctorContributions = computed(() => {
  return prestationContributions.value.reduce((sum, contrib) => sum + Number(contrib.doctorAmount || 0), 0)
})

const totalContributions = computed(() => {
  return totalUserContributions.value + totalDoctorContributions.value
})

const hasValidContributions = computed(() => {
  return prestationContributions.value.some(contrib => 
    (Number(contrib.userAmount || 0) > 0) || (Number(contrib.doctorAmount || 0) > 0)
  )
})

// Notify function for all contributions
const notifyAllContributions = async () => {
  if (!hasValidContributions.value) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Warning', 
      detail: 'Please set at least one contribution amount', 
      life: 2500 
    })
    return
  }

  // Validate all user contributions against their salaries
  const salaryValidationErrors = []
  
  // Group contributions by user
  const userTotals = new Map()
  prestationDisplayData.value.forEach((item, index) => {
    if (item.selectedUser && item.userAmount > 0) {
      const current = userTotals.get(item.selectedUser) || 0
      userTotals.set(item.selectedUser, current + Number(item.userAmount))
    }
  })
  
  // Check each user's total against their salary
  userTotals.forEach((totalAmount, userId) => {
    if (userSalaries.value.has(userId)) {
      const userInfo = userSalaries.value.get(userId)
      if (totalAmount > userInfo.salary) {
        salaryValidationErrors.push(
          `${userInfo.name} cannot afford total contribution of ${formatCurrency(totalAmount)} (Salary: ${formatCurrency(userInfo.salary)})`
        )
      }
    }
  })
  
  if (salaryValidationErrors.length > 0) {
    toast.add({
      severity: 'error',
      summary: 'Salary Validation Failed',
      detail: salaryValidationErrors.join('; '),
      life: 5000
    })
    return
  }

  try {
    notifyLoading.value = true
    // Prepare the remise request payload following your scenario structure
    const requestPayload = {
      sender_id: props.group?.user?.id, // Current user (nurse)
      receiver_id: selectedDoctor.value?.id || prestationContributions.value.find(c => c.doctorId)?.doctorId,
      approver_id: null, // Can be set if needed

      patient_id: props.patientId,
      message: `Remise request for ${prestationContributions.value.length} prestations with total contribution of ${formatCurrency(totalContributions.value)}`,
      prestations: prestationContributions.value
        .filter(contrib => contrib.totalContribution > 0)
        .map(contrib => ({
          prestation_id: contrib.prestationId,
          proposed_amount: contrib.totalContribution,
          contributions: [
            ...(contrib.userAmount > 0 ? [{
              user_id: contrib.userId,
              role: 'user',
              proposed_amount: contrib.userAmount
            }] : []),
            ...(contrib.doctorAmount > 0 ? [{
              user_id: contrib.doctorId,
              role: 'doctor', 
              proposed_amount: contrib.doctorAmount
            }] : [])
          ]
        }))
    }
    console.log(prestationContributions.value)
    // Create the remise request using the notification service
    const result = await RemiseNotificationService.createRequest(requestPayload)
    
    if (result.success) {
      toast.add({ 
        severity: 'success', 
        summary: 'Success', 
        detail: 'Remise request created and notifications sent successfully', 
        life: 3000 
      })
      
      // Emit the result to parent component
      emit('apply-remise', {
        type: 'custom_request',
        data: result.data,
        contributions: prestationContributions.value.filter(c => c.totalContribution > 0)
      })
      
      dialogVisible.value = false
      resetForm()
    } else {
      throw new Error(result.message || 'Failed to create remise request')
    }

  } catch (err) {
    console.error('Error creating remise request:', err)
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: err.message || 'Failed to create remise request', 
      life: 3000 
    })
  } finally {
    notifyLoading.value = false
  }
}

// Watch for changes in selectedUser - auto-apply to all prestations
watch(selectedUser, (newUser) => {
  if (newUser && isCustomDiscount.value) {
    // Load salary info if not cached
    if (!userSalaries.value.has(newUser.id)) {
      userSalaries.value.set(newUser.id, {
        salary: Number(newUser.salary || 0),
        name: newUser.name
      })
    }
    
    // Apply to prestationContributions
    prestationContributions.value.forEach(contrib => {
      contrib.userId = newUser.id
    })
    
    // Apply to prestationDisplayData
    prestationDisplayData.value.forEach(item => {
      item.selectedUser = newUser.id
    })
    
    // Show feedback with salary info
    const salaryInfo = userSalaries.value.get(newUser.id)
    toast.add({
      severity: 'info',
      summary: 'Applied to All',
      detail: `${newUser.name} applied to all prestations (Salary: ${formatCurrency(salaryInfo.salary)})`,
      life: 3000
    })
  }
})

// Watch for changes in selectedDoctor - auto-apply to all prestations
watch(selectedDoctor, (newDoctor) => {
  if (newDoctor && isCustomDiscount.value) {
    // Apply to prestationContributions
    prestationContributions.value.forEach(contrib => {
      contrib.doctorId = newDoctor.id
    })
    
    // Apply to prestationDisplayData
    prestationDisplayData.value.forEach(item => {
      item.selectedDoctor = newDoctor.id
    })
    
    // Show feedback
    toast.add({
      severity: 'info',
      summary: 'Applied to All',
      detail: `Dr. ${newDoctor.name} applied to all prestations`,
      life: 2000
    })
  }
})

// Modified applyUserToAll function (now called automatically)
const applyUserToAll = () => {
  if (selectedUser.value) {
    // Apply to prestationContributions
    prestationContributions.value.forEach(contrib => {
      contrib.userId = selectedUser.value.id
    })
    
    // Apply to prestationDisplayData  
    prestationDisplayData.value.forEach(item => {
      item.selectedUser = selectedUser.value.id
    })
    
    toast.add({
      severity: 'success',
      summary: 'Applied Successfully',
      detail: `${selectedUser.value.name} applied to all prestations`,
      life: 2500
    })
  }
}

// Modified applyDoctorToAll function (now called automatically)
const applyDoctorToAll = () => {
  if (selectedDoctor.value) {
    // Apply to prestationContributions
    prestationContributions.value.forEach(contrib => {
      contrib.doctorId = selectedDoctor.value.id
    })
    
    // Apply to prestationDisplayData
    prestationDisplayData.value.forEach(item => {
      item.selectedDoctor = selectedDoctor.value.id
    })
    
    toast.add({
      severity: 'success',
      summary: 'Applied Successfully', 
      detail: `Dr. ${selectedDoctor.value.name} applied to all prestations`,
      life: 2500
    })
  }
}

// Update the initialization to sync with defaults
const initializePrestationContributions = () => {
  const flat = flattenGroupPrestations(props.group)

  prestationContributions.value = flat.map(item => {
    // robustly obtain the prestation/package object and fallback ids
    const prestationObj = item.prestation || item.package || null

    const prestationId = prestationObj?.id
      ?? item.prestation_id
      ?? item.package_id
      ?? item.id

    const prestationName = prestationObj?.name
      ?? item.name
      ?? item.prestation?.name
      ?? item.package?.name
      ?? 'Unknown'

    const prestationCode = prestationObj?.internal_code
      ?? item.internal_code
      ?? null

    return {
      prestationId,
      prestationName,
      prestationCode,
      originalPrice: Number(item.final_price ?? item.base_price ?? 0),
      userId: selectedUser.value?.id ?? null,
      doctorId: selectedDoctor.value?.id ?? null,
      userAmount: 0,
      doctorAmount: 0,
      totalContribution: 0,
      // keep reference to raw item if needed later
      _raw: item
    }
  })

  console.log('Initializing prestation contributions', flat, prestationContributions.value)
}



// Enhanced doctor selection handler (doctors don't have salary limits in this context)
const onDoctorSelected = (index, doctorId) => {
  if (!doctorId) return
  
  // Update the corresponding prestationContributions entry
  const prestationData = prestationDisplayData.value[index]
  const contributionIndex = prestationContributions.value.findIndex(contrib => 
    contrib.prestationId === prestationData._prestationId || contrib.prestationId === prestationData.id
  )
  
  if (contributionIndex >= 0) {
    prestationContributions.value[contributionIndex].doctorId = doctorId
  }
}

// Compute total original price for the group
const totalOriginal = computed(() => {
  const flatPrestations = flattenGroupPrestations(props.group)
  return flatPrestations.reduce((sum, item) => {
    const price = Number(item.final_price || 0)
    return sum + (item.isAffected ? price : 0)
  }, 0)
})

// Compute total discounted price for the group
const totalDiscounted = computed(() => {
  const flatPrestations = flattenGroupPrestations(props.group)
  return flatPrestations.reduce((sum, item) => {
    const price = Number(item.final_price || 0)
    const discount = item.discount_type === 'percentage' ? (price * (Number(item.discount_value) / 100)) : Math.min(price, Number(item.discount_value))
    const discountedPrice = Math.max(0, price - discount)
    return sum + (item.isAffected ? discountedPrice : 0)
  }, 0)
})

// Compute total savings
const totalSavings = computed(() => {
  return totalOriginal.value - totalDiscounted.value
})

// Compute savings percentage impact
const savingsPercentage = computed(() => {
  if (totalOriginal.value === 0) return 0
  return Math.round((totalSavings.value / totalOriginal.value) * 100)
})

// Computed property to manage dialog visibility
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

function normalizeRemise(raw = {}) {
  const rawType = String(raw.type ?? '').toLowerCase()
  const type = rawType === 'fixed' || rawType === 'amount' ? 'fixed' : 'percentage'
  const value = type === 'percentage' ? Number(raw.percentage ?? raw.value ?? 0) : Number(raw.amount ?? raw.value ?? 0)
  let prestations = []
  if (Array.isArray(raw.prestations) && raw.prestations.length) {
    prestations = raw.prestations.map(p => (typeof p === 'object' ? Number(p.id) : Number(p)))
  } else if (Array.isArray(raw.prestation_ids) && raw.prestation_ids.length) {
    prestations = raw.prestation_ids.map(Number)
  }
  return { ...raw, type, value, prestations }
}

function getApplicableIds(remise) {
  if (!remise) return null
  if (Array.isArray(remise.prestations) && remise.prestations.length > 0) {
    return remise.prestations.map(p => typeof p === 'object' ? Number(p.id) : Number(p))
  }
  if (Array.isArray(remise.prestation_ids) && remise.prestation_ids.length > 0) {
    return remise.prestation_ids.map(Number)
  }
  return null
}

function flattenGroupPrestations(group) {
  if (!group || !Array.isArray(group.items)) return []
  const flattened = []
  group.items.forEach(item => {
    flattened.push({ ...item, _source: 'item', _parent_item_id: item.id })
    const deps = item.dependencies || []
    if (Array.isArray(deps) && deps.length) {
      deps.forEach(dep => {
        const depPrestation = dep?.dependencyPrestation || null
        const normalizedDep = {
          ...dep,
          name: depPrestation?.name ?? 'Dependency',
          internal_code: depPrestation?.internal_code ?? 'N/A',
          final_price: Number(dep?.final_price ?? 0),
          prestation: depPrestation,
          _source: 'dependency',
          _parent_item_id: item.id
        }
        flattened.push(normalizedDep)
      })
    }
  })
  return flattened
}

const prestationDisplayData = computed(() => {
  const flat = flattenGroupPrestations(props.group)
  if (!flat.length) return []
  const remise = selectedRemise.value
  const applicableIds = getApplicableIds(remise)
  
  return flat.map((item, index) => {
    const prestationObj = item.prestation || item.package || null
    const prestationId = prestationObj?.id ? Number(prestationObj.id) : null
    const prestationName = prestationObj?.name ?? item.name ?? 'Unknown'
    const prestationCode = prestationObj?.internal_code ?? 'N/A'
    let isAffected = false
    
    if (remise) {
      isAffected = applicableIds === null || (prestationId !== null && applicableIds.includes(prestationId))
    }
    
    const originalPrice = Number(item.final_price ?? 0)
    const fichenavetteMax = Number(item.fichenavette_max ?? originalPrice) // Add fichenavette_max support
    let discountedPrice = originalPrice
    let discount = 0
    
    if (isAffected && remise) {
      if (remise.type === 'percentage') {
        discount = originalPrice * (Number(remise.value) / 100)
      } else {
        discount = Math.min(originalPrice, Number(remise.value))
      }
      discountedPrice = Math.max(0, originalPrice - discount)
    }
    
    // Find corresponding contribution data
    const contributionData = prestationContributions.value.find(contrib => 
      contrib.prestationId === prestationId || contrib.prestationId === item.id
    )
    
    return {
      ...item,
      prestationName,
      prestationCode,
      originalPrice,
      fichenavetteMax,
      discountedPrice,
      discount,
      isAffected,
      _prestationId: prestationId,
      // Use defaults if available, otherwise use contribution data
      selectedUser: contributionData?.userId || selectedUser.value?.id || null,
      selectedDoctor: contributionData?.doctorId || selectedDoctor.value?.id || null,
      userAmount: contributionData?.userAmount || 0,
      doctorAmount: contributionData?.doctorAmount || 0,
    }
  })
})

// Calculate maximum amount for user (can't exceed fichenavette_max or original price)
const getUserMaxAmount = (data, index) => {
  const originalPrice = Number(data.originalPrice || 0)
  const fichenavetteMax = Number(data.fichenavetteMax || originalPrice)
  const doctorAmount = Number(prestationDisplayData.value[index]?.doctorAmount || 0)
  const userId = prestationDisplayData.value[index]?.selectedUser
  
  // Base calculation from price and fichenavette_max
  const maxFromPrice = Math.min(fichenavetteMax, originalPrice)
  const maxFromRemaining = Math.max(0, maxFromPrice - doctorAmount)
  
  // Check user salary limit
  if (userId && userSalaries.value.has(userId)) {
    const userSalary = userSalaries.value.get(userId).salary
    
    // Calculate total user contributions across ALL prestations
    const totalUserContributions = prestationDisplayData.value.reduce((sum, item, idx) => {
      if (item.selectedUser === userId && idx !== index) {
        return sum + Number(item.userAmount || 0)
      }
      return sum
    }, 0)
    
    const availableSalary = Math.max(0, userSalary - totalUserContributions)
    const maxFromSalary = Math.min(maxFromRemaining, availableSalary)
    
    return maxFromSalary
  }
  
  return maxFromRemaining
}

// Calculate maximum amount for doctor (can't exceed remaining after user contribution)
const getDoctorMaxAmount = (data, index) => {
  const originalPrice = Number(data.originalPrice || 0)
  const fichenavetteMax = Number(data.fichenavetteMax || originalPrice)
  const userAmount = Number(prestationDisplayData.value[index]?.userAmount || 0)
  
  // Doctor amount can't exceed original price minus user contribution
  // but total can't exceed fichenavette_max
  const maxFromPrice = originalPrice - userAmount
  const maxFromFichenavette = fichenavetteMax - userAmount
  
  return Math.max(0, Math.min(maxFromPrice, maxFromFichenavette))
}

// Update user amount with validation
const updateUserAmount = (index, value) => {
  const amount = Number(value || 0)
  const userId = prestationDisplayData.value[index]?.selectedUser
  const maxAmount = getUserMaxAmount(prestationDisplayData.value[index], index)
  
  if (amount > maxAmount) {
    let errorMessage = `User amount cannot exceed ${formatCurrency(maxAmount)}`
    
    // Add specific salary warning if salary is the limiting factor
    if (userId && userSalaries.value.has(userId)) {
      const userInfo = userSalaries.value.get(userId)
      const totalUserContributions = prestationDisplayData.value.reduce((sum, item, idx) => {
        if (item.selectedUser === userId && idx !== index) {
          return sum + Number(item.userAmount || 0)
        }
        return sum
      }, 0)
      
      const availableSalary = Math.max(0, userInfo.salary - totalUserContributions)
      
      if (amount > availableSalary) {
        errorMessage = `${userInfo.name} cannot afford ${formatCurrency(amount)}. Available salary: ${formatCurrency(availableSalary)} (Total salary: ${formatCurrency(userInfo.salary)})`
      }
    }
    
    toast.add({
      severity: 'warn',
      summary: 'Invalid Amount',
      detail: errorMessage,
      life: 4000
    })
    
    // Reset to max allowed
    prestationDisplayData.value[index].userAmount = maxAmount
    return
  }
  
  // Update corresponding contribution
  const prestationData = prestationDisplayData.value[index]
  const contributionIndex = prestationContributions.value.findIndex(contrib => 
    contrib.prestationId === prestationData._prestationId || contrib.prestationId === prestationData.id
  )
  
  if (contributionIndex >= 0) {
    prestationContributions.value[contributionIndex].userAmount = amount
    updateContributionTotal(contributionIndex)
  }
}

// Update doctor amount with validation
const updateDoctorAmount = (index, value) => {
  const amount = Number(value || 0)
  const maxAmount = getDoctorMaxAmount(prestationDisplayData.value[index], index)
  
  if (amount > maxAmount) {
    toast.add({
      severity: 'warn',
      summary: 'Invalid Amount',
      detail: `Doctor amount cannot exceed ${formatCurrency(maxAmount)}`,
      life: 3000
    })
    // Reset to max allowed
    prestationDisplayData.value[index].doctorAmount = maxAmount
    return
  }
  
  // Update corresponding contribution
  const prestationData = prestationDisplayData.value[index]
  const contributionIndex = prestationContributions.value.findIndex(contrib => 
    contrib.prestationId === prestationData._prestationId || contrib.prestationId === prestationData.id
  )
  
  if (contributionIndex >= 0) {
    prestationContributions.value[contributionIndex].doctorAmount = amount
    updateContributionTotal(contributionIndex)
  }
}

// Get total contribution for a prestation row
const getTotalContribution = (index) => {
  const prestationData = prestationDisplayData.value[index]
  const userAmount = Number(prestationData.userAmount || 0)
  const doctorAmount = Number(prestationData.doctorAmount || 0)
  return userAmount + doctorAmount
}

// Data loading functions
const getAllUsers = async () => {
  try {
    const res = await userService.getAll()
    users.value = res.success ? res.data : []
  } catch (error) {
    console.error('Error fetching users:', error)
    users.value = []
  }
}

const getRemiseUser = async (userId) => {
  if (!userId) return
  loading.value = true
  try {
    const response = await remiseService.getRemiseUser(userId)
    if (response.success) {
      availableRemises.value = (response.data || []).map(normalizeRemise)
    } else {
      availableRemises.value = []
      toast.add({ severity: 'warn', summary: 'Warning', detail: response.message || 'No discounts found for this user', life: 2500 })
    }
  } catch (error) {
    console.error('Error fetching user remises:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load user discounts', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadRemises = async () => {
  loading.value = true
  try {
    const response = await remiseService.getAll({ group_id: props.group?.id })
    availableRemises.value = (response.data || []).map(normalizeRemise)
  } catch (error) {
    console.error('Error loading remises:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load available discounts', life: 3000 })
  } finally {
    loading.value = false
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'DZD' }).format(amount || 0)
}

// Apply the selected discount
const applyRemise = async () => {
  if (!selectedRemise.value && !isCustomDiscount.value) {
    toast.add({ severity: 'warn', summary: 'Warning', detail: 'Please select or configure a discount', life: 3000 })
    return
  }
  loading.value = true
  try {
    const affected = prestationDisplayData.value
      .filter(item => item.isAffected)
      .map(item => ({
        fiche_item_id: item.id,
        prestation_id: item._prestationId || null,
        parent_item_id: item._parent_item_id || null,
        original_price: Number(item.originalPrice) || 0,
        discounted_price: Number(item.discountedPrice) || 0,
        discount_amount: Number(item.discount) || 0
      }))

    const remiseData = {
      remise_id: selectedRemise.value?.id ?? null,
      is_custom: !!isCustomDiscount.value,
      affected_items: affected
    }
    
    emit('apply-remise', remiseData)
    dialogVisible.value = false
    toast.add({ severity: 'success', summary: 'Success', detail: 'Discount applied successfully', life: 3000 })
    resetForm()

  } catch (error) {
    console.error('Error applying remise:', error)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to apply discount', life: 3000 })
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  selectedRemise.value = null
  isCustomDiscount.value = false
  selectedUser.value = null
  selectedDoctor.value = null
  prestationContributions.value = []
  userSalaries.value.clear() // Clear salary cache
  activeTab.value = 0
}

// Lifecycle Hooks
watch(dialogVisible, (visible) => {
  if (visible) {
    const userId = props.group?.user?.id
    if (userId) {
      getRemiseUser(userId)
    } else {
      loadRemises()
    }
  } else {
    resetForm()
  }
})

onMounted(() => {
  getAllUsers()
  if (props.visible) {
    const userId = props.group?.user?.id
    if (userId) {
      getRemiseUser(userId)
    } else {
      loadRemises()
    }
  }
})

// Check if user is near salary limit
const isNearSalaryLimit = (index) => {
  const userId = prestationDisplayData.value[index]?.selectedUser
  if (!userId || !userSalaries.value.has(userId)) return false
  
  const remaining = getUserRemainingSalary(userId)
  const userInfo = userSalaries.value.get(userId)
  const salaryUsagePercentage = ((userInfo.salary - remaining) / userInfo.salary) * 100
  
  return salaryUsagePercentage > 80 // Warning if more than 80% of salary is used
}

// Get user remaining salary
const getUserRemainingSalary = (userId) => {
  if (!userId || !userSalaries.value.has(userId)) return 0
  
  const userInfo = userSalaries.value.get(userId)
  const totalUserContributions = prestationDisplayData.value.reduce((sum, item) => {
    if (item.selectedUser === userId) {
      return sum + Number(item.userAmount || 0)
    }
    return sum
  }, 0)
  
  return Math.max(0, userInfo.salary - totalUserContributions)
}

// Get salary status for visual indicators
const getSalaryStatus = (userId) => {
  if (!userSalaries.value.has(userId)) return 'unknown'
  
  const remaining = getUserRemainingSalary(userId)
  const userInfo = userSalaries.value.get(userId)
  
  if (userInfo.salary === 0) return 'unknown'
  
  const percentage = (remaining / userInfo.salary) * 100
  
  if (percentage > 50) return 'good'
  if (percentage > 20) return 'warning'
  return 'critical'
}

// Get CSS class for salary status
const getSalaryStatusClass = (userId) => {
  const status = getSalaryStatus(userId)
  return {
    'salary-good': status === 'good',
    'salary-warning': status === 'warning', 
    'salary-critical': status === 'critical'
  }
}

// Get discount impact status
const discountImpact = computed(() => {
  if (savingsPercentage.value >= 20) return 'high'
  if (savingsPercentage.value >= 10) return 'medium'
  if (savingsPercentage.value > 0) return 'low'
  return 'none'
})
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    header="Apply Discount (Remise)"
    :style="{ width: '1400px', maxHeight: '90vh' }"
    :modal="true"
    :draggable="false"
    class="remise-modal"
  >
    <div class="remise-container">
      <div class="summary-header">
        <div class="summary-item">
          <span class="summary-label">Original Total</span>
          <span class="summary-value">{{ formatCurrency(totalOriginal) }}</span>
        </div>
        <div class="summary-item savings">
          <span class="summary-label">You Save</span>
          <span class="summary-value">{{ formatCurrency(totalSavings) }}</span>
          <div class="savings-percentage" :class="discountImpact">
            {{ savingsPercentage }}% OFF
          </div>
        </div>
        <div class="summary-item total">
          <span class="summary-label">New Total</span>
          <span class="summary-value">{{ formatCurrency(totalDiscounted) }}</span>
        </div>
        <div v-if="isCustomDiscount" class="summary-item contribution">
          <span class="summary-label">Total Contributions</span>
          <span class="summary-value">{{ formatCurrency(totalContributions) }}</span>
        </div>
      </div>

      <ProgressBar v-if="loading" mode="indeterminate" class="progress-bar" />

      <div class="main-content">
        <div class="config-panel">
          <TabView v-model:activeIndex="activeTab">
            <TabPanel header="Select Discount">
              <div class="tab-content">
                <p class="tab-description">Choose a preset discount from the list below. The discount will be automatically calculated and applied to all eligible services.</p>
                <div class="field">
                  <label for="remise-dropdown" class="field-label">Available Discounts</label>
                  <Dropdown
                    id="remise-dropdown"
                    v-model="selectedRemise"
                    :options="availableRemises"
                    option-label="name"
                    placeholder="Select a discount"
                    :loading="loading"
                    class="w-full"
                    filter
                  >
                    <template #option="{ option }">
                      <div class="remise-option">
                        <div class="remise-info">
                          <div class="remise-name">{{ option.name }}</div>
                          <small>{{ option.description || 'No description' }}</small>
                        </div>
                        <div class="remise-badge" :class="option.type">
                          <span v-if="option.type === 'percentage'">{{ option.value }}%</span>
                          <span v-else>{{ formatCurrency(option.value) }}</span>
                        </div>
                      </div>
                    </template>
                  </Dropdown>
                </div>

                <Divider align="center" type="dashed"><b>AND</b></Divider>

                <div class="field-checkbox">
                  <Checkbox id="custom-discount-toggle" v-model="isCustomDiscount" binary />
                  <label for="custom-discount-toggle">Create Custom Remise Request</label>
                </div>
              </div>
            </TabPanel>
            
            <TabPanel header="Custom Remise Request">
              <div class="tab-content">
               
                
                <!-- Enhanced Global User/Doctor Selection -->
                <div class="global-selection">
                  <div class="selection-header">
                    <h5 class="selection-title">
                      <i class="pi pi-users"></i>
                      Default Selection (Auto-applies to all prestations)
                    </h5>
                  </div>
                  
                  <div class="field-group">
                    <div class="field">
                      <label>Default User</label>
                      <div class="input-group">
                        <Dropdown
                          v-model="selectedUser"
                          :options="users"
                          option-label="name"
                          placeholder="Select default user (applies to all)"
                          filter
                          class="flex-grow-1"
                          clearable
                        >
                          <template #value="{ value, placeholder }">
                            <div v-if="value" class="selected-user ">
                              <i class="pi pi-user"></i>
                              <span>{{ value.name }}</span>
                              
                            </div>
                            <span v-else>{{ placeholder }}</span>
                          </template>
                          <template #option="{ option }">
                            <div class="user-option">
                              <div class="user-info">
                                <span class="user-name">{{ option.name }}</span>
                                <div class="user-details">
                                  <small v-if="userSalaries.has(option.id)" class="user-remaining">
                                  </small>
                                </div>
                              </div>
                              <div class="salary-indicator" :class="getSalaryStatus(option.id)">
                                <i class="pi pi-circle-fill"></i>
                              </div>
                            </div>
                          </template>
                        </Dropdown>
                        <Button
                          label="Re-apply to All"
                          size="small"
                          severity="secondary"
                          @click="applyUserToAll"
                          :disabled="!selectedUser"
                          v-tooltip="'Force re-apply this user to all prestations'"
                          
                        />
                      </div>
                      <small class="field-hint" v-if="selectedUser">
                        <i class="pi pi-info-circle"></i>
                        {{ selectedUser.name }} will be automatically applied to all prestations
                      </small>
                    </div>
                    
                    <div class="field">
                      <label>Default Doctor</label>
                      <div class="input-group">
                        <Dropdown
                          v-model="selectedDoctor"
                          :options="doctors"
                          option-label="name"
                          placeholder="Select default doctor (applies to all)"
                          filter
                          class="flex-grow-1"
                          clearable
                        >
                          <template #value="{ value, placeholder }">
                            <div v-if="value" class="selected-doctor">
                              <i class="pi pi-user-plus"></i>
                              <span>{{ value.name }}</span>
                              <small>(Applied to all)</small>
                            </div>
                            <span v-else>{{ placeholder }}</span>
                          </template>
                        </Dropdown>
                        <Button
                          label="Re-apply to All"
                          size="small"
                          severity="secondary"
                          @click="applyDoctorToAll"
                          :disabled="!selectedDoctor"
                          v-tooltip="'Force re-apply this doctor to all prestations'"
                        />
                      </div>
                      <small class="field-hint" v-if="selectedDoctor">
                        <i class="pi pi-info-circle"></i>
                        Dr. {{ selectedDoctor.name }} will be automatically applied to all prestations
                      </small>
                    </div>
                  </div>
                </div>

                <Divider />

                <!-- Summary and Notify Button -->
                <div class="contribution-summary">
                  <div class="summary-grid">
                    <div class="summary-item">
                      <span class="label">Total User Contributions:</span>
                      <span class="value">{{ formatCurrency(totalUserContributions) }}</span>
                    </div>
                    <div class="summary-item">
                      <span class="label">Total Doctor Contributions:</span>
                      <span class="value">{{ formatCurrency(totalDoctorContributions) }}</span>
                    </div>
                    <div class="summary-item total-row">
                      <span class="label">Total Contributions:</span>
                      <span class="value">{{ formatCurrency(totalContributions) }}</span>
                    </div>
                  </div>
                  
                  <Button
                    label="Create Remise Request & Notify All"
                    icon="pi pi-bell"
                    class="p-button-success notify-button"
                    :loading="notifyLoading"
                    :disabled="!hasValidContributions"
                    @click="notifyAllContributions"
                  />
                </div>
              </div>
            </TabPanel>
          </TabView>
        </div>

        <!-- Enhanced pricing panel with auto-selected users/doctors -->
        <div class="pricing-panel">
          <Card class="pricing-card">
            <template #title>
              <div class="pricing-title-section">
                <span>Services & Pricing Breakdown</span>
                <div v-if="isCustomDiscount && (selectedUser || selectedDoctor)" class="auto-applied-badge">
                  <Tag severity="info" value="Auto-Applied Defaults" />
                </div>
              </div>
            </template>
            
            <template #content>
              <DataTable
                :value="prestationDisplayData"
                class="pricing-table"
                responsiveLayout="scroll"
                :paginator="prestationDisplayData.length > 5"
                :rows="5"
                scrollable
                scrollHeight="400px"
              >
                <Column field="prestationName" header="Service" :style="{ minWidth: '200px' }">
                  <template #body="{ data }">
                    <div class="service-cell">
                      <span>{{ data.prestationName }}</span>
                      <small class="service-code">{{ data.prestationCode }}</small>
                    </div>
                  </template>
                </Column>
                <Column field="originalPrice" header="Original" :style="{ minWidth: '120px' }">
                  <template #body="{ data }">
                    <span class="price original">{{ formatCurrency(data.originalPrice) }}</span>
                  </template>
                </Column>
                <Column field="discount" header="Discount" :style="{ minWidth: '120px' }">
                  <template #body="{ data }">
                    <span v-if="data.discount > 0" class="price discount">-{{ formatCurrency(data.discount) }}</span>
                    <span v-else>-</span>
                  </template>
                </Column>
                <Column field="discountedPrice" header="Final" :style="{ minWidth: '120px' }">
                  <template #body="{ data }">
                    <span class="price final" :class="{ 'discounted': data.isAffected }">
                      {{ formatCurrency(data.discountedPrice) }}
                    </span>
                  </template>
                </Column>
                
                <!-- Enhanced User Column with auto-selection indicator -->
                <Column header="User" :style="{ minWidth: '180px' }" v-if="isCustomDiscount">
                  <template #body="{ data, index }">
                    <div class="user-selection-wrapper">
                      <Dropdown
                        v-model="prestationDisplayData[index].selectedUser"
                        :options="users"
                        option-label="name"
                        option-value="id"
                        placeholder="Select user"
                        filter
                        size="small"
                        class="w-full"
                      >
                        <template #value="{ value, placeholder }">
                          <div v-if="value" class="selected-value">
                            <span>{{ users.find(u => u.id === value)?.name || 'Unknown' }}</span>
                            <i v-if="value === selectedUser?.id" class="pi pi-check auto-applied-icon" title="Auto-applied from default"></i>
                          </div>
                          <span v-else>{{ placeholder }}</span>
                        </template>
                      </Dropdown>
                    </div>
                  </template>
                </Column>
                
                <!-- New User Amount Column -->
                <Column header="User Amount" :style="{ minWidth: '160px' }" v-if="isCustomDiscount">
                  <template #body="{ data, index }">
                    <div class="amount-input-wrapper">
                      <InputNumber
                        v-model="prestationDisplayData[index].userAmount"
                        mode="currency"
                        currency="DZD"
                        locale="fr-FR"
                        :min="0"
                        :max="getUserMaxAmount(data, index)"
                        size="small"
                        :disabled="!prestationDisplayData[index].selectedUser"
                        :placeholder="!prestationDisplayData[index].selectedUser ? 'Select user first' : '0.00'"
                        @update:modelValue="updateUserAmount(index, $event)"
                        class="w-full"
                        :class="{ 'salary-warning': isNearSalaryLimit(index) }"
                      />
                      <small v-if="prestationDisplayData[index].selectedUser" class="amount-details">
                        <span class="max-hint">Max: {{ formatCurrency(getUserMaxAmount(data, index)) }}</span>
                      </small>
                    </div>
                  </template>
                </Column>
                
                <!-- Enhanced Doctor Column with auto-selection indicator -->
                <Column header="Doctor" :style="{ minWidth: '180px' }" v-if="isCustomDiscount">
                  <template #body="{ data, index }">
                    <div class="doctor-selection-wrapper">
                      <Dropdown
                        v-model="prestationDisplayData[index].selectedDoctor"
                        :options="doctors"
                        option-label="name"
                        option-value="id"
                        placeholder="Select doctor"
                        filter
                        size="small"
                        class="w-full"
                        @change="onDoctorSelected(index, $event.value)"
                      >
                        <template #value="{ value, placeholder }">
                          <div v-if="value" class="selected-value">
                            <span>{{ doctors.find(d => d.id === value)?.name || 'Unknown' }}</span>
                            <i v-if="value === selectedDoctor?.id" class="pi pi-check auto-applied-icon" title="Auto-applied from default"></i>
                          </div>
                          <span v-else>{{ placeholder }}</span>
                        </template>
                      </Dropdown>
                    </div>
                  </template>
                </Column>
                
                <!-- New Doctor Amount Column -->
                <Column header="Doctor Amount" :style="{ minWidth: '140px' }" v-if="isCustomDiscount">
                  <template #body="{ data, index }">
                    <InputNumber
                      v-model="prestationDisplayData[index].doctorAmount"
                      mode="currency"
                      currency="DZD"
                      locale="fr-FR"
                      :min="0"
                      :max="getDoctorMaxAmount(data, index)"
                      size="small"
                      :disabled="!prestationDisplayData[index].selectedDoctor"
                      :placeholder="!prestationDisplayData[index].selectedDoctor ? 'Select doctor first' : '0.00'"
                      @update:modelValue="updateDoctorAmount(index, $event)"
                      class="w-full"
                    />
                    <small v-if="prestationDisplayData[index].selectedDoctor" class="max-amount-hint">
                      Max: {{ formatCurrency(getDoctorMaxAmount(data, index)) }}
                    </small>
                  </template>
                </Column>
                
                <!-- New Total Contribution Column -->
                <Column header="Total Contribution" :style="{ minWidth: '140px' }" v-if="isCustomDiscount">
                  <template #body="{ data, index }">
                    <span class="price contribution" :class="{ 'has-contribution': getTotalContribution(index) > 0 }">
                      {{ formatCurrency(getTotalContribution(index)) }}
                    </span>
                  </template>
                </Column>
                
                <Column header="Status" bodyClass="text-center" :style="{ minWidth: '100px' }">
                  <template #body="{ data, index }">
                    <Tag v-if="data.isAffected && !isCustomDiscount" severity="success" value="Discounted" />
                    <Tag 
                      v-else-if="isCustomDiscount && getTotalContribution(index) > 0" 
                      severity="info" 
                      value="Contributed" 
                    />
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </div>
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="dialogVisible = false" />
      <Button
        v-if="!isCustomDiscount"
        label="Apply Discount"
        icon="pi pi-check"
        class="p-button-success"
        :disabled="!selectedRemise || loading"
        :loading="loading"
        @click="applyRemise"
      />
    </template>
  </Dialog>
</template>

<style scoped>
/* Basic Layout & Theme */
.remise-container {
  padding: 0.5rem;
}

.progress-bar {
  margin: 1rem 0;
  height: 4px;
}

.main-content {
  display: grid;
  grid-template-columns: 500px 1fr;
  gap: 1.5rem;
  margin-top: 1rem;
}

/* Summary Header */
.summary-header {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 12px;
  border: 1px solid #dee2e6;
}

.summary-item {
  display: flex;
  flex-direction: column;
  padding: 1rem;
  border-radius: 8px;
  background-color: #ffffff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.summary-item.savings {
  background-color: #e6f7f1;
}

.summary-item.total {
  background-color: #e7f1ff;
}

.summary-item.contribution {
  background-color: #fff3cd;
}

.summary-label {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #212529;
}

/* Salary-related styles */
.user-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding: 0.5rem 0;
}

.user-info {
  flex-grow: 1;
}

.user-name {
  font-weight: 500;
  display: block;
}

.user-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-top: 0.25rem;
}

.user-salary {
  color: #6c757d;
  font-size: 0.8rem;
}

.user-remaining {
  color: #28a745;
  font-size: 0.75rem;
  font-weight: 500;
}

.salary-indicator {
  margin-left: 0.5rem;
}

.salary-indicator.good .pi-circle-fill {
  color: #28a745;
}

.salary-indicator.warning .pi-circle-fill {
  color: #ffc107;
}

.salary-indicator.critical .pi-circle-fill {
  color: #dc3545;
}

.salary-indicator.unknown .pi-circle-fill {
  color: #6c757d;
}

/* Amount input wrapper */
.amount-input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.amount-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.max-hint {
  color: #6c757d;
  font-size: 0.75rem;
}

.salary-remaining {
  font-size: 0.75rem;
  font-weight: 500;
}

.salary-remaining.salary-good {
  color: #28a745;
}

.salary-remaining.salary-warning {
  color: #ffc107;
}

.salary-remaining.salary-critical {
  color: #dc3545;
}

/* Salary warning input styling */
.salary-warning :deep(.p-inputnumber-input) {
  border-color: #ffc107 !important;
  box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
}

/* Salary info in selected user display */
.selected-user .salary-info {
  color: #28a745;
  font-weight: 500;
  width: 100%;
  margin-left: 0.5rem;
}

/* Enhanced field hint with salary info */
.field-hint .salary-info {
  color: #28a745;
  font-weight: 500;
  margin-left: 0.5rem;
}

/* Additional styles for the new columns */
.max-amount-hint {
  color: #6c757d;
  font-size: 0.75rem;
  font-style: italic;
}

.price.contribution.has-contribution {
  color: #198754;
  font-weight: 600;
}

.contributions-table :deep(.p-inputnumber-input) {
  font-size: 0.875rem;
}

.contributions-table :deep(.p-dropdown) {
  font-size: 0.875rem;
}

/* Custom styling for disabled inputs */
.contributions-table :deep(.p-inputnumber.p-disabled) {
  opacity: 0.6;
}

.contributions-table :deep(.p-inputnumber.p-disabled .p-inputnumber-input) {
  background-color: #f8f9fa;
  color: #6c757d;
}

/* Responsive table adjustments */
@media (max-width: 1400px) {
  .pricing-table :deep(.p-datatable-wrapper) {
    overflow-x: auto;
  }
}

.savings-percentage {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  display: inline-block;
  margin-top: 0.5rem;
  align-self: flex-start;
}

.savings-percentage.high { background: #d1fae5; color: #065f46; }
.savings-percentage.medium { background: #fef3c7; color: #92400e; }
.savings-percentage.low { background: #dbeafe; color: #1e40af; }


/* TabView & Content */
.config-panel :deep(.p-tabview-nav) {
  background: transparent;
  border-bottom: 1px solid #dee2e6;
}

.tab-content {
  padding: 1.5rem 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.tab-description {
  font-size: 0.6rem;
  color: #6c757d;
  margin: 0 0 0.5rem;
  line-height: 1;
}

.field-label,
.field label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #495057;
}

.field-checkbox {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.field-checkbox label {
  margin-bottom: 0;
  font-weight: 500;
  cursor: pointer;
}

/* Custom Request Styles */
.global-selection {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid #dee2e6;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.selection-header {
  margin-bottom: 1rem;
}

.selection-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #495057;
}

.selected-user,
.selected-doctor {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.selected-user small,
.selected-doctor small {
  color: #6c757d;
  font-style: italic;
}

.field-hint {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #6c757d;
  font-size: 0.8rem;
  margin-top: 0.5rem;
  font-style: italic;
}

.field-hint .pi-info-circle {
  color: #17a2b8;
}

/* Auto-applied indicators */
.auto-applied-badge {
  margin-left: auto;
}

.pricing-title-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.user-selection-wrapper,
.doctor-selection-wrapper {
  position: relative;
}

.selected-value {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.auto-applied-icon {
  color: #28a745;
  font-size: 0.8rem;
  margin-left: 0.5rem;
}

/* Enhanced input group styling */
.input-group {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
}

.input-group .flex-grow-1 {
  flex-grow: 1;
}

/* Toast-like visual feedback */
.field:has(.field-hint) {
  position: relative;
}

.field-hint {
  animation: slideInFromTop 0.3s ease-out;
}

@keyframes slideInFromTop {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .main-content {
    grid-template-columns: 1fr;
  }
  
  .field-group {
    grid-template-columns: 1fr;
  }
  
  .pricing-title-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
