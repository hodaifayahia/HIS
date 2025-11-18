<script setup>
import { computed } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'External Prescriptions',
    icon: '/storage/prescription-icon.png',
    color: '#8B5CF6',
    backRoute: '/home'
};

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
            <!-- Dashboard -->
            <li class="nav-item">
                <router-link to="/external-prescriptions/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>Dashboard</p>
                </router-link>
            </li>

            <!-- Prescriptions List -->
            <li class="nav-item">
                <router-link to="/external-prescriptions/list" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-file-prescription"></i>
                    <p>Prescriptions</p>
                </router-link>
            </li>

        
            <!-- Reports -->
            <li class="nav-item">
                <router-link to="/external-prescriptions/reports" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-chart-bar"></i>
                    <p>Reports</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Any overrides or specific layout for this sidebar would go here */
</style>
