<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useRouter } from 'vue-router';
import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';
import OldConsulation from '../OldConsulation/OldConsulation.vue';
import OpinionRequestReciverReplyModel from '../../../Components/OpinionRequests/OpinionRequestReciverReplyModel.vue';

const opinionRequests = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');
const router = useRouter();
const toaster = useToastr();
const selectedStatus = ref('pending');
const appointmentId = ref(null);
const patientId = ref(null);
const showModal = ref(false);
const showReplyModal = ref(false);
const currentRequestId = ref(null);
const currentPage = ref(1);
const pagination = ref({ last_page: 1, per_page: 10 });
const authStore = useAuthStoreDoctor();
const getOpinionRequests = async (page = 1, status = 'pending', search = '') => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get('/api/opinion-requests', {
      params: {
        page,
        status: status !== 'all' ? status : '',
        search,
        receiver_doctor_id: authStore.doctorData.id,
      },
    });

    opinionRequests.value = response.data.data;
    console.log('opinionRequests:', opinionRequests.value);
    
    // pagination.value = response.data.meta || { last_page: 1, per_page: 10 };
  } catch (err) {
    error.value = 'Failed to load opinion requests';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};


// Listen for real-time opinion request notifications
onMounted(async() => {
  await authStore.getDoctor()
    getOpinionRequests();
    
    if (authStore.doctorData.id) {
        Echo.private(`doctor.${authStore.doctorData.id}`)
            .listen('OpinionRequestCreated', (data) => {
                toaster.success('New opinion request received');
                getOpinionRequests(); // Refresh the list
            });
    }
});

// Watch for changes in filters and pagination
watch(
  [selectedStatus, searchQuery, currentPage],
  ([newStatus, newSearch, newPage]) => {
    console.log('Filter changed:', { newStatus, newSearch, newPage });
    getOpinionRequests(newPage, newStatus, newSearch);
  },
  { immediate: false }
);

// Handle search with debounce
let timeout = null;
const handleSearch = (event) => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    searchQuery.value = event.target.value;
    currentPage.value = 1;
  }, 300);
};

// View consultation details
const goToDetailConsultation = (request) => {
  showModal.value = true;
  appointmentId.value = request.appointment_id;
  patientId.value = request.patient_id;
  currentRequestId.value = request.id;
  console.log(request);
  
  
};



// Handle reply submission
const handleReplySubmitted = () => {
  getOpinionRequests(currentPage.value, selectedStatus.value, searchQuery.value);
};

// Handle page change
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    currentPage.value = page;
  }
};
</script>

<template>
  <div class="consultation-page">
    <div class="header-section">
      <div class="container-fluid">
        <h1 class="header-title">
          <i class="fas fa-notes-medical me-2"></i>
          Opinion Requests
        </h1>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="card main-card">
          <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
              <div class="btn-group status-filters">
                <button
                  v-for="status in ['all', 'pending', 'replied']"
                  :key="status"
                  class="btn"
                  :class="{
                    'btn-primary': selectedStatus === status,
                    'btn-outline-primary': selectedStatus !== status,
                  }"
                  @click="selectedStatus = status; currentPage = 1"
                >
                  <i
                    :class="{
                      'fas fa-list': status === 'all',
                      'fas fa-hourglass-start': status === 'pending',
                      'fas fa-reply': status === 'replied',
                    }"
                    class="me-2"
                  ></i>
                  {{ status.charAt(0).toUpperCase() + status.slice(1).toLowerCase() }}
                </button>
              </div>
              <div class="search-container">
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-primary"></i>
                  </span>
                  <input
                    type="text"
                    class="form-control border-start-0"
                    placeholder="Search requests..."
                    @input="handleSearch"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="card-body p-0">
            <div v-if="loading" class="loader-container">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"></span>
              </div>
            </div>
            <div v-else-if="error" class="alert alert-danger m-3">
              {{ error }}
            </div>
            <div v-else-if="opinionRequests.length === 0" class="empty-state">
              <i class="fas fa-calendar-times empty-icon"></i>
              <p class="empty-text">No opinion requests found</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover appointment-table">
                <thead>
                  <tr>
                    <th>#</th>
                    
                    <th>Sender Doctor</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  
                  <tr
                    v-for="(request, index) in opinionRequests"
                    :key="request.id"
                    class="appointment-row"
                  >
                    <td>{{ index + 1 + (currentPage - 1) * pagination.per_page }}</td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="fas fa-user-md text-info me-2"></i>
                        {{ request.sender_doctor_name || 'Dr. Unknown' }}
                      </div>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="`bg-${request.status === 'pending' ? 'warning' : request.status === 'accepted' ? 'success' : request.status === 'rejected' ? 'danger' : 'info'}`"
                      >
                        {{ request.status }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="far fa-calendar-alt text-success me-2"></i>
                        {{ new Date(request.created_at).toLocaleString() }}
                      </div>
                    </td>
                    <td>
                      <div class="d-flex gap-2">
                        <button
                          class="btn btn-primary btn-sm action-button mr-2"
                          @click="goToDetailConsultation(request)"
                        >
                          <i class="fas fa-eye me-1"></i>
                          View Consultation
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer" v-if="pagination.last_page > 1">
            <nav aria-label="Page navigation example">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="changePage(currentPage - 1)"
                    >Previous</a
                  >
                </li>
                <li
                  class="page-item"
                  v-for="page in pagination.last_page"
                  :key="page"
                  :class="{ 'active': currentPage === page }"
                >
                  <a class="page-link" href="#" @click.prevent="changePage(page)">{{
                    page
                  }}</a>
                </li>
                <li
                  class="page-item"
                  :class="{ 'disabled': currentPage === pagination.last_page }"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="changePage(currentPage + 1)"
                    >Next</a
                  >
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Consultation Modal -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <button class="modal-close" @click="showModal = false">×</button>
        <OldConsulation
        :patient-id="patientId"
        :consultation-id="null"
        :isdoneconsulaiton="false"
          :showreplymodel="true"
          :currentRequestId="currentRequestId"
          :appointment-id="appointmentId"
          :single-mode="true"
          @close="showModal = false"
          @refresh="getOpinionRequests(currentPage.value, 'all', searchQuery.value)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Existing styles unchanged */
.consultation-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.header-section {
  background: linear-gradient(135deg, #0061f2 0%, #6900f2 100%);
  padding: 2rem 1.5rem;
  margin-bottom: 2rem;
  border-radius: 0 0 0.5rem 0.5rem;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
}

.header-title {
  color: white;
  font-size: 1.75rem;
  margin: 0;
  font-weight: 600;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.modal-container {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 1200px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  padding: 20px;
}

.modal-close {
  position: absolute;
  top: 10px;
  right: 10px;
  border: none;
  background: none;
  font-size: 24px;
  cursor: pointer;
  padding: 5px 10px;
  border-radius: 4px;
  z-index: 1;
}

.modal-close:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

.main-card {
  border: none;
  border-radius: 1rem;
  box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
  margin-bottom: 2rem;
}

.card-header {
  background-color: white;
  border-bottom: 1px solid rgba(33, 40, 50, 0.125);
  padding: 1.5rem;
}

.status-filters .btn {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  margin-right: 0.5rem;
  font-weight: 500;
}

.search-container {
  min-width: 300px;
}

.search-container .input-group {
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  border-radius: 0.5rem;
  overflow: hidden;
}

.search-container input {
  border-radius: 0 0.5rem 0.5rem 0;
  padding: 0.75rem 1rem;
}

.loader-container {
  padding: 3rem;
  text-align: center;
}

.empty-state {
  padding: 3rem;
  text-align: center;
  color: #6c757d;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-text {
  font-size: 1.1rem;
  margin: 0;
}

.appointment-table {
  margin: 0;
}

.appointment-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  padding: 1rem;
}

.appointment-row {
  transition: all 0.2s ease;
  cursor: pointer;
}

.appointment-row:hover {
  background-color: #f8f9fa;
  transform: translateY(-1px);
}

.action-button {
  transition: all 0.2s ease;
  border-radius: 0.5rem;
  padding: 0.5rem 1rem;
}

.action-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.card-footer {
  background-color: white;
  border-top: 1px solid rgba(33, 40, 50, 0.125);
  padding: 1rem 1.5rem;
}

.pagination .page-item .page-link {
  color: #007bff;
  border: 1px solid #dee2e6;
  margin: 0 2px;
  border-radius: 0.25rem;
  transition: all 0.2s ease-in-out;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
  color: white;
}

.pagination .page-item.disabled .page-link {
  color: #6c757d;
  pointer-events: none;
  background-color: #e9ecef;
  border-color: #dee2e6;
}

.pagination .page-item .page-link:hover:not(.disabled) {
  background-color: #e9ecef;
  border-color: #007bff;
  color: #0056b3;
}

@media (max-width: 768px) {
  .header-section {
    padding: 1.5rem 1rem;
  }

  .status-filters {
    margin-bottom: 1rem;
    width: 100%;
  }

  .search-container {
    width: 100%;
  }

  .action-button {
    width: 100%;
    margin-top: 0.5rem;
  }
}
</style>