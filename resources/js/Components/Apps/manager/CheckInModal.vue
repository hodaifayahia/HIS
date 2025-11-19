<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

// PrimeVue components
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Textarea from 'primevue/textarea';
import ProgressSpinner from 'primevue/progressspinner';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import Tag from 'primevue/tag';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    modelValue: {
        type: Boolean,
        default: false,
    },
    prestations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'check-in-success']);

const toast = useToast();

const loading = ref(false);
const loadingSalles = ref(false);
const salles = ref([]);
const selectedSalle = ref(null);
const notes = ref('');
const processingCheckIn = ref(false);

// Handle both v-model and visible prop
const dialogVisible = computed({
    get: () => props.visible || props.modelValue,
    set: (value) => {
        emit('update:visible', value);
        emit('update:modelValue', value);
    },
});

// Get unique specializations from prestations
const uniqueSpecializations = computed(() => {
    const specs = new Map();
    props.prestations.forEach(p => {
        if (p.specialization_id && p.specialization_name) {
            specs.set(p.specialization_id, {
                id: p.specialization_id,
                name: p.specialization_name
            });
        }
    });
    return Array.from(specs.values());
});

// Patient info
const patientInfo = computed(() => {
    if (!props.prestations || props.prestations.length === 0) return null;
    const first = props.prestations[0];
    return {
        name: first.patient_name,
        fiche_reference: first.fiche_reference,
    };
});

// Load available salles when modal opens
watch(dialogVisible, async (isVisible) => {
    if (isVisible && uniqueSpecializations.value.length > 0) {
        await loadAvailableSalles();
    } else {
        salles.value = [];
        selectedSalle.value = null;
        notes.value = '';
    }
}, { immediate: true });

const loadAvailableSalles = async () => {
    loadingSalles.value = true;
    salles.value = [];
    selectedSalle.value = null;

    try {
        // Load salles for all unique specializations
        const allSalles = new Map();
        
        for (const spec of uniqueSpecializations.value) {
            const response = await axios.get(`/api/patient-tracking/available-salles/${spec.id}`);
            
            if (response.data.success && response.data.data) {
                response.data.data.forEach(salle => {
                    if (!allSalles.has(salle.id)) {
                        allSalles.set(salle.id, {
                            ...salle,
                            specializations: [spec]
                        });
                    } else {
                        allSalles.get(salle.id).specializations.push(spec);
                    }
                });
            }
        }
        
        salles.value = Array.from(allSalles.values());
        
        if (salles.value.length === 0) {
            toast.add({
                severity: 'warn',
                summary: 'No Salles Available',
                detail: 'No salles found for the selected specializations.',
                life: 4000,
            });
        }
    } catch (error) {
        console.error('Failed to load salles:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load available salles.',
            life: 4000,
        });
    } finally {
        loadingSalles.value = false;
    }
};

const selectSalle = (salle) => {
    selectedSalle.value = salle;
};

const handleCheckIn = async () => {
    if (!selectedSalle.value) {
        toast.add({
            severity: 'warn',
            summary: 'Selection Required',
            detail: 'Please select a salle before checking in.',
            life: 3000,
        });
        return;
    }

    processingCheckIn.value = true;
    const checkInPromises = [];
    const results = [];

    try {
        // Create check-in requests for each prestation
        for (const prestation of props.prestations) {
            checkInPromises.push(
                axios.post('/api/patient-tracking/check-in', {
                    fiche_navette_item_id: prestation.id,
                    salle_id: selectedSalle.value.id,
                    notes: notes.value || null,
                    update_item_status: true,
                }).then(response => {
                    if (response.data.success) {
                        results.push({
                            success: true,
                            prestation: prestation.prestation_name,
                            data: response.data.data
                        });
                    } else {
                        results.push({
                            success: false,
                            prestation: prestation.prestation_name,
                            error: response.data.message
                        });
                    }
                }).catch(error => {
                    results.push({
                        success: false,
                        prestation: prestation.prestation_name,
                        error: error.response?.data?.message || 'Network error'
                    });
                })
            );
        }

        await Promise.all(checkInPromises);

        const successCount = results.filter(r => r.success).length;
        const failureCount = results.filter(r => !r.success).length;

        if (successCount > 0) {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `${successCount} prestation(s) checked in successfully!`,
                life: 3000,
            });

            // Return the first successful result
            const successResult = results.find(r => r.success);
            emit('check-in-success', successResult?.data);
        }

        if (failureCount > 0) {
            const failedNames = results
                .filter(r => !r.success)
                .map(r => r.prestation)
                .join(', ');
            
            toast.add({
                severity: 'warn',
                summary: 'Partial Success',
                detail: `Failed to check in: ${failedNames}`,
                life: 5000,
            });
        }

        if (successCount > 0) {
            closeModal();
        }
    } catch (error) {
        console.error('Check-in error:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An unexpected error occurred during check-in.',
            life: 4000,
        });
    } finally {
        processingCheckIn.value = false;
    }
};

const closeModal = () => {
    selectedSalle.value = null;
    notes.value = '';
    dialogVisible.value = false;
};
</script>

<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :closable="!processingCheckIn"
        :closeOnEscape="!processingCheckIn"
        class="tw-w-full tw-max-w-4xl"
        header="Check-In Patient to Salle"
    >
        <div v-if="!prestations || prestations.length === 0" class="tw-text-center tw-py-8">
            <p class="tw-text-gray-500">No prestations selected.</p>
        </div>

        <div v-else>
            <!-- Patient Info -->
            <Card class="tw-mb-6 tw-bg-blue-50 tw-border tw-border-blue-200">
                <template #content>
                    <div class="tw-space-y-3">
                        <div class="tw-flex tw-justify-between tw-items-center">
                            <div>
                                <p class="tw-text-sm tw-text-gray-600">Patient</p>
                                <p class="tw-font-bold tw-text-lg tw-text-gray-900">{{ patientInfo?.name }}</p>
                            </div>
                            <div class="tw-text-right">
                                <p class="tw-text-sm tw-text-gray-600">Fiche Reference</p>
                                <p class="tw-font-semibold tw-text-gray-900">{{ patientInfo?.fiche_reference || 'N/A' }}</p>
                            </div>
                        </div>
                        
                        <!-- Prestations List -->
                        <div>
                            <p class="tw-text-sm tw-text-gray-600 tw-mb-2">Prestations to Check-In ({{ prestations.length }})</p>
                            <div class="tw-flex tw-flex-wrap tw-gap-2">
                                <Chip 
                                    v-for="prestation in prestations" 
                                    :key="prestation.id"
                                    class="tw-text-sm"
                                >
                                    <span>{{ prestation.prestation_name }}</span>
                                    <Tag 
                                        v-if="prestation.specialization_name"
                                        :value="prestation.specialization_name"
                                        severity="info"
                                        class="tw-ml-2 tw-text-xs"
                                    />
                                </Chip>
                            </div>
                        </div>

                        <!-- Specializations -->
                        <div v-if="uniqueSpecializations.length > 0">
                            <p class="tw-text-sm tw-text-gray-600 tw-mb-2">Specializations</p>
                            <div class="tw-flex tw-flex-wrap tw-gap-2">
                                <Tag 
                                    v-for="spec in uniqueSpecializations" 
                                    :key="spec.id"
                                    :value="spec.name"
                                    severity="primary"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Loading Salles -->
            <div v-if="loadingSalles" class="tw-text-center tw-py-12">
                <ProgressSpinner class="tw-w-12 tw-h-12" strokeWidth="4" />
                <p class="tw-mt-4 tw-text-gray-600">Loading available salles...</p>
            </div>

            <!-- Salles List -->
            <div v-else-if="salles.length > 0">
                <h3 class="tw-text-lg tw-font-semibold tw-mb-4 tw-text-gray-800">Select a Salle</h3>
                
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4 tw-max-h-96 tw-overflow-y-auto">
                    <Card
                        v-for="salle in salles"
                        :key="salle.id"
                        class="tw-cursor-pointer tw-transition-all tw-duration-200 hover:tw-shadow-lg"
                        :class="{
                            'tw-border-2 tw-border-blue-500 tw-bg-blue-50': selectedSalle?.id === salle.id,
                            'tw-border tw-border-gray-200': selectedSalle?.id !== salle.id,
                        }"
                        @click="selectSalle(salle)"
                    >
                        <template #content>
                            <div class="tw-flex tw-justify-between tw-items-start">
                                <div>
                                    <h4 class="tw-font-bold tw-text-gray-900">{{ salle.name }}</h4>
                                    <p class="tw-text-sm tw-text-gray-600">Number: {{ salle.number }}</p>
                                    <p v-if="salle.description" class="tw-text-xs tw-text-gray-500 tw-mt-1">
                                        {{ salle.description }}
                                    </p>
                                    <div v-if="salle.specializations" class="tw-mt-2">
                                        <Tag 
                                            v-for="spec in salle.specializations"
                                            :key="spec.id"
                                            :value="spec.name"
                                            severity="secondary"
                                            class="tw-text-xs tw-mr-1"
                                        />
                                    </div>
                                </div>
                                <Badge 
                                    :value="salle.current_patients || 0" 
                                    :severity="(salle.current_patients || 0) === 0 ? 'success' : 'warning'"
                                    class="tw-ml-2"
                                />
                            </div>
                            <div class="tw-mt-2 tw-text-xs tw-text-gray-500">
                                {{ salle.current_patients || 0 }} patient(s) currently
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Notes -->
                <div class="tw-mt-6">
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                        Notes (Optional)
                    </label>
                    <Textarea
                        v-model="notes"
                        rows="3"
                        class="tw-w-full"
                        placeholder="Add any notes about the check-in..."
                        :disabled="processingCheckIn"
                    />
                </div>
            </div>

            <!-- No Salles Available -->
            <div v-else class="tw-text-center tw-py-12 tw-bg-yellow-50 tw-rounded-lg tw-border tw-border-yellow-200">
                <i class="pi pi-exclamation-triangle tw-text-4xl tw-text-yellow-500 tw-mb-4"></i>
                <p class="tw-text-gray-700 tw-font-medium">No salles available for the selected specializations.</p>
                <p class="tw-text-sm tw-text-gray-600 tw-mt-2">
                    Please contact administration to configure salles.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="tw-flex tw-justify-end tw-gap-3">
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    @click="closeModal"
                    severity="secondary"
                    :disabled="processingCheckIn"
                />
                <Button
                    :label="`Check In ${prestations.length} Prestation(s)`"
                    icon="pi pi-check"
                    @click="handleCheckIn"
                    :disabled="!selectedSalle || processingCheckIn"
                    :loading="processingCheckIn"
                />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
@reference "../../../../../resources/css/app.css";

:deep(.p-dialog-header) {
    @apply tw-bg-blue-600 tw-text-white;
}
:deep(.p-dialog-title) {
    @apply tw-text-white;
}
:deep(.p-dialog-header-icon) {
    @apply tw-text-white;
}
</style>
