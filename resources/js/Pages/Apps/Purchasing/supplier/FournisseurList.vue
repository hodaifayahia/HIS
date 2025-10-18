<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import FournisseurCreate from './FournisseurCreate.vue';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

// Reactive data
const fournisseurs = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const statusFilter = ref('all'); // 'all', 'active', 'inactive'
const showCreateModal = ref(false);
const editingFournisseur = ref(null);

// Computed properties
const filteredFournisseurs = computed(() => {
    let filtered = fournisseurs.value;

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(fournisseur =>
            fournisseur.company_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            fournisseur.contact_person?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            fournisseur.email?.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }

    // Filter by status
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(fournisseur => {
            if (statusFilter.value === 'active') return fournisseur.is_active;
            if (statusFilter.value === 'inactive') return !fournisseur.is_active;
            return true;
        });
    }

    return filtered;
});

// Methods
const fetchFournisseurs = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/fournisseurs');
        fournisseurs.value = response.data.data;
    } catch (error) {
        console.error('Error fetching fournisseurs:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load suppliers',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const viewFournisseur = (fournisseur) => {
    router.push(`/purchasing/fournisseurs/${fournisseur.id}`);
};

const goToCreate = () => {
    editingFournisseur.value = null;
    showCreateModal.value = true;
};

const onFournisseurCreated = () => {
    showCreateModal.value = false;
    fetchFournisseurs();
};

const onModalClose = () => {
    showCreateModal.value = false;
    editingFournisseur.value = null;
};

const editFournisseur = (fournisseur) => {
    router.push(`/purchasing/fournisseurs/${fournisseur.id}/edit`);
};

const deleteFournisseur = (fournisseur) => {
    router.push(`/purchasing/fournisseurs/${fournisseur.id}/delete`);
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
};

const toggleStatus = async (fournisseur) => {
    const action = fournisseur.is_active ? 'deactivate' : 'activate';
    const result = await confirm.require({
        message: `Are you sure you want to ${action} "${fournisseur.company_name}"?`,
        header: `${action.charAt(0).toUpperCase() + action.slice(1)} Supplier`,
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'tw-p-3 tw-text-gray-600',
        acceptClass: 'tw-p-3 tw-bg-red-600 tw-text-white',
        accept: async () => {
            try {
                await axios.patch(`/api/fournisseurs/${fournisseur.id}`, {
                    ...fournisseur,
                    is_active: !fournisseur.is_active
                });
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: `Supplier ${action}d successfully`,
                    life: 3000
                });
                await fetchFournisseurs();
            } catch (error) {
                console.error('Error updating fournisseur status:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to update supplier status',
                    life: 3000
                });
            }
        }
    });
};

// Lifecycle
onMounted(() => {
    fetchFournisseurs();
});
</script>

<template>
    <div class="service-page">
        <div class="content-header">
            <div class="header-flex-container">
                <div class="header-left">
                    <h1 class="page-title">Suppliers</h1>
                    <p class="page-subtitle">Manage your supplier database</p>
                </div>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li class="breadcrumb-current">Suppliers</li>
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
                                <h2 class="card-title">Supplier List</h2>
                                <span class="service-count">{{ filteredFournisseurs.length }} of {{ fournisseurs.length }} suppliers</span>
                            </div>
                            <button @click="goToCreate()" class="add-service-button">
                                <i class="fas fa-plus-circle button-icon"></i>
                                <span>Add New Supplier</span>
                            </button>
                        </div>

                        <div class="filters-section">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by company name, contact, or email..."
                                        class="search-input"
                                    />
                                </div>
                            </div>

                            <div class="filter-container">
                                <select v-model="statusFilter" class="status-filter">
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>

                                <button @click="clearFilters" class="clear-filters-btn">
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
                        <p class="loading-text">Loading suppliers...</p>
                    </div>

                    <div v-else-if="filteredFournisseurs.length > 0" class="table-responsive">
                        <table class="service-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">Company Name</th>
                                    <th class="table-header">Contact Person</th>
                                    <th class="table-header">Email</th>
                                    <th class="table-header">Phone</th>
                                    <th class="table-header">Status</th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <tr v-for="fournisseur in filteredFournisseurs" :key="fournisseur.id" class="table-row">
                                    <td class="table-cell tw-font-medium">{{ fournisseur.company_name }}</td>
                                    <td class="table-cell">{{ fournisseur.contact_person || '-' }}</td>
                                    <td class="table-cell">{{ fournisseur.email || '-' }}</td>
                                    <td class="table-cell">{{ fournisseur.phone || '-' }}</td>
                                    <td class="table-cell">
                                        <span :class="fournisseur.is_active ? 'status-active' : 'status-inactive'">
                                            {{ fournisseur.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <div class="tw-flex tw-justify-center tw-gap-1">
                                            <Button
                                                icon="pi pi-eye"
                                                class="tw-p-2 tw-text-blue-600 hover:tw-bg-blue-50"
                                                v-tooltip.top="'View Details'"
                                                @click="viewFournisseur(fournisseur)"
                                            />
                                            <Button
                                                icon="pi pi-pencil"
                                                class="tw-p-2 tw-text-orange-600 hover:tw-bg-orange-50"
                                                v-tooltip.top="'Edit Supplier'"
                                                @click="editFournisseur(fournisseur)"
                                            />
                                            <Button
                                                :icon="fournisseur.is_active ? 'pi pi-ban' : 'pi pi-check'"
                                                :class="fournisseur.is_active ? 'tw-p-2 tw-text-gray-600 hover:tw-bg-gray-50' : 'tw-p-2 tw-text-green-600 hover:tw-bg-green-50'"
                                                v-tooltip="fournisseur.is_active ? 'Deactivate' : 'Activate'"
                                                @click="toggleStatus(fournisseur)"
                                            />
                                            <Button
                                                icon="pi pi-trash"
                                                class="tw-p-2 tw-text-red-600 hover:tw-bg-red-50"
                                                v-tooltip.top="'Delete Supplier'"
                                                @click="deleteFournisseur(fournisseur)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="no-services">
                        <div class="no-services-content">
                            <i class="fas fa-box-open no-services-icon"></i>
                            <h3 class="no-services-title">No suppliers found</h3>
                            <p class="no-services-text">
                                {{ searchQuery || statusFilter !== 'all' ? 'No suppliers match your current filters.' : 'Get started by adding your first supplier.' }}
                            </p>
                            <div class="no-services-actions">
                                <button @click="goToCreate()" class="add-service-button">
                                    <i class="fas fa-plus-circle button-icon"></i>
                                    <span>Add New Supplier</span>
                                </button>
                                <button v-if="searchQuery || statusFilter !== 'all'" @click="clearFilters" class="clear-filters-btn">
                                    <i class="fas fa-times"></i>
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Dialog -->
        <ConfirmDialog />

        <!-- Create Fournisseur Modal -->
        <FournisseurCreate
            :showModal="showCreateModal"
            :fournisseurData="editingFournisseur"
            @fournisseur-saved="onFournisseurCreated"
            @close="onModalClose"
        />
    </div>
</template>

<style scoped>
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

.table-row {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s;
}

.table-row:hover {
    background-color: #f9fafb;
}

.table-cell {
    padding: 1rem 1.5rem;
    vertical-align: middle;
}

.status-active {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    background-color: #dcfce7;
    color: #166534;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-inactive {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    background-color: #fef2f2;
    color: #991b1b;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
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

:deep(.p-button) {
    border: none;
    background: transparent;
}

:deep(.p-button:hover) {
    background: rgba(0, 0, 0, 0.04);
}
</style>