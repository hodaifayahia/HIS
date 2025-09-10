// src/router/routes/doctor.js
import Calender from '@/Components/Calender.vue';
import Profile from '@/Pages/Profile/Profile.vue';

const doctorRoutes = [  {
        path: '/doctor',
        name: 'doctor',
        // component: () => import('./Layouts/DoctorLayout.vue'),
        meta: { requiresAuth: true, role: 'doctor' },
        children: [
            {
                path: 'dashboard',
                name: 'doctor.dashboard',
                component: Calender,
            },
            {
                path: 'consultations/consultation',
                name: 'doctor.consultations.consulation',
                component: () => import('../Pages/Consultation/consultations/Consulationpatient.vue'),
            },
            {
                path: 'opinionReciver',
                name: 'doctor.opinionReciver',
                component: () => import('../Pages/Consultation/OpinionRequest/OpinionRequestReciver.vue'),
            },
            {
                path: 'patient',
                name: 'doctor.patient',
                component: () => import('../Pages/Patient/PatientList.vue'),
            },
            {
                path: 'consultations/consultation-workspaces',
                name: 'doctor.consultations.ConsultationWorkspaces',
                component: () => import('../Pages/Consultation/DoctorDoc/ConsultationWorkspacelist.vue'),
            },
            {
                path: 'consultations/consultation-workspaces/:id',
                name: 'doctor.consultations.ConsultationWorkspaceDetails',
                component: () => import('../Pages/Consultation/DoctorDoc/ConsultationWorkspaceDetails.vue'),
                props: true,
            },
            {
                path: 'appointments',
                name: 'doctor.appointments',
                component: () => import('../Pages/Users/AppointmentLIstDoctor.vue'),
            },
            {
                path: 'schedule',
                name: 'doctor.schedule',
                component: () => import('../Components/Doctor/DoctorListScheduleDForDoctor.vue'),
            },
            {
                path: 'avilability',
                name: 'doctor.avilability',
                component: () => import('../Components/Doctor/DoctorAvilibilte.vue'),
            },
            {
                path: 'excludeDates',
                name: 'doctor.excludeDates',
                component: () => import('../Pages/Excludes/ExcludeDatesDoctor.vue'),
            },
            {
                path: 'users',
                name: 'doctor.users',
                component: () => import('../Components/Doctor/ListUsersCanForce.vue'),
            },
            {
                path: 'settings',
                name: 'doctor.settings',
                component: () => import('../Pages/Setting/settingsDoctor.vue'),
            },
            {
                path: 'profile',
                name: 'doctor.profile',
                component: Profile,
            },
        ],
    }
    ]

export default doctorRoutes;