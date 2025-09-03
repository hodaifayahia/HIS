<template>
  <div
    :id="`prestation-card-${item.id}`"
    class="tw-border tw-border-gray-300 tw-p-4 tw-rounded-md tw-mb-3"
    :class="getPrestationCardClass(item)"
  >
    <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
      <div>
        <div class="tw-font-semibold tw-text-lg">{{ item.display_name ?? item.prestation?.name ?? item.custom_name ?? 'Prestation' }}</div>
        <div class="tw-text-sm tw-text-gray-500">{{ item.prestation?.service?.name ?? item.service_name ?? '' }}</div>
      </div>
      <div class="tw-text-right">
        <div class="tw-font-bold tw-text-xl tw-text-gray-900">{{ formatCurrency(item.final_price ?? 0) }}</div>
        <div class="tw-text-sm tw-text-gray-500">Payé: {{ formatCurrency(item.paid_amount ?? 0) }}</div>
        <div class="tw-text-sm" :class="remaining <= 0 ? 'tw-text-green-600' : 'tw-text-red-600'">
          Reste: {{ formatCurrency(remaining) }}
        </div>
      </div>
    </div>

    <div class="tw-flex tw-items-center tw-gap-3 tw-mt-2">
      <div class="tw-flex-1 tw-min-w-[120px]">
        <label :for="`item-amount-${item.id}`" class="tw-block tw-text-xs tw-font-medium tw-text-gray-600 tw-mb-1">Montant</label>
        <input
          :id="`item-amount-${item.id}`"
          type="number"
          step="0.01"
          :max="remaining"
          v-model.number="localAmount"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md"
          :disabled="remaining <= 0"
          @input="$emit('update:amount', localAmount)"
        />
      </div>
      <div class="tw-min-w-[120px]">
        <label :for="`item-method-${item.id}`" class="tw-block tw-text-xs tw-font-medium tw-text-gray-600 tw-mb-1">Méthode</label>
        <select
          :id="`item-method-${item.id}`"
          v-model="localMethod"
          class="tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
          :disabled="remaining <= 0"
          @change="$emit('update:method', localMethod)"
        >
          <option value="cash">Espèce</option>
          <option value="card">Carte</option>
          <option value="cheque">Chèque</option>
        </select>
      </div>
      <Button
        label="Payer"
        icon="pi pi-check"
        class="p-button-primary tw-mt-auto"
        :disabled="Number(localAmount) <= 0 || remaining <= 0"
        @click="$emit('pay-item', { id: item.id, amount: Number(localAmount || 0), method: localMethod })"
      />
    </div>

    <TransactionsList 
      v-if="item.transactions && item.transactions.length"
      :transactions="item.transactions"
      :item="item"
      :userCanRefund="userCanRefund"
      :formatCurrency="formatCurrency"
      :getTransactionTypeClass="getTransactionTypeClass"
      :getTransactionTypeText="getTransactionTypeText"
      :canUpdate="canUpdate"
      :canShowRefundButton="canShowRefundButton"
      @update-transaction="$emit('update-transaction', $event)"
      @refund-transaction="$emit('refund-transaction', $event)"
    />

    <div v-if="item.dependencies && item.dependencies.length" class="tw-mt-4 tw-border-t tw-border-gray-300 tw-pt-4">
      <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-2">Dépendances</div>
      <ul class="tw-list-disc tw-pl-5 tw-text-sm tw-text-gray-600">
        <li v-for="d in item.dependencies" :key="d.id">
          {{ d.display_name ?? d.dependencyPrestation?.name ?? 'Dépendance' }}
          <span class="tw-ml-2 tw-text-xs tw-text-gray-500"> — {{ formatCurrency(d.final_price ?? d.base_price ?? 0) }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Button from 'primevue/button'
import TransactionsList from './TransactionsOverview.vue'

const props = defineProps({
  item: { type: Object, required: true },
  userCanRefund: { type: Boolean, default: false },
  formatCurrency: { type: Function, required: true },
  getTransactionTypeClass: { type: Function, required: true },
  getTransactionTypeText: { type: Function, required: true },
  canUpdate: { type: Function, required: true },
  canShowRefundButton: { type: Function, required: true }
})

const emit = defineEmits(['update:amount', 'update:method', 'pay-item', 'update-transaction', 'refund-transaction'])

const localAmount = ref(props.item._pay_amount)
const localMethod = ref(props.item._pay_method || 'cash')

const remaining = computed(() => {
  const finalPrice = Number(props.item.final_price ?? 0)
  const paid = Number(props.item.paid_amount ?? 0)
  return Math.max(0, finalPrice - paid)
})

const getPrestationCardClass = (item) => {
  if (remaining.value <= 0) {
    return 'tw-bg-green-50 tw-border-green-300'
  }
  return 'tw-bg-orange-50 tw-border-orange-300'
}

watch(() => props.item._pay_amount, (val) => localAmount.value = val)
watch(() => props.item._pay_method, (val) => localMethod.value = val)
</script>