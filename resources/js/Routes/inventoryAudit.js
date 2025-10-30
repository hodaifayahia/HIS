
const InventoryAuditProductRoutes = [
  {
    path: '/Inventory-audit',
    name: 'Inventory-audit',
    meta: { role: ['admin', 'SuperAdmin', 'inventory-audit'], appKey: 'InventoryAuditProduct' },
    children: [
      {
        path: 'inventoryAudits',
        name: 'Inventory-audit.list',
        component: () => import('../Pages/Apps/inventory/inventoryAuditList.vue'),
      },
      {
        path: 'inventoryAudits/:id',
        name: 'Inventory-audit.view',
        component: () => import('../Pages/Apps/inventory/inventoryAuditView.vue'),
      },
      {
        path: 'inventoryAudits/:id/products',
        name: 'Inventory-audit.products',
        component: () => import('../Pages/Apps/inventory/inventoryAuditProduct.vue'),
      },
    ]
  },
];
export default InventoryAuditProductRoutes;