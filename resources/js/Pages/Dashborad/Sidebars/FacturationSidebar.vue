<!-- Enhanced Facturation Sidebar -->
<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Facturation System',
    icon: '/storage/billing-icon.png',
    color: '#10b981', // Green-500 for financial
    backRoute: '/home'
};

// Reactive states for controlling submenu visibility
const isBillingManagementOpen = ref(false);
const isPaymentActionsOpen = ref(false);
const isReportingOpen = ref(false);
const isSettingsOpen = ref(false);

// Toggle functions for each submenu
const toggleBillingManagement = () => { 
    isBillingManagementOpen.value = !isBillingManagementOpen.value; 
};
const togglePaymentActions = () => { 
    isPaymentActionsOpen.value = !isPaymentActionsOpen.value; 
};
const toggleReporting = () => { 
    isReportingOpen.value = !isReportingOpen.value; 
};
const toggleSettings = () => { 
    isSettingsOpen.value = !isSettingsOpen.value; 
};

// Permission check function
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
            <!-- Dashboard -->
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/facturation/dashboard" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-chart-line tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Dashboard</p>
                </router-link>
            </li>

            <!-- Billing Management Section -->
            <li class="nav-item has-treeview tw-mb-1" :class="{ 'menu-is-opening menu-open': isBillingManagementOpen}">
                <a 
                    href="#" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                    @click.prevent="toggleBillingManagement"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-file-invoice-dollar tw-text-white"></i>
                    </div>
                    <p class="tw-flex-1 tw-font-medium">
                        Billing Management
                    </p>
                    <i 
                        class="fas fa-chevron-right tw-text-xs tw-transition-transform tw-duration-300" 
                        :class="{ 'tw-rotate-90': isBillingManagementOpen }"
                    ></i>
                </a>

                <transition name="slide">
                    <ul class="nav nav-treeview tw-ml-6 tw-mt-1" v-show="isBillingManagementOpen">
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/invoices" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">All Invoices</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/create-invoice" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Create Invoice</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/fiche-navette-billing" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Fiche Navette Billing</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/emergency-billing" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Emergency Billing</p>
                            </router-link>
                        </li>
                    </ul>
                </transition>
            </li>

            <!-- Payment Actions Section -->
            <li class="nav-item has-treeview tw-mb-1" :class="{ 'menu-is-opening menu-open': isPaymentActionsOpen}">
                <a 
                    href="#" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                    @click.prevent="togglePaymentActions"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-cash-register tw-text-white"></i>
                    </div>
                    <p class="tw-flex-1 tw-font-medium">
                        Caisse & Payments
                    </p>
                    <i 
                        class="fas fa-chevron-right tw-text-xs tw-transition-transform tw-duration-300" 
                        :class="{ 'tw-rotate-90': isPaymentActionsOpen }"
                    ></i>
                </a>

                <transition name="slide">
                    <ul class="nav nav-treeview tw-ml-6 tw-mt-1" v-show="isPaymentActionsOpen">
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/caisse-patient-payment" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Patient Payment</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/caisse-urgence" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Emergency Cashier</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/payment-history" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Payment History</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/refunds" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Refunds</p>
                            </router-link>
                        </li>
                    </ul>
                </transition>
            </li>

            <!-- Insurance Management -->
            <li class="nav-item tw-mb-1">
                <router-link 
                    to="/facturation/insurance-claims" 
                    active-class="active" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-shield-alt tw-text-white"></i>
                    </div>
                    <p class="tw-font-medium">Insurance Claims</p>
                </router-link>
            </li>

            <!-- Reports Section -->
            <li class="nav-item has-treeview tw-mb-1" :class="{ 'menu-is-opening menu-open': isReportingOpen}">
                <a 
                    href="#" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                    @click.prevent="toggleReporting"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-chart-bar tw-text-white"></i>
                    </div>
                    <p class="tw-flex-1 tw-font-medium">
                        Financial Reports
                    </p>
                    <i 
                        class="fas fa-chevron-right tw-text-xs tw-transition-transform tw-duration-300" 
                        :class="{ 'tw-rotate-90': isReportingOpen }"
                    ></i>
                </a>

                <transition name="slide">
                    <ul class="nav nav-treeview tw-ml-6 tw-mt-1" v-show="isReportingOpen">
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/daily-report" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Daily Report</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/monthly-summary" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Monthly Summary</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/revenue-analysis" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Revenue Analysis</p>
                            </router-link>
                        </li>
                    </ul>
                </transition>
            </li>

            <!-- Settings (Admin only) -->
            <li class="nav-item has-treeview tw-mb-1" v-if="hasPermission(['admin', 'billing_manager'])" :class="{ 'menu-is-opening menu-open': isSettingsOpen}">
                <a 
                    href="#" 
                    class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1"
                    @click.prevent="toggleSettings"
                >
                    <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                        <i class="fas fa-cog tw-text-white"></i>
                    </div>
                    <p class="tw-flex-1 tw-font-medium">
                        Settings
                    </p>
                    <i 
                        class="fas fa-chevron-right tw-text-xs tw-transition-transform tw-duration-300" 
                        :class="{ 'tw-rotate-90': isSettingsOpen }"
                    ></i>
                </a>

                <transition name="slide">
                    <ul class="nav nav-treeview tw-ml-6 tw-mt-1" v-show="isSettingsOpen">
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/pricing-rules" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Pricing Rules</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/tax-configuration" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Tax Configuration</p>
                            </router-link>
                        </li>
                        <li class="nav-item tw-mb-1">
                            <router-link 
                                to="/facturation/payment-methods" 
                                active-class="active" 
                                class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-pl-4 hover:tw-translate-x-1"
                            >
                                <div class="tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full"></div>
                                <p class="tw-text-sm">Payment Methods</p>
                            </router-link>
                        </li>
                    </ul>
                </transition>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Smooth slide animation for submenus */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-10px);
}

/* Enhanced active state */
.nav-link.active {
    background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.1)) !important;
    border-left: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Hover effect for nav items */
.nav-link:hover:not(.active) {
    background: rgba(255,255,255,0.08);
}

/* Submenu items styling */
.nav-treeview .nav-link {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.nav-treeview .nav-link.active {
    background: rgba(255,255,255,0.1);
    border-left: 2px solid rgba(255,255,255,0.8);
}
</style>
