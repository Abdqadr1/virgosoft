import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { onMounted, onUnmounted } from "vue";

export function usePusher(userId, callback) {
    let echo = null;

    onMounted(() => {
        echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            encrypted: true,
        });

        echo.private(`user.${userId}`)
            .listen("OrderMatched", (event) => {
                callback(event.trade);
            });
    });

    onUnmounted(() => {
        if (echo) echo.disconnect();
    });
}
