<template>
  <div
    :id="`prestation-card-${item.id}`"
    class="tw-border tw-border-gray-200 tw-shadow-sm tw-transition-all tw-duration-300 hover:tw-shadow-lg tw-p-6 tw-rounded-xl tw-mb-4 tw-bg-white"
    :class="cardClass"
  >
    <!-- Package Content Section -->
    <div v-if="item.package && Array.isArray(item.package.prestations) && item.package.prestations.length" class="tw-mb-6">
      <div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
        <i class="pi pi-box tw-text-blue-500 tw-text-sm"></i>
        <small class="tw-text-sm tw-font-semibold tw-uppercase tw-text-gray-700 tw-tracking-wide">Package Content</small>
      </div>
      <ul class="tw-list-none tw-p-0 tw-bg-blue-50 tw-rounded-lg tw-p-4 tw-space-y-2">
        <li v-for="p in item.package.prestations" :key="p.id" class="tw-flex tw-justify-between tw-items-center tw-py-1">
          <span class="tw-text-sm tw-text-gray-700">{{ p.name }}</span>
          <span class="tw-text-sm tw-font-medium tw-text-blue-600">{{ formatCurrency(Number(p.public_price ?? p.price ?? 0)) }}</span>
        </li>
      </ul>
    </div>

    <!-- Top-level Refund Alert -->
    <div v-if="shouldShowTopLevelRefund" class="tw-mb-6 tw-border tw-border-orange-200 tw-bg-gradient-to-r tw-from-orange-50 tw-to-orange-100 tw-p-4 tw-rounded-xl tw-shadow-sm">
      <div class="tw-flex tw-justify-between tw-items-start tw-gap-4">
        <div class="tw-flex-1">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <i class="pi pi-exclamation-triangle tw-text-orange-600 tw-text-lg"></i>
            <div class="tw-font-semibold tw-text-orange-800">Authorized Refund Available</div>
          </div>
          <div class="tw-text-sm tw-text-orange-700 tw-mb-1">
            <span class="tw-font-medium">Amount:</span> {{ formatCurrency(authorizedRefundAmount) }}
          </div>
          <div class="tw-text-xs tw-text-orange-600 tw-bg-orange-200 tw-px-2 tw-py-1 tw-rounded-md tw-inline-block">
            {{ authorizedRefund?.notes || 'No additional notes' }}
          </div>
        </div>
        <div class="tw-flex tw-items-center">
          <Button
            icon="pi pi-undo"
            label="Process Refund"
            @click="$emit('open-refund', { 
              transaction: authorizedRefund?.transaction, 
              fixedAmount: authorizedRefundAmount,
              isAuthorized: true,
              authorizationId: authorizedRefund?.id
            })"
            class="p-button-sm p-button-warning tw-transition-all tw-duration-200 tw-shadow-md hover:tw-shadow-lg"
          />
        </div>
      </div>
    </div>

    <!-- Main Card Content -->
    <div class="tw-flex tw-justify-between tw-items-start tw-mb-6">
      <div class="tw-flex-1 tw-pr-4">
        <div class="tw-font-bold tw-text-xl tw-text-gray-800 tw-mb-1">{{ displayName }}</div>
        <div class="tw-text-sm tw-text-gray-500 tw-mb-3">{{ serviceName }}</div>
        
        <div class="tw-flex tw-flex-wrap tw-gap-2 tw-mb-3">
          <div v-if="defaultPaymentType" class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-bg-blue-100 tw-text-blue-800 tw-px-3 tw-py-1.5 tw-rounded-full tw-font-medium">
            <i class="pi pi-credit-card tw-text-xs"></i>
            <span>{{ defaultPaymentType }}</span>
          </div>
          <div 
            class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-px-3 tw-py-1.5 tw-rounded-full tw-font-semibold tw-shadow-sm"
            :class="{
              'tw-bg-green-100 tw-text-green-800 tw-border tw-border-green-200': paymentStatus.color === 'green',
              'tw-bg-orange-100 tw-text-orange-800 tw-border tw-border-orange-200': paymentStatus.color === 'orange',
              'tw-bg-red-100 tw-text-red-800 tw-border tw-border-red-200': paymentStatus.color === 'red'
            }"
          >
            <i :class="{
              'pi pi-check-circle': paymentStatus.color === 'green',
              'pi pi-exclamation-triangle': paymentStatus.color === 'orange',
              'pi pi-times-circle': paymentStatus.color === 'red'
            }" class="tw-text-xs"></i>
            <span>{{ paymentStatus.text }}</span>
          </div>
          <!-- Doctor Tags -->
          <div 
            v-for="doctor in doctorTags" 
            :key="doctor.id"
            class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-bg-purple-100 tw-text-purple-800 tw-px-3 tw-py-1.5 tw-rounded-full tw-font-medium tw-border tw-border-purple-200"
          >
            <i class="pi pi-user-md tw-text-xs"></i>
            <span>{{ doctor.name }}</span>
          </div>
        </div>

        <div v-if="minVersementAmount > 0" class="tw-bg-gray-50 tw-rounded-lg tw-p-3 tw-border tw-border-gray-200">
          <div class="tw-flex tw-items-center tw-gap-3 tw-text-sm">
            <i class="pi pi-info-circle tw-text-blue-500"></i>
            <span class="tw-text-gray-600">Minimum payment:</span>
            <span class="tw-font-bold tw-text-gray-800">{{ formatCurrency(minVersementAmount) }}</span>
            <span 
              v-if="isMinVersementPaid"
              class="tw-inline-flex tw-items-center tw-gap-1 tw-text-green-700 tw-font-semibold tw-bg-green-100 tw-px-2 tw-py-1 tw-rounded-full tw-text-xs"
            >
              <i class="pi pi-check tw-text-xs"></i>
              Visa granted
            </span>
            <span 
              v-else
              class="tw-text-orange-600 tw-font-medium"
            >
              ({{ formatCurrency(Math.max(0, minVersementAmount - paidAmount)) }} remaining for visa)
            </span>
          </div>
        </div>
      </div>

      <!-- Price Summary -->
      <div class="tw-text-right tw-bg-gray-50 tw-rounded-lg tw-p-4 tw-min-w-[180px] tw-border tw-border-gray-200">
        <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Price</div>
        <div class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-2">{{ formatCurrency(finalPrice) }}</div>
        <div class="tw-text-sm tw-font-semibold tw-mb-1" :class="remaining <= 0 ? 'tw-text-green-600' : 'tw-text-red-600'">
          {{ remaining <= 0 ? '✓ Fully Paid' : `Remaining: ${formatCurrency(remaining)}` }}
        </div>
        <div v-if="paidAmount > 0" class="tw-text-xs tw-text-gray-500 tw-bg-green-50 tw-px-2 tw-py-1 tw-rounded">
          Paid: {{ formatCurrency(paidAmount) }}
        </div>
      </div>
    </div>

    <!-- Payment Section -->
    <div v-if="remaining > 0" class="tw-relative tw-overflow-hidden tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-border tw-border-blue-200 tw-rounded-2xl tw-p-6 tw-mb-6 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300">
      <!-- Background Pattern -->
      <div class="tw-absolute tw-inset-0 tw-opacity-5">
        <div class="tw-absolute tw-top-0 tw-right-0 tw-w-32 tw-h-32 tw-bg-gradient-to-bl tw-from-blue-400 tw-to-purple-400 tw-rounded-full tw-blur-3xl"></div>
        <div class="tw-absolute tw-bottom-0 tw-left-0 tw-w-24 tw-h-24 tw-bg-gradient-to-tr tw-from-indigo-400 tw-to-blue-400 tw-rounded-full tw-blur-2xl"></div>
      </div>

      <!-- Header -->
      <div class="tw-relative tw-z-10 tw-flex tw-items-center tw-gap-3 tw-mb-6">
        <div class="tw-p-3 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-shadow-lg">
          <i class="pi pi-money-bill tw-text-white tw-text-xl"></i>
        </div>
        <div>
          <h3 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-1">Process Payment</h3>
          <p class="tw-text-sm tw-text-gray-600">Complete your payment securely</p>
        </div>
      </div>

      <!-- Payment Form -->
      <div class="tw-relative tw-z-10 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-12 tw-gap-4 md:tw-gap-6">
        <!-- Amount Input -->
        <div class="md:tw-col-span-1 lg:tw-col-span-12 tw-space-y-3">
          <label :for="`item-pay-${item.id}`" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
            <i class="pi pi-dollar tw-text-blue-500"></i>
            Payment Amount
            <span class="tw-text-red-500">*</span>
          </label>

          <div class="tw-relative">
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
              placeholder="Enter payment amount"
              :class="{
                'p-invalid': !canPay && payAmount,
                'tw-ring-2 tw-ring-red-200': !canPay && payAmount
              }"
              class="tw-w-full tw-text-lg tw-font-medium tw-transition-all tw-duration-200 focus:tw-ring-2 focus:tw-ring-blue-400 focus:tw-border-blue-400"
            />
            <div class="tw-absolute tw-inset-y-0 tw-right-0 tw-flex tw-items-center tw-pr-3 tw-pointer-events-none">
              <span class="tw-text-sm tw-text-gray-400 tw-font-medium">MAD</span>
            </div>
          </div>

          <!-- Helper Messages -->
          <div v-if="paymentHelperText" class="tw-flex  tw-items-start tw-gap-2 tw-p-3 tw-bg-blue-100 tw-border tw-border-blue-200 tw-rounded-lg tw-text-sm">
            <i class="pi pi-info-circle tw-text-blue-600 tw-mt-0.5"></i>
            <span class="tw-text-blue-800 tw-font-medium">{{ paymentHelperText }}</span>
          </div>

          <!-- Suggested Amount -->
          <div v-if="suggestedAmount && !payAmount" class="tw-flex tw-items-center tw-gap-2">
            <button
              @click="$emit('update:pay-amount', suggestedAmount)"
              class="tw-inline-flex tw-items-center tw-gap-2 tw-px-4 tw-py-2 tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-text-white tw-text-sm tw-font-semibold tw-rounded-lg tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200 hover:tw-scale-105"
            >
              <i class="pi pi-lightbulb"></i>
              Suggested: {{ formatCurrency(suggestedAmount) }}
            </button>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="md:tw-col-span-1 lg:tw-col-span-8 tw-space-y-3">
          <label :for="`item-method-${item.id}`" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
            <i class="pi pi-credit-card tw-text-indigo-500"></i>
            Payment Method
            <span class="tw-text-red-500">*</span>
          </label>

          <div class="tw-relative">
            <select
              :id="`item-method-${item.id}`"
              :value="payMethod"
              @change="$emit('update:pay-method', $event.target.value)"
              class="tw-w-full tw-p-4 tw-border tw-border-gray-300 tw-rounded-xl tw-text-base tw-font-medium tw-bg-white tw-shadow-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-indigo-400 focus:tw-border-indigo-400 tw-transition-all tw-duration-200 hover:tw-shadow-md"
            >
              <option value="cash" class="tw-py-2">💵 Cash Payment</option>
              <option value="card" class="tw-py-2">💳 Credit/Debit Card</option>
              <option value="cheque" class="tw-py-2">📄 Bank Cheque</option>
              <option value="transfer" class="tw-py-2">🏦 Bank Transfer</option>
            </select>
           
          </div>
        </div>

        <!-- Action Button -->
        <div class="md:tw-col-span-2 lg:tw-col-span-3 tw-flex tw-items-end tw-mt-4 md:tw-mt-0">
          <Button
            label="pay"
            icon="pi pi-check"
            @click="handlePayment"
            :disabled="!canPay"
            :class="{
              'tw-opacity-50 tw-cursor-not-allowed': !canPay,
              'hover:tw-scale-105 tw-shadow-lg hover:tw-shadow-xl': canPay
            }"
            class="tw-w-full tw-py-4  tw-text-base tw-font-bold tw-rounded-xl tw-transition-all tw-text-nowrap tw-duration-200 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 hover:tw-from-blue-700 hover:tw-to-indigo-700 tw-border-0"
          />
        </div>
      </div>

      <!-- Payment Summary -->
      <div v-if="canPay" class="tw-relative tw-z-10 tw-mt-6 tw-p-4 tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-blue-200 tw-rounded-xl tw-shadow-sm">
        <div class="tw-flex tw-justify-between tw-items-center tw-text-sm">
          <span class="tw-text-gray-600">Amount to Pay:</span>
          <span class="tw-font-bold tw-text-lg tw-text-blue-600">{{ formatCurrency(payAmount) }}</span>
        </div>
        <div class="tw-flex tw-justify-between tw-items-center tw-text-sm tw-mt-1">
          <span class="tw-text-gray-600">Remaining Balance:</span>
          <span class="tw-font-semibold tw-text-gray-700">{{ formatCurrency(Math.max(0, remaining - payAmount)) }}</span>
        </div>
      </div>
    </div>

    <!-- Transactions Section -->
    <div v-if="item.transactions && item.transactions.length" class="tw-border-t tw-border-gray-200 tw-pt-6">
      <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
        <div class="tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-list tw-text-gray-600"></i>
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-700 tw-m-0">Transaction History</h3>
          <div class="tw-bg-blue-100 tw-text-blue-800 tw-text-xs tw-font-bold tw-px-2 tw-py-1 tw-rounded-full">
            {{ sortedTransactions.length }}
          </div>
        </div>
        <Button
          :icon="transactionsVisible ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
          @click="$emit('toggle-transactions')"
          class="p-button-text p-button-sm tw-text-blue-600 hover:tw-text-blue-800 tw-transition-colors tw-duration-200"
          :label="transactionsVisible ? 'Hide' : 'Show'"
        />
      </div>

      <div v-if="transactionsVisible" class="tw-space-y-3">
        <div 
          v-for="tx in sortedTransactions" 
          :key="tx.id" 
          class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-4 tw-transition-all tw-duration-200 hover:tw-shadow-md tw-bg-white"
          :class="{
            'tw-border-l-4 tw-border-l-green-500 tw-bg-green-50': tx.transaction_type === 'payment',
            'tw-border-l-4 tw-border-l-red-500 tw-bg-red-50': tx.transaction_type === 'refund'
          }"
        >
          <div class="tw-flex tw-justify-between tw-items-start">
            <div class="tw-flex-1">
              <div class="tw-flex tw-items-center tw-gap-3 tw-mb-2">
                <span 
                  :class="getTransactionTypeClass(tx.transaction_type)"
                  class="tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide"
                >
                  {{ getTransactionTypeText(tx.transaction_type) }}
                </span>
                <span class="tw-font-bold tw-text-lg tw-text-gray-800">{{ formatCurrency(tx.amount) }}</span>
                <div class="tw-flex tw-items-center tw-gap-1 tw-text-gray-500 tw-text-sm tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded-full">
                  <i class="pi pi-credit-card tw-text-xs"></i>
                  <span>{{ tx.payment_method }}</span>
                </div>
                <div v-if="tx.is_bank_transaction && tx.bank_id" class="tw-flex tw-items-center tw-gap-1 tw-text-blue-600 tw-text-sm tw-bg-blue-100 tw-px-2 tw-py-1 tw-rounded-full">
                  <i class="pi pi-building tw-text-xs"></i>
                  <span>Bank: {{ tx.bank_id }}</span>
                </div>
              </div>
              
              <div class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-1">
                <i class="pi pi-calendar tw-text-xs"></i>
                <span>{{ new Date(tx.created_at).toLocaleString() }}</span>
              </div>
              
              <div v-if="tx.notes" class="tw-text-sm tw-text-gray-600 tw-bg-gray-100 tw-px-3 tw-py-2 tw-rounded-lg tw-mt-2">
                <i class="pi pi-comment tw-text-gray-500 tw-mr-1"></i>
                {{ tx.notes }}
              </div>
            </div>

            <div class="tw-flex tw-flex-col tw-items-end tw-gap-2 tw-ml-4">
              <div class="tw-flex tw-items-center tw-gap-2" v-if="tx.transaction_type === 'payment'">
                <div v-if="tx.refund_authorization || refundAuthMap?.[item.id]" class="tw-bg-orange-100 tw-text-orange-700 tw-px-2 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium">
                  <i class="pi pi-hourglass tw-mr-1"></i>
                  Auth Pending
                </div>

                <Tag 
                  v-if="item.transactions?.some(t => 
                    t.transaction_type === 'refund' && 
                    (t.original_transaction_id === tx.id || t.refunded_transaction_id === tx.id)
                  )"
                  severity="info"
                  class="tw-shadow-sm"
                  :value="`Refunded: ${formatCurrency(
                    item.transactions.find(t => 
                      t.transaction_type === 'refund' && 
                      (t.original_transaction_id === tx.id || t.refunded_transaction_id === tx.id)
                    )?.amount ?? 0
                  )}`"
                />

                <Button
                  v-if="canRefund && shouldShowRefundButton(tx, item)"
                  icon="pi pi-undo"
                  @click="$emit('open-refund', { 
                    transaction: tx, 
                    fixedAmount: getRefundFixedAmount(tx, item),
                    isAuthorized: isRefundAuthorized(tx, item),
                    allowFlexibleAmount: canUseFlexibleRefund(tx, item)
                  })"
                  class="p-button-sm p-button-rounded p-button-warning tw-transition-all tw-duration-200 tw-shadow-md hover:tw-shadow-lg"
                  v-tooltip.top="getRefundButtonTooltip(tx, item)"
                />
              </div>

              <div v-if="tx.transaction_type === 'payment'" class="tw-flex tw-items-center tw-gap-2">
                <Button
                  icon="pi pi-pencil"
                  @click="$emit('open-update', tx)"
                  class="p-button-sm p-button-rounded p-button-secondary tw-transition-all tw-duration-200 tw-shadow-md hover:tw-shadow-lg"
                  v-tooltip.top="'Update transaction'"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dependencies Section -->
    <div v-if="item.dependencies && item.dependencies.length && !item.is_dependency" class="tw-border-t tw-border-gray-200 tw-pt-6 tw-mt-6">
      <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
        <i class="pi pi-sitemap tw-text-gray-600"></i>
        <h3 class="tw-text-lg tw-font-semibold tw-text-gray-700 tw-m-0">Dependencies</h3>
      </div>
      <div class="tw-grid tw-gap-3">
        <div v-for="dep in item.dependencies" :key="dep.id" class="tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-p-4">
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-2 tw-flex-1">
              <i class="pi pi-arrow-right tw-text-blue-500 tw-text-sm"></i>
              <span class="tw-font-medium tw-text-gray-800">
                {{ dep.display_name ?? dep.dependencyPrestation?.name ?? dep.custom_name ?? 'Dependency' }}
              </span>
            </div>

            <div class="tw-text-right tw-space-y-1">
              <div class="tw-text-sm tw-text-gray-600">
                Price: <span class="tw-font-semibold">{{ formatCurrency(dep.final_price ?? dep.base_price ?? dep.dependencyPrestation?.public_price ?? 0) }}</span>
              </div>
              <div class="tw-text-sm tw-font-semibold" :class="(dep.final_price ?? dep.base_price ?? 0) - (dep.paid_amount ?? 0) <= 0 ? 'tw-text-green-600' : 'tw-text-red-600'">
                {{ (dep.final_price ?? dep.base_price ?? 0) - (dep.dependencyPrestation.paid_amount ?? 0) <= 0 ? '✓ Paid' : `Remaining: ${formatCurrency(Math.max(0, (dep.final_price ?? dep.base_price ?? dep.dependencyPrestation?.public_price ?? 0) - (dep.dependencyPrestation.paid_amount ?? 0)))}` }}
              </div>
            </div>
          </div>
        </div>
      </div>
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

// Props remain the same
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
  canRefund: {
    type: Function,
    required: true
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
  serviceName: {
    type: String,
    default: ''
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
    type: Boolean,
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

// All computed properties and methods remain the same as in the original script
const handlePayment = () => {
  const amount = Number(props.payAmount)
  const remaining = props.remaining
  
  if (amount > remaining && remaining > 0) {
    emit('show-overpayment', {
      required: remaining,
      paid: amount,
      excess: amount - remaining,
      item: props.item
    })
  } else {
    emit('pay-item')
  }
}

const displayName = computed(() => {
  
  // For packages, show the package name with price in the title
  if (props.item.package) {
    return `${props.item.package.name} - ${formatCurrency(props.item.final_price)}`
  }
  
  let name = props.item.display_name ?? 
             props.item.prestation?.name ?? 
             props.item.custom_name ?? 
             'Prestation'

  // Handle multiple doctors or single doctor
  const doctors = []
  
  // Main item doctor
  if (props.item.doctor_name) {
    doctors.push(props.item.doctor_name)
  }
  
  // Package prestations doctors
  if (props.item.package?.prestations) {
    props.item.package.prestations.forEach(prestation => {
      if (prestation.doctor?.name && !doctors.includes(prestation.doctor.name)) {
        doctors.push(prestation.doctor.name)
      }
    })
  }
  
  // Dependencies doctors
  if (props.item.dependencies) {
    props.item.dependencies.forEach(dependency => {
      if (dependency.dependencyPrestation?.doctor?.name && !doctors.includes(dependency.dependencyPrestation.doctor.name)) {
        doctors.push(dependency.dependencyPrestation.doctor.name)
      }
    })
  }

  if (doctors.length > 0) {
    name += ` (${doctors.join(', ')})`
  }

  return name
})

const serviceName = computed(() => {
  return props.serviceName || 
         (props.item.prestation?.service?.name ?? 
          props.item.service_name ?? 
          '')
})

const doctorTags = computed(() => {
  const doctors = []
  
  // Main item doctor
  if (props.item.doctor_name) {
    doctors.push({
      id: props.item.doctor_id || 'unknown',
      name: props.item.doctor_name,
      source: 'main'
    })
  }
  
  // Package prestations doctors
  if (props.item.package?.prestations) {
    props.item.package.prestations.forEach(prestation => {
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
  if (props.item.dependencies) {
    props.item.dependencies.forEach(dependency => {
      if (dependency.dependencyPrestation?.doctor?.name && !doctors.some(d => d.id === dependency.dependencyPrestation.doctor.id)) {
        doctors.push({
          id: dependency.dependencyPrestation.doctor.id,
          name: dependency.dependencyPrestation.doctor.name,
          source: 'dependency'
        })
      }
    })
  }

  return doctors
})

const cardClass = computed(() => {
  if (props.paymentStatus.status === 'paid') {
    return 'tw-bg-green-50 tw-border-green-300 tw-shadow-green-100'
  }
  if (props.paymentStatus.status === 'visa_granted') {
    return 'tw-bg-green-50 tw-border-green-300 tw-shadow-green-100'
  }
  if (props.paymentStatus.status === 'partial') {
    return 'tw-bg-orange-50 tw-border-orange-300 tw-shadow-orange-100'
  }
  return 'tw-bg-red-50 tw-border-red-300 tw-shadow-red-100'
})

const canPay = computed(() => {
  const amount = Number(props.payAmount)
  return amount > 0
})

const sortedTransactions = computed(() => {
  if (!props.item?.transactions) return [];
  
  const refundMap = new Map();
  props.item.transactions.forEach(tx => {
    if (tx.transaction_type === 'refund') {
      refundMap.set(tx.original_transaction_id || tx.refunded_transaction_id, tx);
    }
  });

  return [...props.item.transactions].sort((a, b) => {
    if (b.id !== a.id) return b.id - a.id;
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

const hasExistingRefund = (transaction, item) => {
  if (!transaction || !item || !Array.isArray(item.transactions)) return false;

  return item.transactions.some(t => {
    return t.transaction_type === 'refund' && 
           (t.original_transaction_id === transaction.id || 
            t.refunded_transaction_id === transaction.id);
  });
}

const suggestedAmount = computed(() => {
  if (props.minVersementAmount > 0 && !props.isMinVersementPaid) {
    const remainingForVisa = Math.max(0, props.minVersementAmount - props.paidAmount)
    return Math.min(remainingForVisa, props.remaining)
  }
  return Math.min(1000, props.remaining)
})

const paymentHelperText = computed(() => {
  if (props.minVersementAmount > 0 && !props.isMinVersementPaid) {
    const remainingForVisa = Math.max(0, props.minVersementAmount - props.paidAmount)
    if (remainingForVisa > 0) {
      return `Pay ${formatCurrency(remainingForVisa)} to get visa approval`
    }
  }
  return null
})

const isFichePendingOrConfirmed = computed(() => {
  const status = String(props.ficheStatus || '').toLowerCase()
  return ['pending', 'confirmed'].includes(status)
})

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

const shouldShowRefundButton = (transaction, item) => {
  if (!transaction || transaction.transaction_type !== 'payment') return false
  if (hasExistingRefund(transaction, item)) return false
  
  if (isFichePendingOrConfirmed.value) {
    return transaction.id === getLatestPaymentId(item)
  }
  
  return isRefundAuthorized(transaction, item)
}

const isRefundAuthorized = (transaction, item) => {
  if (!transaction || !item) return false
  
  if (transaction.refund_authorization) {
    const status = String(transaction.refund_authorization.status || '').toLowerCase()
    return ['approved', 'pending'].includes(status)
  }
  
  return !!authorizedRefund.value
}

const getRefundFixedAmount = (transaction, item) => {
  if (isFichePendingOrConfirmed.value) return null
  
  if (transaction.refund_authorization?.requested_amount) {
    return transaction.refund_authorization.requested_amount
  }
  
  return authorizedRefundAmount.value || null
}

const canUseFlexibleRefund = (transaction, item) => {
  return isFichePendingOrConfirmed.value
}
</script>
