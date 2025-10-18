<template>
  <div class="content-header">
    <div class="header-flex-container">
      <div class="header-left">
        <h1 class="page-title">{{ title }}</h1>
        <p class="page-subtitle">{{ subtitle }}</p>
      </div>
      <nav class="breadcrumbs">
        <ul class="breadcrumb-list">
          <li><a href="#" class="breadcrumb-link">Home</a></li>
          <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
          <li class="breadcrumb-current">{{ currentBreadcrumb }}</li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';

defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    required: true,
  },
  currentBreadcrumb: {
    type: String,
    required: true,
  },
});
</script>

<style scoped>

</style>