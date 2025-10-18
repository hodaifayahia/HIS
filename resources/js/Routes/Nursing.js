// src/router/routes/reception.js

const NursingRoutes = [
  {
    path: '/nursing',
    name: 'nursing',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'nursing' },
    children: [
      {
        path: 'dashboard',
        name: 'reception.dashboard',
        // component: () => import('../Pages/Apps/reception/Dashboard/ReceptionDashboard.vue'),
      },
      // fiche  navatte
      {
        path: 'fiche-navette',
        name: 'reception.fiche-navette',
        component: () => import('../Pages/Apps/Nursing/FicheNavatte/ficheNavetteList.vue'),
      },
      {
        path: '/nursing/fiche-navette/:id/items',
        name: 'nursing.FicheNavetteItems',
        component: () => import('../Pages/Apps/Nursing/FicheNavatte/FicheNavetteItemsList.vue'),
        meta: {
          requiresAuth: true,
          title: 'Fiche Navette Items'
        }
      },
      {
        path: 'product',
        name: 'nursing.product',
        component: () => import('../Pages/Apps/Nursing/FicheNavatte/NursingProductList.vue'),
        meta: {
          requiresAuth: true,
          title: 'Nursing Product'
        }
      },
      {
        path: '/reception/fiche-navette/:id/items/create',
        name: 'reception.FicheNavetteItems.create',
        component: () => import('../Components/Apps/reception/FicheNavatteItem/FicheNavetteItemCreate.vue'),
        meta: {
          requiresAuth: true,
          title: 'Fiche Navette Items'
        }
      },


    ],
  },
];

export default NursingRoutes;