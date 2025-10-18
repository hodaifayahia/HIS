<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import PlaceholderModel from "../Section/placeholderModel.vue";
import PlaceholderItem from "../Section/PlaceholderItem.vue";
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

const loadDoctorData = async () => {
  try {
    await doctors.getDoctor();
    if (doctors.doctorData) {
      currentDoctorId.value = doctors.doctorData.id;
      specializationId.value = Number(doctors.doctorData.specialization_id) || null;
      console.log('Doctor data loaded - ID:', currentDoctorId.value);
    } else {
      throw new Error('No doctor data available');
    }
  } catch (error) {
    console.error('Failed to load doctor data:', error);
    toaster.error('Failed to load doctor profile');
    // Consider redirecting to login if essential
  }
};

// 2. Watch for changes in currentDoctorId to trigger appointments load
watch(currentDoctorId, (newId) => {
  if (newId) {
    console.log('Doctor ID changed, loading appointments:', newId);
    getPlaceholders();
  }
}, { immediate: false });

// 3. Initialize in onMounted
onMounted(async () => {
  await loadDoctorData();
 getPlaceholders()

});


const hasPlaceholders = computed(() => placeholders.value.length > 0);
const canUpload = computed(() => excelFiles.value.length > 0 && !isUploading.value);

// API methods
const getPlaceholders = async (page = 1) => {
    try {
        componentLoading.value = true;
        error.value = null;

        const params = {
            page: page,
            // FIX: Access the value of the ref
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
    // Initial fetch is now done in onMounted after doctor data is available
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const deletePlaceholder = async (id) => {
    try {
        const result = await swal.fire({
            title: 'Are you sure?',
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
    <div class="placeholder-page">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Sections</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Sections</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-primary me-2" @click="openModal">
                                                <i class="fas fa-plus-circle me-1"></i> New Section
                                            </button>

                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Search sections..."
                                                    v-model="searchQuery">
                                                <button class="btn btn-outline-secondary" type="button" @click="searchQuery = ''"
                                                    v-if="searchQuery">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <input id="file-upload" type="file" multiple @change="handleFileUpload" class="form-control me-2"
                                                accept=".xlsx,.xls,.csv" />
                                            <button class="btn btn-success" @click="uploadFiles" :disabled="!canUpload">
                                                <i class="fas fa-upload me-1"></i>
                                                <span v-if="isUploading">Uploading...</span>
                                                <span v-else>Upload</span>
                                            </button>

                                            <button class="btn btn-outline-secondary ms-2" @click="clearFileSelection"
                                                v-if="excelFiles.length">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div class="mt-2 small text-muted" v-if="excelFiles.length">
                                            {{ excelFiles.length }} file(s) selected
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div v-if="error" class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> {{ error }}
                                    <button class="btn-close float-end" @click="error = null"></button>
                                </div>

                                <div v-if="componentLoading && placeholders.length === 0" class="text-center py-5">
                                    <div class="spinner-border text-primary"></div>
                                    <p class="mt-2">Loading sections...</p>
                                </div>

                                <div v-else-if="!hasPlaceholders && !componentLoading" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="lead">No sections found</p>
                                    <button class="btn btn-primary mt-2" @click="openModal">
                                        <i class="fas fa-plus-circle me-1"></i> Add Your First Section
                                    </button>
                                </div>

                                <div v-else>
                                    <div v-if="placeholders.length === 0 && searchQuery.trim()" class="text-center py-4">
                                        <p>No sections match your search.</p>
                                        <button class="btn btn-sm btn-outline-secondary" @click="searchQuery = ''">
                                            Clear Search
                                        </button>
                                    </div>


                                    <table v-else class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th style="width: 30%">Name</th>
                                                <th style="width: 45%">Description</th>
                                                <th style="width: 45%">Specializations</th>
                                                <th style="width: 45%">Doctor</th>
                                                <th style="width: 20%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <placeholder-item v-for="(placeholder, index) in placeholders" :key="placeholder.id"
                                                :placeholder="placeholder" :index="index" @edit="(item) => openModal(item, true)"
                                                @delete="deletePlaceholder" />
                                        </tbody>
                                    </table>
                                    <div v-if="componentLoading && placeholders.length > 0" class="text-center py-3">
                                        <div class="spinner-border text-primary spinner-border-sm"></div>
                                        <p class="mt-2">Loading more sections...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <PlaceholderModel :doctorId="currentDoctorId" :show-modal="isModalOpen" :placeholder-data="selectedPlaceholder" @close="closeModal"
            @placeholderUpdate="getPlaceholders" />
    </div>
</template>

<style scoped>
.placeholder-page {
    padding-bottom: 2rem;
}

.btn-group {
    display: flex;
    gap: 0.25rem;
}

.card {
    border-radius: 0.5rem;
    overflow: hidden;
}

table {
    margin-bottom: 0;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}

.me-1 { margin-right: 0.25rem; }
.me-2 { margin-right: 0.5rem; }
.ms-2 { margin-left: 0.5rem; }
.mb-3 { margin-bottom: 1rem; }
.mt-2 { margin-top: 0.5rem; }

.form-control.me-2 {
    flex-grow: 1;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}
</style>