<script setup>
import { ref, onMounted, watch } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { useToastr } from '@/Components/toster';
import PatientModel from '@/Components/PatientModel.vue'; // Assuming this component is designed to be a standalone modal

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import OverlayPanel from 'primevue/overlaypanel';
import Tag from 'primevue/tag';

const props = defineProps({
    modelValue: String,
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    placeholder: String,
    patientId: Number,
    onSelectPatient: Function // This prop seems unused, consider removing if not needed
});

const emit = defineEmits(['update:modelValue', 'patientSelected']);

const toastr = useToastr();
const patients = ref([]);
const isLoading = ref(false);
const isModalOpen = ref(false); // Controls the PatientModel visibility
const selectedPatient = ref(null);
const searchQuery = ref('');
const isEditMode = ref(false);

// Ref for the OverlayPanel (for search results dropdown)
const op = ref();
// Ref for the InputText element to anchor the OverlayPanel
const searchInputRef = ref(null);


// Watch for modelValue changes to update the input
watch(() => props.modelValue, (newValue) => {
    if (newValue && !searchQuery.value) {
        searchQuery.value = newValue;
    }
}, { immediate: true });

const resetSearch = () => {
    searchQuery.value = '';
    selectedPatient.value = null;
    patients.value = [];
    if (op.value) { // Check if op.value exists before trying to hide
        op.value.hide();
    }
    emit('update:modelValue', '');
    emit('patientSelected', null);
};

const handleSearch = debounce(async (event) => {
    const query = event.target.value;
    searchQuery.value = query;
    emit('update:modelValue', query);

    if (!query || query.length < 2) {
        patients.value = [];
        if (op.value) {
            op.value.hide();
        }
        return;
    }

    try {
        isLoading.value = true;
        const response = await axios.get('/api/patients/search', {
            params: { query }
        });
        patients.value = response.data.data || [];

        const exactMatch = patients.value.find(p =>
            `${p.first_name} ${p.last_name} ${formatDateOfBirth(p.dateOfBirth)} ${p.phone}` === query
        );
        if (exactMatch) {
            selectPatient(exactMatch);
            if (op.value) {
                op.value.hide();
            }
        } else if (patients.value.length > 0) {
            if (op.value && searchInputRef.value && searchInputRef.value.$el) {
                 op.value.show(event, searchInputRef.value.$el);
            }
        } else {
            if (op.value && searchInputRef.value && searchInputRef.value.$el) {
                op.value.show(event, searchInputRef.value.$el); // Show "No results"
            }
        }

    } catch (error) {
        console.error('Error searching patients:', error);
        toastr.error('Failed to search patients');
        patients.value = [];
        if (op.value) {
            op.value.hide();
        }
    } finally {
        isLoading.value = false;
    }
}, 500);

// Watch for changes in patientId to fetch and select patient
watch(() => props.patientId, async (newId) => {
    if (newId) {
        await fetchPatientById(newId);
    }
}, { immediate: true });

const fetchPatientById = async (id) => {
    try {
        const response = await axios.get(`/api/patients/${id}`);
        if (response.data.data) {
            const patient = response.data.data;
            selectedPatient.value = patient;
            searchQuery.value = `${patient.first_name} ${patient.last_name} ${formatDateOfBirth(patient.dateOfBirth)} ${patient.phone}`;
            emit('patientSelected', patient);
        } else {
            console.error('Patient not found:', response.data.message);
        }
    } catch (error) {
        console.error('Error fetching patient by ID:', error);
        toastr.error('Could not find patient by ID');
    }
};

const openModal = (edit = false) => {
    isModalOpen.value = true;
    isEditMode.value = edit;
    if (!edit) {
        selectedPatient.value = null; // Clear selected patient for "Add New"
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    isEditMode.value = false;
};

const handlePatientAdded = (newPatient) => {
    closeModal();
    selectPatient(newPatient); // Select the newly added/updated patient
    toastr.success(isEditMode.value ? 'Patient updated successfully' : 'Patient added successfully');
};

const formatDateOfBirth = (date) => {
    if (!date) return '';
    const [year, month, day] = date.split('-');
    return `${year}/${month}/${day}`;
};

const selectPatient = (patient) => {
    selectedPatient.value = patient;
    emit('patientSelected', patient);
    const patientString = `${patient.first_name} ${patient.last_name} ${formatDateOfBirth(patient.dateOfBirth)} ${patient.phone}`;
    emit('update:modelValue', patientString);
    searchQuery.value = patientString;
    if (op.value) {
        op.value.hide(); // Hide the OverlayPanel after selection
    }
};

// Handle focus to show/hide OverlayPanel
const onInputFocus = (event) => {
    // Only show if there's a query and results, or to show "No results" immediately
    if (searchQuery.value && patients.value.length > 0 || (searchQuery.value && searchQuery.value.length >= 2 && patients.value.length === 0 && !isLoading.value)) {
        if (op.value && searchInputRef.value && searchInputRef.value.$el) {
            op.value.show(event, searchInputRef.value.$el);
        }
    }
};

// No longer using onInputBlur to hide directly, rely on OverlayPanel's auto-hide or manual hide
</script>

<template>
    <div class="p-fluid">
        <div class="p-grid p-align-center p-mb-2">
            <div class="p-col-12 p-md-9 p-pr-md-2 p-overlay-panel-container">
                <div class="p-inputgroup">
                    <InputText
                        ref="searchInputRef"
                        v-model="searchQuery"
                        @input="handleSearch"
                        placeholder="Search patients by name or phone..."
                        class="p-inputtext-lg"
                        @focus="onInputFocus"
                        :disabled="disabled"
                        :readonly="readonly"
                        :placeholder="placeholder"
                    />
                    <Button
                        v-if="searchQuery"
                        icon="pi pi-times"
                        class="p-button-secondary p-button-text"
                        @click="resetSearch"
                    />
                </div>
            </div>

            <div class="p-col-12 p-md-3 p-d-flex p-jc-end p-gap-2">
                <Button
                    v-if="selectedPatient"
                    label="Edit Patient"
                    icon="pi pi-user-edit"
                    class="p-button-secondary p-button-sm p-button-rounded"
                    @click="openModal(true)"
                />
                <Button
                    label="Add New Patient"
                    icon="pi pi-user-plus"
                    class="p-button-primary p-button-sm p-button-rounded"
                    @click="openModal(false)"
                />
            </div>
        </div>

        <OverlayPanel ref="op" appendTo="body" :showCloseIcon="false" class="p-overlaypanel-fixed-width">
            <div v-if="isLoading" class="p-d-flex p-ai-center p-jc-center p-py-3">
                <ProgressSpinner style="width: 30px; height: 30px" strokeWidth="6" animationDuration=".8s" />
                <span class="p-ml-2">Searching...</span>
            </div>
            <template v-else>
                <div v-if="patients.length > 0" class="patient-list-container">
                    <div class="p-text-bold p-mb-2">Search Results</div>
                    <div v-for="patient in patients" :key="patient.id" class="patient-item p-d-flex p-ai-center p-py-2 p-px-1" @click="selectPatient(patient)">
                        <span class="p-text-sm p-mr-2 p-text-bold">{{ patient.first_name }} {{ patient.last_name }}</span>
                        <Tag icon="pi pi-phone" :value="patient.phone" severity="info" class="p-mr-2" />
                        <Tag icon="pi pi-id-card" :value="patient.Idnum" severity="secondary" class="p-mr-2" />
                        <Tag icon="pi pi-calendar" :value="formatDateOfBirth(patient.dateOfBirth)" severity="warning" />
                    </div>
                </div>
                <div v-else class="p-d-flex p-flex-column p-ai-center p-py-3">
                    <i class="pi pi-search" style="font-size: 2rem; color: var(--surface-400);"></i>
                    <div class="p-text-secondary p-mt-2">No patients found</div>
                </div>
            </template>
        </OverlayPanel>

        <PatientModel
            :show-modal="isModalOpen"
            :spec-data="selectedPatient"
            @close="closeModal"
            @specUpdate="handlePatientAdded"
        />
    </div>
</template>

<style scoped>
/* PrimeFlex classes handle most of the layout */
/* p-inputgroup for input and clear button alignment */
/* p-d-flex, p-jc-end, p-gap-2 for button layout */

.p-inputgroup {
    display: flex; /* Ensure proper grouping */
    width: 100%; /* Take full width of its parent column */
}

/* Custom width for the OverlayPanel to ensure it aligns well with the input */
.p-overlaypanel-fixed-width {
    width: var(--inputtext-width, 100%); /* Use CSS variable if defined, or set a fixed width/max-width */
    min-width: 300px; /* Example minimum width */
    max-width: 600px; /* Example maximum width */
}

.patient-list-container {
    max-height: 350px; /* Adjust height as needed */
    overflow-y: auto;
    border-radius: var(--border-radius);
    background-color: var(--surface-card);
    padding: 0.5rem;
}

.patient-item {
    cursor: pointer;
    font-size: 17px;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background-color 0.2s;
    border-radius: var(--border-radius); /* Match PrimeVue style */
}


.patient-item:hover {
    background-color: var(--surface-hover);
}

.p-button-sm.p-button-rounded {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 2rem;
}

/* Ensure PrimeVue components have adequate spacing */
.p-mb-2 { margin-bottom: 0.5rem; }
.p-pr-md-2 { padding-right: 0.5rem; }
.p-ml-2 { margin-left: 0.5rem; }
.p-mr-2 { margin-right: 0.5rem; }
.p-py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.p-px-1 { padding-left: 0.25rem; padding-right: 0.25rem; }
.p-py-3 { padding-top: 1rem; padding-bottom: 1rem; }
.p-mt-2 { margin-top: 0.5rem; }

/* Additional styles for disabled and readonly states */
.patient-search-disabled {
    background-color: var(--surface-disabled);
    color: var(--text-disabled);
    cursor: not-allowed;
}
</style>