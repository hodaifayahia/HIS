<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from "../../Components/PatientModel.vue";
import PatientListItem from './PatientListItem.vue';
import { useAuthStore } from '../../stores/auth';

const patients = ref([]);
const loading = ref(false);
const error = ref(null);
const toaster = useToastr();

// Use the Pinia store
const authStore = useAuthStore();

// Use computed instead of ref for role to prevent reactivity issues
const role = computed(() => authStore.user?.role || '');

const paginationData = ref({});
const selectedPatient = ref({});
const searchQuery = ref('');
const isModalOpen = ref(false);

const getPatients = async (page = 1) => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/patients?page=${page}`);

        if (response.data.data) {
            patients.value = response.data.data;
            paginationData.value = response.data.meta;
        } else {
            patients.value = response.data;
        }

        console.log('Pagination Data:', paginationData.value);
    } catch (err) {
        console.error('Error fetching patients:', err);
        error.value = err.response?.data?.message || 'Failed to load patients';
    } finally {
        loading.value = false;
    }
};

const openModal = (patient = null) => {
    selectedPatient.value = patient ? { ...patient } : {};
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const refreshPatients = async () => {
    await getPatients();
};

onMounted(async () => {
    // Initialize auth store first
    if (!authStore.user) {
        await authStore.getUser();
    }
    // Then get patients
    await getPatients();
});
</script>


<template>
    <div class="appointment-page">
        <div class="container-fluid">
            <div class="row ">
                <div class="col-sm-6">
                    <h1 class="m-0">Patients</h1> </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Patient</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <PatientListItem
                        :role="role"
                        :patients="patients"
                        :loading="loading"
                        :error="error"
                        :paginationData="paginationData"
                        @edit="openModal"
                        @refresh="refreshPatients"
                        @paginate="getPatients"
                    />
                </div>
            </div>
        </div>
    </div>

    <PatientModel
        :show-modal="isModalOpen"
        :spec-data="selectedPatient"
        @close="closeModal"
        @specUpdate="refreshPatients"
    />
</template>

<style scoped>
.appointment-page {
    padding: 20px;
}

/* Add styling for pagination if needed */
:deep(.pagination) {
    margin-bottom: 0;
    display: flex; /* Ensures proper alignment */
    justify-content: center; /* Center pagination */
    margin-top: 20px; /* Add some space above pagination */
}

:deep(.page-link) {
    color: #007bff;
    background-color: #fff;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem; /* Adjust padding */
    border-radius: 5px; /* Slightly rounded corners */
    margin: 0 2px; /* Small gap between pages */
    transition: all 0.2s ease-in-out;
}

:deep(.page-link:hover) {
    background-color: #e9ecef; /* Light hover background */
    border-color: #007bff;
}

:deep(.page-item.active .page-link) {
    background-color: #007bff;
    border-color: #007bff;
    color: #fff;
    box-shadow: 0 2px 5px rgba(0, 123, 255, 0.2); /* Subtle shadow for active page */
}

:deep(.page-item.disabled .page-link) {
    color: #6c757d;
    pointer-events: none;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
</style>