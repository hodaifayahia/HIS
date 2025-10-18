<template>
  <div class="room-types-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="container">
        <div class="d-flex align-items-center mb-3">
          <button @click="$router.go(-1)" class="btn btn-outline-light me-3">
            <i class="fas fa-arrow-left"></i> Back
          </button>
          <div>
            <h1 class="page-title mb-0">Room Types</h1>
            <p class="text-light mb-0 opacity-75">
              Choose the type of room you need
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Room Types Grid -->
    <div class="container py-5">
      <div class="row justify-content-center">
        <!-- Waiting Room Card -->
        <div class="col-md-3 mb-4 d-flex justify-content-center">
          <div
            class="card text-center shadow-lg room-card waiting-room-card"
            style="width: 100%; max-width: 280px; border-radius: 20px;"
            @click="handleRoomClick('WaitingRoom')"
          >
            <!-- Room Image/Icon -->
            <div class="p-4">
              <div class="mx-auto room-icon-container waiting-room-bg" style="width: 130px; height: 130px;">
                <div class="room-icon">
                  <i class="fas fa-chair fa-3x text-white"></i>
                </div>
              </div>
              
              <!-- Room Status Badge -->
             
            </div>

            <!-- Room Name -->
            <h4 class="card-title text-dark fw-bold mb-4 p-2">
              Waiting Room
            </h4>
         
          </div>
        </div>

        <!-- Normal Room Card -->
        <div class="col-md-3 mb-4 d-flex justify-content-center">
          <div
            class="card text-center shadow-lg room-card normal-room-card"
            style="width: 100%; max-width: 280px; border-radius: 20px;"
            @click="handleRoomClick('Normal')"
          >
            <!-- Room Image/Icon -->
            <div class="p-4">
              <div class="mx-auto room-icon-container normal-room-bg" style="width: 130px; height: 130px;">
                <div class="room-icon">
                  <i class="fas fa-bed fa-3x text-white"></i>
                </div>
              </div>
              
              <!-- Room Status Badge -->
             
            </div>

            <!-- Room Name -->
            <h4 class="card-title text-dark fw-bold mb-4 p-2">
              Normal Room
            </h4>
           
          </div>
        </div>
      </div>

      <!-- Additional Info Section -->
      <div class="row mt-5">
        <div class="col-12">
          <div class="info-card text-center p-4">
            <h5 class="mb-3">
              <i class="fas fa-info-circle text-primary me-2"></i>
              Room Selection Guide
            </h5>
            <div class="row">
              <div class="col-md-6">
                <div class="guide-item">
                  <h6 class="text-primary">Waiting Room</h6>
                  <p class="small text-muted">Perfect for pre-appointment waiting, family areas, and general comfort zones</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="guide-item">
                  <h6 class="text-success">Normal Room</h6>
                  <p class="small text-muted">Ideal for consultations, examinations, and standard medical procedures</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// Static room data
const roomTypes = {
  waiting: {
    id: 'waiting',
    name: 'Waiting Room',
    description: 'Comfortable seating area for patients and visitors',
    features: ['Free WiFi', 'Refreshments', 'Entertainment'],
    icon: 'fa-chair',
    color: 'primary'
  },
  normal: {
    id: 'normal', 
    name: 'Normal Room',
    description: 'Standard patient room with medical facilities',
    features: ['Medical Equipment', 'Privacy', 'Climate Control'],
    icon: 'fa-bed',
    color: 'success'
  }
}

const handleRoomClick = (roomType) => {
  const service_id = route.params.id // Get the room ID from current route
  
  // Navigate to the room type detail page
  router.push({
    name: 'infrastructure.Areas.roomsTyps',
    query: { 
      service_id: service_id, // Pass original room ID as query param
      type: roomType 
    }
  })
}
</script>

<style scoped>
.room-types-container {
  min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #5271fa 0%, #2d72f1 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 0;
}

.page-title {
  font-size: 2.8rem;
  font-weight: 700;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.room-card {
  transition: all 0.3s ease;
  border: none;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
  overflow: hidden;
  position: relative;
}

.room-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
  transform: translateX(-100%);
  transition: transform 0.6s ease;
}

.room-card:hover::before {
  transform: translateX(100%);
}

.room-card:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
  cursor: pointer;
}

.waiting-room-card:hover {
  box-shadow: 0 20px 40px rgba(0, 123, 255, 0.3);
}

.normal-room-card:hover {
  box-shadow: 0 20px 40px rgba(40, 167, 69, 0.3);
}

.room-icon-container {
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.waiting-room-bg {
  background: linear-gradient(135deg, #007bff, #0056b3);
  box-shadow: 0 8px 32px rgba(0, 123, 255, 0.3);
}

.normal-room-bg {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  box-shadow: 0 8px 32px rgba(40, 167, 69, 0.3);
}

.room-icon {
  position: relative;
  z-index: 2;
}

.room-icon-container::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
  transform: rotate(45deg);
  animation: shimmer 3s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
  100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  color: white;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.badge-waiting {
  background: linear-gradient(45deg, #007bff, #0056b3);
}

.badge-normal {
  background: linear-gradient(45deg, #28a745, #1e7e34);
}

.bg-gradient-light {
  background: linear-gradient(180deg, #f8f9fa, #ffffff);
}

.card-title {
  color: #2c3e50;
  margin-bottom: 1rem;
}

.room-features {
  text-align: left;
}

.feature-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #6c757d;
}

.btn-waiting {
  background: linear-gradient(45deg, #007bff, #0056b3);
  border: none;
  color: white;
  padding: 12px 24px;
  border-radius: 25px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

.btn-waiting:hover {
  background: linear-gradient(45deg, #0056b3, #004085);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
  color: white;
}

.btn-normal {
  background: linear-gradient(45deg, #28a745, #1e7e34);
  border: none;
  color: white;
  padding: 12px 24px;
  border-radius: 25px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-normal:hover {
  background: linear-gradient(45deg, #1e7e34, #155724);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  color: white;
}

.info-card {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 15px;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.guide-item {
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1rem;
  background: rgba(248, 249, 250, 0.5);
}

.btn-outline-light {
  border-radius: 10px;
  padding: 0.5rem 1rem;
  font-weight: 500;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.1);
}

.btn-outline-light:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
  color: white;
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }
  
  .col-md-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }
  
  .room-card {
    max-width: 320px !important;
  }
}

@media (max-width: 576px) {
  .container {
    padding: 0 15px;
  }
  
  .room-icon-container {
    width: 100px !important;
    height: 100px !important;
  }
  
  .room-icon i {
    font-size: 2rem !important;
  }
}

/* Loading animation for cards */
.room-card {
  animation: fadeInUp 0.6s ease-out;
}

.waiting-room-card {
  animation-delay: 0.1s;
}

.normal-room-card {
  animation-delay: 0.2s;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
