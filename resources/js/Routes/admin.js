// src/router/routes/admin.js
// Import directly needed components or lazy-load
import Profile from '@/Pages/Profile/Profile.vue';

const adminRoutes = [{
    path: '/admin',
    name: 'admin',
    // Optional: Add a layout component for all admin routes here, e.g., AdminLayout.vue
    // component: () => import('./Layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, role: ['admin', 'SuperAdmin'] }, // All admin children will inherit this
    children: [
        {
            path: 'appointments',
            children: [
                {
            path: 'patient',
            name: 'admin.patient',
            component: () => import('../Pages/Patient/PatientList.vue'),
        },
        {
            path: 'patient/appointments/:id',
            name: 'admin.patient.appointments',
            component: () => import('../Pages/Patient/PatientAppointmentList.vue'),
        },
         {
            path: 'modality-appointment',
            name: 'admin.modality-appointment',
            component: () => import('../Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentSpecilazationList.vue'),
        },
         {
            path: 'modality-appointment/forceAppointment',
            name: 'admin.modality-appointment.forceAppointment',
            component: () => import('../Pages/Apps/Appointments/ModalityAppointment/ForceAppointments/CanFroceList.vue'),
        },
         {
            path: 'modality-appointment/specialization/:id',
            name: 'admin.modality-appointment.specialization',
            component: () => import('../Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentModalityList.vue'),
            props: true,
        },
         {
            path: 'modality-appointment/details/:id/:specializationId',
            name: 'admin.modality-appointment.details',
            component: () => import('../Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentDetails.vue'),
            props: true,
        },
        {
            path: 'users',
            name: 'admin.users',
            component: () => import('../Pages/Users/ListUsers.vue'),
        },
        {
            path: 'excludeDates',
            name: 'admin.excludeDates',
            component: () => import('../Pages/Excludes/ExcludeDates.vue'),
        },
        {
            path: 'excludeDates/:id',
            name: 'admin.excludeDates.doctor',
            component: () => import('../Components/exclude/DataTable.vue'),
        },
        {
            path: 'doctors',
            name: 'admin.doctors',
            component: () => import('../Pages/Users/ListDoctors.vue'),
        },
        {
            path: 'doctors/schedule/:id',
            name: 'admin.doctors.schedule',
            component: () => import('../Pages/Users/DoctorListSchedule.vue'),
        },
        {
            path: 'doctor/forceAppointment',
            name: 'admin.doctor.users',
            component: () => import('../Components/Doctor/ListUsersCanForceAdmin.vue'),
        },
        {
            path: 'settings',
            name: 'admin.settings',
            component: () => import('../Pages/Setting/settingsDoctor.vue'),
        },
        {
            path: 'specializations',
            name: 'admin.specialization',
            component: () => import('../Pages/Specialization/specializationList.vue'),
        },
        {
            path: 'profile',
            name: 'admin.profile',
            component: Profile,
        },
                 {
                path: 'roles',
                name: 'admin.roles',
                component: () => import('../Pages/Roles/Roleslist.vue'),
            },
            {
                path: 'calender',
                name: 'admin.appointments.calender',
               component: () => import('../Components/Calender.vue'),

            },
                {
                    path: 'dashboard',
                    name: 'admin.appointments.dashboard',
                    component: () => import('../Components/Dashborad/Dashborad.vue'),

                },
                {
                    path: 'specialization',
                    name: 'admin.appointments.specialization',
                    component: () => import('../Pages/Appointments/SpecializationListAppointment.vue'),
                },
                {
                    path: 'doctors/:id',
                    name: 'admin.appointments.doctors',
                    component: () => import('../Pages/Appointments/DoctorListAppointment.vue'),
                },
                {
                    path: 'create/:id',
                    name: 'admin.appointments.create',
                    component: () => import('../Pages/Appointments/AppointmentPage.vue'),
                },
                {
                    path: 'create/:id/:specialization_id/:appointmentId',
                    name: 'admin.appointments.edit',
                    component: () => import('../Pages/Appointments/AppointmentPage.vue'),
                },
                {
                    path: 'pending',
                    name: 'admin.appointments.pending',
                    component: () => import('../Pages/Pending/PendingList.vue'),
                },
                {
                    path: ':id',
                    name: 'admin.appointments',
                    component: () => import('../Pages/Appointments/ListAppointment.vue'),
                },
            ],
        },
        {
            path: 'waitlist',
            children: [
                {
                    path: '',
                    name: 'admin.waitlist.specialization',
                    component: () => import('../Pages/waitList/SpecializationListWaitlist.vue'),
                },
                {
                    path: ':id/types',
                    name: 'admin.waitlist.types',
                    component: () => import('../Pages/waitList/WaitlistTypes.vue'),
                },
                {
                    path: ':id/general',
                    name: 'admin.waitlist.general',
                    component: () => import('../Components/waitList/GeneralWaitlist.vue'),
                },
                {
                    path: ':id/daily',
                    name: 'admin.waitlist.daily',
                    component: () => import('../Components/waitList/DailyWaitlist.vue'),
                },
            ],
        },
        
        {
            path: 'consultations',
            children: [
                {
                    path: 'placeholders',
                    name: 'admin.consultation.placeholders',
                    component: () => import('../Pages/Consultation/Section/PlaceholdersList.vue'),
                },
                {
                    path: 'folderlist',
                    name: 'admin.consultation.folderlist',
                    component: () => import('../Pages/Consultation/folder/FolderList.vue'),
                },
                {
                    path: 'template/:folderid',
                    name: 'admin.consultation.template',
                    component: () => import('../Pages/Consultation/template/TemplateList.vue'),
                    props: true,
                },
                {
                    path: 'template/create/:doctor_id?/:folderid?',
                    name: 'admin.consultation.template.add',
                    component: () => import('../Pages/Consultation/template/templateModel.vue'),
                    props: true,
                },
                {
                    path: 'template/edit/:id/:doctor_id?/:folderid',
                    name: 'admin.consultation.template.edit',
                    component: () => import('../Pages/Consultation/template/templateModel.vue'),
                    props: true,
                },
                {
                    path: 'section/:id',
                    name: 'admin.section.attributes', // Consider renaming this to admin.consultation.section.attributes for consistency
                    component: () => import('../Pages/Consultation/attributes/attributesList.vue'),
                },
                {
                    path: 'consultation',
                    name: 'admin.consultations.consulation',
                    component: () => import('../Pages/Consultation/consultations/Consulationpatient.vue'),
                },
                {
                    path: 'consulation/:patientId/:appointmentId?/:doctorId?',
                    name: 'admin.consultations.consulation.show',
                    component: () => import('../Pages/Consultation/consultations/consultationList.vue'),
                    props: true,
                },
                {
                    path: 'consulation/add/:appointmentid/:doctorId/:patientId?/:specialization_id?/:consulationId?',
                    name: 'admin.consultations.consulation.add',
                    component: () => import('../Pages/Consultation/consultations/consultationModel.vue'),
                    props: true,
                },
                {
                    path: 'consulation/edit/:id',
                    name: 'admin.consultations.consulation.edit',
                    component: () => import('../Pages/Consultation/consultations/consultationModel.vue'),
                },
                {
                    path: 'consultation-workspaces',
                    name: 'admin.consultations.ConsultationWorkspaces',
                    component: () => import('../Pages/Consultation/DoctorDoc/ConsultationWorkspacelist.vue'),
                },
                {
                    path: 'consultation-workspaces/:id',
                    name: 'admin.consultations.ConsultationWorkspaceDetails',
                    component: () => import('../Pages/Consultation/DoctorDoc/ConsultationWorkspaceDetails.vue'),
                    props: true,
                },
                 {
            path: 'Medicales',
            name: 'admin.consultations.Medicales.show',
            component: () => import('../Pages/Consultation/medical/MedicalesList.vue'),
            props: true,
        },
            ],
        },
       
        // --- NEW/MODIFIED CONFIGURATION BLOCK ---
     
    ],
}
]

export default adminRoutes;