const purchasingRoutes = [{
    path: '/purchasing',
    name: 'purchasing',
    component: () => import('../Pages/Apps/Purchasing/PurchasingLayout.vue'),
    meta: { requiresAuth: true, role: ['admin', 'SuperAdmin', 'manager'], appKey: 'purchasing' },
    children: [
        {
            path: '',
            name: 'purchasing.index',
            redirect: { name: 'purchasing.service-demands.index' }
        },
        {
            path: 'products',
            name: 'purchasing.products',
            component: () => import('../Pages/Apps/Purchasing/products/productList.vue'),
        },
        {
            path: 'service-demands',
            name: 'purchasing.service-demands.index',
            component: () => import('../Pages/Apps/Purchasing/ServiceDemands/ServiceDemandsList.vue'),
        },
        {
            path: 'service-demands/:id',
            name: 'purchasing.service-demands.detail',
            component: () => import('../Pages/Apps/Purchasing/ServiceDemands/ServiceDemandDetail.vue'),
            props: (route) => ({ demandId: route.params.id })
        },
        {
            path: 'facture-proforma-list',
            name: 'purchasing.facture-proforma-list',
            component: () => import('../Pages/Apps/Purchasing/FactureProforma/FactureProformaList.vue'),
        },
        {
            path: 'facture-proforma/create',
            name: 'purchasing.facture-proforma.create',
            component: () => import('../Components/Apps/Purchasing/FactureProforma/FactureProformaCreate.vue'),
        },
        {
            path: 'facture-proforma/:id/edit',
            name: 'purchasing.facture-proforma.edit',
            component: () => import('../Pages/Apps/Purchasing/FactureProforma/FactureProformaItem.vue'),
            props: (route) => ({ proformaId: parseInt(route.params.id) })
        },
        {
            path: 'bon-commend-list',
            name: 'purchasing.bon-commend-list',
            component: () => import('../Pages/Apps/Purchasing/BonCommend/BonCommendList.vue'),
        },
        {
            path: 'bon-commend/create',
            name: 'purchasing.bon-commend.create',
            component: () => import('../Components/Apps/Purchasing/BonCommend/BonCommendCreate.vue'),
        },
        {
            path: 'bon-commend/:id/edit',
            name: 'purchasing.bon-commend.edit',
            component: () => import('../Pages/Apps/Purchasing/BonCommend/BonCommendItem.vue'),
            props: (route) => ({ bonCommendId: parseInt(route.params.id) })
        },
        {
            path: 'bon-receptions',
            name: 'purchasing.bon-receptions.index',
            component: () => import('../Pages/Apps/Purchasing/BonReception/BonReceptionList.vue'),
        },
        {
            path: 'bon-receptions/create',
            name: 'purchasing.bon-receptions.create',
            component: () => import('../Components/Apps/Purchasing/BonReception/BonReceptionCreate.vue'),
        },
        {
            path: 'bon-receptions/:id',
            name: 'purchasing.bon-receptions.show',
            component: () => import('../Pages/Apps/Purchasing/BonReception/BonReceptionItem.vue'),
            props: (route) => ({ bonReceptionId: parseInt(route.params.id) })
        },
        {
            path: 'bon-receptions/:id/edit',
            name: 'purchasing.bon-receptions.edit',
            component: () => import('../Pages/Apps/Purchasing/BonReception/BonReceptionItem.vue'),
            props: (route) => ({ bonReceptionId: parseInt(route.params.id) })
        },
          // Bon Entrée Routes
        {
            path: 'bon-entrees',
            name: 'purchasing.bon-entrees.index',
            component: () => import('../Pages/Apps/Purchasing/BonEntree/BonEntreeList.vue'),
        },
        {
            path: 'bon-entrees/create',
            name: 'purchasing.bon-entrees.create',
            component: () => import('../Components/Apps/Purchasing/BonEntree/BonEntreeCreate.vue'),
        },
        {
            path: 'bon-entrees/:id',
            name: 'purchasing.bon-entrees.show',
            component: () => import('../Pages/Apps/Purchasing/BonEntree/BonEntreeList.vue'),
            props: (route) => ({ bonEntreeId: parseInt(route.params.id) })
        },
        {
            path: 'bon-entrees/:id/edit',
            name: 'purchasing.bon-entrees.edit',
            component: () => import('../Pages/Apps/Purchasing/BonEntree/BonEntreeEdit.vue'),
            props: (route) => ({ id: parseInt(route.params.id) })
        },
        // Bon Retour Routes
        {
            path: 'bon-retours',
            name: 'purchasing.bon-retours.index',
            component: () => import('../Pages/Apps/Purchasing/BonRetourList.vue'),
        },
        {
            path: 'bon-retours/create',
            name: 'purchasing.bon-retours.create',
            component: () => import('../Pages/Apps/Purchasing/BonRetourCreate.vue'),
        },
        {
            path: 'bon-retours/:id',
            name: 'purchasing.bon-retours.show',
            component: () => import('../Pages/Apps/Purchasing/BonRetourListItem.vue'),
            props: (route) => ({ bonRetourId: parseInt(route.params.id) })
        },
        {
            path: 'bon-retours/:id/edit',
            name: 'purchasing.bon-retours.edit',
            component: () => import('../Pages/Apps/Purchasing/BonRetourListItem.vue'),
            props: (route) => ({ bonRetourId: parseInt(route.params.id), mode: 'edit' })
        },
        // Product History Route
        {
            path: 'products/:productId/history',
            name: 'purchasing.product.history',
            component: () => import('../Pages/Apps/Purchasing/ProductHistory.vue'),
            props: (route) => ({ productId: parseInt(route.params.productId) })
        },
        // Approval Management Routes
        {
            path: 'approval-persons',
            name: 'purchasing.approval-persons',
            component: () => import('../Pages/Apps/Purchasing/ApprovalPersons.vue'),
        },
        {
            path: 'pending-approvals',
            name: 'purchasing.pending-approvals',
            component: () => import('../Pages/Apps/Purchasing/PendingApprovals.vue'),
        },
        {
            path: 'product-approval-settings',
            name: 'purchasing.product-approval-settings',
            component: () => import('../Pages/Apps/Purchasing/ProductApprovalSettings.vue'),
        },
          // Consignment Management Routes
        {
            path: 'consignments',
            name: 'purchasing.consignments.index',
            component: () => import('../Components/Purchasing/ConsignmentDashboard.vue'),
        },  // Consignment Management Routes
        {
            path: 'consignments',
            name: 'purchasing.consignments.index',
            component: () => import('../Components/Purchasing/ConsignmentDashboard.vue'),
        },
        // Future: Fournisseurs management
        {
            path: 'fournisseurs',
            name: 'purchasing.fournisseurs.index',
            component: () => import('../Pages/Apps/Purchasing/supplier/FournisseurList.vue'),
        },
    ],
}];

export default purchasingRoutes;
