<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import MedicalesListItem from './MedicalesListItem.vue';
import { useIntersectionObserver } from '@vueuse/core';
import MedicalesModel from './MedicalesModel.vue';

import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';

const swal = useSweetAlert();
const toastr = useToastr();
const showModal = ref(false);
const selectedMedication = ref(null);
const isEditing = ref(false);

// State
const medications = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');
const selectedMedications = ref([]);
const viewMode = ref('table');
const sortBy = ref('designation');
const sortOrder = ref('asc');
const currentPage = ref(1);
const perPage = ref(20);
const isLoading = ref(false); // This seems unused, consider removing
const hasMorePages = ref(true);
const loadingMore = ref(false);
const observerTarget = ref(null);
const showOnlyFavorites = ref(false);

// New state to store favorite IDs
const favoriteMedicationIds = ref(new Set());

// Computed
const filteredMedications = computed(() => {
    let filtered = medications.value;

    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(med =>
            med.designation.toLowerCase().includes(query) ||
            med.nom_commercial.toLowerCase().includes(query) ||
            med.type_medicament.toLowerCase().includes(query)
        );
    }

    // Apply favorite filter
    if (showOnlyFavorites.value) {
        filtered = filtered.filter(med => med.is_favorite);
    }

    // Apply sorting
    filtered.sort((a, b) => {
        const aVal = a[sortBy.value] || '';
        const bVal = b[sortBy.value] || '';
        const comparison = aVal.toString().localeCompare(bVal.toString());
        return sortOrder.value === 'asc' ? comparison : -comparison;
    });

    return filtered;
});

const toggleShowOnlyFavorites = async () => {
    //call api for favorite medications if needed
await getFavoriteMedications();
    showOnlyFavorites.value = !showOnlyFavorites.value;
    // When toggling favorites, if the source of truth for "all medications" is paginated
    // and not fully loaded, it's better to refetch from page 1 to ensure correct filtering.
    // Otherwise, `filteredMedications` will just filter the *currently loaded* medications.
    // For this setup, where `getMedications` fetches a page at a time,
    // if `showOnlyFavorites` is toggled, it will filter the *current* `medications.value`.
    // If you need server-side filtering for favorites, you'd call `getMedications(1)` here.
};

const doctors = useAuthStoreDoctor();
const currentDoctorId = ref(null);
const specializationId = ref(null);

onMounted(async () => {
    await doctors.getDoctor();
    currentDoctorId.value = doctors.doctorData.id;
    specializationId.value = doctors.doctorData.specialization_id;

    // Fetch favorite medications first to populate favoriteMedicationIds
    await getFavoriteMedications();
    // Then fetch all medications, which will use favoriteMedicationIds
    await getMedications();
});

const openCreateModal = () => {
    selectedMedication.value = null;
    isEditing.value = false;
    showModal.value = true;

};

const openEditModal = (medication) => {
    selectedMedication.value = { ...medication };
    isEditing.value = true;
    showModal.value = true;
};

const handleSaved = (savedMedication) => {
    if (!savedMedication) {
        console.error('No medication data received');
        return;
    }

    try {
        const index = medications.value.findIndex(m => m.id === savedMedication.id);

        if (index !== -1) {
            // Update existing medication, preserving its favorite status
            medications.value[index] = {
                ...savedMedication,
                is_favorite: favoriteMedicationIds.value.has(savedMedication.id)
            };
        } else {
            // Add new medication. It's not a favorite by default.
            medications.value.unshift({ ...savedMedication, is_favorite: false });
        }

        showModal.value = false;
        toastr.success(`Medication ${isEditing.value ? 'updated' : 'added'} successfully`);

        // Re-apply sorting and filtering just in case
        // No need to call getMedications() unless you want to refresh from server
    } catch (error) {
        console.error('Error updating medications list:', error);
        toastr.error('Failed to update medications list');
    }
};

const hasMedications = computed(() => medications.value.length > 0);
const hasSelection = computed(() => selectedMedications.value.length > 0);

// API
const getMedications = async (page = 1) => {
    try {
        if (page === 1) {
            loading.value = true;
            medications.value = []; // Clear for new search or initial load
        } else {
            loadingMore.value = true;
        }
        error.value = null;

        const response = await axios.get('/api/medications', {
            params: {
                page,
                per_page: perPage.value,
                search: searchQuery.value
            }
        });
        const { data, meta } = response.data;

        // Map fetched medications and set their is_favorite status based on favoriteMedicationIds
        const fetchedAndMarkedMedications = data.map(med => ({
            ...med,
            is_favorite: favoriteMedicationIds.value.has(med.id)
        }));

        if (page === 1) {
            // For the first page, set the medications
            medications.value = fetchedAndMarkedMedications;
            // Also ensure any favorites *not* on the first page of 'all medications' are still shown if favorite filter is active
            // This is crucial if a user is showing only favorites and then switches back to all, or if favorites are outside the first page range.
            // A more robust solution for `showOnlyFavorites` would be to have a separate API call for favorite medications.
        } else {
            // For subsequent pages, append and mark
            const combined = [...medications.value, ...fetchedAndMarkedMedications];
            // Remove duplicates, prioritizing the newly fetched (and correctly marked) ones
            const uniqueCombined = [...new Map(combined.map(item => [item['id'], item])).values()];
            medications.value = uniqueCombined;
        }

        hasMorePages.value = meta.current_page < meta.last_page;
        currentPage.value = meta.current_page;

    } catch (err) {
        console.error('Error loading medications:', err);
        error.value = 'Failed to load medications. Please try again.';
        toastr.error(error.value);
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const getFavoriteMedications = async () => {
    if (!currentDoctorId.value) {
        console.warn('Doctor ID not available for fetching favorite medications.');
        return;
    }
    try {
        const response = await axios.get('/api/favorate', {
            params: {
                doctor_id: currentDoctorId.value
            }
        });
        const favoriteData = response.data.data;

        // Populate the Set with favorite medication IDs
        favoriteMedicationIds.value = new Set(favoriteData.map(favMed => favMed.id));

        // Update the favorite status of already loaded medications
        medications.value.forEach(med => {
            med.is_favorite = favoriteMedicationIds.value.has(med.id);
        });

        // Add any favorite medications that are not yet in the main `medications` list
        // This is important if `getMedications` hasn't loaded them yet.
        favoriteData.forEach(favMed => {
            if (!medications.value.some(m => m.id === favMed.id)) {
                medications.value.unshift({ ...favMed, is_favorite: true });
            }
        });

    } catch (err) {
        console.error('Error fetching favorite medications:', err);
        toastr.error('Failed to load favorite medications.');
    }
};

const deleteMedication = async (id, name) => {
    const result = await swal.fire({
        title: 'Confirm Deletion',
        html: `Are you sure you want to delete <strong>"${name}"</strong>?<br><small class="text-muted">This action cannot be undone.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash"></i> Delete',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        focusCancel: true
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/medications/${id}`);
            // Remove from local list
            medications.value = medications.value.filter(med => med.id !== id);
            // Also remove from favoriteMedicationIds set if it was a favorite
            favoriteMedicationIds.value.delete(id);

            toastr.success(`"${name}" deleted successfully`);

            // Remove from selection if it was selected
            selectedMedications.value = selectedMedications.value.filter(med => med.id !== id);
        } catch (err) {
            toastr.error(err.response?.data?.message || 'Failed to delete medication');
        }
    }
};



const toggleFavorite = async (medication) => {
    try {
        const response = await axios.post('/api/medications/toggle-favorite', {
            medication_id: medication.id,
            doctor_id: currentDoctorId.value
        });

        const { is_favorite, message } = response.data;

        toastr.success(message);

        // Update the medication's favorite status directly in the list
        const index = medications.value.findIndex(med => med.id === medication.id);
        if (index !== -1) {
            medications.value[index].is_favorite = is_favorite;
        }

        // Update the favoriteMedicationIds Set
        if (is_favorite) {
            favoriteMedicationIds.value.add(medication.id);
        } else {
            favoriteMedicationIds.value.delete(medication.id);
        }

    } catch (err) {
        toastr.error(err.response?.data?.message || 'Failed to update favorite status');
    }
};

const toggleMedicationSelection = (medication) => {
    const index = selectedMedications.value.findIndex(med => med.id === medication.id);
    if (index !== -1) {
        selectedMedications.value.splice(index, 1);
        toastr.info(`"${medication.designation}" deselected`);
    } else {
        selectedMedications.value.push(medication);
        toastr.success(`"${medication.designation}" selected`);
    }
};

const selectAll = () => {
    selectedMedications.value = [...filteredMedications.value];
    toastr.success(`Selected all ${filteredMedications.value.length} medications`);
};

const isMedicationSelected = (medicationId) => {
    return selectedMedications.value.some(med => med.id === medicationId);
};

const clearSelection = () => {
    selectedMedications.value = [];
    toastr.info('Selection cleared');
};

const handleSort = (field) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

const getSortIcon = (field) => {
    if (sortBy.value !== field) return 'fas fa-sort text-muted';
    return sortOrder.value === 'asc' ? 'fas fa-sort-up text-primary' : 'fas fa-sort-down text-primary';
};

const { stop: stopObserver } = useIntersectionObserver(
    observerTarget,
    ([{ isIntersecting }]) => {
        if (isIntersecting && hasMorePages.value && !loadingMore.value) {
            loadMore();
        }
    },
    { threshold: 0.5 }
);

const loadMore = async () => {
    if (loadingMore.value || !hasMorePages.value) return;
    await getMedications(currentPage.value + 1);
};

onUnmounted(() => {
    stopObserver();
});

let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        hasMorePages.value = true;
        getMedications(1);
    }, 300);
});
</script>

<template>
    <div class="medication-page">
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                </div>
                <div class="d-flex gap-2 ">
                    <button class="btn btn-outline-primary mr-2" @click="getMedications(1)" :disabled="loading">
                        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                        Refresh
                    </button>
                    <button
                        class="btn mr-2"
                        :class="showOnlyFavorites ? 'btn-primary' : 'btn-outline-primary'"
                        @click="toggleShowOnlyFavorites"
                    >
                        <i class="fas fa-star me-1"></i>
                        {{ showOnlyFavorites ? 'Show All' : 'Show Favorites' }}
                    </button>
                    <button @click="openCreateModal" class="btn btn-primary mr-2">
                        <i class="fas fa-plus me-1"></i>
                        Add Medication
                    </button>
                </div>
            </div>
        </div>
        <div class="controls-section mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input v-model="searchQuery" type="text" class="form-control search-input"
                                    placeholder="Search by name, commercial name, or type..." />
                                <button v-if="searchQuery" class="btn btn-link search-clear" @click="searchQuery = ''">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary" @click="selectAll"
                                    :disabled="!hasMedications">
                                    <i class="fas fa-check-double me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-outline-secondary" @click="clearSelection"
                                    :disabled="!hasSelection">
                                    <i class="fas fa-times-circle me-1"></i>Clear Selection
                                </button>
                                <button type="button" class="btn btn-outline-danger" @click="bulkDelete"
                                    :disabled="!hasSelection">
                                    <i class="fas fa-trash me-1"></i>Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="hasSelection" class="selection-banner mb-3">
            <div class="alert alert-info d-flex align-items-center justify-content-between mb-0">
                <div>
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>{{ selectedMedications.length }}</strong> medication(s) selected
                </div>
                <button class="btn btn-sm btn-info" @click="clearSelection">Clear Selection</button>
            </div>
        </div>

        <div class="main-content">
            <div v-if="loading" class="loading-container">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5>Loading medications...</h5>
                        <p class="text-muted mb-0">Please wait while we fetch your data</p>
                    </div>
                </div>
            </div>

            <div v-else-if="error" class="error-container">
                <div class="card border-danger">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                        <h5 class="text-danger">Error Loading Medications</h5>
                        <p class="text-muted">{{ error }}</p>
                        <button class="btn btn-outline-danger" @click="getMedications(1)">
                            <i class="fas fa-retry me-1"></i>Try Again
                        </button>
                    </div>
                </div>
            </div>

            <div v-else-if="!hasMedications" class="empty-container">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="empty-illustration mb-4">
                            <i class="fas fa-pills fa-4x text-muted opacity-50"></i>
                        </div>
                        <h4 class="mb-3">No Medications Found</h4>
                        <p class="text-muted mb-4">Get started by adding your first medication to the system.</p>
                        <button @click="openCreateModal" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Add First Medication
                        </button>
                    </div>
                </div>
            </div>

            <div v-else-if="filteredMedications.length === 0 && searchQuery.trim()" class="no-results-container">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No Results Found</h5>
                        <p class="text-muted mb-3">
                            No medications match "<strong>{{ searchQuery }}</strong>"
                        </p>
                        <button class="btn btn-outline-primary" @click="searchQuery = ''">
                            <i class="fas fa-times me-1"></i>Clear Search
                        </button>
                    </div>
                </div>
            </div>

            <div v-else-if="viewMode === 'table'" class="table-container">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-header">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th class="sortable" @click="handleSort('designation')">
                                        <div class="d-flex align-items-center justify-content-between">
                                            Designation
                                            <i :class="getSortIcon('designation')"></i>
                                        </div>
                                    </th>
                                    <th class="sortable" @click="handleSort('nom_commercial')">
                                        <div class="d-flex align-items-center justify-content-between">
                                            Commercial Name
                                            <i :class="getSortIcon('nom_commercial')"></i>
                                        </div>
                                    </th>
                                    <th class="sortable" @click="handleSort('type_medicament')">
                                        <div class="d-flex align-items-center justify-content-between">
                                            Type
                                            <i :class="getSortIcon('type_medicament')"></i>
                                        </div>
                                    </th>
                                    <th>Form</th>
                                    <th>Box Size</th>
                                    <th>PCH Code</th>
                                    <th class="text-center" style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <MedicalesListItem v-for="(medication, index) in filteredMedications" :key="medication.id"
                                    :medication="medication" :index="index"
                                    :is-selected="isMedicationSelected(medication.id)"
                                    :is-favorite="medication.is_favorite"
                                    @toggle-favorite="toggleFavorite" @select-medication="toggleMedicationSelection"
                                    @edit="openEditModal" @delete="deleteMedication" class="enhanced-row" />
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <div ref="observerTarget" class="load-more-trigger" v-show="hasMorePages">
                <div v-if="loadingMore" class="loading-indicator">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Loading more medications...
                </div>
            </div>
        </div>

        <div v-if="!loading && hasMedications" class="results-summary mt-4">
            <div class="card border-0 bg-light">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Showing {{ filteredMedications.length }} of {{ medications.length }} medications
                            <span v-if="searchQuery"> · Filtered by "{{ searchQuery }}"</span>
                        </small>
                        <small class="text-muted">
                            {{ selectedMedications.length }} selected
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <MedicalesModel v-model="showModal" :doctorId="currentDoctorId" :medication="selectedMedication" :is-edit="isEditing" @saved="handleSaved" />
</template>

<style scoped>
.medication-page {
    padding: 1.5rem;
    background-color: #f8f9fa;
    min-height: 100vh;
}

.load-more-trigger {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
    font-size: 0.875rem;
}

.loading-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Header */
.page-header {
    background: white;
    padding: 2rem;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.page-title {
    color: #2c3e50;
    font-weight: 600;
}

/* Search Container */
.search-container {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 3;
}

.search-input {
    padding-left: 2.5rem;
    padding-right: 2.5rem;
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.search-clear {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 3;
    padding: 0.25rem;
    color: #6c757d;
}

/* Selection Banner */
.selection-banner .alert {
    border-radius: 0.5rem;
    border: none;
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
}

/* Table Enhancements */
.table-container .card {
    border-radius: 0.75rem;
    overflow: hidden;
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid #dee2e6;
}

.table-header th {
    font-weight: 600;
    color: #495057;
    padding: 1rem 0.75rem;
    border: none;
}

.sortable {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
}

.sortable:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* New: Styling for favorited rows */
.table tbody tr.is-favorite {
    background-color: #fff3cd; /* Light yellow background */
    border-left: 5px solid #ffc107; /* Orange left border */
}

/* New: Styling for selected rows */
.table tbody tr.is-selected {
    background-color: #e2f3ff; /* Light blue background for selected */
    border-left: 5px solid #0d6efd; /* Blue left border */
}

/* If a row is both favorite and selected, selected style should take precedence or be combined */
.table tbody tr.is-favorite.is-selected {
    background-color: #d0e7ff; /* A blend or a distinct color for both */
    border-left: 5px solid #0a58ca; /* Darker blue */
}


/* Grid View */
.grid-container {
    margin-top: 1rem;
}

.medication-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.medication-card:hover {
    transform: translateY(-5px);
}

.medication-card.selected .card {
    border: 2px solid #198754;
    box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.15);
}

.medication-card .card {
    transition: all 0.3s ease;
    border-radius: 0.75rem;
}

.medication-card:hover .card {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.selection-indicator {
    font-size: 1.25rem;
}

.medication-details .detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Loading State */
.loading-container .card {
    border-radius: 0.75rem;
}

/* Empty State */
.empty-container .card {
    border-radius: 0.75rem;
}

.empty-illustration {
    opacity: 0.6;
}

/* Results Summary */
.results-summary .card {
    border-radius: 0.5rem;
}

/* Button Enhancements */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn {
    border-radius: 0.5rem;
}

.btn-group .btn:not(:first-child) {
    margin-left: 0.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .medication-page {
        padding: 1rem;
    }

    .page-header {
        padding: 1.5rem;
    }

    .controls-section .row {
        row-gap: 1rem;
    }

    .controls-section .col-md-6:last-child {
        text-align: center;
    }
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.main-content>* {
    animation: fadeIn 0.5s ease-out;
}

/* Custom Scrollbar */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f3f4;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #dadce0;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #bdc1c6;
}
</style>