<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios' // Import axios
import { RoomStatus } from '../../../../Components/models/Room.js' // Ensure this path is correct
// createRoom and validateRoom might still be useful for client-side logic,
// but the actual data handling will be via API.
// getSampleRooms will be removed as we're fetching real data.
// import { createRoom, validateRoom, getSampleRooms } from '../../../../Components/models/Room.js'
import RoomModal from '../../../../Components/Apps/infrastructure/rooms/room/RoomModel.vue'
import RoomBedsModal from '../../../../Components/Apps/infrastructure/rooms/room/RoomBedsModal.vue'
import RoomListItem from './RoomListItem.vue'
import { useRoute } from 'vue-router'
import { useToastr } from '../../../../Components/toster.js'
import { useSweetAlert } from '../../../../Components/useSweetAlert.js'

const  toaster = useToastr()
const swal = useSweetAlert()

const route = useRoute();

// Reactive data
const rooms = ref([])
const searchQuery = ref('')
const statusFilter = ref('')
const showModal = ref(false)
const selectedRoom = ref(null)
const isEditMode = ref(false)
const isLoading = ref(false) // Add loading state
const generalError = ref(null); // To display general API errors

const service_id = ref(route.query.service_id ? parseInt(route.query.service_id) : null);
const room_type_id = ref(route.params.typeId ? parseInt(route.params.typeId) : null); // Renamed typeId to room_type_id for clarity

// Computed properties
const availableRooms = computed(() =>
  rooms.value.filter(room => room.status === RoomStatus.AVAILABLE).length
)

const occupiedRooms = computed(() =>
  rooms.value.filter(room => room.status === RoomStatus.OCCUPIED).length
)

const filteredRooms = computed(() => {
  let filtered = rooms.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(room =>
      room.name.toLowerCase().includes(query) ||
      room.room_number.toLowerCase().includes(query) ||
      room.location.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value) {
    filtered = filtered.filter(room => room.status === statusFilter.value)
  }

  return filtered
})

const showBedsModal = ref(false)

const handleViewBeds = (room) => {
    // Check if room type is not "WaitingRoom"
    const roomTypeName = room.room_type?.room_type || room.room_type?.name
    
    if (roomTypeName && roomTypeName.toLowerCase() === 'waitingroom') {
        toaster.warning('Beds management is not available for waiting rooms')
        return
    }
    
    selectedRoom.value = room
    showBedsModal.value = true
}

const closeBedsModal = () => {
  showBedsModal.value = false
  selectedRoom.value = null
}

const handleBedUpdated = () => {
  // Refresh room data if needed
  // You can emit an event or call a refresh function here
}

// Methods
// Methods
const fetchRooms = async () => {
    isLoading.value = true;
    generalError.value = null;
    try {
        let url = '/api/rooms';
        const params = {};

        if (room_type_id.value) {
            params.room_type_id = room_type_id.value;
        }
        if (service_id.value) {
            params.service_id = service_id.value;
        }

        // Make sure to include room type relationship
        params.with = 'roomType';

        const response = await axios.get(url, { params });
        rooms.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error fetching rooms:', error);
        generalError.value = 'Failed to load rooms. Please try again.';
    } finally {
        isLoading.value = false;
    }
}

const openAddModal = () => {
  selectedRoom.value = null
  isEditMode.value = false
  showModal.value = true
}

const handleEdit = (room) => {
  selectedRoom.value = room
  isEditMode.value = true
  showModal.value = true
}
const handleDelete = async (roomToDelete) => {
    try {
        const result = await swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            // Show loading state during deletion
            isLoading.value = true;
            console.log('roomToDelete',roomToDelete)
            await axios.delete(`/api/rooms/${roomToDelete.id}`);
            
            // ✅ Fixed: Use roomToDelete.id instead of undefined 'id'
            rooms.value = rooms.value.filter(room => room.id !== roomToDelete.id);
            
            // ✅ Fixed: Consistent messaging about rooms
            toaster.success('Room deleted successfully');
            swal.fire('Deleted!', 'Room has been deleted.', 'success');
            
            // Optional: Refresh data from server to ensure consistency
            // await fetchRooms();
        }
    } catch (error) {
        console.error('Error deleting room:', error);
        // ✅ Fixed: Consistent error messaging
        const errorMessage = error.response?.data?.message || 'Failed to delete room.';
        toaster.error(errorMessage);
        swal.fire('Error!', errorMessage, 'error');
    } finally {
        isLoading.value = false;
    }
};


const handleView = (room) => {
  console.log('View room:', room)
  // Implement view functionality, e.g., navigate to a detail page
  // router.push({ name: 'room-details', params: { id: room.id } });
}

const closeModal = () => {
  showModal.value = false
  selectedRoom.value = null
  isEditMode.value = false
}

// const handleModalSubmit = (newRoom) => {
//     rooms.value.unshift(newRoom); // Add to the beginning for immediate visibility
//     closeModal();
// };

const handleModalSubmit = (newRoom) => {
    const index = rooms.value.findIndex(s => s.id === newRoom.id);
    if (index !== -1) {
        rooms.value[index] = newRoom; // Replace the old object with the updated one
    }
    closeModal();
};


// Initial data load when the component is mounted
onMounted(() => {
  fetchRooms();
});
</script>

<template>
  <div class="min-vh-100" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="bg-white shadow-sm border-bottom">
      <div class="container-fluid px-4">
        <div class="py-4">
          <div class="row align-items-center">
            <div class="col-md-6">
              <h1 class="h2 text-dark fw-bold mb-2">Room Management</h1>
              <p class="text-muted mb-0">Manage your hotel rooms efficiently</p>
            </div>
            <div class="col-md-6">
              <div class="row g-3 justify-content-end">
                <div class="col-auto">
                  <div class="card text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);">
                    <div class="card-body text-center py-3 px-4">
                      <div class="fs-4 fw-bold">{{ rooms.length }}</div>
                      <div class="small opacity-75">Total Rooms</div>
                    </div>
                  </div>
                </div>
                <div class="col-auto">
                  <div class="card text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="card-body text-center py-3 px-4">
                      <div class="fs-4 fw-bold">{{ availableRooms }}</div>
                      <div class="small opacity-75">Available</div>
                    </div>
                  </div>
                </div>
                <div class="col-auto">
                  <div class="card text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);">
                    <div class="card-body text-center py-3 px-4">
                      <div class="fs-4 fw-bold">{{ occupiedRooms }}</div>
                      <div class="small opacity-75">Occupied</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid px-4 py-4">
      <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">
          <div class="row g-3 align-items-center">
            <div class="col-md-6">
              <div class="position-relative">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search rooms..."
                  class="form-control form-control-lg ps-5 border-2 rounded-3"
                  style="border-color: #dee2e6 !important;"
                >
              </div>
            </div>
            <div class="col-md-3">
              <select
                v-model="statusFilter"
                class="form-select form-select-lg border-2 rounded-3"
                style="border-color: #dee2e6 !important;"
              >
                <option value="">All Status</option>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Maintenance</option>
                <option value="reserved">Reserved</option>
              </select>
            </div>
            <div class="col-md-3">
              <button
                @click="openAddModal"
                class="btn btn-lg w-100 text-white border-0 rounded-3 shadow-sm"
                style="background: #007bff"
              >
                <i class="fas fa-plus me-2"></i>Add Room
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid px-4 pb-5">
      <div v-if="generalError" class="alert alert-danger mb-4" role="alert">
        {{ generalError }}
      </div>

      <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
          <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2">Loading rooms...</p>
          </div>
          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="border-0 px-4 py-3">
                    <div class="fw-bold text-dark">Room</div>
                  </th>
                  <th class="border-0 px-4 py-3">
                    <div class="fw-bold text-dark">Room Number</div>
                  </th>
                  <th class="border-0 px-4 py-3">
                    <div class="fw-bold text-dark">Location</div>
                  </th>
                  <th class="border-0 px-4 py-3">
                    <div class="fw-bold text-dark">Number of People</div>
                  </th>
                  <th class="border-0 px-4 py-3">
                    <div class="fw-bold text-dark">Status</div>
                  </th>
                  <th class="border-0 px-4 py-3">
                    <div class="fw-bold text-dark">Actions</div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <RoomListItem
                  v-for="room in filteredRooms"
                  :key="room.id"
                  :room="room"
                 @view-beds="handleViewBeds"
                  @view="handleView"
                  @edit="handleEdit"
                  @delete="handleDelete"
                />
              </tbody>
            </table>
          </div>

          <div v-if="!isLoading && filteredRooms.length === 0" class="text-center py-5">
            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-4" style="width: 64px; height: 64px;">
              <i class="fas fa-bed text-muted fs-2"></i>
            </div>
            <h3 class="h5 text-dark mb-2">No rooms found</h3>
            <p class="text-muted">Try adjusting your search or filter criteria, or add a new room.</p>
          </div>
        </div>
      </div>
    </div>
     <RoomBedsModal
    :show-modal="showBedsModal"
    :room="selectedRoom"
    @close="closeBedsModal"
    @bed-updated="handleBedUpdated"
  />

    <RoomModal
      :show="showModal"
      :room="selectedRoom"
      :is-edit="isEditMode"
      :room_type_id="room_type_id"
      :service_id="service_id"
      @close="closeModal"
      @submit="handleModalSubmit"
      
    />
  </div>
</template>

<style scoped>
.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02);
}
</style>