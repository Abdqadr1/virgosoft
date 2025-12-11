import { onMounted, onUnmounted } from "vue";

export function usePusher(userId, callback) {
    let echo = null;

    onMounted(() => {
        windows.echo.private(`match-up.${userId}`)
            .listen("OrderMatched", (event) => {
                console.log("OrderMatched event received:", event);
                callback(event.trade);
            });
    });

    onUnmounted(() => {
        if (echo) echo.disconnect();
    });
}
