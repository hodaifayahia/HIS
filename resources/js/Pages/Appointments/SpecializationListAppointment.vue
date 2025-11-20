<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';

// Reactive state
const specializations = ref([]);
const searchQuery = ref('');
const loading = ref(false);
const errorMessage = ref('');
const router = useRouter();

// Computed property for filtered specializations
const filteredSpecializations = computed(() => {
    if (!searchQuery.value) {
        return specializations.value;
    }
    return specializations.value.filter(spec =>
        spec.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (spec.description && spec.description.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
        (spec.service_name && spec.service_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

// Fetch specializations
const getSpecializations = async () => {
    try {
        loading.value = true;
        errorMessage.value = '';
        const response = await axios.get('/api/specializations', {
            params: { all: true }
        });
        specializations.value = response.data.data || response.data;
        // Debug: Log photo URLs
        console.log('Specializations loaded:', specializations.value.map(s => ({
            name: s.name,
            photo_url: s.photo_url
        })));
    } catch (error) {
        console.error('Error fetching specializations:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to load specializations. Please try again later.';
    } finally {
        loading.value = false;
    }
};

// Navigate to doctors page
const goToDoctorsPage = (specialization) => {
    router.push({
        name: 'admin.appointments.doctors',
        params: { id: specialization.id },
        query: { name: specialization.name },
    });
};

// Watch for search query changes with debounce
let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        // Filter is now computed, so no need to call function
    }, 300);
});

// Fetch data on mount
onMounted(() => {
    getSpecializations();
});

// Handle image loading errors
const handleImageError = (event) => {
    console.warn('Failed to load image for specialization:', event);
    event.target.style.display = 'none';
};
</script>

<template>
    <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
        <!-- Enhanced Header with Gradient Background -->
        <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
            <div class="tw-px-6 tw-py-6">
                <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
                            <i class="bi bi-stethoscope tw-text-white tw-text-xl"></i>
                        </div>
                        <div>
                            <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-mb-1">
                                Medical Specializations
                            </h1>
                            <p class="tw-text-slate-600 tw-text-sm">
                                Select a specialization to browse available doctors
                            </p>
                        </div>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                        <a href="#" class="tw-text-blue-600 hover:tw-text-blue-700 tw-font-medium">
                            <i class="bi bi-house tw-mr-1"></i>Home
                        </a>
                        <i class="bi bi-chevron-right tw-text-slate-400"></i>
                        <span class="tw-text-slate-600 tw-font-medium">Specializations</span>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="tw-px-6 tw-py-8">
            <div class="tw-max-w-7xl tw-mx-auto">
                <!-- Search and Stats Section -->
                <div class="tw-mb-8">
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-6">
                        <!-- Total Specializations Card -->
                        <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-lg tw-p-4 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                                    <i class="bi bi-collection tw-text-blue-600 tw-text-lg"></i>
                                </div>
                                <div>
                                    <p class="tw-text-xs tw-font-semibold tw-text-slate-600 tw-uppercase tw-mb-1">Total Specializations</p>
                                    <p class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ specializations.length }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Available Specializations Card -->
                        <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-lg tw-p-4 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <div class="tw-w-10 tw-h-10 tw-bg-green-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                                    <i class="bi bi-check-circle tw-text-green-600 tw-text-lg"></i>
                                </div>
                                <div>
                                    <p class="tw-text-xs tw-font-semibold tw-text-slate-600 tw-uppercase tw-mb-1">Available</p>
                                    <p class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ filteredSpecializations.length }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State Indicator -->
                        <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-lg tw-p-4 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <div class="tw-w-10 tw-h-10 tw-bg-amber-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                                    <i class="bi bi-search tw-text-amber-600 tw-text-lg"></i>
                                </div>
                                <div>
                                    <p class="tw-text-xs tw-font-semibold tw-text-slate-600 tw-uppercase tw-mb-1">Search Status</p>
                                    <p class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ searchQuery ? 'Active' : 'Ready' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6">
                        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-items-center">
                            <div class="tw-flex-1 tw-w-full">
                                <div class="tw-relative">
                                    <i class="bi bi-search tw-absolute tw-left-4 tw-top-1/2 tw-transform tw--translate-y-1/2 tw-text-slate-400"></i>
                                    <InputText
                                        v-model="searchQuery"
                                        placeholder="Search specializations by name..."
                                        class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-300 tw-rounded-lg tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-transparent tw-transition-all"
                                    />
                                </div>
                            </div>
                            <Button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                icon="bi bi-x-circle"
                                label="Clear"
                                severity="secondary"
                                outlined
                                size="small"
                            />
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
                    <div class="tw-text-center">
                        <ProgressSpinner class="tw-mb-4" />
                        <p class="tw-text-slate-600 tw-font-medium">Loading specializations...</p>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-else-if="errorMessage" class="tw-mb-6">
                    <Message severity="error" class="tw-w-full">
                        <div class="tw-flex tw-items-center tw-gap-3">
                            <i class="bi bi-exclamation-triangle tw-text-xl"></i>
                            <span>{{ errorMessage }}</span>
                        </div>
                    </Message>
                </div>

                <!-- Specializations Grid -->
                <div v-else-if="filteredSpecializations.length > 0" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
                    <div
                        v-for="specialization in filteredSpecializations"
                        :key="specialization.id"
                        @click="goToDoctorsPage(specialization)"
                        class="tw-group tw-cursor-pointer tw-transform tw-transition-all tw-duration-300 hover:tw-scale-105 tw-h-full"
                    >
                        <div class="tw-bg-gradient-to-br tw-from-white tw-to-slate-50 tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden hover:tw-shadow-2xl tw-transition-all tw-duration-300 tw-flex tw-flex-col tw-h-full">
                            <!-- Image Container with Improved Display -->
                            <div class="tw-relative tw-overflow-hidden tw-bg-gradient-to-br tw-from-blue-100 tw-to-indigo-100 tw-h-56 tw-flex tw-items-center tw-justify-center tw-group">
                                <!-- Image with better styling -->
                                <img
                                    v-if="specialization.photo_url"
                                    :src="specialization.photo_url"
                                    :alt="specialization.name"
                                    class="tw-w-full tw-h-full tw-object-cover tw-group-hover:tw-scale-125 tw-transition-transform tw-duration-500 tw-ease-out"
                                    loading="lazy"
                                    @error="handleImageError"
                                />
                                
                                <!-- Fallback Icon when no image -->
                                <div v-else class="tw-absolute tw-inset-0 tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-slate-300">
                                    <i class="bi bi-hospital tw-text-7xl tw-mb-3 tw-opacity-60"></i>
                                    <span class="tw-text-xs tw-text-slate-500 tw-font-medium">No image</span>
                                </div>
                                
                                <!-- Enhanced Overlay with gradient -->
                                <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-t tw-from-black/70 tw-via-black/30 tw-to-transparent tw-opacity-0 group-hover:tw-opacity-100 tw-transition-opacity tw-duration-300 tw-flex tw-flex-col tw-items-center tw-justify-end tw-pb-6 tw-gap-2">
                                    <Button
                                        icon="bi bi-arrow-right-circle-fill"
                                        label="View Doctors"
                                        severity="info"
                                        size="small"
                                        class="tw-shadow-2xl tw-font-semibold"
                                        rounded
                                    />
                                    <span class="tw-text-white tw-text-xs tw-opacity-80 tw-font-medium">Click to explore doctors</span>
                                </div>

                                <!-- Specialty Badge Overlay (top right) -->
                                <div class="tw-absolute tw-top-3 tw-right-3 tw-bg-white/95 tw-backdrop-blur-sm tw-rounded-lg tw-px-3 tw-py-1.5 tw-shadow-lg tw-z-10">
                                    <span class="tw-text-xs tw-font-bold tw-text-blue-600">
                                        <i class="bi bi-star-fill tw-text-yellow-500 tw-mr-1"></i>{{ specialization.name.substring(0, 3).toUpperCase() }}
                                    </span>
                                </div>
                            </div>

                            <!-- Content Container -->
                            <div class="tw-p-6 tw-flex-grow tw-flex tw-flex-col">
                                <h3 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-2 tw-group-hover:tw-text-blue-600 tw-transition-colors tw-line-clamp-2">
                                    {{ specialization.name }}
                                </h3>

                                <div v-if="specialization.description" class="tw-mb-4 tw-flex-grow">
                                    <p class="tw-text-sm tw-text-slate-600 tw-line-clamp-2">
                                        {{ specialization.description }}
                                    </p>
                                </div>

                                <!-- Info Badge with better styling -->
                                <div class="tw-flex tw-items-center tw-gap-2 tw-pt-4 tw-border-t tw-border-slate-200/60 tw-mt-auto">
                                    <div class="tw-flex-1">
                                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-px-3 tw-py-1.5 tw-bg-gradient-to-r tw-from-blue-100 tw-to-blue-50 tw-text-blue-700 tw-text-xs tw-font-semibold tw-rounded-full tw-border tw-border-blue-200/50">
                                            <i class="bi bi-hospital"></i>{{ specialization.service_name || 'Medical Service' }}
                                        </span>
                                    </div>
                                    <div class="tw-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-full tw-text-white tw-group-hover:tw-shadow-lg tw-transition-all tw-group-hover:tw-scale-110">
                                        <i class="bi bi-arrow-right tw-text-sm tw-font-bold"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="tw-text-center tw-py-16">
                    <div class="tw-max-w-md tw-mx-auto">
                        <i class="bi bi-inbox tw-text-6xl tw-text-slate-300 tw-mb-4 tw-block"></i>
                        <h3 class="tw-text-xl tw-font-bold tw-text-slate-600 tw-mb-2">
                            {{ searchQuery ? 'No specializations found' : 'No specializations available' }}
                        </h3>
                        <p class="tw-text-slate-500 tw-mb-6">
                            {{ searchQuery ? 'Try adjusting your search terms' : 'Please contact administration' }}
                        </p>
                        <Button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            icon="bi bi-arrow-counterclockwise"
                            label="Clear Search"
                            severity="secondary"
                            outlined
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Tailwind utilities are applied via tw-* classes in template */

/* Image Handling */
img {
    max-width: 100%;
    height: auto;
    display: block;
}

/* Additional enhancements for smooth transitions */
:deep(.p-progressspinner) {
    width: 3rem;
    height: 3rem;
}

:deep(.p-message) {
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border: none;
}

:deep(.p-message-wrapper) {
    padding: 1rem 1.5rem;
}

/* Enhanced Card Styling */
.tw-group {
    position: relative;
}

.tw-group img {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.tw-group:hover img {
    transform: scale(1.25);
}

/* Custom scrollbar for search results */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(71, 85, 105, 0.3);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(71, 85, 105, 0.5);
}

/* Line clamp utility fallback */
.tw-line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Smooth animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Button hover effects */
:deep(.p-button) {
    transition: all 0.2s ease-in-out;
}

:deep(.p-button:hover) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
}

/* Image overlay effect */
.tw-group .tw-relative::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.tw-group:hover .tw-relative::after {
    opacity: 1;
}

/* Card elevation on hover */
.tw-group {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tw-group:active {
    transform: scale(0.98);
}

/* Badge animation */
.tw-group .tw-absolute.tw-top-3 {
    animation: slideIn 0.4s ease-out;
}

/* Ensure full height cards in grid */
.tw-grid > div {
    display: flex;
    flex-direction: column;
}
</style>