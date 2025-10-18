<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { modalityService } from '../../../../Components/Apps/services/modality/modalityService'; // Import your modality service

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressSpinner from 'primevue/progressspinner';

const route = useRoute();
const router = useRouter();
const specializationId = ref(route.params.id); // This param now represents a specialization ID to filter by

// --- Component State for Modalities ---
const modalities = ref([]); // Reactive array to store modalities
const isLoadingModalities = ref(false); // Loading state
const searchQuery = ref(''); // Local state for the search input

// --- Component Logic ---

/**
 * Fetches a list of modalities from the API using modalityService.
 * @param {number|null} currentSpecializationId - The ID to filter modalities by specialization.
 */
const fetchModalities = async (currentSpecializationId = null) => {
    isLoadingModalities.value = true;
    try {
        const params = {
            search: searchQuery.value, // Use local search query
            

        };
        if (currentSpecializationId) {
            params.specialization_id = currentSpecializationId; // Pass specialization ID as a filter
        }

        // Call the getAll method from your modalityService
        const response = await modalityService.getAll(params);

        if (response.success) {
            modalities.value = response.data.data || response.data; // Adjust based on API response structure
            // If your service returns meta/links, you could store them here too:
            // meta.value = response.meta;
            // links.value = response.links;
        } else {
            console.error('Failed to fetch modalities:', response.message);
            // Optionally, add a user-friendly error notification here (e.g., using PrimeVue Toast)
        }
    } catch (error) {
        // The error is already logged by the service, but you can add more specific handling here
        console.error('Error in fetchModalities component function:', error);
    } finally {
        isLoadingModalities.value = false;
    }
};

// Debounced search logic
const debouncedSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetchModalities(specializationId.value); // Re-fetch with updated search query
        }, 200);
    };
})();

// Watch for changes in the local searchQuery ref
watch(searchQuery, debouncedSearch);

// Fetch modalities on component mount
onMounted(() => {
    fetchModalities(specializationId.value);
});

// Adjusted navigation to a hypothetical "modality details" or "modality management" page
const goToModalityPage = (modality) => {
    // console.log(specializationId.value);
    
    // Adjust this route name and params based on your actual modality routes
    router.push({
        name: 'admin.modality-appointment.details', // Example: a route for modality details
        params: {
            id: modality.id,
            specializationId: specializationId.value, // Pass the specialization ID
        },
    });
};
</script>

---

<template>
    <div>
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Modalities</h1>
                    </div>
                    <div class="col-sm-12">
                        <Button
                            class="p-button-rounded p-button-primary float-left"
                            icon="fas fa-arrow-left"
                            label="Back"
                            @click="router.go(-1)"
                        />
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Modalities</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <h2 class="text-center mb-4">Modality Management</h2>
                <div class="mb-4">
                    <div class="mb-1 search-container">
                        <div class="search-wrapper">
                            <InputText
                                v-model="searchQuery"
                                type="text"
                                class="premium-search p-inputtext"
                                placeholder="Search modalities..."
                            />
                            <Button
                                class="search-button p-button-primary"
                                icon="fas fa-search"
                                label="Search"
                                @click="debouncedSearch"
                            />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div v-for="modality in modalities" :key="modality.id" class="col-md-3 mb-4 d-flex justify-content-center">
                        <Card
                            class="text-center shadow-lg"
                            :class="{ 'loading': isLoadingModalities }"
                            style="width: 100%; max-width: 250px; border-radius: 15px; cursor: pointer;"
                            @click="goToModalityPage(modality)"
                        >
                            <template #content>
                                <div class="p-3">
                                    <div class="mx-auto rounded-circle overflow-hidden border" style="width: 150px; height: 150px;">
                                        <img
                                            :src="modality.icon_url || '/path/to/default_modality_icon.png'"
                                            alt="Modality image"
                                            class="w-100 h-100"
                                            style="object-fit: contain; border-radius: 50%;"
                                        />
                                    </div>
                                </div>
                                <div class="card-body bg-light text-center p-3">
                                    <h4 class="fw-bold text-dark mb-2">{{ modality.name }}</h4>

                                    <div v-if="modality.modality_type?.name" class="mb-2 p-2 rounded bg-white shadow-sm">
                                        <p class="card-text text-primary fw-bold mb-1">
                                            <i class="pi pi-tags"></i> Type:
                                        </p>
                                        <p class="text-dark mb-0">
                                            {{ modality.modality_type.name }}
                                        </p>
                                    </div>

                                    <div class="p-2 rounded bg-white shadow-sm">
                                        <p class="card-text fw-bold mb-1"
                                           :class="modality.operational_status === 'active' ? 'text-success' : 'text-danger'">
                                            <i class="pi pi-info-circle"></i> Status:
                                        </p>
                                        <p class="text-dark mb-0">
                                            {{ modality.operational_status }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>

                <div v-if="modalities.length === 0 && !isLoadingModalities" class="text-center mt-4">
                    No Modalities Found...
                </div>
                <div v-if="isLoadingModalities" class="text-center mt-4">
                    <ProgressSpinner style="width:50px; height:50px" strokeWidth="8" fill="var(--surface-ground)" animationDuration=".5s" aria-label="Custom Progress" />
                    <p>Loading modalities...</p>
                </div>
            </div>
        </div>
    </div>
</template>

---

<style scoped>
/* Your existing styles. Ensure PrimeVue's theme is properly imported in main.js/ts */
.card {
    transition: transform 0.2s ease-in-out, filter 0.2s ease-in-out;
}

.card:hover {
    transform: scale(1.05);
}

.card.loading {
    filter: blur(2px);
    pointer-events: none; /* Disable clicks while loading */
}

.card-text {
    font-size: 0.875rem;
}

.search-container {
    width: 100%;
    position: relative;
}

.search-wrapper {
    display: flex;
    align-items: center;
    border: 2px solid #007BFF;
    border-radius: 50px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.p-inputtext {
    border: none;
    border-radius: 50px 0 0 50px;
    flex-grow: 1;
    padding: 10px 15px;
    font-size: 16px;
    outline: none;
}

.p-inputtext:focus {
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

.search-button.p-button {
    border: none;
    background: #007BFF;
    color: white;
    padding: 10px 20px;
    border-radius: 0 50px 50px 0;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
}

.search-button.p-button:hover {
    background: #0056b3;
}

.search-button.p-button .p-button-icon {
    margin-right: 5px;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
    }
}

.search-wrapper:focus-within {
    animation: pulse 1s;
}
</style>