<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    public $incrementing = false;
    
    public function getKeyName()
    {
        return ['user_id', 'symbol'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
