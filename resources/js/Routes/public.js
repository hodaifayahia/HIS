// src/router/routes/public.js
import HomePage from '@/Pages/Main/HomePage.vue'; // Use @ for alias if configured
import Login from '@/auth/Login.vue';
import Modality from '@/Pages/Main/Modality.vue';

const publicRoutes = [
    {
        path: '/',
        redirect: '/home',
    },
    {
        path: '/home',
        name: 'home',
        component: HomePage,
    },
    {
        path: '/modality',
        name: 'modality',
        component: Modality,
        meta: { requiresAuth: true, role: ['admin', 'SuperAdmin'], appKey: 'modality' },
        children: [
            {
                path: 'modality-appointment',
                name: 'modality.modality-appointment',
                component: () => import('@/Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentSpecilazationList.vue'),
            },
            {
                path: 'modality-appointment/forceAppointment',
                name: 'modality.modality-appointment.forceAppointment',
                component: () => import('@/Pages/Apps/Appointments/ModalityAppointment/ForceAppointments/CanFroceList.vue'),
            },
            {
                path: 'modality-appointment/specialization/:id',
                name: 'modality.modality-appointment.specialization',
                component: () => import('@/Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentModalityList.vue'),
                props: true,
            },
            {
                path: 'modality-appointment/details/:id/:specializationId',
                name: 'modality.modality-appointment.details',
                component: () => import('@/Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentDetails.vue'),
                props: true,
            },
        ],
    },
    {
        path: '/calander',
        name: 'calander.public',
       component: () => import('../Components/Calender.vue'),
        // Accept optional params via query so the homepage can link to /calander or /calander?id=1&specializationId=2
    },
    {
        path: '/login',
        name: 'auth.login',
        component: Login,
    },
];

export default publicRoutes;