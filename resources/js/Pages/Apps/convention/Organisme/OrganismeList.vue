<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios'; // Import axios for API calls
import OrganismeListItem from './OrganismeListItem.vue';
import OrganismeModal from '../../../../Components/Apps/convention/Organisme/OrganismeModel.vue';

// Mock data for organismes (will be replaced by API fetch)
const organismes = ref([]);
const isModalVisible = ref(false);
const currentOrganisme = ref(null); // Used for editing
const searchQuery = ref('');
const loading = ref(false); // New loading state
const error = ref(null); // New error state

/**
 * Computed property for filtered organismes
 */
const filteredOrganismes = computed(() => {
    let filtered = organismes.value;

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(organisme =>
            organisme.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (organisme.description && organisme.description.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (organisme.wilaya && organisme.wilaya.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (organisme.email && organisme.email.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }
    return filtered;
});

/**
 * Fetches the list of corporate partners from the API.
 */
const getOrganismes = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/organismes');
        // Assuming response.data.data or response.data contains the array of organismes
        organismes.value = response.data.data ;
        console.log('Organismes fetched successfully:', organismes.value); // Replace with toaster.success
    } catch (err) {
        console.error('Error fetching organismes:', err);
        error.value = err.response?.data?.message || 'Failed to load corporate partners. Please try again.';
        console.error(error.value); // Replace with toaster.error
    } finally {
        loading.value = false;
    }
};

// Open modal for adding a new organisme
const addOrganisme = () => {
    currentOrganisme.value = null; // Clear current organisme for new entry
    isModalVisible.value = true;
};

// Open modal for editing an organisme
const editOrganisme = (organisme) => {
    currentOrganisme.value = organisme;
    isModalVisible.value = true;
};

// Handle save (add/update) from modal
const handleOrganismeAdded = (newOrganisme) => {
    organismes.value.unshift(newOrganisme); // Add to the beginning of the list
    isModalVisible.value = false;
};

const handleOrganismeUpdated = (updatedOrganisme) => {
    const index = organismes.value.findIndex(o => o.id === updatedOrganisme.id);
    if (index !== -1) {
        organismes.value[index] = updatedOrganisme; // Replace the old object with the updated one
    }
    isModalVisible.value = false;
};

// Delete organisme
const handleDeleteOrganisme = async (id) => {
    // Replace with useSweetAlert equivalent
    if (window.confirm('Are you sure you want to delete this corporate partner? This action cannot be undone.')) {
        try {
            await axios.delete(`/api/organismes/${id}`);
            organismes.value = organismes.value.filter(o => o.id !== id);
            console.log('Organisme deleted successfully!'); // Replace with toaster.success
        } catch (err) {
            console.error('Error deleting organisme:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete corporate partner.';
            console.error(errorMessage); // Replace with toaster.error
            // Replace with swal.fire('Error!', errorMessage, 'error');
        }
    }
};

// Close modal
const handleCloseModal = () => {
    isModalVisible.value = false;
    currentOrganisme.value = null; // Clear current organisme when modal is closed
};

// Clear search filters
const clearFilters = () => {
    searchQuery.value = '';
};

// Fetch organismes when the component is mounted
onMounted(() => {
    getOrganismes();
});
</script>

<template>
    <div class="corporate-partners-page">
        <div class="content-header">
            <div class="header-flex-container">
                <div class="header-left">
                    <h1 class="page-title">Corporate Partners Management</h1>
                    <p class="page-subtitle">Manage your corporate partners and their details</p>
                </div>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li class="breadcrumb-current">Corporate Partners</li>
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
                                <h2 class="card-title">Corporate Partner List</h2>
                                <span class="partner-count">{{ filteredOrganismes.length }} of {{ organismes.length }} partners</span>
                            </div>
                            <button @click="addOrganisme" class="add-partner-button">
                                <i class="fas fa-plus-circle button-icon"></i>
                                <span>Add New Partner</span>
                            </button>
                        </div>

                        <div class="filters-section">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search partners..."
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
                        <p class="loading-text">Loading corporate partners...</p>
                    </div>

                    <div v-else-if="error" class="error-message" role="alert">
                        <div class="error-content">
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <div>
                                <strong class="error-bold">Error!</strong>
                                <span class="error-text">{{ error }}</span>
                            </div>
                        </div>
                        <button @click="getOrganismes" class="retry-button">
                            <i class="fas fa-redo"></i>
                            Retry
                        </button>
                    </div>

                    <div v-else-if="filteredOrganismes.length > 0" class="table-responsive">
                        <table class="organisme-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">#</th>
                                    <th class="table-header">Logo</th>
                                    <th class="table-header">Name & Email</th>
                                    <th class="table-header">Phone</th>
                                    <th class="table-header">Address</th>
                                    <th class="table-header">Status</th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <OrganismeListItem
                                    v-for="(organisme, index) in filteredOrganismes"
                                    :key="organisme.id"
                                    :organisme="organisme"
                                    :index="index"
                                    @edit="editOrganisme"
                                    @delete="handleDeleteOrganisme"
                                />
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="no-partners">
                        <div class="no-partners-content">
                            <i class="fas fa-building no-partners-icon"></i>
                            <h3 class="no-partners-title">
                                {{ searchQuery ? 'No corporate partners match your filters' : 'No corporate partners found' }}
                            </h3>
                            <p class="no-partners-text">
                                {{ searchQuery ? 'Try adjusting your search' : 'Click "Add New Partner" to get started!' }}
                            </p>
                            <div class="no-partners-actions">
                                <button
                                    v-if="searchQuery"
                                    @click="clearFilters"
                                    class="clear-filters-btn"
                                >
                                    <i class="fas fa-times"></i>
                                    Clear Filters
                                </button>
                                <button @click="addOrganisme" class="add-partner-button">
                                    <i class="fas fa-plus-circle button-icon"></i>
                                    Add New Partner
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Organisme Modal -->
        <OrganismeModal
            :isVisible="isModalVisible"
            :organisme="currentOrganisme"
            @organisme-added="handleOrganismeAdded"
            @organisme-updated="handleOrganismeUpdated"
            @close="handleCloseModal"
        />
    </div>
</template>

<style scoped>
/* Base Page Layout */
.corporate-partners-page {
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

.partner-count {
    font-size: 0.875rem;
    color: #64748b;
    background-color: #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 500;
}

.add-partner-button {
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

.add-partner-button:hover {
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


.organisme-table { /* Renamed from .service-table */
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


/* No Partners */
.no-partners {
    padding: 4rem 2rem;
    text-align: center;
}


.no-partners-content {
    max-width: 400px;
    margin: 0 auto;
}


.no-partners-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}


.no-partners-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}


.no-partners-text {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}


.no-partners-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}


/* Responsive Design */
@media (max-width: 768px) {
    .corporate-partners-page {
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

    /* For table, overflow-x: auto will handle responsiveness */
    .partners-grid { /* This class is now removed from template, but keeping style for reference */
        grid-template-columns: 1fr; /* Stack items on small screens */
    }
}
</style>
