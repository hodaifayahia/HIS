<script setup>
import { computed } from 'vue';

// Utility function to parse currency strings
const parseCurrency = (value) => {
    if (typeof value === 'number') {
        return value;
    }
    if (typeof value === 'string') {
        // Remove currency symbols, spaces, and commas, then parse
        const cleanValue = value.replace(/[^0-9.-]/g, '');
        const parsed = parseFloat(cleanValue);
        return isNaN(parsed) ? 0 : parsed;
    }
    return 0;
};

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        required: true
    },
    estimatedTotal: {
        type: Number,
        required: true
    }
});

// Calculate total distribution
const totalDistribution = computed(() => {
    let total = 0;
    const estimatedTotal = parseCurrency(props.estimatedTotal);
    
    console.log('Debug totalDistribution calculation:', {
        estimatedTotal: props.estimatedTotal,
        estimatedTotalParsed: estimatedTotal,
        formData: props.form
    });
    
    // Primary Doctor
    const primaryShare = parseFloat(props.form.primary_doctor_share) || 0;
    let primaryAmount = 0;
    if (props.form.primary_doctor_is_percentage) {
        const percentageAmount = (estimatedTotal * primaryShare) / 100;
        primaryAmount = isNaN(percentageAmount) ? 0 : percentageAmount;
    } else {
        primaryAmount = isNaN(primaryShare) ? 0 : primaryShare;
    }
    total += primaryAmount;
    
    // Assistant Doctor
    const assistantShare = parseFloat(props.form.assistant_doctor_share) || 0;
    let assistantAmount = 0;
    if (props.form.assistant_doctor_is_percentage) {
        const percentageAmount = (estimatedTotal * assistantShare) / 100;
        assistantAmount = isNaN(percentageAmount) ? 0 : percentageAmount;
    } else {
        assistantAmount = isNaN(assistantShare) ? 0 : assistantShare;
    }
    total += assistantAmount;
    
    // Technician
    const technicianShare = parseFloat(props.form.technician_share) || 0;
    let technicianAmount = 0;
    if (props.form.technician_is_percentage) {
        const percentageAmount = (estimatedTotal * technicianShare) / 100;
        technicianAmount = isNaN(percentageAmount) ? 0 : percentageAmount;
    } else {
        technicianAmount = isNaN(technicianShare) ? 0 : technicianShare;
    }
    total += technicianAmount;
    
    // Clinic
    const clinicShare = parseFloat(props.form.clinic_share) || 0;
    let clinicAmount = 0;
    if (props.form.clinic_is_percentage) {
        const percentageAmount = (estimatedTotal * clinicShare) / 100;
        clinicAmount = isNaN(percentageAmount) ? 0 : percentageAmount;
    } else {
        clinicAmount = isNaN(clinicShare) ? 0 : clinicShare;
    }
    total += clinicAmount;
    
    console.log('Debug individual amounts:', {
        primaryAmount,
        assistantAmount,
        technicianAmount,
        clinicAmount,
        total
    });
    
    const finalTotal = isNaN(total) ? 0 : total;
    console.log('Final totalDistribution:', finalTotal);
    return finalTotal;
});

// Check if total exceeds estimated total
const isOverDistributed = computed(() => {
    const estimated = parseCurrency(props.estimatedTotal);
    const distributed = parseFloat(totalDistribution.value) || 0;
    return distributed > estimated;
});

// Calculate remaining amount
const remainingAmount = computed(() => {
    const estimated = parseCurrency(props.estimatedTotal);
    const distributed = parseFloat(totalDistribution.value) || 0;
    
    console.log('Debug remainingAmount:', {
        estimatedTotal: props.estimatedTotal,
        estimatedParsed: estimated,
        totalDistribution: totalDistribution.value,
        distributedParsed: distributed,
        formData: props.form
    });
    
    const remaining = estimated - distributed;
    return isNaN(remaining) ? 0 : remaining;
});

// Format currency
const formatCurrency = (amount) => {
    // Handle NaN, null, undefined, and invalid numbers
    const numericAmount = parseFloat(amount);
    if (isNaN(numericAmount)) {
        return 'DZD 0.00';
    }
    
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'DZD'
    }).format(numericAmount);
};


</script>

<template>
    <div class="tab-content">
        <div class="header-section">
            <h3 class="section-title">Revenue Distribution (TTC)</h3> <h4 class="fs-3">{{ props.estimatedTotal }}</h4>
            <p class="section-description">Configure how revenue is distributed among different roles</p>
        </div>

        <!-- Primary Doctor -->
        <div class="form-card">
            <div class="card-header">
                <div class="role-info">
                    <i class="fas fa-user-md role-icon primary"></i>
                    <h4 class="subsection-title">Primary Doctor</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.primary_doctor_is_percentage" class="checkbox-input" />
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Use Percentage</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            {{ form.primary_doctor_is_percentage ? 'Share (%)' : 'Fixed Amount (HT)' }}
                        </label>
                        <div class="input-container">
                            <input v-model="form.primary_doctor_share" type="number" step="0.01"
                                class="form-input"
                                :placeholder="form.primary_doctor_is_percentage ? '0.00' : '0.00'" />
                            <span class="input-suffix">{{ form.primary_doctor_is_percentage ? '%' : 'HT' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assistant/Anesthetist -->
        <div class="form-card">
            <div class="card-header">
                <div class="role-info">
                    <i class="fas fa-user-nurse role-icon assistant"></i>
                    <h4 class="subsection-title">Assistant/Anesthetist</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.assistant_doctor_is_percentage" class="checkbox-input" />
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Use Percentage</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            {{ form.assistant_doctor_is_percentage ? 'Share (%)' : 'Fixed Amount (HT)' }}
                        </label>
                        <div class="input-container">
                            <input v-model="form.assistant_doctor_share" type="number" step="0.01"
                                class="form-input"
                                :placeholder="form.assistant_doctor_is_percentage ? '0.00' : '0.00'" />
                            <span class="input-suffix">{{ form.assistant_doctor_is_percentage ? '%' : 'HT' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technician -->
        <div class="form-card">
            <div class="card-header">
                <div class="role-info">
                    <i class="fas fa-user-cog role-icon technician"></i>
                    <h4 class="subsection-title">Technician</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.technician_is_percentage" class="checkbox-input" />
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Use Percentage</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            {{ form.technician_is_percentage ? 'Share (%)' : 'Fixed Amount (HT)' }}
                        </label>
                        <div class="input-container">
                            <input v-model="form.technician_share" type="number" step="0.01"
                                class="form-input"
                                :placeholder="form.technician_is_percentage ? '0.00' : '0.00'" />
                            <span class="input-suffix">{{ form.technician_is_percentage ? '%' : 'HT' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clinic -->
        <div class="form-card">
            <div class="card-header">
                <div class="role-info">
                    <i class="fas fa-hospital role-icon clinic"></i>
                    <h4 class="subsection-title">Clinic</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.clinic_is_percentage" class="checkbox-input" />
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Use Percentage</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            {{ form.clinic_is_percentage ? 'Share (%)' : 'Fixed Amount (HT)' }}
                        </label>
                        <div class="input-container">
                            <input v-model="form.clinic_share" type="number" step="0.01"
                                class="form-input"
                                :placeholder="form.clinic_is_percentage ? '0.00' : '0.00'" />
                            <span class="input-suffix">{{ form.clinic_is_percentage ? '%' : 'HT' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribution Summary -->
        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-chart-pie section-icon"></i>
                    <h4 class="subsection-title">Distribution Summary</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Total Distributed:</span>
                        <span class="summary-value" :class="{ 'over-distributed': isOverDistributed }">
                            {{ formatCurrency(totalDistribution) }}
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Available Total:</span>
                        <span class="summary-value">{{ props.estimatedTotal }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Remaining:</span>
                        <span class="summary-value" :class="{ 'negative': remainingAmount < 0, 'positive': remainingAmount > 0 }">
                            {{ formatCurrency(remainingAmount) }}
                        </span>
                    </div>
                </div>
                
                <!-- Validation Warning -->
                <div v-if="isOverDistributed" class="validation-warning">
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                    <span class="warning-text">
                        Warning: Total distribution exceeds available amount by {{ formatCurrency(Math.abs(remainingAmount)) }}
                    </span>
                </div>
                
                <!-- Success Message -->
                <div v-else-if="totalDistribution > 0" class="validation-success">
                    <i class="fas fa-check-circle success-icon"></i>
                    <span class="success-text">
                        Distribution is valid. {{ formatCurrency(remainingAmount) }} remaining.
                    </span>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="errors.fee_distribution_shares" class="form-group full-width">
            <div class="error-card">
                <i class="fas fa-exclamation-triangle error-icon"></i>
                <span class="error-message">{{ errors.fee_distribution_shares }}</span>
            </div>
        </div>

        <!-- Help Section -->
        <div class="form-group full-width">
            <div class="help-card">
                <div class="help-header">
                    <i class="fas fa-info-circle help-icon"></i>
                    <span class="help-title">Important Notes</span>
                </div>
                <div class="help-content">
                    <ul class="help-list">
                        <li>You can mix percentage and fixed amounts for different roles</li>
                        <li>For percentage-based shares, ensure the total doesn't exceed 100%</li>
                        <li>Fixed amounts are calculated in HT (Hors Taxes)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tab-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: #f8fafc;
    min-height: 100vh;
}

/* Header Section */
.header-section {
    text-align: center;
    margin-bottom: 2.5rem;
}

.section-description {
    color: #64748b;
    font-size: 1rem;
    margin: 0;
}

/* Form Cards */
.form-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}

.form-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.card-header {
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.role-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.role-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: white;
    font-size: 1rem;
}

.role-icon.primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.role-icon.assistant {
    background: linear-gradient(135deg, #10b981, #059669);
}

.role-icon.technician {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.role-icon.clinic {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.subsection-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.card-content {
    padding: 1.5rem;
}

/* Input Container for Suffix */
.input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.input-suffix {
    position: absolute;
    right: 1rem;
    color: #6b7280;
    font-weight: 500;
    font-size: 0.875rem;
    pointer-events: none;
}

/* Error Styling */
.error-card {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.error-icon {
    color: #ef4444;
    font-size: 1.125rem;
}

/* Help Section */
.help-card {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.help-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.help-icon {
    color: #0ea5e9;
    font-size: 1.125rem;
}

.help-title {
    font-weight: 600;
    color: #0c4a6e;
    font-size: 1rem;
}

.help-content {
    color: #075985;
}

.help-list {
    margin: 0;
    padding-left: 1.25rem;
}

.help-list li {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.help-list li:last-child {
    margin-bottom: 0;
}

/* Distribution Summary Styles */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f1f5f9;
    border-radius: 0.5rem;
    border-left: 4px solid #3b82f6;
}

.summary-label {
    font-weight: 500;
    color: #475569;
    font-size: 0.875rem;
}

.summary-value {
    font-weight: 600;
    font-size: 1rem;
    color: #1e293b;
}

.summary-value.over-distributed {
    color: #dc2626;
    background: #fef2f2;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.summary-value.negative {
    color: #dc2626;
}

.summary-value.positive {
    color: #059669;
}

.validation-warning {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #fef3cd;
    border: 1px solid #f59e0b;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.warning-icon {
    color: #f59e0b;
    font-size: 1.125rem;
}

.warning-text {
    color: #92400e;
    font-weight: 500;
    font-size: 0.875rem;
}

.validation-success {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #d1fae5;
    border: 1px solid #059669;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.success-icon {
    color: #059669;
    font-size: 1.125rem;
}

.success-text {
    color: #065f46;
    font-weight: 500;
    font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .tab-content {
        padding: 1rem;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .card-content {
        padding: 1rem;
    }
}

/* Animation for smooth transitions */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-card {
    animation: slideIn 0.3s ease-out;
}
</style>
