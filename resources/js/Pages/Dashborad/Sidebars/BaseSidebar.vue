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
    default: '#2196F3'
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
  <aside class="main-sidebar   sidebar-dark-primary elevation-4 fixed " :style="{ background: appColor }">
    <a href="#" class="brand-link d-flex align-items-center">
      <img :src="appIcon" class="img-circle elevation-2 me-2 mr-3" style="width: 40px; height: 40px;" :alt="appName">
      <span class="brand-text font-weight-light ms-">{{ appName }}</span>
    </a>
    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex justify-content-between">
        <div class="image">
          <img :src="user.avatar" class="img-circle elevation-2 mr-2" alt="User Image"
            style="height: 40px; width: 40px; object-fit: cover;">
        </div>
        <div class="info">
          <a href="#" class="d-block text-center mr-5 mt-1">{{ user.name }}</a>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <!-- App-specific navigation items (slot content) -->
          <slot name="navigation"></slot>

          <!-- Common navigation items -->
          <li class="nav-item">
            <button @click="goBack()" class="nav-link btn btn-link text-left">
              <i class="nav-icon fas fa-arrow-left"></i>
              <p>Back to Dashboard</p>
            </button>
          </li>

             <router-link to="/admin/appointments/patient-remise-requests" active-class="active" class="nav-link">
                  <i class="nav-icon fas fa-file-invoice-dollar"></i>
                  <p>Patient Remise Requests</p>
            </router-link>

          <li class="nav-item mr-2">
            <form style="display: contents;">
              <a href="#" @click.prevent="logout" class="nav-link">
                <i class="nav-icon fas fa-sign-out-alt mr-2"></i>
                Logout
              </a>
            </form>
          </li>
        </ul>
      </nav>
    </div>
  </aside>
</template>

<style scoped>
.base-sidebar {
  background: linear-gradient(135deg, var(--app-color, #2196F3) 0%, var(--app-color-dark, #1976D2) 100%);
}

.base-sidebar .brand-link {
  background: rgba(255, 255, 255, 0.1);
}

.base-sidebar .nav-link.active {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

.base-sidebar .nav-link:hover {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

.btn-link {
  border: none;
  background: none;
  width: 100%;
  padding: 0.5rem 1rem;
  text-align: left;
  color: rgba(255, 255, 255, 0.8);
}

.btn-link:hover {
  color: white;
  text-decoration: none;
}
</style>