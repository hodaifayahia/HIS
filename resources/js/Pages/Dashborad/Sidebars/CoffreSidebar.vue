<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Coffre - Secure Document & Information Management',
    icon: '/storage/coffre-icon.png',
    color: '#',
    backRoute: '/home'
};

const isDocumentsOpen = ref(false);
const isSecretsOpen = ref(false);
const isAccessLogsOpen = ref(false);

const toggleDocuments = () => { isDocumentsOpen.value = !isDocumentsOpen.value; };
const toggleSecrets = () => { isSecretsOpen.value = !isSecretsOpen.value; };
const toggleAccessLogs = () => { isAccessLogsOpen.value = !isAccessLogsOpen.value; };

const hasPermission = (requiredRoles) => {
    if (!user.value || !user.value.role) return false;
    const userRole = String(user.value.role).toLowerCase();
    const rolesArray = Array.isArray(requiredRoles) ? requiredRoles.map(r => String(r).toLowerCase()) : [String(requiredRoles).toLowerCase()];
    return rolesArray.includes(userRole);
};
</script>

<template>
    <BaseSidebar
        :user="user"
        :app-name="appDetails.name"
        :app-icon="appDetails.icon"
        :app-color="appDetails.color"
        :back-route="appDetails.backRoute"
    >
        <template #navigation>
            <li class="nav-item">
                <router-link to="/coffre/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-lock"></i>
                    <p>Coffre Dashboard</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/coffre/transactions-dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-lock"></i>
                    <p>Coffre Management</p>
                </router-link>
            </li>

            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isDocumentsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleDocuments">
                    <i class="nav-icon fas fa-file-alt"></i>
                    <p>
                        Corffre 
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isDocumentsOpen">
                    <li class="nav-item">
                        <router-link to="/coffre/list" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Corffre List </p>
                        </router-link>
                    </li>
                    
                </ul>
            </li>
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isSecretsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleSecrets">
                    <i class="nav-icon fas fa-file-alt"></i>
                    <p>
                        Caisse 
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isSecretsOpen">
                    <li class="nav-item">
                        <router-link to="/coffre/caisse" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Caisse List </p>
                        </router-link>
                    </li>
                    
                </ul>
            </li>


            <li  class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isAccessLogsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleAccessLogs">
                    <i class="nav-icon fas fa-history"></i>
                    <p>
                        Bank
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isAccessLogsOpen">
                    <li class="nav-item">
                        <router-link to="/coffre/access-logs" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Bank List </p>
                        </router-link>
                    </li>
                    <!-- <li class="nav-item">
                        <router-link to="/coffre/security-audit" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Security Audit</p>
                        </router-link>
                    </li> -->
                </ul>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>