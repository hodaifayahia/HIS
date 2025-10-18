<template>
  <div class="organisme-details-page">
    <div class="organisme-header-card">
      <h1>{{ organisme.raison_sociale }}</h1>
      <div class="org-info-grid">
        <div><strong>Forme juridique:</strong> {{ organisme.forme_juridique_sociale }}</div>
        <div><strong>Adresse:</strong> {{ organisme.adresse }}</div>
        <div><strong>Ville:</strong> {{ organisme.ville }}</div>
        <div><strong>Code postal:</strong> {{ organisme.code_postal }}</div>
        <div><strong>Pays:</strong> {{ organisme.pays }}</div>
        <div><strong>Téléphone:</strong> {{ organisme.telephone_fixe }}</div>
        <div><strong>Mobile:</strong> {{ organisme.mobile }}</div>
        <div><strong>Email:</strong> {{ organisme.email }}</div>
        <div><strong>Registre de commerce:</strong> {{ organisme.registre_de_commerce }}</div>
        <div><strong>NIF:</strong> {{ organisme.nif }}</div>
        <div><strong>NIS:</strong> {{ organisme.nis }}</div>
        <div><strong>Article:</strong> {{ organisme.article }}</div>
        <div><strong>Fax:</strong> {{ organisme.fax }}</div>
        <div><strong>Latitude:</strong> {{ organisme.latitude }}</div>
        <div><strong>Longitude:</strong> {{ organisme.longitude }}</div>
        <div v-if="organisme.organism_color" style="display:flex;align-items:center;gap:0.5rem;">
          <span style="font-weight:600;">Couleur:</span>
          <span :style="{ backgroundColor: organisme.organism_color, width: '24px', height: '24px', borderRadius: '50%', display: 'inline-block', border: '1px solid #ccc' }"></span>
          <span>{{ organisme.organism_color }}</span>
        </div>
      </div>
      <div v-if="organisme.autres_informations" class="org-extra">
        <strong>Autres informations:</strong>
        <div>{{ organisme.autres_informations }}</div>
      </div>
    </div>

    <div class="tabs">
      <button :class="{active: tab==='contacts'}" @click="tab='contacts'">Contacts</button>
      <button :class="{active: tab==='logs'}" @click="tab='logs'">Historique</button>
    </div>

    <div v-if="tab==='contacts'">
      <ContactList :organismeId="organisme.id" />
    </div>
    <div v-else-if="tab==='logs'">
      <div class="logs-placeholder">
        <h3>Historique des actions</h3>
        <p>À venir : affichage des logs et activités pour ce partenaire.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import ContactList from './ContactList.vue';

const route = useRoute();
const organisme = ref({});
const tab = ref('contacts');

function fetchOrganisme() {
  // TODO: Replace with real API call
  // Example: fetch(`/api/organismes/${route.params.id}`)
  organisme.value = {
    id: Number(route.params.id),
    raison_sociale: 'Entreprise Alpha',
    forme_juridique_sociale: 'SARL',
    adresse: '123 Rue Principale',
    ville: 'Alger',
    code_postal: '16000',
    pays: 'Algérie',
    latitude: '36.7538',
    longitude: '3.0588',
    registre_de_commerce: 'RC123456',
    article: 'Art. 12',
    nif: 'NIF123456',
    nis: 'NIS654321',
    telephone_fixe: '021123456',
    fax: '021654321',
    mobile: '0550123456',
    email: 'contact@alpha.com',
    autres_informations: 'Client premium depuis 2022.',
    organism_color: '#3498db'
  };
}

onMounted(fetchOrganisme);
</script>

<style scoped>
.organisme-details-page {
  max-width: 950px;
  margin: 2.5rem auto;
  padding: 1.5rem;
}
.organisme-header-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  padding: 2rem 2.5rem 1.5rem 2.5rem;
  margin-bottom: 2.2rem;
}
.organisme-header-card h1 {
  margin-bottom: 1.2rem;
  font-size: 2.1rem;
  color: #2563eb;
}
.org-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.7rem 1.5rem;
  font-size: 1.05em;
}
.org-extra {
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
.logs-placeholder {
  background: #f9fafb;
  border-radius: 10px;
  padding: 2rem;
  text-align: center;
  color: #888;
  font-size: 1.1em;
}
</style>
