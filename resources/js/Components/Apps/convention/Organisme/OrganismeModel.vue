<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import axios from 'axios'; // Import axios for API calls

const props = defineProps({
    // Prop to control modal visibility
    isVisible: {
        type: Boolean,
        default: false
    },
    // Prop for the organisme data when editing (optional)
    organisme: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['save', 'close', 'organisme-added', 'organisme-updated']);

// Local reactive state for the form
const formData = ref({
    id: null,
    name: '',
    legal_form: '',
     organism_color: '#3b82f6', // Default or empty string
    trade_register_number: '',
    tax_id_nif: '',
    statistical_id: '',
    article_number: '',
    wilaya: '',
    address: '',
    postal_code: '',
    phone: '',
    fax: '',
    mobile: '',
    email: '',
    website: '',
    latitude: null,
    longitude: null,
    initial_invoice_number: '',
    initial_credit_note_number: '',
    logo_url: '', // For displaying existing logo
    profile_image_url: '', // For displaying existing profile image (if used)
    description: '',
    industry: '',
    creation_date: '',
    number_of_employees: null,
});

// Local state for image file and preview
const logoFile = ref(null);
const logoPreviewUrl = ref(null);
const profileImageFile = ref(null);
const profileImagePreviewUrl = ref(null);


const isEditMode = ref(false);
const submitting = ref(false); // To manage loading state for buttons
const errors = ref({}); // For validation errors from the backend

// Function to reset the form data
const resetForm = () => {
    formData.value = {
        id: null,
        name: '',
        legal_form: '',
        trade_register_number: '',
        tax_id_nif: '',
        statistical_id: '',
        article_number: '',
        wilaya: '',
        address: '',
        postal_code: '',
        phone: '',
        fax: '',
        mobile: '',
        email: '',
        website: '',
        latitude: null,
        longitude: null,
        initial_invoice_number: '',
        initial_credit_note_number: '',
        logo_url: '',
        profile_image_url: '',
        description: '',
        industry: '',
        creation_date: '',
        number_of_employees: null,
    };
    logoFile.value = null;
    logoPreviewUrl.value = null;
    profileImageFile.value = null;
    profileImagePreviewUrl.value = null;
    errors.value = {}; // Clear errors on reset
};

// Watch for changes in the `organisme` prop to populate the form for editing
watch(() => props.organisme, (newVal) => {
    if (newVal) {
        // Deep copy to avoid direct mutation of prop
        formData.value = { ...newVal };
        isEditMode.value = !!newVal.id;
        logoPreviewUrl.value = newVal.logo_url;
        profileImagePreviewUrl.value = newVal.profile_image_url;
    } else {
        // Reset form for new entry
        resetForm();
        isEditMode.value = false;
    }
}, { immediate: true }); // Immediate ensures it runs on initial mount if organisme is provided

// Handle image upload for logo
const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        logoFile.value = null;
        // Keep existing image if no new file selected and we are in edit mode
        logoPreviewUrl.value = formData.value.logo_url;
    }
};

// Handle image upload for profile image
const handleProfileImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        profileImageFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            profileImagePreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        profileImageFile.value = null;
        profileImagePreviewUrl.value = formData.value.profile_image_url;
    }
};

// Handle form submission
const handleSubmit = async () => {
    submitting.value = true;
    errors.value = {}; // Clear previous errors

    const dataToSend = new FormData();

    // Append all form fields
    for (const key in formData.value) {
        if (formData.value[key] !== null && formData.value[key] !== undefined) {
            dataToSend.append(key, formData.value[key]);
        }
    }

    // Append image files if they exist
    if (logoFile.value) {
        dataToSend.append('logo', logoFile.value); // Use 'logo' as the field name for the backend
    } else if (isEditMode.value && !formData.value.logo_url) {
        // If in edit mode and logo was cleared, tell backend to remove it
        dataToSend.append('remove_logo', 'true');
    }

    if (profileImageFile.value) {
        dataToSend.append('profile_image', profileImageFile.value); // Use 'profile_image' as the field name
    } else if (isEditMode.value && !formData.value.profile_image_url) {
        dataToSend.append('remove_profile_image', 'true');
    }

    try {
        let response;
        if (isEditMode.value) {
            // For PUT/PATCH requests with FormData, Laravel often expects _method field
            dataToSend.append('_method', 'PUT'); // Or 'PATCH' depending on your backend
            response = await axios.post(`/api/organismes/${formData.value.id}`, dataToSend, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            console.log('Organisme updated successfully!', response.data); // Replace with toaster.success
            emit('organisme-updated', response.data.data || response.data);
        } else {
            response = await axios.post('/api/organismes', dataToSend, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            console.log('Organisme added successfully!', response.data); // Replace with toaster.success
            emit('organisme-added', response.data.data || response.data);
        }
        closeModal();
    } catch (err) {
        console.error('Error submitting organisme:', err);
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors;
            console.error('Validation errors:', errors.value); // Replace with toaster.error
        } else {
            console.error('An error occurred:', err.response?.data?.message || 'Failed to perform operation.'); // Replace with toaster.error
        }
    } finally {
        submitting.value = false;
    }
};

// Handle modal close
const closeModal = () => {
    emit('close');
    resetForm(); // Reset form when closing
};
</script>

<template>
    <teleport to="body">
        <transition name="modal-fade">
            <div v-if="isVisible" class="modal-overlay">
                <div class="modal-container" @click.stop>
                    <div class="modal-header">
                        <div class="header-content">
                            <div class="modal-icon">
                                <i class="fas fa-building"></i> <!-- Changed icon to building -->
                            </div>
                            <div>
                                <h3 class="modal-title">
                                    {{ isEditMode ? 'Edit Corporate Partner' : 'Add New Corporate Partner' }}
                                </h3>
                                <p class="modal-subtitle">
                                    {{ isEditMode ? 'Update your corporate partner information' : 'Create a new corporate partner entry' }}
                                </p>
                            </div>
                        </div>
                        <button @click="closeModal" class="modal-close-button">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <form @submit.prevent="handleSubmit" class="modal-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-tag label-icon"></i>
                                        Partner Name <span class="required-star">*</span>
                                    </label>
                                    <input type="text" id="name" v-model="formData.name" class="form-input"
                                        :class="{ 'input-error': errors.name }" placeholder="Enter partner name" required>
                                    <p v-if="errors.name" class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ errors.name[0] }}
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope label-icon"></i>
                                        Email <span class="required-star">*</span>
                                    </label>
                                    <input type="email" id="email" v-model="formData.email" class="form-input"
                                        :class="{ 'input-error': errors.email }" placeholder="Enter email address" required>
                                    <p v-if="errors.email" class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ errors.email[0] }}
                                    </p>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone-alt label-icon"></i>
                                        Phone
                                    </label>
                                    <input type="tel" id="phone" v-model="formData.phone" class="form-input" placeholder="Enter phone number">
                                </div>
                                <div class="form-group">
                                    <label for="mobile" class="form-label">
                                        <i class="fas fa-mobile-alt label-icon"></i>
                                        Mobile
                                    </label>
                                    <input type="tel" id="mobile" v-model="formData.mobile" class="form-input" placeholder="Enter mobile number">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fax" class="form-label">
                                        <i class="fas fa-fax label-icon"></i>
                                        Fax
                                    </label>
                                    <input type="tel" id="fax" v-model="formData.fax" class="form-input" placeholder="Enter fax number">
                                </div>
                                <div class="form-group">
                                    <label for="website" class="form-label">
                                        <i class="fas fa-globe label-icon"></i>
                                        Website
                                    </label>
                                    <input type="url" id="website" v-model="formData.website" class="form-input" placeholder="Enter website URL">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt label-icon"></i>
                                    Address
                                </label>
                                <input type="text" id="address" v-model="formData.address" class="form-input" placeholder="Enter street address">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="wilaya" class="form-label">
                                        <i class="fas fa-city label-icon"></i>
                                        Wilaya
                                    </label>
                                    <input type="text" id="wilaya" v-model="formData.wilaya" class="form-input" placeholder="Enter wilaya/state">
                                </div>
                                <div class="form-group">
                                    <label for="postal_code" class="form-label">
                                        <i class="fas fa-mail-bulk label-icon"></i>
                                        Postal Code
                                    </label>
                                    <input type="text" id="postal_code" v-model="formData.postal_code" class="form-input" placeholder="Enter postal code">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="legal_form" class="form-label">
                                        <i class="fas fa-gavel label-icon"></i>
                                        Legal Form
                                    </label>
                                    <input type="text" id="legal_form" v-model="formData.legal_form" class="form-input" placeholder="e.g., SARL, SPA, EURL">
                                </div>
                                <div class="form-group">
                                    <label for="industry" class="form-label">
                                        <i class="fas fa-industry label-icon"></i>
                                        Industry
                                    </label>
                                    <input type="text" id="industry" v-model="formData.industry" class="form-input" placeholder="e.g., IT, Healthcare, Manufacturing">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="trade_register_number" class="form-label">
                                        <i class="fas fa-file-invoice label-icon"></i>
                                        Trade Register Number
                                    </label>
                                    <input type="text" id="trade_register_number" v-model="formData.trade_register_number" class="form-input" placeholder="Enter trade register number">
                                </div>
                                <div class="form-group">
                                    <label for="tax_id_nif" class="form-label">
                                        <i class="fas fa-money-check-alt label-icon"></i>
                                        Tax ID (NIF)
                                    </label>
                                    <input type="text" id="tax_id_nif" v-model="formData.tax_id_nif" class="form-input" placeholder="Enter tax identification number">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="statistical_id" class="form-label">
                                        <i class="fas fa-chart-bar label-icon"></i>
                                        Statistical ID
                                    </label>
                                    <input type="text" id="statistical_id" v-model="formData.statistical_id" class="form-input" placeholder="Enter statistical ID">
                                </div>
                                <div class="form-group">
                                    <label for="article_number" class="form-label">
                                        <i class="fas fa-file-alt label-icon"></i>
                                        Article Number
                                    </label>
                                    <input type="text" id="article_number" v-model="formData.article_number" class="form-input" placeholder="Enter article number">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="initial_invoice_number" class="form-label">
                                        <i class="fas fa-hashtag label-icon"></i>
                                        Initial Invoice Number
                                    </label>
                                    <input type="text" id="initial_invoice_number" v-model="formData.initial_invoice_number" class="form-input" placeholder="Enter initial invoice number">
                                </div>
                                <div class="form-group">
                                    <label for="initial_credit_note_number" class="form-label">
                                        <i class="fas fa-receipt label-icon"></i>
                                        Initial Credit Note Number
                                    </label>
                                    <input type="text" id="initial_credit_note_number" v-model="formData.initial_credit_note_number" class="form-input" placeholder="Enter initial credit note number">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="creation_date" class="form-label">
                                        <i class="fas fa-calendar-alt label-icon"></i>
                                        Creation Date
                                    </label>
                                    <input type="date" id="creation_date" v-model="formData.creation_date" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="number_of_employees" class="form-label">
                                        <i class="fas fa-users label-icon"></i>
                                        Number of Employees
                                    </label>
                                    <input type="number" id="number_of_employees" v-model.number="formData.number_of_employees" class="form-input" placeholder="Enter number of employees">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left label-icon"></i>
                                    Description
                                </label>
                                <textarea id="description" v-model="formData.description" rows="4" class="form-textarea"
                                    placeholder="Enter corporate partner description..."></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="logo_upload" class="form-label">
                                        <i class="fas fa-image label-icon"></i>
                                        Logo Image
                                    </label>
                                    <div class="image-upload-container">
                                        <input type="file" id="logo_upload" @change="handleLogoUpload" accept="image/*" class="form-file-input" hidden>
                                        <label for="logo_upload" class="file-upload-label">
                                            <div class="upload-area">
                                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                                <span class="upload-text">Click to upload logo</span>
                                                <span class="upload-subtext">PNG, JPG, GIF up to 10MB</span>
                                            </div>
                                        </label>
                                        <p v-if="errors.logo" class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ errors.logo[0] }}
                                        </p>
                                        <div v-if="logoPreviewUrl" class="image-preview-container">
                                            <div class="image-preview-wrapper">
                                                <img :src="logoPreviewUrl" alt="Logo Preview" class="image-preview">
                                                <div class="image-overlay">
                                                    <button type="button" @click="logoFile = null; logoPreviewUrl = null; formData.logo_url = ''" class="remove-image-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="profile_image_upload" class="form-label">
                                        <i class="fas fa-portrait label-icon"></i>
                                        Profile Image
                                    </label>
                                    <div class="image-upload-container">
                                        <input type="file" id="profile_image_upload" @change="handleProfileImageUpload" accept="image/*" class="form-file-input" hidden>
                                        <label for="profile_image_upload" class="file-upload-label">
                                            <div class="upload-area">
                                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                                <span class="upload-text">Click to upload profile image</span>
                                                <span class="upload-subtext">PNG, JPG, GIF up to 10MB</span>
                                            </div>
                                        </label>
                                        <p v-if="errors.profile_image" class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ errors.profile_image[0] }}
                                        </p>
                                        <div v-if="profileImagePreviewUrl" class="image-preview-container">
                                            <div class="image-preview-wrapper">
                                                <img :src="profileImagePreviewUrl" alt="Profile Preview" class="image-preview">
                                                <div class="image-overlay">
                                                    <button type="button" @click="profileImageFile = null; profileImagePreviewUrl = null; formData.profile_image_url = ''" class="remove-image-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Location/Map Coordinates -->
                            <div class="time-range-box">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="latitude" class="form-label">
                                            <i class="fas fa-map-pin label-icon"></i>
                                            Latitude
                                        </label>
                                        <input type="number" step="any" id="latitude" v-model.number="formData.latitude" class="form-input" placeholder="Enter latitude">
                                    </div>
                                    <div class="form-group">
                                        <label for="longitude" class="form-label">
                                            <i class="fas fa-map-pin label-icon"></i>
                                            Longitude
                                        </label>
                                        <input type="number" step="any" id="longitude" v-model.number="formData.longitude" class="form-input" placeholder="Enter longitude">
                                    </div>
                                </div>
                            </div>

                            <!-- Company Color Picker -->
                            <div class="form-group">
                                <label for="organism_color" class="form-label">
                                    <i class="fas fa-palette label-icon"></i>
                                    Company Color
                                </label>
                                <input
                                    type="color"
                                    id="organism_color"
                                    v-model="formData.organism_color"
                                    class="form-input"
                                    style="width: 60px; height: 40px; padding: 0; border: none; background: none;"
                                >
                                <span v-if="formData.organism_color" class="color-hex">{{ formData.organism_color }}</span>
                                <p v-if="errors.organism_color" class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ errors.organism_color[0] }}
                                </p>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" @click="closeModal" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary" :disabled="submitting">
                                    <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-save"></i>
                                    {{ isEditMode ? 'Update Partner' : 'Add Partner' }}
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
/* All your existing CSS styles from the serviceModel are copied here.
   I've made sure these styles are applied to the appropriate elements
   in the OrganismeModal.vue component. */

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
    margin-bottom: 1.25rem;    /* keeps spacing consistent with other groups */
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

/* Enhanced Switch (Not directly used in OrganismeModal but kept if needed for other boolean fields) */
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
