<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    modalityType: {
        type: Object,
        required: true,
        default: () => ({
            id: null,
            image_url: '',
            name: '',
            description: ''
        })
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['edit', 'delete']);

const primaryColor = '#2563eb'; // Defined here for consistency with the first component

const editModalityType = () => {
    emit('edit', props.modalityType);
};

const deleteModalityType = () => {
    emit('delete', props.modalityType.id);
};
</script>

<template>
    <tr class="modality-table-row">
        <td class="table-cell">{{ index + 1 }}</td>
        <td class="table-cell">
            <img :src="modalityType.image_url" :alt="modalityType.name" class="modality-image" v-if="modalityType.image_url" />
            <span v-else class="no-image-placeholder">N/A</span>
        </td>
        <td class="table-cell font-bold" :style="{ color: primaryColor }">{{ modalityType.name }}</td>
        <td class="table-cell modality-type-description">{{ modalityType.description }}</td>
        <td class="table-cell actions-cell">
            <button @click="editModalityType" class="action-button edit-button" title="Edit Modality Type">
                <i class="fas fa-edit"></i> <span class="button-text"></span>
            </button>
            <button @click="deleteModalityType" class="action-button delete-button" title="Delete Modality Type">
                <i class="fas fa-trash-alt"></i> <span class="button-text"></span>
            </button>
        </td>
    </tr>
</template>

<style scoped>
.modality-table-row {
    border-bottom: 1px solid #e2e8f0; /* border-b border-gray-200 */
}

.modality-table-row:last-child {
    border-bottom: none;
}

.table-cell {
    padding: 0.75rem 1.5rem; /* py-3 px-6 */
    vertical-align: middle; /* Ensures content is centered vertically */
    word-break: break-word; /* Prevents long words from breaking layout */
}

.modality-image {
    width: 40px; /* w-10 */
    height: 40px; /* h-10 */
    border-radius: 9999px; /* rounded-full */
    object-fit: cover; /* object-cover */
    border: 1px solid #e2e8f0;
}

.no-image-placeholder {
    color: #6b7280;
    font-style: italic;
    font-size: 0.85rem;
    /* Added to match the styling of the first component's N/A placeholder */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px; /* Adjusted to match modality-image width */
    height: 40px; /* Adjusted to match modality-image height */
    background-color: #f0f0f0; /* A light background */
    border-radius: 9999px; /* Rounded for consistency */
    border: 1px dashed #cccccc; /* A subtle dashed border */
}

.modality-type-description {
    max-width: 250px; /* Limit width for descriptions */
    overflow: hidden;
    text-overflow: ellipsis; /* Add ellipsis for long descriptions */
    white-space: nowrap; /* Keep description on one line unless explicitly wrapped */
}

.actions-cell {
    text-align: center; /* text-center */
    white-space: nowrap; /* Prevent buttons from wrapping */
}

.action-button {
    display: inline-flex; /* inline-flex */
    align-items: center; /* items-center */
    padding: 0.5rem 0.75rem; /* px-3 py-2 */
    font-size: 0.875rem; /* text-sm */
    font-weight: 500; /* font-medium */
    border-radius: 0.375rem; /* rounded-md */
    border: none;
    cursor: pointer;
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    margin: 0 0.25rem; /* mx-1 */
}

.action-button .button-icon {
    margin-right: 0.3rem; /* mr-1 */
    /* The first component's button-icon class was essentially empty.
       We'll keep the margin for consistency if you decide to add text later.
       For now, the icon itself is directly styled by the font-size of the parent. */
}

.edit-button {
    background-color: #e0f2fe; /* bg-blue-100 */
    color: v-bind(primaryColor); /* text-blue-600 */
}

.edit-button:hover {
    background-color: #bfdbfe; /* hover:bg-blue-200 */
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.delete-button {
    background-color: #fee2e2; /* bg-red-100 */
    color: #dc2626; /* text-red-600 */
}

.delete-button:hover {
    background-color: #fecaca; /* hover:bg-red-200 */
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.button-text {
    display: none; /* Hide text on smaller screens */
}

@media (min-width: 768px) { /* md:block */
    .button-text {
        display: inline; /* Show text on medium screens and up */
    }
}
</style>