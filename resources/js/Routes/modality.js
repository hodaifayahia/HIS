// src/router/routes/admin.js

const ModalityRoutes = [
  {
    path: '/modality',
    name: 'modality',
    meta: { requiresAuth: true, role: ['admin', 'SuperAdmin'] , appKey: 'modality' },
    // Optional: Add a layout for all admin routes
    // component: () => import('@/Layouts/AdminLayout.vue'),
    children: [
      {
        path: 'patient',
        name: 'admin.patient',
        component: () => import('@/Pages/Patient/PatientList.vue'),
      },
      {
        path: 'patient/appointments/:id',
        name: 'admin.patient.appointments',
        component: () => import('@/Pages/Patient/PatientAppointmentList.vue'),
        props: true,
      },
      {
        path: 'modality-appointment',
        name: 'admin.modality-appointment',
        component: () =>
          import(
            '@/Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentSpecilazationList.vue'
          ),
      },
      {
        path: 'modality-appointment/forceAppointment',
        name: 'admin.modality-appointment.forceAppointment',
        component: () =>
          import(
            '@/Pages/Apps/Appointments/ModalityAppointment/ForceAppointments/CanFroceList.vue'
          ),
      },
      {
        path: 'modality-appointment/specialization/:id',
        name: 'admin.modality-appointment.specialization',
        component: () =>
          import(
            '@/Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentModalityList.vue'
          ),
        props: true,
      },
      {
        path: 'modality-appointment/details/:id/:specializationId',
        name: 'admin.modality-appointment.details',
        component: () =>
          import(
            '@/Pages/Apps/Appointments/ModalityAppointment/ModalityAppointmentDetails.vue'
          ),
        props: true,
      },
    ],
  },
];

export default ModalityRoutes;
