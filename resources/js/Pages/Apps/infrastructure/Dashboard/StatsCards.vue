<script setup>
import { computed } from 'vue'

const props = defineProps({
  stats: {
    type: Object,
    required: true
  }
})

const statsCards = computed(() => [
  {
    title: 'Total Pavilions',
    value: props.stats.totalPavilions,
    icon: 'fas fa-building',
    color: 'primary',
    gradient: 'linear-gradient(135deg, #007bff 0%, #6610f2 100%)'
  },
  {
    title: 'Total Rooms',
    value: props.stats.totalRooms,
    icon: 'fas fa-door-open',
    color: 'info',
    gradient: 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)'
  },
  {
    title: 'Available Rooms',
    value: props.stats.availableRooms,
    icon: 'fas fa-check-circle',
    color: 'success',
    gradient: 'linear-gradient(135deg, #28a745 0%, #20c997 100%)'
  },
  {
    title: 'Occupied Rooms',
    value: props.stats.occupiedRooms,
    icon: 'fas fa-user-friends',
    color: 'warning',
    gradient: 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)'
  },
  {
    title: 'Total Beds',
    value: props.stats.totalBeds,
    icon: 'fas fa-bed',
    color: 'secondary',
    gradient: 'linear-gradient(135deg, #6c757d 0%, #495057 100%)'
  },
  {
    title: 'Occupied Beds',
    value: props.stats.occupiedBeds,
    icon: 'fas fa-user-injured',
    color: 'danger',
    gradient: 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)'
  }
])
</script>

<template>
  <div class="stats-cards">
    <div class="row g-3">
      <div v-for="card in statsCards" :key="card.title" class="col-lg-2 col-md-4 col-sm-6">
        <div class="stat-card" :style="{ background: card.gradient }">
          <div class="stat-icon">
            <i :class="card.icon"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ card.value }}</div>
            <div class="stat-title">{{ card.title }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stat-card {
  padding: 1.5rem;
  border-radius: 1rem;
  color: white;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-icon {
  font-size: 2rem;
  opacity: 0.8;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-title {
  font-size: 0.875rem;
  opacity: 0.9;
  font-weight: 500;
}
</style>
