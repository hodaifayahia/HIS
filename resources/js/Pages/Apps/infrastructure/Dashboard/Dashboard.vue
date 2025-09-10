<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import axios from 'axios'
import { useToastr } from '../../../../Components/toster'
import StatsCards from './StatsCards.vue'
import PavilionOverview from './PavilionOverview.vue'
import RoomStatusChart from './RoomStatusChart.vue'
import BedOccupancyChart from './BedOccupancyChart.vue'
import RecentActivity from './RecentActivity.vue'
import QuickActions from './QuickActions.vue'

const toaster = useToastr()
const isLoading = ref(true)
const isRefreshing = ref(false)

// Separate loading states for progressive loading
const loadingStates = ref({
  stats: true,
  pavilions: true,
  rooms: true,
  beds: true,
  roomTypes: true
})

const dashboardData = ref({
  stats: {
    totalPavilions: 0,
    totalRooms: 0,
    totalBeds: 0,
    occupiedBeds: 0,
    availableRooms: 0,
    occupiedRooms: 0,
    maintenanceRooms: 0
  },
  pavilions: [],
  rooms: [],
  beds: [],
  roomTypes: [],
  recentActivity: []
})

const occupancyRate = computed(() => {
  const total = dashboardData.value.stats.totalBeds
  const occupied = dashboardData.value.stats.occupiedBeds
  return total > 0 ? Math.round((occupied / total) * 100) : 0
})

const getOccupancyClass = (occupancyRate) => {
  if (occupancyRate <= 60) return 'low'
  if (occupancyRate <= 85) return 'medium'
  return 'high'
}

// Check if all data is loaded
const allDataLoaded = computed(() => {
  return !Object.values(loadingStates.value).some(state => state)
})

// Progressive data loading for better UX
const fetchStatsData = async () => {
  try {
    const response = await axios.get('/api/dashboard/infrastructure/stats')
    dashboardData.value.stats = response.data
    loadingStates.value.stats = false
  } catch (error) {
    console.error('Error fetching stats:', error)
    loadingStates.value.stats = false
  }
}

const fetchPavilionsData = async () => {
  try {
    const response = await axios.get('/api/pavilions?with=services')
    dashboardData.value.pavilions = response.data.data || response.data
    loadingStates.value.pavilions = false
  } catch (error) {
    console.error('Error fetching pavilions:', error)
    loadingStates.value.pavilions = false
  }
}

const fetchRoomsData = async () => {
  try {
    const response = await axios.get('/api/rooms?with=roomType,pavilion,service')
    dashboardData.value.rooms = response.data.data || response.data
    loadingStates.value.rooms = false
  } catch (error) {
    console.error('Error fetching rooms:', error)
    loadingStates.value.rooms = false
  }
}

const fetchBedsData = async () => {
  try {
    const response = await axios.get('/api/beds?with=room,currentPatient')
    dashboardData.value.beds = response.data.data || response.data
    loadingStates.value.beds = false
  } catch (error) {
    console.error('Error fetching beds:', error)
    loadingStates.value.beds = false
  }
}

const fetchRoomTypesData = async () => {
  try {
    const response = await axios.get('/api/room-types?with=service')
    dashboardData.value.roomTypes = response.data.data || response.data
    loadingStates.value.roomTypes = false
  } catch (error) {
    console.error('Error fetching room types:', error)
    loadingStates.value.roomTypes = false
  }
}

// Optimized data fetching with progressive loading
const fetchDashboardData = async (isRefresh = false) => {
  if (isRefresh) {
    isRefreshing.value = true
  } else {
    isLoading.value = true
  }

  try {
    // Reset loading states
    Object.keys(loadingStates.value).forEach(key => {
      loadingStates.value[key] = true
    })

    // Fetch stats first (most important for header)
    await fetchStatsData()
    
    // Then fetch other data progressively
    await Promise.all([
      fetchPavilionsData(),
      fetchRoomsData(),
      fetchBedsData(),
      fetchRoomTypesData()
    ])

    // Small delay to ensure smooth transitions
    await nextTick()
    
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
    toaster.error('Failed to load dashboard data')
  } finally {
    isLoading.value = false
    isRefreshing.value = false
  }
}

// Optimized refresh function
const handleRefresh = async () => {
  await fetchDashboardData(true)
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="infrastructure-dashboard">
    <!-- Header - Always visible -->
    <div class="dashboard-header">
      <div class="container-fluid px-4 py-4">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="dashboard-title">Infrastructure Dashboard</h1>
            <p class="dashboard-subtitle">Monitor and manage your facility infrastructure</p>
          </div>
          <div class="col-md-6 text-end">
            <div class="occupancy-indicator">
              <div class="occupancy-circle" :class="getOccupancyClass(occupancyRate)">
                <span class="occupancy-percentage">{{ occupancyRate }}%</span>
              </div>
              <span class="occupancy-label">Bed Occupancy</span>
              <div v-if="isRefreshing" class="refresh-indicator">
                <i class="fas fa-sync-alt fa-spin text-primary"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Initial Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden"></span>
        </div>
      </div>
      <p class="loading-text">Loading dashboard data...</p>
      <div class="loading-progress">
        <div class="progress-bar" :style="{ width: '100%' }"></div>
      </div>
    </div>

    <!-- Dashboard Content with Progressive Loading -->
    <div v-else class="dashboard-content">
      <div class="container-fluid px-4">
        <!-- Stats Cards Row - Load First -->
        <div class="row mb-4">
          <div class="col-12">
            <Transition name="fade-slide" appear>
              <StatsCards 
                v-if="!loadingStates.stats" 
                :stats="dashboardData.stats" 
              />
              <div v-else class="stats-skeleton">
                <div class="skeleton-card" v-for="i in 6" :key="i"></div>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
          <div class="col-lg-6 mb-4">
            <Transition name="fade-slide" appear>
              <RoomStatusChart 
                v-if="!loadingStates.rooms" 
                :rooms="dashboardData.rooms" 
              />
              <div v-else class="chart-skeleton"></div>
            </Transition>
          </div>
          <div class="col-lg-6 mb-4">
            <Transition name="fade-slide" appear>
              <BedOccupancyChart 
                v-if="!loadingStates.beds" 
                :beds="dashboardData.beds" 
              />
              <div v-else class="chart-skeleton"></div>
            </Transition>
          </div>
        </div>

        <!-- Overview and Activity Row -->
        <div class="row mb-4">
          <div class="col-lg-8 mb-4">
            <Transition name="fade-slide" appear>
              <PavilionOverview 
                v-if="!loadingStates.pavilions" 
                :pavilions="dashboardData.pavilions" 
              />
              <div v-else class="overview-skeleton"></div>
            </Transition>
          </div>
          <div class="col-lg-4 mb-4">
            <Transition name="fade-slide" appear>
              <QuickActions 
                @refresh="handleRefresh" 
                :rooms="dashboardData.rooms"
                :patients="[]"
              />
            </Transition>
          </div>
        </div>

        <!-- Recent Activity Row -->
        <div class="row">
          <div class="col-12">
            <Transition name="fade-slide" appear>
              <RecentActivity 
                v-if="allDataLoaded" 
                :rooms="dashboardData.rooms" 
                :beds="dashboardData.beds" 
              />
              <div v-else class="activity-skeleton"></div>
            </Transition>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.infrastructure-dashboard {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.dashboard-header {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-bottom: 1px solid #dee2e6;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  color: #64748b;
  margin: 0;
}

.occupancy-indicator {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.occupancy-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: white;
  transition: all 0.3s ease;
}

.occupancy-circle.low {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.occupancy-circle.medium {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.occupancy-circle.high {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.occupancy-percentage {
  font-size: 0.875rem;
}

.occupancy-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.refresh-indicator {
  margin-left: 0.5rem;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 1rem;
}

.loading-spinner {
  position: relative;
}

.loading-text {
  color: #64748b;
  margin: 0;
  font-weight: 500;
}

.loading-progress {
  width: 200px;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #059669);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.dashboard-content {
  padding: 2rem 0;
}

/* Skeleton Loading Styles */
.stats-skeleton {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.skeleton-card {
  height: 120px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 1rem;
}

.chart-skeleton,
.overview-skeleton,
.activity-skeleton {
  height: 300px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 1rem;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Smooth Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.4s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Performance Optimizations */
.dashboard-content * {
  will-change: transform;
}

.occupancy-circle {
  transform: translateZ(0);
}

/* Responsive Improvements */
@media (max-width: 768px) {
  .dashboard-header {
    position: relative;
  }
  
  .occupancy-indicator {
    justify-content: center;
    margin-top: 1rem;
  }
  
  .dashboard-content {
    padding: 1rem 0;
  }
}
</style>
