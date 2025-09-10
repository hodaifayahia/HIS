import './bootstrap';
import './echo';

import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import { allRoutes as AppRoutes } from '../js/Routes/Routes.js';
import Login from '../js/auth/Login.vue';
import App from '../js/Pages/App.vue';

// ✅ PrimeVue imports (only config and services)
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmDialog from 'primevue/confirmdialog'

import ConfirmationService from 'primevue/confirmationservice';

// ✅ Initialize Pinia
const pinia = createPinia();

// ✅ Create app instance
const app = createApp(App);
app.use(pinia);
    app.component('ConfirmDialog', ConfirmDialog)

// ✅ Configure PrimeVue with custom theme
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            prefix: 'p',
            darkModeSelector: 'system',
            cssLayer: false
            
        }
        
    },
   

});
app.use(PrimeVue, { ripple: true }); // Enable ripple effect for buttons

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
