<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
// Import PrimeVue components
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Calendar from 'primevue/calendar';
import ProgressSpinner from 'primevue/progressspinner';
import Avatar from 'primevue/avatar';
import Message from 'primevue/message';
import Tooltip from 'primevue/tooltip';

import AnnexFormModal from '../models/AnnexFormModal.vue';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

const props = defineProps({
    contractState: String,
    contractId: String
});

const router = useRouter();
const toast = useToast();

// Search and filter state
const searchQuery = ref("");
const selectedFilter = ref({ label: "By Name", value: "annex_name" });
const filterOptions = [
    { label: "By ID", value: "id" },
    { label: "By Name", value: "annex_name" },
    { label: "By Creation time", value: "created_at" },
    { label: "By Service Name", value: "service_name" },
];

const loading = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);

const items = ref([]); // Stores all annexes for the current contract
const services = ref([]); // All available services

// This computed property is correct and reactive to `items.value`
const usedServiceIds = computed(() => {
    return items.value.map(item => item.service_id).filter(id => id !== null);
});

// Modal Visibility and Form State
const showFormModal = ref(false);
const showDeleteConfirmModal = ref(false);
const isEditingMode = ref(false);
const currentForm = ref({
    id: null,
    contract_id: '',
    annex_name: '',
    service_id: null,
    description: '',
    min_price: 0,
    prestation_prix_status: '',
});
const itemToDelete = ref(null);

// Pagination states
const first = ref(0);
const rows = ref(8);

const filteredItemsComputed = computed(() => {
    if (!searchQuery.value) return items.value;

    const query = String(searchQuery.value).toLowerCase();
    const filterValue = selectedFilter.value.value;

    return items.value.filter(item => {
        switch (filterValue) {
            case "id":
                return item.id && String(item.id).includes(query);
            case "annex_name":
                return item.annex_name && String(item.annex_name).toLowerCase().includes(query);
            case "created_at":
                const searchDateFormatted = searchQuery.value instanceof Date
                    ? formatDateDisplay(searchQuery.value)
                    : query;
                return item.created_at && formatDateDisplay(item.created_at).includes(searchDateFormatted);
            case "service_name":
                return item.service_name && String(item.service_name).toLowerCase().includes(query);
            default:
                return true;
        }
    });
});

const formatDateDisplay = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear());
        return `${day}/${month}/${year}`;
    } catch (error) {
        console.error("Error formatting date:", error);
        return dateString;
    }
};

const capitalizeFirstLetter = (string) => {
    if (!string) return '';
    return String(string).charAt(0).toUpperCase() + String(string).slice(1);
};

// Fetch annexes for the contract
const fetchAnnexes = async () => {
    if (!props.contractId) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Contract ID is missing', life: 3000 });
        return;
    }

    try {
        loading.value = true;
        const response = await axios.get(`/api/annex/contract/${props.contractId}`);

        if (response.data.success) {
            items.value = response.data.data;
        } else {
            items.value = [];
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load annexes', life: 3000 });
        }
    } catch (error) {
        console.error("Error fetching annexes:", error);
        const errorMessage = error.response?.data?.message || 'Failed to load annexes';
        toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
    } finally {
        loading.value = false;
    }
};

// Fetch available services (all services)
const fetchServices = async () => {
    try {
        const response = await axios.get(`/api/services`);
        services.value = response.data.data || response.data;
        if (services.value.length === 0) {
            toast.add({ severity: 'warn', summary: 'Warning', detail: 'No available services found.', life: 3000 });
        }
    } catch (error) {
        console.error("Error fetching services:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load services for form.', life: 3000 });
    }
};

// Open unified form modal for adding
const openAddFormModal = async () => {
    isEditingMode.value = false;
    // Reset form for adding
    Object.assign(currentForm.value, {
        id: null,
        contract_id: props.contractId,
        annex_name: "",
        service_id: null, // Ensure service_id is null for new additions
        description: "",
        min_price: 0,
        prestation_prix_status: "empty"
    });
    await fetchServices(); // Always fetch services before opening to get the latest list
    showFormModal.value = true;
};

// Open unified form modal for editing
const openEditFormModal = async (item) => {
    isEditingMode.value = true;
    Object.assign(currentForm.value, {
        id: item.id,
        contract_id: item.contract_id,
        annex_name: item.annex_name,
        service_id: item.service_id,
        description: item.description || '',
        min_price: item.min_price || 0,
        prestation_prix_status: item.prestation_prix_status || 'empty'
    });
    await fetchServices(); // Always fetch services before opening
    showFormModal.value = true;
};

// Open delete confirmation modal
const openDeleteConfirmModal = (item) => {
    itemToDelete.value = item;
    showDeleteConfirmModal.value = true;
};

// Function to handle the 'save' event from AnnexFormModal
const handleFormSave = async (formDataPayload) => {
    isSaving.value = true;
    try {
        const formData = new FormData();
        formData.append('annex_name', formDataPayload.annex_name);
        formData.append('service_id', formDataPayload.service_id);
        formData.append('min_price', formDataPayload.min_price);
        formData.append('prestation_prix_status', formDataPayload.prestation_prix_status);

        if (formDataPayload.description) {
            formData.append('description', formDataPayload.description);
        }

        let url = '';
        let method = '';

        if (isEditingMode.value) {
            url = `/api/annex/${formDataPayload.id}`;
            method = 'post'; // Use POST for FormData with _method=PUT for Laravel
            formData.append('_method', 'PUT');
        } else {
            url = `/api/annex/${props.contractId}`; // Route for adding new annexes to a contract
            method = 'post';
        }

        const response = await axios({
            method: method,
            url: url,
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            toast.add({ severity: 'success', summary: 'Success', detail: `Annex ${isEditingMode.value ? 'updated' : 'added'} successfully`, life: 3000 });
            await fetchAnnexes(); // Re-fetch annexes to update the `items.value` AND `usedServiceIds`
            showFormModal.value = false;
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: response.data.message || 'Operation failed', life: 3000 });
        }
    } catch (error) {
        console.error("Error saving annex:", error);
        if (error.response && error.response.data) {
            if (error.response.data.errors) {
                for (const field in error.response.data.errors) {
                    error.response.data.errors[field].forEach(message => toast.add({ severity: 'error', summary: 'Validation Error', detail: message, life: 5000 }));
                }
            } else {
                toast.add({ severity: 'error', summary: 'Error', detail: error.response.data.message || `Failed to ${isEditingMode.value ? 'update' : 'save'} annex`, life: 3000 });
            }
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: `Failed to ${isEditingMode.value ? 'update' : 'save'} annex: ${error.message}`, life: 3000 });
        }
    } finally {
        isSaving.value = false;
    }
};

// Handle delete confirmation
const handleDeleteConfirm = async () => {
    isDeleting.value = true;
    try {
        // First check if annex has any prestation pricing
        // This check is important as it prevents deleting an annex if it's tied to other data.
        const checkResponse = await axios.get(`/api/annex/${itemToDelete.value.id}/check-relations`);

        if (checkResponse.data.hasPrestationPricing) {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Cannot delete annex: It has associated prestation pricing records.', life: 5000 });
            showDeleteConfirmModal.value = false;
            return;
        }

        const response = await axios.delete(`/api/annex/${itemToDelete.value.id}`);

        if (response.data.success) {
            toast.add({ severity: 'success', summary: 'Success', detail: 'Annex deleted successfully', life: 3000 });
            await fetchAnnexes(); // Re-fetch annexes to update the `items.value` AND `usedServiceIds`
            showDeleteConfirmModal.value = false;
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: response.data.message || 'Failed to delete annex', life: 3000 });
        }
    } catch (error) {
        console.error("Error deleting annex:", error);
        const errorMessage = error.response?.data?.message ||
            (error.response?.data?.error?.includes('foreign key constraint')
                ? 'Cannot delete annex: It has associated records.'
                : 'Failed to delete annex');
        toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 });
    } finally {
        isDeleting.value = false;
        itemToDelete.value = null;
    }
};

// Function to handle navigation to details page
const viewAnnexDetails = (id) => {
    router.push({
        name: 'convention.annex.details',
        params: { id: id }
    });
};

// DataTable pagination handler
const onPage = (event) => {
    first.value = event.first;
    rows.value = event.rows;
};

// Initial data fetch
onMounted(() => {
    fetchAnnexes();
    // fetchServices is now called before opening the modal, not on mount,
    // to ensure the freshest list when needed.
});
</script>

<template>
    <div class="surface-card border-round-lg shadow-2 p-4">
        <Toast />

        <div class="flex flex-column sm:flex-row justify-content-between align-items-start sm:align-items-center mb-4 gap-3">
            <div class="d-flex gap-2 w-full">
                <div class="d-flex gap-2 pl-2 mr-2 flex-grow-1">
                    <Dropdown
                        v-model="selectedFilter"
                        :options="filterOptions"
                        optionLabel="label"
                        placeholder="Select a Filter"
                        class="p-inputtext-sm mr-2"
                        style="min-width: 150px;"
                    />

                    <InputText
                        v-if="selectedFilter && selectedFilter.value !== 'created_at'"
                        type="text"
                        v-model="searchQuery"
                        placeholder="Search..."
                        class="flex-grow-1 w-full p-inputtext-sm"
                    />

                    <Calendar
                        v-if="selectedFilter && selectedFilter.value === 'created_at'"
                        v-model="searchQuery"
                        dateFormat="dd/mm/yy"
                        placeholder="Select Date"
                        class="flex-grow-1 p-inputtext-sm"
                    />
                </div>

                <Button
                    v-if="props.contractState === 'pending'"
                    label="Add Annex"
                    icon="pi pi-plus"
                    class="p-button-primary border-round-md white-space-nowrap"
                    @click="openAddFormModal()"
                />
            </div>
        </div>

        <div class="surface-card border-round-lg border-1 border-300 overflow-hidden">
            <div v-if="loading" class="flex flex-column align-items-center justify-content-center py-6 px-4">
                <ProgressSpinner
                    style="width: 50px; height: 50px"
                    strokeWidth="6"
                    fill="transparent"
                    animationDuration=".8s"
                    aria-label="Loading"
                />
                <p class="mt-3 text-600 font-medium">Loading annexes...</p>
            </div>

            <div
                v-else-if="filteredItemsComputed.length === 0"
                class="flex flex-column justify-content-center align-items-center py-8 px-4 text-center"
            >
                <div class="bg-blue-50 border-circle w-5rem h-5rem flex align-items-center justify-content-center mb-3">
                    <i class="pi pi-folder-open text-3xl text-blue-400"></i>
                </div>
                <h6 class="text-900 font-semibold mb-2">No annexes found</h6>
                <p class="text-600 mb-0">Try adjusting your search criteria or add a new annex.</p>
            </div>

            <div v-else class="overflow-auto">
                <DataTable
                    :value="filteredItemsComputed"
                    :paginator="true"
                    :rows="rows"
                    :first="first"
                    @page="onPage"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                    :class="{ 'p-datatable-striped': true }"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                >
                    <Column field="id" header="ID" sortable class="white-space-nowrap">
                        <template #body="slotProps">
                            <span class="font-mono text-sm">#{{ slotProps.data.id }}</span>
                        </template>
                    </Column>

                    <Column field="annex_name" header="Name" sortable>
                        <template #body="slotProps">
                            <span class="font-semibold text-900">{{ slotProps.data.annex_name }}</span>
                        </template>
                    </Column>

                    <Column field="service_name" header="Service" sortable>
                        <template #body="slotProps">
                            <span class="text-sm">{{ slotProps.data.service_name || 'N/A' }}</span>
                        </template>
                    </Column>

                    <Column field="created_by" header="Created By" sortable>
                        <template #body="slotProps">
                            <div class="flex align-items-center gap-2">
                                <Avatar
                                    :label="slotProps.data.created_by.charAt(0).toUpperCase()"
                                    class="bg-primary text-primary-contrast"
                                    size="small"
                                    shape="circle"
                                />
                                <span class="text-sm">{{ slotProps.data.created_by }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="created_at" header="Created At" sortable>
                        <template #body="slotProps">
                            <span class="text-sm text-600">{{ formatDateDisplay(slotProps.data.created_at) }}</span>
                        </template>
                    </Column>

                    <Column field="min_price" header="Min Price" sortable>
                        <template #body="slotProps">
                            <span class="font-semibold text-orange-600">DZD {{ slotProps.data.min_price }}</span>
                        </template>
                    </Column>

                    <Column v-if="props.contractState === 'pending'" header="Actions" class="white-space-nowrap">
                        <template #body="slotProps">
                            <div class="flex gap-1">
                                <Button
                                    icon="pi pi-info-circle"
                                    class="p-button-sm p-button-text p-button-info border-round-md"
                                    @click="viewAnnexDetails(slotProps.data.id)"
                                    v-tooltip.top="'View Details'"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    class="p-button-sm p-button-text p-button-warning border-round-md"
                                    @click="openEditFormModal(slotProps.data)"
                                    v-tooltip.top="'Edit'"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    class="p-button-sm p-button-text p-button-danger border-round-md"
                                    @click="openDeleteConfirmModal(slotProps.data)"
                                    v-tooltip.top="'Delete'"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column v-else header="Actions" class="white-space-nowrap">
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-info-circle"
                                class="p-button-sm p-button-text p-button-info border-round-md"
                                @click="viewAnnexDetails(slotProps.data.id)"
                                v-tooltip.top="'View Details'"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <AnnexFormModal
            :maxAllowedShare="maxAmount"
            :currentCompanyShare="currentShare"
            :show-modal="showFormModal"
            :is-editing="isEditingMode"
            :form-data="currentForm"
            :services="services"
            :used-service-ids="usedServiceIds"
            :is-loading="isSaving"
            @save="handleFormSave"
            @close-modal="showFormModal = false"
        />

        <Dialog
            v-model:visible="showDeleteConfirmModal"
            modal
            header="Confirm Delete"
            :style="{ width: '400px' }"
            class="border-round-lg"
        >
            <div class="flex flex-column align-items-center text-center p-3">
                <div class="bg-red-50 border-circle w-4rem h-4rem flex align-items-center justify-content-center mb-3">
                    <i class="pi pi-exclamation-triangle text-2xl text-red-500" />
                </div>
                <h6 class="text-900 font-semibold mb-2">Delete Annex</h6>
                <p class="text-600 mb-3">
                    Are you sure you want to delete
                    <strong class="text-900">"{{ itemToDelete?.annex_name }}"</strong>?
                </p>
                <Message
                    severity="warn"
                    :closable="false"
                    class="w-full border-round-md"
                >
                    <span class="text-sm">This action cannot be undone.</span>
                </Message>
            </div>

            <template #footer>
                <div class="flex justify-content-end gap-2 p-3">
                    <Button
                        label="Cancel"
                        icon="pi pi-times"
                        class="p-button-text border-round-md"
                        @click="showDeleteConfirmModal = false"
                        :disabled="isDeleting"
                    />
                    <Button
                        label="Delete"
                        icon="pi pi-check"
                        class="p-button-danger border-round-md"
                        @click="handleDeleteConfirm"
                        :loading="isDeleting"
                    />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
/* Your existing styles */
.surface-card {
    background: var(--surface-card);
    transition: all 0.2s ease-in-out;
}

/* Custom scrollbar for data table */
.overflow-auto::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: var(--surface-100);
    border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: var(--surface-300);
    border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: var(--surface-400);
}

/* Improved table styling */
:deep(.p-datatable .p-datatable-header) {
    background: var(--surface-50);
    border-bottom: 1px solid var(--surface-200);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: var(--surface-50);
    border-bottom: 1px solid var(--surface-200);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-color-secondary);
    padding: 1rem 0.75rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.875rem 0.75rem;
    border-bottom: 1px solid var(--surface-100);
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: var(--surface-50);
}

/* Button hover effects */
:deep(.p-button:hover) {
    transform: translateY(-1px);
    transition: all 0.2s ease-in-out;
}

/* Improved spacing for mobile */
@media (max-width: 576px) {
    .surface-card {
        margin: 0 -0.5rem;
        border-radius: 0;
    }

    /* Stack the search controls and button vertically on mobile */
    .flex.gap-2.w-full {
        flex-direction: column;
    }

    .flex.gap-2.flex-grow-1 {
        width: 100%;
    }
}

/* Loading animation enhancement */
:deep(.p-progress-spinner-circle) {
    stroke: var(--primary-color);
    stroke-linecap: round;
}
</style>