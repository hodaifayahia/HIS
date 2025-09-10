<script setup>
import { ref, computed, defineProps, watch } from 'vue';
import { Form, Field } from 'vee-validate';
import * as yup from 'yup';
import axios from 'axios';
import { useToastr } from './toster';

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
    first_name: props.specData?.first_name || '',
    last_name: props.specData?.last_name || '',
    phone: props.specData?.phone || '',
    Idnum: props.specData?.Idnum || '',
    Parent: props.specData?.Parent || '',
    dateOfBirth: props.specData?.dateOfBirth || null,
    gender: props.specData?.gender || 'male', // Default to male
});

const isEditMode = computed(() => !!props.specData?.id);

watch(
    () => props.specData,
    (newValue) => {
        Patient.value = {
            id: newValue?.id || null,
            first_name: newValue?.first_name || '',
            Parent: newValue?.Parent || '',
            last_name: newValue?.last_name || '',
            phone: newValue?.phone || '',
            Idnum: newValue?.Idnum || '',
            dateOfBirth: newValue?.dateOfBirth || null,
            gender: newValue?.gender || 'male',
        };
    },
    { immediate: true, deep: true }
);

const PatientSchema = yup.object({
    first_name: yup
        .string()
        .required('First name is required')
        .min(2, 'First name must be at least 2 characters')
        .max(50, 'First name cannot exceed 50 characters'),
    last_name: yup
        .string()
        .required('Last name is required')
        .min(2, 'Last name must be at least 2 characters')
        .max(50, 'Last name cannot exceed 50 characters'),
    Parent: yup
        .string(),
    phone: yup
        .string()
       ,
    Idnum: yup
        .string()
        .nullable()
        .notRequired()
        .matches(/^[A-Za-z0-9]{5,20}$/, 'ID Number must be 5-20 alphanumeric characters')
        .transform((value, originalValue) => originalValue === '' ? null : value),
    dateOfBirth: yup
        .date()
        .nullable()
        .notRequired()
        .max(new Date(), 'Date of Birth cannot be in the future')
        .transform((value, originalValue) => originalValue === '' ? null : value),
    gender: yup
        .string()
        .required('Gender is required')
        .oneOf(['male', 'female'], 'Invalid gender selection'),
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
    <div class="modal fade overflow-auto" :class="{ show: showModal }" tabindex="-1" aria-labelledby="specializationModalLabel"
        aria-hidden="true" v-if="showModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEditMode ? 'Edit Patient' : 'Add Patient' }}</h5>
                    <button type="button" class="btn btn-danger" @click="closeModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <Form
                        :validation-schema="PatientSchema"
                        @submit="submitForm"
                        v-slot="{ errors, handleSubmit }"
                    >
                        <div class="row">
                            <!-- First Name -->
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label for="patient-first-name" class="text-muted">Patient Last Name</label>
                                    <Field
                                        name="first_name"
                                        v-model="Patient.first_name"
                                        type="text"
                                        class="form-control form-control-md rounded-pill"
                                        :class="{ 'is-invalid': errors.first_name }"
                                        id="patient-first-name"
                                        placeholder="Enter Patient First Name"
                                    />
                                    <span class="invalid-feedback">{{ errors.first_name }}</span>
                                </div>
                            </div>
                            
                            <!-- Last Name -->
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label for="patient-last-name" class="text-muted">Patient First Name</label>
                                    <Field
                                        name="last_name"
                                        v-model="Patient.last_name"
                                        type="text"
                                        class="form-control form-control-md rounded-pill"
                                        :class="{ 'is-invalid': errors.last_name }"
                                        id="patient-last-name"
                                        placeholder="Enter Patient Last Name"
                                    />
                                    <span class="invalid-feedback">{{ errors.last_name }}</span>
                                </div>
                            </div>
                            
                            <!-- Parent Name -->
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label for="patient-parent" class="text-muted">Parent Name</label>
                                    <Field
                                        name="Parent"
                                        v-model="Patient.Parent"
                                        type="text"
                                        class="form-control form-control-md rounded-pill"
                                        :class="{ 'is-invalid': errors.Parent }"
                                        id="patient-parent"
                                        placeholder="Enter Parent Name"
                                    />
                                    <span class="invalid-feedback">{{ errors.Parent }}</span>
                                </div>
                            </div>
                            
                            <!-- Gender Selection -->
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label class="text-muted d-block mb-2">Gender</label>
                                    <div class="d-flex justify-content-between">
                                        <div class="form-check form-check-inline flex-grow-1 text-center">
                                            <input 
                                                class="form-check-input d-none" 
                                                type="radio" 
                                                id="gender-male" 
                                                value="male" 
                                                v-model="Patient.gender"
                                            >
                                            <label 
                                                class="form-check-label gender-option p-3 rounded-3 d-flex flex-column align-items-center"
                                                :class="{ 'bg-primary text-white': Patient.gender === 'male', 'bg-light': Patient.gender !== 'male' }"
                                                for="gender-male"
                                            >
                                                <i class="fas fa-mars fa-2x mb-2"></i>
                                                <span>Male</span>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-check-inline flex-grow-1 text-center mx-2">
                                            <input 
                                                class="form-check-input d-none" 
                                                type="radio" 
                                                id="gender-female" 
                                                value="female" 
                                                v-model="Patient.gender"
                                            >
                                            <label 
                                                class="form-check-label gender-option p-3 rounded-3 d-flex flex-column align-items-center"
                                                :class="{ 'bg-primary text-white': Patient.gender === 'female', 'bg-light': Patient.gender !== 'female' }"
                                                for="gender-female"
                                            >
                                                <i class="fas fa-venus fa-2x mb-2"></i>
                                                <span>Female</span>
                                            </label>
                                        </div>
                                    </div>
                                    <Field name="gender" v-model="Patient.gender" type="hidden" />
                                    <span class="invalid-feedback d-block">{{ errors.gender }}</span>
                                </div>
                            </div>
                            
                            <!-- Phone -->
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label for="patient-phone" class="text-muted">Patient Phone</label>
                                    <Field
                                        name="phone"
                                        v-model="Patient.phone"
                                        type="text"
                                        class="form-control form-control-md rounded-pill"
                                        :class="{ 'is-invalid': errors.phone }"
                                        id="patient-phone"
                                        placeholder="Enter Patient Phone"
                                    />
                                    <span class="invalid-feedback">{{ errors.phone }}</span>
                                </div>
                            </div>
                            
                            <!-- ID Number -->
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label for="patient-idnum" class="text-muted">ID Number (optional)</label>
                                    <Field
                                        name="Idnum"
                                        v-model="Patient.Idnum"
                                        type="text"
                                        class="form-control form-control-md rounded-pill"
                                        :class="{ 'is-invalid': errors.Idnum }"
                                        id="patient-idnum"
                                        placeholder="Enter ID Number"
                                    />
                                    <span class="invalid-feedback">{{ errors.Idnum }}</span>
                                </div>
                            </div>
                            
                            <!-- Date of Birth -->
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label for="patient-dob" class="text-muted">Date of Birth (optional)</label>
                                    <Field
                                        name="dateOfBirth"
                                        v-model="Patient.dateOfBirth"
                                        type="date"
                                        class="form-control form-control-md rounded-pill"
                                        :class="{ 'is-invalid': errors.dateOfBirth }"
                                        id="patient-dob"
                                    />
                                    <span class="invalid-feedback">{{ errors.dateOfBirth }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-outline-primary">
                                {{ isEditMode ? 'Update Patient' : 'Add Patient' }}
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

.invalid-feedback {
    display: block;
    color: red;
    font-size: 0.875rem;
}

.gender-option {
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.gender-option:hover {
    background-color: #e9ecef !important;
}

.gender-option i {
    transition: all 0.3s ease;
}

.form-check-input:checked + .gender-option {
    background-color: var(--bs-primary);
    color: white;
}

/* Make sure Font Awesome icons are properly sized */
.fa-2x {
    font-size: 1.5em;
}
</style>