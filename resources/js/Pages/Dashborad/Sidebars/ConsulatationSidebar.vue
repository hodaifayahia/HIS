<script setup>
import { useRouter, useRoute } from 'vue-router';
import { ref, defineProps, onMounted, watch, onUnmounted, nextTick } from 'vue'; // Import nextTick
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';

// Import the BaseSidebar component
import BaseSidebar from './BaseSidebar.vue'; // Adjust path if necessary

const router = useRouter();
const route = useRoute();
const toaster = useToastr();
const doctorStore = useAuthStoreDoctor();

const props = defineProps({
    user: {
        type: Object,
        required: true,
        default: () => ({})
    }
});

const isConsultationMenuOpen = ref(false); // Ref to store the consultation menu state
const pendingDoctorRequestCount = ref(0); // Ref to store the count of pending doctor requests
let echoChannel = null; // Store the Echo channel reference for cleanup

// Function to toggle the menu
const toggleConsultationMenu = () => {
    isConsultationMenuOpen.value = !isConsultationMenuOpen.value;
    // Potentially trigger AdminLTE's collapse behavior if it exists
    // This part often needs to directly interact with AdminLTE's JS if it's taking over.
    // For now, relying on class binding.
};

// Function to fetch the initial pending count
const fetchPendingRequestCount = async (doctorId) => {
    try {
        const response = await axios.get(`/api/opinion-requests/pending-opinion-requests-count/${doctorId}`);
        pendingDoctorRequestCount.value = response.data.count;
    } catch (error) {
        console.error('Error fetching pending request count:', error);
        pendingDoctorRequestCount.value = 0; // Reset or handle error
    }
};

// Function to check if the current route is a child of the consultation parameters menu
const isCurrentRouteInConsultationParameters = () => {
    // Check if the current route matches any of the submenu paths
    const currentPath = route.path;
    return currentPath.startsWith('/admin/consultations/') || 
           currentPath.startsWith('/admin/Medicales') ||
           currentPath === '/admin/Medicales'; // Add exact match for Medicales route
};

// Function to update the menu state based on the current route
const updateMenuState = () => {
    isConsultationMenuOpen.value = isCurrentRouteInConsultationParameters();

    // If you encounter visual glitches or the menu still doesn't open
    // it might be due to AdminLTE's JS not picking up the initial state.
    // A common "hack" is to manually trigger a resize event or a click on the parent.
    // However, for clean Vue integration, applying the classes should be enough.
    // If AdminLTE actively manages 'menu-open', you might need to find its API
    // or trigger its internal toggle function.
};

onMounted(async () => {
    try {
        await doctorStore.getDoctor();
        const doctorId = doctorStore.doctorData.id;

        // Immediately update menu state on mount
        updateMenuState();

        if (doctorId) {
            console.log('Setting up WebSocket listener for doctor:', doctorId);
            console.log('Doctor data:', doctorStore.doctorData);

            await fetchPendingRequestCount(doctorId);

            // Ensure window.Echo exists before trying to use it
            if (window.Echo) {
                echoChannel = window.Echo.private(`doctor.${doctorId}`)
                    .listen('.OpinionRequestCreated', (data) => {
                        console.log(data);
                        pendingDoctorRequestCount.value++;
                        toaster.success(`New opinion request from ${data.sender_doctor_name}`, {
                            duration: 5000,
                            position: 'top-right'
                        });
                        playNotificationSound();
                    })
                    .listen('.OpinionRequestReplied', (data) => {
                        console.log(data);
                        if (pendingDoctorRequestCount.value > 0) {
                            pendingDoctorRequestCount.value--;
                        }
                        toaster.success(
                            `Your opinion request was replied by ${data.receiver_doctor_name}`,
                            {
                                duration: 5000,
                                position: 'top-right'
                            }
                        );
                        playNotificationSound();
                    })
                    .error((error) => {
                        console.error('WebSocket connection error:', error);
                        console.error('Error details:', error);
                    });
                console.log(`WebSocket listener set up for channel: doctor.${doctorId}`);
            } else {
                console.warn('Echo (Laravel Echo) is not defined. WebSocket features will not work.');
            }
        } else {
            console.log('Doctor ID not found in store');
        }
    } catch (error) {
        console.error('Error fetching doctor data or setting up WebSocket:', error);
    }
});

onUnmounted(() => {
    if (echoChannel && doctorStore.doctorData.id && window.Echo) {
        echoChannel.stopListening('.OpinionRequestCreated');
        echoChannel.stopListening('.OpinionRequestReplied');
        window.Echo.leave(`doctor.${doctorStore.doctorData.id}`);
        console.log('WebSocket channel cleaned up');
    }
});

// Watch for route changes to update the menu state
watch(
    () => route.fullPath,
    (newPath) => {
        // Refetch pending count if navigating to opinionReciver
        if (newPath === '/doctor/opinionReciver' && doctorStore.doctorData.id) {
            fetchPendingRequestCount(doctorStore.doctorData.id);
        }
        // Update menu state on route change
        updateMenuState();
    },
    { immediate: true } // Run immediately on component mount
);

const playNotificationSound = () => {
    try {
        // Check if the browser supports Audio before trying to play
        if (typeof Audio !== 'undefined') {
            const audio = new Audio('/storage/sound/notification.mp3');
            audio.play().catch(e => console.log('Could not play notification sound (user interaction required, etc.):', e));
        } else {
            console.log('Audio API not supported in this browser.');
        }
    } catch (error) {
        console.log('Error attempting to play notification sound:', error);
    }
};
</script>

<template>
    <BaseSidebar
        :user="user"
        appName="Doctor Panel"
        appIcon="/storage/doctor.png"
        appColor="#007bff"
        backRoute="/home"
    >
        <template #navigation>
            <li class="nav-item">
                <router-link to="/doctor/appointments" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-calendar-check"></i>
                    <p>Appointments</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/excludeDates" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-ban"></i>
                    <p>Day Offs</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/avilability" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-clock"></i>
                    <p>Availability</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/patient" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-injured"></i>
                    <p>Patient</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/schedule" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <p>Schedule</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/users" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-shield"></i>
                    <p>Manage Forced Appointments</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/consultations/consultation-workspaces" active-class="active" class="nav-link">
                    <i class="nav-icon fas fa-user-shield"></i>
                    <p>Workspace</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/opinionReciver" active-class="active" class="nav-link d-flex align-items-center">
                    <i class="nav-icon fas fa-stethoscope"></i>
                    <span style="margin-left: 8px;">Doctor Request</span>
                    <span v-if="pendingDoctorRequestCount > 0" class="badge bg-danger animated-badge"
                        style="font-size: 13px; margin-left: 60px;">
                        {{ pendingDoctorRequestCount }}
                    </span>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/doctor/consultations/consultation" active-class="active" class="nav-link">
                    <i class="fas fa-clipboard nav-icon"></i>
                    <p>Consultation</p>
                </router-link>
            </li>

            <li class="nav-item" :class="{ 'menu-is-opening': isConsultationMenuOpen, 'menu-open': isConsultationMenuOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleConsultationMenu">
                    <i class="fas fa-sliders-h nav-icon"></i>
                    <p>
                        Consultation Parameters
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>

                <ul class="nav nav-treeview" v-show="isConsultationMenuOpen">
                    <li class="nav-item">
                        <router-link to="/admin/consultations/placeholders" active-class="active" class="nav-link">
                            <i class="far fa-folder-open nav-icon"></i>
                            <p>Sections</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/consultations/folderlist" active-class="active" class="nav-link">
                            <i class="far fa-folder nav-icon"></i>
                            <p>Templates Folder</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/Medicales" active-class="active" class="nav-link">
                            <i class="far fa-folder nav-icon"></i>
                            <p>Medicales</p>
                        </router-link>
                    </li>
                </ul>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* Only keep styles specific to elements within DoctorSidebar, not the overall sidebar structure */
.animated-badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.1);
    }

    100% {
        transform: scale(1);
    }
}
</style>