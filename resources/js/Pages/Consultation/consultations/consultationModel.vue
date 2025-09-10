<script setup>
import { ref, onMounted, computed, watch, onUnmounted, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import PlaceholderTab from '../../../Components/Consultation/PlaceholderTab.vue';
import TemplatesTab from '../../../Components/Consultation/TemplatesTab.vue';
import InformationHistoryTab from '../InformationHistory/InformationHistoryTab.vue';
import PrescriptionTab from '../../../Pages/Consultation/Prescription/PrescriptionTab.vue';
import OldConsulation from '../OldConsulation/OldConsulation.vue';
import PatientInformation from '../PatientInformations/PatientInformation.vue';
import OpinionRequest from '../OpinionRequest/OpinionRequest.vue';
import AppointmentModal from '../../../Components/appointments/AppointmentModal.vue';
import AddConsultationToWorkspaceModal from '../../../Components/DoctorDoc/AddConsultationToWorkspaceModal.vue';

const router = useRouter();
const route = useRoute();
const patientInfo = ref();
const toaster = useToastr();
const patientInformationRef = ref(null);

const patientId = route.params.patientId;
if (!patientId) {
    toaster.error('Patient ID is required but not provided in the route params.');
}
const consulationId = route.params.consulationId;
const appointmentId = route.params.appointmentid;
if (!appointmentId) {
    toaster.error('Appointment ID is required but not provided in the route params.');
}
const doctorId = route.params.doctorId;
if (!doctorId) {
    toaster.error('Doctor ID is required but not provided in the route params.');
}
const specialization_id = route.params.specialization_id;
if (!specialization_id) {
    toaster.error('specialization_id is required but not provided in the route params.');
}

const swal = useSweetAlert();

const activeTab = ref('PatientInfo');
const consultationData = ref({});
const selectedTemplates = ref([]);
const loading = ref(false);

const isModalVisible = ref(false);
const consultationName = ref('');

const showAddToWorkspaceModal = ref(false);

const tabs = [
    { id: 'PatientInfo', label: 'Patient Info', icon: 'mr-1 fas fa-user-md' },
    { id: 'consultation', label: 'Consultations History', icon: 'mr-1 fas fa-user-md' },
    { id: 'InformationHistory', label: 'Medical History', icon: 'mr-1 fas fa-notes-medical' },
    { id: 'placeholders', label: 'Attributes', icon: 'mr-1 fas fa-puzzle-piece' },
    { id: 'Templates', label: 'Documents', icon: 'mr-1 fas fa-file-alt' },
    { id: 'prescription', label: 'Prescriptions', icon: 'mr-1 fas fa-prescription ' },
    { id: 'OpinionRequest', label: 'Opinion Request', icon: 'mr-1 fas fa-stethoscope' },
];

const placeholderTabRef = ref(null);

let saveTimeout = null;
const autoSaveDelay = 2000;

const debouncedAutoSave = () => {
    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }
    saveTimeout = setTimeout(() => {
        autoSaveConsultationData();
    }, autoSaveDelay);
};

const autoSaveConsultationData = async () => {
    try {
        if (Object.keys(consultationData.value).length > 0 && activeTab.value !== 'PatientInfo') {
            console.log('Auto-saving consultation data...');
            sessionStorage.setItem(`consultation_${appointmentId}`, JSON.stringify({
                data: consultationData.value,
                activeTab: activeTab.value,
                consultationName: consultationName.value,
                timestamp: Date.now()
            }));
        }
    } catch (error) {
        console.error('Auto-save failed:', error);
    }
};

const loadSavedConsultationData = () => {
    try {
        const savedData = sessionStorage.getItem(`consultation_${appointmentId}`);
        if (savedData) {
            const parsed = JSON.parse(savedData);
            const oneHour = 60 * 60 * 1000;
            if (Date.now() - parsed.timestamp < oneHour) {
                consultationData.value = parsed.data || {};
                consultationName.value = parsed.consultationName || '';
                console.log('Loaded saved consultation data:', parsed);
            } else {
                sessionStorage.removeItem(`consultation_${appointmentId}`);
            }
        }
    } catch (error) {
        console.error('Failed to load saved consultation data:', error);
    }
};

const handleConsultationNameUpdate = (newName) => {
    consultationName.value = newName;
    consultationData.value.consultation_name = newName;
    debouncedAutoSave();
};

const setActiveTab = async (tabId) => {
    try {
        if (activeTab.value === 'PatientInfo' && tabId !== 'PatientInfo') {
            if (patientInformationRef.value && patientInformationRef.value.updateConsultation) {
                await patientInformationRef.value.updateConsultation();
                consultationName.value = patientInformationRef.value.consultation_name;
                consultationData.value.consultation_name = consultationName.value;
            }
        }

        if (activeTab.value === 'placeholders' && tabId !== 'placeholders') {
            if (placeholderTabRef.value) {
                await placeholderTabRef.value.saveAttributes();
            }
        }

        consultationData.value._activeTab = tabId;

    } catch (error) {
        console.error('Error saving data before tab change:', error);
        toaster.error('Failed to save current tab data');
        return;
    }

    activeTab.value = tabId;
    debouncedAutoSave();
};

const updateInfo = (data) => {
    patientInfo.value = data;
    if (data && data.specialization_id) {
        specialization_id.value = data.specialization_id;
    }
};

// const goToNextTab = () => {
//     const currentIndex = tabs.findIndex(tab => tab.id === activeTab.value);
//     if (currentIndex < tabs.length - 1) {
//         setActiveTab(tabs[currentIndex + 1].id);
//     }
// };

const isLastTab = computed(() => {
    return activeTab.value === 'prescription' || activeTab.value === 'OpinionRequest';
});

const goToAddConsulationPage = async () => {
   
        try {
            if (activeTab.value === 'placeholders' && placeholderTabRef.value) {
                await placeholderTabRef.value.saveAttributes();
            }

            isModalVisible.value = true;
            await axios.patch(`/api/appointment/${appointmentId}/status`, {
                status: 4
            });
            sessionStorage.removeItem(`consultation_${appointmentId}`);

        } catch (error) {
            console.error('Error saving before finish or updating appointment status:', error);
            toaster.error('Failed to finalize consultation or update appointment status.');
        }
    } 


const closeModal = () => {
    isModalVisible.value = false;
    sessionStorage.removeItem(`consultation_${appointmentId}`);
    router.push({
        name: 'admin.consultations.consulation',
    });
};

const updateConsultationData = (newData) => {
    console.log('Received new consultation data:', newData);
    consultationData.value = { ...consultationData.value, ...newData };
    debouncedAutoSave();
};

const updateSelectedTemplates = (templates) => {
    selectedTemplates.value = templates;
    debouncedAutoSave();
};

const openAddToWorkspaceModal = async () => {
    try {
        if (activeTab.value === 'placeholders' && placeholderTabRef.value) {
            await placeholderTabRef.value.saveAttributes();
        }
    } catch (error) {
        console.error('Error saving data before opening workspace modal:', error);
        toaster.error('Failed to save current tab data before adding to workspace.');
    }

    if (consulationId) {
        showAddToWorkspaceModal.value = true;
    } else {
        toaster.error("Cannot add to workspace: Consultation not yet created or ID is missing.");
        console.warn("Attempted to open workspace modal for consultation with missing ID.");
    }
};

const handleConsultationAddedToWorkspace = () => {
    toaster.success('Consultation added to workspace successfully!');
    showAddToWorkspaceModal.value = false;
};

watch(consultationData, () => {
    debouncedAutoSave();
}, { deep: true });

const cleanup = () => {
    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }
};

// --- START OF CHANGES ---

onMounted(() => {
    // 1. Load saved consultation data from sessionStorage first
    loadSavedConsultationData();

    // 2. Restore active tab if it was saved, OTHERWISE, default to 'placeholders'.
    if (consultationData.value._activeTab) {
        activeTab.value = consultationData.value._activeTab;
    } else {
        // If no saved active tab, default to 'placeholders'
        activeTab.value = 'placeholders';
        // Immediately trigger the attribute loading if defaulting to placeholders
        // No need for nextTick here, as the watch will handle it on this initial set
    }

    // 3. Set up the beforeunload listener for cleanup
    window.addEventListener('beforeunload', cleanup);
});
// WATCH activeTab with nextTick for reliable ref access
// This is the primary mechanism for calling getSavedConsultationAttributes
// when the 'placeholders' tab becomes active.
watch(activeTab, async (newTab) => {
    if (newTab === 'placeholders') {
        // Wait for the DOM to update and PlaceholderTab to be mounted
        await nextTick();
        if (placeholderTabRef.value && placeholderTabRef.value.getSavedConsultationAttributes) {
            console.log('Calling getSavedConsultationAttributes from watch(activeTab)');
            // Call the method exposed by the PlaceholderTab child component
            await placeholderTabRef.value.getSavedConsultationAttributes();
        } else {
            console.warn('PlaceholderTab ref or getSavedConsultationAttributes method not available even after nextTick.');
        }
    }
});

// --- END OF CHANGES ---


const hasTabData = (tabId) => {
    const data = consultationData.value;
    switch (tabId) {
        case 'PatientInfo':
            return consultationName.value || data.consultation_name;
        case 'placeholders':
            return Object.keys(data).some(key =>
                key.includes('.') || (key.includes('_') && !key.startsWith('_'))
            );
        case 'Templates':
            return selectedTemplates.value && selectedTemplates.value.length > 0;
        case 'prescription':
            return Object.keys(data).some(key =>
                key.toLowerCase().includes('prescription') ||
                key.toLowerCase().includes('medication')
            );
        case 'InformationHistory':
            return Object.keys(data).some(key =>
                key.toLowerCase().includes('history') ||
                key.toLowerCase().includes('medical') ||
                key.toLowerCase().includes('allergy') ||
                key.toLowerCase().includes('chronic') ||
                key.toLowerCase().includes('surgical') ||
                key.toLowerCase().includes('family')
            );
        case 'consultation':
            return false;
        case 'OpinionRequest':
            return false;
        default:
            return false;
    }
};

onUnmounted(() => {
    cleanup();
    window.removeEventListener('beforeunload', cleanup);
});
</script>

<template>
    <div class="premium-placeholders-page">

        <button class="btn btn-light bg-primary rounded-pill shadow-sm position-absolute"
            style="" @click="router.go(-1)">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <div class="premium-container">
            <div class="premium-header">

                <div class="d-flex align-items-center">
                    <h2 class="premium-title">Consultation
                        <span v-if="consultationName" class="consultation-name">- {{ consultationName }}</span>
                    </h2>
                </div>

                <div class="header-actions">
                    <button class="btn-premium-outline me-2" @click="openAddToWorkspaceModal">
                        <i class="fas fa-archive me-2"></i> Save to Workspace
                    </button>

                    <button class="btn-premium-outline" @click="goToAddConsulationPage">
                        <i :class="[isLastTab ? 'fas fa-save' : 'fas fa-arrow-right', 'me-2']"></i>
                        Save & Finish  
                    </button>
                </div>
            </div>

            <div class="premium-tabs">
                <div v-for="tab in tabs" :key="tab.id" :class="['premium-tab', { active: activeTab === tab.id }]"
                    @click="setActiveTab(tab.id)">
                    <i :class="[tab.icon, 'me-2']"></i>
                    <span>{{ tab.label }}</span>
                    <span v-if="hasTabData(tab.id)"  title="This tab has saved data">
                    </span>
                </div>
            </div>

            <div class="premium-card">
                <PatientInformation v-if="activeTab === 'PatientInfo'" ref="patientInformationRef"
                    :patient-id="patientId" :consultation-id="consulationId" :consultation-name="consultationName"
                    @update:consultation-name="handleConsultationNameUpdate" @patientFetched="updateInfo"
                    @update:consultation-data="updateConsultationData" />

                <OldConsulation v-if="activeTab === 'consultation'" :consultation-data="consultationData"
                    :patient-id="patientId" @update:consultation-data="updateConsultationData"  />

                <InformationHistoryTab v-if="activeTab === 'InformationHistory'" :consultation-data="consultationData"
                    :patient-id="patientId" @update:consultation-data="updateConsultationData" />

                <PlaceholderTab v-if="activeTab === 'placeholders'" ref="placeholderTabRef"
                    v-model:consultation-data="consultationData" :appointment-id="appointmentId" :doctorId="doctorId"
                    :patient-id="patientId" />

                <TemplatesTab v-if="activeTab === 'Templates'" :consultation-data="consultationData"
                    :selected-templates="selectedTemplates" :patient-id="patientId" :doctor-id="doctorId"
                    :appointment-id="appointmentId" @update:selected-templates="updateSelectedTemplates" />

                <PrescriptionTab v-if="activeTab === 'prescription'" :consultation-data="consultationData"
                    :patient-id="patientId" :appointmentId="appointmentId"
                    @update:consultation-data="updateConsultationData" />

                <OpinionRequest v-if="activeTab === 'OpinionRequest'" :appointmentId="appointmentId"
                    :doctorId="doctorId" :patient-id="patientId" />
            </div>
        </div>

        <AppointmentModal v-if="isModalVisible" :specialization_id="specialization_id" :doctorId="doctorId"
            :is-consulation="true" :appointmentId="appointmentId" :editMode="true" @close="closeModal" />

        <AddConsultationToWorkspaceModal
            v-if="showAddToWorkspaceModal"
            v-model="showAddToWorkspaceModal"
            :consultation-id-to-add="consulationId" :current-doctor-id="doctorId" @consultation-added="handleConsultationAddedToWorkspace"
        />
    </div>
</template>

<style scoped>
/* Ensure the .header-actions class is added for layout */
.header-actions {
    display: flex;
    gap: 1rem; /* Space between the two buttons */
    align-items: center;
}

/* Your existing styles */
.premium-placeholders-page {
    padding: 2rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f0 100%);
    min-height: 100vh;
    width: 100%;
}

.premium-container {
    max-width: 100%;
    padding: 2rem;
    margin: 0 auto;
}

.premium-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    width: 100%;
}

.premium-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.consultation-name {
    font-size: 1.2rem;
    font-weight: 500;
    color: #3a5bb1;
    font-style: italic;
}

.premium-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.btn-premium-outline {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: all 0.4s;
    background: transparent;
    border: 1px solid #3a5bb1;
    color: #3a5bb1;
}

.btn-premium-outline:hover {
    background: #3a5bb1;
    color: white;
}

.premium-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
}

.premium-tab {
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    background-color: #f1f5f9;
    color: #64748b;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: all 0.3s;
    white-space: nowrap;
}

.premium-tab:hover {
    background-color: #e2e8f0;
    color: #334155;
}

.premium-tab.active {
    background-color: #3a5bb1;
    color: white;
}

/* Tab data indicator style (adjust as needed) */
.tab-data-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #28a745; /* Green dot */
    border-radius: 50%;
    margin-left: 8px;
}

@media (max-width: 768px) {
    .premium-placeholders-page {
        padding: 1rem;
    }

    .premium-title {
        font-size: 1.5rem;
    }

    .premium-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    /* Adjust button alignment for smaller screens */
    .header-actions {
        width: 100%;
        justify-content: stretch; /* Stretch buttons to full width */
    }
    .header-actions .btn-premium-outline {
        flex: 1; /* Make buttons share space equally */
        justify-content: center;
    }
}
</style>