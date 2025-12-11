
import { createRouter, createWebHistory } from 'vue-router';
import WalletOverview from './pages/OrdersAndWalletOverview.vue';
import LimitOrderForm from './pages/LimitOrderForm.vue';
import Login from './pages/Login.vue';

const routes = [
    { path: '/', component: WalletOverview },
    { path: '/limit-order', component: LimitOrderForm },
    { path: '/login', component: Login },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;

