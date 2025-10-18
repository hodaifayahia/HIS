<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { defineProps, defineEmits, defineExpose } from 'vue';
import PatientModel from '../../../Components/PatientModel.vue';

const toaster = useToastr();
const props = defineProps({
    patientId: {
        type: [Number, String],
        required: true
    },
    consultationId: {
        type: [Number, String],
        required: true
    },
    consultationName: {
        type: String,
        default: ''
    }
});

const consultation_name = ref(props.consultationName || '');
const emit = defineEmits(['patientFetched', 'update:consultation-name']);

const patient = ref(null);
const loading = ref(true);
const error = ref(false);
const isEditing = ref(false);

// Modal state
const isModalOpen = ref(false);
const selectedPatient = ref(null);

// Computed properties for better UX
const consultationNameValid = computed(() => {
    return consultation_name.value && consultation_name.value.length >= 3;
});

const patientInitials = computed(() => {
    if (!patient.value) return '';
    const first = patient.value.first_name?.charAt(0) || '';
    const last = patient.value.last_name?.charAt(0) || '';
    return (first + last).toUpperCase();
});

const patientFullName = computed(() => {
    if (!patient.value) return '';
    return `${patient.value.first_name || ''} ${patient.value.last_name || ''}`.trim();
});

const fetchPatientInfo = async () => {
    try {
        loading.value = true;
        error.value = false;
        const response = await axios.get(`/api/patients/${props.patientId}`);
        patient.value = response.data.data;
        emit('patientFetched', patient.value);
    } catch (err) {
        console.error('Error fetching patient:', err);
        toaster.error('Failed to load patient information. Please try again.');
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const openModalForEdit = () => {
    selectedPatient.value = patient.value;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedPatient.value = null;
};

const refreshPatients = () => {
    fetchPatientInfo();
};

watch(consultation_name, (newVal) => {
    emit('update:consultation-name', newVal);
}, { immediate: true });

watch(() => props.consultationName, (newVal) => {
    if (newVal !== consultation_name.value) {
        consultation_name.value = newVal || '';
    }
});

const updateConsultation = async () => {
    if (!consultationNameValid.value) {
        toaster.error('Consultation name must be at least 3 characters.');
        return;
    }
    
    isEditing.value = true;
    try {
        await axios.put(`/api/consultations/${props.consultationId}`, {
            consultation_name: consultation_name.value
        });
        toaster.success('Consultation name updated successfully.');
        emit('update:consultation-name', consultation_name.value);
    } catch (err) {
        console.error('Error updating consultation:', err);
        toaster.error('Failed to update consultation name. Please try again.');
    } finally {
        isEditing.value = false;
    }
};

defineExpose({ updateConsultation, consultation_name });

const calculateAge = (dateOfBirth) => {
    const today = new Date();
    const birthDate = new Date(dateOfBirth);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const formatPhoneNumber = (phoneNumber) => {
    if (!phoneNumber) return 'Not provided';
    const cleaned = ('' + phoneNumber).replace(/\D/g, '');
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
    if (match) {
        return `(${match[1]}) ${match[2]}-${match[3]}`;
    }
    return phoneNumber;
};

const capitalize = (str) => {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
};

onMounted(() => {
    fetchPatientInfo();
});
</script>

<template>
    <div class="patient-consultation-container">
        <!-- Enhanced Consultation Section -->
        <div class="consultation-section">
            
            <div class="consultation-input-group">
                <label for="consultation_name" class="consultation-label">
                    Consultation Name
                    <span class="required-indicator">*</span>
                </label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="consultation_name" 
                        v-model="consultation_name" 
                        class="consultation-input"
                        :class="{ 
                            'valid': consultationNameValid, 
                            'invalid': consultation_name && !consultationNameValid 
                        }"
                        placeholder="Enter consultation name (min. 3 characters)" 
                        maxlength="100"
                    />
                  
                </div>
                <div class="input-feedback">
                    <div v-if="consultation_name && !consultationNameValid" class="validation-message error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        Please enter at least 3 characters
                    </div>
                    <div v-else-if="consultationNameValid" class="validation-message success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                        Consultation name looks good
                    </div>
                    <div class="character-count">
                        {{ consultation_name.length }}/100 characters
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Patient Info Container -->
        <div class="patient-info-container">
            <div v-if="loading" class="status-container loading-state">
                <div class="loading-animation">
                    <div class="pulse-circle"></div>
                    <div class="pulse-circle pulse-delayed"></div>
                    <div class="pulse-circle pulse-delayed-2"></div>
                </div>
                <h4>Loading Patient Information</h4>
                <p>Please wait while we fetch the patient details...</p>
            </div>

            <div v-else-if="error" class="status-container error-state">
                <div class="error-illustration">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <h4>Unable to Load Patient Data</h4>
                <p>We encountered an issue while fetching patient information.</p>
                <button @click="fetchPatientInfo" class="retry-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23,4 23,10 17,10"/>
                        <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"/>
                    </svg>
                    Try Again
                </button>
            </div>

            <div v-else-if="patient" class="patient-display">
                <!-- Enhanced Patient Header -->
                <div class="patient-header">
                    <div class="header-background"></div>
                    <div class="header-content">
                        <div class="patient-avatar-section">
                            <div class="avatar-container">
                                <img 
                                    v-if="patient.gender && patient.gender.toLowerCase() === 'male'" 
                                    src="/storage/man.png"
                                    alt="Male Patient" 
                                    class="avatar-image" 
                                />
                                <img 
                                    v-else-if="patient.gender && patient.gender.toLowerCase() === 'female'"
                                    src="/storage/woman.png" 
                                    alt="Female Patient" 
                                    class="avatar-image" 
                                />
                                <div v-else class="avatar-placeholder">
                                    <span class="avatar-initials">{{ patientInitials }}</span>
                                </div>
                                <div class="avatar-status online"></div>
                            </div>
                        </div>
                        
                        <div class="patient-info-section">
                            <div class="patient-name-group">
                                <h2 class="patient-name">{{ patientFullName }}</h2>
                                <div class="patient-badges">
                                    <span class="badge primary">ID: {{ props.patientId }}</span>
                                    <span v-if="patient.gender" class="badge secondary">
                                        {{ capitalize(patient.gender) }}
                                    </span>
                                </div>
                            </div>
                            <div class="quick-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Age</span>
                                    <span class="stat-value">{{ calculateAge(patient.dateOfBirth) }} years</span>
                                </div>
                                <div class="stat-divider"></div>
                                <div class="stat-item">
                                    <span class="stat-label">Last Visit</span>
                                    <span class="stat-value">{{ new Date().toLocaleDateString() }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="header-actions">
                            <button @click="openModalForEdit" class="edit-patient-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit Patient
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Patient Details -->
                <div class="patient-details">
                    <div class="details-header">
                        <h4>Patient Information</h4>
                        <div class="details-toggle">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="details-grid">
                        <div class="detail-card">
                            <div class="detail-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="detail-content">
                                <span class="detail-label">Full Name</span>
                                <span class="detail-value">{{ patientFullName || 'Not provided' }}</span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <div class="detail-content">
                                <span class="detail-label">Date of Birth</span>
                                <span class="detail-value">
                                    {{ new Date(patient.dateOfBirth).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) }}
                                </span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                            </div>
                            <div class="detail-content">
                                <span class="detail-label">Phone Number</span>
                                <span class="detail-value">{{ formatPhoneNumber(patient.phone) }}</span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10,9 9,9 8,9"/>
                                </svg>
                            </div>
                            <div class="detail-content">
                                <span class="detail-label">ID Number</span>
                                <span class="detail-value">{{ patient.Idnum || 'Not provided' }}</span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                </svg>
                            </div>
                            <div class="detail-content">
                                <span class="detail-label">Age</span>
                                <span class="detail-value">{{ calculateAge(patient.dateOfBirth) }} years old</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="status-container empty-state">
                <div class="empty-illustration">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h4>No Patient Found</h4>
                <p>No patient data found for ID: {{ props.patientId }}</p>
            </div>
        </div>

        <PatientModel
            :show-modal="isModalOpen"
            :spec-data="selectedPatient"
            @close="closeModal"
            @specUpdate="refreshPatients"
        />
    </div>
</template>

<style scoped>
/* Enhanced Base Styles */
.patient-consultation-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Inter', sans-serif;
    color: #1a202c;
    line-height: 1.6;
    /* background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); */
    min-height: 100vh;
}

/* Enhanced Consultation Section */
.consultation-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(226, 232, 240, 0.8);
}

.consultation-header {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.consultation-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.consultation-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.consultation-title h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a202c;
}

.consultation-title p {
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
    color: #64748b;
}

.consultation-input-group {
    position: relative;
}

.consultation-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.required-indicator {
    color: #ef4444;
    margin-left: 0.25rem;
}

.input-wrapper {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.consultation-input {
    flex: 1;
    padding: 0.875rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    transition: all 0.2s ease;
    font-weight: 500;
}

.consultation-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.consultation-input.valid {
    border-color: #10b981;
}

.consultation-input.invalid {
    border-color: #ef4444;
}

.input-actions {
    flex-shrink: 0;
}

.update-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s ease;
    min-width: 100px;
}

.update-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.update-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.update-btn.loading {
    background: #6b7280;
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-content svg {
    width: 16px;
    height: 16px;
}

.mini-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.input-feedback {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
}

.validation-message {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.validation-message svg {
    width: 14px;
    height: 14px;
}

.validation-message.error {
    color: #ef4444;
}

.validation-message.success {
    color: #10b981;
}

.character-count {
    font-size: 0.75rem;
    color: #9ca3af;
    font-weight: 500;
}

/* Enhanced Patient Info Container */
.patient-info-container {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(226, 232, 240, 0.8);
}

/* Enhanced Status States */
.status-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
}

.loading-state {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.loading-animation {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.pulse-circle {
    width: 12px;
    height: 12px;
    background: #3b82f6;
    border-radius: 50%;
    animation: pulse 1.5s ease-in-out infinite;
}

.pulse-delayed {
    animation-delay: 0.3s;
}

.pulse-delayed-2 {
    animation-delay: 0.6s;
}

@keyframes pulse {
    0% { transform: scale(0.8); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(0.8); opacity: 0.5; }
}

.error-state {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.error-illustration svg,
.empty-illustration svg {
    width: 64px;
    height: 64px;
    color: #9ca3af;
    margin-bottom: 1.5rem;
}

.status-container h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.status-container p {
    margin: 0 0 2rem 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.retry-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.retry-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.retry-btn svg {
    width: 16px;
    height: 16px;
}

/* Enhanced Patient Display */
.patient-display {
    position: relative;
}

.patient-header {
    position: relative;
    overflow: hidden;
}

.header-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0.1;
}

.header-content {
    position: relative;
    display: flex;
    align-items: center;
    padding: 2rem;
    gap: 1.5rem;
}

.patient-avatar-section {
    flex-shrink: 0;
}

.avatar-container {
    position: relative;
}

.avatar-image,
.avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    border: 4px solid white;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.avatar-image {
    object-fit: cover;
}

.avatar-placeholder {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-initials {
    color: white;
    font-size: 1.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.avatar-status {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid white;
}

.avatar-status.online {
    background: #10b981;
}

.patient-info-section {
    flex: 1;
    min-width: 0;
}

.patient-name-group {
    margin-bottom: 1rem;
}

.patient-name {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: #1a202c;
    text-transform: capitalize;
}

.patient-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.badge.primary {
    background: #dbeafe;
    color: #1e40af;
}

.badge.secondary {
    background: #f3f4f6;
    color: #374151;
}

.quick-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-divider {
    width: 1px;
    height: 2rem;
    background: #e5e7eb;
}

.header-actions {
    flex-shrink: 0;
}

.edit-patient-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.edit-patient-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.edit-patient-btn svg {
    width: 16px;
    height: 16px;
}

/* Enhanced Patient Details */
.patient-details {
    background: #f8fafc;
}

.details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.details-header h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
}

.details-toggle {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.details-toggle:hover {
    background: #f3f4f6;
}

.details-toggle svg {
    width: 16px;
    height: 16px;
    color: #6b7280;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    padding: 2rem;
}

.detail-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.detail-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.detail-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.detail-icon svg {
    width: 20px;
    height: 20px;
    color: #667eea;
}

.detail-content {
    flex: 1;
    min-width: 0;
}

.detail-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.detail-value {
    display: block;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    word-break: break-word;
}

/* Animations */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .patient-consultation-container {
        padding: 1rem;
    }
    
    .consultation-section,
    .patient-info-container {
        border-radius: 12px;
    }
    
    .consultation-section {
        padding: 1.5rem;
    }
    
    .consultation-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .consultation-icon {
        margin-right: 0;
    }
    
    .input-wrapper {
        flex-direction: column;
        gap: 1rem;
    }
    
    .update-btn {
        width: 100%;
        justify-content: center;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .patient-info-section {
        width: 100%;
    }
    
    .patient-name {
        font-size: 1.5rem;
    }
    
    .quick-stats {
        justify-content: center;
    }
    
    .header-actions {
        width: 100%;
    }
    
    .edit-patient-btn {
        width: 100%;
        justify-content: center;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
        padding: 1.5rem;
    }
    
    .detail-card {
        padding: 1.25rem;
    }
}

@media (max-width: 480px) {
    .patient-consultation-container {
        padding: 0.5rem;
    }
    
    .consultation-section {
        padding: 1rem;
    }
    
    .consultation-title h3 {
        font-size: 1.25rem;
    }
    
    .consultation-icon {
        width: 40px;
        height: 40px;
    }
    
    .consultation-icon svg {
        width: 20px;
        height: 20px;
    }
    
    .header-content {
        padding: 1rem;
    }
    
    .avatar-image,
    .avatar-placeholder {
        width: 80px;
        height: 80px;
    }
    
    .avatar-initials {
        font-size: 1.5rem;
    }
    
    .patient-name {
        font-size: 1.25rem;
    }
    
    .details-grid {
        padding: 1rem;
        gap: 0.75rem;
    }
    
    .detail-card {
        padding: 1rem;
    }
    
    .detail-icon {
        width: 40px;
        height: 40px;
    }
    
    .detail-icon svg {
        width: 16px;
        height: 16px;
    }
}

/* Focus states for accessibility */
.consultation-input:focus,
.update-btn:focus,
.edit-patient-btn:focus,
.retry-btn:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Print styles */
@media print {
    .consultation-section,
    .header-actions,
    .details-toggle {
        display: none;
    }
    
    .patient-info-container {
        box-shadow: none;
        border: 1px solid #000;
    }
    
    .patient-header {
        background: none !important;
    }
    
    .detail-card {
        box-shadow: none;
        border: 1px solid #ccc;
    }
}
</style>