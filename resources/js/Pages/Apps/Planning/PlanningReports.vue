<script setup>
import { ref } from 'vue';
import PlanningSidebar from '../../Dashborad/Sidebars/PlanningSidebar.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';

const reports = ref([
  {
    id: 1,
    title: 'Monthly Nursing Coverage',
    description: 'Overview of nursing staff coverage for the month',
    icon: 'fas fa-user-nurse',
    color: '#10B981'
  },
  {
    id: 2,
    title: 'Monthly Doctor Coverage',
    description: 'Overview of doctor coverage for the month',
    icon: 'fas fa-user-md',
    color: '#3B82F6'
  },
  {
    id: 3,
    title: 'Shift Distribution',
    description: 'Analysis of shift distribution across staff',
    icon: 'fas fa-chart-pie',
    color: '#8B5CF6'
  },
  {
    id: 4,
    title: 'Weekly Summary',
    description: 'Weekly planning summary and statistics',
    icon: 'fas fa-chart-line',
    color: '#F59E0B'
  }
]);

</script>

<template>
  <div class="tw-flex tw-h-screen tw-overflow-hidden">
    <!-- Sidebar -->
    <PlanningSidebar />

    <!-- Main Content -->
    <div class="tw-flex-1 tw-flex tw-flex-col tw-overflow-hidden">
      <!-- Header -->
      <div class="tw-bg-gradient-to-r tw-from-orange-600 tw-to-orange-500 tw-text-white tw-px-8 tw-py-6 tw-shadow-lg">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">Planning Reports</h1>
            <p class="tw-text-orange-100">Generate and view planning reports and analytics</p>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="tw-flex-1 tw-overflow-auto tw-bg-gray-50 tw-p-8">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <Card 
            v-for="report in reports" 
            :key="report.id"
            class="hover:tw-shadow-lg tw-transition-shadow"
          >
            <template #content>
              <div class="tw-flex tw-items-start tw-gap-4">
                <div 
                  class="tw-w-16 tw-h-16 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0"
                  :style="{ backgroundColor: report.color + '20' }"
                >
                  <i 
                    :class="report.icon" 
                    class="tw-text-3xl"
                    :style="{ color: report.color }"
                  ></i>
                </div>
                <div class="tw-flex-1">
                  <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-2">{{ report.title }}</h3>
                  <p class="tw-text-gray-600 tw-mb-4">{{ report.description }}</p>
                  <Button 
                    label="Generate Report"
                    icon="fas fa-file-download"
                    class="p-button-sm p-button-outlined"
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>
