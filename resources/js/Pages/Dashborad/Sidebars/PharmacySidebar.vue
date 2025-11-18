<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Pharmacy Management',
    icon: '/storage/pharmacy-icon.png',
    color: '#',
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
            <!-- Stock Dashboard Link -->
            <li class="nav-item">
                <router-link to="/pharmacy/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cubes"></i>
                    <p>Stock Dashboard</p>
                </router-link>
            </li>
            <!-- Products Management -->
            <li class="nav-item">
                <router-link to="/pharmacy/productList" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-box"></i>
                    <p>Product List</p>
                </router-link>
            </li>
            
            
            <li class="nav-item">
                <router-link to="/pharmacy/services" active-class="active" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Services</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/pharmacy/product-reserves" active-class="active" class="nav-link">
                    <i class="fas fa-bookmark nav-icon"></i>
                    <p>Product Reserves</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/pharmacy/service-groups" active-class="active" class="nav-link">
                    <i class="fas fa-percentage nav-icon"></i>
                    <p>Selling Settings</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/pharmacy/stockages" active-class="active" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Storages</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/pharmacy/requests" active-class="active" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>My Requests</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/pharmacy/approvals" active-class="active" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Approvals</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Any overrides or specific layout for this sidebar would go here */
</style>