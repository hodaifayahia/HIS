<template>
  <div>
    <h1>Médecins Référents</h1>
    <button class="btn btn-primary mb-3" @click="openCreateModal">+ Nouveau Médecin</button>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Prénom</th>
          <th>Nom</th>
          <th>Spécialité</th>
          <th>Clinique</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Date création</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="med in medecins" :key="med.id">
          <td>{{ med.id }}</td>
          <td>{{ med.first_name }}</td>
          <td>{{ med.last_name }}</td>
          <td>{{ med.specialty }}</td>
          <td>{{ med.clinic_name }}</td>
          <td>{{ med.email }}</td>
          <td>{{ med.phone }}</td>
          <td>{{ med.created_at }}</td>
          <td>
            <button class="btn btn-sm btn-warning mr-1" @click="openEditModal(med)">Editer</button>
            <button class="btn btn-sm btn-danger" @click="removeMedecin(med)">Supprimer</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal de création/édition -->
    <div v-if="showModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">
          <h3>{{ editMode ? 'Editer le médecin' : 'Nouveau médecin référent' }}</h3>
          <form @submit.prevent="editMode ? updateMedecin() : createMedecin()">
            <div class="form-group">
              <label>Prénom</label>
              <input v-model="form.first_name" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Nom</label>
              <input v-model="form.last_name" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Spécialité</label>
              <input v-model="form.specialty" type="text" class="form-control" />
            </div>
            <div class="form-group">
              <label>Clinique</label>
              <input v-model="form.clinic_name" type="text" class="form-control" />
            </div>
            <div class="form-group">
              <label>Email</label>
              <input v-model="form.email" type="email" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Téléphone</label>
              <input v-model="form.phone" type="text" class="form-control" />
            </div>
            <div class="form-group mt-3">
              <button type="submit" class="btn btn-success">{{ editMode ? 'Mettre à jour' : 'Créer' }}</button>
              <button type="button" class="btn btn-secondary ml-2" @click="closeModal">Annuler</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Swal from 'sweetalert2';

const medecins = ref([
  {
    id: 1,
    first_name: 'Ali',
    last_name: 'Ben Salah',
    specialty: 'Cardiologie',
    clinic_name: 'Clinique du Coeur',
    email: 'ali.bensalah@exemple.com',
    phone: '0612345678',
    created_at: '2025-07-01 10:00:00',
  },
]);

const showModal = ref(false);
const editMode = ref(false);
const selectedMedecin = ref(null);
const form = ref({
  first_name: '',
  last_name: '',
  specialty: '',
  clinic_name: '',
  email: '',
  phone: '',
});

function openCreateModal() {
  showModal.value = true;
  editMode.value = false;
  form.value = { first_name: '', last_name: '', specialty: '', clinic_name: '', email: '', phone: '' };
  selectedMedecin.value = null;
}

function openEditModal(med) {
  showModal.value = true;
  editMode.value = true;
  form.value = { ...med };
  selectedMedecin.value = med;
}

function createMedecin() {
  // TODO: Replace with API call
  medecins.value.push({
    id: medecins.value.length + 1,
    ...form.value,
    created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
  });
  closeModal();
}

function updateMedecin() {
  if (!selectedMedecin.value) return;
  Object.assign(selectedMedecin.value, form.value);
  closeModal();
}

function removeMedecin(med) {
  Swal.fire({
    title: 'Êtes-vous sûr ?',
    text: 'Cette action est irréversible!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Oui, supprimer',
    cancelButtonText: 'Annuler',
  }).then((result) => {
    if (result.isConfirmed) {
      medecins.value = medecins.value.filter(m => m.id !== med.id);
      Swal.fire('Supprimé!', 'Le médecin a été supprimé.', 'success');
    }
  });
}

function closeModal() {
  showModal.value = false;
  editMode.value = false;
  selectedMedecin.value = null;
  form.value = { first_name: '', last_name: '', specialty: '', clinic_name: '', email: '', phone: '' };
}
</script>

<style scoped>
.table {
  width: 100%;
  margin-bottom: 1rem;
  background-color: #fff;
}
.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-wrapper {
  box-shadow: 0 2px 8px rgba(0,0,0,0.33);
}
.modal-container {
  background: #fff;
  padding: 20px 30px;
  border-radius: 8px;
  min-width: 350px;
  max-width: 90vw;
}
</style>
