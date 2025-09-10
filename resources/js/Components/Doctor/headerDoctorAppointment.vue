<script setup>
import { onMounted, watch ,ref ,computed } from 'vue'; // Removed 'ref' for `loading`
import { useRouter } from 'vue-router';
import { useToastr } from '../../Components/toster'; // Assuming this is your toastr utility
import axios from 'axios'; // Keep axios here as fetchCurrentDoctorInfo is local

// Import YOUR existing Appointment Store
import { useAppointmentStore } from '@/stores/appointments'; // Make sure this path is correct!
import { storeToRefs } from 'pinia'; // Helper to destructure reactive state from store

const router = useRouter();
const toaster = useToastr();

// Define props for the component
const props = defineProps({
  doctorId: {
    type: [String, Number],
    required: true
  },
  isDcotro: { // Renamed from isDcotro to match your template usage
    type: Boolean,
    default: true
  },
});

// Initialize the Pinia store
const appointmentStore = useAppointmentStore();

// Destructure reactive state properties and getters from the store using storeToRefs
// These are from the store and will reflect the loading state of appointments within the store
const {
  availableAppointments,      // This is the object containing all doctors' available appointments
  loadingAppointments,        // This is the object containing loading status for each doctor's appointments
} = storeToRefs(appointmentStore);

// Destructure actions and the specific getter from the store
const {
  fetchAvailableAppointments, // Action to fetch specific doctor's appointments
  formatClosestCanceledAppointment, // Getter from the store for formatting
} = appointmentStore;

// Local ref for the current doctor's info (fetched separately as a single item)
const currentDoctor = ref(null);
const isLoadingDoctorInfo = ref(false); // This local loading ref is for the *currentDoctor* data fetch

// --- Component Methods ---

// Fetch the specific doctor's main information
const fetchCurrentDoctorInfo = async (doctorId) => {
  if (!doctorId) {
    console.warn('Doctor ID is missing for fetchCurrentDoctorInfo.');
    return;
  }
  isLoadingDoctorInfo.value = true; // Set local loading for doctor info
  try {
    const response = await axios.get(`/api/doctors/${doctorId}`);
    currentDoctor.value = response.data.data;
  } catch (error) {
    console.error('Error fetching current doctor info:', error);
    toaster.error('Failed to fetch doctor information.');
  } finally {
    isLoadingDoctorInfo.value = false; // Stop local loading
  }
};

// --- Component Lifecycle & Watchers ---

// Watch for changes in the doctorId prop to refetch all relevant data
watch(() => props.doctorId, async (newDoctorId) => {
  if (newDoctorId) {
    // Fetch the specific doctor's main info
    await fetchCurrentDoctorInfo(newDoctorId);

    // Fetch the available appointments for this specific doctor using the store action
    await fetchAvailableAppointments(newDoctorId);

    // No need to check for errors from the store action here if you don't have a specific
    // error state for individual doctor's appointments in the store's `loadingAppointments` object.
    // Your store's `fetchAvailableAppointments` currently only logs errors.
  }
}, { immediate: true }); // `immediate: true` runs the watcher on component mount

// A computed property to get the appointments specific to the current doctor
const currentDoctorAppointments = computed(() => {
  // Use .value on availableAppointments because it's a ref from storeToRefs
  return availableAppointments.value[props.doctorId] || { canceled_appointments: [], normal_appointments: {} };
});

// A computed property to get the loading status specific to the current doctor's appointments
const currentDoctorAppointmentsLoading = computed(() => {
  // Use .value on loadingAppointments because it's a ref from storeToRefs
  return loadingAppointments.value[props.doctorId] || false;
});

// A computed property for the formatted closest canceled appointment for the *current* doctor
const formattedCanceledAppointments = computed(() => {
  return formatClosestCanceledAppointment(currentDoctorAppointments.value.canceled_appointments);
});
</script>

<template>
  <div class="header p-4 rounded-lg d-flex flex-column position-relative bg-primary">
    <button v-if="!isDcotro" class="btn btn-light bg-primary rounded-pill shadow-sm position-absolute"
      style="top: 20px; left: 20px;" @click="router.go(-1)">
      <i class="fas fa-arrow-left"></i> Back
    </button>

    <div class="d-flex align-items-center justify-content-between mt-5">
      <div class="d-flex align-items-center">
        <div class="mx-auto rounded-circle overflow-hidden border" style="width: 150px; height: 150px;">
          <img v-if="!isLoadingDoctorInfo && currentDoctor && currentDoctor.avatar" :src="currentDoctor.avatar" alt="Doctor image" class="w-100 h-100"
            style="object-fit: contain; border-radius: 50%;" />
          <div v-else class="w-100 h-100 bg-gray-300 animate-pulse rounded-circle"></div>
        </div>
        <div class="ml-4">
          <h2 v-if="!isLoadingDoctorInfo && currentDoctor" class="h4 font-weight-bold text-white mb-2">{{ currentDoctor.name }}</h2>
          <p v-if="!isLoadingDoctorInfo && currentDoctor" class="mb-1 text-white font-weight-bold">{{ currentDoctor.specialization }}
            <span class="font-weight-bold">{{ currentDoctor.credentials }}</span>
          </p>
          <p v-if="!isLoadingDoctorInfo && currentDoctor" class="mb-0 text-white-50">
            <span class="font-weight-bold"><i class="fas fa-phone"></i> {{ currentDoctor.phone }}</span>
          </p>
          <div v-if="isLoadingDoctorInfo">
            <div class="h4 bg-gray-300 animate-pulse rounded mb-2" style="width: 150px; height: 24px;"></div>
            <div class="bg-gray-300 animate-pulse rounded mb-1" style="width: 200px; height: 16px;"></div>
            <div class="bg-gray-300 animate-pulse rounded" style="width: 100px; height: 16px;"></div>
          </div>
        </div>
      </div>

      <div class="text-right">
        <div class="mb-4">
          <p class="mb-1 small text-white-50">Next Appointment:</p>
          <p v-if="!currentDoctorAppointmentsLoading && currentDoctorAppointments.normal_appointments" class="h5 font-weight-bold text-white mb-2">
            {{ currentDoctorAppointments.normal_appointments.date + ' at ' +
               currentDoctorAppointments.normal_appointments.available_times[0] }}
          </p>
          <p v-else-if="!currentDoctorAppointmentsLoading && !currentDoctorAppointments.normal_appointments" class="h5 font-weight-bold text-white mb-2">
            No upcoming appointments
          </p>
          <div v-else class="h5 bg-gray-300 animate-pulse rounded mb-2" style="width: 200px; height: 24px;"></div>
        </div>
        <div>
          <p class="mb-1 small text-white-50">Closest Appointments:</p>
          <p v-if="!currentDoctorAppointmentsLoading" class="h5 font-weight-bold text-white mb-2">
            {{ formattedCanceledAppointments }}
          </p>
          <div v-else class="h5 bg-gray-300 animate-pulse rounded mb-2" style="width: 250px; height: 24px;"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Add this style for the pulse animation */
@keyframes pulse {
  0% {
    background-color: rgba(165, 165, 165, 0.1);
  }

  50% {
    background-color: rgba(165, 165, 165, 0.3);
  }

  100% {
    background-color: rgba(165, 165, 165, 0.1);
  }
}

.animate-pulse {
  animation: pulse 1.5s infinite;
}
</style>