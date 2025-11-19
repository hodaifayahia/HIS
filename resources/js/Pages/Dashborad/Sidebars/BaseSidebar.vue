<!-- Enhanced BaseSidebar -->
<script setup>
import { defineProps, defineEmits } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const props = defineProps({
  user: {
    type: Object,
    required: true,
    default: () => ({})
  },
  appName: {
    type: String,
    required: true
  },
  appIcon: {
    type: String,
    required: true
  },
  appColor: {
    type: String,
    default: '#2563eb' // Blue-600
  },
  backRoute: {
    type: String,
    default: '/home'
  }
});

const emit = defineEmits(['logout']);
const router = useRouter();

// Logout Function
const logout = async () => {
  try {
    await axios.post('/logout');
    emit('logout');
    window.location.href = '/login';
  } catch (error) {
    console.error('Error logging out:', error);
  }
};

const goBack = () => {
  router.push(props.backRoute);
};
</script>

<template>
  <aside 
    class="main-sidebar sidebar-dark-primary elevation-4 fixed tw-overflow-hidden" 
    :style="{ background: `linear-gradient(135deg, ${appColor}, ${appColor}dd)` }"
  >
    <!-- Enhanced Brand Link -->
    <div class="brand-link tw-bg-white/10 tw-backdrop-blur tw-border-b tw-border-white/10 tw-flex tw-items-center tw-gap-3 tw-px-4 tw-py-3">
      <div>
        <span class="tw-text-white tw-font-bold tw-text-lg tw-tracking-wide">{{ appName }}</span>
      </div>
    </div>

    <div class="sidebar tw-h-full tw-overflow-y-auto">
      <!-- Enhanced User Panel -->
      <div class="user-panel tw-mt-4 tw-pb-4 tw-mb-4 tw-px-3 tw-border-b tw-border-white/10">
        <div class="tw-bg-white/5 tw-backdrop-blur tw-rounded-xl tw-p-3">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-relative">
              <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-br tw-from-green-400 tw-to-blue-500 tw-rounded-full tw-blur tw-opacity-60"></div>
              <img 
                :src="user.avatar || '/default-avatar.png'" 
                class="tw-relative tw-w-12 tw-h-12 tw-rounded-full tw-border-2 tw-border-white/50 tw-shadow-lg" 
                alt="User"
              >
              <div class="tw-absolute tw-bottom-0 tw-right-0 tw-w-3 tw-h-3 tw-bg-green-500 tw-rounded-full tw-border-2 tw-border-white"></div>
            </div>
            <div class="tw-flex-1">
              <div class="tw-text-white tw-font-semibold">{{ user.name || 'User' }}</div>
              <div class="tw-text-white/60 tw-text-xs">{{ user.role || 'Staff' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="tw-px-3">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- App-specific navigation items (slot content) -->
          <div class="tw-mb-4">
            <slot name="navigation"></slot>
          </div>

          <!-- Divider -->
          <div class="tw-h-px tw-bg-white/10 tw-my-4"></div>

          <!-- Common Actions -->
          <li class="nav-item tw-mb-2">
            <button 
              @click="goBack()" 
              class="nav-link tw-w-full tw-text-left tw-bg-transparent tw-border-0 tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1 hover:tw-bg-white/10"
            >
              <div class="tw-w-9 tw-h-9 tw-bg-white/10 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <i class="fas fa-arrow-left tw-text-white"></i>
              </div>
              <p class="tw-font-medium">Back to Dashboard</p>
            </button>
          </li>

          <li class="nav-item tw-mb-2 d-flex">
            <a 
              href="#" 
              @click.prevent="logout" 
              class="nav-link tw-rounded-lg tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 hover:tw-translate-x-1 hover:tw-bg-red-500/20"
            >
              <div class="tw-w-9 tw-h-9 tw-bg-red-500/20 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <i class="fas fa-sign-out-alt tw-text-red-300"></i>
              </div>
              <p class="tw-font-medium tw-text-red-200">Logout</p>
            </a>
          </li>
        </ul>
      </nav>

      <!-- Footer Info -->
      <div class="tw-absolute tw-bottom-0 tw-left-0 tw-right-0 tw-p-4 tw-bg-black/20">
        <div class="tw-text-white/40 tw-text-xs tw-text-center">
          <div>© 2025 Emergency System</div>
          <div class="tw-mt-1">Version 2.0</div>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
/* Smooth scrollbar */
.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Remove default AdminLTE styles that interfere */
.main-sidebar .nav-link {
  display: flex;
  border-radius: 0.5rem;
  margin-bottom: 0.25rem;
  transition: all 0.3s ease;
}

.main-sidebar .nav-link p {
  margin: 0;
  color: rgba(255, 255, 255, 0.9);
}

.main-sidebar .nav-link:hover {
  background: rgba(255, 255, 255, 0.1);
}

/* Enhanced shadow for depth */
.main-sidebar {
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
}

/* Smooth transitions */
* {
  transition-property: background-color, transform, opacity;
  transition-duration: 0.3s;
  transition-timing-function: ease;
}
</style>