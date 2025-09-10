import Index from '../Pages/Apps/ticketmanagement/Index.vue';
import TicketList from '../Pages/Apps/ticketmanagement/Components/TicketList.vue';
import TicketDetail from '../Pages/Apps/ticketmanagement/Components/TicketDetail.vue';



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