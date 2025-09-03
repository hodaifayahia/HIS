


// src/router/routes/manger.js

const mangerRoutes = [
  {
    path: '/manger',
    name: 'manger',
    meta: { role: ['admin', 'SuperAdmin', 'manager'], appKey: 'manger' },
    children: [
      {
        path: 'dashboard',
        name: 'manger.dashboard',
        component: () => import('../Pages/Apps/manager/mangerDashboard.vue'),
      },
      {
        path: 'refund-requests',
        name: 'manger.refund-requests',
        component: () => import('../Pages/Apps/manager/RefoundRequest.vue'),
      },
      {
        path: 'refund-requests/:id',
        name: 'manger.refund-requests.details',
        component: () => import('../Components/Apps/manager/RefoundRequestDetails.vue'),
      },
       {
        path: 'prestation',
        name: 'manger.prestation',
        component: () => import('../Pages/Apps/manager/PrestationPatient.vue'),
      },


    ],
  },
];

export default mangerRoutes;