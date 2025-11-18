// src/router/routes/planning.js

const planningRoutes = [
  {
    path: '/planning',
    name: 'planning',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'planning' },
    children: [
      {
        path: 'dashboard',
        name: 'planning.dashboard',
        component: () => import('../Pages/Apps/Planning/PlanningDashboard.vue'),
        meta: {
          requiresAuth: true,
          title: 'Planning Dashboard'
        }
      },
      {
        path: 'emergency-nursing',
        name: 'planning.emergency-nursing',
        component: () => import('../Pages/Apps/Emergency/EmergencyPlanning/NursingEmergencyPlanning.vue'),
        meta: {
          requiresAuth: true,
          title: 'Emergency Nursing Planning'
        }
      },
      {
        path: 'emergency-doctor',
        name: 'planning.emergency-doctor',
        component: () => import('../Pages/Apps/Emergency/EmergencyPlanning/DoctorEmergencyPlanning.vue'),
        meta: {
          requiresAuth: true,
          title: 'Emergency Doctor Planning'
        }
      },
      {
        path: 'calendar',
        name: 'planning.calendar',
        component: () => import('../Pages/Apps/Planning/PlanningCalendar.vue'),
        meta: {
          requiresAuth: true,
          title: 'Planning Calendar'
        }
      },
      {
        path: 'reports',
        name: 'planning.reports',
        component: () => import('../Pages/Apps/Planning/PlanningReports.vue'),
        meta: {
          requiresAuth: true,
          title: 'Planning Reports'
        }
      }
    ],
  },
];

export default planningRoutes;
