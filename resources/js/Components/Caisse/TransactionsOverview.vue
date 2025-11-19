<template>
  <div class="tw-bg-white tw-rounded-lg tw-p-6 tw-shadow-md">
    <h3 class="tw-text-xl tw-font-semibold tw-mb-4">Transactions Overview</h3>
    
    <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-3 tw-mb-4">
      <InputText
        :modelValue="searchQuery"
        @update:modelValue="$emit('update:search-query', $event)"
        placeholder="Search by ID..."
        class="tw-flex-1"
      />
      <Dropdown
        :modelValue="selectedType"
        @update:modelValue="$emit('update:selected-type', $event)"
        :options="transactionTypeOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="Transaction Type"
        class="tw-w-full md:tw-w-auto"
      />
      <Calendar
        :modelValue="selectedDateRange"
        @update:modelValue="$emit('update:selected-date-range', $event)"
        selectionMode="range"
        placeholder="Period"
        class="tw-w-full md:tw-w-auto"
      />
    </div>
    
    <div class="tw-overflow-x-auto">
      <table class="tw-min-w-full tw-text-sm tw-text-left tw-whitespace-nowrap">
        <thead>
          <tr class="tw-border-b tw-border-gray-200">
            <th class="tw-py-2 tw-px-4 tw-font-semibold">ID</th>
            <th class="tw-py-2 tw-px-4 tw-font-semibold">Amount</th>
            <th class="tw-py-2 tw-px-4 tw-font-semibold">Type</th>
            <th class="tw-py-2 tw-px-4 tw-font-semibold tw-text-right">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="tx in filteredTransactions"
            :key="tx.id"
            class="tw-border-b tw-border-gray-100 hover:tw-bg-gray-50"
          >
            <td class="tw-py-2 tw-px-4">#{{ tx.id }}</td>
            <td class="tw-py-2 tw-px-4">{{ formatCurrency(tx.amount ?? tx.total ?? 0) }}</td>
            <td class="tw-py-2 tw-px-4">
              <span :class="getTransactionTypeClass(tx.transaction_type)">
                {{ getTransactionTypeText(tx.transaction_type) }}
              </span>
            </td>
            <td class="tw-py-2 tw-px-4 tw-text-right tw-text-gray-600">
              {{ new Date(tx.created_at).toLocaleString() }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import { useCurrencyFormatter } from '@/composables/useCurrencyFormatter'
import { useTransactionHelpers } from '@/composables/useTransactionHelpers'

const props = defineProps({
  filteredTransactions: {
    type: Array,
    required: true
  },
  transactionTypeOptions: {
    type: Array,
    required: true
  },
  searchQuery: {
    type: String,
    default: ''
  },
  selectedType: {
    type: String,
    default: null
  },
  selectedDateRange: {
    type: Array,
    default: null
  }
})

const emit = defineEmits([
  'update:search-query',
  'update:selected-type', 
  'update:selected-date-range'
])

const { formatCurrency } = useCurrencyFormatter()
const { getTransactionTypeText, getTransactionTypeClass } = useTransactionHelpers()
</script>