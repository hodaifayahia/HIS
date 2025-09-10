<template>
  <div class="patients-list-container">
    <div class="patients-header">
      <h1>Patients</h1>
      <input v-model="search" class="search-input" placeholder="Rechercher un patient..." />
      <button class="add-btn" @click="showAddModal = true">+ Nouveau patient</button>
    </div>
    <div class="patients-table-wrapper">
      <table class="patients-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Âge</th>
            <th>Sexe</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Dernière visite</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="patient in filteredPatients" :key="patient.id">
            <td>
              <img :src="patient.avatar || defaultAvatar" class="table-avatar" alt="avatar" />
              {{ patient.full_name }}
            </td>
            <td>{{ patient.age }}</td>
            <td>{{ patient.gender }}</td>
            <td>{{ patient.email }}</td>
            <td>{{ patient.phone }}</td>
            <td>{{ patient.last_visit }}</td>
            <td>
              <router-link :to="{ name: 'patient360', params: { id: patient.id } }" class="action-btn view">Voir</router-link>
            </td>
          </tr>
          <tr v-if="filteredPatients.length === 0">
            <td colspan="7" class="empty-row">Aucun patient trouvé.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Add Patient -->
    <div v-if="showAddModal" class="modal">
      <div class="modal-content">
        <h3>Nouveau patient</h3>
        <form @submit.prevent="savePatient" class="modal-form">
          <input v-model="form.full_name" placeholder="Nom complet *" required />
          <input v-model="form.age" type="number" placeholder="Âge *" required />
          <select v-model="form.gender" required>
            <option value="" disabled>Sexe</option>
            <option value="Homme">Homme</option>
            <option value="Femme">Femme</option>
          </select>
          <input v-model="form.email" placeholder="Email" type="email" />
          <input v-model="form.phone" placeholder="Téléphone" />
          <input v-model="form.address" placeholder="Adresse" />
          <input v-model="form.city" placeholder="Ville" />
          <input v-model="form.postal_code" placeholder="Code postal" />
          <input v-model="form.insurance" placeholder="Assurance" />
          <input v-model="form.last_visit" type="date" placeholder="Dernière visite" />
          <textarea v-model="form.notes" placeholder="Notes"></textarea>
          <div class="modal-actions">
            <button type="submit" class="modal-btn save">Enregistrer</button>
            <button type="button" class="modal-btn cancel" @click="showAddModal = false">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const defaultAvatar = 'https://ui-avatars.com/api/?name=Patient&background=2563eb&color=fff&size=128';
const search = ref('');
const showAddModal = ref(false);

const patients = ref([
  {
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
  },
  {
    id: 2,
    full_name: 'Sarah Benali',
    age: 28,
    gender: 'Femme',
    avatar: '',
    email: 'sarah.benali@email.com',
    phone: '0550987654',
    address: '456 Avenue Liberté',
    city: 'Oran',
    postal_code: '31000',
    insurance: 'CASH',
    last_visit: '2025-07-01',
    notes: ''
  }
]);

const form = ref({
  full_name: '',
  age: '',
  gender: '',
  avatar: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  postal_code: '',
  insurance: '',
  last_visit: '',
  notes: ''
});

const filteredPatients = computed(() => {
  if (!search.value) return patients.value;
  return patients.value.filter(p =>
    p.full_name.toLowerCase().includes(search.value.toLowerCase()) ||
    (p.email && p.email.toLowerCase().includes(search.value.toLowerCase())) ||
    (p.phone && p.phone.includes(search.value))
  );
});

function savePatient() {
  patients.value.push({ ...form.value, id: Date.now() });
  showAddModal.value = false;
  form.value = {
    full_name: '',
    age: '',
    gender: '',
    avatar: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    postal_code: '',
    insurance: '',
    last_visit: '',
    notes: ''
  };
}
</script>

<style scoped>
.patients-list-container {
  max-width: 1100px;
  margin: 2.5rem auto;
  padding: 1.5rem;
}
.patients-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.patients-header h1 {
  flex: 1;
  font-size: 2rem;
  color: #2563eb;
}
.search-input {
  flex: 2;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-size: 1.1em;
  background: #f9fafb;
  transition: border 0.2s;
}
.search-input:focus {
  border: 1.5px solid #2563eb;
  outline: none;
  background: #fff;
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
.patients-table-wrapper {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  padding: 1.5rem;
}
.patients-table {
  width: 100%;
  border-collapse: collapse;
}
.patients-table th, .patients-table td {
  padding: 0.75rem 1rem;
  text-align: left;
}
.patients-table thead {
  background: #f1f5f9;
}
.patients-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
}
.patients-table tbody tr:last-child {
  border-bottom: none;
}
.table-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 0.7rem;
  vertical-align: middle;
  border: 2px solid #2563eb;
}
.action-btn.view {
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 5px;
  padding: 0.3rem 0.9rem;
  font-size: 0.97em;
  cursor: pointer;
  transition: background 0.2s;
  text-decoration: none;
}
.action-btn.view:hover {
  background: #1d4ed8;
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
input[type="text"], input[type="email"], input[type="number"], input[type="date"], textarea, select {
  border: 1px solid #d1d5db;
  border-radius: 5px;
  padding: 0.45rem 0.8rem;
  font-size: 1em;
  width: 100%;
  background: #f9fafb;
  transition: border 0.2s;
  resize: none;
}
input[type="text"]:focus, input[type="email"]:focus, input[type="number"]:focus, input[type="date"]:focus, textarea:focus, select:focus {
  border: 1.5px solid #2563eb;
  outline: none;
  background: #fff;
}
</style>
