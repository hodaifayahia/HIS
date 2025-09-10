<script setup>
import { ref } from 'vue';
import { useToastr } from '../../../../toster';

const toastr = useToastr();

const props = defineProps({
  name: String,
  description: String,
  createdAt: String,
  createdBy: String,
  serviceName: String,
  isActive: Boolean,
  minPrice: Number,
  maxPrice: Number,
  // Convention detail props
  conventionMinPrice: Number,
  conventionMaxPrice: Number,
  discountPercentage: Number,
});


const copyToClipboard = (text) => {
  const textarea = document.createElement('textarea');
  textarea.value = text;
  document.body.appendChild(textarea);
  textarea.select();
  try {
    document.execCommand('copy');
    toastr.success('Text copied to clipboard');
  } catch (err) {
    console.error('Failed to copy text: ', err);
    toastr.error('Failed to copy text');
  } finally {
    document.body.removeChild(textarea);
  }
};

// Calculate patient share percentage
const patientSharePercentage = (100 - (props.discountPercentage || 0));
</script>

<template>
  <div class="card shadow-md border-light p-4 annex-details-card">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="flex-grow-1">
        <h3 class="h5 fw-semibold text-dark mb-1">{{ name }}</h3>
        <p class="small text-muted mb-0">{{ description }}</p>
      </div>
      <div class="annex-icon-header rounded-3 d-flex align-items-center justify-content-center">
        <i class="fas fa-folder-open text-white fs-6"></i>
      </div>
    </div>

    <!-- Information Grid -->
    <div class="row g-4">
      <!-- Left Column -->
      <div class="col-md-6 info-column">
        <!-- Service Name -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-purple-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-cogs text-purple fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Service</p>
            <p class="text-dark fw-medium">{{ serviceName || 'N/A' }}</p>
          </div>
        </div>

        <!-- Status -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-info-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-toggle-on text-info fs-6" v-if="isActive"></i>
            <i class="fas fa-toggle-off text-muted fs-6" v-else></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Status</p>
            <span class="badge" :class="isActive ? 'bg-success' : 'bg-secondary'">
              {{ isActive ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>

        <!-- Annex Min Price -->
        <div class="d-flex align-items-center gap-3 info-item" v-if="minPrice">
          <div class="info-icon-wrapper bg-orange-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-money-bill-wave text-orange fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Annex Min Price</p>
            <p class="text-dark fw-medium">{{ minPrice.toLocaleString() }} DZD</p>
          </div>
        </div>

        <!-- Annex Max Price -->
        <div class="d-flex align-items-center gap-3 info-item" v-if="maxPrice">
          <div class="info-icon-wrapper bg-danger-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-money-check-alt text-danger fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Annex Max Price</p>
            <p class="text-dark fw-medium">{{ maxPrice.toLocaleString() }} DZD</p>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="col-md-6 info-column">
        <!-- Convention Min Price -->
        <div class="d-flex align-items-center gap-3 info-item" v-if="conventionMinPrice">
          <div class="info-icon-wrapper bg-primary-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-coins text-primary fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Convention Min Price</p>
            <p class="text-dark fw-medium">{{ conventionMinPrice.toLocaleString() }} DZD</p>
          </div>
        </div>

        <!-- Convention Max Price -->
        <div class="d-flex align-items-center gap-3 info-item" v-if="conventionMaxPrice">
          <div class="info-icon-wrapper bg-success-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-hand-holding-usd text-success fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Convention Max Price</p>
            <p class="text-dark fw-medium">{{ conventionMaxPrice.toLocaleString() }} DZD</p>
          </div>
        </div>

        <!-- Company Share (Discount Percentage) -->
        <div class="d-flex align-items-center gap-3 info-item" v-if="discountPercentage">
          <div class="info-icon-wrapper bg-teal-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-building text-teal fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Company Share</p>
            <p class="text-dark fw-medium">{{ discountPercentage }}%</p>
          </div>
        </div>

        <!-- Patient Share -->
        <div class="d-flex align-items-center gap-3 info-item" v-if="discountPercentage">
          <div class="info-icon-wrapper bg-warning-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-user-injured text-warning fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Patient Share</p>
            <p class="text-dark fw-medium">{{ patientSharePercentage }}%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer with Creator Info -->
    <div class="row g-4 mt-2 pt-3 border-top">
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
      <div class="col-md-6">
        <!-- Created At -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-light-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-calendar-alt text-muted fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Created At</p>
            <p class="text-dark fw-medium">{{ createdAt }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom utility classes to mimic Tailwind's color palette and spacing */
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

/* Custom colors for purple, teal, orange to match previous design aesthetics */
.bg-purple-subtle { background-color: #e6e0ff; }
.text-purple { color: #6f42c1; }
.bg-teal-subtle { background-color: #d4f7ed; }
.text-teal { color: #20c997; }
.bg-orange-subtle { background-color: #ffe8d4; }
.text-orange { color: #fd7e14; }

/* Main Card Styling */
.annex-details-card {
  background-color: #ffffff;
  border-radius: 1rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  padding: 1.5rem;
}

/* Header Icon Styling */
.annex-icon-header {
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

/* Badge styling for status */
.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
  .annex-details-card {
    padding: 1rem;
  }
  .info-column .info-item {
    margin-bottom: 1rem;
  }
  .annex-icon-header {
    width: 2rem;
    height: 2rem;
  }
  .annex-icon-header .fas {
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
