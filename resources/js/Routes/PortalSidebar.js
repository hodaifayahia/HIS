
    

// src/router/routes/portal.js

const PortalRoutes = [
  {
    path: '/portal',
    name: 'portal',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'portal' },
    children: [
      {
        path: 'dashboard',
        name: 'portal.dashboard',
        component: () => import('../Pages/Apps/portal/portalDashboard.vue'),
      },
      {
        path: 'remise-request',
        name: 'portal.remise-request',
        component: () => import('../Pages/Apps/portal/RemiseRequest.vue'),
      },
      {
        path: 'remise-request-approver',
        name: 'portal.remise-request.approver',
        component: () => import('../Pages/Apps/portal/ApproverRemiseList.vue'),
      },
     
    ],
  },
];

export default PortalRoutes;