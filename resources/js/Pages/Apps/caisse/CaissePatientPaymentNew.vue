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

// New: Refund Authorization State
const refundAuthorization = ref(null);
const refundAuthMap = ref({});
const RefoundAmount = ref([]);
// Permission: whether the authenticated user can perform refunds
const userCanRefund = ref(false)

// Summary items for the top table
const summaryItems = computed(() => {
  if (!items.value) return [];
  console.log('Summary Items:', items.value);
  return items.value.map(item => ({
    id: item.id,
    display_name: item.display_name ?? item.prestation?.name ?? item.custom_name ?? 'Prestation',
    final_price: getItemFinalPrice(item),
    paid_amount: getItemPaidAmount(item),
    remaining: itemRemaining(item)
  }));
});

const itemRemaining = (it) => {
  if (!it) return 0
  // Use the helper function that properly handles both dependencies and regular items
  return getItemRemainingAmount(it)
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

const checkGlobalOverpayment = () => {
  const amount = Number(globalPayment.amount)
  const total = totalOutstanding.value
  
  if (amount > total && total > 0) {
    overpaymentData.required = total
    overpaymentData.paid = amount
    overpaymentData.excess = amount - total
    overpaymentData.itemIndex = null
    overpaymentData.isGlobal = true
    showOverpaymentModal.value = true
  }
}

const handleOverpayment = async (action) => {
  processingOverpayment.value = true

  try {
    // prefer dependency target if present -> use parent_item_id as fiche_navette_item_id
    const dep = overpaymentData._dependency ?? null
    const targetFicheItemId = dep ? (dep.parent_item_id ?? dep.parentItemId ?? dep.parent_item ?? null) : (overpaymentData.isGlobal ? items.value[0]?.id : items.value[overpaymentData.itemIndex]?.id)

    const payload = {
      // fiche_navette_item_id must be the parent/root fiche item for dependencies
      fiche_navette_item_id: targetFicheItemId,
      // for dependencies set patient_id to the dependent prestation id so service locates ItemDependency
      dependent_prestation_id: dep ? (dep.dependent_prestation_id ?? dep.prestation_id ?? dep.prestation?.id ?? null) : patientId.value,
      cashier_id: cashier_id.value,
      required_amount: overpaymentData.required,
      paid_amount: overpaymentData.paid,
      payment_method: mapPaymentMethod(
        dep?._pay_method
          ?? (overpaymentData.isGlobal ? globalPayment.method : items.value[overpaymentData.itemIndex]?._pay_method)
      ),
      overpayment_action: action,
      // include dependency record id for clarity (optional, service/controller can use it)
      item_dependency_id: dep ? dep.id : undefined,
      notes: `Overpayment handling - ${action === 'donate' ? 'donated' : 'added to balance'}`
    }

    const res = await axios.post('/api/financial-transactions/handle-overpayment', payload)
    const data = res?.data ?? {}

    if (data?.success) {
      const actionText = action === 'donate' ? 'don effectué' : 'crédit ajouté au compte patient'
      toast.add({
        severity: 'success',
        summary: 'Paiement traité',
        detail: `Paiement enregistré et ${actionText}`,
        life: 4000
      })

      // clear the correct input: dependency -> its _pay_amount, else global/item
      if (overpaymentData.isGlobal) {
        globalPayment.amount = null
      } else if (overpaymentData._dependency) {
        overpaymentData._dependency._pay_amount = null
      } else if (typeof overpaymentData.itemIndex === 'number') {
        items.value[overpaymentData.itemIndex]._pay_amount = null
      }

      await loadItems()
    } else {
      toast.add({ severity: 'error', summary: 'Erreur', detail: data.message || 'Processing failed', life: 4000 })
    }
  } catch (e) {
    console.error('Overpayment handling error', e)
    const msg = e.response?.data?.message ?? 'Failed to process overpayment'
    toast.add({ severity: 'error', summary: 'Erreur', detail: msg, life: 4000 })
  } finally {
    processingOverpayment.value = false
    showOverpaymentModal.value = false
    // reset dependency pointer to avoid stale reference
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
  if (!transaction) return false

  if (transaction.transaction_type !== 'payment') return false
  const amount = Number(transaction.amount ?? transaction.total ?? 0)
  if (amount <= 0) return false

  const item = transaction.fiche_navette_item ?? transaction.ficheNavetteItem ?? {}
  if (item && hasExistingRefund(transaction, item)) return false
  const ficheStatus = item?.fiche_navette?.status ?? item?.status ?? null

  if (String(ficheStatus) === 'pending') return true

  const itemId = item?.id ?? transaction.fiche_navette_item_id ?? transaction.ficheNavetteItem?.id
  if (!itemId) return false

  const auth = transaction?.refund_authorization ?? refundAuthMap.value[itemId] ?? null
  if (!auth) return false

  const status = String((auth.status ?? auth.status_text ?? '')).toLowerCase()
  if (['used', 'approved'].includes(status)) return false

  const requested = Number(auth.requested_amount ?? auth.requestedAmount ?? 0)
  if (requested > 0) return true

  return false
}

const canShowRefundButton = (transaction, item) => {
  if (!canRefund(transaction)) return false;
  if (!item) return false;

  if (hasExistingRefund(transaction, item)) return false;

  const txAuth = transaction?.refund_authorization ?? refundAuthMap.value[item.id];
  if (txAuth) {
    const s = String((txAuth.status ?? txAuth.status_text ?? '')).toLowerCase()
    if (['used', 'approved'].includes(s)) return false
    return true
  }

  const ficheStatus = item.fiche_navette?.status ?? item.status ?? null;
  if (['pending', 'confirmed'].includes(String(ficheStatus))) return true;

  return false;
}

const openRefundModal = async (transaction, item) => {
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
  return transaction.transaction_type === 'payment' && transaction.id === Math.max(...items.value.flatMap(it => it.transactions.map(tx => tx.id)).filter(id => id));
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
  
  try {
    const payload = {
      amount,
      notes: notes || `Correction de paiement de ${formatCurrency(transaction.amount)} vers ${formatCurrency(amount)}`,
      payment_method: transaction.payment_method
    }

    const res = await axios.put(`/api/financial-transactions/${transaction.id}`, payload)
    const data = res?.data ?? {}
    
    if (data?.success) {
      toast.add({
        severity: 'success',
        summary: 'Mise à jour réussie',
        detail: `Le paiement #${transaction.id} a été mis à jour`,
        life: 4000
      })
      closeUpdateModal()
      await loadItems()
    } else {
      toast.add({ severity: 'error', summary: 'Erreur', detail: data.message || 'Échec de la mise à jour', life: 4000 })
    }
  } catch (e) {
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
    
    allTransactions.value = rawTransactions;

    const txMap = rawTransactions.reduce((acc, tx) => {
      const key = tx.fiche_navette_item_id ?? tx.ficheNavetteItem?.id ?? null;
      if (key) {
        if (!acc[key]) acc[key] = [];
        acc[key].push(tx);
      }
      return acc;
    }, {});

    // Flatten items + dependencies so dependencies appear as full items in the list
    const flat = [];
    rawItems.forEach(it => {
      flat.push({
        ...it,
        _pay_amount: null,
        _pay_method: 'cash',
        _transactionsVisible: false,
        transactions: txMap[it.id] ?? [],
        parent_item_id: null,
        is_dependency: false
      });

      (it.dependencies ?? []).forEach(dep => {
        flat.push({
          display_name: dep.display_name ?? dep.dependencyPrestation?.name ?? dep.custom_name ?? 'Dépendance'
        });
      });
    });

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
  
  if (!amount || amount <= 0) {
    toast.add({ severity: 'warn', summary: 'Invalid amount', detail: 'The entered amount is invalid', life: 3000 })
    return
  }

  if (amount > itemRemaining(it)) {
    checkItemOverpayment(it, idx)
    return
  }

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

  const payload = {
    fiche_navette_id: ficheId.value,
    fiche_navette_item_id: it.id,
    caisse_session_id: caisseSessionId.value,
    cashier_id: cashierId,
    patient_id:patient_id,
    amount: amount,
    transaction_type: 'payment',
    payment_method: mapPaymentMethod(it._pay_method || globalPayment.method || 'cash'),
    notes: `Payment for fiche item ${it.id}`
  }

  try {
    const res = await axios.post('/api/financial-transactions', payload)
    const data = res?.data ?? {}
    if (data?.success ?? (data?.data ? true : false)) {
      await loadItems()
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: data.message || 'Payment failed', life: 4000 })
    }
  } catch (e) {
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

  const cashierId = cashier_id?.value ?? null
  if (!cashierId) {
    toast.add({ severity: 'error', summary: 'Cashier missing', detail: 'cashier_id is required', life: 4000 })
    return
  }

  const payload = {
    fiche_navette_id: ficheId.value,
    fiche_navette_item_id: parentItem.id,
    item_dependency_id: dep.id,
    caisse_session_id: caisseSessionId.value,
    cashier_id: cashierId,
    dependent_prestation_id: dep.dependent_prestation_id ?? dep.prestation_id ?? dep.prestation?.id ?? null,
    amount: amount,
    transaction_type: 'payment',
    payment_method: mapPaymentMethod(dep._pay_method || globalPayment.method || 'cash'),
    notes: `Payment for dependency ${dep.id} under fiche item ${parentItem.id}`
  }

  try {
    const res = await axios.post('/api/financial-transactions', payload)
    const data = res?.data ?? {}
    if (data?.success ?? (data?.data ? true : false)) {
      await loadItems()
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: data.message || 'Payment failed', life: 4000 })
    }
  } catch (e) {
    console.error('Dependency payment error', e)
    const msg = e.response?.data?.message ?? 'Dependency payment failed'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 })
  }
}

const payGlobal = async () => {
  let amount = Number(globalPayment.amount)
  if (!amount || amount <= 0) {
    toast.add({ severity: 'warn', summary: 'Invalid amount', detail: 'Please enter an amount', life: 3000 })
    return
  }

  if (amount > totalOutstanding.value) {
    checkGlobalOverpayment()
    return
  }

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

  const allocation = []
  let remainingToApply = amount
  for (const it of items.value) {
    const rem = itemRemaining(it)
    if (rem > 0 && remainingToApply > 0) {
      const toApply = Math.min(rem, remainingToApply)
      allocation.push({
        fiche_navette_item_id: it.id,
        amount: toApply,
        item_dependency_id: it.is_dependency ? it.id : undefined
      })
      remainingToApply -= toApply
    }
  }

  const payload = {
    fiche_navette_id: ficheId.value,
    caisse_session_id: caisseSessionId.value,
    cashier_id: cashierId,
    patient_id: patient_id,
    total_amount: amount,
    transaction_type: 'payment',
    payment_method: mapPaymentMethod(globalPayment.method || 'cash'),
    notes: `Global payment of ${amount}`,
    allocation: allocation
  }

  try {
    const res = await axios.post('/api/financial-transactions/global', payload)
    const data = res?.data ?? {}
    if (data?.success ?? (data?.data ? true : false)) {
      globalPayment.amount = null
      await loadItems()
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: data.message || 'Global payment failed', life: 4000 })
    }
  } catch (e) {
    console.error('Global payment error', e)
    const msg = e.response?.data?.message ?? 'Global payment failed'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 })
  }
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
                @pay-item="payItem(idx)"
                @toggle-transactions="item._transactionsVisible = !item._transactionsVisible"
                @open-update="openUpdateModal"
                @open-refund="(tx) => openRefundModal(tx, item)"
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
        :show-overpayment-modal="showOverpaymentModal"
        :show-refund-modal="showRefundModal"
        :show-update-modal="showUpdateModal"
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
      />

      <Toast />
      <ConfirmDialog />
    </div>
  </div>
</template>

