<script setup>
import { ref, watch, onMounted } from 'vue';
import { useToastr } from '../../../Components/toster'; // Assuming this path is correct for the user's environment
import axios from 'axios'; // Assuming axios is correctly installed and configured

const props = defineProps({

  modelValue: Boolean, // Controls the visibility of the modal
  medication: {
    type: Object,
    default: () => ({ // Default empty medication object for new entries
      designation: '',
      nom_commercial: '',
      type_medicament: '',
      forme: '',
      boite_de: '',
      code_pch: ''
    })
  },
  doctorId: {
    type: [Number, String],
    default: null // Default to null if no doctor ID is provided
  },
  isEdit: Boolean // Flag to determine if the form is for editing or adding
});

const emit = defineEmits(['update:modelValue', 'saved']); // Events for updating modal visibility and signaling successful save
const toastr = useToastr(); // Toastr for notifications

// Reactive form data, initialized with default empty values
const form = ref({
  designation: '',
  nom_commercial: '',
  type_medicament: '',
  forme: '',
  boite_de: '',
  code_pch: ''
});

const loading = ref(false); // Loading state for form submission
const errors = ref({}); // Object to hold validation errors from the API

// Predefined options for dropdowns (kept as per original request)
const typeOptions = [
  'ANAPATHE', 'ANTISEPTIQUE', 'CATHETERISME', 'CHIMIQUE',
  'CHIRURGIE GÉNÉRALE', 'CONSOMMABLE', 'FIBROSCOPIE', 'FROID',
  'INSTRUMENT', 'LABORATOIRE', 'LIGATURE', 'MÉDICAMENT',
  'OSTÉO-SYNTHÈSE', 'PSYCHOTROPE 1', 'PSYCHOTROPE 2', 'RADIOLOGIE',
  'SOLUTÉ GRAND VOLUME', 'STÉRILISATION', 'STUPÉFIANT'
];

const formeOptions = [
  'AMPOULE', 'COMPRIME', 'FLACON', 'GELULE', 'LITRE', 'SACHET', 'TUBE', 'UNITE'
];

/**
 * Resets the form fields and clears any validation errors.
 */
const resetForm = () => {
  form.value = {
    designation: '',
    nom_commercial: '',
    type_medicament: '',
    forme: '',
    boite_de: '',
    code_pch: ''
  };
  errors.value = {}; // Clear errors on reset
};

/**
 * Watches for changes in the 'medication' prop.
 * When 'medication' changes (e.g., when opening for edit),
 * it populates the form fields with the medication data.
 */
watch(() => props.medication, (newVal) => {
  if (newVal) {
    form.value = {
      designation: newVal.designation || '',
      nom_commercial: newVal.nom_commercial || '',
      type_medicament: newVal.type_medicament || '',
      forme: newVal.forme || '',
      boite_de: newVal.boite_de || '',
      code_pch: newVal.code_pch || ''
    };
  }
}, { immediate: true, deep: true }); // immediate: runs handler immediately on component mount; deep: watches nested properties

/**
 * Handles the form submission (either create or update).
 * Sends data to the appropriate API endpoint.
 */
const handleSubmit = async () => {
  try {
    // i want to send the doctorId with form 
    const params = {
      ...form.value,
      doctor_id: props.doctorId // Include doctor ID if provided
    };
    loading.value = true; // Set loading state to true
    errors.value = {}; // Clear previous errors

    // Determine the API URL and HTTP method based on 'isEdit' prop
    const url = props.isEdit
      ? `/api/medications/${props.medication.id}`
      : '/api/medications';
    const method = props.isEdit ? 'put' : 'post';

    // Make the API call using axios
    const response = await axios[method](url, params);

    // Extract the saved medication data from the response
    const savedMedication = response.data.data || response.data;

    // Show success notification
    toastr.success(`Medication ${props.isEdit ? 'updated' : 'created'} successfully`);
    emit('saved', savedMedication); // Emit 'saved' event with the new/updated medication
    emit('update:modelValue', false); // Close the modal
    resetForm(); // Reset the form after successful submission
  } catch (err) {
    // Handle API errors
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors; // Set validation errors
    }
    // Show error notification
    toastr.error(err.response?.data?.message || 'Failed to save medication');
    console.error('Error saving medication:', err); // Log the full error for debugging
  } finally {
    loading.value = false; // Reset loading state
  }
};

/**
 * Closes the modal and resets the form.
 */
const closeModal = () => {
  emit('update:modelValue', false); // Emit to close the modal
  resetForm(); // Reset the form
};
</script>

<template>
  <!-- Modal Container -->
  <div
    class="modal fade"
    :class="{ 'show': modelValue }"
    tabindex="-1"
    :style="{ display: modelValue ? 'block' : 'none' }"
    aria-modal="true"
    role="dialog"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow rounded-lg">
        <!-- Modal Header -->
        <div class="modal-header border-b-0 bg-light-blue-100 rounded-t-lg px-4 py-3 flex justify-between items-center">
          <h5 class="modal-title fw-bold text-gray-800 text-lg flex items-center">
            <i class="fas fa-pills text-blue-500 me-2 text-xl"></i>
            {{ isEdit ? 'Edit' : 'Add New' }} Medication
          </h5>
          <button
            type="button"
            class="btn btn-danger btn-sm rounded-full w-8 h-8 flex items-center justify-center text-white bg-red-500 hover:bg-red-600 transition-colors duration-200"
            @click="closeModal"
            aria-label="Close"
          >
            <i class="fas fa-times text-xs"></i>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-4">
          <form @submit.prevent="handleSubmit">
            <div class="row g-3">
              <!-- Designation -->
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label block text-sm font-medium text-gray-700 mb-1">Designation</label>
                  <input
                    v-model="form.designation"
                    type="text"
                    class="form-control w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'is-invalid border-red-500': errors.designation }"
                    placeholder="Enter medication designation"
                  >
                  <div v-if="errors.designation" class="invalid-feedback text-red-600 text-sm mt-1">{{ errors.designation?.[0] }}</div>
                </div>
              </div>

              <!-- Commercial Name -->
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label block text-sm font-medium text-gray-700 mb-1">Commercial Name</label>
                  <input
                    v-model="form.nom_commercial"
                    type="text"
                    class="form-control w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'is-invalid border-red-500': errors.nom_commercial }"
                    placeholder="Enter commercial name"
                  >
                  <div v-if="errors.nom_commercial" class="invalid-feedback text-red-600 text-sm mt-1">{{ errors.nom_commercial?.[0] }}</div>
                </div>
              </div>

              <!-- Type -->
              <div class="col-md-6"> <!-- Ensures full width -->
                <div class="form-group mb-3">
                  <label class="form-label block text-sm font-medium text-gray-700 mb-1">Type</label>
                  <select
                    v-model="form.type_medicament"
                    class="form-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'is-invalid border-red-500': errors.type_medicament }"
                  >
                    <option disabled value="">Select type</option>
                    <option v-for="type in typeOptions" :key="type" :value="type">
                      {{ type }}
                    </option>
                  </select>
                  <div v-if="errors.type_medicament" class="invalid-feedback text-red-600 text-sm mt-1">{{ errors.type_medicament?.[0] }}</div>
                </div>
              </div>

              <!-- Form -->
              <div class="col-md-6"> <!-- Ensures full width -->
                <div class="form-group mb-3">
                  <label class="form-label block text-sm font-medium text-gray-700 mb-1">Form</label>
                  <select
                    v-model="form.forme"
                    class="form-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'is-invalid border-red-500': errors.forme }"
                  >
                    <option disabled value="">Select form</option>
                    <option v-for="forme in formeOptions" :key="forme" :value="forme">
                      {{ forme }}
                    </option>
                  </select>
                  <div v-if="errors.forme" class="invalid-feedback text-red-600 text-sm mt-1">{{ errors.forme?.[0] }}</div>
                </div>
              </div>

              <!-- Box Size -->
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label block text-sm font-medium text-gray-700 mb-1">Box Size</label>
                  <input
                    v-model="form.boite_de"
                    type="text"
                    class="form-control w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'is-invalid border-red-500': errors.boite_de }"
                    placeholder="Enter box size"
                  >
                  <div v-if="errors.boite_de" class="invalid-feedback text-red-600 text-sm mt-1">{{ errors.boite_de?.[0] }}</div>
                </div>
              </div>

              <!-- PCH Code -->
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label block text-sm font-medium text-gray-700 mb-1">PCH Code</label>
                  <input
                    v-model="form.code_pch"
                    type="text"
                    class="form-control w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'is-invalid border-red-500': errors.code_pch }"
                    placeholder="Enter PCH code"
                  >
                  <div v-if="errors.code_pch" class="invalid-feedback text-red-600 text-sm mt-1">{{ errors.code_pch?.[0] }}</div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer border-t-0 bg-light-blue-100 rounded-b-lg px-4 py-3 flex justify-end gap-2">
          <button
            type="button"
            class="btn btn-secondary bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center"
            @click="closeModal"
          >
            <i class="fas fa-times me-1 text-sm"></i>
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center"
            @click="handleSubmit"
            :disabled="loading"
          >
            <i class="fas me-1 text-sm" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
            {{ loading ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Backdrop -->
  <div
    v-if="modelValue"
    class="modal-backdrop fade show fixed inset-0 bg-black bg-opacity-50 z-40"
    @click="closeModal"
  ></div>
</template>

<style scoped>
/* Scoped styles for the modal */

/* Modal Content Styling */
.modal-content {
  border-radius: 0.75rem; /* Slightly reduced border-radius for a softer look */
  overflow: hidden; /* Ensures rounded corners are applied to content */
}

/* Modal Header Background */
.bg-light-blue-100 {
  background-color: #e3f2fd; /* Light blue background for header and footer */
}

/* Form Group Spacing */
.form-group {
  margin-bottom: 1rem; /* Consistent spacing below form groups */
}

/* Form Label Styling */
.form-label {
  font-weight: 500;
  color: #4a5568; /* Darker text for labels */
  margin-bottom: 0.375rem; /* Reduced margin below label */
}

/* Form Control (Input/Select) Styling */
.form-control,
.form-select {
  border: 1px solid #cbd5e0; /* Lighter border color */
  border-radius: 0.375rem; /* Smaller border-radius for inputs */
  padding: 0.625rem 0.875rem; /* Adjusted padding */
  transition: all 0.2s ease-in-out; /* Smooth transitions */
  width: 100%; /* Full width for inputs */
}

.form-control:focus,
.form-select:focus {
  border-color: #3b82f6; /* Blue border on focus */
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); /* Blue shadow on focus */
  outline: none; /* Remove default outline */
}

/* Invalid feedback for validation errors */
.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #ef4444; /* Red border for invalid fields */
}

.invalid-feedback {
  font-size: 0.875rem; /* Smaller font size for feedback */
  color: #dc2626; /* Darker red for feedback text */
}

/* Button Styling */
.btn {
  padding: 0.625rem 1.25rem;
  font-weight: 500;
  border-radius: 0.375rem; /* Smaller border-radius for buttons */
  transition: all 0.2s ease-in-out; /* Smooth transitions */
  display: inline-flex; /* For icon alignment */
  align-items: center;
  justify-content: center;
}

.btn:hover {
  transform: translateY(-2px); /* Slight lift on hover */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow on hover */
}

.btn-secondary {
  background-color: #e2e8f0; /* Light gray background for secondary button */
  color: #2d3748; /* Dark text for secondary button */
}

.btn-secondary:hover {
  background-color: #cbd5e0;
}

.btn-primary {
  background-color: #3b82f6; /* Blue background for primary button */
  color: #ffffff; /* White text for primary button */
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-danger {
  background-color: #ef4444; /* Red background for danger button */
  color: #ffffff;
}

.btn-danger:hover {
  background-color: #dc2626;
}

/* Icon Spacing */
.btn i.fas {
  margin-right: 0.5rem; /* Space between icon and text */
}

/* Modal Backdrop */
.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.6); /* Slightly darker backdrop */
  z-index: 1040; /* Ensure it's below modal but above content */
}

/* Modal Show Animation */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal.show {
  animation: fadeIn 0.3s ease-out; /* Fade-in animation for the modal */
}

/* Adjustments for icons within buttons */
.btn .fa-times {
  font-size: 0.75rem; /* Smaller font size for the close icon */
}
</style>
