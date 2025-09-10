<script setup>
import { ref, onMounted, watch } from 'vue'; // Import 'watch'
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import FolderListItem from './FolderListItem.vue';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import FolderModel from './FolderModel.vue';
import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';

const router = useRouter();
const swal = useSweetAlert();
const toaster = useToastr();

// State
const folders = ref([]);
const doctors = useAuthStoreDoctor(); // Initialize Pinia store
const currentDoctorId = ref(null);
const specializationId = ref(null);

// Lifecycle Hook
onMounted(async () => {
  await doctors.getDoctor(); 
  // Ensure the doctor data is fetched and awaited.
  // Assuming doctors.getDoctor() populates doctors.doctorData in the store.

  // After the async operation, doctorData should be available.
  // Always add a check in case the API call failed or doctorData isn't populated.
  if (doctors.doctorData) {
    currentDoctorId.value = doctors.doctorData.id;
    // Ensure specializationId is a number. Convert it if it might come as a string.
    specializationId.value = doctors.doctorData.specialization_id 
                             ? Number(doctors.doctorData.specialization_id) 
                             : null; // Or 0, depending on your default for an empty ID
  } else {
    // Handle the case where doctor data couldn't be loaded (e.g., show error, redirect)
    toaster.error('Failed to load doctor profile. Please try again.');
    // Optionally, return or redirect to prevent further execution if doctor data is crucial
    // router.push({ name: 'login' }); 
    return; 
  }

  // Now that currentDoctorId is guaranteed to be set (or handled as null),
  // you can safely call getFolders.
  if (currentDoctorId.value !== null) {
    getFolders();
  } else {
    toaster.info('Doctor ID not found. Cannot fetch folders.');
  }
});

// Use a watcher to react to changes in currentDoctorId if getFolders could be called later
// (Though in this specific setup, onMounted handles the initial call)
/*
watch(currentDoctorId, (newId) => {
  if (newId !== null) {
    getFolders(); // Re-fetch folders if doctorId changes (e.g., after login/logout flow)
  }
});
*/

const loading = ref(false);
const error = ref(null);
const showCreateModal = ref(false);
const selectedFolder = ref(null);
const isEditMode = ref(false);
const searchTerm = ref('');

const filteredFolders = computed(() => {
  if (!searchTerm.value) return folders.value;
  
  const search = searchTerm.value.toLowerCase();
  return folders.value.filter(folder => 
    folder.name.toLowerCase().includes(search) ||
    (folder.description && folder.description.toLowerCase().includes(search))
  );
});

// API Calls
const getFolders = async () => {
  // Ensure currentDoctorId has a value before making the API call
  if (currentDoctorId.value === null) {
    console.warn('Cannot fetch folders: currentDoctorId is null.');
    return; // Exit if ID is not available
  }

  loading.value = true; // Set loading state before the call
  try {
    // Pass currentDoctorId.value to access the actual value of the ref
    const response = await axios.get('/folders', {
      params: { // Use 'params' for GET requests to send query parameters
        doctorid: currentDoctorId.value, 
      },
    });
    folders.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load folders';
    toaster.error(error.value);
  } finally {
    loading.value = false; // Reset loading state
  }
};

const deleteFolder = async (id, name) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: `Delete folder "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel',
    customClass: {
      confirmButton: 'btn btn-danger',
      cancelButton: 'btn btn-secondary'
    }
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/folders/${id}`);
      folders.value = folders.value.filter(f => f.id !== id);
      toaster.success('Folder deleted successfully');
    } catch (err) {
      toaster.error(err.response?.data?.message || 'Failed to delete folder');
    }
  }
};

const handleFolderSaved = async () => {
  await getFolders();
  showCreateModal.value = false;
};

const openModal = (folder = null, edit = false) => {
  selectedFolder.value = folder ? { ...folder } : {};
  showCreateModal.value = true;
  isEditMode.value = edit;
};

const viewFolder = (folderId) => {
  router.push({ name: 'FolderDetails', params: { id: folderId } });
};

const handleEdit = (folder) => {
  openModal(folder, true);
};

const handleDelete = (id, name) => {
  deleteFolder(id, name);
};

const handleView = (folderId) => {
  viewFolder(folderId);
};
</script>

<template>
  <div class="folder-page min-vh-100 bg-light p-4">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Template Folders</h1>
          <p class="text-muted mb-0 small">Organize your templates in folders</p>
        </div>
        <button 
          class="btn btn-primary d-flex align-items-center gap-2"
          @click="openModal()"
        >
          <i class="fas fa-folder-plus"></i>
          New Folder
        </button>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <i class="fas fa-search text-muted"></i>
            </span>
            <input 
              type="text" 
              v-model="searchTerm"
              class="form-control border-start-0 ps-0" 
              placeholder="Search folders..."
            >
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-3 mb-0">Loading your folders...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>{{ error }}</div>
          </div>

          <div v-else-if="folders.length === 0 && !loading" class="text-center py-5">
            <div class="empty-state">
              <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No Folders Yet</h5>
              <p class="text-muted mb-4">Create your first folder to organize your templates</p>
              <button class="btn btn-primary" @click="openModal()">
                <i class="fas fa-folder-plus me-2"></i>Create Folder
              </button>
            </div>
          </div>

          <div class="row gap-3">
            <FolderListItem
              v-for="folder in filteredFolders"
              :key="folder.id"
              :folderid="folder.id"
              :folder="folder"
              @edit="handleEdit"
              @delete="handleDelete"
              @view="handleView"
            />
          </div>
        </div>
      </div>

      <FolderModel
        v-model="showCreateModal"
        :folder="selectedFolder"
        :doctorid="currentDoctorId"
        :specializationId="specializationId"
        :is-edit="isEditMode"
        @folder-saved="handleFolderSaved"
      />
    </div>
  </div>
</template>

<style scoped>
.folder-page {
  background: #f8f9fa;
}

.folder-card {
  transition: all 0.3s ease;
  cursor: pointer;
}

.folder-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}

.folder-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 193, 7, 0.1);
  border-radius: 8px;
}

.folder-card:hover .folder-icon {
  background: rgba(255, 193, 7, 0.2);
}

.folder-stats {
  opacity: 0.7;
}

.folder-card:hover .folder-stats {
  opacity: 1;
}

.btn-light {
  background: #f8f9fa;
  border-color: #f0f0f0;
}

.btn-light:hover {
  background: #e9ecef;
  border-color: #e9ecef;
}

.empty-state {
  max-width: 400px;
  margin: 0 auto;
}

.card {
  border-radius: 12px;
}

.input-group {
  border-radius: 8px;
  overflow: hidden;
}

.input-group-text,
.form-control {
  border-color: #e9ecef;
}

.form-control:focus {
  border-color: #e9ecef;
  box-shadow: none;
}
</style>