<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'infrastructure & Space Management', // Changed name based on the document
    icon: 'fas fa-hospital-alt', // A more suitable icon for hospital management
    color: '#', // Example color
    backRoute: '/home'
};

// Refactored state for clarity, aligning with document sections
const isInfrastructureHierarchyOpen = ref(false);
const isInpatientManagementOpen = ref(false);
const isConsultationManagementOpen = ref(false);
const isTreatmentProcedureManagementOpen = ref(false);
const isAdvancedFeaturesOpen = ref(false); // For AI/IoT related items

// Toggle functions
const toggleInfrastructureHierarchy = () => { isInfrastructureHierarchyOpen.value = !isInfrastructureHierarchyOpen.value; };
const toggleInpatientManagement = () => { isInpatientManagementOpen.value = !isInpatientManagementOpen.value; };
const toggleConsultationManagement = () => { isConsultationManagementOpen.value = !isConsultationManagementOpen.value; };
const toggleTreatmentProcedureManagement = () => { isTreatmentProcedureManagementOpen.value = !isTreatmentProcedureManagementOpen.value; };
const toggleAdvancedFeatures = () => { isAdvancedFeaturesOpen.value = !isAdvancedFeaturesOpen.value; };

const hasPermission = (requiredRoles) => {
    if (!user.value || !user.value.role) return false;
    const userRole = user.value.role.toLowerCase();
    const rolesArray = Array.isArray(requiredRoles) ? requiredRoles.map(r => r.toLowerCase()) : [requiredRoles.toLowerCase()];
    return rolesArray.includes(userRole);
};
</script>

<template>
    <BaseSidebar
        :user="authStore.user"
        :app-name="appDetails.name"
        :app-icon="appDetails.icon"
        :app-color="appDetails.color"
        :back-route="appDetails.backRoute"
    >
        <template #navigation>
            <li class="nav-item">
                <router-link to="/infrastructure/infrastructure-dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Dashboard</p>
                </router-link>
            </li>

            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isInfrastructureHierarchyOpen}">
                <a href="#" class="nav-link" @click.prevent="toggleInfrastructureHierarchy">
                    <i class="nav-icon fas fa-sitemap"></i>
                    <p>
                        Facility Structure
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isInfrastructureHierarchyOpen">
                    <li class="nav-item">
                        <router-link to="/infrastructure/structure/pavillons" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pavillons (Wings)</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/structure/room-types" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Rooms Types</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isInpatientManagementOpen}">
                <a href="#" class="nav-link" @click.prevent="toggleInpatientManagement">
                    <i class="nav-icon fas fa-bed"></i>
                    <p>
                        Inpatient Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isInpatientManagementOpen">
                    <li class="nav-item">
                        <router-link to="/infrastructure/inpatient/rooms" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Hospitalization Rooms</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/inpatient/beds" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Bed Management</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/inpatient/admission-transfer" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Admission & Transfers</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/inpatient/discharge-housekeeping" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Discharge & Housekeeping</p>
                        </router-link>
                    </li>
                </ul>
            </li> -->

            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isConsultationManagementOpen}">
                <a href="#" class="nav-link" @click.prevent="toggleConsultationManagement">
                    <i class="nav-icon fas fa-user-md"></i>
                    <p>
                         Areas
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isConsultationManagementOpen">
                    <li class="nav-item">
                        <router-link to="/infrastructure/Areas/pavilionCards" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Rooms</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/Areas/bebs" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Bads</p>
                        </router-link>
                    </li>
                </ul>
            </li>
                
            <!-- <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isTreatmentProcedureManagementOpen}">
                <a href="#" class="nav-link" @click.prevent="toggleTreatmentProcedureManagement">
                    <i class="nav-icon fas fa-notes-medical"></i>
                    <p>
                        Treatment & Procedure Areas
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isTreatmentProcedureManagementOpen">
                    <li class="nav-item">
                        <router-link to="/infrastructure/treatment/rooms" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Treatment Rooms</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/treatment/radiology-rooms" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Radiology Rooms</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/treatment/cath-labs" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Cath Labs</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/treatment/operating-theaters" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Operating Theaters</p>
                        </router-link>
                    </li>
                </ul>
            </li> -->

            
<!-- 
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isAdvancedFeaturesOpen}">
                <a href="#" class="nav-link" @click.prevent="toggleAdvancedFeatures">
                    <i class="nav-icon fas fa-brain"></i>
                    <p>
                        Smart Clinic Features
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isAdvancedFeaturesOpen">
                    <li class="nav-item">
                        <router-link to="/infrastructure/smart-clinic/digital-twin" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Digital Floor Plan (Twin)</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/smart-clinic/ai-patient-placement" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>AI Patient Placement</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/smart-clinic/predictive-discharge" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Predictive Discharge</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/infrastructure/smart-clinic/iot-integration" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>IoT Integration</p>
                        </router-link>
                    </li>
                </ul>
            </li> -->

            

            <li class="nav-item">
                <router-link to="/infrastructure/settings" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cog"></i>
                    <p>Module Settings</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>