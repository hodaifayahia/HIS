import Index from '../Pages/Apps/TicketManagement/Index.vue';
import TicketList from '../Pages/Apps/TicketManagement/Components/TicketList.vue';
import TicketDetail from '../Pages/Apps/TicketManagement/Components/TicketDetail.vue';



// src/router/routes/crm.js

const TicketManagementRoutes = [
  {
    path: '/ticket-management',
    name: 'ticket-management',
    component: Index,
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'ticketManagement' },
    children: [
      {
        path: '',
        name: 'ticket-management.list',
        component: TicketList
      },
      {
        path: ':id',
        name: 'ticket-management.detail',
        component: TicketDetail
      }
    ]
  }
];
 
export default TicketManagementRoutes;