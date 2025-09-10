<script setup>
import { defineProps, defineEmits, ref, computed } from 'vue';

const props = defineProps({
    medication: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    },
    isSelected: {
        type: Boolean,
        default: false
    },
    isFavorite: { // New prop to indicate if the medication is a favorite
        type: Boolean,
        default: false
    }
});

// Emits:
// 'toggle-selection': For the checkbox, to manage bulk selection state
// 'toggle-favorite': For the star button, to manage favorite status
// 'delete': Existing delete action
// 'edit': Existing edit action
const emit = defineEmits(['toggle-selection', 'delete', 'edit', 'toggle-favorite']);

const isHovered = ref(false);
const showDetails = ref(false);

// Computed property to apply row classes based on selection and favorite status
const rowClasses = computed(() => ({
    'selected': props.isSelected, // Class for multi-selection
    'is-favorite': props.isFavorite, // Class for favorite status
    'hovered': isHovered.value
}));

const handleDelete = (event) => {
    event.stopPropagation();
    emit('delete', props.medication.id, props.medication.designation);
};

const handleEdit = (event) => {
    event.stopPropagation();
    emit('edit', props.medication);
};

const handleToggleFavorite = (event) => {
    event.stopPropagation(); // Prevent row click from interfering
    emit('toggle-favorite', props.medication); // Emit to parent to handle API call
};

const handleToggleSelection = (event) => {
    event.stopPropagation(); // Prevent row click from interfering
    emit('toggle-selection', props.medication); // Emit to parent to update selectedMedications array
};

const toggleDetails = () => {
    showDetails.value = !showDetails.value;
};

// Helper function to get medication type color
const getTypeColor = (type) => {
    const colors = {
        'tablet': 'primary',
        'capsule': 'success',
        'syrup': 'warning',
        'injection': 'danger',
        'cream': 'info',
        'default': 'secondary'
    };

    const normalizedType = type?.toLowerCase() || '';
    for (const [key, color] of Object.entries(colors)) {
        if (normalizedType.includes(key)) return color;
    }
    return colors.default;
};
</script>

<template>
    <tr class="medication-row" :class="rowClasses" @mouseenter="isHovered = true" @mouseleave="isHovered = false">
        <td class="ps-4 row-checkbox-cell">
            <div class="form-check d-flex align-items-center">
                <input class="form-check-input" type="checkbox" :checked="isSelected"
                    @change="handleToggleSelection" :id="`medication-select-${medication.id}`">
                <label class="form-check-label ms-2" :for="`medication-select-${medication.id}`">
                    <span class="row-index">{{ index + 1 }}</span>
                </label>
            </div>
        </td>

        <td class="designation-cell">
            <div class="d-flex align-items-center">
                <div class="medication-icon me-3">
                    <i class="fas fa-pills text-primary"></i>
                </div>
                <div class="medication-info">
                    <div class="medication-name fw-bold">
                        {{ medication.designation }}
                    </div>
                    <div class="medication-subtitle text-muted small">
                        {{ medication.nom_commercial || 'No commercial name' }}
                    </div>
                </div>
            </div>
        </td>

        <td class="commercial-name-cell">
            <div class="commercial-name">
                {{ medication.nom_commercial || '-' }}
            </div>
        </td>

        <td class="type-cell">
            <span class="badge rounded-pill type-badge" :class="`bg-${getTypeColor(medication.type_medicament)}`">
                <i class="fas fa-tag me-1"></i>
                {{ medication.type_medicament }}
            </span>
        </td>

        <td class="form-cell">
            <div class="form-info">
                <span class="form-text">{{ medication.forme || 'N/A' }}</span>
            </div>
        </td>

        <td class="box-cell">
            <div class="box-info">
                <i class="fas fa-box text-muted me-1"></i>
                <span>{{ medication.boite_de || 'N/A' }}</span>
            </div>
        </td>

        <td class="pch-cell">
            <div class="pch-info">
                <code class="pch-code">{{ medication.code_pch || 'N/A' }}</code>
            </div>
        </td>

        <td class="actions-cell">
            <div class="action-buttons">
                <button class="btn btn-sm action-btn favorite-btn"
                    :class="isFavorite ? 'btn-warning' : 'btn-outline-warning'" @click.stop="handleToggleFavorite"
                    :title="isFavorite ? 'Remove from favorites' : 'Add to favorites'">
                    <i class="fas fa-star"></i>
                </button>

                <button class="btn btn-sm btn-outline-info action-btn info-btn" @click.stop="toggleDetails"
                    :title="showDetails ? 'Hide Details' : 'Show Details'">
                    <i class="fas" :class="showDetails ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>

                <button class="btn btn-sm btn-outline-primary action-btn edit-btn" @click.stop="handleEdit" title="Edit Medication">
                    <i class="fas fa-edit"></i>
                </button>

                <button class="btn btn-sm btn-outline-danger action-btn delete-btn" @click.stop="handleDelete"
                    title="Delete Medication">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </td>
    </tr>

    <tr v-if="showDetails" class="details-row">
        <td colspan="8" class="details-content">
            <div class="details-container">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6 class="detail-title">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Basic Information
                            </h6>
                            <div class="detail-list">
                                <div class="detail-item">
                                    <span class="detail-label">Designation:</span>
                                    <span class="detail-value">{{ medication.designation }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Commercial Name:</span>
                                    <span class="detail-value">{{ medication.nom_commercial || 'Not specified' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Type:</span>
                                    <span class="detail-value">{{ medication.type_medicament }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6 class="detail-title">
                                <i class="fas fa-prescription-bottle text-success me-2"></i>
                                Packaging Details
                            </h6>
                            <div class="detail-list">
                                <div class="detail-item">
                                    <span class="detail-label">Form:</span>
                                    <span class="detail-value">{{ medication.forme || 'Not specified' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Box Size:</span>
                                    <span class="detail-value">{{ medication.boite_de || 'Not specified' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">PCH Code:</span>
                                    <span class="detail-value">
                                        <code v-if="medication.code_pch">{{ medication.code_pch }}</code>
                                        <span v-else class="text-muted">Not specified</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
</template>

<style scoped>
/* Row Styles */
.medication-row {
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.medication-row:hover {
    background-color: rgba(0, 123, 255, 0.08) !important;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

/* Base style for selected row */
.medication-row.selected {
    background-color: rgba(25, 135, 84, 0.1) !important;
    border-left: 4px solid #198754;
}

/* Base style for favorite row */
.medication-row.is-favorite {
    background-color: #fff3cd !important; /* Light yellow for favorite */
    border-left: 4px solid #ffc107; /* Orange border for favorite */
}

/* When a row is both selected AND favorite, selected takes precedence in background but combine border */
.medication-row.selected.is-favorite {
    background-color: rgba(25, 135, 84, 0.15) !important; /* Slightly darker green-tinted background */
    border-left: 4px solid #198754; /* Keep selected border */
    /* You could also try a combined border like a gradient or a different color: */
    /* border-image: linear-gradient(to bottom, #198754, #ffc107) 1; */
}


.medication-row td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Row Number & Checkbox */
.row-checkbox-cell {
    width: 80px;
}

.row-index {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: #f8f9fa;
    border-radius: 50%;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.medication-row:hover .row-index {
    background: #e9ecef;
    color: #495057;
}

.form-check-input {
    min-width: 1.25em; /* Ensure checkbox is visible */
    min-height: 1.25em;
    margin-right: 0.5rem; /* Space between checkbox and number */
    cursor: pointer;
}


/* Designation Cell */
.designation-cell {
    min-width: 280px;
}

.medication-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 0.5rem;
    font-size: 1.25rem;
}

.medication-info .medication-name {
    color: #2c3e50;
    font-size: 1rem;
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.medication-subtitle {
    font-size: 0.85rem;
    color: #6c757d !important;
}

/* Commercial Name */
.commercial-name {
    font-weight: 500;
    color: #495057;
}

/* Type Badge */
.type-badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

/* Form Info */
.form-info .form-text {
    padding: 0.25rem 0.5rem;
    background: #f8f9fa;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #495057;
}

/* Box Info */
.box-info {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #495057;
}

/* PCH Code */
.pch-code {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
    color: #495057;
}

/* Action Buttons */
.actions-cell {
    width: 160px; /* Adjust width if needed for new button */
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
    justify-content: center;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15);
}

.info-btn:hover {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

.edit-btn:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.delete-btn:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

/* Favorite Button Specific Styles */
.favorite-btn.btn-warning {
    color: #fff;
    background-color: #ffc107;
    border-color: #ffc107;
}
.favorite-btn.btn-outline-warning {
    color: #ffc107;
    border-color: #ffc107;
}
.favorite-btn.btn-outline-warning:hover {
    background-color: #ffc107;
    color: #fff;
}


/* Details Row */
.details-row {
    background-color: #f8f9fa;
}

.details-content {
    padding: 0 !important;
}

.details-container {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-left: 4px solid #0d6efd;
}

.detail-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    height: 100%;
}

.detail-title {
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.detail-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.875rem;
}

.detail-value {
    font-weight: 400;
    color: #495057;
    text-align: right;
    max-width: 60%;
    word-break: break-word;
}

.details-actions {
    background: rgba(255, 255, 255, 0.5);
    border-radius: 0.375rem;
    padding: 0.75rem;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.1);
    }

    100% {
        transform: scale(1);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .medication-row td {
        padding: 0.75rem 0.5rem;
    }

    .designation-cell {
        min-width: 200px;
    }

    .action-buttons {
        flex-wrap: wrap;
        gap: 0.125rem;
    }

    .action-btn {
        width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
    }

    .details-container {
        padding: 1rem;
    }

    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .detail-value {
        max-width: 100%;
        text-align: left;
    }
}

/* Print Styles */
@media print {
    .action-buttons {
        display: none;
    }

    .medication-row:hover {
        background-color: transparent !important;
        transform: none;
        box-shadow: none;
    }
}
</style>