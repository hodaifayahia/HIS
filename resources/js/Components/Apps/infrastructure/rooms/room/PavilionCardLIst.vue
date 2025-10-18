
<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'

// Router
const router = useRouter()

// Reactive state
const pavilions = ref([])
const services = ref([])
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')
const sortBy = ref('name')
const viewMode = ref('grid')
const favorites = ref(new Set())

// Constants
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'
const defaultImage = 'https://via.placeholder.com/400x300?text=Medical+Pavilion'

// Computed properties
const totalServices = computed(() => services.value.length)

const filteredPavilions = computed(() => {
  let filtered = pavilions.value

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(pavilion =>
      pavilion.name.toLowerCase().includes(query) ||
      pavilion.description.toLowerCase().includes(query) ||
      pavilion.location?.toLowerCase().includes(query)
    )
  }

  // Apply sorting
  return filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'services':
        return getServiceCount(b.id) - getServiceCount(a.id)
      case 'newest':
        return new Date(b.created_at) - new Date(a.created_at)
      default:
        return 0
    }
  })
})

// Methods
const fetchPavilions = async () => {
  loading.value = true
  error.value = null
  
  try {
    const pavilionsResponse = await fetch(`${API_BASE_URL}/pavilions`)
    
    if (!pavilionsResponse.ok) {
      throw new Error('Failed to fetch pavilions')
    }
    
    const pavilionsData = await pavilionsResponse.json()
    pavilions.value = pavilionsData.data || pavilionsData
    
  } catch (err) {
    error.value = 'Failed to load pavilions. Please check your connection and try again.'
    console.error('Error fetching pavilions:', err)
  } finally {
    loading.value = false
  }
}

// Fetch services for a specific pavilion
const fetchPavilionServices = async (pavilionId) => {
  try {
    const response = await fetch(`${API_BASE_URL}/pavilions/${pavilionId}/services`)
    
    if (!response.ok) {
      throw new Error('Failed to fetch pavilion services')
    }
    
    const servicesData = await response.json()
    return servicesData.data || servicesData
    
  } catch (err) {
    console.error('Error fetching pavilion services:', err)
    return []
  }
}

// Get service count for a pavilion (if services are already loaded)
const getServiceCount = (pavilionId) => {
  return services.value.filter(service => service.pavilion_id === pavilionId).length
}

// Get preview services for a pavilion
const getPreviewServices = (pavilionId) => {
  return services.value
    .filter(service => service.pavilion_id === pavilionId)
    .slice(0, 3)
}

// Fixed viewPavilionDetails function
const viewPavilionDetails = (pavilion) => {
  // Method 1: Using named route with params
  router.push({
    name: 'infrastructure.structure.Cardservices',
    params: { id: pavilion.id }
  })
}

const bookAppointment = (pavilion) => {
  router.push(`/appointments/book?pavilion=${pavilion.id}`)
}

const handleImageError = (event) => {
  event.target.src = defaultImage
}

const clearSearch = () => {
  searchQuery.value = ''
}

const truncateDescription = (description) => {
  if (!description) return ''
  return description.length > 80 ? description.substring(0, 80) + '...' : description
}

// Load saved preferences
const loadPreferences = () => {
  const savedViewMode = localStorage.getItem('pavilion-view-mode')
  if (savedViewMode) {
    viewMode.value = savedViewMode
  }
  
  const savedFavorites = localStorage.getItem('pavilion-favorites')
  if (savedFavorites) {
    favorites.value = new Set(JSON.parse(savedFavorites))
  }
}

// Lifecycle hooks
onMounted(() => {
  loadPreferences()
  fetchPavilions()
})

// Watch for search changes with debounce
watch(searchQuery, (newQuery) => {
  if (newQuery) {
    console.log('Search query:', newQuery)
  }
}, { debounce: 300 })
</script>
<template>
  <div class="pavilion-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">Pavilions</h1>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section bg-white border-bottom">
      <div class="container py-3">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input
                v-model="searchQuery"
                type="text"
                class="form-control"
                placeholder="Search pavilions..."
              />
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex gap-2 justify-content-md-end">
              <select v-model="sortBy" class="form-select">
                <option value="name">Sort by Name</option>
                <option value="services">Sort by Services</option>
                <option value="newest">Newest First</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted">Loading pavilions...</p>
      </div>

      <!-- Error State -->
      <div v-if="error" class="alert alert-danger" role="alert">
        <div class="d-flex align-items-center mb-3">
          <i class="fas fa-exclamation-circle me-2"></i>
          <h5 class="mb-0">Error Loading Pavilions</h5>
        </div>
        <p class="mb-3">{{ error }}</p>
        <button @click="fetchPavilions" class="btn btn-danger">
          <i class="fas fa-redo me-2"></i>
          Try Again
        </button>
      </div>

      <!-- Pavilions Grid -->
      <div v-if="!loading && !error">
        <!-- Results Info -->
        <div class="results-info mb-4">
          <p class="text-muted mb-0">
            Showing {{ filteredPavilions.length }} of {{ pavilions.length }} pavilions
          </p>
        </div>

        <!-- Grid View -->
        <div class="row">
          <div
            v-for="pavilion in filteredPavilions"
            :key="pavilion.id"
            class="col-md-3 mb-4 d-flex justify-content-center"
          >
            <div
              class="card text-center shadow-lg"
              style="width: 100%; max-width: 250px; border-radius: 15px;"
              @click="viewPavilionDetails(pavilion)"
            >
              <!-- Pavilion Image -->
              <div class="p-4">
                <div class="mx-auto rounded-pill" style="width: 120px; height: 120px; overflow: hidden;">
                  <img 
                    :src="pavilion.image_url || defaultImage" 
                    :alt="pavilion.name"
                    class="w-100 h-100" 
                    style="object-fit: cover"
                    @error="handleImageError"
                  />
                </div>
                
                <!-- Services Count Badge -->
                <div class="services-badge mt-2">
                  <span class="badge bg-primary text-white">
                    <i class="fas fa-stethoscope me-1"></i>
                    {{ pavilion.services?.length || 0 }} Services
                  </span>
                </div>
              </div>

              <!-- Card Body -->
              <div class="card-body bg-light">
                <!-- Pavilion Name -->
                <p class="card-text text-dark fw-bold fs-5">
                  {{ pavilion.name }}
                </p>
                
                <!-- Pavilion Description -->
                <p class="card-text text-muted small mb-2" v-if="pavilion.description">
                  {{ truncateDescription(pavilion.description) }}
                </p>

                <!-- Location Info -->
                <div class="location-info mb-2" v-if="pavilion.location">
                  <i class="fas fa-map-marker-alt text-muted me-1"></i>
                  <small class="text-muted">{{ pavilion.location }}</small>
                </div>

                <!-- Rating -->
                <div class="rating mb-2" v-if="pavilion.rating">
                  <i class="fas fa-star text-warning"></i>
                  <span class="ms-1 small fw-bold">{{ pavilion.rating }}</span>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons mt-3">
                  <button
                    @click.stop="viewPavilionDetails(pavilion)"
                    class="btn btn-primary btn-sm me-2"
                  >
                    <i class="fas fa-eye me-1"></i>
                    View Services
                  </button>
                 
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && !error && filteredPavilions.length === 0" class="text-center py-5">
        <i class="fas fa-search fa-4x text-muted mb-4"></i>
        <h3 class="text-dark mb-2">No Pavilions Found</h3>
        <p class="text-muted">
          {{ searchQuery ? 'Try adjusting your search criteria.' : 'There are currently no pavilions to display.' }}
        </p>
        <button v-if="searchQuery" @click="clearSearch" class="btn btn-outline-primary">
          Clear Search
        </button>
      </div>
    </div>
  </div>
</template>


<style scoped>
.pavilion-container {
  min-height: 100vh;
  background-color: #f8f9fa;
}

/* Header Section */
.page-header {
    background: linear-gradient(135deg, #5271fa 0%, #2d72f1 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 0;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.header-text {
  flex: 1;
}

.filters-section {
  .search-box {
    position: relative;
    
    i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
      z-index: 2;
    }
    
    .form-control {
      padding-left: 40px;
      border-radius: 25px;
      border: 2px solid #e9ecef;
      transition: all 0.3s ease;
      
      &:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
      }
    }
  }
}

.results-info {
  font-size: 0.9rem;
}

.card {
  transition: transform 0.2s;
  border: none;
}

.card:hover {
  transform: scale(1.05);
  cursor: pointer;
}

.services-badge {
  display: flex;
  justify-content: center;
}

.badge {
  font-size: 0.75rem;
  padding: 0.4rem 0.8rem;
  border-radius: 15px;
  font-weight: 500;
}

.location-info {
  display: flex;
  align-items: center;
  justify-content: center;
}

.rating {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
}

.btn-sm {
  padding: 0.4rem 0.8rem;
  font-size: 0.8rem;
  border-radius: 8px;
}

.loading-state {
  padding: 100px 0;
}

.spinner-border {
  width: 3rem;
  height: 3rem;
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }
  
  .col-md-3 {
    flex: 0 0 50%;
    max-width: 50%;
  }
  
  .filters-section {
    .row > div {
      margin-bottom: 1rem;
    }
  }
}

@media (max-width: 576px) {
  .col-md-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}
</style>
