<script setup>
import { reactive, ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from './PatientSearch.vue';
import AvailableAppointments from './AvailableAppointments.vue';
import NextAppointmentDate from './NextAppointmentDate.vue';
import AppointmentCalendar from './AppointmentCalendar.vue';
import { useToastr } from '../../Components/toster';
import { useAuthStore } from '../../stores/auth';

// PrimeVue MultiSelect
import MultiSelect from 'primevue/multiselect';

const route = useRoute();
const router = useRouter();
const nextAppointmentDate = ref('');
const searchQuery = ref('');
const toastr = useToastr();
const isEmpty = ref(false);
const importanceLevels = ref([]);
const authStore = useAuthStore();
const doctors = ref([]);
const autoPrint = ref(false);
const specializationId = ref(route.params.specializationId ? parseInt(route.params.specializationId) : null);
const doctorId = ref(route.params.id ? parseInt(route.params.doctorId) : null);

// Prestation related refs
const allPrestations = ref([]); // Store ALL prestations (unfiltered)
const consultationPrestations = ref([]); // Store only consultation prestations for dropdown
const prestations = ref([]); // Prestations for the dropdown (consultation filtered)
const selectedPrestations = ref([]);
const dependencyPrestations = ref([]); // Dependencies to show as checkboxes
const selectedDependencies = ref([]); // User-selected dependencies
const patientInstructions = ref(''); // Combined instructions from selected prestations
const instructionsExpanded = ref(false);
const INSTRUCTIONS_PREVIEW_LENGTH = 200; // characters to show in collapsed state

const truncatedInstructions = computed(() => {
  if (!patientInstructions.value) return '';
  if (instructionsExpanded.value) return patientInstructions.value;
  return patientInstructions.value.length > INSTRUCTIONS_PREVIEW_LENGTH
    ? patientInstructions.value.slice(0, INSTRUCTIONS_PREVIEW_LENGTH) + '...'
    : patientInstructions.value;
});

const props = defineProps({
  editMode: { type: Boolean, default: false },
  NextAppointment: { type: Boolean, default: false },
  doctorId: { type: Number, default: null },
  specialization_id: { type: Number, default: null },
  isConsulation: { type: Boolean, default: false },
  appointmentId: { type: Number, default: null }
});

console.log(props);

const emit = defineEmits(['close']);

const fetchDoctors = async () => {
  if (props.editMode) {
    try {
      const response = await axios.get(`/api/doctors/specializations/${props.specialization_id}`);
      doctors.value = response.data.data;
    } catch (error) {
      console.error('Failed to fetch doctors:', error);
    }
  }
};

const form = reactive({
  id: null,
  first_name: '',
  patient_id: null,
  last_name: '',
  patient_Date_Of_Birth: '',
  phone: '',
  doctor_id: null,
  appointment_date: '',
  appointment_time: '',
  description: '',
  addToWaitlist: false,
  importance: 1,
  status: {},
  selectionMethod: '',
  days: '',
  prestation_id: null,
  prestations: [],
});

// Fetch prestations for specialization - filters consultations for dropdown, keeps all for dependencies
const fetchPrestationsForSpecialization = async (specialization) => {
  try {
    const res = await axios.get(`/api/reception/prestations/by-specialization/${specialization}`);
    const fetchedPrestations = (res.data && (res.data.data ?? res.data)) || [];
    
    // Store ALL prestations (for dependencies)
    allPrestations.value = fetchedPrestations;
    
    // Filter consultation prestations for the dropdown: only include items with 'consultation' in the name
    consultationPrestations.value = fetchedPrestations.filter(prestation => {
      const prestationName = (prestation.name || '').toLowerCase();
      return prestationName.includes('consultation');
    });
    
    // Use filtered consultations for the dropdown
    prestations.value = consultationPrestations.value;
    
    console.log(`Loaded ${consultationPrestations.value.length} consultation prestations from ${fetchedPrestations.length} total`);
    
    // Sort consultations
    prestations.value.sort((a, b) => {
      const aExact = a.name?.toLowerCase() === 'consultation';
      const bExact = b.name?.toLowerCase() === 'consultation';
      
      if (aExact && !bExact) return -1;
      if (!aExact && bExact) return 1;
      
      return (a.name || '').localeCompare(b.name || '');
    });
    
  } catch (err) {
    console.error('Failed to load prestations:', err);
    allPrestations.value = [];
    consultationPrestations.value = [];
    prestations.value = [];
  }
};

// Fetch dependencies and patient instructions for selected prestations
const fetchDependenciesAndInstructions = async () => {
  if (!selectedPrestations.value || selectedPrestations.value.length === 0) {
    dependencyPrestations.value = [];
    selectedDependencies.value = [];
    patientInstructions.value = '';
    return;
  }

  try {
    const allDependencyIds = new Set();
    let combinedInstructions = [];

    for (const prestationId of selectedPrestations.value) {
      // Find prestation in consultation list first, then in all prestations
      const prestation = consultationPrestations.value.find(p => p.id === prestationId) || 
                        allPrestations.value.find(p => p.id === prestationId);
      
      if (prestation) {
        // Add patient instructions
        if (prestation.patient_instructions) {
          combinedInstructions.push(`${prestation.name}: ${prestation.patient_instructions}`);
        }

        // Add dependencies from required_prestations_info
        if (prestation.required_prestations_info && Array.isArray(prestation.required_prestations_info)) {
          prestation.required_prestations_info.forEach(depId => {
            if (depId && !selectedPrestations.value.includes(depId)) {
              allDependencyIds.add(depId);
            }
          });
        }
      }
    }

    // Update patient instructions
    patientInstructions.value = combinedInstructions.join('\n\n');
    
    if (patientInstructions.value) {
      form.description = patientInstructions.value;
    }

    // Fetch ALL dependency prestations (not filtered)
    if (allDependencyIds.size > 0) {
      const dependencyIds = Array.from(allDependencyIds);
      
      // Get dependencies from ALL prestations (not just consultations)
      dependencyPrestations.value = allPrestations.value.filter(p => 
        dependencyIds.includes(p.id)
      );
      
      // Auto-select all dependencies by default
      selectedDependencies.value = dependencyIds;
      
      console.log('Dependencies found (auto-selected):', dependencyPrestations.value);
      console.log('Auto-selected dependency IDs:', selectedDependencies.value);
    } else {
      dependencyPrestations.value = [];
      selectedDependencies.value = [];
    }
  } catch (err) {
    console.error('Failed to fetch dependencies:', err);
  }
};

// Watch selected prestations and update form accordingly
watch(selectedPrestations, async (val) => {
  form.prestations = Array.isArray(val) ? val : (val ? [val] : []);
  form.prestation_id = (Array.isArray(val) && val.length) ? val[0] : null;
  console.log('Selected prestation ID:', form.prestation_id);
  console.log('Selected prestations array:', form.prestations);

  // Fetch dependencies and patient instructions for selected prestations
  await fetchDependenciesAndInstructions();
});

// Auto-select when only one consultation prestation is available
watch(consultationPrestations, async (val) => {
  try {
    if (Array.isArray(val) && val.length === 1) {
      const onlyId = val[0].id;
      if (!selectedPrestations.value || selectedPrestations.value.length !== 1 || selectedPrestations.value[0] !== onlyId) {
        selectedPrestations.value = [onlyId];
      }

      // ensure dependencies/instructions are fetched for the auto-selected prestation
      await fetchDependenciesAndInstructions();
    }
  } catch (err) {
    console.error('Error auto-selecting single consultation prestation:', err);
  }
}, { immediate: true });

const fetchAppointmentData = async () => {
  if (props.editMode && props.appointmentId) {
    try {
      const response = await axios.get(`/api/appointments/${props.doctorId}/${props.appointmentId}`);
      if (response.data.success) {
        const appointment = response.data.data;
        Object.assign(form, {
          id: appointment.id,
          first_name: appointment.first_name,
          patient_id: appointment.patient_id,
          last_name: appointment.last_name,
          patient_Date_Of_Birth: appointment.patient_Date_Of_Birth,
          phone: appointment.phone,
          doctor_id: appointment.doctor_id || props.doctorId,
          appointment_date: appointment.appointment_date,
          appointment_time: appointment.appointment_time,
          description: appointment.description,
          addToWaitlist: appointment.addToWaitlist,
          status: appointment.status
        });

        searchQuery.value = `${appointment.first_name} ${appointment.last_name} ${appointment.patient_Date_Of_Birth} ${appointment.phone}`;
      }
    } catch (error) {
      console.error('Failed to fetch appointment data:', error);
    }
  } else if (!props.editMode && props.doctorId) {
    form.doctor_id = props.doctorId;
  }
};

const fetchImportanceEnum = async () => {
  const response = await axios.get('/api/importance-enum');
  importanceLevels.value = response.data;
};

const isWaitListEmpty = async () => {
  const response = await axios.get('/api/waitlist/empty');
  isEmpty.value = response.data.data;
};

const handlePatientSelect = (patient) => {
  form.first_name = patient.first_name;
  form.last_name = patient.last_name;
  form.patient_Date_Of_Birth = patient.dateOfBirth;
  form.phone = patient.phone;
  form.patient_id = patient.id;
};

const getPatientFullName = (patient) => {
  if (!patient || typeof patient !== 'object') {
    return 'N/A';
  }

  const { patient_last_name = '', patient_first_name = '' } = patient;
  const fullName = [patient_first_name, patient_last_name]
    .filter(Boolean)
    .join(' ');

  return fullName ? capitalize(fullName) : 'N/A';
};

const handleDaysChange = (days) => {
  form.days = days;
};

const handleDateSelected = (date) => {
  form.appointment_date = date;
  nextAppointmentDate.value = date;
};

const handleTimeSelected = (time) => {
  form.appointment_time = time;
  console.log('Time selected:', time);

  // After time is chosen -> load prestations for the current specialization
  const currentSpecializationId = props.specialization_id || specializationId.value;
  if (currentSpecializationId) {
    fetchPrestationsForSpecialization(currentSpecializationId);
  }
};

const handleSubmit = async (values, { setErrors }) => {
  try {
    // Combine selected prestations with selected dependencies
    const allPrestations = [...(form.prestations || []), ...(selectedDependencies.value || [])];
    const uniquePrestations = [...new Set(allPrestations)];
    
    // Update form with combined prestations
    form.prestations = uniquePrestations;
    if (uniquePrestations.length > 0 && !form.prestation_id) {
      form.prestation_id = uniquePrestations[0];
    }

    console.log('Submitting appointment payload:', {
      ...form,
      prestations: form.prestations,
      prestation_id: form.prestation_id,
      dependencies: selectedDependencies.value
    });

    let url = '/api/appointments';
    let method = props.editMode ? 'put' : 'post';

    if (props.editMode) {
      if (props.NextAppointment) {
        url = `/api/appointment/nextappointment/${props.appointmentId}`;
        method = 'post';
      } else {
        url = `/api/appointments/${props.appointmentId}`;
      }
    }

    const response = await axios[method](url, form);
    toastr.success(`${props.editMode ? 'Appointment updated' : 'Appointment created'} successfully`);

    if (autoPrint.value && response.data.data) {
      await PrintTicket(response.data.data);
    }

    if (props.isConsulation) {
      router.push({ name: 'admin.consultations.consulation' });
    } else if (props.NextAppointment) {
      emit('close');
    } else {
      router.push({ name: 'admin.appointments.list', params: { id: form.doctor_id, specializationId: props.specialization_id || specializationId.value } });
    }
  } catch (error) {
    console.error(`${props.editMode ? 'Error updating appointment:' : 'Error creating appointment:'}`, error);
    setErrors({ form: 'An error occurred while processing your request' });
  }
};

const handleCancel = () => {
  emit('close');
  if (props.isConsulation) {
    router.push({ name: 'admin.consultations.consulation' });
  }
};

const PrintTicket = async () => {
  try {
    const ticketData = {
      patient_name: `${form.first_name} ${form.last_name}`,
      patient_first_name: form.first_name,
      patient_last_name: form.last_name,
      doctor_id: form.doctor_id || 'N/A',
      appointment_date: form.appointment_date,
      appointment_time: form.appointment_time,
      description: form.description || 'N/A'
    };

    const response = await axios.post('/api/appointments/print-ticket', ticketData, {
      responseType: 'blob'
    });
    
    const pdfUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const printWindow = window.open(pdfUrl);
    
    printWindow.onload = function() {
      printWindow.print();
    };
  } catch (error) {
    console.error('Error printing ticket:', error);
    toastr.error('Failed to print ticket');
  }
};

const resetSelection = () => {
  if (form.selectionMethod === 'days') {
    form.appointment_date = '';
    nextAppointmentDate.value = '';
  } else {
    form.days = '';
  }
};

// Helper method to highlight consultation keyword in dropdown
const highlightConsultation = (text) => {
  if (!text) return '';
  
  // Highlight consultation keywords
  const keywords = ['consultation', 'consult', 'consul'];
  let highlighted = text;
  
  keywords.forEach(keyword => {
    const regex = new RegExp(`(${keyword})`, 'gi');
    highlighted = highlighted.replace(regex, '<mark>$1</mark>');
  });
  
  return highlighted;
};

// Select/Deselect all dependencies
const selectAllDependencies = () => {
  selectedDependencies.value = dependencyPrestations.value.map(d => d.id);
};

const clearAllDependencies = () => {
  selectedDependencies.value = [];
};

watch(() => form.selectionMethod, resetSelection);

onMounted(async () => {
  await Promise.all([
    fetchImportanceEnum(),
    fetchDoctors(),
    isWaitListEmpty()
  ]);
  await fetchAppointmentData();
});
</script>

<template>
  <Form @submit="handleSubmit" v-slot="{ errors }">
    <PatientSearch 
      v-model="searchQuery" 
      :patientId="form.patient_id" 
      @patientSelected="handlePatientSelect" 
    />
    
    <div class="mb-3" v-if="props.editMode && authStore.user.role !== 'doctor'">
      <label for="doctor_id" class="form-label">Select Doctor</label>
      <select id="doctor_id" v-model="form.doctor_id" class="form-control" required>
        <option value="" disabled>Select a doctor</option>
        <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
          {{ doctor.name }}
        </option>
      </select>
      <span class="text-sm invalid-feedback">{{ errors.doctor_id }}</span>
    </div>

    <AvailableAppointments
      v-if="!form.selectionMethod"
      :waitlist="false"
      :isEmpty="isEmpty"
      :doctorId="form.doctor_id || props.doctorId"
      @dateSelected="handleDateSelected"
      @timeSelected="handleTimeSelected"
    />

    <!-- Consultation Prestations MultiSelect - shows only consultation prestations -->
    <div v-if="form.appointment_time" class="form-group mb-4">
      <label class="form-label">
        Consultation Prestations
        <span class="badge bg-info text-white ms-2">{{ consultationPrestations.length }} available</span>
      </label>
      <MultiSelect
        v-model="selectedPrestations"
        :options="consultationPrestations"
        option-label="name"
        option-value="id"
        placeholder="Select consultation prestations"
        filter
        show-clear
        class="w-full"
        :maxSelectedLabels="3"
        selectedItemsLabel="{0} consultations selected"
      >
        <!-- Custom option template to highlight consultation keyword -->
        <template #option="slotProps">
          <div class="multiselect-option">
            <span v-html="highlightConsultation(slotProps.option.name)"></span>
            <small class="text-muted d-block">{{ slotProps.option.service_name }}</small>
          </div>
        </template>
      </MultiSelect>
      <small class="form-text text-muted">
        Only showing prestations with "consultation" in their name. Dependencies will be shown separately.
      </small>
    </div>

    <!-- Dependencies Section - shows ALL dependencies, auto-selected -->
    <div v-if="dependencyPrestations.length > 0" class="form-group mb-4 dependencies-section">
      <label class="form-label">
        Required Dependencies 
        <span class="badge bg-success text-white ms-2">
          {{ selectedDependencies.length }}/{{ dependencyPrestations.length }} selected
        </span>
      </label>
      <div class="dependencies-info alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        These dependencies are auto-selected but can be deselected if not needed.
      </div>

      <div class="dependencies-list">
        <div 
          v-for="dep in dependencyPrestations" 
          :key="dep.id" 
          class="dependency-item"
          :class="{ 'selected': selectedDependencies.includes(dep.id) }"
        >
          <label class="checkbox-label">
            <input 
              type="checkbox" 
              :value="dep.id"
              v-model="selectedDependencies"
              class="form-check-input"
            />
            <span class="dependency-details">
              <span class="dependency-name">{{ dep.name }}</span>
              <span class="dependency-meta">
                <small class="text-muted ml-4">{{ dep.service_name }}</small>
              </span>
            </span>
          </label>
        </div>
      </div>
      
      <div class="mt-2 d-flex gap-2">
        <button 
          type="button" 
          class="btn btn-sm btn-outline-primary"
          @click="selectAllDependencies"
        >
          Select All
        </button>
        <button 
          type="button" 
          class="btn btn-sm btn-outline-secondary"
          @click="clearAllDependencies"
        >
          Clear All
        </button>
      </div>
    </div>

    <!-- Patient Instructions Display -->
    <div v-if="patientInstructions" class="form-group mb-4 patient-instructions">
      <label class="form-label">Patient Instructions</label>
      <div class="instructions-box">
        <pre>{{ truncatedInstructions }}</pre>
        <div v-if="patientInstructions.length > INSTRUCTIONS_PREVIEW_LENGTH" class="mt-2">
          <button type="button" class="btn btn-sm btn-link" @click="instructionsExpanded = !instructionsExpanded">
            {{ instructionsExpanded ? 'Show less' : 'Show more' }}
          </button>
        </div>
      </div>
    </div>

    <div class="form-group mb-4">
      <label for="selectionMethod" class="form-label">Select Appointment Method</label>
      <select id="selectionMethod" v-model="form.selectionMethod" class="form-control">
        <option value="">Select Available Appointments</option>
        <option value="days">By Days</option>
        <option value="calendar">By Calendar</option>
      </select>
    </div>

    <NextAppointmentDate
      v-if="form.selectionMethod === 'days'"
      :doctorId="form.doctor_id || props.doctorId"
      :initialDays="form.days"
      @update:days="handleDaysChange"
      @dateSelected="handleDateSelected"
      @timeSelected="handleTimeSelected"
    />

    <AppointmentCalendar
      v-if="form.selectionMethod === 'calendar'"
      :doctorId="form.doctor_id || props.doctorId"
      @timeSelected="handleTimeSelected"
      @dateSelected="handleDateSelected"
    />

    <div class="form-group mb-4">
      <label for="addToWaitlist" class="form-label">Add to Waitlist</label>
      <input 
        type="checkbox" 
        id="addToWaitlist" 
        v-model="form.addToWaitlist" 
        class="form-check-input" 
      />
    </div>

    <div class="form-group mb-4">
      <label for="description" class="form-label">Description</label>
      <textarea
        id="description"
        v-model="form.description"
        class="form-control"
        rows="3"
        placeholder="Enter appointment details..."
      ></textarea>
    </div>

    <div class="form-group mb-4">
      <label for="autoPrint" class="form-label">
        <input type="checkbox" id="autoPrint" v-model="autoPrint" />
        Print ticket automatically after creating appointment
      </label>
    </div>

    <div class="form-group d-flex justify-content-between align-items-center">
      <button type="submit" class="btn btn-primary rounded-pill">
        {{ props.NextAppointment ? 'Create Appointment' : props.editMode ? 'Update Appointment' : 'Create Appointment' }}
      </button>

      <button
        type="button"
        class="btn btn-secondary rounded-pill"
        @click="handleCancel"
        v-if="isConsulation"
      >
        No Next Appointment
      </button>
    </div>
  </Form>
</template>

<style scoped>
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.form-check-input {
  margin-left: 10px;
}

.btn {
  padding: 0.8rem 1.5rem;
  font-size: 16px;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.text-muted {
  color: #6c757d !important;
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875rem;
}

.rounded-pill {
  border-radius: 50px;
}

.no-slots {
  text-align: center;
  margin: 2rem 0;
}

.no-slots button {
  width: 200px;
}

.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.align-items-center {
  align-items: center;
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.text-sm {
  font-size: 0.875rem;
}

/* PrimeVue MultiSelect styling */
:deep(.p-multiselect) {
  width: 100%;
  border: 1px solid #ddd;
  border-radius: 4px;
}

:deep(.p-multiselect .p-multiselect-label) {
  padding: 0.5rem;
}

.w-full {
  width: 100%;
}

/* Enhanced Dependencies Styling */
.dependencies-section {
  background: linear-gradient(to bottom, #f8f9fa, #ffffff);
  padding: 1.25rem;
  border-radius: 10px;
  border: 1px solid #dee2e6;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.dependencies-info {
  padding: 0.75rem;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.dependencies-info i {
  color: #0dcaf0;
}

.dependencies-list {
  max-height: 300px;
  overflow-y: auto;
  padding-right: 5px;
}

.dependency-item {
  padding: 0.75rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #dee2e6;
  margin-bottom: 0.5rem;
  transition: all 0.2s ease;
}

.dependency-item:hover {
  border-color: #0d6efd;
  box-shadow: 0 2px 4px rgba(13,110,253,0.1);
}

.dependency-item.selected {
  background: #e7f3ff;
  border-color: #0d6efd;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  cursor: pointer;
  margin: 0;
}

.dependency-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.dependency-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dependency-name {
  font-weight: 500;
  font-size: 0.95rem;
  color: #212529;
}

.dependency-price {
  color: #28a745;
  font-weight: 600;
  font-size: 0.9rem;
  margin-left: auto;
}

/* Patient Instructions Styling */
.patient-instructions {
  background-color: #fff3cd;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #ffc107;
}

.instructions-box {
  background-color: white;
  padding: 1rem;
  border-radius: 4px;
  border: 1px solid #dee2e6;
  max-height: 200px;
  overflow-y: auto;
}

.instructions-box pre {
  white-space: pre-wrap;
  word-wrap: break-word;
  margin: 0;
  font-family: inherit;
  font-size: 0.9rem;
  color: #495057;
}

/* MultiSelect option styling */
.multiselect-option {
  padding: 0.25rem 0;
}

.multiselect-option mark {
  background-color: #fff3cd;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 600;
}

/* Badge styling */
.badge {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 4px;
  display: inline-block;
}

.bg-info {
  background-color: #0dcaf0 !important;
}

.bg-success {
  background-color: #198754 !important;
}

.text-white {
  color: white !important;
}

.ms-2 {
  margin-left: 0.5rem;
}

.me-2 {
  margin-right: 0.5rem;
}

.gap-2 {
  gap: 0.5rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.btn-outline-primary {
  color: #0d6efd;
  border: 1px solid #0d6efd;
  background: white;
}

.btn-outline-primary:hover {
  background: #0d6efd;
  color: white;
}

.btn-outline-secondary {
  color: #6c757d;
  border: 1px solid #6c757d;
  background: white;
}

.btn-outline-secondary:hover {
  background: #6c757d;
  color: white;
}

.d-block {
  display: block;
}

.alert {
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}

.alert-info {
  color: #055160;
  background-color: #cff4fc;
  border-color: #b6effb;
}
</style>
