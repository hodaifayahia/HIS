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
import SelectButton from 'primevue/selectbutton';

// Custom components
import CheckInModal from '../../../Components/Apps/manager/CheckInModal.vue';

const toast = useToast();
const router = useRouter();

const loading = ref(true);
const prestations = ref([]);
const groupedPrestations = ref([]);
const errorMessage = ref('');
const processingPrestationId = ref(null);

// Check-in modal state
const showCheckInModal = ref(false);
const selectedPrestationsForCheckIn = ref([]);

// Filter states
const showFilters = ref(false);
const searchQuery = ref('');
const selectedStatus = ref(null);
const selectedDateRange = ref(null);

// Specialization and Doctor filters
const specializations = ref([]);
const selectedSpecialization = ref(null);
const doctors = ref([]);
const selectedDoctor = ref(null);

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

// Group prestations by patient and fiche
const groupPrestationsByPatient = (prestationsList) => {
    const grouped = new Map();
    
    prestationsList.forEach(item => {
        const key = `${item.fiche_navette_id}_${item.patient_name}`;
        
        if (!grouped.has(key)) {
            grouped.set(key, {
                id: key,
                fiche_navette_id: item.fiche_navette_id,
                fiche_reference: item.fiche_reference,
                fiche_date: item.fiche_date,
                patient_name: item.patient_name,
                patient_id: item.patient_id,
                prestations: [],
                specializations: new Map(),
                doctors: new Map(),
                statuses: new Set(),
                payment_statuses: new Set(),
                total_amount: 0,
                paid_amount: 0,
                fiche_status: item.fiche_status,
            });
        }
        
        const group = grouped.get(key);
        group.prestations.push(item);
        
        // Collect unique specializations
        if (item.specialization_id && item.specialization_name) {
            group.specializations.set(item.specialization_id, {
                id: item.specialization_id,
                name: item.specialization_name
            });
        }
        
        // Collect unique doctors
        if (item.doctor_id && item.doctor_name) {
            group.doctors.set(item.doctor_id, {
                id: item.doctor_id,
                name: item.doctor_name,
                specialization_id: item.specialization_id
            });
        }
        
        // Collect statuses and payment info
        group.statuses.add(item.status);
        if (item.payment_status) group.payment_statuses.add(item.payment_status);
        group.total_amount += Number(item.amount || 0);
        group.paid_amount += Number(item.paid_amount || 0);
    });
    
    return Array.from(grouped.values());
};

const filteredGroupedPrestations = computed(() => {
    let filtered = prestations.value;

    // Filter by specialization
    if (selectedSpecialization.value) {
        filtered = filtered.filter(p => String(p.specialization_id) === String(selectedSpecialization.value));
    }

    // Filter by doctor
    if (selectedDoctor.value) {
        filtered = filtered.filter(p => String(p.doctor_id) === String(selectedDoctor.value));
    }

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

    // Group the filtered results
    return groupPrestationsByPatient(filtered);
});

// Computed doctors based on selected specialization
const filteredDoctors = computed(() => {
    if (!selectedSpecialization.value) return doctors.value;
    return doctors.value.filter(d => String(d.specialization_id) === String(selectedSpecialization.value));
});

// Extract unique specializations from prestations
const extractSpecializations = () => {
    const specializationMap = new Map();
    prestations.value.forEach(p => {
        if (p.specialization_id && p.specialization_name) {
            specializationMap.set(p.specialization_id, {
                id: p.specialization_id,
                name: p.specialization_name
            });
        }
    });
    specializations.value = Array.from(specializationMap.values());
};

// Extract unique doctors from prestations
const extractDoctors = () => {
    const doctorMap = new Map();
    prestations.value.forEach(p => {
        if (p.doctor_id && p.doctor_name) {
            doctorMap.set(p.doctor_id, {
                id: p.doctor_id,
                name: p.doctor_name,
                specialization_id: p.specialization_id
            });
        }
    });
    doctors.value = Array.from(doctorMap.values());
};

// Load authenticated user's specializations
const loadUserSpecializations = async () => {
    try {
        const resp = await axios.get('/api/setting/user');
        const user = (resp.data && resp.data.data) ? resp.data.data : resp.data;
        if (user) {
            const specs = [];

            if (user.activeSpecializations && Array.isArray(user.activeSpecializations)) {
                user.activeSpecializations.forEach(as => {
                    if (as.specialization && as.specialization.id) {
                        specs.push({ id: as.specialization.id, name: as.specialization.name });
                    }
                });
            }

            if (user.specializations && Array.isArray(user.specializations)) {
                user.specializations.forEach(s => {
                    if (s.id) specs.push({ id: s.id, name: s.name });
                });
            }

            if (specs.length === 0 && specializations.value.length > 0) {
                specs.push(...specializations.value);
            }

            const map = new Map();
            specs.forEach(s => map.set(s.id, s));
            specializations.value = Array.from(map.values());
        }
    } catch (e) {
        console.warn('Could not load user specializations:', e);
    }
};

// Watch selected specialization and load doctors for it
watch(selectedSpecialization, async (specId) => {
    selectedDoctor.value = null;
    if (!specId) {
        extractDoctors();
        return;
    }

    try {
        const resp = await axios.get(`/api/doctors/specializations/${specId}`);
        const list = (resp.data && resp.data.data) ? resp.data.data : resp.data;
        if (Array.isArray(list)) {
            doctors.value = list.map(d => {
                let name = 'Unknown';
                if (d.user) {
                    name = d.user.name || d.user.fullname || ((d.user.Firstname && d.user.Lastname) ? `${d.user.Firstname} ${d.user.Lastname}` : 'Unknown');
                } else if (d.name) {
                    name = d.name;
                }

                return {
                    id: d.id,
                    name,
                    specialization_id: d.specialization_id || specId
                };
            });
        } else {
            doctors.value = [];
        }
    } catch (err) {
        console.error('Failed to load doctors for specialization', specId, err);
        extractDoctors();
    }
});

const loadPrestations = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.get('/api/reception/fiche-navette/today-pending');
        
        if (response.data.success) {
            prestations.value = response.data.data.map(item => {
                let doctorId = item.doctor_id ?? null;
                let doctorName = item.doctor_name ?? null;

                if (!doctorName && item.doctor) {
                    const d = item.doctor;
                    if (d.user?.name) {
                        doctorName = d.user.name;
                    } else if (d.user?.Firstname && d.user?.Lastname) {
                        doctorName = `${d.user.Firstname} ${d.user.Lastname}`;
                    } else if (d.name) {
                        doctorName = d.name;
                    }
                }

                if (!doctorName && item.doctor_user_name) doctorName = item.doctor_user_name;
                if (!doctorId && item.doctor?.id) doctorId = item.doctor.id;

                return {
                    ...item,
                    patient_name: item.patient_name || item.fiche?.patient?.name || 'Unknown Patient',
                    status: item.status ?? item.fiche_status ?? 'pending',
                    doctor_id: doctorId ?? null,
                    doctor_name: doctorName ?? null,
                };
            });
            
            extractSpecializations();
            extractDoctors();
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

const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = null;
    selectedDateRange.value = null;
    selectedSpecialization.value = null;
    selectedDoctor.value = null;
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

const getOverallStatus = (statuses) => {
    // Priority order for overall status
    if (statuses.has('working')) return 'working';
    if (statuses.has('confirmed')) return 'confirmed';
    if (statuses.has('pending')) return 'pending';
    if (statuses.has('done')) return 'done';
    if (statuses.has('canceled')) return 'canceled';
    return 'pending';
};

const openCheckInModal = (groupedItem) => {
    // Filter prestations based on current filters
    let prestationsToCheckIn = groupedItem.prestations;
    
    // If specialization is selected, only include prestations for that specialization
    if (selectedSpecialization.value) {
        prestationsToCheckIn = prestationsToCheckIn.filter(p => 
            String(p.specialization_id) === String(selectedSpecialization.value)
        );
    }
    
    // If doctor is selected, only include prestations for that doctor
    if (selectedDoctor.value) {
        prestationsToCheckIn = prestationsToCheckIn.filter(p => 
            String(p.doctor_id) === String(selectedDoctor.value)
        );
    }
    
    if (prestationsToCheckIn.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Prestations',
            detail: 'No prestations match the current filters for check-in.',
            life: 3000,
        });
        return;
    }
    
    selectedPrestationsForCheckIn.value = prestationsToCheckIn;
    showCheckInModal.value = true;
};

const canCheckIn = (groupedItem) => {
    // Get prestations based on current filters
    let relevantPrestations = groupedItem.prestations;
    
    if (selectedSpecialization.value) {
        relevantPrestations = relevantPrestations.filter(p => 
            String(p.specialization_id) === String(selectedSpecialization.value)
        );
    }
    
    if (selectedDoctor.value) {
        relevantPrestations = relevantPrestations.filter(p => 
            String(p.doctor_id) === String(selectedDoctor.value)
        );
    }
    
    // Check if at least one prestation can be checked in
    return relevantPrestations.some(item => {
        // Can't check in if status is done, canceled, or already working
        if (item.status === 'done' || item.status === 'canceled' || item.status === 'working') {
            return false;
        }
        
        if (!item.specialization_id) {
            return false;
        }
        
        const paymentType = (item.payment_type ?? '').toString().toLowerCase();
        const paymentStatus = (item.payment_status ?? '').toString().toLowerCase();
        
        if (paymentType === 'pre-paid' || paymentType === 'prepaid') {
            return paymentStatus === 'partial' || paymentStatus === 'paid' || 
                   paymentStatus === 'partially_paid' || paymentStatus === 'partial_paid';
        }
        
        return true;
    });
};

const handleCheckInSuccess = (trackingData) => {
    // Update the status of checked-in prestations
    selectedPrestationsForCheckIn.value.forEach(prestation => {
        const item = prestations.value.find(p => p.id === prestation.id);
        if (item) {
            item.status = 'working';
        }
    });
    
    const count = selectedPrestationsForCheckIn.value.length;
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `${count} prestation(s) checked in successfully`,
        life: 3000,
    });
    
    showCheckInModal.value = false;
    selectedPrestationsForCheckIn.value = [];
};

onMounted(async () => {
    await loadPrestations();
    await loadUserSpecializations();
});
</script>

<template>
    <div class="tw-bg-gray-100 tw-min-h-screen tw-p-6 tw-font-sans">
        <div class="tw-max-w-7xl tw-mx-auto">
            <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-mb-6">Today's Prestations</h1>
            
            <!-- Specialization Filter -->
            <Card v-if="specializations.length > 0" class="tw-mb-4 tw-shadow-md">
                <template #content>
                    <div class="tw-p-4">
                        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">Filter by Specialization</label>
                        <div class="tw-flex tw-flex-wrap tw-gap-2">
                            <Button
                                label="All"
                                :severity="selectedSpecialization === null ? 'primary' : 'secondary'"
                                :outlined="selectedSpecialization !== null"
                                @click="selectedSpecialization = null; selectedDoctor = null;"
                                size="small"
                            />
                            <Button
                                v-for="spec in specializations"
                                :key="spec.id"
                                :label="spec.name"
                                :severity="selectedSpecialization === spec.id ? 'primary' : 'secondary'"
                                :outlined="selectedSpecialization !== spec.id"
                                @click="selectedSpecialization = spec.id; selectedDoctor = null;"
                                size="small"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Doctor Filter -->
            <Card v-if="selectedSpecialization" class="tw-mb-4 tw-shadow-md">
                <template #content>
                    <div class="tw-p-4">
                        <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3">Filter by Doctor</label>
                        <div v-if="filteredDoctors.length > 0" class="tw-flex tw-flex-wrap tw-gap-2">
                            <Button
                                label="All Doctors"
                                :severity="selectedDoctor === null ? 'info' : 'secondary'"
                                :outlined="selectedDoctor !== null"
                                @click="selectedDoctor = null"
                                size="small"
                            />
                            <Button
                                v-for="doctor in filteredDoctors"
                                :key="doctor.id"
                                :label="doctor.name"
                                :severity="selectedDoctor === doctor.id ? 'info' : 'secondary'"
                                :outlined="selectedDoctor !== doctor.id"
                                @click="selectedDoctor = doctor.id"
                                size="small"
                            />
                        </div>
                        <div v-else class="tw-text-sm tw-text-gray-500">
                            No doctors available for this specialization
                        </div>
                    </div>
                </template>
            </Card>
            
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
                    
                    <div v-else-if="filteredGroupedPrestations.length === 0" class="tw-bg-white tw-rounded-xl tw-p-8 tw-shadow-md tw-text-center">
                        <i class="pi pi-check-circle tw-text-6xl tw-text-green-500 tw-mb-4"></i>
                        <h4 class="tw-text-xl tw-font-semibold tw-text-gray-900">All caught up!</h4>
                        <p class="tw-text-gray-600 tw-mt-2">You have no pending or scheduled prestations that match your criteria.</p>
                    </div>

                    <DataTable
                        v-else
                        :value="filteredGroupedPrestations"
                        class="tw-rounded-xl tw-shadow-sm tw-overflow-hidden"
                        responsiveLayout="scroll"
                        rowHover
                        stripedRows
                        dataKey="id"
                    >
                        <Column field="patient_name" header="Patient" sortable class="tw-w-[15%]"></Column>
                        
                        <Column header="Prestations" class="tw-w-[30%]">
                            <template #body="{ data }">
                                <div class="tw-flex tw-flex-wrap tw-gap-1">
                                    <Chip 
                                        v-for="prestation in data.prestations" 
                                        :key="prestation.id"
                                        :label="prestation.prestation_name"
                                        class="tw-text-xs"
                                    />
                                </div>
                            </template>
                        </Column>

                        <Column header="Specializations" class="tw-w-[15%]">
                            <template #body="{ data }">
                                <div class="tw-flex tw-flex-wrap tw-gap-1">
                                    <Tag 
                                        v-for="spec in data.specializations.values()" 
                                        :key="spec.id"
                                        :value="spec.name"
                                        severity="info"
                                        class="tw-text-xs"
                                    />
                                </div>
                            </template>
                        </Column>

                        <Column header="Doctors" class="tw-w-[15%]">
                            <template #body="{ data }">
                                <div class="tw-flex tw-flex-col tw-gap-1">
                                    <span 
                                        v-for="doctor in data.doctors.values()" 
                                        :key="doctor.id"
                                        class="tw-text-sm tw-text-gray-700"
                                    >
                                        {{ doctor.name }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Status" sortable class="tw-w-[10%]">
                            <template #body="{ data }">
                                <Tag 
                                    :value="getOverallStatus(data.statuses)" 
                                    :severity="getFicheStatusSeverity(getOverallStatus(data.statuses))"
                                    class="tw-font-semibold"
                                />
                            </template>
                        </Column>
                        
                        <Column header="Count" class="tw-w-[5%]">
                            <template #body="{ data }">
                                <Badge :value="data.prestations.length" severity="secondary" />
                            </template>
                        </Column>
                        
                        <Column header="Actions" class="tw-w-[10%]">
                            <template #body="{ data }">
                                <Button
                                    icon="pi pi-sign-in"
                                    label="Check-In"
                                    size="small"
                                    @click="openCheckInModal(data)"
                                    :disabled="!canCheckIn(data)"
                                    :severity="canCheckIn(data) ? 'success' : 'secondary'"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Check-In Modal -->
        <CheckInModal 
            :visible="showCheckInModal"
            @update:visible="showCheckInModal = $event"
            :prestations="selectedPrestationsForCheckIn"
            @check-in-success="handleCheckInSuccess"
        />
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
