<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const isDropdownOpen = ref(false);
const currentTime = ref(new Date());

// Update time every minute
onMounted(() => {
    setInterval(() => {
        currentTime.value = new Date();
    }, 60000);
});

const logout = async () => {
    try {
        await axios.post('/logout');
        window.location.href = '/login';
    } catch (error) {
        console.error('Error logging out:', error);
    }
};

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

const formatTime = (date) => {
    return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
};

const formatDate = (date) => {
    return date.toLocaleDateString('en-US', { 
        weekday: 'short',
        month: 'short', 
        day: 'numeric' 
    });
};
</script>

<template>
    <nav class="main-header navbar navbar-expand premium-navbar">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link sidebar-toggle" data-widget="pushmenu" href="#" role="button">
                    <div class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </a>
            </li>
        </ul>

        <!-- Center - Time Display -->
        <div class="navbar-center">
            <div class="time-display">
                <div class="current-time">{{ formatTime(currentTime) }}</div>
                <div class="current-date">{{ formatDate(currentTime) }}</div>
            </div>
        </div>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Dashboard Button -->
            <li class="nav-item">
                <button @click="router.push('/home')" class="nav-btn dashboard-btn" title="Dashboard">
                    <i class="fas fa-th-large"></i>
                    <span class="btn-text">Dashboard</span>
                </button>
            </li>

            <!-- Fullscreen Toggle -->
            <li class="nav-item">
                <button class="nav-btn fullscreen-btn" data-widget="fullscreen" title="Toggle Fullscreen">
                    <i class="fas fa-expand-arrows-alt"></i>
                </button>
            </li>

            <!-- Notifications -->
            <li class="nav-item dropdown">
                <button class="nav-btn notification-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
            </li>

            <!-- User Dropdown -->
            <li class="nav-item dropdown user-dropdown">
                <button class="nav-btn user-btn" @click="toggleDropdown" :class="{ active: isDropdownOpen }">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name">Dr. Smith</span>
                        <i class="fas fa-chevron-down dropdown-arrow" :class="{ rotated: isDropdownOpen }"></i>
                    </div>
                </button>
                
                <div class="dropdown-menu premium-dropdown" :class="{ show: isDropdownOpen }">
                    <div class="dropdown-header">
                        <div class="user-details">
                            <div class="user-avatar-large">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="user-meta">
                                <div class="user-name-large">Dr. John Smith</div>
                                <div class="user-email">john.smith@hospital.com</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dropdown-divider"></div>
                    
                    <router-link to="/admin/profile" class="dropdown-item">
                        <i class="fas fa-user-edit"></i>
                        <span>Edit Profile</span>
                    </router-link>
                    
                    <router-link to="/admin/settings" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </router-link>
                    
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-question-circle"></i>
                        <span>Help & Support</span>
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="#" @click.prevent="logout" class="dropdown-item logout-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</template>

<style scoped>
/* Premium Navbar */
.premium-navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    padding: 0.5rem 1rem;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
}

/* Sidebar Toggle */
.sidebar-toggle {
    background: none;
    border: none;
    padding: 0.5rem;
}

.hamburger-icon {
    width: 24px;
    height: 18px;
    position: relative;
    cursor: pointer;
}

.hamburger-icon span {
    display: block;
    position: absolute;
    height: 2px;
    width: 100%;
    background: white;
    border-radius: 1px;
    opacity: 1;
    left: 0;
    transform: rotate(0deg);
    transition: 0.25s ease-in-out;
}

.hamburger-icon span:nth-child(1) { top: 0px; }
.hamburger-icon span:nth-child(2) { top: 8px; }
.hamburger-icon span:nth-child(3) { top: 16px; }

/* Center Time Display */
.navbar-center {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.time-display {
    text-align: center;
    color: white;
}

.current-time {
    font-size: 1.2rem;
    font-weight: 600;
    line-height: 1;
}

.current-date {
    font-size: 0.8rem;
    opacity: 0.8;
}

/* Navigation Buttons */
.nav-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    margin: 0 0.25rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.dashboard-btn .btn-text {
    font-size: 0.9rem;
    font-weight: 500;
}

.notification-btn {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4757;
    color: white;
    font-size: 0.7rem;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

/* User Dropdown */
.user-dropdown {
    position: relative;
}

.user-btn {
    padding: 0.5rem 1rem;
    gap: 0.75rem;
}

.user-avatar i {
    font-size: 1.5rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 500;
}

.dropdown-arrow {
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.dropdown-arrow.rotated {
    transform: rotate(180deg);
}

/* Premium Dropdown */
.premium-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    min-width: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    margin-top: 0.5rem;
}

.premium-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px 12px 0 0;
    color: white;
}

.user-details {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar-large i {
    font-size: 2.5rem;
}

.user-name-large {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.user-email {
    font-size: 0.85rem;
    opacity: 0.9;
}

.dropdown-divider {
    height: 1px;
    background: #e9ecef;
    margin: 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: #495057;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}

.dropdown-item:hover {
    background: #f8f9fa;
    color: #007bff;
    padding-left: 2rem;
}

.dropdown-item i {
    width: 16px;
    text-align: center;
}

.logout-item:hover {
    background: #fff5f5;
    color: #dc3545;
}

/* Animations */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Responsive */
@media (max-width: 768px) {
    .navbar-center {
        display: none;
    }
    
    .nav-btn .btn-text {
        display: none;
    }
    
    .user-name {
        display: none;
    }
}
</style>
