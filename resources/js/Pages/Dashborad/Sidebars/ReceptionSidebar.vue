<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Reception Desk',
    icon: '/storage/reception-icon.png', // Example icon, or use 'fas fa-clipboard-list'
    color: '#', // Example color
    backRoute: '/home'
};

// Reactive states for controlling submenu visibility
const isPatientManagementOpen = ref(false);
const isFinancialActionsOpen = ref(false);

// Toggle functions for each submenu
const togglePatientManagement = () => { isPatientManagementOpen.value = !isPatientManagementOpen.value; };
const toggleFinancialActions = () => { isFinancialActionsOpen.value = !isFinancialActionsOpen.value; };

// Permission check function (can be adapted from the previous sidebar if needed for specific items)
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
                <router-link to="/reception/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Dashboard</p>
                </router-link>
            </li>

            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isPatientManagementOpen}">
                <a href="#" class="nav-link" @click.prevent="togglePatientManagement">
                    <i class="nav-icon fas fa-user-plus"></i>
                    <p>
                        Patient Check-in
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isPatientManagementOpen">
                    <li class="nav-item">
                        <router-link to="/reception/fiche-navette" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Fiche Navette</p> </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/reception/fiche-navette/custom-package" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Custom Package Name</p> </router-link>
                    </li>
                   
                </ul>
            </li>

            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isFinancialActionsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleFinancialActions">
                    <i class="nav-icon fas fa-wallet"></i>
                    <p>
                        Financial Actions
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isFinancialActionsOpen">
                    <li class="nav-item">
                        <router-link to="/reception/patient-balance" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Patient Balances</p> </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/reception/process-payments" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Process Card Payments</p> </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/reception/apply-discount" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Apply Discounts (Remise)</p> </router-link>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <router-link to="/reception/patient-flow" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>Patient Flow Monitor</p> </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>