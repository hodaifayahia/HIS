// src/router/routes/reception.js

const receptionRoutes = [
  {
    path: '/reception',
    name: 'reception',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'reception' },
    children: [
      {
        path: 'dashboard',
        name: 'reception.dashboard',
        component: () => import('../Pages/Apps/reception/FicheNavatte/ficheNavetteList.vue'),
      },
     // fiche  navatte
        {
        path: 'fiche-navettes',
        name: 'reception.fiche-navettes',
        component: () => import('../Pages/Apps/reception/FicheNavatte/ficheNavetteList.vue'),
      },
        {
        path: 'fiche-navette/custom-package',
        name: 'reception.fiche-navette.custom-package',
        component: () => import('../Pages/Apps/reception/FicheNavatte/ficheNavetteCustomPackage.vue'),
      },
      {
        path: 'fiche-navette/:id/items',
        name: 'reception.FicheNavetteItems',
        component: () => import('../Pages/Apps/reception/FicheNavatte/FicheNavetteItemsList.vue'),
        meta: {
          requiresAuth: true,
          title: 'Fiche Navette Items'
        }
      },
      {
        path: 'fiche-navette/:id/items/create',
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

export default receptionRoutes;