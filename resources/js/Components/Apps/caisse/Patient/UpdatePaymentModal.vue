<template>
  <Dialog :visible="visible" modal :style="{ width: '500px' }" header="Modifier le Paiement" @hide="$emit('close')">
    <div class="tw-py-4">
      <div class="tw-mb-4">
        <h3 class="tw-text-lg tw-font-semibold tw-mb-2">Modifier la Transaction</h3>
        <p class="tw-text-gray-600 tw-text-sm">
          Transaction: {{ updateData.transaction?.reference }}<br>
          Montant original: {{ formatCurrency(updateData.transaction?.amount) }}
        </p>
      </div>

      <div class="tw-mb-4">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Nouveau Montant
        </label>
        <InputNumber
          v-model="updateData.amount"
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
          Raison de la modification
        </label>
        <Textarea
          v-model="updateData.notes"
          rows="3"
          placeholder="Entrez la raison de la modification..."
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
          label="Confirmer Modification"
          icon="pi pi-check"
          class="p-button-primary"
          @click="$emit('process-update')"
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
  updateData: { type: Object, required: true },
  processing: { type: Boolean, required: true },
  formatCurrency: { type: Function, required: true },
});
const emit = defineEmits(['close', 'process-update']);
</script>

<style scoped>
/* Scoped styles here */
</style>