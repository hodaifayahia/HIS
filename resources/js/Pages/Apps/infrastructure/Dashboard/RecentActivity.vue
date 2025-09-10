<script setup>
import { computed, ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  rooms: {
    type: Array,
    required: true
  },
  beds: {
    type: Array,
    required: true
  }
})

const activities = ref([])
const isLoading = ref(false)

const fetchRecentActivity = async () => {
  isLoading.value = true
  try {
    // This would be a real API endpoint that tracks infrastructure changes
    const response = await axios.get('/api/infrastructure/recent-activity')
    activities.value = response.data.data || response.data
  } catch (error) {
    // Fallback to mock data if API doesn't exist yet
    activities.value = generateMockActivity()
  } finally {
    isLoading.value = false
  }
}

const generateMockActivity = () => {
  const mockActivities = []
  const now = new Date()
  
  // Generate some mock recent activities
  for (let i = 0; i < 10; i++) {
    const date = new Date(now.getTime() - (i * 60 * 60 * 1000)) // Hours ago
    mockActivities.push({
      id: i + 1,
      type: ['room_status_change', 'bed_assignment', 'pavilion_update'][Math.floor(Math.random() * 3)],
      description: [
        'Room 101 status changed to Available',
        'Bed A assigned to patient John Doe',
        'Pavilion North Wing updated',
        'Room 205 moved to Maintenance',
        'Bed B freed from patient assignment'
      ][Math.floor(Math.random() * 5)],
      timestamp: date.toISOString(),
      user: 'System Admin'
    })
  }
  
  return mockActivities
}

const formatTimestamp = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const hours = Math.floor(diff / (1000 * 60 * 60))
  
  if (hours < 1) return 'Just now'
  if (hours === 1) return '1 hour ago'
  return `${hours} hours ago`
}

const getActivityIcon = (type) => {
  switch (type) {
    case 'room_status_change': return 'fas fa-door-open'
    case 'bed_assignment': return 'fas fa-bed'
    case 'pavilion_update': return 'fas fa-building'
    default: return 'fas fa-info-circle'
  }
}

const getActivityColor = (type) => {
  switch (type) {
    case 'room_status_change': return '#17a2b8'
    case 'bed_assignment': return '#28a745'
    case 'pavilion_update': return '#007bff'
    default: return '#6c757d'
  }
}

onMounted(() => {
  fetchRecentActivity()
})
</script>

<template>
  <div class="recent-activity">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-clock me-2"></i>
          Recent Activity
        </h5>
      </div>
      <div class="card-body">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <span class="ms-2">Loading recent activity...</span>
        </div>
        <div v-else-if="activities.length === 0" class="empty-state">
          <i class="fas fa-clock text-muted"></i>
          <p class="text-muted">No recent activity</p>
        </div>
        <div v-else class="activity-timeline">
          <div v-for="activity in activities" :key="activity.id" class="activity-item">
            <div class="activity-icon" :style="{ color: getActivityColor(activity.type) }">
              <i :class="getActivityIcon(activity.type)"></i>
            </div>
            <div class="activity-content">
              <div class="activity-description">{{ activity.description }}</div>
              <div class="activity-meta">
                <span class="activity-user">{{ activity.user }}</span>
                <span class="activity-time">{{ formatTimestamp(activity.timestamp) }}</span>
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

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #64748b;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.activity-timeline {
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 50%;
  font-size: 1rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-description {
  font-weight: 500;
  color: #1e293b;
  margin-bottom: 0.25rem;
  line-height: 1.4;
}

.activity-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  color: #64748b;
}

.activity-user {
  font-weight: 500;
}

.activity-time {
  color: #94a3b8;
}
</style>
