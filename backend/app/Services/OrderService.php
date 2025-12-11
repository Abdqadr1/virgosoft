<?php 

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Exceptions\OrderException;
use App\Jobs\MatchingJob;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Token;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService {

    // Matching - full matches only (no partial)
    public function attemptMatch(Order $newOrder)
    {
        // We'll attempt to find the first matching counter order according to rules.
        // Full-match only: candidate.amount == newOrder.amount
        // For a buy: find first sell where sell.price <= buy.price
        // For a sell: find first buy where buy.price >= sell.price

        DB::transaction(function () use ($newOrder) {
            // Lock the new order for update
            $order = Order::where('id', $newOrder->id)->lockForUpdate()->first();

            if ($order->status != OrderStatus::OPEN) return;

            $symbol = $order->symbol;
            $side = $order->side;
            $amount = $order->amount;
            $price = $order->price;

            $counterSide = $side === OrderSide::BUY ? OrderSide::SELL : OrderSide::BUY;

            $query = Order::where('symbol', $symbol)
                ->where('side', $counterSide)
                ->where('status', OrderStatus::OPEN)
                ->where('amount', $amount);

            if ($side === 'buy') {
                $query->where('price', '<=', $price)->orderBy('price', 'asc')->orderBy('created_at', 'asc');
            } else {
                $query->where('price', '>=', $price)->orderBy('price', 'desc')->orderBy('created_at', 'asc');
            }

            // Lock candidate row. Important: this lock will ensure only one transaction matches the same pair.
            $counter = $query->lockForUpdate()->first();

            if (!$counter) {
                return; // no match
            }

            // Now we have $order and $counter locked. Re-validate funds/asset (in case changed)
            $buyerOrder = $side === OrderSide::BUY ? $order : $counter;
            $sellerOrder = $side === OrderSide::SELL ? $order : $counter;

            $usdVolume = bcmul($buyerOrder->price, $amount, 8);

            // Commission 1.5% of USD volume from buyer
            $commission = bcmul($usdVolume, '0.015', 8);

            // Lock buyer user and seller asset rows
            $buyer = User::where('id', $buyerOrder->user_id)->lockForUpdate()->first();
            $seller = User::where('id', $sellerOrder->user_id)->lockForUpdate()->first();

            // Seller asset
            $sellerAsset = Asset::where('user_id', $sellerOrder->user_id)
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            // ensure buyer exists and hasn't been tampered with. But we must apply commission deduction (commission taken from buyer balance)
            // Commission could exceed buyer balance if buyer's balance was used for multiple parallel orders; to be safe, we check:
            if (bccomp($buyer->balance, $commission, 8) < 0) {
                // buyer doesn't have enough to pay commission -> cancel both orders and refund appropriately
                $order->status = OrderStatus::CANCELLED;
                $order->save();
                $counter->status = OrderStatus::CANCELLED;
                $counter->save();
                // refund buyer reserved USD for the orders (full amounts)
                // compute reserved USD: price*amount (buyerOrder)
                $refund = bcmul($buyerOrder->price, $amount, 8);
                $buyer->balance = bcadd($buyer->balance, $refund, 8);
                $buyer->save();

                // refund seller locked assets
                if ($sellerAsset) {
                    $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
                    $sellerAsset->amount = bcadd($sellerAsset->amount, $amount, 8);
                    $sellerAsset->save();
                }
                return;
            }

            // Transfer asset to buyer
            $buyerAsset = Asset::firstOrNew(['user_id' => $buyer->id, 'symbol' => $symbol]);
            // buyerAsset row lock
            $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol', $symbol)->lockForUpdate()->first();
            if (!$buyerAsset) {
                $buyerAsset = new Asset();
                $buyerAsset->user_id = $buyer->id;
                $buyerAsset->symbol = $symbol;
                $buyerAsset->amount = 0;
                $buyerAsset->locked_amount = 0;
            }

            // Move asset from seller locked_amount to buyer amount
            if (!$sellerAsset || bccomp($sellerAsset->locked_amount, $amount, 8) < 0) {
                // not enough locked asset (shouldn't happen) - abort
                return;
            }

            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
            $sellerAsset->save();

            $buyerAsset->amount = bcadd($buyerAsset->amount ?? '0', $amount, 8);
            $buyerAsset->save();

            // Commission handling: deduct commission from buyer user's *current* balance.
            $buyer->balance = bcsub($buyer->balance, $commission, 8);
            $buyer->save();

            // Credit seller with USD Proceeds minus commission? (we chose commission only from buyer, so seller receives full USD)
            $sellerProceeds = $usdVolume; // all USD goes to seller in this model
            $seller->balance = bcadd($seller->balance, $sellerProceeds, 8);
            $seller->save();

            // Credit commission to platform user (id=platformId) — set platformId variable or find by is_platform
            $platform = User::where('is_platform', true)->lockForUpdate()->first();
            if ($platform) {
                $platform->balance = bcadd($platform->balance, $commission, 8);
                $platform->save();
            }

            // Update orders as filled
            $order->status = OrderStatus::FILLED;
            $counter->status = OrderStatus::FILLED;
            $order->filled_amount = $amount;
            $counter->filled_amount = $amount;
            $order->save();
            $counter->save();

            // Save Trade record
            $trade = Trade::create([
                'buy_order_id' => $buyerOrder->id,
                'sell_order_id' => $sellerOrder->id,
                'symbol' => $symbol,
                'price' => $buyerOrder->price,
                'amount' => $amount,
                'usd_volume' => $usdVolume,
                'commission_usd' => $commission,
            ]);

            // Broadcast OrderMatched event to both users
            // OrderMatched will contain trade and updated balances/assets and order ids
            event(new OrderMatched($trade, [$buyer->id, $seller->id]));
        });
    }

    public function createOrder(array $data, User $user)
    {
        return DB::transaction(function () use ($user, $data) {

            $price = Token::where('symbol', $data['symbol'])->value('price_usd');

            // volume in USD
            $totalUsd = bcmul($price, $data['amount'], 8);
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
                if (bccomp($dbUser->balance, $totalUsd, 8) < 0) {
                    throw new OrderException('Insufficient USD balance', 422);
                }
                // Deduct amount * price from users.balance
                $dbUser->balance = bcsub($dbUser->balance, $totalUsd, 8);

                // Commission 1.5% of USD volume from buyer
                $commission = bcmul($totalUsd, '0.015', 8);
                // check if user has enough balance to pay commission later during matching
                if (bccomp($dbUser->balance, $commission, 8) < 0) {
                    throw new OrderException('Insufficient USD balance to cover commission', 422);
                }
                // Deduct commission from users.balance
                $dbUser->balance = bcsub($dbUser->balance, $commission, 8);

                // add commission to order
                $order->commission = $commission;

                $dbUser->save();
            } else {
                // lock seller asset row
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $data['symbol'])
                    ->lockForUpdate()
                    ->first();

                if (!$asset || bccomp($asset->amount, $data['amount'], 8) < 0) {
                    throw new OrderException('Insufficient asset amount', 422);
                }
                // move amount to locked_amount
                $asset->amount = bcsub($asset->amount, $data['amount'], 8);
                $asset->locked_amount = bcadd($asset->locked_amount, $data['amount'], 8);
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
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->user_id !== $user->id) return response()->json(['error' => 'Not allowed'], 403);
            if ($order->status != OrderStatus::OPEN) return response()->json(['error' => 'Order not open'], 400);

            // Release locked funds / assets
            if ($order->side === OrderSide::BUY) {
                $dbUser = User::where('id', $user->id)->lockForUpdate()->first();
                $dbUser->balance = bcadd($dbUser->balance, $order->usd_amount, 8);
                $dbUser->save();
            } else {
                // seller: move locked_amount back to amount
                $asset = Asset::where('user_id', $user->id)
                            ->where('symbol', $order->symbol)
                            ->lockForUpdate()
                            ->first();

                $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
                $asset->amount = bcadd($asset->amount, $order->amount, 8);
                $asset->save();
            }
            $order->status = OrderStatus::CANCELLED;
            $order->save();
        });
    }

}