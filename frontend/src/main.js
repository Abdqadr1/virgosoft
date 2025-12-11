import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: false,
    encrypted: false,
    wsPort: 6001,
    wssPort: 6001,
    wsHost: '127.0.0.1',
    enabledTransports: ["ws"],

    // IMPORTANT WHEN FRONTEND IS SEPARATE
    authEndpoint: `${import.meta.env.VITE_SERVER_URL}/broadcasting/auth`,

    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
    },
});

createApp(App)
    .use(router)
    .mount('#app')
