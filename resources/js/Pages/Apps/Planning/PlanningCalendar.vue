<script setup>
import { ref, computed, onMounted } from 'vue';
import PlanningSidebar from '../../Dashborad/Sidebars/PlanningSidebar.vue';
import Card from 'primevue/card';
import Calendar from 'primevue/calendar';

const selectedDate = ref(new Date());

const events = ref([
  // Sample events - would be loaded from API
]);

</script>

<template>
  <div class="tw-flex tw-h-screen tw-overflow-hidden">
    <!-- Sidebar -->
    <PlanningSidebar />

    <!-- Main Content -->
    <div class="tw-flex-1 tw-flex tw-flex-col tw-overflow-hidden">
      <!-- Header -->
      <div class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-purple-500 tw-text-white tw-px-8 tw-py-6 tw-shadow-lg">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">Planning Calendar</h1>
            <p class="tw-text-purple-100">View all planning schedules in calendar format</p>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="tw-flex-1 tw-overflow-auto tw-bg-gray-50 tw-p-8">
        <Card>
          <template #content>
            <div class="tw-text-center tw-py-12">
              <i class="fas fa-calendar-alt tw-text-8xl tw-text-purple-200 tw-mb-4"></i>
              <h3 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-2">Calendar View</h3>
              <p class="tw-text-gray-600">Calendar integration coming soon</p>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>
