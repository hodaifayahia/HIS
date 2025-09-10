
<script setup>
import { ref, computed, onMounted } from 'vue'
import Card from 'primevue/card'
import Chart from 'primevue/chart'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

// Reactive data
const dashboardData = ref({
  totalCompanies: 0,
  activeContracts: 0,
  pendingContracts: 0,
  expiredContracts: 0,
  recentContracts: [],
  topCompanies: [],
  specialtyStats: [],
  monthlyContractData: []
})

// Chart data
const contractStatusChartData = computed(() => ({
  labels: ['Active', 'Pending', 'Expired'],
  datasets: [{
    data: [
      dashboardData.value.activeContracts,
      dashboardData.value.pendingContracts,
      dashboardData.value.expiredContracts
    ],
    backgroundColor: [
      '#10B981', // Green for active
      '#F59E0B', // Orange for pending
      '#EF4444'  // Red for expired
    ],
    borderWidth: 2,
    borderColor: '#ffffff'
  }]
}))

const monthlyChartData = computed(() => ({
  labels: dashboardData.value.monthlyContractData.map(item => {
    const date = new Date(item.month + '-01')
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
  }),
  datasets: [{
    label: 'Contracts Created',
    data: dashboardData.value.monthlyContractData.map(item => item.count),
    backgroundColor: '#3B82F6',
    borderColor: '#1D4ED8',
    borderWidth: 1
  }]
}))

// Chart options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: true,
  aspectRatio: 1.5,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 10,
        usePointStyle: true
      }
    }
  }
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: true,
  aspectRatio: 1.8,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1
      }
    }
  },
  layout: {
    padding: {
      top: 10,
      bottom: 10
    }
  }
}

// Methods
const getStatusSeverity = (status) => {
  switch (status.toLowerCase()) {
    case 'active':
      return 'success'
    case 'pending':
      return 'warning'
    case 'expired':
      return 'danger'
    default:
      return 'info'
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const fetchDashboardData = async () => {
  try {
    const response = await fetch(`/api/convention/dashboard`)
    const data = await response.json()
    dashboardData.value = data
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="dashboard-container">
    <!-- Header Section -->
    <div class="header-section">
      <h1 class="dashboard-title">Dashboard Overview</h1>
      <p class="dashboard-subtitle">Contract Management System Analytics</p>
    </div>

    <!-- Stats Cards Row -->
    <div class="stats-row">
      <div class="stat-card blue">
        <i class="pi pi-building stat-icon"></i>
        <div class="stat-content">
          <div class="stat-number">{{ dashboardData.totalCompanies }}</div>
          <div class="stat-label">Total Companies</div>
        </div>
      </div>
      
      <div class="stat-card green">
        <i class="pi pi-check-circle stat-icon"></i>
        <div class="stat-content">
          <div class="stat-number">{{ dashboardData.activeContracts }}</div>
          <div class="stat-label">Active Contracts</div>
        </div>
      </div>
      
      <div class="stat-card orange">
        <i class="pi pi-clock stat-icon"></i>
        <div class="stat-content">
          <div class="stat-number">{{ dashboardData.pendingContracts }}</div>
          <div class="stat-label">Pending Contracts</div>
        </div>
      </div>
      
      <div class="stat-card red">
        <i class="pi pi-times-circle stat-icon"></i>
        <div class="stat-content">
          <div class="stat-number">{{ dashboardData.expiredContracts }}</div>
          <div class="stat-label">Expired Contracts</div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
      <!-- Contract Status Chart -->
      <Card class="chart-card">
        <template #title>
          <div class="card-title">
            <i class="pi pi-chart-pie"></i>
            Contract Status Distribution
          </div>
        </template>
        <template #content>
          <div class="chart-container">
            <Chart type="doughnut" :data="contractStatusChartData" :options="chartOptions" />
          </div>
        </template>
      </Card>

      <!-- Monthly Contracts Chart -->
      <Card class="chart-card">
        <template #title>
          <div class="card-title">
            <i class="pi pi-chart-line"></i>
            Monthly Contract Trends
          </div>
        </template>
        <template #content>
          <div class="chart-container">
            <Chart type="bar" :data="monthlyChartData" :options="barChartOptions" />
          </div>
        </template>
      </Card>
    </div>

    <!-- Tables Row -->
    <div class="tables-row">
      <!-- Recent Contracts Table -->
      <Card class="table-card-large">
        <template #title>
          <div class="card-title-with-badge">
            <div class="card-title">
              <i class="pi pi-file-text"></i>
              Recent Contracts
            </div>
            <Badge :value="dashboardData.recentContracts.length" severity="info" />
          </div>
        </template>
        <template #content>
          <DataTable :value="dashboardData.recentContracts" :paginator="true" :rows="5" responsiveLayout="scroll">
            <Column field="contract_name" header="Contract Name" sortable>
              <template #body="slotProps">
                <div class="contract-name">{{ slotProps.data.contract_name }}</div>
              </template>
            </Column>
            <Column field="company_name" header="Company" sortable>
              <template #body="slotProps">
                <div class="company-cell">
                  <i class="pi pi-building"></i>
                  {{ slotProps.data.company_name }}
                </div>
              </template>
            </Column>
            <Column field="status" header="Status" sortable>
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.status" 
                  :severity="getStatusSeverity(slotProps.data.status)"
                  rounded
                />
              </template>
            </Column>
            <Column field="created_at" header="Created Date" sortable>
              <template #body="slotProps">
                {{ formatDate(slotProps.data.created_at) }}
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Top Companies -->
      <Card class="table-card-small">
        <template #title>
          <div class="card-title">
            <i class="pi pi-star"></i>
            Top Companies
          </div>
        </template>
        <template #content>
          <div class="companies-list">
            <div 
              v-for="(company, index) in dashboardData.topCompanies" 
              :key="company.id"
              class="company-item"
            >
              <div class="company-info">
                <div class="company-rank">{{ index + 1 }}</div>
                <div class="company-details">
                  <div class="company-name">{{ company.company_name }}</div>
                  <div class="company-type">
                    <i class="pi pi-globe"></i>
                    {{ company.is_public === 'yes' ? 'Public' : 'Private' }}
                  </div>
                </div>
              </div>
              <Badge :value="company.contract_count + ' contracts'" severity="info" />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Specialty Statistics -->
    <div class="specialty-section">
      <Card class="specialty-card">
        <template #title>
          <div class="card-title">
            <i class="pi pi-heart"></i>
            Medical Specialties Overview
          </div>
        </template>
        <template #content>
          <DataTable :value="dashboardData.specialtyStats" responsiveLayout="scroll">
            <Column field="specialty_name" header="Specialty" sortable>
              <template #body="slotProps">
                <div class="specialty-name">{{ slotProps.data.specialty_name }}</div>
              </template>
            </Column>
            <Column field="description" header="Description">
              <template #body="slotProps">
                <div class="specialty-description">{{ slotProps.data.description }}</div>
              </template>
            </Column>
            <Column field="annex_count" header="Annexes" sortable>
              <template #body="slotProps">
                <div class="count-cell">
                  <i class="pi pi-file blue-icon"></i>
                  <span>{{ slotProps.data.annex_count }}</span>
                </div>
              </template>
            </Column>
            <Column field="prestation_count" header="Prestations" sortable>
              <template #body="slotProps">
                <div class="count-cell">
                  <i class="pi pi-cog green-icon"></i>
                  <span>{{ slotProps.data.prestation_count }}</span>
                </div>
              </template>
            </Column>

          </DataTable>
        </template>
      </Card>
    </div>
  </div>
</template>


<style scoped>
/* Main Container */
.dashboard-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px;
  min-height: 100vh;
  max-width: 1400px;
  margin: 0 auto;
}

/* Header Section */
.header-section {
  padding: 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  color: white;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 8px 0;
}

.dashboard-subtitle {
  font-size: 1.1rem;
  margin: 0;
  opacity: 0.9;
}

/* Stats Cards Row */
.stats-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.stat-card {
  flex: 1;
  min-width: 250px;
  display: flex;
  align-items: center;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  gap: 16px;
}

.stat-card.blue {
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  border-left: 4px solid #3b82f6;
}

.stat-card.green {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  border-left: 4px solid #10b981;
}

.stat-card.orange {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-left: 4px solid #f59e0b;
}

.stat-card.red {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
  border-left: 4px solid #ef4444;
}

.stat-icon {
  font-size: 2.5rem;
  opacity: 0.8;
}

.stat-card.blue .stat-icon { color: #1d4ed8; }
.stat-card.green .stat-icon { color: #059669; }
.stat-card.orange .stat-icon { color: #d97706; }
.stat-card.red .stat-icon { color: #dc2626; }

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-card.blue .stat-number { color: #1e3a8a; }
.stat-card.green .stat-number { color: #064e3b; }
.stat-card.orange .stat-number { color: #92400e; }
.stat-card.red .stat-number { color: #991b1b; }

.stat-label {
  font-size: 0.9rem;
  font-weight: 600;
  opacity: 0.8;
}

/* Charts Row */
.charts-row {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
}

.chart-card {
  flex: 1;
  min-width: 400px;
}

.chart-container {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Tables Row */
.tables-row {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
}

.table-card-large {
  flex: 2;
  min-width: 600px;
}

.table-card-small {
  flex: 1;
  min-width: 350px;
}

/* Companies List */
.companies-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.company-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background-color: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.company-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.company-rank {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background-color: #3b82f6;
  color: white;
  font-weight: 700;
  border-radius: 6px;
  font-size: 0.9rem;
}

.company-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.company-name {
  font-weight: 600;
  color: #1e293b;
}

.company-type {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.85rem;
  color: #64748b;
}

/* Specialty Section */
.specialty-section {
  width: 100%;
}

.specialty-card {
  width: 100%;
}

/* Card Titles */
.card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #1e293b;
  font-weight: 600;
  font-size: 1.1rem;
}

.card-title-with-badge {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

/* Table Cell Styles */
.contract-name {
  font-weight: 600;
  color: #1e293b;
}

.company-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
}

.specialty-name {
  font-weight: 600;
  color: #3b82f6;
}

.specialty-description {
  color: #64748b;
  max-width: 300px;
}

.count-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.blue-icon { color: #3b82f6; }
.green-icon { color: #10b981; }

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-container {
    padding: 16px;
    gap: 16px;
  }
  
  .stats-row {
    flex-direction: column;
  }
  
  .stat-card {
    min-width: unset;
  }
  
  .charts-row {
    flex-direction: column;
  }
  
  .chart-card {
    min-width: unset;
  }
  
  .tables-row {
    flex-direction: column;
  }
  
  .table-card-large,
  .table-card-small {
    min-width: unset;
  }
  
  .dashboard-title {
    font-size: 1.5rem;
  }
  
  .stat-number {
    font-size: 2rem;
  }
}

/* PrimeVue Component Overrides */
:deep(.p-card) {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

:deep(.p-card-content) {
  padding-top: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f1f5f9;
  color: #334155;
  font-weight: 600;
  border: none;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 12px;
  border: none;
  border-bottom: 1px solid #f1f5f9;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8fafc;
}

:deep(.p-tag) {
  font-size: 0.75rem;
  font-weight: 600;
}

:deep(.p-badge) {
  font-size: 0.75rem;
  font-weight: 600;
}
</style>