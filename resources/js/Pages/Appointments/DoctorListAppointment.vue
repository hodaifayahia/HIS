<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppointmentStore } from '@/stores/appointments'; // Import your Pinia store
import { storeToRefs } from 'pinia'; // Helper to destructure reactive properties from store

const route = useRoute();
const router = useRouter();
const specializationId = ref(route.params.id); // Use ref for reactivity if param can change

// --- Pinia Store Integration ---
const appointmentStore = useAppointmentStore();

// Destructure reactive state properties from the store
// Using storeToRefs() ensures reactivity is maintained for state properties
const {
  doctors,
  isLoadingDoctors,
  availableAppointments,
  loadingAppointments,
  searchQuery // If searchQuery is also managed by the store
} = storeToRefs(appointmentStore);

// Destructure actions directly (they don't lose reactivity)
const {
  fetchDoctors,
  searchDoctors,
  fetchAvailableAppointments, // Will be called internally by fetchDoctors/searchDoctors if doctors are loaded.
  formatClosestCanceledAppointment // Use the getter from the store
} = appointmentStore;

// --- Component Logic ---

// Debounced search logic, now calling the store action
const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      searchDoctors(searchQuery.value); // Call the store action
    }, 200);
  };
})();

// Watch for changes in the local searchQuery ref, which is bound to the input
watch(searchQuery, debouncedSearch);

// Fetch doctors and their appointments on component mount
onMounted(() => {
  // Pass specializationId to the store action
  fetchDoctors(specializationId.value);
});

// Computed property to check if a specific doctor's appointments are loading
const getDoctorLoadingStatus = (doctorId) => {
  return computed(() => loadingAppointments.value[doctorId] || false);
};

const goToAppointmentPage = (doctor) => {
  router.push({
    name: 'admin.appointments',
    params: {
      id: doctor.id,
      specializationId: doctor.specialization_id
    },
  });
};
</script>

<template>
  <div>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Doctors</h1>
          </div>
          <div class="col-sm-12">
            <button class="float-left btn btn-ligh bg-primary rounded-pill" @click="router.go(-1)">
              <i class="fas fa-arrow-left"></i> Back
            </button>
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Doctors</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container">
        <h2 class="text-center mb-4">Doctor Management</h2>
        <div class="mb-4">
          <div class="mb-1 search-container">
            <div class="search-wrapper">
              <input
                v-model="searchQuery"
                type="text"
                class="premium-search"
                placeholder="Search doctors..."
              />
              <button class="search-button">
                <i class="fas fa-search"></i> Search
              </button>
            </div>
          </div>
        </div>
        <div class="row">
          <div v-for="doctor in doctors" :key="doctor.id" class="col-md-3 mb-4 d-flex justify-content-center">
            <div
              class="card text-center shadow-lg"
              :class="{ 'loading': getDoctorLoadingStatus(doctor.id).value }"
              style="width: 100%; max-width: 250px; border-radius: 15px;"
              @click="goToAppointmentPage(doctor)"
            >
              <div class="p-3">
                <div class="mx-auto rounded-circle overflow-hidden border" style="width: 150px; height: 150px;">
                  <img
                    :src="doctor.avatar"
                    alt="Doctor image"
                    class="w-100 h-100"
                    style="object-fit: contain; border-radius: 50%;"
                  />
                </div>
              </div>
              <div class="card-body bg-light text-center p-3">
                <h4 class="fw-bold text-dark mb-2">{{ doctor.name }}</h4>

                <div v-if="availableAppointments[doctor.id]" class="mb-2 p-2 rounded bg-white shadow-sm">
                  <p class="card-text text-success fw-bold mb-1">
                    <i class="bi bi-calendar-check"></i> Next Appointment:
                  </p>
                  <p class="text-dark mb-0">
                    {{ availableAppointments[doctor.id].normal_appointments &&
                       availableAppointments[doctor.id].normal_appointments.date ?
                       availableAppointments[doctor.id].normal_appointments.date + ' at ' +
                       availableAppointments[doctor.id].normal_appointments.available_times[0] :
                       'No upcoming appointments' }}
                  </p>
                </div>

                <div v-if="availableAppointments[doctor.id]" class="p-2 rounded bg-white shadow-sm">
                  <p class="card-text text-warning fw-bold mb-1">
                    <i class="bi bi-clock"></i> Soonest Available Slot:
                  </p>
                  <p class="text-dark mb-0">
                    {{ formatClosestCanceledAppointment(availableAppointments[doctor.id].canceled_appointments) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="doctors.length === 0 && !isLoadingDoctors" class="text-center mt-4">
          No Results Found...
        </div>
        <div v-if="isLoadingDoctors" class="text-center mt-4">
          Loading doctors...
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Your existing styles */
.card {
  transition: transform 0.2s ease-in-out, filter 0.2s ease-in-out;
}

.card:hover {
  transform: scale(1.05);
}

.card.loading {
  filter: blur(2px);
  pointer-events: none; /* Disable clicks while loading */
}

.card-title {
  font-size: 1rem;
}

.card-text {
  font-size: 0.875rem;
}

.search-container {
  width: 100%;
  position: relative;
}

.search-wrapper {
  display: flex;
  align-items: center;
  border: 2px solid #007BFF;
  border-radius: 50px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.premium-search {
  border: none;
  border-radius: 50px 0 0 50px;
  flex-grow: 1;
  padding: 10px 15px;
  font-size: 16px;
  outline: none;
}

.premium-search:focus {
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

.search-button {
  border: none;
  background: #007BFF;
  color: white;
  padding: 10px 20px;
  border-radius: 0 50px 50px 0;
  cursor: pointer;
  font-size: 16px;
  transition: background 0.3s ease;
}

.search-button:hover {
  background: #0056b3;
}

.search-button i {
  margin-right: 5px;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
  }

  70% {
    box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
  }
}

.search-wrapper:focus-within {
  animation: pulse 1s;
}
</style>