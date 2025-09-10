<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../toster';

const toaster = useToastr();

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    roomTypeData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'room-type-updated', 'room-type-added']);

const form = ref({
    id: null,
    name: '',
    description: '',
    image_url: '',
    room_type: '', // Default to false (Normal Room)
    service_id: null, // Change to single service ID, default to null
});

const selectedFile = ref(null);
const imagePreview = ref(null);
const isUploading = ref(false);
const availableServices = ref([]); // To store services fetched from API

const isEditMode = computed(() => !!form.value.id);
const errors = ref({});

// Fetch services when the component is mounted
onMounted(() => {
    fetchServices();
});

watch(() => props.showModal, (newVal) => {
    if (newVal) {
        if (props.roomTypeData) {
            form.value = {
                ...props.roomTypeData,
                // Get the first service ID if services exist, otherwise null
                service_id: props.roomTypeData.services && props.roomTypeData.services.length > 0
                              ? props.roomTypeData.services[0].id
                              : null,
                // Convert room_type to a boolean for the select dropdown
                room_type: !!props.roomTypeData.room_type
            };
            imagePreview.value = props.roomTypeData.image_url || null;
        } else {
            // Reset form for new room type
            form.value = {
                id: null,
                name: '',
                description: '',
                image_url: '',
                room_type: '', // Reset to default (Normal Room)
                service_id: null, // Reset to null for single selection
            };
            imagePreview.value = null;
        }
        selectedFile.value = null;
        errors.value = {};
    }
});

// Function to fetch services
const fetchServices = async () => {
    try {
        const response = await axios.get('/api/services');
        availableServices.value = response.data.data;
    } catch (error) {
        toaster.error('Failed to load services.');
        console.error('Error fetching services:', error);
    }
};

// Handle file selection
const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (!file.type.startsWith('image/')) {
            toaster.error('Please select a valid image file.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) { // 5MB
            toaster.error('Image size should be less than 5MB.');
            return;
        }

        selectedFile.value = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Remove image
const removeImage = () => {
    selectedFile.value = null;
    imagePreview.value = null;
    form.value.image_url = ''; // Clear the stored image URL
    // Reset file input element directly
    const fileInput = document.getElementById('image-upload');
    if (fileInput) fileInput.value = '';
};


const saveRoomType = async () => {
    errors.value = {};
    try {
        const formData = new FormData();

        formData.append('name', form.value.name);
        formData.append('description', form.value.description);
        formData.append('room_type', form.value.room_type);

        // Append only the single selected service ID if it exists
        if (form.value.service_id) {
            formData.append('service_id', form.value.service_id);
        } else {
             // If no service is selected, ensure the backend doesn't try to associate an old one
             // You might need to check your backend's behavior if service_id can be null.
             // For example, if it's an optional field, just don't append it.
             // If it's required and can be unselected, you might send an explicit null/empty string.
        }


        if (selectedFile.value) {
            formData.append('image', selectedFile.value);
        } else if (isEditMode.value && !imagePreview.value && form.value.image_url) {
            // This case handles explicit removal of an existing image without new upload
            formData.append('remove_image', '1');
        } else if (isEditMode.value && imagePreview.value && !selectedFile.value) {
            // If in edit mode, and there's a preview but no new file, it means the old image is kept.
            // No need to append anything for the image in this case.
        }

        let response;
        if (isEditMode.value) {
            // For PUT/PATCH requests with FormData, you often need to spoof the method
            formData.append('_method', 'PUT');
            response = await axios.post(`/api/room-types/${form.value.id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            toaster.success('Room type updated successfully!');
            emit('room-type-updated');
        } else {
            response = await axios.post('/api/room-types', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            toaster.success('Room type added successfully!');
            emit('room-type-added');
        }
        closeModal(); // Close modal on successful save
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
            toaster.error('Validation failed. Please check your inputs.');
        } else {
            toaster.error(error.response?.data?.message || 'An error occurred while saving the room type.');
            console.error('Error saving room type:', error);
        }
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ isEditMode ? 'Edit Room Type' : 'Add New Room Type' }}</h3>
                <button class="close-button" @click="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="saveRoomType">
                    <div class="form-group">
                        <label for="name">Room Type Name<span class="required">*</span></label>
                        <input type="text" id="name" v-model="form.name" :class="{ 'is-invalid': errors.name }" required>
                        <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" v-model="form.description" :class="{ 'is-invalid': errors.description }"></textarea>
                        <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="room_type_category">Room Category<span class="required">*</span></label>
                        <select
                            id="room_type_category"
                            v-model="form.room_type"
                            :class="{ 'is-invalid': errors.room_type }"
                            required
                        >
                            <option :value="'Normal'">Normal Room</option>
                            <option :value="'WaitingRoom'">Waiting Room</option>
                        </select>
                        <div v-if="errors.room_type" class="invalid-feedback">{{ errors.room_type[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="service">Associated Service</label>
                        <select
                            id="service"
                            v-model="form.service_id"
                            :class="{ 'is-invalid': errors.service_id }"
                            class="single-select"
                        >
                            <option :value="null">-- Select a Service --</option> <option v-for="service in availableServices" :key="service.id" :value="service.id">
                                {{ service.name }}
                            </option>
                        </select>
                        <div v-if="errors.service_id" class="invalid-feedback">{{ errors.service_id[0] }}</div>
                        </div>

                    <div class="form-group">
                        <label for="image-upload">Room Type Image</label>
                        <div class="image-upload-container">
                            <div v-if="imagePreview" class="image-preview">
                                <img :src="imagePreview" alt="Room type preview" class="preview-image">
                                <button type="button" class="remove-image-btn" @click="removeImage">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div v-else class="upload-area">
                                <label for="image-upload" class="upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to upload image</span>
                                    <input
                                        id="image-upload"
                                        type="file"
                                        accept="image/*"
                                        @change="handleFileSelect"
                                        class="file-input"
                                    >
                                </label>
                                <p class="upload-hint">JPG, PNG or GIF (Max 5MB)</p>
                            </div>
                        </div>
                        <div v-if="errors.image" class="invalid-feedback">{{ errors.image[0] }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="cancel-button" @click="closeModal">Cancel</button>
                        <button type="submit" class="save-button">{{ isEditMode ? 'Update Room Type' : 'Add Room Type' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Reuse the modal styles from your PavilionModel.vue */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 600px;
    animation: fadeInScale 0.3s ease-out;
    display: flex;
    flex-direction: column;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.close-button {
    background: none;
    border: none;
    font-size: 2rem;
    color: #64748b;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: color 0.2s ease;
}

.close-button:hover {
    color: #475569;
}

.modal-body {
    padding: 1.75rem;
    overflow-y: auto; /* Enable scrolling for longer forms */
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.5rem;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select { /* Apply styles to select as well */
    width: 100%;
    padding: 0.85rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 1rem;
    color: #334155;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box; /* Ensures padding doesn't add to total width */
}

.form-group textarea {
    min-height: 90px; /* Adjust as needed */
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus { /* Apply focus styles to select */
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group .is-invalid {
    border-color: #ef4444;
}

.invalid-feedback {
    color: #ef4444;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

/* single-select specific styles (removed multi-select height) */
.single-select {
    /* No specific min-height needed, standard select height is fine */
}

.hint {
    font-size: 0.85rem;
    color: #64748b;
    margin-top: 0.5rem;
}

.image-upload-container {
    margin-top: 0.5rem;
}

.upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.upload-area:hover {
    border-color: #3b82f6;
    background-color: #f8fafc;
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
}

.upload-label i {
    font-size: 2rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.upload-label span {
    color: #475569;
    font-weight: 500;
}

.upload-hint {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.5rem;
}

.file-input {
    display: none;
}

.image-preview {
    position: relative;
    width: 100%;
    max-width: 300px;
    margin: 0 auto;
}

.preview-image {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.remove-image-btn {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #ef4444;
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-image-btn:hover {
    background-color: #dc2626;
    transform: scale(1.1);
}
.required {
    color: #ef4444;
    margin-left: 0.25rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 1.25rem 1.75rem;
    border-top: 1px solid #e2e8f0;
    background-color: #f8fafc;
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
    gap: 0.75rem;
}

.cancel-button,
.save-button {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    border: none;
}

.cancel-button {
    background-color: #cbd5e1;
    color: #475569;
}

.cancel-button:hover {
    background-color: #94a3b8;
    color: #1e293b;
}

.save-button {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
}

.save-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 10px rgba(59, 130, 246, 0.3);
}
</style>