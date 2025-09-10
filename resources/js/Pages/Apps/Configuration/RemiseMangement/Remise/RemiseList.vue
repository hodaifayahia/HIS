<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import ConfirmDialog from 'primevue/confirmdialog'
import RemiseForm from '../../../../../Components/Apps/Configuration/RemiseMangement/Remise/RemiseForm.vue'
import RemiseView from '../../../../../Components/Apps/Configuration/RemiseMangement/Remise/RemiseView.vue'
import { remiseService } from '../../../../../Components/Apps/services/Remise/remiseService'
import { userService } from '../../../../../Components/Apps/services/User/userService'
import { prestationService } from '../../../../../Components/Apps/services/Prestation/prestationService'
import PageHeader from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/PageHeader.vue';


// Reactive data
const remises = ref([])
const selectedRemise = ref(null)
const showDialog = ref(false)
const showViewDialog = ref(false)
const dialogMode = ref('add') // 'add' or 'edit'
const loading = ref(false)
const saving = ref(false)
const totalRecords = ref(0)
const globalFilter = ref('')
const availableUsers = ref([])
const availablePrestations = ref([])
const error = ref(null); // Added error ref

// PrimeVue DataTable filters setup
const filters = ref({
    global: { value: null, matchMode: 'contains' },
});


// Composables
const toast = useToast()
const confirm = useConfirm()

// Methods
const loadRemises = async (page = 0, size = 10) => {
    loading.value = true
    error.value = null

    try {
        const result = await remiseService.getAll({
            page: page + 1,
            size,
            search: globalFilter.value // Pass the global filter value to the API
        })

        if (result.success) {
            remises.value = result.data
            // Assuming your API returns total records for pagination
            totalRecords.value = result.meta?.total || result.data.length // Adjust based on your API response structure
        } else {
            error.value = result.message
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: result.message,
                life: 3000
            })
        }
    } catch (err) {
        console.error('Unexpected error loading remises:', err)
        error.value = 'An unexpected error occurred'
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An unexpected error occurred while loading remises.',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}


const loadUsers = async () => {
    try {
        const result = await userService.getAll()
        if (result.success) {
            availableUsers.value = result.data.map(user => ({
                label: `${user.name} (${user.email})`,
                value: user.id
            }))
        } else {
            console.error('Failed to load users:', result.message)
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to load users for selection.',
                life: 3000
            })
        }
    } catch (error) {
        console.error('Unexpected error loading users:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An unexpected error occurred while loading users.',
            life: 3000
        })
    }
}


const loadPrestations = async () => {
    try {
        const result = await prestationService.getAll()
        if (result.success) {
            availablePrestations.value = result.data.map(prestation => ({
                label: prestation.name,
                value: prestation.id
            }))
        } else {
            console.error('Failed to load prestations:', result.message)
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to load prestations for selection.',
                life: 3000
            })
        }
    } catch (error) {
        console.error('Unexpected error loading prestations:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An unexpected error occurred while loading prestations.',
            life: 3000
        })
    }
}


const handleSave = async (remiseData) => {
    saving.value = true

    try {
        let result

        if (dialogMode.value === 'add') {
            result = await remiseService.create(remiseData)
            if (result.success) {
                // Add the new item to the array
                remises.value.push(result.data);
            }
        } else {
            result = await remiseService.update(selectedRemise.value.id, remiseData)
            if (result.success) {
                // Find and update the item in the array
                const index = remises.value.findIndex(r => r.id === result.data.id);
                if (index !== -1) {
                    // Update reactivity by creating a new object or using Vue.set
                    remises.value[index] = { ...result.data };
                }
            }
        }

        if (result.success) {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `Remise ${dialogMode.value === 'add' ? 'created' : 'updated'} successfully`,
                life: 3000
            })

            closeDialog()
            // If the current filters/pagination might hide the new/updated item,
            // you might still consider a partial reload or smart re-filtering if API supports it.
            // For now, with client-side add/edit, we assume the item remains visible if it matches filters.
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: result.message,
                life: 3000
            })
        }
    } catch (error) {
        console.error('Unexpected error saving remise:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An unexpected error occurred while saving the remise.',
            life: 3000
        })
    } finally {
        saving.value = false
    }
}


const deleteRemise = async (id) => {
    try {
        const result = await remiseService.delete(id)

        if (result.success) {
            // Remove the item from the array
            remises.value = remises.value.filter(remise => remise.id !== id);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Remise deleted successfully',
                life: 3000
            })
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: result.message,
                life: 3000
            })
        }
    } catch (error) {
        console.error('Unexpected error deleting remise:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An unexpected error occurred while deleting the remise.',
            life: 3000
        })
    }
}


const openAddDialog = () => {
    selectedRemise.value = {
        name: '',
        description: '',
        code: '',
        type: 'fixed',
        amount: null,
        percentage: null,
        is_active: true,
        user_ids: [],
        prestation_ids: []
    }
    dialogMode.value = 'add'
    showDialog.value = true
}


const editRemise = (remise) => {
    selectedRemise.value = {
        ...remise,
        user_ids: remise.users ? remise.users.map(user => user.id) : [],
        prestation_ids: remise.prestations ? remise.prestations.map(prestation => prestation.id) : []
    }
    dialogMode.value = 'edit'
    showDialog.value = true
}


const viewRemise = (remise) => {
    selectedRemise.value = remise
    showViewDialog.value = true
}


const confirmDelete = (remise) => {
    confirm.require({
        message: `Are you sure you want to delete the remise "${remise.name}"?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        accept: () => deleteRemise(remise.id),
        rejectClass: 'p-button-secondary p-button-outlined'
    })
}


const closeDialog = () => {
    showDialog.value = false
    selectedRemise.value = null
}


const onPage = (event) => {
    loadRemises(event.page, event.rows)
}


const onGlobalFilter = () => {
    // When global filter changes, update the filters object and reload remises from the first page
    filters.value.global.value = globalFilter.value;
    loadRemises(0, 10);
}


const formatCurrency = (value) => {
    // Locale for Algeria (fr-DZ) and currency DZD
    return new Intl.NumberFormat('fr-DZ', {
        style: 'currency',
        currency: 'DZD'
    }).format(value || 0)
}


// Lifecycle
onMounted(async () => {
    await Promise.all([
        loadRemises(),
        loadUsers(),
        loadPrestations()
    ])
})
</script>

<template>
    <div class="remise-list-page">
        <PageHeader
            title="Remises Management"
            subtitle="Administer and oversee discount codes and promotions."
            current-breadcrumb="Remises"
        />

        <div class="remise-content">
            <div class="remise-container">
                <div class="remise-card">
                    <div class="remise-card-header">
                        <div class="remise-header-content">
                            <h2 class="remise-card-title">All Remises</h2>
                            <Button
                                label="Add Remise"
                                icon="pi pi-plus"
                                @click="openAddDialog"
                                class="add-remise-button"
                            />
                        </div>

                        <div class="remise-filters-section">
                            <span class="p-input-icon-left remise-search-input-wrapper">
                                <i class="pi pi-search remise-search-icon" />
                                <InputText
                                    v-model="globalFilter"
                                    placeholder="Search remises..."
                                    @input="onGlobalFilter"
                                    class="remise-search-input"
                                />
                            </span>
                        </div>
                    </div>

                    <DataTable
                        :value="remises"
                        :loading="loading"
                        paginator
                        :rows="10"
                        :totalRecords="totalRecords"
                        lazy
                        @page="onPage"
                        :filters="filters"
                        filterDisplay="row"
                        dataKey="id"
                        responsiveLayout="scroll"
                        :globalFilterFields="['name', 'code', 'type']"
                        class="remise-datatable"
                    >
                        <Column field="name" header="Name" sortable>
                            <template #body="{ data }">
                                <strong class="remise-name">{{ data.name }}</strong>
                            </template>
                        </Column>

                        <Column field="code" header="Code" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.code" class="remise-tag p-tag-info" />
                            </template>
                        </Column>

                        <Column field="type" header="Type" sortable>
                            <template #body="{ data }">
                                <Tag
                                    :value="data.type"
                                    :class="data.type === 'fixed' ? 'remise-tag p-tag-success' : 'remise-tag p-tag-warning'"
                                />
                            </template>
                        </Column>

                        <Column field="amount" header="Value" sortable>
                            <template #body="{ data }">
                                <span v-if="data.type === 'fixed'">
                                    {{ formatCurrency(data.amount) }}
                                </span>
                                <span v-else>
                                    {{ data.percentage }}%
                                </span>
                            </template>
                        </Column>

                        <Column field="is_active" header="Status" sortable>
                            <template #body="{ data }">
                                <Tag
                                    :value="data.is_active ? 'Active' : 'Inactive'"
                                    :class="data.is_active ? 'remise-tag p-tag-success' : 'remise-tag p-tag-danger'"
                                />
                            </template>
                        </Column>

                        <Column field="users" header="Users">
                            <template #body="{ data }">
                                <span v-if="data.users && data.users.length > 0">
                                    {{ data.users.length }} user(s)
                                </span>
                                <span v-else class="text-muted">No users</span>
                            </template>
                        </Column>

                        <Column field="prestations" header="Prestations">
                            <template #body="{ data }">
                                <span v-if="data.prestations && data.prestations.length > 0">
                                    {{ data.prestations.length }} prestation(s)
                                </span>
                                <span v-else class="text-muted">No prestations</span>
                            </template>
                        </Column>

                        <Column header="Actions" :exportable="false">
                            <template #body="{ data }">
                                <div class="remise-actions-container">
                                    <Button
                                        icon="pi pi-eye"
                                        class="remise-action-button p-button-info p-button-sm"
                                        @click="viewRemise(data)"
                                        v-tooltip.top="'View Details'"
                                    />
                                    <Button
                                        icon="pi pi-pencil"
                                        class="remise-action-button p-button-warning p-button-sm"
                                        @click="editRemise(data)"
                                        v-tooltip.top="'Edit Remise'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        class="remise-action-button p-button-danger p-button-sm"
                                        @click="confirmDelete(data)"
                                        v-tooltip.top="'Delete Remise'"
                                    />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="remise-empty-state">
                                <i class="pi pi-box remise-empty-icon"></i>
                                <p class="remise-empty-title">No remises found.</p>
                                <p class="remise-empty-text">Try adjusting your search or add a new remise.</p>
                                <Button
                                    label="Add Remise"
                                    icon="pi pi-plus"
                                    @click="openAddDialog"
                                    class="p-button-success mt-4"
                                />
                            </div>
                        </template>
                        <template #loading>
                            <div class="remise-loading-state">
                                <i class="pi pi-spin pi-spinner remise-loading-spinner"></i>
                                <p class="remise-loading-text">Loading remises, please wait...</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>


        <Dialog
            v-model:visible="showDialog"
            :header="dialogMode === 'add' ? 'Add New Remise' : 'Edit Remise'"
            :modal="true"
            :closable="false"
            :style="{ width: '600px' }"
        >
            <RemiseForm
                :remise="selectedRemise"
                :mode="dialogMode"
                :users="availableUsers"
                :prestations="availablePrestations"
                @save="handleSave"
                @cancel="closeDialog"
                :loading="saving"
            />
        </Dialog>

        <Dialog
            v-model:visible="showViewDialog"
            header="Remise Details"
            :modal="true"
            :style="{ width: '500px' }"
            :draggable="false"
        >
            <RemiseView
                :remise="selectedRemise"
                @close="showViewDialog = false"
            />
        </Dialog>

        <ConfirmDialog />
    </div>
</template>

<style scoped>
/* Page Layout */
.remise-list-page {
    padding: 2rem; /* Increased padding */
    background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%); /* Softer, more modern gradient */
    min-height: 100vh;
    font-family: 'Inter', sans-serif; /* Modern font choice */
    color: #334155; /* Default text color */
}

.remise-content {
    margin-top: 2.5rem; /* Space between header and main content */
}

.remise-container {
    max-width: 90rem;
    margin: 0 auto;
    padding: 0 1rem; /* Horizontal padding for responsiveness */
}

/* Card Styling */
.remise-card {
    background: #ffffff;
    border-radius: 1.25rem; /* Significantly more rounded corners */
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15); /* Stronger, more modern shadow */
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.remise-card-header {
    padding: 2.5rem 2rem 1.5rem 2rem; /* Adjust padding for better flow */
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
}

.remise-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.75rem; /* More space below header content */
    flex-wrap: wrap; /* Allow wrapping on smaller screens */
    gap: 1rem;
}

.remise-card-title {
    font-size: 2rem; /* Larger card title */
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

/* Add Remise Button */
.add-remise-button {
    display: inline-flex;
    align-items: center;
    padding: 0.9rem 1.8rem; /* Larger button */
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    font-weight: 600;
    border-radius: 0.75rem; /* More rounded */
    box-shadow: 0 6px 15px -3px rgba(16, 185, 129, 0.35); /* Stronger shadow */
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Smoother transition */
    border: none;
    cursor: pointer;
    font-size: 1rem; /* Larger text */
}

.add-remise-button:hover {
    transform: translateY(-2px) scale(1.01); /* More pronounced hover effect */
    box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.45);
}

/* Filters/Search Section */
.remise-filters-section {
    display: flex;
    justify-content: flex-end; /* Align search to the right */
    width: 100%;
}

.remise-search-input-wrapper {
    position: relative;
    width: 100%; /* Take full width on small screens */
    max-width: 400px; /* Limit width on larger screens */
}

.remise-search-icon {
    position: absolute;
    left: 1.1rem; /* Adjust icon position */
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; /* Softer icon color */
    font-size: 0.95rem; /* Slightly larger icon */
}

.remise-search-input {
    width: 100%;
    padding: 0.85rem 1.2rem 0.85rem 3rem; /* Adjust padding for icon */
    border: 2px solid #e5e7eb;
    border-radius: 0.6rem; /* More rounded */
    font-size: 0.95rem;
    transition: all 0.2s ease-in-out;
    background-color: #ffffff;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05); /* Subtle inner shadow */
}

.remise-search-input:focus {
    outline: none;
    border-color: var(--primary-color); /* Use primary color for focus */
    box-shadow: 0 0 0 3px var(--primary-color-lighter); /* Consistent focus ring */
}

/* DataTable Styling */
.remise-datatable {
    border: none; /* Remove default PrimeVue border, card handles it */
    border-radius: 0; /* Handled by parent card */
    overflow: hidden; /* Ensures content respects rounded corners of parent */
    padding: 0 1.5rem 1.5rem 1.5rem; /* Padding inside the card for the table */
}

.p-datatable-gridlines .p-datatable-thead > tr > th {
    background: linear-gradient(135deg, #eef2f6 0%, #eef2f6 100%); /* Lighter header background */
    border-bottom: 2px solid #dcdfe3;
    padding: 1.2rem 1.2rem; /* More padding */
    font-weight: 700; /* Bolder headers */
    color: #475569; /* Darker text */
    text-transform: uppercase;
    font-size: 0.8rem; /* Slightly larger font */
    letter-spacing: 0.08em; /* More letter spacing */
    border-right: 1px solid #f1f5f9; /* Subtle vertical line */
}

.p-datatable-gridlines .p-datatable-thead > tr > th:first-child {
    border-top-left-radius: 0.75rem; /* Match card radius */
}
.p-datatable-gridlines .p-datatable-thead > tr > th:last-child {
    border-top-right-radius: 0.75rem; /* Match card radius */
    border-right: none;
}

.p-datatable-gridlines .p-datatable-tbody > tr > td {
    padding: 1rem 1.2rem; /* Consistent padding */
    vertical-align: middle;
    border-bottom: 1px solid #eef2f6; /* Lighter row separator */
    border-right: 1px solid #f1f5f9; /* Subtle vertical line */
    color: #4b5563;
}
.p-datatable-gridlines .p-datatable-tbody > tr > td:last-child {
    border-right: none;
}

.p-datatable-gridlines .p-datatable-tbody > tr:last-child > td {
    border-bottom: none; /* No border on last row */
}
.p-datatable-gridlines .p-datatable-tbody > tr:last-child > td:first-child {
    border-bottom-left-radius: 0.75rem; /* Rounded for bottom-left cell */
}
.p-datatable-gridlines .p-datatable-tbody > tr:last-child > td:last-child {
    border-bottom-right-radius: 0.75rem; /* Rounded for bottom-right cell */
}

.p-datatable-gridlines .p-datatable-tbody > tr:hover {
    background-color: #f8fafc;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05); /* Subtle hover shadow */
    transform: translateY(-1px); /* Slight lift on hover */
}

.remise-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

.text-muted {
    color: #64748b; /* Softer muted color */
    font-style: italic;
    font-size: 0.9rem;
}

/* Tag Enhancements */
.remise-tag {
    font-size: 0.8rem; /* Smaller tag font */
    padding: 0.3em 0.7em; /* Adjusted padding */
    border-radius: 9999px; /* Fully rounded */
    font-weight: 600;
    text-transform: capitalize; /* Capitalize type tags */
}
/* Specific tag colors, aligned with PrimeVue defaults but can be customized */
.p-tag-info { background-color: var(--blue-500); color: var(--blue-50); }
.p-tag-success { background-color: var(--green-500); color: var(--green-50); }
.p-tag-warning { background-color: var(--orange-500); color: var(--orange-50); }
.p-tag-danger { background-color: var(--red-500); color: var(--red-50); }


/* Action Buttons */
.remise-actions-container {
    display: flex;
    gap: 0.6rem; /* Space between buttons */
    justify-content: center;
}

.remise-action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.8rem; /* Larger buttons */
    height: 2.8rem;
    border-radius: 0.6rem; /* More rounded */
    border: none;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    font-size: 1rem; /* Larger icon size */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08); /* Subtle shadow */
}
/* PrimeVue button classes for colors */
.p-button-info {
    background-color: var(--blue-200);
    color: var(--blue-800);
}
.p-button-info:hover {
    background-color: var(--blue-300);
    transform: translateY(-1px) scale(1.05);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}
.p-button-warning {
    background-color: var(--orange-200);
    color: var(--orange-800);
}
.p-button-warning:hover {
    background-color: var(--orange-300);
    transform: translateY(-1px) scale(1.05);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}
.p-button-danger {
    background-color: var(--red-200);
    color: var(--red-800);
}
.p-button-danger:hover {
    background-color: var(--red-300);
    transform: translateY(-1px) scale(1.05);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}

/* Empty State */
.remise-empty-state {
    padding: 4rem 2rem;
    text-align: center;
    color: #64748b;
}

.remise-empty-icon {
    font-size: 4.5rem; /* Larger icon */
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.remise-empty-title {
    font-size: 1.6rem; /* Larger title */
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #374151;
}

.remise-empty-text {
    font-size: 1rem;
    margin-bottom: 2rem;
}

/* Loading State */
.remise-loading-state {
    padding: 4rem 2rem;
    text-align: center;
    color: #64748b;
}

.remise-loading-spinner {
    font-size: 4.5rem; /* Larger spinner */
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.remise-loading-text {
    font-size: 1.1rem; /* Larger text */
    font-weight: 500;
}

/* Dialog Styling Improvements (as previously defined) */
.p-dialog .p-dialog-header {
    border-bottom: 1px solid var(--surface-border);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    font-weight: 600;
    font-size: 1.25rem;
    color: var(--text-color);
}

/* Responsive Adjustments */
@media (max-width: 1024px) {
    .remise-header-content {
        flex-direction: column;
        align-items: stretch;
    }
    .add-remise-button {
        width: 100%;
        justify-content: center;
    }
    .remise-filters-section {
        justify-content: center; /* Center search on medium screens */
    }
    .remise-search-input-wrapper {
        max-width: none; /* Allow full width */
    }
}

@media (max-width: 768px) {
    .remise-list-page {
        padding: 1rem;
    }
    .remise-card-header {
        padding: 1.5rem 1rem;
    }
    .remise-card-title {
        font-size: 1.5rem;
    }
    .remise-datatable {
        padding: 0 0.5rem 0.5rem 0.5rem; /* Reduced padding for small screens */
    }
    /* Adjust font size and padding for table headers and cells on small screens */
    .p-datatable-gridlines .p-datatable-thead > tr > th,
    .p-datatable-gridlines .p-datatable-tbody > tr > td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8rem;
    }
    .remise-action-button {
        width: 2.2rem;
        height: 2.2rem;
        font-size: 0.85rem;
    }
    .remise-empty-icon, .remise-loading-spinner {
        font-size: 3.5rem;
    }
    .remise-empty-title, .remise-loading-text {
        font-size: 1.2rem;
    }
}
</style>