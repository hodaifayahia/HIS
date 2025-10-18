<template>
  <tr>
    <td>{{ annex.id }}</td>
    <td>{{ annex.annex_name }}</td>
    <td>{{ annex.service_name }}</td> <td>{{ capitalizeFirstLetter(annex.created_by) }}</td> <td>{{ formatDateDisplay(annex.created_at) }}</td>
    <td>{{ annex.min_price }}</td> <td>{{ annex.max_price }}</td> <td v-if="contractState === 'pending'">
      <button class="btn btn-sm btn-warning" @click="emit('edit', annex)" title="Edit">
        <i class="fas fa-pencil-alt"></i>
      </button>
    </td>
    <td v-if="contractState === 'pending'">
      <button class="btn btn-sm btn-danger" @click="emit('delete', annex)" title="Delete">
        <i class="fas fa-trash-alt"></i>
      </button>
    </td>
    <td>
      <button class="btn btn-sm btn-info" @click="emit('view-details', annex.id)" title="Details">
        <i class="fas fa-eye me-1"></i> Details
      </button>
    </td>
  </tr>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  annex: {
    type: Object,
    required: true,
  },
  contractState: {
    type: String,
    required: true,
  }
});

const emit = defineEmits(['edit', 'delete', 'view-details']);

// Helper functions (could be extracted to a utility file if used globally)
const formatDateDisplay = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = String(date.getFullYear());
    return `${day}/${month}/${year}`;
  } catch (error) {
    console.error("Error formatting date:", error);
    return dateString;
  }
};

const capitalizeFirstLetter = (string) => {
  if (!string) return '';
  return String(string).charAt(0).toUpperCase() + String(string).slice(1);
};
</script>

<style scoped>
/* No specific styling needed here, table styles are handled in parent */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
.btn-warning {
  background-color: #ffc107;
  border-color: #ffc107;
  color: #212529; /* Dark text for warning button */
}
.btn-warning:hover {
  background-color: #e0a800;
  border-color: #d39e00;
}
.btn-danger {
  background-color: #dc3545;
  border-color: #dc3545;
}
.btn-danger:hover {
  background-color: #c82333;
  border-color: #bd2130;
}
.btn-info {
  background-color: #17a2b8;
  border-color: #17a2b8;
}
.btn-info:hover {
  background-color: #138496;
  border-color: #117a8b;
}
</style>