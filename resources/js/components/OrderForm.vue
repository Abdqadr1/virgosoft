 
<template>
    <form @submit.prevent="submit" class="space-y-4">
        <div>
            <label>Symbol</label>
            <select v-model="symbol" class="input">
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
            </select>
        </div>

        <div>
            <label>Side</label>
            <select v-model="side" class="input">
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
            </select>
        </div>

        <div>
            <label>Price</label>
            <input v-model.number="price" type="number" class="input" />
        </div>

        <div>
            <label>Amount</label>
            <input v-model.number="amount" type="number" class="input" />
        </div>

        <button class="btn-primary">Place Order</button>
    </form>
</template>

<script lang="ts" setup>
import { ref } from "vue";
import { useOrders } from "../composables/useOrders";

const symbol = ref<"BTC" | "ETH">("BTC");
const side = ref<"buy" | "sell">("buy");
const price = ref<number | null>(null);
const amount = ref<number | null>(null);

const { placeOrder } = useOrders();

const submit = async (): Promise<void> => {
    if (!price.value || !amount.value) return;
    await placeOrder({
        symbol: symbol.value,
        side: side.value,
        price: price.value,
        amount: amount.value
    });

    price.value = null;
    amount.value = null;
};
</script>

<style scoped>
.input {
    @apply border rounded p-2 w-full;
}

.btn-primary {
    @apply bg-blue-600 text-white px-4 py-2 rounded;
}
</style>
