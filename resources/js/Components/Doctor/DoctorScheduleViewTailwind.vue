<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStoreDoctor } from '../../stores/AuthDoctor';
import { storeToRefs } from 'pinia';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';

const router = useRouter();
const months = ref([]);
const schedules = ref([]);
const loading = ref(true);
const error = ref(null);
const route = useRoute();
const doctorName = ref("");
const excludedDates = ref([]);
const patients_based_on_time = ref("");

const doctor = ref([]);

const doctors = useAuthStoreDoctor(); // Initialize Pinia store
const doctorId = ref(null);

const monthNames = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];

const monthwork = async () => {
  try {
    const response = await axios.get(`/api/monthwork/${doctorId.value}`);
    months.value = response.data.map(month => ({
      ...month,
      month_name: monthNames[month.month - 1] // Convert month integer to name
    }));
  } catch (e) {
    console.error("Error fetching month data:", e);
    error.value = "Failed to fetch month data."; // Set an error message
  }
};

const fetchSchedules = async () => {
  try {
    const response = await axios.get(`/api/schedules/${doctorId.value}`, {
      params: {
        doctor_id: doctorId.value,
      },
    });

    schedules.value = response.data.schedules;
    doctorName.value = response.data.doctor_name;
    patients_based_on_time.value = response.data.patients_based_on_time;

    let formattedSchedules = [];
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    days.forEach(day => {
      const schedulesOfDay = schedules.value.filter(s => s.day_of_week === day);
      const morning = schedulesOfDay.find(s => s.shift_period === 'morning');
      const afternoon = schedulesOfDay.find(s => s.shift_period === 'afternoon');
      const date = schedulesOfDay.find(s => s.date)?.date || null;

      formattedSchedules.push({
        date: date,
        day_of_week: day.charAt(0).toUpperCase() + day.slice(1),
        morning_start_time: morning?.start_time.slice(0, -3) || '-',
        morning_end_time: morning?.end_time.slice(0, -3) || '-',
        afternoon_start_time: afternoon?.start_time.slice(0, -3) || '-',
        afternoon_end_time: afternoon?.end_time.slice(0, -3) || '-',
        number_of_patients_per_day:
          (morning?.number_of_patients_per_day || 0) +
          (patients_based_on_time.value ? (afternoon?.number_of_patients_per_day || 0) : 0)
      });
    });

    schedules.value = formattedSchedules;

  } catch (e) {
    console.error("Error fetching schedules:", e);
    error.value = "Failed to fetch schedules."; // Set user-friendly error
  } finally {
    loading.value = false;
  }
};

// Fetch excluded dates from the backend
const fetchExcludedDates = async () => {
  try {
    const response = await axios.get(`/api/excluded-dates/${doctorId.value}`);
    console.log(response.data);
    excludedDates.value = response.data.data.map(item => ({
      start_date: item.start_date,
      end_date: item.end_date || null // Handle cases where end_date is missing
    }));
  } catch (fetchError) {
    console.error('Error fetching excluded dates:', fetchError);
    // Don't override the main error state, just log the error
    excludedDates.value = []; // Set to empty array on error
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const goBack = () => {
  router.go(-1);
};

onMounted(async () => {
  try {
    // First, ensure doctor data is loaded
    await doctors.getDoctor();
    
    console.log('Doctor data loaded:', doctors.doctorData);
    
    // Then set the doctorId from the store
    if (doctors.doctorData && doctors.doctorData.id) {
      doctorId.value = doctors.doctorData.id;
      console.log('Doctor ID set to:', doctorId.value);
      
      // Now fetch the data with the correct doctorId
      await Promise.all([
        fetchSchedules(),
        monthwork(),
        fetchExcludedDates()
      ]);
    } else {
      console.error('Doctor data or ID is missing:', doctors.doctorData);
      error.value = "Unable to load doctor information";
      loading.value = false;
    }
  } catch (mountError) {
    console.error('Error in onMounted:', mountError);
    error.value = "Failed to initialize component";
    loading.value = false;
  }
});
</script>

<template>
  <div class="tw-w-full tw-mt-6 tw-px-4 tw-font-sans">
    <!-- Back Button -->
    <div class="tw-mb-6">
      <Button 
        @click="goBack" 
        icon="pi pi-arrow-left" 
        label="Back" 
        class="tw-bg-blue-600 hover:tw-bg-blue-700 tw-text-white tw-rounded-full tw-px-6 tw-py-2 tw-transition-all tw-duration-200 tw-shadow-md hover:tw-shadow-lg"
        severity="primary"
      />
    </div>

    <!-- Loading Spinner -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
      <ProgressSpinner class="tw-w-12 tw-h-12" strokeWidth="4" />
      <span class="tw-ml-3 tw-text-lg tw-text-gray-600">Loading doctor schedule...</span>
    </div>

    <!-- Error Message -->
    <div v-else-if="error" class="tw-mb-6">
      <Message severity="error" :closable="false" class="tw-w-full">
        <template #messageicon>
          <i class="pi pi-exclamation-triangle tw-mr-2"></i>
        </template>
        {{ error }}
      </Message>
    </div>

    <!-- Doctor Schedule and Data -->
    <div v-else class="tw-space-y-8">
      <!-- Doctor Header -->
      <div class="tw-text-center tw-mb-8">
        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-mb-2">
          Schedule for Dr. {{ doctorName }}
        </h1>
        <div class="tw-w-24 tw-h-1 tw-bg-blue-500 tw-mx-auto tw-rounded"></div>
      </div>

      <!-- Schedule Table -->
      <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl tw-overflow-hidden">
        <template #title>
          <div class="tw-flex tw-items-center tw-text-xl tw-font-semibold tw-text-gray-800">
            <i class="pi pi-calendar tw-mr-3 tw-text-blue-600"></i>
            Weekly Schedule
          </div>
        </template>
        <template #content>
          <div v-if="schedules.length > 0">
            <DataTable 
              :value="schedules" 
              class="tw-w-full" 
              :paginator="false"
              responsiveLayout="scroll"
              :rowHover="true"
              stripedRows
            >
              <Column field="day_of_week" header="Day of Week" class="tw-font-medium">
                <template #body="slotProps">
                  <div class="tw-flex tw-flex-col">
                    <span class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.day_of_week }}</span>
                    <span v-if="slotProps.data.date" class="tw-text-sm tw-text-gray-500">
                      {{ formatDate(slotProps.data.date) }}
                    </span>
                  </div>
                </template>
              </Column>
              <Column field="morning_start_time" header="Morning Start" class="tw-text-center">
                <template #body="slotProps">
                  <Badge 
                    :value="slotProps.data.morning_start_time" 
                    :severity="slotProps.data.morning_start_time !== '-' ? 'success' : 'secondary'"
                    class="tw-px-3 tw-py-1"
                  />
                </template>
              </Column>
              <Column field="morning_end_time" header="Morning End" class="tw-text-center">
                <template #body="slotProps">
                  <Badge 
                    :value="slotProps.data.morning_end_time" 
                    :severity="slotProps.data.morning_end_time !== '-' ? 'success' : 'secondary'"
                    class="tw-px-3 tw-py-1"
                  />
                </template>
              </Column>
              <Column field="afternoon_start_time" header="Afternoon Start" class="tw-text-center">
                <template #body="slotProps">
                  <Badge 
                    :value="slotProps.data.afternoon_start_time" 
                    :severity="slotProps.data.afternoon_start_time !== '-' ? 'info' : 'secondary'"
                    class="tw-px-3 tw-py-1"
                  />
                </template>
              </Column>
              <Column field="afternoon_end_time" header="Afternoon End" class="tw-text-center">
                <template #body="slotProps">
                  <Badge 
                    :value="slotProps.data.afternoon_end_time" 
                    :severity="slotProps.data.afternoon_end_time !== '-' ? 'info' : 'secondary'"
                    class="tw-px-3 tw-py-1"
                  />
                </template>
              </Column>
              <Column field="number_of_patients_per_day" header="Patients" class="tw-text-center">
                <template #body="slotProps">
                  <Chip 
                    :label="slotProps.data.number_of_patients_per_day.toString()" 
                    class="tw-bg-blue-100 tw-text-blue-800 tw-font-semibold"
                  />
                </template>
              </Column>
            </DataTable>
          </div>
          <div v-else class="tw-text-center tw-py-8">
            <Message severity="info" :closable="false">
              <template #messageicon>
                <i class="pi pi-info-circle tw-mr-2"></i>
              </template>
              No schedules found for this doctor.
            </Message>
          </div>
        </template>
      </Card>

      <!-- Available Months -->
      <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl">
        <template #title>
          <div class="tw-flex tw-items-center tw-text-xl tw-font-semibold tw-text-gray-800">
            <i class="pi pi-calendar-plus tw-mr-3 tw-text-green-600"></i>
            Available Months
          </div>
        </template>
        <template #content>
          <div v-if="months.length > 0" class="tw-grid tw-grid-cols-2 md:tw-grid-cols-3 lg:tw-grid-cols-4 xl:tw-grid-cols-6 tw-gap-4">
            <div 
              v-for="month in months" 
              :key="month.month" 
              class="tw-group"
            >
              <Card class="tw-h-full tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-300 tw-border-0 tw-bg-green-50 hover:tw-bg-green-100 tw-cursor-pointer group-hover:tw-scale-105">
                <template #content>
                  <div class="tw-text-center tw-py-4">
                    <div class="tw-text-lg tw-font-semibold tw-text-green-800">
                      {{ month.month_name }}
                    </div>
                    <div class="tw-text-2xl tw-font-bold tw-text-green-600 tw-mt-1">
                      {{ month.year }}
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </div>
          <div v-else class="tw-text-center tw-py-8">
            <Message severity="info" :closable="false">
              <template #messageicon>
                <i class="pi pi-info-circle tw-mr-2"></i>
              </template>
              No available months found.
            </Message>
          </div>
        </template>
      </Card>

      <!-- Excluded Dates / Days Off -->
      <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl">
        <template #title>
          <div class="tw-flex tw-items-center tw-text-xl tw-font-semibold tw-text-gray-800">
            <i class="pi pi-calendar-times tw-mr-3 tw-text-red-600"></i>
            Days Off
          </div>
        </template>
        <template #content>
          <div v-if="excludedDates.length > 0" class="tw-grid tw-grid-cols-2 md:tw-grid-cols-3 lg:tw-grid-cols-4 xl:tw-grid-cols-6 tw-gap-4">
            <div 
              v-for="(date, index) in excludedDates" 
              :key="index" 
              class="tw-group"
            >
              <Card class="tw-h-full tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-300 tw-border-0 tw-bg-red-50 hover:tw-bg-red-100 tw-cursor-pointer group-hover:tw-scale-105">
                <template #content>
                  <div class="tw-text-center tw-py-4">
                    <div class="tw-text-sm tw-font-medium tw-text-red-800">
                      {{ formatDate(date.start_date) }}
                    </div>
                    <div v-if="date.end_date" class="tw-text-xs tw-text-red-600 tw-mt-1">
                      to {{ formatDate(date.end_date) }}
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </div>
          <div v-else class="tw-text-center tw-py-8">
            <Message severity="info" :closable="false">
              <template #messageicon>
                <i class="pi pi-info-circle tw-mr-2"></i>
              </template>
              No dates are currently excluded.
            </Message>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for enhanced visual appeal */
.p-card {
  transition: all 0.3s ease;
}

.p-card:hover {
  transform: translateY(-2px);
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f8fafc;
}

.p-badge {
  font-weight: 600;
}

.p-chip {
  font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>