<script setup>
import { computed } from 'vue';

const props = defineProps({
    roomType: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['edit', 'delete']);

const formattedCreatedAt = computed(() => {
    return new Date(props.roomType.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
});

// Computed property to determine the image source
const displayImageUrl = computed(() => {
    // Provide a default image path. Make sure this image exists in your public directory.
    // Example: public/images/default-room-type.png
    return props.roomType.image_url || '/images/default-room-type.png';
});

// Computed property to check if an image URL is provided (not using default)
const hasImageUrl = computed(() => {
    return !!props.roomType.image_url;
});
</script>

<template>
    <tr class="modality-table-row">
        <td class="table-cell">{{ index + 1 }}</td>
        <td class="table-cell image-cell"> <div class="image-wrapper">
                <img v-if="hasImageUrl" :src="displayImageUrl" :alt="roomType.name" class="room-type-image" />
                <div v-else class="no-image-placeholder">
                    <i class="fas fa-image no-image-icon"></i>
                    <span>No Image</span>
                </div>
            </div>
        </td>
        <td class="table-cell name-cell">
            <span class="room-type-name">{{ roomType.name }}</span>
        </td>
        <td class="table-cell description-cell">{{ roomType.description || 'N/A' }}</td>
        <td class="table-cell description-cell">{{ roomType.service_name || 'N/A' }}</td>
        <td class="table-cell description-cell">{{ roomType.room_type || 'N/A' }}</td>
        <td class="table-cell">{{ formattedCreatedAt }}</td>
        <td class="table-cell actions-cell">
            <div class="action-buttons">
                <button @click="emit('edit', roomType)" class="action-button edit-button">
                    <i class="fas fa-edit button-icon"></i> <span class="button-text">Edit</span>
                </button>
                <button @click="emit('delete', roomType.id)" class="action-button delete-button">
                    <i class="fas fa-trash button-icon"></i> <span class="button-text">Delete</span>
                </button>
            </div>
        </td>
    </tr>
</template>

<style scoped>
/* Reusing general table row/cell and button styles from PavilionsListItem or common styles */
.modality-table-row {
    border-bottom: 1px solid #e2e8f0;
}

.modality-table-row:last-child {
    border-bottom: none;
}

.table-cell {
    padding: 0.75rem 1.5rem;
    vertical-align: middle;
    word-break: break-word;
    color: #475569;
}

/* Specific image cell styling */
.image-cell {
    width: 80px; /* Adjust width as needed for the image/placeholder */
    min-width: 80px;
    text-align: center;
}

.image-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 3rem; /* w-12 */
    height: 3rem; /* h-12 */
    margin: 0 auto; /* Center the image/placeholder */
}

/* Renamed from .service-image to .room-type-image for clarity */
.room-type-image {
    width: 100%;
    height: 100%;
    border-radius: 0.25rem; /* rounded */
    object-fit: cover; /* object-cover */
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
    border: 1px solid #e5e7eb; /* border border-gray-200 */
}

/* Styles for the default image placeholder */
.no-image-placeholder {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
    background-color: #f1f5f9; /* Light gray background */
    border: 1px dashed #cbd5e1; /* Dashed border */
    border-radius: 0.25rem;
    color: #94a3b8; /* Gray text/icon color */
    font-size: 0.75rem;
    line-height: 1.2;
    text-align: center;
}

.no-image-icon {
    font-size: 1.5rem; /* Larger icon */
    margin-bottom: 0.25rem;
}


.name-cell {
    font-weight: 600;
    color: #1e293b;
}

.description-cell {
    font-size: 0.9rem;
    color: #64748b;
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.actions-cell {
    text-align: center;
    white-space: nowrap;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-button {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    margin: 0 0.25rem;
}

.action-button .button-icon {
    margin-right: 0.3rem;
}

/* The button text is hidden by default on small screens, adjust if you want to always show icons only */
.edit-button {
    background-color: #e0f2fe;
    color: #2563eb;
}

.edit-button:hover {
    background-color: #bfdbfe;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.delete-button {
    background-color: #fee2e2;
    color: #dc2626;
}

.delete-button:hover {
    background-color: #fecaca;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.button-text {
    display: none; /* Hidden on small screens initially based on your original media query */
}

@media (min-width: 768px) {
    .button-text {
        display: inline; /* Shown on medium screens and up */
    }
}
</style>