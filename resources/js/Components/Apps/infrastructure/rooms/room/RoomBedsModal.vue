<!-- components/RoomBedsModal.vue -->
<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import BedModel from '../../bad/BedModel.vue'
import { useToastr } from '../../../../toster';
import { useSweetAlert } from '../../../../useSweetAlert';
const props = defineProps({
  showModal: {
    type: Boolean,
    default: false
  },
  room: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'bed-updated'])

const swal = useSweetAlert()
const toaster = useToastr()

const beds = ref([])
const patients = ref([])
const loading = ref(false)
const showBedModal = ref(false)
const selectedBed = ref(null)

// Computed properties for bed statistics
const bedStats = computed(() => {
  const total = beds.value.length
  const free = beds.value.filter(bed => bed.status === 'free').length
  const occupied = beds.value.filter(bed => bed.status === 'occupied').length
  const reserved = beds.value.filter(bed => bed.status === 'reserved').length
  
  return { total, free, occupied, reserved }
})

const occupancyRate = computed(() => {
  if (bedStats.value.total === 0) return 0
  return Math.round((bedStats.value.occupied / bedStats.value.total) * 100)
})

// Watch for modal visibility
watch(() => props.showModal, (newVal) => {
  if (newVal && props.room.id) {
    fetchRoomBeds()
    fetchPatients()
  }
})

const fetchRoomBeds = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/beds`, {
      params: { room_id: props.room.id }
    })
    beds.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching room beds:', error)
    toaster.error('Failed to load room beds')
  } finally {
    loading.value = false
  }
}

const fetchPatients = async () => {
  try {
    const response = await axios.get('/api/patients')
    patients.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching patients:', error)
  }
}

const getBedStatusIcon = (status) => {
  const icons = {
    'free': 'fa-check-circle',
    'occupied': 'fa-user',
    'reserved': 'fa-clock'
  }
  return icons[status] || 'fa-question-circle'
}

const getBedStatusColor = (status) => {
  const colors = {
    'free': '#10b981',
    'occupied': '#ef4444',
    'reserved': '#f59e0b'
  }
  return colors[status] || '#6b7280'
}

const openAddBedModal = () => {
  selectedBed.value = null
  showBedModal.value = true
}

const openEditBedModal = (bed) => {
  selectedBed.value = bed
  showBedModal.value = true
}

const closeBedModal = () => {
  showBedModal.value = false
  selectedBed.value = null
}

const handleBedSaved = () => {
  closeBedModal()
  fetchRoomBeds()
  emit('bed-updated')
}

const updateBedStatus = async (bed, newStatus) => {
  try {
    const response = await axios.patch(`/api/beds/${bed.id}`, {
      status: newStatus,
      room_id: bed.room.id,
      bed_identifier: bed.bed_identifier,
      current_patient_id: newStatus === 'free' ? null : bed.current_patient_id
    })
    
    // Update local bed data
    const index = beds.value.findIndex(b => b.id === bed.id)
    if (index !== -1) {
      beds.value[index] = response.data.data
    }
    
    toaster.success(`Bed ${bed.bed_identifier} status updated to ${newStatus}`)
    emit('bed-updated')
  } catch (error) {
    console.error('Error updating bed status:', error)
    toaster.error('Failed to update bed status')
  }
}

const deleteBed = async (bed) => {
  const result = await swal.fire({
    title: 'Delete Bed?',
    text: `Are you sure you want to delete bed ${bed.bed_identifier}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  })

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/beds/${bed.id}`)
      beds.value = beds.value.filter(b => b.id !== bed.id)
      toaster.success('Bed deleted successfully')
      emit('bed-updated')
    } catch (error) {
      console.error('Error deleting bed:', error)
      toaster.error('Failed to delete bed')
    }
  }
}

const closeModal = () => {
  emit('close')
}
</script>

<template>
  <div v-if="showModal" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-info">
          <div class="room-icon">
            <i class="fas fa-door-open"></i>
          </div>
          <div>
            <h2 class="modal-title">{{ room.name }}</h2>
            <p class="room-details">
              Room {{ room.room_number }} • {{ room.room_type }} • {{ room.service?.name || 'No Service' }}
            </p>
          </div>
        </div>
        <button @click="closeModal" class="close-button">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-section">
        <div class="stats-grid">
          <div class="stat-card total">
            <div class="stat-icon">
              <i class="fas fa-bed"></i>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ bedStats.total }}</div>
              <div class="stat-label">Total Beds</div>
            </div>
          </div>
          
          <div class="stat-card free">
            <div class="stat-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ bedStats.free }}</div>
              <div class="stat-label">Available</div>
            </div>
          </div>
          
          <div class="stat-card occupied">
            <div class="stat-icon">
              <i class="fas fa-user"></i>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ bedStats.occupied }}</div>
              <div class="stat-label">Occupied</div>
            </div>
          </div>
          
          <div class="stat-card reserved">
            <div class="stat-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ bedStats.reserved }}</div>
              <div class="stat-label">Reserved</div>
            </div>
          </div>
        </div>

        <!-- Occupancy Rate -->
        <div class="occupancy-rate">
          <div class="occupancy-header">
            <span class="occupancy-label">Occupancy Rate</span>
            <span class="occupancy-percentage">{{ occupancyRate }}%</span>
          </div>
          <div class="occupancy-bar">
            <div 
              class="occupancy-fill" 
              :style="{ width: occupancyRate + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Add Bed Button -->
      <div class="action-section">
        <button @click="openAddBedModal" class="add-bed-btn">
          <i class="fas fa-plus"></i>
          Add New Bed
        </button>
      </div>

      <!-- Beds Grid -->
      <div class="beds-section">
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading beds...</p>
        </div>
        
        <div v-else-if="beds.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-bed"></i>
          </div>
          <h3>No beds in this room</h3>
          <p>Click "Add New Bed" to create the first bed for this room.</p>
        </div>
        
        <div v-else class="beds-grid">
          <div 
            v-for="bed in beds" 
            :key="bed.id" 
            class="bed-card"
            :class="bed.status"
          >
            <!-- Bed Header -->
            <div class="bed-header">
              <div class="bed-info">
                <div class="bed-identifier">{{ bed.bed_identifier }}</div>
                <div class="bed-status">
                  <i :class="['fas', getBedStatusIcon(bed.status)]" :style="{ color: getBedStatusColor(bed.status) }"></i>
                  {{ bed.status_label }}
                </div>
              </div>
              <div class="bed-actions">
                <button @click="openEditBedModal(bed)" class="action-btn edit" title="Edit bed">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="deleteBed(bed)" class="action-btn delete" title="Delete bed">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>

            <!-- Patient Info -->
            <div class="bed-body">
              <div v-if="bed.current_patient" class="patient-info">
                <div class="patient-avatar">
                  <i class="fas fa-user"></i>
                </div>
                <div class="patient-details">
                  <div class="patient-name">{{ bed.current_patient.name }}</div>
                  <div class="patient-id">ID: {{ bed.current_patient.id }}</div>
                </div>
              </div>
              <div v-else class="no-patient">
                <i class="fas fa-user-slash"></i>
                <span>No patient assigned</span>
              </div>
            </div>

            <!-- Status Actions -->
            <div class="bed-footer">
              <div class="status-actions">
                <button 
                  v-if="bed.status !== 'free'"
                  @click="updateBedStatus(bed, 'free')"
                  class="status-btn free"
                >
                  <i class="fas fa-check"></i>
                  Mark Free
                </button>
                <button 
                  v-if="bed.status !== 'occupied'"
                  @click="updateBedStatus(bed, 'occupied')"
                  class="status-btn occupied"
                >
                  <i class="fas fa-user"></i>
                  Mark Occupied
                </button>
                <button 
                  v-if="bed.status !== 'reserved'"
                  @click="updateBedStatus(bed, 'reserved')"
                  class="status-btn reserved"
                >
                  <i class="fas fa-clock"></i>
                  Mark Reserved
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
     <RoomBedsModal
    :show-modal="showBedsModal"
    :room="selectedRoom"
    @close="closeBedsModal"
    @bed-updated="handleBedUpdated"
  />

    <!-- Bed Modal -->
    <BedModel
      :show-modal="showBedModal"
      :bed-data="selectedBed"
      :rooms="[room]"
      :patients="patients"
      @close="closeBedModal"
      @bed-added="handleBedSaved"
      @bed-updated="handleBedSaved"
    />
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(8px);
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 1.5rem;
  width: 100%;
  max-width: 1200px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  border: 1px solid #e2e8f0;
}

/* Modal Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 1.5rem 1.5rem 0 0;
}

.header-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.room-icon {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.modal-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
}

.room-details {
  margin: 0.5rem 0 0 0;
  opacity: 0.9;
  font-size: 0.875rem;
}

.close-button {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 0.5rem;
  color: white;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-button:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Statistics Section */
.stats-section {
  padding: 2rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.stat-card.total .stat-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.free .stat-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-card.occupied .stat-icon {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.stat-card.reserved .stat-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

/* Occupancy Rate */
.occupancy-rate {
  background: white;
  padding: 1.5rem;
  border-radius: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.occupancy-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.occupancy-label {
  font-weight: 600;
  color: #374151;
}

.occupancy-percentage {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.occupancy-bar {
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.occupancy-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  transition: width 0.3s ease;
}

/* Action Section */
.action-section {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.add-bed-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
}

.add-bed-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
}

/* Beds Section */
.beds-section {
  padding: 2rem;
}

.loading-state {
  text-align: center;
  padding: 3rem;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #10b981;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #64748b;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.beds-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

/* Bed Cards */
.bed-card {
  background: white;
  border-radius: 1rem;
  border: 2px solid #e5e7eb;
  overflow: hidden;
  transition: all 0.2s;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.bed-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.bed-card.free {
  border-color: #10b981;
  background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
}

.bed-card.occupied {
  border-color: #ef4444;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.bed-card.reserved {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.bed-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.bed-identifier {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.bed-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: capitalize;
}

.bed-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn.edit {
  background: #dbeafe;
  color: #1e40af;
}

.action-btn.edit:hover {
  background: #bfdbfe;
}

.action-btn.delete {
  background: #fee2e2;
  color: #dc2626;
}

.action-btn.delete:hover {
  background: #fecaca;
}

.bed-body {
  padding: 1.5rem;
}

.patient-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.patient-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.patient-name {
  font-weight: 600;
  color: #1e293b;
}

.patient-id {
  font-size: 0.75rem;
  color: #64748b;
}

.no-patient {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #9ca3af;
  font-style: italic;
}

.bed-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.status-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.status-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.status-btn.free {
  background: #dcfce7;
  color: #166534;
}

.status-btn.free:hover {
  background: #bbf7d0;
}

.status-btn.occupied {
  background: #fee2e2;
  color: #991b1b;
}

.status-btn.occupied:hover {
  background: #fecaca;
}

.status-btn.reserved {
  background: #fef3c7;
  color: #92400e;
}

.status-btn.reserved:hover {
  background: #fde68a;
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-content {
    margin: 0.5rem;
    border-radius: 1rem;
  }

  .modal-header {
    padding: 1.5rem;
    border-radius: 1rem 1rem 0 0;
  }

  .header-info {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .beds-grid {
    grid-template-columns: 1fr;
  }

  .bed-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .status-actions {
    justify-content: center;
  }
}
</style>
