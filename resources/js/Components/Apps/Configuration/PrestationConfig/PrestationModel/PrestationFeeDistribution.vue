<script setup>
import { computed } from 'vue';

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


</script>

<template>
    <div class="tab-content">
        <div class="header-section">
            <h3 class="section-title">Revenue Distribution (HT)</h3> <h4 class="fs-3">{{ props.estimatedTotal }}</h4>
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
