<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Clinic Configuration',
    icon: 'fas fa-solid fa-gear', // Using a Font Awesome icon directly
    color: '#', // Example color
    backRoute: '/home'
};

// State for managing dropdowns
const isUserAccessManagementOpen = ref(false);
const ismangerManagementOpen = ref(false); // New
const isFacturationBillingOpen = ref(false);   // New
const isClinicServicesResourcesOpen = ref(false); // New
const isSystemAdministrationOpen = ref(false); // New

// Toggle functions for new dropdowns
const toggleUserAccessManagement = () => { isUserAccessManagementOpen.value = !isUserAccessManagementOpen.value; };
const togglemangerManagement = () => { ismangerManagementOpen.value = !ismangerManagementOpen.value; };
const toggleFacturationBilling = () => { isFacturationBillingOpen.value = !isFacturationBillingOpen.value; };
const toggleClinicServicesResources = () => { isClinicServicesResourcesOpen.value = !isClinicServicesResourcesOpen.value; };
const toggleSystemAdministration = () => { isSystemAdministrationOpen.value = !isSystemAdministrationOpen.value; };


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
                <router-link :to="{ name: 'Inventory-audit.list' }" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-clipboard-list"></i>
                    <p>Inventory Audits</p>
                </router-link>
            </li>
            
      
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>
