<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Stock Management',
    icon: '/storage/stock-icon.png', // A new icon path for stock management
    color: '#',
    backRoute: '/home'
};

const isProductsOpen = ref(false);
const isSuppliersOpen = ref(false);
const isReportsOpen = ref(false);

const toggleProducts = () => { isProductsOpen.value = !isProductsOpen.value; };
const toggleSuppliers = () => { isSuppliersOpen.value = !isSuppliersOpen.value; };
const toggleReports = () => { isReportsOpen.value = !isReportsOpen.value; };

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
            <!-- Stock Dashboard Link -->
            <li class="nav-item">
                <router-link to="/stock/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cubes"></i>
                    <p>Stock Dashboard</p>
                </router-link>
            </li>
            <!-- Products Management -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isProductsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleProducts">
                    <i class="nav-icon fas fa-box"></i>
                    <p>
                        Product Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isProductsOpen">
                    <li class="nav-item">
                        <router-link to="/stock/productList" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Product List</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/services-stock" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Service Stock</p>
                        </router-link>
                    </li>
            <li class="nav-item">
                <router-link to="/stock/product-reserves" active-class="active" class="nav-link">
                    <i class="fas fa-bookmark nav-icon"></i>
                    <p>Product Reserves</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/stock/selling-settings" active-class="active" class="nav-link">
                    <i class="fas fa-percentage nav-icon"></i>
                    <p>Selling Settings</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/stock/stockages" active-class="active" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Storages</p>
                </router-link>
            </li>
                    <li class="nav-item">
                        <router-link to="/stock/requests" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>My Requests</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/approvals" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Approvals</p>
                        </router-link>
                    </li>

                </ul>
            </li>

            <!-- Purchasing Management -->
            

            <!-- Reports Section -->
            <!-- <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isReportsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleReports">
                    <i class="nav-icon fas fa-chart-pie"></i>
                    <p>
                        Reports
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isReportsOpen">
                    <li class="nav-item">
                        <router-link to="/stock/reports/inventory-status" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Inventory Status</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/reports/stock-value" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Stock Value</p>
                        </router-link>
                    </li>
                </ul>
            </li> -->
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Any overrides or specific layout for this sidebar would go here */
</style>