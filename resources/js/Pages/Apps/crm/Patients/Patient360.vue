<template>
  <div class="patient360-container">
    <div class="patient-header-card">
      <div class="header-main">
        <img :src="patient.avatar || defaultAvatar" class="patient-avatar" alt="avatar" />
        <div>
          <h1>{{ patient.full_name }}</h1>
          <div class="patient-meta">
            <span><strong>Âge:</strong> {{ patient.age }}</span>
            <span><strong>Sexe:</strong> {{ patient.gender }}</span>
            <span><strong>ID:</strong> {{ patient.id }}</span>
          </div>
        </div>
      </div>
      <div class="patient-info-grid">
        <div><strong>Email:</strong> {{ patient.email }}</div>
        <div><strong>Téléphone:</strong> {{ patient.phone }}</div>
        <div><strong>Adresse:</strong> {{ patient.address }}</div>
        <div><strong>Ville:</strong> {{ patient.city }}</div>
        <div><strong>Code postal:</strong> {{ patient.postal_code }}</div>
        <div><strong>Assurance:</strong> {{ patient.insurance }}</div>
        <div><strong>Dernière visite:</strong> {{ patient.last_visit }}</div>
      </div>
      <div v-if="patient.notes" class="patient-notes">
        <strong>Notes:</strong>
        <div>{{ patient.notes }}</div>
      </div>
    </div>

    <div class="tabs">
      <button :class="{active: tab==='interactions'}" @click="tab='interactions'">Interactions</button>
      <button :class="{active: tab==='preferences'}" @click="tab='preferences'">Préférences</button>
      <button :class="{active: tab==='segmentation'}" @click="tab='segmentation'">Segmentation</button>
    </div>

    <div v-if="tab==='interactions'">
      <div class="interactions-section">
        <div class="interactions-header">
          <h2>Historique des interactions</h2>
          <button class="add-btn" @click="showAddModal = true">+ Ajouter une interaction</button>
        </div>
        <div v-if="interactions.length === 0" class="empty-row">Aucune interaction pour ce patient.</div>
        <div v-else class="interactions-list">
          <div v-for="interaction in interactions" :key="interaction.id" class="interaction-card">
            <div class="interaction-header">
              <span class="interaction-type" :class="interaction.interaction_type.toLowerCase()">{{ getTypeLabel(interaction.interaction_type) }}</span>
              <span class="interaction-date">{{ formatDate(interaction.interaction_date) }}</span>
            </div>
            <div class="interaction-subject">{{ interaction.subject }}</div>
            <div class="interaction-content">{{ interaction.content }}</div>
            <div class="interaction-meta">Par utilisateur #{{ interaction.created_by_user_id }}</div>
          </div>
        </div>
      </div>

      <!-- Modal Add Interaction -->
      <div v-if="showAddModal" class="modal">
        <div class="modal-content">
          <h3>Nouvelle interaction</h3>
          <form @submit.prevent="saveInteraction" class="modal-form">
            <input v-model="form.subject" placeholder="Sujet *" required />
            <select v-model="form.interaction_type" required>
              <option value="" disabled>Type d'interaction</option>
              <option v-for="type in interactionTypes" :key="type.key" :value="type.key">{{ type.label }}</option>
            </select>
            <textarea v-model="form.content" placeholder="Contenu"></textarea>
            <input v-model="form.interaction_date" type="datetime-local" required />
            <input v-model="form.created_by_user_id" type="number" placeholder="ID de l'utilisateur" required />
            <div class="modal-actions">
              <button type="submit" class="modal-btn save">Enregistrer</button>
              <button type="button" class="modal-btn cancel" @click="showAddModal = false">Annuler</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-else-if="tab==='preferences'">
      <div class="preferences-section">
        <h2>Préférences du patient</h2>
        <div v-if="!preferences || Object.keys(preferences).length === 0" class="empty-row">Aucune préférence enregistrée.</div>
        <div v-else class="preferences-list">
          <div v-for="(value, key) in preferences" :key="key" class="preference-item">
            <strong>{{ key }}:</strong> {{ value }}
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="tab==='segmentation'">
      <div class="segmentation-section">
        <h2>Segmentation</h2>
        <div v-if="!segmentation || segmentation.length === 0" class="empty-row">Aucune segmentation disponible.</div>
        <div v-else class="segmentation-list">
          <div v-for="segment in segmentation" :key="segment.id" class="segment-item">
            <strong>{{ segment.label }}</strong> <span class="segment-desc">{{ segment.description }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const defaultAvatar = 'https://ui-avatars.com/api/?name=Patient&background=2563eb&color=fff&size=128';
const tab = ref('interactions');
const showAddModal = ref(false);

const patient = ref({
  id: 1,
  full_name: 'Amine Bensalem',
  age: 32,
  gender: 'Homme',
  avatar: '',
  email: 'amine.bensalem@email.com',
  phone: '0550123456',
  address: '123 Rue Principale',
  city: 'Alger',
  postal_code: '16000',
  insurance: 'CNAS',
  last_visit: '2025-06-20',
  notes: 'Patient suivi pour hypertension. Allergique à la pénicilline.'
});

const interactions = ref([
  {
    id: 1,
    subject: 'Consultation initiale',
    interaction_type: 'MEETING',
    content: 'Première consultation avec le patient.',
    interaction_date: '2025-06-01T10:00',
    created_by_user_id: 2,
    interactable_id: 1,
    interactable_type: 'Patient',
  },
  {
    id: 2,
    subject: 'Appel de suivi',
    interaction_type: 'CALL',
    content: 'Appel pour vérifier l’évolution.',
    interaction_date: '2025-06-10T15:30',
    created_by_user_id: 3,
    interactable_id: 1,
    interactable_type: 'Patient',
  },
]);

const preferences = ref({
  Langue: 'Français',
  Communication: 'Email',
  "Rappel RDV": 'Oui',
});

const segmentation = ref([
  { id: 1, label: 'Hypertension', description: 'Patient suivi pour hypertension.' },
  { id: 2, label: 'Allergie', description: 'Allergique à la pénicilline.' },
]);

const interactionTypes = [
  { key: 'CALL', label: 'Appel' },
  { key: 'EMAIL', label: 'Email' },
  { key: 'SMS', label: 'SMS' },
  { key: 'MEETING', label: 'Réunion' },
  { key: 'IN_APP_MESSAGE', label: 'Message in-app' },
];

const form = ref({
  subject: '',
  interaction_type: '',
  content: '',
  interaction_date: '',
  created_by_user_id: '',
});

function getTypeLabel(key) {
  const t = interactionTypes.find(t => t.key === key);
  return t ? t.label : key;
}
function formatDate(dt) {
  if (!dt) return '-';
  const d = new Date(dt);
  return d.toLocaleString();
}
function saveInteraction() {
  interactions.value.unshift({
    ...form.value,
    id: Date.now(),
    interactable_id: patient.value.id,
    interactable_type: 'Patient',
  });
  showAddModal.value = false;
}
</script>

<style scoped>
.patient360-container {
  max-width: 950px;
  margin: 2.5rem auto;
  padding: 1.5rem;
}
.patient-header-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  padding: 2rem 2.5rem 1.5rem 2.5rem;
  margin-bottom: 2.2rem;
}
.header-main {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.2rem;
}
.patient-avatar {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #2563eb;
}
.patient-header-card h1 {
  margin-bottom: 0.5rem;
  font-size: 2.1rem;
  color: #2563eb;
}
.patient-meta {
  display: flex;
  gap: 1.5rem;
  color: #555;
  font-size: 1.08em;
}
.patient-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.7rem 1.5rem;
  font-size: 1.05em;
}
.patient-notes {
  margin-top: 1.2rem;
  background: #f1f5f9;
  border-radius: 7px;
  padding: 0.7rem 1rem;
  color: #444;
}
.tabs {
  display: flex;
  gap: 1.2rem;
  margin-bottom: 1.5rem;
}
.tabs button {
  background: none;
  border: none;
  font-size: 1.1em;
  padding: 0.5rem 1.5rem;
  border-bottom: 3px solid transparent;
  color: #2563eb;
  cursor: pointer;
  transition: border 0.2s, color 0.2s;
}
.tabs button.active {
  border-bottom: 3px solid #2563eb;
  color: #1d4ed8;
  font-weight: 600;
}
.interactions-section {
  background: #f9f9fb;
  border-radius: 12px;
  padding: 2rem 2.5rem 1.5rem 2.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.interactions-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.2rem;
}
.add-btn {
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1.2rem;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s;
}
.add-btn:hover {
  background: #1d4ed8;
}
.interactions-list {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}
.interaction-card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.07);
  padding: 1rem 1.2rem;
  border-left: 5px solid #2563eb;
}
.interaction-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.3rem;
}
.interaction-type {
  font-weight: 600;
  font-size: 1em;
  padding: 0.2rem 0.7rem;
  border-radius: 12px;
  background: #e0e7ff;
  color: #2563eb;
  text-transform: capitalize;
}
.interaction-type.call { background: #fef9c3; color: #b45309; }
.interaction-type.email { background: #dbeafe; color: #2563eb; }
.interaction-type.sms { background: #fce7f3; color: #be185d; }
.interaction-type.meeting { background: #dcfce7; color: #15803d; }
.interaction-type.in_app_message { background: #f3e8ff; color: #7c3aed; }
.interaction-date {
  color: #888;
  font-size: 0.97em;
}
.interaction-subject {
  font-weight: 600;
  margin-bottom: 0.2rem;
}
.interaction-content {
  color: #444;
  margin-bottom: 0.2rem;
}
.interaction-meta {
  color: #888;
  font-size: 0.95em;
}
.empty-row {
  text-align: center;
  color: #888;
  font-style: italic;
  margin: 1.5rem 0;
}
.preferences-section, .segmentation-section {
  background: #f9f9fb;
  border-radius: 12px;
  padding: 2rem 2.5rem 1.5rem 2.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.preferences-list, .segmentation-list {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}
.preference-item, .segment-item {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.07);
  padding: 1rem 1.2rem;
}
.segment-desc {
  color: #555;
  margin-left: 0.7rem;
}
.modal {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
}
.modal-content {
  background: #fff;
  padding: 2.2rem 2.5rem 1.5rem 2.5rem;
  border-radius: 12px;
  min-width: 350px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.13);
  position: relative;
}
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.7rem;
  margin-top: 0.5rem;
}
.modal-btn {
  border: none;
  border-radius: 5px;
  padding: 0.4rem 1.2rem;
  font-size: 1em;
  cursor: pointer;
  transition: background 0.2s;
}
.modal-btn.save {
  background: #2563eb;
  color: #fff;
}
.modal-btn.save:hover {
  background: #1d4ed8;
}
.modal-btn.cancel {
  background: #e5e7eb;
  color: #222;
}
.modal-btn.cancel:hover {
  background: #cbd5e1;
}
input[type="text"], input[type="number"], input[type="datetime-local"], select, textarea {
  border: 1px solid #d1d5db;
  border-radius: 5px;
  padding: 0.45rem 0.8rem;
  font-size: 1em;
  width: 100%;
  background: #f9fafb;
  transition: border 0.2s;
  resize: none;
}
input[type="text"]:focus, input[type="number"]:focus, input[type="datetime-local"]:focus, select:focus, textarea:focus {
  border: 1.5px solid #2563eb;
  outline: none;
  background: #fff;
}
</style>
