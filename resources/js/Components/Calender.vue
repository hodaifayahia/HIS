
<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useAuthStoreDoctor } from '../stores/AuthDoctor.js';

const toast = useToast();

const authStore = useAuthStoreDoctor();

const appointments = ref([]);
const loading = ref(false);
const error = ref(null);
const currentFilter = ref(null);
const pagination = ref(null);
const currentDate = ref(new Date());
const selectedDate = ref(new Date());
const doctors = ref([]);
const displayMode = ref('calendar'); // 'calendar' or 'table'
const selectedAppointments = ref([]);
const showTransferModal = ref(false);
const transferData = ref({
  newDoctorId: '',
  newDate: '',
  availableDoctors: []
});
const transferLoading = ref(false);

// Use computed properties to get doctor data from the store
const doctor_id = computed(() => authStore.doctorData.id);
const role = computed(() => authStore.doctorData.id ? 'doctor' : null);

const filters = ref({
  patientName: '',
  phone: '',
  dateOfBirth: '',
  time: '',
  status: '',
  doctorName: '',
});

const initializeDoctor = async () => {
  try {
    await authStore.getDoctor();
    
    // If doctor data is loaded, fetch appointments
    if (authStore.doctorData.id) {
      await getAppointments();
    }
  } catch (err) {
    console.error('Error fetching doctor data:', err);
  }
};


const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const getAppointments = async (status = null, filter = null, date = null) => {
  try {
    loading.value = true;
    error.value = null;

    currentFilter.value = status || 'ALL';

    const params = {
      status: status === 'ALL' ? null : status,
      filter: filter,
      date: date || filters.value.date,
      doctorName: filters.value.doctorName,
      // Add doctor_id to params if it exists
      ...(doctor_id.value && { doctor_id: doctor_id.value })

    };


    const response = await axios.get(`/api/appointments`, { params });
    console.log(response.data);
    
    pagination.value = response.data.meta;

    if (response.data.success) {
      appointments.value = response.data.data;
    } else {
      throw new Error(response.data.message);
    }
  } catch (err) {
    console.error('Error fetching appointments:', err);
    error.value = err.message || 'Failed to load appointments';
    appointments.value = [];
  } finally {
    loading.value = false;
  }
};
const fetchDoctorsworkingDates = async (month) => {
  try {
    loading.value = true;
    error.value = null;

    // Make the API call for working dates
    const response = await axios.get('/api/doctors/WorkingDates', {
      params: {
        month,
        doctorId: doctor_id.value
      }
    });

    if (response.data.data) {
      const colors = [
    '#CC0000',  // Vibrant Red
    '#FF9933',  // Warm Orange
    '#CCCC00',  // Bright Yellow
    '#99FF33',  // Electric Lime
    '#00CC66',  // Cool Green
    '#33FF99',  // Minty Fresh
    '#00CCCC',  // Cyan Splash
    '#33CCFF',  // Sky Blue
    '#0000CC',  // Deep Navy
    '#6666FF',  // Soft Periwinkle
    '#CC00CC',  // Fuchsia Magenta
    '#FF66FF',  // Cotton Candy Pink
    '#CC66CC',  // Lavender Mist
    '#FF3399',  // Hot Pink
    '#CC3366'   // Dusty Rose
];

      if (doctor_id.value) {
        // If doctor_id exists, we expect a single doctor's data
        const doctorData = Array.isArray(response.data.data) ? response.data.data[0] : response.data.data;
        doctors.value = [{
          ...doctorData,
          working_dates: doctorData.working_dates.map(date => formatLocalDate(new Date(date))),
          // The excludedDates are already in the response
          excludedDates: doctorData.excludedDates || [],
          color: colors[0]
        }];
      } else {
        // If no doctor_id, handle multiple doctors as before
        doctors.value = response.data.data.map((doctor, index) => ({
          ...doctor,
          working_dates: doctor.working_dates.map(date => formatLocalDate(new Date(date))),
          // The excludedDates are already in the response
          excludedDates: doctor.excludedDates || [],
          color: colors[index % colors.length]
        }));
      }
    } else {
      throw new Error('Failed to fetch doctors');
    }
    
    // Also fetch appointments for the same month to ensure we have complete data
    await getAppointments();
    
  } catch (err) {
    console.error('Error fetching doctors:', err);
    error.value = err.message || 'Failed to load doctors';
    doctors.value = [];
  } finally {
    loading.value = false;
  }
};
// Update the getDoctorsWorkingOnDate function to handle excluded dates
const getDoctorsWorkingOnDate = (date) => {
  const dateStr = formatLocalDate(date);
  const result = [];

  doctors.value.forEach(doctor => {
    // Check if doctor is working on this date
    const isWorking = doctor.working_dates.some(workingDate => {
      return formatLocalDate(new Date(workingDate)) === dateStr;
    });

    if (isWorking) {
      result.push({
        name: doctor.name,
        color: doctor.color,
      });
    }
    
    // Check for excluded dates (holidays)
    if (doctor.excludedDates && doctor.excludedDates.length > 0) {
      const isHoliday = doctor.excludedDates.some(exclusion => {
        // Check if date falls within an excluded range
        const startDate = new Date(exclusion.start_date);
        const endDate = exclusion.end_date ? new Date(exclusion.end_date) : startDate;
        const currentDate = new Date(dateStr);
        
        // Normalize to compare just the dates
        const normalizedStart = new Date(startDate.setHours(0, 0, 0, 0));
        const normalizedEnd = new Date(endDate.setHours(0, 0, 0, 0));
        const normalizedCurrent = new Date(currentDate.setHours(0, 0, 0, 0));
        
        // Check if date is within range and exclusionType is complete
        return normalizedCurrent >= normalizedStart && 
               normalizedCurrent <= normalizedEnd && 
               exclusion.exclusionType === 'complete';
      });
      
      if (isHoliday) {
        result.push({
          name: `${doctor.name}: Holiday`,
          color: '#FF0000', // Red color for holidays
          isHoliday: true
        });
      }
    }
  });

  return result;
};
const setDisplayMode = (mode) => {
  displayMode.value = mode;
};
const hasAppointments = (date) => {
  const dateStr = formatLocalDate(date);
  return appointments.value.some(apt => {
    const aptDate = formatLocalDate(new Date(apt.appointment_date));
    return aptDate === dateStr;
  });
};

// Calendar navigation
const previousMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1);
  fetchDoctorsworkingDates(formatMonthYear(currentDate.value));
};

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1);
  fetchDoctorsworkingDates(formatMonthYear(currentDate.value));
};

// Format month as 'YYYY-MM'
const formatMonthYear = (date) => {
  return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
};
const formatLocalDate = (date) => {
  if (date === null) {
    return "";
  }
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Calendar computations
const currentMonthYear = computed(() => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
  }).format(currentDate.value);
});
const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const days = [];

  // Add days from previous month
  const firstDayOfWeek = firstDay.getDay();
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    const date = new Date(year, month, -i);
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  // Add days of current month
  for (let date = new Date(firstDay); date <= lastDay; date.setDate(date.getDate() + 1)) {
    days.push({
      date: new Date(date),
      isCurrentMonth: true,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  // Add days from next month
  const remainingDays = 42 - days.length;
  for (let i = 1; i <= remainingDays; i++) {
    const date = new Date(year, month + 1, i);
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  return days;
});

// Helper functions
const isSameDate = (date1, date2) => {
  return date1.getDate() === date2.getDate() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getFullYear() === date2.getFullYear();
};

const isSelectedDate = (date) => {
  return isSameDate(date, selectedDate.value);
};

const formattedDate = computed(() => {
  if (!selectedDate.value) return '';
  return `${selectedDate.value.getFullYear()}/${(selectedDate.value.getMonth() + 1).toString().padStart(2, '0')}/${selectedDate.value.getDate().toString().padStart(2, '0')}`;
});

const selectDate = (date) => {
  selectedDate.value = date;
  filters.value.date = formattedDate.value;
  filters.value.doctorName = "";
  getAppointments(null, null, formattedDate.value); // Pass the selected date
};



// Filter by doctor name
const filterByDoctor = (doctorName) => {
  filters.value.doctorName = doctorName;
  getAppointments(null, null, filters.value.date); // Pass the selected date
};

const formattedSelectedDate = computed(() => {
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(selectedDate.value);
});



// Format date
const formatDate = (date) => {
  if (date === null) {
    return "N/A";
    
  }
  return new Date(date).toLocaleDateString();
};



function formatTime(time) {
  // Handle undefined or null input
  if (!time) {
    console.error("Invalid time input:", time);
    return "00:00"; // Return a default value or handle the error as needed
  }

  try {
    // Check if the time is in ISO format (e.g., "2023-10-02T10:00:00")
    if (time.includes('T')) {
      const [, timePart] = time.split('T');
      if (timePart.length === 6) return timePart; // Handle cases like "T10:00"
      const [hours, minutes] = timePart.split(':');
      return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
    }

    // Handle plain time strings (e.g., "10:00")
    const [hours, minutes] = time.split(':');
    return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
  } catch (error) {
    console.error("Error formatting time:", error);
    return "00:00"; // Return a default value or handle the error as needed
  }
}
// Status options
const statusOptions = ref([
  { value: "0", label: 'Scheduled' },
  { value: "1", label: 'Confirmed' },
  { value: "2", label: 'Canceled' },
  { value: "3", label: 'Pending' },
  { value: "4", label: 'Done' },
]);
// Filter doctors based on selected display mode

const filteredDoctors = (doctors) => {
  if (displayMode.value === 'all') {
    return doctors;
  } else if (displayMode.value === 'holidays') {
    return doctors.filter((doctor) => doctor.isHoliday);
  } else {
    return doctors.filter((doctor) => !doctor.isHoliday);
  }
};
const filteredAppointments = computed(() => {
  return appointments.value.filter(appointment => {
    // Base filtering conditions
    const baseConditions =
      (!filters.value.patientName ||
        `${appointment.patient_first_name} ${appointment.patient_last_name}`
          .toLowerCase()
          .includes(filters.value.patientName.toLowerCase())) &&
      (!filters.value.phone ||
        appointment.phone.includes(filters.value.phone)) &&
      (!filters.value.dateOfBirth ||
        appointment.patient_Date_Of_Birth.includes(filters.value.dateOfBirth)) &&
      (!filters.value.time ||
        appointment.appointment_time.includes(filters.value.time)) &&
      (!filters.value.status ||
        // Convert both values to numbers for comparison
        Number(appointment.status.value) === Number(filters.value.status));
    
    // Additional doctor name filter only if user is not a doctor
    const doctorNameCondition = role.value === 'doctor' ? true :
      (!filters.value.doctorName ||
        appointment.doctor_name.toLowerCase().includes(filters.value.doctorName.toLowerCase()));

    return baseConditions && doctorNameCondition;
  });
});
const generatePdf = async () => {
  try {
    const response = await axios.post('/api/generate-appointments-pdf', {
      appointments: filteredAppointments.value,
      date: formattedSelectedDate.value
    }, {
      responseType: 'blob'
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `appointments-${formattedSelectedDate.value}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Error generating PDF:', error);
    toast.add({
      severity: 'error',
      summary: 'PDF Generation Failed',
      detail: 'Failed to generate PDF',
      life: 3000
    });
  }
};

const printAppointments = () => {
  window.print();
};

// Transfer functionality
const toggleAppointmentSelection = (appointmentId) => {
  const index = selectedAppointments.value.indexOf(appointmentId);
  if (index > -1) {
    selectedAppointments.value.splice(index, 1);
  } else {
    selectedAppointments.value.push(appointmentId);
  }
};

const selectAllAppointments = () => {
  if (selectedAppointments.value.length === filteredAppointments.value.length) {
    selectedAppointments.value = [];
  } else {
    selectedAppointments.value = filteredAppointments.value.map(apt => apt.id);
  }
};

const openTransferModal = async () => {
  if (selectedAppointments.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Selection',
      detail: 'Please select at least one appointment to transfer.',
      life: 3000
    });
    return;
  }
  
  try {
    // Fetch available doctors
    const response = await axios.get('/api/doctors');
    transferData.value.availableDoctors = response.data.data || response.data;
    transferData.value.newDate = formatLocalDate(new Date());
    showTransferModal.value = true;
  } catch (error) {
    console.error('Error fetching doctors:', error);
    toast.add({
      severity: 'error',
      summary: 'Loading Failed',
      detail: 'Failed to load doctors for transfer.',
      life: 3000
    });
  }
};

const closeTransferModal = () => {
  showTransferModal.value = false;
  transferData.value.newDoctorId = '';
  transferData.value.newDate = '';
  transferData.value.availableDoctors = [];
};

const transferAppointments = async () => {
  if (!transferData.value.newDoctorId || !transferData.value.newDate) {
    toast.add({
      severity: 'warn',
      summary: 'Incomplete Selection',
      detail: 'Please select both doctor and date for transfer.',
      life: 3000
    });
    return;
  }
  
  transferLoading.value = true;
  
  try {
    const response = await axios.post('/api/appointments/transfer', {
      appointment_ids: selectedAppointments.value,
      new_doctor_id: transferData.value.newDoctorId,
      new_date: transferData.value.newDate
    });
    
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Transfer Successful',
        detail: `Successfully transferred ${response.data.data.transferred_count} appointments with ${response.data.data.conflict_resolutions.length} conflict resolutions.`,
        life: 5000
      });
      
      // Show conflict details if any
      if (response.data.data.conflict_resolutions.length > 0) {
        const conflicts = response.data.data.conflict_resolutions
          .map(c => `Appointment ID ${c.appointment_id}: ${c.original_time} → ${c.new_time}`)
          .join(', ');
        toast.add({
          severity: 'info',
          summary: 'Time Conflicts Resolved',
          detail: conflicts,
          life: 8000
        });
      }
      
      // Refresh appointments and close modal
      await getAppointments();
      selectedAppointments.value = [];
      closeTransferModal();
    } else {
      toast.add({
        severity: 'error',
        summary: 'Transfer Failed',
        detail: response.data.message,
        life: 4000
      });
    }
  } catch (error) {
    console.error('Error transferring appointments:', error);
    toast.add({
      severity: 'error',
      summary: 'Transfer Error',
      detail: 'Failed to transfer appointments: ' + (error.response?.data?.message || error.message),
      life: 4000
    });
  } finally {
    transferLoading.value = false;
  }
};

// Modified onMounted to ensure doctor data is initialized first
onMounted(async () => {
  await initializeDoctor();
  fetchDoctorsworkingDates(formatMonthYear(currentDate.value));
});
</script>
<template>
  <div class="container">
    <div class="card mb-4 shadow-sm rounded-lg">
      <div class="card-header d-flex justify-content-between align-items-center p-3 bg-primary text-white">
        <h4 class="card-title mb-0"></h4>
        <div class="d-flex align-items-center">
          <button @click="previousMonth" class="btn btn-light btn-sm me-2">
            &lt; <!-- Left arrow for previous month -->
          </button>
         
          <span class="fw-bold mx-3 fs-5 text-black" style="color:black;">{{ currentMonthYear }}</span>
          <button @click="nextMonth" class="btn btn-light btn-sm ms-2">
            &gt; <!-- Right arrow for next month -->
          </button>
        </div>
      </div>
    <!-- Toggle Buttons -->
  <div class="toggle-buttons mb-2 mt-2">
    <button 
      class="toggle-btn" 
      :class="{ active: displayMode === 'working' }" 
      @click="setDisplayMode('working')">
      Show Working Dates
    </button>
    <button 
      class="toggle-btn" 
      :class="{ active: displayMode === 'holidays' }" 
      @click="setDisplayMode('holidays')">
      Days off
    </button>
   
  </div>

  <!-- Calendar Grid -->
  <div class="calendar-grid">
    <!-- Weekday Headers -->
    <div v-for="day in weekDays" :key="day" class="calendar-header">
      {{ day }}
    </div>
    <!-- Calendar Days -->
    <div v-for="{ date, isCurrentMonth, isToday, doctors } in calendarDays" :key="date.toISOString()"
      class="calendar-day" 
      :class="{
        'current-month': isCurrentMonth,
        'other-month': !isCurrentMonth,
        'today': isToday,
        'selected': isSelectedDate(date)
      }" 
      @click="selectDate(date)">
      <div class="date-number">{{ date.getDate() }}</div>
     
      
      <!-- Display doctors with a vertical stack based on display mode -->
      <div class="doctor-container" v-if="doctors.length > 0">
        <div v-for="doctor in filteredDoctors(doctors)" :key="doctor.name" 
          class="doctor-badge"
          :class="{ 'holiday': doctor.isHoliday }"
          :style="{ borderLeftColor: doctor.color }" 
          @click.stop="filterByDoctor(doctor.isHoliday ? '' : doctor.name.split(':')[0])">
          <span class="doctor-name">{{ doctor.name }}</span>
        </div>
      </div>
    </div>
  </div>
</div>
    

    <div class="selected-date-display mb-3 p-3 bg-light rounded shadow-sm text-center" v-if="selectedDate">
      <h5 class="mb-0">{{ formattedSelectedDate }}</h5>
    </div>

    <div>
      <!-- Error Message -->
      <div v-if="error" class="alert alert-danger my-4">
        {{ error }}
      </div>

      <!-- Filters and Table -->
      <div class="card shadow-sm">
        <div class="mb-2 bg-light d-flex justify-content-between align-items-center p-3">
          <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0">Appointments</h5>
            <span v-if="selectedAppointments.length > 0" class="badge bg-info">
              {{ selectedAppointments.length }} selected
            </span>
          </div>
          <div class="d-flex gap-2">
            <button 
              v-if="(selectedAppointments.length > 0 ) " 
              @click="openTransferModal" 
              class="btn btn-warning btn-sm"
              :disabled="transferLoading">
              <i class="fas fa-exchange-alt me-2"></i> Transfer Selected
            </button>
            <button @click="generatePdf" class="btn btn-primary btn-sm">
              <i class="fas fa-file-pdf me-2"></i> Export PDF
            </button>
          </div>
        </div>
        <div class="tw-p-4">
          <div class="tw-mb-4 tw-grid tw-grid-cols-1 md:tw-grid-cols-4 lg:tw-grid-cols-8 tw-gap-2">
            <input v-model="filters.patientName" class="tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm" placeholder="Patient Name" />
            <input v-model="filters.phone" class="tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm" placeholder="Phone" />
            <input v-model="filters.dateOfBirth" class="tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm" placeholder="Date of Birth" />
            <input v-model="filters.date" class="tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm" placeholder="Date" />
            <input v-model="filters.time" class="tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm" placeholder="Time" />
            <select v-model="filters.status" class="tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm">
              <option value="">All Statuses</option>
              <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
            <input v-if="role !== 'doctor'" v-model="filters.doctorName" class="tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm" placeholder="Doctor Name" />
            <div class="tw-flex tw-items-center">
              <input 
                type="checkbox" 
                @change="selectAllAppointments" 
                :checked="selectedAppointments.length === filteredAppointments.length && filteredAppointments.length > 0"
                class="tw-mr-2"
              />
              <span class="tw-text-sm tw-text-gray-600">Select All</span>
            </div>
          </div>
          
          <DataTable 
            :value="filteredAppointments" 
            :paginator="true" 
            :rows="10" 
            :rowsPerPageOptions="[5, 10, 20, 50]"
            class="tw-w-full"
            tableClass="tw-min-w-full tw-bg-white tw-border tw-border-gray-200"
            :emptyMessage="'No appointments found.'"
          >
            <Column 
              field="select" 
              header="Select" 
              :sortable="false"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            >
              <template #body="{ data }">
                <input 
                  type="checkbox" 
                  :value="data.id" 
                  @change="toggleAppointmentSelection(data.id)"
                  :checked="selectedAppointments.includes(data.id)"
                  class="tw-rounded tw-border-gray-300"
                />
              </template>
            </Column>
            
            <Column 
              field="index" 
              header="#" 
              :sortable="false"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            >
              <template #body="{ index }">
                {{ index + 1 }}
              </template>
            </Column>
            
            <Column 
              field="patient_name" 
              header="Patient Name" 
              :sortable="true"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            >
              <template #body="{ data }">
                {{ data.patient_first_name }} {{ data.patient_last_name }}
              </template>
            </Column>
            
            <Column 
              field="phone" 
              header="Phone" 
              :sortable="true"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            />
            
            <Column 
              field="patient_Date_Of_Birth" 
              header="Date Of Birth" 
              :sortable="true"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            >
              <template #body="{ data }">
                {{ formatDate(data.patient_Date_Of_Birth) }}
              </template>
            </Column>
            
            <Column 
              field="appointment_date" 
              header="Appointment Date" 
              :sortable="true"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            >
              <template #body="{ data }">
                {{ formatDate(data.appointment_date) }}
              </template>
            </Column>
            
            <Column 
              field="appointment_time" 
              header="Appointment Time" 
              :sortable="true"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            >
              <template #body="{ data }">
                {{ formatTime(data.appointment_time) }}
              </template>
            </Column>
            
            <Column 
              field="status" 
              header="Status" 
              :sortable="true"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            >
              <template #body="{ data }">
                  <span class="tw-inline-flex tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-rounded-full" :class="`bg-${data.status.color.toLowerCase()}`">
                    {{ data.status.name }}
                  </span>
                </template>
            </Column>
            
            <Column 
              v-if="role !== 'doctor'"
              field="doctor_name" 
              header="Doctor Name" 
              :sortable="true"
              headerClass="tw-bg-blue-50 tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
              bodyClass="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
            />
          </DataTable>
        </div>
      </div>
    </div>

    <!-- Transfer Modal -->
    <div v-if="showTransferModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-exchange-alt me-2"></i>
              Transfer Appointments
            </h5>
            <button type="button" class="btn-close" @click="closeTransferModal"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label fw-bold">Select New Doctor:</label>
                  <select v-model="transferData.newDoctorId" class="form-select" required>
                    <option value="">Choose a doctor...</option>
                    <option v-for="doctor in transferData.availableDoctors" :key="doctor.id" :value="doctor.id">
                      {{ doctor.name}} - {{ doctor.specialization || 'General' }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label fw-bold">Select New Date:</label>
                  <input 
                    v-model="transferData.newDate" 
                    type="date" 
                    class="form-control" 
                    :min="formatLocalDate(new Date())"
                    required
                  />
                </div>
              </div>
            </div>
            
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Transfer Details:</strong>
              <ul class="mb-0 mt-2">
                <li>{{ selectedAppointments.length }} appointment(s) will be transferred</li>
                <li>Original appointment times will be preserved when possible</li>
                <li>Time conflicts will be resolved by adding 5-minute intervals</li>
                <li>All appointment details and patient information will remain unchanged</li>
              </ul>
            </div>
            
            <div class="mt-3">
              <h6 class="fw-bold">Selected Appointments:</h6>
              <div class="tw-max-h-48 tw-overflow-y-auto">
                <DataTable 
                  :value="filteredAppointments.filter(apt => selectedAppointments.includes(apt.id))" 
                  class="tw-w-full"
                  tableClass="tw-min-w-full tw-bg-white tw-border tw-border-gray-200"
                  :emptyMessage="'No appointments selected.'"
                  :paginator="false"
                >
                  <Column 
                    field="patient_name" 
                    header="Patient" 
                    headerClass="tw-bg-gray-50 tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
                    bodyClass="tw-px-3 tw-py-2 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
                  >
                    <template #body="{ data }">
                      {{ data.patient_first_name }} {{ data.patient_last_name }}
                    </template>
                  </Column>
                  
                  <Column 
                    field="appointment_date" 
                    header="Current Date" 
                    headerClass="tw-bg-gray-50 tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
                    bodyClass="tw-px-3 tw-py-2 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
                  >
                    <template #body="{ data }">
                      {{ formatDate(data.appointment_date) }}
                    </template>
                  </Column>
                  
                  <Column 
                    field="appointment_time" 
                    header="Current Time" 
                    headerClass="tw-bg-gray-50 tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
                    bodyClass="tw-px-3 tw-py-2 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
                  >
                    <template #body="{ data }">
                      {{ formatTime(data.appointment_time) }}
                    </template>
                  </Column>
                  
                  <Column 
                    field="doctor_name" 
                    header="Current Doctor" 
                    headerClass="tw-bg-gray-50 tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200"
                    bodyClass="tw-px-3 tw-py-2 tw-whitespace-nowrap tw-text-sm tw-border-b tw-border-gray-200"
                  />
                </DataTable>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeTransferModal" :disabled="transferLoading">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-warning" 
              @click="transferAppointments" 
              :disabled="transferLoading || !transferData.newDoctorId || !transferData.newDate"
            >
              <span v-if="transferLoading" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-exchange-alt me-2"></i>
              {{ transferLoading ? 'Transferring...' : 'Transfer Appointments' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Toast component for notifications -->
  <Toast />
</template>

<style scoped>
.toggle-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  justify-content: center;
}

.toggle-btn {
  padding: 8px 16px;
  border: 1px solid #dee2e6;
  background-color: #f8f9fa;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  background-color: #e9ecef;
}

.toggle-btn.active {
  background-color: #0d6efd;
  color: white;
  border-color: #0d6efd;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background-color: #f0f2f5;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
}

.calendar-header {
  background-color: #e9ecef;
  padding: 1rem;
  text-align: center;
  font-weight: bold;
  border-bottom: 1px solid #dee2e6;
}

.calendar-day {
  background-color: white;
  min-height: 200px;
  padding: 1rem;
  position: relative;
  cursor: pointer;
  border: 1px solid #f0f2f5;
  transition: all 0.2s ease;
}

.calendar-day:hover {
  background-color: #f8f9fa;
  transform: scale(0.98);
}

.date-number {
  position: absolute;
  top: 5px;
  right: 5px;
  font-weight: bold;
  background-color: #f8f9fa;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.other-month {
  color: #adb5bd;
  background-color: #f8f9fa;
}

.today {
  background-color: #e3f2fd;
}

.today .date-number {
  background-color: #0d6efd;
  color: white;
}

.selected {
  background-color: #e7f5ff;
  border: 2px solid #0d6efd;
}

.selected .date-number {
  background-color: #0d6efd;
  color: white;
  font-weight: bold;
}

.appointment-indicator {
  position: absolute;
  bottom: 5px;
  right: 5px;
  background-color: #0d6efd;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Doctor container and badges */
.doctor-container {
  position: absolute;
  top: 35px;
  left: 0;
  right: 30px;
  bottom: 30px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0 5px;
}

.doctor-badge {
  font-size: 13px;
  padding: 3px 6px;
  border-radius: 4px;
  background-color: #f8f9fa;
  border-left: 3px solid;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  cursor: pointer;
  transition: background-color 0.2s;
}

.doctor-badge:hover {
  background-color: #e9ecef;
}

.doctor-badge.holiday {
  opacity: 0.7;
}

.doctor-name {
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Transfer functionality styles */
.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.badge.bg-info {
  background-color: #0dcaf0 !important;
}

.modal.show {
  display: block !important;
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.5);
}

.btn-warning {
  background-color: #ffc107;
  border-color: #ffc107;
  color: #000;
}

.btn-warning:hover {
  background-color: #ffca2c;
  border-color: #ffc720;
  color: #000;
}

.btn-warning:disabled {
  background-color: #ffc107;
  border-color: #ffc107;
  opacity: 0.65;
}

.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
}

.table-responsive {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Status Badge Classes */
.status-scheduled {
  @apply tw-bg-blue-100 tw-text-blue-800 tw-border tw-border-blue-200;
}

.status-confirmed {
  @apply tw-bg-green-100 tw-text-green-800 tw-border tw-border-green-200;
}

.status-canceled {
  @apply tw-bg-red-100 tw-text-red-800 tw-border tw-border-red-200;
}

.status-cancelled {
  @apply tw-bg-red-100 tw-text-red-800 tw-border tw-border-red-200;
}

.status-pending {
  @apply tw-bg-yellow-100 tw-text-yellow-800 tw-border tw-border-yellow-200;
}

.status-done {
  @apply tw-bg-indigo-100 tw-text-indigo-800 tw-border tw-border-indigo-200;
}

.status-onworking {
  @apply tw-bg-orange-100 tw-text-orange-800 tw-border tw-border-orange-200;
}

/* PrimeVue severity mapping to Tailwind classes */
.status-primary {
  @apply tw-bg-blue-100 tw-text-blue-800 tw-border tw-border-blue-200;
}

.status-success {
  @apply tw-bg-green-100 tw-text-green-800 tw-border tw-border-green-200;
}

.status-danger {
  @apply tw-bg-red-100 tw-text-red-800 tw-border tw-border-red-200;
}

.status-warning {
  @apply tw-bg-yellow-100 tw-text-yellow-800 tw-border tw-border-yellow-200;
}

.status-info {
  @apply tw-bg-indigo-100 tw-text-indigo-800 tw-border tw-border-indigo-200;
}

.status-secondary {
  @apply tw-bg-gray-100 tw-text-gray-800 tw-border tw-border-gray-200;
}

@media (max-width: 768px) {
  .toggle-buttons {
    flex-wrap: wrap;
  }
  
  .toggle-btn {
    font-size: 12px;
    padding: 6px 12px;
  }
  
  .calendar-day {
    min-height: 100px;
    padding: 0.5rem;
  }
  
  .doctor-badge {
    font-size: 9px;
    padding: 2px 4px;
  }
  
  .doctor-container {
    top: 30px;
    right: 25px;
    bottom: 25px;
  }
  
  .modal-dialog {
    margin: 1rem;
  }
  
  .d-flex.gap-2 {
    flex-direction: column;
    gap: 0.5rem !important;
  }
  
  .btn-sm {
    font-size: 0.75rem;
  }
}
</style>