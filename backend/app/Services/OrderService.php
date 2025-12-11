<?php 

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Jobs\MatchingJob;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService {

    const MATCH_COMMISSION_RATE = '0.015'; // 1.5%

    public function createOrder(array $data, User $user)
    {
        return DB::transaction(function () use ($user, $data) {

            $mathScale = config('app.match_scale');

            $price = Token::where('symbol', $data['symbol'])->value('price_usd');

            // volume in USD
            $totalUsd = bcmul($price, $data['amount'], $mathScale);
            $order = new Order([
                'user_id' => $user->id,
                'symbol' => $data['symbol'],
                'side' => $data['side'],
                'price' => $price,
                'amount' => $data['amount'],
                'usd_amount' => $totalUsd,
                'status' => OrderStatus::OPEN,
            ]);

            if ($data['side'] === 'buy') {
                // lock buyer balance FOR UPDATE
                $dbUser = User::where('id', $user->id)->lockForUpdate()->first();

                //Check if users.balance >= amount * price
                if (bccomp($dbUser->balance, $totalUsd, $mathScale) < 0) {
                    throw new OrderException('Insufficient USD balance', 422);
                }
                // Deduct amount * price from users.balance
                $dbUser->balance = bcsub($dbUser->balance, $totalUsd, $mathScale);

                // Commission 1.5% of USD volume from buyer
                $commission = bcmul($totalUsd, static::MATCH_COMMISSION_RATE, $mathScale);
                // check if user has enough balance to pay commission later during matching
                if (bccomp($dbUser->balance, $commission, $mathScale) < 0) {
                    throw new OrderException('Insufficient USD balance to cover commission', 422);
                }
                // Deduct commission from users.balance
                $dbUser->balance = bcsub($dbUser->balance, $commission, $mathScale);

                // add commission to order
                $order->commission = $commission;

                $dbUser->save();
            } else {
                // lock seller asset row
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $data['symbol'])
                    ->lockForUpdate()
                    ->first();

                if (!$asset || bccomp($asset->amount, $data['amount'], $mathScale) < 0) {
                    throw new OrderException('Insufficient asset amount', 422);
                }
                // move amount to locked_amount
                $asset->amount = bcsub($asset->amount, $data['amount'], $mathScale);
                $asset->locked_amount = bcadd($asset->locked_amount, $data['amount'], $mathScale);
                $asset->save();
            }

            $order->save();

            // After creating the order and locking funds/assets, attempt matching
            MatchingJob::dispatch($order)->afterCommit();

            return $order->fresh();
        });
    }

    public function cancelOrder(int $orderId, User $user){

        return DB::transaction(function () use ($orderId, $user) {

            $mathScale = config('app.match_scale');

            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->user_id !== $user->id) return response()->json(['error' => 'Not allowed'], 403);
            if ($order->status != OrderStatus::OPEN) return response()->json(['error' => 'Order not open'], 400);

            // Release locked funds / assets
            if ($order->side === OrderSide::BUY) {
                $dbUser = User::where('id', $user->id)->lockForUpdate()->first();
                $dbUser->balance = bcadd($dbUser->balance, $order->usd_amount, $mathScale);
                $dbUser->save();
            } else {
                // seller: move locked_amount back to amount
                $asset = Asset::where('user_id', $user->id)
                            ->where('symbol', $order->symbol)
                            ->lockForUpdate()
                            ->first();

                $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, $mathScale);
                $asset->amount = bcadd($asset->amount, $order->amount, $mathScale);
                $asset->save();
            }
            $order->status = OrderStatus::CANCELLED;
            $order->save();

            return $order->fresh();
        });
    }

}