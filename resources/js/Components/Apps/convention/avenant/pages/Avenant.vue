<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import Avenant_card from '../cards/Avenant_card.vue';
import Avenant_tab from '../tabs/Avenant_tab.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import RadioButton from 'primevue/radiobutton';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';


// Retrieve the avenant ID from the route
const route = useRoute();
const avenantId = route.query.id


// Data states
const avenantData = ref(null);
const loading = ref(true);
const error = ref(null);
const processingActivation = ref(false);

// Dialog control
const activationDialog = ref(false);
const activationType = ref('now');
const activationDate = ref(null);

const toast = useToast(); // Initialize useToast

// Fetch data from API
const fetchAvenantData = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/avenants/${avenantId}`); // Use API_BASE_URL here
    avenantData.value = response.data;
    loading.value = false;
  } catch (err) {
    error.value = "Failed to load avenant data";
    loading.value = false;
    console.error("Error fetching avenant data:", err);
    toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.value,
        life: 3000
    });
  }
};

// Open activation dialog
const openActivationDialog = () => {
  activationDialog.value = true;
};

// Close dialog
const closeDialog = () => {
  activationDialog.value = false;
};

// Handle activation
const handleActivation = async () => {
  if (processingActivation.value) return; // Prevent multiple submissions

  processingActivation.value = true;

  try {
    const today = new Date();
    let formattedDate = formatDate(today);
    let isDelayedActivation = false;

    if (activationType.value === 'later') {
      if (!activationDate.value) {
        toast.add({
          severity: 'error',
          summary: 'Missing Date',
          detail: 'Please select an activation date',
          life: 3000
        });
        processingActivation.value = false;
        return;
      }
      formattedDate = formatDate(activationDate.value);
      isDelayedActivation = true;
    }

    // *** MODIFIED AXIOS CALL HERE ***
    // Align with Laravel route: /api/avenants/{avenantId}/activate
    await axios.patch( // Changed from .put to .patch for consistency with route
      `/api/avenants/${avenantId}/activate`,
      { activationDate: formattedDate },
      { params: { activate_later: isDelayedActivation ? 'yes' : 'no' } } // Use params for query string
    );

    const successMessage = isDelayedActivation
      ? `The avenant has been scheduled for activation on ${formattedDate}`
      : 'The avenant has been activated';

    toast.add({
      severity: 'success',
      summary: 'Operation Successful',
      detail: successMessage,
      life: 3000
    });

    // Refresh avenant data to show updated status/timestamp
    await fetchAvenantData();
    closeDialog();

  } catch (error) {
    console.error('Error processing avenant:', error);
    toast.add({
      severity: 'error',
      summary: 'Operation Failed',
      detail: error.response?.data?.error || error.message || 'Failed to process the avenant',
      life: 3000
    });
  } finally {
    processingActivation.value = false;
  }
};

// Format date for API - YYYY-MM-DD format (no time for DATE type)
const formatDate = (date) => {
  if (!date) return null;

  // Create a new Date object with the selected date
  const d = new Date(date);

  // Format as YYYY-MM-DD
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
};

// Fetch data when component mounts
onMounted(() => {
  fetchAvenantData();
});
</script>
<template>
  <Toast />
  <div class="content">
    <div class="title">
      <h1 id="maintitle">Avenant</h1>
    </div>
    <div v-if="loading" class="loading">
      Loading avenant data...
    </div>
    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else-if="avenantData">
      <Avenant_card :avenantData="avenantData.id" :contractId="avenantData.convention_id" :status="avenantData.status" :createdAt="avenantData.created_at"
        :activateAt="avenantData.activate_at ? avenantData.activate_at : 'Not selected yet'"
          />
        <div class="activer">
            <Button v-if="avenantData.status === 'pending'"
                      icon="pi pi-file-check"
                      severity="success"
                      label="Activate"
                      @click="openActivationDialog" />
        </div>
      <div class="title">
        <h1 id="contracts">Avenant Content</h1>
      </div>
      <Avenant_tab :avenantState="avenantData.status" :avenantpage="'yes'" :avenantid="avenantData.id" :convention_data="avenantData.convention_data"  :contractState="avenantData.convention_status"/>
    </div>

    <Dialog
      v-model:visible="activationDialog"
      modal
      header="Activate Avenant"
      :style="{ width: '450px' }"
      :closable="true">
      <div class="activation-options">
        <div class="radio-option">
          <RadioButton v-model="activationType" inputId="activate-now" name="activation-type" value="now" />
          <label for="activate-now" class="ml-2">Activate now</label>
        </div>
        <div class="radio-option">
          <RadioButton v-model="activationType" inputId="activate-later" name="activation-type" value="later" />
          <label for="activate-later" class="ml-2">Select activation date</label>
        </div>

        <div v-if="activationType === 'later'" class="date-picker-container">
          <Calendar v-model="activationDate" :showIcon="true" dateFormat="dd/mm/yy" />
        </div>
      </div>

      <div class="dialog-footer">
        <Button label="Cancel" severity="secondary" text @click="closeDialog" />
        <Button
          label="OK"
          severity="success"
          @click="handleActivation"
          :loading="processingActivation" />
      </div>
    </Dialog>
  </div>
</template>
<style scoped>
.container {
  display: flex;
  flex-direction: row;
}
.content {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding-top: 10px;
  padding-right: 20px;
  padding-bottom: 20px;
}
.title h1 {
  margin-top: 40px;
  margin-bottom: 30px;
  font-weight: bold;
  font-size: 2rem;
}
.title #maintitle {
  margin-top: 20px;
  margin-bottom: 10px;
  font-weight: bold;
  font-size: 2rem;
}
#contracts {
  margin-top: 1rem;
}
.loading,
.error {
  padding: 20px;
  margin: 20px 0;
  border-radius: 8px;
}
.loading {
  background-color: #f0f0f0;
}
.error {
  background-color: #ffebee;
  color: #d32f2f;
}
.activer {
    margin-top: 1rem;
    margin-left: 1rem;
    margin-bottom: 0.5rem;
    display: flex;
    flex-direction: row;
}
.activation-options {
  padding: 1rem 0;
}
.radio-option {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}
.date-picker-container {
  margin-top: 0.5rem;
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 1rem;
}
</style>