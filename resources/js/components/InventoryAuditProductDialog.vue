<template>
  <Dialog
    :visible="visible"
    modal
    header="Inventory Audit - Product Details"
    :style="{ width: '40rem' }"
    :closable="true"
    @update:visible="$emit('close')"
  >
    <div v-if="product" class="tw-p-4">
      <div class="tw-space-y-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Product Name</label>
          <p class="tw-text-gray-900">{{ product.name }}</p>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Code</label>
          <p class="tw-text-gray-900">{{ product.code }}</p>
        </div>

        <div v-if="product.description">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Description</label>
          <p class="tw-text-gray-900">{{ product.description }}</p>
        </div>
      </div>
    </div>

    <template #footer>
      <Button
        label="Close"
        icon="pi pi-times"
        class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-lg tw-px-6 tw-py-3"
        @click="$emit('close')"
      />
    </template>
  </Dialog>
</template>

<script>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

export default {
  name: 'InventoryAuditProductDialog',
  components: {
    Dialog,
    Button
  },
  props: {
    visible: {
      type: Boolean,
      required: true
    },
    product: {
      type: Object,
      default: null
    }
  },
  emits: ['close']
};
</script>
