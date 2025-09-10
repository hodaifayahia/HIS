<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { modalityService } from '../../../../Components/Apps/services/modality/modalityService'; // Adjust path as needed
import { useToastr } from '../../../../Components/toster';
import { useSweetAlert } from '../../../../Components/useSweetAlert';
import ModalityModel from '../../../../Components/Apps/Configuration/modalities/ModalityModel.vue';
import ModalityListItem from './ModalityListItem.vue'; // Might be removed if DataTable handles all rendering

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast'; // For PrimeVue Toast

const swal = useSweetAlert();
const customToastr = useToastr(); // Renamed to avoid conflict if using PrimeVue Toast
const toast = useToast(); // PrimeVue Toast instance

// Data
const modalities = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedModality = ref(null);

// Filter options (will be fetched from API)
const filterOptions = ref({
    modality_types: [],
    specializations: [],
    operational_statuses: []
});

// Search and Filter states
const searchQuery = ref('');
const filters = ref({
    modality_type_id: null,
    specialization_id: null,
    operational_status: null,
});

// Pagination
const pagination = ref({
    current_page: 1,
    per_page: 30, // Matches backend default
    total: 0,
    last_page: 1
});

// Sorting
const sortBy = ref('created_at');
const sortDirection = ref('desc');

// UI States
const showFilters = ref(false);

const primaryColor = '#2563eb'; // Tailwind blue-600

// Computed
const hasActiveFilters = computed(() => {
    return searchQuery.value ||
           Object.values(filters.value).some(filter => filter !== '' && filter !== null);
});

const totalFilteredResults = computed(() => {
    return pagination.value.total;
});

// Watch for changes in search and filters
watch(
    [searchQuery, filters, sortBy, sortDirection, () => pagination.value.per_page],
    (newValues, oldValues) => {
        // Only call getModalities if the values actually changed
        if (JSON.stringify(newValues) !== JSON.stringify(oldValues)) {
            pagination.value.current_page = 1; // Reset to the first page
            getModalities();
        }
    },
    { deep: true }
);

/**
 * Fetches the list of modalities using modalityService.
 */
const getModalities = async () => {
    loading.value = true; // Set loading to true at the start
    error.value = null;

    try {
        const params = {
            page: pagination.value.current_page,
            per_page: pagination.value.per_page,
            sort_by: sortBy.value,
            sort_direction: sortDirection.value,
            query: searchQuery.value,
            modality_type_id: filters.value.modality_type_id,
            specialization_id: filters.value.specialization_id,
            operational_status: filters.value.operational_status,
        };

        // Remove empty filters before sending to service
        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                delete params[key];
            }
        });

        const result = await modalityService.getAll(params);

        

        if (result.success) {
            modalities.value = result.data;
            if (result.meta) {
                pagination.value = {
                    current_page: result.meta.current_page,
                    per_page: result.meta.per_page,
                    total: result.meta.total,
                    last_page: result.meta.last_page,
                };
            } else {
                pagination.value = {
                    current_page: 1,
                    per_page: result.data.length,
                    total: result.data.length,
                    last_page: 1,
                };
            }
        } else {
            error.value = result.message || 'Failed to load modalities.';
            customToastr.error(error.value);
        }
    } catch (err) {
        console.error('Unexpected error in getModalities:', err);
        error.value = err.response?.data?.message || 'An unexpected error occurred.';
        customToastr.error(error.value);
    } finally {
        loading.value = false; // Ensure loading is set to false in all cases
    }
};

/**
 * Fetches filter options for dropdowns using modalityService.
 */
const getFilterOptions = async () => {
    try {
        // Assuming your backend route for filter options is now /api/modalities/filter-options
        const result = await modalityService.getFilterOptions(); // Use the service

        if (result.success) {
            filterOptions.value.modality_types = result.data.modality_types || [];
            filterOptions.value.specializations = result.data.specializations || [];
            filterOptions.value.operational_statuses = result.data.operational_statuses || [];
        } else {
            customToastr.error(result.message);
        }
    } catch (err) {
        console.error('Unexpected error in getFilterOptions:', err);
        customToastr.error('An unexpected error occurred while loading filter options.');
    }
};


/**
 * Clears all filters and search.
 */
const clearFilters = () => {
    searchQuery.value = '';
    filters.value = {
        modality_type_id: null,
        specialization_id: null,
        operational_status: null,
    };
    pagination.value.current_page = 1;
    // The watch effect will trigger getModalities()
};

/**
 * Handles pagination change for PrimeVue Paginator.
 */
const onPageChange = (event) => {
    pagination.value.current_page = event.page + 1; // PrimeVue pages are 0-indexed
    pagination.value.per_page = event.rows;
    getModalities();
};

/**
 * Helper to get severity for status tag.
 */
const getStatusSeverity = (status) => {
    switch (status) {
        case 'Working': return 'success';
        case 'Not Working': return 'danger';
        case 'In Maintenance': return 'warning';
        default: return null;
    }
};

/**
 * Opens the ModalityModel for adding a new modality or editing an existing one.
 */
const openModal = (modality = null) => {
    selectedModality.value = modality ? { ...modality } : {
        name: '',
        internal_code: '',
        modality_type_id: null,
        dicom_ae_title: '',
        port: null,
        physical_location_id: null,
        operational_status: 'Working',
        specialization_id: null,
        integration_protocol: '',
        connection_configuration: '',
        data_retrieval_method: '',
        ip_address: '',
        frequency: 'Daily',
        time_slot_duration: null,
        slot_type: 'minutes',
        booking_window: null,
        is_active: true,
        notes: '',
        schedules: [],
        customDates: [],
        availability_months: [],
        start_time_force: null,
        end_time_force: null,
        number_of_patients: null,
    };
    isModalOpen.value = true;
};

/**
 * Closes the ModalityModel.
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedModality.value = null;
};

/**
 * Refreshes the modality list after an action.
 */
const refreshModalities = () => {
    getModalities();
    closeModal();
};

/**
 * Handles the deletion of a modality using modalityService.
 */
const deleteModality = async (id) => {
    const result = await swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        loading.value = true;
        try {
            const deleteResult = await modalityService.delete(id); // Use the service
            if (deleteResult.success) {
                customToastr.success(deleteResult.message);
                await getModalities();
                swal.fire('Deleted!', 'Your modality has been deleted.', 'success');
            } else {
                customToastr.error(deleteResult.message);
                swal.fire('Error!', deleteResult.message, 'error');
            }
        } catch (err) {
            console.error('Unexpected error deleting modality:', err);
            const errorMessage = err.message || 'An unexpected error occurred during deletion.';
            customToastr.error(errorMessage);
            swal.fire('Error!', errorMessage, 'error');
        } finally {
            loading.value = false;
        }
    }
};

// Initialize component
onMounted(() => {
    getModalities();
    getFilterOptions();
});
</script>

<template>
    <div class="modality-page">
        <div class="content-header">
            <div class="header-flex-container">
                <h1 class="page-title">Modality Management</h1>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li>Modalities</li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <div class="card p-card">
                    <div class="card-header ">
                        <h2 class="">Modality List</h2>
                        <div class="">
                            <Button @click="openModal()" class="p-button-primary p-button-sm">
                                <i class="pi pi-plus button-icon"></i>
                                Add New Modality
                            </Button>
                        </div>
                    </div>

                    <div class="search-filter-section">
                        <div class="search-bar p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-search"></i>
                            </span>
                            <InputText
                                v-model="searchQuery"
                                placeholder="Search by name or internal code..."
                            />
                            <Button
                                v-if="searchQuery"
                                icon="pi pi-times"
                                class="p-button-secondary"
                                @click="searchQuery = ''"
                            />
                        </div>

                        <div class="filter-actions">
                            <Button
                                @click="showFilters = !showFilters"
                                :class="{'p-button-secondary': !showFilters, 'p-button-primary': showFilters}"
                                icon="pi pi-filter"
                                label="Toggle Filters"
                            >
                            </Button>

                            <Button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                icon="pi pi-eraser"
                                label="Clear All Filters"
                                class="p-button-danger p-button-outlined"
                            />

                            <span class="results-count ml-auto">{{ totalFilteredResults }} results found</span>
                        </div>

                        <div v-if="showFilters" class="filters-panel p-card mt-3">
                            <div class="p-grid p-fluid">
                                <div class="p-col-12 p-md-4">
                                    <div class="p-field">
                                        <label for="modalityType">Modality Type</label>
                                        <Dropdown
                                            id="modalityType"
                                            v-model="filters.modality_type_id"
                                            :options="filterOptions.modality_types"
                                            optionLabel="name"
                                            optionValue="id"
                                            placeholder="Select a type"
                                            showClear
                                        />
                                    </div>
                                </div>
                                <div class="p-col-12 p-md-4">
                                    <div class="p-field">
                                        <label for="specialization">Specialization</label>
                                        <Dropdown
                                            id="specialization"
                                            v-model="filters.specialization_id"
                                            :options="filterOptions.specializations"
                                            optionLabel="name"
                                            optionValue="id"
                                            placeholder="Select a specialization"
                                            showClear
                                        />
                                    </div>
                                </div>
                                <div class="p-col-12 p-md-4">
                                    <div class="p-field">
                                        <label for="operationalStatus">Operational Status</label>
                                        <Dropdown
                                            id="operationalStatus"
                                            v-model="filters.operational_status"
                                            :options="filterOptions.operational_statuses"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Select status"
                                            showClear
                                        />
                                    </div>
                                </div>
                                </div>
                        </div>
                    </div>

                    <div v-if="loading" class="loading-state">
                        <ProgressSpinner style="width:50px; height:50px" strokeWidth="8" animationDuration=".8s" aria-label="Loading" />
                        <p class="loading-text">Loading modalities...</p>
                    </div>

                    <div v-else-if="error" class="error-message p-message p-message-error" role="alert">
                        <strong class="error-bold">Error!</strong>
                        <span class="error-text">{{ error }}</span>
                    </div>

                    <div v-else-if="modalities.length > 0" class="card">
                        <DataTable
                            :value="modalities"
                            :rows="pagination.per_page"
                            :totalRecords="pagination.total"
                            lazy
                            :first="(pagination.current_page - 1) * pagination.per_page"
                            @page="onPageChange"
                            stripedRows
                            tableStyle="min-width: 50rem"
                        >
                            <Column field="id" header="#">
                                <template #body="slotProps">
                                    {{ slotProps.index + 1 + ((pagination.current_page - 1) * pagination.per_page) }}
                                </template>
                            </Column>
                            <!-- <Column header="Image">
                                <template #body>
                                    <i class="pi pi-image" style="font-size: 2rem"></i>
                                </template>
                            </Column> -->
                            <Column field="name" header="Name" sortable></Column>
                            <Column field="internal_code" header="Internal Code" sortable></Column>
                            <Column header="Type">
                                <template #body="slotProps">
                                    {{ slotProps.data.modality_type ? slotProps.data.modality_type.name : 'N/A' }}
                                </template>
                            </Column>
                            <Column header="Specialization">
                                <template #body="slotProps">
                                    {{ slotProps.data.specialization ? slotProps.data.specialization.name : 'N/A' }}
                                </template>
                            </Column>
                            <Column field="integration_protocol" header="Protocol"></Column>
                            <Column field="connection_configuration" header="Config"></Column>
                            <Column field="data_retrieval_method" header="Data Method"></Column>
                            <Column field="ip_address" header="IP Address"></Column>
                            <Column field="operational_status" header="Status" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.operational_status" :severity="getStatusSeverity(slotProps.data.operational_status)" />
                                </template>
                            </Column>
                            <Column header="Actions" style="width: 100%;">
                                <template #body="slotProps">
                                    <Button
                                        icon="pi pi-pencil"
                                        class="p-button-rounded p-button-info p-button-text"
                                        @click="openModal(slotProps.data)"
                                        v-tooltip.top="'Edit Modality'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        class="p-button-rounded p-button-danger p-button-text ml-2"
                                        @click="deleteModality(slotProps.data.id)"
                                        v-tooltip.top="'Delete Modality'"
                                    />
                                </template>
                            </Column>
                        </DataTable>

                        <Paginator
                            v-if="pagination.total > pagination.per_page"
                            :rows="pagination.per_page"
                            :totalRecords="pagination.total"
                            :rowsPerPageOptions="[10, 25, 50, 100]"
                            @page="onPageChange"
                            template="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                            :currentPageReportTemplate="`Showing {first}-{last} of {totalRecords} modalities`"
                        />
                    </div>

                    <div v-else class="no-modalities">
                        <div class="no-results-content">
                            <i class="pi pi-search no-results-icon"></i>
                            <p class="no-results-text">
                                <span v-if="hasActiveFilters">
                                    No modalities found matching your search criteria.
                                </span>
                                <span v-else>
                                    No modalities found. Click "Add New Modality" to get started!
                                </span>
                            </p>
                            <Button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                icon="pi pi-eraser"
                                label="Clear Filters"
                                class="p-button-secondary mt-3"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ModalityModel
            :show-modal="isModalOpen"
            :modality-data="selectedModality"
            @close="closeModal"
            @modality-updated="refreshModalities"
            @modality-added="refreshModalities"
        />

        <Toast /> </div>
</template>

<style scoped>
/* Keep much of your existing CSS, and adapt for PrimeVue class names. */
/* PrimeVue components often have their own styling and responsive behavior. */

.modality-page {
    padding: 1rem;
    background-color: #f3f4f6;
    min-height: 100vh;
}

.content-header {
    margin-bottom: 1.5rem;
}

.header-flex-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
}

.breadcrumbs {
    font-size: 0.875rem;
}

.breadcrumb-list {
    display: flex;
    align-items: center;
    list-style: none;
    padding: 0;
    margin: 0;
    color: #6b7280;
}

.breadcrumb-list li {
    margin-right: 0.5rem;
}

.breadcrumb-list li:last-child {
    margin-right: 0;
}

.breadcrumb-link {
    color: v-bind(primaryColor);
    text-decoration: none;
}

.breadcrumb-link:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    font-size: 0.75rem;
    margin-left: 0.5rem;
    margin-right: 0.5rem;
}

.container {
    max-width: 80rem;
    margin-left: auto;
    margin-right: auto;
}

.card {
    background-color: #ffffff;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.button-icon {
    margin-right: 0.5rem;
}

/* Search and Filter Section */
.search-filter-section {
    margin-bottom: 1.5rem;
}

.search-bar {
    margin-bottom: 1rem;
}

/* PrimeVue's p-inputgroup handles the positioning of icon */
.p-inputgroup .p-inputgroup-addon {
    background-color: #f3f4f6;
    border-right: none;
}
.p-inputgroup .p-inputtext {
    border-left: none;
}
.p-inputgroup .p-button-secondary {
    border-left: none;
}

.filter-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.results-count {
    font-size: 0.875rem;
    color: #6b7280;
}

.filters-panel {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

/* PrimeVue Field styling */
.p-field {
    margin-bottom: 1rem; /* Adjust spacing between fields */
}
.p-field label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

/* Loading State */
.loading-state {
    text-align: center;
    padding: 2rem;
}

.loading-text {
    color: #4b5563;
    margin-top: 0.5rem;
}

/* Error Message */
.error-message {
    /* PrimeVue p-message classes will handle most styling */
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
}

.error-bold {
    font-weight: 700;
}

.error-text {
    margin-left: 0.5rem;
}

/* No Results */
.no-modalities {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.no-results-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.no-results-icon {
    font-size: 3rem;
    color: #d1d5db;
}

.no-results-text {
    font-size: 1.125rem;
    margin: 0;
}

/* Custom Tag styling for operational status */
.p-tag.p-tag-success {
    background-color: #28a745; /* Green for Working */
    color: #fff;
}
.p-tag.p-tag-warning {
    background-color: #ffc107; /* Yellow for In Maintenance */
    color: #212529; /* Darker text for better contrast */
}
.p-tag.p-tag-danger {
    background-color: #dc3545; /* Red for Not Working */
    color: #fff;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-flex-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    /* PrimeVue DataTable handles responsiveness well, often with horizontal scroll */
    .p-datatable {
        overflow-x: auto;
    }
    .p-col-12.p-md-4 {
        flex: 0 0 100%; /* Ensure full width on small screens */
        max-width: 100%;
    }
}
</style>