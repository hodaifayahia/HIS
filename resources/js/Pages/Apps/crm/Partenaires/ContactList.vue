<template>
  <div class="contact-list-container">
    <div class="header-row">
      <h2>Contacts du Partenaire</h2>
      <button class="add-btn" @click="openAddContact">+ Ajouter un contact</button>
    </div>
    <table class="styled-table">
      <thead>
        <tr>
          <th>Prénom</th>
          <th>Nom</th>
          <th>Poste</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Contact Principal</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="contact in contacts" :key="contact.id">
          <td>{{ contact.first_name }}</td>
          <td>{{ contact.last_name }}</td>
          <td>{{ contact.position }}</td>
          <td>{{ contact.email }}</td>
          <td>{{ contact.phone }}</td>
          <td>
            <span v-if="contact.is_primary_contact" class="primary-badge">Oui</span>
            <span v-else>Non</span>
          </td>
          <td>
            <button class="action-btn edit" @click="editContact(contact)">Éditer</button>
            <button class="action-btn delete" @click="deleteContact(contact.id)">Supprimer</button>
          </td>
        </tr>
        <tr v-if="contacts.length === 0">
          <td colspan="7" class="empty-row">Aucun contact trouvé.</td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Add/Edit Contact -->
    <div v-if="showContactModal" class="modal">
      <div class="modal-content">
        <h3>{{ editingContact ? 'Éditer' : 'Ajouter' }} un contact</h3>
        <form @submit.prevent="saveContact" class="modal-form">
          <div class="form-row">
            <input v-model="contactForm.first_name" placeholder="Prénom" required />
            <input v-model="contactForm.last_name" placeholder="Nom" required />
          </div>
          <div class="form-row">
            <input v-model="contactForm.position" placeholder="Poste" />
            <input v-model="contactForm.email" placeholder="Email" type="email" />
          </div>
          <div class="form-row">
            <input v-model="contactForm.phone" placeholder="Téléphone" />
            <label class="checkbox-label">
              <input type="checkbox" v-model="contactForm.is_primary_contact" /> Contact principal
            </label>
          </div>
          <div class="modal-actions">
            <button type="submit" class="modal-btn save">Enregistrer</button>
            <button type="button" class="modal-btn cancel" @click="closeContactModal">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, defineProps } from 'vue';

const props = defineProps({
  organismeId: { type: Number, required: true },
});

const contacts = ref([]);
const showContactModal = ref(false);
const editingContact = ref(null);
const contactForm = ref({
  first_name: '',
  last_name: '',
  position: '',
  email: '',
  phone: '',
  is_primary_contact: false,
});

function fetchContacts() {
  // TODO: Replace with real API call
  // Example: fetch(`/api/partenaires/${props.organismeId}/contacts`).then(...)
  contacts.value = [];
}

function openAddContact() {
  editingContact.value = null;
  contactForm.value = {
    first_name: '',
    last_name: '',
    position: '',
    email: '',
    phone: '',
    is_primary_contact: false,
  };
  showContactModal.value = true;
}

function editContact(contact) {
  editingContact.value = contact;
  contactForm.value = { ...contact };
  showContactModal.value = true;
}

function closeContactModal() {
  showContactModal.value = false;
}

function saveContact() {
  if (editingContact.value) {
    // TODO: Update contact via API
    Object.assign(editingContact.value, contactForm.value);
  } else {
    // TODO: Add contact via API
    contacts.value.push({ ...contactForm.value, id: Date.now() });
  }
  showContactModal.value = false;
}

function deleteContact(id) {
  // TODO: Delete contact via API
  contacts.value = contacts.value.filter(c => c.id !== id);
}

watch(() => props.organismeId, fetchContacts, { immediate: true });
</script>

<style scoped>
.contact-list-container {
  background: #f9f9fb;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  max-width: 900px;
  margin: 2rem auto;
}
.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
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
.styled-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,0.03);
}
.styled-table th, .styled-table td {
  padding: 0.75rem 1rem;
  text-align: left;
}
.styled-table thead {
  background: #f1f5f9;
}
.styled-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
}
.styled-table tbody tr:last-child {
  border-bottom: none;
}
.primary-badge {
  background: #22c55e;
  color: #fff;
  border-radius: 12px;
  padding: 0.2rem 0.7rem;
  font-size: 0.95em;
  font-weight: 500;
}
.action-btn {
  border: none;
  border-radius: 5px;
  padding: 0.3rem 0.9rem;
  margin-right: 0.4rem;
  font-size: 0.97em;
  cursor: pointer;
  transition: background 0.2s;
}
.action-btn.edit {
  background: #fbbf24;
  color: #fff;
}
.action-btn.edit:hover {
  background: #f59e1b;
}
.action-btn.delete {
  background: #ef4444;
  color: #fff;
}
.action-btn.delete:hover {
  background: #dc2626;
}
.empty-row {
  text-align: center;
  color: #888;
  font-style: italic;
}
.modal {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
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
.form-row {
  display: flex;
  gap: 1rem;
}
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1em;
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
input[type="text"], input[type="email"] {
  border: 1px solid #d1d5db;
  border-radius: 5px;
  padding: 0.45rem 0.8rem;
  font-size: 1em;
  width: 100%;
  background: #f9fafb;
  transition: border 0.2s;
}
input[type="text"]:focus, input[type="email"]:focus {
  border: 1.5px solid #2563eb;
  outline: none;
  background: #fff;
}
</style>
