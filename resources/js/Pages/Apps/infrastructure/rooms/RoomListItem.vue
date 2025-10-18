<!-- components/RoomTableRow.vue -->
<script setup>
import { getStatusClasses, formatPrice } from '../../../../Components/models/Room.js'

const props = defineProps({
  room: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['view', 'edit', 'delete', 'view-beds'])

const handleView = () => {
  emit('view', props.room)
}

const handleEdit = () => {
  emit('edit', props.room)
}

const handleDelete = () => {
  emit('delete', props.room)
}

const handleViewBeds = () => {
  emit('view-beds', props.room)
}

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getRoomTypeLabel = (typeId) => {
  const types = {
    1: 'Deluxe',
    2: 'Standard',
    3: 'Presidential',
    4: 'Economy',
    5: 'Family'
  }
  return types[typeId] || 'Standard'
}
</script>

<template>
  <tr class="border-bottom room-row" @click="handleViewBeds" style="cursor: pointer;">
    <!-- Room Info -->
    <td class="px-4 py-3">
      <div class="d-flex align-items-center">
        <div class="me-3">
          <div class="rounded-3 overflow-hidden rounded-pill room-thumbnail">
            <div
              class="w-100 h-100 d-flex align-items-center justify-content-center text-white "
              style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);"
            >
              <i class="fas fa-bed"></i>
            </div>
          </div>
        </div>
        <div>
          <div class="fw-bold text-dark">{{ room.name }}</div>
          <div class="small text-muted">{{ getRoomTypeLabel(room.room_type_id) }}</div>
        </div>
      </div>
    </td>

    <!-- Room Number -->
    <td class="px-4 py-3">
      <div class="fw-semibold text-dark">{{ room.room_number }}</div>
    </td>

    <!-- Location -->
    <td class="px-4 py-3">
      <div class="d-flex align-items-center text-muted">
        <i class="fas fa-map-marker-alt me-2"></i>
        {{ room.location }}
      </div>
    </td>

    <!-- Price -->
    <td class="px-4 py-3">
      <div class="fw-bold text-dark">{{ formatPrice(room.nightly_price) }}</div>
      <div class="small text-muted">per night</div>
    </td>

    <!-- Status -->
    <td class="px-4 py-3">
      <span :class="getStatusClasses(room.status)" class="badge px-3 py-2 rounded-pill">
        {{ formatStatus(room.status) }}
      </span>
    </td>

    <!-- Actions -->
    <td class="px-4 py-3" @click.stop>
      <div class="d-flex gap-2">
        <button
          @click="handleEdit"
          class="btn btn-outline-primary btn-sm rounded-3"
          title="Edit Room"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button
          @click="handleDelete"
          class="btn btn-outline-danger btn-sm rounded-3"
          title="Delete Room"
        >
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </td>
  </tr>
</template>

<style scoped>
.room-thumbnail {
  width: 60px;
  height: 60px;
}

.object-fit-cover {
  object-fit: cover;
}

.room-row:hover {
  background-color: rgba(0, 0, 0, 0.02);
  transform: translateY(-1px);
  transition: all 0.2s ease;
}

.border-bottom {
  border-bottom: 1px solid #dee2e6 !important;
}
</style>
