// src/router/routes/configuration.js

const configurationRoutes = [
    {
        path: '/admin',
        name: 'admin',
        meta: { requiresAuth: true, role: 'admin' },
        children: [
            {
                path: 'configuration',
                name: 'admin.configuration',
                meta: { role: ['admin', 'SuperAdmin'], appKey: 'configuration' },
                children: [
                    {
                        path: 'users',
                        name: 'admin.configuration.users',
                        component: () => import('../Pages/Users/ListUsers.vue'),
                    },
                    {
                        path: 'roles',
                        name: 'admin.configuration.roles',
                        component: () => import('../Pages/Roles/Roleslist.vue'),
                    },
                    {
                        path: 'services',
                        name: 'admin.configuration.services',
                        component: () => import('../Pages/Apps/Configuration/Services/servicesList.vue'),
                    },
                    // Uncomment and use these as needed
                    {
                        path: 'modalities',
                        name: 'admin.configuration.modalities',
                        component: () => import('../Pages/Apps/Configuration/modalities/ModalityList.vue'),
                    },
                    {
                        path: 'modalities-types',
                        name: 'admin.configuration.modality-types',
                        component: () => import('../Pages/Apps/Configuration/modalities/ModalityListType.vue'),
                    },
                    {
                        path: 'prestations',
                        name: 'admin.configuration.prestations',
                        component: () => import('../Pages/Apps/Configuration/PrestationConfig/PrestationsList.vue'),
                    },
                    {
                        path: 'prestations-packages',
                        name: 'admin.configuration.prestations-packages',
                        component: () => import('../Pages/Apps/Configuration/PrestationPackges/PrestationsPackagesList.vue'),
                    },
                    {
                        path: 'prescriptionscreate',
                        name: 'admin.configuration.create',
                        component: () => import('../Components/Apps/Configuration/PrestationConfig/PrescriptionModel.vue'),
                    },
                    {
                        path: 'prescriptions/view',
                        name: 'admin.configuration.prestations.view',
                        component: () => import('../Components/Apps/Configuration/PrestationConfig/PrescriptionView.vue'),
                    },
                     {
                        path: 'specializations',
                        name: 'admin.configuration.specializations',
                        component: () => import('../Pages/Specialization/specializationList.vue'),
                    },
                     {
                        path: 'system-settings',
                        name: 'admin.configuration.system-settings',
                        component: () => import('../Components/Apps/Configuration/SystemSettings/SystemSettings.vue'),
                    },
                    //  {
                    //     path: 'remise-types',
                    //     name: 'admin.configuration.remise-types',
                    //     component: () => import('../Components/Apps/Configuration/SystemSettings/RemiseTypes.vue'),
                    // },
                     {
                        path: 'payment-methods',
                        name: 'admin.configuration.payment-methods',
                        component: () => import('../Pages/Apps/Configuration/RemiseMangement/PaymentMethod/PaymentMethodList.vue'),
                    },
                     {
                        path: 'remise-management',
                        name: 'admin.configuration.remise-management',
                        component: () => import('../Pages/Apps/Configuration/RemiseMangement/Remise/RemiseList.vue'),
                    },
                    // Add more config routes here as needed...
                ],
            },
        ],
    },
];

export default configurationRoutes;