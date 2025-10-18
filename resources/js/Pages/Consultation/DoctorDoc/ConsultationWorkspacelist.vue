<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import ConsultationWorkspacelistitem from './ConsultationWorkspacelistitem.vue';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import consultationworkspacesModel from '../../../Components/DoctorDoc/consultationworkspacesModel.vue';
import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';

const router = useRouter();
const swal = useSweetAlert();
const toaster = useToastr();

// State
const workspaces = ref([]);
const doctors = useAuthStoreDoctor();
const currentDoctorId = ref(null);
const loading = ref(false);
const error = ref(null);
const showCreateModal = ref(false);
const selectedWorkspace = ref(null);
const isEditMode = ref(false);
const searchTerm = ref('');

// Computed
const filteredWorkspaces = computed(() => {
  if (!searchTerm.value) return workspaces.value;

  const search = searchTerm.value.toLowerCase();
  return workspaces.value.filter(workspace =>
    workspace.name.toLowerCase().includes(search) ||
    (workspace.description && workspace.description.toLowerCase().includes(search))
  );
});

const activeWorkspaces = computed(() =>
  filteredWorkspaces.value.filter(w => !w.is_archived)
);

const archivedWorkspaces = computed(() =>
  filteredWorkspaces.value.filter(w => w.is_archived)
);

// Lifecycle Hook
onMounted(async () => {
  await initializeDoctor();
});

const initializeDoctor = async () => {
  try {
    await doctors.getDoctor();

    if (doctors.doctorData) {
      currentDoctorId.value = doctors.doctorData.id;
      await getWorkspaces();
    } else {
      toaster.error('Failed to load doctor profile. Please try again.');
      router.push({ name: 'login' });
    }
  } catch (err) {
    toaster.error('Failed to initialize. Please refresh the page.');
    console.error('Initialization error:', err);
  }
};

// API Calls
const getWorkspaces = async () => {
  if (!currentDoctorId.value) {
    console.warn('Cannot fetch workspaces: currentDoctorId is null.');
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get('/api/consultationworkspaces', {
      params: {
        doctorid: currentDoctorId.value
      },
    });
    workspaces.value = response.data.data || response.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load workspaces';
    toaster.error(error.value);
    workspaces.value = [];
  } finally {
    loading.value = false;
  }
};

const deleteWorkspace = async (id, name) => {
  console.log(id, name);

  const result = await swal.fire({
    title: 'Are you sure?',
    text: `Delete workspace "${name}"? This action cannot be undone.`,
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
      await axios.delete(`/api/consultationworkspaces/${id}`);
      workspaces.value = workspaces.value.filter(w => w.id !== id);
      toaster.success('Workspace deleted successfully');
    } catch (err) {
      toaster.error(err.response?.data?.message || 'Failed to delete workspace');
    }
  }
};

// Event Handlers
const handleWorkspaceSaved = async () => {
  await getWorkspaces();
  showCreateModal.value = false;
  selectedWorkspace.value = null;
  isEditMode.value = false;
};
const closemodel = () => {
  showCreateModal.value = false;
}

const openModal = (workspace = null, edit = false) => {
  selectedWorkspace.value = workspace ? { ...workspace } : {};
  showCreateModal.value = true;
  isEditMode.value = edit;
};


const handleEdit = (workspace) => {
  openModal(workspace, true);
};

const handleDelete = (id, name) => {
  deleteWorkspace(id, name);

};

const handleView = (workspaceId) => {
  // Assuming there's a route for viewing workspace details, similar to folders
  router.push({ name: 'WorkspaceDetails', params: { id: workspaceId } });
};


const refreshWorkspaces = () => {
  getWorkspaces();
};
</script>

<template>
  <div class="workspace-page min-vh-100 bg-light p-4">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">
            <i class="fas fa-briefcase-medical me-2 text-primary"></i>
            Consultation Workspaces
          </h1>
          <p class="text-muted mb-0 small">
            Organize and manage your consultation templates and workflows
          </p>
        </div>
        <div class="d-flex gap-2">
          <button
            class="btn btn-outline-primary"
            @click="refreshWorkspaces"
            :disabled="loading"
            title="Refresh workspaces"
          >
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
          </button>
          <button
            class="btn btn-primary d-flex align-items-center gap-2"
            @click="openModal()"
          >
            <i class="fas fa-plus"></i>
            New Workspace
          </button>
        </div>
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
              placeholder="Search workspaces..."
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
            <p class="text-muted mt-3 mb-0">Loading Workspaces...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>{{ error }}</div>
          </div>

          <div v-else-if="workspaces.length === 0 && !loading" class="text-center py-5">
            <div class="empty-state">
              <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No Workspaces Yet</h5>
              <p class="text-muted mb-4">
                Create your first workspace to start organizing your consultation templates and workflows.
              </p>
              <button class="btn btn-primary" @click="openModal()">
                <i class="fas fa-plus me-2"></i>Create Your First Workspace
              </button>
            </div>
          </div>

          <div v-else>
            <div v-if="activeWorkspaces.length > 0" class="workspace-section mb-5">
              <h5 class="section-title fw-bold text-dark mb-3">
                <i class="fas fa-folder-open me-2 text-success"></i>
                Active Workspaces ({{ activeWorkspaces.length }})
              </h5>
              <div class="row g-4">
                <ConsultationWorkspacelistitem
                  v-for="workspace in activeWorkspaces"
                  :key="`active-${workspace.id}`"
                  :workspaceid="workspace.id"
                  :workspace="workspace"
                  @edit="handleEdit"
                  @delete="handleDelete"
                  @view="handleView"
                />
              </div>
            </div>

            <div v-if="archivedWorkspaces.length > 0" class="workspace-section">
              <h5 class="section-title fw-bold text-dark mb-3">
                <i class="fas fa-archive me-2 text-warning"></i>
                Archived Workspaces ({{ archivedWorkspaces.length }})
              </h5>
              <div class="row g-4">
                <ConsultationWorkspacelistitem
                  v-for="workspace in archivedWorkspaces"
                  :key="`archived-${workspace.id}`"
                  :workspaceid="workspace.id"
                  :workspace="workspace"
                  @edit="handleEdit"
                  @delete="handleDelete"
                  @view="handleView"
                />
              </div>
            </div>

            <div v-if="filteredWorkspaces.length === 0 && searchTerm" class="no-results text-center py-5">
              <i class="fas fa-search fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No workspaces found</h5>
              <p class="text-muted mb-3">Try adjusting your search terms</p>
              <button class="btn btn-outline-primary" @click="searchTerm = ''">
                Clear Search
              </button>
            </div>
          </div>
        </div>
      </div>

      <consultationworkspacesModel
        v-model="showCreateModal"
        :workspace="selectedWorkspace"
        :doctorid="currentDoctorId"
        :is-edit="isEditMode"
        @workspace-saved="handleWorkspaceSaved"
        @close="closemodel()"
      />
    </div>
  </div>
</template>

<style scoped>
.workspace-page {
  background: #f8f9fa; /* Consistent light background */
}

/* Base card styles */
.card {
  border-radius: 12px; /* Consistent border-radius */
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important; /* Lighter shadow */
  transition: all 0.3s ease;
}

/* Header styles (simplified as per FolderList's header) */
.h3 {
  font-weight: bold;
}

/* Search input group styles */
.input-group {
  border-radius: 8px; /* Consistent with FolderList */
  overflow: hidden;
}

.input-group-text,
.form-control {
  border-color: #e9ecef; /* Consistent border color */
}

.form-control:focus {
  border-color: #e9ecef; /* Keep border color consistent on focus */
  box-shadow: none; /* Remove default bootstrap focus shadow */
}

/* Buttons */
.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  border: none;
  border-radius: 8px; /* Slightly smaller radius for buttons */
  padding: 10px 20px; /* Adjusted padding */
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2); /* Lighter shadow on hover */
}

.btn-outline-primary {
  border: 1px solid #007bff; /* Consistent border */
  border-radius: 8px; /* Consistent radius */
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-outline-primary:hover {
  background-color: #e6f0fa; /* Light background on hover */
  transform: translateY(-1px);
  box-shadow: 0 2px 5px rgba(0, 123, 255, 0.1);
}

/* Section titles (Active/Archived) */
.section-title {
  font-weight: bold; /* Bold text for section titles */
  color: #343a40; /* Darker text for better contrast */
  /* Remove line and background styles for a cleaner look, consistent with FolderList */
}

/* Loading, Error, Empty, No results states */
.spinner-border {
  color: #007bff !important; /* Ensure primary color for spinner */
}

.alert {
  border-radius: 8px; /* Consistent with other elements */
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.empty-state, .no-results {
  max-width: 500px; /* Constrain width for better readability */
  margin: 0 auto;
}

.empty-state .fa-folder-open, .no-results .fa-search {
  color: #ced4da !important; /* Lighter grey for icons in empty states */
}

/* Removed previous specific header-card, search-card, content-card styles
   as the generic .card styles are now applied.
   Also removed the gradient background and complex search input styles
   to match the simpler FolderList design. */
</style>