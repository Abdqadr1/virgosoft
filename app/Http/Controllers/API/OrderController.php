<?php 

namespace App\Http\Controllers\API;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{

    public function __construct(
        private OrderService $orderService
    ){}

    public function list(Request $request)
    {
        $symbol = $request->query('symbol', 'BTC');
        $orders = Order::where('symbol', $symbol)
                    ->where('status', OrderStatus::OPEN)
                    ->latest()
                    ->get();

        return response()->json($orders);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required', 'exists:tokens,symbol'],
            'side' => ['required', Rule::enum(OrderSide::class)],
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($user, $data) {
            $totalUsd = bcmul($data['price'], $data['amount'], 8); // string math could be used; here use bc
            $order = Order::create([
                'user_id' => $user->id,
                'symbol' => $data['symbol'],
                'side' => $data['side'],
                'price' => $data['price'],
                'amount' => $data['amount'],
                'status' => OrderStatus::OPEN,
            ]);

            if ($data['side'] === 'buy') {
                // lock buyer balance
                // reload user with FOR UPDATE
                $dbUser = User::where('id', $user->id)->lockForUpdate()->first();
                if (bccomp($dbUser->balance, $totalUsd, 8) < 0) {
                    throw new \Exception('Insufficient USD balance');
                }
                // deduct funds (money is reserved by reducing balance immediately)
                $dbUser->balance = bcsub($dbUser->balance, $totalUsd, 8);
                $dbUser->save();
                // we store filled_amount (0) and keep track via balance deduction; if cancelled we refund
            } else {
                // lock seller asset row
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $data['symbol'])
                    ->lockForUpdate()
                    ->first();

                if (!$asset || bccomp($asset->amount, $data['amount'], 8) < 0) {
                    throw new \Exception('Insufficient asset amount');
                }
                // move amount to locked_amount
                $asset->amount = bcsub($asset->amount, $data['amount'], 8);
                $asset->locked_amount = bcadd($asset->locked_amount, $data['amount'], 8);
                $asset->save();
            }

            // After creating the order and locking funds/assets, attempt matching
            $this->orderService->attemptMatch($order);

            return response()->json($order->fresh());
        });
    }

    public function cancel(Request $request, Order $order)
    {
        $user = $request->user();

        return DB::transaction(function () use ($order, $user) {
            $order = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            if ($order->user_id !== $user->id) return response()->json(['error' => 'Not allowed'], 403);
            if ( $order->status != OrderStatus::OPEN ) return response()->json(['error' => 'Order not open'], 400);

            // Release locked funds / assets
            if ($order->side === OrderSide::BUY) {
                $usdLocked = bcmul($order->price, bcsub($order->amount, $order->filled_amount, 8), 8);
                $dbUser = User::where('id', $user->id)->lockForUpdate()->first();
                $dbUser->balance = bcadd($dbUser->balance, $usdLocked, 8);
                $dbUser->save();
            } else {
                // seller: move locked_amount back to amount
                $asset = Asset::where('user_id', $user->id)->where('symbol', $order->symbol)->lockForUpdate()->first();
                $remaining = bcsub($order->amount, $order->filled_amount, 8);
                $asset->locked_amount = bcsub($asset->locked_amount, $remaining, 8);
                $asset->amount = bcadd($asset->amount, $remaining, 8);
                $asset->save();
            }
            $order->status = OrderStatus::CANCELLED;
            $order->save();

            return response()->json(['ok' => true, 'order' => $order]);
        });
    }

}