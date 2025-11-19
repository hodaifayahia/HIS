<template>
  <div
    :id="`prestation-card-${item.id}`"
    class="tw-border tw-border-gray-300 tw-p-4 tw-rounded-md tw-mb-3"
    :class="cardClass"
  >
    <div v-if="item.package && Array.isArray(item.package.prestations) && item.package.prestations.length" class="tw-mb-2">
      <small class="tw-text-xs tw-text-gray-500">Package content:</small>
      <ul class="tw-list-inside tw-list-disc tw-text-xs tw-text-gray-600 tw-mt-1">
        <li v-for="p in item.package.prestations" :key="p.id" class="tw-mb-0.5">
          {{ p.name }} <span class="tw-text-gray-400">· {{ formatCurrency(Number(p.public_price ?? p.price ?? 0)) }}</span>
        </li>
      </ul>
    </div>

    <!-- Rule 2: Top-level refund for non-pending/confirmed status with authorization -->
    <div v-if="shouldShowTopLevelRefund" class="tw-mb-4 tw-border tw-border-orange-300 tw-bg-orange-50 tw-p-3 tw-rounded-md">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div class="tw-flex-1">
          <div class="tw-font-medium tw-text-orange-800">Authorized Refund Available</div>
          <div class="tw-text-sm tw-text-orange-600">
            Amount: {{ formatCurrency(authorizedRefundAmount) }}
          </div>
          <div class="tw-text-xs tw-text-orange-500 tw-mt-1">
            {{ authorizedRefund?.notes || 'No additional notes' }}
          </div>
        </div>
        <div class="tw-flex tw-items-center tw-gap-2">
          <Button
            icon="pi pi-undo"
            label="Process Refund"
            @click="$emit('open-refund', { 
              transaction: authorizedRefund?.transaction, 
              fixedAmount: authorizedRefundAmount,
              isAuthorized: true,
              authorizationId: authorizedRefund?.id
            })"
            class="p-button-sm p-button-warning"
          />
        </div>
      </div>
    </div>

    <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
      <div class="tw-flex-1">
        <div class="tw-font-semibold tw-text-lg">{{ displayName }}</div>
        <div class="tw-text-sm tw-text-gray-500">{{ serviceName }}</div>
        
        <div class="tw-flex tw-gap-3 tw-mt-2">
          <div v-if="defaultPaymentType" class="tw-text-xs tw-bg-blue-100 tw-text-blue-800 tw-px-2 tw-py-1 tw-rounded">
            Type: {{ defaultPaymentType }}
          </div>
          <div 
            class="tw-text-xs tw-px-2 tw-py-1 tw-rounded tw-font-medium"
            :class="{
              'tw-bg-green-100 tw-text-green-800': paymentStatus.color === 'green',
              'tw-bg-orange-100 tw-text-orange-800': paymentStatus.color === 'orange',
              'tw-bg-red-100 tw-text-red-800': paymentStatus.color === 'red'
            }"
          >
            {{ paymentStatus.text }}
          </div>
        </div>
        
        <div v-if="minVersementAmount > 0" class="tw-mt-2 tw-text-xs tw-text-gray-600">
          <div class="tw-flex tw-items-center tw-gap-2">
            <span>Minimum payment:</span>
            <span class="tw-font-medium">{{ formatCurrency(minVersementAmount) }}</span>
            <span 
              v-if="isMinVersementPaid"
              class="tw-text-green-600 tw-font-medium"
            >
              ✓ Visa granted
            </span>
            <span 
              v-else
              class="tw-text-orange-600"
            >
              ({{ formatCurrency(Math.max(0, minVersementAmount - paidAmount)) }} remaining for visa)
            </span>
          </div>
        </div>
      </div>
      <div class="tw-text-right">
        <div class="tw-text-sm tw-text-gray-600">Price: {{ formatCurrency(finalPrice) }}</div>
        <div class="tw-text-sm tw-font-semibold" :class="remaining <= 0 ? 'tw-text-green-600' : 'tw-text-red-600'">
          {{ remaining <= 0 ? 'Paid' : `Remaining: ${formatCurrency(remaining)}` }}
        </div>
        <div v-if="paidAmount > 0" class="tw-text-xs tw-text-gray-500">
          Paid: {{ formatCurrency(paidAmount) }}
        </div>
      </div>
    </div>

    <div v-if="remaining > 0" class="tw-flex tw-items-center tw-gap-3 tw-mt-2">
      <div class="tw-flex-1 tw-min-w-[120px]">
        <InputNumber
          :id="`item-pay-${item.id}`"
          :model-value="payAmount"
          @update:model-value="$emit('update:pay-amount', $event)"
          mode="currency"
          currency="MAD"
          locale="en-US"
          :min="0"
          :max="remaining"
          :step="10"
          placeholder="Amount"
          :class="{'p-invalid': !canPay && payAmount}"
          class="tw-w-full"
        />
        <div v-if="paymentHelperText" class="tw-text-xs tw-text-blue-600 tw-mt-1">
          {{ paymentHelperText }}
        </div>
        <div v-if="suggestedAmount && !payAmount" class="tw-mt-1">
          <button 
            @click="$emit('update:pay-amount', suggestedAmount)"
            class="tw-text-xs tw-text-blue-600 hover:tw-text-blue-800 tw-underline"
          >
            Pay {{ formatCurrency(suggestedAmount) }}
          </button>
        </div>
      </div>
      <div class="tw-min-w-[120px]">
        <label :for="`item-method-${item.id}`" class="tw-block tw-text-xs tw-font-medium tw-text-gray-600 tw-mb-1">
          Payment Method
        </label>
        <select
          :id="`item-method-${item.id}`"
          :value="payMethod"
          @change="$emit('update:pay-method', $event.target.value)"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
        >
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="cheque">Cheque</option>
          <option value="transfer">Transfer</option>
        </select>
      </div>
      <Button
        label="Pay"
        icon="pi pi-check"
        @click="handlePayment"
        :disabled="!canPay"
        class="p-button-primary tw-mt-auto"
      />
    </div>

    <div v-if="item.transactions && item.transactions.length" class="tw-mt-4 tw-border-t tw-border-gray-300 tw-pt-4">
      <div class="tw-font-medium tw-mb-2 tw-text-gray-600 tw-flex tw-justify-between tw-items-center">
        <span>Transactions</span>
        <Button
          :icon="transactionsVisible ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
          @click="$emit('toggle-transactions')"
          class="p-button-text p-button-sm"
        />
      </div>
      <ul v-if="transactionsVisible" class="tw-list-none tw-p-0 tw-text-sm tw-space-y-2">
        <li 
          v-for="tx in sortedTransactions" 
          :key="tx.id" 
          class="tw-bg-gray-100 tw-rounded-md tw-p-3 tw-flex tw-justify-between tw-items-center"
          :class="{
            'tw-bg-green-100': tx.transaction_type === 'payment',
            'tw-bg-red-100': tx.transaction_type === 'refund'
          }"
        >
          <div class="tw-flex-1">
            <div class="tw-flex tw-items-center tw-gap-2">
              <span 
                :class="getTransactionTypeClass(tx.transaction_type)"
                class="tw-px-2 tw-py-1 tw-rounded tw-text-xs tw-font-medium"
              >
                {{ getTransactionTypeText(tx.transaction_type) }}
              </span>
              <span class="tw-font-medium">{{ formatCurrency(tx.amount) }}</span>
              <span class="tw-text-gray-500 tw-text-xs">{{ tx.payment_method }}</span>
            </div>
            <div class="tw-text-xs tw-text-gray-500 tw-mt-1">
              {{ new Date(tx.created_at).toLocaleString() }}
            </div>
            <div v-if="tx.notes" class="tw-text-xs tw-text-gray-600 tw-mt-1">
              {{ tx.notes }}
            </div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-flex tw-items-center tw-gap-2" v-if="tx.transaction_type === 'payment'">
              <div v-if="tx.refund_authorization || refundAuthMap?.[item.id]">
                <small class="tw-text-orange-600">Auth pending</small>
              </div>

              <!-- Check for existing refund -->
              <Tag 
                v-if="item.transactions?.some(t => 
                  t.transaction_type === 'refund' && 
                  (t.original_transaction_id === tx.id || t.refunded_transaction_id === tx.id)
                )"
                severity="info"
                :value="`Refunded: ${formatCurrency(
                  item.transactions.find(t => 
                    t.transaction_type === 'refund' && 
                    (t.original_transaction_id === tx.id || t.refunded_transaction_id === tx.id)
                  )?.amount ?? 0
                )}`"
              />

              <!-- Rule 1 & 2: Refund button with new logic -->
              <Button
                v-if="userCanRefund && shouldShowRefundButton(tx, item)"
                icon="pi pi-undo"
                @click="$emit('open-refund', { 
                  transaction: tx, 
                  fixedAmount: getRefundFixedAmount(tx, item),
                  isAuthorized: isRefundAuthorized(tx, item),
                  allowFlexibleAmount: canUseFlexibleRefund(tx, item)
                })"
                class="p-button-sm p-button-rounded p-button-warning"
                v-tooltip.top="getRefundButtonTooltip(tx, item)"
              />
            </div>

            <div v-if="tx.transaction_type === 'payment' && canUpdate(tx)" class="tw-flex tw-items-center tw-gap-2">
              <Button
                icon="pi pi-pencil"
                @click="$emit('open-update', tx)"
                class="p-button-sm p-button-rounded p-button-secondary"
                v-tooltip.top="'Update transaction'"
              />
            </div>
          </div>
        </li>
      </ul>
    </div>

    <div v-if="item.dependencies && item.dependencies.length && !item.is_dependency" class="tw-mt-4 tw-border-t tw-border-gray-300 tw-pt-4">
      <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-2">Dependencies</div>
      <ul class="tw-list-disc tw-pl-5 tw-text-sm tw-text-gray-600">
        <li v-for="dep in item.dependencies" :key="dep.id" class="tw-mb-1">
          <div class="tw-flex tw-justify-between tw-items-center">
            <span>{{ dep.display_name ?? dep.dependencyPrestation?.name ?? dep.custom_name ?? 'Dependency' }}</span>
            <div class="tw-text-right">
              <div class="tw-text-xs tw-text-gray-500">
                Price: {{ formatCurrency(dep.final_price ?? dep.base_price ?? dep.dependencyPrestation?.public_price ?? 0) }}
              </div>
              <div class="tw-text-xs" :class="(dep.final_price ?? dep.base_price ?? 0) - (dep.paid_amount ?? 0) <= 0 ? 'tw-text-green-600' : 'tw-text-red-600'">
                Remaining: {{ formatCurrency(Math.max(0, (dep.final_price ?? dep.base_price ?? dep.dependencyPrestation?.public_price ?? 0) - (dep.paid_amount ?? 0))) }}
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { computed, ref, watch } from 'vue'
import { useCurrencyFormatter } from '@/composables/useCurrencyFormatter'
import { useTransactionHelpers } from '@/composables/useTransactionHelpers'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  payAmount: {
    type: Number,
    default: null
  },
  payMethod: {
    type: String,
    default: 'cash'
  },
  transactionsVisible: {
    type: Boolean,
    default: false
  },
  userCanRefund: {
    type: Boolean,
    default: false
  },
  finalPrice: {
    type: Number,
    required: true
  },
  paidAmount: {
    type: Number,
    required: true
  },
  remaining: {
    type: Number,
    required: true
  },
  paymentStatus: {
    type: Object,
    required: true
  },
  isMinVersementPaid: {
    type: Boolean,
    default: false
  },
  minVersementAmount: {
    type: Number,
    default: 0
  },
  defaultPaymentType: {
    type: String,
    default: null
  },
  refundAuthMap: {
    type: Object,
    default: () => ({})
  },
  canShowRefundButton: {
    type: Function,
    required: true
  },
  canRefund: {
    type: Function,
    required: true
  },
  canUpdate: {
    type: Function,
    required: true
  },
  ficheStatus: {
    type: String,
    default: ''
  },
  authorizedRefunds: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits([
  'update:pay-amount',
  'update:pay-method',
  'pay-item',
  'toggle-transactions',
  'open-update',
  'open-refund',
  'show-overpayment'
])

const { formatCurrency } = useCurrencyFormatter()
const { getTransactionTypeText, getTransactionTypeClass } = useTransactionHelpers()

// Handle payment with overpayment check
const handlePayment = () => {
  const amount = Number(props.payAmount)
  const remaining = props.remaining
  
  if (amount > remaining && remaining > 0) {
    // Show overpayment modal
    emit('show-overpayment', {
      required: remaining,
      paid: amount,
      excess: amount - remaining,
      item: props.item
    })
  } else {
    // Normal payment
    emit('pay-item')
  }
}

const displayName = computed(() => {
  return props.item.display_name ?? 
         props.item.prestation?.name ?? 
         props.item.custom_name ?? 
         'Prestation'
})

const serviceName = computed(() => {
  return props.item.prestation?.service?.name ?? 
         props.item.service_name ?? 
         ''
})

const cardClass = computed(() => {
  if (props.paymentStatus.status === 'paid') {
    return 'tw-bg-green-50 tw-border-green-300'
  }
  if (props.paymentStatus.status === 'visa_granted') {
    return 'tw-bg-green-50 tw-border-green-300'
  }
  if (props.paymentStatus.status === 'partial') {
    return 'tw-bg-orange-50 tw-border-orange-300'
  }
  return 'tw-bg-red-50 tw-border-red-300'
})

const canPay = computed(() => {
  const amount = Number(props.payAmount)
  return amount > 0
})

// Pre-sort transactions once and cache the result
const sortedTransactions = computed(() => {
  if (!props.item?.transactions) return [];
  
  // Create a Map for quick refund lookups
  const refundMap = new Map();
  props.item.transactions.forEach(tx => {
    if (tx.transaction_type === 'refund') {
      refundMap.set(tx.original_transaction_id || tx.refunded_transaction_id, tx);
    }
  });

  // Sort transactions with refund info
  return [...props.item.transactions].sort((a, b) => {
    // Most recent first
    if (b.id !== a.id) return b.id - a.id;
    // If same ID, put refunds after payments
    return a.transaction_type === 'refund' ? 1 : -1;
  });
})

const getRefundButtonTooltip = (tx, item) => {
  if (!tx.refund_authorization) return 'No refund authorization';

  const status = tx.refund_authorization.status?.toLowerCase();
  const amount = tx.refund_authorization.requested_amount;

  if (status === 'pending') {
    return `Process pending refund (${formatCurrency(amount)})`;
  }

  return 'Refund not available';
}

// Memoize the latest payment ID for each item
const itemLatestPaymentMap = new Map();

const getLatestPaymentId = (item) => {
  if (!item?.id) return null;
  
  if (!itemLatestPaymentMap.has(item.id)) {
    const latestPayment = item.transactions
      ?.filter(t => t.transaction_type === 'payment')
      ?.sort((a, b) => b.id - a.id)[0];
    itemLatestPaymentMap.set(item.id, latestPayment?.id);
  }
  
  return itemLatestPaymentMap.get(item.id);
};

// Check if a refund already exists for this specific transaction
const hasExistingRefund = (transaction, item) => {
  if (!transaction || !item || !Array.isArray(item.transactions)) return false;

  return item.transactions.some(t => {
    return t.transaction_type === 'refund' && 
           (t.original_transaction_id === transaction.id || 
            t.refunded_transaction_id === transaction.id);
  });
}

// Computed property for suggested payment amount (minimum payment or remaining amount)
const suggestedAmount = computed(() => {
  if (props.minVersementAmount > 0 && !props.isMinVersementPaid) {
    const remainingForVisa = Math.max(0, props.minVersementAmount - props.paidAmount)
    return Math.min(remainingForVisa, props.remaining)
  }
  return Math.min(1000, props.remaining) // Suggest reasonable amount up to 1000
})

// Helper text for payment guidance
const paymentHelperText = computed(() => {
  if (props.minVersementAmount > 0 && !props.isMinVersementPaid) {
    const remainingForVisa = Math.max(0, props.minVersementAmount - props.paidAmount)
    if (remainingForVisa > 0) {
      return `Pay ${formatCurrency(remainingForVisa)} to get visa approval`
    }
  }
  return null
})

// ===== NEW REFUND LOGIC BASED ON FICHE STATUS RULES =====

// Refund logic based on fiche status rules
const isFichePendingOrConfirmed = computed(() => {
  const status = String(props.ficheStatus || '').toLowerCase()
  return ['pending', 'confirmed'].includes(status)
})

// Rule 2: Check for authorized refund at top level (non-pending/confirmed status)
const shouldShowTopLevelRefund = computed(() => {
  if (isFichePendingOrConfirmed.value) return false
  return !!authorizedRefund.value
})

const authorizedRefund = computed(() => {
  if (!props.authorizedRefunds || !Array.isArray(props.authorizedRefunds)) return null
  
  return props.authorizedRefunds.find(auth => {
    const authItemId = auth.fiche_navette_item_id || auth.item_id
    return authItemId === props.item.id && auth.status === 'approved'
  })
})

const authorizedRefundAmount = computed(() => {
  return authorizedRefund.value?.requested_amount || 0
})

// Determine if refund should be shown for a transaction
const shouldShowRefundButton = (transaction, item) => {
  if (!transaction || transaction.transaction_type !== 'payment') return false
  if (hasExistingRefund(transaction, item)) return false
  
  // Rule 1: For pending/confirmed status, show refund on latest transaction
  if (isFichePendingOrConfirmed.value) {
    return transaction.id === getLatestPaymentId(item)
  }
  
  // Rule 2: For other statuses, only show if there's authorization
  return isRefundAuthorized(transaction, item)
}

// Check if refund is authorized for specific transaction
const isRefundAuthorized = (transaction, item) => {
  if (!transaction || !item) return false
  
  // Check transaction-specific authorization
  if (transaction.refund_authorization) {
    const status = String(transaction.refund_authorization.status || '').toLowerCase()
    return ['approved', 'pending'].includes(status)
  }
  
  // Check item-level authorization
  return !!authorizedRefund.value
}

// Get fixed amount for refund (Rule 2) or null for flexible (Rule 1)
const getRefundFixedAmount = (transaction, item) => {
  // Rule 1: Pending/confirmed status allows flexible amount
  if (isFichePendingOrConfirmed.value) return null
  
  // Rule 2: Other statuses use fixed authorized amount
  if (transaction.refund_authorization?.requested_amount) {
    return transaction.refund_authorization.requested_amount
  }
  
  return authorizedRefundAmount.value || null
}

// Check if flexible refund amount is allowed
const canUseFlexibleRefund = (transaction, item) => {
  return isFichePendingOrConfirmed.value
}
</script>
