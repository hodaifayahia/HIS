<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import RemiseNotificationService from '../../../Components/Apps/services/Remise/RemiseNotificationService.js';
import { useToastr } from '../../../Components/toster';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../../stores/auth';

const remiseRequests = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');
const router = useRouter();
const toaster = useToastr();
const selectedStatus = ref('pending');
const showModal = ref(false);
const showReplyModal = ref(false);
const activeRequest = ref(null);
const currentPage = ref(1);
const pagination = ref({ last_page: 1, per_page: 10, total: 0 });
const authStore = useAuthStore();

const getRemiseRequests = async (page = 1, status = 'pending', search = '') => {
  loading.value = true;
  error.value = null;

  try {
    const response = await RemiseNotificationService.getPendingRequests({
      page,
      status: status !== 'all' ? status : '',
      q: search,
    });
    
    console.log('Full API Response:', response);

    if (!response.success) {
      throw new Error(response.message || 'Failed to load remise requests');
    }

    // Extract data correctly - the structure is response.data.items (not response.data.data)
    const responseData = response.data;
    remiseRequests.value = responseData.items || [];
    
    pagination.value = {
      last_page: responseData.last_page || Math.ceil((responseData.total || 0) / (responseData.per_page || 20)),
      per_page: responseData.per_page || 20,
      total: responseData.total || 0,
      current_page: responseData.current_page || 1
    };
    
    console.log('Extracted remiseRequests:', remiseRequests.value);
    console.log('Pagination:', pagination.value);
    
    // Normalize contribution data for editing
    remiseRequests.value.forEach((r) => {
      if (r.prestations && Array.isArray(r.prestations)) {
        r.prestations.forEach((p) => {
          if (p.contributions && Array.isArray(p.contributions)) {
            p.contributions.forEach((c) => {
              // Handle both raw and formatted amounts from resource
              if (c.final_amount === undefined || c.final_amount === null) {
                c.final_amount = c.proposed_amount_raw ?? c.proposed_amount ?? 0;
              }
              // Ensure we have raw numbers for calculations
              c.proposed_amount_raw = c.proposed_amount_raw ?? (parseFloat(c.proposed_amount) || 0);
              c.approved_amount_raw = c.approved_amount_raw ?? (parseFloat(c.approved_amount) || 0);
            });
          }
        });
      }
    });
  } catch (err) {
    error.value = err.message || 'Failed to load remise requests';
    toaster.error(error.value);
    console.error('Error loading remise requests:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  getRemiseRequests();
});

// Watch for changes in filters and pagination
watch(
  [selectedStatus, searchQuery, currentPage],
  ([newStatus, newSearch, newPage]) => {
    console.log('Filter changed:', { newStatus, newSearch, newPage });
    getRemiseRequests(newPage, newStatus, newSearch);
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

// View remise request details
const viewRequestDetails = (request) => {
  activeRequest.value = JSON.parse(JSON.stringify(request)); // Deep clone
  showModal.value = true;
  console.log('Selected request:', request);
};

// Calculate total for request - handle both formatted and raw amounts
const totalForRequest = (request) => {
  if (!request.prestations || !Array.isArray(request.prestations)) {
    return 0;
  }
  
  return request.prestations.reduce((sum, prestation) => {
    if (!prestation.contributions || !Array.isArray(prestation.contributions)) {
      return sum + (parseFloat(prestation.proposed_amount) || 0);
    }
    
    return sum + prestation.contributions.reduce((contribSum, contrib) => {
      const amount = contrib.final_amount ?? 
                   contrib.approved_amount_raw ?? 
                   contrib.proposed_amount_raw ?? 
                   (parseFloat(contrib.proposed_amount) || 0);
      return contribSum + amount;
    }, 0);
  }, 0);
};

// Handle approve
const handleApprove = async () => {
  if (!activeRequest.value) return;
  
  if (!confirm('Are you sure you want to approve this remise request?')) return;

  try {
    const contributions = [];
    
    if (activeRequest.value.prestations && Array.isArray(activeRequest.value.prestations)) {
      activeRequest.value.prestations.forEach((p) => {
        if (p.contributions && Array.isArray(p.contributions)) {
          p.contributions.forEach((c) => {
            contributions.push({ 
              id: c.id, 
              final_amount: parseFloat(c.final_amount) || (parseFloat(c.proposed_amount_raw) || 0)
            });
          });
        }
      });
    }

    const payload = { 
      contributions, 
      comment: activeRequest.value._approvalComment || '' 
    };

    console.log('Approval payload:', payload);

    const response = await RemiseNotificationService.approveRequest(activeRequest.value.id, payload);
    
    if (!response.success) {
      throw new Error(response.message || 'Failed to approve request');
    }

    toaster.success('Remise request approved successfully');
    remiseRequests.value = remiseRequests.value.filter(r => r.id !== activeRequest.value.id);
    closeModal();
  } catch (err) {
    toaster.error(err.message || 'Failed to approve request');
    console.error('Approval error:', err);
  }
};

// Handle reject
const handleReject = async () => {
  if (!activeRequest.value) return;
  
  const comment = activeRequest.value._rejectionComment || prompt('Enter rejection reason:');
  if (comment === null) return;

  try {
    const response = await RemiseNotificationService.rejectRequest(activeRequest.value.id, { comment });
    
    if (!response.success) {
      throw new Error(response.message || 'Failed to reject request');
    }

    toaster.success('Remise request rejected');
    remiseRequests.value = remiseRequests.value.filter(r => r.id !== activeRequest.value.id);
    closeModal();
  } catch (err) {
    toaster.error(err.message || 'Failed to reject request');
    console.error('Rejection error:', err);
  }
};

const closeModal = () => {
  activeRequest.value = null;
  showModal.value = false;
};

// Handle page change
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    currentPage.value = page;
  }
};

const filteredRequests = computed(() => {
  if (!searchQuery.value) return remiseRequests.value;
  const q = searchQuery.value.toLowerCase();
  return remiseRequests.value.filter((r) => {
    const patientName = r.patient?.name || r.patient?.fullname || '';
    const senderName = r.sender?.name || '';
    const receiverName = r.receiver?.name || '';
    const message = r.message || '';
    
    return String(r.id).includes(q) ||
           patientName.toLowerCase().includes(q) ||
           senderName.toLowerCase().includes(q) ||
           receiverName.toLowerCase().includes(q) ||
           message.toLowerCase().includes(q);
  });
});
</script>

<template>
  <div class="consultation-page">
    <div class="header-section">
      <div class="container-fluid">
        <h1 class="header-title">
          <i class="fas fa-file-invoice-dollar me-2"></i>
          Remise Requests
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
                  v-for="status in ['all', 'pending', 'accepted', 'rejected']"
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
                      'fas fa-check-circle': status === 'accepted',
                      'fas fa-times-circle': status === 'rejected',
                    }"
                    class="me-2"
                  ></i>
                  {{ status.charAt(0).toUpperCase() + status.slice(1) }}
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
                    placeholder="Search remise requests..."
                    @input="handleSearch"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="card-body p-0">
            <div v-if="loading" class="loader-container">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <div v-else-if="error" class="alert alert-danger m-3">
              {{ error }}
            </div>
            <div v-else-if="filteredRequests.length === 0" class="empty-state">
              <i class="fas fa-file-invoice empty-icon"></i>
              <p class="empty-text">No remise requests found</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover appointment-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Patient</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Prestations</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(request, index) in filteredRequests"
                    :key="request.id"
                    class="appointment-row"
                  >
                    <td>{{ index + 1 + (currentPage - 1) * pagination.per_page }}</td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="fas fa-user text-info me-2"></i>
                        <div>
                          <div class="fw-bold">{{ request.patient?.name || 'Unknown Patient' }}</div>
                          <small class="text-muted">{{ request.patient?.phone || '' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="fas fa-user-nurse text-success me-2"></i>
                        <div>
                          <div class="fw-bold">{{ request.sender?.name || 'Unknown Sender' }}</div>
                          <small class="text-muted">{{ request.sender?.role || '' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="fas fa-user-doctor text-primary me-2"></i>
                        <div>
                          <div class="fw-bold">{{ request.receiver?.name || 'Unknown Receiver' }}</div>
                          <small class="text-muted">{{ request.receiver?.role || '' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-secondary">
                        {{ request.summary?.total_prestations || (request.prestations?.length || 0) }} prestations
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-info">
                        {{ request.summary?.total_amount_formatted || (totalForRequest(request).toFixed(2) + ' DZD') }}
                      </span>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="`bg-${
                          request.status === 'pending' ? 'warning' : 
                          request.status === 'accepted' ? 'success' : 
                          request.status === 'rejected' ? 'danger' : 'secondary'
                        }`"
                      >
                        {{ request.status }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="far fa-calendar-alt text-primary me-2"></i>
                        <small>{{ new Date(request.created_at).toLocaleString() }}</small>
                      </div>
                    </td>
                    <td>
                      <button
                        class="btn btn-primary btn-sm action-button"
                        @click="viewRequestDetails(request)"
                      >
                        <i class="fas fa-eye me-1"></i>
                        View Details
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <div class="card-footer" v-if="pagination.last_page > 1">
            <nav aria-label="Page navigation">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="changePage(currentPage - 1)"
                  >Previous</a>
                </li>
                <li
                  class="page-item"
                  v-for="page in pagination.last_page"
                  :key="page"
                  :class="{ 'active': currentPage === page }"
                >
                  <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                </li>
                <li
                  class="page-item"
                  :class="{ 'disabled': currentPage === pagination.last_page }"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="changePage(currentPage + 1)"
                  >Next</a>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Remise Request Details Modal -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <button class="modal-close" @click="closeModal">×</button>
        <div class="modal-content">
          <h3 class="modal-title">
            <i class="fas fa-file-invoice-dollar me-2"></i>
            Remise Request Details
          </h3>
          
          <div v-if="activeRequest" class="request-details">
            <!-- Request Summary -->
            <div class="info-section">
              <div class="row">
                <div class="col-md-4">
                  <strong><i class="fas fa-user me-1"></i> Patient:</strong><br>
                  <span class="text-primary">{{ activeRequest.patient?.name || 'Unknown' }}</span><br>
                  <small class="text-muted">{{ activeRequest.patient?.phone || '' }}</small>
                </div>
                <div class="col-md-4">
                  <strong><i class="fas fa-user-nurse me-1"></i> Sender:</strong><br>
                  <span class="text-success">{{ activeRequest.sender?.name || 'Unknown' }}</span><br>
                  <small class="text-muted">{{ activeRequest.sender?.role || '' }}</small>
                </div>
                <div class="col-md-4">
                  <strong><i class="fas fa-user-doctor me-1"></i> Receiver:</strong><br>
                  <span class="text-primary">{{ activeRequest.receiver?.name || 'Unknown' }}</span><br>
                  <small class="text-muted">{{ activeRequest.receiver?.role || '' }}</small>
                </div>
              </div>
              <div class="mt-3">
                <strong><i class="fas fa-comment me-1"></i> Message:</strong><br>
                <p class="text-muted">{{ activeRequest.message || 'No message' }}</p>
              </div>
              <div class="row mt-2">
                <div class="col-md-6">
                  <strong><i class="fas fa-calendar me-1"></i> Created:</strong>
                  {{ new Date(activeRequest.created_at).toLocaleString() }}
                </div>
                <div class="col-md-6">
                  <strong><i class="fas fa-money-bill me-1"></i> Total Amount:</strong>
                  <span class="badge bg-info">
                    {{ activeRequest.summary?.total_amount_formatted || (totalForRequest(activeRequest).toFixed(2) + ' DZD') }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Prestations and Contributions -->
            <div class="prestations-section">
              <h4><i class="fas fa-list me-2"></i>Prestations & Contributions</h4>
              <div v-for="(prestation, index) in activeRequest.prestations" :key="prestation.id" class="prestation-card">
                <h5>
                  <i class="fas fa-stethoscope me-2"></i>
                  {{ prestation.prestation?.name || `Prestation #${prestation.prestation_id}` }}
                  <span class="badge bg-secondary ms-2">{{ prestation.proposed_amount || '0.00' }} DZD</span>
                </h5>
                
                <div class="table-responsive">
                  <table class="table table-sm table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th><i class="fas fa-user me-1"></i>Contributor</th>
                        <th><i class="fas fa-tag me-1"></i>Role</th>
                        <th><i class="fas fa-money-bill-wave me-1"></i>Proposed</th>
                        <th><i class="fas fa-edit me-1"></i>Final Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="contribution in prestation.contributions" :key="contribution.id">
                        <td>
                          <div class="d-flex align-items-center">
                            <img 
                              v-if="contribution.user?.avatar" 
                              :src="`/storage/${contribution.user.avatar}`" 
                              class="rounded-circle me-2" 
                              width="24" 
                              height="24"
                              @error="$event.target.style.display='none'"
                            >
                            <i v-else class="fas fa-user-circle me-2 text-muted"></i>
                            <div>
                              <div class="fw-bold">{{ contribution.user?.name || `User #${contribution.user_id}` }}</div>
                              <small class="text-muted">{{ contribution.user?.email || '' }}</small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="badge bg-outline-primary">{{ contribution.role }}</span>
                        </td>
                        <td>
                          <span class="text-success fw-bold">
                            {{ contribution.proposed_amount || '0.00' }} DZD
                          </span>
                        </td>
                        <td>
                          <div class="input-group input-group-sm">
                            <input
                              v-model="contribution.final_amount"
                              type="number"
                              step="0.01"
                              min="0"
                              class="form-control"
                              :disabled="activeRequest.status !== 'pending'"
                            />
                            <span class="input-group-text">DZD</span>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Comment Section -->
            <div class="comment-section" v-if="activeRequest.status === 'pending'">
              <label class="form-label">
                <i class="fas fa-comment me-1"></i>
                Approval Comment (Optional):
              </label>
              <textarea
                v-model="activeRequest._approvalComment"
                class="form-control"
                rows="3"
                placeholder="Enter your comment about this approval/rejection..."
              ></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons" v-if="activeRequest.status === 'pending'">
              <button class="btn btn-danger me-2" @click="handleReject">
                <i class="fas fa-times me-1"></i>
                Reject Request
              </button>
              <button class="btn btn-success" @click="handleApprove">
                <i class="fas fa-check me-1"></i>
                Approve Request
              </button>
            </div>
            
            <!-- Status Info for non-pending requests -->
            <div v-else class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              This request has been <strong>{{ activeRequest.status }}</strong> and cannot be modified.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<!-- Keep all existing styles -->
<style scoped>
/* All existing styles remain the same */
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
  max-width: 1000px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
}

.modal-close {
  position: absolute;
  top: 15px;
  right: 15px;
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

.modal-content {
  padding: 2rem;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-title {
  margin-bottom: 1.5rem;
  color: #333;
}

.info-section {
  background-color: #f8f9fa;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
}

.prestations-section {
  margin-bottom: 1.5rem;
}

.prestation-card {
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  background-color: #f8f9fa;
}

.prestation-card h5 {
  color: #495057;
  border-bottom: 1px solid #dee2e6;
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
}

.table-bordered {
  border: 1px solid #dee2e6;
}

.table-light {
  background-color: #f8f9fa;
}

.input-group-sm .form-control {
  font-size: 0.875rem;
}

.comment-section {
  margin-bottom: 1.5rem;
}

.action-buttons {
  display: flex;
  justify-content: end;
  gap: 0.5rem;
}

.badge.bg-outline-primary {
  color: #0d6efd;
  border: 1px solid #0d6efd;
  background: transparent;
}

.request-details .row {
  margin-bottom: 1rem;
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

  .modal-container {
    width: 95%;
    margin: 1rem;
  }

  .prestation-card {
    padding: 1rem;
  }

  .table-responsive {
    font-size: 0.875rem;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>