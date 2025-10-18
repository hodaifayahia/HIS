// src/router/routes/facturation.js

const facturationRoutes = [
  {
    path: '/facturation',
    name: 'facturation',
    meta: { 
      role: ['admin', 'SuperAdmin', 'billing_manager', 'cashier', 'accountant'], 
      appKey: 'facturation' 
    },
    children: [
      // Dashboard
    //   {
    //     path: 'dashboard',
    //     name: 'facturation.dashboard',
    //     component: () => import('../Pages/Apps/Facturation/Dashboard/FacturationDashboard.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Facturation Dashboard'
    //     }
    //   },

    //   // ============ BILLING MANAGEMENT SECTION ============
    //   // All Invoices
    //   {
    //     path: 'invoices',
    //     name: 'facturation.invoices',
    //     component: () => import('../Pages/Apps/Facturation/Invoices/InvoicesList.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'All Invoices'
    //     }
    //   },
    //   {
    //     path: 'invoices/:id',
    //     name: 'facturation.invoices.view',
    //     component: () => import('../Pages/Apps/Facturation/Invoices/InvoiceView.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'View Invoice'
    //     }
    //   },
    //   {
    //     path: 'create-invoice',
    //     name: 'facturation.create-invoice',
    //     component: () => import('../Pages/Apps/Facturation/Invoices/CreateInvoice.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Create Invoice'
    //     }
    //   },
    //   {
    //     path: 'invoices/:id/edit',
    //     name: 'facturation.invoices.edit',
    //     component: () => import('../Pages/Apps/Facturation/Invoices/EditInvoice.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Edit Invoice'
    //     }
    //   },

    //   // Fiche Navette Billing
    //   {
    //     path: 'fiche-navette-billing',
    //     name: 'facturation.fiche-navette-billing',
    //     component: () => import('../Pages/Apps/Facturation/FicheNavette/FicheNavetteBillingList.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Fiche Navette Billing'
    //     }
    //   },
    //   {
    //     path: 'fiche-navette/:id/billing',
    //     name: 'facturation.fiche-navette.billing-details',
    //     component: () => import('../Pages/Apps/Facturation/FicheNavette/FicheNavetteBillingDetails.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Fiche Navette Billing Details'
    //     }
    //   },
    //   {
    //     path: 'fiche-navette/:id/items-billing',
    //     name: 'facturation.fiche-navette.items-billing',
    //     component: () => import('../Pages/Apps/Facturation/FicheNavette/FicheNavetteItemsBilling.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Fiche Navette Items Billing'
    //     }
    //   },

    //   // Emergency Billing
    //   {
    //     path: 'emergency-billing',
    //     name: 'facturation.emergency-billing',
    //     component: () => import('../Pages/Apps/Facturation/Emergency/EmergencyBillingList.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Emergency Billing'
    //     }
    //   },
    //   {
    //     path: 'emergency/:id/create-invoice',
    //     name: 'facturation.emergency.create-invoice',
    //     component: () => import('../Pages/Apps/Facturation/Emergency/EmergencyCreateInvoice.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Create Emergency Invoice'
    //     }
    //   },

    //   // ============ CAISSE & PAYMENTS SECTION ============
    //   // Patient Payment (Main Cashier)
    //   {
    //     path: 'caisse-patient-payment',
    //     name: 'facturation.caisse-patient-payment',
    //     component: () => import('../Pages/Apps/Facturation/Caisse/CaissePatientPayment.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Patient Payment Cashier',
    //       defaultService: 'urgence' // Default to urgence service
    //     }
    //   },
    //   {
    //     path: 'caisse-patient-payment/:patientId',
    //     name: 'facturation.caisse-patient-payment.details',
    //     component: () => import('../Pages/Apps/Facturation/Caisse/PatientPaymentDetails.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Patient Payment Details'
    //     }
    //   },
    //   {
    //     path: 'caisse-patient-payment/:patientId/process',
    //     name: 'facturation.caisse-patient-payment.process',
    //     component: () => import('../Pages/Apps/Facturation/Caisse/ProcessPayment.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Process Payment'
    //     }
    //   },

    //   // Emergency Cashier
    //   {
    //     path: 'caisse-urgence',
    //     name: 'facturation.caisse-urgence',
    //     component: () => import('../Pages/Apps/Facturation/Caisse/CaisseUrgence.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Emergency Cashier',
    //       service: 'urgence',
    //       role: ['admin', 'cashier', 'emergency_cashier']
    //     }
    //   },
    //   {
    //     path: 'caisse-urgence/fiche-navette/:id/payment',
    //     name: 'facturation.caisse-urgence.fiche-navette-payment',
    //     component: () => import('../Pages/Apps/Facturation/Caisse/UrgenceFicheNavettePayment.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Emergency Fiche Navette Payment',
    //       service: 'urgence'
    //     }
    //   },

    //   // Payment History
    //   {
    //     path: 'payment-history',
    //     name: 'facturation.payment-history',
    //     component: () => import('../Pages/Apps/Facturation/Payments/PaymentHistory.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Payment History'
    //     }
    //   },
    //   {
    //     path: 'payment-history/:id',
    //     name: 'facturation.payment-history.details',
    //     component: () => import('../Pages/Apps/Facturation/Payments/PaymentDetails.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Payment Details'
    //     }
    //   },

    //   // Refunds
    //   {
    //     path: 'refunds',
    //     name: 'facturation.refunds',
    //     component: () => import('../Pages/Apps/Facturation/Refunds/RefundsList.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Refunds Management'
    //     }
    //   },
    //   {
    //     path: 'refunds/create',
    //     name: 'facturation.refunds.create',
    //     component: () => import('../Pages/Apps/Facturation/Refunds/CreateRefund.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Create Refund'
    //     }
    //   },
    //   {
    //     path: 'refunds/:id',
    //     name: 'facturation.refunds.details',
    //     component: () => import('../Pages/Apps/Facturation/Refunds/RefundDetails.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Refund Details'
    //     }
    //   },

    //   // ============ INSURANCE SECTION ============
    //   {
    //     path: 'insurance-claims',
    //     name: 'facturation.insurance-claims',
    //     component: () => import('../Pages/Apps/Facturation/Insurance/InsuranceClaimsList.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Insurance Claims'
    //     }
    //   },
    //   {
    //     path: 'insurance-claims/create',
    //     name: 'facturation.insurance-claims.create',
    //     component: () => import('../Pages/Apps/Facturation/Insurance/CreateInsuranceClaim.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Create Insurance Claim'
    //     }
    //   },
    //   {
    //     path: 'insurance-claims/:id',
    //     name: 'facturation.insurance-claims.details',
    //     component: () => import('../Pages/Apps/Facturation/Insurance/InsuranceClaimDetails.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Insurance Claim Details'
    //     }
    //   },
    //   {
    //     path: 'insurance-claims/:id/edit',
    //     name: 'facturation.insurance-claims.edit',
    //     component: () => import('../Pages/Apps/Facturation/Insurance/EditInsuranceClaim.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Edit Insurance Claim'
    //     }
    //   },

    //   // ============ REPORTS SECTION ============
    //   {
    //     path: 'daily-report',
    //     name: 'facturation.daily-report',
    //     component: () => import('../Pages/Apps/Facturation/Reports/DailyReport.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Daily Financial Report'
    //     }
    //   },
    //   {
    //     path: 'monthly-summary',
    //     name: 'facturation.monthly-summary',
    //     component: () => import('../Pages/Apps/Facturation/Reports/MonthlySummary.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Monthly Financial Summary'
    //     }
    //   },
    //   {
    //     path: 'revenue-analysis',
    //     name: 'facturation.revenue-analysis',
    //     component: () => import('../Pages/Apps/Facturation/Reports/RevenueAnalysis.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Revenue Analysis'
    //     }
    //   },
    //   {
    //     path: 'cashier-report',
    //     name: 'facturation.cashier-report',
    //     component: () => import('../Pages/Apps/Facturation/Reports/CashierReport.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Cashier Report'
    //     }
    //   },
    //   {
    //     path: 'service-revenue',
    //     name: 'facturation.service-revenue',
    //     component: () => import('../Pages/Apps/Facturation/Reports/ServiceRevenue.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Service Revenue Report'
    //     }
    //   },

    //   // ============ SETTINGS SECTION (Admin Only) ============
    //   {
    //     path: 'pricing-rules',
    //     name: 'facturation.pricing-rules',
    //     component: () => import('../Pages/Apps/Facturation/Settings/PricingRules.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Pricing Rules Configuration',
    //       role: ['admin', 'billing_manager']
    //     }
    //   },
    //   {
    //     path: 'pricing-rules/create',
    //     name: 'facturation.pricing-rules.create',
    //     component: () => import('../Pages/Apps/Facturation/Settings/CreatePricingRule.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Create Pricing Rule',
    //       role: ['admin', 'billing_manager']
    //     }
    //   },
    //   {
    //     path: 'pricing-rules/:id/edit',
    //     name: 'facturation.pricing-rules.edit',
    //     component: () => import('../Pages/Apps/Facturation/Settings/EditPricingRule.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Edit Pricing Rule',
    //       role: ['admin', 'billing_manager']
    //     }
    //   },
    //   {
    //     path: 'tax-configuration',
    //     name: 'facturation.tax-configuration',
    //     component: () => import('../Pages/Apps/Facturation/Settings/TaxConfiguration.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Tax Configuration',
    //       role: ['admin', 'billing_manager']
    //     }
    //   },
    //   {
    //     path: 'payment-methods',
    //     name: 'facturation.payment-methods',
    //     component: () => import('../Pages/Apps/Facturation/Settings/PaymentMethods.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Payment Methods Configuration',
    //       role: ['admin', 'billing_manager']
    //     }
    //   },
    //   {
    //     path: 'discount-policies',
    //     name: 'facturation.discount-policies',
    //     component: () => import('../Pages/Apps/Facturation/Settings/DiscountPolicies.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Discount Policies',
    //       role: ['admin', 'billing_manager']
    //     }
    //   },

    //   // ============ QUICK ACCESS ROUTES ============
    //   // Direct payment link from other modules
    //   {
    //     path: 'quick-payment/:ficheNavetteId',
    //     name: 'facturation.quick-payment',
    //     component: () => import('../Pages/Apps/Facturation/QuickActions/QuickPayment.vue'),
    //     props: route => ({ 
    //       ficheNavetteId: route.params.ficheNavetteId,
    //       service: route.query.service || 'urgence'
    //     }),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Quick Payment'
    //     }
    //   },
    //   {
    //     path: 'batch-invoicing',
    //     name: 'facturation.batch-invoicing',
    //     component: () => import('../Pages/Apps/Facturation/QuickActions/BatchInvoicing.vue'),
    //     meta: {
    //       requiresAuth: true,
    //       title: 'Batch Invoicing',
    //       role: ['admin', 'billing_manager']
    //     }
    //   }
    ],
  },
];

export default facturationRoutes;
