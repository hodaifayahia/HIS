<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import FamilyHistoryModel from '../../../Components/InformationHistory/FamilyHistoryModel.vue';

const toaster = useToastr();
const swal = useSweetAlert();

const props = defineProps({
  patientId: {
    type: Number,
    required: true,
  },
});

const familyHistory = ref([]);
const loading = ref(false);
const error = ref(null);
const showAddModal = ref(false);
const showEditModal = ref(false);
const selectedEntry = ref(null);

const fetchPatientFamilyHistory = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/consultation/patients/${props.patientId}/family-history`);
    familyHistory.value = response.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load patient family history.';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const handleFamilyEntryAdded = () => {
  fetchPatientFamilyHistory();
};

const handleFamilyEntryUpdated = () => {
  fetchPatientFamilyHistory();
};

const editEntry = (entry) => {
  selectedEntry.value = { ...entry };
  showEditModal.value = true;
};

const deleteEntry = async (entry) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: 'You are about to delete this family history entry. This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!'
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/consultation/patients/${props.patientId}/family-history/${entry.id}`);
    toaster.success('Family history entry deleted successfully!');
    fetchPatientFamilyHistory();
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Failed to delete family history entry.');
  }
};

onMounted(() => {
  if (props.patientId) {
    fetchPatientFamilyHistory();
  }
});

watch(() => props.patientId, (newId) => {
  if (newId) {
    fetchPatientFamilyHistory();
  }
});
</script>

<template>
  <div class="premium-family-history-section">
    <div class="header-section">
      <div class="premium-section-subtitle">Patient Family History</div>
      <button
        @click="showAddModal = true"
        class="add-family-history-btn"
      >
        Add Family Entry
      </button>
    </div>

    <div v-if="loading" class="premium-loading-state">
      <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
      <p>Loading family history...</p>
    </div>

    <div v-else-if="error" class="premium-empty-state">
      <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
      <p>{{ error }}</p>
    </div>

    <div v-else-if="familyHistory.length === 0" class="premium-empty-state">
      <i class="fas fa-users fa-3x text-muted mb-3"></i>
      <p>No family history recorded for this patient.</p>
    </div>

    <div v-else class="premium-table-container">
      <table class="premium-table">
        <thead>
          <tr>
            <th>Disease Name</th>
            <th>Family Member</th>
            <th>Notes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in familyHistory" :key="entry.id">
            <td>{{ entry.disease_name }}</td>
            <td>{{ entry.relation }}</td>
            <td>{{ entry.notes || '-' }}</td>
            <td class="actions-cell">
              <button @click="editEntry(entry)" class="action-btn edit-btn">
                <i class="fas fa-pencil-alt"></i>
              </button>
              <button @click="deleteEntry(entry)" class="action-btn delete-btn">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <FamilyHistoryModel
    :show="showAddModal"
    :patient-id="props.patientId"
    @close="showAddModal = false"
    @family-entry-added="handleFamilyEntryAdded"
  />

  <FamilyHistoryModel
    :show="showEditModal"
    :patient-id="props.patientId"
    :entry="selectedEntry"
    @close="showEditModal = false"
    @family-entry-updated="handleFamilyEntryUpdated"
  />
</template>

<style scoped>
.premium-family-history-section {
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

.add-family-history-btn {
  background-color: #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.add-family-history-btn:hover {
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