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
        <div class="tw-font-bold tw-text-xl tw-text-gray-900">{{ formatCurrency(finalPrice) }}</div>
        <div class="tw-text-sm tw-text-gray-500">Paid: {{ formatCurrency(paidAmount) }}</div>
        <div class="tw-text-sm" :class="remaining <= 0 ? 'tw-text-green-600' : 'tw-text-red-600'">
          Remaining: {{ formatCurrency(remaining) }}
        </div>
      </div>
    </div>

    <div v-if="remaining > 0" class="tw-flex tw-items-center tw-gap-3 tw-mt-2">
      <div class="tw-flex-1 tw-min-w-[120px]">
        <label :for="`item-amount-${item.id}`" class="tw-block tw-text-xs tw-font-medium tw-text-gray-600 tw-mb-1">
          Amount
        </label>
        <InputNumber
          :id="`item-amount-${item.id}`"
          :modelValue="payAmount"
          @update:modelValue="$emit('update:pay-amount', $event)"
          :max="remaining"
          class="tw-w-full"
          mode="decimal"
          :minFractionDigits="2"
          :maxFractionDigits="2"
          :placeholder="suggestedAmount ? formatCurrency(suggestedAmount) : '0.00'"
        />
        <div v-if="paymentHelperText" class="tw-text-xs tw-text-blue-600 tw-mt-1">
          {{ paymentHelperText }}
        </div>
        <div v-if="suggestedAmount && !payAmount" class="tw-mt-1">
          <Button
            :label="`Pay minimum (${formatCurrency(suggestedAmount)})`"
            @click="$emit('update:pay-amount', suggestedAmount)"
            class="p-button-sm p-button-outlined p-button-info"
            size="small"
          />
        </div>
      </div>
      <div class="tw-min-w-[120px]">
        <label :for="`item-method-${item.id}`" class="tw-block tw-text-xs tw-font-medium tw-text-gray-600 tw-mb-1">
          Method
        </label>
        <select
          :id="`item-method-${item.id}`"
          :value="payMethod"
          @change="$emit('update:pay-method', $event.target.value)"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
        >
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="cheque">Check</option>
        </select>
      </div>
      <Button
        label="Pay"
        icon="pi pi-check"
        @click="$emit('pay-item')"
        :disabled="!canPay"
        class="p-button-primary tw-mt-auto"
      />
    </div>

    <div v-if="item.transactions && item.transactions.length" class="tw-mt-4 tw-border-t tw-border-gray-300 tw-pt-4">
      <div class="tw-font-medium tw-mb-2 tw-text-gray-600 tw-flex tw-justify-between tw-items-center">
        <span>Transactions</span>
        <Button
          :icon="`pi ${transactionsVisible ? 'pi-chevron-up' : 'pi-chevron-down'}`"
          class="p-button-text p-button-sm"
          @click="$emit('toggle-transactions')"
        />
      </div>
      <ul v-if="transactionsVisible" class="tw-list-none tw-p-0 tw-text-sm tw-space-y-2">
        <li 
          v-for="tx in sortedTransactions" 
          :key="tx.id" 
          class="tw-bg-gray-100 tw-rounded-md tw-p-3 tw-flex tw-justify-between tw-items-center"
        >
          <div class="tw-flex-1">
            <div class="tw-font-semibold tw-text-gray-900">{{ formatCurrency(tx.amount ?? tx.total ?? 0) }}</div>
            <div class="tw-text-xs tw-text-gray-500">
              ID: {{ tx.id }} · {{ tx.payment_method ?? tx.method ?? '—' }} ·
              <span :class="getTransactionTypeClass(tx.transaction_type)">
                {{ getTransactionTypeText(tx.transaction_type) }}
              </span>
            </div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-text-xs tw-text-right tw-text-gray-600 tw-mr-2">
              {{ new Date(tx.created_at).toLocaleString() }}
            </div>
            
            <!-- Edit button -->
            <Button
              v-if="tx.transaction_type === 'payment' && canUpdate(tx)"
              icon="pi pi-pencil"
              @click="$emit('open-update', tx)"
              class="p-button-info p-button-sm p-button-rounded p-button-text"
              v-tooltip.top="'Edit payment'"
            />

            <!-- Refund status and button -->
            <div class="tw-flex tw-items-center tw-gap-2" v-if="tx.transaction_type === 'payment'">
              <!-- Authorization status tags -->
              <div v-if="tx.refund_authorization || refundAuthMap?.[item.id]">
                <!-- Show transaction-specific authorization status -->
                <template v-if="tx.refund_authorization">
                  <Tag 
                    v-if="tx.refund_authorization.status === 'pending'"
                    severity="warning" 
                    :value="`Refund Pending (${formatCurrency(tx.refund_authorization.requested_amount)})`"
                  />
                  <Tag 
                    v-else-if="tx.refund_authorization.status === 'rejected'"
                    severity="danger" 
                    value="Refund Rejected"
                  />
                </template>
                <!-- Show global authorization status if no transaction-specific one exists -->
                <template v-else-if="refundAuthMap?.[item.id]">
                  <Tag 
                    v-if="Array.isArray(refundAuthMap[item.id]) && refundAuthMap[item.id][0]?.status === 'pending'"
                    severity="warning" 
                    :value="`Refund Pending (${formatCurrency(refundAuthMap[item.id][0]?.requested_amount)})`"
                  />
                  <Tag 
                    v-else-if="Array.isArray(refundAuthMap[item.id]) && refundAuthMap[item.id][0]?.status === 'rejected'"
                    severity="danger" 
                    value="Refund Rejected"
                  />
                  <Tag 
                    v-else-if="Array.isArray(refundAuthMap[item.id]) && refundAuthMap[item.id][0]?.status === 'approved'"
                    severity="success" 
                    :value="`Refund Approved (${formatCurrency(refundAuthMap[item.id][0]?.requested_amount)})`"
                  />
                  <Tag 
                    v-else-if="Array.isArray(refundAuthMap[item.id]) && refundAuthMap[item.id][0]?.status === 'used'"
                    severity="info" 
                    value="Refund Completed"
                  />
                </template>
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

              <!-- Refund button -->
              <Button
                v-if="canShowRefundButton(tx, item)"
                icon="pi pi-undo"
                @click="$emit('open-refund', { 
                  transaction: tx, 
                  fixedAmount: tx.refund_authorization?.requested_amount ?? null,
                  isAuthorized: false
                })"
                class="p-button-sm p-button-rounded p-button-warning"
                v-tooltip.top="getRefundButtonTooltip(tx, item)"
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
  }
})

const emit = defineEmits([
  'update:pay-amount',
  'update:pay-method',
  'pay-item',
  'toggle-transactions',
  'open-update',
  'open-refund'
])

const { formatCurrency } = useCurrencyFormatter()
const { getTransactionTypeText, getTransactionTypeClass } = useTransactionHelpers()

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

const canUpdate = (tx) => {
  return tx.transaction_type === 'payment'
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

const canShowRefundButton = (tx, item) => {
  // Basic validation
  if (!tx?.id || !item) return false;
  if (tx.transaction_type !== 'payment') return false;

  // Quick check for refund authorization status
  const authStatus = tx.refund_authorization?.status?.toLowerCase();
  if (authStatus !== 'pending') return false;

  // Check if this is the latest payment
  const latestPaymentId = getLatestPaymentId(item);
  if (tx.id !== latestPaymentId) return false;

  // Check for existing refund using direct property access
  const hasRefund = item.transactions?.some(t => 
    t.transaction_type === 'refund' && (
      t.original_transaction_id === tx.id || 
      t.refunded_transaction_id === tx.id
    )
  );

  return !hasRefund;
}
</script>