<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function buyOrder()
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    public function sellOrder()
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }
    
}
