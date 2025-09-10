<script setup>
import { defineProps, computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        required: true
    },
    formOptions: {
        type: Object,
        required: true
    }
});

const estimatedTotal = computed(() => {
    const price = parseFloat(props.form.public_price) || 0;
    const vatRate = parseFloat(props.form.vat_rate) || 0;
    const consumables = parseFloat(props.form.consumables_cost) || 0;
    const nightTariff = props.form.night_tariff_active ? (parseFloat(props.form.night_tariff) || 0) : 0;

    const basePrice = props.form.night_tariff_active ? nightTariff : price;

    const vatAmount = (basePrice * vatRate) / 100;
    const total = basePrice + vatAmount + consumables;

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'DZD' // Assuming Algerian Dinars as currency
    }).format(total);
});
</script>

<template>
    <div class="tab-content">
        <div class="header-section">
            <h3 class="section-title">Pricing & Financial Configuration</h3>
            <p class="section-description">Set up pricing, taxes, and payment options for this service</p>
        </div>

        <!-- Pricing Summary Card -->
        <div class="summary-card">
            <div class="summary-header">
                <i class="fas fa-calculator summary-icon"></i>
                <h4 class="summary-title">Estimated Total</h4>
            </div>
            <div class="price-display">
                <span class="price-label">Total (TTC):</span>
                <span class="price-value">{{ estimatedTotal }}</span>
            </div>
            <div class="price-breakdown">
                <div class="breakdown-item">
                    <span class="breakdown-label">Base Price (HT):</span>
                    <span class="breakdown-value">
                        {{ new Intl.NumberFormat('en-US', { style: 'currency', currency: 'DZD' }).format(
                            form.night_tariff_active ? (parseFloat(form.night_tariff) || 0) : (parseFloat(form.public_price) || 0)
                        ) }}
                    </span>
                </div>
                <div class="breakdown-item">
                    <span class="breakdown-label">VAT ({{ form.vat_rate || 0 }}%):</span>
                    <span class="breakdown-value">
                        {{ new Intl.NumberFormat('en-US', { style: 'currency', currency: 'DZD' }).format(
                            ((form.night_tariff_active ? (parseFloat(form.night_tariff) || 0) : (parseFloat(form.public_price) || 0)) * (parseFloat(form.vat_rate) || 0)) / 100
                        ) }}
                    </span>
                </div>
                <div class="breakdown-item">
                    <span class="breakdown-label">Consumables:</span>
                    <span class="breakdown-value">
                        {{ new Intl.NumberFormat('en-US', { style: 'currency', currency: 'DZD' }).format(parseFloat(form.consumables_cost) || 0) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Basic Pricing Section -->
        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-tags section-icon pricing"></i>
                    <h4 class="subsection-title">Basic Pricing</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Standard Public Price (HT)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-dollar-sign input-icon"></i>
                            <input v-model="form.public_price" type="number" step="0.01" class="form-input with-icon"
                                :class="{ 'error': errors.public_price }" placeholder="0.00" />
                        </div>
                        <span v-if="errors.public_price" class="error-message">{{ errors.public_price }}</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label "> convenience Price </label>
                        <div class="input-with-icon">
                            <i class="fas fa-dollar-sign input-icon"></i>
                            <input v-model="form.convenience_prix" type="number" step="0.01" class="form-input with-icon"
                                :class="{ 'error': errors.convenience_prix }" placeholder="0.00" />
                        </div>
                        <span v-if="errors.convenience_prix" class="error-message">{{ errors.convenience_prix }}</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">VAT Rate (%)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-percentage input-icon"></i>
                            <input v-model="form.vat_rate" type="number" step="0.01" class="form-input with-icon"
                                :class="{ 'error': errors.vat_rate }" placeholder="19.00" />
                        </div>
                        <span v-if="errors.vat_rate" class="error-message">{{ errors.vat_rate }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cost of Consumables</label>
                        <div class="input-with-icon">
                            <i class="fas fa-boxes input-icon"></i>
                            <input v-model="form.consumables_cost" type="number" step="0.01" class="form-input with-icon"
                                :class="{ 'error': errors.consumables_cost }" placeholder="0.00" />
                        </div>
                        <span v-if="errors.consumables_cost" class="error-message">{{ errors.consumables_cost }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Night Tariff Section -->
        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-moon section-icon night"></i>
                    <h4 class="subsection-title">Night Tariff</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-group full-width">
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input v-model="form.night_tariff_active" type="checkbox" class="checkbox-input" />
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Enable Night Tariff</span>
                        </label>
                    </div>
                </div>

                <div v-if="form.night_tariff_active" class="form-group">
                    <label class="form-label required">Night Tariff (HT)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-moon input-icon"></i>
                        <input v-model="form.night_tariff" type="number" step="0.01" class="form-input with-icon"
                            :class="{ 'error': errors.night_tariff }" placeholder="0.00" />
                    </div>
                    <span v-if="errors.night_tariff" class="error-message">{{ errors.night_tariff }}</span>
                    <small class="form-help">Special pricing for services provided during night hours</small>
                </div>
            </div>
        </div>

        <!-- Social Security Section -->
        <!-- <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-shield-alt section-icon security"></i>
                    <h4 class="subsection-title">Social Security</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-group full-width">
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input v-model="form.is_social_security_reimbursable" type="checkbox" class="checkbox-input" />
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Social Security Reimbursable</span>
                        </label>
                        <small class="form-help">This prestation is eligible for social security reimbursement</small>
                    </div>
                </div>

                <div v-if="form.is_social_security_reimbursable" class="form-group full-width">
                    <label class="form-label">Reimbursement Conditions</label>
                    <textarea v-model="form.reimbursement_conditions" class="form-textarea" rows="3"
                        placeholder="e.g., Requires prior approval, Only for chronic illnesses"></textarea>
                </div>
            </div>
        </div> -->

        <!-- Payment & Discounts Section -->
        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-credit-card section-icon payment"></i>
                    <h4 class="subsection-title">Payment & Discounts</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Default Payment Type</label>
                        <select v-model="form.default_payment_type" class="form-select">
                            <option value="">Select Payment Type</option>
                            <option v-for="paymentType in formOptions.payment_types" :key="paymentType.value"
                                :value="paymentType.value">
                                {{ paymentType.label }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Minimum Deposit Amount</label>
                        <div class="input-with-icon">
                            <i class="fas fa-money-bill-wave input-icon"></i>
                            <input v-model="form.min_versement_amount" type="number" step="0.01" class="form-input with-icon"
                                placeholder="0.00" />
                        </div>
                        <small class="form-help">Minimum deposit required for Visa Accordé</small>
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

/* Summary Card */
.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.summary-icon {
    width: 24px;
    height: 24px;
    font-size: 1.25rem;
}

.summary-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.price-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.price-label {
    font-size: 1.25rem;
    font-weight: 500;
    opacity: 0.9;
}

.price-value {
    font-size: 2.5rem;
    font-weight: 700;
}

.price-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    opacity: 0.8;
}

.breakdown-label {
    font-weight: 500;
}

.breakdown-value {
    font-weight: 600;
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
    font-size: 1rem;
}

.section-icon.pricing {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.section-icon.night {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
}

.section-icon.security {
    background: linear-gradient(135deg, #10b981, #059669);
}

.section-icon.payment {
    background: linear-gradient(135deg, #f59e0b, #d97706);
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

/* Responsive Design */
@media (max-width: 768px) {
    .tab-content {
        padding: 1rem;
    }
    
    .summary-card {
        padding: 1.5rem;
    }
    
    .price-display {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .price-value {
        font-size: 2rem;
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
