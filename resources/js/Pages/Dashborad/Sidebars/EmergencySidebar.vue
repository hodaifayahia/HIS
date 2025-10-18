<!-- Enhanced Emergency Sidebar -->
<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Emergency Desk',
    icon: '/storage/emergency-icon.png',
    color: '#2563eb', // Blue-600
    backRoute: '/home'
};

// Reactive states for controlling submenu visibility
const isPatientManagementOpen = ref(false);
const isFinancialActionsOpen = ref(false);

// Toggle functions for each submenu
const togglePatientManagement = () => { 
    isPatientManagementOpen.value = !isPatientManagementOpen.value; 
};
const toggleFinancialActions = () => { 
    isFinancialActionsOpen.value = !isFinancialActionsOpen.value; 
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
                    to="/emergency/dashboard" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-tachometer-alt tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Dashboard</p>
                </router-link>
            </li>

            <!-- Patient Check-in Section -->
            <li class="nav-item has-treeview tw-mb-1" :class="{ 'menu-is-opening menu-open': isPatientManagementOpen}">
                <a 
                    href="#" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                    @click.prevent="togglePatientManagement"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-user-plus tw-text-white"></i>
                    </div>
                    <p class="tw-flex-1 tw-font-medium">
                        Patient Check-in
                    </p>
                    <i 
                        class="fas fa-chevron-right tw-text-xs tw-transition-transform tw-duration-300" 
                        :class="{ 'tw-rotate-90': isPatientManagementOpen }"
                    ></i>
                </a>

                <transition name="slide">
                    <ul class="nav nav-treeview tw-ml-6 tw-mt-1" v-show="isPatientManagementOpen">
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/emergency/fiche-navette" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Fiche Navette</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/emergency/fiche-navette/custom-package" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Custom Package Name</p>
                            </router-link>
                        </li>
                    </ul>
                </transition>
            </li>

            <!-- Doctor Emergency -->
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/emergency/doctor-planning" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-user-md tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Doctor Emergency</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Smooth slide animation for submenus */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-10px);
}

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

/* Submenu items styling */
.nav-treeview .nav-link {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.nav-treeview .nav-link.active {
    background: rgba(255,255,255,0.1);
    border-left: 2px solid rgba(255,255,255,0.8);
}
</style>