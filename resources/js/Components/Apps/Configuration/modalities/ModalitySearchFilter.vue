<script setup>
import { ref, watch, defineProps, defineEmits, computed } from 'vue';

const props = defineProps({
    // Initial values for search and filters
    initialSearchQuery: {
        type: String,
        default: ''
    },
    initialFilters: {
        type: Object,
        default: () => ({
            modality_type_id: '',
            service_id: '',
            operational_status: '',
            physical_location_id: '',
            integration_protocol: '',
            data_retrieval_method: '',
            created_from: '',
            created_to: ''
        })
    },
    filterOptions: {
        type: Object,
        default: () => ({
            modality_types: [],
            services: [],
            physical_locations: [],
            protocols: [],
            data_retrieval_methods: [],
            operational_statuses: []
        })
    },
    totalResults: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['update:searchQuery', 'update:filters', 'clearFilters']);

// Internal states for search and filters, initialized from props
const searchQuery = ref(props.initialSearchQuery);
const filters = ref({ ...props.initialFilters }); // Deep copy to avoid direct mutation

const showFilters = ref(false); // UI state for showing/hiding filter panel

const primaryColor = '#2563eb'; // Define primary color for consistent styling

// Watch for changes in internal search/filters and emit to parent
watch(searchQuery, (newVal) => {
    emit('update:searchQuery', newVal);
});

watch(filters, (newVal) => {
    emit('update:filters', newVal);
}, { deep: true });

// Computed property to check if any filters are active
const hasActiveFilters = computed(() => {
    return searchQuery.value ||
           Object.values(filters.value).some(filter => filter !== '' && filter !== null);
});

// Method to clear all filters
const clearAllFilters = () => {
    searchQuery.value = '';
    filters.value = {
        modality_type_id: '',
        service_id: '',
        operational_status: '',
        physical_location_id: '',
        integration_protocol: '',
        data_retrieval_method: '',
        created_from: '',
        created_to: ''
    };
    emit('clearFilters'); // Emit event for parent to handle (e.g., reset pagination)
};
</script>

<template>
    <div class="search-filter-section">
        <div class="search-bar">
            <div class="search-input-container">
                <i class="fas fa-search search-icon"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search modalities..."
                    class="search-input"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="clear-search-button"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="filter-actions">
            <button
                @click="showFilters = !showFilters"
                class="filter-toggle-button"
                :class="{ active: showFilters }"
            >
                <i class="fas fa-filter"></i>
                Filters
                <span v-if="hasActiveFilters" class="filter-count">
                    {{ Object.values(filters).filter(f => f !== '' && f !== null).length + (searchQuery ? 1 : 0) }}
                </span>
            </button>

            <button
                v-if="hasActiveFilters"
                @click="clearAllFilters"
                class="clear-filters-button"
            >
                <i class="fas fa-eraser"></i>
                Clear All
            </button>

            <div class="results-info">
                <span class="results-count">
                    {{ totalResults }} results found
                </span>
            </div>
        </div>

        <div v-if="showFilters" class="filters-panel">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Modality Type</label>
                    <select v-model="filters.modality_type_id" class="filter-select">
                        <option value="">All Types</option>
                        <option
                            v-for="type in filterOptions.modality_types"
                            :key="type.id"
                            :value="type.id"
                        >
                            {{ type.name }}
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Service</label>
                    <select v-model="filters.service_id" class="filter-select">
                        <option value="">All Services</option>
                        <option
                            v-for="service in filterOptions.services"
                            :key="service.id"
                            :value="service.id"
                        >
                            {{ service.name }}
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select v-model="filters.operational_status" class="filter-select">
                        <option value="">All Statuses</option>
                        <option
                            v-for="status in filterOptions.operational_statuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Physical Location</label>
                    <select v-model="filters.physical_location_id" class="filter-select">
                        <option value="">All Locations</option>
                        <option
                            v-for="location in filterOptions.physical_locations"
                            :key="location.id"
                            :value="location.id"
                        >
                            {{ location.name }}
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Integration Protocol</label>
                    <select v-model="filters.integration_protocol" class="filter-select">
                        <option value="">All Protocols</option>
                        <option
                            v-for="protocol in filterOptions.protocols"
                            :key="protocol"
                            :value="protocol"
                        >
                            {{ protocol }}
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Data Retrieval Method</label>
                    <select v-model="filters.data_retrieval_method" class="filter-select">
                        <option value="">All Methods</option>
                        <option
                            v-for="method in filterOptions.data_retrieval_methods"
                            :key="method"
                            :value="method"
                        >
                            {{ method }}
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Created From</label>
                    <input
                        v-model="filters.created_from"
                        type="date"
                        class="filter-input"
                    />
                </div>

                <div class="filter-group">
                    <label class="filter-label">Created To</label>
                    <input
                        v-model="filters.created_to"
                        type="date"
                        class="filter-input"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Inherit and adapt styles from your original ModalityList.vue here */
/* Search and Filter Section */
.search-filter-section {
    margin-bottom: 1.5rem;
}

.search-bar {
    margin-bottom: 1rem;
}

.search-input-container {
    position: relative;
    max-width: 500px;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    width: 100%;
    padding: 0.75rem 0.75rem 0.75rem 2.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: #ffffff;
    transition: border-color 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: v-bind(primaryColor);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.clear-search-button {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
}

.clear-search-button:hover {
    color: #374151;
}

.filter-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.filter-toggle-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-toggle-button.active {
    background-color: v-bind(primaryColor);
    color: #ffffff;
    border-color: v-bind(primaryColor);
}

.filter-count {
    background-color: #ef4444;
    color: #ffffff;
    border-radius: 9999px;
    padding: 0.125rem 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.clear-filters-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #ef4444;
    color: #ffffff;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.clear-filters-button:hover {
    background-color: #dc2626;
}

.results-info {
    margin-left: auto;
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

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.filter-select, .filter-input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    background-color: #ffffff;
    transition: border-color 0.3s ease;
}

.filter-select:focus, .filter-input:focus {
    outline: none;
    border-color: v-bind(primaryColor);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .filter-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    .filters-grid {
        grid-template-columns: 1fr;
    }
}
</style>