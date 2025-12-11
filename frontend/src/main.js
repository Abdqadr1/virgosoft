import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Lazy Echo initialization with current token
let currentToken = null;
function initEcho() {
    const token = localStorage.getItem("auth_token");
    if (!token) return;

    // Avoid re-initializing if token hasn't changed
    if (currentToken === token && window.Echo) return;
    currentToken = token;

    // Clean up previous Echo instance
    if (window.Echo && typeof window.Echo.disconnect === 'function') {
        window.Echo.disconnect();
    }

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,

        // IMPORTANT WHEN FRONTEND IS SEPARATE
        authEndpoint: `${import.meta.env.VITE_SERVER_URL}/api/broadcasting/auth`,
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
            },
        },
    });
}

createApp(App)
    .use(router)
    .mount('#app');

// Ensure Echo is initialized after router is ready
router.isReady().then(() => {
    initEcho();
});

// Re-init on each navigation in case the token was just stored after login
router.afterEach(() => {
    initEcho();
});
