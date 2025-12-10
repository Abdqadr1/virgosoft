<?php

namespace App\Models;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{

    protected $casts = [
        'status' => OrderStatus::class,
        'side' => OrderSide::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
}
