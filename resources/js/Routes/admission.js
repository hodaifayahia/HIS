// src/router/routes/admission.js

const AdmissionRoutes = [
  {
    path: '/admissions',
    name: 'admissions',
    meta: { role: ['admin', 'SuperAdmin', 'doctor', 'nurse', 'reception'], appKey: 'admissions' },
    children: [
      {
        path: '',
        name: 'admissions.index',
        component: () => import('../Pages/Admission/AdmissionIndex.vue'),
        meta: { title: 'Admissions List' }
      },
      {
        path: 'create',
        name: 'admissions.create',
        component: () => import('../Pages/Admission/AdmissionCreate.vue'),
        meta: { title: 'Create Admission' }
      },
      {
        path: ':id',
        name: 'admissions.show',
        component: () => import('../Pages/Admission/AdmissionShow.vue'),
        meta: { title: 'Admission Details' },
        props: true
      },
      {
        path: ':id/edit',
        name: 'admissions.edit',
        component: () => import('../Pages/Admission/AdmissionEdit.vue'),
        meta: { title: 'Edit Admission' },
        props: true
      }
    ]
  }
];

export default AdmissionRoutes;
