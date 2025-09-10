<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from './BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Patient Portal',
    icon: '/storage/config-icon.png',
    color: '#4F46E5',
    backRoute: '/home'
};

const isPatientOpen = ref(false);
const isAppointmentsOpen = ref(false);
const isBillingOpen = ref(false);

const togglePatient = () => { isPatientOpen.value = !isPatientOpen.value; };
const toggleAppointments = () => { isAppointmentsOpen.value = !isAppointmentsOpen.value; };
const toggleBilling = () => { isBillingOpen.value = !isBillingOpen.value; };
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
            <!-- Portal Dashboard -->
            <li class="nav-item">
                <router-link to="/portal" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Portal Dashboard</p>
                </router-link>
            </li>

            <!-- My Profile -->
            <li class="nav-item">
                <router-link to="/portal/profile" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-circle"></i>
                    <p>My Profile</p>
                </router-link>
            </li>
                <li class="nav-item">
                    <router-link to="/portal/remise-request" active-class="active" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>Remise Request</p>
                    </router-link>
                 </li>
                    <li class="nav-item">
                <router-link to="/portal/remise-request-approver" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-users"></i>
                    <p>Approver List</p>
                </router-link>
            </li>

            <!-- Appointments -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isAppointmentsOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleAppointments">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <p>
                        My Appointments
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isAppointmentsOpen">
                    <li class="nav-item">
                        <router-link to="/portal/appointments" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>View Appointments</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/portal/appointments/book" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Book Appointment</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- Medical Records -->
            <li class="nav-item">
                <router-link to="/portal/medical-records" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-file-medical"></i>
                    <p>Medical Records</p>
                </router-link>
            </li>

            <!-- Messages -->
            <li class="nav-item">
                <router-link to="/portal/messages" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-envelope"></i>
                    <p>Messages</p>
                </router-link>
            </li>

            <!-- Billing -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isBillingOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleBilling">
                    <i class="nav-icon fas fa-receipt"></i>
                    <p>
                        Billing & Payments
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isBillingOpen">
                    <li class="nav-item">
                        <router-link to="/portal/billing/invoices" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>My Invoices</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/portal/billing/payments" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Payment History</p>
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