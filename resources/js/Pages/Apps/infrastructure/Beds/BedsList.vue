<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useToastr } from '../../../../Components/toster'
import BedModel from "../../../../Components/Apps/infrastructure/bad/BedModel.vue"
import { useSweetAlert } from '../../../../Components/useSweetAlert'

const swal = useSweetAlert()
const toaster = useToastr()

const beds = ref([])
const rooms = ref([])
const services = ref([])
const patients = ref([])
const loading = ref(false)
const error = ref(null)
const isModalOpen = ref(false)
const selectedBed = ref({})
const searchQuery = ref('')
const statusFilter = ref('all') // 'all', 'free', 'occupied', 'reserved'
const serviceFilter = ref('')
const roomTypeFilter = ref('')
const roomFilter = ref('')

/**
 * Computed property for filtered beds
 */
const filteredBeds = computed(() => {
    let filtered = beds.value

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(bed =>
            bed.bed_identifier.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            bed.room.room_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (bed.room.service?.name && bed.room.service.name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (bed.current_patient?.name && bed.current_patient.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
        )
    }

    // Filter by status
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(bed => bed.status === statusFilter.value)
    }

    // Filter by service
    if (serviceFilter.value) {
        filtered = filtered.filter(bed => bed.room.service?.id == serviceFilter.value)
    }

    // Filter by room type
    if (roomTypeFilter.value) {
        filtered = filtered.filter(bed => bed.room.room_type === roomTypeFilter.value)
    }

    // Filter by specific room
    if (roomFilter.value) {
        filtered = filtered.filter(bed => bed.room.id == roomFilter.value)
    }

    return filtered
})

/**
 * Fetches the list of beds from the API
 */
const getBeds = async () => {
    loading.value = true
    error.value = null
    try {
        const response = await axios.get('/api/beds', {
            params: {
                all: true,
            }
        })
        beds.value = response.data.data || response.data
        console.log(beds.value);
        
    } catch (err) {
        console.error('Error fetching beds:', err)
        error.value = err.response?.data?.message || 'Failed to load beds. Please try again.'
        toaster.error(error.value)
    } finally {
        loading.value = false
    }
}

/**
 * Fetches available rooms
 */
const getRooms = async () => {
    try {
        const response = await axios.get('/api/rooms')
        rooms.value = response.data.data
    } catch (err) {
        console.error('Error fetching rooms:', err)
    }
}

/**
 * Fetches services for filtering
 */
const getServices = async () => {
    try {
        const response = await axios.get('/api/services')
        services.value = response.data.data || response.data
    } catch (err) {
        console.error('Error fetching services:', err)
      }
    }
    
    /**
     * Fetches patients for bed assignment
     */
const getPatients = async () => {
    try {
        const response = await axios.get('/api/patients')
        patients.value = response.data.data 
        console.log('Patients:', patients.value);
        
    } catch (err) {
        console.error('Error fetching patients:', err)
    }
}

/**
 * Opens the modal for adding/editing bed
 */
const openModal = (bed = null) => {
    selectedBed.value = bed ? { ...bed } : {
        room_id: '',
        bed_identifier: '',
        status: 'free',
        current_patient_id: null
    }
    isModalOpen.value = true
}

/**
 * Closes the modal
 */
const closeModal = () => {
    isModalOpen.value = false
    selectedBed.value = {}
}

/**
 * Handles a newly added bed
 */
const handleBedAdded = (newBed) => {
    beds.value.unshift(newBed)
    closeModal()
}

/**
 * Handles an updated bed
 */
const handleBedUpdated = (updatedBed) => {
    const index = beds.value.findIndex(b => b.id === updatedBed.id)
    if (index !== -1) {
        beds.value[index] = updatedBed
    }
    closeModal()
}

/**
 * Updates bed status
 */
const updateBedStatus = async (bed, newStatus) => {
    const result = await swal.fire({
        title: `Change Bed Status?`,
        text: `Are you sure you want to change bed ${bed.bed_identifier} status to ${newStatus}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, change it!',
        cancelButtonText: 'Cancel'
    })

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/api/beds/${bed.id}`, {
                status: newStatus,
                room_id: bed.room.id,
                bed_identifier: bed.bed_identifier,
                current_patient_id: newStatus === 'free' ? null : bed.current_patient_id
            })
            
            // Update the local bed object
            const index = beds.value.findIndex(b => b.id === bed.id)
            if (index !== -1) {
                beds.value[index] = response.data.data
            }
            
            toaster.success(`Bed status updated successfully!`)
        } catch (err) {
            console.error('Error updating bed status:', err)
            const errorMessage = err.response?.data?.message || 'Failed to update bed status.'
            toaster.error(errorMessage)
        }
    }
}

/**
 * Deletes a bed
 */
const deleteBed = async (id) => {
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
        })

        if (result.isConfirmed) {
            await axios.delete(`/api/beds/${id}`)
            beds.value = beds.value.filter(b => b.id !== id)
            toaster.success('Bed deleted successfully')
            swal.fire('Deleted!', 'Bed has been deleted.', 'success')
        }
    } catch (error) {
        console.error('Error deleting bed:', error)
        const errorMessage = error.response?.data?.message || 'Failed to delete bed.'
        toaster.error(errorMessage)
        swal.fire('Error!', errorMessage, 'error')
    }
}

/**
 * Clears all filters
 */
const clearFilters = () => {
    searchQuery.value = ''
    statusFilter.value = 'all'
    serviceFilter.value = ''
    roomTypeFilter.value = ''
    roomFilter.value = ''
}

/**
 * Gets status badge class
 */
const getStatusClass = (status) => {
    const classes = {
        'free': 'status-free',
        'occupied': 'status-occupied',
        'reserved': 'status-reserved'
    }
    return classes[status] || 'status-free'
}

/**
 * Gets status icon
 */
const getStatusIcon = (status) => {
    const icons = {
        'free': 'fa-check-circle',
        'occupied': 'fa-user',
        'reserved': 'fa-clock'
    }
    return icons[status] || 'fa-check-circle'
}

onMounted(() => {
    getBeds()
    getRooms()
    getServices()
    getPatients()
})
</script>

<template>
    <div class="bed-page">
        <div class="content-header">
            <div class="header-flex-container">
                <div class="header-left">
                    <h1 class="page-title">Bed Management</h1>
                    <p class="page-subtitle">Manage hospital beds and their assignments</p>
                </div>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li class="breadcrumb-current">Beds</li>
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
                                <h2 class="card-title">Bed List</h2>
                                <span class="bed-count">{{ filteredBeds.length }} of {{ beds.length }} beds</span>
                            </div>
                            <button @click="openModal()" class="add-bed-button">
                                <i class="fas fa-plus-circle button-icon"></i>
                                <span>Add Bed</span>
                            </button>
                        </div>

                        <div class="filters-section">
                            <div class="search-container">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search beds, rooms, services, patients..."
                                        class="search-input"
                                    >
                                </div>
                            </div>

                            <div class="filter-container">
                                <select v-model="statusFilter" class="status-filter">
                                    <option value="all">All Status</option>
                                    <option value="free">Free</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="reserved">Reserved</option>
                                </select>

                                <select v-model="serviceFilter" class="status-filter">
                                    <option value="">All Services</option>
                                    <option v-for="service in services" :key="service.id" :value="service.id">
                                        {{ service.name }}
                                    </option>
                                </select>

                                <select v-model="roomTypeFilter" class="status-filter">
                                    <option value="">All Room Types</option>
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                    <option value="ward">Ward</option>
                                    <option value="icu">ICU</option>
                                </select>

                                <select v-model="roomFilter" class="status-filter">
                                    <option value="">All Rooms</option>
                                    <option v-for="room in rooms" :key="room.id" :value="room.id">
                                        Room {{ room.room_number }}
                                    </option>
                                </select>

                                <button
                                    @click="clearFilters"
                                    class="clear-filters-btn"
                                    v-if="searchQuery || statusFilter !== 'all' || serviceFilter || roomTypeFilter || roomFilter"
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
                        <p class="loading-text">Loading beds...</p>
                    </div>

                    <div v-else-if="error" class="error-message" role="alert">
                        <div class="error-content">
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <div>
                                <strong class="error-bold">Error!</strong>
                                <span class="error-text">{{ error }}</span>
                            </div>
                        </div>
                        <button @click="getBeds" class="retry-button">
                            <i class="fas fa-redo"></i>
                            Retry
                        </button>
                    </div>

                    <div v-else-if="filteredBeds.length > 0" class="table-responsive">
                        <table class="bed-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">#</th>
                                    <th class="table-header">Bed ID</th>
                                    <th class="table-header">Room</th>
                                    <th class="table-header">Room Type</th>
                                    <th class="table-header">Service</th>
                                    <th class="table-header">Status</th>
                                    <th class="table-header">Current Patient</th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <tr v-for="(bed, index) in filteredBeds" :key="bed.id" class="table-row">
                                    <td class="table-cell">{{ index + 1 }}</td>
                                    <td class="table-cell">
                                        <div class="bed-identifier">{{ bed.bed_identifier }}</div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="room-info">
                                            <span class="room-number">{{ bed.room.room_number }}</span>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        <span class="room-type-badge">{{ bed.room.room_type }}</span>
                                    </td>
                                    <td class="table-cell">
                                        <span class="service-badge">{{ bed.room.service?.name || 'N/A' }}</span>
                                    </td>
                                    <td class="table-cell">
                                        <div class="status-dropdown">
                                            <select 
                                                :value="bed.status" 
                                                @change="updateBedStatus(bed, $event.target.value)"
                                                :class="['status-select', getStatusClass(bed.status)]"
                                            >
                                                <option value="free">Free</option>
                                                <option value="occupied">Occupied</option>
                                                <option value="reserved">Reserved</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="patient-info">
                                            <span v-if="bed.current_patient" class="patient-name">
                                                {{ bed.current_patient.name }}
                                            </span>
                                            <span v-else class="no-patient">No patient</span>
                                        </div>
                                    </td>
                                    <td class="table-cell actions-cell">
                                        <div class="actions-container">
                                            <button
                                                @click="openModal(bed)"
                                                class="action-button edit-button"
                                                title="Edit bed"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                @click="deleteBed(bed.id)"
                                                class="action-button delete-button"
                                                title="Delete bed"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="no-beds">
                        <div class="no-beds-content">
                            <i class="fas fa-bed no-beds-icon"></i>
                            <h3 class="no-beds-title">
                                {{ searchQuery || statusFilter !== 'all' || serviceFilter || roomTypeFilter || roomFilter ? 'No beds match your filters' : 'No beds found' }}
                            </h3>
                            <p class="no-beds-text">
                                {{ searchQuery || statusFilter !== 'all' || serviceFilter || roomTypeFilter || roomFilter ? 'Try adjusting your search or filters' : 'Click "Add Bed" to get started!' }}
                            </p>
                            <div class="no-beds-actions">
                                <button
                                    v-if="searchQuery || statusFilter !== 'all' || serviceFilter || roomTypeFilter || roomFilter"
                                    @click="clearFilters"
                                    class="clear-filters-btn"
                                >
                                    Clear Filters
                                </button>
                                <button @click="openModal()" class="add-bed-button">
                                    <i class="fas fa-plus-circle button-icon"></i>
                                    Add Bed
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <BedModel
            :show-modal="isModalOpen"
            :bed-data="selectedBed"
            :rooms="rooms"
            :patients="patients"
            @close="closeModal"
            @bed-added="handleBedAdded"
            @bed-updated="handleBedUpdated"
        />
    </div>
</template>

<style scoped>
/* Base Page Layout */
.bed-page {
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

.bed-count {
    font-size: 0.875rem;
    color: #64748b;
    background-color: #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 500;
}

.add-bed-button {
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

.add-bed-button:hover {
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
    flex-wrap: wrap;
}

.status-filter {
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    background-color: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 150px;
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

.bed-table {
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

/* Bed Identifier */
.bed-identifier {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

/* Room Info */
.room-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.room-number {
    font-weight: 600;
    color: #1e293b;
}

/* Room Type Badge */
.room-type-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background-color: #e0e7ff;
    color: #3730a3;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
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

/* Status Select */
.status-dropdown {
    position: relative;
}

.status-select {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    appearance: none;
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 1rem;
    padding-right: 2rem;
}

.status-select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.status-free {
    background-color: #dcfce7;
    color: #166534;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23166534' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
}

.status-occupied {
    background-color: #fee2e2;
    color: #991b1b;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23991b1b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
}

.status-reserved {
    background-color: #fff3cd;
    color: #856404;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23856404' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
}

/* Patient Info */
.patient-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.patient-name {
    font-weight: 500;
    color: #1e293b;
}

.no-patient {
    color: #9ca3af;
    font-style: italic;
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

/* No Beds */
.no-beds {
    padding: 4rem 2rem;
    text-align: center;
}

.no-beds-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-beds-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.no-beds-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.no-beds-text {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.no-beds-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive Design */
@media (max-width: 768px) {
    .bed-page {
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

    .status-select {
        font-size: 0.6rem;
        padding: 0.25rem 0.5rem;
        padding-right: 1.5rem;
    }
}
</style>
