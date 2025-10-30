<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

// PrimeVue components
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Badge from 'primevue/badge';
import Dropdown from 'primevue/dropdown';

const toast = useToast();

const loading = ref(true);
const positions = ref([]);
const occupancy = ref([]);
const errorMessage = ref('');

// Filter states
const selectedSalle = ref(null);
const selectedSpecialization = ref(null);
const selectedStatus = ref(null);
const statusOptions = ref([
    { label: 'All', value: null },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Waiting', value: 'waiting' },
    { label: 'Completed', value: 'completed' },
    { label: 'Cancelled', value: 'cancelled' },
]);

const salles = ref([]);
const specializations = ref([]);

const loadPositions = async () => {
    loading.value = true;
    errorMessage.value = '';
    
    try {
        const params = {};
        if (selectedSalle.value) params.salle_id = selectedSalle.value;
        if (selectedSpecialization.value) params.specialization_id = selectedSpecialization.value;
        if (selectedStatus.value) params.status = selectedStatus.value;

        // Use the history endpoint to show read-only tracking history
        const response = await axios.get('/api/patient-tracking/history', { params });
        
        if (response.data.success) {
            positions.value = response.data.data;
        } else {
            errorMessage.value = response.data.message || 'Failed to load patient positions.';
        }
    } catch (err) {
        console.error('API Error:', err);
        errorMessage.value = 'An unexpected error occurred while fetching data.';
        toast.add({
            severity: 'error',
            summary: 'Network Error',
            detail: errorMessage.value,
            life: 5000,
        });
    } finally {
        loading.value = false;
    }
};

const loadOccupancy = async () => {
    try {
        const response = await axios.get('/api/patient-tracking/salle-occupancy');
        if (response.data.success) {
            occupancy.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to load occupancy:', err);
    }
};

const loadFilters = async () => {
    try {
        // Attempt to load salles and specializations for the filters. If API returns an object
        // with success/data pattern use that, otherwise accept array responses.
        const [salleRes, specRes] = await Promise.all([
            axios.get('/api/salles').catch(() => ({ data: [] })),
            axios.get('/api/specializations').catch(() => ({ data: [] })),
        ]);

        salles.value = salleRes.data?.data ?? salleRes.data ?? [];
        specializations.value = specRes.data?.data ?? specRes.data ?? [];
    } catch (err) {
        console.error('Failed to load filters:', err);
    }
};

const formatDuration = (checkInTime) => {
    if (!checkInTime) return 'N/A';
    
    const checkIn = new Date(checkInTime);
    const now = new Date();
    const diffMs = now - checkIn;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 60) {
        return `${diffMins} mins`;
    } else {
        const hours = Math.floor(diffMins / 60);
        const mins = diffMins % 60;
        return `${hours}h ${mins}m`;
    }
};

// No actions (check-out) in history view; read-only page.

const getStatusSeverity = (status) => {
    switch (status) {
        case 'in_progress': return 'info';
        case 'waiting': return 'warning';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};

onMounted(async () => {
    await Promise.all([loadFilters(), loadPositions(), loadOccupancy()]);
});
</script>

<template>
    <div class="tw-bg-gray-100 tw-min-h-screen tw-p-6 tw-font-sans">
        <div class="tw-max-w-7xl tw-mx-auto">
            <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-mb-6">Patient Positions</h1>

            <!-- Occupancy Stats -->
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-4 tw-mb-6">
                <Card 
                    v-for="stat in occupancy" 
                    :key="stat.salle_id"
                    class="tw-shadow-lg tw-rounded-xl tw-border-none"
                >
                    <template #content>
                        <div class="tw-flex tw-justify-between tw-items-start">
                            <div>
                                <h3 class="tw-font-bold tw-text-gray-900">{{ stat.salle_name }}</h3>
                                <p class="tw-text-sm tw-text-gray-600">{{ stat.salle_number }}</p>
                                <p v-if="stat.specialization" class="tw-text-xs tw-text-gray-500 tw-mt-1">
                                    {{ stat.specialization.name }}
                                </p>
                            </div>
                            <div class="tw-text-right">
                                <Badge 
                                    :value="stat.active_patients" 
                                    :severity="stat.active_patients === 0 ? 'success' : 'warning'"
                                    class="tw-text-lg"
                                />
                                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                                    {{ stat.today_total }} today
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Current Positions Table -->
            <Card class="tw-shadow-xl tw-rounded-2xl tw-border-none tw-overflow-hidden">
                <template #content>
                    <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4 tw-p-6 tw-bg-gray-50 tw-border-b tw-border-gray-200">
                        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">Current Patient Positions</h2>
                        <div class="tw-flex tw-gap-3">
                            <Button
                                label="Refresh"
                                icon="pi pi-refresh"
                                @click="loadPositions"
                                :loading="loading"
                                size="small"
                            />
                        </div>
                    </div>

                    <div v-if="loading" class="tw-text-center tw-py-12">
                        <ProgressSpinner class="tw-w-12 tw-h-12" strokeWidth="4" />
                        <p class="tw-mt-4 tw-text-gray-600">Loading patient positions...</p>
                    </div>

                    <div v-else-if="errorMessage" class="tw-bg-red-100 tw-text-red-700 tw-p-6 tw-rounded-xl tw-shadow-md tw-text-center tw-m-6">
                        <i class="pi pi-exclamation-triangle tw-text-2xl tw-mb-2"></i>
                        <p>{{ errorMessage }}</p>
                    </div>
                    
                    <div v-else-if="positions.length === 0" class="tw-bg-white tw-rounded-xl tw-p-8 tw-shadow-md tw-text-center">
                        <i class="pi pi-check-circle tw-text-6xl tw-text-green-500 tw-mb-4"></i>
                        <h4 class="tw-text-xl tw-font-semibold tw-text-gray-900">No patients currently checked in</h4>
                        <p class="tw-text-gray-600 tw-mt-2">All salles are currently empty.</p>
                    </div>

                    <DataTable
                        v-else
                        :value="positions"
                        class="tw-rounded-xl tw-shadow-sm tw-overflow-hidden"
                        responsiveLayout="scroll"
                        rowHover
                        stripedRows
                        dataKey="id"
                    >
                        <Column field="patient.full_name" header="Patient" sortable></Column>
                        
                        <Column field="prestation.name" header="Prestation" sortable></Column>

                        <Column field="salle.name" header="Salle" sortable>
                            <template #body="{ data }">
                                <div>
                                    <p class="tw-font-semibold">{{ data.salle?.name }}</p>
                                    <p class="tw-text-xs tw-text-gray-500">{{ data.salle?.number }}</p>
                                </div>
                            </template>
                        </Column>

                        <Column field="specialization.name" header="Specialization" sortable></Column>

                        <Column field="check_in_time" header="Check-In Time" sortable>
                            <template #body="{ data }">
                                <div>
                                    <p>{{ new Date(data.check_in_time).toLocaleTimeString() }}</p>
                                    <p class="tw-text-xs tw-text-gray-500">{{ formatDuration(data.check_in_time) }}</p>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="Status" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Check-Out Modal -->
        <Dialog
            v-model:visible="showCheckOutModal"
            modal
            :closable="!processingCheckOut"
            :closeOnEscape="!processingCheckOut"
            class="tw-w-full tw-max-w-lg"
            header="Check-Out Patient"
        >
            <div v-if="selectedTracking">
                <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-mb-4">
                    <p class="tw-font-semibold">{{ selectedTracking.patient?.full_name }}</p>
                    <p class="tw-text-sm tw-text-gray-600">{{ selectedTracking.prestation?.name }}</p>
                    <p class="tw-text-sm tw-text-gray-600">Salle: {{ selectedTracking.salle?.name }}</p>
                </div>

                <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                        Notes (Optional)
                    </label>
                    <Textarea
                        v-model="checkOutNotes"
                        rows="4"
                        class="tw-w-full"
                        placeholder="Add any notes about the check-out..."
                        :disabled="processingCheckOut"
                    />
                </div>
            </div>

            <template #footer>
                <div class="tw-flex tw-justify-end tw-gap-3">
                    <Button
                        label="Cancel"
                        icon="pi pi-times"
                        @click="closeCheckOutModal"
                        severity="secondary"
                        :disabled="processingCheckOut"
                    />
                    <Button
                        label="Check Out"
                        icon="pi pi-check"
                        @click="handleCheckOut"
                        :loading="processingCheckOut"
                        severity="danger"
                    />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
:deep(.p-card-header) {
    @apply p-0;
}
:deep(.p-card-content) {
    @apply p-0;
}
:deep(.p-datatable-thead tr th) {
    @apply bg-gray-100 tw-text-gray-700 tw-font-semibold tw-text-sm;
}
:deep(.p-datatable-tbody tr td) {
    @apply text-gray-800;
}
:deep(.p-datatable-tbody tr:hover) {
    @apply bg-blue-50;
}
</style>
