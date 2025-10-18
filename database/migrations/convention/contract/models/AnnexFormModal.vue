<script setup>
import { defineProps, defineEmits, ref, watch, reactive, computed } from 'vue';
import axios from 'axios';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';

// Utility function to format number as DZD currency
const formatDZD = (value) => {
    if (value === null || value === '' || isNaN(value) || value === 0) return '';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

// Utility function to parse DZD input to number
const parseDZD = (value) => {
    if (!value || value.trim() === '') return null;
    const cleaned = value.replace(/[^\d.]/g, '');
    if (cleaned === '' || cleaned === '.') return null;
    const parsed = parseFloat(cleaned);
    return isNaN(parsed) ? null : parsed;
};

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    isEditing: {
        type: Boolean,
        default: false
    },
    formData: {
        type: Object,
        required: true
    },
    services: {
        type: Array,
        default: () => []
    },
    usedServiceIds: {
        type: Array,
        default: () => []
    },
    isLoading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['save', 'close-modal']);

// Internal reactive form state
const internalForm = reactive({
    id: null,
    contract_id: null,
    annex_name: '',
    service_id: null,
    min_price: null,
    prestation_prix_status: 'empty'
});

const displayMinPrice = ref('');
const isEditingPrice = ref(false);
const internalFormErrors = reactive({});

// Reactive state to track if service/price status fields should be disabled
const hasLinkedPrestations = ref(false);

const prestationPrixStatusOptions = ref([
    { label: 'Empty', value: 'empty' },
    { label: 'Convenience Price', value: 'convenience_prix' },
    { label: 'Public Price', value: 'public_prix' }
]);

const availableServices = computed(() => {
    if (props.isEditing) {
        const currentServiceId = internalForm.service_id;
        return props.services.filter(service =>
            !props.usedServiceIds.includes(service.id) || service.id === currentServiceId
        );
    } else {
        return props.services.filter(service => !props.usedServiceIds.includes(service.id));
    }
});

// Watch for changes in props.formData to populate internalForm
watch(() => props.formData, (newVal) => {
    if (newVal) {
        Object.assign(internalForm, newVal);
        if (!isEditingPrice.value) {
            displayMinPrice.value = formatDZD(newVal.min_price);
        }
    }
}, { immediate: true, deep: true });

// NEW WATCH: Trigger checkLinkedPrestations based on modal visibility and edit mode
watch(() => [props.showModal, props.isEditing], async ([newShowModal, newIsEditing]) => {
    if (newShowModal && newIsEditing && internalForm.id && internalForm.service_id) {
        // Only run the check if the modal is shown, it's in edit mode,
        // AND the internal form has valid IDs (meaning formData has populated it)
        await checkLinkedPrestations(internalForm.service_id, internalForm.id);
    } else if (!newShowModal) {
        // When modal closes, reset everything cleanly
        Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
        displayMinPrice.value = '';
        isEditingPrice.value = false;
        hasLinkedPrestations.value = false; // Reset when modal closes
    } else if (newShowModal && !newIsEditing) {
        // If it's add mode, ensure hasLinkedPrestations is false
        hasLinkedPrestations.value = false;
    }
}, { immediate: true });


// Function to check if there are linked prestations
const checkLinkedPrestations = async (serviceId, annexId) => {
    try {
        const response = await axios.get(`/api/prestations/allavailable-for-service-annex/${serviceId}/${annexId}`);
        console.log(`Response for service ${serviceId} and annex ${annexId}:`, response.data);
        hasLinkedPrestations.value = response.data.data.length > 0;
        console.log(`Annex ${annexId} has linked prestations: ${hasLinkedPrestations.value}`);
    } catch (error) {
        console.error("Error checking linked prestations:", error);
        hasLinkedPrestations.value = false; // Assume no linked prestations on error
    }
};


// Client-side validation
const validateInternalForm = () => {
    Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
    let isValid = true;

    if (!internalForm.annex_name || !internalForm.annex_name.trim()) {
        internalFormErrors.annex_name = "Name is required.";
        isValid = false;
    }

    if (internalForm.service_id === null || internalForm.service_id === '') {
        internalFormErrors.service_id = "Service is required.";
        isValid = false;
    }

    if (!internalForm.prestation_prix_status || !['convenience_prix', 'empty', 'public_prix'].includes(internalForm.prestation_prix_status)) {
        internalFormErrors.prestation_prix_status = "Price status is required and must be a valid option.";
        isValid = false;
    }

    if (internalForm.prestation_prix_status !== 'empty' && (internalForm.min_price === null || isNaN(internalForm.min_price) || internalForm.min_price < 0)) {
        internalFormErrors.min_price = "Minimum price must be a non-negative number when price status is set.";
        isValid = false;
    }

    return isValid;
};

const saveAnnex = () => {
    if (validateInternalForm()) {
        emit('save', internalForm);
    }
};

const handleCloseModal = () => {
    Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
    displayMinPrice.value = '';
    isEditingPrice.value = false;
    hasLinkedPrestations.value = false; // Important: reset this on close
    emit('close-modal');
};

const handlePriceInput = (event) => {
    const value = event.target.value;
    displayMinPrice.value = value;
    internalForm.min_price = parseDZD(value);
};

const handlePriceFocus = () => {
    isEditingPrice.value = true;
    if (internalForm.min_price !== null && internalForm.min_price !== '') {
        displayMinPrice.value = internalForm.min_price.toString();
    } else {
        displayMinPrice.value = '';
    }
};

const handlePriceBlur = () => {
    isEditingPrice.value = false;
    displayMinPrice.value = formatDZD(internalForm.min_price);
};

const handlePriceKeydown = (event) => {
    if ([8, 9, 27, 13, 46].indexOf(event.keyCode) !== -1 ||
        (event.keyCode === 65 && event.ctrlKey === true) ||
        (event.keyCode === 67 && event.ctrlKey === true) ||
        (event.keyCode === 86 && event.ctrlKey === true) ||
        (event.keyCode === 88 && event.ctrlKey === true) ||
        (event.keyCode >= 35 && event.keyCode <= 39)) {
        return;
    }
    if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) &&
        (event.keyCode < 96 || event.keyCode > 105) &&
        event.keyCode !== 190 && event.keyCode !== 110) {
        event.preventDefault();
    }
};
</script>

<template>
    <Dialog
        :visible="showModal"
        :modal="true"
        :header="isEditing ? 'Edit Annex' : 'Add Annex'"
        @update:visible="handleCloseModal"
        :style="{ width: '50vw' }"
        :breakpoints="{ '460px': '75vw', '341px': '100vw' }"
        :closable="!isLoading"
    >
        <div class="p-fluid">
            <div v-if="isEditing && hasLinkedPrestations" class="p-message p-message-warn mb-3">
                <div class="p-message-wrapper">
                    <span class="pi pi-exclamation-triangle p-message-icon"></span>
                    <div class="p-message-text">
                        **Important:** To change the **Service** or **Price Status**, you must first remove all linked prestations associated with this annex.
                    </div>
                </div>
            </div>

            <div class="field mb-3">
                <label for="annexName">Name:</label>
                <InputText
                    id="annexName"
                    v-model="internalForm.annex_name"
                    :class="{ 'p-invalid': internalFormErrors.annex_name }"
                    :disabled="isLoading"
                />
                <small class="p-error" v-if="internalFormErrors.annex_name">
                    {{ internalFormErrors.annex_name }}
                </small>
            </div>

            <div class="field mb-3">
                <label for="service">Service:</label>
                <Dropdown
                    id="service"
                    v-model="internalForm.service_id"
                    :options="availableServices" optionLabel="name"
                    optionValue="id"
                    placeholder="Select Service"
                    :class="{ 'p-invalid': internalFormErrors.service_id }"
                    :disabled="isLoading || (isEditing && hasLinkedPrestations)" />
                <small class="p-error" v-if="internalFormErrors.service_id">
                    {{ internalFormErrors.service_id }}
                </small>
            </div>

            <div class="field mb-3">
                <label for="prestationPrixStatus">Price Status:</label>
                <Dropdown
                    id="prestationPrixStatus"
                    v-model="internalForm.prestation_prix_status"
                    :options="prestationPrixStatusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Price Status"
                    :class="{ 'p-invalid': internalFormErrors.prestation_prix_status }"
                    :disabled="isLoading || (isEditing && hasLinkedPrestations)" />
                <small class="p-error" v-if="internalFormErrors.prestation_prix_status">
                    {{ internalFormErrors.prestation_prix_status }}
                </small>
            </div>

            <div class="field mb-3">
                <label for="minPrice">Minimum Price:</label>
                <InputText
                    id="minPrice"
                    v-model="displayMinPrice"
                    @input="handlePriceInput"
                    @focus="handlePriceFocus"
                    @blur="handlePriceBlur"
                    @keydown="handlePriceKeydown"
                    :class="{ 'p-invalid': internalFormErrors.min_price }"
                    :disabled="isLoading"
                    placeholder="0 DZD" style="direction: ltr; text-align: left;"
                />
                <small class="p-error" v-if="internalFormErrors.min_price">
                    {{ internalFormErrors.min_price }}
                </small>
            </div>
        </div>

        <template #footer>
            <Button
                label="Cancel"
                icon="pi pi-times"
                class="p-button-secondary"
                @click="handleCloseModal"
                :disabled="isLoading"
            />
            <Button
                :label="isEditing ? 'Update' : 'Save'"
                icon="pi pi-check"
                class="p-button-primary"
                @click="saveAnnex"
                :loading="isLoading"
            />
        </template>
    </Dialog>
</template>

<style scoped>
/* PrimeVue components come with their own styling. */
/* You might need to add global styles or adjust PrimeVue theme for overall look. */

/* Custom style to ensure right-to-left text alignment for currency inputs */
/* This might be needed if PrimeVue's InputText doesn't handle it by default based on locale */
#minPrice {
    direction: ltr;
    text-align: right;
}

/* Basic spacing for fields within the dialog */
.field {
    margin-bottom: 1.5rem; /* Adjust as needed */
}

.p-error {
    font-size: 0.875em;
    color: var(--red-500); /* PrimeVue's default error color */
    display: block;
    margin-top: 0.25rem;
}

/* Style for the warning message */
.p-message-warn {
    background-color: var(--yellow-100); /* A light yellow for warning */
    color: var(--yellow-800); /* Darker yellow for text */
    border: 1px solid var(--yellow-300);
    padding: 1rem;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.p-message-icon {
    font-size: 1.5rem;
}

.p-message-text {
    flex-grow: 1;
}
</style>