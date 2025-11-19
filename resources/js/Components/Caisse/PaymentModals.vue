<template>
  <Dialog :visible="showOverpaymentModal" @update:visible="$emit('update:show-overpayment-modal', $event)" modal :style="{ width: '500px' }" header="Overpayment Surplus">
    <div class="tw-text-center tw-py-4">
      <div class="tw-mb-4">
        <i class="pi pi-exclamation-triangle tw-text-yellow-500 tw-text-4xl"></i>
      </div>
      <h3 class="tw-text-lg tw-font-semibold tw-mb-2">Overpayment Detected</h3>
      <p class="tw-text-gray-600 tw-mb-4">
        Required amount: {{ formatCurrency(overpaymentData.required) }}<br>
        Amount paid: {{ formatCurrency(overpaymentData.paid) }}<br>
        <strong class="tw-text-green-600">Surplus: {{ formatCurrency(overpaymentData.excess) }}</strong>
      </p>
      <div class="tw-mb-6">
        <div class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4 tw-text-center">
          <div class="tw-text-sm tw-text-blue-600 tw-mb-2">Return Amount Information</div>
          <div class="tw-text-2xl tw-font-bold tw-text-blue-800">
            {{ formatCurrency(overpaymentData.excess) }}
          </div>
          <div class="tw-text-xs tw-text-blue-500 tw-mt-1">
            Should be returned to patient
          </div>
        </div>
      </div>
      
      <div class="tw-flex tw-gap-3 tw-justify-center tw-flex-wrap">
        <Button
          label="Return Info"
          icon="pi pi-info-circle"
          class="p-button-help p-button-lg tw-text-lg tw-font-semibold tw-px-6 tw-py-3"
          @click="showReturnInfo"
          :disabled="processingOverpayment"
          v-tooltip.top="'Shows what should be returned to the patient'"
        />
        <Button
          label="Donate"
          icon="pi pi-heart"
          class="p-button-success"
          @click="$emit('handle-overpayment', 'donate')"
          :loading="processingOverpayment"
        />
        <Button
          label="Add to Patient Credit"
          icon="pi pi-wallet"
          class="p-button-info"
          @click="$emit('handle-overpayment', 'balance')"
          :loading="processingOverpayment"
        />
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="$emit('cancel-overpayment')"
          :disabled="processingOverpayment"
        />
      </div>
    </div>
  </Dialog>

  <Dialog :visible="showRefundModal" @update:visible="$emit('update:show-refund-modal', $event)" modal :style="{ width: '500px' }" header="Refund">
    <div class="tw-py-4">
      <div class="tw-mb-4">
        <h3 class="tw-text-lg tw-font-semibold tw-mb-2">Refund Transaction</h3>
        <p class="tw-text-gray-600 tw-text-sm">
          Transaction: {{ refundData.transaction?.reference ?? `#${refundData.transaction?.id}` }}<br>
          Original amount: {{ formatCurrency(refundData.transaction?.amount) }}
          <span v-if="refundAuthorization">
            <br>Authorization: {{ refundAuthorization.reason }}
            <br>Status: {{ refundAuthorization.status_text }}
          </span>
        </p>
      </div>

      <div class="tw-mb-4">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Amount to refund
          <span v-if="refundData.fixedAmount" class="tw-text-xs tw-text-blue-600">(authorized amount)</span>
        </label>
        <InputNumber
          :modelValue="refundData.amount"
          @update:modelValue="$emit('update:refund-amount', $event)"
          :min="0.01"
          :max="refundData.fixedAmount ? refundData.amount : refundData.transaction?.amount"
          :minFractionDigits="2"
          :maxFractionDigits="2"
          placeholder="0.00"
          class="tw-w-full"
          :class="{ 'p-invalid': refundData.errors.amount }"
          :disabled="refundData.fixedAmount"
        />
        <small v-if="refundData.errors.amount" class="p-error">{{ refundData.errors.amount }}</small>
        <small v-if="refundData.fixedAmount" class="tw-text-blue-600 tw-text-xs">
          The amount is fixed by the refund authorization
        </small>
      </div>

      <div class="tw-mb-6">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Reason for refund
        </label>
        <Textarea
          :modelValue="refundData.notes"
          @update:modelValue="$emit('update:refund-notes', $event)"
          rows="3"
          placeholder="Enter the reason for the refund..."
          class="tw-w-full"
        />
      </div>

      <div class="tw-flex tw-gap-3 tw-justify-end">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="$emit('close-refund')"
          :disabled="processingRefund"
        />
        <Button
          label="Confirm Refund"
          icon="pi pi-check"
          class="p-button-danger"
          @click="$emit('process-refund')"
          :loading="processingRefund"
        />
      </div>
    </div>
  </Dialog>

  <Dialog :visible="showUpdateModal" @update:visible="$emit('update:show-update-modal', $event)" modal :style="{ width: '500px' }" header="Edit Payment">
    <div class="tw-py-4">
      <div class="tw-mb-4">
        <h3 class="tw-text-lg tw-font-semibold tw-mb-2">Edit Transaction</h3>
        <p class="tw-text-gray-600 tw-text-sm">
          Transaction: {{ updateData.transaction?.reference }}<br>
          Original amount: {{ formatCurrency(updateData.transaction?.amount) }}
        </p>
      </div>

      <div class="tw-mb-4">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          New Amount
        </label>
        <InputNumber
          :modelValue="updateData.amount"
          @update:modelValue="$emit('update:update-amount', $event)"
          :min="0.01"
          :max="updateData.maxAmount"
          :minFractionDigits="2"
          :maxFractionDigits="2"
          placeholder="0.00"
          class="tw-w-full"
          :class="{ 'p-invalid': updateData.errors.amount }"
        />
        <small v-if="updateData.errors.amount" class="p-error">{{ updateData.errors.amount }}</small>
      </div>

      <div class="tw-mb-6">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Reason for modification
        </label>
        <Textarea
          :modelValue="updateData.notes"
          @update:modelValue="$emit('update:update-notes', $event)"
          rows="3"
          placeholder="Enter the reason for the modification..."
          class="tw-w-full"
        />
      </div>

      <div class="tw-flex tw-gap-3 tw-justify-end">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="$emit('close-update')"
          :disabled="processingUpdate"
        />
        <Button
          label="Confirm Modification"
          icon="pi pi-check"
          class="p-button-primary"
          @click="$emit('process-update')"
          :loading="processingUpdate"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import { useCurrencyFormatter } from '@/composables/useCurrencyFormatter'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  showOverpaymentModal: {
    type: Boolean,
    default: false
  },
  showRefundModal: {
    type: Boolean,
    default: false
  },
  showUpdateModal: {
    type: Boolean,
    default: false
  },
  overpaymentData: {
    type: Object,
    required: true
  },
  refundData: {
    type: Object,
    required: true
  },
  updateData: {
    type: Object,
    required: true
  },
  refundAuthorization: {
    type: Object,
    default: null
  },
  processingOverpayment: {
    type: Boolean,
    default: false
  },
  processingRefund: {
    type: Boolean,
    default: false
  },
  processingUpdate: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
  'handle-overpayment',
  'cancel-overpayment',
  'update:refund-amount',
  'update:refund-notes',
  'close-refund',
  'process-refund',
  'update:update-amount',
  'update:update-notes',
  'close-update',
  'process-update',
  'update:show-overpayment-modal',
  'update:show-refund-modal',
  'update:show-update-modal',
  'pay-global-amount'
])

const { formatCurrency } = useCurrencyFormatter()
const toast = useToast()

// Show return information and trigger global payment for needed amount
const showReturnInfo = () => {
  const required = props.overpaymentData.required
  
  // Emit event to trigger global payment with the required amount
  emit('pay-global-amount', required)
  
  toast.add({
    severity: 'success',
    summary: '💰 Global Payment Triggered',
    detail: `Processing global payment for: ${formatCurrency(required)} (the amount needed to pay).`,
    life: 6000,
    style: 'font-size: 16px; font-weight: bold;'
  })
}
</script>