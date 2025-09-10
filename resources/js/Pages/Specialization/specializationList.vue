<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import specializationModel from "../../Components/specializationModel.vue";
import { useSweetAlert } from '../../Components/useSweetAlert';

const swal = useSweetAlert();
const toaster = useToastr();

const specializations = ref([])
const loading = ref(false)
const error = ref(null)
const isModalOpen = ref(false)
const selectedSpecialization = ref([])
const searchQuery = ref('')
const statusFilter = ref('all') // 'all', 'active', 'inactive'

/**
 * Computed property for filtered specializations
 */
const filteredSpecializations = computed(() => {
    let filtered = specializations.value;

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(specialization =>
            specialization.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (specialization.description && specialization.description.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (specialization.service_name && specialization.service_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }

    // Filter by status
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(specialization => {
            if (statusFilter.value === 'active') return specialization.is_active;
            if (statusFilter.value === 'inactive') return !specialization.is_active;
            return true;
        });
    }

    return filtered;
});

/**
 * Fetches the list of specializations from the API
 */
const getSpecializations = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/specializations', {
            params: {
                all: true,
            }
        });
        specializations.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error fetching specializations:', err);
        error.value = err.response?.data?.message || 'Failed to load specializations. Please try again.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};

/**
 * Opens the modal for adding/editing specialization
 */
const openModal = (specialization = null) => {
    selectedSpecialization.value = specialization ? { ...specialization } : {
        name: '',
        description: '',
        service_id: '',
        photo_url: null, // Ensure new specialization starts without a photo_url
        is_active: true
    };
    isModalOpen.value = true;
};

/**
 * Closes the modal
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedSpecialization.value = [];
};

/**
 * Handles a newly added specialization.
 * Adds the new specialization object to the local array.
 */
const handleSpecializationAdded = (newSpecialization) => {
    specializations.value.unshift(newSpecialization); // Add to the beginning for immediate visibility
    closeModal();
};

/**
 * Handles an updated specialization.
 * Finds and replaces the updated specialization in the local array.
 */
const handleSpecializationUpdated = (updatedSpecialization) => {
    const index = specializations.value.findIndex(s => s.id === updatedSpecialization.id);
    if (index !== -1) {
        specializations.value[index] = updatedSpecialization; // Replace the old object with the updated one
    }
    closeModal();
};

/**
 * Toggles the active status of a specialization
 */
const toggleSpecializationStatus = async (specialization) => {
    const action = specialization.is_active ? 'deactivate' : 'activate';
    const result = await swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Specialization?`,
        text: `Are you sure you want to ${action} "${specialization.name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: specialization.is_active ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${action} it!`,
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            await axios.patch(`/api/specializations/${specialization.id}/toggle-status`);
            // Update the local service object's status immediately
            specialization.is_active = !specialization.is_active;
            toaster.success(`Specialization ${action}d successfully!`);
        } catch (err) {
            console.error(`Error ${action}ing specialization:`, err);
            const errorMessage = err.response?.data?.message || `Failed to ${action} specialization.`;
            toaster.error(errorMessage);
        }
    }
};

/**
 * Deletes a specialization
 */
const deleteSpecialization = async (id) => {``
    try {
        const result = await swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            await axios.delete(`/api/specializations/${id}`);
            // Remove the specialization from the local array
            specializations.value = specializations.value.filter(s => s.id !== id);
            toaster.success('Specialization deleted successfully');
            swal.fire('Deleted!', 'Specialization has been deleted.', 'success');
        }
    } catch (error) {
        console.error('Error deleting specialization:', error);
        const errorMessage = error.response?.data?.message || 'Failed to delete specialization.';
        toaster.error(errorMessage);
        swal.fire('Error!', errorMessage, 'error');
    }
};

/**
 * Clears all filters
 */
const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
};

onMounted(() => {
    getSpecializations();
});
</script>

<template>
    <div class="specialization-page">
        <div class="content-header">
            <div class="header-flex-container">
                <div class="header-left">
                    <h1 class="page-title">Specializations Management</h1>
                    <p class="page-subtitle">Manage medical specializations and their status</p>
                </div>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li class="breadcrumb-current">Specializations</li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <div class="header-content">
                            <div class="title-section">
                                <h2 class="card-title">Specialization List</h2>
                                <span class="specialization-count">{{ filteredSpecializations.length }} of {{ specializations.length }} specializations</span>
                            </div>
                            <button @click="openModal()" class="add-specialization-button">
                                <i class="fas fa-plus-circle button-icon"></i>
                                <span>Add Specialization</span>
                            </button>
                        </div>

                        <div class="filters-section">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search specializations..."
                                        class="search-input"
                                    >
                                </div>
                            </div>

                            <div class="filter-container">
                                <select v-model="statusFilter" class="status-filter">
                                    <option value="all">All Status</option>
                                    <option value="active">Active Only</option>
                                    <option value="inactive">Inactive Only</option>
                                </select>

                                <button
                                    @click="clearFilters"
                                    class="clear-filters-btn"
                                    v-if="searchQuery || statusFilter !== 'all'"
                                >
                                    <i class="fas fa-times"></i>
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="loading" class="loading-state">
                        <div class="spinner" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="loading-text">Loading specializations...</p>
                    </div>

                    <div v-else-if="error" class="error-message" role="alert">
                        <div class="error-content">
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <div>
                                <strong class="error-bold">Error!</strong>
                                <span class="error-text">{{ error }}</span>
                            </div>
                        </div>
                        <button @click="getSpecializations" class="retry-button">
                            <i class="fas fa-redo"></i>
                            Retry
                        </button>
                    </div>

                    <div v-else-if="filteredSpecializations.length > 0" class="table-responsive">
                        <table class="specialization-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">#</th>
                                    <th class="table-header">Photo</th>
                                    <th class="table-header">Specialization</th>
                                    <th class="table-header">Service</th>
                                    <th class="table-header">Status</th>
                                    <th class="table-header">Description</th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <tr v-for="(specialization, index) in filteredSpecializations" :key="specialization.id" class="table-row">
                                    <td class="table-cell">{{ index + 1 }}</td>
                                    <td class="table-cell">
                                        <div class="photo-container">
                                            <img
                                                v-if="specialization.photo_url"
                                                :src="specialization.photo_url"
                                                :alt="`Photo for ${specialization.name}`"
                                                class="specialization-photo"
                                            />
                                            <div v-else class="no-photo">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="specialization-name">{{ specialization.name }}</div>
                                    </td>
                                    <td class="table-cell">
                                        <span class="service-badge">{{ specialization.service_name || 'N/A' }}</span>
                                    </td>
                                    <td class="table-cell">
                                        <button
                                            @click="toggleSpecializationStatus(specialization)"
                                            :class="['status-badge', specialization.is_active ? 'status-active' : 'status-inactive']"
                                        >
                                            <i :class="['fas', specialization.is_active ? 'fa-check-circle' : 'fa-times-circle']"></i>
                                            {{ specialization.is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="table-cell">
                                        <div class="description-text">{{ specialization.description || 'No description' }}</div>
                                    </td>
                                    <td class="table-cell actions-cell">
                                        <div class="actions-container">
                                            <button
                                                @click="openModal(specialization)"
                                                class="action-button edit-button"
                                                title="Edit specialization"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                @click="deleteSpecialization(specialization.id)"
                                                class="action-button delete-button"
                                                title="Delete specialization"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="no-specializations">
                        <div class="no-specializations-content">
                            <i class="fas fa-stethoscope no-specializations-icon"></i>
                            <h3 class="no-specializations-title">
                                {{ searchQuery || statusFilter !== 'all' ? 'No specializations match your filters' : 'No specializations found' }}
                            </h3>
                            <p class="no-specializations-text">
                                {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your search or filters' : 'Click "Add Specialization" to get started!' }}
                            </p>
                            <div class="no-specializations-actions">
                                <button
                                    v-if="searchQuery || statusFilter !== 'all'"
                                    @click="clearFilters"
                                    class="clear-filters-btn"
                                >
                                    Clear Filters
                                </button>
                                <button @click="openModal()" class="add-specialization-button">
                                    <i class="fas fa-plus-circle button-icon"></i>
                                    Add Specialization
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <specializationModel
            :show-modal="isModalOpen"
            :spec-data="selectedSpecialization"
            @close="closeModal"
            @specialization-added="handleSpecializationAdded"
            @specialization-updated="handleSpecializationUpdated"
        />
    </div>
</template>


<style scoped>
/* Base Page Layout */
.specialization-page {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
}

/* Content Header */
.content-header {
    margin-bottom: 2rem;
}

.header-flex-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.header-left {
    flex: 1;
}

.page-title {
    font-size: 2.25rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: #64748b;
    font-size: 1rem;
    margin: 0;
}

.breadcrumbs {
    font-size: 0.875rem;
}

.breadcrumb-list {
    display: flex;
    align-items: center;
    list-style: none;
    padding: 0.75rem 1rem;
    margin: 0;
    background-color: #ffffff;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.breadcrumb-list li {
    margin-right: 0.5rem;
}

.breadcrumb-list li:last-child {
    margin-right: 0;
}

.breadcrumb-link {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb-link:hover {
    color: #1d4ed8;
}

.breadcrumb-current {
    color: #64748b;
    font-weight: 500;
}

.breadcrumb-separator {
    font-size: 0.75rem;
    margin: 0 0.5rem;
    color: #cbd5e1;
}

/* Main Content */
.container {
    max-width: 90rem;
    margin: 0 auto;
}

.card {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.card-header {
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.title-section {
    display: flex;
    align-items: baseline;
    gap: 1rem;
}

.card-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.specialization-count {
    font-size: 0.875rem;
    color: #64748b;
    background-color: #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 500;
}

.add-specialization-button {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    font-weight: 600;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
}

.add-specialization-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
}

.button-icon {
    margin-right: 0.5rem;
}

/* Filters Section */
.filters-section {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-container {
    flex: 1;
    min-width: 300px;
}

.search-input-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.875rem;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
    background-color: #ffffff;
}

.search-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.filter-container {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.status-filter {
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    background-color: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
}

.status-filter:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.clear-filters-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.clear-filters-btn:hover {
    background-color: #e2e8f0;
    color: #475569;
}

/* Loading State */
.loading-state {
    text-align: center;
    padding: 4rem 2rem;
}

.spinner {
    display: inline-block;
    width: 3rem;
    height: 3rem;
    border: 4px solid #e5e7eb;
    border-top-color: #10b981;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: #64748b;
    margin-top: 1rem;
    font-size: 1rem;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Error Message */
.error-message {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fca5a5;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.error-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.error-icon {
    color: #dc2626;
    font-size: 1.25rem;
}

.error-bold {
    font-weight: 700;
    color: #dc2626;
}

.error-text {
    color: #b91c1c;
    margin-left: 0.5rem;
}

.retry-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #dc2626;
    color: #ffffff;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background-color 0.2s;
}

.retry-button:hover {
    background-color: #b91c1c;
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
    margin: 0;
}

.specialization-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.table-header-row {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-bottom: 2px solid #cbd5e1;
}

.table-header {
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.actions-header {
    text-align: center;
}

.table-body {
    color: #4b5563;
}

.table-row {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s;
}

.table-row:hover {
    background-color: #f8fafc;
}

.table-cell {
    padding: 1rem 1.5rem;
    vertical-align: middle;
}

/* Photo Container */
.photo-container {
    display: flex;
    align-items: center;
    justify-content: center;
}

.specialization-photo {
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    object-fit: cover;
    border: 2px solid #e5e7eb;
}

.no-photo {
    width: 50px;
    height: 50px;
    background-color: #f1f5f9;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

/* Specialization Name */
.specialization-name {
    font-weight: 600;
    color: #1e293b;
}

/* Service Badge */
.service-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background-color: #dbeafe;
    color: #1e40af;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.status-active {
    background-color: #dcfce7;
    color: #166534;
}

.status-active:hover {
    background-color: #bbf7d0;
}

.status-inactive {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-inactive:hover {
    background-color: #fecaca;
}

/* Description */
.description-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #64748b;
}

/* Actions */
.actions-cell {
    text-align: center;
}

.actions-container {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.edit-button {
    background-color: #dbeafe;
    color: #1e40af;
}

.edit-button:hover {
    background-color: #bfdbfe;
    transform: translateY(-1px);
}

.delete-button {
    background-color: #fee2e2;
    color: #dc2626;
}

.delete-button:hover {
    background-color: #fecaca;
    transform: translateY(-1px);
}

/* No Specializations */
.no-specializations {
    padding: 4rem 2rem;
    text-align: center;
}

.no-specializations-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-specializations-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.no-specializations-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.no-specializations-text {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.no-specializations-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive Design */
@media (max-width: 768px) {
    .specialization-page {
        padding: 1rem;
    }

    .header-flex-container {
        flex-direction: column;
        gap: 1rem;
    }

    .header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .filters-section {
        flex-direction: column;
        gap: 1rem;
    }

    .search-container {
        min-width: auto;
    }

    .filter-container {
        justify-content: center;
    }

    .page-title {
        font-size: 1.75rem;
    }

    .card-header {
        padding: 1.5rem;
    }

    .table-header,
    .table-cell {
        padding: 0.75rem;
    }

    .actions-container {
        flex-direction: column;
        gap: 0.25rem;
    }

    .description-text {
        max-width: 150px;
    }
}
</style>