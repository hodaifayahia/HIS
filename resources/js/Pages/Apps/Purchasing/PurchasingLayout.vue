<template>
  <div class="tw-min-h-screen tw-bg-gray-50">
    <!-- Main Content -->
    <div class="tw-w-full">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

// Any shared logic for purchasing module can go here
onMounted(() => {
  // Initialize any global purchasing data if needed
})
</script>

<style scoped>
/* Purchasing module specific styles */
</style>