<script setup>
import { defineProps, defineEmits, ref, onMounted, watch, capitalize, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';
import { useRouter } from 'vue-router';
import { useSweetAlert } from '../../../../Components/useSweetAlert';
import ReasonModel from '../../../../Components/appointments/ReasonModel.vue';
import { modalityAppointmentServices } from '../../../../Components/Apps/services/modality/ModalityAppointment';

// PrimeVue Components
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import Menu from 'primevue/menu';
import Message from 'primevue/message';

const swal = useSweetAlert();
const props = defineProps({
    appointments: {
        type: Array,
        required: true
    },
    error: {
        type: String,
        default: null
    },
    userRole: {
        type: String,
        required: true
    },
    modalityId: {
        type: [String, Number],
        required: false
    },
});

const emit = defineEmits(['updateAppointment', 'updateStatus', 'filterByDate', 'search', 'editAppointment']);

const toastr = useToastr();
const router = useRouter();

const statuses = ref([]);
const isReasonModelVisible = ref(false);
const selectedAppointmentForAction = ref(null);
const isRescheduleModalVisible = ref(false);
const rescheduleAppointmentId = ref(null);
const rescheduleSpecializationId = ref(null);
const isLoading = ref(false);
const selectedDate = ref(null);
const searchQuery = ref('');
const printMenuRefs = ref({});

// --- Computed Properties ---
const hasAdminPermissions = computed(() => {
    return props.userRole === 'admin' || props.userRole === 'SuperAdmin';
});
const groupedAppointments = computed(() => {
    if (!props.appointments || !props.appointments.length) return [];

    console.log('Original appointments:', props.appointments);

    // Group by patient, modality, and consecutive dates
    const sorted = [...props.appointments].sort((a, b) => {
        if (a.patient_id !== b.patient_id) return a.patient_id - b.patient_id;
        if (a.modality_id !== b.modality_id) return a.modality_id - b.modality_id;
        return new Date(a.appointment_date) - new Date(b.appointment_date);
    });

    console.log('Sorted appointments:', sorted);

    const grouped = [];
    let i = 0;

    while (i < sorted.length) {
        const current = sorted[i];

        // Check if this is a full day appointment (days-type modality or full_day_available time)
        const isFullDayAppointment =
            (current.modality?.slot_type === 'days') ||
            (current.appointment_time === 'full_day_available');

        if (!isFullDayAppointment) {
            // For non-full-day appointments, add them individually
            grouped.push(current);
            i++;
            continue;
        }

        console.log(`Processing full-day appointment ${i}:`, current);

        // Start a new group for consecutive days
        let group = [current];
        let j = i + 1;
        let currentDate = new Date(current.appointment_date);

        console.log('Start date:', currentDate);

        // Look for consecutive appointments for the same patient and modality
        while (j < sorted.length) {
            const next = sorted[j];

            // Check if it's the same patient, modality, and is also a full-day appointment
            const samePatient = next.patient_id === current.patient_id;
            const sameModality = next.modality_id === current.modality_id;
            const isNextFullDay =
                (next.modality?.slot_type === 'days') ||
                (next.appointment_time === 'full_day_available');

            if (!samePatient || !sameModality || !isNextFullDay) {
                console.log('Different patient, modality, or not full-day - breaking');
                break;
            }

            const nextDate = new Date(next.appointment_date);
            console.log('Next date to check:', nextDate);

            // Check if next date is consecutive (exactly 1 day later)
            const expectedNextDate = new Date(currentDate);
            expectedNextDate.setDate(expectedNextDate.getDate() + 1);

            console.log('Expected next date:', expectedNextDate);

            // Compare dates (ignoring time)
            const nextDateString = nextDate.toISOString().split('T')[0];
            const expectedDateString = expectedNextDate.toISOString().split('T')[0];

            if (nextDateString === expectedDateString) {
                console.log('Found consecutive date:', nextDate);
                group.push(next);
                currentDate = nextDate;
                j++;
            } else {
                console.log('Not consecutive, breaking. Expected:', expectedDateString, 'Got:', nextDateString);
                break;
            }
        }

        console.log('Group formed:', group);

        // Create the grouped entry
        if (group.length > 1) {
            // For multiple consecutive appointments, create a single row
            const groupedEntry = {
                ...group[0], // Use first appointment as base
                duration_days: group.length,
                end_date: group[group.length - 1].appointment_date,
                // Keep original data but update display fields
                grouped_appointments: group // Store all appointments in the group for reference
            };
            console.log('Adding grouped entry with duration:', groupedEntry.duration_days);
            grouped.push(groupedEntry);
        } else {
            // Single appointment
            const singleEntry = { ...current, duration_days: 1 };
            console.log('Adding single entry:', singleEntry);
            grouped.push(singleEntry);
        }

        i += group.length;
    }

    console.log('Final grouped appointments:', grouped);
    return grouped;
});
// --- Utility Functions ---

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        console.error("Error formatting date:", e);
        return 'Invalid Date';
    }
};

const formatTime = (time) => {
    if (!time) return "N/A";
    try {
        const date = new Date(`2000-01-01T${time}`);
        if (isNaN(date.getTime())) {
            return "Invalid Time";
        }
        return date.toLocaleTimeString('fr', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
    } catch (error) {
        console.error("Error formatting time:", error);
        return "Invalid Time";
    }
};

const formatDateTime = (input) => {
    if (!input) return 'N/A';
    try {
        const date = new Date(input);
        if (isNaN(date.getTime())) {
            return "Invalid Date/Time";
        }
        const formattedDate = date.toLocaleDateString('fr', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        const formattedTime = date.toLocaleTimeString('fr', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        return `${formattedDate} ${formattedTime}`;
    } catch (error) {
        console.error("Error formatting datetime:", error);
        return 'Invalid Date/Time';
    }
};

const getPatientFullName = (patient) => {
    // Check if patient object exists and has required properties
    if (!patient || (typeof patient.patient_first_name === 'undefined' && typeof patient.first_name === 'undefined')) {
        return 'N/A';
    }
    const firstName = patient.patient_first_name || patient.first_name || '';
    const lastName = patient.patient_last_name || patient.last_name || '';

    const fullName = [firstName, lastName].filter(Boolean).join(' ');
    return fullName ? capitalize(fullName) : 'N/A';
};

const getStatusText = (status) => {
    return status?.name || 'Unknown';
};

// --- Action Functions ---

const updateAppointmentStatus = async (appointmentId, newStatusValue, reason = null) => {
    try {
        const payload = { status: newStatusValue };

        if (newStatusValue === 2) { // CANCELED status (assuming 2 is the value for CANCELED)
            if (!reason) {
                // If cancellation is attempted without a reason, open the reason model
                openReasonModel({ id: appointmentId });
                return; // Stop execution here, waiting for reason input
            }
            payload.reason = reason;
        }

        const response = await modalityAppointmentServices.changeStatus(appointmentId, payload);
        if (response.success) {
            toastr.success(response.message);
            emit('updateStatus'); // Emit to re-fetch/update appointments
        } else {
            toastr.error(response.message);
        }
    } catch (err) {
        console.error('Error updating status:', err);
        toastr.error('Failed to update appointment status');
    }
};

const submitReasonValue = (reason) => {
    isReasonModelVisible.value = false;
    if (selectedAppointmentForAction.value) {
        // Pass the provided reason to updateAppointmentStatus
        updateAppointmentStatus(selectedAppointmentForAction.value.id, 2, reason); // 2 represents the CANCELED status
    }
    selectedAppointmentForAction.value = null;
};

const openReasonModel = (appointment) => {
    selectedAppointmentForAction.value = appointment;
    isReasonModelVisible.value = true;
};

const openRescheduleModal = (appointment) => {
    isRescheduleModalVisible.value = true;
    rescheduleAppointmentId.value = appointment.id;
    rescheduleSpecializationId.value = appointment.specialization_id;
};

const closeRescheduleModal = () => {
    isRescheduleModalVisible.value = false;
    emit('updateStatus');
    emit('updateAppointment');
};

const printTicket = async (appointment) => {
    try {
        // Ensure patient and modality data are available
      
        console.log('Printing ticket for appointment:', appointment);
        

        const ticketData = {
    patient_first_name: appointment.patient_first_name,
    patient_last_name: appointment.patient_last_name,
    modality_name: appointment.modality_name,
    modality_id: appointment.modality_id,
    appointment_date: appointment.appointment_date,
    appointment_time: appointment.appointment_time,
    description: appointment.notes || appointment.description,
};

        const response = await modalityAppointmentServices.printTicket(ticketData);

        if (response.success) {
            const blob = response.data;
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `modality_ticket_${appointment.id}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            toastr.success('Ticket printed successfully');
        } else {
            toastr.error(response.message || 'Failed to print ticket');
        }
    } catch (error) {
        console.error('Error printing ticket:', error);
        toastr.error('Failed to print ticket');
    }
};
const hasDayBasedAppointments = computed(() => {
    console.log('Checking for day-based appointments in groupedAppointments:', groupedAppointments.value);

    return groupedAppointments.value.some(appointment =>

        appointment.modality?.slot_type === 'days' ||
        appointment.appointment_time === 'full_day_available'

    );

});

const printConfirmationTicket = async (appointment) => {
    try {
        if (!props.modalityId) {
            toastr.error('Modality ID is missing for confirmation ticket printing.');
            return;
        }

        const ticketDataConfirmation = {
            patient_first_name: appointment.patient.first_name || appointment.patient_first_name,
            patient_last_name: appointment.patient.last_name || appointment.last_name,
            specialization_id: appointment.specialization_id,
            modality_name: appointment.modality?.name || appointment.modality_name || 'N/A',
            appointment_date: appointment.appointment_date,
            appointment_time: appointment.appointment_time,
            description: appointment.description || 'N/A'
        };

        const response = await modalityAppointmentServices.printConfirmationTicket(ticketDataConfirmation);

        if (response.success) {
            const pdfUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
            const printWindow = window.open(pdfUrl);
            printWindow.onload = function () {
                printWindow.print();
            };
        } else {
            toastr.error(response.message);
        }
    } catch (error) {
        console.error('Error printing confirmation ticket:', error);
        toastr.error('Failed to print confirmation ticket');
    }
};

// --- Lifecycle Hooks and Watchers ---

onMounted(() => {
    getAppointmentsStatus();
});

// Fetch status options (assuming a generic endpoint for all appointment statuses)
const getAppointmentsStatus = async () => {
    try {
        const response = await axios.get(`/api/appointment-statuses`);
        statuses.value = response.data;
    } catch (err) {
        console.error('Error fetching appointment statuses:', err);
    }
};

// Debounced search and date filter handlers
let searchTimeout = null;
const handleSearchInput = (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        emit('search', value);
    }, 300);
};

const handleDateFilterChange = (date) => {
    const formattedDate = date ? new Date(date).toISOString().split('T')[0] : null;
    emit('filterByDate', formattedDate);
};

const goToEditAppointmentPage = (appointment) => {
    emit('editAppointment', appointment);
};

const deleteAppointment = (appointmentId) => {
    emit('deleteAppointment', appointmentId);
};

// For print dropdown menu
const togglePrintMenu = (event, appointmentId) => {
    // Close other print menus if open
    Object.keys(printMenuRefs.value).forEach(key => {
        if (key !== appointmentId && printMenuRefs.value[key]) {
            printMenuRefs.value[key].hide(event);
        }
    });
    // Toggle current print menu
    if (printMenuRefs.value[appointmentId]) {
        printMenuRefs.value[appointmentId].toggle(event);
    }
};
</script>

<template>
    <div class="card p-4 shadow-sm">
        <Message v-if="props.error" severity="error" :closable="false">{{ props.error }}</Message>

        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
            <div style="flex-grow: 1; min-width: 200px; max-width: 300px; display: flex;">
                <Calendar v-model="selectedDate" dateFormat="yy-mm-dd" placeholder="Filter by date" showIcon
                    @date-select="handleDateFilterChange" class="w-full" />
                <Button icon="pi pi-filter" @click="handleDateFilterChange(selectedDate)" :loading="isLoading" />
                <Button icon="pi pi-replay" class="p-button-secondary p-button-outlined"
                    @click="selectedDate = null; handleDateFilterChange(null)" />
            </div>

            <span style="display: flex; flex-grow: 1; min-width: 250px; position: relative;">
                <i class="pi pi-search"
                    style="position: absolute; top: 50%; left: 1rem; transform: translateY(-50%); z-index: 1;" />
                <InputText v-model="searchQuery" placeholder="Search by patient name or phone"
                    @input="handleSearchInput(searchQuery)" style="padding-left: 2.5rem; width: 100%;" />
            </span>
        </div>

        <div class="overflow-x-auto">

            <DataTable :value="groupedAppointments" stripedRows showGridlines>
                <Column header="#" :rowEditor="true">
                    <template #body="slotProps">
                        {{ slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="patient_first_name" header="Patient">
                    <template #body="slotProps">
                        {{ getPatientFullName(slotProps.data) }}
                    </template>
                </Column>
                <Column field="Parent" header="Parent"></Column>
                <Column field="phone" header="Phone"></Column>
                <Column field="patient_Date_Of_Birth" header="Date Of Birth">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.patient_Date_Of_Birth) }}
                    </template>
                </Column>
                <Column field="appointment_date" header="Date">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.appointment_date) }}
                        <template v-if="slotProps.data.end_date">
                            - {{ formatDate(slotProps.data.end_date) }}
                        </template>
                    </template>
                </Column>
                <Column v-if="hasDayBasedAppointments" field="duration_days" header="Duration (Days)">
                    <template #body="slotProps">
                        {{ slotProps.data.duration_days || 1 }}
                    </template>
                </Column>
                <Column field="appointment_time" header="Time">
                    <template #body="slotProps">
                        <template v-if="slotProps.data.modality?.slot_type === 'days'">
                            Full Day
                        </template>
                        <template v-else>
                            {{ formatTime(slotProps.data.appointment_time) }}
                        </template>
                    </template>
                </Column>
                <Column field="description" header="Description">
                    <template #body="slotProps">
                        {{ slotProps.data.description ?? "Null" }}
                    </template>
                </Column>
                <Column field="status.name" header="Status">
                    <template #body="slotProps">
                        <Dropdown v-model="slotProps.data.status.value" :options="statuses" optionLabel="name"
                            optionValue="value"
                            @change="updateAppointmentStatus(slotProps.data.id, slotProps.data.status.value)"
                            :placeholder="getStatusText(slotProps.data.status)"
                            :class="`p-button-${slotProps.data.status.color}`">
                            <template #value="slotStatus">
                                <Tag :value="getStatusText(statuses.find(s => s.value === slotStatus.value))"
                                    :severity="statuses.find(s => s.value === slotStatus.value)?.color"
                                    :icon="statuses.find(s => s.value === slotStatus.value)?.icon" />
                            </template>
                            <template #option="optionSlotProps">
                                <Tag :value="getStatusText(optionSlotProps.option)"
                                    :severity="optionSlotProps.option?.color" :icon="optionSlotProps.option?.icon" />
                            </template>
                        </Dropdown>
                    </template>
                </Column>
                <Column field="reason" header="Reason">
                    <template #body="slotProps">
                        {{ slotProps.data.reason ?? "__" }}
                    </template>
                </Column>
                <Column v-if="hasAdminPermissions" field="created_by_user_name" header="Created By"></Column>
                <Column v-if="hasAdminPermissions" field="created_at" header="Created At">
                    <template #body="slotProps">
                        {{ formatDateTime(slotProps.data.created_at) }}
                    </template>
                </Column>
                <Column v-if="hasAdminPermissions" field="canceled_by_user_name" header="Canceled By"></Column>
                <Column v-if="hasAdminPermissions" field="updated_by_user_name" header="Updated By"></Column>
                <Column v-if="hasAdminPermissions" field="updated_at" header="Updated At">
                    <template #body="slotProps">
                        {{ formatDateTime(slotProps.data.updated_at) }}
                    </template>
                </Column>

                <Column header="Actions">
                    <template #body="slotProps">
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <Button type="button" icon="pi pi-print" class="p-button-sm p-button-info p-button-outlined"
                                @click="(event) => togglePrintMenu(event, slotProps.data.id)" aria-haspopup="true"
                                aria-controls="overlay_menu" />
                            <Menu :ref="(el) => printMenuRefs[slotProps.data.id] = el" :model=" [
                                { label: 'Ticket', icon: 'pi pi-ticket', command: () => printTicket(slotProps.data) },
                            ]" :popup="true" />

                            <Button @click="goToEditAppointmentPage(slotProps.data)" icon="pi pi-pencil"
                                class="p-button-sm p-button-primary p-button-outlined" />
                            
                            <!-- Add Delete Button (always visible) -->
                            <Button @click="deleteAppointment(slotProps.data.id)"
                                icon="pi pi-trash" class="p-button-sm p-button-danger p-button-outlined" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <ReasonModel :show="isReasonModelVisible" @close="isReasonModelVisible = false" @submit="submitReasonValue" />
    </div>
</template>

<style scoped>
/* Scoped styles for ModalityAppointmentListItem.vue */
.p-datatable .p-column-header-content {
    justify-content: center;
    /* Center header text */
}

/* Specific button overrides if needed */
.p-button-info.p-button-outlined {
    color: var(--blue-500);
    border-color: var(--blue-500);
}

.p-button-info.p-button-outlined:hover {
    background-color: var(--blue-50);
}

.p-button-secondary.p-button-outlined {
    color: var(--surface-500);
    border-color: var(--surface-500);
}

.p-button-secondary.p-button-outlined:hover {
    background-color: var(--surface-100);
}

/* Custom flexbox utilities (replacing PrimeFlex classes) */
.w-full {
    width: 100%;
}

/* Custom styling for the search input with icon */
.p-input-icon-left {
    position: relative;
}

.p-input-icon-left i {
    position: absolute;
    top: 50%;
    left: 1rem;
    transform: translateY(-50%);
    z-index: 1;
    color: var(--text-color-secondary);
    /* Adjust icon color */
}

.p-input-icon-left .p-inputtext {
    padding-left: 2.5rem;
    /* Ensure space for the icon */
}

.p-dropdown {
    width: 100%;
    /* Ensure dropdown takes full width of its column */
}
</style>