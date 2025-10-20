<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Caisse Management',
    icon: '/storage/caisse-icon.png',
    color: '#253237',
    backRoute: '/home'
};

const isCaisseOpen = ref(false);
const isBankOpen = ref(false);

const toggleCaisse = () => { isCaisseOpen.value = !isCaisseOpen.value; };
const toggleBank = () => { isBankOpen.value = !isBankOpen.value; };

const hasPermission = (requiredRoles) => {
    if (!user.value || !user.value.role) return false;
    const userRole = String(user.value.role).toLowerCase();
    const rolesArray = Array.isArray(requiredRoles) ? requiredRoles.map(r => String(r).toLowerCase()) : [String(requiredRoles).toLowerCase()];
    return rolesArray.includes(userRole);
};
</script>

<template>
    <BaseSidebar
        :user="user"
        :app-name="appDetails.name"
        :app-icon="appDetails.icon"
        :app-color="appDetails.color"
        :back-route="appDetails.backRoute"
    >
        <template #navigation>
            <li class="nav-item">
                <router-link to="/caisse/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-calculator"></i>
                    <p>Caisse Dashboard</p>
                </router-link>
            </li>

             <li class="nav-item">
                <router-link to="/caisse/caisse-session" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-injured"></i>
                    <p>Patient Overview</p>
                </router-link>
            </li>
             <li class="nav-item">
                <router-link to="/caisse/payment-approvals" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-injured"></i>
                    <p>Pending approvals</p>
                </router-link>
            </li>
          
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>