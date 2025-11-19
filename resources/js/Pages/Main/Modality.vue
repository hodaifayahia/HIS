<template>
  <div class="modality-page">
    <!-- Include the ModalitySidebar -->
    <ModalitySidebar />
    
    <!-- Main content area with router-view for nested routes -->
    <div class="main-content">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import ModalitySidebar from '../Dashborad/Sidebars/ModalitySidebar.vue';
</script>

<style scoped>
.modality-page {
  display: flex;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  padding: 2rem;
  background: #f9fafb;
}
</style>