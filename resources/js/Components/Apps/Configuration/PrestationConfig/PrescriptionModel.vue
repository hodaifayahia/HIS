<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';

// Import the new sub-components
import PrestationBasicInfo from '../../../../Components/Apps/Configuration/PrestationConfig/PrestationModel/PrestationBasicInfo.vue';
import PrestationPricingPayment from '../../../../Components/Apps/Configuration/PrestationConfig/PrestationModel/PrestationPricingPayment.vue';
import PrestationFeeDistribution from '../../../../Components/Apps/Configuration/PrestationConfig/PrestationModel/PrestationFeeDistribution.vue';
import PrestationOperationalClinical from '../../../../Components/Apps/Configuration/PrestationConfig/PrestationModel/PrestationOperationalClinical.vue';
import PrestationSummary from './PrestationModel/PrestationSummary.vue';
import ModalFooter from '../../../../Components/Apps/Configuration/PrestationConfig/PrestationModel/ModalFooter.vue'; // Reusable footer

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    prestationData: { // Renamed prop for consistency
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'prestation-added', 'prestation-updated']);

const toaster = useToastr();
// Form data - central state for the entire form
const form = ref({ // Use ref() for the form object itself
    id: null,
     need_an_appointment: false,
    name: '',
    internal_code: '',
    billing_code: '',
    description: '',
    service_id: null,
    specialization_id: null,
    type: 'Médical', // Default for new
    is_active: true,
    public_price: 0, // Default to 0 for new
    convenience_prix: 0, // Default to 0 for new
    vat_rate: null, // Null for new
    night_tariff: null, // Null for new
    night_tariff_active: false, // New toggle for night tariff
    consumables_cost: null, // Null for new
    is_social_security_reimbursable: false,
    reimbursement_conditions: '',
    non_applicable_discount_rules: [],
    fee_distribution_model: 'percentage', // Default for new
    primary_doctor_share: null, // Initialize as null for new, will be populated on edit
    primary_doctor_is_percentage: false, // Default for new, will be populated on edit
    assistant_doctor_share: null,
    assistant_doctor_is_percentage: false,
    technician_share: null,
    technician_is_percentage: false,
    clinic_share: null,
    clinic_is_percentage: false,
    default_payment_type: null, // Null for new
    min_versement_amount: null, // Null for new
    requires_hospitalization: false,
    default_hosp_nights: null, // Null for new
    required_modality_type_id: null,
    default_duration_minutes: null, // Null for new
    required_prestations_info: [],
    patient_instructions: '',
    required_consents: [],
});
const resetForm = () => {
    // Reset to initial null/empty states for a new form
    form.value = {
        id: null,
        name: '',
        internal_code: '',
        billing_code: '',
        description: '',
        service_id: null,
        specialization_id: null,
        type: 'Médical', // Default for new
        is_active: true,
        public_price: 0,
        convenience_prix: 0,
        need_an_appointment: false,
        vat_rate: null,
        night_tariff_active: false,
        night_tariff: null,
        consumables_cost: null,
        is_social_security_reimbursable: false,
        reimbursement_conditions: '',
        non_applicable_discount_rules: [],
        fee_distribution_model: 'percentage', // Default for new
        primary_doctor_share: null, // Reset to null or 0 for display
        primary_doctor_is_percentage: false,
        assistant_doctor_share: null,
        assistant_doctor_is_percentage: false,
        technician_share: null,
        technician_is_percentage: false,
        clinic_share: null,
        clinic_is_percentage: false,
        default_payment_type: null,
        min_versement_amount: null,
        requires_hospitalization: false,
        default_hosp_nights: null,
        required_modality_type_id: null,
        default_duration_minutes: null,
        required_prestations_info: [],
        patient_instructions: '',
        required_consents: [],
    };
    errors.value = {};
    activeTab.value = 'basic';
};


// Form options - shared among relevant components
const formOptions = ref({
    services: [],
    specializations: [], // This will hold ALL specializations
    modality_types: [],
    available_prestations: [],
    payment_types: [
        { value: 'Pré-paiement', label: 'Pré-paiement' },
        { value: 'Post-paiement', label: 'Post-paiement' },
        { value: 'Versement', label: 'Versement' }
    ],
    prestation_types: [ // Renamed from prestation_types to be consistent with 'type' field
        { value: 'Médical', label: 'Médical' },
        { value: 'Chirurgical', label: 'Chirurgical' }
    ],
    available_discounts: [
        { id: 1, name: '10% Seasonal Promotion' },
        { id: 2, name: 'First-time Patient Discount' },
        { id: 3, name: 'Senior Citizen Discount' }
    ],
});

// Watchers
watch(() => props.showModal, async (newVal) => { // Make the watcher async
    if (newVal) {
        resetForm(); // Reset form to clear previous state

        // Ensure options are fetched BEFORE populating the form
        await getFormOptions(); // Await the API call for options

        if (props.prestationData) { // If in edit mode, populate the form
            populateForm();
        }
    }
});

// Form state
const loading = ref(false);
const errors = ref({});
const activeTab = ref('basic');

// Map tab names to components for dynamic rendering
const tabComponents = {
    basic: PrestationBasicInfo,
    pricing: PrestationPricingPayment,
    fees: PrestationFeeDistribution,
    medical: PrestationOperationalClinical,
    summary: PrestationSummary, // New summary tab
    // 'settings' is kept as a simple div for now, but could be its own component too
};

// Computed property to get the currently active component
const currentTabComponent = computed(() => {
    return tabComponents[activeTab.value] || null;
});

// Computed property for shared props, making template cleaner
const sharedProps = computed(() => ({
    form: form.value,
    errors: errors.value,
    formOptions: formOptions.value,
    // Add the filtered specializations here
    filteredSpecializations: filteredSpecializations.value,
}));

// Computed
const isEditMode = computed(() => {
    return props.prestationData && props.prestationData.id;
});

const modalTitle = computed(() => {
    return isEditMode.value ? 'Edit Prestation' : 'Add New Prestation';
});

const estimatedTotal = computed(() => {
    const price = parseFloat(form.value.public_price) || 0;
    
    const convenience_prix = parseFloat(form.value.convenience_prix) || 0;
    const vatRate = parseFloat(form.value.vat_rate) || 0;
    const consumables = parseFloat(form.value.consumables_cost) || 0;
    const nightTariff = form.value.night_tariff_active ? (parseFloat(form.value.night_tariff) || 0) : 0;

    const basePrice = form.value.night_tariff_active ? nightTariff : price;

    const vatAmount = (basePrice * vatRate) / 100;
    const total = basePrice + vatAmount + consumables;

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'DZD'
    }).format(total);
});


// Watchers
watch(() => props.showModal, (newVal) => {
    if (newVal) {
        resetForm();
        if (props.prestationData) { // Use prestationData here
            populateForm();
        }
        getFormOptions();
    }
});

watch(() => form.value.requires_hospitalization, (newVal) => {
    if (!newVal) {
        form.value.default_hosp_nights = '';
    }
});

watch(() => form.value.night_tariff_active, (newVal) => {
    if (!newVal) {
        form.value.night_tariff = null;
    }
});

// NEW: Watch for changes in service_id to clear specialization_id
watch(() => form.value.service_id, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        form.value.specialization_id = null; // Clear specialization when service changes
    }
});


// Watch for changes in fee distribution and validate if percentage model
watch([
    () => form.value.primary_doctor_share,
    () => form.value.assistant_doctor_share,
    () => form.value.technician_share,
    () => form.value.clinic_share,
    () => form.value.primary_doctor_is_percentage,
    () => form.value.assistant_doctor_is_percentage,
    () => form.value.technician_is_percentage,
    () => form.value.clinic_is_percentage,
    () => form.value.need_an_appointment
], () => {
    // Re-run validation only for percentage-based sums
    if (form.value.fee_distribution_model === 'percentage') {
        const primary = form.value.primary_doctor_is_percentage ? (parseFloat(form.value.primary_doctor_share) || 0) : 0;
        const assistant = form.value.assistant_doctor_is_percentage ? (parseFloat(form.value.assistant_doctor_share) || 0) : 0;
        const technician = form.value.technician_is_percentage ? (parseFloat(form.value.technician_share) || 0) : 0;
        const clinic = form.value.clinic_is_percentage ? (parseFloat(form.value.clinic_share) || 0) : 0;

        const totalPercentageShares = primary + assistant + technician + clinic;

        if (totalPercentageShares > 100) {
            errors.value.fee_distribution_shares = 'Total percentage shares exceed 100%. Please adjust.';
        } else if (totalPercentageShares !== 100 &&
            form.value.primary_doctor_is_percentage &&
            form.value.assistant_doctor_is_percentage &&
            form.value.technician_is_percentage &&
            form.value.clinic_is_percentage) {
            errors.value.fee_distribution_shares = `Total percentage shares must sum to 100%. Current total: ${totalPercentageShares}%`;
        } else {
            // Clear error if it's no longer problematic
            if (errors.value.fee_distribution_shares) {
                delete errors.value.fee_distribution_shares;
            }
        }
    }
}, { deep: true });

// Methods


const populateForm = () => {
    if (props.prestationData) {
        // Assign the ID for edit mode
        form.value.id = props.prestationData.id;
        console.log('Prestation Data:', props.prestationData);


        // Use a loop to assign most fields directly
        for (const key in form.value) {
            if (Object.prototype.hasOwnProperty.call(props.prestationData, key) && key !== 'id' && key !== 'night_tariff_active') {
                // Handle special cases for relations if they are objects in resource but IDs in form
                if (key === 'service_id' && props.prestationData.service) {
                    form.value.service_id = props.prestationData.service.id;
                } else if (key === 'specialization_id' && props.prestationData.specialization) {
                    form.value.specialization_id = props.prestationData.specialization.id;
                } else if (key === 'required_modality_type_id' && props.prestationData.modality_type) {
                    form.value.required_modality_type_id = props.prestationData.modality_type.id;
                }
                // Handle booleans coming from backend (e.g., 0/1 or true/false)
                else if (typeof form.value[key] === 'boolean' && props.prestationData[key] !== undefined) {
                    form.value[key] = Boolean(props.prestationData[key]);
                }
                // Handle arrays (e.g., from JSON casts)
                else if (Array.isArray(form.value[key]) && props.prestationData[key] !== undefined) {
                    form.value[key] = Array.isArray(props.prestationData[key]) ? props.prestationData[key] : [];
                }
                // All other direct assignments
                else {
                    form.value[key] = props.prestationData[key];
                }
            }
        }

        // Special handling for night_tariff_active based on night_tariff
        form.value.night_tariff_active = props.prestationData.night_tariff !== null && props.prestationData.night_tariff !== undefined;
        // Ensure night_tariff itself is set correctly (it will be by the loop, but this reinforces)
        form.value.night_tariff = props.prestationData.night_tariff ?? null;
        form.value.need_an_appointment = props.prestationData.need_an_appointment ?? false;


        // Specific logic for fee distribution shares:
        // We assume primary_doctor_is_percentage, etc., are stored as boolean in DB
        // and share values are stored as string "XX%" or numeric.
        // We need to parse the string to number for display.

        const sharesToParse = [
            'primary_doctor',
            'assistant_doctor',
            'technician',
            'clinic',
        ];

        sharesToParse.forEach(role => {
            const shareField = `${role}_share`;
            const isPercentageField = `${role}_is_percentage`;

            // Set the boolean flag first (directly from DB)
            form.value[isPercentageField] = Boolean(props.prestationData[isPercentageField]);

            // Now, parse the share value
            let shareValue = props.prestationData[shareField];

            if (shareValue !== null && shareValue !== undefined) {
                // If it's a string and ends with '%', remove '%' and parse to float
                if (typeof shareValue === 'string' && shareValue.endsWith('%')) {
                    form.value[shareField] = parseFloat(shareValue.replace('%', ''));
                } else {
                    // Otherwise, just parse to float (for fixed amounts or if backend already sends numbers)
                    form.value[shareField] = parseFloat(shareValue);
                }
            } else {
                form.value[shareField] = null; // Ensure null if data is null/undefined
            }
        });


        console.log('Form populated for edit:', form.value); // Use .value here
    }
};
const prestationApiService = {
    async getServices() {
        const response = await axios.get('/api/services');
        return response.data.data;
    },

    async getSpecializations() {
        // Assuming your backend can return specializations with their associated service_id
        const response = await axios.get('/api/specializations');
        return response.data.data;
    },

    async getModalityTypes() {
        const response = await axios.get('/api/modality-types');
        return response.data.data;
    },
    async getPrestation() {
        const response = await axios.get(`/api/prestation`);
        return response.data.data;
    },

    async createPrestation(data) {
        const response = await axios.post('/api/prestation', data);
        return response.data;
    },

    async updatePrestation(id, data) {
        const response = await axios.put(`/api/prestation/${id}`, data);
        return response.data;
    }
};


const getFormOptions = async () => {
    try {
        formOptions.value.services = await prestationApiService.getServices();
        // Fetch ALL specializations initially
        formOptions.value.specializations = await prestationApiService.getSpecializations();
        formOptions.value.modality_types = await prestationApiService.getModalityTypes();
        formOptions.value.available_prestations = await prestationApiService.getPrestation();
    } catch (err) {
        console.error('Error fetching form options:', err);
        toaster.error('Failed to load form options.');
    }
};

const validateForm = () => {
    errors.value = {};

    if (!form.value.name?.trim()) {
        errors.value.name = 'Prestation Name is required';
    }

    if (!form.value.internal_code?.trim()) {
        errors.value.internal_code = 'Internal Code is required';
    }

    if (!form.value.service_id) {
        errors.value.service_id = 'Service is required';
    }

    if (!form.value.type?.trim()) {
        errors.value.type = 'Type (Médical/Chirurgical) is required';
    }

    const publicPrice = parseFloat(form.value.public_price);
    if (isNaN(publicPrice) || publicPrice <= 0) {
        errors.value.public_price = 'Valid Public Price is required (must be greater than 0)';
    }

    const vatRate = parseFloat(form.value.vat_rate);
    if (!isNaN(vatRate) && (vatRate < 0 || vatRate > 100)) {
        errors.value.vat_rate = 'VAT rate must be between 0 and 100';
    }

    const nightTariff = parseFloat(form.value.night_tariff);
    if (form.value.night_tariff_active && (isNaN(nightTariff) || nightTariff <= 0)) {
        errors.value.night_tariff = 'Valid Night Tariff is required if active (must be greater than 0)';
    }

    const consumablesCost = parseFloat(form.value.consumables_cost);
    if (!isNaN(consumablesCost) && consumablesCost < 0) {
        errors.value.consumables_cost = 'Consumables Cost cannot be negative';
    }

    if (form.value.requires_hospitalization && (!form.value.default_hosp_nights || parseInt(form.value.default_hosp_nights) <= 0)) {
        errors.value.default_hosp_nights = 'Default Hospitalization Nights is required if hospitalization is required';
    }

    // Fee distribution validation logic - specific to percentage model
    let totalPercentageShares = 0;
    const allSharesArePercentages = form.value.primary_doctor_is_percentage &&
        form.value.assistant_doctor_is_percentage &&
        form.value.technician_is_percentage &&
        form.value.clinic_is_percentage;

    if (form.value.fee_distribution_model === 'percentage') {
        if (form.value.primary_doctor_is_percentage) totalPercentageShares += (parseFloat(form.value.primary_doctor_share) || 0);
        if (form.value.assistant_doctor_is_percentage) totalPercentageShares += (parseFloat(form.value.assistant_doctor_share) || 0);
        if (form.value.technician_is_percentage) totalPercentageShares += (parseFloat(form.value.technician_share) || 0);
        if (form.value.clinic_is_percentage) totalPercentageShares += (parseFloat(form.value.clinic_share) || 0);

        if (allSharesArePercentages && totalPercentageShares !== 100) {
            errors.value.fee_distribution_shares = `Fee distribution percentages must sum to 100%. Current total: ${totalPercentageShares}%`;
        } else if (!allSharesArePercentages && totalPercentageShares === 0) {
            // If not all are percentages, but the sum of percentage parts is 0,
            // it might indicate an incomplete setup if fixed values aren't set
            // This logic might need refinement based on exact business rules for mixed models
        }
    }


    return Object.keys(errors.value).length === 0;
};

const submitForm = async () => {
    if (!validateForm()) {
        toaster.error('Please fix the form errors before submitting.');
        return;
    }

    loading.value = true;

    try {
        const dataToSubmit = { ...form.value }; // Copy the current form state

        // Clean up temporary local UI toggles and format data for backend
        if (!dataToSubmit.night_tariff_active) {
            dataToSubmit.night_tariff = null;
        }
        delete dataToSubmit.night_tariff_active; // Remove this field before sending

        // Ensure array fields are arrays, even if empty
        dataToSubmit.non_applicable_discount_rules = Array.isArray(dataToSubmit.non_applicable_discount_rules) ? dataToSubmit.non_applicable_discount_rules : [];
        dataToSubmit.required_prestations_info = Array.isArray(dataToSubmit.required_prestations_info) ? dataToSubmit.required_prestations_info : [];
        dataToSubmit.required_consents = Array.isArray(dataToSubmit.required_consents) ? dataToSubmit.required_consents : [];


        // Format share values based on their `_is_percentage` flag
        const sharesToFormat = [
            'primary_doctor',
            'assistant_doctor',
            'technician',
            'clinic',
        ];

        sharesToFormat.forEach(role => {
            const shareField = `${role}_share`;
            // FIX: Corrected typo here from `_is_is_percentage` to `_is_percentage`
            const isPercentageField = `${role}_is_percentage`;

            if (dataToSubmit[isPercentageField]) {
                // If it's a percentage, append '%'
                if (dataToSubmit[shareField] !== null && dataToSubmit[shareField] !== '') {
                    dataToSubmit[shareField] = `${parseFloat(dataToSubmit[shareField])}%`;
                } else {
                    dataToSubmit[shareField] = null; // Send null if empty but percentage is active
                }
            } else {
                // If it's a fixed amount, ensure it's a string (as per Laravel validation)
                if (dataToSubmit[shareField] !== null && dataToSubmit[shareField] !== '') {
                    // CRUCIAL FIX: Convert the number to a string here
                    dataToSubmit[shareField] = String(parseFloat(dataToSubmit[shareField]));
                } else {
                    dataToSubmit[shareField] = null; // Send null if empty and not percentage
                }
            }
        });


        const response = isEditMode.value
            ? await prestationApiService.updatePrestation(props.prestationData.id, dataToSubmit)
            : await prestationApiService.createPrestation(dataToSubmit);

        const message = isEditMode.value
            ? 'Prestation updated successfully!'
            : 'Prestation created successfully!';

        toaster.success(message);

        emit(isEditMode.value ? 'prestation-updated' : 'prestation-added', response);
        closeModal();

    } catch (err) {
        console.error('Error saving prestation:', err);
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
            // Add a toastr for general form errors if specific ones are present
            toaster.error('Please check the form for errors.');
        } else {
            toaster.error(err.response?.data?.message || 'Failed to save prestation.');
        }
    } finally {
        loading.value = false;
    }
};

const closeModal = () => {
    emit('close');
};

const switchTab = (tab) => {
    activeTab.value = tab;
};


// Handle escape key
const handleEscape = (event) => {
    if (event.key === 'Escape' && props.showModal) {
        closeModal();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
});

// NEW: Computed property for filtered specializations
const filteredSpecializations = computed(() => {
    if (!form.value.service_id || !formOptions.value.specializations) {
        return [];
    }
    return formOptions.value.specializations.filter(
        (specialization) => specialization.service_id === form.value.service_id
    );
});
</script>
<template>
    <Teleport to="body">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal" aria-modal="true" role="dialog"
            aria-labelledby="modal-title">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 id="modal-title" class="modal-title">{{ modalTitle }}</h2>
                    <button @click="closeModal" class="close-button" aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="tab-navigation">
                    <button @click="switchTab('basic')" :class="['tab-button', { active: activeTab === 'basic' }]"
                        aria-controls="tab-basic" :aria-selected="activeTab === 'basic'">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </button>
                    <button @click="switchTab('pricing')" :class="['tab-button', { active: activeTab === 'pricing' }]"
                        aria-controls="tab-pricing" :aria-selected="activeTab === 'pricing'">
                        <i class="fas fa-dollar-sign"></i>
                        Pricing & Payment
                    </button>
                    <button @click="switchTab('fees')" :class="['tab-button', { active: activeTab === 'fees' }]"
                        aria-controls="tab-fees" :aria-selected="activeTab === 'fees'">
                        <i class="fas fa-handshake"></i>
                        Fee Distribution
                    </button>
                    <button @click="switchTab('medical')" :class="['tab-button', { active: activeTab === 'medical' }]"
                        aria-controls="tab-medical" :aria-selected="activeTab === 'medical'">
                        <i class="fas fa-stethoscope"></i>
                        Operational & Clinical
                    </button>
                    <button @click="switchTab('summary')" :class="['tab-button', { active: activeTab === 'summary' }]"
                        aria-controls="tab-summary" :aria-selected="activeTab === 'summary'">
                        <i class="fas fa-clipboard-list"></i>
                        Summary
                    </button>
                    <button @click="switchTab('settings')" :class="['tab-button', { active: activeTab === 'settings' }]"
                        aria-controls="tab-settings" :aria-selected="activeTab === 'settings'">
                        <i class="fas fa-cog"></i>
                        Settings & Other
                    </button>
                </div>

                <div class="modal-body">
                    <form @submit.prevent="submitForm">
                        <div v-if="currentTabComponent" :id="`tab-${activeTab}`" role="tabpanel">
                            <component :is="currentTabComponent" v-bind="sharedProps"
                                :estimatedTotal="estimatedTotal" />
                        </div>

                        <div v-show="activeTab === 'settings'" class="tab-content" id="tab-settings" role="tabpanel">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input v-model="form.is_active" type="checkbox" class="checkbox-input" />
                                            <span class="checkbox-custom"></span>
                                            <span class="checkbox-text">Active Status</span>
                                        </label>
                                        <small class="form-help">Enable this prestation for use in the system.</small>
                                    </div>
                                </div>
                                <p v-if="errors.general" class="error-message full-width">{{ errors.general }}</p>
                            </div>
                        </div>
                    </form>
                </div>

                <ModalFooter :loading="loading" :isEditMode="isEditMode" @cancel="closeModal" @save="submitForm" />
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Scoped styles for the modal structure */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 1000px;
    /* Increased max-width for more content */
    max-height: 90vh;
    /* Adjust as needed */
    display: flex;
    flex-direction: column;
    overflow: hidden;
    /* Ensures content inside doesn't spill */
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 25px;
    border-bottom: 1px solid #eee;
    background-color: #f9f9f9;
}

.modal-title {
    font-size: 1.5rem;
    color: #333;
    margin: 0;
}

.close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #888;
    cursor: pointer;
    transition: color 0.2s;
}

.close-button:hover {
    color: #555;
}

.tab-navigation {
    display: flex;
    border-bottom: 1px solid #eee;
    background-color: #f5f5f5;
    max-height: 70px;
    width: 100%;
}

.tab-button {
    background: none;
    border: none;
    padding: 15px 20px;
    font-size: 0.95rem;
    cursor: pointer;
    color: #555;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    /* Prevent wrapping */
}

.tab-button:hover:not(.active) {
    color: #007bff;
    background-color: #e9ecef;
}

.tab-button.active {
    color: #007bff;
    border-bottom: 3px solid #007bff;
    font-weight: 600;
    background-color: #fff;
}

.modal-body {
    padding: 25px;
    flex-grow: 1;
    /* Allows body to take up available space */
    overflow-y: auto;
    /* Scroll for content inside the body */
}

.modal-body form {
    height: 100%;
    /* Ensure form takes full height for its children */
}

.tab-content {
    /* Styles for individual tab content areas */
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
    position: relative;
    /* For error message positioning */
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    color: #555;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
    /* Include padding in element's total width and height */
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
    outline: none;
}

.input-error {
    border-color: #dc3545;
    /* Red border for errors */
}

.error-message {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 5px;
}

.form-help {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 5px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    margin-top: 10px;
    margin-bottom: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    /* Prevent text selection */
}

.checkbox-input {
    display: none;
    /* Hide default checkbox */
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #ccc;
    border-radius: 4px;
    margin-right: 10px;
    position: relative;
    transition: all 0.2s ease;
    flex-shrink: 0;
    /* Prevent shrinking */
}

.checkbox-input:checked+.checkbox-custom {
    background-color: #007bff;
    border-color: #007bff;
}

.checkbox-input:checked+.checkbox-custom::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 6px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-input:focus+.checkbox-custom {
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.checkbox-text {
    color: #333;
    font-weight: 400;
}

.full-width {
    grid-column: 1 / -1;
    /* Spans all columns in a grid */
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-container {
        width: 95%;
        margin: 10px;
    }

    .modal-header {
        padding: 15px 20px;
    }

    .modal-title {
        font-size: 1.3rem;
    }

    .tab-button {
        padding: 12px 15px;
        font-size: 0.85rem;
    }

    .modal-body {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
        /* Stack columns on small screens */
    }
}
</style>