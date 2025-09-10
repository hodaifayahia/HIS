<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import SurgicalHistroyModel from '../../../Components/InformationHistory/SurgicalHistroyModel.vue';
import { useSweetAlert } from '../../../Components/useSweetAlert';

const swal = useSweetAlert();
const toast = useToastr();

const props = defineProps({
  patientId: {
    type: Number,
    required: true,
  },
});

const surgicalHistory = ref([]);
const loading = ref(false);
const error = ref(null);
const showAddModal = ref(false);
const showEditModal = ref(false);
const selectedEntry = ref(null);

const fetchPatientSurgicalHistory = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/consultation/patients/${props.patientId}/surgical-history`);
    surgicalHistory.value = response.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load patient surgical history.';
    toast.error(error.value);
  } finally {
    loading.value = false;
  }
};

const handleSurgicalEntryAdded = () => {
  fetchPatientSurgicalHistory();
};

const handleSurgicalEntryUpdated = () => {
  fetchPatientSurgicalHistory();
};

const editEntry = (entry) => {
  selectedEntry.value = { 
    ...entry,
    surgery_name: entry.procedure_name,
    date: entry.surgery_date,
    notes: entry.description
  };
  showEditModal.value = true;
};

const deleteEntry = async (entry) => {
  const result = await swal.fire({
    title: 'Are you sure?',
    text: 'You are about to delete this surgical history. This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!'
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/consultation/patients/${props.patientId}/surgical-history/${entry.id}`);
    toast.success('Surgical history deleted successfully!');
    fetchPatientSurgicalHistory();
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete surgical history.');
  }
};

onMounted(() => {
  if (props.patientId) {
    fetchPatientSurgicalHistory();
  }
});

watch(() => props.patientId, (newId) => {
  if (newId) {
    fetchPatientSurgicalHistory();
  }
});
</script>
<template>
  <div class="premium-surgical-history-section">
    <div class="header-section">
      <div class="premium-section-subtitle">Patient Surgical History</div>
      <button
        @click="showAddModal = true"
        class="add-surgical-history-btn"
      >
        Add Surgical Entry
      </button>
    </div>

    <div v-if="loading" class="premium-loading-state">
      <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
      <p>Loading surgical history...</p>
    </div>

    <div v-else-if="error" class="premium-empty-state">
      <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
      <p>{{ error }}</p>
    </div>

    <div v-else-if="surgicalHistory.length === 0" class="premium-empty-state">
      <i class="fas fa-hospital-user fa-3x text-muted mb-3"></i>
      <p>No surgical history recorded for this patient.</p>
    </div>

    <div v-else class="premium-table-container">
      <table class="premium-table">
        <thead>
          <tr>
            <th>Surgery Name</th>
            <th>Date</th>
            <th>Notes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in surgicalHistory" :key="entry.id">
            <td>{{ entry.procedure_name }}</td>
            <td>{{ new Date(entry.surgery_date).toLocaleDateString() }}</td>
            <td>{{ entry.description || '-' }}</td>
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

  <!-- Add Modal -->
  <SurgicalHistroyModel
    :show="showAddModal"
    :patient-id="patientId"
    @close="showAddModal = false"
    @surgical-entry-added="handleSurgicalEntryAdded"
  />

  <!-- Edit Modal -->
  <SurgicalHistroyModel
    :show="showEditModal"
    :patient-id="patientId"
    :entry="selectedEntry"
    @close="showEditModal = false"
    @surgical-entry-updated="handleSurgicalEntryUpdated"
  />
</template>

<style scoped>
.premium-surgical-history-section {
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
.add-surgical-history-btn {
  background-color: #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.add-surgical-history-btn:hover {
  background-color: #2563eb;
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

.add-allergy-btn {
  background-color: #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.add-allergy-btn:hover {
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

.severity-mild {
  color: #0ea5e9;
  background-color: #e0f2fe;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.875rem;
}

.severity-moderate {
  color: #eab308;
  background-color: #fef9c3;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.875rem;
}

.severity-severe {
  color: #dc2626;
  background-color: #fee2e2;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.875rem;
}

/* New styles for action buttons */
.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px 8px;
  font-size: 1rem;
  margin-right: 5px; /* Space between buttons */
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.edit-btn {
  color: #3b82f6; /* Blue for edit */
}

.edit-btn:hover {
  background-color: #e0f2fe;
}

.delete-btn {
  color: #dc2626; /* Red for delete */
}

.delete-btn:hover {
  background-color: #fee2e2;
}
</style>