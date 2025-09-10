<template>
  <div>
    <h1>Liste des Campagnes</h1>
    <button class="btn btn-primary mb-3" @click="showModal = true">
      + Nouvelle Campagne
    </button>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nom</th>
          <th>Description</th>
          <th>Date début</th>
          <th>Date fin</th>
          <th>Statut</th>
          <th>Créée par</th>
          <th>Date création</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="campagne in campagnes" :key="campagne.id">
          <td>{{ campagne.id }}</td>
          <td>{{ campagne.name }}</td>
          <td>{{ campagne.description }}</td>
          <td>{{ campagne.start_date }}</td>
          <td>{{ campagne.end_date }}</td>
          <td>{{ campagne.status }}</td>
          <td>{{ campagne.created_by_user_id }}</td>
          <td>{{ campagne.created_at }}</td>
          <td>
            <button v-if="campagne.status === 'DRAFT'" class="btn btn-sm btn-warning mr-1" @click="openEditModal(campagne)">Editer</button>
            <button v-else class="btn btn-sm btn-info mr-1" @click="openStatusModal(campagne)">Changer Statut</button>
            <button class="btn btn-sm btn-danger" @click="removeCampagne(campagne)">Supprimer</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal de création/édition de campagne -->
    <div v-if="showModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">
          <h3>{{ editMode ? 'Editer la campagne' : 'Créer une nouvelle campagne' }}</h3>
          <form @submit.prevent="editMode ? updateCampagne() : createCampagne()">
            <div class="form-group">
              <label>Nom</label>
              <input v-model="form.name" type="text" class="form-control" required :disabled="editMode && form.status !== 'DRAFT'" />
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea v-model="form.description" class="form-control" :disabled="editMode && form.status !== 'DRAFT'"></textarea>
            </div>
            <div class="form-group">
              <label>Date début</label>
              <input v-model="form.start_date" type="date" class="form-control" :disabled="editMode && form.status !== 'DRAFT'" />
            </div>
            <div class="form-group">
              <label>Date fin</label>
              <input v-model="form.end_date" type="date" class="form-control" :disabled="editMode && form.status !== 'DRAFT'" />
            </div>
            <div class="form-group">
              <label>Statut</label>
              <select v-model="form.status" class="form-control" :disabled="editMode && form.status !== 'DRAFT'">
                <option value="DRAFT">Brouillon</option>
                <option value="ACTIVE">Active</option>
                <option value="COMPLETED">Terminée</option>
                <option value="ARCHIVED">Archivée</option>
              </select>
            </div>
            <div class="form-group mt-3">
              <button type="submit" class="btn btn-success">{{ editMode ? 'Mettre à jour' : 'Créer' }}</button>
              <button type="button" class="btn btn-secondary ml-2" @click="closeModal">Annuler</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de changement de statut -->
    <div v-if="showStatusModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">
          <h3>Changer le statut de la campagne</h3>
          <form @submit.prevent="updateStatus()">
            <div class="form-group">
              <label>Nouveau statut</label>
              <select v-model="form.status" class="form-control">
                <option value="ACTIVE" v-if="selectedCampagne.status !== 'ACTIVE'">Active</option>
                <option value="COMPLETED" v-if="selectedCampagne.status !== 'COMPLETED'">Terminée</option>
                <option value="ARCHIVED" v-if="selectedCampagne.status !== 'ARCHIVED'">Archivée</option>
              </select>
            </div>
            <div class="form-group mt-3">
              <button type="submit" class="btn btn-success">Mettre à jour</button>
              <button type="button" class="btn btn-secondary ml-2" @click="closeStatusModal">Annuler</button>
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

// Demo data, replace with API call
const campagnes = ref([
  {
    id: 1,
    name: 'Campagne Vaccination',
    description: 'Campagne de vaccination contre la grippe',
    start_date: '2025-09-01',
    end_date: '2025-09-30',
    status: 'ACTIVE',
    created_by_user_id: 2,
    created_at: '2025-07-01 10:00:00',
  },
]);

const showModal = ref(false);
const showStatusModal = ref(false);
const editMode = ref(false);
const selectedCampagne = ref(null);
const form = ref({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  status: 'DRAFT',
});

function createCampagne() {
  campagnes.value.push({
    id: campagnes.value.length + 1,
    ...form.value,
    created_by_user_id: 1, // TODO: Replace with actual user
    created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
  });
  closeModal();
}

function openEditModal(campagne) {
  editMode.value = true;
  showModal.value = true;
  form.value = { ...campagne };
  selectedCampagne.value = campagne;
}

function updateCampagne() {
  if (!selectedCampagne.value) return;
  Object.assign(selectedCampagne.value, form.value);
  closeModal();
}

function openStatusModal(campagne) {
  selectedCampagne.value = campagne;
  form.value = { ...campagne };
  showStatusModal.value = true;
}

function updateStatus() {
  if (!selectedCampagne.value) return;
  if (form.value.status === 'DRAFT') return; // Can't revert to DRAFT
  selectedCampagne.value.status = form.value.status;
  showStatusModal.value = false;
}

function closeModal() {
  showModal.value = false;
  editMode.value = false;
  form.value = { name: '', description: '', start_date: '', end_date: '', status: 'DRAFT' };
  selectedCampagne.value = null;
}

function closeStatusModal() {
  showStatusModal.value = false;
  selectedCampagne.value = null;
}

function removeCampagne(campagne) {
  Swal.fire({
    title: 'Êtes-vous sûr ?',
    text: 'Cette action est irréversible!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Oui, supprimer',
    cancelButtonText: 'Annuler',
  }).then((result) => {
    if (result.isConfirmed) {
      campagnes.value = campagnes.value.filter(c => c.id !== campagne.id);
      Swal.fire('Supprimé!', 'La campagne a été supprimée.', 'success');
    }
  });
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
