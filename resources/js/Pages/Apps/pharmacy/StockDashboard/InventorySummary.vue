<template>
  <div class="tw-p-4">
    <h4 class="tw-text-lg tw-font-semibold tw-mb-4">Current Inventory Levels</h4>
    <DataTable :value="summary" responsiveLayout="scroll">
      <Column field="product_name" header="Product"></Column>
      <Column field="total_quantity" header="Total Quantity"></Column>
    </DataTable>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

export default defineComponent({
  name: 'InventorySummary',
  components: {
    DataTable,
    Column
  },
  props: {
    summary: {
      type: Array,
      default: () => []
    }
  }
});
</script>

<style scoped>
/* Add any specific styles for InventorySummary here */
</style>