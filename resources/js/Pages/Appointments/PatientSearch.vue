<script setup>
import { ref, onMounted, watch } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { useToastr } from '@/Components/toster';
import PatientModel from '@/Components/PatientModel.vue';

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
    onSelectPatient: Function
});

const emit = defineEmits(['update:modelValue', 'patientSelected']);

const toastr = useToastr();
const patients = ref([]);
const isLoading = ref(false);
const isModalOpen = ref(false);
const selectedPatient = ref(null);
const searchQuery = ref('');
const isEditMode = ref(false);

const op = ref();
const searchInputRef = ref(null);

watch(() => props.modelValue, (newValue) => {
    if (newValue && !searchQuery.value) {
        searchQuery.value = newValue;
    }
}, { immediate: true });

const resetSearch = () => {
    searchQuery.value = '';
    selectedPatient.value = null;
    patients.value = [];
    if (op.value) {
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
            if (op.value && searchInputRef.value) {
                op.value.show(event, searchInputRef.value.$el || event.target);
            }
        } else {
            if (op.value && searchInputRef.value) {
                op.value.show(event, searchInputRef.value.$el || event.target);
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
        selectedPatient.value = null;
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    isEditMode.value = false;
};

const handlePatientAdded = (newPatient) => {
    closeModal();
    selectPatient(newPatient);
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
        op.value.hide();
    }
};

const onInputFocus = (event) => {
    if (searchQuery.value && patients.value.length > 0 || (searchQuery.value && searchQuery.value.length >= 2 && patients.value.length === 0 && !isLoading.value)) {
        if (op.value && searchInputRef.value) {
            op.value.show(event, searchInputRef.value.$el || event.target);
        }
    }
};
</script>

<template>
    <div class="tw-w-full">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-3 tw-mb-2">
            <!-- Search Input Section -->
            <div class="tw-flex-1 tw-relative">
                <div class="tw-flex tw-items-center tw-gap-2">
                    <InputText
                        ref="searchInputRef"
                        v-model="searchQuery"
                        @input="handleSearch"
                        placeholder="Search patients by name or phone..."
                        class="tw-w-full"
                        @focus="onInputFocus"
                        :disabled="disabled"
                        :readonly="readonly"
                        :placeholder="placeholder"
                    />
                    <Button
                        v-if="searchQuery"
                        icon="pi pi-times"
                        class="p-button-secondary p-button-text tw-ml-auto"
                        @click="resetSearch"
                    />
                </div>
            </div>

            <!-- Action Buttons Section -->
            <div class="tw-flex tw-gap-2 tw-justify-end">
                <Button
                    v-if="selectedPatient"
                    label="Edit Patient"
                    icon="pi pi-user-edit"
                    class="p-button-secondary p-button-sm tw-rounded-full"
                    @click="openModal(true)"
                />
                <Button
                    label="Add New Patient"
                    icon="pi pi-user-plus"
                    class="p-button-primary p-button-sm tw-rounded-full"
                    @click="openModal(false)"
                />
            </div>
        </div>

        <!-- Search Results Overlay Panel -->
        <OverlayPanel ref="op" :showCloseIcon="false" class="tw-patient-overlay">
            <!-- Loading State -->
            <div v-if="isLoading" class="tw-flex tw-items-center tw-justify-center tw-py-6">
                <ProgressSpinner 
                    style="width: 30px; height: 30px" 
                    strokeWidth="6" 
                    animationDuration=".8s" 
                />
                <span class="tw-ml-3 tw-text-gray-600">Searching...</span>
            </div>
            
            <!-- Results Template -->
            <template v-else>
                <!-- Patient Results List -->
                <div v-if="patients.length > 0" class="tw-w-full">
                    <div class="tw-font-semibold tw-text-gray-700 tw-mb-3 tw-px-2">
                        Search Results ({{ patients.length }})
                    </div>
                    <div class="tw-max-h-96 tw-overflow-y-auto">
                        <div 
                            v-for="patient in patients" 
                            :key="patient.id" 
                            class="tw-patient-item tw-flex tw-items-center tw-justify-between tw-py-3 tw-px-4 tw-mb-2 tw-rounded-lg tw-bg-white hover:tw-bg-blue-50 tw-cursor-pointer tw-transition-colors tw-duration-200 tw-border tw-border-gray-200 hover:tw-border-blue-300"
                            @click="selectPatient(patient)"
                        >
                            <div class="tw-flex tw-flex-col">
                                <span class="tw-text-base tw-font-semibold tw-text-gray-800">
                                    {{ patient.first_name }} {{ patient.last_name }}
                                </span>
                                <div class="tw-flex tw-gap-3 tw-mt-1">
                                    <Tag 
                                        icon="pi pi-phone" 
                                        :value="patient.phone" 
                                        severity="info" 
                                        class="tw-text-xs"
                                    />
                                    <Tag 
                                        icon="pi pi-id-card" 
                                        :value="patient.Idnum" 
                                        severity="secondary" 
                                        class="tw-text-xs"
                                    />
                                    <Tag 
                                        icon="pi pi-calendar" 
                                        :value="formatDateOfBirth(patient.dateOfBirth)" 
                                        severity="warning" 
                                        class="tw-text-xs"
                                    />
                                </div>
                            </div>
                            <i class="pi pi-chevron-right tw-text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- No Results State -->
                <div v-else class="tw-flex tw-flex-col tw-items-center tw-py-8">
                    <i class="pi pi-search tw-text-5xl tw-text-gray-300 tw-mb-4"></i>
                    <div class="tw-text-gray-500 tw-text-lg">No patients found</div>
                    <div class="tw-text-gray-400 tw-text-sm tw-mt-1">
                        Try searching with different keywords
                    </div>
                </div>
            </template>
        </OverlayPanel>

        <!-- Patient Modal -->
        <PatientModel
            :show-modal="isModalOpen"
            :spec-data="selectedPatient"
            @close="closeModal"
            @specUpdate="handlePatientAdded"
        />
    </div>
</template>

<style scoped>
/* Custom overlay panel width and positioning */
:deep(.tw-patient-overlay) {
    min-width: 450px !important;
    max-width: 600px !important;
    margin-top: 4px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    border-radius: 8px !important;
    border: 1px solid #e5e7eb !important;
}

:deep(.p-overlaypanel-content) {
    padding: 0.75rem !important;
}

/* PrimeVue button customization */
:deep(.p-button-sm) {
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
}

:deep(.p-inputtext) {
    width: 100% !important;
}

/* Patient item hover effect */
.tw-patient-item:hover {
    transform: translateX(2px);
}

/* Scrollbar styling for results list */
.tw-max-h-96::-webkit-scrollbar {
    width: 6px;
}

.tw-max-h-96::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.tw-max-h-96::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.tw-max-h-96::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
