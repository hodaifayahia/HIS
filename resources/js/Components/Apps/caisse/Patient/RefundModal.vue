<template>
  <Dialog :visible="visible" modal :style="{ width: '500px' }" header="Remboursement" @hide="$emit('close')">
    <div class="tw-py-4">
      <div class="tw-mb-4">
        <h3 class="tw-text-lg tw-font-semibold tw-mb-2">Rembourser la Transaction</h3>
        <p class="tw-text-gray-600 tw-text-sm">
          Transaction: {{ refundData.transaction?.reference ?? `#${refundData.transaction?.id}` }}<br>
          Montant original: {{ formatCurrency(refundData.transaction?.amount) }}
          <span v-if="refundAuthorization">
            <br>Autorisation: {{ refundAuthorization.reason }}
            <br>Statut: {{ refundAuthorization.status_text }}
          </span>
        </p>
      </div>

      <div class="tw-mb-4">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Montant à rembourser
          <span v-if="refundData.fixedAmount" class="tw-text-xs tw-text-blue-600">(montant autorisé)</span>
        </label>
        <InputNumber
          v-model="refundData.amount"
          :min="0.01"
          :max="refundData.transaction?.amount"
          :minFractionDigits="2"
          :maxFractionDigits="2"
          placeholder="0.00"
          class="tw-w-full"
          :class="{ 'p-invalid': refundData.errors.amount }"
          :disabled="refundData.fixedAmount"
        />
        <small v-if="refundData.errors.amount" class="p-error">{{ refundData.errors.amount }}</small>
      </div>

      <div class="tw-mb-6">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Raison du remboursement
        </label>
        <Textarea
          v-model="refundData.notes"
          rows="3"
          placeholder="Entrez la raison du remboursement..."
          class="tw-w-full"
        />
      </div>

      <div class="tw-flex tw-gap-3 tw-justify-end">
        <Button
          label="Annuler"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="$emit('close')"
          :disabled="processing"
        />
        <Button
          label="Confirmer Remboursement"
          icon="pi pi-check"
          class="p-button-danger"
          @click="$emit('process-refund')"
          :loading="processing"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';

const props = defineProps({
  visible: { type: Boolean, required: true },
  refundData: { type: Object, required: true },
  refundAuthorization: { type: Object, default: null },
  processing: { type: Boolean, required: true },
  formatCurrency: { type: Function, required: true },
});
const emit = defineEmits(['close', 'process-refund']);
</script>

<style scoped>
/* Scoped styles here */
</style>