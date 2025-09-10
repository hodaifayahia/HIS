<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster'; // Adjust path if necessary
import { useSweetAlert } from '../../../../Components/useSweetAlert'; // Adjust path if necessary
import ModalityModel from '../../../../Components/Apps/Configuration/modalities/ModalityTypeModel.vue';
import ModalityListItem from './ModalityTypeListItem.vue';

const swal = useSweetAlert();
const toaster = useToastr();

const modalityTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedModalityType = ref(null);

/**
 * Fetches the list of modality types from the API.
 */
const getModalityTypes = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/modality-types');
        modalityTypes.value = response.data.data || response.data; // Adjust based on your API response structure
    } catch (err) {
        console.error('Error fetching modality types:', err);
        error.value = err.response?.data?.message || 'Failed to load modality types. Please try again.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};

/**
 * Opens the ModalityModel for adding a new modality type or editing an existing one.
 * @param {Object} modalityType - The modality type object to edit, or null for a new one.
 */
const openModal = (modalityType = null) => {
    selectedModalityType.value = modalityType ? { ...modalityType } : {
        image_url: '',
        name: '',
        description: ''
    };
    isModalOpen.value = true;
};

/**
 * Closes the ModalityModel.
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedModalityType.value = null; // Clear selected modality type when modal closes
};

/**
 * Refreshes the modality type list after an action (add/edit/delete).
 */
const refreshModalityTypes = () => {
    getModalityTypes();
    closeModal();
};

/**
 * Handles the deletion of a modality type.
 * @param {number} id - The ID of the modality type to delete.
 */
const deleteModalityType = async (id) => {
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
        try {
            await axios.delete(`/api/modality-types/${id}`);
            toaster.success('Modality Type deleted successfully!');
            await getModalityTypes(); // Refresh the list
            swal.fire(
                'Deleted!',
                'Your modality type has been deleted.',
                'success'
            );
        } catch (err) {
            console.error('Error deleting modality type:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete modality type.';
            toaster.error(errorMessage);
            swal.fire(
                'Error!',
                errorMessage,
                'error'
            );
        }
    }
};

// Fetch modality types when the component is mounted
onMounted(() => {
    getModalityTypes();
});
</script>

<template>
    <div class="modality-type-page">
        <div class="content-header">
            <div class="header-flex-container">
                <h1 class="page-title">Modality Types Management</h1>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li>Modality Types</li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Modality Type List</h2>
                        <button @click="openModal()" class="add-modality-type-button">
                            <i class="fas fa-plus-circle button-icon"></i>
                            <span>Add New Modality Type</span>
                        </button>
                    </div>

                    <div v-if="loading" class="loading-state">
                        <div class="spinner" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="loading-text">Loading modality types...</p>
                    </div>

                    <div v-else-if="error" class="error-message" role="alert">
                        <strong class="error-bold">Error!</strong>
                        <span class="error-text">{{ error }}</span>
                    </div>

                    <div v-else-if="modalityTypes.length > 0" class="table-responsive">
                        <table class="modality-type-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">#</th>
                                    <th class="table-header">Image</th>
                                    <th class="table-header">Name</th>
                                    <th class="table-header">Description</th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <ModalityListItem
                                    v-for="(modalityType, index) in modalityTypes"
                                    :key="modalityType.id"
                                    :modality-type="modalityType"
                                    :index="index"
                                    @edit="openModal"
                                    @delete="deleteModalityType"
                                />
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="no-modality-types">
                        <p class="no-modality-types-text">No modality types found. Click "Add New Modality Type" to get started!</p>
                        <i class="fas fa-box-open no-modality-types-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <ModalityModel
            :show-modal="isModalOpen"
            :modality-type-data="selectedModalityType"
            @close="closeModal"
            @modality-type-updated="refreshModalityTypes"
            @modality-type-added="refreshModalityTypes"
        />
    </div>
</template>

<style scoped>
/* Base Page Layout */
.modality-type-page {
    padding: 1rem; /* p-4 */
    background-color: #f3f4f6; /* bg-gray-100 */
    min-height: 100vh; /* min-h-screen */
}

/* Content Header */
.content-header {
    margin-bottom: 1.5rem; /* mb-6 */
}

.header-flex-container {
    display: flex; /* flex */
    justify-content: space-between; /* justify-between */
    align-items: center; /* items-center */
}

.page-title {
    font-size: 1.875rem; /* text-3xl */
    font-weight: 700; /* font-bold */
    color: #1f2937; /* text-gray-800 */
}

.breadcrumbs {
    font-size: 0.875rem; /* text-sm */
}

.breadcrumb-list {
    display: flex; /* flex */
    align-items: center; /* items-center */
    list-style: none; /* No default list styling */
    padding: 0;
    margin: 0;
    color: #6b7280; /* text-gray-500 */
}

.breadcrumb-list li {
    margin-right: 0.5rem; /* space-x-2 for items */
}

.breadcrumb-list li:last-child {
    margin-right: 0;
}

.breadcrumb-link {
    color: #2563eb; /* text-blue-600 */
    text-decoration: none;
}

.breadcrumb-link:hover {
    text-decoration: underline; /* hover:underline */
}

.breadcrumb-separator {
    font-size: 0.75rem; /* text-xs */
    margin-left: 0.5rem; /* Added for spacing */
    margin-right: 0.5rem;
}

/* Main Content Area */
.content {
    /* No direct styles needed here based on the original Tailwind structure,
        as the container handles its width and centering. */
}

.container {
    max-width: 80rem; /* max-w-7xl */
    margin-left: auto; /* mx-auto */
    margin-right: auto; /* mx-auto */
}

.card {
    background-color: #ffffff; /* bg-white */
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
    border-radius: 0.5rem; /* rounded-lg */
    padding: 1.5rem; /* p-6 */
}

.card-header {
    display: flex; /* flex */
    justify-content: space-between; /* justify-between */
    align-items: center; /* items-center */
    margin-bottom: 1.5rem; /* mb-6 */
}

.card-title {
    font-size: 1.5rem; /* text-2xl */
    font-weight: 600; /* font-semibold */
    color: #374151; /* text-gray-700 */
}

.add-modality-type-button {
    display: inline-flex; /* inline-flex */
    align-items: center; /* items-center */
    margin-left: 700px;
    padding: 0.5rem 1rem; /* px-4 py-2 */
    background-color: #2563eb; /* bg-blue-600 */
    color: #ffffff; /* text-white */
    font-weight: 600; /* font-semibold */
    border-radius: 0.375rem; /* rounded-md */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* shadow-md */
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter; /* transition */
    transition-duration: 300ms; /* duration-300 */
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); /* ease-in-out */
    border: none;
    cursor: pointer;
}

.add-modality-type-button:hover {
    background-color: #1d4ed8; /* hover:bg-blue-700 */
}

.button-icon {
    margin-right: 0.5rem; /* mr-2 */
}

/* Loading State */
.loading-state {
    text-align: center; /* text-center */
    padding-top: 2rem; /* py-8 */
    padding-bottom: 2rem; /* py-8 */
}

.spinner {
    display: inline-block; /* inline-block */
    width: 2rem; /* w-8 */
    height: 2rem; /* h-8 */
    border: 4px solid #3b82f6; /* border-4 border-blue-500 */
    border-top-color: transparent; /* border-t-transparent */
    border-radius: 9999px; /* rounded-full */
    animation: spin 1s linear infinite; /* animate-spin */
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
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
    border-width: 0;
}

.loading-text {
    color: #4b5563; /* text-gray-600 */
    margin-top: 0.5rem; /* mt-2 */
}

/* Error Message */
.error-message {
    background-color: #fee2e2; /* bg-red-100 */
    border: 1px solid #ef4444; /* border border-red-400 */
    color: #b91c1c; /* text-red-700 */
    padding: 0.75rem 1rem; /* px-4 py-3 */
    border-radius: 0.25rem; /* rounded */
    position: relative; /* relative */
    margin-bottom: 1rem; /* mb-4 */
}

.error-bold {
    font-weight: 700; /* font-bold */
}

.error-text {
    display: inline; /* sm:inline */
    margin-left: 0.5rem; /* ml-2 */
}

/* Table Styles */
.table-responsive {
    overflow-x: auto; /* overflow-x-auto */
}

.modality-type-table {
    min-width: 100%; /* min-w-full */
    line-height: 1.5; /* leading-normal */
    border-collapse: collapse; /* Ensure cells align */
}

.table-header-row {
    background-color: #e5e7eb; /* bg-gray-200 */
    color: #374151; /* text-gray-700 */
    text-transform: uppercase; /* uppercase */
    font-size: 0.875rem; /* text-sm */
    line-height: 1.25; /* leading-normal */
}

.table-header {
    padding: 0.75rem 1.5rem; /* py-3 px-6 */
    text-align: left; /* text-left */
}

.actions-header {
    text-align: center; /* text-center */
}

.table-body {
    color: #4b5563; /* text-gray-600 */
    font-size: 0.875rem; /* text-sm */
    font-weight: 300; /* font-light */
}

/* No Modality Types Found */
.no-modality-types {
    text-align: center; /* text-center */
    padding-top: 2rem; /* py-8 */
    color: #6b7280; /* text-gray-500 */
}

.no-modality-types-text {
    font-size: 1.125rem; /* text-lg */
}

.no-modality-types-icon {
    font-size: 4rem; /* text-6xl */
    margin-top: 1rem; /* mt-4 */
    color: #d1d5db; /* text-gray-300 */
}
</style>