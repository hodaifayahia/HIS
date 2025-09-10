<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster'; // Assuming this path is correct


const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    serviceData: {
        type: Object,
        default: () => ({
            image_url: '',
            name: '',
            description: '',
            start_time: '',
            end_time: '',
            agmentation: '',
            is_active: true // Default should be boolean
        })
    }
});


const emit = defineEmits(['close', 'service-added', 'service-updated']);
const toaster = useToastr();


const form = ref({
    id: null,
    name: '',
    description: '',
    image_url: '',
    start_time: '',
    end_time: '',
    agmentation: '',
    is_active: true // Ensure initial ref value is boolean
});


const imageFile = ref(null);
const imagePreviewUrl = ref(null);


const isEditMode = ref(false);
const submitting = ref(false);
const errors = ref({}); // For validation errors from the backend


const resetForm = () => {
    form.value = {
        id: null,
        name: '',
        description: '',
        image_url: '',
        start_time: '',
        end_time: '',
        agmentation: '',
        is_active: true // Reset to boolean true
    };
    imageFile.value = null;
    imagePreviewUrl.value = null;
    errors.value = {};
};


watch(() => props.serviceData, (newVal) => {
    if (newVal && Object.keys(newVal).length > 0) {
        form.value = {
            ...newVal,
            // Explicitly ensure is_active is a boolean
            is_active: !!newVal.is_active
        };
        isEditMode.value = !!newVal.id;
        imagePreviewUrl.value = newVal.image_url;
    } else {
        resetForm();
        isEditMode.value = false;
    }
}, { immediate: true });


const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        imageFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        imageFile.value = null;
        // Keep existing image if no new file selected and we are in edit mode
        imagePreviewUrl.value = form.value.image_url;
    }
};


const handleSubmit = async () => {
    submitting.value = true;
    errors.value = {};


    const formData = new FormData();


    formData.append('name', form.value.name);
    // Append description only if it has a value
    if (form.value.description) {
        formData.append('description', form.value.description);
    }


    if (imageFile.value) {
        formData.append('image', imageFile.value);
    } else if (form.value.image_url && isEditMode.value) {
        // If no new image, but there's an existing URL in edit mode, send it
        // This is crucial if your backend distinguishes between no change and deleting image
        formData.append('image_url', form.value.image_url);
    } else if (isEditMode.value && !form.value.image_url && !imageFile.value) {
        // Explicitly tell backend to remove image if it was cleared in edit mode
        formData.append('remove_image', 'true');
    }



    if (isEditMode.value) {
        // For PUT requests with FormData, Laravel expects _method field
        formData.append('_method', 'PUT');
    }
    formData.append('start_time', form.value.start_time);
    formData.append('end_time', form.value.end_time);
    formData.append('agmentation', form.value.agmentation);
    // Send 1 or 0 for boolean fields to backend, as FormData converts true/false to 'true'/'false' strings
    formData.append('is_active', form.value.is_active ? 1 : 0);


    try {
        let response;
        if (isEditMode.value) {
            response = await axios.post(`/api/services/${form.value.id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            toaster.success('Service updated successfully!');
            // Emit the updated service object
            emit('service-updated', response.data.data || response.data);
        } else {
            response = await axios.post('/api/services', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            toaster.success('Service added successfully!');
            // Emit the newly added service object
            emit('service-added', response.data.data || response.data);
        }
        closeModal();
    } catch (err) {
        console.error('Error submitting service:', err);
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors;
            toaster.error('Please correct the form errors.');
        } else {
            toaster.error(err.response?.data?.message || 'An error occurred. Please try again.');
        }
    } finally {
        submitting.value = false;
    }
};


const closeModal = () => {
    resetForm();
    emit('close');
};
</script>


<template>
    <teleport to="body">
        <transition name="modal-fade">
            <div v-if="showModal" class="modal-overlay" @click="closeModal">
                <div class="modal-container" @click.stop>
                    <div class="modal-header">
                        <div class="header-content">
                            <div class="modal-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div>
                                <h3 class="modal-title">
                                    {{ isEditMode ? 'Edit Service' : 'Add New Service' }}
                                </h3>
                                <p class="modal-subtitle">
                                    {{ isEditMode ? 'Update your service information' : 'Create a new service for your platform' }}
                                </p>
                            </div>
                        </div>
                        <button @click="closeModal" class="modal-close-button">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>


                    <div class="modal-body">
                        <form @submit.prevent="handleSubmit" class="modal-form">
                            <div class="form-group">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag label-icon"></i>
                                    Service Name <span class="required-star">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="form.name"
                                    class="form-input"
                                    :class="{ 'input-error': errors.name }"
                                    placeholder="Enter service name"
                                    required
                                >
                                <p v-if="errors.name" class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ errors.name[0] }}
                                </p>
                            </div>


                            <div class="form-group">
                                <label for="image_upload" class="form-label">
                                    <i class="fas fa-image label-icon"></i>
                                    Service Image
                                </label>
                                <div class="image-upload-container">
                                    <input
                                        type="file"
                                        id="image_upload"
                                        @change="handleImageUpload"
                                        accept="image/*"
                                        class="form-file-input"
                                        hidden
                                    >
                                    <label for="image_upload" class="file-upload-label">
                                        <div class="upload-area">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <span class="upload-text">Click to upload image</span>
                                            <span class="upload-subtext">PNG, JPG, GIF up to 10MB</span>
                                        </div>
                                    </label>
                                    <p v-if="errors.image" class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ errors.image[0] }}
                                    </p>


                                    <div v-if="imagePreviewUrl" class="image-preview-container">
                                        <div class="image-preview-wrapper">
                                            <img :src="imagePreviewUrl" alt="Service Preview" class="image-preview">
                                            <div class="image-overlay">
                                                <button type="button" @click="imageFile = null; imagePreviewUrl = null; form.image_url = ''" class="remove-image-btn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- time-range box -->
                                <div class="time-range-box">
                                <div class="form-row">
                                    <!-- START TIME -->
                                    <div class="form-group">
                                    <label for="start_time" class="form-label">
                                        <i class="fas fa-clock label-icon"></i>
                                        Start Time
                                    </label>
                                    <input
                                        type="time"
                                        id="start_time"
                                        v-model="form.start_time"
                                        class="form-input"
                                        :class="{ 'input-error': errors.start_time }"
                                    >
                                    <p v-if="errors.start_time" class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ errors.start_time[0] }}
                                    </p>
                                    </div>

                                    <!-- END TIME -->
                                    <div class="form-group">
                                    <label for="end_time" class="form-label">
                                        <i class="fas fa-hourglass-end label-icon"></i>
                                        End Time
                                    </label>
                                    <input
                                        type="time"
                                        id="end_time"
                                        v-model="form.end_time"
                                        class="form-input"
                                        :class="{ 'input-error': errors.end_time }"
                                    >
                                    <p v-if="errors.end_time" class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ errors.end_time[0] }}
                                    </p>
                                    </div>
                                </div>
                                  <div class="form-row">
                                <div class="form-group">
                                    <label for="agmentation" class="form-label">
                                        <i class="fas fa-chart-line label-icon"></i>
                                        Augmentation
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        id="agmentation"
                                        v-model="form.agmentation"
                                        class="form-input"
                                        :class="{ 'input-error': errors.agmentation }"
                                        placeholder="0.00"
                                    >
                                    <p v-if="errors.agmentation" class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ errors.agmentation[0] }}
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label for="is_active" class="form-label">
                                        <i class="fas fa-toggle-on label-icon"></i>
                                        Augmentation Status
                                    </label>
                                    <div class="switch-container">
                                        <input
                                            type="checkbox"
                                            id="is_active"
                                            v-model="form.is_active"
                                            class="switch-input"
                                            />
                                        <label for="is_active" class="switch-label">
                                            <span class="switch-slider"></span>
                                        </label>
                                        <span class="switch-text">
                                            {{ form.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p v-if="errors.is_active" class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ errors.is_active[0] }}
                                    </p>
                                </div>


                            </div>
                                </div>



                          


                            <div class="form-group">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left label-icon"></i>
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="form-textarea"
                                    :class="{ 'input-error': errors.description }"
                                    placeholder="Enter service description..."
                                ></textarea>
                                <p v-if="errors.description" class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ errors.description[0] }}
                                </p>
                            </div>


                            <div class="modal-footer">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="btn btn-secondary"
                                >
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="submitting"
                                >
                                    <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-save"></i>
                                    {{ isEditMode ? 'Update Service' : 'Create Service' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>
<style scoped>
/* All your existing CSS styles remain here, as they are well-crafted.
   No changes are needed in the CSS for the toggle functionality itself. */
/* Enhanced Modal Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
.time-range-box {
  border: 1px solid #dcdcdc;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1.25rem;   /* keeps spacing consistent with other groups */
}
.modal-fade-enter-active .modal-container,
.modal-fade-leave-active .modal-container {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-fade-enter-from .modal-container,
.modal-fade-leave-to .modal-container {
    transform: scale(0.9) translateY(-20px);
}

/* Enhanced Modal Overlay */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.8), rgba(55, 65, 81, 0.6));
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 50;
    padding: 1rem;
}

/* Enhanced Modal Container */
.modal-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 1rem;
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.1);
    width: 100%;
    max-width: 42rem;
    max-height: 90vh;
    overflow: hidden;
    position: relative;
}

.modal-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
}

/* Enhanced Header */
.modal-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 2rem;
    border-bottom: 1px solid #e2e8f0;
    position: relative;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.modal-icon {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.modal-subtitle {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0.25rem 0 0 0;
}

.modal-close-button {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 2.5rem;
    height: 2.5rem;
    border: none;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 0.5rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    backdrop-filter: blur(4px);
}

.modal-close-button:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    transform: scale(1.1);
}

/* Enhanced Body */
.modal-body {
    padding: 2rem;
    max-height: calc(90vh - 8rem);
    overflow-y: auto;
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Enhanced Form Groups */
.form-group {
    position: relative;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.label-icon {
    color: #6b7280;
    font-size: 0.875rem;
}

.required-star {
    color: #ef4444;
    font-weight: 700;
}

/* Enhanced Form Inputs */
.form-input,
.form-textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: #9ca3af;
}

.input-error {
    border-color: #ef4444;
}

.input-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Enhanced Image Upload */
.image-upload-container {
    position: relative;
}

.file-upload-label {
    display: block;
    cursor: pointer;
}

.upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 0.75rem;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}

.upload-area:hover {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.upload-icon {
    font-size: 2rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.upload-text {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.upload-subtext {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Enhanced Image Preview */
.image-preview-container {
    margin-top: 1rem;
}

.image-preview-wrapper {
    position: relative;
    display: inline-block;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.image-preview {
    width: 10rem;
    height: 8rem;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-preview-wrapper:hover .image-overlay {
    opacity: 1;
}

.remove-image-btn {
    background: #ef4444;
    color: white;
    border: none;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.remove-image-btn:hover {
    transform: scale(1.1);
}

/* Enhanced Form Rows */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

/* Enhanced Switch */
.switch-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.25rem;
}

.switch-input {
    display: none; /* Keep this hidden */
}

.switch-label {
    position: relative;
    width: 3rem;
    height: 1.5rem;
    background: #d1d5db;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.switch-slider {
    position: absolute;
    top: 0.125rem;
    left: 0.125rem;
    width: 1.25rem;
    height: 1.25rem;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* These are the crucial CSS rules for the visual switch */
.switch-input:checked + .switch-label {
    background: linear-gradient(135deg, #10b981, #059669);
}

.switch-input:checked + .switch-label .switch-slider {
    transform: translateX(1.5rem);
}

.switch-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

/* Enhanced Error Messages */
.error-message {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 0.25rem;
    border-left: 3px solid #ef4444;
}

/* Enhanced Footer */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5rem;
}

/* Enhanced Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #e5e7eb, #d1d5db);
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 640px) {
    .modal-overlay {
        padding: 0.5rem;
    }

    .modal-header,
    .modal-body {
        padding: 1.5rem;
    }

    .modal-title {
        font-size: 1.25rem;
    }

    .modal-footer {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Custom Scrollbar */
.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>