<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from '../../Pages/Appointments/PatientSearch.vue';
import { useToastr } from '../../Components/toster';

const router = useRouter();
const toastr = useToastr();
const autoPrint = ref(false);

const props = defineProps({
  appointmentId: { type: Number, default: null },
  doctorId: { type: Number, default: null },
});
console.log(props.doctorId);


const emit = defineEmits(['close', 'appointmentCreated']);

// Function to get current date in YYYY-MM-DD format
const getCurrentDate = () => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};
// Function to get current time in HH:mm format
const getCurrentTime = () => {
  const now = new Date();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  return `${hours}:${minutes}`;
};
const form = reactive({
  id: null,
  first_name: '',
  patient_id: null,
  last_name: '',
  patient_Date_Of_Birth: '',
  phone: '',
  doctor_id: null,
  appointment_date:getCurrentDate() ,
  appointment_time: getCurrentTime(),
  description: '',
});

const fetchAppointmentData = async () => {
  if (props.appointmentId) {
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
        });
      }
    } catch (error) {
      console.error('Failed to fetch appointment data:', error);
      toastr.error('Failed to load appointment data.');
    }
  } else if (props.doctorId) {
    form.doctor_id = props.doctorId;
  }
};

const handlePatientSelect = (patient) => {
  form.first_name = patient.first_name;
  form.last_name = patient.last_name;
  form.patient_Date_Of_Birth = patient.dateOfBirth;
  form.phone = patient.phone;
  form.patient_id = patient.id;
  form.doctor_id = props.doctorId
};

const handleSubmit = async (values, { setErrors }) => {
  try {
    let url = '/api/appointments';
    let method = 'post';

    if (props.appointmentId) {
      url = `/api/appointments/${props.appointmentId}`;
      method = 'put';
    }

    const response = await axios[method](url, form);
    toastr.success(`Appointment ${props.appointmentId ? 'updated' : 'created'} successfully`);

    if (autoPrint.value && response.data.data) {
      await PrintTicket(response.data.data);
    }
    emit('appointmentCreated'); // Notify parent component that appointment was created/updated
    emit('close'); // Close the modal
  } catch (error) {
    console.error(`Error ${props.appointmentId ? 'updating' : 'creating'} appointment:`, error);
    setErrors({ form: 'An error occurred while processing your request' });
    toastr.error(`Failed to ${props.appointmentId ? 'update' : 'create'} appointment.`);
  }
};

const handleCancel = () => {
  emit('close');
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

onMounted(async () => {
  await fetchAppointmentData();
});
</script>

<template>
  <div class="modal-overlay" @click.self="handleCancel">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ props.appointmentId ? 'Edit Appointment' : 'Create New Appointment' }}</h5>
        <button type="button" class="close-button" @click="handleCancel">&times;</button>
      </div>
      <Form @submit="handleSubmit" v-slot="{ errors }">
        <div class="modal-body">
          <PatientSearch
            v-model="form.first_name"
            :patientId="form.patient_id"
            @patientSelected="handlePatientSelect"
          />

          <div class="form-group mb-4">
            <label for="appointment_date" class="form-label">Appointment Date</label>
            <input
              type="date"
              id="appointment_date"
              v-model="form.appointment_date"
              class="form-control"
              required
            />
            <span class="text-sm invalid-feedback">{{ errors.appointment_date }}</span>
          </div>

          <div class="form-group mb-4">
            <label for="appointment_time" class="form-label">Appointment Time</label>
            <input
              type="time"
              id="appointment_time"
              v-model="form.appointment_time"
              class="form-control"
              required
            />
            <span class="text-sm invalid-feedback">{{ errors.appointment_time }}</span>
          </div>

          <div class="form-group mb-4">
            <label for="description" class="form-label">Description (Optional)</label>
            <textarea
              id="description"
              v-model="form.description"
              class="form-control"
              rows="3"
              placeholder="Enter appointment details..."
            ></textarea>
          </div>

          <div class="form-group mb-4 form-check">
            <input type="checkbox" id="autoPrint" v-model="autoPrint" class="form-check-input" />
            <label for="autoPrint" class="form-check-label">
              Print ticket automatically after {{ props.appointmentId ? 'updating' : 'creating' }} appointment
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill" @click="handleCancel">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill">
            {{ props.appointmentId ? 'Update Appointment' : 'Create Appointment' }}
          </button>
        </div>
      </Form>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 100000000;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 8px;
  width: 90%;
  max-width: 1000px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  position: relative;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding-bottom: 15px;
  margin-bottom: 20px;
}

.modal-title {
  margin: 0;
  font-size: 1.5rem;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.8rem;
  cursor: pointer;
  color: #888;
}

.modal-body {
  padding-bottom: 20px;
}

.modal-footer {
  border-top: 1px solid #eee;
  padding-top: 15px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

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
  margin-right: 8px; /* Adjusted for better spacing with label */
}

.form-check-label {
  display: inline-block; /* Ensure label aligns with checkbox */
  vertical-align: middle;
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

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875rem;
  display: block; /* Ensure feedback is on its own line */
  margin-top: 0.25rem;
}

.rounded-pill {
  border-radius: 50px;
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