<template>
    <div>
        <NavBar />
        <div class="max-w-5xl mx-auto mt-8 px-4 space-y-6">
            <h1 class="text-3xl font-bold">Orders & Wallet Overview</h1>

            <!-- Balances -->
            <section class="bg-white shadow rounded p-4">
                <h2 class="text-xl font-semibold mb-2">Balances</h2>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                    <div class="p-3 border rounded">
                        <div class="text-xs text-gray-500">USD</div>
                        <div class="font-mono">{{ Number(balance).toFixed(2) }}</div>
                    </div>
                    <div v-for="(asset, i) in assets" :key="symbol" class="p-3 border rounded">
                        <div class="text-xs text-gray-500">{{ asset.symbol }}</div>
                        <div class="font-mono">{{ Number(asset.amount).toFixed(6) }}</div>
                    </div>
                </div>
            </section>

            <!-- Orderbook + symbol selector -->
            <section class="bg-white shadow rounded p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xl font-semibold">Orderbook</h2>
                    <select v-model="selectedSymbol" class="border rounded px-2 py-1">
                        <option v-for="token in tokens" :key="token.symbol" :value="token.symbol">{{ token.symbol }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-red-600 mb-1">Asks</h3>
                        <ul class="space-y-1 max-h-64 overflow-auto">
                            <li v-for="l in orderbook.asks" :key="'a' + l.price"
                                class="flex justify-between font-mono text-sm">
                                <span>{{ l.price }}</span>
                                <span>{{ l.size }}</span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-green-600 mb-1">Bids</h3>
                        <ul class="space-y-1 max-h-64 overflow-auto">
                            <li v-for="l in orderbook.bids" :key="'b' + l.price"
                                class="flex justify-between font-mono text-sm">
                                <span>{{ l.price }}</span>
                                <span>{{ l.size }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- All Orders -->
            <section class="bg-white shadow rounded p-4">
                <h2 class="text-xl font-semibold mb-2">All Orders</h2>
                <div class="overflow-x-auto">
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
                                <td class="py-2 pr-4 font-mono" :title="o.price">${{ Number(o.price).toFixed(2) }}</td>
                                <td class="py-2 pr-4 font-mono" :title="o.amount">{{ Number(o.amount).toFixed(6)}}</td>
                                <td class="py-2 pr-4 font-mono" :title="o.usd_amount">{{ Number(o.usd_amount).toFixed(4)}}</td>
                                <td class="py-2 pr-4">
                                    <span class="px-2 py-0.5 rounded text-xs" :class="statusClass(o.status_name)">{{ o.status_name  }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
import api from "../api/axios";
import { usePusher } from "../composables/usePusher";

export default {
    components: { NavBar },
    setup() {
        const { fetchUser } = useAuth();
        const { fetchOrders, cancelOrder } = useOrders();
        return {
            fetchUser,
            fetchOrders,
            cancelOrder
        };
    },
    data() {
        return {
            userId: null,
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
    computed: {

    },
    methods: {
        fetchTokens() {
            api("/tokens")
                .then(res => { this.tokens = res.data || [] })
                .catch(() => {})
                .finally(() => {});
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
        normalizeLevels(arr = []) {
            return arr.map(l => Array.isArray(l) ? ({ price: l[0], size: l[1] }) : ({ price: l.price, size: l.size }));
        },
        async fetchOrderbook() {
            if (!this.selectedSymbol) return;
            const r = await fetch(`/api/orderbook?symbol=${encodeURIComponent(this.selectedSymbol)}`).catch(() => null);
            if (!r || !r.ok) return;
            const data = await r.json();
            this.orderbook = {
                bids: this.normalizeLevels(data.bids || []),
                asks: this.normalizeLevels(data.asks || [])
            };
        },
        statusClass(status) {
            return {
                "bg-gray-100 text-gray-800": ["open", "pending"].includes((status || "").toLowerCase()),
                "bg-green-100 text-green-800": ["filled"].includes((status || "").toLowerCase()),
                "bg-red-100 text-red-800": ["cancelled", "canceled"].includes((status || "").toLowerCase()),
            };
        },
        applyOrderMatchToOrders(evt) {
            const id = evt.orderId;
            const idx = this.orders.findIndex(o => (o.id || o.orderId) === id);
            if (idx >= 0) {
                const o = { ...this.orders[idx] };
                o.filled = (o.filled ?? 0) + (evt.size ?? evt.filledSize ?? 0);
                if (evt.status) o.status = evt.status;
                this.orders.splice(idx, 1, o);
            } else {
                this.fetchOrders();
            }
        },
    },
    mounted() {
        Promise.all([this.fetchTokens(), this.fetchProfile(), this.fetchUserOrders()])
            .then(() => {
                console.log("Initial data loaded");
                usePusher(null, (evt) => {
                    console.log("Received order match event:", evt);
                    this.applyOrderMatchToOrders(evt);
                });
            });
    },
    unmounted() {
        try { this.ws && this.ws.close(); } catch { }
    },
    watch: {
        
    }
};
</script>

<style scoped>
/* small styling if needed */
</style>
