// router/guards.js
import { useAuthStore } from '../stores/auth';

export function setupRouterGuards(router) {
  router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // If store hasn't been initialized and we need auth, wait for it
    if (to.meta.requiresAuth && authStore.user === null && !authStore.isLoading) {
      await authStore.initializeAuth();
    }
    
    // Check if route requires authentication
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return next('/login');
    }
    
    // Check if route requires guest (not authenticated)
    if (to.meta.requiresGuest && authStore.isAuthenticated) {
      return next('/home');
    }
    
    next();
  });
}
