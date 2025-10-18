<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import PlaceholderModel from "../Section/placeholderModel.vue";
import PlaceholderItem from "./PlaceholderItem.vue"; // Use the new card component
import { useSweetAlert } from '../../../Components/useSweetAlert';
import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';

const swal = useSweetAlert();
const toaster = useToastr();

const currentPage = ref(1);
const lastPage = ref(1);
const isLoading = ref(false);
const placeholders = ref([]);
const componentLoading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedPlaceholder = ref({});
const excelFiles = ref([]);
const isUploading = ref(false);
const isEditmode = ref(false);
const searchQuery = ref('');
let searchDebounceTimeout = null;
const doctors = useAuthStoreDoctor();
const currentDoctorId = ref(null);
const specializationId = ref(null);

const loadInitialData = async () => {
  try {
    await doctors.getDoctor();
    if (!doctors.doctorData) throw new Error("No doctor data");

    currentDoctorId.value = doctors.doctorData.id;
    specializationId.value = Number(doctors.doctorData.specialization_id) || null;
    
    // Load appointments immediately
    await getPlaceholders();
  } catch (error) {
    toaster.error('Failed to load doctor profile');
    console.error(error);
  }
};

watch(currentDoctorId, (newId) => {
  if (newId) getPlaceholders();
});

onMounted(loadInitialData);

const hasPlaceholders = computed(() => placeholders.value.length > 0);
const canUpload = computed(() => excelFiles.value.length > 0 && !isUploading.value);

// API methods
const getPlaceholders = async (page = 1) => {
    try {
        componentLoading.value = true;
        error.value = null;

        const params = {
            page: page,
            doctor_id: currentDoctorId.value
        };

        if (searchQuery.value.trim()) {
            params.search = searchQuery.value.trim();
        }

        const response = await axios.get(`/api/placeholders`, { params });

        if (page === 1) {
            placeholders.value = response.data.data;
        } else {
            placeholders.value = [...placeholders.value, ...response.data.data];
        }

        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load sections';
        toaster.error(error.value);
    } finally {
        componentLoading.value = false;
    }
};

watch(searchQuery, (newQuery) => {
    if (searchDebounceTimeout) {
        clearTimeout(searchDebounceTimeout);
    }
    searchDebounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        getPlaceholders(1);
    }, 300);
});

const handleScroll = async () => {
    const element = document.documentElement;
    if (
        element.scrollTop + element.clientHeight >= element.scrollHeight - 100 &&
        !componentLoading.value &&
        currentPage.value < lastPage.value
    ) {
        await getPlaceholders(currentPage.value + 1);
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const deletePlaceholder = async (id, name) => {
    try {
        const result = await swal.fire({
            title: 'Are you sure?',
            text: `Delete section "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        });

        if (result.isConfirmed) {
            componentLoading.value = true;
            await axios.delete(`/api/placeholders/${id}`);
            await getPlaceholders(currentPage.value);
            toaster.success('Section deleted successfully');
        }
    } catch (err) {
        toaster.error(err.response?.data?.message || 'Failed to delete section');
    } finally {
        componentLoading.value = false;
    }
};

const openModal = (placeholder = null, isEdit) => {
    selectedPlaceholder.value = placeholder ? { ...placeholder } : {};
    isModalOpen.value = true;
    isEditmode.value = isEdit;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedPlaceholder.value = {};
    isEditmode.value = false;
    getPlaceholders(currentPage.value);
};

const handleFileUpload = (event) => {
    excelFiles.value = Array.from(event.target.files);
};

const clearFileSelection = () => {
    excelFiles.value = [];
    const fileInput = document.getElementById('file-upload');
    if (fileInput) fileInput.value = '';
};

const uploadFiles = async () => {
    if (!excelFiles.value.length) return;

    const formData = new FormData();
    excelFiles.value.forEach(file => {
        formData.append('files[]', file);
    });
    // Add doctor_id to form data for upload as well
    if (currentDoctorId.value) {
        formData.append('doctor_id', currentDoctorId.value);
    }

    try {
        isUploading.value = true;
        const response = await axios.post('/api/import/placeholders', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        toaster.success(response.data.message || 'Files uploaded successfully');
        await getPlaceholders(currentPage.value);
        clearFileSelection();
    } catch (error) {
        toaster.error(error.response?.data?.message || 'File upload failed');
    } finally {
        isUploading.value = false;
    }
};
</script>

<template>
  <div class="placeholder-page min-vh-100 bg-light p-4">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Sections</h1>
          <p class="text-muted mb-0 small">Manage your reusable content sections</p>
        </div>
        <button
          class="btn btn-primary d-flex align-items-center gap-2"
          @click="openModal()"
        >
          <i class="fas fa-plus-circle"></i>
          New Section
        </button>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
          <div class="row g-3 align-items-center">
            <div class="col-md-6">
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                  <i class="fas fa-search text-muted"></i>
                </span>
                <input
                  type="text"
                  class="form-control border-start-0 ps-0"
                  placeholder="Search sections..."
                  v-model="searchQuery"
                >
                <button
                  class="btn btn-outline-secondary border-start-0"
                  type="button"
                  @click="searchQuery = ''"
                  v-if="searchQuery"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>

            <div class="col-md-6">
              <div class="d-flex align-items-center gap-2">
                <input
                  id="file-upload"
                  type="file"
                  multiple
                  @change="handleFileUpload"
                  class="form-control"
                  accept=".xlsx,.xls,.csv"
                />
                <button
                  class="btn btn-success d-flex align-items-center gap-1"
                  @click="uploadFiles"
                  :disabled="!canUpload"
                >
                  <i class="fas fa-upload"></i>
                  <span v-if="isUploading">Uploading...</span>
                  <span v-else>Upload</span>
                </button>
                <button
                  class="btn btn-outline-secondary"
                  @click="clearFileSelection"
                  v-if="excelFiles.length"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div class="mt-2 small text-muted" v-if="excelFiles.length">
                Selected: {{ excelFiles.length }} file(s)
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div v-if="componentLoading && placeholders.length === 0" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-3 mb-0">Loading your sections...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>{{ error }}</div>
          </div>

          <div v-else-if="!hasPlaceholders && !componentLoading" class="text-center py-5">
            <div class="empty-state">
              <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No Sections Found</h5>
              <p class="text-muted mb-4">It looks like you haven't created any sections yet.</p>
              <button class="btn btn-primary" @click="openModal()">
                <i class="fas fa-plus-circle me-2"></i>Create First Section
              </button>
            </div>
          </div>

          <div v-else>
            <div v-if="placeholders.length === 0 && searchQuery.trim()" class="text-center py-4">
              <p class="text-muted">No sections match your search.</p>
              <button class="btn btn-sm btn-outline-secondary mt-2" @click="searchQuery = ''">
                Clear Search
              </button>
            </div>
            <div v-else class="row g-4">
              <PlaceholderItem
                v-for="placeholder in placeholders"
                :key="placeholder.id"
                :placeholder="placeholder"
                @edit="(item) => openModal(item, true)"
                @delete="deletePlaceholder"
              />
            </div>
            
            <div v-if="componentLoading && placeholders.length > 0" class="text-center py-3">
              <div class="spinner-border text-primary spinner-border-sm"></div>
              <p class="mt-2 text-muted small">Loading more sections...</p>
            </div>
          </div>
        </div>
      </div>

      <PlaceholderModel
        :doctorId="currentDoctorId"
        :show-modal="isModalOpen"
        :placeholder-data="selectedPlaceholder"
        @close="closeModal"
        @placeholderUpdate="getPlaceholders"
      />
    </div>
  </div>
</template>

<style scoped>
/* Main container styles */
.placeholder-page {
    background: #f8f9fa;
    padding: 2rem 0;
}

/* Card styles */
.card {
    border-radius: 12px;
    border: none;
}

/* Input group styles for search */
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

/* Empty state styles */
.empty-state {
    max-width: 400px;
    margin: 0 auto;
}

/* Utility classes for spacing and alignment */
.d-flex { display: flex; }
.align-items-center { align-items: center; }
.justify-content-between { justify-content: space-between; }
.gap-1 { gap: 0.25rem; }
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 1rem; }
.me-2 { margin-right: 0.5rem; }
.mt-3 { margin-top: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }
.py-5 { padding-top: 3rem; padding-bottom: 3rem; }
.px-4 { padding-left: 1.5rem; padding-right: 1.5rem; }

/* Small text and muted color */
.small { font-size: 0.875em; }
.text-muted { color: #6c757d !important; }

/* Spinner styles */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>