<script setup>
import { onMounted, watch, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToastr } from '../../../toster';
import { modalityService } from '../../../../Components/Apps/services/modality/modalityService';
import { modalityAppointmentServices } from '../../../../Components/Apps/services/modality/ModalityAppointment';

const router = useRouter();
const toaster = useToastr();

const props = defineProps({
    modalityId: {
        type: [String, Number],
        required: true
    },
    isModality: {
        type: Boolean,
        default: true
    },
});

const currentModality = ref(null);
const isLoadingModalityInfo = ref(false);
const appointmentCounts = ref({
    next: 0,
    canceled: 0,
    today: 0,
    canceledAppointments: [],
    normalAppointments: null,
});

// Utility function to format dates in French format
const formatDateToFrench = (date) => {
    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return new Intl.DateTimeFormat('fr-FR', options).format(new Date(date));
};

const fetchCurrentModalityInfo = async (modalityId) => {
    if (!modalityId) {
        console.warn('Modality ID is missing for fetchCurrentModalityInfo.');
        return;
    }
    isLoadingModalityInfo.value = true;
    try {
        const response = await modalityService.getById(modalityId);
        if (response.success) {
            currentModality.value = response.data;
        } else {
            toaster.error(response.message || 'Failed to fetch modality information.');
            console.error('Error fetching current modality info:', response.error);
        }
    } catch (error) {
        console.error('Unexpected error in fetchCurrentModalityInfo:', error);
        toaster.error('An unexpected error occurred while fetching modality information.');
    } finally {
        isLoadingModalityInfo.value = false;
    }
};

const fetchAppointmentCounts = async (modalityId) => {
    try {
        const response = await axios.get(`/api/modality-appointments/${modalityId}/available`);

        if (response.data.success) {
            appointmentCounts.value = {
                today: appointmentCounts.value.today,
                next: response.data.data.normal_appointments ? 1 : 0,
                canceled: response.data.data.canceled_appointments.length,
                canceledAppointments: response.data.data.canceled_appointments || [],
                normalAppointments: response.data.data.normal_appointments || null,
            };
        } else {
            toaster.error(response.data.message || 'Failed to fetch available appointments.');
        }
    } catch (error) {
        console.error('Error fetching appointment counts:', error);
        toaster.error('An error occurred while fetching appointment counts.');
    }
};

watch(() => props.modalityId, async (newModalityId) => {
    if (newModalityId) {
        await fetchCurrentModalityInfo(newModalityId);
        await fetchAppointmentCounts(newModalityId);
    }
}, { immediate: true });
</script>

<template>
    <div class="header p-4 rounded-lg d-flex flex-column position-relative bg-primary">
        <button v-if="!isModality" class="btn btn-light bg-primary rounded-pill shadow-sm position-absolute"
            style="top: 20px; left: 20px;" @click="router.go(-1)">
            <i class="fas fa-arrow-left"></i> Back
        </button>

        <div class="d-flex align-items-center justify-content-between mt-5">
            <div class="d-flex align-items-center">
                <div class="mx-auto rounded-circle overflow-hidden border" style="width: 150px; height: 150px;">
                    <img v-if="!isLoadingModalityInfo && currentModality && currentModality.icon_url"
                        :src="currentModality.icon_url" alt="Modality image" class="w-100 h-100"
                        style="object-fit: contain; border-radius: 50%;" />
                    <div v-else class="w-100 h-100 bg-gray-300 animate-pulse rounded-circle"></div>
                </div>
                <div class="ml-4">
                    <h2 v-if="!isLoadingModalityInfo && currentModality" class="h4 font-weight-bold text-white mb-2">
                        {{ currentModality.name }}
                    </h2>
                    <p v-if="!isLoadingModalityInfo && currentModality && currentModality.modality_type" class="mb-1 text-white font-weight-bold">
                        Type: {{ currentModality.modality_type.name }}
                    </p>
                    <p v-if="!isLoadingModalityInfo && currentModality" class="mb-0 text-white-50">
                        <span class="font-weight-bold"><i class="fas fa-code"></i> Internal Code: {{ currentModality.internal_code }}</span>
                    </p>
                    <div v-if="isLoadingModalityInfo">
                        <div class="h4 bg-gray-300 animate-pulse rounded mb-2" style="width: 150px; height: 24px;"></div>
                        <div class="bg-gray-300 animate-pulse rounded mb-1" style="width: 200px; height: 16px;"></div>
                        <div class="bg-gray-300 animate-pulse rounded" style="width: 100px; height: 16px;"></div>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <div class="mb-4">
                    <p class="mb-1 small text-white-50">Next Appointment:</p>
                    <p v-if="appointmentCounts.normalAppointments" class="h5 font-weight-bold text-white mb-2">
                        {{ formatDateToFrench(appointmentCounts.normalAppointments.date) }}
                    </p>
                    <p v-else class="h5 font-weight-bold text-white mb-2">No upcoming appointments</p>
                </div>
                <div>
                    <p class="mb-1 small text-white-50">Canceled Appointments:</p>
                    <ul class="list-unstyled text-white">
                        <li v-for="appointment in appointmentCounts.canceledAppointments" :key="appointment.date">
                            {{ formatDateToFrench(appointment.date) }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0% {
        background-color: rgba(165, 165, 165, 0.1);
    }
    50% {
        background-color: rgba(165, 165, 165, 0.3);
    }
    100% {
        background-color: rgba(165, 165, 165, 0.1);
    }
}

.animate-pulse {
    animation: pulse 1.5s infinite;
}

.header {
    background-color: #007bff;
    color: white;
}

.btn-light {
    color: #007bff;
}
</style>