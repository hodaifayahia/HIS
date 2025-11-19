<script setup>
import { useRouter, useRoute } from 'vue-router';
import { ref, defineProps, onMounted, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';

// Import the BaseSidebar component
import BaseSidebar from './BaseSidebar.vue';

const toaster = useToastr();

// Define props
const props = defineProps({
    user: {
        type: Object,
        required: true,
        default: () => ({})
    }
});

// Logout Function
const logout = async () => {
    try {
        await axios.post('/logout');
        window.location.href = '/login';
    } catch (error) {
        console.error('Error logging out:', error);
        toaster.error('Failed to log out.');
    }
};
</script>

<template>
    <BaseSidebar
        :user="user" 
        appName="Bank Management Panel" 
        appIcon="/storage/bank-icon.png" 
        appColor="#" 
        backRoute="/home"
        backLabel="Back to Dashboard"
    >
        <template #navigation>
            <li class="nav-item">
                <router-link to="/banking/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>Dashboard</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/banking/accounts" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-university"></i>
                    <p>Bank Accounts</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/banking/transactions/all" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-exchange-alt"></i>
                    <p>Transactions</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/banking/banks" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-paper-plane"></i>
                    <p>Banks</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/bank/reports" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-chart-bar"></i>
                    <p>Reports</p>
                </router-link>
            </li>

            <li class="nav-item">
                <router-link to="/bank/settings" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>Settings</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
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