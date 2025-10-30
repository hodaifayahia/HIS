<script setup>
import { reactive, defineEmits, watch, ref } from 'vue';
import * as yup from 'yup';
import { useForm } from 'vee-validate';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Badge from 'primevue/badge';
import Divider from 'primevue/divider';
import Message from 'primevue/message';

const emit = defineEmits(['schedulesUpdated', 'update:modelValue']);

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  patients_based_on_time: {
    type: Boolean,
    default: false,
  },
  time_slot: {
    type: Number,
    required: false,
  },
  existingSchedules: {
    type: Array,
    default: () => []
  }
});

// Initialize schedules with reactive
const schedules = reactive(
  daysOfWeek.reduce((acc, day) => ({
    ...acc,
    [day]: {
      morning: { 
        isActive: false, 
        startTime: '08:00', 
        endTime: '12:00',
        patients_per_day: 0 
      },
      afternoon: { 
        isActive: false, 
        startTime: '13:00', 
        endTime: '17:00',
        patients_per_day: 0 
      },
    },
  }), {})
);

// Improved reset function
const resetSchedules = () => {
  Object.keys(schedules).forEach(day => {
    schedules[day] = {
      morning: { 
        isActive: false, 
        startTime: '08:00', 
        endTime: '12:00',
        patients_per_day: 0 
      },
      afternoon: { 
        isActive: false, 
        startTime: '13:00', 
        endTime: '17:00',
        patients_per_day: 0 
      }
    };
  });
};

// Improved populate function
const populateSchedules = (existingSchedules) => {
  if (!Array.isArray(existingSchedules) || existingSchedules.length === 0) return;

  resetSchedules();

  existingSchedules.forEach(schedule => {
    const day = schedule.day_of_week.charAt(0).toUpperCase() + schedule.day_of_week.slice(1);
    
    if (schedules[day] && schedule.is_active) {
      const shift = schedule.shift_period.toLowerCase();
      if (schedules[day][shift]) {
        schedules[day][shift] = {
          isActive: true,
          startTime: formatTimeForInput(schedule.start_time),
          endTime: formatTimeForInput(schedule.end_time),
          patients_per_day: schedule.number_of_patients_per_day || 0
        };
      }
    }
  });
};

// Watch for existingSchedules changes
watch(
  () => props.existingSchedules,
  (newSchedules) => {
    if (newSchedules && Array.isArray(newSchedules)) {
      populateSchedules(newSchedules);
    }
  },
  { immediate: true, deep: true }
);

// Form validation schema
const { values, errors } = useForm({
  initialValues: schedules,
  validationSchema: yup.object().shape({
    schedules: yup.array().min(1, "At least one schedule is required"),
    patients_based_on_time: yup.boolean().required(),
    time_slot: yup.number().when('patients_based_on_time', {
      is: true,
      then: yup.number().required('Time slot is required when scheduling by time'),
    }),
  }),
});
// Fetch excluded dates from the backend
const fetchExcludedDates = async (doctorId) => {    
    
    try {
      const response = await axios.get(`/api/excluded-dates/${doctorId}`);
      excludedDates.value = response.data.data;
      console.log(excludedDates.value);
      
    } catch (error) {
      toast.error('Failed to fetch excluded dates');
      console.error('Error fetching excluded dates:', error);
    }
  };

// Helper function to format time for input
function formatTimeForInput(time) {
  if (!time) return '';
  
  try {
    // Handle different time formats
    if (time.includes('T')) {
      // ISO format: 2024-01-01T09:00:00.000000Z
      const date = new Date(time);
      return date.toTimeString().slice(0, 5); // Extracts HH:mm
    } else if (time.includes(':')) {
      // Simple time format: 09:00:00 or 09:00
      const timeParts = time.split(':');
      return `${timeParts[0].padStart(2, '0')}:${timeParts[1].padStart(2, '0')}`;
    }
    return time;
  } catch (e) {
    console.error("Error formatting time:", time, e);
    return time || '';
  }
}

// Calculate patients per shift
const calculatePatientsPerDay = (startTime, endTime, slot) => {
  if (!startTime || !endTime || !slot) return 0;
  const start = new Date(`1970-01-01T${startTime}`);
  const end = new Date(`1970-01-01T${endTime}`);
  const totalMinutes = (end - start) / 60000;
  return Math.floor(totalMinutes / slot);
};

// Function to update schedules data
const updateSchedulesData = () => {
  const schedulesData = Object.entries(schedules)
    .flatMap(([day, shifts]) => {
      const records = [];
      if (shifts.morning.isActive) {
        records.push({
          day_of_week: day.toLowerCase(),
          shift_period: 'morning',
          start_time: shifts.morning.startTime.slice(0, 5),
          end_time: shifts.morning.endTime.slice(0, 5),
          is_active: true,
          number_of_patients_per_day: props.patients_based_on_time
            ? calculatePatientsPerDay(shifts.morning.startTime, shifts.morning.endTime, props.time_slot)
            : shifts.morning.patients_per_day,
        });
      }
      if (shifts.afternoon.isActive) {
        records.push({
          day_of_week: day.toLowerCase(),
          shift_period: 'afternoon',
          start_time: shifts.afternoon.startTime.slice(0, 5),
          end_time: shifts.afternoon.endTime.slice(0, 5),
          is_active: true,
          number_of_patients_per_day: props.patients_based_on_time
            ? calculatePatientsPerDay(shifts.afternoon.startTime, shifts.afternoon.endTime, props.time_slot)
            : shifts.afternoon.patients_per_day,
        });
      }
      return records;
    })
    .filter(record => record !== null);

  emit('schedulesUpdated', schedulesData);
  emit('update:modelValue', { schedules: schedulesData });
};

// Watch for changes in schedules
watch(schedules, () => {
  updateSchedulesData();
}, { deep: true });

// Watch for changes in props that affect patient numbers
watch(
  [
    () => props.patients_based_on_time,
    () => props.time_slot
  ],
  () => {
    updateSchedulesData();
  }
);

// Handle time input changes
const handleTimeChange = (day, shift, type, event) => {
  const time = event.target.value;
  schedules[day][shift][type] = time;
  updateSchedulesData();
};

// Handle patients per day changes
const handlePatientsChange = (day, shift, event) => {
  const value = parseInt(event.target.value) || 0;
  schedules[day][shift].patients_per_day = value;
  updateSchedulesData();
};
</script>

<template>
  <Card class="tw-shadow-xl tw-border-0 tw-bg-gradient-to-br tw-from-white tw-to-gray-50">
    <template #header>
      <div class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-blue-600 tw-text-white tw-p-6 tw-rounded-t-lg">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-space-x-3">
            <i class="pi pi-calendar tw-text-2xl"></i>
            <h2 class="tw-text-2xl tw-font-bold tw-m-0">Weekly Schedule</h2>
          </div>
          <Badge 
            :value="`${Object.values(schedules).reduce((count, day) => count + (day.morning.isActive ? 1 : 0) + (day.afternoon.isActive ? 1 : 0), 0)} Active Shifts`" 
            severity="info" 
            class="tw-bg-white tw-text-purple-600 tw-px-3 tw-py-1"
          />
        </div>
      </div>
    </template>
    
    <template #content>
      <div class="tw-p-6">
        <Message v-if="errors.schedules" severity="error" class="tw-mb-6">
          {{ errors.schedules }}
        </Message>
        
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6">
          <Card 
            v-for="day in daysOfWeek" 
            :key="day" 
            class="tw-shadow-md tw-border tw-border-gray-200 tw-transition-all tw-duration-300 hover:tw-shadow-lg hover:tw-scale-[1.02]"
          >
            <template #header>
              <div class="tw-bg-gradient-to-r tw-from-gray-100 tw-to-gray-200 tw-p-4 tw-rounded-t-lg">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-m-0 tw-flex tw-items-center">
                    <i class="pi pi-calendar-clock tw-mr-2 tw-text-blue-500"></i>
                    {{ day }}
                  </h3>
                  <div class="tw-flex tw-space-x-2">
                    <Badge 
                      v-if="schedules[day].morning.isActive" 
                      value="AM" 
                      severity="warning" 
                      class="tw-text-xs"
                    />
                    <Badge 
                      v-if="schedules[day].afternoon.isActive" 
                      value="PM" 
                      severity="info" 
                      class="tw-text-xs"
                    />
                  </div>
                </div>
              </div>
            </template>
            
            <template #content>
              <div class="tw-p-4 tw-space-y-6">
                <!-- Morning Shift -->
                <div class="tw-bg-gradient-to-r tw-from-amber-50 tw-to-orange-50 tw-p-4 tw-rounded-xl tw-border tw-border-amber-200">
                  <div class="tw-flex tw-items-center tw-mb-4">
                    <Checkbox 
                      :inputId="'morning-' + day"
                      binary="true"
                      v-model="schedules[day].morning.isActive"
                      class="tw-mr-3"
                    />
                    <label :for="'morning-' + day" class="tw-flex tw-items-center tw-text-sm tw-font-medium tw-text-gray-700 tw-cursor-pointer">
                      <i class="pi pi-sun tw-mr-2 tw-text-amber-500"></i>
                      Morning Shift
                    </label>
                  </div>
                  
                  <div v-if="schedules[day].morning.isActive" class="tw-space-y-4 tw-ml-6">
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                      <div class="tw-space-y-2">
                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                          <i class="pi pi-clock tw-mr-2 tw-text-green-500"></i>
                          Start Time
                        </label>
                        <input
                          type="time"
                          class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                          :value="schedules[day].morning.startTime"
                          @input="(e) => handleTimeChange(day, 'morning', 'startTime', e)"
                        />
                      </div>
                      <div class="tw-space-y-2">
                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                          <i class="pi pi-clock tw-mr-2 tw-text-red-500"></i>
                          End Time
                        </label>
                        <input
                          type="time"
                          class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                          :value="schedules[day].morning.endTime"
                          @input="(e) => handleTimeChange(day, 'morning', 'endTime', e)"
                        />
                      </div>
                    </div>
                    <div v-if="!patients_based_on_time" class="tw-space-y-2">
                      <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                        <i class="pi pi-users tw-mr-2 tw-text-purple-500"></i>
                        Patients Per Day (Morning)
                      </label>
                      <InputNumber
                        v-model="schedules[day].morning.patients_per_day"
                        @update:modelValue="(value) => handlePatientsChange(day, 'morning', { target: { value } })"
                        :min="0"
                        :disabled="props.patients_based_on_time"
                        class="tw-w-full"
                        inputClass="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                        :showButtons="true"
                        buttonLayout="horizontal"
                      />
                    </div>
                  </div>
                </div>
                
                <!-- Afternoon Shift -->
                <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-4 tw-rounded-xl tw-border tw-border-blue-200">
                  <div class="tw-flex tw-items-center tw-mb-4">
                    <Checkbox 
                      :inputId="'afternoon-' + day"
                       binary="true"
                      v-model="schedules[day].afternoon.isActive"
                      class="tw-mr-3"
                    />
                    <label :for="'afternoon-' + day" class="tw-flex tw-items-center tw-text-sm tw-font-medium tw-text-gray-700 tw-cursor-pointer">
                      <i class="pi pi-moon tw-mr-2 tw-text-blue-500"></i>
                      Afternoon Shift
                    </label>
                  </div>
                  
                  <div v-if="schedules[day].afternoon.isActive" class="tw-space-y-4 tw-ml-6">
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                      <div class="tw-space-y-2">
                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                          <i class="pi pi-clock tw-mr-2 tw-text-green-500"></i>
                          Start Time
                        </label>
                        <input
                          type="time"
                          class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                          :value="schedules[day].afternoon.startTime"
                          @input="(e) => handleTimeChange(day, 'afternoon', 'startTime', e)"
                        />
                      </div>
                      <div class="tw-space-y-2">
                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                          <i class="pi pi-clock tw-mr-2 tw-text-red-500"></i>
                          End Time
                        </label>
                        <input
                          type="time"
                          class="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                          :value="schedules[day].afternoon.endTime"
                          @input="(e) => handleTimeChange(day, 'afternoon', 'endTime', e)"
                        />
                      </div>
                    </div>
                    <div v-if="!patients_based_on_time" class="tw-space-y-2">
                      <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                        <i class="pi pi-users tw-mr-2 tw-text-purple-500"></i>
                        Patients Per Day (Afternoon)
                      </label>
                      <InputNumber
                        v-model="schedules[day].afternoon.patients_per_day"
                        @update:modelValue="(value) => handlePatientsChange(day, 'afternoon', { target: { value } })"
                        :min="0"
                        :disabled="props.patients_based_on_time"
                        class="tw-w-full"
                        inputClass="tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg tw-focus:ring-2 tw-focus:ring-blue-500 tw-focus:border-transparent tw-transition-all tw-duration-200"
                        :showButtons="true"
                        buttonLayout="horizontal"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
/* Custom PrimeVue component overrides */
:deep(.p-card) {
  @apply transition-all tw-duration-300;
}

:deep(.p-card-header) {
  @apply p-0;
}

:deep(.p-card-content) {
  @apply p-0;
}

:deep(.p-checkbox .p-checkbox-box) {
  @apply border-2 tw-border-gray-300 tw-rounded-md tw-transition-all tw-duration-200;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
  @apply bg-blue-500 tw-border-blue-500;
}

:deep(.p-checkbox .p-checkbox-box:hover) {
  @apply border-blue-400;
}

:deep(.p-inputnumber-input) {
  @apply bg-white;
}

:deep(.p-inputnumber-button) {
  @apply bg-gray-100 tw-border-gray-300 hover:tw-bg-gray-200;
}

:deep(.p-message) {
  @apply rounded-lg tw-border-l-4;
}

:deep(.p-message.p-message-error) {
  @apply bg-red-50 tw-border-red-500 tw-text-red-700;
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
  @apply bg-gray-100;
}

::-webkit-scrollbar-thumb {
  @apply bg-gray-400 tw-rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-500;
}

/* Responsive grid adjustments */
@media (max-width: 768px) {
  :deep(.tw-grid-cols-1.md\:tw-grid-cols-2) {
    @apply grid-cols-1;
  }
}
</style>