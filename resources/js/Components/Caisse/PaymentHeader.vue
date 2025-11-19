<template>
  <div class="tw-bg-white tw-rounded-lg tw-p-6 tw-mb-6 tw-shadow-md">
    <div class="tw-flex tw-justify-between tw-items-center">
      <div>
        <h2 class="tw-text-2xl tw-font-bold">Paiement — Fiche #{{ ficheId }}</h2>
        <div class="tw-text-sm tw-text-gray-500">Patient: {{ patientName || '—' }}</div>
      </div>
      <div class="tw-text-right">
        <div class="tw-text-sm tw-text-gray-500">Total à payer</div>
        <div class="tw-text-3xl tw-font-bold tw-text-red-600">{{ formatCurrency(totalOutstanding) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCurrencyFormatter } from '@/composables/useCurrencyFormatter'

const props = defineProps({
  ficheId: {
    type: [String, Number],
    required: true
  },
  patientName: {
    type: String,
    default: null
  },
  totalOutstanding: {
    type: Number,
    required: true
  }
})

const { formatCurrency } = useCurrencyFormatter()
</script>
