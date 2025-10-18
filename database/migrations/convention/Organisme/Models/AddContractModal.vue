<script setup>
import { ref, defineProps, defineEmits, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import MultiSelect from 'primevue/multiselect'; // Ensure this import is correct

const props = defineProps({
    visible: Boolean,
    isEdit: {
        type: Boolean,
        default: false
    },
    contractData: {
        type: Object,
        default: () => null
    },
    familyAuthOptions: {
        type: Array,
        default: () => [
            { label: "Ascendant", value: "ascendant" },
            { label: "Descendant", value: "descendant" },
            { label: "Conjoint", value: "Conjoint" },
            { label: "Adherent", value: "adherent" },
            { label: "Autre", value: "autre" }
        ]
    }
});

const emit = defineEmits(['close', 'save']);
const toast = useToast();

const formData = ref({
    name: "",
    id: null,
    start_date: null,
    end_date: null,
    min_price: null,
    max_price: null,
    discount_percentage: null,
    status: 'pending',
});

const selectedFamilyAuth = ref([]); // This needs to be an array for MultiSelect
const errors = ref({
    name: "",
    start_date: "",
    end_date: "",
    id: null,
    min_price: "",
    max_price: "",
    discount_percentage: "",
    family_auth: ""
});

const close = () => {
    resetForm();
    emit('close');
};

const hasAnnexes = computed(() => props.contractData?.annexes?.length > 0);

const isFieldEditable = computed(() => {
    if (!props.isEdit) return true;

    if (props.contractData?.status !== 'pending') return false;

    if (!hasAnnexes.value) return true;

    return {
        name: true,
        start_date: true,
        end_date: true,
        id: false,
        min_price: true,
        family_auth: true, // Family auth is editable even with annexes
        max_price: false,
        discount_percentage: false,
        status: false
    };
});

// Helper computed property for MultiSelect disabled state
const isFamilyAuthDisabled = computed(() => {
    if (typeof isFieldEditable.value === 'object') {
        return !isFieldEditable.value.family_auth;
    }
    return !isFieldEditable.value;
});

const resetForm = () => {
    formData.value = {
        name: "",
        start_date: null,
        id: null,
        end_date: null,
        min_price: null,
        max_price: null,
        discount_percentage: null,
        status: 'pending',
    };
    selectedFamilyAuth.value = [];
    errors.value = {
        name: "",
        id: null,
        start_date: "",
        end_date: "",
        min_price: "",
        max_price: "",
        discount_percentage: "",
        family_auth: "",
        status: 'pending',
    };
};

const validateForm = () => {
    let isValid = true;
    errors.value = {
        name: "",
        start_date: "",
        id: null,
        end_date: "",
        min_price: "",
        max_price: "",
        discount_percentage: "",
        family_auth: ""
    };

    if (!formData.value.name) {
        errors.value.name = "Contract name is required";
        isValid = false;
    }

    if (!formData.value.start_date) {
        errors.value.start_date = "Start date is required";
        isValid = false;
    }

    if (!formData.value.end_date) {
        errors.value.end_date = "End date is required";
        isValid = false;
    } else if (formData.value.start_date && new Date(formData.value.start_date) >= new Date(formData.value.end_date)) {
        errors.value.end_date = "End date must be after start date";
        isValid = false;
    }else if (formData.value.end_date && new Date(formData.value.end_date) < new Date()) {
        errors.value.end_date = "End date cannot be in the past";
        isValid = false;
    }

    if (formData.value.min_price !== null && formData.value.max_price !== null &&
        parseFloat(formData.value.min_price) > parseFloat(formData.value.max_price)) {
        errors.value.max_price = "Max price must be greater than min price";
        isValid = false;
    }

    if (formData.value.discount_percentage !== null &&
        (parseFloat(formData.value.discount_percentage) < 0 || parseFloat(formData.value.discount_percentage) > 100)) {
        errors.value.discount_percentage = "Discount percentage must be between 0 and 100";
        isValid = false;
    }

    return isValid;
};

watch(() => props.contractData, (newVal) => {
    if (newVal && props.isEdit) {
        formData.value = {
            name: newVal.contract_name || '',
            id: newVal.id || null,
            start_date: newVal.start_date || null,
            end_date: newVal.end_date || null,
            min_price: newVal.min_price || null,
            max_price: newVal.max_price || null,
            discount_percentage: newVal.discount_percentage || null,
            status: newVal.status || 'pending',
        };
        // Initialize selectedFamilyAuth as an array of values
        if (newVal.family_auth) {
            selectedFamilyAuth.value = newVal.family_auth.split(',');
        } else {
            selectedFamilyAuth.value = [];
        }
    }
}, { immediate: true });

const formatDateForAPI = (date) => {
    if (!date) return null;
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const save = () => {
    if (!validateForm()) {
        return;
    }

    const dataToSave = {
        ...formData.value,
        // Join the array back into a comma-separated string for the API
        family_auth: selectedFamilyAuth.value.length > 0 ? selectedFamilyAuth.value.join(',') : null
    };

    if (dataToSave.start_date) {
        dataToSave.start_date = formatDateForAPI(dataToSave.start_date);
    }
    if (dataToSave.end_date) {
        dataToSave.end_date = formatDateForAPI(dataToSave.end_date);
    }

    emit('save', dataToSave);
    resetForm();
};

const showRestrictionToast = (fieldName) => {
    if (props.isEdit && props.contractData?.status === 'pending' && hasAnnexes.value) {
        const fieldAccess = typeof isFieldEditable.value === 'object' ? isFieldEditable.value[fieldName] : isFieldEditable.value;
        if (!fieldAccess) {
            toast.add({
                severity: 'warn',
                summary: 'Edit Restricted',
                detail: `To modify the ${fieldName.replace('_', ' ')}, you must first remove all existing annexes associated with this contract.`,
                life: 5000
            });
        }
    }
};
</script>

<template>
    <div v-if="visible" class="custom-modal-overlay ">
        <div class="custom-modal-container overflow-auto " @click.stop>
            <div class="custom-modal-header">
                <div class="header-content">
                    <div class="modal-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div>
                        <h3 class="modal-title">{{ props.isEdit ? 'Edit' : 'Add' }} Contract</h3>
                        <p class="modal-subtitle">
                            {{ props.isEdit ? 'Modify existing contract details' : 'Create a new contract entry' }}
                        </p>
                    </div>
                </div>
                <button @click="close" class="modal-close-button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div v-if="props.isEdit && hasAnnexes && props.contractData?.status === 'pending'" class="annexe-info-banner">
                <div class="info-content">
                    <div class="info-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="info-text">
                        <h4 class="info-title">Editing Restrictions</h4>
                        <p class="info-message">
                            This contract has <strong>{{ props.contractData?.annexes?.length }} annexe(s)</strong> attached.
                            To modify the <strong>Max Price</strong> and <strong>Discount Percentage</strong>,
                            you must first remove all existing annexes from this contract.
                        </p>
                    </div>
                </div>
            </div>

            <div class="custom-modal-body ">
                <form @submit.prevent="save" class="modal-form">
                    <div class="form-group">
                        <label for="addContractName" class="form-label">
                            <i class="fas fa-tag label-icon"></i> Name:
                        </label>
                        <input
                            type="text"
                            id="addContractName"
                            v-model="formData.name"
                            :disabled="!isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.name)"
                            class="form-control form-input"
                            :class="{
                                'input-error': errors.name,
                                'input-disabled': !isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.name)
                            }"
                        />
                        <small v-if="errors.name" class="error-message">
                            <i class="fas fa-exclamation-circle"></i> {{ errors.name }}
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="addStartDate" class="form-label">
                                <i class="fas fa-calendar-alt label-icon"></i> Start Date:
                            </label>
                            <input
                                type="date"
                                id="addStartDate"
                                v-model="formData.start_date"
                                :disabled="!isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.start_date)"
                                class="form-control form-input"
                                :class="{
                                    'input-error': errors.start_date,
                                    'input-disabled': !isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.start_date)
                                }"
                            />
                            <small v-if="errors.start_date" class="error-message">
                                <i class="fas fa-exclamation-circle"></i> {{ errors.start_date }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="addEndDate" class="form-label">
                                <i class="fas fa-calendar-alt label-icon"></i> End Date:
                            </label>
                            <input
                                type="date"
                                id="addEndDate"
                                v-model="formData.end_date"
                                :disabled="!isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.end_date)"
                                class="form-control form-input"
                                :class="{
                                    'input-error': errors.end_date,
                                    'input-disabled': !isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.end_date)
                                }"
                            />
                            <small v-if="errors.end_date" class="error-message">
                                <i class="fas fa-exclamation-circle"></i> {{ errors.end_date }}
                            </small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="addMinPrice" class="form-label">
                                <i class="fas fa-dollar-sign label-icon"></i> Min Price:
                            </label>
                            <div class="input-with-addon">
                                <input
                                    type="number"
                                    step="0.01"
                                    id="addMinPrice"
                                    v-model.number="formData.min_price"
                                    :disabled="!isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.min_price)"
                                    class="form-control form-input"
                                    :class="{
                                        'input-error': errors.min_price,
                                        'input-disabled': !isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.min_price)
                                    }"
                                />
                                <span class="input-addon">DZD</span>
                            </div>
                            <small v-if="errors.min_price" class="error-message">
                                <i class="fas fa-exclamation-circle"></i> {{ errors.min_price }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="addMaxPrice" class="form-label">
                                <i class="fas fa-dollar-sign label-icon"></i> Max Price:
                                <span v-if="props.isEdit && hasAnnexes && typeof isFieldEditable === 'object' && !isFieldEditable.max_price"
                                            class="restricted-field-indicator">
                                        <i class="fas fa-lock"></i>
                                </span>
                            </label>
                            <div class="input-with-addon">
                                <input
                                    type="number"
                                    step="0.01"
                                    id="addMaxPrice"
                                    v-model.number="formData.max_price"
                                    :disabled="!isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.max_price)"
                                    class="form-control form-input"
                                    :class="{
                                        'input-error': errors.max_price,
                                        'input-disabled': !isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.max_price)
                                    }"
                                    @focus="showRestrictionToast('max_price')"
                                />
                                <span class="input-addon">DZD</span>
                            </div>
                            <small v-if="errors.max_price" class="error-message">
                                <i class="fas fa-exclamation-circle"></i> {{ errors.max_price }}
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="addDiscountPercentage" class="form-label">
                            <i class="fas fa-percent label-icon"></i> Company Percentage:
                            <span v-if="props.isEdit && hasAnnexes && typeof isFieldEditable === 'object' && !isFieldEditable.discount_percentage"
                                    class="restricted-field-indicator">
                                <i class="fas fa-lock"></i>
                            </span>
                        </label>
                        <div class="input-with-addon">
                            <input
                                type="number"
                                step="0.01"
                                id="addDiscountPercentage"
                                v-model.number="formData.discount_percentage"
                                :disabled="!isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.discount_percentage)"
                                class="form-control form-input"
                                :class="{
                                    'input-error': errors.discount_percentage,
                                    'input-disabled': !isFieldEditable || (typeof isFieldEditable === 'object' && !isFieldEditable.discount_percentage)
                                }"
                                @focus="showRestrictionToast('discount_percentage')"
                            />
                            <span class="input-addon">%</span>
                        </div>
                        <small v-if="errors.discount_percentage" class="error-message">
                            <i class="fas fa-exclamation-circle"></i> {{ errors.discount_percentage }}
                        </small>
                    </div>

                    <div class="">
                        <label class="form-label">
                            <i class="fas fa-users label-icon"></i> Family Authorization:
                        </label>
                        <MultiSelect
                            v-model="selectedFamilyAuth"
                            :options="familyAuthOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select Family Authorizations"
                            :disabled="isFamilyAuthDisabled"
                            class="w-full md:w-20rem"
                            :class="{ 'p-invalid': errors.family_auth, 'input-disabled': isFamilyAuthDisabled }"
                            @focus="showRestrictionToast('family_auth')"
                        />
                        <small v-if="errors.family_auth" class="error-message">
                            <i class="fas fa-exclamation-circle"></i> {{ errors.family_auth }}
                        </small>
                    </div>

                    <div class="custom-modal-footer">
                        <button type="button" class="btn btn-secondary" @click="close">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary ms-2">
                            <i class="fas fa-check me-2"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Annexe Info Banner Styles */
.annexe-info-banner {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-left: 4px solid #3b82f6;
    margin: 0;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.info-content {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.info-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: #3b82f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.125rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.info-text {
    flex-grow: 1;
}

.info-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e40af;
    margin: 0 0 0.5rem 0;
}

.info-message {
    font-size: 0.875rem;
    color: #1e3a8a;
    margin: 0;
    line-height: 1.5;
}

/* Restricted Field Indicator */
.restricted-field-indicator {
    color: #f59e0b;
    margin-left: 0.5rem;
    font-size: 0.75rem;
}

/* Add these styles to your component's style block */
.input-with-addon {
    display: flex;
    align-items: center;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    background-color: #fff;
}

.input-with-addon .form-input {
    flex-grow: 1;
    border: none; /* Remove border from the input itself */
    padding-right: 0; /* Adjust padding if necessary */
}

.input-with-addon .form-input:focus {
    box-shadow: none; /* Remove default focus style if it applies a border */
}

.input-addon {
    padding: 0.375rem 0.75rem;
    margin-bottom: 0;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    text-align: center;
    background-color: #e9ecef;
    border-left: 1px solid #ced4da;
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
}

/* Ensure the error highlighting still works correctly with the new structure */
.input-with-addon .input-error {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem); /* Adjust for the error icon if present */
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.input-with-addon.input-error {
    border-color: #dc3545; /* Apply border to the whole addon container on error */
}

/* All the modal styles from the original component go here */
.custom-modal-overlay {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.8), rgba(55, 65, 81, 0.6));
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1040;
    padding: 1rem;
}

.custom-modal-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
    width: 100%;
    max-width: 42rem;
    max-height: 90vh;
    overflow: hidden;
    position: relative;
    animation: fadeInScale 0.3s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.custom-modal-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
}

.custom-modal-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 2rem;
    border-bottom: 1px solid #e2e8f0;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.custom-modal-header .header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.custom-modal-header .modal-icon {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.custom-modal-header .modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.input-disabled {
    background-color: #f3f4f6 !important;
    cursor: not-allowed !important;
    opacity: 0.7;
}

/* Specific styles for PrimeVue MultiSelect when disabled */
.p-multiselect.p-disabled {
    background-color: #f3f4f6 !important;
    cursor: not-allowed !important;
    opacity: 0.7 !important; /* Added !important for stronger override */
}

/* Add a style to make the PrimeVue MultiSelect match your other form inputs */
.p-multiselect {
    width: 100%;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.p-multiselect:not(.p-disabled):hover {
    border-color: #3b82f6; /* Apply hover style */
}

.p-multiselect:not(.p-disabled).p-focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.p-multiselect.p-invalid {
    border-color: #ef4444; /* Error border for PrimeVue component */
}

.custom-modal-header .modal-subtitle {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0.25rem 0 0 0;
}

.modal-close-button {
    width: 2.5rem;
    height: 2.5rem;
    border: none;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 0.5rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    backdrop-filter: blur(4px);
}

.modal-close-button:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    transform: scale(1.1);
}

.custom-modal-body {
    padding: 2rem;
    max-height: calc(90vh - 8rem);
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    position: relative;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.label-icon {
    color: #6b7280;
    font-size: 0.875rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.form-input::placeholder {
    color: #9ca3af;
}

.input-error {
    border-color: #ef4444;
}

.input-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-message {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 0.25rem;
    border-left: 3px solid #ef4444;
}

.custom-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5rem;
}

/* Custom Scrollbar for modal body */
.custom-modal-body::-webkit-scrollbar {
    width: 6px;
}

.custom-modal-body::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.custom-modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.custom-modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .custom-modal-container {
        max-width: 95%;
    }

    .custom-modal-header,
    .custom-modal-body {
        padding: 1.5rem;
    }

    .annexe-info-banner {
        padding: 1rem 1.5rem;
    }

    .info-content {
        flex-direction: column;
        gap: 0.75rem;
    }

    .custom-modal-header .modal-title {
        font-size: 1.25rem;
    }

    .custom-modal-footer {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>