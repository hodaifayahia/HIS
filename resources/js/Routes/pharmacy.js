
    

// src/router/routes/pharmacy.js

const pharmacyRoutes = [
  {
    path: '/pharmacy',
    name: 'pharmacy',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'pharmacy' },
    children: [
      // {
      //   path: 'dashboard',
      //   name: 'pharmacy.dashboard',
      //   component: () => import('../Pages/Apps/pharmacy/Dashboard/PharmacyDashboard.vue'),
      // },
     
    ],
  },
];

export default pharmacyRoutes;