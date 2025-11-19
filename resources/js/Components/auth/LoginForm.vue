<template>
  <div class="min-h-screen bg-gray-100 flex justify-center items-center p-3">
    <div class="login-box bg-white shadow-lg rounded-lg overflow-hidden max-w-md w-full">
      <!-- Image at the Top -->
      <div class="bg-primary text-center p-4">
        <div class="rounded-circle mx-auto border border-primary shadow"
          style="width: 120px; height: 120px; overflow: hidden;">
          <img src="/login.png" alt="Doctor image" class="w-100 h-100" style="object-fit: cover;" />
        </div>
        <h1 class="text-white text-2xl font-bold">HIS</h1>
        <h2 class="text-white text-lg">Oasis Clinic</h2>
      </div>

      <!-- Login Form -->
      <div class="p-6 m-3">
        <p class="text-center text-gray-600 mb-4">Sign in to start your session</p>
        
        <!-- Loading Spinner -->
        <div v-if="authStore.isLoading" class="text-center mb-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <form @submit.prevent="handleLogin" :class="{ 'opacity-50': authStore.isLoading }">
          <!-- Email Field -->
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <div class="input-group">
              <input 
                v-model="form.email" 
                type="email" 
                id="email" 
                class="form-control"
                placeholder="Enter your email" 
                required 
                :disabled="authStore.isLoading"
                autocomplete="email"
              >
              <span class="input-group-text">
                <i class="fas fa-envelope"></i>
              </span>
            </div>
          </div>

          <!-- Password Field -->
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input 
                v-model="form.password" 
                :type="showPassword ? 'text' : 'password'" 
                id="password"
                class="form-control" 
                placeholder="Enter your password" 
                required 
                :disabled="authStore.isLoading"
                autocomplete="current-password"
              >
              <span class="input-group-text cursor-pointer" @click="togglePasswordVisibility">
                <i :class="['fas', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
              </span>
            </div>
          </div>

          <!-- Remember Me & Submit -->
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input 
                v-model="form.remember" 
                class="form-check-input" 
                type="checkbox" 
                id="remember" 
                :disabled="authStore.isLoading"
              />
              <label class="form-check-label text-sm text-muted" for="remember">
                Remember Me
              </label>
            </div>
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="authStore.isLoading"
            >
              <span v-if="authStore.isLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ authStore.isLoading ? 'Signing In...' : 'Sign In' }}
            </button>
          </div>
        </form>

        <!-- Error Message -->
        <div 
          v-if="authStore.loginError"
          class="alert alert-danger d-flex align-items-center" 
          role="alert"
        >
          <i class="fas fa-exclamation-circle me-2"></i>
          {{ authStore.loginError }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const handleLogin = async () => {
  const result = await authStore.login({
    email: form.email,
    password: form.password,
    remember: form.remember
  })

  if (result.success) {
    // Navigate to dashboard/home without page refresh
    router.push('/dashboard')
  }
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}
</script>

<style scoped>
.bg-primary {
  background-color: #4F46E5;
}

.bg-primary-dark {
  background-color: #3730A3;
}

.form-input {
  border-radius: 0.375rem;
  padding: 0.5rem;
  width: 100%;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #4B5563;
  background-color: #F9FAFB;
  border: 1px solid #D1D5DB;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #4F46E5;
  background-color: #E5E7EB;
}

.cursor-pointer {
  cursor: pointer;
}

.opacity-50 {
  opacity: 0.5;
}
</style>
