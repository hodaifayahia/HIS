<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Gestion de Stock',
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
                <router-link to="/stock/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cubes"></i>
                    <p>Tableau de bord Stock</p>
                </router-link>
            </li>
            <!-- Products Management -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isProductsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleProducts">
                    <i class="nav-icon fas fa-box"></i>
                    <p>
                        Gestion des Produits
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isProductsOpen">
                    <li class="nav-item">
                        <router-link to="/stock/productList" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Liste des Produits</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/services" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Services</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/stockages" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Stockages</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/movements" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Mouvements de Stock</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- Supplier & Orders Management -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isSuppliersOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleSuppliers">
                    <i class="nav-icon fas fa-truck"></i>
                    <p>
                        Fournisseurs & Commandes
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isSuppliersOpen">
                    <li class="nav-item">
                        <router-link to="/stock/suppliers" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Liste des Fournisseurs</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/purchase-orders" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Commandes d'Achat</p>
                        </router-link>
                    </li>
                </ul>
            </li>
            
            <!-- Reports Section -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isReportsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleReports">
                    <i class="nav-icon fas fa-chart-pie"></i>
                    <p>
                        Rapports
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isReportsOpen">
                    <li class="nav-item">
                        <router-link to="/stock/reports/inventory-status" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Statut d'Inventaire</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/stock/reports/stock-value" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Valeur du Stock</p>
                        </router-link>
                    </li>
                </ul>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Any overrides or specific layout for this sidebar would go here */
</style>