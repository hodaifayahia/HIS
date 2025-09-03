// src/router/routes/configuration.js

// Routes for the "Coffre" (CRM) section — renamed & reorganized for CoffreSidebar
const CoffreRoutes = [
  {
    path: '/coffre',
    name: 'coffre',
    meta: { role: ['admin', 'SuperAdmin', 'manager', 'reception', 'support', 'partner'], appKey: 'coffre' },
    children: [
      {
        path: 'dashboard', // <-- changed from '' to 'dashboard' so /coffre/dashboard works
        name: 'coffre.dashboard',
        component: () => import('../Pages/Apps/coffre/Coffre/CoffreList.vue'),
        meta: { title: 'Coffre Dashboard' }
      },
      {
        path: 'list', // <-- remove leading slash so path becomes /coffre/list
        name: 'coffre.list',
        component: () => import('../Pages/Apps/coffre/Coffre/CoffreList.vue'),
        meta: { title: 'Coffre List' }
      },
       {
        path: 'transactions/:coffre_id',
        name: 'coffre.transactions',
        component: () => import('../Pages/Apps/coffre/Transactions/CoffreTransactionList.vue'),
        meta: { title: 'Coffre Transactions' },
        props: true
      },
      {
        path: 'caisse', // <-- remove leading slash so path becomes /coffre/caisse
        name: 'coffre.caisse',
        component: () => import('../Pages/Apps/coffre/caisse/CaisseList.vue'),
        meta: { title: 'Coffre Caisse' }
      },
      {
        path: 'caisse/:caisse_id', // <-- keep param name
        name: 'coffre.caisse.operation',
        component: () => import('../Pages/Apps/coffre/caisse/CaisseSessionList.vue'),
        meta: {
            title: 'Cash Register Sessions',
            requiresAuth: true
          },
        // map route param 'caisse_id' to prop 'id' expected by CaisseSessionList.vue
        props: (route) => ({ id: route.params.caisse_id })
      },
     
    ]
  }
];

export default CoffreRoutes;
