<template>
  <div class="enhanced-schedule-container">
    <!-- Header Section -->
    <div class="schedule-header">
      <div class="header-content">
        <div class="title-section">
          <i class="pi pi-calendar-plus text-3xl text-blue-600"></i>
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Schedule Management</h1>
            <p class="text-gray-600">Manage your availability and appointment slots</p>
          </div>
        </div>
        <div class="header-actions">
          <Button 
            icon="pi pi-refresh" 
            label="Refresh" 
            @click="fetchSchedules"
            :loading="loading"
            class="p-button-outlined"
          />
          <Button 
            icon="pi pi-plus" 
            label="Add Schedule" 
            @click="showAddModal = true"
            class="p-button-success"
          />
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
      <Card class="stat-card">
        <template #content>
          <div class="stat-content">
            <i class="pi pi-calendar text-blue-500"></i>
            <div>
              <div class="stat-number">{{ activeSchedulesCount }}</div>
              <div class="stat-label">Active Schedules</div>
            </div>
          </div>
        </template>
      </Card>
      
      <Card class="stat-card">
        <template #content>
          <div class="stat-content">
            <i class="pi pi-clock text-green-500"></i>
            <div>
              <div class="stat-number">{{ totalWeeklyHours }}</div>
              <div class="stat-label">Weekly Hours</div>
            </div>
          </div>
        </template>
      </Card>
      
      <Card class="stat-card">
        <template #content>
          <div class="stat-content">
            <i class="pi pi-users text-purple-500"></i>
            <div>
              <div class="stat-number">{{ totalPatientsPerWeek }}</div>
              <div class="stat-label">Patients/Week</div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters and Controls -->
    <Card class="filters-card">
      <template #content>
        <div class="filters-content">
          <div class="filter-group">
            <label>View Mode:</label>
            <SelectButton 
              v-model="viewMode" 
              :options="viewModeOptions" 
              optionLabel="label" 
              optionValue="value"
            />
          </div>
          
          <div class="filter-group">
            <label>Day Filter:</label>
            <MultiSelect 
              v-model="selectedDays" 
              :options="daysOfWeek" 
              placeholder="All Days"
              class="w-full md:w-20rem"
            />
          </div>
          
          <div class="filter-group">
            <label>Shift Period:</label>
            <Dropdown 
              v-model="selectedShift" 
              :options="shiftOptions" 
              optionLabel="label" 
              optionValue="value"
              placeholder="All Shifts"
              class="w-full md:w-14rem"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Schedule Display -->
    <div v-if="viewMode === 'grid'" class="schedule-grid">
      <Card 
        v-for="day in daysOfWeek" 
        :key="day" 
        class="day-card"
        :class="{ 'has-schedule': getDaySchedules(day).length > 0 }"
      >
        <template #header>
          <div class="day-header">
            <h3>{{ day }}</h3>
            <Badge 
              v-if="getDaySchedules(day).length > 0" 
              :value="getDaySchedules(day).length" 
              severity="info"
            />
          </div>
        </template>
        
        <template #content>
          <div class="day-schedules">
            <div 
              v-for="schedule in getDaySchedules(day)" 
              :key="schedule.id"
              class="schedule-item"
              :class="{ 'inactive': !schedule.is_active }"
            >
              <div class="schedule-info">
                <div class="time-info">
                  <i class="pi pi-clock"></i>
                  <span>{{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}</span>
                </div>
                <div class="shift-badge">
                  <Badge 
                    :value="schedule.shift_period" 
                    :severity="schedule.shift_period === 'morning' ? 'warning' : 'info'"
                  />
                </div>
              </div>
              
              <div class="schedule-details">
                <div class="detail-item">
                  <i class="pi pi-users"></i>
                  <span>{{ schedule.number_of_patients_per_day }} patients</span>
                </div>
                <div v-if="schedule.break_duration" class="detail-item">
                  <i class="pi pi-pause"></i>
                  <span>{{ schedule.break_duration }}min break</span>
                </div>
              </div>
              
              <div class="schedule-actions">
                <Button 
                  icon="pi pi-pencil" 
                  @click="editSchedule(schedule)"
                  class="p-button-text p-button-sm"
                  v-tooltip="'Edit Schedule'"
                />
                <Button 
                  :icon="schedule.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'" 
                  @click="toggleScheduleStatus(schedule)"
                  class="p-button-text p-button-sm"
                  :class="schedule.is_active ? 'p-button-warning' : 'p-button-success'"
                  v-tooltip="schedule.is_active ? 'Deactivate' : 'Activate'"
                />
                <Button 
                  icon="pi pi-trash" 
                  @click="confirmDeleteSchedule(schedule)"
                  class="p-button-text p-button-sm p-button-danger"
                  v-tooltip="'Delete Schedule'"
                />
              </div>
            </div>
            
            <Button 
              v-if="getDaySchedules(day).length === 0"
              icon="pi pi-plus" 
              label="Add Schedule" 
              @click="addScheduleForDay(day)"
              class="p-button-outlined w-full"
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- Table View -->
    <Card v-else class="table-card">
      <template #content>
        <DataTable 
          :value="filteredSchedules" 
          :paginator="true" 
          :rows="10"
          :loading="loading"
          responsiveLayout="scroll"
          class="schedule-table"
        >
          <Column field="day_of_week" header="Day" sortable>
            <template #body="{ data }">
              <div class="day-cell">
                <i class="pi pi-calendar"></i>
                <span>{{ capitalizeFirst(data.day_of_week) }}</span>
              </div>
            </template>
          </Column>
          
          <Column field="shift_period" header="Shift" sortable>
            <template #body="{ data }">
              <Badge 
                :value="data.shift_period" 
                :severity="data.shift_period === 'morning' ? 'warning' : 'info'"
              />
            </template>
          </Column>
          
          <Column header="Time" sortable>
            <template #body="{ data }">
              <div class="time-cell">
                <i class="pi pi-clock"></i>
                <span>{{ formatTime(data.start_time) }} - {{ formatTime(data.end_time) }}</span>
              </div>
            </template>
          </Column>
          
          <Column field="number_of_patients_per_day" header="Patients" sortable>
            <template #body="{ data }">
              <div class="patients-cell">
                <i class="pi pi-users"></i>
                <span>{{ data.number_of_patients_per_day }}</span>
              </div>
            </template>
          </Column>
          
          <Column header="Breaks" sortable>
            <template #body="{ data }">
              <div v-if="data.break_duration" class="break-cell">
                <i class="pi pi-pause"></i>
                <span>{{ data.break_duration }}min</span>
                <small v-if="data.break_times">
                  ({{ JSON.parse(data.break_times || '[]').length }} breaks)
                </small>
              </div>
              <span v-else class="text-gray-400">No breaks</span>
            </template>
          </Column>
          
          <Column field="is_active" header="Status" sortable>
            <template #body="{ data }">
              <Tag 
                :value="data.is_active ? 'Active' : 'Inactive'" 
                :severity="data.is_active ? 'success' : 'danger'"
              />
            </template>
          </Column>
          
          <Column header="Actions">
            <template #body="{ data }">
              <div class="action-buttons">
                <Button 
                  icon="pi pi-pencil" 
                  @click="editSchedule(data)"
                  class="p-button-text p-button-sm"
                  v-tooltip="'Edit'"
                />
                <Button 
                  :icon="data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'" 
                  @click="toggleScheduleStatus(data)"
                  class="p-button-text p-button-sm"
                  :class="data.is_active ? 'p-button-warning' : 'p-button-success'"
                  v-tooltip="data.is_active ? 'Deactivate' : 'Activate'"
                />
                <Button 
                  icon="pi pi-trash" 
                  @click="confirmDeleteSchedule(data)"
                  class="p-button-text p-button-sm p-button-danger"
                  v-tooltip="'Delete'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Add/Edit Schedule Modal -->
    <Dialog 
      v-model:visible="showAddModal" 
      :header="editingSchedule ? 'Edit Schedule' : 'Add New Schedule'"
      :modal="true"
      :closable="true"
      class="schedule-modal"
      :style="{ width: '50vw' }"
    >
      <ScheduleForm 
        :schedule="editingSchedule"
        @save="handleScheduleSave"
        @cancel="closeModal"
        :loading="saving"
      />
    </Dialog>

    <!-- Delete Confirmation -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import { useAuthStoreDoctor } from '../../stores/AuthDoctor'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import SelectButton from 'primevue/selectbutton'
import MultiSelect from 'primevue/multiselect'
import Dropdown from 'primevue/dropdown'
import ConfirmDialog from 'primevue/confirmdialog'

// Custom Components
import ScheduleForm from './ScheduleForm.vue'

// Composables
const confirm = useConfirm()
const toast = useToast()
const doctorStore = useAuthStoreDoctor()

// Reactive Data
const schedules = ref([])
const loading = ref(false)
const saving = ref(false)
const showAddModal = ref(false)
const editingSchedule = ref(null)
const viewMode = ref('grid')
const selectedDays = ref([])
const selectedShift = ref(null)

// Constants
const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const viewModeOptions = [
  { label: 'Grid View', value: 'grid' },
  { label: 'Table View', value: 'table' }
]

const shiftOptions = [
  { label: 'Morning', value: 'morning' },
  { label: 'Afternoon', value: 'afternoon' }
]

// Computed Properties
const activeSchedulesCount = computed(() => 
  schedules.value.filter(s => s.is_active).length
)

const totalWeeklyHours = computed(() => {
  return schedules.value
    .filter(s => s.is_active)
    .reduce((total, schedule) => {
      const start = new Date(`1970-01-01T${schedule.start_time}`)
      const end = new Date(`1970-01-01T${schedule.end_time}`)
      const hours = (end - start) / (1000 * 60 * 60)
      return total + hours
    }, 0)
    .toFixed(1)
})

const totalPatientsPerWeek = computed(() => 
  schedules.value
    .filter(s => s.is_active)
    .reduce((total, schedule) => total + (schedule.number_of_patients_per_day || 0), 0)
)

const filteredSchedules = computed(() => {
  let filtered = schedules.value

  if (selectedDays.value.length > 0) {
    filtered = filtered.filter(s => 
      selectedDays.value.includes(capitalizeFirst(s.day_of_week))
    )
  }

  if (selectedShift.value) {
    filtered = filtered.filter(s => s.shift_period === selectedShift.value)
  }

  return filtered
})

// Methods
const fetchSchedules = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/schedules/${doctorStore.doctorData.id}`)
    schedules.value = response.data.schedules || []
  } catch (error) {
    console.error('Error fetching schedules:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch schedules',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const getDaySchedules = (day) => {
  return schedules.value.filter(s => 
    capitalizeFirst(s.day_of_week) === day
  )
}

const addScheduleForDay = (day) => {
  editingSchedule.value = {
    day_of_week: day.toLowerCase(),
    shift_period: 'morning',
    start_time: '08:00',
    end_time: '12:00',
    number_of_patients_per_day: 10,
    is_active: true
  }
  showAddModal.value = true
}

const editSchedule = (schedule) => {
  editingSchedule.value = { ...schedule }
  showAddModal.value = true
}

const handleScheduleSave = async (scheduleData) => {
  saving.value = true
  try {
    if (editingSchedule.value?.id) {
      // Update existing schedule
      await axios.put(`/api/schedules/${editingSchedule.value.id}`, scheduleData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Schedule updated successfully',
        life: 3000
      })
    } else {
      // Create new schedule
      await axios.post('/api/schedules', {
        ...scheduleData,
        doctor_id: doctorStore.doctorData.id
      })
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Schedule created successfully',
        life: 3000
      })
    }
    
    await fetchSchedules()
    closeModal()
  } catch (error) {
    console.error('Error saving schedule:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save schedule',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const toggleScheduleStatus = async (schedule) => {
  try {
    await axios.patch(`/api/schedules/${schedule.id}/toggle-status`)
    schedule.is_active = !schedule.is_active
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Schedule ${schedule.is_active ? 'activated' : 'deactivated'}`,
      life: 3000
    })
  } catch (error) {
    console.error('Error toggling schedule status:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update schedule status',
      life: 3000
    })
  }
}

const confirmDeleteSchedule = (schedule) => {
  confirm.require({
    message: 'Are you sure you want to delete this schedule?',
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    accept: () => deleteSchedule(schedule),
    reject: () => {}
  })
}

const deleteSchedule = async (schedule) => {
  try {
    await axios.delete(`/api/schedules/${schedule.id}`)
    schedules.value = schedules.value.filter(s => s.id !== schedule.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Schedule deleted successfully',
      life: 3000
    })
  } catch (error) {
    console.error('Error deleting schedule:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete schedule',
      life: 3000
    })
  }
}

const closeModal = () => {
  showAddModal.value = false
  editingSchedule.value = null
}

const formatTime = (time) => {
  if (!time) return ''
  return time.slice(0, 5) // Remove seconds
}

const capitalizeFirst = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

// Lifecycle
onMounted(() => {
  fetchSchedules()
})
</script>

<style scoped>
.enhanced-schedule-container {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
}

.schedule-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.title-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.stat-content i {
  font-size: 2rem;
}

.stat-number {
  font-size: 2rem;
  font-weight: bold;
  color: #2d3748;
}

.stat-label {
  color: #718096;
  font-size: 0.875rem;
}

.filters-card {
  margin-bottom: 2rem;
}

.filters-content {
  display: flex;
  gap: 2rem;
  align-items: center;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #4a5568;
}

.schedule-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.day-card {
  border: 2px solid #e2e8f0;
  transition: all 0.3s;
}

.day-card.has-schedule {
  border-color: #4299e1;
}

.day-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f7fafc;
  border-bottom: 1px solid #e2e8f0;
}

.day-header h3 {
  margin: 0;
  color: #2d3748;
}

.day-schedules {
  padding: 1rem;
}

.schedule-item {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  background: white;
  transition: all 0.2s;
}

.schedule-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.schedule-item.inactive {
  opacity: 0.6;
  background: #f7fafc;
}

.schedule-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.time-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.schedule-details {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #718096;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.schedule-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.table-card {
  margin-bottom: 2rem;
}

.schedule-table {
  border-radius: 8px;
  overflow: hidden;
}

.day-cell,
.time-cell,
.patients-cell,
.break-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.schedule-modal {
  border-radius: 12px;
}

@media (max-width: 768px) {
  .enhanced-schedule-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    text-align: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .schedule-grid {
    grid-template-columns: 1fr;
  }
}
</style>