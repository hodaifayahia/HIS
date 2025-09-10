<script setup>
import { useRouter, useRoute } from 'vue-router';
import { ref, defineProps, onMounted, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { useSweetAlert } from '../../Components/useSweetAlert'; // Not used in this snippet, but kept for context
import { useAuthStore } from '../../stores/auth'; // Not used in this snippet, but kept for context
import { useAuthStoreDoctor } from '../../stores/AuthDoctor';

const authStore = useAuthStore(); // Not used in this snippet, but kept for context
const doctorStore = useAuthStoreDoctor();
const router = useRouter();
const route = useRoute();
const toaster = useToastr();

const props = defineProps({
    user: {
        type: Object,
        required: true,
        default: () => ({})
    }
})

// Add this new ref for the consultation menu state
const isConsultationMenuOpen = ref(false);

// Ref to store the count of pending doctor requests
const pendingDoctorRequestCount = ref(0);

// Store the Echo channel reference for cleanup
let echoChannel = null;

// Add this new method to toggle the menu
const toggleConsultationMenu = () => {
    isConsultationMenuOpen.value = !isConsultationMenuOpen.value;
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


onMounted(async () => {
    // Only listen for doctor role
    if (props.user.data.role === 'doctor') {
        try {
            // Fetch doctor data to get the doctor ID
            await doctorStore.getDoctor();

            const doctorId = doctorStore.doctorData.id;

            if (doctorId) {
                console.log('Setting up WebSocket listener for doctor:', doctorId);
                console.log('Doctor data:', doctorStore.doctorData);

                // Fetch initial count
                await fetchPendingRequestCount(doctorId);


                // Create the private channel using the doctor ID from store
                echoChannel = Echo.private(`doctor.${doctorId}`)
                    .listen('.OpinionRequestCreated', (data) => {
                        console.log(data);

                        // Increment the count for new requests
                        pendingDoctorRequestCount.value++;

                        // Show toast notification with sender doctor name
                        toaster.success(`New opinion request from ${data.sender_doctor_name}`, {
                            duration: 5000,
                            position: 'top-right'
                        });

                        // Optional: Play notification sound
                        playNotificationSound();
                    })
                    .listen('.OpinionRequestReplied', (data) => {
                        console.log(data);
                        // Decrement the count if the reply means the request is no longer pending
                        // You might need to adjust this logic based on your 'status' field.
                        // For example, only decrement if the status goes from 'pending' to 'replied'.
                        // For now, we'll assume a reply means it's no longer pending.
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
                console.log('Doctor ID not found in store');
            }
        } catch (error) {
            console.error('Error fetching doctor data or setting up WebSocket:', error);
        }
    } else {
        console.log('WebSocket not initialized - user is not a doctor:', {
            role: props.user.data.role,
            user: props.user
        });
    }
});

onUnmounted(() => {
    // Clean up the Echo channel when component is unmounted
    if (echoChannel && doctorStore.doctorData.id) {
        echoChannel.stopListening('.OpinionRequestCreated');
        echoChannel.stopListening('.OpinionRequestReplied'); // Stop listening to this too
        window.Echo.leave(`doctor.${doctorStore.doctorData.id}`);
        console.log('WebSocket channel cleaned up');
    }
});

// Watch for route changes to re-fetch the count or handle state if needed
watch(
    () => route.fullPath,
    (newPath) => {
        // When navigating to the opinion receiver page, re-fetch the count
        // to ensure it's accurate after the user has potentially viewed requests.
        if (newPath === '/doctor/opinionReciver' && props.user.data.role === 'doctor' && doctorStore.doctorData.id) {
            fetchPendingRequestCount(doctorStore.doctorData.id);
        }
    }
);

// Optional: Function to play notification sound
const playNotificationSound = () => {
    try {
        const audio = new Audio('/storage/sound/notification.mp3');
        audio.play().catch(e => console.log('Could not play notification sound:', e));
    } catch (error) {
        console.log('Notification sound not available:', error);
    }
};
</script>

<template>
    <aside class="main-sidebar sidebar-dark-primary elevation-4 fixed">
        
        <a href="#" class="brand-link d-flex align-items-center">
            <img :src="'/storage/doctor.png'" class="img-circle elevation-2 me-2 mr-3"
                style="width: 40px; height: 40px;" alt="User Image">
            <span class="brand-text font-weight-light ms-2">Clinic </span>
        </a>

        <div class="sidebar ">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex justify-content-between">
                <div class="image">
                    <img :src="user.avatar" class="img-circle elevation-2 mr-2" alt="User Image"
                        style="height: 40px; width: 40px; object-fit: cover;">
                </div>
                <div class="info ">
                    <a href="#" class="d-block text-center mr-5 mt-1">{{ user.name }}</a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <li class="nav-item">
                        <router-link to="/admin/appointments/dashboard" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Dashboard</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/appointments/calender" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>Calendar</p>
                        </router-link>
                    </li>
                    <template v-if="user.data.role === 'admin' || user.data.role === 'SuperAdmin'">
                        <li class="nav-item">
                            <router-link to="/admin/appointments/users" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Users</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/appointments/Roles" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-cog"></i> <p>Roles</p>
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/admin/appointments/specializations" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-briefcase"></i>
                                <p>Specializations</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/appointments/excludeDates" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-calendar-times"></i>
                                <p>Exclude</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/appointments/doctor/forceAppointment" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Manage Forced Appointments</p>
                            </router-link>
                        </li>
                    </template>
                    <template
                        v-if="user.data.role === 'admin' || user.data.role === 'receptionist' || user.data.role === 'SuperAdmin'">
                        <li class="nav-item">
                            <router-link to="/admin/appointments/doctors" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Doctors</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/appointments/specialization" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Appointment</p>
                            </router-link>
                        </li>
                        
                        <li class="nav-item">
                            <router-link to="/admin/appointments/pending" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-tasks"></i>
                                <p>PendingList</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/Waitlist" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-clock"></i>
                                <p>Waitlist</p>
                            </router-link>
                        </li>
                    <li class="nav-item">
                        <router-link to="/admin/appointments/modality-appointment" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>Modality Appointment</p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/appointments/modality-appointment/forceAppointment" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Modality Force Appointment</p>
                        </router-link>
                    </li>
                    </template>
                     <li class="nav-item">
                            <router-link to="/admin/appointments/patient" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-injured"></i>
                                <p>Patient</p>
                            </router-link>
                        </li>

                    <template v-if="user.data.role === 'doctor'">
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
                            <router-link to="/admin/doctors/schedule" active-class="active" class="nav-link">
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
                            <router-link to="/admin/consultations/consultation-workspaces" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Workspace </p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/doctor/opinionReciver" active-class="active"
                                class="nav-link d-flex align-items-center">
                                <i class="nav-icon fas fa-stethoscope"></i>
                                <span style="margin-left: 8px;">Doctor Request</span>
                                <span v-if="pendingDoctorRequestCount > 0" class="badge bg-danger animated-badge"
                                    style="font-size: 13px; margin-left: 60px;">
                                    {{ pendingDoctorRequestCount }}
                                </span>
                            </router-link>
                        </li>
                    </template>

                    <template v-if="['admin', 'doctor', 'SuperAdmin'].includes(user.data.role)">
                        <li class="nav-item">
                            <router-link to="/admin/consultations/consultation" active-class="active" class="nav-link">
                                <i class="fas fa-clipboard nav-icon"></i>
                                <p>Consultation</p>
                            </router-link>
                        </li>

                        <li class="nav-item" :class="{ 'menu-is-opening menu-open': isConsultationMenuOpen }">
                            <a href="#" class="nav-link" @click.prevent="toggleConsultationMenu">
                                <i class="fas fa-sliders-h nav-icon"></i>
                                <p>
                                    Consultation Parameters
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview" v-show="isConsultationMenuOpen">
                                <li class="nav-item">
                                    <router-link to="/admin/consultations/placeholders" active-class="active"
                                        class="nav-link">
                                        <i class="far fa-folder-open nav-icon"></i>
                                        <p>Sections</p>
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/admin/consultations/folderlist" active-class="active"
                                        class="nav-link">
                                        <i class="far fa-folder nav-icon"></i>
                                        <p>Templates Folder</p>
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/admin/consultations/Medicales" active-class="active" class="nav-link">
                                        <i class="far fa-folder nav-icon"></i>
                                        <p>Medicales</p>
                                    </router-link>
                                </li>
                            </ul>
                        </li>
                    </template>

                    <li class="nav-item">
                        <router-link to="/admin/appointments/settings" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Settings</p>
                        </router-link>
                    </li>
                    <li class="nav-item mr-2">
                        <form style="display: contents;">
                            <a href="#" @click.prevent="logout" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt mr-2"></i>
                                Logout
                            </a>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>
</template>

<style scoped>
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