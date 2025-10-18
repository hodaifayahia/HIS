<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';
import { useSweetAlert } from '../../../../Components/useSweetAlert';
import servicesModel from '../../../../Components/Apps/Configuration/Services/servicesModel.vue';
import serviceslistitem from '../../Configuration/Services/serviceslistitem.vue';
import { useRoute } from 'vue-router'; // Correct import

const route = useRoute(); // Initialize useRoute hook

const swal = useSweetAlert();
const toaster = useToastr();

const services = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedService = ref(null);
const searchQuery = ref('');
const statusFilter = ref('all'); // 'all', 'active', 'inactive'

// Correctly get the pavilionId from the route parameters
// Use a computed property if you want it reactive to route changes,
// otherwise, direct assignment in onMounted is fine.
const pavilionId = computed(() => route.params.id);


/**
 * Computed property for filtered services
 */
const filteredServices = computed(() => {
    let filtered = services.value;

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(service =>
            service.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            service.description.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }

    // Filter by status
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(service => {
            if (statusFilter.value === 'active') return service.is_active;
            if (statusFilter.value === 'inactive') return !service.is_active;
            return true;
        });
    }

    return filtered;
});


/**
 * Fetches the list of services from the API.
 */
const getServices = async () => {
    // Correctly log the pavilionId using a template literal or concatenation
    console.log(`Fetching services for pavilion ID: ${pavilionId.value}`);

    // Ensure pavilionId exists before making the API call
    if (!pavilionId.value) {
        console.error('Pavilion ID is not available. Cannot fetch services.');
        error.value = 'Pavilion ID missing. Please navigate from a valid pavilion link.';
        return; // Exit the function if ID is missing
    }

    loading.value = true;
    error.value = null;
    try {
        // Use pavilionId.value to access the reactive value
        const response = await axios.get(`/api/pavilions/${pavilionId.value}/services`);
        services.value = response.data.data || []; // Ensure it defaults to an empty array
        // Log the response data to confirm
        console.log('Services fetched:', services.value);
    } catch (err) {
        console.error('Error fetching services:', err);
        error.value = err.response?.data?.message || 'Failed to load services. Please try again.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};


/**
 * Opens the ServiceModel for adding a new service or editing an existing one.
 */
const openModal = (service = null) => {
    selectedService.value = service ? { ...service } : {
        image_url: '',
        name: '',
        description: '',
        start_time: '', // Corrected from start_date
        end_time: '',   // Corrected from end_date
        agmentation: '', // Typo: Consider renaming to 'augmentation'
        is_active: true
    };
    isModalOpen.value = true;
};


/**
 * Closes the ServiceModel.
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedService.value = null;
    // Re-fetch services after modal closes, in case a service was added/removed/updated
    getServices();
};


/**
 * Handles adding a new service to the local list.
 * Emitted from servicesModel when a new service is successfully added.
 * @param {Object} newService - The newly created service object from the API response.
 */
const handleServiceAdded = (newService) => {
    services.value.unshift(newService); // Add to the beginning of the list
    closeModal(); // This will also trigger getServices()
};

/**
 * Handles updating an existing service in the local list.
 * Emitted from servicesModel when a service is successfully updated.
 * @param {Object} updatedService - The updated service object from the API response.
 */
const handleServiceUpdated = (updatedService) => {
    const index = services.value.findIndex(s => s.id === updatedService.id);
    if (index !== -1) {
        services.value[index] = updatedService; // Replace the old object with the updated one
    }
    closeModal(); // This will also trigger getServices()
};


/**
 * Toggles the active status of a service.
 */
const toggleServiceStatus = async (service) => {
    const action = service.is_active ? 'deactivate' : 'activate';
    const result = await swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Service?`,
        text: `Are you sure you want to ${action} "${service.name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: service.is_active ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${action} it!`,
        cancelButtonText: 'Cancel'
    });


    if (result.isConfirmed) {
        try {
            await axios.patch(`/api/services/${service.id}/toggle-status`);

            service.is_active = !service.is_active; // Update the local state directly
            toaster.success(`Service ${action}d successfully!`);
        } catch (err) {
            console.error(`Error ${action}ing service:`, err);
            const errorMessage = err.response?.data?.message || `Failed to ${action} service.`;
            toaster.error(errorMessage);
        }
    }
};


/**
 * Handles the deletion of a service.
 */
const deleteService = async (id) => {
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
        try {
            await axios.delete(`/api/services/${id}`);
            services.value = services.value.filter(service => service.id !== id);
            toaster.success('Service deleted successfully!');
        } catch (err) {
            console.error('Error deleting service:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete service.';
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
    statusFilter.value = 'all';
};


// Fetch services when the component is mounted
onMounted(() => {
    // getServices() is now called, and it will use the reactive pavilionId.value
    getServices();
});
</script>

<template>
    <div class="service-page">
        <div class="content-header">
            <div class="header-flex-container">
                <div class="header-left">
                    <h1 class="page-title">Services for Pavilion ID: {{ pavilionId }}</h1>
                    <p class="page-subtitle">Manage services associated with this pavilion</p>
                </div>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li><a href="#" class="breadcrumb-link" @click="$router.push({ name: 'infrastructure.structure.pavillons' })">Pavilions</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li class="breadcrumb-current">Pavilion Services</li>
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
                                <h2 class="card-title">Service List</h2>
                                <span class="service-count">{{ filteredServices.length }} of {{ services.length }} services</span>
                            </div>
                            <button @click="openModal()" class="add-service-button">
                                <i class="fas fa-plus-circle button-icon"></i>
                                <span>Add New Service</span>
                            </button>
                        </div>

                        <div class="filters-section">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search services..."
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
                        <p class="loading-text">Loading services...</p>
                    </div>


                    <div v-else-if="error" class="error-message" role="alert">
                        <div class="error-content">
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <div>
                                <strong class="error-bold">Error!</strong>
                                <span class="error-text">{{ error }}</span>
                            </div>
                        </div>
                        <button @click="getServices" class="retry-button">
                            <i class="fas fa-redo"></i>
                            Retry
                        </button>
                    </div>


                    <div v-else-if="filteredServices.length > 0" class="table-responsive">
                        <table class="service-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">#</th>
                                    <th class="table-header">Image</th>
                                    <th class="table-header">Name</th>
                                    <th class="table-header">Start Time</th>
                                    <th class="table-header">End Time</th>
                                    <th class="table-header">Augmentation</th>
                                    <th class="table-header">Status</th>
                                    <th class="table-header">Description</th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <serviceslistitem
                                    v-for="(service, index) in filteredServices"
                                    :key="service.id"
                                    :service="service"
                                    :index="index"
                                    @edit="openModal"
                                    @delete="deleteService"
                                    @toggle-status="toggleServiceStatus"
                                />
                            </tbody>
                        </table>
                    </div>


                    <div v-else class="no-services">
                        <div class="no-services-content">
                            <i class="fas fa-box-open no-services-icon"></i>
                            <h3 class="no-services-title">
                                {{ searchQuery || statusFilter !== 'all' ? 'No services match your filters' : 'No services found' }}
                            </h3>
                            <p class="no-services-text">
                                {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your search or filters' : 'Click "Add New Service" to get started!' }}
                            </p>
                            <div class="no-services-actions">
                                <button
                                    v-if="searchQuery || statusFilter !== 'all'"
                                    @click="clearFilters"
                                    class="clear-filters-btn"
                                >
                                    Clear Filters
                                </button>
                                <button @click="openModal()" class="add-service-button">
                                    <i class="fas fa-plus-circle button-icon"></i>
                                    Add New Service
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <servicesModel
            :show-modal="isModalOpen"
            :service-data="selectedService"
            @close="closeModal"
            @service-updated="handleServiceUpdated"
            @service-added="handleServiceAdded"
        />
    </div>
</template>

<style scoped>
/* (Your existing styles remain here, no changes needed) */
/* Base Page Layout */
.service-page {
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


.service-count {
    font-size: 0.875rem;
    color: #64748b;
    background-color: #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 500;
}


.add-service-button {
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


.add-service-button:hover {
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


.service-table {
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


/* No Services */
.no-services {
    padding: 4rem 2rem;
    text-align: center;
}


.no-services-content {
    max-width: 400px;
    margin: 0 auto;
}


.no-services-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}


.no-services-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}


.no-services-text {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}


.no-services-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}


/* Responsive Design */
@media (max-width: 768px) {
    .service-page {
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