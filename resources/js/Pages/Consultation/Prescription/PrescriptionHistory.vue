<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import PdfPreviewModal from '../../../Components/composables/PdfPreviewModal.vue';

const toaster = useToastr();

const props = defineProps({
  patientId: {
    type: [Number, String],
    required: true,
  },
  appointmentId: {
    type: [Number, String],
    required: false,
  }
});

// Reactive state
const showPdfPreviewModal = ref(false);
const pdfToPreviewUrl = ref(null);
const prescriptions = ref([]);
const loading = ref(false);
const error = ref(null);
const isPrinting = ref(false);

// Methods
const fetchPrescriptionsHistory = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get(`/api/consultation/${props.patientId}/documents`, {
      params: {
        type: 'prescription',
        appointment_id: props.appointmentId ?? null
      },
    });
    
    prescriptions.value = response.data.data || [];
  } catch (err) {
    console.error('Error fetching prescription history:', err);
    error.value = err.response?.data?.message || 'Failed to load prescription history.';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const generatePdf = async (pdfPath) => {
  try {
    const response = await axios.get(`/api/prescription/download`, {
      params: { path: pdfPath },
      responseType: 'blob'
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `prescription_${new Date().toISOString().slice(0,10)}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Revoke the object URL after a delay
    setTimeout(() => {
      window.URL.revokeObjectURL(url);
    }, 1000);

    toaster.success('PDF downloaded successfully');
  } catch (error) {
    console.error('Error downloading PDF:', error);
    toaster.error('Failed to download PDF');
  }
};
const viewPrescription = async (prescription) => {
  try {
    const response = await axios.get(`/api/prescription/view/${prescription.appointment_id}`, {
      responseType: 'blob'
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);

    // Always preview inside modal
    pdfToPreviewUrl.value = url;
    showPdfPreviewModal.value = true;

  } catch (error) {
    console.error('Error viewing prescription:', error);
    toaster.error('Failed to view prescription');
  }
};

const printPrescription = async (pdfPath) => {
  if (isPrinting.value) return;
  
  try {
    isPrinting.value = true;
    const response = await axios.get(`/api/prescription/print`, {
      params: { path: pdfPath },
      responseType: 'blob'
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    
    // Create iframe for printing
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = url;
    document.body.appendChild(iframe);
    
    iframe.onload = () => {
      setTimeout(() => {
        iframe.contentWindow?.print();
        
        // Cleanup after printing
        iframe.contentWindow?.addEventListener('afterprint', () => {
          document.body.removeChild(iframe);
          window.URL.revokeObjectURL(url);
          isPrinting.value = false;
        });
        
        // Fallback cleanup in case afterprint doesn't fire
        setTimeout(() => {
          if (document.body.contains(iframe)) {
            document.body.removeChild(iframe);
            window.URL.revokeObjectURL(url);
            isPrinting.value = false;
          }
        }, 5000);
      }, 500);
    };
    
    // Handle iframe load error
    iframe.onerror = () => {
      document.body.removeChild(iframe);
      window.URL.revokeObjectURL(url);
      isPrinting.value = false;
      toaster.error('Failed to load PDF for printing');
    };
    
  } catch (error) {
    console.error('Error printing prescription:', error);
    toaster.error(error.message || 'Failed to print prescription');
    isPrinting.value = false;
  }
};

const handlePdfPreviewClose = () => {
  showPdfPreviewModal.value = false;

  if (pdfToPreviewUrl.value) {
    window.URL.revokeObjectURL(pdfToPreviewUrl.value);
    pdfToPreviewUrl.value = null;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? dateString : date.toLocaleString();
  } catch (e) {
    return dateString;
  }
};

const refresh = async () => {
  await fetchPrescriptionsHistory();
};

// Expose methods
defineExpose({
  fetchPrescriptionsHistory,
  refresh
});

// Lifecycle hooks
onMounted(() => {
  if (props.patientId) {
    fetchPrescriptionsHistory();
  }
});

watch(() => props.patientId, (newId) => {
  if (newId) {
    fetchPrescriptionsHistory();
  }
});
</script>

<template>
  <div class="premium-document-history-section">
    <div class="premium-section-subtitle">Patient Prescription History</div>

    <div v-if="loading" class="premium-loading-state">
      <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
      <p>Loading prescriptions...</p>
    </div>

    <div v-else-if="error" class="premium-empty-state">
      <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
      <p>{{ error }}</p>
    </div>

    <div v-else-if="prescriptions.length === 0" class="premium-empty-state">
      <i class="fas fa-file-prescription fa-3x text-muted mb-3"></i>
      <p>No past prescriptions found for this patient.</p>
    </div>

    <div v-else class="premium-table-container">
      <table class="premium-table">
        <thead>
          <tr>
            <th>Created Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="prescription in prescriptions" :key="prescription.id">
            <td>{{ formatDate(prescription.created_at) }}</td>
            <td>
              <div class="premium-actions">
                <button 
                  @click="generatePdf(prescription.document_path)" 
                  class="premium-icon-btn download-btn" 
                  title="Download PDF"
                >
                  <i class="fas fa-download"></i>
                </button>
                <button 
                  @click="viewPrescription(prescription)" 
                  class="premium-icon-btn view-btn" 
                  title="View PDF"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button 
                  @click="printPrescription(prescription.document_path)" 
                  class="premium-icon-btn print-btn" 
                  title="Print PDF"
                  :disabled="isPrinting"
                >
                  <i :class="['fas', isPrinting ? 'fa-spinner fa-spin' : 'fa-print']"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <PdfPreviewModal
    v-if="showPdfPreviewModal"
    :isVisible="showPdfPreviewModal"
    :pdfUrl="pdfToPreviewUrl"
    @close="handlePdfPreviewClose"
  />
</template>

<style scoped>
.premium-document-history-section {
  background-color: #ffffff;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-top: 2rem;
}

.premium-loading-state,
.premium-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6c757d;
  text-align: center;
}

.premium-loading-state p,
.premium-empty-state p {
  font-size: 1.1rem;
  margin-top: 0.5rem;
}

.premium-table-container {
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.premium-table th,
.premium-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.premium-table th {
  background-color: #f1f5f9;
  font-weight: 600;
  color: #334155;
  text-transform: uppercase;
  font-size: 0.85rem;
}

.premium-table tbody tr:hover {
  background-color: #f8fafc;
}

.premium-icon-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  font-size: 1.1rem;
  border-radius: 5px;
  transition: background-color 0.2s ease-in-out;
  margin-right: 5px;
}

.premium-icon-btn:hover {
  background-color: #e2e8f0;
}

.view-btn {
  color: #3a5bb1;
}

.print-btn {
  color: #28a745;
}

.download-btn {
  color: #6c757d;
}

.premium-icon-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.premium-icon-btn:disabled:hover {
  background-color: transparent;
}
</style>