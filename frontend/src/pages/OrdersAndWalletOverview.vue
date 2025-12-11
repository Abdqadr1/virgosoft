<template>
    <div>
        <NavBar />
        <div class="max-w-5xl mx-auto mt-8 px-4 space-y-6">
            <h1 class="text-3xl font-bold">Orders & Wallet Overview</h1>

            <!-- Balances -->
            <section class="bg-white shadow rounded p-4">
                <h2 class="text-xl font-semibold mb-2">Balances</h2>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                    <div class="p-3 inline-flex flex-col gap-2 border rounded">
                        <div class="text-xs text-gray-500">USD</div>
                        <div class="font-mono text-sm">{{ formatCrypto(balance) }}</div>
                    </div>
                    <div v-for="(asset, i) in assets" :key="asset.symbol || i"
                        class="p-3 inline-flex flex-col gap-2 border rounded">
                        <div class="text-xs text-gray-500">{{ asset.symbol }}</div>
                        <div class="font-mono text-sm" :title="asset.amount">{{ formatCrypto(asset.amount) }}</div>
                    </div>
                </div>
            </section>

            <!-- Orderbook + symbol selector -->
            <section class="bg-white shadow rounded p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xl font-semibold">Orderbook</h2>
                    <select v-model="selectedSymbol" class="border rounded px-2 py-1">
                        <option v-for="token in tokens" :key="token.symbol" :value="token.symbol">{{ token.symbol }}
                        </option>
                    </select>
                </div>
                <div class="space-y-4">
                    <ul v-if="recentTrades.length" class="space-y-1 max-h-64 overflow-auto">
                        <li v-for="trade in recentTrades" :key="trade.id"
                            class="flex justify-between font-mono text-sm">
                            <span>{{ trade.symbol }}</span>
                            <span :title="trade.usd_volume">{{ formatCrypto(trade.usd_volume) }}</span>
                            <span :title="trade.amount">{{ formatCrypto(trade.amount) }}</span>
                            <span :title="trade.price">${{ formatCrypto(trade.price) }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-gray-500 text-sm">No recent trades available.</p>
                </div>
            </section>

            <!-- All Orders -->
            <section class="bg-white shadow rounded p-4">
                <h2 class="text-xl font-semibold mb-2">All Orders</h2>
                <div class="overflow-x-auto">
                    <template v-if="orders.length">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs text-gray-500">
                                <tr class="border-b">
                                    <th class="py-2 pr-4">ID</th>
                                    <th class="py-2 pr-4">Symbol</th>
                                    <th class="py-2 pr-4">Side</th>
                                    <th class="py-2 pr-4">Token Price</th>
                                    <th class="py-2 pr-4">Amount</th>
                                    <th class="py-2 pr-4">Paid</th>
                                    <th class="py-2 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="o in orders" :key="o.id || o.orderId" class="border-t">
                                    <td class="py-2 pr-4 font-mono">{{ o.id }}</td>
                                    <td class="py-2 pr-4">{{ o.symbol }}</td>
                                    <td class="py-2 pr-4 font-mono">{{ o.side }}</td>
                                    <td class="py-2 pr-4 font-mono" :title="o.price">${{ formatCrypto(o.price) }}</td>
                                    <td class="py-2 pr-4 font-mono" :title="o.amount">{{ formatCrypto(o.amount) }}</td>
                                    <td class="py-2 pr-4 font-mono" :title="o.usd_amount">{{ formatCrypto(o.usd_amount)
                                    }}</td>
                                    <td class="py-2 pr-4">
                                        <span class="px-2 py-0.5 rounded text-xs" :class="statusClass(o.status_name)">{{
                                            o.status_name }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                    <p v-else class="text-gray-500 text-sm">No orders available.</p>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import NavBar from "../components/NavBar.vue";
import Swal from "sweetalert2";
import useAuth from "../composables/useAuth";
import { useOrders } from "../composables/useOrders";
import { useFormat } from "../composables/useFormat";
import api from "../api/axios";

const { fetchUser, user } = useAuth();
const { fetchOrders } = useOrders();
const { formatCrypto } = useFormat();

const echo = ref(null);
const balance = ref(0);
const assets = ref([]);
const tokens = ref([]);
const orders = ref([]);
const recentTrades = ref([]);
const selectedSymbol = ref("BTC");
const userId = ref(null);
const channelName = ref(null);

// Methods
const pusherListen = () => {
    echo.value = window.Echo;

    const pusher = echo.value.connector.pusher;
    const connectionState = pusher.connection.state;

    if (connectionState === 'connected') {
        console.log('Pusher already connected.');
    }

    channelName.value = `matchup.${user.value.id}`;

    echo.value.private(channelName.value)
        .listen(".OrderMatched", (event) => {
            // console.log("Received OrderMatched event:", event);
            handleMatchUp(event.trade);
        }).error((error) => {
            console.error("Error subscribing to channel:", error);
        });

    const connectedHandler = () => {
        console.log('Pusher connected successfully.');
    };

    const errorHandler = (err) => {
        console.error('Pusher connection error:', err);
    };

    pusher.connection.bind('connected', connectedHandler);
    pusher.connection.bind('error', errorHandler);
};

const fetchTokens = () => {
    api("/tokens")
        .then(res => { tokens.value = res.data || [] })
        .catch(() => { })
        .finally(() => { });
};

const fetchProfile = async () => {
    fetchUser().then(userData => {
        if (userData && userData.balance) {
            balance.value = userData.balance;
            assets.value = userData.assets || [];
            userId.value = userData.id;
        }
    });
};

const fetchUserOrders = async () => {
    fetchOrders()
        .then(data => {
            orders.value = data || [];
        });
};

const statusClass = (status) => {
    return {
        "bg-gray-100 text-gray-800": ["open", "pending"].includes((status || "").toLowerCase()),
        "bg-green-100 text-green-800": ["filled"].includes((status || "").toLowerCase()),
        "bg-red-100 text-red-800": ["cancelled", "canceled"].includes((status || "").toLowerCase()),
    };
};

const handleMatchUp = (trade) => {
    if (!trade) return;

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `Trade matched: ${formatCrypto(trade.amount)} ${trade.symbol} at $${formatCrypto(trade.price)}`,
        showConfirmButton: false,
        timer: 5000
    });

    if (trade.symbol === selectedSymbol.value && recentTrades.value.find(t => t.id === trade.id) === undefined) {
        recentTrades.value.unshift(trade);
    }

    Promise.all([fetchProfile(), fetchUserOrders()]);
};

// Lifecycle
onMounted(() => {
    Promise.all([fetchTokens(), fetchProfile(), fetchUserOrders()])
        .then(() => {
            pusherListen();
        });
});

onUnmounted(() => {
    if (echo.value && channelName.value) {
        console.log('leaving channel:', channelName.value);
        echo.value.leave(channelName.value);
    }
});

</script>
