<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import Textarea from 'primevue/textarea';
import ProgressSpinner from 'primevue/progressspinner';
import Badge from 'primevue/badge';

const props = defineProps({
    visible: Boolean,
    modelValue: Boolean,
    prestations: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'multi-check-in-success']);

const toast = useToast();

const dialogVisible = computed({
    get: () => props.visible !== undefined ? props.visible : props.modelValue,
    set: (value) => {
        emit('update:visible', value);
        emit('update:modelValue', value);
    }
});

const loadingSalles = ref(false);
const salles = ref([]);
const selectedSalle = ref(null);
const notes = ref('');
const processing = ref(false);

// Watch when dialog opens to load salles
watch(() => dialogVisible.value, async (isVisible) => {
    if (isVisible && props.prestations.length > 0) {
        const firstPrestation = props.prestations[0];
        if (firstPrestation.specialization_id) {
            await loadAvailableSalles(firstPrestation.specialization_id);
        }
    } else {
        // Reset when closing
        salles.value = [];
        selectedSalle.value = null;
        notes.value = '';
    }
});

const loadAvailableSalles = async (specializationId) => {
    loadingSalles.value = true;
    salles.value = [];
    selectedSalle.value = null;

    try {
        const response = await axios.get(`/api/patient-tracking/available-salles/${specializationId}`);
        
        if (response.data.success) {
            salles.value = response.data.data;
        } else {
            toast.add({
                severity: 'warn',
                summary: 'No Salles Available',
                detail: response.data.message || 'No salles found for this specialization.',
                life: 4000,
            });
        }
    } catch (error) {
        console.error('Failed to load salles:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to load available salles.',
            life: 4000,
        });
    } finally {
        loadingSalles.value = false;
    }
};

const selectSalle = (salle) => {
    selectedSalle.value = salle;
};

const performMultiCheckIn = async () => {
    if (!selectedSalle.value) {
        toast.add({
            severity: 'warn',
            summary: 'Selection Required',
            detail: 'Please select a salle before checking in.',
            life: 3000,
        });
        return;
    }

    processing.value = true;
    const results = [];
    const errors = [];

    try {
        // Check in each prestation
        for (const prestation of props.prestations) {
            try {
                const response = await axios.post('/api/patient-tracking/check-in', {
                    fiche_navette_item_id: prestation.id,
                    salle_id: selectedSalle.value.id,
                    notes: notes.value || null,
                    update_item_status: true,
                });

                if (response.data.success) {
                    results.push({
                        ...response.data.data,
                        fiche_navette_item_id: prestation.id
                    });
                }
            } catch (error) {
                console.error('Failed to check in prestation:', prestation.id, error);
                errors.push({
                    prestation: prestation.prestation_name,
                    error: error.response?.data?.message || 'Unknown error'
                });
            }
        }

        if (results.length > 0) {
            emit('multi-check-in-success', results);
            closeModal();
        }

        if (errors.length > 0) {
            toast.add({
                severity: 'warn',
                summary: 'Partial Success',
                detail: `${results.length} checked in, ${errors.length} failed`,
                life: 5000,
            });
        }
    } finally {
        processing.value = false;
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
        v-model="dialogVisible"
        modal
        :closable="!processing"
        :closeOnEscape="!processing"
        class="tw-w-full tw-max-w-4xl"
        header="Check-In Multiple Prestations"
    >
        <div v-if="!prestations || prestations.length === 0" class="tw-text-center tw-py-8">
            <p class="tw-text-gray-500">No prestations selected.</p>
        </div>

        <div v-else>
            <!-- Patient & Prestations Info -->
            <Card class="tw-mb-6 tw-bg-blue-50 tw-border tw-border-blue-200">
                <template #content>
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                        <div>
                            <p class="tw-text-sm tw-text-gray-600">Patient</p>
                            <p class="tw-font-semibold tw-text-gray-900">{{ prestations[0]?.patient_name }}</p>
                        </div>
                        <div>
                            <p class="tw-text-sm tw-text-gray-600">Number of Prestations</p>
                            <Badge :value="prestations.length" severity="info" class="tw-mt-1" />
                        </div>
                    </div>
                    <div class="tw-mt-4">
                        <p class="tw-text-sm tw-text-gray-600 tw-mb-2">Prestations to Check-In:</p>
                        <div class="tw-flex tw-flex-wrap tw-gap-2">
                            <Chip
                                v-for="p in prestations"
                                :key="p.id"
                                :label="p.prestation_name"
                                class="tw-bg-blue-100 tw-text-blue-800"
                            />
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
                                </div>
                                <Badge 
                                    :value="salle.current_patients" 
                                    :severity="salle.current_patients === 0 ? 'success' : 'warning'"
                                    class="tw-ml-2"
                                />
                            </div>
                            <div class="tw-mt-2 tw-text-xs tw-text-gray-500">
                                {{ salle.current_patients }} patient(s) currently
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
                        :disabled="processing"
                    />
                </div>
            </div>

            <!-- No Salles Available -->
            <div v-else class="tw-text-center tw-py-12 tw-bg-yellow-50 tw-rounded-lg tw-border tw-border-yellow-200">
                <i class="pi pi-exclamation-triangle tw-text-4xl tw-text-yellow-500 tw-mb-4"></i>
                <p class="tw-text-gray-700 tw-font-medium">No salles available for this specialization.</p>
                <p class="tw-text-sm tw-text-gray-600 tw-mt-2">
                    Please contact administration to configure salles for this specialization.
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
                    :disabled="processing"
                />
                <Button
                    label="Check In All"
                    icon="pi pi-check"
                    @click="performMultiCheckIn"
                    :disabled="!selectedSalle || processing"
                    :loading="processing"
                    severity="success"
                />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
:deep(.p-dialog-header) {
    background-color: #2563eb;
    color: white;
}
:deep(.p-dialog-title) {
    color: white;
}
</style>
