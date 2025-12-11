<?php 

namespace App\Http\Controllers\API;

use App\Enums\OrderSide;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
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
        $symbol = $request->query('symbol');
        $orders = Order::where('user_id', Auth::id())
                    ->when($symbol, function ($query, $symbol) {
                        $query->where('symbol', $symbol);
                    })
                    ->latest()
                    ->get();

        return response()->json( JsonResource::collection($orders) );
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
        // Ensure the order belongs to the user
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $user = $request->user();

        $order = $this->orderService->cancelOrder($order->id, $user);

        return response()->json(['ok' => true, 'order' => $order]);
    }

}