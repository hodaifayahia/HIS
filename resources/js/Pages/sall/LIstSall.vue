<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import sallModel from "../../Components/sallModel.vue";
import doctorSallModel from "../../Components/doctorSallModel.vue";
import { useSweetAlert } from '../../Components/useSweetAlert';
const swal = useSweetAlert();

const salls = ref([])
const loading = ref(false)
const error = ref(null)
const toaster = useToastr();

const getSalls = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get('/api/salls');
    salls.value = response.data.data || response.data;
    console.log(salls.value);
  } catch (error) {
    console.error('Error fetching salls:', error);
    error.value = error.response?.data?.message || 'Failed to load salls';
  } finally {
    loading.value = false;
  }
};

const isSallModalOpen = ref(false);
const isDoctorSallModalOpen = ref(false);
const selectedSall = ref([]);
const selectedSallForDoctors = ref(null);

const openSallModal = (sall = null) => {
  selectedSall.value = sall ? { ...sall } : {};
  isSallModalOpen.value = true;
};

const closeSallModal = () => {
  isSallModalOpen.value = false;
};

const openDoctorSallModal = (sall) => {
  selectedSallForDoctors.value = sall;
  isDoctorSallModalOpen.value = true;
};

const closeDoctorSallModal = () => {
  isDoctorSallModalOpen.value = false;
  selectedSallForDoctors.value = null;
};

const refreshSalls = async () => {
  await getSalls();
};

const deleteSall = async (id) => {
  try {
    const result = await swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
      await axios.delete(`/api/salls/${id}`);
      toaster.success('Sall deleted successfully');
      refreshSalls();
      
      swal.fire(
        'Deleted!',
        'Sall has been deleted.',
        'success'
      );

      closeSallModal();
    }
  } catch (error) {
    if (error.response?.data?.message) {
      swal.fire(
        'Error!',
        error.response.data.message,
        'error'
      );
    } else {
      swal.fire(
        'Error!',
        'Failed to delete Sall.',
        'error'
      );
    }
  }
};

onMounted(() => {
  getSalls();
})
</script>

<template>
  <div class="sall-page">
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Salls</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Salls</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 py-2" title="Add Sall"
                @click="openSallModal">
                <i class="fas fa-plus-circle"></i>
                <span>Add Sall</span>
              </button>
            </div>

            <!-- Sall List -->
            <div class="card shadow-sm">
              <div class="card-body">
                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                  </div>
                </div>

                <table v-else class="table table-hover">
                  <thead>
                    <tr>
                      <th scope="col">#</th>
                      <th scope="col">Name</th>
                      <th scope="col">Number</th>
                      <th scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="salls.length === 0">
                      <td colspan="4" class="text-center">No salls found</td>
                    </tr>
                    <tr v-else v-for="(sall, index) in salls" :key="sall.id" 
                        @click="openDoctorSallModal(sall)" 
                        class="clickable-row">
                      <td>{{ index + 1 }}</td>
                      <td>{{ sall.name }}</td>
                      <td>{{ sall.number }}</td>
                      <td @click.stop>
                        <button @click="openSallModal(sall)" class="btn btn-sm btn-outline-primary me-2">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click="deleteSall(sall.id)"
                          class="btn btn-sm btn-outline-danger ml-1">
                          <i class="fas fa-trash-alt"></i>
                        </button>
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

    <!-- Sall Modal -->
    <sallModel :show-modal="isSallModalOpen" :sall-data="selectedSall" @close="closeSallModal"
      @sallUpdate="refreshSalls" />

    <!-- Doctor Sall Modal -->
    <doctorSallModel :show-modal="isDoctorSallModalOpen" :sall-data="selectedSallForDoctors" 
      @close="closeDoctorSallModal" />
  </div>
</template>

<style scoped>
.clickable-row {
  cursor: pointer;
}

.clickable-row:hover {
  background-color: #f8f9fa;
}
</style>