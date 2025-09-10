<script setup>
import { reactive, ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from '../../../../Pages/Appointments/PatientSearch.vue';
import AvailableAppointmentsModality from '..//ModalityAppointment/AvailableAppointmentsModality.vue';
import NextAppointmentDateModality from '../ModalityAppointment/NextAppointmentDateModality.vue';
import AppointmentCalendarModality from '../ModalityAppointment/AppointmentCalendarModality.vue';
import { useToastr } from '@/Components/toster';
import { useAuthStore } from '@/stores/auth';

// PrimeVue Components for modal functionality
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const route = useRoute();
const router = useRouter();
const toastr = useToastr();
const authStore = useAuthStore();

// Renamed to `currentSelectedPatient` to avoid prop naming conflict
// and to clearly distinguish it from the form.patient reactive property.
const currentSelectedPatient = ref(null);

const modalityInfo = ref(null);

const props = defineProps({
    editMode: { type: Boolean, default: false },
    modalityId: { type: Number, required: true },
    appointmentId: { type: Number, default: null },
    visible: {
        type: Boolean,
        default: false
    },
    // This prop `selectedPatient` conflicts with the ref.
    // Let's remove this prop and rely on the `form.patient` reactive property
    // for initial patient data in edit mode, populated by `fetchAppointmentData`.
    // selectedPatient: {
    //     type: Object,
    //     default: null
    // }
});

const selectedPatientString = ref(''); // This will be used for display in PatientSearch input
const emit = defineEmits(['close', 'update-appointment', 'update-status']);

const form = reactive({
    id: null,
    patient: null, // This reactive property will hold the selected patient object
    patient_id: null,
    first_name: '',
    last_name: '',
    patient_Date_Of_Birth: '',
    phone: '',
    modality_id: props.modalityId,
    appointment_date: '',
    appointment_time: '',
    description: '',
    addToWaitlist: false,
    days: '',
    selectionMethod: '',
    duration_days: null, // Default to null, will be set based on selection
});

// Internal ref to control the Dialog's visibility
const dialogVisible = ref(props.visible);

const backendErrors = ref({});

// Computed property for available durations based on selected date (for 'days' slot_type)
const computedAvailableDurations = computed(() => {
    // If not 'days' slot type, or no modality info, return a default range (e.g., 1 day)
    if (modalityInfo.value?.slot_type !== 'days' || !modalityInfo.value?.time_slot_duration) {
        return [1];
    }

    const maxModalityDuration = modalityInfo.value.time_slot_duration;
    const durations = [];

    // If a date is selected, apply specific day-of-week rules
    if (form.appointment_date) {
        const selectedDate = new Date(form.appointment_date);
        const dayOfWeek = selectedDate.getDay(); // Sunday - 0, Monday - 1, ..., Saturday - 6

        if (dayOfWeek === 5) { // Friday
            return []; // No durations on Friday
        }

        if (dayOfWeek === 4) { // Thursday (0-indexed, Thursday is 4)
            for (let i = 1; i <= Math.min(3, maxModalityDuration); i++) {
                durations.push(i);
            }
        } else { // Any other day (Monday, Tuesday, Wednesday, Saturday, Sunday)
            durations.push(1); // Only 1 day allowed
        }
    } else {
        // If no date is selected yet, show all possible durations up to maxModalityDuration,
        // as the day specific rule hasn't been determined.
        // This gives the user options before picking a date.
        for (let i = 1; i <= maxModalityDuration; i++) {
            durations.push(i);
        }
    }
    return durations;
});


const selectedType = ref(''); // Tracks whether the selected appointment is 'canceled' or 'normal'
const appointmentDateErrorMessage = ref(''); // To display specific date-related errors
const durationDaysErrorMessage = ref(''); // To display specific duration-related errors

// Method to select duration
const selectDuration = (duration) => {
    form.duration_days = duration;
    form.days = duration.toString();

    console.log('Duration selected:', duration);
};

// Fetch modality info to get max duration and slot type
const fetchModalityInfo = async () => {
    try {
        const response = await axios.get(`/api/modalities/${props.modalityId}`);
        if (response.data.success) {
            modalityInfo.value = response.data.data;
            console.log('Modality info:', modalityInfo.value);

            if (modalityInfo.value.slot_type === 'days' && !props.editMode && form.duration_days === null) {
                form.duration_days = 1;
                form.days = '1';
            }
        }
    } catch (error) {
        console.error('Error fetching modality info:', error);
    }
};

// Watch for changes in the 'visible' prop from the parent
watch(() => props.visible, (newVal) => {
    dialogVisible.value = newVal;
    if (newVal) {
        fetchModalityInfo();
        // In edit mode, if a selectedPatient prop exists, use it to initialize form.patient
        if (props.editMode) {
            fetchAppointmentData(); // Fetches full appointment data including patient
        } else {
            // For new appointments, ensure patient-related form fields are clear
            resetPatientFields();
        }
    } else if (!newVal) {
        resetForm();
    }
});

onMounted(() => {
    fetchModalityInfo();
    if (props.editMode) {
        fetchAppointmentData();
    }
});

const isRebookingCanceled = ref(false);

const fetchAppointmentData = async () => {
    try {
        console.log('Fetching appointment data for:', props.modalityId, props.appointmentId);
        const response = await axios.get(`/api/modality-appointments/${props.modalityId}/${props.appointmentId}`);

        console.log('Fetched appointment response:', response.data);

        if (response.data.success) {
            const appointment = response.data.data;
            console.log('Appointment data:', appointment);

            // Directly assign the patient object to form.patient
            // It's crucial that the `patient` object from the API response matches the structure `PatientSearch` expects.
            form.patient = {
                id: appointment.patient.id,
                first_name: appointment.patient.first_name || appointment.patient.firstname || appointment.patient.Firstname,
                last_name: appointment.patient.last_name || appointment.patient.lastname || appointment.patient.Lastname,
                dateOfBirth: appointment.patient.date_of_birth || appointment.patient.dateOfBirth,
                phone: appointment.patient.phone
            };

            // Populate other form data
            Object.assign(form, {
                id: appointment.id,
                patient_id: form.patient.id, // Ensure patient_id is set from the patient object
                first_name: form.patient.first_name,
                last_name: form.patient.last_name,
                patient_Date_Of_Birth: form.patient.dateOfBirth,
                phone: form.patient.phone,
                modality_id: appointment.modality_id,
                appointment_date: appointment.appointment_date,
                appointment_time: appointment.appointment_time,
                description: appointment.notes || '',
                duration_days: appointment.duration_days || 1,
            });

            // Update selectedPatient ref for display in the patient-display-card
            currentSelectedPatient.value = form.patient;
            // Update the display string for PatientSearch (though it's disabled in edit mode)
            selectedPatientString.value = `${form.patient.first_name} ${form.patient.last_name} ${form.patient.dateOfBirth} ${form.patient.phone}`;


            console.log('Form populated with:', form);
        }
    } catch (error) {
        console.error('Failed to fetch appointment data:', error);
        toastr.error('Failed to load appointment data: ' + (error.response?.data?.message || error.message));
    }
};

const handlePatientSelect = (patient) => {
    // This function is primarily for new appointments, as PatientSearch is hidden in edit mode.
    // If you need to allow patient changes in edit mode, you'd enable PatientSearch for it.
    if (props.editMode) {
        console.log('Patient selection is disabled in edit mode');
        return;
    }

    if (!patient) {
        resetPatientFields();
        return;
    }

    console.log('Patient selected (raw):', patient); // Log the raw patient object to inspect its structure

    // Directly assign the entire patient object to form.patient
    form.patient = patient;
    form.patient_id = patient.id;
    form.first_name = patient.first_name || patient.firstname || patient.Firstname;
    form.last_name = patient.last_name || patient.lastname || patient.Lastname;
    form.patient_Date_Of_Birth = patient.dateOfBirth || patient.date_of_birth; // Use dateOfBirth from backend
    form.phone = patient.phone;

    // Update the display string for the PatientSearch component's v-model
    selectedPatientString.value = `${form.first_name} ${form.last_name} ${form.patient_Date_Of_Birth} ${form.phone}`;

    console.log('Updated form after patient select:', form);
};

const resetPatientFields = () => {
    form.patient = null;
    form.patient_id = null;
    form.first_name = '';
    form.last_name = '';
    form.patient_Date_Of_Birth = '';
    form.phone = '';
    selectedPatientString.value = '';
};

const handleDaysChange = (days) => {
    form.days = days;
    form.duration_days = parseInt(days);
};

// Update handleDateSelected
const handleDateSelected = (date, type = 'normal') => {
    form.appointment_date = date;
    selectedType.value = type;
    isRebookingCanceled.value = type === 'canceled';

    // Clear client-side date/duration errors when a new date is selected
    appointmentDateErrorMessage.value = '';
    durationDaysErrorMessage.value = '';
    backendErrors.value.appointment_date = null; // Clear backend error for date
    backendErrors.value.duration_days = null; // Clear backend error for duration

    // Client-side validation for Friday and Thursday rules
    if (modalityInfo.value?.slot_type === 'days') {
        const selectedDate = new Date(date);
        const dayOfWeek = selectedDate.getDay(); // 0 for Sunday, 1 for Monday, ..., 5 for Friday, 6 for Saturday

        if (dayOfWeek === 5) { // Friday
            appointmentDateErrorMessage.value = 'Booking on Friday is not allowed for this modality type.';
            form.appointment_date = ''; // Clear selection if Friday
            form.duration_days = null; // Clear duration
            return;
        }

        // If a duration is already selected, re-validate it against the new date's rules
        if (form.duration_days !== null) {
            if (dayOfWeek === 4) { // Thursday
                if (form.duration_days > 3) {
                    durationDaysErrorMessage.value = 'On Thursdays, you can book a maximum of three days.';
                    // Don't clear form.duration_days directly here; let the user adjust or rely on backend.
                }
            } else { // Any other day (Monday, Tuesday, Wednesday, Saturday, Sunday)
                if (form.duration_days !== 1) {
                    durationDaysErrorMessage.value = 'You can only book one day at a time for this modality, except on Thursdays.';
                    // Don't clear form.duration_days directly here.
                }
            }
        } else {
            // If no duration is selected, and it's a non-Friday, default to 1
            if (dayOfWeek !== 5) {
                form.duration_days = 1;
            }
        }
    }

    console.log('Date selected:', date, 'Type:', type);
};

// Update handleTimeSelected
const handleTimeSelected = (time, type = 'normal') => {
    form.appointment_time = time;
    selectedType.value = type;
    isRebookingCanceled.value = type === 'canceled';
    console.log('Time selected:', time, 'Type:', type);
};

const handleSubmit = async (values, { setErrors }) => {
    backendErrors.value = {}; // Clear previous backend errors

    if (!form.patient_id) {
        setErrors({ patient: 'Please select a patient' });
        return;
    }
    if (!form.appointment_date) {
        setErrors({ appointment_date: 'Please select an appointment date' });
        return;
    }

    if (modalityInfo.value?.slot_type === 'days') {
        if (form.duration_days === null || form.duration_days <= 0) {
            setErrors({ duration_days: 'Please select a valid duration in days.' });
            return;
        }

        const selectedDate = new Date(form.appointment_date);
        const dayOfWeek = selectedDate.getDay();

        if (dayOfWeek === 5) { // Friday
            setErrors({ appointment_date: 'Booking on Friday is not allowed for this modality type.' });
            return;
        }

        if (dayOfWeek === 4) { // Thursday
            if (form.duration_days > 3) {
                setErrors({ duration_days: 'On Thursdays, you can book a maximum of three days.' });
                return;
            }
        } else { // Any other day (Mon, Tue, Wed, Sat, Sun)
            if (form.duration_days !== 1) {
                setErrors({ duration_days: 'You can only book one day at a time for this modality type, except on Thursdays.' });
                return;
            }
        }
    }

    try {
        const submitData = {
            patient_id: form.patient_id,
            modality_id: form.modality_id,
            appointment_date: form.appointment_date,
            appointment_time: form.appointment_time,
            description: form.description,
            is_rebook_canceled: isRebookingCanceled.value,
        };

        if (modalityInfo.value?.slot_type === 'days') {
            submitData.duration_days = form.duration_days;
        }

        console.log('Submitting appointment data:', submitData);

        const url = props.editMode
            ? `/api/modality-appointments/${props.appointmentId}`
            : '/api/modality-appointments';
        const method = props.editMode ? 'put' : 'post';

        const response = await axios[method](url, submitData);

        console.log('Appointment submission response:', response.data);

        toastr.success(`${props.editMode ? 'Appointment updated' : 'Appointment created'} successfully`);
        emit('update-appointment');
        handleCancel();
    } catch (error) {
        console.error('Error submitting appointment:', error);

        if (error.response?.data?.errors) {
            setErrors(error.response.data.errors);
            backendErrors.value = error.response.data.errors; // Store all backend errors

            // Update specific error messages for display
            appointmentDateErrorMessage.value = backendErrors.value.appointment_date?.[0] || '';
            durationDaysErrorMessage.value = backendErrors.value.duration_days?.[0] || '';

        } else {
            const errorMessage = error.response?.data?.message || 'An error occurred while processing your request';
            setErrors({ form: errorMessage });
            toastr.error(errorMessage);
        }
    }
};

const handleCancel = () => {
    dialogVisible.value = false;
    emit('close');
};

const resetSelection = () => {
    form.appointment_date = '';
    form.appointment_time = '';
    appointmentDateErrorMessage.value = '';
    durationDaysErrorMessage.value = '';
    backendErrors.value = {}; // Clear backend errors
};

const resetForm = () => {
    Object.assign(form, {
        id: null,
        patient: null, // Reset the patient object
        patient_id: null,
        first_name: '',
        last_name: '',
        patient_Date_Of_Birth: '',
        phone: '',
        modality_id: props.modalityId,
        appointment_date: '',
        appointment_time: '',
        description: '',
        addToWaitlist: false,
        days: '',
        selectionMethod: '',
        duration_days: null, // Reset to null
    });
    selectedPatientString.value = '';
    currentSelectedPatient.value = null; // Also reset the ref used for display
    appointmentDateErrorMessage.value = '';
    durationDaysErrorMessage.value = '';
    backendErrors.value = {};
};

onMounted(() => {
    fetchModalityInfo();
    if (props.editMode) {
        fetchAppointmentData();
    }
});

watch(() => form.selectionMethod, resetSelection);

// --- New `PatientSearch` binding and initial patient setup ---
// This prop in your original code: `selectedPatient: { type: Object, default: null }`
// was causing a naming conflict with the ref `selectedPatient`.
// I've commented out the prop, and we will handle initial patient data via `form.patient`.
// The `PatientSearch` component will now be `v-model`ed directly to `form.patient`.

// Watch for changes in `props.selectedPatient` (if it were still a prop) or `form.patient` to initialize `selectedPatientString` for display.
// Since `PatientSearch` uses `v-model="form.patient"`, `handlePatientSelect` will update `form.patient` and related fields.
// For edit mode, `fetchAppointmentData` will populate `form.patient`.

// We'll rename the ref used for displaying the patient card in edit mode to `currentSelectedPatient`.
// And ensure `PatientSearch` uses `v-model="form.patient"` to bind the full patient object.
// The `PatientSearch` component itself needs to emit the full patient object.

// Template update: Use `currentSelectedPatient` for the display card.
// `PatientSearch` should bind to `form.patient` directly.
// --- End New `PatientSearch` binding ---

const showAllAppointmentsModal = ref(false);
const customDate = ref('');
const customTime = ref('');
const appointmentsForDate = ref([]);

const fetchAppointmentsForDate = async () => {
    if (!customDate.value) {
        appointmentsForDate.value = [];
        return;
    }

    try {
        const response = await axios.get(`/api/appointments-for-date`, {
            params: {
                date: customDate.value,
                modalityId: props.modalityId
            }
        });
        if (response.data.success) {
            appointmentsForDate.value = response.data.data;
        } else {
            toastr.error('Failed to fetch appointments for the selected date');
        }
    } catch (error) {
        console.error('Error fetching appointments for date:', error);
        toastr.error('Error fetching appointments for date: ' + (error.response?.data?.message || error.message));
    }
};

watch(customDate, (newDate) => {
    if (newDate) {
        fetchAppointmentsForDate();
    } else {
        appointmentsForDate.value = [];
    }
});

// `bookCustomTime` needs to be defined if you want to use it
const bookCustomTime = () => {
    if (customDate.value && customTime.value) {
        form.appointment_date = customDate.value;
        form.appointment_time = customTime.value;
        showAllAppointmentsModal.value = false; // Close the modal
        toastr.info(`Selected custom time: ${customTime.value} on ${customDate.value}`);
        // Consider calling handleSubmit here or provide a button outside this modal
        // for final submission after setting these values.
    } else {
        toastr.warning('Please select both a date and a time.');
    }
};

// No longer watching `props.selectedPatient` as it's removed to avoid conflict.
// Instead, `fetchAppointmentData` directly populates `form.patient` and `currentSelectedPatient`.
// And `PatientSearch` for new appointments directly binds to `form.patient`.

// Watch for form.patient changes to update related form fields (removed the patient-specific watch)
// The `handlePatientSelect` function directly updates all `form` fields, making this watch unnecessary.
// If PatientSearch component's v-model emits an object, this should be fine.
</script>

<template>
    <Dialog v-model:visible="dialogVisible" modal :header="props.editMode ? 'Edit Appointment' : 'New Appointment'"
        :style="{ width: '50vw' }" @update:visible="handleCancel">
        <Form @submit="handleSubmit" v-slot="{ errors }">
            <div class="form-group mb-4">
                <label class="form-label">
                    {{ props.editMode ? 'Current Patient (Cannot be changed)' : 'Select Patient' }}
                </label>
                <div v-if="props.editMode && currentSelectedPatient" class="patient-display-card">
                    <div class="patient-info">
                        <div class="patient-name">
                            <i class="pi pi-user me-2"></i>
                            <strong>{{ currentSelectedPatient.first_name }} {{ currentSelectedPatient.last_name }}</strong>
                        </div>
                        <div class="patient-details">
                            <span class="detail-item">
                                <i class="pi pi-calendar me-1"></i>
                                DOB: {{ currentSelectedPatient.dateOfBirth }}
                            </span>
                            <span class="detail-item">
                                <i class="pi pi-phone me-1"></i>
                                {{ currentSelectedPatient.phone }}
                            </span>
                        </div>
                    </div>
                    <div class="lock-indicator">
                        <i class="pi pi-lock"></i>
                        <small>Locked</small>
                    </div>
                </div>

                <div v-if="!props.editMode">
                    <PatientSearch
                        v-model="form.patient"
                        :disabled="props.editMode"
                        :initial-patient="form.patient"
                        class="mb-3"
                        @patientSelected="handlePatientSelect"
                    />
                    <small v-if="errors.patient" class="text-danger">{{ errors.patient }}</small>
                </div>

            </div>

            <div v-if="modalityInfo?.slot_type === 'days'" class="form-group mb-4">
                <label class="form-label">Select Duration (Days)</label>
                <div class="duration-options">
                    <div v-for="day in computedAvailableDurations" :key="day" class="duration-option"
                        :class="{ 'selected': form.duration_days === day }" @click="selectDuration(day)">
                        <div class="duration-number">{{ day }}</div>
                        <div class="duration-label">
                            {{ day === 1 ? 'Whole Day' : `${day} Days` }}
                        </div>
                    </div>
                    <p v-if="form.appointment_date && new Date(form.appointment_date).getDay() === 5"
                        class="text-danger mt-2 w-100">
                        No booking available on Fridays. Please select another date.
                    </p>
                </div>
                <small class="text-muted">
                    <strong>Duration 1:</strong> Books the whole selected day<br>
                    <strong>Duration 2+:</strong> Books multiple consecutive days
                </small>
                <small v-if="errors.duration_days || durationDaysErrorMessage || backendErrors.duration_days"
                    class="text-danger d-block">
                    {{ errors.duration_days ? errors.duration_days[0] : (durationDaysErrorMessage ||
                        backendErrors.duration_days[0]) }}
                </small>
            </div>

            <div class="form-group mb-4">
                <label for="selectionMethod" class="form-label">Appointment Method</label>
                <select id="selectionMethod" v-model="form.selectionMethod" class="form-control">
                    <option value="">{{ props.editMode ? 'Keep Current Selection' : 'Select Available Appointments'
                        }}</option>
                    <option value="available">Available Appointments</option>
                    <option value="next">Next Appointment</option>
                    <option value="calendar">Calendar Selection</option>
                </select>
            </div>

            <div v-if="props.editMode && form.appointment_date" class="form-group mb-4">
                <label class="form-label">Current Appointment Details</label>
                <div class="alert alert-secondary">
                    <div><strong>Date:</strong> {{ form.appointment_date }}</div>
                    <div><strong>Time:</strong> {{ form.appointment_time || 'Full Day' }}</div>
                    <div v-if="form.duration_days"><strong>Duration:</strong> {{ form.duration_days }} day(s)</div>
                    <div v-if="form.description"><strong>Description:</strong> {{ form.description }}</div>
                </div>
            </div>

            <AvailableAppointmentsModality
                v-if="(form.selectionMethod === 'available' || (!form.selectionMethod && !props.editMode))"
                :modalityId="form.modality_id" :durationDays="form.duration_days" @dateSelected="handleDateSelected"
                @timeSelected="handleTimeSelected" />

            <NextAppointmentDateModality v-if="form.selectionMethod === 'next'" :modalityId="form.modality_id"
                :durationDays="form.duration_days" @dateSelected="handleDateSelected"
                @timeSelected="handleTimeSelected" />

            <AppointmentCalendarModality v-if="form.selectionMethod === 'calendar'" :modalityId="form.modality_id"
                :durationDays="form.duration_days" @timeSelected="handleTimeSelected"
                @dateSelected="handleDateSelected" />

            <div v-if="errors.appointment_date || appointmentDateErrorMessage || (backendErrors.appointment_date && backendErrors.appointment_date.length > 0) || errors.duration_days || durationDaysErrorMessage || (backendErrors.duration_days && backendErrors.duration_days.length > 0)"
                class="alert alert-warning mb-3">
                <span v-if="errors.appointment_date">{{ errors.appointment_date[0] }}</span>
                <span v-else-if="appointmentDateErrorMessage">{{ appointmentDateErrorMessage }}</span>
                <span v-else-if="backendErrors.appointment_date && backendErrors.appointment_date.length > 0">{{ backendErrors.appointment_date[0] }}</span>

                <br
                    v-if="(errors.appointment_date || appointmentDateErrorMessage || (backendErrors.appointment_date && backendErrors.appointment_date.length > 0)) && (errors.duration_days || durationDaysErrorMessage || (backendErrors.duration_days && backendErrors.duration_days.length > 0))" />

                <span v-if="errors.duration_days">{{ errors.duration_days[0] }}</span>
                <span v-else-if="durationDaysErrorMessage">{{ durationDaysErrorMessage }}</span>
                <span v-else-if="backendErrors.duration_days && backendErrors.duration_days.length > 0">{{ backendErrors.duration_days[0] }}</span>
            </div>


            <div class="form-group mb-4">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" v-model="form.description" class="form-control" rows="3"
                    placeholder="Enter appointment details..."></textarea>
            </div>

            <div v-if="errors.form && !errors.appointment_date && !errors.duration_days"
                class="alert alert-danger mb-3">
                {{ errors.form }}
            </div>

            <div class="form-group d-flex justify-content-between align-items-center">
                <Button type="submit" :label="props.editMode ? 'Update Appointment' : 'Create Appointment'"
                    icon="pi pi-check" class="p-button-primary p-button-rounded" />
                <Button type="button" label="Cancel" icon="pi pi-times" class="p-button-secondary p-button-rounded"
                    @click="handleCancel" />
            </div>
        </Form>
    </Dialog>

    <Dialog v-model:visible="showAllAppointmentsModal" modal header="Appointments for Selected Date"
        :style="{ width: '40vw' }">
        <div>
            <label>Select Date</label>
            <input type="date" v-model="customDate" class="form-control mb-2" />
            <div v-if="customDate">
                <ul>
                    <li v-for="app in appointmentsForDate" :key="app.id">
                        {{ app.appointment_time }} - {{ app.patient_first_name }} {{ app.patient_last_name }}
                    </li>
                </ul>
                <label>Select Time</label>
                <input type="time" v-model="customTime" class="form-control mb-2" />
                <Button label="Book at this time" class="p-button-success" @click="bookCustomTime" />
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
/* Scoped styles specific to this component */
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

/* Updated button styles to use PrimeVue classes where applicable */
.p-button.p-button-primary {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.p-button.p-button-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

.p-button.p-button-rounded {
    border-radius: 50px;
}

.duration-options {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.duration-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: white;
    min-width: 80px;
}

.duration-option:hover {
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
}

.duration-option.selected {
    border-color: #007bff;
    background-color: #e3f2fd;
    color: #007bff;
}

.duration-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.duration-label {
    font-size: 0.875rem;
    text-align: center;
}

.patient-display-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.patient-info {
    flex: 1;
}

.patient-name {
    font-size: 1.1rem;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.patient-details {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.detail-item {
    color: #6c757d;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
}

.lock-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #ffc107;
}

.lock-indicator .pi-lock {
    font-size: 1.2rem;
    margin-bottom: 0.2rem;
}

.lock-indicator small {
    font-size: 0.75rem;
    color: #6c757d;
}

/* Styles for the new dialog showing all appointments for a selected date */
.p-dialog {
    z-index: 1050;
    /* Ensure it appears above other content */
}

.p-dialog .form-control {
    margin-bottom: 1rem;
}

.p-dialog ul {
    list-style-type: none;
    padding: 0;
}

.p-dialog li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #ddd;
}

.p-dialog li:last-child {
    border-bottom: none;
}
</style>