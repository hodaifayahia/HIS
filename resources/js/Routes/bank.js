const BankRoutes = [
  {
    path: '/banking',
    name: 'Banking',
    //  component: () => import('../Pages/Apps/banking/BankingDashboard.vue'),
    meta: { role: ['admin', 'SuperAdmin', 'banking'], appKey: 'banking' },
    children: [
      {
       path: '/dashboard',
       name: 'BankingDashboard',
       component: () => import('../Pages/Apps/banking/BankingDashboard.vue'),
       meta: {
           title: 'Banking Dashboard',
           requiresAuth: true
       }
   },
      {
        path: 'banks',
        name: 'bank.banks',
        component: () => import('../Pages/Apps/banking/BankingBanks/BankList.vue')
      },
      {
        path: 'accounts',
        name: 'bank.accounts',
        component: () => import('../Pages/Apps/banking/account/BankAccountList.vue')
      },
      {
        path: 'transactions',
        name: 'bank.transactions',
        component: () => import('../Pages/Apps/banking/transaction/BankAccountTransactionList.vue')
      },
      {
        path: 'transactions/all',
        name: 'bank.transactions.all',
        component: () => import('../Pages/Apps/banking/transaction/AllTransactionsList.vue')
      },
    ]
  }
];

export default BankRoutes;