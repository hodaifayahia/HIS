import { defineAsyncComponent } from 'vue';

const sidebarManifest = {
  // Your existing general sidebar (used as a fallback)
  default: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebar.vue')),

  configuration: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ConfigurationSidebar.vue')),
  reception: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ReceptionSidebar.vue')),
  // App-specific sidebars
//   appointments: defineAsyncComponent(() => import('../Dashborad/Sidebars/AppointmentsSidebar.vue')),
//   consultation: defineAsyncComponent(() => import('../Dashborad/Sidebars/ConsultationSidebar.vue')),
//   caisse: defineAsyncComponent(() => import('../Dashborad/Sidebars/CaisseSidebar.vue')),
//   coffre: defineAsyncComponent(() => import('../Dashborad/Sidebars/CoffreSidebar.vue')),
//   banking: defineAsyncComponent(() => import('../Dashborad/Sidebars/BankingSidebar.vue')),
  convention: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/ConventionSidebar.vue')),
//   facturation: defineAsyncComponent(() => import('../Dashborad/Sidebars/FacturationSidebar.vue')),
  infrastructure: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/InfrastructureSidebar.vue')),
  crm: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/CrmSidebar.vue')),
  ticketManagement: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/TicketManagementSidebar.vue')),
  portal: defineAsyncComponent(() => import('../../Pages/Dashborad/Sidebars/PortalSidebar.vue')),
//   admission: defineAsyncComponent(() => import('../Dashborad/Sidebars/AdmissionSidebar.vue')),
//   emergency: defineAsyncComponent(() => import('../Dashborad/Sidebars/EmergencySidebar.vue')),
//   nursing: defineAsyncComponent(() => import('../Dashborad/Sidebars/NursingSidebar.vue')),
//   radiology: defineAsyncComponent(() => import('../Dashborad/Sidebars/RadiologySidebar.vue')),
//   hospitalization: defineAsyncComponent(() => import('../Dashborad/Sidebars/HospitalizationSidebar.vue')),
//   laboratory: defineAsyncComponent(() => import('../Dashborad/Sidebars/LaboratorySidebar.vue')),
//   operatingRoom: defineAsyncComponent(() => import('../Dashborad/Sidebars/OperatingRoomSidebar.vue')),
//   pharmacy: defineAsyncComponent(() => import('../Dashborad/Sidebars/PharmacySidebar.vue')),
//   catering: defineAsyncComponent(() => import('../Dashborad/Sidebars/CateringSidebar.vue')),
//   inventory: defineAsyncComponent(() => import('../Dashborad/Sidebars/InventorySidebar.vue')),
//   purchasing: defineAsyncComponent(() => import('../Dashborad/Sidebars/PurchasingSidebar.vue')),
//   hygiene: defineAsyncComponent(() => import('../Dashborad/Sidebars/HygieneSidebar.vue')),
//   biomedical: defineAsyncComponent(() => import('../Dashborad/Sidebars/BiomedicalSidebar.vue')),
//   catheterization: defineAsyncComponent(() => import('../Dashborad/Sidebars/CatheterizationSidebar.vue')),
//   archive: defineAsyncComponent(() => import('../Dashborad/Sidebars/ArchiveSidebar.vue')),
//   hr: defineAsyncComponent(() => import('../Dashborad/Sidebars/HrSidebar.vue')),
//   maintenance: defineAsyncComponent(() => import('../Dashborad/Sidebars/MaintenanceSidebar.vue')),
 // doctor: defineAsyncComponent(() => import('../Dashborad/Sidebars/DoctorSidebar.vue')), // A specific sidebar for doctor role
  // ... add all 20+ of your app sidebars here
};

export default sidebarManifest;