import api from "../api/axios";

export function useOrders() {

    const fetchOrders = async (symbol) => {
        const { data } = await api.get('/orders', { params: { symbol } });
        return data;
    };

    const placeOrder = async (order) => {
        await api.post('/orders', order);
    };

    const cancelOrder = async (id, symbol) => {
        await api.post(`/orders/${id}/cancel`);
    };

    return { fetchOrders, placeOrder, cancelOrder };
}
