<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from './PatientSearch.vue';
import AvailableAppointments from './AvailableAppointments.vue';
import NextAppointmentDate from './NextAppointmentDate.vue';
import AppointmentCalendar from './AppointmentCalendar.vue';
import { useToastr } from '../../Components/toster';
import { useAuthStore } from '../../stores/auth';

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

const props = defineProps({
  editMode: { type: Boolean, default: false },
  NextAppointment: { type: Boolean, default: false },
  doctorId: { type: Number, default: null },
  specialization_id: { type: Number, default: null },
  isConsulation : {type: Boolean, default: false},
  appointmentId: { type: Number, default: null }
});

const emit = defineEmits(['close']);

const fetchDoctors = async () => {
  if (props.editMode ) {
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
  days: ''
});

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
  console.log('Date selected:', date); // Debug log
};

const handleTimeSelected = (time) => {
  form.appointment_time = time;
  console.log('Time selected:', time); // Debug log
};

const handleSubmit = async (values, { setErrors }) => {
  try {
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
      router.push({ name: 'admin.appointments', params: { doctorId: form.doctor_id } });
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

watch(() => form.selectionMethod, resetSelection);

onMounted(async () => {
  await Promise.all([
    fetchImportanceEnum(),
    fetchDoctors(),
    isWaitListEmpty()
  ]);
  await fetchAppointmentData();
});

// Add this method to format the date for display
const formatDisplayDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Add helper to capitalize names
const capitalize = (str) => {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
};
</script>

<template>
  <Form @submit="handleSubmit.prevent" v-slot="{ errors }">
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

    <!-- Show selected appointment details -->
    <div v-if="form.appointment_date && form.appointment_time" class="alert alert-success mb-4">
      <h6><i class="fas fa-check-circle me-2"></i>Ready to Create Appointment</h6>
      <div class="row">
        <div class="col-md-6">
          <strong>Patient:</strong> {{ form.first_name }} {{ form.last_name }}
        </div>
        <div class="col-md-6">
          <strong>Date:</strong> {{ formatDisplayDate(form.appointment_date) }}
        </div>
        <div class="col-md-6">
          <strong>Time:</strong> {{ form.appointment_time }}
        </div>
        <div class="col-md-6" v-if="form.doctor_id">
          <strong>Doctor ID:</strong> {{ form.doctor_id }}
        </div>
      </div>
    </div>

    <div class="form-group d-flex justify-content-between align-items-center">
      <button 
        type="submit" 
        class="btn btn-primary rounded-pill"
        :disabled="!form.patient_id || !form.appointment_date || !form.appointment_time"
      >
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
  color: #6c757d;
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
</style>