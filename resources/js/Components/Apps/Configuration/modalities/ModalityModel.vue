<script setup>
import { ref, computed, defineProps, defineEmits, watch, onUnmounted, onMounted } from 'vue';
import { Field, Form } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from '../../../toster'; // Assuming toster.js is available at this path
import DoctorSchedules from '../../../Doctor/DoctorSchedules.vue'; // Reusing as per your request
import CustomDates from '../../../Doctor/CustomDates.vue'; // Reusing as per your request
import AppointmentBookingWindowModel from '../../../Doctor/AppointmentBookingWindowModel.vue'; // Reusing as per your request

// Import services for data fetching (assuming these paths are correct)
import { modalityService } from '../../services/modality/modalityService';
import { specializationService } from '../../services/specialization/specializationService';

const props = defineProps({
    showModal: {
        type: Boolean,
        required: true,
    },
    modalityData: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close', 'modalityUpdated', 'modalityAdded']);
const toaster = useToastr();
const errors = ref({}); // Used for manual errors like image size
const specializations = ref([]); // Will hold fetched specializations
const modalityTypes = ref([]); // Will hold fetched modality types
const isLoading = ref(false); // Used for form submission loading state

const selectedMonths = ref([]); // To handle data from AppointmentBookingWindowModel

// Unified reactive object for modality data, mirroring the doctor object structure
const modality = ref({
    id: props.modalityData?.id || null,
    name: props.modalityData?.name || '',
    internal_code: props.modalityData?.internal_code || '',
    modality_type_id: props.modalityData?.modality_type_id || null,
    dicom_ae_title: props.modalityData?.dicom_ae_title || '',
    port: props.modalityData?.port || null,
    physical_location_id: props.modalityData?.physical_location_id || null,
    operational_status: props.modalityData?.operational_status || 'Working',
    specialization_id: props.modalityData?.specialization_id || null,
    integration_protocol: props.modalityData?.integration_protocol || '',
    connection_configuration: props.modalityData?.connection_configuration || '',
    data_retrieval_method: props.modalityData?.data_retrieval_method || '',
    ip_address: props.modalityData?.ip_address || '',
    frequency: props.modalityData?.frequency || 'Weekly', // Default to Weekly as per doctor model
    time_slot_duration: props.modalityData?.time_slot_duration || null,
    slot_type: props.modalityData?.slot_type || 'minutes',
    booking_window: props.modalityData?.booking_window || 30,
    notes: props.modalityData?.notes || '',
    is_active: props.modalityData?.is_active !== undefined ? props.modalityData.is_active : true, // Mimicking doctor.is_active
    include_time: !!props.modalityData?.include_time, // New field, always boolean

    // Schedule and Custom Dates (reusing doctor's schedule structure)
    schedules: Array.isArray(props.modalityData?.schedules) ? [...props.modalityData.schedules] : [],
    customDates: Array.isArray(props.modalityData?.customDates) ? [...props.modalityData.customDates] : [],

    // Appointment Forcer mirroring Doctor model
    start_time_force: props.modalityData?.start_time_force || '',
    end_time_force: props.modalityData?.end_time_force || '',
    number_of_patients: props.modalityData?.number_of_patients || '',

    // Appointment Booking Window (renamed to match doctor's key)
    appointmentBookingWindow: props.modalityData?.appointment_booking_window, // Assuming backend uses this name now
    avatar: props.modalityData?.avatar || null, // Placeholder for image, if modality also has one

    // New fields for consumption type and unit
    consumption_type: props.modalityData?.consumption_type || '',
    consumption_unit: props.modalityData?.consumption_unit || null,
});

const imagePreview = ref(modality.value.avatar ? modality.value.avatar : null);

const isEditMode = computed(() => !!props.modalityData?.id);

// Fetch dropdown options (Specializations and Modality Types)
const fetchDropdownOptions = async () => {
    try {
        const [modalityTypesResponse, specializationsResponse] = await Promise.all([
            modalityService.getModalityTypes(),
            specializationService.getAll(),
        ]);

        if (modalityTypesResponse.success) {
            modalityTypes.value = modalityTypesResponse.data;
        } else {
            toaster.error(modalityTypesResponse.message || 'Failed to load modality types');
        }

        if (specializationsResponse.success) {
            specializations.value = specializationsResponse.data;
        } else {
            toaster.error(specializationsResponse.message || 'Failed to load specializations');
        }
    } catch (error) {
        console.error('Error fetching dropdown options:', error);
        toaster.error('Failed to load dropdown options');
    }
};

const closeModal = () => {
    errors.value = {}; // Clear manual errors
    emit('close');
};

const handleModalityUpdate = () => {
    emit(isEditMode.value ? 'modalityUpdated' : 'modalityAdded');
    closeModal();
};

// YUP Schema for validation, mimicking doctor model
const getModalitySchema = (isEditMode) => {
    const baseSchema = {
        name: yup.string().required('Modality name is required'),
        internal_code: yup.string().required('Internal code is required'),
        modality_type_id: yup.number().nullable().required('Modality type is required'),
        operational_status: yup.string().required('Operational status is required'),
        frequency: yup.string().required('Frequency is required'),
        specialization_id: yup.number().nullable().required('Specialization is required'),
        slot_type: yup.string().required('Slot type is required'),
        time_slot_duration: yup.number().when('slot_type', {
            is: 'minutes',
            then: (schema) => schema.required('Time slot duration is required for minutes slot type').min(1, 'Duration must be at least 1 minute'),
            otherwise: (schema) => schema.nullable(),
        }),
        booking_window: yup.number().when('slot_type', {
            is: 'days',
            then: (schema) => schema.required('Booking window is required for days slot type').min(0, 'Booking window cannot be negative'),
            otherwise: (schema) => schema.nullable(),
        }),
        // Optional appointment force fields (now directly on modality object)
        start_time_force: yup.string().optional(),
        end_time_force: yup.string().optional(),
        number_of_patients: yup.number().optional().nullable().min(0, 'Number of patients cannot be negative'),

        // New fields for consumption type and unit
        consumption_type: yup.string().nullable().required('Consumption type is required'),
        consumption_unit: yup.number().nullable().required('Consumption unit is required').min(0, 'Consumption unit must be at least 0'),
    };
    return yup.object(baseSchema);
};

// Watcher for props.modalityData, mirroring doctor model
watch(
    () => props.modalityData,
    (newValue) => {
        if (newValue) {
            // Use available_months for selectedMonths
            if (newValue.available_months) {
                selectedMonths.value = newValue.available_months.map((month) => ({
                    value: month.month,
                    year: month.year,
                    is_available: month.is_available,
                }));
            } else {
                selectedMonths.value = [];
            }

            modality.value = {
                ...modality.value,
                appointmentBookingWindow: newValue.available_months
                    ? newValue.available_months.map((month) => ({
                        month: month.month,
                        year: month.year,
                        is_available: month.is_available,
                    }))
                    : [],
                id: newValue?.id || null,
                name: newValue?.name || '',
                internal_code: newValue?.internal_code || '',
                modality_type_id: newValue?.modality_type_id || null,
                dicom_ae_title: newValue?.dicom_ae_title || '',
                port: newValue?.port || null,
                physical_location_id: newValue?.physical_location_id || null,
                operational_status: newValue?.operational_status || 'Working',
                specialization_id: newValue?.specialization_id || null,
                integration_protocol: newValue?.integration_protocol || '',
                connection_configuration: newValue?.connection_configuration || '',
                data_retrieval_method: newValue?.data_retrieval_method || '',
                ip_address: newValue?.ip_address || '',
                frequency: newValue?.frequency || 'Weekly',
                time_slot_duration: newValue?.time_slot_duration || null,
                slot_type: newValue?.slot_type || 'minutes',
                booking_window: newValue?.booking_window || 30,
                notes: newValue?.notes || '',
                is_active: newValue?.is_active !== undefined ? newValue.is_active : true,
                include_time: !!newValue?.include_time, // Ensure boolean

                schedules: Array.isArray(newValue?.schedules) ? [...newValue.schedules] : [],
                customDates: Array.isArray(newValue?.customDates) ? [...newValue.customDates] : [],

                start_time_force: newValue?.start_time_force,
                end_time_force: newValue?.end_time_force,
                number_of_patients: newValue?.number_of_patients || '',

                avatar: newValue?.avatar || null,

                // New fields
                consumption_type: newValue?.consumption_type || '',
                consumption_unit: newValue?.consumption_unit || null,
            };

            if (newValue.avatar) {
                imagePreview.value = newValue.avatar;
            }
        }
    },
    { immediate: true, deep: true }
);

// Handlers for child component updates (v-model will directly update `modality.schedules` and `modality.customDates`)
const handleSchedulesUpdated = (newSchedules) => {
    console.log('Schedules updated:', newSchedules);
    modality.value.schedules = newSchedules;
};

const handleCustomDatesUpdated = (newCustomDates) => {
    console.log('Custom dates updated:', newCustomDates);
    modality.value.customDates = newCustomDates;
};

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) {
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        errors.value = { ...errors.value, avatar: 'Image must be less than 2MB' };
        e.target.value = '';
        return;
    }

    if (errors.value.avatar) {
        errors.value = { ...errors.value, avatar: null };
    }

    modality.value = {
        ...modality.value,
        avatar: file
    };

    const previewURL = URL.createObjectURL(file);
    imagePreview.value = previewURL;
};

// Mirroring handleFrequencySelectionChange from Doctor model
const handleFrequencySelectionChange = () => {
    if (modality.value.frequency === 'Monthly') {
        modality.value.schedules = []; // Clear schedules if switching to Monthly
    } else {
        modality.value.customDates = []; // Clear customDates if switching from Monthly
    }
};

// Watch selectedMonths to update modality.appointmentBookingWindow, mirroring Doctor model
watch(
    selectedMonths,
    (newSelectedMonths) => {
        modality.value.appointmentBookingWindow = newSelectedMonths.map((month) => ({
            month: month.value, // Use 'month' property for consistency with backend
            year: month.year, // Include year as per doctor model
            is_available: month.is_available,
        }));
    },
    { deep: true }
);

onUnmounted(() => {
    if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreview.value);
    }
});

const submitForm = async (values, { setErrors, resetForm }) => {
    isLoading.value = true;
    try {
        console.log('Form values from vee-validate:', values);
        console.log('Modality object:', modality.value);

        // Extract schedules and customDates properly
        let schedulesData = [];
        let customDatesData = [];

        // Handle schedules based on the structure returned by child components
        if (modality.value.schedules) {
            if (Array.isArray(modality.value.schedules)) {
                schedulesData = modality.value.schedules;
            } else if (modality.value.schedules.schedules && Array.isArray(modality.value.schedules.schedules)) {
                schedulesData = modality.value.schedules.schedules;
            }
        }

        // Handle custom dates based on the structure returned by child components
        if (modality.value.customDates) {
            if (Array.isArray(modality.value.customDates)) {
                customDatesData = modality.value.customDates;
            } else if (modality.value.customDates.schedules && Array.isArray(modality.value.customDates.schedules)) {
                customDatesData = modality.value.customDates.schedules;
            }
        }

        // Prepare the payload
        const payload = {
            ...values,
            id: modality.value.id || null,
            modality_type_id: parseInt(modality.value.modality_type_id) || null,
            specialization_id: parseInt(modality.value.specialization_id) || null,
            port: modality.value.port ? parseInt(modality.value.port) : null,
            physical_location_id: modality.value.physical_location_id ? parseInt(modality.value.physical_location_id) : null,
            operational_status: modality.value.operational_status || 'Working',
            frequency: modality.value.frequency || 'Weekly',
            time_slot_duration: modality.value.time_slot_duration ? parseInt(modality.value.time_slot_duration) : null,
            slot_type: modality.value.slot_type || 'minutes',
            booking_window: modality.value.booking_window ? parseInt(modality.value.booking_window) : null,
            notes: modality.value.notes || '',
            is_active: modality.value.is_active ? 1 : 0,
            include_time: modality.value.include_time ? 1 : 0,
            start_time_force: modality.value.start_time_force || null,
            end_time_force: modality.value.end_time_force || null,
            number_of_patients: modality.value.number_of_patients ? parseInt(modality.value.number_of_patients) : null,
            appointment_booking_window: modality.value.appointmentBookingWindow || [],
            schedules: schedulesData,
            customDates: customDatesData,

            // New fields
            consumption_type: modality.value.consumption_type || '',
            consumption_unit: modality.value.consumption_unit !== null ? parseInt(modality.value.consumption_unit) : null,
        };

        console.log('Final payload being sent:', payload);
        console.log('Schedules data being sent:', payload.schedules);
        console.log('CustomDates data being sent:', payload.customDates);

        // Handle file upload separately if avatar exists
        if (modality.value.avatar instanceof File) {
            const formData = new FormData();

            // Add all other fields to FormData
            Object.keys(payload).forEach(key => {
                if (key === 'schedules' || key === 'customDates' || key === 'appointment_booking_window') {
                    // Convert array of objects to JSON string for FormData
                    const jsonData = JSON.stringify(payload[key]);
                    console.log(`Adding ${key} to FormData:`, jsonData);
                    formData.append(key, jsonData);
                } else if (payload[key] !== null && payload[key] !== undefined) {
                    formData.append(key, payload[key]);
                }
            });

            formData.append('avatar', modality.value.avatar);

            if (isEditMode.value) {
                formData.append('_method', 'PUT');
            }

            // Log FormData contents for debugging
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}:`, value);
            }

            const url = isEditMode.value ? `/api/modalities/${modality.value.id}` : '/api/modalities';
            const response = await axios.post(url, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            console.log('Server response:', response.data);
        } else {
            // No file upload, send JSON
            console.log('Sending JSON payload (no file):', payload);
            
            const url = isEditMode.value ? `/api/modalities/${modality.value.id}` : '/api/modalities';

            let response;
            if (isEditMode.value) {
                response = await axios.put(url, payload, {
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });
            } else {
                response = await axios.post(url, payload, {
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });
            }

            console.log('Server response:', response.data);
        }

        toaster.success(`Modality ${isEditMode.value ? 'updated' : 'added'} successfully`);
        isLoading.value = false;
        handleModalityUpdate();
        resetForm();
    } catch (error) {
        console.error('Error submitting form:', error);
        console.error('Error response:', error.response?.data);

        if (error.response?.data?.errors) {
            console.log('Validation errors from server:', error.response.data.errors);
            setErrors(error.response.data.errors);
            errors.value = error.response.data.errors;
        } else if (error.response?.data?.message) {
            toaster.error(error.response.data.message);
        } else {
            toaster.error('An unexpected error occurred');
        }
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchDropdownOptions();
});
</script>

<template>
    <div class="modal fade overflow-auto" :class="{ show: showModal }" tabindex="-1" aria-labelledby="modalityModalLabel"
        aria-hidden="true" v-if="showModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEditMode ? 'Edit Modality' : 'Add New Modality' }}</h5>
                    <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <Form v-slot="{ errors: validationErrors }" @submit="submitForm"
                        :validation-schema="getModalitySchema(isEditMode)">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active"
                                    v-model="modality.is_active" />
                                <label class="form-check-label" for="is_active">
                                    Active Modality
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label for="name" class="form-label fs-5">Name</label>
                                <Field type="text" id="name" name="name" :class="{ 'is-invalid': validationErrors.name }"
                                    v-model="modality.name" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.name }}</span>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label for="internal_code" class="form-label fs-5">Internal Code</label>
                                <Field type="text" id="internal_code" name="internal_code"
                                    :class="{ 'is-invalid': validationErrors.internal_code }"
                                    v-model="modality.internal_code" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.internal_code }}</span>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4 pt-3">
                                    <input type="checkbox" id="include_time" v-model="modality.include_time" class="form-check-input" />
                                    <label for="include_time" class="form-check-label fs-6">
                                        Include Time in print Appointment List
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="modality_type_id" class="form-label fs-5">Modality Type</label>
                                <Field as="select" id="modality_type_id" name="modality_type_id"
                                    :class="{ 'is-invalid': validationErrors.modality_type_id }"
                                    v-model="modality.modality_type_id" class="form-control form-control-md">
                                    <option value="">Select Modality Type</option>
                                    <option v-for="type in modalityTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </Field>
                                <span class="text-sm invalid-feedback">{{ validationErrors.modality_type_id }}</span>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="operational_status" class="form-label fs-5">Operational Status</label>
                                <Field as="select" id="operational_status" name="operational_status"
                                    :class="{ 'is-invalid': validationErrors.operational_status }"
                                    v-model="modality.operational_status" class="form-control form-control-md">
                                    <option value="Working">Working</option>
                                    <option value="Not Working">Not Working</option>
                                    <option value="In Maintenance">In Maintenance</option>
                                </Field>
                                <span class="text-sm invalid-feedback">{{ validationErrors.operational_status }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="dicom_ae_title" class="form-label fs-5">DICOM AE Title</label>
                                <Field type="text" id="dicom_ae_title" name="dicom_ae_title"
                                    :class="{ 'is-invalid': validationErrors.dicom_ae_title }"
                                    v-model="modality.dicom_ae_title" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.dicom_ae_title }}</span>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="port" class="form-label fs-5">Port</label>
                                <Field type="number" id="port" name="port"
                                    :class="{ 'is-invalid': validationErrors.port }" v-model="modality.port"
                                    class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.port }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="physical_location_id" class="form-label fs-5">Physical Location ID</label>
                                <Field type="number" id="physical_location_id" name="physical_location_id"
                                    :class="{ 'is-invalid': validationErrors.physical_location_id }"
                                    v-model="modality.physical_location_id" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.physical_location_id }}</span>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="specialization_id" class="form-label fs-5">Specialization</label>
                                <Field as="select" id="specialization_id" name="specialization_id"
                                    :class="{ 'is-invalid': validationErrors.specialization_id }"
                                    v-model="modality.specialization_id" class="form-control form-control-md">
                                    <option value="">Select Specialization</option>
                                    <option v-for="spec in specializations" :key="spec.id" :value="spec.id">
                                        {{ spec.name }}
                                    </option>
                                </Field>
                                <span class="text-sm invalid-feedback">{{ validationErrors.specialization_id }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="integration_protocol" class="form-label fs-5">Integration Protocol</label>
                                <Field type="text" id="integration_protocol" name="integration_protocol"
                                    :class="{ 'is-invalid': validationErrors.integration_protocol }"
                                    v-model="modality.integration_protocol" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.integration_protocol }}</span>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="connection_configuration" class="form-label fs-5">Connection
                                    Configuration</label>
                                <Field type="text" id="connection_configuration" name="connection_configuration"
                                    :class="{ 'is-invalid': validationErrors.connection_configuration }"
                                    v-model="modality.connection_configuration" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.connection_configuration }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="data_retrieval_method" class="form-label fs-5">Data Retrieval Method</label>
                                <Field type="text" id="data_retrieval_method" name="data_retrieval_method"
                                    :class="{ 'is-invalid': validationErrors.data_retrieval_method }"
                                    v-model="modality.data_retrieval_method" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.data_retrieval_method }}</span>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="ip_address" class="form-label fs-5">IP Address</label>
                                <Field type="text" id="ip_address" name="ip_address"
                                    :class="{ 'is-invalid': validationErrors.ip_address }"
                                    v-model="modality.ip_address" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.ip_address }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="frequency" class="form-label fs-5">Frequency</label>
                                <Field as="select" id="frequency" name="frequency" :class="{ 'is-invalid': validationErrors.frequency }"
                                    v-model="modality.frequency" @change="handleFrequencySelectionChange"
                                    class="form-control form-control-md">
                                    <option value="" disabled>Select Frequency</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="Monthly">Monthly</option>
                                </Field>
                                <span class="invalid-feedback text-sm" v-if="validationErrors.frequency">
                                    {{ validationErrors.frequency }}
                                </span>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="slot_type" class="form-label fs-5">Slot Type</label>
                                <Field as="select" id="slot_type" name="slot_type"
                                    :class="{ 'is-invalid': validationErrors.slot_type }" v-model="modality.slot_type"
                                    class="form-control form-control-md">
                                    <option value="minutes">Minutes</option>
                                    <option value="days">Days</option>
                                </Field>
                                <span class="text-sm invalid-feedback">{{ validationErrors.slot_type }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4" v-if="modality.slot_type === 'minutes'">
                                <label for="time_slot_duration" class="form-label fs-5">Time Slot Duration
                                    (minutes)</label>
                                <Field type="number" id="time_slot_duration" name="time_slot_duration"
                                    :class="{ 'is-invalid': validationErrors.time_slot_duration }"
                                    v-model="modality.time_slot_duration" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.time_slot_duration }}</span>
                            </div>
                            <div class="col-md-6 mb-4" v-if="modality.slot_type === 'days'">
                                <label for="booking_window" class="form-label fs-5">Booking Window (Days)</label>
                                <Field type="number" id="booking_window" name="booking_window"
                                    :class="{ 'is-invalid': validationErrors.booking_window }"
                                    v-model="modality.booking_window" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.booking_window }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label for="notes" class="form-label fs-5">Notes</label>
                                <Field as="textarea" id="notes" name="notes" v-model="modality.notes" rows="3"
                                    class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.notes }}</span>
                            </div>
                        </div>

                        <!-- Appointment Force Fields -->
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label for="start_time_force" class="form-label fs-5">Force Start Time</label>
                                <Field type="time" id="start_time_force" name="start_time_force"
                                    :class="{ 'is-invalid': validationErrors.start_time_force }"
                                    v-model="modality.start_time_force" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.start_time_force }}</span>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label for="end_time_force" class="form-label fs-5">Force End Time</label>
                                <Field type="time" id="end_time_force" name="end_time_force"
                                    :class="{ 'is-invalid': validationErrors.end_time_force }"
                                    v-model="modality.end_time_force" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.end_time_force }}</span>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label for="number_of_patients" class="form-label fs-5">Number of Patients</label>
                                <Field type="number" id="number_of_patients" name="number_of_patients"
                                    :class="{ 'is-invalid': validationErrors.number_of_patients }"
                                    v-model="modality.number_of_patients" class="form-control form-control-md" />
                                <span class="text-sm invalid-feedback">{{ validationErrors.number_of_patients }}</span>
                            </div>
                        </div>

                        <!-- New fields for consumption type and unit -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="consumption_type" class="form-label fs-5">Consumption Type</label>
                                <Field as="select" id="consumption_type" name="consumption_type"
                                    v-model="modality.consumption_type" class="form-control form-control-md">
                                    <option value="">Select Consumption Type</option>
                                    <option value="big_battery">Big Battery</option>
                                    <option value="small_battery">Small Battery</option>
                                </Field>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="consumption_unit" class="form-label fs-5">Consumption Unit</label>
                                <Field type="number" id="consumption_unit" name="consumption_unit"
                                    v-model="modality.consumption_unit" class="form-control form-control-md" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label fs-5">Appointment Booking Window</label>
                                <AppointmentBookingWindowModel :isEditMode="isEditMode"
                                    :appointment-booking-window="modality.appointmentBookingWindow"
                                    v-model="selectedMonths" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12" v-if="modality.frequency === 'Daily' || modality.frequency === 'Weekly'">
                                <DoctorSchedules :modalityId="modality.id" :existingSchedules="modality.schedules"
                                    :patients_based_on_time="false" :time_slot="modality.time_slot_duration"
                                    v-model="modality.schedules" @schedulesUpdated="handleSchedulesUpdated" />
                            </div>
                            <div class="col-md-12 mb-4" v-if="modality.frequency === 'Monthly'">
                                <label class="form-label fs-5">Custom Dates</label>
                                <CustomDates :modalityId="modality.id" :existingSchedules="modality.schedules"
                                    v-model="modality.customDates" :patients_based_on_time="false"
                                    :time_slot="modality.time_slot_duration"
                                    @schedulesUpdated="handleCustomDatesUpdated" />
                            </div>
                        </div>

                        <div v-if="isLoading" class="modal-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-outline-primary" :disabled="isLoading">
                                {{ isEditMode ? 'Update Modality' : 'Add Modality' }}
                                <span v-if="isLoading" class="spinner-border spinner-border-sm ms-2" role="status"
                                    aria-hidden="true"></span>
                            </button>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal.show {
    display: block;
    background-color: rgba(0, 0, 0, 0.5);
}

.input-group {
    display: flex;
    align-items: center;
}

.invalid-feedback {
    display: block;
    color: red;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.modal-dialog {
    max-width: 800px;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1050; /* Above modal content */
}
</style>