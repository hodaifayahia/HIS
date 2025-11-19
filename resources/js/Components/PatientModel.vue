<script setup>
import { ref, computed, defineProps, watch } from 'vue';
import { Form, Field } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from './toster';

// PrimeVue components
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import ProgressBar from 'primevue/progressbar';
import Divider from 'primevue/divider';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

const props = defineProps({
    showModal: {
        type: Boolean,
        required: true,
    },
    specData: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close', 'patientsUpdate', 'specUpdate']);
const toastr = useToastr();

const Patient = ref({
    id: props.specData?.id || null,
    Firstname: props.specData?.Firstname || '',
    Lastname: props.specData?.Lastname || '',
    phone: props.specData?.phone || '',
    fax_number: props.specData?.fax_number || '',
    email: props.specData?.email || '',
    address: props.specData?.address || '',
    city: props.specData?.city || '',
    postal_code: props.specData?.postal_code || '',
    Idnum: props.specData?.Idnum || '',
    identity_document_type: props.specData?.identity_document_type || null,
    identity_issued_on: props.specData?.identity_issued_on || null,
    identity_issued_by: props.specData?.identity_issued_by || '',
    passport_number: props.specData?.passport_number || '',
    professional_badge_number: props.specData?.professional_badge_number || '',
    foreigner_card_number: props.specData?.foreigner_card_number || '',
    nss: props.specData?.nss || '',
    Parent: props.specData?.Parent || '',
    dateOfBirth: props.specData?.dateOfBirth || null,
    birth_place: props.specData?.birth_place || '',
    is_birth_place_presumed: props.specData?.is_birth_place_presumed || false,
    additional_ids: props.specData?.additional_ids || null,
    gender: props.specData?.gender || 'male',
    height: props.specData?.height || null,
    weight: props.specData?.weight || null,
    blood_group: props.specData?.blood_group || '',
    marital_status: props.specData?.marital_status || '',
    mother_firstname: props.specData?.mother_firstname || '',
    mother_lastname: props.specData?.mother_lastname || '',
    balance: props.specData?.balance || null,
    is_faithful: props.specData?.is_faithful || false,
    firstname_ar: props.specData?.firstname_ar || '',
    lastname_ar: props.specData?.lastname_ar || '',
    other_clinical_info: props.specData?.other_clinical_info || '',
    created_by: props.specData?.created_by || null,
});

const isEditMode = computed(() => !!props.specData?.id);

// Loading state for fetching patient details
const isLoadingPatientData = ref(false);

// Function to fetch complete patient information from backend
const fetchCompletePatientData = async (patientId) => {
    if (!patientId) return;
    
    try {
        isLoadingPatientData.value = true;
        const response = await axios.get(`/api/patients/${patientId}`);
        const completePatientData = response.data.data || response.data;
        
        // Update Patient ref with complete data from backend
        Patient.value = {
            id: completePatientData.id || null,
            Firstname: completePatientData.Firstname || '',
            Lastname: completePatientData.Lastname || '',
            phone: completePatientData.phone || '',
            fax_number: completePatientData.fax_number || '',
            email: completePatientData.email || '',
            address: completePatientData.address || '',
            city: completePatientData.city || '',
            postal_code: completePatientData.postal_code || '',
            Idnum: completePatientData.Idnum || '',
            identity_document_type: completePatientData.identity_document_type || null,
            identity_issued_on: completePatientData.identity_issued_on || null,
            identity_issued_by: completePatientData.identity_issued_by || '',
            passport_number: completePatientData.passport_number || '',
            professional_badge_number: completePatientData.professional_badge_number || '',
            foreigner_card_number: completePatientData.foreigner_card_number || '',
            nss: completePatientData.nss || '',
            Parent: completePatientData.Parent || '',
            dateOfBirth: completePatientData.dateOfBirth || null,
            birth_place: completePatientData.birth_place || '',
            is_birth_place_presumed: completePatientData.is_birth_place_presumed || false,
            additional_ids: completePatientData.additional_ids || null,
            gender: completePatientData.gender || 'male',
            height: completePatientData.height || null,
            weight: completePatientData.weight || null,
            blood_group: completePatientData.blood_group || '',
            marital_status: completePatientData.marital_status || '',
            mother_firstname: completePatientData.mother_firstname || '',
            mother_lastname: completePatientData.mother_lastname || '',
            balance: completePatientData.balance || null,
            is_faithful: completePatientData.is_faithful || false,
            firstname_ar: completePatientData.firstname_ar || '',
            lastname_ar: completePatientData.lastname_ar || '',
            other_clinical_info: completePatientData.other_clinical_info || '',
            created_by: completePatientData.created_by || null,
        };
    } catch (error) {
        console.error('Error fetching patient data:', error);
        toastr.error('Failed to load patient information');
    } finally {
        isLoadingPatientData.value = false;
    }
};

// Document type options
const documentTypes = ref([
    { label: 'National Card', value: 'national_card' },
    { label: 'Passport', value: 'passport' },
    { label: 'Foreigner Card', value: 'foreigner_card' },
    { label: 'Driver\'s License', value: 'drivers_license' },
    { label: 'Other', value: 'other' },
]);

// Blood group options
const bloodGroups = ref([
    { label: 'A+', value: 'A+' },
    { label: 'A-', value: 'A-' },
    { label: 'B+', value: 'B+' },
    { label: 'B-', value: 'B-' },
    { label: 'AB+', value: 'AB+' },
    { label: 'AB-', value: 'AB-' },
    { label: 'O+', value: 'O+' },
    { label: 'O-', value: 'O-' },
]);

// Marital status options
const maritalStatuses = ref([
    { label: 'Single', value: 'single' },
    { label: 'Married', value: 'married' },
    { label: 'Divorced', value: 'divorced' },
    { label: 'Widowed', value: 'widowed' },
]);

watch(
    () => props.specData,
    (newValue) => {
        if (newValue?.id) {
            // In edit mode: fetch complete patient data from backend
            fetchCompletePatientData(newValue.id);
        } else {
            // In create mode: use provided data or defaults
            Patient.value = {
                id: newValue?.id || null,
                Firstname: newValue?.Firstname || '',
                Lastname: newValue?.Lastname || '',
                phone: newValue?.phone || '',
                fax_number: newValue?.fax_number || '',
                email: newValue?.email || '',
                address: newValue?.address || '',
                city: newValue?.city || '',
                postal_code: newValue?.postal_code || '',
                Idnum: newValue?.Idnum || '',
                identity_document_type: newValue?.identity_document_type || null,
                identity_issued_on: newValue?.identity_issued_on || null,
                identity_issued_by: newValue?.identity_issued_by || '',
                passport_number: newValue?.passport_number || '',
                professional_badge_number: newValue?.professional_badge_number || '',
                foreigner_card_number: newValue?.foreigner_card_number || '',
                nss: newValue?.nss || '',
                Parent: newValue?.Parent || '',
                dateOfBirth: newValue?.dateOfBirth || null,
                birth_place: newValue?.birth_place || '',
                is_birth_place_presumed: newValue?.is_birth_place_presumed || false,
                additional_ids: newValue?.additional_ids || null,
                gender: newValue?.gender || 'male',
                height: newValue?.height || null,
                weight: newValue?.weight || null,
                blood_group: newValue?.blood_group || '',
                marital_status: newValue?.marital_status || '',
                mother_firstname: newValue?.mother_firstname || '',
                mother_lastname: newValue?.mother_lastname || '',
                balance: newValue?.balance || null,
                is_faithful: newValue?.is_faithful || false,
                firstname_ar: newValue?.firstname_ar || '',
                lastname_ar: newValue?.lastname_ar || '',
                other_clinical_info: newValue?.other_clinical_info || '',
                created_by: newValue?.created_by || null,
            };
        }
    },
    { immediate: true, deep: true }
);

const PatientSchema = yup.object({
    Firstname: yup
        .string()
        .required('First name is required')
        .min(2, 'First name must be at least 2 characters')
        .max(50, 'First name cannot exceed 50 characters'),
    Lastname: yup
        .string()
        .required('Last name is required')
        .min(2, 'Last name must be at least 2 characters')
        .max(50, 'Last name cannot exceed 50 characters'),
    Parent: yup
        .string()
        .nullable()
        .notRequired(),
    phone: yup
        .string()
        .nullable()
        .notRequired(),
    fax_number: yup
        .string()
        .nullable()
        .notRequired(),
    email: yup
        .string()
        .email('Invalid email address')
        .nullable()
        .notRequired(),
    address: yup
        .string()
        .nullable()
        .notRequired(),
    city: yup
        .string()
        .nullable()
        .notRequired(),
    postal_code: yup
        .string()
        .nullable()
        .notRequired(),
    Idnum: yup
        .string()
        .nullable()
        .notRequired()
        .matches(/^[A-Za-z0-9]{5,20}$/, 'ID Number must be 5-20 alphanumeric characters')
        .transform((value, originalValue) => originalValue === '' ? null : value),
    identity_document_type: yup
        .string()
        .nullable()
        .notRequired()
        .oneOf(['national_card', 'passport', 'foreigner_card', 'drivers_license', 'other'], 'Invalid document type'),
    identity_issued_on: yup
        .date()
        .nullable()
        .notRequired()
        .transform((value, originalValue) => originalValue === '' ? null : value),
    identity_issued_by: yup
        .string()
        .nullable()
        .notRequired(),
    passport_number: yup
        .string()
        .nullable()
        .notRequired(),
    professional_badge_number: yup
        .string()
        .nullable()
        .notRequired(),
    foreigner_card_number: yup
        .string()
        .nullable()
        .notRequired(),
    nss: yup
        .string()
        .nullable()
        .notRequired(),
    dateOfBirth: yup
        .date()
        .nullable()
        .notRequired()
        .max(new Date(), 'Date of Birth cannot be in the future')
        .transform((value, originalValue) => originalValue === '' ? null : value),
    birth_place: yup
        .string()
        .nullable()
        .notRequired(),
    is_birth_place_presumed: yup
        .boolean()
        .notRequired(),
    additional_ids: yup
        .mixed()
        .nullable()
        .notRequired(),
    gender: yup
        .string()
        .required('Gender is required')
        .oneOf(['0', '1', 0, 1], 'Invalid gender selection'),

    height: yup
        .number()
        .nullable()
        .notRequired()
        .min(0, 'Height must be greater than 0'),
    weight: yup
        .number()
        .nullable()
        .notRequired()
        .min(0, 'Weight must be greater than 0'),
    blood_group: yup
        .string()
        .nullable()
        .notRequired(),
    marital_status: yup
        .string()
        .nullable()
        .notRequired(),
    mother_firstname: yup
        .string()
        .nullable()
        .notRequired(),
    mother_lastname: yup
        .string()
        .nullable()
        .notRequired(),
    balance: yup
        .number()
        .nullable()
        .notRequired(),
    is_faithful: yup
        .boolean()
        .notRequired(),
    firstname_ar: yup
        .string()
        .nullable()
        .notRequired(),
    lastname_ar: yup
        .string()
        .nullable()
        .notRequired(),
    other_clinical_info: yup
        .string()
        .nullable()
        .notRequired(),
    created_by: yup
        .number()
        .nullable()
        .notRequired(),
});

const closeModal = () => {
    emit('close');
};

const handleBackendErrors = (error) => {
    if (error.response?.data?.errors) {
        Object.values(error.response.data.errors).forEach((messages) => {
            toastr.error(messages[0]);
        });
    } else if (error.response?.data?.message) {
        toastr.error(error.response.data.message);
    } else {
        toastr.error('An unexpected error occurred');
    }
};

const emitUpdate = (newPatient) => {
    emit('specUpdate', newPatient);
};

const submitForm = async (values) => {
    try {
        const submissionData = { ...values, id: Patient.value.id };

        if (isEditMode.value) {
            const response = await axios.put(`/api/patients/${submissionData.id}`, submissionData);
            emitUpdate(response.data.data);
            toastr.success('Patient updated successfully');
        } else {
            const response = await axios.post('/api/patients', submissionData);
            emitUpdate(response.data.data);
            toastr.success('Patient added successfully');
        }

        emit('patientsUpdate');
        closeModal();
    } catch (error) {
        handleBackendErrors(error);
    }
};
</script>

<template>
    <Dialog
        :visible="showModal"
        modal
        :header="isEditMode ? 'Edit Patient Information' : 'Add New Patient'"
        :style="{ width: '90vw', maxWidth: '1400px' }"
        :closable="true"
        @hide="closeModal"
        class="tw-p-0"
    >
        <!-- Loading Indicator for Edit Mode -->
        <div v-if="isEditMode && isLoadingPatientData" class="tw-flex tw-items-center tw-justify-center tw-p-8">
            <ProgressBar mode="indeterminate" style="height: 6px"></ProgressBar>
            <span class="tw-ml-3 tw-text-gray-600">Loading patient information...</span>
        </div>

        <Form
            v-if="!isLoadingPatientData"
            :validation-schema="PatientSchema"
            @submit="submitForm"
            v-slot="{ errors, isSubmitting }"
        >
            <!-- Progress Bar -->
            <div class="tw-mb-6">
                <ProgressBar
                    :value="25"
                    :showValue="false"
                    class="tw-h-2 tw-rounded-full"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
                />
            </div>

            <!-- Tabbed Interface -->
            <TabView class="tw-border-0">
                <!-- Personal Information Tab -->
                <TabPanel header="Personal Information" class="tw-p-0">
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6 tw-p-6">
                        <!-- Basic Information Card -->
                        <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl">
                            <template #title>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-user tw-text-blue-600"></i>
                                    <span class="tw-font-semibold tw-text-gray-800">Basic Information</span>
                                </div>
                            </template>

                            <template #content>
                                <div class="tw-space-y-4">
                                    <!-- First Name & Last Name -->
                                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                First Name *
                                            </label>
                                            <Field name="Firstname" v-model="Patient.Firstname">
                                                <template #default="{ field, errorMessage }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter first name"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                        :class="{ 'tw-border-red-500': errorMessage }"
                                                    />
                                                    <small v-if="errorMessage" class="tw-text-red-500 tw-mt-1 tw-block">
                                                        {{ errorMessage }}
                                                    </small>
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Last Name *
                                            </label>
                                            <Field name="Lastname" v-model="Patient.Lastname">
                                                <template #default="{ field, errorMessage }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter last name"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                        :class="{ 'tw-border-red-500': errorMessage }"
                                                    />
                                                    <small v-if="errorMessage" class="tw-text-red-500 tw-mt-1 tw-block">
                                                        {{ errorMessage }}
                                                    </small>
                                                </template>
                                            </Field>
                                        </div>
                                    </div>

                                    <!-- Parent Name -->
                                    <div>
                                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                            Parent Name
                                        </label>
                                        <Field name="Parent" v-model="Patient.Parent">
                                            <template #default="{ field }">
                                                <InputText
                                                    v-bind="field"
                                                    placeholder="Enter parent name"
                                                    class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                />
                                            </template>
                                        </Field>
                                    </div>

                                    <!-- Gender Selection -->
                                    <div>
                                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-3">
                                            Gender *
                                        </label>
                                        <div class="tw-flex tw-gap-6">
                                            <div class="tw-flex tw-items-center">
                                                <RadioButton
                                                    id="gender-male"
                                                    name="gender"
                                                    value="1"
                                                    v-model="Patient.gender"
                                                />
                                                <label for="gender-male" class="tw-ml-2 tw-flex tw-items-center tw-gap-2 tw-cursor-pointer">
                                                    <i class="pi pi-male tw-text-blue-600"></i>
                                                    Male
                                                </label>
                                            </div>
                                            <div class="tw-flex tw-items-center">
                                                <RadioButton
                                                    id="gender-female"
                                                    name="gender"
                                                    value="0"
                                                    v-model="Patient.gender"
                                                />
                                                <label for="gender-female" class="tw-ml-2 tw-flex tw-items-center tw-gap-2 tw-cursor-pointer">
                                                    <i class="pi pi-female tw-text-pink-600"></i>
                                                    Female
                                                </label>
                                            </div>
                                        </div>
                                        <Field name="gender" v-model="Patient.gender" type="hidden" />
                                        <small v-if="errors.gender" class="tw-text-red-500 tw-mt-1 tw-block">
                                            {{ errors.gender }}
                                        </small>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <!-- Birth Information Card -->
                        <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl">
                            <template #title>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-calendar tw-text-green-600"></i>
                                    <span class="tw-font-semibold tw-text-gray-800">Birth Information</span>
                                </div>
                            </template>

                            <template #content>
                                <div class="tw-space-y-4">
                                    <!-- Date of Birth -->
                                    <div>
                                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                            Date of Birth
                                        </label>
                                        <Calendar
                                            v-model="Patient.dateOfBirth"
                                            placeholder="Select date"
                                            dateFormat="yy-mm-dd"
                                            class="tw-w-full tw-rounded-lg"
                                            :maxDate="new Date()"
                                        />
                                    </div>

                                    <!-- Birth Place -->
                                    <div>
                                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                            Birth Place
                                        </label>
                                        <Field name="birth_place" v-model="Patient.birth_place">
                                            <template #default="{ field }">
                                                <InputText
                                                    v-bind="field"
                                                    placeholder="Enter birth place"
                                                    class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                />
                                            </template>
                                        </Field>
                                    </div>

                                    <!-- Birth Place Presumed -->
                                    <div class="tw-flex tw-items-center tw-gap-2">
                                        <Checkbox
                                            id="birth-presumed"
                                            v-model="Patient.is_birth_place_presumed"
                                            :binary="true"
                                        />
                                        <label for="birth-presumed" class="tw-text-sm tw-font-medium tw-text-gray-700 tw-cursor-pointer">
                                            Birth place is presumed
                                        </label>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- Contact Information Tab -->
                <TabPanel header="Contact Information" class="tw-p-0">
                    <div class="tw-p-6">
                        <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl">
                            <template #title>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-phone tw-text-green-600"></i>
                                    <span class="tw-font-semibold tw-text-gray-800">Contact Details</span>
                                </div>
                            </template>

                            <template #content>
                                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                                    <!-- Contact Information -->
                                    <div class="tw-space-y-4">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Phone Number
                                            </label>
                                            <Field name="phone" v-model="Patient.phone">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter phone number"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Fax Number
                                            </label>
                                            <Field name="fax_number" v-model="Patient.fax_number">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter fax number"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Email Address
                                            </label>
                                            <Field name="email" v-model="Patient.email">
                                                <template #default="{ field, errorMessage }">
                                                    <InputText
                                                        v-bind="field"
                                                        type="email"
                                                        placeholder="Enter email address"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                        :class="{ 'tw-border-red-500': errorMessage }"
                                                    />
                                                    <small v-if="errorMessage" class="tw-text-red-500 tw-mt-1 tw-block">
                                                        {{ errorMessage }}
                                                    </small>
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                NSS Number
                                            </label>
                                            <Field name="nss" v-model="Patient.nss">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter NSS number"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>
                                    </div>

                                    <!-- Address Information -->
                                    <div class="tw-space-y-4">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Address
                                            </label>
                                            <Field name="address" v-model="Patient.address">
                                                <template #default="{ field }">
                                                    <Textarea
                                                        v-bind="field"
                                                        placeholder="Enter full address"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                        rows="3"
                                                    />
                                                </template>
                                            </Field>
                                        </div>

                                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                                            <div>
                                                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                    City
                                                </label>
                                                <Field name="city" v-model="Patient.city">
                                                    <template #default="{ field }">
                                                        <InputText
                                                            v-bind="field"
                                                            placeholder="Enter city"
                                                            class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                        />
                                                    </template>
                                                </Field>
                                            </div>

                                            <div>
                                                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                    Postal Code
                                                </label>
                                                <Field name="postal_code" v-model="Patient.postal_code">
                                                    <template #default="{ field }">
                                                        <InputText
                                                            v-bind="field"
                                                            placeholder="Enter postal code"
                                                            class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                        />
                                                    </template>
                                                </Field>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- Identity Documents Tab -->
                <TabPanel header="Identity Documents" class="tw-p-0">
                    <div class="tw-p-6">
                        <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl">
                            <template #title>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-id-card tw-text-orange-600"></i>
                                    <span class="tw-font-semibold tw-text-gray-800">Identity Documents</span>
                                </div>
                            </template>

                            <template #content>
                                <div class="tw-space-y-6">
                                    <!-- Primary ID Information -->
                                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                ID Number
                                            </label>
                                            <Field name="Idnum" v-model="Patient.Idnum">
                                                <template #default="{ field, errorMessage }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter ID number"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                        :class="{ 'tw-border-red-500': errorMessage }"
                                                    />
                                                    <small v-if="errorMessage" class="tw-text-red-500 tw-mt-1 tw-block">
                                                        {{ errorMessage }}
                                                    </small>
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Document Type
                                            </label>
                                            <Dropdown
                                                v-model="Patient.identity_document_type"
                                                :options="documentTypes"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="Select document type"
                                                class="tw-w-full tw-rounded-lg"
                                            />
                                        </div>
                                    </div>

                                    <!-- Document Details -->
                                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Issued On
                                            </label>
                                            <Calendar
                                                v-model="Patient.identity_issued_on"
                                                placeholder="Select issue date"
                                                dateFormat="yy-mm-dd"
                                                class="tw-w-full tw-rounded-lg"
                                            />
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Issued By
                                            </label>
                                            <Field name="identity_issued_by" v-model="Patient.identity_issued_by">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter issuing authority"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>
                                    </div>

                                    <Divider />

                                    <!-- Additional Documents -->
                                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Passport Number
                                            </label>
                                            <Field name="passport_number" v-model="Patient.passport_number">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter passport number"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Professional Badge
                                            </label>
                                            <Field name="professional_badge_number" v-model="Patient.professional_badge_number">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter badge number"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Foreigner Card
                                            </label>
                                            <Field name="foreigner_card_number" v-model="Patient.foreigner_card_number">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Enter foreigner card number"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- Medical Information Tab -->
                <TabPanel header="Medical Information" class="tw-p-0">
                    <div class="tw-p-6">
                        <Card class="tw-shadow-lg tw-border-0 tw-rounded-xl">
                            <template #title>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-heart tw-text-red-600"></i>
                                    <span class="tw-font-semibold tw-text-gray-800">Medical Information</span>
                                </div>
                            </template>

                            <template #content>
                                <div class="tw-space-y-6">
                                    <!-- Physical Measurements -->
                                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Height (cm)
                                            </label>
                                            <Field name="height" v-model="Patient.height">
                                                <template #default="{ field, errorMessage }">
                                                    <InputNumber
                                                        v-bind="field"
                                                        placeholder="Height in cm"
                                                        :min="0"
                                                        suffix=" cm"
                                                        class="tw-w-full"
                                                        :class="{ 'p-invalid': errorMessage }"
                                                    />
                                                    <small v-if="errorMessage" class="tw-text-red-500 tw-mt-1 tw-block">
                                                        {{ errorMessage }}
                                                    </small>
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Weight (kg)
                                            </label>
                                            <Field name="weight" v-model="Patient.weight">
                                                <template #default="{ field, errorMessage }">
                                                    <InputNumber
                                                        v-bind="field"
                                                        placeholder="Weight in kg"
                                                        :min="0"
                                                        suffix=" kg"
                                                        class="tw-w-full"
                                                        :class="{ 'p-invalid': errorMessage }"
                                                    />
                                                    <small v-if="errorMessage" class="tw-text-red-500 tw-mt-1 tw-block">
                                                        {{ errorMessage }}
                                                    </small>
                                                </template>
                                            </Field>
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Blood Group
                                            </label>
                                            <Dropdown
                                                v-model="Patient.blood_group"
                                                :options="bloodGroups"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="Select blood group"
                                                class="tw-w-full tw-rounded-lg"
                                            />
                                        </div>
                                    </div>

                                    <!-- Additional Medical Info -->
                                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Marital Status
                                            </label>
                                            <Dropdown
                                                v-model="Patient.marital_status"
                                                :options="maritalStatuses"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="Select marital status"
                                                class="tw-w-full tw-rounded-lg"
                                            />
                                        </div>

                                        <div>
                                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                                Account Balance
                                            </label>
                                            <InputNumber
                                                v-model="Patient.balance"
                                                placeholder="Account balance"
                                                mode="currency"
                                                currency="USD"
                                                locale="en-US"
                                                class="tw-w-full tw-rounded-lg"
                                            />
                                        </div>
                                    </div>

                                    <!-- Mother Information -->
                                    <div>
                                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-3">
                                            Mother's Information
                                        </label>
                                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                                            <Field name="mother_firstname" v-model="Patient.mother_firstname">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Mother's first name"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>

                                            <Field name="mother_lastname" v-model="Patient.mother_lastname">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Mother's last name"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>
                                    </div>

                                    <!-- Arabic Names -->
                                    <div>
                                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-3">
                                            Arabic Names
                                        </label>
                                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                                            <Field name="firstname_ar" v-model="Patient.firstname_ar">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="First name in Arabic"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>

                                            <Field name="lastname_ar" v-model="Patient.lastname_ar">
                                                <template #default="{ field }">
                                                    <InputText
                                                        v-bind="field"
                                                        placeholder="Last name in Arabic"
                                                        class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    />
                                                </template>
                                            </Field>
                                        </div>
                                    </div>

                                    <!-- Faithful Customer -->
                                    <div class="tw-flex tw-items-center tw-gap-2">
                                        <Checkbox
                                            id="faithful-customer"
                                            v-model="Patient.is_faithful"
                                            :binary="true"
                                        />
                                        <label for="faithful-customer" class="tw-text-sm tw-font-medium tw-text-gray-700 tw-cursor-pointer">
                                            <i class="pi pi-star tw-text-yellow-500 tw-mr-1"></i>
                                            Mark as faithful customer
                                        </label>
                                    </div>

                                    <!-- Clinical Information -->
                                    <div>
                                        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                                            Other Clinical Information
                                        </label>
                                        <Field name="other_clinical_info" v-model="Patient.other_clinical_info">
                                            <template #default="{ field }">
                                                <Textarea
                                                    v-bind="field"
                                                    placeholder="Enter any additional clinical information..."
                                                    class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
                                                    rows="4"
                                                />
                                            </template>
                                        </Field>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>
            </TabView>

            <!-- Action Buttons -->
            <div class="tw-flex tw-justify-end tw-gap-3 tw-mt-6 tw-p-6 tw-border-t tw-border-gray-200">
                <Button
                    type="button"
                    label="Cancel"
                    icon="pi pi-times"
                    class="p-button-outlined p-button-secondary tw-rounded-lg"
                    @click="closeModal"
                    :disabled="isSubmitting"
                />
                <Button
                    type="submit"
                    :label="isEditMode ? 'Update Patient' : 'Create Patient'"
                    :icon="isEditMode ? 'pi pi-pencil' : 'pi pi-plus'"
                    class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-purple-600 tw-border-0 tw-rounded-lg tw-px-6"
                    :loading="isSubmitting"
                />
            </div>
        </Form>
    </Dialog>
</template>

<style scoped>
/* Custom PrimeVue component styling with Tailwind */
:deep(.p-dialog) {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 0;
}

:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
    color: white;
    border-bottom: 0;
    border-radius: 12px 12px 0 0;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 0;
}

:deep(.p-dialog .p-dialog-footer) {
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

:deep(.p-tabview .p-tabview-panels) {
    border: 0;
    box-shadow: none;
}

:deep(.p-tabview .p-tabview-nav) {
    border-bottom: 1px solid #e5e7eb;
    background-color: white;
}

:deep(.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
    color: white;
    border: 0;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    border: 0;
    border-radius: 0;
    padding: 12px 24px;
}

:deep(.p-card) {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 0;
}

:deep(.p-card .p-card-header) {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-bottom: 1px solid #e5e7eb;
}

:deep(.p-card .p-card-body) {
    padding: 24px;
}

:deep(.p-inputtext) {
    border-radius: 8px;
    border: 1px solid #d1d5db;
}

:deep(.p-inputtext:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

:deep(.p-inputnumber) {
    border-radius: 8px;
    border: 1px solid #d1d5db;
}

:deep(.p-inputnumber:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

:deep(.p-dropdown) {
    border-radius: 8px;
    border: 1px solid #d1d5db;
}

:deep(.p-dropdown:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

:deep(.p-calendar) {
    border-radius: 8px;
    border: 1px solid #d1d5db;
}

:deep(.p-calendar:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

:deep(.p-textarea) {
    border-radius: 8px;
    border: 1px solid #d1d5db;
}

:deep(.p-textarea:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

:deep(.p-checkbox .p-checkbox-box) {
    border: 1px solid #d1d5db;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    background-color: #2563eb;
    border-color: #2563eb;
}

:deep(.p-radiobutton .p-radiobutton-box) {
    border: 1px solid #d1d5db;
}

:deep(.p-radiobutton .p-radiobutton-box.p-highlight) {
    background-color: #2563eb;
    border-color: #2563eb;
}

:deep(.p-button) {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

:deep(.p-button:hover) {
    transform: scale(1.05);
}

:deep(.p-progressbar) {
    border-radius: 9999px;
}

:deep(.p-progressbar .p-progressbar-value) {
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    :deep(.p-dialog) {
        width: 100%;
        max-width: 100%;
        margin: 8px;
    }

    :deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
        padding: 8px 12px;
        font-size: 14px;
    }
}

/* Custom animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

:deep(.p-card) {
    animation: fadeInUp 0.5s ease-out;
}

/* Loading state styling */
:deep(.p-button .p-button-loading-icon) {
    margin-right: 8px;
}

/* Dropdown fixes */
:deep(.p-dropdown) {
    position: relative;
}

:deep(.p-dropdown-panel) {
    z-index: 9999 !important;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    max-height: 200px;
    overflow-y: auto;
}

:deep(.p-dropdown-item) {
    padding: 8px 12px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

:deep(.p-dropdown-item:hover) {
    background-color: #f3f4f6;
}

:deep(.p-dropdown-item.p-highlight) {
    background-color: #3b82f6;
    color: white;
}
</style>