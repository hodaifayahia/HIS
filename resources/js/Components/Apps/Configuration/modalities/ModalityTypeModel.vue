<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../toster'; // Adjust path if necessary

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    modalityTypeData: { // Renamed from serviceData
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'modality-type-updated', 'modality-type-added']); // Renamed events

const toaster = useToastr();
const resetForm = () => {
    form.value = {
        id: null,
        name: '',
        description: '',
        image_url: '',
        image: null
    };
    formErrors.value = {};
    isLoading.value = false;
    imagePreviewUrl.value = null; // Clear image preview
};

const form = ref({
    id: null,
    name: '',
    description: '',
    image_url: '', // This will hold the current image URL for display
    image: null // This will hold the new file object for upload
});

const formErrors = ref({});
const isLoading = ref(false);
const imagePreviewUrl = ref(null);

// Watch for changes in modalityTypeData to populate the form for editing
watch(() => props.modalityTypeData, (newVal) => {
    if (newVal) {
        form.value.id = newVal.id;
        form.value.name = newVal.name;
        form.value.description = newVal.description;
        form.value.image_url = newVal.image_url; // Set existing image URL
        imagePreviewUrl.value = newVal.image_url; // Set preview for existing image
        form.value.image = null; // Clear file input when opening for edit
    } else {
        resetForm(); // Reset if no data (for new entry)
    }
}, { deep: true, immediate: true }); // Immediate to run on initial mount if data is present

watch(() => props.showModal, (newVal) => {
    if (!newVal) {
        resetForm();
    }
});


const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file;
        imagePreviewUrl.value = URL.createObjectURL(file); // Create URL for instant preview
        formErrors.value.image = null; // Clear image error on new selection
    } else {
        form.value.image = null;
        // If there was an existing image_url, keep its preview, otherwise clear
        imagePreviewUrl.value = form.value.image_url;
    }
};

const removeImage = () => {
    form.value.image = null; // Clear the new file input
    form.value.image_url = null; // Mark for removal in the backend
    imagePreviewUrl.value = null; // Clear the preview
    formErrors.value.image = null; // Clear any image-related errors
};

const submitForm = async () => {
    isLoading.value = true;
    formErrors.value = {}; // Clear previous errors

    const formData = new FormData();
    formData.append('name', form.value.name || '');
    formData.append('description', form.value.description || '');

    if (form.value.image) {
        formData.append('image', form.value.image); // Append new image file
    } else if (form.value.image_url === null) {
        // If image_url is explicitly null, means user wants to remove existing image
        formData.append('image_url', ''); // Send empty string to signify removal
    }
    // If image is null and image_url is not null, it means no change to image, so don't append image_url at all.

    try {
        if (form.value.id) {
            // Update existing service
            formData.append('_method', 'PUT'); // Spoof PUT request for file uploads
            await axios.post(`/api/modality-types/${form.value.id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            toaster.success('Modality Type updated successfully!');
            emit('modality-type-updated'); // Emit updated event
        } else {
            // Add new service
            await axios.post('/api/modality-types', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            toaster.success('Modality Type added successfully!');
            emit('modality-type-added'); // Emit added event
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            formErrors.value = error.response.data.errors;
            toaster.error('Validation failed. Please check your inputs.');
        } else {
            toaster.error(error.response?.data?.message || 'An error occurred. Please try again.');
        }
        console.error('API Error:', error);
    } finally {
        isLoading.value = false;
    }
};



const close = () => {
    emit('close');
    resetForm();
};
</script>

<template>
    <div v-if="showModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ form.id ? 'Edit Modality Type' : 'Add New Modality Type' }}</h3>
                <button @click="close" class="close-button">&times;</button>
            </div>
            <form @submit.prevent="submitForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="modalityTypeName">Name:</label>
                        <input type="text" id="modalityTypeName" v-model="form.name"
                               :class="{ 'is-invalid': formErrors.name }" required>
                        <div v-if="formErrors.name" class="invalid-feedback">
                            {{ formErrors.name[0] }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="modalityTypeDescription">Description:</label>
                        <textarea id="modalityTypeDescription" v-model="form.description"
                                  :class="{ 'is-invalid': formErrors.description }"></textarea>
                        <div v-if="formErrors.description" class="invalid-feedback">
                            {{ formErrors.description[0] }}
                        </div>
                    </div>

                    <div class="form-group image-upload-group">
                        <label for="modalityTypeImage">Image:</label>
                        <input type="file" id="modalityTypeImage" @change="handleFileChange" accept="image/*"
                               :class="{ 'is-invalid': formErrors.image }">
                        <div v-if="formErrors.image" class="invalid-feedback">
                            {{ formErrors.image[0] }}
                        </div>

                        <div v-if="imagePreviewUrl" class="image-preview">
                            <img :src="imagePreviewUrl" alt="Image Preview" class="uploaded-image">
                            <button type="button" @click="removeImage" class="remove-image-button">
                                <i class="fas fa-times-circle"></i> Remove Image
                            </button>
                        </div>
                        <p v-else class="no-image-selected">No image selected.</p>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" @click="close" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isLoading">
                        <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        {{ isLoading ? 'Saving...' : (form.id ? 'Update' : 'Add') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Modal Overlay */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000; /* Ensure it's above other content */
}

/* Modal Content */
.modal-content {
    background-color: #ffffff; /* White background */
    border-radius: 0.5rem; /* Rounded corners */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Soft shadow */
    width: 90%; /* Responsive width */
    max-width: 600px; /* Max width for larger screens */
    animation: fadeIn 0.3s ease-out; /* Simple fade-in animation */
    display: flex;
    flex-direction: column;
    max-height: 90vh; /* Limit height for scrollable content */
    overflow: hidden; /* Hide overflow from header/footer */
}

.modal-header {
    padding: 1rem 1.5rem; /* px-6 py-4 */
    border-bottom: 1px solid #e5e7eb; /* border-b border-gray-200 */
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.25rem; /* text-xl */
    font-weight: 600; /* font-semibold */
    color: #1f2937; /* text-gray-800 */
    margin: 0;
}

.close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280; /* text-gray-500 */
    cursor: pointer;
    transition: color 0.2s ease-in-out;
}

.close-button:hover {
    color: #374151; /* text-gray-700 */
}

.modal-body {
    padding: 1.5rem; /* p-6 */
    overflow-y: auto; /* Make body scrollable if content overflows */
    flex-grow: 1; /* Allow body to take available space */
}

.form-group {
    margin-bottom: 1rem; /* mb-4 */
}

.form-group label {
    display: block; /* block */
    font-size: 0.875rem; /* text-sm */
    font-weight: 500; /* font-medium */
    color: #374151; /* text-gray-700 */
    margin-bottom: 0.5rem; /* mb-1 */
}

.form-group input[type="text"],
.form-group textarea,
.form-group input[type="file"] {
    width: 100%; /* w-full */
    padding: 0.625rem 0.75rem; /* px-3 py-2.5 */
    border: 1px solid #d1d5db; /* border border-gray-300 */
    border-radius: 0.375rem; /* rounded-md */
    font-size: 0.875rem; /* text-sm */
    color: #4b5563; /* text-gray-700 */
    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    box-sizing: border-box; /* Include padding in element's total width and height */
}

.form-group input[type="text"]:focus,
.form-group textarea:focus,
.form-group input[type="file"]:focus {
    border-color: #3b82f6; /* focus:border-blue-500 */
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); /* focus:ring-blue-500/50 */
}

.form-group textarea {
    min-height: 80px;
    resize: vertical; /* Allow vertical resizing */
}

.form-group .is-invalid {
    border-color: #ef4444; /* border-red-500 */
}

.invalid-feedback {
    color: #ef4444; /* text-red-500 */
    font-size: 0.75rem; /* text-xs */
    margin-top: 0.25rem; /* mt-1 */
}

/* Image Upload Specific Styles */
.image-upload-group {
    margin-top: 1.5rem;
    border: 1px dashed #d1d5db;
    padding: 1rem;
    border-radius: 0.375rem;
    background-color: #f9fafb;
}

.image-upload-group input[type="file"] {
    margin-top: 0.5rem;
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
}

.image-preview {
    margin-top: 1rem;
    text-align: center;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.5rem;
    background-color: #ffffff;
}

.uploaded-image {
    max-width: 100%;
    max-height: 150px; /* Limit image preview height */
    height: auto;
    display: block;
    margin: 0 auto 0.75rem auto; /* Center image and add spacing */
    border-radius: 0.25rem;
    object-fit: contain;
}

.remove-image-button {
    background-color: #ef4444;
    color: #ffffff;
    border: none;
    padding: 0.4rem 0.8rem;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: background-color 0.2s ease-in-out;
}

.remove-image-button:hover {
    background-color: #dc2626;
}

.no-image-selected {
    text-align: center;
    margin-top: 1rem;
    color: #9ca3af;
    font-style: italic;
    font-size: 0.875rem;
}


.modal-footer {
    padding: 1rem 1.5rem; /* px-6 py-4 */
    border-top: 1px solid #e5e7eb; /* border-t border-gray-200 */
    display: flex;
    justify-content: flex-end; /* Align buttons to the right */
    gap: 0.75rem; /* space-x-3 */
}

.btn {
    padding: 0.5rem 1rem; /* px-4 py-2 */
    border-radius: 0.375rem; /* rounded-md */
    font-weight: 600; /* font-semibold */
    cursor: pointer;
    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, color 0.2s ease-in-out;
    border: 1px solid transparent;
}

.btn-secondary {
    background-color: #6c757d; /* bg-gray-500 */
    color: #ffffff; /* text-white */
}

.btn-secondary:hover {
    background-color: #5a6268; /* hover:bg-gray-600 */
}

.btn-primary {
    background-color: #2563eb; /* bg-blue-600 */
    color: #ffffff; /* text-white */
}

.btn-primary:hover {
    background-color: #1d4ed8; /* hover:bg-blue-700 */
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Spinner for loading state */
.spinner-border {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    vertical-align: text-bottom;
    border: 0.125em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border .75s linear infinite;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}

/* Animation for modal */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>