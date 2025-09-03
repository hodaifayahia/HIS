<template>
  <div class="tw-mb-6 tw-pb-6 tw-border-b tw-border-gray-200">
    <h3 class="tw-text-xl tw-font-semibold tw-mb-4">Global Payment</h3>
    <div class="tw-flex tw-items-center tw-gap-3 tw-flex-wrap">
      <div class="tw-flex-1 tw-min-w-[150px]">
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
      <div class="tw-min-w-[120px]">
        <label for="global-method" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">
          Method
        </label>
        <select
          id="global-method"
          :value="method"
          @change="$emit('update:method', $event.target.value)"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
        >
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="cheque">Check</option>
        </select>
      </div>
      <Button
        label="Pay Global"
        icon="pi pi-check-circle"
        @click="$emit('pay-global')"
        :disabled="!canPay"
        class="p-button-success tw-mt-auto"
      />
    </div>
  </div>
</template>

<script setup>
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import { computed } from 'vue'

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
  }
})

const emit = defineEmits(['update:amount', 'update:method', 'pay-global'])

const canPay = computed(() => {
  const amount = Number(props.amount)
  return amount > 0
})
</script>

<style scoped>
/*
  The `p-invalid` class is provided by PrimeVue.
  We apply Tailwind's border-red-500 to it for consistent styling.
*/
.p-invalid {
  @apply tw-border-red-500;
}
</style>