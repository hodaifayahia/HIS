<!-- Planning Sidebar -->
<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Planning',
    icon: 'fas fa-clipboard-check',
    color: '#14B8A6', // Teal-500
    backRoute: '/home'
};

// Permission check function
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
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/planning/dashboard" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-tachometer-alt tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Dashboard</p>
                </router-link>
            </li>

            <!-- Emergency Nursing Planning -->
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/planning/emergency-nursing" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-user-nurse tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Emergency Nursing</p>
                </router-link>
            </li>

            <!-- Emergency Doctor Planning -->
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/planning/emergency-doctor" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-user-md tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Emergency Doctor</p>
                </router-link>
            </li>

            <!-- Calendar View -->
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/planning/calendar" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-calendar-alt tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Calendar View</p>
                </router-link>
            </li>

            <!-- Reports -->
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/planning/reports" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-chart-bar tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Reports</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Enhanced active state */
.nav-link.active {
    background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.1)) !important;
    border-left: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Hover effect for nav items */
.nav-link:hover:not(.active) {
    background: rgba(255,255,255,0.08);
}

/* Icon container animation */
.nav-link:hover .tw-w-9 {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}
</style>
