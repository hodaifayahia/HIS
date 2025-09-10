<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useToastr } from '../../../../toster'; // Assuming toastr is still desired for notifications

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator'; // PrimeVue's Paginator for custom pagination if needed
import ProgressSpinner from 'primevue/progressspinner'; // For loading state
import Calendar from 'primevue/calendar'; // For date filtering

const props = defineProps({
    contractState: String, // This prop still refers to the contract's overall state
    conventionId: String // Renamed from contractid to conventionId for consistency
});

const router = useRouter();
const toast = useToastr(); // Using toastr for notifications (you might want to switch to PrimeVue's Toast service if available in your setup)

const searchQuery = ref("");
const selectedFilter = ref({ label: "By ID", value: "id" }); // PrimeVue Dropdown expects an object
const hasPendingAvenant = ref(false); // Controls 'Add Avenant' button
const items = ref([]); // Data for the table
const loading = ref(false); // Loading state for data fetching

// Pagination states for DataTable (PrimeVue DataTable handles most of this internally)
const first = ref(0); // Index of the first record
const rows = ref(8); // Number of rows to display per page (default for DataTable)
const totalRecords = computed(() => filteredItemsComputed.value.length); // Total records after filtering

const filterOptions = [
    { label: "By ID", value: "id" },
    { label: "By Convention ID", value: "convention_id" },
    { label: "By Status", value: "status" },
    { label: "By Creation Date", value: "created_at" },
];

// Computed property for filtered items (DataTable can also handle filtering, but this remains for custom logic)
const filteredItemsComputed = computed(() => {
    if (!searchQuery.value) return items.value;

    const query = String(searchQuery.value).toLowerCase();
    const filterValue = selectedFilter.value.value; // Access the value from the selected object

    return items.value.filter(item => {
        switch (filterValue) {
            case "id":
                return item.id && String(item.id).includes(query);
            case "convention_id":
                return item.convention_id && String(item.convention_id).includes(query);
            case "status":
                return item.status && String(item.status).toLowerCase().includes(query);
            case "created_at":
                // If searchQuery is a Date object (from Calendar), format it for comparison
                const searchDateFormatted = searchQuery.value instanceof Date
                    ? formatDateForDisplay(searchQuery.value)
                    : query;
                return item.created_at && formatDateForDisplay(item.created_at).includes(searchDateFormatted);
            default:
                return true;
        }
    });
});

const fetchAvenants = async () => {
    loading.value = true;
    try {
        // Corrected API endpoint for fetching all avenants for a convention
        const response = await axios.get(`/api/avenants/convention/${props.conventionId}`);
        console.log("Response data:", response.data);

        // Assuming your API returns an array directly, if it's nested (e.g., {data: [...]}) adjust accordingly
        items.value = response.data;
        first.value = 0; // Reset DataTable pagination on new data

        // Check for pending avenants
        const pendingResponse = await axios.get(`/api/avenants/convention/${props.conventionId}/pending`);
        hasPendingAvenant.value = pendingResponse.data.hasPending;
    } catch (error) {
        console.error("Failed to fetch avenants data:", error);
        toast.error('Failed to load avenants');
    } finally {
        loading.value = false;
    }
};

const createAvenant = async () => {
    try {
        if (!props.conventionId) {
            toast.error('Convention ID is missing for avenant creation.');
            return;
        }
        // Corrected API endpoint for duplicating/creating avenant
        await axios.post(`/api/avenants/convention/${props.conventionId}/duplicate`);
        toast.success('New avenant created successfully');
        // Refresh the data after creating new avenant
        fetchAvenants();
    } catch (error) {
        console.error("Failed to create avenant:", error);
        toast.error('Failed to create avenant');
    }
};

// Fetch avenants data when component is mounted
onMounted(() => {
    fetchAvenants();
});

const getBadgeClass = (status) => {
    switch (String(status).toLowerCase()) { // Ensure case-insensitivity
        case "active":
            return "bg-green-500 text-white font-medium"; // PrimeVue color classes
        case "pending":
        case "pending":
            return "bg-orange-500 text-white font-medium";
        case "expired":
        case "inactive":
        case "archived":
            return "bg-red-500 text-white font-medium";
        case "draft":
            return "bg-gray-500 text-white font-medium";
        default:
            return "bg-blue-500 text-white font-medium"; // Default info badge
    }
};

const moreInfo = (id) => {
    router.push({
        name: 'convention.avenants.details',
        query: { id: id }
    });
};

// Helper to format date for display (DD/MM/YYYY)
const formatDateForDisplay = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        // Handle various date string formats for robust parsing
        let date;
        const parts = dateString.match(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/);
        if (parts) {
            date = new Date(`${parts[3]}-${parts[2]}-${parts[1]}T${parts[4]}:${parts[5]}:${parts[6]}`);
        } else {
            date = new Date(dateString);
        }

        if (isNaN(date.getTime())) return dateString; // Fallback if invalid date
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    } catch (e) {
        console.error("Error formatting date:", e);
        return dateString;
    }
};

// DataTable pagination handler
const onPage = (event) => {
    first.value = event.first;
    rows.value = event.rows;
};
</script>

<template>
    <div class="surface-card border-round-lg shadow-2 p-4">
        <div class="d-flex sm:flex-row justify-content-between align-items-start sm:align-items-center mb-4 gap-3">
            <div class="flex flex-column sm:flex-row gap-2 w-full">
                <Dropdown
                    v-model="selectedFilter"
                    :options="filterOptions"
                    optionLabel="label"
                    placeholder="Select a Filter"
                    class="p-inputtext-sm w-full sm:w-auto"
                />

                <InputText
                    v-if="selectedFilter.value !== 'created_at'"
                    type="text"
                    v-model="searchQuery"
                    placeholder="Search..."
                    class="flex-grow-1 p-inputtext-sm"
                />
                <Calendar
                    v-else
                    v-model="searchQuery"
                    dateFormat="dd/mm/yy"
                    placeholder="Select Date"
                    class="flex-grow-1 p-inputtext-sm"
                    showIcon
                />
            </div>

            <Button
                v-if="!hasPendingAvenant && (props.contractState === 'pending' || props.contractState === 'active')"
                label="Add Avenant"
                icon="pi pi-plus"
                class="p-button-primary border-round-md white-space-nowrap w-full sm:w-auto"
                @click="createAvenant"
            />
        </div>

        <div class="surface-border surface-card border-round-lg border-1 overflow-hidden">
            <div v-if="loading" class="flex flex-column align-items-center justify-content-center py-6 px-4">
                <ProgressSpinner
                    style="width: 50px; height: 50px"
                    strokeWidth="6"
                    fill="var(--surface-card)"
                    animationDuration=".8s"
                    aria-label="Loading"
                />
                <p class="mt-3 text-600 font-medium">Loading avenants...</p>
            </div>

            <div
                v-else-if="filteredItemsComputed.length === 0"
                class="flex flex-column justify-content-center align-items-center py-8 px-4 text-center"
            >
                <div class="bg-blue-50 border-circle w-5rem h-5rem flex align-items-center justify-content-center mb-3">
                    <i class="pi pi-file text-3xl text-blue-400"></i>
                </div>
                <h6 class="text-900 font-semibold mb-2">No avenants found</h6>
                <p class="text-600 mb-0">Try adjusting your search criteria or add a new avenant.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <DataTable
                    :value="filteredItemsComputed"
                    :paginator="true"
                    :rows="rows"
                    :first="first"
                    @page="onPage"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                    :class="{ 'p-datatable-striped': true }"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                    :rowsPerPageOptions="[8, 15, 25, 50]"
                >
                    <Column field="id" header="ID" sortable class="white-space-nowrap">
                        <template #body="slotProps">
                            <span class="font-mono text-sm">#{{ slotProps.data.id }}</span>
                        </template>
                    </Column>
                    <Column field="convention_id" header="Convention ID" sortable class="white-space-nowrap">
                        <template #body="slotProps">
                            <span class="text-sm">{{ slotProps.data.convention_id }}</span>
                        </template>
                    </Column>
                    <Column field="status" header="Status" sortable>
                        <template #body="slotProps">
                            <span :class="['badge', getBadgeClass(slotProps.data.status)]">
                                {{ slotProps.data.status }}
                            </span>
                        </template>
                    </Column>
                    <Column field="created_at" header="Created At" sortable class="white-space-nowrap">
                        <template #body="slotProps">
                            <span class="text-sm text-600">{{ formatDateForDisplay(slotProps.data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column header="Actions" class="white-space-nowrap">
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-info-circle"
                                class="p-button-sm p-button-text p-button-info border-round-md"
                                @click="moreInfo(slotProps.data.id)"
                                v-tooltip.top="'View Details'"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Base container and card styling */
.surface-card {
    background: var(--surface-card);
    transition: all 0.2s ease-in-out;
}

/* Header section flexbox adjustments */
.flex-column.sm\:flex-row {
    flex-direction: column;
}

@media (min-width: 576px) {
    .flex-column.sm\:flex-row {
        flex-direction: row;
    }
    .w-full.sm\:w-auto {
        width: auto;
    }
}

/* PrimeVue specific overrides and enhancements */
.p-inputtext-sm .p-inputtext { /* Adjust size for input fields */
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
}

.p-dropdown { /* Adjust size for dropdown */
    height: auto; /* Allow content to dictate height */
}

.p-button-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--primary-color-text);
}

.p-button-primary:hover {
    background-color: var(--primary-dark-color);
    border-color: var(--primary-dark-color);
}

.p-button-info {
    color: var(--blue-500);
}

.p-button-info:hover {
    background-color: var(--blue-50);
}

/* DataTable specific styling */
:deep(.p-datatable .p-datatable-header) {
    background: var(--surface-50);
    border-bottom: 1px solid var(--surface-200);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: var(--surface-50);
    border-bottom: 1px solid var(--surface-200);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-color-secondary);
    padding: 1rem 0.75rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.875rem 0.75rem;
    border-bottom: 1px solid var(--surface-100);
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: var(--surface-50);
}

/* Badge styles - mapping to PrimeVue/Tailwind-like classes */
.badge {
    padding: 0.3em 0.6em; /* Smaller padding for a more compact badge */
    border-radius: 9999px; /* Fully rounded */
    font-size: 0.75rem; /* Smaller font size */
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 3.5rem; /* Ensure consistent width */
    text-align: center;
}

/* PrimeVue color utilities (or define if not using PrimeFlex) */
.bg-green-500 { background-color: var(--green-500); }
.bg-orange-500 { background-color: var(--orange-500); }
.bg-red-500 { background-color: var(--red-500); }
.bg-gray-500 { background-color: var(--gray-500); }
.bg-blue-500 { background-color: var(--blue-500); }
.text-white { color: white; }
.font-medium { font-weight: 500; }

/* Empty state styling */
.border-circle {
    border-radius: 50%;
}

/* General button hover effects */
.p-button:not(.p-button-text):not(.p-button-link):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease-in-out;
}
</style>