<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  modality: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['edit', 'delete']);

const primaryColor = '#2563eb';
</script>

<template>
  <tr class="modality-table-row">
    <td class="table-cell">{{ props.index + 1 }}</td>
    <td class="table-cell">
      <span class="no-image-placeholder">N/A</span>
    </td>
    <td class="table-cell font-bold" :style="{ color: primaryColor }">{{ props.modality.name }}</td>
    <td class="table-cell">{{ props.modality.internal_code }}</td>
    <td class="table-cell">{{ props.modality.service?.name || 'N/A' }}</td>
    <td class="table-cell">{{ props.modality.integration_protocol || 'N/A' }}</td>
    <td class="table-cell modality-connection-config-cell">{{ props.modality.connection_configuration || 'N/A' }}</td>
    <td class="table-cell">{{ props.modality.data_retrieval_method || 'N/A' }}</td>
    <td class="table-cell">{{ props.modality.ip_address || 'N/A' }}</td>
    <td class="table-cell">
      <span :class="{
        'badge bg-success': modality.operational_status === 'Working',
        'badge bg-danger': modality.operational_status === 'Not Working',
        'badge bg-warning': modality.operational_status === 'In Maintenance'
      }">
        {{ modality.operational_status }}
      </span>

    </td>
    <td class="table-cell actions-cell">
      <button @click="emit('edit', props.modality)" class="action-button edit-button">
        <i class="fas fa-edit"></i> <span class="button-text"></span>
      </button>
      <button @click="emit('delete', props.modality.id)" class="action-button delete-button">
        <i class="fas fa-trash-alt"></i> <span class="button-text"></span>
      </button>
    </td>
  </tr>
</template>

<style scoped>
.modality-table-row {
  border-bottom: 1px solid #e2e8f0;
  /* border-b border-gray-200 */
}

.modality-table-row:last-child {
  border-bottom: none;
}

.table-cell {
  padding: 0.75rem 1.5rem;
  /* py-3 px-6 */
  vertical-align: middle;
  /* Ensures content is centered vertically */
  word-break: break-word;
  /* Prevents long words from breaking layout */
}

.modality-image {
  width: 40px;
  /* w-10 */
  height: 40px;
  /* h-10 */
  border-radius: 9999px;
  /* rounded-full */
  object-fit: cover;
  /* object-cover */
  border: 1px solid #e2e8f0;
}

.no-image-placeholder {
  color: #6b7280;
  font-style: italic;
  font-size: 0.85rem;
}

.status-operational {
  display: inline-block;
  width: 90px;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  /* rounded-full */
  background-color: #d1fae5;
  /* bg-green-100 */
  color: #065f46;
  /* text-green-800 */
  font-weight: 600;
  /* font-semibold */
  font-size: 0.75rem;
  /* text-xs */
}

.status-inactive {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  /* rounded-full */
  background-color: #fee2e2;
  /* bg-red-100 */
  color: #991b1b;
  /* text-red-800 */
  font-weight: 600;
  /* font-semibold */
  font-size: 0.75rem;
  /* text-xs */
}

.actions-cell {
  text-align: center;
  /* text-center */
  white-space: nowrap;
  /* Prevent buttons from wrapping */
}

.action-button {
  display: inline-flex;
  /* inline-flex */
  align-items: center;
  /* items-center */
  padding: 0.5rem 0.75rem;
  /* px-3 py-2 */
  font-size: 0.875rem;
  /* text-sm */
  font-weight: 500;
  /* font-medium */
  border-radius: 0.375rem;
  /* rounded-md */
  border: none;
  cursor: pointer;
  transition: background-color 0.2s ease, box-shadow 0.2s ease;
  margin: 0 0.25rem;
  /* mx-1 */
}

.action-button .button-icon {
  margin-right: 0.3rem;
  /* mr-1 */
}

.edit-button {
  background-color: #e0f2fe;
  /* bg-blue-100 */
  color: v-bind(primaryColor);
  /* text-blue-600 */
}

.edit-button:hover {
  background-color: #bfdbfe;
  /* hover:bg-blue-200 */
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.delete-button {
  background-color: #fee2e2;
  /* bg-red-100 */
  color: #dc2626;
  /* text-red-600 */
}

.delete-button:hover {
  background-color: #fecaca;
  /* hover:bg-red-200 */
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.button-text {
  display: none;
  /* Hide text on smaller screens */
}

@media (min-width: 768px) {

  /* md:block */
  .button-text {
    display: inline;
    /* Show text on medium screens and up */
  }
}
</style>