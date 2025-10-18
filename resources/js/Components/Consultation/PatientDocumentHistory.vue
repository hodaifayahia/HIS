<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../toster'; // Adjust path as needed
import PdfPreviewModal from '../composables/PdfPreviewModal.vue'; // Import the new component

const toaster = useToastr();

const props = defineProps({
  patientId: {
    type: Number,
    required: true,
  },
  appointmentId: {
    type: [Number, String],
    required: false,
  }
});

const documents = ref([]);
const loading = ref(false);
const error = ref(null);

// Reactive variables for the confirmation modal
const showPrintConfirmationModal = ref(false);
const documentToPrint = ref(null); // To store details of the document to be printed

// Reactive variables for the PDF preview modal (now managed by the parent)
const showPdfPreviewModal = ref(false);
const pdfToPreviewUrl = ref(null); // Renamed to avoid conflict if 'pdfPreviewUrl' was used elsewhere

// Add refresh method that can be called from parent
const refresh = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/consultation/${props.patientId}/documents`, {
      params: {
        type: 'Consultation',
        appointment_id: props.appointmentId ?? null
      }
    });
    documents.value = response.data.data || [];

  } catch (err) {
    toaster.error('Failed to refresh documents');
    console.error('Refresh error:', err);
  } finally {
    loading.value = false;
  }
};

const fetchPatientDocuments = async () => {
  try {
    loading.value = true;
    console.log(props.patientId, props.appointmentId);

    const response = await axios.get(`/api/consultation/${props.patientId}/documents`, {
      params: {
        type: 'Consultation',
        appointment_id: props.appointmentId
      },
    });
    documents.value = response.data.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load patient documents.';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const viewDocument = (documentPath) => {
  const url = `/storage/${documentPath}`;
  // Check if the document is a PDF before showing the modal
  if (documentPath.toLowerCase().endsWith('.pdf')) {
    pdfToPreviewUrl.value = url;
    showPdfPreviewModal.value = true;
  } else {
    // For non-PDF documents, open in a new tab as before
    window.open(url, '_blank');
  }
};

// Handler for when the PdfPreviewModal emits its 'close' event
const handlePdfPreviewClose = () => {
  showPdfPreviewModal.value = false;
  pdfToPreviewUrl.value = null;
};

const downloadDocument = (documentPath, documentName) => {
  const url = `/storage/${documentPath}`;
  const link = document.createElement('a');
  link.href = url;
  link.download = documentName || 'document';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// Modified printDocument to show modal
const printDocument = (doc) => {
  documentToPrint.value = doc; // Store the entire document object
  showPrintConfirmationModal.value = true; // Show the modal
};

// New function to handle actual printing after confirmation
const confirmPrint = () => {
  if (documentToPrint.value) {
    const url = `/storage/${documentToPrint.value.document_path}`;
    const printWindow = window.open(url, '_blank');
    printWindow.onload = () => {
      printWindow.print();
    };
    showPrintConfirmationModal.value = false; // Close the modal
    documentToPrint.value = null; // Clear the document
  }
};

const cancelPrint = () => {
  showPrintConfirmationModal.value = false; // Close the modal
  documentToPrint.value = null; // Clear the document
};

defineExpose({
  refresh
});

onMounted(() => {
  if (props.patientId) {
    fetchPatientDocuments();
  }
});

watch(() => props.patientId, (newId) => {
  if (newId) {
    fetchPatientDocuments();
  }
});
</script>

<template>
  <div class="premium-document-history-section">
    <div class="premium-section-subtitle">Patient Document History</div>

    <div v-if="loading" class="premium-loading-state">
      <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
      <p>Loading documents...</p>
    </div>

    <div v-else-if="error" class="premium-empty-state">
      <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
      <p>{{ error }}</p>
    </div>

    <div v-else-if="documents.length === 0" class="premium-empty-state">
      <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
      <p>No documents found for this patient.</p>
    </div>

    <div v-else class="premium-table-container">
      <table class="premium-table">
        <thead>
          <tr>
            <th>Document Name</th>
            <th>Document Type</th>
            <th>Saved Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="documents" v-for="doc in documents" :key="doc.id">
            <td>{{ doc.document_name }}</td>
            <td>{{ doc.document_type === 'Consultation' ? 'A4' : doc.document_type }}</td>
            <td>{{ new Date(doc.created_at).toLocaleDateString() }}</td>
            <td class="action-buttons">
              <button @click="viewDocument(doc.document_path)" class="premium-icon-btn view-btn" title="View">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="downloadDocument(doc.document_path, doc.document_name)"
                class="premium-icon-btn download-btn" title="Download">
                <i class="fas fa-download"></i>
              </button>
              <button @click="printDocument(doc)" class="premium-icon-btn print-btn" title="Print">
                <i class="fas fa-print"></i>
              </button>
            </td>
          </tr>
          <tr v-else>
            <td colspan="4">
              there is no history for this patient
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showPrintConfirmationModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Confirm Print</h3>
          <button @click="cancelPrint" class="close-modal-btn">&times;</button>
        </div>
        <div class="modal-body">
          <p>You are about to print the following document:</p>
          <p><strong>Document Name:</strong> {{ documentToPrint?.document_name }}</p>
          <p><strong>Document Type:</strong> {{ documentToPrint?.document_type }}</p>
          <p>Do you wish to proceed?</p>
        </div>
        <div class="modal-footer">
          <button @click="cancelPrint" class="premium-btn cancel-btn">Cancel</button>
          <button @click="confirmPrint" class="premium-btn confirm-btn">Print</button>
        </div>
      </div>
    </div>

    <PdfPreviewModal
      :isVisible="showPdfPreviewModal"
      :pdfUrl="pdfToPreviewUrl"
      @close="handlePdfPreviewClose"
    />

  </div>
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

.action-buttons {
  display: flex;
  gap: 8px;
}

.premium-icon-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  font-size: 1.1rem;
  border-radius: 5px;
  transition: all 0.2s ease-in-out;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.premium-icon-btn:hover {
  transform: scale(1.1);
}

.view-btn {
  color: #3b82f6;
  background-color: #eff6ff;
}

.download-btn {
  color: #10b981;
  background-color: #ecfdf5;
}

.print-btn {
  color: #f59e0b;
  background-color: #fffbeb;
}

.premium-icon-btn:hover {
  opacity: 0.9;
}

/* Modal Styles for Print Confirmation (can be moved to a generic Modal component too) */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: #fff;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 500px; /* Default max-width for print confirmation modal */
  animation: fadeInScale 0.3s ease-out;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding-bottom: 15px;
  margin-bottom: 20px;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.4rem;
  color: #333;
}

.close-modal-btn {
  background: none;
  border: none;
  font-size: 1.8rem;
  cursor: pointer;
  color: #999;
  transition: color 0.2s;
}

.close-modal-btn:hover {
  color: #333;
}

.modal-body {
  margin-bottom: 25px;
  line-height: 1.6;
  color: #555;
}

.modal-body p {
  margin-bottom: 10px;
}

.modal-body strong {
  color: #333;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 15px;
  border-top: 1px solid #eee;
}

.premium-btn {
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  transition: background-color 0.2s, color 0.2s;
}

.cancel-btn {
  background-color: #e0e0e0;
  color: #333;
  border: 1px solid #ccc;
}

.cancel-btn:hover {
  background-color: #d0d0d0;
}

.confirm-btn {
  background-color: #007bff;
  color: white;
  border: 1px solid #007bff;
}

.confirm-btn:hover {
  background-color: #0056b3;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.9);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>