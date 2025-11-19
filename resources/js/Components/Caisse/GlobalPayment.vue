<template>
  <div class="tw-mb-6 tw-pb-6 tw-border-b tw-border-gray-200">
    <h3 class="tw-text-xl tw-font-semibold tw-mb-4">Global Payment</h3>
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-3 tw-mb-4">
      <div>
        <label for="global-amount" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">
          Global Amount
        </label>
        <InputNumber
          id="global-amount"
          :modelValue="amount"
          @update:modelValue="$emit('update:amount', $event)"
          placeholder="Global Amount"
          class="tw-w-full"
          mode="decimal"
          :minFractionDigits="2"
          :maxFractionDigits="2"
        />
      </div>
      <div>
        <label for="global-method" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">
          Method
        </label>
        <select
          id="global-method"
          :value="method"
          @change="handleMethodChange"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
        >
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="cheque">Check</option>
          <option value="bank_transfer">Bank Transfer</option>
        </select>
      </div>

      <div class="tw-flex tw-items-end">
        <Button
          label="Pay Global"
          icon="pi pi-check-circle"
          @click="$emit('pay-global')"
          :disabled="!canPay"
          class="p-button-success tw-w-full"
        />
      </div>
    </div>
    <div v-if="isBankTransaction" class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-md tw-p-3">
      <div class="tw-flex tw-items-center tw-gap-2">
        <i class="pi pi-info-circle tw-text-blue-600"></i>
        <span class="tw-text-sm tw-text-blue-800">
          This transaction will be processed as a bank transaction with the default active bank account
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import { computed, ref } from 'vue'

const props = defineProps({
  amount: {
    type: Number,
    default: null
  },
  method: {
    type: String,
    default: 'cash'
  },
  maxAmount: {
    type: Number,
    required: true
  },
  bankId: {
    type: [Number, String],
    default: null
  },
  isBankTransaction: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
  'update:amount', 
  'update:method', 
  'update:bank-id', 
  'update:is-bank-transaction', 
  'pay-global'
])

// Local state
const loading = ref(false)

// Computed properties

const canPay = computed(() => {
  const amount = Number(props.amount)
  return amount > 0
})

// Methods
const handleMethodChange = (event) => {
  const newMethod = event.target.value
  emit('update:method', newMethod)
  
  // Auto-set bank transaction flag
  const isBankMethod = newMethod === 'bank_transfer'
  emit('update:is-bank-transaction', isBankMethod)
}
</script>

<style scoped>
@reference "../../../../resources/css/app.css";

/*
  The `p-invalid` class is provided by PrimeVue.
  We apply Tailwind's border-red-500 to it for consistent styling.
*/
.p-invalid {
  @apply tw-border-red-500;
}
</style>