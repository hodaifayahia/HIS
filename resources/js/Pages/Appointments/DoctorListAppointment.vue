<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const doctors = ref([]);
const searchQuery = ref('');
const isLoading = ref(false);
const loadingAppointments = ref({});
const route = useRoute();
const router = useRouter();
const specializationId = route.params.id;

const availableAppointments = ref({});

// Computed properties for stats
const availableDoctorsCount = computed(() => {
  return doctors.value.filter(doctor => {
    const appointments = availableAppointments.value[doctor.id];
    return appointments && (
      appointments.normal_appointments?.date || 
      appointments.canceled_appointments?.length > 0
    );
  }).length;
});

const totalSlotsToday = computed(() => {
  let total = 0;
  doctors.value.forEach(doctor => {
    const appointments = availableAppointments.value[doctor.id];
    if (appointments) {
      if (appointments.normal_appointments?.available_times) {
        total += appointments.normal_appointments.available_times.length;
      }
      if (appointments.canceled_appointments) {
        total += appointments.canceled_appointments.length;
      }
    }
  });
  return total;
});

const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/doctors/search', {
          params: { query: searchQuery.value },
        });
        doctors.value = response.data.data;
      } catch (error) {
        console.error('Error searching doctors:', error);
      } finally {
        isLoading.value = false;
      }
    }, 200);
  };
})();

watch(searchQuery, debouncedSearch);

const getDoctors = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get(`/api/doctors/specializations/${specializationId}`, {
      params: { query: specializationId },
    });
    doctors.value = response.data.data;

    await Promise.all(doctors.value.map(doctor => fetchAvailableAppointments(doctor.id)));
  } catch (error) {
    console.error('Error fetching doctors:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchAvailableAppointments = async (doctorId) => {
  try {
    loadingAppointments.value[doctorId] = true;
    const response = await axios.get('/api/appointments/available', {
      params: { doctor_id: doctorId }
    });
    availableAppointments.value[doctorId] = {
      canceled_appointments: response.data.canceled_appointments,
      normal_appointments: response.data.normal_appointments
    };
  } catch (error) {
    console.error(`Error fetching available appointments for doctor ${doctorId}:`, error);
  } finally {
    loadingAppointments.value[doctorId] = false;
  }
};

const formatClosestCanceledAppointment = (appointments) => {
  if (!appointments || appointments.length === 0) return 'No upcoming canceled appointments';

  const sortedAppointments = appointments.sort((a, b) => {
    const dateA = new Date(a.date + 'T' + a.available_times[0] + ':00');
    const dateB = new Date(b.date + 'T' + b.available_times[0] + ':00');
    return dateA - dateB;
  });

  const closest = sortedAppointments[0];
  return `${closest.date} at ${closest.available_times[0]}`;
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

// New methods for enhanced functionality
const clearSearch = () => {
  searchQuery.value = '';
};

const refreshData = async () => {
  await getDoctors();
};

const handleImageError = (event) => {
  event.target.src = '/images/default-doctor.png';
  console.warn('Doctor image failed to load, using default');
};

const getDoctorInitials = (name) => {
  if (!name) return 'DR';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return parts[0].substring(0, 2).toUpperCase();
};

onMounted(() => {
  getDoctors();
});
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-user-md tw-text-white tw-text-2xl"></i>
              </div>
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-400 tw-rounded-full tw-border-2 tw-border-white tw-animate-pulse"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
                Doctors
              </h1>
              <p class="tw-text-slate-600 tw-text-sm tw-mt-1 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Select a doctor for your appointment
              </p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-users tw-text-blue-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-blue-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-blue-700 tw-font-medium tw-uppercase tw-tracking-wide">Total</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">
                    {{ doctors.length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-green-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-calendar-check tw-text-green-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-green-700 tw-font-medium tw-uppercase tw-tracking-wide">Available</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">
                    {{ availableDoctorsCount }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-amber-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-clock tw-text-amber-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-amber-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-amber-700 tw-font-medium tw-uppercase tw-tracking-wide">Slots Today</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-amber-800">
                    {{ totalSlotsToday }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <div class="tw-max-w-7xl tw-mx-auto">
        <!-- Enhanced Action Toolbar -->
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-flex-1">
            <!-- Search with Enhanced Design -->
            <div class="tw-relative tw-flex-1 tw-min-w-[250px] tw-max-w-[400px]">
              <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-4 tw-flex tw-items-center tw-pointer-events-none">
                <i class="pi pi-search tw-text-slate-400 tw-text-lg"></i>
              </div>
              <InputText
                v-model="searchQuery"
                placeholder="Search doctors by name..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all tw-duration-200 tw-bg-slate-50/50 hover:tw-bg-white"
              />
              <div v-if="searchQuery" class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center">
                <Button
                  @click="clearSearch"
                  icon="pi pi-times"
                  class="p-button-text p-button-sm p-button-rounded tw-text-slate-400 hover:tw-text-slate-600"
                  v-tooltip.top="'Clear search'"
                />
              </div>
            </div>

            <!-- Back Button -->
            <Button 
              @click="router.go(-1)"
              icon="pi pi-arrow-left"
              label="Back"
              class="p-button-outlined p-button-secondary tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
            />
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button 
              @click="refreshData"
              icon="pi pi-refresh"
              class="p-button-outlined p-button-secondary p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
              v-tooltip.top="'Refresh data'"
              :loading="isLoading"
            />
          </div>
        </div>
      </div>

      <!-- Doctors Grid -->
      <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
        <div v-for="doctor in doctors" 
             :key="doctor.id"
             class="tw-bg-white tw-border tw-border-slate-200 tw-rounded-2xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 hover:tw-transform hover:tw-scale-105 tw-cursor-pointer tw-overflow-hidden tw-flex tw-flex-col tw-h-full"
             @click="goToAppointmentPage(doctor)">
          
          <!-- Doctor Avatar Container -->
          <div class="tw-relative tw-overflow-hidden tw-bg-gradient-to-br tw-from-blue-100 tw-to-indigo-100 tw-h-48 tw-flex tw-items-center tw-justify-center">
            <div class="tw-relative">
              <div class="tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-slate-400 tw-to-slate-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-3xl tw-shadow-lg tw-overflow-hidden tw-transition-all tw-duration-300">
                <!-- Loading skeleton -->
                <div v-if="!doctor.avatar && !doctor.name" class="tw-w-full tw-h-full tw-bg-gradient-to-r tw-from-slate-300 tw-to-slate-400 tw-animate-pulse tw-rounded-full"></div>
                
                <!-- Doctor Image -->
                <img 
                  v-if="doctor.avatar"
                  :src="doctor.avatar" 
                  :alt="doctor.name"
                  class="tw-w-full tw-h-full tw-object-contain tw-rounded-full tw-transition-all tw-duration-500 hover:tw-scale-105"
                  @error="handleImageError"
                  @load="$event.target.style.opacity = '1'"
                  style="opacity: 0;"
                />
                
                <!-- Fallback Initials -->
                <div v-else class="tw-w-full tw-h-full tw-flex tw-items-center tw-justify-center tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-full tw-text-white tw-font-bold tw-text-xl">
                  {{ getDoctorInitials(doctor.name) }}
                </div>
              </div>
              <div class="tw-absolute -tw-bottom-1 -tw-right-1 tw-w-5 tw-h-5 tw-bg-green-400 tw-rounded-full tw-border-2 tw-border-white tw-flex tw-items-center tw-justify-center tw-shadow-md">
                <i class="pi pi-check tw-text-white tw-text-xs"></i>
              </div>
            </div>
          </div>

          <!-- Content Container -->
          <div class="tw-p-4 tw-flex-grow tw-flex tw-flex-col">
            <h3 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-2 tw-line-clamp-2">
              {{ doctor.name }}
            </h3>

            <div class="tw-mb-4 tw-flex-grow">
              <p class="tw-text-sm tw-text-slate-600 tw-line-clamp-2">
                {{ doctor.specialization?.name || 'General' }} Specialist
              </p>
            </div>

            <!-- Appointment Info -->
            <div class="tw-space-y-2 tw-mb-4">
              <!-- Next Available Appointment -->
              <div v-if="availableAppointments[doctor.id]" class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-md tw-p-2">
                <div class="tw-flex tw-items-center tw-gap-1 tw-mb-1">
                  <i class="pi pi-calendar-check tw-text-blue-600 tw-text-sm"></i>
                  <span class="tw-text-sm tw-text-blue-700 tw-font-medium">Next</span>
                </div>
                <p class="tw-text-sm tw-text-blue-900 tw-font-semibold">
                  {{ availableAppointments[doctor.id].normal_appointments &&
                    availableAppointments[doctor.id].normal_appointments.date ?
                    availableAppointments[doctor.id].normal_appointments.date + ' at ' +
                    availableAppointments[doctor.id].normal_appointments.available_times[0] :
                    'No upcoming' }}
                </p>
              </div>

              <!-- Soonest Available Slot -->
              <div v-if="availableAppointments[doctor.id]" class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-md tw-p-2">
                <div class="tw-flex tw-items-center tw-gap-1 tw-mb-1">
                  <i class="pi pi-clock tw-text-amber-600 tw-text-sm"></i>
                  <span class="tw-text-sm tw-text-amber-700 tw-font-medium">Soonest</span>
                </div>
                <p class="tw-text-sm tw-text-amber-900 tw-font-semibold">
                  {{ formatClosestCanceledAppointment(availableAppointments[doctor.id].canceled_appointments) }}
                </p>
              </div>
            </div>

            <!-- Info Badge and Action -->
            <div class="tw-flex tw-items-center tw-gap-2 tw-pt-4 tw-border-t tw-border-slate-200/60 tw-mt-auto">
              <div class="tw-flex-1">
                <span class="tw-inline-flex tw-items-center tw-gap-2 tw-px-3 tw-py-1.5 tw-bg-gradient-to-r tw-from-blue-100 tw-to-blue-50 tw-text-blue-700 tw-text-xs tw-font-semibold tw-rounded-full tw-border tw-border-blue-200/50">
                  <i class="pi pi-user-md"></i>Available
                </span>
              </div>
              <div class="tw-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-full tw-text-white tw-shadow-lg tw-transition-all hover:tw-scale-110">
                <i class="pi pi-calendar-plus tw-text-sm tw-font-bold"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="doctors.length === 0 && !isLoading" class="tw-text-center tw-py-12">
        <div class="tw-mx-auto tw-w-24 tw-h-24 tw-bg-slate-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mb-4">
          <i class="pi pi-user-md tw-text-4xl tw-text-slate-400"></i>
        </div>
        <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">No doctors found</h3>
        <p class="tw-text-slate-500 tw-mb-4">Try adjusting your search criteria</p>
        <Button @click="clearSearch" icon="pi pi-refresh" label="Clear Search" class="p-button-primary" />
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
        <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
          <div class="tw-animate-spin tw-rounded-full tw-h-8 tw-w-8 tw-border-b-2 tw-border-blue-600"></div>
          <p class="tw-text-slate-600">Loading doctors...</p>
        </div>
        </div>
      </div>
    </div>

    <!-- Floating Action Button -->
    <button 
      @click="router.go(-1)"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Go Back'"
    >
      <i class="pi pi-arrow-left tw-text-xl"></i>
    </button>
  </div>
</template>

<style scoped>
/* Enhanced Animations and Effects */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-10px); }
  to { opacity: 1; transform: translateX(0); }
}

.tw-animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.tw-animate-slide-in {
  animation: slideIn 0.2s ease-out;
}

/* Enhanced card hover effects */
.card-hover {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Loading skeleton */
.skeleton {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Floating Action Button */
.fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.39);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  transition: all 0.3s ease;
  z-index: 1000;
}

.fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

.fab:active {
  transform: scale(0.95);
}

/* Enhanced status indicators */
.status-indicator {
  position: relative;
  display: inline-block;
}

.status-indicator::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: inherit;
  background: inherit;
  opacity: 0.2;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 0.2; }
  50% { transform: scale(1.05); opacity: 0.3; }
  100% { transform: scale(1); opacity: 0.2; }
}

/* Doctor card specific styles */
.doctor-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.doctor-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
}

/* Avatar container */
.avatar-container {
  position: relative;
  display: inline-block;
}

.avatar-container img {
  transition: transform 0.3s ease, opacity 0.5s ease;
}

.avatar-container:hover img {
  transform: scale(1.05);
}

/* Doctor avatar specific styles */
.doctor-avatar {
  position: relative;
  overflow: hidden;
  border-radius: 50%;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.doctor-avatar img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50%;
}

.doctor-avatar:hover {
  transform: scale(1.02);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.doctor-avatar:hover img {
  transform: scale(1.05);
}

/* Image loading animation */
@keyframes imageFadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.doctor-avatar img {
  animation: imageFadeIn 0.5s ease-out;
}

/* Initials fallback styling */
.doctor-initials {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.25rem;
  color: white;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* Appointment info boxes */
.appointment-info {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05));
  border: 1px solid rgba(59, 130, 246, 0.2);
  transition: all 0.2s ease;
}

.appointment-info:hover {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.1));
  border-color: rgba(59, 130, 246, 0.3);
}

.available-slot {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
  border: 1px solid rgba(245, 158, 11, 0.2);
  transition: all 0.2s ease;
}

.available-slot:hover {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.1));
  border-color: rgba(245, 158, 11, 0.3);
}

/* Enhanced button styles */
.book-button {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: none;
  transition: all 0.3s ease;
}

.book-button:hover {
  background: linear-gradient(135deg, #2563eb, #1e40af);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* Line clamp utility fallback */
.tw-line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Smooth animations */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Button hover effects */
:deep(.p-button) {
  transition: all 0.2s ease-in-out;
}

:deep(.p-button:hover) {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
}

/* Card elevation on hover */
.tw-group {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tw-group:active {
  transform: scale(0.98);
}

/* Ensure full height cards in grid */
.tw-grid > div {
  display: flex;
  flex-direction: column;
}
</style>