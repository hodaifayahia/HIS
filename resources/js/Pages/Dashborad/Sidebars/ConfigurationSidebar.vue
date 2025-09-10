<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Clinic Configuration',
    icon: '/storage/config-icon.png', // Or use an actual font-awesome class if it's dynamic, e.g., 'fas fa-solid fa-gear'
    color: '#', // Example color
    backRoute: '/home'
};

// Reactive states for controlling submenu visibility
const isUserAccessManagementOpen = ref(false);
const isModalityResourceOpen = ref(false);
const isPrestationConfigOpen = ref(false);
const isRemiseConfigOpen = ref(false);

// Toggle functions for each submenu
const toggleUserAccessManagement = () => { isUserAccessManagementOpen.value = !isUserAccessManagementOpen.value; };
const toggleModalityResource = () => { isModalityResourceOpen.value = !isModalityResourceOpen.value; };
const togglePrestationConfig = () => { isPrestationConfigOpen.value = !isPrestationConfigOpen.value; };
const toggleRemiseConfig = () => { isRemiseConfigOpen.value = !isRemiseConfigOpen.value; };

// Permission check function (retained from original)
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
            <!-- User & Access Management Section -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isUserAccessManagementOpen}">
                <a href="#" class="nav-link" @click.prevent="toggleUserAccessManagement">
                    <i class="nav-icon fas fa-user-shield"></i> <!-- Changed from fas fa-users-cog -->
                    <p>
                        User & Access Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isUserAccessManagementOpen">
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/users" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Users</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/roles" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Roles</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/permissions" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Permissions</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/specializations" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Specializations</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/services" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Services</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- Modality & Resource Management Section -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isModalityResourceOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleModalityResource">
                    <i class="nav-icon fas fa-cube"></i> <!-- Changed from fas fa-cogs -->
                    <p>
                        Modality & Resource Mgmt
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isModalityResourceOpen">
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/modalities" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Modalities List</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/modalities-types" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Modality Types</p>
                        </router-link>
                    </li>
                  
                </ul>
            </li>

            <!-- Prestation Configuration Section -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isPrestationConfigOpen }">
                <a href="#" class="nav-link" @click.prevent="togglePrestationConfig">
                    <i class="nav-icon fas fa-tags"></i> <!-- Changed from fas fa-boxes -->
                    <p>
                        Prestation Config
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
              <ul class="nav nav-treeview" v-show="isPrestationConfigOpen">
    <li class="nav-item">
        <router-link to="/admin/configuration/prestations" active-class="active" class="nav-link">
            <i class="fas fa-hand-holding-medical nav-icon"></i>
            <p>Prestation Catalog</p>
        </router-link>
    </li>
    <li class="nav-item">
        <router-link to="/admin/configuration/prestations-packages" active-class="active" class="nav-link">
            <i class="fas fa-box-open nav-icon"></i>
            <p>Prestations Packages</p>
        </router-link>
    </li>
</ul>
            </li>

            <!-- Remise Management Section -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isRemiseConfigOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleRemiseConfig">
                    <i class="nav-icon fas fa-percent"></i> <!-- Changed from fas fa-boxes -->
                    <p>
                        Remise Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                
                <ul class="nav nav-treeview" v-show="isRemiseConfigOpen">   
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/remise-management" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Remise List</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/configuration/payment-methods" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Payment Methods</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- System Settings Section -->
            <li class="nav-item" >
                <router-link to="/admin/configuration/system-settings" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>System Settings</p>
                </router-link>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>
