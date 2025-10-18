
    

// src/router/routes/stock.js

const stockRoutes = [
  {
    path: '/stock',
    name: 'stock',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'stock' },
    children: [
      {
        path: 'dashboard',
        name: 'stock.dashboard',
        component: () => import('../Pages/Apps/stock/StockDashboard.vue'),
      },
      {
        path: 'productList',
        name: 'stock.productList',
        component: () => import('../Pages/Apps/stock/products/productList.vue'),
      },
      {
        path: 'products/:id/details',
        name: 'stock.products.details',
        component: () => import('../Pages/Apps/stock/products/productStockDetails.vue'),
        props: (route) => ({ productId: route.params.id }),
      },
      {
        path: 'stockages',
        name: 'stock.stockages',
        component: () => import('../Pages/Apps/stock/stockages/stockageList.vue'),
      },
      {
        path: 'stockages/:id',
        name: 'stock.stockages.detail',
        component: () => import('../Pages/Apps/stock/stockages/stockageDetail.vue'),
        // pass the route id param as `stockageId` prop to the component
        props: (route) => ({ stockageId: route.params.id }),
      },
      {
        path: 'stockages/:id/stock',
        name: 'stock.stockages.stock',
        component: () => import('../Pages/Apps/stock/stockages/stockageStock.vue'),
        props: (route) => ({ stockageId: route.params.id }),
      },
      {
        path: 'services-stock',
        name: 'stock.services',
        component: () => import('../Pages/Apps/stock/services/servicesList.vue'),
      },
      {
        path: 'services/:id/stock',
        name: 'stock.services.stock',
        component: () => import('../Pages/Apps/stock/services/serviceStock.vue'),
        props: (route) => ({ serviceId: route.params.id }),
      },
      {
        path: 'services/:id/management',
        name: 'stock.services.management',
        component: () => import('../Pages/Apps/stock/services/serviceStockManagement.vue'),
        props: (route) => ({ serviceId: route.params.id }),
      },
      {
        path: 'services/:id/orders',
        name: 'stock.services.orders',
        component: () => import('../Pages/Apps/stock/services/purchasing/ServiceDemandManagement.vue'),
        props: (route) => ({ serviceId: route.params.id }),
      },
     
      {
        path: 'service-demands/create',
        name: 'stock.service-demands.create',
        component: () => import('../Pages/Apps/stock/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'create', ...route.params })
      },
      {
        path: 'service-demands/create/:demandId',
        name: 'stock.service-demands.create.edit',
        component: () => import('../Pages/Apps/stock/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'create', ...route.params })
      },
      {
        path: 'service-demands/edit/:demandId',
        name: 'stock.service-demands.edit',
        component: () => import('../Pages/Apps/stock/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'edit', demandId: route.params.demandId })
      },
      {
        path: 'service-demands/view/:demandId',
        name: 'stock.service-demands.view',
        component: () => import('../Pages/Apps/stock/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'view', demandId: route.params.demandId })
      },
      {
        path: 'movements',
        name: 'stock.movements',
        component: () => import('../Pages/Apps/stock/StockMovement.vue'),
      },
      {
        path: 'requests',
        name: 'stock.requests',
        component: () => import('../Pages/Apps/stock/StockRequester.vue'),
      },
      {
        path: 'approvals',
        name: 'stock.approvals',
        component: () => import('../Pages/Apps/stock/StockReceiver.vue'),
      },
      {
        path: 'movements/:id/manage',
        name: 'stock.movements.manage',
        component: () => import('../Pages/Apps/stock/StockMovementManage.vue'),
        props: (route) => ({ movementId: route.params.id }),
      },
      {
        path: 'movements/:id/view',
        name: 'stock.movements.view',
        component: () => import('../Pages/Apps/stock/StockMovementView.vue'),
        props: (route) => ({ movementId: route.params.id }),
      },
    ],
  },
];

export default stockRoutes;