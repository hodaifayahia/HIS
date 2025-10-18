<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter ,useRoute} from 'vue-router';
import { useToastr } from '../../../../toster';
import { useSweetAlert } from '../../../../useSweetAlert';
import RoomTypeModel from '../../../../Apps/infrastructure/rooms/roomstype/RoomTypeModel.vue';

const router = useRouter();
const route = useRoute();
const swal = useSweetAlert();
const toaster = useToastr();

const roomTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedRoomType = ref(null);
const searchQuery = ref('');
const service_id = ref(route.params.id || '');
const type = ref(null);



/**
 * Computed property for filtered room types
 */
const filteredRoomTypes = computed(() => {
    let filtered = roomTypes.value;
    
    if (searchQuery.value) {
        filtered = filtered.filter(roomType => 
            roomType.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (roomType.description && roomType.description.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }
    
    return filtered;
});

/**\
 * Fetches the list of room types from the API
 */
// In your <script setup>

// Update your getRoomTypes function to include room_type filter
const getRoomTypes = async () => {
    
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get('/api/room-types', {
            params: {
                type: type.value,
                service_id: service_id.value
            }
        });
        roomTypes.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error fetching room types:', err);
        error.value = err.response?.data?.message || 'Failed to load room types. Please try again.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};

// Add a method to filter by room type
const filterByRoomType = (roomTypeFilter) => {
    type.value = roomTypeFilter;
    getRoomTypes();
};
/**
 * Navigate to rooms list for specific type
 */
const navigateToRooms = (roomType) => {
    router.push({
        name: 'infrastructure.Areas.rooms', // Ensure this matches your router config for the rooms overview page
        params: { typeId: roomType.id }, // You might want to pass typeId
        query: { service_id: service_id.value } // You might want to pass typeName as query param
    });
};

/**
 * Opens the modal for adding/editing room types
 */
const openModal = (roomType = null) => {
    selectedRoomType.value = roomType ? { ...roomType } : {
        name: '',
        description: '',
        image_url: '', // Initialize image_url for new entries
    };
    isModalOpen.value = true;
};

/**
 * Closes the modal
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedRoomType.value = null;
};

/**
 * Refreshes the room type list
 */
const refreshRoomTypes = () => {
    getRoomTypes();
    closeModal();
};

/**
 * Handles room type deletion
 */
const deleteRoomType = async (roomType) => {
    const result = await swal.fire({
        title: 'Delete Room Type?',
        text: `Are you sure you want to delete "${roomType.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/room-types/${roomType.id}`);
            toaster.success('Room type deleted successfully!');
            await getRoomTypes();
            swal.fire('Deleted!', 'Room type has been deleted.', 'success');
        } catch (err) {
            console.error('Error deleting room type:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete room type.';
            toaster.error(errorMessage);
            swal.fire('Error!', errorMessage, 'error');
        }
    }
};

/**
 * Get icon for room type based on name
 */
const getRoomTypeIcon = (name) => {
    const iconMap = {
        'hospitalisation': 'fas fa-bed', // Added for Hospitalisation type
        'consultation': 'fas fa-user-md',
        'surgery': 'fas fa-cut',
        'emergency': 'fas fa-ambulance',
        'laboratory': 'fas fa-flask',
        'radiology': 'fas fa-x-ray',
        'pharmacy': 'fas fa-pills',
        'waiting': 'fas fa-chair',
        'office': 'fas fa-briefcase',
        'storage': 'fas fa-boxes',
        'meeting': 'fas fa-users',
        'cath lab': 'fas fa-heartbeat', // Added for Cath Lab
        'operating theater': 'fas fa-hospital-symbol', // Added for Operating Theater
        'treatment': 'fas fa-syringe', // Added for general Treatment Rooms
        'default': 'fas fa-door-open'
    };
    
    // Normalize name to lowercase for matching
    const normalizedName = name.toLowerCase();
    
    // Find a key that is contained in the normalized name
    const key = Object.keys(iconMap).find(k => 
        normalizedName.includes(k)
    );
    
    return iconMap[key] || iconMap.default;
};

/**
 * Get color scheme for room type
 */
const getRoomTypeColor = (index) => {
    const colors = [
        { bg: 'from-blue-500 to-blue-600', accent: 'bg-blue-100 text-blue-700' },
        { bg: 'from-emerald-500 to-emerald-600', accent: 'bg-emerald-100 text-emerald-700' },
        { bg: 'from-purple-500 to-purple-600', accent: 'bg-purple-100 text-purple-700' },
        { bg: 'from-orange-500 to-orange-600', accent: 'bg-orange-100 text-orange-700' },
        { bg: 'from-pink-500 to-pink-600', accent: 'bg-pink-100 text-pink-700' },
        { bg: 'from-indigo-500 to-indigo-600', accent: 'bg-indigo-100 text-indigo-700' },
        { bg: 'from-red-500 to-red-600', accent: 'bg-red-100 text-red-700' },
        { bg: 'from-teal-500 to-teal-600', accent: 'bg-teal-100 text-teal-700' }
    ];
    
    return colors[index % colors.length];
};

// Fetch room types on component mount
onMounted(() => {
    getRoomTypes();
});
</script>

<template>
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="page-title">Room Types</h1>
                <p class="page-subtitle">Organize and manage different categories of rooms in your facility</p>
            </div>
            
            <nav class="breadcrumbs">
                <div class="breadcrumb-container">
                    <a href="#" class="breadcrumb-link">Infrastructure</a>
                    <i class="fas fa-chevron-right breadcrumb-separator"></i>
                    <span class="breadcrumb-current">Room Types</span>
                </div>
            </nav>
        </div>
    </div>
    
    <div class="room-types-page p-2">
        <div class="btn-group mb-3" role="group" aria-label="Room type filter">
    <button 
        type="button"
        @click="filterByRoomType('')"
        :class="{'btn-primary': !type, 'btn-outline-secondary': type}"
        class="btn"
    >
        All Rooms
    </button>
    <button 
        type="button"
        @click="filterByRoomType('Normal')"
        :class="{'btn-primary': type === 'Normal', 'btn-outline-secondary': type !== 'Normal'}"
        class="btn"
    >
        Normal Rooms
    </button>
    <button 
        type="button"
        @click="filterByRoomType('WaitingRoom')"
        :class="{'btn-primary': type === 'WaitingRoom', 'btn-outline-secondary': type !== 'WaitingRoom'}"
        class="btn"
    >
        Waiting Rooms
    </button>
</div>
        <div class="controls-section">
            <div class="controls-container">
                <div class="search-section">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search room types..." 
                            class="search-input"
                        >
                        <button 
                            v-if="searchQuery" 
                            @click="clearFilters"
                            class="clear-search-btn"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="action-section">
                    <div class="results-count">
                        <span class="count-badge">{{ filteredRoomTypes.length }}</span>
                        <span class="count-text">Room Types</span>
                    </div>
                    
                    <button @click="openModal()" class="add-button">
                        <i class="fas fa-plus add-icon"></i>
                        <span>Add Room Type</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div v-if="loading" class="loading-container">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p class="loading-text">Loading room types...</p>
                </div>
            </div>

            <div v-else-if="error" class="error-container">
                <div class="error-card">
                    <div class="error-icon-wrapper">
                        <i class="fas fa-exclamation-triangle error-icon"></i>
                    </div>
                    <div class="error-content">
                        <h3 class="error-title">Something went wrong</h3>
                        <p class="error-message">{{ error }}</p>
                        <button @click="getRoomTypes" class="retry-button">
                            <i class="fas fa-redo retry-icon"></i>
                            Try Again
                        </button>
                    </div>
                </div>
            </div>

            <div v-else-if="filteredRoomTypes.length > 0" class="cards-grid">
                <div 
                    v-for="(roomType, index) in filteredRoomTypes" 
                    :key="roomType.id"
                    class="room-type-card"
                    @click="navigateToRooms(roomType)"
                >
                    <!-- Simplified Card Structure -->
                    <div
                    
                     class="card-content">
                        <!-- Icon Section -->
                        <div class="icon-section">
                            <div class="room-icon-container" :class="getRoomTypeColor(index).bg">
                                <img v-if="roomType.image_url" 
                                     :src="roomType.image_url" 
                                     :alt="roomType.name" 
                                     class="room-type-image" />
                                <i v-else :class="getRoomTypeIcon(roomType.name)" class="room-icon"></i>
                            </div>
                        </div>

                        <!-- Title Section -->
                        <div class="title-section">
                            <h4 class="room-title">{{ roomType.name }}</h4>
                            <p class="room-count">{{ roomType.rooms_count || 0 }} Rooms</p>
                        </div>

                        <!-- Action Buttons (Hidden by default, shown on hover) -->
                        <div class="card-actions">
                            <button 
                                @click.stop="openModal(roomType)"
                                class="action-btn edit-btn"
                                title="Edit Room Type"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                @click.stop="deleteRoomType(roomType)"
                                class="action-btn delete-btn"
                                title="Delete Room Type"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="empty-state">
                <div class="empty-content">
                    <div class="empty-icon-wrapper">
                        <i class="fas fa-layer-group empty-icon"></i>
                    </div>
                    <h3 class="empty-title">
                        {{ searchQuery ? 'No matching room types' : 'No room types yet' }}
                    </h3>
                    <p class="empty-message">
                        {{ searchQuery 
                            ? 'Try adjusting your search criteria' 
                            : 'Create your first room type to get started organizing your facility' 
                        }}
                    </p>
                    <div class="empty-actions">
                        <button 
                            v-if="searchQuery" 
                            @click="clearFilters"
                            class="secondary-button"
                        >
                            Clear Search
                        </button>
                        <button @click="openModal()" class="primary-button">
                            <i class="fas fa-plus"></i>
                            Create Room Type
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <RoomTypeModel
            :show-modal="isModalOpen"
            :room-type-data="selectedRoomType"
            @close="closeModal"
            @room-type-updated="refreshRoomTypes"
            @room-type-added="refreshRoomTypes"
        />
    </div>
</template>
<style scoped>
/* Base Styles */
.room-types-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding-left: 20px;
}

/* Header Section - Keep existing styles */
.page-header {
    background: linear-gradient(135deg, #5271fa 0%, #2d72f1 100%);
    color: rgb(233, 233, 233);
    padding: 2rem 30px;
    margin-bottom: 0;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.header-text {
    flex: 1;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    background: white;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: white;
    font-size: 1.125rem;
    margin: 0;
    line-height: 1.6;
}

.breadcrumbs {
    display: flex;
    align-items: center;
}

.breadcrumb-container {
    display: flex;
    align-items: center;
    background: white;
    padding: 0.75rem 1.25rem;
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.breadcrumb-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.breadcrumb-link:hover {
    color: #1d4ed8;
}

.breadcrumb-separator {
    margin: 0 0.75rem;
    color: #cbd5e1;
    font-size: 0.75rem;
}

.breadcrumb-current {
    color: #64748b;
    font-weight: 600;
}

/* Controls Section - Keep existing styles */
.controls-section {
    margin-bottom: 2rem;
}

.controls-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 1rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.search-section {
    flex: 1;
    max-width: 400px;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 1rem;
    color: #9ca3af;
    z-index: 10;
}

.search-input {
    width: 100%;
    padding: 0.875rem 1rem 0.875rem 2.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s;
    background: #f9fafb;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-search-btn {
    position: absolute;
    right: 0.75rem;
    background: #e5e7eb;
    border: none;
    border-radius: 50%;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.clear-search-btn:hover {
    background: #d1d5db;
}

.action-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.results-count {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.count-badge {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.875rem;
}

.count-text {
    color: #64748b;
    font-weight: 500;
}

.add-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
}

.add-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
}

.add-icon {
    font-size: 0.875rem;
}

/* Main Content */
.main-content {
    min-height: 400px;
}

/* Loading and Error States - Keep existing styles */
.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.loading-spinner {
    text-align: center;
}

.spinner {
    width: 3rem;
    height: 3rem;
    border: 4px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: #64748b;
    font-size: 1rem;
    margin: 0;
}

.error-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.error-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border: 1px solid #fca5a5;
    max-width: 400px;
}

.error-icon-wrapper {
    margin-bottom: 1rem;
}

.error-icon {
    font-size: 3rem;
    color: #ef4444;
}

.error-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.error-message {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.retry-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #ef4444;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

.retry-button:hover {
    background: #dc2626;
}

/* Simplified Cards Grid */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    justify-items: center;
}

/* Simplified Room Type Card */
.room-type-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border: none;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    width: 100%;
    max-width: 280px;
    animation: fadeInUp 0.6s ease-out;
}

.room-type-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.room-type-card:hover::before {
    transform: translateX(100%);
}

.room-type-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.card-content {
    padding: 2rem;
    text-align: center;
    position: relative;
}

/* Icon Section */
.icon-section {
    margin-bottom: 1.5rem;
}

.room-icon-container {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
}

.room-icon-container::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.room-type-image {
    width: 200px;
    height: 200px;
    object-fit: cover;
    border-radius: 50%;
    position: relative;
    z-index: 2;
}

.room-icon {
    font-size: 3rem;
    color: white;
    position: relative;
    z-index: 2;
}

/* Title Section */
.title-section {
    margin-bottom: 1rem;
}

.room-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.room-count {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
}

/* Action Buttons */
.card-actions {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.room-type-card:hover .card-actions {
    opacity: 1;
}

.action-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.action-btn:hover {
    background: white;
    transform: scale(1.1);
}

.edit-btn:hover {
    color: #3b82f6;
}

.delete-btn:hover {
    color: #ef4444;
}

/* Color Classes for Different Room Types */
.bg-blue {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.bg-green {
    background: linear-gradient(135deg, #10b981, #059669);
}

.bg-purple {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.bg-orange {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.bg-red {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.bg-teal {
    background: linear-gradient(135deg, #14b8a6, #0d9488);
}

/* Empty State - Keep existing styles */
.empty-state {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.empty-content {
    text-align: center;
    max-width: 400px;
}

.empty-icon-wrapper {
    margin-bottom: 1.5rem;
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e1;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
}

.empty-message {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.primary-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
}

.primary-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
}

.secondary-button {
    background: white;
    color: #64748b;
    padding: 0.875rem 1.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.secondary-button:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .cards-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .room-types-page {
        padding: 1rem;
    }
    
    .header-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .controls-container {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .search-section {
        max-width: none;
    }
    
    .action-section {
        justify-content: space-between;
    }
    
    .cards-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .empty-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .card-content {
        padding: 1.5rem;
    }
    
    .room-icon-container {
        width: 100px;
        height: 100px;
    }
    
    .room-icon {
        font-size: 2rem;
    }
}
</style>
