<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'

// Components
import PaymentHeader from '@/Components/Caisse/PaymentHeader.vue'
import PrestationsSummary from '@/Components/Caisse/PrestationsSummary.vue'
import TransactionsOverview from '@/Components/Caisse/TransactionsOverview.vue'
import GlobalPayment from '@/Components/Caisse/GlobalPayment.vue'
import PrestationCard from '@/Components/Caisse/PrestationCard.vue'
import PaymentModals from '@/Components/Caisse/PaymentModals.vue'
import PaymentApprovalModal from '@/Components/PaymentApprovalModal.vue'

// PrimeVue Components
import ProgressSpinner from 'primevue/progressspinner'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

// Composables
import { useCurrencyFormatter } from '@/composables/useCurrencyFormatter'
import { usePaymentHelpers } from '@/composables/usePaymentHelpers'
import { useTransactionHelpers } from '@/composables/useTransactionHelpers'

const route = useRoute()
const router = useRouter()
const toast = useToast()

// Composables
const { formatCurrency } = useCurrencyFormatter()
const { getItemFinalPrice, getItemPaidAmount, getItemRemainingAmount, mapPaymentMethod } = usePaymentHelpers()
const { getTransactionTypeText, getTransactionTypeClass } = useTransactionHelpers()

const ficheId = ref(route.query?.fiche_navette_id)
const caisseSessionId = ref(route.query?.caisse_session_id ?? null)
const cashier_id = ref(route.query?.cashier_id)
const patientId = ref(route.query?.patient_id ?? null)

const items = ref([])
const loading = ref(false)
const fichePatientName = ref(null)

const globalPayment = reactive({
  amount: null,
  method: 'cash'
})

// Overpayment handling
const showOverpaymentModal = ref(false)
const processingOverpayment = ref(false)
const overpaymentData = reactive({
  required: 0,
  paid: 0,
  excess: 0,
  itemIndex: null,
  isGlobal: false
})

// Refund handling
const showRefundModal = ref(false)
const processingRefund = ref(false)
const refundData = reactive({
  transaction: null,
  item: null,
  amount: 0,
  notes: '',
  errors: {},
  fixedAmount: false,
  authorization: null
})

// Update handling
const showUpdateModal = ref(false)
const processingUpdate = ref(false)
const updateData = reactive({
  transaction: null,
  maxAmount: 0,
  amount: 0,
  notes: '',
  errors: {}
})

// Approval handling for card/cheque payments
const showApprovalModal = ref(false)
const approvalData = reactive({
  fiche_navette_item_id: null,
  item_dependency_id: null,
  patient_id: patientId?.value ?? null,
  amount: 0,
  method: '',
  itemName: '',
  itemIndex: null
})

// New: Refund Authorization State
const refundAuthorization = ref(null);
const refundAuthMap = ref({});
const RefoundAmount = ref([]);
// Permission: whether the authenticated user can perform refunds
const userCanRefund = ref(false)

// Reactive set to track refunds for transactions
const refundExistsForTransaction = reactive(new Set())

// Handle refund authorization actions
const handleRefundAuthorization = async (authId, action) => {
  try {
    const endpoint = `/api/refund-authorizations/${authId}/${action}`
    const response = await axios.post(endpoint)
    
    if (response?.data?.success) {
      // Update local state
      const updatedAuth = response.data.data
      if (updatedAuth?.fiche_navette_item_id) {
        refundAuthMap.value[updatedAuth.fiche_navette_item_id] = updatedAuth
      }
      
      // Show success message
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Refund request ${action}d successfully`,
        life: 3000
      })
      
      // Reload data to refresh the UI
      await loadItems()
    }
  } catch (error) {
    console.error(`Error ${action}ing refund authorization:`, error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: `Failed to ${action} refund request`,
      life: 3000
    })
  }
}

// Approve a refund request
const approveRefundRequest = (authId) => handleRefundAuthorization(authId, 'approve')

// Reject a refund request
const rejectRefundRequest = (authId) => handleRefundAuthorization(authId, 'reject')

// Load pending refund authorizations for the current fiche
const loadPendingAuthorizations = async () => {
  try {
    const [authResponse, refundableResponse] = await Promise.all([
      // Get existing authorization requests
      axios.get('/api/refund-authorizations', {
        params: {
          fiche_navette_id: ficheId.value
        }
      }),
      // Get refundable transactions
      axios.get('/api/financial-transactions/refundable', {
        params: {
          fiche_navette_id: ficheId.value
        }
      })
    ])
    
    // Process authorizations
    if (authResponse?.data?.data) {
      const authorizations = authResponse.data.data
      refundAuthMap.value = authorizations.reduce((acc, auth) => {
        if (auth.fiche_navette_item_id) {
          acc[auth.fiche_navette_item_id] = {
            ...auth,
            refundStatus: auth.status || 'pending'
          }
        }
        return acc
      }, {})
    }

    // Process refundable transactions
    if (refundableResponse?.data?.data) {
      const refundableItems = refundableResponse.data.data
      refundableItems.forEach(item => {
        // If item doesn't have an authorization yet, mark it as refundable
        if (!refundAuthMap.value[item.id]) {
          refundAuthMap.value[item.id] = {
            fiche_navette_item_id: item.id,
            refundStatus: 'refundable',
            amount: item.amount
          }
        }
      })
    }

    console.log('Updated refund authorization map:', refundAuthMap.value)
  } catch (error) {
    console.error('Error loading refund data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load refund information',
      life: 3000
    })
  }
}

// Check user's refund permissions
const checkUserRefundPermission = async () => {
  try {
    const response = await axios.get('/api/user-refund-permissions/check')
    if (response?.data?.success) {
      userCanRefund.value = response.data.data.can_refund
      console.log('User refund permission:', userCanRefund.value)
    } else {
      userCanRefund.value = false
    }
  } catch (error) {
    console.error('Error checking refund permissions:', error)
    userCanRefund.value = false
    toast.add({
      severity: 'error',
      summary: 'Permission Error',
      detail: 'Unable to verify refund permissions',
      life: 3000
    })
  }
}

// Summary items for the top table
const summaryItems = computed(() => {
  if (!items.value) return [];
  console.log('Summary Items:', items.value);
  return items.value.map(item => ({
    id: item.id,
    display_name: item.display_name ?? item.prestation?.name ?? item.custom_name ?? 'Prestation',
    final_price: getItemFinalPrice(item),
    paid_amount: getItemPaidAmount(item),
    remaining: itemRemaining(item),
    payment_status: getPaymentStatus(item),
    min_versement_amount: Number(item.min_versement_amount || 0),
    is_min_versement_paid: isMinVersementPaid(item),
    default_payment_type: item.default_payment_type
  }));
});

const itemRemaining = (it) => {
  if (!it) return 0
  // Use the helper function that properly handles both dependencies and regular items
  return getItemRemainingAmount(it)
}

// Check if minimum versement is paid (visa granted)
const isMinVersementPaid = (item) => {
  const paidAmount = getItemPaidAmount(item)
  const minVersement = Number(item.min_versement_amount || 0)
  
  if (minVersement <= 0) return false
  return paidAmount >= minVersement
}

// Get payment status based on minimum versement
const getPaymentStatus = (item) => {
  const finalPrice = getItemFinalPrice(item)
  const paidAmount = getItemPaidAmount(item)
  const remaining = itemRemaining(item)
  const minVersement = Number(item.prestation?.min_versement_amount || 0)
  const defaultPaymentType = item.default_payment_type || item.prestation?.default_payment_type

  if (remaining <= 0) {
    return { status: 'paid', color: 'green', text: 'Payé' }
  }
  
  // Special handling for pre_payment type: grant visa when minimum payment is made
  if (defaultPaymentType === 'Pré-paiement' && paidAmount > 0) {
    return { status: 'visa_granted', color: 'green', text: 'Visa accordé' }
  }
  
  if (minVersement > 0 && paidAmount >= minVersement) {
    return { status: 'visa_granted', color: 'green', text: 'Visa accordé' }
  }
  
  if (paidAmount > 0) {
    return { status: 'partial', color: 'orange', text: 'Partiel' }
  }
  
  return { status: 'unpaid', color: 'red', text: 'Impayé' }
}

const allTransactions = ref([])
const transactionsSearchQuery = ref('')
const selectedTransactionType = ref(null)
const selectedTransactionDateRange = ref(null)

const transactionTypeOptions = computed(() => {
  const allTypes = [...new Set(allTransactions.value.map(tx => tx.transaction_type))];
  const options = [{ label: 'All types', value: null }];
  allTypes.forEach(type => {
    options.push({ label: getTransactionTypeText(type), value: type });
  });
  return options;
});

const filteredTransactions = computed(() => {
  let filtered = allTransactions.value;

  if (transactionsSearchQuery.value) {
    const query = transactionsSearchQuery.value.toLowerCase();
    filtered = filtered.filter(tx => 
      String(tx.id).includes(query) ||
      String(tx.amount).includes(query) ||
      (tx.payment_method || '').toLowerCase().includes(query) ||
      (tx.notes || '').toLowerCase().includes(query)
    );
  }

  if (selectedTransactionType.value) {
    filtered = filtered.filter(tx => tx.transaction_type === selectedTransactionType.value);
  }

  if (selectedTransactionDateRange.value && selectedTransactionDateRange.value.length === 2) {
    const [startDate, endDate] = selectedTransactionDateRange.value;
    const start = startDate ? new Date(startDate) : null;
    const end = endDate ? new Date(endDate) : null;
    
    filtered = filtered.filter(tx => {
      const txDate = new Date(tx.created_at);
      if (start && txDate < start) return false;
      if (end && txDate > end) return false;
      return true;
    });
  }

  return filtered;
});

const onTransactionsSearch = () => {
  // No need to reload all data, computed property handles filtering
}

const scrollToItem = (itemId) => {
  const element = document.getElementById(`prestation-card-${itemId}`);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

const goBack = () => {
  router.go(-1)
}

const totalOutstanding = computed(() => {
  return items.value.reduce((sum, i) => sum + itemRemaining(i), 0)
})

// ======= OPTIMISTIC UI HELPERS =======
const createTempId = () => `tmp_${Date.now()}_${Math.floor(Math.random()*1000)}`

const findItemIndexById = (id, isDependency = false) => {
  return items.value.findIndex(it => it.id === id && !!it.is_dependency === !!isDependency)
}

const updateItemAmounts = (itemIndex, amount, operation = 'add') => {
  if (itemIndex === -1) return
  
  const item = items.value[itemIndex]
  const currentPaid = Number(item.paid_amount) || 0
  const finalPrice = Number(item.final_price) || 0
  
  if (operation === 'add') {
    item.paid_amount = currentPaid + Number(amount)
  } else if (operation === 'subtract') {
    item.paid_amount = Math.max(0, currentPaid - Number(amount))
  } else if (operation === 'set') {
    item.paid_amount = Number(amount)
  }
  
  item.remaining_amount = Math.max(0, finalPrice - item.paid_amount)
  
  // Update payment status
  if (item.remaining_amount <= 0) {
    item.payment_status = 'paid'
  } else if (item.paid_amount > 0) {
    item.payment_status = 'partial'
  } else {
    item.payment_status = 'unpaid'
  }
}

const attachTransactionLocally = (tx, targetItemId = null, isDependency = false) => {
  // Add to global transactions list
  allTransactions.value = [tx, ...allTransactions.value]

  // Find target item
  let targetIndex = -1
  if (targetItemId) {
    targetIndex = findItemIndexById(targetItemId, isDependency)
  } else if (tx.fiche_navette_item_id && !isDependency) {
    targetIndex = findItemIndexById(tx.fiche_navette_item_id, false)
  } else if (tx.item_dependency_id) {
    // For dependency transactions, find by dependency ID
    targetIndex = items.value.findIndex(item => 
      item.is_dependency && item.id === tx.item_dependency_id
    )
  }

  console.log('Attaching transaction:', tx, 'to item index:', targetIndex, 'isDependency:', isDependency)

  if (targetIndex !== -1) {
    const targetItem = items.value[targetIndex]
    
    // Initialize transactions array if needed
    if (!Array.isArray(targetItem.transactions)) {
      targetItem.transactions = []
    }
    
    // Add transaction (avoid duplicates)
    if (!targetItem.transactions.find(t => t.id === tx.id)) {
      targetItem.transactions.unshift(tx)
    }
    
    // Update amounts based on transaction type
    if (tx.transaction_type === 'payment') {
      updateItemAmounts(targetIndex, tx.amount, 'add')
    } else if (tx.transaction_type === 'refund') {
      updateItemAmounts(targetIndex, Math.abs(tx.amount), 'subtract')
    }
  }
}

const detachTransactionLocally = (txId) => {
  // Remove from global transactions
  const removedTx = allTransactions.value.find(t => t.id === txId)
  allTransactions.value = allTransactions.value.filter(t => t.id !== txId)

  // Remove from items and update amounts
  items.value.forEach((item, itemIndex) => {
    if (Array.isArray(item.transactions)) {
      const txIndex = item.transactions.findIndex(t => t.id === txId)
      if (txIndex !== -1) {
        const tx = item.transactions[txIndex]
        item.transactions.splice(txIndex, 1)
        
        // Reverse the amount change
        if (tx.transaction_type === 'payment') {
          updateItemAmounts(itemIndex, tx.amount, 'subtract')
        } else if (tx.transaction_type === 'refund') {
          updateItemAmounts(itemIndex, Math.abs(tx.amount), 'add')
        }
      }
    }
  })
}

const updateTransactionLocally = (txId, newAmount, newNotes = null) => {
  // Update in global transactions
  const globalTxIndex = allTransactions.value.findIndex(t => t.id === txId)
  if (globalTxIndex !== -1) {
    const oldAmount = Number(allTransactions.value[globalTxIndex].amount) || 0
    allTransactions.value[globalTxIndex].amount = newAmount
    if (newNotes) allTransactions.value[globalTxIndex].notes = newNotes
    
    // Find the item and update amounts
    items.value.forEach((item, itemIndex) => {
      const txIndex = item.transactions?.findIndex(t => t.id === txId)
      if (txIndex !== -1) {
        item.transactions[txIndex].amount = newAmount
        if (newNotes) item.transactions[txIndex].notes = newNotes
        
        // Recalculate amounts for this item
        const totalPaid = item.transactions.reduce((sum, t) => {
          return sum + (t.transaction_type === 'payment' ? Number(t.amount) : -Math.abs(Number(t.amount)))
        }, 0)
        updateItemAmounts(itemIndex, totalPaid, 'set')
      }
    })
  }
}

const replaceTempTransaction = (tempId, realTx) => {
  // Replace in global transactions
  const globalIndex = allTransactions.value.findIndex(t => t.id === tempId)
  if (globalIndex !== -1) {
    allTransactions.value.splice(globalIndex, 1, realTx)
  }
  
  // Replace in items
  items.value.forEach(item => {
    if (Array.isArray(item.transactions)) {
      const txIndex = item.transactions.findIndex(t => t.id === tempId)
      if (txIndex !== -1) {
        item.transactions.splice(txIndex, 1, realTx)
      }
    }
  })
}

const canPayItem = (it) => {
  const amount = Number(it._pay_amount)
  return amount > 0
}

const canPayGlobal = computed(() => {
  const amount = Number(globalPayment.amount)
  return amount > 0
})

const checkItemOverpayment = (item, itemIndex) => {
  const amount = Number(item._pay_amount)
  const remaining = itemRemaining(item)
  
  if (amount > remaining && remaining > 0) {
    overpaymentData.required = remaining
    overpaymentData.paid = amount
    overpaymentData.excess = amount - remaining
    overpaymentData.itemIndex = itemIndex
    overpaymentData.isGlobal = false
    showOverpaymentModal.value = true
  }
}

const handleItemOverpayment = (data, itemIndex) => {
  overpaymentData.required = data.required
  overpaymentData.paid = data.paid
  overpaymentData.excess = data.excess
  overpaymentData.itemIndex = itemIndex
  overpaymentData.isGlobal = false
  showOverpaymentModal.value = true
}

const checkGlobalOverpayment = () => {
  const amount = Number(globalPayment.amount)
  const total = totalOutstanding.value
  
  console.log('checkGlobalOverpayment:', { amount, total, shouldShow: amount > total })
  
  if (amount > total) {
    // For global overpayment, we need to determine which item to use for overpayment processing
    // Use the first unpaid/partial item as the reference point
    const unpaidItems = items.value.filter(it => itemRemaining(it) > 0)
    const referenceItem = unpaidItems.length > 0 ? unpaidItems[0] : items.value[0]
    
    overpaymentData.required = total
    overpaymentData.paid = amount
    overpaymentData.excess = amount - total
    overpaymentData.itemIndex = null
    overpaymentData.isGlobal = true
    overpaymentData._globalReferenceItem = referenceItem // Store reference item for backend processing
    showOverpaymentModal.value = true
    
    console.log('Showing overpayment modal with data:', overpaymentData)
  }
}

const handleOverpayment = async (action) => {
  processingOverpayment.value = true

  try {
    // For global overpayments, first process normal payments for all outstanding amounts
    if (overpaymentData.isGlobal) {
      // Get all unpaid items
      const unpaidItems = items.value.filter(it => itemRemaining(it) > 0)
      
      if (unpaidItems.length > 0) {
        // Sort by priority: unpaid first (visa not granted), then partial, then paid
        const sortedItems = [...unpaidItems].sort((a, b) => {
          const aPaid = getItemPaidAmount(a)
          const bPaid = getItemPaidAmount(b)
          const aRemaining = itemRemaining(a)
          const bRemaining = itemRemaining(b)
          
          // Priority 1: Completely unpaid items (visa not granted) - paid_amount = 0
          const aIsUnpaid = aPaid === 0 && aRemaining > 0
          const bIsUnpaid = bPaid === 0 && bRemaining > 0
          
          // Priority 2: Partial payments - paid_amount > 0 but remaining > 0
          const aIsPartial = aPaid > 0 && aRemaining > 0
          const bIsPartial = bPaid > 0 && bRemaining > 0
          
          // Priority 3: Fully paid items - remaining = 0
          const aIsPaid = aRemaining <= 0
          const bIsPaid = bRemaining <= 0
          
          // Sort: unpaid first, then partial, then paid
          if (aIsUnpaid && !bIsUnpaid) return -1
          if (bIsUnpaid && !aIsUnpaid) return 1
          if (aIsPartial && !bIsPartial && !bIsUnpaid) return -1
          if (bIsPartial && !aIsPartial && !aIsUnpaid) return 1
          
          return 0
        })

        // Create bulk payment for all outstanding amounts
        const bulkPaymentItems = []
        
        for (const it of sortedItems) {
          const rem = itemRemaining(it)
          if (rem > 0) {
            bulkPaymentItems.push({
              fiche_navette_item_id: it.is_dependency ? (it.parent_item_id ?? it.parentItemId ?? it.parent_item) : it.id,
              item_dependency_id: it.is_dependency ? it.id : null,
              amount: rem,
              remaining_amount: rem,
              item_name: it.display_name || it.prestation?.name || 'Unknown Item',
              is_dependency: it.is_dependency || false
            })
          }
        }

        if (bulkPaymentItems.length > 0) {
          const bulkPayload = {
            fiche_navette_id: ficheId.value,
            caisse_session_id: caisseSessionId.value,
            cashier_id: cashier_id.value,
            patient_id: patientId.value,
            payment_method: mapPaymentMethod(globalPayment.method || 'cash'),
            transaction_type: 'bulk_payment',
            total_amount: overpaymentData.required, // Pay exact outstanding amount
            items: bulkPaymentItems,
            notes: `Global payment for ${bulkPaymentItems.length} items`
          }

          try {
            await axios.post('/api/financial-transactions-bulk-payment', bulkPayload)
          } catch (bulkError) {
            console.error('Bulk payment failed:', bulkError)
            toast.add({ 
              severity: 'error', 
              summary: 'Payment Failed', 
              detail: 'Failed to process payments before donation', 
              life: 4000 
            })
            return
          }
        }
      }

      // Now handle the excess amount as donation or balance
      if (action === 'donate') {
        // Get the first unpaid item to associate the donation with
        const firstUnpaidItem = unpaidItems.length > 0 ? unpaidItems[0] : null
        const ficheNavetteItemId = firstUnpaidItem ? 
          (firstUnpaidItem.is_dependency ? 
            (firstUnpaidItem.parent_item_id ?? firstUnpaidItem.parentItemId ?? firstUnpaidItem.parent_item) : 
            firstUnpaidItem.id
          ) : null
        
        // Create donation transaction associated with the first item
        const donationPayload = {
          patient_id: patientId.value,
          cashier_id: cashier_id.value,
          amount: overpaymentData.excess,
          transaction_type: 'donation',
          payment_method: mapPaymentMethod(globalPayment.method || 'cash'),
          notes: `Global payment excess donation of ${formatCurrency(overpaymentData.excess)}`,
          // Associate donation with the first item from global payment
          fiche_navette_item_id: ficheNavetteItemId
        }

        const donationRes = await axios.post('/api/financial-transactions', donationPayload)
        const donationData = donationRes?.data ?? {}

        if (donationData?.success) {
          toast.add({
            severity: 'success',
            summary: 'Payment & Donation Complete',
            detail: `All outstanding amounts paid and ${formatCurrency(overpaymentData.excess)} donated successfully!`,
            life: 5000
          })
        }
      } else if (action === 'balance') {
        // Get the first unpaid item to associate the credit with
        const firstUnpaidItem = unpaidItems.length > 0 ? unpaidItems[0] : null
        const ficheNavetteItemId = firstUnpaidItem ? 
          (firstUnpaidItem.is_dependency ? 
            (firstUnpaidItem.parent_item_id ?? firstUnpaidItem.parentItemId ?? firstUnpaidItem.parent_item) : 
            firstUnpaidItem.id
          ) : null
        
        // Add excess to patient balance associated with the first item
        const balancePayload = {
          patient_id: patientId.value,
          cashier_id: cashier_id.value,
          amount: overpaymentData.excess,
          transaction_type: 'credit',
          payment_method: mapPaymentMethod(globalPayment.method || 'cash'),
          notes: `Global payment excess added to patient balance: ${formatCurrency(overpaymentData.excess)}`,
          // Associate credit with the first item from global payment
          fiche_navette_item_id: ficheNavetteItemId
        }

        const balanceRes = await axios.post('/api/financial-transactions', balancePayload)
        const balanceData = balanceRes?.data ?? {}

        if (balanceData?.success) {
          toast.add({
            severity: 'success',
            summary: 'Payment & Credit Complete',
            detail: `All outstanding amounts paid and ${formatCurrency(overpaymentData.excess)} added to patient balance!`,
            life: 5000
          })
        }
      }

      // Clear global payment amount
      globalPayment.amount = null
      showOverpaymentModal.value = false
      
      // Refresh data to show updates
      await loadItems()
      return
    }

    // Handle individual item overpayments (existing code)
    const dep = overpaymentData._dependency ?? null
    const targetFicheItemId = dep ? (dep.parent_item_id ?? dep.parentItemId ?? dep.parent_item) : items.value[overpaymentData.itemIndex]?.id

    const payload = {
      fiche_navette_item_id: targetFicheItemId,
      patient_id: patientId.value,
      cashier_id: cashier_id.value,
      required_amount: overpaymentData.required,
      paid_amount: overpaymentData.paid,
      payment_method: mapPaymentMethod(
        dep?._pay_method ?? items.value[overpaymentData.itemIndex]?._pay_method ?? 'cash'
      ),
      overpayment_action: action, // 'donate' or 'balance'
      notes: `Overpayment handling - ${action === 'donate' ? 'donated' : 'added to balance'}`
    }

    // Add dependency information if this is a dependency payment
    if (dep) {
      payload.item_dependency_id = dep.id
      payload.dependent_prestation_id = dep.dependent_prestation_id ?? dep.prestation_id ?? dep.prestation?.id
    }

    const res = await axios.post('/api/financial-transactions/handle-overpayment', payload)
    const data = res?.data ?? {}

    if (data?.success) {
      const actionText = action === 'donate' ? 'don effectué' : 'crédit ajouté au compte patient'
      toast.add({
        severity: 'success',
        summary: 'Paiement traité',
        detail: data.message || `Paiement enregistré et ${actionText}`,
        life: 4000
      })

      // Clear individual item inputs
      if (overpaymentData._dependency) {
        overpaymentData._dependency._pay_amount = null
      } else if (typeof overpaymentData.itemIndex === 'number') {
        items.value[overpaymentData.itemIndex]._pay_amount = null
      }

      // Close modal and clear dependency reference
      showOverpaymentModal.value = false
      overpaymentData._dependency = null

      // Refresh items data to show updated amounts
      await loadItems()
    } else {
      toast.add({ severity: 'error', summary: 'Erreur', detail: data.message || 'Processing failed', life: 4000 })
    }
  } catch (e) {
    console.error('Overpayment handling error:', e)
    const msg = e.response?.data?.message ?? 'Une erreur est survenue lors du traitement du paiement'
    toast.add({ severity: 'error', summary: 'Erreur', detail: msg, life: 4000 })
  } finally {
    processingOverpayment.value = false
    showOverpaymentModal.value = false
    overpaymentData._dependency = null
  }
}

const cancelOverpayment = () => {
  if (overpaymentData.isGlobal) {
    globalPayment.amount = overpaymentData.required
  } else if (overpaymentData._dependency) {
    // restore dependency input
    overpaymentData._dependency._pay_amount = overpaymentData.required
  } else if (typeof overpaymentData.itemIndex === 'number') {
    items.value[overpaymentData.itemIndex]._pay_amount = overpaymentData.required
  }
  // clear temporary dependency pointer and close modal
  overpaymentData._dependency = null
  showOverpaymentModal.value = false
}

// Handle global payment for the required amount from Return Info button
const handlePayGlobalAmount = async (amount) => {
  // Set the global payment amount to the required amount
  globalPayment.amount = amount
  
  // Close the overpayment modal
  showOverpaymentModal.value = false
  
  // Trigger the global payment
  await payGlobal()
}

//create closeRefundModal 
const closeRefundModal = () => {
  showRefundModal.value = false;
  refundData.notes = '';
};

const hasExistingRefund = (transaction, item) => {
  if (!transaction || !item || !Array.isArray(item.transactions)) return false;

  return item.transactions.some(t => {
    if (t.transaction_type !== 'refund') return false;

    if (t.original_transaction_id && t.original_transaction_id === transaction.id) return true;
    if (t.originalTransactionId && t.originalTransactionId === transaction.id) return true;
    if (t.refunded_transaction_id && t.refunded_transaction_id === transaction.id) return true;
    if (t.original_transaction && t.original_transaction.id === transaction.id) return true;

    try {
      const refundDate = new Date(t.created_at);
      const paymentDate = new Date(transaction.created_at);
      if (!isNaN(refundDate.getTime()) && !isNaN(paymentDate.getTime())) {
        if (refundDate >= paymentDate && Number(t.amount ?? 0) > 0) return true;
      }
    } catch (e) {
      // ignore parse errors
    }

    return false;
  });
}

const canRefund = (transaction) => {
  if (!transaction) return false;

  // Check transaction type and amount
  if (transaction.transaction_type !== 'payment') return false;
  const amount = Number(transaction.amount ?? transaction.total ?? 0);
  if (amount <= 0) return false;

  console.log('Checking canRefund for transaction:', transaction);

  // Get the associated item
  const item = transaction.fiche_navette_item ?? transaction.ficheNavetteItem ?? {};

  // Check if item exists and has required associations
  if (!item || !item.id) {
    console.warn('Invalid or missing item for transaction:', transaction);
    return false;
  }

  // PRIORITY CHECK: If status is pending or confirmed, allow refund without other conditions
  const ficheStatus = String(transaction?.fiche_navette_item_status).toLowerCase();
  if (['pending', 'confirmed'].includes(ficheStatus)) {
    console.log('Status is pending/confirmed - allowing refund without additional conditions');
    return true;
  }

  // Check for existing refunds (only if status is not pending/confirmed)
  if (hasExistingRefund(transaction, item)) {
    console.debug('Transaction already has a refund:', transaction.id);
    return false;
  }

  const itemId = item.id ?? transaction.fiche_navette_item_id ?? transaction.ficheNavetteItem?.id;

  // Check for existing authorization (only if status is not pending/confirmed)
  const auth = transaction?.refund_authorization ?? refundAuthMap.value[itemId] ?? null;

  // If there's an authorization, check its status
  if (auth) {
    const status = String((auth.status ?? auth.status_text ?? '')).toLowerCase();
    if (['used', 'approved'].includes(status)) {
      console.debug('Authorization already used/approved:', auth);
      return false;
    }


    const requested = Number(auth.requested_amount ?? auth.requestedAmount ?? 0);
    if (requested > 0) return true;
  }

  console.log('No existing authorization, but status is not pending/confirmed - refund not allowed');

  console.debug('Refund not allowed for status:', ficheStatus);
  return false;
}

const canShowRefundButton = (transaction, item) => {
  console.log('Checking canShowRefundButton for transaction:', transaction, 'and item:', item);
  
  if (!canRefund(transaction)) return false;
  if (!item) return false;

  // Check reactive refund state first
  if (refundExistsForTransaction.has(`${transaction.id}_${item.id}`)) return false;

  if (hasExistingRefund(transaction, item)) {
    // Add to reactive set for future checks
    refundExistsForTransaction.add(`${transaction.id}_${item.id}`);
    return false;
  }

  const txAuth = transaction?.refund_authorization ?? refundAuthMap.value[item.id];
  console.log('Checking refund button visibility for item:', item.id, 'Transaction:', transaction?.id, 'Authorization:', txAuth);
  
  if (txAuth) {
    const s = String((txAuth.status ?? txAuth.status_text ?? '')).toLowerCase();
    if (['used', 'approved', 'pending'].includes(s)) return false;
    return true;
  }

  // Check if there is an existing authorization in process
  const existingAuth = refundAuthMap.value[item.id];
  if (existingAuth) {
    const authStatus = String((existingAuth.status ?? existingAuth.status_text ?? '')).toLowerCase();
    return !['used', 'approved', 'pending'].includes(authStatus);
  }

  // Get the fiche navette status - check multiple possible paths
  let ficheStatus = '';
  
  // Check item's fiche_navette status first
  if (item.fiche_navette?.status) {
    ficheStatus = String(item.fiche_navette.status).toLowerCase();
  } 
  // Check item's direct status
  else if (item.status) {
    ficheStatus = String(item.status).toLowerCase();
  }
  // For dependencies, check parent item's fiche status
  else if (item.is_dependency && item.parent_item_id) {
    const parentItem = items.value.find(i => !i.is_dependency && i.id === item.parent_item_id);
    if (parentItem?.fiche_navette?.status) {
      ficheStatus = String(parentItem.fiche_navette.status).toLowerCase();
    } else if (parentItem?.status) {
      ficheStatus = String(parentItem.status).toLowerCase();
    }
  }

  console.log('Fiche status for refund check:', ficheStatus, 'Item:', item.id);
  
  // Only show refund button for pending/confirmed status
  return ['pending', 'confirmed'].includes(ficheStatus);
}

const openRefundModal = async (transaction, item) => {
  console.log(transaction, item);

  // Check if refund already exists for this transaction
  if (hasExistingRefund(transaction, item)) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Refund Already Exists', 
      detail: 'A refund already exists for this payment or item.', 
      life: 4000 
    });
    
    // Add to reactive set to hide button
    refundExistsForTransaction.add(`${transaction.id}_${item.id}`);
    return;
  }

  refundAuthorization.value = null;

  const txAuth = transaction?.refund_authorization ?? refundAuthMap.value[item.id] ?? null;
  if (txAuth) refundAuthorization.value = txAuth;

  const paymentTx = transaction?.transaction_type === 'payment' ? transaction : (item.transactions || []).find(t => t.transaction_type === 'payment');

  if (!paymentTx) {
    toast.add({ severity: 'warn', summary: 'No Payment', detail: 'Cannot refund an unpaid prestation.', life: 3000 });
    return;
  }

  refundData.transaction = paymentTx;
  refundData.item = item;
  refundData.errors = {};
  refundData.notes = '';

  if (refundAuthorization.value) {
    const amt = Number(refundAuthorization.value.requested_amount ?? refundAuthorization.value.requested_amount ?? 0);
    refundData.amount = Math.min(amt, Number(paymentTx.amount ?? 0));
    refundData.fixedAmount = true;
  } else {
    refundData.amount = Number(paymentTx.amount ?? 0);
    refundData.fixedAmount = false;
  }

  showRefundModal.value = true;
}

const processRefund = async () => {
  refundData.errors = {};
  if (!refundData.amount || Number(refundData.amount) <= 0) {
    refundData.errors.amount = 'Montant invalide';
    return;
  }
  processingRefund.value = true;

  try {
    if (refundAuthorization.value) {
      if (String(refundAuthorization.value.status) !== 'approved') {
        try {
          const approvePayload = {
            authorized_amount: refundAuthorization.value.requested_amount ?? refundData.amount
          };
          const approveRes = await axios.post(`/api/refund-authorizations/${refundAuthorization.value.id}/approve`, approvePayload);
          if (approveRes?.data?.success) {
            refundAuthorization.value.status = 'approved';
            refundAuthorization.value.authorized_amount = approvePayload.authorized_amount;
            refundAuthMap.value[refundData.item.id] = refundAuthorization.value;
          } else {
            throw new Error(approveRes?.data?.message ?? 'Approval failed');
          }
        } catch (approveErr) {
          console.error('Approval error', approveErr);
          toast.add({ severity: 'error', summary: 'Approval Failed', detail: 'Failed to approve refund authorization.', life: 4000 });
          processingRefund.value = false;
          return;
        }
      }

      const payload = {
        fiche_navette_id: ficheId.value,
        fiche_navette_item_id: refundData.item.id,
        refund_authorization_id: refundAuthorization.value.id,
        refund_amount: refundData.amount,
        cashier_id: cashier_id.value,
        notes: refundData.notes || `Refund by authorization ${refundAuthorization.value.id}`
      };

      const res = await axios.post('/api/financial-transactions/process-refund', payload);
      if (res?.data?.success) {
        toast.add({ severity: 'success', summary: 'Refund Done', detail: `Refund of ${refundData.amount} processed`, life: 4000 });
        refundAuthorization.value.status = 'used';
        refundAuthMap.value[refundData.item.id] = refundAuthorization.value;
        
        // Add to reactive set to hide button
        refundExistsForTransaction.add(`${refundData.transaction.id}_${refundData.item.id}`);
        
        closeRefundModal();
        await loadItems();
        await load();
      } else {
        toast.add({ severity: 'error', summary: 'Refund Failed', detail: res?.data?.message ?? 'Failed to process refund', life: 4000 });
      }
    } else {
      const payload = {
        original_transaction_id: refundData.transaction.id,
        refund_amount: refundData.amount,
        cashier_id: cashier_id.value,
        notes: refundData.notes || `Refund for transaction ${refundData.transaction.id}`
      };
      const res = await axios.post('/api/financial-transactions/process-refund', payload);
      if (res?.data?.success) {
        toast.add({ severity: 'success', summary: 'Refund Done', detail: `Refund of ${refundData.amount} processed`, life: 4000 });
        
        // Add to reactive set to hide button
        refundExistsForTransaction.add(`${refundData.transaction.id}_${refundData.item.id}`);
        
        closeRefundModal();
        await loadItems();
        await load();
      } else {
        toast.add({ severity: 'error', summary: 'Refund Failed', detail: res?.data?.message ?? 'Failed to process refund', life: 4000 });
      }
    }
  } catch (e) {
    console.error('Refund error', e);
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message ?? 'Refund error', life: 4000 });
  } finally {
    processingRefund.value = false;
  }
};
// Component utility functions for main page functionality

const canUpdate = (transaction) => {
  // Only allow updating payments (not refunds)
  if (transaction.transaction_type !== 'payment') return false;
  
  // Don't allow editing payments that have authorized refunds
  if (transaction.refund_authorization) {
    const authStatus = String(transaction.refund_authorization.status || '').toLowerCase();
    if (['pending', 'approved', 'used'].includes(authStatus)) {
      return false; // Cannot edit payments with refund authorizations
    }
  }
  
  // Check if this is the latest payment transaction
  const allPaymentIds = items.value.flatMap(it => 
    (it.transactions || [])
      .filter(tx => tx.transaction_type === 'payment')
      .map(tx => tx.id)
  ).filter(id => id);
  
  const latestPaymentId = Math.max(...allPaymentIds);
  return transaction.id === latestPaymentId;
}

const openUpdateModal = (transaction) => {
  updateData.transaction = transaction
  updateData.amount = transaction.amount
  updateData.notes = ''
  updateData.errors = {}
  updateData.maxAmount = totalOutstanding.value + transaction.amount
  showUpdateModal.value = true
}

const closeUpdateModal = () => {
  showUpdateModal.value = false
  updateData.transaction = null
  updateData.amount = 0
  updateData.notes = ''
  updateData.errors = {}
  updateData.maxAmount = 0
}

const processUpdate = async () => {
  updateData.errors = {}
  const { transaction, amount, notes } = updateData
  
  if (!amount || amount <= 0) {
    updateData.errors.amount = 'Montant invalide'
    return
  }
  
  processingUpdate.value = true

  // Store original amount for rollback
  const originalAmount = transaction.amount
  const amountDiff = Number(amount) - originalAmount

  // Apply optimistic update immediately
  updateTransactionLocally(transaction.id, amountDiff)
  
  try {
    const payload = {
      amount,
      notes: notes || `Correction de paiement de ${formatCurrency(transaction.amount)} vers ${formatCurrency(amount)}`,
      payment_method: transaction.payment_method
    }

    const res = await axios.put(`/api/financial-transactions/${transaction.id}`, payload)
    const data = res?.data ?? {}
    
    if (data?.success ?? (res?.status === 200)) {
      // Update the transaction in place with real data
      const realTx = data?.data ?? data
      if (realTx && realTx.id) {
        const txIndex = allTransactions.value.findIndex(t => t.id === transaction.id)
        if (txIndex !== -1) {
          allTransactions.value[txIndex] = { ...allTransactions.value[txIndex], ...realTx }
        }
        // Update in items as well
        items.value.forEach(item => {
          const itemTxIndex = item.transactions.findIndex(t => t.id === transaction.id)
          if (itemTxIndex !== -1) {
            item.transactions[itemTxIndex] = { ...item.transactions[itemTxIndex], ...realTx }
          }
        })
      }
      
      toast.add({
        severity: 'success',
        summary: 'Mise à jour réussie',
        detail: `Le paiement #${transaction.id} a été mis à jour`,
        life: 4000
      })
      closeUpdateModal()
    } else {
      // Rollback optimistic update on failure
      updateTransactionLocally(transaction.id, -amountDiff)
      toast.add({ severity: 'error', summary: 'Erreur', detail: data.message || 'Échec de la mise à jour', life: 4000 })
    }
  } catch (e) {
    // Rollback optimistic update on error
    updateTransactionLocally(transaction.id, -amountDiff)
    console.error('Update error', e)
    const msg = e.response?.data?.message ?? 'Échec de la mise à jour du paiement'
    toast.add({ severity: 'error', summary: 'Erreur', detail: msg, life: 4000 })
  } finally {
    processingUpdate.value = false
  }
}

const load = async () => {
  try {
    const rep = await axios.get('/api/financial-transactions-by-fiche-navette', {
      params: { fiche_navette_id: ficheId.value, per_page: 1000 }
    });
    const data = rep?.data ?? {};
    allTransactions.value = data.data ?? [];

    refundAuthMap.value = {}; // reset
    const topAuths = data.refund_authorizations ?? {};
    Object.keys(topAuths).forEach(itemId => {
      const list = topAuths[itemId];
      if (Array.isArray(list) && list.length > 0) refundAuthMap.value[itemId] = list[0];
    });

    (allTransactions.value || []).forEach(tx => {
      if (tx?.refund_authorization && tx.fiche_navette_item_id) {
        refundAuthMap.value[tx.fiche_navette_item_id] = tx.refund_authorization;
      }
    });

    console.log('loaded transactions:', allTransactions.value.length);
    console.log('refundAuthMap', refundAuthMap.value);
  } catch (error) {
    console.error('Error loading financial transactions:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load transaction data', life: 4000 });
  }
};

const loadItems = async () => {
  if (!ficheId.value) return
  loading.value = true
  try {
    const [itemsRes, transactionsRes] = await Promise.all([
      axios.get(`/api/reception/fiche-navette/${ficheId.value}/items`),
      axios.get('/api/financial-transactions', { params: { fiche_navette_id: ficheId.value, per_page: 1000 } }),
    ]);
    
    const rawItems = itemsRes?.data?.data ?? itemsRes?.data ?? [];
    const rawTransactions = transactionsRes?.data?.data ?? transactionsRes?.data ?? [];
    
    console.log('Raw items from API:', rawItems);
    console.log('Raw transactions from API:', rawTransactions);
    
    allTransactions.value = rawTransactions;

    // Create transaction maps for both regular items and dependencies
    const txMap = rawTransactions.reduce((acc, tx) => {
      const key = tx.fiche_navette_item_id ?? tx.ficheNavetteItem?.id ?? null;
      if (key) {
        if (!acc[key]) acc[key] = [];
        acc[key].push(tx);
      }
      
      // Also map transactions by item_dependency_id for dependencies
      const depKey = tx.item_dependency_id ?? tx.itemDependencyId ?? null;
      if (depKey) {
        if (!acc[`dep_${depKey}`]) acc[`dep_${depKey}`] = [];
        acc[`dep_${depKey}`].push(tx);
      }
      
      return acc;
    }, {});

    // Flatten items + dependencies so dependencies appear as full items in the list
    const flat = [];
    rawItems.forEach(it => {
      // Add the main item
      flat.push({
        ...it,
        _pay_amount: null,
        _pay_method: 'cash',
        _transactionsVisible: false,
        transactions: txMap[it.id] ?? [],
        parent_item_id: null,
        is_dependency: false
      });

      // Add all dependencies (handle multiple possible property names)
      const dependencies = it.dependencies ?? it.item_dependencies ?? it.itemDependencies ?? [];
      console.log(`Item ${it.id} dependencies:`, dependencies);
      
      dependencies.forEach(dep => {
        const dependencyTransactions = txMap[`dep_${dep.id}`] ?? [];
        console.log(`Dependency ${dep.id} transactions:`, dependencyTransactions);
        
        flat.push({
          ...dep,
          display_name: dep.display_name ?? dep.dependencyPrestation?.name ?? dep.dependency_prestation?.name ?? dep.custom_name ?? 'Dépendance',
          service_name: dep.dependencyPrestation?.service?.name ?? dep.dependency_prestation?.service?.name ?? dep.dependencyPrestation?.specialization?.name ?? dep.dependency_prestation?.specialization?.name ?? '',
          prestation: dep.dependencyPrestation ?? dep.dependency_prestation ?? null,
          default_payment_type: dep.dependencyPrestation?.default_payment_type ?? dep.dependency_prestation?.default_payment_type ?? null,
          min_versement_amount: dep.dependencyPrestation?.min_versement_amount ?? dep.dependency_prestation?.min_versement_amount ?? 0,
          _pay_amount: null,
          _pay_method: 'cash',
          _transactionsVisible: false,
          transactions: dependencyTransactions,
          parent_item_id: it.id,
          is_dependency: true
        });
      });
    });

    console.log('Flattened items (including dependencies):', flat);

    // Sort flattened items by payment priority: unpaid (no paid amount, remaining>0) first,
    // then partial (paid>0 and remaining>0), then fully paid (remaining<=0).
    flat.sort((a, b) => {
      const aPaid = getItemPaidAmount(a)
      const bPaid = getItemPaidAmount(b)
      const aRemaining = itemRemaining(a)
      const bRemaining = itemRemaining(b)

      const aIsUnpaid = aPaid === 0 && aRemaining > 0
      const bIsUnpaid = bPaid === 0 && bRemaining > 0

      const aIsPartial = aPaid > 0 && aRemaining > 0
      const bIsPartial = bPaid > 0 && bRemaining > 0

      const aIsPaid = aRemaining <= 0
      const bIsPaid = bRemaining <= 0

      if (aIsUnpaid && !bIsUnpaid) return -1
      if (bIsUnpaid && !aIsUnpaid) return 1
      if (aIsPartial && !bIsPartial && !bIsUnpaid) return -1
      if (bIsPartial && !aIsPartial && !aIsUnpaid) return 1

      return 0
    })

    items.value = flat;

    if (itemsRes?.data?.meta?.fiche_patient_name) {
      fichePatientName.value = itemsRes.data.meta.fiche_patient_name
    } else if (items.value.length && items.value[0].fiche_navette && items.value[0].fiche_navette.patient) {
      fichePatientName.value = items.value[0].fiche_navette.patient.name
    } else {
      fichePatientName.value = null
    }
  } catch (e) {
    console.error('Failed to load data', e)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load prestations and transactions', life: 4000 })
  } finally {
    loading.value = false
  }
}

const payItem = async (idx) => {
  const it = items.value[idx]
  if (!it) return

  // If this flattened item is actually a dependency, delegate to payDependency
  if (it.is_dependency) {
    const parentItem = items.value.find(i => !i.is_dependency && (i.id === it.parent_item_id || i.id === it.parent_item)) || null
    await payDependency(parentItem || { id: it.parent_item_id || it.parentItemId }, it)
    return
  }

  const amount = Number(it._pay_amount)
  const paymentMethod = it._pay_method || globalPayment.method || 'cash'
  
  if (!amount || amount <= 0) {
    toast.add({ severity: 'warn', summary: 'Invalid amount', detail: 'The entered amount is invalid', life: 3000 })
    return
  }

  if (amount > itemRemaining(it)) {
    checkItemOverpayment(it, idx)
    return
  }

  // Check if payment method requires approval
  if (paymentMethod === 'card' || paymentMethod === 'cheque') {
    // Show approval modal
    approvalData.fiche_navette_item_id = it.id
    approvalData.item_dependency_id = null
    approvalData.amount = amount
    approvalData.cashier_id = cashier_id?.value ?? null
    approvalData.patient_id = patientId.value
    approvalData.method = paymentMethod
    approvalData.itemName = it.display_name ?? it.prestation?.name ?? it.custom_name ?? 'Prestation'
    approvalData.itemIndex = idx
    showApprovalModal.value = true
    return
  }

  // Process cash payment directly
  await processDirectPayment(it, amount, paymentMethod, idx)
}

const processDirectPayment = async (item, amount, paymentMethod, itemIndex) => {
  const cashierId = cashier_id?.value ?? null
  const patient_id = patientId?.value ?? null

  if (!cashierId) {
    toast.add({ severity: 'error', summary: 'Cashier missing', detail: 'cashier_id is required', life: 4000 })
    return
  }
  if (!patient_id) {
    toast.add({ severity: 'error', summary: 'Patient missing', detail: 'patient_id is required', life: 4000 })
    return
  }

  // Create optimistic transaction
  const tempId = createTempId()
  const now = new Date().toISOString()
  const tempTx = {
    id: tempId,
    fiche_navette_item_id: item.id,
    amount: Number(amount),
    payment_method: mapPaymentMethod(paymentMethod),
    transaction_type: 'payment',
    status: 'pending',
    cashier_id: cashierId,
    patient_id,
    notes: `Payment for ${item.display_name || 'item'}`,
    created_at: now,
    updated_at: now
  }

  // Apply optimistic update immediately
  attachTransactionLocally(tempTx, item.id, false)
  
  // Clear payment input immediately for better UX
  if (items.value[itemIndex]) {
    items.value[itemIndex]._pay_amount = null
  }

  const payload = {
    fiche_navette_id: ficheId.value,
    fiche_navette_item_id: item.id,
    caisse_session_id: caisseSessionId.value,
    cashier_id: cashierId,
    patient_id: patientId?.value,
    amount: amount,
    transaction_type: 'payment',
    payment_method: mapPaymentMethod(paymentMethod),
    notes: `Payment for fiche item ${item.id}`
  }

  try {
    const res = await axios.post('/api/financial-transactions', payload)
    const data = res?.data ?? {}
    const realTx = data?.data ?? data
    
    if (data?.success ?? (data?.data ? true : false)) {
      // Replace optimistic transaction with real one if available
      if (realTx && realTx.id) {
        replaceTempTransaction(tempId, realTx)
      } else {
        // If no real transaction returned, just mark as completed
        updateTransactionLocally(tempId, amount)
      }
      
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: `Paiement de ${formatCurrency(amount)} effectué`,
        life: 3000
      })
    } else {
      // Rollback optimistic update on failure
      detachTransactionLocally(tempId)
      toast.add({ severity: 'error', summary: 'Error', detail: data.message || 'Payment failed', life: 4000 })
    }
  } catch (e) {
    // Rollback optimistic update on error
    detachTransactionLocally(tempId)
    console.error('Payment error', e)
    const msg = e.response?.data?.message ?? 'Payment failed'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 })
  }
}

const itemDependencyRemaining = (dep) => {
  return getItemRemainingAmount(dep)
}

const payDependency = async (parentItem, dep) => {
  const amount = Number(dep._pay_amount)
  const paymentMethod = dep._pay_method || globalPayment.method || 'cash'
  
  if (!amount || amount <= 0) {
    toast.add({ severity: 'warn', summary: 'Invalid amount', detail: 'The entered amount is invalid', life: 3000 })
    return
  }

  const remaining = itemDependencyRemaining(dep)
  if (amount > remaining && remaining > 0) {
    overpaymentData.required = remaining
    overpaymentData.paid = amount
    overpaymentData.excess = amount - remaining
    overpaymentData._dependency = dep
    overpaymentData.isGlobal = false
    showOverpaymentModal.value = true
    return
  }

  // Check if payment method requires approval
  if (paymentMethod === 'card' || paymentMethod === 'cheque') {
    // Show approval modal for dependency
    approvalData.fiche_navette_item_id = parentItem.id
    approvalData.item_dependency_id = dep.id
    approvalData.amount = amount
    approvalData.method = paymentMethod
    approvalData.itemName = `${dep.display_name ?? dep.dependencyPrestation?.name ?? 'Dépendance'} (sous ${parentItem.display_name ?? parentItem.prestation?.name ?? 'Prestation'})`
    approvalData.itemIndex = null
    showApprovalModal.value = true
    return
  }

  // Process cash payment directly
  await processDependencyDirectPayment(parentItem, dep, amount, paymentMethod)
}

const processDependencyDirectPayment = async (parentItem, dep, amount, paymentMethod) => {
  const cashierId = cashier_id?.value ?? null
  if (!cashierId) {
    toast.add({ severity: 'error', summary: 'Cashier missing', detail: 'cashier_id is required', life: 4000 })
    return
  }

  // Create optimistic transaction for dependency
  const tempId = createTempId()
  const now = new Date().toISOString()
  const tempTx = {
    id: tempId,
    item_dependency_id: dep.id,
    fiche_navette_item_id: parentItem.id,
    amount: Number(amount),
    payment_method: mapPaymentMethod(paymentMethod),
    transaction_type: 'payment',
    status: 'pending',
    cashier_id: cashierId,
    patient_id: patientId?.value ?? null,
    notes: `Payment for dependency ${dep.display_name || 'dependency'}`,
    created_at: now,
    updated_at: now
  }

  // Apply optimistic update immediately for dependency
  attachTransactionLocally(tempTx, dep.id, true)
  
  console.log('Processing dependency payment:', {
    parentItem: parentItem.id,
    dependency: dep.id,
    amount,
    tempTx
  })
  
  // Clear dependency payment input immediately
  if (dep) {
    dep._pay_amount = null
  }

  const payload = {
    fiche_navette_id: ficheId.value,
    fiche_navette_item_id: parentItem.id,
    item_dependency_id: dep.id,
    patient_id: patientId?.value ?? null,
    caisse_session_id: caisseSessionId.value,
    cashier_id: cashierId,
    dependent_prestation_id: dep.dependent_prestation_id ?? dep.prestation_id ?? dep.prestation?.id ?? null,
    amount: amount,
    transaction_type: 'payment',
    payment_method: mapPaymentMethod(paymentMethod),
    notes: `Payment for dependency ${dep.id} under fiche item ${parentItem.id}`
  }

  try {
    const res = await axios.post('/api/financial-transactions', payload)
    const data = res?.data ?? {}
    const realTx = data?.data ?? data
    
    if (data?.success ?? (data?.data ? true : false)) {
      // Replace optimistic transaction with real one if available
      if (realTx && realTx.id) {
        replaceTempTransaction(tempId, realTx)
      } else {
        // If no real transaction returned, just mark as completed
        updateTransactionLocally(tempId, amount)
      }
      
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: `Paiement dépendance de ${formatCurrency(amount)} effectué`,
        life: 3000
      })
    } else {
      // Rollback optimistic update on failure
      detachTransactionLocally(tempId)
      toast.add({ severity: 'error', summary: 'Error', detail: data.message || 'Payment failed', life: 4000 })
    }
  } catch (e) {
    // Rollback optimistic update on error
    detachTransactionLocally(tempId)
    console.error('Dependency payment error', e)
    const msg = e.response?.data?.message ?? 'Dependency payment failed'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 })
  }
}
const getAuthorizedRefunds = (item) => {

  const itemId = item.id ?? item.fiche_navette_item_id ?? item.ficheNavetteItem?.id;
  const auth = refundAuthMap.value[itemId] ?? null;
  if (auth && ['approved', 'used'].includes(String(auth.status).toLowerCase())) {
    return Number(auth.authorized_amount ?? auth.authorizedAmount ?? 0);
  }
  return 0;
}
const payGlobal = async () => {
  let amount = Number(globalPayment.amount)
  if (!amount || amount <= 0) {
    toast.add({ severity: 'warn', summary: 'Invalid amount', detail: 'Please enter an amount', life: 3000 })
    return
  }

  // Check for overpayment first
  if (amount > totalOutstanding.value) {
    checkGlobalOverpayment()
    return
  }

  // Check if global payment method requires approval
  if (globalPayment.method === 'card' || globalPayment.method === 'cheque') {
    toast.add({ 
      severity: 'warn', 
      summary: 'Approbation Requise', 
      detail: 'Les paiements par carte et chèque nécessitent une approbation. Veuillez effectuer des paiements individuels.', 
      life: 6000 
    })
    return
  }

  const cashierId = cashier_id?.value ?? null

  if (!cashierId) {
    toast.add({ severity: 'error', summary: 'Cashier missing', detail: 'cashier_id is required', life: 4000 })
    return
  }

  // Get all items with remaining amounts (unpaid/partial)
  const unpaidItems = items.value.filter(it => itemRemaining(it) > 0)
  
  if (unpaidItems.length === 0) {
    toast.add({ severity: 'warn', summary: 'Nothing to pay', detail: 'No outstanding items to pay', life: 3000 })
    return
  }
  // Sort by priority: unpaid first (visa not granted), then partial, then paid
  const sortedItems = [...unpaidItems].sort((a, b) => {
    const aPaid = getItemPaidAmount(a)
    const bPaid = getItemPaidAmount(b)
    const aRemaining = itemRemaining(a)
    const bRemaining = itemRemaining(b)
    
    // Priority 1: Completely unpaid items (visa not granted) - paid_amount = 0
    const aIsUnpaid = aPaid === 0 && aRemaining > 0
    const bIsUnpaid = bPaid === 0 && bRemaining > 0
    
    // Priority 2: Partial payments - paid_amount > 0 but remaining > 0
    const aIsPartial = aPaid > 0 && aRemaining > 0
    const bIsPartial = bPaid > 0 && bRemaining > 0
    
    // Priority 3: Fully paid items - remaining = 0
    const aIsPaid = aRemaining <= 0
    const bIsPaid = bRemaining <= 0
    
    // Sort: unpaid first, then partial, then paid
    if (aIsUnpaid && !bIsUnpaid) return -1
    if (bIsUnpaid && !aIsUnpaid) return 1
    if (aIsPartial && !bIsPartial && !bIsUnpaid) return -1
    if (bIsPartial && !aIsPartial && !aIsUnpaid) return 1
    
    return 0
  })

  // Create a single bulk payment request with all items
  const bulkPaymentItems = []
  
  for (const it of sortedItems) {
    const rem = itemRemaining(it)
    if (rem > 0) {
      bulkPaymentItems.push({
        fiche_navette_item_id: it.is_dependency ? (it.parent_item_id ?? it.parentItemId ?? it.parent_item) : it.id,
        item_dependency_id: it.is_dependency ? it.id : null,
        amount: rem, // Use full remaining amount for each item
        remaining_amount: rem,
        item_name: it.display_name || it.prestation?.name || 'Unknown Item',
        is_dependency: it.is_dependency || false
      })
    }
  }

  if (bulkPaymentItems.length === 0) {
    toast.add({ severity: 'warn', summary: 'Nothing to allocate', detail: 'No outstanding items to apply payment to', life: 3000 })
    return
  }

  // Clear global payment amount immediately for better UX
  globalPayment.amount = null

  // Send a single bulk payment request (for exact amounts only, no excess)
  const bulkPayload = {
    fiche_navette_id: ficheId.value,
    caisse_session_id: caisseSessionId.value,
    cashier_id: cashierId,
    patient_id: patientId.value,
    payment_method: mapPaymentMethod(globalPayment.method || 'cash'),
    transaction_type: 'bulk_payment',
    total_amount: amount, // Use the actual payment amount
    items: bulkPaymentItems,
    notes: `Global bulk payment for ${bulkPaymentItems.length} items`
  }

  console.log('Sending bulk payment request:', bulkPayload)

  try {
    const res = await axios.post('/api/financial-transactions-bulk-payment', bulkPayload)
    const data = res?.data ?? {}
    
    if (data?.success) {
      const message = `Global payment processed successfully for ${bulkPaymentItems.length} items`
        
      toast.add({
        severity: 'success',
        summary: 'Payment Success',
        detail: message,
        life: 4000
      })
      
      // Refresh all data to show updated amounts
      await loadItems()
    } else {
      toast.add({
        severity: 'error', 
        summary: 'Payment Failed',
        detail: data.message || 'Bulk payment failed',
        life: 4000
      })
      
      // Restore the amount if payment failed
      globalPayment.amount = amount
    }
  } catch (e) {
    console.error('Bulk payment error:', e)
    const msg = e.response?.data?.message ?? 'Une erreur est survenue lors du paiement global'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 })
    
    // Restore the amount if payment failed
    globalPayment.amount = amount
  }
}
const getFicheStatus = (item) => {
  
  return item.status || (item.fiche_navette_item?.fiche_navette?.status) || 'unknown'
}
// Approval modal handlers
const onApprovalRequestSent = (request) => {
  const hasAttachment = request.attachment ? ' with attachment' : ''
  
  toast.add({
    severity: 'info',
    summary: 'Demande Envoyée',
    detail: `Votre demande d'approbation a été envoyée${hasAttachment}. En attente de validation.`,
    life: 5000
  })
  
  // Clear the payment amount for the item
  if (approvalData.itemIndex !== null) {
    const item = items.value[approvalData.itemIndex]
    if (item) {
      item._pay_amount = null
    }
  }
  
  showApprovalModal.value = false
}

const onApprovalModalClose = () => {
  showApprovalModal.value = false
  // Reset approval data
  approvalData.fiche_navette_item_id = null
  approvalData.item_dependency_id = null
  approvalData.amount = 0
  approvalData.method = ''
  approvalData.itemName = ''
  approvalData.itemIndex = null
}

onMounted(async () => {
  await loadItems()
  await load()
})
</script>
<template>
  <div class="tw-bg-gray-100 tw-min-h-screen tw-p-6 tw-text-gray-900">
    <div class="tw-max-w-6xl tw-mx-auto">
      <!-- Payment Header -->
      <PaymentHeader 
        :fiche-id="ficheId"
        :patient-name="fichePatientName"
        :total-outstanding="totalOutstanding"
      />

      <div class="tw-grid lg:tw-grid-cols-2 tw-gap-6 tw-items-start">
        <div class="tw-space-y-6">
          <!-- Prestations Summary -->
          <PrestationsSummary 
            :summary-items="summaryItems"
            @scroll-to-item="scrollToItem"
          />

          <!-- Transactions Overview -->
          <TransactionsOverview
            :filtered-transactions="filteredTransactions"
            :transaction-type-options="transactionTypeOptions"
            v-model:search-query="transactionsSearchQuery"
            v-model:selected-type="selectedTransactionType"
            v-model:selected-date-range="selectedTransactionDateRange"
          />
        </div>

        <div>
          <div class="tw-bg-white tw-rounded-lg tw-p-6 tw-shadow-md">
            <!-- Global Payment -->
            <GlobalPayment
              v-model:amount="globalPayment.amount"
              v-model:method="globalPayment.method"
              :max-amount="totalOutstanding"
              @pay-global="payGlobal"
            />

            <div v-if="loading" class="tw-text-center tw-py-8">
              <ProgressSpinner class="tw-w-10 tw-h-10 tw-text-blue-500" strokeWidth="4" />
            </div>

            <div v-else>
              <div v-if="items.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
                Aucune prestation trouvée pour cette fiche.
              </div>

              <h3 class="tw-text-xl tw-font-semibold tw-mb-4">Détails des Prestations</h3>
              
              <!-- Prestation Cards -->
              <PrestationCard
                v-for="(item, idx) in items"
                :key="item.id"
                :item="item"
                v-model:pay-amount="item._pay_amount"
                v-model:pay-method="item._pay_method"
                :transactions-visible="item._transactionsVisible"
                :user-can-refund="userCanRefund"
                :final-price="getItemFinalPrice(item)"
                :paid-amount="getItemPaidAmount(item)"
                :remaining="itemRemaining(item)"
                :payment-status="getPaymentStatus(item)"
                :is-min-versement-paid="isMinVersementPaid(item)"
                :min-versement-amount="Number(item.prestation?.min_versement_amount || 0)"
                :default-payment-type="item.prestation?.default_payment_type"
                :service-name="item.prestation?.service?.name ?? item.service_name ?? ''"
                :refund-auth-map="refundAuthMap"
                :can-show-refund-button="canShowRefundButton"
                :can-refund="canRefund"
                :can-update="canUpdate"
                :fiche-status="getFicheStatus(item)"
                :authorized-refunds="getAuthorizedRefunds(item)"
                @pay-item="payItem(idx)"
                @toggle-transactions="item._transactionsVisible = !item._transactionsVisible"
                @open-update="openUpdateModal"
                @open-refund="(tx) => openRefundModal(tx, item)"
                @show-overpayment="(data) => handleItemOverpayment(data, idx)"
              />

              <div class="tw-mt-6 tw-flex tw-justify-end">
                <Button
                  label="Retour"
                  icon="pi pi-arrow-left"
                  @click="goBack"
                  class="p-button-secondary"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Modals -->
      <PaymentModals
        v-model:show-overpayment-modal="showOverpaymentModal"
        v-model:show-refund-modal="showRefundModal"
        v-model:show-update-modal="showUpdateModal"
        :overpayment-data="overpaymentData"
        :refund-data="refundData"
        :update-data="updateData"
        :refund-authorization="refundAuthorization"
        :processing-overpayment="processingOverpayment"
        :processing-refund="processingRefund"
        :processing-update="processingUpdate"
        @handle-overpayment="handleOverpayment"
        @cancel-overpayment="cancelOverpayment"
        @update:refund-amount="refundData.amount = $event"
        @update:refund-notes="refundData.notes = $event"
        @close-refund="closeRefundModal"
        @process-refund="processRefund"
        @update:update-amount="updateData.amount = $event"
        @update:update-notes="updateData.notes = $event"
        @close-update="closeUpdateModal"
        @process-update="processUpdate"
        @pay-global-amount="handlePayGlobalAmount"
      />

      <!-- Payment Approval Modal -->
      <PaymentApprovalModal
        v-model:visible="showApprovalModal"
        :payment-data="approvalData"
        @request-sent="onApprovalRequestSent"
        @close="onApprovalModalClose"
      />

      <Toast />
      <ConfirmDialog />
    </div>
  </div>
</template>

