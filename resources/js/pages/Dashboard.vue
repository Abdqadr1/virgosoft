<template>
    <div class="container mx-auto p-6">
        <h2 class="text-xl font-bold mb-4">Dashboard</h2>
        <OrderForm />
        <WalletOverview />
        <OrderList />
        <Orderbook />
    </div>
</template>

<script lang="ts" setup>
import OrderForm from "@/components/OrderForm.vue";
import WalletOverview from "@/components/WalletOverview.vue";
import OrderList from "@/components/OrderList.vue";
import Orderbook from "@/components/Orderbook.vue";
import { useWallet } from "@/composables/useWallet";
import { useOrders } from "@/composables/useOrders";
import { useOrderbook } from "@/composables/useOrderbook";
import { usePusher } from "@/composables/usePusher";

const { fetchProfile } = useWallet();
const { fetchOrders } = useOrders();
const { fetchOrderbook } = useOrderbook();

const userId = window.App.user.id as number;

usePusher(userId, () => {
    fetchProfile();
    fetchOrders("BTC");
    fetchOrderbook("BTC");
});
</script>
