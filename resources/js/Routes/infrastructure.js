// src/router/routes/configuration.js

const infrastructureRoutes = [
    {
        path: '/infrastructure', // Base path for the entire infrastructure/configuration module
        name: 'infrastructure', // Main name for the module
        meta: { role: ['admin', 'SuperAdmin'], appKey: 'infrastructure' }, // Original appKey
        children: [
            // -------------------------------------------------------------
            // SECTION: Hospitality & Space Management (New additions under Infrastructure)
            // This aligns with the "Facility Structure" and other sections
            // -------------------------------------------------------------
            {
                path: 'infrastructure-dashboard', // Renamed for clarity to avoid conflict with main dashboard
                name: 'infrastructure.hospitality-dashboard',
                component: () => import('../Pages/Apps/infrastructure/Dashboard/Dashboard.vue'), // Placeholder component
                meta: { title: 'Hospitality Overview' }
            },
            

            {
                path: 'structure', // Parent for facility structure
                name: 'infrastructure.structure',
                meta: { title: 'Facility Structure' },
                children: [
                    {
                        path: 'pavillons', // Nested path for pavilions
                        name: 'infrastructure.structure.pavillons', // Full name
                        component: () => import('../Pages/Apps/infrastructure/Pavilions/PavilionsList.vue'), // Corrected path to your component
                        meta: { title: 'Pavilions (Wings)' },
                    },
                    {
                        path: 'room-types', // New route for Room Types
                        name: 'infrastructure.structure.room-types',
                        component: () => import('../Pages/Apps/infrastructure/rooms/roomstype/RoomTypesList.vue'),
                        meta: { title: 'Room Types' },
                    },
                    {
                        path: 'pavillon-services/:id', // New route for Room Types
                        name: 'infrastructure.structure.services',
                        component: () => import('../Pages/Apps/infrastructure/Pavilions/ServicePavilionsList.vue'),
                        meta: { title: 'pavillons services' },
                    },
                    {
                        path: 'pavillon/services/:id', // New route for Room Types
                        name: 'infrastructure.structure.Cardservices',
                        component: () => import('../Components/Apps/infrastructure/rooms/room/ServicesCardList.vue'),
                        meta: { title: 'pavillons services' },
                    },
                ]
            },



            {
                path: 'Areas', // Parent for facility structure
                name: 'infrastructure.Areas',
                meta: { title: 'Areas' },
                children: [
                    {
                        path: 'pavilionCards', // New route for Room Types
                        name: 'infrastructure.Areas.pavilionCards',
                        component: () => import('../Components/Apps/infrastructure/rooms/room/PavilionCardLIst.vue'),
                        meta: { title: 'Room Types' },
                    },
                    {
                        path: 'rooms/:id', // New route for Room Types
                        name: 'infrastructure.Areas.roomsTyps',
                        component: () => import('../Components/Apps/infrastructure/rooms/room/RoomTypesCards.vue'),
                        meta: { title: 'Room Types' },
                    },
                    {
                        path: 'bebs', // New route for Room Types
                        name: 'infrastructure.Areas.bebs',
                        component: () => import('../Pages/Apps/infrastructure/Beds/BedsList.vue'),
                        meta: { title: 'Room Types' },
                    },
                   {
                        path: 'rooms/:typeId',
                        name: 'infrastructure.Areas.rooms',
                        component: () => import('../Pages/Apps/infrastructure/rooms/RoomsList.vue'),
                        meta: { title: 'Rooms' },
                        props: (route) => ({
                            service_id: route.query.service_id,
                            typeId: route.params.typeId
                        })
                        }
                                            // {
                    //     path: 'roomType/:id', // New route for Room Types
                    //     name: 'infrastructure.Areas.RoomType',
                    //     component: () => import('../Components/Apps/infrastructure/rooms/room/RoomType.vue'),
                    //     meta: { title: 'Room Types' },
                    // },
               
                ]
            },





            //         {
            //             path: 'waiting-transit-areas',
            //             name: 'infrastructure.structure.waiting-transit-areas',
            //             component: () => import('../../Pages/Hospitality/WaitingTransitAreas.vue'), // Placeholder
            //             meta: { title: 'Waiting & Transit Areas' },
            //         },
            //         // Add other Level 1 / structure related items here if they get their own pages
            //     ]
            // },
            // {
            //     path: 'inpatient-management', // Renamed from 'inpatient' to avoid potential path conflicts
            //     name: 'infrastructure.inpatient-management',
            //     meta: { title: 'Inpatient Management' },
            //     children: [
            //         {
            //             path: 'rooms',
            //             name: 'infrastructure.inpatient-management.rooms',
            //             component: () => import('../../Pages/Hospitality/Inpatient/HospitalizationRooms.vue'), // Placeholder
            //             meta: { title: 'Hospitalization Rooms' },
            //         },
            //         {
            //             path: 'beds',
            //             name: 'infrastructure.inpatient-management.beds',
            //             component: () => import('../../Pages/Hospitality/Inpatient/BedManagement.vue'), // Placeholder
            //             meta: { title: 'Bed Management' },
            //         },
            //         {
            //             path: 'admission-transfer',
            //             name: 'infrastructure.inpatient-management.admission-transfer',
            //             component: () => import('../../Pages/Hospitality/Inpatient/AdmissionTransfer.vue'), // Placeholder
            //             meta: { title: 'Admission & Transfers' },
            //         },
            //         {
            //             path: 'discharge-housekeeping',
            //             name: 'infrastructure.inpatient-management.discharge-housekeeping',
            //             component: () => import('../../Pages/Hospitality/Inpatient/DischargeHousekeeping.vue'), // Placeholder
            //             meta: { title: 'Discharge & Housekeeping' },
            //         },
            //     ]
            // },
            // {
            //     path: 'consultation-areas', // Renamed from 'consultation'
            //     name: 'infrastructure.consultation-areas',
            //     meta: { title: 'Consultation Areas' },
            //     children: [
            //         {
            //             path: 'rooms',
            //             name: 'infrastructure.consultation-areas.rooms',
            //             component: () => import('../../Pages/Hospitality/Consultation/ConsultationRooms.vue'), // Placeholder
            //             meta: { title: 'Consultation Rooms' },
            //         },
            //     ]
            // },
            // {
            //     path: 'treatment-procedure-areas', // Renamed from 'treatment'
            //     name: 'infrastructure.treatment-procedure-areas',
            //     meta: { title: 'Treatment & Procedure Areas' },
            //     children: [
            //         {
            //             path: 'rooms',
            //             name: 'infrastructure.treatment-procedure-areas.rooms',
            //             component: () => import('../../Pages/Hospitality/Treatment/TreatmentRooms.vue'), // Placeholder
            //             meta: { title: 'Treatment Rooms' },
            //         },
            //         {
            //             path: 'radiology-rooms',
            //             name: 'infrastructure.treatment-procedure-areas.radiology-rooms',
            //             component: () => import('../../Pages/Hospitality/Treatment/RadiologyRooms.vue'), // Placeholder
            //             meta: { title: 'Radiology Rooms' },
            //         },
            //         {
            //             path: 'cath-labs',
            //             name: 'infrastructure.treatment-procedure-areas.cath-labs',
            //             component: () => import('../../Pages/Hospitality/Treatment/CathLabs.vue'), // Placeholder
            //             meta: { title: 'Cath Labs' },
            //         },
            //         {
            //             path: 'operating-theaters',
            //             name: 'infrastructure.treatment-procedure-areas.operating-theaters',
            //             component: () => import('../../Pages/Hospitality/Treatment/OperatingTheaters.vue'), // Placeholder
            //             meta: { title: 'Operating Theaters' },
            //         },
            //     ]
            // },

            // // -------------------------------------------------------------
            // // SECTION: System Settings (Original Infrastructure Route)
            // // -------------------------------------------------------------
            // {
            //     path: 'system-settings',
            //     name: 'infrastructure.system-settings',
            //     component: () => import('../Components/Apps/Configuration/SystemSettings/SystemSettings.vue'),
            //     meta: { title: 'System Settings' },
        ]

    },

]
    


export default infrastructureRoutes;