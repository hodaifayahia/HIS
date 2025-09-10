<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();


const props = defineProps({
    pavilion: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['edit', 'delete']);

// Format dates if needed (though pavilions don't have start/end dates in your schema)
const formattedCreatedAt = computed(() => {
    return new Date(props.pavilion.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
});
const GotoPavilionsService = (id) => {
    router.push({
        name: 'infrastructure.structure.services',
        params: { id }, // Use params instead of query
    });
}


</script>

<template>
    <tr
    :key="pavilion.id"
     v-on:click="GotoPavilionsService(pavilion.id)"
     class="modality-table-row">
        <td class="table-cell">{{ index + 1 }}</td>
        <td class="table-cell name-cell">
            <div class="name-display">
                <span class="pavilion-name">{{ pavilion.name }}</span>
            </div>
        </td>
        <td class="table-cell description-cell">{{ pavilion.description || 'N/A' }}</td>
        <td class="table-cell">{{ formattedCreatedAt }}</td>
        <td class="table-cell actions-cell">
            <div class="action-buttons">
                <button @click="emit('edit', pavilion)" class="action-button edit-button">
                    <i class="fas fa-edit button-icon"></i> <span class="button-text"></span>
                </button>
                <button @click="emit('delete', pavilion.id)" class="action-button delete-button">
                    <i class="fas fa-trash button-icon"></i> <span class="button-text"></span>
                </button>
            </div>
        </td>
    </tr>
</template>

<style scoped>
/* All styles copied directly from your provided block, with `primaryColor` replaced. */

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

/* Modality-specific styles (like image and status) are present but not used by pavilions */
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
    color: #2563eb; /* Replaced v-bind(primaryColor) with a hex value (blue-600 equivalent) */
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
    .button-text {
        display: inline;
        /* Show text on medium screens and up */
    }
}
</style>