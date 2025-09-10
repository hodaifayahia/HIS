<script setup>
import { computed } from 'vue'

const props = defineProps({
  beds: {
    type: Array,
    required: true
  }
})
const getOccupancyClass = (occupancyRate) => {
  if (occupancyRate <= 60) {
    return 'low'
  } else if (occupancyRate <= 85) {
    return 'medium'
  } else {
    return 'high'
  }
}

const bedStatusData = computed(() => {
  const statusCounts = {
    free: 0,
    occupied: 0,
    reserved: 0
  }

  props.beds.forEach(bed => {
    if (statusCounts.hasOwnProperty(bed.status)) {
      statusCounts[bed.status]++
    }
  })

  return [
    { status: 'Free', count: statusCounts.free, color: '#10b981', icon: 'fas fa-bed' },
    { status: 'Occupied', count: statusCounts.occupied, color: '#ef4444', icon: 'fas fa-user-injured' },
    { status: 'Reserved', count: statusCounts.reserved, color: '#8b5cf6', icon: 'fas fa-bookmark' }
  ].map(item => ({
    ...item,
    percentage: props.beds.length > 0 ? Math.round((item.count / props.beds.length) * 100) : 0
  }))
})

const occupancyRate = computed(() => {
  const occupied = bedStatusData.value.find(item => item.status === 'Occupied')?.count || 0
  const total = props.beds.length
  return total > 0 ? Math.round((occupied / total) * 100) : 0
})
</script>

<template>
  <div class="bed-occupancy-chart">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-bed me-2"></i>
          Bed Occupancy Status
        </h5>
      </div>
      <div class="card-body">
        <div v-if="props.beds.length === 0" class="empty-state">
          <i class="fas fa-bed text-muted"></i>
          <p class="text-muted">No bed data available</p>
        </div>
        <div v-else class="occupancy-content">
          <!-- Occupancy Rate Circle -->
          <div class="occupancy-circle-container">
            <div class="occupancy-circle" :class="getOccupancyClass(occupancyRate)">
              <div class="occupancy-inner">
                <span class="occupancy-rate">{{ occupancyRate }}%</span>
                <span class="occupancy-label">Occupied</span>
              </div>
            </div>
          </div>

          <!-- Status Breakdown -->
          <div class="status-breakdown">
            <div v-for="item in bedStatusData" :key="item.status" class="status-item">
              <div class="status-icon" :style="{ color: item.color }">
                <i :class="item.icon"></i>
              </div>
              <div class="status-details">
                <div class="status-name">{{ item.status }}</div>
                <div class="status-count">{{ item.count }} beds</div>
                <div class="status-percentage">{{ item.percentage }}%</div>
              </div>
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

.occupancy-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
}

.occupancy-circle-container {
  display: flex;
  justify-content: center;
}

.occupancy-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: conic-gradient(var(--occupancy-color) 0deg, var(--occupancy-color) calc(var(--occupancy-percentage) * 3.6deg), #f1f5f9 calc(var(--occupancy-percentage) * 3.6deg), #f1f5f9 360deg);
}

.occupancy-circle.low {
  --occupancy-color: #10b981;
}

.occupancy-circle.medium {
  --occupancy-color: #f59e0b;
}

.occupancy-circle.high {
  --occupancy-color: #ef4444;
}

.occupancy-inner {
  width: 80px;
  height: 80px;
  background: white;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.occupancy-rate {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.occupancy-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.status-breakdown {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
}

.status-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 0.5rem;
  font-size: 1.25rem;
}

.status-details {
  flex: 1;
}

.status-name {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.status-count {
  font-size: 0.875rem;
  color: #64748b;
}

.status-percentage {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 500;
}
</style>
