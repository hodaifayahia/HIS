<template>
  <div class="ticket-list-container">
    <div class="header">
      <div>
        <h1>Gestion des Tickets</h1>
        <p>Feedback, plaintes, suggestions, incidents, et plus.</p>
      </div>
      <button @click="openAddModal" class="btn btn-primary">
        <span class="material-icons">add_circle</span> Nouveau Ticket
      </button>
    </div>

    <div class="ticket-grid">
      <div
        v-for="ticket in filteredTickets"
        :key="ticket.id"
        class="ticket-card"
        @click="openDetails(ticket)"
      >
        <div class="ticket-meta">
          <span class="ticket-id">#{{ ticket.id }}</span>
          <span :class="typeBadgeClass(ticket.type)">{{ ticket.type }}</span>
          <span :class="statusBadgeClass(ticket.status)">{{ ticket.status }}</span>
          <span :class="priorityBadgeClass(ticket.priority)">{{ ticket.priority }}</span>
        </div>
        <div class="ticket-subject">{{ ticket.subject }}</div>
        <div class="ticket-description">{{ ticket.description }}</div>
        <div class="ticket-assignment">
          <span class="material-icons">person</span>
          <span class="assigned-to">
            <span v-if="ticket.assigned_to && ticket.assigned_service">
              {{ ticket.assigned_to }} <span class="text-xs text-gray-400">({{ ticket.assigned_service }})</span>
            </span>
            <span v-else-if="ticket.assigned_to">
              {{ ticket.assigned_to }}
            </span>
            <span v-else-if="ticket.assigned_service">
              {{ ticket.assigned_service }} <span class="text-xs text-gray-400">(Service)</span>
            </span>
            <span v-else>-</span>
          </span>
        </div>
        <div class="ticket-footer">
          <span class="ticket-date">{{ formatDate(ticket.created_at) }}</span>
          <div class="ticket-actions">
            <button @click.stop="editTicket(ticket)" class="btn btn-xs btn-warning">
              <span class="material-icons">edit</span>
            </button>
            <button @click.stop="deleteTicket(ticket)" class="btn btn-xs btn-danger">
              <span class="material-icons">delete</span>
            </button>
          </div>
        </div>
      </div>
      <div v-if="filteredTickets.length === 0" class="no-tickets">Aucun ticket trouvé.</div>
    </div>

    <div v-if="showModal" class="modal-overlay">
      <div class="modal-card">
        <button @click="closeModal" class="modal-close-btn">
          <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">{{ editingTicket ? 'Modifier le Ticket' : 'Nouveau Ticket' }}</h2>
        <form @submit.prevent="saveTicket" class="modal-form">
          <div>
            <label class="form-label">Sujet</label>
            <input v-model="form.subject" required class="input" placeholder="Sujet du ticket" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Type</label>
              <select v-model="form.type" required class="input">
                <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Statut</label>
              <select v-model="form.status" required class="input">
                <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Priorité</label>
              <select v-model="form.priority" required class="input">
                <option v-for="p in priorityOptions" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Assigné à</label>
              <div class="assign-option">
                <input type="checkbox" id="assignService" v-model="form.assignToService" />
                <label for="assignService">Service</label>
              </div>
              <div v-if="form.assignToService">
                <select v-model="form.assigned_service" class="input service-select">
                  <option value="" disabled>Sélectionner un service</option>
                  <option v-for="service in services" :key="service.id" :value="service.name">{{ service.name }}</option>
                </select>
                <div v-if="form.assigned_service" class="service-users-info">
                  <div class="service-users-title">Utilisateurs du service :</div>
                  <ul class="service-users-list">
                    <li v-for="user in usersByService(form.assigned_service)" :key="user.id">{{ user.name }}</li>
                  </ul>
                </div>
              </div>
              <div v-else>
                <input type="text" placeholder="Rechercher un utilisateur..." class="input" v-model="userSearch" />
                <select v-model="form.assigned_to" class="input">
                  <option value="" disabled>Sélectionner un utilisateur</option>
                  <option v-for="user in filteredUsers" :key="user.id" :value="user.name">{{ user.name }}</option>
                </select>
              </div>
            </div>
          </div>
          <div>
            <label class="form-label">Description</label>
            <textarea v-model="form.description" rows="3" class="input" placeholder="Décrivez le problème ou la demande..."></textarea>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn btn-ghost">Annuler</button>
            <button type="submit" class="btn btn-primary">{{ editingTicket ? 'Enregistrer' : 'Créer' }}</button>
          </div>
        </form>
      </div>
    </div>

    <transition name="slide">
      <div v-if="showDetails" class="details-panel">
        <div class="details-header">
          <h2>Ticket #{{ selectedTicket?.id }}</h2>
          <button @click="closeDetails" class="modal-close-btn">
            <span class="material-icons">close</span>
          </button>
        </div>
        <div class="details-content">
          <div class="details-badges">
            <span :class="typeBadgeClass(selectedTicket?.type)">{{ selectedTicket?.type }}</span>
            <span :class="statusBadgeClass(selectedTicket?.status)">{{ selectedTicket?.status }}</span>
            <span :class="priorityBadgeClass(selectedTicket?.priority)">{{ selectedTicket?.priority }}</span>
          </div>
          <div class="details-info">
            <div class="details-subject">{{ selectedTicket?.subject }}</div>
            <div class="details-assignment-info">
              Assigné à :
              <span class="font-semibold">
                <span v-if="selectedTicket?.assigned_to && selectedTicket?.assigned_service">
                  {{ selectedTicket.assigned_to }} <span class="text-xs text-gray-400">({{ selectedTicket.assigned_service }})</span>
                </span>
                <span v-else-if="selectedTicket?.assigned_to">
                  {{ selectedTicket.assigned_to }}
                </span>
                <span v-else-if="selectedTicket?.assigned_service">
                  {{ selectedTicket.assigned_service }} <span class="text-xs text-gray-400">(Service)</span>
                </span>
                <span v-else>-</span>
              </span>
            </div>
            <div class="details-description">{{ selectedTicket?.description }}</div>
          </div>
          <div class="details-date">Créé le {{ formatDate(selectedTicket?.created_at) }}</div>
        </div>
        <div class="details-history">
          <h3 class="history-title">Historique & Actions</h3>
          <ul class="history-list">
            <li v-for="log in selectedTicket?.logs || []" :key="log.id" class="history-item">
              <span class="material-icons">history</span>
              <div>
                <div class="history-action">{{ log.action }}</div>
                <div class="history-date">{{ formatDate(log.date) }} par {{ log.user }}</div>
              </div>
            </li>
            <li v-if="!selectedTicket?.logs || selectedTicket.logs.length === 0" class="no-history">Aucune action enregistrée.</li>
          </ul>
          <form @submit.prevent="addLog" class="add-log-form">
            <input v-model="newLog" placeholder="Ajouter une note d'action..." class="input flex-grow" />
            <button type="submit" class="btn btn-primary">Ajouter</button>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

// UI options for select filters and modals
const statusOptions = ['Ouvert', 'En cours', 'Fermé'];
const priorityOptions = ['Haute', 'Moyenne', 'Basse'];
const typeOptions = ['Incident', 'Suggestion', 'Plainte', 'Feedback'];

// Sample/mock data for tickets
const tickets = ref([
  {
    id: 101,
    subject: 'Problème de connexion',
    type: 'Incident',
    status: 'Ouvert',
    priority: 'Haute',
    assigned_to: 'Dr. Martin',
    description: 'Impossible de se connecter au portail patient.',
    created_at: '2025-07-08T09:30:00',
    logs: [
      { id: 1, action: 'Ticket créé', date: '2025-07-08T09:30:00', user: 'Alice' },
      { id: 2, action: 'Assigné à Dr. Martin', date: '2025-07-08T10:00:00', user: 'Admin' },
    ],
  },
  {
    id: 102,
    subject: 'Suggestion: prise de RDV plus rapide',
    type: 'Suggestion',
    status: 'En cours',
    priority: 'Moyenne',
    assigned_to: 'Equipe IT',
    description: 'Serait-il possible de simplifier la prise de rendez-vous ?',
    created_at: '2025-07-07T14:15:00',
    logs: [
      { id: 1, action: 'Ticket créé', date: '2025-07-07T14:15:00', user: 'Bob' },
      { id: 2, action: 'Réponse envoyée', date: '2025-07-07T15:00:00', user: 'Equipe IT' },
    ],
  },
  {
    id: 103,
    subject: 'Plainte: attente trop longue',
    type: 'Plainte',
    status: 'Fermé',
    priority: 'Haute',
    assigned_to: 'Service Client',
    description: 'Le temps d’attente dépasse 30 minutes.',
    created_at: '2025-07-06T11:00:00',
    logs: [
      { id: 1, action: 'Ticket créé', date: '2025-07-06T11:00:00', user: 'Claire' },
      { id: 2, action: 'Ticket résolu', date: '2025-07-06T12:00:00', user: 'Service Client' },
    ],
  },
]);

const search = ref('');
const statusFilter = ref('');
const priorityFilter = ref('');


const showModal = ref(false);
const editingTicket = ref(null);
const form = ref({
  subject: '',
  type: '',
  status: '',
  priority: '',
  assigned_to: '',
  assignToService: false,
  assigned_service: '',
  description: ''
});
const userSearch = ref('');

// Example services and users
const services = [
  { id: 1, name: 'Service Client' },
  { id: 2, name: 'Equipe IT' },
  { id: 3, name: 'Accueil' },
];
const users = [
  { id: 1, name: 'Dr. Martin', service: 'Service Client' },
  { id: 2, name: 'Alice', service: 'Service Client' },
  { id: 3, name: 'Equipe IT', service: 'Equipe IT' },
  { id: 4, name: 'Bob', service: 'Equipe IT' },
  { id: 5, name: 'Claire', service: 'Accueil' },
];

const filteredUsers = computed(() => {
  if (!userSearch.value) return users;
  return users.filter(u => u.name.toLowerCase().includes(userSearch.value.toLowerCase()));
});

function usersByService(serviceName) {
  return users.filter(u => u.service === serviceName);
}

const showDetails = ref(false);
const selectedTicket = ref(null);
const newLog = ref('');

const filteredTickets = computed(() => {
  return tickets.value.filter(t => {
    const matchesSearch =
      t.subject.toLowerCase().includes(search.value.toLowerCase()) ||
      t.description.toLowerCase().includes(search.value.toLowerCase()) ||
      t.assigned_to.toLowerCase().includes(search.value.toLowerCase());
    const matchesStatus = !statusFilter.value || t.status === statusFilter.value;
    const matchesPriority = !priorityFilter.value || t.priority === priorityFilter.value;
    return matchesSearch && matchesStatus && matchesPriority;
  });
});

function openAddModal() {
  editingTicket.value = null;
  form.value = {
    subject: '',
    type: '',
    status: '',
    priority: '',
    assigned_to: '',
    assignToService: false,
    assigned_service: '',
    description: ''
  };
  userSearch.value = '';
  showModal.value = true;
}

function editTicket(ticket) {
  editingTicket.value = ticket;
  form.value = {
    subject: ticket.subject,
    type: ticket.type,
    status: ticket.status,
    priority: ticket.priority,
    assigned_to: ticket.assigned_to,
    assignToService: !!ticket.assigned_service,
    assigned_service: ticket.assigned_service || '',
    description: ticket.description
  };
  userSearch.value = '';
  showModal.value = true;
}

function saveTicket() {
  if (editingTicket.value) {
    // Update existing
    Object.assign(editingTicket.value, {
      ...form.value,
      assigned_to: form.value.assignToService ? '' : form.value.assigned_to,
      assigned_service: form.value.assignToService ? form.value.assigned_service : '',
    });
  } else {
    // Add new
    const newId = Math.max(...tickets.value.map(t => t.id)) + 1;
    tickets.value.unshift({
      ...form.value,
      id: newId,
      assigned_to: form.value.assignToService ? '' : form.value.assigned_to,
      assigned_service: form.value.assignToService ? form.value.assigned_service : '',
      created_at: new Date().toISOString(),
      logs: [{ id: 1, action: 'Ticket créé', date: new Date().toISOString(), user: 'Admin' }]
    });
  }
  showModal.value = false;
}

function deleteTicket(ticket) {
  if (confirm('Supprimer ce ticket ?')) {
    tickets.value = tickets.value.filter(t => t.id !== ticket.id);
    closeDetails();
  }
}

function closeModal() {
  showModal.value = false;
}

function openDetails(ticket) {
  selectedTicket.value = ticket;
  showDetails.value = true;
  newLog.value = '';
}

function closeDetails() {
  showDetails.value = false;
  selectedTicket.value = null;
}

function addLog() {
  if (!newLog.value.trim()) return;
  const logs = selectedTicket.value.logs || [];
  logs.push({ id: logs.length + 1, action: newLog.value, date: new Date().toISOString(), user: 'Admin' });
  selectedTicket.value.logs = logs;
  newLog.value = '';
}

function formatDate(date) {
  if (!date) return '';
  return new Date(date).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
}

function statusBadgeClass(status) {
  return [
    'badge',
    status === 'Ouvert' ? 'badge-success' : '',
    status === 'En cours' ? 'badge-warning' : '',
    status === 'Fermé' ? 'badge-secondary' : '',
  ].join(' ');
}

function priorityBadgeClass(priority) {
  return [
    'badge',
    priority === 'Haute' ? 'badge-danger' : '',
    priority === 'Moyenne' ? 'badge-info' : '',
    priority === 'Basse' ? 'badge-primary' : '',
  ].join(' ');
}

function typeBadgeClass(type) {
  return [
    'badge',
    type === 'Incident' ? 'badge-incident' : '',
    type === 'Suggestion' ? 'badge-suggestion' : '',
    type === 'Plainte' ? 'badge-complaint' : '',
    type === 'Feedback' ? 'badge-feedback' : '',
  ].join(' ');
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');

/* Base Styles */
.ticket-list-container {
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  background-color: #f4f7f6; /* Light background */
  padding: 2rem 1rem;
}

/* Header */
.header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 2rem;
}

@media (min-width: 768px) {
  .header {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

.header h1 {
  font-size: 2.25rem; /* text-3xl */
  font-weight: 800; /* font-extrabold */
  color: #1a202c; /* blue-900 equivalent for darker text */
  margin-bottom: 0.25rem;
  letter-spacing: -0.025em; /* tracking-tight */
}

.header p {
  color: #64748b; /* gray-500 */
}

/* Buttons */
.btn {
  border-radius: 0.5rem; /* rounded-lg */
  font-weight: 600; /* font-semibold */
  padding: 0.75rem 1.5rem; /* px-6 py-2 */
  font-size: 1rem;
  transition: all 0.2s ease-in-out;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background-color: #3b82f6; /* blue-600 */
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.btn-primary:hover {
  background-color: #2563eb; /* blue-700 */
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.btn-ghost {
  background-color: #e2e8f0; /* gray-200 */
  color: #1e293b; /* gray-800 */
}

.btn-ghost:hover {
  background-color: #cbd5e1; /* gray-300 */
}

.btn-xs {
  padding: 0.4rem 0.75rem;
  font-size: 0.75rem;
  border-radius: 0.375rem;
}

.btn-warning {
  background-color: #f97316; /* orange-500 */
  color: #ffffff;
}

.btn-warning:hover {
  background-color: #ea580c; /* orange-600 */
}

.btn-danger {
  background-color: #ef4444; /* red-500 */
  color: #ffffff;
}

.btn-danger:hover {
  background-color: #dc2626; /* red-600 */
}

/* Ticket Grid */
.ticket-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}

@media (min-width: 768px) {
  .ticket-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1200px) {
  .ticket-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.ticket-card {
  background-color: #ffffff;
  border-radius: 1.25rem; /* rounded-2xl */
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  border: 1px solid #e0e7ff; /* blue-100 */
  transition: all 0.2s ease-in-out;
  cursor: pointer;
  position: relative;
}

.ticket-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
  transform: translateY(-3px);
}

.ticket-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.ticket-id {
  font-family: 'JetBrains Mono', 'Fira Mono', 'Menlo', monospace;
  color: #1d4ed8; /* blue-700 */
  font-size: 1.125rem; /* text-lg */
  font-weight: 600;
}

.ticket-subject {
  font-weight: 700; /* font-bold */
  font-size: 1.125rem; /* text-lg */
  color: #1a202c; /* blue-900 */
  margin-bottom: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ticket-description {
  color: #64748b; /* gray-500 */
  font-size: 0.875rem; /* text-sm */
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ticket-assignment {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.ticket-assignment .material-icons {
  color: #60a5fa; /* blue-400 */
  font-size: 1.2em;
}

.assigned-to {
  color: #1e40af; /* blue-800 */
  font-weight: 600; /* font-semibold */
}

.ticket-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
}

.ticket-date {
  font-size: 0.75rem; /* text-xs */
  color: #94a3b8; /* gray-400 */
}

.ticket-actions {
  display: flex;
  gap: 0.5rem;
  opacity: 0;
  transition: opacity 0.2s ease-in-out;
}

.ticket-card:hover .ticket-actions {
  opacity: 1;
}

.no-tickets {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem 0;
  color: #94a3b8; /* gray-400 */
  font-size: 1.125rem; /* text-lg */
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem; /* px-3 py-1 */
  border-radius: 9999px; /* rounded-full */
  font-size: 0.75rem; /* text-xs */
  font-weight: 700; /* font-bold */
}

.badge-success {
  background-color: #d1fae5; /* green-100 */
  color: #047857; /* green-700 */
}

.badge-warning {
  background-color: #fef3c7; /* yellow-100 */
  color: #b45309; /* yellow-700 */
}

.badge-secondary {
  background-color: #e5e7eb; /* gray-200 */
  color: #6b7280; /* gray-500 */
  text-decoration: line-through;
}

.badge-danger {
  background-color: #fee2e2; /* red-100 */
  color: #b91c1c; /* red-700 */
}

.badge-info {
  background-color: #dbeafe; /* blue-100 */
  color: #1e40af; /* blue-700 */
}

.badge-primary {
  background-color: #bfdbfe; /* blue-200 */
  color: #1e3a8a; /* blue-900 */
}

.badge-incident {
  background-color: #ede9fe; /* purple-100 */
  color: #6d28d9; /* purple-700 */
}

.badge-suggestion {
  background-color: #dbeafe; /* blue-100 */
  color: #2563eb; /* blue-700 */
}

.badge-complaint {
  background-color: #fce7f3; /* pink-100 */
  color: #be185d; /* pink-700 */
}

.badge-feedback {
  background-color: #ccfbf1; /* teal-100 */
  color: #0f766e; /* teal-700 */
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.4); /* bg-black bg-opacity-40 */
}

.modal-card {
  background-color: #ffffff;
  border-radius: 1.5rem; /* 2rem */
  box-shadow: 0 8px 40px rgba(56, 189, 248, 0.13), 0 2px 12px rgba(16, 30, 54, 0.08);
  padding: 2.5rem;
  min-width: 350px;
  max-width: 480px;
  width: 100%;
  position: relative;
  animation: fadeIn 0.3s ease-out;
}

.modal-close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  color: #9ca3af; /* gray-400 */
  cursor: pointer;
  background: none;
  border: none;
  font-size: 1.5rem;
  transition: color 0.2s;
}

.modal-close-btn:hover {
  color: #ef4444; /* red-500 */
}

.modal-title {
  font-size: 1.5rem; /* text-2xl */
  font-weight: 800; /* font-extrabold */
  color: #1a202c; /* blue-900 */
  margin-bottom: 1rem;
  text-align: center;
  letter-spacing: -0.01em;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1rem; /* space-y-4 */
}

.form-label {
  display: block;
  font-size: 0.875rem; /* text-sm */
  font-weight: 600; /* font-semibold */
  margin-bottom: 0.25rem;
  color: #1e293b; /* gray-800 */
}

.input {
  border: 1.5px solid #d1d5db; /* gray-300 */
  border-radius: 0.5rem; /* rounded-lg */
  padding: 0.6rem 1rem;
  font-size: 1rem;
  background-color: #f9fafb; /* gray-50 */
  width: 100%;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input:focus {
  border-color: #3b82f6; /* blue-500 */
  outline: none;
  background-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

textarea.input {
  min-height: 80px;
  resize: vertical;
}

.form-row {
  display: flex;
  gap: 1rem; /* gap-4 */
}

.form-group {
  flex: 1;
}

.assign-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.assign-option input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  border-radius: 0.25rem;
  border: 1px solid #d1d5db;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  cursor: pointer;
  position: relative;
  transition: background-color 0.2s, border-color 0.2s;
}

.assign-option input[type="checkbox"]:checked {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.assign-option input[type="checkbox"]:checked::before {
  content: '\2713'; /* Checkmark */
  display: block;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 0.8rem;
  line-height: 1;
}


.service-select {
  margin-bottom: 0.5rem;
}

.service-users-info {
  background-color: #eff6ff; /* blue-50 */
  border-radius: 0.5rem; /* rounded-lg */
  padding: 0.75rem;
  margin-top: 0.25rem;
}

.service-users-title {
  font-size: 0.75rem; /* text-xs */
  color: #1e3a8a; /* blue-900 */
  font-weight: 600; /* font-semibold */
  margin-bottom: 0.25rem;
}

.service-users-list {
  font-size: 0.75rem; /* text-xs */
  color: #1d4ed8; /* blue-700 */
  padding-left: 1rem; /* pl-4 */
  list-style: disc;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

/* Details Panel (Side Panel) */
.details-panel {
  position: fixed;
  top: 0;
  right: 0;
  height: 100%;
  width: 100%;
  background-color: #ffffff;
  box-shadow: 0 0 30px rgba(0, 0, 0, 0.15);
  z-index: 50;
  overflow-y: auto;
  animation: slideIn 0.3s ease-out;
}

@media (min-width: 768px) {
  .details-panel {
    width: 480px; /* md:w-[480px] */
  }
}

.details-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0; /* border-b */
}

.details-header h2 {
  font-size: 1.25rem; /* text-xl */
  font-weight: 700; /* font-bold */
  color: #1a202c; /* blue-900 */
}

.details-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem; /* space-y-4 */
}

.details-badges {
  display: flex;
  gap: 1rem; /* gap-4 */
}

.details-info {
  margin-bottom: 0.5rem;
}

.details-subject {
  font-weight: 600; /* font-semibold */
  font-size: 1.125rem; /* text-lg */
  margin-bottom: 0.25rem;
}

.details-assignment-info {
  color: #64748b; /* gray-500 */
  margin-bottom: 0.5rem;
}

.details-description {
  color: #334155; /* gray-700 */
  white-space: pre-line;
}

.details-date {
  font-size: 0.75rem; /* text-xs */
  color: #94a3b8; /* gray-400 */
}

.details-history {
  border-top: 1px solid #e2e8f0; /* border-t */
  padding: 1.5rem;
}

.history-title {
  font-weight: 700; /* font-bold */
  margin-bottom: 0.75rem;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem; /* space-y-3 */
}

.history-item {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.history-item .material-icons {
  color: #60a5fa; /* blue-400 */
  font-size: 1.2em;
}

.history-action {
  font-size: 0.875rem; /* text-sm */
  font-weight: 600; /* font-semibold */
}

.history-date {
  font-size: 0.75rem; /* text-xs */
  color: #94a3b8; /* gray-400 */
}

.no-history {
  color: #94a3b8; /* gray-400 */
  font-size: 0.875rem; /* text-sm */
}

.add-log-form {
  margin-top: 1rem;
  display: flex;
  gap: 0.5rem;
}

/* Transitions */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
  }
  to {
    transform: translateX(0);
  }
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease-out;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>