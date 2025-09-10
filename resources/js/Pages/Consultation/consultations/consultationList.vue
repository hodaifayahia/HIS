<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import ConsultationListItem from './consultationListItem.vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const swal = useSweetAlert();
const toaster = useToastr();
const route = useRoute(); // Get the current route

// Extract patientId and patientName from route params
const patientId = route.params.patientId;

// Ensure patientId is present
if (!patientId) {
    toaster.error('Patient ID is required but not provided in the route params.');
    // Optionally redirect or handle this error more gracefully
}
const patientName = ref(route.query.patient_name || 'Patient'); // Get patient name from query or default

// State
const consultations = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');


// Computed property for filtered consultations (only by search query now)
const filteredConsultations = computed(() => {
    let filtered = consultations.value;

    // Apply search query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(c =>
            (c.doctor_name || '').toLowerCase().includes(query) ||
            (c.date || '').toLowerCase().includes(query) // Include date in search
        );
    }

    return filtered;
});

const hasConsultations = computed(() => consultations.value.length > 0);

// API Calls
const getConsultations = async () => {
    if (!patientId) return; // Prevent API call if patientId is missing

    try {
        loading.value = true;
        error.value = null; // Clear any previous errors
       
        const response = await axios.get(`/api/consulations/${patientId}`);
        consultations.value = response.data.data || []; // Ensure data is an array
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load consultations';
        toaster.error(error.value);
        console.error("Error fetching consultations:", err);
    } finally {
        loading.value = false;
    }
};

const deleteConsultation = async (id, consultationPatientName) => {
    const result = await swal.fire({
        title: 'Are you sure?',
        text: `Delete consultation for "${consultationPatientName}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false // Important for custom button classes to work
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/consultations/${id}`);
            await getConsultations(); // Refresh the list after deletion
            toaster.success('Consultation deleted successfully!');
        } catch (err) {
            toaster.error(err.response?.data?.message || 'Failed to delete consultation.');
            console.error("Error deleting consultation:", err);
        }
    }
};

// Lifecycle Hooks
onMounted(() => {
    getConsultations();
});
</script>

<template>
    <div class="consultation-page">
        <div class="card shadow-lg">
            
            <div class="card-header bg-white border-bottom-0 p-4">
                <button  class="btn btn-light bg-primary rounded-pill shadow-sm position-absolute"
              style="top: 29px; left: 10px; " @click="router.go(-1)">
              <i class="fas fa-arrow-left"></i> Back
          </button>
                <div class="header-content">
                    <h3 class="page-title">Consultation History for {{ patientName }}</h3>

                    <div class="controls-wrapper justify-content-end">
                        <div class="search-wrapper">
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="form-control modern-input"
                                placeholder="Search doctor or date..."
                            />
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div v-if="loading" class="loading-state">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="loading-text">Loading consultations...</p>
                </div>

                <div v-else-if="error" class="alert alert-danger m-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> {{ error }}
                </div>

                <div v-else-if="!hasConsultations && !searchQuery" class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <h4 class="empty-title">No consultations recorded for this patient</h4>
                    <p class="empty-description">There are no historical consultations available.</p>
                </div>

                <div v-else-if="filteredConsultations.length === 0 && searchQuery.trim()" class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-search-minus"></i>
                    </div>
                    <h4 class="empty-title">No matching consultations</h4>
                    <p class="empty-description">Try adjusting your search criteria</p>
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-header">
                            <tr>
                                <th class="th-id">#</th>
                                <th class="">CodeBash</th>
                                <th class="">patient</th>
                                <th class="th-doctor">Doctor</th>
                                <th class="th-date">Date</th>
                                <th class="th-date">duration</th>
                                <th class="th-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <ConsultationListItem
                                v-for="(consultation, index) in filteredConsultations"
                                :key="consultation.id"
                                :consultation="consultation"
                                :index="index"
                                @delete="deleteConsultation"
                            />
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.consultation-page {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: calc(100vh - 60px);
}

.card {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    border-radius: 16px;
    border: none;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    background: #ffffff;
}

.card-header {
  background: linear-gradient(135deg, #0061f2 0%, #6900f2 100%);
    border: none;
    padding: 2rem;
}

.header-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-title {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-left: 70px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.controls-wrapper {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.modern-select {
    min-width: 180px;
    border-radius: 12px;
    border: 2px solid #e1e5e9;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    background: white;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.modern-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.search-wrapper {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.modern-input {
    width: 100%;
    border-radius: 12px;
    border: 2px solid #e1e5e9;
    padding: 0.75rem 1rem 0.75rem 3rem;
    font-size: 0.95rem;
    background: white;
    transition: all 0.3s ease;
}

.modern-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.custom-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    flex-shrink: 0;
}

.custom-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}

.custom-btn-success {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    border: none;
    border-radius: 12px;
    padding: 0.875rem 2rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.custom-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
    background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    gap: 1rem;
}

.loading-state .spinner-border {
    width: 3rem;
    height: 3rem;
}

.loading-text {
    font-size: 1.125rem;
    color: #6c757d;
    margin: 0;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    background: #fafbfc;
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 1.5rem;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.empty-description {
    font-size: 1rem;
    color: #718096;
    margin-bottom: 2rem;
    max-width: 400px;
}

.table {
    font-size: 0.95rem;
}

.table-header {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}

.table-header th {
    font-weight: 600;
    color: #4a5568;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 1rem;
    border: none;
    vertical-align: middle;
}

.th-id {
    width: 60px;
}

.th-actions {
    width: 120px;
    text-align: center;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f7fafc;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.alert {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.5rem;
}

/* Responsive Design */
@media (max-width: 992px) {
    .header-content {
        gap: 1rem;
    }

    .controls-wrapper {
        flex-direction: column;
        align-items: stretch;
    }

    .modern-select,
    .search-wrapper,
    .custom-btn-primary {
        width: 100%;
        min-width: unset;
    }

    .custom-btn-primary {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .consultation-page {
        padding: 1rem;
    }

    .card {
        border-radius: 12px;
    }

    .card-header {
        padding: 1.5rem;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .search-wrapper {
        min-width: unset;
    }

    .modern-input,
    .modern-select {
        padding: 0.625rem 0.875rem;
        font-size: 0.9rem;
    }

    .modern-input {
        padding-left: 2.5rem;
    }

    .search-icon {
        left: 0.875rem;
    }

    .empty-state {
        padding: 3rem 1.5rem;
    }

    .empty-icon {
        font-size: 3rem;
    }

    .empty-title {
        font-size: 1.25rem;
    }

    .table-responsive {
        border-radius: 0 0 12px 12px;
    }

    .table-header th {
        padding: 0.75rem 0.5rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .consultation-page {
        padding: 0.5rem;
    }

    .card-header {
        padding: 1rem;
    }

    .page-title {
        font-size: 1.25rem;
    }

    .controls-wrapper {
        gap: 0.75rem;
    }

    .custom-btn-primary span {
        display: none;
    }

    .empty-state {
        padding: 2rem 1rem;
    }
}
</style>