<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Purchasing Management',
    icon: '/storage/purchasing-icon.png',
    color: '#',
    backRoute: '/home'
};

const isSupplierManagementOpen = ref(false);
const isOrderingProductsOpen = ref(false);
const isInventoryManagementOpen = ref(false);
const isApprovalManagementOpen = ref(false);

const toggleSupplierManagement = () => {
    isSupplierManagementOpen.value = !isSupplierManagementOpen.value;
};

const toggleOrderingProducts = () => {
    isOrderingProductsOpen.value = !isOrderingProductsOpen.value;
};

const toggleInventoryManagement = () => {
    isInventoryManagementOpen.value = !isInventoryManagementOpen.value;
};

const toggleApprovalManagement = () => {
    isApprovalManagementOpen.value = !isApprovalManagementOpen.value;
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
                </ul>
            </li>
            <li class="nav-item">
                <router-link to="/purchasing/products" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-shopping-bag"></i>
                    <p>All Products (Stock & Pharmacy)</p>
                </router-link>
            </li>
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isOrderingProductsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleOrderingProducts">
                    <i class="nav-icon fas fa-truck"></i>
                    <p>
                        ordering Products 
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isOrderingProductsOpen">
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
                    <li class="nav-item">
                        <router-link to="/purchasing/bon-receptions" class="nav-link">
                            <i class="far fa-box nav-icon"></i>
                            <p>Bon Reception List</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isInventoryManagementOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleInventoryManagement">
                    <i class="nav-icon fas fa-warehouse"></i>
                    <p>
                        Inventory Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isInventoryManagementOpen">
                    <li class="nav-item">
                        <router-link to="/purchasing/bon-entrees" class="nav-link">
                            <i class="far fa-box-open nav-icon"></i>
                            <p>Bon Entrée List</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/purchasing/bon-retours" class="nav-link">
                            <i class="far fa-undo nav-icon"></i>
                            <p>Bon Retour List</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/purchasing/consignments" class="nav-link">
                            <i class="nav-icon fas fa-handshake"></i>
                            <p>Consignments</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isApprovalManagementOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleApprovalManagement">
                    <i class="nav-icon fas fa-user-check"></i>
                    <p>
                        Approval Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isApprovalManagementOpen">
                    <li class="nav-item">
                        <router-link to="/purchasing/approval-persons" class="nav-link">
                            <i class="far fa-users nav-icon"></i>
                            <p>Approval Persons</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/purchasing/pending-approvals" class="nav-link">
                            <i class="far fa-clock nav-icon"></i>
                            <p>Pending Approvals</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/purchasing/product-approval-settings" class="nav-link">
                            <i class="far fa-cog nav-icon"></i>
                            <p>Product Approval Settings</p>
                        </router-link>
                    </li>
                </ul>
            </li>

        </template>
    </BaseSidebar>
</template>

<style scoped>
</style>