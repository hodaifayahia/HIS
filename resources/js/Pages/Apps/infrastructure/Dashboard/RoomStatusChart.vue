<script setup>
import { computed, ref, onMounted } from 'vue'

const props = defineProps({
  rooms: {
    type: Array,
    required: true
  }
})

const chartContainer = ref(null)

const roomStatusData = computed(() => {
  const statusCounts = {
    available: 0,
    occupied: 0,
    maintenance: 0,
    reserved: 0
  }

  props.rooms.forEach(room => {
    if (statusCounts.hasOwnProperty(room.status)) {
      statusCounts[room.status]++
    }
  })

  return [
    { status: 'Available', count: statusCounts.available, color: '#10b981', percentage: 0 },
    { status: 'Occupied', count: statusCounts.occupied, color: '#f59e0b', percentage: 0 },
    { status: 'Maintenance', count: statusCounts.maintenance, color: '#ef4444', percentage: 0 },
    { status: 'Reserved', count: statusCounts.reserved, color: '#8b5cf6', percentage: 0 }
  ].map(item => ({
    ...item,
    percentage: props.rooms.length > 0 ? Math.round((item.count / props.rooms.length) * 100) : 0
  }))
})

const totalRooms = computed(() => props.rooms.length)
</script>

<template>
  <div class="room-status-chart">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-chart-pie me-2"></i>
          Room Status Distribution
        </h5>
      </div>
      <div class="card-body">
        <div v-if="totalRooms === 0" class="empty-state">
          <i class="fas fa-chart-pie text-muted"></i>
          <p class="text-muted">No room data available</p>
        </div>
        <div v-else class="chart-content">
          <div class="status-bars">
            <div v-for="item in roomStatusData" :key="item.status" class="status-bar-item">
              <div class="status-info">
                <div class="status-label">
                  <span class="status-dot" :style="{ backgroundColor: item.color }"></span>
                  {{ item.status }}
                </div>
                <div class="status-value">{{ item.count }} ({{ item.percentage }}%)</div>
              </div>
              <div class="status-bar">
                <div 
                  class="status-fill" 
                  :style="{ 
                    width: item.percentage + '%', 
                    backgroundColor: item.color 
                  }"
                ></div>
              </div>
            </div>
          </div>
          <div class="chart-summary">
            <div class="summary-item">
              <span class="summary-label">Total Rooms</span>
              <span class="summary-value">{{ totalRooms }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
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

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.status-bars {
  margin-bottom: 2rem;
}

.status-bar-item {
  margin-bottom: 1.5rem;
}

.status-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.status-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.status-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.status-value {
  font-weight: 600;
  color: #1e293b;
}

.status-bar {
  height: 8px;
  background-color: #f1f5f9;
  border-radius: 4px;
  overflow: hidden;
}

.status-fill {
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 4px;
}

.chart-summary {
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-label {
  color: #64748b;
  font-weight: 500;
}

.summary-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}
</style>
