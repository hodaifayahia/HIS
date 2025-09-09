<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Caisse Management',
    icon: '/storage/caisse-icon.png',
    color: '#253237', // A custom dark color for the caisse app
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
            <li class="tw-nav-item">
                <router-link to="/caisse/dashboard" active-class="active" class="tw-nav-link tw-group tw-flex tw-items-center tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-200 hover:tw-bg-white hover:tw-bg-opacity-20">
                    <i class="nav-icon fas fa-calculator tw-mr-3 tw-text-lg tw-text-gray-200 group-hover:tw-text-white"></i>
                    <p class="tw-text-white tw-font-medium group-hover:tw-text-white">Caisse Dashboard</p>
                </router-link>
            </li>
            <li class="tw-nav-item">
                <router-link to="/caisse/caisse-session" active-class="active" class="tw-nav-link tw-group tw-flex tw-items-center tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-200 hover:tw-bg-white hover:tw-bg-opacity-20">
                    <i class="nav-icon fas fa-user-injured tw-mr-3 tw-text-lg tw-text-gray-200 group-hover:tw-text-white"></i>
                    <p class="tw-text-white tw-font-medium group-hover:tw-text-white">Patients</p>
                </router-link>
            </li>
            <li class="tw-nav-item">
                <router-link to="/caisse/payment-approvals" active-class="active" class="tw-nav-link tw-group tw-flex tw-items-center tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-200 hover:tw-bg-white hover:tw-bg-opacity-20">
                    <i class="nav-icon fas fa-user-injured tw-mr-3 tw-text-lg tw-text-gray-200 group-hover:tw-text-white"></i>
                    <p class="tw-text-white tw-font-medium group-hover:tw-text-white">Pending approvals</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Add `tw-` prefix to AdminLTE classes to avoid conflicts */
.tw-nav-item {
  position: relative;
  display: block;
}

.tw-nav-link.active {
  @apply tw-bg-white tw-bg-opacity-25;
}

.tw-nav-link p {
  @apply tw-text-white;
}
</style>