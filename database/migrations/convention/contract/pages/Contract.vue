<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

// PrimeVue Imports
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import RadioButton from 'primevue/radiobutton';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast'; // For PrimeVue's built-in toast
import ConfirmDialog from 'primevue/confirmdialog'; // For confirmation dialogs
import { useToast } from 'primevue/usetoast'; // PrimeVue's toast service
import { useConfirm } from 'primevue/useconfirm'; // PrimeVue's confirm service

// Custom component imports
import Contract_card from '../cards/Contract_card.vue';
import Contract_content_tab from '../tabs/Contract_content_tab.vue';

// Initialize PrimeVue services
const toast = useToast();
const confirm = useConfirm();

// Get the route object
const route = useRoute();
const contractId = route.params.id;

// Contract data with reactive reference
const contract = ref(null); // Initialize as null to handle loading state more clearly
const loadingContract = ref(true); // New loading state for fetching contract data
const contractError = ref(null); // New error state for fetching contract data

// Dialog control for activation
const activationDialog = ref(false);
const activationType = ref('now'); // 'now' or 'later'
const activationDate = ref(null); // For 'later' activation

// Loading state for activation/termination buttons
const processingAction = ref(false); // Unified loading for actions

// Fetch contract data
const fetchContractData = async () => {
    loadingContract.value = true;
    contractError.value = null;
    try {
        const response = await axios.get(`/api/conventions/${contractId}`);
        if (response.data && response.data.data) {
            contract.value = {
                ...response.data.data,
                id: contractId // Ensure ID is always a string
            };
        } else {
           
        }
    } catch (err) {
        contractError.value = 'Failed to load contract data.';
        console.error('Error fetching contract data:', err);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: contractError.value,
            life: 3000
        });
    } finally {
        loadingContract.value = false;
    }
};

// Open activation dialog
const openActivationDialog = () => {
    activationDialog.value = true;
    // Reset dialog state when opening
    activationType.value = 'now';
    activationDate.value = null;
};

// Close dialog
const closeDialog = () => {
    activationDialog.value = false;
};

// Format date for API - YYYY-MM-DD format (no time for DATE type)
const formatDate = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Handle activation
const handleActivation = async () => {
    if (processingAction.value) return;

    processingAction.value = true;

    try {
        let formattedDate = formatDate(new Date()); // Default to today
        let isDelayedActivation = false;

        if (activationType.value === 'later') {
            if (!activationDate.value) {
                toast.add({
                    severity: 'error',
                    summary: 'Missing Date',
                    detail: 'Please select an activation date',
                    life: 3000
                });
                processingAction.value = false;
                return;
            }
            formattedDate = formatDate(activationDate.value);
            isDelayedActivation = true;
        }

        await axios.patch(
            `/api/conventions/${contractId}/activate`,
            { activationDate: formattedDate },
            { params: { activate_later: isDelayedActivation ? 'yes' : 'no' } }
        );

        const successMessage = isDelayedActivation
            ? `The contract has been scheduled for activation on ${formattedDate}`
            : 'The contract has been activated';

        toast.add({
            severity: 'success',
            summary: 'Operation Successful',
            detail: successMessage,
            life: 3000
        });

        await fetchContractData(); // Refresh contract data to show updated status/timestamp
        closeDialog();

    } catch (error) {
        console.error('Error processing contract activation:', error);
        toast.add({
            severity: 'error',
            summary: 'Operation Failed',
            detail: error.response?.data?.error || error.response?.data?.message || 'Failed to process the contract',
            life: 3000
        });
    } finally {
        processingAction.value = false;
    }
};

// Confirm termination using PrimeVue ConfirmDialog
const confirmTerminate = () => {
    confirm.require({
        message: 'Are you sure you want to terminate this contract? This action cannot be undone.',
        header: 'Confirm Termination',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await terminateContract();
        },
        reject: () => {
            toast.add({ severity: 'info', summary: 'Cancelled', detail: 'Termination cancelled', life: 3000 });
        }
    });
};

// Function to terminate the contract
const terminateContract = async () => {
    if (processingAction.value) return;

    processingAction.value = true;
    try {
        await axios.patch(`/api/conventions/${contractId}/expire`);

        toast.add({
            severity: 'success',
            summary: 'Operation Successful',
            detail: 'The contract has been successfully terminated.',
            life: 3000
        });

        await fetchContractData(); // Refresh data

    } catch (error) {
        console.error('Error terminating contract:', error);
        toast.add({
            severity: 'error',
            summary: 'Operation Failed',
            detail: error.response?.data?.error || error.response?.data?.message || 'An error occurred during termination.',
            life: 3000
        });
    } finally {
        processingAction.value = false;
    }
};

// Fetch data when component mounts
onMounted(() => {
    fetchContractData();
});
</script>

<template>
    <Toast />
    <ConfirmDialog />

    <div class="content">
        <div class="title">
            <h1 id="maintitle">Contract</h1>
        </div>

        <div v-if="loadingContract" class="loading-state">
            Loading contract data...
        </div>
        <div v-else-if="contractError" class="error-state">
            {{ contractError }}
        </div>

        <div v-else-if="contract">
            <Contract_card
                :contract="contract"
                :activateAt="contract.activate_at ? contract.activate_at : 'Not selected yet'"
            />
            <div class="action-buttons">
                <Button
                    v-if="contract.status === 'pending' || contract.status === 'scheduled'"
                    icon="pi pi-file-check"
                    severity="success"
                    label="Activate "
                    @click="openActivationDialog"
                    :loading="processingAction"
                    :disabled="processingAction"
                    class="p-button-sm"
                />

                <Button
                    v-if="contract.status === 'active' || contract.status === 'scheduled'"
                    icon="pi pi-times"
                    severity="danger"
                    label="Terminate "
                    @click="confirmTerminate"
                    :loading="processingAction"
                    :disabled="processingAction"
                    class="p-button-sm"
                />
            </div>
            <div class="title">
                <h1 id="contracts">Contract Content</h1>
            </div>
            <Contract_content_tab :contract="contract" />
        </div>

        <Dialog
            v-model:visible="activationDialog"
            modal
            header="Activate Contract"
            :style="{ width: '450px' }"
            :closable="true"
            @hide="closeDialog"
        >
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

            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="closeDialog" />
                <Button
                    label="OK"
                    severity="success"
                    @click="handleActivation"
                    :loading="processingAction" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
/* Main layout and general styles */
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

/* Loading and Error states */
.loading-state,
.error-state {
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    text-align: center;
    font-size: 1.1rem;
}
.loading-state {
    background-color: #e0f2f7; /* Light blue */
    color: #01579b; /* Darker blue */
}
.error-state {
    background-color: #ffebee; /* Light red */
    color: #c62828; /* Darker red */
}

/* Action buttons section */
.action-buttons {
    margin-top: 1rem;
    margin-left: 1rem; /* Aligns with Avenant_Details .activer */
    margin-bottom: 0.5rem;
    display: flex;
    gap: 0.75rem; /* Space between buttons */
}

/* PrimeVue Dialog specific styles */
.activation-options {
    padding: 1rem 0;
}
.radio-option {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}
.radio-option label {
    margin-left: 0.5rem; /* Space between radio button and label */
}
.date-picker-container {
    margin-top: 0.5rem;
    margin-left: 1.5rem; /* Indent for date picker */
    margin-bottom: 1rem;
}
</style>