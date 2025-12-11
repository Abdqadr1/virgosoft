<?php

namespace App\Models;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Order
 * @property int $id
 * @property float $commission
 * @property float $price
 * @property float $amount
 * @property string $status_name
 * @property OrderStatus $status
 * @property OrderSide $side
 */
class Order extends Model
{

    protected $casts = [
        'status' => OrderStatus::class,
        'side' => OrderSide::class,
    ];

    protected $appends = ['status_name'];

    public function getStatusNameAttribute(): string
    {
        return $this->status->name;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
}
