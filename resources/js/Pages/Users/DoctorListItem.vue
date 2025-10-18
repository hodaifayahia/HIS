<script setup>
import { defineProps, defineEmits, ref, onMounted } from 'vue';
import DoctorModel from '../../Components/DoctorModel.vue';
import { useRouter } from 'vue-router';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { useAuthStore } from '../../stores/auth';

import axios from 'axios';

const swal = useSweetAlert();
const router = useRouter();

// Props and emits
const props = defineProps({
  doctor: { type: Object, required: true },
  index: { type: Number, required: true },
  selectAll: { type: Boolean, default: false }
});

// events: doctorUpdated, doctorDeleted
const emit = defineEmits(['doctorUpdated', 'doctorDeleted']);

// Refs
const showDeleteModel = ref(false);
const isModalOpen = ref(false);
const selectedUser = ref(null);
const userRole = ref(null); // Track user role
const showDuplicateModal = ref(false);
const duplicateForm = ref({
  name: '',
  email: '',
  password: '',
  phone: ''
});


const authStore = useAuthStore();
// Guard for user role
onMounted(() => {
  userRole.value = authStore.user ? authStore.user.role : null;
});


// Modal and user actions
const closeModal = () => {
  isModalOpen.value = false;
  showDeleteModel.value = false;
  showDuplicateModal.value = false;
  selectedUser.value = null;
  duplicateForm.value = {
    name: '',
    email: '',
    password: '',
    phone: ''
  };
};

const editUser = () => {
  selectedUser.value = { ...props.doctor };
  isModalOpen.value = true;
};

const openDeleteModal = () => {
  selectedUser.value = { ...props.doctor };
  showDeleteModel.value = true;
};

const openDuplicateModal = () => {
  showDuplicateModal.value = true;
  duplicateForm.value = {
    name: '',
    email: '',
    password: '',
    phone: props.doctor.phone || ''
  };
};

const handleDuplicate = async () => {
  if (!duplicateForm.value.name || !duplicateForm.value.email || !duplicateForm.value.password) {
    swal.fire('Error!', 'Please fill in all required fields.', 'error');
    return;
  }

  try {
    const response = await axios.post(`/api/doctors/${props.doctor.id}/duplicate`, duplicateForm.value);
    
    if (response.data.message) {
      swal.fire('Success!', response.data.message, 'success');
      emit('doctorUpdated');
      closeModal();
    }
  } catch (error) {
    if (error.response?.data?.message) {
      swal.fire('Error!', error.response.data.message, 'error');
    } else if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat().join('\n');
      swal.fire('Validation Error!', errors, 'error');
    } else {
      swal.fire('Error!', 'Failed to duplicate doctor.', 'error');
    }
  }
};

const handleUserUpdate = () => {
  emit('doctorUpdated');
  closeModal();
};

// Format time
const formatTime = (time) => {
  if (!time) return '';
  const [hours, minutes] = time.split(':');
  const hour = parseInt(hours, 10);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12; 
  return `${formattedHour}:${minutes} ${ampm}`;
};

// Navigate to doctor schedule page
const goToDoctorSchdulePage = (doctorId) => {
  router.push({ name: 'admin.doctors.schedule', params: { id: doctorId } });
};

// Handle delete
const handleDelete = async (id) => {
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
      await axios.delete(`/api/doctors/${id}`, { params: { id: props.doctor.id } });
      swal.fire('Deleted!', 'Doctor has been deleted.', 'success');
      emit('doctorDeleted');
      closeModal();
    }
  } catch (error) {
    if (error.response?.data?.message) {
      swal.fire('Error!', error.response.data.message, 'error');
    } else {
      swal.fire('Error!', 'Failed to delete Doctor.', 'error');
    }
  }
};
</script>

<template>
  <div class="d-flex gap-2">
    <button 
      class="btn btn-sm btn-outline-primary" 
      title="Edit"
      @click.stop="editUser"
    >
      <i class="fas fa-edit"></i>
    </button>
    <button 
      class="btn btn-sm btn-outline-success" 
      title="Duplicate Configuration"
      @click.stop="openDuplicateModal"
    >
      <i class="fas fa-copy"></i>
    </button>
    <button 
      class="btn btn-sm btn-outline-danger" 
      title="Delete"
      @click.stop="handleDelete(doctor.id)"
    >
      <i class="fas fa-trash-alt"></i>
    </button>

    <!-- Modals -->
    <Teleport to="body">
      <DoctorModel
        v-if="isModalOpen"
        :show-modal="isModalOpen"
        :doctor-data="selectedUser"
        @close="closeModal"
        @doctorUpdated="handleUserUpdate"
      />

      <!-- Duplicate Doctor Modal -->
      <div 
        v-if="showDuplicateModal" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background-color: rgba(0,0,0,0.5);"
      >
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="fas fa-copy mr-2"></i>
                Duplicate Doctor Configuration
              </h5>
              <button type="button" class="close" @click="closeModal">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p class="text-muted mb-3">
                <i class="fas fa-info-circle"></i>
                This will copy <strong>all configurations</strong> from 
                <strong>{{ doctor.name }}</strong>. Only enter new credentials below.
              </p>
              
              <div class="form-group">
                <label for="duplicate-name">
                  Doctor Name <span class="text-danger">*</span>
                </label>
                <input 
                  id="duplicate-name"
                  type="text" 
                  class="form-control" 
                  v-model="duplicateForm.name"
                  placeholder="Enter new doctor's name"
                  required
                />
              </div>

              <div class="form-group">
                <label for="duplicate-email">
                  Email (Username) <span class="text-danger">*</span>
                </label>
                <input 
                  id="duplicate-email"
                  type="email" 
                  class="form-control" 
                  v-model="duplicateForm.email"
                  placeholder="Enter new doctor's email"
                  required
                />
              </div>

              <div class="form-group">
                <label for="duplicate-password">
                  Password <span class="text-danger">*</span>
                </label>
                <input 
                  id="duplicate-password"
                  type="password" 
                  class="form-control" 
                  v-model="duplicateForm.password"
                  placeholder="Enter password (min 8 characters)"
                  required
                />
              </div>

              <div class="form-group">
                <label for="duplicate-phone">Phone (Optional)</label>
                <input 
                  id="duplicate-phone"
                  type="text" 
                  class="form-control" 
                  v-model="duplicateForm.phone"
                  placeholder="Enter phone number"
                />
              </div>

              <div class="alert alert-info mt-3">
                <strong><i class="fas fa-calendar-check"></i> Schedule Configuration:</strong>
                <ul class="mb-2 mt-2">
                  <li>Specialization & time settings</li>
                  <li>All recurring schedules (weekly)</li>
                  <li>Custom date schedules</li>
                  <li>Appointment booking windows</li>
                  <li>Excluded dates</li>
                </ul>
                
                <strong><i class="fas fa-stethoscope"></i> Consultation Configuration:</strong>
                <ul class="mb-0 mt-2">
                  <li>Template folders</li>
                  <li>Consultation templates</li>
                  <li>Sections/Placeholders with attributes</li>
                  <li>Medication favorites</li>
                </ul>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeModal">
                Cancel
              </button>
              <button type="button" class="btn btn-success" @click="handleDuplicate">
                <i class="fas fa-copy mr-1"></i>
                Duplicate Doctor
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.doctor-item {
  transition: background-color 0.3s ease;
}

.doctor-item:hover {
  background-color: rgba(0, 123, 255, 0.075);
}

.select-column {
  width: 5%;
}

.doctor-name, .doctor-email, .doctor-phone, .doctor-specialization, .doctor-frequency, .doctor-start-time, .doctor-end-time, .doctor-patients {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.doctor-name {
  max-width: 150px;
}

.doctor-email, .doctor-phone {
  max-width: 180px;
}

.doctor-specialization, .doctor-frequency {
  max-width: 120px;
}

.doctor-start-time, .doctor-end-time {
  width: 100px;
}

.doctor-patients {
  width: 150px;
}

.doctor-actions {
  width: 120px;
}

.btn-group .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
}

.btn-sm {
  border-radius: 50px;
}
</style>