<template>
  <div class="tw-mb-6 tw-pb-6 tw-border-b tw-border-gray-200">
    <h3 class="tw-text-xl tw-font-semibold tw-mb-4">Paiement Global</h3>
    <div class="tw-flex tw-items-center tw-gap-3 tw-flex-wrap">
      <div class="tw-flex-1 tw-min-w-[150px]">
        <label for="global-amount" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Montant global</label>
        <InputNumber
          id="global-amount"
          v-model="localAmount"
          :placeholder="`Montant global`"
          :max="totalOutstanding"
          class="tw-w-full"
          mode="decimal"
          :minFractionDigits="2"
          :maxFractionDigits="2"
          @input="$emit('update:amount', localAmount)"
        />
      </div>
      <div class="tw-min-w-[120px]">
        <label for="global-method" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Méthode</label>
        <select
          id="global-method"
          v-model="localMethod"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
          @change="$emit('update:method', localMethod)"
        >
          <option value="cash">Espèce</option>
          <option value="card">Carte</option>
          <option value="cheque">Chèque</option>
        </select>
      </div>
      <Button
        label="Payer Global"
        icon="pi pi-check-circle"
        @click="$emit('pay-global')"
        :disabled="!canPayGlobal"
        class="p-button-success tw-mt-auto"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'

const props = defineProps({
  amount: { type: Number, default: null },
  method: { type: String, default: 'cash' },
  totalOutstanding: { type: Number, required: true }
})

const emit = defineEmits(['update:amount', 'update:method', 'pay-global'])

const localAmount = ref(props.amount)
const localMethod = ref(props.method)

const canPayGlobal = computed(() => {
  const amount = Number(localAmount.value)
  return amount > 0
})

watch(() => props.amount, (val) => localAmount.value = val)
watch(() => props.method, (val) => localMethod.value = val)
</script>