<template>
  <div class="tw-p-4">
    <h4 class="tw-text-lg tw-font-semibold tw-mb-4">Stock Movement History</h4>
    <DataTable :value="movements" responsiveLayout="scroll">
      <Column field="product_name" header="Product"></Column>
      <Column field="movement_type" header="Type"></Column>
      <Column field="quantity" header="Quantity"></Column>
      <Column field="movement_date" header="Date"></Column>
      <Column field="status" header="Status"></Column>
    </DataTable>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

export default defineComponent({
  name: 'StockMovementHistory',
  components: {
    DataTable,
    Column
  },
  props: {
    movements: {
      type: Array,
      default: () => []
    }
  }
});
</script>

<style scoped>
/* Add any specific styles for StockMovementHistory here */
</style>