<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    service: {
        type: Object,
        required: true,
        default: () => ({
            id: null,
            image_url: '',
            name: '',
            description: '',
            start_date: '',
            end_date: '',
            agmentation: '',
            is_active: true
        })
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['edit', 'delete']);

const editService = () => {
    emit('edit', props.service);
};

const deleteService = () => {
    emit('delete', props.service.id);
};
</script>
<template>
  <tr class="table-body-row">
    <td class="table-data">{{ index + 1 }}</td>
    <td class="table-data">
      <img :src="service.image_url" :alt="service.name" class="service-image" />
    </td>
    <td class="table-data">{{ service.name }}</td>
    <td class="table-data">{{ service.start_date }}</td>
    <td class="table-data">{{ service.end_date }}</td>
    <td class="table-data">{{ service.agmentation }}</td>

    <td class="table-data status-cell">
      <span
        :class="{ 'status-indicator-active': service.is_active, 'status-indicator-inactive': !service.is_active }"
        class="status-indicator"
      ></span>
      {{ service.is_active ? "Active" : "Inactive" }}
    </td>

    <td class="table-data service-description">{{ service.description }}</td>
    <td class="table-data actions-column">
      <button @click="editService" class="action-button edit-button" title="Edit Service">
        <i class="fas fa-edit button-icon"></i>
      </button>
      <button @click="deleteService" class="action-button delete-button" title="Delete Service">
        <i class="fas fa-trash-alt button-icon"></i>
      </button>
    </td>
  </tr>
</template>

<style scoped>
/* Table Row and Data */
.table-body-row {
    border-bottom: 1px solid #e5e7eb; /* border-b border-gray-200 */
}

/* Styles for the status indicator span */
.status-indicator {
  display: inline-block; /* Allows setting width/height and margin */
  width: 10px; /* Size of the dot */
  height: 10px; /* Size of the dot */
  border-radius: 50%; /* Makes it a circle */
  margin-right: 8px; /* Space between the dot and the text */
  vertical-align: middle; /* Align with text */
}

.status-indicator-active {
  background-color: #28a745; /* A standard green for active */
}

.status-indicator-inactive {
  background-color: #dc3545; /* A standard red for inactive */
}

/* Optional: Style the parent cell for better alignment/appearance */
.status-cell {
  display: flex; /* Use flexbox to center content */
  align-items: center;
  justify-content: center;
  gap: 5px; /* Space between dot and text */
}
.table-body-row:last-child {
    border-bottom: none; /* No border for the last row */
}

.table-data {
    padding: 0.75rem 1.5rem; /* py-3 px-6 */
    vertical-align: middle; /* Align content nicely */
    word-break: break-word; /* Ensure long text wraps */
}

/* Service Image */
.service-image {
    width: 3rem; /* w-12 */
    height: 3rem; /* h-12 */
    border-radius: 0.25rem; /* rounded */
    object-fit: cover; /* object-cover */
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
    border: 1px solid #e5e7eb; /* border border-gray-200 */
}

/* Service Description (Optional: for potential truncation) */
.service-description {
    max-width: 250px; /* Limit width for descriptions */
    overflow: hidden;
    text-overflow: ellipsis; /* Add ellipsis for long descriptions */
    white-space: nowrap; /* Keep description on one line unless explicitly wrapped */
}

/* Actions Column */
.actions-column {
    text-align: center; /* Center the buttons */
    white-space: nowrap; /* Prevent buttons from wrapping */
}

.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem; /* p-2 */
    border-radius: 0.25rem; /* rounded-md */
    transition: background-color 0.2s ease-in-out;
    border: none;
    cursor: pointer;
    font-size: 0.875rem; /* text-sm */
    margin: 0 0.25rem; /* mx-1 */
}

.action-button .button-icon {
    font-size: 1rem; /* Adjust icon size as needed */
}

.edit-button {
    background-color: #3b82f6; /* bg-blue-500 */
    color: #ffffff; /* text-white */
}

.edit-button:hover {
    background-color: #2563eb; /* hover:bg-blue-600 */
}

.delete-button {
    background-color: #ef4444; /* bg-red-500 */
    color: #ffffff; /* text-white */
}

.delete-button:hover {
    background-color: #dc2626; /* hover:bg-red-600 */
}
</style>