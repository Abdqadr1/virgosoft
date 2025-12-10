import { ref } from "vue";
import api from "../api/axios";

const orders = ref([]);

export function useOrders() {
    const fetchOrders = async (symbol) => {
        const { data } = await api.get('/orders', { params: { symbol } });
        orders.value = data;
    };

    const placeOrder = async (order) => {
        await api.post('/orders', order);
        await fetchOrders(order.symbol);
    };

    const cancelOrder = async (id, symbol) => {
        await api.post(`/orders/${id}/cancel`);
        await fetchOrders(symbol);
    };

    return { orders, fetchOrders, placeOrder, cancelOrder };
}
