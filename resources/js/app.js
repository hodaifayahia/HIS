import './bootstrap';
import './echo';

import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '@fortawesome/fontawesome-free/css/all.min.css';

import 'admin-lte/dist/css/adminlte.min.css';
import 'admin-lte';

import { createApp } from 'vue/dist/vue.runtime.esm-bundler.js';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import { allRoutes as AppRoutes } from '../js/Routes/Routes.js';
import Login from '../js/auth/Login.vue';
import App from '../js/Pages/App.vue';

// ✅ PrimeVue imports (only config and services)
import PrimeVue from 'primevue/config';
 
import 'primevue/resources/themes/saga-blue/theme.css';
import 'primevue/resources/primevue.min.css';
import ToastService from 'primevue/toastservice';
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast';
import Tooltip from 'primevue/tooltip';

import ConfirmationService from 'primevue/confirmationservice';

// ✅ Initialize Pinia
const pinia = createPinia();

// ✅ Create app instance
const app = createApp(App);
app.use(pinia);

if (import.meta.env.DEV) {
    app.config.devtools = true;
}
    app.component('ConfirmDialog', ConfirmDialog)
    app.component('Toast', Toast)

// ✅ Configure PrimeVue with custom theme and directives
app.use(PrimeVue, {
    theme: {
        options: {
            prefix: 'p',
            darkModeSelector: 'system',
            cssLayer: false
        }
    },
    ripple: true // Enable ripple effect for buttons
});

// ✅ Register Tooltip directive globally
app.directive('tooltip', Tooltip);

// ✅ Add PrimeVue services
app.use(ToastService);
app.use(ConfirmationService);

// ✅ Create router
const router = createRouter({
    history: createWebHistory(),
    routes: AppRoutes,
});


app.use(router);

// ✅ Mount apps
if (window.location.pathname === '/login') {
    const currentApp = createApp(Login);
    currentApp.mount('#login');
} else {
    app.mount('#app');
}
