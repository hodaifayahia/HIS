<template>
  <div v-if="modelValue" class="modal-backdrop" @click.self="close">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Liste des Médicaments</h5>
          <button type="button" class="btn-close" @click="close" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Chargement des médicaments...</p>
          </div>
          <div v-else-if="error" class="error-state">
            <p class="error-message">{{ error }}</p>
            <button @click="fetchMedications" class="btn btn-retry">Réessayer</button>
          </div>
          <MedicalesList 
            v-else
            :medications="activeMedications" 
            @medication-selected="handleMedicationSelected"
          />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="close">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import MedicalesList from '../../../Pages/Consultation/medical/MedicalesList.vue';

const props = defineProps({
  modelValue: Boolean,
  medications: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue', 'medication-selected']);

const localMedications = ref([]);
const loading = ref(false);
const error = ref('');

// Use props medications if available, otherwise use locally fetched ones
const activeMedications = computed(() => {
  return props.medications.length > 0 ? props.medications : localMedications.value;
});

const close = () => {
  emit('update:modelValue', false);
  emit('medication', null); // Clear selected medication on close
};

const handleMedicationSelected = (medication) => {
  emit('medication-selected', medication);
  close();
};


const fetchMedications = async () => {
  if (props.medications.length > 0) return; // Don't fetch if medications are provided via props
  
  loading.value = true;
  error.value = '';
  
  try {
    const response = await axios.get('/api/medications');
    localMedications.value = response.data.data || [];
  } catch (err) {
    error.value = 'Erreur lors du chargement des médicaments. Veuillez réessayer.';
    console.error('Error fetching medications:', err);
  } finally {
    loading.value = false;
  }
};

// Fetch medications when modal opens and no medications are provided
watch(() => props.modelValue, (newValue) => {
  if (newValue && !props.medications.length && !localMedications.value.length) {
    fetchMedications();
  }
});

// Handle escape key
const handleKeydown = (event) => {
  if (event.key === 'Escape' && props.modelValue) {
    close();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

// Cleanup event listener
import { onUnmounted } from 'vue';
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 20px;
  box-sizing: border-box;
}

.modal-dialog {
  background: #fff;
  border-radius: 12px;
  max-width: 1400px;
  width: 100%;
  max-height: 90vh;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
      overflow-y: auto;

}

.modal-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
  background: #f8f9fa;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  color: #6c757d;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.btn-close:hover {
  background: #e9ecef;
  color: #333;
}

.btn-close:focus {
  outline: 2px solid #007bff;
  outline-offset: 2px;
}

.modal-body {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
  min-height: 0;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-shrink: 0;
  background: #f8f9fa;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  color: #dc3545;
  margin-bottom: 16px;
  font-weight: 500;
}

.btn {
  padding: 8px 16px;
  border-radius: 6px;
  border: 1px solid transparent;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border-color: #6c757d;
}

.btn-secondary:hover {
  background: #545b62;
  border-color: #545b62;
}

.btn-retry {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.btn-retry:hover {
  background: #0056b3;
  border-color: #0056b3;
}

.btn:focus {
  outline: 2px solid #007bff;
  outline-offset: 2px;
}

/* Responsive design */
@media (max-width: 768px) {
  .modal-backdrop {
    padding: 10px;
  }
  
  .modal-dialog {
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 16px;
  }
  
  .modal-title {
    font-size: 1.1rem;
  }
}

@media (max-width: 480px) {
  .modal-backdrop {
    padding: 5px;
  }
  
  .modal-dialog {
    max-height: 98vh;
    border-radius: 8px;
  }
  
  .modal-header,
  .modal-body {
    padding: 12px;
  }
  
  .modal-footer {
    padding: 12px;
  }
}
</style>