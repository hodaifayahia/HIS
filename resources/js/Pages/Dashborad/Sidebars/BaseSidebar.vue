<script setup>
import { defineProps, defineEmits, computed } from 'vue';
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

// Computed property to create a linear gradient background from the appColor prop
const sidebarStyle = computed(() => {
  // Use the appColor and a slightly darker version for a gradient effect
  const color = props.appColor;
  const darkerColor = darkenColor(color, 20); // Darken by 20%
  return {
    background: `linear-gradient(135deg, ${color} 0%, ${darkerColor} 100%)`,
    '--app-color': color,
  };
});

// Helper function to darken a hex color
function darkenColor(hex, percent) {
  let f = parseInt(hex.slice(1), 16),
    t = percent < 0 ? 0 : 255,
    p = percent / 100,
    R = f >> 16,
    G = f >> 8 & 0x00FF,
    B = f & 0x0000FF;
  return "#" + (0x1000000 + (Math.round((t - R) * p) + R) * 0x10000 + (Math.round((t - G) * p) + G) * 0x100 + (Math.round((t - B) * p) + B)).toString(16).slice(1);
}

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
    class="main-sidebar tw-fixed tw-top-0 tw-left-0 tw-h-screen tw-w-64 tw-transition-transform tw-duration-300 tw-ease-in-out tw-transform -tw-translate-x-full md:tw-translate-x-0 tw-shadow-lg tw-z-50"
    :style="sidebarStyle"
  >
    <div class="tw-flex tw-items-center tw-justify-start tw-p-4 tw-mb-4 tw-bg-white tw-bg-opacity-10">
      <img :src="appIcon" class="tw-rounded-full tw-w-10 tw-h-10 tw-shadow-md tw-mr-3" :alt="appName">
      <span class="tw-text-white tw-font-semibold tw-text-xl tw-tracking-wide">{{ appName }}</span>
    </div>

    <div class="tw-flex tw-items-center tw-p-4 tw-mb-4 tw-bg-white tw-bg-opacity-10 tw-rounded-lg tw-mx-3">
      <img :src="user.avatar" class="tw-rounded-full tw-w-10 tw-h-10 tw-shadow-md tw-object-cover tw-mr-3" alt="User Image">
      <div class="tw-flex-1">
        <span class="tw-block tw-text-white tw-font-medium">{{ user.name }}</span>
        <span class="tw-block tw-text-sm tw-text-gray-200">{{ user.role }}</span>
      </div>
    </div>

    <nav class="tw-mt-4 tw-px-3">
      <ul class="tw-space-y-2">
        <slot name="navigation"></slot>

        <li>
          <button @click="goBack()" class="tw-w-full tw-flex tw-items-center tw-px-4 tw-py-2 tw-rounded-lg tw-text-white tw-font-medium tw-transition-all tw-duration-200 hover:tw-bg-white hover:tw-bg-opacity-20">
            <i class="nav-icon fas fa-arrow-left tw-mr-3 tw-text-lg"></i>
            <span class="tw-text-base">Back to Dashboard</span>
          </button>
        </li>

        <li>
          <a href="#" @click.prevent="logout" class="tw-w-full tw-flex tw-items-center tw-px-4 tw-py-2 tw-rounded-lg tw-text-white tw-font-medium tw-transition-all tw-duration-200 hover:tw-bg-white hover:tw-bg-opacity-20">
            <i class="nav-icon fas fa-sign-out-alt tw-mr-3 tw-text-lg"></i>
            <span class="tw-text-base">Logout</span>
          </a>
        </li>
      </ul>
    </nav>
  </aside>
</template>

<style scoped>
/* Scoped styles to ensure Tailwind and AdminLTE play nice.
   The 'tw-' prefix on all Tailwind classes prevents conflicts. */

/* Hide the AdminLTE default sidebar overflow behavior for a full-height sidebar */
.main-sidebar {
  overflow-y: hidden;
}

/* Ensure font awesome icons look good */
.nav-icon {
  width: 1.25rem;
  text-align: center;
}
</style>