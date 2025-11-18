


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
        path: 'services',
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
        path: 'services/:id/orders',
        name: 'pharmacy.services.orders',
        component: () => import('../Pages/Apps/pharmacy/services/purchasing/ServiceDemandManagement.vue'),
        props: (route) => ({ serviceId: route.params.id, moduleType: 'pharmacy' }),
      },
      {
        path: 'service-demands/create',
        name: 'pharmacy.service-demands.create',
        component: () => import('../Pages/Apps/pharmacy/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'create', ...route.params }),
      },
      {
        path: 'service-demands/create/:demandId',
        name: 'pharmacy.service-demands.create.edit',
        component: () => import('../Pages/Apps/pharmacy/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'create', ...route.params }),
      },
      {
        path: 'service-demands/edit/:demandId',
        name: 'pharmacy.service-demands.edit',
        component: () => import('../Pages/Apps/pharmacy/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'edit', demandId: route.params.demandId }),
      },
      {
        path: 'service-demands/view/:demandId',
        name: 'pharmacy.service-demands.view',
        component: () => import('../Pages/Apps/pharmacy/services/purchasing/ServiceDemandCreate.vue'),
        props: (route) => ({ mode: 'view', demandId: route.params.demandId }),
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
      {
        path: 'reservations',
        name: 'pharmacy.reservations',
        component: () => import('../Pages/Apps/pharmacy/Reservations/stockageReservations.vue'),
      },
      {
        path: 'product-reserves',
        name: 'pharmacy.product-reserves',
        component: () => import('../Pages/Apps/pharmacy/ProductReserve/ReserveList.vue'),
      },
      {
        path: 'product-reserves/:reserveId/products',
        name: 'pharmacy.product-reserves.products',
        component: () => import('../Pages/Apps/pharmacy/ProductReserve/ReserveProducts.vue'),
        props: (route) => ({ reserveId: route.params.reserveId }),
      },
      {
        path: 'service-groups',
        name: 'pharmacy.service-groups',
        component: () => import('../Pages/Apps/pharmacy/ServiceGroups/ServiceGroupList.vue'),
      },
      {
        path: 'external-prescriptions',
        name: 'pharmacy.external-prescriptions',
        component: () => import('../Pages/Apps/ExternalPrescriptions/ExternalPrescriptionList.vue'),
        meta: { role: ['doctor', 'admin', 'SuperAdmin'] },
      },
      {
        path: 'external-prescriptions/:id',
        name: 'pharmacy.external-prescriptions.detail',
        component: () => import('../Pages/Apps/ExternalPrescriptions/ExternalPrescriptionDetail.vue'),
        props: (route) => ({ prescriptionId: route.params.id }),
      },
    ],
  },
];

export default pharmacyRoutes;