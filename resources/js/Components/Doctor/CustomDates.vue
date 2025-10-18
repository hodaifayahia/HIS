<script setup>
import { ref, defineProps, defineEmits, onMounted, watch } from 'vue';
import { Field } from 'vee-validate';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import axios from 'axios';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  doctorId: {
    type: Number,
    required: false,
  },
  value: {
    type: Object,
    default: () => ({}),
  },
  patients_based_on_time: {
    type: Boolean,
    required: true,
  },
  time_slot: {
    type: Number,
    required: true,
  },
  existingSchedules: {
    type: Array,
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'schedulesUpdated']);

const dates = ref([]);

// Helper function to calculate patients per shift
const calculatePatientsPerShift = (startTime, endTime, slot) => {
  if (!startTime || !endTime || !slot) return 0;

  const start = new Date(`1970-01-01T${startTime}`);
  const end = new Date(`1970-01-01T${endTime}`);
  const totalMinutes = (end - start) / 60000;
  return Math.floor(totalMinutes / slot);
};

function formatTimeForInput(time) {
  if (!time) return '';
  const [hours, minutes] = time.split(':');
  return `${hours}:${minutes}`;
}

const processExistingSchedules = (schedules) => {
  const processedDates = new Map();

  schedules.forEach(schedule => {
    const dateKey = schedule.date;
    
    if (!processedDates.has(dateKey)) {
      processedDates.set(dateKey, {
        date: dateKey,
        morningStartTime: '',
        morningEndTime: '',
        morningPatients: 0,
        afternoonStartTime: '',
        afternoonEndTime: '',
        afternoonPatients: 0,
      });
    }

    const dateEntry = processedDates.get(dateKey);
    const timePrefix = schedule.shift_period.toLowerCase();
    
    dateEntry[`${timePrefix}StartTime`] = formatTimeForInput(schedule.start_time);
    dateEntry[`${timePrefix}EndTime`] = formatTimeForInput(schedule.end_time);
    dateEntry[`${timePrefix}Patients`] = schedule.number_of_patients_per_day;
  });

  return Array.from(processedDates.values());
};

// Calculate and update patients for a specific date and shift
const updatePatientsForShift = (dateInfo, shift) => {
  const startTime = dateInfo[`${shift}StartTime`];
  const endTime = dateInfo[`${shift}EndTime`];

  if (startTime && endTime && props.patients_based_on_time) {
    dateInfo[`${shift}Patients`] = calculatePatientsPerShift(
      startTime,
      endTime,
      props.time_slot
    );
  }
};

const handlePatientsChange = (index, shift, event) => {
  if (!props.patients_based_on_time) {
    const value = parseInt(event.target.value) || 0;
    dates.value[index][`${shift}Patients`] = value;
    emitUpdate();
  }
};

onMounted(() => {
  if (props.existingSchedules && props.existingSchedules.length > 0) {
    dates.value = processExistingSchedules(props.existingSchedules);
  } else if (!props.modelValue || props.modelValue.length === 0) {
    dates.value = [{
      date: '',
      morningStartTime: '',
      morningEndTime: '',
      morningPatients: 0,
      afternoonStartTime: '',
      afternoonEndTime: '',
      afternoonPatients: 0,
    }];
  } else {
    dates.value = props.modelValue.map(date => {
      if (typeof date === 'string') {
        return {
          date,
          morningStartTime: '',
          morningEndTime: '',
          morningPatients: 0,
          afternoonStartTime: '',
          afternoonEndTime: '',
          afternoonPatients: 0,
        };
      }
      return date;
    });
  }
});

const addDate = () => {
  dates.value.push({
    date: '',
    morningStartTime: '',
    morningEndTime: '',
    morningPatients: 0,
    afternoonStartTime: '',
    afternoonEndTime: '',
    afternoonPatients: 0,
  });
  emitUpdate();
};

const removeDate = async (index) => {
  const removedDate = dates.value[index];
  console.log(props.doctorId);
  

  // Only send a delete request if the schedule exists in the backend
  if (removedDate.date && props.doctorId) {
    try {
      await axios.delete(`/api/schedules/${props.doctorId}`, {
        data: { date: removedDate.date }, // Pass the date to identify the record
      });

      // Remove from the local state only after successful API response
      dates.value.splice(index, 1);
      emitUpdate();
    } catch (error) {
      console.error('Error deleting schedule:', error);
    }
  } else {
    // If not in backend, just remove from the UI
    dates.value.splice(index, 1);
    emitUpdate();
  }
};

const handleChange = (index, field, event) => {
  dates.value[index][field] = event.target.value;

  // If time fields change, update patient calculations
  if (field.includes('Time')) {
    const shift = field.includes('morning') ? 'morning' : 'afternoon';
    updatePatientsForShift(dates.value[index], shift);
  }

  emitUpdate();
};

const handleDateChange = (index, value) => {
  // Format the date to YYYY-MM-DD format for Laravel validation
  if (value instanceof Date) {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');
    dates.value[index].date = `${year}-${month}-${day}`;
  } else if (typeof value === 'string') {
    dates.value[index].date = value;
  } else {
    dates.value[index].date = '';
  }
  
  emitUpdate();
};

const emitUpdate = () => {
  const schedulesData = dates.value.flatMap((dateInfo) => {
    const records = [];
    const day = new Date(dateInfo.date).getDay();
    const dayName = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][day];

    // Morning shift
    if (dateInfo.morningStartTime && dateInfo.morningEndTime) {
      records.push({
        date: dateInfo.date,
        day_of_week: dayName,
        shift_period: 'morning',
        start_time: dateInfo.morningStartTime,
        end_time: dateInfo.morningEndTime,
        is_active: true,
        number_of_patients_per_day: dateInfo.morningPatients,
      });
    }

    // Afternoon shift
    if (dateInfo.afternoonStartTime && dateInfo.afternoonEndTime) {
      records.push({
        date: dateInfo.date,
        day_of_week: dayName,
        shift_period: 'afternoon',
        start_time: dateInfo.afternoonStartTime,
        end_time: dateInfo.afternoonEndTime,
        is_active: true,
        number_of_patients_per_day: dateInfo.afternoonPatients,
      });
    }

    return records;
  });

  emit('update:modelValue', dates.value);
  emit('schedulesUpdated', schedulesData);
};

// Watch for changes in props that affect patient calculations
watch(
  [() => props.patients_based_on_time, () => props.time_slot],
  () => {
    dates.value.forEach(dateInfo => {
      updatePatientsForShift(dateInfo, 'morning');
      updatePatientsForShift(dateInfo, 'afternoon');
    });
    emitUpdate();
  }
);
</script>

<template>
  <div class="tw-w-full tw-space-y-6">
    <Card 
      v-for="(dateInfo, index) in dates" 
      :key="index" 
      class="tw-shadow-lg tw-border-0 tw-bg-gradient-to-br tw-from-white tw-to-gray-50"
    >
      <template #header>
        <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-p-4 tw-rounded-t-lg">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div class="tw-flex tw-items-center tw-space-x-3">
              <i class="pi pi-calendar tw-text-xl"></i>
              <h3 class="tw-text-lg tw-font-semibold tw-m-0">
                Custom Schedule {{ index + 1 }}
              </h3>
            </div>
            <Badge 
              v-if="dateInfo.date" 
              :value="new Date(dateInfo.date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })" 
              severity="info" 
              class="tw-bg-white tw-text-blue-600"
            />
          </div>
        </div>
      </template>
      
      <template #content>
        <div class="tw-p-6 tw-space-y-6">
          <!-- Date Input -->
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-calendar-plus tw-mr-2 tw-text-blue-500"></i>
              Select Date
            </label>
            <Calendar
              v-model="dateInfo.date"
              @update:modelValue="(value) => handleDateChange(index, value)"
              dateFormat="yy-mm-dd"
              :showIcon="true"
              appendTo="self"
              iconDisplay="input"
              class="tw-w-full"
              inputClass="tw-w-full tw-px-4 tw-py-3 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
              placeholder="Select a date"
            />
          </div>

          <Divider class="tw-my-6" />

          <!-- Morning Session -->
          <div class="tw-bg-gradient-to-r tw-from-amber-50 tw-to-orange-50 tw-p-5 tw-rounded-xl tw-border tw-border-amber-200">
            <div class="tw-flex tw-items-center tw-mb-4">
              <i class="pi pi-sun tw-text-2xl tw-text-amber-500 tw-mr-3"></i>
              <h4 class="tw-text-lg tw-font-semibold tw-text-amber-800 tw-m-0">Morning Session</h4>
            </div>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
              <div class="tw-space-y-2">
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                  <i class="pi pi-clock tw-mr-2 tw-text-green-500"></i>
                  Start Time
                </label>
                <Field
                  type="time"
                  :name="'morningStartTime-' + index"
                  :value="dateInfo.morningStartTime"
                  @input="(e) => handleChange(index, 'morningStartTime', e)"
                  class="tw-w-full tw-px-4 tw-py-3 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200 tw-bg-white"
                />
              </div>
              <div class="tw-space-y-2">
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                  <i class="pi pi-clock tw-mr-2 tw-text-red-500"></i>
                  End Time
                </label>
                <Field
                  type="time"
                  :name="'morningEndTime-' + index"
                  :value="dateInfo.morningEndTime"
                  @input="(e) => handleChange(index, 'morningEndTime', e)"
                  class="tw-w-full tw-px-4 tw-py-3 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200 tw-bg-white"
                />
              </div>
              <div class="tw-space-y-2">
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                  <i class="pi pi-users tw-mr-2 tw-text-purple-500"></i>
                  Patients
                </label>
                <InputNumber
                  v-model="dateInfo.morningPatients"
                  @update:modelValue="(value) => handlePatientsChange(index, 'morning', { target: { value } })"
                  :min="0"
                  :disabled="props.patients_based_on_time"
                  class="tw-w-full"
                  inputClass="tw-w-full tw-px-4 tw-py-3 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                  :showButtons="true"
                  buttonLayout="horizontal"
                />
              </div>
            </div>
          </div>

          <!-- Afternoon Session -->
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-5 tw-rounded-xl tw-border tw-border-blue-200">
            <div class="tw-flex tw-items-center tw-mb-4">
              <i class="pi pi-moon tw-text-2xl tw-text-blue-500 tw-mr-3"></i>
              <h4 class="tw-text-lg tw-font-semibold tw-text-blue-800 tw-m-0">Afternoon Session</h4>
            </div>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
              <div class="tw-space-y-2">
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                  <i class="pi pi-clock tw-mr-2 tw-text-green-500"></i>
                  Start Time
                </label>
                <Field
                  type="time"
                  :name="'afternoonStartTime-' + index"
                  :value="dateInfo.afternoonStartTime"
                  @input="(e) => handleChange(index, 'afternoonStartTime', e)"
                  class="tw-w-full tw-px-4 tw-py-3 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200 tw-bg-white"
                />
              </div>
              <div class="tw-space-y-2">
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                  <i class="pi pi-clock tw-mr-2 tw-text-red-500"></i>
                  End Time
                </label>
                <Field
                  type="time"
                  :name="'afternoonEndTime-' + index"
                  :value="dateInfo.afternoonEndTime"
                  @input="(e) => handleChange(index, 'afternoonEndTime', e)"
                  class="tw-w-full tw-px-4 tw-py-3 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200 tw-bg-white"
                />
              </div>
              <div class="tw-space-y-2">
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                  <i class="pi pi-users tw-mr-2 tw-text-purple-500"></i>
                  Patients
                </label>
                <InputNumber
                  v-model="dateInfo.afternoonPatients"
                  @update:modelValue="(value) => handlePatientsChange(index, 'afternoon', { target: { value } })"
                  :min="0"
                  :disabled="props.patients_based_on_time"
                  class="tw-w-full"
                  inputClass="tw-w-full tw-px-4 tw-py-3 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                  :showButtons="true"
                  buttonLayout="horizontal"
                />
              </div>
            </div>
          </div>

          <!-- Remove Button -->
          <div class="tw-flex tw-justify-end tw-pt-4" v-if="dates.length > 1">
            <Button
              @click="removeDate(index)"
              severity="danger"
              outlined
              class="tw-transition-all tw-duration-200 hover:tw-scale-105"
            >
              <i class="pi pi-trash tw-mr-2"></i>
              Remove Schedule
            </Button>
          </div>
        </div>
      </template>
    </Card>

    <!-- Add Date Button -->
    <div class="tw-flex tw-justify-center tw-pt-6">
      <Button
        @click="addDate"
        class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-border-0 tw-px-8 tw-py-3 tw-text-white tw-font-semibold tw-rounded-xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 hover:tw-scale-105"
      >
        <i class="pi pi-plus tw-mr-2"></i>
        Add New Schedule
      </Button>
    </div>
  </div>
</template>

<style scoped>
/* Custom PrimeVue component overrides */
:deep(.p-card) {
  @apply tw-transition-all tw-duration-300 hover:tw-shadow-xl;
}

:deep(.p-card-header) {
  @apply tw-p-0;
}

:deep(.p-card-content) {
  @apply tw-p-0;
}

:deep(.p-inputnumber-input) {
  @apply tw-bg-white;
}

:deep(.p-calendar-w-btn .p-inputtext) {
  @apply tw-pr-12;
}

:deep(.p-calendar .p-button) {
  @apply tw-bg-blue-500 tw-border-blue-500 hover:tw-bg-blue-600;
}

:deep(.p-button.p-button-outlined.p-button-danger) {
  @apply tw-border-red-500 tw-text-red-500 hover:tw-bg-red-500 hover:tw-text-white;
}

/* Animation classes */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  @apply tw-bg-gray-100;
}

::-webkit-scrollbar-thumb {
  @apply tw-bg-gray-400 tw-rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply tw-bg-gray-500;
}
</style>