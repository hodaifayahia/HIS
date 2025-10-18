<script setup>
import { ref } from 'vue';
import { useToastr } from '../../../../toster';

const toastr = useToastr();

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
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
</script>

<template>
  <div class="card shadow-md border-light p-4 company-details-card">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <h3 class="h5 fw-semibold text-dark mb-0">Company Details</h3>
      <div class="company-icon-header rounded-3 d-flex align-items-center justify-content-center">
        <i class="fas fa-building text-white fs-6"></i>
      </div>
    </div>

    <!-- Information Grid -->
    <div class="row g-4">
      <!-- Left Column -->
      <div class="col-md-6 info-column">
        <!-- ID -->
       
        <!-- Name -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-success-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-building text-success fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Company Name</p>
            <p class="text-dark fw-medium">{{ company.name }}</p>
          </div>
        </div>

        <!-- Address -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-purple-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-map-marker-alt text-purple fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <p class="small text-muted mb-1">Address</p>
            <p class="text-dark fw-medium">{{ company.address }}</p>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="col-md-6 info-column">
        <!-- Phone -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-orange-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-phone-alt text-orange fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2 min-w-0">
            <p class="small text-muted mb-1">Phone</p>
            <div class="d-flex align-items-center">
              <p class="text-dark fw-medium me-2 truncate-text">{{ company.phone }}</p>
              <button
                class="btn btn-sm btn-outline-secondary copy-button"
                @click="copyToClipboard(company.phone)"
                title="Copy Phone"
              >
                <i class="fas fa-copy fs-6"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Email -->
        <div class="d-flex align-items-center gap-3 info-item">
          <div class="info-icon-wrapper bg-danger-subtle rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-envelope text-danger fs-6"></i>
          </div>
          <div class="flex-grow-1 ml-2 min-w-0">
            <p class="small text-muted mb-1">Email</p>
            <div class="d-flex align-items-center">
              <p class="text-dark fw-medium me-2 truncate-text">{{ company.email }}</p>
              <button
                class="btn btn-sm btn-outline-secondary copy-button"
                @click="copyToClipboard(company.email)"
                title="Copy Email"
              >
                <i class="fas fa-copy fs-6"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<!-- Keep your existing styles -->


<style scoped>
/* Custom utility classes to mimic Tailwind's color palette and spacing */
.bg-primary-subtle { background-color: #cfe2ff; } /* Bootstrap primary light */
.text-primary { color: #007bff; } /* Bootstrap primary */
.bg-success-subtle { background-color: #d4edda; } /* Bootstrap success light */
.text-success { color: #28a745; } /* Bootstrap success */
.bg-danger-subtle { background-color: #f8d7da; } /* Bootstrap danger light */
.text-danger { color: #dc3545; } /* Bootstrap danger */
.bg-warning-subtle { background-color: #fff3cd; } /* Bootstrap warning light */
.text-warning { color: #ffc107; } /* Bootstrap warning */
.bg-info-subtle { background-color: #d1ecf1; } /* Bootstrap info light */
.text-info { color: #17a2b8; } /* Bootstrap info */
.bg-light-subtle { background-color: #f8f9fa; } /* Bootstrap light */
.text-muted { color: #6c757d; } /* Bootstrap muted */
.text-dark { color: #343a40; } /* Bootstrap dark */

/* Custom colors for purple, teal, orange to match previous design aesthetics */
.bg-purple-subtle { background-color: #e6e0ff; }
.text-purple { color: #6f42c1; }
.bg-teal-subtle { background-color: #d4f7ed; }
.text-teal { color: #20c997; }
.bg-orange-subtle { background-color: #ffe8d4; }
.text-orange { color: #fd7e14; }


/* Main Card Styling */
.company-details-card {
  background-color: #ffffff;
  border-radius: 1rem; /* Rounded-xl */
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* Shadow-md */
  border: 1px solid #e2e8f0; /* Border-gray-200 */
  padding: 1.5rem; /* p-6 (converted from Tailwind unit) */
}

/* Header Icon Styling */
.company-icon-header {
  width: 2.5rem; /* w-8 */
  height: 2.5rem; /* h-8 */
  background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%); /* from-blue-500 to-purple-600 */
  border-radius: 0.5rem; /* rounded-lg */
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Information Item Styling */
.info-column .info-item {
  margin-bottom: 1.5rem; /* space-y-4 for vertical spacing */
}
.info-column .info-item:last-child {
  margin-bottom: 0;
}

.info-icon-wrapper {
  width: 2.5rem; /* w-8 */
  height: 2.5rem; /* h-8 */
  border-radius: 0.5rem; /* rounded-lg */
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.info-item p {
  margin-bottom: 0; /* Remove default paragraph margin */
  line-height: 1.2; /* Adjust line height for better spacing */
}

.info-item .small {
  font-size: 0.75rem; /* text-xs */
}

.info-item .fw-medium {
  font-weight: 500; /* font-medium */
}

.truncate-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%; /* Ensure it truncates within its container */
}

/* Copy Button Styling */
.copy-button {
  color: #6c757d; /* text-gray-400 */
  padding: 0.25rem; /* p-1 */
  border-radius: 0.25rem; /* rounded */
  transition: all 0.2s ease-in-out; /* transition-colors */
  border: none; /* Remove default button border */
  background-color: transparent; /* Ensure transparent background */
}

.copy-button:hover {
  color: #007bff; /* hover:text-blue-500 */
  background-color: rgba(0, 123, 255, 0.1); /* Light background on hover */
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
  .company-details-card {
    padding: 1rem;
  }
  .info-column .info-item {
    margin-bottom: 1rem; /* Adjust spacing for smaller screens */
  }
  .company-icon-header {
    width: 2rem;
    height: 2rem;
  }
  .company-icon-header .fas {
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


