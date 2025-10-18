<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import axios from "axios";

// Import the new components and composables
import AddPrestationDialog from "../models/AddPrestationDialog.vue";
import EditPrestationDialog from "../models/EditPrestationDialog.vue";
import { useSweetAlert } from "../../../../useSweetAlert"; // Adjust path as needed

const props = defineProps({
    contractState: String,
    contractdata: Object,
    avenantpage: String,
    avenantState: String,
    avenantid: String,
});

const toast = useToast();
const swil = useSweetAlert();

// State variables
const searchQuery = ref("");
const searchFilter = ref("prestation_name");

const prestations = ref([]); // This will hold the fetched PrestationPricing records
const selectedPrestations = ref([]); // State for selected rows for bulk delete

const filterOptions = ref([
    { label: "By ID", value: "prestation_id" },
    { label: "By Name", value: "prestation_name" },
    { label: "By Code", value: "formatted_id" },
    { label: "By Global Price", value: "prix" },
]);

// Dialog visibility states for child components
const addDialogVisible = ref(false);
const editDialogVisible = ref(false);
const selectedPrestationForEdit = ref(null); // Holds the item to be edited

// Computed property to check if bulk delete button should be enabled
const hasSelectedPrestations = computed(() => selectedPrestations.value.length > 0);

// Fetch data on component mount
onMounted(async () => {
    await fetchPrestations();
});

// Fetch prestations for the avenant
const fetchPrestations = async () => {
    try {
        const response = await axios.get(
            `/api/prestation-pricings/avenant/${props.avenantid}`
        );
        const rawPrestations = response.data.data || response.data;

        prestations.value = rawPrestations.map((item) => {
            // Ensure patientPercentage is calculated for display in the table
            if (item.pricing && item.pricing.prix > 0) {
                item.patientPercentage = (item.pricing.patient_price / item.pricing.prix) * 100;
            } else {
                item.patientPercentage = 0;
            }
            return item;
        });
        selectedPrestations.value = []; // Clear selections after refetch
    } catch (error) {
        console.error("Error fetching prestations:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to load prestations",
            life: 3000,
        });
    }
};

// Handlers for AddPrestationDialog
const openAddDialog = () => {
    addDialogVisible.value = true;
};

const handlePrestationAdded = async () => {
    addDialogVisible.value = false;
    await fetchPrestations(); // Refresh list after adding
};

// Handlers for EditPrestationDialog
const openEditDialog = (item) => {
    selectedPrestationForEdit.value = item;
    editDialogVisible.value = true;
};

const handlePrestationUpdated = async () => {
    editDialogVisible.value = false;
    await fetchPrestations(); // Refresh list after updating
};

// Single delete prestation handlers (using SweetAlert)
const confirmDelete = async (item) => {
    const result = await swil.fire(
        "Confirm Deletion",
        `Are you sure you want to delete prestation: ${item.prestation_name} (${item.formatted_id})?`,
        "question"
    );

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/prestation-pricings/${item.id}`);
            prestations.value = prestations.value.filter(
                (prestation) => prestation.id !== item.id
            );
            swil.fire("Deleted!", "Prestation has been deleted successfully.", "success");
        } catch (error) {
            console.error("Error deleting prestation:", error);
            swil.fire(
                "Deletion Failed",
                error.response?.data?.message || "Failed to delete prestation.",
                "error"
            );
        }
    } else if (result.isDismissed && result.dismiss === swil.DismissReason.cancel) {
        toast.add({
            severity: "info",
            summary: "Cancelled",
            detail: "Deletion cancelled.",
            life: 3000,
        });
    }
};

// Bulk delete prestation handlers
const confirmBulkDelete = async () => {
    if (selectedPrestations.value.length === 0) {
        toast.add({
            severity: "warn",
            summary: "No Selection",
            detail: "Please select prestations to delete.",
            life: 3000,
        });
        return;
    }

    const result = await swil.fire(
        "Confirm Bulk Deletion",
        `Are you sure you want to delete ${selectedPrestations.value.length} selected prestations? This action cannot be undone.`,
        "warning",
        true // Show confirm button
    );

    if (result.isConfirmed) {
        try {
            const idsToDelete = selectedPrestations.value.map(item => item.id);
            await axios.post(`/api/prestation-pricings/bulk-delete`, { ids: idsToDelete });

            prestations.value = prestations.value.filter(
                (prestation) => !idsToDelete.includes(prestation.id)
            );
            selectedPrestations.value = []; // Clear selections
            swil.fire("Deleted!", "Selected prestations have been deleted successfully.", "success");
        } catch (error) {
            console.error("Error during bulk deletion:", error);
            swil.fire(
                "Bulk Deletion Failed",
                error.response?.data?.message || "Failed to delete selected prestations.",
                "error"
            );
            // Optionally re-fetch data to ensure consistency if partial deletion occurred or error was transient
            await fetchPrestations();
        }
    } else if (result.isDismissed && result.dismiss === swil.DismissReason.cancel) {
        toast.add({
            severity: "info",
            summary: "Cancelled",
            detail: "Bulk deletion cancelled.",
            life: 3000,
        });
    }
};

// Filtered items for the DataTable
const filteredItems = computed(() => {
    if (!searchQuery.value) return prestations.value;

    const searchVal = searchQuery.value.toLowerCase();

    return prestations.value.filter((item) => {
        let fieldValue;

        if (searchFilter.value === "prestation_name") {
            fieldValue = item.prestation_name;
        } else if (searchFilter.value === "prestation_id") {
            fieldValue = item.prestation_id;
        } else if (searchFilter.value === "formatted_id") {
            fieldValue = item.formatted_id;
        } else if (searchFilter.value === "prix") {
            fieldValue = item.pricing?.prix;
        } else {
            fieldValue = item[searchFilter.value];
        }

        return String(fieldValue || "").toLowerCase().includes(searchVal);
    });
});
</script>

<template>
    <div class="prestation-list-container">
        <div class="header-controls">
            <div class="search-filter-group">
                <Dropdown
                    v-model="searchFilter"
                    :options="filterOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Filter By"
                    class="filter-dropdown"
                />
                <InputText
                    v-model="searchQuery"
                    placeholder="Search..."
                    class="search-input"
                />
            </div>
            <div class="action-buttons">
                <Button
                    v-if="
                        props.contractState === 'pending' ||
                        (props.avenantpage === 'yes' && props.avenantState === 'pending')
                    "
                    label="Add Prestation"
                    icon="pi pi-plus"
                    @click="openAddDialog"
                    class="p-button-primary"
                />
                <Button
                    v-if="
                        (props.contractState === 'pending' ||
                        (props.avenantpage === 'yes' && props.avenantState === 'pending')) &&
                        hasSelectedPrestations
                    "
                    label="Delete Selected"
                    icon="pi pi-trash"
                    severity="danger"
                    @click="confirmBulkDelete"
                    :disabled="!hasSelectedPrestations"
                    class="p-button-danger"
                />
            </div>
        </div>

        <DataTable
            :value="filteredItems"
            stripedRows
            paginator
            :rows="8"
            tableStyle="min-width: 50rem"
            v-model:selection="selectedPrestations"
            dataKey="id"
            class="prestation-data-table"
        >
            <Column selectionMode="multiple" headerStyle="width: 3em"></Column>
            <Column field="prestation_id" header="ID"></Column>
            <Column field="prestation_name" header="Name"></Column>
            <Column field="formatted_id" header="Code"></Column>
            <Column field="service" header="Service"></Column>
            <Column field="pricing.prix" header="Global Price"></Column>
            <Column field="pricing.company_price" header="Company Part"></Column>
            <Column field="pricing.patient_price" header="Patient Part"></Column>
            <Column
                v-if="
                    props.contractState === 'pending' ||
                    (props.avenantpage === 'yes' && props.avenantState === 'pending')
                "
                header="Actions"
            >
                <template #body="slotProps">
                    <Button
                        icon="pi pi-pencil"
                        severity="warn"
                        size="small"
                        @click="openEditDialog(slotProps.data)"
                        class="p-button-warning"
                    />
                    <Button
                        icon="pi pi-trash"
                        severity="danger"
                        size="small"
                        class="ml-2 p-button-danger"
                        @click="confirmDelete(slotProps.data)"
                    />
                </template>
            </Column>
            <template #empty>
                <div class="empty-table-message">
                    <i class="pi pi-list empty-icon"></i>
                    <span>No prestations found.</span>
                </div>
            </template>
        </DataTable>

        <AddPrestationDialog
            :visible="addDialogVisible"
            @update:visible="addDialogVisible = $event"
            :avenantId="props.avenantid"
            :contractData="props.contractdata"
            @prestationAdded="handlePrestationAdded"
        />

        <EditPrestationDialog
            :visible="editDialogVisible"
            @update:visible="editDialogVisible = $event"
            :selectedPrestation="selectedPrestationForEdit"
            :contractData="props.contractdata"
            @prestationUpdated="handlePrestationUpdated"
        />

        <Toast />
    </div>
</template>

<style scoped>
/* Main container for padding and overall layout */
.prestation-list-container {
    padding: 1.5rem; /* Equivalent to p-4 */
    width: 100%;
    box-sizing: border-box; /* Include padding in width */
}

/* Header controls for search, filter, and action buttons */
.header-controls {
    display: flex;
    flex-direction: column; /* Stack vertically on small screens */
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem; /* mb-4 is too much for 16px, 1rem is 16px */
    gap: 0.75rem; /* Gap between elements */
}

@media (min-width: 1024px) { /* Equivalent to lg:flex-row */
    .header-controls {
        flex-direction: row;
        gap: 0.5rem; /* Smaller gap on larger screens for a tighter look */
    }
}

/* Group for search input and filter dropdown */
.search-filter-group {
    display: flex;
    flex-grow: 1; /* Allows it to take available space */
    align-items: center;
    gap: 0.5rem;
    width: 100%; /* Full width on small screens */
}

/* Specific styling for PrimeVue Dropdown */
.filter-dropdown {
    border-radius: 0.5rem; /* rounded-lg */
    border: 1px solid var(--surface-border); /* Consistent border */
    /* Add specific PrimeVue classes for smaller size if needed, e.g., p-inputtext-sm */
    flex-shrink: 0; /* Prevent it from shrinking too much */
}

/* Specific styling for PrimeVue InputText */
.search-input {
    width: 100%; /* Make it take available space */
    padding: 0.75rem 1rem; /* p-2 is often smaller, adjusted for better feel */
    border-radius: 0.5rem; /* rounded-lg */
    border: 1px solid var(--surface-border); /* Consistent border */
}

/* Group for action buttons (Add/Delete Selected) */
.action-buttons {
    display: flex;
    gap: 0.5rem; /* Gap between buttons */
    flex-shrink: 0; /* Prevent buttons from shrinking */
    width: 100%; /* Full width on small screens */
    justify-content: flex-end; /* Align to the right on larger screens */
}

@media (min-width: 1024px) {
    .action-buttons {
        width: auto; /* Auto width on larger screens */
    }
}

/* PrimeVue Button overrides for consistent look */
.p-button-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--primary-color-text);
    /* Further customize if needed, e.g., padding, font-size */
}

/* .p-button-primary:hover {
    background-color: var(--primary-dark-color);
    border-color: var(--primary-dark-color);
} */

.p-button-danger {
    background-color: var(--red-500); /* PrimeVue's red */
    border-color: var(--red-500);
    color: white;
}

.p-button-danger:hover {
    background-color: var(--red-600);
    border-color: var(--red-600);
}

.p-button-warning {
    background-color: var(--orange-500); /* PrimeVue's orange */
    border-color: var(--orange-500);
    color: white;
}

.p-button-warning:hover {
    background-color: var(--orange-600);
    border-color: var(--orange-600);
}

/* Specific styling for DataTable */
.prestation-data-table {
    border-radius: 0.5rem; /* Consistent rounded corners */
    overflow: hidden; /* Ensures borders are contained */
    border: 1px solid var(--surface-border); /* Light border around the whole table */
}

/* Override PrimeVue's default table styles for header, cells, etc. */
/* Using :deep() to target nested PrimeVue components' elements */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: var(--surface-100); /* Lighter header background */
    color: var(--text-color-secondary);
    font-weight: 600;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--surface-border);
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:nth-child(even)) {
    background-color: var(--surface-50); /* For striped rows */
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: var(--surface-100); /* Hover effect */
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--surface-border);
}

/* Remove last cell border bottom to avoid double border with paginator */
:deep(.p-datatable .p-datatable-tbody > tr:last-child > td) {
    border-bottom: none;
}

/* Empty table message styling */
.empty-table-message {
    text-align: center;
    color: var(--text-color-secondary);
    padding: 3rem 1rem; /* py-6 is about 3rem, px-4 is about 1rem */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-icon {
    font-size: 2.25rem; /* text-3xl */
    margin-bottom: 0.5rem; /* mb-2 */
    color: var(--surface-400); /* A slightly darker gray for the icon */
}

/* Adjust button spacing within table cells */
.p-button.ml-2 {
    margin-left: 0.5rem; /* Standardizing the margin */
}
</style>