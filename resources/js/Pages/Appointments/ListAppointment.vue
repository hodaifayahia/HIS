<script setup>
import { ref, onMounted, watch, reactive, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// axios is still used inside the store, no need to import here unless directly used
import AppointmentListItem from './AppointmentListItem.vue';
import headerDoctorAppointment from '@/Components/Doctor/headerDoctorAppointment.vue';
import PrimeWaitlistModal from '@/Components/Doctor/PrimeWaitlistModal.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useAuthStore } from '../../stores/auth'; // Assuming this store exists
import { useAppointmentStore } from '../../stores/AppointmentStata'; // Your updated store
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue'; // Import the modal
import { storeToRefs } from 'pinia';

// Local refs for UI state (not shared globally)
const selectedWaitlist = ref(null);
const showAddModal = ref(false);
const fileInput = ref(null); // Keep this local as it's a direct DOM reference

// Reactive variables for route params
const route = useRoute();
const router = useRouter();
const doctorId = ref(route.params.id); // Make reactive if route params can change
const specializationId = ref(route.params.specializationId); // Make reactive

// Pinia Store Instances
const appointmentStore = useAppointmentStore();
const authStore = useAuthStore();

// Destructure state from appointmentStore using storeToRefs to maintain reactivity
const {
  appointments,
  pagination,
  loading,
  error,
  currentFilter,
  todaysAppointmentsCount,
  countWithDoctor,
  countWithoutDoctor,
  statuses,
  uploadProgress,
  currentFileIndex,
  selectedFiles,
  uploadResults,
} = storeToRefs(appointmentStore);

// Destructure actions from appointmentStore
const {
  getAppointments,
  getTodaysAppointments,
  getAppointmentsStatus,
  fetchWaitlists,
  handleFileSelection: handleFileSelectionStore, // Rename to avoid conflict with local handler
  removeFile: removeFileStore,
  uploadFiles: uploadFilesStore, // Rename to avoid conflict with local handler
  exportAppointments,
  handleSearchResults,
  resetStore, // To clean up state if needed
} = appointmentStore;

// Destructure user role from authStore (assuming authStore.user is reactive)
const { user } = storeToRefs(authStore);
const userRole = computed(() => user.value?.role); // Safely get user role

// Local UI state for waitlist modals (these don't necessarily need to be in Pinia)
const NotForYou = ref(false);
const WaitlistDcotro = ref(false);

// --- Component Methods ---

// Track initialization state
const isInitialized = ref(false);

// Combined initialization logic
const initializeComponent = async () => {
  // Prevent multiple initializations
  if (loading.value || isInitialized.value) {
    return;
  }

  try {
    loading.value = true;
    
    // Only fetch user data if not already loaded
    if (!user.value) {
      await authStore.getUser();
    }

    // Fetch all necessary data in parallel if we have valid IDs
    if (doctorId.value) {
      await Promise.all([
        getAppointments(doctorId.value, 1, currentFilter.value),
        getAppointmentsStatus(doctorId.value),
        specializationId.value ? 
          fetchWaitlists(doctorId.value, specializationId.value, NotForYou.value) : 
          Promise.resolve()
      ]);
      
      isInitialized.value = true;
    }
  } catch (error) {
    console.error('Error initializing component:', error);
  } finally {
    loading.value = false;
  }
};

// Handle file selection (calls store action)
const handleFileSelection = (event) => {
  handleFileSelectionStore(event.target.files);
};

// Handle removing file (calls store action)
const removeFile = (index) => {
  removeFileStore(index);
};

// Handle file upload (calls store action)
const uploadFiles = async () => {
  await uploadFilesStore(doctorId.value);
  showUploadResults(); // Call local function to show results
};

// Function to display upload results (can use a notification library like `vue-toastification` or `sweetalert2`)
const showUploadResults = () => {
  let message = '';
  if (uploadResults.value.success.length) {
    message += `Successfully processed ${uploadResults.value.success.length} files.\n`;
  }
  if (uploadResults.value.errors.length) {
    message += `\nFailed to process ${uploadResults.value.errors.length} files:\n`;
    uploadResults.value.errors.forEach(error => {
      message += `${error.filename}: ${error.error}\n`;
    });
  }

  // Example using a hypothetical `notify` function (replace with your actual notification system)
  if (typeof notify !== 'undefined') { // Check if notify function exists globally
      notify({
          title: 'Import Results',
          message: message,
          type: uploadResults.value.errors.length ? 'warning' : 'success'
      });
  } else {
      alert(message); // Fallback to alert if no notification system
  }
};

// Update the status filter handler (calls store action)
const handleStatusFilter = (statusValue) => {
  getAppointments(doctorId.value, 1, statusValue); // Reset to page 1
};

// Update other methods to use store actions
const handleFilterByDate = (date) => {
  getAppointments(doctorId.value, 1, currentFilter.value, date);
};

// Navigate to create appointment page
const goToAddAppointmentPage = () => {
  router.push({
    name: 'admin.appointments.create',
    params: { doctorId: doctorId.value }
  });
};

// Open/Close Waitlist Modals
const openWaitlistForYouModal = () => {
  NotForYou.value = false;
  WaitlistDcotro.value = true;
  // Re-fetch waitlists specific for the doctor just before opening
  fetchWaitlists(doctorId.value, specializationId.value, NotForYou.value);
};

const openWaitlistNotForYouModal = () => {
  NotForYou.value = true;
  WaitlistDcotro.value = true;
  // Re-fetch waitlists for specialization just before opening
  fetchWaitlists(doctorId.value, specializationId.value, NotForYou.value);
};

const closeWaitlistModal = () => {
  WaitlistDcotro.value = false;
  // Re-fetch waitlist counts after closing in case changes were made
  fetchWaitlists(doctorId.value, specializationId.value, NotForYou.value);
};

const handleSave = () => {
  closeAddModal();
  fetchWaitlists(doctorId.value, specializationId.value, NotForYou.value); // Refresh waitlist counts
};

const handleUpdate = () => {
  closeAddModal();
  fetchWaitlists(doctorId.value, specializationId.value, NotForYou.value); // Refresh waitlist counts
};

const openAddModal = () => {
  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
};

// --- Lifecycle Hooks and Watchers ---

// Initial component setup
onMounted(async () => {
  if (!loading.value) { // Prevent duplicate initialization
    await initializeComponent();
  }
});

// Watch for doctor ID changes in route params
watch(
  () => route.params.id,
  async (newDoctorId, oldDoctorId) => {
    // Only trigger if we have a new valid doctor ID that's different from current
    if (newDoctorId && newDoctorId !== oldDoctorId && !loading.value) {
      doctorId.value = newDoctorId; // Update local reactive doctorId
      await initializeComponent(); // Re-initialize component
    }
  }
);
</script>

<template>
  <div class="appointment-page">
    <div class="pb-2">
      <header-doctor-appointment v-if="doctorId" :isDcotro="false" :doctor-id="doctorId" />
      </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
              <button @click="goToAddAppointmentPage" class="btn btn-primary rounded-pill flex-shrink-0">
                <i class="fas fa-plus me-2"></i>
                Add Appointment
              </button>
              <div class="">
                <button class="btn btn-primary rounded-pill flex-shrink-0 mr-2" @click="openAddModal">
                  <i class="fas fa-plus"></i> Add to WaitList
                </button>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <button @click="getTodaysAppointments(doctorId)" :class="[
                    'btn btn-sm rounded-pill mr-1',
                    currentFilter === 'TODAY' ? 'btn-info' : 'btn-outline-info'
                  ]">
                  <i class="fas fa-calendar-day me-1"></i>
                  Today's Appointments
                  <span class="badge rounded-pill ms-1" :class="currentFilter === 'TODAY' ? 'bg-light text-dark' : 'bg-info'">
                    {{ todaysAppointmentsCount }}
                  </span>
                </button>

                <button v-for="status in statuses" :key="status.name" @click="handleStatusFilter(status.value)" :class="[
                    'btn btn-sm rounded-pill mr-1',
                    currentFilter === status.value ? `btn-${status.color}` : `btn-outline-${status.color}`
                  ]">
                  <i :class="status.icon" class="me-1"></i>
                  {{ status.name }}
                  <span class="badge rounded-pill ms-1" :class="currentFilter === status.value ? 'bg-light text-dark' : `bg-${status.color}`">
                    {{ status.count }}
                  </span>
                </button>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
              <button class="btn btn-outline-success position-relative" type="button" @click="openWaitlistForYouModal">
                <i class="fas fa-user-clock me-2"></i>
                Waitlist for You
                <span v-if="countWithDoctor > 0" class="badge bg-danger ms-1">{{ countWithDoctor }}</span>
              </button>

              <button class="btn btn-outline-warning position-relative ml-1" type="button" @click="openWaitlistNotForYouModal">
                <i class="fas fa-user-times me-2"></i>
                Waitlist for specialization
                <span v-if="countWithoutDoctor > 0" class="badge bg-danger ms-1">{{ countWithoutDoctor }}</span>
              </button>
            </div>

            <div class="d-flex flex-column flex-md-row gap-3 mb-4">
              <div class="d-flex flex-column flex-md-row gap-2 w-100">
                <input type="file" @change="handleFileSelection" multiple accept=".csv,.xlsx,.xls" ref="fileInput" class="d-none" />
                <button @click="$refs.fileInput.click()" class="btn btn-outline-primary w-100" :disabled="loading">
                  <i class="fas fa-upload me-2"></i>
                  Select Files
                </button>
                <button @click="uploadFiles" :disabled="loading || selectedFiles.length === 0" class="btn btn-success w-100">
                  <i class="fas fa-file-import me-2"></i>
                  Import {{ selectedFiles.length }} File{{ selectedFiles.length !== 1 ? 's' : '' }}
                </button>
                <button @click="exportAppointments" class="btn btn-primary w-100">
                  <i class="fas fa-file-export me-2"></i>
                  Export File
                </button>
              </div>
            </div>


            <div v-if="loading && uploadProgress > 0" class="mb-4">
              <div class="progress">
                <div class="progress-bar" :style="{ width: `${uploadProgress}%` }">
                  {{ uploadProgress }}%
                </div>
              </div>
              <div class="mt-2 text-center">
                Uploading file {{ currentFileIndex + 1 }} of {{ selectedFiles.length }}
              </div>
            </div>

            <AppointmentListItem
              :appointments="appointments"
              :userRole="userRole"
              :error="error"
              :doctor-id="doctorId"
              @update-appointment="getAppointments(doctorId, 1, currentFilter)"
              @update-status="getAppointmentsStatus(doctorId)"
              @get-appointments="handleSearchResults"
              @filterByDate="handleFilterByDate"
            />

            <div class="mt-4 d-flex justify-content-center">
              <Bootstrap5Pagination :data="pagination" :limit="5" @pagination-change-page="(page) => getAppointments(doctorId, page, currentFilter)" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <PrimeWaitlistModal
      :WaitlistDcotro="WaitlistDcotro"
      :NotForYou="NotForYou"
      :specializationId="specializationId"
      :doctorId="doctorId"
      @close="closeWaitlistModal"
    />

    <AddWaitlistModal
      :show="showAddModal"
      :editMode="false"
      :specializationId="specializationId"
      @close="closeAddModal"
      @save="handleSave"
      @update="handleUpdate"
    />
  </div>
</template>

<style scoped>
.bg-gradient {
  background: linear-gradient(90deg, rgba(131, 189, 231, 0.7), rgba(86, 150, 202, 0.7));
}
.custom-time {
  position: absolute;
  top: -8px;
  right: -7px;
  background-color: red;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  color: white;
}
/* Ensure buttons and inputs are touch-friendly */
.btn,
.custom-file-label {
  padding: 0.5rem 0.75rem;
  font-size: 1rem;
}

/* Adjust spacing for mobile */
@media (max-width: 768px) {
  .btn-group {
    flex-direction: column;
    width: 100%;
  }

  .btn-group .btn {
    width: 100%;
    margin: 0.25rem 0;
  }

  .d-flex.flex-column.flex-md-row {
    flex-direction: column;
  }

  .d-flex.flex-column.flex-md-row .btn {
    width: 100%;
    margin: 0.25rem 0;
  }

  .d-flex.gap-2 {
    flex-direction: column;
    gap: 0.5rem !important;
  }
}
</style>