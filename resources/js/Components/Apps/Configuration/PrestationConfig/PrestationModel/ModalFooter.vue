<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    loading: {
        type: Boolean,
        default: false
    },
    isEditMode: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['cancel', 'save']);
</script>

<template>
    <div class="modal-footer">
        <button @click="emit('cancel')" type="button" class="cancel-button" :disabled="loading">
            Cancel
        </button>
        <button @click="emit('save')" type="submit" class="submit-button" :disabled="loading">
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas" :class="isEditMode ? 'fa-save' : 'fa-plus'"></i>
            {{ loading ? 'Saving...' : (isEditMode ? 'Update Prestation' : 'Create Prestation') }}
        </button>
    </div>
</template>

<style scoped>
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem 2rem 2rem;
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

.cancel-button,
.submit-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-size: 0.875rem;
}

.cancel-button {
    background-color: #ffffff;
    color: #374151;
    border: 2px solid #d1d5db;
}

.cancel-button:hover:not(:disabled) {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.submit-button {
    background-color: #2563eb;
    color: #ffffff;
}

.submit-button:hover:not(:disabled) {
    background-color: #1d4ed8;
}

.cancel-button:disabled,
.submit-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .modal-footer {
        flex-direction: column;
        padding: 1.5rem;
    }

    .cancel-button,
    .submit-button {
        width: 100%;
        justify-content: center;
    }
}
</style>