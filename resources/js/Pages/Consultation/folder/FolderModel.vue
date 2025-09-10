<script setup>
import { ref, watch } from 'vue';
import { useToastr } from '../../../Components/toster';
import axios from 'axios'; // Make sure axios is imported

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    folder: {
        type: Object,
        default: () => ({
            name: '',
            description: ''
        })
    },
    isEdit: {
        type: Boolean,
        default: false
    },
    doctorid: {
        type: Number, // Assuming doctorid is a number
        default: null
    },
    specializationId: { // New prop for specializationId
        type: Number, // Assuming specializationId is a number
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'folder-saved']);
const toaster = useToastr();

const folderForm = ref({
    name: '',
    description: ''
});

const loading = ref(false);
const errors = ref({});

// Watch for changes in props.folder and update folderForm accordingly
watch(() => props.folder, (newVal) => {
    folderForm.value = { ...newVal };
}, { deep: true });

const saveFolder = async () => {
    try {
        loading.value = true;
        errors.value = {};

        const url = props.isEdit
            ? `/folders/${props.folder.id}`
            : '/folders';

        const method = props.isEdit ? 'put' : 'post';

        // Create a payload that includes doctor_id and specializationId
        const payload = {
            ...folderForm.value, // Copy existing form data (name, description)
            doctor_id: props.doctorid, // Add the doctorid from props
            specialization_id: props.specializationId // Add the specializationId from props
        };

        const response = await axios[method](url, payload); // Send the updated payload

        emit('folder-saved', response.data.data);
        closeModal();
        toaster.success(`Folder ${props.isEdit ? 'updated' : 'created'} successfully`);
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else {
            toaster.error(err.response?.data?.message || 'Failed to save folder');
        }
    } finally {
        loading.value = false;
    }
};

const closeModal = () => {
    emit('update:modelValue', false);
    // Reset form to initial state, clear errors
    folderForm.value = {
        name: '',
        description: ''
    };
    errors.value = {};
};
</script>

<template>
    <div class="modal fade show" :class="{ 'd-block': modelValue }" tabindex="-1" v-if="modelValue">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-2">
                    <div>
                        <h5 class="modal-title fw-bold">{{ isEdit ? 'Edit' : 'Create New' }} Folder</h5>
                        <p class="text-muted small mb-0">{{ isEdit ? 'Update folder details' : 'Create a new folder to organize your templates' }}</p>
                    </div>
                </div>
                <div class="modal-body pt-2">
                    <form @submit.prevent="saveFolder">
                        <div class="mb-4">
                            <label class="form-label fw-medium">Folder Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                :class="{ 'is-invalid': errors.name }"
                                v-model="folderForm.name"
                                placeholder="Enter folder name"
                                autofocus
                            >
                            <div class="invalid-feedback" v-if="errors.name">
                                {{ errors.name[0] }}
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium">Description</label>
                            <textarea
                                class="form-control"
                                :class="{ 'is-invalid': errors.description }"
                                v-model="folderForm.description"
                                placeholder="Add a description for your folder (optional)"
                                rows="3"
                            ></textarea>
                            <div class="invalid-feedback" v-if="errors.description">
                                {{ errors.description[0] }}
                            </div>
                            <div class="form-text">
                                A good description helps you remember the purpose of this folder
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button
                        type="button"
                        class="btn btn-light px-4"
                        :disabled="loading"
                        @click="closeModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary px-4"
                        :disabled="loading"
                        @click="saveFolder"
                    >
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                        {{ isEdit ? 'Update' : 'Create' }} Folder
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal {
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    border-radius: 12px;
}

.form-control {
    border-radius: 8px;
    border-color: #e9ecef;
    padding: 0.75rem 1rem;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.form-control.is-invalid:focus {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
}

.form-control-lg {
    font-size: 1rem;
}

.btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:active {
    transform: translateY(0);
}

.btn-primary {
    background: #0d6efd;
    border: none;
}

.btn-primary:hover {
    background: #0b5ed7;
}

.btn-light {
    background: #f8f9fa;
    border-color: #f0f0f0;
}

.btn-light:hover {
    background: #e9ecef;
    border-color: #e9ecef;
}

.invalid-feedback {
    font-size: 0.875rem;
    margin-top: 0.375rem;
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.375rem;
}
</style>