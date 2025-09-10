<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../toster'; // Adjust path if necessary

const toaster = useToastr();

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    pavilionData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'pavilion-updated', 'pavilion-added']);

const form = ref({
    id: null,
    name: '',
    description: '',
    image_url: '', // This will hold the URL from the backend
    image_file: null, // This will hold the File object for upload
    service_ids: [],
});

const allServices = ref([]);
const isEditMode = computed(() => !!form.value.id);
const errors = ref({});

// Fetch all services when the component is mounted
onMounted(async () => {
    try {
        const response = await axios.get('/api/services');
        allServices.value = response.data.data;
    } catch (error) {
        console.error('Error fetching services:', error);
        toaster.error('Failed to load services.');
    }
});

watch(() => props.showModal, (newVal) => {
    if (newVal) {
        if (props.pavilionData) {
            form.value = {
                ...props.pavilionData,
                service_ids: props.pavilionData.services ? props.pavilionData.services.map(s => s.id) : [],
                image_file: null, // Reset file input when opening modal
            };
        } else {
            // Reset form for new pavilion
            form.value = {
                id: null,
                name: '',
                description: '',
                image_url: '',
                image_file: null, // Reset file input
                service_ids: [],
            };
        }
        errors.value = {}; // Clear errors when modal opens
    }
});

const handleImageUpload = (event) => {
    form.value.image_file = event.target.files[0];
};

const savePavilion = async () => {
    errors.value = {}; // Clear previous errors

    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('description', form.value.description);
    // Append image file if selected
    if (form.value.image_file) {
        formData.append('image', form.value.image_file);
    }
    // Append service_ids (handle array correctly for FormData)
    form.value.service_ids.forEach(id => {
        formData.append('service_ids[]', id);
    });

    try {
        if (isEditMode.value) {
            // For PUT requests with file uploads, you need to spoof the method
            formData.append('_method', 'PUT');
            await axios.post(`/api/pavilions/${form.value.id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            toaster.success('Pavilion updated successfully!');
            emit('pavilion-updated');
        } else {
            await axios.post('/api/pavilions', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            toaster.success('Pavilion added successfully!');
            emit('pavilion-added');
        }
        closeModal(); // Close modal after successful save
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
            toaster.error('Validation failed. Please check your inputs.');
        } else {
            toaster.error(error.response?.data?.message || 'An error occurred while saving the pavilion.');
            console.error('Error saving pavilion:', error);
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
                <h3 class="modal-title">{{ isEditMode ? 'Edit Pavilion' : 'Add New Pavilion' }}</h3>
                <button class="close-button" @click="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="savePavilion">
                    <div class="form-group">
                        <label for="name">Pavilion Name<span class="required">*</span></label>
                        <input type="text" id="name" v-model="form.name" :class="{ 'is-invalid': errors.name }" required>
                        <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" v-model="form.description" :class="{ 'is-invalid': errors.description }"></textarea>
                        <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="image">Pavilion Image</label>
                        <input type="file" id="image" @change="handleImageUpload" accept="image/*" :class="{ 'is-invalid': errors.image }">
                        <div v-if="errors.image" class="invalid-feedback">{{ errors.image[0] }}</div>
                        <div v-if="form.image_url && !form.image_file" class="current-image-preview mt-2">
                            <p>Current Image:</p>
                            <img :src="form.image_url" alt="Current Pavilion Image" style="max-width: 150px; height: auto; border-radius: 8px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="services">Associated Services</label>
                        <select id="services" multiple v-model="form.service_ids" :class="{ 'is-invalid': errors.service_ids }" class="multi-select">
                            <option v-for="service in allServices" :key="service.id" :value="service.id">
                                {{ service.name }}
                            </option>
                        </select>
                        <div v-if="errors.service_ids" class="invalid-feedback">{{ errors.service_ids[0] }}</div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="cancel-button" @click="closeModal">Cancel</button>
                        <button type="submit" class="save-button">{{ isEditMode ? 'Update Pavilion' : 'Add Pavilion' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* (Keep your existing modal and form styling, only adding the necessary) */
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
.form-group select.multi-select {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 1rem;
    color: #334155;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
}

/* Specific styling for file input to match others */
.form-group input[type="file"] {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 1rem;
    color: #334155;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
    cursor: pointer; /* Indicate it's clickable */
    background-color: #f8fafc; /* Light background for file input */
}

.form-group input[type="file"]::-webkit-file-upload-button {
    background: #e2e8f0;
    border: 1px solid #cbd5e1;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    cursor: pointer;
    margin-right: 1rem;
}

.form-group textarea {
    min-height: 90px; /* Adjust as needed */
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select.multi-select:focus,
.form-group input[type="file"]:focus { /* Added file input focus style */
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

.multi-select {
    min-height: 100px;
}

.current-image-preview {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px dashed #cbd5e1;
    border-radius: 0.5rem;
    background-color: #f0f4f8;
    text-align: center;
}
.current-image-preview p {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #334155;
}
.current-image-preview img {
    display: block; /* Center image */
    margin: 0 auto;
}
</style>