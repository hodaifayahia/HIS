<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';

// Reactive state
const specializations = ref([]);
const searchQuery = ref('');
const loading = ref(false);
const errorMessage = ref(''); // To store error messages
const router = useRouter();

// Computed property for filtered specializations
const filteredSpecializations = ref([]);

// Fetch specializations
const getSpecializations = async () => {
    try {
        loading.value = true;
        errorMessage.value = ''; // Clear previous errors
        const response = await axios.get('/api/specializations');
        specializations.value = response.data.data || response.data;
        filterSpecializations(); // Filter immediately after fetching
    } catch (error) {
        console.error('Error fetching specializations:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to load specializations. Please try again later.';
    } finally {
        loading.value = false;
    }
};

// Filter specializations based on search query
const filterSpecializations = () => {
    if (!searchQuery.value) {
        filteredSpecializations.value = specializations.value;
    } else {
        filteredSpecializations.value = specializations.value.filter(spec =>
            spec.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
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
        filterSpecializations();
    }, 300);
});

// Fetch data on mount
onMounted(() => {
    getSpecializations();
});
</script>

<template>
    <div>
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Doctors</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <h2 class="text-center mb-4">Specializations</h2>

                <div class="mb-4 d-flex justify-content-center">
                    <span class="p-input-icon-right" style="width: 100%; max-width: 500px;">
                        <i class="pi pi-search" />
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search specializations"
                            class="p-inputtext-lg w-100"
                        />
                    </span>
                </div>

                <div v-if="loading" class="d-flex justify-content-center mt-5">
                    <ProgressSpinner />
                </div>

                <div v-if="errorMessage" class="mt-4">
                    <Message severity="error">{{ errorMessage }}</Message>
                </div>

                <div class="row">
                    <div
                        v-for="specialization in filteredSpecializations"
                        :key="specialization.id"
                        class="col-md-3 mb-4 d-flex justify-content-center"
                    >
                        <Card
                            class="text-center shadow-lg specialization-card"
                            style="width: 100%; max-width: 250px; border-radius: 15px;"
                            @click="goToDoctorsPage(specialization)"
                        >
                            <template #content>
                                <div class="p-3">
                                    <div class="mx-auto" style="width: 120px; height: 120px; overflow: hidden;">
                                        <img
                                            :src="specialization.photo_url"
                                            alt="Specialization image"
                                            class="w-100 h-100"
                                            style="object-fit: contain"
                                        />
                                    </div>
                                </div>
                                <p class="card-text text-dark fw-bold fs-5 mt-3">
                                    {{ specialization.name }}
                                </p>
                            </template>
                        </Card>
                    </div>
                </div>

                <div v-if="!loading && !errorMessage && filteredSpecializations.length === 0" class="text-center mt-4">
                    No specializations found matching your search.
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Keeping the card-specific styles from the original component */
.specialization-card {
    transition: transform 0.2s;
    cursor: pointer;
}

.specialization-card:hover {
    transform: scale(1.05);
}

/* PrimeVue-specific styling for the search input to match your design */
:deep(.p-inputtext.p-inputtext-lg) {
    padding: 10px 15px !important;
    font-size: 16px !important;
    border-radius: 50px !important;
    border: 2px solid var(--primary-color, #007bff) !important;
}

:deep(.p-input-icon-right .p-inputtext) {
    padding-right: 2.5rem !important;
}

:deep(.p-input-icon-right .p-inputtext + .pi) {
    right: 1rem !important;
    color: var(--primary-color, #007bff) !important;
}
</style>