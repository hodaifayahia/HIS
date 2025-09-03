<script setup>
import { computed } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
  transaction: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close']);

// Methods
const formatCurrency = (amount, currency = 'DZD') => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2
  }).format(amount || 0);
};

const formatFullDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  }).format(new Date(dateString));
};

const getStatusIcon = (status) => {
  const icons = {
    'pending': 'pi pi-clock',
    'completed': 'pi pi-check-circle',
    'cancelled': 'pi pi-times-circle',
    'reconciled': 'pi pi-verified'
  };
  return icons[status] || 'pi pi-circle';
};

const getAmountClass = (type) => {
  return type === 'credit' ? 'tw-text-green-500' : 'tw-text-red-500';
};

const getOverviewClass = (type) => {
  return type === 'credit' ? 'tw-bg-gradient-to-br tw-from-green-500 tw-to-emerald-500' : 'tw-bg-gradient-to-br tw-from-red-500 tw-to-rose-500';
};

const printTransaction = () => {
  window.print();
};
</script>

<template>
  <div class="tw-fixed tw-inset-0 tw-bg-black/60 tw-flex tw-items-center tw-justify-center tw-z-[1050] tw-backdrop-blur-sm" @click.self="$emit('close')">
    <div class="tw-bg-white tw-rounded-[20px] tw-w-[90%] md:tw-max-w-[900px] tw-max-h-[95vh] tw-overflow-y-auto tw-shadow-2xl tw-animate-slideIn">
      <div class="tw-p-6 md:tw-p-8 tw-border-b tw-border-gray-200 tw-flex tw-justify-between tw-items-center tw-bg-gray-50 tw-rounded-t-[20px]">
        <h4 class="tw-m-0 tw-font-semibold tw-text-xl md:tw-text-2xl tw-text-gray-900 tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-receipt"></i>
          Transaction Details
        </h4>
        <button type="button" class="tw-bg-transparent tw-border-none tw-text-gray-600 tw-text-xl tw-p-2 tw-rounded-full tw-w-10 tw-h-10 tw-flex tw-items-center tw-justify-center tw-transition-all tw-duration-200 tw-cursor-pointer hover:tw-bg-gray-100 hover:tw-text-red-500" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="tw-p-6 md:tw-p-8" v-if="transaction">
        <div class="tw-p-8 tw-rounded-xl tw-mb-8 tw-text-white" :class="getOverviewClass(transaction.transaction_type)">
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-center sm:tw-items-start tw-gap-6 tw-text-center sm:tw-text-left">
            <div class="tw-w-16 tw-h-16 tw-bg-white/20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-3xl">
              <i :class="transaction.transaction_type === 'credit' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"></i>
            </div>
            <div class="tw-flex-1">
              <h3 class="tw-m-0 tw-font-bold tw-text-4xl">
                {{ transaction.transaction_type === 'credit' ? '+' : '-' }}{{ formatCurrency(transaction.amount, transaction.bank_account?.currency) }}
              </h3>
              <p class="tw-m-0 tw-mt-1 tw-opacity-90 tw-text-lg">{{ transaction.transaction_type_text }} Transaction</p>
              <div class="tw-mt-4">
                <Tag
                  :value="transaction.status_text"
                  :severity="transaction.status_color"
                  :icon="getStatusIcon(transaction.status)"
                  class="tw-text-sm tw-py-2 tw-px-4"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-6 tw-mb-8">
          <div class="tw-bg-gray-50 tw-p-6 tw-rounded-xl tw-border tw-border-gray-200">
            <h6 class="tw-m-0 tw-font-semibold tw-text-lg tw-text-gray-900 tw-pb-2 tw-border-b-2 tw-border-gray-200">Transaction Information</h6>
            <div class="tw-flex tw-flex-col tw-gap-3 tw-mt-4">
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Reference Number</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right tw-font-mono">{{ transaction.reference }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Transaction Date</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ formatFullDate(transaction.transaction_date) }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Amount</span>
                <span class="tw-font-bold tw-text-sm tw-text-right" :class="getAmountClass(transaction.transaction_type)">
                  {{ transaction.transaction_type === 'credit' ? '+' : '-' }}{{ formatCurrency(transaction.amount, transaction.bank_account?.currency) }}
                </span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Currency</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ transaction.bank_account?.currency || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <div class="tw-bg-gray-50 tw-p-6 tw-rounded-xl tw-border tw-border-gray-200">
            <h6 class="tw-m-0 tw-font-semibold tw-text-lg tw-text-gray-900 tw-pb-2 tw-border-b-2 tw-border-gray-200">Account Information</h6>
            <div class="tw-flex tw-flex-col tw-gap-3 tw-mt-4">
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Account Name</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ transaction.bank_account?.account_name || 'N/A' }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Bank</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ transaction.bank_account?.bank_name || 'Unknown Bank' }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Account Number</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right tw-font-mono">{{ transaction.bank_account?.account_number || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <div class="tw-bg-gray-50 tw-p-6 tw-rounded-xl tw-border tw-border-gray-200">
            <h6 class="tw-m-0 tw-font-semibold tw-text-lg tw-text-gray-900 tw-pb-2 tw-border-b-2 tw-border-gray-200">Processing Information</h6>
            <div class="tw-flex tw-flex-col tw-gap-3 tw-mt-4">
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Accepted By</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ transaction.accepted_by?.name || 'System' }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4" v-if="transaction.accepted_by?.email">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">User Email</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ transaction.accepted_by.email }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4" v-if="transaction.reconciled_by">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Reconciled By</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ transaction.reconciled_by?.name || 'N/A' }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-gap-4" v-if="transaction.reconciled_at">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm">Reconciled At</span>
                <span class="tw-font-semibold tw-text-gray-900 tw-text-sm tw-text-right">{{ formatFullDate(transaction.reconciled_at) }}</span>
              </div>
            </div>
          </div>

          <div class="tw-col-span-1 md:tw-col-span-3 tw-bg-gray-50 tw-p-6 tw-rounded-xl tw-border tw-border-gray-200" v-if="transaction.description">
            <h6 class="tw-m-0 tw-font-semibold tw-text-lg tw-text-gray-900 tw-pb-2 tw-border-b-2 tw-border-gray-200">Description</h6>
            <div class="tw-mt-4">
              <p class="tw-m-0 tw-text-gray-700 tw-leading-snug">{{ transaction.description }}</p>
            </div>
          </div>
        </div>

        <div class="tw-bg-gray-50 tw-p-6 tw-rounded-xl tw-border tw-border-gray-200">
          <h6 class="tw-m-0 tw-font-semibold tw-text-lg tw-text-gray-900 tw-pb-2 tw-border-b-2 tw-border-gray-200 tw-mb-6">Transaction Timeline</h6>
          <div class="tw-flex tw-flex-col tw-gap-4">
            <div class="tw-flex tw-items-start tw-gap-4 tw-opacity-50 tw-transition-opacity tw-duration-300" :class="{ 'tw-opacity-100': true }">
              <div class="tw-w-10 tw-h-10 tw-bg-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-gray-600 tw-text-lg tw-flex-shrink-0" :class="{ 'tw-bg-blue-600 tw-text-white': true }">
                <i class="pi pi-plus"></i>
              </div>
              <div>
                <h5 class="tw-m-0 tw-font-semibold tw-text-base tw-text-gray-900">Transaction Created</h5>
                <p class="tw-m-0 tw-text-sm tw-text-gray-600">{{ formatFullDate(transaction.created_at) }}</p>
                <small class="tw-text-gray-400 tw-text-xs">By {{ transaction.accepted_by?.name || 'System' }}</small>
              </div>
            </div>
            <div class="tw-flex tw-items-start tw-gap-4 tw-opacity-50 tw-transition-opacity tw-duration-300" :class="{ 'tw-opacity-100': transaction.status === 'completed' || transaction.status === 'reconciled' }">
              <div class="tw-w-10 tw-h-10 tw-bg-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-gray-600 tw-text-lg tw-flex-shrink-0" :class="{ 'tw-bg-green-600 tw-text-white': transaction.status === 'completed' || transaction.status === 'reconciled' }">
                <i class="pi pi-check"></i>
              </div>
              <div>
                <h5 class="tw-m-0 tw-font-semibold tw-text-base tw-text-gray-900">Transaction Completed</h5>
                <p v-if="transaction.status === 'completed' || transaction.status === 'reconciled'" class="tw-m-0 tw-text-sm tw-text-gray-600">{{ formatFullDate(transaction.updated_at) }}</p>
                <p v-else class="tw-m-0 tw-text-sm tw-text-gray-400 tw-italic">Pending completion</p>
              </div>
            </div>
            <div class="tw-flex tw-items-start tw-gap-4 tw-opacity-50 tw-transition-opacity tw-duration-300" :class="{ 'tw-opacity-100': transaction.status === 'reconciled' }" v-if="transaction.reconciled_at">
              <div class="tw-w-10 tw-h-10 tw-bg-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-gray-600 tw-text-lg tw-flex-shrink-0" :class="{ 'tw-bg-indigo-600 tw-text-white': transaction.status === 'reconciled' }">
                <i class="pi pi-verified"></i>
              </div>
              <div>
                <h5 class="tw-m-0 tw-font-semibold tw-text-base tw-text-gray-900">Transaction Reconciled</h5>
                <p class="tw-m-0 tw-text-sm tw-text-gray-600">{{ formatFullDate(transaction.reconciled_at) }}</p>
                <small class="tw-text-gray-400 tw-text-xs">By {{ transaction.reconciled_by?.name || 'System' }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="tw-p-6 md:tw-p-8 tw-border-t tw-border-gray-200 tw-flex tw-justify-end tw-gap-4 tw-bg-gray-50 tw-rounded-b-[20px]">
        <Button
          label="Close"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="$emit('close')"
        />
        <Button
          label="Print"
          icon="pi pi-print"
          class="p-button-outlined"
          @click="printTransaction"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
 * Minimal CSS is needed when using Tailwind.
 * All custom classes have been removed from the template.
 */
@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>