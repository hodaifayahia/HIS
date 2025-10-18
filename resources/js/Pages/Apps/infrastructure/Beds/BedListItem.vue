<template>
  <div class="bed-card" :class="statusClass">
    <div class="bed-header">
      <h3>Bed {{ bed.bed_identifier }}</h3>
      <span class="status-badge" :class="statusClass">
        {{ bed.status_label }}
      </span>
    </div>
    
    <div class="bed-details">
      <p><strong>Room:</strong> {{ bed.room.room_number }}</p>
      <p><strong>Room Type:</strong> {{ bed.room.room_type }}</p>
      <p><strong>Service:</strong> {{ bed.room.service?.name || 'N/A' }}</p>
     {{ bed }}
      <div v-if="bed.current_patient" class="patient-info">
        <p><strong>Current Patient:</strong> {{ bed.current_patient.name }} </p>
      </div>
    </div>
    
    <div class="bed-actions">
      <button @click="$emit('edit', bed)" class="btn btn-sm btn-secondary">
        Edit
      </button>
      <button @click="$emit('delete', bed)" class="btn btn-sm btn-danger">
        Delete
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BedListItem',
  props: {
    bed: {
      type: Object,
      required: true
    }
  },
  computed: {
    statusClass() {
      return `status-${this.bed.status}`
    }
  }
}
</script>

<style scoped>
.bed-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  background: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.bed-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.bed-header h3 {
  margin: 0;
  color: #333;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
  text-transform: uppercase;
}

.status-free {
  background: #d4edda;
  color: #155724;
}

.status-occupied {
  background: #f8d7da;
  color: #721c24;
}

.status-reserved {
  background: #fff3cd;
  color: #856404;
}

.bed-details p {
  margin: 5px 0;
  color: #666;
}

.patient-info {
  margin-top: 10px;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 4px;
}

.bed-actions {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}

.btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-sm {
  padding: 4px 8px;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-danger {
  background: #dc3545;
  color: white;
}
</style>
