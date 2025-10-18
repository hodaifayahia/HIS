<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();

// Reactive state for the current time
const currentTime = ref('');
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
};
let timeInterval = null;

// Reactive state for dropdowns
const isNotificationsMenuOpen = ref(false);
const isUserMenuOpen = ref(false);

const toggleNotificationsMenu = () => {
    isNotificationsMenuOpen.value = !isNotificationsMenuOpen.value;
    isUserMenuOpen.value = false;
};

const toggleUserMenu = () => {
    isUserMenuOpen.value = !isUserMenuOpen.value;
    isNotificationsMenuOpen.value = false;
};

// Function to handle clicking outside the dropdowns
const closeDropdowns = (event) => {
    if (!event.target.closest('.notifications-menu') && !event.target.closest('.notifications-toggle')) {
        isNotificationsMenuOpen.value = false;
    }
    if (!event.target.closest('.user-menu') && !event.target.closest('.user-toggle')) {
        isUserMenuOpen.value = false;
    }
};

// Logout Function
const logout = async () => {
    try {
        await axios.post('/logout');
        window.location.href = '/login';
    } catch (error) {
        console.error('Error logging out:', error);
    }
};

onMounted(() => {
    // Start the clock
    updateTime();
    timeInterval = setInterval(updateTime, 1000);

    // Add click listener to close dropdowns
    document.addEventListener('click', closeDropdowns);
});

onUnmounted(() => {
    // Clean up the interval and event listener
    if (timeInterval) {
        clearInterval(timeInterval);
    }
    document.removeEventListener('click', closeDropdowns);
});
</script>

<template>
    <nav class="tw-main-header tw-navbar tw-flex tw-items-center tw-justify-between tw-px-4 tw-py-1 tw-bg-[#253237] tw-text-white tw-shadow-md tw-relative">
        <ul class="tw-navbar-nav tw-flex tw-items-center tw-space-x-4 tw-list-none tw-m-0 tw-p-0">
            <li class="tw-nav-item">
                <a class="tw-nav-link tw-p-2 tw-text-white hover:tw-text-gray-300" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>

        <div class="tw-absolute tw-left-1/2 tw-transform -tw-translate-x-1/2 tw-text-xl tw-font-bold tw-text-gray-200">
            {{ currentTime }}
        </div>

        <ul class="tw-navbar-nav tw-ml-auto tw-flex tw-items-center tw-space-x-4 tw-list-none tw-m-0 tw-p-0">
            <li class="tw-nav-item">
                <button @click="router.push('/home')" class="tw-btn tw-p-2 tw-text-white hover:tw-text-gray-300 tw-transition-colors" title="Go back">
                    <img class="tw-w-5 tw-h-5" src="https://img.icons8.com/ios/50/squared-menu.png" alt="squared-menu"/>
                </button>
            </li>
            
            <li class="tw-nav-item">
                <a class="tw-nav-link tw-p-2 tw-text-white hover:tw-text-gray-300" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            <!-- <li class="tw-nav-item tw-dropdown tw-relative">
                <a @click="toggleNotificationsMenu" class="tw-nav-link tw-p-2 tw-text-white hover:tw-text-gray-300 tw-cursor-pointer notifications-toggle">
                    <i class="far fa-bell"></i>
                    <span class="tw-badge tw-absolute tw-top-0 tw-right-0 tw-bg-red-500 tw-text-white tw-rounded-full tw-text-xs tw-px-1.5 tw-py-0.5">15</span>
                </a>
                <div v-if="isNotificationsMenuOpen" class="notifications-menu tw-absolute tw-right-0 tw-mt-2 tw-w-80 tw-bg-white tw-rounded-lg tw-shadow-xl tw-z-50">
                    <span class="tw-dropdown-header tw-block tw-p-3 tw-text-sm tw-font-bold tw-text-gray-700 tw-border-b tw-border-gray-200">15 Notifications</span>
                    <div class="tw-dropdown-divider tw-h-px tw-bg-gray-200"></div>
                    <a href="#" class="tw-dropdown-item tw-flex tw-items-center tw-px-4 tw-py-3 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 tw-transition-colors">
                        <i class="fas fa-envelope tw-mr-2 tw-w-4"></i>
                        <span>4 new messages</span>
                        <span class="tw-ml-auto tw-text-xs tw-text-gray-500">3 mins</span>
                    </a>
                    <div class="tw-dropdown-divider tw-h-px tw-bg-gray-200"></div>
                    <a href="#" class="tw-dropdown-item tw-flex tw-items-center tw-px-4 tw-py-3 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 tw-transition-colors">
                        <i class="fas fa-users tw-mr-2 tw-w-4"></i>
                        <span>8 friend requests</span>
                        <span class="tw-ml-auto tw-text-xs tw-text-gray-500">12 hours</span>
                    </a>
                    <div class="tw-dropdown-divider tw-h-px tw-bg-gray-200"></div>
                    <a href="#" class="tw-dropdown-item tw-flex tw-items-center tw-px-4 tw-py-3 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 tw-transition-colors">
                        <i class="fas fa-file tw-mr-2 tw-w-4"></i>
                        <span>3 new reports</span>
                        <span class="tw-ml-auto tw-text-xs tw-text-gray-500">2 days</span>
                    </a>
                    <div class="tw-dropdown-divider tw-h-px tw-bg-gray-200"></div>
                    <a href="#" class="tw-dropdown-item tw-dropdown-footer tw-block tw-text-center tw-py-2 tw-text-sm tw-text-gray-600 hover:tw-bg-gray-100 hover:tw-text-gray-800 tw-transition-colors">See All Notifications</a>
                </div>
            </li> -->
            <li class="tw-nav-item tw-dropdown tw-relative">
                <a @click="toggleUserMenu" class="tw-nav-link tw-p-2 tw-text-white hover:tw-text-gray-300 tw-cursor-pointer user-toggle">
                    <i class="far fa-user"></i>
                </a>
                <div v-if="isUserMenuOpen" class="user-menu tw-absolute tw-right-0 tw-mt-2 tw-w-56 tw-bg-white tw-rounded-lg tw-shadow-xl tw-z-50">
                    <div class="tw-dropdown-divider tw-h-px tw-bg-gray-200"></div>
                    <RouterLink to="/admin/settings" active-class="active"
                        class="tw-nav-link tw-flex tw-items-center tw-px-4 tw-py-3 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 hover:tw-text-gray-900 tw-transition-colors">
                        <i class="fas fa-cog tw-mr-2 tw-w-4"></i>
                        <p>Settings</p>
                    </RouterLink>
                    <div class="tw-dropdown-divider tw-h-px tw-bg-gray-200"></div>
                    <form style="display: contents;">
                        <a href="#" @click.prevent="logout"
                            class="tw-nav-link tw-flex tw-items-center tw-px-4 tw-py-3 tw-text-sm tw-text-gray-700 hover:tw-bg-gray-100 hover:tw-text-gray-900 tw-transition-colors">
                            <i class="fas fa-sign-out-alt tw-mr-2 tw-w-4"></i>
                            <span>Logout</span>
                        </a>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
</template>