<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../toster'; // Adjust path if needed
import AgreementTableListItem from './AgreementTableListItem.vue';
import AgreementModal from '../models/AgreementModals.vue'; // Correct path to your modal component

const props = defineProps({
  contractState: String,
  contractid: String, // Ensure this prop is correctly passed from parent
});

const toast = useToastr();

// Search and filter state
const searchQuery = ref('');
const searchDate = ref(null);
const filterType = ref('id');
const filterOptions = [
  { label: 'By ID', value: 'id' },
  { label: 'By Creation Time', value: 'createdAt' },
];

// Data state
const items = ref([]);
const selectedItem = ref({}); // Used for info/delete modals

// Modal state
const showModalFlag = ref(false);
const currentModalType = ref('add'); // 'add', 'edit', 'info', 'delete'
const currentForm = ref({
  id: null,
  title: '',
  description: '',
  file_path: null,
  file_url: null,
  file: null, // For new file upload
  remove_file: false, // Flag to indicate file removal
});
const isLoading = ref(false);

// Pagination state
const currentPage = ref(1);
const itemsPerPage = ref(8);

// Computed properties
const filteredItemsComputed = computed(() => {
  let filtered = items.value;

  if (filterType.value === 'id' && searchQuery.value) {
    filtered = filtered.filter((item) => item.id.toString().includes(searchQuery.value));
  } else if (filterType.value === 'createdAt' && searchDate.value) {
    const dateToCompare = searchDate.value instanceof Date ? searchDate.value : new Date(searchDate.value);
    const formattedDate = dateToCompare.toISOString().split('T')[0];

    filtered = filtered.filter((item) => {
      const itemDate = new Date(item.created_at).toISOString().split('T')[0];
      return itemDate === formattedDate;
    });
  }
  return filtered;
});

const paginatedFilteredItems = computed(() => {
  const filtered = filteredItemsComputed.value;
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filtered.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredItemsComputed.value.length / itemsPerPage.value);
});

// Pagination methods
const changePage = (page) => {
  if (page > 0 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

// API methods
const fetchAgreements = async () => {
  if (!props.contractid) {
    toast.error('Contract ID is missing for fetching agreements.');
    return;
  }

  try {
    const response = await axios.get(`/api/agreements`, {
      params: {
        convention_id: props.contractid, // Pass convention_id for filtering
      },
    });
    items.value = response.data.map(item => ({
      ...item,
      // Create file_url for direct access in the frontend, if file_path exists
      file_url: item.file_path ? `/storage/${item.file_path}` : null
    }));
    currentPage.value = 1; // Reset to first page on new data
  } catch (error) {
    console.error('Error fetching agreements:', error);
    toast.error('Failed to fetch agreements');
  }
};

// --- Modal methods ---
const openAddModal = () => {
  currentModalType.value = 'add';
  // Reset form for adding
  currentForm.value = {
    id: null,
    title: '',
    description: '',
    file_path: null,
    file_url: null,
    file: null,
    remove_file: false,
  };
  selectedItem.value = {}; // Clear selected item for add
  showModalFlag.value = true;
};

const openEditModal = (item) => {
  currentModalType.value = 'edit';
  // Populate form with item data for editing
  currentForm.value = {
    id: item.id,
    title: item.name || '', // Use item.name from backend
    description: item.description || '',
    file_path: item.file_path || null,
    file_url: item.file_url || null,
    file: null, // File input should be cleared for new selection
    remove_file: false, // Reset remove file flag for edit
  };
  selectedItem.value = { ...item }; // Keep a copy of original for reference if needed
  showModalFlag.value = true;
};

const openInfoModal = (item) => {
  currentModalType.value = 'info';
  selectedItem.value = { ...item }; // Pass item for display
  showModalFlag.value = true;
};

const openDeleteModal = (item) => {
  currentModalType.value = 'delete';
  selectedItem.value = { ...item }; // Pass item for confirmation message
  showModalFlag.value = true;
};

const handleCloseModal = () => {
  showModalFlag.value = false;
};

// --- Modal event handlers ---
const handleModalSave = async (formData) => {
  if (!formData.title) {
    toast.warning('Title is required');
    return;
  }

  isLoading.value = true;

  try {
    const dataToSend = new FormData();
    dataToSend.append('name', formData.title); // Backend expects 'name'
    dataToSend.append('description', formData.description || '');
    dataToSend.append('convention_id', props.contractid);

    if (formData.file) {
      dataToSend.append('file', formData.file);
    }

    let url = `/api/agreements`; // Default URL for POST
    let method = '';

    if (currentModalType.value === 'add') {
      method = 'post';
    } else if (currentModalType.value === 'edit') {
      // THIS IS THE CRUCIAL CHANGE FOR EDITING
      url = `/api/agreements/${formData.id}`; // Append the ID to the URL for PUT
      method = 'post'; // Still use POST for file uploads with method spoofing
      dataToSend.append('_method', 'PUT'); // Spoof PUT method for Laravel
      dataToSend.append('id', formData.id); // Add ID for edit (can be removed if not explicitly needed by controller)
      dataToSend.append('remove_file', formData.remove_file ? '1' : '0'); // Send as string '1' or '0'
    }

    const config = {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    };

    let response;
    if (method === 'post') {
      response = await axios.post(url, dataToSend, config);
    }
    // No 'else if (method === 'put')' block is needed here
    // because we are always sending a POST request and spoofing PUT.

    toast.success(`Agreement ${currentModalType.value === 'add' ? 'added' : 'updated'} successfully`);

    await fetchAgreements();
    showModalFlag.value = false;
  } catch (error) {
    console.error('Error saving agreement:', error);
    if (error.response && error.response.data && error.response.data.errors) {
      for (const key in error.response.data.errors) {
        toast.error(error.response.data.errors[key][0]);
      }
    } else {
      toast.error(`Failed to ${currentModalType.value} agreement`);
    }
  } finally {
    isLoading.value = false;
  }
};

const handleModalDelete = async () => {
  if (!selectedItem.value.id) {
    toast.error('No agreement selected for deletion.');
    return;
  }

  isLoading.value = true;

  try {
    await axios.delete(`/api/agreements/${selectedItem.value.id}`); // Corrected endpoint
    await fetchAgreements();
    showModalFlag.value = false;
    toast.success('Agreement deleted successfully');
  } catch (error) {
    console.error('Error deleting agreement:', error);
    toast.error('Failed to delete agreement');
  } finally {
    isLoading.value = false;
  }
};

// Initialize component
onMounted(() => {
  fetchAgreements();
});
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 gap-2">
      <div class="d-flex align-items-center gap-2 flex-grow-1 w-100">
        <select v-model="filterType" class="form-select border rounded-lg w-auto">
          <option v-for="option in filterOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <input v-if="filterType === 'id'" type="text" v-model="searchQuery" placeholder="Search by ID..."
          class="form-control flex-grow-1" />
        <input v-else type="date" v-model="searchDate" placeholder="Select Date" class="form-control flex-grow-1" />
      </div>
      <button v-if="contractState === 'pending'" class="btn btn-primary d-flex align-items-center"
        @click="openAddModal">
        <i class="fas fa-plus me-1"></i> Add
      </button>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div v-if="paginatedFilteredItems.length === 0"
          class="text-center text-muted py-5 d-flex flex-column align-items-center">
          <i class="fas fa-file fs-3 mb-2"></i>
          <span>No agreements found.</span>
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Title</th>
                <th scope="col">Created At</th>
                <th scope="col">Description</th>
                <th scope="col">Document</th>
                <th v-if="contractState === 'pending'" scope="col">Actions</th>
                <th scope="col">Details</th>
              </tr>
            </thead>
            <tbody>
              <AgreementTableListItem v-for="item in paginatedFilteredItems" :key="item.id" :agreement="item"
                :contract-state="contractState" @edit="openEditModal" @delete="openDeleteModal"
                @show-info="openInfoModal" />
            </tbody>
          </table>
        </div>

        <nav v-if="totalPages > 1" aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
              <button class="page-link" @click="changePage(currentPage - 1)" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
              </button>
            </li>
            <li class="page-item" v-for="page in totalPages" :key="page" :class="{ 'active': currentPage === page }">
              <button class="page-link" @click="changePage(page)">{{ page }}</button>
            </li>
            <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
              <button class="page-link" @click="changePage(currentPage + 1)" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <AgreementModal :show-modal="showModalFlag" :modal-type="currentModalType" :form="currentForm"
      :selected-item="selectedItem" :is-loading="isLoading" @save="handleModalSave" @delete="handleModalDelete"
      @close-modal="handleCloseModal" />
  </div>
</template>

<style scoped>
/* Container styling */
.container-fluid.py-4 {
  padding-top: 1.5rem !important;
  padding-bottom: 1.5rem !important;
}

/* Gap utility for flexbox */
.d-flex.gap-2>* {
  margin-right: 0.5rem;
}

.d-flex.gap-2>*:last-child {
  margin-right: 0;
}

/* Card styling */
.card {
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

/* Form controls */
.form-control,
.form-select {
  border-radius: 0.5rem;
  padding: 0.625rem 0.75rem;
}

/* Table styling */
.table th,
.table td {
  vertical-align: middle;
  padding: 0.75rem;
}

/* Empty state styling */
.text-muted {
  color: #6c757d !important;
}

.fs-3 {
  font-size: calc(1.3rem + 0.6vw) !important;
}

/* Button styling */
.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.btn-warning {
  background-color: #ffc107;
  border-color: #ffc107;
  color: #212529;
}

.btn-warning:hover {
  background-color: #e0a800;
  border-color: #d39e00;
}

.btn-danger {
  background-color: #dc3545;
  border-color: #dc3545;
}

.btn-danger:hover {
  background-color: #c82333;
  border-color: #bd2130;
}

.btn-info {
  background-color: #17a2b8;
  border-color: #17a2b8;
}

.btn-info:hover {
  background-color: #138496;
  border-color: #117a8b;
}

/* Pagination styling */
.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #007bff;
  background-color: #fff;
  border: 1px solid #dee2e6;
}

.page-link:hover {
  color: #0056b3;
  background-color: #e9ecef;
  border-color: #dee2e6;
}

.page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.page-item.disabled .page-link {
  color: #6c757d;
  background-color: #fff;
  border-color: #dee2e6;
}
</style>