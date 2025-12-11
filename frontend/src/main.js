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
    forceTLS: true,

    // IMPORTANT WHEN FRONTEND IS SEPARATE
    authEndpoint: `${import.meta.env.VITE_SERVER_URL}/api/broadcasting/auth`,

    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
            Accept: "application/json",
        },
    },
});

createApp(App)
    .use(router)
    .mount('#app')
