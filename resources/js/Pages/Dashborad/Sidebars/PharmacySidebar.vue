<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Gestion de Pharmacie',
    icon: '/storage/pharmacy-icon.png',
    color: '#',
    backRoute: '/home'
};

const isProductsOpen = ref(false);
const isInventoryOpen = ref(false);
const isStockagesOpen = ref(false);
const isMovementsOpen = ref(false);
const isReportsOpen = ref(false);

const toggleProducts = () => { isProductsOpen.value = !isProductsOpen.value; };
const toggleInventory = () => { isInventoryOpen.value = !isInventoryOpen.value; };
const toggleStockages = () => { isStockagesOpen.value = !isStockagesOpen.value; };
const toggleMovements = () => { isMovementsOpen.value = !isMovementsOpen.value; };
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
                <router-link to="/pharmacy/dashboard" active-class="active" class="nav-link">
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
                        <router-link to="/pharmacy/productList" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Liste des Produits</p>
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
                                    <p>Reservations</p>
                            </router-link>
                                        </li>
                    <li class="nav-item">
                        <router-link to="/pharmacy/stockages" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Stockages</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/pharmacy/requests" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Mes Demandes</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/pharmacy/approvals" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Approbations</p>
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