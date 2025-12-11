<template>
    <div>
        <NavBar />
        <div class="max-w-4xl mx-auto mt-8 px-4">
            <h1 class="text-3xl font-bold mb-4">Limit Order</h1>
            <p class="text-gray-700">Place a limit order by specifying the details below.</p>

            <!-- Limit Order Form -->
            <div class="mt-6 bg-white shadow rounded p-6 max-w-md mx-auto">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Symbol -->
                    <div>
                        <label for="symbol" class="block text-sm font-medium text-gray-700">Symbol</label>
                        <select id="symbol" v-model="symbol"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-gray-400 focus:ring-0 focus:outline-none"
                            :disabled="tokensLoading">
                            <option disabled value="">Select a token</option>
                            <option v-for="token in tokens" :key="token.symbol" :value="token.symbol">
                                {{ token.symbol }}
                            </option>
                        </select>
                    </div>

                    <!-- Side -->
                    <div>
                        <label for="side" class="block text-sm font-medium text-gray-700">Side</label>
                        <select id="side" v-model="side"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-gray-400 focus:ring-0 focus:outline-none">
                            <option value="buy">BUY</option>
                            <option value="sell">SELL</option>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                        <input id="amount" type="number" step="0.0001" min="0" v-model.number="amount"
                            placeholder="Enter amount"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-gray-400 focus:ring-0 focus:outline-none" />
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-0"
                            :disabled="isLoading">
                            {{ isLoading ? 'Submitting' : 'Place Order' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import NavBar from "../components/NavBar.vue";
import { useOrders } from "../composables/useOrders";
import Swal from "sweetalert2";
import api from "../api/axios";

export default {
    name: "LimitOrderForm",
    components: { NavBar },
    data() {
        return {
            symbol: "",
            side: "buy",
            amount: null,
            isLoading: false,
            tokens: [],
            tokensLoading: false,
            tokensError: "",
        };
    },
    mounted() {
        this.fetchTokens();
    },
    methods: {
        async fetchTokens() {
            this.tokensLoading = true;
            this.tokensError = "";
            api("/tokens")
                .then(res => {
                    this.tokens = res.data || [];
                })
                .catch(() => {
                    this.tokensError = "Unable to load tokens.";
                })
                .finally(() => {
                    this.tokensLoading = false;
                });
        },
        submit() {
            if (!this.symbol || !this.side || !this.amount) {
                Swal.fire("Error", "Please fill in all fields.", "error");
                return;
            }

            const payload = {
                symbol: this.symbol,
                side: this.side,
                amount: this.amount,
            };

            const { placeOrder } = useOrders();
            this.isLoading = true;
            placeOrder(payload)
                .then(() => {
                    Swal.fire({
                        title: "Success",
                        text: "Order placed successfully.",
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonText: "OK",
                        cancelButtonText: "Wallet Overview",
                    }).then((result) => {
                        if (result.isDismissed) {
                            this.$router.push("/");
                            return;
                        }
                        this.clearForm();
                    });
                })
                .catch((err) => {
                    const error = err?.response?.data?.message || "Failed to place order.";
                    Swal.fire("Error", error || "Invalid credentials", "error");
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        clearForm() {
            this.symbol = "";
            this.side = "buy";
            this.amount = null;
        },
    },
};
</script>

<style scoped>
/* small styling if needed */
</style>
