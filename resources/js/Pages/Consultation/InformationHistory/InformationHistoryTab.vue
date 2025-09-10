<script setup>
import { ref } from 'vue';
import AllergieHistroy from './AllergieHistroy.vue';
import ChronicDiseasesHistroy from './ChronicDiseasesHistroy.vue';
import FamilyHistory from './FamilyHistory.vue';
import SurgicalHistroy from './SurgicalHistroy.vue';

// Props definition from the original component
const props = defineProps({
    consultationData: {
        type: Object,
        default: () => ({})
    },
    patientId: {
        type: [Number, String],
        required: true
    }
});

// State to manage the currently active tab. 'allergies' is the default.
const activeTab = ref('allergies');

// An array to define the tabs, making the template cleaner and easier to manage.
const tabs = [
    { id: 'allergies', name: 'Allergies', icon: 'fas fa-exclamation-triangle', component: AllergieHistroy },
    { id: 'chronic', name: 'Chronic Diseases', icon: 'fas fa-heartbeat', component: ChronicDiseasesHistroy },
    { id: 'surgical', name: 'Surgical History', icon: 'fas fa-user-md', component: SurgicalHistroy },
    { id: 'family', name: 'Family History', icon: 'fas fa-users', component: FamilyHistory },
];

/**
 * Sets the active tab.
 * @param {string} tabId - The ID of the tab to make active.
 */
const setActiveTab = (tabId) => {
    activeTab.value = tabId;
};
</script>

<template>
    <div class="history-container">
        <div class="history-header">
            <h1 class="main-title">Patient Medical History</h1>
            <p class="subtitle">Comprehensive medical background information</p>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs-container">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="setActiveTab(tab.id)"
                :class="['tab-button', { 'active-tab': activeTab === tab.id }]"
            >
                <i :class="tab.icon"></i>
                <span>{{ tab.name }}</span>
            </button>
        </div>

        <!-- Tab Content Area -->
        <div class="tab-content-area">
            <div v-for="tab in tabs" :key="tab.id">
                <div v-if="activeTab === tab.id" class="history-section">
                    <div class="section-header" :class="`${tab.id}-bg`">
                        <div class="section-icon" :class="tab.id">
                            <i :class="tab.icon"></i>
                        </div>
                        <div class="section-title">
                            <h2>{{ tab.name }}</h2>
                            <p>
                                <span v-if="tab.id === 'allergies'">Known allergies and reactions</span>
                                <span v-if="tab.id === 'chronic'">Long-term medical conditions</span>
                                <span v-if="tab.id === 'surgical'">Previous surgeries and procedures</span>
                                <span v-if="tab.id === 'family'">Hereditary conditions and family background</span>
                            </p>
                        </div>
                    </div>
                    <div class="section-content">
                        <!-- Dynamically render the component for the active tab -->
                        <component :is="tab.component" :patientId="props.patientId" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Main container styling */
.history-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    background-color: #f8fafc;
    min-height: 100vh;
}

/* Header styling */
.history-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
}

.main-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #3b82f6, #1e40af);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.subtitle {
    font-size: 1.1rem;
    color: #64748b;
    margin: 0;
}

/* Tab navigation styling */
.tabs-container {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap; /* Allows tabs to wrap on smaller screens */
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
    color: #475569;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 9999px; /* Pill shape */
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.tab-button:hover {
    background-color: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
}

/* Active tab styling */
.active-tab {
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1);
}
.active-tab, .active-tab:hover {
    background: linear-gradient(135deg, #3b82f6, #1e40af);
}


/* Tab Content Styling */
.tab-content-area {
    position: relative;
}

.history-section {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    overflow: hidden; /* Ensures the header gradient doesn't bleed out */
    animation: fadeIn 0.5s ease-out; /* Animation for content switching */
}

.section-header {
    display: flex;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.section-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
    font-size: 1.25rem;
    color: white;
    flex-shrink: 0;
}

/* Icon background colors */
.section-icon.allergies { background: linear-gradient(135deg, #f59e0b, #d97706); }
.section-icon.chronic { background: linear-gradient(135deg, #ef4444, #dc2626); }
.section-icon.surgical { background: linear-gradient(135deg, #10b981, #059669); }
.section-icon.family { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.section-title h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.section-title p {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

.section-content {
    padding: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .history-container {
        padding: 1.5rem;
    }
    .main-title {
        font-size: 2rem;
    }
    .tabs-container {
        gap: 0.5rem;
    }
    .tab-button {
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
    }
    .section-header {
      padding: 1rem 1.5rem;
    }
    .section-content {
      padding: 1.5rem;
    }
}

/* Animation for content appearance */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
