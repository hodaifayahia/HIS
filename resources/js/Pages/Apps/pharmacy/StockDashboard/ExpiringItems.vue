<template>
  <div class="tw-p-4">
    <h4 class="tw-text-lg tw-font-semibold tw-mb-4">Expiring Items</h4>
    <DataTable :value="items" responsiveLayout="scroll">
      <Column field="product_name" header="Product"></Column>
      <Column field="batch_number" header="Batch"></Column>
      <Column field="expiry_date" header="Expiry Date"></Column>
      <Column field="quantity" header="Quantity"></Column>
    </DataTable>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

export default defineComponent({
  name: 'ExpiringItems',
  components: {
    DataTable,
    Column
  },
  props: {
    items: {
      type: Array,
      default: () => []
    }
  }
});
</script>

<style scoped>
/* Add any specific styles for ExpiringItems here */
</style>