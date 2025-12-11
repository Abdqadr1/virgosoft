<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Class Trade
 * @property int $id
 * @property int $buy_order_id
 * @property int $sell_order_id
 * @property string $symbol
 * @property float $price
 * @property float $amount
 * @property float $usd_amount
 * @property float $commission
 */
class Trade extends Model
{

    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }

    public function buyer(): HasOneThrough {
        return $this->hasOneThrough(
            User::class,
            Order::class,
            'id',
            'id',
            'buy_order_id',
            'user_id'
        );
    }

    public function seller(): HasOneThrough {
        return $this->hasOneThrough(
            User::class,
            Order::class,
            'id',
            'id',
            'sell_order_id',
            'user_id'
        );
    }
    
}
