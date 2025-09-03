<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

// PrimeVue components
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Chip from 'primevue/chip';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import Badge from 'primevue/badge';

const toast = useToast();
const router = useRouter();

const loading = ref(true);
const prestations = ref([]);
const errorMessage = ref('');
const processingPrestationId = ref(null);

// Filter states
const showFilters = ref(false);
const searchQuery = ref('');
const selectedStatus = ref(null);
const selectedDateRange = ref(null);

const statusOptions = ref([
    { label: 'All', value: null, severity: 'secondary' },
    { label: 'Pending', value: 'pending', severity: 'warning' },
    { label: 'Confirmed', value: 'confirmed', severity: 'info' },
    { label: 'Working', value: 'working', severity: 'info' },
    { label: 'Canceled', value: 'canceled', severity: 'danger' },
    { label: 'Done', value: 'done', severity: 'success' },
]);

const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedStatus.value || (selectedDateRange.value && selectedDateRange.value[1]);
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (searchQuery.value) count++;
    if (selectedStatus.value) count++;
    if (selectedDateRange.value && selectedDateRange.value[1]) count++;
    return count;
});

const filteredPrestations = computed(() => {
    let filtered = prestations.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(p => 
            p.prestation_name.toLowerCase().includes(query) ||
            p.patient_name.toLowerCase().includes(query) ||
            String(p.fiche_reference).includes(query)
        );
    }
    
    if (selectedStatus.value) {
        filtered = filtered.filter(p => p.status === selectedStatus.value);
    }

    if (selectedDateRange.value && selectedDateRange.value[1]) {
        const [start, end] = selectedDateRange.value;
        const startDate = start ? new Date(start) : null;
        const endDate = end ? new Date(end) : null;

        filtered = filtered.filter(p => {
            const prestationDate = new Date(p.fiche_date);
            return (!startDate || prestationDate >= startDate) && (!endDate || prestationDate <= endDate);
        });
    }

    return filtered;
});

const loadPrestations = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.get('/api/reception/fiche-navette/today-pending');
        
        if (response.data.success) {
            prestations.value = response.data.data.map(item => ({
                ...item,
                patient_name: item.patient_name || item.fiche?.patient?.name || 'Unknown Patient',
                status: item.status ?? item.fiche_status ?? 'pending',
            }));
        } else {
            errorMessage.value = response.data.message || 'Failed to load prestations.';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errorMessage.value,
                life: 4000,
            });
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

const changePrestationStatus = async (item, newStatus) => {
    if (processingPrestationId.value) return;
    processingPrestationId.value = item.id;
    
    try {
        const response = await axios.post(`/api/reception/fiche-navette/${item.id}/update-status`, { status: newStatus });
        
        if (response.data.success) {
            item.status = newStatus;
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Prestation status updated successfully.',
                life: 3000,
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: response.data.message || 'Failed to update status.',
                life: 4000,
            });
        }
    } catch (err) {
        console.error('Status Update Error:', err);
        toast.add({
            severity: 'error',
            summary: 'Network Error',
            detail: 'Failed to connect to the server.',
            life: 5000,
        });
    } finally {
        processingPrestationId.value = null;
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = null;
    selectedDateRange.value = null;
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const formatCurrency = (amount) => {
    const value = Number(amount ?? 0);
    return new Intl.NumberFormat('fr-DZ', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 2,
    }).format(value);
};

const getFicheStatusSeverity = (status) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'working': return 'info';
        case 'done': return 'success';
        case 'canceled': return 'danger';
        default: return 'secondary';
    }
};

// statuses that allow refund actions on their fiche
const refundableFicheStatuses = ['pending', 'confirmed'];

// payment_status values that indicate there is something paid and can be refunded
const refundablePaymentStatuses = ['paid', 'partial', 'partially_paid', 'partial_paid', 'partially'];

const refundItem = (item) => {
    const ficheStatus = (item.fiche_status ?? '').toString().toLowerCase();
    const itemStatus = (item.status ?? '').toString().toLowerCase();

    // block refunds if fiche or item/dependency is already done
    if (ficheStatus === 'done' || itemStatus === 'done') {
        toast.add({ severity: 'warn', summary: 'Not allowed', detail: 'Refunds are not allowed on completed fiches or items.', life: 4000 });
        return;
    }

    // Determine payment eligibility: prefer payment_status, fallback to paid_amount
    const paymentStatus = (item.payment_status ?? '').toString().toLowerCase();
    const hasPaidAmount = Number(item.paid_amount ?? 0) > 0;
    const isRefundableByStatus = refundablePaymentStatuses.includes(paymentStatus) || hasPaidAmount;

    if (!isRefundableByStatus) {
        toast.add({ severity: 'warn', summary: 'No payments', detail: 'This prestation has no payments to refund.', life: 4000 });
        return;
    }

    // Ensure fiche status allows refund as well (user request: only when fiche is pending or confirmed)
    if (!refundableFicheStatuses.includes(ficheStatus)) {
        toast.add({ severity: 'warn', summary: 'Not allowed', detail: 'Refunds are allowed only when fiche is pending or confirmed.', life: 4000 });
        return;
    }

    // Navigate to the caisse payment/refund page (adjust route if you have a named route)
    router.push({ path: '/apps/caisse/payment', query: { fiche_navette_id: item.fiche_navette_id, fiche_navette_item_id: item.id } });
}

const canRefund = (item) => {
    const ficheStatus = (item.fiche_status ?? '').toString().toLowerCase();
    const itemStatus = (item.status ?? '').toString().toLowerCase();

    // must not be done
    if (ficheStatus === 'done' || itemStatus === 'done') return false;

    // fiche must be in refundable states
    if (!refundableFicheStatuses.includes(ficheStatus)) return false;

    // payment check: prefer payment_status, fallback to paid_amount
    const paymentStatus = (item.payment_status ?? '').toString().toLowerCase();
    if (refundablePaymentStatuses.includes(paymentStatus)) return true;

    if (Number(item.paid_amount ?? 0) > 0) return true;

    return false;
}

onMounted(loadPrestations);
</script>

<template>
    <div class="tw-bg-gray-100 tw-min-h-screen tw-p-6 tw-font-sans">
        <div class="tw-max-w-7xl tw-mx-auto">
            <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-mb-6">Today's Prestations</h1>
            
            <Card class="tw-shadow-xl tw-rounded-2xl tw-border-none tw-overflow-hidden">
                <template #content>
                    <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4 tw-p-6 tw-bg-gray-50 tw-border-b tw-border-gray-200">
                        <div class="p-inputgroup tw-flex-1">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-search" />
                            </span>
                            <InputText v-model="searchQuery" placeholder="Search..." />
                        </div>
                        <div class="tw-flex tw-gap-2">
                            <Button
                                :icon="showFilters ? 'pi pi-filter-slash' : 'pi pi-filter'"
                                :label="showFilters ? 'Hide Filters' : 'Show Filters'"
                                class="p-button-outlined"
                                :severity="hasActiveFilters ? 'success' : 'secondary'"
                                @click="toggleFilters"
                            >
                                <Badge v-if="activeFiltersCount > 0" :value="activeFiltersCount" class="tw-ml-1" />
                            </Button>
                            <Button
                                v-if="hasActiveFilters"
                                icon="pi pi-times"
                                class="p-button-outlined p-button-danger"
                                @click="clearFilters"
                                v-tooltip.bottom="'Clear all filters'"
                            />
                            <Button
                                icon="pi pi-refresh"
                                class="p-button-outlined"
                                @click="loadPrestations"
                                v-tooltip.bottom="'Refresh'"
                                :loading="loading"
                            />
                        </div>
                    </div>
                    
                    <div v-if="showFilters" class="tw-p-6 tw-bg-gray-100 tw-border-b tw-border-gray-200">
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
                            <div class="tw-flex tw-flex-col tw-gap-2">
                                <label class="tw-font-semibold tw-text-gray-700 tw-text-sm">Status</label>
                                <Dropdown 
                                    v-model="selectedStatus" 
                                    :options="statusOptions" 
                                    optionLabel="label" 
                                    optionValue="value"
                                    placeholder="All Statuses" 
                                />
                            </div>
                            <div class="tw-flex tw-flex-col tw-gap-2">
                                <label class="tw-font-semibold tw-text-gray-700 tw-text-sm">Period</label>
                                <Calendar 
                                    v-model="selectedDateRange" 
                                    selectionMode="range" 
                                    placeholder="Select a period"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-if="loading" class="tw-text-center tw-py-12">
                        <ProgressSpinner class="tw-w-12 tw-h-12" strokeWidth="4" />
                        <p class="tw-mt-4 tw-text-gray-600">Loading your prestations...</p>
                    </div>

                    <div v-else-if="errorMessage" class="tw-bg-red-100 tw-text-red-700 tw-p-6 tw-rounded-xl tw-shadow-md tw-text-center tw-m-6">
                        <i class="pi pi-exclamation-triangle tw-text-2xl tw-mb-2"></i>
                        <p>{{ errorMessage }}</p>
                    </div>
                    
                    <div v-else-if="filteredPrestations.length === 0" class="tw-bg-white tw-rounded-xl tw-p-8 tw-shadow-md tw-text-center">
                        <i class="pi pi-check-circle tw-text-6xl tw-text-green-500 tw-mb-4"></i>
                        <h4 class="tw-text-xl tw-font-semibold tw-text-gray-900">All caught up!</h4>
                        <p class="tw-text-gray-600 tw-mt-2">You have no pending or scheduled prestations that match your criteria.</p>
                    </div>

                    <DataTable
                        v-else
                        :value="filteredPrestations"
                        class="tw-rounded-xl tw-shadow-sm tw-overflow-hidden"
                        responsiveLayout="scroll"
                        rowHover
                        stripedRows
                        dataKey="id"
                    >
                        
                        
                        <Column field="patient_name" header="Patient" sortable class="tw-w-[15%]"></Column>
                        
                        <Column field="prestation_name" header="Prestation" sortable class="tw-w-[20%]"></Column>

                        <Column field="price" header="Price" sortable class="tw-w-[10%]">
                            <template #body="{ data }">
                                <div class="tw-font-semibold tw-text-right">{{ formatCurrency(data.price) }}</div>
                            </template>
                        </Column>

                        <Column field="specialization_name" header="Specialization" sortable class="tw-w-[15%]">
                            <template #body="{ data }">
                                <Chip :label="data.specialization_name" class="tw-text-xs" />
                            </template>
                        </Column>

                        <Column header="Status" sortable class="tw-w-[15%]">
                            <template #body="{ data }">
                                <Dropdown 
                                    v-model="data.status" 
                                    :options="statusOptions" 
                                    optionLabel="label" 
                                    optionValue="value"
                                    class="tw-w-full"
                                    :placeholder="data.status"
                                    @change="changePrestationStatus(data, data.status)"
                                    :loading="processingPrestationId === data.id"
                                >
                                    <template #value="slotProps">
                                        <Tag 
                                            :value="slotProps.value" 
                                            :severity="getFicheStatusSeverity(slotProps.value)"
                                            class="tw-font-semibold"
                                        />
                                    </template>
                                    <template #option="slotProps">
                                        <Tag 
                                            :value="slotProps.option.value" 
                                            :severity="getFicheStatusSeverity(slotProps.option.value)"
                                            class="tw-font-semibold"
                                        />
                                    </template>
                                </Dropdown>
                            </template>
                        </Column>
                        
                        <Column field="notes" header="Notes" class="tw-w-[15%]"></Column>
                        
                       
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
/* PrimeVue Overrides */
:deep(.p-card-header) {
    @apply tw-p-0;
}
:deep(.p-card-content) {
    @apply tw-p-0;
}
:deep(.p-datatable-thead tr th) {
    @apply tw-bg-gray-100 tw-text-gray-700 tw-font-semibold tw-text-sm;
}
:deep(.p-datatable-tbody tr td) {
    @apply tw-text-gray-800;
}
:deep(.p-datatable-tbody tr:hover) {
    @apply tw-bg-blue-50;
}
:deep(.p-dropdown-panel) {
  width: auto;
  min-width: 100px;
}
</style>