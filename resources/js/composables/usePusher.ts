import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { onMounted, onUnmounted } from "vue";
import type { Trade } from "..";

export function usePusher(userId: number, callback: (trade: Trade) => void) {
    let echo: Echo | null = null;

    onMounted(() => {
        echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY as string,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER as string,
            encrypted: true,
        });

        echo.private(`user.${userId}`)
            .listen("OrderMatched", (event: { trade: Trade }) => {
                callback(event.trade);
            });
    });

    onUnmounted(() => {
        if (echo) echo.disconnect();
    });
}
