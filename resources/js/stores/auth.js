// stores/auth.js
import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isLoading: false,
    isAuthenticated: false,
    loginError: null,
  }),

  actions: {
    async login(credentials) {
      this.isLoading = true;
      this.loginError = null;
      
      try {
        // First, get CSRF token (required by Fortify)
        await axios.get('/sanctum/csrf-cookie');
        
        // Attempt login via Fortify
        const response = await axios.post('/login', credentials);
        
        // After successful login, get the authenticated user
        await this.getUser();
        
        return { success: true };
      } catch (error) {
        this.loginError = error.response?.data?.message || 
                          error.response?.data?.errors?.email?.[0] || 
                          'Login failed';
        this.isAuthenticated = false;
        this.user = null;
        return { success: false, error: this.loginError };
      } finally {
        this.isLoading = false;
      }
    },

    async logout() {
      this.isLoading = true;
      
      try {
        // Logout via Fortify
        await axios.post('/logout');
        this.user = null;
        this.isAuthenticated = false;
        
        return { success: true };
      } catch (error) {
        console.error('Logout error:', error);
        // Force logout even if API call fails
        this.user = null;
        this.isAuthenticated = false;
        return { success: false };
      } finally {
        this.isLoading = false;
      }
    },

    async getUser() {
      this.isLoading = true;
      
      try {
        // Get authenticated user (adjust endpoint based on your setup)
        const response = await axios.get('/api/setting/user');
        this.user = response.data;
        this.isAuthenticated = true;
      } catch (error) {
        this.user = null;
        this.isAuthenticated = false;
      } finally {
        this.isLoading = false;
      }
    },

    // Initialize auth state on app load
    async initializeAuth() {
      try {
        await this.getUser();
      } catch (error) {
        // User not authenticated
        this.user = null;
        this.isAuthenticated = false;
      }
    }
  }
});
