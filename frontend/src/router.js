
import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './pages/Dashboard.vue';
import Login from './pages/Login.vue';

const routes = [
    { path: '/', component: Dashboard },
    { path: '/login', component: Login },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;

