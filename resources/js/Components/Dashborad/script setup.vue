<script setup>
import { ref, onMounted, computed } from 'vue'
import Chart from 'chart.js/auto'
import 'chartjs-adapter-date-fns'

// Reactive state for dashboard data
const patientStats = ref({
  currentPatients: 275,
  criticalCases: 24,
  pendingTests: 38,
  averageHeartRate: 74
})

const selectedDepartment = ref('All Departments')
const selectedTimeframe = ref('Today')
const activePatientId = ref(1)

// Charts references
const vitalSignsChartRef = ref(null)
const patientDistributionChartRef = ref(null)
const medicationAdherenceChartRef = ref(null)
let vitalSignsChart = null
let patientDistributionChart = null
let medicationAdherenceChart = null

// Sample data for visualizations
const vitalSignsData = ref([
  { time: '08:00', heartRate: 72, bloodPressure: 120, temperature: 98.6, oxygenLevel: 98 },
  { time: '10:00', heartRate: 75, bloodPressure: 122, temperature: 98.7, oxygenLevel: 97 },
  { time: '12:00', heartRate: 78, bloodPressure: 124, temperature: 98.9, oxygenLevel: 97 },
  { time: '14:00', heartRate: 74, bloodPressure: 118, temperature: 98.5, oxygenLevel: 99 },
  { time: '16:00', heartRate: 73, bloodPressure: 119, temperature: 98.6, oxygenLevel: 98 },
  { time: '18:00', heartRate: 71, bloodPressure: 117, temperature: 98.4, oxygenLevel: 98 }
])

const patientDistributionData = ref([
  { name: 'ICU', count: 24, color: '#e63757' },
  { name: 'ER', count: 45, color: '#f6c343' },
  { name: 'General', count: 86, color: '#2c7be5' },
  { name: 'Outpatient', count: 120, color: '#00d97e' }
])

const medicationAdherenceData = ref([
  { name: 'Mon', adherence: 92 },
  { name: 'Tue', adherence: 88 },
  { name: 'Wed', adherence: 94 },
  { name: 'Thu', adherence: 91 },
  { name: 'Fri', adherence: 89 },
  { name: 'Sat', adherence: 85 },
  { name: 'Sun', adherence: 84 }
])

// Recent alerts
const recentAlerts = ref([
  { id: 1, patient: 'Morgan, J.', type: 'critical', message: 'Abnormal heart rhythm detected', time: '14 min ago' },
  { id: 2, patient: 'Chen, L.', type: 'warning', message: 'Medication schedule missed', time: '23 min ago' },
  { id: 3, patient: 'Garcia, M.', type: 'info', message: 'Lab results ready for review', time: '46 min ago' }
])

// Active patients
const activePatients = ref([
  { id: 1, name: 'Emma Wilson', age: 45, condition: 'Post-Op Recovery', status: 'stable', room: '302' },
  { id: 2, name: 'Michael Chen', age: 67, condition: 'Cardiac Monitoring', status: 'critical', room: 'ICU-4' },
  { id: 3, name: 'Sofia Rodriguez', age: 32, condition: 'Pregnancy (3rd trimester)', status: 'stable', room: '215' },
  { id: 4, name: 'James Taylor', age: 58, condition: 'Pneumonia', status: 'improving', room: '187' }
])

// Computed properties
const filteredPatients = computed(() => {
  if (selectedDepartment.value === 'All Departments') {
    return activePatients.value
  }
  // In a real app, you would filter based on department
  return activePatients.value.slice(0, 2)
})

const selectedPatient = computed(() => {
  return activePatients.value.find(patient => patient.id === activePatientId.value) || activePatients.value[0]
})

// Methods
const initCharts = () => {
  // Initialize Vital Signs Chart
  vitalSignsChart = new Chart(vitalSignsChartRef.value, {
    type: 'line',
    data: {
      labels: vitalSignsData.value.map(item => item.time),
      datasets: [
        {
          label: 'Heart Rate',
          data: vitalSignsData.value.map(item => item.heartRate),
          borderColor: '#e63757',
          backgroundColor: 'rgba(230, 55, 87, 0.1)',
          borderWidth: 2,
          fill: false,
          tension: 0.4
        },
        {
          label: 'Blood Pressure',
          data: vitalSignsData.value.map(item => item.bloodPressure),
          borderColor: '#2c7be5',
          backgroundColor: 'rgba(44, 123, 229, 0.1)',
          borderWidth: 2,
          fill: false,
          tension: 0.4
        },
        {
          label: 'Oxygen Level',
          data: vitalSignsData.value.map(item => item.oxygenLevel),
          borderColor: '#00d97e',
          backgroundColor: 'rgba(0, 217, 126, 0.1)',
          borderWidth: 2,
          fill: false,
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          align: 'end'
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          }
        },
        y: {
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            precision: 0
          }
        }
      }
    }
  })

  // Initialize Patient Distribution Chart
  patientDistributionChart = new Chart(patientDistributionChartRef.value, {
    type: 'doughnut',
    data: {
      labels: patientDistributionData.value.map(item => item.name),
      datasets: [{
        data: patientDistributionData.value.map(item => item.count),
        backgroundColor: patientDistributionData.value.map(item => item.color),
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      },
      cutout: '70%'
    }
  })

  // Initialize Medication Adherence Chart  
  medicationAdherenceChart = new Chart(medicationAdherenceChartRef.value, {
    type: 'bar',
    data: {
      labels: medicationAdherenceData.value.map(item => item.name),
      datasets: [{
        label: 'Adherence %',
        data: medicationAdherenceData.value.map(item => item.adherence),
        backgroundColor: '#6b5eae',
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          }
        },
        y: {
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          min: 0,
          max: 100
        }
      }
    }
  })
}

// Lifecycle hooks
onMounted(() => {
  initCharts()
  
  // Simulate data updates in a real application
  setInterval(() => {
    // Update data (in a real app, this would be an API call)
    vitalSignsData.value = vitalSignsData.value.map(item => ({
      ...item,
      heartRate: item.heartRate + Math.floor(Math.random() * 3) - 1
    }))
    
    // Update the chart
    vitalSignsChart.data.datasets[0].data = vitalSignsData.value.map(item => item.heartRate)
    vitalSignsChart.update()
  }, 5000)
})

// Utility functions
const getStatusClass = (status) => {
  switch (status) {
    case 'critical': return 'bg-red-100 text-red-800'
    case 'warning': return 'bg-yellow-100 text-yellow-800'
    case 'info': return 'bg-blue-100 text-blue-800'
    case 'stable': return 'bg-green-100 text-green-800'
    case 'improving': return 'bg-blue-100 text-blue-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const formatDate = () => {
  const now = new Date()
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
  return now.toLocaleDateString('en-US', options)
}

const selectPatient = (id) => {
  activePatientId.value = id
}

const refreshData = () => {
  // In a real app, this would fetch fresh data from an API
  alert('Refreshing data...')
}
</script>

<template>
  <div class="medical-dashboard">
    <!-- Header -->
    <header class="dashboard-header">
      <div class="logo-container">
        <div class="logo">MD</div>
        <h1>MediDash</h1>
      </div>
      <div class="user-controls">
        <button class="notification-btn">
          <span class="notification-count">3</span>
          <i class="bi bi-bell"></i>
        </button>
        <div class="user-info">
          <div class="avatar"></div>
          <span>Dr. Sarah Chen</span>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="dashboard-content">
      <!-- Dashboard header with controls -->
      <div class="dashboard-controls">
        <div class="dashboard-title">
          <h2>Patient Overview</h2>
          <p>{{ formatDate() }}</p>
        </div>
        <div class="dashboard-filters">
          <select v-model="selectedDepartment" class="filter-select">
            <option>All Departments</option>
            <option>Cardiology</option>
            <option>Neurology</option>
            <option>Pediatrics</option>
          </select>
          <select v-model="selectedTimeframe" class="filter-select">
            <option>Today</option>
            <option>Yesterday</option>
            <option>Last Week</option>
            <option>Last Month</option>
          </select>
          <button @click="refreshData" class="refresh-btn">
            <i class="bi bi-arrow-clockwise"></i>
            Refresh
          </button>
        </div>
      </div>

      <!-- KPI Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-content">
            <div>
              <p class="stat-label">Current Patients</p>
              <h3 class="stat-value">{{ patientStats.currentPatients }}</h3>
              <p class="stat-change positive">↑ 3 from yesterday</p>
            </div>
            <div class="stat-icon blue">
              <i class="bi bi-people"></i>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-content">
            <div>
              <p class="stat-label">Critical Cases</p>
              <h3 class="stat-value">{{ patientStats.criticalCases }}</h3>
              <p class="stat-change negative">↑ 2 from yesterday</p>
            </div>
            <div class="stat-icon red">
              <i class="bi bi-heart-pulse"></i>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-content">
            <div>
              <p class="stat-label">Pending Tests</p>
              <h3 class="stat-value">{{ patientStats.pendingTests }}</h3>
              <p class="stat-change positive">↓ 5 from yesterday</p>
            </div>
            <div class="stat-icon green">
              <i class="bi bi-clipboard-check"></i>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-content">
            <div>
              <p class="stat-label">Avg. Heart Rate</p>
              <h3 class="stat-value">{{ patientStats.averageHeartRate }} <span class="stat-unit">bpm</span></h3>
              <p class="stat-change neutral">Stable</p>
            </div>
            <div class="stat-icon purple">
              <i class="bi bi-activity"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main grid layout -->
      <div class="dashboard-grid">
        <!-- Vital Signs Chart -->
        <div class="dashboard-card vital-signs">
          <div class="card-header">
            <h3>Vital Signs</h3>
            <div class="card-controls">
              <select class="compact-select">
                <option>Last 24 Hours</option>
                <option>Last Week</option>
              </select>
            </div>
          </div>
          <div class="chart-container">
            <canvas ref="vitalSignsChartRef"></canvas>
          </div>
        </div>

        <!-- Patient Distribution -->
        <div class="dashboard-card patient-distribution">
          <div class="card-header">
            <h3>Patient Distribution</h3>
          </div>
          <div class="chart-container">
            <canvas ref="patientDistributionChartRef"></canvas>
          </div>
        </div>

        <!-- Medication Adherence -->
        <div class="dashboard-card medication-adherence">
          <div class="card-header">
            <h3>Medication Adherence</h3>
            <div class="card-controls">
              <select class="compact-select">
                <option>This Week</option>
                <option>Last Week</option>
              </select>
            </div>
          </div>
          <div class="chart-container">
            <canvas ref="medicationAdherenceChartRef"></canvas>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>


<style scoped>
.dashboard { background: #F7F9FC; padding: 2rem; }
.header { display: flex; justify-content: space-between; color: #1A237E; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.kpi-card { 
  padding: 1.5rem; 
  border-radius: 12px; 
  color: white; 
  background: linear-gradient(135deg, #26A69A, #4DB6AC); 
}
.bg-navy { background: linear-gradient(135deg, #1A237E, #3949AB); }
.bg-coral { background: linear-gradient(135deg, #FF6F61, #FF8A80); }
.value { font-size: 2rem; font-weight: 600; }
.label { font-size: 1rem; opacity: 0.9; }
.h-200 { height: 200px; }
</style>



        <!-- Charts -->
   <!--     <section class="charts row g-3">
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Revenue Trends</h3>
                <select v-model="chartType" class="chart-selector" aria-label="Chart type selector">
                  <option value="line">Line</option>
                  <option value="bar">Bar</option>
                  <option value="scatter">Scatter</option>
                </select>
              </div>
              <div class="card-body">
                <canvas id="revenue-chart" aria-label="Revenue trends over time"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card bg-primary">
              <div class="card-header">
                <h3 class="card-title text-white">Quick Stats</h3>
              </div>
              <div class="card-body">
                <div id="quick-stats" class="h-200"></div>
              </div>
            </div>
          </div>
        </section>-->
