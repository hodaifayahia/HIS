// src/router/routes/public.js
import HomePage from '@/Pages/Main/HomePage.vue'; // Use @ for alias if configured
import Login from '@/auth/Login.vue';

const publicRoutes = [
    {
        path: '/',
        redirect: '/home',
    },
    {
        path: '/home',
        name: 'home',
        component: HomePage,
    },
    {
        path: '/login',
        name: 'auth.login',
        component: Login,
    },
];

export default publicRoutes;