// src/router/routes/caisse.js

// Routes for the "Caisse" section
const CaisseRoutes = [
  {
    path: '/caisse',
    name: 'caisse',
    meta: { role: ['admin', 'SuperAdmin', 'manager', 'reception', 'support', 'cashier'], appKey: 'caisse' },
    children: [
      // {
      //   path: 'dashboard',
      //   name: 'caisse.dashboard',
      //   component: () => import('../Pages/Apps/caisse/CaisseDashboard.vue'),
      //   meta: { title: 'Caisse Dashboard' }
      // },
      {
        path: 'sessions',
        name: 'caisse.sessions',
        component: () => import('../Pages/Apps/caisse/CaisseSessionAuthCard.vue'),
        meta: { title: 'Cash Register Sessions' }
      },
      {
        path: 'caisse-session',
        name: 'patients.list',
        component: () => import('../Pages/Apps/caisse/CaisseSessionAuthCard.vue'),
        meta: { title: 'Patient Records' }
      },
      {
        path: 'caisse-patient-payment',
        name: 'patients.list.caisse-patient-payment',
        component: () => import('../Pages/Apps/caisse/CaissePatientPayment.vue'),
        meta: { title: 'Patient Payments' },
        props: (route) => ({
          caisse_session_id: route.query.caisse_session_id,
          patient_id: route.query.patient_id
        })
      },
      {
        path: 'caisse-pending-transfer',
        name: 'caisse.pending-transfer',
        component: () => import('../Components/Apps/caisse/Pending/CaissePendingTransfer.vue'),
        meta: { title: 'Pending Transfer Requests' }
      },
      {
        path: 'payment-approvals',
        name: 'caisse.payment-approvals',
        component: () => import('../Components/ApprovalDashboard.vue'),
        meta: { title: 'Payment Approvals' }
      },
      {
        path: 'patients/:id/:caisse_session_id/:caisse_id',
        name: 'patients.with-session',
        component: () => import('../Pages/Apps/caisse/Patient/PatientCaisseList.vue'),
        meta: { title: 'Patient Records' },
        props: true
      }
    ]
  }
];

export default CaisseRoutes;