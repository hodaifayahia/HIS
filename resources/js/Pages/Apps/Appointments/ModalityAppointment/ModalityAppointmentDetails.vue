<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { modalityAppointmentServices } from '../../../../Components/Apps/services/modality/ModalityAppointment'; // Import the service
import ModalityDetailsHeader from '../../../../Components/Apps/Appointments/ModalityAppointment/ModalityDetailsHeader.vue'; // Import the header component
import AppointmentListItem from './ModalityAppointmentListItem.vue';
// Import the modal component for adding/editing appointments
import  {useSweetAlert}  from '../../../../Components/useSweetAlert';
import AppointmentModalityModel from '../../../../Components/Apps/Appointments/ModalityAppointment/AppointmentModalityModel.vue'; // Import the modal component
// PrimeVue Components
import ImportModalityAppointmentsModal from '../../../../Components/Apps/Appointments/ModalityAppointment/ImportModalityAppointmentsModal.vue';

import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';
import Paginator from 'primevue/paginator';
import Dropdown from 'primevue/dropdown';

// Local refs for UI state
const swal = useSweetAlert();

const appointments = ref([]);
const pagination = ref({
    currentPage: 1,
    perPage: 30,
    total: 0,
    lastPage: 1
});
const loading = ref(false);
const error = ref(null);
const currentFilterStatus = ref(null);
const todaysAppointmentsCount = ref(0);
const availableStatuses = ref([
    { name: 'All', value: null, color: 'secondary', icon: 'pi pi-list' },
    { name: 'Scheduled', value: 0, color: 'primary', icon: 'pi pi-clock' },
    { name: 'Confirmed', value: 1, color: 'success', icon: 'pi pi-check' },
    { name: 'Canceled', value: 2, color: 'danger', icon: 'pi pi-times' },
    { name: 'Pending', value: 3, color: 'warning', icon: 'pi pi-hourglass' },
    { name: 'Done', value: 4, color: 'info', icon: 'pi pi-check-circle' },
    { name: 'OnWorking', value: 5, color: 'help', icon: 'pi pi-cog' }
]);
const selectedStatusFilter = ref(availableStatuses.value[0]);

// Reactive variables for route params
const route = useRoute();
const router = useRouter();
const modalityId = ref(route.params.id);
const specializationId = ref(route.params.specialization_id || ''); // Default to empty if not provided

// Ref to control the visibility of the AppointmentModalityModel
const showAppointmentModal = ref(false);
const showEditAppointmentModal = ref(false);
const editingAppointment = ref(null);

// Add import modal state
const showImportModal = ref(false);

// Function to open the appointment modal
const openAddAppointmentModal = () => {
    showAppointmentModal.value = true;
};

// Function to close the appointment modal
const closeAddAppointmentModal = () => {
    showAppointmentModal.value = false;
    showEditAppointmentModal.value = false;
    editingAppointment.value = null;
};

// Function to open the import modal
const openImportModal = () => {
    showImportModal.value = true;
};

// Function to close the import modal
const closeImportModal = () => {
    showImportModal.value = false;
};

// --- Component Methods ---

// Fetch appointments (updated to accept all filter parameters)
const getAppointments = async (page = 1, status = null, dateFilter = null, searchQuery = null) => {
    loading.value = true;
    error.value = null;

    try {
        const params = {
            modality_id: modalityId.value,
            page,
        };

        if (status !== null) {
            params.status = status;
        }
        if (dateFilter !== null) {
            params.date = dateFilter;
        }
        if (searchQuery !== null && searchQuery !== '') {
            params.search = searchQuery;
        }

        console.log('Fetching appointments with params:', params);

        const response = await modalityAppointmentServices.getAll(params);

        if (response.success) {
            // FIX: Use response.data.data for the actual appointments array
            appointments.value = Array.isArray(response.data.data)
                ? response.data.data
                : response.data; // fallback for non-paginated
            console.log('Response from getAll:', appointments.value);

            pagination.value = {
                currentPage: response.meta?.current_page || 1,
                perPage: response.meta?.per_page || 30,
                total: response.meta?.total || 0,
                lastPage: response.meta?.last_page || 1,
            };
        } else {
            error.value = response.message;
        }
    } catch (err) {
        console.error('Error fetching appointments:', err);
        error.value = 'Failed to fetch appointments. Please try again.';
    } finally {
        loading.value = false;
    }
};


// Fetch today's appointments count
const getTodaysAppointmentsCount = async () => {
    loading.value = true;
    error.value = null;

    try {
        const params = {
            modality_id: modalityId.value,
            filter: 'today'
        };

        console.log('Fetching today\'s appointments count with params:', params);

        const response = await modalityAppointmentServices.getAll(params);

        if (response.success) {
            todaysAppointmentsCount.value = response.data.data ? response.data.data.length : 0;
        } else {
            error.value = response.message;
        }
    } catch (err) {
        console.error('Error fetching today\'s appointments count:', err);
        error.value = 'Failed to fetch today\'s appointments count. Please try again.';
    } finally {
        loading.value = false;
    }
};

// Handle status filter change (from dropdown)
const onStatusFilterChange = () => {
    currentFilterStatus.value = selectedStatusFilter.value ? selectedStatusFilter.value.value : null;
    getAppointments(1, currentFilterStatus.value); // Pass the new filter directly
};

// Handle events from ModalityAppointmentListItem or AppointmentModalityModel
const handleUpdateAppointment = () => {
    getAppointments(pagination.value.currentPage, currentFilterStatus.value);
    getTodaysAppointmentsCount();
    closeAddAppointmentModal(); // Close the modal after update/creation
};

const handleUpdateStatus = () => {
    getAppointments(pagination.value.currentPage, currentFilterStatus.value);
    getTodaysAppointmentsCount(); // Re-fetch today's count as well
};

const handleFilterByDate = (date) => {
    getAppointments(1, currentFilterStatus.value, date, null); // Pass date filter
}

const handleSearch = (query) => {
    getAppointments(1, currentFilterStatus.value, null, query); // Pass search query
}

// Add this function to handle edit appointment
const handleEditAppointment = (appointment) => {
    editingAppointment.value = appointment;
    showEditAppointmentModal.value = true;
}

// Add this function to handle local edit
const handleEditAppointmentSave = (updatedAppointment) => {
    const index = appointments.value.findIndex(a => a.id === updatedAppointment.id);
    if (index !== -1) {
        appointments.value[index] = updatedAppointment;
    }
    showEditAppointmentModal.value = false;
    editingAppointment.value = null;
}

const handleDeleteAppointment = async (appointmentId) => {
    try {
        // Show SweetAlert confirmation dialog using the configured swal instance
        const result = await swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        // If user confirms, proceed with deletion
        if (result.isConfirmed) {
            appointments.value = appointments.value.filter(a => a.id !== appointmentId);

            // Show success message
            await swal.fire(
                'Deleted!',
                'Appointment has been deleted.',
                'success'
            );
        }
    } catch (error) {
        await swal.fire(
            'Error!',
            'Failed to delete appointment.',
            'error'
        );
    }
};

// Handle import completion
const handleImportCompleted = (importData) => {
    closeImportModal();
    // Refresh appointments after import
    getAppointments(1);
    getTodaysAppointmentsCount();
};

// --- Lifecycle Hooks and Watchers ---

onMounted(() => {
    getAppointments(1);
    getTodaysAppointmentsCount();
});

watch(
    () => route.params.id,
    (newModalityId) => {
        if (newModalityId && newModalityId !== modalityId.value) {
            modalityId.value = newModalityId;
            getAppointments(1);
            getTodaysAppointmentsCount();
        }
    }
);
</script>

<template>
    <div class="appointment-page">
        <div class="pb-2">
            <ModalityDetailsHeader v-if="modalityId" :isDoctor="false" :modalityId="modalityId" />
        </div>

        <div class="p-2">
            <h1>Modality Appointments</h1>
        </div>

        <div class="card p-4">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <Button @click="openAddAppointmentModal()" icon="pi pi-plus" label="Add Appointment"
                        class="p-button-primary p-button-rounded" />
                    
                    <Button @click="openImportModal()" icon="pi pi-upload" label="Import Appointments"
                        class="p-button-secondary p-button-rounded" />
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <Button
                        @click="getAppointments(1, 'today'); currentFilterStatus = 'today'"
                        :class="['p-button-sm p-button-rounded', currentFilterStatus === 'today' ? 'p-button-info' : 'p-button-outlined p-button-info']"
                        aria-label="Today's Appointments"
                    >
                        <i class="pi pi-calendar-today me-1"></i>
                        Today's Appointments
                        <Tag :value="todaysAppointmentsCount"
                            :severity="currentFilterStatus === 'today' ? 'contrast' : 'info'" class="ml-1" />
                    </Button>

                    <Dropdown v-model="selectedStatusFilter" :options="availableStatuses" optionLabel="name"
                        placeholder="Filter by Status" class="w-full md:w-14rem" @change="onStatusFilterChange" />
                </div>
            </div>

            <ProgressSpinner v-if="loading" style="width: 50px; height: 50px" strokeWidth="8" fill="var(--surface-ground)"
                animationDuration=".5s" aria-label="Loading..." />

            <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

            <div v-else-if="appointments.length > 0">
                <AppointmentListItem
                    :appointments="appointments"
                    :modality-id="modalityId"
                    @update-appointment="handleUpdateAppointment"
                    @update-status="handleUpdateStatus"
                    @filterByDate="handleFilterByDate"
                    @search="handleSearch"
                    @editAppointment="handleEditAppointment"
                    @editAppointmentSave="handleEditAppointmentSave" 
                    @deleteAppointment="handleDeleteAppointment" 
                />

                <div style="display: flex; justify-content: center; margin-top: 1rem;">
                    <Paginator
                        :rows="pagination.perPage"
                        :totalRecords="pagination.total"
                        :first="(pagination.currentPage - 1) * pagination.perPage"
                        @page="event => getAppointments(event.page + 1, currentFilterStatus)"
                        :template="{
                            'default': 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport'
                        }"
                        :currentPageReportTemplate="`Showing {first}-{last} of {totalRecords} records`"
                    ></Paginator>
                </div>
            </div>

            <Message v-else severity="info" :closable="false">No appointments found for this modality.</Message>

            <AppointmentModalityModel
                v-if="showAppointmentModal"
                :visible="showAppointmentModal"
                :modality-id="modalityId"
                :specialization_id="specializationId"
                @update-appointment="handleUpdateAppointment"
                @update-status="handleUpdateStatus"
                @filterByDate="handleFilterByDate"
                @search="handleSearch"
                @close="closeAddAppointmentModal"
            />

            <!-- Add this for editing appointments -->
            <AppointmentModalityModel
                v-if="showEditAppointmentModal"
                :visible="showEditAppointmentModal"
                :modality-id="modalityId"
                :appointment-id="editingAppointment?.id"
                :edit-mode="true"
                @save="handleEditAppointmentSave" 
                @close="closeAddAppointmentModal"
            />

            <!-- Import Modal -->
            <ImportModalityAppointmentsModal
                v-if="showImportModal"
                :visible="showImportModal"
                :modality-id="modalityId"
                @close="closeImportModal"
                @imported="handleImportCompleted"
            />
        </div>
    </div>
</template>

<style scoped>
/* Scoped styles specific to this component */
.appointment-page {
    padding: 1rem;
}

/* Custom button styles to match your design system if Aura is not enough */
.p-button.p-button-info {
    background-color: var(--blue-500);
    border-color: var(--blue-500);
    color: var(--surface-0);
}
.p-button.p-button-outlined.p-button-info {
    color: var(--blue-500);
    border-color: var(--blue-500);
    background-color: transparent;
}
.p-button.p-button-outlined.p-button-info:hover {
    background-color: var(--blue-50);
}

.p-tag.p-tag-info {
    background-color: var(--blue-500);
    color: var(--surface-0);
}

.p-tag.p-tag-contrast {
    background-color: var(--surface-900);
    color: var(--surface-0);
}
/* General PrimeVue overrides/additions if needed */
.p-button.p-button-rounded {
    border-radius: 2rem; /* Example, adjust as needed */
}

/* Margin/padding utilities for internal spacing without PrimeFlex */
.mb-4 { margin-bottom: 1.5rem; }
.gap-3 { gap: 1rem; }
.gap-2 { gap: 0.5rem; }
.flex-wrap { flex-wrap: wrap; }
.justify-between { justify-content: space-between; }
.align-center { align-items: center; }
.ml-1 { margin-left: 0.25rem; }
.mr-1 { margin-right: 0.25rem; }
.mt-4 { margin-top: 1.5rem; }

/* Adjust dropdown width if necessary */
.w-full { width: 100%; }
.md\:w-14rem {
    min-width: 14rem; /* Example width for medium screens */
}
</style>