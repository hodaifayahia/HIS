<template>
  <div class="opportunity-details-page">
    <!-- Header with breadcrumb -->
    <div class="page-header">
      <div class="breadcrumb">
        <span class="breadcrumb-item">Opportunités</span>
        <svg class="breadcrumb-separator" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="breadcrumb-item current">{{ opportunity.opportunite_name }}</span>
      </div>
    </div>

    <!-- Opportunity Header Card -->
    <div class="opp-header-card">
      <div class="opp-header-content">
        <div class="opp-title-section">
          <h1>{{ opportunity.opportunite_name }}</h1>
          <div class="stage-badge" :class="getStageClass(opportunity.stage)">
            <div class="stage-dot"></div>
            {{ getStageLabel(opportunity.stage) }}
          </div>
        </div>
        
        <div class="opp-actions">
          <button class="action-btn secondary">
            <svg class="action-icon" viewBox="0 0 20 20" fill="currentColor">
              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
            </svg>
            Modifier
          </button>
          <button class="action-btn primary">
            <svg class="action-icon" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Marquer comme gagnée
          </button>
        </div>
      </div>

      <div class="opp-info-grid">
        <div class="info-item">
          <div class="info-label">Entreprise</div>
          <div class="info-value">{{ getOrganismeName(opportunity.organisme_id) }}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Valeur estimée</div>
          <div class="info-value value-highlight">
            {{ opportunity.estimated_value ? formatCurrency(opportunity.estimated_value) : '-' }}
          </div>
        </div>
        <div class="info-item">
          <div class="info-label">Date de clôture</div>
          <div class="info-value">{{ formatDate(opportunity.close_date) || '-' }}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Responsable</div>
          <div class="info-value">
            <div class="user-avatar">
              <span>{{ getUserInitials(opportunity.assigned_to_user_id) }}</span>
            </div>
            Utilisateur #{{ opportunity.assigned_to_user_id }}
          </div>
        </div>
      </div>
    </div>

    <!-- Interactions Section -->
    <div class="interactions-section">
      <div class="section-header">
        <div class="section-title">
          <h2>Historique des interactions</h2>
          <div class="interactions-count">{{ interactions.length }} interaction{{ interactions.length !== 1 ? 's' : '' }}</div>
        </div>
        <button class="add-interaction-btn" @click="showAddModal = true">
          <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          Nouvelle interaction
        </button>
      </div>

      <div v-if="interactions.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
        <h3>Aucune interaction</h3>
        <p>Commencez par ajouter une première interaction avec ce client.</p>
        <button class="empty-action-btn" @click="showAddModal = true">
          Ajouter une interaction
        </button>
      </div>

      <div v-else class="interactions-timeline">
        <div v-for="interaction in interactions" :key="interaction.id" class="interaction-item">
          <div class="interaction-timeline-dot" :class="getInteractionTypeClass(interaction.interaction_type)"></div>
          <div class="interaction-card">
            <div class="interaction-header">
              <div class="interaction-type-badge" :class="getInteractionTypeClass(interaction.interaction_type)">
                <svg class="type-icon" viewBox="0 0 20 20" fill="currentColor">
                  <path v-if="interaction.interaction_type === 'CALL'" d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                  <path v-else-if="interaction.interaction_type === 'EMAIL'" d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                  <path v-else-if="interaction.interaction_type === 'SMS'" d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                  <path v-else-if="interaction.interaction_type === 'MEETING'" d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                  <path v-else d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" />
                </svg>
                {{ getTypeLabel(interaction.interaction_type) }}
              </div>
              <div class="interaction-date">{{ formatDateTime(interaction.interaction_date) }}</div>
            </div>
            <div class="interaction-subject">{{ interaction.subject }}</div>
            <div class="interaction-content">{{ interaction.content }}</div>
            <div class="interaction-footer">
              <div class="interaction-author">
                <div class="author-avatar">
                  <span>{{ getUserInitials(interaction.created_by_user_id) }}</span>
                </div>
                <span>Utilisateur #{{ interaction.created_by_user_id }}</span>
              </div>
              <div class="interaction-actions">
                <button class="interaction-action-btn">
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                  </svg>
                </button>
                <button class="interaction-action-btn">
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Add Interaction -->
    <div v-if="showAddModal" class="modal-overlay" @click="showAddModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Nouvelle interaction</h3>
          <button class="modal-close" @click="showAddModal = false">
            <svg viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveInteraction" class="modal-form">
          <div class="form-group">
            <label>Sujet *</label>
            <input v-model="form.subject" type="text" placeholder="Entrez le sujet de l'interaction" required />
          </div>

          <div class="form-group">
            <label>Type d'interaction *</label>
            <select v-model="form.interaction_type" required>
              <option value="" disabled>Sélectionnez un type</option>
              <option v-for="type in interactionTypes" :key="type.key" :value="type.key">{{ type.label }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>Date et heure *</label>
            <input v-model="form.interaction_date" type="datetime-local" required />
          </div>

          <div class="form-group">
            <label>Contenu</label>
            <textarea v-model="form.content" placeholder="Décrivez les détails de l'interaction..." rows="4"></textarea>
          </div>

          <div class="form-group">
            <label>Utilisateur *</label>
            <input v-model="form.created_by_user_id" type="number" placeholder="ID de l'utilisateur" required />
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="showAddModal = false">Annuler</button>
            <button type="submit" class="btn-save">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const opportunity = ref({});
const interactions = ref([]);
const showAddModal = ref(false);
const form = ref({
  subject: '',
  interaction_type: '',
  content: '',
  interaction_date: '',
  created_by_user_id: '',
});

const organismes = [
  { id: 1, raison_sociale: 'Entreprise Alpha' },
  { id: 2, raison_sociale: 'Entreprise Beta' },
];

const stages = [
  { key: 'PROSPECTING', label: 'Prospection' },
  { key: 'QUALIFICATION', label: 'Qualification' },
  { key: 'NEGOTIATION', label: 'Négociation' },
  { key: 'WON', label: 'Gagnée' },
  { key: 'LOST', label: 'Perdue' },
];

const interactionTypes = [
  { key: 'CALL', label: 'Appel' },
  { key: 'EMAIL', label: 'Email' },
  { key: 'SMS', label: 'SMS' },
  { key: 'MEETING', label: 'Réunion' },
  { key: 'IN_APP_MESSAGE', label: 'Message in-app' },
];

function getOrganismeName(id) {
  const org = organismes.find(o => o.id === id);
  return org ? org.raison_sociale : '-';
}

function getStageLabel(key) {
  const s = stages.find(s => s.key === key);
  return s ? s.label : key;
}

function getStageClass(key) {
  const classes = {
    'PROSPECTING': 'stage-prospecting',
    'QUALIFICATION': 'stage-qualification',
    'NEGOTIATION': 'stage-negotiation',
    'WON': 'stage-won',
    'LOST': 'stage-lost',
  };
  return classes[key] || 'stage-default';
}

function getTypeLabel(key) {
  const t = interactionTypes.find(t => t.key === key);
  return t ? t.label : key;
}

function getInteractionTypeClass(type) {
  const classes = {
    'CALL': 'type-call',
    'EMAIL': 'type-email',
    'SMS': 'type-sms',
    'MEETING': 'type-meeting',
    'IN_APP_MESSAGE': 'type-message',
  };
  return classes[type] || 'type-default';
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
}

function formatDateTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatCurrency(value) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(value);
}

function getUserInitials(userId) {
  return `U${userId}`;
}

function fetchOpportunity() {
  // TODO: Replace with real API call
  opportunity.value = {
    id: Number(route.params.id),
    opportunite_name: 'Contrat Alpha - Modernisation IT',
    organisme_id: 1,
    stage: 'NEGOTIATION',
    estimated_value: 250000,
    close_date: '2025-08-15',
    assigned_to_user_id: 1,
  };
}

function fetchInteractions() {
  // TODO: Replace with real API call
  interactions.value = [
    {
      id: 1,
      subject: 'Appel de découverte',
      interaction_type: 'CALL',
      content: 'Discussion initiale avec le décideur technique. Besoins identifiés : modernisation de l\'infrastructure IT, migration cloud, formation équipes.',
      interaction_date: '2025-07-01T10:00',
      created_by_user_id: 1,
      interactable_id: Number(route.params.id),
      interactable_type: 'CrmOpportuniteB2B',
    },
    {
      id: 2,
      subject: 'Envoi de la proposition technique',
      interaction_type: 'EMAIL',
      content: 'Proposition détaillée envoyée comprenant l\'architecture technique, le planning de migration et les formations recommandées.',
      interaction_date: '2025-07-03T15:30',
      created_by_user_id: 2,
      interactable_id: Number(route.params.id),
      interactable_type: 'CrmOpportuniteB2B',
    },
    {
      id: 3,
      subject: 'Réunion de présentation',
      interaction_type: 'MEETING',
      content: 'Présentation de la solution devant le comité technique. Feedback positif, quelques ajustements demandés sur les délais.',
      interaction_date: '2025-07-05T14:00',
      created_by_user_id: 1,
      interactable_id: Number(route.params.id),
      interactable_type: 'CrmOpportuniteB2B',
    },
  ];
}

function saveInteraction() {
  // TODO: Save to backend
  const newInteraction = {
    ...form.value,
    id: Date.now(),
    interactable_id: opportunity.value.id,
    interactable_type: 'CrmOpportuniteB2B',
    created_by_user_id: Number(form.value.created_by_user_id),
  };
  
  interactions.value.unshift(newInteraction);
  showAddModal.value = false;
  
  // Reset form
  form.value = {
    subject: '',
    interaction_type: '',
    content: '',
    interaction_date: '',
    created_by_user_id: '',
  };
}

onMounted(() => {
  fetchOpportunity();
  fetchInteractions();
});
</script>

<style scoped>
.opportunity-details-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
  padding: 2rem 1rem;
}

.page-header {
  max-width: 1200px;
  margin: 0 auto 2rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
}

.breadcrumb-separator {
  width: 1rem;
  height: 1rem;
  opacity: 0.6;
}

.breadcrumb-item.current {
  color: white;
  font-weight: 500;
}

.opp-header-card {
  max-width: 1200px;
  margin: 0 auto 2rem;
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  padding: 2rem;
}

.opp-header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 2rem;
}

.opp-title-section {
  flex: 1;
}

.opp-title-section h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.stage-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.9rem;
}

.stage-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.stage-prospecting {
  background: #fef3c7;
  color: #d97706;
}

.stage-prospecting .stage-dot {
  background: #d97706;
}

.stage-qualification {
  background: #dbeafe;
  color: #2563eb;
}

.stage-qualification .stage-dot {
  background: #2563eb;
}

.stage-negotiation {
  background: #fce7f3;
  color: #be185d;
}

.stage-negotiation .stage-dot {
  background: #be185d;
}

.stage-won {
  background: #dcfce7;
  color: #15803d;
}

.stage-won .stage-dot {
  background: #15803d;
}

.stage-lost {
  background: #fee2e2;
  color: #dc2626;
}

.stage-lost .stage-dot {
  background: #dc2626;
}

.opp-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn.secondary {
  background: #f8fafc;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.action-btn.secondary:hover {
  background: #f1f5f9;
  color: #475569;
}

.action-btn.primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.action-btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.action-icon {
  width: 1rem;
  height: 1rem;
}

.opp-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.info-item {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.info-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.5rem;
}

.info-value {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.value-highlight {
  color: #059669;
  font-size: 1.3rem;
}

.user-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  font-weight: 600;
}

.interactions-section {
  max-width: 1200px;
  margin: 0 auto;
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  padding: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 2rem;
}

.section-title h2 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 0.5rem;
}

.interactions-count {
  font-size: 0.9rem;
  color: #64748b;
}

.add-interaction-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.add-interaction-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-icon {
  width: 1rem;
  height: 1rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #64748b;
}

.empty-icon {
  width: 4rem;
  height: 4rem;
  margin: 0 auto 1rem;
  color: #cbd5e1;
}

.empty-state h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-state p {
  margin-bottom: 2rem;
}

.empty-action-btn {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.empty-action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.interactions-timeline {
  position: relative;
  padding-left: 2rem;
}

.interactions-timeline::before {
  content: '';
  position: absolute;
  left: 0.75rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #e2e8f0, transparent);
}

.interaction-item {
  position: relative;
  margin-bottom: 2rem;
}

.interaction-timeline-dot {
  position: absolute;
  left: -1.75rem;
  top: 1rem;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 0 0 1px #e2e8f0;
}

.type-call .interaction-timeline-dot {
  background: #f59e0b;
}

.type-email .interaction-timeline-dot {
  background: #3b82f6;
}

.type-sms .interaction-timeline-dot {
  background: #ec4899;
}

.type-meeting .interaction-timeline-dot {
  background: #10b981;
}

.type-message .interaction-timeline-dot {
  background: #8b5cf6;
}

.interaction-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  transition: all 0.2s;
}

.interaction-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  border-color: #cbd5e1;
}

.interaction-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.interaction-type {
  font-size: 0.9rem;
  font-weight: 600;
  color: #64748b;
}
.interaction-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 600;
}
.interaction-type-badge .type-icon {
  width: 1rem;
  height: 1rem;
}
.interaction-date {
  font-size: 0.8rem;
  color: #9ca3af;
}
.interaction-subject {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
}
.interaction-content {
  font-size: 0.9rem;
  color: #4b5563;
  margin-bottom: 1rem;
}
.interaction-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.interaction-author {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.author-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  font-weight: 600;
}
.interaction-actions {
  display: flex;
  gap: 0.5rem;
}
.interaction-action-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #4b5563;
  transition: color 0.2s;
}
.interaction-action-btn:hover {
  color: #1a202c;
}
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}
.modal-content {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.modal-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
}
.modal-close {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #4b5563;
  transition: color 0.2s;
}
.modal-close:hover {
  color: #1a202c;
}
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-group label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #4b5563;
}

.form-group textarea {
  font-size: 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  color: #4b5563;
}
.form-group input,
.form-group select {
  font-size: 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  color: #4b5563;
}
.form-group input[type="datetime-local"] {
  padding: 0.75rem;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}
.btn-cancel {
  padding: 0.75rem 1.5rem;
  background: #f8fafc;
  color: #4b5563;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-cancel:hover {
  background: #e2e8f0;
  color: #1a202c;
}
.btn-save {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}
@media (max-width: 768px) {
  .opp-header-content {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .opp-actions {
    width: 100%;
    justify-content: space-between;
  }
  
  .opp-info-grid {
    grid-template-columns: 1fr;
  }
  
  .interaction-item {
    margin-bottom: 1.5rem;
  }
}
.interaction-card {
  padding: 1rem;
}
.interaction-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
.interaction-header {
  margin-bottom: 0.5rem;
}
.interaction-type-badge {
  font-size: 0.8rem;
  padding: 0.25rem 0.5rem;
}
.interaction-subject {
  font-size: 1rem;
  margin-bottom: 0.25rem;
}
.interaction-content {
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}
.interaction-footer {
  font-size: 0.8rem;
  color: #6b7280;
}
.interaction-actions {
  display: flex;
  gap: 0.25rem;
}
.modal-form input,
.modal-form select,
.modal-form textarea {
  font-size: 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  color: #4b5563;
}
.modal-form input[type="datetime-local"] {
  padding: 0.75rem;
}
.modal-form button {
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
}
.modal-form .btn-cancel {
  background: #f8fafc;
  color: #4b5563;
  border: none;
}
.modal-form .btn-cancel:hover {
  background: #e2e8f0;
  color: #1a202c;
}
.modal-form .btn-save {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
}
.modal-form .btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}
.modal-form .form-group {
  margin-bottom: 1rem;
}
.modal-form .form-group label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #4b5563;
}
.modal-form .form-group input,
.modal-form .form-group select,
.modal-form .form-group textarea {
  font-size: 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  color: #4b5563;
}
</style>