import { ref } from "vue";
import api from "../api/axios";
import type { Order } from "..";

const orders = ref<Order[]>([]);

export function useOrders() {
    const fetchOrders = async (symbol: string): Promise<void> => {
        const { data } = await api.get<Order[]>('/orders', { params: { symbol } });
        orders.value = data;
    };

    const placeOrder = async (order: {
        symbol: string;
        side: "buy" | "sell";
        price: number;
        amount: number;
    }): Promise<void> => {
        await api.post('/orders', order);
        await fetchOrders(order.symbol);
    };

    const cancelOrder = async (id: number, symbol: string): Promise<void> => {
        await api.post(`/orders/${id}/cancel`);
        await fetchOrders(symbol);
    };

    return { orders, fetchOrders, placeOrder, cancelOrder };
}
