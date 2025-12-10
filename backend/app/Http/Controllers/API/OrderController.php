<?php 

namespace App\Http\Controllers\API;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{

    public function __construct(
        private OrderService $orderService
    ){}

    /**
     * List open orders by symbol
     */
    public function list(Request $request)
    {
        $symbol = $request->query('symbol', 'BTC');
        $orders = Order::where('symbol', $symbol)
                    ->where('status', OrderStatus::OPEN)
                    ->latest()
                    ->get();

        return response()->json($orders);
    }

    /**
     * Create a new limit order
     */
    public function create(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required', 'exists:tokens,symbol'],
            'side' => ['required', Rule::enum(OrderSide::class)],
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $user = $request->user();

        $order = $this->orderService->createOrder($data, $user);

        return response()->json($order );
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, Order $order)
    {
        $user = $request->user();

        $order = $this->orderService->cancelOrder($order->id, $user);

        return response()->json(['ok' => true, 'order' => $order]);
    }

}