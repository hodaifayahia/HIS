<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Purchasing Management',
    icon: '/storage/purchasing-icon.png',
    color: '#28a745',
    backRoute: '/home'
};

const isSupplierManagementOpen = ref(false);

const toggleSupplierManagement = () => {
    isSupplierManagementOpen.value = !isSupplierManagementOpen.value;
};

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
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isSupplierManagementOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleSupplierManagement">
                    <i class="nav-icon fas fa-truck"></i>
                    <p>
                        Supplier Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isSupplierManagementOpen">
                    <li class="nav-item">
                        <router-link to="/purchasing/fournisseurs" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>All Suppliers</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/purchasing/fournisseurs/create" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Add Supplier</p>
                        </router-link>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isSupplierManagementOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleSupplierManagement">
                    <i class="nav-icon fas fa-truck"></i>
                    <p>
                        ordering Products 
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isSupplierManagementOpen">
                    <li class="nav-item">
                        <router-link to="/purchasing/service-demands" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Order</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/purchasing/facture-proforma-list" class="nav-link">
                            <i class="far fa-file-invoice nav-icon"></i>
                            <p>Facture Proforma List</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/purchasing/bon-commend-list" class="nav-link">
                            <i class="far fa-shopping-cart nav-icon"></i>
                            <p>Bon Commend List</p>
                        </router-link>
                    </li>
                </ul>
            </li>

        </template>
    </BaseSidebar>
</template>

<style scoped>
</style>