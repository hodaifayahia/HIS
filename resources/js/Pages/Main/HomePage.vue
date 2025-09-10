<template>
  <div class="dashboard-container">
    <!-- Enhanced Navigation Bar -->
    <nav class="navbar">
      <div class="navbar-content">
        <!-- Left Section -->
        <div class="navbar-left">
        
          <div class="brand">
            <i class="fas fa-hospital-alt brand-icon"></i>
            <span class="brand-text">MedSystem</span>
          </div>
           <div class="navbar-center">
          <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              class="search-input"
              placeholder="Search applications..." 
              aria-label="Search apps"
              @input="onSearch"
            >
            <div class="search-shortcut">⌘K</div>
          </div>
        </div>
        </div>

        <!-- Center Search -->
       

        <!-- Right Section -->
        <div class="navbar-right">
          <button class="nav-button notification-btn" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">3</span>
          </button>
          
          <div class="user-menu" @click="toggleUserMenu">
            <div class="user-avatar">
              <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-info">
              <span class="user-name">{{ user?.data?.name || 'Admin' }}</span>
              <span class="user-role">{{ user?.data?.role || 'Administrator' }}</span>
            </div>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
            
            <!-- User Dropdown -->
            <div class="dropdown-menu" :class="{ show: showUserMenu }">
              <a href="#" class="dropdown-item">
                <i class="fas fa-user"></i>
                Profile
              </a>
              <a href="#" class="dropdown-item">
                <i class="fas fa-cog"></i>
                Settings
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" @click.prevent="logout" class="dropdown-item logout">
                <i class="fas fa-sign-out-alt"></i>
                Sign Out
              </a>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Layout -->
    <div class="main-layout">
      <!-- Sidebar (Mobile overlay) -->
    

      <!-- Main Content -->
      <main class="main-content">
        <!-- Header Section -->
     

        <!-- Applications Section -->
        <section class="apps-section">
          

          <!-- Applications Grid/List -->
          <div class="apps-container">
            <div v-if="filteredApps.length === 0" class="empty-state">
              <i class="fas fa-search"></i>
              <h3>No applications found</h3>
              <p>Try adjusting your search criteria</p>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="apps-grid">
              <div 
                v-for="app in filteredApps" 
                :key="app.id" 
                class="app-card"
                @click="navigateToApp(app)"
                @keydown.enter="navigateToApp(app)"
                tabindex="0"
                role="button"
                :aria-label="`Open ${app.name} application`"
              >
                <div class="app-card-content">
                  <div class="app-icon" :style="{ backgroundColor: app.color }">
                    <i :class="app.icon"></i>
                  </div>
                  <div class="app-info">
                    <h3 class="app-name">{{ app.name }}</h3>
                    <p class="app-description">{{ getAppDescription(app.name) }}</p>
                  </div>
                </div>
                <div class="app-actions">
                  <button class="action-btn favorite" @click.stop="toggleFavorite(app.id)">
                    <i class="fas fa-star"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- List View -->
            <div v-else class="apps-list">
              <div 
                v-for="app in filteredApps" 
                :key="app.id" 
                class="app-list-item"
                @click="navigateToApp(app)"
                @keydown.enter="navigateToApp(app)"
                tabindex="0"
                role="button"
                :aria-label="`Open ${app.name} application`"
              >
                <div class="app-icon" :style="{ backgroundColor: app.color }">
                  <i :class="app.icon"></i>
                </div>
                <div class="app-content">
                  <div class="app-details">
                    <h3 class="app-name">{{ app.name }}</h3>
                    <p class="app-description">{{ getAppDescription(app.name) }}</p>
                  </div>
                  <div class="app-actions">
                    <button class="action-btn" @click.stop="toggleFavorite(app.id)">
                      <i class="fas fa-star"></i>
                    </button>
                    <button class="action-btn">
                      <i class="fas fa-external-link-alt"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" :class="{ active: sidebarOpen }" @click="closeSidebar"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { storeToRefs } from 'pinia';

// Store setup
const authStore = useAuthStore();
const { user, isLoading } = storeToRefs(authStore);
const router = useRouter();

// Reactive state
const viewMode = ref('grid');
const searchQuery = ref('');
const selectedCategory = ref('');
const sidebarOpen = ref(false);
const showUserMenu = ref(false);

// Apps data
const allApps = ref([
  { 
    id: 2, 
    name: 'Calendar', 
    icon: 'fas fa-calendar-alt',
    color: '#10B981',
    route: '/calander',
    category: 'administrative'
  },
  { 
    id: 3, 
    name: 'Appointments', 
    icon: 'fas fa-calendar-check',
    color: '#F59E0B',
    route: '/admin/appointments/specialization',
    category: 'administrative'
  },
  { 
    id: 5, 
    name: 'Consultation', 
    icon: 'fas fa-stethoscope',
    color: '#8B5CF6',
    route: '/admin/consultations/consultation',
    category: 'clinical'
  },
  { 
    id: 6, 
    name: 'Reception', 
    icon: 'fas fa-concierge-bell',
    color: '#3B82F6',
    route: '/reception',
    category: 'administrative'
  },
  { 
    id: 7, 
    name: 'Configuration', 
    icon: 'fas fa-cogs',
    color: '#6B7280',
    route: '/admin/configuration',
    category: 'administrative'
  },
  { 
    id: 8, 
    name: 'Caisse', 
    icon: 'fas fa-cash-register',
    color: '#14B8A6',
    route: '/admin/caisse',
    category: 'financial'
  },
  { 
    id: 9, 
    name: 'Coffre', 
    icon: 'fas fa-lock',
    color: '#9CA3AF',
    route: '/admin/coffre',
    category: 'financial'
  },
  { 
    id: 10, 
    name: 'Banking', 
    icon: 'fas fa-university',
    color: '#F97316',
    route: '/admin/banking',
    category: 'financial'
  },
  { 
    id: 11, 
    name: 'Convention', 
    icon: 'fas fa-handshake',
    color: '#7C3AED',
    route: '/convention',
    category: 'administrative'
  },
  { 
    id: 12, 
    name: 'Facturation', 
    icon: 'fas fa-file-invoice-dollar',
    color: '#0EA5E9',
    route: '/admin/facturation',
    category: 'financial'
  },
  { 
    id: 13, 
    name: 'Infrastructure', 
    icon: 'fas fa-building',
    color: '#EC4899',
    route: '/infrastructure',
    category: 'administrative'
  },
  { 
    id: 14, 
    name: 'CRM', 
    icon: 'fas fa-address-book',
    color: '#6366F1',
    route: '/crm',
    category: 'administrative'
  },
  { 
    id: 15, 
    name: 'Admission', 
    icon: 'fas fa-hospital-user',
    color: '#EF4444',
    route: '/admin/admission',
    category: 'clinical'
  },
  { 
    id: 16, 
    name: 'Emergency', 
    icon: 'fas fa-ambulance',
    color: '#DC2626',
    route: '/admin/emergency',
    category: 'clinical'
  },
  { 
    id: 18, 
    name: 'Nursing', 
    icon: 'fas fa-user-nurse',
    color: '#F472B6',
    route: '/admin/Nursing',
    category: 'clinical'
  },
  { 
    id: 19, 
    name: 'Radiology', 
    icon: 'fas fa-x-ray',
    color: '#059669',
    route: '/admin/radiology',
    category: 'clinical'
  },
  { 
    id: 20, 
    name: 'Hospitalization', 
    icon: 'fas fa-bed',
    color: '#7C3AED',
    route: '/admin/hospitalization',
    category: 'clinical'
  },
  { 
    id: 21, 
    name: 'Laboratory', 
    icon: 'fas fa-microscope',
    color: '#0D9488',
    route: '/admin/laboratory',
    category: 'clinical'
  },
  { 
    id: 22, 
    name: 'Operating Room', 
    icon: 'fas fa-procedures',
    color: '#D97706',
    route: '/admin/operating-room',
    category: 'clinical'
  },
  { 
    id: 23, 
    name: 'Pharmacy', 
    icon: 'fas fa-prescription-bottle-alt',
    color: '#2563EB',
    route: '/admin/pharmacy',
    category: 'clinical'
  },
  { 
    id: 24, 
    name: 'Catering', 
    icon: 'fas fa-utensils',
    color: '#F59E0B',
    route: '/admin/catering',
    category: 'administrative'
  },
  { 
    id: 25, 
    name: 'Inventory', 
    icon: 'fas fa-boxes',
    color: '#6B7280',
    route: '/admin/inventory',
    category: 'administrative'
  },
  { 
    id: 26, 
    name: 'Purchasing', 
    icon: 'fas fa-shopping-cart',
    color: '#EC4899',
    route: '/admin/purchasing',
    category: 'financial'
  },
  { 
    id: 27, 
    name: 'Hygiene', 
    icon: 'fas fa-soap',
    color: '#10B981',
    route: '/admin/hygiene',
    category: 'clinical'
  },
  { 
    id: 28, 
    name: 'Biomedical', 
    icon: 'fas fa-dna',
    color: '#8B5CF6',
    route: '/admin/biomedical',
    category: 'clinical'
  },
  { 
    id: 29, 
    name: 'Ticket Management', 
    icon: 'fas fa-tags',
    color: '#F97316',
    route: '/ticket-management',
    category: 'administrative'
  },
  { 
    id: 30, 
    name: 'Catheterization', 
    icon: 'fas fa-heartbeat',
    color: '#14B8A6',
    route: '/admin/catheterization',
    category: 'clinical'
  },
  { 
    id: 31, 
    name: 'Archive', 
    icon: 'fas fa-archive',
    color: '#9CA3AF',
    route: '/admin/archive',
    category: 'administrative'
  },
  { 
    id: 32, 
    name: 'Human Resources', 
    icon: 'fas fa-id-badge',
    color: '#EF4444',
    route: '/admin/hr',
    category: 'administrative'
  },
  { 
    id: 33, 
    name: 'Dashboard', 
    icon: 'fas fa-tachometer-alt',
    color: '#0EA5E9',
    route: '/admin/dashboard',
    category: 'administrative'
  },
  { 
    id: 34, 
    name: 'Maintenance', 
    icon: 'fas fa-tools',
    color: '#DC2626',
    route: '/admin/maintenance',
    category: 'administrative'
  },
{
  id: 35,
  name: 'portal',
  icon: 'fas fa-user',      // Changed from 'fas fa-solid fa-gift'
  color: '#DC2785',
  route: '/portal',
  category: 'administrative'
},

  { 
    id: 36, 
    name: 'Soon', 
    icon: 'fas fa-solid fa-gift',
    color: '#DC2785',
    route: '/admin/maintenance',
    category: 'administrative'
  },
  { 
    id: 37, 
    name: 'Soon', 
    icon: 'fas fa-solid fa-gift',
    color: '#DC2785',
    route: '/admin/maintenance',
    category: 'administrative'
  },
  { 
    id: 38, 
    name: 'Soon', 
    icon: 'fas fa-solid fa-gift',
    color: '#DC2785',
    route: '/admin/maintenance',
    category: 'administrative'
  },
  { 
    id: 39, 
    name: 'Soon', 
    icon: 'fas fa-solid fa-gift',
    color: '#DC2785',
    route: '/admin/maintenance',
    category: 'administrative'
  },
]);

// Computed properties
const filteredApps = computed(() => {
  let filtered = allApps.value;
  
  // Role-based filtering
  if (user.value?.data.role.toLowerCase() === 'doctor') {
    filtered = filtered.filter(app => 
      app.name === 'Calendar' || app.name === 'Consultation' 
    );
  } else if (user.value?.data.role.toLowerCase() === 'receptionist') {
    filtered = filtered.filter(app => 
      app.name === 'Appointments' || app.name === 'portal'
    );
  }

  // Category filtering
  if (selectedCategory.value) {
    filtered = filtered.filter(app => app.category === selectedCategory.value);
  }

  // Search filtering
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(app => 
      app.name.toLowerCase().includes(query)
    );
  }

  return filtered;
});

// Methods
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
  sidebarOpen.value = false;
};

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
};

const setViewMode = (mode) => {
  viewMode.value = mode;
};

const onSearch = () => {
  // Search functionality handled by computed property
};

const getAppDescription = (appName) => {
  const descriptions = {
    'Calendar': 'Schedule and manage appointments',
    'Appointments': 'Patient appointment management',
    'Consultation': 'Clinical consultation tools',
    'Reception': 'Front desk operations',
    'Configuration': 'System settings and preferences',
    'Caisse': 'Cash management system',
    'Coffre': 'Secure storage management',
    'Banking': 'Financial transactions',
    'Convention': 'Contract and agreement management',
    'Facturation': 'Billing and invoicing',
    'Infrastructure': 'Facility management',
    'CRM': 'Customer relationship management',
    'Admission': 'Patient admission process',
    'Emergency': 'Emergency department management',
    'Nursing': 'Nursing care coordination',
    'Radiology': 'Medical imaging services',
    'Hospitalization': 'Inpatient care management',
    'Laboratory': 'Lab tests and results',
    'Operating Room': 'Surgery scheduling and management',
    'Pharmacy': 'Medication dispensing',
    'Catering': 'Food service management',
    'Inventory': 'Stock and supply management',
    'Purchasing': 'Procurement and orders',
    'Hygiene': 'Infection control and cleanliness',
    'Biomedical': 'Medical equipment maintenance',
    'Ticket Management': 'Support ticket system',
    'Catheterization': 'Cardiac catheterization lab',
    'Archive': 'Document storage and retrieval',
    'Human Resources': 'Staff management',
    'Dashboard': 'Analytics and reporting',
    'Maintenance': 'Facility maintenance',
    'Soon': 'Upcoming features and improvements'
    };
  return descriptions[appName] || `Manage ${appName.toLowerCase()} operations`;
};

const toggleFavorite = (appId) => {
  // Favorite functionality
  console.log('Toggle favorite for app:', appId);
};

const navigateToApp = (app) => {
  router.push(app.route);
};

const logout = async () => {
  try {
    await axios.post('/logout');
    router.push('/');
  } catch (error) {
    console.error('Error logging out:', error);
  }
};

// Lifecycle
onMounted(() => {
  // Close user menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.user-menu')) {
      showUserMenu.value = false;
    }
  });
});

onUnmounted(() => {
  document.removeEventListener('click', () => {});
});
</script>

<style scoped>
/*
* Global Styles and Full-Screen Setup
* ------------------------------------
* This section ensures the app takes up the entire screen space and
* establishes a modern, clean foundation for all components.
*/
:root {
  --primary-color: #2563eb;
  --primary-dark: #1d4ed8;
  --secondary-color: #10b981;
  --accent-color: #f59e0b;
  --danger-color: #ef4444;
  --warning-color: #f97316;
  --success-color: #22c55e;
  --info-color: #06b6d4;
  
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  
  --white: #ffffff;
  --black: #000000;
  
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  --border-radius: 0.5rem; /* 8px */
  --border-radius-lg: 0.75rem; /* 12px */
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Base styles for full-screen layout */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* Updated: The main body now handles scrolling */
html, body {
  height: 100%;
  width: 100%;
  overflow-x: hidden;
  overflow-y: auto; /* The entire page is now scrollable */
  font-family: var(--font-sans);
  color: var(--gray-900);
  background: var(--gray-50);
}

.dashboard-container {
  min-height: 100vh; /* This ensures the container is at least the height of the viewport */
  display: flex;
  flex-direction: column;
 
}

/*
* Navbar
* ------------------------------------
* Fixed-top navigation bar with a blur effect for a modern feel.
*/
.navbar {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 1000;
  border-bottom: 1px solid var(--gray-200);
}

.navbar-content {
  height: 4rem;
  padding: 0 1rem;
  width: 100%;
}

.navbar-left, .navbar-right {
  gap: 1rem;
}

.nav-button {

  width: 2.5rem;
  height: 2.5rem;
  border: none;
  background: transparent;
  color: var(--gray-600);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;
}

.nav-button:hover {
  background: var(--gray-100);
  color: var(--gray-900);
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
  user-select: none;
}

.brand-text {
  display: none; /* Hidden on mobile */
}

.search-container {
  width: 100vw;
  
}

.search-input {
  width: 100vw;
  padding: 0.75rem 0.75rem 0.75rem 2.5rem;
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius);
  background: var(--gray-50);
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  background: var(--white);
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400);
}

.search-shortcut {
  display: none; /* Hidden on mobile */
}

.user-menu {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.2s ease;
}

.user-menu:hover {
  background: var(--gray-100);
}

.user-avatar {
  font-size: 2.25rem;
  color: var(--primary-color);
}

.user-info {
  display: none;
}

.dropdown-arrow {
  color: var(--gray-400);
  font-size: 0.75rem;
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  min-width: 12rem;
  background: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-lg);
  padding: 0.5rem 0;
  z-index: 100;
}

.opacity .navbar {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 1000;
  border-bottom: 1px solid var(--gray-200);
  box-shadow: var(--shadow-sm); /* Subtle floating effect */
}

.navbar-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 4rem;
  padding: 0 1.5rem;
}

.navbar-left, .navbar-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border: none;
  background: transparent;
  color: var(--gray-600);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;
  font-size: 1rem;
}

.nav-button:hover {
  background: var(--gray-100);
  color: var(--gray-900);
}

.nav-text {
  display: none;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
  user-select: none;
}

.brand-icon {
  font-size: 1.5rem;
}

.brand-text {
  display: none;
}

/* Search Container */
.search-container {
  position: relative;
  flex-grow: 1;
  max-width: 32rem;
  margin: 0 2rem;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400);
  font-size: 1rem;
}

.search-input {
  width: 100%;
  padding: 0.75rem 0.75rem 0.75rem 2.5rem;
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius);
  background: var(--gray-50);
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  background: var(--white);
}

.search-shortcut {
  display: none; /* Hidden on mobile */
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: var(--gray-100);
  color: var(--gray-500);
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid var(--gray-200);
}

/* Notification Button */
.notification-btn {
  position: relative;
}

.notification-badge {
  position: absolute;
  top: -0.25rem;
  right: -0.25rem;
  background: var(--danger-color);
  color: var(--white);
  font-size: 0.75rem;
  font-weight: 600;
  min-width: 1.25rem;
  height: 1.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.1rem;
}

/* User Menu */
.user-menu {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.2s ease;
}

.user-menu:hover {
  background: var(--gray-100);
}

.user-avatar {
  font-size: 2rem;
  color: var(--primary-color);
}

.user-info {
  display: none;
}

.user-name {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--gray-900);
}

.user-role {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.dropdown-arrow {
  color: var(--gray-400);
  font-size: 0.75rem;
  margin-left: 0.5rem;
  transition: transform 0.2s ease;
}

.user-menu:hover .dropdown-arrow {
  transform: rotate(180deg);
}

/* Dropdown Menu */
.dropdown-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  min-width: 12rem;
  background: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-lg);
  padding: 0.5rem 0;
  z-index: 100;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-0.5rem);
  transition: all 0.2s ease;
}

.dropdown-menu.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: var(--gray-700);
  text-decoration: none;
  font-size: 0.875rem;
  transition: background 0.2s ease;
}

.dropdown-item:hover {
  background: var(--gray-50);
}

.dropdown-divider {
  height: 1px;
  background: var(--gray-200);
  margin: 0.5rem 0;
}

/*
* Main Content Layout
* ------------------------------------
* This section manages the main dashboard area, now without its own scrollbar.
*/
.main-layout {
  display: flex;
  flex: 1;
   overflow: hidden;
}

.main-content {
  flex: 1;
  padding: 0.8rem 1rem;
     overflow: hidden;

}

.content-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

.page-subtitle {
  font-size: 0.875rem;
  color: var(--gray-600);
  margin: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.view-controls {
  display: flex;
  gap: 0.25rem;
  background: var(--gray-100);
  border-radius: var(--border-radius);
  padding: 0.25rem;
}

.view-btn {
  border: none;
  background: transparent;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.375rem; /* 6px */
  cursor: pointer;
  color: var(--gray-500);
  transition: all 0.2s ease;
}

.view-btn.active {
  background: var(--white);
  color: var(--primary-color);
  box-shadow: var(--shadow-sm);
}

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius);
  background: var(--white);
  font-size: 0.875rem;
  color: var(--gray-700);
  cursor: pointer;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280'%3E%3Cpath d='M6 8l4 4 4-4' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 0.75rem;
}

/*
* Apps Grid and List
* ------------------------------------
* Refined styles for the app cards and list items.
*/
.apps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
}

.app-card {
  background: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: var(--border-radius-lg);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.app-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.app-card:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  border-color: var(--primary-color);
}

.app-card-content {
  padding: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.app-icon {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: var(--border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
  font-size: 1.5rem;
  flex-shrink: 0;
  box-shadow: var(--shadow-sm);
}

.app-name {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.app-description {
  font-size: 0.875rem;
  color: var(--gray-600);
  margin: 0.25rem 0 0 0;
}

.app-actions {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.app-card:hover .app-actions {
  opacity: 1;
}

.action-btn {
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  background: var(--white);
  border: 1px solid var(--gray-200);
  color: var(--gray-500);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.action-btn:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.apps-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.app-list-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.app-list-item:hover {
  transform: translateX(4px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.app-list-item:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  border-color: var(--primary-color);
}

.app-list-item .app-actions {
  position: static;
  opacity: 1;
}

/*
* Responsive Breakpoints
* ------------------------------------
* This section uses media queries to adapt the layout for different screen sizes.
*/
/* Tablet and larger */
@media (min-width: 640px) {
  .brand-text {
    display: inline-block;
  }
  .nav-button {
    width: auto;
    height: auto;
    padding: 0.5rem 0.75rem;
  }
  .nav-text {
    display: inline-block;
  }
  .navbar-content {
    padding: 0 1.5rem;
  }
  .main-content {
    /* padding: 2rem 1.5rem; */
  }
  .header-content {
    flex-direction: row;
    justify-content: space-between;
  }
  .search-shortcut {
    display: inline-block;
  }
}

/* Desktop and larger */
@media (min-width: 1024px) {
  .navbar-content {
    padding: 0 2rem;
  }
  .main-content {
    /* padding: 2.5rem 2rem; */
  }
  .user-info {
    display: flex;
  }
  .user-menu {
    gap: 0.75rem;
    padding: 0.5rem;
  }
  .search-container {
    max-width: 32rem;
  }
  .apps-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  }
}

/* Widescreen and larger */
@media (min-width: 1280px) {
  .navbar-content,
  .header-content,
  .apps-section {
    margin-left: auto;
    margin-right: auto;
  }
}
</style>