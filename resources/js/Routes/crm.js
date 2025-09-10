
    

// src/router/routes/crm.js

const crmRoutes = [
  {
    path: '/crm',
    name: 'crm',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'crm' },
    children: [
      {
        path: 'dashboard',
        name: 'crm.dashboard',
        component: () => import('../Pages/Apps/crm/dashboard/CrmDashboard.vue'),
      },
      // B2C: Patient CRM
      {
        path: 'patients',
        name: 'patients',
        component: () => import('../Pages/Apps/crm/Patients/PatientsList.vue'),
      },
      {
        path: 'patients/:id',
        name: 'patient360',
        component: () => import('../Pages/Apps/crm/Patients/Patient360.vue'),
        props: true
      },
      {
        path: 'campaigns',
        name: 'crm.campaigns',
        component: () => import('../Pages/Apps/crm/Campaigns/CampaignList.vue'),
      },

      // Ticketing: Feedback & Satisfaction
      {
        path: 'tickets',
        name: 'crm.tickets',
        component: () => import('../Pages/Apps/crm/Ticketing/TicketList.vue'),
      },

      // B2B: Partner CRM
      {
        path: 'partenaires',
        name: 'crm.partenaires',
        component: () => import('../Pages/Apps/crm/Partenaires/PartenaireList.vue'),
      },
      {
        path: 'partenaires/:id',
        name: 'organisme-details',
        component: () => import('../Pages/Apps/crm/Partenaires/OrganismeDetails.vue'),
        props: true,
      },
      {
        path: 'opportunites',
        name: 'crm.opportunites',
        component: () => import('../Pages/Apps/crm/Opportunites/OpportuniteList.vue'),
      },
      {
        path: 'opportunites/:id',
        name: 'crm.opportunite-details',
        component: () => import('../Pages/Apps/crm/Opportunites/OpportuniteDetails.vue'),
        props: true
      },
      {
        path: 'medecins-referents',
        name: 'crm.medecinsReferents',
        component: () => import('../Pages/Apps/crm/MedecinsReferents/MedecinReferentList.vue'),
      },
      {
        path: 'referrals',
        name: 'crm.referrals',
        component: () => import('../Pages/Apps/crm/Referrals/ReferralList.vue'),
      },
    ],
  },
];

export default crmRoutes;