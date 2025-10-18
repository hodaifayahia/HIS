<script setup>
import { ref, onMounted, watch } from 'vue';
import PatientDocumentHistory from '../../../Components/Consultation/PatientDocumentHistory.vue';
import PrescriptionHistory from '../Prescription/PrescriptionHistory.vue';
import PlaceholderHistory from './PlaceholderHistory.vue';
import { useToastr } from '../../../Components/toster';
import axios from 'axios';
import ConsultationControls from './ConsultationControls.vue';
import OpinionRequestReciverReplyModel from '../../../Components/OpinionRequests/OpinionRequestReciverReplyModel.vue';
import RequestHistory from '../../../Components/OpinionRequests/RequestHistory.vue';

const props = defineProps({
  patientId: {
    type: [Number, String],
    required: true
  },
  consultationId: {
    type: [Number, String],
    default: null
  },
  appointmentId: {
    type: [Number, String],
    default: null
  },
  singleMode: {
    type: Boolean,
    default: false
  },
  showreplymodel: {
    type: Boolean,
    default: false
  },
  isdoneconsulaiton: {
    type: Boolean,
    default: null
  },
  currentRequestId: {
    type: Number,
  },
});

// State
const allConsultations = ref([]);
const selectedConsultation = ref(null);
const appointmentid = ref(null); // This will now hold the appointment ID
const isLoading = ref(true);
const activeTab = ref('placeholder');

// New state to manage reply status for specific requests
const repliedRequests = ref({}); // Stores { requestId: true/false }

const fetchPatientHistory = async () => {
  isLoading.value = true;
  try {
    let response;
    if (props.singleMode) {
      if (props.consultationId) {
        response = await axios.get(`/api/consultations/${props.consultationId}`, {
          params: {
            isdoneconsultation: props.isdoneconsulaiton,
          }
        });
      } else if (props.appointmentId) {
        response = await axios.get(`/api/consultations/by-appointment/${parseInt(props.appointmentId)}`, {
          params: {
            isdoneconsultation: props.isdoneconsulaiton,
          }
        });
      } else {
        useToastr().warning('No consultation or appointment ID provided in single mode.', 'Warning');
        isLoading.value = false;
        return;
      }
      selectedConsultation.value = response.data.data;
      allConsultations.value = [response.data.data];
    } else {
      response = await axios.get(`/api/consulations/${props.patientId}`);
      allConsultations.value = response.data.data;
      if (allConsultations.value.length > 0) {
        selectedConsultation.value = allConsultations.value[0];
      }
    }
  } catch (error) {
    useToastr().error('Failed to load consultation data', 'Error');
  } finally {
    isLoading.value = false;
  }
};

// Watch for changes in selectedConsultation and update appointmentid
watch(selectedConsultation, (newVal) => {
  appointmentid.value = newVal ? newVal.appointment_id : null;
}, { immediate: true });

// Handle filter changes
const handleFilter = (filters) => {
  const filteredConsultations = allConsultations.value.filter(consultation => {
    const dateMatch = !filters.date || consultation.date.startsWith(filters.date);
    const doctorMatch = !filters.doctor || (consultation.doctor?.name === filters.doctor);
    const searchMatch = !filters.search ||
      (consultation.name?.toLowerCase().includes(filters.search.toLowerCase())) ||
      (consultation.doctor?.name.toLowerCase().includes(filters.search.toLowerCase()));

    return dateMatch && doctorMatch && searchMatch;
  }).sort((a, b) => new Date(b.date) - new Date(a.date));

  if (selectedConsultation.value &&
    !filteredConsultations.some(c => c.id === selectedConsultation.value.id)) {
    selectedConsultation.value = filteredConsultations[0] || null;
  } else if (!selectedConsultation.value && filteredConsultations.length > 0) {
    selectedConsultation.value = filteredConsultations[0];
  }
};

// Select consultation
const selectConsultation = (consultation) => {
  selectedConsultation.value = consultation;
  activeTab.value = 'placeholder';
};

// New handler for when the reply status changes in the child component
const handleReplyStatusChanged = ({ requestId, status }) => {
  if (status === 'replied') {
    repliedRequests.value[requestId] = true;
  }
  emit('refresh')
};

// Helper computed property to check if the current request has been replied to
const hasCurrentRequestBeenReplied = () => {
    return props.currentRequestId && repliedRequests.value[props.currentRequestId];
};

onMounted(() => {
  fetchPatientHistory();
});
</script>

<template>
  <div class="history-container">
    <div v-if="isLoading" class="loading-indicator">
      <div class="spinner"></div>
      <p>Loading Consultation Data...</p>
    </div>

    <main v-else class="history-layout">
      <ConsultationControls
        v-if="!singleMode"
        :consultations="allConsultations"
        :selected-consultation="selectedConsultation"
        @select="selectConsultation"
        @filter="handleFilter"
      />

      <div class="history-details">
        <div v-if="selectedConsultation" class="details-card">
          <nav class="details-tabs">
            <button @click="activeTab = 'placeholder'" :class="{ 'active': activeTab === 'placeholder' }">
              <i class="fas fa-notes-medical"></i> Details
            </button>
            <button @click="activeTab = 'prescriptions'" :class="{ 'active': activeTab === 'prescriptions' }">
              <i class="fas fa-pills"></i> Prescriptions
            </button>
            <button @click="activeTab = 'documents'" :class="{ 'active': activeTab === 'documents' }">
              <i class="fas fa-file-alt"></i> Documents
            </button>
            <button @click="activeTab = 'OpinionRequest'" :class="{ 'active': activeTab === 'OpinionRequest' }">
              <i class="fas fa-file-alt"></i> Opinion Requests
            </button>
            <button
              v-if="showreplymodel"
              @click="activeTab = 'Reply'"
              :class="{ 'active': activeTab === 'Reply', 'disabled-tab': hasCurrentRequestBeenReplied() }"
              :disabled="hasCurrentRequestBeenReplied()"
              title="You have already replied to this request."
            >
              <i class="fas fa-reply"></i> Reply
            </button>
          </nav>

          <div class="tab-content">
            <div v-if="activeTab === 'placeholder'">
              <PlaceholderHistory :consultationData="selectedConsultation" :patientId="props.patientId" :appointmentId="appointmentid" />
            </div>
            <div v-if="activeTab === 'prescriptions'">
              <PrescriptionHistory :consultationData="selectedConsultation" :patientId="props.patientId" :appointmentId="appointmentid" />
            </div>
            <div v-if="activeTab === 'documents'">
              <PatientDocumentHistory :consultationData="selectedConsultation" :patientId="props.patientId" :appointmentId="appointmentid" />
            </div>
            <div v-if="activeTab === 'OpinionRequest'">
              <RequestHistory :patientId="props.patientId" :appointmentId="appointmentid" />
            </div>
            
            <div v-if="activeTab === 'Reply' && showreplymodel">
              <OpinionRequestReciverReplyModel
                :request-id="props.currentRequestId"
                @submitted="handleReplyStatusChanged({ requestId: props.currentRequestId, status: 'replied' })"
                @reply-status-changed="handleReplyStatusChanged"
              />
            </div>
          </div>
        </div>
        <div v-else class="no-selection">
          <i class="fas fa-info-circle"></i>
          <h2>No Consultation Selected</h2>
          <p v-if="!allConsultations.length">No consultations found for this patient.</p>
          <p v-else>Please select a consultation from the list.</p>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  --primary-color: #3b82f6;
  --bg-color: #f8fafc;
  --card-bg: #ffffff;
  --text-dark: #1e293b;
  --text-light: #64748b;
  --border-color: #e2e8f0;
}

.history-container {
  background-color: var(--bg-color);
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
  padding: 1.5rem;
}

.history-header {
  text-align: center;
  margin-bottom: 2rem;
}
.main-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-dark);
}
.subtitle {
  font-size: 1.1rem;
  color: var(--text-light);
  margin-top: 0.5rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

/* --- Layout --- */
.history-layout {
  max-width: 1200px;
  margin: 0 auto;
}

.control-panel {
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
  padding: 1.5rem;
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (min-width: 768px) {
  .control-panel {
    grid-template-columns: repeat(2, 1fr);
    align-items: end;
  }
}
@media (min-width: 1024px) {
  .control-panel {
    grid-template-columns: 2fr 1fr 1fr;
    align-items: end;
  }
}

.consultation-selector {
  grid-column: 1 / -1;
}

@media (min-width: 1024px) {
  .consultation-selector {
    grid-column: auto;
  }
}

.filters-card {
  display: contents; /* Use subgrid-like behavior */
}

.filter-group, .consultation-selector {
  display: flex;
  flex-direction: column;
}

.filter-group label, .consultation-selector label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-dark);
  margin-bottom: 0.5rem;
}

.filter-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
  background-color: var(--bg-color);
  font-size: 1rem;
  transition: all 0.2s ease;
  -webkit-appearance: none;
  appearance: none;
}
.filter-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.select-wrapper {
  position: relative;
}
.select-arrow {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-light);
  pointer-events: none;
}


/* --- Details Pane --- */
.details-card {
  background-color: var(--card-bg);
  border-radius: 1rem;
  border: 1px solid var(--border-color);
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.details-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-color);
  background-color: #fdfdff;
}
.details-tabs button {
  flex: 1;
  padding: 1rem;
  font-size: 1rem;
  font-weight: 500;
  color: var(--text-light);
  background: none;
  border: none;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.2s ease;
  text-align: center;
}
@media (min-width: 640px) {
  .details-tabs button {
    flex: 0 1 auto;
    padding: 1rem 1.5rem;
  }
}
.details-tabs button:hover {
  color: var(--text-dark);
}
.details-tabs button.active {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}
.details-tabs button i {
  margin-right: 0.5rem;
}

/* New style for disabled tab button */
.details-tabs button.disabled-tab {
  cursor: not-allowed;
  opacity: 0.6;
  background-color: #f0f0f0;
  color: var(--text-light);
  border-bottom-color: transparent;
}
.details-tabs button.disabled-tab:hover {
  color: var(--text-light); /* Prevent changing color on hover for disabled */
}

.tab-content {
  padding: 2rem;
  min-height: 300px;
}
.tab-content > div {
  animation: fadeIn 0.3s ease;
}

/* --- States (Loading, Empty, No Selection) --- */
.loading-indicator, .empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--text-light);
}
.spinner {
  width: 50px; height: 50px;
  border: 5px solid #e2e8f0;
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
.empty-state .icon {
  font-size: 3rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}
.empty-state h2, .no-selection h2 {
  color: var(--text-dark);
}
.no-selection {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  min-height: 400px;
  background-color: var(--card-bg);
  border: 2px dashed var(--border-color);
  border-radius: 1rem;
  color: var(--text-light);
}
.no-selection i { font-size: 3rem; margin-bottom: 1rem; }

@keyframes spin {
  to { transform: rotate(360deg); }
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>