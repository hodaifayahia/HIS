<script setup>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const props = defineProps({
  id: String,
  contractId: String,
  status: String,
  createdAt: String,
  activateAt: String,
  description: String,
  createdBy: String,
});

const copyToClipboard = (text) => {
  const textarea = document.createElement('textarea');
  textarea.value = text;
  document.body.appendChild(textarea);
  textarea.select();
  try {
    document.execCommand('copy');
    toast.add({
      severity: 'success',
      summary: 'Copied',
      detail: 'Text copied to clipboard',
      life: 3000
    });
  } catch (err) {
    console.error('Failed to copy text: ', err);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to copy text',
      life: 3000
    });
  } finally {
    document.body.removeChild(textarea);
  }
};
</script>

<template>
  <div class="card shadow-md border-light p-4 avenant-details-card">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="flex-grow-1">
        <h3 class="h5 fw-semibold text-dark mb-1">Avenant Details</h3>
        <p class="small text-muted mb-0">{{ description || 'Avenant modification details' }}</p>
      </div>
      <div class="avenant-icon-header rounded-3 d-flex align-items-center justify-content-center">
        <i class="fas fa-file-edit text-white fs-6"></i>
      </div>
    </div>

    <!-- Information Grid -->
    <div class="row g-4">
      <!-- Left Column -->
      <div class="col-md-6 info-column">
        <!-- ID -->
       

        <!-- Contract ID -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-green-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-file-contract text-green fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Contract ID</p>
            <div class="d-flex align-items-center">
              <p class="text-dark fw-medium me-2 text-truncate">{{ contractId }}</p>
              <button
                class="copy-btn btn btn-sm border-0 bg-transparent p-1"
                @click="copyToClipboard(contractId)"
              >
                <i class="fas fa-copy"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Status -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-purple-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-check-circle text-purple fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Status</p>
            <span class="badge bg-primary">{{ status }}</span>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="col-md-6 info-column">
        <!-- Activate At -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-teal-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-clock text-teal fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Activate At</p>
            <p class="text-dark fw-medium">{{ activateAt }}</p>
          </div>
        </div>

        <!-- Created At -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-orange-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-calendar-alt text-orange fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Created At</p>
            <p class="text-dark fw-medium">{{ createdAt }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer with Creator Info -->
    <div class="row g-4 mt-2 pt-3 border-top" v-if="createdBy">
      <div class="col-md-6">
        <!-- Created By -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-secondary-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-user text-secondary fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Created By</p>
            <p class="text-dark fw-medium">{{ createdBy }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom utility classes to match your color palette */
.bg-primary-subtle { background-color: #cfe2ff; }
.text-primary { color: #007bff; }
.bg-success-subtle { background-color: #d4edda; }
.text-success { color: #28a745; }
.bg-danger-subtle { background-color: #f8d7da; }
.text-danger { color: #dc3545; }
.bg-warning-subtle { background-color: #fff3cd; }
.text-warning { color: #ffc107; }
.bg-info-subtle { background-color: #d1ecf1; }
.text-info { color: #17a2b8; }
.bg-light-subtle { background-color: #f8f9fa; }
.bg-secondary-subtle { background-color: #e2e3e5; }
.text-secondary { color: #6c757d; }
.text-muted { color: #6c757d; }
.text-dark { color: #343a40; }

/* Custom colors */
.bg-purple-subtle { background-color: #e6e0ff; }
.text-purple { color: #6f42c1; }
.bg-teal-subtle { background-color: #d4f7ed; }
.text-teal { color: #20c997; }
.bg-orange-subtle { background-color: #ffe8d4; }
.text-orange { color: #fd7e14; }
.bg-blue-subtle { background-color: #dbeafe; }
.text-blue { color: #3b82f6; }
.bg-green-subtle { background-color: #dcfce7; }
.text-green { color: #16a34a; }

/* Main Card Styling */
.avenant-details-card {
  background-color: #ffffff;
  border-radius: 1rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  padding: 1.5rem;
}

/* Header Icon Styling */
.avenant-icon-header {
  width: 2.5rem;
  height: 2.5rem;
  background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Information Item Styling */
.info-column .info-item {
  margin-bottom: 1.5rem;
}
.info-column .info-item:last-child {
  margin-bottom: 0;
}

.info-icon-wrapper {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.info-item p {
  margin-bottom: 0;
  line-height: 1.2;
}

.info-item .small {
  font-size: 0.75rem;
}

.info-item .fw-medium {
  font-weight: 500;
}

/* Copy button styling */
.copy-btn {
  color: #9ca3af;
  transition: color 0.2s;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.75rem;
}

.copy-btn:hover {
  color: #3b82f6;
}

/* Badge styling */
.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
  .avenant-details-card {
    padding: 1rem;
  }
  .info-column .info-item {
    margin-bottom: 1rem;
  }
  .avenant-icon-header {
    width: 2rem;
    height: 2rem;
  }
  .avenant-icon-header .fas {
    font-size: 0.875rem;
  }
  .info-icon-wrapper {
    width: 2rem;
    height: 2rem;
  }
  .info-icon-wrapper .fas {
    font-size: 0.875rem;
  }
}
</style>
