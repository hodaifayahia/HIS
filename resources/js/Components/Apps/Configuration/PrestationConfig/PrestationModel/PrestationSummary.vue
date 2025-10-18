<script setup>
import { defineProps, computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    formOptions: {
        type: Object,
        required: true
    },
    estimatedTotal: {
        type: String,
        required: true
    }
});

const getServiceName = computed(() => {
    return props.formOptions.services?.find(s => s.id === props.form.service_id)?.name || 'Not selected';
});

const getSpecializationName = computed(() => {
    return props.formOptions.specializations?.find(s => s.id === props.form.specialization_id)?.name || 'Not selected';
});

const getTypeName = computed(() => {
    return props.formOptions.prestation_types?.find(t => t.value === props.form.type)?.label || 'Not selected';
});

const calculateTotalPercentage = computed(() => {
    let total = 0;
    if (props.form.primary_doctor_is_percentage) total += parseFloat(props.form.primary_doctor_share) || 0;
    if (props.form.assistant_doctor_is_percentage) total += parseFloat(props.form.assistant_doctor_share) || 0;
    if (props.form.technician_is_percentage) total += parseFloat(props.form.technician_share) || 0;
    if (props.form.clinic_is_percentage) total += parseFloat(props.form.clinic_share) || 0;
    return total;
});
</script>

<template>
    <div class="tab-content">
        <div class="header-section">
            <h3 class="section-title">Prestation Summary</h3>
            <p class="section-description">Overview of all configured settings</p>
        </div>

        <!-- Basic Information Summary -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="card-header">
                    <div class="section-info">
                        <i class="fas fa-info-circle section-icon primary"></i>
                        <h4 class="subsection-title">Basic Information</h4>
                    </div>
                </div>
                <div class="summary-content">
                    <div class="summary-item">
                        <span class="item-label">Name:</span>
                        <span class="item-value">{{ form.name || 'Not specified' }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Internal Code:</span>
                        <span class="item-value">{{ form.internal_code || 'Not specified' }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Service:</span>
                        <span class="item-value">{{ getServiceName }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Specialization:</span>
                        <span class="item-value">{{ getSpecializationName }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Type:</span>
                        <span class="item-value">{{ getTypeName }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Summary -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="card-header">
                    <div class="section-info">
                        <i class="fas fa-dollar-sign section-icon pricing"></i>
                        <h4 class="subsection-title">Pricing Information</h4>
                    </div>
                </div>
                <div class="summary-content">
                    <div class="summary-item highlighted">
                        <span class="item-label">Total Price (TTC):</span>
                        <span class="item-value">{{ estimatedTotal }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Base Price (HT):</span>
                        <span class="item-value">{{ new Intl.NumberFormat('en-US', { style: 'currency', currency: 'DZD' }).format(form.public_price || 0) }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">VAT Rate:</span>
                        <span class="item-value">{{ form.vat_rate || 0 }}%</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Night Tariff:</span>
                        <span class="item-value">{{ form.night_tariff_active ? (form.night_tariff || '0') + ' DZD' : 'Not Active' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fee Distribution Summary -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="card-header">
                    <div class="section-info">
                        <i class="fas fa-handshake section-icon distribution"></i>
                        <h4 class="subsection-title">Fee Distribution</h4>
                    </div>
                </div>
                <div class="summary-content">
                    <div class="summary-item highlighted">
                        <span class="item-label">Total Percentage:</span>
                        <span class="item-value">{{ calculateTotalPercentage }}%</span>
                    </div>
                    <div v-if="form.primary_doctor_share" class="summary-item">
                        <span class="item-label">Primary Doctor:</span>
                        <span class="item-value">{{ form.primary_doctor_share }}{{ form.primary_doctor_is_percentage ? '%' : ' DZD' }}</span>
                    </div>
                    <div v-if="form.assistant_doctor_share" class="summary-item">
                        <span class="item-label">Assistant/Anesthetist:</span>
                        <span class="item-value">{{ form.assistant_doctor_share }}{{ form.assistant_doctor_is_percentage ? '%' : ' DZD' }}</span>
                    </div>
                    <div v-if="form.technician_share" class="summary-item">
                        <span class="item-label">Technician:</span>
                        <span class="item-value">{{ form.technician_share }}{{ form.technician_is_percentage ? '%' : ' DZD' }}</span>
                    </div>
                    <div v-if="form.clinic_share" class="summary-item">
                        <span class="item-label">Clinic:</span>
                        <span class="item-value">{{ form.clinic_share }}{{ form.clinic_is_percentage ? '%' : ' DZD' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Details Summary -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="card-header">
                    <div class="section-info">
                        <i class="fas fa-cogs section-icon operational"></i>
                        <h4 class="subsection-title">Operational Details</h4>
                    </div>
                </div>
                <div class="summary-content">
                    <div class="summary-item">
                        <span class="item-label">Duration:</span>
                        <span class="item-value">{{ form.default_duration_minutes || 'Not specified' }} minutes</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Hospitalization:</span>
                        <span class="item-value">{{ form.requires_hospitalization ? `Required (${form.default_hosp_nights} nights)` : 'Not Required' }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Status:</span>
                        <span class="item-value status" :class="{ active: form.is_active }">
                            {{ form.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tab-content {
    max-width: 900px;
    margin: 0 auto;
    padding: 1rem;
}

.header-section {
    text-align: center;
    margin-bottom: 2rem;
}

.summary-section {
    margin-bottom: 1.5rem;
}

.summary-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.section-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: white;
}

.section-icon.primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.section-icon.pricing { background: linear-gradient(135deg, #10b981, #059669); }
.section-icon.distribution { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.section-icon.operational { background: linear-gradient(135deg, #f59e0b, #d97706); }

.summary-content {
    padding: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.highlighted {
    background-color: #f8fafc;
    font-weight: 600;
}

.item-label {
    color: #64748b;
    font-size: 0.9rem;
}

.item-value {
    font-weight: 500;
    color: #1e293b;
}

.status {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status.active {
    background-color: #dcfce7;
    color: #166534;
}

.status:not(.active) {
    background-color: #fee2e2;
    color: #991b1b;
}

@media (max-width: 768px) {
    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .item-value {
        width: 100%;
    }
}
</style>