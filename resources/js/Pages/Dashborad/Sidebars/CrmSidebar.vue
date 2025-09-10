<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);


const appDetails = {
    name: 'CRM - Gestion de la Relation Patient & Partenaire',
    icon: '/storage/config-icon.png',
    color: '#',
    backRoute: '/home'
};

const isB2COpen = ref(false);
const isB2BOpen = ref(false);
const isTicketingOpen = ref(false);

const toggleB2C = () => { isB2COpen.value = !isB2COpen.value; };
const toggleB2B = () => { isB2BOpen.value = !isB2BOpen.value; };
const toggleTicketing = () => { isTicketingOpen.value = !isTicketingOpen.value; };

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
            <!-- CRM Dashboard Link -->
            <li class="nav-item">
                <router-link to="/crm/dashboard" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>Tableau de bord CRM</p>
                </router-link>
            </li>
            <!-- B2C CRM: Patient Engagement & Loyalty -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isB2COpen }">
                <a href="#" class="nav-link" @click.prevent="toggleB2C">
                    <i class="nav-icon fas fa-user-friends"></i>
                    <p>
                        CRM Patient (B2C)
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isB2COpen">
                    <li class="nav-item">
                        <router-link to="/crm/patients" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Profil Patient 360°</p>
                        </router-link>
                    </li>
                    
                 
                    <li class="nav-item">
                        <router-link to="/crm/campaigns" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Campagnes Santé & Marketing</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- Ticketing: Feedback & Satisfaction -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isTicketingOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleTicketing">
                    <i class="nav-icon fas fa-ticket-alt"></i>
                    <p>
                        Retours & Satisfaction
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isTicketingOpen">
                    <li class="nav-item">
                        <router-link to="/crm/tickets" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Gestion des Tickets</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- B2B CRM: Partner Management -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isB2BOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleB2B">
                    <i class="nav-icon fas fa-briefcase"></i>
                    <p>
                        CRM Partenaire (B2B)
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isB2BOpen">
                    <li class="nav-item">
                        <router-link to="/crm/partenaires" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Comptes & Contacts Partenaires</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/crm/opportunites" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pipeline Opportunités</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/crm/medecins-referents" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Médecins Référents</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/crm/referrals" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Suivi des Références</p>
                        </router-link>
                    </li>
                </ul>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>