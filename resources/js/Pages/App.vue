<script setup>
import { onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Navbar from './Dashborad/Navbar.vue';
import Footer from './Dashborad/Footer.vue';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';
import sidebarManifest from '../Components/config/sidebarManifest.js'; // Import the centralized manifest

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { user, isLoading } = storeToRefs(authStore);

// List of routes that should NOT show the dashboard layout at all (e.g., login, home)
const noLayoutRoutes = [
  'home',
  'auth.login',
  // Add more routes as needed that are full-page layouts without any dashboard elements
];

// Check if current route should hide the dashboard layout
const shouldHideLayout = computed(() => {
  return noLayoutRoutes.includes(route.name) || route.meta?.hideLayout;
});

// Determine which sidebar component to render dynamically from the manifest
const currentSidebarComponent = computed(() => {
  const appKey = route.meta?.appKey || 'default'; // Get appKey from route meta, default to 'default'
  // Look up the component in the manifest. Fallback to 'default' if appKey not found.
  return sidebarManifest[appKey] || sidebarManifest.default;
});

// Check if back button should be shown (exclude home route)
const showBackButton = computed(() => {
  return shouldHideLayout.value && route.name !== 'home' && route.name !== 'auth.login';
});

onMounted(async () => {
  await authStore.getUser();
});
</script>

<template>
  <div v-if="!isLoading" class="wrapper" id="app" >
    <template v-if="!shouldHideLayout">
      <Navbar />
      <component :is="currentSidebarComponent" :user="user" /> 
      
      <div class="content-wrapper" style="margin-top: 60px;" >
        <router-view></router-view>
      </div>
      <Footer />
    </template>
    
    <template v-else>
      <div class="full-page-layout ">
        <button 
          v-if="showBackButton" 
          @click="router.go(-1)"
          class="back-button"
        >
          <img width="27" height="30" src="https://img.icons8.com/ios/50/squared-menu.png" alt="Back"/>
        </button>
        <router-view></router-view>
      </div>
    </template>
  </div>
</template>

<style scoped>
/* Your existing styles for .full-page-layout and .back-button */

</style>