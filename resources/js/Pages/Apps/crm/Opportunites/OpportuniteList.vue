<template>
  <div class="pipeline-container">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-left">
        <h1 class="page-title">
          <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18l-2 13H5L3 6z"/>
            <path d="M3 6l2-2h14l2 2"/>
          </svg>
          Pipeline des Ventes
        </h1>
        <p class="page-subtitle">Gérez vos opportunités et optimisez votre processus de vente</p>
      </div>
      <div class="header-actions">
        <div class="filter-controls">
          <select class="filter-select">
            <option>Tous les propriétaires</option>
            <option>Mes opportunités</option>
            <option>Équipe commerciale</option>
          </select>
          <select class="filter-select">
            <option>Ce mois</option>
            <option>Ce trimestre</option>
            <option>Cette année</option>
          </select>
        </div>
        <button class="primary-btn" @click="openAddModal">
          <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="16"/>
            <line x1="8" y1="12" x2="16" y2="12"/>
          </svg>
          Nouvelle opportunité
        </button>
      </div>
    </div>

    <!-- Pipeline Stats -->
    <div class="stats-section">
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-label">Total Pipeline</span>
          <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div class="stat-value">{{ formatCurrency(getTotalPipelineValue()) }}</div>
        <div class="stat-change positive">+12.5% vs mois dernier</div>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-label">Opportunités actives</span>
          <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="8.5" cy="7" r="4"/>
            <line x1="20" y1="8" x2="20" y2="14"/>
            <line x1="23" y1="11" x2="17" y2="11"/>
          </svg>
        </div>
        <div class="stat-value">{{ getActiveOpportunities() }}</div>
        <div class="stat-change positive">+3 cette semaine</div>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-label">Taux de conversion</span>
          <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
          </svg>
        </div>
        <div class="stat-value">{{ getConversionRate() }}%</div>
        <div class="stat-change negative">-2.1% vs mois dernier</div>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-label">Cycle de vente moyen</span>
          <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12,6 12,12 16,14"/>
          </svg>
        </div>
        <div class="stat-value">24 jours</div>
        <div class="stat-change positive">-3 jours vs mois dernier</div>
      </div>
    </div>

    <!-- Kanban Board -->
    <div class="kanban-wrapper">
      <div class="kanban-board">
        <div
          v-for="stage in stages"
          :key="stage.key"
          class="kanban-column"
          :class="[{'won-column': stage.key === 'WON', 'lost-column': stage.key === 'LOST'}, minimizedStages[stage.key] ? 'minimized' : '']"
          @dragover.prevent
          @drop="onDrop(stage.key)"
        >
          <div class="column-header">
            <div class="column-title">
              <div class="stage-indicator" :style="{ backgroundColor: stage.color }"></div>
              {{ stage.label }}
            </div>
            <div class="column-stats">
              <span class="opportunity-count">{{ opportunitiesByStage(stage.key).length }}</span>
              <span class="stage-value">{{ formatCurrency(getStageValue(stage.key)) }}</span>
            </div>
            <button class="minimize-btn" @click.stop="toggleMinimize(stage.key)">
              <span v-if="!minimizedStages[stage.key]">-</span>
              <span v-else>+</span>
            </button>
          </div>
          <transition name="fade">
            <div v-show="!minimizedStages[stage.key]" class="column-content">
              <div
                v-for="opp in opportunitiesByStage(stage.key)"
                :key="opp.id"
                class="opportunity-card"
                draggable="true"
                @dragstart="onDragStart(opp)"
                @click="openOpportunityDetails(opp)"
              >
                <div class="card-header">
                  <h3 class="opportunity-name">{{ opp.opportunite_name }}</h3>
                  <div class="opportunity-menu">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="1"/>
                      <circle cx="12" cy="5" r="1"/>
                      <circle cx="12" cy="19" r="1"/>
                    </svg>
                  </div>
                </div>
                <div class="card-body">
                  <div class="company-info">
                    <div class="company-avatar">
                      {{ getCompanyInitials(opp.organisme_id) }}
                    </div>
                    <span class="company-name">{{ getOrganismeName(opp.organisme_id) }}</span>
                  </div>
                  <div class="opportunity-value">
                    {{ formatCurrency(opp.estimated_value) }}
                  </div>
                  <div class="opportunity-meta">
                    <div class="meta-item">
                      <svg class="meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                      </svg>
                      <span>{{ formatDate(opp.close_date) }}</span>
                    </div>
                    <div class="meta-item">
                      <svg class="meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                      </svg>
                      <span>{{ getUserName(opp.assigned_to_user_id) }}</span>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                  <div class="probability-bar">
                    <div class="probability-fill" :style="{ width: stage.probability + '%', backgroundColor: stage.color }"></div>
                  </div>
                  <span class="probability-text">{{ stage.probability }}% de probabilité</span>
                </div>
              </div>
              <button class="add-opportunity-btn" @click="openAddModal(stage.key)">
                <svg class="add-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="12" y1="8" x2="12" y2="16"/>
                  <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                Ajouter une opportunité
              </button>
            </div>
          </transition>
        </div>
      </div>
    </div>


    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h2 class="modal-title">
            <svg class="modal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="8.5" cy="7" r="4"/>
              <line x1="20" y1="8" x2="20" y2="14"/>
              <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
            Nouvelle opportunité
          </h2>
          <button class="close-btn" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
        
        <form @submit.prevent="saveOpportunity" class="modal-form">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Nom de l'opportunité *</label>
              <input 
                v-model="form.opportunite_name" 
                class="form-input" 
                placeholder="Ex: Contrat système CRM"
                required 
              />
            </div>
            
            <div class="form-group">
              <label class="form-label">Entreprise *</label>
              <select v-model="form.organisme_id" class="form-select" required>
                <option value="" disabled>Sélectionnez une entreprise</option>
                <option v-for="org in organismes" :key="org.id" :value="org.id">
                  {{ org.raison_sociale }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Étape du pipeline *</label>
              <select v-model="form.stage" class="form-select" required>
                <option v-for="stage in stages" :key="stage.key" :value="stage.key">
                  {{ stage.label }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Valeur estimée (DZD)</label>
              <input 
                v-model="form.estimated_value" 
                type="number" 
                step="1000" 
                class="form-input" 
                placeholder="0"
              />
            </div>
            
            <div class="form-group">
              <label class="form-label">Date de clôture prévue</label>
              <input 
                v-model="form.close_date" 
                type="date" 
                class="form-input"
              />
            </div>
            
            <div class="form-group">
              <label class="form-label">Responsable *</label>
              <select v-model="form.assigned_to_user_id" class="form-select" required>
                <option value="" disabled>Sélectionnez un responsable</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="modal-actions">
            <button type="button" class="secondary-btn" @click="closeModal">
              Annuler
            </button>
            <button type="submit" class="primary-btn">
              <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
              </svg>
              Créer l'opportunité
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const stages = [
  { key: 'PROSPECTING', label: 'Prospection', color: '#8B5CF6', probability: 10 },
  { key: 'QUALIFICATION', label: 'Qualification', color: '#06B6D4', probability: 25 },
  { key: 'NEGOTIATION', label: 'Négociation', color: '#F59E0B', probability: 60 },
  { key: 'WAITING_APPROVAL', label: "En attente de l'accord", color: '#fbbf24', probability: 80 },
  { key: 'WON', label: 'Gagnée', color: '#10B981', probability: 100 },
  { key: 'LOST', label: 'Perdue', color: '#EF4444', probability: 0 },
];

const organismes = ref([
  { id: 1, raison_sociale: 'Entreprise Alpha' },
  { id: 2, raison_sociale: 'Entreprise Beta' },
  { id: 3, raison_sociale: 'TechCorp Solutions' },
  { id: 4, raison_sociale: 'Innovation Labs' },
]);

const users = ref([
  { id: 1, name: 'Ahmed Bensalem' },
  { id: 2, name: 'Fatima Mokhtar' },
  { id: 3, name: 'Karim Belhadj' },
]);

const opportunities = ref([
  { 
    id: 1, 
    opportunite_name: 'Système CRM Enterprise', 
    organisme_id: 1, 
    stage: 'PROSPECTING', 
    estimated_value: 250000, 
    close_date: '2025-09-15', 
    assigned_to_user_id: 1 
  },
  { 
    id: 2, 
    opportunite_name: 'Formation équipe commerciale', 
    organisme_id: 2, 
    stage: 'QUALIFICATION', 
    estimated_value: 75000, 
    close_date: '2025-08-10', 
    assigned_to_user_id: 2 
  },
  { 
    id: 3, 
    opportunite_name: 'Intégration API', 
    organisme_id: 3, 
    stage: 'NEGOTIATION', 
    estimated_value: 120000, 
    close_date: '2025-07-30', 
    assigned_to_user_id: 3 
  },
  { 
    id: 4, 
    opportunite_name: 'Consultation stratégique', 
    organisme_id: 4, 
    stage: 'WON', 
    estimated_value: 45000, 
    close_date: '2025-07-15', 
    assigned_to_user_id: 1 
  },
  // Example for new status:
  {
    id: 5,
    opportunite_name: "Projet ERP - Accord en attente",
    organisme_id: 2,
    stage: 'WAITING_APPROVAL',
    estimated_value: 180000,
    close_date: '2025-10-01',
    assigned_to_user_id: 2
  },
]);

const showModal = ref(false);
const form = ref({
  opportunite_name: '',
  organisme_id: '',
  stage: 'PROSPECTING',
  estimated_value: '',
  close_date: '',
  assigned_to_user_id: '',
});

let draggedOpportunity = null;

// Computed properties
const getTotalPipelineValue = () => {
  return opportunities.value.reduce((sum, opp) => sum + (opp.estimated_value || 0), 0);
};

const getActiveOpportunities = () => {
  return opportunities.value.filter(opp => opp.stage !== 'WON' && opp.stage !== 'LOST').length;
};

const getConversionRate = () => {
  const total = opportunities.value.length;
  const won = opportunities.value.filter(opp => opp.stage === 'WON').length;
  return total > 0 ? Math.round((won / total) * 100) : 0;
};

// Helper functions
function opportunitiesByStage(stage) {
  return opportunities.value.filter(o => o.stage === stage);
}

function getStageValue(stage) {
  return opportunities.value
    .filter(o => o.stage === stage)
    .reduce((sum, opp) => sum + (opp.estimated_value || 0), 0);
}

function getOrganismeName(id) {
  const org = organismes.value.find(o => o.id === id);
  return org ? org.raison_sociale : 'Entreprise inconnue';
}

function getCompanyInitials(id) {
  const org = organismes.value.find(o => o.id === id);
  if (!org) return '?';
  return org.raison_sociale.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2);
}

function getUserName(id) {
  const user = users.value.find(u => u.id === id);
  return user ? user.name : 'Non assigné';
}

function formatCurrency(amount) {
  if (!amount) return '0 DZD';
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 0
  }).format(amount);
}

function formatDate(dateString) {
  if (!dateString) return 'Non définie';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
}

// Drag and drop
function onDragStart(opp) {
  draggedOpportunity = opp;
}

function onDrop(stage) {
  if (draggedOpportunity && draggedOpportunity.stage !== stage) {
    draggedOpportunity.stage = stage;
    // TODO: Update in backend
  }
  draggedOpportunity = null;
}

// Modal functions
function openAddModal(defaultStage = 'PROSPECTING') {
  form.value = {
    opportunite_name: '',
    organisme_id: '',
    stage: defaultStage,
    estimated_value: '',
    close_date: '',
    assigned_to_user_id: '',
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

function saveOpportunity() {
  const newOpportunity = {
    ...form.value,
    id: Date.now(),
    estimated_value: form.value.estimated_value ? parseFloat(form.value.estimated_value) : null,
    assigned_to_user_id: parseInt(form.value.assigned_to_user_id),
    organisme_id: parseInt(form.value.organisme_id),
  };
  
  opportunities.value.push(newOpportunity);
  showModal.value = false;
}

function openOpportunityDetails(opp) {
 router.push({ name: 'crm.opportunite-details', params: { id: opp.id } });
}
const minimizedStages = ref({});

function toggleMinimize(stageKey) {
  minimizedStages.value[stageKey] = !minimizedStages.value[stageKey];
}

</script>

<style scoped>
* {
  box-sizing: border-box;
}

.pipeline-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header Section */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.header-left {
  flex: 1;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 2.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.title-icon {
  width: 2.5rem;
  height: 2.5rem;
  color: #6366f1;
}

.page-subtitle {
  font-size: 1.1rem;
  color: #64748b;
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.filter-controls {
  display: flex;
  gap: 0.5rem;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
}

.primary-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.primary-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px -6px rgba(99, 102, 241, 0.5);
}

.btn-icon {
  width: 1.25rem;
  height: 1.25rem;
}

/* Stats Section */
.stats-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.stat-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #6366f1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.stat-change {
  font-size: 0.875rem;
  font-weight: 500;
}

.stat-change.positive {
  color: #10b981;
}

.stat-change.negative {
  color: #ef4444;
}

/* Kanban Board */
.kanban-wrapper {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.kanban-board {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1.5rem;
  min-height: 600px;
}

.kanban-column {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1rem;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.kanban-column:hover {
  border-color: #e2e8f0;
}

.won-column {
  background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
}

.lost-column {
  background: linear-gradient(135deg, #fef2f2 0%, #fef2f2 100%);
}

.column-header {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.column-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.stage-indicator {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 50%;
}

.column-stats {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.opportunity-count {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.stage-value {
  font-size: 0.875rem;
  color: #1e293b;
  font-weight: 600;
}

.column-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  height: 100%;
}

/* Opportunity Cards */
.opportunity-card {
  background: white;
  border-radius: 10px;
  padding: 1.25rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.2s;
  border-left: 4px solid transparent;
}

.opportunity-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px -8px rgba(0, 0, 0, 0.15);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.opportunity-name {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  line-height: 1.3;
}

.opportunity-menu {
  opacity: 0;
  transition: opacity 0.2s;
}

.opportunity-card:hover .opportunity-menu {
  opacity: 1;
}

.menu-icon {
  width: 1rem;
  height: 1rem;
  color: #64748b;
  cursor: pointer;
}

.card-body {
  margin-bottom: 1rem;
}

.company-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.company-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
}

.company-name {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.opportunity-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.75rem;
}

.opportunity-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: #64748b;
}

.meta-icon {
  width: 1rem;
  height: 1rem;
}

.card-footer {
  border-top: 1px solid #f1f5f9;
  padding-top: 1rem;
}

.probability-bar {
  width: 100%;
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.probability-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s;
}

.probability-text {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.add-opportunity-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: transparent;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: auto;
}

.add-opportunity-btn:hover {
  border-color: #6366f1;
  color: #6366f1;
  background: #f8fafc;
}

.add-icon {
  width: 1rem;
  height: 1rem;
}

/* Enhanced Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2rem 1rem 2rem;
  border-bottom: 1px solid #f1f5f9;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.modal-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #6366f1;
}

.close-btn {
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: #64748b;
  border-radius: 6px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.close-btn svg {
  width: 1.25rem;
  height: 1.25rem;
}

.modal-form {
  padding: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group:first-child {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-input,
.form-select {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s;
  background: white;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input::placeholder {
  color: #9ca3af;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.secondary-btn {
  padding: 0.75rem 1.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  color: #374151;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.secondary-btn:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .kanban-board {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .kanban-column:nth-child(4),
  .kanban-column:nth-child(5) {
    grid-column: span 1;
  }
}

@media (max-width: 768px) {
  .pipeline-container {
    padding: 1rem;
  }
  
  .header-section {
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
  }
  
  .header-actions {
    width: 100%;
    justify-content: space-between;
  }
  
  .filter-controls {
    flex: 1;
  }
  
  .filter-select {
    flex: 1;
  }
  
  .stats-section {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .kanban-board {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .stats-section {
    grid-template-columns: 1fr;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .modal-container {
    width: 95%;
    margin: 1rem;
  }
  
  .modal-header,
  .modal-form {
    padding: 1rem;
  }
}

/* Dark theme support */
@media (prefers-color-scheme: dark) {
  .pipeline-container {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #e2e8f0;
  }
  
  .header-section,
  .stat-card,
  .kanban-wrapper {
    background: #1e293b;
    border: 1px solid #334155;
  }
  
  .opportunity-card {
    background: #0f172a;
    border: 1px solid #334155;
  }
  
  .kanban-column {
    background: #0f172a;
  }
  
  .page-title,
  .stat-value,
  .opportunity-name {
    color: #e2e8f0;
  }
  
  .modal-container {
    background: #1e293b;
    border: 1px solid #334155;
  }
  
  .form-input,
  .form-select {
    background: #0f172a;
    border-color: #334155;
    color: #e2e8f0;
  }
  
  .secondary-btn {
    background: #0f172a;
    border-color: #334155;
    color: #e2e8f0;
  }
}

/* Loading states */
.loading-skeleton {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Accessibility improvements */
.opportunity-card:focus {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

.primary-btn:focus,
.secondary-btn:focus {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}

/* Animation improvements */
.opportunity-card {
  animation: cardSlideIn 0.3s ease-out;
}

@keyframes cardSlideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Drag and drop visual feedback */
.opportunity-card.dragging {
  opacity: 0.5;
  transform: rotate(5deg);
}

.kanban-column.drag-over {
  background: #f0f9ff;
  border-color: #0ea5e9;
}

/* Success/Error states */
.success-message {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.error-message {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}
/* ...existing code... */
.kanban-board {
  display: flex;
  flex-direction: row;
  gap: 1.5rem;
  min-height: 600px;
}
.kanban-column {
  flex: 1 1 0;
  min-width: 260px;
  max-width: 340px;
  transition: all 0.3s cubic-bezier(.4,2,.6,1);
  position: relative;
}
.kanban-column.minimized {
  min-width: 60px;
  max-width: 60px;
  overflow: hidden;
  padding: 0.5rem 0.2rem;
}
.kanban-column .column-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}
.minimize-btn {
  background: #f1f5f9;
  border: none;
  border-radius: 6px;
  width: 28px;
  height: 28px;
  font-size: 1.2rem;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.minimize-btn:hover {
  background: #e0e7ff;
  color: #6366f1;
}
.kanban-column.minimized .column-header {
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
}
.kanban-column.minimized .column-title {
  writing-mode: vertical-rl;
  text-orientation: mixed;
  font-size: 0.9rem;
  margin-bottom: 0;
}
.kanban-column.minimized .column-stats,
.kanban-column.minimized .add-opportunity-btn,
.kanban-column.minimized .column-content {
  display: none !important;
}
.kanban-column.minimized .minimize-btn {
  margin-top: 0.5rem;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>