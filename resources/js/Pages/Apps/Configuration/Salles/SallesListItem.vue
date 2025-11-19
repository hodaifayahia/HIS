<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';
import { useSweetAlert } from '../../../../Components/useSweetAlert';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import Chip from 'primevue/chip';

const props = defineProps({
    salle: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['edit', 'delete', 'specialization-updated']);

const swal = useSweetAlert();
const toaster = useToastr();

// Reactive data
const showSpecializationModal = ref(false);
const availableSpecializations = ref([]);
const selectedSpecializations = ref([]);
const loadingSpecializations = ref(false);
const updatingSpecializations = ref(false);

// Computed properties
const assignedSpecializations = computed(() => {
    return props.salle.specializations || [];
});

const specializationCount = computed(() => {
    return assignedSpecializations.value.length;
});

const defaultSpecializationName = computed(() => {
    return props.salle.default_specialization?.name || 'None';
});

/**
 * Load available specializations
 */
const loadSpecializations = async () => {
    loadingSpecializations.value = true;
    try {
        const response = await axios.get('/api/salles/specializations/available');
        availableSpecializations.value = response.data.data;
        
        // Pre-select currently assigned specializations
        selectedSpecializations.value = assignedSpecializations.value.map(spec => spec.id);
    } catch (error) {
        console.error('Error loading specializations:', error);
        toaster.error('Failed to load specializations');
    } finally {
        loadingSpecializations.value = false;
    }
};

/**
 * Open specialization assignment modal
 */
const openSpecializationModal = () => {
    showSpecializationModal.value = true;
    loadSpecializations();
};

/**
 * Close specialization assignment modal
 */
const closeSpecializationModal = () => {
    showSpecializationModal.value = false;
    selectedSpecializations.value = [];
    availableSpecializations.value = [];
};

/**
 * Save specialization assignments
 */
const saveSpecializations = async () => {
    updatingSpecializations.value = true;
    try {
        // Get current assigned IDs
        const currentIds = assignedSpecializations.value.map(spec => spec.id);
        
        // Find specializations to add and remove
        const toAdd = selectedSpecializations.value.filter(id => !currentIds.includes(id));
        const toRemove = currentIds.filter(id => !selectedSpecializations.value.includes(id));
        
        // If there are changes, sync all specializations
        if (toAdd.length > 0 || toRemove.length > 0) {
            // Update the salle with new specialization assignments
            const response = await axios.put(`/api/salles/${props.salle.id}`, {
                ...props.salle,
                specialization_ids: selectedSpecializations.value
            });
            
            emit('specialization-updated', response.data.data);
            toaster.success('Specializations updated successfully!');
        }
        
        closeSpecializationModal();
    } catch (error) {
        console.error('Error updating specializations:', error);
        const errorMessage = error.response?.data?.message || 'Failed to update specializations';
        toaster.error(errorMessage);
    } finally {
        updatingSpecializations.value = false;
    }
};

/**
 * Remove a specific specialization
 */
const removeSpecialization = async (specializationId) => {
    const result = await swal.fire({
        title: 'Remove Specialization?',
        text: 'Are you sure you want to remove this specialization from the salle?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.post(`/api/salles/${props.salle.id}/remove-specializations`, {
                specialization_ids: [specializationId]
            });
            
            emit('specialization-updated', response.data.data);
            toaster.success('Specialization removed successfully!');
        } catch (error) {
            console.error('Error removing specialization:', error);
            const errorMessage = error.response?.data?.message || 'Failed to remove specialization';
            toaster.error(errorMessage);
        }
    }
};

/**
 * Handle edit click
 */
const handleEdit = () => {
    emit('edit', props.salle);
};

/**
 * Handle delete click
 */
const handleDelete = () => {
    emit('delete', props.salle.id);
};
</script>

<template>
    <tr class="table-row">
        <!-- Index -->
        <td class="table-cell tw-font-mono tw-text-sm">
            {{ index + 1 }}
        </td>

        <!-- Name -->
        <td class="table-cell">
            <div class="tw-flex tw-flex-col">
                <span class="tw-font-medium tw-text-gray-900">{{ salle.name }}</span>
                <span class="tw-text-xs tw-text-gray-500">{{ salle.full_name }}</span>
            </div>
        </td>

        <!-- Number -->
        <td class="table-cell">
            <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-medium tw-bg-blue-100 tw-text-blue-800">
                {{ salle.number }}
            </span>
        </td>

        <!-- Default Specialization -->
        <td class="table-cell">
            <span v-if="salle.default_specialization" 
                  class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-medium tw-bg-green-100 tw-text-green-800">
                {{ defaultSpecializationName }}
            </span>
            <span v-else class="tw-text-gray-400 tw-text-sm">No default</span>
        </td>

        <!-- Assigned Specializations -->
        <td class="table-cell">
            <div class="tw-flex tw-flex-col tw-gap-2">
                <div v-if="specializationCount > 0" class="tw-flex tw-flex-wrap tw-gap-1">
                    <Chip 
                        v-for="specialization in assignedSpecializations.slice(0, 3)"
                        :key="specialization.id"
                        :label="specialization.name"
                        class="tw-text-xs"
                        removable
                        @remove="removeSpecialization(specialization.id)"
                    />
                    <span v-if="specializationCount > 3" 
                          class="tw-text-xs tw-text-gray-500 tw-px-2 tw-py-1">
                        +{{ specializationCount - 3 }} more
                    </span>
                </div>
                <span v-else class="tw-text-gray-400 tw-text-sm">No specializations</span>
                
                <button 
                    @click="openSpecializationModal"
                    class="tw-text-xs tw-text-blue-600 hover:tw-text-blue-800 tw-font-medium tw-self-start"
                >
                    <i class="fas fa-plus tw-mr-1"></i>
                    {{ specializationCount > 0 ? 'Manage' : 'Assign' }} Specializations
                </button>
            </div>
        </td>

        <!-- Description -->
        <td class="table-cell">
            <div class="tw-max-w-xs">
                <p v-if="salle.description" 
                   class="tw-text-sm tw-text-gray-600 tw-truncate" 
                   :title="salle.description">
                    {{ salle.description }}
                </p>
                <span v-else class="tw-text-gray-400 tw-text-sm">No description</span>
            </div>
        </td>

        <!-- Actions -->
        <td class="table-cell">
            <div class="tw-flex tw-justify-center tw-gap-2">
                <Button
                    icon="pi pi-pencil"
                    class="tw-p-2 tw-text-orange-600 hover:tw-bg-orange-50"
                    v-tooltip.top="'Edit Salle'"
                    @click="handleEdit"
                />
                <Button
                    icon="pi pi-cog"
                    class="tw-p-2 tw-text-blue-600 hover:tw-bg-blue-50"
                    v-tooltip.top="'Manage Specializations'"
                    @click="openSpecializationModal"
                />
                <Button
                    icon="pi pi-trash"
                    class="tw-p-2 tw-text-red-600 hover:tw-bg-red-50"
                    v-tooltip.top="'Delete Salle'"
                    @click="handleDelete"
                />
            </div>
        </td>
    </tr>

    <!-- Specialization Assignment Modal -->
    <Dialog 
        :visible="showSpecializationModal" 
        @update:visible="showSpecializationModal = $event"
        :style="{ width: '500px' }" 
        header="Manage Specializations" 
        :modal="true"
        @hide="closeSpecializationModal"
    >
        <div class="tw-space-y-4">
            <div>
                <h4 class="tw-text-lg tw-font-medium tw-text-gray-900 tw-mb-2">
                    {{ salle.name }} ({{ salle.number }})
                </h4>
                <p class="tw-text-sm tw-text-gray-600">
                    Select specializations to assign to this salle. You can add or remove multiple specializations.
                </p>
            </div>

            <div v-if="loadingSpecializations" class="tw-text-center tw-py-4">
                <i class="fas fa-spinner fa-spin tw-text-blue-600"></i>
                <p class="tw-text-sm tw-text-gray-600 tw-mt-2">Loading specializations...</p>
            </div>

            <div v-else>
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                    Assigned Specializations
                </label>
                <MultiSelect
                    v-model="selectedSpecializations"
                    :options="availableSpecializations"
                    optionLabel="name"
                    optionValue="id"
                    appendTo="self"
                    placeholder="Select specializations..."
                    :maxSelectedLabels="10"
                    class="tw-w-full"
                    :loading="loadingSpecializations"
                />
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                    {{ selectedSpecializations.length }} specialization(s) selected
                </p>
            </div>
        </div>

        <template #footer>
            <div class="tw-flex tw-justify-end tw-gap-2">
                <Button 
                    label="Cancel" 
                    severity="secondary" 
                    @click="closeSpecializationModal"
                    :disabled="updatingSpecializations"
                />
                <Button 
                    label="Save Changes" 
                    severity="primary" 
                    @click="saveSpecializations"
                    :loading="updatingSpecializations"
                    :disabled="loadingSpecializations"
                />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.table-row {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s;
}

.table-row:hover {
    background-color: #f9fafb;
}

.table-cell {
    padding: 1rem 1.5rem;
    vertical-align: middle;
}

:deep(.p-button) {
    border: none;
    background: transparent;
}

:deep(.p-button:hover) {
    background: rgba(0, 0, 0, 0.04);
}

:deep(.p-chip) {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

:deep(.p-chip .p-chip-remove-icon) {
    font-size: 0.625rem;
    margin-left: 0.25rem;
}

:deep(.p-multiselect) {
    min-height: 2.5rem;
}

:deep(.p-multiselect .p-multiselect-label) {
    padding: 0.5rem;
}
</style>