<script setup>
import { defineProps, defineEmits, computed } from "vue";

const props = defineProps({
  contract: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["delete-contract", "view-details"]);

const getStatusBadgeClass = computed(() => {
  switch (props.contract.status) {
    case "Active":
      return "bg-success";
    case "Expired":
      return "bg-danger";
    case "Pending":
      return "bg-warning text-dark";
    default:
      return "bg-secondary";
  }
});

const handleDelete = () => {
  emit("delete-contract", props.contract);
};

const handleViewDetails = () => {
  emit("view-details", props.contract);
};
</script>

<template>
  <tr @click="handleViewDetails" class="table-row">
    <td class="table-cell">{{ contract.id }}</td>
    <td class="table-cell">{{ contract.contract_name }}</td>
    <td class="table-cell">{{ contract.start_date }}</td>
    <td class="table-cell">{{ contract.end_date || "No end date" }}</td>
    <td class="table-cell">
      <span :class="['badge', getStatusBadgeClass]">
        {{ contract.status }}
      </span>
    </td>
<!--     
    <td class="table-cell actions-cell">
      <button
        v-if="contract.status === 'Pending'"
        class="btn btn-sm btn-danger action-button delete-button"
        @click="handleDelete"
      >
        <i class="fas fa-trash-alt"></i>
      </button>
    </td> -->
  </tr>
</template>

<style scoped>
/* You can add specific styles for your table cells/rows here if needed */
.table-row {
  transition: background-color 0.2s ease;
  cursor: pointer;
}

.table-row:hover {
  background-color: #f8f9fa; /* Light grey on hover */
}

.table-cell {
  padding: 0.75rem;
  vertical-align: middle;
}

.actions-cell {
  white-space: nowrap; /* Prevent buttons from wrapping */
}

.action-button {
  margin: 0 0.25rem;
}
</style>