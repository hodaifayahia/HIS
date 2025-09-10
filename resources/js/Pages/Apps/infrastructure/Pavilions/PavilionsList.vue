<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster'; // Adjust path
import { useSweetAlert } from '../../../../Components/useSweetAlert'; // Adjust path
import PavilionModel from '../../../../Components/Apps/infrastructure/Pavilion/PavilionModel.vue'; // New path for PavilionModel
import PavilionsListItem from '../Pavilions/PavilionsListItem.vue'; // Same folder for list item

const swal = useSweetAlert();
const toaster = useToastr();

const pavilions = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedPavilion = ref(null);
const searchQuery = ref('');

/**
 * Computed property for filtered pavilions
 */
const filteredPavilions = computed(() => {
    let filtered = pavilions.value;
    
    // Filter by search query (name or description)
    if (searchQuery.value) {
        filtered = filtered.filter(pavilion => 
            pavilion.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (pavilion.description && pavilion.description.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }
    
    return filtered;
});

/**
 * Fetches the list of pavilions from the API.
 */
const getPavilions = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pavilions');
        pavilions.value = response.data.data || response.data; // Adjust based on API response structure
    } catch (err) {
        console.error('Error fetching pavilions:', err);
        error.value = err.response?.data?.message || 'Failed to load pavilions. Please try again.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};

/**
 * Opens the PavilionModel for adding a new pavilion or editing an existing one.
 */
const openModal = (pavilion = null) => {
    selectedPavilion.value = pavilion ? { ...pavilion } : {
        name: '',
        description: '',
        // display_color: '#000000', // Initialize if you added this field
    };
    isModalOpen.value = true;
};

/**
 * Closes the PavilionModel.
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedPavilion.value = null;
};

/**
 * Refreshes the pavilion list after an action.
 */
const refreshPavilions = () => {
    getPavilions();
    closeModal();
};

/**
 * Handles the deletion of a pavilion.
 */
const deletePavilion = async (id) => {
    const result = await swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! All associated rooms and beds will also be affected.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/pavilions/${id}`);
            toaster.success('Pavilion deleted successfully!');
            await getPavilions(); // Refresh list after deletion
            swal.fire('Deleted!', 'Your pavilion has been deleted.', 'success');
        } catch (err) {
            console.error('Error deleting pavilion:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete pavilion. It might have associated rooms.';
            toaster.error(errorMessage);
            swal.fire('Error!', errorMessage, 'error');
        }
    }
};

/**
 * Clears all filters
 */
const clearFilters = () => {
    searchQuery.value = '';
};

// Fetch pavilions when the component is mounted
onMounted(() => {
    getPavilions();
});
</script>

<template>
    <div class="pavilion-page">
        <div class="content-header">
            <div class="header-flex-container">
                <div class="header-left">
                    <h1 class="page-title">Pavilions Management</h1>
                    <p class="page-subtitle">Manage the main wings and sections of your clinic</p>
                </div>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li class="breadcrumb-current">Pavilions</li>
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
                                <h2 class="card-title">Pavilion List</h2>
                                <span class="pavilion-count">{{ filteredPavilions.length }} of {{ pavilions.length }} pavilions</span>
                            </div>
                            <button @click="openModal()" class="add-pavilion-button">
                                <i class="fas fa-plus-circle button-icon"></i>
                                <span>Add New Pavilion</span>
                            </button>
                        </div>
                        
                        <div class="filters-section">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input 
                                        v-model="searchQuery"
                                        type="text" 
                                        placeholder="Search pavilions..." 
                                        class="search-input"
                                    >
                                </div>
                            </div>
                            
                            <div class="filter-container">
                                <button 
                                    @click="clearFilters" 
                                    class="clear-filters-btn"
                                    v-if="searchQuery"
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
                        <p class="loading-text">Loading pavilions...</p>
                    </div>

                    <div v-else-if="error" class="error-message" role="alert">
                        <div class="error-content">
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <div>
                                <strong class="error-bold">Error!</strong>
                                <span class="error-text">{{ error }}</span>
                            </div>
                        </div>
                        <button @click="getPavilions" class="retry-button">
                            <i class="fas fa-redo"></i>
                            Retry
                        </button>
                    </div>

                    <div v-else-if="filteredPavilions.length > 0" class="table-responsive">
                        <table class="pavilion-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">#</th>
                                    <th class="table-header">Name</th>
                                    <th class="table-header">Description</th>
                                    <th class="table-header">Created At</th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <PavilionsListItem
                                    v-for="(pavilion, index) in filteredPavilions"
                                    :key="pavilion.id"
                                    :pavilion="pavilion"
                                    :index="index"
                                    @edit="openModal"
                                    @delete="deletePavilion"
                                />
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="no-pavilions">
                        <div class="no-pavilions-content">
                            <i class="fas fa-building no-pavilions-icon"></i>
                            <h3 class="no-pavilions-title">
                                {{ searchQuery ? 'No pavilions match your filters' : 'No pavilions found' }}
                            </h3>
                            <p class="no-pavilions-text">
                                {{ searchQuery ? 'Try adjusting your search' : 'Click "Add New Pavilion" to get started!' }}
                            </p>
                            <div class="no-pavilions-actions">
                                <button 
                                    v-if="searchQuery" 
                                    @click="clearFilters" 
                                    class="clear-filters-btn"
                                >
                                    Clear Filters
                                </button>
                                <button @click="openModal()" class="add-pavilion-button">
                                    <i class="fas fa-plus-circle button-icon"></i>
                                    Add New Pavilion
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <PavilionModel
            :show-modal="isModalOpen"
            :pavilion-data="selectedPavilion"
            @close="closeModal"
            @pavilion-updated="refreshPavilions"
            @pavilion-added="refreshPavilions"
        />
    </div>
</template>

<style scoped>
/*
    All styles from your original Services component are copied here.
    I've only changed class names from "service-" to "pavilion-" where appropriate
    to avoid conflicts and make it specific to Pavilions.
    
    Make sure these styles are included in your build process, e.g., via
    a global CSS file or by importing this component's styles.
*/

/* Base Page Layout */
.pavilion-page {
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

.pavilion-count { /* Changed from service-count */
    font-size: 0.875rem;
    color: #64748b;
    background-color: #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 500;
}

.add-pavilion-button { /* Changed from add-service-button */
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #ffffff;
    font-weight: 600;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
}

.add-pavilion-button:hover { /* Changed from add-service-button */
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
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
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-container {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Removed status-filter as pavilions don't have is_active */
/*
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
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
*/

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
    border-top-color: #3b82f6;
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

.pavilion-table { /* Changed from service-table */
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

/* No Pavilions */ /* Changed from no-services */
.no-pavilions {
    padding: 4rem 2rem;
    text-align: center;
}

.no-pavilions-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-pavilions-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.no-pavilions-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.no-pavilions-text {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.no-pavilions-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive Design */
@media (max-width: 768px) {
    .pavilion-page {
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
}
</style>