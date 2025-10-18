<script setup>
import { defineProps, ref, computed, watch } from 'vue'; // Import 'computed' and 'watch'
// axios and useToastr are not directly used in this child component, so you can remove them if not needed here.
// import axios from 'axios'; 
// import { useToastr } from '../../../../toster'; 

// const toaster = useToastr(); // Not needed in this child component

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

// No need for these refs here, as formOptions already contains the data
// const specializationlist = ref([]); 
// const servicesOptions = ref([]);

// --- New Computed Property for Filtered Specializations ---
const filteredSpecializations = computed(() => {
    // If no service is selected or no specializations are loaded, return an empty array
    if (!props.form.service_id || !props.formOptions.specializations) {
        return [];
    }
    // Filter specializations where their service_id matches the selected service_id in the form
    return props.formOptions.specializations.filter(
        (specialization) => specialization.service_id === props.form.service_id
    );
});

// --- New Watcher to clear specialization when service changes ---
watch(() => props.form.service_id, (newServiceId, oldServiceId) => {
    // Only clear if the service has actually changed and it's not the initial load where both might be null
    if (newServiceId !== oldServiceId) {
        props.form.specialization_id = null; // Reset specialization when service changes
    }
});
</script>
<template>
    <div class="tab-content">
        <div class="header-section"
        >
            <h3 class="section-title">Basic Information</h3>
            <p class="section-description">Configure the fundamental details of your prestation</p>
        </div>

        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-info-circle section-icon primary"></i>
                    <h4 class="subsection-title">Primary Details</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-group full-width">
                    <label class="form-label required">Prestation Name</label>
                    <div class="input-with-icon">
                        <i class="fas fa-stethoscope input-icon"></i>
                        <input v-model="form.name" type="text" class="form-input with-icon" 
                            :class="{ 'error': errors.name }" placeholder="Enter prestation name" />
                    </div>
                    <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Internal Code</label>
                        <div class="input-with-icon">
                            <i class="fas fa-barcode input-icon"></i>
                            <input v-model="form.internal_code" type="text" class="form-input with-icon"
                                :class="{ 'error': errors.internal_code }" placeholder="IRM-CER-INJ" />
                        </div>
                        <span v-if="errors.internal_code" class="error-message">{{ errors.internal_code }}</span>
                        <small class="form-help">Unique identifier for internal tracking</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Billing Code</label>
                        <div class="input-with-icon">
                            <i class="fas fa-receipt input-icon"></i>
                            <input v-model="form.billing_code" type="text" class="form-input with-icon" 
                                placeholder="BILL-001" />
                        </div>
                        <small class="form-help">External billing reference code</small>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Description</label>
                    <div class="textarea-container">
                        <i class="fas fa-align-left textarea-icon"></i>
                        <textarea v-model="form.description" class="form-textarea with-icon" rows="4"
                            placeholder="Enter detailed description of the prestation..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-tags section-icon classification"></i>
                    <h4 class="subsection-title">Classification</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Service</label>
                        <div class="select-container">
                            <i class="fas fa-hospital select-icon"></i>
                            <select v-model="form.service_id" class="form-select with-icon" 
                                :class="{ 'error': errors.service_id }">
                                <option value="">Select Service</option>
                                <option v-for="service in formOptions.services" :key="service.id" :value="service.id">
                                    {{ service.name }}
                                </option>
                            </select>
                        </div>
                        <span v-if="errors.service_id" class="error-message">{{ errors.service_id }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Specialty</label>
                        <div class="select-container">
                            <i class="fas fa-user-md select-icon"></i>
                            <select 
                                v-model="form.specialization_id" 
                                class="form-select with-icon"
                                :class="{ 'error': errors.specialization_id }" 
                                :disabled="!form.service_id || filteredSpecializations.length === 0"
                            >
                                <option value="">Select Specialty</option>
                                <option v-for="specialization in filteredSpecializations" :key="specialization.id"
                                    :value="specialization.id">
                                    {{ specialization.name }}
                                </option>
                                <option v-if="!form.service_id" disabled>Select a service first</option>
                                <option v-else-if="filteredSpecializations.length === 0 && form.service_id" disabled>No specializations for this service</option>
                                </select>
                        </div>
                        <span v-if="errors.specialization_id" class="error-message">{{ errors.specialization_id }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Type</label>
                        <div class="select-container">
                            <i class="fas fa-layer-group select-icon"></i>
                            <select v-model="form.type" class="form-select with-icon" 
                                :class="{ 'error': errors.type }">
                                <option value="">Select Type</option>
                                <option v-for="typeOption in formOptions.prestation_types" :key="typeOption.value"
                                    :value="typeOption.value">
                                    {{ typeOption.label }}
                                </option>
                            </select>
                        </div>
                        <span v-if="errors.type" class="error-message">{{ errors.type }}</span>
                    </div>
                </div>
            </div>
        </div>

      
    </div>
</template>

<style scoped>
/* ... (Your existing styles) ... */
</style>

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

.section-icon.primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.section-icon.classification {
    background: linear-gradient(135deg, #10b981, #059669);
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

/* Enhanced Input Styling */
.textarea-container {
    position: relative;
}

.textarea-icon {
    position: absolute;
    left: 0.875rem;
    top: 1rem;
    color: #6b7280;
    font-size: 0.875rem;
    z-index: 1;
}

.form-textarea.with-icon {
    padding-left: 2.75rem;
}

.select-container {
    position: relative;
}

.select-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 0.875rem;
    z-index: 1;
    pointer-events: none;
}

.form-select.with-icon {
    padding-left: 2.75rem;
    width: 100%;
;
}

/* Preview Card */
.preview-card {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.preview-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.preview-icon {
    color: #0ea5e9;
    font-size: 1.125rem;
}

.preview-title {
    font-weight: 600;
    color: #0c4a6e;
    font-size: 1rem;
}

.preview-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 0.75rem;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e0f2fe;
}

.preview-item:last-child {
    border-bottom: none;
}

.preview-label {
    font-weight: 500;
    color: #075985;
    font-size: 0.875rem;
}

.preview-value {
    color: #0c4a6e;
    font-size: 0.875rem;
    font-weight: 600;
    text-align: right;
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
    
    .preview-content {
        grid-template-columns: 1fr;
    }
    
    .preview-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .preview-value {
        text-align: left;
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
