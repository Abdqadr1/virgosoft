import './bootstrap'; // Laravel bootstrap (axios, CSRF token, etc.)
import '../css/app.css'; // Tailwind CSS

import { createApp } from 'vue';
import App from './App.vue';
import { createRouter } from './router.ts';

// Create Vue app
const app = createApp(App);

// Register router
app.use(createRouter());

// Mount app
app.mount('#app');
