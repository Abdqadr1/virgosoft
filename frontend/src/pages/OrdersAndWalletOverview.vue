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
                    <div v-for="(asset, i) in assets" :key="asset.symbol || i" class="p-3 inline-flex flex-col gap-2 border rounded">
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
                            <span :title="trade.usd_volume">{{ formatCrypto(trade.usd_volume)}}</span>
                            <span :title="trade.amount">{{ formatCrypto(trade.amount)}}</span>
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
                                    <td class="py-2 pr-4 font-mono" :title="o.usd_amount">{{ formatCrypto(o.usd_amount) }}</td>
                                    <td class="py-2 pr-4">
                                        <span class="px-2 py-0.5 rounded text-xs" :class="statusClass(o.status_name)">{{ o.status_name }}</span>
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

<script>
import NavBar from "../components/NavBar.vue";
import Swal from "sweetalert2";
import useAuth from "../composables/useAuth";
import { useOrders } from "../composables/useOrders";
import { useFormat } from "../composables/useFormat";
import api from "../api/axios";

export default {
    components: { NavBar },
    setup() {
        const { fetchUser, user } = useAuth();
        const { fetchOrders, cancelOrder } = useOrders();
        const { formatCrypto } = useFormat();
        return {
            user,
            fetchUser,
            fetchOrders,
            cancelOrder,
            formatCrypto
        };
    },
    data() {
        return {
            echo: null,
            balance: 0,
            assets: [],
            tokens: [],
            orders: [],
            recentTrades: [],
            selectedSymbol: "BTC",
            orderbook: { bids: [], asks: [] },
            ws: undefined,
        };
    },
    methods: {
        pusherListen() {
            this.echo = window.Echo;
            this.echo.private(`matchup.${this.user.id}`)
                .listen(".OrderMatched", (event) => {
                    console.log("Received OrderMatched event:", event);
                    this.handleMatchUp(event.trade);
                }).error((error) => {
                    console.error("Error subscribing to channel:", error);
                });

            this.echo.connector.pusher.connection.bind('connected', function (err) {
                console.log('Pusher connected successfully.');
            });
            this.echo.connector.pusher.connection.bind('error', function (err) {
                console.error('Pusher connection error:', err);
            });
        },
        fetchTokens() {
            api("/tokens")
                .then(res => { this.tokens = res.data || [] })
                .catch(() => { })
                .finally(() => { });
        },
        async fetchProfile() {
            this.fetchUser().then(user => {
                if (user && user.balance) {
                    this.balance = user.balance;
                    this.assets = user.assets || [];
                    this.userId = user.id;
                }
            });
        },
        async fetchUserOrders() {
            this.fetchOrders()
                .then(data => {
                    this.orders = data || [];
                });
        },
        statusClass(status) {
            return {
                "bg-gray-100 text-gray-800": ["open", "pending"].includes((status || "").toLowerCase()),
                "bg-green-100 text-green-800": ["filled"].includes((status || "").toLowerCase()),
                "bg-red-100 text-red-800": ["cancelled", "canceled"].includes((status || "").toLowerCase()),
            };
        },
        handleMatchUp(trade) {
            if (!trade) return;

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Trade matched: ${this.formatCrypto(trade.amount)} ${trade.symbol} at $${this.formatCrypto(trade.price)}`,
                showConfirmButton: false,
                timer: 5000
            });

            if (trade.symbol === this.selectedSymbol && this.recentTrades.find(t => t.id === trade.id) === undefined) {
                this.recentTrades.unshift(trade);
            }

            Promise.all([this.fetchProfile(), this.fetchUserOrders()]);
        },
    },
    mounted() {
        Promise.all([this.fetchTokens(), this.fetchProfile(), this.fetchUserOrders()])
            .then(() => {
                this.pusherListen();
            });
    },
    unmounted() {
        if (this.echo) this.echo.disconnect();
    },
};
</script>
