<?php 

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchingService {

    // Matching - full matches only (no partial)
    public static function attempt(Order $newOrder)
    {
        // We'll attempt to find the first matching counter order according to rules.
        // Full-match only: candidate.amount == newOrder.amount
        // For a buy: find first sell where sell.price <= buy.price
        // For a sell: find first buy where buy.price >= sell.price

        DB::transaction(function () use ($newOrder) {
            $mathScale = config('app.match_scale');
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

            $usdVolume = bcmul($buyerOrder->price, $amount, $mathScale);

            // Lock buyer user and seller asset rows
            $buyer = User::where('id', $buyerOrder->user_id)->lockForUpdate()->first();
            $seller = User::where('id', $sellerOrder->user_id)->lockForUpdate()->first();

            // Seller asset
            $sellerAsset = Asset::where('user_id', $sellerOrder->user_id)
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            // We already got the commission from the buyer when creating the order by reserving funds.

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
            if (!$sellerAsset || bccomp($sellerAsset->locked_amount, $amount, $mathScale) < 0) {
                // not enough locked asset (shouldn't happen) - abort
                return;
            }

            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, $mathScale);
            $sellerAsset->save();

            $buyerAsset->amount = bcadd($buyerAsset->amount ?? '0', $amount, $mathScale);
            $buyerAsset->save();

            // Credit seller with USD Proceeds minus commission? (we chose commission only from buyer, so seller receives full USD)
            $sellerProceeds = $usdVolume; // all USD goes to seller in this model
            $seller->balance = bcadd($seller->balance, $sellerProceeds, $mathScale);
            $seller->save();

            // Update orders as filled
            $order->status = OrderStatus::FILLED;
            $counter->status = OrderStatus::FILLED;
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
                'commission_usd' => $buyerOrder->commission,
            ]);

            // Broadcast OrderMatched event to both users
            event(new OrderMatched($trade, [$buyer->id, $seller->id]));
        });
    }

}