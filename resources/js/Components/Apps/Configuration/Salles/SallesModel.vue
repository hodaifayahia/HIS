<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    salleData: {
        type: Object,
        default: () => ({
            id: null,
            name: '',
            number: '',
            description: '',
            defult_specialization_id: null,
            specialization_ids: []
        })
    }
});

const emit = defineEmits(['close', 'salle-added', 'salle-updated']);
const toaster = useToastr();

// Reactive data
const saving = ref(false);
const loading = ref(false);
const availableSpecializations = ref([]);
const isEdit = ref(false);

const salle = ref({
    id: null,
    name: '',
    number: '',
    description: '',
    defult_specialization_id: null,
    specialization_ids: []
});

// Form validation errors
const errors = ref({
    name: '',
    number: '',
    description: ''
});

// Computed properties
const modalTitle = computed(() => {
    return isEdit.value ? 'Edit Salle' : 'Add New Salle';
});

const submitButtonLabel = computed(() => {
    return isEdit.value ? 'Update Salle' : 'Create Salle';
});

const isFormValid = computed(() => {
    return salle.value.name.trim() && salle.value.number.trim() && !hasErrors.value;
});

const hasErrors = computed(() => {
    return Object.values(errors.value).some(error => error !== '');
});

// Watchers
watch(() => props.salleData, (newData) => {
    if (newData) {
        isEdit.value = !!newData.id;
        salle.value = {
            id: newData.id || null,
            name: newData.name || '',
            number: newData.number || '',
            description: newData.description || '',
            defult_specialization_id: newData.defult_specialization_id || null,
            specialization_ids: newData.specializations?.map(s => s.id) || []
        };
        clearErrors();
    }
}, { deep: true, immediate: true });

watch(() => props.showModal, (show) => {
    if (show) {
        loadSpecializations();
    } else {
        resetForm();
    }
});

// Methods
const loadSpecializations = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/salles/specializations/available');
        availableSpecializations.value = response.data.data;
    } catch (error) {
        console.error('Error loading specializations:', error);
        toaster.error('Failed to load specializations');
    } finally {
        loading.value = false;
    }
};

const validateField = (field, value) => {
    errors.value[field] = '';

    switch (field) {
        case 'name':
            if (!value || !value.trim()) {
                errors.value.name = 'Salle name is required';
            } else if (value.length > 255) {
                errors.value.name = 'Salle name must not exceed 255 characters';
            }
            break;
        case 'number':
            if (!value || !value.trim()) {
                errors.value.number = 'Salle number is required';
            } else if (value.length > 50) {
                errors.value.number = 'Salle number must not exceed 50 characters';
            }
            break;
        case 'description':
            if (value && value.length > 1000) {
                errors.value.description = 'Description must not exceed 1000 characters';
            }
            break;
    }
};

const clearErrors = () => {
    errors.value = {
        name: '',
        number: '',
        description: ''
    };
};

const resetForm = () => {
    salle.value = {
        id: null,
        name: '',
        number: '',
        description: '',
        defult_specialization_id: null,
        specialization_ids: []
    };
    clearErrors();
    isEdit.value = false;
};

const saveSalle = async () => {
    // Validate all fields
    validateField('name', salle.value.name);
    validateField('number', salle.value.number);
    validateField('description', salle.value.description);

    if (!isFormValid.value) {
        toaster.error('Please fix the validation errors before submitting');
        return;
    }

    saving.value = true;

    try {
        const data = { ...salle.value };

        if (isEdit.value) {
            const response = await axios.put(`/api/salles/${salle.value.id}`, data);
            toaster.success('Salle updated successfully!');
            emit('salle-updated', response.data.data);
        } else {
            const response = await axios.post('/api/salles', data);
            toaster.success('Salle created successfully!');
            emit('salle-added', response.data.data);
        }

        closeModal();
    } catch (error) {
        console.error('Error saving salle:', error);
        
        // Handle validation errors from server
        if (error.response?.status === 422) {
            const serverErrors = error.response.data.errors;
            for (const field in serverErrors) {
                if (errors.value.hasOwnProperty(field)) {
                    errors.value[field] = serverErrors[field][0];
                }
            }
        } else {
            const errorMessage = error.response?.data?.message || 'Failed to save salle';
            toaster.error(errorMessage);
        }
    } finally {
        saving.value = false;
    }
};

const closeModal = () => {
    emit('close');
};

// Load specializations on component mount
onMounted(() => {
    if (props.showModal) {
        loadSpecializations();
    }
});
</script>

<template>
    <Dialog 
        :visible="showModal" 
        @update:visible="$emit('close')"
        :style="{ width: '600px' }" 
        :header="modalTitle"
        :modal="true"
        @hide="closeModal"
        class="salle-modal"
    >
        <div class="tw-space-y-6">
            <!-- Basic Information -->
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <!-- Name Field -->
                <div class="tw-space-y-2">
                    <label for="salle-name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                        Salle Name *
                    </label>
                    <InputText
                        id="salle-name"
                        v-model="salle.name"
                        placeholder="Enter salle name"
                        class="tw-w-full"
                        :class="{ 'p-invalid': errors.name }"
                        @blur="validateField('name', salle.name)"
                    />
                    <small v-if="errors.name" class="p-error tw-text-xs">{{ errors.name }}</small>
                </div>

                <!-- Number Field -->
                <div class="tw-space-y-2">
                    <label for="salle-number" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                        Salle Number *
                    </label>
                    <InputText
                        id="salle-number"
                        v-model="salle.number"
                        placeholder="Enter salle number"
                        class="tw-w-full"
                        :class="{ 'p-invalid': errors.number }"
                        @blur="validateField('number', salle.number)"
                    />
                    <small v-if="errors.number" class="p-error tw-text-xs">{{ errors.number }}</small>
                </div>
            </div>

            <!-- Default Specialization -->
            <div class="tw-space-y-2">
                <label for="default-specialization" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                    Default Specialization
                </label>
                <Dropdown
                    id="default-specialization"
                    v-model="salle.defult_specialization_id"
                    :options="availableSpecializations"
                     appendTo="self"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select a default specialization"
                    class="tw-w-full"
                    :loading="loading"
                    showClear
                />
                <small class="tw-text-xs tw-text-gray-500">
                    Optional: Select a primary specialization for this salle
                </small>
            </div>

            <!-- Assigned Specializations -->
            <div class="tw-space-y-2">
                <label for="specializations" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                    Assigned Specializations
                </label>
                <MultiSelect
                    id="specializations"
                    v-model="salle.specialization_ids"
                    :options="availableSpecializations"
                    appendTo="self"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select specializations to assign"
                    class="tw-w-full"
                    :loading="loading"
                    :maxSelectedLabels="5"
                />
                <small class="tw-text-xs tw-text-gray-500">
                    Select multiple specializations that can use this salle
                </small>
            </div>

            <!-- Description -->
            <div class="tw-space-y-2">
                <label for="salle-description" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                    Description
                </label>
                <Textarea
                    id="salle-description"
                    v-model="salle.description"
                    placeholder="Enter salle description (optional)"
                    class="tw-w-full"
                    rows="3"
                    :class="{ 'p-invalid': errors.description }"
                    @blur="validateField('description', salle.description)"
                />
                <small v-if="errors.description" class="p-error tw-text-xs">{{ errors.description }}</small>
                <small v-else class="tw-text-xs tw-text-gray-500">
                    Optional: Add any additional details about this salle
                </small>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="tw-text-center tw-py-4">
                <i class="fas fa-spinner fa-spin tw-text-blue-600"></i>
                <p class="tw-text-sm tw-text-gray-600 tw-mt-2">Loading data...</p>
            </div>
        </div>

        <template #footer>
            <div class="tw-flex tw-justify-end tw-gap-3">
                <Button 
                    label="Cancel" 
                    severity="secondary" 
                    @click="closeModal"
                    :disabled="saving"
                />
                <Button 
                    :label="submitButtonLabel"
                    severity="primary" 
                    @click="saveSalle"
                    :loading="saving"
                    :disabled="!isFormValid || loading"
                />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
:deep(.salle-modal .p-dialog-header) {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-bottom: 1px solid #e2e8f0;
}

:deep(.salle-modal .p-dialog-content) {
    padding: 1.5rem;
}

:deep(.salle-modal .p-dialog-footer) {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 1rem 1.5rem;
}

:deep(.p-inputtext) {
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    padding: 0.5rem 0.75rem;
}

:deep(.p-inputtext:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-textarea) {
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    padding: 0.5rem 0.75rem;
    resize: vertical;
}

:deep(.p-textarea:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-dropdown) {
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
}

:deep(.p-dropdown:not(.p-disabled):hover) {
    border-color: #9ca3af;
}

:deep(.p-dropdown:not(.p-disabled).p-focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-multiselect) {
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
}

:deep(.p-multiselect:not(.p-disabled):hover) {
    border-color: #9ca3af;
}

:deep(.p-multiselect:not(.p-disabled).p-focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-invalid) {
    border-color: #ef4444 !important;
}

:deep(.p-invalid:focus) {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.p-error {
    color: #ef4444;
}
</style>