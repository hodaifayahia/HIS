<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import ChronicDiseasesModel from '../../../Components/InformationHistory/ChronicDiseasesModel.vue';
import { useSweetAlert } from '../../../Components/useSweetAlert';

const toaster = useToastr();
const props = defineProps({
  patientId: {
    type: Number,
    required: true,
  },
});
const swal = useSweetAlert();

const chronicDiseases = ref([]);
const showAddModal = ref(false);
const showEditModal = ref(false);
const selectedDisease = ref(null);
const loading = ref(false);
const error = ref(null);

const fetchPatientChronicDiseases = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/consultation/patients/${props.patientId}/chronic-diseases`);
    chronicDiseases.value = response.data?.data || response.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load patient chronic diseases.';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const handleDiseaseAdded = () => {
  fetchPatientChronicDiseases();
  showAddModal.value = false;
};

const handleDiseaseUpdated = () => {
  fetchPatientChronicDiseases();
  showEditModal.value = false;
  selectedDisease.value = null;
};

const openEditModal = (disease) => {
  selectedDisease.value = { ...disease };
  showEditModal.value = true;
};

const deleteDisease = async (diseaseId) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: 'You are about to delete this chronic disease record. This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!'
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/consultation/patients/${props.patientId}/chronic-diseases/${diseaseId}`);
    toaster.success('Chronic disease deleted successfully!');
    fetchPatientChronicDiseases();
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Failed to delete chronic disease.');
  }
};

onMounted(() => {
  if (props.patientId) {
    fetchPatientChronicDiseases();
  }
});

watch(() => props.patientId, (newId) => {
  if (newId) {
    fetchPatientChronicDiseases();
  }
});
</script>

<template>
  <div class="premium-chronic-disease-history-section">
    <div class="header-section">
      <div class="premium-section-subtitle">Patient Chronic Disease History</div>
      <button @click="showAddModal = true" class="add-chronic-disease-btn">
        Add Chronic Disease
      </button>
    </div>

    <div v-if="loading" class="premium-loading-state">
      <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
      <p>Loading chronic diseases...</p>
    </div>

    <div v-else-if="error" class="premium-empty-state">
      <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
      <p>{{ error }}</p>
    </div>

    <div v-else-if="chronicDiseases.length === 0" class="premium-empty-state">
      <i class="fas fa-notes-medical fa-3x text-muted mb-3"></i>
      <p>No chronic diseases recorded for this patient.</p>
    </div>

    <div v-else class="premium-table-container">
      <table class="premium-table">
        <thead>
          <tr>
            <th>Disease Name</th>
            <th>Description</th>
            <th>Diagnosis Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="disease in chronicDiseases" :key="disease.id">
            <td>{{ disease.name }}</td>
            <td>{{ disease.description || '-' }}</td>
            <td>{{ new Date(disease.diagnosis_date).toLocaleDateString() }}</td>
            <td>
              <button @click="openEditModal(disease)" class="action-btn edit-btn">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="deleteDisease(disease.id)" class="action-btn delete-btn">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Modal -->
  <ChronicDiseasesModel
    :show="showAddModal"
    :patient-id="patientId"
    @close="showAddModal = false"
    @chronic-disease-added="handleDiseaseAdded"
  />

  <!-- Edit Modal -->
  <ChronicDiseasesModel
    v-if="selectedDisease"
    :show="showEditModal"
    :patient-id="patientId"
    :disease-to-edit="selectedDisease"
    @close="showEditModal = false"
    @chronic-disease-updated="handleDiseaseUpdated"
  />
</template>

<style scoped>
.premium-chronic-disease-history-section {
  background-color: #ffffff;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-top: 2rem;
}

.premium-loading-state,
.premium-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6c757d;
  text-align: center;
}

.premium-loading-state p,
.premium-empty-state p {
  font-size: 1.1rem;
  margin-top: 0.5rem;
}

.premium-table-container {
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.add-chronic-disease-btn {
  background-color: #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.add-chronic-disease-btn:hover {
  background-color: #2563eb;
}

.premium-table th,
.premium-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.premium-table th {
  background-color: #f1f5f9;
  font-weight: 600;
  color: #334155;
  text-transform: uppercase;
  font-size: 0.85rem;
}

.premium-table tbody tr:hover {
  background-color: #f8fafc;
}

.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px 8px;
  font-size: 1rem;
  margin-right: 5px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.edit-btn {
  color: #3b82f6;
}

.edit-btn:hover {
  background-color: #e0f2fe;
}

.delete-btn {
  color: #dc2626;
}

.delete-btn:hover {
  background-color: #fee2e2;
}
</style>