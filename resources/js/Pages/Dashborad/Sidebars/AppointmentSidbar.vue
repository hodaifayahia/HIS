<script setup>
import { useRouter, useRoute } from 'vue-router';
import { ref, defineProps, onMounted, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster'; // Adjust path based on ReceptionistSidebar's location

// Import the BaseSidebar component which provides the common UI structure
import BaseSidebar from './BaseSidebar.vue'; // Adjust path if necessary

const toaster = useToastr();

// Define props (user object will be passed from the parent MainLayout)
const props = defineProps({
    user: {
        type: Object,
        required: true,
        default: () => ({})
    }
});

// Logout Function (kept here as it's a common sidebar action)
const logout = async () => {
    try {
        await axios.post('/logout');
        window.location.href = '/login'; // Redirect after logout
    } catch (error) {
        console.error('Error logging out:', error);
        toaster.error('Failed to log out.'); // Show a toast notification on error
    }
};
</script>

<template>
    <BaseSidebar
        :user="user" 
        appName="Receptionist Panel" 
        appIcon="/storage/receptionist.png" 
        appColor="#20c997" 
        backRoute="/home"
    >
        <template #navigation>
            <li class="nav-item">
                <router-link to="/admin/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>Dashboard</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/admin/calender" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <p>Calendar</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/admin/doctors" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-users"></i>
                    <p>Doctors</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/admin/appointments/specialization" active-class="active" class="nav-link d-flex align-items-center">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <p>Appointments</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/admin/appointments/pending" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-tasks"></i>
                    <p>Pending List</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/admin/waitlist" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-clock"></i>
                    <p>Waitlist</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/admin/patient" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-injured"></i>
                    <p>Patient</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/admin/settings" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>Settings</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* These styles are specifically for elements within this ReceptionistSidebar component.
   General sidebar styling (e.g., fixed position, overall width) should ideally be handled
   by your BaseSidebar component or global CSS/AdminLTE. */
.animated-badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.1);
    }

    100% {
        transform: scale(1);
    }
}
</style>