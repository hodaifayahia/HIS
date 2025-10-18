<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import axios from 'axios';

import SpecializationSelector from '../../../Components/OpinionRequests/SpecializationSelector.vue';
import DoctorSelector from '../../../Components/OpinionRequests/DoctorSelector.vue';
import RequestHistory from '../../../Components/OpinionRequests/RequestHistory.vue';
import ProgressSteps from '../../../Components/OpinionRequests/ProgressSteps.vue';

const props = defineProps({
  appointmentId: {
    type: [Number, String],
    required: true
  },
  doctorId: {
    type: [Number, String],
    required: true
  },
  patientId: {
    type: [Number, String],
    required: true
  }
});

const toaster = useToastr();
const swal = useSweetAlert();

const specializations = ref([]);
const selectedSpecialization = ref(null);
const doctors = ref([]);
const selectedDoctors = ref([]);
const opinionRequests = ref([]);
const loading = ref(false);
const showReplies = ref(false);
const selectedRequest = ref(null);
const searchTerm = ref('');
const currentStep = ref(1);
const filterStatus = ref('all');
const sortOrder = ref('newest');
const requestDescription = ref(''); // New state for the textarea


const filteredDoctors = computed(() => {
  return doctors.value
    .filter(doctor => doctor.id !== parseInt(props.doctorId))
    .filter(doctor => {
      if (!searchTerm.value) return true;
      return (
        doctor.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        doctor.specialization.toLowerCase().includes(searchTerm.value.toLowerCase())
      )
    })
})

const filteredRequests = computed(() => {
  let filtered = Array.isArray(opinionRequests.value) ? opinionRequests.value : [];

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(request => request.status === filterStatus.value);
  }

  return filtered.sort((a, b) => {
    const dateA = new Date(a.created_at);
    const dateB = new Date(b.created_at);
    return sortOrder.value === 'newest' ? dateB - dateA : dateA - dateB;
  });
});

const selectedDoctorsInfo = computed(() => {
  return doctors.value.filter(doctor => selectedDoctors.value.includes(doctor.id));
});

// Fetch specializations
const fetchSpecializations = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/specializations');
    specializations.value = response.data.data;
  } catch (error) {
    console.error('Error fetching specializations:', error);
    toaster.error('Failed to load specializations');
  } finally {
    loading.value = false;
  }
};

// Fetch doctors by specialization
const fetchDoctors = async (specializationId) => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/doctors/specializations/${specializationId}`);
    doctors.value = response.data.data;
    currentStep.value = 2;
  } catch (error) {
    console.error('Error fetching doctors:', error);
    toaster.error('Failed to load doctors');
  } finally {
    loading.value = false;
  }
};

// Send opinion request
const sendOpinionRequest = async () => {
  if (selectedDoctors.value.length === 0) {
    toaster.error('Please select at least one doctor');
    return;
  }
  if (!requestDescription.value.trim()) {
    toaster.error('Please provide a description for your request.');
    return;
  }

  const result = await swal.fire({
    title: 'Send Opinion Requests?',
    text: `You are about to send requests to ${selectedDoctors.value.length} doctor(s)`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Send Requests',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;

  try {
    loading.value = true;
    const requests = selectedDoctors.value.map(doctorId => ({
      appointment_id: props.appointmentId,
      sender_doctor_id: parseInt(props.doctorId),
      reciver_doctor_id: doctorId,
      patient_id: props.patientId,
      request: requestDescription.value,
      status: 'pending'
    }));

    // Send requests one by one to trigger events properly
    for (const request of requests) {
      await axios.post('/api/opinion-requests', request);
    }

    toaster.success('Opinion requests sent successfully');
    resetForm();
    fetchOpinionRequests();
  } catch (error) {
    console.error('Error sending opinion requests:', error);
    toaster.error('Failed to send opinion requests');
  } finally {
    loading.value = false;
  }
};

// Fetch existing opinion requests
const fetchOpinionRequests = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/opinion-requests/${props.appointmentId}`);
    opinionRequests.value = response.data.data;

  } catch (error) {
    console.error('Error fetching opinion requests:', error);
    toaster.error('Failed to load opinion requests');
  } finally {
    loading.value = false;
  }
};

// View replies for a specific request
const viewReplies = (request) => {
  selectedRequest.value = request;
  showReplies.value = true;
};

// Toggle doctor selection
const toggleDoctorSelection = (doctorId) => {
  if (selectedDoctors.value.includes(doctorId)) {
    selectedDoctors.value = selectedDoctors.value.filter(id => id !== doctorId);
  } else {
    selectedDoctors.value.push(doctorId);
  }
};

// Reset form
const resetForm = () => {
  selectedSpecialization.value = null;
  selectedDoctors.value = [];
  searchTerm.value = '';
  requestDescription.value = ''; // Clear the textarea
  currentStep.value = 1;
  selectedSpecialization.value = null;
  fetchOpinionRequests();
};
const playNotificationSound = () => {
    try {
        const audio = new Audio('/storage/sound/notification.mp3');
        audio.play().catch(e => console.log('Could not play notification sound:', e));
    } catch (error) {
        console.log('Notification sound not available:', error);
    }
};


onMounted(() => {
  fetchSpecializations();
  fetchOpinionRequests();

  // Listen for new replies in real-time
  Echo.private(`doctor.${props.doctorId}`)
    .listen('.OpinionRequestReplied', (data) => {
      // Show a toast for the reply

      // Set the notification flag
      toaster.success(
        `Your opinion request was replied by ${data.receiver_doctor_name}`,
        {
          duration: 5000,
          position: 'top-right'
        }
      );
      // Optionally, you can set a flag or badge for replies as well
      // For example, you could add a hasNewReply.value = true;
      playNotificationSound();

      // Fetch the latest opinion requests instantly
      fetchOpinionRequests();
    })
    .error((error) => {
      console.error('WebSocket connection error:', error);
      console.error('Error details:', error);
    });
});

// Watch for specialization selection
watch(selectedSpecialization, (newVal) => {
  if (newVal) {
    fetchDoctors(newVal);
  } else {
    doctors.value = [];
    selectedDoctors.value = [];
    currentStep.value = 1;
  }
});
</script>

<template>
  <div class="opinion-request-container">
    <div class="header-section">
      <div class="header-content">
      </div>

      <ProgressSteps :currentStep="currentStep" />
    </div>

    <div class="form-section">
      <div class="premium-card">
        <SpecializationSelector v-if="currentStep === 1" :specializations="specializations"
          :selectedSpecialization="selectedSpecialization" :loading="loading"
          @update:selectedSpecialization="selectedSpecialization = $event" />

        <DoctorSelector v-if="currentStep === 2 && doctors.length > 0" :doctors="doctors"
          :filteredDoctors="filteredDoctors" :selectedDoctors="selectedDoctors"
          :selectedDoctorsInfo="selectedDoctorsInfo" :searchTerm="searchTerm" :loading="loading"
          :requestDescription="requestDescription" @update:searchTerm="searchTerm = $event"
          @toggleDoctorSelection="toggleDoctorSelection" @update:requestDescription="requestDescription = $event"
          @resetForm="resetForm" @sendOpinionRequest="sendOpinionRequest" />
      </div>
    </div>

    <RequestHistory :filteredRequests="filteredRequests" :filterStatus="filterStatus" :sortOrder="sortOrder"
      @update:filterStatus="filterStatus = $event" @update:sortOrder="sortOrder = $event" />

    <!-- <ReplyModal
      :showReplies="showReplies"
      :selectedRequest="selectedRequest"
      @closeModal="showReplies = false"
    /> -->

    <!-- <LoadingOverlay v-if="loading" /> -->
  </div>
</template>

<style scoped>
/* OpinionRequestForm.vue styles - These will primarily define layout and overall theming. */
.opinion-request-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  background: linear-gradient(135deg, #4566fa 0%, #5170f7 100%);
  min-height: 100vh;
}

.header-section {
  margin-bottom: 2rem;
}

.header-content {
  text-align: center;
  margin-bottom: 2rem;
}

.main-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: white;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.subtitle {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.9);
  margin: 0.5rem 0 0;
}

/* Premium Card (common style) */
.premium-card {
  background: white;
  border-radius: 20px;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 2rem;
  margin-bottom: 2rem;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Base button styles - can be moved to a common utility CSS or passed as props/slots if highly dynamic */
.btn-primary,
.btn-secondary,
.btn-outline,
.btn-filter {
  padding: 0.875rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  border: none;
  font-size: 0.95rem;
  position: relative;
  overflow: hidden;
}

.btn-primary {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border: 2px solid #e2e8f0;

}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  color: #4f46e5;
  border: 2px solid #4f46e5;
}

.btn-outline:hover:not(:disabled) {
  background: #4f46e5;
  color: white;
  transform: translateY(-1px);
}

.btn-outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-filter {
  background: rgba(255, 255, 255, 0.9);
  color: #475569;
  border: 2px solid #e2e8f0;
  backdrop-filter: blur(10px);
}

.btn-filter:hover {
  background: white;
  border-color: #4f46e5;
  color: #4f46e5;
}

/* Spinner */
.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .opinion-request-container {
    padding: 1rem;
  }

  .main-title {
    font-size: 2rem;
  }

  .form-actions {
    justify-content: stretch;
  }

  .form-actions .btn-primary,
  .form-actions .btn-secondary {
    flex: 1;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .main-title {
    font-size: 1.8rem;
  }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Focus Styles for Accessibility */
button:focus {
  outline: 3px solid #4f46e5;
  outline-offset: 2px;
}

select:focus,
input:focus {
  outline: none;
}

/* Print Styles */
@media print {

  .modal-overlay,
  .loading-overlay,
  .form-actions,
  .header-actions {
    display: none !important;
  }

  .opinion-request-container {
    background: white !important;
    padding: 1rem !important;
  }

  .premium-card {
    box-shadow: none !important;
    border: 1px solid #e2e8f0 !important;
  }
}
</style>