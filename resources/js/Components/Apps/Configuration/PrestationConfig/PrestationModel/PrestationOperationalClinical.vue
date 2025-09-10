<script setup>
import { defineProps } from 'vue';
import MultiSelect from 'primevue/multiselect'; // Import PrimeVue MultiSelect

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
</script>

<template>
    <div class="tab-content">
        <div class="header-section">
            <h3 class="section-title">Scheduling & Requirements</h3>
            <p class="section-description">Configure timing, resources, and patient requirements</p>
        </div>

        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-calendar-alt section-icon timing"></i>
                    <h4 class="subsection-title">Timing & Resources</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Default Duration (Minutes)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-clock input-icon"></i>
                            <input v-model="form.default_duration_minutes" type="number" class="form-input with-icon"
                                placeholder="60" />
                        </div>
                        <small class="form-help">Estimated time for this procedure</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Required Modality / Resource</label>
                        <div class="select-container">
                            <i class="fas fa-cogs select-icon"></i>
                            <select v-model="form.required_modality_type_id" class="form-select with-icon">
                                <option value="">Select Modality / Resource</option>
                                <option v-for="modalityType in formOptions.modality_types" :key="modalityType.id"
                                    :value="modalityType.id">
                                    {{ modalityType.name }}
                                </option>
                            </select>
                        </div>
                        <small class="form-help">Equipment or resource needed for this procedure</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-hospital section-icon hospitalization"></i>
                    <h4 class="subsection-title">Hospitalization</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-group full-width">
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input v-model="form.requires_hospitalization" type="checkbox" class="checkbox-input" />
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Hospitalization Required</span>
                        </label>
                        <small class="form-help">Indicates if this prestation requires patient admission</small>
                    </div>
                </div>

                <div v-if="form.requires_hospitalization" class="hospitalization-details">
                    <div class="form-group">
                        <label class="form-label required">Default Hospitalization Nights</label>
                        <div class="input-with-icon">
                            <i class="fas fa-bed input-icon"></i>
                            <input v-model="form.default_hosp_nights" type="number" class="form-input with-icon"
                                :class="{ 'error': errors.default_hosp_nights }" placeholder="1" />
                        </div>
                        <span v-if="errors.default_hosp_nights" class="error-message">{{ errors.default_hosp_nights }}</span>
                        <small class="form-help">Expected number of nights for patient stay</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-link section-icon dependencies"></i>
                    <h4 class="subsection-title">Dependencies</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-group w-full">
                    <label class="form-label">Required Prestations (Informational Only)</label>
                    <!-- {{ formOptions.available_prestations }} -->
                    <div class="select-container">
                        <MultiSelect
                            v-model="form.required_prestations_info"
                            :options="formOptions.available_prestations"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Prestations"
                            display="chip"
                            class="primevue-multiselect"
                        />
                    </div>
                    <small class="form-help">Link other prestations typically required before this one</small>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-user-injured section-icon instructions"></i>
                    <h4 class="subsection-title">Patient Instructions</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-group full-width">
                    <label class="form-label">Pre-procedure Instructions</label>
                    <div class="textarea-container">
                        <i class="fas fa-clipboard-list textarea-icon"></i>
                        <textarea v-model="form.patient_instructions" class="form-textarea with-icon" rows="4"
                            placeholder="e.g., Être à jeun 8 heures avant l'examen, apporter les examens précédents..."></textarea>
                    </div>
                    <small class="form-help">Instructions that will be provided to patients before the procedure</small>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-header">
                <div class="section-info">
                    <i class="fas fa-calendar-check section-icon"></i>
                    <h4 class="subsection-title">Appointment Requirement</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="form-group full-width">
                    <label class="checkbox-label">
                        <input v-model="form.need_an_appointment" type="checkbox" class="checkbox-input" />
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">Requires Appointment</span>
                    </label>
                    <small class="form-help">Check if this prestation requires an appointment to be scheduled.</small>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* Your existing styles */

/* Add some basic styling for PrimeVue MultiSelect to fit your theme */
.primevue-multiselect {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #374151;
    background-color: #fff;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.primevue-multiselect:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
    outline: none;
}

/* Adjust padding for the icon in the select-container if needed */
.select-container .p-multiselect {
    padding-left: 2.75rem; /* Adjust this to make space for the icon */
}

/* You might need to adjust the icon's position for PrimeVue's structure */
.select-container .select-icon {
    top: 50%;
    transform: translateY(-50%);
    left: 0.875rem;
    z-index: 2; /* Ensure icon is above the multiselect */
}


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

.section-icon.timing {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.section-icon.hospitalization {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.section-icon.dependencies {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.section-icon.instructions {
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
}

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

/* Hospitalization Details */
.hospitalization-details {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        padding-top: 0;
        margin-top: 0;
    }
    to {
        opacity: 1;
        max-height: 200px;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }
}

/* Summary Card */
.summary-card {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.summary-icon {
    color: #0ea5e9;
    font-size: 1.125rem;
}

.summary-title {
    font-weight: 600;
    color: #0c4a6e;
    font-size: 1rem;
}

.summary-content {
    color: #075985;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e0f2fe;
}

.summary-label {
    font-weight: 500;
    color: #075985;
    font-size: 0.875rem;
}

.summary-value {
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

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .summary-value {
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