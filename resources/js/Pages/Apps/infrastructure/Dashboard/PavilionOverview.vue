<script setup>
import { computed } from 'vue'

const props = defineProps({
  pavilions: {
    type: Array,
    required: true
  }
})

const pavilionStats = computed(() => {
  return props.pavilions.map(pavilion => ({
    ...pavilion,
    roomCount: pavilion.rooms?.length || 0,
    serviceCount: pavilion.services?.length || 0
  }))
})
</script>

<template>
  <div class="pavilion-overview">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-building me-2"></i>
          Pavilion Overview
        </h5>
      </div>
      <div class="card-body">
        <div v-if="pavilionStats.length === 0" class="empty-state">
          <i class="fas fa-building text-muted"></i>
          <p class="text-muted">No pavilions found</p>
        </div>
        <div v-else class="pavilion-grid">
          <div v-for="pavilion in pavilionStats" :key="pavilion.id" class="pavilion-card">
            <div class="pavilion-image">
              <img 
                v-if="pavilion.image_url" 
                :src="pavilion.image_url" 
                :alt="pavilion.name"
                class="pavilion-img"
              />
              <div v-else class="pavilion-placeholder">
                <i class="fas fa-building"></i>
              </div>
            </div>
            <div class="pavilion-info">
              <h6 class="pavilion-name">{{ pavilion.name }}</h6>
              <p class="pavilion-description">{{ pavilion.description || 'No description available' }}</p>
              <div class="pavilion-stats">
                <span class="stat-badge">
                  <i class="fas fa-door-open"></i>
                  {{ pavilion.roomCount }} Rooms
                </span>
                <span class="stat-badge">
                  <i class="fas fa-cogs"></i>
                  {{ pavilion.serviceCount }} Services
                </span>
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

.pavilion-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.pavilion-card {
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  overflow: hidden;
  transition: all 0.2s ease;
}

.pavilion-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.pavilion-image {
  height: 120px;
  overflow: hidden;
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.pavilion-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pavilion-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 2rem;
}

.pavilion-info {
  padding: 1rem;
}

.pavilion-name {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.pavilion-description {
  color: #64748b;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.pavilion-stats {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.stat-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: #f1f5f9;
  color: #475569;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 500;
}
</style>
