// External Prescriptions Routes - Consolidated Single Page Interface

const externalPrescriptionRoutes = [
  {
    path: '/external-prescriptions',
    name: 'external-prescriptions',
    meta: { role: ['doctor', 'admin', 'SuperAdmin'], appKey: 'externalPrescriptions' },
    children: [
      {
        path: '', // Default route
        redirect: 'list',
      },
      {
        path: 'dashboard',
        name: 'external-prescriptions.dashboard',
        component: () => import('../Pages/Apps/ExternalPrescriptions/Dashboard.vue'),
      },
      {
        path: 'list',
        name: 'external-prescriptions.list',
        component: () => import('../Pages/Apps/ExternalPrescriptions/ExternalPrescriptionConsolidatedList.vue'),
        meta: { role: ['doctor', 'admin', 'SuperAdmin'] },
      },
      // Legacy route redirects for backward compatibility
      {
        path: 'create',
        redirect: 'list',
      },
      {
        path: 'my-prescriptions',
        redirect: 'list',
      },
      {
        path: 'drafts',
        redirect: (to) => ({ name: 'external-prescriptions.list', query: { status: 'draft' } }),
      },
      {
        path: 'confirmed',
        redirect: (to) => ({ name: 'external-prescriptions.list', query: { status: 'confirmed' } }),
      },
      {
        path: ':id',
        name: 'external-prescriptions.detail',
        component: () => import('../Pages/Apps/ExternalPrescriptions/ExternalPrescriptionDetail.vue'),
        props: (route) => ({ prescriptionId: route.params.id }),
      },
      {
        path: 'reports',
        name: 'external-prescriptions.reports',
        component: () => import('../Pages/Apps/ExternalPrescriptions/Reports.vue'),
      },
    ],
  },
];

export default externalPrescriptionRoutes;
