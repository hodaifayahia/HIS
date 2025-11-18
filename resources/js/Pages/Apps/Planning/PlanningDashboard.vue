<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Card from 'primevue/card';
import Button from 'primevue/button';

const router = useRouter();

// Planning statistics
const stats = ref({
  totalNursingPlans: 0,
  totalDoctorPlans: 0,
  activeToday: 0,
  upcomingWeek: 0
});

// Quick actions
const quickActions = [
  {
    title: 'Emergency Nursing',
    description: 'Manage emergency nursing schedules and shifts',
    icon: 'fas fa-user-nurse',
    color: '#10B981',
    route: '/planning/emergency-nursing'
  },
  {
    title: 'Emergency Doctor',
    description: 'Manage emergency doctor schedules and shifts',
    icon: 'fas fa-user-md',
    color: '#3B82F6',
    route: '/planning/emergency-doctor'
  },
  {
    title: 'Calendar View',
    description: 'View all planning in calendar format',
    icon: 'fas fa-calendar-alt',
    color: '#8B5CF6',
    route: '/planning/calendar'
  },
  {
    title: 'Reports',
    description: 'Generate and view planning reports',
    icon: 'fas fa-chart-bar',
    color: '#F59E0B',
    route: '/planning/reports'
  }
];

// Navigate to specific planning module
const navigateTo = (route) => {
  router.push(route);
};

onMounted(() => {
  // Load statistics (would normally call API)
  loadStatistics();
});

const loadStatistics = () => {
  // Placeholder - integrate with actual API
  stats.value = {
    totalNursingPlans: 45,
    totalDoctorPlans: 32,
    activeToday: 12,
    upcomingWeek: 28
  };
};
</script>

<template>
  <div class="tw-flex tw-flex-col tw-h-screen tw-overflow-hidden">
    <!-- Main Content -->
    <div class="tw-flex-1 tw-flex tw-flex-col tw-overflow-hidden">
      <!-- Header -->
      <div class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-teal-500 tw-text-white tw-px-8 tw-py-6 tw-shadow-lg">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">Planning Dashboard</h1>
            <p class="tw-text-teal-100">Manage emergency nursing and doctor schedules</p>
          </div>
          <div class="tw-flex tw-gap-3">
            <i class="fas fa-clipboard-check tw-text-6xl tw-opacity-20"></i>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="tw-flex-1 tw-overflow-auto tw-bg-gray-50 tw-p-8">
        <!-- Statistics Cards -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
          <Card class="tw-border-l-4 tw-border-teal-500 hover:tw-shadow-lg tw-transition-shadow">
            <template #content>
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-gray-600 tw-text-sm tw-mb-1">Nursing Plans</p>
                  <p class="tw-text-3xl tw-font-bold tw-text-gray-800">{{ stats.totalNursingPlans }}</p>
                </div>
                <div class="tw-w-14 tw-h-14 tw-bg-teal-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="fas fa-user-nurse tw-text-2xl tw-text-teal-600"></i>
                </div>
              </div>
            </template>
          </Card>

          <Card class="tw-border-l-4 tw-border-blue-500 hover:tw-shadow-lg tw-transition-shadow">
            <template #content>
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-gray-600 tw-text-sm tw-mb-1">Doctor Plans</p>
                  <p class="tw-text-3xl tw-font-bold tw-text-gray-800">{{ stats.totalDoctorPlans }}</p>
                </div>
                <div class="tw-w-14 tw-h-14 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="fas fa-user-md tw-text-2xl tw-text-blue-600"></i>
                </div>
              </div>
            </template>
          </Card>

          <Card class="tw-border-l-4 tw-border-green-500 hover:tw-shadow-lg tw-transition-shadow">
            <template #content>
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-gray-600 tw-text-sm tw-mb-1">Active Today</p>
                  <p class="tw-text-3xl tw-font-bold tw-text-gray-800">{{ stats.activeToday }}</p>
                </div>
                <div class="tw-w-14 tw-h-14 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="fas fa-calendar-day tw-text-2xl tw-text-green-600"></i>
                </div>
              </div>
            </template>
          </Card>

          <Card class="tw-border-l-4 tw-border-purple-500 hover:tw-shadow-lg tw-transition-shadow">
            <template #content>
              <div class="tw-flex tw-items-center tw-justify-between">
                <div>
                  <p class="tw-text-gray-600 tw-text-sm tw-mb-1">Upcoming Week</p>
                  <p class="tw-text-3xl tw-font-bold tw-text-gray-800">{{ stats.upcomingWeek }}</p>
                </div>
                <div class="tw-w-14 tw-h-14 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <i class="fas fa-calendar-week tw-text-2xl tw-text-purple-600"></i>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Quick Actions -->
        <div class="tw-mb-6">
          <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
            <i class="fas fa-bolt tw-text-teal-500"></i>
            Quick Actions
          </h2>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
          <Card 
            v-for="action in quickActions" 
            :key="action.route"
            class="hover:tw-shadow-xl tw-transition-all tw-duration-300 hover:tw-scale-105 tw-cursor-pointer"
            @click="navigateTo(action.route)"
          >
            <template #content>
              <div class="tw-text-center tw-py-4">
                <div 
                  class="tw-w-20 tw-h-20 tw-rounded-full tw-mx-auto tw-mb-4 tw-flex tw-items-center tw-justify-center"
                  :style="{ backgroundColor: action.color + '20' }"
                >
                  <i 
                    :class="action.icon" 
                    class="tw-text-4xl"
                    :style="{ color: action.color }"
                  ></i>
                </div>
                <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-2">{{ action.title }}</h3>
                <p class="tw-text-gray-600 tw-text-sm tw-mb-4">{{ action.description }}</p>
                <Button 
                  :label="'Go to ' + action.title"
                  icon="fas fa-arrow-right"
                  iconPos="right"
                  class="p-button-sm"
                  :style="{ backgroundColor: action.color, borderColor: action.color }"
                />
              </div>
            </template>
          </Card>
        </div>

        <!-- Recent Activity Section -->
        <div class="tw-mt-8">
          <Card>
            <template #header>
              <div class="tw-p-4 tw-border-b">
                <h2 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-gap-2">
                  <i class="fas fa-history tw-text-teal-500"></i>
                  Recent Activity
                </h2>
              </div>
            </template>
            <template #content>
              <div class="tw-text-center tw-py-8 tw-text-gray-500">
                <i class="fas fa-inbox tw-text-6xl tw-mb-4 tw-opacity-20"></i>
                <p>No recent activity to display</p>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tw-grid > * {
  animation: fadeInUp 0.5s ease-out;
}
</style>
