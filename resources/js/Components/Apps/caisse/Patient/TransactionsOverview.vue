<template>
  <div class="tw-mt-4 tw-border-t tw-border-gray-300 tw-pt-4">
    <div class="tw-font-medium tw-mb-2 tw-text-gray-600 tw-flex tw-justify-between tw-items-center">
      <span>Transactions</span>
      <Button
        :icon="`pi ${transactionsVisible ? 'pi-chevron-up' : 'pi-chevron-down'}`"
        class="p-button-text p-button-sm"
        @click="transactionsVisible = !transactionsVisible"
      />
    </div>
    <ul v-if="transactionsVisible" class="tw-list-none tw-p-0 tw-text-sm tw-space-y-2">
      <li v-for="tx in transactions" :key="tx.id" class="tw-bg-gray-100 tw-rounded-md tw-p-3 tw-flex tw-justify-between tw-items-center">
        <div class="tw-flex-1">
          <div class="tw-font-semibold tw-text-gray-900">{{ formatCurrency(tx.amount ?? tx.total ?? 0) }}</div>
          <div class="tw-text-xs tw-text-gray-500">
            ID: {{ tx.id }} · {{ tx.payment_method ?? tx.method ?? '—' }} ·
            <span :class="getTransactionTypeClass(tx.transaction_type)">
              {{ getTransactionTypeText(tx.transaction_type) }}
            </span>
            <span v-if="tx.is_bank_transaction && tx.bank_id" class="tw-ml-2 tw-px-2 tw-py-1 tw-bg-blue-100 tw-text-blue-800 tw-rounded-full tw-text-xs tw-font-medium">
              <i class="pi pi-building tw-mr-1"></i>
              Bank ID: {{ tx.bank_id }}
            </span>
          </div>
        </div>
        <div class="tw-flex tw-items-center tw-gap-2">
          <div class="tw-text-xs tw-text-right tw-text-gray-600 tw-mr-2">
            {{ new Date(tx.created_at).toLocaleString() }}
          </div>
          <Button
            v-if="tx.transaction_type === 'payment' && canUpdate(tx)"
            icon="pi pi-pencil"
            @click="$emit('update-transaction', { transaction: tx, item })"
            class="p-button-info p-button-sm p-button-rounded p-button-text"
            v-tooltip.top="'Modifier le paiement'"
          />
          <Button
            v-if="userCanRefund && canShowRefundButton(tx, item)"
            icon="pi pi-undo"
            @click="$emit('refund-transaction', { transaction: tx, item })"
            class="p-button-danger p-button-sm p-button-rounded"
          />
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Button from 'primevue/button'

const props = defineProps({
  transactions: { type: Array, required: true },
  item: { type: Object, required: true },
  userCanRefund: { type: Boolean, default: false },
  formatCurrency: { type: Function, required: true },
  getTransactionTypeClass: { type: Function, required: true },
  getTransactionTypeText: { type: Function, required: true },
  canUpdate: { type: Function, required: true },
  canShowRefundButton: { type: Function, required: true }
})

const emit = defineEmits(['update-transaction', 'refund-transaction'])

const transactionsVisible = ref(false)
</script>