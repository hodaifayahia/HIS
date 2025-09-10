<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    prescription: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['view', 'edit', 'delete']);

// Reactive state
const expanded = ref(false);

// Computed properties
const statusClass = computed(() => {
    return props.prescription.is_active ? 'status-active' : 'status-inactive';
});

const statusText = computed(() => {
    return props.prescription.is_active ? 'Active' : 'Inactive';
});

const hospitalizationClass = computed(() => {
    return props.prescription.requires_hospitalization ? 'status-required' : 'status-not-required';
});

const hospitalizationText = computed(() => {
    return props.prescription.requires_hospitalization ? 'Required' : 'Not Required';
});

const socialSecurityClass = computed(() => {
    return props.prescription.is_social_security_reimbursable ? 'status-reimbursable' : 'status-not-reimbursable';
});

const socialSecurityText = computed(() => {
    return props.prescription.is_social_security_reimbursable ? 'Reimbursable' : 'Not Reimbursable';
});

const formattedPrice = computed(() => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(props.prescription.public_price || 0);
});

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const formattedDuration = computed(() => {
    const minutes = props.prescription.default_duration_minutes;
    if (!minutes) return 'N/A';
    
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    
    if (hours > 0) {
        return remainingMinutes > 0 ? `${hours}h ${remainingMinutes}m` : `${hours}h`;
    }
    return `${minutes}m`;
});

const paymentTypeDisplay = computed(() => {
    const paymentTypes = {
        'cash': 'Cash',
        'card': 'Card',
        'insurance': 'Insurance',
        'mixed': 'Mixed'
    };
    return paymentTypes[props.prescription.default_payment_type] || props.prescription.default_payment_type || 'N/A';
});

const nightTariffStatus = computed(() => {
    return props.prescription.Tarif_de_nuit_is_active ? 'Active' : 'Inactive';
});

// Event handlers
const toggleExpanded = () => {
    expanded.value = !expanded.value;
};

const handleView = () => {
    emit('view', props.prescription);
};

const handleEdit = () => {
    emit('edit', props.prescription);
};

const handleDelete = () => {
    emit('delete', props.prescription.id);
};
</script>

<template>
    <!-- Main row -->
    <tr class="table-row">
        <td class="table-cell expand-cell">
            <button 
                @click="toggleExpanded"
                class="expand-button"
                :class="{ expanded: expanded }"
            >
                <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </td>
        
        <td class="table-cell">
            <span class="row-number">{{ index + 1 }}</span>
        </td>
        
        <td class="table-cell">
            <div class="prescription-info">
                <div class="prescription-name">{{ prescription.name }}</div>
                <div class="prescription-description" v-if="prescription.description">
                    {{ prescription.description.substring(0, 50) }}{{ prescription.description.length > 50 ? '...' : '' }}
                </div>
            </div>
        </td>
        
        <td class="table-cell">
            <div class="codes-info">
                <div class="code-item">
                    <span class="code-label">Internal:</span>
                    <span class="code-badge internal-code">{{ prescription.internal_code || 'N/A' }}</span>
                </div>
                <div class="code-item">
                    <span class="code-label">Billing:</span>
                    <span class="code-badge billing-code">{{ prescription.billing_code || 'N/A' }}</span>
                </div>
            </div>
        </td>
        
        <td class="table-cell">
            <div class="service-info">
                <div class="service-name">{{ prescription.service?.name || 'N/A' }}</div>
                <div class="specialization-name">{{ prescription.specialization?.name || 'N/A' }}</div>
            </div>
        </td>
        
        <td class="table-cell">
            <div class="financial-info">
                <div class="price-main">{{ formattedPrice }}</div>
                <div class="financial-details">
                    <small v-if="prescription.vat_rate" class="vat-rate">VAT: {{ prescription.vat_rate }}%</small>
                    <small class="payment-type">{{ paymentTypeDisplay }}</small>
                </div>
            </div>
        </td>
        
        <td class="table-cell">
            <span class="duration">{{ formattedDuration }}</span>
        </td>
        
        <!-- Active Status Column -->
        <td class="table-cell status-cell">
            <span :class="['status-badge', statusClass]">
                <i class="fas" :class="prescription.is_active ? 'fa-check-circle' : 'fa-times-circle'"></i>
                {{ statusText }}
            </span>
        </td>
        
        <!-- Hospitalization Status Column -->
        <td class="table-cell status-cell">
            <span :class="['status-badge', hospitalizationClass]">
                <i class="fas" :class="prescription.requires_hospitalization ? 'fa-hospital' : 'fa-home'"></i>
                {{ hospitalizationText }}
            </span>
            <div v-if="prescription.requires_hospitalization && prescription.default_hosp_nights" class="status-detail">
                <small>{{ prescription.default_hosp_nights }} nights</small>
            </div>
        </td>
        
        <!-- Social Security Status Column -->
        <td class="table-cell status-cell">
            <span :class="['status-badge', socialSecurityClass]">
                <i class="fas" :class="prescription.is_social_security_reimbursable ? 'fa-shield-alt' : 'fa-shield'"></i>
                {{ socialSecurityText }}
            </span>
        </td>
        
        <td class="table-cell actions-cell">
            <div class="action-buttons">
                <button 
                    @click="handleView"
                    class="action-button view-button"
                    title="View Details"
                >
                    <i class="fas fa-eye"></i>
                </button>
                
                <button 
                    @click="handleEdit"
                    class="action-button edit-button"
                    title="Edit Prescription"
                >
                    <i class="fas fa-edit"></i>
                </button>
                
                <button 
                    @click="handleDelete"
                    class="action-button delete-button"
                    title="Delete Prescription"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>

    <!-- Expanded details row -->
    <tr v-if="expanded" class="expanded-row">
        <td colspan="11" class="expanded-content">
            <div class="expanded-details">
                <div class="details-grid">
                    <div class="detail-section">
                        <h4 class="detail-title">
                            <i class="fas fa-dollar-sign"></i>
                            Financial Details
                        </h4>
                        <div class="detail-items">
                            <div class="detail-item">
                                <span class="detail-label">Consumables Cost:</span>
                                <span class="detail-value">{{ formatCurrency(prescription.consumables_cost) }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Min Payment Amount:</span>
                                <span class="detail-value">{{ formatCurrency(prescription.min_versement_amount) }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Default Payment Type:</span>
                                <span class="detail-value">{{ paymentTypeDisplay }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4 class="detail-title">
                            <i class="fas fa-bed"></i>
                            Hospitalization Details
                        </h4>
                        <div class="detail-items">
                            <div class="detail-item">
                                <span class="detail-label">Required:</span>
                                <span class="detail-value">{{ hospitalizationText }}</span>
                            </div>
                            <div class="detail-item" v-if="prescription.requires_hospitalization">
                                <span class="detail-label">Default Nights:</span>
                                <span class="detail-value">{{ prescription.default_hosp_nights || 'N/A' }}</span>
                            </div>
                            <div class="detail-item" v-if="prescription.required_modality_type_id">
                                <span class="detail-label">Modality Type ID:</span>
                                <span class="detail-value">{{ prescription.required_modality_type_id }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4 class="detail-title">
                            <i class="fas fa-moon"></i>
                            Night Tariff
                        </h4>
                        <div class="detail-items">
                            <div class="detail-item">
                                <span class="detail-label">Night Rate:</span>
                                <span class="detail-value">{{ formatCurrency(prescription.Tarif_de_nuit) }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Night Tariff Status:</span>
                                <span :class="['detail-value', 'status-badge', prescription.Tarif_de_nuit_is_active ? 'status-active' : 'status-inactive']">
                                    {{ nightTariffStatus }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4 class="detail-title">
                            <i class="fas fa-info-circle"></i>
                            Additional Information
                        </h4>
                        <div class="detail-items">
                            <div class="detail-item">
                                <span class="detail-label">VAT Rate:</span>
                                <span class="detail-value">{{ prescription.vat_rate ? prescription.vat_rate + '%' : 'N/A' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Duration:</span>
                                <span class="detail-value">{{ formattedDuration }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
</template>

<style scoped>
.table-row {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s ease;
}

.table-row:hover {
    background-color: #f9fafb;
}

.table-cell {
    padding: 1rem;
    vertical-align: top;
}

.expand-cell {
    width: 40px;
    text-align: center;
    padding: 0.5rem;
}

.expand-button {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
    color: #6b7280;
}

.expand-button:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.expand-button.expanded {
    color: #3b82f6;
}

.row-number {
    font-weight: 600;
    color: #6b7280;
}

.prescription-info {
    min-width: 200px;
}

.prescription-name {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.prescription-description {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.4;
}

.codes-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.code-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.code-label {
    font-size: 0.75rem;
    color: #6b7280;
    min-width: 50px;
}

.code-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.internal-code {
    background-color: #dbeafe;
    color: #1e40af;
}

.billing-code {
    background-color: #d1fae5;
    color: #065f46;
}

.service-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-name {
    font-weight: 600;
    color: #374151;
}

.specialization-name {
    font-size: 0.875rem;
    color: #6b7280;
}

.financial-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.price-main {
    font-weight: 600;
    color: #059669;
    font-size: 1rem;
}

.financial-details {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.vat-rate, .payment-type {
    color: #6b7280;
    font-size: 0.75rem;
}

.duration {
    font-weight: 500;
    color: #374151;
}

/* Status cell styles */
.status-cell {
    text-align: center;
    min-width: 120px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}

.status-detail {
    margin-top: 0.25rem;
}

.status-detail small {
    color: #6b7280;
    font-size: 0.625rem;
}

/* Active Status */
.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background-color: #fee2e2;
    color: #991b1b;
}

/* Hospitalization Status */
.status-required {
    background-color: #fef3c7;
    color: #92400e;
}

.status-not-required {
    background-color: #f3f4f6;
    color: #374151;
}

/* Social Security Status */
.status-reimbursable {
    background-color: #e0e7ff;
    color: #3730a3;
}

.status-not-reimbursable {
    background-color: #f1f5f9;
    color: #475569;
}

.actions-cell {
    text-align: center;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.view-button {
    background-color: #8dd891;
    color: #ffffff;
}

.view-button:hover {
    background-color: rgb(106, 214, 138);
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.edit-button {
    background-color: #e0f2fe;
    color: #0284c7;
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

/* Expanded row styles */
.expanded-row {
    background-color: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.expanded-content {
    padding: 1.5rem;
}

.expanded-details {
    max-width: 100%;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.detail-section {
    background-color: #ffffff;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
}

.detail-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.detail-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
}

.detail-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    font-size: 0.875rem;
    color: #374151;
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .status-badge {
        font-size: 0.625rem;
        padding: 0.25rem 0.5rem;
    }
    
    .status-cell {
        min-width: 100px;
    }
}

@media (max-width: 768px) {
    .table-cell {
        padding: 0.5rem;
    }
    
    .status-badge {
        font-size: 0.625rem;
        padding: 0.25rem 0.375rem;
    }
    
    .status-cell {
        min-width: 80px;
    }
    
    .codes-info {
        gap: 0.25rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .action-button {
        width: 1.75rem;
        height: 1.75rem;
        font-size: 0.75rem;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .detail-section {
        padding: 0.75rem;
    }
}
</style>
