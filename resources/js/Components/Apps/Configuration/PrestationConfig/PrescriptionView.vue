<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'; // Added watch import
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';
import { useSweetAlert } from '../../../../Components/useSweetAlert';

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    prescriptionId: {
        type: [String, Number],
        required: true
    }
});

const emit = defineEmits(['close', 'edit', 'delete']);

const toaster = useToastr();
const swal = useSweetAlert();

// Data
const prescription = ref(null);
const loading = ref(false);
const error = ref(null);

// Computed
const formattedPrice = computed(() => {
    if (!prescription.value?.public_price) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(prescription.value.public_price);
});

const formattedConsumablesCost = computed(() => {
    if (!prescription.value?.consumables_cost) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(prescription.value.consumables_cost);
});

const formattedMinVersement = computed(() => {
    if (!prescription.value?.min_versement_amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(prescription.value.min_versement_amount);
});

const totalEstimatedCost = computed(() => {
    if (!prescription.value) return 'N/A';
    
    const price = parseFloat(prescription.value.public_price) || 0;
    const vatRate = parseFloat(prescription.value.vat_rate) || 0;
    const consumables = parseFloat(prescription.value.consumables_cost) || 0;
    
    const vatAmount = (price * vatRate) / 100;
    const total = price + vatAmount + consumables;
    
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(total);
});

const vatAmount = computed(() => {
    if (!prescription.value?.public_price || !prescription.value?.vat_rate) return 'N/A';
    
    const price = parseFloat(prescription.value.public_price) || 0;
    const vatRate = parseFloat(prescription.value.vat_rate) || 0;
    const vat = (price * vatRate) / 100;
    
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(vat);
});

const formattedDuration = computed(() => {
    if (!prescription.value?.default_duration_minutes) return 'N/A';
    
    const minutes = prescription.value.default_duration_minutes;
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    
    if (hours > 0) {
        return remainingMinutes > 0 ? `${hours}h ${remainingMinutes}m` : `${hours}h`;
    }
    return `${minutes}m`;
});

const statusClass = computed(() => {
    return prescription.value?.is_active ? 'status-active' : 'status-inactive';
});

const statusText = computed(() => {
    return prescription.value?.is_active ? 'Active' : 'Inactive';
});

const paymentTypeFormatted = computed(() => {
    if (!prescription.value?.default_payment_type) return 'N/A';
    
    const paymentTypes = {
        'cash': 'Cash Payment',
        'card': 'Card Payment',
        'insurance': 'Insurance',
        'bank_transfer': 'Bank Transfer'
    };
    
    return paymentTypes[prescription.value.default_payment_type] || prescription.value.default_payment_type;
});

const createdAtFormatted = computed(() => {
    if (!prescription.value?.created_at) return 'N/A';
    return new Date(prescription.value.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
});

const updatedAtFormatted = computed(() => {
    if (!prescription.value?.updated_at) return 'N/A';
    return new Date(prescription.value.updated_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
});

// Methods
const getPrescription = async () => {
    if (!props.prescriptionId) return;
    
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get(`/api/prescriptions/${props.prescriptionId}`);
        prescription.value = response.data;
    } catch (err) {
        console.error('Error fetching prescription:', err);
        error.value = err.response?.data?.message || 'Failed to load prescription details.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};

const editPrescription = () => {
    emit('edit', prescription.value);
};

const deletePrescription = async () => {
    const result = await swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/prescriptions/${prescription.value.id}`);
            toaster.success('Prescription deleted successfully!');
            swal.fire('Deleted!', 'The prescription has been deleted.', 'success');
            emit('delete', prescription.value.id);
            closeModal();
        } catch (err) {
            console.error('Error deleting prescription:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete prescription.';
            toaster.error(errorMessage);
            swal.fire('Error!', errorMessage, 'error');
        }
    }
};

const closeModal = () => {
    emit('close');
};

const printPrescription = () => {
    window.print();
};

const exportPrescription = async () => {
    try {
        const response = await axios.get(`/api/prescriptions/${prescription.value.id}/export`, {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `prescription-${prescription.value.internal_code}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        
        toaster.success('Prescription exported successfully!');
    } catch (err) {
        console.error('Error exporting prescription:', err);
        toaster.error('Failed to export prescription.');
    }
};

// Handle escape key
const handleEscape = (event) => {
    if (event.key === 'Escape' && props.showModal) {
        closeModal();
    }
};

// Watch for modal visibility and prescription ID changes
watch(() => props.showModal, (newVal) => {
    if (newVal) {
        getPrescription();
    }
});

watch(() => props.prescriptionId, (newVal) => {
    if (newVal && props.showModal) {
        getPrescription();
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
    if (props.showModal) {
        getPrescription();
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <Teleport to="body">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal-container">
                <div class="modal-header">
                    <div class="header-content">
                        <h2 class="modal-title">Prescription Details</h2>
                        <div v-if="prescription" class="prescription-status">
                            <span :class="['status-badge', statusClass]">
                                {{ statusText }}
                            </span>
                            <span v-if="prescription.is_social_security_reimbursable" class="info-badge social-security">
                                <i class="fas fa-shield-alt"></i>
                                SS Reimbursable
                            </span>
                            <span v-if="prescription.requires_hospitalization" class="info-badge hospitalization">
                                <i class="fas fa-hospital"></i>
                                Hospitalization
                            </span>
                        </div>
                    </div>
                    <button @click="closeModal" class="close-button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div v-if="loading" class="loading-state">
                    <div class="spinner"></div>
                    <p class="loading-text">Loading prescription details...</p>
                </div>

                <div v-else-if="error" class="error-state">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="error-title">Error Loading Prescription</h3>
                    <p class="error-message">{{ error }}</p>
                    <button @click="getPrescription" class="retry-button">
                        <i class="fas fa-redo"></i>
                        Try Again
                    </button>
                </div>

                <div v-else-if="prescription" class="modal-body">
                    <div class="action-bar">
                        <div class="action-buttons">
                            <button @click="printPrescription" class="action-button print-button">
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                            <button @click="exportPrescription" class="action-button export-button">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                            <button @click="editPrescription" class="action-button edit-button">
                                <i class="fas fa-edit"></i>
                                Edit
                            </button>
                            <button @click="deletePrescription" class="action-button delete-button">
                                <i class="fas fa-trash"></i>
                                Delete
                            </button>
                        </div>
                    </div>

                    <div class="content-grid">
                        <div class="info-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle"></i>
                                    Basic Information
                                </h3>
                            </div>
                            <div class="card-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Prescription Name</label>
                                        <span class="info-value prescription-name">{{ prescription.name }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Internal Code</label>
                                        <span class="info-value code-badge internal-code">{{ prescription.internal_code || 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Billing Code</label>
                                        <span class="info-value code-badge billing-code">{{ prescription.billing_code || 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Service</label>
                                        <span class="info-value service-name">{{ prescription.service?.name || 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Specialization</label>
                                        <span class="info-value specialization-name">{{ prescription.specialization?.name || 'N/A' }}</span>
                                    </div>
                                    <div class="info-item full-width" v-if="prescription.description">
                                        <label class="info-label">Description</label>
                                        <span class="info-value description">{{ prescription.description }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-dollar-sign"></i>
                                    Pricing & Billing
                                </h3>
                            </div>
                            <div class="card-content">
                                <div class="pricing-summary">
                                    <div class="total-cost">
                                        <span class="cost-label">Total Estimated Cost</span>
                                        <span class="cost-value">{{ totalEstimatedCost }}</span>
                                    </div>
                                </div>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Public Price</label>
                                        <span class="info-value price">{{ formattedPrice }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">VAT Rate</label>
                                        <span class="info-value vat-rate">{{ prescription.vat_rate ? prescription.vat_rate + '%' : 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">VAT Amount</label>
                                        <span class="info-value price vat-amount">{{ vatAmount }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Consumables Cost</label>
                                        <span class="info-value price">{{ formattedConsumablesCost }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Payment Type</label>
                                        <span class="info-value payment-type">{{ paymentTypeFormatted }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Min Versement</label>
                                        <span class="info-value price">{{ formattedMinVersement }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-stethoscope"></i>
                                    Medical Details
                                </h3>
                            </div>
                            <div class="card-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Duration</label>
                                        <span class="info-value duration">{{ formattedDuration }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Modality Type</label>
                                        <span class="info-value modality-type">{{ prescription.required_modality_type?.name || 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Hospitalization</label>
                                        <span class="info-value">
                                            <span :class="['boolean-badge', prescription.requires_hospitalization ? 'yes' : 'no']">
                                                <i :class="prescription.requires_hospitalization ? 'fas fa-check' : 'fas fa-times'"></i>
                                                {{ prescription.requires_hospitalization ? 'Required' : 'Not Required' }}
                                            </span>
                                        </span>
                                    </div>
                                    <div v-if="prescription.requires_hospitalization" class="info-item">
                                        <label class="info-label">Default Nights</label>
                                        <span class="info-value hospitalization-nights">
                                            <i class="fas fa-bed"></i>
                                            {{ prescription.default_hosp_nights || 'N/A' }} nights
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-cog"></i>
                                    System Information
                                </h3>
                            </div>
                            <div class="card-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Status</label>
                                        <span class="info-value">
                                            <span :class="['boolean-badge', prescription.is_active ? 'active' : 'inactive']">
                                                <i :class="prescription.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                                {{ prescription.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Social Security</label>
                                        <span class="info-value">
                                            <span :class="['boolean-badge', prescription.is_social_security_reimbursable ? 'yes' : 'no']">
                                                <i :class="prescription.is_social_security_reimbursable ? 'fas fa-check' : 'fas fa-times'"></i>
                                                {{ prescription.is_social_security_reimbursable ? 'Reimbursable' : 'Not Reimbursable' }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Created</label>
                                        <span class="info-value date-info">
                                            <i class="fas fa-calendar-plus"></i>
                                            {{ createdAtFormatted }}
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Updated</label>
                                        <span class="info-value date-info">
                                            <i class="fas fa-calendar-edit"></i>
                                            {{ updatedAtFormatted }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
    backdrop-filter: blur(4px);
}

.modal-container {
    background-color: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 1200px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 2rem 2rem 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.header-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.modal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.prescription-status {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.status-badge {
    display: inline-block;
    padding: 0.375rem 0.875rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background-color: #fee2e2;
    color: #991b1b;
}

.info-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.625rem;
    font-weight: 500;
}

.social-security {
    background-color: #e0e7ff;
    color: #3730a3;
}

.hospitalization {
    background-color: #fef3c7;
    color: #92400e;
}

.close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-button:hover {
    color: #374151;
    background-color: #f3f4f6;
}

/* Loading and Error States */
.loading-state, .error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
}

.spinner {
    width: 3rem;
    height: 3rem;
    border: 4px solid rgba(37, 99, 235, 0.3);
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.loading-text {
    color: #6b7280;
    font-size: 1.125rem;
}

.error-icon {
    font-size: 4rem;
    color: #ef4444;
    margin-bottom: 1rem;
}

.error-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.error-message {
    color: #6b7280;
    margin-bottom: 2rem;
}

.retry-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background-color: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.retry-button:hover {
    background-color: #1d4ed8;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 2rem 2rem;
}

.action-bar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 2rem;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 0.75rem;
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.print-button {
    background-color: #6b7280;
    color: #ffffff;
}

.print-button:hover {
    background-color: #4b5563;
}

.export-button {
    background-color: #10b981;
    color: #ffffff;
}

.export-button:hover {
    background-color: #059669;
}

.edit-button {
    background-color: #f59e0b;
    color: #ffffff;
}

.edit-button:hover {
    background-color: #d97706;
}

.delete-button {
    background-color: #ef4444;
    color: #ffffff;
}

.delete-button:hover {
    background-color: #dc2626;
}

.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.info-card {
    background-color: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    padding: 1.25rem;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
}

.card-content {
    padding: 1.5rem;
}

.pricing-summary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 0.5rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    color: white;
}

.total-cost {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cost-label {
    font-size: 1rem;
    font-weight: 500;
}

.cost-value {
    font-size: 1.75rem;
    font-weight: 700;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.25rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.875rem;
    color: #1f2937;
    font-weight: 500;
}

.info-value.prescription-name {
    font-size: 1rem;
    font-weight: 600;
    color: #2563eb;
}

.code-badge {
    background-color: #eef2ff;
    color: #4f46e5;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    max-width: fit-content;
}

.service-name, .specialization-name, .modality-type, .payment-type {
    font-weight: 600;
    color: #4b5563;
}

.price {
    font-weight: 600;
    color: #059669;
}

.vat-rate {
    font-weight: 600;
    color: #92400e;
}

.duration {
    font-weight: 600;
    color: #1d4ed8;
}

.hospitalization-nights {
    font-weight: 600;
    color: #92400e;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.boolean-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.boolean-badge.yes, .boolean-badge.active {
    background-color: #d1fae5;
    color: #065f46;
}

.boolean-badge.no, .boolean-badge.inactive {
    background-color: #fee2e2;
    color: #991b1b;
}

.date-info {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.875rem;
    color: #4b5563;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-container {
        max-width: 95%;
        margin: 1rem;
    }

    .modal-header {
        flex-direction: column;
        align-items: center;
        padding: 1.5rem 1.5rem 1rem;
        text-align: center;
    }

    .header-content {
        align-items: center;
        margin-bottom: 1rem;
    }

    .close-button {
        position: absolute;
        top: 1rem;
        right: 1rem;
    }

    .modal-title {
        font-size: 1.5rem;
    }

    .prescription-status {
        justify-content: center;
    }

    .action-bar {
        justify-content: center;
        padding: 0.75rem;
    }

    .action-buttons {
        flex-direction: column;
        width: 100%;
    }

    .action-button {
        width: 100%;
        justify-content: center;
        padding: 0.75rem 1rem;
    }

    .content-grid {
        grid-template-columns: 1fr;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>