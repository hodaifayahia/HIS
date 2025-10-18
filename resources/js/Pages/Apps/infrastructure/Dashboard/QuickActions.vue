<script setup>
import { ref } from 'vue'
import BedModel from "../../../../Components/Apps/infrastructure/bad/BedModel.vue"
import PavilionModel from '../../../../Components/Apps/infrastructure/Pavilion/PavilionModel.vue'
import RoomTypeModel from '../../../../Components/Apps/infrastructure/rooms/roomstype/RoomTypeModel.vue'
import RoomModal from '../../../../Components/Apps/infrastructure/rooms/room/RoomModel.vue'

const emit = defineEmits(['refresh'])

// Modal states
const showBedModal = ref(false)
const showPavilionModal = ref(false)
const showRoomTypeModal = ref(false)
const showRoomModal = ref(false)

// Selected data for editing (null for creating new items)
const selectedBed = ref(null)
const selectedPavilion = ref(null)
const selectedRoomType = ref(null)
const selectedRoom = ref(null)

// Props that might be needed for room creation
const rooms = ref([])
const patients = ref([])
const room_type_id = ref(null)
const service_id = ref(null)

const quickActions = ref([
  {
    title: 'Add Pavilion',
    icon: 'fas fa-plus',
    color: 'primary',
    action: () => openPavilionModal()
  },
  {
    title: 'Add Room Type',
    icon: 'fas fa-th-large',
    color: 'warning',
    action: () => openRoomTypeModal()
  },
  {
    title: 'Add Room',
    icon: 'fas fa-door-open',
    color: 'success',
    action: () => openRoomModal()
  },
  {
    title: 'Add Bed',
    icon: 'fas fa-bed',
    color: 'info',
    action: () => openBedModal()
  },
  {
    title: 'Refresh Data',
    icon: 'fas fa-sync-alt',
    color: 'secondary',
    action: () => emit('refresh')
  }
])

// Modal opening functions
const openBedModal = () => {
  selectedBed.value = null
  showBedModal.value = true
}

const openPavilionModal = () => {
  selectedPavilion.value = null
  showPavilionModal.value = true
}

const openRoomTypeModal = () => {
  selectedRoomType.value = null
  showRoomTypeModal.value = true
}

const openRoomModal = () => {
  selectedRoom.value = null
  showRoomModal.value = true
}

// Modal closing functions
const closeBedModal = () => {
  showBedModal.value = false
  selectedBed.value = null
}

const closePavilionModal = () => {
  showPavilionModal.value = false
  selectedPavilion.value = null
}

const closeRoomTypeModal = () => {
  showRoomTypeModal.value = false
  selectedRoomType.value = null
}

const closeRoomModal = () => {
  showRoomModal.value = false
  selectedRoom.value = null
}

// Event handlers
const handleBedAdded = (bedData) => {
  closeBedModal()
  emit('refresh')
}

const handleBedUpdated = (bedData) => {
  closeBedModal()
  emit('refresh')
}

const refreshPavilions = () => {
  closePavilionModal()
  emit('refresh')
}

const refreshRoomTypes = () => {
  closeRoomTypeModal()
  emit('refresh')
}

const handleRoomSubmit = (roomData) => {
  closeRoomModal()
  emit('refresh')
}
</script>

<template>
  <div class="quick-actions">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-bolt me-2"></i>
          Quick Actions
        </h5>
      </div>
      <div class="card-body">
        <div class="actions-grid">
          <button 
            v-for="action in quickActions" 
            :key="action.title"
            @click="action.action"
            class="action-button"
            :class="`btn-${action.color}`"
          >
            <i :class="action.icon"></i>
            <span>{{ action.title }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Bed Modal -->
    <BedModel
      :show-modal="showBedModal"
      :bed-data="selectedBed"
      :rooms="rooms"
      :patients="patients"
      @close="closeBedModal"
      @bed-added="handleBedAdded"
      @bed-updated="handleBedUpdated"
    />

    <!-- Pavilion Modal -->
    <PavilionModel
      :show-modal="showPavilionModal"
      :pavilion-data="selectedPavilion"
      @close="closePavilionModal"
      @pavilion-updated="refreshPavilions"
      @pavilion-added="refreshPavilions"
    />

    <!-- Room Type Modal -->
    <RoomTypeModel
      :show-modal="showRoomTypeModal"
      :room-type-data="selectedRoomType"
      @close="closeRoomTypeModal"
      @room-type-updated="refreshRoomTypes"
      @room-type-added="refreshRoomTypes"
    />

    <!-- Room Modal -->
    <RoomModal
      :show="showRoomModal"
      :room="selectedRoom"
      :is-edit="false"
      :room_type_id="room_type_id"
      :service_id="service_id"
      @close="closeRoomModal"
      @submit="handleRoomSubmit"
    />
  </div>
</template>

<style scoped>
.card {
  border: none;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border-radius: 1rem;
}

.card-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #dee2e6;
  border-radius: 1rem 1rem 0 0;
  padding: 1.5rem;
}

.card-title {
  color: #1e293b;
  font-weight: 600;
}

.actions-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.action-button {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border: none;
  border-radius: 0.75rem;
  font-weight: 500;
  transition: all 0.2s ease;
  text-align: left;
  width: 100%;
}

.action-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.action-button i {
  font-size: 1.125rem;
}

.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
}

.btn-success {
  background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
  color: white;
}

.btn-info {
  background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
  color: white;
}

.btn-secondary {
  background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
  color: white;
}

.btn-warning {
  background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
  color: white;
}
</style>
