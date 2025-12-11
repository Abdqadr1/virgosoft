<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Class Asset
 * @property int $id
 * @property int $user_id
 * @property string $symbol
 * @property float $commission
 * @property float $amount
 * @property float $locked_amount
 */
class Asset extends Model
{

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
