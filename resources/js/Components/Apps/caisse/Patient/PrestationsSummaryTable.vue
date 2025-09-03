<template>
  <div class="tw-bg-white tw-rounded-lg tw-p-6 tw-shadow-md lg:tw-sticky lg:tw-top-6">
    <h3 class="tw-text-xl tw-font-semibold tw-mb-4">Aperçu des Prestations</h3>
    <div class="tw-overflow-x-auto">
      <table class="tw-min-w-full tw-text-sm tw-text-left tw-whitespace-nowrap">
        <thead>
          <tr class="tw-border-b tw-border-gray-200">
            <th class="tw-py-2 tw-px-4 tw-font-semibold">Prestation</th>
            <th class="tw-py-2 tw-px-4 tw-font-semibold tw-text-right">Prix</th>
            <th class="tw-py-2 tw-px-4 tw-font-semibold tw-text-right">Payé</th>
            <th class="tw-py-2 tw-px-4 tw-font-semibold tw-text-right">Reste</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in summaryItems" :key="item.id" class="tw-border-b tw-border-gray-100">
            <td class="tw-py-2 tw-px-4">
              <a href="#" @click.prevent="$emit('scroll-to-item', item.id)" class="tw-text-blue-600 hover:tw-underline">
                {{ item.display_name }}
              </a>
            </td>
            <td class="tw-py-2 tw-px-4 tw-text-right">{{ formatCurrency(item.final_price) }}</td>
            <td class="tw-py-2 tw-px-4 tw-text-right">{{ formatCurrency(item.paid_amount) }}</td>
            <td class="tw-py-2 tw-px-4 tw-text-right" :class="item.remaining <= 0 ? 'tw-text-green-600' : 'tw-text-red-600'">
              {{ formatCurrency(item.remaining) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  summaryItems: { type: Array, required: true },
  formatCurrency: { type: Function, required: true }
})

const emit = defineEmits(['scroll-to-item'])
</script>