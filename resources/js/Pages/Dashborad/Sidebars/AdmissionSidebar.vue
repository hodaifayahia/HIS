<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Admissions',
    icon: '/storage/admission-icon.png',
    color: '#',
    backRoute: '/home'
};

// Reactive states for controlling submenu visibility
const isAdmissionsOpen = ref(false);

// Toggle function
const toggleAdmissions = () => { isAdmissionsOpen.value = !isAdmissionsOpen.value; };

// Permission check function
const hasPermission = (requiredRoles) => {
    if (!user.value || !user.value.role) return false;
    const userRole = user.value.role.toLowerCase();
    const rolesArray = Array.isArray(requiredRoles) ? requiredRoles.map(r => r.toLowerCase()) : [requiredRoles.toLowerCase()];
    return rolesArray.includes(userRole);
};
</script>

<template>
    <BaseSidebar :user="authStore.user" :app-name="appDetails.name" :app-icon="appDetails.icon"
        :app-color="appDetails.color" :back-route="appDetails.backRoute">
        <template #navigation>
            <li class="nav-item">
                <router-link to="/admissions" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-hospital-user"></i>
                    <p>All Admissions</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
</style>
