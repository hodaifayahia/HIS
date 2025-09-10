<template>
  <div class="crm-dashboard">
    <!-- Header -->
    <header class="dashboard-header">
      <div class="header-left">
        <h1 class="dashboard-title">
          <span class="title-icon">📊</span>
          Tableau de bord CRM
        </h1>
        <div class="breadcrumb">
          <span>Accueil</span>
          <span class="separator">></span>
          <span class="current">Dashboard</span>
        </div>
      </div>
      <div class="header-right">
        <div class="date-filter">
          <select v-model="selectedPeriod" class="period-select">
            <option value="7">7 derniers jours</option>
            <option value="30">30 derniers jours</option>
            <option value="90">90 derniers jours</option>
            <option value="365">Cette année</option>
          </select>
        </div>
        <button class="refresh-btn" @click="refreshData">
          <span class="refresh-icon">🔄</span>
          Actualiser
        </button>
      </div>
    </header>

    <!-- Key Metrics Row -->
    <div class="metrics-row">
      <div class="metric-card revenue">
        <div class="metric-icon">💰</div>
        <div class="metric-content">
          <h3>Revenus du mois</h3>
          <p class="metric-value">{{ formatCurrency(metrics.monthlyRevenue) }}</p>
          <span class="metric-change positive">+12.5%</span>
        </div>
      </div>
      <div class="metric-card deals">
        <div class="metric-icon">🤝</div>
        <div class="metric-content">
          <h3>Affaires conclues</h3>
          <p class="metric-value">{{ metrics.dealsWon }}</p>
          <span class="metric-change positive">+8.2%</span>
        </div>
      </div>
      <div class="metric-card leads">
        <div class="metric-icon">👥</div>
        <div class="metric-content">
          <h3>Nouveaux prospects</h3>
          <p class="metric-value">{{ metrics.newLeads }}</p>
          <span class="metric-change negative">-2.1%</span>
        </div>
      </div>
      <div class="metric-card satisfaction">
        <div class="metric-icon">⭐</div>
        <div class="metric-content">
          <h3>Satisfaction client</h3>
          <p class="metric-value">{{ metrics.satisfaction }}/5</p>
          <span class="metric-change positive">+0.3</span>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="main-grid">
      <!-- Pipeline Overview -->
      <div class="dashboard-card pipeline-card">
        <div class="card-header">
          <h2 class="card-title">
            <span class="title-icon">🎯</span>
            Pipeline des ventes
          </h2>
          <div class="card-actions">
            <button class="action-btn">Voir tout</button>
          </div>
        </div>
        <div class="pipeline-stages">
          <div v-for="stage in pipelineStages" :key="stage.name" class="stage-column">
            <div class="stage-header">
              <h4>{{ stage.name }}</h4>
              <span class="stage-count">{{ stage.count }}</span>
            </div>
            <div class="stage-value">{{ formatCurrency(stage.value) }}</div>
            <div class="stage-progress">
              <div class="progress-bar" :style="{ width: stage.progress + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="dashboard-card activity-card">
        <div class="card-header">
          <h2 class="card-title">
            <span class="title-icon">📋</span>
            Activités récentes
          </h2>
          <div class="card-actions">
            <button class="action-btn">Voir tout</button>
          </div>
        </div>
        <div class="activity-feed">
          <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
            <div class="activity-avatar" :class="activity.type">
              <span>{{ activity.avatar }}</span>
            </div>
            <div class="activity-content">
              <p class="activity-text">{{ activity.text }}</p>
              <span class="activity-time">{{ formatTime(activity.time) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Performing Team -->
      <div class="dashboard-card team-card">
        <div class="card-header">
          <h2 class="card-title">
            <span class="title-icon">🏆</span>
            Top équipe
          </h2>
        </div>
        <div class="team-list">
          <div v-for="member in topTeam" :key="member.id" class="team-member">
            <div class="member-avatar">
              <img :src="member.avatar" :alt="member.name" />
            </div>
            <div class="member-info">
              <h4>{{ member.name }}</h4>
              <p>{{ member.role }}</p>
            </div>
            <div class="member-stats">
              <span class="deals-count">{{ member.deals }} affaires</span>
              <span class="revenue">{{ formatCurrency(member.revenue) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="dashboard-card actions-card">
        <div class="card-header">
          <h2 class="card-title">
            <span class="title-icon">⚡</span>
            Actions rapides
          </h2>
        </div>
        <div class="quick-actions">
          <button class="quick-action-btn primary">
            <span class="action-icon">👤</span>
            Nouveau prospect
          </button>
          <button class="quick-action-btn secondary">
            <span class="action-icon">📞</span>
            Planifier appel
          </button>
          <button class="quick-action-btn secondary">
            <span class="action-icon">📧</span>
            Campagne email
          </button>
          <button class="quick-action-btn secondary">
            <span class="action-icon">📊</span>
            Rapport
          </button>
        </div>
      </div>

      <!-- Tickets & Support -->
      <div class="dashboard-card support-card">
        <div class="card-header">
          <h2 class="card-title">
            <span class="title-icon">🎫</span>
            Support & Tickets
          </h2>
        </div>
        <div class="support-stats">
          <div class="support-metric">
            <div class="metric-circle open">
              <span>{{ supportStats.open }}</span>
            </div>
            <p>Tickets ouverts</p>
          </div>
          <div class="support-metric">
            <div class="metric-circle pending">
              <span>{{ supportStats.pending }}</span>
            </div>
            <p>En attente</p>
          </div>
          <div class="support-metric">
            <div class="metric-circle resolved">
              <span>{{ supportStats.resolved }}</span>
            </div>
            <p>Résolus</p>
          </div>
        </div>
        <div class="support-footer">
          <p>Temps de réponse moyen: <strong>{{ supportStats.avgResponse }}</strong></p>
        </div>
      </div>

      <!-- Calendar/Tasks -->
      <div class="dashboard-card calendar-card">
        <div class="card-header">
          <h2 class="card-title">
            <span class="title-icon">📅</span>
            Prochaines tâches
          </h2>
        </div>
        <div class="task-list">
          <div v-for="task in upcomingTasks" :key="task.id" class="task-item">
            <div class="task-priority" :class="task.priority"></div>
            <div class="task-content">
              <h4>{{ task.title }}</h4>
              <p>{{ task.description }}</p>
              <span class="task-due">{{ formatDate(task.dueDate) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Reactive data
const selectedPeriod = ref('30')

const metrics = ref({
  monthlyRevenue: 2850000,
  dealsWon: 47,
  newLeads: 124,
  satisfaction: 4.6
})

const pipelineStages = ref([
  { name: 'Prospects', count: 28, value: 420000, progress: 85 },
  { name: 'Qualification', count: 15, value: 675000, progress: 65 },
  { name: 'Proposition', count: 8, value: 480000, progress: 40 },
  { name: 'Négociation', count: 5, value: 280000, progress: 25 },
  { name: 'Conclusion', count: 3, value: 180000, progress: 15 }
])

const recentActivities = ref([
  {
    id: 1,
    type: 'deal',
    avatar: '🤝',
    text: 'Affaire "Système ERP" conclue par Ahmed Benali',
    time: '2025-07-09T10:30:00'
  },
  {
    id: 2,
    type: 'lead',
    avatar: '👤',
    text: 'Nouveau prospect ajouté: Société TechCorp',
    time: '2025-07-09T09:15:00'
  },
  {
    id: 3,
    type: 'meeting',
    avatar: '📅',
    text: 'Réunion prévue avec client Pharmaceutique Inc.',
    time: '2025-07-09T08:45:00'
  },
  {
    id: 4,
    type: 'email',
    avatar: '📧',
    text: 'Campagne email "Offres été" envoyée à 500 contacts',
    time: '2025-07-08T16:20:00'
  }
])

const topTeam = ref([
  {
    id: 1,
    name: 'Sarah Mahmoudi',
    role: 'Responsable commerciale',
    avatar: 'https://images.unsplash.com/photo-1494790108755-2616b5ad40c5?w=40&h=40&fit=crop&crop=face',
    deals: 12,
    revenue: 450000
  },
  {
    id: 2,
    name: 'Karim Benzema',
    role: 'Commercial senior',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face',
    deals: 8,
    revenue: 320000
  },
  {
    id: 3,
    name: 'Fatima Zahra',
    role: 'Commerciale',
    avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=40&h=40&fit=crop&crop=face',
    deals: 6,
    revenue: 280000
  }
])

const supportStats = ref({
  open: 12,
  pending: 8,
  resolved: 45,
  avgResponse: '2h 15min'
})

const upcomingTasks = ref([
  {
    id: 1,
    title: 'Appel de suivi - Client ABC',
    description: 'Discuter des détails du contrat',
    dueDate: '2025-07-10T14:00:00',
    priority: 'high'
  },
  {
    id: 2,
    title: 'Présentation produit',
    description: 'Démonstration pour nouveau prospect',
    dueDate: '2025-07-11T10:30:00',
    priority: 'medium'
  },
  {
    id: 3,
    title: 'Rapport mensuel',
    description: 'Finaliser le rapport de performance',
    dueDate: '2025-07-12T09:00:00',
    priority: 'low'
  }
])

// Methods
const formatCurrency = (amount) => {
  if (!amount) return '0 DZD'
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  const now = new Date()
  const date = new Date(dateString)
  const diffMs = now - date
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffMins = Math.floor(diffMs / (1000 * 60))
  
  if (diffHours > 24) {
    return `${Math.floor(diffHours / 24)}j`
  } else if (diffHours > 0) {
    return `${diffHours}h`
  } else {
    return `${diffMins}min`
  }
}

const refreshData = () => {
  // Simulate data refresh
  console.log('Refreshing data...')
}

onMounted(() => {
  // Initialize component
  console.log('CRM Dashboard loaded')
})
</script>

<style scoped>
.crm-dashboard {
  min-height: 100vh;
  background: linear-gradient(135deg, #dbd9f8 0%, #ffffff 100%);
  padding: 0;
}

.dashboard-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.dashboard-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.title-icon {
  font-size: 1.5rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
  background: rgba(255, 255, 255, 0.8);
}

.separator {
  color: #cbd5e0;
}

.current {
  color: #3b82f6;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.period-select {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  font-size: 0.875rem;
  color: #374151;
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.refresh-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.refresh-icon {
  font-size: 1rem;
}

.metrics-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  padding: 2rem;
  padding-bottom: 1rem;
}

.metric-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 1rem;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s;
}

.metric-card:hover {
  transform: translateY(-2px);
}

.metric-icon {
  font-size: 2rem;
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.metric-content h3 {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.metric-change {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.125rem 0.5rem;
  border-radius: 0.375rem;
}

.metric-change.positive {
  color: #059669;
  background: #d1fae5;
}

.metric-change.negative {
  color: #dc2626;
  background: #fee2e2;
}

.main-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  padding: 1rem 2rem 2rem;
}

.dashboard-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s;
}

.dashboard-card:hover {
  transform: translateY(-2px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.action-btn {
  font-size: 0.75rem;
  color: #3b82f6;
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
}

.pipeline-card {
  grid-column: span 2;
}

.pipeline-stages {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
}

.stage-column {
  flex: 1;
  min-width: 140px;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
}

.stage-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.stage-header h4 {
  margin: 0;
  font-size: 0.875rem;
  color: #374151;
  font-weight: 500;
}

.stage-count {
  background: #3b82f6;
  color: white;
  padding: 0.125rem 0.375rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.stage-value {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.75rem;
}

.stage-progress {
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  transition: width 0.3s ease;
}

.activity-feed {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 300px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.activity-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.activity-avatar.deal {
  background: #dbeafe;
}

.activity-avatar.lead {
  background: #f3e8ff;
}

.activity-avatar.meeting {
  background: #fef3c7;
}

.activity-avatar.email {
  background: #d1fae5;
}

.activity-content {
  flex: 1;
}

.activity-text {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  color: #374151;
}

.activity-time {
  font-size: 0.75rem;
  color: #6b7280;
}

.team-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.team-member {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
}

.member-avatar img {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  object-fit: cover;
}

.member-info {
  flex: 1;
}

.member-info h4 {
  margin: 0 0 0.125rem 0;
  font-size: 0.875rem;
  color: #1f2937;
  font-weight: 500;
}

.member-info p {
  margin: 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.member-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.125rem;
}

.deals-count {
  font-size: 0.75rem;
  color: #3b82f6;
  font-weight: 500;
}

.revenue {
  font-size: 0.875rem;
  color: #1f2937;
  font-weight: 600;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.quick-action-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-action-btn.primary {
  background: #3b82f6;
  color: white;
}

.quick-action-btn.secondary {
  background: #f8fafc;
  color: #374151;
  border: 1px solid #e2e8f0;
}

.quick-action-btn:hover {
  transform: translateY(-1px);
}

.quick-action-btn.primary:hover {
  background: #2563eb;
}

.quick-action-btn.secondary:hover {
  background: #f1f5f9;
}

.action-icon {
  font-size: 1rem;
}

.support-stats {
  display: flex;
  justify-content: space-around;
  margin-bottom: 1.5rem;
}

.support-metric {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.metric-circle {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 600;
  color: white;
}

.metric-circle.open {
  background: #ef4444;
}

.metric-circle.pending {
  background: #f59e0b;
}

.metric-circle.resolved {
  background: #10b981;
}

.support-metric p {
  margin: 0;
  font-size: 0.75rem;
  color: #6b7280;
  text-align: center;
}

.support-footer {
  text-align: center;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.support-footer p {
  margin: 0;
  font-size: 0.875rem;
  color: #374151;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.task-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
}

.task-priority {
  width: 4px;
  border-radius: 2px;
  flex-shrink: 0;
}

.task-priority.high {
  background: #ef4444;
}

.task-priority.medium {
  background: #f59e0b;
}

.task-priority.low {
  background: #10b981;
}

.task-content {
  flex: 1;
}

.task-content h4 {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  color: #1f2937;
  font-weight: 500;
}

.task-content p {
  margin: 0 0 0.5rem 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.task-due {
  font-size: 0.75rem;
  color: #3b82f6;
  font-weight: 500;
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-right {
    justify-content: space-between;
  }
  
  .metrics-row {
    grid-template-columns: 1fr;
  }
  
  .main-grid {
    grid-template-columns: 1fr;
  }
  
  .pipeline-card {
    grid-column: span 1;
  }
  
  .pipeline-stages {
    flex-direction: column;
  }
}
</style>