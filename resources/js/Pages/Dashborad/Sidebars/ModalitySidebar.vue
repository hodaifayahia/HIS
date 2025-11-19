<script setup>
import { computed } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Modality',
    icon: '/storage/modality-icon.png',
    color: '#', // Green color
    backRoute: '/home'
};
</script>

<template>
    <BaseSidebar :user="authStore.user" :app-name="appDetails.name" :app-icon="appDetails.icon" :app-color="appDetails.color" :back-route="appDetails.backRoute">
        <template #navigation>
            <li class="nav-item">
                <router-link to="/modality" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Dashboard</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/modality/modality-appointment" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-calendar-check"></i>
                    <p>Modality Appointment</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/modality/modality-appointment/forceAppointment" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-shield"></i>
                    <p>Modality Force Appointment</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Keep styling minimal; BaseSidebar provides layout */
</style>