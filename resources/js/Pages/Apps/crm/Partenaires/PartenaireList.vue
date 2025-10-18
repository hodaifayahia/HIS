<template>
  <div class="company-list-container">
    <div class="header-row">
      <div>
        <h1>Comptes & Contacts Partenaires</h1>
        <p class="subtitle">Gestion des partenaires B2B et de leurs contacts.</p>
      </div>
      <button class="add-btn" @click="openAddCompany">+ Ajouter un compte partenaire</button>
    </div>
    <table class="styled-table">
      <thead>
        <tr>
          <th>Nom de l'entreprise</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="company in companies" :key="company.id">
          <td>{{ company.raison_sociale }}</td>
          <td>
            <router-link :to="{ name: 'organisme-details', params: { id: company.id } }" class="action-btn view">Voir</router-link>
            <button class="action-btn edit" @click="editCompany(company)">Éditer</button>
            <button class="action-btn delete" @click="deleteCompany(company.id)">Supprimer</button>
          </td>
        </tr>
        <tr v-if="companies.length === 0">
          <td colspan="2" class="empty-row">Aucun compte partenaire trouvé.</td>
        </tr>
      </tbody>
    </table>

    <!-- Details modal removed: now handled by dedicated page -->

    <!-- Modal Add/Edit Company -->
    <div v-if="showCompanyModal" class="modal">
      <div class="modal-content">
        <h3>{{ editingCompany ? 'Éditer' : 'Ajouter' }} un compte partenaire</h3>
        <form @submit.prevent="saveCompany" class="modal-form organisme-form">
          <div class="form-row">
            <input v-model="companyForm.raison_sociale" placeholder="Raison sociale *" required />
            <input v-model="companyForm.forme_juridique_sociale" placeholder="Forme juridique *" required />
          </div>
          <div class="form-row">
            <input v-model="companyForm.adresse" placeholder="Adresse" />
            <input v-model="companyForm.ville" placeholder="Ville" />
            <input v-model="companyForm.code_postal" placeholder="Code postal" />
            <input v-model="companyForm.pays" placeholder="Pays" />
          </div>
          <div class="form-row">
            <input v-model="companyForm.latitude" placeholder="Latitude" type="number" step="any" />
            <input v-model="companyForm.longitude" placeholder="Longitude" type="number" step="any" />
          </div>
          <div class="form-row">
            <input v-model="companyForm.registre_de_commerce" placeholder="Registre de commerce" />
            <input v-model="companyForm.article" placeholder="Article" />
            <input v-model="companyForm.nif" placeholder="NIF" />
            <input v-model="companyForm.nis" placeholder="NIS" />
          </div>
          <div class="form-row">
            <input v-model="companyForm.telephone_fixe" placeholder="Téléphone fixe" />
            <input v-model="companyForm.fax" placeholder="Fax" />
            <input v-model="companyForm.mobile" placeholder="Mobile" />
            <input v-model="companyForm.email" placeholder="Email" type="email" />
          </div>
          <div class="form-row">
            <textarea v-model="companyForm.autres_informations" placeholder="Autres informations" rows="2"></textarea>
          </div>
          <div class="modal-actions">
            <button type="submit" class="modal-btn save">Enregistrer</button>
            <button type="button" class="modal-btn cancel" @click="closeCompanyModal">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import ContactList from './ContactList.vue';

const companies = ref([]);
const selectedCompany = ref(null);
const showCompanyModal = ref(false);
const editingCompany = ref(null);
const companyForm = ref({
  raison_sociale: '',
  forme_juridique_sociale: '',
  adresse: '',
  ville: '',
  code_postal: '',
  pays: '',
  latitude: '',
  longitude: '',
  registre_de_commerce: '',
  article: '',
  nif: '',
  nis: '',
  telephone_fixe: '',
  fax: '',
  mobile: '',
  email: '',
  autres_informations: '',
});

function fetchCompanies() {
  // TODO: Replace with real API call
  companies.value = [
    {
      id: 1,
      raison_sociale: "Entreprise Alpha",
      forme_juridique_sociale: "SARL",
      adresse: "123 Rue Principale",
      ville: "Alger",
      code_postal: "16000",
      pays: "Algérie",
      latitude: "36.7538",
      longitude: "3.0588",
      registre_de_commerce: "RC123456",
      article: "Art. 12",
      nif: "NIF123456",
      nis: "NIS654321",
      telephone_fixe: "021123456",
      fax: "021654321",
      mobile: "0550123456",
      email: "contact@alpha.com",
      autres_informations: "Client premium depuis 2022."
    },
    {
      id: 2,
      raison_sociale: "Entreprise Beta",
      forme_juridique_sociale: "SPA",
      adresse: "456 Avenue Liberté",
      ville: "Oran",
      code_postal: "31000",
      pays: "Algérie",
      latitude: "35.6971",
      longitude: "-0.6308",
      registre_de_commerce: "RC654321",
      article: "Art. 34",
      nif: "NIF654321",
      nis: "NIS123456",
      telephone_fixe: "041987654",
      fax: "041123456",
      mobile: "0550987654",
      email: "contact@beta.com",
      autres_informations: "Partenaire depuis 2023."
    }
  ];
}

function openAddCompany() {
  editingCompany.value = null;
  companyForm.value = {
    raison_sociale: '',
    forme_juridique_sociale: '',
    adresse: '',
    ville: '',
    code_postal: '',
    pays: '',
    latitude: '',
    longitude: '',
    registre_de_commerce: '',
    article: '',
    nif: '',
    nis: '',
    telephone_fixe: '',
    fax: '',
    mobile: '',
    email: '',
    autres_informations: '',
  };
  showCompanyModal.value = true;
}

function editCompany(company) {
  editingCompany.value = company;
  companyForm.value = { ...company };
  showCompanyModal.value = true;
}

function closeCompanyModal() {
  showCompanyModal.value = false;
}

function saveCompany() {
  if (editingCompany.value) {
    // TODO: Update company via API
    Object.assign(editingCompany.value, companyForm.value);
  } else {
    // TODO: Add company via API
    companies.value.push({ ...companyForm.value, id: Date.now() });
  }
  showCompanyModal.value = false;
}

function deleteCompany(id) {
  // TODO: Delete company via API
  companies.value = companies.value.filter(c => c.id !== id);
  if (selectedCompany.value && selectedCompany.value.id === id) {
    selectedCompany.value = null;
  }
}

function selectCompany(company) {
  selectedCompany.value = company;
}

fetchCompanies();
</script>

<style scoped>
.company-list-container {
  background: #f9f9fb;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  max-width: 800px;
  margin: 2rem auto;
}
.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.subtitle {
  color: #64748b;
  font-size: 1.1em;
  margin-top: 0.3rem;
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
.action-btn {
  border: none;
  border-radius: 5px;
  padding: 0.3rem 0.9rem;
  margin-right: 0.4rem;
  font-size: 0.97em;
  cursor: pointer;
  transition: background 0.2s;
}
.action-btn.view {
  background: #2563eb;
  color: #fff;
}
.action-btn.view:hover {
  background: #1d4ed8;
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
.company-details-modal {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.company-details-content {
  background: #fff;
  padding: 2.2rem 2.5rem 1.5rem 2.5rem;
  border-radius: 12px;
  min-width: 400px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.13);
  position: relative;
}
.details-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.2rem;
}
.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  color: #888;
  cursor: pointer;
  line-height: 1;
  padding: 0 0.5rem;
  transition: color 0.2s;
}
.close-btn:hover {
  color: #ef4444;
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
input[type="text"], input[type="email"], input[type="number"], textarea {
  border: 1px solid #d1d5db;
  border-radius: 5px;
  padding: 0.45rem 0.8rem;
  font-size: 1em;
  width: 100%;
  background: #f9fafb;
  transition: border 0.2s;
  resize: none;
}
input[type="text"]:focus, input[type="email"]:focus, input[type="number"]:focus, textarea:focus {
  border: 1.5px solid #2563eb;
  outline: none;
  background: #fff;
}
.organisme-form .form-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}
.organisme-form .form-row > * {
  flex: 1 1 180px;
  min-width: 0;
}
.organisme-form textarea {
  min-height: 48px;
  max-height: 120px;
}
</style>
