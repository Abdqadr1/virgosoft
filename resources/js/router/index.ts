
import { createRouter as _createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Dashboard from '@/pages/Dashboard.vue';
import Login from '@/pages/Login.vue';

const routes: Array<RouteRecordRaw> = [
    { path: '/', component: Dashboard },
    { path: '/login', component: Login },
];

export function createRouter() {
    return _createRouter({
        history: createWebHistory(),
        routes,
    });
}
