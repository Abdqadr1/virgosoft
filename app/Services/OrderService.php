<?php 

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
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

            if ($order->status != 1) return;

            $symbol = $order->symbol;
            $side = $order->side;
            $amount = $order->amount; // full amount
            $price = $order->price;

            $counterSide = $side === 'buy' ? 'sell' : 'buy';

            // Build query for candidate counter order
            $query = Order::where('symbol', $symbol)
                ->where('side', $counterSide)
                ->where('status', 1)
                ->where('amount', $amount); // full match

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
            $buyerOrder = $side === 'buy' ? $order : $counter;
            $sellerOrder = $side === 'sell' ? $order : $counter;

            $usdVolume = bcmul($buyerOrder->price, $amount, 8);

            // Commission 1.5% of USD volume
            $commission = bcmul($usdVolume, '0.015', 8);

            // Lock buyer user and seller asset rows
            $buyer = User::where('id', $buyerOrder->user_id)->lockForUpdate()->first();
            $seller = User::where('id', $sellerOrder->user_id)->lockForUpdate()->first();

            // Seller asset
            $sellerAsset = Asset::where('user_id', $sellerOrder->user_id)
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            // Validate buyer has enough reserved funds (we previously removed funds from buyer balance on order creation)
            // For our implementation buyer balance already had amount reserved (deducted at order creation). So no further deduction needed,
            // only ensure buyer exists and hasn't been tampered with. But we must apply commission deduction (commission taken from buyer balance)
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

}