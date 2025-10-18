<template>
  <div class="services-container">
    <div class="page-header">
      <div class="container">
        <div class="d-flex align-items-center mb-3">
          <button @click="$router.go(-1)" class="btn  me-3">
            <i class="fas fa-arrow-left"></i> Back
          </button>
          <div>
            <h1 class="page-title mb-0">{{ pavilion?.name }} Services</h1>
            <p class="text-muted mb-0" v-if="pavilion?.description">
              {{ pavilion.description }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="container py-4">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary mb-3"></div>
        <p class="text-muted">Loading services...</p>
      </div>

      <!-- Services Grid -->
      <div v-else-if="services.length > 0" class="row">
        <div
          v-for="service in services"
          :key="service.id"
          class="col-md-3 mb-4 d-flex justify-content-center"
        >
          <div
            class="card text-center shadow-lg"
            :class="{ 'inactive-service': !service.is_active }"
            style="width: 100%; max-width: 250px; border-radius: 15px;"
            @click="handleServiceClick(service.id)"
          >
            <!-- Service Image -->
            <div class="p-3">
              <div class="mx-auto rounded-pill" style="width: 120px; height: 120px; overflow: hidden;">
                <img 
                  v-if="service.image_url" 
                  :src="service.image_url" 
                  :alt="service.name"
                  class="w-100 h-100" 
                  style="object-fit: contain"
                  @error="handleImageError"
                />
                <div 
                  v-else 
                  class="w-100 h-100 d-flex align-items-center justify-content-center bg-light rounded"
                  style="object-fit: contain"
                >
                  <i class="fas fa-image fa-2x text-muted"></i>
                </div>
              </div>
              
              <!-- Active Status Badge -->
              <div 
                class="status-badge mt-2" 
                :class="service.is_active ? 'badge-active' : 'badge-inactive'"
              >
                <i class="fas" :class="service.is_active ? 'fa-check-circle' : 'fa-pause-circle'"></i>
                {{ service.is_active ? 'Active' : 'Inactive' }}
              </div>
            </div>

            <!-- Card Body -->
            <div class="card-body bg-light">
              <!-- Service Name -->
              <p class="card-text text-dark fw-bold fs-5" :class="{ 'text-muted': !service.is_active }">
                {{ service.name }}
              </p>
              
              <!-- Service Description -->
              <p class="card-text text-muted small" v-if="service.description">
                {{ truncateDescription(service.description) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-5">
        <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
        <h3>No Services Available</h3>
        <p class="text-muted">This pavilion currently has no services listed.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const pavilion = ref(null)
const services = ref([])
const loading = ref(true)

const fetchPavilionServices = async () => {
  try {
    const pavilionId = route.params.id
    console.log('Pavilion ID:', pavilionId)

    const response = await axios.get(`/api/pavilions/${pavilionId}/services`)
    console.log('Services:', response.data.data)
    
    services.value = response.data.data
     
  } catch (error) {
    console.error('Error fetching services:', error)
  } finally {
    loading.value = false
  }
}

const handleServiceClick = (id) => {
   router.push({
        name: 'infrastructure.Areas.roomsTyps',
        params: { id }, // Use params instead of query
    });
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  const placeholder = event.target.parentElement.querySelector('.image-placeholder')
  if (placeholder) {
    placeholder.style.display = 'flex'
  }
}

const truncateDescription = (description) => {
  if (!description) return ''
  return description.length > 60 ? description.substring(0, 60) + '...' : description
}

onMounted(() => {
  fetchPavilionServices()
})
</script>

<style scoped>
.services-container {
  min-height: 100vh;
  background-color: #f8f9fa;
  border-radius: 40px;
}

.page-header {
    background: linear-gradient(135deg, #5271fa 0%, #2d72f1 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 0;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
}

.card {
  transition: transform 0.2s;
  border: none;
}

.card:hover {
  transform: scale(1.05);
  cursor: pointer;
}

.inactive-service {
  opacity: 0.7;
}

.inactive-service:hover {
  opacity: 0.9;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.badge-active {
  background-color: #28a745;
}

.badge-inactive {
  background-color: #6c757d;
}

.btn-outline-secondary {
  border-radius: 10px;
  padding: 0.5rem 1rem;
  font-weight: 500;
}

.btn-outline-secondary:hover {
  background-color: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.3);
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }
  
  .col-md-3 {
    flex: 0 0 50%;
    max-width: 50%;
  }
}

@media (max-width: 576px) {
  .col-md-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}
</style>
