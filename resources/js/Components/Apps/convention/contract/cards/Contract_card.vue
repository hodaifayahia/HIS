<script setup>
import { ref } from 'vue';
// --- START: Toastr setup ---
// Make sure this path is correct for your toastr setup
import { useToastr } from '../../../../toster'; 
// --- END: Toastr setup ---

const toast = useToastr(); // Use the toastr instance

const props = defineProps({
  contract:{
    type:Object,
    required:true,
  }
  
});
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text);
  toast.success('Text copied to clipboard'); // Changed to toastr.success
};
</script>

<template>
  <div class="card shadow-sm mb-4">
    <div class="card-body p-4">
      <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
        <h3 class="h5 font-weight-bold text-gray-800 mb-0">Contract details</h3>
        <div class="icon-circle bg-primary text-white">
          <i class="fas fa-file-signature"></i> 
        </div>
      </div>

      <div class="row g-4"> <div class="col-md-6">
          <div class="space-y-4">

            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle-sm bg-green-100 text-green-600 me-3">
                <i class="fas fa-building"></i>
              </div>
              <div class="flex-grow-1 ml-2">
                <p class="text-muted small mb-1">Company</p>
                <p class="text-gray-800 font-weight-medium mb-0">{{contract.contract_name }}</p>
              </div>
            </div>

            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle-sm bg-purple-100 text-purple-600 me-3">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="flex-grow-1 ml-2">
                <p class="text-muted small mb-1">Status</p>
                <p class="text-gray-800 font-weight-medium mb-0">{{ contract.status }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="space-y-4">
            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle-sm bg-teal-100 text-teal-600 me-3">
                <i class="fas fa-calendar-plus"></i>
              </div>
              <div class="flex-grow-1 ml-2">
                <p class="text-muted small mb-1">Start Date</p>
                <p class="text-gray-800 font-weight-medium mb-0">{{ formatDate(contract.start_date) || 'No start date' }}</p>
              </div>
            </div>

            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle-sm bg-orange-100 text-orange-600 me-3">
                <i class="fas fa-calendar-times"></i>
              </div>
              <div class="flex-grow-1 ml-2">
                <p class="text-muted small mb-1">End Date</p>
                <p class="text-gray-800 font-weight-medium mb-0">{{formatDate(contract.end_date)|| 'No end date' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles to mimic the Tailwind/PrimeVue aesthetic using CSS/Bootstrap */

/* Mimic rounded-xl shadow-md border */
.card {
  border-radius: 0.75rem; /* ~12px */
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0; /* gray-200 */
}

/* Header icon circle */
.icon-circle {
  width: 2.5rem; /* h-8 */
  height: 2.5rem; /* w-8 */
  border-radius: 0.5rem; /* rounded-lg */
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(to bottom right, #3b82f6, #9333ea); /* blue-500 to purple-600 */
  color: #fff;
  font-size: 0.875rem; /* text-sm */
}

/* Information section small icon circles */
.icon-circle-sm {
  width: 2rem; /* h-8 in Tailwind, slightly smaller for inner icons */
  height: 2rem; /* w-8 */
  border-radius: 0.5rem; /* rounded-lg */
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0; /* flex-shrink-0 */
}

/* Background colors for icon circles */
.bg-blue-100 { background-color: #dbeafe; } /* Example blue-100 */
.text-blue-600 { color: #2563eb; } /* Example blue-600 */
.bg-green-100 { background-color: #dcfce7; } /* Example green-100 */
.text-green-600 { color: #16a34a; } /* Example green-600 */
.bg-purple-100 { background-color: #ede9fe; } /* Example purple-100 */
.text-purple-600 { color: #7c3aed; } /* Example purple-600 */
.bg-teal-100 { background-color: #ccfbf1; } /* Example teal-100 */
.text-teal-600 { color: #0d9488; } /* Example teal-600 */
.bg-orange-100 { background-color: #fff7ed; } /* Example orange-100 */
.text-orange-600 { color: #ea580c; } /* Example orange-600 */

/* Text colors */
.text-gray-800 { color: #1f2937; } /* gray-800 */
.text-gray-500 { color: #6b7280; } /* gray-500 */
.text-muted { color: #6c757d !important; } /* Bootstrap's text-muted */

/* Font weights */
.font-weight-bold { font-weight: 700; }
.font-weight-medium { font-weight: 500; }

/* Spacing and Flex utilities - using Bootstrap's d-flex, mb-*, me-*, pb-*, border-bottom */
/* Tailwind's gap-6 for grid is replaced by Bootstrap's g-4 (or g-sm-4, g-md-4) and row/col */
/* space-y-4 is replaced by mb-3 on child elements within the column */

/* Copy button specific styles */
.copy-button {
  border: none; /* remove default btn outline */
  padding: 0.25rem 0.5rem; /* p-1 equivalent */
  line-height: 1; /* Adjust line-height for icon alignment */
}

.copy-button i {
    font-size: 0.75rem; /* text-xs */
}

.copy-button:hover {
  color: #2563eb; /* blue-500 hover */
  background-color: transparent; /* Keep background transparent */
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%; /* Ensure it truncates within its container */
}

/* Adjust row gap if g-4 isn't enough vertical space */
.row.g-4 > div {
    padding-bottom: 0.5rem; /* Adjust as needed for vertical spacing within columns */
}
</style>