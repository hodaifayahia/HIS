<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../toster'; // Adjust path as needed
import { onClickOutside } from '@vueuse/core'; // For closing dropdowns
import consultationworkspacesModel from '../DoctorDoc/consultationworkspacesModel.vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false // Controls modal visibility
    },
    initialWorkspaceId: {
        type: [Number, String, Array], // Can be single ID or array of IDs
        default: null // Changed default to null for clearer initialization logic
    },
    currentDoctorId: {
        type: [Number, String],
        required: true // Doctor ID is likely needed to filter workspaces
    },
    consultationIdToAdd: {
        type: [Number, String],
        default: null // If null, the user will select from a list (existing functionality)
    }
});

const emit = defineEmits(['update:modelValue', 'consultation-added']);

const toaster = useToastr();

// Form state
const selectedWorkspaceIds = ref([]); // Changed to an array
const selectedConsultationId = ref(props.consultationIdToAdd);
const notes = ref('');

// Data for dropdowns
const allWorkspaces = ref([]);
const filteredWorkspaces = ref([]);
const workspaceSearchQuery = ref('');
const showWorkspaceDropdown = ref(false);
const workspaceLoading = ref(false);
const showCreateModal = ref(false);

// State for the single consultation if provided via prop
const consultationDetails = ref(null);
const consultationDetailsLoading = ref(false);

// Refs for dropdown elements to use onClickOutside
const workspaceDropdownRef = ref(null);
onClickOutside(workspaceDropdownRef, () => { showWorkspaceDropdown.value = false; });

const isLoading = ref(false);
const errors = ref({});

// --- Computed properties ---
const isFormValid = computed(() => {
    return selectedWorkspaceIds.value.length > 0 && selectedConsultationId.value !== null;
});

const selectedWorkspacesDisplay = computed(() => {
    return selectedWorkspaceIds.value.map(id => {
        const workspace = allWorkspaces.value.find(ws => ws.id == id);
        return workspace ? { id: workspace.id, name: workspace.name } : null;
    }).filter(Boolean); // Filter out any nulls if an ID isn't found
});

// Event Handlers
const handleWorkspaceSaved = async () => {
    await fetchWorkspaces(); // Re-fetch to include the newly created workspace
    showCreateModal.value = false;
    // Optionally pre-select the newly created workspace if desired
    // For now, it just closes the modal and refreshes the list
};

const openModal = (workspace = null, edit = false) => {
    showCreateModal.value = true;
};

// Determine if we are in 'single consultation' mode or 'select consultation from list' mode
const isSingleConsultationMode = computed(() => props.consultationIdToAdd !== null);

// --- Methods ---

const closeModal = () => {
    emit('update:modelValue', false);
    resetForm();
};

const resetForm = () => {
    // Always start with an empty array
    selectedWorkspaceIds.value = []; 

    // Then, if initialWorkspaceId is provided, populate it carefully
    if (props.initialWorkspaceId !== null) {
        if (Array.isArray(props.initialWorkspaceId)) {
            // Ensure each item in the array is a valid ID before adding
            props.initialWorkspaceId.forEach(id => {
                if (id !== null && id !== undefined && id !== '') { // Basic check for validity
                    selectedWorkspaceIds.value.push(id);
                }
            });
        } else if (props.initialWorkspaceId !== null && props.initialWorkspaceId !== undefined && props.initialWorkspaceId !== '') {
            selectedWorkspaceIds.value.push(props.initialWorkspaceId);
        }
    }
    
    selectedConsultationId.value = props.consultationIdToAdd;
    notes.value = '';
    workspaceSearchQuery.value = '';
    errors.value = {};
    showWorkspaceDropdown.value = false;
    consultationDetails.value = null;

    // Optional: Log the state after reset for debugging
    // console.log('selectedWorkspaceIds after reset:', selectedWorkspaceIds.value);
};

// Fetch Workspaces
const fetchWorkspaces = async () => {
    workspaceLoading.value = true;
    try {
        const response = await axios.get('/api/consultationworkspaces', {
            params: {
                doctorid: props.currentDoctorId,
                is_archived: false
            }
        });
        allWorkspaces.value = response.data.data;
        filteredWorkspaces.value = allWorkspaces.value;

        // Re-apply pre-selection based on current allWorkspaces and initialWorkspaceId
        // This makes sure selected IDs actually correspond to existing workspaces
        const initialSelected = [];
        if (props.initialWorkspaceId !== null) {
            const initialIds = Array.isArray(props.initialWorkspaceId) ? props.initialWorkspaceId : [props.initialWorkspaceId];
            initialIds.forEach(id => {
                const foundWorkspace = allWorkspaces.value.find(ws => ws.id == id);
                if (foundWorkspace && !initialSelected.includes(foundWorkspace.id)) {
                    initialSelected.push(foundWorkspace.id);
                }
            });
        }
        // Only update if there are new initial selections
        selectedWorkspaceIds.value = [...new Set([...selectedWorkspaceIds.value, ...initialSelected])];


    } catch (err) {
        toaster.error('Failed to load workspaces.');
        console.error('Error fetching workspaces:', err);
    } finally {
        workspaceLoading.value = false;
    }
};

const closemodel = () => {
    showCreateModal.value = false;
    fetchWorkspaces(); // Refresh list after creating a new workspace
};

const onWorkspaceSearch = () => {
    showWorkspaceDropdown.value = true;
    filteredWorkspaces.value = allWorkspaces.value.filter(ws =>
        ws.name.toLowerCase().includes(workspaceSearchQuery.value.toLowerCase()) &&
        !selectedWorkspaceIds.value.includes(ws.id) // Don't show already selected ones in the dropdown
    );
};

const onWorkspaceSelect = (workspace) => {
    // Add console.logs here to verify 'workspace' and 'workspace.id'
    // console.log('Workspace selected:', workspace);
    // console.log('Workspace ID to add:', workspace.id);

    if (workspace && workspace.id !== null && workspace.id !== undefined && !selectedWorkspaceIds.value.includes(workspace.id)) {
        selectedWorkspaceIds.value.push(workspace.id);
        errors.value.consultation_workspace_ids = null; // Clear error on selection
    }
    workspaceSearchQuery.value = ''; // Clear search query after selection
    showWorkspaceDropdown.value = false; // Close dropdown after selection
    filteredWorkspaces.value = allWorkspaces.value.filter(ws => !selectedWorkspaceIds.value.includes(ws.id));
};

const removeSelectedWorkspace = (workspaceId) => {
    selectedWorkspaceIds.value = selectedWorkspaceIds.value.filter(id => id !== workspaceId);
    // Re-filter dropdown to potentially re-include the removed workspace if search query matches
    onWorkspaceSearch(); // Re-trigger search to update filtered list
    if (selectedWorkspaceIds.value.length === 0) {
        // Use the correct error key consistent with Laravel's validation output
        errors.value.consultation_workspace_ids = ['Please select at least one workspace.'];
    }
};


// Handle Form Submission
const handleSubmit = async () => {
    if (!isFormValid.value) {
        // Ensure error message is correctly assigned to the array field
        if (selectedWorkspaceIds.value.length === 0) errors.value.consultation_workspace_ids = ['Please select at least one workspace.'];
        if (selectedConsultationId.value === null) errors.value.consultation_id = ['A consultation must be selected or provided.'];
        toaster.error('Please fill in all required fields.');
        return;
    }

    isLoading.value = true;
    errors.value = {};

    // Add console.logs for final payload before sending
    // console.log('Payload being sent:', {
    //     consultation_id: selectedConsultationId.value,
    //     consultation_workspace_ids: selectedWorkspaceIds.value,
    //     notes: notes.value
    // });
    
    try {
        const payload = {
            consultation_id: selectedConsultationId.value,
            // Send an array of workspace IDs
            consultation_workspace_ids: selectedWorkspaceIds.value, // Changed key and value type
            notes: notes.value
        };
        const response = await axios.post('/api/details/consultationworkspaces', payload); // Adjust API endpoint if necessary
        toaster.success('Consultation successfully added to workspace(s)!');
        emit('consultation-added', response.data.data); // Emits the response data, which might be an array or single item
        closeModal();
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors;
            toaster.error('Validation failed. Please check your input.');
        } else {
            toaster.error(err.response?.data?.message || 'Failed to add consultation to workspace(s).');
        }
        console.error('Error adding consultation to workspace(s):', err);
    } finally {
        isLoading.value = false;
    }
};

// Lifecycle Hooks and Watchers
onMounted(() => {
    if (props.modelValue) {
        fetchWorkspaces();
        resetForm(); // Ensure form is reset on mount if modal is initially open
    }
});

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        resetForm(); // Reset form when modal opens
        fetchWorkspaces();
    }
});

// Watch selectedWorkspaceIds for debugging purposes
// watch(selectedWorkspaceIds, (newVal, oldVal) => {
//     console.log('selectedWorkspaceIds updated:', newVal);
// }, { deep: true });

</script>

<template>
    <Transition name="modal-fade">
        <div v-if="modelValue" class="modal-backdrop">
            <div class="modal-dialog" role="document" @click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Consultation to Workspace(s)</h5>
                        <button type="button" class="close" @click="closeModal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary w-100" @click="showCreateModal = true">
                                <i class="fas fa-plus me-2"></i>
                                Add New Workspace
                            </button>
                        </div>
                        <form @submit.prevent="handleSubmit">
                            <div class="form-group mb-3">
                                <label for="workspace-select" class="form-label">Select Workspace(s) <span
                                        class="text-danger">*</span></label>
                                <div class="premium-dropdown-container" ref="workspaceDropdownRef">
                                    <div class="premium-input-group">
                                        <i class="fas fa-folder premium-input-icon"></i>
                                        <input type="text" class="form-control premium-search"
                                            placeholder="Search or select workspaces..." v-model="workspaceSearchQuery"
                                            @focus="showWorkspaceDropdown = true" @input="onWorkspaceSearch"
                                            :class="{ 'is-invalid': errors.consultation_workspace_ids }"
                                            aria-label="Select Workspaces">
                                        <i v-if="workspaceSearchQuery" class="fas fa-times premium-clear-icon"
                                            @click="workspaceSearchQuery = ''; onWorkspaceSearch();"
                                            title="Clear Search"
                                            style="right: 2.5rem; cursor:pointer; position:absolute;"></i>
                                        <i class="fas fa-chevron-down premium-dropdown-arrow"
                                            @click="showWorkspaceDropdown = !showWorkspaceDropdown"></i>
                                    </div>
                                    <div v-if="showWorkspaceDropdown" class="premium-dropdown-menu">
                                        <div v-if="workspaceLoading" class="dropdown-loading-state">Loading
                                            workspaces...</div>
                                        <div v-else-if="filteredWorkspaces.length > 0">
                                            <div v-for="workspace in filteredWorkspaces" :key="workspace.id"
                                                class="premium-dropdown-item" @click="onWorkspaceSelect(workspace)">
                                                <i class="fas fa-folder premium-item-icon"></i>
                                                {{ workspace.name }}
                                            </div>
                                        </div>
                                        <div v-else class="premium-dropdown-empty">No matching workspaces found</div>
                                    </div>
                                    <div v-if="errors.consultation_workspace_ids" class="invalid-feedback d-block">
                                        {{ errors.consultation_workspace_ids[0] }}
                                    </div>
                                </div>

                                <div class="selected-workspaces-pills mt-2 d-flex flex-wrap gap-2">
                                    <span v-for="workspace in selectedWorkspacesDisplay" :key="workspace.id"
                                        class="badge bg-primary text-white py-3 px-3 d-flex align-items-center">
                                        {{ workspace.name }}
                                        <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove"
                                            @click="removeSelectedWorkspace(workspace.id)"> <i class="fas fa-times "></i></button>
                                    </span>
                                    <span
                                        v-if="selectedWorkspaceIds.length === 0 && !showWorkspaceDropdown && !workspaceSearchQuery"
                                        class="text-muted fst-italic">No workspaces selected.</span>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="notes" class="form-label">Notes (Optional)</label>
                                <textarea id="notes" class="form-control" rows="3" v-model="notes"
                                    :class="{ 'is-invalid': errors.notes }" aria-label="Notes"></textarea>
                                <div v-if="errors.notes" class="invalid-feedback">
                                    {{ errors.notes[0] }}
                                </div>
                            </div>

                            <div class="modal-footer justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" @click="closeModal">Cancel</button>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading || !isFormValid">
                                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status"
                                        aria-hidden="true"></span>
                                    {{ isLoading ? 'Adding...' : 'Add' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <consultationworkspacesModel v-model="showCreateModal" :doctorid="currentDoctorId"
        @workspace-saved="handleWorkspaceSaved" @close="closemodel()" />
</template>

<style scoped>
/* Existing styles from your original component should go here */
/* Modal Overlay & Centering */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1050;
    /* Bootstrap modal-backdrop z-index */
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    margin-left: 0.5rem;
}

.modal-dialog {
    background: #fff;
    border-radius: 0.75rem;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    animation: zoomIn 0.3s ease-out;
    display: flex;
    /* To make modal-content take full width */
    flex-direction: column;
    /* To make modal-content stack vertically */
}

.modal-content {
    border: none;
    background-color: transparent;
    /* Remove default bootstrap background */
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #343a40;
}

.modal-header .close {
    padding: 0.5rem;
    margin: -0.5rem -0.5rem -0.5rem auto;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    opacity: 0.5;
    background: transparent;
    border: 0;
    cursor: pointer;
    border-radius: 0.25rem;
    transition: opacity 0.15s ease-in-out;
}

.modal-header .close:hover {
    opacity: 0.75;
}

.modal-body {
    padding: 1.5rem;
    flex-grow: 1;
    overflow-y: auto;
    /* Enable scrolling for long content */
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
}

/* Custom Premium Styles for Inputs & Dropdowns (reused from your template) */
.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #495057;
}

.form-control,
.premium-search {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #ced4da;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.2s ease-in-out;
    background-color: #fefefe;
}

.form-control:focus,
.premium-search:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
    outline: none;
}

.is-invalid {
    border-color: #dc3545 !important;
}

.invalid-feedback {
    display: block;
    /* Always show if present */
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

.premium-dropdown-container {
    position: relative;
    width: 100%;
}

.premium-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.premium-input-icon {
    position: absolute;
    left: 1rem;
    color: #6c757d;
    z-index: 2;
}

.premium-search {
    padding-left: 2.5rem;
    /* Make space for icon */
}

.premium-clear-icon {
    position: absolute;
    right: 2.5rem;
    color: #6c757d;
    cursor: pointer;
    z-index: 2;
}

.premium-dropdown-arrow {
    position: absolute;
    right: 1rem;
    color: #6c757d;
    cursor: pointer;
    z-index: 2;
    transition: transform 0.2s ease;
}

.premium-dropdown-menu {
    position: absolute;
    top: 100%;
    /* Position below the input */
    left: 0;
    right: 0;
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    max-height: 250px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 0.5rem;
    /* Space between input and dropdown */
}

.premium-dropdown-item {
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.premium-dropdown-item:hover {
    background-color: #f8f9fa;
}

.premium-item-icon {
    margin-right: 0.75rem;
    color: #6c757d;
}

.premium-dropdown-empty,
.dropdown-loading-state {
    padding: 1rem;
    color: #6c757d;
    text-align: center;
}

/* Spinner for loading state */
.spinner-border {
    width: 1.25rem;
    height: 1.25rem;
    vertical-align: -0.125em;
    border: 0.15em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: .75s linear infinite spinner-border;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}

/* Transition for modal fade-in/out */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* New styles for selected workspaces pills */
.selected-workspaces-pills .badge {
    background-color: #007bff !important; /* Primary color */
    font-size: 0.9em;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.selected-workspaces-pills .badge .btn-close-white {
    background-color: transparent;
    border: none;
    font-size: 0.65em;
    opacity: 0.8;
    margin-top: -2px; /* Adjust vertical alignment */
}

.selected-workspaces-pills .badge .btn-close-white:hover {
    opacity: 1;
}

</style>