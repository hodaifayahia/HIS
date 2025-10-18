<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';
import { useSweetAlert } from '../../../../Components/useSweetAlert';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import { FilterMatchMode } from 'primevue/api';
import SallesListItem from './SallesListItem.vue';
import SallesModel from '../../../../Components/Apps/Configuration/Salles/SallesModel.vue';

const swal = useSweetAlert();
const toaster = useToastr();

const salles = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedSalle = ref(null);
const searchQuery = ref('');

/**
 * Computed property for filtered salles
 */
const filteredSalles = computed(() => {
    let filtered = salles.value;

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(salle =>
            salle.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            salle.number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (salle.description && salle.description.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }

    return filtered;
});

/**
 * Fetches the list of salles from the API.
 */
const getSalles = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/salles');
        salles.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error fetching salles:', err);
        error.value = err.response?.data?.message || 'Failed to load salles. Please try again.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};

/**
 * Opens the SallesModel for adding a new salle or editing an existing one.
 */
const openModal = (salle = null) => {
    selectedSalle.value = salle ? { ...salle } : {
        name: '',
        number: '',
        description: '',
        defult_specialization_id: null,
        specialization_ids: []
    };
    isModalOpen.value = true;
};

/**
 * Closes the SallesModel.
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedSalle.value = null;
};

/**
 * Handles adding a new salle to the local list.
 */
const handleSalleAdded = (newSalle) => {
    salles.value.unshift(newSalle); // Add to the beginning of the list
    closeModal();
};

/**
 * Handles updating an existing salle in the local list.
 */
const handleSalleUpdated = (updatedSalle) => {
    const index = salles.value.findIndex(s => s.id === updatedSalle.id);
    if (index !== -1) {
        salles.value[index] = updatedSalle; // Replace the old object with the updated one
    }
    closeModal();
};

/**
 * Handles the deletion of a salle.
 */
const deleteSalle = async (id) => {
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
            await axios.delete(`/api/salles/${id}`);
            // Remove the salle from the local array
            salles.value = salles.value.filter(salle => salle.id !== id);
            toaster.success('Salle deleted successfully!');
        } catch (err) {
            console.error('Error deleting salle:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete salle.';
            toaster.error(errorMessage);
            swal.fire('Error!', errorMessage, 'error');
        }
    }
};

/**
 * Handles specialization assignment updates
 */
const handleSpecializationUpdate = (updatedSalle) => {
    const index = salles.value.findIndex(s => s.id === updatedSalle.id);
    if (index !== -1) {
        salles.value[index] = updatedSalle;
    }
};

/**
 * Clears all filters
 */
const clearFilters = () => {
    searchQuery.value = '';
};

// Fetch salles when the component is mounted
onMounted(() => {
    getSalles();
});
</script>
<template>
  <div class="tw-p-6 tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-300 tw-min-h-screen">
    <div class="tw-mb-8">
      <div class="tw-flex tw-justify-between tw-items-start tw-gap-4">
        <div class="tw-flex-1">
          <h1
            class="tw-text-4xl tw-font-extrabold tw-mb-2 tw-bg-gradient-to-br tw-from-slate-900 tw-to-slate-600 tw-text-transparent tw-bg-clip-text"
          >
            Salles Management
          </h1>
          <p class="tw-text-gray-500 tw-text-base tw-m-0">
            Manage your salles and assign specializations
          </p>
        </div>
        <nav class="tw-text-sm">
          <ul class="tw-flex tw-items-center tw-list-none tw-px-4 tw-py-3 tw-m-0 tw-bg-white tw-rounded-lg tw-shadow-sm">
            <li class="tw-mr-2">
              <a href="#" class="tw-text-blue-500 hover:tw-text-blue-700">Home</a>
            </li>
            <li class="tw-text-gray-300 tw-mx-2">
              <i class="fas fa-chevron-right tw-text-xs"></i>
            </li>
            <li class="tw-mr-2">
              <a href="#" class="tw-text-blue-500 hover:tw-text-blue-700">Configuration</a>
            </li>
            <li class="tw-text-gray-300 tw-mx-2">
              <i class="fas fa-chevron-right tw-text-xs"></i>
            </li>
            <li class="tw-text-gray-500 tw-font-medium">Salles</li>
          </ul>
        </nav>
      </div>
    </div>

    <div class="tw-container tw-mx-auto tw-max-w-screen-xl">
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-md tw-overflow-hidden tw-border tw-border-gray-300">
        <div class="tw-p-8 tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-border-b tw-border-gray-300">
          <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
            <div class="tw-flex tw-items-baseline tw-gap-4">
              <h2 class="tw-text-2xl tw-font-bold tw-text-slate-900 tw-m-0">Salles List</h2>
              <span
                class="tw-text-sm tw-text-gray-500 tw-bg-gray-200 tw-px-3 tw-py-1 tw-rounded-full tw-font-medium"
              >
                {{ filteredSalles.length }} of {{ salles.length }} salles
              </span>
            </div>
            <button
              @click="openModal()"
              class="tw-inline-flex tw-items-center tw-px-6 tw-py-3 tw-bg-blue-600 tw-text-white tw-font-semibold tw-rounded-lg tw-shadow-md hover:tw-bg-blue-700 tw-transition tw-duration-200 tw-ease-in-out tw-border-none tw-cursor-pointer tw-text-sm"
            >
              <i class="fas fa-plus-circle tw-mr-2"></i>
              <span>Add New Salle</span>
            </button>
          </div>

          <div class="tw-flex tw-gap-4 tw-flex-wrap tw-items-center">
            <div class="tw-flex-1 tw-min-w-[300px]">
              <div class="tw-relative">
                <i class="fas fa-search tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400 tw-text-sm"></i>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search salles by name, number, or description..."
                  class="tw-w-full tw-pl-10 tw-pr-3 tw-py-3 tw-border-2 tw-border-gray-300 tw-rounded-lg tw-text-sm tw-bg-white tw-transition tw-duration-200 focus:tw-outline-none focus:tw-border-blue-600 focus:tw-ring-2 focus:tw-ring-blue-200"
                />
              </div>
            </div>

            <div v-if="searchQuery" class="tw-flex tw-gap-3 tw-items-center">
              <button
                @click="clearFilters"
                class="tw-inline-flex tw-items-center tw-gap-2 tw-px-4 tw-py-3 tw-bg-gray-100 tw-text-gray-600 tw-border tw-border-gray-300 tw-rounded-lg tw-text-sm tw-cursor-pointer hover:tw-bg-gray-200 hover:tw-text-gray-800"
              >
                <i class="fas fa-times"></i>
                Clear Filters
              </button>
            </div>
          </div>
        </div>

        <div v-if="loading" class="tw-text-center tw-py-16">
          <div
            class="tw-inline-block tw-w-12 tw-h-12 tw-border-4 tw-border-gray-300 tw-border-t-blue-600 tw-rounded-full tw-animate-spin"
            role="status"
          >
            <span class="tw-sr-only">Loading...</span>
          </div>
          <p class="tw-text-gray-500 tw-mt-4 tw-text-base">Loading salles...</p>
        </div>

        <div v-else-if="error" class="tw-bg-red-100 tw-border tw-border-red-300 tw-rounded-xl tw-p-6 tw-m-4 tw-flex tw-justify-between tw-items-center" role="alert">
          <div class="tw-flex tw-items-center tw-gap-4">
            <i class="fas fa-exclamation-triangle tw-text-red-600 tw-text-xl"></i>
            <div>
              <strong class="tw-font-bold tw-text-red-600">Error!</strong>
              <span class="tw-text-red-700 tw-ml-1">{{ error }}</span>
            </div>
          </div>
          <button
            @click="getSalles"
            class="tw-inline-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-bg-red-600 tw-text-white tw-rounded-md tw-cursor-pointer tw-text-sm hover:tw-bg-red-700"
          >
            <i class="fas fa-redo"></i>
            Retry
          </button>
        </div>

        <div v-else-if="filteredSalles.length > 0" class="tw-overflow-x-auto">
          <table class="tw-w-full tw-border-collapse tw-text-sm tw-text-gray-600">
            <thead>
              <tr class="tw-bg-gradient-to-br tw-from-gray-200 tw-to-gray-300 tw-border-b-2 tw-border-gray-400">
                <th class="tw-text-left tw-font-semibold tw-uppercase tw-px-6 tw-py-4 tw-text-xs tw-tracking-wider">#</th>
                <th class="tw-text-left tw-font-semibold tw-uppercase tw-px-6 tw-py-4 tw-text-xs tw-tracking-wider">Name</th>
                <th class="tw-text-left tw-font-semibold tw-uppercase tw-px-6 tw-py-4 tw-text-xs tw-tracking-wider">Number</th>
                <th class="tw-text-left tw-font-semibold tw-uppercase tw-px-6 tw-py-4 tw-text-xs tw-tracking-wider">Default Specialization</th>
                <th class="tw-text-left tw-font-semibold tw-uppercase tw-px-6 tw-py-4 tw-text-xs tw-tracking-wider">Assigned Specializations</th>
                <th class="tw-text-left tw-font-semibold tw-uppercase tw-px-6 tw-py-4 tw-text-xs tw-tracking-wider">Description</th>
                <th class="tw-text-center tw-font-semibold tw-uppercase tw-px-6 tw-py-4 tw-text-xs tw-tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody>
              <SallesListItem
                v-for="(salle, index) in filteredSalles"
                :key="salle.id"
                :salle="salle"
                :index="index"
                @edit="openModal"
                @delete="deleteSalle"
                @specialization-updated="handleSpecializationUpdate"
              />
            </tbody>
          </table>
        </div>

        <div v-else class="tw-py-16 tw-text-center tw-max-w-md tw-mx-auto">
          <i class="fas fa-door-open tw-text-6xl tw-text-gray-300 tw-mb-6"></i>
          <h3 class="tw-text-xl tw-font-semibold tw-text-gray-700 tw-mb-2">
            {{ searchQuery ? 'No salles match your search' : 'No salles found' }}
          </h3>
          <p class="tw-text-gray-500 tw-mb-8 tw-leading-relaxed">
            {{ searchQuery ? 'Try adjusting your search terms' : 'Click "Add New Salle" to get started!' }}
          </p>
          <div class="tw-flex tw-gap-4 tw-justify-center tw-flex-wrap">
            <button
              v-if="searchQuery"
              @click="clearFilters"
              class="tw-inline-flex tw-items-center tw-gap-2 tw-px-4 tw-py-3 tw-bg-gray-100 tw-text-gray-600 tw-border tw-border-gray-300 tw-rounded-lg tw-text-sm tw-cursor-pointer hover:tw-bg-gray-200 hover:tw-text-gray-800"
            >
              Clear Search
            </button>
            <button
              @click="openModal()"
              class="tw-inline-flex tw-items-center tw-px-6 tw-py-3 tw-bg-blue-600 tw-text-white tw-font-semibold tw-rounded-lg hover:tw-bg-blue-700 tw-shadow-md tw-transition tw-duration-200 tw-ease-in-out tw-cursor-pointer tw-text-sm"
            >
              <i class="fas fa-plus-circle tw-mr-2"></i>
              Add New Salle
            </button>
          </div>
        </div>
      </div>
    </div>

    <SallesModel
      :show-modal="isModalOpen"
      :salle-data="selectedSalle"
      @close="closeModal"
      @salle-updated="handleSalleUpdated"
      @salle-added="handleSalleAdded"
    />
  </div>
</template>
