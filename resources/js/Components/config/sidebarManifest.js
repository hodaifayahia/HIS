import { defineAsyncComponent } from 'vue';

const sidebarManifest = {
  // Your existing general sidebar (used as a fallback)
  default: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebar.vue')),

  configuration: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ConfigurationSidebar.vue')),
  reception: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ReceptionSidebar.vue')),
  // App-specific sidebars
//   appointments: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/AppointmentsSidebar.vue')),
//   consultation: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ConsultationSidebar.vue')),
   caisse: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/CaisseSidebar.vue')),
   coffre: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/CoffreSidebar.vue')),
   banking: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/BankingSidebar.vue')),
  convention: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ConventionSidebar.vue')),
//   facturation: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/FacturationSidebar.vue')),
  infrastructure: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/InfrastructureSidebar.vue')),
     stock: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/StockSidebar.vue')),

  crm: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/CrmSidebar.vue')),
  ticketManagement: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/TicketManagementSidebar.vue')),
  portal: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/PortalSidebar.vue')),
  manger: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ManagerSidebar.vue')),
   emergency: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/EmergencySidebar.vue')),
   purchasing: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/PurchasingSidebar.vue')),
//   radiology: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/RadiologySidebar.vue')),
//   hospitalization: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/HospitalizationSidebar.vue')),
//   laboratory: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/LaboratorySidebar.vue')),
//   operatingRoom: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/OperatingRoomSidebar.vue')),
//   pharmacy: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/PharmacySidebar.vue')),
//   catering: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/CateringSidebar.vue')),
//   inventory: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/InventorySidebar.vue')),
//   purchasing: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/PurchasingSidebar.vue')),
//   hygiene: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/HygieneSidebar.vue')),
//   biomedical: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/BiomedicalSidebar.vue')),
//   catheterization: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/CatheterizationSidebar.vue')),
//   archive: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ArchiveSidebar.vue')),
//   hr: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/HrSidebar.vue')),
//   maintenance: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/MaintenanceSidebar.vue')),
 // doctor: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/DoctorSidebar.vue')), // A specific sidebar for doctor role
  // ... add all 20+ of your app sidebars here
};

export default sidebarManifest;