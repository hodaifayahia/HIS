<template>
  <Dialog :visible="visible" :style="{ width: '500px' }" header="Surplus de Paiement" :modal="true" @hide="$emit('cancel-overpayment')">
    <div class="tw-text-center tw-py-4">
      <div class="tw-mb-4">
        <i class="pi pi-exclamation-triangle tw-text-yellow-500 tw-text-4xl"></i>
      </div>
      <h3 class="tw-text-lg tw-font-semibold tw-mb-2">Paiement Excédentaire Détecté</h3>
      <p class="tw-text-gray-600 tw-mb-4">
        Montant requis: {{ formatCurrency(overpaymentData.required) }}<br>
        Montant payé: {{ formatCurrency(overpaymentData.paid) }}<br>
        <strong class="tw-text-green-600">Surplus: {{ formatCurrency(overpaymentData.excess) }}</strong>
      </p>
      <p class="tw-text-sm tw-text-gray-500 tw-mb-6">
        Que souhaitez-vous faire avec le surplus?
      </p>
      
      <div class="tw-flex tw-gap-3 tw-justify-center">
        <Button
          label="Faire un Don"
          icon="pi pi-heart"
          class="p-button-success"
          @click="$emit('handle-overpayment', 'donate')"
          :loading="processing"
        />
        <Button
          label="Ajouter au Crédit Patient"
          icon="pi pi-wallet"
          class="p-button-info"
          @click="$emit('handle-overpayment', 'balance')"
          :loading="processing"
        />
        <Button
          label="Annuler"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="$emit('cancel-overpayment')"
          :disabled="processing"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
  visible: { type: Boolean, required: true },
  overpaymentData: { type: Object, required: true },
  processing: { type: Boolean, required: true },
  formatCurrency: { type: Function, required: true },
});
const emit = defineEmits(['handle-overpayment', 'cancel-overpayment']);
</script>

<style scoped>
/* Scoped styles here */
</style>