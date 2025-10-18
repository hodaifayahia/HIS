// src/router/routes/emergency.js

const emergencyRoutes = [
  {
    path: '/emergency',
    name: 'emergency',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'emergency' },
    children: [
    //   {
    //     path: 'dashboard',
    //     name: 'emergency.dashboard',
    //     // component: () => import('../Pages/Apps/emergency/Dashboard/emergencyDashboard.vue'),
    //   },
     // fiche  navatte
        {
        path: 'fiche-navette',
        name: 'emergency.fiche-navette',
        component: () => import('../Pages/Apps/Emergency/FicheNavatte/FichenavetteEmergencyList.vue'),
      },
        {
        path: 'fiche-navette/custom-package',
        name: 'emergency.fiche-navette.custom-package',
        component: () => import('../Pages/Apps/Emergency/FicheNavatte/ficheNavetteCustomPackage.vue'),
      },
      {
        path: 'fiche-navette/:id/items',
        name: 'emergency.FicheNavetteItems',
        component: () => import('../Pages/Apps/Emergency/FicheNavatte/FicheNavetteItemsList.vue'),
        meta: {
          requiresAuth: true,
          title: 'Emergency Fiche Navette Items'
        }
      },
      {
        path: 'fiche-navette/:id/items/create',
        name: 'emergency.FicheNavetteItems.create',
        component: () => import('../Components/Apps/Emergency/FicheNavatteItem/FicheNavetteItemCreate.vue'),
        meta: {
          requiresAuth: true,
          title: 'Emergency Fiche Navette Items Create'
        }
      },
      {
        path: 'doctor-planning',
        name: 'emergency.doctor-planning',
        component: () => import('../Pages/Apps/Emergency/EmergencyPlanning/DoctorEmergencyPlanning.vue'),
        meta: {
          requiresAuth: true,
          title: 'Doctor Emergency Planning'
        }
      }
    //     {
    //     path: 'fiche-navette/custom-package',
    //     name: 'emergency.fiche-navette.custom-package',
    //     component: () => import('../Pages/Apps/emergency/FicheNavatte/ficheNavetteCustomPackage.vue'),
    //   },
    //   {
    //     path: '/emergency/fiche-navette/:id/items',
    //     name: 'emergency.FicheNavetteItems',
    //     component: () => import('../Pages/Apps/emergency/FicheNavatte/FicheNavetteItemsList.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Fiche Navette Items'
    //     }
    //   },
    //   {
    //     path: '/emergency/fiche-navette/:id/items/create',
    //     name: 'emergency.FicheNavetteItems.create',
    //     component: () => import('../Components/Apps/emergency/FicheNavatteItem/FicheNavetteItemCreate.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Fiche Navette Items'
    //     }
    //   },
     
    ],
  },
];

export default emergencyRoutes;