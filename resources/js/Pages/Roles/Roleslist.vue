<script setup>
import { ref, onMounted, watch } from 'vue'; // Import 'watch' for reactivity
import axios from 'axios';
import { useToastr } from '../../Components/toster'; // Adjust path as needed
import { useSweetAlert } from '../../Components/useSweetAlert'; // Adjust path as needed
import RoleModal from '../../Components/Roles/Rolemodel.vue'; // Assuming RoleModal.vue is in the same directory or adjust path

const swal = useSweetAlert();
const toaster = useToastr();

const roles = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedRole = ref(null);
const searchTerm = ref(''); // New ref for search input

// Debounce for search input
let searchTimeout = null;

// --- API Operations ---

const getRoles = async () => {
  try {
    loading.value = true;
    error.value = null; // Clear previous errors

    // Construct query parameters for search
    const params = {};
    if (searchTerm.value) {
      params.search = searchTerm.value;
    }

    const response = await axios.get('/api/roles', { params });
    roles.value = response.data.data || response.data; // Adjust based on your API response
    console.log('Roles fetched:', roles.value);
  } catch (err) {
    console.error('Error fetching roles:', err);
    error.value = err.response?.data?.message || 'Failed to load roles.';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const deleteRole = async (id) => {
  try {
    const result = await swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
    });

    if (result.isConfirmed) {
      await axios.delete(`/api/roles/${id}`);
      toaster.success('Role deleted successfully!');
      await getRoles(); // Refresh the list after deletion
      swal.fire(
        'Deleted!',
        'The role has been deleted.',
        'success'
      );
    }
  } catch (err) {
    console.error('Error deleting role:', err);
    const errorMessage = err.response?.data?.message || 'Failed to delete role.';
    toaster.error(errorMessage);
    swal.fire(
      'Error!',
      errorMessage,
      'error'
    );
  }
};

// --- Modal Operations ---

const openModal = (role = null) => {
  selectedRole.value = role ? { ...role } : { name: '' }; // Initialize with empty name for new roles
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedRole.value = null;
};

const handleRoleUpdated = async () => {
  await getRoles(); // Refresh roles after creation/update
  closeModal();
};

// --- Search Handler ---
const handleSearch = () => {
  clearTimeout(searchTimeout); // Clear previous timeout
  searchTimeout = setTimeout(() => {
    getRoles(); // Trigger API call after debounce delay
  }, 300); // 300ms debounce delay
};

// --- Lifecycle Hook ---
onMounted(() => {
  getRoles();
});

// Watch for changes in search term
watch(searchTerm, () => {
  handleSearch();
});
</script>

<template>
  <div class="role-management-page">
    <div class="content-header bg-light py-3 border-bottom">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Role Management</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Roles</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content pt-3">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-3">
              <button class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2 flex-grow-1 flex-md-grow-0" @click="openModal()">
                <i class="fas fa-plus-circle"></i>
                <span>Add New Role</span>
              </button>
              <div class="input-group w-auto flex-grow-1 flex-md-grow-0">
                <input
                  type="text"
                  class="form-control"
                  placeholder="Search roles..."
                  v-model="searchTerm"
                />
                <div class="input-group-append">
                  <span class="input-group-text">
                    <i class="fas fa-search"></i>
                  </span>
                </div>
              </div>
            </div>

            <div class="card shadow-sm">
              <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Roles List</h3>
              </div>
              <div class="card-body p-0">
                <div v-if="loading" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                  </div>
                  <p class="mt-2 text-muted">Loading roles...</p>
                </div>

                <div v-else-if="error" class="alert alert-danger m-3" role="alert">
                  {{ error }}
                </div>

                <table v-else class="table table-hover table-striped mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th scope="col" style="width: 50px;">#</th>
                      <th scope="col">Role Name</th>
                      <th scope="col" style="width: 120px;">Actions</th> </tr>
                  </thead>
                  <tbody>
                    <tr v-if="roles.length === 0 && !loading && !error">
                      <td colspan="3" class="text-center py-4 text-muted">No roles found. Try adjusting your search or click "Add New Role".</td>
                    </tr>
                    <tr v-else v-for="(role, index) in roles" :key="role.id">
                      <td>{{ index + 1 }}</td>
                      <td>{{ role.name }}</td>
                      <td>
                        <div class="d-flex gap-2">
                          <button @click="openModal(role)" class="btn btn-sm btn-outline-info" title="Edit Role">
                            <i class="fas fa-edit"></i>
                            <span class="sr-only">Edit</span>
                          </button>
                          <button @click="deleteRole(role.id)" class="btn btn-sm btn-outline-danger" title="Delete Role">
                            <i class="fas fa-trash-alt"></i>
                            <span class="sr-only">Delete</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              </div>
          </div>
        </div>
      </div>
    </div>

    <RoleModal
      :show-modal="isModalOpen"
      :role-data="selectedRole"
      @close="closeModal"
      @roleUpdated="handleRoleUpdated"
    />
  </div>
</template>

<style scoped>
.role-management-page {
  min-height: calc(100vh - 56px); /* Adjust based on your header/footer height */
  background-color: #f8f9fa;
}

.content-header h1 {
  font-size: 1.8rem;
  font-weight: 600;
}

.breadcrumb-item + .breadcrumb-item::before {
  content: ">";
}

.card-header {
  border-bottom: 1px solid #dee2e6;
  padding-bottom: 0.75rem; /* Adjust padding */
  padding-top: 0.75rem;
}

.table-hover tbody tr:hover {
  background-color: #f2f2f2;
}

/* Adjust button spacing for actions column */
td .btn {
  margin-right: 5px; /* Add some space between buttons */
}
td .btn:last-child {
  margin-right: 0;
}

/* Flexbox for top controls */
.d-flex.flex-column.flex-md-row {
  align-items: stretch; /* Align items to stretch vertically in column mode */
}

@media (min-width: 768px) {
  .d-flex.flex-column.flex-md-row {
    align-items: center; /* Center items vertically in row mode */
  }
}

.input-group {
  max-width: 300px; /* Limit search input width on larger screens */
}

/* Ensure the `RoleModal.vue` import path is correct: */
/* RoleModal from '../../Components/Roles/RoleModal.vue'; */
/* Make sure you've renamed `Rolemodel.vue` to `RoleModal.vue` as per previous advice. */
</style>