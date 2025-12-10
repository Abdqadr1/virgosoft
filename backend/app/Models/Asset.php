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
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getQueueableId()
    {
        return $this->user_id . '-' . $this->symbol;
    }

    public function newQueryForRestoration($ids)
    {
        $keys = explode('-', $ids);

        return $this->newQueryWithoutScopes()
            ->where('user_id', $keys[0])
            ->where('symbol', $keys[1]);
    }


}
