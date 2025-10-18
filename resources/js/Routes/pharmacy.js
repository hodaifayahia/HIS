
    

// src/router/routes/pharmacy.js

const pharmacyRoutes = [
  {
    path: '/pharmacy',
    name: 'pharmacy',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'pharmacy' },
    children: [
      {
        path: 'dashboard',
        name: 'pharmacy.dashboard',
        component: () => import('../Pages/Apps/pharmacy/StockDashboard.vue'),
      },
      {
        path: 'productList',
        name: 'pharmacy.productList',
        component: () => import('../Pages/Apps/pharmacy/products/productList.vue'),
      },
      {
        path: 'products/:id/details',
        name: 'pharmacy.products.details',
        component: () => import('../Pages/Apps/pharmacy/products/productStockDetails.vue'),
        props: (route) => ({ productId: route.params.id }),
      },
      {
        path: 'stockages',
        name: 'pharmacy.stockages',
        component: () => import('../Pages/Apps/pharmacy/stockages/stockageList.vue'),
      },
      {
        path: 'stockages/:id',
        name: 'pharmacy.stockages.detail',
        component: () => import('../Pages/Apps/pharmacy/stockages/stockageDetail.vue'),
        // pass the route id param as `stockageId` prop to the component
        props: (route) => ({ stockageId: route.params.id }),
      },
      {
        path: 'stockages/:id/stock',
        name: 'pharmacy.stockages.stock',
        component: () => import('../Pages/Apps/pharmacy/stockages/stockageStock.vue'),
        props: (route) => ({ stockageId: route.params.id }),
      },
      {
        path: 'services-stock',
        name: 'pharmacy.services',
        component: () => import('../Pages/Apps/pharmacy/services/servicesList.vue'),
      },
      {
        path: 'services/:id/stock',
        name: 'pharmacy.services.stock',
        component: () => import('../Pages/Apps/pharmacy/services/serviceStock.vue'),
        props: (route) => ({ serviceId: route.params.id }),
      },
      {
        path: 'services/:id/management',
        name: 'pharmacy.services.management',
        component: () => import('../Pages/Apps/pharmacy/services/serviceStockManagement.vue'),
        props: (route) => ({ serviceId: route.params.id }),
      },
      {
        path: 'movements',
        name: 'pharmacy.movements',
        component: () => import('../Pages/Apps/pharmacy/StockMovement.vue'),
      },
      {
        path: 'requests',
        name: 'pharmacy.requests',
        component: () => import('../Pages/Apps/pharmacy/StockRequester.vue'),
      },
      {
        path: 'approvals',
        name: 'pharmacy.approvals',
        component: () => import('../Pages/Apps/pharmacy/StockReceiver.vue'),
      },
      {
        path: 'movements/:id/manage',
        name: 'pharmacy.movements.manage',
        component: () => import('../Pages/Apps/pharmacy/StockMovementManage.vue'),
        props: (route) => ({ movementId: route.params.id }),
      },
      {
        path: 'movements/:id/view',
        name: 'pharmacy.movements.view',
        component: () => import('../Pages/Apps/pharmacy/StockMovementView.vue'),
        props: (route) => ({ movementId: route.params.id }),
      },
    ],
  },
];

export default pharmacyRoutes;