const purchasingRoutes = [{
    path: '/purchasing',
    name: 'purchasing',
    component: () => import('../Pages/Apps/Purchasing/PurchasingLayout.vue'),
    meta: { requiresAuth: true, role: ['admin', 'SuperAdmin', 'manager'], appKey: 'purchasing' },
    children: [
        {
            path: '',
            redirect: { name: 'purchasing.service-demands.index' }
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
            component: () => import('../Pages/Apps/Purchasing/FactureProformaList.vue'),
        },
        {
            path: 'bon-commend-list',
            name: 'purchasing.bon-commend-list',
            component: () => import('../Pages/Apps/Purchasing/BonCommendList.vue'),
        },
        // Future: Fournisseurs management
        // {
        //     path: 'fournisseurs',
        //     name: 'purchasing.fournisseurs.index',
        //     component: () => import('../Pages/Apps/Purchasing/Fournisseurs/FournisseursList.vue'),
        // },
    ],
}];

export default purchasingRoutes;
